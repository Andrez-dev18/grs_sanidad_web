<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

header('Content-Type: application/json');

$codEnvio = $_POST['codEnvio'] ?? '';
$posSolicitud = intval($_POST['posSolicitud'] ?? 0);

if ($codEnvio === '' || $posSolicitud === 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
    exit;
}

$stmt = $conn->prepare("
    SELECT accion, comentario, tipo_analisis, usuario, fechaHoraRegistro
    FROM san_dim_historial_resultados
    WHERE codEnvio = ? AND posSolicitud = ?
    ORDER BY fechaHoraRegistro DESC
");
$stmt->bind_param("si", $codEnvio, $posSolicitud);
$stmt->execute();
$res = $stmt->get_result();

$historial = [];
while ($row = $res->fetch_assoc()) {
    $historial[] = $row;
}

echo json_encode([
    'success' => true,
    'historial' => $historial
]);

$stmt->close();
$conn->close();
?>