<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$draw = intval($_POST['draw']);
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$search = $_POST['search']['value'] ?? '';

// Columnas para ordenar
$columns = ['tfectra', 'tgranja', 'tcampania', 'tgalpon', 'tedad', 'tnumreg', 'tuser', 'tdate'];
$order_column_index = $_POST['order'][0]['column'] ?? 0;
$order_dir = $_POST['order'][0]['dir'] ?? 'desc';
$order_column = $columns[$order_column_index] ?? 'tfectra';

// Consulta base: una fila por necropsia (usando DISTINCT por clave primaria parcial)
$sql = "SELECT DISTINCT 
            tfectra, tgranja, tcampania, tgalpon, tedad, tnumreg, tuser, tdate
        FROM t_regnecropsia";

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(tgranja LIKE ? OR tcampania LIKE ? OR tgalpon LIKE ? OR tnumreg LIKE ? OR tfectra LIKE ? OR tuser LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like, $like);
    $types .= 'ssssss';
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

// Total registros sin filtro
$total_query = $conn->query("SELECT COUNT(DISTINCT tgranja, tgalpon, tnumreg, tfectra) AS total FROM t_regnecropsia");
$total = $total_query->fetch_assoc()['total'];

// Total filtrados
$filtered = $total;
if (!empty($where)) {
    $count_sql = "SELECT COUNT(DISTINCT tgranja, tgalpon, tnumreg, tfectra) AS total FROM t_regnecropsia WHERE " . implode(' AND ', $where);
    $stmt_count = $conn->prepare($count_sql);
    if ($types) $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $filtered = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();
}

// Ordenar y paginar
$sql .= " ORDER BY $order_column $order_dir LIMIT ? OFFSET ?";
$params[] = $length;
$params[] = $start;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
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