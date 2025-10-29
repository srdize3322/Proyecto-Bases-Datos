#!/usr/bin/env php
<?php
// E3/RequestPHP/filtroOrden.php — depura Orden.csv con FK válidas hacia Atencion y Arancel DCColita.

mb_internal_encoding('UTF-8');

$BASE = realpath(__DIR__.'/..');
$IN  = "$BASE/Old/Orden.csv";
$OK  = "$BASE/Depurado/Orden_OK.csv";
$ERR = "$BASE/Eliminado/Orden_ERR.csv";
$LOG = "$BASE/Logs/Orden_LOG.txt";
$ATENC = "$BASE/Depurado/Atencion_OK.csv";
$ARANCEL = "$BASE/Depurado/Arancel_DCColita_OK.csv";

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
$collapseSpaces = fn($s) => preg_replace('/\s+/u', ' ', trim((string)$s));
$onlyDigits = fn($s) => preg_replace('/\D/', '', (string)$s);
$fixMojibake = function ($s) {
    $map = [
        '√©'=>'é','√á'=>'á','√í'=>'í','√ó'=>'ó','√ú'=>'ú','√±'=>'ñ','√¨'=>'ü',
        '√É'=>'É','√Á'=>'Á','√Í'=>'Í','√Ó'=>'Ó','√Ú'=>'Ú','√Ñ'=>'Ñ',
        '√¯'=>'í','√≤'=>'ó','√≥'=>'ó','√≠'=>'í','√∫'=>'ú','√°'=>'á','√ß'=>'ó','√¶'=>'ö',
        '√†'=>'†','√ª'=>'ª','√µ'=>'µ','√ì'=>'í','√π'=>'ó','√§'=>'§','√´'=>'ó'
    ];
    return strtr($s, $map);
};
$normalizeKey = function ($s) use ($collapseSpaces, $fixMojibake) {
    $s = mb_strtolower($collapseSpaces($fixMojibake($s)), 'UTF-8');
    $s = preg_replace('/[^a-z0-9áéíóúñü ]/u', '', $s);
    return preg_replace('/\s+/u', ' ', trim($s));
};

// --- Precarga catálogos ---
$atencionValid = [];
if (($fh = fopen($ATENC, 'r')) !== false) {
    $hdr = fgetcsv($fh, 0, ';', '"', '\\');
    if ($hdr !== false) {
        $hdr[0] = $stripBom($hdr[0]);
        $idx = array_search('ID', $hdr);
        if ($idx !== false) {
            while (($row = fgetcsv($fh, 0, ';', '"', '\\')) !== false) {
                if (isset($row[$idx]) && $row[$idx] !== '') {
                    $atencionValid[(int)$row[$idx]] = true;
                }
            }
        }
    }
    fclose($fh);
}

$arancelValid = [];
$mapDesc = [];
$ambiguous = [];
if (($fh = fopen($ARANCEL, 'r')) !== false) {
    $hdr = fgetcsv($fh, 0, ';', '"', '\\');
    if ($hdr !== false) {
        $hdr[0] = $stripBom($hdr[0]);
        $idxCod = array_search('Codigo interno', $hdr);
        $idxDesc = array_search('CONSULTAS Y ATENCION MEDICA', $hdr);
        if ($idxCod !== false && $idxDesc !== false) {
            while (($row = fgetcsv($fh, 0, ';', '"', '\\')) !== false) {
                if (!isset($row[$idxCod]) || $row[$idxCod] === '') continue;
                $codigo = (int)$row[$idxCod];
                $arancelValid[$codigo] = true;
                $key = $normalizeKey($row[$idxDesc]);
                if ($key === '') continue;
                if (isset($ambiguous[$key])) continue;
                if (!isset($mapDesc[$key])) {
                    $mapDesc[$key] = $codigo;
                } elseif ($mapDesc[$key] !== $codigo) {
                    unset($mapDesc[$key]);
                    $ambiguous[$key] = true;
                }
            }
        }
    }
    fclose($fh);
}

$in  = fopen($IN, 'r');
$ok  = fopen($OK, 'w');
$err = fopen($ERR, 'w');
$log = fopen($LOG, 'w');

$header = ['IDAtencion','IDArancel','ConsAtMedica'];
fputcsv($ok, $header, ';', '"', '\\');
fputcsv($err, $header, ';', '"', '\\');

$line = 1;
$okCount = 0;
$errCount = 0;
$reasonCount = [];

while (($row = fgetcsv($in, 0, ';', '"', '\\')) !== false) {
    $line++;
    $rawIdA = $stripBom($row[0] ?? '');
    $rawIdB = $row[1] ?? '';
    $rawDesc = $row[2] ?? '';

    $notes = [];

    $digitsA = $onlyDigits($rawIdA);
    $idA = $digitsA === '' ? null : (int)$digitsA;
    if ($digitsA !== '' && (string)$idA !== $rawIdA) {
        $notes[] = "IDAtencion:'$rawIdA'->$idA";
    }

    $digitsB = $onlyDigits($rawIdB);
    $idB = $digitsB === '' ? null : (int)$digitsB;
    if ($digitsB !== '' && (string)$idB !== $rawIdB) {
        $notes[] = "IDArancel:'$rawIdB'->$idB";
    }

    $desc = $collapseSpaces($fixMojibake($rawDesc));
    if ($desc === '' || strcasecmp($desc, 'NULL') === 0) {
        $notes[] = "consulta vacía -> 'Sin descripción'";
        $desc = 'Sin descripción';
    }
    if (mb_strlen($desc, 'UTF-8') > 100) {
        $desc = mb_substr($desc, 0, 100, 'UTF-8');
        $notes[] = "consulta:>100 -> trunc";
    }

    $out = [
        $idA !== null ? (string)$idA : '',
        $idB !== null ? (string)$idB : '',
        $desc
    ];

    $reason = null;
    if ($idA === null) {
        $reason = 'IDAtencion vacío';
    } elseif (!isset($atencionValid[$idA])) {
        $reason = "IDAtencion $idA sin referencia";
    } elseif ($idB === null) {
        $reason = 'IDArancel vacío';
    } elseif (isset($arancelValid[$idB])) {
        // válido sin cambios
    } else {
        $key = $normalizeKey($desc);
        if ($key !== '' && isset($mapDesc[$key])) {
            $mapped = $mapDesc[$key];
            $notes[] = "IDArancel '$idB' -> $mapped por descripción";
            $idB = $mapped;
            $out[1] = (string)$mapped;
        } else {
            $reason = "IDArancel $idB sin referencia";
        }
    }

    if ($reason === null) {
        fputcsv($ok, $out, ';', '"', '\\');
        $okCount++;
    } else {
        fputcsv($err, $out, ';', '"', '\\');
        $errCount++;
        $reasonCount[$reason] = ($reasonCount[$reason] ?? 0) + 1;
        $notes[] = "ERR: $reason";
    }

    if ($notes) {
        fwrite($log, '['.date('Y-m-d H:i:s')."] L$line ".implode(' | ', $notes).PHP_EOL);
    }
}

fwrite($log, "TOTAL OK: $okCount".PHP_EOL);
fwrite($log, "TOTAL ERR: $errCount".PHP_EOL);
if ($reasonCount) {
    arsort($reasonCount);
    fwrite($log, "Top 5 motivos ERR:".PHP_EOL);
    $i = 0;
    foreach ($reasonCount as $reason => $count) {
        fwrite($log, " - $reason: $count".PHP_EOL);
        if (++$i === 5) break;
    }
}

fclose($in); fclose($ok); fclose($err); fclose($log);
