<?php
session_start();
if (!$_SESSION['active'])
    exit();
include '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
$g = mysqli_real_escape_string($conn, $_GET['granja']);
// Campaña: últimos 3 dígitos del centro de costo (tcencos = granja+campaña)
$sql = "SELECT DISTINCT RIGHT(tcencos,3) AS campania 
        FROM cargapollo_proyeccion 
        WHERE LEFT(tcencos,3) = '$g' 
        ORDER BY campania";
$res = mysqli_query($conn, $sql);
$data = [];
while ($row = mysqli_fetch_assoc($res))
    $data[] = $row['campania'];
header('Content-Type: application/json');
echo json_encode($data);
?>