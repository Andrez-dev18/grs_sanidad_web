<?php
/**
 * Endpoint igual al de Flutter: get_cencos_galpones.
 * Devuelve CENCOS con galpones y fec_ing_min para cálculo correcto de campaña y edad.
 * Campaña = últimos 3 dígitos de codigo. Edad = DATEDIFF(fecha, fec_ing_min) + 1
 */
header('Content-Type: application/json; charset=utf-8');
include_once '../../../conexion_grs/conexion.php';
date_default_timezone_set('America/Lima');

$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error de conexión: ' . mysqli_connect_error(),
        'data' => ['cencos' => [], 'total' => 0]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_set_charset($conn, "latin1");
mysqli_query($conn, "SET time_zone = 'America/Lima'");

$query = "
    SELECT c.codigo, c.nombre, g.tcodint, m.fecha fec_ing_min
    FROM ccos AS c
        INNER JOIN regcencosgalpones AS g ON LEFT(c.codigo, 3) = g.tcencos
        INNER JOIN (
            SELECT tcencos, tcodint, DATE_FORMAT(MIN(fec_ing), '%Y-%m-%d') AS fecha
            FROM maes_zonas
            WHERE tcodigo IN ('P0001001','P0001002')
            GROUP BY tcencos, tcodint
        ) AS m ON c.codigo = m.tcencos AND g.tcodint = m.tcodint
    WHERE
        LEFT(c.codigo, 1) IN ('6', '5')
        AND RIGHT(c.codigo, 3) <> '000'
        AND c.swac = 'A'
        AND CHAR_LENGTH(c.codigo) = 6
        AND LEFT(c.codigo, 3) <> '650'
    GROUP BY c.codigo ASC, g.tcodint ASC
";

$result = mysqli_query($conn, $query);
if (!$result) {
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error SQL: ' . mysqli_error($conn),
        'data' => ['cencos' => [], 'total' => 0]
    ], JSON_UNESCAPED_UNICODE);
    mysqli_close($conn);
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
        $cencosMap[$codigo]['galpones'][] = [
            'tcodint' => $row['tcodint'],
            'fec_ing_min' => $row['fec_ing_min'],
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
mysqli_close($conn);

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
