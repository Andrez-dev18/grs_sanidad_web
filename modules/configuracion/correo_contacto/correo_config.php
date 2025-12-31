<?php
session_start();
if (empty($_SESSION['active'])) exit(json_encode(['success' => false, 'message' => 'No autorizado']));

include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'get') {
    $codigo = $_SESSION['usuario'];
    $stmt = mysqli_prepare($conexion, "SELECT correo, password FROM san_correo_sanidad WHERE codigo = ?");
    mysqli_stmt_bind_param($stmt, 's', $codigo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result) ?: ['correo' => '', 'password' => ''];
    echo json_encode($row);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Correo inválido']);
        exit;
    }

    // Upsert: si existe, actualiza; si no, inserta
    $codigo = $_SESSION['usuario'];
    $password64 = base64_encode($password);
    $stmt = mysqli_prepare($conexion, "REPLACE INTO san_correo_sanidad (codigo, correo, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sss', $codigo, $correo, $password64);
    $ok = mysqli_stmt_execute($stmt);
    echo json_encode(['success' => $ok, 'message' => $ok ? '' : 'Error al guardar']);
    exit;
}