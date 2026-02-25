<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}
include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
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
$subzona = is_string($subzonaRaw) ? trim($subzonaRaw) : (is_numeric($subzonaRaw) ? (string)$subzonaRaw : '');

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

$hoy = date('Y-m-d');
// Verificar si hay registros pasados (fechaEjecucion < hoy) para decidir lógica de despliegue
$tienePasados = false;
$stPasados = $conn->prepare("SELECT 1 FROM san_fact_cronograma WHERE numCronograma = ? AND DATE(fechaEjecucion) < ? LIMIT 1");
if ($stPasados) {
    $stPasados->bind_param("is", $numCronograma, $hoy);
    $stPasados->execute();
    $tienePasados = ($stPasados->get_result()->num_rows > 0);
    $stPasados->close();
}

// Al editar: no eliminar todos los registros; solo los de fecha de ejecución >= hoy (conservar los anteriores al día actual)
$del = $conn->prepare("DELETE FROM san_fact_cronograma WHERE numCronograma = ? AND DATE(fechaEjecucion) >= ?");
if (!$del) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al preparar DELETE.']);
    exit;
}
$del->bind_param("is", $numCronograma, $hoy);
$del->execute();
$del->close();

$cols = "granja, campania, galpon, codPrograma, nomPrograma, fechaCarga, fechaEjecucion, usuarioRegistro, zona, subzona, numCronograma, usuarioModificacion, fechaHoraModificacion";
$placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()";
$types = "ssssssssssis";
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
    $subzonaItem = $it['subzona'] ?? $subzona;
    $subzonaVal = is_string($subzonaItem) ? trim($subzonaItem) : (is_numeric($subzonaItem) ? (string)$subzonaItem : '');
    foreach ($fechas as $f) {
        $fechaCarga = isset($f['fechaCarga']) ? (is_string($f['fechaCarga']) ? $f['fechaCarga'] : date('Y-m-d', strtotime($f['fechaCarga']))) : '';
        $fechaEjecucion = isset($f['fechaEjecucion']) ? (is_string($f['fechaEjecucion']) ? $f['fechaEjecucion'] : date('Y-m-d', strtotime($f['fechaEjecucion']))) : '';
        if ($fechaEjecucion === '') continue;
        // Insertar solo registros con fecha de ejecución >= hoy (los anteriores ya se conservaron en BD)
        if ($fechaEjecucion < $hoy) continue;
        $campaniaRaw = trim((string)($f['campania'] ?? $it['campania'] ?? ''));
        $campania = strlen($campaniaRaw) >= 3 ? substr($campaniaRaw, -3) : str_pad($campaniaRaw, 3, '0', STR_PAD_LEFT);
        $edadVal = isset($f['edad']) ? (int)$f['edad'] : (isset($it['edad']) ? (int)$it['edad'] : (int)$edadPrograma);
        if ($edadVal > 999) $edadVal = 999;
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

// Actualizar san_cronograma_despliegue
// Solo se permite BORRAR cuando TODAS las asignaciones son futuras (si hay pasadas, solo añadir nuevas)
$chkDespliegue = @$conn->query("SHOW TABLES LIKE 'san_cronograma_despliegue'");
if ($chkDespliegue && $chkDespliegue->num_rows > 0) {
    if (!$tienePasados) {
        // Todos futuros: reemplazar despliegue completamente
        $delDesp = $conn->prepare("DELETE FROM san_cronograma_despliegue WHERE numCronograma = ?");
        if ($delDesp) {
            $delDesp->bind_param("i", $numCronograma);
            $delDesp->execute();
            $delDesp->close();
        }
    }
    // Usar combinaciones enviadas en payload.combinaciones si existen; si no, calcular desde items (granja/campaña/galpón seleccionados)
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
            $campaniaRaw = trim((string)($it['campania'] ?? ''));
            $campania = strlen($campaniaRaw) >= 3 ? substr($campaniaRaw, -3) : str_pad($campaniaRaw, 3, '0', STR_PAD_LEFT);
            if ($granja === '' || $galpon === '') continue;
            $key = $granja . '|' . $campania . '|' . $galpon;
            if (!isset($despliegueUnicos[$key])) {
                $despliegueUnicos[$key] = ['granja' => $granja, 'campania' => $campania, 'galpon' => $galpon];
            }
        }
    }
    $stDesp = $conn->prepare("INSERT INTO san_cronograma_despliegue (numCronograma, granja, campania, galpon, usuarioRegistro, fechaHoraRegistro, usuarioModificacion, fechaHoraModificacion) VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW())");
    if ($stDesp) {
        foreach ($despliegueUnicos as $d) {
            if ($tienePasados) {
                $stChk = $conn->prepare("SELECT 1 FROM san_cronograma_despliegue WHERE numCronograma = ? AND granja = ? AND campania = ? AND galpon = ? LIMIT 1");
                if ($stChk) {
                    $stChk->bind_param("isss", $numCronograma, $d['granja'], $d['campania'], $d['galpon']);
                    $stChk->execute();
                    $existe = ($stChk->get_result()->num_rows > 0);
                    $stChk->close();
                    if ($existe) continue;
                }
            }
            $stDesp->bind_param("isssss", $numCronograma, $d['granja'], $d['campania'], $d['galpon'], $usuario, $usuario);
            @$stDesp->execute();
        }
        $stDesp->close();
    }
}

$conn->close();
$estBackend = min(10000, 1000 + ($total * 150));
header('X-Estimated-Time-Backend: ' . (int)$estBackend);
header('X-Estimated-Time-Frontend: 1000');
echo json_encode(['success' => true, 'message' => 'Cronograma actualizado correctamente.', 'total' => $total]);
