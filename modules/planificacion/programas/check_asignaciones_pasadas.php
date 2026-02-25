<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado', 'tieneAsignacionesPasadas' => false]);
    exit;
}
include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión', 'tieneAsignacionesPasadas' => false]);
    exit;
}
$codigo = trim($_GET['codigo'] ?? $_POST['codigo'] ?? '');
if ($codigo === '') {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Falta código', 'tieneAsignacionesPasadas' => false]);
    exit;
}
$chk = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
if (!$chk || $chk->num_rows === 0) {
    $conn->close();
    echo json_encode(['success' => true, 'tieneAsignacionesPasadas' => false, 'tieneAsignacionesFuturas' => false]);
    exit;
}
$chkCol = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fechaEjecucion'");
if (!$chkCol || $chkCol->num_rows === 0) {
    $conn->close();
    echo json_encode(['success' => true, 'tieneAsignacionesPasadas' => false, 'tieneAsignacionesFuturas' => false]);
    exit;
}
$hoy = date('Y-m-d');
$st = $conn->prepare("SELECT 1 FROM san_fact_cronograma WHERE codPrograma = ? AND DATE(fechaEjecucion) < ? LIMIT 1");
$st->bind_param("ss", $codigo, $hoy);
$st->execute();
$res = $st->get_result();
$tienePasadas = ($res && $res->num_rows > 0);
$st->close();
$st2 = $conn->prepare("SELECT 1 FROM san_fact_cronograma WHERE codPrograma = ? AND DATE(fechaEjecucion) >= ? LIMIT 1");
$st2->bind_param("ss", $codigo, $hoy);
$st2->execute();
$res2 = $st2->get_result();
$tieneFuturas = ($res2 && $res2->num_rows > 0);
$st2->close();
$conn->close();
echo json_encode(['success' => true, 'tieneAsignacionesPasadas' => (bool)$tienePasadas, 'tieneAsignacionesFuturas' => (bool)$tieneFuturas]);