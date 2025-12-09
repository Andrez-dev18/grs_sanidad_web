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
/*
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? "";
if ($authHeader !== "Bearer " . API_TOKEN) {
    echo json_encode([
        'success' => false,
        'message' => 'Token inválido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
*/
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
    $codLab = $data['laboratorio'] ?? '';
    $codEmpTrans = $data['empresa_transporte'] ?? '';
    $usuarioRegistrador = $data['usuario_registrador'] ?? $_SESSION['usuario'] ?? 'Desconocido';
    $usuarioResponsable = $data['usuario_responsable'] ?? '';
    $autorizadoPor = $data['autorizado_por'] ?? '';

    $numeroSolicitudes = (int) ($data['numeroSolicitudes'] ?? 0);


    if (empty($fechaEnvio) || empty($horaEnvio) || empty($codLab) || empty($codEmpTrans) || $numeroSolicitudes <= 0) {
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

    $codigoRecibido = $data['codigoEnvio'] ?? '';

    $codigoEnvio = generarCodigoEnvio($conexion);
   

    $stmt = mysqli_prepare($conexion, "SELECT nombre FROM com_laboratorio WHERE codigo = ?");
    mysqli_stmt_bind_param($stmt, "s", $codLab);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $laboratorio = mysqli_fetch_assoc($result);

    if (!$laboratorio) {
        die("Error: Laboratorio con código $codLab no existe.");
    }
    $nomLab = $laboratorio['nombre'];

    $stmt = mysqli_prepare($conexion, "SELECT nombre FROM com_emp_trans WHERE codigo = ?");


    mysqli_stmt_bind_param($stmt, "s", $codEmpTrans);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $empTrans = mysqli_fetch_assoc($result);

    if (!$empTrans) {
        die("Error: Laboratorio con código $codLab no existe.");
    }
    $nomEmpTrans = $empTrans['nombre'];

    $queryCabecera = "INSERT INTO com_db_solicitud_cab (
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
        $codTipoMuestra = $data["tipoMuestra_{$i}"] ?? null;
        $codigoReferencia = $data["codigoReferenciaValue_{$i}"] ?? '';
        $observacionesMuestra = $data["observaciones_{$i}"] ?? '';
        $numeroMuestras = $data["numeroMuestras_{$i}"] ?? '';
        $analisisSeleccionados = $data["analisis_{$i}"] ?? [];


        $stmt = mysqli_prepare($conexion, "SELECT nombre FROM com_tipo_muestra WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "s", $codTipoMuestra);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $tipoMuestra = mysqli_fetch_assoc($result);
        $nomTipoMuestra = $tipoMuestra['nombre'];
       
        if (!empty($analisisSeleccionados)) {
            $queryDetalle = "INSERT INTO com_db_solicitud_det (
                    codEnvio, posSolicitud, fecToma, numMuestras, codMuestra, nomMuestra, codAnalisis, nomAnalisis, codRef, obs, id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtDetalle = mysqli_prepare($conexion, $queryDetalle);
             $posicionSolicitud = 1;
            foreach ($analisisSeleccionados as $idAnalisis) {
                $uuid = generar_uuid_v4();

                $stmtAnalisis = mysqli_prepare($conexion, "SELECT nombre FROM com_analisis WHERE codigo = ?");
                mysqli_stmt_bind_param($stmtAnalisis, "s", $idAnalisis);
                mysqli_stmt_execute($stmtAnalisis);
                $result = mysqli_stmt_get_result($stmtAnalisis);
                $analisis = mysqli_fetch_assoc($result);
                $nomAnalisis = $analisis['nombre'];

                
                mysqli_stmt_bind_param(
                    $stmtDetalle,
                    "sssssssssss",
                    $codigoEnvio,
                    $posicionSolicitud,
                    $fechaToma,
                    $numeroMuestras,
                    $codTipoMuestra,
                    $nomTipoMuestra,
                    $idAnalisis,
                    $nomAnalisis,
                    $codigoReferencia,
                    $observacionesMuestra,
                    $uuid
                );

                mysqli_stmt_execute($stmtDetalle);
                $posicionSolicitud++;


            }
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