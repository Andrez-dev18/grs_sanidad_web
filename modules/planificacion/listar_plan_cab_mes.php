<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
    exit();
}
mysqli_set_charset($conn, 'utf8');

$draw = (int)($_POST['draw'] ?? 1);
$start = (int)($_POST['start'] ?? 0);
$length = (int)($_POST['length'] ?? 25);
$length = max(1, min(100, $length));
$search = trim($_POST['search']['value'] ?? '');
$anio = trim($_POST['anio'] ?? '');
$mes = trim($_POST['mes'] ?? '');

$where = [];
$params = [];
$types = '';

if ($anio !== '') { $where[] = "c.anio = ?"; $params[] = $anio; $types .= 'i'; }
if ($mes !== '') { $where[] = "c.mes = ?"; $params[] = $mes; $types .= 'i'; }
if ($search !== '') {
    $like = "%$search%";
    $where[] = "(c.anio LIKE ? OR c.mes LIKE ? OR c.usuarioRegistrador LIKE ?)";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}

$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$totalRes = $conn->query("SELECT COUNT(*) AS total FROM san_plan_cab c");
$recordsTotal = (int)($totalRes ? ($totalRes->fetch_assoc()['total'] ?? 0) : 0);

$recordsFiltered = $recordsTotal;
if ($whereClause) {
    $stmtF = $conn->prepare("SELECT COUNT(*) AS total FROM san_plan_cab c $whereClause");
    if ($types) $stmtF->bind_param($types, ...$params);
    $stmtF->execute();
    $recordsFiltered = (int)($stmtF->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtF->close();
}

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

$sql = "SELECT c.id AS cabId, c.anio, c.mes, c.fecProgramacion, c.usuarioRegistrador, c.fechaHoraRegistro
        FROM san_plan_cab c
        $whereClause
        ORDER BY c.anio DESC, c.mes DESC
        LIMIT ? OFFSET ?";
$params2 = $whereClause ? array_merge($params, [$length, $start]) : [$length, $start];
$types2 = $types . 'ii';
$stmt = $conn->prepare($sql);
if ($types2) $stmt->bind_param($types2, ...$params2);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $row['mesNombre'] = $meses[(int)$row['mes']] ?? '';
    $row['periodo'] = $row['mesNombre'] . ' ' . $row['anio'];
    $data[] = $row;
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
], JSON_UNESCAPED_UNICODE);
