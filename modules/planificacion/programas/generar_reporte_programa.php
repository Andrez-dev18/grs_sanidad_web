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

// Todos los campos del detalle (según tipo_programa: ubicacion, producto, unidades, unidadDosis, numeroFrascos, edad, areaGalpon, cantidadPorGalpon) + proveedor, dosis, descripcionVacuna; edad siempre al final
$colsSinNum = ['ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'unidadDosis', 'area_galpon', 'cantidad_por_galpon', 'edad'];
$labelsReporte = [
    'ubicacion' => 'Ubicación', 'producto' => 'Producto', 'proveedor' => 'Proveedor', 'unidad' => 'Unidad',
    'dosis' => 'Dosis', 'descripcion_vacuna' => 'Descripción', 'numeroFrascos' => 'Nº frascos', 'unidadDosis' => 'Unid. dosis',
    'area_galpon' => 'Área galpón', 'cantidad_por_galpon' => 'Cant. por galpón', 'edad' => 'Edad de aplicación'
];
function formatearDescripcionVacuna($s) {
    $s = trim((string)($s ?? ''));
    if ($s === '') return '';
    if (preg_match('/^Contra[\r\n]/', $s) || (strpos($s, "\n") !== false && strpos($s, '- ') !== false)) return $s;
    $partes = array_filter(array_map('trim', explode(',', $s)));
    if (empty($partes)) return '';
    return "Contra\n" . implode("\n", array_map(function($p) { return '- ' . $p; }, $partes));
}
function valorClaveDetalleReporte($k, $d) {
    if ($k === 'edad' || $k === 'num') return '';
    if ($k === 'ubicacion') return $d['ubicacion'] ?? '';
    if ($k === 'producto') return $d['nomProducto'] ?? ($d['codProducto'] ?? '');
    if ($k === 'proveedor') return (trim((string)($d['codProveedor'] ?? '')) !== '' ? $d['codProveedor'] : ($d['nomProveedor'] ?? ''));
    if ($k === 'unidad') return $d['unidades'] ?? '';
    if ($k === 'dosis') return $d['dosis'] ?? '';
    if ($k === 'descripcion_vacuna') return $d['descripcionVacuna'] ?? '';
    if ($k === 'numeroFrascos') return $d['numeroFrascos'] ?? '';
    if ($k === 'unidadDosis') return $d['unidadDosis'] ?? '';
    if ($k === 'area_galpon') return (isset($d['areaGalpon']) && $d['areaGalpon'] !== null && $d['areaGalpon'] !== '') ? (string)$d['areaGalpon'] : '';
    if ($k === 'cantidad_por_galpon') return (isset($d['cantidadPorGalpon']) && $d['cantidadPorGalpon'] !== null && $d['cantidadPorGalpon'] !== '') ? (string)$d['cantidadPorGalpon'] : '';
    return '';
}
function agruparDetallesPorEdadReporte($detalles, $colsSinNum) {
    if (empty($detalles)) return [];
    $colsSinEdad = array_values(array_filter($colsSinNum, function($k) { return $k !== 'edad'; }));
    $map = [];
    foreach ($detalles as $d) {
        $key = implode("\t", array_map(function($k) use ($d) { return valorClaveDetalleReporte($k, $d); }, $colsSinEdad));
        if (!isset($map[$key])) $map[$key] = [];
        $map[$key][] = $d;
    }
    $out = [];
    foreach ($map as $group) {
        $first = $group[0];
        $ages = [];
        foreach ($group as $row) {
            $e = $row['edad'] ?? null;
            if ($e !== null && $e !== '') $ages[] = trim((string)$e);
        }
        $merged = $first;
        $merged['edad'] = count($ages) > 0 ? implode(' - ', $ages) : (isset($first['edad']) ? (string)$first['edad'] : '');
        $out[] = $merged;
    }
    return $out;
}

$detalles = agruparDetallesPorEdadReporte($detalles, $colsSinNum);

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

$numCols = 4 + count($colsSinNum); // cab + detalle
$pctCol = round(100 / $numCols, 2);

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body{font-family:"Segoe UI",Arial,sans-serif;font-size:9pt;color:#1e293b;margin:0;padding:10px;}
    .fecha-hora-arriba{position:absolute;top:8px;right:0;font-size:9pt;color:#475569;z-index:10;}
    .data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:1px solid #cbd5e1;}
    .data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;overflow:hidden;}
    .data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
</style></head><body style="position:relative;">';

$html .= '<div class="fecha-hora-arriba">' . htmlspecialchars($fechaReporte) . '</div>';
$html .= '<table width="100%" style="border-collapse: collapse; margin-bottom: 10px; margin-top: 24px;">';
$html .= '<tr>';
if (!empty($logo)) {
    $html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap;">' . $logo . ' GRANJA RINCONADA DEL SUR S.A.</td>';
} else {
    $html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap;">GRANJA RINCONADA DEL SUR S.A.</td>';
}
$html .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px;">REPORTE ' . htmlspecialchars(strtoupper($nombrePrograma)) . '</td>';
$html .= '<td style="width: 20%; background-color: #fff;"></td></tr></table>';
$html .= '<table class="data-table"><colgroup>';
for ($i = 0; $i < $numCols; $i++) $html .= '<col style="width:' . $pctCol . '%"/>';
$html .= '</colgroup><thead><tr>';
$html .= '<th>Código</th><th>Nombre programa</th><th>Despliegue</th><th>Descripción</th>';
foreach ($colsSinNum as $k) {
    $html .= '<th>' . htmlspecialchars($labelsReporte[$k] ?? $k) . '</th>';
}
$html .= '</tr></thead><tbody>';

if (empty($detalles)) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($codigo) . '</td>';
    $html .= '<td>' . htmlspecialchars($nombrePrograma) . '</td>';
    $html .= '<td>' . htmlspecialchars($cabDespliegue) . '</td>';
    $html .= '<td>' . htmlspecialchars($cabDesc) . '</td>';
    $html .= '<td colspan="' . count($colsSinNum) . '" style="text-align:center;color:#64748b;">Sin registros en el detalle.</td></tr>';
} else {
    foreach ($detalles as $i => $d) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($codigo) . '</td>';
        $html .= '<td>' . htmlspecialchars($nombrePrograma) . '</td>';
        $html .= '<td>' . htmlspecialchars($cabDespliegue) . '</td>';
        $html .= '<td>' . htmlspecialchars($cabDesc) . '</td>';
        foreach ($colsSinNum as $k) {
            if ($k === 'ubicacion') {
                $html .= '<td>' . htmlspecialchars($d['ubicacion'] ?? '') . '</td>';
            } elseif ($k === 'producto') {
                $html .= '<td>' . htmlspecialchars($d['nomProducto'] ?? ($d['codProducto'] ?? '')) . '</td>';
            } elseif ($k === 'proveedor') {
                $proveedorVal = (trim((string)($d['codProveedor'] ?? '')) !== '') ? ($d['codProveedor'] ?? '') : ($d['nomProveedor'] ?? '');
                $html .= '<td>' . htmlspecialchars($proveedorVal) . '</td>';
            } elseif ($k === 'unidad') {
                $html .= '<td>' . htmlspecialchars($d['unidades'] ?? '') . '</td>';
            } elseif ($k === 'dosis') {
                $html .= '<td>' . htmlspecialchars($d['dosis'] ?? '') . '</td>';
            } elseif ($k === 'descripcion_vacuna') {
                $html .= '<td style="white-space:pre-wrap;">' . htmlspecialchars(formatearDescripcionVacuna($d['descripcionVacuna'] ?? '')) . '</td>';
            } elseif ($k === 'numeroFrascos') {
                $html .= '<td>' . htmlspecialchars($d['numeroFrascos'] ?? '') . '</td>';
            } elseif ($k === 'edad') {
                $edadVal = $d['edad'] ?? '';
                $html .= '<td>' . htmlspecialchars($edadVal !== '' && $edadVal !== null ? $edadVal : '') . '</td>';
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
