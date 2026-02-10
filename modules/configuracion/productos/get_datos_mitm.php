<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$codigo = trim((string)($_GET['codigo'] ?? ''));
if ($codigo === '') {
    echo json_encode(['success' => true, 'nomProducto' => '', 'codProveedor' => '', 'nomProveedor' => '', 'unidad' => '', 'dosis' => '']);
    exit;
}

$stmt = $conn->prepare("SELECT m.codigo, m.descri, m.tcodprove, m.dosis, c.nombre AS nombre_proveedor 
                       FROM mitm m 
                       LEFT JOIN ccte c ON c.codigo = m.tcodprove 
                       WHERE m.codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['success' => true, 'nomProducto' => '', 'codProveedor' => '', 'nomProveedor' => '', 'unidad' => '', 'dosis' => '']);
    $conn->close();
    exit;
}

$unidad = '';
$chkUnidad = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'unidad'");
if ($chkUnidad && $chkUnidad->fetch_assoc()) {
    $stU = $conn->prepare("SELECT unidad FROM mitm WHERE codigo = ? LIMIT 1");
    if ($stU) {
        $stU->bind_param("s", $codigo);
        $stU->execute();
        $rU = $stU->get_result();
        if ($rU && $ru = $rU->fetch_assoc()) $unidad = trim((string)($ru['unidad'] ?? ''));
        $stU->close();
    }
}

$codEnfermedades = [];
$chkRel = @$conn->query("SHOW TABLES LIKE 'san_rel_vacuna_enfermedad'");
if ($chkRel && $chkRel->fetch_assoc()) {
    $chkVac = @$conn->query("SHOW COLUMNS FROM san_rel_vacuna_enfermedad LIKE 'codVacuna'");
    $colVacuna = ($chkVac && $chkVac->fetch_assoc()) ? 'codVacuna' : 'codProducto';
    $sqlRel = "SELECT codEnfermedad FROM san_rel_vacuna_enfermedad WHERE " . $colVacuna . " = ?";
    $stRel = $conn->prepare($sqlRel);
    if ($stRel) {
        $stRel->bind_param("s", $codigo);
        $stRel->execute();
        $resRel = $stRel->get_result();
        while ($r = $resRel->fetch_assoc()) $codEnfermedades[] = (int)$r['codEnfermedad'];
        $stRel->close();
    }
}
$es_vacuna = count($codEnfermedades) > 0 ? 1 : 0;

echo json_encode([
    'success' => true,
    'nomProducto' => (string)($row['descri'] ?? ''),
    'codProveedor' => (string)($row['tcodprove'] ?? ''),
    'nomProveedor' => (string)($row['nombre_proveedor'] ?? ''),
    'unidad' => $unidad,
    'dosis' => (string)($row['dosis'] ?? ''),
    'es_vacuna' => $es_vacuna,
    'codEnfermedades' => $codEnfermedades
]);
$conn->close();
