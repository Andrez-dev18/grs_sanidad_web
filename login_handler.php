<?php
session_start();
include_once '../conexion_grs_joya/conexion.php';
include_once 'historial_acciones.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$usuario = trim($_POST['usuario'] ?? '');
$clave = trim($_POST['clave'] ?? '');
$gps = $_POST['gps'] ?? null;

if (empty($usuario) || empty($clave)) {
    echo json_encode(['success' => false, 'message' => 'Ingrese su usuario y contraseña']);
    exit();
}

$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit();
}

$sqlUser = "SELECT u.codigo, u.nombre, u.estado 
            FROM usuario u 
            WHERE u.codigo = ?";

$stmtUser = $conexion->prepare($sqlUser);
if (!$stmtUser) {
    echo json_encode(['success' => false, 'message' => 'Error interno al verificar usuario']);
    mysqli_close($conexion);
    exit();
}

$stmtUser->bind_param("s", $usuario);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$userData = $resultUser->fetch_assoc();
$stmtUser->close();

if (!$userData) {
    echo json_encode(['success' => false, 'message' => 'El usuario no está registrado']);
    mysqli_close($conexion);
    exit();
}

if ($userData['estado'] !== 'A') {
    // Usuario existe pero está inactivo
    echo json_encode(['success' => false, 'message' => 'El usuario está inactivo']);
    mysqli_close($conexion);
    exit();
}

// 2. Verificar CONTRASEÑA (usando la lógica de cifrado que ya tienes)
$sqlPass = "SELECT u.codigo, u.nombre
            FROM usuario u
            JOIN conempre c ON c.epre = 'RS'
            WHERE u.codigo = ?
            AND u.password = LEFT(AES_ENCRYPT(?, c.enom), 8)
            AND u.estado = 'A'";

$stmtPass = $conexion->prepare($sqlPass);
if (!$stmtPass) {
    echo json_encode(['success' => false, 'message' => 'Error interno al verificar contraseña']);
    mysqli_close($conexion);
    exit();
}

$stmtPass->bind_param("ss", $usuario, $clave);
$stmtPass->execute();
$resultPass = $stmtPass->get_result();
$loginData = $resultPass->fetch_assoc();
$stmtPass->close();
mysqli_close($conexion);

if ($loginData) {
    $_SESSION['active'] = true;
    $_SESSION['usuario'] = $loginData['codigo'];
    $_SESSION['nombre'] = $loginData['nombre'];
    registrarAccionLoginLogout("LOGIN", $loginData['codigo'], $loginData['nombre'], $gps);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
}