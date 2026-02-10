<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit();
}

include_once '../../../../conexion_grs_joya/conexion.php';
include_once '../../../includes/historial_acciones.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión.']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$codigo = isset($_POST['codigo']) ? (int) $_POST['codigo'] : 0;

if ($action === 'create') {
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'Nombre es obligatorio.']);
        exit();
    }
    $stmt = $conexion->prepare("INSERT INTO san_dim_emptrans (nombre) VALUES (?)");
    if ($stmt && $stmt->bind_param("s", $nombre) && $stmt->execute()) {
        $codigoGenerado = $conexion->insert_id;
        $datos_nuevos = json_encode(['codigo' => $codigoGenerado, 'nombre' => $nombre], JSON_UNESCAPED_UNICODE);
        $usuario = $_SESSION['usuario'] ?? 'sistema';
        $nom_usuario = $_SESSION['nombre'] ?? $usuario;
        try {
            registrarAccion(
                $usuario,
                $nom_usuario,
                'INSERT',
                'san_dim_emptrans',
                $codigoGenerado,
                null,
                $datos_nuevos,
                'Se creo una nueva empresa de transporte',
                null
            );
        } catch (Exception $e) {
            error_log("Error al registrar historial de acciones: " . $e->getMessage());
        }
        echo json_encode(['success' => true, 'message' => 'Empresa creada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear la empresa.']);
    }
} elseif ($action === 'update') {
    if (empty($nombre) || !$codigo) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
        exit();
    }
    // Obtener datos previos
    $stmt_prev = $conexion->prepare("SELECT nombre FROM san_dim_emptrans WHERE codigo = ?");
    $stmt_prev->bind_param("i", $codigo);
    $stmt_prev->execute();
    $result_prev = $stmt_prev->get_result();
    $datos_previos = null;
    if ($row_prev = $result_prev->fetch_assoc()) {
        $datos_previos = json_encode(['codigo' => $codigo, 'nombre' => $row_prev['nombre']], JSON_UNESCAPED_UNICODE);
    }
    $stmt_prev->close();
    
    $stmt = $conexion->prepare("UPDATE san_dim_emptrans SET nombre = ? WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("si", $nombre, $codigo) && $stmt->execute()) {
        $datos_nuevos = json_encode(['codigo' => $codigo, 'nombre' => $nombre], JSON_UNESCAPED_UNICODE);
        $usuario = $_SESSION['usuario'] ?? 'sistema';
        $nom_usuario = $_SESSION['nombre'] ?? $usuario;
        try {
            registrarAccion(
                $usuario,
                $nom_usuario,
                'UPDATE',
                'san_dim_emptrans',
                $codigo,
                $datos_previos,
                $datos_nuevos,
                'Se actualizo una empresa de transporte',
                null
            );
        } catch (Exception $e) {
            error_log("Error al registrar historial de acciones: " . $e->getMessage());
        }
        echo json_encode(['success' => true, 'message' => 'Empresa actualizada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
    }
} elseif ($action === 'delete') {
    if (!$codigo) {
        echo json_encode(['success' => false, 'message' => 'Código no válido.']);
        exit();
    }
    // Obtener datos previos antes de eliminar
    $stmt_prev = $conexion->prepare("SELECT nombre FROM san_dim_emptrans WHERE codigo = ?");
    $stmt_prev->bind_param("i", $codigo);
    $stmt_prev->execute();
    $result_prev = $stmt_prev->get_result();
    $datos_previos = null;
    if ($row_prev = $result_prev->fetch_assoc()) {
        $datos_previos = json_encode(['codigo' => $codigo, 'nombre' => $row_prev['nombre']], JSON_UNESCAPED_UNICODE);
    }
    $stmt_prev->close();
    
    // Opcional: verificar si está en uso antes de eliminar
    $stmt = $conexion->prepare("DELETE FROM san_dim_emptrans WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("i", $codigo) && $stmt->execute()) {
        $usuario = $_SESSION['usuario'] ?? 'sistema';
        $nom_usuario = $_SESSION['nombre'] ?? $usuario;
        try {
            registrarAccion(
                $usuario,
                $nom_usuario,
                'DELETE',
                'san_dim_emptrans',
                $codigo,
                $datos_previos,
                null,
                'Se elimino una empresa de transporte',
                null
            );
        } catch (Exception $e) {
            error_log("Error al registrar historial de acciones: " . $e->getMessage());
        }
        echo json_encode(['success' => true, 'message' => 'Empresa eliminada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
}
?>