<?php
session_start();
if (!$_SESSION['active'])
    exit();
include '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
$sql = "SELECT codigo, nombre FROM ccos WHERE LENGTH(codigo)=3 AND swac='A' AND LEFT(codigo,1)='6' AND codigo NOT IN ('650','668','669','600') ORDER BY nombre";
$res = mysqli_query($conn, $sql);
$data = [];
while ($row = mysqli_fetch_assoc($res))
    $data[] = $row;
header('Content-Type: application/json');
echo json_encode($data);
?>