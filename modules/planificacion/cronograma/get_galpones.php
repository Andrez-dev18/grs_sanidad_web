<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
$codigo = $_GET['codigo'] ?? '';
if (strlen($codigo) < 3) {
    echo json_encode(['success' => true, 'data' => []]);
    $conn->close();
    exit;
}
$prefijo = substr($codigo, 0, 3);
$stmt = $conn->prepare("
    SELECT
        TRIM(tcodint) AS tcodint,
        MAX(TRIM(tnomcen)) AS tnomcen
    FROM regcencosgalpones
    WHERE LEFT(TRIM(tcencos), 3) = ?
      AND TRIM(tcodint) <> ''
    GROUP BY TRIM(tcodint)
    ORDER BY TRIM(tcodint) ASC
");
$stmt->bind_param("s", $prefijo);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = ['galpon' => $row['tcodint'], 'nombre' => trim($row['tnomcen'])];
}
$stmt->close();
$conn->close();
echo json_encode(['success' => true, 'data' => $data]);
