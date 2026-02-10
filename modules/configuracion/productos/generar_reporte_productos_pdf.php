<?php
session_start();
if (empty($_SESSION['active'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('No autorizado');
}

$lin = trim((string)($_GET['lin'] ?? ''));
$alma = trim((string)($_GET['alma'] ?? ''));
$tcodprove = trim((string)($_GET['tcodprove'] ?? ''));
$descri = trim((string)($_GET['descri'] ?? ''));

// Sin al menos un filtro no generar PDF (miles de registros)
if ($lin === '' && $alma === '' && $tcodprove === '' && $descri === '') {
    header('Content-Type: text/html; charset=UTF-8');
    header('HTTP/1.1 400 Bad Request');
    exit('Debe usar al menos un filtro (Línea, Almacén, Proveedor o Descripción) para generar el PDF. Sin filtros el reporte incluiría miles de registros.');
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) exit('Error de conexión');

$tieneLin = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'lin'");
$tieneAlma = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'alma'");
$tieneLin = $tieneLin && $tieneLin->fetch_assoc();
$tieneAlma = $tieneAlma && $tieneAlma->fetch_assoc();

$sql = "SELECT m.codigo, m.descri, m.tcodprove, m.dosis, c.nombre AS nombre_proveedor";
$sqlFrom = " FROM mitm m LEFT JOIN ccte c ON c.codigo = m.tcodprove";
$sqlWhere = " WHERE 1=1";
$params = [];
$types = '';
if ($tieneLin && $lin !== '') { $sqlWhere .= " AND m.lin = ?"; $params[] = $lin; $types .= 's'; }
if ($tieneAlma && $alma !== '') { $sqlWhere .= " AND m.alma = ?"; $params[] = $alma; $types .= 's'; }
if ($tcodprove !== '') { $sqlWhere .= " AND m.tcodprove = ?"; $params[] = $tcodprove; $types .= 's'; }
if ($descri !== '') { $sqlWhere .= " AND m.descri LIKE ?"; $params[] = '%' . $descri . '%'; $types .= 's'; }
$chkUnidad = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'unidad'");
$tieneUnidad = $chkUnidad && $chkUnidad->fetch_assoc();
if ($tieneUnidad) $sql .= ", m.unidad";
if ($tieneLin) $sql .= ", m.lin";
if ($tieneAlma) $sql .= ", m.alma";
$sql .= $sqlFrom . $sqlWhere . " ORDER BY m.descri ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $lista = [];
    while ($row = $res->fetch_assoc()) {
        $item = ['codigo' => $row['codigo'], 'descri' => $row['descri'], 'nombre_proveedor' => $row['nombre_proveedor'] ?? '', 'dosis' => $row['dosis'] ?? '', 'unidad' => $tieneUnidad ? ($row['unidad'] ?? '') : '', 'lin' => $tieneLin ? ($row['lin'] ?? '') : '', 'alma' => $tieneAlma ? ($row['alma'] ?? '') : ''];
        $lista[] = $item;
    }
    $stmt->close();
} else {
    $lista = [];
}
$conn->close();

date_default_timezone_set('America/Lima');
$fechaReporte = date('d/m/Y H:i');
$css = 'body{font-family:"Segoe UI",Arial,sans-serif;font-size:10pt;color:#1e293b;margin:0;padding:15px;}
.header-info{font-size:9pt;color:#475569;text-align:right;margin-bottom:8px;}
table{width:100%;border-collapse:collapse;font-size:9pt;margin-top:10px;}
th,td{padding:6px 8px;border:1px solid #cbd5e1;text-align:left;}
th{background-color:#f0f9ff;color:#0c4a6e;font-weight:bold;}
tbody tr:nth-child(even){background-color:#f8fafc;}';

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>';
$html .= '<div class="header-info">Reporte de productos - ' . htmlspecialchars($fechaReporte) . '</div>';
$html .= '<h2 style="font-size:14pt;margin-bottom:10px;">Productos</h2>';
$html .= '<table><thead><tr><th>Código</th><th>Descripción</th><th>Línea</th><th>Almacén</th><th>Proveedor</th><th>Unidad</th><th>Dosis</th></tr></thead><tbody>';
foreach ($lista as $p) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($p['codigo']) . '</td>';
    $html .= '<td>' . htmlspecialchars($p['descri']) . '</td>';
    $html .= '<td>' . htmlspecialchars($p['lin']) . '</td>';
    $html .= '<td>' . htmlspecialchars($p['alma']) . '</td>';
    $html .= '<td>' . htmlspecialchars($p['nombre_proveedor']) . '</td>';
    $html .= '<td>' . htmlspecialchars($p['unidad']) . '</td>';
    $html .= '<td>' . htmlspecialchars($p['dosis']) . '</td>';
    $html .= '</tr>';
}
if (empty($lista)) {
    $html .= '<tr><td colspan="7" style="text-align:center;color:#64748b;">Sin resultados.</td></tr>';
}
$html .= '</tbody></table></body></html>';

$tempDir = __DIR__ . '/../../../pdf_tmp';
if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);
if (!is_dir($tempDir) || !is_writable($tempDir)) $tempDir = sys_get_temp_dir();
try {
    if (ob_get_level()) ob_clean();
    require_once __DIR__ . '/../../../vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 15, 'margin_bottom' => 15, 'tempDir' => $tempDir]);
    $mpdf->WriteHTML($html);
    $mpdf->Output('reporte_productos_' . date('Ymd_His') . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    if (ob_get_level()) @ob_end_clean();
    exit('Error generando PDF: ' . $e->getMessage());
}
