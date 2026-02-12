<?php
/**
 * Descarga un archivo subido (resultados) por ruta relativa al proyecto.
 * Uso: descargar_archivo.php?ruta=uploads/resultados/cod_pos_nombre.pdf
 * Los nombres pueden tener puntos (p.ej. p.m..png), comas y espacios.
 */
session_start();
if (empty($_SESSION['active'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('No autorizado');
}

$ruta = isset($_GET['ruta']) ? trim($_GET['ruta']) : '';
if ($ruta === '') {
    header('HTTP/1.1 400 Bad Request');
    exit('Ruta no indicada');
}
// Decodificar por si viene codificado (espacios, comas, etc.)
$ruta = rawurldecode($ruta);
// Solo permitir rutas bajo uploads/ (sin permitir segmentos ".." que salgan del directorio)
if (preg_match('#^uploads[/\\\\]#', $ruta) !== 1) {
    header('HTTP/1.1 400 Bad Request');
    exit('Ruta no permitida');
}
// Rechazar path traversal: segmentos que sean exactamente ".."
$segmentos = preg_split('#[/\\\\]#', $ruta, -1, PREG_SPLIT_NO_EMPTY);
foreach ($segmentos as $seg) {
    if ($seg === '..') {
        header('HTTP/1.1 400 Bad Request');
        exit('Ruta no válida');
    }
}

$baseDir = dirname(__DIR__, 2);
$pathFisico = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta);

if (!is_file($pathFisico)) {
    header('HTTP/1.1 404 Not Found');
    exit('Archivo no encontrado');
}

// Asegurar que la ruta resuelta no salga de baseDir (por si el SO normaliza algo)
$realPath = realpath($pathFisico);
$realBase = realpath($baseDir);
if ($realPath === false || $realBase === false || strpos($realPath, $realBase) !== 0) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}
$pathFisico = $realPath;

$nombreArchivo = basename($pathFisico);
$mime = @mime_content_type($pathFisico);
if ($mime === false || $mime === '') {
    $mime = 'application/octet-stream';
}

// Filename con caracteres especiales: usar nombre seguro en header (solo ASCII en filename="")
$nombreSeguro = preg_replace('/[^\x20-\x7E]/', '_', $nombreArchivo);
if ($nombreSeguro === '') {
    $nombreSeguro = 'descarga';
}

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . str_replace('"', '\\"', $nombreSeguro) . '"');
header('Content-Length: ' . filesize($pathFisico));
header('Cache-Control: no-cache, must-revalidate');
readfile($pathFisico);
exit;
