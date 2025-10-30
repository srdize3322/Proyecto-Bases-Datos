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
    'filtroMaestroPlanes.php',
];

$phpBinary = PHP_BINARY ?: 'php';
$BASE = rtrim($BASE, DIRECTORY_SEPARATOR);
$oldDir = $BASE . '/Old/firmas';
$destDir = $BASE . '/Depurado/firmas';

$status = 0;
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

if ($status === 0) {
    fwrite(STDOUT, "[INFO] Copiando carpeta de firmas...\n");
    copyDir($oldDir, $destDir);
}

fwrite(STDOUT, "[INFO] Proceso completado.\n");

function copyDir(string $src, string $dst): void
{
    if (!is_dir($src)) {
        fwrite(STDERR, "[WARN] No se encontró el directorio de firmas en $src; se omite la copia.\n");
        return;
    }
    if (!is_dir($dst) && !mkdir($dst, 0777, true) && !is_dir($dst)) {
        fwrite(STDERR, "[ERROR] No se pudo crear el directorio destino $dst.\n");
        return;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $targetPath = $dst . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($targetPath) && !mkdir($targetPath, 0777, true) && !is_dir($targetPath)) {
                fwrite(STDERR, "[ERROR] No se pudo crear subdirectorio $targetPath.\n");
                return;
            }
        } else {
            if (!copy($item->getPathname(), $targetPath)) {
                fwrite(STDERR, "[ERROR] No se pudo copiar {$item->getPathname()} a $targetPath.\n");
                return;
            }
        }
    }
    fwrite(STDOUT, "[INFO] Firmas copiadas a $dst\n");
}
