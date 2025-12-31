<?php
// --- CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once '../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$codEnvio = $_GET['codEnvio'] ?? '';
$posSolicitud = $_GET['posSolicitud'] ?? '';

// Validación estricta
if (!is_string($codEnvio) || trim($codEnvio) === '') {
    echo json_encode([
        'success' => false,
        'message' => 'codEnvio es obligatorio y debe ser una cadena no vacía'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_numeric($posSolicitud) || (int) $posSolicitud <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'posSolicitud debe ser un número entero positivo'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$posSolicitud = (int) $posSolicitud;

// || Consulta: todos los análisis de la solicitud, con resultado si existe
$sql = "
        SELECT 
        COALESCE(det.codAnalisis, a.codigo_enfermedad) AS codigo,
        COALESCE(det.nomAnalisis, a.enfermedad) AS nombre,
        COALESCE(a.gmean, 'No ingresado') AS gmean,
        COALESCE(a.cv, 'No ingresado') AS cv,
        COALESCE(a.desviacion_estandar, 'No ingresado') AS sd,
        COALESCE(a.count_muestras, 'No ingresado') AS count_muestras,
        COALESCE(a.s01, 'No ingresado') AS s01,
        COALESCE(a.s02, 'No ingresado') AS s02,
        COALESCE(a.s03, 'No ingresado') AS s03,
        COALESCE(a.s04, 'No ingresado') AS s04,
        COALESCE(a.s05, 'No ingresado') AS s05,
        COALESCE(a.s06, 'No ingresado') AS s06,
        COALESCE(a.t01, 'No ingresado') AS t01,
        COALESCE(a.t02, 'No ingresado') AS t02,
        COALESCE(a.t03, 'No ingresado') AS t03,
        COALESCE(a.t04, 'No ingresado') AS t04,
        COALESCE(a.t05, 'No ingresado') AS t05,
        COALESCE(a.t06, 'No ingresado') AS t06,
        COALESCE(a.t07, 'No ingresado') AS t07,
        COALESCE(a.t08, 'No ingresado') AS t08,
        COALESCE(a.t09, 'No ingresado') AS t09,
        COALESCE(a.t10, 'No ingresado') AS t10,
        COALESCE(a.t11, 'No ingresado') AS t11,
        COALESCE(a.t12, 'No ingresado') AS t12,
        COALESCE(a.t13, 'No ingresado') AS t13,
        COALESCE(a.t14, 'No ingresado') AS t14,
        COALESCE(a.t15, 'No ingresado') AS t15,
        COALESCE(a.t16, 'No ingresado') AS t16,
        COALESCE(a.t17, 'No ingresado') AS t17,
        COALESCE(a.t18, 'No ingresado') AS t18,
        COALESCE(a.t19, 'No ingresado') AS t19,
        COALESCE(a.t20, 'No ingresado') AS t20,
        COALESCE(a.t21, 'No ingresado') AS t21,
        COALESCE(a.t22, 'No ingresado') AS t22,
        COALESCE(a.t23, 'No ingresado') AS t23,
        COALESCE(a.t24, 'No ingresado') AS t24,
        COALESCE(a.t25, 'No ingresado') AS t25
    FROM san_fact_solicitud_det det
    LEFT JOIN san_analisis_pollo_bb_adulto a
        ON det.codEnvio = a.codigo_envio 
        AND det.posSolicitud = a.posSolicitud
        AND det.codAnalisis = a.codigo_enfermedad
    WHERE det.codEnvio = ? AND det.posSolicitud = ?

    UNION

    SELECT 
        a.codigo_enfermedad AS codigo,
        a.enfermedad AS nombre,
        COALESCE(a.gmean, 'No ingresado') AS gmean,
        COALESCE(a.cv, 'No ingresado') AS cv,
        COALESCE(a.desviacion_estandar, 'No ingresado') AS sd,
        COALESCE(a.count_muestras, 'No ingresado') AS count_muestras,
        COALESCE(a.s01, 'No ingresado') AS s01,
        COALESCE(a.s02, 'No ingresado') AS s02,
        COALESCE(a.s03, 'No ingresado') AS s03,
        COALESCE(a.s04, 'No ingresado') AS s04,
        COALESCE(a.s05, 'No ingresado') AS s05,
        COALESCE(a.s06, 'No ingresado') AS s06,
        COALESCE(a.t01, 'No ingresado') AS t01,
        COALESCE(a.t02, 'No ingresado') AS t02,
        COALESCE(a.t03, 'No ingresado') AS t03,
        COALESCE(a.t04, 'No ingresado') AS t04,
        COALESCE(a.t05, 'No ingresado') AS t05,
        COALESCE(a.t06, 'No ingresado') AS t06,
        COALESCE(a.t07, 'No ingresado') AS t07,
        COALESCE(a.t08, 'No ingresado') AS t08,
        COALESCE(a.t09, 'No ingresado') AS t09,
        COALESCE(a.t10, 'No ingresado') AS t10,
        COALESCE(a.t11, 'No ingresado') AS t11,
        COALESCE(a.t12, 'No ingresado') AS t12,
        COALESCE(a.t13, 'No ingresado') AS t13,
        COALESCE(a.t14, 'No ingresado') AS t14,
        COALESCE(a.t15, 'No ingresado') AS t15,
        COALESCE(a.t16, 'No ingresado') AS t16,
        COALESCE(a.t17, 'No ingresado') AS t17,
        COALESCE(a.t18, 'No ingresado') AS t18,
        COALESCE(a.t19, 'No ingresado') AS t19,
        COALESCE(a.t20, 'No ingresado') AS t20,
        COALESCE(a.t21, 'No ingresado') AS t21,
        COALESCE(a.t22, 'No ingresado') AS t22,
        COALESCE(a.t23, 'No ingresado') AS t23,
        COALESCE(a.t24, 'No ingresado') AS t24,
        COALESCE(a.t25, 'No ingresado') AS t25
    FROM san_analisis_pollo_bb_adulto a
    LEFT JOIN san_fact_solicitud_det det 
        ON a.codigo_envio = det.codEnvio 
        AND a.posSolicitud = det.posSolicitud
        AND a.codigo_enfermedad = det.codAnalisis
    WHERE a.codigo_envio = ? AND a.posSolicitud = ?
    AND det.codAnalisis IS NULL  -- solo los que NO están en det
    ORDER BY nombre
    ";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar la consulta: ' . $conexion->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('sisi', $codEnvio, $posSolicitud, $codEnvio, $posSolicitud);
$stmt->execute();
$result = $stmt->get_result();

$datos = [];
while ($row = $result->fetch_assoc()) {
    $datos[] = $row;
}
$stmt->close();
$conexion->close();

echo json_encode([
    'success' => true,
    'data' => $datos
], JSON_UNESCAPED_UNICODE);