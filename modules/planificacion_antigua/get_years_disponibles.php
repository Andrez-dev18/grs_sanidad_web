<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit();
}
include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion)
    exit();

// Obtener años distintos de la tabla cargapollo_proyeccion
$sql = "SELECT DISTINCT YEAR(fecha) AS anio
FROM cargapollo_proyeccion
WHERE fecha IS NOT NULL
  AND YEAR(fecha) >= YEAR(CURDATE())
ORDER BY anio DESC";
$res = mysqli_query($conexion, $sql);
$anios = [];
while ($row = mysqli_fetch_assoc($res)) {
    $anios[] = (int) $row['anio'];
}
header('Content-Type: application/json');
echo json_encode($anios);
?>