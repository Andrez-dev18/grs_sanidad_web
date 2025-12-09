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
    mysqli_autocommit($conexion, false);
    $anio_actual = date('y'); // Ej. "25" para 2025

    // Leer y bloquear el contador
    $res = mysqli_query($conexion, "SELECT ultimo_numero, anio FROM com_contador_codigo WHERE id = 1 FOR UPDATE");

    if (!$res || mysqli_num_rows($res) === 0) {
        // Inicializar el contador si no existe
        mysqli_query($conexion, "INSERT INTO com_contador_codigo (id, ultimo_numero, anio) VALUES (1, 0, '$anio_actual')");
        $ultimo_numero = 0;
        $anio_db = $anio_actual;
    } else {
        $row = mysqli_fetch_assoc($res);
        $ultimo_numero = (int) $row['ultimo_numero'];
        $anio_db = $row['anio'];
    }

    // Reiniciar si es un año nuevo
    if ($anio_db !== $anio_actual) {
        $nuevo_numero = 1;
        mysqli_query($conexion, "UPDATE com_contador_codigo SET ultimo_numero = 1, anio = '$anio_actual' WHERE id = 1");
    } else {
        $nuevo_numero = $ultimo_numero + 1;

    }

    $codigo = "SAN-0{$anio_actual}" . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);

    mysqli_commit($conexion);
    mysqli_autocommit($conexion, true);

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