<?php
// get_tipo_muestra.php — Compatible con web (sesión) y móvil (token)

while (ob_get_level())
    ob_end_clean();
ob_start();
header('Content-Type: application/json; charset=utf-8');

include_once '../conexion_grs_joya/conexion.php';

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}

function sendJson($data, $status = 200)
{
    http_response_code($status);
    if (ob_get_level())
        ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario_id = null;
$isMobile = false;

// --- Detectar si es móvil (tiene Bearer token válido) ---
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
if (!$authHeader) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
}

if ($authHeader && preg_match('/Bearer\s+(.*)/i', $authHeader, $matches)) {
    $token = $matches[1];
    if (defined('API_TOKEN') && $token === API_TOKEN) {
        $isMobile = true;
        $usuario_id = $_SERVER['HTTP_X_USER_ID'] ?? null;
        if (!$usuario_id) {
            $allHeaders = getallheaders();
            $usuario_id = $allHeaders['X-User-ID'] ?? $allHeaders['x-user-id'] ?? null;
        }
        if (!$usuario_id) {
            sendJson([
                'success' => false,
                'status' => 400,
                'message' => 'Falta X-User-ID',
                'data' => null
            ], 400);
        }
        $usuario_id = trim($usuario_id);
        if ($usuario_id === '') {
            sendJson([
                'success' => false,
                'status' => 400,
                'message' => 'Usuario inválido',
                'data' => null
            ], 400);
        }
    }
}

// --- Si no es móvil, usar sesión (web) ---
if (!$isMobile) {
    session_start();
    if (empty($_SESSION['active'])) {
        sendJson(['error' => 'No autorizado'], 401);
    }
    $usuario_id = $_SESSION['usuario'] ?? 'WEB_USER';
}

// --- Validar parámetro 'tipo' ---
$tipoMuestraId = $_GET['tipo'] ?? null;
if (!$tipoMuestraId || !is_numeric($tipoMuestraId)) {
    if ($isMobile) {
        sendJson(['success' => false, 'status' => 400, 'message' => 'Tipo de muestra inválido', 'data' => null], 400);
    } else {
        sendJson(['error' => 'Tipo de muestra inválido'], 400);
    }
}

$conexion = conectar_joya();
if (!$conexion) {
    if ($isMobile) {
        sendJson(['success' => false, 'status' => 500, 'message' => 'Error de conexión', 'data' => null], 500);
    } else {
        sendJson(['error' => 'Error de conexión'], 500);
    }
}

try {
    // Consultar tipo de muestra
    $tm = mysqli_fetch_assoc(mysqli_query($conexion, "
        SELECT codigo, nombre, lonCod 
        FROM com_tipo_muestra 
        WHERE codigo = " . (int) $tipoMuestraId . "
        
    "));

    if (!$tm) {
        if ($isMobile) {
            sendJson(['success' => false, 'status' => 404, 'message' => 'Tipo de muestra no encontrado', 'data' => null], 404);
        } else {
            sendJson(['error' => 'Tipo de muestra no encontrado'], 404);
        }
    }

    // Consultar paquetes
    $paquetes = [];
    $paquetes_res = mysqli_query($conexion, "
        SELECT codigo, nombre 
        FROM com_paquete_muestra 
        WHERE tipoMuestra = " . (int) $tipoMuestraId . " 
        ORDER BY nombre
    ");
    while ($row = mysqli_fetch_assoc($paquetes_res)) {
        $paquetes[] = $row;
    }

    // Consultar análisis
    $analisis = [];
    $analisis_res = mysqli_query($conexion, "
            SELECT A.codigo, A.nombre, A.paquete 
            FROM com_analisis A
            JOIN com_paquete_muestra P ON A.paquete = P.codigo
            WHERE P.tipoMuestra = " . (int) $tipoMuestraId . " 
            ORDER BY nombre
    ");
    while ($row = mysqli_fetch_assoc($analisis_res)) {
        $analisis[] = [
            'codigo' => (int) $row['codigo'],
            'nombre' => $row['nombre'],
            'paquete' => $row['paquete'] ? (int) $row['paquete'] : null
        ];
    }

    $response = [
        'tipo_muestra' => [
            'codigo' => (int) $tm['codigo'],
            'nombre' => $tm['nombre'],
            'longitud_codigo' => (int) $tm['lonCod']
        ],
        'paquetes' => $paquetes,
        'analisis' => $analisis
    ];

    // ✅ Formato según el cliente
    if ($isMobile) {
        sendJson([
            'success' => true,
            'status' => 200,
            'message' => 'OK',
            'data' => $response
        ]);
    } else {
        sendJson($response);
    }

} catch (Exception $e) {
    if ($isMobile) {
        sendJson(['success' => false, 'status' => 500, 'message' => 'Error interno', 'data' => null], 500);
    } else {
        sendJson(['error' => 'Error interno'], 500);
    }
}
?>