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
$codigo = $_GET['codigo'] ?? '';
if (strlen($codigo) < 3) {
    echo json_encode([]);
    exit;
}
$prefijo = substr($codigo, 0, 3);
$stmt = $conn->prepare("SELECT tcodint, tnomcen FROM regcencosgalpones WHERE tcencos = ? ORDER BY tcodint ASC");
$stmt->bind_param("s", $prefijo);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = ['galpon' => $row['tcodint'], 'nombre' => trim($row['tnomcen'])];
}
$stmt->close();
$conn->close();
echo json_encode($data);
