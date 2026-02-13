<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>var u="../../../login.php";if(window.top!==window.self){window.top.location.href=u;}else{window.location.href=u;}</script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Comparativo - Necropsias vs Cronograma</title>

    <link href="../../../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
            border: none;
            padding: 0.625rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(16, 185, 129, 0.4);
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <div class="card-filtros-compacta mx-5 mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="w-full flex items-center justify-between px-6 py-4 bg-gray-50">
            <div class="flex items-center gap-2">
                <span class="text-lg">🔎</span>
                <h3 class="text-base font-semibold text-gray-800">Comparativo Necropsias vs Cronograma</h3>
            </div>
        </div>

        <div class="px-6 pb-6 pt-4">
            <p class="text-sm text-gray-600 mb-4">
                Compare lo registrado en necropsias con lo planificado en el cronograma (tipo Necropsias).
            </p>
            <div class="filter-row-periodo flex flex-wrap items-end gap-4 mb-4">
                <div class="flex-shrink-0" style="min-width: 200px;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-calendar-alt mr-1 text-blue-600"></i>Periodo
                    </label>
                    <select id="periodoTipo" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer text-sm">
                        <option value="TODOS">Todos</option>
                        <option value="POR_FECHA" selected>Por fecha</option>
                        <option value="ENTRE_FECHAS">Entre fechas</option>
                        <option value="POR_MES">Por mes</option>
                        <option value="ENTRE_MESES">Entre meses</option>
                        <option value="ULTIMA_SEMANA">Última Semana</option>
                    </select>
                </div>
                <div id="periodoPorFecha" class="flex-shrink-0 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-day mr-1 text-blue-600"></i>Fecha</label>
                    <input id="fechaUnica" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" />
                </div>
                <div id="periodoEntreFechas" class="hidden flex-shrink-0 flex items-end gap-2">
                    <div class="min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i>Desde</label>
                        <input id="fechaInicio" type="date" value="<?php echo date('Y-m-01'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div class="min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i>Hasta</label>
                        <input id="fechaFin" type="date" value="<?php echo date('Y-m-t'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>
                <div id="periodoPorMes" class="hidden flex-shrink-0 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar mr-1 text-blue-600"></i>Mes</label>
                    <input id="mesUnico" type="month" value="<?php echo date('Y-m'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div id="periodoEntreMeses" class="hidden flex-shrink-0 flex items-end gap-2">
                    <div class="min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i>Mes Inicio</label>
                        <input id="mesInicio" type="month" value="<?php echo date('Y') . '-01'; ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div class="min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i>Mes Fin</label>
                        <input id="mesFin" type="month" value="<?php echo date('Y') . '-12'; ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-shrink-0">
                    <button type="button" id="btnFiltrar" class="btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
                <div class="flex-shrink-0">
                    <button type="button" id="btnLimpiar" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 text-sm font-medium inline-flex items-center gap-2">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function aplicarVisibilidadPeriodoComparativo() {
        var t = (document.getElementById('periodoTipo') && document.getElementById('periodoTipo').value) || '';
        ['periodoPorFecha', 'periodoEntreFechas', 'periodoPorMes', 'periodoEntreMeses'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        });
        if (t === 'POR_FECHA') { var e1 = document.getElementById('periodoPorFecha'); if (e1) e1.classList.remove('hidden'); }
        else if (t === 'ENTRE_FECHAS') { var e2 = document.getElementById('periodoEntreFechas'); if (e2) e2.classList.remove('hidden'); }
        else if (t === 'POR_MES') { var e3 = document.getElementById('periodoPorMes'); if (e3) e3.classList.remove('hidden'); }
        else if (t === 'ENTRE_MESES') { var e4 = document.getElementById('periodoEntreMeses'); if (e4) e4.classList.remove('hidden'); }
    }

    function getParamsPeriodo() {
        return {
            periodoTipo: (document.getElementById('periodoTipo') && document.getElementById('periodoTipo').value) || 'POR_FECHA',
            fechaUnica: (document.getElementById('fechaUnica') && document.getElementById('fechaUnica').value) || '',
            fechaInicio: (document.getElementById('fechaInicio') && document.getElementById('fechaInicio').value) || '',
            fechaFin: (document.getElementById('fechaFin') && document.getElementById('fechaFin').value) || '',
            mesUnico: (document.getElementById('mesUnico') && document.getElementById('mesUnico').value) || '',
            mesInicio: (document.getElementById('mesInicio') && document.getElementById('mesInicio').value) || '',
            mesFin: (document.getElementById('mesFin') && document.getElementById('mesFin').value) || ''
        };
    }

    document.getElementById('btnFiltrar').addEventListener('click', function() {
        var p = getParamsPeriodo();
        var params = new URLSearchParams();
        params.set('periodoTipo', p.periodoTipo);
        if (p.fechaUnica) params.set('fechaUnica', p.fechaUnica);
        if (p.fechaInicio) params.set('fechaInicio', p.fechaInicio);
        if (p.fechaFin) params.set('fechaFin', p.fechaFin);
        if (p.mesUnico) params.set('mesUnico', p.mesUnico);
        if (p.mesInicio) params.set('mesInicio', p.mesInicio);
        if (p.mesFin) params.set('mesFin', p.mesFin);
        var url = '../cronograma/generar_reporte_necropsias_vs_cronograma.php?' + params.toString();
        window.open(url, '_blank');
    });

    document.getElementById('btnLimpiar').addEventListener('click', function() {
        var d = new Date();
        var pad = function(n) { return String(n).padStart(2, '0'); };
        var ymd = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        var first = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-01';
        var last = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate());
        var month = d.getFullYear() + '-' + pad(d.getMonth() + 1);
        var y = d.getFullYear();

        var p = document.getElementById('periodoTipo'); if (p) p.value = 'POR_FECHA';
        var fu = document.getElementById('fechaUnica'); if (fu) fu.value = ymd;
        var fi = document.getElementById('fechaInicio'); if (fi) fi.value = first;
        var ff = document.getElementById('fechaFin'); if (ff) ff.value = last;
        var mu = document.getElementById('mesUnico'); if (mu) mu.value = month;
        var mi = document.getElementById('mesInicio'); if (mi) mi.value = y + '-01';
        var mf = document.getElementById('mesFin'); if (mf) mf.value = y + '-12';
        aplicarVisibilidadPeriodoComparativo();
    });

    var periodoTipo = document.getElementById('periodoTipo');
    if (periodoTipo) periodoTipo.addEventListener('change', aplicarVisibilidadPeriodoComparativo);
    aplicarVisibilidadPeriodoComparativo();
})();
</script>
</body>
</html>
