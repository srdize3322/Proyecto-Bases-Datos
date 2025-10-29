#!/usr/bin/env php
<?php
// E3/RequestPHP/filtroArancelFonasa.php — depuración del catálogo Arancel Fonasa.
// Reglas de limpieza:
// - codF: entero obligatorio y único. Se eliminan puntos/espacios; si falta -> ERR.
// - codA: entero opcional. Se normaliza igual que codF; vacío si no existe.
// - atencion: texto obligatorio, se compacta espacios y se trunca a 100 caracteres.
// - valor: entero obligatorio (se remueven separadores de miles).
// - grupo / tipo: texto ≤30 caracteres, se compacta espacios y se trunca.
// Líneas irrecuperables van a *_ERR.csv con el motivo en *_LOG.txt.

mb_internal_encoding('UTF-8');

$BASE = realpath(__DIR__.'/..');
$IN   = "$BASE/Old/Arancel fonasa.csv";
$OK   = "$BASE/Depurado/Arancel_Fonasa_OK.csv";
$ERR  = "$BASE/Eliminado/Arancel_Fonasa_ERR.csv";
$LOG  = "$BASE/Logs/Arancel_Fonasa_LOG.txt";

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
    if ($s !== '' && $s[0] === "\xEF") {
        return preg_replace('/^\xEF\xBB\xBF/u', '', $s);
    }
    return $s;
};
$collapseSpaces = fn($s) => preg_replace('/\s+/u', ' ', trim((string)$s));
$onlyDigits     = fn($s) => preg_replace('/\D/', '', (string)$s);
$trimRow = function (&$row) {
    foreach ($row as &$col) {
        $col = trim((string)$col);
    }
};
$log = fn($h,$m)=>fwrite($h,'['.date('Y-m-d H:i:s')."] $m\n");

$in = fopen($IN, 'r');
$ok = fopen($OK, 'w');
$er = fopen($ERR, 'w');
$lg = fopen($LOG, 'w');

$sep = ';'; $enc='"'; $esc='\\';
$header = fgetcsv($in, 0, $sep, $enc, $esc);
if ($header === false) {
    fclose($in); fclose($ok); fclose($er); fclose($lg);
    exit(0);
}
foreach ($header as &$col) {
    $col = $stripBom($col);
    $col = preg_replace('/\s+/u',' ', $col);
    $col = trim($col);
}
unset($col);
fputcsv($ok, $header, $sep, $enc, $esc);
fputcsv($er, $header, $sep, $enc, $esc);

$IDX_COD   = 0;
$IDX_ADIC  = 1;
$IDX_ATEN  = 2;
$IDX_VAL   = 3;
$IDX_GRUPO = 4;
$IDX_TIPO  = 5;

$seenCod = [];
$line = 1;
while (($row = fgetcsv($in, 0, $sep, $enc, $esc)) !== false) {
    $line++;
    $raw = $row;
    $trimRow($row);
    if (isset($row[$IDX_COD])) {
        $row[$IDX_COD] = $stripBom($row[$IDX_COD]);
    }
    if (!array_filter($row, fn($c) => $c !== '')) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line fila vacía -> ERR");
        continue;
    }
    $notes = [];

    // codF
    $codRaw = $row[$IDX_COD] ?? '';
    $cod = ltrim($onlyDigits($codRaw), '0');
    if ($cod === '') {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line sin Código -> ERR");
        continue;
    }
    if ($cod !== $codRaw) {
        $notes[] = "codF:'$codRaw'->$cod";
    }
    if (isset($seenCod[$cod])) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line Código duplicado $cod -> ERR");
        continue;
    }
    $seenCod[$cod] = true;

    // codA
    $adicRaw = $row[$IDX_ADIC] ?? '';
    $adic = $onlyDigits($adicRaw);
    if ($adicRaw !== '' && $adic === '') {
        $notes[] = "codA:'$adicRaw'->''";
    }

    // atencion
    $atenRaw = $row[$IDX_ATEN] ?? '';
    $aten = $collapseSpaces($atenRaw);
    if ($aten === '') {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line atención vacía -> ERR");
        unset($seenCod[$cod]);
        continue;
    }
    if ($aten !== $atenRaw) {
        $notes[] = "atencion:Normalizada";
    }
    if (mb_strlen($aten, 'UTF-8') > 100) {
        $aten = mb_substr($aten, 0, 100, 'UTF-8');
        $notes[] = "atencion:>100 -> trunc";
    }

    // valor
    $valorRaw = $row[$IDX_VAL] ?? '';
    $valor = $onlyDigits($valorRaw);
    if ($valor === '') {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $log($lg, "L$line valor vacío -> ERR");
        unset($seenCod[$cod]);
        continue;
    }
    if ($valor !== $valorRaw) {
        $notes[] = "valor:'$valorRaw'->$valor";
    }
    $valor = (string)intval($valor, 10);

    // grupo
    $grupoRaw = $row[$IDX_GRUPO] ?? '';
    $grupo = $collapseSpaces($grupoRaw);
    if ($grupo !== $grupoRaw) {
        $notes[] = "grupo:Normalizado";
    }
    if (mb_strlen($grupo, 'UTF-8') > 30) {
        $grupo = mb_substr($grupo, 0, 30, 'UTF-8');
        $notes[] = "grupo:>30 -> trunc";
    }

    // tipo
    $tipoRaw = $row[$IDX_TIPO] ?? '';
    $tipo = $collapseSpaces($tipoRaw);
    if ($tipo !== $tipoRaw) {
        $notes[] = "tipo:Normalizado";
    }
    if (mb_strlen($tipo, 'UTF-8') > 30) {
        $tipo = mb_substr($tipo, 0, 30, 'UTF-8');
        $notes[] = "tipo:>30 -> trunc";
    }

    fputcsv($ok, [$cod, $adic, $aten, $valor, $grupo, $tipo], $sep, $enc, $esc);
    if ($notes) {
        $log($lg, "L$line ".implode(' | ', $notes));
    }
}

fclose($in); fclose($ok); fclose($er); fclose($lg);
