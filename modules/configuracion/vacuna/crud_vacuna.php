<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit();
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión.']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$codigo = isset($_POST['codigo']) ? (int)$_POST['codigo'] : 0;
$codProducto = trim((string)($_POST['codProducto'] ?? ''));
$descripcion = trim((string)($_POST['descripcion'] ?? ''));
$codigo_actual = isset($_POST['codigo_actual']) ? (int)$_POST['codigo_actual'] : 0;

if ($action === 'create') {
    if ($codigo <= 0 || $descripcion === '') {
        echo json_encode(['success' => false, 'message' => 'Código y descripción son obligatorios.']);
        exit();
    }
    $stmt = $conexion->prepare("INSERT INTO san_dim_vacuna (codigo, codProducto, descripcion) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conexion->error]);
        exit();
    }
    $stmt->bind_param("iss", $codigo, $codProducto, $descripcion);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Vacuna registrada correctamente.']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => $conexion->errno === 1062 ? 'Ya existe una vacuna con ese código.' : 'Error: ' . $conexion->error]);
    }
} elseif ($action === 'update') {
    if ($codigo_actual <= 0 || $descripcion === '') {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
        exit();
    }
    $stmt = $conexion->prepare("UPDATE san_dim_vacuna SET codProducto = ?, descripcion = ? WHERE codigo = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conexion->error]);
        exit();
    }
    $stmt->bind_param("ssi", $codProducto, $descripcion, $codigo_actual);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Vacuna actualizada correctamente.']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
    }
} elseif ($action === 'delete') {
    if ($codigo_actual <= 0) {
        echo json_encode(['success' => false, 'message' => 'Código no válido.']);
        exit();
    }
    $stmt = $conexion->prepare("DELETE FROM san_dim_vacuna WHERE codigo = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conexion->error]);
        exit();
    }
    $stmt->bind_param("i", $codigo_actual);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Vacuna eliminada.']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
}
