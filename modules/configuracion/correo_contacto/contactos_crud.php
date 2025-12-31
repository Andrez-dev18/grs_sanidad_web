<?php
session_start();
if (empty($_SESSION['active'])) exit(json_encode(['success' => false, 'message' => 'No autorizado']));

include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

$codigo = $_SESSION['usuario'];
if ($_GET['action'] ?? '' === 'list') {
    $stmt = mysqli_prepare($conexion, "SELECT id, codigo, contacto, correo FROM san_contacto_sanidad WHERE codigo = ?");
    mysqli_stmt_bind_param($stmt, 's', $codigo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    echo json_encode($rows);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action === 'create') {
    $stmt = mysqli_prepare($conexion, "INSERT INTO san_contacto_sanidad (codigo, contacto, correo) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sss', $codigo, $_POST['contacto'], $_POST['correo']);
    $ok = mysqli_stmt_execute($stmt);
    echo json_encode(['success' => $ok]);
} elseif ($action === 'edit') {
    $stmt = mysqli_prepare($conexion, "UPDATE san_contacto_sanidad SET contacto = ?, correo = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'ssi', $_POST['contacto'], $_POST['correo'], $_POST['id']);
    $ok = mysqli_stmt_execute($stmt);
    echo json_encode(['success' => $ok]);
} elseif ($action === 'delete') {
    $stmt = mysqli_prepare($conexion, "DELETE FROM san_contacto_sanidad WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_POST['id']);
    $ok = mysqli_stmt_execute($stmt);
    echo json_encode(['success' => $ok]);
}