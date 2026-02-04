<?php
/**
 * Listado de registros de san_plan_cab para DataTables.
 * Filtros: fecha_desde, fecha_hasta, granja, campania, galpon, edad.
 */
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['draw' => (int)($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['draw' => (int)($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
    exit();
}
mysqli_set_charset($conn, 'utf8');

$draw = (int)($_GET['draw'] ?? 1);
$start = (int)($_GET['start'] ?? 0);
$length = (int)($_GET['length'] ?? 25);
$length = max(1, min(100, $length));

$fecha_desde = trim($_GET['fecha_desde'] ?? '');
$fecha_hasta = trim($_GET['fecha_hasta'] ?? '');
$granja = trim($_GET['granja'] ?? '');
$campania = trim($_GET['campania'] ?? '');
$galpon = trim($_GET['galpon'] ?? '');
$edad = trim($_GET['edad'] ?? '');

$where = [];
$params = [];
$types = '';

if ($fecha_desde !== '') {
    $where[] = "c.fecProgramacion >= ?";
    $params[] = $fecha_desde;
    $types .= 's';
}
if ($fecha_hasta !== '') {
    $where[] = "c.fecProgramacion <= ?";
    $params[] = $fecha_hasta;
    $types .= 's';
}
if ($granja !== '' && preg_match('/^\d{3}$/', $granja)) {
    $where[] = "EXISTS (SELECT 1 FROM san_plan_det d WHERE d.cabId = c.id AND LEFT(d.codRef, 3) = ?)";
    $params[] = $granja;
    $types .= 's';
}
if ($campania !== '' && preg_match('/^\d{3}$/', $campania)) {
    $where[] = "EXISTS (SELECT 1 FROM san_plan_det d WHERE d.cabId = c.id AND SUBSTRING(d.codRef, 4, 3) = ?)";
    $params[] = $campania;
    $types .= 's';
}
if ($galpon !== '' && preg_match('/^\d{2}$/', $galpon)) {
    $where[] = "EXISTS (SELECT 1 FROM san_plan_det d WHERE d.cabId = c.id AND SUBSTRING(d.codRef, 7, 2) = ?)";
    $params[] = $galpon;
    $types .= 's';
}
if ($edad !== '' && preg_match('/^\d{2}$/', $edad)) {
    $where[] = "EXISTS (SELECT 1 FROM san_plan_det d WHERE d.cabId = c.id AND SUBSTRING(d.codRef, 9, 2) = ?)";
    $params[] = $edad;
    $types .= 's';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sqlTotal = "SELECT COUNT(*) AS total FROM san_plan_cab c";
$recordsTotal = (int)($conn->query($sqlTotal)->fetch_assoc()['total'] ?? 0);

$sqlFiltered = "SELECT COUNT(DISTINCT c.id) AS total FROM san_plan_cab c $whereClause";
if ($params) {
    $stmtF = $conn->prepare($sqlFiltered);
    $stmtF->bind_param($types, ...$params);
    $stmtF->execute();
    $recordsFiltered = (int)($stmtF->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtF->close();
} else {
    $recordsFiltered = $recordsTotal;
}

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

$sql = "SELECT c.id AS cabId, c.anio, c.mes, c.fecProgramacion, c.usuarioRegistrador, c.fechaHoraRegistro
        FROM san_plan_cab c
        $whereClause
        ORDER BY c.fecProgramacion DESC, c.id DESC
        LIMIT ? OFFSET ?";
$params2 = array_merge($params, [$length, $start]);
$types2 = $types . 'ii';
$stmt = $conn->prepare($sql);
if ($types2) $stmt->bind_param($types2, ...$params2);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $row['mesNombre'] = $meses[(int)$row['mes']] ?? '';
    $row['periodo'] = trim(($row['mesNombre'] ?? '') . ' ' . ($row['anio'] ?? ''));
    $data[] = $row;
}
$stmt->close();

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
], JSON_UNESCAPED_UNICODE);
