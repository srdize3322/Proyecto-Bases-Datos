#!/usr/bin/env php
<?php
// consolida los planes depurados en un maestro único

mb_internal_encoding('UTF-8');
// fijo utf para mantener consistencia con los csv anteriores

$BASE    = realpath(__DIR__.'/..');
$SRC_DIR = "$BASE/Depurado/planes";
$DST_DIR = "$BASE/Depurado";
$ERR_DIR = "$BASE/Eliminado";
$LOG_DIR = "$BASE/Logs";
$MASTER  = "$DST_DIR/MaestroPlanes_OK.csv";
$ERR_FILE = "$ERR_DIR/MaestroPlanes_ERR.csv";
$LOG     = "$LOG_DIR/MaestroPlanes_LOG.txt";
// reutilizo los mismos directorios de depurado

@mkdir($DST_DIR, 0777, true);
@mkdir($ERR_DIR, 0777, true);
@mkdir($LOG_DIR, 0777, true);

$log = fopen($LOG, 'w');
if (!$log) {
    fwrite(STDERR, "No puedo escribir $LOG\n");
    exit(1);
}

$logLine = function($msg) use ($log) {
    fwrite($log, '['.date('Y-m-d H:i:s')."] $msg\n");
};

if (!is_dir($SRC_DIR)) {
    // si no hay planes individuales cierro sin error
    $logLine("No existe directorio $SRC_DIR -> nada que hacer");
    fclose($log);
    exit(0);
}

$files = array_filter(glob($SRC_DIR.'/*_OK.csv'), 'is_file');
sort($files, SORT_STRING);

$out = fopen($MASTER, 'w');
if (!$out) {
    $logLine("ERROR: no puedo escribir $MASTER");
    fclose($log);
    exit(1);
}
$err = fopen($ERR_FILE, 'w');
if ($err) {
    $sep=';'; $enc='"'; $esc='\\';
    fputcsv($err, ['archivo','motivo'], $sep, $enc, $esc);
    // este csv resume incidencias de cada archivo individual
}

$sep=';'; $enc='"'; $esc='\\';
fputcsv($out, ['institucion','grupo','bonificacion'], $sep, $enc, $esc);
// encabezado del maestro queda homogeneo para todas las isapres

$totalFiles = 0;
$totalRows  = 0;

foreach ($files as $path) {
    $totalFiles++;
    $name = basename($path);
    $inst = preg_replace('/_OK\.csv$/', '', $name);

    $logLine("Procesando $name");
    $fh = fopen($path, 'r');
    if (!$fh) {
        $logLine("  ERROR: no puedo abrir $name -> omitido");
        if ($err) fputcsv($err, [$name, 'no se pudo abrir'], $sep, $enc, $esc);
        continue;
    }

    $header = fgetcsv($fh, 0, $sep, $enc, $esc);
    if ($header === false) {
        $logLine("  WARNING: $name sin filas -> omitido");
        if ($err) fputcsv($err, [$name, 'sin filas'], $sep, $enc, $esc);
        fclose($fh);
        continue;
    }

    $norm = array_map(fn($h)=>strtolower(trim($h)), $header);
    $idxBon = array_search('bonificacion', $norm);
    $idxGrp = array_search('grupo', $norm);
    if ($idxBon === false || $idxGrp === false) {
        $logLine("  ERROR: columnas bonificacion/grupo no encontradas -> omitido");
        if ($err) fputcsv($err, [$name, 'faltan columnas bonificacion/grupo'], $sep, $enc, $esc);
        fclose($fh);
        continue;
    }

    $rowsFile = 0;
    while (($row = fgetcsv($fh, 0, $sep, $enc, $esc)) !== false) {
        if (!array_filter($row, fn($c)=>trim((string)$c)!=='')) {
            $logLine("  L".($rowsFile+2)." fila vacía -> omitida");
            continue;
        }
        $bon = $row[$idxBon] ?? '';
        $grp = $row[$idxGrp] ?? '';
        fputcsv($out, [$inst, $grp, $bon], $sep, $enc, $esc);
        // escribo instituto actual junto a cada grupo y bonificación depurada
        $rowsFile++;
    }
    fclose($fh);

    $totalRows += $rowsFile;
    $logLine("  filas copiadas: $rowsFile");
}

$logLine("TOTAL archivos procesados: $totalFiles");
$logLine("TOTAL filas maestro: $totalRows");

fclose($out);
if ($err) fclose($err);
fclose($log);
