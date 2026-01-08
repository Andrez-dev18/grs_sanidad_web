<?php
ob_start();

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

include_once '../../../conexion_grs_joya/conexion.php';
date_default_timezone_set('America/Lima');

// --- Conexión ---
$conexion = conectar_joya();

if (ob_get_level()) {
    ob_clean();
}
if (!$conexion) {
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error de conexión: ' . mysqli_connect_error()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Obtener parámetro tcencos ---
$tcencos = isset($_GET['tcencos']) ? trim($_GET['tcencos']) : '';

if (empty($tcencos)) {
    echo json_encode([
        'status' => 400,
        'success' => false,
        'message' => 'Parámetro tcencos es requerido',
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Consulta GALPONES ---
$query = "
        SELECT 
            tcencos,
            tcodint  
        FROM regcencosgalpones 
        WHERE tcencos = '$tcencos'
        ORDER BY tcodint ASC
";

$result = mysqli_query($conexion, $query);

function resultToArray($result) {
    $rows = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$galpones = resultToArray($result);

// --- Limpiar buffer y responder ---
if (ob_get_level()) {
    ob_clean();
}

echo json_encode([
    'status' => 200,
    'success' => true,
    'message' => 'Galpones cargados correctamente',
    'data' => [
        'galpones' => $galpones
    ]
], JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
exit;
?>