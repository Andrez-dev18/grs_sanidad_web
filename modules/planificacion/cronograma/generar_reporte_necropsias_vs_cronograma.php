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
$fechaLegacy = trim((string)($_GET['fecha'] ?? ''));

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
    exit('Indique un período válido.');
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    exit('Error de conexión');
}

$COD_TIPO_NECROPSIAS = 1;

// Cronograma tipo Necropsias para esta fecha (granja, campania, galpon, edad)
$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;
$sqlCrono = "SELECT DATE(c.fechaEjecucion) AS fecha_ref, c.granja, c.campania, c.galpon" . ($tieneEdad ? ", c.edad" : "") . "
    FROM san_fact_cronograma c
    INNER JOIN san_fact_programa_cab cab ON cab.codigo = c.codPrograma AND cab.codTipo = ?
    WHERE DATE(c.fechaEjecucion) >= ? AND DATE(c.fechaEjecucion) <= ?";
$stmtCrono = $conn->prepare($sqlCrono);
$stmtCrono->bind_param('iss', $COD_TIPO_NECROPSIAS, $rango['desde'], $rango['hasta']);
$stmtCrono->execute();
$resCrono = $stmtCrono->get_result();
$planificados = [];
while ($row = $resCrono->fetch_assoc()) {
    $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
    $g = trim((string)($row['granja'] ?? ''));
    $c = trim((string)($row['campania'] ?? ''));
    $gp = trim((string)($row['galpon'] ?? ''));
    $e = $tieneEdad ? trim((string)($row['edad'] ?? '')) : '';
    $key = $fechaRef . '|' . $g . '|' . $c . '|' . $gp . '|' . $e;
    $planificados[$key] = true;
}
$stmtCrono->close();

// Necropsias registradas en esta fecha
$chkTfectra = @$conn->query("SHOW COLUMNS FROM t_regnecropsia LIKE 'tfectra'");
if (!$chkTfectra || $chkTfectra->num_rows === 0) {
    $conn->close();
    exit('Tabla t_regnecropsia sin columna tfectra.');
}
$sqlNecro = "SELECT DATE(tfectra) AS fecha_ref, tgranja, tgalpon, tcampania, tedad, tnumreg, tfectra
    FROM t_regnecropsia
    WHERE DATE(tfectra) >= ? AND DATE(tfectra) <= ?
    ORDER BY DATE(tfectra), tgranja, tgalpon, tnumreg";
$stmtNecro = $conn->prepare($sqlNecro);
$stmtNecro->bind_param('ss', $rango['desde'], $rango['hasta']);
$stmtNecro->execute();
$resNecro = $stmtNecro->get_result();
$filas = [];
while ($row = $resNecro->fetch_assoc()) {
    $fechaRef = trim((string)($row['fecha_ref'] ?? ''));
    $tgranja = trim((string)($row['tgranja'] ?? ''));
    $granja = strlen($tgranja) >= 3 ? substr($tgranja, 0, 3) : $tgranja;
    $campania = trim((string)($row['tcampania'] ?? ''));
    if ($campania === '' && strlen($tgranja) >= 3) {
        $campania = substr($tgranja, -3);
    }
    $galpon = trim((string)($row['tgalpon'] ?? ''));
    $edad = $row['tedad'] !== null && $row['tedad'] !== '' ? trim((string)$row['tedad']) : '';
    $key = $fechaRef . '|' . $granja . '|' . $campania . '|' . $galpon . '|' . $edad;
    $tipo = isset($planificados[$key]) ? 'Planificado' : 'Eventual';
    $filas[] = [
        'fecha' => $fechaRef,
        'granja' => $granja,
        'campania' => $campania,
        'galpon' => $galpon,
        'edad' => $edad,
        'numreg' => $row['tnumreg'] ?? '',
        'tipo' => $tipo,
    ];
}
$stmtNecro->close();
$conn->close();

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
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
}
if (empty($logo) && file_exists(__DIR__ . '/../../logo.png')) {
    $logoPath = __DIR__ . '/../../logo.png';
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
}

$bordeTitulo = 'border: 1px solid #64748b;';
$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
body{font-family:"Segoe UI",Arial,sans-serif;font-size:9pt;color:#1e293b;margin:0;padding:10px;}
.fecha-hora-arriba{position:absolute;top:8px;right:0;font-size:9pt;color:#475569;z-index:10;}
.data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:1px solid #cbd5e1;}
.data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;}
.data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
.data-table .tipo-planificado{background:#dcfce7;}
.data-table .tipo-eventual{background:#fef3c7;}
</style></head><body style="position:relative;">';

$html .= '<div class="fecha-hora-arriba">' . htmlspecialchars($fechaReporte) . '</div>';
$html .= '<table width="100%" style="border-collapse: collapse; margin-bottom: 10px; margin-top: 24px; ' . $bordeTitulo . '">';
$html .= '<tr>';
$html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; ' . $bordeTitulo . '">' . ($logo ? $logo . ' ' : '') . 'GRANJA RINCONADA DEL SUR S.A.</td>';
$html .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px; ' . $bordeTitulo . '">REPORTE NECROPSIAS VS CRONOGRAMA — ' . htmlspecialchars($fechaTitulo) . '</td>';
$html .= '<td style="width: 20%; background-color: #fff; ' . $bordeTitulo . '"></td></tr></table>';

$html .= '<table class="data-table"><thead><tr>';
$html .= '<th style="width:12%">Fecha</th><th style="width:15%">Granja</th><th style="width:15%">Campaña</th><th style="width:15%">Galpón</th><th style="width:12%">Edad</th><th style="width:13%">Nº reg.</th><th style="width:18%">Tipo</th>';
$html .= '</tr></thead><tbody>';

if (empty($filas)) {
    $html .= '<tr><td colspan="7" style="text-align:center;color:#64748b;">No hay necropsias registradas para este período.</td></tr>';
} else {
    foreach ($filas as $r) {
        $clase = $r['tipo'] === 'Planificado' ? 'tipo-planificado' : 'tipo-eventual';
        $html .= '<tr class="' . $clase . '">';
        $html .= '<td>' . htmlspecialchars(date('d/m/Y', strtotime($r['fecha']))) . '</td>';
        $html .= '<td>' . htmlspecialchars($r['granja']) . '</td>';
        $html .= '<td>' . htmlspecialchars($r['campania']) . '</td>';
        $html .= '<td>' . htmlspecialchars($r['galpon']) . '</td>';
        $html .= '<td>' . htmlspecialchars($r['edad']) . '</td>';
        $html .= '<td>' . htmlspecialchars($r['numreg']) . '</td>';
        $html .= '<td>' . htmlspecialchars($r['tipo']) . '</td>';
        $html .= '</tr>';
    }
}

$html .= '</tbody></table></body></html>';

$tempDir = __DIR__ . '/../../../pdf_tmp';
if (!is_dir($tempDir)) {
    @mkdir($tempDir, 0775, true);
}
if (!is_dir($tempDir) || !is_writable($tempDir)) {
    $tempDir = sys_get_temp_dir();
}

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
    $mpdf->SetFooter('<div style="text-align:center;font-size:9pt;">{PAGENO} de {nbpg}</div>');
    $mpdf->WriteHTML($html);
    $mpdf->Output('reporte_necropsias_vs_cronograma_' . $rango['desde'] . '_' . $rango['hasta'] . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
