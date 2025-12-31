<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión'], JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario = $_GET['usuario'];
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