<?php
header('Content-Type: application/json');

// Conexión
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Leer JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['granja']) || !isset($input['registros']) || empty($input['registros'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o incompletos']);
    exit;
}

// Cabecera
$granja     = $input['granja'];
$campania   = $input['campania'];
$galpon     = $input['galpon'];
$edad       = $input['edad'];
$fectra     = $input['fectra'];
$numreg     = (int)$input['numreg'];

// Automáticos
session_start();
$tuser = $_SESSION['usuario'] ?? 'WEB';
$tdate = date('Y-m-d');
$ttime = date('H:i:s');
$diareg = date('Y-m-d');
$tcencos = '';
$tcodsistema = 0;

// SQL con 23 ?
$sql = "INSERT INTO t_regnecropsia (
    tuser, tdate, ttime, tcencos, tgranja, tcampania, tedad, tgalpon, tnumreg, tfectra, diareg,
    tcodsistema, tsistema, tnivel, tparametro,
    tporcentaje1, tporcentaje2, tporcentaje3, tporcentaje4, tporcentaje5, tporcentajetotal,
    tobservacion, tobs
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?,
    ?, ?
)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparando consulta: ' . $conn->error]);
    exit;
}

$insertadas = 0;

foreach ($input['registros'] as $reg) {
    $obs = $reg['tobservacion'] ?? '';

    // 23 VARIABLES
    // 23 TIPOS: 16 's' + 1 'i' + 6 'd'
    $stmt->bind_param(
        "ssssssssissssssddddddss",  // EXACTAMENTE 23 caracteres
        $tuser,
        $tdate,
        $ttime,
        $tcencos,
        $granja,
        $campania,
        $edad,
        $galpon,
        $numreg,                  // i (int)
        $fectra,
        $diareg,
        $tcodsistema,             // s (aunque sea int)
        $reg['tsistema'],
        $reg['tnivel'],
        $reg['tparametro'],
        $reg['tporcentaje1'],     // d
        $reg['tporcentaje2'],     // d
        $reg['tporcentaje3'],     // d
        $reg['tporcentaje4'],     // d
        $reg['tporcentaje5'],     // d
        $reg['tporcentajetotal'], // d
        $obs,
        $obs
    );

    if ($stmt->execute()) {
        $insertadas++;
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => $insertadas > 0,
    'message' => $insertadas > 0 
        ? "¡Necropsia guardada correctamente! ($insertadas filas insertadas)"
        : "No se insertó ningún registro"
]);
?>