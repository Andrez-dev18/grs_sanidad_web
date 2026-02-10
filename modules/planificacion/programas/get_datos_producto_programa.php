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
    echo json_encode(['success' => true, 'nomProducto' => '', 'codProveedor' => '', 'nomProveedor' => '', 'unidad' => '', 'dosis' => '', 'esVacuna' => false, 'descripcionVacuna' => '']);
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
    echo json_encode(['success' => true, 'nomProducto' => '', 'codProveedor' => '', 'nomProveedor' => '', 'unidad' => '', 'dosis' => '', 'esVacuna' => false, 'descripcionVacuna' => '']);
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

// Es vacuna si existe en san_rel_vacuna_enfermedad (codVacuna = código mitm, o codProducto si migrado)
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
    'nomProducto' => (string)($row['descri'] ?? ''),
    'codProveedor' => (string)($row['tcodprove'] ?? ''),
    'nomProveedor' => (string)($row['nombre_proveedor'] ?? ''),
    'unidad' => $unidad,
    'dosis' => (string)($row['dosis'] ?? ''),
    'esVacuna' => $esVacuna,
    'descripcionVacuna' => $descripcionVacuna
]);
$conn->close();
