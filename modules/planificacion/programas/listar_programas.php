<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Agrupar por codigo/nombre/codTipo/nomTipo y juntar edades
$sql = "SELECT codigo, nombre, codTipo, nomTipo, GROUP_CONCAT(edad ORDER BY edad ASC) AS edades 
        FROM san_plan_programa 
        GROUP BY codigo, nombre, codTipo, nomTipo 
        ORDER BY codigo DESC";
$result = $conn->query($sql);
$lista = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lista[] = [
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'],
            'codTipo' => (int)$row['codTipo'],
            'nomTipo' => $row['nomTipo'],
            'edades' => $row['edades']
        ];
    }
}

echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
