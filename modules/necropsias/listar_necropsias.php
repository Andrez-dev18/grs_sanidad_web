<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$draw = intval($_POST['draw']);
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$search = $_POST['search']['value'] ?? '';

// Filtros extra (desde dashboard-necropsias-listado.php)
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';
$granja_filtro = $_POST['granja'] ?? '';

// Columnas visibles en la tabla (para ordenar)
// DataTables: 0=counter(no ordenable), 1=tnumreg, 2=tfectra, 3=tgranja, 4=tcencos, 5=tcampania, 6=tgalpon, 7=tedad, 8=tuser, 9=tdate
$columns_map = [
    1 => 'tnumreg',
    2 => 'tfectra',    // fecha necropsia
    3 => 'tgranja',    // granja (3 primeros dígitos) se ordena por tgranja
    4 => 'tcencos',    // nombre se ordena por tcencos
    5 => 'tcampania',  // campaña se ordena por tcampania (últimos 3 dígitos de tgranja)
    6 => 'tgalpon',
    7 => 'tedad',
    8 => 'tuser',
    9 => 'tdate'       // fecha registro
];

$order_column_index = (int)($_POST['order'][0]['column'] ?? 9);
$order_dir = $_POST['order'][0]['dir'] ?? 'desc';
$order_column = $columns_map[$order_column_index] ?? 'tdate';

// Búsqueda
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $like = "%$search%";
    $where[] = "(tcencos LIKE ? OR tgranja LIKE ? OR CAST(tnumreg AS CHAR) LIKE ? OR tfectra LIKE ? OR tdate LIKE ? OR tgalpon LIKE ? OR tuser LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like]);
    $types .= str_repeat('s', 7);
}

// Fecha inicio/fin sobre tfectra (Y-m-d)
if (!empty($fecha_inicio)) {
    $where[] = "tfectra >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
}

if (!empty($fecha_fin)) {
    $where[] = "tfectra <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}

// Granja exacta (tgranja suele ser el código completo)
if (!empty($granja_filtro)) {
    $where[] = "tgranja = ?";
    $params[] = $granja_filtro;
    $types .= 's';
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

// Consulta lotes únicos (cabeceras) con indicador de enlace planificación
$sql = "SELECT DISTINCT r.tgranja, r.tnumreg, r.tfectra, r.tcencos, r.tedad, r.tgalpon, r.tuser, r.tdate, r.ttime,
        (SELECT COUNT(*) FROM san_plan_link_necropsia ln WHERE ln.tgranja = r.tgranja AND ln.tfectra = r.tfectra AND ln.tnumreg = r.tnumreg) AS enlace_plan
        FROM t_regnecropsia r";

if (!empty($where)) {
    $whereStr = implode(' AND ', $where);
    foreach (['tgranja', 'tnumreg', 'tfectra', 'tcencos', 'tedad', 'tgalpon', 'tuser', 'tdate'] as $col) {
        $whereStr = preg_replace('/\b' . $col . '\b/', "r.$col", $whereStr);
    }
    $sql .= " WHERE " . $whereStr;
}

// Ordenar: por fecha de registro usar tdate + ttime (incluye hora)
$orderCol = "r.$order_column";
if ($order_column === 'tdate') {
    $sql .= " ORDER BY r.tdate $order_dir, r.ttime $order_dir, r.tgranja ASC LIMIT ? OFFSET ?";
} elseif (in_array($order_column, ['tgranja', 'tnumreg', 'tfectra', 'tcencos', 'tedad', 'tgalpon', 'tuser'])) {
    $sql .= " ORDER BY $orderCol $order_dir, r.tdate DESC, r.ttime DESC, r.tgranja ASC LIMIT ? OFFSET ?";
} else {
    $sql .= " ORDER BY r.tdate $order_dir, r.ttime $order_dir, r.tgranja ASC LIMIT ? OFFSET ?";
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

    // Extraer granja (3 primeros dígitos de tgranja)
    $granja = substr($row['tgranja'], 0, 3);
    
    // Extraer campaña (últimos 3 dígitos de tgranja)
    $campania = strlen($row['tgranja']) >= 3 ? substr($row['tgranja'], -3) : '';
    
    // Extraer nombre (tcencos hasta antes de C=)
    $nombre = $row['tcencos'];
    if (strpos($nombre, 'C=') !== false) {
        $nombre = trim(substr($nombre, 0, strpos($nombre, 'C=')));
    }
    
    $enlacePlan = !empty($row['enlace_plan']) && (int)$row['enlace_plan'] > 0;

    $data[] = [
        'counter' => $counter++,
        'tgranja' => $row['tgranja'],
        'granja' => $granja, // 3 primeros dígitos
        'tcencos' => $row['tcencos'],
        'nombre' => $nombre, // Nombre hasta antes de C=
        'campania' => $campania, // Últimos 3 dígitos de tgranja
        'tedad' => $row['tedad'],
        'tgalpon' => $row['tgalpon'],
        'tnumreg' => $row['tnumreg'],
        'tfectra' => $fechaNecropsia,
        'tfectra_raw' => $row['tfectra'], // Formato Y-m-d para editar
        'tuser' => $row['tuser'],
        'tdate' => $row['tdate'],
        'fecha_registro' => $fechaRegistro,
        'enlace_plan' => $enlacePlan
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