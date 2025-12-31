<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

include_once '../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit();
}

$codigo = filter_input(INPUT_POST, 'codigo', FILTER_VALIDATE_INT);

if ($codigo === false || $codigo === null) {
    echo json_encode(['success' => false, 'message' => 'Código de respuesta inválido']);
    exit();
}

// Opcional: verificar que pertenece al análisis actual (no estrictamente necesario si UI lo controla)
$stmt = $conexion->prepare("DELETE FROM san_dim_tiporesultado WHERE codigo = ?");
$stmt->bind_param("i", $codigo);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Respuesta eliminada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontró la respuesta o ya fue eliminada']);
}

$conexion->close();
?>