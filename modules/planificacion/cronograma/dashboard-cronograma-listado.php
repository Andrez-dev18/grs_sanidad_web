<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; } else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) die("Error de conexión: " . mysqli_connect_error());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronograma - Listado</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f8f9fa; font-family: system-ui, sans-serif; }
        .table-wrapper { overflow-x: auto; width: 100%; border-radius: 1rem; }
        .data-table { width: 100% !important; border-collapse: collapse; min-width: 600px; }
        .data-table th, .data-table td { padding: 0.75rem 1rem; text-align: left; font-size: 0.875rem; border-bottom: 1px solid #e5e7eb; }
        .data-table th {
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
            font-weight: 600; color: #ffffff !important; position: sticky; top: 0; z-index: 10;
        }
        .data-table tbody tr:hover { background-color: #eff6ff !important; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .modal-overlay.hidden { display: none; }
        .modal-box { background: white; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); width: 100%; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .modal-body { padding: 1.25rem; }
        .btn-row { padding: 0.35rem 0.6rem; border-radius: 0.5rem; border: 1px solid #93c5fd; color: #2563eb; background: #eff6ff; font-size: 0.8rem; cursor: pointer; margin-right: 0.35rem; }
        .btn-row:hover { background: #dbeafe; }
        .btn-cal { padding: 0.35rem 0.6rem; border-radius: 0.5rem; border: 1px solid #86efac; color: #15803d; background: #dcfce7; font-size: 0.8rem; cursor: pointer; }
        .btn-cal:hover { background: #bbf7d0; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; font-size: 0.8rem; }
        .cal-dia-header { padding: 0.4rem; text-align: center; font-weight: 600; color: #64748b; background: #f1f5f9; border-radius: 4px; }
        .cal-dia { min-height: 70px; padding: 4px; border: 1px solid #e2e8f0; border-radius: 4px; background: #fafafa; }
        .cal-dia.otro-mes { background: #f1f5f9; color: #94a3b8; }
        .cal-dia-num { font-weight: 600; color: #475569; margin-bottom: 4px; }
        .cal-evento { padding: 2px 6px; border-radius: 4px; margin-bottom: 2px; font-size: 0.7rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; display: flex; align-items: center; gap: 4px; }
        .cal-evento:hover { opacity: 0.9; }
        .cal-evento-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .cal-evento-texto { overflow: hidden; text-overflow: ellipsis; }
        .leyenda { display: flex; flex-wrap: wrap; gap: 0.75rem 1rem; margin-top: 0.75rem; font-size: 0.8rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; align-items: center; }
        .leyenda-titulo { width: 100%; font-weight: 600; color: #374151; margin-bottom: 0.25rem; }
        .leyenda-item { display: flex; align-items: center; gap: 0.35rem; }
        .leyenda-color { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
        .btn-ver-mas { margin-top: 0.75rem; padding: 0.4rem 0.75rem; border-radius: 0.5rem; border: 1px solid #93c5fd; color: #2563eb; background: #eff6ff; font-size: 0.8rem; cursor: pointer; }
        .btn-ver-mas:hover { background: #dbeafe; }
        .programa-cab-detalle { margin-top: 0.75rem; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #f8fafc; font-size: 0.8rem; }
        .programa-cab-detalle .cab-tit { font-weight: 600; color: #1e40af; margin-bottom: 0.5rem; }
        .programa-cab-detalle table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; font-size: 0.75rem; }
        .programa-cab-detalle th, .programa-cab-detalle td { padding: 4px 6px; border: 1px solid #e2e8f0; text-align: left; }
        .programa-cab-detalle th { background: #e0e7ff; color: #3730a3; }
        .modal-calendario-alto .modal-box { min-height: 75vh; max-height: 90vh; }
        .modal-detalle-evento .modal-box {
            border-radius: 1rem; overflow: hidden;
            max-width: 720px; width: 95%;
            max-height: 90vh;
            display: flex; flex-direction: column;
        }
        .modal-detalle-evento .modal-header { flex-shrink: 0; }
        .modal-detalle-evento .modal-body {
            flex: 1; min-height: 0;
            overflow-y: auto; overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            word-wrap: break-word; overflow-wrap: break-word;
        }
        .modal-detalle-evento .programa-cab-detalle { max-width: 100%; overflow-x: auto; }
        .modal-detalle-evento .programa-cab-detalle table { table-layout: auto; min-width: 100%; }
        .cab-dos-columnas { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1.5rem; font-size: 0.8rem; }
        .cab-dos-columnas dt { color: #64748b; font-weight: 500; }
        .cab-dos-columnas dd { margin: 0; color: #1e293b; }
        .detalle-evento-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1.5rem; font-size: 0.875rem; }
        .detalle-evento-grid dt { color: #64748b; font-weight: 500; }
        .detalle-evento-grid dd { margin: 0; color: #1e293b; }
        .tabs-detalle { display: flex; gap: 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 1rem; }
        .tabs-detalle .tab-btn { padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; color: #64748b; background: transparent; border: none; border-bottom: 2px solid transparent; cursor: pointer; margin-bottom: -1px; }
        .tabs-detalle .tab-btn:hover { color: #2563eb; }
        .tabs-detalle .tab-btn.active { color: #2563eb; border-bottom-color: #2563eb; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border">
        <!-- Filtros (estilo reportes) -->
        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <button type="button" id="btnToggleFiltrosCronograma" class="w-full px-6 py-4 flex items-center justify-between text-left font-medium text-gray-800 hover:bg-gray-50 transition">
                <span><i class="fas fa-filter mr-2 text-blue-600"></i> Filtros</span>
                <svg id="iconoFiltrosCronograma" class="w-5 h-5 text-gray-600 transition-transform duration-300 rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="contenidoFiltrosCronograma" class="px-6 pb-6 pt-4 border-t border-gray-100">
                <div class="filter-row-periodo flex flex-wrap items-end gap-4 mb-6">
                    <div class="flex-shrink-0" style="min-width: 200px;">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1 text-blue-600"></i> Periodo</label>
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
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-day mr-1 text-blue-600"></i> Fecha</label>
                        <input id="fechaUnica" type="date" value="<?php echo date('Y-m-d'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div id="periodoEntreFechas" class="hidden flex-shrink-0 flex items-end gap-2">
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i> Desde</label>
                            <input id="fechaInicio" type="date" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i> Hasta</label>
                            <input id="fechaFin" type="date" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <div id="periodoPorMes" class="hidden flex-shrink-0 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar mr-1 text-blue-600"></i> Mes</label>
                        <input id="mesUnico" type="month" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div id="periodoEntreMeses" class="hidden flex-shrink-0 flex items-end gap-2">
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i> Mes Inicio</label>
                            <input id="mesInicio" type="month" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i> Mes Fin</label>
                            <input id="mesFin" type="month" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <div class="flex-shrink-0" style="min-width: 200px;">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-tags mr-1 text-blue-600"></i> Tipo de programa</label>
                        <select id="filtroCodTipo" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer text-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="btnFiltrarCronograma" class="px-5 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium">Filtrar</button>
                    <button type="button" id="btnLimpiarFiltrosCronograma" class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 text-sm">Limpiar</button>
                    <button type="button" id="btnReportePdfFiltrado" class="px-5 py-2 rounded-lg text-white text-sm font-medium inline-flex items-center gap-2" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);">
                        <i class="fas fa-file-pdf"></i> Reporte PDF
                    </button>
                    <button type="button" id="btnCalendarioFiltrado" class="px-5 py-2 rounded-lg text-white text-sm font-medium inline-flex items-center gap-2" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);" title="Ver todo lo filtrado en calendario">
                        <i class="fas fa-calendar-alt"></i> Calendario
                    </button>
                </div>
            </div>
        </div>
        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="table-wrapper p-4">
                <table id="tablaCronograma" class="data-table w-full text-sm config-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">N°</th>
                            <th class="px-4 py-3 text-left">Cód. Programa</th>
                            <th class="px-4 py-3 text-left">Nom. Programa</th>
                            <th class="px-4 py-3 text-left">Fecha Prog.</th>
                            <th class="px-4 py-3 text-left">Detalles</th>
                            <th class="px-4 py-3 text-left">Opciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detalles: dos tabs (Granjas / Programa) -->
    <div id="modalDetalles" class="modal-overlay hidden">
        <div class="modal-box rounded-2xl overflow-hidden" style="max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-header flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-800">Detalles de la asignación</h3>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none" data-modal="modalDetalles">&times;</button>
            </div>
            <div class="modal-body overflow-hidden flex-1 flex flex-col min-h-0 p-4">
                <p class="text-xs font-medium text-gray-500 mb-2">Programa: <strong id="detallesCodPrograma"></strong> — <span id="detallesTotal">0</span> registro(s)</p>
                <div class="tabs-detalle">
                    <button type="button" class="tab-btn active" data-tab="granjas">Granjas</button>
                    <button type="button" class="tab-btn" data-tab="programa">Programa</button>
                </div>
                <div id="tabPanelGranjas" class="tab-panel active overflow-x-auto overflow-y-auto flex-1">
                    <table class="data-table w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left bg-blue-600 text-white">N°</th>
                                <th class="px-3 py-2 text-left bg-blue-600 text-white">Cód. Programa</th>
                                <th class="px-3 py-2 text-left bg-blue-600 text-white">Granja</th>
                                <th class="px-3 py-2 text-left bg-blue-600 text-white">Nom. Granja</th>
                                <th class="px-3 py-2 text-left bg-blue-600 text-white">Campaña</th>
                                <th class="px-3 py-2 text-left bg-blue-600 text-white">Galpón</th>
                                <th class="px-3 py-2 text-left bg-blue-600 text-white">Edad</th>
                                <th class="px-3 py-2 text-left bg-blue-600 text-white">Fec. Carga</th>
                                <th class="px-3 py-2 text-left bg-blue-600 text-white">Fec. Ejecución</th>
                            </tr>
                        </thead>
                        <tbody id="detallesLista"></tbody>
                    </table>
                </div>
                <div id="tabPanelPrograma" class="tab-panel overflow-x-auto overflow-y-auto flex-1">
                    <div id="detallesProgramaCab" class="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"></div>
                    <div class="table-wrapper overflow-x-auto">
                        <table class="data-table w-full text-sm border border-gray-200 rounded-lg overflow-hidden" id="tablaDetallesPrograma">
                            <thead class="bg-gray-50 border-b border-gray-200" id="detallesProgramaThead"></thead>
                            <tbody id="detallesProgramaBody"></tbody>
                        </table>
                    </div>
                    <p id="detallesProgramaSinReg" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle del programa.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Calendario -->
    <div id="modalCalendario" class="modal-overlay hidden modal-calendario-alto">
        <div class="modal-box" style="max-width: 820px; min-height: 75vh; max-height: 90vh;">
            <div class="modal-header flex-wrap gap-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" id="calPrevMes" class="px-2 py-1 rounded border border-gray-300 hover:bg-gray-100 text-sm">&laquo;</button>
                    <span id="calMesAnio" class="font-semibold text-gray-800"></span>
                    <button type="button" id="calNextMes" class="px-2 py-1 rounded border border-gray-300 hover:bg-gray-100 text-sm">&raquo;</button>
                    <span id="calFiltroPrograma" class="text-xs text-green-700 bg-green-50 px-2 py-1 rounded hidden"></span>
                </div>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none" data-modal="modalCalendario">&times;</button>
            </div>
            <div class="modal-body">
                <div id="calGrid" class="cal-grid"></div>
                <div id="calLeyenda" class="leyenda"></div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle evento (al hacer clic en un evento del calendario) -->
    <div id="modalDetalleEvento" class="modal-overlay hidden modal-detalle-evento">
        <div class="modal-box rounded-2xl overflow-hidden">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-800">Detalle del evento</h3>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none" data-modal="modalDetalleEvento">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="detalle-evento-grid text-sm">
                    <div><dt>Granja</dt><dd id="detEvGranja" class="text-gray-800"></dd></div>
                    <div><dt>Nombre granja</dt><dd id="detEvNomGranja" class="text-gray-800"></dd></div>
                    <div><dt>Campaña</dt><dd id="detEvCampania" class="text-gray-800"></dd></div>
                    <div><dt>Galpón</dt><dd id="detEvGalpon" class="text-gray-800"></dd></div>
                    <div><dt>Edad</dt><dd id="detEvEdad" class="text-gray-800"></dd></div>
                    <div><dt>Fec. Carga</dt><dd id="detEvFecCarga" class="text-gray-800"></dd></div>
                    <div><dt>Fec. Ejecución</dt><dd id="detEvFecEjec" class="text-gray-800"></dd></div>
                </dl>
                <div id="detEvVerMasWrap" class="mt-4 pt-3 border-t border-gray-200">
                    <div class="mb-2"><dt class="text-gray-500 font-medium text-sm">Programa</dt><dd id="detEvPrograma" class="text-gray-800 text-sm"></dd></div>
                    <button type="button" id="btnDetEvVerMas" class="btn-ver-mas"><i class="fas fa-chevron-down mr-1"></i> Ver más </button>
                    <div id="detEvProgramaCabDet" class="programa-cab-detalle hidden"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var listadoData = [];
        var mesActualCal = 0, anioActualCal = 0;
        var codProgramaFiltroCal = null;
        var coloresGranja = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1'];

        function esc(s) { return (s || '').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }
        function colorGranja(granja) {
            if (!granja) return '#94a3b8';
            var n = 0;
            for (var i = 0; i < granja.length; i++) n += granja.toString().charCodeAt(i);
            return coloresGranja[n % coloresGranja.length];
        }
        function fechaDDMMYYYY(s) {
            if (!s) return '';
            s = (s || '').toString().trim();
            var m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (m) return m[3] + '/' + m[2] + '/' + m[1];
            var m2 = s.match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})/);
            if (m2) return m2[3] + '/' + m2[2] + '/' + m2[1] + ' ' + m2[4] + ':' + m2[5];
            return s;
        }
        var columnasPorSiglaReporte = { 'NC': ['num','ubicacion','edad'], 'PL': ['num','ubicacion','producto','proveedor','unidad','dosis','descripcion_vacuna','numeroFrascos','edad'], 'GR': ['num','ubicacion','producto','proveedor','unidad','dosis','descripcion_vacuna','numeroFrascos','edad'], 'MC': ['num','ubicacion','producto','proveedor','dosis','area_galpon','cantidad_por_galpon','unidadDosis','edad'], 'LD': ['num','ubicacion','producto','proveedor','dosis','unidadDosis','edad'], 'CP': ['num','ubicacion','producto','proveedor','dosis','unidadDosis','edad'] };
        var columnasDetalleCompletas = ['posDetalle','ubicacion','producto','proveedor','unidad','dosis','unidadDosis','numeroFrascos','edad','descripcion_vacuna','area_galpon','cantidad_por_galpon'];
        var labelsReportePrograma = { num: '#', posDetalle: 'N° Det', ubicacion: 'Ubicación', producto: 'Producto', proveedor: 'Proveedor', unidad: 'Unidad', dosis: 'Dosis', descripcion_vacuna: 'Descripción vacuna', numeroFrascos: 'Nº frascos', edad: 'Edad', unidadDosis: 'Unid. dosis', area_galpon: 'Área galpón', cantidad_por_galpon: 'Cant. por galpón' };
        function formatearDescripcionVacunaDet(s) {
            if (s === null || s === undefined) s = ''; s = String(s).trim();
            if (!s) return '';
            if (/^Contra[\r\n]/.test(s) || (s.indexOf('\n') !== -1 && s.indexOf('- ') !== -1)) return s;
            var partes = s.split(',').map(function(x) { return x.trim(); }).filter(Boolean);
            return partes.length ? 'Contra\n' + partes.map(function(p) { return '- ' + p; }).join('\n') : '';
        }
        function valorCeldaDetallePrograma(k, d) {
            if (k === 'num') return '';
            if (k === 'posDetalle') return (d.posDetalle !== null && d.posDetalle !== undefined && d.posDetalle !== '' ? esc(d.posDetalle) : '');
            if (k === 'ubicacion') return esc(d.ubicacion || '');
            if (k === 'producto') return esc(d.nomProducto || d.codProducto || '');
            if (k === 'proveedor') return esc(d.nomProveedor || '');
            if (k === 'unidad') return esc(d.unidades || '');
            if (k === 'dosis') return esc(d.dosis || '');
            if (k === 'descripcion_vacuna') return esc(formatearDescripcionVacunaDet(d.descripcionVacuna));
            if (k === 'numeroFrascos') return esc(d.numeroFrascos || '');
            if (k === 'edad') return (d.edad !== null && d.edad !== undefined && d.edad !== '' ? d.edad : '');
            if (k === 'unidadDosis') return esc(d.unidadDosis || '');
            if (k === 'area_galpon') return (d.areaGalpon !== null && d.areaGalpon !== undefined && d.areaGalpon !== '' ? d.areaGalpon : '');
            if (k === 'cantidad_por_galpon') return (d.cantidadPorGalpon !== null && d.cantidadPorGalpon !== undefined && d.cantidadPorGalpon !== '' ? d.cantidadPorGalpon : '');
            return '';
        }
        function cargarTabProgramaEnDetalles(codPrograma) {
            var cabEl = document.getElementById('detallesProgramaCab');
            var theadEl = document.getElementById('detallesProgramaThead');
            var tbodyEl = document.getElementById('detallesProgramaBody');
            var sinRegEl = document.getElementById('detallesProgramaSinReg');
            if (!cabEl || !theadEl || !tbodyEl || !sinRegEl) return;
            cabEl.innerHTML = '<span class="text-gray-500">Cargando...</span>';
            theadEl.innerHTML = '';
            tbodyEl.innerHTML = '';
            sinRegEl.classList.add('hidden');
            if (!codPrograma || String(codPrograma).trim() === '') {
                cabEl.innerHTML = '<span class="text-gray-500">No hay programa asociado a este cronograma.</span>';
                return;
            }
            fetch('../programas/get_programa_cab_detalle.php?codigo=' + encodeURIComponent(codPrograma)).then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) {
                    cabEl.innerHTML = '<span class="text-red-600">' + esc(res.message || 'Error al cargar programa.') + '</span>';
                    return;
                }
                var cab = res.cab || {};
                var detalles = res.detalles || [];
                var sigla = (res.sigla || 'PL').toUpperCase();
                if (sigla === 'NEC') sigla = 'NC';
                var cabHtml = '<div class="font-semibold text-gray-800 mb-1">' + esc(cab.codigo) + ' — ' + esc(cab.nombre) + '</div>';
                cabHtml += '<dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-600">';
                cabHtml += '<dt class="font-medium">Tipo</dt><dd>' + esc(cab.nomTipo || '') + '</dd>';
                if (cab.despliegue) { cabHtml += '<dt class="font-medium">Despliegue</dt><dd>' + esc(cab.despliegue) + '</dd>'; }
                if (cab.descripcion) { cabHtml += '<dt class="font-medium col-span-2">Descripción</dt><dd class="col-span-2">' + esc(cab.descripcion) + '</dd>'; }
                cabHtml += '</dl>';
                cabEl.innerHTML = cabHtml;
                // Columnas dinámicas por sigla (igual que en programas listado); Edad siempre al final
                var cols = columnasPorSiglaReporte[sigla] || columnasPorSiglaReporte['PL'];
                var colsSinNum = cols.filter(function(k) { return k !== 'num'; });
                if (colsSinNum.indexOf('edad') !== -1) {
                    colsSinNum = colsSinNum.filter(function(k) { return k !== 'edad'; });
                    colsSinNum.push('edad');
                }
                var thCells = '<th class="px-3 py-2 text-left bg-blue-600 text-white">Código</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Nombre programa</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Despliegue</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Descripción</th>';
                colsSinNum.forEach(function(k) { thCells += '<th class="px-3 py-2 text-left bg-blue-600 text-white">' + (labelsReportePrograma[k] || k) + '</th>'; });
                theadEl.innerHTML = '<tr>' + thCells + '</tr>';
                tbodyEl.innerHTML = '';
                if (detalles.length === 0) {
                    sinRegEl.classList.remove('hidden');
                } else {
                    detalles.forEach(function(d) {
                        var tr = document.createElement('tr');
                        tr.className = 'border-b border-gray-200';
                        var td = '<td class="px-3 py-2">' + esc(cab.codigo) + '</td><td class="px-3 py-2">' + esc(cab.nombre) + '</td><td class="px-3 py-2">' + esc(cab.despliegue || '') + '</td><td class="px-3 py-2">' + esc(cab.descripcion || '') + '</td>';
                        colsSinNum.forEach(function(k) {
                            td += '<td class="px-3 py-2"' + (k === 'descripcion_vacuna' ? ' style="white-space:pre-wrap;"' : '') + '>' + valorCeldaDetallePrograma(k, d) + '</td>';
                        });
                        tr.innerHTML = td;
                        tbodyEl.appendChild(tr);
                    });
                }
            }).catch(function() {
                cabEl.innerHTML = '<span class="text-red-600">Error al cargar el programa.</span>';
            });
        }
        document.getElementById('modalDetalles').addEventListener('click', function(e) {
            var tabBtn = e.target.closest('.tab-btn[data-tab]');
            if (tabBtn) {
                e.preventDefault();
                var tab = tabBtn.getAttribute('data-tab');
                document.querySelectorAll('#modalDetalles .tab-btn').forEach(function(b) { b.classList.remove('active'); });
                document.querySelectorAll('#modalDetalles .tab-panel').forEach(function(p) { p.classList.remove('active'); });
                tabBtn.classList.add('active');
                if (tab === 'granjas') document.getElementById('tabPanelGranjas').classList.add('active');
                else if (tab === 'programa') document.getElementById('tabPanelPrograma').classList.add('active');
            }
        });

        function paramsFiltro() {
            var p = [];
            var t = document.getElementById('periodoTipo').value || 'TODOS';
            p.push('periodoTipo=' + encodeURIComponent(t));
            if (t === 'POR_FECHA') p.push('fechaUnica=' + encodeURIComponent((document.getElementById('fechaUnica').value || '')));
            if (t === 'ENTRE_FECHAS') {
                p.push('fechaInicio=' + encodeURIComponent((document.getElementById('fechaInicio').value || '')));
                p.push('fechaFin=' + encodeURIComponent((document.getElementById('fechaFin').value || '')));
            }
            if (t === 'POR_MES') p.push('mesUnico=' + encodeURIComponent((document.getElementById('mesUnico').value || '')));
            if (t === 'ENTRE_MESES') {
                p.push('mesInicio=' + encodeURIComponent((document.getElementById('mesInicio').value || '')));
                p.push('mesFin=' + encodeURIComponent((document.getElementById('mesFin').value || '')));
            }
            var codTipo = document.getElementById('filtroCodTipo').value || '';
            if (codTipo) p.push('codTipo=' + encodeURIComponent(codTipo));
            return p.join('&');
        }

        function cargarListado() {
            var qs = paramsFiltro();
            var url = 'listar_cronograma.php' + (qs ? '?' + qs : '');
            fetch(url).then(r => r.json()).then(res => {
                if (!res.success) return;
                listadoData = res.data || [];
                // Agrupar por numCronograma para mostrar la tabla (una fila por cronograma)
                var map = {};
                listadoData.forEach(function(r) {
                    var numC = (r.numCronograma !== undefined && r.numCronograma !== null && r.numCronograma !== 0) ? Number(r.numCronograma) : 0;
                    if (!numC) return;
                    var key = 'n' + numC;
                    if (!map[key]) {
                        map[key] = { numCronograma: numC, codPrograma: r.codPrograma || '', nomPrograma: r.nomPrograma || '', fechaProg: r.fechaHoraRegistro || '', detalles: [] };
                    }
                    map[key].detalles.push(r);
                });
                var grupos = Object.keys(map).map(function(k) { return map[k]; }).sort(function(a, b) {
                    return (b.numCronograma || 0) - (a.numCronograma || 0);
                });
                if ($.fn.DataTable.isDataTable('#tablaCronograma')) $('#tablaCronograma').DataTable().destroy();
                var tbody = document.querySelector('#tablaCronograma tbody');
                tbody.innerHTML = '';
                grupos.forEach(function(g, idx) {
                    var tr = document.createElement('tr');
                    tr.className = 'border-b border-gray-200';
                    var urlPdf = 'generar_reporte_cronograma_pdf.php?numCronograma=' + encodeURIComponent(g.numCronograma);
                    var dataKey = 'data-numcronograma="' + g.numCronograma + '"';
                    tr.innerHTML = '<td class="px-4 py-3">' + (idx + 1) + '</td>' +
                        '<td class="px-4 py-3">' + esc(g.codPrograma) + '</td>' +
                        '<td class="px-4 py-3">' + esc(g.nomPrograma) + '</td>' +
                        '<td class="px-4 py-3">' + esc(fechaDDMMYYYY(g.fechaProg)) + '</td>' +
                        '<td class="px-4 py-3"><button type="button" class="btn-row btn-detalles" ' + dataKey + '><i class="fas fa-list mr-1"></i>Ver</button></td>' +
                        '<td class="px-4 py-3"><a href="' + urlPdf + '" target="_blank" class="btn-row inline-flex items-center mr-1" title="Reporte PDF"><i class="fas fa-file-pdf text-red-600"></i></a><button type="button" class="btn-row btn-cal-fila inline-flex items-center" ' + dataKey + ' title="Ver en calendario"><i class="fas fa-calendar-alt text-green-600"></i></button></td>';
                    tbody.appendChild(tr);
                });
                window.gruposCronograma = grupos;
                $('#tablaCronograma').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }, order: [[1, 'asc']] });
            }).catch(function() {});
        }

        document.querySelectorAll('.modal-cerrar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-modal');
                if (id) document.getElementById(id).classList.add('hidden');
            });
        });
        document.getElementById('modalDetalles').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });

        document.getElementById('tablaCronograma').addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-detalles');
            if (!btn) return;
            e.preventDefault();
            var numCrono = btn.getAttribute('data-numcronograma');
            var grupos = window.gruposCronograma;
            if (!grupos || numCrono === null || numCrono === '') return;
            var g = grupos.find(function(x) { return Number(x.numCronograma) === Number(numCrono); });
            if (!g) return;
            var detalles = g.detalles || [];
            document.getElementById('detallesCodPrograma').textContent = (g.codPrograma || '') + ' — ' + (g.nomPrograma || '');
            document.getElementById('detallesTotal').textContent = detalles.length;
            var tbody = document.getElementById('detallesLista');
            tbody.innerHTML = '';
            detalles.forEach(function(x, i) {
                var tr = document.createElement('tr');
                tr.className = 'border-b border-gray-200';
                tr.innerHTML = '<td class="px-3 py-2">' + (i + 1) + '</td>' +
                    '<td class="px-3 py-2">' + esc(x.codPrograma) + '</td>' +
                    '<td class="px-3 py-2">' + esc(x.granja) + '</td>' +
                    '<td class="px-3 py-2">' + esc(x.nomGranja || x.granja) + '</td>' +
                    '<td class="px-3 py-2">' + esc(x.campania) + '</td>' +
                    '<td class="px-3 py-2">' + esc(x.galpon) + '</td>' +
                    '<td class="px-3 py-2">' + (x.edad !== undefined && x.edad !== null && x.edad !== '' ? esc(x.edad) : '—') + '</td>' +
                    '<td class="px-3 py-2">' + esc(fechaDDMMYYYY(x.fechaCarga)) + '</td>' +
                    '<td class="px-3 py-2">' + esc(fechaDDMMYYYY(x.fechaEjecucion)) + '</td>';
                tbody.appendChild(tr);
            });
            document.querySelectorAll('#modalDetalles .tab-btn').forEach(function(b) { b.classList.remove('active'); });
            document.getElementById('tabPanelGranjas').classList.add('active');
            document.getElementById('tabPanelPrograma').classList.remove('active');
            var firstTab = document.querySelector('#modalDetalles .tab-btn[data-tab="granjas"]');
            if (firstTab) firstTab.classList.add('active');
            cargarTabProgramaEnDetalles(g.codPrograma);
            document.getElementById('modalDetalles').classList.remove('hidden');
        });

        function eventosPorDiaDesdeListado() {
            var map = {};
            var filtro = codProgramaFiltroCal;
            listadoData.forEach(function(r) {
                if (filtro) {
                    if (String(filtro).indexOf('num_') === 0) { var n = parseInt(String(filtro).replace('num_', ''), 10); if (Number(r.numCronograma) !== n) return; }
                    else if ((r.codPrograma || '') !== filtro) return;
                }
                var fec = (r.fechaEjecucion || '').toString().trim();
                var key = fec.substring(0, 10);
                if (!key || key.length < 10) return;
                if (!map[key]) map[key] = [];
                map[key].push({
                    numCronograma: r.numCronograma !== undefined && r.numCronograma !== null ? Number(r.numCronograma) : 0,
                    granja: r.granja || '',
                    nomGranja: r.nomGranja || r.granja || '',
                    campania: r.campania || '',
                    galpon: r.galpon || '',
                    edad: r.edad !== undefined && r.edad !== null && r.edad !== '' ? r.edad : '—',
                    codPrograma: r.codPrograma || '',
                    nomPrograma: r.nomPrograma || '',
                    fechaCarga: r.fechaCarga || '',
                    fechaEjecucion: r.fechaEjecucion || '',
                    posDetalle: r.posDetalle !== undefined && r.posDetalle !== null ? r.posDetalle : ''
                });
            });
            return map;
        }

        function granjasUnicasParaLeyenda() {
            var seen = {};
            var out = [];
            var filtro = codProgramaFiltroCal;
            listadoData.forEach(function(r) {
                if (filtro) {
                    if (String(filtro).indexOf('num_') === 0) { var n = parseInt(String(filtro).replace('num_', ''), 10); if (Number(r.numCronograma) !== n) return; }
                    else if ((r.codPrograma || '') !== filtro) return;
                }
                var g = (r.granja || '').toString().trim();
                if (g && !seen[g]) {
                    seen[g] = true;
                    out.push({ granja: g, nomGranja: (r.nomGranja || g).toString().trim() });
                }
            });
            return out;
        }

        function cronogramasUnicosParaLeyenda() {
            var seen = {};
            var out = [];
            listadoData.forEach(function(r) {
                var numC = (r.numCronograma !== undefined && r.numCronograma !== null) ? Number(r.numCronograma) : 0;
                if (!numC || seen[numC]) return;
                seen[numC] = true;
                out.push({
                    numCronograma: numC,
                    codPrograma: (r.codPrograma || '').toString().trim(),
                    nomPrograma: (r.nomPrograma || '').toString().trim()
                });
            });
            out.sort(function(a, b) { return a.numCronograma - b.numCronograma; });
            out.forEach(function(c, i) {
                c.etiqueta = (i + 1) + ' — ' + (c.codPrograma || '') + (c.nomPrograma ? ' ' + c.nomPrograma : '');
            });
            return out;
        }

        function colorCronograma(numCronograma) {
            var idx = Math.abs(Number(numCronograma) || 0) % coloresGranja.length;
            return coloresGranja[idx];
        }

        var calEventosGlobal = [];
        function renderCalendario(codProgramaFiltro) {
            codProgramaFiltroCal = codProgramaFiltro || null;
            var elFiltro = document.getElementById('calFiltroPrograma');
            if (elFiltro) {
                if (codProgramaFiltroCal && String(codProgramaFiltroCal).indexOf('num_') === 0) {
                    var n = parseInt(String(codProgramaFiltroCal).replace('num_', ''), 10);
                    var g = window.gruposCronograma ? window.gruposCronograma.find(function(x) { return Number(x.numCronograma) === n; }) : null;
                    elFiltro.textContent = g ? ('Cronograma N° ' + g.numCronograma + (g.codPrograma ? ' — ' + g.codPrograma + (g.nomPrograma ? ' ' + g.nomPrograma : '') : '')) : ('Cronograma N° ' + n);
                    elFiltro.classList.remove('hidden');
                } else if (codProgramaFiltroCal === null) {
                    elFiltro.textContent = 'Todos los cronogramas (filtro actual)';
                    elFiltro.classList.remove('hidden');
                } else {
                    elFiltro.textContent = '';
                    elFiltro.classList.add('hidden');
                }
            }
            var eventosPorDia = eventosPorDiaDesdeListado();
            var esVistaTodosCronogramas = (codProgramaFiltroCal === null);
            var granjasLeyenda = esVistaTodosCronogramas ? [] : granjasUnicasParaLeyenda();
            var cronogramasLeyenda = esVistaTodosCronogramas ? cronogramasUnicosParaLeyenda() : [];
            calEventosGlobal = [];
            var mesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            document.getElementById('calMesAnio').textContent = mesNombres[mesActualCal] + ' ' + anioActualCal;

            function pad2(n) { return (n < 10 ? '0' : '') + n; }
            var primerdia = new Date(anioActualCal, mesActualCal, 1);
            var inicioSem = primerdia.getDay();
            var diasGrid = [];
            var d = 1 - inicioSem;
            for (var i = 0; i < 42; i++) {
                var fecha = new Date(anioActualCal, mesActualCal, d + i);
                var esEsteMes = fecha.getMonth() === mesActualCal;
                var dia = fecha.getDate();
                var key = fecha.getFullYear() + '-' + pad2(fecha.getMonth() + 1) + '-' + pad2(dia);
                var eventos = eventosPorDia[key] || [];
                diasGrid.push({ dia: dia, esEsteMes: esEsteMes, key: key, eventos: eventos });
            }

            var html = '<div class="cal-dia-header">Dom</div><div class="cal-dia-header">Lun</div><div class="cal-dia-header">Mar</div><div class="cal-dia-header">Mié</div><div class="cal-dia-header">Jue</div><div class="cal-dia-header">Vie</div><div class="cal-dia-header">Sáb</div>';
            diasGrid.forEach(function(cel) {
                var cls = 'cal-dia';
                if (!cel.esEsteMes) cls += ' otro-mes';
                html += '<div class="' + cls + '">';
                html += '<div class="cal-dia-num">' + cel.dia + '</div>';
                (cel.eventos || []).forEach(function(ev) {
                    var idx = calEventosGlobal.length;
                    calEventosGlobal.push(ev);
                    var color = esVistaTodosCronogramas ? colorCronograma(ev.numCronograma) : colorGranja(ev.granja);
                    var texto = esVistaTodosCronogramas
                        ? (ev.codPrograma || '') + (ev.nomPrograma ? ' ' + ev.nomPrograma : '') + ' · ' + (ev.nomGranja || ev.granja) + ' · ' + (ev.campania || '') + ' · ' + (ev.galpon || '') + (ev.edad !== '—' && ev.edad !== '' ? ' · ' + ev.edad + 'd' : '')
                        : (ev.nomGranja || ev.granja) + ' · ' + (ev.campania || '') + ' · ' + (ev.galpon || '') + (ev.edad !== '—' && ev.edad !== '' ? ' · ' + ev.edad + 'd' : '');
                    html += '<div class="cal-evento cal-evento-click" data-evidx="' + idx + '" style="border-left: 3px solid ' + color + '">';
                    html += '<span class="cal-evento-dot" style="background:' + color + '"></span>';
                    html += '<span class="cal-evento-texto" title="' + esc(texto) + '">' + esc(texto) + '</span>';
                    html += '</div>';
                });
                html += '</div>';
            });
            document.getElementById('calGrid').innerHTML = html;

            var leyendaHtml = '';
            if (esVistaTodosCronogramas) {
                leyendaHtml = '<div class="leyenda-titulo font-semibold text-gray-700 mb-1">Cronogramas</div>';
                cronogramasLeyenda.forEach(function(c) {
                    var color = colorCronograma(c.numCronograma);
                    leyendaHtml += '<div class="leyenda-item"><span class="leyenda-color" style="background:' + color + '"></span><span>' + esc(c.etiqueta) + '</span></div>';
                });
            } else {
                granjasLeyenda.forEach(function(g) {
                    var color = colorGranja(g.granja);
                    leyendaHtml += '<div class="leyenda-item"><span class="leyenda-color" style="background:' + color + '"></span><span>' + esc(g.nomGranja || g.granja) + '</span></div>';
                });
            }
            document.getElementById('calLeyenda').innerHTML = leyendaHtml || '<span class="text-gray-500">Sin eventos en el cronograma</span>';

            document.querySelectorAll('.cal-evento-click').forEach(function(el) {
                el.addEventListener('click', function() {
                    var idx = parseInt(this.getAttribute('data-evidx'), 10);
                    var ev = calEventosGlobal[idx];
                    if (!ev) return;
                    window.detEvCodPrograma = ev.codPrograma || '';
                    window.detEvPosDetalle = ev.posDetalle !== undefined && ev.posDetalle !== null ? String(ev.posDetalle) : '';
                    document.getElementById('detEvGranja').textContent = ev.granja || '—';
                    document.getElementById('detEvNomGranja').textContent = ev.nomGranja || '—';
                    document.getElementById('detEvCampania').textContent = ev.campania || '—';
                    document.getElementById('detEvGalpon').textContent = ev.galpon || '—';
                    document.getElementById('detEvEdad').textContent = ev.edad !== undefined && ev.edad !== null && ev.edad !== '' ? ev.edad : '—';
                    document.getElementById('detEvPrograma').textContent = (ev.codPrograma || '') + (ev.nomPrograma ? ' — ' + ev.nomPrograma : '');
                    document.getElementById('detEvFecCarga').textContent = fechaDDMMYYYY(ev.fechaCarga) || '—';
                    document.getElementById('detEvFecEjec').textContent = fechaDDMMYYYY(ev.fechaEjecucion) || '—';
                    var cabDet = document.getElementById('detEvProgramaCabDet');
                    cabDet.classList.add('hidden');
                    cabDet.innerHTML = '';
                    document.getElementById('btnDetEvVerMas').innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Ver más';
                    document.getElementById('modalDetalleEvento').classList.remove('hidden');
                });
            });
        }

        document.getElementById('tablaCronograma').addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-cal-fila');
            if (!btn) return;
            e.preventDefault();
            var numCrono = btn.getAttribute('data-numcronograma');
            if (numCrono === null || numCrono === '') return;
            var hoy = new Date();
            mesActualCal = hoy.getMonth();
            anioActualCal = hoy.getFullYear();
            renderCalendario('num_' + numCrono);
            document.getElementById('modalCalendario').classList.remove('hidden');
        });

        document.getElementById('btnCalendarioFiltrado').addEventListener('click', function() {
            var qs = paramsFiltro();
            var url = 'listar_cronograma.php' + (qs ? '?' + qs : '');
            var btnCal = this;
            var textoOriginal = btnCal.innerHTML;
            btnCal.disabled = true;
            btnCal.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Cargando...';
            fetch(url).then(function(r) { return r.json(); }).then(function(res) {
                btnCal.disabled = false;
                btnCal.innerHTML = textoOriginal;
                if (!res.success) return;
                listadoData = res.data || [];
                var map = {};
                listadoData.forEach(function(r) {
                    var numC = (r.numCronograma !== undefined && r.numCronograma !== null && r.numCronograma !== 0) ? Number(r.numCronograma) : 0;
                    if (!numC) return;
                    var key = 'n' + numC;
                    if (!map[key]) {
                        map[key] = { numCronograma: numC, codPrograma: r.codPrograma || '', nomPrograma: r.nomPrograma || '', fechaProg: r.fechaHoraRegistro || '', detalles: [] };
                    }
                    map[key].detalles.push(r);
                });
                window.gruposCronograma = Object.keys(map).map(function(k) { return map[k]; }).sort(function(a, b) { return (b.numCronograma || 0) - (a.numCronograma || 0); });
                var hoy = new Date();
                mesActualCal = hoy.getMonth();
                anioActualCal = hoy.getFullYear();
                renderCalendario(null);
                document.getElementById('modalCalendario').classList.remove('hidden');
            }).catch(function() {
                btnCal.disabled = false;
                btnCal.innerHTML = textoOriginal;
            });
        });
        document.getElementById('modalCalendario').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
        document.getElementById('modalDetalleEvento').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
        document.getElementById('calPrevMes').addEventListener('click', function() {
            mesActualCal--;
            if (mesActualCal < 0) { mesActualCal = 11; anioActualCal--; }
            renderCalendario(codProgramaFiltroCal);
        });
        document.getElementById('calNextMes').addEventListener('click', function() {
            mesActualCal++;
            if (mesActualCal > 11) { mesActualCal = 0; anioActualCal++; }
            renderCalendario(codProgramaFiltroCal);
        });

        document.getElementById('btnDetEvVerMas').addEventListener('click', function() {
            var box = document.getElementById('detEvProgramaCabDet');
            if (box.classList.contains('hidden')) {
                var cod = window.detEvCodPrograma || '';
                if (!cod) { box.innerHTML = '<p class="text-gray-500">No hay programa asociado.</p>'; box.classList.remove('hidden'); return; }
                box.innerHTML = '<p class="text-gray-500">Cargando...</p>';
                box.classList.remove('hidden');
                var btnVerMas = document.getElementById('btnDetEvVerMas');
                var posDet = window.detEvPosDetalle || '';
                fetch('../programas/get_programa_cab_detalle.php?codigo=' + encodeURIComponent(cod)).then(function(r) { return r.json(); }).then(function(res) {
                    if (!res.success) { box.innerHTML = '<p class="text-red-600">' + esc(res.message || 'Error al cargar programa.') + '</p>'; return; }
                    var cab = res.cab || {};
                    var det = res.detalles || [];
                    var detFila = null;
                    if (posDet !== '') {
                        for (var i = 0; i < det.length; i++) {
                            if (String(det[i].posDetalle !== undefined && det[i].posDetalle !== null ? det[i].posDetalle : '') === posDet) {
                                detFila = det[i];
                                break;
                            }
                        }
                    }
                    if (detFila === null && det.length > 0) detFila = det[0];
                    var html = '<div class="cab-tit">Programa</div>';
                    html += '<dl class="cab-dos-columnas">';
                    html += '<div><dt>Código</dt><dd>' + esc(cab.codigo) + '</dd></div>';
                    html += '<div><dt>Nombre</dt><dd>' + esc(cab.nombre) + '</dd></div>';
                    html += '<div><dt>Tipo</dt><dd>' + esc(cab.nomTipo || '') + '</dd></div>';
                    if (cab.zona) html += '<div><dt>Zona</dt><dd>' + esc(cab.zona) + '</dd></div>';
                    if (cab.descripcion) html += '<div style="grid-column:1/-1;"><dt>Descripción</dt><dd>' + esc(cab.descripcion) + '</dd></div>';
                    html += '</dl>';
                    html += '<div class="cab-tit" style="margin-top:0.75rem;">N° ' + esc(posDet || '—') + '</div>';
                    if (!detFila) {
                        html += '<p class="text-gray-500 text-xs">No se encontró el detalle para este registro.</p>';
                    } else {
                        var cols = ['ubicacion','codProducto','nomProducto','edad','dosis','unidadDosis','numeroFrascos'];
                        html += '<table><thead><tr>';
                        cols.forEach(function(c) { html += '<th>' + esc(c) + '</th>'; });
                        html += '</tr></thead><tbody><tr>';
                        cols.forEach(function(c) { html += '<td>' + esc(detFila[c] !== undefined && detFila[c] !== null ? detFila[c] : '') + '</td>'; });
                        html += '</tr></tbody></table>';
                    }
                    box.innerHTML = html;
                    if (btnVerMas) btnVerMas.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> Ocultar';
                }).catch(function() {
                    box.innerHTML = '<p class="text-red-600">Error al cargar.</p>';
                });
            } else {
                box.classList.add('hidden');
                box.innerHTML = '';
                this.innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Ver más';
            }
        });

        function aplicarVisibilidadPeriodoCronograma() {
            var t = document.getElementById('periodoTipo').value || '';
            ['periodoPorFecha','periodoEntreFechas','periodoPorMes','periodoEntreMeses'].forEach(function(id) {
                document.getElementById(id).classList.add('hidden');
            });
            if (t === 'POR_FECHA') document.getElementById('periodoPorFecha').classList.remove('hidden');
            else if (t === 'ENTRE_FECHAS') document.getElementById('periodoEntreFechas').classList.remove('hidden');
            else if (t === 'POR_MES') document.getElementById('periodoPorMes').classList.remove('hidden');
            else if (t === 'ENTRE_MESES') document.getElementById('periodoEntreMeses').classList.remove('hidden');
        }

        document.getElementById('periodoTipo').addEventListener('change', aplicarVisibilidadPeriodoCronograma);

        document.getElementById('btnToggleFiltrosCronograma').addEventListener('click', function() {
            var cont = document.getElementById('contenidoFiltrosCronograma');
            var icon = document.getElementById('iconoFiltrosCronograma');
            if (cont.classList.contains('hidden')) {
                cont.classList.remove('hidden');
                if (icon) icon.style.transform = 'rotate(180deg)';
            } else {
                cont.classList.add('hidden');
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        });

        document.getElementById('btnFiltrarCronograma').addEventListener('click', function() { cargarListado(); });
        document.getElementById('btnLimpiarFiltrosCronograma').addEventListener('click', function() {
            document.getElementById('periodoTipo').value = 'POR_FECHA';
            document.getElementById('fechaUnica').value = new Date().toISOString().slice(0, 10);
            document.getElementById('fechaInicio').value = '';
            document.getElementById('fechaFin').value = '';
            document.getElementById('mesUnico').value = '';
            document.getElementById('mesInicio').value = '';
            document.getElementById('mesFin').value = '';
            document.getElementById('filtroCodTipo').value = '';
            aplicarVisibilidadPeriodoCronograma();
            cargarListado();
        });

        document.getElementById('btnReportePdfFiltrado').addEventListener('click', function() {
            var qs = paramsFiltro();
            window.open('generar_reporte_cronograma_filtrado_pdf.php' + (qs ? '?' + qs : ''), '_blank');
        });

        fetch('../programas/get_tipos_programa.php').then(function(r) { return r.json(); }).then(function(res) {
            if (!res.success || !res.data) return;
            var sel = document.getElementById('filtroCodTipo');
            (res.data || []).forEach(function(t) {
                var opt = document.createElement('option');
                opt.value = t.codigo !== undefined ? t.codigo : '';
                opt.textContent = (t.nombre || t.sigla || 'Cód. ' + t.codigo) + (t.sigla ? ' (' + t.sigla + ')' : '');
                sel.appendChild(opt);
            });
        }).catch(function() {});

        aplicarVisibilidadPeriodoCronograma();
        cargarListado();
    </script>
</body>
</html>
