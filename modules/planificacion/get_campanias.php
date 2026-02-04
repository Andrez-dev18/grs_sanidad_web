<?php
session_start();
if (!$_SESSION['active'])
    exit();
include '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
$g = mysqli_real_escape_string($conn, $_GET['granja']);
$sql = "SELECT DISTINCT tcodint AS campania FROM regcencosgalpones WHERE tcencos = '$g'  ORDER BY tcodint";
$res = mysqli_query($conn, $sql);
$data = [];
while ($row = mysqli_fetch_assoc($res))
    $data[] = $row['campania'];
header('Content-Type: application/json');
echo json_encode($data);
?>