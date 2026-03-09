<?php
ob_start();

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

include_once '../../../conexion_grs/conexion.php';
date_default_timezone_set('America/Lima');

$conexion = conectar_joya_mysqli();

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
mysqli_query($conexion, "SET time_zone = 'America/Lima'");

// Fecha opcional enviada desde web (select fecha) para devolver edad ya calculada por galpón.
// Si no llega fecha válida, no se calcula edad_calculada (queda null).
$fechaSelRaw = trim((string)($_GET['fecha'] ?? $_POST['fecha'] ?? ''));
$fechaSel = '';
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSelRaw)) {
    $fechaSel = $fechaSelRaw;
}
$fechaSelSql = ($fechaSel !== '') ? mysqli_real_escape_string($conexion, $fechaSel) : '';
$filtroFechaSql = ($fechaSelSql !== '') ? " AND DATE(fec_ing) <= '{$fechaSelSql}'" : "";
// Join: regcencosgalpones.tcencos = 3 dígitos. LEFT JOIN maes_zonas para incluir campañas nuevas (ej. 621191)
// que aún no tienen maes_zonas; fec_ing_min fallback desde misma granja+galpón (campaña anterior)
$query = "
    SELECT c.codigo, MAX(c.nombre) AS nombre, g.tcodint,
        MAX(COALESCE(m1.fecha, m2.fecha)) AS fec_ing_min
    FROM ccos AS c 
        INNER JOIN regcencosgalpones AS g ON LEFT(c.codigo, 3) = g.tcencos 
        LEFT JOIN (
            SELECT tcencos,tcodint,DATE_FORMAT(MAX(fec_ing), '%Y-%m-%d') AS fecha
            FROM maes_zonas
            WHERE tcodigo IN ('P0001001','P0001002')
              AND fec_ing IS NOT NULL
              AND fec_ing > '1900-01-01'
              {$filtroFechaSql}
            GROUP BY tcencos,tcodint
        ) AS m1 ON c.codigo=m1.tcencos AND g.tcodint=m1.tcodint
        LEFT JOIN (
            SELECT LEFT(tcencos,3) AS prefijo, tcodint, DATE_FORMAT(MAX(fec_ing), '%Y-%m-%d') AS fecha
            FROM maes_zonas
            WHERE tcodigo IN ('P0001001','P0001002')
              AND fec_ing IS NOT NULL
              AND fec_ing > '1900-01-01'
              {$filtroFechaSql}
            GROUP BY LEFT(tcencos,3), tcodint
        ) AS m2 ON LEFT(c.codigo,3)=m2.prefijo AND g.tcodint=m2.tcodint AND m1.fecha IS NULL
    WHERE 
        LEFT(c.codigo, 1) IN ('6', '5') 
        AND RIGHT(c.codigo, 3) <> '000' 
        AND c.swac = 'A' 
        AND CHAR_LENGTH(c.codigo) = 6 
        AND LEFT(c.codigo, 3) <> '650'
    GROUP BY c.codigo ASC, g.tcodint ASC
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

$cencosMap = [];

while ($row = mysqli_fetch_assoc($result)) {
    $codigo = $row['codigo'];

    if (!isset($cencosMap[$codigo])) {
        $cencosMap[$codigo] = [
            'codigo' => $codigo,
            'nombre' => $row['nombre'],
            
            'galpones' => []
        ];
    }

    if ($row['tcodint'] !== null) {
        $fecIngMin = $row['fec_ing_min'] ?? null;
        // Blindaje: si llega una fecha sentinela (ej. 1000-01-01), no devolverla para evitar edades absurdas.
        if ($fecIngMin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecIngMin)) {
            $anio = (int)substr($fecIngMin, 0, 4);
            if ($anio <= 1900) {
                $fecIngMin = null;
            }
        }

        // Edad calculada en backend (si hay fecha seleccionada y fec_ing_min válida).
        $edadCalculada = null;
        if ($fecIngMin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecIngMin) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSel)) {
            $tsIng = strtotime($fecIngMin . ' 00:00:00');
            $tsSel = strtotime($fechaSel . ' 00:00:00');
            if ($tsIng !== false && $tsSel !== false) {
                $diffDias = (int)floor(($tsSel - $tsIng) / 86400);
                $edadTmp = $diffDias + 1;
                // Blindaje por rangos absurdos.
                if ($edadTmp > 0 && $edadTmp <= 2000) {
                    $edadCalculada = $edadTmp;
                }
            }
        }

        $cencosMap[$codigo]['galpones'][] = [
            'tcodint' => $row['tcodint'],
            'fec_ing_min' => $fecIngMin,
            'edad_calculada' => $edadCalculada,
            'fecha_referencia' => ($fechaSel !== '' ? $fechaSel : null),
        ];
    }
}

$cencos = array_values($cencosMap);
// Ordenar por codigo DESC para que 621191 aparezca antes que 621190 (campaña mayor = más reciente)
usort($cencos, function ($a, $b) {
    $cmp = strcmp($b['codigo'] ?? '', $a['codigo'] ?? '');
    if ($cmp !== 0) return $cmp;
    return strcmp($a['nombre'] ?? '', $b['nombre'] ?? '');
});
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
