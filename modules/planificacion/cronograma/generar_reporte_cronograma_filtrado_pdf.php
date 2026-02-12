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
$sql .= " FROM san_fact_cronograma c";
$sql .= $joinTipo;
$sql .= " WHERE " . ($whereTipo ?: " 1=1 ");

if ($rango !== null && isset($rango['desde'], $rango['hasta'])) {
    $campoFechaFiltro = $tieneFechaHoraRegistro ? 'c.fechaHoraRegistro' : 'c.fechaEjecucion';
    $sql .= " AND DATE(" . $campoFechaFiltro . ") >= ? AND DATE(" . $campoFechaFiltro . ") <= ? ";
    $params[] = $rango['desde'];
    $params[] = $rango['hasta'];
    $types .= 'ss';
}

$sql .= " ORDER BY c.codPrograma, c.granja, c.campania, c.galpon, c.fechaEjecucion ASC";

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
    .data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:3px solid #64748b;}
    .data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;overflow:hidden;}
    .data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
    .data-table tbody tr.borde-grueso-codprograma{border-bottom:2px solid #64748b;}
    .data-table tbody tr.borde-grueso-codprograma td{border-bottom:2px solid #64748b;}
</style></head><body style="position:relative;">';

$html .= '<div class="fecha-hora-arriba">' . htmlspecialchars($fechaReporte) . '</div>';
$html .= '<table width="100%" style="border-collapse: collapse; border: 1px solid #cbd5e1; margin-bottom: 10px; margin-top: 28px;">';
$html .= '<tr>';
if (!empty($logo)) {
    $html .= '<td style="width: 20%; text-align: left; padding: 8px 10px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">' . $logo . ' GRANJA RINCONADA DEL SUR S.A.</td>';
} else {
    $html .= '<td style="width: 20%; text-align: left; padding: 8px 10px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">GRANJA RINCONADA DEL SUR S.A.</td>';
}
$html .= '<td style="width: 60%; text-align: center; padding: 8px 10px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px; border: 1px solid #cbd5e1;">REPORTE ASIGNACIÓN DE PROGRAMAS</td>';
$html .= '<td style="width: 20%; background-color: #fff; border: 1px solid #cbd5e1;"></td></tr></table>';

$pctColCronoFilt = round(100 / 9, 2);
$html .= '<table class="data-table"><colgroup>';
for ($i = 0; $i < 9; $i++) $html .= '<col style="width:' . $pctColCronoFilt . '%"/>';
$html .= '</colgroup><thead><tr><th>N°</th><th>Cód. Programa</th><th>Granja</th><th>Nom. Granja</th><th>Campaña</th><th>Galpón</th><th>Fec. Carga</th><th>Fec. Ejecución</th><th>Edad de aplicación</th></tr></thead><tbody>';
if (empty($filas)) {
    $html .= '<tr><td colspan="9" style="text-align:center;color:#64748b;">Sin registros con los filtros aplicados.</td></tr>';
} else {
    $totalFilas = count($filas);
    foreach ($filas as $i => $f) {
        $edad = ($f['edad'] !== '' && $f['edad'] !== null) ? $f['edad'] : '—';
        $esUltimaFilaPrograma = ($i === $totalFilas - 1) || (isset($filas[$i + 1]) && ($filas[$i + 1]['codPrograma'] ?? '') !== ($f['codPrograma'] ?? ''));
        $claseTr = $esUltimaFilaPrograma ? ' class="borde-grueso-codprograma"' : '';
        $html .= '<tr' . $claseTr . '>';
        $html .= '<td>' . ($i + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($f['codPrograma']) . '</td>';
        $html .= '<td>' . htmlspecialchars($f['granja']) . '</td>';
        $html .= '<td>' . htmlspecialchars($f['nomGranja']) . '</td>';
        $html .= '<td>' . htmlspecialchars($f['campania']) . '</td>';
        $html .= '<td>' . htmlspecialchars($f['galpon']) . '</td>';
        $html .= '<td>' . htmlspecialchars(fechaDDMMYYYY($f['fechaCarga'])) . '</td>';
        $html .= '<td>' . htmlspecialchars(fechaDDMMYYYY($f['fechaEjecucion'])) . '</td>';
        $html .= '<td>' . htmlspecialchars($edad) . '</td>';
        $html .= '</tr>';
    }
}
$html .= '</tbody></table></body></html>';

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
    $mpdf->SetFooter('<div style="text-align:center;font-size:9pt;font-weight:normal;">{PAGENO} de {nbpg}</div>');
    $mpdf->WriteHTML($html);
    $nombreArchivo = 'cronograma_filtrado_' . date('Ymd_His') . '.pdf';
    $mpdf->Output($nombreArchivo, 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
