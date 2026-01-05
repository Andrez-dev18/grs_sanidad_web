<?php
// --- Encabezados CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('America/Lima');

// --- Token de autorización (igual que en tu otro endpoint funcional) ---
include_once '../../conexion_grs_joya/conexion.php';
include_once '../includes/historial_resultados.php';
include_once '../includes/historial_acciones.php';

// --- Conexión ---
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a base de datos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}
mysqli_autocommit($conexion, FALSE);

try {

    $fechaEnvio = $data['fechaEnvio'] ?? '';
    $horaEnvio = $data['horaEnvio'] ?? '';
    $codLab = $data['laboratorioCodigo'] ?? '';
    $nomLab = $data['laboratorioNombre'] ?? '';
    $codEmpTrans = $data['empresaTransporteCodigo'] ?? '';
    $nomEmpTrans = $data['empresaTransporteNombre'] ?? '';
    $usuarioRegistrador = $data['usuarioRegistrador'] ;
    $usuarioResponsable = $data['usuarioResponsable'] ?? '';
    $autorizadoPor = $data['autorizadoPor'] ?? '';

    $numeroSolicitudes = (int) ($data['numeroSolicitudes'] ?? 0);

    if (empty($fechaEnvio) || empty($horaEnvio) || empty($codLab) || empty($codEmpTrans) || $numeroSolicitudes <= 0) {
        throw new Exception('Faltan datos r equeridos en el formulario.');
    }

    function generarCodigoEnvio($conexion)
    {
        // Bloqueamos la tabla para evitar duplicados
        mysqli_query($conexion, "LOCK TABLES san_fact_solicitud_cab WRITE");

        try {
            $anio_actual = date('y');

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

            //liberar tabla
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

    $codigoEnvio = generarCodigoEnvio($conexion);

    $queryCabecera = "INSERT INTO san_fact_solicitud_cab (
            codEnvio, fecEnvio, horaEnvio, codLab, nomLab, codEmpTrans, nomEmpTrans, 
            usuarioRegistrador, usuarioResponsable, autorizadoPor, fechaHoraRegistro
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmtCabecera = mysqli_prepare($conexion, $queryCabecera);
    mysqli_stmt_bind_param(
        $stmtCabecera,
        "ssssssssss",
        $codigoEnvio,
        $fechaEnvio,
        $horaEnvio,
        $codLab,
        $nomLab,
        $codEmpTrans,
        $nomEmpTrans,
        $usuarioRegistrador,
        $usuarioResponsable,
        $autorizadoPor
    );

    if (!mysqli_stmt_execute($stmtCabecera)) {
        throw new Exception('Error al guardar la cabecera: ' . mysqli_error($conexion));
    }

    for ($i = 0; $i < $numeroSolicitudes; $i++) {
        $fechaToma = $data["fechaToma_{$i}"] ?? '';
        $codTipoMuestra = $data["tipoMuestraCodigo_{$i}"] ?? null;
        $nomTipoMuestra = $data["tipoMuestraNombre_{$i}"] ?? null;
        $codigoReferencia = $data["codigoReferenciaValue_{$i}"] ?? '';
        $observacionesMuestra = $data["observaciones_{$i}"] ?? '';
        $numeroMuestras = $data["numeroMuestras_{$i}"] ?? '';       
        $analisisArray = $data["analisisCompletos_{$i}"] ?? [];

        $queryDetalle = "INSERT INTO san_fact_solicitud_det (
                    codEnvio, posSolicitud, fecToma, numMuestras, codMuestra, nomMuestra, codPaquete, nomPaquete, codAnalisis, nomAnalisis, codRef, obs, id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtDetalle = mysqli_prepare($conexion, $queryDetalle);
        $posicionSolicitud = $i + 1;

        foreach ($analisisArray as $analisis) {
            $uuid = generar_uuid_v4();

            $codAnalisis = $analisis['analisis_codigo'] ?? null;
            $nomAnalisis = $analisis['analisis_nombre'] ?? null;
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

    // Registrar en historial de resultados
    $historialOk = insertarHistorial(
        $conexion,
        $codigoEnvio,
        0,                   // posSolicitud (0 = acción general)
        'ENVIO_REGISTRADO',  // acción
        null,                // tipo_analisis
        'Se registro el envio de muestra desde app móvil',
        $usuarioRegistrador,
        'Flutter'
    );

    if (!$historialOk) {
        throw new Exception('Error al registrar historial del envío');
    }

    // Registrar en historial de acciones
    $nom_usuario = $data['usuarioNombre'] ?? $usuarioRegistrador ?? 'Usuario Móvil';

    // Registrar cabecera
    $datosCabecera = json_encode([
        'codEnvio' => $codigoEnvio,
        'fechaEnvio' => $fechaEnvio,
        'horaEnvio' => $horaEnvio,
        'laboratorio' => $nomLab,
        'empresaTransporte' => $nomEmpTrans,
        'usuarioResponsable' => $usuarioResponsable,
        'autorizadoPor' => $autorizadoPor,
        'numeroSolicitudes' => $numeroSolicitudes
    ], JSON_UNESCAPED_UNICODE);

    try {
        registrarAccion(
            $usuarioRegistrador,
            $nom_usuario,
            'INSERT',
            'san_fact_solicitud_cab',
            $codigoEnvio,
            null,
            $datosCabecera,
            'Se registro un nuevo envío de muestra desde app móvil',
            'Flutter'
        );
    } catch (Exception $e) {
        error_log("Error al registrar historial de acciones (cabecera): " . $e->getMessage());
    }

    // Registrar detalles
    for ($i = 0; $i < $numeroSolicitudes; $i++) {
        $fechaToma = $data["fechaToma_{$i}"] ?? '';
        $codTipoMuestra = $data["tipoMuestraCodigo_{$i}"] ?? null;
        $nomTipoMuestra = $data["tipoMuestraNombre_{$i}"] ?? null;
        $codigoReferencia = $data["codigoReferenciaValue_{$i}"] ?? '';
        $observacionesMuestra = $data["observaciones_{$i}"] ?? '';
        $numeroMuestras = $data["numeroMuestras_{$i}"] ?? '';
        $analisisArray = $data["analisisCompletos_{$i}"] ?? [];
        
        if (empty($analisisArray)) {
            continue;
        }

        $posicionSolicitud = $i + 1;
        
        // Datos del detalle
        $datosDetalle = json_encode([
            'codEnvio' => $codigoEnvio,
            'posSolicitud' => $posicionSolicitud,
            'fechaToma' => $fechaToma,
            'tipoMuestra' => $nomTipoMuestra,
            'codigoReferencia' => $codigoReferencia,
            'numeroMuestras' => $numeroMuestras,
            'observaciones' => $observacionesMuestra,
            'analisis' => $analisisArray
        ], JSON_UNESCAPED_UNICODE);

        try {
            registrarAccion(
                $usuarioRegistrador,
                $nom_usuario,
                'INSERT',
                'san_fact_solicitud_det',
                $codigoEnvio . '-' . $posicionSolicitud,
                null,
                $datosDetalle,
                "Se registro detalle de solicitud #{$posicionSolicitud} desde app móvil",
                'Flutter'
            );
        } catch (Exception $e) {
            error_log("Error al registrar historial de acciones (detalle {$posicionSolicitud}): " . $e->getMessage());
        }
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
?>