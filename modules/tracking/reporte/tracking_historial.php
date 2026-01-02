<?php
//ruta relativa a la conexion
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Parámetros de DataTables
$draw = intval($_POST['draw']);
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$search = $_POST['search']['value'] ?? '';
$orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
$orderDir = $_POST['order'][0]['dir'] ?? 'asc';

// Filtros personalizados
$fechaInicio = $_POST['fechaInicio'] ?? '';
$fechaFin = $_POST['fechaFin'] ?? '';
$ubicacionFiltro = $_POST['ubicacion'] ?? '';

// === OBTENER SOLO ENVÍOS QUE TENGAN AL MENOS UN REGISTRO EN GRS ===
$enviosValidosSql = "SELECT DISTINCT codEnvio FROM san_dim_historial_resultados WHERE ubicacion = 'GRS'";
$enviosValidosResult = $conn->query($enviosValidosSql);

if ($enviosValidosResult->num_rows === 0) {
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit;
}

$enviosValidos = [];
while ($row = $enviosValidosResult->fetch_assoc()) {
    $enviosValidos[] = $conn->real_escape_string($row['codEnvio']);
}

$enviosList = "'" . implode("','", $enviosValidos) . "'";

// === CONSULTA PRINCIPAL: solo registros con ubicación válida y de envíos válidos ===
$baseSql = "
    FROM san_dim_historial_resultados 
    WHERE codEnvio IN ($enviosList)
      AND ubicacion IS NOT NULL 
      AND ubicacion IN ('GRS', 'Transporte', 'Laboratorio')
";

// Aplicar filtros
$whereConditions = $baseSql;

if ($fechaInicio) {
    $whereConditions .= " AND fechaHoraRegistro >= '$fechaInicio 00:00:00'";
}
if ($fechaFin) {
    $whereConditions .= " AND fechaHoraRegistro <= '$fechaFin 23:59:59'";
}
if ($ubicacionFiltro) {
    $whereConditions .= " AND ubicacion = '" . $conn->real_escape_string($ubicacionFiltro) . "'";
}

// Búsqueda global
if ($search) {
    $searchEscaped = $conn->real_escape_string($search);
    $whereConditions .= " AND (
        codEnvio LIKE '%$searchEscaped%' OR 
        accion LIKE '%$searchEscaped%' OR 
        comentario LIKE '%$searchEscaped%' OR 
        usuario LIKE '%$searchEscaped%'
    )";
}

// Total registros filtrados
$countSql = "SELECT COUNT(*) as total $whereConditions";
$totalFiltered = $conn->query($countSql)->fetch_assoc()['total'];

// Ordenamiento
$columns = ['id', 'codEnvio', 'accion', 'comentario', 'evidencia', 'usuario', 'ubicacion', 'fechaHoraRegistro', 'id'];
$orderColumn = $columns[$orderColumnIndex] ?? 'fechaHoraRegistro';

$orderSql = " ORDER BY $orderColumn $orderDir";

// Limit
$limitSql = ($length > 0) ? " LIMIT $start, $length" : "";

// Datos finales
$dataSql = "SELECT id, codEnvio, accion, comentario, evidencia, usuario, ubicacion, fechaHoraRegistro $whereConditions $orderSql $limitSql";
$result = $conn->query($dataSql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Total general (sin filtros, solo envíos válidos con ubicación)
$totalSql = "SELECT COUNT(*) as total 
             FROM san_dim_historial_resultados 
             WHERE codEnvio IN ($enviosList) 
               AND ubicacion IS NOT NULL 
               AND ubicacion IN ('GRS', 'Transporte', 'Laboratorio')";
$totalRecords = $conn->query($totalSql)->fetch_assoc()['total'];

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalFiltered,
    'data' => $data
]);