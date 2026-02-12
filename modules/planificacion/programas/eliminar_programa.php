<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$codigo = trim((string)($_GET['codigo'] ?? $_POST['codigo'] ?? ''));
if ($codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Falta código de programa']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$enUso = false;
$chk = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
if ($chk && $chk->num_rows > 0) {
    $stmt = $conn->prepare("SELECT 1 FROM san_fact_cronograma WHERE codPrograma = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $res = $stmt->get_result();
        $enUso = $res && $res->num_rows > 0;
        $stmt->close();
    }
}
if ($enUso) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'No se puede eliminar: el programa ya ha sido asignado en cronogramas.']);
    exit;
}

$stmtDet = $conn->prepare("DELETE FROM san_fact_programa_det WHERE codPrograma = ?");
if ($stmtDet) {
    $stmtDet->bind_param("s", $codigo);
    $stmtDet->execute();
    $stmtDet->close();
}
$stmtCab = $conn->prepare("DELETE FROM san_fact_programa_cab WHERE codigo = ?");
if ($stmtCab) {
    $stmtCab->bind_param("s", $codigo);
    $ok = $stmtCab->execute();
    $stmtCab->close();
    $conn->close();
    echo json_encode($ok ? ['success' => true, 'message' => 'Programa eliminado.'] : ['success' => false, 'message' => 'Error al eliminar.']);
} else {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al preparar consulta.']);
}
