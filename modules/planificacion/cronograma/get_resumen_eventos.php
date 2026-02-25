<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'hoy' => 0, 'proximos' => 0, 'eventosHoy' => [], 'eventosProximos' => []]);
    exit;
}

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'hoy' => 0, 'proximos' => 0, 'eventosHoy' => [], 'eventosProximos' => []]);
    exit;
}

$hoy = date('Y-m-d');
$proximosHasta = date('Y-m-d', strtotime('+7 days'));

$chkTable = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
if (!$chkTable || $chkTable->num_rows === 0) {
    $conn->close();
    echo json_encode(['success' => true, 'hoy' => 0, 'proximos' => 0, 'eventosHoy' => [], 'eventosProximos' => []]);
    exit;
}
$chk = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fechaEjecucion'");
if (!$chk || $chk->num_rows === 0) {
    $conn->close();
    echo json_encode(['success' => true, 'hoy' => 0, 'proximos' => 0, 'eventosHoy' => [], 'eventosProximos' => []]);
    exit;
}
$chkNomGranja = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
$tieneNomGranja = $chkNomGranja && $chkNomGranja->num_rows > 0;
$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;

$sqlBase = "SELECT c.id, c.codPrograma, c.nomPrograma, c.granja, c.campania, c.galpon, c.fechaEjecucion";
if ($tieneNomGranja) $sqlBase .= ", c.nomGranja";
if ($tieneEdad) $sqlBase .= ", c.edad";
$sqlBase .= " FROM san_fact_cronograma c WHERE 1=1";

$eventosHoy = [];
$stmtHoy = $conn->prepare($sqlBase . " AND DATE(c.fechaEjecucion) = ? ORDER BY c.fechaEjecucion ASC");
if ($stmtHoy) {
    $stmtHoy->bind_param('s', $hoy);
    $stmtHoy->execute();
    $resHoy = $stmtHoy->get_result();
    while ($row = $resHoy->fetch_assoc()) {
        $ev = [
            'id' => (int)($row['id'] ?? 0),
            'codPrograma' => $row['codPrograma'] ?? '',
            'nomPrograma' => $row['nomPrograma'] ?? '',
            'granja' => $row['granja'] ?? '',
            'nomGranja' => $tieneNomGranja ? ($row['nomGranja'] ?? '') : '',
            'campania' => $row['campania'] ?? '',
            'galpon' => $row['galpon'] ?? '',
            'edad' => $tieneEdad ? ($row['edad'] ?? '') : '',
            'fechaEjecucion' => $row['fechaEjecucion'] ?? ''
        ];
        $eventosHoy[] = $ev;
    }
    $stmtHoy->close();
}

$eventosProximos = [];
$stmtProx = $conn->prepare($sqlBase . " AND DATE(c.fechaEjecucion) > ? AND DATE(c.fechaEjecucion) <= ? ORDER BY c.fechaEjecucion ASC");
if ($stmtProx) {
    $stmtProx->bind_param('ss', $hoy, $proximosHasta);
    $stmtProx->execute();
    $resProx = $stmtProx->get_result();
    while ($row = $resProx->fetch_assoc()) {
        $ev = [
            'id' => (int)($row['id'] ?? 0),
            'codPrograma' => $row['codPrograma'] ?? '',
            'nomPrograma' => $row['nomPrograma'] ?? '',
            'granja' => $row['granja'] ?? '',
            'nomGranja' => $tieneNomGranja ? ($row['nomGranja'] ?? '') : '',
            'campania' => $row['campania'] ?? '',
            'galpon' => $row['galpon'] ?? '',
            'edad' => $tieneEdad ? ($row['edad'] ?? '') : '',
            'fechaEjecucion' => $row['fechaEjecucion'] ?? ''
        ];
        $eventosProximos[] = $ev;
    }
    $stmtProx->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'hoy' => count($eventosHoy),
    'proximos' => count($eventosProximos),
    'eventosHoy' => $eventosHoy,
    'eventosProximos' => $eventosProximos
]);
