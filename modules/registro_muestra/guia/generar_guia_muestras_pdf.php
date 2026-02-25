<?php
ob_start();
error_reporting(0);
@ini_set('display_errors', 0);
session_start();
if (empty($_SESSION['active'])) {
    ob_end_clean();
    header('HTTP/1.1 401 Unauthorized');
    exit('No autorizado');
}
@ini_set('max_execution_time', '60');
@set_time_limit(60);
@ini_set('memory_limit', '256M');

$rutaMd = __DIR__ . '/GUIA-MUESTRAS.md';
$contenido = file_exists($rutaMd) ? file_get_contents($rutaMd) : 'Guía no encontrada.';

require_once __DIR__ . '/guia_helpers_muestras.php';
$guiaData = getGuiaHtmlMuestras($contenido, true);
$html = $guiaData['html'];

$cssPdf = '
/* Estilo revista digital - igual que guía Planificación */
body{font-family:DejaVu Serif,Georgia,serif;font-size:12pt;color:#1f2937;line-height:1.6;}
.guia-wrap{max-width:100%;}

.guia-h1{font-family:DejaVu Sans,sans-serif;font-size:28pt;font-weight:bold;color:#0f172a;margin:0 0 24px 0;padding-bottom:16px;border-bottom:3px solid #059669;letter-spacing:-0.5px;line-height:1.2;}
.guia-h2{font-family:DejaVu Sans,sans-serif;font-size:18pt;font-weight:bold;color:#059669;margin:32px 0 16px 0;padding-left:12px;border-left:4px solid #10b981;line-height:1.3;}
.guia-h3{font-family:DejaVu Sans,sans-serif;font-size:14pt;font-weight:bold;color:#374151;margin:24px 0 12px 0;}

.guia-wrap p{margin:0 0 16px 0;line-height:1.7;font-size:12pt;}
.guia-block{margin:20px 0;padding:20px 24px;background:#f8fafc;border-left:4px solid #10b981;font-size:11pt;line-height:1.6;}
.guia-block-line{margin-bottom:10px;}
.guia-block-line:last-child{margin-bottom:0;}

.guia-pagina-unica{text-align:center;padding:8mm;}
.guia-imagen{margin:0;text-align:center;width:100%;}
.guia-imagen img{width:100%;max-width:100%;height:auto;display:block;}

.guia-icon{font-size:1.1em;display:inline;}
.guia-icon-eye{color:#2563eb;}
.guia-icon-pdf{color:#dc2626;}
.guia-icon-edit{color:#4f46e5;}
.guia-icon-trash{color:#e11d48;}
.guia-icon-filter{color:#64748b;}
.guia-icon-qr{color:#475569;}
.guia-icon-history{color:#d97706;}
.guia-icon-mail{color:#2563eb;}
.guia-icon-warning{color:#eab308;}

.guia-btn{display:inline-block;padding:8px 14px;border-radius:6px;font-size:11pt;font-weight:600;margin:2px 4px 2px 0;}
.guia-btn-primary{background:#059669;color:#fff;border:none;}
.guia-btn-filtrar{background:#2563eb;color:#fff;border:none;}
.guia-placeholder{background:#fffbeb;border-left:4px solid #d97706;padding:12px 16px;margin:16px 0;font-size:11pt;}

.guia-link{color:#059669;font-weight:bold;text-decoration:none;}

ol{margin:16px 0 24px 28px;padding-left:8px;font-size:12pt;line-height:1.7;}
ol li{margin-bottom:10px;}

.guia-wrap br{margin:8px 0;}

.guia-footer{margin-top:32px;padding-top:20px;font-size:10pt;color:#6b7280;border-top:1px solid #e5e7eb;}
';

$tempDir = __DIR__ . '/../../../pdf_tmp';
if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);
if (!is_dir($tempDir) || !is_writable($tempDir)) $tempDir = sys_get_temp_dir();

try {
    if (ob_get_level()) ob_end_clean();
    require_once __DIR__ . '/../../../vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf(['mode'=>'utf-8','format'=>'A4','margin_left'=>20,'margin_right'=>20,'margin_top'=>22,'margin_bottom'=>28,'tempDir'=>$tempDir]);
    $mpdf->SetBasePath(__DIR__ . '/');
    $mpdf->SetTitle('Acerca de - Muestras - Sanidad');
    $mpdf->SetFooter('<div style="text-align:center;font-size:10pt;color:#6b7280;">Guía Muestras — Página {PAGENO} de {nbpg}</div>');
    $mpdf->WriteHTML($cssPdf, \Mpdf\HTMLParserMode::HEADER_CSS);

    $toc = $guiaData['toc'] ?? [];
    $coverHtml = '<div style="text-align:center;padding-top:80px;page-break-after:always;">
        <p style="font-family:DejaVu Sans,sans-serif;font-size:32pt;font-weight:bold;color:#0f172a;margin:0 0 8px 0;">GUÍA</p>
        <p style="font-family:DejaVu Sans,sans-serif;font-size:18pt;color:#6b7280;margin:0 0 40px 0;">Paso a Paso</p>
        <p style="font-family:DejaVu Sans,sans-serif;font-size:28pt;font-weight:bold;color:#059669;margin:0;">MÓDULO</p>
        <p style="font-family:DejaVu Sans,sans-serif;font-size:28pt;font-weight:bold;color:#059669;margin:0;">MUESTRAS</p>
    </div>';
    $indiceHtml = '';
    if (!empty($toc)) {
        $indiceHtml = '<div style="page-break-after:always;"><h2 class="guia-h2" style="margin-top:0;">ÍNDICE</h2><ul style="font-size:12pt;line-height:2;list-style:none;padding-left:0;">';
        foreach ($toc as $item) {
            $indiceHtml .= '<li>' . htmlspecialchars($item['titulo']) . '</li>';
        }
        $indiceHtml .= '</ul></div>';
    }

    $mpdf->WriteHTML($coverHtml . $indiceHtml . '<div class="guia-wrap">' . $html . '<p class="guia-footer">Guía del Sistema de Sanidad — Módulo Muestras</p></div>', \Mpdf\HTMLParserMode::HTML_BODY);
    if (ob_get_level()) ob_end_clean();
    $mpdf->Output('acerca_muestras_' . date('Ymd_His') . '.pdf', 'D');
} catch (Exception $e) {
    exit('Error: ' . $e->getMessage());
}
