<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit();
}

$codRef = trim($_GET['codRef'] ?? '');
$fecToma = trim($_GET['fecToma'] ?? '');
$codMuestra = $_GET['codMuestra'] ?? '';

if ($codRef === '' || $fecToma === '' || $codMuestra === '' || !preg_match('/^\d+$/', (string)$codMuestra)) {
    echo json_encode(['matched' => false, 'total' => 0, 'message' => 'Parámetros incompletos'], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt = $conn->prepare("SELECT id, nomMuestra, nomCronograma, responsable FROM san_plan_det WHERE codRef = ? AND fecToma = ? AND (codMuestra = ? OR codMuestra IS NULL) AND estado = 'PLANIFICADO' ORDER BY fecProgramacion DESC LIMIT 1");
if ($stmt) {
    $stmt->bind_param('ssi', $codRef, $fecToma, $codMuestra);
    $stmt->execute();
    $r = $stmt->get_result();
    $row = $r->fetch_assoc();
    $stmt->close();
    echo json_encode([
        'matched' => (bool)$row,
        'total' => $row ? 1 : 0,
        'nomMuestra' => $row['nomMuestra'] ?? '',
        'cronograma' => $row['nomCronograma'] ?? '',
        'responsable' => $row['responsable'] ?? '',
        'detId' => $row['id'] ?? '',
        'cabId' => $row['id'] ?? '',
        'planId' => $row['id'] ?? ''
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode(['matched' => false, 'total' => 0, 'message' => 'Error'], JSON_UNESCAPED_UNICODE);
