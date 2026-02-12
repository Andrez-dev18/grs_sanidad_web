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

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => null]);
    exit;
}

$chkNom = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneNomGranja = $chkNom && $chkNom->num_rows > 0;
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;

$sql = "SELECT id, codPrograma, nomPrograma, granja, campania, galpon, fechaCarga, fechaEjecucion";
if ($tieneNomGranja) $sql .= ", nomGranja";
if ($tieneEdad) $sql .= ", edad";
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
while ($row = $res->fetch_assoc()) {
    $codPrograma = $row['codPrograma'] ?? '';
    $nomPrograma = $row['nomPrograma'] ?? '';
    $rows[] = [
        'granja' => $row['granja'] ?? '',
        'nomGranja' => $tieneNomGranja ? ($row['nomGranja'] ?? '') : '',
        'campania' => $row['campania'] ?? '',
        'galpon' => $row['galpon'] ?? '',
        'fechaCarga' => $row['fechaCarga'] ?? '',
        'fechaEjecucion' => $row['fechaEjecucion'] ?? '',
        'edad' => $tieneEdad ? ($row['edad'] ?? null) : null
    ];
}
$stmt->close();
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
$conn->close();

// Agrupar por granja|campania|galpon en items con fechas[]
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

echo json_encode([
    'success' => true,
    'data' => [
        'numCronograma' => $numCronograma,
        'codPrograma' => $codPrograma,
        'nomPrograma' => $nomPrograma,
        'codTipo' => $codTipo,
        'items' => $items
    ]
]);
