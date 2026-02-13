<?php
session_start();
if (empty($_SESSION['active'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('No autorizado');
}

$periodoTipo = trim((string)($_GET['periodoTipo'] ?? ''));
$fechaUnica = trim((string)($_GET['fechaUnica'] ?? ''));
$fechaInicio = trim((string)($_GET['fechaInicio'] ?? ''));
$fechaFin = trim((string)($_GET['fechaFin'] ?? ''));
$mesUnico = trim((string)($_GET['mesUnico'] ?? ''));
$mesInicio = trim((string)($_GET['mesInicio'] ?? ''));
$mesFin = trim((string)($_GET['mesFin'] ?? ''));
$codTipo = trim((string)($_GET['codTipo'] ?? ''));
$porFechaEjecucion = !empty($_GET['porFechaEjecucion']);

$rango = null;
if ($periodoTipo !== '' && $periodoTipo !== 'TODOS') {
    if (is_file(__DIR__ . '/../../../../includes/filtro_periodo_util.php')) {
        include_once __DIR__ . '/../../../../includes/filtro_periodo_util.php';
        $rango = periodo_a_rango([
            'periodoTipo' => $periodoTipo, 'fechaUnica' => $fechaUnica, 'fechaInicio' => $fechaInicio, 'fechaFin' => $fechaFin,
            'mesUnico' => $mesUnico, 'mesInicio' => $mesInicio, 'mesFin' => $mesFin
        ]);
    } else {
        if ($periodoTipo === 'POR_FECHA' && $fechaUnica !== '') $rango = ['desde' => $fechaUnica, 'hasta' => $fechaUnica];
        elseif ($periodoTipo === 'ENTRE_FECHAS' && $fechaInicio !== '' && $fechaFin !== '') $rango = ['desde' => $fechaInicio, 'hasta' => $fechaFin];
        elseif ($periodoTipo === 'POR_MES' && preg_match('/^\d{4}-\d{2}$/', $mesUnico)) $rango = ['desde' => $mesUnico . '-01', 'hasta' => date('Y-m-t', strtotime($mesUnico . '-01'))];
        elseif ($periodoTipo === 'ENTRE_MESES' && preg_match('/^\d{4}-\d{2}$/', $mesInicio) && preg_match('/^\d{4}-\d{2}$/', $mesFin)) {
            $rango = ['desde' => $mesInicio . '-01', 'hasta' => date('Y-m-t', strtotime($mesFin . '-01'))];
        } elseif ($periodoTipo === 'ULTIMA_SEMANA') {
            $rango = ['desde' => date('Y-m-d', strtotime('-6 days')), 'hasta' => date('Y-m-d')];
        }
    }
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) exit('Error de conexión');

$chk = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
$tieneNomGranja = $chk && $chk->num_rows > 0;
$chk2 = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneEdad = $chk2 && $chk2->num_rows > 0;
$chk3 = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'posDetalle'");
$tienePosDetalle = $chk3 && $chk3->num_rows > 0;
$chkFhr = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fechaHoraRegistro'");
$tieneFechaHoraRegistro = $chkFhr && $chkFhr->num_rows > 0;
$chkZona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'zona'");
$tieneZona = $chkZona && $chkZona->num_rows > 0;
$chkSubzona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'subzona'");
$tieneSubzona = $chkSubzona && $chkSubzona->num_rows > 0;
$chkNumCrono = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
$tieneNumCronograma = $chkNumCrono && $chkNumCrono->num_rows > 0;

$joinTipo = '';
$whereTipo = '';
$params = [];
$types = '';

if ($codTipo !== '') {
    $joinTipo = " INNER JOIN san_fact_programa_cab cab ON cab.codigo = c.codPrograma AND cab.codTipo = ? ";
    $whereTipo = " 1=1 ";
    $params[] = $codTipo;
    $types .= 's';
}

$sql = "SELECT c.codPrograma, c.nomPrograma, c.granja, c.campania, c.galpon, c.fechaCarga, c.fechaEjecucion";
if ($tieneNomGranja) $sql .= ", c.nomGranja";
if ($tieneEdad) $sql .= ", c.edad";
if ($tienePosDetalle) $sql .= ", c.posDetalle";
if ($tieneZona) $sql .= ", c.zona";
if ($tieneSubzona) $sql .= ", c.subzona";
if ($tieneNumCronograma) $sql .= ", c.numCronograma";
$sql .= " FROM san_fact_cronograma c";
$sql .= $joinTipo;
$sql .= " WHERE " . ($whereTipo ?: " 1=1 ");

if ($rango !== null && isset($rango['desde'], $rango['hasta'])) {
    $campoFechaFiltro = ($porFechaEjecucion || !$tieneFechaHoraRegistro) ? 'c.fechaEjecucion' : 'c.fechaHoraRegistro';
    $sql .= " AND DATE(" . $campoFechaFiltro . ") >= ? AND DATE(" . $campoFechaFiltro . ") <= ? ";
    $params[] = $rango['desde'];
    $params[] = $rango['hasta'];
    $types .= 'ss';
}

$sql .= " ORDER BY " . ($tieneNumCronograma ? "c.numCronograma ASC, " : "") . "c.codPrograma, c.granja, c.campania, c.galpon, c.fechaEjecucion ASC";

$filas = [];
if (count($params) > 0) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $filas[] = [
                'codPrograma' => $row['codPrograma'] ?? '',
                'nomPrograma' => $row['nomPrograma'] ?? '',
                'granja' => $row['granja'] ?? '',
                'nomGranja' => $tieneNomGranja ? ($row['nomGranja'] ?? '') : ($row['granja'] ?? ''),
                'campania' => $row['campania'] ?? '',
                'galpon' => $row['galpon'] ?? '',
                'edad' => $tieneEdad ? ($row['edad'] ?? '') : '',
                'posDetalle' => $tienePosDetalle ? ($row['posDetalle'] ?? '') : '',
                'fechaCarga' => $row['fechaCarga'] ?? '',
                'fechaEjecucion' => $row['fechaEjecucion'] ?? '',
                'zona' => $tieneZona ? ($row['zona'] ?? '') : '',
                'subzona' => $tieneSubzona ? ($row['subzona'] ?? '') : '',
                'numCronograma' => $tieneNumCronograma ? (int)($row['numCronograma'] ?? 0) : 0,
            ];
        }
        $stmt->close();
    }
} else {
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $filas[] = [
                'codPrograma' => $row['codPrograma'] ?? '',
                'nomPrograma' => $row['nomPrograma'] ?? '',
                'granja' => $row['granja'] ?? '',
                'nomGranja' => $tieneNomGranja ? ($row['nomGranja'] ?? '') : ($row['granja'] ?? ''),
                'campania' => $row['campania'] ?? '',
                'galpon' => $row['galpon'] ?? '',
                'edad' => $tieneEdad ? ($row['edad'] ?? '') : '',
                'posDetalle' => $tienePosDetalle ? ($row['posDetalle'] ?? '') : '',
                'fechaCarga' => $row['fechaCarga'] ?? '',
                'fechaEjecucion' => $row['fechaEjecucion'] ?? '',
                'zona' => $tieneZona ? ($row['zona'] ?? '') : '',
                'subzona' => $tieneSubzona ? ($row['subzona'] ?? '') : '',
                'numCronograma' => $tieneNumCronograma ? (int)($row['numCronograma'] ?? 0) : 0,
            ];
        }
    }
}
$conn->close();

function fechaDDMMYYYY($s) {
    if ($s === null || $s === '') return '';
    $s = trim((string)$s);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(\s+\d{2}:\d{2}(:\d{2})?)?/', $s, $m)) {
        return $m[3] . '/' . $m[2] . '/' . $m[1] . (isset($m[4]) ? ' ' . trim($m[4]) : '');
    }
    return $s;
}

date_default_timezone_set('America/Lima');
$fechaReporte = date('d/m/Y H:i');

$logoPath = __DIR__ . '/../../../logo.png';
$logo = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
}
if (empty($logo) && file_exists(__DIR__ . '/../../logo.png')) {
    $logoData = file_get_contents(__DIR__ . '/../../logo.png');
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
}

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body{font-family:"Segoe UI",Arial,sans-serif;font-size:9pt;color:#1e293b;margin:0;padding:12px 14px;}
    .fecha-hora-arriba{position:absolute;top:12px;right:14px;font-size:9pt;color:#475569;z-index:10;}
    .data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:2px solid #cbd5e1;}
    .data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;overflow:hidden;}
    .data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
    .data-table tbody tr.borde-grueso-codprograma{border-bottom:2px solid #cbd5e1;}
    .data-table tbody tr.borde-grueso-codprograma td{border-bottom:2px solid #cbd5e1;}
</style></head><body style="position:relative;">';

$tituloReporte = 'REPORTE ASIGNACIÓN DE PROGRAMAS';
if ($rango !== null && isset($rango['desde'], $rango['hasta']) && $rango['desde'] === $rango['hasta']) {
    $tituloReporte = 'REPORTE CRONOGRAMAS DEL ' . fechaDDMMYYYY($rango['desde']);
}
$html .= '<div class="fecha-hora-arriba">' . htmlspecialchars($fechaReporte) . '</div>';
$html .= '<table width="100%" style="border-collapse: collapse; border: 1px solid #cbd5e1; margin-bottom: 10px; margin-top: 28px;">';
$html .= '<tr>';
if (!empty($logo)) {
    $html .= '<td style="width: 20%; text-align: left; padding: 8px 10px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">' . $logo . ' GRANJA RINCONADA DEL SUR S.A.</td>';
} else {
    $html .= '<td style="width: 20%; text-align: left; padding: 8px 10px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">GRANJA RINCONADA DEL SUR S.A.</td>';
}
$html .= '<td style="width: 60%; text-align: center; padding: 8px 10px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px; border: 1px solid #cbd5e1;">' . htmlspecialchars($tituloReporte) . '</td>';
$html .= '<td style="width: 20%; background-color: #fff; border: 1px solid #cbd5e1;"></td></tr></table>';

// Agrupar por numCronograma para títulos (si existe la columna)
$grupos = [];
if ($tieneNumCronograma && !empty($filas)) {
    foreach ($filas as $f) {
        $numC = (int)($f['numCronograma'] ?? 0);
        if (!isset($grupos[$numC])) $grupos[$numC] = [];
        $grupos[$numC][] = $f;
    }
} else {
    $grupos[0] = $filas;
}

// Anchos en orden: N°, Cód., Nombre Programa, Zona?, Subzona?, Granja, Nom.Granja, Campaña, Galpón, Fec.Carga, Fec.Ejec, Edad
$colWidths = [4, 8, 12, 8, 8, 8, 12, 9, 8, 11, 11, 2];
$colWidthsOrdered = [ $colWidths[0], $colWidths[1], $colWidths[2] ];
if ($tieneZona) $colWidthsOrdered[] = $colWidths[3];
if ($tieneSubzona) $colWidthsOrdered[] = $colWidths[4];
$colWidthsOrdered = array_merge($colWidthsOrdered, array_slice($colWidths, 5));
$numCols = count($colWidthsOrdered);
$anchoUniformePct = $numCols > 0 ? round(100 / $numCols, 4) : 0;
$cellWidthStyle = 'width:' . $anchoUniformePct . '%;min-width:' . $anchoUniformePct . '%;max-width:' . $anchoUniformePct . '%;';

$html .= '<style>.crono-titulo-seccion{display:block;margin-top:16px;margin-bottom:8px;padding:0;font-weight:bold;font-size:11pt;color:#1e293b;}.crono-titulo-seccion:first-of-type{margin-top:0;}</style>';

if (empty($filas)) {
    $html .= '<table class="data-table"><tbody><tr><td colspan="' . $numCols . '" style="text-align:center;color:#64748b;">Sin registros con los filtros aplicados.</td></tr></tbody></table>';
} else {
    $numCronogramaCorrelativo = 0;
    foreach ($grupos as $numCronograma => $filasGrupo) {
        $numCronogramaCorrelativo++;
        $nPorTabla = 0;
        $primera = $filasGrupo[0] ?? null;
        $programaTexto = trim(($primera['codPrograma'] ?? '') . ' ' . ($primera['nomPrograma'] ?? ''));
        $tituloCrono = 'Cronograma ' . $numCronogramaCorrelativo . ' — Programa: ' . htmlspecialchars($programaTexto ?: '—');
        $html .= '<div class="crono-titulo-seccion">' . $tituloCrono . '</div>';
        $html .= '<table class="data-table" style="width:100%;table-layout:fixed;"><thead><tr>';
        $html .= '<th style="' . $cellWidthStyle . '">N°</th>';
        $html .= '<th style="' . $cellWidthStyle . '">Cód. Programa</th>';
        $html .= '<th style="' . $cellWidthStyle . '">Nombre Programa</th>';
        if ($tieneZona) $html .= '<th style="' . $cellWidthStyle . '">Zona</th>';
        if ($tieneSubzona) $html .= '<th style="' . $cellWidthStyle . '">Subzona</th>';
        $html .= '<th style="' . $cellWidthStyle . '">Granja</th>';
        $html .= '<th style="' . $cellWidthStyle . '">Nom. Granja</th>';
        $html .= '<th style="' . $cellWidthStyle . '">Campaña</th>';
        $html .= '<th style="' . $cellWidthStyle . '">Galpón</th>';
        $html .= '<th style="' . $cellWidthStyle . '">Fec. Carga</th>';
        $html .= '<th style="' . $cellWidthStyle . '">Fec. Ejecución</th>';
        $html .= '<th style="' . $cellWidthStyle . '">Edad</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($filasGrupo as $i => $f) {
            $nPorTabla++;
            $edad = ($f['edad'] !== '' && $f['edad'] !== null) ? $f['edad'] : '—';
            $html .= '<tr>';
            $html .= '<td style="' . $cellWidthStyle . '">' . $nPorTabla . '</td>';
            $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars($f['codPrograma']) . '</td>';
            $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars($f['nomPrograma']) . '</td>';
            if ($tieneZona) $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars($f['zona'] ?? '') . '</td>';
            if ($tieneSubzona) $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars($f['subzona'] ?? '') . '</td>';
            $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars($f['granja']) . '</td>';
            $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars($f['nomGranja']) . '</td>';
            $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars($f['campania']) . '</td>';
            $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars($f['galpon']) . '</td>';
            $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars(fechaDDMMYYYY($f['fechaCarga'])) . '</td>';
            $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars(fechaDDMMYYYY($f['fechaEjecucion'])) . '</td>';
            $html .= '<td style="' . $cellWidthStyle . '">' . htmlspecialchars($edad) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    }
}
$html .= '</body></html>';

$tempDir = __DIR__ . '/../../../pdf_tmp';
if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);
if (!is_dir($tempDir) || !is_writable($tempDir)) $tempDir = sys_get_temp_dir();

try {
    if (ob_get_level()) ob_clean();
    require_once __DIR__ . '/../../../vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 12,
        'margin_bottom' => 18,
        'tempDir' => $tempDir,
        'defaultfooterline' => 0,
    ]);
    // Evita que mPDF reduzca cada tabla de forma distinta según contenido.
    $mpdf->shrink_tables_to_fit = 0;
    $mpdf->SetFooter('<div style="text-align:center;font-size:9pt;font-weight:normal;">{PAGENO} de {nbpg}</div>');
    $mpdf->WriteHTML($html);
    $nombreArchivo = 'cronograma_filtrado_' . date('Ymd_His') . '.pdf';
    $mpdf->Output($nombreArchivo, 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
