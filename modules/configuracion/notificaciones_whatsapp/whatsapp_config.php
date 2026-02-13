<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (!isset($_GET['action']) || $_GET['action'] === 'get')) {
    $codigo = $_SESSION['usuario'];
    $stmt = mysqli_prepare($conexion, "SELECT COALESCE(telefo, '') AS telefono FROM usuario WHERE codigo = ? LIMIT 1");
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al consultar usuario']);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 's', $codigo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result) ?: ['telefono' => ''];
    mysqli_stmt_close($stmt);
    header('Content-Type: application/json');
    echo json_encode($row);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefono = trim((string)($_POST['telefono'] ?? ''));

    $telefono = preg_replace('/[\s\-\(\)]/', '', $telefono);
    if (substr($telefono, 0, 1) === '+') {
        $telefono = substr($telefono, 1);
    }
    if (!preg_match('/^\d{9,15}$/', $telefono)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ingrese un número válido (solo dígitos, 9 a 15).']);
        exit;
    }

    $codigo = $_SESSION['usuario'];
    $stmt = mysqli_prepare($conexion, "UPDATE usuario SET telefo = ? WHERE codigo = ?");
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al preparar actualización de usuario.']);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'ss', $telefono, $codigo);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    if (!$ok) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al guardar número en usuario.']);
        exit;
    }
    if ($affected < 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el usuario.']);
        exit;
    }
    $message = 'Número guardado correctamente.';
    header('Content-Type: application/json');
    echo json_encode(['success' => $ok, 'message' => $message]);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Método no permitido']);
