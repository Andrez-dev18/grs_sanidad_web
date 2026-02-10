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

$lista = [];
$res = $conn->query("SELECT codigo, nombre FROM ccte WHERE TRIM(COALESCE(nombre, '')) <> '' ORDER BY nombre ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $lista[] = ['codigo' => (string)$row['codigo'], 'nombre' => (string)$row['nombre']];
    }
}

echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
