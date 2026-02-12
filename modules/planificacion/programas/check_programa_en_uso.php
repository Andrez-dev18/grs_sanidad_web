<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'enUso' => false, 'message' => 'No autorizado']);
    exit;
}

$codigo = trim((string)($_GET['codigo'] ?? $_POST['codigo'] ?? ''));
if ($codigo === '') {
    echo json_encode(['success' => true, 'enUso' => false]);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'enUso' => false, 'message' => 'Error de conexión']);
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
$conn->close();
echo json_encode(['success' => true, 'enUso' => $enUso]);
