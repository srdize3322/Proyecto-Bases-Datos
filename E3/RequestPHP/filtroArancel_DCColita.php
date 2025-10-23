#!/usr/bin/env php
<?php
// E3/RequestPHP/filtroArancel_DCColita.php — depura Arancel DCColita de rana
// Reglas principales:
// - codigo interno: dígitos únicamente, obligatorio y único.
// - codFonasa: dígitos con opcional sufijo "-dígitos".
// - atencion: texto obligatorio, se compactan espacios y se trunca a 100 chars.
// - valor: dígitos únicamente, obligatorio.
// Filas a las que no se les puede corregir el error se envían a _ERR y se deja traza en el log.

mb_internal_encoding('UTF-8');

$BASE = realpath(__DIR__.'/..');
$IN   = "$BASE/Old/Arancel DCColita de rana.csv";
$OK   = "$BASE/Depurado/Arancel_DCColita_OK.csv";
$ERR  = "$BASE/Eliminado/Arancel_DCColita_ERR.csv";
$LOG  = "$BASE/Logs/Arancel_DCColita_LOG.txt";

@mkdir("$BASE/Depurado",0777,true);
@mkdir("$BASE/Eliminado",0777,true);
@mkdir("$BASE/Logs",0777,true);

if (!is_readable($IN)) {
    fwrite(STDERR, "No puedo leer $IN\n");
    exit(1);
}

$logLine = fn($handle, $msg) => fwrite($handle, '['.date('Y-m-d H:i:s')."] $msg\n");
$trimRow = function (&$row) {
    foreach ($row as &$col) {
        $col = trim((string)$col);
    }
};
$collapseSpaces = fn($s) => preg_replace('/\s+/u', ' ', trim((string)$s));
$onlyDigits     = fn($s) => preg_replace('/\D/', '', (string)$s);

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
fputcsv($ok, $header, $sep, $enc, $esc);
fputcsv($er, $header, $sep, $enc, $esc);

$idxCodigo   = 0;
$idxFonasa   = 1;
$idxAtencion = 2;
$idxValor    = 3;

$seenCodigo = [];
$line = 1;

while (($row = fgetcsv($in, 0, $sep, $enc, $esc)) !== false) {
    $line++;
    $raw = $row;
    $trimRow($row);
    $notes = [];

    if (!array_filter($row, fn($c) => $c !== '')) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $logLine($lg, "L$line fila vacía -> ERR");
        continue;
    }

    // codigo interno
    $codigoRaw = $row[$idxCodigo] ?? '';
    $codigo = $onlyDigits($codigoRaw);
    if ($codigo === '') {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $logLine($lg, "L$line sin código interno -> ERR");
        continue;
    }
    $codigo = (string)intval($codigo, 10);
    if ($codigo !== $codigoRaw) {
        $notes[] = "codigo:'$codigoRaw'->$codigo";
    }
    if (isset($seenCodigo[$codigo])) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $logLine($lg, "L$line código duplicado $codigo -> ERR");
        continue;
    }
    $seenCodigo[$codigo] = true;

    // Código FONASA
    $fonasaRaw = $row[$idxFonasa] ?? '';
    $fonasa = str_replace([' ', '.'], '', $fonasaRaw);
    if ($fonasa === '') {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $logLine($lg, "L$line sin código FONASA -> ERR");
        unset($seenCodigo[$codigo]);
        continue;
    }
    if (!preg_match('/^\d+(?:-\d+)?$/', $fonasa)) {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $logLine($lg, "L$line Código FONASA inválido '$fonasaRaw' -> ERR");
        unset($seenCodigo[$codigo]);
        continue;
    }
    if ($fonasa !== $fonasaRaw) {
        $notes[] = "codFonasa:'$fonasaRaw'->$fonasa";
    }

    // Descripción de atención
    $atencionRaw = $row[$idxAtencion] ?? '';
    $atencion = $collapseSpaces($atencionRaw);
    if ($atencion === '') {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $logLine($lg, "L$line atención vacía -> ERR");
        unset($seenCodigo[$codigo]);
        continue;
    }
    if ($atencion !== $atencionRaw) {
        $notes[] = "atencion:Normalizada";
    }
    if (mb_strlen($atencion, 'UTF-8') > 100) {
        $cortada = mb_substr($atencion, 0, 100, 'UTF-8');
        $notes[] = "atencion:>100 -> trunc";
        $atencion = $cortada;
    }

    // Valor DCColita
    $valorRaw = $row[$idxValor] ?? '';
    $valor = $onlyDigits($valorRaw);
    if ($valor === '') {
        fputcsv($er, $raw, $sep, $enc, $esc);
        $logLine($lg, "L$line valor vacío -> ERR");
        unset($seenCodigo[$codigo]);
        continue;
    }
    if ($valor !== $valorRaw) {
        $notes[] = "valor:'$valorRaw'->$valor";
    }
    $valor = (string)intval($valor, 10);

    fputcsv($ok, [$codigo, $fonasa, $atencion, $valor], $sep, $enc, $esc);
    if ($notes) {
        $logLine($lg, "L$line ".implode(' | ', $notes));
    }
}

fclose($in); fclose($ok); fclose($er); fclose($lg);
