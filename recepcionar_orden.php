<?php
session_start();
include '../conexion_grs_joya/conexion.php';
include 'historial_resultados.php';

$conn = conectar_joya();
$data = json_decode(file_get_contents("php://input"), true);

// Validar datos obligatorios
if (!isset($data['codEnvio']) || trim($data['codEnvio']) === '') {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Código de envío requerido'
    ]);
    exit;
}

if (!isset($data['tipoReceptor']) || trim($data['tipoReceptor']) === '') {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Debe seleccionar quién realiza la recepción'
    ]);
    exit;
}

$codEnvio = trim($data['codEnvio']);
$obs = $data['obs'] ?? '';
$tipoReceptor = $data['tipoReceptor'];

// Validar tipoReceptor permitido
if ($tipoReceptor !== 'Transporte' && $tipoReceptor !== 'Laboratorio') {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Tipo de receptor no válido'
    ]);
    exit;
}

// Determinar la ubicación según el tipo de receptor
$ubicacion = ($tipoReceptor === 'Transporte') ? 'Transporte' : 'Laboratorio';

// Verificar si ya existe un registro con este codEnvio y esta ubicación
$sqlCheck = "SELECT id FROM san_dim_historial_resultados 
             WHERE codEnvio = ? 
             AND ubicacion = ? 
             LIMIT 1";

$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ss", $codEnvio, $ubicacion);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Esta muestra ya fue recepcionada en esta etapa (' . $ubicacion . ')'
    ]);
    $stmtCheck->close();
    exit;
}
$stmtCheck->close();

// Si no hay duplicado, proceder con el registro
$accion = ($tipoReceptor === 'Transporte') 
    ? 'Recepción de muestra' 
    : 'Recepción de muestra por laboratorio';

$usuarioDefault = ($tipoReceptor === 'Transporte') ? 'transportista' : 'laboratorio';

$ok = insertarHistorial(
    $conn,
    $codEnvio,
    0,
    $accion,
    null,
    $obs,
    $_SESSION['usuario'] ?? $usuarioDefault,
    $ubicacion
);

$mensaje = $ok 
    ? ($tipoReceptor === 'Transporte' 
        ? 'Muestra recogida por transportista' 
        : 'Muestra recibida en laboratorio')
    : 'Error al registrar historial';

echo json_encode([
    'ok' => $ok,
    'mensaje' => $mensaje
]);