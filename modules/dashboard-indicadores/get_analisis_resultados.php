<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$modo = $_GET['modo'] ?? 'mas'; // 'mas' o 'menos'

$order = $modo === 'mas' ? 'DESC' : 'ASC';

$sql = "
    SELECT 
        da.nombre AS analisis,
        tm.nombre AS tipo_muestra,
        COUNT(ra.id) AS resultados
    FROM san_fact_solicitud_det sd
    INNER JOIN san_dim_analisis da ON sd.codAnalisis = da.codigo
    INNER JOIN san_dim_tipo_muestra tm ON sd.codMuestra = tm.codigo
    LEFT JOIN san_fact_resultado_analisis ra ON sd.codEnvio = ra.codEnvio AND sd.posSolicitud = ra.posSolicitud
    GROUP BY sd.codAnalisis, sd.codMuestra
    HAVING resultados > 0
    ORDER BY resultados $order
    LIMIT 10
";

$result = $conn->query($sql);

$labels = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $label = $row['analisis'];
    if ($row['tipo_muestra'] && trim($row['tipo_muestra']) !== '') {
        $label .= ' (' . $row['tipo_muestra'] . ')';
    }

    $labels[] = $label;
    $data[] = (int)$row['resultados'];
}

echo json_encode([
    'labels' => $labels,
    'data' => $data
]);