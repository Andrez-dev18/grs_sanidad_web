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
    <script src="../../../assets/js/pagination-iconos.js"></script>
    <style>
        body { background: #f8f9fa; font-family: system-ui, sans-serif; }
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
        .cal-dia.cal-dia-celda { cursor: pointer; }
        .cal-dia.otro-mes { background: #f1f5f9; color: #94a3b8; }
        .cal-dia.cal-dia-hoy { background: #dbeafe; border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,0.3); }
        .cal-dia.cal-dia-elegida { background: #fef3c7; border-color: #d97706; box-shadow: 0 0 0 2px rgba(217,119,6,0.3); }
        .cal-dia.cal-dia-hoy.cal-dia-elegida { background: #dbeafe; border-color: #2563eb; }
        .cal-dia-num { font-weight: 600; color: #475569; margin-bottom: 4px; }
        .cal-evento { padding: 2px 6px; border-radius: 4px; margin-bottom: 2px; font-size: 0.68rem; cursor: pointer; display: flex; align-items: flex-start; gap: 4px; line-height: 1.2; }
        .cal-evento:hover { opacity: 0.9; }
        .cal-evento-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; margin-top: 0.25rem; }
        .cal-evento-texto { white-space: pre-line; word-break: break-word; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; line-clamp: 4; }
        .leyenda { display: flex; flex-wrap: wrap; gap: 0.75rem 1rem; margin-top: 0.75rem; font-size: 0.8rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; align-items: center; }
        .leyenda-titulo { width: 100%; font-weight: 600; color: #374151; margin-bottom: 0.25rem; }
        .leyenda-item { display: flex; align-items: center; gap: 0.35rem; cursor: pointer; user-select: none; }
        .leyenda-item input[type="checkbox"] { cursor: pointer; margin-right: 0.15rem; }
        .leyenda-color { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
        /* Toolbar y paginación tabla detalles (igual que cronograma-registro) */
        #tabPanelGranjas .tabla-crono-wrapper { background: #fff; border-radius: 0.75rem; padding: 1rem; margin-bottom: 0.5rem; }
        #tabPanelGranjas .tabla-crono-toolbar-top { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 0.75rem; }
        @media (min-width: 768px) { #tabPanelGranjas .tabla-crono-toolbar-top { flex-direction: row; align-items: center; justify-content: space-between; } }
        #tabPanelGranjas .tabla-crono-toolbar-top .toolbar-length { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #374151; }
        #tabPanelGranjas .tabla-crono-toolbar-top .toolbar-length select { padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; }
        #tabPanelGranjas .tabla-crono-toolbar-top .toolbar-filter input { padding: 0.4rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; min-width: 160px; }
        #tabPanelGranjas .tabla-crono-toolbar-bottom { display: flex; flex-direction: column; gap: 0.75rem; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; color: #4b5563; }
        @media (min-width: 768px) { #tabPanelGranjas .tabla-crono-toolbar-bottom { flex-direction: row; align-items: center; justify-content: space-between; } }
        #tabPanelGranjas .tabla-crono-toolbar-bottom .paginacion-controles { display: flex; align-items: center; gap: 0.5rem; }
        #tabPanelGranjas .tabla-crono-toolbar-bottom .paginacion-controles button { padding: 0.4rem 0.75rem; border-radius: 0.5rem; border: 1px solid #d1d5db; background: #fff; font-size: 0.875rem; cursor: pointer; }
        #tabPanelGranjas .tabla-crono-toolbar-bottom .paginacion-controles button:hover:not(:disabled) { background: #eff6ff; color: #1d4ed8; }
        #tabPanelGranjas .tabla-crono-toolbar-bottom .paginacion-controles button:disabled { opacity: 0.5; cursor: not-allowed; }
        #tabPanelGranjas .table-wrapper { overflow-x: auto; }
        .btn-ver-mas { margin-top: 0.75rem; padding: 0.4rem 0.75rem; border-radius: 0.5rem; border: 1px solid #93c5fd; color: #2563eb; background: #eff6ff; font-size: 0.8rem; cursor: pointer; }
        .btn-ver-mas:hover { background: #dbeafe; }
        .programa-cab-detalle { margin-top: 0.75rem; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #f8fafc; font-size: 0.8rem; }
        .programa-cab-detalle .cab-tit { font-weight: 600; color: #1e40af; margin-bottom: 0.5rem; }
        .programa-cab-detalle table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; font-size: 0.75rem; }
        .programa-cab-detalle th, .programa-cab-detalle td { padding: 4px 6px; border: 1px solid #e2e8f0; text-align: left; }
        .programa-cab-detalle th { background: #e0e7ff; color: #3730a3; }
        .modal-calendario-alto .modal-box { min-height: 75vh; max-height: 90vh; }
        .modal-calendario-grande { width: 95vw !important; max-width: 95vw !important; height: 90vh !important; max-height: 90vh !important; display: flex !important; flex-direction: column !important; }
        .modal-calendario-grande .modal-body { flex: 1; min-height: 0; overflow: auto; }
        .modal-detalle-evento .modal-box {
            border-radius: 1rem; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            width: 92%; max-width: 720px;
            max-height: 88vh;
            display: flex; flex-direction: column;
            position: relative; z-index: 9999;
        }
        .modal-detalle-evento .modal-header { flex-shrink: 0; }
        .modal-detalle-evento .modal-body {
            flex: 1; min-height: 0;
            overflow-y: auto; overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            word-wrap: break-word; overflow-wrap: break-word;
            padding: 0 1.25rem 1.25rem;
        }
        .modal-detalle-evento .detalle-evento-seccion {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
            font-size: 0.8125rem;
        }
        .modal-detalle-evento .detalle-evento-seccion-titulo {
            font-weight: 600; color: #475569; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; padding-bottom: 0.25rem; border-bottom: 1px solid #e2e8f0;
        }
        .modal-detalle-evento .detalle-evento-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.35rem 1rem; }
        .modal-detalle-evento .detalle-evento-grid dt { color: #64748b; font-weight: 500; margin: 0; }
        .modal-detalle-evento .detalle-evento-grid dd { margin: 0; color: #1e293b; }
        .modal-detalle-evento .programa-seccion {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
            font-size: 0.8125rem;
        }
        .modal-detalle-evento .programa-seccion-titulo { font-weight: 600; color: #1e40af; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; margin: 0; }
        .modal-detalle-evento .programa-seccion-header { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.5rem; flex-wrap: wrap; }
        .modal-detalle-evento .programa-seccion-header .btn-ver-mas { margin-top: 0; }
        .modal-detalle-evento .programa-cab-fila { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.35rem 1rem; font-size: 0.8125rem; }
        .modal-detalle-evento .programa-cab-fila .campo { display: block; }
        .modal-detalle-evento .programa-cab-fila .campo.descripcion { grid-column: 1 / -1; }
        .modal-detalle-evento .programa-cab-fila .campo dt { color: #64748b; font-weight: 500; margin: 0; font-size: 0.7rem; }
        .modal-detalle-evento .programa-cab-fila .campo dd { margin: 0; color: #1e293b; white-space: pre-wrap; word-break: break-word; }
        .modal-detalle-evento .programa-cab-detalle { max-width: 100%; overflow-x: auto; margin-top: 0.5rem; border-radius: 0.375rem; border: 1px solid #e2e8f0; }
        .modal-detalle-evento .programa-cab-detalle table { table-layout: auto; min-width: 100%; font-size: 0.8125rem; }
        .cab-dos-columnas { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1.5rem; font-size: 0.8rem; }
        .cab-dos-columnas dt { color: #64748b; font-weight: 500; }
        .cab-dos-columnas dd { margin: 0; color: #1e293b; }
        .detalle-evento-fila { display: flex; flex-wrap: wrap; gap: 1rem 1.5rem; align-items: baseline; font-size: 0.875rem; }
        .detalle-evento-fila .campo { display: flex; align-items: baseline; gap: 0.35rem; }
        .detalle-evento-fila .campo dt { color: #64748b; font-weight: 500; margin: 0; }
        .detalle-evento-fila .campo dd { margin: 0; color: #1e293b; }
        .detalle-evento-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1.5rem; font-size: 0.875rem; }
        .detalle-evento-grid dt { color: #64748b; font-weight: 500; }
        .detalle-evento-grid dd { margin: 0; color: #1e293b; }
        .programa-cab-fila { display: flex; flex-wrap: wrap; gap: 1rem 1.5rem; align-items: flex-start; font-size: 0.875rem; margin-bottom: 0.75rem; }
        .programa-cab-fila .campo { display: flex; flex-direction: column; gap: 0.25rem; max-width: 100%; }
        .programa-cab-fila .campo.descripcion { min-width: 280px; }
        .programa-cab-fila .campo dt { color: #64748b; font-weight: 500; margin: 0; }
        .programa-cab-fila .campo dd { margin: 0; color: #1e293b; white-space: pre-wrap; word-break: break-word; }
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
        <div class="card-filtros-compacta mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <button type="button" id="btnToggleFiltrosCronograma" class="w-full px-6 py-4 flex items-center justify-between text-left font-medium text-gray-800 hover:bg-gray-50 transition">
                <span><i class="fas fa-filter mr-2 text-blue-600"></i> Filtros</span>
                <svg id="iconoFiltrosCronograma" class="w-5 h-5 text-gray-600 transition-transform duration-300 rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="contenidoFiltrosCronograma" class="px-6 pb-6 pt-4">
                <div class="border-t border-gray-100 pt-4 mx-4">
                <div class="filter-row-periodo flex flex-wrap items-end gap-4 mb-6">
                    <div class="flex-shrink-0" style="min-width: 200px;">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1 text-blue-600"></i> Periodo</label>
                        <select id="periodoTipo" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer text-sm">
                            <option value="TODOS" selected>Todos</option>
                            <option value="POR_FECHA">Por fecha</option>
                            <option value="ENTRE_FECHAS">Entre fechas</option>
                            <option value="POR_MES">Por mes</option>
                            <option value="ENTRE_MESES">Entre meses</option>
                            <option value="ULTIMA_SEMANA">Última Semana</option>
                        </select>
                    </div>
                    <div id="periodoPorFecha" class="flex-shrink-0 min-w-[200px] hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-day mr-1 text-blue-600"></i> Fecha</label>
                        <input id="fechaUnica" type="date" value="<?php echo date('Y-m-d'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div id="periodoEntreFechas" class="hidden flex-shrink-0 flex items-end gap-2">
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i> Desde</label>
                            <input id="fechaInicio" type="date" value="<?php echo date('Y-m-01'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i> Hasta</label>
                            <input id="fechaFin" type="date" value="<?php echo date('Y-m-t'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <div id="periodoPorMes" class="hidden flex-shrink-0 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar mr-1 text-blue-600"></i> Mes</label>
                        <input id="mesUnico" type="month" value="<?php echo date('Y-m'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div id="periodoEntreMeses" class="hidden flex-shrink-0 flex items-end gap-2">
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i> Mes Inicio</label>
                            <input id="mesInicio" type="month" value="<?php echo date('Y') . '-01'; ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i> Mes Fin</label>
                            <input id="mesFin" type="month" value="<?php echo date('Y') . '-12'; ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
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
                </div>
                </div>
            </div>
        </div>
        <div class="mb-4">
            <button type="button" id="btnAbrirCalendario" class="px-5 py-2 rounded-lg text-white text-sm font-medium inline-flex items-center gap-2" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                <i class="fas fa-calendar-alt"></i> Ver calendario
            </button>
        </div>
        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden" id="tablaCronogramaWrapper" data-vista-tabla-iconos data-vista="lista">
            <div class="p-4">
                <!-- Toolbar: Lista/Iconos y controles de tabla en la misma fila (como reportes) -->
                <div class="toolbar-vista-row flex flex-wrap items-center justify-between gap-3 mb-3" id="cronogramaToolbarRow">
                    <div class="view-toggle-group flex items-center gap-2" id="viewToggleGroupCrono">
                        <button type="button" class="view-toggle-btn active" id="btnViewListaCrono" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                        <button type="button" class="view-toggle-btn" id="btnViewIconosCrono" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                    </div>
                    <div id="cronoDtControls" class="flex flex-wrap items-center gap-3"></div>
                    <div id="cronoIconosControls" class="flex flex-wrap items-center gap-3" style="display: none;"></div>
                </div>
                <div class="view-lista-wrap" id="viewListaCrono">
            <div class="table-wrapper table-wrapper-borde">
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
                <div class="view-tarjetas-wrap hidden px-4 pb-4 overflow-x-hidden" id="viewTarjetasCrono" style="display: none;">
                    <div id="cardsControlsTopCrono" class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-gray-600 border-b border-gray-200 pb-3"></div>
                    <div id="cardsContainerCrono" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                    <div id="cardsPaginationCrono" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3" data-page-handler="cronoIconosPageGo"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Calendario (super grande) -->
    <div id="modalCalendario" class="modal-overlay hidden">
        <div class="modal-box modal-calendario-grande rounded-2xl overflow-hidden">
            <div class="modal-header flex-shrink-0 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-800">Calendario</h3>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none" data-modal="modalCalendario">&times;</button>
            </div>
            <div class="modal-body p-4 overflow-auto">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <button type="button" id="calPrevMes" class="px-2 py-1 rounded border border-gray-300 hover:bg-gray-100 text-sm">&laquo;</button>
                    <span id="calMesAnio" class="font-semibold text-gray-800 text-lg min-w-[140px]"></span>
                    <button type="button" id="calNextMes" class="px-2 py-1 rounded border border-gray-300 hover:bg-gray-100 text-sm">&raquo;</button>
                    <input type="date" id="calIrFecha" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm ml-2">
                </div>
                <div id="calGrid" class="cal-grid"></div>
                <div id="calLeyenda" class="leyenda"></div>
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
                    <div class="tabla-crono-wrapper">
                        <div id="detallesToolbarTop" class="tabla-crono-toolbar-top">
                            <div class="toolbar-length"><span>Mostrar</span><select id="detallesPageSize"><option value="20">20</option><option value="50">50</option><option value="100">100</option></select><span>registros</span></div>
                            <div class="toolbar-filter"><label class="inline-flex items-center gap-1"><span>Buscar:</span><input type="text" id="detallesSearch" placeholder="Buscar..." autocomplete="off"></label></div>
                        </div>
                        <div class="table-wrapper">
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
                        <div id="detallesToolbarBottom" class="tabla-crono-toolbar-bottom"></div>
                    </div>
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

    <!-- Modal Detalle evento (al hacer clic en un evento del calendario) -->
    <div id="modalDetalleEvento" class="modal-overlay hidden modal-detalle-evento">
        <div class="modal-box rounded-2xl overflow-hidden">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-800">Detalle del evento</h3>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none" data-modal="modalDetalleEvento">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detalle-evento-seccion">
                    <div class="detalle-evento-seccion-titulo">Cronograma</div>
                    <div class="detalle-evento-grid">
                        <dt>Granja</dt><dd id="detEvGranja" class="text-gray-800"></dd>
                        <dt>Campaña</dt><dd id="detEvCampania" class="text-gray-800"></dd>
                        <dt>Galpón</dt><dd id="detEvGalpon" class="text-gray-800"></dd>
                        <dt>Edad de aplicación</dt><dd id="detEvEdad" class="text-gray-800"></dd>
                        <dt>Fecha de carga</dt><dd id="detEvFecCarga" class="text-gray-800"></dd>
                        <dt>Fecha de ejecución</dt><dd id="detEvFecEjec" class="text-gray-800"></dd>
                    </div>
                </div>
                <div id="detEvVerMasWrap" class="programa-seccion">
                    <div class="programa-seccion-header">
                        <span class="detalle-evento-seccion-titulo programa-seccion-titulo">Programa</span>
                        <button type="button" id="btnDetEvVerMas" class="btn-ver-mas text-blue-600 hover:text-blue-800 text-sm font-medium"><i class="fas fa-chevron-down mr-1"></i> Ver programa</button>
                    </div>
                    <div id="detEvProgramaCabFila" class="programa-cab-fila hidden"></div>
                    <div id="detEvProgramaCabDet" class="programa-cab-detalle hidden mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var listadoData = [];
        var calendarData = [];
        var mesActualCal = 0, anioActualCal = 0;
        var calFechaElegida = null;
        var codProgramaFiltroCal = null;
        var coloresGranja = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1','#14b8a6','#a855f7','#e11d48','#0ea5e9','#22c55e','#f43f5e','#d946ef','#facc15','#2dd4bf','#64748b','#1d4ed8','#15803d','#c2410c','#4f46e5','#be185d','#0d9488','#65a30d','#dc2626','#7c3aed','#0891b2'];
        var calCronogramasVisibles = null;
        var calGranjasVisibles = null;
        var cronogramaModalCount = 0;
        function notificarParentModal(abierto) {
            if (abierto) {
                cronogramaModalCount++;
                try { (window.top || window.parent).postMessage({ type: 'sanidadIframeFullscreen', open: true }, '*'); } catch (e) {}
            } else {
                cronogramaModalCount--;
                if (cronogramaModalCount <= 0) { cronogramaModalCount = 0; try { (window.top || window.parent).postMessage({ type: 'sanidadIframeFullscreen', open: false }, '*'); } catch (e) {} }
            }
        }

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
        var columnasDetalleCompletas = ['ubicacion','producto','proveedor','unidad','dosis','unidadDosis','numeroFrascos','edad','descripcion_vacuna','area_galpon','cantidad_por_galpon'];
        var labelsReportePrograma = { num: '#', ubicacion: 'Ubicación', producto: 'Producto', proveedor: 'Proveedor', unidad: 'Unidad', dosis: 'Dosis', descripcion_vacuna: 'Descripción', numeroFrascos: 'Nº frascos', edad: 'Edad de aplicación', unidadDosis: 'Unid. dosis', area_galpon: 'Área galpón', cantidad_por_galpon: 'Cant. por galpón' };
        function formatearDescripcionVacunaDet(s) {
            if (s === null || s === undefined) s = ''; s = String(s).trim();
            if (!s) return '';
            if (/^Contra[\r\n]/.test(s) || (s.indexOf('\n') !== -1 && s.indexOf('- ') !== -1)) return s;
            var partes = s.split(',').map(function(x) { return x.trim(); }).filter(Boolean);
            return partes.length ? 'Contra\n' + partes.map(function(p) { return '- ' + p; }).join('\n') : '';
        }
        function valorCeldaDetallePrograma(k, d) {
            if (k === 'num') return '';
            if (k === 'ubicacion') return esc(d.ubicacion || '');
            if (k === 'producto') return esc(d.nomProducto || d.codProducto || '');
            if (k === 'proveedor') return esc((d.codProveedor && String(d.codProveedor).trim()) ? d.codProveedor : (d.nomProveedor || ''));
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
        function valorClaveDetalle(k, d) {
            if (k === 'edad') return '';
            if (k === 'num') return '';
            if (k === 'ubicacion') return (d.ubicacion || '');
            if (k === 'producto') return (d.nomProducto || d.codProducto || '');
            if (k === 'proveedor') return ((d.codProveedor && String(d.codProveedor).trim()) ? d.codProveedor : (d.nomProveedor || ''));
            if (k === 'unidad') return (d.unidades || '');
            if (k === 'dosis') return (d.dosis || '');
            if (k === 'descripcion_vacuna') return (d.descripcionVacuna || '');
            if (k === 'numeroFrascos') return (d.numeroFrascos || '');
            if (k === 'unidadDosis') return (d.unidadDosis || '');
            if (k === 'area_galpon') return (d.areaGalpon !== null && d.areaGalpon !== undefined ? String(d.areaGalpon) : '');
            if (k === 'cantidad_por_galpon') return (d.cantidadPorGalpon !== null && d.cantidadPorGalpon !== undefined ? String(d.cantidadPorGalpon) : '');
            return '';
        }
        function agruparDetallesPorEdad(detalles, colsSinNum) {
            if (!detalles || detalles.length === 0) return [];
            var colsSinEdad = colsSinNum.filter(function(k) { return k !== 'edad'; });
            var map = {};
            detalles.forEach(function(d) {
                var key = colsSinEdad.map(function(k) { return valorClaveDetalle(k, d); }).join('\t');
                if (!map[key]) map[key] = [];
                map[key].push(d);
            });
            var out = [];
            Object.keys(map).forEach(function(key) {
                var group = map[key];
                var first = group[0];
                var ages = group.map(function(d) { var e = d.edad; return (e !== null && e !== undefined && e !== '' ? String(e).trim() : null); }).filter(Boolean);
                var merged = {};
                for (var p in first) if (first.hasOwnProperty(p)) merged[p] = first[p];
                merged.edad = ages.length > 0 ? ages.join(' - ') : (first.edad !== null && first.edad !== undefined ? String(first.edad) : '');
                out.push(merged);
            });
            return out;
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
                var detallesAgrupados = agruparDetallesPorEdad(detalles, colsSinNum);
                var thCells = '<th class="px-3 py-2 text-left bg-blue-600 text-white">Código</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Nombre programa</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Despliegue</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Descripción</th>';
                colsSinNum.forEach(function(k) { thCells += '<th class="px-3 py-2 text-left bg-blue-600 text-white">' + (labelsReportePrograma[k] || k) + '</th>'; });
                theadEl.innerHTML = '<tr>' + thCells + '</tr>';
                tbodyEl.innerHTML = '';
                if (detallesAgrupados.length === 0) {
                    sinRegEl.classList.remove('hidden');
                } else {
                    detallesAgrupados.forEach(function(d) {
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
                        '<td class="px-4 py-3"><button type="button" class="btn-cal btn-cal-fila" ' + dataKey + ' title="Ver calendario de este cronograma"><i class="fas fa-calendar-alt"></i></button> <a href="' + urlPdf + '" target="_blank" class="btn-row inline-flex items-center ml-1" title="Reporte PDF"><i class="fas fa-file-pdf text-red-600"></i></a></td>';
                    tbody.appendChild(tr);
                });
                window.gruposCronograma = grupos;
                $('#tablaCronograma').DataTable({
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                    order: [[1, 'asc']],
                    dom: '<"dt-top-row"<"flex items-center gap-6" l><"flex items-center gap-2" f>>rt<"dt-bottom-row"<"text-sm text-gray-600" i><"text-sm text-gray-600" p>>',
                    initComplete: function() {
                        var wrapper = $('#tablaCronograma').closest('.dataTables_wrapper');
                        var $length = wrapper.find('.dataTables_length').first();
                        var $filter = wrapper.find('.dataTables_filter').first();
                        var $controls = $('#cronoDtControls');
                        if ($controls.length && $length.length && $filter.length) {
                            $controls.append($length, $filter);
                        }
                    }
                });
            }).catch(function() {});
        }

        document.querySelectorAll('.modal-cerrar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-modal');
                if (id) {
                    document.getElementById(id).classList.add('hidden');
                    if (id === 'modalCalendario' || id === 'modalDetalleEvento') {
                        notificarParentModal(false);
                        var tituloCal = document.querySelector('#modalCalendario h3');
                        if (tituloCal) tituloCal.textContent = 'Calendario';
                    }
                }
            });
        });
        document.getElementById('modalDetalles').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });

        document.getElementById('tablaCronograma').addEventListener('click', function(e) {
            var btnCal = e.target.closest('.btn-cal-fila');
            if (btnCal) {
                e.preventDefault();
                var numCrono = btnCal.getAttribute('data-numcronograma');
                var grupos = window.gruposCronograma;
                if (!grupos || !numCrono) return;
                var g = grupos.find(function(x) { return Number(x.numCronograma) === Number(numCrono); });
                if (!g) return;
                window.calendarioSoloCronograma = { numCronograma: g.numCronograma, data: g.detalles || [], codPrograma: g.codPrograma || '', nomPrograma: g.nomPrograma || '' };
                window.calGranjasVisibles = null;
                calendarData = g.detalles || [];
                window.calendarDataLeyendaAnio = g.detalles || [];
                var d = new Date();
                anioActualCal = d.getFullYear();
                mesActualCal = d.getMonth();
                calFechaElegida = null;
                codProgramaFiltroCal = 'num_' + g.numCronograma;
                renderCalendario('num_' + g.numCronograma);
                var tituloCal = document.querySelector('#modalCalendario h3');
                if (tituloCal) tituloCal.textContent = 'Calendario: ' + (g.codPrograma || '') + (g.nomPrograma ? ' — ' + g.nomPrograma : '');
                document.getElementById('modalCalendario').classList.remove('hidden');
                notificarParentModal(true);
                return;
            }
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
            window._detallesListadoFilas = detalles;
            window._detallesListadoSearch = '';
            window._detallesListadoPageSize = 20;
            var selSize = document.getElementById('detallesPageSize');
            if (selSize) selSize.value = '20';
            var inpSearch = document.getElementById('detallesSearch');
            if (inpSearch) inpSearch.value = '';
            document.querySelectorAll('#modalDetalles .tab-btn').forEach(function(b) { b.classList.remove('active'); });
            document.getElementById('tabPanelGranjas').classList.add('active');
            document.getElementById('tabPanelPrograma').classList.remove('active');
            var firstTab = document.querySelector('#modalDetalles .tab-btn[data-tab="granjas"]');
            if (firstTab) firstTab.classList.add('active');
            cargarTabProgramaEnDetalles(g.codPrograma);
            renderDetallesListadoPage(1);
            document.getElementById('modalDetalles').classList.remove('hidden');
        });

        var detallesPageSizeEl = document.getElementById('detallesPageSize');
        if (detallesPageSizeEl) detallesPageSizeEl.addEventListener('change', function() {
            window._detallesListadoPageSize = parseInt(this.value, 10) || 20;
            renderDetallesListadoPage(1);
        });
        var detallesSearchEl = document.getElementById('detallesSearch');
        if (detallesSearchEl) detallesSearchEl.addEventListener('input', function() {
            window._detallesListadoSearch = this.value;
            renderDetallesListadoPage(1);
        });

        function eventosPorDiaDesdeListado() {
            var map = {};
            var filtro = codProgramaFiltroCal;
            var data = calendarData.length ? calendarData : listadoData;
            data.forEach(function(r) {
                if (filtro) {
                    if (String(filtro).indexOf('num_') === 0) { var n = parseInt(String(filtro).replace('num_', ''), 10); if (Number(r.numCronograma) !== n) return; }
                    else if ((r.codPrograma || '') !== filtro) return;
                    if (window.calGranjasVisibles && window.calGranjasVisibles.size > 0) {
                        var gKey = (r.granja || '').toString().trim();
                        if (!window.calGranjasVisibles.has(gKey)) return;
                    }
                } else if (window.calCronogramasVisibles && window.calCronogramasVisibles.size > 0) {
                    if (!window.calCronogramasVisibles.has(Number(r.numCronograma))) return;
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
                    fechaEjecucion: r.fechaEjecucion || ''
                });
            });
            return map;
        }

        function granjasUnicasParaLeyenda() {
            var seen = {};
            var out = [];
            var filtro = codProgramaFiltroCal;
            var data = calendarData.length ? calendarData : listadoData;
            data.forEach(function(r) {
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

        function cronogramasUnicosParaLeyenda(datos) {
            var data = datos && datos.length ? datos : (calendarData.length ? calendarData : listadoData);
            var seen = {};
            var out = [];
            data.forEach(function(r) {
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

        function filterDetallesListadoPorBusqueda(filas, q) {
            if (!filas || !filas.length) return [];
            var term = (q || '').toString().trim().toLowerCase();
            if (term === '') return filas;
            return filas.filter(function(r) {
                var txt = [r.codPrograma, r.granja, r.nomGranja, r.campania, r.galpon, r.edad, fechaDDMMYYYY(r.fechaCarga), fechaDDMMYYYY(r.fechaEjecucion)].join(' ').toLowerCase();
                return txt.indexOf(term) !== -1;
            });
        }
        function renderDetallesListadoPage(page) {
            var filasCompletas = window._detallesListadoFilas || [];
            var searchQ = (window._detallesListadoSearch || '').toString().trim();
            var filas = filterDetallesListadoPorBusqueda(filasCompletas, searchQ);
            var pageSize = Math.max(1, parseInt(window._detallesListadoPageSize, 10) || 20);
            var total = filas.length;
            var totalPag = Math.max(1, Math.ceil(total / pageSize));
            page = Math.max(1, Math.min(page, totalPag));
            window._detallesListadoPage = page;
            var start = (page - 1) * pageSize;
            var end = Math.min(start + pageSize, total);
            var pageFilas = filas.slice(start, end);
            var tbody = document.getElementById('detallesLista');
            if (!tbody) return;
            tbody.innerHTML = '';
            pageFilas.forEach(function(x, i) {
                var num = i + 1;
                var tr = document.createElement('tr');
                tr.className = 'border-b border-gray-200';
                tr.innerHTML = '<td class="px-3 py-2">' + num + '</td>' +
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
            var pagEl = document.getElementById('detallesToolbarBottom');
            if (pagEl) {
                var desde = total === 0 ? 0 : start + 1;
                var infoText = 'Mostrando ' + desde + ' a ' + end + ' de ' + total + ' registros';
                var prevDisabled = page <= 1 ? ' disabled' : '';
                var nextDisabled = page >= totalPag ? ' disabled' : '';
                var prevClass = page <= 1 ? ' opacity-50 cursor-not-allowed' : ' hover:bg-gray-100';
                var nextClass = page >= totalPag ? ' opacity-50 cursor-not-allowed' : ' hover:bg-gray-100';
                pagEl.innerHTML = '<span class="text-sm text-gray-600">' + infoText + '</span><div class="paginacion-controles"><button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm detalles-prev' + prevClass + '"' + prevDisabled + '>Anterior</button><span class="px-2 text-sm text-gray-600">Pág. ' + page + ' de ' + totalPag + '</span><button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm detalles-next' + nextClass + '"' + nextDisabled + '>Siguiente</button></div>';
                pagEl.querySelector('.detalles-prev').addEventListener('click', function() { if (page > 1) renderDetallesListadoPage(page - 1); });
                pagEl.querySelector('.detalles-next').addEventListener('click', function() { if (page < totalPag) renderDetallesListadoPage(page + 1); });
            }
        }

        var calEventosGlobal = [];
        function renderCalendario(codProgramaFiltro) {
            codProgramaFiltroCal = codProgramaFiltro || null;
            var esVistaTodosCronogramas = (codProgramaFiltroCal === null);
            var cronogramasLeyenda = esVistaTodosCronogramas ? cronogramasUnicosParaLeyenda(window.calendarDataLeyendaAnio || calendarData) : [];
            if (esVistaTodosCronogramas && (!window.calCronogramasVisibles || window.calCronogramasVisibles.size === 0) && cronogramasLeyenda.length > 0) {
                window.calCronogramasVisibles = new Set(cronogramasLeyenda.map(function(c) { return c.numCronograma; }));
            }
            var granjasLeyenda = esVistaTodosCronogramas ? [] : granjasUnicasParaLeyenda();
            if (!esVistaTodosCronogramas && (!window.calGranjasVisibles || window.calGranjasVisibles.size === 0) && granjasLeyenda.length > 0) {
                window.calGranjasVisibles = new Set(granjasLeyenda.map(function(g) { return (g.granja || '').toString().trim(); }));
            }
            var eventosPorDia = eventosPorDiaDesdeListado();
            calEventosGlobal = [];
            var colorByNumCronograma = {};
            var colorByGranja = {};
            cronogramasLeyenda.forEach(function(c, i) { colorByNumCronograma[c.numCronograma] = coloresGranja[i % coloresGranja.length]; });
            granjasLeyenda.forEach(function(g, i) { colorByGranja[(g.granja || '').toString().trim()] = coloresGranja[i % coloresGranja.length]; });
            var mesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            document.getElementById('calMesAnio').textContent = mesNombres[mesActualCal] + ' ' + anioActualCal;
            var calIrFechaEl = document.getElementById('calIrFecha');
            if (calIrFechaEl) calIrFechaEl.value = anioActualCal + '-' + (mesActualCal + 1 < 10 ? '0' : '') + (mesActualCal + 1) + '-01';

            function pad2(n) { return (n < 10 ? '0' : '') + n; }
            var hoy = new Date();
            var hoyStr = hoy.getFullYear() + '-' + pad2(hoy.getMonth() + 1) + '-' + pad2(hoy.getDate());
            var primerdia = new Date(anioActualCal, mesActualCal, 1);
            var inicioSem = primerdia.getDay();
            var ultimoDiaMes = new Date(anioActualCal, mesActualCal + 1, 0).getDate();
            var ultimoDate = new Date(anioActualCal, mesActualCal, ultimoDiaMes);
            var primerFechaCal = new Date(anioActualCal, mesActualCal, 1 - inicioSem);
            var ultimaFechaCal = new Date(anioActualCal, mesActualCal, ultimoDiaMes + (6 - ultimoDate.getDay()));
            var diasGrid = [];
            var fecha = new Date(primerFechaCal.getTime());
            while (fecha.getTime() <= ultimaFechaCal.getTime()) {
                var esEsteMes = fecha.getMonth() === mesActualCal;
                var dia = fecha.getDate();
                var key = fecha.getFullYear() + '-' + pad2(fecha.getMonth() + 1) + '-' + pad2(dia);
                var eventos = eventosPorDia[key] || [];
                diasGrid.push({ dia: dia, esEsteMes: esEsteMes, key: key, eventos: eventos });
                fecha.setDate(fecha.getDate() + 1);
            }

            var html = '<div class="cal-dia-header">Dom</div><div class="cal-dia-header">Lun</div><div class="cal-dia-header">Mar</div><div class="cal-dia-header">Mié</div><div class="cal-dia-header">Jue</div><div class="cal-dia-header">Vie</div><div class="cal-dia-header">Sáb</div>';
            diasGrid.forEach(function(cel) {
                var cls = 'cal-dia cal-dia-celda';
                if (!cel.esEsteMes) cls += ' otro-mes';
                if (cel.key === hoyStr) cls += ' cal-dia-hoy';
                if (calFechaElegida && cel.key === calFechaElegida && cel.key !== hoyStr) cls += ' cal-dia-elegida';
                html += '<div class="' + cls + '" data-key="' + esc(cel.key) + '" role="button" tabindex="0" title="Seleccionar día">';
                html += '<div class="cal-dia-num">' + cel.dia + '</div>';
                (cel.eventos || []).forEach(function(ev) {
                    var idx = calEventosGlobal.length;
                    calEventosGlobal.push(ev);
                    var color = esVistaTodosCronogramas ? (colorByNumCronograma[ev.numCronograma] || colorCronograma(ev.numCronograma)) : (colorByGranja[(ev.granja || '').toString().trim()] || colorGranja(ev.granja));
                    var cod = (ev.codPrograma || '').toString().trim();
                    var nom = (ev.nomPrograma || '').toString().trim();
                    var granja = (ev.nomGranja || ev.granja || '').toString().trim();
                    var lineas = [cod, nom, granja].filter(function(s) { return s !== ''; });
                    var texto = lineas.join('\n') || '—';
                    var textoEsc = esc(texto);
                    html += '<div class="cal-evento cal-evento-click" data-evidx="' + idx + '" style="border-left: 3px solid ' + color + '">';
                    html += '<span class="cal-evento-dot" style="background:' + color + '"></span>';
                    html += '<span class="cal-evento-texto" title="' + textoEsc + '">' + textoEsc.replace(/\n/g, '<br>') + '</span>';
                    html += '</div>';
                });
                html += '</div>';
            });
            document.getElementById('calGrid').innerHTML = html;

            document.querySelectorAll('#calGrid .cal-dia-celda').forEach(function(el) {
                el.addEventListener('click', function(ev) {
                    if (ev.target.closest('.cal-evento-click')) return;
                    var key = this.getAttribute('data-key');
                    if (!key) return;
                    calFechaElegida = key;
                    renderCalendario(codProgramaFiltroCal);
                });
            });

            var leyendaHtml = '';
            if (esVistaTodosCronogramas) {
                leyendaHtml = '<div class="leyenda-titulo font-semibold text-gray-700 mb-1">Cronogramas</div>';
                cronogramasLeyenda.forEach(function(c, i) {
                    var color = colorByNumCronograma[c.numCronograma] || coloresGranja[i % coloresGranja.length];
                    var checked = window.calCronogramasVisibles && window.calCronogramasVisibles.has(c.numCronograma);
                    leyendaHtml += '<label class="leyenda-item"><input type="checkbox" class="leyenda-chk-crono" data-numcronograma="' + c.numCronograma + '" ' + (checked ? 'checked' : '') + '><span class="leyenda-color" style="background:' + color + '"></span><span>' + esc(c.etiqueta) + '</span></label>';
                });
            } else {
                leyendaHtml = '<div class="leyenda-titulo font-semibold text-gray-700 mb-1">Granjas</div>';
                granjasLeyenda.forEach(function(g, i) {
                    var gKey = (g.granja || '').toString().trim();
                    var color = colorByGranja[gKey] || coloresGranja[i % coloresGranja.length];
                    var checked = window.calGranjasVisibles && window.calGranjasVisibles.has(gKey);
                    leyendaHtml += '<label class="leyenda-item"><input type="checkbox" class="leyenda-chk-granja" data-granja="' + esc(gKey) + '" ' + (checked ? 'checked' : '') + '><span class="leyenda-color" style="background:' + color + '"></span><span>' + esc(g.nomGranja || g.granja) + '</span></label>';
                });
            }
            document.getElementById('calLeyenda').innerHTML = leyendaHtml || '<span class="text-gray-500">Sin eventos en el cronograma</span>';
            document.querySelectorAll('#calLeyenda .leyenda-chk-granja').forEach(function(chk) {
                chk.addEventListener('change', function() {
                    var gKey = (this.getAttribute('data-granja') || '').toString().trim();
                    if (!window.calGranjasVisibles) return;
                    if (this.checked) window.calGranjasVisibles.add(gKey); else window.calGranjasVisibles.delete(gKey);
                    renderCalendario(codProgramaFiltroCal);
                });
            });
            document.querySelectorAll('#calLeyenda .leyenda-chk-crono').forEach(function(chk) {
                chk.addEventListener('change', function() {
                    var num = parseInt(this.getAttribute('data-numcronograma'), 10);
                    if (!window.calCronogramasVisibles) return;
                    if (this.checked) window.calCronogramasVisibles.add(num); else window.calCronogramasVisibles.delete(num);
                    renderCalendario(codProgramaFiltroCal);
                });
            });

            document.querySelectorAll('.cal-evento-click').forEach(function(el) {
                el.addEventListener('click', function() {
                    var idx = parseInt(this.getAttribute('data-evidx'), 10);
                    var ev = calEventosGlobal[idx];
                    if (!ev) return;
                    window.detEvCodPrograma = ev.codPrograma || '';
                    var granjaTexto = [ev.granja || '', ev.nomGranja || ''].filter(Boolean).join(' — ') || '—';
                    document.getElementById('detEvGranja').textContent = granjaTexto;
                    document.getElementById('detEvCampania').textContent = ev.campania || '—';
                    document.getElementById('detEvGalpon').textContent = ev.galpon || '—';
                    document.getElementById('detEvEdad').textContent = ev.edad !== undefined && ev.edad !== null && ev.edad !== '' ? ev.edad : '—';
                    document.getElementById('detEvFecCarga').textContent = fechaDDMMYYYY(ev.fechaCarga) || '—';
                    document.getElementById('detEvFecEjec').textContent = fechaDDMMYYYY(ev.fechaEjecucion) || '—';
                    var cabFila = document.getElementById('detEvProgramaCabFila');
                    var cabDet = document.getElementById('detEvProgramaCabDet');
                    cabFila.classList.add('hidden');
                    cabFila.innerHTML = '';
                    cabDet.classList.add('hidden');
                    cabDet.innerHTML = '';
                    document.getElementById('btnDetEvVerMas').innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Ver programa';
                    document.getElementById('modalDetalleEvento').classList.remove('hidden');
                    notificarParentModal(true);
                });
            });
        }

        function cargarCalendarioMes(anio, mes) {
            if (window.calendarioSoloCronograma) {
                calendarData = window.calendarioSoloCronograma.data || [];
                window.calendarDataLeyendaAnio = window.calendarioSoloCronograma.data || [];
                renderCalendario('num_' + window.calendarioSoloCronograma.numCronograma);
                return;
            }
            var mesStr = (mes + 1) < 10 ? '0' + (mes + 1) : '' + (mes + 1);
            var mesEjecucion = anio + '-' + mesStr;
            var codTipo = document.getElementById('filtroCodTipo').value || '';
            var url = 'listar_cronograma.php?mesEjecucion=' + encodeURIComponent(mesEjecucion) + (codTipo ? '&codTipo=' + encodeURIComponent(codTipo) : '');
            fetch(url).then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) return;
                calendarData = res.data || [];
                var urlAnio = 'listar_cronograma.php?periodoTipo=ENTRE_FECHAS&fechaInicio=' + anio + '-01-01&fechaFin=' + anio + '-12-31' + (codTipo ? '&codTipo=' + encodeURIComponent(codTipo) : '');
                fetch(urlAnio).then(function(r2) { return r2.json(); }).then(function(res2) {
                    window.calendarDataLeyendaAnio = (res2.success && res2.data) ? res2.data : calendarData;
                    renderCalendario(null);
                }).catch(function() {
                    window.calendarDataLeyendaAnio = calendarData;
                    renderCalendario(null);
                });
            }).catch(function() {
                calendarData = [];
                window.calendarDataLeyendaAnio = [];
                renderCalendario(null);
            });
        }

        document.getElementById('modalDetalleEvento').addEventListener('click', function(e) {
            if (e.target === this) { this.classList.add('hidden'); notificarParentModal(false); }
        });
        document.getElementById('modalCalendario').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                notificarParentModal(false);
                var tituloCal = document.querySelector('#modalCalendario h3');
                if (tituloCal) tituloCal.textContent = 'Calendario';
            }
        });
        document.getElementById('btnAbrirCalendario').addEventListener('click', function() {
            window.calendarioSoloCronograma = null;
            window.calCronogramasVisibles = null;
            var tituloCal = document.querySelector('#modalCalendario h3');
            if (tituloCal) tituloCal.textContent = 'Calendario';
            var d = new Date();
            anioActualCal = d.getFullYear();
            mesActualCal = d.getMonth();
            calFechaElegida = null;
            cargarCalendarioMes(anioActualCal, mesActualCal);
            document.getElementById('modalCalendario').classList.remove('hidden');
            notificarParentModal(true);
        });
        document.getElementById('calPrevMes').addEventListener('click', function() {
            mesActualCal--;
            if (mesActualCal < 0) { mesActualCal = 11; anioActualCal--; }
            cargarCalendarioMes(anioActualCal, mesActualCal);
        });
        document.getElementById('calNextMes').addEventListener('click', function() {
            mesActualCal++;
            if (mesActualCal > 11) { mesActualCal = 0; anioActualCal++; }
            cargarCalendarioMes(anioActualCal, mesActualCal);
        });
        document.getElementById('calIrFecha').addEventListener('change', function() {
            var val = this.value;
            if (!val) return;
            var parts = val.split('-');
            if (parts.length >= 2) {
                anioActualCal = parseInt(parts[0], 10);
                mesActualCal = parseInt(parts[1], 10) - 1;
                calFechaElegida = val;
                cargarCalendarioMes(anioActualCal, mesActualCal);
            }
        });

        document.getElementById('btnDetEvVerMas').addEventListener('click', function() {
            var cabFilaEl = document.getElementById('detEvProgramaCabFila');
            var box = document.getElementById('detEvProgramaCabDet');
            if (box.classList.contains('hidden') && cabFilaEl.classList.contains('hidden')) {
                var cod = window.detEvCodPrograma || '';
                if (!cod) { box.innerHTML = '<p class="text-gray-500">No hay programa asociado.</p>'; box.classList.remove('hidden'); return; }
                cabFilaEl.innerHTML = '<p class="text-gray-500">Cargando...</p>';
                cabFilaEl.classList.remove('hidden');
                box.innerHTML = '';
                box.classList.add('hidden');
                var btnVerMas = document.getElementById('btnDetEvVerMas');
                fetch('../programas/get_programa_cab_detalle.php?codigo=' + encodeURIComponent(cod)).then(function(r) { return r.json(); }).then(function(res) {
                    if (!res.success) {
                        cabFilaEl.innerHTML = '<p class="text-red-600">' + esc(res.message || 'Error al cargar programa.') + '</p>';
                        return;
                    }
                    var cab = res.cab || {};
                    var det = res.detalles || [];
                    var sigla = (res.sigla || 'PL').toUpperCase();
                    if (sigla === 'NEC') sigla = 'NC';
                    var cols = columnasPorSiglaReporte[sigla] || columnasPorSiglaReporte['PL'];
                    var colsSinNum = cols.filter(function(k) { return k !== 'num'; });
                    if (colsSinNum.indexOf('edad') !== -1) {
                        colsSinNum = colsSinNum.filter(function(k) { return k !== 'edad'; });
                        colsSinNum.push('edad');
                    }
                    var cabFilaHtml = '<div class="campo"><dt>Código</dt><dd>' + esc(cab.codigo) + '</dd></div>';
                    cabFilaHtml += '<div class="campo"><dt>Tipo</dt><dd>' + esc(cab.nomTipo || '') + '</dd></div>';
                    cabFilaHtml += '<div class="campo"><dt>Nombre</dt><dd>' + esc(cab.nombre) + '</dd></div>';
                    cabFilaHtml += '<div class="campo descripcion"><dt>Descripción</dt><dd>' + (cab.descripcion ? esc(cab.descripcion) : '—') + '</dd></div>';
                    cabFilaEl.innerHTML = cabFilaHtml;
                    if (det.length === 0) {
                        box.innerHTML = '<p class="text-gray-500 text-sm">Sin registros en el detalle del programa.</p>';
                    } else {
                        var detAgrupados = agruparDetallesPorEdad(det, colsSinNum);
                        var thCells = colsSinNum.map(function(k) { return '<th class="px-3 py-2 text-left bg-blue-600 text-white text-xs">' + (labelsReportePrograma[k] || k) + '</th>'; }).join('');
                        var tableHtml = '<table class="w-full border-collapse text-sm"><thead><tr>' + thCells + '</tr></thead><tbody>';
                        detAgrupados.forEach(function(d) {
                            tableHtml += '<tr class="border-b border-gray-200">';
                            colsSinNum.forEach(function(k) {
                                var val = valorCeldaDetallePrograma(k, d);
                                tableHtml += '<td class="px-3 py-2"' + (k === 'descripcion_vacuna' ? ' style="white-space:pre-wrap;"' : '') + '>' + val + '</td>';
                            });
                            tableHtml += '</tr>';
                        });
                        tableHtml += '</tbody></table>';
                        box.innerHTML = tableHtml;
                    }
                    box.classList.remove('hidden');
                    if (btnVerMas) btnVerMas.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> Ocultar programa';
                }).catch(function() {
                    cabFilaEl.innerHTML = '<p class="text-red-600">Error al cargar.</p>';
                });
            } else {
                cabFilaEl.classList.add('hidden');
                cabFilaEl.innerHTML = '';
                box.classList.add('hidden');
                box.innerHTML = '';
                this.innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Ver programa';
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
            document.getElementById('periodoTipo').value = 'TODOS';
            var d = new Date();
            document.getElementById('fechaUnica').value = d.toISOString().slice(0, 10);
            document.getElementById('fechaInicio').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-01';
            document.getElementById('fechaFin').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate()).padStart(2, '0');
            document.getElementById('mesUnico').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            document.getElementById('mesInicio').value = d.getFullYear() + '-01';
            document.getElementById('mesFin').value = d.getFullYear() + '-12';
            document.getElementById('filtroCodTipo').value = '';
            aplicarVisibilidadPeriodoCronograma();
            cargarListado();
        });

        document.getElementById('btnReportePdfFiltrado').addEventListener('click', function() {
            var qs = paramsFiltro();
            window.open('generar_reporte_cronograma_filtrado_pdf.php' + (qs ? '?' + qs : ''), '_blank');
        });

        window._cronoIconosPage = 0;
        window._cronoIconosPageSize = 10;
        window._cronoIconosSearch = '';

        function renderizarTarjetasCronograma() {
            var grupos = window.gruposCronograma || [];
            var q = (window._cronoIconosSearch || '').toString().trim().toLowerCase();
            var filtrado = q ? grupos.filter(function(g) {
                return (g.codPrograma || '').toLowerCase().indexOf(q) >= 0 ||
                    (g.nomPrograma || '').toLowerCase().indexOf(q) >= 0 ||
                    (fechaDDMMYYYY(g.fechaProg) || '').toLowerCase().indexOf(q) >= 0;
            }) : grupos.slice(0);
            var pageSize = Math.max(1, parseInt(window._cronoIconosPageSize, 10) || 10);
            var total = filtrado.length;
            var totalPag = Math.max(1, Math.ceil(total / pageSize));
            var page = Math.max(0, Math.min(window._cronoIconosPage, totalPag - 1));
            window._cronoIconosPage = page;
            var start = page * pageSize;
            var slice = filtrado.slice(start, start + pageSize);

            var cont = document.getElementById('cardsContainerCrono');
            if (!cont) return;
            cont.innerHTML = '';
            slice.forEach(function(g, idx) {
                var num = start + idx + 1;
                var urlPdf = 'generar_reporte_cronograma_pdf.php?numCronograma=' + encodeURIComponent(g.numCronograma);
                var dataKey = 'data-numcronograma="' + g.numCronograma + '"';
                var card = document.createElement('div');
                card.className = 'card-item';
                card.innerHTML =
                    '<div class="card-numero-row">#' + num + '</div>' +
                    '<div class="card-contenido">' +
                    '<div class="card-codigo">' + esc(g.codPrograma || '') + '</div>' +
                    '<div class="card-campos">' +
                    '<div class="card-row"><span class="label">Programa:</span> ' + esc(g.nomPrograma || '') + '</div>' +
                    '<div class="card-row"><span class="label">Fecha:</span> ' + esc(fechaDDMMYYYY(g.fechaProg)) + '</div>' +
                    '<div class="card-row"><span class="label">Registros:</span> ' + (g.detalles ? g.detalles.length : 0) + '</div>' +
                    '</div>' +
                    '<div class="card-acciones">' +
                    '<button type="button" class="btn-detalles px-2 py-1 text-blue-600 hover:bg-blue-50 rounded text-sm" ' + dataKey + ' title="Ver detalles"><i class="fas fa-list mr-1"></i> Ver</button>' +
                    '<button type="button" class="btn-cal btn-cal-fila px-2 py-1 text-green-600 hover:bg-green-50 rounded text-sm" ' + dataKey + ' title="Calendario"><i class="fas fa-calendar-alt"></i></button>' +
                    '<a href="' + urlPdf + '" target="_blank" class="inline-flex items-center px-2 py-1 text-red-600 hover:bg-red-50 rounded text-sm" title="PDF"><i class="fas fa-file-pdf"></i></a>' +
                    '</div></div>';
                cont.appendChild(card);
            });
            cont.querySelectorAll('.btn-detalles').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var numCrono = btn.getAttribute('data-numcronograma');
                    var g = (window.gruposCronograma || []).find(function(x) { return Number(x.numCronograma) === Number(numCrono); });
                    if (!g) return;
                    var detalles = g.detalles || [];
                    document.getElementById('detallesCodPrograma').textContent = (g.codPrograma || '') + ' — ' + (g.nomPrograma || '');
                    document.getElementById('detallesTotal').textContent = detalles.length;
                    window._detallesListadoFilas = detalles;
                    window._detallesListadoSearch = '';
                    window._detallesListadoPageSize = 20;
                    var selSize = document.getElementById('detallesPageSize');
                    if (selSize) selSize.value = '20';
                    var inpSearch = document.getElementById('detallesSearch');
                    if (inpSearch) inpSearch.value = '';
                    document.querySelectorAll('#modalDetalles .tab-btn').forEach(function(b) { b.classList.remove('active'); });
                    document.getElementById('tabPanelGranjas').classList.add('active');
                    document.getElementById('tabPanelPrograma').classList.remove('active');
                    var firstTab = document.querySelector('#modalDetalles .tab-btn[data-tab="granjas"]');
                    if (firstTab) firstTab.classList.add('active');
                    cargarTabProgramaEnDetalles(g.codPrograma);
                    renderDetallesListadoPage(1);
                    document.getElementById('modalDetalles').classList.remove('hidden');
                });
            });
            cont.querySelectorAll('.btn-cal-fila').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var numCrono = btn.getAttribute('data-numcronograma');
                    var g = (window.gruposCronograma || []).find(function(x) { return Number(x.numCronograma) === Number(numCrono); });
                    if (!g) return;
                    window.calendarioSoloCronograma = { numCronograma: g.numCronograma, data: g.detalles || [], codPrograma: g.codPrograma || '', nomPrograma: g.nomPrograma || '' };
                    window.calGranjasVisibles = null;
                    calendarData = g.detalles || [];
                    window.calendarDataLeyendaAnio = g.detalles || [];
                    var d = new Date();
                    anioActualCal = d.getFullYear();
                    mesActualCal = d.getMonth();
                    calFechaElegida = null;
                    codProgramaFiltroCal = 'num_' + g.numCronograma;
                    renderCalendario('num_' + g.numCronograma);
                    var tituloCal = document.querySelector('#modalCalendario h3');
                    if (tituloCal) tituloCal.textContent = 'Calendario: ' + (g.codPrograma || '') + (g.nomPrograma ? ' — ' + g.nomPrograma : '');
                    document.getElementById('modalCalendario').classList.remove('hidden');
                    notificarParentModal(true);
                });
            });

            window._cronoTotalPag = totalPag;
            window.cronoIconosPageGo = function(p) {
                var totalP = window._cronoTotalPag || 1;
                var cur = window._cronoIconosPage || 0;
                var next = (p === 'prev') ? cur - 1 : (p === 'next') ? cur + 1 : (typeof p === 'number' ? p : cur);
                if (next < 0 || next >= totalP) return;
                window._cronoIconosPage = next;
                renderizarTarjetasCronograma();
            };
            var pagEl = document.getElementById('cardsPaginationCrono');
            if (pagEl && typeof buildPaginationIconos === 'function') {
                pagEl.innerHTML = buildPaginationIconos({ page: page, pages: totalPag, start: start, end: total === 0 ? 0 : Math.min(start + slice.length, total), recordsDisplay: total });
            } else if (pagEl) {
                pagEl.innerHTML = '<span class="dataTables_info">Mostrando ' + (total === 0 ? 0 : start + 1) + ' a ' + Math.min(start + slice.length, total) + ' de ' + total + ' registros</span>';
            }
        }

        function aplicarVistaCronograma(vista) {
            var w = document.getElementById('tablaCronogramaWrapper');
            if (!w) return;
            w.setAttribute('data-vista', vista);
            var esLista = (vista === 'lista');
            var listWrap = document.getElementById('viewListaCrono');
            var iconWrap = document.getElementById('viewTarjetasCrono');
            var cronoDt = document.getElementById('cronoDtControls');
            var cronoIconos = document.getElementById('cronoIconosControls');
            if (listWrap) { listWrap.classList.toggle('hidden', !esLista); listWrap.style.display = esLista ? 'block' : 'none'; }
            if (iconWrap) { iconWrap.classList.toggle('hidden', esLista); iconWrap.style.display = esLista ? 'none' : 'block'; }
            var btnLista = document.getElementById('btnViewListaCrono'); var btnIconos = document.getElementById('btnViewIconosCrono');
            if (btnLista) btnLista.classList.toggle('active', esLista);
            if (btnIconos) btnIconos.classList.toggle('active', !esLista);
            if (esLista) {
                if (cronoIconos && cronoDt) {
                    var filterEl = cronoIconos.querySelector('.dataTables_filter');
                    if (filterEl) { cronoIconos.removeChild(filterEl); cronoDt.appendChild(filterEl); }
                }
                if (cronoIconos) cronoIconos.style.display = 'none';
                if (cronoDt) cronoDt.style.display = '';
            } else {
                if (cronoDt && cronoIconos) {
                    var filterEl = cronoDt.querySelector('.dataTables_filter');
                    if (filterEl) { cronoDt.removeChild(filterEl); cronoIconos.appendChild(filterEl); }
                }
                if (cronoDt) cronoDt.style.display = 'none';
                if (cronoIconos) cronoIconos.style.display = '';
                var toolbarRow = cronoIconos.querySelector('.iconos-toolbar-row');
                if (!toolbarRow) {
                    var lengthOptions = [5, 10, 25, 50];
                    var len = window._cronoIconosPageSize;
                    var lengthSelect = '<label class="inline-flex items-center gap-2"><span>Mostrar</span><select class="cards-length-select">' +
                        lengthOptions.map(function(n) { return '<option value="' + n + '"' + (n === len ? ' selected' : '') + '>' + n + '</option>'; }).join('') + '</select><span>registros</span></label>';
                    toolbarRow = document.createElement('div');
                    toolbarRow.className = 'iconos-toolbar-row flex flex-wrap items-center gap-3';
                    toolbarRow.innerHTML = lengthSelect;
                    cronoIconos.insertBefore(toolbarRow, cronoIconos.firstChild);
                    if (cronoIconos.querySelector('.dataTables_filter')) toolbarRow.appendChild(cronoIconos.querySelector('.dataTables_filter'));
                    toolbarRow.querySelector('.cards-length-select').addEventListener('change', function() {
                        window._cronoIconosPageSize = parseInt(this.value, 10) || 10;
                        window._cronoIconosPage = 0;
                        renderizarTarjetasCronograma();
                    });
                }
                var searchInput = document.querySelector('#cronoIconosControls .dataTables_filter input');
                if (searchInput) {
                    window._cronoIconosSearch = searchInput.value || '';
                    searchInput.removeEventListener('input', window._cronoSearchHandler);
                    searchInput.removeEventListener('keyup', window._cronoSearchHandler);
                    window._cronoSearchHandler = function() { window._cronoIconosSearch = searchInput.value || ''; window._cronoIconosPage = 0; renderizarTarjetasCronograma(); };
                    searchInput.addEventListener('input', window._cronoSearchHandler);
                    searchInput.addEventListener('keyup', window._cronoSearchHandler);
                }
                renderizarTarjetasCronograma();
            }
        }
        document.getElementById('btnViewListaCrono').addEventListener('click', function() { aplicarVistaCronograma('lista'); });
        document.getElementById('btnViewIconosCrono').addEventListener('click', function() { aplicarVistaCronograma('iconos'); });

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

        (function initCalendario() {
            var hoy = new Date();
            mesActualCal = hoy.getMonth();
            anioActualCal = hoy.getFullYear();
        })();
    </script>
</body>
</html>
