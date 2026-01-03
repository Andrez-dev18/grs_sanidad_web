<?php
header('Content-Type: application/json');
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$unidad = $_GET['unidad'] ?? 'horas';

$timeDiff = $unidad === 'horas' ? "TIMESTAMPDIFF(HOUR, " : "TIMESTAMPDIFF(DAY, ";

$sql = "
    SELECT 
        'GRS → Transporte' AS etapa,
        AVG($timeDiff g.fechaHoraRegistro, t.fechaHoraRegistro)) AS demora
    FROM san_dim_historial_resultados g
    INNER JOIN san_dim_historial_resultados t ON g.codEnvio = t.codEnvio
    WHERE g.ubicacion = 'GRS' AND t.ubicacion = 'Transporte'

    UNION

    SELECT 
        'Transporte → Laboratorio' AS etapa,
        AVG($timeDiff t.fechaHoraRegistro, l.fechaHoraRegistro)) AS demora
    FROM san_dim_historial_resultados t
    INNER JOIN san_dim_historial_resultados l ON t.codEnvio = l.codEnvio
    WHERE t.ubicacion = 'Transporte' AND l.ubicacion = 'Laboratorio'

    UNION

    SELECT 
        'En Laboratorio' AS etapa,
        AVG($timeDiff l.fechaHoraRegistro, NOW())) AS demora
    FROM san_dim_historial_resultados l
    WHERE l.ubicacion = 'Laboratorio'
";

$result = $conn->query($sql);

$labels = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['etapa'];
    $data[] = (float)$row['demora'];
}

echo json_encode([
    'labels' => $labels,
    'data' => $data
]);