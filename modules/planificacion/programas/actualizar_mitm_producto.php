<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$codigo = trim((string)($_POST['codigo'] ?? ''));
$tcodprove = trim((string)($_POST['tcodprove'] ?? ''));
$unidad = trim((string)($_POST['unidad'] ?? ''));
$dosis = trim((string)($_POST['dosis'] ?? ''));

if ($codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Código de producto requerido.']);
    exit;
}

$chkUnidad = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'unidad'");
$tieneUnidad = $chkUnidad && $chkUnidad->fetch_assoc();

if ($tieneUnidad) {
    $stmt = $conn->prepare("UPDATE mitm SET tcodprove = ?, unidad = ?, dosis = ? WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("ssss", $tcodprove, $unidad, $dosis, $codigo) && $stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
    }
} else {
    $stmt = $conn->prepare("UPDATE mitm SET tcodprove = ?, dosis = ? WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("sss", $tcodprove, $dosis, $codigo) && $stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
    }
}
$conn->close();
