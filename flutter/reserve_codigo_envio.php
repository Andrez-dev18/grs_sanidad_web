<?php
// --- Encabezados ---
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

include_once '../../conexion_grs_joya/conexion.php';
date_default_timezone_set('America/Lima');

// --- Conexión ---
$conexion = conectar_joya();


// --- 3. Conexión a BD ---

if (!$conexion) {
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error de conexión a la base de datos',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 4. Lógica de reserva de código ---
try {
   // BLOQUEAR TABLA
    mysqli_query($conexion, "LOCK TABLES san_fact_solicitud_cab WRITE");

    $anio_actual = date('y');

    // Obtener último código
    $sql = "
        SELECT codEnvio
        FROM san_fact_solicitud_cab
        WHERE codEnvio LIKE 'SAN-0{$anio_actual}%'
        ORDER BY codEnvio DESC
        LIMIT 1
    ";

    $res = mysqli_query($conexion, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $ultimo = $row['codEnvio'];
        $numero = intval(substr($ultimo, -4));
        $nuevo_numero = $numero + 1;
    } else {
        $nuevo_numero = 1;
    }

    // Generar código final
    $codigo = "SAN-0{$anio_actual}" . str_pad($nuevo_numero, 4, "0", STR_PAD_LEFT);

    // LIBERAR TABLAS
    mysqli_query($conexion, "UNLOCK TABLES");


    // ✅ Respuesta exitosa
    echo json_encode([
        'status' => 200,
        'success' => true,
        'message' => 'Código reservado correctamente',
        'data' => ['codigo_envio' => $codigo]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error interno al reservar código',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}

mysqli_close($conexion);
exit;