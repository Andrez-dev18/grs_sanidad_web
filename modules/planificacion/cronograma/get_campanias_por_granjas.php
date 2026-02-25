<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

$granjas = [];
if (isset($_GET['granjas']) && is_array($_GET['granjas'])) {
    foreach ($_GET['granjas'] as $g) {
        $g = trim((string)$g);
        if ($g !== '') $granjas[] = substr(str_pad($g, 3, '0', STR_PAD_LEFT), 0, 3);
    }
} elseif (!empty($_GET['granjas'])) {
    $g = trim((string)$_GET['granjas']);
    if ($g !== '') $granjas[] = substr(str_pad($g, 3, '0', STR_PAD_LEFT), 0, 3);
}
$granjas = array_values(array_unique($granjas));

if (count($granjas) === 0) {
    echo json_encode(['success' => true, 'data' => []]);
    $conn->close();
    exit;
}

$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
if ($anio < 2000 || $anio > 2100) $anio = (int)date('Y');

$placeholders = implode(',', array_fill(0, count($granjas), '?'));
$sql = "SELECT LEFT(tcencos, 3) AS granja, RIGHT(tcencos, 3) AS campania
        FROM cargapollo_proyeccion
        WHERE LEFT(tcencos, 3) IN ($placeholders) AND YEAR(fecha) = ?
        GROUP BY LEFT(tcencos, 3), RIGHT(tcencos, 3)
        ORDER BY LEFT(tcencos, 3), RIGHT(tcencos, 3)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'data' => [], 'message' => $conn->error]);
    $conn->close();
    exit;
}
$types = str_repeat('s', count($granjas)) . 'i';
$params = $granjas;
$params[] = $anio;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
    $g = trim((string)($row['granja'] ?? ''));
    $c = trim((string)($row['campania'] ?? ''));
    if ($g === '' || $c === '') continue;
    if (!isset($out[$g])) $out[$g] = [];
    if (!in_array($c, $out[$g], true)) $out[$g][] = $c;
}
$stmt->close();
$conn->close();

$data = [];
foreach ($out as $g => $cs) {
    sort($cs);
    $data[] = ['granja' => $g, 'campanias' => $cs];
}

echo json_encode(['success' => true, 'data' => $data]);

