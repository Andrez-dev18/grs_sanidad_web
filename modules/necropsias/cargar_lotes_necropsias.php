<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

// 1. Recepción de parámetros
$draw = intval($_REQUEST['draw'] ?? 1);
$start = intval($_REQUEST['start'] ?? 0);
$length = intval($_REQUEST['length'] ?? 10);

// Filtros
$search_value = $_REQUEST['search']['value'] ?? $_REQUEST['q'] ?? '';
$filtroInicio = $_REQUEST['fecha_inicio'] ?? '';
$filtroFin    = $_REQUEST['fecha_fin'] ?? '';
$filtroGranja = $_REQUEST['granja'] ?? '';

// 2. Construcción dinámica del WHERE
$where = [];
$params = [];
$types = '';

// A) Buscador de Texto (Input searchReportes)
// Busca por Granja, Numero de Registro o Fecha
if (!empty($search_value)) {
    $like = "%$search_value%";
    $where[] = "(tgranja LIKE ? OR CAST(tnumreg AS CHAR) LIKE ? OR tfectra LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

// B) Filtro Fecha Inicio
if (!empty($filtroInicio)) {
    $where[] = "tfectra >= ?";
    $params[] = $filtroInicio;
    $types .= 's';
}

// C) Filtro Fecha Fin
if (!empty($filtroFin)) {
    $where[] = "tfectra <= ?";
    $params[] = $filtroFin;
    $types .= 's';
}

// D) Filtro Granja
// Usamos LIKE 'XXX%' porque en BD es '632001' y el filtro envía '632'
if (!empty($filtroGranja)) {
    $where[] = "tgranja LIKE ?";
    $params[] = $filtroGranja . '%';
    $types .= 's';
}

// Construir String WHERE
$sqlWhere = "";
if (!empty($where)) {
    $sqlWhere = " WHERE " . implode(' AND ', $where);
}

// 3. Contar Total Registros (Sin filtros)
$total_sql = "SELECT COUNT(DISTINCT tgranja, tnumreg, tfectra) AS total FROM t_regnecropsia";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->execute();
$total_res = $total_stmt->get_result()->fetch_assoc();
$total = $total_res ? $total_res['total'] : 0;
$total_stmt->close();

// 4. Contar Registros Filtrados
$filtered_sql = "SELECT COUNT(DISTINCT tgranja, tnumreg, tfectra) AS total FROM t_regnecropsia" . $sqlWhere;
$filtered_stmt = $conn->prepare($filtered_sql);
if (!empty($params)) {
    $filtered_stmt->bind_param($types, ...$params);
}
$filtered_stmt->execute();
$filtered_res = $filtered_stmt->get_result()->fetch_assoc();
$filtered = $filtered_res ? $filtered_res['total'] : 0;
$filtered_stmt->close();

// 5. Consulta de Datos
$sql = "SELECT DISTINCT tgranja, tnumreg, tfectra, tcencos, tedad, tgalpon, tcampania, tuser, tdate, ttime
        FROM t_regnecropsia" . $sqlWhere;

// ORDENAMIENTO: Fecha Necropsia DESC -> Fecha Registro DESC -> Hora Registro DESC
// Así te aseguras que lo último ingresado salga primero
$sql .= " ORDER BY tfectra DESC, tdate DESC, ttime DESC LIMIT ? OFFSET ?";

$params[] = $length;
$params[] = $start;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'tgranja' => $row['tgranja'],
        'tnumreg' => $row['tnumreg'],
        'tfectra' => date('d/m/Y', strtotime($row['tfectra'])), // Formato vista
        'tcencos' => $row['tcencos'] ?? '',
        'tedad' => $row['tedad'] ?? '',
        'tgalpon' => $row['tgalpon'] ?? '',
        'tcampania' => $row['tcampania'] ?? '',
        'tuser' => $row['tuser'] ?? '',
        'tdate' => $row['tdate'],
        'ttime' => $row['ttime']
    ];
}

echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $total,
    "recordsFiltered" => $filtered,
    "data" => $data
]);

$stmt->close();
$conn->close();
?>