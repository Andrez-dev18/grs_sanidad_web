<?php
/**
 * Genera el HTML del contenido de la guía de Muestras para web o PDF.
 * @param string $contenido Markdown
 * @param bool $forPdf Si true, usa texto en lugar de iconos FontAwesome
 * @return array ['html' => string, 'toc' => array]
 */
function getGuiaHtmlMuestras($contenido, $forPdf = false) {
    $toc = [];
    $html = md2htmlMuestras($contenido, $toc, $forPdf);
    return ['html' => $html, 'toc' => $toc];
}

function slugMuestras($s) {
    $s = strtolower(trim($s));
    $s = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function md2htmlMuestras($md, &$toc, $forPdf = false) {
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
            $id = slugMuestras($m[1]);
            $tit = $m[1];
            $toc[] = ['id' => $id, 'titulo' => $tit];
            $out[] = '<h2 id="' . $id . '" class="guia-h2 guia-section">' . htmlspecialchars($tit) . '</h2>';
        } elseif (preg_match('/^### (.+)$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<h3 class="guia-h3">' . htmlspecialchars($m[1]) . '</h3>';
        } elseif (preg_match('/^\[imagen:(\d+)\]$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<!--IMAGEN:' . $m[1] . '-->';
        } elseif (preg_match('/^> (.+)$/', $trimmed, $m)) {
            $closeBlock();
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<p class="guia-placeholder">' . htmlspecialchars($m[1]) . '</p>';
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
        '[i:eye]' => '<span class="guia-icon guia-icon-eye" title="Detalles">[Detalles]</span>',
        '[i:pdf]' => '<span class="guia-icon guia-icon-pdf" title="PDF">[PDF]</span>',
        '[i:file-lines]' => '<span class="guia-icon guia-icon-pdf" title="PDF Resumen">[PDF Resumen]</span>',
        '[i:history]' => '<span class="guia-icon guia-icon-history" title="Historial">[Historial]</span>',
        '[i:edit]' => '<span class="guia-icon guia-icon-edit" title="Editar">[Editar]</span>',
        '[i:trash]' => '<span class="guia-icon guia-icon-trash" title="Eliminar">[Eliminar]</span>',
        '[i:qr]' => '<span class="guia-icon guia-icon-qr" title="QR">[QR]</span>',
        '[i:paper-plane]' => '<span class="guia-icon guia-icon-mail" title="Correo">[Correo]</span>',
        '[i:warning]' => '<span class="guia-icon guia-icon-warning" title="Advertencia">[!]</span>',
        '[i:mail]' => '<span class="guia-icon guia-icon-mail" title="Correo">[Correo]</span>',
    ] : [
        '[i:eye]' => '<i class="fas fa-eye text-blue-600" title="Ver"></i>',
        '[i:pdf]' => '<i class="fa-solid fa-file-pdf text-red-600" title="PDF Tabla"></i>',
        '[i:file-lines]' => '<i class="fa-solid fa-file-lines text-red-600" title="PDF Resumen"></i>',
        '[i:edit]' => '<i class="fa-solid fa-edit text-indigo-600" title="Editar"></i>',
        '[i:trash]' => '<i class="fa-solid fa-trash text-rose-600" title="Eliminar"></i>',
        '[i:qr]' => '<i class="fa-solid fa-qrcode text-slate-700" title="QR"></i>',
        '[i:paper-plane]' => '<i class="fa-solid fa-paper-plane text-blue-600" title="Correo"></i>',
        '[i:warning]' => '<i class="fas fa-exclamation-triangle" style="color:#eab308" title="Advertencia"></i>',
        '[i:mail]' => '<i class="fas fa-envelope text-amber-600" title="Enviar correo"></i>',
        '[i:history]' => '<i class="fa-solid fa-clock-rotate-left text-amber-600" title="Historial"></i>',
    ];
    foreach ($icons as $k => $v) $html = str_replace($k, $v, $html);

    $btns = $forPdf ? [
        '[btn:guardar]' => '<span class="guia-btn guia-btn-primary">Guardar</span>',
        '[btn:filtrar]' => '<span class="guia-btn guia-btn-filtrar">Filtrar</span>',
    ] : [
        '[btn:guardar]' => '<span class="guia-btn guia-btn-primary"><i class="fas fa-save"></i> Guardar</span>',
        '[btn:filtrar]' => '<span class="guia-btn guia-btn-filtrar"><i class="fas fa-filter"></i> Filtrar</span>',
    ];
    foreach ($btns as $k => $v) $html = str_replace($k, $v, $html);

    $links = [
        'registro' => ['modules/registro_muestra/dashboard-registro-muestras.php', 'Registro de Muestras', 'Registro del pedido de muestra', '2.1 Registro de Muestras'],
        'listado' => ['modules/reportes/dashboard-reportes.php', 'Listado de Muestras', 'Listado de registros enviados', '2.2 Listado de Muestras'],
        'seguimiento' => ['modules/seguimiento/dashboard-seguimiento.php', 'Seguimiento de Muestras', 'Resultados cualitativos y cuantitativos', '2.3 Seguimiento de Muestras'],
        'config-transporte' => ['modules/configuracion/empTransporte/dashboard-empresas-transporte.php', 'Empresas de transporte', '7.1', '7.1 Empresas de transporte'],
        'config-laboratorio' => ['modules/configuracion/laboratorio/dashboard-laboratorio.php', 'Laboratorios', '7.2', '7.2 Laboratorios'],
        'config-tipo-muestra' => ['modules/configuracion/tipo_muestra/dashboard-tipo-muestra.php', 'Tipos de muestra', '7.3', '7.3 Tipos de muestra'],
        'config-tipo-analisis' => ['modules/configuracion/tipo_analisis/dashboard-analisis.php', 'Tipos de análisis', '7.4', '7.4 Tipos de análisis'],
        'config-paquete' => ['modules/configuracion/paquete_analisis/dashboard-paquete-analisis.php', 'Paquetes de análisis', '7.5', '7.5 Paquetes de análisis'],
        'config-tipo-respuesta' => ['modules/configuracion/tipo_respuesta/dashboard-respuesta.php', 'Tipos de respuesta', '7.6', '7.6 Tipos de respuesta'],
        'config-correo' => ['modules/configuracion/correo_contacto/dashboard-correo-contactos.php', 'Correo y contactos', '7.7', '7.7 Correo y contactos'],
    ];
    foreach ($links as $key => $d) {
        $txt = isset($d[3]) ? $d[3] : $d[1];
        $repl = '<span class="guia-link">' . htmlspecialchars($txt) . '</span>';
        $html = str_replace('[link:' . $key . ']', $repl, $html);
    }

    $imgDir = __DIR__ . '/imagenes/';
    $html = preg_replace_callback('/<!--IMAGEN:(\d+)-->/', function($m) use ($imgDir, $forPdf) {
        $n = (int)$m[1];
        $base = $imgDir . 'imagen' . $n;
        $ext = file_exists($base . '.png') ? 'png' : (file_exists($base . '.jpg') ? 'jpg' : null);
        if (!$ext) return '';
        $src = 'imagenes/imagen' . $n . '.' . $ext;
        $alt = 'Imagen ' . $n;
        $fig = '<figure class="guia-imagen"><img src="' . htmlspecialchars($src) . '" alt="' . $alt . '"></figure>';
        $caption = $forPdf ? '<p style="margin-top:16px;font-size:11pt;color:#6b7280;">Figura ' . $n . '</p>' : '';
        return $forPdf ? '<div class="guia-pagina-unica" style="page-break-before:always;page-break-after:always;">' . $fig . $caption . '</div>' : $fig;
    }, $html);

    return $html;
}
