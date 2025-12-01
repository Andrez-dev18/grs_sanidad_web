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
$codigo = isset($_POST['codigo']) ? (int)$_POST['codigo'] : null;

if (empty($nombre) && $action !== 'delete') {
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
    exit();
}

mysqli_begin_transaction($conexion);

try {
    if ($action === 'create') {
        // Verificar que no exista un laboratorio con el mismo nombre
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_laboratorio WHERE nombre = ?");
        mysqli_stmt_bind_param($check, "s", $nombre);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('Ya existe un laboratorio con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "INSERT INTO com_laboratorio (nombre) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $nombre);
        
    } elseif ($action === 'update') {
        if (!$codigo) throw new Exception('Código no válido.');

        // Verificar que no exista otro laboratorio con el mismo nombre
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_laboratorio WHERE nombre = ? AND codigo != ?");
        mysqli_stmt_bind_param($check, "si", $nombre, $codigo);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('Ya existe otro laboratorio con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "UPDATE com_laboratorio SET nombre = ? WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "si", $nombre, $codigo);
        
    } elseif /*($action === 'delete') {
        if (!$codigo) throw new Exception('Código no válido.');

        // Verificar si el laboratorio está en uso en envíos
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_db_muestra_cabecera WHERE laboratorio = ?");
        mysqli_stmt_bind_param($check, "i", $codigo);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('No se puede eliminar: el laboratorio está en uso en ' . $row['cnt'] . ' envío(s).');
        }

        $stmt = mysqli_prepare($conexion, "DELETE FROM com_laboratorio WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "i", $codigo);
        
    } else {
        throw new Exception('Acción no válida.');
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error en la base de datos: ' . mysqli_error($conexion));
    }*/
        ($action === 'delete') {
        if (!$codigo) throw new Exception('Código no válido.');

        // Verificar si el laboratorio está en uso en envíos
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_db_muestra_cabecera WHERE laboratorio = ?");
        mysqli_stmt_bind_param($check, "i", $codigo);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('No se puede eliminar: el laboratorio está en uso en ' . $row['cnt'] . ' envío(s). Primero debe eliminar o reasignar los envíos asociados.');
        }

        // Intentar eliminar sin verificar auto_increment
        $stmt = mysqli_prepare($conexion, "DELETE FROM com_laboratorio WHERE codigo = ?");
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
            $mensaje = '✅ Laboratorio creado correctamente.';
            break;
        case 'update':
            $mensaje = '✅ Laboratorio actualizado correctamente.';
            break;
        case 'delete':
            $mensaje = '✅ Laboratorio eliminado correctamente.';
            break;
    }
    
    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conexion);
?>
