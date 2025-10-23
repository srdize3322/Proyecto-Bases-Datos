#!/usr/bin/env php
<?php
// E3/RequestPHP/filtroAtencion.php — depuración de Atencion.csv
// Reglas principales:
//  - ID entero, único y obligatorio.
//  - Fecha normalizada a formato ISO (YYYY-MM-DD).
//  - RUN paciente / médico normalizados (formato XX...-DV) y existentes en Persona_OK.csv.
//  - Diagnóstico limpio (corrige mojibake, trunca a 100). Vacío si efectuada=FALSE.
//  - Efectuada en {TRUE, FALSE}. Si TRUE y diagnóstico vacío -> registro a ERR.
//  - Filas irrecuperables van a *_ERR.csv con detalle en *_LOG.txt.

mb_internal_encoding('UTF-8');

$BASE = realpath(__DIR__.'/..');
$IN   = "$BASE/Old/Atencion.csv";
$OK   = "$BASE/Depurado/Atencion_OK.csv";
$ERR  = "$BASE/Eliminado/Atencion_ERR.csv";
$LOG  = "$BASE/Logs/Atencion_LOG.txt";
$PERSONAS = "$BASE/Depurado/Persona_OK.csv";

@mkdir("$BASE/Depurado",0777,true);
@mkdir("$BASE/Eliminado",0777,true);
@mkdir("$BASE/Logs",0777,true);

if (!is_readable($IN)) {
    fwrite(STDERR, "No puedo leer $IN\n");
    exit(1);
}

// --- Utilidades ---
$stripBom = function ($s) {
    $s = (string)$s;
    if (strncmp($s, "\xEF\xBB\xBF", 3) === 0) {
        return substr($s, 3);
    }
    return $s;
};
$log = fn($h,$m)=>fwrite($h,'['.date('Y-m-d H:i:s')."] $m\n");
$trimRow = function (&$row) {
    foreach ($row as &$c) {
        $c = trim((string)$c);
    }
};
$onlyDigits = fn($s)=>preg_replace('/\D/','', (string)$s);

$fixMojibake = function ($s) {
    $map = [
        '√©'=>'é','√á'=>'á','√í'=>'í','√ó'=>'ó','√ú'=>'ú','√±'=>'ñ','√¨'=>'ü',
        '√É'=>'É','√Á'=>'Á','√Í'=>'Í','√Ó'=>'Ó','√Ú'=>'Ú','√Ñ'=>'Ñ',
        '√¯'=>'í','√≤'=>'ó','√≥'=>'ó','√≠'=>'í','√∫'=>'ú','√°'=>'á','√ß'=>'ó','√¶'=>'ö',
        '√†'=>'†','√ª'=>'ª','√µ'=>'µ','√ì'=>'í','√π'=>'ó','√§'=>'§','√´'=>'ó'
    ];
    return strtr($s, $map);
};

$dv11 = function ($n) {
    $sum = 0; $mul = 2;
    for ($i = strlen($n) - 1; $i >= 0; $i--) {
        $sum += intval($n[$i]) * $mul;
        $mul = $mul == 7 ? 2 : $mul + 1;
    }
    $res = 11 - ($sum % 11);
    return $res == 11 ? '0' : ($res == 10 ? 'K' : (string)$res);
};

$normRUN = function ($raw, &$why=null) use ($dv11, $onlyDigits) {
    $s = strtoupper(str_replace(['.',' '], '', trim((string)$raw)));
    if ($s === '') { $why = 'RUN vacío'; return null; }
    if (strpos($s, '-') !== false) {
        [$body,$dv] = explode('-', $s, 2);
    } else {
        if (strlen($s) < 2) { $why = 'formato RUN'; return null; }
        $body = substr($s, 0, -1);
        $dv   = substr($s, -1);
    }
    $body = $onlyDigits($body);
    $dv   = strtoupper($dv);
    if ($body === '' || strlen($body) < 6 || strlen($body) > 8) {
        $why = 'RUN longitud'; return null;
    }
    if (!preg_match('/^[0-9K]$/', $dv)) {
        $why = 'DV inválido'; return null;
    }
    $calc = $dv11($body);
    if ($calc !== $dv) {
        $dv = $calc;
    }
    return $body.'-'.$dv;
};

$normFecha = function ($f) {
    $f = trim((string)$f);
    if ($f === '') return null;
    $dt = DateTime::createFromFormat('d-m-y', $f);
    if (!$dt) return null;
    return $dt->format('Y-m-d');
};

// --- Cargar RUN válidos de Persona_OK ---
$runValidos = [];
if (is_readable($PERSONAS)) {
    if (($pf = fopen($PERSONAS, 'r')) !== false) {
        $hdr = fgetcsv($pf, 0, ';', '"', '\\');
        if ($hdr !== false) {
            $hdr[0] = $stripBom($hdr[0]);
            $idxRun = array_search('RUN', $hdr);
            if ($idxRun !== false) {
                while (($row = fgetcsv($pf, 0, ';', '"', '\\')) !== false) {
                    if (isset($row[$idxRun])) {
                        $runValidos[trim($row[$idxRun])] = true;
                    }
                }
            }
        }
        fclose($pf);
    }
}

// --- Preparar IO ---
$in  = fopen($IN, 'r');
$ok  = fopen($OK, 'w');
$er  = fopen($ERR, 'w');
$lg  = fopen($LOG, 'w');

$sep = ';'; $enc = '"'; $esc = '\\';
$header = fgetcsv($in, 0, $sep, $enc, $esc);
if ($header === false) {
    fclose($in); fclose($ok); fclose($er); fclose($lg);
    exit(0);
}
foreach ($header as &$c) {
    $c = $stripBom($c);
}
unset($c);
fputcsv($ok, $header, $sep, $enc, $esc);
fputcsv($er, $header, $sep, $enc, $esc);

$IDX_ID    = 0;
$IDX_FECHA = 1;
$IDX_RUNP  = 2;
$IDX_RUNM  = 3;
$IDX_DIAG  = 4;
$IDX_EFECT = 5;

$seenID = [];
$line = 1;

while (($row = fgetcsv($in, 0, $sep, $enc, $esc)) !== false) {
    $line++;
    $raw = $row;
    $trimRow($row);
    if (isset($row[$IDX_ID])) {
        $row[$IDX_ID] = $stripBom($row[$IDX_ID]);
    }

    if (!array_filter($row, fn($c) => $c !== '')) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line fila vacía -> ERR");
        continue;
    }

    $notes = [];

    // ID
    $idRaw = $row[$IDX_ID] ?? '';
    $idDigits = $onlyDigits($idRaw);
    if ($idDigits === '') {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line sin ID -> ERR");
        continue;
    }
    $id = (string)intval($idDigits, 10);
    if ($id !== $idRaw) {
        $notes[] = "ID:'$idRaw'->$id";
    }
    if (isset($seenID[$id])) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line ID duplicado $id -> ERR");
        continue;
    }
    $seenID[$id] = true;

    // Fecha
    $fechaRaw = $row[$IDX_FECHA] ?? '';
    $fechaIso = $normFecha($fechaRaw);
    if ($fechaIso === null) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line fecha inválida '$fechaRaw' -> ERR");
        unset($seenID[$id]);
        continue;
    }
    if ($fechaIso !== $fechaRaw) {
        $notes[] = "fecha:'$fechaRaw'->$fechaIso";
    }

    // RUN paciente
    $why = null;
    $runPaciente = $normRUN($row[$IDX_RUNP] ?? '', $why);
    if ($runPaciente === null) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line RUN paciente inválido ({$row[$IDX_RUNP]} - $why) -> ERR");
        unset($seenID[$id]);
        continue;
    }
    if ($runPaciente !== ($row[$IDX_RUNP] ?? '')) {
        $notes[] = "runpaciente:'{$row[$IDX_RUNP]}'->$runPaciente";
    }
    if (!isset($runValidos[$runPaciente])) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line RUN paciente $runPaciente sin referencia -> ERR");
        unset($seenID[$id]);
        continue;
    }

    // RUN médico
    $why = null;
    $runMedico = $normRUN($row[$IDX_RUNM] ?? '', $why);
    if ($runMedico === null) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line RUN médico inválido ({$row[$IDX_RUNM]} - $why) -> ERR");
        unset($seenID[$id]);
        continue;
    }
    if ($runMedico !== ($row[$IDX_RUNM] ?? '')) {
        $notes[] = "runmedico:'{$row[$IDX_RUNM]}'->$runMedico";
    }
    if (!isset($runValidos[$runMedico])) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line RUN médico $runMedico sin referencia -> ERR");
        unset($seenID[$id]);
        continue;
    }

    // Efectuada
    $efect = strtoupper($row[$IDX_EFECT] ?? '');
    if (!in_array($efect, ['TRUE','FALSE'], true)) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line efectuada inválida '{$row[$IDX_EFECT]}' -> ERR");
        unset($seenID[$id]);
        continue;
    }
    if ($efect !== ($row[$IDX_EFECT] ?? '')) {
        $notes[] = "efectuada:'{$row[$IDX_EFECT]}'->$efect";
    }

    // Diagnóstico
    $diagRaw = $row[$IDX_DIAG] ?? '';
    $diagNorm = $fixMojibake($diagRaw);
    $diagNorm = preg_replace('/\s+/u', ' ', trim($diagNorm));
    if (strcasecmp($diagNorm, 'NULL') === 0) {
        $diagNorm = '';
    }

    if ($efect === 'TRUE') {
        if ($diagNorm === '') {
            fputcsv($er, $raw, $sep, $enc, $esc);
            $log($lg, "L$line efectuada TRUE sin diagnóstico -> ERR");
            unset($seenID[$id]);
            continue;
        }
    } else { // FALSE
        if ($diagNorm !== '') {
            $notes[] = "diagnostico:'$diagNorm'->'' (no efectuada)";
        }
        $diagNorm = '';
    }

    if (mb_strlen($diagNorm, 'UTF-8') > 100) {
        $diagNorm = mb_substr($diagNorm, 0, 100, 'UTF-8');
        $notes[] = "diagnostico:>100 -> trunc";
    }

    fputcsv($ok, [$id, $fechaIso, $runPaciente, $runMedico, $diagNorm, $efect], $sep, $enc, $esc);
    if ($notes) {
        $log($lg, "L$line ".implode(' | ', $notes));
    }
}

fclose($in); fclose($ok); fclose($er); fclose($lg);
