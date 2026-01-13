<?php
ob_start();

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

include_once '../../../conexion_grs_joya/conexion.php';
date_default_timezone_set('America/Lima');

// --- Conexión ---
$conexion = conectar_joya();

if (ob_get_level()) {
    ob_clean();
}
if (!$conexion) {
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error de conexión: ' . mysqli_connect_error()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Configurar charset de conexión para evitar problemas de codificación ---
mysqli_set_charset($conexion, "latin1");

// --- Consulta CENCOS ---
$query = "
    SELECT 
        a.codigo,
        a.nombre,
        CAST(IFNULL(b.edad, 0) AS UNSIGNED) AS edad 
    FROM ccos AS a 
    LEFT JOIN (
        SELECT 
            a.tcencos,
            DATEDIFF(NOW(), MIN(a.fec_ing)) + 1 AS edad 
        FROM maes_zonas AS a 
        WHERE a.tcodigo IN ('P0001001','P0001002')  
        GROUP BY a.tcencos
    ) AS b ON a.codigo = b.tcencos  
    WHERE 
        LEFT(a.codigo, 1) IN ('6', '5') 
        AND RIGHT(a.codigo, 3) <> '000' 
        AND a.swac = 'A' 
        AND CHAR_LENGTH(a.codigo) = 6 
        AND LEFT(a.codigo, 3) <> '650'
        AND CAST(LEFT(a.codigo, 3) AS UNSIGNED) <= 667
        AND IFNULL(b.edad, 0) > 0
    ORDER BY a.codigo ASC
";

$result = mysqli_query($conexion, $query);

// --- Verificar errores en la consulta ---
if (!$result) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error en la consulta SQL: ' . mysqli_error($conexion),
        'error_code' => mysqli_errno($conexion),
        'query' => $query
    ], JSON_UNESCAPED_UNICODE);
    mysqli_close($conexion);
    exit;
}

function resultToArray($result) {
    $rows = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$cencos = resultToArray($result);
$numRows = count($cencos);

// --- Limpiar buffer y responder ---
if (ob_get_level()) {
    ob_clean();
}

// --- Incluir información de depuración si no hay resultados ---
$response = [
    'status' => 200,
    'success' => true,
    'message' => $numRows > 0 ? 'CENCOS cargados correctamente' : 'No se encontraron CENCOS con los criterios especificados',
    'data' => [
        'cencos' => $cencos,
        'total' => $numRows
    ]
];

// Si no hay resultados, agregar información de depuración
if ($numRows === 0) {
    // Ejecutar consulta sin el filtro de edad para ver cuántos registros hay en total
    $queryDebug = "
        SELECT COUNT(*) as total
        FROM ccos AS a 
        LEFT JOIN (
            SELECT 
                a.tcencos,
                DATEDIFF(NOW(), MIN(a.fec_ing)) + 1 AS edad 
            FROM maes_zonas AS a 
            WHERE a.tcodigo IN ('P0001001','P0001002')  
            GROUP BY a.tcencos
        ) AS b ON a.codigo = b.tcencos  
        WHERE 
            LEFT(a.codigo, 1) IN ('6', '5') 
            AND RIGHT(a.codigo, 3) <> '000' 
            AND a.swac = 'A' 
            AND CHAR_LENGTH(a.codigo) = 6 
            AND LEFT(a.codigo, 3) <> '650'
            AND CAST(LEFT(a.codigo, 3) AS UNSIGNED) <= 667
    ";
    $resultDebug = mysqli_query($conexion, $queryDebug);
    if ($resultDebug) {
        $debugRow = mysqli_fetch_assoc($resultDebug);
        $response['debug'] = [
            'total_sin_filtro_edad' => $debugRow['total'] ?? 0
        ];
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
exit;
?>