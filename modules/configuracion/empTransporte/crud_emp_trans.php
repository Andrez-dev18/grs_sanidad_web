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
$nombre = trim($_POST['nombre'] ?? '');
$codigo = isset($_POST['codigo']) ? (int) $_POST['codigo'] : 0;

if ($action === 'create') {
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'Nombre es obligatorio.']);
        exit();
    }
    $stmt = $conexion->prepare("INSERT INTO san_dim_emptrans (nombre) VALUES (?)");
    if ($stmt && $stmt->bind_param("s", $nombre) && $stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Empresa creada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear la empresa.']);
    }
} elseif ($action === 'update') {
    if (empty($nombre) || !$codigo) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
        exit();
    }
    $stmt = $conexion->prepare("UPDATE san_dim_emptrans SET nombre = ? WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("si", $nombre, $codigo) && $stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Empresa actualizada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
    }
} elseif ($action === 'delete') {
    if (!$codigo) {
        echo json_encode(['success' => false, 'message' => 'Código no válido.']);
        exit();
    }
    // Opcional: verificar si está en uso antes de eliminar
    $stmt = $conexion->prepare("DELETE FROM san_dim_emptrans WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("i", $codigo) && $stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Empresa eliminada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
}
?>