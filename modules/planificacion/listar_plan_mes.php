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
    echo json_encode(['error' => 'Error de conexión', 'data' => []]);
    exit();
}
mysqli_set_charset($conn, 'utf8');

$cabId = trim($_GET['cabId'] ?? $_GET['cab_id'] ?? '');
$anio = (int)($_GET['anio'] ?? date('Y'));
$mes = (int)($_GET['mes'] ?? date('n'));
$mes = max(1, min(12, $mes));
$codCronograma = trim($_GET['codCronograma'] ?? $_GET['cronograma'] ?? '');

$where = [];
$params = [];
$types = '';

if ($cabId !== '') {
    $where[] = "d.cabId = ?";
    $params[] = $cabId;
    $types .= 's';
} else {
    $fecha_desde = sprintf('%04d-%02d-01', $anio, $mes);
    $fecha_hasta = sprintf('%04d-%02d-%02d', $anio, $mes, date('t', strtotime($fecha_desde)));
    $where[] = "d.fecToma >= ?";
    $where[] = "d.fecToma <= ?";
    $params = [$fecha_desde, $fecha_hasta];
    $types = 'ss';
}

if ($codCronograma !== '') {
    $where[] = "d.codCronograma = ?";
    $params[] = $codCronograma;
    $types .= 's';
}

$where[] = "d.estado IN ('PLANIFICADO','EJECUTADO')";
$whereClause = implode(' AND ', $where);

$stmt = $conn->prepare("SELECT d.id AS detId, d.cabId, d.fecToma, d.codCronograma, d.nomCronograma, d.granja, d.nomGranja, d.campania, d.galpon, d.edad, d.codRef, d.lugarToma, d.responsable, d.codDestino, d.nomDestino, d.estado, d.codMuestra, d.nomMuestra, d.nMacho, d.nHembra,
    (SELECT GROUP_CONCAT(CONCAT(lm.codEnvio, ' #', lm.posSolicitud) SEPARATOR ', ') FROM san_plan_link_muestra lm WHERE lm.detId = d.id) AS muestraEnlazada
    FROM san_plan_det d
    WHERE $whereClause
    ORDER BY d.fecToma ASC, d.nomCronograma ASC, d.nomMuestra ASC");

if (!$stmt) {
    echo json_encode(['error' => $conn->error, 'data' => []], JSON_UNESCAPED_UNICODE);
    exit();
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;
$stmt->close();

echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
