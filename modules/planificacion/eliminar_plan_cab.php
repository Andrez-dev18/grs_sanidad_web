<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$rol = $_SESSION['rol'] ?? $_SESSION['tipo'] ?? $_SESSION['admin'] ?? '';
$esAdmin = !empty($_SESSION['admin']) || stripos((string)$rol, 'admin') !== false || $rol === '1' || $rol === 'ADMIN';

if (!$esAdmin) {
    echo json_encode(['success' => false, 'message' => 'Solo administradores pueden eliminar']);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$cabId = trim($input['cabId'] ?? $input['cab_id'] ?? '');

if ($cabId === '') {
    echo json_encode(['success' => false, 'message' => 'cabId requerido']);
    exit();
}

$r = $conn->query("SELECT id FROM san_plan_det WHERE cabId = '$cabId'");
$detIds = [];
while ($row = $r->fetch_assoc()) $detIds[] = "'" . $conn->real_escape_string($row['id']) . "'";
if (!empty($detIds)) {
    $ids = implode(',', $detIds);
    $conn->query("DELETE FROM san_plan_link_muestra WHERE detId IN ($ids)");
    $conn->query("DELETE FROM san_plan_link_necropsia WHERE detId IN ($ids)");
}
$conn->query("DELETE FROM san_plan_det WHERE cabId = '$cabId'");
$conn->query("DELETE FROM san_plan_cab WHERE id = '$cabId'");

echo json_encode(['success' => true, 'message' => 'Planificación eliminada'], JSON_UNESCAPED_UNICODE);
