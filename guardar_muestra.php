<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_sanidad();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit();
}


mysqli_autocommit($conexion, FALSE);

try {

    $fechaEnvio = $_POST['fechaEnvio'] ?? '';
    $horaEnvio = $_POST['horaEnvio'] ?? '';
    $laboratorio = $_POST['laboratorio'] ?? '';
    $empTrans = $_POST['empresa_transporte'] ?? '';
    $usuarioRegistrador = $_POST['usuario_registrador'] ?? $_SESSION['usuario'] ?? 'Desconocido';
    $usuarioResponsable = $_POST['usuario_responsable'] ?? '';
    $autorizadoPor = $_POST['autorizado_por'] ?? '';

    $numeroSolicitudes = (int) ($_POST['numeroSolicitudes'] ?? 0);


    if (empty($fechaEnvio) || empty($horaEnvio) || empty($laboratorio) || $numeroSolicitudes <= 0) {
        throw new Exception('Faltan datos requeridos en el formulario.');
    }



    function generarCodigoEnvio($conexion)
    {
        $anio_actual = date('y');
        $res = mysqli_query($conexion, "SELECT ultimo_numero, anio FROM com_contador_codigo WHERE id = 1 FOR UPDATE");
        if (!$res || mysqli_num_rows($res) === 0) {
            mysqli_query($conexion, "INSERT INTO com_contador_codigo (id, ultimo_numero, anio) VALUES (1, 0, '$anio_actual')");
            $ultimo_numero = 0;
            $anio_db = $anio_actual;
        } else {
            $row = mysqli_fetch_assoc($res);
            $ultimo_numero = (int) $row['ultimo_numero'];
            $anio_db = $row['anio'];
        }

        if ($anio_db !== $anio_actual) {
            $nuevo_numero = 1;
            mysqli_query($conexion, "UPDATE com_contador_codigo SET ultimo_numero = 1, anio = '$anio_actual' WHERE id = 1");
        } else {
            $nuevo_numero = $ultimo_numero + 1;
            mysqli_query($conexion, "UPDATE com_contador_codigo SET ultimo_numero = $nuevo_numero WHERE id = 1");
        }
        return "SAN-0{$anio_actual}" . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);
    }

    $codigoRecibido = $_POST['codigoEnvio'] ?? '';

    if (!empty($codigoRecibido)) {

        $stmt = mysqli_prepare($conexion, "
            SELECT codigo FROM com_codigos_pendientes 
            WHERE codigo = ?
        ");
        mysqli_stmt_bind_param($stmt, "s", $codigoRecibido);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {

            $codigoEnvio = $codigoRecibido;

            mysqli_query($conexion, "DELETE FROM com_codigos_pendientes WHERE codigo = '$codigoEnvio'");
        } else {

            $codigoEnvio = generarCodigoEnvio($conexion);
        }
    } else {

        $codigoEnvio = generarCodigoEnvio($conexion);
    }


    $queryCabecera = "INSERT INTO com_db_muestra_cabecera (
            codigoEnvio, fechaEnvio, horaEnvio, laboratorio, empTrans, 
            usuarioRegistrador, usuarioResponsable, autorizadoPor, fechaRegistro
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmtCabecera = mysqli_prepare($conexion, $queryCabecera);
    mysqli_stmt_bind_param(
        $stmtCabecera,
        "ssssssss",
        $codigoEnvio,
        $fechaEnvio,
        $horaEnvio,
        $laboratorio,
        $empTrans,
        $usuarioRegistrador,
        $usuarioResponsable,
        $autorizadoPor
    );

    if (!mysqli_stmt_execute($stmtCabecera)) {
        throw new Exception('Error al guardar la cabecera: ' . mysqli_error($conexion));
    }


    for ($i = 0; $i < $numeroSolicitudes; $i++) {
        $fechaToma = $_POST["fechaToma_{$i}"] ?? '';
        $tipoMuestra = $_POST["tipoMuestra_{$i}"] ?? null;
        $codigoReferencia = $_POST["codigoReferenciaValue_{$i}"] ?? '';
        $observacionesMuestra = $_POST["observaciones_{$i}"] ?? '';       
        $numeroMuestras = $_POST["numeroMuestras_{$i}"] ?? '';
        $analisisSeleccionados = $_POST["analisis_{$i}"] ?? [];
       $analisisStr = !empty($analisisSeleccionados) ? implode(',', $analisisSeleccionados) : '';

        if ($tipoMuestra !== null) {
            $posicionSolicitud = $i + 1;
            $queryDetalle = "INSERT INTO com_db_muestra_detalle (
                    codigoEnvio, posicionSolicitud, numeroMuestras, fechaToma, codigoReferencia, observaciones, analisis
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmtDetalle = mysqli_prepare($conexion, $queryDetalle);
            mysqli_stmt_bind_param(
                $stmtDetalle,
                "sssssss",
                $codigoEnvio,
                $posicionSolicitud,
                $numeroMuestras,
                $fechaToma,
                $codigoReferencia,
                $observacionesMuestra,
                $analisisStr
            );

            if (!mysqli_stmt_execute($stmtDetalle)) {
                throw new Exception('Error al guardar el detalle de la muestra ' . ($i + 1) . ': ' . mysqli_error($conexion));
            }
            mysqli_stmt_close($stmtDetalle);
        }
    }


    mysqli_commit($conexion);


    echo json_encode([
        'status' => 'success',
        'message' => 'Registro guardado exitosamente',
        'codigoEnvio' => $codigoEnvio
    ]);

} catch (Exception $e) {

    mysqli_rollback($conexion);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

mysqli_close($conexion);
?>