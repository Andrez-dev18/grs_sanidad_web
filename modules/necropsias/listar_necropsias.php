<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$draw = intval($_POST['draw']);
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$search = $_POST['search']['value'] ?? '';

// Columnas visibles en la tabla (para ordenar)
// DataTables: 0=counter(no ordenable), 1=tnumreg, 2=fecha_registro(tdate), 3=tcencos, 4=tgalpon, 5=tedad, 6=tuser, 7=tfectra
// Mapeo: fecha_registro (columna 2) se ordena por tdate
$columns_map = [
    1 => 'tnumreg',
    2 => 'tdate',      // fecha_registro se ordena por tdate
    3 => 'tcencos',
    4 => 'tgalpon',
    5 => 'tedad',
    6 => 'tuser',
    7 => 'tfectra'
];

$order_column_index = $_POST['order'][0]['column'] ?? 2;
$order_dir = $_POST['order'][0]['dir'] ?? 'desc';
$order_column = $columns_map[$order_column_index] ?? 'tdate';

// Búsqueda
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $like = "%$search%";
    $where[] = "(tcencos LIKE ? OR tgranja LIKE ? OR CAST(tnumreg AS CHAR) LIKE ? OR tfectra LIKE ? OR tgalpon LIKE ? OR tuser LIKE ?)";
    $params = [$like, $like, $like, $like, $like, $like];
    $types = str_repeat('s', 6);
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

// Consulta lotes únicos (cabeceras)
$sql = "SELECT DISTINCT tgranja, tnumreg, tfectra, tcencos, tedad, tgalpon, tuser, tdate, ttime
        FROM t_regnecropsia";

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

// Ordenar
// Si es orden por fecha de registro (tdate), ordenar también por hora (ttime)
if ($order_column === 'tdate') {
    $sql .= " ORDER BY $order_column $order_dir, ttime $order_dir, tgranja ASC LIMIT ? OFFSET ?";
} else {
    $sql .= " ORDER BY $order_column $order_dir, tgranja ASC LIMIT ? OFFSET ?";
}
$params[] = $length;
$params[] = $start;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$counter = $start + 1; // Contador para el número de fila
while ($row = $result->fetch_assoc()) {
    // Formatear fecha de registro
    $fechaRegistro = $row['tdate'] === '1000-01-01' ? '-' : 
        date('d/m/Y H:i', strtotime($row['tdate'] . ' ' . ($row['ttime'] ?? '00:00:00')));
    
    // Formatear fecha de necropsia
    $fechaNecropsia = date('d/m/Y', strtotime($row['tfectra']));

    $data[] = [
        'counter' => $counter++,
        'tgranja' => $row['tgranja'],
        'tcencos' => $row['tcencos'],
        'tedad' => $row['tedad'],
        'tgalpon' => $row['tgalpon'],
        'tnumreg' => $row['tnumreg'],
        'tfectra' => $fechaNecropsia,
        'tuser' => $row['tuser'],
        'tdate' => $row['tdate'],
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