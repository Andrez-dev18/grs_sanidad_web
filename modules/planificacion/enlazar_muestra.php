<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

function generar_uuid_v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'JSON inválido']);
    exit();
}

$codEnvio = trim($input['codEnvio'] ?? '');
$posSolicitud = (int)($input['posSolicitud'] ?? 0);
$codRef = trim($input['codRef'] ?? '');
$fecToma = trim($input['fecToma'] ?? '');
$codMuestra = (int)($input['codMuestra'] ?? 0);
$detIdManual = trim($input['detId'] ?? $input['det_id'] ?? $input['planId'] ?? $input['cabId'] ?? $input['cab_id'] ?? '');
$usuario = $_SESSION['usuario'] ?? 'SYSTEM';

if ($codEnvio === '' || $posSolicitud <= 0 || $codRef === '' || $fecToma === '' || $codMuestra <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos'], JSON_UNESCAPED_UNICODE);
    exit();
}

$detId = '';
if ($detIdManual !== '') {
    $detId = $detIdManual;
} else {
    $stmtPlan = $conn->prepare("SELECT id FROM san_plan_det WHERE codRef = ? AND fecToma = ? AND (codMuestra = ? OR codMuestra IS NULL) AND estado = 'PLANIFICADO' ORDER BY fecProgramacion DESC LIMIT 1");
    if ($stmtPlan) {
        $stmtPlan->bind_param('ssi', $codRef, $fecToma, $codMuestra);
        $stmtPlan->execute();
        $r = $stmtPlan->get_result();
        if ($row = $r->fetch_assoc()) $detId = $row['id'];
        $stmtPlan->close();
    }
}

if ($detId === '') {
    echo json_encode(['success' => false, 'message' => 'No se encontró planificación para enlazar'], JSON_UNESCAPED_UNICODE);
    exit();
}

$id = generar_uuid_v4();
$stmt = $conn->prepare("INSERT INTO san_plan_link_muestra (id, detId, codEnvio, posSolicitud, codRef, fecToma, codMuestra, usuarioRegistrador, usuarioTransferencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param('ssssisiss', $id, $detId, $codEnvio, $posSolicitud, $codRef, $fecToma, $codMuestra, $usuario, $usuario);
    if ($stmt->execute()) {
        $conn->query("UPDATE san_plan_det SET estado = 'EJECUTADO' WHERE id = '$detId' AND estado = 'PLANIFICADO'");
        echo json_encode(['success' => true, 'message' => 'Enlace guardado'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    if ((int)$stmt->errno === 1062) {
        $conn->query("UPDATE san_plan_det SET estado = 'EJECUTADO' WHERE id = '$detId' AND estado = 'PLANIFICADO'");
        echo json_encode(['success' => true, 'message' => 'Ya estaba enlazado'], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Error al enlazar'], JSON_UNESCAPED_UNICODE);
