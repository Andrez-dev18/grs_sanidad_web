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
    <title>Dashboard Planificación</title>

    <!-- Tailwind CSS y estilos del dashboard -->
    <link href="../../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../css/dashboard-config.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            overflow-y: auto;
        }
        html { overflow-y: auto; scroll-behavior: smooth; }
        .eventos-tabla-contenedor { overflow-x: auto; min-height: 14rem; }
        .eventos-tabla-contenedor.eventos-expandido { max-height: none; min-height: auto; }
        .modal-overlay-planif {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay-planif.hidden {
            display: none;
        }
        .modal-box-planif {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header-planif {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-body-planif {
            padding: 1.25rem;
        }
        .btn-ojito-granjas {
            background: none;
            border: none;
            color: #2563eb;
            cursor: pointer;
            padding: 0.25rem;
        }
        .btn-ojito-granjas:hover {
            color: #1d4ed8;
        }
        /* Modal Detalles (réplica del listado) */
        #planifModalDetalles.modal-overlay-planif { align-items: center; justify-content: center; }
        #planifModalDetalles .modal-box-planif { max-width: 900px; width: 100%; height: 75vh; min-height: 500px; max-height: 90vh; display: flex; flex-direction: column; }
        #planifModalDetalles .modal-body-planif { flex: 1; min-height: 0; overflow: hidden; display: flex; flex-direction: column; }
        .tabs-detalle-planif { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .tabs-detalle-planif .tab-btn { padding: 0.5rem 1rem; background: none; border: none; border-bottom: 2px solid transparent; color: #6b7280; cursor: pointer; font-size: 0.875rem; }
        .tabs-detalle-planif .tab-btn:hover { color: #2563eb; }
        .tabs-detalle-planif .tab-btn.active { color: #2563eb; border-bottom-color: #2563eb; }
        .tab-panel-planif { display: none; flex: 1; min-height: 0; overflow: auto; }
        .tab-panel-planif.active { display: flex; flex-direction: column; }
        .hidden-fila-proximo { display: none; }
        .tasa-tabla-contenedor { max-height: none; overflow-y: visible; }
        .tasa-tabla-contenedor.tasa-expandido { max-height: none; }
        .hidden-fila-tasa { display: none; }
        /* Marcas de sección: indicador de página/sección */
        .planif-marcas-seccion {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem 1rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 0.875rem;
        }
        .planif-marcas-seccion span { color: #6b7280; }
        .planif-marcas-seccion a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
        }
        .planif-marcas-seccion a:hover { background: #eff6ff; color: #1d4ed8; }
        .planif-marcas-seccion .separador { color: #d1d5db; user-select: none; }
        /* Etiqueta de sección dentro de cada bloque (indica en qué "página" estás) */
        .planif-seccion-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #4b5563;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            margin-bottom: 0.75rem;
        }
        /* Texto explicativo: tamaño uniforme y sin cortes */
        .planif-texto-concepto {
            font-size: 0.875rem;
            line-height: 1.5;
            word-spacing: normal;
            overflow-wrap: break-word;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">
        <!-- Filtro por año + Tarjetas KPI -->
        <div id="seccion-1" class="max-w-6xl mx-auto mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <div class="flex items-center gap-2">
                    <label for="yearFilter" class="text-sm font-medium text-gray-700">Filtrar por año:</label>
                    <select id="yearFilter"
                        class="px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </select>
                </div>
                <div id="kpiCards" class="grid grid-cols-2 md:grid-cols-4 gap-4 flex-1 min-w-0">
                    <div class="bg-gray-100 rounded-xl h-24 animate-pulse"></div>
                    <div class="bg-gray-100 rounded-xl h-24 animate-pulse"></div>
                    <div class="bg-gray-100 rounded-xl h-24 animate-pulse"></div>
                    <div class="bg-gray-100 rounded-xl h-24 animate-pulse"></div>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto space-y-8 mb-8">
            <div id="seccion-2" class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-100">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Resumen del estado (año seleccionado)</h4>
                        <div class="relative h-56">
                            <canvas id="chartCumplimientoResumen" aria-label="Resumen cumplimiento"></canvas>
                        </div>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Tasa de cumplimiento por asignación</h3>

                <div class="overflow-x-auto tasa-tabla-contenedor">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-2 py-2 w-8"></th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 uppercase tracking-wider">N°</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Programa</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 uppercase tracking-wider">Asig.</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 uppercase tracking-wider">Total</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 uppercase tracking-wider">Si cumplió</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 uppercase tracking-wider">Atrasado</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 uppercase tracking-wider">No cumplido</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 uppercase tracking-wider">Pendiente</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 uppercase tracking-wider">Tasa %</th>
                            </tr>
                        </thead>
                        <tbody id="tablaTasaCumplimiento" class="divide-y divide-gray-100">
                            <tr>
                                <td colspan="10" class="px-3 py-4 text-center text-gray-500">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="seccion-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200 flex flex-col">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Eventos hoy</h3>
                    <div class="overflow-x-auto eventos-tabla-contenedor">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Programa</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Granja</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Campaña</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Galpón</th>
                                </tr>
                            </thead>
                            <tbody id="tablaEventosHoy" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                    <p id="eventosHoyVacio" class="hidden text-sm text-gray-500 mt-2">Sin eventos hoy.</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200 flex flex-col">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Próximos 7 días</h3>
                    <div class="overflow-x-auto eventos-tabla-contenedor">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Programa</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Granja</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Campaña</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Galpón</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Fecha</th>
                                </tr>
                            </thead>
                            <tbody id="tablaEventosProximos" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                    <p id="eventosProximosVacio" class="hidden text-sm text-gray-500 mt-2">Sin eventos en los próximos 7 días.</p>
                    <button type="button" id="btnVerMasProximos" class="hidden mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">Ver más</button>
                </div>
                </div>
            </div>

            <!-- Modal Detalles (Fechas / Granjas / Programa) -->
            <div id="planifModalDetalles" class="modal-overlay-planif hidden">
                <div class="modal-box-planif">
                    <div class="modal-header-planif">
                        <h3 class="text-lg font-semibold text-gray-800">Detalles de la asignación</h3>
                        <button type="button" id="planifModalDetallesCerrar" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                    </div>
                    <div class="modal-body-planif p-4">
                        <p class="text-sm font-medium text-gray-500 mb-2">Programa: <strong id="planifDetallesCodPrograma"></strong> — <span id="planifDetallesTotal">0</span> registro(s)</p>
                        <div class="tabs-detalle-planif">
                            <button type="button" class="tab-btn active" data-tab="fechas">Fechas</button>
                            <button type="button" class="tab-btn" data-tab="granjas">Granjas</button>
                            <button type="button" class="tab-btn" data-tab="programa">Programa</button>
                        </div>
                        <div id="planifTabPanelFechas" class="tab-panel-planif active">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <label class="inline-flex items-center gap-1 text-sm"><span>Mostrar</span><select id="planifDetallesPageSize" class="border border-gray-300 rounded px-2 py-1 text-sm"><option value="20">20</option><option value="50">50</option><option value="100">100</option></select><span>registros</span></label>
                                <label class="inline-flex items-center gap-1 text-sm"><span>Buscar:</span><input type="text" id="planifDetallesSearch" class="border border-gray-300 rounded px-2 py-1 text-sm w-40" placeholder="Buscar..."></label>
                            </div>
                            <div class="overflow-x-auto flex-1 min-h-0">
                                <table class="min-w-full text-sm border border-gray-200 rounded-lg">
                                    <thead><tr>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">N°</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Granja</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Nom. Granja</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Campaña</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Galpón</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Edad</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Fec. Carga</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Fec. Ejecución</th>
                                    </tr></thead>
                                    <tbody id="planifDetallesLista"></tbody>
                                </table>
                            </div>
                            <div id="planifDetallesToolbarBottom" class="text-sm text-gray-600 mt-2"></div>
                        </div>
                        <div id="planifTabPanelGranjas" class="tab-panel-planif">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <label class="inline-flex items-center gap-1 text-sm"><span>Mostrar</span><select id="planifDetallesGranjasPageSize" class="border border-gray-300 rounded px-2 py-1 text-sm"><option value="20">20</option><option value="50">50</option><option value="100">100</option></select><span>registros</span></label>
                                <label class="inline-flex items-center gap-1 text-sm"><span>Buscar:</span><input type="text" id="planifDetallesGranjasSearch" class="border border-gray-300 rounded px-2 py-1 text-sm w-40" placeholder="Buscar..."></label>
                            </div>
                            <div class="overflow-x-auto flex-1 min-h-0">
                                <table class="min-w-full text-sm border border-gray-200 rounded-lg">
                                    <thead><tr>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">N°</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Granja</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Nom. Granja</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Galpón</th>
                                        <th class="px-3 py-2 text-left bg-blue-600 text-white">Campaña</th>
                                    </tr></thead>
                                    <tbody id="planifDetallesListaGranjas"></tbody>
                                </table>
                            </div>
                            <div id="planifDetallesGranjasToolbarBottom" class="text-sm text-gray-600 mt-2"></div>
                        </div>
                        <div id="planifTabPanelPrograma" class="tab-panel-planif">
                            <div id="planifDetallesProgramaCab" class="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"></div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm border border-gray-200 rounded-lg">
                                    <thead class="bg-gray-50 border-b border-gray-200" id="planifDetallesProgramaThead"></thead>
                                    <tbody id="planifDetallesProgramaBody"></tbody>
                                </table>
                            </div>
                            <p id="planifDetallesProgramaSinReg" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle del programa.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-12">
                <p class="text-gray-500 text-sm">
                    Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> -
                    © <span id="currentYear"></span>
                </p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();

        (function () {
            var yearSelect = document.getElementById('yearFilter');
            var currentYear = new Date().getFullYear();
            var selectedYear = currentYear;

            var colorsKPI = [
                'bg-blue-50 border-blue-200 text-blue-800',
                'bg-emerald-50 border-emerald-200 text-emerald-800',
                'bg-amber-50 border-amber-200 text-amber-800',
                'bg-violet-50 border-violet-200 text-violet-800'
            ];
            var chartCumplimientoResumen = null;

            function populateYearSelect() {
                yearSelect.innerHTML = '';
                var startYear = currentYear - 5;
                for (var y = currentYear; y >= startYear; y--) {
                    var opt = document.createElement('option');
                    opt.value = y;
                    opt.textContent = y;
                    yearSelect.appendChild(opt);
                }
                yearSelect.value = currentYear;
                selectedYear = currentYear;
            }

            function esc(s) {
                if (s == null || s === undefined) return '';
                var d = document.createElement('div');
                d.textContent = s;
                return d.innerHTML;
            }

            function renderKPICards(data, eventosHoy, eventosProximos) {
                var container = document.getElementById('kpiCards');
                if (!container) return;
                var programas = data.totalProgramas != null ? data.totalProgramas : '—';
                var asignaciones = data.totalAsignaciones != null ? data.totalAsignaciones : '—';
                var hoy = (eventosHoy === undefined || eventosHoy === null) ? '—' : eventosHoy;
                var proximos = (eventosProximos === undefined || eventosProximos === null) ? '—' : eventosProximos;

                container.innerHTML =
                    '<div class="bg-gradient-to-br ' + colorsKPI[0] + ' rounded-xl shadow-sm p-4 border text-center flex flex-col justify-center">' +
                    '<p class="text-sm font-medium">Programas</p>' +
                    '<p class="text-xl font-bold mt-1">' + programas + '</p></div>' +
                    '<div class="bg-gradient-to-br ' + colorsKPI[1] + ' rounded-xl shadow-sm p-4 border text-center flex flex-col justify-center">' +
                    '<p class="text-sm font-medium">Asignaciones</p>' +
                    '<p class="text-xl font-bold mt-1">' + asignaciones + '</p></div>' +
                    '<div class="bg-gradient-to-br ' + colorsKPI[2] + ' rounded-xl shadow-sm p-4 border text-center flex flex-col justify-center">' +
                    '<p class="text-sm font-medium">Eventos hoy</p>' +
                    '<p class="text-xl font-bold mt-1" id="kpiHoyVal">' + hoy + '</p></div>' +
                    '<div class="bg-gradient-to-br ' + colorsKPI[3] + ' rounded-xl shadow-sm p-4 border text-center flex flex-col justify-center">' +
                    '<p class="text-sm font-medium">Próximos 7 días</p>' +
                    '<p class="text-xl font-bold mt-1" id="kpiProximosVal">' + proximos + '</p></div>';
            }

            function loadKPIs(eventosHoy, eventosProximos) {
                fetch('get_dashboard_planificacion.php?year=' + selectedYear)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            renderKPICards(data, eventosHoy, eventosProximos);
                        }
                    })
                    .catch(function () {
                        renderKPICards({ totalProgramas: '—', totalAsignaciones: '—' }, eventosHoy, eventosProximos);
                    });
            }

            var LIMITE_VISIBLE_PROXIMOS = 5;
            var eventosProximosExpandido = false;

            function loadEventos() {
                return fetch('cronograma/get_resumen_eventos.php')
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.success) return { hoy: 0, proximos: 0 };
                        var hoy = data.hoy || 0;
                        var proximos = data.proximos || 0;
                        var tHoy = document.getElementById('tablaEventosHoy');
                        var tProx = document.getElementById('tablaEventosProximos');
                        var vacioHoy = document.getElementById('eventosHoyVacio');
                        var vacioProx = document.getElementById('eventosProximosVacio');
                        var btnVerMas = document.getElementById('btnVerMasProximos');
                        var contProx = document.querySelector('#tablaEventosProximos') ? document.querySelector('#tablaEventosProximos').closest('.eventos-tabla-contenedor') : null;

                        if (document.getElementById('kpiHoyVal')) document.getElementById('kpiHoyVal').textContent = hoy;
                        if (document.getElementById('kpiProximosVal')) document.getElementById('kpiProximosVal').textContent = proximos;

                        var listHoy = data.eventosHoy || [];
                        var listProx = data.eventosProximos || [];
                        if (listHoy.length === 0) {
                            if (tHoy) tHoy.innerHTML = '';
                            if (vacioHoy) vacioHoy.classList.remove('hidden');
                        } else {
                            if (vacioHoy) vacioHoy.classList.add('hidden');
                            if (tHoy) tHoy.innerHTML = listHoy.map(function (ev) {
                                return '<tr class="hover:bg-gray-50"><td class="px-3 py-2">' + esc(ev.nomPrograma || ev.codPrograma) + '</td><td class="px-3 py-2">' + esc(ev.nomGranja || ev.granja) + '</td><td class="px-3 py-2">' + esc(ev.campania) + '</td><td class="px-3 py-2">' + esc(ev.galpon) + '</td></tr>';
                            }).join('');
                        }
                        eventosProximosExpandido = false;
                        if (contProx) contProx.classList.remove('eventos-expandido');
                        if (listProx.length === 0) {
                            if (tProx) tProx.innerHTML = '';
                            if (vacioProx) vacioProx.classList.remove('hidden');
                            if (btnVerMas) { btnVerMas.classList.add('hidden'); btnVerMas.textContent = 'Ver más'; }
                        } else {
                            if (vacioProx) vacioProx.classList.add('hidden');
                            var fmt = function (d) {
                                if (!d) return '—';
                                d = String(d).substr(0, 10);
                                if (d.length >= 10) return d.split('-').reverse().join('/');
                                return d;
                            };
                            window._eventosProximosLista = listProx;
                            var rows = listProx.map(function (ev, i) {
                                var hidden = i >= LIMITE_VISIBLE_PROXIMOS;
                                var tr = '<tr class="hover:bg-gray-50 fila-proximo' + (hidden ? ' hidden-fila-proximo' : '') + '" data-index="' + i + '"><td class="px-3 py-2">' + esc(ev.nomPrograma || ev.codPrograma) + '</td><td class="px-3 py-2">' + esc(ev.nomGranja || ev.granja) + '</td><td class="px-3 py-2">' + esc(ev.campania) + '</td><td class="px-3 py-2">' + esc(ev.galpon) + '</td><td class="px-3 py-2">' + fmt(ev.fechaEjecucion) + '</td></tr>';
                                return tr;
                            });
                            if (tProx) tProx.innerHTML = rows.join('');
                            if (btnVerMas) {
                                if (listProx.length > LIMITE_VISIBLE_PROXIMOS) {
                                    btnVerMas.classList.remove('hidden');
                                    btnVerMas.textContent = 'Ver más (' + (listProx.length - LIMITE_VISIBLE_PROXIMOS) + ' más)';
                                } else {
                                    btnVerMas.classList.add('hidden');
                                }
                            }
                        }
                        return { hoy: hoy, proximos: proximos };
                    })
                    .catch(function () {
                        if (document.getElementById('kpiHoyVal')) document.getElementById('kpiHoyVal').textContent = '—';
                        if (document.getElementById('kpiProximosVal')) document.getElementById('kpiProximosVal').textContent = '—';
                        return { hoy: undefined, proximos: undefined };
                    });
            }

            document.addEventListener('DOMContentLoaded', function () {
                var btnVerMas = document.getElementById('btnVerMasProximos');
                var tProx = document.getElementById('tablaEventosProximos');
                var contProx = tProx ? tProx.closest('.eventos-tabla-contenedor') : null;
                if (btnVerMas && contProx) {
                    btnVerMas.addEventListener('click', function () {
                        eventosProximosExpandido = !eventosProximosExpandido;
                        var filas = document.querySelectorAll('#tablaEventosProximos .fila-proximo');
                        var limite = LIMITE_VISIBLE_PROXIMOS;
                        filas.forEach(function (tr) {
                            var idx = parseInt(tr.getAttribute('data-index'), 10);
                            tr.classList.toggle('hidden-fila-proximo', !eventosProximosExpandido && idx >= limite);
                        });
                        contProx.classList.toggle('eventos-expandido', eventosProximosExpandido);
                        var total = window._eventosProximosLista ? window._eventosProximosLista.length : 0;
                        btnVerMas.textContent = eventosProximosExpandido ? 'Ver menos' : 'Ver más' + (total > limite ? ' (' + (total - limite) + ' más)' : '');
                    });
                }
                var planifModal = document.getElementById('planifModalDetalles');
                var planifCerrar = document.getElementById('planifModalDetallesCerrar');
                if (planifModal && planifCerrar) {
                    planifCerrar.addEventListener('click', function () { planifModal.classList.add('hidden'); });
                    planifModal.addEventListener('click', function (e) { if (e.target === planifModal) planifModal.classList.add('hidden'); });
                }
                document.querySelectorAll('#planifModalDetalles .tabs-detalle-planif .tab-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var tab = this.getAttribute('data-tab');
                        document.querySelectorAll('#planifModalDetalles .tab-btn').forEach(function (b) { b.classList.remove('active'); });
                        document.querySelectorAll('#planifModalDetalles .tab-panel-planif').forEach(function (p) { p.classList.remove('active'); });
                        this.classList.add('active');
                        var panel = document.getElementById('planifTabPanel' + (tab === 'fechas' ? 'Fechas' : tab === 'granjas' ? 'Granjas' : 'Programa'));
                        if (panel) panel.classList.add('active');
                    });
                });
                var planifSearch = document.getElementById('planifDetallesSearch');
                var planifPageSize = document.getElementById('planifDetallesPageSize');
                if (planifSearch) planifSearch.addEventListener('input', function () { window._planifDetallesSearch = this.value; renderPlanifDetallesFechasPage(1); });
                if (planifPageSize) planifPageSize.addEventListener('change', function () { window._planifDetallesPageSize = parseInt(this.value, 10) || 20; renderPlanifDetallesFechasPage(1); });
                var planifSearchG = document.getElementById('planifDetallesGranjasSearch');
                var planifPageSizeG = document.getElementById('planifDetallesGranjasPageSize');
                if (planifSearchG) planifSearchG.addEventListener('input', function () { window._planifDetallesGranjasSearch = this.value; renderPlanifDetallesGranjasPage(1); });
                if (planifPageSizeG) planifPageSizeG.addEventListener('change', function () { window._planifDetallesGranjasPageSize = parseInt(this.value, 10) || 20; renderPlanifDetallesGranjasPage(1); });
                document.addEventListener('click', function (e) {
                    var btn = e.target.closest && e.target.closest('.btn-ojito-granjas');
                    if (btn) {
                        e.preventDefault();
                        var num = btn.getAttribute('data-numcronograma');
                        if (num != null) openModalDetallesPlanif(num);
                    }
                });
                document.getElementById('tablaTasaCumplimiento') && document.getElementById('tablaTasaCumplimiento').addEventListener('click', function (e) {
                    var btn = e.target.closest('.btn-toggle-programa');
                    if (!btn) return;
                    var cod = btn.getAttribute('data-codprograma');
                    if (!cod) return;
                    var filas = document.querySelectorAll('#tablaTasaCumplimiento tr.fila-asignacion[data-codprograma="' + cod.replace(/"/g, '&quot;') + '"]');
                    var expanded = btn.getAttribute('aria-expanded') === 'true';
                    filas.forEach(function (tr) { tr.classList.toggle('hidden', expanded); });
                    btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    btn.innerHTML = expanded ? '<i class="fas fa-chevron-right text-gray-500"></i>' : '<i class="fas fa-chevron-down text-gray-500"></i>';
                });
            });

            function fechaDDMMYYYY(s) {
                if (s == null || s === undefined) return '—';
                s = String(s).trim().substring(0, 10);
                if (s.length < 10) return s || '—';
                return s.split('-').reverse().join('/');
            }
            function ordenarDetallesGranjas(detalles) {
                if (!detalles || detalles.length === 0) return [];
                return detalles.slice().sort(function (a, b) {
                    var cmp = (a.granja || '').localeCompare(b.granja || '');
                    if (cmp !== 0) return cmp;
                    cmp = (a.campania || '').localeCompare(b.campania || '');
                    if (cmp !== 0) return cmp;
                    cmp = (a.galpon || '').localeCompare(b.galpon || '');
                    if (cmp !== 0) return cmp;
                    var na = typeof a.edad === 'number' ? a.edad : (parseFloat(a.edad, 10) || 0);
                    var nb = typeof b.edad === 'number' ? b.edad : (parseFloat(b.edad, 10) || 0);
                    return na - nb;
                });
            }
            function granjasUnicosDesdeDetalles(detalles) {
                var seen = {};
                var out = [];
                (detalles || []).forEach(function (r) {
                    var g = (r.granja || '').toString().trim();
                    var gp = (r.galpon || '').toString().trim();
                    var c = (r.campania || '').toString().trim();
                    var key = g + '|' + gp + '|' + c;
                    if (key !== '||' && !seen[key]) {
                        seen[key] = true;
                        out.push({ granja: g, nomGranja: (r.nomGranja || g).toString().trim(), galpon: gp, campania: c });
                    }
                });
                out.sort(function (a, b) {
                    var cmp = (a.granja || '').localeCompare(b.granja || '');
                    if (cmp !== 0) return cmp;
                    cmp = (a.galpon || '').localeCompare(b.galpon || '');
                    if (cmp !== 0) return cmp;
                    return (a.campania || '').localeCompare(b.campania || '');
                });
                return out;
            }
            function renderGranjasSoloOjo(numCronograma) {
                if (numCronograma == null || numCronograma === '') return '—';
                return '<button type="button" class="btn-ojito-granjas inline-flex items-center justify-center" data-numcronograma="' + esc(String(numCronograma)) + '" title="Ver detalles"><i class="fas fa-eye"></i></button>';
            }
            window._tasaCumplimientoFilas = [];
            window._planifDetallesFilas = [];
            window._planifDetallesGranjasUnicos = [];
            window._planifDetallesSearch = '';
            window._planifDetallesGranjasSearch = '';
            window._planifDetallesPage = 1;
            window._planifDetallesGranjasPage = 1;
            window._planifDetallesPageSize = 20;
            window._planifDetallesGranjasPageSize = 20;
            function filterPlanifDetallesPorBusqueda(filas, q) {
                if (!filas || !filas.length) return [];
                var term = (q || '').toString().trim().toLowerCase();
                if (term === '') return filas;
                return filas.filter(function (r) {
                    var txt = [r.granja, r.nomGranja, r.campania, r.galpon, r.edad, fechaDDMMYYYY(r.fechaCarga), fechaDDMMYYYY(r.fechaEjecucion)].join(' ').toLowerCase();
                    return txt.indexOf(term) !== -1;
                });
            }
            function filterPlanifGranjasPorBusqueda(unicos, q) {
                if (!unicos || !unicos.length) return [];
                var term = (q || '').toString().trim().toLowerCase();
                if (term === '') return unicos;
                return unicos.filter(function (r) {
                    var txt = [r.granja, r.nomGranja, r.galpon, r.campania].join(' ').toLowerCase();
                    return txt.indexOf(term) !== -1;
                });
            }
            function renderPlanifDetallesFechasPage(page) {
                var filas = filterPlanifDetallesPorBusqueda(window._planifDetallesFilas || [], window._planifDetallesSearch);
                var pageSize = Math.max(1, parseInt(window._planifDetallesPageSize, 10) || 20);
                var total = filas.length;
                var totalPag = Math.max(1, Math.ceil(total / pageSize));
                page = Math.max(1, Math.min(page, totalPag));
                window._planifDetallesPage = page;
                var start = (page - 1) * pageSize;
                var pageFilas = filas.slice(start, start + pageSize);
                var tbody = document.getElementById('planifDetallesLista');
                if (tbody) {
                    tbody.innerHTML = pageFilas.map(function (x, i) {
                        var num = start + i + 1;
                        return '<tr class="border-b border-gray-200"><td class="px-3 py-2">' + num + '</td><td class="px-3 py-2">' + esc(x.granja) + '</td><td class="px-3 py-2">' + esc(x.nomGranja || x.granja) + '</td><td class="px-3 py-2">' + esc(x.campania) + '</td><td class="px-3 py-2">' + esc(x.galpon) + '</td><td class="px-3 py-2">' + (x.edad !== undefined && x.edad !== null && x.edad !== '' ? esc(x.edad) : '—') + '</td><td class="px-3 py-2">' + esc(fechaDDMMYYYY(x.fechaCarga)) + '</td><td class="px-3 py-2">' + esc(fechaDDMMYYYY(x.fechaEjecucion)) + '</td></tr>';
                    }).join('');
                }
                var pagEl = document.getElementById('planifDetallesToolbarBottom');
                if (pagEl) {
                    var desde = total === 0 ? 0 : start + 1;
                    var hasta = Math.min(start + pageSize, total);
                    pagEl.innerHTML = 'Mostrando ' + desde + ' a ' + hasta + ' de ' + total + ' registros. Pág. ' + page + ' de ' + totalPag;
                }
            }
            function renderPlanifDetallesGranjasPage(page) {
                var unicos = filterPlanifGranjasPorBusqueda(window._planifDetallesGranjasUnicos || [], window._planifDetallesGranjasSearch);
                var pageSize = Math.max(1, parseInt(window._planifDetallesGranjasPageSize, 10) || 20);
                var total = unicos.length;
                var totalPag = Math.max(1, Math.ceil(total / pageSize));
                page = Math.max(1, Math.min(page, totalPag));
                window._planifDetallesGranjasPage = page;
                var start = (page - 1) * pageSize;
                var pageUnicos = unicos.slice(start, start + pageSize);
                var tbody = document.getElementById('planifDetallesListaGranjas');
                if (tbody) {
                    tbody.innerHTML = pageUnicos.map(function (x, i) {
                        var num = start + i + 1;
                        return '<tr class="border-b border-gray-200"><td class="px-3 py-2">' + num + '</td><td class="px-3 py-2">' + esc(x.granja) + '</td><td class="px-3 py-2">' + esc(x.nomGranja || x.granja) + '</td><td class="px-3 py-2">' + esc(x.galpon) + '</td><td class="px-3 py-2">' + esc(x.campania) + '</td></tr>';
                    }).join('');
                }
                var pagEl = document.getElementById('planifDetallesGranjasToolbarBottom');
                if (pagEl) pagEl.innerHTML = 'Mostrando ' + (total === 0 ? 0 : start + 1) + ' a ' + Math.min(start + pageSize, total) + ' de ' + total + ' registros. Pág. ' + page + ' de ' + totalPag;
            }
            function cargarPlanifTabPrograma(codPrograma) {
                var cabEl = document.getElementById('planifDetallesProgramaCab');
                var theadEl = document.getElementById('planifDetallesProgramaThead');
                var tbodyEl = document.getElementById('planifDetallesProgramaBody');
                var sinRegEl = document.getElementById('planifDetallesProgramaSinReg');
                if (!cabEl || !theadEl || !tbodyEl || !sinRegEl) return;
                cabEl.innerHTML = '<span class="text-gray-500">Cargando...</span>';
                theadEl.innerHTML = '';
                tbodyEl.innerHTML = '';
                sinRegEl.classList.add('hidden');
                if (!codPrograma || String(codPrograma).trim() === '') {
                    cabEl.innerHTML = '<span class="text-gray-500">No hay programa asociado.</span>';
                    return;
                }
                fetch('programas/get_programa_cab_detalle.php?codigo=' + encodeURIComponent(codPrograma))
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!res.success) {
                            cabEl.innerHTML = '<span class="text-red-600">' + esc(res.message || 'Error al cargar.') + '</span>';
                            return;
                        }
                        var cab = res.cab || {};
                        var detalles = res.detalles || [];
                        cabEl.innerHTML = '<div class="font-semibold text-gray-800 mb-1">' + esc(cab.codigo) + ' — ' + esc(cab.nombre) + '</div><dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-600"><dt class="font-medium">Tipo</dt><dd>' + esc(cab.nomTipo || '') + '</dd>' + (cab.despliegue ? '<dt class="font-medium">Despliegue</dt><dd>' + esc(cab.despliegue) + '</dd>' : '') + (cab.descripcion ? '<dt class="font-medium col-span-2">Descripción</dt><dd class="col-span-2">' + esc(cab.descripcion) + '</dd>' : '') + (cab.fechaInicio ? '<dt class="font-medium">Fecha inicio</dt><dd>' + fechaDDMMYYYY(String(cab.fechaInicio).substring(0, 10)) + '</dd>' : '') + (cab.fechaFin ? '<dt class="font-medium">Fecha fin</dt><dd>' + fechaDDMMYYYY(String(cab.fechaFin).substring(0, 10)) + '</dd>' : '') + '</dl>';
                        theadEl.innerHTML = '<tr><th class="px-3 py-2 text-left bg-blue-600 text-white">Edad</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Tolerancia</th></tr>';
                        if (detalles.length === 0) {
                            sinRegEl.classList.remove('hidden');
                        } else {
                            tbodyEl.innerHTML = detalles.map(function (d) {
                                return '<tr class="border-b border-gray-200"><td class="px-3 py-2">' + esc(d.edad != null ? d.edad : '—') + '</td><td class="px-3 py-2">' + esc(d.tolerancia != null ? d.tolerancia : '—') + '</td></tr>';
                            }).join('');
                        }
                    })
                    .catch(function () {
                        cabEl.innerHTML = '<span class="text-red-600">Error al cargar el programa.</span>';
                    });
            }
            function openModalDetallesPlanif(numCronograma) {
                if (numCronograma == null || numCronograma === '') return;
                fetch('cronograma/get_cronograma.php?numCronograma=' + encodeURIComponent(String(numCronograma)))
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!res.success || !res.data) return;
                        var d = res.data;
                        var detalles = [];
                        (d.items || []).forEach(function (it) {
                            (it.fechas || []).forEach(function (f) {
                                detalles.push({
                                    codPrograma: d.codPrograma || '',
                                    nomPrograma: d.nomPrograma || '',
                                    granja: it.granja || '',
                                    nomGranja: it.nomGranja || '',
                                    campania: it.campania || (f && f.campania) || '',
                                    galpon: it.galpon || '',
                                    fechaCarga: (f && f.fechaCarga) || '',
                                    fechaEjecucion: (f && f.fechaEjecucion) || '',
                                    edad: (f && f.edad != null) ? f.edad : (it.edad != null ? it.edad : ''),
                                    numCronograma: d.numCronograma,
                                    zona: it.zona || '',
                                    subzona: it.subzona || ''
                                });
                            });
                        });
                        window._planifDetallesFilas = ordenarDetallesGranjas(detalles);
                        window._planifDetallesGranjasUnicos = granjasUnicosDesdeDetalles(detalles);
                        window._planifDetallesSearch = '';
                        window._planifDetallesGranjasSearch = '';
                        document.getElementById('planifDetallesCodPrograma').textContent = (d.codPrograma || '') + ' — ' + (d.nomPrograma || '');
                        document.getElementById('planifDetallesTotal').textContent = detalles.length;
                        var ps = document.getElementById('planifDetallesPageSize');
                        var gs = document.getElementById('planifDetallesGranjasPageSize');
                        if (ps) ps.value = '20';
                        if (gs) gs.value = '20';
                        window._planifDetallesPageSize = 20;
                        window._planifDetallesGranjasPageSize = 20;
                        var searchInp = document.getElementById('planifDetallesSearch');
                        var searchG = document.getElementById('planifDetallesGranjasSearch');
                        if (searchInp) searchInp.value = '';
                        if (searchG) searchG.value = '';
                        document.querySelectorAll('#planifModalDetalles .tab-btn').forEach(function (b) { b.classList.remove('active'); });
                        document.querySelectorAll('#planifModalDetalles .tab-panel-planif').forEach(function (p) { p.classList.remove('active'); });
                        document.querySelector('#planifModalDetalles .tab-btn[data-tab="fechas"]').classList.add('active');
                        document.getElementById('planifTabPanelFechas').classList.add('active');
                        renderPlanifDetallesFechasPage(1);
                        renderPlanifDetallesGranjasPage(1);
                        cargarPlanifTabPrograma(d.codPrograma || '');
                        document.getElementById('planifModalDetalles').classList.remove('hidden');
                    })
                    .catch(function () { });
            }
            function updateChartCumplimientoResumen(cumplido, atrasado, noCumplido, pendiente) {
                var canvas = document.getElementById('chartCumplimientoResumen');
                if (!canvas) return;
                var total = (cumplido || 0) + (atrasado || 0) + (noCumplido || 0) + (pendiente || 0);
                if (chartCumplimientoResumen) { chartCumplimientoResumen.destroy(); chartCumplimientoResumen = null; }
                if (total === 0) return;
                var ctx = canvas.getContext('2d');
                chartCumplimientoResumen = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Si cumplió', 'Atrasado', 'No cumplido', 'Pendiente'],
                        datasets: [{ data: [cumplido || 0, atrasado || 0, noCumplido || 0, pendiente || 0], backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#94a3b8'], borderWidth: 1, borderColor: '#fff' }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: { callbacks: { label: function (ctx) { var pct = total ? (ctx.raw / total * 100).toFixed(1) : 0; return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)'; } } }
                        }
                    }
                });
            }
            function loadTasaCumplimiento() {
                var tbody = document.getElementById('tablaTasaCumplimiento');
                fetch('cronograma/get_tasa_cumplimiento_planificacion.php?year=' + selectedYear)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.success || !data.items || data.items.length === 0) {
                            window._tasaCumplimientoFilas = [];
                            tbody.innerHTML = '<tr><td colspan="10" class="px-3 py-4 text-center text-gray-500">Sin datos de cumplimiento para este año.</td></tr>';
                            if (chartCumplimientoResumen) { chartCumplimientoResumen.destroy(); chartCumplimientoResumen = null; }
                            return;
                        }
                        var items = data.items;
                        window._tasaCumplimientoFilas = items;
                        var porPrograma = {};
                        items.forEach(function (row) {
                            var cod = row.codPrograma || '';
                            var nom = row.nomPrograma || row.codPrograma || '';
                            var sigla = (row.sigla || '').toString().trim();
                            if (!porPrograma[cod]) {
                                porPrograma[cod] = { codPrograma: cod, nomPrograma: nom, sigla: sigla, total: 0, cumplido: 0, atrasado: 0, noCumplido: 0, pendiente: 0, asignacionesList: [], granjasSet: {} };
                            }
                            var p = porPrograma[cod];
                            if (sigla) p.sigla = p.sigla || sigla;
                            p.total += row.total || 0;
                            p.cumplido += row.cumplido != null ? row.cumplido : 0;
                            p.atrasado += row.atrasado != null ? row.atrasado : 0;
                            p.noCumplido += row.noCumplido != null ? row.noCumplido : 0;
                            p.pendiente += row.pendiente != null ? row.pendiente : 0;
                            p.asignacionesList.push(row);
                            (row.granjas || []).forEach(function (g) { p.granjasSet[g] = true; });
                        });
                        var filasProg = Object.keys(porPrograma).sort().map(function (cod) {
                            var p = porPrograma[cod];
                            var total = p.total;
                            var tasa = total > 0 ? (Math.round((p.cumplido + p.atrasado) / total * 1000) / 10) : null;
                            var nomMostrar = (p.sigla ? p.sigla + ' - ' : '') + (p.nomPrograma || p.codPrograma || '');
                            return {
                                nomPrograma: p.nomPrograma,
                                codPrograma: p.codPrograma,
                                sigla: p.sigla,
                                nomMostrar: nomMostrar,
                                asignaciones: p.asignacionesList.length,
                                total: total,
                                cumplido: p.cumplido,
                                atrasado: p.atrasado,
                                noCumplido: p.noCumplido,
                                pendiente: p.pendiente,
                                tasa: tasa,
                                asignacionesList: p.asignacionesList
                            };
                        });
                        filasProg.sort(function (a, b) { return (b.tasa != null ? b.tasa : 0) - (a.tasa != null ? a.tasa : 0); });
                        var sumCumplido = 0, sumAtrasado = 0, sumNoCumplido = 0, sumPendiente = 0;
                        items.forEach(function (row) {
                            sumCumplido += row.cumplido != null ? row.cumplido : 0;
                            sumAtrasado += row.atrasado != null ? row.atrasado : 0;
                            sumNoCumplido += row.noCumplido != null ? row.noCumplido : 0;
                            sumPendiente += row.pendiente != null ? row.pendiente : 0;
                        });
                        updateChartCumplimientoResumen(sumCumplido, sumAtrasado, sumNoCumplido, sumPendiente);
                        var html = [];
                        filasProg.forEach(function (prog, idx) {
                            var tasa = (prog.tasa != null ? prog.tasa : '—');
                            html.push('<tr class="hover:bg-gray-50 fila-programa" data-codprograma="' + esc(prog.codPrograma) + '"><td class="px-2 py-2 text-center"><button type="button" class="btn-toggle-programa p-1" data-codprograma="' + esc(prog.codPrograma) + '" aria-expanded="false"><i class="fas fa-chevron-right text-gray-500"></i></button></td><td class="px-3 py-2 text-center">' + (idx + 1) + '</td><td class="px-3 py-2 font-medium">' + esc(prog.nomMostrar || prog.nomPrograma || prog.codPrograma) + '</td><td class="px-3 py-2 text-center">' + prog.asignaciones + '</td><td class="px-3 py-2 text-center">' + prog.total + '</td><td class="px-3 py-2 text-center">' + (prog.cumplido != null ? prog.cumplido : '—') + '</td><td class="px-3 py-2 text-center">' + (prog.atrasado != null ? prog.atrasado : '—') + '</td><td class="px-3 py-2 text-center">' + (prog.noCumplido != null ? prog.noCumplido : '—') + '</td><td class="px-3 py-2 text-center">' + (prog.pendiente != null ? prog.pendiente : '—') + '</td><td class="px-3 py-2 text-center">' + (tasa !== '—' ? tasa : '—') + '</td></tr>');
                            (prog.asignacionesList || []).forEach(function (row) {
                                var total = row.total || 0;
                                var tasaAsig = (row.tasa != null ? row.tasa : (total > 0 ? ((row.cumplido + row.atrasado) / total * 100).toFixed(1) : '—'));
                                var granjasHtml = renderGranjasSoloOjo(row.numCronograma);
                                html.push('<tr class="fila-asignacion hidden bg-gray-50/50" data-codprograma="' + esc(prog.codPrograma) + '"><td class="px-2 py-2"></td><td class="px-3 py-2 text-center"></td><td class="px-3 py-2 pl-8 text-gray-600">—</td><td class="px-3 py-2 text-center">' + granjasHtml + '</td><td class="px-3 py-2 text-center">' + total + '</td><td class="px-3 py-2 text-center">' + (row.cumplido != null ? row.cumplido : '—') + '</td><td class="px-3 py-2 text-center">' + (row.atrasado != null ? row.atrasado : '—') + '</td><td class="px-3 py-2 text-center">' + (row.noCumplido != null ? row.noCumplido : '—') + '</td><td class="px-3 py-2 text-center">' + (row.pendiente != null ? row.pendiente : '—') + '</td><td class="px-3 py-2 text-center">' + (tasaAsig !== null && tasaAsig !== undefined ? tasaAsig : '—') + '</td></tr>');
                            });
                        });
                        tbody.innerHTML = html.join('');
                    })
                    .catch(function () {
                        tbody.innerHTML = '<tr><td colspan="10" class="px-3 py-4 text-center text-gray-500">Error al cargar datos.</td></tr>';
                    });
            }

            function loadData() {
                loadEventos().then(function (r) {
                    loadKPIs(r && r.hoy !== undefined ? r.hoy : undefined, r && r.proximos !== undefined ? r.proximos : undefined);
                });
                loadTasaCumplimiento();
            }

            if (yearSelect) {
                populateYearSelect();
                loadData();
                yearSelect.addEventListener('change', function () {
                    selectedYear = parseInt(yearSelect.value, 10);
                    loadData();
                });
            }
        })();
    </script>
</body>

</html>
