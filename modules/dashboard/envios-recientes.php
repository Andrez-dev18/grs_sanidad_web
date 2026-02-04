<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    http_response_code(500);
    echo json_encode([]);
    exit();
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$sql = "
    SELECT
        codEnvio,
        fecEnvio,
        nomLab,
        estado
    FROM san_fact_solicitud_cab
    WHERE YEAR(fecEnvio) = ?
    ORDER BY codEnvio DESC
    LIMIT 10
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $year);
$stmt->execute();
$result = $stmt->get_result();

$envios = [];
while ($row = $result->fetch_assoc()) {
    $row['estado'] = strtolower(trim($row['estado'] ?? 'pendiente'));
    $envios[] = $row;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($envios);