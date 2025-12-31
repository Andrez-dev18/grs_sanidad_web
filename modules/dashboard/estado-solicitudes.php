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
$where = "";
$params = [];
$types = "";

if ($year && ctype_digit($year)) {
    $where = "WHERE YEAR(fechaHoraRegistro) = ?";
    $params[] = (int)$year;
    $types = "i";
}

$sql = "
    SELECT 
        estado,
        COUNT(*) AS total
    FROM san_fact_solicitud_cab
    $where
    GROUP BY estado
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
    if ($row['estado'] === 'completado') $completadas = (int)$row['total'];
    if ($row['estado'] === 'pendiente') $pendientes = (int)$row['total'];
}

echo json_encode([
    'success' => true,
    'data' => ['completadas' => $completadas, 'pendientes' => $pendientes]
], JSON_UNESCAPED_UNICODE);
mysqli_close($conexion);