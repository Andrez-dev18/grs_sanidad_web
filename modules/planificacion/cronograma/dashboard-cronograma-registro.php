<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; } else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronograma - Registro</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f8f9fa; font-family: system-ui, sans-serif; }
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none; padding: 0.625rem 1.5rem; font-size: 0.875rem; font-weight: 600;
            color: white; border-radius: 0.75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-primary:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); transform: translateY(-2px); }
        .form-control { width: 100%; padding: 0.625rem 1rem; border: 1px solid #d1d5db; border-radius: 0.75rem; font-size: 0.875rem; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
        .bloque-especifico { display: none; }
        .bloque-especifico.visible { display: block; }
        #fechasResultado, #fechasResultadoZonas { margin-top: 1rem; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; font-size: 0.875rem; border: 1px solid #e2e8f0; }
        #fechasResultado ul { margin: 0; padding-left: 1.25rem; }
        /* Tabla fechas cronograma: mismo estilo que dashboard-seguimiento */
        .tabla-fechas-crono { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .tabla-fechas-crono th {
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
            font-weight: 600;
            color: #ffffff !important;
            padding: 0.75rem 1rem;
            text-align: left;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .tabla-fechas-crono td { padding: 0.5rem 1rem; border-bottom: 1px solid #e5e7eb; }
        .tabla-fechas-crono tbody tr:nth-child(even) { background: #f9fafb; }
        .tabla-fechas-crono tbody tr:hover { background-color: #eff6ff !important; }
        .tabla-fechas-crono tbody tr:last-child td { border-bottom: none; }
        /* Wrapper tipo seguimiento (mismo estilo que tablaSeguimientoWrapper) */
        .tabla-crono-wrapper { background: #fff; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1); padding: 1.25rem; }
        .tabla-crono-wrapper .table-wrapper { overflow-x: auto; }
        /* Toolbar superior: Mostrar + Buscar (como seguimiento) */
        .tabla-crono-toolbar-top { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1rem; }
        @media (min-width: 768px) { .tabla-crono-toolbar-top { flex-direction: row; align-items: center; justify-content: space-between; } }
        .tabla-crono-toolbar-top .toolbar-length { display: flex; align-items: center; gap: 0.5rem; }
        .tabla-crono-toolbar-top .toolbar-length span { font-size: 0.875rem; color: #374151; }
        .tabla-crono-toolbar-top .toolbar-length select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            margin: 0 0.5rem;
            font-size: 0.875rem;
        }
        .tabla-crono-toolbar-top .toolbar-filter input {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            margin-left: 0.5rem;
            font-size: 0.875rem;
            min-width: 180px;
        }
        /* Toolbar inferior: info + paginado (como seguimiento) */
        .tabla-crono-toolbar-bottom { display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; color: #4b5563; }
        @media (min-width: 768px) { .tabla-crono-toolbar-bottom { flex-direction: row; align-items: center; justify-content: space-between; } }
        .tabla-crono-toolbar-bottom .paginacion-controles { display: flex; align-items: center; gap: 0.5rem; }
        .tabla-crono-toolbar-bottom .paginacion-controles button {
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            background: #fff;
            font-size: 0.875rem;
            cursor: pointer;
        }
        .tabla-crono-toolbar-bottom .paginacion-controles button:hover:not(:disabled) { background: #eff6ff; color: #1d4ed8; }
        .tabla-crono-toolbar-bottom .paginacion-controles button:disabled { opacity: 0.5; cursor: not-allowed; }
        .tabla-crono-toolbar-bottom .paginacion-controles .btn-page-current {
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%) !important;
            color: white !important;
            border: 1px solid #1e40af !important;
        }
        /* Unificar estilo del select Código del programa con el resto */
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            padding: 0.5rem 1rem !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.75rem !important;
            font-size: 0.875rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 26px !important; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px !important; }
        #cronoPrograma + .select2-container { width: 100% !important; }
        .zona-subzonas { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem; }
        .zona-subzonas h4 { font-size: 0.9rem; color: #475569; margin-bottom: 0.75rem; }
        .subzona-chk { display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0; }
        .subzona-chk label { cursor: pointer; font-size: 0.875rem; }
        .subzona-chk .granja-nom { font-size: 0.75rem; color: #64748b; margin-left: 0.25rem; }
        .campanias-chk { display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; }
        .campanias-chk label { cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; gap: 0.35rem; }
        /* Popover flotante (zona): debajo del ícono, fuera de contenedores */
        #popoverInfoZonaFlotante { position: fixed; z-index: 9999; min-width: 220px; max-width: 280px; padding: 8px 10px; font-size: 0.75rem; line-height: 1.35; color: #374151; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); white-space: normal; display: none; }
        #popoverInfoZonaFlotante.visible { display: block; }
        #modalCargaCrono.hidden { display: none !important; }
        #modalCargaCrono { display: flex; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .modal-overlay.hidden { display: none; }
        .modal-box { background: white; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-width: 95%; width: 100%; max-width: 900px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; }
        .modal-box .modal-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .modal-box .modal-body { padding: 1rem 1.25rem; overflow-y: auto; flex: 1; }
        .btn-ver-crono-detalle { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; color: #4b5563; border-radius: 0.5rem; }
        .btn-ver-crono-detalle:hover { color: #2563eb; background: #eff6ff; }
        .tabs-crono-resultado { display: flex; gap: 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 0.75rem; }
        .tabs-crono-resultado .tab-btn { padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; color: #64748b; background: transparent; border: none; border-bottom: 2px solid transparent; cursor: pointer; margin-bottom: -1px; }
        .tabs-crono-resultado .tab-btn:hover { color: #2563eb; }
        .tabs-crono-resultado .tab-btn.active { color: #2563eb; border-bottom-color: #2563eb; }
        .tab-panel-crono { display: none; }
        .tab-panel-crono.active { display: block; }
        /* Crono Granjas: mismo estilo lista/iconos y controles que reportes */
        #cronoGranjasWrapperEspecifico[data-vista="iconos"] .view-lista-wrap-crono,
        #cronoGranjasWrapperZonas[data-vista="iconos"] .view-lista-wrap-crono { display: none !important; }
        #cronoGranjasWrapperEspecifico[data-vista="iconos"] .view-tarjetas-wrap-crono,
        #cronoGranjasWrapperZonas[data-vista="iconos"] .view-tarjetas-wrap-crono { display: block !important; }
        #cronoGranjasWrapperEspecifico[data-vista="lista"] .view-tarjetas-wrap-crono,
        #cronoGranjasWrapperZonas[data-vista="lista"] .view-tarjetas-wrap-crono { display: none !important; }
        #cronoGranjasWrapperEspecifico .view-tarjetas-wrap-crono,
        #cronoGranjasWrapperZonas .view-tarjetas-wrap-crono { display: none; }
        .crono-granjas-toolbar-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1rem; }
        .crono-dt-controls, .crono-iconos-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1rem; }
        .crono-iconos-controls { display: none; }
        #cronoGranjasWrapperEspecifico[data-vista="iconos"] .crono-dt-controls,
        #cronoGranjasWrapperZonas[data-vista="iconos"] .crono-dt-controls { display: none; }
        #cronoGranjasWrapperEspecifico[data-vista="iconos"] .crono-iconos-controls,
        #cronoGranjasWrapperZonas[data-vista="iconos"] .crono-iconos-controls { display: flex; }
        .crono-cards-controls-top, .crono-cards-pagination { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #4b5563; }
        .crono-cards-controls-top { margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .crono-cards-pagination { margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; }
        .crono-cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; padding: 0.5rem 0; }
        .crono-card-item { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .crono-card-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .crono-card-item .card-codigo { font-weight: 700; font-size: 1rem; color: #1e40af; margin-bottom: 0.5rem; }
        .crono-card-item .card-row { font-size: 0.8rem; color: #4b5563; margin-bottom: 0.25rem; }
        .crono-card-item .card-row .label { color: #6b7280; }
        .crono-cards-pagination .paginate_button { padding: 0.5rem 1rem; margin: 0 0.25rem; border-radius: 0.5rem; border: 1px solid #d1d5db; background: #fff; cursor: pointer; font-size: 0.875rem; }
        .crono-cards-pagination .paginate_button:hover:not(.disabled) { background: #eff6ff; color: #1d4ed8; }
        .crono-cards-pagination .paginate_button.current { background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%); color: white; border-color: #1e40af; }
        .crono-cards-pagination .paginate_button.disabled { opacity: 0.5; cursor: not-allowed; }
        .tabla-crono-toolbar-bottom .paginate_button_wrap { display: inline-flex; align-items: center; gap: 0.25rem; }
        .tabla-crono-toolbar-bottom .paginate_button { padding: 0.5rem 1rem; margin: 0 0.25rem; border-radius: 0.5rem; border: 1px solid #d1d5db; background: #fff; cursor: pointer; font-size: 0.875rem; }
        .tabla-crono-toolbar-bottom .paginate_button:hover:not(.disabled) { background: #eff6ff; color: #1d4ed8; }
        .tabla-crono-toolbar-bottom .paginate_button.current { background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%); color: white; border-color: #1e40af; }
        .tabla-crono-toolbar-bottom .paginate_button.disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Modal Ver cabecera y detalle del programa (cronograma) -->
    <div id="modalVerProgramaDetalleCrono" class="modal-overlay hidden" aria-hidden="true">
        <div class="modal-box" role="dialog" aria-modal="true">
            <div class="modal-header">
                <h3 id="modalVerProgramaDetalleCronoTitulo" class="text-lg font-semibold text-gray-800">Programa - Cabecera y detalle</h3>
                <button type="button" id="modalVerProgramaDetalleCronoCerrar" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="modal-body">
                <div id="modalVerCabeceraCrono" class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200 text-sm"></div>
                <div class="overflow-x-auto">
                    <table class="tabla-fechas-crono w-full text-sm" id="tablaModalVerDetalleCrono">
                        <thead class="bg-gray-50 border-b border-gray-200" id="modalVerDetalleTheadCrono"></thead>
                        <tbody id="modalVerDetalleBodyCrono"></tbody>
                    </table>
                </div>
                <p id="modalVerDetalleSinRegistrosCrono" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle.</p>
            </div>
        </div>
    </div>

    <div id="modalCargaCrono" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-sky-50 rounded-xl shadow-2xl p-8 text-center max-w-sm w-full">
            <img src="../../../assets/img/gallina.gif" alt="Cargando..." class="w-32 h-32 mx-auto mb-4" onerror="this.style.display='none'">
            <p class="text-lg font-semibold text-gray-800">Calculando fechas...</p>
            <p class="text-sm text-gray-600 mt-2">Por favor espere, estamos procesando el cronograma</p>
            <div class="mt-6">
                <div class="inline-block w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
        </div>
    </div>
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border">
        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de programa *</label>
                    <select id="cronoTipo" class="form-control">
                        <option value="">Seleccione tipo...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código del programa *</label>
                    <select id="cronoPrograma" class="form-control" style="width:100%;" disabled>
                        <option value="">Primero seleccione el tipo de programa</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del programa</label>
                        <input type="text" id="cronoNomProgramaDisplay" class="form-control bg-gray-100" readonly placeholder="—">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Año *</label>
                        <select id="cronoAnio" class="form-control"></select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1.5">
                        Zona *
                        <i id="infoZona" class="fas fa-info-circle text-blue-500 cursor-pointer hover:text-blue-700" role="button" title="Ver información" aria-label="Ver información"></i>
                    </label>
                    <select id="cronoZona" class="form-control" multiple style="min-height: 80px;">
                        <option value="Especifico">Especifico</option>
                    </select>
                   
                </div>
                <div id="bloqueSubzonas" class="bloque-especifico">
                    <div id="contenedorSubzonas"></div>
                </div>
                <div id="bloqueEspecifico" class="bloque-especifico space-y-4 mt-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Granja *</label>
                            <select id="cronoGranja" class="form-control">
                                <option value="">Seleccione...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Galpón *</label>
                            <select id="cronoGalpon" class="form-control" disabled>
                                <option value="">Primero seleccione granja</option>
                            </select>
                        </div>
                    </div>
                    <div id="bloqueCampanias">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Campañas *</label>
                        <div id="contenedorCampanias" class="campanias-chk"></div>
                        <p class="text-xs text-gray-500 mt-0.5">Seleccione una o más campañas para esa granja y galpón.</p>
                    </div>
                    <button type="button" id="btnAsignar" class="btn-primary w-full sm:w-auto">
                        <i class="fas fa-calendar-check mr-1"></i> Asignar
                    </button>
                    <div id="fechasResultado" class="hidden mt-2 p-3 rounded-lg text-sm">
                        <div class="tabs-crono-resultado">
                            <button type="button" class="tab-btn active" data-tab="granjas" data-context="especifico">Granjas</button>
                            <button type="button" class="tab-btn" data-tab="programa" data-context="especifico">Programa</button>
                        </div>
                        <div id="tabPanelGranjasEspecifico" class="tab-panel-crono active"></div>
                        <div id="tabPanelProgramaEspecifico" class="tab-panel-crono">
                            <div id="programaCabEspecifico" class="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"></div>
                            <div class="table-wrapper overflow-x-auto"><table class="tabla-fechas-crono config-table w-full text-sm" id="tablaProgramaEspecifico"><thead id="programaTheadEspecifico"></thead><tbody id="programaBodyEspecifico"></tbody></table></div>
                            <p id="programaSinRegEspecifico" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle del programa.</p>
                        </div>
                    </div>
                </div>
                <div id="bloqueAsignarZonas" class="bloque-especifico" style="display:none;">
                    <button type="button" id="btnAsignarZonas" class="btn-primary">
                        <i class="fas fa-calendar-check mr-1"></i> Calcular fechas
                    </button>
                    <div id="fechasResultadoZonas" class="hidden mt-2 p-3 rounded-lg text-sm">
                        <div class="tabs-crono-resultado">
                            <button type="button" class="tab-btn active" data-tab="granjas" data-context="zonas">Granjas</button>
                            <button type="button" class="tab-btn" data-tab="programa" data-context="zonas">Programa</button>
                        </div>
                        <div id="tabPanelGranjasZonas" class="tab-panel-crono active"></div>
                        <div id="tabPanelProgramaZonas" class="tab-panel-crono">
                            <div id="programaCabZonas" class="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"></div>
                            <div class="table-wrapper overflow-x-auto"><table class="tabla-fechas-crono config-table w-full text-sm" id="tablaProgramaZonas"><thead id="programaTheadZonas"></thead><tbody id="programaBodyZonas"></tbody></table></div>
                            <p id="programaSinRegZonas" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle del programa.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 pb-6 flex gap-3 justify-end">
                <button type="button" id="btnLimpiarCrono" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">Limpiar</button>
                <button type="button" id="btnGuardarCrono" class="btn-primary" disabled>
                    <i class="fas fa-save"></i> Guardar cronograma
                </button>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let fechasAsignadas = [];
        let paresCargaEjecucion = []; // [{ edad, fechaCarga, fechaEjecucion }] para modo zona
        let itemsEspecifico = [];
        let itemsZonas = []; // modo zonas: [{ granja, campania, galpon, fechas: [{ edad, fechaCarga, fechaEjecucion }] }]
        var programasMap = {};
        var granjasMap = {}; // codigo (3 chars) -> nombre granja
        var edadProgramaCrono = null; // primera edad del programa (siempre registrar)

        function formatoDDMMYYYY(ymd) {
            if (!ymd) return '';
            var s = String(ymd).trim();
            var p = s.split('-');
            if (p.length >= 3) return (p[2].length === 1 ? '0' + p[2] : p[2]) + '/' + (p[1].length === 1 ? '0' + p[1] : p[1]) + '/' + p[0];
            return s;
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
        var columnasPorSiglaReporte = {
            'NC': ['num', 'ubicacion', 'edad'],
            'PL': ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'edad'],
            'GR': ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'edad'],
            'MC': ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'area_galpon', 'cantidad_por_galpon', 'unidadDosis', 'edad'],
            'LD': ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'unidadDosis', 'edad'],
            'CP': ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'unidadDosis', 'edad']
        };
        var columnasDetalleCompletasCrono = ['ubicacion','producto','proveedor','unidad','dosis','unidadDosis','numeroFrascos','edad','descripcion_vacuna','area_galpon','cantidad_por_galpon'];
        var labelsReporteCrono = {
            num: '#', ubicacion: 'Ubicación', producto: 'Producto', proveedor: 'Proveedor', unidad: 'Unidad',
            dosis: 'Dosis', descripcion_vacuna: 'Descripcion', numeroFrascos: 'Nº frascos', edad: 'Edad',
            unidadDosis: 'Unid. dosis', area_galpon: 'Área galpón', cantidad_por_galpon: 'Cant. por galpón'
        };
        function valorCeldaDetalleCrono(k, d) {
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
        function cargarTabProgramaEnResultadoCrono(codPrograma, cabElId, theadId, tbodyId, sinRegId) {
            var cabEl = document.getElementById(cabElId);
            var theadEl = document.getElementById(theadId);
            var tbodyEl = document.getElementById(tbodyId);
            var sinRegEl = document.getElementById(sinRegId);
            if (!cabEl || !theadEl || !tbodyEl || !sinRegEl) return;
            cabEl.innerHTML = '<span class="text-gray-500">Cargando...</span>';
            theadEl.innerHTML = '';
            tbodyEl.innerHTML = '';
            sinRegEl.classList.add('hidden');
            if (!codPrograma || String(codPrograma).trim() === '') {
                cabEl.innerHTML = '<span class="text-gray-500">No hay programa seleccionado.</span>';
                return;
            }
            fetch('../programas/get_programa_cab_detalle.php?codigo=' + encodeURIComponent(codPrograma)).then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) {
                    cabEl.innerHTML = '<span class="text-red-600">' + esc(res.message || 'Error al cargar programa.') + '</span>';
                    return;
                }
                var cab = res.cab || {};
                var detalles = res.detalles || [];
                var cabHtml = '<div class="font-semibold text-gray-800 mb-1">' + esc(cab.codigo) + ' — ' + esc(cab.nombre) + '</div>';
                cabHtml += '<dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-600">';
                cabHtml += '<dt class="font-medium">Tipo</dt><dd>' + esc(cab.nomTipo || '') + '</dd>';
                if (cab.despliegue) { cabHtml += '<dt class="font-medium">Despliegue</dt><dd>' + esc(cab.despliegue) + '</dd>'; }
                if (cab.descripcion) { cabHtml += '<dt class="font-medium col-span-2">Descripción</dt><dd class="col-span-2">' + esc(cab.descripcion) + '</dd>'; }
                cabHtml += '</dl>';
                cabEl.innerHTML = cabHtml;
                // Mismo orden de columnas que tab Programas del listado: dinámicas por sigla, edad al final
                var sigla = (res.sigla || 'PL').toUpperCase();
                if (sigla === 'NEC') sigla = 'NC';
                var cols = columnasPorSiglaReporte[sigla] || columnasPorSiglaReporte['PL'];
                var colsSinNum = cols.filter(function(k) { return k !== 'num'; });
                if (colsSinNum.indexOf('edad') !== -1) {
                    colsSinNum = colsSinNum.filter(function(k) { return k !== 'edad'; });
                    colsSinNum.push('edad');
                }
                var thCells = '<th class="px-3 py-2 text-left">Código</th><th class="px-3 py-2 text-left">Nombre programa</th><th class="px-3 py-2 text-left">Despliegue</th><th class="px-3 py-2 text-left">Descripción</th>';
                colsSinNum.forEach(function(k) { thCells += '<th class="px-3 py-2 text-left">' + (labelsReporteCrono[k] || k) + '</th>'; });
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
                            td += '<td class="px-3 py-2"' + (k === 'descripcion_vacuna' ? ' style="white-space:pre-wrap;"' : '') + '>' + valorCeldaDetalleCrono(k, d) + '</td>';
                        });
                        tr.innerHTML = td;
                        tbodyEl.appendChild(tr);
                    });
                }
            }).catch(function() {
                cabEl.innerHTML = '<span class="text-red-600">Error al cargar el programa.</span>';
            });
        }
        function abrirModalVerProgramaDetalleCrono(codigo, posDetalle) {
            if (!codigo) return;
            var pos = parseInt(posDetalle, 10);
            if (isNaN(pos) || pos < 1) pos = 1;
            document.getElementById('modalVerProgramaDetalleCronoTitulo').textContent = 'Programa ' + codigo + ' - Cabecera y detalle';
            document.getElementById('modalVerCabeceraCrono').innerHTML = '<p class="text-gray-500">Cargando...</p>';
            document.getElementById('modalVerDetalleBodyCrono').innerHTML = '';
            document.getElementById('modalVerDetalleSinRegistrosCrono').classList.add('hidden');
            fetch('../programas/get_programa_cab_detalle.php?codigo=' + encodeURIComponent(codigo))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) {
                        document.getElementById('modalVerCabeceraCrono').innerHTML = '<p class="text-red-500">' + esc(res.message || 'Error') + '</p>';
                        return;
                    }
                    var cab = res.cab || {};
                    var detalles = res.detalles || [];
                    var sigla = (res.sigla || 'PL').toUpperCase();
                    if (sigla === 'NEC') sigla = 'NC';
                    var cols = columnasPorSiglaReporte[sigla] || columnasPorSiglaReporte['PL'];
                    var colsSinNum = cols.filter(function(k) { return k !== 'num'; });
                    var cabHtml = '<div class="grid grid-cols-2 gap-x-4 gap-y-1 text-gray-700">' +
                        '<span class="font-medium">Código:</span><span>' + esc(cab.codigo) + '</span>' +
                        '<span class="font-medium">Nombre:</span><span>' + esc(cab.nombre) + '</span>' +
                        '<span class="font-medium">Tipo:</span><span>' + esc(cab.nomTipo) + '</span>' +
                        '<span class="font-medium">Zona:</span><span>' + esc(cab.zona) + '</span>' +
                        '<span class="font-medium">Despliegue:</span><span>' + esc(cab.despliegue) + '</span>' +
                        '<span class="font-medium">Fecha registro:</span><span>' + (cab.fechaHoraRegistro ? formatoDDMMYYYY(cab.fechaHoraRegistro.split(' ')[0]) : '') + '</span>' +
                        (cab.descripcion ? ('<span class="font-medium">Descripción:</span><span class="col-span-1">' + esc(cab.descripcion) + '</span>') : '') +
                        '</div>';
                    document.getElementById('modalVerCabeceraCrono').innerHTML = cabHtml;
                    var thCells = '<th class="px-3 py-2 text-left">Código</th><th class="px-3 py-2 text-left">Nombre programa</th><th class="px-3 py-2 text-left">Zona</th><th class="px-3 py-2 text-left">Despliegue</th><th class="px-3 py-2 text-left">Descripción</th>';
                    colsSinNum.forEach(function(k) {
                        thCells += '<th class="px-3 py-2 text-left">' + (labelsReporteCrono[k] || k) + '</th>';
                    });
                    document.getElementById('modalVerDetalleTheadCrono').innerHTML = '<tr>' + thCells + '</tr>';
                    var tbody = document.getElementById('modalVerDetalleBodyCrono');
                    var sinReg = document.getElementById('modalVerDetalleSinRegistrosCrono');
                    var detalleIndex = pos - 1;
                    if (detalleIndex < 0 || !detalles[detalleIndex]) {
                        sinReg.classList.remove('hidden');
                    } else {
                        sinReg.classList.add('hidden');
                        var d = detalles[detalleIndex];
                        var td = '<td class="px-3 py-2">' + esc(cab.codigo || codigo) + '</td><td class="px-3 py-2">' + esc(cab.nombre || '') + '</td><td class="px-3 py-2">' + esc(cab.zona || '') + '</td><td class="px-3 py-2">' + esc(cab.despliegue || '') + '</td><td class="px-3 py-2">' + esc(cab.descripcion || '') + '</td>';
                        colsSinNum.forEach(function(k) {
                            td += '<td class="px-3 py-2"' + (k === 'descripcion_vacuna' ? ' style="white-space:pre-wrap;"' : '') + '>' + valorCeldaDetalleCrono(k, d) + '</td>';
                        });
                        tbody.innerHTML = '<tr class="border-b border-gray-200">' + td + '</tr>';
                    }
                    document.getElementById('modalVerProgramaDetalleCrono').classList.remove('hidden');
                })
                .catch(function() {
                    document.getElementById('modalVerCabeceraCrono').innerHTML = '<p class="text-red-500">Error de conexión.</p>';
                });
        }
        function cerrarModalVerProgramaDetalleCrono() {
            document.getElementById('modalVerProgramaDetalleCrono').classList.add('hidden');
        }
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-ver-crono-detalle');
            if (!btn) return;
            e.preventDefault();
            abrirModalVerProgramaDetalleCrono(btn.getAttribute('data-codigo'), btn.getAttribute('data-pos-detalle'));
        });
        document.getElementById('modalVerProgramaDetalleCronoCerrar').addEventListener('click', cerrarModalVerProgramaDetalleCrono);
        document.getElementById('modalVerProgramaDetalleCrono').addEventListener('click', function(e) { if (e.target === this) cerrarModalVerProgramaDetalleCrono(); });
        document.addEventListener('click', function(e) {
            var tabBtn = e.target.closest('.tabs-crono-resultado .tab-btn');
            if (!tabBtn) return;
            e.preventDefault();
            var tab = tabBtn.getAttribute('data-tab');
            var ctx = tabBtn.getAttribute('data-context') || 'especifico';
            var container = ctx === 'zonas' ? document.getElementById('fechasResultadoZonas') : document.getElementById('fechasResultado');
            if (!container) return;
            container.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
            container.querySelectorAll('.tab-panel-crono').forEach(function(p) { p.classList.remove('active'); });
            tabBtn.classList.add('active');
            if (tab === 'granjas') {
                var panel = ctx === 'zonas' ? document.getElementById('tabPanelGranjasZonas') : document.getElementById('tabPanelGranjasEspecifico');
                if (panel) panel.classList.add('active');
            } else if (tab === 'programa') {
                var panel = ctx === 'zonas' ? document.getElementById('tabPanelProgramaZonas') : document.getElementById('tabPanelProgramaEspecifico');
                if (panel) panel.classList.add('active');
            }
        });
        function mostrarCarga(mostrar) {
            var el = document.getElementById('modalCargaCrono');
            if (mostrar) el.classList.remove('hidden'); else el.classList.add('hidden');
        }

        var PAGE_SIZE_GRANJAS = 20;
        function filterFilasPorBusqueda(filas, q, isZonas) {
            if (!q || String(q).trim() === '') return filas;
            var term = String(q).trim().toLowerCase();
            return filas.filter(function(r) {
                var txt = (r.codPrograma || '') + ' ' + (r.granja || '') + ' ' + (r.nomGranja || '') + ' ' + (r.campania || '') + ' ' + (r.galpon || '') + ' ' + (r.edad || '') + ' ' + (r.fechaCarga || '') + ' ' + (r.fechaEjec || '');
                if (isZonas) txt += ' ' + (r.zona || '') + ' ' + (r.subzona || '');
                return txt.toLowerCase().indexOf(term) !== -1;
            });
        }
        function buildCronoGranjasPaginationHtml(tipo, page, totalPag, total, start, end) {
            var infoText = 'Mostrando ' + (total === 0 ? 0 : start + 1) + ' a ' + end + ' de ' + total + ' registros';
            var prevDisabled = page <= 1;
            var nextDisabled = page >= totalPag;
            var prevClass = prevDisabled ? ' disabled' : '';
            var nextClass = nextDisabled ? ' disabled' : '';
            var html = '<span class="dataTables_info">' + infoText + '</span>';
            html += '<span class="paginate_button_wrap" style="display:inline-flex;align-items:center;gap:0.25rem;">';
            html += '<span class="paginate_button previous' + prevClass + '" data-crono-pagenav="prev" data-context="' + tipo + '" role="button">Anterior</span>';
            html += '<span class="paginate_button current" role="button">Pág. ' + page + ' de ' + totalPag + '</span>';
            html += '<span class="paginate_button next' + nextClass + '" data-crono-pagenav="next" data-context="' + tipo + '" role="button">Siguiente</span>';
            html += '</span>';
            return html;
        }
        function renderCronoGranjasCards(filaPage, tipo, total, totalPag, page) {
            var isZonas = (tipo === 'zonas');
            var wrapperId = isZonas ? 'cronoGranjasWrapperZonas' : 'cronoGranjasWrapperEspecifico';
            var containerId = isZonas ? 'cardsContainerGranjasZonas' : 'cardsContainerGranjasEspecifico';
            var pagId = isZonas ? 'cardsPaginationGranjasZonas' : 'cardsPaginationGranjasEspecifico';
            var start = (page - 1) * PAGE_SIZE_GRANJAS;
            var end = Math.min(start + PAGE_SIZE_GRANJAS, total);
            var container = document.getElementById(containerId);
            var pagEl = document.getElementById(pagId);
            if (!container) return;
            var cardsHtml = '';
            filaPage.forEach(function(r, i) {
                var num = start + i + 1;
                cardsHtml += '<div class="crono-card-item card-item">';
                cardsHtml += '<div class="card-codigo card-codigo">#' + num + ' · ' + esc(r.codPrograma || '—') + '</div>';
                cardsHtml += '<div class="card-row"><span class="label">Granja:</span> ' + esc(r.granja || '—') + '</div>';
                cardsHtml += '<div class="card-row"><span class="label">Nom. Granja:</span> ' + esc(r.nomGranja || '—') + '</div>';
                cardsHtml += '<div class="card-row"><span class="label">Campaña:</span> ' + esc(r.campania || '—') + '</div>';
                cardsHtml += '<div class="card-row"><span class="label">Galpón:</span> ' + esc(r.galpon || '—') + '</div>';
                if (isZonas) {
                    cardsHtml += '<div class="card-row"><span class="label">Zona:</span> ' + esc(r.zona || '—') + '</div>';
                    cardsHtml += '<div class="card-row"><span class="label">Subzona:</span> ' + esc(r.subzona || '—') + '</div>';
                }
                cardsHtml += '<div class="card-row"><span class="label">Edad:</span> ' + esc(r.edad || '—') + '</div>';
                cardsHtml += '<div class="card-row"><span class="label">Fec. Carga:</span> ' + esc(r.fechaCarga || '—') + '</div>';
                cardsHtml += '<div class="card-row"><span class="label">Fec. Ejecución:</span> ' + esc(r.fechaEjec || '—') + '</div>';
                cardsHtml += '</div>';
            });
            container.innerHTML = cardsHtml;
            if (pagEl) {
                pagEl.innerHTML = buildCronoGranjasPaginationHtml(tipo, page, totalPag, total, start, end);
                var prevBtn = pagEl.querySelector('.paginate_button.previous');
                var nextBtn = pagEl.querySelector('.paginate_button.next');
                if (prevBtn && !prevBtn.classList.contains('disabled')) prevBtn.addEventListener('click', function() { renderGranjasPage(tipo, page - 1); });
                if (nextBtn && !nextBtn.classList.contains('disabled')) nextBtn.addEventListener('click', function() { renderGranjasPage(tipo, page + 1); });
            }
        }
        function renderTablaGranjasPaginada(filas, panel, tipo, totalTexto) {
            if (!panel) return;
            var isZonas = (tipo === 'zonas');
            if (isZonas) { window._filasGranjasZonas = filas; window._searchGranjasZonas = ''; }
            else { window._filasGranjasEspecifico = filas; window._searchGranjasEspecifico = ''; }
            var wrapperId = isZonas ? 'cronoGranjasWrapperZonas' : 'cronoGranjasWrapperEspecifico';
            var tableId = isZonas ? 'tablaGranjasZonas' : 'tablaGranjasEspecifico';
            var theadZonas = '<tr><th>N°</th><th>cod. programa</th><th>Zona</th><th>Subzona</th><th>Granja</th><th>Nom. Granja</th><th>Campaña</th><th>Galpón</th><th>Edad</th><th>Fec. Carga</th><th>Fec. Ejecución</th></tr>';
            var theadEsp = '<tr><th>N°</th><th>cod. programa</th><th>Granja</th><th>Nom. Granja</th><th>Campaña</th><th>Galpón</th><th>Edad</th><th>Fec. Carga</th><th>Fec. Ejecución</th></tr>';
            var thead = isZonas ? theadZonas : theadEsp;
            var tbodyId = isZonas ? 'tbodyGranjasZonas' : 'tbodyGranjasEspecifico';
            var pagId = isZonas ? 'paginacionGranjasZonas' : 'paginacionGranjasEspecifico';
            var searchId = isZonas ? 'searchGranjasZonas' : 'searchGranjasEspecifico';
            var searchIdIconos = isZonas ? 'searchGranjasZonasIconos' : 'searchGranjasEspecificoIconos';
            var sizeSelectId = isZonas ? 'granjasSizeZonas' : 'granjasSizeEspecifico';
            var sizeSelectIdIconos = isZonas ? 'granjasSizeZonasIconos' : 'granjasSizeEspecificoIconos';
            var cardsTopId = isZonas ? 'cardsControlsTopGranjasZonas' : 'cardsControlsTopGranjasEspecifico';
            var cardsContainerId = isZonas ? 'cardsContainerGranjasZonas' : 'cardsContainerGranjasEspecifico';
            var cardsPagId = isZonas ? 'cardsPaginationGranjasZonas' : 'cardsPaginationGranjasEspecifico';
            var sizeOpts = PAGE_SIZE_GRANJAS === 20 ? ' selected' : '';
            var sizeOpts50 = PAGE_SIZE_GRANJAS === 50 ? ' selected' : '';
            var sizeOpts100 = PAGE_SIZE_GRANJAS === 100 ? ' selected' : '';
            var controlsLista = '<div class="crono-dt-controls">' +
                '<label class="inline-flex items-center gap-2" style="margin:0;"><span>Mostrar</span><select id="' + sizeSelectId + '" data-granjas-size data-context="' + tipo + '"><option value="20"' + sizeOpts + '>20</option><option value="50"' + sizeOpts50 + '>50</option><option value="100"' + sizeOpts100 + '>100</option></select><span>registros</span></label>' +
                '<label class="inline-flex items-center gap-2" style="margin:0;"><span>Buscar:</span><input type="text" class="buscar-granjas" id="' + searchId + '" placeholder="Buscar..." autocomplete="off" data-context="' + tipo + '" style="padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;min-width:180px;"></label>' +
                '</div>';
            var controlsIconos = '<div class="crono-iconos-controls">' +
                '<label class="inline-flex items-center gap-2" style="margin:0;"><span>Mostrar</span><select id="' + sizeSelectIdIconos + '" data-granjas-size data-context="' + tipo + '"><option value="20"' + sizeOpts + '>20</option><option value="50"' + sizeOpts50 + '>50</option><option value="100"' + sizeOpts100 + '>100</option></select><span>registros</span></label>' +
                '<label class="inline-flex items-center gap-2" style="margin:0;"><span>Buscar:</span><input type="text" class="buscar-granjas" id="' + searchIdIconos + '" placeholder="Buscar..." autocomplete="off" data-context="' + tipo + '" style="padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;min-width:180px;"></label>' +
                '</div>';
            var html = (totalTexto ? '<p class="text-gray-600 text-sm mb-3">' + totalTexto + '</p>' : '') +
                '<div class="tabla-crono-wrapper" id="' + wrapperId + '" data-vista="lista">' +
                '<div class="crono-granjas-toolbar-row">' +
                '<div class="view-toggle-group flex items-center gap-2">' +
                '<button type="button" class="view-toggle-btn active" data-crono-view="lista" data-context="' + tipo + '" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>' +
                '<button type="button" class="view-toggle-btn" data-crono-view="iconos" data-context="' + tipo + '" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>' +
                '</div>' + controlsLista + controlsIconos + '</div>' +
                '<div class="view-tarjetas-wrap-crono px-4 pb-4 overflow-x-hidden">' +
                '<div id="' + cardsTopId + '" class="crono-cards-controls-top"></div>' +
                '<div id="' + cardsContainerId + '" class="crono-cards-grid cards-grid-iconos"></div>' +
                '<div id="' + cardsPagId + '" class="crono-cards-pagination" data-context="' + tipo + '"></div>' +
                '</div>' +
                '<div class="view-lista-wrap-crono">' +
                '<div class="table-wrapper overflow-x-auto">' +
                '<table class="tabla-fechas-crono config-table w-full text-sm border-collapse" id="' + tableId + '"><thead>' + thead + '</thead><tbody id="' + tbodyId + '"></tbody></table>' +
                '</div>' +
                '<div id="' + pagId + '" class="tabla-crono-toolbar-bottom" data-context="' + tipo + '"></div>' +
                '</div></div>';
            panel.innerHTML = html;
            var wrapper = document.getElementById(wrapperId);
            document.querySelectorAll('#' + wrapperId + ' [data-granjas-size]').forEach(function(sel) {
                sel.addEventListener('change', function() { PAGE_SIZE_GRANJAS = parseInt(this.value, 10) || 20; renderGranjasPage(tipo, 1); });
            });
            document.querySelectorAll('#' + wrapperId + ' .buscar-granjas').forEach(function(inp) {
                inp.addEventListener('input', function() { if (tipo === 'zonas') window._searchGranjasZonas = this.value; else window._searchGranjasEspecifico = this.value; renderGranjasPage(tipo, 1); });
            });
            document.querySelectorAll('#' + wrapperId + ' [data-crono-view]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var v = btn.getAttribute('data-crono-view');
                    wrapper.setAttribute('data-vista', v);
                    document.querySelectorAll('#' + wrapperId + ' .view-toggle-btn').forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    renderGranjasPage(tipo, window._cronoGranjasCurrentPage ? window._cronoGranjasCurrentPage[tipo] || 1 : 1);
                });
            });
            renderGranjasPage(tipo, 1);
        }
        function renderGranjasPage(tipo, page) {
            var filasCompletas = tipo === 'zonas' ? (window._filasGranjasZonas || []) : (window._filasGranjasEspecifico || []);
            var searchQ = tipo === 'zonas' ? (window._searchGranjasZonas || '') : (window._searchGranjasEspecifico || '');
            var filas = filterFilasPorBusqueda(filasCompletas, searchQ, tipo === 'zonas');
            var total = filas.length;
            var totalPag = Math.max(1, Math.ceil(total / PAGE_SIZE_GRANJAS));
            page = Math.max(1, Math.min(page, totalPag));
            if (!window._cronoGranjasCurrentPage) window._cronoGranjasCurrentPage = {};
            window._cronoGranjasCurrentPage[tipo] = page;
            var isZonas = (tipo === 'zonas');
            var tbodyId = isZonas ? 'tbodyGranjasZonas' : 'tbodyGranjasEspecifico';
            var pagId = isZonas ? 'paginacionGranjasZonas' : 'paginacionGranjasEspecifico';
            var wrapperId = isZonas ? 'cronoGranjasWrapperZonas' : 'cronoGranjasWrapperEspecifico';
            var start = (page - 1) * PAGE_SIZE_GRANJAS;
            var end = Math.min(start + PAGE_SIZE_GRANJAS, total);
            var filaPage = filas.slice(start, end);
            var tbody = document.getElementById(tbodyId);
            if (!tbody) return;
            var rowsHtml = '';
            filaPage.forEach(function(r, i) {
                var num = start + i + 1;
                if (isZonas) {
                    rowsHtml += '<tr><td>' + num + '</td><td>' + (r.codPrograma || '—') + '</td><td>' + (r.zona || '—') + '</td><td>' + (r.subzona || '—') + '</td><td>' + r.granja + '</td><td>' + r.nomGranja + '</td><td>' + r.campania + '</td><td>' + r.galpon + '</td><td>' + r.edad + '</td><td>' + r.fechaCarga + '</td><td>' + r.fechaEjec + '</td></tr>';
                } else {
                    rowsHtml += '<tr><td>' + num + '</td><td>' + (r.codPrograma || '—') + '</td><td>' + r.granja + '</td><td>' + r.nomGranja + '</td><td>' + r.campania + '</td><td>' + r.galpon + '</td><td>' + r.edad + '</td><td>' + r.fechaCarga + '</td><td>' + r.fechaEjec + '</td></tr>';
                }
            });
            tbody.innerHTML = rowsHtml;
            var pagEl = document.getElementById(pagId);
            var pagHtml = buildCronoGranjasPaginationHtml(tipo, page, totalPag, total, start, end);
            if (pagEl) {
                pagEl.innerHTML = pagHtml;
                var prevBtn = pagEl.querySelector('.paginate_button.previous');
                var nextBtn = pagEl.querySelector('.paginate_button.next');
                if (prevBtn && !prevBtn.classList.contains('disabled')) prevBtn.addEventListener('click', function() { renderGranjasPage(tipo, page - 1); });
                if (nextBtn && !nextBtn.classList.contains('disabled')) nextBtn.addEventListener('click', function() { renderGranjasPage(tipo, page + 1); });
            }
            var searchIds = isZonas ? ['searchGranjasZonas', 'searchGranjasZonasIconos'] : ['searchGranjasEspecifico', 'searchGranjasEspecificoIconos'];
            searchIds.forEach(function(id) { var el = document.getElementById(id); if (el) el.value = searchQ; });
            var wrapper = document.getElementById(wrapperId);
            if (wrapper && wrapper.getAttribute('data-vista') === 'iconos') {
                renderCronoGranjasCards(filaPage, tipo, total, totalPag, page);
                var cardsTopId = isZonas ? 'cardsControlsTopGranjasZonas' : 'cardsControlsTopGranjasEspecifico';
                var cardsTop = document.getElementById(cardsTopId);
                var cardsTopId = isZonas ? 'cardsControlsTopGranjasZonas' : 'cardsControlsTopGranjasEspecifico';
                if (cardsTop && !cardsTop.hasChildNodes()) {
                    cardsTop.innerHTML = '<label class="inline-flex items-center gap-2"><span>Mostrar</span><select data-granjas-size data-context="' + tipo + '"><option value="20">20</option><option value="50">50</option><option value="100">100</option></select><span>registros</span></label>' +
                        '<label class="inline-flex items-center gap-2"><span>Buscar:</span><input type="text" class="buscar-granjas" placeholder="Buscar..." data-context="' + tipo + '" style="padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;min-width:180px;"></label>';
                    var topEl = document.getElementById(cardsTopId);
                    if (topEl) {
                        topEl.querySelectorAll('[data-granjas-size]').forEach(function(sel) {
                            sel.addEventListener('change', function() { PAGE_SIZE_GRANJAS = parseInt(this.value, 10) || 20; renderGranjasPage(tipo, 1); });
                        });
                        topEl.querySelectorAll('.buscar-granjas').forEach(function(inp) {
                            inp.addEventListener('input', function() { if (tipo === 'zonas') window._searchGranjasZonas = this.value; else window._searchGranjasEspecifico = this.value; renderGranjasPage(tipo, 1); });
                        });
                    }
                }
                if (cardsTop) {
                    var selTop = cardsTop.querySelector('select[data-granjas-size]');
                    if (selTop) selTop.value = String(PAGE_SIZE_GRANJAS);
                    var inpTop = cardsTop.querySelector('input.buscar-granjas');
                    if (inpTop) inpTop.value = searchQ;
                }
            }
        }

        function llenarAnios() {
            var sel = document.getElementById('cronoAnio');
            var anioActual = new Date().getFullYear();
            sel.innerHTML = '';
            for (var a = anioActual; a <= anioActual + 5; a++) {
                var opt = document.createElement('option');
                opt.value = a;
                opt.textContent = a;
                if (a === anioActual) opt.selected = true;
                sel.appendChild(opt);
            }
        }

        function cargarGranjas() {
            fetch('get_granjas.php').then(r => r.json()).then(data => {
                var sel = document.getElementById('cronoGranja');
                sel.innerHTML = '<option value="">Seleccione...</option>';
                granjasMap = {};
                (data || []).forEach(g => {
                    var cod = (g.codigo != null) ? String(g.codigo).trim().substring(0, 3) : '';
                    if (cod) granjasMap[cod] = (g.nombre != null) ? String(g.nombre).trim() : '';
                    var opt = document.createElement('option');
                    opt.value = cod || g.codigo;
                    opt.textContent = (g.codigo || '') + ' - ' + (g.nombre || '');
                    sel.appendChild(opt);
                });
            }).catch(() => {});
        }

        document.getElementById('cronoGranja').addEventListener('change', function() {
            var granja = this.value;
            var galp = document.getElementById('cronoGalpon');
            galp.innerHTML = '<option value="">Cargando...</option>';
            galp.disabled = true;
            document.getElementById('contenedorCampanias').innerHTML = '';
            if (!granja) {
                galp.innerHTML = '<option value="">Primero granja</option>';
                return;
            }
            fetch('get_galpones.php?codigo=' + encodeURIComponent(granja)).then(r => r.json()).then(data => {
                galp.innerHTML = '<option value="">Seleccione galpón...</option>';
                (data || []).forEach(g => {
                    var opt = document.createElement('option');
                    opt.value = g.galpon;
                    opt.textContent = g.galpon + (g.nombre ? ' - ' + g.nombre : '');
                    galp.appendChild(opt);
                });
                galp.disabled = false;
            }).catch(() => { galp.innerHTML = '<option value="">Error</option>'; galp.disabled = false; });
        });

        function cargarCampanias() {
            var granja = document.getElementById('cronoGranja').value.trim();
            var galpon = document.getElementById('cronoGalpon').value.trim();
            var anio = (document.getElementById('cronoAnio') && document.getElementById('cronoAnio').value) || new Date().getFullYear();
            var cont = document.getElementById('contenedorCampanias');
            cont.innerHTML = '';
            if (!granja || !galpon) return;
            cont.innerHTML = '<span class="text-gray-500 text-sm">Cargando campañas...</span>';
            fetch('get_campanias.php?granja=' + encodeURIComponent(granja) + '&galpon=' + encodeURIComponent(galpon) + '&anio=' + encodeURIComponent(anio)).then(r => r.json()).then(data => {
                cont.innerHTML = '';
                (data || []).forEach(c => {
                    var label = document.createElement('label');
                    label.className = 'inline-flex items-center gap-1';
                    var chk = document.createElement('input');
                    chk.type = 'checkbox';
                    chk.className = 'chk-campania rounded border-gray-300';
                    chk.value = c.campania;
                    label.appendChild(chk);
                    label.appendChild(document.createTextNode(c.campania));
                    cont.appendChild(label);
                });
            }).catch(function() { cont.innerHTML = '<span class="text-red-500 text-sm">Error al cargar campañas</span>'; });
        }

        document.getElementById('cronoGalpon').addEventListener('change', cargarCampanias);
        document.getElementById('cronoAnio').addEventListener('change', cargarCampanias);

        function initSelect2CronoPrograma() {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
            var $sel = jQuery('#cronoPrograma');
            if ($sel.data('select2')) $sel.select2('destroy');
            $sel.select2({
                placeholder: 'Escriba código o nombre...',
                allowClear: true,
                width: '100%',
                language: { noResults: function() { return 'Sin resultados'; }, searching: function() { return 'Buscando...'; } }
            });
        }

        function actualizarNombrePrograma() {
            var cod = (document.getElementById('cronoPrograma').value || '').toString().trim();
            var info = cod && programasMap[cod] ? programasMap[cod] : { nombre: '' };
            document.getElementById('cronoNomProgramaDisplay').value = info.nombre || '';
        }

        function cargarTiposPrograma() {
            fetch('../programas/get_tipos_programa.php').then(r => r.json()).then(res => {
                if (!res.success || !res.data) return;
                var sel = document.getElementById('cronoTipo');
                sel.innerHTML = '<option value="">Seleccione tipo...</option>';
                res.data.forEach(function(t) {
                    var opt = document.createElement('option');
                    opt.value = t.codigo;
                    opt.textContent = t.nombre || '';
                    sel.appendChild(opt);
                });
            }).catch(function() {});
        }

        function cargarProgramas(codTipo) {
            programasMap = {};
            var sel = document.getElementById('cronoPrograma');
            if (!codTipo) {
                sel.innerHTML = '<option value="">Primero seleccione el tipo de programa</option>';
                sel.disabled = true;
                if (jQuery(sel).data('select2')) jQuery(sel).val('').trigger('change');
                initSelect2CronoPrograma();
                return;
            }
            sel.disabled = false;
            sel.innerHTML = '<option value="">Cargando...</option>';
            fetch('get_programas.php?codTipo=' + encodeURIComponent(codTipo)).then(r => r.json()).then(res => {
                if (!res.success) {
                    sel.innerHTML = '<option value="">Error al cargar</option>';
                    return;
                }
                sel.innerHTML = '<option value="">Escriba código o nombre...</option>';
                (res.data || []).forEach(p => {
                    var cod = (p.codigo != null) ? String(p.codigo).trim() : '';
                    if (cod === '') return;
                    programasMap[cod] = { nombre: (p.nombre != null) ? String(p.nombre) : '', nomTipo: (p.nomTipo != null) ? String(p.nomTipo) : '' };
                    var opt = document.createElement('option');
                    opt.value = cod;
                    opt.textContent = p.label || (cod + ' - ' + (p.nombre || ''));
                    sel.appendChild(opt);
                });
                initSelect2CronoPrograma();
                jQuery('#cronoPrograma').off('select2:select').on('select2:select', function() { actualizarNombrePrograma(); });
            }).catch(function() {
                sel.innerHTML = '<option value="">Error de conexión</option>';
            });
        }

        function cargarZonas() {
            fetch('get_zonas_caracteristicas.php').then(r => r.json()).then(res => {
                if (!res.success) return;
                var sel = document.getElementById('cronoZona');
                var opts = '';
                (res.zonas || []).forEach(function(z) {
                    if (z && String(z).trim() !== '' && String(z).trim().toLowerCase() !== 'especifico')
                        opts += '<option value="' + String(z).replace(/"/g, '&quot;') + '">' + String(z).replace(/</g, '&lt;') + '</option>';
                });
                opts += '<option value="Especifico">Especifico</option>';
                sel.innerHTML = opts;
            }).catch(function() {});
        }

        var subzonasPorZona = {};
        function cargarSubzonasParaZona(zona, callback) {
            if (subzonasPorZona[zona]) {
                if (callback) callback(subzonasPorZona[zona]);
                return;
            }
            fetch('get_subzonas_por_zona.php?zona=' + encodeURIComponent(zona)).then(r => r.json()).then(res => {
                if (res.success && res.data && res.data.length) {
                    subzonasPorZona[zona] = res.data;
                    if (callback) callback(res.data);
                } else {
                    subzonasPorZona[zona] = [];
                    if (callback) callback([]);
                }
            }).catch(function() { if (callback) callback([]); });
        }

        function renderSubzonas() {
            var sel = document.getElementById('cronoZona');
            var selected = Array.from(sel.selectedOptions).map(function(o) { return o.value; }).filter(Boolean);
            var zonasNoEsp = selected.filter(function(z) { return z !== 'Especifico'; });
            var cont = document.getElementById('contenedorSubzonas');
            cont.innerHTML = '';
            if (zonasNoEsp.length === 0) {
                document.getElementById('bloqueSubzonas').classList.remove('visible');
                document.getElementById('bloqueAsignarZonas').style.display = 'none';
                return;
            }
            document.getElementById('bloqueSubzonas').classList.add('visible');
            document.getElementById('bloqueAsignarZonas').style.display = 'block';
            zonasNoEsp.forEach(function(zona) {
                var div = document.createElement('div');
                div.className = 'zona-subzonas';
                div.setAttribute('data-zona', zona);
                var h4 = document.createElement('h4');
                h4.textContent = 'Zona: ' + zona + ' — Subzonas';
                div.appendChild(h4);
                var wrap = document.createElement('div');
                wrap.className = 'subzonas-list';
                wrap.innerHTML = '<span class="text-gray-500 text-sm">Cargando...</span>';
                div.appendChild(wrap);
                cont.appendChild(div);
                cargarSubzonasParaZona(zona, function(data) {
                    wrap.innerHTML = '';
                    if (data.length === 0) {
                        wrap.innerHTML = '<p class="text-sm text-gray-500">Sin subzonas para esta zona.</p>';
                        return;
                    }
                    // Agrupar por subzona única (dato); cada una puede tener varios (id_granja, id_galpon)
                    var porSubzona = {};
                    data.forEach(function(s) {
                        var d = s.dato || '';
                        if (!d) return;
                        if (!porSubzona[d]) porSubzona[d] = [];
                        porSubzona[d].push({ id_granja: s.id_granja, id_galpon: s.id_galpon, nombre_granja: s.nombre_granja || s.id_granja });
                    });
                    var subzonasUnicas = Object.keys(porSubzona).sort();
                    subzonasUnicas.forEach(function(dato) {
                        var items = porSubzona[dato];
                        var nombresGranja = [];
                        items.forEach(function(it) {
                            if (it.nombre_granja && nombresGranja.indexOf(it.nombre_granja) === -1) nombresGranja.push(it.nombre_granja);
                        });
                        var granjaTexto = nombresGranja.length ? ' — ' + nombresGranja.join(', ') : '';
                        var pairsJson = JSON.stringify(items.map(function(it) { return { id_granja: it.id_granja, id_galpon: it.id_galpon }; }));
                        var label = document.createElement('label');
                        label.className = 'subzona-chk';
                        var chk = document.createElement('input');
                        chk.type = 'checkbox';
                        chk.className = 'chk-subzona rounded border-gray-300';
                        chk.setAttribute('data-zona', zona);
                        chk.setAttribute('data-dato', dato);
                        chk.setAttribute('data-pairs', pairsJson);
                        label.appendChild(chk);
                        label.appendChild(document.createTextNode(dato));
                        var span = document.createElement('span');
                        span.className = 'granja-nom';
                        span.textContent = granjaTexto;
                        span.setAttribute('data-granja-nom', granjaTexto);
                        label.appendChild(span);
                        wrap.appendChild(label);
                    });
                });
            });
        }

        document.getElementById('cronoTipo').addEventListener('change', function() {
            var codTipo = this.value || '';
            cargarProgramas(codTipo);
            document.getElementById('cronoNomProgramaDisplay').value = '';
            if (jQuery('#cronoPrograma').data('select2')) jQuery('#cronoPrograma').val('').trigger('change');
        });

        document.getElementById('cronoPrograma').addEventListener('change', function() {
            actualizarNombrePrograma();
        });

        function actualizarVisibilidadZonas() {
            var selZona = document.getElementById('cronoZona');
            var selected = Array.from(selZona.selectedOptions).map(function(o) { return o.value; });
            var tieneEspecifico = selected.indexOf('Especifico') !== -1;
            document.getElementById('bloqueEspecifico').classList.toggle('visible', tieneEspecifico);
            if (tieneEspecifico && selected.length > 1) {
                Array.from(selZona.options).forEach(function(opt) {
                    if (opt.value !== 'Especifico') opt.selected = false;
                });
            }
            if (!tieneEspecifico) renderSubzonas();
            else {
                document.getElementById('bloqueSubzonas').classList.remove('visible');
                document.getElementById('bloqueAsignarZonas').style.display = 'none';
            }
        }

        document.getElementById('cronoZona').addEventListener('change', function() {
            var sel = this;
            var selected = Array.from(sel.selectedOptions).map(function(o) { return o.value; });
            if (selected.indexOf('Especifico') !== -1 && selected.length > 1) {
                Array.from(sel.options).forEach(function(opt) {
                    opt.selected = opt.value === 'Especifico';
                });
            }
            if (selected.length > 1 && selected[selected.length-1] !== 'Especifico') {
                var esp = sel.querySelector('option[value="Especifico"]');
                if (esp && esp.selected) esp.selected = false;
            }
            actualizarVisibilidadZonas();
        });

        document.getElementById('infoZona').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var pop = document.getElementById('popoverInfoZonaFlotante');
            if (!pop) {
                pop = document.createElement('div');
                pop.id = 'popoverInfoZonaFlotante';
                pop.innerHTML = 'Puede seleccionar más de una zona manteniendo presionada la tecla <strong>Control</strong> (Ctrl) y haciendo clic en las opciones.<br><br>La opción <strong>Específico</strong> solo se puede seleccionar sola (granja y galpón concretos).';
                document.body.appendChild(pop);
            }
            var isVisible = pop.classList.contains('visible');
            if (isVisible) {
                pop.classList.remove('visible');
            } else {
                var rect = this.getBoundingClientRect();
                var pad = 8;
                pop.style.left = rect.left + 'px';
                pop.style.top = (rect.bottom + 6) + 'px';
                pop.classList.add('visible');
                var w = pop.offsetWidth, h = pop.offsetHeight;
                var left = parseFloat(pop.style.left) || 0, top = parseFloat(pop.style.top) || 0;
                left = Math.max(pad, Math.min(left, window.innerWidth - w - pad));
                top = Math.max(pad, Math.min(top, window.innerHeight - h - pad));
                pop.style.left = left + 'px';
                pop.style.top = top + 'px';
            }
        });
        document.addEventListener('click', function(ev) {
            if (!ev.target.closest('#infoZona')) {
                var p = document.getElementById('popoverInfoZonaFlotante');
                if (p) p.classList.remove('visible');
            }
        });

        document.getElementById('btnAsignarZonas').addEventListener('click', function() {
            var codPrograma = document.getElementById('cronoPrograma').value.trim();
            var anio = document.getElementById('cronoAnio').value || new Date().getFullYear();
            var chks = document.querySelectorAll('.chk-subzona:checked');
            if (!codPrograma) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Seleccione tipo y código del programa.' });
                return;
            }
            if (chks.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Seleccione al menos una subzona.' });
                return;
            }
            var pairs = [];
            chks.forEach(function(c) {
                var raw = c.getAttribute('data-pairs');
                if (raw) {
                    try {
                        var arr = JSON.parse(raw);
                        (arr || []).forEach(function(p) { pairs.push({ id_granja: p.id_granja, id_galpon: p.id_galpon }); });
                    } catch (e) {
                        if (c.getAttribute('data-id_granja')) pairs.push({ id_granja: c.getAttribute('data-id_granja'), id_galpon: c.getAttribute('data-id_galpon') });
                    }
                }
            });
            var fd = new FormData();
            fd.append('modo', 'zonas');
            fd.append('codPrograma', codPrograma);
            fd.append('anio', anio);
            pairs.forEach(function(p, i) {
                fd.append('pairs[' + i + '][id_granja]', p.id_granja);
                fd.append('pairs[' + i + '][id_galpon]', p.id_galpon);
            });
            mostrarCarga(true);
            fetch('calcular_fechas.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    var div = document.getElementById('fechasResultadoZonas');
                    var panelGranjas = document.getElementById('tabPanelGranjasZonas');
                    if (!res.success) {
                        div.classList.add('hidden');
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudieron calcular fechas.' });
                        return;
                    }
                    fechasAsignadas = res.fechas || [];
                    paresCargaEjecucion = res.pares || [];
                    itemsZonas = res.items || [];
                    document.querySelectorAll('#fechasResultadoZonas .tab-btn').forEach(function(b) { b.classList.remove('active'); });
                    document.querySelectorAll('#fechasResultadoZonas .tab-panel-crono').forEach(function(p) { p.classList.remove('active'); });
                    var firstTab = document.querySelector('#fechasResultadoZonas .tab-btn[data-tab="granjas"]');
                    if (firstTab) firstTab.classList.add('active');
                    if (panelGranjas) panelGranjas.classList.add('active');
                    if (fechasAsignadas.length === 0) {
                        if (panelGranjas) panelGranjas.innerHTML = '<p class="text-amber-700">No se encontraron fechas para los criterios seleccionados.</p>';
                    } else {
                        var zonaSubzonaPorPar = {};
                        document.querySelectorAll('.chk-subzona:checked').forEach(function(c) {
                            var raw = c.getAttribute('data-pairs');
                            var zona = (c.getAttribute('data-zona') || '').toString().trim();
                            var subzona = (c.getAttribute('data-dato') || '').toString().trim();
                            if (raw) {
                                try {
                                    var arr = JSON.parse(raw);
                                    (arr || []).forEach(function(p) {
                                        var ig = String(p.id_granja || p.idGranja || '').trim();
                                        var ia = String(p.id_galpon || p.idGalpon || '').trim();
                                        if (ig.length < 3) ig = ig.padStart(3, '0');
                                        zonaSubzonaPorPar[ig + '|' + ia] = { zona: zona, subzona: subzona };
                                    });
                                } catch (e) {}
                            }
                        });
                        var filas = [];
                        (itemsZonas || []).forEach(function(it) {
                            var key = String(it.granja || '').trim();
                            if (key.length < 3) key = key.padStart(3, '0');
                            key += '|' + (it.galpon || '');
                            var zs = zonaSubzonaPorPar[key] || { zona: '—', subzona: '—' };
                            var zonaTxt = zs.zona || '—';
                            var subzonaTxt = zs.subzona || '—';
                            var nomG = (granjasMap[it.granja] || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;');
                            (it.fechas || []).forEach(function(f, idx) {
                                var campaniaFila = (f.campania != null && String(f.campania).trim() !== '') ? String(f.campania).trim() : (it.campania || '—');
                                filas.push({ codPrograma: codPrograma, zona: zonaTxt, subzona: subzonaTxt, granja: it.granja || '—', nomGranja: nomG || '—', campania: campaniaFila, galpon: it.galpon || '—', edad: f.edad != null ? f.edad : '—', fechaCarga: formatoDDMMYYYY(f.fechaCarga), fechaEjec: formatoDDMMYYYY(f.fechaEjecucion) });
                            });
                        });
                        var totalTexto = '<strong>Total:</strong> ' + filas.length + ' registro(s)';
                        renderTablaGranjasPaginada(filas, panelGranjas, 'zonas', totalTexto);
                    }
                    cargarTabProgramaEnResultadoCrono(codPrograma, 'programaCabZonas', 'programaTheadZonas', 'programaBodyZonas', 'programaSinRegZonas');
                    div.classList.remove('hidden');
                    document.getElementById('btnGuardarCrono').disabled = fechasAsignadas.length === 0;
                })
                .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }); })
                .finally(function() { mostrarCarga(false); });
        });

        document.getElementById('btnAsignar').addEventListener('click', function() {
            var granja = document.getElementById('cronoGranja').value.trim();
            var galpon = document.getElementById('cronoGalpon').value.trim();
            var codPrograma = document.getElementById('cronoPrograma').value.trim();
            var anio = document.getElementById('cronoAnio').value || new Date().getFullYear();
            var chks = document.querySelectorAll('.chk-campania:checked');
            var campanias = Array.from(chks).map(function(c) { return c.value; });
            if (!codPrograma) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Seleccione tipo y código del programa.' });
                return;
            }
            if (!granja || !galpon || campanias.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Seleccione granja, galpón y al menos una campaña.' });
                return;
            }
            var fd = new FormData();
            fd.append('modo', 'especifico');
            fd.append('granja', granja);
            fd.append('galpon', galpon);
            fd.append('codPrograma', codPrograma);
            fd.append('anio', anio);
            campanias.forEach(function(c, i) { fd.append('campanias[' + i + ']', c); });
            mostrarCarga(true);
            fetch('calcular_fechas.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    var div = document.getElementById('fechasResultado');
                    var panelGranjas = document.getElementById('tabPanelGranjasEspecifico');
                    if (!res.success) {
                        div.classList.add('hidden');
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudieron calcular fechas.' });
                        return;
                    }
                    fechasAsignadas = res.fechas || [];
                    itemsEspecifico = res.items || [];
                    edadProgramaCrono = res.edadPrograma != null ? parseInt(res.edadPrograma, 10) : null;
                    document.querySelectorAll('#fechasResultado .tab-btn').forEach(function(b) { b.classList.remove('active'); });
                    document.querySelectorAll('#fechasResultado .tab-panel-crono').forEach(function(p) { p.classList.remove('active'); });
                    var firstTab = document.querySelector('#fechasResultado .tab-btn[data-tab="granjas"]');
                    if (firstTab) firstTab.classList.add('active');
                    if (panelGranjas) panelGranjas.classList.add('active');
                    if (fechasAsignadas.length === 0) {
                        if (panelGranjas) panelGranjas.innerHTML = '<p class="text-amber-700">No se encontraron fechas para los criterios seleccionados.</p>';
                    } else {
                        var nomGranjaSel = (granjasMap[granja] || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;');
                        var filas = [];
                        (itemsEspecifico || []).forEach(function(it) {
                            var nomG = (granjasMap[it.granja] || nomGranjaSel || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;');
                            (it.fechas || []).forEach(function(f, idx) {
                                var fe = (f && typeof f === 'object') ? f : {};
                                var campaniaFila = (fe.campania != null && String(fe.campania).trim() !== '') ? String(fe.campania).trim() : (it.campania || '—');
                                filas.push({ codPrograma: codPrograma, granja: it.granja || '—', nomGranja: nomG || '—', campania: campaniaFila, galpon: it.galpon || '—', edad: fe.edad != null ? fe.edad : '—', fechaCarga: formatoDDMMYYYY(fe.fechaCarga), fechaEjec: formatoDDMMYYYY(fe.fechaEjecucion) });
                            });
                        });
                        var totalTexto = '<strong>Zona:</strong> Especifico &nbsp;·&nbsp; <strong>Total:</strong> ' + filas.length + ' registro(s)';
                        renderTablaGranjasPaginada(filas, panelGranjas, 'especifico', totalTexto);
                    }
                    cargarTabProgramaEnResultadoCrono(codPrograma, 'programaCabEspecifico', 'programaTheadEspecifico', 'programaBodyEspecifico', 'programaSinRegEspecifico');
                    div.classList.remove('hidden');
                    document.getElementById('btnGuardarCrono').disabled = fechasAsignadas.length === 0;
                })
                .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }); })
                .finally(function() { mostrarCarga(false); });
        });

        document.getElementById('btnGuardarCrono').addEventListener('click', function() {
            if (fechasAsignadas.length === 0) return;
            var codPrograma = document.getElementById('cronoPrograma').value.trim();
            var nomPrograma = (programasMap[codPrograma] && programasMap[codPrograma].nombre) ? programasMap[codPrograma].nombre : '';
            var selZona = document.getElementById('cronoZona');
            var zonasSel = Array.from(selZona.selectedOptions).map(function(o) { return o.value; }).filter(Boolean);
            var tieneEspecifico = zonasSel.indexOf('Especifico') !== -1;

            var payload = { codPrograma: codPrograma, nomPrograma: nomPrograma };
            if (tieneEspecifico && itemsEspecifico.length > 0) {
                var granjaCod = document.getElementById('cronoGranja').value ? String(document.getElementById('cronoGranja').value).trim().substring(0, 3) : '';
                var nomGranjaSel = granjasMap[granjaCod] || '';
                var itemsConNomGranjaYEdad = itemsEspecifico.map(function(it) {
                    var g = (it.granja != null) ? String(it.granja).trim().substring(0, 3) : granjaCod;
                    return {
                        granja: g,
                        nomGranja: nomGranjaSel || (granjasMap[g] || ''),
                        campania: (it.campania != null) ? String(it.campania).trim() : '',
                        galpon: (it.galpon != null) ? String(it.galpon).trim() : '',
                        edad: edadProgramaCrono != null ? edadProgramaCrono : (it.edad != null ? parseInt(it.edad, 10) : null),
                        fechas: (it.fechas || []).map(function(f) {
                            var fe = (f && typeof f === 'object') ? f : {};
                            var campaniaFe = (fe.campania != null) ? String(fe.campania).trim() : ((it.campania != null) ? String(it.campania).trim() : '');
                            return { edad: fe.edad != null ? fe.edad : (edadProgramaCrono != null ? edadProgramaCrono : null), fechaCarga: fe.fechaCarga, fechaEjecucion: fe.fechaEjecucion, campania: campaniaFe };
                        })
                    };
                });
                payload.zona = 'Especifico';
                payload.items = itemsConNomGranjaYEdad;
                fetch('guardar_cronograma.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            Swal.fire({ icon: 'success', title: 'Guardado', text: res.message }).then(function() { limpiarFormulario(); });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo guardar.' });
                        }
                    })
                    .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }); });
                return;
            }

            var zona = zonasSel.join(',');
            var subzona = '';
            document.querySelectorAll('.chk-subzona:checked').forEach(function(c) {
                if (subzona) subzona += ',';
                subzona += c.getAttribute('data-dato');
            });
            var zonaSubzonaPorPar = {};
            document.querySelectorAll('.chk-subzona:checked').forEach(function(c) {
                var raw = c.getAttribute('data-pairs');
                var zonaItem = (c.getAttribute('data-zona') || '').toString().trim();
                var subzonaItem = (c.getAttribute('data-dato') || '').toString().trim();
                if (raw) {
                    try {
                        var arr = JSON.parse(raw);
                        (arr || []).forEach(function(p) {
                            var ig = String(p.id_granja || p.idGranja || '').trim();
                            var ia = String(p.id_galpon || p.idGalpon || '').trim();
                            if (ig.length < 3) ig = ig.padStart(3, '0');
                            zonaSubzonaPorPar[ig + '|' + ia] = { zona: zonaItem, subzona: subzonaItem };
                        });
                    } catch (e) {}
                }
            });
            var itemsZonasParaGuardar = (itemsZonas || []).map(function(it) {
                var key = String(it.granja || '').trim();
                if (key.length < 3) key = key.padStart(3, '0');
                key += '|' + (it.galpon || '');
                var zs = zonaSubzonaPorPar[key] || { zona: '', subzona: '' };
                return {
                    granja: key.substring(0, 3),
                    nomGranja: (granjasMap[it.granja] || '').toString().trim(),
                    campania: (it.campania != null) ? String(it.campania).trim() : '',
                    galpon: (it.galpon != null) ? String(it.galpon).trim() : '',
                    zona: zs.zona,
                    subzona: zs.subzona,
                        fechas: (it.fechas || []).map(function(f) {
                            var campaniaF = (f.campania != null) ? String(f.campania).trim() : ((it.campania != null) ? String(it.campania).trim() : '');
                            return { edad: f.edad != null ? f.edad : null, fechaCarga: f.fechaCarga, fechaEjecucion: f.fechaEjecucion, campania: campaniaF };
                        })
                };
            });
            fetch('guardar_cronograma.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    codPrograma: codPrograma,
                    nomPrograma: nomPrograma,
                    zona: zona,
                    subzona: subzona,
                    items: itemsZonasParaGuardar
                })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Guardado', text: res.message }).then(function() { limpiarFormulario(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo guardar.' });
                    }
                })
                .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }); });
        });

        function limpiarFormulario() {
            Array.from(document.getElementById('cronoZona').options).forEach(function(o) { o.selected = false; });
            document.getElementById('bloqueEspecifico').classList.remove('visible');
            document.getElementById('bloqueSubzonas').classList.remove('visible');
            document.getElementById('bloqueAsignarZonas').style.display = 'none';
            document.getElementById('contenedorSubzonas').innerHTML = '';
            if (jQuery('#cronoPrograma').data('select2')) jQuery('#cronoPrograma').val('').trigger('change');
            else document.getElementById('cronoPrograma').value = '';
            document.getElementById('cronoNomProgramaDisplay').value = '';
            llenarAnios();
            document.getElementById('cronoGranja').value = '';
            document.getElementById('cronoGalpon').innerHTML = '<option value="">Primero granja</option>';
            document.getElementById('cronoGalpon').disabled = true;
            document.getElementById('contenedorCampanias').innerHTML = '';
            document.getElementById('fechasResultado').classList.add('hidden');
            document.getElementById('fechasResultadoZonas').classList.add('hidden');
            document.getElementById('btnGuardarCrono').disabled = true;
            fechasAsignadas = [];
            paresCargaEjecucion = [];
            itemsEspecifico = [];
        }

        document.getElementById('btnLimpiarCrono').addEventListener('click', limpiarFormulario);

        llenarAnios();
        cargarGranjas();
        cargarTiposPrograma();
        cargarZonas();
    </script>
</body>
</html>
