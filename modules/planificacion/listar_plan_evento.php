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
mysqli_set_charset($conn, 'utf8');

$draw = (int)($_POST['draw'] ?? 1);
$start = (int)($_POST['start'] ?? 0);
$length = (int)($_POST['length'] ?? 25);
$length = max(1, min(100, $length));
$search = trim($_POST['search']['value'] ?? '');

$fecha_desde = trim($_POST['fecha_desde'] ?? '');
$fecha_hasta = trim($_POST['fecha_hasta'] ?? '');
$granja = trim($_POST['granja'] ?? '');
$cronograma = trim($_POST['cronograma'] ?? '');
$estado = trim($_POST['estado'] ?? '');

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $like = "%$search%";
    $where[] = "(cronograma LIKE ? OR codRef LIKE ? OR nombreGranja LIKE ? OR responsable LIKE ? OR destino LIKE ? OR lugar_toma LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
    $types .= 'ssssss';
}

if ($fecha_desde !== '') {
    $where[] = "fecToma >= ?";
    $params[] = $fecha_desde;
    $types .= 's';
}
if ($fecha_hasta !== '') {
    $where[] = "fecToma <= ?";
    $params[] = $fecha_hasta;
    $types .= 's';
}
if ($granja !== '') {
    $where[] = "granja = ?";
    $params[] = $granja;
    $types .= 's';
}
if ($cronograma !== '') {
    $where[] = "cronograma = ?";
    $params[] = $cronograma;
    $types .= 's';
}
if ($estado !== '') {
    $where[] = "estado = ?";
    $params[] = $estado;
    $types .= 's';
}

$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$totalSql = "SELECT COUNT(*) AS total FROM san_plan_evento";
$totalRes = $conn->query($totalSql);
$recordsTotal = (int)($totalRes ? ($totalRes->fetch_assoc()['total'] ?? 0) : 0);

$recordsFiltered = $recordsTotal;
if ($whereClause) {
    $sqlFiltered = "SELECT COUNT(*) AS total FROM san_plan_evento $whereClause";
    $stmtF = $conn->prepare($sqlFiltered);
    if ($types) $stmtF->bind_param($types, ...$params);
    $stmtF->execute();
    $recordsFiltered = (int)($stmtF->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtF->close();
}

// Datos (incluye indicador de enlace)
$sql = "
    SELECT 
        p.*,
        CASE WHEN lm.codEnvio IS NOT NULL THEN 1 ELSE 0 END AS enlazado_muestra,
        CASE WHEN ln.tnumreg IS NOT NULL THEN 1 ELSE 0 END AS enlazado_necropsia
    FROM san_plan_evento p
    LEFT JOIN (
        SELECT DISTINCT codRef, fecToma, codMuestra, codEnvio
        FROM san_plan_link_muestra
    ) lm
      ON lm.codRef = p.codRef AND lm.fecToma = p.fecToma AND (p.codMuestra IS NOT NULL AND lm.codMuestra = p.codMuestra)
    LEFT JOIN san_plan_link_necropsia ln
      ON ln.plan_id = p.id
    $whereClause
    ORDER BY p.fecToma DESC, p.fecha_programacion DESC
    LIMIT ? OFFSET ?
";
$params2 = array_merge($params, [$length, $start]);
$types2 = $types . 'ii';

$stmt = $conn->prepare($sql);
if ($types2) $stmt->bind_param($types2, ...$params2);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
], JSON_UNESCAPED_UNICODE);

