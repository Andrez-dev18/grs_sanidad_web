<?php
header('Content-Type: application/json');
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$periodo = $_GET['periodo'] ?? 'mes';

$groupBy = '';
$format = '';

switch ($periodo) {
    case 'dia':
        $groupBy = 'DATE(fechaHoraRegistro)';
        $format = '%Y-%m-%d';
        break;
    case 'semana':
        $groupBy = 'YEAR(fechaHoraRegistro), WEEK(fechaHoraRegistro, 1)';
        $format = 'Sem %v';
        break;
    case 'mes':
    default:
        $groupBy = 'YEAR(fechaHoraRegistro), MONTH(fechaHoraRegistro)';
        $format = '%Y-%m';
        break;
}

// Contar envíos nuevos (primer registro: ENVIO_REGISTRADO o ubicación GRS)
$sql = "
    SELECT DATE_FORMAT(fechaHoraRegistro, '$format') AS periodo,
           COUNT(DISTINCT codEnvio) AS envios
    FROM san_dim_historial_resultados
    WHERE accion = 'ENVIO_REGISTRADO' OR (ubicacion = 'GRS' AND accion IS NULL)
    GROUP BY periodo
    ORDER BY periodo DESC
    LIMIT 12
";

$result = $conn->query($sql);

$labels = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['periodo'];
    $data[] = (int)$row['envios'];
}

echo json_encode([
    'labels' => array_reverse($labels),
    'data' => array_reverse($data)
]);