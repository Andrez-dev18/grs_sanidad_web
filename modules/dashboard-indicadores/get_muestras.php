<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode(['labels' => [], 'data' => []]);
    exit;
}

$periodo = $_GET['periodo'] ?? 'anio';

$groupBy = '';
$format = '';
$order = 'DESC';

switch ($periodo) {
    case 'anio':
        $groupBy = 'YEAR(fecToma)';
        $format = '%Y';
        break;
    case 'mes':
        $groupBy = 'YEAR(fecToma), MONTH(fecToma)';
        $format = '%Y-%m';
        break;
    case 'dia':
        $groupBy = 'fecToma';
        $format = '%Y-%m-%d';
        break;
    default:
        $groupBy = 'YEAR(fecToma)';
        $format = '%Y';
}

$sql = "
    SELECT DATE_FORMAT(fecToma, '$format') AS periodo_label, 
           COUNT(DISTINCT codEnvio) AS muestras
    FROM san_fact_solicitud_det
    WHERE fecToma IS NOT NULL
    GROUP BY periodo_label
    ORDER BY periodo_label $order
    LIMIT 12
";

$result = $conn->query($sql);

$labels = [];
$data = [];

if ($result === false) {
    error_log("Error SQL en get_muestras: " . $conn->error);
    echo json_encode(['labels' => [], 'data' => []]);
    exit;
}

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['periodo_label'];
    $data[] = (int)$row['muestras'];
}

// Invertir para mostrar más reciente primero (opcional)
if ($order === 'DESC') {
    $labels = array_reverse($labels);
    $data = array_reverse($data);
}

echo json_encode(['labels' => $labels, 'data' => $data]);