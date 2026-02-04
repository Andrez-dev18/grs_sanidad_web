<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include_once '../../../../conexion_grs_joya/conexion.php';
include_once '../../../includes/historial_acciones.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

// PHP 7.2 compatible: ?? es válido, pero usamos isset() si prefieres (opcional)
$action = isset($_POST['action']) ? $_POST['action'] : '';
$nombre = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
$descripcion = trim(isset($_POST['descripcion']) ? $_POST['descripcion'] : '');
$longitud_codigo = isset($_POST['lonCod']) ? (int)$_POST['lonCod'] : 10;
$codigo = isset($_POST['codigo']) ? (int)$_POST['codigo'] : null;

if (empty($nombre) && $action !== 'delete') {
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
    exit();
}

if ($action !== 'delete' && ($longitud_codigo < 1)) {
    echo json_encode(['success' => false, 'message' => 'La longitud de código debe ser mayor que 0.']); // corregido: "mayor que 0", no "1"
    exit();
}

mysqli_begin_transaction($conexion);

try {
    if ($action === 'create') {
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) FROM san_dim_tipo_muestra WHERE nombre = ?");
        mysqli_stmt_bind_param($check, "s", $nombre);
        mysqli_stmt_execute($check);
        $count = 0;
        mysqli_stmt_bind_result($check, $count);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);

        if ($count > 0) {
            throw new Exception('Ya existe un tipo de muestra con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "INSERT INTO san_dim_tipo_muestra (nombre, descripcion, lonCod) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssi", $nombre, $descripcion, $longitud_codigo);

    } elseif ($action === 'update') {
        if ($codigo === null || $codigo <= 0) {
            throw new Exception('Código no válido.');
        }

        $check = mysqli_prepare($conexion, "SELECT COUNT(*) FROM san_dim_tipo_muestra WHERE nombre = ? AND codigo != ?");
        mysqli_stmt_bind_param($check, "si", $nombre, $codigo);
        mysqli_stmt_execute($check);
        $count = 0;
        mysqli_stmt_bind_result($check, $count);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);

        if ($count > 0) {
            throw new Exception('Ya existe otro tipo de muestra con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "UPDATE san_dim_tipo_muestra SET nombre = ?, descripcion = ?, lonCod = ? WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "ssii", $nombre, $descripcion, $longitud_codigo, $codigo);

    } elseif ($action === 'delete') {
        if ($codigo === null || $codigo <= 0) {
            throw new Exception('Código no válido.');
        }

        // Verificar uso en paquetes
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) FROM san_dim_paquete WHERE tipoMuestra = ?");
        mysqli_stmt_bind_param($check, "i", $codigo);
        mysqli_stmt_execute($check);
        $count = 0;
        mysqli_stmt_bind_result($check, $count);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);

        if ($count > 0) {
            throw new Exception('No se puede eliminar: el tipo de muestra está en uso en ' . $count . ' paquete(s) de análisis.');
        }

        // Verificar uso en análisis
        $check2 = mysqli_prepare($conexion, "SELECT COUNT(*) FROM san_dim_paquete p
            WHERE p.tipoMuestra = ?");
        mysqli_stmt_bind_param($check2, "i", $codigo);
        mysqli_stmt_execute($check2);
        $count2 = 0;
        mysqli_stmt_bind_result($check2, $count2);
        mysqli_stmt_fetch($check2);
        mysqli_stmt_close($check2);

        if ($count2 > 0) {
            throw new Exception('No se puede eliminar: el tipo de muestra está en uso en ' . $count2 . ' análisis.');
        }

        $stmt = mysqli_prepare($conexion, "DELETE FROM san_dim_tipo_muestra WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "i", $codigo);

    } else {
        throw new Exception('Acción no válida.');
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error en la base de datos: ' . mysqli_error($conexion));
    }
    mysqli_stmt_close($stmt);

    mysqli_commit($conexion);

    // Registrar en historial de acciones
    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $nom_usuario = $_SESSION['nombre'] ?? $usuario;
    
    try {
        if ($action === 'create') {
            $codigoGenerado = mysqli_insert_id($conexion);
            $datos_nuevos = json_encode([
                'codigo' => $codigoGenerado,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'lonCod' => $longitud_codigo
            ], JSON_UNESCAPED_UNICODE);
            registrarAccion(
                $usuario,
                $nom_usuario,
                'INSERT',
                'san_dim_tipo_muestra',
                $codigoGenerado,
                null,
                $datos_nuevos,
                'Se creo un nuevo tipo de muestra',
                null
            );
        } elseif ($action === 'update') {
            $datos_nuevos = json_encode([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'lonCod' => $longitud_codigo
            ], JSON_UNESCAPED_UNICODE);
            registrarAccion(
                $usuario,
                $nom_usuario,
                'UPDATE',
                'san_dim_tipo_muestra',
                $codigo,
                null, // Podríamos obtener datos previos si es necesario
                $datos_nuevos,
                'Se actualizo un tipo de muestra',
                null
            );
        } elseif ($action === 'delete') {
            $datos_previos = json_encode([
                'codigo' => $codigo
            ], JSON_UNESCAPED_UNICODE);
            registrarAccion(
                $usuario,
                $nom_usuario,
                'DELETE',
                'san_dim_tipo_muestra',
                $codigo,
                $datos_previos,
                null,
                'Se elimino un tipo de muestra',
                null
            );
        }
    } catch (Exception $e) {
        error_log("Error al registrar historial de acciones: " . $e->getMessage());
    }

    $mensaje = '';
    switch ($action) {
        case 'create':
            $mensaje = '✅ Tipo de muestra creado correctamente.';
            break;
        case 'update':
            $mensaje = '✅ Tipo de muestra actualizado correctamente.';
            break;
        case 'delete':
            $mensaje = '✅ Tipo de muestra eliminado correctamente.';
            break;
    }

    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    mysqli_close($conexion);
}
?>