<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit;
}

include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

$usuario = $_SESSION['usuario'];
$stmt = mysqli_prepare($conexion, "SELECT contacto, correo FROM san_contacto_sanidad WHERE codigo = ?");
mysqli_stmt_bind_param($stmt, 's', $usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$contactos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $contactos[] = $row;
}
echo json_encode($contactos);
mysqli_close($conexion);
?>