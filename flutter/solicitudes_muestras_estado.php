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
$pagina = max(1, $pagina);
$offset = ($pagina - 1) * $registrosPorPagina;

$busqueda = trim($_GET['q'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? 'todos'); // 'todos', 'pendiente', 'completado'

$condicion = '';
$params = [];
$types = '';

// Búsqueda por codEnvio
if ($busqueda !== '') {
    $condicion .= " AND c.codEnvio LIKE ?";
    $params[] = "%$busqueda%";
    $types .= 's';
}

// Filtro por estado: usaremos una subconsulta con GROUP BY + condición en HAVING
// Primero, definimos el filtro lógico en la cláusula HAVING
// Filtro por estado
$havingCondition = '';
if ($filtroEstado === 'completado') {
    $havingCondition = "HAVING MIN(c.estado_cuali) = 'completado' AND MAX(c.estado_cuali) = 'completado'";
} elseif ($filtroEstado === 'pendiente') {
    $havingCondition = "HAVING NOT (MIN(c.estado_cuali) = 'completado' AND MAX(c.estado_cuali) = 'completado')";
}
// Si 'todos', no hay HAVING

// --- Contar total de grupos (codEnvio + posSolicitud) que cumplen el filtro ---
$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM (
        SELECT c.codEnvio, c.posSolicitud
        FROM san_fact_solicitud_det c
        WHERE 1=1 $condicion
        GROUP BY c.codEnvio, c.posSolicitud
        $havingCondition
    ) AS grouped
";

if (!empty($params)) {
    $stmtTotal = mysqli_prepare($conexion, $sqlTotal);
    mysqli_stmt_bind_param($stmtTotal, $types, ...$params);
    mysqli_stmt_execute($stmtTotal);
    $totalRes = mysqli_stmt_get_result($stmtTotal);
    $total = (int) mysqli_fetch_assoc($totalRes)['total'];
    mysqli_stmt_close($stmtTotal);
} else {
    $res = mysqli_query($conexion, $sqlTotal);
    $total = (int) mysqli_fetch_assoc($res)['total'];
}

$totalPaginas = ceil($total / $registrosPorPagina);

// --- Consulta principal: agrupar por envío + posición y determinar estado general ---
$sql = "
    SELECT 
        c.codEnvio,
        c.posSolicitud,
        MIN(c.fecToma) AS fecToma,
        MIN(c.numMuestras) AS numMuestras,
        MIN(c.codRef) AS codRef,
        MIN(c.nomMuestra) AS nomMuestra,
        GROUP_CONCAT(
            DISTINCT CONCAT(c.codAnalisis, '::', c.nomAnalisis) 
            ORDER BY c.codAnalisis 
            SEPARATOR '; '
        ) AS analisis,
        MAX(c.obs) AS observaciones,
        CASE 
            WHEN MIN(c.estado_cuali) = 'completado' AND MAX(c.estado_cuali) = 'completado'
            THEN 'completado'
            ELSE 'pendiente'
        END AS estado_general,
        COUNT(*) AS total_registros
    FROM san_fact_solicitud_det c
    WHERE 1=1 $condicion
    GROUP BY c.codEnvio, c.posSolicitud
    $havingCondition
    ORDER BY c.codEnvio DESC, c.posSolicitud ASC
    LIMIT ? OFFSET ?
";

$params[] = $registrosPorPagina;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$solicitudes = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Opcional: parsear los análisis si quieres estructura más limpia en Flutter
    $analisisList = [];
    if (!empty($row['analisis'])) {
        $pares = explode('; ', $row['analisis']);
        foreach ($pares as $par) {
            if (strpos($par, '::') !== false) {
                [$cod, $nom] = explode('::', $par, 2);
                $analisisList[] = ['codigo' => $cod, 'nombre' => $nom];
            }
        }
    }

    $solicitudes[] = [
        'codigoEnvio' => $row['codEnvio'],
        'posSolicitud' => (int) $row['posSolicitud'],
        'fechaToma' => $row['fecToma'],
        'numeroMuestras' => (int) $row['numMuestras'],
        'codigoReferencia' => $row['codRef'],
        'tipoMuestra' => $row['nomMuestra'],
        'analisis' => $analisisList,
        'observaciones' => $row['observaciones'],
        'estado' => $row['estado_general'],
        'totalItems' => (int) $row['total_registros']
    ];
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);

echo json_encode([
    'success' => true,
    'data' => $solicitudes,
    'total_pages' => $totalPaginas,
    'current_page' => $pagina,
    'total_records' => $total
], JSON_UNESCAPED_UNICODE);