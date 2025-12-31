<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
include_once '../../../conexion_grs_joya/conexion.php';

$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión'], JSON_UNESCAPED_UNICODE);
    exit;
}

$year = $_GET['year'] ?? null;
$joinWhere = "";
$params = [];
$types = "";

if ($year && ctype_digit($year)) {
    $joinWhere = "WHERE YEAR(c.fechaHoraRegistro) = ?";
    $params[] = (int)$year;
    $types = "i";
}

$sql = "
    SELECT 
        CASE
            WHEN MIN(d.estado_cuanti) = 'completado' AND MAX(d.estado_cuanti) = 'completado'
            THEN 'completadas'
            ELSE 'pendientes'
        END AS estado,
        COUNT(*) AS total
    FROM san_fact_solicitud_det d
    JOIN san_fact_solicitud_cab c ON d.codEnvio = c.codEnvio
    $joinWhere
    GROUP BY d.codEnvio, d.posSolicitud
";

if ($params) {
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conexion, $sql);
}

$completadas = 0;
$pendientes = 0;

while ($row = mysqli_fetch_assoc($result)) {
    if ($row['estado'] === 'completadas') $completadas += (int)$row['total'];
    if ($row['estado'] === 'pendientes') $pendientes += (int)$row['total'];
}

echo json_encode([
    'success' => true,
    'data' => ['completadas' => $completadas, 'pendientes' => $pendientes]
], JSON_UNESCAPED_UNICODE);
mysqli_close($conexion);