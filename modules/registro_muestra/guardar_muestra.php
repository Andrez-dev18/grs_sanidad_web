<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

//ruta relativa a la conexion
include_once '../../../conexion_grs_joya/conexion.php';
include_once '../../includes/historial_resultados.php';
$conexion = conectar_joya();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit();
}


mysqli_autocommit($conexion, FALSE);

try {
    $id =  $_POST['id'] ?? '';
    $fechaEnvio = $_POST['fechaEnvio'] ?? '';
    $horaEnvio = $_POST['horaEnvio'] ?? '';
    $codLab = $_POST['laboratorio'] ?? '';
    $codEmpTrans = $_POST['empresa_transporte'] ?? '';
    $usuarioRegistrador = $_POST['usuario_registrador'] ?? $_SESSION['usuario'];
    $usuarioTransferencia = $_SESSION['usuario'];
    $usuarioResponsable = $_POST['usuario_responsable'] ?? '';
    $autorizadoPor = $_POST['autorizado_por'] ?? '';

    $numeroSolicitudes = (int) ($_POST['numeroSolicitudes'] ?? 0);


    if (empty($fechaEnvio) || empty($horaEnvio) || empty($codLab) || empty($codEmpTrans) || $numeroSolicitudes <= 0) {
        throw new Exception('Faltan datos requeridos en el formulario.');
    }

    function generarCodigoEnvio($conexion)
    {
        // Bloqueamos la tabla para evitar duplicados
        mysqli_query($conexion, "LOCK TABLES san_fact_solicitud_cab WRITE");

        try {
            $anio_actual = date('y'); // "25" para 2025

            // Obtener el último código generado
            $q = "
            SELECT codEnvio
            FROM san_fact_solicitud_cab
            WHERE codEnvio LIKE 'SAN-0{$anio_actual}%'
            ORDER BY codEnvio DESC
            LIMIT 1
        ";

            $res = mysqli_query($conexion, $q);

            if ($res && mysqli_num_rows($res) > 0) {
                $row = mysqli_fetch_assoc($res);
                $ultimo = $row['codEnvio'];   // Ej. SAN-0250002

                // Extraemos el número final
                $numero = intval(substr($ultimo, -4)); // 0002 → 2
                $nuevo_numero = $numero + 1;
            } else {
                // Si no hay registros este año, empezamos desde 1
                $nuevo_numero = 1;
            }

            // Construimos el nuevo código
            $codigo = "SAN-0{$anio_actual}" . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);

            // IMPORTANTE: liberar tabla
            mysqli_query($conexion, "UNLOCK TABLES");

            return $codigo;
        } catch (Exception $e) {

            mysqli_query($conexion, "UNLOCK TABLES");
            throw new Exception("Error generando código: " . $e->getMessage());
        }
    }
    function generar_uuid_v4()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits para time_low
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits para time_mid
            mt_rand(0, 0xffff),
            // 16 bits para time_hi_and_version (4 indica versión 4)
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits para clock_seq_hi_and_reserved (2 bits más significativos = 10xx)
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits para node
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    $codigoRecibido = $_POST['codigoEnvio'] ?? '';

    $codigoEnvio = generarCodigoEnvio($conexion);

    $stmt = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_laboratorio WHERE codigo = ?");
    mysqli_stmt_bind_param($stmt, "s", $codLab);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $laboratorio = mysqli_fetch_assoc($result);

    if (!$laboratorio) {
        die("Error: Laboratorio con código $codLab no existe.");
    }
    $nomLab = $laboratorio['nombre'];

    $stmt = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_emptrans WHERE codigo = ?");


    mysqli_stmt_bind_param($stmt, "s", $codEmpTrans);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $empTrans = mysqli_fetch_assoc($result);

    if (!$empTrans) {
        die("Error: Laboratorio con código $codLab no existe.");
    }
    $nomEmpTrans = $empTrans['nombre'];

    $queryCabecera = "INSERT INTO san_fact_solicitud_cab (
            id, codEnvio, fecEnvio, horaEnvio, codLab, nomLab, codEmpTrans, nomEmpTrans, 
            usuarioResponsable, autorizadoPor, fechaHoraRegistro, usuarioRegistrador, usuarioTransferencia, fechaHoraTransferencia
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?,  ?, NOW())";

    $stmtCabecera = mysqli_prepare($conexion, $queryCabecera);
    mysqli_stmt_bind_param(
        $stmtCabecera,
        "ssssssssssss",
        $id,
        $codigoEnvio,
        $fechaEnvio,
        $horaEnvio,
        $codLab,
        $nomLab,
        $codEmpTrans,
        $nomEmpTrans,
       
        $usuarioResponsable,
        $autorizadoPor,
        $usuarioRegistrador,
        $usuarioTransferencia
    );

    if (!mysqli_stmt_execute($stmtCabecera)) {
        throw new Exception('Error al guardar la cabecera: ' . mysqli_error($conexion));
    }

    for ($i = 0; $i < $numeroSolicitudes; $i++) {
        $fechaToma = $_POST["fechaToma_{$i}"] ?? '';
        $codTipoMuestra = $_POST["tipoMuestra_{$i}"] ?? null;

        $nomTipoMuestra = $_POST["tipoMuestraNombre_{$i}"] ?? null;

        $codigoReferencia = $_POST["codigoReferenciaValue_{$i}"] ?? '';
        $observacionesMuestra = $_POST["observaciones_{$i}"] ?? '';
        $numeroMuestras = $_POST["numeroMuestras_{$i}"] ?? '';
        //$analisisSeleccionados = $_POST["analisis_{$i}"] ?? [];
        $analisisArray = $_POST["analisis_completos_{$i}"] ?? [];


        /*$stmt = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_tipo_muestra WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "s", $codTipoMuestra);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $tipoMuestra = mysqli_fetch_assoc($result);
        $nomTipoMuestra = $tipoMuestra['nombre'];
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Manejo de error si el JSON es inválido
            error_log("JSON inválido en analisis_completos_{$i}: " . $analisisJson);
            $analisisArray = [];
        }*/
        $analisisArray = json_decode($analisisArray, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("❌ JSON inválido en solicitud {$i}: " . json_last_error_msg());
            continue;
        }

        $queryDetalle = "INSERT INTO san_fact_solicitud_det (
                    codEnvio, posSolicitud, fecToma, numMuestras, codMuestra, nomMuestra, codPaquete, nomPaquete, codAnalisis, nomAnalisis, codRef, obs, id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtDetalle = mysqli_prepare($conexion, $queryDetalle);
        $posicionSolicitud = $i + 1;

        foreach ($analisisArray as $analisis) {
            $uuid = generar_uuid_v4();

            /*$stmtAnalisis = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_analisis WHERE codigo = ?");
            mysqli_stmt_bind_param($stmtAnalisis, "s", $analisis['codigo']);
            mysqli_stmt_execute($stmtAnalisis);
            $result = mysqli_stmt_get_result($stmtAnalisis);
            $analisis = mysqli_fetch_assoc($result);
            $nomAnalisis = $analisis['nombre'];*/

            $codAnalisis = $analisis['codigo'] ?? null;
            $nomAnalisis = $analisis['nombre'] ?? null;
            $codPaquete = $analisis['paquete_codigo'] ?? null;
            $nomPaquete = $analisis['paquete_nombre'] ?? null;


            mysqli_stmt_bind_param(
                $stmtDetalle,
                "sssssssssssss",
                $codigoEnvio,
                $posicionSolicitud,
                $fechaToma,
                $numeroMuestras,
                $codTipoMuestra,
                $nomTipoMuestra,
                $codPaquete,
                $nomPaquete,
                $codAnalisis,
                $nomAnalisis,
                $codigoReferencia,
                $observacionesMuestra,
                $uuid
            );

            mysqli_stmt_execute($stmtDetalle);
        }
    }

    $historialOk = insertarHistorial(
        $conexion,
        $codigoEnvio,        // codEnvio
        0,                   // posSolicitud (0 = acción general)
        'ENVIO_REGISTRADO',  // acción
        null,                // tipo_analisis
        'Se registro el envio de muestra',
        $usuarioRegistrador,
        'GRS'
    );

    if (!$historialOk) {
        throw new Exception('Error al registrar historial del envío');
    }

    
    mysqli_commit($conexion);
    // mysqli_stmt_close($stmtDetalle);

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
