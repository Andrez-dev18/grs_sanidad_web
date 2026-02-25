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

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
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
// Eliminar también el despliegue asociado
$chkDespliegue = @$conn->query("SHOW TABLES LIKE 'san_cronograma_despliegue'");
if ($chkDespliegue && $chkDespliegue->num_rows > 0) {
    $stmtDesp = $conn->prepare("DELETE FROM san_cronograma_despliegue WHERE numCronograma = ?");
    if ($stmtDesp) {
        $stmtDesp->bind_param("i", $numCronograma);
        @$stmtDesp->execute();
        $stmtDesp->close();
    }
}
$conn->close();
echo json_encode($ok ? ['success' => true, 'message' => 'Asignación eliminada.'] : ['success' => false, 'message' => 'Error al eliminar.']);
