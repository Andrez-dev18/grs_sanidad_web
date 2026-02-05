<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
$sql = "SELECT granja, campania, galpon, codPrograma, nomPrograma, GROUP_CONCAT(fecha ORDER BY fecha ASC) AS fechas 
        FROM san_plan_cronograma 
        GROUP BY granja, campania, galpon, codPrograma, nomPrograma 
        ORDER BY granja, campania, galpon, codPrograma";
$res = $conn->query($sql);
$lista = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $lista[] = [
            'granja' => $row['granja'],
            'campania' => $row['campania'],
            'galpon' => $row['galpon'],
            'codPrograma' => $row['codPrograma'],
            'nomPrograma' => $row['nomPrograma'],
            'fechas' => $row['fechas']
        ];
    }
}
echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
