#!/usr/bin/env php
<?php
//primera pasada para planes desde archivos individuales en Old/planes hacia Depurado/planes
// deja cada plan listo para el maestro central

mb_internal_encoding('UTF-8');
// mantenemos utf8 en la salida para combinar con el resto

$BASE    = realpath(__DIR__.'/..');
$SRC_DIR = "$BASE/Old/planes";
$DST_DIR = "$BASE/Depurado/planes";
$ERR_DIR = "$BASE/Eliminado/planes";
$LOG_DIR = "$BASE/Logs/planes";
// rutas de entrada y salida alineadas con la estructura general

@mkdir($DST_DIR, 0777, true);
@mkdir($ERR_DIR, 0777, true);
@mkdir($LOG_DIR, 0777, true);

if (!is_dir($SRC_DIR)) {
    fwrite(STDERR, "No existe el directorio $SRC_DIR\n");
    exit(1);
}

$files = array_values(array_filter(scandir($SRC_DIR), function($f) use ($SRC_DIR) {
    return $f !== '.' && $f !== '..' && is_file("$SRC_DIR/$f") && str_ends_with(strtolower($f), '.csv');
}));

if (!$files) {
    fwrite(STDERR, "No se encontraron archivos CSV en $SRC_DIR\n");
    exit(0);
}
// si no hay archivos, corto temprano para no generar logs vacios

$fixMojibake = function ($s) {
    $map = [
        '√©'=>'é','√á'=>'á','√í'=>'í','√ó'=>'ó','√ú'=>'ú','√±'=>'ñ','√¨'=>'ü',
        '√É'=>'É','√Á'=>'Á','√Í'=>'Í','√Ó'=>'Ó','√Ú'=>'Ú','√Ñ'=>'Ñ',
        '√¯'=>'í','√≤'=>'ó','√≥'=>'ó','√≠'=>'í','√∫'=>'ú','√°'=>'á','√ß'=>'ó','√¶'=>'ö',
        '√†'=>'†','√ª'=>'ª','√µ'=>'µ','√ì'=>'í','√π'=>'ó','√§'=>'§','√´'=>'ó',
        'Ã¡'=>'á','Ã©'=>'é','Ã­'=>'í','Ã³'=>'ó','Ãº'=>'ú','Ã±'=>'ñ','Ã¼'=>'ü',
        'Ã'=>'Á','Ã‰'=>'É','Ã'=>'Í','Ã“'=>'Ó','Ãš'=>'Ú','Ã‘'=>'Ñ'
    ];
    return strtr($s, $map);
};
$collapseSpaces = fn($s)=>preg_replace('/\s+/u',' ', trim((string)$s));
$logLine = fn($h,$m)=>fwrite($h,'['.date('Y-m-d H:i:s')."] $m\n");
// helpers para limpiar textos y registrar ajustes

foreach ($files as $name) {
    $srcPath = "$SRC_DIR/$name";
    $baseName = pathinfo($name, PATHINFO_FILENAME);
    $okPath  = sprintf("%s/%s_OK.csv", $DST_DIR, $baseName);
    $logPath = sprintf("%s/%s_LOG.txt", $LOG_DIR, $baseName);

    // Algunos archivos vienen en Latin-1 con BOM; usamos latin-1 y convertimos a UTF-8 manualmente.
    $in  = fopen($srcPath, 'r');
    if (!$in) {
        fwrite(STDERR, "No puedo leer $srcPath\n");
        continue;
    }
    $ok  = fopen($okPath, 'w');
    $err = fopen(sprintf("%s/%s_ERR.csv", $ERR_DIR, $baseName), 'w');
    $log = fopen($logPath, 'w');
    if (!$ok || !$err || !$log) {
        fwrite(STDERR, "No puedo escribir salidas para $name\n");
        fclose($in);
        if ($ok) fclose($ok);
        if ($err) fclose($err);
        if ($log) fclose($log);
        continue;
    }

    $sep=';'; $enc='"'; $esc='\\';
    // read header and convert encoding/trim BOM if present
    $rawHeader = fgetcsv($in, 0, $sep, $enc, $esc);
    if ($rawHeader === false) {
        fclose($in); fclose($ok); fclose($log);
        continue;
    }
    $header = array_map(function($col){
        $col = mb_convert_encoding($col, 'UTF-8', 'UTF-8, ISO-8859-1');
        $col = preg_replace('/^\xEF\xBB\xBF/u','',$col);
        return $col;
    }, $rawHeader);
    // normalizamos cabeceras para mantener consistencia
    $header = ['bonificacion','grupo'];
    fputcsv($ok, $header, $sep, $enc, $esc);
    fputcsv($err, $header, $sep, $enc, $esc);
    // todas las salidas usan misma estructura para unir luego

    $rowNum = 1;
    while (($row = fgetcsv($in, 0, $sep, $enc, $esc)) !== false) {
        $rowNum++;
        // cada fila se valida y se ajusta antes de pasar al depurado
        if (!array_filter($row, fn($c)=>trim((string)$c) !== '')) {
            fputcsv($err, ['', ''], $sep, $enc, $esc);
            $logLine($log, "L$rowNum fila vacía -> ERR");
            continue;
        }

        $bonRaw = isset($row[0]) ? mb_convert_encoding($row[0], 'UTF-8', 'UTF-8, ISO-8859-1') : '';
        $grpRaw = isset($row[1]) ? mb_convert_encoding($row[1], 'UTF-8', 'UTF-8, ISO-8859-1') : '';

        $notes = [];

        $bon = trim($bonRaw);
        if ($bon === '' || strcasecmp($bon, 'NULL') === 0) {
            $bon = '0';
            $notes[] = "bonificación vacía -> 0";
        }
        $bonNorm = preg_replace('/[^\d\.]/', '', $bon);
        if ($bonNorm === '') {
            $bonInt = 0;
            $notes[] = "bonificación inválida '$bonRaw' -> 0";
        } else {
            $bonFloat = (float)$bonNorm;
            if ($bonFloat < 0) {
                $notes[] = "bonificación<$0 -> 0";
                $bonFloat = 0;
            } elseif ($bonFloat > 100) {
                $notes[] = "bonificación>100 -> 100";
                $bonFloat = 100;
            }
            $bonInt = (int)round($bonFloat, 0, PHP_ROUND_HALF_UP);
        }

        $grp = $collapseSpaces($fixMojibake($grpRaw));
        if ($grp === '') {
            $grp = 'Sin grupo';
            $notes[] = "grupo vacío -> 'Sin grupo'";
        }
        if (mb_strlen($grp, 'UTF-8') > 100) {
            $grp = mb_substr($grp, 0, 100, 'UTF-8');
            $notes[] = "grupo:>100 -> trunc";
        }

        fputcsv($ok, [$bonInt, $grp], $sep, $enc, $esc);
        if ($notes) {
            $logLine($log, "L$rowNum ".implode(' | ', $notes));
        }
    }

    fclose($in); fclose($ok); fclose($err); fclose($log);
    // termino este archivo y sigo con el siguiente en la carpeta
}
