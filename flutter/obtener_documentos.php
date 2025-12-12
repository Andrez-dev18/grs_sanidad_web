<?php
// --- CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
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
    echo json_encode(['success' => false, 'message' => 'Error de conexión'], JSON_UNESCAPED_UNICODE);
    exit;
}

$codEnvio = $_GET['codEnvio'] ?? '';
$posSolicitud = $_GET['posSolicitud'] ?? '';

if (!$codEnvio || !is_numeric($posSolicitud)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conexion->prepare("
    SELECT archRuta
    FROM san_fact_resultado_archivo
    WHERE codEnvio = ? AND posSolicitud = ?
    ORDER BY archRuta
");
$stmt->bind_param('si', $codEnvio, $posSolicitud);
$stmt->execute();
$result = $stmt->get_result();

$documentos = [];
while ($row = $result->fetch_assoc()) {
    $documentos[] = [
        'ruta' => $row['archRuta'],
        'nombre' => basename($row['archRuta']),
    ];
}

echo json_encode([
    'success' => true,
    'data' => $documentos
], JSON_UNESCAPED_UNICODE);
$stmt->close();
$conexion->close();