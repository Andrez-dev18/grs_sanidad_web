<?php
// listar_planificacion.php — COMPATIBLE CON DATATABLES
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode([
        'draw' => (int)($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode([
        'draw' => (int)($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit();
}

// === DATATABLES ENVÍA: draw, start, length ===
$draw = (int)($_GET['draw'] ?? 1);
$start = (int)($_GET['start'] ?? 0);
$length = (int)($_GET['length'] ?? 25);
$length = max(1, min(100, $length)); // entre 1 y 100

// === TUS FILTROS ===
$fecha_desde = $_GET['fecha_desde'] ?? null;
$fecha_hasta = $_GET['fecha_hasta'] ?? null;
$granja = $_GET['granja'] ?? null;      // Nota: JS envía un solo valor (no array)
$campania = $_GET['campania'] ?? null;
$galpon = $_GET['galpon'] ?? null;
$edad = $_GET['edad'] ?? null;

// === CONSTRUIR WHERE ===
$where = [];
$params = [];
$types = '';

if ($fecha_desde) {
    $where[] = "p.fecToma >= ?";
    $params[] = $fecha_desde;
    $types .= 's';
}
if ($fecha_hasta) {
    $where[] = "p.fecToma <= ?";
    $params[] = $fecha_hasta;
    $types .= 's';
}
if ($granja && preg_match('/^\d{3}$/', $granja)) {
    $where[] = "LEFT(p.codRef, 3) = ?";
    $params[] = $granja;
    $types .= 's';
}
if ($campania && preg_match('/^\d{3}$/', $campania)) {
    $where[] = "SUBSTRING(p.codRef, 4, 3) = ?";
    $params[] = $campania;
    $types .= 's';
}
if ($galpon && preg_match('/^\d{2}$/', $galpon)) {
    $where[] = "SUBSTRING(p.codRef, 7, 2) = ?";
    $params[] = $galpon;
    $types .= 's';
}
if ($edad && preg_match('/^\d{2}$/', $edad)) {
    $where[] = "SUBSTRING(p.codRef, 9, 2) = ?";
    $params[] = $edad;
    $types .= 's';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// === CONTAR TOTAL (sin filtro) ===
$sqlTotal = "SELECT COUNT(*) AS total FROM san_planificacion p";
$totalRes = mysqli_query($conexion, $sqlTotal);
$recordsTotal = (int) mysqli_fetch_assoc($totalRes)['total'];

// === CONTAR CON FILTRO ===
$sqlFiltered = "SELECT COUNT(*) AS total FROM san_planificacion p $whereClause";
$stmtF = mysqli_prepare($conexion, $sqlFiltered);
if ($params) mysqli_stmt_bind_param($stmtF, $types, ...$params);
mysqli_stmt_execute($stmtF);
$recordsFiltered = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmtF))['total'];

// === CONSULTA PRINCIPAL ===
// En la consulta principal, selecciona también nomMuestra
$sql = "
    SELECT 
        p.fecToma AS fecha,
        LEFT(p.codRef, 3) AS granja,
        c.nombre AS nombreGranja,
        SUBSTRING(p.codRef, 4, 3) AS campania,
        SUBSTRING(p.codRef, 7, 2) AS galpon,
        SUBSTRING(p.codRef, 9, 2) AS edad,
        p.nomMuestra, 
        p.nomAnalisis
    FROM san_planificacion p
    LEFT JOIN ccos c ON LEFT(p.codRef, 3) = c.codigo
    $whereClause
    ORDER BY p.fecToma DESC, p.codRef
    LIMIT ? OFFSET ?
";
array_push($params, $length, $start);
$types .= 'ii';

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$registros = [];
while ($row = mysqli_fetch_assoc($result)) {
    $registros[] = $row;
}

// === AGRUPAR ANÁLISIS ===
$agrupado = [];
foreach ($registros as $r) {
    $key = $r['fecha'] . '|' . $r['granja'] . $r['campania'] . $r['galpon'] . $r['edad'];
    if (!isset($agrupado[$key])) {
        $agrupado[$key] = [
            'fecha' => $r['fecha'],
            'granja' => $r['granja'],
            'nombreGranja' => $r['nombreGranja'] ?: '–',
            'campania' => $r['campania'],
            'galpon' => $r['galpon'],
            'edad' => $r['edad'],
            'analisis' => []
        ];
    }
    $agrupado[$key]['analisis'][] = $r['nomAnalisis'];
}

$data = [];
foreach ($agrupado as $item) {
    $item['analisisResumen'] = implode(', ', array_unique($item['analisis']));
    unset($item['analisis']);
    $data[] = $item;
}

// === RESPUESTA COMPATIBLE CON DATATABLES ===
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
], JSON_UNESCAPED_UNICODE);
?>