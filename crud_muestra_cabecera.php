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
$codigoEnvio = trim($_POST['codigoEnvio'] ?? '');
$fechaEnvio = $_POST['fechaEnvio'] ?? '';
$horaEnvio = $_POST['horaEnvio'] ?? '';
$laboratorio = isset($_POST['laboratorio']) ? (int)$_POST['laboratorio'] : null;
$empTrans = isset($_POST['empTrans']) ? (int)$_POST['empTrans'] : null;
$usuarioResponsable = trim($_POST['usuarioResponsable'] ?? '');
$autorizadoPor = trim($_POST['autorizadoPor'] ?? '');
$codigoOriginal = trim($_POST['codigoOriginal'] ?? '');

$usuarioRegistrador = $_SESSION['nombre'] ?? 'Sistema';

if ($action !== 'delete') {
    if (empty($codigoEnvio) || empty($fechaEnvio) || empty($horaEnvio) || 
        !$laboratorio || !$empTrans || empty($usuarioResponsable) || empty($autorizadoPor)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit();
    }
}

mysqli_begin_transaction($conexion);

try {
    if ($action === 'create') {
        // Verificar que no exista el código de envío
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_db_muestra_cabecera WHERE codigoEnvio = ?");
        mysqli_stmt_bind_param($check, "s", $codigoEnvio);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('Ya existe un envío con ese código.');
        }

        // Verificar que el laboratorio existe
        $check2 = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_laboratorio WHERE codigo = ?");
        mysqli_stmt_bind_param($check2, "i", $laboratorio);
        mysqli_stmt_execute($check2);
        $row2 = mysqli_stmt_get_result($check2)->fetch_assoc();
        if ($row2['cnt'] == 0) {
            throw new Exception('El laboratorio seleccionado no existe.');
        }

        // Verificar que la empresa de transporte existe
        $check3 = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_emp_trans WHERE codigo = ?");
        mysqli_stmt_bind_param($check3, "i", $empTrans);
        mysqli_stmt_execute($check3);
        $row3 = mysqli_stmt_get_result($check3)->fetch_assoc();
        if ($row3['cnt'] == 0) {
            throw new Exception('La empresa de transporte seleccionada no existe.');
        }

        $stmt = mysqli_prepare($conexion, 
            "INSERT INTO com_db_muestra_cabecera 
            (codigoEnvio, fechaEnvio, horaEnvio, laboratorio, empTrans, usuarioRegistrador, usuarioResponsable, autorizadoPor) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssiisss", $codigoEnvio, $fechaEnvio, $horaEnvio, $laboratorio, $empTrans, 
            $usuarioRegistrador, $usuarioResponsable, $autorizadoPor);
        
    } elseif ($action === 'update') {
        if (empty($codigoOriginal)) throw new Exception('Código original no válido.');

        // Verificar que el laboratorio existe
        $check2 = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_laboratorio WHERE codigo = ?");
        mysqli_stmt_bind_param($check2, "i", $laboratorio);
        mysqli_stmt_execute($check2);
        $row2 = mysqli_stmt_get_result($check2)->fetch_assoc();
        if ($row2['cnt'] == 0) {
            throw new Exception('El laboratorio seleccionado no existe.');
        }

        // Verificar que la empresa de transporte existe
        $check3 = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_emp_trans WHERE codigo = ?");
        mysqli_stmt_bind_param($check3, "i", $empTrans);
        mysqli_stmt_execute($check3);
        $row3 = mysqli_stmt_get_result($check3)->fetch_assoc();
        if ($row3['cnt'] == 0) {
            throw new Exception('La empresa de transporte seleccionada no existe.');
        }

        // Si cambió el código de envío, verificar que no exista
        if ($codigoEnvio !== $codigoOriginal) {
            $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_db_muestra_cabecera WHERE codigoEnvio = ?");
            mysqli_stmt_bind_param($check, "s", $codigoEnvio);
            mysqli_stmt_execute($check);
            $row = mysqli_stmt_get_result($check)->fetch_assoc();
            if ($row['cnt'] > 0) {
                throw new Exception('Ya existe un envío con ese código.');
            }

            // Actualizar también los detalles asociados
            $updateDetalle = mysqli_prepare($conexion, "UPDATE com_db_muestra_detalle SET codigoEnvio = ? WHERE codigoEnvio = ?");
            mysqli_stmt_bind_param($updateDetalle, "ss", $codigoEnvio, $codigoOriginal);
            if (!mysqli_stmt_execute($updateDetalle)) {
                throw new Exception('Error al actualizar detalles asociados.');
            }
        }

        $stmt = mysqli_prepare($conexion, 
            "UPDATE com_db_muestra_cabecera 
            SET codigoEnvio = ?, fechaEnvio = ?, horaEnvio = ?, laboratorio = ?, empTrans = ?, 
                usuarioResponsable = ?, autorizadoPor = ? 
            WHERE codigoEnvio = ?");
        mysqli_stmt_bind_param($stmt, "sssiisss", $codigoEnvio, $fechaEnvio, $horaEnvio, $laboratorio, $empTrans, 
            $usuarioResponsable, $autorizadoPor, $codigoOriginal);
        
    } elseif ($action === 'delete') {
        if (empty($codigoEnvio)) throw new Exception('Código no válido.');

        // Eliminar primero los detalles asociados
        $deleteDetalle = mysqli_prepare($conexion, "DELETE FROM com_db_muestra_detalle WHERE codigoEnvio = ?");
        mysqli_stmt_bind_param($deleteDetalle, "s", $codigoEnvio);
        mysqli_stmt_execute($deleteDetalle);

        $stmt = mysqli_prepare($conexion, "DELETE FROM com_db_muestra_cabecera WHERE codigoEnvio = ?");
        mysqli_stmt_bind_param($stmt, "s", $codigoEnvio);
        
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
            $mensaje = '✅ Registro de muestra creado correctamente.';
            break;
        case 'update':
            $mensaje = '✅ Registro de muestra actualizado correctamente.';
            break;
        case 'delete':
            $mensaje = '✅ Registro de muestra y sus detalles eliminados correctamente.';
            break;
    }
    
    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conexion);
?>
