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
    <script src="../../../assets/js/fetch-auth-redirect.js"></script>
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
        .zona-subzonas-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem; }
        .zona-subzonas h4 { font-size: 0.9rem; color: #475569; margin: 0; }
        .chk-subzona-todas { display: flex; align-items: center; gap: 0.35rem; cursor: pointer; font-size: 0.8rem; font-weight: 500; color: #2563eb; }
        .chk-subzona-todas:hover { color: #1d4ed8; }
        .subzona-chk { display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0; }
        .subzona-chk label { cursor: pointer; font-size: 0.875rem; }
        .subzona-chk .granja-nom { font-size: 0.75rem; color: #64748b; margin-left: 0.25rem; }
        .campanias-chk { display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; }
        .campanias-chk label { cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; gap: 0.35rem; }
        .galpon-campanias-block { margin-bottom: 1rem; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; }
        .galpon-campanias-block .galpon-titulo { font-weight: 600; font-size: 0.9rem; color: #1e293b; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .galpon-campanias-block .galpon-titulo input[type="checkbox"] { transform: translateY(1px); }
        .galpon-campanias-block .campanias-wrap { margin-left: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; }
        .galpon-campanias-block .campanias-wrap label { cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; gap: 0.35rem; }
        #contenedorGalponesCampanias .text-cargando { color: #64748b; font-size: 0.875rem; }
        /* Popover flotante (zona): debajo del ícono, fuera de contenedores */
        #popoverInfoZonaFlotante { position: fixed; z-index: 9999; min-width: 220px; max-width: 280px; padding: 8px 10px; font-size: 0.75rem; line-height: 1.35; color: #374151; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); white-space: normal; display: none; }
        #popoverInfoZonaFlotante.visible { display: block; }
        #modalCargaCrono.hidden { display: none !important; }
        #modalCargaCrono { display: flex; }
        @keyframes crono-loading-bar {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        .crono-loading-bar-track { height: 6px; }
        .crono-loading-bar {
            width: 0%;
            height: 6px;
            min-height: 6px;
            background: linear-gradient(90deg, #0ea5e9, #38bdf8);
            animation: crono-loading-bar 2s ease-out forwards;
        }
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
        /* Registro: en panelProgramaInfo solo mostrar Programa (sin tab Granjas) */
        body:not(.en-modal-editar-crono) #panelProgramaInfo .tabs-crono-resultado { display: none !important; }
        body:not(.en-modal-editar-crono) #panelProgramaInfo #tabPanelProgramaInfoFechas,
        body:not(.en-modal-editar-crono) #panelProgramaInfo #tabPanelProgramaInfoGranjas { display: none !important; }
        body:not(.en-modal-editar-crono) #panelProgramaInfo #tabPanelProgramaInfoPrograma { display: block !important; }
        body:not(.en-modal-editar-crono) #panelProgramaInfo #panelProgramaInfoResumen { display: none !important; }
        /* Edición: mostrar tabs Granjas | Programa como en modal Detalles */
        body.en-modal-editar-crono #panelProgramaInfo .tabs-crono-resultado { display: flex; gap: 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 1rem; }
        body.en-modal-editar-crono .tabs-crono-resultado .tab-btn { padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; color: #64748b; background: transparent; border: none; border-bottom: 2px solid transparent; cursor: pointer; margin-bottom: -1px; }
        body.en-modal-editar-crono .tabs-crono-resultado .tab-btn:hover { color: #2563eb; }
        body.en-modal-editar-crono .tabs-crono-resultado .tab-btn.active { color: #2563eb; border-bottom-color: #2563eb; }
        /* Crono Granjas: un solo toolbar (Mostrar + Buscar) visible en lista e iconos; estilos alineados con dashboard-config */
        .crono-granjas-wrapper[data-vista="iconos"] .view-lista-wrap-crono { display: none !important; }
        .crono-granjas-wrapper[data-vista="iconos"] .view-tarjetas-wrap-crono { display: block !important; }
        .crono-granjas-wrapper[data-vista="lista"] .view-tarjetas-wrap-crono { display: none !important; }
        .crono-granjas-wrapper .view-tarjetas-wrap-crono { display: none; }
        .crono-granjas-toolbar-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1rem; }
        .crono-dt-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1rem; }
        .crono-dt-controls .buscar-granjas {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #374151;
            min-height: 2.25rem;
            min-width: 180px;
            box-sizing: border-box;
        }
        .crono-dt-controls .buscar-granjas:hover { border-color: #9ca3af; }
        .crono-dt-controls .buscar-granjas:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
        .crono-cards-controls-top { margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        /* Paginación lista e iconos: misma estructura que reportes (dt-bottom-row + dataTables_*); estilos en dashboard-config.css */
        .crono-cards-pagination.dt-bottom-row { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
        /* Grid y tarjetas: usar estilos globales .cards-grid.cards-grid-iconos (dashboard-vista-tabla-iconos.css) para responsividad */
        .view-tarjetas-wrap-crono { max-width: 100%; min-width: 0; box-sizing: border-box; }
        .crono-card-item { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .crono-card-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .crono-card-item .card-codigo { font-weight: 700; font-size: 1rem; color: #1e40af; margin-bottom: 0.5rem; }
        .crono-card-item .card-row { font-size: 0.8rem; color: #4b5563; margin-bottom: 0.25rem; }
        .crono-card-item .card-row .label { color: #6b7280; }
        /* Dentro del modal de edición (iframe): sin gris, contenedor plano y contenido compacto arriba */
        body.en-modal-editar-crono { background: transparent !important; padding: 0 !important; }
        body.en-modal-editar-crono .cronograma-form-wrap {
            background: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            margin-bottom: 0 !important;
        }
        body.en-modal-editar-crono .w-full.max-w-full {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }
        body.en-modal-editar-crono .cronograma-form-wrap > div {
            padding: 0.5rem 0.75rem !important;
            display: flex;
            flex-direction: column;
            gap: 0.5rem !important;
        }
        body.en-modal-editar-crono .cronograma-form-wrap label { margin-bottom: 0.25rem !important; font-size: 0.8125rem !important; }
        body.en-modal-editar-crono .cronograma-form-wrap .form-control {
            padding: 0.375rem 0.5rem !important;
            font-size: 0.8125rem !important;
            min-height: 32px;
        }
        body.en-modal-editar-crono .cronograma-form-wrap .grid { gap: 0.5rem 0.75rem !important; }
        body.en-modal-editar-crono .cronograma-form-wrap #cronoZona[multiple] { min-height: 56px !important; }
        body.en-modal-editar-crono .cronograma-form-wrap .bloque-especifico.space-y-4 { margin-top: 0.5rem !important; }
        body.en-modal-editar-crono .cronograma-form-wrap .bloque-especifico.space-y-4 .grid { gap: 0.5rem !important; }
        body.en-modal-editar-crono .cronograma-form-wrap .btn-primary { padding: 0.375rem 0.75rem !important; font-size: 0.8125rem !important; }
        body.en-modal-editar-crono .select2-container { height: 32px !important; }
        body.en-modal-editar-crono .select2-container .select2-selection--single {
            min-height: 32px !important;
            height: 32px !important;
            padding: 0.25rem 0.5rem !important;
        }
        body.en-modal-editar-crono .select2-container .select2-selection--single .select2-selection__rendered { line-height: 20px !important; }
        body.en-modal-editar-crono .select2-container .select2-selection--single .select2-selection__arrow,
        body.en-modal-editar-crono .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 30px !important;
            top: 1px !important;
        }
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
            <div class="flex flex-col items-center gap-3">
                <img src="../../../assets/img/gallina.gif" alt="Cargando..." class="w-32 h-32" onerror="this.style.display='none'">
                <div class="crono-loading-bar-track w-full max-w-xs h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div class="crono-loading-bar rounded-full"></div>
                </div>
            </div>
            <p id="modalCargaCronoTitulo" class="text-lg font-semibold text-gray-800 mt-4">Calculando fechas...</p>
            <p id="modalCargaCronoTexto" class="text-sm text-gray-600 mt-2">Por favor espere, estamos procesando la asignación</p>
        </div>
    </div>
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border">
        <div class="cronograma-form-wrap mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <div id="panelProgramaInfo" class="hidden mt-4 p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                    <p id="panelProgramaInfoResumen" class="text-xs font-medium text-gray-500 mb-2" style="display:none;">Programa: <strong id="panelProgramaInfoCod"></strong> — <span id="panelProgramaInfoTotal">0</span> registro(s)</p>
                    <div class="tabs-crono-resultado">
                        <button type="button" class="tab-btn active" data-tab="fechas" data-context="panelinfo">Fechas</button>
                        <button type="button" class="tab-btn" data-tab="granjas" data-context="panelinfo">Granjas</button>
                        <button type="button" class="tab-btn" data-tab="programa" data-context="panelinfo">Programa</button>
                    </div>
                    <div id="tabPanelProgramaInfoFechas" class="tab-panel-crono active">
                        <p id="panelProgramaInfoGranjasSinAsignar" class="text-gray-500 text-sm">Asigne granjas y fechas para ver el listado.</p>
                        <div id="panelProgramaInfoGranjasContenido" class="hidden"></div>
                    </div>
                    <div id="tabPanelProgramaInfoGranjas" class="tab-panel-crono">
                        <p class="text-sm text-gray-500 mb-3">Combinaciones únicas granja / galpón / campaña.</p>
                        <div class="table-wrapper overflow-x-auto">
                            <table class="tabla-fechas-crono w-full text-sm border-collapse">
                                <thead><tr><th class="px-3 py-2 text-left bg-blue-600 text-white">N°</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Granja</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Nom. Granja</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Galpón</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Campaña</th></tr></thead>
                                <tbody id="panelProgramaInfoGranjasUnicos"></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="tabPanelProgramaInfoPrograma" class="tab-panel-crono">
                        <div id="panelProgramaInfoCab" class="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"></div>
                        <div class="table-wrapper overflow-x-auto">
                            <table class="tabla-fechas-crono config-table w-full text-sm" id="tablaPanelProgramaInfo">
                                <thead id="panelProgramaInfoThead"></thead>
                                <tbody id="panelProgramaInfoBody"></tbody>
                            </table>
                        </div>
                        <p id="panelProgramaInfoSinReg" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle del programa.</p>
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Granja *</label>
                        <select id="cronoGranja" class="form-control" style="max-width: 280px;">
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    <div id="bloqueGalponesCampanias">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Galpones y campañas *</label>
                        <div id="contenedorGalponesCampanias" class="space-y-0">
                            <p class="text-cargando text-gray-500 text-sm">Seleccione una granja para cargar galpones y sus campañas.</p>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">Marque las campañas que desee por cada galpón.</p>
                    </div>
                    <button type="button" id="btnAsignar" class="btn-primary w-full sm:w-auto">
                        <i class="fas fa-calendar-check mr-1"></i> Asignar
                    </button>
                    <div id="fechasResultado" class="hidden mt-2 p-3 rounded-lg text-sm">
                        <p id="editarResumenProgramaEspecifico" class="text-xs font-medium text-gray-500 mb-2" style="display:none;">Programa: <strong id="editarCodProgramaEspecifico"></strong> — <span id="editarTotalRegistrosEspecifico">0</span> registro(s)</p>
                        <div class="tabs-crono-resultado">
                            <button type="button" class="tab-btn active" data-tab="fechas" data-context="especifico">Fechas</button>
                            <button type="button" class="tab-btn" data-tab="granjas" data-context="especifico">Granjas</button>
                            <button type="button" class="tab-btn" data-tab="programa" data-context="especifico">Programa</button>
                        </div>
                        <div id="tabPanelFechasEspecifico" class="tab-panel-crono active"></div>
                        <div id="tabPanelGranjasEspecifico" class="tab-panel-crono">
                            <p class="text-sm text-gray-500 mb-3">Combinaciones únicas granja / galpón / campaña.</p>
                            <div class="table-wrapper overflow-x-auto">
                                <table class="tabla-fechas-crono w-full text-sm border-collapse">
                                    <thead><tr><th class="px-3 py-2 text-left bg-blue-600 text-white">N°</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Granja</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Nom. Granja</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Galpón</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Campaña</th></tr></thead>
                                    <tbody id="granjasUnicosEspecifico"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="tabPanelProgramaEspecifico" class="tab-panel-crono">
                            <div id="resultadoProgramaCabEspecifico" class="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"></div>
                            <div class="table-wrapper overflow-x-auto">
                                <table class="tabla-fechas-crono config-table w-full text-sm border-collapse" id="tablaResultadoProgramaEspecifico">
                                    <thead id="resultadoProgramaTheadEspecifico"></thead>
                                    <tbody id="resultadoProgramaBodyEspecifico"></tbody>
                                </table>
                            </div>
                            <p id="resultadoProgramaSinRegEspecifico" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle del programa.</p>
                        </div>
                    </div>
                </div>
                <div id="bloqueAsignarZonas" class="bloque-especifico" style="display:none;">
                    <button type="button" id="btnAsignarZonas" class="btn-primary">
                        <i class="fas fa-calendar-check mr-1"></i> Calcular fechas
                    </button>
                    <div id="fechasResultadoZonas" class="hidden mt-2 p-3 rounded-lg text-sm">
                        <p id="editarResumenProgramaZonas" class="text-xs font-medium text-gray-500 mb-2" style="display:none;">Programa: <strong id="editarCodProgramaZonas"></strong> — <span id="editarTotalRegistrosZonas">0</span> registro(s)</p>
                        <div class="tabs-crono-resultado">
                            <button type="button" class="tab-btn active" data-tab="fechas" data-context="zonas">Fechas</button>
                            <button type="button" class="tab-btn" data-tab="granjas" data-context="zonas">Granjas</button>
                            <button type="button" class="tab-btn" data-tab="programa" data-context="zonas">Programa</button>
                        </div>
                        <div id="tabPanelFechasZonas" class="tab-panel-crono active"></div>
                        <div id="tabPanelGranjasZonas" class="tab-panel-crono">
                            <p class="text-sm text-gray-500 mb-3">Combinaciones únicas granja / galpón / campaña.</p>
                            <div class="table-wrapper overflow-x-auto">
                                <table class="tabla-fechas-crono w-full text-sm border-collapse">
                                    <thead><tr><th class="px-3 py-2 text-left bg-blue-600 text-white">N°</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Granja</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Nom. Granja</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Galpón</th><th class="px-3 py-2 text-left bg-blue-600 text-white">Campaña</th></tr></thead>
                                    <tbody id="granjasUnicosZonas"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="tabPanelProgramaZonas" class="tab-panel-crono">
                            <div id="resultadoProgramaCabZonas" class="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"></div>
                            <div class="table-wrapper overflow-x-auto">
                                <table class="tabla-fechas-crono config-table w-full text-sm border-collapse" id="tablaResultadoProgramaZonas">
                                    <thead id="resultadoProgramaTheadZonas"></thead>
                                    <tbody id="resultadoProgramaBodyZonas"></tbody>
                                </table>
                            </div>
                            <p id="resultadoProgramaSinRegZonas" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle del programa.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div id="cronogramaFooter" class="px-6 pb-6 dashboard-actions flex flex-wrap justify-end gap-4">
                <button type="button" id="btnLimpiarCrono" class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 text-sm font-medium">Limpiar</button>
                <button type="button" id="btnGuardarCrono" class="btn-primary px-6 py-2.5" disabled>
                    <i class="fas fa-save"></i> Guardar Asignación
                </button>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        (function() {
            var params = new URLSearchParams(window.location.search);
            var editar = params.get('editar') === '1';
            var numCron = (params.get('numCronograma') || '').trim();
            window._modoEditarCrono = editar && numCron !== '' && window.self !== window.top;
            window._numCronogramaEditar = numCron;
        })();
        let fechasAsignadas = [];
        let paresCargaEjecucion = []; // [{ edad, fechaCarga, fechaEjecucion }] para modo zona
        let itemsEspecifico = [];
        let itemsZonas = []; // modo zonas: [{ granja, campania, galpon, fechas: [{ edad, fechaCarga, fechaEjecucion }] }]
        var programasMap = {};
        var granjasMap = {}; // codigo (3 chars) -> nombre granja
        var granjasMetaMap = {}; // codigo (3 chars) -> { zona, subzona }
        var edadProgramaCrono = null; // primera edad del programa (siempre registrar)

        if (window._modoEditarCrono) {
            document.body.classList.add('en-modal-editar-crono');
            var foot = document.getElementById('cronogramaFooter');
            if (foot) foot.style.display = 'none';
            var selTipo = document.getElementById('cronoTipo');
            if (selTipo) selTipo.disabled = true;
            var selAnio = document.getElementById('cronoAnio');
            if (selAnio) selAnio.disabled = true;
        }

        function formatoDDMMYYYY(ymd) {
            if (!ymd) return '';
            var s = String(ymd).trim();
            var p = s.split('-');
            if (p.length >= 3) return (p[2].length === 1 ? '0' + p[2] : p[2]) + '/' + (p[1].length === 1 ? '0' + p[1] : p[1]) + '/' + p[0];
            return s;
        }
        function fechaHoyYMD() {
            var d = new Date();
            var m = d.getMonth() + 1, dt = d.getDate();
            return d.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (dt < 10 ? '0' : '') + dt;
        }
        function extraerFechaYMD(val) {
            if (!val) return '';
            var s = String(val).trim();
            var m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
            return m ? m[1] + '-' + m[2] + '-' + m[3] : s;
        }
        function tieneFechasAnterioresHoy(items) {
            if (!items || !items.length) return false;
            var hoy = fechaHoyYMD();
            for (var i = 0; i < items.length; i++) {
                var fechas = items[i].fechas || [];
                for (var j = 0; j < fechas.length; j++) {
                    var fec = extraerFechaYMD(fechas[j].fechaEjecucion || fechas[j].fechaCarga);
                    if (fec && fec < hoy) return true;
                }
            }
            return false;
        }
        function filtrarItemsSoloDesdeHoy(items) {
            if (!items || !items.length) return items;
            var hoy = fechaHoyYMD();
            return items.map(function(it) {
                var fechasFiltradas = (it.fechas || []).filter(function(f) {
                    var fec = extraerFechaYMD(f.fechaEjecucion || f.fechaCarga);
                    return fec && fec >= hoy;
                });
                return fechasFiltradas.length > 0 ? Object.assign({}, it, { fechas: fechasFiltradas }) : null;
            }).filter(Boolean);
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
        function keyDetalleSinEdad(d, colsSinEdad) {
            var parts = [];
            colsSinEdad.forEach(function(k) { parts.push(valorCeldaDetalleCrono(k, d)); });
            return parts.join('\t');
        }
        function agruparDetallesPorEdadPanel(detalles, colsSinNum) {
            if (!detalles || detalles.length === 0) return [];
            var colsSinEdad = colsSinNum.filter(function(k) { return k !== 'edad'; });
            var map = {};
            detalles.forEach(function(d) {
                var key = keyDetalleSinEdad(d, colsSinEdad);
                if (!map[key]) map[key] = [];
                map[key].push(d);
            });
            var out = [];
            Object.keys(map).forEach(function(key) {
                var group = map[key];
                var first = group[0];
                var ages = [];
                group.forEach(function(r) {
                    var e = r.edad;
                    if (e !== null && e !== undefined && (e + '').trim() !== '') ages.push((e + '').trim());
                });
                ages.sort(function(a, b) {
                    var na = parseFloat(a, 10);
                    var nb = parseFloat(b, 10);
                    if (isNaN(na)) na = 0;
                    if (isNaN(nb)) nb = 0;
                    return na - nb;
                });
                var merged = Object.assign({}, first);
                merged.edad = ages.length > 0 ? ages.join(', ') : (first.edad !== null && first.edad !== undefined ? (first.edad + '') : '');
                out.push(merged);
            });
            return out;
        }
        function rellenarProgramaCabDetalle(codPrograma, cabEl, theadEl, tbodyEl, sinRegEl, onDone) {
            if (!cabEl || !theadEl || !tbodyEl || !sinRegEl) { if (onDone) onDone(); return; }
            cabEl.innerHTML = '<span class="text-gray-500">Cargando...</span>';
            theadEl.innerHTML = '';
            tbodyEl.innerHTML = '';
            sinRegEl.classList.add('hidden');
            if (!codPrograma || String(codPrograma).trim() === '') {
                cabEl.innerHTML = '<span class="text-gray-500">No hay programa seleccionado.</span>';
                if (onDone) onDone();
                return;
            }
            fetch('../programas/get_programa_cab_detalle.php?codigo=' + encodeURIComponent(codPrograma)).then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) {
                    cabEl.innerHTML = '<span class="text-red-600">' + esc(res.message || 'Error al cargar programa.') + '</span>';
                    if (onDone) onDone();
                    return;
                }
                var cab = res.cab || {};
                var detalles = res.detalles || [];
                var cabHtml = '<div class="font-semibold text-gray-800 mb-1">' + esc(cab.codigo) + ' — ' + esc(cab.nombre) + '</div>';
                cabHtml += '<dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-600">';
                cabHtml += '<dt class="font-medium">Tipo</dt><dd>' + esc(cab.nomTipo || '') + '</dd>';
                if (cab.despliegue) { cabHtml += '<dt class="font-medium">Despliegue</dt><dd>' + esc(cab.despliegue) + '</dd>'; }
                if (cab.descripcion) { cabHtml += '<dt class="font-medium col-span-2">Descripción</dt><dd class="col-span-2">' + esc(cab.descripcion) + '</dd>'; }
                if (cab.fechaInicio) { cabHtml += '<dt class="font-medium">Fecha inicio</dt><dd>' + esc(formatoDDMMYYYY((cab.fechaInicio || '').toString().substring(0, 10))) + '</dd>'; }
                if (cab.fechaFin) { cabHtml += '<dt class="font-medium">Fecha fin</dt><dd>' + esc(formatoDDMMYYYY((cab.fechaFin || '').toString().substring(0, 10))) + '</dd>'; }
                cabHtml += '</dl>';
                cabEl.innerHTML = cabHtml;
                var sigla = (res.sigla || 'PL').toUpperCase();
                if (sigla === 'NEC') sigla = 'NC';
                var cols = columnasPorSiglaReporte[sigla] || columnasPorSiglaReporte['PL'];
                var colsSinNum = cols.filter(function(k) { return k !== 'num'; });
                if (colsSinNum.indexOf('edad') !== -1) {
                    colsSinNum = colsSinNum.filter(function(k) { return k !== 'edad'; });
                    colsSinNum.push('edad');
                }
                var thCells = '<th class="px-3 py-2 text-left">N°</th><th class="px-3 py-2 text-left">Código</th><th class="px-3 py-2 text-left">Nombre programa</th><th class="px-3 py-2 text-left">Despliegue</th><th class="px-3 py-2 text-left">Descripción</th>';
                colsSinNum.forEach(function(k) { thCells += '<th class="px-3 py-2 text-left">' + (labelsReporteCrono[k] || k) + '</th>'; });
                theadEl.innerHTML = '<tr>' + thCells + '</tr>';
                tbodyEl.innerHTML = '';
                if (detalles.length === 0) {
                    sinRegEl.classList.remove('hidden');
                } else {
                    var filasAgrupadas = agruparDetallesPorEdadPanel(detalles, colsSinNum);
                    filasAgrupadas.forEach(function(d, idx) {
                        var tr = document.createElement('tr');
                        tr.className = 'border-b border-gray-200';
                        var num = idx + 1;
                        var td = '<td class="px-3 py-2">' + num + '</td><td class="px-3 py-2">' + esc(cab.codigo) + '</td><td class="px-3 py-2">' + esc(cab.nombre) + '</td><td class="px-3 py-2">' + esc(cab.despliegue || '') + '</td><td class="px-3 py-2">' + esc(cab.descripcion || '') + '</td>';
                        colsSinNum.forEach(function(k) {
                            td += '<td class="px-3 py-2"' + (k === 'descripcion_vacuna' ? ' style="white-space:pre-wrap;"' : '') + '>' + valorCeldaDetalleCrono(k, d) + '</td>';
                        });
                        tr.innerHTML = td;
                        tbodyEl.appendChild(tr);
                    });
                }
                if (onDone) onDone();
            }).catch(function() {
                cabEl.innerHTML = '<span class="text-red-600">Error al cargar el programa.</span>';
                if (onDone) onDone();
            });
        }

        function cargarPanelProgramaInfo(codPrograma) {
            var panel = document.getElementById('panelProgramaInfo');
            var cabEl = document.getElementById('panelProgramaInfoCab');
            var theadEl = document.getElementById('panelProgramaInfoThead');
            var tbodyEl = document.getElementById('panelProgramaInfoBody');
            var sinRegEl = document.getElementById('panelProgramaInfoSinReg');
            var granjasSinAsignar = document.getElementById('panelProgramaInfoGranjasSinAsignar');
            var granjasContenido = document.getElementById('panelProgramaInfoGranjasContenido');
            if (!panel) return;
            if (!codPrograma || String(codPrograma).trim() === '') {
                panel.classList.add('hidden');
                return;
            }
            panel.classList.remove('hidden');
            rellenarProgramaCabDetalle(codPrograma, cabEl, theadEl, tbodyEl, sinRegEl);
            var cabEsp = document.getElementById('resultadoProgramaCabEspecifico');
            var theadEsp = document.getElementById('resultadoProgramaTheadEspecifico');
            var tbodyEsp = document.getElementById('resultadoProgramaBodyEspecifico');
            var sinRegEsp = document.getElementById('resultadoProgramaSinRegEspecifico');
            if (cabEsp && theadEsp && tbodyEsp && sinRegEsp) {
                rellenarProgramaCabDetalle(codPrograma, cabEsp, theadEsp, tbodyEsp, sinRegEsp);
            }
            var cabZon = document.getElementById('resultadoProgramaCabZonas');
            var theadZon = document.getElementById('resultadoProgramaTheadZonas');
            var tbodyZon = document.getElementById('resultadoProgramaBodyZonas');
            var sinRegZon = document.getElementById('resultadoProgramaSinRegZonas');
            if (cabZon && theadZon && tbodyZon && sinRegZon) {
                rellenarProgramaCabDetalle(codPrograma, cabZon, theadZon, tbodyZon, sinRegZon);
            }
            if (granjasSinAsignar && granjasContenido) {
                if (itemsEspecifico && itemsEspecifico.length > 0) {
                    granjasSinAsignar.classList.add('hidden');
                    granjasContenido.classList.remove('hidden');
                    var zonaD = '';
                    var subzonaD = '';
                    var esEdicion = !!(window._modoEditarCrono && window._numCronogramaEditar);
                    if (!esEdicion && window._datosEdicionCrono) {
                        var d = window._datosEdicionCrono;
                        zonaD = (d.zona != null && String(d.zona).trim() !== '') ? String(d.zona).trim() : '';
                        subzonaD = (d.subzona != null && String(d.subzona).trim() !== '') ? String(d.subzona).trim() : '';
                    }
                    var filas = [];
                    itemsEspecifico.forEach(function(it) {
                        var nomG = (granjasMap[it.granja] || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;');
                        (it.fechas || []).forEach(function(f) {
                            var fe = (f && typeof f === 'object') ? f : {};
                            var campaniaFila = (fe.campania != null && String(fe.campania).trim() !== '') ? String(fe.campania).trim() : (it.campania || '—');
                            filas.push({ codPrograma: codPrograma, zona: zonaD, subzona: subzonaD, granja: it.granja || '—', nomGranja: nomG || '—', campania: campaniaFila, galpon: it.galpon || '—', edad: (fe.edad != null ? fe.edad : '—'), fechaCarga: formatoDDMMYYYY(fe.fechaCarga), fechaEjec: formatoDDMMYYYY(fe.fechaEjecucion) });
                        });
                    });
                    filas = ordenarFilasFechasCrono(filas, !esEdicion && (!!zonaD || !!subzonaD));
                    var totalTexto = esEdicion ? ('<strong>Total:</strong> ' + filas.length + ' registro(s)') : ((zonaD || subzonaD) ? ('<strong>Zona:</strong> ' + (zonaD || '—') + ' &nbsp;·&nbsp; <strong>Subzona:</strong> ' + (subzonaD || '—') + ' &nbsp;·&nbsp; <strong>Total:</strong> ' + filas.length + ' registro(s)') : ('<strong>Total:</strong> ' + filas.length + ' registro(s)'));
                    renderTablaGranjasPaginada(filas, granjasContenido, esEdicion ? 'especifico' : 'zonas', totalTexto, esEdicion ? 'Editar' : '');
                    renderGranjasUnicosRegistro(filas, 'panelProgramaInfoGranjasUnicos');
                    var resumenPanel = document.getElementById('panelProgramaInfoResumen');
                    if (resumenPanel) {
                        document.getElementById('panelProgramaInfoCod').textContent = codPrograma || '—';
                        document.getElementById('panelProgramaInfoTotal').textContent = filas.length;
                        resumenPanel.style.display = 'block';
                    }
                } else {
                    granjasSinAsignar.classList.remove('hidden');
                    granjasContenido.classList.add('hidden');
                    granjasContenido.innerHTML = '';
                    var resumenPanel = document.getElementById('panelProgramaInfoResumen');
                    if (resumenPanel) resumenPanel.style.display = 'none';
                }
            }
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
            var container = ctx === 'panelinfo' ? document.getElementById('panelProgramaInfo') : (ctx === 'zonas' ? document.getElementById('fechasResultadoZonas') : document.getElementById('fechasResultado'));
            if (!container) return;
            container.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
            container.querySelectorAll('.tab-panel-crono').forEach(function(p) { p.classList.remove('active'); });
            tabBtn.classList.add('active');
            if (tab === 'fechas') {
                var panel = ctx === 'panelinfo' ? document.getElementById('tabPanelProgramaInfoFechas') : (ctx === 'zonas' ? document.getElementById('tabPanelFechasZonas') : document.getElementById('tabPanelFechasEspecifico'));
                if (panel) panel.classList.add('active');
            } else if (tab === 'granjas') {
                var panel = ctx === 'panelinfo' ? document.getElementById('tabPanelProgramaInfoGranjas') : (ctx === 'zonas' ? document.getElementById('tabPanelGranjasZonas') : document.getElementById('tabPanelGranjasEspecifico'));
                if (panel) panel.classList.add('active');
            } else if (tab === 'programa') {
                var panel = ctx === 'panelinfo' ? document.getElementById('tabPanelProgramaInfoPrograma') : (ctx === 'zonas' ? document.getElementById('tabPanelProgramaZonas') : document.getElementById('tabPanelProgramaEspecifico'));
                if (panel) panel.classList.add('active');
            }
        });
        var _cronoModalCargaMostradoDesde = 0;
        var _cronoModalCargaMinMs = 2000;
        function mostrarCarga(mostrar, titulo, texto, alOcultar, tiempoBackend, tiempoFrontend) {
            var enModoEditar = !!(window._modoEditarCrono && typeof window.parent !== 'undefined');
            var el = document.getElementById('modalCargaCrono');
            var ttl = document.getElementById('modalCargaCronoTitulo');
            var txt = document.getElementById('modalCargaCronoTexto');
            var tiempoEl = document.getElementById('modalCargaCronoTiempo');
            var tiempoBack = (typeof tiempoBackend === 'number' && tiempoBackend > 0) ? Math.round(tiempoBackend / 1000) : null;
            var tiempoFront = (typeof tiempoFrontend === 'number' && tiempoFrontend > 0) ? Math.round(tiempoFrontend / 1000) : null;
           
            if (mostrar) {
                if (enModoEditar) {
                    try { window.parent.postMessage({ tipo: 'mostrarCargaCrono', titulo: titulo || 'Actualizando asignación', texto: texto || 'Se está actualizando la asignación en el calendario.', tiempoBackend: tiempoBack, tiempoFrontend: tiempoFront }, '*'); } catch (e) {}
                } else {
                    if (ttl) ttl.textContent = titulo || 'Calculando fechas...';
                    if (txt) txt.textContent = texto || 'Por favor espere, estamos procesando la asignación';
                 
                    _cronoModalCargaMostradoDesde = Date.now();
                    if (el) el.classList.remove('hidden');
                }
                _cronoModalCargaMostradoDesde = Date.now();
            } else {
                var transcurrido = Date.now() - _cronoModalCargaMostradoDesde;
                var faltante = Math.max(0, _cronoModalCargaMinMs - transcurrido);
                var ejecutarAlOcultar = function() {
                    if (enModoEditar) {
                        try { window.parent.postMessage({ tipo: 'ocultarCargaCrono' }, '*'); } catch (e) {}
                    } else if (el) {
                        el.classList.add('hidden');
                    }
                    if (typeof alOcultar === 'function') alOcultar();
                };
                if (faltante > 0) {
                    setTimeout(ejecutarAlOcultar, faltante);
                } else {
                    ejecutarAlOcultar();
                }
            }
        }

        var PAGE_SIZE_GRANJAS = 20;
        
        function ordenarFilasFechasCrono(filas, conZonaSubzona) {
            if (!filas || filas.length === 0) return [];
            return filas.slice().sort(function(a, b) {
                var cmp = (a.codPrograma || '').localeCompare(b.codPrograma || '');
                if (conZonaSubzona) {
                    if (cmp !== 0) return cmp;
                    cmp = (a.zona || '').localeCompare(b.zona || '');
                    if (cmp !== 0) return cmp;
                    cmp = (a.subzona || '').localeCompare(b.subzona || '');
                }
                if (cmp !== 0) return cmp;
                cmp = (a.granja || '').localeCompare(b.granja || '');
                if (cmp !== 0) return cmp;
                cmp = (a.nomGranja || '').localeCompare(b.nomGranja || '');
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
        function filterFilasPorBusqueda(filas, q, isZonas) {
            if (!q || String(q).trim() === '') return filas;
            var term = String(q).trim().toLowerCase();
            return filas.filter(function(r) {
                var txt = (r.codPrograma || '') + ' ' + (r.granja || '') + ' ' + (r.nomGranja || '') + ' ' + (r.campania || '') + ' ' + (r.galpon || '') + ' ' + (r.edad || '') + ' ' + (r.fechaCarga || '') + ' ' + (r.fechaEjec || '');
                if (isZonas) txt += ' ' + (r.zona || '') + ' ' + (r.subzona || '');
                return txt.toLowerCase().indexOf(term) !== -1;
            });
        }
        function granjasUnicosDesdeFilas(filas) {
            var seen = {};
            var out = [];
            (filas || []).forEach(function(r) {
                var g = (r.granja || '').toString().trim();
                var gp = (r.galpon || '').toString().trim();
                var c = (r.campania || '').toString().trim();
                var key = g + '|' + gp + '|' + c;
                if (key !== '||' && !seen[key]) {
                    seen[key] = true;
                    out.push({ granja: g, nomGranja: (r.nomGranja || g).toString().trim(), galpon: gp, campania: c });
                }
            });
            out.sort(function(a, b) {
                var cmp = (a.granja || '').localeCompare(b.granja || '');
                if (cmp !== 0) return cmp;
                cmp = (a.galpon || '').localeCompare(b.galpon || '');
                if (cmp !== 0) return cmp;
                return (a.campania || '').localeCompare(b.campania || '');
            });
            return out;
        }

        function renderGranjasUnicosRegistro(filas, tbodyId) {
            var unicos = granjasUnicosDesdeFilas(filas);
            var tbody = document.getElementById(tbodyId);
            if (!tbody) return;
            tbody.innerHTML = '';
            unicos.forEach(function(x, i) {
                var tr = document.createElement('tr');
                tr.className = 'border-b border-gray-200';
                tr.innerHTML = '<td class="px-3 py-2">' + (i + 1) + '</td>' +
                    '<td class="px-3 py-2">' + (x.granja || '—').replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</td>' +
                    '<td class="px-3 py-2">' + (x.nomGranja || x.granja || '—').replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</td>' +
                    '<td class="px-3 py-2">' + (x.galpon || '—').replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</td>' +
                    '<td class="px-3 py-2">' + (x.campania || '—').replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</td>';
                tbody.appendChild(tr);
            });
        }

        function buildCronoGranjasPaginationHtml(tipo, page, totalPag, total, start, end) {
            var infoText = 'Mostrando ' + (total === 0 ? 0 : start + 1) + ' a ' + end + ' de ' + total + ' registros';
            var prevDisabled = page <= 1;
            var nextDisabled = page >= totalPag;
            var prevClass = prevDisabled ? ' disabled' : '';
            var nextClass = nextDisabled ? ' disabled' : '';
            var html = '<span class="dataTables_info">' + infoText + '</span>';
            html += '<span class="dataTables_paginate paginate_button_wrap">';
            html += '<span class="paginate_button previous' + prevClass + '" data-crono-pagenav="prev" data-context="' + tipo + '" role="button">Anterior</span>';
            html += '<span class="paginate_button current" role="button">Pág. ' + page + ' de ' + totalPag + '</span>';
            html += '<span class="paginate_button next' + nextClass + '" data-crono-pagenav="next" data-context="' + tipo + '" role="button">Siguiente</span>';
            html += '</span>';
            return html;
        }
        function renderCronoGranjasCards(filaPage, tipo, total, totalPag, page, idSuffix) {
            var suf = (idSuffix && String(idSuffix).trim()) ? String(idSuffix).trim() : '';
            var isZonas = (tipo === 'zonas');
            var wrapperId = (isZonas ? 'cronoGranjasWrapperZonas' : 'cronoGranjasWrapperEspecifico') + suf;
            var containerId = (isZonas ? 'cardsContainerGranjasZonas' : 'cardsContainerGranjasEspecifico') + suf;
            var pagId = (isZonas ? 'cardsPaginationGranjasZonas' : 'cardsPaginationGranjasEspecifico') + suf;
            var start = (page - 1) * PAGE_SIZE_GRANJAS;
            var end = Math.min(start + PAGE_SIZE_GRANJAS, total);
            var container = document.getElementById(containerId);
            var pagEl = document.getElementById(pagId);
            if (!container) return;
            var cardsHtml = '';
            filaPage.forEach(function(r, i) {
                var num = start + i + 1;
                cardsHtml += '<div class="crono-card-item card-item">';
                cardsHtml += '<div class="card-numero-row">#' + num + '</div>';
                cardsHtml += '<div class="card-codigo">' + esc(r.codPrograma || '—') + '</div>';
                cardsHtml += '<div class="card-contenido"><div class="card-campos">';
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
                cardsHtml += '</div></div></div>';
            });
            container.innerHTML = cardsHtml;
            if (pagEl) {
                pagEl.innerHTML = buildCronoGranjasPaginationHtml(tipo, page, totalPag, total, start, end);
                var prevBtn = pagEl.querySelector('.paginate_button.previous');
                var nextBtn = pagEl.querySelector('.paginate_button.next');
                if (prevBtn && !prevBtn.classList.contains('disabled')) prevBtn.addEventListener('click', function() { renderGranjasPage(tipo, page - 1, suf); });
                if (nextBtn && !nextBtn.classList.contains('disabled')) nextBtn.addEventListener('click', function() { renderGranjasPage(tipo, page + 1, suf); });
            }
        }
        function renderTablaGranjasPaginada(filas, panel, tipo, totalTexto, idSuffix) {
            if (!panel) return;
            var isZonas = (tipo === 'zonas');
            var suf = (idSuffix && String(idSuffix).trim()) ? String(idSuffix).trim() : '';
            if (isZonas && suf === '') { window._filasGranjasZonas = filas; window._searchGranjasZonas = ''; }
            else if (isZonas && suf === 'Editar') { window._filasGranjasZonasEditar = filas; }
            else if (!isZonas) { window._filasGranjasEspecifico = filas; window._searchGranjasEspecifico = ''; }
            var wrapperId = (isZonas ? 'cronoGranjasWrapperZonas' : 'cronoGranjasWrapperEspecifico') + suf;
            var tableId = (isZonas ? 'tablaGranjasZonas' : 'tablaGranjasEspecifico') + suf;
            var theadZonas = '<tr><th>N°</th><th>cod. programa</th><th>Zona</th><th>Subzona</th><th>Granja</th><th>Nom. Granja</th><th>Campaña</th><th>Galpón</th><th>Edad</th><th>Fec. Carga</th><th>Fec. Ejecución</th></tr>';
            var theadEsp = '<tr><th>N°</th><th>cod. programa</th><th>Granja</th><th>Nom. Granja</th><th>Campaña</th><th>Galpón</th><th>Edad</th><th>Fec. Carga</th><th>Fec. Ejecución</th></tr>';
            var thead = isZonas ? theadZonas : theadEsp;
            var tbodyId = (isZonas ? 'tbodyGranjasZonas' : 'tbodyGranjasEspecifico') + suf;
            var pagId = (isZonas ? 'paginacionGranjasZonas' : 'paginacionGranjasEspecifico') + suf;
            var searchId = (isZonas ? 'searchGranjasZonas' : 'searchGranjasEspecifico') + suf;
            var sizeSelectId = (isZonas ? 'granjasSizeZonas' : 'granjasSizeEspecifico') + suf;
            var cardsTopId = (isZonas ? 'cardsControlsTopGranjasZonas' : 'cardsControlsTopGranjasEspecifico') + suf;
            var cardsContainerId = (isZonas ? 'cardsContainerGranjasZonas' : 'cardsContainerGranjasEspecifico') + suf;
            var cardsPagId = (isZonas ? 'cardsPaginationGranjasZonas' : 'cardsPaginationGranjasEspecifico') + suf;
            var sizeOpts = PAGE_SIZE_GRANJAS === 20 ? ' selected' : '';
            var sizeOpts50 = PAGE_SIZE_GRANJAS === 50 ? ' selected' : '';
            var sizeOpts100 = PAGE_SIZE_GRANJAS === 100 ? ' selected' : '';
            var controlsLista = '<div class="crono-dt-controls">' +
                '<label class="inline-flex items-center gap-2" style="margin:0;"><span>Mostrar</span><select id="' + sizeSelectId + '" class="dt-toolbar-length-select cards-length-select" data-granjas-size data-context="' + tipo + '"><option value="20"' + sizeOpts + '>20</option><option value="50"' + sizeOpts50 + '>50</option><option value="100"' + sizeOpts100 + '>100</option></select><span>registros</span></label>' +
                '<label class="inline-flex items-center gap-2" style="margin:0;"><span>Buscar:</span><input type="text" class="buscar-granjas" id="' + searchId + '" placeholder="Buscar..." autocomplete="off" data-context="' + tipo + '"></label>' +
                '</div>';
            var html = (totalTexto ? '<p class="text-gray-600 text-sm mb-3">' + totalTexto + '</p>' : '') +
                '<div class="tabla-crono-wrapper crono-granjas-wrapper dataTables_wrapper" id="' + wrapperId + '" data-vista="lista">' +
                '<div class="crono-granjas-toolbar-row">' +
                '<div class="view-toggle-group flex items-center gap-2">' +   
                '<button type="button" class="view-toggle-btn active" data-crono-view="lista" data-context="' + tipo + '" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>' +
                '<button type="button" class="view-toggle-btn" data-crono-view="iconos" data-context="' + tipo + '" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>' +
                '</div>' + controlsLista + '</div>' +
                '<div class="view-tarjetas-wrap-crono view-tarjetas-wrap px-4 pb-4 overflow-x-hidden">' +
                '<div id="' + cardsTopId + '" class="crono-cards-controls-top"></div>' +
                '<div id="' + cardsContainerId + '" class="cards-grid cards-grid-iconos"></div>' +
                '<div id="' + cardsPagId + '" class="crono-cards-pagination dt-bottom-row dataTables_wrapper" data-context="' + tipo + '"></div>' +
                '</div>' +
                '<div class="view-lista-wrap-crono">' +
                '<div class="table-wrapper overflow-x-auto">' +
                '<table class="tabla-fechas-crono data-table config-table w-full text-sm border-collapse" id="' + tableId + '"><thead>' + thead + '</thead><tbody id="' + tbodyId + '"></tbody></table>' +
                '</div>' +
                '<div id="' + pagId + '" class="tabla-crono-toolbar-bottom dt-bottom-row dataTables_wrapper" data-context="' + tipo + '"></div>' +
                '</div></div>';
            panel.innerHTML = html;
            var wrapper = document.getElementById(wrapperId);
            document.querySelectorAll('#' + wrapperId + ' [data-granjas-size]').forEach(function(sel) {
                sel.addEventListener('change', function() { PAGE_SIZE_GRANJAS = parseInt(this.value, 10) || 20; renderGranjasPage(tipo, 1, suf); });
            });
            document.querySelectorAll('#' + wrapperId + ' .buscar-granjas').forEach(function(inp) {
                inp.addEventListener('input', function() { if (tipo === 'zonas' && suf !== 'Editar') window._searchGranjasZonas = this.value; else if (tipo !== 'zonas') window._searchGranjasEspecifico = this.value; renderGranjasPage(tipo, 1, suf); });
            });
            document.querySelectorAll('#' + wrapperId + ' [data-crono-view]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var v = btn.getAttribute('data-crono-view');
                    wrapper.setAttribute('data-vista', v);
                    document.querySelectorAll('#' + wrapperId + ' .view-toggle-btn').forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    renderGranjasPage(tipo, window._cronoGranjasCurrentPage ? window._cronoGranjasCurrentPage[tipo] || 1 : 1, suf);
                });
            });
            renderGranjasPage(tipo, 1, suf);
        }
        function renderGranjasPage(tipo, page, idSuffix) {
            var suf = (idSuffix && String(idSuffix).trim()) ? String(idSuffix).trim() : '';
            var isZonas = (tipo === 'zonas');
            var filasCompletas = isZonas ? (suf === 'Editar' ? (window._filasGranjasZonasEditar || []) : (window._filasGranjasZonas || [])) : (window._filasGranjasEspecifico || []);
            var searchQ = isZonas ? (window._searchGranjasZonas || '') : (window._searchGranjasEspecifico || '');
            var filas = filterFilasPorBusqueda(filasCompletas, searchQ, isZonas);
            var total = filas.length;
            var totalPag = Math.max(1, Math.ceil(total / PAGE_SIZE_GRANJAS));
            page = Math.max(1, Math.min(page, totalPag));
            if (!window._cronoGranjasCurrentPage) window._cronoGranjasCurrentPage = {};
            window._cronoGranjasCurrentPage[tipo] = page;
            var tbodyId = (isZonas ? 'tbodyGranjasZonas' : 'tbodyGranjasEspecifico') + suf;
            var pagId = (isZonas ? 'paginacionGranjasZonas' : 'paginacionGranjasEspecifico') + suf;
            var wrapperId = (isZonas ? 'cronoGranjasWrapperZonas' : 'cronoGranjasWrapperEspecifico') + suf;
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
                if (prevBtn && !prevBtn.classList.contains('disabled')) prevBtn.addEventListener('click', function() { renderGranjasPage(tipo, page - 1, suf); });
                if (nextBtn && !nextBtn.classList.contains('disabled')) nextBtn.addEventListener('click', function() { renderGranjasPage(tipo, page + 1, suf); });
            }
            var searchIdEl = (isZonas ? 'searchGranjasZonas' : 'searchGranjasEspecifico') + suf;
            var sizeSelectIdEl = (isZonas ? 'granjasSizeZonas' : 'granjasSizeEspecifico') + suf;
            var searchEl = document.getElementById(searchIdEl);
            if (searchEl) searchEl.value = searchQ;
            var sizeEl = document.getElementById(sizeSelectIdEl);
            if (sizeEl) sizeEl.value = String(PAGE_SIZE_GRANJAS);
            var wrapper = document.getElementById(wrapperId);
            if (wrapper && wrapper.getAttribute('data-vista') === 'iconos') {
                renderCronoGranjasCards(filaPage, tipo, total, totalPag, page, suf);
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

        function asList(res) {
            if (!res) return [];
            if (Array.isArray(res.data)) return res.data;
            if (Array.isArray(res)) return res;
            return [];
        }

        function cargarGranjas() {
            fetch('get_granjas.php').then(r => r.json()).then(function(res) {
                var data = asList(res);
                var sel = document.getElementById('cronoGranja');
                sel.innerHTML = '<option value="">Seleccione...</option>';
                granjasMap = {};
                granjasMetaMap = {};
                data.forEach(g => {
                    var cod = (g.codigo != null) ? String(g.codigo).trim().substring(0, 3) : '';
                    if (cod) granjasMap[cod] = (g.nombre != null) ? String(g.nombre).trim() : '';
                    if (cod) {
                        granjasMetaMap[cod] = {
                            zona: (g.zona != null) ? String(g.zona).trim() : '',
                            subzona: (g.subzona != null) ? String(g.subzona).trim() : ''
                        };
                    }
                    var opt = document.createElement('option');
                    opt.value = cod || g.codigo;
                    opt.textContent = (g.codigo || '') + ' - ' + (g.nombre || '');
                    sel.appendChild(opt);
                });
            }).catch(() => {});
        }

        function obtenerZonaSubzonaRealDeGranja(codGranja) {
            var cod = (codGranja || '').toString().trim().substring(0, 3);
            var meta = granjasMetaMap[cod] || {};
            return {
                zona: (meta.zona || '').toString().trim(),
                subzona: (meta.subzona || '').toString().trim()
            };
        }

        function cargarGalponesYCampanias(granja, campaniasPreseleccionPorGalpon) {
            var cont = document.getElementById('contenedorGalponesCampanias');
            if (!cont) return;
            campaniasPreseleccionPorGalpon = campaniasPreseleccionPorGalpon || {};
            if (!granja) {
                cont.innerHTML = '<p class="text-cargando">Seleccione una granja para cargar galpones y sus campañas.</p>';
                return;
            }
            cont.innerHTML = '<p class="text-cargando">Cargando galpones...</p>';
            var anio = (document.getElementById('cronoAnio') && document.getElementById('cronoAnio').value) || new Date().getFullYear();
            fetch('get_galpones.php?codigo=' + encodeURIComponent(granja)).then(r => r.json()).then(function(res) {
                var galpones = asList(res);
                if (galpones.length === 0) {
                    cont.innerHTML = '<p class="text-gray-500 text-sm">No hay galpones para esta granja.</p>';
                    return;
                }
                cont.innerHTML = '';
                galpones.forEach(function(g) {
                    var galpon = String((g && g.galpon) || '').trim();
                    if (!galpon) return;
                    var block = document.createElement('div');
                    block.className = 'galpon-campanias-block';
                    block.setAttribute('data-galpon', galpon);
                    var titulo = document.createElement('div');
                    titulo.className = 'galpon-titulo';
                    var chkGalpon = document.createElement('input');
                    chkGalpon.type = 'checkbox';
                    chkGalpon.className = 'chk-galpon-todo rounded border-gray-300';
                    chkGalpon.setAttribute('data-galpon', galpon);
                    titulo.appendChild(chkGalpon);
                    titulo.appendChild(document.createTextNode('Galpón ' + galpon + (g.nombre ? ' — ' + g.nombre : '')));
                    block.appendChild(titulo);
                    var campaniasWrap = document.createElement('div');
                    campaniasWrap.className = 'campanias-wrap';
                    campaniasWrap.setAttribute('data-galpon', galpon);
                    campaniasWrap.innerHTML = '<span class="text-gray-500 text-sm">Cargando campañas...</span>';
                    block.appendChild(campaniasWrap);
                    cont.appendChild(block);
                    fetch('get_campanias.php?granja=' + encodeURIComponent(granja) + '&galpon=' + encodeURIComponent(galpon) + '&anio=' + encodeURIComponent(anio))
                        .then(function(r) { return r.json(); })
                        .then(function(campRes) {
                            var camps = asList(campRes);
                            var setPre = new Set((campaniasPreseleccionPorGalpon[galpon] || []).map(function(c) { return String(c || '').trim(); }).filter(Boolean));
                            campaniasWrap.innerHTML = '';
                            camps.forEach(function(c) {
                                var camp = String((c && c.campania) || '').trim();
                                if (!camp) return;
                                var label = document.createElement('label');
                                var chk = document.createElement('input');
                                chk.type = 'checkbox';
                                chk.className = 'chk-campania rounded border-gray-300';
                                chk.setAttribute('data-galpon', galpon);
                                chk.value = camp;
                                if (setPre.has(camp)) chk.checked = true;
                                label.appendChild(chk);
                                label.appendChild(document.createTextNode(camp));
                                campaniasWrap.appendChild(label);
                            });
                            chkGalpon.addEventListener('change', function() {
                                campaniasWrap.querySelectorAll('.chk-campania').forEach(function(cb) { cb.checked = chkGalpon.checked; });
                            });
                        })
                        .catch(function() { campaniasWrap.innerHTML = '<span class="text-red-500 text-sm">Error al cargar campañas</span>'; });
                });
            }).catch(function() { cont.innerHTML = '<p class="text-red-500 text-sm">Error al cargar galpones.</p>'; });
        }

        document.getElementById('cronoGranja').addEventListener('change', function() {
            var granja = this.value.trim();
            cargarGalponesYCampanias(granja, {});
        });
        document.getElementById('cronoAnio').addEventListener('change', function() {
            var granja = document.getElementById('cronoGranja').value.trim();
            if (granja) cargarGalponesYCampanias(granja, {});
        });

        function precargarGranjaGalponEspecifico(granjaCod, itemsParaPreseleccion) {
            var selGranja = document.getElementById('cronoGranja');
            var granja = (granjaCod || '').toString().trim().substring(0, 3);
            if (selGranja) selGranja.value = granja;
            if (!granja) {
                cargarGalponesYCampanias('', {});
                return Promise.resolve();
            }
            var porGalpon = {};
            if (Array.isArray(itemsParaPreseleccion)) {
                itemsParaPreseleccion.forEach(function(it) {
                    var gp = String(it.galpon || '').trim();
                    var camp = String(it.campania || '').trim();
                    if (gp && camp) {
                        if (!porGalpon[gp]) porGalpon[gp] = [];
                        if (porGalpon[gp].indexOf(camp) === -1) porGalpon[gp].push(camp);
                    }
                });
            }
            cargarGalponesYCampanias(granja, porGalpon);
            return Promise.resolve();
        }

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
                jQuery('#cronoPrograma').off('select2:select').on('select2:select', function() {
                    actualizarNombrePrograma();
                    cargarPanelProgramaInfo((jQuery(this).val() || '').toString().trim());
                });
            }).catch(function() {
                sel.innerHTML = '<option value="">Error de conexión</option>';
            });
        }

        function cargarZonas() {
            fetch('get_zonas_caracteristicas.php').then(r => r.json()).then(function(res) {
                if (res && res.success === false) return;
                var sel = document.getElementById('cronoZona');
                var zonas = asList(res);
                var opts = '';
                zonas.forEach(function(z) {
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
            fetch('get_subzonas_por_zona.php?zona=' + encodeURIComponent(zona)).then(r => r.json()).then(function(res) {
                var data = asList(res);
                subzonasPorZona[zona] = data;
                if (callback) callback(data);
            }).catch(function() { subzonasPorZona[zona] = []; if (callback) callback([]); });
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
                var header = document.createElement('div');
                header.className = 'zona-subzonas-header';
                var h4 = document.createElement('h4');
                h4.textContent = 'Zona: ' + zona + ' — Subzonas';
                header.appendChild(h4);
                var labelTodas = document.createElement('label');
                labelTodas.className = 'chk-subzona-todas';
                var chkTodas = document.createElement('input');
                chkTodas.type = 'checkbox';
                chkTodas.className = 'chk-subzona-todas-zona rounded border-gray-300';
                chkTodas.setAttribute('data-zona', zona);
                labelTodas.appendChild(chkTodas);
                labelTodas.appendChild(document.createTextNode('Seleccionar todas'));
                header.appendChild(labelTodas);
                div.appendChild(header);
                var wrap = document.createElement('div');
                wrap.className = 'subzonas-list';
                wrap.innerHTML = '<span class="text-gray-500 text-sm">Cargando...</span>';
                div.appendChild(wrap);
                cont.appendChild(div);
                cargarSubzonasParaZona(zona, function(data) {
                    wrap.innerHTML = '';
                    if (data.length === 0) {
                        wrap.innerHTML = '<p class="text-sm text-gray-500">Sin subzonas para esta zona.</p>';
                        chkTodas.style.display = 'none';
                        return;
                    }
                    // Agrupar por subzona única (dato); solo incluir granjas que estén en granjasMap (mismo criterio que modo especifico)
                    var porSubzona = {};
                    data.forEach(function(s) {
                        var d = s.dato || '';
                        if (!d) return;
                        var id = (s.id_granja || '').toString().trim();
                        if (id.length < 3) id = id.padStart(3, '0');
                        else if (id.length > 3) id = id.substring(0, 3);
                        if (!id || !(id in granjasMap)) return;
                        if (!porSubzona[d]) porSubzona[d] = [];
                        porSubzona[d].push({ id_granja: s.id_granja, id_galpon: s.id_galpon, nombre_granja: s.nombre_granja || s.id_granja });
                    });
                    var subzonasUnicas = Object.keys(porSubzona).sort();
                    subzonasUnicas.forEach(function(dato) {
                        var items = porSubzona[dato];
                        if (items.length === 0) return;
                        var granjasConCodigo = [];
                        var seenId = {};
                        items.forEach(function(it) {
                            var id = (it.id_granja || '').toString().trim();
                            if (id.length < 3) id = id.padStart(3, '0');
                            else if (id.length > 3) id = id.substring(0, 3);
                            if (!id) return;
                            if (seenId[id]) return;
                            seenId[id] = true;
                            var nom = (granjasMap[id] != null && granjasMap[id] !== '') ? String(granjasMap[id]).trim() : (it.nombre_granja || id);
                            granjasConCodigo.push(id + ' ' + nom);
                        });
                        var granjaTexto = granjasConCodigo.length ? ' — ' + granjasConCodigo.join(', ') : '';
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
                    // Seleccionar todas: al hacer clic, marcar/desmarcar todas las subzonas de esta zona
                    chkTodas.addEventListener('change', function() {
                        var checked = this.checked;
                        div.querySelectorAll('.chk-subzona').forEach(function(c) { c.checked = checked; });
                    });
                    // Al cambiar subzonas individuales, actualizar estado del "Seleccionar todas"
                    div.querySelectorAll('.chk-subzona').forEach(function(c) {
                        c.addEventListener('change', function() {
                            var chks = div.querySelectorAll('.chk-subzona');
                            var total = chks.length;
                            var checked = div.querySelectorAll('.chk-subzona:checked').length;
                            chkTodas.checked = (total > 0 && checked === total);
                            chkTodas.indeterminate = (checked > 0 && checked < total);
                        });
                    });
                });
            });
        }

        document.getElementById('cronoTipo').addEventListener('change', function() {
            var codTipo = this.value || '';
            cargarProgramas(codTipo);
            document.getElementById('cronoNomProgramaDisplay').value = '';
            if (jQuery('#cronoPrograma').data('select2')) jQuery('#cronoPrograma').val('').trigger('change');
            document.getElementById('panelProgramaInfo').classList.add('hidden');
        });

        document.getElementById('cronoPrograma').addEventListener('change', function() {
            actualizarNombrePrograma();
            cargarPanelProgramaInfo((this.value || '').toString().trim());
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
                // Si hay items cargados (edición), rellenar granja y galpones/campañas al mostrar Especifico
                if (itemsEspecifico && itemsEspecifico.length > 0) {
                    var itemRef = itemsEspecifico[0];
                    var granjaRef = (itemRef.granja != null) ? String(itemRef.granja).trim().substring(0, 3) : '';
                    var porGalpon = {};
                    itemsEspecifico.forEach(function(itx) {
                        var gpx = (itx.galpon != null) ? String(itx.galpon).trim() : '';
                        var cIt = (itx.campania != null) ? String(itx.campania).trim() : '';
                        if (gpx && cIt) {
                            if (!porGalpon[gpx]) porGalpon[gpx] = [];
                            if (porGalpon[gpx].indexOf(cIt) === -1) porGalpon[gpx].push(cIt);
                        }
                        (itx.fechas || []).forEach(function(fx) {
                            var cFx = (fx && fx.campania != null) ? String(fx.campania).trim() : '';
                            if (gpx && cFx && (!porGalpon[gpx] || porGalpon[gpx].indexOf(cFx) === -1)) {
                                if (!porGalpon[gpx]) porGalpon[gpx] = [];
                                porGalpon[gpx].push(cFx);
                            }
                        });
                    });
                    precargarGranjaGalponEspecifico(granjaRef, porGalpon);
                    var panelGranjas = document.getElementById('tabPanelFechasEspecifico');
                    if (panelGranjas) {
                        var codPrograma = (document.getElementById('cronoPrograma') && document.getElementById('cronoPrograma').value) ? document.getElementById('cronoPrograma').value.trim() : '';
                    var filas = [];
                    itemsEspecifico.forEach(function(it) {
                        var nomG = (granjasMap[it.granja] || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;');
                        (it.fechas || []).forEach(function(f) {
                            var fe = (f && typeof f === 'object') ? f : {};
                            var campaniaFila = (fe.campania != null && String(fe.campania).trim() !== '') ? String(fe.campania).trim() : (it.campania || '—');
                            filas.push({ codPrograma: codPrograma, granja: it.granja || '—', nomGranja: nomG || '—', campania: campaniaFila, galpon: it.galpon || '—', edad: (fe.edad != null ? fe.edad : '—'), fechaCarga: formatoDDMMYYYY(fe.fechaCarga), fechaEjec: formatoDDMMYYYY(fe.fechaEjecucion) });
                        });
                    });
                    filas = ordenarFilasFechasCrono(filas, false);
                    var totalTexto = '<strong>Zona:</strong> Especifico &nbsp;·&nbsp; <strong>Total:</strong> ' + filas.length + ' registro(s)';
                    renderTablaGranjasPaginada(filas, panelGranjas, 'especifico', totalTexto);
                }
                var div = document.getElementById('fechasResultado');
                if (div) div.classList.remove('hidden');
                    var btnGuardar = document.getElementById('btnGuardarCrono');
                    if (btnGuardar) btnGuardar.disabled = fechasAsignadas.length === 0;
                }
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
            mostrarCarga(true, 'Calculando fechas...', 'Por favor espere, estamos procesando la asignación', null, 4000, 1000);
            fetch('calcular_fechas.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    var div = document.getElementById('fechasResultadoZonas');
                    var panelGranjas = document.getElementById('tabPanelFechasZonas');
                    if (!res.success) {
                        div.classList.add('hidden');
                        return { error: true, msg: res.message || 'No se pudieron calcular fechas.' };
                    }
                    fechasAsignadas = res.fechas || [];
                    paresCargaEjecucion = res.pares || [];
                    itemsZonas = res.items || [];
                    document.querySelectorAll('#fechasResultadoZonas .tab-btn').forEach(function(b) { b.classList.remove('active'); });
                    document.querySelectorAll('#fechasResultadoZonas .tab-panel-crono').forEach(function(p) { p.classList.remove('active'); });
                    var firstTab = document.querySelector('#fechasResultadoZonas .tab-btn[data-tab="fechas"]');
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
                        filas = ordenarFilasFechasCrono(filas, true);
                        var totalTexto = '<strong>Total:</strong> ' + filas.length + ' registro(s)';
                        renderTablaGranjasPaginada(filas, panelGranjas, 'zonas', totalTexto);
                        renderGranjasUnicosRegistro(filas, 'granjasUnicosZonas');
                    }
                    div.classList.remove('hidden');
                    document.getElementById('btnGuardarCrono').disabled = fechasAsignadas.length === 0;
                    if (window._modoEditarCrono && tieneFechasAnterioresHoy(itemsZonas)) {
                        var alOcultar = function() {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'info', title: 'Aviso', text: 'Se detectaron asignaciones con fechas anteriores a hoy. Solo se guardarán las asignaciones a partir de la fecha actual.' });
                            } else if (window.parent) {
                                try { window.parent.postMessage({ tipo: 'mostrarSwal', icon: 'info', title: 'Aviso', text: 'Se detectaron asignaciones con fechas anteriores a hoy. Solo se guardarán las asignaciones a partir de la fecha actual.' }, '*'); } catch (e) {}
                            }
                        };
                        mostrarCarga(false, null, null, alOcultar);
                        return;
                    }
                    return { error: false };
                })
                .catch(function() { return { error: true, msg: 'Error de conexión.' }; })
                .then(function(r) {
                    if (!r) return;
                    var alOcultar = (r && r.error) ? function() { Swal.fire({ icon: 'error', title: 'Error', text: r.msg }); } : function() {};
                    mostrarCarga(false, null, null, alOcultar);
                });
        });

        document.getElementById('btnAsignar').addEventListener('click', function() {
            var granja = document.getElementById('cronoGranja').value.trim();
            var codPrograma = document.getElementById('cronoPrograma').value.trim();
            var anio = document.getElementById('cronoAnio').value || new Date().getFullYear();
            var chks = document.querySelectorAll('.chk-campania:checked');
            var porGalpon = {};
            chks.forEach(function(c) {
                var gp = (c.getAttribute('data-galpon') || '').trim();
                var camp = (c.value || '').trim();
                if (gp && camp) {
                    if (!porGalpon[gp]) porGalpon[gp] = [];
                    porGalpon[gp].push(camp);
                }
            });
            var galponesConCampanias = Object.keys(porGalpon).filter(function(gp) { return (porGalpon[gp] || []).length > 0; });
            if (!codPrograma) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Seleccione tipo y código del programa.' });
                return;
            }
            if (!granja || galponesConCampanias.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Seleccione granja y al menos una campaña en algún galpón.' });
                return;
            }
            mostrarCarga(true, 'Calculando fechas...', 'Por favor espere, estamos procesando la asignación', null, 4000, 1000);
            var promesas = galponesConCampanias.map(function(galpon) {
                var campanias = porGalpon[galpon] || [];
                var fd = new FormData();
                fd.append('modo', 'especifico');
                fd.append('granja', granja);
                fd.append('galpon', galpon);
                fd.append('codPrograma', codPrograma);
                fd.append('anio', anio);
                campanias.forEach(function(c, i) { fd.append('campanias[' + i + ']', c); });
                return fetch('calcular_fechas.php', { method: 'POST', body: fd }).then(function(r) { return r.json(); });
            });
            Promise.all(promesas).then(function(resultados) {
                var primerError = resultados.find(function(r) { return !r.success; });
                if (primerError) {
                    return { error: true, msg: primerError.message || 'No se pudieron calcular fechas.' };
                }
                var todasFechas = [];
                var todosItems = [];
                resultados.forEach(function(res) {
                    (res.fechas || []).forEach(function(f) { todasFechas.push(f); });
                    (res.items || []).forEach(function(it) { todosItems.push(it); });
                });
                fechasAsignadas = Array.from(new Set(todasFechas)).sort();
                itemsEspecifico = todosItems;
                edadProgramaCrono = (resultados[0] && resultados[0].edadPrograma != null) ? parseInt(resultados[0].edadPrograma, 10) : null;
                var div = document.getElementById('fechasResultado');
                var panelFechas = document.getElementById('tabPanelFechasEspecifico');
                document.querySelectorAll('#fechasResultado .tab-btn').forEach(function(b) { b.classList.remove('active'); });
                document.querySelectorAll('#fechasResultado .tab-panel-crono').forEach(function(p) { p.classList.remove('active'); });
                var firstTab = document.querySelector('#fechasResultado .tab-btn[data-tab="fechas"]');
                if (firstTab) firstTab.classList.add('active');
                if (panelFechas) panelFechas.classList.add('active');
                if (fechasAsignadas.length === 0) {
                    if (panelFechas) panelFechas.innerHTML = '<p class="text-amber-700">No se encontraron fechas para los criterios seleccionados.</p>';
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
                    filas = ordenarFilasFechasCrono(filas, false);
                        var totalTexto = '<strong>Zona:</strong> Especifico &nbsp;·&nbsp; <strong>Total:</strong> ' + filas.length + ' registro(s)';
                        renderTablaGranjasPaginada(filas, panelFechas, 'especifico', totalTexto);
                        renderGranjasUnicosRegistro(filas, 'granjasUnicosEspecifico');
                    }
                    div.classList.remove('hidden');
                    document.getElementById('btnGuardarCrono').disabled = fechasAsignadas.length === 0;
                    if (window._modoEditarCrono && tieneFechasAnterioresHoy(itemsEspecifico)) {
                        var alOcultarEsp = function() {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'info', title: 'Aviso', text: 'Se detectaron asignaciones con fechas anteriores a hoy. Solo se guardarán las asignaciones a partir de la fecha actual.' });
                            } else if (window.parent) {
                                try { window.parent.postMessage({ tipo: 'mostrarSwal', icon: 'info', title: 'Aviso', text: 'Se detectaron asignaciones con fechas anteriores a hoy. Solo se guardarán las asignaciones a partir de la fecha actual.' }, '*'); } catch (e) {}
                            }
                        };
                        mostrarCarga(false, null, null, alOcultarEsp);
                        return;
                    }
                    return { error: false };
                })
                .catch(function() { return { error: true, msg: 'Error de conexión.' }; })
                .then(function(r) {
                    if (!r) return;
                    var alOcultar = r.error ? function() { Swal.fire({ icon: 'error', title: 'Error', text: r.msg || 'Error de conexión.' }); } : function() {};
                    mostrarCarga(false, null, null, alOcultar);
                });
        });

        function enviarCronogramaPayload(payload, esActualizar) {
            var url = esActualizar ? 'actualizar_cronograma.php' : 'guardar_cronograma.php';
            if (esActualizar) payload.numCronograma = parseInt(window._numCronogramaEditar, 10);
            mostrarCarga(true, esActualizar ? 'Actualizando asignación' : 'Guardando asignación', esActualizar ? 'Se está actualizando la asignación en el calendario.' : 'Se está registrando la asignación en el calendario.', null, 5000, 1000);
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(function(r) {
                    return r.text().then(function(body) {
                        return { ok: r.ok, status: r.status, statusText: r.statusText, body: body };
                    });
                })
                .then(function(x) {
                    var resultado = { tipo: 'error', msg: '' };
                    try {
                        var res = JSON.parse(x.body);
                        if (x.ok && res.success) {
                            resultado = { tipo: 'success', msg: res.message };
                            return resultado;
                        }
                        resultado.msg = res.message || ('HTTP ' + x.status + ': ' + (res.message || x.body.substring(0, 200)));
                    } catch (e) {
                        resultado.msg = 'HTTP ' + x.status + (x.statusText ? ' ' + x.statusText : '') + '. Respuesta del servidor: ' + (x.body ? x.body.substring(0, 500).replace(/\s+/g, ' ') : '(vacía)');
                    }
                    if (!x.ok && !resultado.msg) resultado.msg = 'HTTP ' + x.status + '. Respuesta: ' + (x.body ? x.body.substring(0, 300) : '(vacía)');
                    return resultado;
                })
                .catch(function(err) {
                    var msg = 'Error de conexión.';
                    if (err && err.message) msg += ' ' + err.message;
                    return { tipo: 'error', msg: msg };
                })
                .then(function(resultado) {
                    var alOcultar = function() {
                        if (resultado.tipo === 'success') {
                            if (window._modoEditarCrono) {
                                try { window.parent.postMessage({ tipo: 'mostrarSwal', icon: 'success', title: 'Actualizado', text: resultado.msg, cerrarAlConfirmar: true }, '*'); } catch (e) {}
                            } else {
                                Swal.fire({ icon: 'success', title: 'Guardado', text: resultado.msg }).then(function() { limpiarFormulario(); });
                            }
                        } else {
                            if (window._modoEditarCrono) {
                                try { window.parent.postMessage({ tipo: 'mostrarSwal', icon: 'error', title: 'Error', text: resultado.msg }, '*'); } catch (e) {}
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: resultado.msg });
                            }
                        }
                    };
                    mostrarCarga(false, null, null, alOcultar);
                });
        }

        window.submitFormCronograma = function() {
            if (fechasAsignadas.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Calcular fechas', text: 'Debe calcular las fechas antes de guardar. Seleccione programa, granjas (o zonas) y año, luego presione "Calcular fechas".' });
                } else if (window.parent) {
                    try { window.parent.postMessage({ tipo: 'mostrarSwal', icon: 'warning', title: 'Calcular fechas', text: 'Debe calcular las fechas antes de guardar. Seleccione programa, granjas (o zonas) y año, luego presione "Calcular fechas".' }, '*'); } catch (e) { alert('Debe calcular las fechas antes de guardar.'); }
                } else {
                    alert('Debe calcular las fechas antes de guardar.');
                }
                return;
            }
            if (window._modoEditarCrono) {
                var selZona = document.getElementById('cronoZona');
                var zonasSel = Array.from(selZona.selectedOptions).map(function(o) { return o.value; }).filter(Boolean);
                if (zonasSel.length === 0) {
                    try { window.parent.postMessage({ tipo: 'mostrarSwal', icon: 'info', title: 'Sin cambios', text: 'No hay cambios que guardar.', cerrarAlConfirmar: true }, '*'); } catch (e) {}
                    return;
                }
                var tieneEspec = (zonasSel.indexOf('Especifico') !== -1);
                var itemsParaRevisar = tieneEspec ? (itemsEspecifico || []) : (itemsZonas || []);
                if (tieneFechasAnterioresHoy(itemsParaRevisar)) {
                    var msgHtml = 'Esta asignación tiene <strong>fechas anteriores a hoy</strong>.<br><br>Solo se actualizarán las asignaciones a partir de la fecha actual. Las anteriores se conservarán.<br><br>¿Confirmar guardado?';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Aviso al guardar',
                            html: msgHtml,
                            icon: 'warning',
                            iconColor: '#d97706',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, guardar',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#2563eb',
                            cancelButtonColor: '#6b7280'
                        }).then(function(result) {
                            if (result.isConfirmed) ejecutarSubmitFormCronograma();
                        });
                    } else {
                        if (confirm('Esta asignación tiene fechas anteriores a hoy. Solo se actualizarán las asignaciones a partir de la fecha actual. ¿Confirmar guardado?')) ejecutarSubmitFormCronograma();
                    }
                    return;
                }
            }
            ejecutarSubmitFormCronograma();
        };

        function calcularCombinacionesDespliegue(items) {
            var seen = {};
            var out = [];
            (items || []).forEach(function(it) {
                var g = (it.granja || '').toString().trim();
                if (g.length > 3) g = g.substring(0, 3);
                else if (g.length < 3) g = g.padStart(3, '0');
                var gp = (it.galpon || '').toString().trim();
                if (!g || !gp) return;
                var campanias = {};
                var cItem = (it.campania != null ? String(it.campania).trim() : '');
                if (cItem) campanias[cItem.length >= 3 ? cItem.slice(-3) : cItem.padStart(3, '0')] = true;
                (it.fechas || []).forEach(function(f) {
                    var c = (f.campania != null ? String(f.campania).trim() : (it.campania != null ? String(it.campania).trim() : ''));
                    if (c) campanias[c.length >= 3 ? c.slice(-3) : c.padStart(3, '0')] = true;
                });
                if (Object.keys(campanias).length === 0) campanias['000'] = true;
                Object.keys(campanias).forEach(function(c3) {
                    var key = g + '|' + c3 + '|' + gp;
                    if (!seen[key]) {
                        seen[key] = true;
                        out.push({ granja: g, campania: c3, galpon: gp });
                    }
                });
            });
            return out;
        }

        function ejecutarSubmitFormCronograma() {
            var codPrograma = document.getElementById('cronoPrograma').value.trim();
            var nomPrograma = (programasMap[codPrograma] && programasMap[codPrograma].nombre) ? programasMap[codPrograma].nombre : '';
            var selZona = document.getElementById('cronoZona');
            var zonasSel = Array.from(selZona.selectedOptions).map(function(o) { return o.value; }).filter(Boolean);
            var tieneEspecifico = zonasSel.indexOf('Especifico') !== -1;
            var payload = { codPrograma: codPrograma, nomPrograma: nomPrograma };
            if (tieneEspecifico && itemsEspecifico.length > 0) {
                var granjaCod = document.getElementById('cronoGranja').value ? String(document.getElementById('cronoGranja').value).trim().substring(0, 3) : '';
                var nomGranjaSel = granjasMap[granjaCod] || '';
                var itemsParaGuardar = window._modoEditarCrono ? filtrarItemsSoloDesdeHoy(itemsEspecifico) : itemsEspecifico;
                if (itemsParaGuardar.length === 0) {
                    if (window._modoEditarCrono) {
                        try { window.parent.postMessage({ tipo: 'mostrarSwal', icon: 'warning', title: 'Sin asignaciones', text: 'No hay asignaciones a partir de la fecha actual para guardar.' }, '*'); } catch (e) {}
                    } else {
                        Swal.fire({ icon: 'warning', title: 'Sin asignaciones', text: 'No hay asignaciones a partir de la fecha actual para guardar.' });
                    }
                    return;
                }
                var itemsConNomGranjaYEdad = itemsParaGuardar.map(function(it) {
                    var g = (it.granja != null) ? String(it.granja).trim().substring(0, 3) : granjaCod;
                    var zsReal = obtenerZonaSubzonaRealDeGranja(g);
                    return {
                        granja: g,
                        nomGranja: nomGranjaSel || (granjasMap[g] || ''),
                        campania: (it.campania != null) ? String(it.campania).trim() : '',
                        galpon: (it.galpon != null) ? String(it.galpon).trim() : '',
                        zona: zsReal.zona,
                        subzona: zsReal.subzona,
                        edad: edadProgramaCrono != null ? edadProgramaCrono : (it.edad != null ? parseInt(it.edad, 10) : null),
                        fechas: (it.fechas || []).map(function(f) {
                            var fe = (f && typeof f === 'object') ? f : {};
                            var campaniaFe = (fe.campania != null) ? String(fe.campania).trim() : ((it.campania != null) ? String(it.campania).trim() : '');
                            return { edad: fe.edad != null ? fe.edad : (edadProgramaCrono != null ? edadProgramaCrono : null), fechaCarga: fe.fechaCarga, fechaEjecucion: fe.fechaEjecucion, campania: campaniaFe };
                        })
                    };
                });
                var zsPayload = obtenerZonaSubzonaRealDeGranja(granjaCod);
                payload.zona = zsPayload.zona;
                payload.subzona = zsPayload.subzona;
                payload.items = itemsConNomGranjaYEdad;
                payload.combinaciones = calcularCombinacionesDespliegue(itemsConNomGranjaYEdad);
                enviarCronogramaPayload(payload, !!window._modoEditarCrono);
            } else {
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
                var itemsZonasFuente = window._modoEditarCrono ? filtrarItemsSoloDesdeHoy(itemsZonas || []) : (itemsZonas || []);
                if (window._modoEditarCrono && itemsZonasFuente.length === 0) {
                    try { window.parent.postMessage({ tipo: 'mostrarSwal', icon: 'warning', title: 'Sin asignaciones', text: 'No hay asignaciones a partir de la fecha actual para guardar.' }, '*'); } catch (e) {}
                    return;
                }
                var itemsZonasParaGuardar = itemsZonasFuente.map(function(it) {
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
                payload.zona = zona;
                payload.subzona = subzona;
                payload.items = itemsZonasParaGuardar;
                payload.combinaciones = calcularCombinacionesDespliegue(itemsZonasParaGuardar);
                enviarCronogramaPayload(payload, !!window._modoEditarCrono);
            }
        };

        document.getElementById('btnGuardarCrono').addEventListener('click', function() {
            if (fechasAsignadas.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Calcular fechas', text: 'Debe calcular las fechas antes de guardar. Seleccione programa, granjas (o zonas) y año, luego presione "Calcular fechas".' });
                } else {
                    alert('Debe calcular las fechas antes de guardar.');
                }
                return;
            }
            if (window._modoEditarCrono) { window.submitFormCronograma(); return; }
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
                    var zsReal = obtenerZonaSubzonaRealDeGranja(g);
                    return {
                        granja: g,
                        nomGranja: nomGranjaSel || (granjasMap[g] || ''),
                        campania: (it.campania != null) ? String(it.campania).trim() : '',
                        galpon: (it.galpon != null) ? String(it.galpon).trim() : '',
                        zona: zsReal.zona,
                        subzona: zsReal.subzona,
                        edad: edadProgramaCrono != null ? edadProgramaCrono : (it.edad != null ? parseInt(it.edad, 10) : null),
                        fechas: (it.fechas || []).map(function(f) {
                            var fe = (f && typeof f === 'object') ? f : {};
                            var campaniaFe = (fe.campania != null) ? String(fe.campania).trim() : ((it.campania != null) ? String(it.campania).trim() : '');
                            return { edad: fe.edad != null ? fe.edad : (edadProgramaCrono != null ? edadProgramaCrono : null), fechaCarga: fe.fechaCarga, fechaEjecucion: fe.fechaEjecucion, campania: campaniaFe };
                        })
                    };
                });
                var zsPayload = obtenerZonaSubzonaRealDeGranja(granjaCod);
                payload.zona = zsPayload.zona;
                payload.subzona = zsPayload.subzona;
                payload.items = itemsConNomGranjaYEdad;
                payload.combinaciones = calcularCombinacionesDespliegue(itemsConNomGranjaYEdad);
                enviarCronogramaPayload(payload, false);
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
            payload.zona = zona;
            payload.subzona = subzona;
            payload.items = itemsZonasParaGuardar;
            payload.combinaciones = calcularCombinacionesDespliegue(itemsZonasParaGuardar);
            enviarCronogramaPayload(payload, false);
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
            document.getElementById('panelProgramaInfo').classList.add('hidden');
            document.getElementById('panelProgramaInfoCab').innerHTML = '';
            document.getElementById('panelProgramaInfoThead').innerHTML = '';
            document.getElementById('panelProgramaInfoBody').innerHTML = '';
            document.getElementById('panelProgramaInfoSinReg').classList.add('hidden');
            llenarAnios();
            document.getElementById('cronoGranja').value = '';
            cargarGalponesYCampanias('', {});
            document.getElementById('fechasResultado').classList.add('hidden');
            document.getElementById('fechasResultadoZonas').classList.add('hidden');
            document.getElementById('btnGuardarCrono').disabled = true;
            fechasAsignadas = [];
            paresCargaEjecucion = [];
            itemsEspecifico = [];
        }

        document.getElementById('btnLimpiarCrono').addEventListener('click', limpiarFormulario);

        function cargarDatosEdicion() {
            if (!window._modoEditarCrono || !window._numCronogramaEditar) return;
            fetch('get_cronograma.php?numCronograma=' + encodeURIComponent(window._numCronogramaEditar))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success || !res.data) return;
                    var d = res.data;
                    var selTipo = document.getElementById('cronoTipo');
                    var selPrograma = document.getElementById('cronoPrograma');
                    var selAnio = document.getElementById('cronoAnio');
                    selTipo.value = d.codTipo || '';
                    selTipo.disabled = true;
                    var anioAsignacion = new Date().getFullYear();
                    if (d.items && d.items.length > 0) {
                        var primerasFechas = d.items[0].fechas;
                        if (primerasFechas && primerasFechas.length > 0 && primerasFechas[0].fechaEjecucion && String(primerasFechas[0].fechaEjecucion).length >= 4) {
                            anioAsignacion = parseInt(String(primerasFechas[0].fechaEjecucion).substring(0, 4), 10);
                        }
                    }
                    if (selAnio) {
                        var opcionAnio = Array.from(selAnio.options).find(function(o) { return o.value === String(anioAsignacion); });
                        if (!opcionAnio) {
                            var opt = document.createElement('option');
                            opt.value = anioAsignacion;
                            opt.textContent = anioAsignacion;
                            selAnio.appendChild(opt);
                        }
                        selAnio.value = anioAsignacion;
                        selAnio.disabled = true;
                    }
                    fetch('get_programas.php?codTipo=' + encodeURIComponent(d.codTipo || ''))
                        .then(function(r2) { return r2.json(); })
                        .then(function(res2) {
                            if (!res2.success) return;
                            (res2.data || []).forEach(function(p) {
                                var cod = (p.codigo != null) ? String(p.codigo).trim() : '';
                                if (cod) programasMap[cod] = { nombre: (p.nombre != null) ? String(p.nombre) : '', nomTipo: (p.nomTipo != null) ? String(p.nomTipo) : '' };
                            });
                            selPrograma.innerHTML = '<option value="">Escriba código o nombre...</option>';
                            (res2.data || []).forEach(function(p) {
                                var cod = (p.codigo != null) ? String(p.codigo).trim() : '';
                                if (cod === '') return;
                                var opt = document.createElement('option');
                                opt.value = cod;
                                opt.textContent = p.label || (cod + ' - ' + (p.nombre || ''));
                                selPrograma.appendChild(opt);
                            });
                            initSelect2CronoPrograma();
                            if (jQuery(selPrograma).data('select2')) jQuery(selPrograma).val(d.codPrograma || '').trigger('change');
                            else selPrograma.value = d.codPrograma || '';
                            document.getElementById('cronoNomProgramaDisplay').value = d.nomPrograma || '';
                            actualizarNombrePrograma();
                            window._datosEdicionCrono = d;
                            itemsEspecifico = d.items || [];
                            fechasAsignadas = [];
                            edadProgramaCrono = null;
                            itemsEspecifico.forEach(function(it) {
                                if (it.edad != null && it.edad !== '' && edadProgramaCrono == null) edadProgramaCrono = parseInt(it.edad, 10);
                                (it.fechas || []).forEach(function(f) {
                                    fechasAsignadas.push(f);
                                    if ((f.edad != null && f.edad !== '') && edadProgramaCrono == null) edadProgramaCrono = parseInt(f.edad, 10);
                                });
                            });
                            cargarPanelProgramaInfo((d.codPrograma || '').toString().trim());
                            actualizarVisibilidadZonas();
                            document.getElementById('btnGuardarCrono').disabled = fechasAsignadas.length === 0;
                        })
                        .catch(function() {});
                })
                .catch(function() {});
        }

        llenarAnios();
        cargarGranjas();
        cargarTiposPrograma();
        cargarZonas();
        if (window._modoEditarCrono) {
            setTimeout(cargarDatosEdicion, 900);
        }
    </script>
</body>
</html>
