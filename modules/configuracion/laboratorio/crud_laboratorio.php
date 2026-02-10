<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include_once '../conexion_grs_joya/conexion.php';
include_once '../../../includes/historial_acciones.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

// Usamos operador ?? (válido en PHP 7.2)
$action = isset($_POST['action']) ? $_POST['action'] : '';
$nombre = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
$codigo = isset($_POST['codigo']) ? (int) $_POST['codigo'] : null;

// Validar nombre solo para crear/editar
if (($action === 'create' || $action === 'update') && empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
    exit();
}

mysqli_begin_transaction($conexion);

// Variables para historial
$nombre_previo = '';

try {
    if ($action === 'create') {
        // Evitar duplicados
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM san_dim_laboratorio WHERE nombre = ?");
        mysqli_stmt_bind_param($check, "s", $nombre);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        $row = mysqli_fetch_assoc($result);
        if ($row['cnt'] > 0) {
            throw new Exception('Ya existe un laboratorio con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "INSERT INTO san_dim_laboratorio (nombre) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $nombre);

    } elseif ($action === 'update') {
        if ($codigo === null || $codigo <= 0) {
            throw new Exception('Código no válido.');
        }

        /*
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM san_dim_laboratorio WHERE nombre = ? AND codigo != ?");
        mysqli_stmt_bind_param($check, "si", $nombre, $codigo);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        $row = mysqli_fetch_assoc($result);
        if ($row['cnt'] > 0) {
            throw new Exception('Ya existe otro laboratorio con ese nombre.');
        }
        */

        $stmt = mysqli_prepare($conexion, "UPDATE san_dim_laboratorio SET nombre = ? WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "si", $nombre, $codigo);

    } elseif ($action === 'delete') {
        if ($codigo === null || $codigo <= 0) {
            throw new Exception('Código no válido.');
        }

        // Obtener datos previos antes de eliminar
        $check_prev = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_laboratorio WHERE codigo = ?");
        mysqli_stmt_bind_param($check_prev, "i", $codigo);
        mysqli_stmt_execute($check_prev);
        $result_prev = mysqli_stmt_get_result($check_prev);
        $nombre_previo = '';
        if ($row_prev = mysqli_fetch_assoc($result_prev)) {
            $nombre_previo = $row_prev['nombre'];
        }
        mysqli_stmt_close($check_prev);

        $stmt = mysqli_prepare($conexion, "DELETE FROM san_dim_laboratorio WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "i", $codigo);

    } else {
        throw new Exception('Acción no válida.');
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al ejecutar la operación en la base de datos.');
    }

    // Obtener código generado o usado
    $codigo_usado = null;
    if ($action === 'create') {
        $codigo_usado = mysqli_insert_id($conexion);
    } elseif ($action === 'update' || $action === 'delete') {
        $codigo_usado = $codigo;
    }

    mysqli_commit($conexion);

    // Registrar en historial de acciones
    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $nom_usuario = $_SESSION['nombre'] ?? $usuario;
    
    try {
        if ($action === 'create') {
            $datos_nuevos = json_encode(['codigo' => $codigo_usado, 'nombre' => $nombre], JSON_UNESCAPED_UNICODE);
            registrarAccion(
                $usuario,
                $nom_usuario,
                'INSERT',
                'san_dim_laboratorio',
                $codigo_usado,
                null,
                $datos_nuevos,
                'Se creo un nuevo laboratorio',
                null
            );
        } elseif ($action === 'update') {
            // Obtener datos previos (ya tenemos el nombre anterior si lo necesitamos)
            $datos_previos = null; // Podríamos obtenerlo antes del update si es necesario
            $datos_nuevos = json_encode(['codigo' => $codigo_usado, 'nombre' => $nombre], JSON_UNESCAPED_UNICODE);
            registrarAccion(
                $usuario,
                $nom_usuario,
                'UPDATE',
                'san_dim_laboratorio',
                $codigo_usado,
                $datos_previos,
                $datos_nuevos,
                'Se actualizo un laboratorio',
                null
            );
        } elseif ($action === 'delete') {
            $datos_previos = json_encode(['codigo' => $codigo_usado, 'nombre' => $nombre_previo], JSON_UNESCAPED_UNICODE);
            registrarAccion(
                $usuario,
                $nom_usuario,
                'DELETE',
                'san_dim_laboratorio',
                $codigo_usado,
                $datos_previos,
                null,
                'Se elimino un laboratorio',
                null
            );
        }
    } catch (Exception $e) {
        error_log("Error al registrar historial de acciones: " . $e->getMessage());
    }

    // Reemplazar match por switch (PHP 7.2 compatible)
    switch ($action) {
        case 'create':
            $mensaje = '✅ Laboratorio creado correctamente.';
            break;
        case 'update':
            $mensaje = '✅ Laboratorio actualizado correctamente.';
            break;
        case 'delete':
            $mensaje = '✅ Laboratorio eliminado correctamente.';
            break;
        default:
            $mensaje = 'Operación completada.';
    }

    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    mysqli_close($conexion);
}
?>