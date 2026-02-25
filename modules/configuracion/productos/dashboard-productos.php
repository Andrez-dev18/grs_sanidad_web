<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; }
        else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}

include_once '../../../../conexion_grs/conexion.php';
$conexion = conectar_joya_mysqli();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
include_once __DIR__ . '/../../../includes/datatables_lang_es.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Productos</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../../assets/js/sweetalert-helpers.js"></script>
    <style>
        body { background: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; min-height: 100vh; }
        .card { transition: all 0.3s ease; cursor: pointer; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .select2-container .select2-selection--single { height: 42px; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 6px 12px; display: flex; align-items: center; }
        #productoModal .select2-container .select2-selection--single { height: 34px; padding: 4px 10px; border-radius: 0.375rem; }
        .select2-selection__rendered { font-size: 0.875rem; color: #374151; }
        .select2-selection__arrow { height: 100%; }
        .select2-dropdown { border-radius: 0.5rem; border: 1px solid #d1d5db; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #3b82f6; color: #fff; }
        .select2-container--default .select2-search--dropdown .select2-search__field { border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem 0.75rem; }
        /* Tabla/DataTables: estilos generales en dashboard-config.css */
        @media (max-width: 640px) {
            .container.mx-auto { padding-left: 0.75rem; padding-right: 0.75rem; padding-top: 1rem; padding-bottom: 1.5rem; }
            .dashboard-actions.filtros-actions { flex-direction: column; align-items: stretch; }
            .dashboard-actions.filtros-actions button { width: 100%; justify-content: center; }
            .text-center.mt-12 { margin-top: 2rem; padding: 0 0.5rem; }
        }
        #modalEditarProducto { min-height: 100vh; min-height: 100dvh; align-items: center; justify-content: center; padding: 0.75rem; padding-top: max(0.75rem, env(safe-area-inset-top)); padding-bottom: max(0.75rem, env(safe-area-inset-bottom)); overflow-y: auto; -webkit-overflow-scrolling: touch; }
        #modalEditarProducto .modal-inner { width: 100%; max-width: 42rem; max-height: calc(100vh - 1.5rem); max-height: calc(100dvh - 1.5rem); display: flex; flex-direction: column; flex-shrink: 0; }
        #modalEditarProducto .modal-body { flex: 1; min-height: 0; overflow-y: auto; -webkit-overflow-scrolling: touch; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 py-6 sm:py-12 max-w-full">

        <div id="viewProductos" class="content-view">
            <div class="form-container max-w-7xl mx-auto">

                <!-- Card filtros (estilo reportes, desplegado por defecto) -->
                <div class="mb-4 sm:mb-6 bg-white border rounded-xl sm:rounded-2xl shadow-sm overflow-hidden min-w-0">
                    <button type="button" id="btnToggleFiltrosProductos"
                        class="w-full flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 hover:bg-gray-100 transition touch-manipulation">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🔎</span>
                            <h3 class="text-base font-semibold text-gray-800">Filtros de búsqueda</h3>
                        </div>
                        <svg id="iconoFiltrosProductos" class="w-5 h-5 text-gray-600 transition-transform duration-300"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="contenidoFiltrosProductos" class="px-4 sm:px-6 pb-4 sm:pb-6 pt-4 hidden">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Línea</label>
                                    <select id="filtroLinea" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 form-control">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Almacén</label>
                                    <select id="filtroAlmacen" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 form-control">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                                    <select id="filtroProveedor" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 form-control" style="width:100%;">
                                        <option value="">Escriba para buscar (código, sigla o descripción)...</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                    <input type="text" id="filtroDescripcion" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 form-control" placeholder="Texto en descripción del producto" maxlength="200">
                                </div>
                            </div>
                        </div>
                        <div class="dashboard-actions filtros-actions mt-6 flex flex-wrap justify-end gap-3 sm:gap-4">
                            <button type="button" id="btnBuscarProductos" class="px-4 sm:px-6 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-medium inline-flex items-center justify-center gap-2 touch-manipulation">
                                Buscar
                            </button>
                            <button type="button" id="btnLimpiarFiltrosProductos" class="px-4 sm:px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium inline-flex items-center justify-center gap-2 touch-manipulation">
                                Limpiar
                            </button>
                            <button type="button" id="btnExportarPdfProductos" class="px-4 sm:px-6 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 inline-flex items-center justify-center gap-2 font-medium touch-manipulation" title="Exportar a PDF los resultados de la búsqueda">
                                <i class="fas fa-file-pdf"></i> Exportar PDF
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla / Iconos productos (inicialmente vacía) -->
                <div id="tablaProductosWrapper" class="mb-6 bg-white border rounded-xl sm:rounded-2xl shadow-sm overflow-hidden min-w-0" data-vista-tabla-iconos data-vista="tabla">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                        <div class="view-toggle-group flex items-center gap-2 flex-shrink-0">
                            <button type="button" class="view-toggle-btn active text-sm sm:text-base px-3 sm:px-4 py-2 rounded-lg" id="btnViewTablaProductos" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                            <button type="button" class="view-toggle-btn text-sm sm:text-base px-3 sm:px-4 py-2 rounded-lg" id="btnViewIconosProductos" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                        </div>
                        <div id="productosDtControls" class="flex flex-wrap items-center gap-3"></div>
                        <div id="productosIconosControls" class="flex flex-wrap items-center gap-3" style="display:none;"></div>
                    </div>
                    <div class="view-tarjetas-wrap px-4 sm:px-6 pb-4 overflow-x-hidden" id="viewTarjetasProductos" style="display: none;">
                        <div id="cardsContainerProductos" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                        <div id="cardsPaginationProductos" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3"></div>
                    </div>
                    <div class="view-lista-wrap table-container overflow-x-auto px-4 sm:px-6 pb-6 pt-4">
                        <div class="table-wrapper overflow-x-auto -webkit-overflow-scrolling-touch">
                            <table id="tablaProductosMitm" class="data-table w-full text-sm config-table">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">N°</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Código</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Descripción</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Línea</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Almacén</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Proveedor</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Unidad</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Dosis</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="productosMitmBody"></tbody>
                            </table>
                        </div>
                        <p id="productosMitmMensaje" class="text-sm text-gray-500 mt-2">Seleccione filtros y pulse Buscar.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar producto: proveedor, unidad, dosis, ¿Es vacuna?, enfermedades -->
        <div id="modalEditarProducto" class="fixed inset-0 bg-black/50 flex justify-center z-50 overflow-y-auto" style="display: none;" aria-hidden="true">
            <div class="modal-inner bg-white rounded-xl sm:rounded-2xl shadow-xl w-full my-auto flex flex-col overflow-hidden">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 border-b border-gray-200 flex-shrink-0 gap-3">
                    <h2 class="text-base sm:text-lg font-bold text-gray-800 min-w-0">Editar producto</h2>
                    <button type="button" id="modalEditarProductoCerrar" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition touch-manipulation" aria-label="Cerrar">×</button>
                </div>
                <form id="formEditarProducto" class="flex flex-col flex-1 min-h-0 overflow-y-auto modal-body">
                    <input type="hidden" id="editProductoCodigo" name="codigo" value="">
                    <div class="p-4 sm:p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código / Descripción</label>
                            <input type="text" id="editProductoDescri" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg bg-gray-100" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                            <select id="editProductoProveedor" name="tcodprove" class="w-full form-control" style="width:100%;">
                                <option value="">Escriba para buscar...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unidad</label>
                            <input type="text" id="editProductoUnidad" name="unidad" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" maxlength="50" placeholder="Unidad">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dosis</label>
                            <input type="text" id="editProductoDosis" name="dosis" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" maxlength="100" placeholder="Dosis">
                        </div>
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="editProductoEsVacuna" name="es_vacuna" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">¿Es vacuna?</span>
                            </label>
                        </div>
                        <div id="wrapEditProductoEnfermedades" class="hidden border-t border-gray-200 pt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Enfermedades</label>
                            <div id="loadingEditEnfermedades" class="hidden text-sm text-gray-500 py-1">Cargando...</div>
                            <div id="wrapCheckboxEditEnfermedades" class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2 text-sm"></div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 justify-end px-4 sm:px-6 pb-4 pt-2 border-t border-gray-100 flex-shrink-0">
                        <button type="button" id="btnCancelarEditarProducto" class="w-full sm:w-auto px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium touch-manipulation">Cancelar</button>
                        <button type="submit" class="w-full sm:w-auto px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium inline-flex items-center justify-center gap-1 touch-manipulation"><i class="fas fa-save"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-8 sm:mt-12 px-2">
            <p class="text-gray-500 text-xs sm:text-sm">Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © <span id="currentYear"></span></p>
        </div>
        <script>document.getElementById('currentYear').textContent = new Date().getFullYear();</script>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="../../../assets/js/i18n/datatables-es.js"></script>
    <script src="../../../assets/js/pagination-iconos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    (function() {
        var baseUrl = window.location.pathname.replace(/\/[^/]+\.php$/, '/');
        var tableProductos = null;
        var PRODUCTOS_LENGTH_OPTIONS = [20, 25, 50, 100];
        var productosPageLengthSeleccionado = 20;

        function normalizarPageLengthProductos(valor) {
            var n = parseInt(valor, 10);
            return PRODUCTOS_LENGTH_OPTIONS.indexOf(n) >= 0 ? n : 20;
        }

        function sincronizarControlesDtProductos() {
            if (!tableProductos) return;
            var $wrapper = jQuery('#tablaProductosMitm').closest('.dataTables_wrapper');
            var $controls = jQuery('#productosDtControls');
            var $length = $wrapper.find('.dataTables_length').first();
            var $filter = $wrapper.find('.dataTables_filter').first();
            if ($controls.length && $length.length && $filter.length) {
                $controls.empty().append($length, $filter);
            }
        }

        function toggleFiltrosProductos() {
            var contenido = document.getElementById('contenidoFiltrosProductos');
            var icono = document.getElementById('iconoFiltrosProductos');
            if (!contenido || !icono) return;
            contenido.classList.toggle('hidden');
            icono.classList.toggle('rotate-180');
        }

        function cargarLineas() {
            fetch(baseUrl + 'get_lineas.php').then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success || !res.data) return;
                var sel = document.getElementById('filtroLinea');
                if (!sel) return;
                sel.innerHTML = '<option value="">Seleccionar</option>';
                res.data.forEach(function(o) {
                    var opt = document.createElement('option');
                    opt.value = o.linea || '';
                    opt.textContent = o.text || (o.linea + ' - ' + (o.descri || ''));
                    sel.appendChild(opt);
                });
            }).catch(function() {});
        }

        function cargarAlmacenes() {
            fetch(baseUrl + 'get_almacenes.php').then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success || !res.data) return;
                var sel = document.getElementById('filtroAlmacen');
                if (!sel) return;
                sel.innerHTML = '<option value="">Seleccionar</option>';
                res.data.forEach(function(o) {
                    var opt = document.createElement('option');
                    opt.value = o.alma || '';
                    opt.textContent = o.text || o.alma || '';
                    sel.appendChild(opt);
                });
            }).catch(function() {});
        }

        function initSelect2FiltroProveedor() {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
            var $sel = jQuery('#filtroProveedor');
            if ($sel.length === 0) return;
            if ($sel.data('select2')) $sel.select2('destroy');
            $sel.select2({
                placeholder: 'Escriba para buscar...',
                allowClear: true,
                minimumInputLength: 1,
                ajax: {
                    url: baseUrl + 'get_proveedores_buscar.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term || '' }; },
                    processResults: function(data) {
                        if (!data.success || !data.results) return { results: [] };
                        return { results: data.results };
                    }
                }
            });
        }

        function initSelect2ModalProveedor() {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
            var $sel = jQuery('#editProductoProveedor');
            if ($sel.length === 0) return;
            if ($sel.data('select2')) $sel.select2('destroy');
            $sel.select2({
                placeholder: 'Escriba para buscar...',
                allowClear: true,
                minimumInputLength: 1,
                ajax: {
                    url: baseUrl + 'get_proveedores_buscar.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term || '' }; },
                    processResults: function(data) {
                        if (!data.success || !data.results) return { results: [] };
                        return { results: data.results };
                    }
                }
            });
        }

        function cargarProductosFiltrados() {
            var lin = (document.getElementById('filtroLinea') && document.getElementById('filtroLinea').value) ? document.getElementById('filtroLinea').value.trim() : '';
            var alma = (document.getElementById('filtroAlmacen') && document.getElementById('filtroAlmacen').value) ? document.getElementById('filtroAlmacen').value.trim() : '';
            var tcodprove = (document.getElementById('filtroProveedor') && document.getElementById('filtroProveedor').value) ? document.getElementById('filtroProveedor').value.trim() : '';
            var descri = (document.getElementById('filtroDescripcion') && document.getElementById('filtroDescripcion').value) ? document.getElementById('filtroDescripcion').value.trim() : '';
            var tbody = document.getElementById('productosMitmBody');
            var msg = document.getElementById('productosMitmMensaje');
            if (!tbody) return;
            tbody.innerHTML = '';
            if (msg) msg.textContent = 'Cargando...';
            var url = baseUrl + 'listar_mitm_filtrado.php?lin=' + encodeURIComponent(lin) + '&alma=' + encodeURIComponent(alma) + '&tcodprove=' + encodeURIComponent(tcodprove) + '&descri=' + encodeURIComponent(descri);
            fetch(url).then(function(r) { return r.json(); }).then(function(res) {
                if (msg) msg.textContent = '';
                if (jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable && jQuery.fn.DataTable.isDataTable('#tablaProductosMitm')) {
                    productosPageLengthSeleccionado = normalizarPageLengthProductos(jQuery('#tablaProductosMitm').DataTable().page.len());
                    jQuery('#tablaProductosMitm').DataTable().destroy();
                    jQuery('#tablaProductosMitm').find('tbody').empty();
                }
                tableProductos = null;
                if (!res.success) { if (msg) msg.textContent = 'Error al cargar.'; return; }
                var data = res.data || [];
                if (data.length === 0) { if (msg) msg.textContent = 'Sin resultados para el filtro.'; return; }
                if (msg) msg.textContent = data.length + ' producto(s).';
                data.forEach(function(p, i) {
                    var idx = i + 1;
                    var cod = (p.codigo || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    var codigoAttr = (p.codigo || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                    var desc = (p.descri || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    var descAttr = (p.descri || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/&/g, '&amp;');
                    var prov = (p.nombre_proveedor || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    var provAttr = (p.nombre_proveedor || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/&/g, '&amp;');
                    var linAttr = (p.lin || '').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                    var almaAttr = (p.alma || '').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                    var unidadAttr = (p.unidad || '').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                    var dosisAttr = (p.dosis || '').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                    var tr = document.createElement('tr');
                    tr.className = 'border-b border-gray-200';
                    tr.setAttribute('data-codigo', codigoAttr);
                    tr.setAttribute('data-descri', descAttr);
                    tr.setAttribute('data-lin', linAttr);
                    tr.setAttribute('data-alma', almaAttr);
                    tr.setAttribute('data-proveedor', provAttr);
                    tr.setAttribute('data-unidad', unidadAttr);
                    tr.setAttribute('data-dosis', dosisAttr);
                    tr.setAttribute('data-index', String(idx));
                    tr.innerHTML = '<td class="px-4 py-3">' + idx + '</td><td class="px-4 py-3">' + cod + '</td><td class="px-4 py-3">' + desc + '</td><td class="px-4 py-3">' + (p.lin || '').replace(/</g, '&lt;') + '</td><td class="px-4 py-3">' + (p.alma || '').replace(/</g, '&lt;') + '</td><td class="px-4 py-3">' + prov + '</td><td class="px-4 py-3">' + (p.unidad || '').replace(/</g, '&lt;') + '</td><td class="px-4 py-3">' + (p.dosis || '').replace(/</g, '&lt;') + '</td><td class="px-4 py-3 text-center"><button type="button" class="btn-editar-mitm p-2 text-blue-600 hover:bg-blue-100 rounded-lg" data-codigo="' + codigoAttr + '" title="Editar"><i class="fas fa-edit"></i></button></td>';
                    tbody.appendChild(tr);
                });
                renderizarTarjetasProductos();
                aplicarVisibilidadVistaProductos();
                normalizarFilasTablaProductos();
                tableProductos = jQuery('#tablaProductosMitm').DataTable({
                    pageLength: normalizarPageLengthProductos(productosPageLengthSeleccionado),
                    lengthMenu: [[20, 25, 50, 100, -1], [20, 25, 50, 100, 'Todos']],
                    language: window.DATATABLES_LANG_ES || {},
                    ordering: false,
                    order: [[0, 'asc']],
                    orderClasses: false,
                    columnDefs: [{ orderable: false, targets: [8] }],
                    drawCallback: function () {
                        renderizarTarjetasProductos();
                        sincronizarControlesDtProductos();
                    },
                    initComplete: function () {
                        sincronizarControlesDtProductos();
                        $('#productosDtControls').show();
                        $('#productosIconosControls').hide();
                    }
                });
                sincronizarControlesDtProductos();
            }).catch(function() { if (msg) msg.textContent = 'Error de conexión.'; });
        }

        function abrirModalEditarProducto(codigo) {
            if (!codigo) return;
            document.getElementById('editProductoCodigo').value = codigo;
            document.getElementById('editProductoDescri').value = '';
            document.getElementById('editProductoProveedor').value = '';
            if (jQuery('#editProductoProveedor').data('select2')) jQuery('#editProductoProveedor').empty().append('<option value="">Escriba para buscar...</option>');
            document.getElementById('editProductoUnidad').value = '';
            document.getElementById('editProductoDosis').value = '';
            document.getElementById('editProductoEsVacuna').checked = false;
            document.getElementById('wrapEditProductoEnfermedades').classList.add('hidden');
            document.getElementById('wrapCheckboxEditEnfermedades').innerHTML = '';
            document.getElementById('modalEditarProducto').style.display = 'flex';
            initSelect2ModalProveedor();
            fetch(baseUrl + 'get_datos_mitm.php?codigo=' + encodeURIComponent(codigo)).then(function(r) { return r.json(); }).then(function(res) {
                if (res.success) {
                    document.getElementById('editProductoDescri').value = (codigo + ' - ' + (res.nomProducto || '')).trim();
                    document.getElementById('editProductoUnidad').value = res.unidad || '';
                    document.getElementById('editProductoDosis').value = res.dosis || '';
                    var esVacuna = !!(res.es_vacuna && res.es_vacuna !== 0);
                    var codEnfermedades = res.codEnfermedades || [];
                    document.getElementById('editProductoEsVacuna').checked = esVacuna;
                    var wrapEdit = document.getElementById('wrapEditProductoEnfermedades');
                    if (esVacuna) {
                        wrapEdit.classList.remove('hidden');
                        cargarEnfermedadesEnModalEditar(codEnfermedades);
                    }
                    var $sel = jQuery('#editProductoProveedor');
                    if (res.codProveedor && res.nomProveedor) {
                        if ($sel.find('option[value="' + res.codProveedor.replace(/"/g, '&quot;') + '"]').length === 0) {
                            var opt = new Option(res.nomProveedor, res.codProveedor, true, true);
                            $sel.append(opt);
                        }
                        $sel.val(res.codProveedor).trigger('change');
                    }
                }
            }).catch(function() {});
        }

        function cargarEnfermedadesEnModalEditar(codEnfermedadesPreseleccionados) {
            var cont = document.getElementById('wrapCheckboxEditEnfermedades');
            var loading = document.getElementById('loadingEditEnfermedades');
            codEnfermedadesPreseleccionados = codEnfermedadesPreseleccionados || [];
            if (cont.innerHTML.trim() === '') {
                loading.classList.remove('hidden');
                fetch(baseUrl + 'get_enfermedades.php').then(function(r) { return r.json(); }).then(function(res) {
                    loading.classList.add('hidden');
                    if (!res.success || !res.results) return;
                    cont.innerHTML = '';
                    (res.results || []).forEach(function(e) {
                        var label = document.createElement('label');
                        label.className = 'flex items-center gap-2 cursor-pointer whitespace-nowrap overflow-hidden';
                        label.style.minWidth = '0';
                        var cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.name = 'cod_enfermedades[]';
                        cb.value = e.cod_enf;
                        cb.className = 'rounded border-gray-300 text-indigo-600';
                        if (codEnfermedadesPreseleccionados.indexOf(e.cod_enf) !== -1) cb.checked = true;
                        var span = document.createElement('span');
                        span.className = 'truncate';
                        span.title = e.nom_enf || '';
                        span.textContent = e.nom_enf || '';
                        label.appendChild(cb);
                        label.appendChild(span);
                        cont.appendChild(label);
                    });
                }).catch(function() { loading.classList.add('hidden'); });
            } else {
                cont.querySelectorAll('input[name="cod_enfermedades[]"]').forEach(function(cb) {
                    cb.checked = codEnfermedadesPreseleccionados.indexOf(parseInt(cb.value, 10)) !== -1;
                });
            }
        }

        function cerrarModalEditarProducto() {
            document.getElementById('modalEditarProducto').style.display = 'none';
        }

        function limpiarFiltros() {
            document.getElementById('filtroLinea').value = '';
            document.getElementById('filtroAlmacen').value = '';
            if (document.getElementById('filtroDescripcion')) document.getElementById('filtroDescripcion').value = '';
            if (jQuery('#filtroProveedor').data('select2')) jQuery('#filtroProveedor').val(null).trigger('change');
            if (jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable && jQuery.fn.DataTable.isDataTable('#tablaProductosMitm')) {
                productosPageLengthSeleccionado = normalizarPageLengthProductos(jQuery('#tablaProductosMitm').DataTable().page.len());
                jQuery('#tablaProductosMitm').DataTable().destroy();
            }
            tableProductos = null;
            document.getElementById('productosMitmBody').innerHTML = '';
            var msg = document.getElementById('productosMitmMensaje');
            if (msg) msg.textContent = 'Seleccione filtros y pulse Buscar.';
            $('#productosDtControls').empty();
            $('#productosIconosControls').empty();
        }

        function exportarPdfProductos() {
            var lin = (document.getElementById('filtroLinea') && document.getElementById('filtroLinea').value) ? document.getElementById('filtroLinea').value.trim() : '';
            var alma = (document.getElementById('filtroAlmacen') && document.getElementById('filtroAlmacen').value) ? document.getElementById('filtroAlmacen').value.trim() : '';
            var tcodprove = (document.getElementById('filtroProveedor') && document.getElementById('filtroProveedor').value) ? document.getElementById('filtroProveedor').value.trim() : '';
            var descri = (document.getElementById('filtroDescripcion') && document.getElementById('filtroDescripcion').value) ? document.getElementById('filtroDescripcion').value.trim() : '';
            if (lin === '' && alma === '' && tcodprove === '' && descri === '') {
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Filtros requeridos', text: 'Seleccione al menos un filtro para exportar el PDF.' });
                else alert('Debe usar al menos un filtro para exportar el PDF.');
                return;
            }
            var url = baseUrl + 'generar_reporte_productos_pdf.php?lin=' + encodeURIComponent(lin) + '&alma=' + encodeURIComponent(alma) + '&tcodprove=' + encodeURIComponent(tcodprove) + '&descri=' + encodeURIComponent(descri);
            window.open(url, '_blank', 'noopener');
        }

        function aplicarVisibilidadVistaProductos() {
            var wrapper = document.getElementById('tablaProductosWrapper');
            if (!wrapper) return;
            var vista = wrapper.getAttribute('data-vista') || 'tabla';
            var listaWrap = wrapper.querySelector('.view-lista-wrap');
            var tarjetasWrap = document.getElementById('viewTarjetasProductos');
            var btnLista = document.getElementById('btnViewTablaProductos');
            var btnIconos = document.getElementById('btnViewIconosProductos');
            if (listaWrap) listaWrap.style.display = vista === 'tabla' ? 'block' : 'none';
            if (tarjetasWrap) tarjetasWrap.style.display = vista === 'iconos' ? 'block' : 'none';
            if (btnLista) btnLista.classList.toggle('active', vista === 'tabla');
            if (btnIconos) btnIconos.classList.toggle('active', vista === 'iconos');
            $('#productosDtControls').show();
            $('#productosIconosControls').hide();
            sincronizarControlesDtProductos();
        }

        function renderizarTarjetasProductos() {
            var tbody = document.getElementById('productosMitmBody');
            var cont = document.getElementById('cardsContainerProductos');
            if (!tbody || !cont) return;
            cont.innerHTML = '';
            var rows = tbody.querySelectorAll('tr[data-codigo]');
            rows.forEach(function(tr) {
                var codigo = tr.getAttribute('data-codigo') || '';
                var descri = tr.getAttribute('data-descri') || '';
                var lin = tr.getAttribute('data-lin') || '';
                var alma = tr.getAttribute('data-alma') || '';
                var proveedor = tr.getAttribute('data-proveedor') || '';
                var unidad = tr.getAttribute('data-unidad') || '';
                var dosis = tr.getAttribute('data-dosis') || '';
                var idx = tr.getAttribute('data-index') || '0';
                var esc = function(s) { return (s + '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;'); };
                var card = document.createElement('div');
                card.className = 'card-item';
                card.setAttribute('data-codigo', codigo);
                card.innerHTML = '<div class="card-numero-row">#' + idx + '</div>' +
                    '<div class="card-row"><span class="label">Código:</span> <span>' + esc(codigo) + '</span></div>' +
                    '<div class="card-row"><span class="label">Descripción:</span> <span>' + esc(descri) + '</span></div>' +
                    '<div class="card-row"><span class="label">Línea:</span> <span>' + esc(lin) + '</span></div>' +
                    '<div class="card-row"><span class="label">Almacén:</span> <span>' + esc(alma) + '</span></div>' +
                    '<div class="card-row"><span class="label">Proveedor:</span> <span>' + esc(proveedor) + '</span></div>' +
                    '<div class="card-row"><span class="label">Unidad:</span> <span>' + esc(unidad) + '</span></div>' +
                    '<div class="card-row"><span class="label">Dosis:</span> <span>' + esc(dosis) + '</span></div>' +
                    '<div class="card-acciones">' +
                    '<button type="button" class="btn-editar-mitm btn-editar-card-producto p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition" data-codigo="' + esc(codigo) + '" title="Editar"><i class="fas fa-edit"></i></button>' +
                    '</div>';
                cont.appendChild(card);
            });
            var pag = document.getElementById('cardsPaginationProductos');
            if (pag) {
                if (tableProductos) {
                    var info = tableProductos.page.info();
                    pag.innerHTML = (typeof buildPaginationIconos === 'function')
                        ? buildPaginationIconos(info)
                        : ('<span>Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros</span>');
                } else {
                    var total = rows.length;
                    pag.innerHTML = total > 0 ? ('<span>Mostrando 1 a ' + total + ' de ' + total + ' registros</span>') : '';
                }
            }
        }

        function normalizarFilasTablaProductos() {
            var tabla = document.getElementById('tablaProductosMitm');
            if (!tabla) return;
            var columnas = tabla.querySelectorAll('thead th').length;
            var filas = tabla.querySelectorAll('tbody tr');
            filas.forEach(function(tr) {
                var celdas = tr.querySelectorAll('td');
                var tieneColspan = !!tr.querySelector('td[colspan]');
                if (tieneColspan || celdas.length !== columnas) {
                    tr.remove();
                }
            });
        }

        function initVistaProductos() {
            var wrapper = document.getElementById('tablaProductosWrapper');
            if (!wrapper) return;
            var vistaInicial = window.innerWidth < 768 ? 'iconos' : 'tabla';
            wrapper.setAttribute('data-vista', vistaInicial);
            renderizarTarjetasProductos();
            aplicarVisibilidadVistaProductos();
        }

        jQuery(document).on('click', '.btn-editar-mitm, .btn-editar-card-producto', function() {
            var cod = jQuery(this).attr('data-codigo') || '';
            if (cod) abrirModalEditarProducto(cod);
        });
        document.getElementById('btnToggleFiltrosProductos').addEventListener('click', toggleFiltrosProductos);
        document.getElementById('btnBuscarProductos').addEventListener('click', cargarProductosFiltrados);
        document.getElementById('btnLimpiarFiltrosProductos').addEventListener('click', limpiarFiltros);
        document.getElementById('btnExportarPdfProductos').addEventListener('click', exportarPdfProductos);
        var btnViewTablaProductos = document.getElementById('btnViewTablaProductos');
        var btnViewIconosProductos = document.getElementById('btnViewIconosProductos');
        var tablaProductosWrapper = document.getElementById('tablaProductosWrapper');
        sincronizarControlesDtProductos();
        if (btnViewTablaProductos) btnViewTablaProductos.addEventListener('click', function() {
            if (tablaProductosWrapper) tablaProductosWrapper.setAttribute('data-vista', 'tabla');
            aplicarVisibilidadVistaProductos();
        });
        if (btnViewIconosProductos) btnViewIconosProductos.addEventListener('click', function() {
            if (tablaProductosWrapper) tablaProductosWrapper.setAttribute('data-vista', 'iconos');
            renderizarTarjetasProductos();
            aplicarVisibilidadVistaProductos();
        });
        initVistaProductos();

        document.getElementById('editProductoEsVacuna').addEventListener('change', function() {
            var wrap = document.getElementById('wrapEditProductoEnfermedades');
            if (this.checked) {
                wrap.classList.remove('hidden');
                cargarEnfermedadesEnModalEditar([]);
            } else {
                wrap.classList.add('hidden');
            }
        });
        document.getElementById('modalEditarProductoCerrar').addEventListener('click', cerrarModalEditarProducto);
        document.getElementById('btnCancelarEditarProducto').addEventListener('click', cerrarModalEditarProducto);
        document.getElementById('modalEditarProducto').addEventListener('click', function(e) { if (e.target === this) cerrarModalEditarProducto(); });
        document.getElementById('formEditarProducto').addEventListener('submit', function(e) {
            e.preventDefault();
            var codigo = document.getElementById('editProductoCodigo').value.trim();
            var tcodprove = document.getElementById('editProductoProveedor').value.trim();
            var unidad = document.getElementById('editProductoUnidad').value.trim();
            var dosis = document.getElementById('editProductoDosis').value.trim();
            var es_vacuna = document.getElementById('editProductoEsVacuna').checked ? 1 : 0;
            var cod_enfermedades = [];
            document.querySelectorAll('#wrapCheckboxEditEnfermedades input[name="cod_enfermedades[]"]:checked').forEach(function(cb) { cod_enfermedades.push(cb.value); });
            var fd = new FormData();
            fd.append('codigo', codigo);
            fd.append('tcodprove', tcodprove);
            fd.append('unidad', unidad);
            fd.append('dosis', dosis);
            fd.append('es_vacuna', es_vacuna);
            cod_enfermedades.forEach(function(c) { fd.append('cod_enfermedades[]', c); });
            fetch(baseUrl + 'actualizar_mitm_producto.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Guardado', text: res.message }); else alert(res.message);
                        cerrarModalEditarProducto();
                        cargarProductosFiltrados();
                    } else {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo guardar.' }); else alert(res.message || 'Error');
                    }
                })
                .catch(function() { if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }); });
        });

        cargarLineas();
        cargarAlmacenes();
        initSelect2FiltroProveedor();
    })();
    </script>
</body>
</html>
