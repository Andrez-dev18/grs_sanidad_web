<?php
/**
 * Reporte comparativo Planificado vs Desarrollado para tipos con datos en movi_zonas.
 * Tipos soportados: VACUNA GJA (GJ/VGJ), VACUNA Pl (Pl/VPI), Control de plagas (CP/CDP),
 * Limpieza y desinfección (LD/LYD), Manejo cama (MC/MDC).
 *
 * Configuración movi_zonas por tipo (ajustar tline/tcodtra según su BD):
 * - VACUNA GJA: ccios, tline='003', tcodtra='S003'
 * - VACUNA Pl: ccos, tline='004', tcodtra='S003'
 * - CP: tline y tcodtra por definir (usar valores de su sistema)
 * - LD: tline y tcodtra por definir
 * - MC: tline y tcodtra por definir
 */
@ini_set('pcre.backtrack_limit', 10000000);
@ini_set('pcre.recursion_limit', 10000000);
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['active'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('No autorizado');
}
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '0');
@set_time_limit(0);

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
if (isset($_GET['galpon']) && is_array($_GET['galpon'])) {
    foreach ($_GET['galpon'] as $v) {
        $v = trim((string)$v);
        if ($v !== '') $filtroGalpones[] = $v;
    }
} elseif (!empty($_GET['galpon'])) {
    $v = trim((string)$_GET['galpon']);
    if ($v !== '') $filtroGalpones[] = $v;
}
$filtroCampanias = [];
if (isset($_GET['campania']) && is_array($_GET['campania'])) {
    foreach ($_GET['campania'] as $v) {
        $v = trim((string)$v);
        if ($v !== '') $filtroCampanias[] = $v;
    }
} elseif (!empty($_GET['campania'])) {
    $v = trim((string)$_GET['campania']);
    if ($v !== '') $filtroCampanias[] = $v;
}
$filtroEdades = [];
if (isset($_GET['edad']) && is_array($_GET['edad'])) {
    foreach ($_GET['edad'] as $v) {
        $v = trim((string)$v);
        if ($v !== '') $filtroEdades[] = $v;
    }
} elseif (!empty($_GET['edad'])) {
    $v = trim((string)$_GET['edad']);
    if ($v !== '') $filtroEdades[] = $v;
}
$filtroEstado = trim((string)($_GET['estado'] ?? ''));
$filtroCategoria = trim((string)($_GET['categoria'] ?? ''));

$tipoProgramaIds = [];
if (isset($_GET['tipoPrograma']) && is_array($_GET['tipoPrograma'])) {
    foreach ($_GET['tipoPrograma'] as $v) {
        $v = trim((string)$v);
        if ($v !== '') $tipoProgramaIds[] = $v;
    }
}

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
if (!function_exists('match_plan_eje_con_tolerancia')) {
    function match_plan_eje_con_tolerancia($fechasPlan, $fechasEje, $tol) {
        $tol = max(1, (int)$tol);
        $fechasPlan = array_values(array_unique($fechasPlan));
        $fechasEje = array_values(array_unique($fechasEje));
        sort($fechasPlan);
        sort($fechasEje);
        $interseccion = [];
        $pares = [];
        $usadas = [];
        foreach ($fechasPlan as $fp) {
            $tsPlan = strtotime($fp);
            $candidatos = [];
            foreach ($fechasEje as $i => $fe) {
                if (isset($usadas[$i])) continue;
                $tsEje = strtotime($fe);
                $dias = (int)round(($tsEje - $tsPlan) / 86400);
                if ($dias >= -$tol && $dias <= $tol) $candidatos[] = ['i' => $i, 'fecha' => $fe, 'dias' => $dias, 'abs' => abs($dias)];
            }
            if (!empty($candidatos)) {
                usort($candidatos, function ($a, $b) { return $a['abs'] - $b['abs']; });
                $mejor = $candidatos[0];
                $usadas[$mejor['i']] = true;
                $interseccion[] = $mejor['fecha'];
                $pares[] = ['atrasado' => $mejor['dias'] > 0];
            }
        }
        $estado = '';
        $fechaMostrar = '';
        if (count($interseccion) > 0) {
            $fechaMostrar = $interseccion[0];
            $algunaAtrasada = false;
            foreach ($pares as $p) { if ($p['atrasado']) { $algunaAtrasada = true; break; } }
            $estado = $algunaAtrasada ? 'ATRASADO' : 'CUMPLIDO';
        }
        return ['interseccion' => $interseccion, 'estado' => $estado, 'fechaMostrar' => $fechaMostrar];
    }
}

include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    exit('Error de conexión');
}
include_once __DIR__ . '/comparativo_unificado_util.php';

// Mapeo: sigla -> [patrones codPrograma, tline, tcodtra, joinTable]
$configMovi = [
    'GJ' => [['GJ%', 'VGJ%'], '003', 'S003', 'ccios'],
    'VGJ' => [['GJ%', 'VGJ%'], '003', 'S003', 'ccios'],
    'PL' => [['PL%', 'VPI%'], '004', 'S003', 'ccos'],
    'VPI' => [['PL%', 'VPI%'], '004', 'S003', 'ccos'],
    'CP' => [['CP%', 'CDP%'], '005', 'S003', 'ccos'],
    'CDP' => [['CP%', 'CDP%'], '005', 'S003', 'ccos'],
    'LD' => [['LD%', 'LYD%'], '006', 'S003', 'ccos'],
    'LYD' => [['LD%', 'LYD%'], '006', 'S003', 'ccos'],
    'MC' => [['MC%', 'MDC%'], '007', 'S003', 'ccos'],
    'MDC' => [['MC%', 'MDC%'], '007', 'S003', 'ccos'],
];
// Grupos por tipo de programa (nombre => siglas que lo componen)
$tipoGrupos = [
    'VACUNA GJA' => ['GJ', 'VGJ'],
    'VACUNA Pl' => ['PL', 'VPI'],
    'Control de plagas' => ['CP', 'CDP'],
    'Limpieza y desinfección' => ['LD', 'LYD'],
    'Manejo cama' => ['MC', 'MDC'],
];

$siglasActivas = [];
if (count($tipoProgramaIds) > 0) {
    $ph = implode(',', array_fill(0, count($tipoProgramaIds), '?'));
    $stTipos = $conn->prepare("SELECT codigo, UPPER(TRIM(COALESCE(sigla,''))) AS sigla FROM san_dim_tipo_programa WHERE codigo IN ($ph)");
    if ($stTipos) {
        $typesTipos = str_repeat('s', count($tipoProgramaIds));
        $stTipos->bind_param($typesTipos, ...$tipoProgramaIds);
        $stTipos->execute();
        $resTipos = $stTipos->get_result();
        while ($row = $resTipos->fetch_assoc()) {
            $sigla = trim((string)($row['sigla'] ?? ''));
            if ($sigla !== '' && isset($configMovi[$sigla])) {
                $siglasActivas[$sigla] = $configMovi[$sigla];
            }
        }
        $stTipos->close();
    }
}
if (count($siglasActivas) === 0) {
    $stTipos = $conn->query("SELECT codigo, UPPER(TRIM(COALESCE(sigla,''))) AS sigla FROM san_dim_tipo_programa");
    if ($stTipos) {
        while ($row = $stTipos->fetch_assoc()) {
            $sigla = trim((string)($row['sigla'] ?? ''));
            if ($sigla !== '' && isset($configMovi[$sigla])) {
                $siglasActivas[$sigla] = $configMovi[$sigla];
            }
        }
    }
}
if (count($siglasActivas) === 0) {
    exit('No hay tipos de programa configurados para este reporte.');
}

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
$CHUNK_SIZE = 5000;
$chkNumCronograma = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
$tieneNumCronograma = $chkNumCronograma && $chkNumCronograma->num_rows > 0;
$chkTipoCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'tipo'");
$tieneTipoCab = $chkTipoCab && $chkTipoCab->num_rows > 0;
$chkRegnocontable = @$conn->query("SHOW TABLES LIKE 'regnocontable'");
$tieneRegnocontable = $chkRegnocontable && $chkRegnocontable->num_rows > 0;
$chkCcos = @$conn->query("SHOW TABLES LIKE 'ccos'");
$chkCcios = @$conn->query("SHOW TABLES LIKE 'ccios'");
$tieneCcos = $chkCcos && $chkCcos->num_rows > 0;
$tieneCcios = $chkCcios && $chkCcios->num_rows > 0;

$filasPorTipo = [];
$metaPorGranja = [];

foreach ($tipoGrupos as $nombreTipo => $siglasGrupo) {
    $tieneSigla = false;
    foreach ($siglasGrupo as $s) {
        if (isset($siglasActivas[$s])) { $tieneSigla = true; break; }
    }
    if (!$tieneSigla) continue;

    $cfg = $configMovi[$siglasGrupo[0]];
    $patrones = $cfg[0];
    $condicionesCod = [];
    foreach ($patrones as $pat) {
        $condicionesCod[] = 'UPPER(TRIM(c.codPrograma)) LIKE \'' . str_replace('%', '', $pat) . '%\'';
    }
    $whereCodPrograma = ' (' . implode(' OR ', $condicionesCod) . ')';

    // Códigos de este tipo para esEspecial (key = granja|campania|galpon o granja si especial)
    $codigosTipo = [];
    foreach ($patrones as $pat) {
        $like = str_replace("'", "''", trim($pat)) . '%';
        $r = @$conn->query("SELECT codigo FROM san_fact_programa_cab WHERE UPPER(TRIM(codigo)) LIKE UPPER('" . $like . "')");
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $cod = trim((string)($row['codigo'] ?? ''));
                if ($cod !== '') $codigosTipo[$cod] = true;
            }
        }
    }
    $codigosTipo = array_keys($codigosTipo);
    $esEspecialPorCod = comparativo_cargar_es_especial($conn, $codigosTipo);
    $usarKeyEspecial = !empty($esEspecialPorCod) && max(array_values($esEspecialPorCod)) === 1;

    // Para Control de plagas (CP): programas con tipo ROEDORES/GORGOJOS/INSECTOS usan regnocontable como desarrollado
    $codigosCPRoedores = [];
    $codigosCPGorgojos = [];
    $esControlPlagas = ($nombreTipo === 'Control de plagas');
    if ($esControlPlagas && $tieneTipoCab) {
        $stCab = $conn->prepare("SELECT codigo, UPPER(TRIM(COALESCE(tipo,''))) AS tipo FROM san_fact_programa_cab WHERE UPPER(TRIM(codigo)) LIKE 'CP%' OR UPPER(TRIM(codigo)) LIKE 'CDP%'");
        if ($stCab) {
            $stCab->execute();
            $resCab = $stCab->get_result();
            while ($r = $resCab->fetch_assoc()) {
                $tipo = trim((string)($r['tipo'] ?? ''));
                $cod = trim((string)($r['codigo'] ?? ''));
                if ($cod === '') continue;
                if ($tipo === 'ROEDORES') $codigosCPRoedores[] = $cod;
                if ($tipo === 'GORGOJOS' || $tipo === 'INSECTOS') $codigosCPGorgojos[] = $cod;
            }
            $stCab->close();
        }
    }

    $chkToleranciaCrono = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'tolerancia'");
    $chkCategoriaCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'categoria'");
    $tieneToleranciaCrono = $chkToleranciaCrono && $chkToleranciaCrono->num_rows > 0;
    $tieneCategoriaCab = $chkCategoriaCab && $chkCategoriaCab->num_rows > 0;
    $joinCategoriaCrono = ($filtroCategoria !== '' && $tieneCategoriaCab) ? " LEFT JOIN san_fact_programa_cab cab ON TRIM(cab.codigo) = TRIM(c.codPrograma)" : "";
    $sqlCronoBase = "SELECT DATE(c.fechaEjecucion) AS fecha_ref, c.granja, c.campania, c.galpon, c.codPrograma"
        . ($tieneEdad ? ", c.edad" : "")
        . ($tieneNomGranja ? ", c.nomGranja" : "")
        . ($tieneZona ? ", c.zona" : "")
        . ($tieneSubzona ? ", c.subzona" : "")
        . ($tieneNumCronograma ? ", COALESCE(c.numCronograma, 0) AS numCronograma" : ", 0 AS numCronograma")
        . ($tieneToleranciaCrono ? ", COALESCE(NULLIF(c.tolerancia, 0), 1) AS tolerancia" : ", 1 AS tolerancia")
        . " FROM san_fact_cronograma c" . $joinCategoriaCrono;
    $whereCrono = [$whereCodPrograma];
    if ($tieneNumCronograma) {
        $whereCrono[] = " (c.numCronograma >= 0 OR c.numCronograma IS NULL)";
    }
    $paramsCrono = [];
    $typesCrono = '';
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
    if (count($filtroEdades) > 0) {
        $ph = implode(',', array_fill(0, count($filtroEdades), '?'));
        $whereCrono[] = " CAST(TRIM(c.edad) AS UNSIGNED) IN ($ph)";
        foreach ($filtroEdades as $fe) { $paramsCrono[] = $fe; $typesCrono .= 's'; }
    }
    if ($filtroCategoria !== '' && $tieneCategoriaCab) {
        $whereCrono[] = " TRIM(cab.categoria) = ?";
        $paramsCrono[] = $filtroCategoria;
        $typesCrono .= 's';
    }

    $planificadosPorKey = [];
    $offsetCrono = 0;
    do {
        $sqlCrono = $sqlCronoBase . " WHERE" . implode(" AND", $whereCrono);
        $sqlCrono .= " ORDER BY c.granja, c.campania, c.galpon, c.fechaEjecucion LIMIT " . (int)$CHUNK_SIZE . " OFFSET " . (int)$offsetCrono;
        $stmtCrono = $conn->prepare($sqlCrono);
        if (!$stmtCrono) break;
        if (count($paramsCrono) > 0) $stmtCrono->bind_param($typesCrono, ...$paramsCrono);
        $stmtCrono->execute();
        $resCrono = $stmtCrono->get_result();
        $rowsChunk = 0;
        while ($row = $resCrono->fetch_assoc()) {
            $g = norm_granja_3($row['granja'] ?? '');
            $c = norm_campania_3($row['campania'] ?? '');
            $gp = norm_num_text($row['galpon'] ?? '');
            $e = $tieneEdad ? norm_num_text($row['edad'] ?? '') : '';
            $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
            $codPrograma = trim((string)($row['codPrograma'] ?? ''));
            $key = comparativo_build_key($g, $c, $gp, $codPrograma, $esEspecialPorCod, $e, ($nombreTipo === 'Vacunas'));
            $nomGranja = $tieneNomGranja ? trim((string)($row['nomGranja'] ?? '')) : '';
            $zona = $tieneZona ? trim((string)($row['zona'] ?? '')) : '';
            $subzona = $tieneSubzona ? trim((string)($row['subzona'] ?? '')) : '';
            $numCron = (int)($row['numCronograma'] ?? 0);
            if (!isset($planificadosPorKey[$key])) {
                $planificadosPorKey[$key] = ['granja' => $g, 'campania' => $c, 'galpon' => $gp, 'edad' => $e, 'codPrograma' => $codPrograma, 'nomGranja' => $nomGranja, 'zona' => $zona, 'subzona' => $subzona, 'fechas' => [], 'toleranciaPorFecha' => [], 'edadPorFecha' => [], 'numCronogramaPorFecha' => []];
            } else {
                if ($planificadosPorKey[$key]['codPrograma'] === '' && $codPrograma !== '') $planificadosPorKey[$key]['codPrograma'] = $codPrograma;
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
            $rowsChunk++;
        }
        $stmtCrono->close();
        $offsetCrono += $CHUNK_SIZE;
    } while ($rowsChunk >= $CHUNK_SIZE);

    $ejecutadosPorKey = [];
    if ($tieneNumCronograma) {
    $usarRegnocontableParaCP = $esControlPlagas && $tieneTipoCab && $tieneRegnocontable && (count($codigosCPRoedores) > 0 || count($codigosCPGorgojos) > 0);
    $joinCabDes = $usarRegnocontableParaCP ? " LEFT JOIN san_fact_programa_cab cab ON cab.codigo = c.codPrograma " : "";
    if ($filtroCategoria !== '' && $tieneCategoriaCab && $joinCabDes === '') {
        $joinCabDes = " LEFT JOIN san_fact_programa_cab cab ON TRIM(cab.codigo) = TRIM(c.codPrograma) ";
    }
    $whereDesarrollado = [$whereCodPrograma, " c.numCronograma = 0"];
    if ($usarRegnocontableParaCP) {
        $whereDesarrollado[] = " (cab.tipo IS NULL OR UPPER(TRIM(COALESCE(cab.tipo,''))) NOT IN ('ROEDORES','GORGOJOS','INSECTOS'))";
    }
    $sqlDesarrolladoBase = "SELECT DATE(c.fechaEjecucion) AS fecha_ref, c.granja, c.campania, c.galpon, c.codPrograma"
        . ($tieneEdad ? ", c.edad" : "")
        . ($tieneNomGranja ? ", c.nomGranja" : "")
        . ($tieneZona ? ", c.zona" : "")
        . ($tieneSubzona ? ", c.subzona" : "")
        . " FROM san_fact_cronograma c" . $joinCabDes;
    $paramsDesarrollado = [];
    $typesDesarrollado = '';
    if (!$esTodos) {
        $whereDesarrollado[] = " DATE(c.fechaEjecucion) >= ? AND DATE(c.fechaEjecucion) <= ?";
        $paramsDesarrollado[] = $rango['desde'];
        $paramsDesarrollado[] = $rango['hasta'];
        $typesDesarrollado .= 'ss';
    }
    if (count($filtroGranjasNorm) > 0) {
        $phG = implode(',', array_fill(0, count($filtroGranjasNorm), '?'));
        $whereDesarrollado[] = " LEFT(TRIM(c.granja), 3) IN ($phG)";
        foreach ($filtroGranjasNorm as $gN) { $paramsDesarrollado[] = $gN; $typesDesarrollado .= 's'; }
    }
    if (count($filtroGalpones) > 0) {
        $phGp = implode(',', array_fill(0, count($filtroGalpones), '?'));
        $whereDesarrollado[] = " TRIM(c.galpon) IN ($phGp)";
        foreach ($filtroGalpones as $fgp) { $paramsDesarrollado[] = $fgp; $typesDesarrollado .= 's'; }
    }
    if (count($filtroCampanias) > 0) {
        $ph = implode(',', array_fill(0, count($filtroCampanias), '?'));
        $whereDesarrollado[] = " TRIM(c.campania) IN ($ph)";
        foreach ($filtroCampanias as $fc) { $paramsDesarrollado[] = $fc; $typesDesarrollado .= 's'; }
    }
    if (count($filtroEdades) > 0 && $tieneEdad) {
        $ph = implode(',', array_fill(0, count($filtroEdades), '?'));
        $whereDesarrollado[] = " CAST(TRIM(c.edad) AS UNSIGNED) IN ($ph)";
        foreach ($filtroEdades as $fe) { $paramsDesarrollado[] = $fe; $typesDesarrollado .= 's'; }
    }
    if ($filtroCategoria !== '' && $tieneCategoriaCab) {
        $whereDesarrollado[] = " TRIM(cab.categoria) = ?";
        $paramsDesarrollado[] = $filtroCategoria;
        $typesDesarrollado .= 's';
    }
    $offsetDes = 0;
    do {
        $sqlDes = $sqlDesarrolladoBase . " WHERE" . implode(" AND", $whereDesarrollado);
        $sqlDes .= " ORDER BY c.granja, c.campania, c.galpon, c.fechaEjecucion LIMIT " . (int)$CHUNK_SIZE . " OFFSET " . (int)$offsetDes;
        $stmtDes = $conn->prepare($sqlDes);
        if (count($paramsDesarrollado) > 0) $stmtDes->bind_param($typesDesarrollado, ...$paramsDesarrollado);
        $stmtDes->execute();
        $resDes = $stmtDes->get_result();
        $rowsChunk = 0;
        while ($row = $resDes->fetch_assoc()) {
            $g = norm_granja_3($row['granja'] ?? '');
            $c = norm_campania_3($row['campania'] ?? '');
            $gp = norm_num_text($row['galpon'] ?? '');
            $e = $tieneEdad ? norm_num_text($row['edad'] ?? '') : '';
            $codProgramaDes = trim((string)($row['codPrograma'] ?? $codigosTipo[0] ?? ''));
            $key = comparativo_build_key($g, $c, $gp, $codProgramaDes ?: ($codigosTipo[0] ?? ''), $esEspecialPorCod, $e, ($nombreTipo === 'Vacunas'));
            $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
            $nomGranja = $tieneNomGranja ? trim((string)($row['nomGranja'] ?? '')) : '';
            $zona = $tieneZona ? trim((string)($row['zona'] ?? '')) : '';
            $subzona = $tieneSubzona ? trim((string)($row['subzona'] ?? '')) : '';
            if (!isset($ejecutadosPorKey[$key])) {
                $ejecutadosPorKey[$key] = ['granja' => $g, 'campania' => $c, 'galpon' => $gp, 'edad' => $e, 'fechas' => [], 'nomGranja' => $nomGranja, 'zona' => $zona, 'subzona' => $subzona];
            }
            if ($fechaRef && !in_array($fechaRef, $ejecutadosPorKey[$key]['fechas'], true)) {
                $ejecutadosPorKey[$key]['fechas'][] = $fechaRef;
            }
            if ($nomGranja !== '' && ($ejecutadosPorKey[$key]['nomGranja'] ?? '') === '') $ejecutadosPorKey[$key]['nomGranja'] = $nomGranja;
            if ($zona !== '' && ($ejecutadosPorKey[$key]['zona'] ?? '') === '') $ejecutadosPorKey[$key]['zona'] = $zona;
            if ($subzona !== '' && ($ejecutadosPorKey[$key]['subzona'] ?? '') === '') $ejecutadosPorKey[$key]['subzona'] = $subzona;
            if (!isset($metaPorGranja[$g])) $metaPorGranja[$g] = ['nomGranja' => $nomGranja, 'zona' => $zona, 'subzona' => $subzona];
            $rowsChunk++;
        }
        $stmtDes->close();
        $offsetDes += $CHUNK_SIZE;
    } while ($rowsChunk >= $CHUNK_SIZE);
    }

    // CP con tipo ROEDORES: desarrollado desde regnocontable tproceso='PLAGAS' (tcencos=granja+campaña, tcodint=galpón, tfectra=fecha).
    // Se ejecuta siempre que sea Control de plagas y existan columnas, para que aparezcan registros aunque no haya programa_cab con tipo ROEDORES.
    if ($esControlPlagas && $tieneRegnocontable) {
        $chkPlagas = @$conn->query("SHOW COLUMNS FROM regnocontable LIKE 'tproceso'");
        $chkTfectra = @$conn->query("SHOW COLUMNS FROM regnocontable LIKE 'tfectra'");
        if ($chkPlagas && $chkPlagas->num_rows > 0 && $chkTfectra && $chkTfectra->num_rows > 0) {
            $sqlReg = "SELECT DISTINCT DATE(r.tfectra) AS fecha_ref, LEFT(TRIM(r.tcencos), 3) AS granja, RIGHT(TRIM(r.tcencos), 3) AS campania, TRIM(r.tcodint) AS galpon FROM regnocontable r WHERE UPPER(TRIM(r.tproceso)) = 'PLAGAS' AND LENGTH(TRIM(r.tcencos)) >= 6 AND TRIM(r.tcodint) <> '' AND TRIM(r.tcodint) <> '0'";
            $paramsReg = [];
            $typesReg = '';
            if (!$esTodos) {
                $sqlReg .= " AND DATE(r.tfectra) >= ? AND DATE(r.tfectra) <= ?";
                $paramsReg[] = $rango['desde'];
                $paramsReg[] = $rango['hasta'];
                $typesReg .= 'ss';
            }
            if (count($filtroGranjasNorm) > 0) {
                $phG = implode(',', array_fill(0, count($filtroGranjasNorm), '?'));
                $sqlReg .= " AND LEFT(TRIM(r.tcencos), 3) IN ($phG)";
                foreach ($filtroGranjasNorm as $gN) { $paramsReg[] = $gN; $typesReg .= 's'; }
            }
            if (count($filtroGalpones) > 0) {
                $phGp = implode(',', array_fill(0, count($filtroGalpones), '?'));
                $sqlReg .= " AND TRIM(r.tcodint) IN ($phGp)";
                foreach ($filtroGalpones as $fgp) { $paramsReg[] = $fgp; $typesReg .= 's'; }
            }
            if (count($filtroCampanias) > 0) {
                $ph = implode(',', array_fill(0, count($filtroCampanias), '?'));
                $sqlReg .= " AND RIGHT(TRIM(r.tcencos), 3) IN ($ph)";
                foreach ($filtroCampanias as $fc) { $paramsReg[] = $fc; $typesReg .= 's'; }
            }
            $sqlReg .= " ORDER BY granja, campania, galpon, fecha_ref";
            $stReg = $conn->prepare($sqlReg);
            if ($stReg && count($paramsReg) > 0) $stReg->bind_param($typesReg, ...$paramsReg);
            if ($stReg && $stReg->execute()) {
                $resReg = $stReg->get_result();
                while ($row = $resReg->fetch_assoc()) {
                    $g = norm_granja_3($row['granja'] ?? '');
                    $c = norm_campania_3($row['campania'] ?? '');
                    $gp = norm_num_text($row['galpon'] ?? '');
                    $key = $usarKeyEspecial ? $g : ($g . '|' . $c . '|' . $gp);
                    $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
                    if (!isset($ejecutadosPorKey[$key])) {
                        $ejecutadosPorKey[$key] = ['granja' => $g, 'campania' => $c, 'galpon' => $gp, 'edad' => '', 'fechas' => [], 'nomGranja' => '', 'zona' => '', 'subzona' => ''];
                    }
                    if ($fechaRef && !in_array($fechaRef, $ejecutadosPorKey[$key]['fechas'], true)) {
                        $ejecutadosPorKey[$key]['fechas'][] = $fechaRef;
                    }
                }
                $stReg->close();
            }
        }
    }

    // CP con tipo GORGOJOS/INSECTOS: desarrollado desde regnocontable tproceso='EVA_GORGOJO' (tfec_ini, tcencos, tcodint)
    if ($esControlPlagas && $tieneRegnocontable && count($codigosCPGorgojos) > 0) {
        $chkEva = @$conn->query("SHOW COLUMNS FROM regnocontable LIKE 'tproceso'");
        $chkTfecIni = @$conn->query("SHOW COLUMNS FROM regnocontable LIKE 'tfec_ini'");
        if ($chkEva && $chkEva->num_rows > 0 && $chkTfecIni && $chkTfecIni->num_rows > 0) {
            $sqlReg = "SELECT DISTINCT DATE(r.tfec_ini) AS fecha_ref, LEFT(TRIM(r.tcencos), 3) AS granja, RIGHT(TRIM(r.tcencos), 3) AS campania, TRIM(r.tcodint) AS galpon FROM regnocontable r WHERE UPPER(TRIM(r.tproceso)) = 'EVA_GORGOJO' AND LENGTH(TRIM(r.tcencos)) >= 6 AND TRIM(r.tcodint) <> ''";
            $paramsReg = [];
            $typesReg = '';
            if (!$esTodos) {
                $sqlReg .= " AND DATE(r.tfec_ini) >= ? AND DATE(r.tfec_ini) <= ?";
                $paramsReg[] = $rango['desde'];
                $paramsReg[] = $rango['hasta'];
                $typesReg .= 'ss';
            }
            if (count($filtroGranjasNorm) > 0) {
                $phG = implode(',', array_fill(0, count($filtroGranjasNorm), '?'));
                $sqlReg .= " AND LEFT(TRIM(r.tcencos), 3) IN ($phG)";
                foreach ($filtroGranjasNorm as $gN) { $paramsReg[] = $gN; $typesReg .= 's'; }
            }
            if (count($filtroGalpones) > 0) {
                $phGp = implode(',', array_fill(0, count($filtroGalpones), '?'));
                $sqlReg .= " AND TRIM(r.tcodint) IN ($phGp)";
                foreach ($filtroGalpones as $fgp) { $paramsReg[] = $fgp; $typesReg .= 's'; }
            }
            if (count($filtroCampanias) > 0) {
                $ph = implode(',', array_fill(0, count($filtroCampanias), '?'));
                $sqlReg .= " AND RIGHT(TRIM(r.tcencos), 3) IN ($ph)";
                foreach ($filtroCampanias as $fc) { $paramsReg[] = $fc; $typesReg .= 's'; }
            }
            $sqlReg .= " ORDER BY granja, campania, galpon, fecha_ref";
            $stReg = $conn->prepare($sqlReg);
            if ($stReg && count($paramsReg) > 0) $stReg->bind_param($typesReg, ...$paramsReg);
            if ($stReg && $stReg->execute()) {
                $resReg = $stReg->get_result();
                while ($row = $resReg->fetch_assoc()) {
                    $g = norm_granja_3($row['granja'] ?? '');
                    $c = norm_campania_3($row['campania'] ?? '');
                    $gp = norm_num_text($row['galpon'] ?? '');
                    $key = $usarKeyEspecial ? $g : ($g . '|' . $c . '|' . $gp);
                    $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
                    if (!isset($ejecutadosPorKey[$key])) {
                        $ejecutadosPorKey[$key] = ['granja' => $g, 'campania' => $c, 'galpon' => $gp, 'edad' => '', 'fechas' => [], 'nomGranja' => '', 'zona' => '', 'subzona' => ''];
                    }
                    if ($fechaRef && !in_array($fechaRef, $ejecutadosPorKey[$key]['fechas'], true)) {
                        $ejecutadosPorKey[$key]['fechas'][] = $fechaRef;
                    }
                }
                $stReg->close();
            }
        }
    }

    // Conjunto de claves único (reutilizado para enriquecer y para filas)
    $clavesUnion = array_values(array_unique(array_merge(array_keys($planificadosPorKey), array_keys($ejecutadosPorKey))));
    // Enriquecer metaPorGranja con zona, subzona, nomGranja desde pi_dim_detalles y regcencosgalpones/ccos cuando falten
    $granjasParaEnriquecer = [];
    foreach ($clavesUnion as $key) {
        $g = (strpos($key, '|') !== false) ? explode('|', $key)[0] : $key;
        if ($g === '') continue;
        $meta = $metaPorGranja[$g] ?? ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
        if (trim($meta['zona'] ?? '') === '' || trim($meta['subzona'] ?? '') === '' || trim($meta['nomGranja'] ?? '') === '') {
            $granjasParaEnriquecer[$g] = true;
        }
    }
    $granjasParaEnriquecer = array_keys($granjasParaEnriquecer);
    if (count($granjasParaEnriquecer) > 0) {
        $chkPi = @$conn->query("SHOW TABLES LIKE 'pi_dim_detalles'");
        $chkCar = @$conn->query("SHOW TABLES LIKE 'pi_dim_caracteristicas'");
        $chkReg = @$conn->query("SHOW TABLES LIKE 'regcencosgalpones'");
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
                $typesZs = str_repeat('s', count($granjasParaEnriquecer));
                $stZs->bind_param($typesZs, ...$granjasParaEnriquecer);
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
        if ($chkReg && $chkReg->num_rows > 0) {
            $ph = implode(',', array_fill(0, count($granjasParaEnriquecer), '?'));
            $stNom = $conn->prepare("SELECT LEFT(TRIM(tcencos), 3) AS codigo, MAX(TRIM(tnomcen)) AS nombre FROM regcencosgalpones WHERE TRIM(tcencos) <> '' AND LEFT(TRIM(tcencos), 3) IN ($ph) GROUP BY LEFT(TRIM(tcencos), 3)");
            if ($stNom) {
                $stNom->bind_param(str_repeat('s', count($granjasParaEnriquecer)), ...$granjasParaEnriquecer);
                $stNom->execute();
                $resNom = $stNom->get_result();
                while ($rowNom = $resNom->fetch_assoc()) {
                    $cg = trim((string)($rowNom['codigo'] ?? ''));
                    $nom = trim((string)($rowNom['nombre'] ?? ''));
                    if ($cg !== '' && $nom !== '') {
                        if (!isset($metaPorGranja[$cg])) $metaPorGranja[$cg] = ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
                        if (($metaPorGranja[$cg]['nomGranja'] ?? '') === '') $metaPorGranja[$cg]['nomGranja'] = $nom;
                    }
                }
                $stNom->close();
            }
        } elseif ($tieneCcos) {
            $ph = implode(',', array_fill(0, count($granjasParaEnriquecer), '?'));
            $chkNomCcos = @$conn->query("SHOW COLUMNS FROM ccos LIKE 'nombre'");
            if ($chkNomCcos && $chkNomCcos->num_rows > 0) {
                $stNom = $conn->prepare("SELECT LEFT(TRIM(codigo), 3) AS codigo, MAX(TRIM(nombre)) AS nombre FROM ccos WHERE TRIM(codigo) <> '' AND LEFT(TRIM(codigo), 3) IN ($ph) GROUP BY LEFT(TRIM(codigo), 3)");
                if ($stNom) {
                    $stNom->bind_param(str_repeat('s', count($granjasParaEnriquecer)), ...$granjasParaEnriquecer);
                    $stNom->execute();
                    $resNom = $stNom->get_result();
                    while ($rowNom = $resNom->fetch_assoc()) {
                        $cg = trim((string)($rowNom['codigo'] ?? ''));
                        $nom = trim((string)($rowNom['nombre'] ?? ''));
                        if ($cg !== '' && $nom !== '') {
                            if (!isset($metaPorGranja[$cg])) $metaPorGranja[$cg] = ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
                            if (($metaPorGranja[$cg]['nomGranja'] ?? '') === '') $metaPorGranja[$cg]['nomGranja'] = $nom;
                        }
                    }
                    $stNom->close();
                }
            }
        } elseif ($tieneCcios) {
            $chkNomCcios = @$conn->query("SHOW COLUMNS FROM ccios LIKE 'nombre'");
            if ($chkNomCcios && $chkNomCcios->num_rows > 0) {
                $ph = implode(',', array_fill(0, count($granjasParaEnriquecer), '?'));
                $stNom = $conn->prepare("SELECT LEFT(TRIM(codigo), 3) AS codigo, MAX(TRIM(nombre)) AS nombre FROM ccios WHERE TRIM(codigo) <> '' AND LEFT(TRIM(codigo), 3) IN ($ph) GROUP BY LEFT(TRIM(codigo), 3)");
                if ($stNom) {
                    $stNom->bind_param(str_repeat('s', count($granjasParaEnriquecer)), ...$granjasParaEnriquecer);
                    $stNom->execute();
                    $resNom = $stNom->get_result();
                    while ($rowNom = $resNom->fetch_assoc()) {
                        $cg = trim((string)($rowNom['codigo'] ?? ''));
                        $nom = trim((string)($rowNom['nombre'] ?? ''));
                        if ($cg !== '' && $nom !== '') {
                            if (!isset($metaPorGranja[$cg])) $metaPorGranja[$cg] = ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
                            if (($metaPorGranja[$cg]['nomGranja'] ?? '') === '') $metaPorGranja[$cg]['nomGranja'] = $nom;
                        }
                    }
                    $stNom->close();
                }
            }
        }
    }

    $todasLasClaves = $clavesUnion;
    $filasPorKey = [];
    foreach ($todasLasClaves as $key) {
        $plan = isset($planificadosPorKey[$key]) ? $planificadosPorKey[$key] : null;
        $eje = isset($ejecutadosPorKey[$key]) ? $ejecutadosPorKey[$key] : null;
        $g = $plan ? $plan['granja'] : $eje['granja'];
        $c = $plan ? $plan['campania'] : $eje['campania'];
        $gp = $plan ? $plan['galpon'] : $eje['galpon'];
        $ed = $plan ? $plan['edad'] : $eje['edad'];
        $fechasPlan = $plan ? $plan['fechas'] : [];
        $fechasEje = $eje ? $eje['fechas'] : [];
        // CP con tipo ROEDORES/GORGOJOS: desarrollado viene por key sin edad (g|c|gp|''); fusionar
        $keySinEdad = $g . '|' . $c . '|' . $gp . '|';
        if (isset($ejecutadosPorKey[$keySinEdad]) && !empty($ejecutadosPorKey[$keySinEdad]['fechas'])) {
            $fechasEje = array_values(array_unique(array_merge($fechasEje, $ejecutadosPorKey[$keySinEdad]['fechas'])));
        }
        sort($fechasPlan);
        sort($fechasEje);
        $toleranciaPorFecha = $plan && isset($plan['toleranciaPorFecha']) ? $plan['toleranciaPorFecha'] : [];
        $matchResult = match_plan_eje_con_tolerancia_por_fecha($fechasPlan, $toleranciaPorFecha, $fechasEje);
        $interseccion = $matchResult['interseccion'];
        $estadoMatch = $matchResult['estado'];
        $fechaMostrarMatch = $matchResult['fechaMostrar'];
        $metaG = $metaPorGranja[$g] ?? ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
        $nomGranja = trim((string)($plan['nomGranja'] ?? $metaG['nomGranja'] ?? ''));
        if ($nomGranja === '' && $eje && !empty($eje['nomGranja'])) $nomGranja = trim((string)$eje['nomGranja']);
        $zona = trim((string)($plan['zona'] ?? $metaG['zona'] ?? ''));
        $subzona = trim((string)($plan['subzona'] ?? $metaG['subzona'] ?? ''));
        $tipo = (count($fechasEje) > 0 && count($fechasPlan) === 0) ? 'Eventual' : 'Planificado';
        $estado = '';
        if (count($interseccion) > 0) $estado = $estadoMatch;
        elseif (count($fechasPlan) > 0 && count($fechasEje) > 0) $estado = 'ATRASADO';
        elseif (count($fechasPlan) > 0 && count($fechasEje) === 0) {
            $estado = (strcmp($fechasPlan[0], date('Y-m-d')) < 0) ? 'NO CUMPLIDO' : '';
        } elseif (count($fechasEje) > 0 && count($fechasPlan) === 0) $estado = 'CUMPLIDO';
        $fechaMostrar = '';
        if ($fechaMostrarMatch !== '') $fechaMostrar = $fechaMostrarMatch;
        elseif (count($fechasEje) > 0) $fechaMostrar = $fechasEje[0];
        elseif (count($fechasPlan) > 0) $fechaMostrar = $fechasPlan[0];
        $filasPorKey[$key] = [
            'zona' => $zona, 'subzona' => $subzona, 'granja' => $g, 'nomGranja' => $nomGranja,
            'campania' => $c, 'galpon' => $gp, 'edad' => $ed,
            'planificado' => $fechasPlan, 'ejecutado' => $fechasEje,
            'fechaMostrar' => $fechaMostrar, 'tipo' => $tipo, 'estado' => $estado,
        ];
    }

    // Atrasado: si planificado (numCronograma!=0) no tiene match, buscar ejecutado (numCronograma=0) sin match, más cercano mayor en edad, misma granja/campania/galpon
    $usedEjecutadoKeys = [];
    $keyBaseGranjaCampaniaGalpon = function ($k) {
        $p = explode('|', $k);
        return (isset($p[0]) ? $p[0] : '') . '|' . (isset($p[1]) ? $p[1] : '') . '|' . (isset($p[2]) ? $p[2] : '');
    };
    $edadNumerica = function ($e) {
        $n = trim((string)$e);
        if ($n === '' || $n === '-') return null;
        return ctype_digit($n) || (strlen($n) > 0 && $n[0] === '-' && ctype_digit(substr($n, 1))) ? (int)$n : null;
    };
    $planificadosSinMatch = [];
    foreach ($filasPorKey as $key => $f) {
        if (count($f['planificado']) > 0 && count(array_intersect($f['planificado'], $f['ejecutado'])) === 0 && $f['estado'] === 'NO CUMPLIDO') {
            $planificadosSinMatch[] = $key;
        }
    }
    usort($planificadosSinMatch, function ($a, $b) use ($edadNumerica) {
        $ea = $edadNumerica(explode('|', $a)[3] ?? '');
        $eb = $edadNumerica(explode('|', $b)[3] ?? '');
        if ($ea === null && $eb === null) return 0;
        if ($ea === null) return 1;
        if ($eb === null) return -1;
        return $ea - $eb;
    });
    foreach ($planificadosSinMatch as $keyPlan) {
        $f = $filasPorKey[$keyPlan];
        $basePlan = $keyBaseGranjaCampaniaGalpon($keyPlan);
        $edadP = $edadNumerica($f['edad']);
        if ($edadP === null) continue;
        $mejorKey = null;
        $mejorDiff = PHP_INT_MAX;
        foreach ($ejecutadosPorKey as $keyEje => $eje) {
            if (isset($usedEjecutadoKeys[$keyEje])) continue;
            if ($keyBaseGranjaCampaniaGalpon($keyEje) !== $basePlan) continue;
            if (count($eje['fechas']) === 0) continue;
            $edadE = $edadNumerica($eje['edad']);
            if ($edadE === null || $edadE < $edadP) continue;
            $planEje = $planificadosPorKey[$keyEje] ?? null;
            $intEje = $planEje ? array_intersect($planEje['fechas'], $eje['fechas']) : [];
            if (count($intEje) > 0) continue;
            $diff = $edadE - $edadP;
            if ($diff < $mejorDiff) {
                $mejorDiff = $diff;
                $mejorKey = $keyEje;
            }
        }
        if ($mejorKey !== null) {
            $ejeFechas = $ejecutadosPorKey[$mejorKey]['fechas'];
            $filasPorKey[$keyPlan]['ejecutado'] = array_values(array_unique(array_merge($filasPorKey[$keyPlan]['ejecutado'], $ejeFechas)));
            sort($filasPorKey[$keyPlan]['ejecutado']);
            $filasPorKey[$keyPlan]['estado'] = 'ATRASADO';
            $filasPorKey[$keyPlan]['fechaMostrar'] = !empty($filasPorKey[$keyPlan]['ejecutado']) ? $filasPorKey[$keyPlan]['ejecutado'][0] : $filasPorKey[$keyPlan]['fechaMostrar'];
            $filasPorKey[$keyPlan]['tipo'] = 'Planificado';
            $usedEjecutadoKeys[$mejorKey] = true;
            $filasPorKey[$mejorKey]['ejecutado'] = [];
            $filasPorKey[$mejorKey]['fechaMostrar'] = !empty($filasPorKey[$mejorKey]['planificado']) ? $filasPorKey[$mejorKey]['planificado'][0] : '';
            $filasPorKey[$mejorKey]['estado'] = '';
            $filasPorKey[$mejorKey]['tipo'] = count($filasPorKey[$mejorKey]['planificado']) > 0 ? 'Planificado' : 'Eventual';
        }
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
        $hoy = date('Y-m-d');
        foreach ($matchResult['pares'] as $par) {
            $fechaPlan = $par['plan'];
            $fechaEje = $par['ejec'];
            $exactamenteIguales = ($fechaEje !== null && $fechaPlan === $fechaEje);
            if ($exactamenteIguales) {
                $edadFila = isset($edadPorFecha[$fechaPlan]) ? $edadPorFecha[$fechaPlan] : $ed;
                $numCron = (int)($plan['numCronogramaPorFecha'][$fechaPlan] ?? 0);
                $tipoPlan = ($numCron !== 0) ? 'Planificado' : 'Eventual';
                $filas[] = [
                    'zona' => $f['zona'], 'subzona' => $f['subzona'], 'granja' => $f['granja'], 'nomGranja' => $f['nomGranja'],
                    'campania' => $f['campania'], 'galpon' => $f['galpon'], 'edad' => $edadFila,
                    'planificado' => [$fechaPlan], 'ejecutado' => [$fechaEje],
                    'fechaMostrar' => $fechaPlan, 'tipo' => $tipoPlan, 'estado' => 'CUMPLIDO',
                ];
            } elseif ($fechaEje !== null) {
                $edadFila = isset($edadPorFecha[$fechaPlan]) ? $edadPorFecha[$fechaPlan] : $ed;
                $estadoPlan = ($fechaPlan <= $hoy) ? 'NO CUMPLIDO' : '';
                $numCron = (int)($plan['numCronogramaPorFecha'][$fechaPlan] ?? 0);
                $tipoPlan = ($numCron !== 0) ? 'Planificado' : 'Eventual';
                $keyPlan = $key . '|' . $fechaPlan;
                $filas[] = [
                    'zona' => $f['zona'], 'subzona' => $f['subzona'], 'granja' => $f['granja'], 'nomGranja' => $f['nomGranja'],
                    'campania' => $f['campania'], 'galpon' => $f['galpon'], 'edad' => $edadFila,
                    'planificado' => [$fechaPlan], 'ejecutado' => [],
                    'fechaMostrar' => $fechaPlan, 'tipo' => $tipoPlan, 'estado' => $estadoPlan,
                    'key' => $keyPlan,
                ];
                $esAnomaliaEje = ($fechaEje <= $hoy) && (strcmp($fechaEje, $fechaPlan) < 0);
                $estadoEje = ($fechaEje <= $hoy) ? ($esAnomaliaEje ? 'NO CUMPLIO' : 'ATRASADO') : '';
                $filas[] = [
                    'zona' => $f['zona'], 'subzona' => $f['subzona'], 'granja' => $f['granja'], 'nomGranja' => $f['nomGranja'],
                    'campania' => $f['campania'], 'galpon' => $f['galpon'], 'edad' => $ed,
                    'planificado' => [], 'ejecutado' => [$fechaEje],
                    'fechaMostrar' => $fechaEje, 'tipo' => '-', 'estado' => $estadoEje,
                    'parentKey' => $keyPlan,
                ];
            } else {
                $edadFila = isset($edadPorFecha[$fechaPlan]) ? $edadPorFecha[$fechaPlan] : $ed;
                $estadoPlan = ($fechaPlan <= $hoy) ? 'NO CUMPLIDO' : '';
                $numCron = (int)($plan['numCronogramaPorFecha'][$fechaPlan] ?? 0);
                $tipoPlan = ($numCron !== 0) ? 'Planificado' : 'Eventual';
                $filas[] = [
                    'zona' => $f['zona'], 'subzona' => $f['subzona'], 'granja' => $f['granja'], 'nomGranja' => $f['nomGranja'],
                    'campania' => $f['campania'], 'galpon' => $f['galpon'], 'edad' => $edadFila,
                    'planificado' => [$fechaPlan], 'ejecutado' => [],
                    'fechaMostrar' => $fechaPlan, 'tipo' => $tipoPlan, 'estado' => $estadoPlan,
                ];
            }
        }
        foreach ($matchResult['ejecutadasSinMatch'] as $fechaEje) {
            $estadoEje = ($fechaEje <= $hoy) ? 'ANOMALIA' : '';
            $filas[] = [
                'zona' => $f['zona'], 'subzona' => $f['subzona'], 'granja' => $f['granja'], 'nomGranja' => $f['nomGranja'],
                'campania' => $f['campania'], 'galpon' => $f['galpon'], 'edad' => $ed,
                'planificado' => [], 'ejecutado' => [$fechaEje],
                'fechaMostrar' => $fechaEje, 'tipo' => '-', 'estado' => $estadoEje,
            ];
        }
    }

    if ($filtroEstado !== '') {
        $filas = array_filter($filas, function ($f) use ($filtroEstado) {
            $e = $f['estado'] ?? '';
            if ($filtroEstado === 'cumplido') return in_array($e, ['CUMPLIDO', 'REALIZADO'], true);
            if ($filtroEstado === 'atrasado') return $e === 'ATRASADO';
            if ($filtroEstado === 'no_cumplido') return $e === 'NO CUMPLIDO' || $e === 'NO CUMPLIO';
            if ($filtroEstado === 'anomalia') return ($f['tipo'] ?? '') === '-' && ($e === 'ANOMALIA' || ($e === 'NO CUMPLIO' && isset($f['parentKey'])));
            return true;
        });
        $filas = array_values($filas);
    }

    usort($filas, function ($a, $b) {
        $primeraA = $a['fechaMostrar'] ?? '';
        $primeraB = $b['fechaMostrar'] ?? '';
        if ($primeraA === '') {
            $fechasA = array_values(array_unique(array_merge($a['planificado'], $a['ejecutado'])));
            sort($fechasA);
            $primeraA = !empty($fechasA) ? $fechasA[0] : '';
        }
        if ($primeraB === '') {
            $fechasB = array_values(array_unique(array_merge($b['planificado'], $b['ejecutado'])));
            sort($fechasB);
            $primeraB = !empty($fechasB) ? $fechasB[0] : '';
        }
        $x = strcmp($primeraA, $primeraB);
        if ($x !== 0) return $x;
        $x = strcmp($a['zona'], $b['zona']);
        if ($x !== 0) return $x;
        $x = strcmp($a['subzona'], $b['subzona']);
        if ($x !== 0) return $x;
        $x = strcmp($a['granja'], $b['granja']);
        if ($x !== 0) return $x;
        $x = strcmp($a['campania'], $b['campania']);
        if ($x !== 0) return $x;
        $x = strcmp($a['galpon'], $b['galpon']);
        if ($x !== 0) return $x;
        return strcmp((string)$a['edad'], (string)$b['edad']);
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
            if ($k !== '' && isset($atrasadosPorParent[$k])) {
                foreach ($atrasadosPorParent[$k] as $atr) $filasOrden2[] = $atr;
            }
        }
        $filas = $filasOrden2;
    }

    $filasPorTipo[$nombreTipo] = $filas;
}

$conn->close();

if (isset($GLOBALS['REPORTE_UNIFICADO_RETORNAR']) && $GLOBALS['REPORTE_UNIFICADO_RETORNAR'] === 'movi') {
    $GLOBALS['reporte_movi_data'] = ['filasPorTipo' => $filasPorTipo, 'rango' => $rango];
    return;
}

date_default_timezone_set('America/Lima');
$fechaReporte = date('d/m/Y H:i');
$fechaTitulo = $periodoTipo === 'TODOS' ? 'Todos los registros' : date('d/m/Y', strtotime($rango['desde']));
if ($periodoTipo !== 'TODOS' && $rango['desde'] !== $rango['hasta']) {
    $fechaTitulo = date('d/m/Y', strtotime($rango['desde'])) . ' al ' . date('d/m/Y', strtotime($rango['hasta']));
}

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
$tituloReporte = 'REPORTE COMPARATIVO — ' . $fechaTitulo;

$tempDir = __DIR__ . '/../../../pdf_tmp';
if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);
if (!is_dir($tempDir) || !is_writable($tempDir)) $tempDir = sys_get_temp_dir();

$css = '
body{font-family:"Segoe UI",Arial,sans-serif;font-size:9pt;color:#1e293b;margin:0;padding:10px;}
.fecha-hora-arriba{position:absolute;top:8px;right:0;font-size:9pt;color:#475569;z-index:10;}
.data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:1px solid #cbd5e1;}
.data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;}
.data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
.data-table .tipo-planificado{background:#dcfce7;}
.data-table .tipo-eventual{background:#fef3c7;}
.data-table .tipo-anomalia{background:#fee2e2;}
.data-table .celda-vacia{color:#94a3b8;}
.grupo-fecha{background:#0f172a !important;color:#fff !important;font-weight:bold;border-top:2px solid #1e293b;}
';

function formatearFechasCortaMovi($fechas, $max = 3) {
    if (empty($fechas)) return '';
    $total = count($fechas);
    $lista = array_slice($fechas, 0, $max);
    $txt = implode(', ', array_map(function ($d) {
        return date('d/m/Y', strtotime($d));
    }, $lista));
    if ($total > $max) $txt .= ' ...';
    return $txt;
}

try {
    if (ob_get_level()) ob_clean();
    require_once __DIR__ . '/../../../vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8', 'format' => 'A4-L',
        'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 12, 'margin_bottom' => 18,
        'tempDir' => $tempDir, 'defaultfooterline' => 0, 'simpleTables' => true, 'packTableData' => true,
    ]);
    $mpdf->SetFooter('<div style="text-align:center;font-size:9pt;font-weight:normal;color:#374151;">{PAGENO} de {nbpg}</div>');
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $htmlCab = '<table width="100%" style="border-collapse: collapse; margin-bottom: 10px; ' . $bordeTitulo . '">';
    $htmlCab .= '<tr><td style="width: 20%; padding: 5px; ' . $bordeTitulo . '">' . ($logo ? $logo . ' ' : '') . 'GRANJA RINCONADA DEL SUR S.A.</td>';
    $htmlCab .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px; ' . $bordeTitulo . '">' . htmlspecialchars($tituloReporte) . '</td>';
    $htmlCab .= '<td style="width: 20%; text-align: right; padding: 5px; ' . $bordeTitulo . '">' . htmlspecialchars($fechaReporte) . '</td></tr></table>';
    $mpdf->WriteHTML($htmlCab, \Mpdf\HTMLParserMode::HTML_BODY);
    $theadRow = '<tr><th style="width:5%;text-align:center;">N&#176;</th><th style="width:6%">Zona</th><th style="width:6%">Subzona</th><th style="width:7%">Granja</th><th style="width:12%">Nombre granja</th><th style="width:7%">Campaña</th><th style="width:6%">Galpón</th><th style="width:4%">Edad</th><th style="width:10%">Fecha planificada</th><th style="width:10%">Fecha desarrollada</th><th style="width:6%">Tipo</th><th style="width:8%">Estado</th></tr>';

    $maxFilasPdf = ($periodoTipo === 'TODOS') ? 5000 : 20000;
    $totalFilas = 0;
    foreach ($filasPorTipo as $nombreTipo => $filas) {
        $tituloSeccion = strtoupper($nombreTipo);
        $mpdf->WriteHTML('<div style="margin-top:12px;margin-bottom:6px;font-weight:bold;font-size:11pt;color:#1e40af;">' . htmlspecialchars($tituloSeccion) . '</div>', \Mpdf\HTMLParserMode::HTML_BODY);
        if (empty($filas)) {
            $mpdf->WriteHTML('<table class="data-table"><thead>' . $theadRow . '</thead><tbody><tr><td colspan="12" style="text-align:center;color:#64748b;">No hay datos para este período y filtros.</td></tr></tbody></table>', \Mpdf\HTMLParserMode::HTML_BODY);
        } else {
            $filasRecortadas = false;
            if ($totalFilas + count($filas) > $maxFilasPdf) {
                $filas = array_slice($filas, 0, max(0, $maxFilasPdf - $totalFilas));
                $filasRecortadas = true;
            }
            $totalFilas += count($filas);
            $buf = '';
            $n = 0;
            $fechaAnt = '';
            $prefijoFecha = htmlspecialchars($tituloSeccion) . ' ';
            $mpdf->WriteHTML('<table class="data-table"><thead>' . $theadRow . '</thead><tbody>', \Mpdf\HTMLParserMode::HTML_BODY);
            foreach ($filas as $r) {
                $fechaMostrar = $r['fechaMostrar'] ?? '';
                $fechaLabel = $fechaMostrar ? date('d/m/Y', strtotime($fechaMostrar)) : '';
                if ($fechaLabel !== '' && $fechaLabel !== $fechaAnt) {
                    $fechaAnt = $fechaLabel;
                    $buf .= '<tr class="grupo-fecha"><td colspan="12">' . $prefijoFecha . htmlspecialchars($fechaLabel) . '</td></tr>';
                }
                $n++;
                $esPosteriorHoy = ($fechaMostrar !== '' && strtotime($fechaMostrar) > strtotime(date('Y-m-d')));
                $vacio = $esPosteriorHoy ? '' : '<span class="celda-vacia">—</span>';
                $clase = $r['tipo'] === 'Planificado' ? 'tipo-planificado' : ($r['tipo'] === 'Eventual' ? 'tipo-eventual' : ($r['tipo'] === 'ANOMALIA' ? 'tipo-anomalia' : ''));
                $fechaPlanTxt = formatearFechasCortaMovi($r['planificado'] ?? [], 3);
                $fechaDesTxt = formatearFechasCortaMovi($r['ejecutado'] ?? [], 3);
                $estado = $r['estado'] ?? '';
                $buf .= '<tr class="' . $clase . '"><td style="text-align:center;">' . $n . '</td><td>' . htmlspecialchars($r['zona']) . '</td><td>' . htmlspecialchars($r['subzona']) . '</td><td>' . htmlspecialchars($r['granja']) . '</td><td>' . htmlspecialchars($r['nomGranja']) . '</td><td>' . htmlspecialchars($r['campania']) . '</td><td>' . htmlspecialchars($r['galpon']) . '</td><td>' . htmlspecialchars($r['edad']) . '</td><td>' . ($fechaPlanTxt !== '' ? htmlspecialchars($fechaPlanTxt) : $vacio) . '</td><td>' . ($fechaDesTxt !== '' ? htmlspecialchars($fechaDesTxt) : $vacio) . '</td><td>' . htmlspecialchars($r['tipo']) . '</td><td>' . ($estado !== '' ? htmlspecialchars($estado) : $vacio) . '</td></tr>';
                if ($n % 100 === 0) {
                    $mpdf->WriteHTML($buf, \Mpdf\HTMLParserMode::HTML_BODY);
                    $buf = '';
                }
            }
            if ($buf !== '') $mpdf->WriteHTML($buf, \Mpdf\HTMLParserMode::HTML_BODY);
            $mpdf->WriteHTML('</tbody></table>', \Mpdf\HTMLParserMode::HTML_BODY);
            if ($filasRecortadas) {
                $mpdf->WriteHTML('<div style="margin:4px 0;font-size:8pt;color:#92400e;">Se alcanzó el límite de filas.</div>', \Mpdf\HTMLParserMode::HTML_BODY);
            }
        }
    }
    $mpdf->Output('reporte_comparativo_movi_' . $rango['desde'] . '_' . $rango['hasta'] . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
