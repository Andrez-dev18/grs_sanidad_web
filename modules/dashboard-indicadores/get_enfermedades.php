<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode(['labels' => [], 'data' => []]);
    exit;
}

// Consulta corregida: une con san_dim_analisis para obtener nombre y enfermedad
$sql = "
    SELECT 
        COALESCE(da.enfermedad, 'Sin enfermedad') AS enfermedad,
        da.nombre AS nombre_analisis,
        COUNT(*) AS ocurrencias
    FROM san_analisis_pollo_bb_adulto a
    INNER JOIN san_fact_solicitud_det sd ON a.codigo_envio = sd.codEnvio AND a.posSolicitud = sd.posSolicitud
    INNER JOIN san_dim_analisis da ON sd.codAnalisis = da.codigo
    GROUP BY da.codigo, da.enfermedad, da.nombre
    ORDER BY ocurrencias DESC
    LIMIT 10
";

$result = $conn->query($sql);

$labels = [];
$data = [];

if ($result === false) {
    error_log("Error SQL en get_enfermedades: " . $conn->error);
    echo json_encode(['labels' => [], 'data' => []]);
    exit;
}

while ($row = $result->fetch_assoc()) {
    $enfermedad = $row['enfermedad'];
    $analisis = $row['nombre_analisis'] ?? 'Análisis desconocido';

    // Formato final del label
    if ($enfermedad !== 'Sin enfermedad' && trim($enfermedad) !== '') {
        $label = $enfermedad . ' (' . $analisis . ')';
    } else {
        $label = $analisis;
    }

    $labels[] = $label;
    $data[] = (int)$row['ocurrencias'];
}

echo json_encode([
    'labels' => $labels,
    'data'   => $data
]);