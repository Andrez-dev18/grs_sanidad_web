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

$analisis = filter_input(INPUT_POST, 'analisis', FILTER_VALIDATE_INT);
$tipo = trim($_POST['tipo'] ?? '');
$codigo = filter_input(INPUT_POST, 'codigo', FILTER_VALIDATE_INT);

// Validaciones
if ($analisis === false || $analisis === null) {
    echo json_encode(['success' => false, 'message' => 'Análisis inválido']);
    exit();
}

if (empty($tipo)) {
    echo json_encode(['success' => false, 'message' => 'El valor de la respuesta no puede estar vacío']);
    exit();
}

if (strlen($tipo) > 255) {
    echo json_encode(['success' => false, 'message' => 'El valor es demasiado largo (máx. 255 caracteres)']);
    exit();
}

$conexion->autocommit(false);

try {
    if ($codigo) {
        // ✏️ ACTUALIZAR
        $stmt = $conexion->prepare("UPDATE san_dim_tiporesultado SET tipo = ? WHERE codigo = ? AND analisis = ?");
        $stmt->bind_param("sii", $tipo, $codigo, $analisis);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conexion->commit();
            echo json_encode(['success' => true, 'message' => 'Respuesta actualizada correctamente']);
        } else {
            throw new Exception('No se encontró la respuesta o no se modificó nada');
        }
    } else {
        // ➕ INSERTAR
        // Evitar duplicados
        $check = $conexion->prepare("SELECT codigo FROM san_dim_tiporesultado WHERE analisis = ? AND tipo = ?");
        $check->bind_param("is", $analisis, $tipo);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            throw new Exception('Ya existe una respuesta idéntica para este análisis');
        }

        $stmt = $conexion->prepare("INSERT INTO san_dim_tiporesultado (analisis, tipo) VALUES (?, ?)");
        $stmt->bind_param("is", $analisis, $tipo);
        $stmt->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Respuesta creada correctamente']);
    }
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conexion->autocommit(true);
$conexion->close();
?>