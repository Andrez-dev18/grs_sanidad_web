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
$granja = str_pad(trim($_GET['granja'] ?? ''), 3, '0', STR_PAD_LEFT);
if (strlen($granja) > 3) {
    $granja = substr($granja, 0, 3);
}
$galpon = trim($_GET['galpon'] ?? '');
if ($granja === '' || $galpon === '') {
    echo json_encode([]);
    exit;
}
// Campaña = últimos 3 dígitos de tcencos; granja = LEFT(tcencos,3), galpon = tcodint
$stmt = $conn->prepare("SELECT DISTINCT RIGHT(tcencos, 3) AS campania FROM cargapollo_proyeccion WHERE LEFT(tcencos, 3) = ? AND tcodint = ? ORDER BY campania");
$stmt->bind_param("ss", $granja, $galpon);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = ['campania' => $row['campania']];
}
$stmt->close();
$conn->close();
echo json_encode($data);
