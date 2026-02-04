<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$draw = intval($_REQUEST['draw'] ?? 1);
$start = intval($_REQUEST['start'] ?? 0);
$length = intval($_REQUEST['length'] ?? 10);
$search_value = $_REQUEST['search']['value'] ?? $_REQUEST['q'] ?? '';

// Búsqueda
$where = [];
$params = [];
$types = '';

if (!empty($search_value)) {
    $like = "%$search_value%";
    $where[] = "(tgranja LIKE ? OR CAST(tnumreg AS CHAR) LIKE ? OR tfectra LIKE ?)";
    $params = [$like, $like, $like];
    $types = 'sss';
}

// Total lotes únicos (sin filtro)
$total_sql = "SELECT COUNT(DISTINCT tgranja, tnumreg, tfectra) AS total FROM t_regnecropsia";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->execute();
$total = $total_stmt->get_result()->fetch_assoc()['total'];
$total_stmt->close();

// Total filtrado
$filtered = $total;
if (!empty($where)) {
    $filtered_sql = "SELECT COUNT(DISTINCT tgranja, tnumreg, tfectra) AS total FROM t_regnecropsia WHERE " . implode(' AND ', $where);
    $filtered_stmt = $conn->prepare($filtered_sql);
    $filtered_stmt->bind_param($types, ...$params);
    $filtered_stmt->execute();
    $filtered = $filtered_stmt->get_result()->fetch_assoc()['total'];
    $filtered_stmt->close();
}

// Consulta lotes únicos
$sql = "SELECT DISTINCT tgranja, tnumreg, tfectra, tcencos, tedad, tgalpon, tcampania, tuser, tdate, ttime
        FROM t_regnecropsia";

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY tdate DESC, tnumreg DESC, tgranja ASC LIMIT ? OFFSET ?";
$params[] = $length;
$params[] = $start;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    // Formatear fecha de registro (tdate) para mostrar en tarjetas
    $fechaRegistro = ($row['tdate'] ?? '') === '1000-01-01' || empty($row['tdate'])
        ? '-'
        : date('d/m/Y', strtotime($row['tdate']));

    $data[] = [
        'tgranja' => $row['tgranja'],
        'tnumreg' => $row['tnumreg'],
        'tfectra' => date('d/m/Y', strtotime($row['tfectra'])),
        'tcencos' => $row['tcencos'] ?? '',
        'tedad' => $row['tedad'] ?? '',
        'tgalpon' => $row['tgalpon'] ?? '',
        'tcampania' => $row['tcampania'] ?? '',
        'tuser' => $row['tuser'] ?? '',
        'tdate' => $row['tdate'],
        'ttime' => $row['ttime'],
        'fecha_registro' => $fechaRegistro
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