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

// La página no ejecuta consultas; los datos se cargan por AJAX (get_tipos_programa, guardar_programa, etc.).
// No abrir conexión aquí para evitar agotar el límite de conexiones de MySQL.
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
        .table-wrapper {
            overflow-x: auto;
            overflow-y: visible;
            width: 100%;
            border-radius: 1rem;
        }
        .table-wrapper::-webkit-scrollbar { height: 10px; }
        .table-wrapper::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        .table-wrapper::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; }
        .data-table {
            width: 100% !important;
            border-collapse: collapse;
            min-width: 800px;
        }
        .data-table th,
        .data-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.875rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .data-table th {
            background: #2563eb !important;
            font-weight: 600;
            color: #ffffff !important;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .data-table tbody tr:hover {
            background-color: #eff6ff !important;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { padding: 1rem; font-weight: normal; }
        .dataTables_wrapper .dataTables_length select {
            padding: 0.5rem;
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
        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.hidden { display: none; }
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
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border">

        <!-- Card Programas: filtros + tabla única + botón exportar PDF -->
        <div class="card-filtros-compacta mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <!-- Filtros de búsqueda (estilo reportes, desplegado por defecto) -->
            <div class="border-b border-gray-200">
                <button type="button" id="btnToggleFiltrosProgramas"
                    class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🔎</span>
                        <h3 class="text-base font-semibold text-gray-800">Filtros de búsqueda</h3>
                    </div>
                    <svg id="iconoFiltrosProgramas" class="w-5 h-5 text-gray-600 transition-transform duration-300 rotate-180"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="contenidoFiltrosProgramas" class="px-6 pb-6 pt-4">
                    <!-- Fila 1: Periodo -->
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
                            <input id="mesUnico" type="month" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
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

            <div class="px-6 pb-6 pt-4">
                <div class="table-wrapper overflow-x-auto">
                    <table id="tablaProgramas" class="data-table w-full text-sm config-table">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left">N°</th>
                                <th class="px-4 py-3 text-left">Código</th>
                                <th class="px-4 py-3 text-left">Nombre</th>
                                <th class="px-4 py-3 text-left">Tipo</th>
                                <th class="px-4 py-3 text-left">Zona</th>
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
                    <table class="data-table w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-2 text-left">#</th>
                                <th class="px-3 py-2 text-left">Ubicación</th>
                                <th class="px-3 py-2 text-left">Producto</th>
                                <th class="px-3 py-2 text-left">Proveedor</th>
                                <th class="px-3 py-2 text-left">Unidad</th>
                                <th class="px-3 py-2 text-left">Dosis</th>
                                <th class="px-3 py-2 text-left">Descripcion</th>
                                <th class="px-3 py-2 text-left">Nº frascos</th>
                                <th class="px-3 py-2 text-left">Edad</th>
                                <th class="px-3 py-2 text-left">Unid. dosis</th>
                                <th class="px-3 py-2 text-left">Área galpón</th>
                                <th class="px-3 py-2 text-left">Cant. galpón</th>
                            </tr>
                        </thead>
                        <tbody id="modalDetallesBody"></tbody>
                    </table>
                </div>
                <p id="modalDetallesSinRegistros" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        window._detallesPorPrograma = {};

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
            fetch(url, { cache: 'no-store' })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    var data = res.data || [];
                    if ($.fn.DataTable.isDataTable('#tablaProgramas')) {
                        $('#tablaProgramas').DataTable().destroy();
                    }
                    tbody.innerHTML = '';
                    data.forEach(function(item, idx) {
                        var cab = item.cab || {};
                        var codigo = cab.codigo || '';
                        window._detallesPorPrograma[codigo] = item.detalles || [];
                        var tr = document.createElement('tr');
                        tr.className = 'border-b border-gray-200 hover:bg-gray-50';
                        var reporteUrl = 'generar_reporte_programa.php?codigo=' + encodeURIComponent(codigo);
                        tr.innerHTML = '<td class="px-4 py-3">' + (idx + 1) + '</td>' +
                            '<td class="px-4 py-3">' + esc(codigo) + '</td>' +
                            '<td class="px-4 py-3">' + esc(cab.nombre) + '</td>' +
                            '<td class="px-4 py-3">' + esc(cab.nomTipo) + '</td>' +
                            '<td class="px-4 py-3">' + esc(cab.zona) + '</td>' +
                            '<td class="px-4 py-3">' + esc(cab.despliegue) + '</td>' +
                            '<td class="px-4 py-3">' + formatearFecha(cab.fechaHoraRegistro) + '</td>' +
                            '<td class="px-4 py-3 text-center"><button type="button" class="btn-detalles-programa px-3 py-1.5 text-blue-600 hover:bg-blue-50 rounded-lg text-sm font-medium" data-codigo="' + (codigo || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;') + '" title="Ver detalle"><i class="fas fa-list mr-1"></i> Detalles</button></td>' +
                            '<td class="px-4 py-3 text-center"><a href="' + reporteUrl + '" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-9 h-9 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition" title="Ver reporte PDF"><i class="fas fa-file-pdf text-lg"></i></a></td>';
                        tbody.appendChild(tr);
                    });
                    tbody.querySelectorAll('.btn-detalles-programa').forEach(function(btn) {
                        btn.addEventListener('click', function() { abrirModalDetalles(this.getAttribute('data-codigo')); });
                    });
                    $('#tablaProgramas').DataTable({
                        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                        order: [[6, 'desc']]
                    });
                })
                .catch(function() {});
        }

        function abrirModalDetalles(codigo) {
            if (!codigo) return;
            var detalles = window._detallesPorPrograma[codigo];
            if (!detalles) detalles = [];
            document.getElementById('modalDetallesTitulo').textContent = 'Detalles - ' + codigo;
            var tbody = document.getElementById('modalDetallesBody');
            var sinReg = document.getElementById('modalDetallesSinRegistros');
            tbody.innerHTML = '';
            if (detalles.length === 0) {
                sinReg.classList.remove('hidden');
            } else {
                sinReg.classList.add('hidden');
                detalles.forEach(function(d, i) {
                    var tr = document.createElement('tr');
                    tr.className = 'border-b border-gray-200';
                    tr.innerHTML = '<td class="px-3 py-2">' + (i + 1) + '</td><td class="px-3 py-2">' + esc(d.ubicacion) + '</td><td class="px-3 py-2">' + esc(d.nomProducto || d.codProducto) + '</td><td class="px-3 py-2">' + esc(d.nomProveedor) + '</td><td class="px-3 py-2">' + esc(d.unidades) + '</td><td class="px-3 py-2">' + esc(d.dosis) + '</td><td class="px-3 py-2" style="white-space:pre-wrap;">' + esc(formatearDescripcionVacuna(d.descripcionVacuna)) + '</td><td class="px-3 py-2">' + esc(d.numeroFrascos) + '</td><td class="px-3 py-2">' + (d.edad !== null && d.edad !== undefined && d.edad !== '' ? d.edad : '') + '</td><td class="px-3 py-2">' + esc(d.unidadDosis) + '</td><td class="px-3 py-2">' + (d.areaGalpon !== null && d.areaGalpon !== undefined && d.areaGalpon !== '' ? d.areaGalpon : '') + '</td><td class="px-3 py-2">' + (d.cantidadPorGalpon !== null && d.cantidadPorGalpon !== undefined && d.cantidadPorGalpon !== '' ? d.cantidadPorGalpon : '') + '</td>';
                    tbody.appendChild(tr);
                });
            }
            document.getElementById('modalDetallesPrograma').classList.remove('hidden');
        }

        function cerrarModalDetalles() {
            document.getElementById('modalDetallesPrograma').classList.add('hidden');
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
            if (pt) pt.value = 'POR_FECHA';
            ['fechaInicio','fechaFin','mesUnico','mesInicio','mesFin'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.value = '';
            });
            var fu = document.getElementById('fechaUnica');
            if (fu) fu.value = new Date().toISOString().slice(0, 10);
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

        cargarTiposParaFiltro();
        cargarListado();
    </script>
</body>
</html>
