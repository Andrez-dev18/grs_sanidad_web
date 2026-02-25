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

$rutaMd = __DIR__ . '/GUIA-PLANIFICACION.md';
$contenido = file_exists($rutaMd) ? file_get_contents($rutaMd) : 'Guía no encontrada.';

$isAdmin = false;
if (!empty($_SESSION['usuario'])) {
    include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
    $conn = @conectar_joya_mysqli();
    if ($conn) {
        $cod = $_SESSION['usuario'];
        $stmt = $conn->prepare("SELECT rol_sanidad FROM usuario WHERE codigo = ? AND estado = 'A'");
        if ($stmt) {
            $stmt->bind_param("s", $cod);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $isAdmin = ($r && strtoupper(trim($r['rol_sanidad'] ?? '')) === 'ADMIN');
            $stmt->close();
        }
    }
}

$adminNotif = $isAdmin ? "\n### Notificaciones de usuarios\n\n[link:notificaciones-usuarios] Gestione los teléfonos autorizados para recibir notificaciones.\n" : '';
$contenido = str_replace('[admin:notificaciones]', $adminNotif, $contenido);

require_once __DIR__ . '/guia_helpers.php';
$guiaData = getGuiaHtml($contenido, true);
$html = $guiaData['html'];

$cssPdf = '
/* Estilo revista digital - tipografía amplia y espaciado generoso */
body{font-family:DejaVu Serif,Georgia,serif;font-size:12pt;color:#1f2937;line-height:1.6;}
.guia-wrap{max-width:100%;}

/* Títulos estilo revista */
.guia-h1{font-family:DejaVu Sans,sans-serif;font-size:28pt;font-weight:bold;color:#0f172a;margin:0 0 24px 0;padding-bottom:16px;border-bottom:3px solid #059669;letter-spacing:-0.5px;line-height:1.2;}
.guia-h2{font-family:DejaVu Sans,sans-serif;font-size:18pt;font-weight:bold;color:#059669;margin:32px 0 16px 0;padding-left:12px;border-left:4px solid #10b981;line-height:1.3;}
.guia-h3{font-family:DejaVu Sans,sans-serif;font-size:14pt;font-weight:bold;color:#374151;margin:24px 0 12px 0;}

/* Párrafos y texto */
.guia-wrap p{margin:0 0 16px 0;line-height:1.7;font-size:12pt;}
.guia-block{margin:20px 0;padding:20px 24px;background:#f8fafc;border-left:4px solid #10b981;font-size:11pt;line-height:1.6;}
.guia-block-line{margin-bottom:10px;}
.guia-block-line:last-child{margin-bottom:0;}

/* Imágenes en página propia - ancho completo */
.guia-pagina-unica{text-align:center;padding:8mm;}
.guia-imagen{margin:0;text-align:center;width:100%;}
.guia-imagen img{width:100%;max-width:100%;height:auto;display:block;}

/* Diagrama de flujo en página propia - flujo principal + opcional al costado */
.guia-flow-pdf{margin:0 auto;padding:24px;background:#f0fdf4;border:1px solid #a7f3d0;border-radius:8px;}
.guia-flow-pdf-side .guia-flow-pdf{width:100%;}
.guia-flow-pdf table{width:100%;max-width:320px;margin:0 auto;border-collapse:collapse;}
.guia-flow-pdf td{text-align:center;padding:14px 16px;background:#fff;border:2px solid #10b981;font-weight:bold;font-size:12pt;}
.guia-flow-pdf tr td span{font-size:10pt;color:#6b7280;font-weight:normal;display:block;margin-top:6px;}
.guia-flow-pdf td{padding-top:8px;}

/* Iconos estilo web */
.guia-icon{font-size:1.1em;display:inline;}
.guia-icon-eye{color:#2563eb;}
.guia-icon-pdf{color:#dc2626;}
.guia-icon-edit{color:#4f46e5;}
.guia-icon-trash{color:#e11d48;}
.guia-icon-copy{color:#059669;}
.guia-icon-filter{color:#64748b;}
.guia-icon-warning{color:#eab308;}
.guia-icon-whatsapp{color:#22c55e;}
.guia-sec-icon{font-size:1em;}

/* Botones estilo web */
.guia-btn{display:inline-block;padding:8px 14px;border-radius:6px;font-size:11pt;font-weight:600;margin:2px 4px 2px 0;}
.guia-btn-primary{background:#059669;color:#fff;border:none;}
.guia-btn-filtrar{background:#2563eb;color:#fff;border:none;}
.guia-btn-limpiar{background:#f3f4f6;color:#374151;border:1px solid #d1d5db;}
.guia-placeholder{background:#fffbeb;border-left:4px solid #d97706;padding:12px 16px;margin:16px 0;font-size:11pt;}
.guia-render{margin:20px 0;padding:16px 20px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;}
.guia-render-label{font-size:10pt;color:#64748b;margin-bottom:8px;}
.guia-render-input{font-size:11pt;}

/* Enlaces estilo revista */
.guia-link{color:#059669;font-weight:bold;text-decoration:none;}
.guia-link:hover{text-decoration:underline;}

/* Listas */
ol{margin:16px 0 24px 28px;padding-left:8px;font-size:12pt;line-height:1.7;}
ol li{margin-bottom:10px;}

/* Separadores entre secciones */
.guia-wrap br{margin:8px 0;}

/* Footer */
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
    $mpdf->SetTitle('Acerca de - Planificación - Sanidad');
    $mpdf->SetFooter('<div style="text-align:center;font-size:10pt;color:#6b7280;">Guía Planificación — Página {PAGENO} de {nbpg}</div>');
    $mpdf->WriteHTML($cssPdf, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML('<div class="guia-wrap">' . $html . '<p class="guia-footer">Guía del Sistema de Sanidad — Módulo Planificación</p></div>', \Mpdf\HTMLParserMode::HTML_BODY);
    if (ob_get_level()) ob_end_clean();
    $mpdf->Output('acerca_planificacion_' . date('Ymd_His') . '.pdf', 'D');
} catch (Exception $e) {
    exit('Error: ' . $e->getMessage());
}
