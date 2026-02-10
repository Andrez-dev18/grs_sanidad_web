<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode([]);
    exit;
}
$sql = "SELECT codigo, nombre FROM ccos WHERE LENGTH(codigo)=3 AND swac='A' AND LEFT(codigo,1)='6' AND codigo NOT IN ('650','668','669','600') ORDER BY nombre";
$res = $conn->query($sql);
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = ['codigo' => $row['codigo'], 'nombre' => $row['nombre']];
    }
}
echo json_encode($data);
$conn->close();
