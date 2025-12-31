<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}
include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

$codEnvio = $_GET['codEnvio'] ?? '';
if (!$codEnvio) {
    echo json_encode(['error' => 'codEnvio requerido']);
    exit();
}

$stmt = mysqli_prepare($conexion, "SELECT * FROM san_fact_solicitud_cab WHERE codEnvio = ?");
mysqli_stmt_bind_param($stmt, "s", $codEnvio);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo json_encode(['error' => 'Envío no encontrado']);
} else {
    echo json_encode($row);
}
mysqli_close($conexion);
?>