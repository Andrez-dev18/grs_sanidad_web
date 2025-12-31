<?php

/**
 * Inserta un registro en el historial de resultados
 *
 * @param mysqli $conn Conexión activa a la BD
 * @param string $codEnvio
 * @param int $posSolicitud
 * @param string $accion
 * @param string|null $tipo_analisis 'cualitativo' o 'cuantitativo'
 * @param string|null $comentario
 * @param string|null $usuario
 * @param string|null $ubicacion 'GRS', 'Transporte' o 'Laboratorio'
 * @return bool
 */

date_default_timezone_set('America/Lima');  // Zona horaria de Perú

function insertarHistorial(
    $conn,
    $codEnvio,
    $posSolicitud,
    $accion,
    $tipo_analisis = null,
    $comentario = null,
    $usuario = null,
    $ubicacion = null,
    $evidencia = null
) {

    // === OBTENER FECHA Y HORA ACTUAL ===
    $fechaHoraActual = date('Y-m-d H:i:s');

    // Usuario por defecto
    if (!$usuario && isset($_SESSION['usuario'])) {
        $usuario = $_SESSION['usuario'];
    }
    $usuario = $usuario ?: 'system';

    $stmt = $conn->prepare("
        INSERT INTO san_dim_historial_resultados 
        (codEnvio, posSolicitud, tipo_analisis, accion, comentario, evidencia, usuario, ubicacion, fechaHoraRegistro)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log("Error prepare historial: " . $conn->error);
        return false;
    }

    $stmt->bind_param("sisssssss", $codEnvio, $posSolicitud, $tipo_analisis, $accion, $comentario, $evidencia, $usuario, $ubicacion, $fechaHoraActual);

    $exito = $stmt->execute();

    if (!$exito) {
        error_log("Error insert historial: " . $stmt->error);
    }

    $stmt->close();
    return $exito;
}
