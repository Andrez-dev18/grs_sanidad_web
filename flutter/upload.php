<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Lima');

// --- Autenticación ---
include_once '../../conexion_grs_joya/conexion.php';
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
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(["success" => false, "message" => "Error conexión"]);
    exit;
}

// --- Leer JSON ---
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['envios_muestras']) || !is_array($data['envios_muestras'])) {
    echo json_encode(["success" => false, "message" => "Formato inválido"]);
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
                continue; // O registrar error
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

            // === Insertar detalles ===
            $numeroSolicitudes = (int) $item['numeroSolicitudes'];
            for ($i = 0; $i < $numeroSolicitudes; $i++) {
                $fechaToma = $item["fechaToma_{$i}"] ?? '';
                $codTipoMuestra = $item["tipoMuestraCodigo_{$i}"] ?? null;
                $nomTipoMuestra = $item["tipoMuestraNombre_{$i}"] ?? null;
                $codigoReferencia = $item["codigoReferenciaValue_{$i}"] ?? '';
                $observaciones = $item["observaciones_{$i}"] ?? '';
                $numeroMuestras = $item["numeroMuestras_{$i}"] ?? '';
                $analisisArray = $item["analisisCompletos_{$i}"] ?? [];

                if (empty($codTipoMuestra) || empty($analisisArray)) {
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
            }

            $insertados++;

        } catch (Exception $e) {
            // Si hay error, hacer rollback y continuar con el siguiente envío
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
    mysqli_query($conexion, "LOCK TABLES san_fact_solicitud_cab WRITE");
    $anio_actual = date('y');
    $sql = "SELECT codEnvio FROM san_fact_solicitud_cab WHERE codEnvio LIKE 'SAN-0{$anio_actual}%' ORDER BY codEnvio DESC LIMIT 1";
    $res = mysqli_query($conexion, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $ultimo = $row['codEnvio'];
        $numero = intval(substr($ultimo, -4));
        $nuevo_numero = $numero + 1;
    } else {
        $nuevo_numero = 1;
    }
    mysqli_query($conexion, "UNLOCK TABLES");
    return "SAN-0{$anio_actual}" . str_pad($nuevo_numero, 4, "0", STR_PAD_LEFT);
}

echo json_encode([
    "success" => true,
    "message" => "Registros procesados",
    "detalle" => ["envios_muestras" => $respuesta],

], JSON_UNESCAPED_UNICODE);