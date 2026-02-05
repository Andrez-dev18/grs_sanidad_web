<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$sql = "SELECT codigo, nombre FROM san_tipo_programa ORDER BY nombre";
$result = $conn->query($sql);
$lista = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lista[] = ['codigo' => (int)$row['codigo'], 'nombre' => $row['nombre']];
    }
}

echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
