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

$codigo_actual = trim((string)($_GET['codigo_actual'] ?? ''));

// Solo registros con nombre no vacío. Crear: no proveedores; Editar: no proveedores + el actual
if ($codigo_actual !== '') {
    $stmt = $conn->prepare("SELECT codigo, nombre FROM ccte WHERE TRIM(COALESCE(nombre, '')) <> '' AND (COALESCE(proveedor_programa, 0) = 0 OR codigo = ?) ORDER BY nombre");
    $stmt->bind_param("s", $codigo_actual);
} else {
    $stmt = $conn->prepare("SELECT codigo, nombre FROM ccte WHERE TRIM(COALESCE(nombre, '')) <> '' AND COALESCE(proveedor_programa, 0) = 0 ORDER BY nombre");
}
if (!$stmt || !$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al cargar opciones']);
    exit;
}
$result = $stmt->get_result();
$lista = [];
while ($row = $result->fetch_assoc()) {
    if (trim($row['nombre'] ?? '') === '') continue;
    $lista[] = ['codigo' => (string)$row['codigo'], 'nombre' => $row['nombre']];
}
$stmt->close();
echo json_encode(['success' => true, 'data' => $lista]);
?>
