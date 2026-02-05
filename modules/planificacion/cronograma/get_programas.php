<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
$sql = "SELECT codigo, nombre FROM san_plan_programa GROUP BY codigo, nombre ORDER BY codigo DESC";
$res = $conn->query($sql);
$lista = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $lista[] = [
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'],
            'label' => $row['codigo'] . ' - ' . $row['nombre']
        ];
    }
}
echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
