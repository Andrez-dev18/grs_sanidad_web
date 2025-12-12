<?php
// --- CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once '../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$codEnvio = $_GET['codEnvio'] ?? '';
$posSolicitud = $_GET['posSolicitud'] ?? '';

// Validación estricta
if (!is_string($codEnvio) || trim($codEnvio) === '') {
    echo json_encode([
        'success' => false,
        'message' => 'codEnvio es obligatorio y debe ser una cadena no vacía'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_numeric($posSolicitud) || (int)$posSolicitud <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'posSolicitud debe ser un número entero positivo'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$posSolicitud = (int)$posSolicitud;

// ✅ Consulta: todos los análisis de la solicitud, con resultado si existe
$sql = "
    SELECT 
        det.codAnalisis AS codigo,
        det.nomAnalisis AS nombre,
        COALESCE(res.resultado, 'No ingresado') AS resultado,
        COALESCE(res.obs, 'No ingresado') AS observaciones
    FROM san_fact_solicitud_det det
    LEFT JOIN san_fact_resultado_analisis res 
        ON det.codEnvio = res.codEnvio 
        AND det.posSolicitud = res.posSolicitud
        AND det.codAnalisis = res.analisis_codigo
    WHERE det.codEnvio = ? AND det.posSolicitud = ?
    ORDER BY det.nomAnalisis
";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar la consulta: ' . $conexion->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('si', $codEnvio, $posSolicitud);
$stmt->execute();
$result = $stmt->get_result();

$analisis = [];
while ($row = $result->fetch_assoc()) {
    $analisis[] = [
        'codigo' => (string) $row['codigo'],
        'nombre' => (string) $row['nombre'],
        'resultado' => (string) $row['resultado'],
        'observaciones' => (string) $row['observaciones'],
    ];
}

echo json_encode([
    'success' => true,
    'data' => $analisis
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conexion->close();