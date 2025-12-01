<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include_once 'conexion_grs_joya/conexion.php';
$conexion = conectar_sanidad();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$action = $_POST['action'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$tipoMuestra = isset($_POST['tipoMuestra']) ? (int)$_POST['tipoMuestra'] : null;
$codigo = isset($_POST['codigo']) ? (int)$_POST['codigo'] : null;

if (empty($nombre) && $action !== 'delete') {
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
    exit();
}

if (!$tipoMuestra && $action !== 'delete') {
    echo json_encode(['success' => false, 'message' => 'Debe seleccionar un tipo de muestra.']);
    exit();
}

mysqli_begin_transaction($conexion);

try {
    if ($action === 'create') {
        // Verificar que el tipo de muestra existe
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_tipo_muestra WHERE codigo = ?");
        mysqli_stmt_bind_param($check, "i", $tipoMuestra);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] == 0) {
            throw new Exception('El tipo de muestra seleccionado no existe.');
        }

        // Verificar que no exista un paquete con el mismo nombre
        $check2 = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_paquetes_analisis WHERE nombre = ?");
        mysqli_stmt_bind_param($check2, "s", $nombre);
        mysqli_stmt_execute($check2);
        $row2 = mysqli_stmt_get_result($check2)->fetch_assoc();
        if ($row2['cnt'] > 0) {
            throw new Exception('Ya existe un paquete de análisis con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "INSERT INTO com_paquetes_analisis (nombre, tipoMuestra) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $nombre, $tipoMuestra);
        
    } elseif ($action === 'update') {
        if (!$codigo) throw new Exception('Código no válido.');

        // Verificar que el tipo de muestra existe
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_tipo_muestra WHERE codigo = ?");
        mysqli_stmt_bind_param($check, "i", $tipoMuestra);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] == 0) {
            throw new Exception('El tipo de muestra seleccionado no existe.');
        }

        // Verificar que no exista otro paquete con el mismo nombre
        $check2 = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_paquetes_analisis WHERE nombre = ? AND codigo != ?");
        mysqli_stmt_bind_param($check2, "si", $nombre, $codigo);
        mysqli_stmt_execute($check2);
        $row2 = mysqli_stmt_get_result($check2)->fetch_assoc();
        if ($row2['cnt'] > 0) {
            throw new Exception('Ya existe otro paquete de análisis con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "UPDATE com_paquetes_analisis SET nombre = ?, tipoMuestra = ? WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "sii", $nombre, $tipoMuestra, $codigo);
        
    } elseif ($action === 'delete') {
        if (!$codigo) throw new Exception('Código no válido.');

        // Verificar si el paquete está en uso en análisis
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_analisis WHERE PaqueteAnalisis = ?");
        mysqli_stmt_bind_param($check, "i", $codigo);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('No se puede eliminar: el paquete está en uso en ' . $row['cnt'] . ' análisis.');
        }

        $stmt = mysqli_prepare($conexion, "DELETE FROM com_paquetes_analisis WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "i", $codigo);
        
    } else {
        throw new Exception('Acción no válida.');
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error en la base de datos: ' . mysqli_error($conexion));
    }

    mysqli_commit($conexion);
    
    $mensaje = '';
    switch ($action) {
        case 'create':
            $mensaje = '✅ Paquete de análisis creado correctamente.';
            break;
        case 'update':
            $mensaje = '✅ Paquete de análisis actualizado correctamente.';
            break;
        case 'delete':
            $mensaje = '✅ Paquete de análisis eliminado correctamente.';
            break;
    }
    
    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conexion);
?>

