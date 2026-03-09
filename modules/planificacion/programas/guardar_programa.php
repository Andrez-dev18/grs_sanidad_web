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

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$codigo = trim($input['codigo'] ?? '');
$nombre = trim($input['nombre'] ?? '');
$codTipo = (int)($input['codTipo'] ?? 0);
$nomTipo = trim($input['nomTipo'] ?? '');
$zona = '';
$subzona = '';
$despliegue = trim($input['despliegue'] ?? '');
$descripcion = trim($input['descripcion'] ?? '');
$tipo = trim($input['tipo'] ?? '');
$categoria = trim($input['categoria'] ?? '');
$tipoCDP = trim($input['tipo'] ?? ''); // tipo de control de plagas (cuando codigo es CP)
$fechaInicio = trim($input['fechaInicio'] ?? '');
$fechaFin = isset($input['fechaFin']) && $input['fechaFin'] !== '' && $input['fechaFin'] !== null ? trim($input['fechaFin']) : null;
$detalles = isset($input['detalles']) && is_array($input['detalles']) ? $input['detalles'] : [];
$esEspecial = isset($input['esEspecial']) ? (int)$input['esEspecial'] : 0;
$modoEspecial = isset($input['modoEspecial']) && $input['modoEspecial'] !== '' && $input['modoEspecial'] !== null ? trim((string)$input['modoEspecial']) : null;
$intervaloMeses = (isset($input['intervaloMeses']) && $input['intervaloMeses'] !== '' && $input['intervaloMeses'] !== null) ? max(1, min(12, (int)$input['intervaloMeses'])) : null;
$diaDelMes = (isset($input['diaDelMes']) && $input['diaDelMes'] !== '' && $input['diaDelMes'] !== null) ? max(1, min(31, (int)$input['diaDelMes'])) : null;
$fechasManuales = isset($input['fechasManuales']) && is_array($input['fechasManuales']) ? $input['fechasManuales'] : [];
$fechasManualesJson = !empty($fechasManuales) ? json_encode(array_values(array_filter(array_map('trim', $fechasManuales)))) : null;


if (empty($codigo) || empty($nombre) || $codTipo <= 0) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Faltan código, nombre o tipo.']);
    exit;
}

$esSoloSeguimiento = (stripos($categoria, 'PROGRAMA SEGUIMIENTO') !== false) && (stripos($categoria, 'PROGRAMA SANITARIO') === false) && (stripos($categoria, 'SANIDAD') === false);
$permiteDetallesVacios = $esSoloSeguimiento || $esEspecial;
if (empty($detalles) && !$permiteDetallesVacios) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Debe agregar al menos una fila al detalle (Programa Sanitario requiere detalle).']);
    exit;
}

// Nombre del tipo si no vino
if (empty($nomTipo)) {
    $st = $conn->prepare("SELECT nombre FROM san_dim_tipo_programa WHERE codigo = ?");
    $st->bind_param("i", $codTipo);
    $st->execute();
    $r = $st->get_result();
    if ($r && $row = $r->fetch_assoc()) $nomTipo = $row['nombre'];
    $st->close();
}

$usuarioRegistro = $_SESSION['usuario'] ?? 'WEB';

// Cabecera: tipo, código, nombre, zona, despliegue (si existe), descripción. Edad en detalle si existe columna.
$chkEdadCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'edad'");
$tieneEdadCab = $chkEdadCab && $chkEdadCab->fetch_assoc();
$chkDespliegueCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'despliegue'");
$tieneDespliegueCab = $chkDespliegueCab && $chkDespliegueCab->fetch_assoc();
$chkFechaInicioCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'fechaInicio'");
$tieneFechasCab = $chkFechaInicioCab && $chkFechaInicioCab->fetch_assoc();
$chkTipoCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'tipo'");
$tieneTipoCab = $chkTipoCab && $chkTipoCab->fetch_assoc();
$chkCategoriaCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'categoria'");
$tieneCategoriaCab = $chkCategoriaCab && $chkCategoriaCab->fetch_assoc();
$chkTipoCDPCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'tipoCDP'");
$tieneTipoCDPCab = $chkTipoCDPCab && $chkTipoCDPCab->fetch_assoc();
$chkEsEspecialCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'esEspecial'");
$tieneEspecialCab = $chkEsEspecialCab && $chkEsEspecialCab->fetch_assoc();
$chkFechasManualesCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'fechas_manuales'");
$tieneFechasManualesCab = $chkFechasManualesCab && $chkFechasManualesCab->fetch_assoc();
if ($tieneFechasCab && $fechaInicio === '') {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'La fecha de inicio es obligatoria.']);
    exit;
}
$colsTipo = $tieneTipoCab ? ", tipo" : "";
$valsTipo = $tieneTipoCab ? ", ?" : "";
$bindTipo = $tieneTipoCab ? "s" : "";
$colsCategoria = $tieneCategoriaCab ? ", categoria" : "";
$valsCategoria = $tieneCategoriaCab ? ", ?" : "";
$colsTipoCDP = $tieneTipoCDPCab ? ", tipoCDP" : "";
$valsTipoCDP = $tieneTipoCDPCab ? ", ?" : "";
$colsExtra = $colsCategoria . $colsTipoCDP;
$valsExtra = $valsCategoria . $valsTipoCDP;
// fechas, intervaloMeses, diaDelMes solo en DET (no en CAB)
$colsEspecial = $tieneEspecialCab ? ", esEspecial, modoEspecial" : "";
$valsEspecial = $tieneEspecialCab ? ", ?, ?" : "";
$colsFechasManuales = ""; // ya no en CAB
$valsFechasManuales = "";
$optParams = [];
if ($tieneTipoCab) $optParams[] = $tipo;
if ($tieneCategoriaCab) $optParams[] = $categoria;
if ($tieneTipoCDPCab) $optParams[] = $tipoCDP;
if ($tieneEspecialCab) $optParams[] = $esEspecial;
if ($tieneEspecialCab) $optParams[] = $modoEspecial;
$bindOpt = ($tieneTipoCab ? 's' : '') . ($tieneCategoriaCab ? 's' : '') . ($tieneTipoCDPCab ? 's' : '') . ($tieneEspecialCab ? 'is' : '');
if ($tieneCategoriaCab && trim($categoria) === '') {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Marque al menos una categoría (Programa Sanitario y/o Programa Seguimiento).']);
    exit;
}

// Programa especial: debe tener modo PERIODICIDAD (con intervaloMeses/diaDelMes) o MANUAL (con fechas)
if ($tieneEspecialCab && $esEspecial === 1) {
    $modoUpper = $modoEspecial ? strtoupper(trim($modoEspecial)) : '';
    if ($modoUpper === 'PERIODICIDAD') {
        if ($intervaloMeses === null || $intervaloMeses < 1 || $diaDelMes === null || $diaDelMes < 1) {
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Programa especial con periodicidad: indique intervalo de meses y día del mes.']);
            exit;
        }
    } elseif ($modoUpper === 'MANUAL') {
        if (empty($fechasManuales) || $fechasManualesJson === null) {
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Programa especial con modo manual: agregue al menos una fecha manual.']);
            exit;
        }
    } else {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Programa especial: seleccione Periodicidad (intervalo y día) o Manual (fechas específicas).']);
        exit;
    }
}
if ($tieneEdadCab && $tieneDespliegueCab) {
    $edadCab = (count($detalles) > 0 && isset($detalles[0]['edad'])) ? (int)$detalles[0]['edad'] : 0;
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, edad, zona, despliegue, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", ?, ?, NOW(), ?)");
        if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
        $params = array_merge([$codigo, $nombre, $codTipo, $nomTipo, $edadCab, $zona, $despliegue, $descripcion], $optParams, [$fechaInicio, $fechaFin, $usuarioRegistro]);
        $types = "ssisisss" . $bindOpt . "sss";
        if (strlen($types) !== count($params)) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error interno: desajuste parámetros cabecera (edad+despl+fechas).']); exit; }
        $refs = array();
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array($types), $refs));
    } else {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, edad, zona, despliegue, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", NOW(), ?)");
        if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
        $params = array_merge([$codigo, $nombre, $codTipo, $nomTipo, $edadCab, $zona, $despliegue, $descripcion], $optParams, [$usuarioRegistro]);
        $types = "ssisisss" . $bindOpt . "s";
        if (strlen($types) !== count($params)) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error interno: desajuste parámetros cabecera (edad+despl).']); exit; }
        $refs = array();
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array($types), $refs));
    }
} elseif ($tieneDespliegueCab) {
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, despliegue, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", ?, ?, NOW(), ?)");
        if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
        $params = array_merge([$codigo, $nombre, $codTipo, $nomTipo, $zona, $despliegue, $descripcion], $optParams, [$fechaInicio, $fechaFin, $usuarioRegistro]);
        $types = "ssissss" . $bindOpt . "sss";
        if (strlen($types) !== count($params)) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error interno: desajuste parámetros cabecera (despl+fechas).']); exit; }
        $refs = array();
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array($types), $refs));
    } else {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, despliegue, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", NOW(), ?)");
        if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
        $params = array_merge([$codigo, $nombre, $codTipo, $nomTipo, $zona, $despliegue, $descripcion], $optParams, [$usuarioRegistro]);
        $types = "ssissss" . $bindOpt . "s";
        if (strlen($types) !== count($params)) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error interno: desajuste parámetros cabecera (despl).']); exit; }
        $refs = array();
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array($types), $refs));
    }
} elseif ($tieneEdadCab) {
    $edadCab = (count($detalles) > 0 && isset($detalles[0]['edad'])) ? (int)$detalles[0]['edad'] : 0;
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, edad, zona, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", ?, ?, NOW(), ?)");
        if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
        $params = array_merge([$codigo, $nombre, $codTipo, $nomTipo, $edadCab, $zona, $descripcion], $optParams, [$fechaInicio, $fechaFin, $usuarioRegistro]);
        $types = "ssisiss" . $bindOpt . "sss";
        if (strlen($types) !== count($params)) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error interno: desajuste parámetros cabecera (edad+fechas).']); exit; }
        $refs = array();
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array($types), $refs));
    } else {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, edad, zona, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", NOW(), ?)");
        if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
        $params = array_merge([$codigo, $nombre, $codTipo, $nomTipo, $edadCab, $zona, $descripcion], $optParams, [$usuarioRegistro]);
        $types = "ssisiss" . $bindOpt . "s";
        if (strlen($types) !== count($params)) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error interno: desajuste parámetros cabecera (edad).']); exit; }
        $refs = array();
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array($types), $refs));
    }
} else {
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", ?, ?, NOW(), ?)");
        if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
        $params = array_merge([$codigo, $nombre, $codTipo, $nomTipo, $zona, $descripcion], $optParams, [$fechaInicio, $fechaFin, $usuarioRegistro]);
        $types = "ssisss" . $bindOpt . "sss";
        if (strlen($types) !== count($params)) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error interno: desajuste parámetros cabecera (base+fechas).']); exit; }
        $refs = array();
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array($types), $refs));
    } else {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", NOW(), ?)");
        if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
        $params = array_merge([$codigo, $nombre, $codTipo, $nomTipo, $zona, $descripcion], $optParams, [$usuarioRegistro]);
        $types = "ssisss" . $bindOpt . "s";
        if (strlen($types) !== count($params)) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error interno: desajuste parámetros cabecera (base).']); exit; }
        $refs = array();
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array($types), $refs));
    }
}
if (!$stmtCab->execute()) {
    $errMsg = $stmtCab->error ?: $conn->error ?: 'Error desconocido';
    $stmtCab->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al guardar cabecera: ' . $errMsg]);
    exit;
}
$stmtCab->close();

// Cuando es especial sin detalles, construir 1 fila con fechas/periodicidad/tolerancia (todo en DET)
if (empty($detalles) && $esEspecial === 1 && $permiteDetallesVacios) {
    $tolDet = 1;
    if (!empty($input['detalles'][0]['tolerancia'])) {
        $tolDet = max(1, (int)$input['detalles'][0]['tolerancia']);
    }
    $detEsp = [
        'ubicacion' => '', 'codProducto' => '', 'nomProducto' => '', 'codProveedor' => '', 'nomProveedor' => '',
        'unidades' => '', 'dosis' => '', 'unidadDosis' => '', 'numeroFrascos' => '', 'edad' => null,
        'descripcionVacuna' => '', 'areaGalpon' => null, 'cantidadPorGalpon' => null, 'posDetalle' => 1,
        'tolerancia' => $tolDet,
        'fechas' => $fechasManuales,
        'intervaloMeses' => $intervaloMeses,
        'diaDelMes' => $diaDelMes
    ];
    $detalles = [$detEsp];
}

// Detalle: una fila por cada elemento (incl. fechas, intervaloMeses, diaDelMes para programa especial)
$chkCols = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'descripcionVacuna'");
$tieneExtras = $chkCols && $chkCols->fetch_assoc();
$chkPosDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'posDetalle'");
$tienePosDetalle = $chkPosDet && $chkPosDet->fetch_assoc();
$chkTolDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'tolerancia'");
$tieneToleranciaDet = $chkTolDet && $chkTolDet->fetch_assoc();
$chkFechasDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'fechas'");
$tieneFechasDet = $chkFechasDet && $chkFechasDet->fetch_assoc();
$chkIntervaloDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'intervaloMeses'");
$chkDiaDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'diaDelMes'");
$tienePeriodicidadDet = $chkIntervaloDet && $chkIntervaloDet->fetch_assoc() && $chkDiaDet && $chkDiaDet->fetch_assoc();

$colTol = $tieneToleranciaDet ? ", tolerancia" : "";
$valTol = $tieneToleranciaDet ? ", ?" : "";
$colFechas = ($tieneFechasDet ? ", fechas" : "") . ($tienePeriodicidadDet ? ", intervaloMeses, diaDelMes" : "");
$valFechas = ($tieneFechasDet ? ", ?" : "") . ($tienePeriodicidadDet ? ", ?, ?" : "");

if ($tieneExtras && $tienePosDetalle) {
    $stmtDet = $conn->prepare("INSERT INTO san_fact_programa_det (codPrograma, nomPrograma, codProducto, nomProducto, codProveedor, nomProveedor, ubicacion, unidades, dosis, unidadDosis, numeroFrascos, edad, descripcionVacuna, areaGalpon, cantidadPorGalpon, posDetalle" . $colTol . $colFechas . ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?" . $valTol . $valFechas . ")");
} elseif ($tieneExtras) {
    $stmtDet = $conn->prepare("INSERT INTO san_fact_programa_det (codPrograma, nomPrograma, codProducto, nomProducto, codProveedor, nomProveedor, ubicacion, unidades, dosis, unidadDosis, numeroFrascos, edad, descripcionVacuna, areaGalpon, cantidadPorGalpon" . $colTol . $colFechas . ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?" . $valTol . $valFechas . ")");
} elseif ($tienePosDetalle) {
    $stmtDet = $conn->prepare("INSERT INTO san_fact_programa_det (codPrograma, nomPrograma, codProducto, nomProducto, codProveedor, nomProveedor, ubicacion, unidades, dosis, unidadDosis, numeroFrascos, edad, posDetalle" . $colTol . $colFechas . ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?" . $valTol . $valFechas . ")");
} else {
    $stmtDet = $conn->prepare("INSERT INTO san_fact_programa_det (codPrograma, nomPrograma, codProducto, nomProducto, codProveedor, nomProveedor, ubicacion, unidades, dosis, unidadDosis, numeroFrascos, edad" . $colTol . $colFechas . ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?" . $valTol . $valFechas . ")");
}
if (!$stmtDet) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al preparar detalle: ' . $conn->error]);
    exit;
}

foreach ($detalles as $d) {
    $ubicacion = trim($d['ubicacion'] ?? '');
    $codProducto = trim($d['codProducto'] ?? '');
    $nomProducto = trim($d['nomProducto'] ?? '');
    $codProveedor = trim($d['codProveedor'] ?? '');
    $nomProveedor = trim($d['nomProveedor'] ?? '');
    $unidades = trim($d['unidades'] ?? '');
    $dosis = trim($d['dosis'] ?? '');
    $unidadDosis = trim($d['unidadDosis'] ?? '');
    $numeroFrascos = trim($d['numeroFrascos'] ?? '');
    $edad = (isset($d['edad']) && $d['edad'] !== null && $d['edad'] !== '') ? (int)$d['edad'] : null;
    $descripcionVacuna = trim($d['descripcionVacuna'] ?? '');
    // descripcionVacuna solo si el producto es vacuna (san_rel_vacuna_enfermedad: codVacuna = mitm.codigo o codProducto)
    if ($descripcionVacuna !== '' && $codProducto !== '') {
        $chkColVac = @$conn->query("SHOW COLUMNS FROM san_rel_vacuna_enfermedad LIKE 'codVacuna'");
        $colVacuna = ($chkColVac && $chkColVac->fetch_assoc()) ? 'codVacuna' : 'codProducto';
        $chkVac = $conn->prepare("SELECT 1 FROM san_rel_vacuna_enfermedad WHERE " . $colVacuna . " = ? LIMIT 1");
        if ($chkVac) {
            $chkVac->bind_param("s", $codProducto);
            $chkVac->execute();
            $rV = $chkVac->get_result();
            if (!$rV || !$rV->fetch_assoc()) $descripcionVacuna = '';
            $chkVac->close();
        }
    }
    $areaGalpon = isset($d['areaGalpon']) && $d['areaGalpon'] !== '' && $d['areaGalpon'] !== null ? (int)$d['areaGalpon'] : null;
    $cantidadPorGalpon = isset($d['cantidadPorGalpon']) && $d['cantidadPorGalpon'] !== '' && $d['cantidadPorGalpon'] !== null ? (int)$d['cantidadPorGalpon'] : null;
    $posDetalle = isset($d['posDetalle']) ? (int)$d['posDetalle'] : 1;
    if ($posDetalle < 1) $posDetalle = 1;
    $tolerancia = isset($d['tolerancia']) ? max(1, (int)$d['tolerancia']) : 1;
    // Fechas/periodicidad: desde la fila o desde input cuando es especial
    $fechasRow = null;
    if (isset($d['fechas']) && is_array($d['fechas']) && !empty($d['fechas'])) {
        $fechasRow = json_encode(array_values(array_filter(array_map('trim', $d['fechas']))));
    } elseif ($esEspecial === 1 && $fechasManualesJson !== null) {
        $fechasRow = $fechasManualesJson;
    }
    $intervaloRow = isset($d['intervaloMeses']) && $d['intervaloMeses'] !== '' && $d['intervaloMeses'] !== null ? max(1, min(12, (int)$d['intervaloMeses'])) : ($esEspecial === 1 ? $intervaloMeses : null);
    $diaRow = isset($d['diaDelMes']) && $d['diaDelMes'] !== '' && $d['diaDelMes'] !== null ? max(1, min(31, (int)$d['diaDelMes'])) : ($esEspecial === 1 ? $diaDelMes : null);

    $paramsDet = [$codigo, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad];
    $typesDet = "ssssssssssss";  // edad como 's' para permitir NULL en programa especial sin edades
    if ($tieneExtras) {
        $paramsDet = array_merge($paramsDet, [$descripcionVacuna, $areaGalpon, $cantidadPorGalpon]);
        $typesDet .= "sii";
    }
    if ($tienePosDetalle) {
        $paramsDet[] = $posDetalle;
        $typesDet .= "i";
    }
    if ($tieneToleranciaDet) {
        $paramsDet[] = $tolerancia;
        $typesDet .= "i";
    }
    if ($tieneFechasDet) {
        $paramsDet[] = $fechasRow;
        $typesDet .= "s";
    }
    if ($tienePeriodicidadDet) {
        $paramsDet[] = $intervaloRow;
        $paramsDet[] = $diaRow;
        $typesDet .= "ii";
    }
    $refsDet = [];
    foreach ($paramsDet as $k => $v) { $refsDet[$k] = &$paramsDet[$k]; }
    call_user_func_array([$stmtDet, 'bind_param'], array_merge([$typesDet], $refsDet));
    if (!$stmtDet->execute()) {
        $stmtDet->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Error al guardar detalle: ' . $conn->error]);
        exit;
    }
}

$stmtDet->close();
echo json_encode(['success' => true, 'message' => 'Programa registrado correctamente.']);
$conn->close();
