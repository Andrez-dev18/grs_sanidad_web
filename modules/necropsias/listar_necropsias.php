<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$draw = intval($_POST['draw']);
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$search = $_POST['search']['value'] ?? '';

// Columnas visibles en la tabla (para ordenar)
$columns = [
    'tcencos', 'tedad', 'tgalpon', 'tnumreg', 'tfectra',
    'tsistema', 'tnivel', 'tparametro',
    'tporcentaje1', 'tporcentajetotal', 'tobservacion'
];

$order_column_index = $_POST['order'][0]['column'] ?? 0;
$order_dir = $_POST['order'][0]['dir'] ?? 'desc';
$order_column = $columns[$order_column_index] ?? 'tfectra';

// Consulta principal: TODOS los registros detallados
$sql = "SELECT 
            tid, tcencos, tedad, tgalpon, tnumreg, tfectra,
            tsistema, tnivel, tparametro,
            tporcentaje1, tporcentaje2, tporcentaje3, tporcentaje4, tporcentaje5,
            tporcentajetotal, tobservacion, evidencia, tuser, tdate, ttime
        FROM t_regnecropsia";

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(tcencos LIKE ? OR tgranja LIKE ? OR tgalpon LIKE ? OR tnumreg LIKE ? OR tfectra LIKE ? OR tsistema LIKE ? OR tnivel LIKE ? OR tparametro LIKE ?)";
    $like = "%$search%";
    $params = array_fill(0, 8, $like);
    $types = str_repeat('s', 8);
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

// Total registros (sin filtro)
$total = $conn->query("SELECT COUNT(*) AS total FROM t_regnecropsia")->fetch_assoc()['total'];

// Total filtrados
$filtered = $total;
if (!empty($where)) {
    $count_sql = "SELECT COUNT(*) AS total FROM t_regnecropsia WHERE " . implode(' AND ', $where);
    $stmt_count = $conn->prepare($count_sql);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $filtered = $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();
}

// Ordenar y paginar
$sql .= " ORDER BY tdate DESC, tnumreg DESC, tgranja ASC, tid ASC LIMIT ? OFFSET ?";
$params[] = $length;
$params[] = $start;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    // Formatear fecha
    $fechaRegistro = $row['tdate'] === '1000-01-01' ? '-' : 
        date('d/m/Y H:i', strtotime($row['tdate'] . ' ' . $row['ttime']));

    $data[] = [
        'tid' => $row['tid'],
        'tcencos' => $row['tcencos'],
        'tedad' => $row['tedad'],
        'tgalpon' => $row['tgalpon'],
        'tnumreg' => $row['tnumreg'],
        'tfectra' => date('d/m/Y', strtotime($row['tfectra'])),
        'tsistema' => $row['tsistema'],
        'tnivel' => $row['tnivel'],
        'tparametro' => $row['tparametro'],
        'tporcentaje1' => $row['tporcentaje1'],
        'tporcentaje2' => $row['tporcentaje2'],
        'tporcentaje3' => $row['tporcentaje3'],
        'tporcentaje4' => $row['tporcentaje4'],
        'tporcentaje5' => $row['tporcentaje5'],
        'tporcentajetotal' => $row['tporcentajetotal'],
        'tobservacion' => $row['tobservacion'],
        'evidencia' => $row['evidencia'],
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