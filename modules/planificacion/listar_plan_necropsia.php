<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode([
        'draw' => (int)($_POST['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode([
        'draw' => (int)($_POST['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit();
}

$draw = (int)($_POST['draw'] ?? 1);
$start = (int)($_POST['start'] ?? 0);
$length = (int)($_POST['length'] ?? 25);
$length = max(1, min(100, $length));

$search = trim($_POST['search']['value'] ?? '');
$fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
$fecha_fin = trim($_POST['fecha_fin'] ?? '');
$granja = trim($_POST['tgranja'] ?? '');
$estado = trim($_POST['estado'] ?? '');

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $like = "%$search%";
    $where[] = "(tgranja LIKE ? OR tgalpon LIKE ? OR tedad LIKE ? OR responsable LIKE ? OR observacion LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
    $types .= 'sssss';
}

if ($fecha_inicio !== '') {
    $where[] = "tfectra >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
}

if ($fecha_fin !== '') {
    $where[] = "tfectra <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}

if ($granja !== '') {
    $where[] = "tgranja = ?";
    $params[] = $granja;
    $types .= 's';
}

if ($estado !== '') {
    $where[] = "estado = ?";
    $params[] = $estado;
    $types .= 's';
}

$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Total
$totalSql = "SELECT COUNT(*) AS total FROM san_plan_necropsia";
$totalRes = $conn->query($totalSql);
$recordsTotal = (int)($totalRes ? ($totalRes->fetch_assoc()['total'] ?? 0) : 0);

// Filtrado
$recordsFiltered = $recordsTotal;
if ($whereClause) {
    $sqlFiltered = "SELECT COUNT(*) AS total FROM san_plan_necropsia $whereClause";
    $stmtF = $conn->prepare($sqlFiltered);
    if ($types) $stmtF->bind_param($types, ...$params);
    $stmtF->execute();
    $recordsFiltered = (int)($stmtF->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtF->close();
}

// Data
$sql = "SELECT 
            p.id,
            p.fecha_programacion,
            p.tgranja,
            LEFT(p.tgranja, 3) AS granja,
            c.nombre AS nombreGranja,
            p.tcampania,
            p.tgalpon,
            p.tedad,
            p.tfectra,
            p.responsable,
            p.estado,
            p.usuario_registra,
            p.observacion
        FROM san_plan_necropsia p
        LEFT JOIN ccos c ON LEFT(p.tgranja, 3) = c.codigo
        $whereClause
        ORDER BY p.tfectra DESC, p.fecha_programacion DESC
        LIMIT ? OFFSET ?";
$params2 = array_merge($params, [$length, $start]);
$types2 = $types . 'ii';

$stmt = $conn->prepare($sql);
if ($types2) $stmt->bind_param($types2, ...$params2);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $row['nombreGranja'] = $row['nombreGranja'] ?: '–';
    $data[] = $row;
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
], JSON_UNESCAPED_UNICODE);

