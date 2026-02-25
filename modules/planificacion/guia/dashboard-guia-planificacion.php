<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; } else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}
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
function slug($s) {
    $s = strtolower(trim($s));
    $s = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}
function md2html($md, &$toc) {
    $lines = explode("\n", $md);
    $out = [];
    $inList = false;
    $inBlock = false;
    $closeBlock = function() use (&$out, &$inBlock) {
        if ($inBlock) { $out[] = '</div>'; $inBlock = false; }
    };
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (preg_match('/^# (.+)$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<h1 class="guia-h1">' . htmlspecialchars($m[1]) . '</h1>';
        } elseif (preg_match('/^## (.+)$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $id = slug($m[1]);
            $tit = $m[1];
            $secIcons = [
                'flujo-de-registro' => 'fa-project-diagram', 'conceptos-del-modulo' => 'fa-lightbulb', 'conceptos-del-m-dulo' => 'fa-lightbulb',
                'programa-registro' => 'fa-clipboard', 'programa-listado' => 'fa-list',
                'asignacion-registro' => 'fa-calendar-plus', 'asignacion-listado' => 'fa-list',
                'cronograma' => 'fa-calendar-alt', 'comparativo' => 'fa-balance-scale',
                'configuracion' => 'fa-cog'
            ];
            $icon = isset($secIcons[$id]) ? '<i class="fas ' . $secIcons[$id] . ' guia-sec-icon"></i> ' : '';
            $toc[] = ['id' => $id, 'titulo' => $tit];
            $out[] = '<h2 id="' . $id . '" class="guia-h2 guia-section">' . $icon . htmlspecialchars($tit) . '</h2>';
        } elseif (preg_match('/^### (.+)$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $h3tit = $m[1];
            $h3icons = ['Tipos de Programa' => 'fa-clipboard', 'Proveedor' => 'fa-box', 'Productos' => 'fa-boxes-stacked', 'Enfermedades' => 'fa-virus', 'Número telefónico' => 'fa-phone', 'Notificaciones de usuarios' => 'fa-users'];
            $h3icon = isset($h3icons[$h3tit]) ? '<i class="fas ' . $h3icons[$h3tit] . ' guia-sec-icon"></i> ' : '';
            $out[] = '<h3 class="guia-h3">' . $h3icon . htmlspecialchars($h3tit) . '</h3>';
        } elseif (preg_match('/^\[imagen:(\d+)\]$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<!--IMAGEN:' . $m[1] . '-->';
        } elseif (preg_match('/^> (.+)$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<p class="guia-placeholder">' . htmlspecialchars($m[1]) . '</p>';
        } elseif (preg_match('/^\[render:(\w+)\]$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<!--RENDER:' . $m[1] . '-->';
        } elseif (preg_match('/^\[flow:(\w+)\]$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<!--FLOW:' . $m[1] . '-->';
        } elseif (preg_match('/^\| (.+)$/', $trimmed, $m)) {
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            if (!$inBlock) { $closeBlock(); $out[] = '<div class="guia-block">'; $inBlock = true; }
            $out[] = '<div class="guia-block-line">' . preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', htmlspecialchars($m[1])) . '</div>';
        } elseif (preg_match('/^(\d+)\. (.+)$/', $trimmed, $m)) {
            $closeBlock();
            if (!$inList) { $out[] = '<ol>'; $inList = true; }
            $out[] = '<li>' . preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', htmlspecialchars($m[2])) . '</li>';
        } elseif ($trimmed === '') {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<br>';
        } else {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<p>' . nl2br(preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', htmlspecialchars($line))) . '</p>';
        }
    }
    $closeBlock();
    if ($inList) $out[] = '</ol>';
    $html = implode("\n", $out);
    /* Iconos alineados con dashboard-reportes.php */
    $icons = [
        '[i:eye]' => '<i class="fas fa-eye text-blue-600" title="Ver"></i>',
        '[i:pdf]' => '<i class="fa-solid fa-file-pdf text-red-600" title="PDF"></i>',
        '[i:file-lines]' => '<i class="fa-solid fa-file-lines text-red-600" title="PDF Resumen"></i>',
        '[i:edit]' => '<i class="fa-solid fa-edit text-indigo-600" title="Editar"></i>',
        '[i:trash]' => '<i class="fa-solid fa-trash text-rose-600" title="Eliminar"></i>',
        '[i:copy]' => '<i class="fa-solid fa-copy text-emerald-600" title="Copiar"></i>',
        '[i:filter]' => '<i class="fas fa-filter text-slate-600" title="Filtros"></i>',
        '[i:qr]' => '<i class="fa-solid fa-qrcode text-slate-700" title="QR"></i>',
        '[i:paper-plane]' => '<i class="fa-solid fa-paper-plane text-blue-600" title="Correo"></i>',
        '[i:warning]' => '<i class="fas fa-exclamation-triangle" style="color:#eab308" title="Advertencia"></i>',
        '[i:whatsapp]' => '<i class="fab fa-whatsapp text-green-600" title="WhatsApp"></i>',
    ];
    foreach ($icons as $k => $v) $html = str_replace($k, $v, $html);
    $btns = [
        '[btn:agregar-fila]' => '<span class="guia-btn guia-btn-primary"><i class="fas fa-plus"></i> Agregar fila</span>',
        '[btn:guardar]' => '<span class="guia-btn guia-btn-primary"><i class="fas fa-save"></i> Guardar</span>',
        '[btn:filtrar]' => '<span class="guia-btn guia-btn-filtrar"><i class="fas fa-filter"></i> Filtrar</span>',
        '[btn:limpiar]' => '<span class="guia-btn guia-btn-limpiar">Limpiar</span>',
        '[btn:calcular-fechas]' => '<span class="guia-btn guia-btn-primary"><i class="fas fa-calculator"></i> Calcular fechas</span>',
    ];
    foreach ($btns as $k => $v) $html = str_replace($k, $v, $html);
    $links = [
        'programa-registro' => ['modules/planificacion/programas/dashboard-programas-registro.php', '📋 Programa - Registro', 'Registro de programas', '4.1.1 Registro'],
        'programa-listado' => ['modules/planificacion/programas/dashboard-programas-listado.php', '📋 Programa - Listado', 'Filtros y listado de programas', '4.1.2 Listado'],
        'asignacion-registro' => ['modules/planificacion/cronograma/dashboard-cronograma-registro.php', '📅 Asignación - Registro', 'Registro de cronograma', '4.2.1 Registro'],
        'asignacion-listado' => ['modules/planificacion/cronograma/dashboard-cronograma-listado.php', '📅 Asignación - Listado', 'Listado de cronogramas', '4.2.2 Listado'],
        'calendario' => ['modules/planificacion/calendario/dashboard-calendario.php', '📅 Cronograma', 'Vista por día, semana, mes y año', '4.3.1 Cronograma'],
        'comparativo' => ['modules/planificacion/cronograma/dashboard-comparativo.php', '⚖️ Comparativo', 'Necropsias vs Cronograma', '4.3.2 Comparativo'],
        'tipo-programa' => ['modules/configuracion/tipoPrograma/dashboard-tipo-programa.php', '📋 Tipos de Programa', 'Administre los tipos de programa', '7.8 Tipos de Programa'],
        'proveedor' => ['modules/configuracion/proveedor/dashboard-proveedor.php', '📦 Proveedor', 'Administre proveedores', '7.9 Proveedor'],
        'productos' => ['modules/configuracion/productos/dashboard-productos.php', '📦 Productos', 'Asigne proveedores a productos', '7.10 Productos'],
        'enfermedades' => ['modules/configuracion/enfermedades/dashboard-enfermedades.php', '🩺 Enfermedades', 'Gestione las enfermedades', '7.11 Enfermedades'],
        'whatsapp' => ['modules/configuracion/notificaciones_whatsapp/dashboard-notificaciones-whatsapp.php', '📱 Número telefónico', 'Configure su número para recordatorios por WhatsApp', '7.12 Número telefónico'],
        'notificaciones-usuarios' => ['modules/configuracion/notificaciones_usuarios/dashboard-notificaciones-usuarios.php', '👥 Notificaciones de usuarios', 'Gestione teléfonos autorizados', '7.13 Notificaciones de usuarios'],
    ];
    foreach ($links as $key => $d) {
        $txt = isset($d[3]) ? $d[3] : $d[1];
        $repl = '<a href="#" class="guia-link" data-url="' . htmlspecialchars($d[0]) . '" data-title="' . htmlspecialchars($d[1]) . '" data-subtitle="' . htmlspecialchars($d[2]) . '" title="Ir a ' . htmlspecialchars($d[1]) . '">' . htmlspecialchars($txt) . '</a>';
        $html = str_replace('[link:' . $key . ']', $repl, $html);
    }
    $renders = [
        '<!--RENDER:edad-->' => '<div class="guia-render"><span class="guia-render-label">Campo Edad (detalle)</span><div class="guia-render-input-wrap"><span class="guia-render-th">Edad <i class="fas fa-info-circle text-blue-500 text-sm" title="Edad 1 = fecha carga. -1 = un día antes."></i></span><input type="text" class="guia-render-input" value="1, 2, 5" readonly placeholder="1, 2, -1" style="width:70px;padding:0.25rem 0.5rem;font-size:0.75rem;border:1px solid #d1d5db;border-radius:0.2rem;background:#fff;"></div></div>',
    ];
    foreach ($renders as $k => $v) $html = str_replace($k, $v, $html);
    $flowRegistro = '<div class="guia-flow guia-flow-arrows" id="guia-flow-registro">
        <div class="guia-flow-row guia-flow-row-single">
            <div class="guia-flow-box"><a href="#" class="guia-flow-link" data-url="modules/planificacion/programas/dashboard-programas-registro.php" data-title="Programa - Registro" data-subtitle="Crear programa">Crear mi programa</a><div class="guia-flow-label">Defino el tipo y nombre del programa, descripción y detalles</div></div>
        </div>
        <div class="guia-flow-connector"><svg viewBox="0 0 50 60"><defs><marker id="arr1" markerWidth="8" markerHeight="8" refX="9" refY="4" orient="auto"><polygon points="0 0, 8 4, 0 8" fill="#059669"/></marker></defs><path d="M25 5 Q25 25 25 55" fill="none" stroke="#059669" stroke-width="2" marker-end="url(#arr1)"/></svg><span class="guia-flow-text">llevar el programa a dónde y cuándo</span></div>
        <div class="guia-flow-row guia-flow-row-single">
            <div class="guia-flow-box"><a href="#" class="guia-flow-link" data-url="modules/planificacion/cronograma/dashboard-cronograma-registro.php" data-title="Asignación - Registro" data-subtitle="Crear asignación">Crear mi asignación</a><div class="guia-flow-label">Elijo un programa creado, granjas (por zona o una por una) y año. Calculo fechas y guardo</div></div>
        </div>
        <div class="guia-flow-connector"><svg viewBox="0 0 50 60"><defs><marker id="arr2" markerWidth="8" markerHeight="8" refX="9" refY="4" orient="auto"><polygon points="0 0, 8 4, 0 8" fill="#059669"/></marker></defs><path d="M25 5 Q25 25 25 55" fill="none" stroke="#059669" stroke-width="2" marker-end="url(#arr2)"/></svg><span class="guia-flow-text">ver todo en el calendario</span></div>
        <div class="guia-flow-row guia-flow-row-single">
            <div class="guia-flow-box"><a href="#" class="guia-flow-link" data-url="modules/planificacion/calendario/dashboard-calendario.php" data-title="Cronograma" data-subtitle="Vista por día, semana, mes y año">Ver cronograma</a><div class="guia-flow-label">Ver por día, semana o mes. Reporte diario y enviar por WhatsApp</div></div>
        </div>
    </div>';
    $html = str_replace('<!--FLOW:registro-->', $flowRegistro, $html);
    $imgDir = __DIR__ . '/imagenes/';
    $html = preg_replace_callback('/<!--IMAGEN:(\d+)-->/', function($m) use ($imgDir) {
        $n = (int)$m[1];
        $base = $imgDir . 'imagen' . $n;
        $ext = file_exists($base . '.png') ? 'png' : (file_exists($base . '.jpg') ? 'jpg' : 'png');
        $src = 'imagenes/imagen' . $n . '.' . $ext;
        $alt = 'Imagen ' . $n;
        return '<figure class="guia-imagen"><img src="' . htmlspecialchars($src) . '" alt="' . $alt . '" loading="lazy"></figure>';
    }, $html);
    return $html;
}
$toc = [];
$html = md2html($contenido, $toc);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guía - Planificación</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <style>
        body { background: #f8f9fa; font-family: system-ui, sans-serif; margin: 0; }
        .guia-layout { display: grid; grid-template-columns: 180px 1fr; gap: 1rem; width: 100%; max-width: 100%; padding: 0.75rem 1rem; margin: 0 auto; box-sizing: border-box; }
        @media (min-width: 1200px) { .guia-layout { max-width: 1600px; } }
        @media (max-width: 900px) { .guia-layout { grid-template-columns: 1fr; } .guia-nav { position: static !important; } }
        .guia-nav { position: sticky; top: 1rem; height: fit-content; background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .guia-nav-title { font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem; }
        .guia-nav a { display: flex; align-items: center; padding: 0.4rem 0.6rem; font-size: 0.8125rem; color: #4b5563; text-decoration: none; border-radius: 0.375rem; transition: background 0.15s, color 0.15s; }
        .guia-nav-icon { width: 1rem; margin-right: 0.4rem; color: #059669; flex-shrink: 0; }
        .guia-nav a:hover { background: #f0fdf4; color: #059669; }
        .guia-nav a.active { background: #ecfdf5; color: #047857; font-weight: 500; }
        .guia-wrap { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; padding: 1.25rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); min-width: 0; }
        .guia-h1 { font-size: 1.35rem; font-weight: 700; color: #111827; margin: 0 0 0.5rem; padding-bottom: 0.4rem; border-bottom: 2px solid #10b981; }
        .guia-h2 { font-size: 1.1rem; font-weight: 700; color: #374151; margin: 0.65rem 0 0.25rem; padding-top: 0.15rem; scroll-margin-top: 1rem; }
        .guia-sec-icon { margin-right: 0.35rem; color: #059669; }
        .guia-h2:first-of-type { margin-top: 0; }
        .guia-h3 { font-size: 0.95rem; font-weight: 700; color: #4b5563; margin: 0.35rem 0 0.1rem; }
        .guia-link { color: #059669; font-weight: 600; text-decoration: none; }
        .guia-link:hover { text-decoration: underline; }
        .guia-placeholder { background: #fffbeb; border-left: 4px solid #d97706; padding: 0.4rem 0.65rem; margin: 0.35rem 0 0.5rem; font-size: 0.8125rem; color: #92400e; border-radius: 0 0.2rem 0.2rem 0; display: block; }
        .guia-wrap ol { margin: 0.25rem 0 0.4rem 1.25rem; padding-left: 1rem; }
        .guia-wrap li { margin-bottom: 0.15rem; }
        .guia-wrap p { margin: 0.2rem 0; line-height: 1.5; font-size: 0.9rem; color: #4b5563; }
        .guia-wrap strong { color: #111827; }
        .guia-block { margin: 0.35rem 0; padding: 0.5rem 0.75rem; background: #f8fafc; border-radius: 0.375rem; border-left: 3px solid #94a3b8; font-size: 0.875rem; }
        .guia-block-line { line-height: 1.5; margin-bottom: 0.25rem; }
        .guia-block-line:last-child { margin-bottom: 0; }
        .guia-render { margin: 0.35rem 0; padding: 0.45rem 0.65rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.375rem; display: inline-block; }
        .guia-render-label { display: block; font-size: 0.7rem; color: #64748b; margin-bottom: 0.35rem; }
        .guia-render-input-wrap { display: flex; align-items: center; gap: 0.5rem; }
        .guia-render-th { font-size: 0.75rem; font-weight: 500; color: #4b5563; }
        .guia-render-input { font-family: inherit; }
        .guia-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.25rem; white-space: nowrap; }
        .guia-btn-primary { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; }
        .guia-btn-filtrar { background: #2563eb; color: #fff; }
        .guia-btn-limpiar { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .guia-imagen { margin: 0.35rem 0; }
        .guia-imagen img { max-width: 100%; height: auto; border-radius: 0.375rem; border: 1px solid #e2e8f0; }
        .guia-footer { margin-top: 1.25rem; padding-top: 0.75rem; font-size: 0.75rem; color: #6b7280; }
        .guia-flow { margin: 0.5rem 0; padding: 0.75rem; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1px solid #a7f3d0; border-radius: 0.75rem; }
        .guia-flow-arrows { display: flex; flex-direction: column; align-items: center; max-width: 560px; margin-left: auto; margin-right: auto; }
        .guia-flow-row { display: flex; align-items: center; justify-content: center; gap: 0.5rem; flex-wrap: wrap; }
        .guia-flow-row-single { justify-content: center; }
        .guia-flow-arrow-h { display: flex; flex-direction: column; align-items: center; min-width: 70px; }
        .guia-flow-arrow-h .guia-arrow-svg { width: 50px; height: 24px; }
        .guia-flow-optional-tag { font-size: 0.65rem; color: #64748b; font-style: italic; }
        .guia-flow-with-side { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.5rem; max-width: 520px; margin-left: auto; margin-right: auto; }
        .guia-flow-main { display: flex; flex-direction: column; align-items: center; flex: 1; min-width: 200px; }
        .guia-flow-optional { display: flex; flex-direction: column; gap: 0.5rem; padding-left: 1rem; border-left: 2px dashed #94a3b8; }
        .guia-flow-optional-label { font-size: 0.7rem; color: #64748b; font-weight: 600; margin-bottom: 0.25rem; }
        .guia-flow-box-optional { background: #f8fafc !important; border: 1px dashed #94a3b8 !important; border-radius: 0.5rem; padding: 0.5rem 0.75rem !important; min-width: 150px; }
        .guia-flow-vertical { display: flex; flex-direction: column; align-items: center; max-width: 320px; margin-left: auto; margin-right: auto; }
        .guia-flow-item { width: 100%; display: flex; justify-content: center; }
        .guia-flow-box { display: flex; flex-direction: column; align-items: center; padding: 0.6rem 1rem; background: #fff; border: 2px solid #10b981; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(16,185,129,0.2); min-width: 200px; }
        .guia-flow-box .guia-flow-link { font-weight: 600; color: #047857; text-decoration: none; text-align: center; }
        .guia-flow-box .guia-flow-link:hover { text-decoration: underline; }
        .guia-flow-label { font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem; text-align: center; }
        .guia-flow-connector { display: flex; flex-direction: column; align-items: center; padding: 0.25rem 0; }
        .guia-flow-connector svg { width: 28px; height: 36px; }
        .guia-flow-text { font-size: 0.7rem; color: #047857; font-style: italic; margin-top: 0.15rem; max-width: 220px; text-align: center; }
        .guia-toolbar { margin-bottom: 1rem; }
        .guia-btn-pdf { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.75rem; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #fff; font-size: 0.8125rem; font-weight: 600; border-radius: 0.375rem; text-decoration: none; }
        .guia-btn-pdf:hover { background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%); color: #fff; }
        .guia-chat-fab { position: fixed; bottom: 1.5rem; right: 1.5rem; width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(5,150,105,0.4); z-index: 999; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .guia-chat-fab:hover { transform: scale(1.05); }
        .guia-chat-panel { position: fixed; bottom: 1.5rem; right: 1.5rem; width: 320px; max-width: calc(100vw - 2rem); height: 420px; background: #fff; border-radius: 0.75rem; box-shadow: 0 8px 24px rgba(0,0,0,0.15); z-index: 1000; display: flex; flex-direction: column; overflow: hidden; opacity: 0; visibility: hidden; transform: translateY(10px); transition: opacity 0.2s, visibility 0.2s, transform 0.2s; }
        .guia-chat-panel.guia-chat-open { opacity: 1; visibility: visible; transform: translateY(0); }
        .guia-chat-header { padding: 0.75rem 1rem; background: #059669; color: #fff; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .guia-chat-close { background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; line-height: 1; padding: 0 0.25rem; }
        .guia-chat-messages { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .guia-chat-msg { display: flex; }
        .guia-chat-msg.guia-chat-user { justify-content: flex-end; }
        .guia-chat-bubble { max-width: 85%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; line-height: 1.4; }
        .guia-chat-msg:not(.guia-chat-user) .guia-chat-bubble { background: #f0fdf4; color: #065f46; border: 1px solid #a7f3d0; }
        .guia-chat-msg.guia-chat-user .guia-chat-bubble { background: #059669; color: #fff; }
        .guia-chat-suggestions { padding: 0.6rem 0.75rem; display: flex; flex-wrap: wrap; gap: 0.4rem; border-top: 1px solid #e5e7eb; background: #fafafa; min-height: 72px; max-height: 140px; overflow-y: auto; align-content: flex-start; }
        .guia-chat-suggestion { padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #fff; border: 1px solid #a7f3d0; color: #047857; border-radius: 1rem; cursor: pointer; white-space: nowrap; }
        .guia-chat-suggestion:hover { background: #f0fdf4; border-color: #10b981; }
        .guia-chat-input-wrap { padding: 0.75rem; border-top: 1px solid #e5e7eb; display: flex; gap: 0.5rem; }
        .guia-chat-input { flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; }
        .guia-chat-send { padding: 0.5rem 0.75rem; background: #059669; color: #fff; border: none; border-radius: 0.5rem; cursor: pointer; }
        /* Alternar vistas: web vs revista */
        .guia-vista-web.hidden { display: none !important; }
        .guia-vista-revista.hidden { display: none !important; }
        .guia-vistas-toolbar { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
        .guia-vista-btn { padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; border: 2px solid #e5e7eb; background: #fff; color: #6b7280; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.2s; }
        .guia-vista-btn:hover { border-color: #10b981; color: #059669; background: #f0fdf4; }
        .guia-vista-btn.active { border-color: #10b981; background: #ecfdf5; color: #047857; }
        .guia-vista-revista-wrap { padding: 1rem; }
        .guia-vista-revista-wrap iframe { border: 0; width: 100%; height: 700px; min-height: 70vh; display: block; }
        /* Chatbot oculto */
        .guia-chat-fab, .guia-chat-panel { display: none !important; }
        /* Flipbook revista - contenedor libro */
        .guia-flipbook-loader p { margin: 0; font-size: 0.9rem; }
        .guia-libro-wrap { padding: 1rem; background: linear-gradient(180deg, #2d3748 0%, #1a202c 50%, #0d1117 100%); border-radius: 0.5rem; margin: 0 auto; max-width: 960px; box-shadow: inset 0 2px 20px rgba(0,0,0,0.4), 0 20px 60px rgba(0,0,0,0.5); }
        .guia-libro-inner { position: relative; margin: 0 auto; border-radius: 4px; box-shadow: 0 0 0 1px rgba(255,255,255,0.08), 0 4px 6px rgba(0,0,0,0.3), 0 12px 24px rgba(0,0,0,0.4), 0 24px 48px rgba(0,0,0,0.5); padding: 0.35rem; background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%); }
        #guiaFlipbook { border-radius: 2px; overflow: visible; }
        #guiaFlipbook .turn-page { background: #1a1a1a; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.2); padding: 0; }
        #guiaFlipbook .turn-page img { width: 100%; height: 100%; object-fit: contain; display: block; }
        #guiaFlipbookNav { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; margin-top: 1.25rem; }
        .guia-reveal { opacity: 0; transform: translateY(24px); transition: opacity 1.8s ease, transform 1.8s ease; }
        .guia-reveal.guia-revealed { opacity: 1; transform: translateY(0); }
    </style>
    <script src="../../../assets/js/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js"></script>
</head>
<body>
    <div class="guia-vistas-toolbar" style="padding: 0.75rem 1rem; background: #fff; border-bottom: 1px solid #e5e7eb;">
        <button type="button" class="guia-vista-btn active" data-vista="web" title="Contenido en formato web con navegación">
            <i class="fas fa-globe"></i> Vista web
        </button>
        <button type="button" class="guia-vista-btn" data-vista="revista" title="Guía en formato revista digital (flipbook)">
            <i class="fas fa-book-open"></i> Vista revista
        </button>
    </div>
    <div id="guiaVistaWeb" class="guia-vista-web">
    <div class="guia-layout">
        <?php if (!empty($toc)): ?>
        <nav class="guia-nav" id="guiaNav">
            <div class="guia-nav-title">Contenido</div>
            <?php
            $navIcons = [
                'flujo-de-registro' => 'fa-project-diagram', 'conceptos-del-modulo' => 'fa-lightbulb', 'conceptos-del-m-dulo' => 'fa-lightbulb',
                'programa-registro' => 'fa-clipboard', 'programa-listado' => 'fa-list',
                'asignacion-registro' => 'fa-calendar-plus', 'asignacion-listado' => 'fa-list',
                'cronograma' => 'fa-calendar-alt', 'comparativo' => 'fa-balance-scale',
                'configuracion' => 'fa-cog'
            ];
            foreach ($toc as $item):
                $ic = isset($navIcons[$item['id']]) ? '<i class="fas ' . $navIcons[$item['id']] . ' guia-nav-icon"></i> ' : '';
            ?>
            <a href="#<?= htmlspecialchars($item['id']) ?>" class="guia-nav-link" data-id="<?= htmlspecialchars($item['id']) ?>"><?= $ic ?><?= htmlspecialchars($item['titulo']) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
        <div class="guia-wrap">
            <div class="guia-toolbar">
                <a href="generar_guia_planificacion_pdf.php" target="_blank" rel="noopener" class="guia-btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> Descargar PDF
                </a>
            </div>
            <?= $html ?>
            <p class="guia-footer"><i class="fas fa-info-circle"></i> Guía del Sistema de Sanidad — Módulo Planificación</p>
        </div>
    </div>
    </div>
    <iframe allowfullscreen="allowfullscreen" allow="clipboard-write" scrolling="no" class="fp-iframe" src="https://heyzine.com/flip-book/9fba263cb9.html" style="border: 1px solid lightgray; width: 100%; height: 680px;"></iframe>   
    <button type="button" id="guiaChatBtn" class="guia-chat-fab" title="Ayuda FAQ"><i class="fas fa-comments"></i></button>
    <div id="guiaChatPanel" class="guia-chat-panel">
        <div class="guia-chat-header">
            <span>Ayuda Planificación</span>
            <button type="button" id="guiaChatClose" class="guia-chat-close">&times;</button>
        </div>
        <div id="guiaChatMessages" class="guia-chat-messages">
            <div class="guia-chat-msg"><div class="guia-chat-bubble">Hola. Haga clic en una pregunta o escriba la suya.</div></div>
        </div>
        <div class="guia-chat-suggestions" id="guiaChatSuggestions">
            <button type="button" class="guia-chat-suggestion" data-q="¿Qué es un programa?">¿Qué es un programa?</button>
            <button type="button" class="guia-chat-suggestion" data-q="¿Cómo edito un programa?">¿Cómo edito un programa?</button>
            <button type="button" class="guia-chat-suggestion" data-q="¿Cómo agrego detalles al programa?">¿Cómo agrego detalles?</button>
            <button type="button" class="guia-chat-suggestion" data-q="¿Qué es la asignación?">¿Qué es la asignación?</button>
            <button type="button" class="guia-chat-suggestion" data-q="¿Cómo creo una asignación?">¿Cómo creo una asignación?</button>
            <button type="button" class="guia-chat-suggestion" data-q="¿Qué es la edad -1?">¿Qué es la edad -1?</button>
            <button type="button" class="guia-chat-suggestion" data-q="¿Puedo ingresar edad 0?">¿Puedo ingresar edad 0?</button>
            <button type="button" class="guia-chat-suggestion" data-q="¿Cómo veo el calendario?">¿Cómo veo el calendario?</button>
            <button type="button" class="guia-chat-suggestion" data-q="¿Cuál es el flujo de registro?">¿Cuál es el flujo?</button>
        </div>
        <div class="guia-chat-input-wrap">
            <input type="text" id="guiaChatInput" class="guia-chat-input" placeholder="Escriba su pregunta...">
            <button type="button" id="guiaChatSend" class="guia-chat-send"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
    <script>
    (function() {
        var wrap = document.querySelector('#guiaVistaWeb .guia-wrap');
        if (wrap) {
            [].forEach.call(wrap.children, function(el) { if (el.tagName !== 'BR') el.classList.add('guia-reveal'); });
            var io = new IntersectionObserver(function(entries) { entries.forEach(function(e) { if (e.isIntersecting) e.target.classList.add('guia-revealed'); }); }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
            wrap.querySelectorAll('.guia-reveal').forEach(function(el) { io.observe(el); });
        }
        var vistaWeb = document.getElementById('guiaVistaWeb');
        var vistaRevista = document.getElementById('guiaVistaRevista');
        document.querySelectorAll('.guia-vista-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var v = this.getAttribute('data-vista');
                document.querySelectorAll('.guia-vista-btn').forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
                if (v === 'web') {
                    if (vistaWeb) vistaWeb.classList.remove('hidden');
                    if (vistaRevista) vistaRevista.classList.add('hidden');
                } else {
                    if (vistaWeb) vistaWeb.classList.add('hidden');
                    if (vistaRevista) vistaRevista.classList.remove('hidden');
                }
            });
        });

        var links = document.querySelectorAll('.guia-nav-link');
        var sections = document.querySelectorAll('.guia-section');
        function updateActive() {
            var best = null;
            for (var i = sections.length - 1; i >= 0; i--) {
                if (sections[i].getBoundingClientRect().top <= 120) {
                    best = sections[i].id;
                    break;
                }
            }
            if (!best && sections.length) best = sections[0].id;
            links.forEach(function(a) {
                a.classList.toggle('active', a.dataset.id === best);
            });
        }
        window.addEventListener('scroll', function() { requestAnimationFrame(updateActive); });
        document.querySelectorAll('.guia-nav a').forEach(function(a) {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('href').slice(1);
                document.getElementById(id).scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
        updateActive();
        function handleGuiaLink(a) {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                var url = this.getAttribute('data-url');
                var title = this.getAttribute('data-title');
                var subtitle = this.getAttribute('data-subtitle');
                if (url && window.parent && window.parent !== window && typeof window.parent.loadDashboardAndData === 'function') {
                    window.parent.loadDashboardAndData(url, title, subtitle);
                }
            });
        }
        document.querySelectorAll('.guia-link').forEach(handleGuiaLink);
        document.querySelectorAll('.guia-flow-link').forEach(handleGuiaLink);

        var chatBtn = document.getElementById('guiaChatBtn');
        var chatPanel = document.getElementById('guiaChatPanel');
        var chatClose = document.getElementById('guiaChatClose');
        var chatInput = document.getElementById('guiaChatInput');
        var chatSend = document.getElementById('guiaChatSend');
        var chatMessages = document.getElementById('guiaChatMessages');

        if (chatBtn && chatPanel) {
            chatBtn.addEventListener('click', function() {
                var open = chatPanel.classList.toggle('guia-chat-open');
                chatBtn.style.display = open ? 'none' : 'flex';
            });
        }
        if (chatClose) chatClose.addEventListener('click', function() {
            chatPanel.classList.remove('guia-chat-open');
            if (chatBtn) chatBtn.style.display = 'flex';
        });
        function addMsg(text, isUser) {
            var div = document.createElement('div');
            div.className = 'guia-chat-msg' + (isUser ? ' guia-chat-user' : '');
            div.innerHTML = '<div class="guia-chat-bubble">' + (isUser ? text : text) + '</div>';
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        function enviarPregunta() {
            var txt = (chatInput.value || '').trim();
            if (!txt) return;
            addMsg(txt.replace(/</g, '&lt;'), true);
            chatInput.value = '';
            chatSend.disabled = true;
            var form = new FormData();
            form.append('pregunta', txt);
            fetch('chat_faq.php', { method: 'POST', body: form })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    addMsg(data.respuesta || 'Sin respuesta.', false);
                })
                .catch(function() { addMsg('Error de conexión. Intente de nuevo.', false); })
                .finally(function() { chatSend.disabled = false; });
        }
        if (chatSend) chatSend.addEventListener('click', enviarPregunta);
        if (chatInput) chatInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') enviarPregunta(); });
        document.querySelectorAll('.guia-chat-suggestion').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var q = (btn.getAttribute('data-q') || '').trim();
                if (q && chatInput) { chatInput.value = q; enviarPregunta(); }
            });
        });

        // PDF Flipbook - revista
        (function initRevistaFlipbook() {
            var pdfUrl = 'revista.pdf';
            var flipEl = document.getElementById('guiaFlipbook');
            var loaderEl = document.getElementById('guiaFlipbookLoader');
            var navEl = document.getElementById('guiaFlipbookNav');
            var prevBtn = document.getElementById('guiaFlipPrev');
            var nextBtn = document.getElementById('guiaFlipNext');
            var pageInfo = document.getElementById('guiaFlipPageInfo');
            if (!flipEl || typeof pdfjsLib === 'undefined' || typeof jQuery === 'undefined') return;

            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
                var numPages = pdf.numPages;
                return pdf.getPage(1).then(function(firstPage) {
                    var vp = firstPage.getViewport({ scale: 1 });
                    var pdfAspect = vp.height / vp.width;
                    var maxW = Math.min(860, window.innerWidth - 80);
                    var maxH = Math.min(520, Math.floor(window.innerHeight * 0.55));
                    var pageWidth = Math.min(maxW, maxH / pdfAspect);
                    var pageHeight = Math.floor(pageWidth * pdfAspect);

                    flipEl.innerHTML = '';
                    for (var i = 1; i <= numPages; i++) {
                        var div = document.createElement('div');
                        div.className = 'turn-page';
                        div.style.width = pageWidth + 'px';
                        div.style.height = pageHeight + 'px';
                        div.style.overflow = 'hidden';
                        div.dataset.pageNum = i;
                        div.innerHTML = '<div style="padding:1rem;color:#94a3b8;"><i class="fas fa-spinner fa-spin"></i></div>';
                        flipEl.appendChild(div);
                    }

                    var libroWrap = document.getElementById('guiaLibroWrap');
                    if (loaderEl) loaderEl.style.display = 'none';
                    if (libroWrap) libroWrap.style.display = 'block';
                    flipEl.style.display = 'block';
                    if (navEl) navEl.style.display = 'flex';

                    function renderPageToDiv(pageNum, pageDiv) {
                        if (!pageDiv || pageDiv.querySelector('img')) return;
                        pdf.getPage(pageNum).then(function(pageObj) {
                        var vp1 = pageObj.getViewport({ scale: 1 });
                        var scale = Math.min(pageWidth / vp1.width, pageHeight / vp1.height);
                        var viewport = pageObj.getViewport({ scale: scale });
                        var canvas = document.createElement('canvas');
                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        var ctx = canvas.getContext('2d');
                        pageObj.render({ canvasContext: ctx, viewport: viewport }).promise.then(function() {
                            var img = document.createElement('img');
                            img.src = canvas.toDataURL('image/png');
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.objectFit = 'contain';
                            img.style.display = 'block';
                            pageDiv.innerHTML = '';
                            pageDiv.appendChild(img);
                        });
                    });
                }

                jQuery(flipEl).turn({
                    width: pageWidth,
                    height: pageHeight,
                    autoCenter: true,
                    when: {
                        turning: function(event, page) {
                            [page, page + 1].forEach(function(p) {
                                if (p > numPages) return;
                                var pageDiv = flipEl.querySelector('[data-page-num="' + p + '"]');
                                if (pageDiv && pageDiv.querySelector('.fa-spinner')) renderPageToDiv(p, pageDiv);
                            });
                        }
                    }
                });

                function updatePageInfo() {
                    var current = jQuery(flipEl).turn('page');
                    if (pageInfo) pageInfo.textContent = 'Página ' + current + ' de ' + numPages;
                }

                if (prevBtn) prevBtn.addEventListener('click', function() { jQuery(flipEl).turn('previous'); });
                if (nextBtn) nextBtn.addEventListener('click', function() { jQuery(flipEl).turn('next'); });
                jQuery(flipEl).bind('turned', updatePageInfo);
                updatePageInfo();

                renderPageToDiv(1, flipEl.querySelector('[data-page-num="1"]'));
                if (numPages >= 2) renderPageToDiv(2, flipEl.querySelector('[data-page-num="2"]'));
                });
            }).catch(function(err) {
                if (loaderEl) loaderEl.innerHTML = '<p style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i> No se pudo cargar la revista. ' + (err.message || '') + '</p>';
            });
        })();
    })();
    </script>
</body>
</html>
