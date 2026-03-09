<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'items' => []]);
    exit;
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($year < 2000 || $year > 2100) $year = (int)date('Y');

$rango = ['desde' => $year . '-01-01', 'hasta' => $year . '-12-31'];
$hoy = date('Y-m-d');

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
        if (ctype_digit($s)) { $n = ltrim($s, '0'); return $n === '' ? '0' : $n; }
        return $s;
    }
}

// Helpers para keyBase (alineado con comparativo necropsias)
$extractKeyBase = function ($k) {
    $p = explode('|', $k);
    return (isset($p[0]) ? $p[0] : '') . '|' . (isset($p[1]) ? $p[1] : '') . '|' . (isset($p[2]) ? $p[2] : '');
};
$canonicalKeyBase = function ($kb) {
    $p = explode('|', $kb, 3);
    $norm = function ($s) {
        $s = trim((string)$s);
        if ($s === '') return '';
        return ctype_digit($s) ? str_pad($s, 3, '0', STR_PAD_LEFT) : $s;
    };
    return $norm($p[0] ?? '') . '|' . $norm($p[1] ?? '') . '|' . $norm($p[2] ?? '');
};

include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
include_once __DIR__ . '/comparativo_unificado_util.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'year' => $year, 'items' => []]);
    exit;
}

$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;
$chkNum = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
$tieneNumCronograma = $chkNum && $chkNum->num_rows > 0;
if (!$tieneNumCronograma) {
    $conn->close();
    echo json_encode(['success' => true, 'year' => $year, 'items' => []]);
    exit;
}

// Planificado: por (numCronograma, key) -> fechas y toleranciaPorFecha; codPrograma por numCronograma
$chkToleranciaCrono = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'tolerancia'");
$tieneToleranciaCrono = $chkToleranciaCrono && $chkToleranciaCrono->num_rows > 0;

// Primero cargar todos los planificados en una estructura temporal para obtener los códigos
$planificadosTemp = [];
$sqlCrono = "SELECT c.numCronograma, c.codPrograma, c.nomPrograma, DATE(c.fechaEjecucion) AS fecha_ref, c.granja, c.campania, c.galpon"
    . ($tieneEdad ? ", c.edad" : ", '' AS edad")
    . ($tieneToleranciaCrono ? ", COALESCE(NULLIF(c.tolerancia, 0), 1) AS tolerancia" : ", 1 AS tolerancia")
    . " FROM san_fact_cronograma c"
    . " WHERE (c.numCronograma >= 0 OR c.numCronograma IS NULL)"
    . " AND DATE(c.fechaEjecucion) >= ? AND DATE(c.fechaEjecucion) <= ?"
    . " ORDER BY c.numCronograma, c.granja, c.campania, c.galpon, c.edad, c.fechaEjecucion";
$stC = $conn->prepare($sqlCrono);
if (!$stC) {
    $conn->close();
    echo json_encode(['success' => true, 'year' => $year, 'items' => []]);
    exit;
}
$stC->bind_param('ss', $rango['desde'], $rango['hasta']);
$stC->execute();
$resC = $stC->get_result();
while ($row = $resC->fetch_assoc()) {
    $num = (int)$row['numCronograma'];
    if (!isset($planificadosTemp[$num])) {
        $planificadosTemp[$num] = [
            'codPrograma' => trim((string)($row['codPrograma'] ?? '')),
            'nomPrograma' => trim((string)($row['nomPrograma'] ?? $row['codPrograma'] ?? '')),
            'rows' => []
        ];
    }
    $planificadosTemp[$num]['rows'][] = $row;
}
$stC->close();

// Obtener códigos únicos y cargar esEspecialPorCod
$codigosUnicos = [];
foreach ($planificadosTemp as $num => $data) {
    $cod = $data['codPrograma'];
    if ($cod !== '' && !in_array($cod, $codigosUnicos)) $codigosUnicos[] = $cod;
}

// Cargar siglas
$siglaPorCod = [];
if (!empty($codigosUnicos)) {
    $ph = implode(',', array_fill(0, count($codigosUnicos), '?'));
    $stSigla = $conn->prepare("SELECT cab.codigo, UPPER(TRIM(COALESCE(t.sigla,''))) AS sigla FROM san_fact_programa_cab cab LEFT JOIN san_dim_tipo_programa t ON t.codigo = cab.codTipo WHERE cab.codigo IN ($ph)");
    if ($stSigla) {
        $typesSigla = str_repeat('s', count($codigosUnicos));
        $stSigla->bind_param($typesSigla, ...$codigosUnicos);
        $stSigla->execute();
        $resSigla = $stSigla->get_result();
        while ($r = $resSigla->fetch_assoc()) {
            $cod = trim((string)($r['codigo'] ?? ''));
            $sig = trim((string)($r['sigla'] ?? ''));
            $sigNorm = ($sig === 'NEC' || $sig === 'NCS') ? 'NC' : $sig;
            if ($cod !== '') $siglaPorCod[$cod] = $sigNorm;
        }
        $stSigla->close();
    }
}

// Cargar esEspecial para construir keys correctamente (igual que comparativo)
$esEspecialPorCod = comparativo_cargar_es_especial($conn, $codigosUnicos);
$codigoNecropsia = '';
foreach ($codigosUnicos as $cod) {
    if (isset($siglaPorCod[$cod]) && $siglaPorCod[$cod] === 'NC') {
        $codigoNecropsia = $cod;
        break;
    }
}
if ($codigoNecropsia === '') $codigoNecropsia = 'NC';

// Ahora procesar los planificados con las keys correctas (mismo criterio que comparativo necropsias: key con edad para NC)
$planificadosPorNumYKey = [];
$codProgramaPorNum = [];
$nomProgramaPorNum = [];
foreach ($planificadosTemp as $num => $data) {
    $codProgramaPorNum[$num] = $data['codPrograma'];
    $nomProgramaPorNum[$num] = $data['nomPrograma'];
    $sigla = isset($siglaPorCod[$data['codPrograma']]) ? $siglaPorCod[$data['codPrograma']] : '';
    foreach ($data['rows'] as $row) {
        $g = norm_granja_3($row['granja'] ?? '');
        $c = norm_campania_3($row['campania'] ?? '');
        $gp = norm_num_text($row['galpon'] ?? '');
        $codPrograma = trim((string)($row['codPrograma'] ?? ''));
        $edad = ($sigla === 'NC' && $tieneEdad) ? norm_num_text(trim((string)($row['edad'] ?? ''))) : null;
        $key = comparativo_build_key($g, $c, $gp, $codPrograma, $esEspecialPorCod, $edad);
        $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
        $tol = max(1, (int)($row['tolerancia'] ?? 1));
        if (!isset($planificadosPorNumYKey[$num])) $planificadosPorNumYKey[$num] = [];
        if (!isset($planificadosPorNumYKey[$num][$key])) {
            $planificadosPorNumYKey[$num][$key] = ['fechas' => [], 'toleranciaPorFecha' => []];
        }
        if ($fechaRef && !in_array($fechaRef, $planificadosPorNumYKey[$num][$key]['fechas'], true)) {
            $planificadosPorNumYKey[$num][$key]['fechas'][] = $fechaRef;
            $actual = (int)($planificadosPorNumYKey[$num][$key]['toleranciaPorFecha'][$fechaRef] ?? 0);
            if ($tol > $actual) $planificadosPorNumYKey[$num][$key]['toleranciaPorFecha'][$fechaRef] = $tol;
        }
    }
}

$ejecutadosNecropsias = [];
$chkReg = @$conn->query("SHOW TABLES LIKE 't_regnecropsia'");
if ($chkReg && $chkReg->num_rows > 0) {
    $sqlReg = "SELECT DATE(r.tfectra) AS fecha_ref,
        LPAD(LEFT(TRIM(r.tgranja), 3), 3, '0') AS granja,
        LPAD(RIGHT(COALESCE(NULLIF(TRIM(r.tcampania), ''), RIGHT(TRIM(r.tgranja), 3)), 3), 3, '0') AS campania,
        TRIM(r.tgalpon) AS galpon,
        COALESCE(NULLIF(TRIM(CAST(r.tedad AS CHAR)), ''), '0') AS edad
        FROM t_regnecropsia r
        WHERE TRIM(r.tgranja) <> '' AND TRIM(r.tgalpon) <> '' AND TRIM(r.tgalpon) != '0'
        AND DATE(r.tfectra) >= ? AND DATE(r.tfectra) <= ?";
    $stR = $conn->prepare($sqlReg);
    if ($stR) {
        $stR->bind_param('ss', $rango['desde'], $rango['hasta']);
        $stR->execute();
        $resR = $stR->get_result();
        while ($row = $resR->fetch_assoc()) {
            $g = norm_granja_3($row['granja'] ?? '');
            $c = norm_campania_3($row['campania'] ?? '');
            $gp = norm_num_text($row['galpon'] ?? '');
            $e = norm_num_text($row['edad'] ?? '0');
            $key = comparativo_build_key($g, $c, $gp, $codigoNecropsia, $esEspecialPorCod, $e);
            $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
            if (!isset($ejecutadosNecropsias[$key])) $ejecutadosNecropsias[$key] = [];
            if ($fechaRef && !in_array($fechaRef, $ejecutadosNecropsias[$key], true)) $ejecutadosNecropsias[$key][] = $fechaRef;
            // Indexar también por key canónica para lookup con variantes (ej: "6" vs "006" en galpon)
            $keyBase = $g . '|' . $c . '|' . $gp;
            $keyCanon = $canonicalKeyBase($keyBase) . '|' . $e;
            if ($keyCanon !== $key) {
                if (!isset($ejecutadosNecropsias[$keyCanon])) $ejecutadosNecropsias[$keyCanon] = [];
                if ($fechaRef && !in_array($fechaRef, $ejecutadosNecropsias[$keyCanon], true)) $ejecutadosNecropsias[$keyCanon][] = $fechaRef;
            }
        }
        $stR->close();
    }
}

$ejecutadosMovi = [];
$likeMovi = ["GJ%", "VGJ%", "PL%", "VPI%", "CP%", "CDP%", "LD%", "LYD%", "MC%", "MDC%"];
$condMovi = [];
$paramsMovi = [];
$typesMovi = '';
foreach ($likeMovi as $pat) {
    $condMovi[] = "UPPER(TRIM(c.codPrograma)) LIKE UPPER(?)";
    $paramsMovi[] = $pat;
    $typesMovi .= 's';
}
$paramsMovi[] = $rango['desde'];
$paramsMovi[] = $rango['hasta'];
$typesMovi .= 'ss';
$sqlMovi = "SELECT DATE(c.fechaEjecucion) AS fecha_ref, c.granja, c.campania, c.galpon FROM san_fact_cronograma c WHERE (c.numCronograma = 0 OR c.numCronograma IS NULL) AND (" . implode(' OR ', $condMovi) . ") AND DATE(c.fechaEjecucion) >= ? AND DATE(c.fechaEjecucion) <= ?";
$stMovi = $conn->prepare($sqlMovi);
if ($stMovi && $stMovi->bind_param($typesMovi, ...$paramsMovi) && $stMovi->execute()) {
    $resMovi = $stMovi->get_result();
    while ($row = $resMovi->fetch_assoc()) {
        $g = norm_granja_3($row['granja'] ?? '');
        $c = norm_campania_3($row['campania'] ?? '');
        $gp = norm_num_text($row['galpon'] ?? '');
        $keyBase = $g . '|' . $c . '|' . $gp;
        $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
        if (!isset($ejecutadosMovi[$keyBase])) $ejecutadosMovi[$keyBase] = [];
        if ($fechaRef && !in_array($fechaRef, $ejecutadosMovi[$keyBase], true)) $ejecutadosMovi[$keyBase][] = $fechaRef;
        // Indexar también por keyBase canónico para lookup con variantes
        $keyBaseCanon = $canonicalKeyBase($keyBase);
        if ($keyBaseCanon !== $keyBase) {
            if (!isset($ejecutadosMovi[$keyBaseCanon])) $ejecutadosMovi[$keyBaseCanon] = [];
            if ($fechaRef && !in_array($fechaRef, $ejecutadosMovi[$keyBaseCanon], true)) $ejecutadosMovi[$keyBaseCanon][] = $fechaRef;
        }
    }
    $stMovi->close();
}

// CP (Control de plagas): desarrollados desde regnocontable (como en reporte comparativo MOVI)
$chkRegnocontable = @$conn->query("SHOW TABLES LIKE 'regnocontable'");
$chkTfectra = $chkRegnocontable && $chkRegnocontable->num_rows > 0 ? @$conn->query("SHOW COLUMNS FROM regnocontable LIKE 'tfectra'") : false;
$chkTfecIni = $chkRegnocontable && $chkRegnocontable->num_rows > 0 ? @$conn->query("SHOW COLUMNS FROM regnocontable LIKE 'tfec_ini'") : false;
$chkTproceso = $chkRegnocontable && $chkRegnocontable->num_rows > 0 ? @$conn->query("SHOW COLUMNS FROM regnocontable LIKE 'tproceso'") : false;
if ($chkRegnocontable && $chkRegnocontable->num_rows > 0 && $chkTproceso && $chkTproceso->num_rows > 0) {
    foreach (['PLAGAS' => 'tfectra', 'EVA_GORGOJO' => 'tfec_ini'] as $tproceso => $colFec) {
        $chkCol = @$conn->query("SHOW COLUMNS FROM regnocontable LIKE '" . $colFec . "'");
        if (!$chkCol || $chkCol->num_rows === 0) continue;
        $sqlReg = "SELECT DISTINCT DATE(r." . $colFec . ") AS fecha_ref, LEFT(TRIM(r.tcencos), 3) AS granja, RIGHT(TRIM(r.tcencos), 3) AS campania, TRIM(r.tcodint) AS galpon FROM regnocontable r WHERE UPPER(TRIM(r.tproceso)) = ? AND LENGTH(TRIM(r.tcencos)) >= 6 AND TRIM(r.tcodint) <> '' AND TRIM(r.tcodint) <> '0' AND DATE(r." . $colFec . ") >= ? AND DATE(r." . $colFec . ") <= ?";
        $stReg = $conn->prepare($sqlReg);
        if ($stReg && $stReg->bind_param('sss', $tproceso, $rango['desde'], $rango['hasta']) && $stReg->execute()) {
            $resReg = $stReg->get_result();
            while ($row = $resReg->fetch_assoc()) {
                $g = norm_granja_3($row['granja'] ?? '');
                $c = norm_campania_3($row['campania'] ?? '');
                $gp = norm_num_text($row['galpon'] ?? '');
                $keyBase = $g . '|' . $c . '|' . $gp;
                $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
                if (!isset($ejecutadosMovi[$keyBase])) $ejecutadosMovi[$keyBase] = [];
                if ($fechaRef && !in_array($fechaRef, $ejecutadosMovi[$keyBase], true)) $ejecutadosMovi[$keyBase][] = $fechaRef;
                $keyBaseCanon = $canonicalKeyBase($keyBase);
                if ($keyBaseCanon !== $keyBase) {
                    if (!isset($ejecutadosMovi[$keyBaseCanon])) $ejecutadosMovi[$keyBaseCanon] = [];
                    if ($fechaRef && !in_array($fechaRef, $ejecutadosMovi[$keyBaseCanon], true)) $ejecutadosMovi[$keyBaseCanon][] = $fechaRef;
                }
            }
            $stReg->close();
        }
    }
}

$ejecutadosMSA = [];
$ejecutadosMSB = [];
$chkSolicitud = @$conn->query("SHOW TABLES LIKE 'san_fact_solicitud_det'");
$chkFecToma = $chkSolicitud && $chkSolicitud->num_rows > 0 ? @$conn->query("SHOW COLUMNS FROM san_fact_solicitud_det LIKE 'fecToma'") : false;
$tieneFecToma = $chkFecToma && $chkFecToma->num_rows > 0;
if ($chkSolicitud && $chkSolicitud->num_rows > 0 && $tieneFecToma) {
    $codRefPad = "LPAD(TRIM(CAST(a.codRef AS CHAR)), 10, '0')";
    $granjaExpr = "LEFT($codRefPad, 3)";
    $campaniaExpr = "SUBSTRING($codRefPad, 4, 3)";
    $galponExpr = "SUBSTRING($codRefPad, 7, 2)";
    $edadNumExpr = "CAST(SUBSTRING($codRefPad, 9, 2) AS UNSIGNED)";
    $sqlMS = "SELECT DATE(a.fecToma) AS fecha_ref, $granjaExpr AS granja, $campaniaExpr AS campania, $galponExpr AS galpon FROM san_fact_solicitud_det a WHERE TRIM(a.codRef) <> '' AND LENGTH(TRIM(CAST(a.codRef AS CHAR))) >= 9 AND DATE(a.fecToma) >= ? AND DATE(a.fecToma) <= ?";
    foreach (['MSA' => '> 1', 'MSB' => '= 1'] as $tipoMS => $whereEdad) {
        $stMS = $conn->prepare($sqlMS . " AND $edadNumExpr $whereEdad");
        if ($stMS && $stMS->bind_param('ss', $rango['desde'], $rango['hasta']) && $stMS->execute()) {
            $resMS = $stMS->get_result();
            $target = ($tipoMS === 'MSA') ? $ejecutadosMSA : $ejecutadosMSB;
            while ($row = $resMS->fetch_assoc()) {
                $g = norm_granja_3($row['granja'] ?? '');
                $c = norm_campania_3($row['campania'] ?? '');
                $gp = norm_num_text($row['galpon'] ?? '');
                $keyBase = $g . '|' . $c . '|' . str_pad($gp, 2, '0', STR_PAD_LEFT);
                $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
                if (!isset($target[$keyBase])) $target[$keyBase] = [];
                if ($fechaRef && !in_array($fechaRef, $target[$keyBase], true)) $target[$keyBase][] = $fechaRef;
            }
            if ($tipoMS === 'MSA') $ejecutadosMSA = $target; else $ejecutadosMSB = $target;
            $stMS->close();
        }
    }
}

function get_ejecutados_por_tipo($num, $key, $keyBase, $siglaPorCod, $codProgramaPorNum, $ejecutadosNecropsias, $ejecutadosMovi, $ejecutadosMSA, $ejecutadosMSB, $extractKeyBase, $canonicalKeyBase) {
    $cod = isset($codProgramaPorNum[$num]) ? trim((string)$codProgramaPorNum[$num]) : '';
    $sigla = isset($siglaPorCod[$cod]) ? $siglaPorCod[$cod] : '';
    if ($sigla === 'NC') {
        $fechas = isset($ejecutadosNecropsias[$key]) ? $ejecutadosNecropsias[$key] : [];
        if (!empty($fechas)) return $fechas;
        // Intentar variantes de key (ej: "6" vs "006" en galpon)
        $kb = $extractKeyBase($key);
        $kbCanon = $canonicalKeyBase($kb);
        $partes = explode('|', $key);
        $edad = (isset($partes[3]) && $partes[3] !== '') ? $partes[3] : '';
        $keyCanon = $kbCanon . ($edad !== '' ? '|' . $edad : '');
        if ($keyCanon !== $key) {
            $fechas = isset($ejecutadosNecropsias[$keyCanon]) ? $ejecutadosNecropsias[$keyCanon] : [];
            if (!empty($fechas)) return $fechas;
        }
        $keyAlt = (isset($partes[0]) ? $partes[0] : '') . '|' . (isset($partes[1]) ? $partes[1] : '') . '|' . str_pad(trim($partes[2] ?? ''), 3, '0', STR_PAD_LEFT) . ($edad !== '' ? '|' . $edad : '');
        if ($keyAlt !== $key && $keyAlt !== $keyCanon) {
            $fechas = isset($ejecutadosNecropsias[$keyAlt]) ? $ejecutadosNecropsias[$keyAlt] : [];
            if (!empty($fechas)) return $fechas;
        }
        return [];
    }
    $p = explode('|', $keyBase, 3);
    $keyBaseMS = (count($p) >= 3) ? ($p[0] . '|' . $p[1] . '|' . str_pad(trim($p[2] ?? ''), 2, '0', STR_PAD_LEFT)) : $keyBase;
    if ($sigla === 'MSA') return isset($ejecutadosMSA[$keyBaseMS]) ? $ejecutadosMSA[$keyBaseMS] : [];
    if ($sigla === 'MSB') return isset($ejecutadosMSB[$keyBaseMS]) ? $ejecutadosMSB[$keyBaseMS] : [];
    // MOVI: intentar keyBase y keyBase canónico
    $fechas = isset($ejecutadosMovi[$keyBase]) ? $ejecutadosMovi[$keyBase] : [];
    if (!empty($fechas)) return $fechas;
    $keyBaseCanon = $canonicalKeyBase($keyBase);
    return isset($ejecutadosMovi[$keyBaseCanon]) ? $ejecutadosMovi[$keyBaseCanon] : [];
}

// Precalcular NO CUMPLIO por keyBase por num (para emparejar ejecutadas sin match con planes de otras keys, mismo keyBase)
$noCumplioPorNumYKeyBase = [];
foreach ($planificadosPorNumYKey as $num => $keysYData) {
    $noCumplioPorNumYKeyBase[$num] = [];
    $siglaNum = isset($siglaPorCod[$codProgramaPorNum[$num] ?? '']) ? $siglaPorCod[$codProgramaPorNum[$num] ?? ''] : '';
    $soloExacto = ($siglaNum === 'NC');
    foreach ($keysYData as $key => $data) {
        $fechasPlan = isset($data['fechas']) ? $data['fechas'] : [];
        $toleranciaPorFecha = isset($data['toleranciaPorFecha']) ? $data['toleranciaPorFecha'] : [];
        $keyBase = $extractKeyBase($key);
        $keyBasesToAdd = array_unique(array_filter([$keyBase, $canonicalKeyBase($keyBase)]));
        $fechasEje = get_ejecutados_por_tipo($num, $key, $keyBase, $siglaPorCod, $codProgramaPorNum, $ejecutadosNecropsias, $ejecutadosMovi, $ejecutadosMSA, $ejecutadosMSB, $extractKeyBase, $canonicalKeyBase);
        sort($fechasPlan);
        sort($fechasEje);
        $matchResult = match_plan_eje_con_tolerancia_por_fecha($fechasPlan, $toleranciaPorFecha, $fechasEje, $soloExacto);
        foreach ($matchResult['pares'] as $par) {
            if (array_key_exists('ejec', $par) && $par['ejec'] === null) {
                $tol = isset($toleranciaPorFecha[$par['plan']]) ? max(1, (int)$toleranciaPorFecha[$par['plan']]) : 1;
                $item = ['plan' => $par['plan'], 'tol' => $tol, 'key' => $key];
                foreach ($keyBasesToAdd as $kb) {
                    if ($kb === '') continue;
                    if (!isset($noCumplioPorNumYKeyBase[$num][$kb])) $noCumplioPorNumYKeyBase[$num][$kb] = [];
                    $noCumplioPorNumYKeyBase[$num][$kb][] = $item;
                }
            }
        }
    }
}

// Mismo criterio que comparativo necropsias: match con tolerancia, emparejar atrasados con plan anterior.
// Regla: ejecutada sin match solo empareja con plan NO CUMPLIO anterior (tsPlan < tsEje), nunca con plan posterior.
// Para NC: incluir keys que solo tienen ejecutados (mismo keyBase) para emparejar ATRASADOS; evitar doble conteo.
// Dos pasadas: (1) procesar y acumular planUsadoDesdeOtraKey, (2) contar excluyendo planes usados desde otra key.
$ejecutadasUsadasPorKey = []; // key => [ fecha => true ] para no contar la misma ejecutada en varios nums
$contadoresPorNum = [];
foreach ($planificadosPorNumYKey as $num => $keysYData) {
    $contadoresPorNum[$num] = ['cumplido' => 0, 'atrasado' => 0, 'no_cumplido' => 0, 'pendiente' => 0, 'granjas' => []];
    $siglaNum = isset($siglaPorCod[$codProgramaPorNum[$num] ?? '']) ? $siglaPorCod[$codProgramaPorNum[$num] ?? ''] : '';
    $soloExacto = ($siglaNum === 'NC');
    $planUsadoDesdeOtraKeyNum = [];
    $keysAProcesar = $keysYData;
    if ($siglaNum === 'NC' && !empty($ejecutadosNecropsias)) {
        $keyBasesDelNum = [];
        foreach (array_keys($keysYData) as $k) {
            $keyBasesDelNum[$canonicalKeyBase($extractKeyBase($k))] = true;
        }
        foreach (array_keys($ejecutadosNecropsias) as $keyEje) {
            $kbCanon = $canonicalKeyBase($extractKeyBase($keyEje));
            if (isset($keyBasesDelNum[$kbCanon]) && !isset($keysAProcesar[$keyEje])) {
                $keysAProcesar[$keyEje] = ['fechas' => [], 'toleranciaPorFecha' => []];
            }
        }
    }
    $resultadosPorKey = [];
    foreach ($keysAProcesar as $key => $data) {
        $fechasPlan = isset($data['fechas']) ? $data['fechas'] : [];
        $toleranciaPorFecha = isset($data['toleranciaPorFecha']) ? $data['toleranciaPorFecha'] : [];
        $partes = explode('|', $key);
        $keyBase = $extractKeyBase($key);
        $g = isset($partes[0]) ? trim((string)$partes[0]) : '';
        $c = isset($partes[1]) ? trim((string)$partes[1]) : '';
        $gp = isset($partes[2]) ? trim((string)$partes[2]) : '';
        $fechasEje = get_ejecutados_por_tipo($num, $key, $keyBase, $siglaPorCod, $codProgramaPorNum, $ejecutadosNecropsias, $ejecutadosMovi, $ejecutadosMSA, $ejecutadosMSB, $extractKeyBase, $canonicalKeyBase);
        if ($siglaNum === 'NC' && !empty($ejecutadasUsadasPorKey[$key] ?? [])) {
            $fechasEje = array_values(array_filter($fechasEje, function ($f) use ($key, $ejecutadasUsadasPorKey) {
                return !isset($ejecutadasUsadasPorKey[$key][$f]);
            }));
        }
        sort($fechasPlan);
        sort($fechasEje);
        $matchResult = match_plan_eje_con_tolerancia_por_fecha($fechasPlan, $toleranciaPorFecha, $fechasEje, $soloExacto);
        $keyBaseCanon = $canonicalKeyBase($keyBase);
        $keyBaseAlt = $g . '|' . $c . '|' . $gp;
        $lookupBases = array_unique(array_filter([$keyBase, $keyBaseAlt, $keyBaseCanon, $canonicalKeyBase($keyBaseAlt)]));
        foreach (array_keys($noCumplioPorNumYKeyBase[$num] ?? []) as $kb) {
            if ($canonicalKeyBase($kb) === $keyBaseCanon) $lookupBases[] = $kb;
        }
        $lookupBases = array_unique(array_filter($lookupBases));
        $seenPlan = [];
        $noCumplioExtras = [];
        foreach ($lookupBases as $lb) {
            foreach ($noCumplioPorNumYKeyBase[$num][$lb] ?? [] as $item) {
                $pk = ($item['plan'] ?? '') . '|' . ($item['key'] ?? '');
                if (!isset($seenPlan[$pk])) {
                    $seenPlan[$pk] = true;
                    $noCumplioExtras[] = $item;
                }
            }
        }
        if (empty($noCumplioExtras) && !empty($matchResult['ejecutadasSinMatch'])) {
            $keyBaseParaMatch = $keyBase;
            foreach ($keysYData as $pkKey => $pkData) {
                $pkKeyBase = $extractKeyBase($pkKey);
                if ($canonicalKeyBase($pkKeyBase) !== $canonicalKeyBase($keyBaseParaMatch)) continue;
                $pkFechasPlan = $pkData['fechas'] ?? [];
                $pkTol = $pkData['toleranciaPorFecha'] ?? [];
                $pkFechasEje = get_ejecutados_por_tipo($num, $pkKey, $pkKeyBase, $siglaPorCod, $codProgramaPorNum, $ejecutadosNecropsias, $ejecutadosMovi, $ejecutadosMSA, $ejecutadosMSB, $extractKeyBase, $canonicalKeyBase);
                sort($pkFechasPlan);
                sort($pkFechasEje);
                $pkMatch = match_plan_eje_con_tolerancia_por_fecha($pkFechasPlan, $pkTol, $pkFechasEje, $soloExacto);
                foreach ($pkMatch['pares'] as $par) {
                    if (array_key_exists('ejec', $par) && $par['ejec'] === null) {
                        $tol = isset($pkTol[$par['plan']]) ? max(1, (int)$pkTol[$par['plan']]) : 1;
                        $noCumplioExtras[] = ['plan' => $par['plan'], 'tol' => $tol, 'key' => $pkKey];
                    }
                }
            }
        }
        $emparejado = emparejar_atrasados_con_plan_anterior($matchResult['pares'], $matchResult['ejecutadasSinMatch'], $toleranciaPorFecha, $noCumplioExtras);
        foreach ($emparejado['planUsadoDesdeOtraKey'] ?? [] as $pk => $fechas) {
            foreach ($fechas as $fp => $v) {
                if (!isset($planUsadoDesdeOtraKeyNum[$pk])) $planUsadoDesdeOtraKeyNum[$pk] = [];
                $planUsadoDesdeOtraKeyNum[$pk][$fp] = true;
            }
        }
        if ($siglaNum === 'NC') {
            foreach ($emparejado['pares'] as $par) {
                if (!empty($par['atrasado']) && isset($par['ejec'])) {
                    if (!isset($ejecutadasUsadasPorKey[$key])) $ejecutadasUsadasPorKey[$key] = [];
                    $ejecutadasUsadasPorKey[$key][$par['ejec']] = true;
                }
            }
        }
        $resultadosPorKey[$key] = ['pares' => $emparejado['pares'], 'ejecutadasSinMatch' => $emparejado['ejecutadasSinMatch'], 'g' => $g];
    }
    foreach ($resultadosPorKey as $key => $res) {
        $pares = $res['pares'];
        $ejecutadasSinMatch = $res['ejecutadasSinMatch'];
        $g = $res['g'];
        if ($g !== '') $contadoresPorNum[$num]['granjas'][$g] = true;
        foreach ($pares as $par) {
            $fechaPlan = $par['plan'];
            $fechaEje = isset($par['ejec']) ? $par['ejec'] : null;
            $planKeyPar = isset($par['planKey']) ? $par['planKey'] : $key;
            if ($fechaEje !== null) {
                if (!empty($par['atrasado'])) {
                    $contadoresPorNum[$num]['atrasado']++;
                } else {
                    $contadoresPorNum[$num]['cumplido']++;
                }
            } else {
                if (isset($planUsadoDesdeOtraKeyNum[$planKeyPar][$fechaPlan])) continue;
                if ($fechaPlan <= $hoy) {
                    $contadoresPorNum[$num]['no_cumplido']++;
                } else {
                    $contadoresPorNum[$num]['pendiente']++;
                }
            }
        }
        foreach ($ejecutadasSinMatch as $fechaEje) {
            if ($fechaEje <= $hoy) $contadoresPorNum[$num]['no_cumplido']++;
        }
    }
}

$items = [];
foreach ($contadoresPorNum as $num => $cnt) {
    $cumplido = $cnt['cumplido'];
    $atrasado = $cnt['atrasado'];
    $no_cumplido = $cnt['no_cumplido'];
    $pendiente = $cnt['pendiente'];
    $total = $cumplido + $atrasado + $no_cumplido + $pendiente;
    $tasa = $total > 0 ? round(($cumplido + $atrasado) / $total * 100, 1) : null;
    $cod = $codProgramaPorNum[$num] ?? '';
    $sigla = isset($siglaPorCod[$cod]) ? trim((string)$siglaPorCod[$cod]) : '';
    $items[] = [
        'codPrograma' => $cod,
        'nomPrograma' => $nomProgramaPorNum[$num] ?? $cod ?? '',
        'sigla' => $sigla,
        'numCronograma' => $num,
        'granjas' => array_keys($cnt['granjas'] ?? []),
        'total' => $total,
        'cumplido' => $cumplido,
        'atrasado' => $atrasado,
        'noCumplido' => $no_cumplido,
        'pendiente' => $pendiente,
        'tasa' => $tasa
    ];
}

// Ordenar por numCronograma
usort($items, function ($a, $b) { return $a['numCronograma'] - $b['numCronograma']; });

$conn->close();

echo json_encode([
    'success' => true,
    'year' => $year,
    'items' => $items
]);
