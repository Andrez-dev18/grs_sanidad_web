<?php
/* Recalcula fechas de asignaciones al editar programa . Usado cuando se cambian fechas o detalles. */

header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado', 'total' => 0]);
    exit;
}
include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión', 'total' => 0]);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST ?: [];
$debug = (isset($_GET['debug']) && $_GET['debug'] !== '0') || !empty($input['debug']);
// Parámetros de entrada
$codPrograma = trim($input['codPrograma'] ?? $_GET['codPrograma'] ?? '');
$codProgramaOrigen = trim($input['codProgramaOrigen'] ?? $_GET['codProgramaOrigen'] ?? '');
$fechaInicioInput = trim((string)($input['fechaInicio'] ?? $_GET['fechaInicio'] ?? ''));
$fechaFinInput = null;
if (isset($input['fechaFin']) && $input['fechaFin'] !== null && $input['fechaFin'] !== '') {
    $fechaFinInput = trim((string)$input['fechaFin']);
} elseif (!empty($_GET['fechaFin'])) {
    $fechaFinInput = trim((string)$_GET['fechaFin']);
}
$fechaFinAnteriorInput = null;
if (isset($input['fechaFinAnterior']) && $input['fechaFinAnterior'] !== null && $input['fechaFinAnterior'] !== '') {
    $fechaFinAnteriorInput = trim((string)$input['fechaFinAnterior']);
} elseif (!empty($_GET['fechaFinAnterior'])) {
    $fechaFinAnteriorInput = trim((string)$_GET['fechaFinAnterior']);
}
$fechaInicioAnteriorInput = null;
if (isset($input['fechaInicioAnterior']) && $input['fechaInicioAnterior'] !== null && $input['fechaInicioAnterior'] !== '') {
    $fechaInicioAnteriorInput = trim((string)$input['fechaInicioAnterior']);
} elseif (!empty($_GET['fechaInicioAnterior'])) {
    $fechaInicioAnteriorInput = trim((string)$_GET['fechaInicioAnterior']);
}
$soloCambioFechaFin = !empty($input['soloCambioFechaFin']) || !empty($_GET['soloCambioFechaFin']);
if ($codPrograma === '') {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Falta codPrograma', 'total' => 0]);
    exit;
}
$codParaScope = ($codProgramaOrigen !== '') ? $codProgramaOrigen : $codPrograma;
$hoy = date('Y-m-d');
$tieneZona = true;
$tieneSubzona = true;
$tieneNomGranja = true;


function extraerScope($conn, $codParaScope, $hoy, $tieneZona, $tieneSubzona, $tieneNomGranja, $soloFuturas, $numCronogramaFiltro = null) {
    $scopeByKey = [];
    if ($numCronogramaFiltro !== null && $numCronogramaFiltro > 0) {
        if ($soloFuturas) {
            $st = $conn->prepare("SELECT granja, galpon, campania, zona, subzona, nomGranja FROM san_fact_cronograma WHERE codPrograma = ? AND numCronograma = ? AND DATE(fechaEjecucion) >= ?");
            $st->bind_param("sis", $codParaScope, $numCronogramaFiltro, $hoy);
        } else {
            $st = $conn->prepare("SELECT granja, galpon, campania, zona, subzona, nomGranja FROM san_fact_cronograma WHERE codPrograma = ? AND numCronograma = ?");
            $st->bind_param("si", $codParaScope, $numCronogramaFiltro);
        }
    } elseif ($soloFuturas) {
        $st = $conn->prepare("SELECT granja, galpon, campania, zona, subzona, nomGranja FROM san_fact_cronograma WHERE codPrograma = ? AND DATE(fechaEjecucion) >= ?");
        $st->bind_param("ss", $codParaScope, $hoy);
    } else {
        $st = $conn->prepare("SELECT granja, galpon, campania, zona, subzona, nomGranja FROM san_fact_cronograma WHERE codPrograma = ?");
        $st->bind_param("s", $codParaScope);
    }
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) {
        $g = substr(trim((string)($row['granja'] ?? '')), 0, 3);
        $gp = trim((string)($row['galpon'] ?? ''));
        $c = strlen(trim((string)($row['campania'] ?? ''))) >= 3 ? substr(trim($row['campania']), -3) : str_pad(trim((string)($row['campania'] ?? '')), 3, '0', STR_PAD_LEFT);
        $key = $g . '|' . $gp;
        if (!isset($scopeByKey[$key])) {
            $scopeByKey[$key] = [
                'granja' => $g,
                'galpon' => $gp,
                'campanias' => [],
                'zona' => $tieneZona ? trim((string)($row['zona'] ?? '')) : '',
                'subzona' => $tieneSubzona ? trim((string)($row['subzona'] ?? '')) : '',
                'nomGranja' => $tieneNomGranja ? trim((string)($row['nomGranja'] ?? '')) : ''
            ];
        }
        if (!in_array($c, $scopeByKey[$key]['campanias'])) $scopeByKey[$key]['campanias'][] = $c;
    }
    $st->close();
    return $scopeByKey;
}

// Extrae scope desde san_cronograma_despliegue por numCronograma 
function extraerScopeDesdeDespliegue($conn, $codParaScope, $tieneZona, $tieneSubzona, $tieneNomGranja) {
    $scopeByKey = [];
    $stNum = $conn->prepare("SELECT DISTINCT numCronograma FROM san_fact_cronograma WHERE codPrograma = ?");
    if (!$stNum) return $scopeByKey;
    $stNum->bind_param("s", $codParaScope);
    $stNum->execute();
    $resNum = $stNum->get_result();
    $nums = [];
    while ($r = $resNum->fetch_assoc()) {
        $n = (int)($r['numCronograma'] ?? 0);
        if ($n > 0) $nums[] = $n;
    }
    $stNum->close();
    if (empty($nums)) return $scopeByKey;
    return extraerScopeDesdeDesplieguePorNums($conn, $nums, $tieneZona, $tieneSubzona, $tieneNomGranja);
}
function extraerScopeDesdeDesplieguePorNums($conn, $nums, $tieneZona, $tieneSubzona, $tieneNomGranja) {
    $scopeByKey = [];
    if (empty($nums)) return $scopeByKey;
    $ph = implode(',', array_fill(0, count($nums), '?'));
    $st = $conn->prepare("SELECT granja, campania, galpon FROM san_cronograma_despliegue WHERE numCronograma IN ($ph)");
    if (!$st) return $scopeByKey;
    $types = str_repeat('i', count($nums));
    $st->bind_param($types, ...$nums);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) {
        $g = substr(trim((string)($row['granja'] ?? '')), 0, 3);
        $gp = trim((string)($row['galpon'] ?? ''));
        $c = strlen(trim((string)($row['campania'] ?? ''))) >= 3 ? substr(trim($row['campania']), -3) : str_pad(trim((string)($row['campania'] ?? '')), 3, '0', STR_PAD_LEFT);
        $key = $g . '|' . $gp;
        if (!isset($scopeByKey[$key])) {
            $scopeByKey[$key] = [
                'granja' => $g,
                'galpon' => $gp,
                'campanias' => [],
                'zona' => '',
                'subzona' => '',
                'nomGranja' => ''
            ];
        }
        if (!in_array($c, $scopeByKey[$key]['campanias'])) $scopeByKey[$key]['campanias'][] = $c;
    }
    $st->close();
    return $scopeByKey;
}


function obtenerZonaSubzona($conn, &$scopeByKey, $tieneZona, $tieneSubzona, $tieneNomGranja) {
    if (empty($scopeByKey) || (!$tieneZona && !$tieneSubzona && !$tieneNomGranja)) return;
    $granjasUnicas = array_values(array_unique(array_filter(array_map(function ($sc) { return substr(trim((string)($sc['granja'] ?? '')), 0, 3); }, array_values($scopeByKey)), function ($g) { return $g !== ''; })));
    if (empty($granjasUnicas)) return;
    $mapGranja = [];
    $placeholders = implode(',', array_fill(0, count($granjasUnicas), '?'));
    $st = $conn->prepare("SELECT LEFT(TRIM(tcencos), 3) AS codigo, MAX(TRIM(tnomcen)) AS nombre FROM regcencosgalpones WHERE TRIM(tcencos) <> '' AND LEFT(TRIM(tcencos), 3) IN ($placeholders) GROUP BY LEFT(TRIM(tcencos), 3)");
    if ($st) {
        $types = str_repeat('s', count($granjasUnicas));
        $st->bind_param($types, ...$granjasUnicas);
        $st->execute();
        $res = $st->get_result();
        while ($row = $res->fetch_assoc()) {
            $cod = trim((string)($row['codigo'] ?? ''));
            if ($cod !== '') $mapGranja[$cod] = ['zona' => '', 'subzona' => '', 'nombre' => trim((string)($row['nombre'] ?? $cod))];
        }
        $st->close();
    }
    if ($tieneZona || $tieneSubzona) {
        $placeholders = implode(',', array_fill(0, count($granjasUnicas), '?'));
        $st = $conn->prepare("
            SELECT LEFT(TRIM(det.id_granja), 3) AS codigo,
                   MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'ZONA' THEN TRIM(det.dato) END) AS zona,
                   MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'SUBZONA' THEN TRIM(det.dato) END) AS subzona
            FROM pi_dim_detalles det
            INNER JOIN pi_dim_caracteristicas car ON car.id = det.id_caracteristica
            WHERE TRIM(det.id_granja) <> '' AND UPPER(TRIM(car.nombre)) IN ('ZONA', 'SUBZONA')
              AND LEFT(TRIM(det.id_granja), 3) IN ($placeholders)
            GROUP BY LEFT(TRIM(det.id_granja), 3)
        ");
        if ($st) {
            $types = str_repeat('s', count($granjasUnicas));
            $st->bind_param($types, ...$granjasUnicas);
            $st->execute();
            $res = $st->get_result();
            while ($row = $res->fetch_assoc()) {
                $cod = trim((string)($row['codigo'] ?? ''));
                $z = trim((string)($row['zona'] ?? ''));
                $sz = trim((string)($row['subzona'] ?? ''));
                if ($cod !== '') {
                    if (!isset($mapGranja[$cod])) $mapGranja[$cod] = ['zona' => '', 'subzona' => '', 'nombre' => $cod];
                    if ($z !== '') $mapGranja[$cod]['zona'] = $z;
                    if ($sz !== '') $mapGranja[$cod]['subzona'] = $sz;
                }
            }
            $st->close();
        }
    }
    foreach ($scopeByKey as $key => &$sc) {
        $g = substr(trim((string)($sc['granja'] ?? '')), 0, 3);
        if ($g === '' || !isset($mapGranja[$g])) continue;
        $info = $mapGranja[$g];
        if ($tieneZona && ($sc['zona'] ?? '') === '' && isset($info['zona']) && $info['zona'] !== '') $sc['zona'] = $info['zona'];
        if ($tieneSubzona && ($sc['subzona'] ?? '') === '' && isset($info['subzona']) && $info['subzona'] !== '') $sc['subzona'] = $info['subzona'];
        if ($tieneNomGranja && ($sc['nomGranja'] ?? '') === '' && isset($info['nombre']) && $info['nombre'] !== '') $sc['nomGranja'] = $info['nombre'];
    }
    unset($sc);
}


$tieneNumCronograma = true;
$tieneDespliegue = true;
$numsCronograma = [];
if ($tieneNumCronograma) {
    $stNums = $conn->prepare("SELECT DISTINCT numCronograma FROM san_fact_cronograma WHERE codPrograma = ?");
    if ($stNums) {
        $stNums->bind_param("s", $codPrograma);
        $stNums->execute();
        $resNums = $stNums->get_result();
        while ($r = $resNums->fetch_assoc()) {
            $n = (int)($r['numCronograma'] ?? 0);
            if ($n > 0) $numsCronograma[] = $n;
        }
        $stNums->close();
    }
}
if (empty($numsCronograma)) {
    $numsCronograma = [0];
}

// Año de referencia para cargapollo_proyeccion
$anioAsignaciones = 0;
$stAnio = $conn->prepare("SELECT fechaEjecucion FROM san_fact_cronograma WHERE codPrograma = ? ORDER BY fechaEjecucion ASC LIMIT 1");
if ($stAnio) {
    $stAnio->bind_param("s", $codParaScope);
    $stAnio->execute();
    $rowAnio = $stAnio->get_result()->fetch_assoc();
    $stAnio->close();
    if ($rowAnio && !empty($rowAnio['fechaEjecucion']) && strlen($rowAnio['fechaEjecucion']) >= 4) {
        $anioAsignaciones = (int)substr($rowAnio['fechaEjecucion'], 0, 4);
    }
}

// Datos del programa y fechas efectivas (incl. programa especial)
$chkEsEspecial = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'esEspecial'");
$tieneEspecial = $chkEsEspecial && $chkEsEspecial->num_rows > 0;
$sqlCab = "SELECT nombre, fechaInicio, fechaFin" . ($tieneEspecial ? ", esEspecial, modoEspecial" : "") . " FROM san_fact_programa_cab WHERE codigo = ? LIMIT 1";
$stCab = $conn->prepare($sqlCab);
$stCab->bind_param("s", $codPrograma);
$stCab->execute();
$cab = $stCab->get_result()->fetch_assoc();
$stCab->close();
if (!$cab) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Programa no encontrado', 'total' => 0]);
    exit;
}
$esEspecial = $tieneEspecial ? (int)($cab['esEspecial'] ?? 0) : 0;
$modoEspecial = $tieneEspecial ? trim((string)($cab['modoEspecial'] ?? '')) : '';
$intervaloMeses = 1;
$diaDelMes = 15;
$fechas_manuales_json = null;

// fechas, intervaloMeses, diaDelMes solo en DET
$chkFechasDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'fechas'");
$chkIntervaloDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'intervaloMeses'");
$chkDiaDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'diaDelMes'");
if (($chkFechasDet && $chkFechasDet->num_rows > 0) || ($chkIntervaloDet && $chkIntervaloDet->num_rows > 0 && $chkDiaDet && $chkDiaDet->num_rows > 0)) {
    $camposDetEsp = ($chkFechasDet && $chkFechasDet->num_rows > 0) ? "fechas" : "";
    if ($chkIntervaloDet && $chkIntervaloDet->num_rows > 0 && $chkDiaDet && $chkDiaDet->num_rows > 0) $camposDetEsp .= ($camposDetEsp ? ", " : "") . "intervaloMeses, diaDelMes";
    $stDetEsp = $conn->prepare("SELECT " . $camposDetEsp . " FROM san_fact_programa_det WHERE codPrograma = ? LIMIT 1");
    if ($stDetEsp) {
        $stDetEsp->bind_param("s", $codPrograma);
        $stDetEsp->execute();
        $rowDetEsp = $stDetEsp->get_result()->fetch_assoc();
        $stDetEsp->close();
        if ($rowDetEsp) {
            if (isset($rowDetEsp['fechas']) && $rowDetEsp['fechas'] !== null && $rowDetEsp['fechas'] !== '') $fechas_manuales_json = $rowDetEsp['fechas'];
            if (isset($rowDetEsp['intervaloMeses']) && $rowDetEsp['intervaloMeses'] !== null) $intervaloMeses = max(1, min(12, (int)$rowDetEsp['intervaloMeses']));
            if (isset($rowDetEsp['diaDelMes']) && $rowDetEsp['diaDelMes'] !== null) $diaDelMes = max(1, min(31, (int)$rowDetEsp['diaDelMes']));
        }
    }
}

$chkFi = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'fechaInicio'");
$tieneFechasCab = $chkFi && $chkFi->num_rows > 0;
$fechaInicioPrograma = null;
$fechaFinPrograma = null;
$esCrearNuevo = ($codProgramaOrigen !== '' && $codProgramaOrigen !== $codPrograma);

if ($esCrearNuevo) {
    $fechaInicioPrograma = $hoy;
    if ($fechaFinInput !== null && $fechaFinInput !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFinInput) && $fechaFinInput >= $hoy) {
        $fechaFinPrograma = $fechaFinInput;
    } elseif ($tieneFechasCab && $cab) {
        $ff = trim((string)($cab['fechaFin'] ?? ''));
        if ($ff !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ff) && $ff >= $hoy) {
            $fechaFinPrograma = $ff;
        } else {
            $fechaFinPrograma = date('Y') . '-12-31';
        }
    } else {
        $fechaFinPrograma = date('Y') . '-12-31';
    }
} else {
    if ($fechaInicioInput !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicioInput)) {
        $fechaInicioPrograma = $fechaInicioInput;
    }
    if ($fechaFinInput !== null && $fechaFinInput !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFinInput)) {
        $fechaFinPrograma = $fechaFinInput;
    } elseif (($fechaFinInput === null || $fechaFinInput === '') && ($fechaInicioPrograma !== null || $anioAsignaciones > 0)) {
        $anioRef = $anioAsignaciones > 0 ? $anioAsignaciones : ($fechaInicioPrograma && strlen($fechaInicioPrograma) >= 4 ? (int)substr($fechaInicioPrograma, 0, 4) : 0);
        if ($anioRef > 0) $fechaFinPrograma = $anioRef . '-12-31';
    }
    if ($fechaInicioPrograma === null && $tieneFechasCab && $cab) {
        $fi = trim((string)($cab['fechaInicio'] ?? ''));
        if ($fi !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fi)) $fechaInicioPrograma = $fi;
    }
    if ($fechaFinPrograma === null && $tieneFechasCab && $cab) {
        $ff = trim((string)($cab['fechaFin'] ?? ''));
        if ($ff !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ff)) {
            $fechaFinPrograma = $ff;
        } elseif ($fechaInicioPrograma !== null || $anioAsignaciones > 0) {
            $anioRef = $anioAsignaciones > 0 ? $anioAsignaciones : ($fechaInicioPrograma && strlen($fechaInicioPrograma) >= 4 ? (int)substr($fechaInicioPrograma, 0, 4) : 0);
            if ($anioRef > 0) $fechaFinPrograma = $anioRef . '-12-31';
        }
    }
}
// Año de referencia y rango efectivo para recálculo
$anio = (int)date('Y');
if ($fechaInicioPrograma && strlen($fechaInicioPrograma) >= 4) {
    $anio = (int)substr($fechaInicioPrograma, 0, 4);
} elseif ($anioAsignaciones > 0) {
    $anio = $anioAsignaciones;
}
$fechaInicioEfectiva = $fechaInicioPrograma;
if ($fechaInicioEfectiva !== null && $anio > 0 && $fechaInicioEfectiva < $anio . '-01-01') {
    $fechaInicioEfectiva = $anio . '-01-01';
}
if ($fechaInicioEfectiva === null && $anio > 0) {
    $fechaInicioEfectiva = $anio . '-01-01';
}
$fechaInicioRecalc = $hoy;
if ($fechaInicioEfectiva !== null && $fechaInicioEfectiva > $hoy) {
    $fechaInicioRecalc = $fechaInicioEfectiva;
}
$fechaFinRecalc = $fechaFinPrograma;
if ($fechaFinRecalc === null) {
    $anioRef = $anioAsignaciones > 0 ? $anioAsignaciones : ((int)date('Y'));
    $fechaFinRecalc = $anioRef . '-12-31';
}


// Modo solo cambio fecha fin: reducir o ampliar rango
$fechaFinNueva = $fechaFinPrograma ?? $fechaFinRecalc;
$modoSoloFechaFin = $soloCambioFechaFin
    && ($fechaFinAnteriorInput !== null && $fechaFinAnteriorInput !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFinAnteriorInput))
    && ($fechaFinNueva !== null && $fechaFinNueva !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFinNueva));

$modoAumentoFechaFin = false;
// Reducir fecha fin: eliminar asignaciones posteriores
if ($modoSoloFechaFin && $fechaFinNueva < $fechaFinAnteriorInput) {
    $delStmt = $conn->prepare("DELETE FROM san_fact_cronograma WHERE codPrograma = ? AND DATE(fechaEjecucion) > ?");
    if ($delStmt) {
        $delStmt->bind_param("ss", $codPrograma, $fechaFinNueva);
        $delStmt->execute();
        $delStmt->close();
    }
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Asignaciones posteriores a la nueva fecha fin eliminadas.', 'total' => 0]);
    exit;
}
// Ampliar fecha fin: insertar desde antiguaFin+1 hasta nuevaFin
if ($modoSoloFechaFin && $fechaFinNueva > $fechaFinAnteriorInput) {
    $modoAumentoFechaFin = true;
    $fechaInicioRecalc = date('Y-m-d', strtotime($fechaFinAnteriorInput . ' +1 day'));
    $fechaFinRecalc = $fechaFinNueva;
}

// Modo solo cambio fecha inicio
$fechaInicioNueva = $fechaInicioPrograma ?? $fechaInicioRecalc;
$modoSoloFechaInicio = $soloCambioFechaFin
    && ($fechaInicioAnteriorInput !== null && $fechaInicioAnteriorInput !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicioAnteriorInput))
    && ($fechaInicioNueva !== null && $fechaInicioNueva !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicioNueva));

// Nueva fecha inicio posterior: eliminar rango [antiguaInicio, nuevaInicio-1]
if ($modoSoloFechaInicio && $fechaInicioNueva > $fechaInicioAnteriorInput) {
    $fechaFinRango = date('Y-m-d', strtotime($fechaInicioNueva . ' -1 day'));
    $delStmt = $conn->prepare("DELETE FROM san_fact_cronograma WHERE codPrograma = ? AND DATE(fechaEjecucion) >= ? AND DATE(fechaEjecucion) <= ?");
    if ($delStmt) {
        $delStmt->bind_param("sss", $codPrograma, $fechaInicioAnteriorInput, $fechaFinRango);
        $delStmt->execute();
        $delStmt->close();
    }
    
    $antiguaFin = $fechaFinAnteriorInput ?? '';
    if ($antiguaFin !== '' && $fechaInicioNueva > $antiguaFin) {
        $modoAumentoFechaFin = true;
        $fechaInicioRecalc = $fechaInicioNueva;
        $fechaFinRecalc = $fechaFinNueva;
     
    } else {
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Asignaciones del rango eliminadas (nueva fecha inicio posterior).', 'total' => 0]);
        exit;
    }
}
// Nueva fecha inicio anterior: ampliar rango hacia atrás
if ($modoSoloFechaInicio && $fechaInicioNueva < $fechaInicioAnteriorInput) {
    if ($modoAumentoFechaFin) {    
        $modoAumentoFechaFin = false;
        $fechaInicioRecalc = $fechaInicioNueva;
        $fechaFinRecalc = $fechaFinNueva;
    } else {
        $fechaInicioRecalc = $fechaInicioNueva;
        $fechaFinRecalc = date('Y-m-d', strtotime($fechaInicioAnteriorInput . ' -1 day'));
    }
}

// Edades del detalle del programa (programa especial puede tener edad=null)
$nomPrograma = trim((string)($cab['nombre'] ?? ''));
$zonaDefault = '';
$stDet = $conn->prepare("SELECT DISTINCT edad FROM san_fact_programa_det WHERE codPrograma = ? AND edad IS NOT NULL AND edad != 0 ORDER BY edad ASC");
$stDet->bind_param("s", $codPrograma);
$stDet->execute();
$resDet = $stDet->get_result();
$edadesUnicas = [];
while ($r = $resDet->fetch_assoc()) $edadesUnicas[] = (int)$r['edad'];
$stDet->close();
$edadesNegativas = array_values(array_filter($edadesUnicas, function ($e) { return $e < 0; }));
$edadesParaTabla = array_values(array_filter($edadesUnicas, function ($e) { return $e > 0; }));
if (!empty($edadesNegativas) && !in_array(1, $edadesParaTabla)) {
    $edadesParaTabla[] = 1;
    sort($edadesParaTabla);
}
if (empty($edadesParaTabla)) $edadesParaTabla = [1];
// Programa especial sin edades (edad=null en det): usar edad 1 como placeholder
if (empty($edadesUnicas) && $esEspecial === 1) {
    $edadesParaTabla = [1];
    $edadesNegativas = [];
}
// Solo fallar si no es especial y no hay edades
if (empty($edadesUnicas) && $esEspecial !== 1) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Programa sin edades en detalle', 'total' => 0]);
    exit;
}

// Filtra pares (edad,fechaEjecucion) dentro del rango [fechaInicio, fechaFin] 
function filtrarParesPorRangoRecal($pares, $fechaInicio, $fechaFin) {
    if ($fechaInicio === null || $fechaFin === null) return $pares;
    return array_values(array_filter($pares, function ($p) use ($fechaInicio, $fechaFin) {
        $fe = isset($p['fechaEjecucion']) ? trim((string)$p['fechaEjecucion']) : '';
        if ($fe === '' || strlen($fe) < 10) return false;
        return substr($fe, 0, 10) >= $fechaInicio && substr($fe, 0, 10) <= $fechaFin;
    }));
}

if ($anio > 0 && $fechaFinRecalc !== null && $fechaFinRecalc > $anio . '-12-31') {
    $fechaFinRecalc = $anio . '-12-31';
}

// Programa especial: ventana [1 ene | inicio programa, 31 dic | fin programa] del año de asignación
$fechasEspecial = [];
if ($esEspecial === 1 && $anio > 0) {
    $desdeEspecial = $anio . '-01-01';
    $hastaEspecial = $anio . '-12-31';
    if ($fechaInicioPrograma !== null && $fechaInicioPrograma !== '' && $fechaInicioPrograma > $desdeEspecial) {
        $desdeEspecial = $fechaInicioPrograma;
    }
    if ($fechaFinPrograma !== null && $fechaFinPrograma !== '' && $fechaFinPrograma < $hastaEspecial) {
        $hastaEspecial = $fechaFinPrograma;
    }
    if ($desdeEspecial > $hastaEspecial) {
        $fechasEspecial = [];
    } elseif (strtoupper($modoEspecial) === 'MANUAL' && $fechas_manuales_json !== null && $fechas_manuales_json !== '') {
        $dec = json_decode($fechas_manuales_json, true);
        $arr = is_array($dec) ? $dec : [];
        foreach ($arr as $f) {
            $d = is_string($f) ? substr(trim($f), 0, 10) : (isset($f['fecha']) ? substr(trim($f['fecha']), 0, 10) : '');
            if ($d !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && $d >= $desdeEspecial && $d <= $hastaEspecial) {
                $fechasEspecial[] = $d;
            }
        }
        $fechasEspecial = array_values(array_unique($fechasEspecial));
        sort($fechasEspecial);
    } elseif (strtoupper($modoEspecial) === 'PERIODICIDAD') {
        $mes = (int)substr($desdeEspecial, 5, 2);
        $anioInt = (int)substr($desdeEspecial, 0, 4);
        $dia = max(1, min(31, $diaDelMes));
        $fechaActual = sprintf('%04d-%02d-%02d', $anioInt, $mes, min($dia, (int)date('t', mktime(0, 0, 0, $mes, 1, $anioInt))));
        if ($fechaActual < $desdeEspecial) {
            $mes += $intervaloMeses;
            while ($mes > 12) { $mes -= 12; $anioInt++; }
            $fechaActual = sprintf('%04d-%02d-%02d', $anioInt, $mes, min($dia, (int)date('t', mktime(0, 0, 0, $mes, 1, $anioInt))));
        }
        while ($fechaActual <= $hastaEspecial) {
            $fechasEspecial[] = $fechaActual;
            $mes += $intervaloMeses;
            while ($mes > 12) { $mes -= 12; $anioInt++; }
            $ultimoDia = (int)date('t', mktime(0, 0, 0, $mes, 1, $anioInt));
            $fechaActual = sprintf('%04d-%02d-%02d', $anioInt, $mes, min($dia, $ultimoDia));
        }
    }
}

// Preparar INSERT y procesar por cada numCronograma
$placeholders = implode(',', array_fill(0, count($edadesParaTabla), '?'));
$total = 0;
$usuario = $_SESSION['usuario'] ?? 'WEB';
$tieneNomGranjaCol = true;
$tieneEdad = true;
$chkTolerancia = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'tolerancia'");
$chkTolDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'tolerancia'");
$tieneTolerancia = $chkTolerancia && $chkTolerancia->num_rows > 0;
$tieneTolDet = $chkTolDet && $chkTolDet->num_rows > 0;
$toleranciaPorEdad = [];
if ($tieneTolerancia && $tieneTolDet) {
    $stTol = $conn->prepare("SELECT edad, COALESCE(NULLIF(tolerancia, 0), 1) AS tol FROM san_fact_programa_det WHERE codPrograma = ?");
    if ($stTol) {
        $stTol->bind_param("s", $codPrograma);
        $stTol->execute();
        $resTol = $stTol->get_result();
        while ($row = $resTol->fetch_assoc()) {
            $e = $row['edad'] !== null && $row['edad'] !== '' ? (int)$row['edad'] : 0;
            $toleranciaPorEdad[$e] = max(1, (int)($row['tol'] ?? 1));
        }
        $stTol->close();
    }
}
$cols = "granja, campania, galpon, codPrograma, nomPrograma, fechaCarga, fechaEjecucion, usuarioRegistro, zona, subzona";
$placeholdersInsert = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
$types = "ssssssssss";
if ($tieneNumCronograma) { $cols .= ", numCronograma"; $placeholdersInsert .= ", ?"; $types .= "i"; }
if ($tieneNomGranjaCol) { $cols .= ", nomGranja"; $placeholdersInsert .= ", ?"; $types .= "s"; }
if ($tieneEdad) { $cols .= ", edad"; $placeholdersInsert .= ", ?"; $types .= "i"; }
if ($tieneTolerancia) { $cols .= ", tolerancia"; $placeholdersInsert .= ", ?"; $types .= "i"; }
$stmt = $conn->prepare("INSERT INTO san_fact_cronograma ($cols) VALUES ($placeholdersInsert)");
if (!$stmt) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error prepare insert', 'total' => 0]);
    exit;
}
foreach ($numsCronograma as $numCronograma) {
    $scopeByKey = [];
    if ($numCronograma > 0 && $chkDespliegue && $chkDespliegue->num_rows > 0) {
        $scopeByKey = extraerScopeDesdeDesplieguePorNums($conn, [$numCronograma], $tieneZona, $tieneSubzona, $tieneNomGranja);
    }
    if (empty($scopeByKey)) {
        $scopeByKey = extraerScope($conn, $codParaScope, $hoy, $tieneZona, $tieneSubzona, $tieneNomGranja, true, ($numCronograma > 0 ? $numCronograma : null));
    }
    if (empty($scopeByKey)) {
        $scopeByKey = extraerScope($conn, $codParaScope, $hoy, $tieneZona, $tieneSubzona, $tieneNomGranja, false, ($numCronograma > 0 ? $numCronograma : null));
    }
    obtenerZonaSubzona($conn, $scopeByKey, $tieneZona, $tieneSubzona, $tieneNomGranja);
    if (empty($scopeByKey)) continue;

    // Borrar asignaciones futuras (salvo en modo ampliar fecha fin)
    if (!$modoAumentoFechaFin) {
        $del = $conn->prepare("DELETE FROM san_fact_cronograma WHERE codPrograma = ? AND numCronograma = ? AND DATE(fechaEjecucion) >= ?");
        $del->bind_param("sis", $codPrograma, $numCronograma, $hoy);
        $del->execute();
        $del->close();
    }

    $allItems = [];
    foreach ($scopeByKey as $sc) {
    $granja = $sc['granja'];
    $galpon = $sc['galpon'];
    $campanias = $sc['campanias'];
    // Programa especial: si no hay galpon/campañas usar campaña placeholder para granja sin zona/subzona
    if ($esEspecial === 1 && empty($campanias)) {
        $campanias = ['000'];
    }
    if (empty($campanias)) continue;
    $items = [];
    if ($esEspecial === 1 && !empty($fechasEspecial)) {
        $fechaMaxRecalc = $fechaFinRecalc ?? $anio . '-12-31';
        foreach ($campanias as $campaniaVal) {
            $campania = str_pad($campaniaVal, 3, '0', STR_PAD_LEFT);
            $paresEstaCampania = [];
            foreach ($edadesParaTabla as $edad) {
                foreach ($fechasEspecial as $fechaEjec) {
                    if ($fechaEjec >= $fechaInicioRecalc && $fechaEjec <= $fechaMaxRecalc) {
                        $paresEstaCampania[] = ['edad' => $edad, 'fechaCarga' => $fechaEjec, 'fechaEjecucion' => $fechaEjec, 'campania' => $campania];
                    }
                }
            }
            foreach ($fechasEspecial as $fechaEjec) {
                foreach ($edadesNegativas as $edadNeg) {
                    $fechaEjecNeg = date('Y-m-d', strtotime($fechaEjec . ' ' . $edadNeg . ' days'));
                    if ($fechaEjecNeg >= $fechaInicioRecalc && $fechaEjecNeg <= $fechaMaxRecalc) {
                        $paresEstaCampania[] = ['edad' => $edadNeg, 'fechaCarga' => $fechaEjec, 'fechaEjecucion' => $fechaEjecNeg, 'campania' => $campania];
                    }
                }
            }
            if (!empty($paresEstaCampania)) {
                $items[] = ['granja' => $granja, 'campania' => $campania, 'galpon' => $galpon, 'fechas' => $paresEstaCampania];
            }
        }
    } else {
    foreach ($campanias as $campaniaVal) {
        $campania = str_pad($campaniaVal, 3, '0', STR_PAD_LEFT);
        $tcencos = $granja . $campania;
        $sqlFechas = "SELECT fecha AS fecha_ejecucion, edad, tcencos, tcodint, DATE_ADD(fecha, INTERVAL -(edad) + 1 DAY) AS fecha_carga FROM cargapollo_proyeccion WHERE tcencos = ? AND tcodint = ? AND edad IN ($placeholders) AND YEAR(fecha) = ? AND fecha >= ? AND fecha <= ? GROUP BY fecha, edad, tcencos, tcodint ORDER BY fecha, edad";
        $fechaMin = $fechaInicioRecalc;
        $fechaMax = $fechaFinRecalc ?? ($anio . '-12-31');
        $paramsFechas = array_merge([$tcencos, $galpon], $edadesParaTabla, [$anio, $fechaMin, $fechaMax]);
        $typesFechas = 'ss' . str_repeat('i', count($edadesParaTabla)) . 'iss';
        $st2 = $conn->prepare($sqlFechas);
        $st2->bind_param($typesFechas, ...$paramsFechas);
        $st2->execute();
        $resFechas = $st2->get_result();
        $paresEstaCampania = [];
        $filasEdad1 = [];
        while ($r = $resFechas->fetch_assoc()) {
            $fechaEjec = $r['fecha_ejecucion'] ?? $r['fecha'] ?? null;
            $fechaCarga = $r['fecha_carga'] ?? null;
            $edad = (int)($r['edad'] ?? 0);
            if (!$fechaEjec) continue;
            if ($edad >= 1) {
                $paresEstaCampania[] = ['edad' => $edad, 'fechaCarga' => $fechaCarga, 'fechaEjecucion' => $fechaEjec, 'campania' => $campania];
            }
            if ($edad === 1 && !empty($edadesNegativas)) {
                $filasEdad1[] = ['fechaCarga' => $fechaCarga, 'fechaEjecucion' => $fechaEjec, 'campania' => $campania];
            }
        }
        foreach ($filasEdad1 as $ref) {
            foreach ($edadesNegativas as $edadNeg) {
                $fechaEjecNeg = date('Y-m-d', strtotime($ref['fechaEjecucion'] . ' ' . $edadNeg . ' days'));
                $paresEstaCampania[] = ['edad' => $edadNeg, 'fechaCarga' => $ref['fechaCarga'], 'fechaEjecucion' => $fechaEjecNeg, 'campania' => $campania];
            }
        }
        $st2->close();
        $paresEstaCampania = filtrarParesPorRangoRecal($paresEstaCampania, $fechaInicioRecalc, $fechaFinRecalc);
        if (!empty($paresEstaCampania)) {
            $items[] = ['granja' => $granja, 'campania' => $campania, 'galpon' => $galpon, 'fechas' => $paresEstaCampania];
        }
    }
    }
    $zonaItem = $sc['zona'] !== '' ? $sc['zona'] : $zonaDefault;
    $subzonaVal = $sc['subzona'];
    $nomGranja = $sc['nomGranja'];
    foreach ($items as $it) {
        $allItems[] = array_merge($it, ['zona' => $zonaItem, 'subzona' => $subzonaVal, 'nomGranja' => $nomGranja]);
    }
}

    // Insertar asignaciones en san_fact_cronograma
    foreach ($allItems as $it) {
    $granja = substr(trim((string)($it['granja'] ?? '')), 0, 3);
    $galpon = trim((string)($it['galpon'] ?? ''));
    $zonaItem = trim((string)($it['zona'] ?? $zonaDefault));
    $subzonaItem = $it['subzona'] ?? '';
    $subzonaVal = is_string($subzonaItem) ? trim($subzonaItem) : (is_numeric($subzonaItem) ? (string)$subzonaItem : '');
    $nomGranja = trim((string)($it['nomGranja'] ?? ''));
    $fechas = $it['fechas'] ?? [];
    foreach ($fechas as $f) {
        $campaniaRaw = trim((string)($f['campania'] ?? $it['campania'] ?? ''));
        $campania = strlen($campaniaRaw) >= 3 ? substr($campaniaRaw, -3) : str_pad($campaniaRaw, 3, '0', STR_PAD_LEFT);
        $edadVal = isset($f['edad']) ? (int)$f['edad'] : (isset($it['edad']) ? (int)$it['edad'] : 0);
        if ($edadVal > 999) $edadVal = 999;
        $fechaCarga = isset($f['fechaCarga']) ? (is_string($f['fechaCarga']) ? $f['fechaCarga'] : date('Y-m-d', strtotime($f['fechaCarga']))) : '';
        $fechaEjecucion = isset($f['fechaEjecucion']) ? (is_string($f['fechaEjecucion']) ? $f['fechaEjecucion'] : date('Y-m-d', strtotime($f['fechaEjecucion']))) : '';
        if ($fechaCarga === '') $fechaCarga = $fechaEjecucion;
        $bindVals = [$granja, $campania, $galpon, $codPrograma, $nomPrograma, $fechaCarga, $fechaEjecucion, $usuario, $zonaItem, $subzonaVal];
        if ($tieneNumCronograma) $bindVals[] = $numCronograma;
        if ($tieneNomGranjaCol) $bindVals[] = $nomGranja;
        if ($tieneEdad) $bindVals[] = $edadVal;
        if ($tieneTolerancia) $bindVals[] = isset($toleranciaPorEdad[$edadVal]) ? $toleranciaPorEdad[$edadVal] : (empty($toleranciaPorEdad) ? 1 : max(array_values($toleranciaPorEdad)));
        $params = array_merge([$types], $bindVals);
        $refs = [];
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        call_user_func_array([$stmt, 'bind_param'], $refs);
        if (!$stmt->execute()) {
            $stmt->close();
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Error al insertar: ' . $stmt->error, 'total' => $total]);
            exit;
        }
        $total++;
    }
}
}
$stmt->close();


$respuesta = ['success' => true, 'message' => 'Recálculo realizado', 'total' => (int)$total];
if ($debug) {
    $generado = [];
    foreach ($allItems as $it) {
        $fechasResum = [];
        foreach ($it['fechas'] ?? [] as $f) {
            $fechasResum[] = ['edad' => $f['edad'] ?? null, 'fechaEjecucion' => $f['fechaEjecucion'] ?? null, 'fechaCarga' => $f['fechaCarga'] ?? null];
        }
        $generado[] = ['granja' => $it['granja'] ?? '', 'galpon' => $it['galpon'] ?? '', 'campania' => $it['campania'] ?? '', 'cantidadFechas' => count($it['fechas'] ?? []), 'fechas' => $fechasResum];
    }
    $scopeResum = [];
    foreach ($scopeByKey as $k => $v) {
        $scopeResum[] = ['key' => $k, 'granja' => $v['granja'] ?? '', 'galpon' => $v['galpon'] ?? '', 'campanias' => $v['campanias'] ?? []];
    }
    $respuesta['debug'] = [
        'input' => ['codPrograma' => $codPrograma, 'codProgramaOrigen' => $codProgramaOrigen, 'fechaInicio' => $fechaInicioInput, 'fechaFin' => $fechaFinInput, 'fechaFinAnterior' => $fechaFinAnteriorInput ?? null, 'fechaInicioAnterior' => $fechaInicioAnteriorInput ?? null, 'soloCambioFechaFin' => $soloCambioFechaFin ?? false, 'esCrearNuevo' => $esCrearNuevo ?? false],
        'modoSoloFechaFin' => $modoSoloFechaFin ?? false,
        'modoSoloFechaInicio' => $modoSoloFechaInicio ?? false,
        'modoAumentoFechaFin' => $modoAumentoFechaFin ?? false,
        'procesado' => [
            'codParaScope' => $codParaScope,
            'fechaInicioPrograma' => $fechaInicioPrograma,
            'fechaFinPrograma' => $fechaFinPrograma,
            'anio' => $anio ?? null,
            'edadesUnicas' => $edadesUnicas ?? [],
            'scopeCount' => count($scopeByKey),
            'scope' => $scopeResum,
            'allItemsCount' => count($allItems),
            'generado' => $generado,
            'fuenteScope' => ($tieneDespliegue && !empty($scopeByKey)) ? 'san_cronograma_despliegue' : 'asignaciones'
        ]
    ];
}
$conn->close();
echo json_encode($respuesta);
