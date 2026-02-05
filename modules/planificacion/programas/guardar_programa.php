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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$codigo = trim($input['codigo'] ?? '');
$nombre = trim($input['nombre'] ?? '');
$codTipo = (int)($input['codTipo'] ?? 0);
$nomTipo = trim($input['nomTipo'] ?? '');
$edadesRaw = $input['edades'] ?? ''; // puede ser "21,22,23" o array [21,22,23]

if (empty($codigo) || empty($nombre) || $codTipo <= 0) {
    echo json_encode(['success' => false, 'message' => 'Faltan código, nombre o tipo.']);
    exit;
}

// Parsear edades: string "21, 22, 23" o array
$edades = [];
if (is_array($edadesRaw)) {
    $edades = array_map('intval', array_filter($edadesRaw));
} else {
    $partes = preg_split('/[\s,;]+/', (string)$edadesRaw, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($partes as $p) {
        $n = (int) trim($p);
        if ($n > 0 && $n < 999) $edades[] = $n;
    }
}
$edades = array_unique($edades);
if (empty($edades)) {
    echo json_encode(['success' => false, 'message' => 'Indique al menos una edad (días).']);
    exit;
}

// Obtener nombre del tipo si no vino
if (empty($nomTipo)) {
    $st = $conn->prepare("SELECT nombre FROM san_tipo_programa WHERE codigo = ?");
    $st->bind_param("i", $codTipo);
    $st->execute();
    $r = $st->get_result();
    if ($r && $row = $r->fetch_assoc()) $nomTipo = $row['nombre'];
    $st->close();
}

$usuarioRegistro = $_SESSION['usuario'] ?? 'WEB';

$stmt = $conn->prepare("INSERT INTO san_plan_programa (codigo, nombre, codTipo, nomTipo, edad, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
$stmt->bind_param("ssisis", $codigo, $nombre, $codTipo, $nomTipo, $edad, $usuarioRegistro);

foreach ($edades as $edad) {
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $conn->error]);
        exit;
    }
}

$stmt->close();
$conn->close();
echo json_encode(['success' => true, 'message' => 'Programa registrado correctamente.']);
