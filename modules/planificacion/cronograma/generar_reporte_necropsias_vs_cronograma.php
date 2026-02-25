<?php
session_start();
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

function norm_granja_3($v) {
    $s = trim((string)$v);
    if ($s === '') return '';
    if (strlen($s) >= 3) return substr($s, 0, 3);
    return str_pad($s, 3, '0', STR_PAD_LEFT);
}
function norm_campania_3($camp, $fallback = '') {
    $s = trim((string)$camp);
    if ($s === '') $s = trim((string)$fallback);
    if ($s === '') return '';
    if (ctype_digit($s)) return substr(str_pad($s, 3, '0', STR_PAD_LEFT), -3);
    return $s;
}
function norm_num_text($v) {
    $s = trim((string)$v);
    if ($s === '') return '';
    if (ctype_digit($s)) {
        $n = ltrim($s, '0');
        return $n === '' ? '0' : $n;
    }
    return $s;
}

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    exit('Error de conexión');
}

// Normalizar granja(s) a 3 caracteres para filtro
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

// Cronograma tipo Necropsias: agrupar por (granja,campania,galpon,edad) -> fechas planificadas (fechaEjecucion)
$sqlCronoBase = "SELECT DATE(c.fechaEjecucion) AS fecha_ref, c.granja, c.campania, c.galpon"
    . ($tieneEdad ? ", c.edad" : "")
    . ($tieneNomGranja ? ", c.nomGranja" : "")
    . ($tieneZona ? ", c.zona" : "")
    . ($tieneSubzona ? ", c.subzona" : "")
    . "
    FROM san_fact_cronograma c";
$whereCrono = [" UPPER(TRIM(c.codPrograma)) LIKE 'NC%'"];
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
    $key = $g . '|' . $c . '|' . $gp . '|' . $e;
    $nomGranja = $tieneNomGranja ? trim((string)($row['nomGranja'] ?? '')) : '';
    $zona = $tieneZona ? trim((string)($row['zona'] ?? '')) : '';
    $subzona = $tieneSubzona ? trim((string)($row['subzona'] ?? '')) : '';
    if (!isset($planificadosPorKey[$key])) {
        $planificadosPorKey[$key] = [
            'granja' => $g,
            'campania' => $c,
            'galpon' => $gp,
            'edad' => $e,
            'nomGranja' => $nomGranja,
            'zona' => $zona,
            'subzona' => $subzona,
            'fechas' => []
        ];
    } else {
        if ($planificadosPorKey[$key]['nomGranja'] === '' && $nomGranja !== '') $planificadosPorKey[$key]['nomGranja'] = $nomGranja;
        if ($planificadosPorKey[$key]['zona'] === '' && $zona !== '') $planificadosPorKey[$key]['zona'] = $zona;
        if ($planificadosPorKey[$key]['subzona'] === '' && $subzona !== '') $planificadosPorKey[$key]['subzona'] = $subzona;
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
    $rowsChunk++;
    }
    $stmtCrono->close();
    $offsetCrono += $CHUNK_SIZE;
} while ($rowsChunk >= $CHUNK_SIZE);

// Necropsias: agrupar por (granja,campania,galpon,edad) -> fechas ejecutadas (tfectra)
$chkTfectra = @$conn->query("SHOW COLUMNS FROM t_regnecropsia LIKE 'tfectra'");
if (!$chkTfectra || $chkTfectra->num_rows === 0) {
    $conn->close();
    exit('Tabla t_regnecropsia sin columna tfectra.');
}
$sqlNecroBase = "SELECT DATE(tfectra) AS fecha_ref, tgranja, tgalpon, tcampania, tedad, tcencos
    FROM t_regnecropsia";
$whereNecro = [];
$paramsNecro = [];
$typesNecro = '';
if (!$esTodos) {
    $whereNecro[] = " DATE(tfectra) >= ? AND DATE(tfectra) <= ?";
    $paramsNecro[] = $rango['desde'];
    $paramsNecro[] = $rango['hasta'];
    $typesNecro .= 'ss';
}
if (count($filtroGranjasNorm) > 0) {
    $phGN = implode(',', array_fill(0, count($filtroGranjasNorm), '?'));
    $whereNecro[] = " LEFT(TRIM(tgranja), 3) IN ($phGN)";
    foreach ($filtroGranjasNorm as $gN) { $paramsNecro[] = $gN; $typesNecro .= 's'; }
}
if (count($filtroGalpones) > 0) {
    $phGpn = implode(',', array_fill(0, count($filtroGalpones), '?'));
    $whereNecro[] = " CAST(TRIM(tgalpon) AS UNSIGNED) IN ($phGpn)";
    foreach ($filtroGalpones as $fgp) {
        $paramsNecro[] = $fgp;
        $typesNecro .= 's';
    }
}
if (count($filtroCampanias) > 0) {
    $placeholders = implode(',', array_fill(0, count($filtroCampanias), '?'));
    $whereNecro[] = " (TRIM(tcampania) IN ($placeholders) OR RIGHT(TRIM(tgranja), 3) IN ($placeholders))";
    foreach ($filtroCampanias as $fc) { $paramsNecro[] = $fc; $typesNecro .= 's'; }
    foreach ($filtroCampanias as $fc) { $paramsNecro[] = $fc; $typesNecro .= 's'; }
}
if (count($filtroEdades) > 0) {
    $placeholdersEdad = implode(',', array_fill(0, count($filtroEdades), '?'));
    $whereNecro[] = " CAST(TRIM(CAST(tedad AS CHAR)) AS UNSIGNED) IN ($placeholdersEdad)";
    foreach ($filtroEdades as $fe) { $paramsNecro[] = $fe; $typesNecro .= 's'; }
}
$ejecutadosPorKey = [];
$offsetNecro = 0;
do {
    $sqlNecro = $sqlNecroBase . (count($whereNecro) > 0 ? " WHERE" . implode(" AND", $whereNecro) : "");
    $sqlNecro .= " ORDER BY tgranja, tgalpon, tcampania, tedad, tfectra LIMIT " . (int)$CHUNK_SIZE . " OFFSET " . (int)$offsetNecro;
    $stmtNecro = $conn->prepare($sqlNecro);
    if (count($paramsNecro) > 0) {
        $stmtNecro->bind_param($typesNecro, ...$paramsNecro);
    }
    $stmtNecro->execute();
    $resNecro = $stmtNecro->get_result();
    $rowsChunkN = 0;
    while ($row = $resNecro->fetch_assoc()) {
    $tgranja = trim((string)($row['tgranja'] ?? ''));
    $granja = norm_granja_3($tgranja);
    $campania = norm_campania_3($row['tcampania'] ?? '', strlen($tgranja) >= 3 ? substr($tgranja, -3) : '');
    $galpon = norm_num_text($row['tgalpon'] ?? '');
    $edad = $row['tedad'] !== null && $row['tedad'] !== '' ? norm_num_text($row['tedad']) : '';
    $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
    $key = $granja . '|' . $campania . '|' . $galpon . '|' . $edad;
    if (!isset($ejecutadosPorKey[$key])) $ejecutadosPorKey[$key] = ['granja' => $granja, 'campania' => $campania, 'galpon' => $galpon, 'edad' => $edad, 'fechas' => [], 'nomGranja' => ''];
    if ($fechaRef && !in_array($fechaRef, $ejecutadosPorKey[$key]['fechas'], true)) {
        $ejecutadosPorKey[$key]['fechas'][] = $fechaRef;
    }
    // Nombre granja desde tcencos: mostrar hasta antes de "C=" (ej: "Granja mi granja C=" -> "Granja mi granja")
    $tcencos = trim((string)($row['tcencos'] ?? ''));
    if ($tcencos !== '' && ($ejecutadosPorKey[$key]['nomGranja'] ?? '') === '') {
        $posC = stripos($tcencos, 'C=');
        $nomDesdeTcencos = ($posC !== false) ? trim(substr($tcencos, 0, $posC)) : $tcencos;
        if ($nomDesdeTcencos !== '') $ejecutadosPorKey[$key]['nomGranja'] = $nomDesdeTcencos;
    }
    $rowsChunkN++;
    }
    $stmtNecro->close();
    $offsetNecro += $CHUNK_SIZE;
} while ($rowsChunkN >= $CHUNK_SIZE);

// Para granjas que solo están en t_regnecropsia: obtener zona y subzona desde pi_dim_detalles
$granjasSoloNecro = [];
foreach (array_keys($ejecutadosPorKey) as $key) {
    $g = $ejecutadosPorKey[$key]['granja'] ?? '';
    if ($g === '') continue;
    $meta = $metaPorGranja[$g] ?? ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
    if ((trim($meta['zona'] ?? '') === '' && trim($meta['subzona'] ?? '') === '')) {
        $granjasSoloNecro[$g] = true;
    }
}
$granjasSoloNecro = array_keys($granjasSoloNecro);
if (count($granjasSoloNecro) > 0) {
    $chkPi = @$conn->query("SHOW TABLES LIKE 'pi_dim_detalles'");
    if ($chkPi && $chkPi->num_rows > 0) {
        $ph = implode(',', array_fill(0, count($granjasSoloNecro), '?'));
        $sqlZs = "SELECT TRIM(det.id_granja) AS codigo,
            MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'ZONA' THEN TRIM(det.dato) END) AS zona,
            MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'SUBZONA' THEN TRIM(det.dato) END) AS subzona
            FROM pi_dim_detalles det
            INNER JOIN pi_dim_caracteristicas car ON car.id = det.id_caracteristica
            WHERE TRIM(det.id_granja) IN ($ph) AND UPPER(TRIM(car.nombre)) IN ('ZONA', 'SUBZONA')
            GROUP BY TRIM(det.id_granja)";
        $stZs = $conn->prepare($sqlZs);
        if ($stZs) {
            $typesZs = str_repeat('s', count($granjasSoloNecro));
            $stZs->bind_param($typesZs, ...$granjasSoloNecro);
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
}
$conn->close();

// Unir todas las claves: una fila por (granja, campania, galpon, edad); si coinciden planificado y desarrollado se ve en la misma fila
$todasLasClaves = array_keys($planificadosPorKey + $ejecutadosPorKey);
$filas = [];
foreach ($todasLasClaves as $key) {
    $plan = $planificadosPorKey[$key] ?? null;
    $eje = $ejecutadosPorKey[$key] ?? null;
    $g = $plan ? $plan['granja'] : $eje['granja'];
    $c = $plan ? $plan['campania'] : $eje['campania'];
    $gp = $plan ? $plan['galpon'] : $eje['galpon'];
    $ed = $plan ? $plan['edad'] : $eje['edad'];
    $fechasPlan = $plan ? $plan['fechas'] : [];
    $fechasEje = $eje ? $eje['fechas'] : [];
    sort($fechasPlan);
    sort($fechasEje);
    $interseccion = array_values(array_intersect($fechasPlan, $fechasEje));
    $metaG = $metaPorGranja[$g] ?? ['nomGranja' => '', 'zona' => '', 'subzona' => ''];
    $nomGranja = trim((string)($plan['nomGranja'] ?? $metaG['nomGranja'] ?? ''));
    if ($nomGranja === '' && $eje && !empty($eje['nomGranja'])) {
        $nomGranja = trim((string)$eje['nomGranja']);
    }
    $zona = trim((string)($plan['zona'] ?? $metaG['zona'] ?? ''));
    $subzona = trim((string)($plan['subzona'] ?? $metaG['subzona'] ?? ''));
    $tipo = (count($interseccion) > 0) ? 'Planificado' : (count($fechasEje) > 0 ? 'Eventual' : '');
    $filas[] = [
        'zona' => $zona,
        'subzona' => $subzona,
        'granja' => $g,
        'nomGranja' => $nomGranja,
        'campania' => $c,
        'galpon' => $gp,
        'edad' => $ed,
        'planificado' => $fechasPlan,
        'ejecutado' => $fechasEje,
        'tipo' => $tipo,
    ];
}
// Ordenar: zona, subzona, fecha (más antigua primero), granja, campaña, galpón, edad
usort($filas, function ($a, $b) {
    $x = strcmp($a['zona'], $b['zona']);
    if ($x !== 0) return $x;
    $x = strcmp($a['subzona'], $b['subzona']);
    if ($x !== 0) return $x;
    $fechasA = array_values(array_unique(array_merge($a['planificado'], $a['ejecutado'])));
    $fechasB = array_values(array_unique(array_merge($b['planificado'], $b['ejecutado'])));
    sort($fechasA);
    sort($fechasB);
    $primeraA = !empty($fechasA) ? $fechasA[0] : '';
    $primeraB = !empty($fechasB) ? $fechasB[0] : '';
    $x = strcmp($primeraA, $primeraB);
    if ($x !== 0) return $x;
    $x = strcmp($a['granja'], $b['granja']);
    if ($x !== 0) return $x;
    $x = strcmp($a['campania'], $b['campania']);
    if ($x !== 0) return $x;
    $x = strcmp($a['galpon'], $b['galpon']);
    if ($x !== 0) return $x;
    return strcmp((string)$a['edad'], (string)$b['edad']);
});

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

$bordeTitulo = 'border: 1px solid #64748b;';
$css = '
body{font-family:"Segoe UI",Arial,sans-serif;font-size:9pt;color:#1e293b;margin:0;padding:10px;}
.fecha-hora-arriba{position:absolute;top:8px;right:0;font-size:9pt;color:#475569;z-index:10;}
.data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:1px solid #cbd5e1;}
.data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;}
.data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
.data-table .tipo-planificado{background:#dcfce7;}
.data-table .tipo-eventual{background:#fef3c7;}
.data-table .celda-vacia{color:#94a3b8;}
.grupo-zona{background:#dbeafe !important;font-weight:bold;border-top:2px solid #2563eb;}
.grupo-subzona{background:#e0e7ff !important;font-weight:600;}
';

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
    $mpdf->SetFooter('<div style="text-align:center;font-size:9pt;font-weight:normal;">{PAGENO} de {nbpg}</div>');

    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

    $htmlCab = '<table width="100%" style="border-collapse: collapse; margin-bottom: 10px; margin-top: 8px; ' . $bordeTitulo . '">';
    $htmlCab .= '<tr>';
    $htmlCab .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; ' . $bordeTitulo . '">' . ($logo ? $logo . ' ' : '') . 'GRANJA RINCONADA DEL SUR S.A.</td>';
    $htmlCab .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px; ' . $bordeTitulo . '">REPORTE NECROPSIAS VS CRONOGRAMA — ' . htmlspecialchars($fechaTitulo) . '</td>';
    $htmlCab .= '<td style="width: 20%; text-align: right; padding: 5px; background-color: #fff; font-size: 9pt; color: #475569; ' . $bordeTitulo . '">' . htmlspecialchars($fechaReporte) . '</td></tr></table>';
    if ($filasRecortadas) {
        $htmlCab .= '<div style="margin:0 0 8px 0;padding:6px 8px;border:1px solid #f59e0b;background:#fffbeb;color:#92400e;font-size:8pt;">Se muestran las primeras ' . $maxFilasPdf . ' filas para evitar desborde de memoria.</div>';
    }
    $mpdf->WriteHTML($htmlCab, \Mpdf\HTMLParserMode::HTML_BODY);

    $thead = '<table class="data-table"><thead><tr>'
        . '<th style="width:5%;text-align:center;">N&#176;</th><th style="width:7%">Zona</th><th style="width:7%">Subzona</th><th style="width:8%">Granja</th><th style="width:14%">Nombre granja</th><th style="width:8%">Campaña</th><th style="width:7%">Galpón</th><th style="width:5%">Edad</th><th style="width:16%">Planificado</th><th style="width:16%">Desarrollado</th><th style="width:6%">Tipo</th>'
        . '</tr></thead><tbody>';
    $mpdf->WriteHTML($thead, \Mpdf\HTMLParserMode::HTML_BODY);

    if (empty($filas)) {
        $mpdf->WriteHTML('<tr><td colspan="11" style="text-align:center;color:#64748b;">No hay datos para este período y filtros.</td></tr>', \Mpdf\HTMLParserMode::HTML_BODY);
    } else {
        $chunkRows = 300;
        $buf = '';
        $n = 0;
        $zonaAnt = '';
        $subzonaAnt = '';
        foreach ($filas as $r) {
            if ($r['zona'] !== $zonaAnt) {
                $zonaAnt = $r['zona'];
                $subzonaAnt = '';
                $buf .= '<tr class="grupo-zona"><td colspan="11">Zona: ' . htmlspecialchars($r['zona'] ?: 'Sin zona') . '</td></tr>';
            }
            if ($r['subzona'] !== $subzonaAnt) {
                $subzonaAnt = $r['subzona'];
                $buf .= '<tr class="grupo-subzona"><td colspan="11">Subzona: ' . htmlspecialchars($r['subzona'] ?: 'Sin subzona') . '</td></tr>';
            }
            $n++;
            $clase = $r['tipo'] === 'Planificado' ? 'tipo-planificado' : ($r['tipo'] === 'Eventual' ? 'tipo-eventual' : '');
            $planTxt = formatearFechasReporte($r['planificado']);
            $ejeTxt = formatearFechasReporte($r['ejecutado']);
            $buf .= '<tr class="' . $clase . '">';
            $buf .= '<td style="text-align:center;">' . $n . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['zona']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['subzona']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['granja']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['nomGranja']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['campania']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['galpon']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['edad']) . '</td>';
            $buf .= '<td>' . ($planTxt !== '' ? htmlspecialchars($planTxt) : '<span class="celda-vacia">—</span>') . '</td>';
            $buf .= '<td>' . ($ejeTxt !== '' ? htmlspecialchars($ejeTxt) : '<span class="celda-vacia">—</span>') . '</td>';
            $buf .= '<td>' . htmlspecialchars($r['tipo']) . '</td>';
            $buf .= '</tr>';
            if ($n % $chunkRows === 0) {
                $mpdf->WriteHTML($buf, \Mpdf\HTMLParserMode::HTML_BODY);
                $buf = '';
            }
        }
        if ($buf !== '') $mpdf->WriteHTML($buf, \Mpdf\HTMLParserMode::HTML_BODY);
    }
    $mpdf->WriteHTML('</tbody></table>', \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output('reporte_necropsias_vs_cronograma_' . $rango['desde'] . '_' . $rango['hasta'] . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
