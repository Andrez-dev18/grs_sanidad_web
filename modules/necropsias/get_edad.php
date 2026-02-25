<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs/conexion.php';

$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos']);
    exit;
}

$codigo = trim((string)($_GET['codigo'] ?? ''));
$galpon = trim((string)($_GET['galpon'] ?? ''));
$fechaInput = trim((string)($_GET['fecha'] ?? ''));

if ($codigo === '' || $galpon === '') {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
    $conn->close();
    exit;
}

$fechaObj = DateTime::createFromFormat('Y-m-d', $fechaInput);
$fechaBase = $fechaObj ? $fechaObj->format('Y-m-d') : date('Y-m-d');
$prefijo = substr($codigo, 0, 3);

// Edad por fecha + granja + galpón.
// Se usa OR entre código completo y prefijo por compatibilidad con datos históricos.
$sql = "SELECT DATEDIFF(?, MIN(m.fec_ing)) + 1 AS edad
        FROM maes_zonas m
        WHERE m.tcodigo IN ('P0001001','P0001002')
          AND (m.tcencos = ? OR m.tcencos = ?)
          AND CAST(m.tcodint AS CHAR) = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparando consulta']);
    $conn->close();
    exit;
}

$stmt->bind_param('ssss', $fechaBase, $codigo, $prefijo, $galpon);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();
$conn->close();

$edad = null;
if ($row && isset($row['edad']) && $row['edad'] !== null) {
    $edad = (int)$row['edad'];
}

echo json_encode([
    'success' => true,
    'edad' => $edad,
    'fecha' => $fechaBase,
    'codigo' => $codigo,
    'galpon' => $galpon
]);

