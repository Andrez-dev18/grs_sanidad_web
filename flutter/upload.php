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

// --- Autenticación ---
include_once '../../conexion_grs_joya/conexion.php';
include_once '../includes/historial_resultados.php';
include_once '../includes/historial_acciones.php';
/*$authHeader = '';

if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} else {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
    }
}

if ($authHeader !== "Bearer " . API_TOKEN) {
    echo json_encode(["success" => false, "message" => "Token inválido"], JSON_UNESCAPED_UNICODE);
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
    echo json_encode(['success' => false, 'message' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$data || !isset($data['envios_muestras']) || !is_array($data['envios_muestras'])) {
    echo json_encode(["success" => false, "message" => "Formato inválido: se requiere 'envios_muestras' como array"], JSON_UNESCAPED_UNICODE);
    exit;
}

$items = $data['envios_muestras'];
$respuesta = procesarEnviosMuestras($conexion, $items);


function procesarEnviosMuestras($conexion, $items)
{
    $insertados = 0;
    $duplicadosCab = 0;
    $duplicadosDet = 0;
    $codigosGenerados = [];
    mysqli_autocommit($conexion, FALSE);

    // Preparar statements para verificar duplicados
    $checkCab = $conexion->prepare("SELECT COUNT(*) FROM san_fact_solicitud_cab WHERE id = ?");
    if (!$checkCab) {
        return ["error" => "Error prepare check cabecera: " . $conexion->error];
    }

    $checkDet = $conexion->prepare("SELECT COUNT(*) FROM san_fact_solicitud_det WHERE id = ?");
    if (!$checkDet) {
        return ["error" => "Error prepare check detalle: " . $conexion->error];
    }

    foreach ($items as $item) {
        $id = $item['id'];
        $existeCab = 0; // Inicializar variable para PHP 7.2

        // Verificar si la cabecera ya existe
        $checkCab->bind_param("s", $id);
        $checkCab->execute();
        $checkCab->bind_result($existeCab);
        $checkCab->fetch();
        $checkCab->free_result();

        if ($existeCab > 0) {
            $duplicadosCab++;
            continue;
        }

        try {
            // === Validar datos mínimos ===
            if (empty($item['fechaEnvio']) || empty($item['horaEnvio']) || empty($item['laboratorioCodigo']) || empty($item['empresaTransporteCodigo']) || ($item['numeroSolicitudes'] ?? 0) <= 0) {
                error_log("Item con ID {$id} rechazado: Faltan datos requeridos");
                continue;
            }

            // === Generar código de envío único ===
            $codigoEnvio = generarCodigoEnvioSanidad($conexion);
            $fechaEnvio = $item['fechaEnvio'];
            $horaEnvio = $item['horaEnvio'];
            $codLab = $item['laboratorioCodigo'];
            $nomLab = $item['laboratorioNombre'];
            $codEmpTrans = $item['empresaTransporteCodigo'];
            $nomEmpTrans = $item['empresaTransporteNombre'];
            $usuarioRegistrador = $item['usuarioRegistrador'] ?? 'app_movil';
            $usuarioResponsable = $item['usuarioResponsable'] ?? '';
            $autorizadoPor = $item['autorizadoPor'] ?? '';
            $fechaHoraRegistro = $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s');
            $usuarioTransferencia = $item['usuarioTransferencia'] ?? 'app_movil';
            $fechaHoraTransferencia = $item['fechaHoraTransferencia'] ?? date('Y-m-d H:i:s');

            // === Insertar cabecera ===
            $queryCab = "INSERT INTO san_fact_solicitud_cab (
                codEnvio, fecEnvio, horaEnvio, codLab, nomLab, codEmpTrans, nomEmpTrans,
                usuarioRegistrador, usuarioResponsable, autorizadoPor, fechaHoraRegistro,
                usuarioTransferencia, fechaHoraTransferencia, id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmtCab = mysqli_prepare($conexion, $queryCab);

            mysqli_stmt_bind_param(
                $stmtCab,
                "ssssssssssssss",
                $codigoEnvio,
                $fechaEnvio,
                $horaEnvio,
                $codLab,
                $nomLab,
                $codEmpTrans,
                $nomEmpTrans,
                $usuarioRegistrador,
                $usuarioResponsable,
                $autorizadoPor,
                $fechaHoraRegistro,
                $usuarioTransferencia,
                $fechaHoraTransferencia,
                $id
            );

            if (!mysqli_stmt_execute($stmtCab)) {
                throw new Exception('Error cabecera: ' . mysqli_error($conexion));
            }
            mysqli_stmt_close($stmtCab);

            $codigosGenerados[] = [
                'id' => $id,
                'codigoEnvio' => $codigoEnvio
            ];

            // === Registrar en historial de resultados ===
            $historialOk = insertarHistorial(
                $conexion,
                $codigoEnvio,
                0,                   // posSolicitud (0 = acción general)
                'ENVIO_REGISTRADO',  // acción
                null,                // tipo_analisis
                'Se registro el envio de muestra desde app móvil (upload)',
                $usuarioRegistrador,
                'Flutter'
            );

            if (!$historialOk) {
                error_log("Error al registrar historial de resultados para codEnvio: {$codigoEnvio}");
                // No lanzamos excepción porque procesamos múltiples items, pero registramos el error
            }

            // === Registrar en historial de acciones (cabecera) ===
            $nom_usuario = $item['usuarioNombre'] ?? $usuarioRegistrador ?? 'Usuario Móvil';
            $numeroSolicitudes = (int) $item['numeroSolicitudes'];
            
            $datosCabecera = json_encode([
                'codEnvio' => $codigoEnvio,
                'fechaEnvio' => $fechaEnvio,
                'horaEnvio' => $horaEnvio,
                'laboratorio' => $nomLab,
                'empresaTransporte' => $nomEmpTrans,
                'usuarioResponsable' => $usuarioResponsable,
                'autorizadoPor' => $autorizadoPor,
                'numeroSolicitudes' => $numeroSolicitudes,
                'fechaHoraRegistro' => $fechaHoraRegistro,
                'usuarioTransferencia' => $usuarioTransferencia,
                'fechaHoraTransferencia' => $fechaHoraTransferencia
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
                    'Se registro un nuevo envío de muestra desde app móvil (upload)',
                    'Flutter'
                );
            } catch (Exception $e) {
                error_log("Error al registrar historial de acciones (cabecera): " . $e->getMessage());
            }

            // === Insertar detalles ===
            for ($i = 0; $i < $numeroSolicitudes; $i++) {
                $fechaToma = $item["fechaToma_{$i}"] ?? '';
                $codTipoMuestra = $item["tipoMuestraCodigo_{$i}"] ?? null;
                $nomTipoMuestra = $item["tipoMuestraNombre_{$i}"] ?? null;
                $codigoReferencia = $item["codigoReferenciaValue_{$i}"] ?? '';
                $observaciones = $item["observaciones_{$i}"] ?? '';
                $numeroMuestras = $item["numeroMuestras_{$i}"] ?? '';
                $analisisArray = $item["analisisCompletos_{$i}"] ?? [];

                if (empty($codTipoMuestra) || empty($analisisArray) || empty($fechaToma)) {
                    error_log("Solicitud #{$i} del item {$id} rechazada: Faltan datos requeridos (tipoMuestra, analisis o fechaToma)");
                    continue;
                }

                // Preparar el statement para insertar detalles
                $queryDet = "INSERT INTO san_fact_solicitud_det (
                    codEnvio, posSolicitud, fecToma, numMuestras, codMuestra, nomMuestra, 
                    codPaquete, nomPaquete, codAnalisis, nomAnalisis, codRef, obs, id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmtDet = mysqli_prepare($conexion, $queryDet);
                if (!$stmtDet) {
                    throw new Exception('Error preparando statement detalle: ' . mysqli_error($conexion));
                }

                $posSolicitud = $i + 1;

                // Para cada análisis, insertar un registro
                foreach ($analisisArray as $analisis) {
                    $idSolicitud = $analisis['analisis_id'] ?? null;
                    $existeDet = 0; // Inicializar variable para PHP 7.2

                    if (!$idSolicitud) {
                        continue; // Si no hay ID, no podemos verificar ni insertar
                    }

                    // Verificar si el detalle ya existe
                    $checkDet->bind_param("s", $idSolicitud);
                    $checkDet->execute();
                    $checkDet->bind_result($existeDet);
                    $checkDet->fetch();
                    $checkDet->free_result();

                    if ($existeDet > 0) {
                        $duplicadosDet++;
                        continue;
                    }

                    $codAnalisis = $analisis['analisis_codigo'] ?? null;
                    $nomAnalisis = $analisis['analisis_nombre'] ?? null;
                    $codPaquete = $analisis['paquete_codigo'] ?? null;
                    $nomPaquete = $analisis['paquete_nombre'] ?? null;

                    mysqli_stmt_bind_param(
                        $stmtDet,
                        "sssssssssssss",
                        $codigoEnvio,
                        $posSolicitud,
                        $fechaToma,
                        $numeroMuestras,
                        $codTipoMuestra,
                        $nomTipoMuestra,
                        $codPaquete,
                        $nomPaquete,
                        $codAnalisis,
                        $nomAnalisis,
                        $codigoReferencia,
                        $observaciones,
                        $idSolicitud
                    );

                    if (!mysqli_stmt_execute($stmtDet)) {
                        mysqli_stmt_close($stmtDet);
                        throw new Exception('Error ejecutando detalle: ' . mysqli_error($conexion));
                    }
                }

                mysqli_stmt_close($stmtDet);

                // === Registrar en historial de acciones (detalle) ===
                if (!empty($analisisArray)) {
                    $datosDetalle = json_encode([
                        'codEnvio' => $codigoEnvio,
                        'posSolicitud' => $posSolicitud,
                        'fechaToma' => $fechaToma,
                        'tipoMuestra' => $nomTipoMuestra,
                        'codigoReferencia' => $codigoReferencia,
                        'numeroMuestras' => $numeroMuestras,
                        'observaciones' => $observaciones,
                        'analisis' => $analisisArray
                    ], JSON_UNESCAPED_UNICODE);

                    try {
                        registrarAccion(
                            $usuarioRegistrador,
                            $nom_usuario,
                            'INSERT',
                            'san_fact_solicitud_det',
                            $codigoEnvio . '-' . $posSolicitud,
                            null,
                            $datosDetalle,
                            "Se registro detalle de solicitud #{$posSolicitud} desde app móvil (upload)",
                            'Flutter'
                        );
                    } catch (Exception $e) {
                        error_log("Error al registrar historial de acciones (detalle {$posSolicitud}): " . $e->getMessage());
                    }
                }
            }

            $insertados++;

        } catch (Exception $e) {
            // Si hay error, hacer rollback y continuar con el siguiente envío
            error_log("Error procesando item con ID {$id}: " . $e->getMessage());
            mysqli_rollback($conexion);
            mysqli_autocommit($conexion, FALSE);
            continue;
        }
    }

    // Cerrar los statements de verificación
    $checkCab->close();
    $checkDet->close();

    mysqli_commit($conexion);
    mysqli_autocommit($conexion, TRUE);

    return [
        "insertados" => $insertados,
        "duplicadosCab" => $duplicadosCab,
        "duplicadosDet" => $duplicadosDet,
        "codigosGenerados" => $codigosGenerados
    ];
}

// === Funciones auxiliares ===

function generarCodigoEnvioSanidad($conexion)
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

echo json_encode([
    "success" => true,
    "message" => "Registros procesados",
    "detalle" => ["envios_muestras" => $respuesta]
], JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>