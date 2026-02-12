<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$numCronograma = isset($input['numCronograma']) ? (int)$input['numCronograma'] : 0;
$codPrograma = trim($input['codPrograma'] ?? '');
$nomPrograma = trim($input['nomPrograma'] ?? '');
$zona = trim($input['zona'] ?? '');
$subzonaRaw = $input['subzona'] ?? '';
$subzona = is_numeric($subzonaRaw) ? (int)$subzonaRaw : (int)(is_string($subzonaRaw) ? trim(explode(',', $subzonaRaw)[0]) : 0);

if ($numCronograma <= 0 || $codPrograma === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan numCronograma o código de programa.']);
    exit;
}

$usuario = $_SESSION['usuario'] ?? 'WEB';

$chkNom = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneNomGranja = $chkNom && $chkNom->num_rows > 0;
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;

$edadPrograma = null;
if ($tieneEdad) {
    $stEdad = $conn->prepare("SELECT edad FROM san_fact_programa_det WHERE codPrograma = ? AND edad IS NOT NULL AND edad > 0 ORDER BY edad ASC LIMIT 1");
    if ($stEdad) {
        $stEdad->bind_param("s", $codPrograma);
        $stEdad->execute();
        $rEdad = $stEdad->get_result();
        if ($rEdad && $row = $rEdad->fetch_assoc()) $edadPrograma = (int)$row['edad'];
        $stEdad->close();
    }
}

$items = $input['items'] ?? null;
if (!is_array($items) || empty($items)) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Faltan items del cronograma.']);
    exit;
}

$del = $conn->prepare("DELETE FROM san_fact_cronograma WHERE numCronograma = ?");
if (!$del) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al preparar DELETE.']);
    exit;
}
$del->bind_param("i", $numCronograma);
$del->execute();
$del->close();

$cols = "granja, campania, galpon, codPrograma, nomPrograma, fechaCarga, fechaEjecucion, usuarioRegistro, zona, subzona, numCronograma, usuarioModificacion, fechaHoraModificacion";
$placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()";
$types = "sssssssssis";
if ($tieneNomGranja) {
    $cols .= ", nomGranja";
    $placeholders .= ", ?";
    $types .= "s";
}
if ($tieneEdad) {
    $cols .= ", edad";
    $placeholders .= ", ?";
    $types .= "i";
}
$stmt = $conn->prepare("INSERT INTO san_fact_cronograma ($cols) VALUES ($placeholders)");
if (!$stmt) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error prepare INSERT: ' . $conn->error]);
    exit;
}

$total = 0;
foreach ($items as $it) {
    $granja = substr(trim((string)($it['granja'] ?? '')), 0, 3);
    $nomGranja = trim((string)($it['nomGranja'] ?? ''));
    $galpon = trim((string)($it['galpon'] ?? ''));
    $fechas = $it['fechas'] ?? [];
    if (!is_array($fechas)) $fechas = [];
    $zonaItem = trim((string)($it['zona'] ?? $zona));
    $subzonaItem = $it['subzona'] ?? null;
    $subzonaVal = (is_numeric($subzonaItem) || (is_string($subzonaItem) && preg_match('/^\d+$/', trim((string)$subzonaItem)))) ? (int)$subzonaItem : 0;
    foreach ($fechas as $f) {
        $campaniaRaw = trim((string)($f['campania'] ?? $it['campania'] ?? ''));
        $campania = strlen($campaniaRaw) >= 3 ? substr($campaniaRaw, -3) : str_pad($campaniaRaw, 3, '0', STR_PAD_LEFT);
        $edadVal = isset($f['edad']) ? (int)$f['edad'] : (isset($it['edad']) ? (int)$it['edad'] : (int)$edadPrograma);
        if ($edadVal < 0) $edadVal = 0;
        if ($edadVal > 999) $edadVal = 999;
        $fechaCarga = isset($f['fechaCarga']) ? (is_string($f['fechaCarga']) ? $f['fechaCarga'] : date('Y-m-d', strtotime($f['fechaCarga']))) : '';
        $fechaEjecucion = isset($f['fechaEjecucion']) ? (is_string($f['fechaEjecucion']) ? $f['fechaEjecucion'] : date('Y-m-d', strtotime($f['fechaEjecucion']))) : '';
        if ($fechaCarga === '') $fechaCarga = $fechaEjecucion;
        $bindVals = [$granja, $campania, $galpon, $codPrograma, $nomPrograma, $fechaCarga, $fechaEjecucion, $usuario, $zonaItem, $subzonaVal, $numCronograma, $usuario];
        if ($tieneNomGranja) $bindVals[] = $nomGranja;
        if ($tieneEdad) $bindVals[] = $edadVal;
        $stmt->bind_param($types, ...$bindVals);
        if (!$stmt->execute()) {
            $stmt->close();
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $conn->error]);
            exit;
        }
        $total++;
    }
}
$stmt->close();
$conn->close();
echo json_encode(['success' => true, 'message' => 'Cronograma actualizado correctamente.', 'total' => $total]);
