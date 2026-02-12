<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$numCronograma = isset($_POST['numCronograma']) ? (int)$_POST['numCronograma'] : (isset($_GET['numCronograma']) ? (int)$_GET['numCronograma'] : 0);
if ($numCronograma <= 0) {
    echo json_encode(['success' => false, 'message' => 'Falta número de cronograma']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM san_fact_cronograma WHERE numCronograma = ?");
if (!$stmt) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al preparar consulta.']);
    exit;
}
$stmt->bind_param("i", $numCronograma);
$ok = $stmt->execute();
$stmt->close();
$conn->close();
echo json_encode($ok ? ['success' => true, 'message' => 'Cronograma eliminado.'] : ['success' => false, 'message' => 'Error al eliminar.']);
