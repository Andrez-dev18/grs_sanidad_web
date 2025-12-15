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
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($authHeader !== "Bearer " . API_TOKEN) {
    echo json_encode(["success" => false, "message" => "Token inválido"]);
    exit;
}

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
    $duplicados = 0;

    mysqli_autocommit($conexion, FALSE);

    foreach ($items as $item) {
        try {
            // === Validar datos mínimos ===
            if (empty($item['fechaEnvio']) || empty($item['horaEnvio']) || empty($item['laboratorio']) || empty($item['empresa_transporte']) || ($item['numeroSolicitudes'] ?? 0) <= 0) {
                continue; // O registrar error
            }

            // === Generar código de envío único ===
            $codigoEnvio = generarCodigoEnvioSanidad($conexion);

            // === Datos de cabecera ===
            $fechaEnvio = $item['fechaEnvio'];
            $horaEnvio = $item['horaEnvio'];
            $codLab = $item['laboratorio'];
            $codEmpTrans = $item['empresa_transporte'];
            $usuarioRegistrador = $item['usuario_registrador'] ?? 'app_movil';
            $usuarioResponsable = $item['usuario_responsable'] ?? '';
            $autorizadoPor = $item['autorizado_por'] ?? '';

            // === Validar laboratorio y empresa ===
            $stmtLab = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_laboratorio WHERE codigo = ?");
            mysqli_stmt_bind_param($stmtLab, "s", $codLab);
            mysqli_stmt_execute($stmtLab);
            $resultLab = mysqli_stmt_get_result($stmtLab);
            $laboratorio = mysqli_fetch_assoc($resultLab);
            mysqli_stmt_close($stmtLab);

            if (!$laboratorio) continue;
            $nomLab = $laboratorio['nombre'];

            $stmtEmp = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_emptrans WHERE codigo = ?");
            mysqli_stmt_bind_param($stmtEmp, "s", $codEmpTrans);
            mysqli_stmt_execute($stmtEmp);
            $resultEmp = mysqli_stmt_get_result($stmtEmp);
            $empTrans = mysqli_fetch_assoc($resultEmp);
            mysqli_stmt_close($stmtEmp);

            if (!$empTrans) continue;
            $nomEmpTrans = $empTrans['nombre'];

            // === Insertar cabecera ===
            $queryCab = "INSERT INTO san_fact_solicitud_cab (
                codEnvio, fecEnvio, horaEnvio, codLab, nomLab, codEmpTrans, nomEmpTrans,
                usuarioRegistrador, usuarioResponsable, autorizadoPor, fechaHoraRegistro,
                usuarioTransferencia, fechaHoraTransferencia
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

            $stmtCab = mysqli_prepare($conexion, $queryCab);
            $usuarioTrans = $item['usuarioTransferencia'] ?? 'app_movil';
            $fechaTrans = $item['fechaHoraTransferencia'] ?? date('Y-m-d H:i:s');
            mysqli_stmt_bind_param(
                $stmtCab,
                "sssssssssssss",
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
                $usuarioTrans,
                $fechaTrans
            );

            if (!mysqli_stmt_execute($stmtCab)) {
                throw new Exception('Error cabecera');
            }
            mysqli_stmt_close($stmtCab);

            // === Insertar detalles ===
            $numeroSolicitudes = (int)$item['numeroSolicitudes'];
            for ($i = 0; $i < $numeroSolicitudes; $i++) {
                $fechaToma = $item["fechaToma_{$i}"] ?? '';
                $codTipoMuestra = $item["tipoMuestra_{$i}"] ?? null;
                $codigoReferencia = $item["codigoReferenciaValue_{$i}"] ?? '';
                $observaciones = $item["observaciones_{$i}"] ?? '';
                $numeroMuestras = $item["numeroMuestras_{$i}"] ?? '';
                $analisisSeleccionados = $item["analisis_{$i}"] ?? [];

                if (empty($codTipoMuestra)) continue;

                $stmtTipo = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_tipo_muestra WHERE codigo = ?");
                mysqli_stmt_bind_param($stmtTipo, "s", $codTipoMuestra);
                mysqli_stmt_execute($stmtTipo);
                $resTipo = mysqli_stmt_get_result($stmtTipo);
                $tipoMuestra = mysqli_fetch_assoc($resTipo);
                $nomTipoMuestra = $tipoMuestra['nombre'];
                mysqli_stmt_close($stmtTipo);

                if (!empty($analisisSeleccionados)) {
                    $queryDet = "INSERT INTO san_fact_solicitud_det (
                        codEnvio, posSolicitud, fecToma, numMuestras, codMuestra, nomMuestra,
                        codAnalisis, nomAnalisis, codRef, obs, id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmtDet = mysqli_prepare($conexion, $queryDet);
                    $posSolicitud = $i + 1;

                    foreach ($analisisSeleccionados as $idAnalisis) {
                        $uuid = generar_uuid_v4();

                        $stmtAnal = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_analisis WHERE codigo = ?");
                        mysqli_stmt_bind_param($stmtAnal, "s", $idAnalisis);
                        mysqli_stmt_execute($stmtAnal);
                        $resAnal = mysqli_stmt_get_result($stmtAnal);
                        $analisis = mysqli_fetch_assoc($resAnal);
                        $nomAnalisis = $analisis['nombre'];
                        mysqli_stmt_close($stmtAnal);

                        mysqli_stmt_bind_param(
                            $stmtDet,
                            "sssssssssss",
                            $codigoEnvio,
                            $posSolicitud,
                            $fechaToma,
                            $numeroMuestras,
                            $codTipoMuestra,
                            $nomTipoMuestra,
                            $idAnalisis,
                            $nomAnalisis,
                            $codigoReferencia,
                            $observaciones,
                            $uuid
                        );

                        mysqli_stmt_execute($stmtDet);
                    }
                    mysqli_stmt_close($stmtDet);
                }
            }

            $insertados++;
        } catch (Exception $e) {
            // En producción, podrías loggear $e->getMessage()
            mysqli_rollback($conexion);
            mysqli_autocommit($conexion, TRUE);
            mysqli_autocommit($conexion, FALSE);
            continue; // o break
        }
    }

    mysqli_commit($conexion);
    mysqli_autocommit($conexion, TRUE);

    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

// === Funciones auxiliares (si no están ya definidas) ===

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

function generar_uuid_v4()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
echo json_encode([
    "success" => true,
    "message" => "Registros procesados",
    "detalle" => ["envios_muestras" => $respuesta]
], JSON_UNESCAPED_UNICODE);
