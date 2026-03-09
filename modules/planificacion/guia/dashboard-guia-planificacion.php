<?php
/**
 * ===========================================================================
 * CONFIGURACIÓN DE LA REVISTA DIGITAL - GUÍA DE PLANIFICACIÓN
 * ===========================================================================
 */

// 📖 CANTIDAD DE CONTENIDO POR PÁGINA (en caracteres)
// Ajusta este valor para mostrar más o menos contenido en cada página de la revista
// Valores recomendados: 350-500 (menos contenido = más páginas, más legible)
//                       500-700 (contenido medio)
//                       700+    (más contenido por página, menos páginas)
$CHARS_PER_PAGE = 450;

// ===========================================================================
session_start();

/**
 * ===========================================================================
 * MARCADORES DE PÁGINA EN EL CONTENIDO (indicativos, no visibles)
 * ===========================================================================
 * Etiquetas para indicar manualmente dónde empieza cada página de la revista.
 * No se muestran al usuario, solo sirven para dividir el contenido.
 * 
 * [page]           - Salto de página: el contenido posterior va en una nueva página
 * [page:blank]     - Inserta una hoja en blanco antes de la siguiente página
 * 
 * Ejemplo en GUIA-PLANIFICACION.md:
 * ## Sección 1
 * Contenido de la sección 1...
 * 
 * [page]
 * 
 * ## Sección 2
 * Aquí comienza una nueva página (el [page] anterior no se ve)
 * 
 * [page:blank]
 * 
 * ## Sección 3
 * Esta sección comienza después de una hoja en blanco
 * ===========================================================================
 */
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
        } elseif (preg_match('/^\[page\]$/', $trimmed, $m) || preg_match('/^<!--PAGE-->$/', $trimmed, $m) || preg_match('/^<page>$/', $trimmed, $m)) {
            // Marcador indicativo de página (no visible)
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<!--PAGE-->';
        } elseif (preg_match('/^\[page:blank\]$/i', $trimmed, $m)) {
            // Marcador indicativo: hoja en blanco antes de la siguiente página
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<!--PAGE:BLANK-->';
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
        '<!--RENDER:proveedor-->' => '<div class="guia-render"><span class="guia-render-label">Campo Proveedor (detalle)</span><div class="guia-render-input-wrap"><span class="guia-render-th">Proveedor <i class="fas fa-info-circle text-blue-500 text-sm" title="En 7.10 Productos asigne proveedor al producto y se cargará por defecto. O bien, puede buscarlo directamente con el icono de lupa."></i></span><div class="guia-render-proveedor-wrap"><textarea readonly class="guia-render-input" placeholder="Proveedor (7.10)" style="width:120px;min-height:32px;padding:0.25rem 0.5rem;font-size:0.75rem;border:1px solid #d1d5db;border-radius:0.2rem;background:#f3f4f6;resize:none;">Proveedor ABC</textarea><button type="button" disabled class="guia-render-btn-lupa" title="Buscar proveedor"><i class="fas fa-search"></i></button></div></div></div>',
        '<!--RENDER:descripcion-->' => '<div class="guia-render"><span class="guia-render-label">Campo Descripción (detalle)</span><div class="guia-render-input-wrap guia-render-desc-wrap"><span class="guia-render-th">Descrip. <i class="fas fa-info-circle text-blue-500 text-sm" title="En 7.10 Productos edite producto: vacuna y enfermedades."></i></span><textarea readonly class="guia-render-input" placeholder="Descripción (7.10)" style="width:160px;min-height:36px;padding:0.25rem 0.5rem;font-size:0.75rem;border:1px solid #d1d5db;border-radius:0.2rem;background:#f3f4f6;resize:none;">Vacuna Gumboro</textarea></div></div>',
        '<!--RENDER:edad-->' => '<div class="guia-render"><span class="guia-render-label">Campo Edad (detalle)</span><div class="guia-render-input-wrap"><span class="guia-render-th">Edad <i class="fas fa-info-circle text-blue-500 text-sm" title="Edad 1 = fecha carga. -1 = un día antes."></i></span><input type="text" class="guia-render-input" value="1, 2, 5" readonly placeholder="1, 2, -1" style="width:70px;padding:0.25rem 0.5rem;font-size:0.75rem;border:1px solid #d1d5db;border-radius:0.2rem;background:#fff;"></div></div>',
    ];
    foreach ($renders as $k => $v) $html = str_replace($k, $v, $html);
    // Marcadores de página: reemplazar por elemento invisible (solo indicativo)
    $html = str_replace('<!--PAGE-->', '<span class="guia-page-marker" aria-hidden="true"></span>', $html);
    $html = str_replace('<!--PAGE:BLANK-->', '<span class="guia-page-marker guia-page-marker-blank" aria-hidden="true"></span>', $html);
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
    $imgDir = __DIR__ . '/imagenes/';
    $flowImg = null;
    if (file_exists($imgDir . 'flujo-registro.png')) $flowImg = 'imagenes/flujo-registro.png';
    elseif (file_exists($imgDir . 'flujo-registro.jpg')) $flowImg = 'imagenes/flujo-registro.jpg';
    $html = str_replace('<!--FLOW:registro-->', $flowImg ? '<figure class="guia-imagen guia-imagen-flujo"><img src="' . htmlspecialchars($flowImg) . '" alt="Flujo de registro" loading="lazy"></figure>' : '<div class="guia-imagen-flujo">' . $flowRegistro . '</div>', $html);
    // Solo se muestra el diagrama de flujo; el resto de imágenes no se renderizan en la guía
    $html = preg_replace_callback('/<!--IMAGEN:(\d+)-->/', function($m) { return ''; }, $html);
    return $html;
}
$toc = [];
$html = md2html($contenido, $toc);

// Páginas para vista revista (StPageFlip): portada + contenido por secciones
// Imagen de portada para la primera página de la revista
$portadaImgRel = 'imagenes/portada.png';
$baseDir = __DIR__ . '/';
if (!file_exists($baseDir . $portadaImgRel)) {
    // Fallback a imagen alternativa si no existe
    $portadaImgRel = file_exists($baseDir . 'imagenes/imagen1.png') ? 'imagenes/imagen1.png' : '';
}
$portadaImgSrc = $portadaImgRel;

/**
 * Divide el HTML por marcadores de página manuales (indicativos, no visibles)
 * Detecta: <!--PAGE-->, <!--PAGE:BLANK-->, guia-page-marker, [page], [page:blank]
 * Retorna array de bloques: ['content' => html, 'blank_before' => true/false]
 */
function split_by_page_markers($html) {
    $blocks = [];
    // Normalizar: convertir marcadores HTML a placeholders para split
    $html = str_replace('<span class="guia-page-marker guia-page-marker-blank" aria-hidden="true"></span>', "\n<!--PAGE:BLANK-->\n", $html);
    $html = str_replace('<span class="guia-page-marker" aria-hidden="true"></span>', "\n<!--PAGE-->\n", $html);
    // Patrón para detectar marcadores
    $pattern = '/<!--PAGE(?::BLANK)?-->|\[page(?::blank)?\]/i';
    $parts = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
    $currentContent = '';
    $lastMatch = '';
    $nextBlankBefore = false; // si el siguiente bloque debe tener página en blanco antes
    
    foreach ($parts as $part) {
        $isMarker = preg_match($pattern, trim($part));
        if ($isMarker) {
            // Marcador: guardar contenido acumulado con blank_before del marcador anterior
            if (trim($currentContent) !== '') {
                $blocks[] = ['content' => trim($currentContent), 'blank_before' => $nextBlankBefore];
            }
            $currentContent = '';
            $nextBlankBefore = (stripos($part, ':blank') !== false);
            $lastMatch = $part;
        } else {
            // Contenido
            $currentContent .= $part;
            $lastMatch = $part;
        }
    }
    
    if (trim($currentContent) !== '') {
        $blocks[] = ['content' => trim($currentContent), 'blank_before' => $nextBlankBefore];
    }
    
    return $blocks;
}

/**
 * Procesa el HTML respetando los marcadores de página manuales
 * Si hay marcadores [page], divide en esos puntos
 * Si no hay marcadores, usa el método tradicional por caracteres
 */
function process_page_markers($html, $maxChars) {
    // Verificar si hay marcadores en el HTML
    if (strpos($html, '[page') === false && strpos($html, '[page]') === false) {
        // No hay marcadores, usar método tradicional
        return process_traditional($html, $maxChars);
    }
    
    // Hay marcadores, dividir por ellos
    $pages = [];
    $markerBlocks = split_by_page_markers($html);
    
    foreach ($markerBlocks as $block) {
        $content = $block['content'];
        $blankBefore = $block['blank_before'];
        
        // Agregar hoja en blanco si se solicita
        if ($blankBefore && !empty($pages)) {
            $pages[] = ''; // hoja en blanco
        }
        
        // Dividir contenido grande por caracteres si excede el límite
        if (strlen(strip_tags($content)) > $maxChars * 1.2) {
            $subPages = split_html_into_pages($content, $maxChars);
            foreach ($subPages as $subPage) {
                if (trim($subPage) !== '') {
                    $pages[] = $subPage;
                }
            }
        } else {
            // Contenido cabe en una página
            if (trim($content) !== '') {
                $pages[] = $content;
            }
        }
    }
    
    return ['pages' => $pages, 'toc' => []];
}

/**
 * Método tradicional de procesamiento (sin marcadores)
 */
function process_traditional($html, $maxChars) {
    global $toc;
    $pages = [];
    $tocPages = [];
    $basePageNum = 3; // portada=1, blank=2, primera de contenido=3
    
    $bits = preg_split('/(<h2 id="[^"]+"[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $intro = isset($bits[0]) ? trim($bits[0]) : '';
    $allBlocks = [];
    $blockToc = [];
    
    // Intro: bloques por cantidad
    if ($intro !== '') {
        foreach (split_html_into_pages($intro, $maxChars) as $c) {
            if (trim(strip_tags($c)) !== '') {
                $allBlocks[] = $c;
                $blockToc[] = null;
            }
        }
    }
    
    // Secciones en orden
    for ($i = 1; $i < count($bits); $i += 2) {
        $h2tag = $bits[$i];
        $content = isset($bits[$i + 1]) ? $bits[$i + 1] : '';
        $fullSection = $h2tag . $content;
        $tocIdx = (int)(($i - 1) / 2);
        $isConceptos = (isset($toc[$tocIdx]['id']) && strpos($toc[$tocIdx]['id'], 'conceptos') !== false);
        
        if ($isConceptos && preg_match('/<h3 class="guia-h3">/', $fullSection)) {
            $chunks = pack_blocks_into_pages(split_conceptos_by_h3($fullSection), $maxChars);
        } else {
            $chunks = split_html_into_pages($fullSection, $maxChars);
        }
        
        if (count($chunks) === 0) $chunks = [$fullSection];
        $tocInfo = isset($toc[$tocIdx]) ? $toc[$tocIdx] : null;
        
        foreach ($chunks as $idx => $c) {
            $txt = trim(strip_tags($c));
            if ($txt !== '') {
                $allBlocks[] = $c;
                $blockToc[] = ($idx === 0 && $tocInfo) ? $tocInfo : null;
            }
        }
    }
    
    $result = pack_all_blocks_by_content($allBlocks, $blockToc, $maxChars);
    return ['pages' => $result['pages'], 'toc' => $result['toc']];
}

// Redistribuir contenido en páginas cortas; NUNCA cortar entre un h3 y su párrafo (evita que "Cronograma" quede sin su definición)
// Usa $CHARS_PER_PAGE definido al inicio del archivo
function split_html_into_pages($html, $maxChars) {
    $pages = [];
    if (trim($html) === '') return $pages;
    $len = strlen($html);
    $pos = 0;
    // No usar '</h3>' como punto de corte: así el título y su párrafo quedan siempre juntos
    $separadores = ['</p>', '</li>', '</div>', '</ul>', '</ol>', "\n\n"];
    while ($pos < $len) {
        if ($pos + $maxChars >= $len) {
            $chunk = trim(substr($html, $pos));
            if ($chunk !== '') $pages[] = $chunk;
            break;
        }
        $slice = substr($html, $pos, $maxChars + 400);
        $best = $maxChars;
        foreach ($separadores as $sep) {
            $p = strrpos(substr($slice, 0, $maxChars + 80), $sep);
            if ($p !== false && $p > (int)($maxChars * 0.3)) {
                $best = $p + strlen($sep);
                break;
            }
        }
        $chunk = trim(substr($html, $pos, $best));
        $pos += $best;
        if ($chunk !== '') $pages[] = $chunk;
    }
    return $pages;
}
function split_conceptos_by_h3($html) {
    // Aceptar h3 con posible icono: <h3 class="guia-h3">...contenido...</h3>
    $parts = preg_split('/(<h3 class="guia-h3">.*?<\/h3>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $pages = [];
    $n = count($parts);
    if ($n === 0) return [];
    $i = 0;
    $beforeFirst = trim($parts[0]);
    $i++;
    while ($i < $n) {
        $h3 = trim($parts[$i]);
        $after = ($i + 1 < $n) ? trim($parts[$i + 1]) : '';
        $i += 2;
        // Título y párrafo siempre juntos (no cortar entre Cronograma y su definición)
        $block = $h3 . $after;
        if ($block === '' || strlen(strip_tags($block)) < 3) continue;
        if (count($pages) === 0 && $beforeFirst !== '') {
            $block = $beforeFirst . $block;
            $beforeFirst = '';
        }
        $pages[] = $block;
    }
    if ($beforeFirst !== '' && strlen(strip_tags($beforeFirst)) > 0) $pages[] = $beforeFirst;
    return $pages;
}
/** Empaqueta bloques semánticos en páginas por límite de caracteres (evita una página por h3). */
function pack_blocks_into_pages($blocks, $maxChars) {
    $blocks = array_values(array_filter($blocks, function ($b) { return trim($b) !== '' && strlen(strip_tags($b)) > 0; }));
    if (count($blocks) === 0) return [];
    $pages = [];
    $current = '';
    $currentLen = 0;
    foreach ($blocks as $block) {
        $blockLen = strlen(strip_tags($block));
        if ($currentLen + $blockLen <= $maxChars && $current !== '') {
            $current .= $block;
            $currentLen += $blockLen;
        } else {
            if ($current !== '') {
                $pages[] = $current;
            }
            $current = $block;
            $currentLen = $blockLen;
        }
    }
    if ($current !== '') $pages[] = $current;
    return $pages;
}

/**
 * Empaqueta todos los bloques por cantidad de contenido (no por categoría).
 * $blocks = array de HTML; $tocForBlock[i] = null o ['id'=>,'titulo'=>] para el primer bloque de una sección.
 * Devuelve ['pages' => [...], 'toc' => [['id'=>,'titulo'=>,'page'=>], ...]].
 */
function pack_all_blocks_by_content($blocks, $tocForBlock, $maxChars) {
    $pages = [];
    $toc = [];
    $current = '';
    $currentLen = 0;
    $firstBlockOnPage = true;
    $basePageNum = 3; // portada=1, blank=2, primera de contenido=3
    foreach ($blocks as $i => $block) {
        $txt = trim(strip_tags($block));
        if ($txt === '') continue;
        $blockLen = strlen($txt);
        $tocInfo = isset($tocForBlock[$i]) ? $tocForBlock[$i] : null;
        if ($currentLen + $blockLen <= $maxChars && $current !== '') {
            if ($firstBlockOnPage && $tocInfo) {
                $toc[] = ['id' => $tocInfo['id'], 'titulo' => $tocInfo['titulo'], 'page' => $basePageNum + count($pages)];
                $firstBlockOnPage = false;
            }
            $current .= $block;
            $currentLen += $blockLen;
        } else {
            if ($current !== '') {
                $pages[] = $current;
            }
            $current = $block;
            $currentLen = $blockLen;
            $firstBlockOnPage = true;
            if ($tocInfo) {
                $toc[] = ['id' => $tocInfo['id'], 'titulo' => $tocInfo['titulo'], 'page' => $basePageNum + count($pages)];
                $firstBlockOnPage = false;
            }
        }
    }
    if ($current !== '') $pages[] = $current;
    return ['pages' => $pages, 'toc' => $toc];
}

$revistaPages = [];
$revistaPages[0] = 'portada';
$revistaPages[1] = ''; // hoja en blanco

// Procesar HTML con marcadores de página manuales
$processedHtml = process_page_markers($html, $CHARS_PER_PAGE);
$revistaToc = $processedHtml['toc'];
$revistaPagesContent = $processedHtml['pages'];

foreach ($revistaPagesContent as $p) {
    $revistaPages[] = $p;
}
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
        body { background: #f8f9fa; font-family: system-ui, sans-serif; margin: 0; height: 100%; overflow: hidden; display: flex; flex-direction: column; }
        .guia-layout { display: grid; grid-template-columns: 180px 1fr; gap: 1rem; width: 100%; max-width: 100%; padding: 0.75rem 1rem 1.5rem 1rem; margin: 0 auto; box-sizing: border-box; min-height: 0; }
        @media (min-width: 1200px) { .guia-layout { max-width: 1600px; } }
        @media (max-width: 900px) { .guia-layout { grid-template-columns: 1fr; } .guia-nav { position: static !important; } }
        .guia-nav { position: sticky; top: 1rem; height: fit-content; background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .guia-nav-title { font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem; }
        .guia-nav a { display: flex; align-items: center; padding: 0.4rem 0.6rem; font-size: 0.8125rem; color: #4b5563; text-decoration: none; border-radius: 0.375rem; transition: background 0.15s, color 0.15s; }
        .guia-nav-icon { width: 1rem; margin-right: 0.4rem; color: #059669; flex-shrink: 0; }
        .guia-nav a:hover { background: #f0fdf4; color: #059669; }
        .guia-nav a.active { background: #ecfdf5; color: #047857; font-weight: 500; }
        .guia-wrap { background: #fff; border-radius: 1rem; border: 1px solid #e5e7eb; padding: 1.25rem 1.5rem 2rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); min-width: 0; overflow: visible; }
        /* Cada concepto (ej. Cronograma + su definición) no se parte entre páginas */
        .guia-concept-block { page-break-inside: avoid; break-inside: avoid; margin-bottom: 0.5rem; }
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
        .guia-render-proveedor-wrap { display: flex; align-items: center; gap: 4px; }
        .guia-render-proveedor-wrap textarea { font-family: inherit; }
        .guia-render-btn-lupa { width: 28px; height: 28px; padding: 0; border: 1px solid #d1d5db; border-radius: 0.25rem; background: #fff; color: #9ca3af; display: inline-flex; align-items: center; justify-content: center; cursor: default; }
        .guia-render-desc-wrap textarea { font-family: inherit; }
        .guia-render-th { font-size: 0.75rem; font-weight: 500; color: #4b5563; }
        .guia-render-input { font-family: inherit; }
        .guia-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.25rem; white-space: nowrap; }
        .guia-btn-primary { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; }
        .guia-btn-filtrar { background: #2563eb; color: #fff; }
        .guia-btn-limpiar { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .guia-imagen { margin: 0.35rem 0; }
        .guia-imagen img { max-width: 100%; height: auto; border-radius: 0.375rem; border: 1px solid #e2e8f0; }
        .guia-footer { margin-top: 2rem; padding-top: 1rem; font-size: 0.75rem; color: #6b7280; border-top: 1px solid #e5e7eb; text-align: center; }
        .guia-flow { margin: 0.5rem 0; padding: 0.75rem; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1px solid #a7f3d0; border-radius: 0.75rem; }
        /* Separador de página para vista de revista */
        .guia-page-break { 
            margin: 2rem 0; 
            padding: 1rem 0; 
            text-align: center;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .guia-page-break::before {
            content: '❧';
            display: block;
            font-size: 1.5rem;
            color: #94a3b8;
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        .guia-page-break::after {
            content: 'Continúa en la siguiente página';
            display: block;
            font-size: 0.7rem;
            color: #94a3b8;
            font-style: italic;
            letter-spacing: 0.05em;
        }
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
        .guia-toolbar { margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .guia-btn-pdf { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #fff; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; text-decoration: none; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.3); transition: all 0.2s; }
        .guia-btn-pdf:hover { background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%); color: #fff; box-shadow: 0 4px 8px rgba(220, 38, 38, 0.4); transform: translateY(-1px); }
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
        /* Chatbot oculto */
        .guia-chat-fab, .guia-chat-panel { display: none !important; }
        /* Alternar vistas: web vs revista */
        html { height: 100%; margin: 0; overflow: hidden; }
        body { height: 100%; margin: 0; display: flex; flex-direction: column; }
        .guia-vista-web { flex: 1; min-height: 0; overflow-y: auto; }
        .guia-vista-web.hidden { display: none !important; }
        .guia-vista-revista { flex: 1; min-height: 0; overflow: hidden; }
        .guia-vista-revista.hidden { display: none !important; }
        .guia-vistas-toolbar { display: flex; gap: 0.5rem; margin-bottom: 0; flex-shrink: 0; padding: 0.75rem 1rem; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .guia-vista-btn { padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; border: 2px solid #e5e7eb; background: #fff; color: #6b7280; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .guia-vista-btn:hover { border-color: #10b981; color: #059669; background: #f0fdf4; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .guia-vista-btn.active { border-color: #10b981; background: #ecfdf5; color: #047857; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .guia-vista-revista-wrap { padding: 1rem; }
        /* Vista revista: índice externo a la izquierda + libro StPageFlip */
        .guia-revista-layout { display: flex; gap: 0; width: 100%; height: calc(100vh - 49px); flex: 1; min-height: 0; padding: 0; background: #1a1a2e; overflow: hidden; }
        #guiaVistaRevista { overflow: hidden; background: #1a1a2e; flex: 1; min-height: 0; display: flex; flex-direction: column; height: calc(100vh - 49px); max-height: calc(100vh - 49px); }
        .guia-revista-indice { width: 220px; flex-shrink: 0; background: #16213e; border-right: 1px solid rgba(255,255,255,0.08); padding: 1rem 0.75rem; overflow-y: auto; position: relative; z-index: 5; }
        .guia-revista-indice-title { font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; padding-left: 0.25rem; }
        .guia-revista-indice a { display: flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.5rem; font-size: 0.8rem; color: #cbd5e1; text-decoration: none; border-radius: 0.35rem; transition: background 0.15s, color 0.15s; }
        .guia-revista-indice a:hover { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .guia-revista-indice a.active { background: rgba(16, 185, 129, 0.25); color: #10b981; font-weight: 600; }
        .guia-revista-indice .guia-nav-icon { color: #10b981; width: 1rem; flex-shrink: 0; }
        .guia-revista-book-wrap { flex: 1; position: relative; min-width: 0; overflow: hidden; display: flex; flex-direction: column; background: linear-gradient(180deg, #1a1a2e 0%, #1a1a2e 80%, #25334a 95%, #1e293b 100%); padding: 0; margin-bottom: 0; }
        .guia-revista-book-scroll { flex: 1; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden; min-height: 0; padding: 0.25rem 0; }
        .guia-libro-wrap { background: transparent; position: relative; display: flex; flex-direction: column; align-items: center; padding-bottom: 0; margin-bottom: 0.25rem; max-width: 100%; }
        #guiaFlipbookLoader { text-align: center; padding: 2rem; color: #94a3b8; max-width: 100%; }
        /* Wrapper del libro: sombra tipo manual, sin reflejo inferior */
        /* Igual que manual: flipbook-wrapper y .page / .page-content / .page-body */
        #flipbook-wrapper { filter: drop-shadow(0 25px 60px rgba(0,0,0,0.7)) drop-shadow(0 8px 20px rgba(0,0,0,0.5)); background: transparent; margin-bottom: 0.25rem; position: relative; z-index: 10; max-width: 100%; overflow: hidden; }
        #flipbook { border-radius: 4px; overflow: hidden; position: relative; background: transparent; z-index: 5; max-width: 100%; }
        /* Contenedor interno del flipbook - controlar overflow para evitar desbordes */
        .stf__wrapper { overflow: hidden !important; }
        .stf__book { overflow: hidden !important; }
        .stf__page { overflow: hidden !important; }
        .stf__page-content { overflow: hidden !important; }
        .stf__shadow { overflow: hidden !important; }
        /* Cada .page tiene width/height en px desde JS; flex para que .page-content y .page-body repartan altura y el scroll quede solo en .page-body */
        .page { background: #fff; overflow: hidden; position: relative; font-family: system-ui, sans-serif; color: #1f2937; box-sizing: border-box; display: flex; flex-direction: column; }
        .page.-left, .page.-right, .page.-center, .page.-in-progress, .page.-current { overflow: hidden !important; }
        .page > * { position: relative; z-index: 1; }
        .page-content { padding: 16px 18px; flex: 1; min-height: 0; overflow: hidden; display: flex; flex-direction: column; gap: 0; box-sizing: border-box; }
        .page-body { flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch; padding-top: 0; padding-bottom: 2px; font-size: 0.8rem; line-height: 1.4; }
        .page-body::-webkit-scrollbar { width: 6px; }
        .page-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .page-body > *:first-child { margin-top: 0; padding-top: 0; }
        .page-body > * { max-width: 100%; overflow-wrap: break-word; word-wrap: break-word; }
        .page-body .guia-block, .page-body .guia-render, .page-body .guia-flow { max-width: 100%; overflow-x: auto; box-sizing: border-box; }
        /* Misma fuente en toda la página revista: base 0.8rem y títulos escalados */
        .page-body, .page-body p, .page-body li, .page-body .guia-block, .page-body .guia-block-line, .page-body .guia-placeholder, .page-body .guia-render, .page-body .guia-render-label, .page-body .guia-render-input, .page-body .guia-flow-label, .page-body .guia-flow-text { font-size: 0.8rem; }
        .page-body .guia-h1 { font-size: 1.05rem; margin: 0 0 0.4rem; }
        .page-body .guia-h2 { font-size: 0.9rem; margin: 0.4rem 0 0.2rem; }
        .page-body .guia-h3 { font-size: 0.82rem; margin: 0.35rem 0 0.1rem; }
        .page-body .guia-concept-block { page-break-inside: avoid; break-inside: avoid; }
        .page-body .guia-imagen img { max-width: 100%; height: auto; border-radius: 0.25rem; max-height: 180px; object-fit: contain; }
        .page-body .guia-flow { max-width: 100%; overflow-x: auto; }
        /* En vista revista solo se muestra el diagrama de flujo; el resto de imágenes se ocultan */
        .page-body .guia-imagen:not(.guia-imagen-flujo) { display: none !important; }
        .page-body .guia-imagen-flujo { display: block; }
        /* Portada (tapa) */
        .page.page-cover { display: flex; align-items: flex-end; padding: 0; position: relative; }
        .page.guia-portada-page { 
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat;
            background-color: #f0f0f0;
        }
        .page.guia-portada-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(255,255,255,0) 60%, rgba(255,255,255,0.9) 100%);
            pointer-events: none;
        }
        /* Páginas de contenido: estilo revista (hojas finas) */
        .page.page-soft { border-left: 1px solid rgba(0,0,0,0.05); }
        .page.page-blank { background: #fafbfc; }
        .page.page-blank .page-body { min-height: 0; }
        .guia-portada-title { position: absolute; left: 1rem; bottom: 1.5rem; right: 1rem; z-index: 10; }
        .guia-portada-title .guia-portada-line1 { font-family: system-ui, sans-serif; font-size: 2.5rem; font-weight: 800; color: #db2777; letter-spacing: -0.02em; line-height: 1.1; margin: 0; text-shadow: 0 2px 8px rgba(255,255,255,0.9); }
        .guia-portada-title .guia-portada-line2, .guia-portada-title .guia-portada-line3 { font-size: 1.1rem; font-weight: 700; color: #1e3a8a; margin: 0.25rem 0 0; text-shadow: 0 1px 4px rgba(255,255,255,0.8); }
        .guia-portada-title .guia-portada-sub { font-size: 0.85rem; font-weight: 500; color: #4b5563; margin: 0.2rem 0 0; }
        .guia-flipbook-nav-bar { display: flex !important; align-items: center; justify-content: center; flex-wrap: wrap; gap: 0.5rem; padding: 0.5rem 0.75rem; position: relative; height: 48px; flex-shrink: 0; box-sizing: border-box; z-index: 30; pointer-events: auto; background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-top: none; margin-top: 0; box-shadow: 0 -2px 8px rgba(0,0,0,0.25); }
        #guiaFlipbookNav button { padding: 0.35rem 0.75rem; background: #374151; color: #e5e7eb; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.8125rem; font-weight: 500; pointer-events: auto; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; }
        #guiaFlipbookNav button:hover:not(:disabled) { background: #4b5563; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        #guiaFlipbookNav button:disabled { opacity: 0.5; cursor: not-allowed; }
        .guia-revista-indice a { pointer-events: auto; cursor: pointer; }
        #pageIndicator { font-size: 0.8125rem; color: #e5e7eb; font-weight: 500; min-width: 6.5rem; text-align: center; background: rgba(255,255,255,0.1); padding: 0.25rem 0.6rem; border-radius: 0.375rem; }
        .guia-reveal { opacity: 0; transform: translateY(24px); transition: opacity 1.8s ease, transform 1.8s ease; }
        .guia-reveal.guia-revealed { opacity: 1; transform: translateY(0); }
    </style>
    <script src="../../../assets/js/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/page-flip@2.0.7/dist/js/page-flip.browser.min.js"></script>
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
    <div id="guiaVistaRevista" class="guia-vista-revista hidden">
        <div class="guia-revista-layout">
            <nav class="guia-revista-indice" id="guiaRevistaIndice">
                <div class="guia-revista-indice-title">Índice</div>
                <?php
                $navIconsRev = [
                    'conceptos-del-modulo' => 'fa-lightbulb', 'conceptos-del-m-dulo' => 'fa-lightbulb',
                    'flujo-de-registro' => 'fa-project-diagram', 'programa-registro' => 'fa-clipboard',
                    'programa-listado' => 'fa-list', 'asignacion-registro' => 'fa-calendar-plus',
                    'asignacion-listado' => 'fa-list', 'cronograma' => 'fa-calendar-alt',
                    'comparativo' => 'fa-balance-scale', 'configuracion' => 'fa-cog'
                ];
                foreach ($revistaToc as $item):
                    $ic = isset($navIconsRev[$item['id']]) ? '<i class="fas ' . $navIconsRev[$item['id']] . ' guia-nav-icon"></i> ' : '';
                    $pageIndex = (int)$item['page'] - 1; /* 0-based para goToPage */
                ?>
                <a href="javascript:void(0)" class="guia-revista-indice-link" data-page="<?= (int)$item['page'] ?>" onclick="if(window.goToPage){window.goToPage(<?= $pageIndex ?>);} return false;"><?= $ic ?><?= htmlspecialchars($item['titulo']) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="guia-revista-book-wrap">
                <div class="guia-revista-book-scroll">
                    <div class="guia-libro-wrap" id="guiaLibroWrap" style="display:none;">
                        <div id="flipbook-wrapper">
                            <div class="flip-book" id="flipbook">
                                <div class="page page-cover page-cover-top guia-portada-page" data-density="hard" style="background-image:url('<?= htmlspecialchars($portadaImgSrc) ?>')">
                                    <div class="guia-portada-title">
                                        <p class="guia-portada-line1">GUÍA</p>
                                        <p class="guia-portada-line2">MÓDULO</p>
                                        <p class="guia-portada-line3">PLANIFICACIÓN</p>
                                        <p class="guia-portada-sub">Paso a Paso</p>
                                    </div>
                                </div>
                                <?php for ($p = 1; $p < count($revistaPages); $p++): ?>
                                <div class="page page-soft <?= ($p === 1 && $revistaPages[$p] === '') ? 'page-blank' : '' ?>" data-density="soft"><div class="page-content"><div class="page-body"><?= $revistaPages[$p] ?></div></div></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <div id="guiaFlipbookLoader" class="guia-flipbook-loader" style="padding:2rem;color:#94a3b8;"><p><i class="fas fa-spinner fa-spin"></i> Cargando revista...</p></div>
                </div>
                <div id="guiaFlipbookNav" class="guia-flipbook-nav-bar">
                    <button type="button" id="btnPrev"><i class="fas fa-chevron-left"></i> Anterior</button>
                    <span id="pageIndicator">Página 1 de <?= count($revistaPages) ?></span>
                    <button type="button" id="btnNext">Siguiente <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </div>
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
                    // Recalcular dimensiones del flipbook al cambiar a vista revista
                    setTimeout(function() {
                        if (typeof window.initGuiaRevistaWhenVisible === 'function') {
                            window.initGuiaRevistaWhenVisible();
                        }
                    }, 150);
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

        // Vista revista: inicializar StPageFlip solo cuando el usuario cambie a "Vista revista" (contenedor visible = dimensiones correctas en iframe)
        var revistaInitialized = false;
        var pageFlipGlobal = null; // Referencia global para recalcular dimensiones
        window.initGuiaRevistaWhenVisible = function() {
            if (revistaInitialized) {
                // Ya está inicializado, solo recalcular dimensiones
                var scrollEl = document.querySelector('.guia-revista-book-scroll');
                var vw = window.innerWidth;
                var availW = scrollEl ? scrollEl.clientWidth - 40 : Math.min(vw - 260, 1100);
                // Restar menos espacio para la barra de navegación (48px) + padding mínimo
                var availH = scrollEl ? scrollEl.clientHeight - 60 : Math.max(450, window.innerHeight - 140);
                var pageH = Math.min(Math.max(400, availH), 850);
                var pageW = Math.round(pageH * (4 / 5.6));
                if (pageW * 2 > availW) { pageW = Math.floor(availW / 2); pageH = Math.round(pageW * (5.6 / 4)); }
                // Recalcular dimensiones de páginas
                var list = document.querySelectorAll('#flipbook .page');
                for (var i = 0; i < list.length; i++) {
                    list[i].style.width = pageW + 'px';
                    list[i].style.height = pageH + 'px';
                    list[i].style.minHeight = pageH + 'px';
                }
                // Forzar actualización del flipbook
                if (pageFlipGlobal && typeof pageFlipGlobal.update === 'function') {
                    pageFlipGlobal.update({ width: pageW, height: pageH });
                }
                return;
            }
            var flipEl = document.getElementById('flipbook');
            var loaderEl = document.getElementById('guiaFlipbookLoader');
            var navEl = document.getElementById('guiaFlipbookNav');
            var libroWrap = document.getElementById('guiaLibroWrap');
            var PageFlipClass = (typeof St !== 'undefined' && St.PageFlip) ? St.PageFlip : (typeof PageFlip !== 'undefined' ? PageFlip : null);
            if (!flipEl || !PageFlipClass) {
                if (loaderEl) loaderEl.innerHTML = '<p style="color:#94a3b8;">Revista no disponible.</p>';
                return;
            }
            var pageEls = document.querySelectorAll('#flipbook .page');
            if (!pageEls.length) {
                if (loaderEl) loaderEl.innerHTML = '<p style="color:#dc2626;">Sin páginas.</p>';
                return;
            }
            var TOTAL_PAGES = pageEls.length;
            function calcBookDims() {
                var scrollEl = document.querySelector('.guia-revista-book-scroll');
                var vw = window.innerWidth;
                var availW = scrollEl ? scrollEl.clientWidth - 40 : Math.min(vw - 260, 1100);
                // Restar menos espacio para la barra de navegación (48px) + padding mínimo
                var availH = scrollEl ? scrollEl.clientHeight - 60 : Math.max(450, window.innerHeight - 140);
                var pageH = Math.min(Math.max(400, availH), 850);
                var pageW = Math.round(pageH * (4 / 5.6));
                if (pageW * 2 > availW) { pageW = Math.floor(availW / 2); pageH = Math.round(pageW * (5.6 / 4)); }
                return { pageW: pageW, pageH: pageH };
            }
            var dims = calcBookDims();
            var pageFlip;
            try {
                pageFlip = new PageFlipClass(flipEl, {
                    width: dims.pageW,
                    height: dims.pageH,
                    size: 'fixed',
                    startPage: 0,
                    showCover: true,
                    drawShadow: true,
                    maxShadowOpacity: 0.7,
                    flippingTime: 800,
                    usePortrait: false,
                    startZIndex: 0,
                    mobileScrollSupport: false,
                    swipeDistance: 30,
                    clickEventForward: true
                });
                pageFlipGlobal = pageFlip; // Guardar referencia global
                pageFlip.loadFromHTML(pageEls);
                function applyPageDimensions() {
                    var list = document.querySelectorAll('.page');
                    for (var i = 0; i < list.length; i++) {
                        list[i].style.width = dims.pageW + 'px';
                        list[i].style.height = dims.pageH + 'px';
                        list[i].style.minHeight = dims.pageH + 'px';
                    }
                }
                applyPageDimensions();
                setTimeout(applyPageDimensions, 80);
                setTimeout(applyPageDimensions, 250);
                revistaInitialized = true;
                if (loaderEl) loaderEl.style.display = 'none';
                if (libroWrap) libroWrap.style.display = 'flex';
                function updateUI(currentPage) {
                    var indicator = document.getElementById('pageIndicator');
                    var prev = document.getElementById('btnPrev');
                    var next = document.getElementById('btnNext');
                    if (indicator) indicator.textContent = 'Página ' + (currentPage + 1) + ' de ' + TOTAL_PAGES;
                    if (prev) prev.disabled = currentPage === 0;
                    if (next) next.disabled = currentPage >= TOTAL_PAGES - 1;
                    document.querySelectorAll('.guia-revista-indice a').forEach(function(btn) {
                        var p = parseInt(btn.getAttribute('data-page'), 10);
                        btn.classList.toggle('active', p === (currentPage + 1));
                    });
                }
                pageFlip.on('flip', function(e) { updateUI(typeof e.data !== 'undefined' ? e.data : pageFlip.getCurrentPageIndex()); });
                pageFlip.on('changeState', function() { updateUI(pageFlip.getCurrentPageIndex()); });
                var btnPrev = document.getElementById('btnPrev');
                var btnNext = document.getElementById('btnNext');
                if (btnPrev) btnPrev.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); pageFlip.flipPrev('bottom'); setTimeout(function() { updateUI(pageFlip.getCurrentPageIndex()); applyPageDimensions(); }, 150); });
                if (btnNext) btnNext.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); pageFlip.flipNext('top'); setTimeout(function() { updateUI(pageFlip.getCurrentPageIndex()); applyPageDimensions(); }, 150); });
                document.addEventListener('keydown', function(e) {
                    if (!document.getElementById('guiaVistaRevista') || document.getElementById('guiaVistaRevista').classList.contains('hidden')) return;
                    if (e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === 'PageDown') { pageFlip.flipNext('top'); setTimeout(function() { updateUI(pageFlip.getCurrentPageIndex()); applyPageDimensions(); }, 150); e.preventDefault(); }
                    if (e.key === 'ArrowLeft' || e.key === 'ArrowUp' || e.key === 'PageUp') { pageFlip.flipPrev('bottom'); setTimeout(function() { updateUI(pageFlip.getCurrentPageIndex()); applyPageDimensions(); }, 150); e.preventDefault(); }
                });
                window.goToPage = function(pageNum) {
                    var n = parseInt(pageNum, 10);
                    if (isNaN(n) || n < 0 || n >= TOTAL_PAGES) return;
                    pageFlip.flip(n, 'top');
                    setTimeout(function() { updateUI(pageFlip.getCurrentPageIndex()); applyPageDimensions(); }, 150);
                };
                setTimeout(function() { updateUI(0); }, 100);
                document.body.addEventListener('click', function(e) {
                    var link = e.target.closest && (e.target.closest('#flipbook .guia-link') || e.target.closest('#flipbook .guia-flow-link'));
                    if (link) {
                        e.preventDefault();
                        var url = link.getAttribute('data-url');
                        var title = link.getAttribute('data-title');
                        var subtitle = link.getAttribute('data-subtitle');
                        if (url && window.parent && window.parent !== window && typeof window.parent.loadDashboardAndData === 'function') {
                            window.parent.loadDashboardAndData(url, title, subtitle);
                        }
                    }
                });
            } catch (err) {
                if (loaderEl) loaderEl.innerHTML = '<p style="color:#dc2626;">Error: ' + (err.message || '') + '</p>';
            }
        };
        // Inicializar en diferido cuando ya está visible (p. ej. si se recarga con vista revista activa o se llama desde fuera)
        var vistaRevistaEl = document.getElementById('guiaVistaRevista');
        if (vistaRevistaEl && !vistaRevistaEl.classList.contains('hidden')) {
            setTimeout(function() { window.initGuiaRevistaWhenVisible(); }, 80);
        }
    })();
    </script>
</body>
</html>
