<?php
header('Content-Type: application/json');
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

// Envíos con GRS
$enviosGRS = $conn->query("SELECT DISTINCT codEnvio FROM san_dim_historial_resultados WHERE ubicacion = 'GRS'");
$totalEnvios = $enviosGRS->num_rows;

if ($totalEnvios == 0) {
    echo json_encode(['pendientes' => 0, 'completados' => 0]);
    exit;
}

// Envíos que llegaron a Laboratorio (completados)
$completados = $conn->query("
    SELECT DISTINCT codEnvio 
    FROM san_dim_historial_resultados 
    WHERE ubicacion = 'Laboratorio'
")->num_rows;

$pendientes = $totalEnvios - $completados;

echo json_encode([
    'pendientes' => $pendientes,
    'completados' => $completados
]);