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
$sql = "SELECT codigo, codProducto, descripcion FROM san_dim_vacuna ORDER BY descripcion";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $texto = !empty(trim($row['descripcion'] ?? '')) ? trim($row['descripcion']) : (trim($row['codProducto'] ?? '') ?: 'Vacuna #' . $row['codigo']);
        $lista[] = ['id' => (int)$row['codigo'], 'text' => $texto];
    }
}
echo json_encode(['success' => true, 'results' => $lista]);
