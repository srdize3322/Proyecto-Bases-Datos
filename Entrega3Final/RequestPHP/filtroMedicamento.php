#!/usr/bin/env php
<?php
// depuración de Medicamento.csv
// IDAtencion entero obligatorio, debe existir en Atencion_OK.csv
//  Medicamento (nombre) y Posologia se normalizan y se truncan a 100 caracteres
//  Posologia vacía se reemplaza por "Sin posologia".
// Psicotropico se normaliza a TRUE/FALSE (acepta variantes 1/0/si/no); valores fuera de dominio

mb_internal_encoding('UTF-8');
// mantengo utf8 para no perder acentos en nombres

$BASE = realpath(__DIR__.'/..');
$IN   = "$BASE/Old/Medicamento.csv";
$OK   = "$BASE/Depurado/Medicamento_OK.csv";
$ERR  = "$BASE/Eliminado/Medicamento_ERR.csv";
$LOG  = "$BASE/Logs/Medicamento_LOG.txt";
$ATENC = "$BASE/Depurado/Atencion_OK.csv";
// rutas alineadas con los otros filtros

@mkdir("$BASE/Depurado",0777,true);
@mkdir("$BASE/Eliminado",0777,true);
@mkdir("$BASE/Logs",0777,true);

if (!is_readable($IN)) {
    fwrite(STDERR, "No puedo leer $IN\n");
    exit(1);
}

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
$collapseSpaces = fn($s)=>preg_replace('/\s+/u', ' ', trim((string)$s));
$fixMojibake = function ($s) {
    $map = [
        '√©'=>'é','√á'=>'á','√í'=>'í','√ó'=>'ó','√ú'=>'ú','√±'=>'ñ','√¨'=>'ü',
        '√É'=>'É','√Á'=>'Á','√Í'=>'Í','√Ó'=>'Ó','√Ú'=>'Ú','√Ñ'=>'Ñ',
        '√¯'=>'í','√≤'=>'ó','√≥'=>'ó','√≠'=>'í','√∫'=>'ú','√°'=>'á','√ß'=>'ó','√¶'=>'ö',
        '√†'=>'†','√ª'=>'ª','√µ'=>'µ','√ì'=>'í','√π'=>'ó','√§'=>'§','√´'=>'ó'
    ];
    return strtr($s, $map);
};

// --- Cargar IDs válidos de Atencion_OK ---
// preparo lookup para validar cada id atencion
$atencionValid = [];
if (is_readable($ATENC)) {
    if (($fh = fopen($ATENC,'r')) !== false) {
        $hdr = fgetcsv($fh, 0, ';', '"', '\\');
        if ($hdr !== false) {
            $hdr[0] = $stripBom($hdr[0]);
            $idx = array_search('ID', $hdr);
            if ($idx !== false) {
                while (($row = fgetcsv($fh, 0, ';', '"', '\\')) !== false) {
                    if (isset($row[$idx])) {
                        $atencionValid[(int)$row[$idx]] = true;
                    }
                }
            }
        }
        fclose($fh);
    }
}

// --- Preparar IO ---
$in  = fopen($IN, 'r');
$ok  = fopen($OK, 'w');
$er  = fopen($ERR, 'w');
$lg  = fopen($LOG, 'w');

$sep=';'; $enc='"'; $esc='\\';
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
// encabezados limpios mantienen el mismo layout en ambos csv

$IDX_ID   = 0;
$IDX_NOM  = 1;
$IDX_POS  = 2;
$IDX_PSIC = 3;

$line = 1;
// recorro cada medicamento aplicando validaciones por campo
while (($row = fgetcsv($in, 0, $sep, $enc, $esc)) !== false) {
    $line++;
    $raw = $row;
    $trimRow($row);
    if (isset($row[$IDX_ID])) {
        $row[$IDX_ID] = $stripBom($row[$IDX_ID]);
    }
    if (!array_filter($row, fn($c)=>$c!=='')) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line fila vacía -> ERR");
        continue;
    }

    $notes = [];

    // ID Atencion
    // controla referencia numerica contra maestro de atenciones
    $idRaw = $row[$IDX_ID] ?? '';
    $idDigits = $onlyDigits($idRaw);
    if ($idDigits === '') {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line sin IDAtencion -> ERR");
        continue;
    }
    $id = (int)$idDigits;
    if ((string)$id !== $idRaw) {
        $notes[] = "IDAtencion:'$idRaw'->$id";
    }
    if (!isset($atencionValid[$id])) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line IDAtencion $id sin referencia -> ERR");
        continue;
    }

    // Nombre medicamento
    // normalizo nombre y garantizo longitudes acotadas
    $nomRaw = $row[$IDX_NOM] ?? '';
    $nom = $collapseSpaces($fixMojibake($nomRaw));
    if ($nom === '') {
        $nom = 'SIN NOMBRE';
        $notes[] = "medicamento vacío -> 'SIN NOMBRE'";
    }
    if (mb_strlen($nom, 'UTF-8') > 100) {
        $nom = mb_substr($nom, 0, 100, 'UTF-8');
        $notes[] = "medicamento:>100 -> trunc";
    }
    if ($nom !== $nomRaw) {
        $notes[] = "medicamento normalizado";
    }

    // Posología
    // posologia siempre queda informada y corta
    $posRaw = $row[$IDX_POS] ?? '';
    $pos = $collapseSpaces($fixMojibake($posRaw));
    if ($pos === '') {
        $pos = 'Sin posologia';
        $notes[] = "posologia vacía -> 'Sin posologia'";
    }
    if (mb_strlen($pos, 'UTF-8') > 100) {
        $pos = mb_substr($pos, 0, 100, 'UTF-8');
        $notes[] = "posologia:>100 -> trunc";
    }
    if ($pos !== $posRaw && strpos($posRaw, 'Sin posologia') === false) {
        $notes[] = "posologia normalizada";
    }

    // Psicotropico
    // ajusto a dominio booleano esperado por reportes
    $psiRaw = strtoupper($row[$IDX_PSIC] ?? '');
    $psiNorm = null;
    if (in_array($psiRaw, ['TRUE','FALSE'], true)) {
        $psiNorm = $psiRaw;
    } elseif ($psiRaw === '1' || $psiRaw === 'SI' || $psiRaw === 'SÍ') {
        $psiNorm = 'TRUE';
    } elseif ($psiRaw === '0' || $psiRaw === 'NO') {
        $psiNorm = 'FALSE';
    }
    if ($psiNorm === null) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line psicotropico inválido '{$row[$IDX_PSIC]}' -> ERR");
        continue;
    }
    if ($psiNorm !== ($row[$IDX_PSIC] ?? '')) {
        $notes[] = "psicotropico:'{$row[$IDX_PSIC]}'->$psiNorm";
    }

    fputcsv($ok, [$id, $nom, $pos, $psiNorm], $sep, $enc, $esc);
    if ($notes) {
        $log($lg, "L$line ".implode(' | ', $notes));
    }
}

// cierro los manejadores para liberar los csv
fclose($in); fclose($ok); fclose($er); fclose($lg);
