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
$crearNuevoPrograma = !empty($input['crearNuevoPrograma']);
$nuevoCodigo = $crearNuevoPrograma ? trim($input['nuevoCodigo'] ?? '') : '';
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
$tipoCDP = trim($input['tipo'] ?? '');
$fechaInicio = trim($input['fechaInicio'] ?? '');
$fechaFin = isset($input['fechaFin']) && $input['fechaFin'] !== '' && $input['fechaFin'] !== null ? trim($input['fechaFin']) : null;
$detalles = isset($input['detalles']) && is_array($input['detalles']) ? $input['detalles'] : [];
$esEspecial = isset($input['esEspecial']) ? (int)$input['esEspecial'] : 0;
$modoEspecial = isset($input['modoEspecial']) && $input['modoEspecial'] !== '' && $input['modoEspecial'] !== null ? trim((string)$input['modoEspecial']) : null;
$intervaloMeses = (isset($input['intervaloMeses']) && $input['intervaloMeses'] !== '' && $input['intervaloMeses'] !== null) ? max(1, min(12, (int)$input['intervaloMeses'])) : null;
$diaDelMes = (isset($input['diaDelMes']) && $input['diaDelMes'] !== '' && $input['diaDelMes'] !== null) ? max(1, min(31, (int)$input['diaDelMes'])) : null;
$fechasManuales = isset($input['fechasManuales']) && is_array($input['fechasManuales']) ? $input['fechasManuales'] : [];
$fechasManualesJson = !empty($fechasManuales) ? json_encode(array_values(array_filter(array_map('trim', $fechasManuales)))) : null;


$esSoloSeguimiento = (stripos($categoria ?? '', 'PROGRAMA SEGUIMIENTO') !== false) && (stripos($categoria ?? '', 'PROGRAMA SANITARIO') === false) && (stripos($categoria ?? '', 'SANIDAD') === false);
$permiteDetallesVacios = $esSoloSeguimiento || !empty($esEspecial);
if ($crearNuevoPrograma) {
    if (empty($nuevoCodigo) || empty($nombre) || $codTipo <= 0) {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Faltan nuevo código, nombre o tipo.']);
        exit;
    }
    if (empty($detalles) && !$permiteDetallesVacios) {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Debe agregar al menos una fila al detalle (Programa Sanitario requiere detalle).']);
        exit;
    }
    $codigoParaInsertar = $nuevoCodigo;
} else {
    if (empty($codigo) || empty($nombre) || $codTipo <= 0) {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Faltan código, nombre o tipo.']);
        exit;
    }
    if (empty($detalles) && !$permiteDetallesVacios) {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Debe agregar al menos una fila al detalle (Programa Sanitario requiere detalle).']);
        exit;
    }
    $codigoParaInsertar = $codigo;
}

// Si el programa está en cronograma, permitimos editar; al establecer fecha de fin se recortan registros posteriores (más abajo)
$chkCrono = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
$tieneTablaCrono = $chkCrono && $chkCrono->num_rows > 0;

if (empty($nomTipo)) {
    $st = $conn->prepare("SELECT nombre FROM san_dim_tipo_programa WHERE codigo = ?");
    $st->bind_param("i", $codTipo);
    $st->execute();
    $r = $st->get_result();
    if ($r && $row = $r->fetch_assoc()) $nomTipo = $row['nombre'];
    $st->close();
}

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
$colsTipo = $tieneTipoCab ? ", tipo" : "";
$valsTipo = $tieneTipoCab ? ", ?" : "";
$bindTipo = $tieneTipoCab ? "s" : "";
$colsCategoria = $tieneCategoriaCab ? ", categoria" : "";
$valsCategoria = $tieneCategoriaCab ? ", ?" : "";
$colsTipoCDP = $tieneTipoCDPCab ? ", tipoCDP" : "";
$valsTipoCDP = $tieneTipoCDPCab ? ", ?" : "";
$optParams = [];
if ($tieneTipoCab) $optParams[] = $tipo;
if ($tieneCategoriaCab) $optParams[] = $categoria;
if ($tieneTipoCDPCab) $optParams[] = $tipoCDP;
// fechas, intervaloMeses, diaDelMes solo en DET (no en CAB)
$colsEspecial = $tieneEspecialCab ? ", esEspecial, modoEspecial" : "";
$valsEspecial = $tieneEspecialCab ? ", ?, ?" : "";
$colsFechasManuales = "";
$valsFechasManuales = "";
if ($tieneEspecialCab) { $optParams[] = $esEspecial; $optParams[] = $modoEspecial; }
$bindOpt = ($tieneTipoCab ? 's' : '') . ($tieneCategoriaCab ? 's' : '') . ($tieneTipoCDPCab ? 's' : '') . ($tieneEspecialCab ? 'is' : '');
$colsExtra = $colsCategoria . $colsTipoCDP;
$valsExtra = $valsCategoria . $valsTipoCDP;
if ($tieneFechasCab && $fechaInicio === '') {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'La fecha de inicio es obligatoria.']);
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

if ($crearNuevoPrograma) {
    $codigoProgramaViejo = trim($input['codigoProgramaViejo'] ?? '');
    $hoy = date('Y-m-d');
    $ayer = date('Y-m-d', strtotime($hoy . ' -1 day'));

    // 1. Programa viejo: fechaFin = hoy - 1 (siempre que tengamos el código)
    if (!empty($codigoProgramaViejo) && $tieneFechasCab) {
        $stUpd = $conn->prepare("UPDATE san_fact_programa_cab SET fechaFin = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stUpd) {
            $usuarioMod = $_SESSION['usuario'] ?? 'WEB';
            $stUpd->bind_param("sss", $ayer, $usuarioMod, $codigoProgramaViejo);
            $stUpd->execute();
            $stUpd->close();
        }
    }

    // 2. Programa viejo: borrar asignaciones con fechaEjecucion >= hoy
    if (!empty($codigoProgramaViejo) && $tieneTablaCrono) {
        $chkFecCrono = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fechaEjecucion'");
        if ($chkFecCrono && $chkFecCrono->num_rows > 0) {
            $stDel = $conn->prepare("DELETE FROM san_fact_cronograma WHERE codPrograma = ? AND DATE(fechaEjecucion) >= ?");
            if ($stDel) {
                $stDel->bind_param("ss", $codigoProgramaViejo, $hoy);
                $stDel->execute();
                $stDel->close();
            }
        }
    }

    // 3. Nuevo programa: fechaInicio = hoy, fechaFin = la del formulario
    $fechaInicio = $hoy;
    $usuarioRegistro = $_SESSION['usuario'] ?? 'WEB';
    $edadCab = (count($detalles) > 0 && isset($detalles[0]['edad'])) ? (int)$detalles[0]['edad'] : 0;
    if ($tieneEdadCab && $tieneDespliegueCab && $tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, edad, zona, despliegue, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", ?, ?, NOW(), ?)");
        if ($stmtCab) {
            $params = array_merge([$codigoParaInsertar, $nombre, $codTipo, $nomTipo, $edadCab, $zona, $despliegue, $descripcion], $optParams, [$fechaInicio, $fechaFin, $usuarioRegistro]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("ssisisss" . $bindOpt . "sss"), $refs));
        }
    } elseif ($tieneDespliegueCab && $tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, despliegue, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", ?, ?, NOW(), ?)");
        if ($stmtCab) {
            $params = array_merge([$codigoParaInsertar, $nombre, $codTipo, $nomTipo, $zona, $despliegue, $descripcion], $optParams, [$fechaInicio, $fechaFin, $usuarioRegistro]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("ssissss" . $bindOpt . "sss"), $refs));
        }
    } elseif ($tieneEdadCab && $tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, edad, zona, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", ?, ?, NOW(), ?)");
        if ($stmtCab) {
            $params = array_merge([$codigoParaInsertar, $nombre, $codTipo, $nomTipo, $edadCab, $zona, $descripcion], $optParams, [$fechaInicio, $fechaFin, $usuarioRegistro]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("ssisiss" . $bindOpt . "sss"), $refs));
        }
    } elseif ($tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", ?, ?, NOW(), ?)");
        if ($stmtCab) {
            $params = array_merge([$codigoParaInsertar, $nombre, $codTipo, $nomTipo, $zona, $descripcion], $optParams, [$fechaInicio, $fechaFin, $usuarioRegistro]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("ssisss" . $bindOpt . "sss"), $refs));
        }
    } else {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, descripcion" . $colsTipo . $colsExtra . $colsEspecial . $colsFechasManuales . ", fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?" . $valsTipo . $valsExtra . $valsEspecial . $valsFechasManuales . ", NOW(), ?)");
        if ($stmtCab) {
            $params = array_merge([$codigoParaInsertar, $nombre, $codTipo, $nomTipo, $zona, $descripcion], $optParams, [$usuarioRegistro]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("ssisss" . $bindOpt . "s"), $refs));
        }
    }
    if (!$stmtCab || !$stmtCab->execute()) {
        if ($stmtCab) $stmtCab->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Error al crear nuevo programa: ' . $conn->error]);
        exit;
    }
    $stmtCab->close();
    if (empty($detalles) && $esEspecial === 1 && $permiteDetallesVacios) {
        $tolDet = 1;
        if (!empty($input['detalles'][0]['tolerancia'])) $tolDet = max(1, (int)$input['detalles'][0]['tolerancia']);
        $detalles = [['edad' => null, 'tolerancia' => $tolDet, 'fechas' => $fechasManuales, 'intervaloMeses' => $intervaloMeses, 'diaDelMes' => $diaDelMes]];
    }
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
        echo json_encode(['success' => false, 'message' => 'Error al preparar detalle nuevo programa.']);
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
        $fechasRow = (isset($d['fechas']) && is_array($d['fechas']) && !empty($d['fechas'])) ? json_encode(array_values(array_filter(array_map('trim', $d['fechas'])))) : ($esEspecial === 1 ? $fechasManualesJson : null);
        $intervaloRow = isset($d['intervaloMeses']) && $d['intervaloMeses'] !== '' ? max(1, min(12, (int)$d['intervaloMeses'])) : ($esEspecial === 1 ? $intervaloMeses : null);
        $diaRow = isset($d['diaDelMes']) && $d['diaDelMes'] !== '' ? max(1, min(31, (int)$d['diaDelMes'])) : ($esEspecial === 1 ? $diaDelMes : null);
        $paramsDet = [$codigoParaInsertar, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad];
        $typesDet = "ssssssssssss";  // edad como 's' para permitir NULL en programa especial sin edades
        if ($tieneExtras) { $paramsDet = array_merge($paramsDet, [$descripcionVacuna, $areaGalpon, $cantidadPorGalpon]); $typesDet .= "sii"; }
        if ($tienePosDetalle) { $paramsDet[] = $posDetalle; $typesDet .= "i"; }
        if ($tieneToleranciaDet) { $paramsDet[] = $tolerancia; $typesDet .= "i"; }
        if ($tieneFechasDet) { $paramsDet[] = $fechasRow; $typesDet .= "s"; }
        if ($tienePeriodicidadDet) { $paramsDet[] = $intervaloRow; $paramsDet[] = $diaRow; $typesDet .= "ii"; }
        $refsDet = []; foreach ($paramsDet as $k => $v) { $refsDet[$k] = &$paramsDet[$k]; }
        call_user_func_array([$stmtDet, 'bind_param'], array_merge([$typesDet], $refsDet));
        if (!$stmtDet->execute()) {
            $stmtDet->close();
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Error al guardar detalle nuevo programa.']);
            exit;
        }
    }
    $stmtDet->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Nuevo programa creado. Se debe recalcular el cronograma para el código ' . $nuevoCodigo . '.', 'nuevoCodigo' => $nuevoCodigo, 'recalcularCodigo' => $nuevoCodigo]);
    exit;
}

$usuarioModificacion = $_SESSION['usuario'] ?? 'WEB';

// UPDATE cabecera (usuarioModificacion y fechaHoraModificacion en tabla)
$edadCab = (count($detalles) > 0 && isset($detalles[0]['edad'])) ? (int)$detalles[0]['edad'] : 0;
$setTipo = $tieneTipoCab ? ", tipo = ?" : "";
$setCategoria = $tieneCategoriaCab ? ", categoria = ?" : "";
$setTipoCDP = $tieneTipoCDPCab ? ", tipoCDP = ?" : "";
$setEspecial = $tieneEspecialCab ? ", esEspecial = ?, modoEspecial = ?" : "";
$setFechasManuales = "";
$setExtra = $setTipo . $setCategoria . $setTipoCDP . $setEspecial . $setFechasManuales;
$optParamsUpd = [];
if ($tieneTipoCab) $optParamsUpd[] = $tipo;
if ($tieneCategoriaCab) $optParamsUpd[] = $categoria;
if ($tieneTipoCDPCab) $optParamsUpd[] = $tipoCDP;
if ($tieneEspecialCab) { $optParamsUpd[] = $esEspecial; $optParamsUpd[] = $modoEspecial; }
$bindOptUpd = ($tieneTipoCab ? 's' : '') . ($tieneCategoriaCab ? 's' : '') . ($tieneTipoCDPCab ? 's' : '') . ($tieneEspecialCab ? 'is' : '');
if ($tieneEdadCab && $tieneDespliegueCab) {
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, edad = ?, zona = ?, despliegue = ?, descripcion = ?" . $setExtra . ", fechaInicio = ?, fechaFin = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) {
            $params = array_merge([$nombre, $codTipo, $nomTipo, $edadCab, $zona, $despliegue, $descripcion], $optParamsUpd, [$fechaInicio, $fechaFin, $usuarioModificacion, $codigo]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("sisssss" . $bindOptUpd . "ssss"), $refs));
        }
    } else {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, edad = ?, zona = ?, despliegue = ?, descripcion = ?" . $setExtra . ", usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) {
            $params = array_merge([$nombre, $codTipo, $nomTipo, $edadCab, $zona, $despliegue, $descripcion], $optParamsUpd, [$usuarioModificacion, $codigo]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("sisssss" . $bindOptUpd . "ss"), $refs));
        }
    }
} elseif ($tieneDespliegueCab) {
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, zona = ?, despliegue = ?, descripcion = ?" . $setExtra . ", fechaInicio = ?, fechaFin = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) {
            $params = array_merge([$nombre, $codTipo, $nomTipo, $zona, $despliegue, $descripcion], $optParamsUpd, [$fechaInicio, $fechaFin, $usuarioModificacion, $codigo]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("sissss" . $bindOptUpd . "ssss"), $refs));
        }
    } else {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, zona = ?, despliegue = ?, descripcion = ?" . $setExtra . ", usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) {
            $params = array_merge([$nombre, $codTipo, $nomTipo, $zona, $despliegue, $descripcion], $optParamsUpd, [$usuarioModificacion, $codigo]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("sissss" . $bindOptUpd . "ss"), $refs));
        }
    }
} elseif ($tieneEdadCab) {
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, edad = ?, zona = ?, descripcion = ?" . $setExtra . ", fechaInicio = ?, fechaFin = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) {
            $params = array_merge([$nombre, $codTipo, $nomTipo, $edadCab, $zona, $descripcion], $optParamsUpd, [$fechaInicio, $fechaFin, $usuarioModificacion, $codigo]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("sisiss" . $bindOptUpd . "ssss"), $refs));
        }
    } else {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, edad = ?, zona = ?, descripcion = ?" . $setExtra . ", usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) {
            $params = array_merge([$nombre, $codTipo, $nomTipo, $edadCab, $zona, $descripcion], $optParamsUpd, [$usuarioModificacion, $codigo]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("sisiss" . $bindOptUpd . "ss"), $refs));
        }
    }
} else {
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, zona = ?, descripcion = ?" . $setExtra . ", fechaInicio = ?, fechaFin = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) {
            $params = array_merge([$nombre, $codTipo, $nomTipo, $zona, $descripcion], $optParamsUpd, [$fechaInicio, $fechaFin, $usuarioModificacion, $codigo]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("sisss" . $bindOptUpd . "ssss"), $refs));
        }
    } else {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, zona = ?, descripcion = ?" . $setExtra . ", usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) {
            $params = array_merge([$nombre, $codTipo, $nomTipo, $zona, $descripcion], $optParamsUpd, [$usuarioModificacion, $codigo]);
            $refs = array(); foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
            call_user_func_array(array($stmtCab, 'bind_param'), array_merge(array("sisss" . $bindOptUpd . "ss"), $refs));
        }
    }
}
if (!$stmtCab || !$stmtCab->execute()) {
    if ($stmtCab) $stmtCab->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al actualizar cabecera: ' . $conn->error]);
    exit;
}
$stmtCab->close();

// Cuando es especial sin detalles, construir 1 fila
if (empty($detalles) && $esEspecial === 1 && $permiteDetallesVacios) {
    $tolDet = 1;
    if (!empty($input['detalles'][0]['tolerancia'])) $tolDet = max(1, (int)$input['detalles'][0]['tolerancia']);
    $detalles = [['edad' => null, 'tolerancia' => $tolDet, 'fechas' => $fechasManuales, 'intervaloMeses' => $intervaloMeses, 'diaDelMes' => $diaDelMes]];
}

// Borrar detalle anterior
$stmtDel = $conn->prepare("DELETE FROM san_fact_programa_det WHERE codPrograma = ?");
if ($stmtDel) {
    $stmtDel->bind_param("s", $codigo);
    $stmtDel->execute();
    $stmtDel->close();
}

// Insertar nuevo detalle (incl. fechas, intervaloMeses, diaDelMes para especial)
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

    $fechasRow = (isset($d['fechas']) && is_array($d['fechas']) && !empty($d['fechas'])) ? json_encode(array_values(array_filter(array_map('trim', $d['fechas'])))) : ($esEspecial === 1 ? $fechasManualesJson : null);
    $intervaloRow = isset($d['intervaloMeses']) && $d['intervaloMeses'] !== '' ? max(1, min(12, (int)$d['intervaloMeses'])) : ($esEspecial === 1 ? $intervaloMeses : null);
    $diaRow = isset($d['diaDelMes']) && $d['diaDelMes'] !== '' ? max(1, min(31, (int)$d['diaDelMes'])) : ($esEspecial === 1 ? $diaDelMes : null);
    $paramsDet = [$codigo, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad];
    $typesDet = "ssssssssssss";  // edad como 's' para permitir NULL en programa especial sin edades
    if ($tieneExtras) { $paramsDet = array_merge($paramsDet, [$descripcionVacuna, $areaGalpon, $cantidadPorGalpon]); $typesDet .= "sii"; }
    if ($tienePosDetalle) { $paramsDet[] = $posDetalle; $typesDet .= "i"; }
    if ($tieneToleranciaDet) { $paramsDet[] = $tolerancia; $typesDet .= "i"; }
    if ($tieneFechasDet) { $paramsDet[] = $fechasRow; $typesDet .= "s"; }
    if ($tienePeriodicidadDet) { $paramsDet[] = $intervaloRow; $paramsDet[] = $diaRow; $typesDet .= "ii"; }
    $refsDet = []; foreach ($paramsDet as $k => $v) { $refsDet[$k] = &$paramsDet[$k]; }
    call_user_func_array([$stmtDet, 'bind_param'], array_merge([$typesDet], $refsDet));
    if (!$stmtDet->execute()) {
        $stmtDet->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Error al guardar detalle: ' . $conn->error]);
        exit;
    }
}

$stmtDet->close();

// No eliminar aquí registros del cronograma: cuando hay cambio de fechas, el frontend llama a
// recalcular_fechas_programa_editado.php que elimina >= hoy e inserta respetando fechaFin.
// Evita borrar todo antes del recálculo y perder el scope (numCronograma, despliegue).

$conn->close();
echo json_encode(['success' => true, 'message' => 'Programa actualizado correctamente.', 'recalcularCodigo' => $codigo]);
