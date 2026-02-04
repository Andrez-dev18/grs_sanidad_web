<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado', 'data' => []]);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión', 'data' => []]);
    exit();
}
mysqli_set_charset($conn, 'utf8');

$cabId = trim($_GET['cabId'] ?? $_GET['cab_id'] ?? '');
if ($cabId === '') {
    echo json_encode(['data' => []]);
    exit();
}

$stmt = $conn->prepare("
    SELECT d.id, d.cabId, d.codCronograma, d.nomCronograma, d.fecProgramacion, d.granja, d.nomGranja, d.campania, d.galpon, d.edad, d.codRef, d.lugarToma, d.fecToma, d.responsable, d.codDestino, d.nomDestino, d.codMuestra, d.nomMuestra, d.nMacho, d.nHembra, d.estado, d.observacion, d.usuarioRegistrador,
           (SELECT GROUP_CONCAT(CONCAT(lm.codEnvio, ' #', lm.posSolicitud) SEPARATOR ', ') FROM san_plan_link_muestra lm WHERE lm.detId = d.id) AS enlaces_muestra,
           (SELECT GROUP_CONCAT(CONCAT(ln.tgranja, '/', ln.tgalpon, ' ', ln.tfectra, ' #', ln.tnumreg) SEPARATOR ', ') FROM san_plan_link_necropsia ln WHERE ln.detId = d.id) AS enlaces_necropsia
    FROM san_plan_det d
    WHERE d.cabId = ?
    ORDER BY d.fecToma ASC, d.nomCronograma ASC
");
if (!$stmt) {
    echo json_encode(['error' => $conn->error, 'data' => []], JSON_UNESCAPED_UNICODE);
    exit();
}
$stmt->bind_param('s', $cabId);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;
$stmt->close();

echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
