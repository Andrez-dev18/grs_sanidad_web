<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

include_once '../../../../conexion_grs_joya/conexion.php';
include_once '../../../includes/historial_acciones.php';
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

// Obtener datos previos antes de eliminar
$stmt_prev = $conexion->prepare("SELECT analisis, tipo FROM san_dim_tiporesultado WHERE codigo = ?");
$stmt_prev->bind_param("i", $codigo);
$stmt_prev->execute();
$result_prev = $stmt_prev->get_result();
$datos_previos = null;
if ($row_prev = $result_prev->fetch_assoc()) {
    $datos_previos = json_encode([
        'codigo' => $codigo,
        'analisis' => $row_prev['analisis'],
        'tipo' => $row_prev['tipo']
    ], JSON_UNESCAPED_UNICODE);
}
$stmt_prev->close();

// Opcional: verificar que pertenece al análisis actual (no estrictamente necesario si UI lo controla)
$stmt = $conexion->prepare("DELETE FROM san_dim_tiporesultado WHERE codigo = ?");
$stmt->bind_param("i", $codigo);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // Registrar en historial de acciones
    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $nom_usuario = $_SESSION['nombre'] ?? $usuario;
    try {
        registrarAccion(
            $usuario,
            $nom_usuario,
            'DELETE',
            'san_dim_tiporesultado',
            $codigo,
            $datos_previos,
            null,
            'Se elimino un tipo de resultado',
            null
        );
    } catch (Exception $e) {
        error_log("Error al registrar historial de acciones: " . $e->getMessage());
    }
    echo json_encode(['success' => true, 'message' => 'Respuesta eliminada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontró la respuesta o ya fue eliminada']);
}

$conexion->close();
?>