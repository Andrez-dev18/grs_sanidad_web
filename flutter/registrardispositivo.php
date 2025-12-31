<?php
ob_start();

// Manejador de errores que devuelve JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error del servidor: ' . $errstr,
        'errorCode' => 'PHP_ERROR'
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 500,
            'success' => false,
            'message' => 'Error fatal del servidor',
            'errorCode' => 'FATAL_ERROR',
            'debug' => $error['message']
        ], JSON_UNESCAPED_UNICODE);
    }
});
function convertToUtf8($value) {
    if (is_string($value)) {
        
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }
        // Si no, convertir de ISO-8859-1 a UTF-8
        return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }
    return $value;
}
// --- CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once '../../conexion_grs_joya/conexion.php';

date_default_timezone_set('America/Lima');

function sendResponse($status, $success, $message, $errorCode = null, $data = null) {
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($status);
    
    $response = [
        'status' => $status,
        'success' => $success,
        'message' => $message
    ];
    
    if ($errorCode !== null) {
        $response['errorCode'] = $errorCode;
    }
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Verificar que API_TOKEN esté definido ---
if (!defined('API_TOKEN')) {
    sendResponse(500, false, 'Configuración incompleta del servidor', 'CONFIG_ERROR');
}

// --- Validar el token ---
$headers = getallheaders();
$authHeader = '';

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
    sendResponse(401, false, 'Acceso denegado. Token inválido', 'INVALID_TOKEN');
}

// --- Conexión ---
$conexion = conectar_joya();
if (!$conexion) {
    sendResponse(500, false, 'Error de conexión a la base de datos', 'DB_CONNECTION_ERROR');
}

// --- Obtener JSON del body ---
$json = file_get_contents('php://input');
$obj = json_decode($json, true);

if (!$obj || !is_array($obj)) {
    sendResponse(400, false, 'Formato JSON inválido o vacío', 'INVALID_JSON');
}

// --- Campos recibidos ---
$android     = trim($obj['android'] ?? '');
$fabricante  = trim($obj['fabricante'] ?? '');
$familia     = trim($obj['familia'] ?? '');
$modelo      = trim($obj['modelo'] ?? '');
$descripcion = trim($obj['descripcion'] ?? '');
$app         = trim($obj['app'] ?? '');

if ($android === "" || $fabricante === "" || $familia === "" || $modelo === "" || $descripcion === "") {
    sendResponse(400, false, 'Faltan datos requeridos', 'MISSING_DATA');
}

// --- Lógica de registro ---
$fecha = date("Y-m-d");
$hora = date("H:i:s");

try {
    // Verificar si el dispositivo ya existe
    $sql_check = "SELECT tidandroid, tfabricante, tmodelo, testado 
                  FROM regnewdispositivosandroid 
                  WHERE tidandroid=? AND tdestino=? 
                  LIMIT 1";
    $stmt = $conexion->prepare($sql_check);
    
    if (!$stmt) {
        sendResponse(500, false, 'Error en la consulta SQL', 'SQL_ERROR');
    }
    
    $stmt->bind_param("ss", $android, $app);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $estado = $row['testado'];
        
        if ($estado === 'A') {
            sendResponse(200, true, 'Este dispositivo ya está registrado y activo', 'DEVICE_ALREADY_ACTIVE', [
                'android' => $android,
                'estado' => 'activo',
                'fabricante' => convertToUtf8($row['tfabricante']),
                'modelo' => convertToUtf8($row['tmodelo'])
            ]);
        } else {
            sendResponse(200, false, 'Este dispositivo está registrado pero inactivo. Contacta al administrador.', 'DEVICE_INACTIVE', [
                'android' => $android,
                'estado' => 'inactivo',
                'fabricante' => convertToUtf8($row['tfabricante']),
                'modelo' => convertToUtf8($row['tmodelo'])
            ]);
        }
    }

    // Dispositivo NUEVO - Proceder con el registro
    $sql_insert = "INSERT INTO regnewdispositivosandroid
        (tdate, ttime, tidandroid, tfabricante, tfamilia, tmodelo, tdescripcion, testado, tdestino)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'A', ?)";
    $stmt2 = $conexion->prepare($sql_insert);
    
    if (!$stmt2) {
        sendResponse(500, false, 'Error al preparar la consulta de inserción', 'SQL_ERROR');
    }
    
    $stmt2->bind_param("ssssssss", $fecha, $hora, $android, $fabricante, $familia, $modelo, $descripcion, $app);

    if ($stmt2->execute()) {
        sendResponse(201, true, 'Dispositivo registrado correctamente', null, [
            'android' => $android,
            'fabricante' => $fabricante,
            'familia' => $familia,
            'modelo' => $modelo,
            'estado' => 'activo'
        ]);
    } else {
        sendResponse(500, false, 'Error al registrar dispositivo: ' . $conexion->error, 'DB_INSERT_ERROR');
    }

} catch (Exception $e) {
    sendResponse(500, false, 'Error inesperado en el servidor', 'EXCEPTION', [
        'error' => $e->getMessage()
    ]);
}

$conexion->close();