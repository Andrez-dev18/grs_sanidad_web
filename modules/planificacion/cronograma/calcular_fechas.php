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
if ($anio < 2000 || $anio > 2100) $anio = (int)date('Y');
if ($codPrograma === '') {
    echo json_encode(['success' => false, 'message' => 'Falta código de programa.', 'fechas' => []]);
    exit;
}

// Edades del programa desde san_fact_programa_det
$st = $conn->prepare("SELECT DISTINCT edad FROM san_fact_programa_det WHERE codPrograma = ? AND edad IS NOT NULL AND edad > 0 ORDER BY edad ASC");
$st->bind_param("s", $codPrograma);
$st->execute();
$resEdades = $st->get_result();
$edades = [];
while ($r = $resEdades->fetch_assoc()) {
    $edades[] = (int) $r['edad'];
}
$st->close();
if (empty($edades)) {
    echo json_encode(['success' => false, 'message' => 'Programa sin edades en el detalle.', 'fechas' => []]);
    exit;
}

$modo = trim($_POST['modo'] ?? $_GET['modo'] ?? 'especifico');
$fechasResultado = [];
$paresCargaEjecucion = []; // { edad, fechaCarga, fechaEjecucion } por cada fila a guardar

// Modo ZONAS: pares id_granja, id_galpon (desde zonas/subzonas). Devolver items por granja/galpón con filas edad/fecCarga/fecEjec.
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
    $itemsZona = [];
    foreach ($pairs as $p) {
        $id_granja = str_pad(trim($p['id_granja'] ?? $p['idGranja'] ?? ''), 3, '0', STR_PAD_LEFT);
        $id_galpon = trim($p['id_galpon'] ?? $p['idGalpon'] ?? '');
        // Campaña = últimos 3 dígitos de tcencos (ej: 621148 -> 148)
        $sqlFechas = "SELECT DISTINCT fecha, tcencos FROM cargapollo_proyeccion WHERE LEFT(tcencos, 3) = ? AND tcodint = ? AND edad = 1 AND YEAR(fecha) = ? ORDER BY fecha";
        $st2 = $conn->prepare($sqlFechas);
        $st2->bind_param("ssi", $id_granja, $id_galpon, $anio);
        $st2->execute();
        $resFechas = $st2->get_result();
        $paresEsta = [];
        while ($r = $resFechas->fetch_assoc()) {
            $tcencos = trim((string)($r['tcencos'] ?? ''));
            $campaniaVal = strlen($tcencos) >= 3 ? substr($tcencos, -3) : '';
            $fechaCarga = $r['fecha'];
            if ($fechaCarga) {
                $d = new DateTime($fechaCarga);
                foreach ($edades as $edad) {
                    $d2 = clone $d;
                    $d2->modify('+' . $edad . ' days');
                    $fechaEjec = $d2->format('Y-m-d');
                    $fechasResultado[] = $fechaEjec;
                    $paresCargaEjecucion[] = ['edad' => $edad, 'fechaCarga' => $fechaCarga, 'fechaEjecucion' => $fechaEjec, 'campania' => $campaniaVal];
                    $paresEsta[] = ['edad' => $edad, 'fechaCarga' => $fechaCarga, 'fechaEjecucion' => $fechaEjec, 'campania' => $campaniaVal];
                }
            }
        }
        $st2->close();
        if (!empty($paresEsta)) {
            $itemsZona[] = ['granja' => $id_granja, 'campania' => $paresEsta[0]['campania'] ?? '', 'galpon' => $id_galpon, 'fechas' => $paresEsta];
        }
    }
    $fechasResultado = array_unique($fechasResultado);
    sort($fechasResultado);
    $edadPrograma = !empty($edades) ? (int)$edades[0] : null;
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
    $items = [];
    foreach ($campanias as $campaniaVal) {
        $campania = str_pad($campaniaVal, 3, '0', STR_PAD_LEFT);
        $tcencos = $granja . $campania;
        $sqlFechas = "SELECT DISTINCT fecha FROM cargapollo_proyeccion WHERE tcencos = ? AND tcodint = ? AND edad = 1 AND YEAR(fecha) = ?";
        $paramsFechas = [$tcencos, $galpon, $anio];
        $typesFechas = 'ssi';
        $sqlFechas .= " ORDER BY fecha";
        $st2 = $conn->prepare($sqlFechas);
        $st2->bind_param($typesFechas, ...$paramsFechas);
        $st2->execute();
        $resFechas = $st2->get_result();
        $paresEstaCampania = [];
        while ($r = $resFechas->fetch_assoc()) {
            $fechaCarga = $r['fecha'];
            if ($fechaCarga) {
                $d = new DateTime($fechaCarga);
                foreach ($edades as $edad) {
                    $d2 = clone $d;
                    $d2->modify('+' . $edad . ' days');
                    $fechaEjec = $d2->format('Y-m-d');
                    $fechasResultado[] = $fechaEjec;
                    $paresEstaCampania[] = ['edad' => $edad, 'fechaCarga' => $fechaCarga, 'fechaEjecucion' => $fechaEjec, 'campania' => $campania];
                }
            }
        }
        $st2->close();
        if (!empty($paresEstaCampania)) {
            $items[] = ['granja' => $granja, 'campania' => $campania, 'galpon' => $galpon, 'fechas' => $paresEstaCampania];
        }
    }
    $fechasResultado = array_unique($fechasResultado);
    sort($fechasResultado);
    $edadPrograma = !empty($edades) ? (int)$edades[0] : null;
    echo json_encode(['success' => true, 'fechas' => array_values($fechasResultado), 'items' => $items, 'edadPrograma' => $edadPrograma]);
    $conn->close();
    exit;
}

// Modo ESPECIFICO simple (una granja, una campaña, un galpón)
$granja = trim($_POST['granja'] ?? $_GET['granja'] ?? '');
$campania = trim($_POST['campania'] ?? $_GET['campania'] ?? '');
$galpon = trim($_POST['galpon'] ?? $_GET['galpon'] ?? '');

if (strlen($granja) !== 3 || strlen($campania) !== 3 || $galpon === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan granja, campaña o galpón.', 'fechas' => []]);
    exit;
}

$tcencos = $granja . $campania;

$sqlFechas = "SELECT DISTINCT fecha FROM cargapollo_proyeccion WHERE tcencos = ? AND tcodint = ? AND edad = 1 AND YEAR(fecha) = ?";
$paramsFechas = [$tcencos, $galpon, $anio];
$typesFechas = 'ssi';
$sqlFechas .= " ORDER BY fecha";
$st2 = $conn->prepare($sqlFechas);
$st2->bind_param($typesFechas, ...$paramsFechas);
$st2->execute();
$resFechas = $st2->get_result();
$fechasBase = [];
while ($r = $resFechas->fetch_assoc()) {
    $f = $r['fecha'];
    if ($f) $fechasBase[] = $f;
}
$st2->close();

if (empty($fechasBase)) {
    echo json_encode(['success' => true, 'message' => 'No hay fechas con edad 1 para este tcencos/galpón.', 'fechas' => []]);
    exit;
}

foreach ($fechasBase as $fechaBase) {
    $d = new DateTime($fechaBase);
    foreach ($edades as $edad) {
        $d2 = clone $d;
        $d2->modify('+' . $edad . ' days');
        $fechaEjec = $d2->format('Y-m-d');
        $fechasResultado[] = $fechaEjec;
        $paresCargaEjecucion[] = ['edad' => $edad, 'fechaCarga' => $fechaBase, 'fechaEjecucion' => $fechaEjec];
    }
}
$fechasResultado = array_unique($fechasResultado);
sort($fechasResultado);

echo json_encode(['success' => true, 'fechas' => array_values($fechasResultado), 'pares' => $paresCargaEjecucion]);
$conn->close();
