<?php
session_start();
if (empty($_SESSION['active'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('No autorizado');
}
@ini_set('max_execution_time', '0');
@set_time_limit(0);
@ini_set('memory_limit', '512M');

$codPrograma = trim((string)($_GET['codPrograma'] ?? ''));
$numCronograma = trim((string)($_GET['numCronograma'] ?? ''));
$porNumCronograma = $numCronograma !== '' && ctype_digit($numCronograma);

if (!$porNumCronograma && $codPrograma === '') {
    exit('Falta parámetro: codPrograma o numCronograma');
}

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) exit('Error de conexión');

$chk = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
$tieneNomGranja = $chk && $chk->num_rows > 0;
$chk2 = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneEdad = $chk2 && $chk2->num_rows > 0;
$chk3 = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'posDetalle'");
$tienePosDetalle = $chk3 && $chk3->num_rows > 0;
$chkZona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'zona'");
$tieneZona = $chkZona && $chkZona->num_rows > 0;
$chkSubzona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'subzona'");
$tieneSubzona = $chkSubzona && $chkSubzona->num_rows > 0;

$sql = "SELECT codPrograma, nomPrograma, granja, campania, galpon, fechaCarga, fechaEjecucion";
if ($tieneNomGranja) $sql .= ", nomGranja";
if ($tieneEdad) $sql .= ", edad";
if ($tienePosDetalle) $sql .= ", posDetalle";
if ($tieneZona) $sql .= ", zona";
if ($tieneSubzona) $sql .= ", subzona";
if ($porNumCronograma) {
    $sql .= " FROM san_fact_cronograma WHERE numCronograma = ? ORDER BY granja, campania, galpon, fechaEjecucion ASC";
    $stmt = $conn->prepare($sql);
    $numCronoInt = (int)$numCronograma;
    $stmt->bind_param("i", $numCronoInt);
} else {
    $sql .= " FROM san_fact_cronograma WHERE codPrograma = ? ORDER BY granja, campania, galpon, fechaEjecucion ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codPrograma);
}
$stmt->execute();
$res = $stmt->get_result();
$filas = [];
$nomPrograma = '';
while ($row = $res->fetch_assoc()) {
    if ($nomPrograma === '') $nomPrograma = (string)($row['nomPrograma'] ?? '');
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
    ];
}
$stmt->close();
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

$cssPdf = '
    body{font-family:"Segoe UI",Arial,sans-serif;font-size:9pt;color:#1e293b;margin:0;padding:12px 14px;}
    .fecha-hora-arriba{position:absolute;top:12px;right:14px;font-size:9pt;color:#475569;z-index:10;}
    .data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:1px solid #cbd5e1;}
    .data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;overflow:hidden;}
    .data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
';

$bordeTitulo = 'border: 1px solid #64748b;';
$htmlCabecera = '<table width="100%" style="border-collapse: collapse; margin-bottom: 10px; margin-top: 8px; ' . $bordeTitulo . '">';
$htmlCabecera .= '<tr>';
if (!empty($logo)) {
    $htmlCabecera .= '<td style="width: 20%; text-align: left; padding: 8px 10px; background-color: #fff; font-size: 8pt; white-space: nowrap; ' . $bordeTitulo . '">' . $logo . ' GRANJA RINCONADA DEL SUR S.A.</td>';
} else {
    $htmlCabecera .= '<td style="width: 20%; text-align: left; padding: 8px 10px; background-color: #fff; font-size: 8pt; white-space: nowrap; ' . $bordeTitulo . '">GRANJA RINCONADA DEL SUR S.A.</td>';
}
$htmlCabecera .= '<td style="width: 60%; text-align: center; padding: 8px 10px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px; ' . $bordeTitulo . '">REPORTE CRONOGRAMA</td>';
$htmlCabecera .= '<td style="width: 20%; text-align: right; padding: 8px 10px; background-color: #fff; font-size: 9pt; color: #475569; ' . $bordeTitulo . '">' . htmlspecialchars($fechaReporte) . '</td></tr></table>';

$htmlTablaInicio = '<table class="data-table"><thead><tr>';
$htmlTablaInicio .= '<th>N°</th><th>Cód. Programa</th>';
if ($tieneZona) $htmlTablaInicio .= '<th>Zona</th>';
if ($tieneSubzona) $htmlTablaInicio .= '<th>Subzona</th>';
$htmlTablaInicio .= '<th>Granja</th><th>Nom. Granja</th><th>Campaña</th><th>Galpón</th><th>Edad</th><th>Fec. Carga</th><th>Fec. Ejecución</th>';
$htmlTablaInicio .= '</tr></thead><tbody>';

$tempDir = __DIR__ . '/../../../pdf_tmp';
if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);
if (!is_dir($tempDir) || !is_writable($tempDir)) $tempDir = sys_get_temp_dir();

try {
    if (ob_get_level()) ob_clean();
    @ini_set('max_execution_time', '0');
    @set_time_limit(0);
    @ini_set('memory_limit', '512M');
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
        'simpleTables' => true,
        'packTableData' => true,
    ]);
    $mpdf->shrink_tables_to_fit = 0;
    $mpdf->SetFooter('<div style="text-align:center;font-size:9pt;font-weight:normal;">{PAGENO} de {nbpg}</div>');
    $mpdf->WriteHTML($cssPdf, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($htmlCabecera, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->WriteHTML($htmlTablaInicio, \Mpdf\HTMLParserMode::HTML_BODY);
    $totalCols = 9 + ($tieneZona ? 1 : 0) + ($tieneSubzona ? 1 : 0);
    if (empty($filas)) {
        $mpdf->WriteHTML('<tr><td colspan="' . $totalCols . '" style="text-align:center;color:#64748b;">Sin registros en el cronograma.</td></tr>', \Mpdf\HTMLParserMode::HTML_BODY);
    } else {
        $buf = '';
        $n = 0;
        foreach ($filas as $i => $f) {
            $edad = ($f['edad'] !== '' && $f['edad'] !== null) ? $f['edad'] : '—';
            $buf .= '<tr>';
            $buf .= '<td>' . ($i + 1) . '</td>';
            $buf .= '<td>' . htmlspecialchars($f['codPrograma']) . '</td>';
            if ($tieneZona) $buf .= '<td>' . htmlspecialchars($f['zona'] ?? '') . '</td>';
            if ($tieneSubzona) $buf .= '<td>' . htmlspecialchars($f['subzona'] ?? '') . '</td>';
            $buf .= '<td>' . htmlspecialchars($f['granja']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($f['nomGranja']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($f['campania']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($f['galpon']) . '</td>';
            $buf .= '<td>' . htmlspecialchars($edad) . '</td>';
            $buf .= '<td>' . htmlspecialchars(fechaDDMMYYYY($f['fechaCarga'])) . '</td>';
            $buf .= '<td>' . htmlspecialchars(fechaDDMMYYYY($f['fechaEjecucion'])) . '</td>';
            $buf .= '</tr>';
            $n++;
            if ($n % 300 === 0) {
                $mpdf->WriteHTML($buf, \Mpdf\HTMLParserMode::HTML_BODY);
                $buf = '';
            }
        }
        if ($buf !== '') {
            $mpdf->WriteHTML($buf, \Mpdf\HTMLParserMode::HTML_BODY);
        }
    }
    $mpdf->WriteHTML('</tbody></table>', \Mpdf\HTMLParserMode::HTML_BODY);
    $nombreArchivo = 'cronograma_' . ($porNumCronograma ? 'n' . $numCronograma : preg_replace('/[^a-zA-Z0-9_-]/', '_', $codPrograma)) . '_' . date('Ymd_His') . '.pdf';
    $mpdf->Output($nombreArchivo, 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
