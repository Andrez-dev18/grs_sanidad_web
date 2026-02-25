<?php
/**
 * Genera el HTML del contenido de la guía de Planificación.
 * @param string $contenido Markdown con [admin:notificaciones] ya reemplazado
 * @param bool $forPdf Si true, usa diagrama de flujo compatible con mPDF (tabla, sin flex/SVG)
 * @return array ['html' => string, 'toc' => array]
 */
function getGuiaHtml($contenido, $forPdf = false) {
    $toc = [];
    $html = md2html($contenido, $toc, $forPdf);
    return ['html' => $html, 'toc' => $toc];
}

function slug($s) {
    $s = strtolower(trim($s));
    $s = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function md2html($md, &$toc, $forPdf = false) {
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
                'programa' => 'fa-clipboard', 'asignacion' => 'fa-calendar-check', 'cronograma' => 'fa-calendar-alt',
                'configuracion' => 'fa-cog'
            ];
            $secEmoji = ['flujo-de-registro'=>'','conceptos-del-modulo'=>'','conceptos-del-m-dulo'=>'','programa'=>'','asignacion'=>'','cronograma'=>'','configuracion'=>''];
            $icon = $forPdf ? (isset($secEmoji[$id]) ? '<span class="guia-sec-icon">' . $secEmoji[$id] . '</span> ' : '') : (isset($secIcons[$id]) ? '<i class="fas ' . $secIcons[$id] . ' guia-sec-icon"></i> ' : '');
            $toc[] = ['id' => $id, 'titulo' => $tit];
            $out[] = '<h2 id="' . $id . '" class="guia-h2 guia-section">' . $icon . htmlspecialchars($tit) . '</h2>';
        } elseif (preg_match('/^### (.+)$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $h3tit = $m[1];
            $h3icons = ['Tipos de Programa' => 'fa-clipboard', 'Proveedor' => 'fa-box', 'Productos' => 'fa-boxes-stacked', 'Enfermedades' => 'fa-virus', 'Número telefónico' => 'fa-phone', 'Notificaciones de usuarios' => 'fa-users'];
            $h3icons = ['Tipos de Programa' => 'fa-clipboard', 'Proveedor' => 'fa-box', 'Productos' => 'fa-boxes-stacked', 'Enfermedades' => 'fa-virus', 'Número telefónico' => 'fa-phone', 'Notificaciones de usuarios' => 'fa-users'];
            $h3emoji = ['Tipos de Programa'=>'','Proveedor'=>'','Productos'=>'','Enfermedades'=>'','Número telefónico'=>'','Notificaciones de usuarios'=>''];
            $h3icon = $forPdf ? (isset($h3emoji[$h3tit]) ? '<span class="guia-sec-icon">' . $h3emoji[$h3tit] . '</span> ' : '') : (isset($h3icons[$h3tit]) ? '<i class="fas ' . $h3icons[$h3tit] . ' guia-sec-icon"></i> ' : '');
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
    $icons = $forPdf ? [
        '[i:eye]' => '<span class="guia-icon guia-icon-eye" title="Ver">[Ver]</span>',
        '[i:pdf]' => '<span class="guia-icon guia-icon-pdf" title="PDF">[PDF]</span>',
        '[i:edit]' => '<span class="guia-icon guia-icon-edit" title="Editar">[Editar]</span>',
        '[i:trash]' => '<span class="guia-icon guia-icon-trash" title="Eliminar">[Eliminar]</span>',
        '[i:copy]' => '<span class="guia-icon guia-icon-copy" title="Copiar">[Copiar]</span>',
        '[i:filter]' => '<span class="guia-icon guia-icon-filter" title="Filtros">[Filtrar]</span>',
        '[i:warning]' => '<span class="guia-icon guia-icon-warning" title="Advertencia">[!]</span>',
        '[i:whatsapp]' => '<span class="guia-icon guia-icon-whatsapp" title="WhatsApp">[WhatsApp]</span>',
    ] : [
        '[i:eye]' => '<i class="fas fa-eye text-blue-600" title="Ver"></i>',
        '[i:pdf]' => '<i class="fa-solid fa-file-pdf text-red-600" title="PDF"></i>',
        '[i:edit]' => '<i class="fa-solid fa-edit text-indigo-600" title="Editar"></i>',
        '[i:trash]' => '<i class="fa-solid fa-trash text-rose-600" title="Eliminar"></i>',
        '[i:copy]' => '<i class="fa-solid fa-copy text-emerald-600" title="Copiar"></i>',
        '[i:filter]' => '<i class="fas fa-filter text-slate-600" title="Filtros"></i>',
        '[i:warning]' => '<i class="fas fa-exclamation-triangle" style="color:#eab308" title="Advertencia"></i>',
        '[i:whatsapp]' => '<i class="fab fa-whatsapp text-green-600" title="WhatsApp"></i>',
    ];
    foreach ($icons as $k => $v) $html = str_replace($k, $v, $html);
    $btns = $forPdf ? [
        '[btn:agregar-fila]' => '<span class="guia-btn guia-btn-primary">+ Agregar fila</span>',
        '[btn:guardar]' => '<span class="guia-btn guia-btn-primary">Guardar</span>',
        '[btn:filtrar]' => '<span class="guia-btn guia-btn-filtrar">Filtrar</span>',
        '[btn:limpiar]' => '<span class="guia-btn guia-btn-limpiar">Limpiar</span>',
        '[btn:calcular-fechas]' => '<span class="guia-btn guia-btn-primary">Calcular fechas</span>',
    ] : [
        '[btn:agregar-fila]' => '<span class="guia-btn guia-btn-primary"><i class="fas fa-plus"></i> Agregar fila</span>',
        '[btn:guardar]' => '<span class="guia-btn guia-btn-primary"><i class="fas fa-save"></i> Guardar</span>',
        '[btn:filtrar]' => '<span class="guia-btn guia-btn-filtrar"><i class="fas fa-filter"></i> Filtrar</span>',
        '[btn:limpiar]' => '<span class="guia-btn guia-btn-limpiar">Limpiar</span>',
        '[btn:calcular-fechas]' => '<span class="guia-btn guia-btn-primary"><i class="fas fa-calculator"></i> Calcular fechas</span>',
    ];
    foreach ($btns as $k => $v) $html = str_replace($k, $v, $html);
    $links = [
        'programa-registro' => ['modules/planificacion/programas/dashboard-programas-registro.php', 'Programa - Registro', 'Registro de programas', '4.1.1 Registro'],
        'programa-listado' => ['modules/planificacion/programas/dashboard-programas-listado.php', 'Programa - Listado', 'Filtros y listado de programas', '4.1.2 Listado'],
        'asignacion-registro' => ['modules/planificacion/cronograma/dashboard-cronograma-registro.php', 'Asignación - Registro', 'Registro de cronograma', '4.2.1 Registro'],
        'asignacion-listado' => ['modules/planificacion/cronograma/dashboard-cronograma-listado.php', 'Asignación - Listado', 'Listado de cronogramas', '4.2.2 Listado'],
        'calendario' => ['modules/planificacion/calendario/dashboard-calendario.php', 'Calendario', 'Vista por día, semana, mes y año', '4.3.1 Calendario'],
        'comparativo' => ['modules/planificacion/cronograma/dashboard-comparativo.php', 'Comparativo', 'Necropsias vs Cronograma', '4.3.2 Comparativo'],
        'tipo-programa' => ['modules/configuracion/tipoPrograma/dashboard-tipo-programa.php', 'Tipos de Programa', 'Administre los tipos de programa', '7.8 Tipos de Programa'],
        'proveedor' => ['modules/configuracion/proveedor/dashboard-proveedor.php', 'Proveedor', 'Administre proveedores', '7.9 Proveedor'],
        'productos' => ['modules/configuracion/productos/dashboard-productos.php', 'Productos', 'Asigne proveedores a productos', '7.10 Productos'],
        'enfermedades' => ['modules/configuracion/enfermedades/dashboard-enfermedades.php', 'Enfermedades', 'Gestione las enfermedades', '7.11 Enfermedades'],
        'whatsapp' => ['modules/configuracion/notificaciones_whatsapp/dashboard-notificaciones-whatsapp.php', 'Número telefónico', 'Configure su número para recordatorios por WhatsApp', '7.12 Número telefónico'],
        'notificaciones-usuarios' => ['modules/configuracion/notificaciones_usuarios/dashboard-notificaciones-usuarios.php', 'Notificaciones de usuarios', 'Gestione teléfonos autorizados', '7.13 Notificaciones de usuarios'],
    ];
    foreach ($links as $key => $d) {
        $txt = isset($d[3]) ? $d[3] : $d[1];
        $repl = '<a href="#" class="guia-link" data-url="' . htmlspecialchars($d[0]) . '" data-title="' . htmlspecialchars($d[1]) . '" data-subtitle="' . htmlspecialchars($d[2]) . '" title="Ir a ' . htmlspecialchars($d[1]) . '">' . htmlspecialchars($txt) . '</a>';
        $html = str_replace('[link:' . $key . ']', $repl, $html);
    }
    $renderEdad = $forPdf
        ? '<div class="guia-render"><span class="guia-render-label">Campo Edad (detalle)</span><div class="guia-render-input-wrap"><span class="guia-render-th">Edad</span><input type="text" class="guia-render-input" value="1, 2, 5" readonly style="width:70px;padding:4px 8px;font-size:11pt;border:1px solid #d1d5db;border-radius:4px;background:#fff;"></div></div>'
        : '<div class="guia-render"><span class="guia-render-label">Campo Edad (detalle)</span><div class="guia-render-input-wrap"><span class="guia-render-th">Edad <i class="fas fa-info-circle text-blue-500 text-sm" title="Edad 1 = fecha carga. -1 = un día antes."></i></span><input type="text" class="guia-render-input" value="1, 2, 5" readonly placeholder="1, 2, -1" style="width:70px;padding:0.25rem 0.5rem;font-size:0.75rem;border:1px solid #d1d5db;border-radius:0.2rem;background:#fff;"></div></div>';
    $renders = ['<!--RENDER:edad-->' => $renderEdad];
    foreach ($renders as $k => $v) $html = str_replace($k, $v, $html);
    $flowRegistro = $forPdf
        ? '<div class="guia-flow-pdf guia-flow-pdf-side"><table cellpadding="6" cellspacing="0" border="0" style="width:100%;max-width:480px;margin:0 auto;border-collapse:collapse;"><tr><td style="text-align:center;padding:10px 12px;background:#fff;border:2px solid #10b981;font-weight:bold;font-size:11pt;">Crear mi programa</td><td style="text-align:center;width:50px;font-size:10pt;color:#94a3b8;">&#8594;</td><td style="text-align:center;padding:8px 10px;background:#f8fafc;border:1px dashed #94a3b8;font-size:10pt;"><strong>Ver mis programas</strong></td></tr><tr><td colspan="3" style="text-align:center;padding:4px;font-size:9pt;color:#047857;">&#8595; llevar el programa a dónde y cuándo</td></tr><tr><td style="text-align:center;padding:10px 12px;background:#fff;border:2px solid #10b981;font-weight:bold;font-size:11pt;">Crear mi asignación</td><td style="text-align:center;width:50px;font-size:10pt;color:#94a3b8;">&#8594;</td><td style="text-align:center;padding:8px 10px;background:#f8fafc;border:1px dashed #94a3b8;font-size:10pt;"><strong>Ver mis asignaciones</strong></td></tr><tr><td colspan="3" style="text-align:center;padding:4px;font-size:9pt;color:#047857;">&#8595; ver todo en el calendario</td></tr><tr><td colspan="3" style="text-align:center;padding:10px 12px;background:#fff;border:2px solid #10b981;font-weight:bold;font-size:11pt;">Calendario</td></tr></table></div>'
        : '<div class="guia-flow guia-flow-vertical" id="guia-flow-registro">
        <div class="guia-flow-item"><div class="guia-flow-box"><a href="#" class="guia-flow-link" data-url="modules/planificacion/programas/dashboard-programas-registro.php" data-title="Programa - Registro" data-subtitle="Crear programa">Crear mi programa</a><div class="guia-flow-label">Defino el tipo y nombre del programa, descripción y detalles</div></div></div>
        <div class="guia-flow-connector"><svg viewBox="0 0 50 60"><defs><marker id="arr1" markerWidth="8" markerHeight="8" refX="9" refY="4" orient="auto"><polygon points="0 0, 8 4, 0 8" fill="#059669"/></marker></defs><path d="M25 5 Q25 25 25 55" fill="none" stroke="#059669" stroke-width="2" marker-end="url(#arr1)"/></svg><span class="guia-flow-text">opcional: verlo en el listado</span></div>
        <div class="guia-flow-item"><div class="guia-flow-box"><a href="#" class="guia-flow-link" data-url="modules/planificacion/programas/dashboard-programas-listado.php" data-title="Programa - Listado" data-subtitle="Ver programas">Ver mis programas</a><div class="guia-flow-label">Aquí puedo buscar, ver, editar, copiar o eliminar mis programas</div></div></div>
        <div class="guia-flow-connector"><svg viewBox="0 0 50 60"><defs><marker id="arr2" markerWidth="8" markerHeight="8" refX="9" refY="4" orient="auto"><polygon points="0 0, 8 4, 0 8" fill="#059669"/></marker></defs><path d="M25 5 Q25 25 25 55" fill="none" stroke="#059669" stroke-width="2" marker-end="url(#arr2)"/></svg><span class="guia-flow-text">llevar el programa a dónde y cuándo</span></div>
        <div class="guia-flow-item"><div class="guia-flow-box"><a href="#" class="guia-flow-link" data-url="modules/planificacion/cronograma/dashboard-cronograma-registro.php" data-title="Asignación - Registro" data-subtitle="Crear asignación">Crear mi asignación</a><div class="guia-flow-label">Elijo un programa creado, granjas (por zona o una por una) y año. Calculo fechas y guardo</div></div></div>
        <div class="guia-flow-connector"><svg viewBox="0 0 50 60"><defs><marker id="arr3" markerWidth="8" markerHeight="8" refX="9" refY="4" orient="auto"><polygon points="0 0, 8 4, 0 8" fill="#059669"/></marker></defs><path d="M25 5 Q25 25 25 55" fill="none" stroke="#059669" stroke-width="2" marker-end="url(#arr3)"/></svg><span class="guia-flow-text">opcional: verla en el listado</span></div>
        <div class="guia-flow-item"><div class="guia-flow-box"><a href="#" class="guia-flow-link" data-url="modules/planificacion/cronograma/dashboard-cronograma-listado.php" data-title="Asignación - Listado" data-subtitle="Ver asignaciones">Ver mis asignaciones</a><div class="guia-flow-label">Buscar por granja, zona o fechas. Ver, editar o eliminar</div></div></div>
        <div class="guia-flow-connector"><svg viewBox="0 0 50 60"><defs><marker id="arr4" markerWidth="8" markerHeight="8" refX="9" refY="4" orient="auto"><polygon points="0 0, 8 4, 0 8" fill="#059669"/></marker></defs><path d="M25 5 Q25 25 25 55" fill="none" stroke="#059669" stroke-width="2" marker-end="url(#arr4)"/></svg><span class="guia-flow-text">ver todo en el calendario</span></div>
        <div class="guia-flow-item"><div class="guia-flow-box"><a href="#" class="guia-flow-link" data-url="modules/planificacion/calendario/dashboard-calendario.php" data-title="Calendario" data-subtitle="Vista por día, semana, mes y año">Calendario</a><div class="guia-flow-label">Ver por día, semana o mes. Descargar PDF, enviar por WhatsApp</div></div></div>
    </div>';
    $flowWrapped = $forPdf
        ? '<div class="guia-pagina-unica" style="page-break-before:always;page-break-after:always;"><h2 class="guia-h2" style="page-break-before:auto;margin-top:0;border:none;padding-left:0;">Flujo de registro</h2><p style="margin-bottom:28px;font-size:12pt;">Siga los pasos del diagrama para crear programas, asignaciones y consultar el calendario.</p>' . $flowRegistro . '</div>'
        : $flowRegistro;
    $html = str_replace('<!--FLOW:registro-->', $flowWrapped, $html);
    $imgDir = __DIR__ . '/imagenes/';
    $html = preg_replace_callback('/<!--IMAGEN:(\d+)-->/', function($m) use ($imgDir, $forPdf) {
        $n = (int)$m[1];
        $base = $imgDir . 'imagen' . $n;
        $ext = file_exists($base . '.png') ? 'png' : (file_exists($base . '.jpg') ? 'jpg' : 'png');
        $src = 'imagenes/imagen' . $n . '.' . $ext;
        $alt = 'Imagen ' . $n;
        $fig = '<figure class="guia-imagen"><img src="' . htmlspecialchars($src) . '" alt="' . $alt . '"></figure>';
        $caption = $forPdf ? '<p style="margin-top:16px;font-size:11pt;color:#6b7280;">Figura ' . $n . '</p>' : '';
        return $forPdf ? '<div class="guia-pagina-unica" style="page-break-before:always;page-break-after:always;">' . $fig . $caption . '</div>' : $fig;
    }, $html);
    return $html;
}
