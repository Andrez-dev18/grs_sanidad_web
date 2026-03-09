<?php
/**
 * Reporte comparativo unificado: combina Necropsias y otros tipos (VACUNA GJA, VACUNA Pl, CP, LD, MC)
 * en un solo PDF cuando se seleccionan ambos.
 */
@ini_set('pcre.backtrack_limit', 10000000);
@ini_set('pcre.recursion_limit', 10000000);
@ini_set('memory_limit', '768M');
@ini_set('max_execution_time', '0');
@set_time_limit(0);
session_start();
if (empty($_SESSION['active'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('No autorizado');
}

$tipoProgramaIds = [];
if (isset($_GET['tipoPrograma']) && is_array($_GET['tipoPrograma'])) {
    foreach ($_GET['tipoPrograma'] as $v) {
        $v = trim((string)$v);
        if ($v !== '') $tipoProgramaIds[] = $v;
    }
}

include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    exit('Error de conexión');
}

$siglasMovi = ['GJ', 'VGJ', 'PL', 'VPI', 'CP', 'CDP', 'LD', 'LYD', 'MC', 'MDC'];
$incluirNecropsias = false;
$incluirMovi = false;
$incluirMSA = false;
$incluirMSB = false;

if (count($tipoProgramaIds) > 0) {
    $ph = implode(',', array_fill(0, count($tipoProgramaIds), '?'));
    $st = $conn->prepare("SELECT codigo, UPPER(TRIM(COALESCE(sigla,''))) AS sigla, LOWER(TRIM(COALESCE(nombre,''))) AS nombre FROM san_dim_tipo_programa WHERE codigo IN ($ph)");
    if ($st) {
        $types = str_repeat('s', count($tipoProgramaIds));
        $st->bind_param($types, ...$tipoProgramaIds);
        $st->execute();
        $res = $st->get_result();
        while ($row = $res->fetch_assoc()) {
            $sigla = trim((string)($row['sigla'] ?? ''));
            $nombre = trim((string)($row['nombre'] ?? ''));
            if ($sigla === 'NEC' || $sigla === 'NC' || strpos($nombre, 'necropsia') !== false) {
                $incluirNecropsias = true;
            }
            if (in_array($sigla, $siglasMovi) || strpos($nombre, 'vacuna') !== false || strpos($nombre, 'plagas') !== false || strpos($nombre, 'limpieza') !== false || strpos($nombre, 'manejo cama') !== false) {
                $incluirMovi = true;
            }
            if ($sigla === 'MSA') $incluirMSA = true;
            if ($sigla === 'MSB') $incluirMSB = true;
        }
        $st->close();
    }
} else {
    $incluirNecropsias = true;
    $incluirMovi = true;
    $incluirMSA = true;
    $incluirMSB = true;
}
$conn->close();

$filasNecropsias = [];
$necropsiasOrden = 1;
$necropsiasTheadRow = null;
$filasPorTipo = [];
$filasMSA = [];
$filasMSB = [];
$rango = ['desde' => date('Y-m-d'), 'hasta' => date('Y-m-d')];

if ($incluirNecropsias) {
    $GLOBALS['REPORTE_UNIFICADO_RETORNAR'] = 'necropsias';
    include __DIR__ . '/generar_reporte_necropsias_vs_cronograma.php';
    unset($GLOBALS['REPORTE_UNIFICADO_RETORNAR']);
    $data = $GLOBALS['reporte_necropsias_data'] ?? null;
    if ($data) {
        $filasNecropsias = $data['filas'] ?? [];
        $rango = $data['rango'] ?? $rango;
        $necropsiasOrden = (int)($data['orden'] ?? 1);
        $necropsiasTheadRow = $data['theadRow'] ?? null;
    }
}

if ($incluirMovi) {
    $GLOBALS['REPORTE_UNIFICADO_RETORNAR'] = 'movi';
    include __DIR__ . '/generar_reporte_comparativo_movi.php';
    unset($GLOBALS['REPORTE_UNIFICADO_RETORNAR']);
    $data = $GLOBALS['reporte_movi_data'] ?? null;
    if ($data) {
        $filasPorTipo = $data['filasPorTipo'] ?? [];
        if (!empty($data['rango'])) $rango = $data['rango'];
    }
}

if ($incluirMSA) {
    $_GET['tipoMS'] = 'MSA';
    $GLOBALS['REPORTE_UNIFICADO_RETORNAR'] = 'ms';
    include __DIR__ . '/generar_reporte_comparativo_ms.php';
    unset($GLOBALS['REPORTE_UNIFICADO_RETORNAR']);
    $data = $GLOBALS['reporte_ms_data'] ?? null;
    if ($data) {
        $filasMSA = $data['filas'] ?? [];
        if (!empty($data['rango'])) $rango = $data['rango'];
    }
}

if ($incluirMSB) {
    $_GET['tipoMS'] = 'MSB';
    $GLOBALS['REPORTE_UNIFICADO_RETORNAR'] = 'ms';
    include __DIR__ . '/generar_reporte_comparativo_ms.php';
    unset($GLOBALS['REPORTE_UNIFICADO_RETORNAR']);
    $data = $GLOBALS['reporte_ms_data'] ?? null;
    if ($data) {
        $filasMSB = $data['filas'] ?? [];
        if (!empty($data['rango'])) $rango = $data['rango'];
    }
}

if (empty($filasNecropsias) && empty($filasPorTipo) && empty($filasMSA) && empty($filasMSB)) {
    exit('No hay datos para generar el reporte.');
}

date_default_timezone_set('America/Lima');
$fechaReporte = date('d/m/Y H:i');
$fechaTitulo = date('d/m/Y', strtotime($rango['desde']));
if ($rango['desde'] !== $rango['hasta']) {
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
.data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:1px solid #cbd5e1;}
.data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;}
.data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
.data-table .tipo-planificado{background:#dcfce7;}
.data-table .tipo-eventual{background:#fef3c7;}
.data-table .tipo-no-planificado{background:#e0e7ff;}
.data-table .tipo-desarrollado{background:#f1f5f9;}
.data-table .celda-vacia{color:#94a3b8;}
.grupo-fecha{background:#0f172a !important;color:#fff !important;font-weight:bold;border-top:2px solid #1e293b;}
';

$theadRow = '<tr><th style="width:5%;text-align:center;">N&#176;</th><th style="width:6%">Zona</th><th style="width:6%">Subzona</th><th style="width:7%">Granja</th><th style="width:12%">Nombre granja</th><th style="width:7%">Campaña</th><th style="width:6%">Galpón</th><th style="width:4%">Edad</th><th style="width:10%">Fecha planificada</th><th style="width:10%">Fecha desarrollada</th><th style="width:6%">Tipo</th><th style="width:8%">Estado</th></tr>';

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

function renderTablaFilas($filas, $theadRow, $nombreSeccion = '') {
    if (empty($filas)) return '<table class="data-table"><thead>' . $theadRow . '</thead><tbody><tr><td colspan="12" style="text-align:center;color:#64748b;">No hay datos.</td></tr></tbody></table>';
    $buf = '<table class="data-table"><thead>' . $theadRow . '</thead><tbody>';
    $n = 0;
    $fechaAnt = '';
    $prefijo = $nombreSeccion !== '' ? htmlspecialchars($nombreSeccion) . ' ' : '';
    foreach ($filas as $r) {
        $fechaMostrar = $r['fechaMostrar'] ?? '';
        $fechaLabel = $fechaMostrar ? date('d/m/Y', strtotime($fechaMostrar)) : '';
        if ($fechaLabel !== '' && $fechaLabel !== $fechaAnt) {
            $fechaAnt = $fechaLabel;
            $buf .= '<tr class="grupo-fecha"><td colspan="12">' . $prefijo . htmlspecialchars($fechaLabel) . '</td></tr>';
        }
        $n++;
        $esPosteriorHoy = ($fechaMostrar !== '' && strtotime($fechaMostrar) > strtotime(date('Y-m-d')));
        $vacio = $esPosteriorHoy ? '' : '<span class="celda-vacia">—</span>';
        $clase = ($r['tipo'] ?? '') === 'Planificado' ? 'tipo-planificado' : (($r['tipo'] ?? '') === 'Eventual' ? 'tipo-eventual' : (($r['tipo'] ?? '') === '-' ? 'tipo-desarrollado' : (($r['tipo'] ?? '') === 'NO PLANIFICADO' ? 'tipo-no-planificado' : '')));
        $fechaPlanTxt = formatearFechasCorta($r['planificado'] ?? [], 3);
        $fechaDesTxt = formatearFechasCorta($r['ejecutado'] ?? [], 3);
        $estado = $r['estado'] ?? '';
        $tipoTxt = trim((string)($r['tipo'] ?? ''));
        $tipoTxt = $tipoTxt !== '' ? htmlspecialchars($tipoTxt) : $vacio;
        $buf .= '<tr class="' . $clase . '"><td style="text-align:center;">' . $n . '</td><td>' . htmlspecialchars($r['zona'] ?? '') . '</td><td>' . htmlspecialchars($r['subzona'] ?? '') . '</td><td>' . htmlspecialchars($r['granja'] ?? '') . '</td><td>' . htmlspecialchars($r['nomGranja'] ?? '') . '</td><td>' . htmlspecialchars($r['campania'] ?? '') . '</td><td>' . htmlspecialchars($r['galpon'] ?? '') . '</td><td>' . htmlspecialchars($r['edad'] ?? '') . '</td><td>' . ($fechaPlanTxt !== '' ? htmlspecialchars($fechaPlanTxt) : $vacio) . '</td><td>' . ($fechaDesTxt !== '' ? htmlspecialchars($fechaDesTxt) : $vacio) . '</td><td>' . $tipoTxt . '</td><td>' . ($estado !== '' ? htmlspecialchars($estado) : $vacio) . '</td></tr>';
    }
    $buf .= '</tbody></table>';
    return $buf;
}

function escribirTablaFilasEnChunks($mpdf, $filas, $theadRow, $nombreSeccion = '', $chunkSize = 150, $ordenComparativo = 1) {
    $colspan = ($ordenComparativo === 2) ? 13 : 12;
    if (empty($filas)) {
        $mpdf->WriteHTML('<table class="data-table"><thead>' . $theadRow . '</thead><tbody><tr><td colspan="' . $colspan . '" style="text-align:center;color:#64748b;">No hay datos.</td></tr></tbody></table>', \Mpdf\HTMLParserMode::HTML_BODY);
        return;
    }
    $mpdf->WriteHTML('<table class="data-table"><thead>' . $theadRow . '</thead><tbody>', \Mpdf\HTMLParserMode::HTML_BODY);
    $n = 0;
    $fechaAnt = '';
    $buf = '';
    $prefijo = $nombreSeccion !== '' ? htmlspecialchars($nombreSeccion) . ' ' : '';
    foreach ($filas as $r) {
        $fechaMostrar = $r['fechaMostrar'] ?? '';
        $fechaLabel = $fechaMostrar ? date('d/m/Y', strtotime($fechaMostrar)) : '';
        if ($fechaLabel !== '' && $fechaLabel !== $fechaAnt) {
            $fechaAnt = $fechaLabel;
            $buf .= '<tr class="grupo-fecha"><td colspan="' . $colspan . '">' . $prefijo . htmlspecialchars($fechaLabel) . '</td></tr>';
        }
        $n++;
        $esPosteriorHoy = ($fechaMostrar !== '' && strtotime($fechaMostrar) > strtotime(date('Y-m-d')));
        $vacio = $esPosteriorHoy ? '' : '<span class="celda-vacia">—</span>';
        $clase = ($r['tipo'] ?? '') === 'Planificado' ? 'tipo-planificado' : (($r['tipo'] ?? '') === 'Eventual' ? 'tipo-eventual' : (($r['tipo'] ?? '') === '-' ? 'tipo-desarrollado' : (($r['tipo'] ?? '') === 'NO PLANIFICADO' ? 'tipo-no-planificado' : '')));
        $fechaPlanTxt = formatearFechasCorta($r['planificado'] ?? [], 3);
        $fechaDesTxt = formatearFechasCorta($r['ejecutado'] ?? [], 3);
        $estado = $r['estado'] ?? '';
        $tipoTxt = trim((string)($r['tipo'] ?? ''));
        $tipoTxt = $tipoTxt !== '' ? htmlspecialchars($tipoTxt) : $vacio;
        if ($ordenComparativo === 2) {
            $buf .= '<tr class="' . $clase . '"><td style="text-align:center;">' . $n . '</td><td>' . htmlspecialchars($r['zona'] ?? '') . '</td><td>' . htmlspecialchars($r['subzona'] ?? '') . '</td><td>' . htmlspecialchars($r['granja'] ?? '') . '</td><td>' . htmlspecialchars($r['nomGranja'] ?? '') . '</td><td>' . htmlspecialchars($r['campania'] ?? '') . '</td><td>' . htmlspecialchars($r['galpon'] ?? '') . '</td><td>' . htmlspecialchars($r['edadPlan'] ?? '') . '</td><td>' . ($fechaPlanTxt !== '' ? htmlspecialchars($fechaPlanTxt) : $vacio) . '</td><td>' . htmlspecialchars($r['edadDes'] ?? '') . '</td><td>' . ($fechaDesTxt !== '' ? htmlspecialchars($fechaDesTxt) : $vacio) . '</td><td>' . $tipoTxt . '</td><td>' . ($estado !== '' ? htmlspecialchars($estado) : $vacio) . '</td></tr>';
        } else {
            $buf .= '<tr class="' . $clase . '"><td style="text-align:center;">' . $n . '</td><td>' . htmlspecialchars($r['zona'] ?? '') . '</td><td>' . htmlspecialchars($r['subzona'] ?? '') . '</td><td>' . htmlspecialchars($r['granja'] ?? '') . '</td><td>' . htmlspecialchars($r['nomGranja'] ?? '') . '</td><td>' . htmlspecialchars($r['campania'] ?? '') . '</td><td>' . htmlspecialchars($r['galpon'] ?? '') . '</td><td>' . htmlspecialchars($r['edad'] ?? '') . '</td><td>' . ($fechaPlanTxt !== '' ? htmlspecialchars($fechaPlanTxt) : $vacio) . '</td><td>' . ($fechaDesTxt !== '' ? htmlspecialchars($fechaDesTxt) : $vacio) . '</td><td>' . $tipoTxt . '</td><td>' . ($estado !== '' ? htmlspecialchars($estado) : $vacio) . '</td></tr>';
        }
        if ($n % $chunkSize === 0) {
            $mpdf->WriteHTML($buf, \Mpdf\HTMLParserMode::HTML_BODY);
            $buf = '';
        }
    }
    if ($buf !== '') $mpdf->WriteHTML($buf, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->WriteHTML('</tbody></table>', \Mpdf\HTMLParserMode::HTML_BODY);
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

    if (!empty($filasNecropsias)) {
        $mpdf->WriteHTML('<div style="margin-top:12px;margin-bottom:6px;font-weight:bold;font-size:11pt;color:#1e40af;">NECROPSIAS</div>', \Mpdf\HTMLParserMode::HTML_BODY);
        $theadNecro = ($necropsiasTheadRow !== null) ? $necropsiasTheadRow : $theadRow;
        escribirTablaFilasEnChunks($mpdf, $filasNecropsias, $theadNecro, 'NECROPSIAS', 150, $necropsiasOrden);
    }

    foreach ($filasPorTipo as $nombreTipo => $filas) {
        $tituloSeccion = strtoupper($nombreTipo);
        $mpdf->WriteHTML('<div style="margin-top:16px;margin-bottom:6px;font-weight:bold;font-size:11pt;color:#1e40af;">' . htmlspecialchars($tituloSeccion) . '</div>', \Mpdf\HTMLParserMode::HTML_BODY);
        escribirTablaFilasEnChunks($mpdf, $filas, $theadRow, $tituloSeccion);
    }

    if (!empty($filasMSA)) {
        $mpdf->WriteHTML('<div style="margin-top:16px;margin-bottom:6px;font-weight:bold;font-size:11pt;color:#1e40af;">MSA (MUESTRAS ADULTO)</div>', \Mpdf\HTMLParserMode::HTML_BODY);
        escribirTablaFilasEnChunks($mpdf, $filasMSA, $theadRow, 'MSA');
    }
    if (!empty($filasMSB)) {
        $mpdf->WriteHTML('<div style="margin-top:16px;margin-bottom:6px;font-weight:bold;font-size:11pt;color:#1e40af;">MSB (MUESTRAS EDAD 1)</div>', \Mpdf\HTMLParserMode::HTML_BODY);
        escribirTablaFilasEnChunks($mpdf, $filasMSB, $theadRow, 'MSB');
    }

    $mpdf->Output('reporte_comparativo_' . $rango['desde'] . '_' . $rango['hasta'] . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
