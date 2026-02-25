<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => null]);
    exit;
}
$numCronograma = isset($_GET['numCronograma']) ? (int)$_GET['numCronograma'] : 0;
if ($numCronograma <= 0) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Falta numCronograma']);
    exit;
}

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => null]);
    exit;
}

$chkNom = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$chkZona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'zona'");
$chkSubzona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'subzona'");
$tieneNomGranja = $chkNom && $chkNom->num_rows > 0;
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;
$tieneZona = $chkZona && $chkZona->num_rows > 0;
$tieneSubzona = $chkSubzona && $chkSubzona->num_rows > 0;

$sql = "SELECT id, codPrograma, nomPrograma, granja, campania, galpon, fechaCarga, fechaEjecucion";
if ($tieneNomGranja) $sql .= ", nomGranja";
if ($tieneEdad) $sql .= ", edad";
if ($tieneZona) $sql .= ", zona";
if ($tieneSubzona) $sql .= ", subzona";
$sql .= " FROM san_fact_cronograma WHERE numCronograma = ? ORDER BY granja, campania, galpon, fechaEjecucion ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    $conn->close();
    echo json_encode(['success' => false, 'data' => null]);
    exit;
}
$stmt->bind_param("i", $numCronograma);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
$codPrograma = '';
$nomPrograma = '';
$codTipo = '';
$zonaCronograma = '';
$subzonaCronograma = '';
while ($row = $res->fetch_assoc()) {
    $codPrograma = $row['codPrograma'] ?? '';
    $nomPrograma = $row['nomPrograma'] ?? '';
    if ($tieneZona && $zonaCronograma === '' && isset($row['zona']) && trim((string)$row['zona']) !== '') {
        $zonaCronograma = trim((string)$row['zona']);
    }
    if ($tieneSubzona && $subzonaCronograma === '' && isset($row['subzona']) && trim((string)$row['subzona']) !== '') {
        $subzonaCronograma = trim((string)$row['subzona']);
    }
    $rows[] = [
        'granja' => $row['granja'] ?? '',
        'nomGranja' => $tieneNomGranja ? ($row['nomGranja'] ?? '') : '',
        'campania' => $row['campania'] ?? '',
        'galpon' => $row['galpon'] ?? '',
        'fechaCarga' => $row['fechaCarga'] ?? '',
        'fechaEjecucion' => $row['fechaEjecucion'] ?? '',
        'edad' => $tieneEdad ? ($row['edad'] ?? null) : null,
        'zona' => $tieneZona ? ($row['zona'] ?? '') : '',
        'subzona' => $tieneSubzona ? ($row['subzona'] ?? '') : ''
    ];
}
$stmt->close();

$tieneFechasAnterioresHoy = false;
$hoy = date('Y-m-d');

if ($codPrograma !== '') {
    $stCab = $conn->prepare("SELECT codTipo FROM san_fact_programa_cab WHERE codigo = ? LIMIT 1");
    if ($stCab) {
        $stCab->bind_param("s", $codPrograma);
        $stCab->execute();
        $rCab = $stCab->get_result();
        if ($rCab && $crow = $rCab->fetch_assoc()) $codTipo = (string)($crow['codTipo'] ?? '');
        $stCab->close();
    }
}

foreach ($rows as $r) {
    $fec = $r['fechaEjecucion'] ?? $r['fechaCarga'] ?? '';
    if ($fec && substr($fec, 0, 10) < $hoy) {
        $tieneFechasAnterioresHoy = true;
        break;
    }
}

$conn->close();

$map = [];
foreach ($rows as $r) {
    $key = ($r['granja'] ?? '') . '|' . ($r['campania'] ?? '') . '|' . ($r['galpon'] ?? '');
    if (!isset($map[$key])) {
        $map[$key] = [
            'granja' => $r['granja'],
            'nomGranja' => $r['nomGranja'],
            'campania' => $r['campania'],
            'galpon' => $r['galpon'],
            'edad' => $r['edad'],
            'zona' => $r['zona'] ?? '',
            'subzona' => $r['subzona'] ?? '',
            'fechas' => []
        ];
    }
    $map[$key]['fechas'][] = [
        'fechaCarga' => $r['fechaCarga'],
        'fechaEjecucion' => $r['fechaEjecucion'],
        'campania' => $r['campania'],
        'edad' => $tieneEdad ? ($r['edad'] ?? null) : null
    ];
}
$items = array_values($map);

$dataOut = [
    'numCronograma' => $numCronograma,
    'codPrograma' => $codPrograma,
    'nomPrograma' => $nomPrograma,
    'codTipo' => $codTipo,
    'items' => $items,
    'tieneFechasAnterioresHoy' => $tieneFechasAnterioresHoy
];
if ($tieneZona && $zonaCronograma !== '') {
    $dataOut['zona'] = $zonaCronograma;
}
if ($tieneSubzona && $subzonaCronograma !== '') {
    $dataOut['subzona'] = $subzonaCronograma;
}
echo json_encode(['success' => true, 'data' => $dataOut]);
