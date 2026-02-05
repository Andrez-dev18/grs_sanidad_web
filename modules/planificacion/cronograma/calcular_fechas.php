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

$granja = trim($_POST['granja'] ?? $_GET['granja'] ?? '');
$campania = trim($_POST['campania'] ?? $_GET['campania'] ?? '');
$galpon = trim($_POST['galpon'] ?? $_GET['galpon'] ?? '');
$codPrograma = trim($_POST['codPrograma'] ?? $_GET['codPrograma'] ?? '');

if (strlen($granja) !== 3 || strlen($campania) !== 3 || $galpon === '' || $codPrograma === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan granja, campaña, galpón o programa.', 'fechas' => []]);
    exit;
}

$tcencos = $granja . $campania; // 6 dígitos

// Edades del programa
$st = $conn->prepare("SELECT DISTINCT edad FROM san_plan_programa WHERE codigo = ? ORDER BY edad ASC");
$st->bind_param("s", $codPrograma);
$st->execute();
$resEdades = $st->get_result();
$edades = [];
while ($r = $resEdades->fetch_assoc()) {
    $edades[] = (int) $r['edad'];
}
$st->close();
if (empty($edades)) {
    echo json_encode(['success' => false, 'message' => 'Programa sin edades.', 'fechas' => []]);
    exit;
}

$st2 = $conn->prepare("SELECT DISTINCT fecha FROM cargapollo_proyeccion WHERE tcencos = ? AND tcodint = ? AND edad = 1 ORDER BY fecha");
$st2->bind_param("ss", $tcencos, $galpon);
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

// Para cada fecha base, sumar cada edad (días) y recolectar
$fechasResultado = [];
foreach ($fechasBase as $fechaBase) {
    $d = new DateTime($fechaBase);
    foreach ($edades as $edad) {
        $d2 = clone $d;
        $d2->modify('+' . $edad . ' days');
        $fechasResultado[] = $d2->format('Y-m-d');
    }
}
$fechasResultado = array_unique($fechasResultado);
sort($fechasResultado);

echo json_encode(['success' => true, 'fechas' => array_values($fechasResultado)]);
$conn->close();
