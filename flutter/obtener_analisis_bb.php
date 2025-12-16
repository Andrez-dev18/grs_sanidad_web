<?php
// obtener_analisis_bb.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión'], JSON_UNESCAPED_UNICODE);
    exit;
}

$codEnvio = $_GET['codEnvio'] ?? '';
$posSolicitud = $_GET['posSolicitud'] ?? '';

if (!is_string($codEnvio) || trim($codEnvio) === '' || !is_numeric($posSolicitud)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}
$posSolicitud = (int)$posSolicitud;

// Paso 1: Verificar que todos los análisis de esta solicitud estén "completado"
$sqlVerificar = "
    SELECT 
        codAnalisis,
        estado_cuali
    FROM san_fact_solicitud_det
    WHERE codEnvio = ? AND posSolicitud = ?
";
$stmt = $conexion->prepare($sqlVerificar);
$stmt->bind_param('si', $codEnvio, $posSolicitud);
$stmt->execute();
$result = $stmt->get_result();

$analisisList = [];
//$todoCompletado = true;
while ($row = $result->fetch_assoc()) {
    /*if ($row['estado_cuali'] !== 'completado') {
        $todoCompletado = false;
    }**/
    $analisisList[] = $row['codAnalisis'];
}
$stmt->close();
/*
if (!$todoCompletado) {
    echo json_encode([
        'success' => false,
        'message' => 'La solicitud no está completamente completada.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
*/
// Paso 2: Obtener los datos cuantitativos desde san_analisis_pollo_bb_adulto
$placeholders = str_repeat('?,', count($analisisList) - 1) . '?';
$sqlBB = "
    SELECT 
        a.codigo_envio,
        a.id_analisis,
        a.gmean,
        a.cv,
        a.desviacion_estandar,
        a.count_muestras,
        a.s01, a.s02, a.s03, a.s04, a.s05, a.s06
    FROM san_analisis_pollo_bb_adulto a
    WHERE a.codigo_envio = ? AND a.id_analisis IN ($placeholders)
    ORDER BY FIELD(a.id_analisis, " . implode(',', array_fill(0, count($analisisList), '?')) . ")
";

$params = array_merge([$codEnvio], $analisisList, $analisisList); // dos veces: 1 para IN, 1 para ORDER BY FIELD
$types = str_repeat('s', 1 + count($analisisList) + count($analisisList));

$stmt = $conexion->prepare($sqlBB);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$datos = [];
while ($row = $result->fetch_assoc()) {
    $datos[] = $row;
}
$stmt->close();
$conexion->close();

echo json_encode([
    'success' => true,
    'data' => $datos
], JSON_UNESCAPED_UNICODE);