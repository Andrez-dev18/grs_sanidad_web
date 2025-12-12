<?php
// --- CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// --- Conexión ---
include_once '../../conexion_grs_joya/conexion.php';
date_default_timezone_set('America/Lima');
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Parámetros ---
$registrosPorPagina = 10;
$pagina = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($pagina < 1)
    $pagina = 1;
$offset = ($pagina - 1) * $registrosPorPagina;

$busqueda = trim($_GET['q'] ?? '');
$condicion = '';
$params = [];
$types = '';

if ($busqueda !== '') {   
    $condicion = "WHERE c.codEnvio LIKE ?";
    $params[] = "%$busqueda%";
    $types .= 's';
}

// --- Contar total ---
$sqlTotal = "SELECT COUNT(*) AS total FROM san_fact_solicitud_cab c $condicion";
if (!empty($params)) {
    $stmtTotal = mysqli_prepare($conexion, $sqlTotal);
    mysqli_stmt_bind_param($stmtTotal, $types, ...$params);
    mysqli_stmt_execute($stmtTotal);
    $totalRes = mysqli_stmt_get_result($stmtTotal);
    $total = mysqli_fetch_assoc($totalRes)['total'];
    mysqli_stmt_close($stmtTotal);
} else {
    $res = mysqli_query($conexion, $sqlTotal);
    $total = mysqli_fetch_assoc($res)['total'];
}

$totalPaginas = ceil($total / $registrosPorPagina);

// --- Consulta principal ---
$sql = "
    SELECT 
        c.codEnvio,
        c.fecEnvio,
        c.horaEnvio,
        c.nomLab AS laboratorio,
        COUNT(d.posSolicitud) AS total_muestras,
        MIN(d.codRef) AS primer_codigo_ref
    FROM san_fact_solicitud_cab c
    LEFT JOIN san_fact_solicitud_det d ON c.codEnvio = d.codEnvio
    $condicion
    GROUP BY c.codEnvio, c.fecEnvio, c.horaEnvio, c.nomLab, c.fechaHoraRegistro
    ORDER BY c.fechaHoraRegistro DESC
    LIMIT ? OFFSET ?
";

$params[] = $registrosPorPagina;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$envios = [];
while ($row = mysqli_fetch_assoc($result)) {
    $envios[] = [
        'codigoEnvio' => $row['codEnvio'],
        'fechaEnvio' => $row['fecEnvio'],
        'horaEnvio' => $row['horaEnvio'],
        'laboratorio' => $row['laboratorio'],
        'total_muestras' => (int) ($row['total_muestras'] ?? 0),
        'primer_codigo_ref' => $row['primer_codigo_ref'] ?? '–',
    ];
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);

// --- Respuesta ---
echo json_encode([
    'success' => true,
    'data' => $envios,
    'total_pages' => $totalPaginas,
    'current_page' => $pagina,
    'total_records' => $total
], JSON_UNESCAPED_UNICODE);