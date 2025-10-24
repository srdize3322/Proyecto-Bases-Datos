#!/usr/bin/env php
<?php
// E3/main.php — ejecuta en secuencia todos los filtros de RequestPHP/
// Uso: php main.php

mb_internal_encoding('UTF-8');

$BASE = realpath(__DIR__);
$SCRIPTS = [
    'filtroInstituciones.php',
    'filtroPersona.php',
    'filtroFarmacia.php',
    'filtroArancel_DCColita.php',
    'filtroArancelFonasa.php',
    'filtroAtencion.php',
    'filtroMedicamento.php',
    'filtroOrden.php',
    'filtroPlanes.php',
];

$phpBinary = PHP_BINARY ?: 'php';

foreach ($SCRIPTS as $script) {
    $path = $BASE . '/RequestPHP/' . $script;
    if (!is_file($path)) {
        fwrite(STDERR, "[WARN] No se encontró $path, se omite.\n");
        continue;
    }
    fwrite(STDOUT, "[INFO] Ejecutando $script...\n");
    $cmd = escapeshellcmd($phpBinary) . ' ' . escapeshellarg($path);
    $descriptor = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($cmd, $descriptor, $pipes, $BASE);
    if (!is_resource($process)) {
        fwrite(STDERR, "[ERROR] No se pudo lanzar $script\n");
        continue;
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $status = proc_close($process);
    if ($stdout) fwrite(STDOUT, trim($stdout) . "\n");
    if ($stderr) fwrite(STDERR, trim($stderr) . "\n");
    if ($status !== 0) {
        fwrite(STDERR, "[ERROR] $script terminó con código $status\n");
        break;
    }
}

fwrite(STDOUT, "[INFO] Proceso completado.\n");
