<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
include_once '../../includes/historial_acciones.php';
$conexion = conectar_joya();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit();
}

mysqli_autocommit($conexion, FALSE);

try {
    $codEnvio = $_POST['codigoEnvio'] ?? '';
    if (!$codEnvio) throw new Exception('Código de envío requerido');
    
    $fechaEnvio = $_POST['fechaEnvio'] ?? '';
    $horaEnvio = $_POST['horaEnvio'] ?? '';
    $codLab = $_POST['laboratorio'] ?? '';
    $codEmpTrans = $_POST['empresa_transporte'] ?? '';
    $usuarioRegistrador = $_POST['usuario_registrador'] ?? $_SESSION['usuario'] ?? 'Desconocido';
    $usuarioResponsable = $_POST['usuario_responsable'] ?? '';
    $autorizadoPor = $_POST['autorizado_por'] ?? '';

    if (empty($fechaEnvio) || empty($horaEnvio) || empty($codLab) || empty($codEmpTrans)) {
        throw new Exception('Faltan datos requeridos en la cabecera.');
    }

    // Obtener nombres
    $stmt = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_laboratorio WHERE codigo = ?");
    mysqli_stmt_bind_param($stmt, "s", $codLab);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $laboratorio = mysqli_fetch_assoc($result);
    if (!$laboratorio) throw new Exception("Laboratorio inválido: $codLab");
    $nomLab = $laboratorio['nombre'];

    $stmt = mysqli_prepare($conexion, "SELECT nombre FROM san_dim_emptrans WHERE codigo = ?");
    mysqli_stmt_bind_param($stmt, "s", $codEmpTrans);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $empTrans = mysqli_fetch_assoc($result);
    if (!$empTrans) throw new Exception("Empresa de transporte inválida: $codEmpTrans");
    $nomEmpTrans = $empTrans['nombre'];

    // Obtener datos anteriores de la cabecera para el historial
    $stmtAnterior = mysqli_prepare($conexion, "SELECT * FROM san_fact_solicitud_cab WHERE codEnvio = ?");
    mysqli_stmt_bind_param($stmtAnterior, "s", $codEnvio);
    mysqli_stmt_execute($stmtAnterior);
    $resultAnterior = mysqli_stmt_get_result($stmtAnterior);
    $datosAnterioresCab = mysqli_fetch_assoc($resultAnterior);
    $datosPreviosCabecera = $datosAnterioresCab ? json_encode($datosAnterioresCab, JSON_UNESCAPED_UNICODE) : null;

    // Obtener datos anteriores de los detalles para el historial
    $stmtDetAnterior = mysqli_prepare($conexion, "SELECT * FROM san_fact_solicitud_det WHERE codEnvio = ? ORDER BY posSolicitud");
    mysqli_stmt_bind_param($stmtDetAnterior, "s", $codEnvio);
    mysqli_stmt_execute($stmtDetAnterior);
    $resultDetAnterior = mysqli_stmt_get_result($stmtDetAnterior);
    $detallesAnteriores = [];
    while ($row = mysqli_fetch_assoc($resultDetAnterior)) {
        $detallesAnteriores[] = $row;
    }
    $datosPreviosDetalles = !empty($detallesAnteriores) ? json_encode($detallesAnteriores, JSON_UNESCAPED_UNICODE) : null;

    $queryUpdate = "UPDATE san_fact_solicitud_cab SET 
        fecEnvio = ?, horaEnvio = ?, codLab = ?, nomLab = ?, 
        codEmpTrans = ?, nomEmpTrans = ?, usuarioResponsable = ?, autorizadoPor = ?
        WHERE codEnvio = ?";

    $stmtUpdate = mysqli_prepare($conexion, $queryUpdate);
    mysqli_stmt_bind_param(
        $stmtUpdate,
        "sssssssss",
        $fechaEnvio,
        $horaEnvio,
        $codLab,
        $nomLab,
        $codEmpTrans,
        $nomEmpTrans,
        $usuarioResponsable,
        $autorizadoPor,
        $codEnvio
    );

    if (!mysqli_stmt_execute($stmtUpdate)) {
        throw new Exception('Error al actualizar la cabecera: ' . mysqli_error($conexion));
    }

    // 2. Eliminar detalles anteriores
    $stmtDel = mysqli_prepare($conexion, "DELETE FROM san_fact_solicitud_det WHERE codEnvio = ?");
    mysqli_stmt_bind_param($stmtDel, "s", $codEnvio);
    if (!mysqli_stmt_execute($stmtDel)) {
        throw new Exception('Error al eliminar detalles anteriores.');
    }

    // 3. Insertar nuevos detalles
    $numeroSolicitudes = 0;
    while (isset($_POST["fechaToma_" . ($numeroSolicitudes + 1)])) {
        $numeroSolicitudes++;
    }

    if ($numeroSolicitudes <= 0) {
        throw new Exception('Debe haber al menos una solicitud.');
    }

    $queryInsert = "INSERT INTO san_fact_solicitud_det (
        codEnvio, posSolicitud, fecToma, numMuestras, codMuestra, nomMuestra, 
        codPaquete, nomPaquete, codAnalisis, nomAnalisis, codRef, obs, id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtInsert = mysqli_prepare($conexion, $queryInsert);

    function generar_uuid_v4() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    for ($i = 1; $i <= $numeroSolicitudes; $i++) {
        $fechaToma = $_POST["fechaToma_{$i}"] ?? '';
        $codTipoMuestra = $_POST["tipoMuestra_{$i}"] ?? '';
        $nomTipoMuestra = $_POST["tipoMuestraNombre_{$i}"] ?? '';
        $codigoReferencia = $_POST["codigoReferenciaValue_{$i}"] ?? '';
        $observacionesMuestra = $_POST["observaciones_{$i}"] ?? '';
        $numeroMuestras = $_POST["numeroMuestras_{$i}"] ?? '1';

        // Validar campos requeridos de la solicitud
        if (empty($fechaToma) || empty($codTipoMuestra) || empty($codigoReferencia)) {
            throw new Exception("Solicitud #{$i}: Faltan datos requeridos (fecha de toma, tipo de muestra o código de referencia).");
        }

        $analisisJson = $_POST["analisis_completos_{$i}"] ?? '[]';
        $analisisArray = json_decode($analisisJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $analisisArray = [];
        }

        // Validar que haya al menos un análisis
        if (empty($analisisArray) || count($analisisArray) === 0) {
            throw new Exception("Solicitud #{$i}: Debe tener al menos un análisis seleccionado.");
        }

        // Validar que cada análisis tenga los datos necesarios
        foreach ($analisisArray as $idx => $analisis) {
            if (empty($analisis['codigo']) || empty($analisis['nombre'])) {
                throw new Exception("Solicitud #{$i}, Análisis #" . ($idx + 1) . ": Faltan código o nombre del análisis.");
            }
        }

        // Insertar cada análisis de la solicitud
        foreach ($analisisArray as $analisis) {
            $uuid = generar_uuid_v4(); 
            $codAnalisis = $analisis['codigo'] ?? null;
            $nomAnalisis = $analisis['nombre'] ?? null;
            $codPaquete = $analisis['paquete_codigo'] ?? null;
            $nomPaquete = $analisis['paquete_nombre'] ?? null;

            if (empty($codAnalisis) || empty($nomAnalisis)) {
                throw new Exception("Solicitud #{$i}: Análisis con datos incompletos.");
            }

            mysqli_stmt_bind_param(
                $stmtInsert,
                "sssssssssssss",
                $codEnvio,
                $i,
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

            if (!mysqli_stmt_execute($stmtInsert)) {
                throw new Exception('Error al insertar detalle de solicitud: ' . mysqli_error($conexion));
            }
        }
    }

    mysqli_commit($conexion);

    // Registrar en historial de acciones
    $nom_usuario = $_SESSION['nombre'] ?? $usuarioRegistrador;

    // Datos nuevos de la cabecera
    $datosNuevosCabecera = json_encode([
        'codEnvio' => $codEnvio,
        'fechaEnvio' => $fechaEnvio,
        'horaEnvio' => $horaEnvio,
        'laboratorio' => $nomLab,
        'empresaTransporte' => $nomEmpTrans,
        'usuarioResponsable' => $usuarioResponsable,
        'autorizadoPor' => $autorizadoPor
    ], JSON_UNESCAPED_UNICODE);

    // Registrar actualización de cabecera
    try {
        registrarAccion(
            $usuarioRegistrador,
            $nom_usuario,
            'UPDATE',
            'san_fact_solicitud_cab',
            $codEnvio,
            $datosPreviosCabecera,
            $datosNuevosCabecera,
            'Se actualizó la cabecera del envío',
            'GRS'
        );
    } catch (Exception $e) {
        error_log("Error al registrar historial de acciones (cabecera): " . $e->getMessage());
    }

    // Registrar eliminación de detalles anteriores
    if ($datosPreviosDetalles) {
        try {
            registrarAccion(
                $usuarioRegistrador,
                $nom_usuario,
                'DELETE',
                'san_fact_solicitud_det',
                $codEnvio,
                $datosPreviosDetalles,
                null,
                'Se eliminaron los detalles anteriores del envío',
                'GRS'
            );
        } catch (Exception $e) {
            error_log("Error al registrar historial de acciones (eliminación detalles): " . $e->getMessage());
        }
    }

    // Registrar inserción de nuevos detalles
    for ($i = 1; $i <= $numeroSolicitudes; $i++) {
        $fechaToma = $_POST["fechaToma_{$i}"] ?? '';
        $codTipoMuestra = $_POST["tipoMuestra_{$i}"] ?? '';
        $nomTipoMuestra = $_POST["tipoMuestraNombre_{$i}"] ?? '';
        $codigoReferencia = $_POST["codigoReferenciaValue_{$i}"] ?? '';
        $observacionesMuestra = $_POST["observaciones_{$i}"] ?? '';
        $numeroMuestras = $_POST["numeroMuestras_{$i}"] ?? '1';
        $analisisJson = $_POST["analisis_completos_{$i}"] ?? '[]';
        $analisisArray = json_decode($analisisJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || empty($analisisArray)) {
            continue;
        }

        $posicionSolicitud = $i;
        
        // Datos del detalle
        $datosDetalle = json_encode([
            'codEnvio' => $codEnvio,
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
                $codEnvio . '-' . $posicionSolicitud,
                null,
                $datosDetalle,
                "Se insertó detalle de solicitud #{$posicionSolicitud}",
                'GRS'
            );
        } catch (Exception $e) {
            error_log("Error al registrar historial de acciones (detalle {$posicionSolicitud}): " . $e->getMessage());
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Envío actualizado correctamente.']);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

mysqli_close($conexion);
?>