<?php
while (ob_get_level()) ob_end_clean();
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
        // Si ya está en UTF-8, devolverlo tal cual
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
ini_set('display_errors', 0); // Mantener en 0 pero con manejadores personalizados

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

// --- Validar token ---
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

if ($authHeader !== 'Bearer ' . API_TOKEN) {
    sendResponse(401, false, 'Acceso denegado. Token inválido', 'INVALID_TOKEN');
}

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

$usuario   = trim($obj['usuario'] ?? '');
$password  = trim($obj['password'] ?? '');
$android   = trim($obj['android'] ?? '');
$version   = trim($obj['version'] ?? '');
$app       = trim($obj['app'] ?? '');

if ($usuario === '' || $password === '' || $android === '' || $version === '') {
    sendResponse(400, false, 'Faltan datos requeridos', 'MISSING_DATA');
}

$new_password = base64_decode($password);
$fecha = date("Y-m-d");
$hora = date("H:i:s");
$fecha_hora_actual = date("Y-m-d H:i:s");

try {
    // 1. Verificar si el usuario existe
    $sql1 = "SELECT u.codigo, u.estado, u.nombre
             FROM usuario u 
             JOIN conempre c ON c.epre='RS' 
             WHERE u.codigo=?";
    $stmt1 = $conexion->prepare($sql1);
    
    if (!$stmt1) {
        sendResponse(500, false, 'Error en la consulta SQL', 'SQL_ERROR');
    }
    
    $stmt1->bind_param("s", $usuario);
    $stmt1->execute();
    $res1 = $stmt1->get_result();

    if ($res1->num_rows === 0) {
        sendResponse(404, false, 'Usuario no encontrado', 'USER_NOT_FOUND');
    }

    $user_data = $res1->fetch_assoc();
    
    if ($user_data['estado'] !== 'A') {
        sendResponse(403, false, 'Tu usuario está inactivo. Contacta al administrador.', 'USER_INACTIVE', [
            'usuario' => $usuario,
            'nombre' => convertToUtf8($user_data['nombre']),
            'estado' => 'inactivo'
        ]);
    }

    // 2. Verificar contraseña
    $sql2 = "SELECT u.codigo, u.nombre
             FROM usuario u
             JOIN conempre c ON c.epre='RS'
             WHERE u.codigo=? 
             AND u.password = LEFT(AES_ENCRYPT(?, c.enom), 8)
             AND u.estado='A'";
    $stmt2 = $conexion->prepare($sql2);
    
    if (!$stmt2) {
        sendResponse(500, false, 'Error en la consulta SQL', 'SQL_ERROR');
    }
    
    $stmt2->bind_param("ss", $usuario, $new_password);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    if ($res2->num_rows === 0) {
        sendResponse(401, false, 'Contraseña incorrecta', 'WRONG_PASSWORD');
    }

    $dato_usuario = [];
    while ($fila = $res2->fetch_assoc()) {
        $dato_usuario[] = array_map("convertToUtf8", $fila);
    }

    // 3. Verificar estado del dispositivo
    $sql3 = "SELECT tidandroid, testado, tfabricante, tmodelo 
             FROM regnewdispositivosandroid 
             WHERE tidandroid=? AND tdestino=?";
    $stmt3 = $conexion->prepare($sql3);
    
    if (!$stmt3) {
        sendResponse(500, false, 'Error en la consulta SQL', 'SQL_ERROR');
    }
    
    $stmt3->bind_param("ss", $android, $app);
    $stmt3->execute();
    $res3 = $stmt3->get_result();

    if ($res3->num_rows === 0) {
        sendResponse(403, false, 'Dispositivo no registrado. Por favor registra tu dispositivo primero.', 'DEVICE_NOT_REGISTERED');
    }

    $dispositivo_data = $res3->fetch_assoc();
    
    if ($dispositivo_data['testado'] !== 'A') {
        sendResponse(403, false, 'Tu dispositivo está inactivo. Contacta al administrador.', 'DEVICE_INACTIVE', [
            'android' => $android,
            'estado' => 'inactivo',
            'fabricante' => convertToUtf8($dispositivo_data['tfabricante']),
            'modelo' => convertToUtf8($dispositivo_data['tmodelo'])
        ]);
    }

    $dispositivo = [
        array_map("convertToUtf8", [
            'tidandroid' => $dispositivo_data['tidandroid']
        ])
    ];

    // 4. Actualizar info del dispositivo
    $sql4 = "UPDATE regnewdispositivosandroid 
             SET tversion=?, tuser_ing=?, tdate_ing=?, ttime_ing=? 
             WHERE tidandroid=? AND tdestino=?";
    $stmt4 = $conexion->prepare($sql4);
    
    if ($stmt4) {
        $stmt4->bind_param("ssssss", $version, $usuario, $fecha, $hora, $android, $app);
        $stmt4->execute();
    }

    // 5. Actualizar último acceso del usuario
    $sql5 = "UPDATE usuario 
             SET ultimo_acceso=? 
             WHERE codigo=?";
    $stmt5 = $conexion->prepare($sql5);
    
    if ($stmt5) {
        $stmt5->bind_param("ss", $fecha_hora_actual, $usuario);
        $stmt5->execute();
    }

    sendResponse(200, true, 'Inicio de sesión exitoso', null, [
        'usuario' => $dato_usuario,
        'dispositivo' => $dispositivo
    ]);

} catch (Exception $e) {
    sendResponse(500, false, 'Error inesperado', 'EXCEPTION', [
        'error' => $e->getMessage()
    ]);
}

$conexion->close();