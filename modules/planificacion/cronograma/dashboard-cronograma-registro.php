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
        .tabla-fechas-crono { width: 100%; border-collapse: collapse; font-size: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .tabla-fechas-crono th { background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%); color: #fff; font-weight: 600; padding: 0.4rem 0.5rem; text-align: left; white-space: nowrap; }
        .tabla-fechas-crono td { padding: 0.35rem 0.5rem; border-bottom: 1px solid #e2e8f0; }
        .tabla-fechas-crono tbody tr:nth-child(even) { background: #f8fafc; }
        .tabla-fechas-crono tbody tr:hover { background: #eff6ff; }
        .tabla-fechas-crono tbody tr:last-child td { border-bottom: none; }
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Zona *</label>
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
                            <div class="overflow-x-auto"><table class="tabla-fechas-crono w-full text-sm" id="tablaProgramaEspecifico"><thead id="programaTheadEspecifico"></thead><tbody id="programaBodyEspecifico"></tbody></table></div>
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
                            <div class="overflow-x-auto"><table class="tabla-fechas-crono w-full text-sm" id="tablaProgramaZonas"><thead id="programaTheadZonas"></thead><tbody id="programaBodyZonas"></tbody></table></div>
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
        var columnasDetalleCompletasCrono = ['posDetalle','ubicacion','producto','proveedor','unidad','dosis','unidadDosis','numeroFrascos','edad','descripcion_vacuna','area_galpon','cantidad_por_galpon'];
        var labelsReporteCrono = {
            num: '#', posDetalle: 'N° Det', ubicacion: 'Ubicación', producto: 'Producto', proveedor: 'Proveedor', unidad: 'Unidad',
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
            if (k === 'posDetalle') return (d.posDetalle !== null && d.posDetalle !== undefined && d.posDetalle !== '' ? esc(d.posDetalle) : '');
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
                var colsDetalle = columnasDetalleCompletasCrono.slice();
                var thCells = '<th class="px-3 py-2 text-left">Código</th><th class="px-3 py-2 text-left">Nombre programa</th><th class="px-3 py-2 text-left">Despliegue</th><th class="px-3 py-2 text-left">Descripción</th>';
                colsDetalle.forEach(function(k) { thCells += '<th class="px-3 py-2 text-left">' + (labelsReporteCrono[k] || k) + '</th>'; });
                theadEl.innerHTML = '<tr>' + thCells + '</tr>';
                tbodyEl.innerHTML = '';
                if (detalles.length === 0) {
                    sinRegEl.classList.remove('hidden');
                } else {
                    detalles.forEach(function(d) {
                        var tr = document.createElement('tr');
                        tr.className = 'border-b border-gray-200';
                        var td = '<td class="px-3 py-2">' + esc(cab.codigo) + '</td><td class="px-3 py-2">' + esc(cab.nombre) + '</td><td class="px-3 py-2">' + esc(cab.despliegue || '') + '</td><td class="px-3 py-2">' + esc(cab.descripcion || '') + '</td>';
                        colsDetalle.forEach(function(k) {
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
                                filas.push({ codPrograma: codPrograma, posDetalle: (f.posDetalle != null && f.posDetalle !== '') ? f.posDetalle : (idx + 1), zona: zonaTxt, subzona: subzonaTxt, granja: it.granja || '—', nomGranja: nomG || '—', campania: campaniaFila, galpon: it.galpon || '—', edad: f.edad != null ? f.edad : '—', fechaCarga: formatoDDMMYYYY(f.fechaCarga), fechaEjec: formatoDDMMYYYY(f.fechaEjecucion) });
                            });
                        });
                        var html = '<p class="text-gray-600 text-xs mb-2"><strong>Total:</strong> ' + filas.length + ' registro(s)</p>';
                        html += '<div class="overflow-x-auto rounded-lg border border-gray-200"><table class="tabla-fechas-crono"><thead><tr><th>cod. programa</th><th>N° Det</th><th>Zona</th><th>Subzona</th><th>Granja</th><th>Nom. Granja</th><th>Campaña</th><th>Galpón</th><th>Edad</th><th>Fec. Carga</th><th>Fec. Ejecución</th></tr></thead><tbody>';
                        filas.forEach(function(r) {
                            var codEsc = (r.codPrograma || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                            html += '<tr><td>' + (r.codPrograma || '—') + '</td><td><span class="mr-1">' + (r.posDetalle || '—') + '</span><button type="button" class="btn-ver-crono-detalle" data-codigo="' + codEsc + '" data-pos-detalle="' + (r.posDetalle || 0) + '" title="Ver cabecera y detalle"><i class="fas fa-eye"></i></button></td><td>' + r.zona + '</td><td>' + r.subzona + '</td><td>' + r.granja + '</td><td>' + r.nomGranja + '</td><td>' + r.campania + '</td><td>' + r.galpon + '</td><td>' + r.edad + '</td><td>' + r.fechaCarga + '</td><td>' + r.fechaEjec + '</td></tr>';
                        });
                        html += '</tbody></table></div>';
                        if (panelGranjas) panelGranjas.innerHTML = html;
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
            fd.append('modo', 'especifico_multi');
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
                                filas.push({ codPrograma: codPrograma, posDetalle: (fe.posDetalle != null && fe.posDetalle !== '') ? fe.posDetalle : (idx + 1), granja: it.granja || '—', nomGranja: nomG || '—', campania: campaniaFila, galpon: it.galpon || '—', edad: fe.edad != null ? fe.edad : '—', fechaCarga: formatoDDMMYYYY(fe.fechaCarga), fechaEjec: formatoDDMMYYYY(fe.fechaEjecucion) });
                            });
                        });
                        var html = '<p class="text-gray-600 text-xs mb-2"><strong>Zona:</strong> Especifico &nbsp;·&nbsp; <strong>Total:</strong> ' + filas.length + ' registro(s)</p>';
                        html += '<div class="overflow-x-auto rounded-lg border border-gray-200"><table class="tabla-fechas-crono"><thead><tr><th>cod. programa</th><th>N° Det</th><th>Granja</th><th>Nom. Granja</th><th>Campaña</th><th>Galpón</th><th>Edad</th><th>Fec. Carga</th><th>Fec. Ejecución</th></tr></thead><tbody>';
                        filas.forEach(function(r) {
                            var codEsc = (r.codPrograma || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                            html += '<tr><td>' + (r.codPrograma || '—') + '</td><td><span class="mr-1">' + (r.posDetalle || '—') + '</span><button type="button" class="btn-ver-crono-detalle" data-codigo="' + codEsc + '" data-pos-detalle="' + (r.posDetalle || 0) + '" title="Ver cabecera y detalle"><i class="fas fa-eye"></i></button></td><td>' + r.granja + '</td><td>' + r.nomGranja + '</td><td>' + r.campania + '</td><td>' + r.galpon + '</td><td>' + r.edad + '</td><td>' + r.fechaCarga + '</td><td>' + r.fechaEjec + '</td></tr>';
                        });
                        html += '</tbody></table></div>';
                        if (panelGranjas) panelGranjas.innerHTML = html;
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
                            return { edad: fe.edad != null ? fe.edad : (edadProgramaCrono != null ? edadProgramaCrono : null), posDetalle: (fe.posDetalle != null && fe.posDetalle !== '') ? fe.posDetalle : null, fechaCarga: fe.fechaCarga, fechaEjecucion: fe.fechaEjecucion, campania: campaniaFe };
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
                            return { edad: f.edad != null ? f.edad : null, posDetalle: (f.posDetalle != null && f.posDetalle !== '') ? f.posDetalle : null, fechaCarga: f.fechaCarga, fechaEjecucion: f.fechaEjecucion, campania: campaniaF };
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
