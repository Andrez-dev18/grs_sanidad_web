<?php
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
$codPrograma = trim($input['codPrograma'] ?? $_GET['codPrograma'] ?? '');
$codProgramaOrigen = trim($input['codProgramaOrigen'] ?? $_GET['codProgramaOrigen'] ?? '');
$fechaInicioInput = trim((string)($input['fechaInicio'] ?? $_GET['fechaInicio'] ?? ''));
$fechaFinInput = null;
if (isset($input['fechaFin']) && $input['fechaFin'] !== null && $input['fechaFin'] !== '') {
    $fechaFinInput = trim((string)$input['fechaFin']);
} elseif (!empty($_GET['fechaFin'])) {
    $fechaFinInput = trim((string)$_GET['fechaFin']);
}
if ($codPrograma === '') {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Falta codPrograma', 'total' => 0]);
    exit;
}
// Para crear nuevo programa: scope se obtiene del programa original (codProgramaOrigen); las inserciones usan codPrograma (nuevo)
$codParaScope = ($codProgramaOrigen !== '') ? $codProgramaOrigen : $codPrograma;
$chkCrono = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
if (!$chkCrono || $chkCrono->num_rows === 0) {
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Sin tabla cronograma', 'total' => 0]);
    exit;
}
$chkFec = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fechaEjecucion'");
if (!$chkFec || $chkFec->num_rows === 0) {
    $conn->close();
    echo json_encode(['success' => true, 'total' => 0]);
    exit;
}
$hoy = date('Y-m-d');
$tieneZona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'zona'")->num_rows > 0;
$tieneSubzona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'subzona'")->num_rows > 0;
$tieneNomGranja = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'")->num_rows > 0;

function extraerScope($conn, $codParaScope, $hoy, $tieneZona, $tieneSubzona, $tieneNomGranja, $soloFuturas) {
    $scopeByKey = [];
    if ($soloFuturas) {
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

function extraerScopeDesdeDespliegue($conn, $codParaScope, $tieneZona, $tieneSubzona, $tieneNomGranja) {
    $scopeByKey = [];
    $chkNum = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
    if (!$chkNum || $chkNum->num_rows === 0) return $scopeByKey;
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

/**
 * Enriquece el scope con zona, subzona y nomGranja cuando están vacíos.
 * Usa pi_dim_detalles, regcencosgalpones y pi_dim_caracteristicas (misma lógica que get_granjas.php).
 */
function enriquecerScopeConZonaSubzonaNomGranja($conn, &$scopeByKey, $tieneZona, $tieneSubzona, $tieneNomGranja) {
    if (empty($scopeByKey) || (!$tieneZona && !$tieneSubzona && !$tieneNomGranja)) return;
    $granjasUnicas = array_values(array_unique(array_filter(array_map(function ($sc) { return substr(trim((string)($sc['granja'] ?? '')), 0, 3); }, array_values($scopeByKey)), function ($g) { return $g !== ''; })));
    if (empty($granjasUnicas)) return;

    $chkPi = @$conn->query("SHOW TABLES LIKE 'pi_dim_detalles'");
    $chkReg = @$conn->query("SHOW TABLES LIKE 'regcencosgalpones'");
    $chkCar = @$conn->query("SHOW TABLES LIKE 'pi_dim_caracteristicas'");
    if ((!$chkPi || $chkPi->num_rows === 0) && (!$chkReg || $chkReg->num_rows === 0)) return;

    $mapGranja = [];
    if ($chkReg && $chkReg->num_rows > 0) {
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
    }
    if ($chkPi && $chkPi->num_rows > 0 && $chkCar && $chkCar->num_rows > 0 && ($tieneZona || $tieneSubzona)) {
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

// Prioridad: 1) scope desde san_cronograma_despliegue (persistente), 2) desde asignaciones
$chkDespliegue = @$conn->query("SHOW TABLES LIKE 'san_cronograma_despliegue'");
if ($chkDespliegue && $chkDespliegue->num_rows > 0) {
    $scopeByKey = extraerScopeDesdeDespliegue($conn, $codParaScope, $tieneZona, $tieneSubzona, $tieneNomGranja);
}
if (empty($scopeByKey)) {
    $scopeByKey = extraerScope($conn, $codParaScope, $hoy, $tieneZona, $tieneSubzona, $tieneNomGranja, true);
}
if (empty($scopeByKey)) {
    $scopeByKey = extraerScope($conn, $codParaScope, $hoy, $tieneZona, $tieneSubzona, $tieneNomGranja, false);
}
// Enriquecer scope con zona, subzona, nomGranja cuando vienen vacíos (p. ej. desde san_cronograma_despliegue)
enriquecerScopeConZonaSubzonaNomGranja($conn, $scopeByKey, $tieneZona, $tieneSubzona, $tieneNomGranja);

// Año de referencia para cargapollo_proyeccion: priorizar el año del nuevo periodo (fechaInicio del programa)
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

$stCab = $conn->prepare("SELECT nombre, fechaInicio, fechaFin FROM san_fact_programa_cab WHERE codigo = ? LIMIT 1");
$stCab->bind_param("s", $codPrograma);
$stCab->execute();
$cab = $stCab->get_result()->fetch_assoc();
$stCab->close();
if (!$cab) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Programa no encontrado', 'total' => 0]);
    exit;
}
$chkFi = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'fechaInicio'");
$tieneFechasCab = $chkFi && $chkFi->num_rows > 0;
$fechaInicioPrograma = null;
$fechaFinPrograma = null;
$esCrearNuevo = ($codProgramaOrigen !== '' && $codProgramaOrigen !== $codPrograma);

if ($esCrearNuevo) {
    // Crear nuevo programa: las asignaciones del nuevo programa empiezan desde hoy (las pasadas quedan en el programa original)
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
    // Recálculo normal: priorizar fechas enviadas en el request; si no, leer de la cab
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
$nomPrograma = trim((string)($cab['nombre'] ?? ''));
$zonaDefault = '';
$stDet = $conn->prepare("SELECT DISTINCT edad FROM san_fact_programa_det WHERE codPrograma = ? AND edad IS NOT NULL AND edad != 0 ORDER BY edad ASC");
$stDet->bind_param("s", $codPrograma);
$stDet->execute();
$resDet = $stDet->get_result();
$edadesUnicas = [];
while ($r = $resDet->fetch_assoc()) $edadesUnicas[] = (int)$r['edad'];
$stDet->close();
if (empty($edadesUnicas)) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Programa sin edades en detalle', 'total' => 0]);
    exit;
}
$edadesNegativas = array_values(array_filter($edadesUnicas, function ($e) { return $e < 0; }));
$edadesParaTabla = array_values(array_filter($edadesUnicas, function ($e) { return $e > 0; }));
if (!empty($edadesNegativas) && !in_array(1, $edadesParaTabla)) {
    $edadesParaTabla[] = 1;
    sort($edadesParaTabla);
}
if (empty($edadesParaTabla)) $edadesParaTabla = [1];
function filtrarParesPorRangoRecal($pares, $fechaInicio, $fechaFin) {
    if ($fechaInicio === null || $fechaFin === null) return $pares;
    return array_values(array_filter($pares, function ($p) use ($fechaInicio, $fechaFin) {
        $fe = isset($p['fechaEjecucion']) ? trim((string)$p['fechaEjecucion']) : '';
        if ($fe === '' || strlen($fe) < 10) return false;
        return substr($fe, 0, 10) >= $fechaInicio && substr($fe, 0, 10) <= $fechaFin;
    }));
}
// Obtener numCronograma de los registros que se van a eliminar, para reutilizarlo y que la vista por numCronograma muestre los datos actualizados
$numCronograma = 1;
$chkNum = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
$tieneNumCronograma = $chkNum && $chkNum->num_rows > 0;
if ($tieneNumCronograma) {
    $stNum = $conn->prepare("SELECT numCronograma FROM san_fact_cronograma WHERE codPrograma = ? AND DATE(fechaEjecucion) >= ? ORDER BY numCronograma ASC LIMIT 1");
    if ($stNum) {
        $stNum->bind_param("ss", $codPrograma, $hoy);
        $stNum->execute();
        $rowNum = $stNum->get_result()->fetch_assoc();
        $stNum->close();
        if ($rowNum && isset($rowNum['numCronograma']) && $rowNum['numCronograma'] !== null && $rowNum['numCronograma'] !== '') {
            $numCronograma = (int)$rowNum['numCronograma'];
        } else {
            $resMax = $conn->query("SELECT COALESCE(MAX(numCronograma), 0) + 1 AS nextNum FROM san_fact_cronograma");
            if ($resMax && $row = $resMax->fetch_assoc()) $numCronograma = (int)$row['nextNum'];
        }
    }
}

$numCronogramasConFuturos = [];
if ($tieneNumCronograma) {
    $stNumsFut = $conn->prepare("SELECT DISTINCT numCronograma FROM san_fact_cronograma WHERE codPrograma = ? AND DATE(fechaEjecucion) >= ?");
    if ($stNumsFut) {
        $stNumsFut->bind_param("ss", $codPrograma, $hoy);
        $stNumsFut->execute();
        $resNumsFut = $stNumsFut->get_result();
        while ($r = $resNumsFut->fetch_assoc()) {
            $n = (int)($r['numCronograma'] ?? 0);
            if ($n > 0) $numCronogramasConFuturos[] = $n;
        }
        $stNumsFut->close();
    }
}

$del = $conn->prepare("DELETE FROM san_fact_cronograma WHERE codPrograma = ? AND DATE(fechaEjecucion) >= ?");
$del->bind_param("ss", $codPrograma, $hoy);
$del->execute();
$del->close();
// Año para cargapollo_proyeccion: priorizar el año del nuevo periodo del programa (fechaInicio)
$anio = (int)date('Y');
if ($fechaInicioPrograma && strlen($fechaInicioPrograma) >= 4) {
    $anio = (int)substr($fechaInicioPrograma, 0, 4);
} elseif ($anioAsignaciones > 0) {
    $anio = $anioAsignaciones;
}
// Si el rango del programa excede el año seleccionado: usar 1 ene o 31 dic del año
if ($anio > 0) {
    if ($fechaInicioPrograma !== null && $fechaInicioPrograma < $anio . '-01-01') {
        $fechaInicioPrograma = $anio . '-01-01';
    }
    if ($fechaFinPrograma !== null && $fechaFinPrograma > $anio . '-12-31') {
        $fechaFinPrograma = $anio . '-12-31';
    }
}
$placeholders = implode(',', array_fill(0, count($edadesParaTabla), '?'));
$allItems = [];
foreach ($scopeByKey as $sc) {
    $granja = $sc['granja'];
    $galpon = $sc['galpon'];
    $campanias = $sc['campanias'];
    if (empty($campanias)) continue;
    $items = [];
    foreach ($campanias as $campaniaVal) {
        $campania = str_pad($campaniaVal, 3, '0', STR_PAD_LEFT);
        $tcencos = $granja . $campania;
        // Misma lógica que calcular_fechas.php (modo especifico_multi): fecha, edad, fecha_carga desde cargapollo_proyeccion
        $sqlFechas = "SELECT fecha AS fecha_ejecucion, edad, tcencos, tcodint, DATE_ADD(fecha, INTERVAL -(edad) + 1 DAY) AS fecha_carga FROM cargapollo_proyeccion WHERE tcencos = ? AND tcodint = ? AND edad IN ($placeholders) AND YEAR(fecha) = ? AND fecha >= ? AND fecha <= ? GROUP BY fecha, edad, tcencos, tcodint ORDER BY fecha, edad";
        $fechaMin = $fechaInicioPrograma ?? ($anio . '-01-01');
        $fechaMax = $fechaFinPrograma ?? ($anio . '-12-31');
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
        $paresEstaCampania = filtrarParesPorRangoRecal($paresEstaCampania, $fechaInicioPrograma, $fechaFinPrograma);
        if (!empty($paresEstaCampania)) {
            $items[] = ['granja' => $granja, 'campania' => $campania, 'galpon' => $galpon, 'fechas' => $paresEstaCampania];
        }
    }
    $zonaItem = $sc['zona'] !== '' ? $sc['zona'] : $zonaDefault;
    $subzonaVal = $sc['subzona'];
    $nomGranja = $sc['nomGranja'];
    foreach ($items as $it) {
        $allItems[] = array_merge($it, ['zona' => $zonaItem, 'subzona' => $subzonaVal, 'nomGranja' => $nomGranja]);
    }
}
$usuario = $_SESSION['usuario'] ?? 'WEB';
$chkNom = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
$tieneNomGranjaCol = $chkNom && $chkNom->num_rows > 0;
$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;
$cols = "granja, campania, galpon, codPrograma, nomPrograma, fechaCarga, fechaEjecucion, usuarioRegistro, zona, subzona";
$placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
$types = "ssssssssss";
if ($tieneNumCronograma) { $cols .= ", numCronograma"; $placeholders .= ", ?"; $types .= "i"; }
if ($tieneNomGranjaCol) { $cols .= ", nomGranja"; $placeholders .= ", ?"; $types .= "s"; }
if ($tieneEdad) { $cols .= ", edad"; $placeholders .= ", ?"; $types .= "i"; }
$stmt = $conn->prepare("INSERT INTO san_fact_cronograma ($cols) VALUES ($placeholders)");
if (!$stmt) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error prepare insert', 'total' => 0]);
    exit;
}
$total = 0;
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
$stmt->close();

// Sincronizar san_cronograma_despliegue con el scope usado (para que futuros recálculos lo tengan)
// Solo se permite BORRAR en despliegue cuando TODAS las asignaciones son futuras (si hay pasadas, solo añadir nuevas)
$chkDespliegue = @$conn->query("SHOW TABLES LIKE 'san_cronograma_despliegue'");
if ($chkDespliegue && $chkDespliegue->num_rows > 0 && $tieneNumCronograma && $total > 0) {
    $numsParaBorrar = array_unique(array_merge($numCronogramasConFuturos, [$numCronograma]));
    $stTienePasados = $conn->prepare("SELECT 1 FROM san_fact_cronograma WHERE numCronograma = ? AND DATE(fechaEjecucion) < ? LIMIT 1");
    foreach ($numsParaBorrar as $nDel) {
        $soloFuturos = true;
        if ($stTienePasados) {
            $stTienePasados->bind_param("is", $nDel, $hoy);
            $stTienePasados->execute();
            $soloFuturos = ($stTienePasados->get_result()->num_rows === 0);
            $stTienePasados->free_result();
        }
        if ($soloFuturos) {
            $delDesp = $conn->prepare("DELETE FROM san_cronograma_despliegue WHERE numCronograma = ?");
            if ($delDesp) {
                $delDesp->bind_param("i", $nDel);
                @$delDesp->execute();
                $delDesp->close();
            }
        }
    }
    if ($stTienePasados) $stTienePasados->close();
    $despliegueUnicos = [];
    foreach ($scopeByKey as $sc) {
        $g = $sc['granja'] ?? '';
        $gp = $sc['galpon'] ?? '';
        foreach ($sc['campanias'] ?? [] as $c) {
            $campania = str_pad($c, 3, '0', STR_PAD_LEFT);
            $key = $g . '|' . $campania . '|' . $gp;
            if (!isset($despliegueUnicos[$key])) {
                $despliegueUnicos[$key] = ['granja' => $g, 'campania' => $campania, 'galpon' => $gp];
            }
        }
    }
    $numCronogramaTienePasados = false;
    $stCheck = $conn->prepare("SELECT 1 FROM san_fact_cronograma WHERE numCronograma = ? AND DATE(fechaEjecucion) < ? LIMIT 1");
    if ($stCheck) {
        $stCheck->bind_param("is", $numCronograma, $hoy);
        $stCheck->execute();
        $numCronogramaTienePasados = ($stCheck->get_result()->num_rows > 0);
        $stCheck->close();
    }
    $stDesp = $conn->prepare("INSERT INTO san_cronograma_despliegue (numCronograma, granja, campania, galpon, usuarioRegistro, fechaHoraRegistro) VALUES (?, ?, ?, ?, ?, NOW())");
    $stChkExiste = $numCronogramaTienePasados ? $conn->prepare("SELECT 1 FROM san_cronograma_despliegue WHERE numCronograma = ? AND granja = ? AND campania = ? AND galpon = ? LIMIT 1") : null;
    if ($stDesp) {
        foreach ($despliegueUnicos as $d) {
            if ($stChkExiste) {
                $stChkExiste->bind_param("isss", $numCronograma, $d['granja'], $d['campania'], $d['galpon']);
                $stChkExiste->execute();
                if ($stChkExiste->get_result()->num_rows > 0) continue;
            }
            $stDesp->bind_param("issss", $numCronograma, $d['granja'], $d['campania'], $d['galpon'], $usuario);
            @$stDesp->execute();
        }
        $stDesp->close();
    }
    if ($stChkExiste) $stChkExiste->close();
}

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
        'input' => [
            'codPrograma' => $codPrograma,
            'codProgramaOrigen' => $codProgramaOrigen,
            'fechaInicio' => $fechaInicioInput,
            'fechaFin' => $fechaFinInput,
            'esCrearNuevo' => $esCrearNuevo ?? false
        ],
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
            'diagnostico' => 'Las fechas se obtienen de cargapollo_proyeccion. Si solo hay datos hasta feb/mar para las combinaciones granja+galpon+campania, las fechas no irán más allá. Verifique que la proyección tenga datos para todo el rango del programa.'
        ]
    ];
}
$conn->close();
echo json_encode($respuesta);