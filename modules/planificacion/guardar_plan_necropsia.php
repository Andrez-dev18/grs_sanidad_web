<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Lima');

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
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'JSON inválido']);
    exit();
}

$tgranja = trim($input['tgranja'] ?? '');
$tcampania = trim($input['tcampania'] ?? '');
$tgalpon = trim($input['tgalpon'] ?? '');
$tedad = trim($input['tedad'] ?? '');
$tfectra = trim($input['tfectra'] ?? '');
$responsable = trim($input['responsable'] ?? '');
$observacion = trim($input['observacion'] ?? '');

if ($tgranja === '' || $tcampania === '' || $tgalpon === '' || $tedad === '' || $tfectra === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios'], JSON_UNESCAPED_UNICODE);
    exit();
}

$id = generar_uuid_v4();
$usuario = $_SESSION['usuario'] ?? 'SYSTEM';
$fecha_programacion = date('Y-m-d H:i:s');

$sql = "INSERT INTO san_plan_necropsia
        (id, fecha_programacion, tgranja, tcampania, tgalpon, tedad, tfectra, responsable, estado, usuario_registra, fechaHoraRegistro, observacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PLANIFICADO', ?, NOW(), ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparando consulta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit();
}
$stmt->bind_param('ssssssssss', $id, $fecha_programacion, $tgranja, $tcampania, $tgalpon, $tedad, $tfectra, $responsable, $usuario, $observacion);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Planificación registrada'], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $stmt->error], JSON_UNESCAPED_UNICODE);

