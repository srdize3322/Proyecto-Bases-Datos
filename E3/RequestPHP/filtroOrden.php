#!/usr/bin/env php
<?php
// E3/RequestPHP/filtroOrden.php — depuración de Orden.csv
// Reglas:
// - IDAtencion e IDArancel deben ser enteros (sin validar contra otros catálogos).
// - consulta (ConsAtMedica) normalizada (mojibake, espacios) y truncada a 100 caracteres.
// - Otras columnas del CSV original (vacías) se descartan.

mb_internal_encoding('UTF-8');

$BASE = realpath(__DIR__.'/..');
$IN   = "$BASE/Old/Orden.csv";
$OK   = "$BASE/Depurado/Orden_OK.csv";
$ERR  = "$BASE/Eliminado/Orden_ERR.csv";
$LOG  = "$BASE/Logs/Orden_LOG.txt";

@mkdir("$BASE/Depurado",0777,true);
@mkdir("$BASE/Eliminado",0777,true);
@mkdir("$BASE/Logs",0777,true);

if (!is_readable($IN)) {
    fwrite(STDERR, "No puedo leer $IN\n");
    exit(1);
}

// Utilidades
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
$collapseSpaces = fn($s)=>preg_replace('/\s+/u',' ', trim((string)$s));
$fixMojibake = function ($s) {
    $map = [
        '√©'=>'é','√á'=>'á','√í'=>'í','√ó'=>'ó','√ú'=>'ú','√±'=>'ñ','√¨'=>'ü',
        '√É'=>'É','√Á'=>'Á','√Í'=>'Í','√Ó'=>'Ó','√Ú'=>'Ú','√Ñ'=>'Ñ',
        '√¯'=>'í','√≤'=>'ó','√≥'=>'ó','√≠'=>'í','√∫'=>'ú','√°'=>'á','√ß'=>'ó','√¶'=>'ö',
        '√†'=>'†','√ª'=>'ª','√µ'=>'µ','√ì'=>'í','√π'=>'ó','√§'=>'§','√´'=>'ó'
    ];
    return strtr($s, $map);
};

// Preparar IO
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
// conservar sólo primeras 3 columnas
$header[0] = $stripBom($header[0]);
$header = array_slice($header, 0, 3);
fputcsv($ok, $header, $sep, $enc, $esc);
fputcsv($er, $header, $sep, $enc, $esc);

$IDX_ATEN = 0;
$IDX_ARAN = 1;
$IDX_CONS = 2;

$line = 1;
while (($row = fgetcsv($in, 0, $sep, $enc, $esc)) !== false) {
    $line++;
    $raw = $row;
    $trimRow($row);
    if (isset($row[$IDX_ATEN])) {
        $row[$IDX_ATEN] = $stripBom($row[$IDX_ATEN]);
    }
    if (!array_filter($row, fn($c)=>$c!=='')) {
        fputcsv($er, ['', '', ''], $sep, $enc, $esc);
        $log($lg, "L$line fila vacía -> ERR");
        continue;
    }

    $notes = [];

    // ID Atencion
    $idARaw = $row[$IDX_ATEN] ?? '';
    $idADigits = $onlyDigits($idARaw);
    if ($idADigits === '') {
        fputcsv($er, array_slice($raw,0,3), $sep, $enc, $esc);
        $log($lg, "L$line sin IDAtencion -> ERR");
        continue;
    }
    $idA = (int)$idADigits;
    if ((string)$idA !== $idARaw) {
        $notes[] = "IDAtencion:'$idARaw'->$idA";
    }

    // ID Arancel
    $idBRaw = $row[$IDX_ARAN] ?? '';
    $idBDigits = $onlyDigits($idBRaw);
    if ($idBDigits === '') {
        fputcsv($er, array_slice($raw,0,3), $sep, $enc, $esc);
        $log($lg, "L$line sin IDArancel -> ERR");
        continue;
    }
    $idB = (int)$idBDigits;
    if ((string)$idB !== $idBRaw) {
        $notes[] = "IDArancel:'$idBRaw'->$idB";
    }

    // Consulta
    $consRaw = $row[$IDX_CONS] ?? '';
    $cons = $collapseSpaces($fixMojibake($consRaw));
    if ($cons === '' || strtoupper($cons) === 'NULL') {
        $cons = 'Sin descripción';
        $notes[] = "consulta vacía -> 'Sin descripción'";
    }
    if (mb_strlen($cons, 'UTF-8') > 100) {
        $cons = mb_substr($cons, 0, 100, 'UTF-8');
        $notes[] = "consulta:>100 -> trunc";
    }
    if ($cons !== $consRaw && stripos($consRaw, 'Sin descripción') === false && stripos($consRaw, 'Sin descripcion') === false) {
        $notes[] = "consulta normalizada";
    }

    fputcsv($ok, [$idA, $idB, $cons], $sep, $enc, $esc);
    if ($notes) {
        $log($lg, "L$line ".implode(' | ', $notes));
    }
}

fclose($in); fclose($ok); fclose($er); fclose($lg);
