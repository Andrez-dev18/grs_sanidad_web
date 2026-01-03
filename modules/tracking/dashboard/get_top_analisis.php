<?php
header('Content-Type: application/json');
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode(['labels' => [], 'data' => []]);
    exit;
}

$sql = "
    SELECT nomAnalisis, COUNT(*) AS solicitudes
    FROM san_fact_solicitud_det
    WHERE nomAnalisis IS NOT NULL AND TRIM(nomAnalisis) != ''
    GROUP BY codAnalisis, nomAnalisis
    ORDER BY solicitudes DESC
    LIMIT 10
";

$result = $conn->query($sql);

$labels = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['nomAnalisis'];
    $data[] = (int)$row['solicitudes'];
}

echo json_encode([
    'labels' => $labels,
    'data' => $data
]);