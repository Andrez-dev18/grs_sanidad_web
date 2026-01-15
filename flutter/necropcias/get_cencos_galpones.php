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

mysqli_set_charset($conexion, "latin1");

$query = "
    SELECT 
        c.codigo,
        c.nombre,
        IFNULL(z.fec_ing_min, '') AS fec_ing_min,
        g.tcodint
    FROM ccos AS c
    LEFT JOIN (
        SELECT 
            a.tcencos,
            DATE_FORMAT(MIN(a.fec_ing), '%Y-%m-%d') AS fec_ing_min
        FROM maes_zonas AS a 
        WHERE a.tcodigo IN ('P0001001','P0001002')  
        GROUP BY a.tcencos
    ) AS z ON c.codigo = z.tcencos
    -- regcencosgalpones.tcencos guarda los 3 primeros dígitos del código (ej: 123456 -> 123)
    LEFT JOIN regcencosgalpones AS g ON LEFT(c.codigo, 3) = g.tcencos
    WHERE 
        LEFT(c.codigo, 1) IN ('6', '5') 
        AND RIGHT(c.codigo, 3) <> '000' 
        AND c.swac = 'A' 
        AND CHAR_LENGTH(c.codigo) = 6 
        AND LEFT(c.codigo, 3) <> '650'
        AND CAST(LEFT(c.codigo, 3) AS UNSIGNED) <= 667
    ORDER BY c.codigo ASC, g.tcodint ASC
";

$result = mysqli_query($conexion, $query);

if (!$result) {
    if (ob_get_level()) ob_clean();
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error SQL: ' . mysqli_error($conexion)
    ], JSON_UNESCAPED_UNICODE);
    mysqli_close($conexion);
    exit;
}

// --- Agrupar resultados en PHP ---
$cencosMap = [];

while ($row = mysqli_fetch_assoc($result)) {
    $codigo = $row['codigo'];
    
    if (!isset($cencosMap[$codigo])) {
        $cencosMap[$codigo] = [
            'codigo' => $codigo,
            'nombre' => $row['nombre'],
            'fec_ing_min' => $row['fec_ing_min'], // YYYY-MM-DD (o '' si no hay)
            'galpones' => []
        ];
    }

    // Agregar galpón si existe (evitar nulls)
    if ($row['tcodint'] !== null) {
        $cencosMap[$codigo]['galpones'][] = [
            'tcodint' => $row['tcodint']
        ];
    }
}

// Convertir a array indexado
$cencos = array_values($cencosMap);
$total = count($cencos);

if (ob_get_level()) ob_clean();

$response = [
    'status' => 200,
    'success' => true,
    'message' => $total > 0 ? 'CENCOS y galpones cargados correctamente' : 'No se encontraron datos',
    'data' => [
        'cencos' => $cencos,
        'total' => $total
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
exit;
?>