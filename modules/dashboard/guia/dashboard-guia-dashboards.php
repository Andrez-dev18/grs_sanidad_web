<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; } else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}
$rutaMd = __DIR__ . '/GUIA-DASHBOARDS.md';
$contenido = file_exists($rutaMd) ? file_get_contents($rutaMd) : 'Guía no encontrada.';
$urlRevistaDashboards = '';

function slug($s) {
    $s = strtolower(trim($s));
    $s = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}
function md2html($md, &$toc) {
    $lines = explode("\n", $md);
    $out = []; $inList = false; $inBlock = false;
    $closeBlock = function() use (&$out, &$inBlock) {
        if ($inBlock) { $out[] = '</div>'; $inBlock = false; }
    };
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (preg_match('/^# (.+)$/', $trimmed, $m)) {
            $closeBlock(); if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<h1 class="guia-h1">' . htmlspecialchars($m[1]) . '</h1>';
        } elseif (preg_match('/^## (.+)$/', $trimmed, $m)) {
            $closeBlock(); if ($inList) { $out[] = '</ol>'; $inList = false; }
            $id = slug($m[1]); $tit = $m[1];
            $secIcons = [
                'dashboard-general' => 'fa-chart-line', 'dashboard-indicadores' => 'fa-chart-bar',
                'dashboard-tracking' => 'fa-location-dot'
            ];
            $icon = isset($secIcons[$id]) ? '<i class="fas ' . $secIcons[$id] . ' guia-sec-icon"></i> ' : '';
            $toc[] = ['id' => $id, 'titulo' => $tit];
            $out[] = '<h2 id="' . $id . '" class="guia-h2 guia-section">' . $icon . htmlspecialchars($tit) . '</h2>';
        } elseif (preg_match('/^### (.+)$/', $trimmed, $m)) {
            $closeBlock(); if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<h3 class="guia-h3">' . htmlspecialchars($m[1]) . '</h3>';
        } elseif (preg_match('/^\[imagen:(\d+)\]$/', $trimmed, $m)) {
            $closeBlock(); if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<!--IMAGEN:' . $m[1] . '-->';
        } elseif (preg_match('/^> (.+)$/', $trimmed, $m)) {
            $closeBlock(); if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<p class="guia-placeholder">' . htmlspecialchars($m[1]) . '</p>';
        } elseif (preg_match('/^\| (.+)$/', $trimmed, $m)) {
            if ($inList) { $out[] = '</ol>'; $inList = false; }
            if (!$inBlock) { $closeBlock(); $out[] = '<div class="guia-block">'; $inBlock = true; }
            $out[] = '<div class="guia-block-line">' . preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', htmlspecialchars($m[1])) . '</div>';
        } elseif (preg_match('/^(\d+)\. (.+)$/', $trimmed, $m)) {
            $closeBlock(); if (!$inList) { $out[] = '<ol>'; $inList = true; }
            $out[] = '<li>' . preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', htmlspecialchars($m[2])) . '</li>';
        } elseif ($trimmed === '') {
            $closeBlock(); if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<br>';
        } else {
            $closeBlock(); if ($inList) { $out[] = '</ol>'; $inList = false; }
            $out[] = '<p>' . nl2br(preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', htmlspecialchars($line))) . '</p>';
        }
    }
    $closeBlock(); if ($inList) $out[] = '</ol>';
    $html = implode("\n", $out);
    $icons = [
        '[i:cards]' => '<i class="fas fa-layer-group text-indigo-600" title="Tarjetas"></i>',
        '[i:table]' => '<i class="fas fa-table text-slate-600" title="Tabla"></i>',
        '[i:chart-bar]' => '<i class="fas fa-chart-bar text-blue-600" title="Gráfico de barras"></i>',
        '[i:chart-pie]' => '<i class="fas fa-chart-pie text-emerald-600" title="Gráfico circular"></i>',
        '[i:chart-line]' => '<i class="fas fa-chart-line text-blue-600" title="Gráfico de evolución"></i>',
        '[i:top]' => '<i class="fas fa-ranking-star text-amber-600" title="Top"></i>',
        '[i:filter]' => '<i class="fas fa-filter text-slate-600" title="Filtros"></i>',
        '[i:vial]' => '<i class="fas fa-vial text-teal-600" title="Muestras"></i>',
        '[i:disease]' => '<i class="fas fa-bacteria text-rose-600" title="Enfermedades"></i>',
        '[i:flask]' => '<i class="fas fa-flask text-cyan-600" title="Resultados"></i>',
        '[i:necropsia]' => '<i class="fas fa-notes-medical text-red-600" title="Necropsias"></i>',
        '[i:truck]' => '<i class="fas fa-truck text-blue-600" title="Envíos"></i>',
        '[i:clock]' => '<i class="fas fa-clock text-amber-600" title="Demora"></i>',
    ];
    foreach ($icons as $k => $v) $html = str_replace($k, $v, $html);
    $links = [
        'dashboard-general' => ['modules/dashboard/dashboard-dashboard.php', '📊 Dashboard General', 'Resumen visual de los datos', '1.1 Dashboard General'],
        'dashboard-indicadores' => ['modules/dashboard-indicadores/dashboard-indicadores.php', '📊 Dashboard Indicadores', 'Indicadores y estadísticas', '1.2 Dashboard Indicadores'],
        'dashboard-tracking' => ['modules/tracking/dashboard/dashboard-tracking.php', '📦 Dashboard Tracking', 'Estados de entrega y pedidos', '1.3 Dashboard Tracking'],
    ];
    foreach ($links as $key => $d) {
        $txt = isset($d[3]) ? $d[3] : $d[1];
        $repl = '<a href="#" class="guia-link" data-url="' . htmlspecialchars($d[0]) . '" data-title="' . htmlspecialchars($d[1]) . '" data-subtitle="' . htmlspecialchars($d[2]) . '" title="Ir a ' . htmlspecialchars($d[1]) . '">' . htmlspecialchars($txt) . '</a>';
        $html = str_replace('[link:' . $key . ']', $repl, $html);
    }
    $imgDir = __DIR__ . '/imagenes/';
    if (is_dir($imgDir)) {
        $html = preg_replace_callback('/<!--IMAGEN:(\d+)-->/', function($m) use ($imgDir) {
            $n = (int)$m[1];
            $base = $imgDir . 'imagen' . $n;
            $ext = file_exists($base . '.png') ? 'png' : (file_exists($base . '.jpg') ? 'jpg' : null);
            if (!$ext) return '';
            return '<figure class="guia-imagen"><img src="imagenes/imagen' . $n . '.' . $ext . '" alt="Imagen ' . $n . '" loading="lazy"></figure>';
        }, $html);
    } else {
        $html = preg_replace('/<!--IMAGEN:\d+-->/', '', $html);
    }
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
    <title>Guía - Dashboards</title>
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
        .guia-wrap ol, .guia-wrap ul { margin: 0.25rem 0 0.4rem 1.25rem; padding-left: 1rem; }
        .guia-wrap li { margin-bottom: 0.15rem; }
        .guia-wrap p { margin: 0.2rem 0; line-height: 1.5; font-size: 0.9rem; color: #4b5563; }
        .guia-wrap strong { color: #111827; }
        .guia-block { margin: 0.35rem 0; padding: 0.5rem 0.75rem; background: #f8fafc; border-radius: 0.375rem; border-left: 3px solid #94a3b8; font-size: 0.875rem; }
        .guia-block-line { line-height: 1.5; margin-bottom: 0.25rem; }
        .guia-block-line:last-child { margin-bottom: 0; }
        .guia-imagen { margin: 0.35rem 0; }
        .guia-imagen img { max-width: 100%; height: auto; border-radius: 0.375rem; border: 1px solid #e2e8f0; }
        .guia-footer { margin-top: 1.25rem; padding-top: 0.75rem; font-size: 0.75rem; color: #6b7280; }
        .guia-reveal { opacity: 0; transform: translateY(24px); transition: opacity 1.8s ease, transform 1.8s ease; }
        .guia-reveal.guia-revealed { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>
    <div class="guia-layout">
        <?php if (!empty($toc)): ?>
        <nav class="guia-nav" id="guiaNav">
            <div class="guia-nav-title">Contenido</div>
            <?php
            $navIcons = ['dashboard-general' => 'fa-chart-line', 'dashboard-indicadores' => 'fa-chart-bar', 'dashboard-tracking' => 'fa-location-dot'];
            foreach ($toc as $item):
                $ic = isset($navIcons[$item['id']]) ? '<i class="fas ' . $navIcons[$item['id']] . ' guia-sec-icon"></i> ' : '';
            ?>
            <a href="#<?= htmlspecialchars($item['id']) ?>" class="guia-nav-link" data-id="<?= htmlspecialchars($item['id']) ?>"><?= $ic ?><?= htmlspecialchars($item['titulo']) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
        <div class="guia-wrap">
            <?= $html ?>
            <p class="guia-footer"><i class="fas fa-info-circle"></i> Guía del Sistema de Sanidad — Dashboards</p>
        </div>
    </div>
    <script>
    (function() {
        var wrap = document.querySelector('.guia-wrap');
        if (wrap) {
            [].forEach.call(wrap.children, function(el) { if (el.tagName !== 'BR') el.classList.add('guia-reveal'); });
            var io = new IntersectionObserver(function(entries) { entries.forEach(function(e) { if (e.isIntersecting) e.target.classList.add('guia-revealed'); }); }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
            wrap.querySelectorAll('.guia-reveal').forEach(function(el) { io.observe(el); });
        }
        var sections = document.querySelectorAll('.guia-section');
        function updateActive() {
            var best = null;
            for (var i = sections.length - 1; i >= 0; i--) {
                if (sections[i].getBoundingClientRect().top <= 120) { best = sections[i].id; break; }
            }
            if (!best && sections.length) best = sections[0].id;
            document.querySelectorAll('.guia-nav-link').forEach(function(a) {
                a.classList.toggle('active', a.dataset.id === best);
            });
        }
        window.addEventListener('scroll', function() { requestAnimationFrame(updateActive); });
        document.querySelectorAll('.guia-nav a').forEach(function(a) {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                var el = document.getElementById(this.getAttribute('href').slice(1));
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
        updateActive();
        document.querySelectorAll('.guia-link').forEach(function(a) {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                var url = this.getAttribute('data-url');
                var title = this.getAttribute('data-title');
                var subtitle = this.getAttribute('data-subtitle');
                if (url && window.parent && window.parent !== window && typeof window.parent.loadDashboardAndData === 'function') {
                    window.parent.loadDashboardAndData(url, title, subtitle);
                }
            });
        });
    })();
    </script>
</body>
</html>
