<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) {
            window.top.location.href = "../../../login.php";
        } else {
            window.location.href = "../../../login.php";
        }
    </script>';
    exit();
}
include_once __DIR__ . '/../../../includes/datatables_lang_es.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programas - Listado</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>window.DATATABLES_LANG_ES = <?php echo $datatablesLangEs; ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            margin: 0 0.5rem;
        }
        .dataTables_wrapper .dataTables_filter input {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            margin-left: 0.5rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 1rem !important;
            margin: 0 0.25rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #1e40af !important;
            color: white !important;
            border: 1px solid #1e40af !important;
            font-weight: normal !important;
        }
        .bloque-detalle { display: block; }
        .select2-container .select2-selection--single { height: 38px; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 4px 10px; }
        .select2-container { width: 100% !important; }
        /* Modal: overlay oscurece toda la pantalla (sin recortes) */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-sizing: border-box;
        }
        .modal-overlay.hidden { display: none; }
        /* Modal Editar: máxima altura posible, poco padding */
        #modalEditarPrograma.modal-overlay {
            align-items: flex-start;
            padding: 0.5rem;
            overflow-y: auto;
        }
        .modal-box {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 95%;
            width: 100%;
            max-width: 1200px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        /* Overlay para buscar producto/proveedor: pantalla completa, no limitado por el modal */
        #overlayProductoProveedor {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            min-width: 100vw;
            min-height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-sizing: border-box;
        }
        #overlayProductoProveedor.hidden { display: none; }
        #overlayProductoProveedor .overlay-inner {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 560px;
            height: 85vh;
            max-height: 85vh;
            min-height: 400px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        #overlayProductoProveedor .overlay-inner iframe {
            width: 100%;
            flex: 1;
            min-height: 0;
            height: 100%;
            border: 0;
        }
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        #formPrograma {
            display: flex;
            flex-direction: column;
            min-height: 0;
            flex: 1;
        }
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
            min-height: 0;
        }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            flex-shrink: 0;
            background: #fff;
        }
        /* Modal Editar: grid para que header, cuerpo y footer (Cancelar + Guardar) siempre visibles; footer encima del iframe */
        #modalEditarProgramaBox.modal-editar-box {
            max-width: 98%;
            width: 1100px;
            max-height: 98vh;
            height: 98vh;
            overflow: hidden;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto;
            grid-template-areas: "modal-editar-head" "modal-editar-body" "modal-editar-foot";
        }
        #modalEditarProgramaBox .modal-header { grid-area: modal-editar-head; }
        .modal-editar-body {
            grid-area: modal-editar-body;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1;
        }
        .modal-editar-iframe {
            width: 100%;
            height: 100%;
            min-height: 200px;
            border: 0;
            display: block;
        }
        .modal-editar-footer {
            grid-area: modal-editar-foot;
            flex-shrink: 0;
            flex-wrap: wrap;
            min-height: 56px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            padding: 12px 1rem;
            position: relative;
            z-index: 10;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            overflow: visible;
        }
        .modal-editar-footer .modal-editar-btn {
            flex-shrink: 0;
            min-width: 100px;
        }
        #modalEditarBtnGuardar {
            display: inline-flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            background: #059669 !important;
            color: #ffffff !important;
            border: 1px solid #047857 !important;
        }
        #modalEditarBtnGuardar:hover {
            background: #047857 !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border">

        <!-- Card Filtros (estilo reportes) -->
        <div class="card-filtros-compacta mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <button type="button" id="btnToggleFiltrosProgramas"
                class="w-full flex items-center justify-between px-6 py-4 text-left font-medium text-gray-800 hover:bg-gray-50 transition">
                <span><i class="fas fa-filter mr-2 text-blue-600"></i> Filtros</span>
                <svg id="iconoFiltrosProgramas" class="w-5 h-5 text-gray-600 transition-transform duration-300 rotate-180"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="contenidoFiltrosProgramas" class="px-6 pb-6 pt-4">
                <div class="border-t border-gray-100 pt-4 mx-4">
                    <!-- Fila 1: Periodo -->
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
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-day mr-1 text-blue-600"></i>Fecha</label>
                            <input id="fechaUnica" type="date" value="<?php echo date('Y-m-d'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div id="periodoEntreFechas" class="hidden flex-shrink-0 flex items-end gap-2">
                            <div class="min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i>Desde</label><input id="fechaInicio" type="date" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></div>
                            <div class="min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i>Hasta</label><input id="fechaFin" type="date" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></div>
                        </div>
                        <div id="periodoPorMes" class="hidden flex-shrink-0 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar mr-1 text-blue-600"></i>Mes</label>
                            <input id="mesUnico" type="month" value="<?php echo date('Y-m'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div id="periodoEntreMeses" class="hidden flex-shrink-0 flex items-end gap-2">
                            <div class="min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i>Mes Inicio</label><input id="mesInicio" type="month" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></div>
                            <div class="min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i>Mes Fin</label><input id="mesFin" type="month" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></div>
                        </div>
                    </div>
                    <!-- Fila 2: Tipo, Zona, Despliegue --> 
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-layer-group mr-1 text-blue-600"></i>Tipo de programa</label>
                            <select id="filtroTipo" class="form-control w-full px-3 py-2 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-map-marker-alt mr-1 text-blue-600"></i>Zona</label>
                            <input type="text" id="filtroZona" class="form-control w-full px-3 py-2 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" list="filtroZonasList" placeholder="Ej: La Joya">
                            <datalist id="filtroZonasList">
                                <option value="Mollendo">
                                <option value="La Joya">
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-sitemap mr-1 text-blue-600"></i>Despliegue</label>
                            <input type="text" id="filtroDespliegue" class="form-control w-full px-3 py-2 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" list="filtroDesplieguesList" placeholder="Ej: GRS">
                            <datalist id="filtroDesplieguesList">
                                <option value="GRS">
                                <option value="Piloto">
                            </datalist>
                        </div>
                    </div>
                    <!-- Fila 2: botones -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" id="btnBuscarProgramas" class="btn-primary px-4 py-2">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <button type="button" id="btnLimpiarFiltrosProgramas" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 text-sm font-medium inline-flex items-center gap-2">
                            Limpiar
                        </button>
                        <button type="button" id="btnExportarPdf" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 text-sm font-medium inline-flex items-center gap-2" title="Exportar a PDF lo filtrado">
                            <i class="fas fa-file-pdf"></i> Reporte PDF
                        </button>
                    </div>
                </div>
                </div>
        </div>

        <!-- Card Tabla (mismo estilo lista/iconos que reportes) -->
        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden" id="tablaProgramasWrapper" data-vista-tabla-iconos data-vista="lista">
            <div class="p-4">
                <!-- Toolbar: Lista/Iconos y controles de tabla en la misma fila (como reportes) -->
                <div class="toolbar-vista-row flex flex-wrap items-center justify-between gap-3 mb-3" id="programasToolbarRow">
                    <div class="view-toggle-group flex items-center gap-2" id="viewToggleGroupProg">
                        <button type="button" class="view-toggle-btn active" id="btnViewListaProg" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                        <button type="button" class="view-toggle-btn" id="btnViewIconosProg" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                    </div>
                    <div id="progDtControls" class="flex flex-wrap items-center gap-3"></div>
                    <div id="progIconosControls" class="flex flex-wrap items-center gap-3" style="display: none;"></div>
                </div>
                <div class="view-lista-wrap" id="viewListaProg">
            <div class="table-wrapper table-wrapper-borde">
                <table id="tablaProgramas" class="data-table w-full text-sm config-table tabla-fixed-8col">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">N°</th>
                            <th class="px-4 py-3 text-left">Código</th>
                            <th class="px-4 py-3 text-left">Nombre</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Despliegue</th>
                            <th class="px-4 py-3 text-left">Fecha registro</th>
                            <th class="px-4 py-3 text-center">Detalles</th>
                            <th class="px-4 py-3 text-center">Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaProgramasBody"></tbody>
                </table>
            </div>
                </div>
                <div class="view-tarjetas-wrap hidden px-4 pb-4 overflow-x-hidden" id="viewTarjetasProg" style="display: none;">
                    <div id="cardsControlsTopProg" class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-gray-600 border-b border-gray-200 pb-3"></div>
                    <div id="cardsContainerProg" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                    <div id="cardsPaginationProg" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3" data-page-handler="progIconosPageGo"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Detalles del programa (san_fact_programa_det) -->
    <div id="modalDetallesPrograma" class="modal-overlay hidden" aria-hidden="true">
        <div class="modal-box" style="max-width: 90%; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;" role="dialog" aria-modal="true">
            <div class="modal-header flex-shrink-0">
                <h3 id="modalDetallesTitulo" class="text-lg font-semibold text-gray-800">Detalles del programa</h3>
                <button type="button" id="modalDetallesCerrar" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="modal-body overflow-auto flex-1 p-4">
                <div class="table-wrapper overflow-x-auto">
                    <table class="data-table w-full text-sm" id="tablaModalDetalles">
                        <thead class="bg-gray-50 border-b border-gray-200" id="modalDetallesThead"></thead>
                        <tbody id="modalDetallesBody"></tbody>
                    </table>
                </div>
                <p id="modalDetallesSinRegistros" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle.</p>
            </div>
        </div>
    </div>

    <!-- Modal Editar programa: cuerpo con iframe (formPrograma) y pie con botones siempre visible -->
    <div id="modalEditarPrograma" class="modal-overlay hidden" aria-hidden="true">
        <div id="modalEditarProgramaBox" class="modal-box rounded-2xl modal-editar-box" role="dialog" aria-modal="true">
            <div class="modal-header flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-800">Editar programa</h3>
                <button type="button" id="modalEditarProgramaCerrar" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="modal-body modal-editar-body flex-1 p-0 overflow-hidden flex flex-col">
                <iframe id="iframeEditarPrograma" src="about:blank" class="modal-editar-iframe" title="Formulario editar programa"></iframe>
            </div>
            <div class="modal-footer modal-editar-footer flex-shrink-0 flex items-center justify-end gap-2 px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <button type="button" id="modalEditarBtnCancelar" class="modal-editar-btn px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 text-sm font-medium">Cancelar</button>
                <button type="button" id="modalEditarBtnGuardar" class="modal-editar-btn px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium inline-flex items-center gap-1.5"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </div>
    </div>

    <!-- Overlay producto/proveedor: fuera del modal, oscurece toda la pantalla -->
    <div id="overlayProductoProveedor" class="hidden" aria-hidden="true">
        <div class="overlay-inner" onclick="event.stopPropagation()">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-shrink-0 bg-white">
                <h3 id="overlayProductoProveedorTitulo" class="text-base font-semibold text-gray-800">Buscar</h3>
                <button type="button" id="overlayProductoProveedorCerrar" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>
            <iframe id="iframeProductoProveedor" src="about:blank" title="Buscar"></iframe>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../../assets/js/pagination-iconos.js"></script>
    <script>
        window._detallesPorPrograma = {};
        window._cabPorPrograma = {};
        window._siglaPorPrograma = {};

        function toggleFiltrosProgramas() {
            var contenido = document.getElementById('contenidoFiltrosProgramas');
            var icono = document.getElementById('iconoFiltrosProgramas');
            if (!contenido || !icono) return;
            contenido.classList.toggle('hidden');
            icono.classList.toggle('rotate-180');
        }

        function getParametrosFiltro() {
            var elTipo = document.getElementById('filtroTipo');
            return {
                codTipo: (elTipo && elTipo.value !== undefined && elTipo.value !== null) ? String(elTipo.value).trim() : '',
                periodoTipo: (document.getElementById('periodoTipo') && document.getElementById('periodoTipo').value) || 'TODOS',
                fechaUnica: (document.getElementById('fechaUnica') && document.getElementById('fechaUnica').value) || '',
                fechaInicio: (document.getElementById('fechaInicio') && document.getElementById('fechaInicio').value) || '',
                fechaFin: (document.getElementById('fechaFin') && document.getElementById('fechaFin').value) || '',
                mesUnico: (document.getElementById('mesUnico') && document.getElementById('mesUnico').value) || '',
                mesInicio: (document.getElementById('mesInicio') && document.getElementById('mesInicio').value) || '',
                mesFin: (document.getElementById('mesFin') && document.getElementById('mesFin').value) || '',
                zona: (document.getElementById('filtroZona') && document.getElementById('filtroZona').value.trim()) || '',
                despliegue: (document.getElementById('filtroDespliegue') && document.getElementById('filtroDespliegue').value.trim()) || ''
            };
        }

        function formatearFecha(f) {
            if (!f) return '';
            var d = new Date(f);
            if (isNaN(d.getTime())) return f;
            return d.getDate().toString().padStart(2, '0') + '/' + (d.getMonth() + 1).toString().padStart(2, '0') + '/' + d.getFullYear();
        }

        function esc(s) {
            if (s === null || s === undefined) return '';
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        }
        function formatearDescripcionVacuna(s) {
            if (s === null || s === undefined) s = '';
            s = String(s).trim();
            if (!s) return '';
            if (/^Contra[\r\n]/.test(s) || (s.indexOf('\n') !== -1 && s.indexOf('- ') !== -1)) return s;
            var partes = s.split(',').map(function(x) { return x.trim(); }).filter(Boolean);
            if (partes.length === 0) return '';
            return 'Contra\n' + partes.map(function(p) { return '- ' + p; }).join('\n');
        }

        function cargarListado() {
            var params = getParametrosFiltro();
            var url = 'listar_programas_filtrado.php?codTipo=' + encodeURIComponent(params.codTipo) + '&periodoTipo=' + encodeURIComponent(params.periodoTipo) + '&fechaUnica=' + encodeURIComponent(params.fechaUnica) + '&fechaInicio=' + encodeURIComponent(params.fechaInicio) + '&fechaFin=' + encodeURIComponent(params.fechaFin) + '&mesUnico=' + encodeURIComponent(params.mesUnico) + '&mesInicio=' + encodeURIComponent(params.mesInicio) + '&mesFin=' + encodeURIComponent(params.mesFin) + '&zona=' + encodeURIComponent(params.zona) + '&despliegue=' + encodeURIComponent(params.despliegue) + '&_=' + Date.now();
            var tbody = document.getElementById('tablaProgramasBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            window._detallesPorPrograma = {};
            window._cabPorPrograma = {};
            window._siglaPorPrograma = {};
            fetch(url, { cache: 'no-store' })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    var data = res.data || [];
                    window._listadoProgramas = data;
                    if ($.fn.DataTable.isDataTable('#tablaProgramas')) {
                        $('#tablaProgramas').DataTable().destroy();
                    }
                    tbody.innerHTML = '';
                    data.forEach(function(item, idx) {
                        var cab = item.cab || {};
                        var codigo = cab.codigo || '';
                        window._detallesPorPrograma[codigo] = item.detalles || [];
                        window._cabPorPrograma[codigo] = cab;
                        window._siglaPorPrograma[codigo] = (item.sigla || 'PL').toUpperCase();
                        var tr = document.createElement('tr');
                        tr.className = 'border-b border-gray-200 hover:bg-gray-50';
                        var reporteUrl = 'generar_reporte_programa.php?codigo=' + encodeURIComponent(codigo);
                        tr.innerHTML = '<td class="px-4 py-3">' + (idx + 1) + '</td>' +
                            '<td class="px-4 py-3">' + esc(codigo) + '</td>' +
                            '<td class="px-4 py-3">' + esc(cab.nombre) + '</td>' +
                            '<td class="px-4 py-3">' + esc(cab.nomTipo) + '</td>' +
                            '<td class="px-4 py-3">' + esc(cab.despliegue) + '</td>' +
                            '<td class="px-4 py-3">' + formatearFecha(cab.fechaHoraRegistro) + '</td>' +
                            '<td class="px-4 py-3 text-center"><button type="button" class="btn-detalles-programa px-3 py-1.5 text-blue-600 hover:bg-blue-50 rounded-lg text-sm font-medium inline-flex items-center gap-2" data-codigo="' + (codigo || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;') + '" title="Ver"><i class="fas fa-eye"></i> Ver</button></td>' +
                            '<td class="px-4 py-3 text-center"><div class="flex items-center justify-center gap-3">' +
                            '<a class="text-red-600 hover:text-red-800" title="Ver reporte PDF" href="' + reporteUrl + '" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i></a>' +
                            '<button type="button" class="btn-editar-programa text-indigo-600 hover:text-indigo-800" title="Editar" data-codigo="' + (codigo || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;') + '"><i class="fa-solid fa-edit"></i></button>' +
                            '<button type="button" class="btn-eliminar-programa text-rose-600 hover:text-rose-800" title="Eliminar" data-codigo="' + (codigo || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;') + '"><i class="fa-solid fa-trash"></i></button>' +
                            '</div></td>';
                        tbody.appendChild(tr);
                    });
                    tbody.querySelectorAll('.btn-detalles-programa').forEach(function(btn) {
                        btn.addEventListener('click', function() { abrirModalDetalles(this.getAttribute('data-codigo')); });
                    });
                    tbody.querySelectorAll('.btn-editar-programa').forEach(function(btn) {
                        btn.addEventListener('click', function() { editarPrograma(this.getAttribute('data-codigo')); });
                    });
                    tbody.querySelectorAll('.btn-eliminar-programa').forEach(function(btn) {
                        btn.addEventListener('click', function() { eliminarPrograma(this.getAttribute('data-codigo')); });
                    });
                    $('#tablaProgramas').DataTable({
                        language: window.DATATABLES_LANG_ES || {},
                        pageLength: 20,
                        lengthMenu: [[20, 25, 50, 100], [20, 25, 50, 100]],
                        order: [[5, 'desc']],
                        dom: '<"dt-top-row"<"flex items-center gap-6" l><"flex items-center gap-2" f>>rt<"dt-bottom-row"<"text-sm text-gray-600" i><"text-sm text-gray-600" p>>',
                        initComplete: function() {
                            var wrapper = $('#tablaProgramas').closest('.dataTables_wrapper');
                            var $length = wrapper.find('.dataTables_length').first();
                            var $filter = wrapper.find('.dataTables_filter').first();
                            var $controls = $('#progDtControls');
                            if ($controls.length && $length.length && $filter.length) {
                                $controls.append($length, $filter);
                            }
                        }
                    });
                })
                .catch(function() {});
        }

        var columnasPorSiglaReporte = {
            'NC': ['num', 'ubicacion', 'edad'],
            'PL': ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'edad'],
            'GR': ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'edad'],
            'MC': ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'area_galpon', 'cantidad_por_galpon', 'unidadDosis', 'edad'],
            'LD': ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'unidadDosis', 'edad'],
            'CP': ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'unidadDosis', 'edad']
        };
        var labelsReporte = {
            num: '#', ubicacion: 'Ubicación', producto: 'Producto', proveedor: 'Proveedor', unidad: 'Unidad',
            dosis: 'Dosis', descripcion_vacuna: 'Descripcion', numeroFrascos: 'Nº frascos', edad: 'Edad de aplicación',
            unidadDosis: 'Unid. dosis', area_galpon: 'Área galpón', cantidad_por_galpon: 'Cant. por galpón'
        };
        function valorCeldaDetalle(k, d) {
            if (k === 'num') return '';
            if (k === 'ubicacion') return esc(d.ubicacion || '');
            if (k === 'producto') return esc(d.nomProducto || d.codProducto || '');
            if (k === 'proveedor') return esc(d.nomProveedor || '');
            if (k === 'unidad') return esc(d.unidades || '');
            if (k === 'dosis') return esc(d.dosis || '');
            if (k === 'descripcion_vacuna') return esc(formatearDescripcionVacuna(d.descripcionVacuna));
            if (k === 'numeroFrascos') return esc(d.numeroFrascos || '');
            if (k === 'edad') return (d.edad !== null && d.edad !== undefined && d.edad !== '' ? d.edad : '');
            if (k === 'unidadDosis') return esc(d.unidadDosis || '');
            if (k === 'area_galpon') return (d.areaGalpon !== null && d.areaGalpon !== undefined && d.areaGalpon !== '' ? d.areaGalpon : '');
            if (k === 'cantidad_por_galpon') return (d.cantidadPorGalpon !== null && d.cantidadPorGalpon !== undefined && d.cantidadPorGalpon !== '' ? d.cantidadPorGalpon : '');
            return '';
        }
        function valorClaveDetalle(k, d) {
            if (k === 'edad' || k === 'num') return '';
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
        function abrirModalDetalles(codigo) {
            if (!codigo) return;
            var detalles = window._detallesPorPrograma[codigo];
            if (!detalles) detalles = [];
            var cab = window._cabPorPrograma[codigo] || {};
            var sigla = (window._siglaPorPrograma[codigo] || 'PL').toUpperCase();
            if (sigla === 'NEC') sigla = 'NC';
            var cols = columnasPorSiglaReporte[sigla] || columnasPorSiglaReporte['PL'];
            var colsSinNum = cols.filter(function(k) { return k !== 'num'; });
            if (colsSinNum.indexOf('edad') !== -1) {
                colsSinNum = colsSinNum.filter(function(k) { return k !== 'edad'; });
                colsSinNum.push('edad');
            }
            var detallesAgrupados = agruparDetallesPorEdad(detalles, colsSinNum);
            document.getElementById('modalDetallesTitulo').textContent = 'Detalles - ' + codigo;
            var thead = document.getElementById('modalDetallesThead');
            var tbody = document.getElementById('modalDetallesBody');
            var sinReg = document.getElementById('modalDetallesSinRegistros');
            var thCells = '<th class="px-3 py-2 text-left">Código</th><th class="px-3 py-2 text-left">Nombre programa</th><th class="px-3 py-2 text-left">Despliegue</th><th class="px-3 py-2 text-left">Descripción</th>';
            colsSinNum.forEach(function(k) {
                thCells += '<th class="px-3 py-2 text-left">' + (labelsReporte[k] || k) + '</th>';
            });
            thead.innerHTML = '<tr>' + thCells + '</tr>';
            tbody.innerHTML = '';
            if (detallesAgrupados.length === 0) {
                sinReg.classList.remove('hidden');
            } else {
                sinReg.classList.add('hidden');
                detallesAgrupados.forEach(function(d, i) {
                    var tr = document.createElement('tr');
                    tr.className = 'border-b border-gray-200';
                    var td = '<td class="px-3 py-2">' + esc(cab.codigo || codigo) + '</td><td class="px-3 py-2">' + esc(cab.nombre || '') + '</td><td class="px-3 py-2">' + esc(cab.despliegue || '') + '</td><td class="px-3 py-2">' + esc(cab.descripcion || '') + '</td>';
                    colsSinNum.forEach(function(k) {
                        td += '<td class="px-3 py-2"' + (k === 'descripcion_vacuna' ? ' style="white-space:pre-wrap;"' : '') + '>' + valorCeldaDetalle(k, d) + '</td>';
                    });
                    tr.innerHTML = td;
                    tbody.appendChild(tr);
                });
            }
            document.getElementById('modalDetallesPrograma').classList.remove('hidden');
        }

        function cerrarModalDetalles() {
            document.getElementById('modalDetallesPrograma').classList.add('hidden');
        }

        function checkProgramaEnUso(codigo) {
            return fetch('check_programa_en_uso.php?codigo=' + encodeURIComponent(codigo), { cache: 'no-store' })
                .then(function(r) { return r.json(); })
                .then(function(res) { return res.success && res.enUso; })
                .catch(function() { return false; });
        }

        function editarPrograma(codigo) {
            if (!codigo) return;
            checkProgramaEnUso(codigo).then(function(enUso) {
                if (enUso) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: 'No se puede editar', text: 'Este programa no puede ser editado porque ya ha sido asignado en un cronograma.' });
                    } else {
                        alert('Este programa no puede ser editado porque ya ha sido asignado en un cronograma.');
                    }
                    return;
                }
                var modal = document.getElementById('modalEditarPrograma');
                var iframe = document.getElementById('iframeEditarPrograma');
                if (iframe) iframe.src = 'dashboard-programas-registro.php?codigo=' + encodeURIComponent(codigo) + '&editar=1';
                if (modal) modal.classList.remove('hidden');
            });
        }

        function cerrarModalEditarPrograma() {
            var modal = document.getElementById('modalEditarPrograma');
            var iframe = document.getElementById('iframeEditarPrograma');
            if (iframe) iframe.src = 'about:blank';
            if (modal) { modal.classList.add('hidden'); modal.classList.remove('modal-editar-expandido'); }
            cargarListado();
        }

        function eliminarPrograma(codigo) {
            if (!codigo) return;
            checkProgramaEnUso(codigo).then(function(enUso) {
                if (enUso) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: 'No se puede eliminar', text: 'Este programa no puede ser eliminado porque ya ha sido asignado en un cronograma.' });
                    } else {
                        alert('Este programa no puede ser eliminado porque ya ha sido asignado en un cronograma.');
                    }
                    return;
                }
                var msg = '¿Está seguro de eliminar el programa \'' + (codigo || '').replace(/'/g, "\\'") + '\'?';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Confirmar eliminación',
                        text: msg,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            var form = new FormData();
                            form.append('codigo', codigo);
                            fetch('eliminar_programa.php', { method: 'POST', body: form })
                                .then(function(r) { return r.json(); })
                                .then(function(res) {
                                    if (res.success) {
                                        Swal.fire({ icon: 'success', title: 'Eliminado', text: res.message || 'Programa eliminado.' });
                                        cargarListado();
                                    } else {
                                        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo eliminar.' });
                                    }
                                })
                                .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }); });
                        }
                    });
                } else {
                    if (confirm(msg)) {
                        var form = new FormData();
                        form.append('codigo', codigo);
                        fetch('eliminar_programa.php', { method: 'POST', body: form })
                            .then(function(r) { return r.json(); })
                            .then(function(res) {
                                if (res.success) { cargarListado(); }
                                else { alert(res.message || 'No se pudo eliminar.'); }
                            })
                            .catch(function() { alert('Error de conexión.'); });
                    }
                }
            });
        }

        function cargarTiposParaFiltro() {
            return fetch('get_tipos_programa.php').then(r => r.json()).then(function(res) {
                if (!res.success || !res.data) return;
                var sel = document.getElementById('filtroTipo');
                if (!sel) return;
                sel.innerHTML = '<option value="">Todos</option>';
                res.data.forEach(function(t) {
                    var opt = document.createElement('option');
                    opt.value = String(t.codigo);
                    opt.textContent = t.nombre || '';
                    sel.appendChild(opt);
                });
            }).catch(function() {});
        }

        document.getElementById('btnToggleFiltrosProgramas').addEventListener('click', toggleFiltrosProgramas);
        document.getElementById('btnBuscarProgramas').addEventListener('click', cargarListado);
        document.getElementById('btnLimpiarFiltrosProgramas').addEventListener('click', function() {
            var pt = document.getElementById('periodoTipo');
            if (pt) pt.value = 'TODOS';
            var d = new Date();
            var fu = document.getElementById('fechaUnica');
            if (fu) fu.value = d.toISOString().slice(0, 10);
            var fi = document.getElementById('fechaInicio');
            if (fi) fi.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-01';
            var ff = document.getElementById('fechaFin');
            if (ff) ff.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate()).padStart(2, '0');
            var mu = document.getElementById('mesUnico');
            if (mu) mu.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
            var mi = document.getElementById('mesInicio');
            if (mi) mi.value = d.getFullYear() + '-01';
            var mf = document.getElementById('mesFin');
            if (mf) mf.value = d.getFullYear() + '-12';
            var ft = document.getElementById('filtroTipo');
            if (ft) ft.value = '';
            var fz = document.getElementById('filtroZona');
            if (fz) fz.value = '';
            var fdp = document.getElementById('filtroDespliegue');
            if (fdp) fdp.value = '';
            aplicarVisibilidadPeriodoProgramas();
            cargarListado();
        });
        function aplicarVisibilidadPeriodoProgramas() {
            var t = (document.getElementById('periodoTipo') && document.getElementById('periodoTipo').value) || '';
            ['periodoPorFecha','periodoEntreFechas','periodoPorMes','periodoEntreMeses'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            });
            if (t === 'POR_FECHA') { var e = document.getElementById('periodoPorFecha'); if (e) e.classList.remove('hidden'); }
            else if (t === 'ENTRE_FECHAS') { var e = document.getElementById('periodoEntreFechas'); if (e) e.classList.remove('hidden'); }
            else if (t === 'POR_MES') { var e = document.getElementById('periodoPorMes'); if (e) e.classList.remove('hidden'); }
            else if (t === 'ENTRE_MESES') { var e = document.getElementById('periodoEntreMeses'); if (e) e.classList.remove('hidden'); }
        }
        var periodoTipoEl = document.getElementById('periodoTipo');
        if (periodoTipoEl) periodoTipoEl.addEventListener('change', aplicarVisibilidadPeriodoProgramas);
        aplicarVisibilidadPeriodoProgramas();
        document.getElementById('btnExportarPdf').addEventListener('click', function() {
            var p = getParametrosFiltro();
            var url = 'generar_reporte_programas_filtrado.php?codTipo=' + encodeURIComponent(p.codTipo) + '&periodoTipo=' + encodeURIComponent(p.periodoTipo) + '&fechaUnica=' + encodeURIComponent(p.fechaUnica) + '&fechaInicio=' + encodeURIComponent(p.fechaInicio) + '&fechaFin=' + encodeURIComponent(p.fechaFin) + '&mesUnico=' + encodeURIComponent(p.mesUnico) + '&mesInicio=' + encodeURIComponent(p.mesInicio) + '&mesFin=' + encodeURIComponent(p.mesFin) + '&zona=' + encodeURIComponent(p.zona) + '&despliegue=' + encodeURIComponent(p.despliegue);
            window.open(url, '_blank', 'noopener');
        });
        document.getElementById('modalDetallesCerrar').addEventListener('click', cerrarModalDetalles);
        document.getElementById('modalDetallesPrograma').addEventListener('click', function(e) { if (e.target === this) cerrarModalDetalles(); });
        var modalEditarCerrar = document.getElementById('modalEditarProgramaCerrar');
        if (modalEditarCerrar) modalEditarCerrar.addEventListener('click', cerrarModalEditarPrograma);
        var modalEditar = document.getElementById('modalEditarPrograma');
        if (modalEditar) modalEditar.addEventListener('click', function(e) { if (e.target === modalEditar) cerrarModalEditarPrograma(); });
        document.getElementById('modalEditarBtnCancelar').addEventListener('click', cerrarModalEditarPrograma);
        document.getElementById('modalEditarBtnGuardar').addEventListener('click', function() {
            var iframe = document.getElementById('iframeEditarPrograma');
            if (iframe && iframe.contentWindow && typeof iframe.contentWindow.submitFormPrograma === 'function') {
                iframe.contentWindow.submitFormPrograma();
            }
        });
        function cerrarOverlayProductoProveedor() {
            var ov = document.getElementById('overlayProductoProveedor');
            var ifr = document.getElementById('iframeProductoProveedor');
            if (ov) ov.classList.add('hidden');
            if (ifr) ifr.src = 'about:blank';
        }
        document.getElementById('overlayProductoProveedorCerrar').addEventListener('click', cerrarOverlayProductoProveedor);
        document.getElementById('overlayProductoProveedor').addEventListener('click', function(e) {
            if (e.target.id === 'overlayProductoProveedor') cerrarOverlayProductoProveedor();
        });
        window.addEventListener('message', function(e) {
            if (e.data && e.data.tipo === 'programaActualizado') { cerrarModalEditarPrograma(); }
            if (e.data && e.data.tipo === 'abrirModalProducto') {
                var rowIndex = e.data.rowIndex != null ? e.data.rowIndex : 0;
                var ov = document.getElementById('overlayProductoProveedor');
                document.getElementById('overlayProductoProveedorTitulo').textContent = 'Buscar producto';
                document.getElementById('iframeProductoProveedor').src = 'modal_buscar_producto.php?rowIndex=' + encodeURIComponent(rowIndex);
                if (ov && ov.parentNode !== document.body) document.body.appendChild(ov);
                ov.classList.remove('hidden');
            }
            if (e.data && e.data.tipo === 'abrirModalProveedor') {
                var rowIndex = e.data.rowIndex != null ? e.data.rowIndex : 0;
                var ov = document.getElementById('overlayProductoProveedor');
                document.getElementById('overlayProductoProveedorTitulo').textContent = 'Buscar proveedor';
                document.getElementById('iframeProductoProveedor').src = 'modal_buscar_proveedor.php?rowIndex=' + encodeURIComponent(rowIndex);
                if (ov && ov.parentNode !== document.body) document.body.appendChild(ov);
                ov.classList.remove('hidden');
            }
            if (e.data && e.data.tipo === 'productoSeleccionado') {
                var iframeEditar = document.getElementById('iframeEditarPrograma');
                if (iframeEditar && iframeEditar.contentWindow) {
                    try { iframeEditar.contentWindow.postMessage(e.data, '*'); } catch (err) {}
                }
                cerrarOverlayProductoProveedor();
            }
            if (e.data && e.data.tipo === 'proveedorSeleccionado') {
                var iframeEditar = document.getElementById('iframeEditarPrograma');
                if (iframeEditar && iframeEditar.contentWindow) {
                    try { iframeEditar.contentWindow.postMessage(e.data, '*'); } catch (err) {}
                }
                cerrarOverlayProductoProveedor();
            }
            if (e.data && (e.data.tipo === 'cerrarModalProducto' || e.data.tipo === 'cerrarModalProveedor')) {
                cerrarOverlayProductoProveedor();
            }
            if (e.data && e.data.tipo === 'mostrarSwal') {
                var d = e.data;
                var opts = { icon: d.icon || 'info', title: d.title || '', text: d.text || '' };
                if (typeof Swal !== 'undefined') {
                    Swal.fire(opts).then(function() {
                        if (d.cerrarAlConfirmar) cerrarModalEditarPrograma();
                    });
                } else {
                    alert((d.title || '') + (d.text ? '\n' + d.text : ''));
                    if (d.cerrarAlConfirmar) cerrarModalEditarPrograma();
                }
            }
        });

        window._progIconosPage = 0;
        window._progIconosPageSize = 10;
        window._progIconosSearch = '';

        function renderizarTarjetasProgramas() {
            var data = window._listadoProgramas || [];
            var q = (window._progIconosSearch || '').toString().trim().toLowerCase();
            var filtrado = q ? data.filter(function(item) {
                var cab = item.cab || {};
                return (cab.codigo || '').toLowerCase().indexOf(q) >= 0 ||
                    (cab.nombre || '').toLowerCase().indexOf(q) >= 0 ||
                    (cab.nomTipo || '').toLowerCase().indexOf(q) >= 0 ||
                    (cab.despliegue || '').toLowerCase().indexOf(q) >= 0 ||
                    (formatearFecha(cab.fechaHoraRegistro) || '').toLowerCase().indexOf(q) >= 0;
            }) : data.slice(0);
            var pageSize = Math.max(1, parseInt(window._progIconosPageSize, 10) || 10);
            var total = filtrado.length;
            var totalPag = Math.max(1, Math.ceil(total / pageSize));
            var page = Math.max(0, Math.min(window._progIconosPage, totalPag - 1));
            window._progIconosPage = page;
            var start = page * pageSize;
            var slice = filtrado.slice(start, start + pageSize);

            var cont = document.getElementById('cardsContainerProg');
            if (!cont) return;
            cont.innerHTML = '';
            slice.forEach(function(item, idx) {
                var cab = item.cab || {};
                var codigo = cab.codigo || '';
                var num = start + idx + 1;
                var reporteUrl = 'generar_reporte_programa.php?codigo=' + encodeURIComponent(codigo);
                var codEsc = (codigo || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                var card = document.createElement('div');
                card.className = 'card-item';
                card.innerHTML =
                    '<div class="card-numero-row">#' + num + '</div>' +
                    '<div class="card-contenido">' +
                    '<div class="card-codigo">' + esc(codigo) + '</div>' +
                    '<div class="card-campos">' +
                    '<div class="card-row"><span class="label">Nombre:</span> ' + esc(cab.nombre || '') + '</div>' +
                    '<div class="card-row"><span class="label">Tipo:</span> ' + esc(cab.nomTipo || '') + '</div>' +
                    '<div class="card-row"><span class="label">Despliegue:</span> ' + esc(cab.despliegue || '') + '</div>' +
                    '<div class="card-row"><span class="label">Fecha:</span> ' + formatearFecha(cab.fechaHoraRegistro) + '</div>' +
                    '</div>' +
                    '<div class="card-acciones">' +
                    '<button type="button" class="btn-detalles-programa cursor-pointer text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-2 transition" data-codigo="' + codEsc + '" title="Ver"><i class="fas fa-eye"></i> Ver</button>' +
                    '<a class="text-red-600 hover:text-red-800" title="PDF" target="_blank" rel="noopener" href="' + reporteUrl + '"><i class="fa-solid fa-file-pdf"></i></a>' +
                    '<button type="button" class="btn-editar-programa text-indigo-600 hover:text-indigo-800" title="Editar" data-codigo="' + codEsc + '"><i class="fa-solid fa-edit"></i></button>' +
                    '<button type="button" class="btn-eliminar-programa text-rose-600 hover:text-rose-800" title="Eliminar" data-codigo="' + codEsc + '"><i class="fa-solid fa-trash"></i></button>' +
                    '</div></div>';
                cont.appendChild(card);
            });
            cont.querySelectorAll('.btn-detalles-programa').forEach(function(btn) {
                btn.addEventListener('click', function() { abrirModalDetalles(this.getAttribute('data-codigo')); });
            });
            cont.querySelectorAll('.btn-editar-programa').forEach(function(btn) {
                btn.addEventListener('click', function() { editarPrograma(this.getAttribute('data-codigo')); });
            });
            cont.querySelectorAll('.btn-eliminar-programa').forEach(function(btn) {
                btn.addEventListener('click', function() { eliminarPrograma(this.getAttribute('data-codigo')); });
            });

            window._progTotalPag = totalPag;
            window.progIconosPageGo = function(p) {
                var totalP = window._progTotalPag || 1;
                var cur = window._progIconosPage || 0;
                var next = (p === 'prev') ? cur - 1 : (p === 'next') ? cur + 1 : (typeof p === 'number' ? p : cur);
                if (next < 0 || next >= totalP) return;
                window._progIconosPage = next;
                renderizarTarjetasProgramas();
            };
            var pagEl = document.getElementById('cardsPaginationProg');
            if (pagEl && typeof buildPaginationIconos === 'function') {
                pagEl.innerHTML = buildPaginationIconos({ page: page, pages: totalPag, start: start, end: total === 0 ? 0 : Math.min(start + slice.length, total), recordsDisplay: total });
            } else if (pagEl) {
                pagEl.innerHTML = '<span class="dataTables_info">Mostrando ' + (total === 0 ? 0 : start + 1) + ' a ' + Math.min(start + slice.length, total) + ' de ' + total + ' registros</span>';
            }
        }

        function aplicarVistaProgramas(vista) {
            var w = document.getElementById('tablaProgramasWrapper');
            if (!w) return;
            w.setAttribute('data-vista', vista);
            var esLista = (vista === 'lista');
            var listWrap = document.getElementById('viewListaProg');
            var iconWrap = document.getElementById('viewTarjetasProg');
            var progDt = document.getElementById('progDtControls');
            var progIconos = document.getElementById('progIconosControls');
            if (listWrap) { listWrap.classList.toggle('hidden', !esLista); listWrap.style.display = esLista ? 'block' : 'none'; }
            if (iconWrap) { iconWrap.classList.toggle('hidden', esLista); iconWrap.style.display = esLista ? 'none' : 'block'; }
            var btnLista = document.getElementById('btnViewListaProg'); var btnIconos = document.getElementById('btnViewIconosProg');
            if (btnLista) btnLista.classList.toggle('active', esLista);
            if (btnIconos) btnIconos.classList.toggle('active', !esLista);
            if (esLista) {
                if (progIconos && progDt) {
                    var filterEl = progIconos.querySelector('.dataTables_filter');
                    if (filterEl) { progIconos.removeChild(filterEl); progDt.appendChild(filterEl); }
                }
                if (progIconos) progIconos.style.display = 'none';
                if (progDt) progDt.style.display = '';
            } else {
                if (progDt && progIconos) {
                    var filterEl = progDt.querySelector('.dataTables_filter');
                    if (filterEl) { progDt.removeChild(filterEl); progIconos.appendChild(filterEl); }
                }
                if (progDt) progDt.style.display = 'none';
                if (progIconos) progIconos.style.display = '';
                var toolbarRow = progIconos.querySelector('.iconos-toolbar-row');
                if (!toolbarRow) {
                    var lengthOptions = [5, 10, 25, 50];
                    var len = window._progIconosPageSize;
                    var lengthSelect = '<label class="inline-flex items-center gap-2"><span>Mostrar</span><select class="cards-length-select">' +
                        lengthOptions.map(function(n) { return '<option value="' + n + '"' + (n === len ? ' selected' : '') + '>' + n + '</option>'; }).join('') + '</select><span>registros</span></label>';
                    toolbarRow = document.createElement('div');
                    toolbarRow.className = 'iconos-toolbar-row flex flex-wrap items-center gap-3';
                    toolbarRow.innerHTML = lengthSelect;
                    progIconos.insertBefore(toolbarRow, progIconos.firstChild);
                    if (progIconos.querySelector('.dataTables_filter')) toolbarRow.appendChild(progIconos.querySelector('.dataTables_filter'));
                    toolbarRow.querySelector('.cards-length-select').addEventListener('change', function() {
                        window._progIconosPageSize = parseInt(this.value, 10) || 10;
                        window._progIconosPage = 0;
                        renderizarTarjetasProgramas();
                    });
                }
                var searchInput = document.querySelector('#progIconosControls .dataTables_filter input');
                if (searchInput) {
                    window._progIconosSearch = searchInput.value || '';
                    searchInput.removeEventListener('input', window._progSearchHandler);
                    searchInput.removeEventListener('keyup', window._progSearchHandler);
                    window._progSearchHandler = function() { window._progIconosSearch = searchInput.value || ''; window._progIconosPage = 0; renderizarTarjetasProgramas(); };
                    searchInput.addEventListener('input', window._progSearchHandler);
                    searchInput.addEventListener('keyup', window._progSearchHandler);
                }
                renderizarTarjetasProgramas();
            }
        }
        document.getElementById('btnViewListaProg').addEventListener('click', function() { aplicarVistaProgramas('lista'); });
        document.getElementById('btnViewIconosProg').addEventListener('click', function() { aplicarVistaProgramas('iconos'); });

        cargarTiposParaFiltro();
        cargarListado();
    </script>
</body>
</html>
