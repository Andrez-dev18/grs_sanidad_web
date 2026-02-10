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
    echo json_encode(['success' => true, 'codEnfermedades' => [], 'descripcion' => '']);
    exit;
}

$codEnfermedades = [];
$descripcion = '';

// Enfermedades desde san_rel_vacuna_enfermedad: codVacuna = código mitm, o codProducto si migrado
$chkVac = @$conn->query("SHOW COLUMNS FROM san_rel_vacuna_enfermedad LIKE 'codVacuna'");
$colVacuna = ($chkVac && $chkVac->fetch_assoc()) ? 'codVacuna' : 'codProducto';
$st = $conn->prepare("SELECT e.cod_enf, e.nom_enf FROM san_rel_vacuna_enfermedad r INNER JOIN tenfermedades e ON e.cod_enf = r.codEnfermedad WHERE r." . $colVacuna . " = ? ORDER BY e.nom_enf");
if ($st) {
    $st->bind_param("s", $codigo);
    $st->execute();
    $res = $st->get_result();
    $nombres = [];
    while ($row = $res->fetch_assoc()) {
        $codEnfermedades[] = (int)$row['cod_enf'];
        $nombres[] = trim($row['nom_enf'] ?? '');
    }
    $st->close();
    if (count($nombres) > 0) $descripcion = implode(', ', $nombres);
}

echo json_encode([
    'success' => true,
    'codEnfermedades' => $codEnfermedades,
    'descripcion' => $descripcion
]);
$conn->close();
