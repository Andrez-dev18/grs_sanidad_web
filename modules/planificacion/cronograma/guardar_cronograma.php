<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
    $conn = conectar_joya_mysqli();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$codPrograma = trim($input['codPrograma'] ?? '');
$nomPrograma = trim($input['nomPrograma'] ?? '');
$zona = trim($input['zona'] ?? '');
$subzonaRaw = $input['subzona'] ?? '';
$subzona = is_string($subzonaRaw) ? trim($subzonaRaw) : (is_numeric($subzonaRaw) ? (string)$subzonaRaw : '');

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

// Modo 1: items[] = [{ granja, nomGranja, campania, galpon, edad?, fechas: [...] }] (Especifico)
$items = $input['items'] ?? null;
if (is_array($items) && !empty($items)) {
    $cols = "granja, campania, galpon, codPrograma, nomPrograma, fechaCarga, fechaEjecucion, usuarioRegistro, zona, subzona";
    $placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
    $types = "ssssssssss";
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
        $errMsg = (isset($conn) && $conn) ? (string)@$conn->error : 'Prepare falló';
        echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $errMsg]);
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
        $subzonaItem = $it['subzona'] ?? $subzona;
        $subzonaVal = is_string($subzonaItem) ? trim($subzonaItem) : (is_numeric($subzonaItem) ? (string)$subzonaItem : '');
        foreach ($fechas as $f) {
            // Campaña = últimos 3 dígitos (del par o del ítem; si viene tcencos largo, se toma solo los 3 últimos)
            $campaniaRaw = trim((string)($f['campania'] ?? $it['campania'] ?? ''));
            $campania = strlen($campaniaRaw) >= 3 ? substr($campaniaRaw, -3) : str_pad($campaniaRaw, 3, '0', STR_PAD_LEFT);
            $edadVal = isset($f['edad']) ? (int)$f['edad'] : (isset($it['edad']) ? (int)$it['edad'] : 0);
            if ($edadVal > 999) $edadVal = 999;
            $fechaCarga = isset($f['fechaCarga']) ? (is_string($f['fechaCarga']) ? $f['fechaCarga'] : date('Y-m-d', strtotime($f['fechaCarga']))) : '';
            $fechaEjecucion = isset($f['fechaEjecucion']) ? (is_string($f['fechaEjecucion']) ? $f['fechaEjecucion'] : date('Y-m-d', strtotime($f['fechaEjecucion']))) : (is_string($f) ? $f : date('Y-m-d', strtotime($f)));
            if ($fechaCarga === '') $fechaCarga = $fechaEjecucion;
            $bindVals = [$granja, $campania, $galpon, $codPrograma, $nomPrograma, $fechaCarga, $fechaEjecucion, $usuario, $zonaItem, $subzonaVal];
            if ($tieneNumCronograma) $bindVals[] = $numCronograma;
            if ($tieneNomGranja) $bindVals[] = $nomGranja;
            if ($tieneEdad) $bindVals[] = $edadVal;
           
            $params = array_merge([$types], $bindVals);
            $refs = [];
            foreach ($params as $k => $v) {
                $refs[$k] = &$params[$k];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);
            if (!$stmt->execute()) {
                $errMsg = $stmt->error ?: (isset($conn) && $conn ? (string)@$conn->error : '');
                $stmt->close();
                $conn->close();
                echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $errMsg]);
                exit;
            }
            $total++;
        }
    }
    $stmt->close();
    // Insertar en san_cronograma_despliegue (scope persistente)
    // Usar combinaciones enviadas en payload.combinaciones si existen; si no, calcular desde items (granja/campaña/galpón seleccionados)
    $chkDespliegue = @$conn->query("SHOW TABLES LIKE 'san_cronograma_despliegue'");
    if ($chkDespliegue && $chkDespliegue->num_rows > 0 && $tieneNumCronograma) {
        $despliegueUnicos = [];
        $combinacionesPayload = $input['combinaciones'] ?? null;
        if (is_array($combinacionesPayload) && !empty($combinacionesPayload)) {
            foreach ($combinacionesPayload as $c) {
                $granja = substr(trim((string)($c['granja'] ?? '')), 0, 3);
                $galpon = trim((string)($c['galpon'] ?? ''));
                $campaniaRaw = trim((string)($c['campania'] ?? ''));
                $campania = strlen($campaniaRaw) >= 3 ? substr($campaniaRaw, -3) : str_pad($campaniaRaw, 3, '0', STR_PAD_LEFT);
                if ($granja === '' || $galpon === '') continue;
                $key = $granja . '|' . $campania . '|' . $galpon;
                if (!isset($despliegueUnicos[$key])) {
                    $despliegueUnicos[$key] = ['granja' => $granja, 'campania' => $campania, 'galpon' => $galpon];
                }
            }
        } else {
            foreach ($items as $it) {
                $granja = substr(trim((string)($it['granja'] ?? '')), 0, 3);
                $galpon = trim((string)($it['galpon'] ?? ''));
                if ($granja === '' || $galpon === '') continue;
                $campaniaRaw = trim((string)($it['campania'] ?? ''));
                $campania = strlen($campaniaRaw) >= 3 ? substr($campaniaRaw, -3) : str_pad($campaniaRaw, 3, '0', STR_PAD_LEFT);
                $key = $granja . '|' . $campania . '|' . $galpon;
                if (!isset($despliegueUnicos[$key])) {
                    $despliegueUnicos[$key] = ['granja' => $granja, 'campania' => $campania, 'galpon' => $galpon];
                }
            }
        }
        $stDesp = $conn->prepare("INSERT INTO san_cronograma_despliegue (numCronograma, granja, campania, galpon, usuarioRegistro, fechaHoraRegistro) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stDesp) {
            foreach ($despliegueUnicos as $d) {
                $stDesp->bind_param("issss", $numCronograma, $d['granja'], $d['campania'], $d['galpon'], $usuario);
                @$stDesp->execute();
            }
            $stDesp->close();
        }
    }
    $conn->close();
    $estBackend = min(10000, 1000 + ($total * 150));
    header('X-Estimated-Time-Backend: ' . (int)$estBackend);
    header('X-Estimated-Time-Frontend: 1000');
    echo json_encode(['success' => true, 'message' => 'Asignación guardada correctamente.', 'total' => $total]);
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
$typesModo2 = "ssssssssss";
if ($tieneNumCronograma) {
    $colsModo2 .= ", numCronograma";
    $placeholdersModo2 .= ", ?";
    $typesModo2 .= "i";
}
$stmt = $conn->prepare("INSERT INTO san_fact_cronograma ($colsModo2) VALUES ($placeholdersModo2)");
if (!$stmt) {
    $errMsg = (isset($conn) && $conn) ? (string)@$conn->error : 'Prepare falló';
    echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $errMsg]);
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
        $errMsg = $stmt->error ?: (isset($conn) && $conn ? (string)@$conn->error : '');
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $errMsg]);
        exit;
    }
}
    $stmt->close();
    // Insertar en san_cronograma_despliegue (Modo 2: una combinación granja/campania/galpon)
    $chkDespliegue = @$conn->query("SHOW TABLES LIKE 'san_cronograma_despliegue'");
    if ($chkDespliegue && $chkDespliegue->num_rows > 0 && $granja !== '' && $campania !== '' && $galpon !== '' && $codPrograma !== '') {
        $g = substr(trim($granja), 0, 3);
        $c = strlen(trim($campania)) >= 3 ? substr(trim($campania), -3) : str_pad(trim($campania), 3, '0', STR_PAD_LEFT);
        $chkCodProg = @$conn->query("SHOW COLUMNS FROM san_cronograma_despliegue LIKE 'codPrograma'");
        if ($chkCodProg && $chkCodProg->num_rows > 0) {
            $stChk = $conn->prepare("SELECT 1 FROM san_cronograma_despliegue WHERE codPrograma = ? AND granja = ? AND campania = ? AND galpon = ? LIMIT 1");
            $existe = false;
            if ($stChk) {
                $stChk->bind_param("ssss", $codPrograma, $g, $c, $galpon);
                $stChk->execute();
                $existe = ($stChk->get_result()->num_rows > 0);
                $stChk->close();
            }
            if (!$existe) {
                $stDesp = $conn->prepare("INSERT INTO san_cronograma_despliegue (codPrograma, granja, campania, galpon, usuarioRegistro, fechaHoraRegistro) VALUES (?, ?, ?, ?, ?, NOW())");
                if ($stDesp) {
                    $stDesp->bind_param("sssss", $codPrograma, $g, $c, $galpon, $usuario);
                    @$stDesp->execute();
                    $stDesp->close();
                }
            }
        } elseif ($tieneNumCronograma) {
            $stDesp = $conn->prepare("INSERT INTO san_cronograma_despliegue (numCronograma, granja, campania, galpon, usuarioRegistro, fechaHoraRegistro) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stDesp) {
                $stDesp->bind_param("issss", $numCronograma, $g, $c, $galpon, $usuario);
                @$stDesp->execute();
                $stDesp->close();
            }
        }
    }
    $conn->close();
    $numRegs = is_array($pares) ? count($pares) : 0;
    $estBackend = min(10000, 1000 + ($numRegs * 150));
    header('X-Estimated-Time-Backend: ' . (int)$estBackend);
    header('X-Estimated-Time-Frontend: 1000');
    echo json_encode(['success' => true, 'message' => 'Asignación guardada correctamente.']);
} catch (Throwable $e) {
    if (isset($conn) && $conn) @$conn->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
}
