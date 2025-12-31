<?php
// --- Encabezados ---
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

include_once '../../conexion_grs_joya/conexion.php';
date_default_timezone_set('America/Lima');

// --- Conexión ---
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error de conexión: ' . mysqli_connect_error()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Consultas ---
$laboratorios = mysqli_query($conexion, "SELECT codigo, nombre FROM san_dim_laboratorio ORDER BY nombre DESC");
$emp_trans = mysqli_query($conexion, "SELECT codigo, nombre FROM san_dim_emptrans ORDER BY nombre DESC");
$muestras = mysqli_query($conexion, "SELECT * FROM san_dim_tipo_muestra ORDER BY codigo ASC");
$paquetes = mysqli_query($conexion, "SELECT * FROM san_dim_paquete ORDER BY codigo DESC");
$analisis = mysqli_query($conexion, "SELECT * FROM san_dim_analisis ORDER BY codigo DESC");



// --- Convertir a arrays ---
function resultToArray($result)
{
    $rows = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$data = [
    'laboratorios' => resultToArray($laboratorios),
    'emp_trans' => resultToArray($emp_trans),
    'muestras' => resultToArray($muestras),
    'paquetes' => resultToArray($paquetes),
    'analisis' => resultToArray($analisis),
    
];

// --- Respuesta final ---
echo json_encode([
    'status' => 200,
    'success' => true,
    'message' => 'Datos descargados correctamente',
    'data' => $data
], JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>