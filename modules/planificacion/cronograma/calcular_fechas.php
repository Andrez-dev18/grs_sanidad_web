<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado', 'fechas' => []]);
    exit;
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión', 'fechas' => []]);
    exit;
}

$codPrograma = trim($_POST['codPrograma'] ?? $_GET['codPrograma'] ?? '');
$anio = (int)($_POST['anio'] ?? $_GET['anio'] ?? 0);

if ($codPrograma === '') {
    echo json_encode(['success' => false, 'message' => 'Falta código de programa.', 'fechas' => []]);
    exit;
}


$sqlDet = "SELECT DISTINCT edad FROM san_fact_programa_det WHERE codPrograma = ? AND edad IS NOT NULL AND edad > 0 ORDER BY edad ASC";
$st = $conn->prepare($sqlDet);
$st->bind_param("s", $codPrograma);
$st->execute();
$resEdades = $st->get_result();
$edadesUnicas = [];
while ($r = $resEdades->fetch_assoc()) {
    $edadesUnicas[] = (int) $r['edad'];
}
$st->close();
if (empty($edadesUnicas)) {
    echo json_encode(['success' => false, 'message' => 'Programa sin edades en el detalle.', 'fechas' => []]);
    exit;
}

$modo = trim($_POST['modo'] ?? $_GET['modo'] ?? 'especifico');
$fechasResultado = [];
$paresCargaEjecucion = []; 


if ($modo === 'zonas') {
    $pairs = $_POST['pairs'] ?? $_GET['pairs'] ?? [];
    if (!is_array($pairs)) $pairs = [];
    $pairs = array_filter($pairs, function ($p) {
        $ig = trim($p['id_granja'] ?? $p['idGranja'] ?? '');
        $ia = trim($p['id_galpon'] ?? $p['idGalpon'] ?? '');
        return $ig !== '' && $ia !== '';
    });
    if (empty($pairs)) {
        echo json_encode(['success' => false, 'message' => 'Indique al menos una zona/subzona.', 'fechas' => [], 'pares' => [], 'items' => []]);
        exit;
    }
    $pairsNorm = [];
    $seenPair = [];
    foreach ($pairs as $p) {
        $id_granja = str_pad(trim($p['id_granja'] ?? $p['idGranja'] ?? ''), 3, '0', STR_PAD_LEFT);
        $id_galpon = trim($p['id_galpon'] ?? $p['idGalpon'] ?? '');
        $key = $id_granja . '|' . $id_galpon;
        if (isset($seenPair[$key])) continue;
        $seenPair[$key] = true;
        $pairsNorm[] = [$id_granja, $id_galpon];
    }
    if (empty($pairsNorm)) {
        echo json_encode(['success' => false, 'message' => 'Indique al menos una zona/subzona.', 'fechas' => [], 'pares' => [], 'items' => []]);
        exit;
    }
    $itemsZona = [];
    foreach ($pairsNorm as $pair) {
        $id_granja = $pair[0];
        $id_galpon = $pair[1];
        $placeholders = implode(',', array_fill(0, count($edadesUnicas), '?'));
        $sqlFechas = "SELECT fecha AS fecha_ejecucion, edad, tcencos, tcodint, DATE_ADD(fecha, INTERVAL (edad * -1) DAY) AS fecha_carga FROM cargapollo_proyeccion WHERE LEFT(tcencos, 3) = ? AND tcodint = ? AND edad IN ($placeholders) AND YEAR(fecha) = ? GROUP BY fecha, edad, tcencos, tcodint ORDER BY fecha, edad";
        $st2 = $conn->prepare($sqlFechas);
        $params = array_merge([$id_granja, $id_galpon], $edadesUnicas, [$anio]);
        $types = 'ss' . str_repeat('i', count($edadesUnicas)) . 'i';
        $st2->bind_param($types, ...$params);
        $st2->execute();
        $resFechas = $st2->get_result();
        $paresEsta = [];
        while ($r = $resFechas->fetch_assoc()) {
            $tcencos = trim((string)($r['tcencos'] ?? ''));
            $campaniaVal = strlen($tcencos) >= 3 ? substr($tcencos, -3) : '';
            $fechaEjec = $r['fecha_ejecucion'] ?? $r['fecha'] ?? null;
            $fechaCarga = $r['fecha_carga'] ?? null;
            $edad = (int)($r['edad'] ?? 0);
            if ($fechaEjec && $edad >= 0) {
                
                $fechasResultado[] = $fechaEjec;
                $paresCargaEjecucion[] = ['edad' => $edad, 'fechaCarga' => $fechaCarga, 'fechaEjecucion' => $fechaEjec, 'campania' => $campaniaVal];
                $paresEsta[] = ['edad' => $edad, 'fechaCarga' => $fechaCarga, 'fechaEjecucion' => $fechaEjec, 'campania' => $campaniaVal];
            }
        }
        $st2->close();
        if (!empty($paresEsta)) {
            $itemsZona[] = ['granja' => $id_granja, 'campania' => $paresEsta[0]['campania'] ?? '', 'galpon' => $id_galpon, 'fechas' => $paresEsta];
        }
    }
    $fechasResultado = array_unique($fechasResultado);
    sort($fechasResultado);
    $edadPrograma = !empty($edadesUnicas) ? (int)$edadesUnicas[0] : null;
    echo json_encode(['success' => true, 'fechas' => array_values($fechasResultado), 'pares' => $paresCargaEjecucion, 'items' => $itemsZona, 'edadPrograma' => $edadPrograma]);
    $conn->close();
    exit;
}

// Modo ESPECIFICO con múltiples campañas (devuelve también items para guardar por campaña)
if ($modo === 'especifico_multi') {
    $granja = trim($_POST['granja'] ?? $_GET['granja'] ?? '');
    $galpon = trim($_POST['galpon'] ?? $_GET['galpon'] ?? '');
    $campanias = $_POST['campanias'] ?? $_GET['campanias'] ?? [];
    if (!is_array($campanias)) $campanias = array_filter(explode(',', $campanias));
    $campanias = array_map('trim', $campanias);
    $campanias = array_filter($campanias);
    if (strlen($granja) !== 3 || $galpon === '' || empty($campanias)) {
        echo json_encode(['success' => false, 'message' => 'Faltan granja, galpón o al menos una campaña.', 'fechas' => [], 'items' => []]);
        exit;
    }
    $campanias = array_values(array_unique($campanias));
    $items = [];
    $placeholders = implode(',', array_fill(0, count($edadesUnicas), '?'));
    foreach ($campanias as $campaniaVal) {
        $campania = str_pad($campaniaVal, 3, '0', STR_PAD_LEFT);
        $tcencos = $granja . $campania;
        $sqlFechas = "SELECT fecha AS fecha_ejecucion, edad, tcencos, tcodint, DATE_ADD(fecha, INTERVAL (edad * -1) DAY) AS fecha_carga FROM cargapollo_proyeccion WHERE tcencos = ? AND tcodint = ? AND edad IN ($placeholders) AND YEAR(fecha) = ? GROUP BY fecha, edad, tcencos, tcodint ORDER BY fecha, edad";
        $paramsFechas = array_merge([$tcencos, $galpon], $edadesUnicas, [$anio]);
        $typesFechas = 'ss' . str_repeat('i', count($edadesUnicas)) . 'i';
        $st2 = $conn->prepare($sqlFechas);
        $st2->bind_param($typesFechas, ...$paramsFechas);
        $st2->execute();
        $resFechas = $st2->get_result();
        $paresEstaCampania = [];
        while ($r = $resFechas->fetch_assoc()) {
            $fechaEjec = $r['fecha_ejecucion'] ?? $r['fecha'] ?? null;
            $fechaCarga = $r['fecha_carga'] ?? null;
            $edad = (int)($r['edad'] ?? 0);
            if ($fechaEjec && $edad >= 0) {
              
                $fechasResultado[] = $fechaEjec;
                $paresEstaCampania[] = ['edad' => $edad, 'fechaCarga' => $fechaCarga, 'fechaEjecucion' => $fechaEjec, 'campania' => $campania];
            }
        }
        $st2->close();
        if (!empty($paresEstaCampania)) {
            $items[] = ['granja' => $granja, 'campania' => $campania, 'galpon' => $galpon, 'fechas' => $paresEstaCampania];
        }
    }
    $fechasResultado = array_unique($fechasResultado);
    sort($fechasResultado);
    $edadPrograma = !empty($edadesUnicas) ? (int)$edadesUnicas[0] : null;
    echo json_encode(['success' => true, 'fechas' => array_values($fechasResultado), 'items' => $items, 'edadPrograma' => $edadPrograma]);
    $conn->close();
    exit;
}

// Modo ESPECIFICO (una granja, un galpón, una o más campañas)
$granja = trim($_POST['granja'] ?? $_GET['granja'] ?? '');
$galpon = trim($_POST['galpon'] ?? $_GET['galpon'] ?? '');
$campanias = $_POST['campanias'] ?? $_GET['campanias'] ?? null;
$campania = trim($_POST['campania'] ?? $_GET['campania'] ?? '');
if ($campanias === null && $campania !== '') {
    $campanias = [$campania];
}
if (!is_array($campanias)) {
    $campanias = array_filter(explode(',', (string)$campanias));
} else {
    $campanias = array_values($campanias);
}
$campanias = array_map('trim', $campanias);
$campanias = array_filter($campanias);
$campanias = array_values(array_unique($campanias));

if (strlen($granja) !== 3 || $galpon === '' || empty($campanias)) {
    echo json_encode(['success' => false, 'message' => 'Faltan granja, galpón o al menos una campaña.', 'fechas' => [], 'pares' => [], 'items' => []]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($edadesUnicas), '?'));
$items = [];
foreach ($campanias as $campaniaVal) {
    $campania = str_pad($campaniaVal, 3, '0', STR_PAD_LEFT);
    $tcencos = $granja . $campania;
    $sqlFechas = "SELECT fecha AS fecha_ejecucion, edad, tcencos, tcodint, DATE_ADD(fecha, INTERVAL (edad * -1) DAY) AS fecha_carga FROM cargapollo_proyeccion WHERE tcencos = ? AND tcodint = ? AND edad IN ($placeholders) AND YEAR(fecha) = ? GROUP BY fecha, edad, tcencos, tcodint ORDER BY fecha, edad";
    $paramsFechas = array_merge([$tcencos, $galpon], $edadesUnicas, [$anio]);
    $typesFechas = 'ss' . str_repeat('i', count($edadesUnicas)) . 'i';
    $st2 = $conn->prepare($sqlFechas);
    $st2->bind_param($typesFechas, ...$paramsFechas);
    $st2->execute();
    $resFechas = $st2->get_result();
    $paresEstaCampania = [];
    while ($r = $resFechas->fetch_assoc()) {
        $fechaEjec = $r['fecha_ejecucion'] ?? $r['fecha'] ?? null;
        $fechaCarga = $r['fecha_carga'] ?? null;
        $edad = (int)($r['edad'] ?? 0);
        if ($fechaEjec && $edad >= 0) {
           
            $fechasResultado[] = $fechaEjec;
            $paresCargaEjecucion[] = ['edad' => $edad, 'fechaCarga' => $fechaCarga, 'fechaEjecucion' => $fechaEjec];
            $paresEstaCampania[] = ['edad' => $edad, 'fechaCarga' => $fechaCarga, 'fechaEjecucion' => $fechaEjec];
        }
    }
    $st2->close();
    if (!empty($paresEstaCampania)) {
        $items[] = ['granja' => $granja, 'campania' => $campania, 'galpon' => $galpon, 'fechas' => $paresEstaCampania];
    }
}

if (empty($paresCargaEjecucion)) {
    echo json_encode(['success' => true, 'message' => 'No hay fechas para las edades del programa en este tcencos/galpón.', 'fechas' => [], 'pares' => [], 'items' => []]);
    exit;
}

$fechasResultado = array_unique($fechasResultado);
sort($fechasResultado);
$edadPrograma = !empty($edadesUnicas) ? (int)$edadesUnicas[0] : null;

echo json_encode(['success' => true, 'fechas' => array_values($fechasResultado), 'pares' => $paresCargaEjecucion, 'items' => $items, 'edadPrograma' => $edadPrograma]);
$conn->close();
