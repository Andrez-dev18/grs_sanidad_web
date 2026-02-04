<?php
// --- Encabezados CORS (copiados de tu archivo funcional) ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Conexión ---
include_once '../../conexion_grs_joya/conexion.php';
date_default_timezone_set('America/Lima');

$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error de conexión: ' . mysqli_connect_error()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Obtener parámetro 'tipo_muestra_codigo' ---
$tipoCodigo = $_GET['tipo_muestra_codigo'] ?? null;

if (!$tipoCodigo || !is_string($tipoCodigo) || trim($tipoCodigo) === '') {
    echo json_encode([
        'status' => 400,
        'success' => false,
        'message' => 'Parámetro "tipo_muestra_codigo" es obligatorio y debe ser una cadena.'
    ], JSON_UNESCAPED_UNICODE);
    mysqli_close($conexion);
    exit;
}

$tipoCodigo = mysqli_real_escape_string($conexion, trim($tipoCodigo));

// --- 1. Consultar tipo de muestra ---
$tmQuery = mysqli_query($conexion, "
    SELECT codigo, nombre, lonCod 
        FROM san_dim_tipo_muestra 
        WHERE codigo = " . (int) $tipoCodigo . "
    LIMIT 1
");
$tipoMuestra = mysqli_fetch_assoc($tmQuery);

if (!$tipoMuestra) {
    echo json_encode([
        'status' => 404,
        'success' => false,
        'message' => 'Tipo de muestra no encontrado.'
    ], JSON_UNESCAPED_UNICODE);
    mysqli_close($conexion);
    exit;
}

// --- 2. Consultar paquetes del tipo ---
$paquetes = [];
$paqQuery = mysqli_query($conexion, "
    SELECT codigo, nombre 
        FROM san_dim_paquete 
        WHERE tipoMuestra = " . (int) $tipoCodigo . " 
        ORDER BY nombre
");
while ($row = mysqli_fetch_assoc($paqQuery)) {
    $paquetes[] = $row;
}

// --- 3. Consultar análisis del tipo ---
$analisis = [];
$anaQuery = mysqli_query($conexion, "
    SELECT A.codigo, A.nombre, P.codigo AS paquete 
            FROM san_dim_analisis_paquete AP
            JOIN san_dim_paquete P ON AP.paquete = P.codigo
            JOIN san_dim_analisis A ON AP.analisis = A.codigo
            WHERE P.tipoMuestra = " . (int) $tipoCodigo . " 
            ORDER BY nombre
");
while ($row = mysqli_fetch_assoc($anaQuery)) {
    $analisis[] = [
        'codigo' => $row['codigo'],
        'nombre' => $row['nombre'],
        'paquete' => $row['paquete'] ?: null
    ];
}

// --- Respuesta final ---
echo json_encode([
    'status' => 200,
    'success' => true,
    'message' => 'Configuración cargada correctamente.',
    'data' => [
        'tipo_muestra' => $tipoMuestra,
        'paquetes' => $paquetes,
        'analisis' => $analisis
    ]
], JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>