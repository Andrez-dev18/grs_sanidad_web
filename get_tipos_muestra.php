<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit();
}
//ruta relativa a la conexion
include_once 'conexion_grs_joya\conexion.php';
$conexion = conectar_sanidad();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit();
}

$result = mysqli_query($conexion, "SELECT codigo, nombre FROM com_tipo_muestra ORDER BY codigo");
$tipos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tipos[] = $row;
}
echo json_encode($tipos);
?>