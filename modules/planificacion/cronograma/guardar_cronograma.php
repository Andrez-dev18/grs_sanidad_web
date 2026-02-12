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
$codPrograma = trim($input['codPrograma'] ?? '');
$nomPrograma = trim($input['nomPrograma'] ?? '');
$zona = trim($input['zona'] ?? '');
$subzonaRaw = $input['subzona'] ?? '';
$subzona = is_numeric($subzonaRaw) ? (int)$subzonaRaw : (int)(is_string($subzonaRaw) ? trim(explode(',', $subzonaRaw)[0]) : 0);

if ($codPrograma === '') {
    echo json_encode(['success' => false, 'message' => 'Falta código de programa.']);
    exit;
}

$usuario = $_SESSION['usuario'] ?? 'WEB';

// Columnas opcionales en san_fact_cronograma
$tieneNomGranja = false;
$tieneEdad = false;
$chkNom = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
if ($chkNom && $chkNom->num_rows > 0) $tieneNomGranja = true;
$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
if ($chkEdad && $chkEdad->num_rows > 0) $tieneEdad = true;
$chkNumCrono = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
$tieneNumCronograma = $chkNumCrono && $chkNumCrono->num_rows > 0;

$numCronograma = 1;
if ($tieneNumCronograma) {
    $resMax = $conn->query("SELECT COALESCE(MAX(numCronograma), 0) + 1 AS nextNum FROM san_fact_cronograma");
    if ($resMax && $row = $resMax->fetch_assoc()) $numCronograma = (int)$row['nextNum'];
}

// Obtener primera edad del programa si se necesita (siempre registrar edad)
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

// Modo 1: items[] = [{ granja, nomGranja, campania, galpon, edad?, fechas: [...] }] (Especifico)
$items = $input['items'] ?? null;
if (is_array($items) && !empty($items)) {
    $cols = "granja, campania, galpon, codPrograma, nomPrograma, fechaCarga, fechaEjecucion, usuarioRegistro, zona, subzona";
    $placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
    $types = "sssssssssi";
    if ($tieneNumCronograma) {
        $cols .= ", numCronograma";
        $placeholders .= ", ?";
        $types .= "i";
    }
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
        echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
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
            // Campaña = últimos 3 dígitos (del par o del ítem; si viene tcencos largo, se toma solo los 3 últimos)
            $campaniaRaw = trim((string)($f['campania'] ?? $it['campania'] ?? ''));
            $campania = strlen($campaniaRaw) >= 3 ? substr($campaniaRaw, -3) : str_pad($campaniaRaw, 3, '0', STR_PAD_LEFT);
            $edadVal = isset($f['edad']) ? (int)$f['edad'] : (isset($it['edad']) ? (int)$it['edad'] : (int)$edadPrograma);
            if ($edadVal < 0) $edadVal = 0;
            if ($edadVal > 999) $edadVal = 999;
            $fechaCarga = isset($f['fechaCarga']) ? (is_string($f['fechaCarga']) ? $f['fechaCarga'] : date('Y-m-d', strtotime($f['fechaCarga']))) : '';
            $fechaEjecucion = isset($f['fechaEjecucion']) ? (is_string($f['fechaEjecucion']) ? $f['fechaEjecucion'] : date('Y-m-d', strtotime($f['fechaEjecucion']))) : (is_string($f) ? $f : date('Y-m-d', strtotime($f)));
            if ($fechaCarga === '') $fechaCarga = $fechaEjecucion;
            $bindVals = [$granja, $campania, $galpon, $codPrograma, $nomPrograma, $fechaCarga, $fechaEjecucion, $usuario, $zonaItem, $subzonaVal];
            if ($tieneNumCronograma) $bindVals[] = $numCronograma;
            if ($tieneNomGranja) $bindVals[] = $nomGranja;
            if ($tieneEdad) $bindVals[] = $edadVal;
            $stmt->bind_param($types, ...$bindVals);
            if (!$stmt->execute()) {
                $stmt->close();
                $conn->close();
                echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $conn->error]);
                exit;
            }
            $total++;
        }
    }
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Cronograma guardado correctamente.', 'total' => $total]);
    exit;
}

// Modo 2: modo zona — pares[] = [{ fechaCarga, fechaEjecucion }, ...]
$granja = trim($input['granja'] ?? '');
$campania = trim($input['campania'] ?? '');
$galpon = trim($input['galpon'] ?? '');
$pares = $input['pares'] ?? $input['fechas'] ?? [];

if (empty($pares)) {
    echo json_encode(['success' => false, 'message' => 'Faltan fechas.']);
    exit;
}
if ($zona === '' && $granja === '' && $campania === '' && $galpon === '') {
    echo json_encode(['success' => false, 'message' => 'Indique zona(s) o granja/campaña/galpón.']);
    exit;
}

if (!is_array($pares)) {
    $pares = array_filter(array_map('trim', explode(',', $pares)));
}

$colsModo2 = "granja, campania, galpon, codPrograma, nomPrograma, fechaCarga, fechaEjecucion, usuarioRegistro, zona, subzona";
$placeholdersModo2 = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
$typesModo2 = "sssssssssi";
if ($tieneNumCronograma) {
    $colsModo2 .= ", numCronograma";
    $placeholdersModo2 .= ", ?";
    $typesModo2 .= "i";
}
$stmt = $conn->prepare("INSERT INTO san_fact_cronograma ($colsModo2) VALUES ($placeholdersModo2)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
    exit;
}

foreach ($pares as $f) {
    if (is_array($f)) {
        $fechaCarga = isset($f['fechaCarga']) ? (is_string($f['fechaCarga']) ? $f['fechaCarga'] : date('Y-m-d', strtotime($f['fechaCarga']))) : '';
        $fechaEjecucion = isset($f['fechaEjecucion']) ? (is_string($f['fechaEjecucion']) ? $f['fechaEjecucion'] : date('Y-m-d', strtotime($f['fechaEjecucion']))) : '';
        if ($fechaCarga === '') $fechaCarga = $fechaEjecucion;
    } else {
        $fechaEjecucion = is_string($f) ? $f : date('Y-m-d', strtotime($f));
        $fechaCarga = $fechaEjecucion;
    }
    if ($tieneNumCronograma) {
        $stmt->bind_param($typesModo2, $granja, $campania, $galpon, $codPrograma, $nomPrograma, $fechaCarga, $fechaEjecucion, $usuario, $zona, $subzona, $numCronograma);
    } else {
        $stmt->bind_param($typesModo2, $granja, $campania, $galpon, $codPrograma, $nomPrograma, $fechaCarga, $fechaEjecucion, $usuario, $zona, $subzona);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $conn->error]);
        exit;
    }
}
$stmt->close();
$conn->close();
echo json_encode(['success' => true, 'message' => 'Cronograma guardado correctamente.']);
