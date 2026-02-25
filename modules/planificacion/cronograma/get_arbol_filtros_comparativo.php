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
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Sin conexion']);
    exit;
}

function norm_granja_3($v) {
    $s = trim((string)$v);
    if ($s === '') return '';
    return substr(str_pad($s, 3, '0', STR_PAD_LEFT), 0, 3);
}

function periodo_rango_desde_request() {
    $periodoTipo = trim((string)($_GET['periodoTipo'] ?? 'TODOS'));
    $fechaUnica = trim((string)($_GET['fechaUnica'] ?? ''));
    $fechaInicio = trim((string)($_GET['fechaInicio'] ?? ''));
    $fechaFin = trim((string)($_GET['fechaFin'] ?? ''));
    $mesUnico = trim((string)($_GET['mesUnico'] ?? ''));
    $mesInicio = trim((string)($_GET['mesInicio'] ?? ''));
    $mesFin = trim((string)($_GET['mesFin'] ?? ''));

    if (is_file(__DIR__ . '/../../../../includes/filtro_periodo_util.php')) {
        include_once __DIR__ . '/../../../../includes/filtro_periodo_util.php';
        return periodo_a_rango([
            'periodoTipo' => $periodoTipo,
            'fechaUnica' => $fechaUnica,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'mesUnico' => $mesUnico,
            'mesInicio' => $mesInicio,
            'mesFin' => $mesFin
        ]);
    }

    if ($periodoTipo === 'POR_FECHA' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaUnica)) {
        return ['desde' => $fechaUnica, 'hasta' => $fechaUnica];
    }
    if ($periodoTipo === 'ENTRE_FECHAS' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
        return ['desde' => $fechaInicio, 'hasta' => $fechaFin];
    }
    if ($periodoTipo === 'POR_MES' && preg_match('/^\d{4}-\d{2}$/', $mesUnico)) {
        return ['desde' => $mesUnico . '-01', 'hasta' => date('Y-m-t', strtotime($mesUnico . '-01'))];
    }
    if ($periodoTipo === 'ENTRE_MESES' && preg_match('/^\d{4}-\d{2}$/', $mesInicio) && preg_match('/^\d{4}-\d{2}$/', $mesFin)) {
        return ['desde' => $mesInicio . '-01', 'hasta' => date('Y-m-t', strtotime($mesFin . '-01'))];
    }
    if ($periodoTipo === 'ULTIMA_SEMANA') {
        return ['desde' => date('Y-m-d', strtotime('-6 days')), 'hasta' => date('Y-m-d')];
    }
    if ($periodoTipo === 'TODOS') {
        return ['desde' => '', 'hasta' => ''];
    }
    return null;
}

$filtroGranjas = [];
if (isset($_GET['granjas']) && is_array($_GET['granjas'])) {
    foreach ($_GET['granjas'] as $g) {
        $gn = norm_granja_3($g);
        if ($gn !== '') $filtroGranjas[$gn] = true;
    }
}
$filtroGranjas = array_keys($filtroGranjas);
$rango = periodo_rango_desde_request();
if ($rango === null) {
    $conn->close();
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Periodo invalido']);
    exit;
}

$where = [
    "cp.tcencos IS NOT NULL",
    "LENGTH(TRIM(cp.tcencos)) >= 6",
    "cp.tcodint IS NOT NULL",
    "TRIM(cp.tcodint) <> ''"
];
$params = [];
$types = '';

if (!empty($rango['desde']) && !empty($rango['hasta'])) {
    $where[] = "cp.fecha >= ? AND cp.fecha < DATE_ADD(?, INTERVAL 1 DAY)";
    $params[] = $rango['desde'];
    $params[] = $rango['hasta'];
    $types .= 'ss';
}

if (count($filtroGranjas) > 0) {
    $ph = implode(',', array_fill(0, count($filtroGranjas), '?'));
    $where[] = "LEFT(TRIM(cp.tcencos), 3) IN ($ph)";
    foreach ($filtroGranjas as $g) {
        $params[] = $g;
        $types .= 's';
    }
}

$sql = "SELECT LEFT(TRIM(cp.tcencos), 3) AS granja,
               RIGHT(TRIM(cp.tcencos), 3) AS campania,
               TRIM(cp.tcodint) AS galpon
        FROM cargapollo_proyeccion cp
        WHERE " . implode(' AND ', $where) . "
        GROUP BY LEFT(TRIM(cp.tcencos), 3), RIGHT(TRIM(cp.tcencos), 3), TRIM(cp.tcodint)
        ORDER BY LEFT(TRIM(cp.tcencos), 3), RIGHT(TRIM(cp.tcencos), 3), CAST(TRIM(cp.tcodint) AS UNSIGNED), TRIM(cp.tcodint)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $msg = $conn->error;
    $conn->close();
    echo json_encode(['success' => false, 'data' => [], 'message' => $msg]);
    exit;
}

if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$tree = [];
while ($row = $res->fetch_assoc()) {
    $g = norm_granja_3($row['granja'] ?? '');
    $c = trim((string)($row['campania'] ?? ''));
    $gp = trim((string)($row['galpon'] ?? ''));
    if ($g === '' || $c === '' || $gp === '') continue;
    if (!isset($tree[$g])) $tree[$g] = [];
    if (!isset($tree[$g][$c])) $tree[$g][$c] = [];
    $tree[$g][$c][$gp] = true;
}

$out = [];
foreach ($tree as $granja => $campMap) {
    ksort($campMap);
    $campanias = [];
    foreach ($campMap as $campania => $galponMap) {
        $galpones = array_keys($galponMap);
        usort($galpones, function ($a, $b) {
            $na = is_numeric($a) ? (int)$a : 999999;
            $nb = is_numeric($b) ? (int)$b : 999999;
            if ($na !== $nb) return $na <=> $nb;
            return strcmp((string)$a, (string)$b);
        });
        $campanias[] = ['campania' => $campania, 'galpones' => $galpones];
    }
    $out[] = ['granja' => $granja, 'campanias' => $campanias];
}

$stmt->close();
$conn->close();
echo json_encode(['success' => true, 'data' => $out]);

