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
function insertarHistorial(
    $conn,
    $codEnvio,
    $posSolicitud,
    $accion,
    $tipo_analisis = null,
    $comentario = null,
    $usuario = null,
    $ubicacion = null
) {
    // Usuario por defecto
    if (!$usuario && isset($_SESSION['usuario'])) {
        $usuario = $_SESSION['usuario'];
    }
    $usuario = $usuario ?: 'system';

    $stmt = $conn->prepare("
        INSERT INTO san_dim_historial_resultados 
        (codEnvio, posSolicitud, tipo_analisis, accion, comentario, usuario, ubicacion)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log("Error prepare historial: " . $conn->error);
        return false;
    }

    $stmt->bind_param("sisssss", $codEnvio, $posSolicitud, $tipo_analisis, $accion, $comentario, $usuario, $ubicacion);

    $exito = $stmt->execute();

    if (!$exito) {
        error_log("Error insert historial: " . $stmt->error);
    }

    $stmt->close();
    return $exito;
}
?>