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

$lista = [['id' => 'SIN PROVEEDOR', 'text' => 'SIN PROVEEDOR']];

$sql = "SELECT codigo, nombre FROM ccte WHERE COALESCE(proveedor_programa, 0) = 1 AND TRIM(COALESCE(nombre, '')) <> '' ORDER BY nombre";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $lista[] = ['id' => (string)$row['codigo'], 'text' => $row['nombre']];
    }
}
echo json_encode(['success' => true, 'results' => $lista]);
