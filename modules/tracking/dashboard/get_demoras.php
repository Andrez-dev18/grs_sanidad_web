<?php
header('Content-Type: application/json');
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$unidad = $_GET['unidad'] ?? 'horas';

$timeUnit = $unidad === 'dias' ? 'DAY' : 'HOUR';

$sql = "
    -- 1. Demora GRS → Transporte
    SELECT 
        'GRS → Transporte' AS etapa,
        AVG(ABS(TIMESTAMPDIFF($timeUnit, g.min_fecha, t.min_fecha))) AS demora
    FROM (
        SELECT codEnvio, MIN(fechaHoraRegistro) AS min_fecha
        FROM san_dim_historial_resultados
        WHERE ubicacion = 'GRS'
        GROUP BY codEnvio
    ) g
    INNER JOIN (
        SELECT codEnvio, MIN(fechaHoraRegistro) AS min_fecha
        FROM san_dim_historial_resultados
        WHERE ubicacion = 'Transporte'
        GROUP BY codEnvio
    ) t ON g.codEnvio = t.codEnvio
    WHERE t.min_fecha >= g.min_fecha  -- solo casos válidos (evita negativos por datos mal registrados)

    UNION ALL

    -- 2. Demora Transporte → Laboratorio
    SELECT 
        'Transporte → Laboratorio' AS etapa,
        AVG(ABS(TIMESTAMPDIFF($timeUnit, t.min_fecha, l.min_fecha))) AS demora
    FROM (
        SELECT codEnvio, MIN(fechaHoraRegistro) AS min_fecha
        FROM san_dim_historial_resultados
        WHERE ubicacion = 'Transporte'
        GROUP BY codEnvio
    ) t
    INNER JOIN (
        SELECT codEnvio, MIN(fechaHoraRegistro) AS min_fecha
        FROM san_dim_historial_resultados
        WHERE ubicacion = 'Laboratorio'
        GROUP BY codEnvio
    ) l ON t.codEnvio = l.codEnvio
    WHERE l.min_fecha >= t.min_fecha

    UNION ALL

    -- 3. Tiempo promedio en Laboratorio (desde llegada hasta hoy)
    SELECT 
        'En Laboratorio' AS etapa,
        AVG(TIMESTAMPDIFF($timeUnit, l.min_fecha, NOW())) AS demora
    FROM (
        SELECT codEnvio, MIN(fechaHoraRegistro) AS min_fecha
        FROM san_dim_historial_resultados
        WHERE ubicacion = 'Laboratorio'
        GROUP BY codEnvio
    ) l
";

$result = $conn->query($sql);

$labels = [];
$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['etapa'];
        $demora = (float)$row['demora'];
        // Redondear a 1 decimal
        $data[] = round($demora, 1);
    }
} else {
    // Si no hay datos, devolver 0
    $labels = ['GRS → Transporte', 'Transporte → Laboratorio', 'En Laboratorio'];
    $data = [0, 0, 0];
}

echo json_encode([
    'labels' => $labels,
    'data' => $data
]);