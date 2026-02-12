<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

$periodoTipo = trim((string)($_GET['periodoTipo'] ?? ''));
$fechaUnica = trim((string)($_GET['fechaUnica'] ?? ''));
$fechaInicio = trim((string)($_GET['fechaInicio'] ?? ''));
$fechaFin = trim((string)($_GET['fechaFin'] ?? ''));
$mesUnico = trim((string)($_GET['mesUnico'] ?? ''));
$mesInicio = trim((string)($_GET['mesInicio'] ?? ''));
$mesFin = trim((string)($_GET['mesFin'] ?? ''));
$codTipo = trim((string)($_GET['codTipo'] ?? ''));
$mesEjecucion = trim((string)($_GET['mesEjecucion'] ?? ''));

// Filtro por mes de ejecución (para calendario): prioridad sobre periodo de registro
$rangoEjecucion = null;
if (preg_match('/^\d{4}-\d{2}$/', $mesEjecucion)) {
    $rangoEjecucion = [
        'desde' => $mesEjecucion . '-01',
        'hasta' => date('Y-m-t', strtotime($mesEjecucion . '-01'))
    ];
}

$rango = null;
if ($rangoEjecucion === null && $periodoTipo !== '' && $periodoTipo !== 'TODOS') {
    if (!is_file(__DIR__ . '/../../../../includes/filtro_periodo_util.php')) {
        if ($periodoTipo === 'POR_FECHA' && $fechaUnica !== '') $rango = ['desde' => $fechaUnica, 'hasta' => $fechaUnica];
        elseif ($periodoTipo === 'ENTRE_FECHAS' && $fechaInicio !== '' && $fechaFin !== '') $rango = ['desde' => $fechaInicio, 'hasta' => $fechaFin];
        elseif ($periodoTipo === 'POR_MES' && preg_match('/^\d{4}-\d{2}$/', $mesUnico)) $rango = ['desde' => $mesUnico . '-01', 'hasta' => date('Y-m-t', strtotime($mesUnico . '-01'))];
        elseif ($periodoTipo === 'ENTRE_MESES' && preg_match('/^\d{4}-\d{2}$/', $mesInicio) && preg_match('/^\d{4}-\d{2}$/', $mesFin)) {
            $rango = ['desde' => $mesInicio . '-01', 'hasta' => date('Y-m-t', strtotime($mesFin . '-01'))];
        } elseif ($periodoTipo === 'ULTIMA_SEMANA') {
            $rango = ['desde' => date('Y-m-d', strtotime('-6 days')), 'hasta' => date('Y-m-d')];
        }
    } else {
        include_once __DIR__ . '/../../../../includes/filtro_periodo_util.php';
        $rango = periodo_a_rango([
            'periodoTipo' => $periodoTipo, 'fechaUnica' => $fechaUnica, 'fechaInicio' => $fechaInicio, 'fechaFin' => $fechaFin,
            'mesUnico' => $mesUnico, 'mesInicio' => $mesInicio, 'mesFin' => $mesFin
        ]);
    }
}

// Una fila por registro de cronograma (incl. nomGranja y edad para calendario/leyenda)
$tieneFechaHora = false;
$tieneNomGranja = false;
$tieneEdad = false;
$chk = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fechaHoraRegistro'");
if ($chk && $chk->num_rows > 0) $tieneFechaHora = true;
$chk2 = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
if ($chk2 && $chk2->num_rows > 0) $tieneNomGranja = true;
$chk3 = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
if ($chk3 && $chk3->num_rows > 0) $tieneEdad = true;
$chk4 = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'posDetalle'");
$tienePosDetalle = $chk4 && $chk4->num_rows > 0;
$chk5 = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
$tieneNumCronograma = $chk5 && $chk5->num_rows > 0;

$joinTipo = '';
$whereTipo = '';
$params = [];
$types = '';

if ($codTipo !== '') {
    $joinTipo = " INNER JOIN san_fact_programa_cab cab ON cab.codigo = c.codPrograma AND cab.codTipo = ? ";
    $whereTipo = " 1=1 ";
    $params[] = $codTipo;
    $types .= 's';
}

$sql = "SELECT c.id, c.codPrograma, c.nomPrograma, c.granja, c.campania, c.galpon, c.fechaCarga, c.fechaEjecucion";
if ($tieneFechaHora) $sql .= ", c.fechaHoraRegistro";
if ($tieneNomGranja) $sql .= ", c.nomGranja";
if ($tieneEdad) $sql .= ", c.edad";
if ($tienePosDetalle) $sql .= ", c.posDetalle";
if ($tieneNumCronograma) $sql .= ", c.numCronograma";
$sql .= " FROM san_fact_cronograma c";
$sql .= $joinTipo;
$sql .= " WHERE " . ($whereTipo ?: " 1=1 ");

if ($rangoEjecucion !== null && isset($rangoEjecucion['desde'], $rangoEjecucion['hasta'])) {
    $sql .= " AND DATE(c.fechaEjecucion) >= ? AND DATE(c.fechaEjecucion) <= ? ";
    $params[] = $rangoEjecucion['desde'];
    $params[] = $rangoEjecucion['hasta'];
    $types .= 'ss';
} elseif ($rango !== null && isset($rango['desde'], $rango['hasta'])) {
    $campoFechaFiltro = $tieneFechaHora ? 'c.fechaHoraRegistro' : 'c.fechaEjecucion';
    $sql .= " AND DATE(" . $campoFechaFiltro . ") >= ? AND DATE(" . $campoFechaFiltro . ") <= ? ";
    $params[] = $rango['desde'];
    $params[] = $rango['hasta'];
    $types .= 'ss';
}

$sql .= " ORDER BY " . ($tieneNumCronograma ? "c.numCronograma DESC, " : "") . "c.codPrograma, c.granja, c.campania, c.galpon, c.fechaEjecucion ASC";

if (count($params) > 0) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'data' => [], 'message' => 'Error preparar: ' . $conn->error]);
        $conn->close();
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}
if ($res === false) {
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Error en consulta: ' . $conn->error]);
    $conn->close();
    exit;
}
$lista = [];
$num = 0;
while ($row = $res->fetch_assoc()) {
    $num++;
    $lista[] = [
        'num' => $num,
        'id' => (int)($row['id'] ?? 0),
        'codPrograma' => $row['codPrograma'] ?? '',
        'nomPrograma' => $row['nomPrograma'] ?? '',
        'fechaHoraRegistro' => $tieneFechaHora ? ($row['fechaHoraRegistro'] ?? '') : ($row['fechaCarga'] ?? ''),
        'granja' => $row['granja'] ?? '',
        'nomGranja' => $tieneNomGranja ? ($row['nomGranja'] ?? '') : '',
        'campania' => $row['campania'] ?? '',
        'galpon' => $row['galpon'] ?? '',
        'fechaCarga' => $row['fechaCarga'] ?? '',
        'fechaEjecucion' => $row['fechaEjecucion'] ?? '',
        'edad' => $tieneEdad ? ($row['edad'] ?? '') : '',
        'posDetalle' => $tienePosDetalle ? ($row['posDetalle'] ?? '') : '',
        'numCronograma' => $tieneNumCronograma ? (int)($row['numCronograma'] ?? 0) : 0
    ];
}
if (isset($stmt)) $stmt->close();
echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
