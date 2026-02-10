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
$sql = "SELECT codigo, nombre FROM san_dim_laboratorio_vacuna ORDER BY nombre";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $lista[] = ['id' => (int)$row['codigo'], 'text' => $row['nombre']];
    }
}
echo json_encode(['success' => true, 'results' => $lista]);
