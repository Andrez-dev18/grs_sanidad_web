<?php
include_once '../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
$analisis = $_POST['analisis'] ?? null;
if (!$analisis) exit('[]');
$stmt = $conexion->prepare("SELECT codigo, tipo FROM san_dim_tiporesultado WHERE analisis = ?");
$stmt->bind_param("i", $analisis);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);