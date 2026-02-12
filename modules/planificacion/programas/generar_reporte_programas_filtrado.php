<?php
session_start();
if (empty($_SESSION['active'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('No autorizado');
}

include_once '../../../../conexion_grs_joya/conexion.php';
require_once __DIR__ . '/../../../includes/filtro_periodo_util.php';
$conn = conectar_joya();
if (!$conn) exit('Error de conexión');

$codTipo = trim((string)($_GET['codTipo'] ?? ''));
$zona = trim((string)($_GET['zona'] ?? ''));
$despliegue = trim((string)($_GET['despliegue'] ?? ''));

$periodoOpts = [
    'periodoTipo' => $_GET['periodoTipo'] ?? '',
    'fechaUnica'  => $_GET['fechaUnica'] ?? '',
    'fechaInicio' => $_GET['fechaInicio'] ?? '',
    'fechaFin'    => $_GET['fechaFin'] ?? '',
    'mesUnico'    => $_GET['mesUnico'] ?? '',
    'mesInicio'   => $_GET['mesInicio'] ?? '',
    'mesFin'      => $_GET['mesFin'] ?? '',
];
$rangoPeriodo = periodo_a_rango($periodoOpts);
if ($rangoPeriodo !== null) {
    $fechaDesde = $rangoPeriodo['desde'];
    $fechaHasta = $rangoPeriodo['hasta'];
} else {
    $fechaDesde = trim((string)($_GET['fechaDesde'] ?? ''));
    $fechaHasta = trim((string)($_GET['fechaHasta'] ?? ''));
}

$chkDespliegue = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'despliegue'");
$tieneDespliegue = $chkDespliegue && $chkDespliegue->fetch_assoc();

$sqlCab = "SELECT c.codigo, c.nombre, c.codTipo, c.nomTipo, c.zona, c.descripcion, c.fechaHoraRegistro";
if ($tieneDespliegue) $sqlCab .= ", c.despliegue";
$sqlCab .= " FROM san_fact_programa_cab c WHERE 1=1";
$params = [];
$types = '';
if ($codTipo !== '' && is_numeric($codTipo)) { $sqlCab .= " AND c.codTipo = ?"; $params[] = (int)$codTipo; $types .= 'i'; }
if ($fechaDesde !== '') { $sqlCab .= " AND DATE(c.fechaHoraRegistro) >= ?"; $params[] = $fechaDesde; $types .= 's'; }
if ($fechaHasta !== '') { $sqlCab .= " AND DATE(c.fechaHoraRegistro) <= ?"; $params[] = $fechaHasta; $types .= 's'; }
if ($zona !== '') { $sqlCab .= " AND c.zona = ?"; $params[] = $zona; $types .= 's'; }
if ($despliegue !== '' && $tieneDespliegue) { $sqlCab .= " AND c.despliegue = ?"; $params[] = $despliegue; $types .= 's'; }
$sqlCab .= " ORDER BY c.codTipo ASC, c.codigo ASC";

$stmtCab = $conn->prepare($sqlCab);
if (!$stmtCab) { $conn->close(); exit('Error preparar consulta'); }
if ($types !== '') $stmtCab->bind_param($types, ...$params);
$stmtCab->execute();
$resCab = $stmtCab->get_result();
$programas = [];
while ($row = $resCab->fetch_assoc()) {
    $programas[] = [
        'codigo' => $row['codigo'],
        'nombre' => $row['nombre'],
        'codTipo' => (int)($row['codTipo'] ?? 0),
        'nomTipo' => $row['nomTipo'] ?? '',
        'zona' => $row['zona'] ?? '',
        'despliegue' => $tieneDespliegue ? ($row['despliegue'] ?? '') : '',
        'descripcion' => $row['descripcion'] ?? '',
    ];
}
$stmtCab->close();

$siglasPorTipo = [];
$rTipos = $conn->query("SELECT codigo, sigla FROM san_dim_tipo_programa");
if ($rTipos) {
    while ($t = $rTipos->fetch_assoc()) {
        $s = strtoupper(trim($t['sigla'] ?? '')); if ($s === 'NEC') $s = 'NC';
        $siglasPorTipo[(int)$t['codigo']] = $s;
    }
}

$chkExtras = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'descripcionVacuna'");
$tieneExtras = $chkExtras && $chkExtras->fetch_assoc();
$sqlDet = $tieneExtras
    ? "SELECT ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad, descripcionVacuna, areaGalpon, cantidadPorGalpon FROM san_fact_programa_det WHERE codPrograma = ? ORDER BY id"
    : "SELECT ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad FROM san_fact_programa_det WHERE codPrograma = ? ORDER BY id";
$stmtDet = $conn->prepare($sqlDet);

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
    'dosis' => 'Dosis', 'descripcion_vacuna' => 'Descripcion', 'numeroFrascos' => 'Nº frascos', 'edad' => 'Edad de aplicación',
    'unidadDosis' => 'Unid. dosis', 'area_galpon' => 'Área galpón', 'cantidad_por_galpon' => 'Cant. por galpón'
];

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
    $logoPath = __DIR__ . '/../../logo.png';
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
}

$cabecerasUnificadas = ['Código', 'Nombre programa', 'Despliegue', 'Descripción', 'Ubicación', 'Producto', 'Proveedor', 'Unidad', 'Dosis', 'Descripcion', 'Nº frascos', 'Unid. dosis', 'Área galpón', 'Cant. por galpón', 'Edad de aplicación'];
$keysDetalle = ['ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'unidadDosis', 'area_galpon', 'cantidad_por_galpon', 'edad'];

function valorClaveDetalleReporteFiltrado($k, $d) {
    if ($k === 'edad') return '';
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
function agruparDetallesPorEdadReporteFiltrado($detalles, $keysDetalle) {
    if (empty($detalles)) return [];
    $colsSinEdad = array_values(array_filter($keysDetalle, function($k) { return $k !== 'edad'; }));
    $map = [];
    foreach ($detalles as $d) {
        $key = implode("\t", array_map(function($k) use ($d) { return valorClaveDetalleReporteFiltrado($k, $d); }, $colsSinEdad));
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

function formatearDescripcionVacuna($s) {
    $s = trim((string)($s ?? ''));
    if ($s === '') return '';
    if (preg_match('/^Contra[\r\n]/', $s) || (strpos($s, "\n") !== false && strpos($s, '- ') !== false)) return $s;
    $partes = array_filter(array_map('trim', explode(',', $s)));
    if (empty($partes)) return '';
    return "Contra\n" . implode("\n", array_map(function($p) { return '- ' . $p; }, $partes));
}

$numColsFiltrado = count($cabecerasUnificadas); // 15: todas las columnas mismo ancho
$pctColFiltrado = round(100 / $numColsFiltrado, 2);
$css = 'body{font-family:"Segoe UI",Arial,sans-serif;font-size:9pt;color:#1e293b;margin:0;padding:10px;position:relative;}
.fecha-hora-arriba{position:absolute;top:8px;right:0;font-size:9pt;color:#475569;z-index:10;}
.tabla-programa{margin-bottom:24px;}
.data-table{width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;border:3px solid #64748b;}
.data-table th,.data-table td{padding:4px 6px;border:1px solid #cbd5e1;vertical-align:top;text-align:left;background:#fff;overflow:hidden;}
.data-table thead th{background-color:#2563eb !important;color:#fff !important;font-weight:bold;}
.data-table tbody tr.borde-grueso-codprograma{border-bottom:2px solid #64748b;}
.data-table tbody tr.borde-grueso-codprograma td{border-bottom:2px solid #64748b;}
.titulo-programa{font-size:10pt;font-weight:bold;margin-bottom:6px;color:#334155;}';

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>';
$html .= '<div class="fecha-hora-arriba">' . htmlspecialchars($fechaReporte) . '</div>';
$html .= '<table width="100%" style="border-collapse: collapse; border: 1px solid #cbd5e1; margin-bottom: 10px; margin-top: 24px;">';
$html .= '<tr>';
if (!empty($logo)) {
    $html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">' . $logo . ' GRANJA RINCONADA DEL SUR S.A.</td>';
} else {
    $html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">GRANJA RINCONADA DEL SUR S.A.</td>';
}
$html .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #2563eb; color: #fff; font-weight: bold; font-size: 14px; border: 1px solid #cbd5e1;">REPORTE DE PROGRAMAS</td>';
$html .= '<td style="width: 20%; background-color: #fff; border: 1px solid #cbd5e1;"></td></tr></table>';

$programasPorTipo = [];
foreach ($programas as $cab) {
    $t = (int)$cab['codTipo'];
    if (!isset($programasPorTipo[$t])) $programasPorTipo[$t] = [];
    $programasPorTipo[$t][] = $cab;
}

foreach ($programasPorTipo as $codTipo => $lista) {
    $nomTipo = count($lista) ? ($lista[0]['nomTipo'] ?? '') : '';
    usort($lista, function($a, $b) { return strcmp($a['codigo'], $b['codigo']); });

    $html .= '<div class="tabla-programa">';
    $html .= '<div class="titulo-programa">' . htmlspecialchars($nomTipo) . '</div>';
    $html .= '<table class="data-table"><colgroup>';
    for ($ci = 0; $ci < $numColsFiltrado; $ci++) $html .= '<col style="width:' . $pctColFiltrado . '%"/>';
    $html .= '</colgroup><thead><tr>';
    foreach ($cabecerasUnificadas as $h) $html .= '<th>' . htmlspecialchars($h) . '</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($lista as $cab) {
        $codigo = $cab['codigo'];
        $stmtDet->bind_param("s", $codigo);
        $stmtDet->execute();
        $resDet = $stmtDet->get_result();
        $detalles = [];
        while ($row = $resDet->fetch_assoc()) $detalles[] = $row;
        $detalles = agruparDetallesPorEdadReporteFiltrado($detalles, $keysDetalle);

        $cabDespliegue = $cab['despliegue'] ?? '';
        $cabDesc = $cab['descripcion'] ?? '';
        $cabNombre = $cab['nombre'] ?? '';

        if (empty($detalles)) {
            $html .= '<tr class="borde-grueso-codprograma">';
            $html .= '<td>' . htmlspecialchars($codigo) . '</td><td>' . htmlspecialchars($cabNombre) . '</td><td>' . htmlspecialchars($cabDespliegue) . '</td><td>' . htmlspecialchars($cabDesc) . '</td>';
            for ($i = 0; $i < count($keysDetalle); $i++) $html .= '<td></td>';
            $html .= '</tr>';
        } else {
            $numDet = count($detalles);
            foreach ($detalles as $idx => $d) {
                $esUltimaFilaPrograma = ($idx === $numDet - 1);
                $claseTr = $esUltimaFilaPrograma ? ' class="borde-grueso-codprograma"' : '';
                $html .= '<tr' . $claseTr . '>';
                $html .= '<td>' . htmlspecialchars($codigo) . '</td><td>' . htmlspecialchars($cabNombre) . '</td><td>' . htmlspecialchars($cabDespliegue) . '</td><td>' . htmlspecialchars($cabDesc) . '</td>';
                $html .= '<td>' . htmlspecialchars($d['ubicacion'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($d['nomProducto'] ?? ($d['codProducto'] ?? '')) . '</td>';
                $proveedorVal = (trim((string)($d['codProveedor'] ?? '')) !== '') ? ($d['codProveedor'] ?? '') : ($d['nomProveedor'] ?? '');
                $html .= '<td>' . htmlspecialchars($proveedorVal) . '</td>';
                $html .= '<td>' . htmlspecialchars($d['unidades'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($d['dosis'] ?? '') . '</td>';
                $descVac = formatearDescripcionVacuna($d['descripcionVacuna'] ?? '');
                $html .= '<td style="white-space:pre-wrap;">' . htmlspecialchars($descVac) . '</td>';
                $html .= '<td>' . htmlspecialchars($d['numeroFrascos'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($d['unidadDosis'] ?? '') . '</td>';
                $html .= '<td>' . (isset($d['areaGalpon']) && $d['areaGalpon'] !== null && $d['areaGalpon'] !== '' ? (int)$d['areaGalpon'] : '') . '</td>';
                $html .= '<td>' . (isset($d['cantidadPorGalpon']) && $d['cantidadPorGalpon'] !== null && $d['cantidadPorGalpon'] !== '' ? (int)$d['cantidadPorGalpon'] : '') . '</td>';
                $html .= '<td>' . htmlspecialchars(isset($d['edad']) && $d['edad'] !== '' && $d['edad'] !== null ? $d['edad'] : '') . '</td>';
                $html .= '</tr>';
            }
        }
    }
    $html .= '</tbody></table></div>';
}

$stmtDet->close();
$conn->close();

$html .= '</body></html>';

$tempDir = __DIR__ . '/../../../pdf_tmp';
if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);
if (!is_dir($tempDir) || !is_writable($tempDir)) $tempDir = sys_get_temp_dir();

try {
    if (ob_get_level()) ob_clean();
    require_once __DIR__ . '/../../../vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 12, 'margin_bottom' => 18, 'tempDir' => $tempDir, 'defaultfooterline' => 0]);
    $mpdf->SetFooter('<div style="text-align:center;font-size:9pt;font-weight:normal;">{PAGENO} de {nbpg}</div>');
    $mpdf->WriteHTML($html);
    $mpdf->Output('reporte_programas_filtrado_' . date('Ymd_His') . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
