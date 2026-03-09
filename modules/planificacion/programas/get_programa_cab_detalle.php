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

$codigo = trim((string)($_GET['codigo'] ?? $_POST['codigo'] ?? ''));
if ($codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Falta código de programa']);
    $conn->close();
    exit;
}

$chkDespliegue = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'despliegue'");
$tieneDespliegue = $chkDespliegue && $chkDespliegue->fetch_assoc();
$chkFechaInicio = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'fechaInicio'");
$tieneFechas = $chkFechaInicio && $chkFechaInicio->fetch_assoc();
$chkTipo = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'tipo'");
$tieneTipo = $chkTipo && $chkTipo->fetch_assoc();
$chkCategoria = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'categoria'");
$tieneCategoria = $chkCategoria && $chkCategoria->fetch_assoc();
$chkTipoCDP = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'tipoCDP'");
$tieneTipoCDP = $chkTipoCDP && $chkTipoCDP->fetch_assoc();
$chkEsEspecial = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'esEspecial'");
$tieneEspecial = $chkEsEspecial && $chkEsEspecial->fetch_assoc();

$sqlCab = "SELECT c.codigo, c.nombre, c.codTipo, c.nomTipo, c.descripcion, c.fechaHoraRegistro";
if ($tieneTipo) $sqlCab .= ", c.tipo";
if ($tieneCategoria) $sqlCab .= ", c.categoria";
if ($tieneTipoCDP) $sqlCab .= ", c.tipoCDP";
if ($tieneDespliegue) $sqlCab .= ", c.despliegue";
if ($tieneFechas) $sqlCab .= ", c.fechaInicio, c.fechaFin";
if ($tieneEspecial) $sqlCab .= ", c.esEspecial, c.modoEspecial";
// intervaloMeses, diaDelMes, fechas, tolerancia solo en DET (no en CAB)
$sqlCab .= " FROM san_fact_programa_cab c WHERE c.codigo = ? LIMIT 1";
$stmtCab = $conn->prepare($sqlCab);
if (!$stmtCab) {
    echo json_encode(['success' => false, 'message' => 'Error preparar consulta']);
    $conn->close();
    exit;
}
$stmtCab->bind_param("s", $codigo);
$stmtCab->execute();
$resCab = $stmtCab->get_result();
$cab = $resCab->fetch_assoc();
$stmtCab->close();

if (!$cab) {
    echo json_encode(['success' => false, 'message' => 'Programa no encontrado']);
    $conn->close();
    exit;
}

$cab['despliegue'] = $tieneDespliegue ? ($cab['despliegue'] ?? '') : '';
$cab['fechaInicio'] = $tieneFechas ? ($cab['fechaInicio'] ?? '') : '';
$cab['fechaFin'] = $tieneFechas ? ($cab['fechaFin'] ?? '') : '';
$cab['tipo'] = $tieneTipoCDP ? trim($cab['tipoCDP'] ?? '') : ($tieneTipo ? ($cab['tipo'] ?? '') : '');
$cab['categoria'] = $tieneCategoria ? ($cab['categoria'] ?? '') : '';
$cab['esEspecial'] = $tieneEspecial ? (int)($cab['esEspecial'] ?? 0) : 0;
$cab['modoEspecial'] = $tieneEspecial ? trim($cab['modoEspecial'] ?? '') : '';

$chkExtras = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'descripcionVacuna'");
$tieneExtras = $chkExtras && $chkExtras->fetch_assoc();
$chkPosDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'posDetalle'");
$tienePosDetalle = $chkPosDet && $chkPosDet->fetch_assoc();
$chkTolerancia = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'tolerancia'");
$tieneTolerancia = $chkTolerancia && $chkTolerancia->fetch_assoc();
$chkFechasDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'fechas'");
$tieneFechasDet = $chkFechasDet && $chkFechasDet->fetch_assoc();
$chkIntervaloDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'intervaloMeses'");
$chkDiaDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'diaDelMes'");
$tienePeriodicidadDet = $chkIntervaloDet && $chkIntervaloDet->fetch_assoc() && $chkDiaDet && $chkDiaDet->fetch_assoc();
$camposDet = $tieneExtras
    ? "ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad, descripcionVacuna, areaGalpon, cantidadPorGalpon"
    : "ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad";
if ($tienePosDetalle) $camposDet .= ", posDetalle";
if ($tieneTolerancia) $camposDet .= ", tolerancia";
if ($tieneFechasDet) $camposDet .= ", fechas";
if ($tienePeriodicidadDet) $camposDet .= ", intervaloMeses, diaDelMes";
$sqlDet = "SELECT " . $camposDet . " FROM san_fact_programa_det WHERE codPrograma = ? ORDER BY " . ($tienePosDetalle ? "posDetalle, id" : "id");
$stmtDet = $conn->prepare($sqlDet);
$detalles = [];
if ($stmtDet) {
    $stmtDet->bind_param("s", $codigo);
    $stmtDet->execute();
    $resDet = $stmtDet->get_result();
    while ($d = $resDet->fetch_assoc()) {
        $detalles[] = $d;
    }
    $stmtDet->close();
}

// intervaloMeses, diaDelMes, fechas, tolerancia: solo en DET. Rellenar cab.* para compatibilidad con el formulario.
$cab['intervaloMeses'] = null;
$cab['diaDelMes'] = null;
$cab['fechasManuales'] = [];
$cab['toleranciaEspecial'] = null;
if (!empty($detalles)) {
    if ($tieneFechasDet && isset($detalles[0]['fechas']) && $detalles[0]['fechas'] !== null && $detalles[0]['fechas'] !== '') {
        $dec = json_decode($detalles[0]['fechas'], true);
        $cab['fechasManuales'] = is_array($dec) ? $dec : [];
    }
    if ($tienePeriodicidadDet && isset($detalles[0]['intervaloMeses']) && $detalles[0]['intervaloMeses'] !== null && $detalles[0]['intervaloMeses'] !== '') {
        $cab['intervaloMeses'] = (int)$detalles[0]['intervaloMeses'];
    }
    if ($tienePeriodicidadDet && isset($detalles[0]['diaDelMes']) && $detalles[0]['diaDelMes'] !== null && $detalles[0]['diaDelMes'] !== '') {
        $cab['diaDelMes'] = (int)$detalles[0]['diaDelMes'];
    }
    if ($tieneTolerancia && isset($detalles[0]['tolerancia']) && $detalles[0]['tolerancia'] !== null && $detalles[0]['tolerancia'] !== '') {
        $cab['toleranciaEspecial'] = (int)$detalles[0]['tolerancia'];
    }
}

// Sigla del tipo (para columnas como en listado/reporte)
$sigla = 'PL';
$codTipo = (int)($cab['codTipo'] ?? 0);
if ($codTipo > 0) {
    $stSigla = $conn->prepare("SELECT sigla FROM san_dim_tipo_programa WHERE codigo = ? LIMIT 1");
    $stSigla->bind_param("i", $codTipo);
    $stSigla->execute();
    $rSigla = $stSigla->get_result();
    if ($rSigla) {
        $row = $rSigla->fetch_assoc();
        if ($row && !empty(trim($row['sigla'] ?? ''))) {
            $sigla = strtoupper(trim($row['sigla']));
            if ($sigla === 'NEC') $sigla = 'NC';
        }
    }
    $stSigla->close();
}

$conn->close();
echo json_encode(['success' => true, 'cab' => $cab, 'detalles' => $detalles, 'sigla' => $sigla]);
