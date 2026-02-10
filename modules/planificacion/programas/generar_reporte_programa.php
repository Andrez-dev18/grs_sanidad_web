<?php
session_start();
if (empty($_SESSION['active'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('No autorizado');
}

$codigo = trim((string)($_GET['codigo'] ?? ''));
if ($codigo === '') {
    exit('Falta código de programa');
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    exit('Error de conexión');
}

// Cabecera del programa (incl. despliegue si existe)
$chkDespliegue = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'despliegue'");
$tieneDespliegue = $chkDespliegue && $chkDespliegue->fetch_assoc();
$sqlCab = "SELECT codigo, nombre, codTipo, nomTipo, zona, descripcion" . ($tieneDespliegue ? ", despliegue" : "") . " FROM san_fact_programa_cab WHERE codigo = ? LIMIT 1";
$stmtCab = $conn->prepare($sqlCab);
$stmtCab->bind_param("s", $codigo);
$stmtCab->execute();
$resCab = $stmtCab->get_result();
$cab = $resCab ? $resCab->fetch_assoc() : null;
$stmtCab->close();

if (!$cab) {
    exit('Programa no encontrado');
}

$nombrePrograma = $cab['nombre'] ?? $codigo;
$codTipo = (int)($cab['codTipo'] ?? 0);

// Sigla del tipo de programa (define columnas del reporte)
$sigla = '';
if ($codTipo > 0) {
    $stSigla = $conn->prepare("SELECT sigla FROM san_dim_tipo_programa WHERE codigo = ? LIMIT 1");
    $stSigla->bind_param("i", $codTipo);
    $stSigla->execute();
    $rSigla = $stSigla->get_result();
    if ($rSigla && $rowSigla = $rSigla->fetch_assoc() && !empty(trim($rowSigla['sigla'] ?? ''))) {
        $sigla = strtoupper(trim($rowSigla['sigla']));
        if ($sigla === 'NEC') $sigla = 'NC';
    }
    $stSigla->close();
}

// Detalle del programa (incl. descripcionVacuna, areaGalpon, cantidadPorGalpon si existen)
$chkExtras = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'descripcionVacuna'");
$tieneExtras = $chkExtras && $chkExtras->fetch_assoc();
$sqlDet = $tieneExtras
    ? "SELECT ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad, descripcionVacuna, areaGalpon, cantidadPorGalpon FROM san_fact_programa_det WHERE codPrograma = ? ORDER BY id"
    : "SELECT ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad FROM san_fact_programa_det WHERE codPrograma = ? ORDER BY id";
$stmtDet = $conn->prepare($sqlDet);
$stmtDet->bind_param("s", $codigo);
$stmtDet->execute();
$resDet = $stmtDet->get_result();
$detalles = [];
while ($row = $resDet->fetch_assoc()) {
    $detalles[] = $row;
}
$stmtDet->close();
$conn->close();

date_default_timezone_set('America/Lima');
$fechaReporte = date('d/m/Y H:i');

$columnasPorSigla = [
    'NC' => ['num', 'ubicacion', 'edad'],
    'PL' => ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'edad'],
    'GR' => ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'edad'],
    'MC' => ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'area_galpon', 'cantidad_por_galpon', 'unidadDosis', 'edad'],
    'LD' => ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'unidadDosis', 'edad'],
    'CP' => ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'unidadDosis', 'edad'],
];
$labelsReporte = [
    'num' => '#', 'ubicacion' => 'Ubicación', 'producto' => 'Producto', 'proveedor' => 'Proveedor', 'unidad' => 'Unidad',
    'dosis' => 'Dosis', 'descripcion_vacuna' => 'Descripcion', 'numeroFrascos' => 'Nº frascos', 'edad' => 'Edad',
    'unidadDosis' => 'Unid. dosis', 'area_galpon' => 'Área galpón', 'cantidad_por_galpon' => 'Cant. por galpón'
];
function formatearDescripcionVacuna($s) {
    $s = trim((string)($s ?? ''));
    if ($s === '') return '';
    if (preg_match('/^Contra[\r\n]/', $s) || (strpos($s, "\n") !== false && strpos($s, '- ') !== false)) return $s;
    $partes = array_filter(array_map('trim', explode(',', $s)));
    if (empty($partes)) return '';
    return "Contra\n" . implode("\n", array_map(function($p) { return '- ' . $p; }, $partes));
}
$cols = isset($columnasPorSigla[$sigla]) ? $columnasPorSigla[$sigla] : $columnasPorSigla['PL'];
$colsSinNum = array_values(array_filter($cols, function($k) { return $k !== 'num'; }));

$cabZona = $cab['zona'] ?? '';
$cabDespliegue = $tieneDespliegue ? ($cab['despliegue'] ?? '') : '';
$cabDesc = $cab['descripcion'] ?? '';

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

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body{font-family:"Segoe UI",Arial,sans-serif;font-size:9pt;color:#1e293b;margin:0;padding:10px;}
    .fecha-hora-arriba{position:absolute;top:8px;right:0;font-size:9pt;color:#475569;z-index:10;}
    .data-table{width:100%;border-collapse:collapse;font-size:8pt;}
    .data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;}
    .data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
</style></head><body style="position:relative;">';

$html .= '<div class="fecha-hora-arriba">' . htmlspecialchars($fechaReporte) . '</div>';
$html .= '<table width="100%" style="border-collapse: collapse; border: 1px solid #cbd5e1; margin-bottom: 10px; margin-top: 24px;">';
$html .= '<tr>';
if (!empty($logo)) {
    $html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">' . $logo . ' GRANJA RINCONADA DEL SUR S.A.</td>';
} else {
    $html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">GRANJA RINCONADA DEL SUR S.A.</td>';
}
$html .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px; border: 1px solid #cbd5e1;">REPORTE ' . htmlspecialchars(strtoupper($nombrePrograma)) . '</td>';
$html .= '<td style="width: 20%; background-color: #fff; border: 1px solid #cbd5e1;"></td></tr></table>';
$html .= '<table class="data-table">';
$html .= '<thead><tr>';
$html .= '<th>Código</th><th>Nombre programa</th><th>Zona</th><th>Despliegue</th><th>Descripción</th>';
foreach ($colsSinNum as $k) {
    $html .= '<th>' . htmlspecialchars($labelsReporte[$k] ?? $k) . '</th>';
}
$html .= '</tr></thead><tbody>';

if (empty($detalles)) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($codigo) . '</td>';
    $html .= '<td>' . htmlspecialchars($nombrePrograma) . '</td>';
    $html .= '<td>' . htmlspecialchars($cabZona) . '</td>';
    $html .= '<td>' . htmlspecialchars($cabDespliegue) . '</td>';
    $html .= '<td>' . htmlspecialchars($cabDesc) . '</td>';
    $html .= '<td colspan="' . count($colsSinNum) . '" style="text-align:center;color:#64748b;">Sin registros en el detalle.</td></tr>';
} else {
    foreach ($detalles as $i => $d) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($codigo) . '</td>';
        $html .= '<td>' . htmlspecialchars($nombrePrograma) . '</td>';
        $html .= '<td>' . htmlspecialchars($cabZona) . '</td>';
        $html .= '<td>' . htmlspecialchars($cabDespliegue) . '</td>';
        $html .= '<td>' . htmlspecialchars($cabDesc) . '</td>';
        foreach ($colsSinNum as $k) {
            if ($k === 'ubicacion') {
                $html .= '<td>' . htmlspecialchars($d['ubicacion'] ?? '') . '</td>';
            } elseif ($k === 'producto') {
                $html .= '<td>' . htmlspecialchars($d['nomProducto'] ?? ($d['codProducto'] ?? '')) . '</td>';
            } elseif ($k === 'proveedor') {
                $html .= '<td>' . htmlspecialchars($d['nomProveedor'] ?? '') . '</td>';
            } elseif ($k === 'unidad') {
                $html .= '<td>' . htmlspecialchars($d['unidades'] ?? '') . '</td>';
            } elseif ($k === 'dosis') {
                $html .= '<td>' . htmlspecialchars($d['dosis'] ?? '') . '</td>';
            } elseif ($k === 'descripcion_vacuna') {
                $html .= '<td>' . htmlspecialchars($d['descripcionVacuna'] ?? '') . '</td>';
            } elseif ($k === 'numeroFrascos') {
                $html .= '<td>' . htmlspecialchars($d['numeroFrascos'] ?? '') . '</td>';
            } elseif ($k === 'edad') {
                $html .= '<td>' . (isset($d['edad']) ? (int)$d['edad'] : '') . '</td>';
            } elseif ($k === 'unidadDosis') {
                $html .= '<td>' . htmlspecialchars($d['unidadDosis'] ?? '') . '</td>';
            } elseif ($k === 'area_galpon') {
                $html .= '<td>' . (isset($d['areaGalpon']) && $d['areaGalpon'] !== null ? (int)$d['areaGalpon'] : '') . '</td>';
            } elseif ($k === 'cantidad_por_galpon') {
                $html .= '<td>' . (isset($d['cantidadPorGalpon']) && $d['cantidadPorGalpon'] !== null ? (int)$d['cantidadPorGalpon'] : '') . '</td>';
            }
        }
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
    $mpdf->SetFooter('<div style="text-align:center;font-size:9pt;font-weight:normal;">{PAGENO} de {nbpg}</div>');

    $mpdf->WriteHTML($html);
    $mpdf->Output('reporte_programa_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $codigo) . '_' . date('Ymd_His') . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
