<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode([]);
    exit();
}
mysqli_set_charset($conn, 'utf8');

$planId = trim($_GET['planId'] ?? $_GET['cabId'] ?? $_GET['cab_id'] ?? '');
if ($planId === '') {
    echo json_encode([]);
    exit();
}

$tblPlan = $conn->query("SHOW TABLES LIKE 'san_plan'");
$usaSanPlan = ($tblPlan && $tblPlan->num_rows > 0);

if ($usaSanPlan) {
    $stmt = $conn->prepare("SELECT id, fecProgramacion, codCronograma, nomCronograma, granja, nomGranja, campania, galpon, edad, codRef, codMuestra, nomMuestra, lugarToma, fecToma, responsable, nMacho, nHembra, codDestino, nomDestino, estado, usuarioRegistrador, observacion FROM san_plan WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('s', $planId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $data = $row ? [$row] : [];
        $stmt->close();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

$stmt = $conn->prepare("SELECT d.id, c.fecProgramacion, c.codCronograma, c.nomCronograma, c.granja, c.nomGranja, c.campania, c.galpon, c.edad, c.codRef, d.codMuestra, d.nomMuestra, c.lugarToma, c.fecToma, c.responsable, d.nMacho, d.nHembra, c.codDestino, c.nomDestino, c.estado, c.usuarioRegistrador, d.observacion
    FROM san_plan_det d INNER JOIN san_plan_cab c ON c.id = d.cabId WHERE d.cabId = ? ORDER BY d.fechaHoraRegistro DESC");
if ($stmt) {
    $stmt->bind_param('s', $planId);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) $data[] = $row;
    $stmt->close();
}
echo json_encode($data ?? [], JSON_UNESCAPED_UNICODE);
