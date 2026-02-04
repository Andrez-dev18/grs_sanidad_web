<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['draw' => (int)($_POST['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['draw' => (int)($_POST['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
    exit();
}
mysqli_set_charset($conn, 'utf8');

$tblPlan = $conn->query("SHOW TABLES LIKE 'san_plan'");
$usaSanPlan = ($tblPlan && $tblPlan->num_rows > 0);

$draw = (int)($_POST['draw'] ?? 1);
$start = (int)($_POST['start'] ?? 0);
$length = (int)($_POST['length'] ?? 25);
$length = max(1, min(100, $length));
$search = trim($_POST['search']['value'] ?? '');
$fecha_desde = trim($_POST['fecha_desde'] ?? '');
$fecha_hasta = trim($_POST['fecha_hasta'] ?? '');
$granja = trim($_POST['granja'] ?? '');
$codCronograma = trim($_POST['codCronograma'] ?? $_POST['cronograma'] ?? '');
$estado = trim($_POST['estado'] ?? '');

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $like = "%$search%";
    if ($usaSanPlan) {
        $where[] = "(p.nomCronograma LIKE ? OR p.codRef LIKE ? OR p.nomGranja LIKE ? OR p.responsable LIKE ? OR p.nomDestino LIKE ? OR p.lugarToma LIKE ? OR p.nomMuestra LIKE ?)";
    } else {
        $where[] = "(c.nomCronograma LIKE ? OR c.codRef LIKE ? OR c.nomGranja LIKE ? OR c.responsable LIKE ? OR c.nomDestino LIKE ? OR c.lugarToma LIKE ? OR EXISTS (SELECT 1 FROM san_plan_det d2 WHERE d2.cabId = c.id AND d2.nomMuestra LIKE ?))";
    }
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like]);
    $types .= 'sssssss';
}

if ($fecha_desde !== '') { $where[] = ($usaSanPlan ? "p" : "c") . ".fecToma >= ?"; $params[] = $fecha_desde; $types .= 's'; }
if ($fecha_hasta !== '') { $where[] = ($usaSanPlan ? "p" : "c") . ".fecToma <= ?"; $params[] = $fecha_hasta; $types .= 's'; }
if ($granja !== '') { $where[] = ($usaSanPlan ? "p" : "c") . ".granja = ?"; $params[] = $granja; $types .= 's'; }
if ($codCronograma !== '') { $where[] = ($usaSanPlan ? "p" : "c") . ".codCronograma = ?"; $params[] = $codCronograma; $types .= 's'; }
if ($estado !== '') { $where[] = ($usaSanPlan ? "p" : "c") . ".estado = ?"; $params[] = $estado; $types .= 's'; }

$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

if ($usaSanPlan) {
    $totalSql = "SELECT COUNT(*) AS total FROM san_plan p";
    $totalRes = $conn->query($totalSql);
    $recordsTotal = (int)($totalRes ? ($totalRes->fetch_assoc()['total'] ?? 0) : 0);
    $recordsFiltered = $recordsTotal;
    if ($whereClause) {
        $stmtF = $conn->prepare("SELECT COUNT(*) AS total FROM san_plan p $whereClause");
        if ($types) $stmtF->bind_param($types, ...$params);
        $stmtF->execute();
        $recordsFiltered = (int)($stmtF->get_result()->fetch_assoc()['total'] ?? 0);
        $stmtF->close();
    }
    $sql = "SELECT p.id AS planId, p.id AS cabId, p.fecToma, p.codCronograma, p.nomCronograma, p.granja, p.nomGranja, p.campania, p.galpon, p.edad, p.codRef, p.lugarToma, p.responsable, p.codDestino, p.nomDestino, p.estado, 1 AS filas, p.nMacho AS totalMacho, p.nHembra AS totalHembra, p.nomMuestra,
            MAX(CASE WHEN lm.codEnvio IS NOT NULL THEN 1 ELSE 0 END) AS enlazadoMuestra,
            MAX(CASE WHEN ln.tnumreg IS NOT NULL THEN 1 ELSE 0 END) AS enlazadoNecropsia
            FROM san_plan p
            LEFT JOIN (SELECT DISTINCT planId, codEnvio FROM san_plan_link_muestra) lm ON lm.planId = p.id
            LEFT JOIN san_plan_link_necropsia ln ON ln.planId = p.id
            $whereClause
            GROUP BY p.id
            ORDER BY p.fecToma DESC, p.nomCronograma ASC
            LIMIT ? OFFSET ?";
} else {
    $totalSql = "SELECT COUNT(*) AS total FROM san_plan_cab";
    $totalRes = $conn->query($totalSql);
    $recordsTotal = (int)($totalRes ? ($totalRes->fetch_assoc()['total'] ?? 0) : 0);
    $recordsFiltered = $recordsTotal;
    if ($whereClause) {
        $stmtF = $conn->prepare("SELECT COUNT(*) AS total FROM san_plan_cab c $whereClause");
        if ($types) $stmtF->bind_param($types, ...$params);
        $stmtF->execute();
        $recordsFiltered = (int)($stmtF->get_result()->fetch_assoc()['total'] ?? 0);
        $stmtF->close();
    }
    $sql = "SELECT c.id AS cabId, c.fecToma, c.codCronograma, c.nomCronograma, c.granja, c.nomGranja, c.campania, c.galpon, c.edad, c.codRef, c.lugarToma, c.responsable, c.codDestino, c.nomDestino, c.estado,
            COUNT(d.id) AS filas, SUM(IFNULL(d.nMacho, 0)) AS totalMacho, SUM(IFNULL(d.nHembra, 0)) AS totalHembra, GROUP_CONCAT(DISTINCT d.nomMuestra ORDER BY d.nomMuestra SEPARATOR ', ') AS nomMuestra,
            MAX(CASE WHEN lm.codEnvio IS NOT NULL THEN 1 ELSE 0 END) AS enlazadoMuestra, MAX(CASE WHEN ln.tnumreg IS NOT NULL THEN 1 ELSE 0 END) AS enlazadoNecropsia
            FROM san_plan_cab c
            LEFT JOIN san_plan_det d ON d.cabId = c.id
            LEFT JOIN (SELECT DISTINCT cabId, codEnvio FROM san_plan_link_muestra) lm ON lm.cabId = c.id
            LEFT JOIN san_plan_link_necropsia ln ON ln.cabId = c.id
            $whereClause
            GROUP BY c.id, c.fecToma, c.codCronograma, c.nomCronograma, c.granja, c.nomGranja, c.campania, c.galpon, c.edad, c.codRef, c.lugarToma, c.responsable, c.codDestino, c.nomDestino, c.estado
            ORDER BY c.fecToma DESC, c.nomCronograma ASC
            LIMIT ? OFFSET ?";
}

$params2 = array_merge($params, [$length, $start]);
$types2 = $types . 'ii';
$stmt = $conn->prepare($sql);
if ($types2) $stmt->bind_param($types2, ...$params2);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;

echo json_encode(['draw' => $draw, 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered, 'data' => $data], JSON_UNESCAPED_UNICODE);
