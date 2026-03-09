<?php
/**
 * Reporte comparativo MSA / MSB (muestras): planificado san_fact_cronograma (codPrograma LIKE 'MSA%' o 'MSB%'),
 * desarrollado san_fact_solicitud_det (codRef = granja(3)+campaña(3)+galpón(2)+edad(2), fecToma = fecha de trabajo).
 * MSA: adulto = edad > 1. MSB: edad = 1.
 * Parámetro: tipoMS = MSA | MSB
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['active'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('No autorizado');
}
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '0');
@set_time_limit(0);

$tipoMS = strtoupper(trim((string)($_GET['tipoMS'] ?? '')));
if ($tipoMS !== 'MSA' && $tipoMS !== 'MSB') {
    exit('Indique tipoMS=MSA o tipoMS=MSB.');
}
$codProgramaLike = $tipoMS . '%';

$periodoTipo = trim((string)($_GET['periodoTipo'] ?? ''));
$fechaUnica = trim((string)($_GET['fechaUnica'] ?? ''));
$fechaInicio = trim((string)($_GET['fechaInicio'] ?? ''));
$fechaFin = trim((string)($_GET['fechaFin'] ?? ''));
$mesUnico = trim((string)($_GET['mesUnico'] ?? ''));
$mesInicio = trim((string)($_GET['mesInicio'] ?? ''));
$mesFin = trim((string)($_GET['mesFin'] ?? ''));

$filtroGranjas = [];
if (isset($_GET['granja']) && is_array($_GET['granja'])) {
    foreach ($_GET['granja'] as $v) {
        $v = trim((string)$v);
        if ($v !== '') $filtroGranjas[] = $v;
    }
} elseif (!empty($_GET['granja'])) {
    $v = trim((string)$_GET['granja']);
    if ($v !== '') $filtroGranjas[] = $v;
}
$filtroGalpones = [];
$filtroCampanias = [];
if (isset($_GET['galpon']) && is_array($_GET['galpon'])) {
    foreach ($_GET['galpon'] as $v) {
        $v = trim((string)$v);
        if ($v !== '') $filtroGalpones[] = $v;
    }
} elseif (!empty($_GET['galpon'])) {
    $v = trim((string)$_GET['galpon']);
    if ($v !== '') $filtroGalpones[] = $v;
}
if (isset($_GET['campania']) && is_array($_GET['campania'])) {
    foreach ($_GET['campania'] as $v) {
        $v = trim((string)$v);
        if ($v !== '') $filtroCampanias[] = $v;
    }
} elseif (!empty($_GET['campania'])) {
    $v = trim((string)$_GET['campania']);
    if ($v !== '') $filtroCampanias[] = $v;
}
$filtroEstado = trim((string)($_GET['estado'] ?? ''));
$filtroCategoria = trim((string)($_GET['categoria'] ?? ''));

$rango = null;
if (is_file(__DIR__ . '/../../../../includes/filtro_periodo_util.php')) {
    include_once __DIR__ . '/../../../../includes/filtro_periodo_util.php';
    $rango = periodo_a_rango([
        'periodoTipo' => $periodoTipo,
        'fechaUnica' => $fechaUnica,
        'fechaInicio' => $fechaInicio,
        'fechaFin' => $fechaFin,
        'mesUnico' => $mesUnico,
        'mesInicio' => $mesInicio,
        'mesFin' => $mesFin
    ]);
} else {
    if ($periodoTipo === 'POR_FECHA' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaUnica)) {
        $rango = ['desde' => $fechaUnica, 'hasta' => $fechaUnica];
    } elseif ($periodoTipo === 'ENTRE_FECHAS' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
        $rango = ['desde' => $fechaInicio, 'hasta' => $fechaFin];
    } elseif ($periodoTipo === 'POR_MES' && preg_match('/^\d{4}-\d{2}$/', $mesUnico)) {
        $rango = ['desde' => $mesUnico . '-01', 'hasta' => date('Y-m-t', strtotime($mesUnico . '-01'))];
    } elseif ($periodoTipo === 'ENTRE_MESES' && preg_match('/^\d{4}-\d{2}$/', $mesInicio) && preg_match('/^\d{4}-\d{2}$/', $mesFin)) {
        $rango = ['desde' => $mesInicio . '-01', 'hasta' => date('Y-m-t', strtotime($mesFin . '-01'))];
    } elseif ($periodoTipo === 'ULTIMA_SEMANA') {
        $rango = ['desde' => date('Y-m-d', strtotime('-6 days')), 'hasta' => date('Y-m-d')];
    }
}
if ($rango === null || empty($rango['desde']) || empty($rango['hasta'])) {
    if ($periodoTipo === 'TODOS') {
        $rango = ['desde' => '2000-01-01', 'hasta' => date('Y-m-d', strtotime('+1 year'))];
    } else {
        exit('Indique un período válido.');
    }
}

if (!function_exists('norm_granja_3')) {
    function norm_granja_3($v) {
        $s = trim((string)$v);
        if ($s === '') return '';
        if (ctype_digit($s)) {
            $n = (int)$s;
            return substr(str_pad((string)$n, 3, '0', STR_PAD_LEFT), -3);
        }
        if (strlen($s) >= 3) return substr($s, 0, 3);
        return str_pad($s, 3, '0', STR_PAD_LEFT);
    }
}
if (!function_exists('norm_campania_3')) {
    function norm_campania_3($camp, $fallback = '') {
        $s = trim((string)$camp);
        if ($s === '') $s = trim((string)$fallback);
        if ($s === '') return '';
        if (ctype_digit($s)) return substr(str_pad($s, 3, '0', STR_PAD_LEFT), -3);
        return $s;
    }
}
if (!function_exists('norm_num_text')) {
    function norm_num_text($v) {
        $s = trim((string)$v);
        if ($s === '') return '';
        if (ctype_digit($s)) {
            $n = ltrim($s, '0');
            return $n === '' ? '0' : $n;
        }
        return $s;
    }
}

include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    exit('Error de conexión');
}
include_once __DIR__ . '/comparativo_unificado_util.php';

$filtroGranjasNorm = [];
foreach ($filtroGranjas as $gIn) {
    $gN = substr(str_pad($gIn, 3, '0', STR_PAD_LEFT), 0, 3);
    if ($gN !== '') $filtroGranjasNorm[$gN] = true;
}
$filtroGranjasNorm = array_keys($filtroGranjasNorm);

$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;
$chkNomGranja = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
$chkZona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'zona'");
$chkSubzona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'subzona'");
$tieneNomGranja = $chkNomGranja && $chkNomGranja->num_rows > 0;
$tieneZona = $chkZona && $chkZona->num_rows > 0;
$tieneSubzona = $chkSubzona && $chkSubzona->num_rows > 0;

$esTodos = ($periodoTipo === 'TODOS');
$metaPorGranja = [];

$codigosMS = [];
$r = @$conn->query("SELECT codigo FROM san_fact_programa_cab WHERE UPPER(TRIM(codigo)) LIKE '" . $conn->real_escape_string($tipoMS) . "%'");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $cod = trim((string)($row['codigo'] ?? ''));
        if ($cod !== '') $codigosMS[$cod] = true;
    }
}
$codigosMS = array_keys($codigosMS);
$esEspecialPorCod = comparativo_cargar_es_especial($conn, $codigosMS);
$usarKeyEspecial = !empty($esEspecialPorCod) && max(array_values($esEspecialPorCod)) === 1;

$chkNumCronograma = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
$chkToleranciaCrono = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'tolerancia'");
$chkCategoriaCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'categoria'");
$tieneNumCronograma = $chkNumCronograma && $chkNumCronograma->num_rows > 0;
$tieneToleranciaCrono = $chkToleranciaCrono && $chkToleranciaCrono->num_rows > 0;
$tieneCategoriaCab = $chkCategoriaCab && $chkCategoriaCab->num_rows > 0;

$joinCategoriaCrono = ($filtroCategoria !== '' && $tieneCategoriaCab) ? " LEFT JOIN san_fact_programa_cab cab ON TRIM(cab.codigo) = TRIM(c.codPrograma)" : "";
$sqlCronoBase = "SELECT DATE(c.fechaEjecucion) AS fecha_ref, c.granja, c.campania, c.galpon, c.codPrograma"
    . ($tieneEdad ? ", c.edad" : ", NULL AS edad")
    . ($tieneNomGranja ? ", c.nomGranja" : "")
    . ($tieneZona ? ", c.zona" : "")
    . ($tieneSubzona ? ", c.subzona" : "")
    . ($tieneNumCronograma ? ", COALESCE(c.numCronograma, 0) AS numCronograma" : ", 0 AS numCronograma")
    . ($tieneToleranciaCrono ? ", COALESCE(NULLIF(c.tolerancia, 0), 1) AS tolerancia" : ", 1 AS tolerancia")
    . " FROM san_fact_cronograma c" . $joinCategoriaCrono;
$whereCrono = [" UPPER(TRIM(c.codPrograma)) LIKE ?"];
$paramsCrono = [$codProgramaLike];
$typesCrono = 's';
if (!$esTodos) {
    $whereCrono[] = " DATE(c.fechaEjecucion) >= ? AND DATE(c.fechaEjecucion) <= ?";
    $paramsCrono[] = $rango['desde'];
    $paramsCrono[] = $rango['hasta'];
    $typesCrono .= 'ss';
}
if (count($filtroGranjasNorm) > 0) {
    $phG = implode(',', array_fill(0, count($filtroGranjasNorm), '?'));
    $whereCrono[] = " LEFT(TRIM(c.granja), 3) IN ($phG)";
    foreach ($filtroGranjasNorm as $gN) { $paramsCrono[] = $gN; $typesCrono .= 's'; }
}
if (count($filtroGalpones) > 0) {
    $phGp = implode(',', array_fill(0, count($filtroGalpones), '?'));
    $whereCrono[] = " TRIM(c.galpon) IN ($phGp)";
    foreach ($filtroGalpones as $fgp) { $paramsCrono[] = $fgp; $typesCrono .= 's'; }
}
if (count($filtroCampanias) > 0) {
    $ph = implode(',', array_fill(0, count($filtroCampanias), '?'));
    $whereCrono[] = " TRIM(c.campania) IN ($ph)";
    foreach ($filtroCampanias as $fc) { $paramsCrono[] = $fc; $typesCrono .= 's'; }
}
if ($filtroCategoria !== '' && $tieneCategoriaCab) {
    $whereCrono[] = " TRIM(cab.categoria) = ?";
    $paramsCrono[] = $filtroCategoria;
    $typesCrono .= 's';
}

$planificadosPorKey = [];
$stmtCrono = $conn->prepare($sqlCronoBase . " WHERE" . implode(" AND", $whereCrono) . " ORDER BY c.granja, c.campania, c.galpon" . ($tieneEdad ? ", c.edad" : "") . ", c.fechaEjecucion");
if ($stmtCrono && (count($paramsCrono) === 0 || $stmtCrono->bind_param($typesCrono, ...$paramsCrono)) && $stmtCrono->execute()) {
    $resCrono = $stmtCrono->get_result();
    while ($row = $resCrono->fetch_assoc()) {
        $g = norm_granja_3($row['granja'] ?? '');
        $c = norm_campania_3($row['campania'] ?? '');
        $gp = norm_num_text($row['galpon'] ?? '');
        $edadRaw = $tieneEdad ? ($row['edad'] ?? null) : null;
        $e = ($edadRaw !== null && (int)$edadRaw === -1) ? '0' : norm_num_text($edadRaw ?? '');
        $codPrograma = trim((string)($row['codPrograma'] ?? ''));
        $key = comparativo_build_key($g, $c, $gp, $codPrograma, $esEspecialPorCod);
        $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
        $nomGranja = $tieneNomGranja ? trim((string)($row['nomGranja'] ?? '')) : '';
        $zona = $tieneZona ? trim((string)($row['zona'] ?? '')) : '';
        $subzona = $tieneSubzona ? trim((string)($row['subzona'] ?? '')) : '';
        $numCron = (int)($row['numCronograma'] ?? 0);
        if (!isset($planificadosPorKey[$key])) {
            $planificadosPorKey[$key] = ['granja' => $g, 'campania' => $c, 'galpon' => $gp, 'edad' => $e, 'nomGranja' => $nomGranja, 'zona' => $zona, 'subzona' => $subzona, 'fechas' => [], 'toleranciaPorFecha' => [], 'edadPorFecha' => [], 'numCronogramaPorFecha' => []];
        } else {
            if ($planificadosPorKey[$key]['edad'] === '' && $e !== '') $planificadosPorKey[$key]['edad'] = $e;
        }
        if ($fechaRef && !in_array($fechaRef, $planificadosPorKey[$key]['fechas'], true)) {
            $planificadosPorKey[$key]['fechas'][] = $fechaRef;
        }
        if ($fechaRef) {
            $tolRow = max(1, (int)($row['tolerancia'] ?? 1));
            $actual = (int)($planificadosPorKey[$key]['toleranciaPorFecha'][$fechaRef] ?? 0);
            if ($tolRow > $actual) $planificadosPorKey[$key]['toleranciaPorFecha'][$fechaRef] = $tolRow;
            $planificadosPorKey[$key]['numCronogramaPorFecha'][$fechaRef] = $numCron;
        }
        if ($fechaRef && $e !== '') {
            $planificadosPorKey[$key]['edadPorFecha'][$fechaRef] = $e;
        }
        if (!isset($metaPorGranja[$g])) $metaPorGranja[$g] = ['nomGranja' => $nomGranja, 'zona' => $zona, 'subzona' => $subzona];
    }
    $stmtCrono->close();
}

// Desarrollado: san_fact_solicitud_det. codRef = granja(3)+campaña(3)+galpón(2)+edad(2). fecToma = fecha de trabajo.
$ejecutadosPorKey = [];
$chkSolicitud = @$conn->query("SHOW TABLES LIKE 'san_fact_solicitud_det'");
$chkFecToma = $chkSolicitud && $chkSolicitud->num_rows > 0 ? @$conn->query("SHOW COLUMNS FROM san_fact_solicitud_det LIKE 'fecToma'") : false;
$tieneFecToma = $chkFecToma && $chkFecToma->num_rows > 0;

if ($chkSolicitud && $chkSolicitud->num_rows > 0 && $tieneFecToma) {
    $codRefPad = "LPAD(TRIM(CAST(a.codRef AS CHAR)), 10, '0')";
    $granjaExpr = "LEFT($codRefPad, 3)";
    $campaniaExpr = "SUBSTRING($codRefPad, 4, 3)";
    $galponExpr = "SUBSTRING($codRefPad, 7, 2)";
    $edadExpr = "SUBSTRING($codRefPad, 9, 2)";
    $edadNumExpr = "CAST(SUBSTRING($codRefPad, 9, 2) AS UNSIGNED)";
    if ($tipoMS === 'MSA') {
        $whereEdad = " AND $edadNumExpr > 1";
    } else {
        $whereEdad = " AND $edadNumExpr = 1";
    }
    $sqlDes = "SELECT DATE(a.fecToma) AS fecha_ref,
        $granjaExpr AS granja,
        $campaniaExpr AS campania,
        $galponExpr AS galpon,
        $edadExpr AS edad_str
        FROM san_fact_solicitud_det a
        WHERE TRIM(a.codRef) <> '' AND LENGTH(TRIM(CAST(a.codRef AS CHAR))) >= 9 $whereEdad";
    $paramsDes = [];
    $typesDes = '';
    if (!$esTodos) {
        $sqlDes .= " AND DATE(a.fecToma) >= ? AND DATE(a.fecToma) <= ?";
        $paramsDes[] = $rango['desde'];
        $paramsDes[] = $rango['hasta'];
        $typesDes .= 'ss';
    }
    if (count($filtroGranjasNorm) > 0) {
        $phG = implode(',', array_fill(0, count($filtroGranjasNorm), '?'));
        $sqlDes .= " AND $granjaExpr IN ($phG)";
        foreach ($filtroGranjasNorm as $gN) { $paramsDes[] = $gN; $typesDes .= 's'; }
    }
    if (count($filtroGalpones) > 0) {
        $phGp = implode(',', array_fill(0, count($filtroGalpones), '?'));
        $sqlDes .= " AND $galponExpr IN ($phGp)";
        foreach ($filtroGalpones as $fgp) { $paramsDes[] = $fgp; $typesDes .= 's'; }
    }
    if (count($filtroCampanias) > 0) {
        $ph = implode(',', array_fill(0, count($filtroCampanias), '?'));
        $sqlDes .= " AND $campaniaExpr IN ($ph)";
        foreach ($filtroCampanias as $fc) { $paramsDes[] = $fc; $typesDes .= 's'; }
    }
    $stDes = $conn->prepare($sqlDes);
    if ($stDes && (count($paramsDes) === 0 || $stDes->bind_param($typesDes, ...$paramsDes)) && $stDes->execute()) {
        $resDes = $stDes->get_result();
        while ($row = $resDes->fetch_assoc()) {
            $g = norm_granja_3($row['granja'] ?? '');
            $c = norm_campania_3($row['campania'] ?? '');
            $gp = norm_num_text($row['galpon'] ?? '');
            $e = norm_num_text($row['edad_str'] ?? '0');
            $key = $usarKeyEspecial ? $g : ($g . '|' . $c . '|' . $gp);
            $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
            if (!isset($ejecutadosPorKey[$key])) {
                $ejecutadosPorKey[$key] = ['granja' => $g, 'campania' => $c, 'galpon' => $gp, 'edad' => $e, 'fechas' => []];
            }
            if ($fechaRef && !in_array($fechaRef, $ejecutadosPorKey[$key]['fechas'], true)) {
                $ejecutadosPorKey[$key]['fechas'][] = $fechaRef;
            }
        }
        $stDes->close();
    }
}

// Conjunto de claves único (reutilizado para enriquecer y para filas)
$clavesUnion = array_values(array_unique(array_merge(array_keys($planificadosPorKey), array_keys($ejecutadosPorKey))));
// Enriquecer zona/subzona/nomGranja
$granjasParaEnriquecer = [];
foreach ($clavesUnion as $key) {
    $g = (strpos($key, '|') !== false) ? explode('|', $key)[0] : $key;
    if ($g === '') continue;
    $meta = $metaPorGranja[$g] ?? ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
    if (trim($meta['zona'] ?? '') === '' || trim($meta['subzona'] ?? '') === '') {
        $granjasParaEnriquecer[$g] = true;
    }
}
$granjasParaEnriquecer = array_keys($granjasParaEnriquecer);
if (count($granjasParaEnriquecer) > 0) {
    $chkPi = @$conn->query("SHOW TABLES LIKE 'pi_dim_detalles'");
    $chkCar = @$conn->query("SHOW TABLES LIKE 'pi_dim_caracteristicas'");
    if ($chkPi && $chkPi->num_rows > 0 && $chkCar && $chkCar->num_rows > 0) {
        $ph = implode(',', array_fill(0, count($granjasParaEnriquecer), '?'));
        $sqlZs = "SELECT LEFT(TRIM(det.id_granja), 3) AS codigo,
            MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'ZONA' THEN TRIM(det.dato) END) AS zona,
            MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'SUBZONA' THEN TRIM(det.dato) END) AS subzona
            FROM pi_dim_detalles det
            INNER JOIN pi_dim_caracteristicas car ON car.id = det.id_caracteristica
            WHERE LEFT(TRIM(det.id_granja), 3) IN ($ph) AND UPPER(TRIM(car.nombre)) IN ('ZONA', 'SUBZONA')
            GROUP BY LEFT(TRIM(det.id_granja), 3)";
        $stZs = $conn->prepare($sqlZs);
        if ($stZs) {
            $stZs->bind_param(str_repeat('s', count($granjasParaEnriquecer)), ...$granjasParaEnriquecer);
            $stZs->execute();
            $resZs = $stZs->get_result();
            while ($rowZs = $resZs->fetch_assoc()) {
                $cg = trim((string)($rowZs['codigo'] ?? ''));
                if ($cg === '') continue;
                $z = trim((string)($rowZs['zona'] ?? ''));
                $sz = trim((string)($rowZs['subzona'] ?? ''));
                if (!isset($metaPorGranja[$cg])) $metaPorGranja[$cg] = ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
                if ($z !== '') $metaPorGranja[$cg]['zona'] = $z;
                if ($sz !== '') $metaPorGranja[$cg]['subzona'] = $sz;
            }
            $stZs->close();
        }
    }
    $chkRegCen = @$conn->query("SHOW TABLES LIKE 'regcencosgalpones'");
    if ($chkRegCen && $chkRegCen->num_rows > 0) {
        $ph = implode(',', array_fill(0, count($granjasParaEnriquecer), '?'));
        $stNom = $conn->prepare("SELECT LEFT(TRIM(tcencos), 3) AS codigo, MAX(TRIM(tnomcen)) AS nombre FROM regcencosgalpones WHERE TRIM(tcencos) <> '' AND LEFT(TRIM(tcencos), 3) IN ($ph) GROUP BY LEFT(TRIM(tcencos), 3)");
        if ($stNom) {
            $stNom->bind_param(str_repeat('s', count($granjasParaEnriquecer)), ...$granjasParaEnriquecer);
            $stNom->execute();
            $resNom = $stNom->get_result();
            while ($rowNom = $resNom->fetch_assoc()) {
                $cg = trim((string)($rowNom['codigo'] ?? ''));
                $nom = trim((string)($rowNom['nombre'] ?? ''));
                if ($cg !== '' && $nom !== '' && isset($metaPorGranja[$cg]) && ($metaPorGranja[$cg]['nomGranja'] ?? '') === '') {
                    $metaPorGranja[$cg]['nomGranja'] = $nom;
                }
            }
            $stNom->close();
        }
    }
}

$conn->close();

// Construir filas (mismo criterio que vacunas/CP)
$todasLasClaves = $clavesUnion;
$filasPorKey = [];
foreach ($todasLasClaves as $key) {
    $plan = isset($planificadosPorKey[$key]) ? $planificadosPorKey[$key] : null;
    $eje = isset($ejecutadosPorKey[$key]) ? $ejecutadosPorKey[$key] : null;
    $g = $plan ? $plan['granja'] : $eje['granja'];
    $c = $plan ? $plan['campania'] : $eje['campania'];
    $gp = $plan ? $plan['galpon'] : $eje['galpon'];
    $ed = $plan ? $plan['edad'] : ($eje ? $eje['edad'] : '');
    $fechasPlan = $plan ? $plan['fechas'] : [];
    $fechasEje = $eje ? $eje['fechas'] : [];
    $toleranciaPorFecha = $plan && isset($plan['toleranciaPorFecha']) ? $plan['toleranciaPorFecha'] : [];
    sort($fechasPlan);
    sort($fechasEje);
    $matchResult = match_plan_eje_con_tolerancia_por_fecha($fechasPlan, $toleranciaPorFecha, $fechasEje);
    $interseccion = $matchResult['interseccion'];
    $estadoMatch = $matchResult['estado'];
    $fechaMostrarMatch = $matchResult['fechaMostrar'];
    $metaG = $metaPorGranja[$g] ?? ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
    $nomGranja = trim((string)($plan['nomGranja'] ?? $metaG['nomGranja'] ?? ''));
    if ($nomGranja === '' && $eje && isset($ejecutadosPorKey[$key])) {
        $nomGranja = trim((string)($metaG['nomGranja'] ?? ''));
    }
    $zona = trim((string)($plan['zona'] ?? $metaG['zona'] ?? ''));
    $subzona = trim((string)($plan['subzona'] ?? $metaG['subzona'] ?? ''));
    $tipo = '';
    $estado = '';
    if (count($interseccion) > 0) {
        $tipo = 'Planificado';
        $estado = $estadoMatch !== '' ? $estadoMatch : 'SI CUMPLIO';
    } elseif (count($fechasPlan) > 0 && count($fechasEje) === 0) {
        $tipo = 'Planificado';
        $estado = 'NO CUMPLIO';
    } elseif (count($fechasEje) > 0 && count($fechasPlan) === 0) {
        $tipo = 'NO PLANIFICADO';
        $estado = 'SI CUMPLIO';
    } elseif (count($fechasPlan) > 0 && count($fechasEje) > 0) {
        $tipo = 'Planificado';
        $estado = 'ATRASADO';
    }
    $fechaMostrar = '';
    if ($fechaMostrarMatch !== '') $fechaMostrar = $fechaMostrarMatch;
    elseif (count($fechasEje) > 0) $fechaMostrar = $fechasEje[0];
    elseif (count($fechasPlan) > 0) $fechaMostrar = $fechasPlan[0];
    $keyBase = (strpos($key, '|') !== false) ? ($g . '|' . $c . '|' . $gp) : $key;
    $filasPorKey[$key] = [
        'zona' => $zona,
        'subzona' => $subzona,
        'granja' => $g,
        'nomGranja' => $nomGranja,
        'campania' => $c,
        'galpon' => $gp,
        'edad' => $ed,
        'planificado' => $fechasPlan,
        'ejecutado' => $fechasEje,
        'fechaMostrar' => $fechaMostrar,
        'tipo' => $tipo,
        'estado' => $estado,
        'keyBase' => $keyBase,
        'key' => $key,
    ];
}

// ATRASADO: emparejar NO CUMPLIO con NO PLANIFICADO (mismo keyBase)
$noCumplioPorKeyBase = [];
foreach ($filasPorKey as $key => $f) {
    if ($f['estado'] === 'NO CUMPLIO' && $f['tipo'] === 'Planificado') {
        $kb = $f['keyBase'] ?? ($f['granja'] . '|' . $f['campania'] . '|' . $f['galpon']);
        if (!isset($noCumplioPorKeyBase[$kb])) $noCumplioPorKeyBase[$kb] = [];
        $noCumplioPorKeyBase[$kb][] = ['key' => $key, 'fechaMostrar' => $f['fechaMostrar'] ?? ''];
    }
}
foreach ($noCumplioPorKeyBase as $kb => $lista) {
    usort($noCumplioPorKeyBase[$kb], function ($a, $b) {
        return strcmp($a['fechaMostrar'] ?? '', $b['fechaMostrar'] ?? '');
    });
}
$usadoNoCumplioPorKeyBase = [];
foreach (array_keys($noCumplioPorKeyBase) as $kb) {
    $usadoNoCumplioPorKeyBase[$kb] = 0;
}
$candidatosAtrasado = [];
foreach ($filasPorKey as $key => $f) {
    if ($f['tipo'] === 'NO PLANIFICADO' && $f['estado'] === 'SI CUMPLIO' && count($f['planificado']) === 0 && count($f['ejecutado']) > 0) {
        $kb = $f['keyBase'] ?? ($f['granja'] . '|' . $f['campania'] . '|' . $f['galpon']);
        if (!isset($candidatosAtrasado[$kb])) $candidatosAtrasado[$kb] = [];
        $candidatosAtrasado[$kb][] = ['key' => $key, 'fechaMostrar' => $f['fechaMostrar'] ?? ''];
    }
}
foreach ($candidatosAtrasado as $kb => $lista) {
    usort($candidatosAtrasado[$kb], function ($a, $b) {
        return strcmp($a['fechaMostrar'] ?? '', $b['fechaMostrar'] ?? '');
    });
}
foreach ($candidatosAtrasado as $kb => $listaCandidatos) {
    $listaNoCumplio = $noCumplioPorKeyBase[$kb] ?? [];
    $idx = $usadoNoCumplioPorKeyBase[$kb] ?? 0;
    foreach ($listaCandidatos as $c) {
        if ($idx >= count($listaNoCumplio)) break;
        $parent = $listaNoCumplio[$idx];
        $filasPorKey[$c['key']]['tipo'] = '';
        $filasPorKey[$c['key']]['estado'] = 'ATRASADO';
        $filasPorKey[$c['key']]['parentKey'] = $parent['key'];
        $idx++;
    }
    $usadoNoCumplioPorKeyBase[$kb] = $idx;
}

// Una fila por fecha (edad para diferenciar); mismo criterio de match
$filas = [];
foreach ($filasPorKey as $key => $f) {
    if (count($f['planificado']) === 0 && count($f['ejecutado']) === 0) continue;
    $plan = isset($planificadosPorKey[$key]) ? $planificadosPorKey[$key] : null;
    $toleranciaPorFecha = $plan && isset($plan['toleranciaPorFecha']) ? $plan['toleranciaPorFecha'] : [];
    $edadPorFecha = $plan && isset($plan['edadPorFecha']) ? $plan['edadPorFecha'] : [];
    $fechasPlan = $f['planificado'];
    $fechasEje = $f['ejecutado'];
    sort($fechasPlan);
    sort($fechasEje);
    $matchResult = match_plan_eje_con_tolerancia_por_fecha($fechasPlan, $toleranciaPorFecha, $fechasEje);
    $emparejado = emparejar_atrasados_con_plan_anterior($matchResult['pares'], $matchResult['ejecutadasSinMatch'], $toleranciaPorFecha);
    $matchResult['pares'] = $emparejado['pares'];
    $matchResult['ejecutadasSinMatch'] = $emparejado['ejecutadasSinMatch'];
    $ed = $f['edad'] ?? '';
    $keyBase = $f['keyBase'] ?? ($f['granja'] . '|' . $f['campania'] . '|' . $f['galpon']);
    $parentKey = isset($f['parentKey']) ? $f['parentKey'] : null;
    $hoy = date('Y-m-d');
    foreach ($matchResult['pares'] as $par) {
        $fechaPlan = $par['plan'];
        $fechaEje = $par['ejec'];
        $exactamenteIguales = ($fechaEje !== null && $fechaPlan === $fechaEje);
        if ($exactamenteIguales) {
            $edadFila = isset($edadPorFecha[$fechaPlan]) ? $edadPorFecha[$fechaPlan] : $ed;
            $filas[] = [
                'zona' => $f['zona'], 'subzona' => $f['subzona'], 'granja' => $f['granja'], 'nomGranja' => $f['nomGranja'],
                'campania' => $f['campania'], 'galpon' => $f['galpon'], 'edad' => $edadFila,
                'planificado' => [$fechaPlan], 'ejecutado' => [$fechaEje],
                'fechaMostrar' => $fechaPlan, 'tipo' => 'Planificado', 'estado' => 'SI CUMPLIO',
                'keyBase' => $keyBase, 'key' => $key,
            ];
        } elseif ($fechaEje !== null) {
            $edadFila = isset($edadPorFecha[$fechaPlan]) ? $edadPorFecha[$fechaPlan] : $ed;
            $estadoPlan = ($fechaPlan <= $hoy) ? 'NO CUMPLIO' : '';
            $numCron = (int)($plan['numCronogramaPorFecha'][$fechaPlan] ?? 0);
            $tipoPlan = ($numCron !== 0) ? 'Planificado' : 'Eventual';
            $filas[] = [
                'zona' => $f['zona'], 'subzona' => $f['subzona'], 'granja' => $f['granja'], 'nomGranja' => $f['nomGranja'],
                'campania' => $f['campania'], 'galpon' => $f['galpon'], 'edad' => $edadFila,
                'planificado' => [$fechaPlan], 'ejecutado' => [],
                'fechaMostrar' => $fechaPlan, 'tipo' => $tipoPlan, 'estado' => $estadoPlan,
                'keyBase' => $keyBase, 'key' => $key,
            ];
            $esAnomaliaEje = ($fechaEje <= $hoy) && (strcmp($fechaEje, $fechaPlan) < 0);
            $estadoEje = ($fechaEje <= $hoy) ? ($esAnomaliaEje ? 'NO CUMPLIO' : 'ATRASADO') : '';
            $row = [
                'zona' => $f['zona'], 'subzona' => $f['subzona'], 'granja' => $f['granja'], 'nomGranja' => $f['nomGranja'],
                'campania' => $f['campania'], 'galpon' => $f['galpon'], 'edad' => $ed,
                'planificado' => [], 'ejecutado' => [$fechaEje],
                'fechaMostrar' => $fechaEje, 'tipo' => '-', 'estado' => $estadoEje,
                'keyBase' => $keyBase, 'key' => $key, 'parentKey' => $key,
            ];
            if ($parentKey !== null) $row['parentKey'] = $parentKey;
            $filas[] = $row;
        } else {
            $edadFila = isset($edadPorFecha[$fechaPlan]) ? $edadPorFecha[$fechaPlan] : $ed;
            $estadoPlan = ($fechaPlan <= $hoy) ? 'NO CUMPLIO' : '';
            $numCron = (int)($plan['numCronogramaPorFecha'][$fechaPlan] ?? 0);
            $tipoPlan = ($numCron !== 0) ? 'Planificado' : 'Eventual';
            $filas[] = [
                'zona' => $f['zona'], 'subzona' => $f['subzona'], 'granja' => $f['granja'], 'nomGranja' => $f['nomGranja'],
                'campania' => $f['campania'], 'galpon' => $f['galpon'], 'edad' => $edadFila,
                'planificado' => [$fechaPlan], 'ejecutado' => [],
                'fechaMostrar' => $fechaPlan, 'tipo' => $tipoPlan, 'estado' => $estadoPlan,
                'keyBase' => $keyBase, 'key' => $key,
            ];
        }
    }
    foreach ($matchResult['ejecutadasSinMatch'] as $fechaEje) {
        $estadoEje = ($fechaEje <= $hoy) ? 'ANOMALIA' : '';
        $row = [
            'zona' => $f['zona'], 'subzona' => $f['subzona'], 'granja' => $f['granja'], 'nomGranja' => $f['nomGranja'],
            'campania' => $f['campania'], 'galpon' => $f['galpon'], 'edad' => $ed,
            'planificado' => [], 'ejecutado' => [$fechaEje],
            'fechaMostrar' => $fechaEje, 'tipo' => '-', 'estado' => $estadoEje,
            'keyBase' => $keyBase, 'key' => $key,
        ];
        if ($parentKey !== null) $row['parentKey'] = $parentKey;
        $filas[] = $row;
    }
}

if ($filtroEstado !== '') {
    if ($filtroEstado === 'no_cumplido') {
        $keysNoCumplio = [];
        foreach ($filas as $f) {
            $e = $f['estado'] ?? '';
            if ($e === 'NO CUMPLIO' || $e === 'NO CUMPLIDO') {
                $k = $f['key'] ?? '';
                if ($k !== '') $keysNoCumplio[$k] = true;
            }
        }
        $filas = array_filter($filas, function ($f) use ($keysNoCumplio) {
            $e = $f['estado'] ?? '';
            if ($e === 'NO CUMPLIO' || $e === 'NO CUMPLIDO') return true;
            if (($e === 'ATRASADO' || (($f['tipo'] ?? '') === '-' && isset($f['parentKey']))) && isset($f['parentKey']) && isset($keysNoCumplio[$f['parentKey']])) return true;
            return false;
        });
    } else {
        $filas = array_filter($filas, function ($f) use ($filtroEstado) {
            $e = $f['estado'] ?? '';
            if ($filtroEstado === 'cumplido') return in_array($e, ['SI CUMPLIO', 'CUMPLIDO', 'REALIZADO'], true);
            if ($filtroEstado === 'atrasado') return $e === 'ATRASADO';
            if ($filtroEstado === 'anomalia') return ($f['tipo'] ?? '') === '-' && ($e === 'ANOMALIA' || ($e === 'NO CUMPLIO' && isset($f['parentKey'])));
            return true;
        });
    }
    $filas = array_values($filas);
}

usort($filas, function ($a, $b) {
    $pa = $a['fechaMostrar'] ?? '';
    $pb = $b['fechaMostrar'] ?? '';
    if ($pa !== $pb) return strcmp($pa, $pb);
    if (($a['zona'] ?? '') !== ($b['zona'] ?? '')) return strcmp($a['zona'], $b['zona']);
    if (($a['subzona'] ?? '') !== ($b['subzona'] ?? '')) return strcmp($a['subzona'], $b['subzona']);
    if (($a['granja'] ?? '') !== ($b['granja'] ?? '')) return strcmp($a['granja'], $b['granja']);
    if (($a['campania'] ?? '') !== ($b['campania'] ?? '')) return strcmp($a['campania'], $b['campania']);
    if (($a['galpon'] ?? '') !== ($b['galpon'] ?? '')) return strcmp($a['galpon'], $b['galpon']);
    return strcmp((string)($a['edad'] ?? ''), (string)($b['edad'] ?? ''));
});

$ordenPropuesta = isset($_GET['orden']) ? (int)$_GET['orden'] : 1;
if ($ordenPropuesta === 2) {
    $atrasadosPorParent = [];
    foreach ($filas as $f) {
        if ((($f['estado'] ?? '') === 'ATRASADO' || (($f['tipo'] ?? '') === '-' && isset($f['parentKey']))) && isset($f['parentKey'])) {
            $pk = $f['parentKey'];
            if (!isset($atrasadosPorParent[$pk])) $atrasadosPorParent[$pk] = [];
            $atrasadosPorParent[$pk][] = $f;
        }
    }
    $filasOrden2 = [];
    foreach ($filas as $f) {
        if ((($f['estado'] ?? '') === 'ATRASADO' || (($f['tipo'] ?? '') === '-' && isset($f['parentKey']))) && isset($f['parentKey'])) continue;
        $filasOrden2[] = $f;
        $k = $f['key'] ?? '';
        if (isset($atrasadosPorParent[$k])) {
            foreach ($atrasadosPorParent[$k] as $atr) $filasOrden2[] = $atr;
        }
    }
    $filas = $filasOrden2;
}

// Si se invoca desde el reporte unificado, retornar datos sin generar PDF
if (!empty($GLOBALS['REPORTE_UNIFICADO_RETORNAR']) && $GLOBALS['REPORTE_UNIFICADO_RETORNAR'] === 'ms') {
    $GLOBALS['reporte_ms_data'] = ['filas' => $filas, 'rango' => $rango];
    return;
}

if (!function_exists('formatearFechasCortaMS')) {
    function formatearFechasCortaMS($fechas, $max = 3) {
        if (empty($fechas)) return '';
        $lista = array_slice($fechas, 0, $max);
        $txt = implode(', ', array_map(function ($d) {
            return date('d/m/Y', strtotime($d));
        }, $lista));
        if (count($fechas) > $max) $txt .= ' ...';
        return $txt;
    }
}

date_default_timezone_set('America/Lima');
$fechaReporte = date('d/m/Y H:i');
$fechaTitulo = date('d/m/Y', strtotime($rango['desde']));
if ($rango['desde'] !== $rango['hasta']) {
    $fechaTitulo = date('d/m/Y', strtotime($rango['desde'])) . ' al ' . date('d/m/Y', strtotime($rango['hasta']));
}

$tituloMS = ($tipoMS === 'MSA') ? 'MSA (Muestras adulto)' : 'MSB (Muestras edad 1)';

$logoPath = __DIR__ . '/../../../logo.png';
$logo = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logo = '<img src="data:image/png;base64,' . base64_encode($logoData) . '" style="height: 20px; vertical-align: top;">';
}
if (empty($logo) && file_exists(__DIR__ . '/../../logo.png')) {
    $logoData = file_get_contents(__DIR__ . '/../../logo.png');
    $logo = '<img src="data:image/png;base64,' . base64_encode($logoData) . '" style="height: 20px; vertical-align: top;">';
}

$bordeTitulo = 'border: 1px solid #64748b;';
$css = '
body{font-family:"Segoe UI",Arial,sans-serif;font-size:9pt;color:#1e293b;margin:0;padding:10px;}
.data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:1px solid #cbd5e1;}
.data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;}
.data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
.data-table .tipo-planificado{background:#dcfce7;}
.data-table .tipo-no-planificado{background:#e0e7ff;}
.data-table .tipo-desarrollado{background:#f1f5f9;}
.data-table .celda-vacia{color:#94a3b8;}
.grupo-fecha{background:#0f172a !important;color:#fff !important;font-weight:bold;border-top:2px solid #1e293b;}
';

$tempDir = __DIR__ . '/../../../pdf_tmp';
if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);
if (!is_dir($tempDir) || !is_writable($tempDir)) $tempDir = sys_get_temp_dir();

try {
    if (ob_get_level()) ob_clean();
    require_once __DIR__ . '/../../../vendor/autoload.php';

    $maxFilasPdf = ($periodoTipo === 'TODOS') ? 5000 : 20000;
    $filasRecortadas = false;
    if (count($filas) > $maxFilasPdf) {
        $filas = array_slice($filas, 0, $maxFilasPdf);
        $filasRecortadas = true;
    }

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 12,
        'margin_bottom' => 18,
        'tempDir' => $tempDir,
        'defaultfooterline' => 0,
        'simpleTables' => true,
        'packTableData' => true,
    ]);
    $mpdf->SetFooter('<div style="text-align:center;font-size:9pt;font-weight:normal;color:#374151;">{PAGENO} de {nbpg}</div>');
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

    $htmlCab = '<table width="100%" style="border-collapse: collapse; margin-bottom: 10px; margin-top: 8px; ' . $bordeTitulo . '">';
    $htmlCab .= '<tr><td style="width: 20%; padding: 5px; ' . $bordeTitulo . '">' . ($logo ? $logo . ' ' : '') . 'GRANJA RINCONADA DEL SUR S.A.</td>';
    $htmlCab .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px; ' . $bordeTitulo . '">REPORTE COMPARATIVO — ' . htmlspecialchars($fechaTitulo) . '</td>';
    $htmlCab .= '<td style="width: 20%; text-align: right; padding: 5px; ' . $bordeTitulo . '">' . htmlspecialchars($fechaReporte) . '</td></tr></table>';
    if ($filasRecortadas) {
        $htmlCab .= '<div style="margin:0 0 8px 0;padding:6px 8px;border:1px solid #f59e0b;background:#fffbeb;color:#92400e;font-size:8pt;">Se muestran las primeras ' . $maxFilasPdf . ' filas.</div>';
    }
    $mpdf->WriteHTML($htmlCab, \Mpdf\HTMLParserMode::HTML_BODY);

    $theadRow = '<tr><th style="width:4%;text-align:center;">N&#176;</th><th style="width:6%">Zona</th><th style="width:6%">Subzona</th><th style="width:6%">Granja</th><th style="width:11%">Nombre granja</th><th style="width:6%">Campaña</th><th style="width:5%">Galpón</th><th style="width:4%">Edad</th><th style="width:11%">Fecha planificada</th><th style="width:11%">Fecha desarrollada</th><th style="width:8%">Tipo</th><th style="width:8%">Estado</th></tr>';

    if (empty($filas)) {
        $mpdf->WriteHTML('<div style="margin-top:12px;margin-bottom:6px;font-weight:bold;font-size:11pt;color:#1e40af;">MANEJO CAMA</div><table class="data-table"><thead>' . $theadRow . '</thead><tbody><tr><td colspan="12" style="text-align:center;color:#64748b;">No hay datos para este período y filtros.</td></tr></tbody></table>', \Mpdf\HTMLParserMode::HTML_BODY);
    } else {
        $mpdf->WriteHTML('<div style="margin-top:12px;margin-bottom:6px;font-weight:bold;font-size:11pt;color:#1e40af;">MANEJO CAMA</div>', \Mpdf\HTMLParserMode::HTML_BODY);
        $chunkRows = 300;
        $buf = '';
        $n = 0;
        $fechaAnt = '';
        $mpdf->WriteHTML('<table class="data-table"><thead>' . $theadRow . '</thead><tbody>', \Mpdf\HTMLParserMode::HTML_BODY);
        foreach ($filas as $r) {
            $fechaMostrar = $r['fechaMostrar'] ?? '';
            $fechaLabel = $fechaMostrar ? date('d/m/Y', strtotime($fechaMostrar)) : '';
            if ($fechaLabel !== '' && $fechaLabel !== $fechaAnt) {
                $fechaAnt = $fechaLabel;
                $buf .= '<tr class="grupo-fecha"><td colspan="12">MANEJO CAMA ' . htmlspecialchars($fechaLabel) . '</td></tr>';
            }
            $n++;
            $esPosteriorHoy = ($fechaMostrar !== '' && strtotime($fechaMostrar) > strtotime(date('Y-m-d')));
            $vacio = $esPosteriorHoy ? '' : '<span class="celda-vacia">—</span>';
            $tipo = $r['tipo'] ?? '';
            $clase = $tipo === 'Planificado' ? 'tipo-planificado' : ($tipo === 'Eventual' ? 'tipo-eventual' : ($tipo === '-' ? 'tipo-desarrollado' : ($tipo === 'NO PLANIFICADO' ? 'tipo-no-planificado' : '')));
            $fechaPlanTxt = formatearFechasCortaMS($r['planificado'] ?? [], 3);
            $fechaDesTxt = formatearFechasCortaMS($r['ejecutado'] ?? [], 3);
            if ($tipo === 'NO PLANIFICADO') $fechaPlanTxt = '';
            $estado = $r['estado'] ?? '';
            $tipoTxt = trim($tipo) !== '' ? htmlspecialchars($tipo) : $vacio;
            $buf .= '<tr class="' . $clase . '">';
            $buf .= '<td style="text-align:center;">' . $n . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['zona']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['subzona']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['granja']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['nomGranja']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['campania']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['galpon']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['edad']) . '</td>';
            $buf .= '<td>' . ($fechaPlanTxt !== '' ? htmlspecialchars($fechaPlanTxt) : $vacio) . '</td>';
            $buf .= '<td>' . ($fechaDesTxt !== '' ? htmlspecialchars($fechaDesTxt) : $vacio) . '</td>';
            $buf .= '<td>' . $tipoTxt . '</td>';
            $buf .= '<td>' . ($estado !== '' ? htmlspecialchars($estado) : $vacio) . '</td>';
            $buf .= '</tr>';
            if ($n % $chunkRows === 0) {
                $mpdf->WriteHTML($buf, \Mpdf\HTMLParserMode::HTML_BODY);
                $buf = '';
            }
        }
        if ($buf !== '') $mpdf->WriteHTML($buf, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->WriteHTML('</tbody></table>', \Mpdf\HTMLParserMode::HTML_BODY);
    }
    $mpdf->Output('reporte_comparativo_' . $tipoMS . '_' . $rango['desde'] . '_' . $rango['hasta'] . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
