<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode([]);
    exit();
}
mysqli_set_charset($conn, 'utf8');

$res = $conn->query("SELECT codigo, nombre FROM san_dim_cronograma ORDER BY nombre");
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
