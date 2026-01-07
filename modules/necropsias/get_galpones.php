<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$codigo = $_GET['codigo'] ?? '';
if (strlen($codigo) < 3) {
    echo json_encode([]);
    exit;
}

$prefijo = substr($codigo, 0, 3); // Primeros 3 dígitos

$sql = "SELECT tcodint, tnomcen 
        FROM regcencosgalpones 
        WHERE tcencos = ? 
        ORDER BY tcodint ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $prefijo);
$stmt->execute();
$result = $stmt->get_result();

$galpones = [];
while ($row = $result->fetch_assoc()) {
    $galpones[] = [
        'galpon' => $row['tcodint'],
        'nombre' => trim($row['tnomcen'])
    ];
}

echo json_encode($galpones);
$stmt->close();
$conn->close();
?>