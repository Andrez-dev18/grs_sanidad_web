<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Filtros: codTipo, zona, despliegue; periodo = periodoTipo + fechas/meses (si TODOS o vacío no filtra por fecha)
$codTipo = trim((string)($_GET['codTipo'] ?? ''));
$periodoTipo = trim((string)($_GET['periodoTipo'] ?? 'TODOS'));
$fechaUnica = trim((string)($_GET['fechaUnica'] ?? ''));
$fechaInicio = trim((string)($_GET['fechaInicio'] ?? ''));
$fechaFin = trim((string)($_GET['fechaFin'] ?? ''));
$mesUnico = trim((string)($_GET['mesUnico'] ?? ''));
$mesInicio = trim((string)($_GET['mesInicio'] ?? ''));
$mesFin = trim((string)($_GET['mesFin'] ?? ''));
$zona = trim((string)($_GET['zona'] ?? ''));
$despliegue = trim((string)($_GET['despliegue'] ?? ''));

// Compatibilidad: si envían fechaDesde/fechaHasta por GET se usan cuando no hay periodo
$fechaDesde = trim((string)($_GET['fechaDesde'] ?? ''));
$fechaHasta = trim((string)($_GET['fechaHasta'] ?? ''));

require_once __DIR__ . '/../../../includes/filtro_periodo_util.php';
$rangoPeriodo = periodo_a_rango([
    'periodoTipo' => $periodoTipo, 'fechaUnica' => $fechaUnica,
    'fechaInicio' => $fechaInicio, 'fechaFin' => $fechaFin,
    'mesUnico' => $mesUnico, 'mesInicio' => $mesInicio, 'mesFin' => $mesFin
]);
if ($rangoPeriodo) {
    $fechaDesde = $rangoPeriodo['desde'];
    $fechaHasta = $rangoPeriodo['hasta'];
}

$chkDespliegue = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'despliegue'");
$tieneDespliegue = $chkDespliegue && $chkDespliegue->fetch_assoc();

$sqlCab = "SELECT c.codigo, c.nombre, c.codTipo, c.nomTipo, c.zona, c.descripcion, c.fechaHoraRegistro";
if ($tieneDespliegue) $sqlCab .= ", c.despliegue";
$sqlCab .= " FROM san_fact_programa_cab c WHERE 1=1";
$params = [];
$types = '';

// Tipo: c.codTipo es el codigo (int) de san_dim_tipo_programa
if ($codTipo !== '' && is_numeric($codTipo)) {
    $sqlCab .= " AND c.codTipo = ?";
    $params[] = (int)$codTipo;
    $types .= 'i';
}
// Fechas por fechaHoraRegistro (desde rango periodo o fechaDesde/fechaHasta legacy)
if ($fechaDesde !== '') {
    $sqlCab .= " AND DATE(c.fechaHoraRegistro) >= ?";
    $params[] = $fechaDesde;
    $types .= 's';
}
if ($fechaHasta !== '') {
    $sqlCab .= " AND DATE(c.fechaHoraRegistro) <= ?";
    $params[] = $fechaHasta;
    $types .= 's';
}
// Zona y despliegue: LIKE para coincidencia parcial (espacios/case)
if ($zona !== '') {
    $sqlCab .= " AND (c.zona IS NOT NULL AND c.zona LIKE ?)";
    $params[] = '%' . $zona . '%';
    $types .= 's';
}
if ($despliegue !== '') {
    if ($tieneDespliegue) {
        $sqlCab .= " AND (c.despliegue IS NOT NULL AND c.despliegue LIKE ?)";
        $params[] = '%' . $despliegue . '%';
        $types .= 's';
    }
}

$sqlCab .= " ORDER BY c.fechaHoraRegistro DESC, c.codigo DESC";

$stmtCab = $conn->prepare($sqlCab);
if (!$stmtCab) {
    echo json_encode(['success' => false, 'message' => 'Error preparar consulta']);
    $conn->close();
    exit;
}
if ($types !== '') $stmtCab->bind_param($types, ...$params);
$stmtCab->execute();
$resCab = $stmtCab->get_result();
$programas = [];
while ($row = $resCab->fetch_assoc()) {
    $programas[] = [
        'codigo' => $row['codigo'],
        'nombre' => $row['nombre'],
        'codTipo' => (int)($row['codTipo'] ?? 0),
        'nomTipo' => $row['nomTipo'] ?? '',
        'zona' => $row['zona'] ?? '',
        'despliegue' => $tieneDespliegue ? ($row['despliegue'] ?? '') : '',
        'descripcion' => $row['descripcion'] ?? '',
        'fechaHoraRegistro' => $row['fechaHoraRegistro'] ?? ''
    ];
}
$stmtCab->close();

// Sigla por tipo (para columnas)
$siglasPorTipo = [];
$rTipos = $conn->query("SELECT codigo, sigla FROM san_dim_tipo_programa");
if ($rTipos) {
    while ($t = $rTipos->fetch_assoc()) {
        $s = strtoupper(trim($t['sigla'] ?? ''));
        if ($s === 'NEC') $s = 'NC';
        $siglasPorTipo[(int)$t['codigo']] = $s;
    }
}

$chkExtras = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'descripcionVacuna'");
$tieneExtras = $chkExtras && $chkExtras->fetch_assoc();
$chkPosDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'posDetalle'");
$tienePosDetalle = $chkPosDet && $chkPosDet->fetch_assoc();
$camposDet = $tieneExtras
    ? "ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad, descripcionVacuna, areaGalpon, cantidadPorGalpon"
    : "ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad";
if ($tienePosDetalle) $camposDet .= ", posDetalle";
$sqlDet = "SELECT " . $camposDet . " FROM san_fact_programa_det WHERE codPrograma = ? ORDER BY " . ($tienePosDetalle ? "posDetalle, id" : "id");
$stmtDet = $conn->prepare($sqlDet);
if (!$stmtDet) {
    echo json_encode(['success' => true, 'data' => $programas, 'detalles' => []]);
    $conn->close();
    exit;
}

$detallesPorPrograma = [];
foreach ($programas as $p) {
    $stmtDet->bind_param("s", $p['codigo']);
    $stmtDet->execute();
    $resDet = $stmtDet->get_result();
    $detalles = [];
    while ($d = $resDet->fetch_assoc()) {
        $detalles[] = $d;
    }
    $detallesPorPrograma[$p['codigo']] = $detalles;
}
$stmtDet->close();
$conn->close();

// Respuesta: programas con sus detalles y sigla para columnas
$resultado = [];
foreach ($programas as $p) {
    $resultado[] = [
        'cab' => $p,
        'detalles' => $detallesPorPrograma[$p['codigo']] ?? [],
        'sigla' => $siglasPorTipo[$p['codTipo']] ?? 'PL'
    ];
}

echo json_encode(['success' => true, 'data' => $resultado]);
