<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$codigo = trim((string)($_GET['codigo'] ?? ''));
if ($codigo === '') {
    echo json_encode(['success' => true, 'nomProducto' => '', 'codProveedor' => '', 'nomProveedor' => '', 'unidad' => '', 'dosis' => '', 'esVacuna' => false, 'descripcionVacuna' => '']);
    exit;
}


$stmt = $conn->prepare("SELECT m.codigo, m.descri, m.tcodprove, m.dosis, m.unidad, c.nombre AS nombre_proveedor 
                       FROM mitm m 
                       LEFT JOIN ccte c ON c.codigo = m.tcodprove 
                       WHERE m.codigo = ? LIMIT 1");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['success' => true, 'nomProducto' => '', 'codProveedor' => '', 'nomProveedor' => '', 'unidad' => '', 'dosis' => '', 'esVacuna' => false, 'descripcionVacuna' => '']);
    $conn->close();
    exit;
}

$tcodprove = $row['tcodprove'] ?? null;
$codProveedor = ($tcodprove === null || trim((string)$tcodprove) === '') ? '' : trim((string)$tcodprove);
$unidad = trim((string)($row['unidad'] ?? ''));
$nomProveedor = trim((string)($row['nombre_proveedor'] ?? ''));

$esVacuna = false;
$descripcionVacuna = '';
$chkVac = @$conn->query("SHOW COLUMNS FROM san_rel_vacuna_enfermedad LIKE 'codVacuna'");
$colVacuna = ($chkVac && $chkVac->fetch_assoc()) ? 'codVacuna' : 'codProducto';
$stE = $conn->prepare("SELECT e.nom_enf FROM san_rel_vacuna_enfermedad r INNER JOIN tenfermedades e ON e.cod_enf = r.codEnfermedad WHERE r." . $colVacuna . " = ? ORDER BY e.nom_enf");
if ($stE) {
    $stE->bind_param("s", $codigo);
    $stE->execute();
    $rE = $stE->get_result();
    $nombres = [];
    while ($re = $rE->fetch_assoc()) {
        $nombres[] = trim($re['nom_enf'] ?? '');
    }
    $stE->close();
    if (count($nombres) > 0) {
        $esVacuna = true;
        $descripcionVacuna = implode(', ', $nombres);
    }
}

echo json_encode([
    'success' => true,
    'nomProducto' => trim((string)($row['descri'] ?? '')),
    'codProveedor' => $codProveedor,
    'nomProveedor' => $nomProveedor,
    'unidad' => $unidad,
    'dosis' => trim((string)($row['dosis'] ?? '')),
    'esVacuna' => $esVacuna,
    'descripcionVacuna' => $descripcionVacuna
]);
$conn->close();
