<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
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
$chkFechas = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'fechaInicio'");
$tieneFechasCab = $chkFechas && $chkFechas->num_rows > 0;
$chkCategoria = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'categoria'");
$tieneCategoria = $chkCategoria && $chkCategoria->fetch_assoc();
$chkEsEspecial = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'esEspecial'");
$tieneEsEspecial = $chkEsEspecial && $chkEsEspecial->num_rows > 0;

$sqlCab = "SELECT c.codigo, c.nombre, c.codTipo, c.nomTipo, c.descripcion, c.fechaHoraRegistro";
if ($tieneDespliegue) $sqlCab .= ", c.despliegue";
if ($tieneFechasCab) $sqlCab .= ", c.fechaInicio, c.fechaFin";
if ($tieneCategoria) $sqlCab .= ", c.categoria";
if ($tieneEsEspecial) $sqlCab .= ", c.esEspecial, c.modoEspecial";
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
    $prog = [
        'codigo' => $row['codigo'],
        'nombre' => $row['nombre'],
        'codTipo' => (int)($row['codTipo'] ?? 0),
        'nomTipo' => $row['nomTipo'] ?? '',
        'despliegue' => $tieneDespliegue ? ($row['despliegue'] ?? '') : '',
        'descripcion' => $row['descripcion'] ?? '',
        'fechaHoraRegistro' => $row['fechaHoraRegistro'] ?? '',
        'fechaInicio' => $tieneFechasCab ? ($row['fechaInicio'] ?? '') : '',
        'fechaFin' => $tieneFechasCab ? ($row['fechaFin'] ?? '') : '',
        'categoria' => $tieneCategoria ? ($row['categoria'] ?? '') : ''
    ];
    if ($tieneEsEspecial) {
        $prog['esEspecial'] = (int)($row['esEspecial'] ?? 0);
        $prog['modoEspecial'] = trim($row['modoEspecial'] ?? '');
        $prog['intervaloMeses'] = null;
        $prog['diaDelMes'] = null;
        $prog['fechasManuales'] = [];
    }
    $programas[] = $prog;
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
$chkTolDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'tolerancia'");
$tieneTolDet = $chkTolDet && $chkTolDet->num_rows > 0;
$chkFechasDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'fechas'");
$chkIntervaloDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'intervaloMeses'");
$chkDiaDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'diaDelMes'");
$tieneFechasDet = $chkFechasDet && $chkFechasDet->num_rows > 0;
$tienePeriodicidadDet = $chkIntervaloDet && $chkIntervaloDet->num_rows > 0 && $chkDiaDet && $chkDiaDet->num_rows > 0;
$camposDet = $tieneExtras
    ? "ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad, descripcionVacuna, areaGalpon, cantidadPorGalpon"
    : "ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad";
if ($tienePosDetalle) $camposDet .= ", posDetalle";
if ($tieneTolDet) $camposDet .= ", tolerancia";
if ($tieneFechasDet) $camposDet .= ", fechas";
if ($tienePeriodicidadDet) $camposDet .= ", intervaloMeses, diaDelMes";
$sqlDet = "SELECT " . $camposDet . " FROM san_fact_programa_det WHERE codPrograma = ? ORDER BY " . ($tienePosDetalle ? "posDetalle, id" : "id");
$stmtDet = $conn->prepare($sqlDet);
if (!$stmtDet) {
    echo json_encode(['success' => true, 'data' => $programas, 'detalles' => []]);
    $conn->close();
    exit;
}

$detallesPorPrograma = [];
foreach ($programas as $idx => $p) {
    $stmtDet->bind_param("s", $p['codigo']);
    $stmtDet->execute();
    $resDet = $stmtDet->get_result();
    $detalles = [];
    while ($d = $resDet->fetch_assoc()) {
        $detalles[] = $d;
    }
    $detallesPorPrograma[$p['codigo']] = $detalles;
    // intervaloMeses, diaDelMes, fechasManuales solo en DET
    if (!empty($detalles) && !empty($p['esEspecial'])) {
        $d0 = $detalles[0];
        if ($tienePeriodicidadDet && isset($d0['intervaloMeses']) && $d0['intervaloMeses'] !== null && $d0['intervaloMeses'] !== '') {
            $programas[$idx]['intervaloMeses'] = (int)$d0['intervaloMeses'];
        }
        if ($tienePeriodicidadDet && isset($d0['diaDelMes']) && $d0['diaDelMes'] !== null && $d0['diaDelMes'] !== '') {
            $programas[$idx]['diaDelMes'] = (int)$d0['diaDelMes'];
        }
        if ($tieneFechasDet && isset($d0['fechas']) && $d0['fechas'] !== null && $d0['fechas'] !== '') {
            $dec = json_decode($d0['fechas'], true);
            $programas[$idx]['fechasManuales'] = is_array($dec) ? $dec : [];
        }
    }
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
