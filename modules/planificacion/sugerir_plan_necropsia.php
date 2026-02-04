<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit();
}

$tgranja = trim($_GET['tgranja'] ?? '');
$tgalpon = trim($_GET['tgalpon'] ?? '');
$tedad = trim($_GET['tedad'] ?? '');
$tfectra = trim($_GET['tfectra'] ?? '');

if ($tgranja === '' || $tgalpon === '' || $tedad === '' || $tfectra === '') {
    echo json_encode(['matches' => []], JSON_UNESCAPED_UNICODE);
    exit();
}

$tgalpon = str_pad($tgalpon, 2, '0', STR_PAD_LEFT);
$tedad = str_pad($tedad, 2, '0', STR_PAD_LEFT);

$stmt = $conn->prepare("SELECT id, fecProgramacion, responsable, estado, observacion FROM san_plan_det WHERE CONCAT(granja, campania) = ? AND galpon = ? AND edad = ? AND fecToma = ? AND (codCronograma = 7 OR nomCronograma = 'Control: Necropsias') AND estado = 'PLANIFICADO' ORDER BY fecProgramacion DESC");
if (!$stmt) {
    echo json_encode(['matches' => [], 'error' => $conn->error], JSON_UNESCAPED_UNICODE);
    exit();
}
$stmt->bind_param('ssss', $tgranja, $tgalpon, $tedad, $tfectra);
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while ($row = $res->fetch_assoc()) $out[] = $row;
$stmt->close();

echo json_encode(['matches' => $out], JSON_UNESCAPED_UNICODE);
