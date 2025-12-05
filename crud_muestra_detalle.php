<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$action = $_POST['action'] ?? '';
$codigoEnvio = trim($_POST['codigoEnvio'] ?? '');
$posicionSolicitud = isset($_POST['posicionSolicitud']) ? (int) $_POST['posicionSolicitud'] : null;
$fechaToma = $_POST['fechaToma'] ?? '';
$codigoReferencia = trim($_POST['codigoReferencia'] ?? '');
$numeroMuestras = isset($_POST['numeroMuestras']) ? (int) $_POST['numeroMuestras'] : null;
$observaciones = trim($_POST['observaciones'] ?? '');
$codigoOriginal = trim($_POST['codigoOriginal'] ?? '');
$posicionOriginal = isset($_POST['posicionOriginal']) ? (int) $_POST['posicionOriginal'] : null;

// Campo analisis inicializado vacío (JSON)
$analisis = '[]';

if ($action !== 'delete') {
    if (
        empty($codigoEnvio) || !$posicionSolicitud || empty($fechaToma) ||
        empty($codigoReferencia) || !$numeroMuestras
    ) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados.']);
        exit();
    }
}

mysqli_begin_transaction($conexion);

try {
    if ($action === 'create') {
        // Verificar que existe la cabecera
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_db_muestra_cabecera WHERE codigoEnvio = ?");
        mysqli_stmt_bind_param($check, "s", $codigoEnvio);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] == 0) {
            throw new Exception('No existe una cabecera de muestra con ese código de envío.');
        }

        // Verificar que no exista la combinación código-posición
        $check2 = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_db_muestra_detalle WHERE codigoEnvio = ? AND posicionSolicitud = ?");
        mysqli_stmt_bind_param($check2, "si", $codigoEnvio, $posicionSolicitud);
        mysqli_stmt_execute($check2);
        $row2 = mysqli_stmt_get_result($check2)->fetch_assoc();
        if ($row2['cnt'] > 0) {
            throw new Exception('Ya existe un detalle con esa posición para este código de envío.');
        }

        $stmt = mysqli_prepare(
            $conexion,
            "INSERT INTO com_db_muestra_detalle 
            (codigoEnvio, posicionSolicitud, fechaToma, codigoReferencia, numeroMuestras, observaciones, analisis) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "sississ",
            $codigoEnvio,
            $posicionSolicitud,
            $fechaToma,
            $codigoReferencia,
            $numeroMuestras,
            $observaciones,
            $analisis
        );

    } elseif ($action === 'update') {
        if (empty($codigoOriginal) || !$posicionOriginal)
            throw new Exception('Código o posición original no válidos.');

        // Verificar que existe la cabecera
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_db_muestra_cabecera WHERE codigoEnvio = ?");
        mysqli_stmt_bind_param($check, "s", $codigoEnvio);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] == 0) {
            throw new Exception('No existe una cabecera de muestra con ese código de envío.');
        }

        // Si cambió el código de envío o posición, verificar que no exista
        if ($codigoEnvio !== $codigoOriginal || $posicionSolicitud !== $posicionOriginal) {
            $check2 = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_db_muestra_detalle WHERE codigoEnvio = ? AND posicionSolicitud = ?");
            mysqli_stmt_bind_param($check2, "si", $codigoEnvio, $posicionSolicitud);
            mysqli_stmt_execute($check2);
            $row2 = mysqli_stmt_get_result($check2)->fetch_assoc();
            if ($row2['cnt'] > 0) {
                throw new Exception('Ya existe un detalle con esa posición para este código de envío.');
            }
        }

        // Mantener el campo análisis existente
        $queryAnalisis = mysqli_prepare($conexion, "SELECT analisis FROM com_db_muestra_detalle WHERE codigoEnvio = ? AND posicionSolicitud = ?");
        mysqli_stmt_bind_param($queryAnalisis, "si", $codigoOriginal, $posicionOriginal);
        mysqli_stmt_execute($queryAnalisis);
        $resultAnalisis = mysqli_stmt_get_result($queryAnalisis);
        $rowAnalisis = mysqli_fetch_assoc($resultAnalisis);
        $analisisExistente = $rowAnalisis['analisis'] ?? '[]';

        $stmt = mysqli_prepare(
            $conexion,
            "UPDATE com_db_muestra_detalle 
            SET codigoEnvio = ?, posicionSolicitud = ?, fechaToma = ?, codigoReferencia = ?, 
                numeroMuestras = ?, observaciones = ? 
            WHERE codigoEnvio = ? AND posicionSolicitud = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "sissiisi",
            $codigoEnvio,
            $posicionSolicitud,
            $fechaToma,
            $codigoReferencia,
            $numeroMuestras,
            $observaciones,
            $codigoOriginal,
            $posicionOriginal
        );

    } elseif ($action === 'delete') {
        if (empty($codigoEnvio) || !$posicionSolicitud)
            throw new Exception('Código o posición no válidos.');

        $stmt = mysqli_prepare($conexion, "DELETE FROM com_db_muestra_detalle WHERE codigoEnvio = ? AND posicionSolicitud = ?");
        mysqli_stmt_bind_param($stmt, "si", $codigoEnvio, $posicionSolicitud);

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
            $mensaje = '✅ Detalle de muestra creado correctamente.';
            break;
        case 'update':
            $mensaje = '✅ Detalle de muestra actualizado correctamente.';
            break;
        case 'delete':
            $mensaje = '✅ Detalle de muestra eliminado correctamente.';
            break;
    }

    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conexion);
?>