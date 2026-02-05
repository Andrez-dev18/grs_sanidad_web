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
$granja = trim($_GET['granja'] ?? '');
if (strlen($granja) !== 3) {
    echo json_encode([]);
    exit;
}
// Campaña = últimos 3 dígitos del codigo (tcencos) en cargapollo_proyeccion para esta granja
$stmt = $conn->prepare("SELECT DISTINCT RIGHT(tcencos, 3) AS campania FROM cargapollo_proyeccion WHERE LEFT(tcencos, 3) = ? ORDER BY campania");
$stmt->bind_param("s", $granja);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = ['campania' => $row['campania']];
}
$stmt->close();
$conn->close();
echo json_encode($data);
