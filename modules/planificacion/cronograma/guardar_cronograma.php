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
$granja = trim($input['granja'] ?? '');
$campania = trim($input['campania'] ?? '');
$galpon = trim($input['galpon'] ?? '');
$codPrograma = trim($input['codPrograma'] ?? '');
$nomPrograma = trim($input['nomPrograma'] ?? '');
$fechas = $input['fechas'] ?? [];

if ($granja === '' || $campania === '' || $galpon === '' || $codPrograma === '' || empty($fechas)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos o fechas.']);
    exit;
}

if (!is_array($fechas)) {
    $fechas = array_filter(array_map('trim', explode(',', $fechas)));
}

$usuario = $_SESSION['usuario'] ?? 'WEB';
$stmt = $conn->prepare("INSERT INTO san_plan_cronograma (granja, campania, galpon, codPrograma, nomPrograma, fecha, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $granja, $campania, $galpon, $codPrograma, $nomPrograma, $fecha, $usuario);

foreach ($fechas as $f) {
    $fecha = is_string($f) ? $f : date('Y-m-d', strtotime($f));
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
