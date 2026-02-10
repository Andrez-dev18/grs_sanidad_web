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
    <title>Programas - Planificación</title>
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
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
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
        .dataTables_wrapper .dataTables_paginate { padding: 1rem; }
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
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%) !important;
            color: white !important;
            border: 1px solid #1e40af !important;
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
            <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Programas</h2>
                <button type="button" id="btnNuevoPrograma" class="btn-primary">
                    <i class="fas fa-plus"></i> Nuevo programa
                </button>
            </div>

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
                <p id="tablaProgramasMensaje" class="text-sm text-gray-500 mt-2">Cargando...</p>
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

    <!-- Modal Registrar programa -->
    <div id="modalPrograma" class="modal-overlay hidden" aria-hidden="true">
        <div class="modal-box overflow-x-auto overflow-y-auto max-h-[90vh]" style="max-width: 95%;" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-800">Registrar programa</h3>
                <button type="button" id="modalCerrar" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" aria-label="Cerrar">&times;</button>
            </div>
            <form id="formPrograma">
                <div class="modal-body space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de programa *</label>
                            <select id="tipo" name="codTipo" class="form-control" required>
                                <option value="">Seleccione tipo...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código del programa</label>
                            <input type="text" id="codigo" name="codigo" class="form-control bg-gray-100" readonly>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del programa *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Necropsia campaña 2026" required>
                        </div>
                        <div id="wrapCabeceraZona">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Zona</label>
                            <input type="text" id="zona" name="zona" class="form-control" placeholder="Ej: La Joya" maxlength="100" list="zonasList" autocomplete="off">
                            <datalist id="zonasList">
                                <option value="Mollendo">
                                <option value="La Joya">
                            </datalist>
                        </div>
                        <div id="wrapCabeceraDespliegue">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Despliegue</label>
                            <input type="text" id="despliegue" name="despliegue" class="form-control" placeholder="Despliegue" maxlength="200" list="desplieguesList" autocomplete="off">
                            <datalist id="desplieguesList">
                                <option value="GRS">
                                <option value="Piloto">
                            </datalist>
                        </div>
                        <div id="wrapCabeceraDescripcion">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <input type="text" id="descripcion" name="descripcion" class="form-control" placeholder="Descripción del programa" maxlength="500">
                        </div>
                    </div>

                        <!-- Detalle: mismo formato para todos los tipos -->
                        <div id="bloqueDetalle" class="bloque-detalle mt-4 pt-4 border-t border-gray-200">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Número de filas <span class="text-red-500">*</span></label>
                                <input type="number" id="numeroSolicitudes" name="numeroSolicitudes" min="1" max="50" placeholder="Cantidad"
                                    class="form-control max-w-xs">
                            </div>
                            <div id="solicitudesContainer" class="hidden mt-3 overflow-x-auto overflow-y-visible">
                                <table class="min-w-full border border-gray-200 rounded-lg text-sm" id="tablaSolicitudes" style="min-width: 900px;">
                                    <thead class="bg-gray-100" id="solicitudesThead"></thead>
                                    <tbody id="solicitudesBody"></tbody>
                                </table>
                            </div>
                            <p id="solicitudesMsgTipo" class="hidden text-amber-600 text-sm mt-1">Seleccione primero el tipo de programa.</p>
                        </div>
                    </div>
                <div class="modal-footer">
                    <button type="button" id="btnCancelarForm" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function cargarTipos() {
            return fetch('get_tipos_programa.php')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    const sel = document.getElementById('tipo');
                    sel.innerHTML = '<option value="">Seleccione tipo...</option>';
                    (res.data || []).forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.codigo;
                        opt.textContent = t.nombre;
                        opt.dataset.nombre = t.nombre || '';
                        opt.dataset.sigla = (t.sigla || '').trim().toUpperCase();
                        opt.dataset.campos = t.campos ? JSON.stringify(t.campos) : '{}';
                        sel.appendChild(opt);
                    });
                })
                .catch(() => {});
        }

        function generarCodigoPorSigla(sigla) {
            if (!sigla) { document.getElementById('codigo').value = ''; return; }
            fetch('generar_codigo_nec.php?sigla=' + encodeURIComponent(sigla))
                .then(r => r.json())
                .then(res => {
                    if (res.success) document.getElementById('codigo').value = res.codigo || '';
                    else document.getElementById('codigo').value = '';
                })
                .catch(() => { document.getElementById('codigo').value = ''; });
        }

        var solicitudesData = {};

        function getSiglaActual() {
            var tipo = document.getElementById('tipo');
            if (!tipo || !tipo.value) return '';
            var opt = tipo.options[tipo.selectedIndex];
            var s = (opt && opt.dataset.sigla) ? String(opt.dataset.sigla).toUpperCase() : '';
            if (s === 'NEC') s = 'NC'; // compatibilidad
            return s;
        }

        function getCamposActual() {
            var tipo = document.getElementById('tipo');
            if (!tipo || !tipo.value) return null;
            var opt = tipo.options[tipo.selectedIndex];
            if (!opt || !opt.dataset.campos) return null;
            try { return JSON.parse(opt.dataset.campos); } catch (e) { return null; }
        }

        function getColumnasFromCampos(campos) {
            if (!campos) return ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'edad'];
            var cols = ['num'];
            if (campos.ubicacion === 1) cols.push('ubicacion');
            if (campos.producto === 1) cols.push('producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna');
            if (campos.unidades === 1 && cols.indexOf('unidad') === -1) cols.push('unidad');
            if (campos.unidad_dosis === 1) cols.push('unidadDosis');
            if (campos.numero_frascos === 1) cols.push('numeroFrascos');
            if (campos.edad_aplicacion === 1) cols.push('edad');
            if (campos.area_galpon === 1) cols.push('area_galpon');
            if (campos.cantidad_por_galpon === 1) cols.push('cantidad_por_galpon');
            if (cols.length === 1) cols.push('ubicacion', 'edad');
            return cols;
        }

        var LABELS = {
            num: '#', ubicacion: 'Ubicación', producto: 'Producto', proveedor: 'Proveedor', unidad: 'Unidad',
            dosis: 'Dosis', descripcion_vacuna: 'Descripcion', numeroFrascos: 'Nº frascos', edad: 'Edad',
            unidadDosis: 'Unid. dosis', area_galpon: 'Área galpón', cantidad_por_galpon: 'Cant. por galpón'
        };

        function buildThead(campos) {
            var thead = document.getElementById('solicitudesThead');
            if (!thead) return;
            var cols = getColumnasFromCampos(campos);
            var html = '<tr>';
            cols.forEach(function(k) { html += '<th class="px-2 py-2 text-left border-b font-semibold text-gray-700">' + (LABELS[k] || k) + '</th>'; });
            html += '</tr>';
            thead.innerHTML = html;
        }

        function buildRowHtml(campos, i) {
            var cols = getColumnasFromCampos(campos);
            var parts = [];
            var estiloEdad = 'min-width:100px';
            var anchoUbicacion = 'min-width:200px';
            var anchoProveedor = 'min-width:200px';
            var anchoUnidad = 'min-width:120px';
            var anchoDosis = 'min-width:130px';
            var anchoUnidDosis = 'min-width:120px';
            var anchoFrascos = 'min-width:110px';
            cols.forEach(function(k) {
                if (k === 'num') parts.push('<td class="px-2 py-2 text-gray-700">' + (i + 1) + '</td>');
                else if (k === 'ubicacion') parts.push('<td class="px-2 py-2" style="' + anchoUbicacion + '"><input type="text" id="ubicacion_' + i + '" name="ubicacion_' + i + '" class="form-control" placeholder="Ubicación" maxlength="200" style="' + anchoUbicacion + '"></td>');
                else if (k === 'producto') parts.push('<td class="px-2 py-2" style="min-width:280px;"><select id="producto_' + i + '" class="form-control select-producto-programa" name="codProducto_' + i + '" style="width:100%;min-width:260px;"><option value="">Escriba nombre del producto...</option></select></td>');
                else if (k === 'proveedor') parts.push('<td class="px-2 py-2" style="' + anchoProveedor + '"><input type="text" id="proveedor_ro_' + i + '" class="form-control bg-gray-100" readonly placeholder="-" style="' + anchoProveedor + '"></td>');
                else if (k === 'unidad') parts.push('<td class="px-2 py-2" style="' + anchoUnidad + '"><input type="text" id="unidad_ro_' + i + '" class="form-control bg-gray-100" readonly placeholder="-" style="' + anchoUnidad + '"></td>');
                else if (k === 'dosis') parts.push('<td class="px-2 py-2" style="' + anchoDosis + '"><input type="text" id="dosis_ro_' + i + '" class="form-control bg-gray-100" readonly placeholder="-" style="' + anchoDosis + '"></td>');
                else if (k === 'descripcion_vacuna') parts.push('<td class="px-2 py-2" style="min-width:280px;"><textarea id="descripcion_vacuna_ro_' + i + '" class="form-control bg-gray-100" readonly rows="5"  style="min-width:260px;white-space:pre-wrap;"></textarea></td>');
                else if (k === 'numeroFrascos') parts.push('<td class="px-2 py-2" style="' + anchoFrascos + '"><input type="text" id="numeroFrascos_' + i + '" name="numeroFrascos_' + i + '" class="form-control" placeholder="Nº frascos" maxlength="50" style="' + anchoFrascos + '"></td>');
                else if (k === 'edad') parts.push('<td class="px-2 py-2"><input type="number" id="edad_' + i + '" name="edad_' + i + '" class="form-control" min="0" max="45" placeholder="0-45" style="' + estiloEdad + '"></td>');
                else if (k === 'unidadDosis') parts.push('<td class="px-2 py-2" style="' + anchoUnidDosis + '"><input type="text" id="unidadDosis_' + i + '" name="unidadDosis_' + i + '" class="form-control" placeholder="Unid. dosis" maxlength="50" style="' + anchoUnidDosis + '"></td>');
                else if (k === 'area_galpon') parts.push('<td class="px-2 py-2"><input type="number" id="area_galpon_' + i + '" name="area_galpon_' + i + '" class="form-control" min="0" placeholder="Área" style="min-width:70px"></td>');
                else if (k === 'cantidad_por_galpon') parts.push('<td class="px-2 py-2"><input type="number" id="cantidad_por_galpon_' + i + '" name="cantidad_por_galpon_' + i + '" class="form-control" min="0" placeholder="Cant." style="min-width:70px"></td>');
            });
            return parts.join('');
        }

        function aplicarVisibilidadCabecera(campos) {
            var wrapZona = document.getElementById('wrapCabeceraZona');
            var wrapDespliegue = document.getElementById('wrapCabeceraDespliegue');
            var wrapDesc = document.getElementById('wrapCabeceraDescripcion');
            if (wrapZona) wrapZona.classList.remove('hidden');
            if (wrapDespliegue) wrapDespliegue.classList.remove('hidden');
            if (wrapDesc) wrapDesc.classList.remove('hidden');
        }

        document.getElementById('tipo').addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (this.value) {
                var sigla = (opt && opt.dataset.sigla) ? opt.dataset.sigla : '';
                generarCodigoPorSigla(sigla);
                aplicarVisibilidadCabecera(getCamposActual());
                var num = parseInt(document.getElementById('numeroSolicitudes').value, 10) || 0;
                if (num > 0) adjustSolicitudesRows(num);
            } else {
                document.getElementById('codigo').value = '';
                aplicarVisibilidadCabecera(null);
            }
        });
        var currentCampos = null;

        function initSelect2ProductoRow(selectEl) {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2 || !selectEl) return;
            var $sel = jQuery(selectEl);
            if ($sel.data('select2')) return;
            $sel.select2({
                placeholder: 'Escriba nombre del producto para buscar...',
                allowClear: true,
                width: '100%',
                dropdownParent: jQuery('#modalPrograma'),
                minimumInputLength: 0,
                ajax: {
                    url: 'get_productos_programa.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) {
                        if (data.success && data.results) return { results: data.results };
                        return { results: [] };
                    },
                    cache: true
                },
                language: { noResults: function() { return 'Sin resultados'; }, searching: function() { return 'Buscando...'; } }
            });
        }

        function onProductoChange(rowIndex) {
            var sel = document.getElementById('producto_' + rowIndex);
            if (!sel || !sel.value) return;
            if (!solicitudesData[rowIndex]) solicitudesData[rowIndex] = {};
            fetch('get_datos_producto_programa.php?codigo=' + encodeURIComponent(sel.value))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) return;
                    solicitudesData[rowIndex].codProveedor = data.codProveedor || '';
                    solicitudesData[rowIndex].nomProducto = data.nomProducto || '';
                    solicitudesData[rowIndex].dosis = data.dosis || '';
                    var descTexto = '';
                    var desc = (data.descripcionVacuna || '').trim();
                    if (data.esVacuna && desc) {
                        var enfermedades = desc.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
                        descTexto = 'Contra:\n' + enfermedades.map(function(e) { return '- ' + e; }).join('\n');
                    } else {
                        descTexto = desc || '';
                    }
                    solicitudesData[rowIndex].descripcionVacuna = descTexto;
                    var prov = document.getElementById('proveedor_ro_' + rowIndex);
                    var unid = document.getElementById('unidad_ro_' + rowIndex);
                    var dosisRo = document.getElementById('dosis_ro_' + rowIndex);
                    var descVac = document.getElementById('descripcion_vacuna_ro_' + rowIndex);
                    if (prov) prov.value = data.nomProveedor || '';
                    if (unid) unid.value = data.unidad || '';
                    if (dosisRo) dosisRo.value = data.dosis || '';
                    if (descVac) descVac.value = descTexto;
                    var ud = document.getElementById('unidadDosis_' + rowIndex);
                    var nf = document.getElementById('numeroFrascos_' + rowIndex);
                    if (ud && nf) {
                        var sigla = getSiglaActual();
                        if (sigla === 'PL' || sigla === 'GR') {
                            if (data.esVacuna) { ud.disabled = false; ud.value = ''; nf.disabled = false; nf.value = ''; }
                            else { ud.disabled = true; ud.value = ''; nf.disabled = true; nf.value = ''; }
                        } else { ud.disabled = false; nf.disabled = false; }
                    }
                })
                .catch(function() {});
        }

        function adjustSolicitudesRows(count) {
            var tbody = document.getElementById('solicitudesBody');
            var container = document.getElementById('solicitudesContainer');
            var msgTipo = document.getElementById('solicitudesMsgTipo');
            if (!tbody) return;
            currentCampos = getCamposActual();
            if (count < 1) {
                container.classList.add('hidden');
                if (msgTipo) msgTipo.classList.add('hidden');
                tbody.innerHTML = '';
                document.getElementById('solicitudesThead').innerHTML = '';
                solicitudesData = {};
                return;
            }
            if (!document.getElementById('tipo').value) {
                if (msgTipo) { msgTipo.classList.remove('hidden'); msgTipo.textContent = 'Seleccione primero el tipo de programa.'; }
                container.classList.add('hidden');
                return;
            }
            if (msgTipo) msgTipo.classList.add('hidden');
            container.classList.remove('hidden');
            buildThead(currentCampos);
            var current = tbody.querySelectorAll('tr').length;
            if (count > current) {
                for (var i = current; i < count; i++) {
                    solicitudesData[i] = solicitudesData[i] || { ubicacion: '', codProducto: '', nomProducto: '', codProveedor: '', nomProveedor: '', unidad: '', dosis: '', descripcionVacuna: '', unidadDosis: '', numeroFrascos: '', edad: '', areaGalpon: '', cantidadPorGalpon: '' };
                    var tr = document.createElement('tr');
                    tr.className = 'border-b border-gray-200';
                    tr.innerHTML = buildRowHtml(currentCampos, i);
                    tbody.appendChild(tr);
                    var inpUb = document.getElementById('ubicacion_' + i);
                    if (inpUb && solicitudesData[i].ubicacion) inpUb.value = solicitudesData[i].ubicacion;
                    var inpDosisRo = document.getElementById('dosis_ro_' + i);
                    if (inpDosisRo && solicitudesData[i].dosis) inpDosisRo.value = solicitudesData[i].dosis;
                    var inpDescVac = document.getElementById('descripcion_vacuna_ro_' + i);
                    if (inpDescVac && solicitudesData[i].descripcionVacuna) inpDescVac.value = solicitudesData[i].descripcionVacuna;
                    var inpEdad = document.getElementById('edad_' + i);
                    if (inpEdad && solicitudesData[i].edad !== undefined && solicitudesData[i].edad !== '') inpEdad.value = solicitudesData[i].edad;
                    var ud = document.getElementById('unidadDosis_' + i);
                    var nf = document.getElementById('numeroFrascos_' + i);
                    if (sigla === 'PL' || sigla === 'GR') { if (ud) ud.disabled = true; if (nf) nf.disabled = true; }
                    initSelect2ProductoRow(document.getElementById('producto_' + i));
                    (function(idx) {
                        jQuery('#producto_' + idx).off('select2:select').on('select2:select', function() { onProductoChange(idx); });
                    })(i);
                }
            } else if (count < current) {
                for (var j = current - 1; j >= count; j--) {
                    var trs = tbody.querySelectorAll('tr');
                    if (trs[j]) trs[j].remove();
                    delete solicitudesData[j];
                }
            } else {
                for (var i = 0; i < count; i++) {
                    var tr = tbody.querySelectorAll('tr')[i];
                    if (tr) {
                        tr.innerHTML = buildRowHtml(currentCampos, i);
                        var inpUb = document.getElementById('ubicacion_' + i);
                        if (inpUb && solicitudesData[i] && solicitudesData[i].ubicacion) inpUb.value = solicitudesData[i].ubicacion;
                        var inpDosisRo = document.getElementById('dosis_ro_' + i);
                        if (inpDosisRo && solicitudesData[i] && solicitudesData[i].dosis) inpDosisRo.value = solicitudesData[i].dosis;
                        var inpDescVac = document.getElementById('descripcion_vacuna_ro_' + i);
                        if (inpDescVac && solicitudesData[i] && solicitudesData[i].descripcionVacuna) inpDescVac.value = solicitudesData[i].descripcionVacuna;
                        var inpEdad = document.getElementById('edad_' + i);
                        if (inpEdad && solicitudesData[i] && solicitudesData[i].edad !== undefined && solicitudesData[i].edad !== '') inpEdad.value = solicitudesData[i].edad;
                        initSelect2ProductoRow(document.getElementById('producto_' + i));
                        (function(idx) {
                            jQuery('#producto_' + idx).off('select2:select').on('select2:select', function() { onProductoChange(idx); });
                        })(i);
                    }
                }
            }
        }

        function handleNumeroSolicitudesChange() {
            var inp = document.getElementById('numeroSolicitudes');
            var val = parseInt(inp.value, 10) || 0;
            if (val < 1) {
                inp.value = '';
                adjustSolicitudesRows(0);
                return;
            }
            if (val > 50) val = 50;
            inp.value = val;
            adjustSolicitudesRows(val);
        }

        document.getElementById('numeroSolicitudes').addEventListener('input', handleNumeroSolicitudesChange);
        document.getElementById('numeroSolicitudes').addEventListener('change', handleNumeroSolicitudesChange);

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

        function cargarListado() {
            var params = getParametrosFiltro();
            var url = 'listar_programas_filtrado.php?codTipo=' + encodeURIComponent(params.codTipo) + '&periodoTipo=' + encodeURIComponent(params.periodoTipo) + '&fechaUnica=' + encodeURIComponent(params.fechaUnica) + '&fechaInicio=' + encodeURIComponent(params.fechaInicio) + '&fechaFin=' + encodeURIComponent(params.fechaFin) + '&mesUnico=' + encodeURIComponent(params.mesUnico) + '&mesInicio=' + encodeURIComponent(params.mesInicio) + '&mesFin=' + encodeURIComponent(params.mesFin) + '&zona=' + encodeURIComponent(params.zona) + '&despliegue=' + encodeURIComponent(params.despliegue);
            var tbody = document.getElementById('tablaProgramasBody');
            var msg = document.getElementById('tablaProgramasMensaje');
            if (!tbody) return;
            if (msg) msg.textContent = 'Cargando...';
            tbody.innerHTML = '';
            window._detallesPorPrograma = {};
            fetch(url)
                .then(r => r.json())
                .then(res => {
                    if (msg) msg.textContent = '';
                    if (!res.success) {
                        if (msg) msg.textContent = 'Error al cargar.';
                        return;
                    }
                    var data = res.data || [];
                    if (msg) msg.textContent = data.length === 0 ? 'Sin resultados.' : data.length + ' programa(s).';
                    if ($.fn.DataTable.isDataTable('#tablaProgramas')) {
                        $('#tablaProgramas').DataTable().destroy();
                    }
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
                .catch(function() { if (msg) msg.textContent = 'Error de conexión.'; });
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
                    tr.innerHTML = '<td class="px-3 py-2">' + (i + 1) + '</td><td class="px-3 py-2">' + esc(d.ubicacion) + '</td><td class="px-3 py-2">' + esc(d.nomProducto || d.codProducto) + '</td><td class="px-3 py-2">' + esc(d.nomProveedor) + '</td><td class="px-3 py-2">' + esc(d.unidades) + '</td><td class="px-3 py-2">' + esc(d.dosis) + '</td><td class="px-3 py-2">' + esc(d.descripcionVacuna) + '</td><td class="px-3 py-2">' + esc(d.numeroFrascos) + '</td><td class="px-3 py-2">' + (d.edad !== null && d.edad !== undefined && d.edad !== '' ? d.edad : '') + '</td><td class="px-3 py-2">' + esc(d.unidadDosis) + '</td><td class="px-3 py-2">' + (d.areaGalpon !== null && d.areaGalpon !== undefined && d.areaGalpon !== '' ? d.areaGalpon : '') + '</td><td class="px-3 py-2">' + (d.cantidadPorGalpon !== null && d.cantidadPorGalpon !== undefined && d.cantidadPorGalpon !== '' ? d.cantidadPorGalpon : '') + '</td>';
                    tbody.appendChild(tr);
                });
            }
            document.getElementById('modalDetallesPrograma').classList.remove('hidden');
        }

        function cerrarModalDetalles() {
            document.getElementById('modalDetallesPrograma').classList.add('hidden');
        }

        function abrirModal() {
            document.getElementById('formPrograma').reset();
            document.getElementById('codigo').value = '';
            document.getElementById('numeroSolicitudes').value = '';
            document.getElementById('solicitudesContainer').classList.add('hidden');
            document.getElementById('solicitudesBody').innerHTML = '';
            document.getElementById('solicitudesThead').innerHTML = '';
            var msgTipo = document.getElementById('solicitudesMsgTipo');
            if (msgTipo) msgTipo.classList.add('hidden');
            solicitudesData = {};
            aplicarVisibilidadCabecera(null);
            document.getElementById('modalPrograma').classList.remove('hidden');
            cargarTipos();
        }

        function cerrarModal() {
            document.getElementById('modalPrograma').classList.add('hidden');
        }

        document.getElementById('btnNuevoPrograma').addEventListener('click', abrirModal);
        document.getElementById('btnCancelarForm').addEventListener('click', cerrarModal);
        document.getElementById('modalCerrar').addEventListener('click', cerrarModal);
        document.getElementById('modalPrograma').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });

        document.getElementById('formPrograma').addEventListener('submit', function(e) {
            e.preventDefault();
            var tipo = document.getElementById('tipo');
            var codTipo = tipo.value;
            var nomTipo = tipo.options[tipo.selectedIndex] ? tipo.options[tipo.selectedIndex].textContent : '';
            var codigo = document.getElementById('codigo').value.trim();
            var nombre = document.getElementById('nombre').value.trim();
            var zona = document.getElementById('zona') ? document.getElementById('zona').value.trim() : '';
            var despliegue = document.getElementById('despliegue') ? document.getElementById('despliegue').value.trim() : '';
            var descripcion = document.getElementById('descripcion') ? document.getElementById('descripcion').value.trim() : '';
            if (!codTipo || !codigo || !nombre) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Complete tipo, código y nombre.' });
                return;
            }
            var numSol = parseInt(document.getElementById('numeroSolicitudes').value, 10) || 0;
            if (numSol < 1) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Indique al menos 1 fila en el detalle.' });
                return;
            }
            var sigla = getSiglaActual();
            var detalles = [];
            for (var s = 0; s < numSol; s++) {
                var ub = document.getElementById('ubicacion_' + s) ? document.getElementById('ubicacion_' + s).value.trim() : '';
                var selProd = document.getElementById('producto_' + s);
                var codProducto = selProd ? (selProd.value || '').trim() : '';
                var nomProducto = selProd && selProd.options[selProd.selectedIndex] ? selProd.options[selProd.selectedIndex].textContent : '';
                var codProveedor = (solicitudesData[s] && solicitudesData[s].codProveedor) ? solicitudesData[s].codProveedor : '';
                var nomProveedor = document.getElementById('proveedor_ro_' + s) ? document.getElementById('proveedor_ro_' + s).value.trim() : '';
                if (solicitudesData[s] && solicitudesData[s].nomProducto) nomProducto = solicitudesData[s].nomProducto;
                var unidadDosis = document.getElementById('unidadDosis_' + s) ? document.getElementById('unidadDosis_' + s).value.trim() : '';
                var numeroFrascos = document.getElementById('numeroFrascos_' + s) ? document.getElementById('numeroFrascos_' + s).value.trim() : '';
                var edadEl = document.getElementById('edad_' + s);
                var edad = edadEl ? (parseInt(edadEl.value, 10) || 0) : 0;
                if (edad < 0) edad = 0;
                if (edad > 45) edad = 45;
                var descVacEl = document.getElementById('descripcion_vacuna_ro_' + s);
                var descripcionVacuna = descVacEl ? descVacEl.value.trim() : (solicitudesData[s] && solicitudesData[s].descripcionVacuna ? solicitudesData[s].descripcionVacuna : '');
                var areaGalponEl = document.getElementById('area_galpon_' + s);
                var areaGalpon = areaGalponEl ? (parseInt(areaGalponEl.value, 10) || null) : null;
                var cantGalponEl = document.getElementById('cantidad_por_galpon_' + s);
                var cantidadPorGalpon = cantGalponEl ? (parseInt(cantGalponEl.value, 10) || null) : null;
                var unidades = (document.getElementById('unidad_ro_' + s) ? document.getElementById('unidad_ro_' + s).value.trim() : '') || '';
                var dosis = (solicitudesData[s] && solicitudesData[s].dosis) ? solicitudesData[s].dosis : '';
                detalles.push({
                    ubicacion: ub,
                    codProducto: codProducto,
                    nomProducto: nomProducto,
                    codProveedor: codProveedor,
                    nomProveedor: nomProveedor,
                    unidades: unidades,
                    dosis: dosis,
                    unidadDosis: unidadDosis,
                    numeroFrascos: numeroFrascos,
                    edad: edad,
                    descripcionVacuna: descripcionVacuna,
                    areaGalpon: areaGalpon,
                    cantidadPorGalpon: cantidadPorGalpon
                });
            }
            var payload = { codigo: codigo, nombre: nombre, codTipo: parseInt(codTipo, 10), nomTipo: nomTipo, sigla: sigla, zona: zona, despliegue: despliegue, descripcion: descripcion, detalles: detalles };
            fetch('guardar_programa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Guardado', text: res.message });
                    cerrarModal();
                    cargarListado();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo guardar.' });
                }
            })
            .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }); });
        });

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

        cargarTipos();
        cargarTiposParaFiltro();
        cargarListado();
    </script>
</body>
</html>
