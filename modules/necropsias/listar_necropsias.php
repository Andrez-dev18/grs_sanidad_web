<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

// 1. Recepción de parámetros de DataTables
$draw = intval($_POST['draw']);
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$search = $_POST['search']['value'] ?? '';

// 2. Recepción de Filtros Personalizados
$filtroInicio = $_POST['fecha_inicio'] ?? '';
$filtroFin = $_POST['fecha_fin'] ?? '';
$filtroGranja = $_POST['granja'] ?? '';

// Mapeo de columnas para ordenamiento
$columns_map = [
    1 => 'tnumreg',
    2 => 'tfectra',
    3 => 'tgranja',
    4 => 'tcencos',
    5 => 'tcampania', // Alias lógico, en BD ordenaremos por substr o tgranja
    6 => 'tgalpon',
    7 => 'tedad',
    8 => 'tuser',
    9 => 'tdate'
];

// Lógica de Ordenamiento
$order_column_index = $_POST['order'][0]['column'] ?? 2;
$order_dir = $_POST['order'][0]['dir'] ?? 'desc';
$order_column = $columns_map[$order_column_index] ?? 'tfectra';

// 3. Construcción dinámica del WHERE
$where = [];
$params = [];
$types = '';

// A) Filtro Global (Buscador general)
if (!empty($search)) {
    $like = "%$search%";
    $where[] = "(tcencos LIKE ? OR tgranja LIKE ? OR CAST(tnumreg AS CHAR) LIKE ? OR tfectra LIKE ? OR tgalpon LIKE ? OR tuser LIKE ?)";
    // Repetimos el parámetro 6 veces
    array_push($params, $like, $like, $like, $like, $like, $like);
    $types .= str_repeat('s', 6);
}

// B) Filtros Específicos (Fecha y Granja)

// Filtro Fecha Inicio (tfectra)
if (!empty($filtroInicio)) {
    $where[] = "tfectra >= ?";
    $params[] = $filtroInicio;
    $types .= 's';
}

// Filtro Fecha Fin (tfectra)
if (!empty($filtroFin)) {
    $where[] = "tfectra <= ?";
    $params[] = $filtroFin;
    $types .= 's';
}

// Filtro Granja (tgranja)
// La BD tiene '632001', el filtro envía '632'. Usamos LIKE '632%'
if (!empty($filtroGranja)) {
    $where[] = "tgranja LIKE ?";
    $params[] = $filtroGranja . '%'; 
    $types .= 's';
}

// Construir string WHERE
$sqlWhere = "";
if (!empty($where)) {
    $sqlWhere = " WHERE " . implode(' AND ', $where);
}

// 4. Contar Total Registros (Sin filtros)
$total_sql = "SELECT COUNT(DISTINCT tgranja, tnumreg, tfectra) AS total FROM t_regnecropsia";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->execute();
$total = $total_stmt->get_result()->fetch_assoc()['total'];
$total_stmt->close();

// 5. Contar Registros Filtrados (Con WHERE)
$filtered_sql = "SELECT COUNT(DISTINCT tgranja, tnumreg, tfectra) AS total FROM t_regnecropsia" . $sqlWhere;
$filtered_stmt = $conn->prepare($filtered_sql);
if (!empty($params)) {
    $filtered_stmt->bind_param($types, ...$params);
}
$filtered_stmt->execute();
$filtered = $filtered_stmt->get_result()->fetch_assoc()['total'];
$filtered_stmt->close();

// 6. Consulta de Datos (Con Paginación y Orden)
$sql = "SELECT DISTINCT tgranja, tnumreg, tfectra, tcencos, tedad, tgalpon, tuser, tdate, ttime
        FROM t_regnecropsia" . $sqlWhere;

// Ordenamiento robusto: Si ordenan por fecha, usamos tfectra y ttime para precisión
if ($order_column === 'tfectra') {
    $sql .= " ORDER BY tfectra $order_dir, ttime $order_dir"; 
} else {
    $sql .= " ORDER BY $order_column $order_dir";
}

$sql .= " LIMIT ? OFFSET ?";

// Agregamos params de paginación
$params[] = $length;
$params[] = $start;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// 7. Procesamiento de filas
$data = [];
$counter = $start + 1;

while ($row = $result->fetch_assoc()) {
    // Formatear fecha de registro
    $fechaRegistro = ($row['tdate'] === '1000-01-01' || !$row['tdate']) ? '-' : 
        date('d/m/Y H:i', strtotime($row['tdate'] . ' ' . ($row['ttime'] ?? '00:00:00')));
    
    // Formatear fecha de necropsia
    $fechaNecropsia = date('d/m/Y', strtotime($row['tfectra']));

    // Extraer granja (3 primeros dígitos de tgranja)
    $granja = substr($row['tgranja'], 0, 3);
    
    // Extraer campaña (últimos 3 dígitos de tgranja)
    $campania = strlen($row['tgranja']) >= 3 ? substr($row['tgranja'], -3) : '';
    
    // Extraer nombre (Limpiar el C=...)
    $nombre = $row['tcencos'];
    if (strpos($nombre, 'C=') !== false) {
        $nombre = trim(explode('C=', $nombre)[0]);
    }
    
    $data[] = [
        'counter' => $counter++,
        'tgranja' => $row['tgranja'], // Código completo oculto o para lógica
        'granja' => $granja,          // Solo código granja visible
        'tcencos' => $row['tcencos'],
        'nombre' => $nombre,          // Nombre limpio
        'campania' => $campania,
        'tedad' => $row['tedad'],
        'tgalpon' => $row['tgalpon'],
        'tnumreg' => $row['tnumreg'],
        'tfectra' => $fechaNecropsia,
        'tfectra_raw' => $row['tfectra'],
        'tuser' => $row['tuser'],
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