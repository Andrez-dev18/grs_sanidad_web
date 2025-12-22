<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

header('Content-Type: application/json');

$codigoEnvio = $_GET['codigoEnvio'] ?? '';
$posicion = $_GET['posicion'] ?? '';

if ($codigoEnvio === '' || $posicion === '') {
    echo json_encode(['tieneResultados' => false]);
    exit;
}

$sql = "
    SELECT COUNT(*) as total
    FROM san_fact_resultado_analisis
    WHERE codEnvio = ?
      AND posSolicitud = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $codigoEnvio, $posicion);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$tieneResultados = ($row['total'] > 0);

echo json_encode(['tieneResultados' => $tieneResultados]);

$stmt->close();
$conn->close();
?>