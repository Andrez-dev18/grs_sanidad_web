<?php
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
$fechaLegacy = trim((string)($_GET['fecha'] ?? ''));

// Filtros opcionales: granja[]|granja, galpon[]|galpon, campania[], edad[]
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
$filtroEstado = trim((string)($_GET['estado'] ?? ''));
$filtroCategoria = trim((string)($_GET['categoria'] ?? ''));
$modoDebug = isset($_GET['debug']) && $_GET['debug'] === '1';

$tipoProgramaIds = [];
if (isset($_GET['tipoPrograma']) && is_array($_GET['tipoPrograma'])) {
    foreach ($_GET['tipoPrograma'] as $v) {
        $v = trim((string)$v);
        if ($v !== '') $tipoProgramaIds[] = $v;
    }
}

// Compatibilidad hacia atrás: ?fecha=YYYY-MM-DD
if ($periodoTipo === '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaLegacy)) {
    $periodoTipo = 'POR_FECHA';
    $fechaUnica = $fechaLegacy;
}
if ($periodoTipo === '') $periodoTipo = 'POR_FECHA';

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

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    exit('Error de conexión');
}
include_once __DIR__ . '/comparativo_unificado_util.php';

// Normalizar granja(s) a 3 caracteres para filtro
$filtroGranjasNorm = [];
foreach ($filtroGranjas as $gIn) {
    $gN = substr(str_pad($gIn, 3, '0', STR_PAD_LEFT), 0, 3);
    if ($gN !== '') $filtroGranjasNorm[$gN] = true;
}
$filtroGranjasNorm = array_keys($filtroGranjasNorm);

// Siglas para filtrar cronograma por tipo de programa. Si no hay tipos, se usa NC%.
$siglasCronograma = ['NC'];
if (count($tipoProgramaIds) > 0) {
    $ph = implode(',', array_fill(0, count($tipoProgramaIds), '?'));
    $stTipos = $conn->prepare("SELECT UPPER(TRIM(COALESCE(sigla,''))) AS sigla FROM san_dim_tipo_programa WHERE codigo IN ($ph)");
    if ($stTipos) {
        $stTipos->bind_param(str_repeat('s', count($tipoProgramaIds)), ...$tipoProgramaIds);
        $stTipos->execute();
        $resTipos = $stTipos->get_result();
        $siglasCronograma = [];
        while ($row = $resTipos->fetch_assoc()) {
            $sigla = trim((string)($row['sigla'] ?? ''));
            if ($sigla !== '' && !in_array($sigla, $siglasCronograma, true)) $siglasCronograma[] = $sigla;
        }
        $stTipos->close();
        if (count($siglasCronograma) === 0) $siglasCronograma = ['NC'];
    }
}

$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;
$chkNomGranja = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
$chkZona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'zona'");
$chkSubzona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'subzona'");
$chkNumCronograma = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
$chkToleranciaCrono = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'tolerancia'");
$chkCategoriaCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'categoria'");
$tieneCategoriaCab = $chkCategoriaCab && $chkCategoriaCab->num_rows > 0;
$tieneNomGranja = $chkNomGranja && $chkNomGranja->num_rows > 0;
$tieneZona = $chkZona && $chkZona->num_rows > 0;
$tieneSubzona = $chkSubzona && $chkSubzona->num_rows > 0;
$tieneNumCronograma = $chkNumCronograma && $chkNumCronograma->num_rows > 0;
$tieneToleranciaCrono = $chkToleranciaCrono && $chkToleranciaCrono->num_rows > 0;

$esTodos = ($periodoTipo === 'TODOS');
$CHUNK_SIZE = 5000;

// Programas NC: cargar esEspecial para key (granja|campania|galpon o solo granja si especial)
$codigosNecro = [];
foreach ($siglasCronograma as $sig) {
    $like = $conn->real_escape_string(trim($sig)) . '%';
    $r = @$conn->query("SELECT codigo FROM san_fact_programa_cab WHERE UPPER(TRIM(codigo)) LIKE UPPER('" . $like . "')");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $cod = trim((string)($row['codigo'] ?? ''));
            if ($cod !== '') $codigosNecro[$cod] = true;
        }
    }
}
$codigosNecro = array_keys($codigosNecro);
$esEspecialPorCod = comparativo_cargar_es_especial($conn, $codigosNecro);
$usarKeyEspecial = !empty($esEspecialPorCod) && max(array_values($esEspecialPorCod)) === 1;

$joinCategoriaCrono = '';
if ($filtroCategoria !== '' && $tieneCategoriaCab) {
    $joinCategoriaCrono = " LEFT JOIN san_fact_programa_cab cab ON TRIM(cab.codigo) = TRIM(c.codPrograma)";
}
$sqlCronoBase = "SELECT DATE(c.fechaEjecucion) AS fecha_ref, c.granja, c.campania, c.galpon, c.codPrograma"
    . ($tieneEdad ? ", c.edad" : "")
    . ($tieneNomGranja ? ", c.nomGranja" : "")
    . ($tieneZona ? ", c.zona" : "")
    . ($tieneSubzona ? ", c.subzona" : "")
    . ($tieneNumCronograma ? ", COALESCE(c.numCronograma, 0) AS numCronograma" : ", 0 AS numCronograma")
    . ($tieneToleranciaCrono ? ", COALESCE(NULLIF(c.tolerancia, 0), 1) AS tolerancia" : ", 1 AS tolerancia")
    . "
    FROM san_fact_cronograma c" . $joinCategoriaCrono;
$condCodPrograma = [];
foreach ($siglasCronograma as $sig) {
    $esc = $conn->real_escape_string($sig);
    $condCodPrograma[] = "UPPER(TRIM(c.codPrograma)) LIKE '" . $esc . "%'";
}
$whereCrono = [" (" . implode(' OR ', $condCodPrograma) . ")"];
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
    $whereCrono[] = " CAST(TRIM(c.galpon) AS UNSIGNED) IN ($phGp)";
    foreach ($filtroGalpones as $fgp) {
        $paramsCrono[] = $fgp;
        $typesCrono .= 's';
    }
}
if (count($filtroCampanias) > 0) {
    $placeholders = implode(',', array_fill(0, count($filtroCampanias), '?'));
    $whereCrono[] = " TRIM(c.campania) IN ($placeholders)";
    foreach ($filtroCampanias as $fc) { $paramsCrono[] = $fc; $typesCrono .= 's'; }
}
if (count($filtroEdades) > 0) {
    $placeholders = implode(',', array_fill(0, count($filtroEdades), '?'));
    $whereCrono[] = " CAST(TRIM(c.edad) AS UNSIGNED) IN ($placeholders)";
    foreach ($filtroEdades as $fe) { $paramsCrono[] = $fe; $typesCrono .= 's'; }
}
if ($filtroCategoria !== '' && $tieneCategoriaCab) {
    $whereCrono[] = " TRIM(cab.categoria) = ?";
    $paramsCrono[] = $filtroCategoria;
    $typesCrono .= 's';
}
$planificadosPorKey = [];
$metaPorGranja = []; // fallback para filas eventuales (sin match en cronograma)
$offsetCrono = 0;
do {
    $sqlCrono = $sqlCronoBase . (count($whereCrono) > 0 ? " WHERE" . implode(" AND", $whereCrono) : "");
    $sqlCrono .= " ORDER BY c.granja, c.campania, c.galpon, c.edad, c.fechaEjecucion LIMIT " . (int)$CHUNK_SIZE . " OFFSET " . (int)$offsetCrono;
    $stmtCrono = $conn->prepare($sqlCrono);
    if (count($paramsCrono) > 0) {
        $stmtCrono->bind_param($typesCrono, ...$paramsCrono);
    }
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
    $key = comparativo_build_key($g, $c, $gp, $codPrograma, $esEspecialPorCod, $e);
    $nomGranja = $tieneNomGranja ? trim((string)($row['nomGranja'] ?? '')) : '';
    $zona = $tieneZona ? trim((string)($row['zona'] ?? '')) : '';
    $subzona = $tieneSubzona ? trim((string)($row['subzona'] ?? '')) : '';
        if (!isset($planificadosPorKey[$key])) {
            $planificadosPorKey[$key] = [
            'granja' => $g,
            'campania' => $c,
            'galpon' => $gp,
            'edad' => $e,
            'codPrograma' => $codPrograma,
            'nomGranja' => $nomGranja,
            'zona' => $zona,
            'subzona' => $subzona,
            'fechas' => [],
            'toleranciaPorFecha' => [],
            'edadPorFecha' => [],
            'numCronogramaPorFecha' => []
        ];
    } else {
        if ($planificadosPorKey[$key]['codPrograma'] === '' && $codPrograma !== '') $planificadosPorKey[$key]['codPrograma'] = $codPrograma;
        if ($planificadosPorKey[$key]['nomGranja'] === '' && $nomGranja !== '') $planificadosPorKey[$key]['nomGranja'] = $nomGranja;
        if ($planificadosPorKey[$key]['zona'] === '' && $zona !== '') $planificadosPorKey[$key]['zona'] = $zona;
        if ($planificadosPorKey[$key]['subzona'] === '' && $subzona !== '') $planificadosPorKey[$key]['subzona'] = $subzona;
        if ($planificadosPorKey[$key]['edad'] === '' && $e !== '') $planificadosPorKey[$key]['edad'] = $e;
    }
    if (!isset($metaPorGranja[$g])) {
        $metaPorGranja[$g] = ['nomGranja' => $nomGranja, 'zona' => $zona, 'subzona' => $subzona];
    } else {
        if ($metaPorGranja[$g]['nomGranja'] === '' && $nomGranja !== '') $metaPorGranja[$g]['nomGranja'] = $nomGranja;
        if ($metaPorGranja[$g]['zona'] === '' && $zona !== '') $metaPorGranja[$g]['zona'] = $zona;
        if ($metaPorGranja[$g]['subzona'] === '' && $subzona !== '') $metaPorGranja[$g]['subzona'] = $subzona;
    }
    if ($fechaRef && !in_array($fechaRef, $planificadosPorKey[$key]['fechas'], true)) {
        $planificadosPorKey[$key]['fechas'][] = $fechaRef;
    }
    if ($fechaRef) {
        $tolRow = max(1, (int)($row['tolerancia'] ?? 1));
        $actual = (int)($planificadosPorKey[$key]['toleranciaPorFecha'][$fechaRef] ?? 0);
        if ($tolRow > $actual) {
            $planificadosPorKey[$key]['toleranciaPorFecha'][$fechaRef] = $tolRow;
        }
        $numCron = (int)($row['numCronograma'] ?? 0);
        $actualNum = (int)($planificadosPorKey[$key]['numCronogramaPorFecha'][$fechaRef] ?? -1);
        if ($numCron > $actualNum) {
            $planificadosPorKey[$key]['numCronogramaPorFecha'][$fechaRef] = $numCron;
        }
    }
    if ($fechaRef && $e !== '') {
        $planificadosPorKey[$key]['edadPorFecha'][$fechaRef] = $e;
    }
    $rowsChunk++;
    }
    $stmtCrono->close();
    $offsetCrono += $CHUNK_SIZE;
} while ($rowsChunk >= $CHUNK_SIZE);

// Necropsias: fechas desarrolladas desde t_regnecropsia (registro real). Sin fallback.
$ejecutadosPorKey = [];
$chkRegNec = @$conn->query("SHOW TABLES LIKE 't_regnecropsia'");
if ($chkRegNec && $chkRegNec->num_rows > 0) {
    // Alinear con migrar_necropsias_a_cronograma.sql: LPAD para granja/campania (keyBase consistente)
    $sqlReg = "SELECT DATE(r.tfectra) AS fecha_ref,
        LPAD(LEFT(TRIM(r.tgranja), 3), 3, '0') AS granja,
        LPAD(RIGHT(COALESCE(NULLIF(TRIM(r.tcampania), ''), RIGHT(TRIM(r.tgranja), 3)), 3), 3, '0') AS campania,
        TRIM(r.tgalpon) AS galpon,
        COALESCE(NULLIF(TRIM(CAST(r.tedad AS CHAR)), ''), '0') AS edad
        FROM t_regnecropsia r
        WHERE TRIM(r.tgranja) <> '' AND TRIM(r.tgalpon) <> '' AND TRIM(r.tgalpon) != '0'";
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
        $sqlReg .= " AND LPAD(LEFT(TRIM(r.tgranja), 3), 3, '0') IN ($phG)";
        foreach ($filtroGranjasNorm as $gN) { $paramsReg[] = $gN; $typesReg .= 's'; }
    }
    if (count($filtroGalpones) > 0) {
        $phGp = implode(',', array_fill(0, count($filtroGalpones), '?'));
        $sqlReg .= " AND TRIM(r.tgalpon) IN ($phGp)";
        foreach ($filtroGalpones as $fgp) { $paramsReg[] = $fgp; $typesReg .= 's'; }
    }
    if (count($filtroCampanias) > 0) {
        $ph = implode(',', array_fill(0, count($filtroCampanias), '?'));
        $sqlReg .= " AND LPAD(RIGHT(COALESCE(NULLIF(TRIM(r.tcampania), ''), RIGHT(TRIM(r.tgranja), 3)), 3), 3, '0') IN ($ph)";
        foreach ($filtroCampanias as $fc) { $paramsReg[] = $fc; $typesReg .= 's'; }
    }
    if (count($filtroEdades) > 0) {
        $ph = implode(',', array_fill(0, count($filtroEdades), '?'));
        $sqlReg .= " AND COALESCE(NULLIF(TRIM(CAST(r.tedad AS CHAR)), ''), '0') IN ($ph)";
        foreach ($filtroEdades as $fe) { $paramsReg[] = $fe; $typesReg .= 's'; }
    }
    $sqlReg .= " ORDER BY granja, campania, galpon, edad, fecha_ref";
    $stReg = $conn->prepare($sqlReg);
    if ($stReg && count($paramsReg) > 0) $stReg->bind_param($typesReg, ...$paramsReg);
    if ($stReg && $stReg->execute()) {
        $resReg = $stReg->get_result();
        while ($row = $resReg->fetch_assoc()) {
            $g = norm_granja_3($row['granja'] ?? '');
            $c = norm_campania_3($row['campania'] ?? '');
            $gp = norm_num_text($row['galpon'] ?? '');
            $e = norm_num_text($row['edad'] ?? '');
            $key = comparativo_build_key($g, $c, $gp, $codigosNecro[0] ?? 'NC', $esEspecialPorCod, $e);
            $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
            $metaG = $metaPorGranja[$g] ?? ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
            if (!isset($ejecutadosPorKey[$key])) {
                $ejecutadosPorKey[$key] = ['granja' => $g, 'campania' => $c, 'galpon' => $gp, 'edad' => $e, 'fechas' => [], 'nomGranja' => $metaG['nomGranja'] ?? '', 'zona' => $metaG['zona'] ?? '', 'subzona' => $metaG['subzona'] ?? ''];
            }
            if ($fechaRef && !in_array($fechaRef, $ejecutadosPorKey[$key]['fechas'], true)) {
                $ejecutadosPorKey[$key]['fechas'][] = $fechaRef;
            }
        }
        $stReg->close();
    }
}

// Conjunto de claves único (reutilizado para enriquecer y para filas)
$clavesUnion = array_values(array_unique(array_merge(array_keys($planificadosPorKey), array_keys($ejecutadosPorKey))));
// Para granjas que necesitan zona, subzona o nomGranja: obtener desde pi_dim_detalles, regcencosgalpones, ccos
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
    $chkReg = @$conn->query("SHOW TABLES LIKE 'regcencosgalpones'");
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
    }
    $chkCcos = @$conn->query("SHOW TABLES LIKE 'ccos'");
    if ($chkCcos && $chkCcos->num_rows > 0) {
        $chkNomCcos = @$conn->query("SHOW COLUMNS FROM ccos LIKE 'nombre'");
        if ($chkNomCcos && $chkNomCcos->num_rows > 0) {
            $ph = implode(',', array_fill(0, count($granjasParaEnriquecer), '?'));
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
    }
}

if ($conn) {
    $conn->close();
}

// Helper: keyBase = primeros 3 segmentos de la key (granja|campania|galpon)
$extractKeyBase = function ($k) {
    $p = explode('|', $k);
    return (isset($p[0]) ? $p[0] : '') . '|' . (isset($p[1]) ? $p[1] : '') . '|' . (isset($p[2]) ? $p[2] : '');
};

// Normalizar keyBase para evitar desajustes cronograma vs t_regnecropsia (ej: "6" vs "062")
$canonicalKeyBase = function ($kb) {
    $p = explode('|', $kb, 3);
    $norm = function ($s) {
        $s = trim((string)$s);
        if ($s === '') return '';
        return ctype_digit($s) ? str_pad($s, 3, '0', STR_PAD_LEFT) : $s;
    };
    return $norm($p[0] ?? '') . '|' . $norm($p[1] ?? '') . '|' . $norm($p[2] ?? '');
};

// Precalcular NO CUMPLIO por keyBase (para emparejar ejecutadas de una key con planes de otra key, mismo keyBase)
// Iterar sobre planificadosPorKey directamente para no perder ninguno (clavesUnion puede tener keys con formato distinto)
$noCumplioPorKeyBase = [];
$debugInfo = [];
foreach ($planificadosPorKey as $key => $plan) {
    $keyBaseFromKey = $extractKeyBase($key);
    $keyBaseFromPlan = $plan['granja'] . '|' . $plan['campania'] . '|' . $plan['galpon'];
    $keyBasesToAdd = array_unique(array_filter([$keyBaseFromKey, $keyBaseFromPlan, $canonicalKeyBase($keyBaseFromKey), $canonicalKeyBase($keyBaseFromPlan)]));
    $fechasPlan = $plan['fechas'];
    $fechasEje = isset($ejecutadosPorKey[$key]) ? $ejecutadosPorKey[$key]['fechas'] : [];
    $toleranciaPorFecha = isset($plan['toleranciaPorFecha']) ? $plan['toleranciaPorFecha'] : [];
    sort($fechasPlan);
    sort($fechasEje);
    $matchResult = match_plan_eje_con_tolerancia_por_fecha($fechasPlan, $toleranciaPorFecha, $fechasEje, true);
    foreach ($matchResult['pares'] as $par) {
        if (array_key_exists('ejec', $par) && $par['ejec'] === null) {
            $tol = isset($toleranciaPorFecha[$par['plan']]) ? max(1, (int)$toleranciaPorFecha[$par['plan']]) : 1;
            $item = ['plan' => $par['plan'], 'tol' => $tol, 'key' => $key];
            foreach ($keyBasesToAdd as $kb) {
                if ($kb === '') continue;
                if (!isset($noCumplioPorKeyBase[$kb])) $noCumplioPorKeyBase[$kb] = [];
                $noCumplioPorKeyBase[$kb][] = $item;
            }
            if ($modoDebug && strpos($key, '664') !== false && strpos($key, '062') !== false && preg_match('/\|(17|18)$/', $key)) {
                $debugInfo['n5_plan_' . $key] = ['key' => $key, 'plan' => $par['plan'], 'tol' => $tol, 'toleranciaPorFecha' => $toleranciaPorFecha, 'keyBasesToAdd' => $keyBasesToAdd];
            }
        }
    }
}

// Pasada 1: emparejar todas las keys (planUsadoDesdeOtraKey se conoce solo tras procesar todas)
$todasLasClaves = $clavesUnion;
$resultadosPorKey = [];
$planUsadoDesdeOtraKeyGlobal = [];
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
    $edadPorFecha = $plan && isset($plan['edadPorFecha']) ? $plan['edadPorFecha'] : [];
    sort($fechasPlan);
    sort($fechasEje);
    $matchResult = match_plan_eje_con_tolerancia_por_fecha($fechasPlan, $toleranciaPorFecha, $fechasEje, true);
    $keyBase = $extractKeyBase($key);
    $keyBaseAlt = $g . '|' . $c . '|' . $gp;
    $keyBaseCanon = $canonicalKeyBase($keyBase);
    $lookupBases = array_unique(array_filter([$keyBase, $keyBaseAlt, $keyBaseCanon, $canonicalKeyBase($keyBaseAlt)]));
    // Fallback: incluir keyBases de noCumplioPorKeyBase cuyo canonical coincida (por si hay variación granja 6 vs 006, etc.)
    foreach (array_keys($noCumplioPorKeyBase) as $kb) {
        if ($canonicalKeyBase($kb) === $keyBaseCanon) {
            $lookupBases[] = $kb;
        }
    }
    $lookupBases = array_unique(array_filter($lookupBases));
    $seenPlan = [];
    $noCumplioExtras = [];
    foreach ($lookupBases as $lb) {
        foreach ($noCumplioPorKeyBase[$lb] ?? [] as $item) {
            $pk = $item['plan'] . '|' . ($item['key'] ?? '');
            if (!isset($seenPlan[$pk])) {
                $seenPlan[$pk] = true;
                $noCumplioExtras[] = $item;
            }
        }
    }
    // Fallback: si noCumplioExtras vacío y tenemos ejecutadas sin match, buscar NO CUMPLIO en planificadosPorKey (mismo keyBase)
    $fallbackEntro = false;
    $fallbackDebugIter = [];
    if (empty($noCumplioExtras) && !empty($matchResult['ejecutadasSinMatch'])) {
        $fallbackEntro = true;
        $keyBaseParaMatch = trim(($eje !== null) ? ((string)$g . '|' . (string)$c . '|' . (string)$gp) : $keyBase);
        foreach ($planificadosPorKey as $pkKey => $pkPlan) {
            $pkGb = trim((string)($pkPlan['granja'] ?? ''));
            $pkCb = trim((string)($pkPlan['campania'] ?? ''));
            $pkGp = trim((string)($pkPlan['galpon'] ?? ''));
            $pkKeyBase = $pkGb . '|' . $pkCb . '|' . $pkGp;
            if ($modoDebug && strpos($pkKeyBase, '664') !== false && strpos($pkKeyBase, '062') !== false) {
                $fallbackDebugIter[] = ['pkKey' => $pkKey, 'pkKeyBase' => $pkKeyBase, 'keyBaseParaMatch' => $keyBaseParaMatch, 'match' => ($pkKeyBase === $keyBaseParaMatch)];
            }
            if ($pkKeyBase !== $keyBaseParaMatch) continue;
            $pkFechasEje = isset($ejecutadosPorKey[$pkKey]) ? $ejecutadosPorKey[$pkKey]['fechas'] : [];
            $pkTol = isset($pkPlan['toleranciaPorFecha']) ? $pkPlan['toleranciaPorFecha'] : [];
            $pkFechas = $pkPlan['fechas'] ?? [];
            sort($pkFechas);
            sort($pkFechasEje);
            $pkMatch = match_plan_eje_con_tolerancia_por_fecha($pkFechas, $pkTol, $pkFechasEje, true);
            foreach ($pkMatch['pares'] as $par) {
                if (array_key_exists('ejec', $par) && $par['ejec'] === null) {
                    $tol = isset($pkTol[$par['plan']]) ? max(1, (int)$pkTol[$par['plan']]) : 1;
                    $noCumplioExtras[] = ['plan' => $par['plan'], 'tol' => $tol, 'key' => $pkKey];
                }
            }
        }
    }
    $emparejado = emparejar_atrasados_con_plan_anterior($matchResult['pares'], $matchResult['ejecutadasSinMatch'], $toleranciaPorFecha, $noCumplioExtras);
    if ($modoDebug) {
        $p = explode('|', $key);
        $esN7 = (count($p) >= 4 && ($p[3] ?? '') === '18' && strpos($key, '664') !== false && strpos($key, '062') !== false && (($p[2] ?? '') === '6' || ($p[2] ?? '') === '006'));
        if ($esN7) {
            $muestraNoCumplio = [];
            foreach ($lookupBases as $lb) {
                $muestraNoCumplio[$lb] = $noCumplioPorKeyBase[$lb] ?? [];
            }
            $n7Debug = [
                'key' => $key, 'keyBase' => $keyBase, 'lookupBases' => $lookupBases,
                'noCumplioExtras' => $noCumplioExtras, 'noCumplioPorKeyBase_muestra' => $muestraNoCumplio,
                'ejecutadasSinMatch' => $matchResult['ejecutadasSinMatch'],
                'ejecutadasSinMatch_despues' => $emparejado['ejecutadasSinMatch'], 'plan' => $plan ? 'existe' : 'null',
                'fallbackEntro' => $fallbackEntro ?? false, 'noCumplioExtras_count' => count($noCumplioExtras),
                'g_c_gp' => ['g' => $g, 'c' => $c, 'gp' => $gp], 'eje_existe' => $eje !== null,
            ];
            if (isset($fallbackDebugIter) && !empty($fallbackDebugIter)) {
                $n7Debug['fallback_iter_664'] = $fallbackDebugIter;
            }
            $debugInfo['n7_eje'] = $n7Debug;
        }
    }
    $pudo = $emparejado['planUsadoDesdeOtraKey'] ?? [];
    foreach ($pudo as $kPlan => $plans) {
        if (!isset($planUsadoDesdeOtraKeyGlobal[$kPlan])) $planUsadoDesdeOtraKeyGlobal[$kPlan] = [];
        foreach ($plans as $fp => $v) {
            $planUsadoDesdeOtraKeyGlobal[$kPlan][$fp] = true;
        }
    }
    $resultadosPorKey[$key] = [
        'plan' => $plan, 'eje' => $eje, 'g' => $g, 'c' => $c, 'gp' => $gp, 'ed' => $ed,
        'edadPorFecha' => $edadPorFecha, 'keyBase' => $keyBase,
        'pares' => $emparejado['pares'], 'ejecutadasSinMatch' => $emparejado['ejecutadasSinMatch'],
    ];
}

// Pasada 2: generar filas (omitir planes que fueron emparejados desde otra key)
$filas = [];
$hoy = date('Y-m-d');
foreach ($resultadosPorKey as $key => $res) {
    $plan = $res['plan'];
    $eje = $res['eje'];
    $g = $res['g'];
    $c = $res['c'];
    $gp = $res['gp'];
    $ed = $res['ed'];
    $edadPorFecha = $res['edadPorFecha'];
    $keyBase = $res['keyBase'];
    $matchResult = ['pares' => $res['pares'], 'ejecutadasSinMatch' => $res['ejecutadasSinMatch']];
    $metaG = $metaPorGranja[$g] ?? ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
    $nomGranja = trim((string)(($plan && isset($plan['nomGranja'])) ? $plan['nomGranja'] : ($metaG['nomGranja'] ?? '')));
    if ($nomGranja === '' && $eje && !empty($eje['nomGranja'])) {
        $nomGranja = trim((string)$eje['nomGranja']);
    }
    $zona = trim((string)(($plan && isset($plan['zona'])) ? $plan['zona'] : ($metaG['zona'] ?? '')));
    $subzona = trim((string)(($plan && isset($plan['subzona'])) ? $plan['subzona'] : ($metaG['subzona'] ?? '')));

    foreach ($matchResult['pares'] as $par) {
        $fechaPlan = $par['plan'];
        $fechaEje = $par['ejec'];
        $planKey = $par['planKey'] ?? $key;
        if (isset($planUsadoDesdeOtraKeyGlobal[$key][$fechaPlan])) continue;
        $planOrigen = isset($planificadosPorKey[$planKey]) ? $planificadosPorKey[$planKey] : $plan;
        $edadPorFechaOrigen = $planOrigen && isset($planOrigen['edadPorFecha']) ? $planOrigen['edadPorFecha'] : $edadPorFecha;
        $edOrigen = $planOrigen ? $planOrigen['edad'] : $ed;
        $exactamenteIguales = ($fechaEje !== null && $fechaPlan === $fechaEje);
        $numCron = (int)($planOrigen['numCronogramaPorFecha'][$fechaPlan] ?? 0);
        $tipoFila = ($numCron > 0) ? 'Planificado' : 'Eventual';
        if ($exactamenteIguales) {
            $estadoFila = 'SI CUMPLIO';
            $edadFila = isset($edadPorFechaOrigen[$fechaPlan]) ? $edadPorFechaOrigen[$fechaPlan] : $edOrigen;
            $edadEjeFila = $eje ? ($eje['edad'] ?? $ed) : $ed;
            $filas[] = [
                'zona' => $zona, 'subzona' => $subzona, 'granja' => $g, 'nomGranja' => $nomGranja,
                'campania' => $c, 'galpon' => $gp, 'edad' => $edadFila,
                'edadPlan' => $edadFila, 'edadDes' => $edadEjeFila,
                'planificado' => [$fechaPlan], 'ejecutado' => [$fechaEje],
                'fechaMostrar' => $fechaPlan, 'tipo' => $tipoFila, 'estado' => $estadoFila,
                'keyBase' => $keyBase, 'key' => $key,
            ];
        } elseif ($fechaEje !== null) {
            $edadFila = isset($edadPorFechaOrigen[$fechaPlan]) ? $edadPorFechaOrigen[$fechaPlan] : $edOrigen;
            $estadoPlan = ($fechaPlan <= $hoy) ? 'NO CUMPLIO' : '';
            $filas[] = [
                'zona' => $zona, 'subzona' => $subzona, 'granja' => $g, 'nomGranja' => $nomGranja,
                'campania' => $c, 'galpon' => $gp, 'edad' => $edadFila,
                'edadPlan' => $edadFila, 'edadDes' => '',
                'planificado' => [$fechaPlan], 'ejecutado' => [],
                'fechaMostrar' => $fechaPlan, 'tipo' => $tipoFila, 'estado' => $estadoPlan,
                'keyBase' => $keyBase, 'key' => $planKey, '_mergeKey' => $planKey . '|' . $fechaPlan,
            ];
            $edadEjeFila = $eje ? ($eje['edad'] ?? $ed) : $ed;
            $esAnomaliaEje = ($fechaEje <= $hoy) && (strcmp($fechaEje, $fechaPlan) < 0);
            $estadoEje = ($fechaEje <= $hoy) ? (($esAnomaliaEje ? 'NO CUMPLIO' : 'ATRASADO')) : '';
            $filas[] = [
                'zona' => $zona, 'subzona' => $subzona, 'granja' => $g, 'nomGranja' => $nomGranja,
                'campania' => $c, 'galpon' => $gp, 'edad' => $edadEjeFila,
                'edadPlan' => '', 'edadDes' => $edadEjeFila,
                'planificado' => [], 'ejecutado' => [$fechaEje],
                'fechaMostrar' => $fechaEje, 'tipo' => '-', 'estado' => $estadoEje,
                'keyBase' => $keyBase, 'key' => $key, 'parentKey' => $planKey,
                '_mergeKey' => $planKey . '|' . $fechaPlan, '_mergeParent' => true,
            ];
        } else {
            $estadoFila = ($fechaPlan <= $hoy) ? 'NO CUMPLIO' : '';
            $edadFila = isset($edadPorFechaOrigen[$fechaPlan]) ? $edadPorFechaOrigen[$fechaPlan] : $edOrigen;
            $filas[] = [
                'zona' => $zona, 'subzona' => $subzona, 'granja' => $g, 'nomGranja' => $nomGranja,
                'campania' => $c, 'galpon' => $gp, 'edad' => $edadFila,
                'edadPlan' => $edadFila, 'edadDes' => '',
                'planificado' => [$fechaPlan], 'ejecutado' => [],
                'fechaMostrar' => $fechaPlan, 'tipo' => $tipoFila, 'estado' => $estadoFila,
                'keyBase' => $keyBase, 'key' => $key,
            ];
        }
    }
    foreach ($matchResult['ejecutadasSinMatch'] as $fechaEje) {
        $edadFila = $eje ? ($eje['edad'] ?? $ed) : $ed;
        $estadoEje = ($fechaEje <= $hoy) ? 'ANOMALIA' : '';
        $filas[] = [
            'zona' => $zona,
            'subzona' => $subzona,
            'granja' => $g,
            'nomGranja' => $nomGranja,
            'campania' => $c,
            'galpon' => $gp,
            'edad' => $edadFila,
            'edadPlan' => '',
            'edadDes' => $edadFila,
            'planificado' => [],
            'ejecutado' => [$fechaEje],
            'fechaMostrar' => $fechaEje,
            'tipo' => '-',
            'estado' => $estadoEje,
            'keyBase' => $keyBase,
            'key' => $key,
        ];
    }
}

// Filtro por estado: no_cumplido incluye también ATRASADO cuyo parentKey es un NO CUMPLIO; atrasado incluye también el NO CUMPLIO relacionado (parent)
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
    } elseif ($filtroEstado === 'atrasado') {
        $parentKeysDeAtrasado = [];
        foreach ($filas as $f) {
            if ((($f['estado'] ?? '') === 'ATRASADO' || (($f['tipo'] ?? '') === '-' && isset($f['parentKey']))) && isset($f['parentKey']) && ($f['parentKey'] ?? '') !== '') {
                $parentKeysDeAtrasado[$f['parentKey']] = true;
            }
        }
        $filas = array_filter($filas, function ($f) use ($parentKeysDeAtrasado) {
            $e = $f['estado'] ?? '';
            if ($e === 'ATRASADO' || (($f['tipo'] ?? '') === '-' && isset($f['parentKey']))) return true;
            $k = $f['key'] ?? '';
            if ($k !== '' && isset($parentKeysDeAtrasado[$k])) return true;
            return false;
        });
    } else {
        $filas = array_filter($filas, function ($f) use ($filtroEstado) {
            $e = $f['estado'] ?? '';
            if ($filtroEstado === 'cumplido') return in_array($e, ['SI CUMPLIO', 'CUMPLIDO', 'REALIZADO'], true);
            if ($filtroEstado === 'realizado') return $e === 'REALIZADO';
            if ($filtroEstado === 'anomalia') return ($f['tipo'] ?? '') === '-' && ((($f['estado'] ?? '') === 'ANOMALIA') || (($f['estado'] ?? '') === 'NO CUMPLIO' && isset($f['parentKey'])));
            return true;
        });
    }
    $filas = array_values($filas);
}

$ordenComparativo = isset($_GET['orden']) ? (int)$_GET['orden'] : 2;
$ordenComparativo = ($ordenComparativo === 1) ? 1 : 2;

function compararFilasCronologico($a, $b) {
    $primeraA = $a['fechaMostrar'] ?? '';
    $primeraB = $b['fechaMostrar'] ?? '';
    if ($primeraA === '') {
        $fechasA = array_values(array_unique(array_merge($a['planificado'] ?? [], $a['ejecutado'] ?? [])));
        sort($fechasA);
        $primeraA = !empty($fechasA) ? $fechasA[0] : '';
    }
    if ($primeraB === '') {
        $fechasB = array_values(array_unique(array_merge($b['planificado'] ?? [], $b['ejecutado'] ?? [])));
        sort($fechasB);
        $primeraB = !empty($fechasB) ? $fechasB[0] : '';
    }
    $x = strcmp($primeraA, $primeraB);
    if ($x !== 0) return $x;
    $x = strcmp($a['zona'] ?? '', $b['zona'] ?? '');
    if ($x !== 0) return $x;
    $x = strcmp($a['subzona'] ?? '', $b['subzona'] ?? '');
    if ($x !== 0) return $x;
    $x = strcmp($a['granja'] ?? '', $b['granja'] ?? '');
    if ($x !== 0) return $x;
    $x = strcmp($a['campania'] ?? '', $b['campania'] ?? '');
    if ($x !== 0) return $x;
    $x = strcmp($a['galpon'] ?? '', $b['galpon'] ?? '');
    if ($x !== 0) return $x;
    return strcmp((string)($a['edad'] ?? ''), (string)($b['edad'] ?? ''));
}

usort($filas, 'compararFilasCronologico');
if ($ordenComparativo === 2) {
    // Orden 2: una fila por plan; si hay match (SI CUMPLIO o ATRASADO) plan+desarrollado en la misma fila
    // No afecta orden 1: esta transformación solo aplica cuando orden=2
    $hijosPorMergeKey = [];
    foreach ($filas as $f) {
        if (!empty($f['_mergeParent']) && isset($f['_mergeKey'])) {
            $mk = $f['_mergeKey'];
            if (!isset($hijosPorMergeKey[$mk])) $hijosPorMergeKey[$mk] = [];
            $hijosPorMergeKey[$mk][] = $f;
        }
    }
    $filasOrden2 = [];
    foreach ($filas as $f) {
        if (!empty($f['_mergeParent'])) continue;
        $mk = $f['_mergeKey'] ?? null;
        if ($mk !== null && isset($hijosPorMergeKey[$mk]) && count($hijosPorMergeKey[$mk]) > 0) {
            $hijo = $hijosPorMergeKey[$mk][0];
            $filasOrden2[] = [
                'zona' => $f['zona'] ?? '',
                'subzona' => $f['subzona'] ?? '',
                'granja' => $f['granja'] ?? '',
                'nomGranja' => $f['nomGranja'] ?? '',
                'campania' => $f['campania'] ?? '',
                'galpon' => $f['galpon'] ?? '',
                'edad' => $f['edad'] ?? '',
                'edadPlan' => $f['edadPlan'] ?? $f['edad'] ?? '',
                'edadDes' => $hijo['edadDes'] ?? $hijo['edad'] ?? '',
                'planificado' => $f['planificado'] ?? [],
                'ejecutado' => $hijo['ejecutado'] ?? [],
                'fechaMostrar' => $f['fechaMostrar'] ?? '',
                'tipo' => $f['tipo'] ?? '',
                'estado' => $hijo['estado'] ?? '',
                'keyBase' => $f['keyBase'] ?? '',
                'key' => $f['key'] ?? '',
            ];
        } else {
            $filasOrden2[] = $f;
        }
    }
    $filas = $filasOrden2;
}

// Modo debug: imprimir keys, tolerancias y noCumplioPorKeyBase
if ($modoDebug) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Debug Necropsias</title>';
    echo '<style>body{font-family:monospace;font-size:12px;padding:12px;background:#1e293b;color:#e2e8f0;} pre{background:#334155;padding:10px;border-radius:6px;overflow:auto;} h2{color:#38bdf8;} .ok{color:#4ade80;} .err{color:#f87171;}</style></head><body>';
    echo '<h1>Debug comparativo Necropsias – N°5 (NO CUMPLIO) / N°7 (debería ATRASADO)</h1>';
    if (empty($debugInfo)) {
        echo '<p class="err">No se capturó debugInfo (keys 664/062/6 con edad 17 o 18 no encontradas).</p>';
    }
    foreach ($debugInfo as $k => $v) {
        echo '<h2>' . htmlspecialchars($k) . '</h2><pre>' . htmlspecialchars(print_r($v, true)) . '</pre>';
    }
    echo '<h2>planificadosPorKey (keys con 664)</h2><pre>';
    $muestra = array_filter(array_keys($planificadosPorKey ?? []), function ($k) { return strpos($k, '664') !== false; });
    echo htmlspecialchars(print_r(array_intersect_key($planificadosPorKey ?? [], array_flip($muestra)), true)) . '</pre>';
    echo '<h2>ejecutadosPorKey (keys con 664)</h2><pre>';
    $muestra2 = array_filter(array_keys($ejecutadosPorKey ?? []), function ($k) { return strpos($k, '664') !== false; });
    echo htmlspecialchars(print_r(array_intersect_key($ejecutadosPorKey ?? [], array_flip($muestra2)), true)) . '</pre>';
    echo '</body></html>';
    exit;
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
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
}
if (empty($logo) && file_exists(__DIR__ . '/../../logo.png')) {
    $logoPath = __DIR__ . '/../../logo.png';
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
}

function formatearFechasReporte($fechas) {
    if (empty($fechas)) return '';
    $max = 12;
    $total = count($fechas);
    $lista = array_slice($fechas, 0, $max);
    $txt = implode(', ', array_map(function ($d) {
        return date('d/m/Y', strtotime($d));
    }, $lista));
    if ($total > $max) $txt .= ' ... (+' . ($total - $max) . ')';
    return $txt;
}
if (!function_exists('formatearFechasCorta')) {
    function formatearFechasCorta($fechas, $max = 3) {
        if (empty($fechas)) return '';
        $total = count($fechas);
        $lista = array_slice($fechas, 0, $max);
        $txt = implode(', ', array_map(function ($d) {
            return date('d/m/Y', strtotime($d));
        }, $lista));
        if ($total > $max) $txt .= ' ...';
        return $txt;
    }
}

$bordeTitulo = 'border: 1px solid #64748b;';
$css = '
body{font-family:"Segoe UI",Arial,sans-serif;font-size:9pt;color:#1e293b;margin:0;padding:10px;}
.fecha-hora-arriba{position:absolute;top:8px;right:0;font-size:9pt;color:#475569;z-index:10;}
.data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:1px solid #cbd5e1;}
.data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;}
.data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
.data-table .tipo-planificado{background:#dcfce7;}
.data-table .tipo-eventual{background:#fef3c7;}
.data-table .tipo-desarrollado{background:#f1f5f9;}
.data-table .celda-vacia{color:#94a3b8;}
.grupo-fecha{background:#0f172a !important;color:#fff !important;font-weight:bold;border-top:2px solid #1e293b;}
';

if (isset($GLOBALS['REPORTE_UNIFICADO_RETORNAR']) && $GLOBALS['REPORTE_UNIFICADO_RETORNAR'] === 'necropsias') {
    $theadRowNecro = ($ordenComparativo === 2)
        ? '<tr><th style="width:5%;text-align:center;">N&#176;</th><th style="width:6%">Zona</th><th style="width:6%">Subzona</th><th style="width:7%">Granja</th><th style="width:12%">Nombre granja</th><th style="width:7%">Campaña</th><th style="width:6%">Galpón</th><th style="width:4%">Edad Plan</th><th style="width:9%">Fecha planificada</th><th style="width:4%">Edad Des</th><th style="width:9%">Fecha desarrollada</th><th style="width:6%">Tipo</th><th style="width:8%">Estado</th></tr>'
        : '<tr><th style="width:5%;text-align:center;">N&#176;</th><th style="width:6%">Zona</th><th style="width:6%">Subzona</th><th style="width:7%">Granja</th><th style="width:12%">Nombre granja</th><th style="width:7%">Campaña</th><th style="width:6%">Galpón</th><th style="width:4%">Edad</th><th style="width:10%">Fecha planificada</th><th style="width:10%">Fecha desarrollada</th><th style="width:6%">Tipo</th><th style="width:8%">Estado</th></tr>';
    $GLOBALS['reporte_necropsias_data'] = ['filas' => $filas, 'rango' => $rango, 'orden' => $ordenComparativo, 'theadRow' => $theadRowNecro];
    return;
}

$tempDir = __DIR__ . '/../../../pdf_tmp';
if (!is_dir($tempDir)) {
    @mkdir($tempDir, 0775, true);
}
if (!is_dir($tempDir) || !is_writable($tempDir)) {
    $tempDir = sys_get_temp_dir();
}

try {
    if (ob_get_level()) ob_clean();
    @ini_set('max_execution_time', '0');
    @set_time_limit(0);
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
    $htmlCab .= '<tr>';
    $htmlCab .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; ' . $bordeTitulo . '">' . ($logo ? $logo . ' ' : '') . 'GRANJA RINCONADA DEL SUR S.A.</td>';
    $htmlCab .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px; ' . $bordeTitulo . '">REPORTE COMPARATIVO — ' . htmlspecialchars($fechaTitulo) . '</td>';
    $htmlCab .= '<td style="width: 20%; text-align: right; padding: 5px; background-color: #fff; font-size: 9pt; color: #475569; ' . $bordeTitulo . '">' . htmlspecialchars($fechaReporte) . '</td></tr></table>';
    if ($filasRecortadas) {
        $htmlCab .= '<div style="margin:0 0 8px 0;padding:6px 8px;border:1px solid #f59e0b;background:#fffbeb;color:#92400e;font-size:8pt;">Se muestran las primeras ' . $maxFilasPdf . ' filas para evitar desborde de memoria.</div>';
    }
    $mpdf->WriteHTML($htmlCab, \Mpdf\HTMLParserMode::HTML_BODY);

    $theadRow = ($ordenComparativo === 2)
        ? '<tr><th style="width:5%;text-align:center;">N&#176;</th><th style="width:6%">Zona</th><th style="width:6%">Subzona</th><th style="width:7%">Granja</th><th style="width:12%">Nombre granja</th><th style="width:7%">Campaña</th><th style="width:6%">Galpón</th><th style="width:4%">Edad Plan</th><th style="width:9%">Fecha planificada</th><th style="width:4%">Edad Des</th><th style="width:9%">Fecha desarrollada</th><th style="width:6%">Tipo</th><th style="width:8%">Estado</th></tr>'
        : '<tr><th style="width:5%;text-align:center;">N&#176;</th><th style="width:6%">Zona</th><th style="width:6%">Subzona</th><th style="width:7%">Granja</th><th style="width:12%">Nombre granja</th><th style="width:7%">Campaña</th><th style="width:6%">Galpón</th><th style="width:4%">Edad</th><th style="width:10%">Fecha planificada</th><th style="width:10%">Fecha desarrollada</th><th style="width:6%">Tipo</th><th style="width:8%">Estado</th></tr>';

    if (empty($filas)) {
        $mpdf->WriteHTML('<div style="margin-top:12px;margin-bottom:6px;font-weight:bold;font-size:11pt;color:#1e40af;">NECROPSIAS</div><table class="data-table"><thead>' . $theadRow . '</thead><tbody><tr><td colspan="12" style="text-align:center;color:#64748b;">No hay datos para este período y filtros.</td></tr></tbody></table>', \Mpdf\HTMLParserMode::HTML_BODY);
    } else {
        $mpdf->WriteHTML('<div style="margin-top:12px;margin-bottom:6px;font-weight:bold;font-size:11pt;color:#1e40af;">NECROPSIAS</div>', \Mpdf\HTMLParserMode::HTML_BODY);
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
                $colspan = ($ordenComparativo === 2) ? 13 : 12;
                $buf .= '<tr class="grupo-fecha"><td colspan="' . $colspan . '">NECROPSIA ' . htmlspecialchars($fechaLabel) . '</td></tr>';
            }
            $n++;
            $esPosteriorHoy = ($fechaMostrar !== '' && strtotime($fechaMostrar) > strtotime(date('Y-m-d')));
            $vacio = $esPosteriorHoy ? '' : '<span class="celda-vacia">—</span>';
            $clase = $r['tipo'] === 'Planificado' ? 'tipo-planificado' : ($r['tipo'] === 'Eventual' ? 'tipo-eventual' : ($r['tipo'] === '-' ? 'tipo-desarrollado' : ''));
            $fechaPlanTxt = formatearFechasCorta($r['planificado'] ?? [], 3);
            $fechaDesTxt = formatearFechasCorta($r['ejecutado'] ?? [], 3);
            $estado = $r['estado'] ?? '';
            $buf .= '<tr class="' . $clase . '">';
            $buf .= '<td style="text-align:center;">' . $n . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['zona']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['subzona']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['granja']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['nomGranja']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['campania']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['galpon']) . '</td>';
            if ($ordenComparativo === 2) {
                $buf .= '<td>' . htmlspecialchars($r['edadPlan'] ?? '') . '</td>';
                $buf .= '<td>' . ($fechaPlanTxt !== '' ? htmlspecialchars($fechaPlanTxt) : $vacio) . '</td>';
                $buf .= '<td>' . htmlspecialchars($r['edadDes'] ?? '') . '</td>';
                $buf .= '<td>' . ($fechaDesTxt !== '' ? htmlspecialchars($fechaDesTxt) : $vacio) . '</td>';
            } else {
                $buf .= '<td>' . htmlspecialchars($r['edad'] ?? '') . '</td>';
                $buf .= '<td>' . ($fechaPlanTxt !== '' ? htmlspecialchars($fechaPlanTxt) : $vacio) . '</td>';
                $buf .= '<td>' . ($fechaDesTxt !== '' ? htmlspecialchars($fechaDesTxt) : $vacio) . '</td>';
            }
            $buf .= '<td>' . (trim((string)($r['tipo'] ?? '')) !== '' ? htmlspecialchars($r['tipo']) : $vacio) . '</td>';
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
    $mpdf->Output('reporte_necropsias_vs_cronograma_' . $rango['desde'] . '_' . $rango['hasta'] . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
