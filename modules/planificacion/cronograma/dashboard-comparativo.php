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
    <title>Comparativo - Necropsias vs Cronograma</title>

    <link href="../../../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">

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
            transition: all 0.2s ease;
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
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .selector-display { display: flex; gap: 0.4rem; align-items: center; }
        .selector-display input[readonly] { background: #fff; cursor: pointer; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); z-index: 1000; display: none; align-items: center; justify-content: center; padding: 1rem; }
        .modal-overlay.visible { display: flex; }
        .modal-box { background: #fff; width: min(760px, 96vw); max-height: 86vh; border-radius: 1rem; box-shadow: 0 20px 35px rgba(0,0,0,0.2); overflow: hidden; display: flex; flex-direction: column; }
        .modal-box.modal-granja-box { width: min(1040px, 98vw); max-height: 90vh; }
        .modal-head { padding: 0.85rem 1rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .modal-body { padding: 0.85rem 1rem; overflow: auto; }
        .modal-body.modal-granja-body { overflow-y: auto; overflow-x: hidden; }
        .modal-granja-top { margin-bottom: 0.6rem; }
        .modal-granja-hint { margin: 0.35rem 0 0.7rem; font-size: 0.78rem; color: #64748b; }
        .modal-foot { padding: 0.75rem 1rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 0.5rem; }
        .checks-grid { display: grid; grid-template-columns: repeat(5, minmax(90px, 1fr)); gap: 0.45rem 0.75rem; }
        .checks-grid label { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; color: #374151; }
        .checks-grid input[type="checkbox"] { transform: translateY(1px); }
        .checks-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(180px, 1fr)); gap: 0.55rem 0.85rem; }
        .checks-grid-3 label { display: flex; align-items: flex-start; gap: 0.45rem; font-size: 0.85rem; color: #374151; padding: 0.35rem 0.45rem; border: 1px solid #e5e7eb; border-radius: 0.55rem; background: #fff; }
        .checks-grid-3 label:hover { background: #f8fafc; }
        .checks-grid-3 .meta { display: block; font-size: 0.74rem; color: #64748b; line-height: 1.25; margin-top: 0.08rem; }
        .campania-group { border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 0.6rem 0.75rem; margin-bottom: 0.7rem; background: #fafafa; }
        .campania-group-title { font-size: 0.82rem; font-weight: 600; color: #1f2937; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem; }
        .campania-group-list { display: grid; grid-template-columns: repeat(4, minmax(90px, 1fr)); gap: 0.35rem 0.6rem; }
        .campania-group-list label { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.82rem; color: #374151; }
        .tree-zone { border: 1px solid #dbeafe; border-radius: 0.75rem; background: #f8fbff; margin-bottom: 0.75rem; padding: 0.6rem 0.75rem; }
        .tree-zone-head { display: flex; align-items: center; gap: 0.45rem; font-size: 0.87rem; font-weight: 700; color: #1e3a8a; margin-bottom: 0.4rem; }
        .tree-granja { border: 1px solid #e5e7eb; border-radius: 0.65rem; background: #fff; padding: 0.45rem 0.55rem; margin: 0.4rem 0; }
        .tree-granja-head { display: flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; color: #1f2937; margin-bottom: 0.25rem; }
        .tree-granja-meta { font-size: 0.72rem; color: #64748b; margin-left: 1.5rem; margin-bottom: 0.2rem; }
        .tree-camps { display: grid; grid-template-columns: repeat(4, minmax(88px, 1fr)); gap: 0.28rem 0.55rem; margin-left: 1.5rem; }
        .tree-camps label { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.8rem; color: #374151; }
        .zona-cards-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 0.75rem; }
        .zona-card { border: 1px solid #dbeafe; border-radius: 0.8rem; background: #f8fbff; padding: 0.7rem; }
        .zona-card-head { display: flex; align-items: center; gap: 0.45rem; font-size: 0.86rem; font-weight: 700; color: #1e3a8a; margin-bottom: 0.45rem; }
        .zona-subzona { border: 1px solid #e5e7eb; border-radius: 0.65rem; background: #fff; padding: 0.5rem 0.55rem; margin-top: 0.45rem; }
        .zona-subzona-head { display: flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; color: #334155; font-weight: 600; margin-bottom: 0.35rem; }
        .zona-subzona-granjas { display: grid; grid-template-columns: 1fr; gap: 0.3rem; margin-left: 1.25rem; }
        .zona-subzona-granjas label { display: inline-flex; align-items: flex-start; gap: 0.35rem; font-size: 0.8rem; color: #374151; }
        .zona-subzona-granjas .meta { display: block; font-size: 0.72rem; color: #64748b; line-height: 1.25; margin-top: 0.08rem; }
        .tree-expand-btn { border: 0; background: transparent; color: #334155; padding: 0 0.25rem; cursor: pointer; line-height: 1; }
        .tree-expand-btn i { transition: transform 0.15s ease; }
        .tree-expand-btn[aria-expanded="true"] i { transform: rotate(90deg); }
        .tree-campanias-wrap { margin-left: 1.45rem; margin-top: 0.4rem; border-left: 2px dashed #dbeafe; padding-left: 0.6rem; }
        .tree-campania-row { margin: 0.2rem 0; }
        .tree-campania-head { display: flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; color: #1f2937; }
        .tree-galpones-wrap { margin: 0.25rem 0 0.35rem 1.35rem; display: grid; grid-template-columns: repeat(6, minmax(70px, 1fr)); gap: 0.25rem 0.4rem; }
        .tree-galpones-wrap label { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.76rem; color: #334155; }
        @media (max-width: 900px) { .tree-galpones-wrap { grid-template-columns: repeat(3, minmax(70px, 1fr)); } }
        @media (max-width: 900px) { .modal-box.modal-granja-box { width: min(98vw, 98vw); max-height: 92vh; } }
        .tree-arbol-container { max-height: 65vh; overflow-y: auto; }
        .tree-subzona { margin-left: 1.25rem; border-left: 2px solid #e2e8f0; padding-left: 0.75rem; margin-top: 0.35rem; }
        .tree-subzona-head { display: flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem; cursor: pointer; }
        .tree-subzona-granjas { margin-left: 1rem; }
        .tree-expand-zona, .tree-expand-subzona { border: 0; background: transparent; color: #64748b; padding: 0 0.25rem; cursor: pointer; line-height: 1; }
        .tree-expand-zona i, .tree-expand-subzona i { transition: transform 0.15s ease; }
        .tree-expand-zona[aria-expanded="true"] i, .tree-expand-subzona[aria-expanded="true"] i { transform: rotate(90deg); }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <div class="card-filtros-compacta mx-5 mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-visible" style="min-height: 320px;">
        <div class="px-6 pb-6 pt-4">
            <div class="filter-row-periodo flex flex-wrap items-start gap-4 mb-4">
                <div class="flex-shrink-0" style="min-width: 200px;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-calendar-alt mr-1 text-blue-600"></i>Periodo
                    </label>
                    <select id="periodoTipo" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer text-sm">
                        <option value="TODOS">Todos</option>
                        <option value="POR_FECHA">Por fecha</option>
                        <option value="ENTRE_FECHAS">Entre fechas</option>
                        <option value="POR_MES">Por mes</option>
                        <option value="ENTRE_MESES" selected>Entre meses</option>
                        <option value="ULTIMA_SEMANA">Última Semana</option>
                    </select>
                </div>
                <div id="periodoPorFecha" class="flex-shrink-0 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-day mr-1 text-blue-600"></i>Fecha</label>
                    <input id="fechaUnica" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" />
                </div>
                <div id="periodoEntreFechas" class="hidden flex-shrink-0 flex items-end gap-2">
                    <div class="min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i>Desde</label>
                        <input id="fechaInicio" type="date" value="<?php echo date('Y-m-01'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div class="min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i>Hasta</label>
                        <input id="fechaFin" type="date" value="<?php echo date('Y-m-t'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>
                <div id="periodoPorMes" class="hidden flex-shrink-0 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar mr-1 text-blue-600"></i>Mes</label>
                    <input id="mesUnico" type="month" value="<?php echo date('Y-m'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div id="periodoEntreMeses" class="hidden flex-shrink-0 flex items-end gap-2">
                    <div class="min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i>Mes Inicio</label>
                        <input id="mesInicio" type="month" value="<?php echo date('Y') . '-01'; ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div class="min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i>Mes Fin</label>
                        <input id="mesFin" type="month" value="<?php echo date('Y') . '-12'; ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-start gap-4 mb-4 border-t border-gray-200 pt-4">
                <div class="flex-shrink-0 min-w-[200px]" style="max-width: 280px;">
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-clipboard-list mr-1 text-blue-600"></i>Tipo de programa</label>
                    <select id="tipoPrograma" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer text-sm">
                        <option value="">Seleccione tipo...</option>
                    </select>
                </div>
                <div class="flex-shrink-0 min-w-[260px]" style="max-width: 360px;">
                    <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-warehouse mr-1 text-blue-600"></i>Granjas</label>
                    <div class="selector-display">
                        <input id="granjaResumen" type="text" class="form-control" readonly value="Todas" />
                    </div>
                </div>
            </div>
            <div class="dashboard-actions mt-6 flex flex-wrap justify-end gap-4">
                <button type="button" id="btnFiltrar" class="btn-primary">
                    <i class="fas fa-file-pdf"></i> Reporte PDF
                </button>
                <button type="button" id="btnLimpiar" class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 text-sm font-medium inline-flex items-center gap-2">
                    Limpiar
                </button>
            </div>
        </div>
    </div>
</div>
<div id="modalGranja" class="modal-overlay">
    <div class="modal-box modal-granja-box">
        <div class="modal-head">
            <h4 class="text-sm font-semibold text-gray-800">Seleccionar granjas</h4>
            <button type="button" id="btnCerrarModalGranja" class="text-gray-500 hover:text-gray-700 text-lg leading-none">&times;</button>
        </div>
        <div class="modal-body modal-granja-body">
            <div class="modal-granja-top">
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <input type="checkbox" id="chkGranjasTodasTarjetas" checked>
                    <span>Marcar / desmarcar todas</span>
                </label>
            </div>
            <div id="cardsZonasGranjas" class="tree-arbol-container"></div>
        </div>
        <div class="modal-foot">
            <button type="button" id="btnCancelarModalGranja" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
            <button type="button" id="btnAplicarModalGranja" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">Aplicar</button>
        </div>
    </div>
</div>

<script src="../../../assets/js/fetch-auth-redirect.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function() {
    function aplicarVisibilidadPeriodoComparativo() {
        var t = (document.getElementById('periodoTipo') && document.getElementById('periodoTipo').value) || '';
        ['periodoPorFecha', 'periodoEntreFechas', 'periodoPorMes', 'periodoEntreMeses'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        });
        if (t === 'POR_FECHA') { var e1 = document.getElementById('periodoPorFecha'); if (e1) e1.classList.remove('hidden'); }
        else if (t === 'ENTRE_FECHAS') { var e2 = document.getElementById('periodoEntreFechas'); if (e2) e2.classList.remove('hidden'); }
        else if (t === 'POR_MES') { var e3 = document.getElementById('periodoPorMes'); if (e3) e3.classList.remove('hidden'); }
        else if (t === 'ENTRE_MESES') { var e4 = document.getElementById('periodoEntreMeses'); if (e4) e4.classList.remove('hidden'); }
    }

    function getParamsPeriodo() {
        return {
            periodoTipo: (document.getElementById('periodoTipo') && document.getElementById('periodoTipo').value) || 'ENTRE_MESES',
            fechaUnica: (document.getElementById('fechaUnica') && document.getElementById('fechaUnica').value) || '',
            fechaInicio: (document.getElementById('fechaInicio') && document.getElementById('fechaInicio').value) || '',
            fechaFin: (document.getElementById('fechaFin') && document.getElementById('fechaFin').value) || '',
            mesUnico: (document.getElementById('mesUnico') && document.getElementById('mesUnico').value) || '',
            mesInicio: (document.getElementById('mesInicio') && document.getElementById('mesInicio').value) || '',
            mesFin: (document.getElementById('mesFin') && document.getElementById('mesFin').value) || ''
        };
    }

    var zonasDisponibles = [];
    var filtroZonasGranjas = [];
    var filtroSubzonasGranjas = [];
    var granjasDisponibles = [];
    var granjasSeleccionadas = []; 
    var arbolFiltrosDisponibles = [];
    var campaniasDisponibles = [];
    var campaniasSeleccionadas = [];
    var galponesDisponibles = [];
    var galponesSeleccionados = [];
    var granjasTemp = [];
    var campaniasTemp = [];
    var galponesTemp = [];
    var treeSelTemp = null;
    var treeExpandTemp = { zonas: new Set(), subzonas: new Set(), granjas: new Set(), campanias: new Set() };
    var arbolGranjasCargadas = new Set();
    var arbolGranjasCargando = {};
    var arbolPeriodoKey = '';

    function asList(res) {
        if (!res) return [];
        if (Array.isArray(res.data)) return res.data;
        if (Array.isArray(res)) return res;
        return [];
    }

    function cargarTiposPrograma() {
        return fetch('../programas/get_tipos_programa.php').then(function(r) { return r.json(); }).then(function(res) {
            var sel = document.getElementById('tipoPrograma');
            if (!sel || !res.success) return;
            sel.innerHTML = '<option value="">Seleccione tipo...</option>';
            (res.data || []).forEach(function(t) {
                var opt = document.createElement('option');
                opt.value = t.codigo;
                opt.textContent = t.nombre || '';
                opt.dataset.nombre = t.nombre || '';
                opt.dataset.sigla = (t.sigla || '').trim().toUpperCase();
                sel.appendChild(opt);
            });
        }).catch(function() {});
    }

    function normalizarGranja(g) {
        var cod = (g && g.codigo != null) ? String(g.codigo).trim() : '';
        var nom = (g && g.nombre != null) ? String(g.nombre).trim() : cod;
        var z = (g && g.zona != null) ? String(g.zona).trim() : '';
        var sz = (g && g.subzona != null) ? String(g.subzona).trim() : '';
        return {
            codigo: cod,
            nombre: nom,
            zonas: z ? [z] : ['Sin zona'],
            subzonas: sz ? [sz] : ['Sin subzona']
        };
    }

    function refrescarResumenes() {
        var g = document.getElementById('granjaResumen');
        if (g) {
            g.value = (granjasSeleccionadas.length === 0) ? 'Todas' : 'Personalizado (' + granjasSeleccionadas.length + ')';
        }
    }
    function construirChecks(contenedorId, values, seleccionadas) {
        var cont = document.getElementById(contenedorId);
        if (!cont) return;
        cont.innerHTML = '';
        var first = document.createElement('label');
        first.innerHTML = '<input type="checkbox" value="" ' + (seleccionadas.length === 0 ? 'checked' : '') + '> <span>Todas</span>';
        cont.appendChild(first);
        values.forEach(function(v) {
            var lb = document.createElement('label');
            var checked = seleccionadas.indexOf(String(v)) >= 0 ? 'checked' : '';
            lb.innerHTML = '<input type="checkbox" value="' + String(v).replace(/"/g, '&quot;') + '" ' + checked + '> <span>' + String(v) + '</span>';
            cont.appendChild(lb);
        });
    }
    function syncChecksExclusivoTodas(contenedorId) {
        var cont = document.getElementById(contenedorId);
        if (!cont) return;
        cont.addEventListener('change', function(ev) {
            var t = ev.target;
            if (!t || t.tagName !== 'INPUT' || t.type !== 'checkbox') return;
            var all = cont.querySelector('input[value=""]');
            var checks = cont.querySelectorAll('input[type="checkbox"]');
            if (t.value === '' && t.checked) {
                checks.forEach(function(c) { if (c.value !== '') c.checked = false; });
            } else if (t.value !== '' && t.checked && all) {
                all.checked = false;
            } else if (t.value !== '' && !t.checked) {
                var anyChecked = false;
                checks.forEach(function(c) { if (c.value !== '' && c.checked) anyChecked = true; });
                if (!anyChecked && all) all.checked = true;
            }
        });
    }
    function leerSeleccionChecks(contenedorId) {
        var cont = document.getElementById(contenedorId);
        if (!cont) return [];
        var vals = [];
        cont.querySelectorAll('input[type="checkbox"]:checked').forEach(function(cb) {
            var v = (cb.value || '').trim();
            if (v) vals.push(v);
        });
        return vals;
    }
    function abrirModal(id) {
        var m = document.getElementById(id);
        if (m) m.classList.add('visible');
    }
    function cerrarModal(id) {
        var m = document.getElementById(id);
        if (m) m.classList.remove('visible');
    }

    function agruparGranjasPorZonaSubzona() {
        var map = {};
        granjasDisponibles.forEach(function(g) {
            var zonas = (g.zonas && g.zonas.length > 0) ? g.zonas.slice() : ['Sin zona'];
            var subzonas = (g.subzonas && g.subzonas.length > 0) ? g.subzonas.slice() : ['Sin subzona'];
            zonas.forEach(function(z) {
                if (!map[z]) map[z] = {};
                subzonas.forEach(function(sz) {
                    if (!map[z][sz]) map[z][sz] = [];
                    if (!map[z][sz].some(function(it) { return it.codigo === g.codigo; })) map[z][sz].push(g);
                });
            });
        });
        return map;
    }

    function keyCamp(granja, campania) { return String(granja || '') + '|' + String(campania || ''); }
    function keyGalpon(granja, campania, galpon) { return String(granja || '') + '|' + String(campania || '') + '|' + String(galpon || ''); }

    function obtenerDisponiblesArbol() {
        var gSet = new Set();
        var cSet = new Set();
        var gpSet = new Set();
        granjasDisponibles.forEach(function(g) { gSet.add(String(g.codigo || '')); });
        campaniasDisponibles.forEach(function(g) {
            var codG = String(g.granja || '');
            (g.campanias || []).forEach(function(c) {
                var codC = String(c || '').trim();
                if (codG && codC) cSet.add(keyCamp(codG, codC));
            });
        });
        galponesDisponibles.forEach(function(g) {
            var codG = String(g.granja || '');
            var codC = String(g.campania || '');
            (g.galpones || []).forEach(function(gp) {
                var codGp = String(gp || '').trim();
                if (codG && codC && codGp) gpSet.add(keyGalpon(codG, codC, codGp));
            });
        });
        return { granjas: gSet, campanias: cSet, galpones: gpSet };
    }

    function expandirDefaultsArbol() {
        if (!treeExpandTemp || !(treeExpandTemp.granjas instanceof Set)) {
            treeExpandTemp = { zonas: new Set(), subzonas: new Set(), granjas: new Set(), campanias: new Set() };
        }
        if (!treeExpandTemp.zonas) treeExpandTemp.zonas = new Set();
        if (!treeExpandTemp.subzonas) treeExpandTemp.subzonas = new Set();
    }

    function construirEstadoTempDesdeSeleccionActual() {
        var disp = obtenerDisponiblesArbol();
        var gSet = new Set();
        var cSet = new Set();
        var gpSet = new Set();
        if (!treeSelTemp) {
            if (granjasSeleccionadas.length === 0) disp.granjas.forEach(function(v) { gSet.add(v); });
            else granjasSeleccionadas.forEach(function(v) { if (disp.granjas.has(String(v))) gSet.add(String(v)); });

            if (campaniasSeleccionadas.length === 0) disp.campanias.forEach(function(v) { cSet.add(v); });
            else campaniasSeleccionadas.forEach(function(v) { var k = keyCamp(v.granja, v.campania); if (disp.campanias.has(k)) cSet.add(k); });

            if (galponesSeleccionados.length === 0) disp.galpones.forEach(function(v) { gpSet.add(v); });
            else galponesSeleccionados.forEach(function(v) { var k = keyGalpon(v.granja, v.campania, v.galpon); if (disp.galpones.has(k)) gpSet.add(k); });
            treeSelTemp = { granjas: gSet, campanias: cSet, galpones: gpSet };
            return;
        }
        treeSelTemp.granjas.forEach(function(v) { if (disp.granjas.has(v)) gSet.add(v); });
        treeSelTemp.campanias.forEach(function(v) { if (disp.campanias.has(v)) cSet.add(v); });
        treeSelTemp.galpones.forEach(function(v) { if (disp.galpones.has(v)) gpSet.add(v); });
        treeSelTemp = { granjas: gSet, campanias: cSet, galpones: gpSet };
    }

    function actualizarChecksMasterArbol() {
        var chkTodos = document.getElementById('chkGranjasTodasTarjetas');
        if (!chkTodos) return;
        var total = granjasDisponibles.length;
        var on = treeSelTemp ? treeSelTemp.granjas.size : 0;
        chkTodos.checked = total === 0 || on === total;
        chkTodos.indeterminate = on > 0 && on < total;
    }

    function renderTarjetasGranjas() {
        var cont = document.getElementById('cardsZonasGranjas');
        if (!cont) return;
        construirEstadoTempDesdeSeleccionActual();
        expandirDefaultsArbol();

        var mapZonas = agruparGranjasPorZonaSubzona();
        var mapTree = {};
        arbolFiltrosDisponibles.forEach(function(g) {
            mapTree[String(g.granja || '')] = Array.isArray(g.campanias) ? g.campanias : [];
        });
        var zonas = Object.keys(mapZonas).sort();
        if (zonas.length === 0) {
            cont.innerHTML = '<div class="text-sm text-gray-500">No hay granjas disponibles.</div>';
            actualizarChecksMasterArbol();
            return;
        }

        var html = '';
        zonas.forEach(function(zona) {
            var expZona = treeExpandTemp.zonas.has(zona);
            var granjasZona = [];
            Object.keys(mapZonas[zona] || {}).forEach(function(sub) {
                (mapZonas[zona][sub] || []).forEach(function(g) { granjasZona.push(String(g.codigo || '').trim()); });
            });
            var selZona = granjasZona.filter(function(c) { return treeSelTemp.granjas.has(c); }).length;
            var checkedZona = granjasZona.length > 0 && selZona === granjasZona.length;
            var indetZona = selZona > 0 && selZona < granjasZona.length;
            html += '<div class="tree-zone">';
            html += '<div class="tree-zone-head">';
            html += '<button type="button" class="tree-expand-zona btnExpZona" data-zona="' + String(zona).replace(/"/g, '&quot;') + '" aria-expanded="' + (expZona ? 'true' : 'false') + '" title="Expandir/contraer"><i class="fas fa-chevron-right"></i></button>';
            html += '<input type="checkbox" class="chkZonaTarjeta" data-zona="' + String(zona).replace(/"/g, '&quot;') + '" title="Marcar/desmarcar zona" ' + (checkedZona ? 'checked' : '') + '> <span>Zona: ' + String(zona).replace(/</g, '&lt;') + '</span></div>';
            var subs = Object.keys(mapZonas[zona] || {}).sort();
            html += '<div class="tree-zone-body"' + (expZona ? '' : ' style="display:none;"') + '>';
            subs.forEach(function(sub) {
                var keySub = zona + '|' + sub;
                var expSub = treeExpandTemp.subzonas.has(keySub);
                var granjasSub = (mapZonas[zona][sub] || []).map(function(g) { return String(g.codigo || '').trim(); });
                var selSub = granjasSub.filter(function(c) { return treeSelTemp.granjas.has(c); }).length;
                var checkedSub = granjasSub.length > 0 && selSub === granjasSub.length;
                html += '<div class="tree-subzona">';
                html += '<div class="tree-subzona-head" data-zona="' + String(zona).replace(/"/g, '&quot;') + '" data-subzona="' + String(sub).replace(/"/g, '&quot;') + '">';
                html += '<button type="button" class="tree-expand-subzona btnExpSubzona" data-key="' + String(keySub).replace(/"/g, '&quot;') + '" aria-expanded="' + (expSub ? 'true' : 'false') + '"><i class="fas fa-chevron-right"></i></button>';
                html += '<input type="checkbox" class="chkSubzonaTarjeta" data-zona="' + String(zona).replace(/"/g, '&quot;') + '" data-subzona="' + String(sub).replace(/"/g, '&quot;') + '" title="Marcar/desmarcar subzona" ' + (checkedSub ? 'checked' : '') + '> <span>Subzona: ' + String(sub).replace(/</g, '&lt;') + '</span></div>';
                html += '<div class="tree-subzona-granjas"' + (expSub ? '' : ' style="display:none;"') + '>';
                (mapZonas[zona][sub] || []).sort(function(a, b) { return String(a.codigo).localeCompare(String(b.codigo)); }).forEach(function(g) {
                    var cod = String(g.codigo || '').trim();
                    var nom = String(g.nombre || cod).trim();
                    var checkedG = treeSelTemp.granjas.has(cod) ? 'checked' : '';
                    var expG = treeExpandTemp.granjas.has(cod);
                    var camps = mapTree[cod] || [];
                    var cargandoDetalle = !!arbolGranjasCargando[cod];
                    html += '<div class="tree-granja">';
                    html += '<div class="tree-granja-head">';
                    html += '<input type="checkbox" class="chkGranjaItemTarjeta" data-zona="' + String(zona).replace(/"/g, '&quot;') + '" data-subzona="' + String(sub).replace(/"/g, '&quot;') + '" data-granja="' + cod.replace(/"/g, '&quot;') + '" value="' + cod.replace(/"/g, '&quot;') + '" title="Marcar/desmarcar granja" ' + checkedG + '>';
                    html += '<span><strong>' + cod + ' - ' + nom.replace(/</g, '&lt;') + '</strong></span>';
                    html += '<button type="button" class="tree-expand-btn btnExpGranja" data-granja="' + cod.replace(/"/g, '&quot;') + '" aria-expanded="' + (expG ? 'true' : 'false') + '" title="Expandir/contraer"><i class="fas fa-chevron-right"></i></button>';
                    html += '</div>';
                    html += '<div class="tree-granja-meta">Zona: ' + String(zona).replace(/</g, '&lt;') + ' | Subzona: ' + String(sub).replace(/</g, '&lt;') + '</div>';
                    html += '<div class="tree-campanias-wrap"' + (expG ? '' : ' style="display:none;"') + '>';
                    html += '<div class="tree-campania-row"><div class="tree-campania-head"><span><strong>Campañas y galpones</strong></span></div></div>';
                    if (expG && cargandoDetalle) {
                        html += '<div class="text-xs text-gray-500 mb-2">Cargando campañas y galpones...</div>';
                    }
                    if (expG && !cargandoDetalle && arbolGranjasCargadas.has(cod) && camps.length === 0) {
                        html += '<div class="text-xs text-gray-500 mb-2">Sin campañas/galpones para el período seleccionado.</div>';
                    }
                    camps.forEach(function(campObj) {
                        var c = String((campObj && campObj.campania) || '').trim();
                        if (!c) return;
                        var keyC = keyCamp(cod, c);
                        var checkedC = treeSelTemp.campanias.has(keyC) ? 'checked' : '';
                        html += '<div class="tree-campania-row">';
                        html += '<div class="tree-campania-head"><label><input type="checkbox" class="chkCampItemTree" data-zona="' + String(zona).replace(/"/g, '&quot;') + '" data-subzona="' + String(sub).replace(/"/g, '&quot;') + '" data-granja="' + cod.replace(/"/g, '&quot;') + '" data-campania="' + c.replace(/"/g, '&quot;') + '" value="' + c.replace(/"/g, '&quot;') + '" ' + checkedC + '> <span><strong>' + c.replace(/</g, '&lt;') + '</strong></span></label></div>';
                        html += '<div class="tree-galpones-wrap" style="display:grid;">';
                        (campObj.galpones || []).forEach(function(gp) {
                            var gpn = String(gp || '').trim();
                            if (!gpn) return;
                            var keyGp = keyGalpon(cod, c, gpn);
                            var checkedGp = treeSelTemp.galpones.has(keyGp) ? 'checked' : '';
                            html += '<label><input type="checkbox" class="chkGalponItemTree" data-zona="' + String(zona).replace(/"/g, '&quot;') + '" data-subzona="' + String(sub).replace(/"/g, '&quot;') + '" data-granja="' + cod.replace(/"/g, '&quot;') + '" data-campania="' + c.replace(/"/g, '&quot;') + '" value="' + gpn.replace(/"/g, '&quot;') + '" ' + checkedGp + '> <span>' + gpn.replace(/</g, '&lt;') + '</span></label>';
                        });
                        html += '</div></div>';
                    });
                    html += '</div></div>';
                });
                html += '</div></div>';
            });
            html += '</div></div>';
        });
        cont.innerHTML = html;

        zonas.forEach(function(zona) {
            var granjasZona = [];
            Object.keys(mapZonas[zona] || {}).forEach(function(sub) {
                (mapZonas[zona][sub] || []).forEach(function(g) { granjasZona.push(String(g.codigo || '').trim()); });
            });
            var selZona = granjasZona.filter(function(c) { return treeSelTemp.granjas.has(c); }).length;
            var indetZona = selZona > 0 && selZona < granjasZona.length;
            cont.querySelectorAll('.chkZonaTarjeta').forEach(function(chk) {
                if ((chk.getAttribute('data-zona') || '') === zona) chk.indeterminate = indetZona;
            });
            Object.keys(mapZonas[zona] || {}).forEach(function(sub) {
                var granjasSub = (mapZonas[zona][sub] || []).map(function(g) { return String(g.codigo || '').trim(); });
                var selSub = granjasSub.filter(function(c) { return treeSelTemp.granjas.has(c); }).length;
                var indetSub = selSub > 0 && selSub < granjasSub.length;
                cont.querySelectorAll('.chkSubzonaTarjeta').forEach(function(chk) {
                    if ((chk.getAttribute('data-zona') || '') === zona && (chk.getAttribute('data-subzona') || '') === sub) chk.indeterminate = indetSub;
                });
            });
        });

        actualizarChecksMasterArbol();

        cont.onclick = function(ev) {
            var t = ev.target;
            if (!t) return;
            var btnZ = t.closest ? t.closest('.btnExpZona') : null;
            if (btnZ) {
                var z = btnZ.getAttribute('data-zona') || '';
                if (!z) return;
                if (treeExpandTemp.zonas.has(z)) {
                    treeExpandTemp.zonas.delete(z);
                } else {
                    treeExpandTemp.zonas.add(z);
                }
                renderTarjetasGranjas();
                return;
            }
            var btnS = t.closest ? t.closest('.btnExpSubzona') : null;
            if (btnS) {
                var k = btnS.getAttribute('data-key') || '';
                if (!k) return;
                if (treeExpandTemp.subzonas.has(k)) {
                    treeExpandTemp.subzonas.delete(k);
                } else {
                    treeExpandTemp.subzonas.add(k);
                }
                renderTarjetasGranjas();
                return;
            }
            var btnG = t.closest ? t.closest('.btnExpGranja') : null;
            if (btnG) {
                var g = btnG.getAttribute('data-granja') || '';
                if (!g) return;
                if (treeExpandTemp.granjas.has(g)) {
                    treeExpandTemp.granjas.delete(g);
                    renderTarjetasGranjas();
                    return;
                }
                treeExpandTemp.granjas.add(g);
                renderTarjetasGranjas();
                cargarDetalleArbolPorGranja(g, getParamsPeriodo()).then(function() {
                    depurarSeleccionesDependientes();
                    construirChecksCampaniasAgrupadas();
                    construirChecksGalponesAgrupados();
                    renderTarjetasGranjas();
                });
                return;
            }
        };

        function syncTreeSelTempFromDom() {
            var gSet = new Set();
            var cSet = new Set();
            var gpSet = new Set();
            Array.prototype.slice.call(cont.querySelectorAll('.chkGranjaItemTarjeta:checked')).forEach(function(cb) {
                var g = String(cb.getAttribute('data-granja') || '').trim();
                if (g) gSet.add(g);
            });
            Array.prototype.slice.call(cont.querySelectorAll('.chkCampItemTree:checked')).forEach(function(cb) {
                var g = String(cb.getAttribute('data-granja') || '').trim();
                var c = String(cb.getAttribute('data-campania') || '').trim();
                if (g && c) cSet.add(keyCamp(g, c));
            });
            Array.prototype.slice.call(cont.querySelectorAll('.chkGalponItemTree:checked')).forEach(function(cb) {
                var g = String(cb.getAttribute('data-granja') || '').trim();
                var c = String(cb.getAttribute('data-campania') || '').trim();
                var gp = String(cb.value || '').trim();
                if (g && c && gp) gpSet.add(keyGalpon(g, c, gp));
            });
            treeSelTemp = { granjas: gSet, campanias: cSet, galpones: gpSet };
        }

        cont.onchange = function(ev) {
            var t = ev && ev.target ? ev.target : null;
            if (!t || t.type !== 'checkbox') return;
            if (t.classList.contains('chkZonaTarjeta')) {
                var z = t.getAttribute('data-zona') || '';
                Array.prototype.slice.call(cont.querySelectorAll('.chkSubzonaTarjeta')).forEach(function(cb) {
                    if ((cb.getAttribute('data-zona') || '') === z) cb.checked = t.checked;
                });
                Array.prototype.slice.call(cont.querySelectorAll('.chkGranjaItemTarjeta,.chkCampItemTree,.chkGalponItemTree')).forEach(function(cb) {
                    if ((cb.getAttribute('data-zona') || '') === z) cb.checked = t.checked;
                });
            } else if (t.classList.contains('chkSubzonaTarjeta')) {
                var z2 = t.getAttribute('data-zona') || '';
                var s2 = t.getAttribute('data-subzona') || '';
                Array.prototype.slice.call(cont.querySelectorAll('.chkGranjaItemTarjeta,.chkCampItemTree,.chkGalponItemTree')).forEach(function(cb) {
                    if ((cb.getAttribute('data-zona') || '') === z2 && (cb.getAttribute('data-subzona') || '') === s2) cb.checked = t.checked;
                });
            } else if (t.classList.contains('chkGranjaItemTarjeta')) {
                var g = t.getAttribute('data-granja') || '';
                Array.prototype.slice.call(cont.querySelectorAll('.chkCampItemTree,.chkGalponItemTree')).forEach(function(cb) {
                    if ((cb.getAttribute('data-granja') || '') === g) cb.checked = t.checked;
                });
            } else if (t.classList.contains('chkCampItemTree')) {
                var g2 = t.getAttribute('data-granja') || '';
                var c2 = t.getAttribute('data-campania') || '';
                Array.prototype.slice.call(cont.querySelectorAll('.chkGalponItemTree')).forEach(function(cb) {
                    if ((cb.getAttribute('data-granja') || '') === g2 && (cb.getAttribute('data-campania') || '') === c2) cb.checked = t.checked;
                });
                if (t.checked) {
                    Array.prototype.slice.call(cont.querySelectorAll('.chkGranjaItemTarjeta')).forEach(function(cb) {
                        if ((cb.getAttribute('data-granja') || '') === g2) cb.checked = true;
                    });
                }
            } else if (t.classList.contains('chkGalponItemTree')) {
                var g3 = t.getAttribute('data-granja') || '';
                var c3 = t.getAttribute('data-campania') || '';
                if (t.checked) {
                    Array.prototype.slice.call(cont.querySelectorAll('.chkGranjaItemTarjeta')).forEach(function(cb) {
                        if ((cb.getAttribute('data-granja') || '') === g3) cb.checked = true;
                    });
                    Array.prototype.slice.call(cont.querySelectorAll('.chkCampItemTree')).forEach(function(cb) {
                        if ((cb.getAttribute('data-granja') || '') === g3 && (cb.getAttribute('data-campania') || '') === c3) cb.checked = true;
                    });
                }
            }
            syncTreeSelTempFromDom();
            syncTreeSelTempFromDom();
            actualizarChecksMasterArbol();
        };

        var chkTodos = document.getElementById('chkGranjasTodasTarjetas');
        if (chkTodos) {
            chkTodos.onchange = function() {
                if (chkTodos.checked) {
                    var disp = obtenerDisponiblesArbol();
                    treeSelTemp.granjas = new Set(disp.granjas);
                    treeSelTemp.campanias = new Set(disp.campanias);
                    treeSelTemp.galpones = new Set(disp.galpones);
                } else {
                    treeSelTemp.granjas = new Set();
                    treeSelTemp.campanias = new Set();
                    treeSelTemp.galpones = new Set();
                }
                renderTarjetasGranjas();
            };
        }

        }

    function aplicarSeleccionGranjasTarjetas() {
        var disp = obtenerDisponiblesArbol();
        var granjasArr = Array.from(treeSelTemp.granjas).filter(function(v) { return disp.granjas.has(v); }).sort();
        var campArr = Array.from(treeSelTemp.campanias).filter(function(v) { return disp.campanias.has(v); }).sort();
        var galpArr = Array.from(treeSelTemp.galpones).filter(function(v) { return disp.galpones.has(v); }).sort();

        granjasSeleccionadas = (granjasArr.length === 0 || granjasArr.length === disp.granjas.size) ? [] : granjasArr;
        campaniasSeleccionadas = (campArr.length === 0 || campArr.length === disp.campanias.size) ? [] : campArr.map(function(k) {
            var p = k.split('|');
            return { granja: p[0] || '', campania: p[1] || '' };
        });
        galponesSeleccionados = (galpArr.length === 0 || galpArr.length === disp.galpones.size) ? [] : galpArr.map(function(k) {
            var p = k.split('|');
            return { granja: p[0] || '', campania: p[1] || '', galpon: p[2] || '' };
        });
    }

    function construirChecksGranjas() { renderTarjetasGranjas(); }
    function renderFiltrosGranjasModal() { renderTarjetasGranjas(); }

    function construirChecksCampaniasAgrupadas() {
        var cont = document.getElementById('checksCampania');
        if (!cont) return;
        cont.innerHTML = '';
        var mapCamp = {};
        campaniasDisponibles.forEach(function(x) { mapCamp[String(x.granja)] = (x.campanias || []).slice(); });

        var granjasObjetivo = granjasSeleccionadas.length > 0
            ? granjasDisponibles.filter(function(g) { return granjasSeleccionadas.indexOf(g.codigo) >= 0; })
            : granjasDisponibles.slice();

        var zonasMap = {};
        granjasObjetivo.forEach(function(g) {
            var zonas = (g.zonas && g.zonas.length > 0) ? g.zonas : ['Sin zona'];
            zonas.forEach(function(z) {
                if (!zonasMap[z]) zonasMap[z] = [];
                zonasMap[z].push(g);
            });
        });

        var allWrap = document.createElement('div');
        allWrap.className = 'campania-group';
        allWrap.innerHTML = '<label class="campania-group-title"><input type="checkbox" id="chkCampTodos" ' + (campaniasTemp.length === 0 ? 'checked' : '') + '> <span>Todas las campañas</span></label>';
        cont.appendChild(allWrap);

        Object.keys(zonasMap).sort().forEach(function(zona) {
            var zBlock = document.createElement('div');
            zBlock.className = 'tree-zone';
            var zHtml = '<div class="tree-zone-head"><input type="checkbox" class="chkCampZona" data-zona="' + zona.replace(/"/g, '&quot;') + '"> <span>Zona: ' + zona + '</span></div>';
            zonasMap[zona].forEach(function(g) {
                var cod = g.codigo;
                var nom = g.nombre || cod;
                var subs = (g.subzonas && g.subzonas.length > 0) ? g.subzonas.join(', ') : '—';
                var camps = mapCamp[cod] || [];
                zHtml += '<div class="tree-granja">';
                zHtml += '<div class="tree-granja-head"><input type="checkbox" class="chkCampGranja" data-zona="' + zona.replace(/"/g, '&quot;') + '" data-granja="' + cod + '"> <span><strong>' + cod + ' - ' + String(nom).replace(/</g, '&lt;') + '</strong></span></div>';
                zHtml += '<div class="tree-granja-meta">Subzona(s): ' + String(subs).replace(/</g, '&lt;') + '</div>';
                zHtml += '<div class="tree-camps">';
                camps.forEach(function(ca) {
                    var checked = campaniasTemp.some(function(s) { return s.granja === cod && s.campania === ca; }) ? 'checked' : '';
                    zHtml += '<label><input type="checkbox" class="chkCampItem" data-zona="' + zona.replace(/"/g, '&quot;') + '" data-granja="' + cod + '" value="' + String(ca).replace(/"/g, '&quot;') + '" ' + checked + '> <span>' + ca + '</span></label>';
                });
                zHtml += '</div></div>';
            });
            zBlock.innerHTML = zHtml;
            cont.appendChild(zBlock);
        });

        function actualizarEstadoArbol() {
            var all = cont.querySelector('#chkCampTodos');
            var itemsAll = Array.prototype.slice.call(cont.querySelectorAll('.chkCampItem'));
            var checkedItems = itemsAll.filter(function(i) { return i.checked; });

            var granjaChecks = cont.querySelectorAll('.chkCampGranja');
            granjaChecks.forEach(function(gb) {
                var g = gb.getAttribute('data-granja') || '';
                var zs = gb.getAttribute('data-zona') || '';
                var items = Array.prototype.slice.call(cont.querySelectorAll('.chkCampItem')).filter(function(i) {
                    return (i.getAttribute('data-zona') || '') === zs && (i.getAttribute('data-granja') || '') === g;
                });
                var on = items.filter(function(i) { return i.checked; }).length;
                gb.indeterminate = on > 0 && on < items.length;
                gb.checked = items.length > 0 && on === items.length;
            });

            var zonaChecks = cont.querySelectorAll('.chkCampZona');
            zonaChecks.forEach(function(zb) {
                var z = zb.getAttribute('data-zona') || '';
                var items = Array.prototype.slice.call(cont.querySelectorAll('.chkCampItem')).filter(function(i) {
                    return (i.getAttribute('data-zona') || '') === z;
                });
                var on = items.filter(function(i) { return i.checked; }).length;
                zb.indeterminate = on > 0 && on < items.length;
                zb.checked = items.length > 0 && on === items.length;
            });

            if (all) {
                all.indeterminate = checkedItems.length > 0 && checkedItems.length < itemsAll.length;
                all.checked = checkedItems.length === 0 || (itemsAll.length > 0 && checkedItems.length === itemsAll.length);
            }
        }

        cont.onchange = function(ev) {
            var t = ev.target;
            if (!t) return;
            if (t.id === 'chkCampTodos') {
                if (t.checked) cont.querySelectorAll('.chkCampZona,.chkCampGranja,.chkCampItem').forEach(function(x) { x.checked = false; x.indeterminate = false; });
                actualizarEstadoArbol();
                return;
            }
            if (t.classList.contains('chkCampZona')) {
                var z = t.getAttribute('data-zona') || '';
                Array.prototype.slice.call(cont.querySelectorAll('.chkCampGranja,.chkCampItem')).forEach(function(x) {
                    if ((x.getAttribute('data-zona') || '') === z) { x.checked = t.checked; x.indeterminate = false; }
                });
            } else if (t.classList.contains('chkCampGranja')) {
                var z2 = t.getAttribute('data-zona') || '';
                var g2 = t.getAttribute('data-granja') || '';
                Array.prototype.slice.call(cont.querySelectorAll('.chkCampItem')).forEach(function(x) {
                    if ((x.getAttribute('data-zona') || '') === z2 && (x.getAttribute('data-granja') || '') === g2) x.checked = t.checked;
                });
            }
            var all2 = cont.querySelector('#chkCampTodos');
            if (all2 && t.checked) { all2.checked = false; all2.indeterminate = false; }
            actualizarEstadoArbol();
        };
        actualizarEstadoArbol();
    }

    function construirChecksGalponesAgrupados() {
        var cont = document.getElementById('checksGalpon');
        if (!cont) return;
        cont.innerHTML = '';

        var permitidasCamp = new Set();
        if (campaniasSeleccionadas.length > 0) {
            campaniasSeleccionadas.forEach(function(c) {
                permitidasCamp.add(String(c.granja || '') + '|' + String(c.campania || ''));
            });
        }

        var grupos = [];
        galponesDisponibles.forEach(function(item) {
            var keyCamp = String(item.granja || '') + '|' + String(item.campania || '');
            if (campaniasSeleccionadas.length > 0 && !permitidasCamp.has(keyCamp)) return;
            grupos.push(item);
        });

        var allWrap = document.createElement('div');
        allWrap.className = 'campania-group';
        allWrap.innerHTML = '<label class="campania-group-title"><input type="checkbox" id="chkGalponTodos" ' + (galponesTemp.length === 0 ? 'checked' : '') + '> <span>Todos los galpones</span></label>';
        cont.appendChild(allWrap);

        if (grupos.length === 0) {
            var vacio = document.createElement('div');
            vacio.className = 'text-sm text-gray-500';
            vacio.textContent = 'No hay galpones disponibles para la selección actual.';
            cont.appendChild(vacio);
            return;
        }

        grupos.forEach(function(g) {
            var bloque = document.createElement('div');
            bloque.className = 'tree-granja';
            var titulo = '<div class="tree-granja-head"><input type="checkbox" class="chkGalponCamp" data-granja="' + String(g.granja || '').replace(/"/g, '&quot;') + '" data-campania="' + String(g.campania || '').replace(/"/g, '&quot;') + '"> <span><strong>Granja ' + String(g.granja || '') + ' - Campaña ' + String(g.campania || '') + '</strong></span></div>';
            var lista = '<div class="tree-camps">';
            (g.galpones || []).forEach(function(gp) {
                var checked = galponesTemp.some(function(s) {
                    return s.granja === g.granja && s.campania === g.campania && s.galpon === gp;
                }) ? 'checked' : '';
                lista += '<label><input type="checkbox" class="chkGalponItem" data-granja="' + String(g.granja || '').replace(/"/g, '&quot;') + '" data-campania="' + String(g.campania || '').replace(/"/g, '&quot;') + '" value="' + String(gp || '').replace(/"/g, '&quot;') + '" ' + checked + '> <span>' + String(gp || '') + '</span></label>';
            });
            lista += '</div>';
            bloque.innerHTML = titulo + lista;
            cont.appendChild(bloque);
        });

        function actualizarEstadoGalpon() {
            var all = cont.querySelector('#chkGalponTodos');
            var itemsAll = Array.prototype.slice.call(cont.querySelectorAll('.chkGalponItem'));
            var checkedItems = itemsAll.filter(function(i) { return i.checked; });

            var campChecks = cont.querySelectorAll('.chkGalponCamp');
            campChecks.forEach(function(cbCamp) {
                var g = cbCamp.getAttribute('data-granja') || '';
                var c = cbCamp.getAttribute('data-campania') || '';
                var items = itemsAll.filter(function(i) {
                    return (i.getAttribute('data-granja') || '') === g && (i.getAttribute('data-campania') || '') === c;
                });
                var on = items.filter(function(i) { return i.checked; }).length;
                cbCamp.indeterminate = on > 0 && on < items.length;
                cbCamp.checked = items.length > 0 && on === items.length;
            });

            if (all) {
                all.indeterminate = checkedItems.length > 0 && checkedItems.length < itemsAll.length;
                all.checked = checkedItems.length === 0 || (itemsAll.length > 0 && checkedItems.length === itemsAll.length);
            }
        }

        cont.onchange = function(ev) {
            var t = ev.target;
            if (!t) return;
            if (t.id === 'chkGalponTodos') {
                if (t.checked) cont.querySelectorAll('.chkGalponCamp,.chkGalponItem').forEach(function(x) { x.checked = false; x.indeterminate = false; });
                actualizarEstadoGalpon();
                return;
            }
            if (t.classList.contains('chkGalponCamp')) {
                var g = t.getAttribute('data-granja') || '';
                var c = t.getAttribute('data-campania') || '';
                Array.prototype.slice.call(cont.querySelectorAll('.chkGalponItem')).forEach(function(x) {
                    if ((x.getAttribute('data-granja') || '') === g && (x.getAttribute('data-campania') || '') === c) x.checked = t.checked;
                });
            }
            var all2 = cont.querySelector('#chkGalponTodos');
            if (all2 && t.checked) { all2.checked = false; all2.indeterminate = false; }
            actualizarEstadoGalpon();
        };
        actualizarEstadoGalpon();
    }

    function cargarZonas() {
        return fetch('get_zonas_caracteristicas.php')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                zonasDisponibles = asList(res);
            })
            .catch(function() { zonasDisponibles = []; });
    }

    function cargarGranjasConMetadatos() {
        granjasDisponibles = [];
        granjasSeleccionadas = [];
        granjasTemp = [];
        arbolFiltrosDisponibles = [];
        campaniasDisponibles = [];
        campaniasSeleccionadas = [];
        galponesDisponibles = [];
        galponesSeleccionados = [];
        arbolGranjasCargadas = new Set();
        arbolGranjasCargando = {};
        arbolPeriodoKey = '';
        refrescarResumenes();
        fetch('get_granjas.php')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                var lista = asList(res);
                granjasDisponibles = lista.map(normalizarGranja).filter(function(g) { return g.codigo !== ''; });
                renderFiltrosGranjasModal();
                construirChecksGranjas();
                cargarArbolFiltrosComparativo();
            })
            .catch(function() {
                var cards = document.getElementById('cardsZonasGranjas');
                if (cards) cards.innerHTML = '';
                construirChecksCampaniasAgrupadas();
                construirChecksGalponesAgrupados();
            });
    }

    function obtenerAnioDesdePeriodo(p) {
        var y = (new Date()).getFullYear();
        var pick = function(v) {
            var s = String(v || '').trim();
            var m = s.match(/^(\d{4})/);
            return m ? parseInt(m[1], 10) : null;
        };
        if (!p || !p.periodoTipo) return y;
        if (p.periodoTipo === 'POR_FECHA') return pick(p.fechaUnica) || y;
        if (p.periodoTipo === 'ENTRE_FECHAS') return pick(p.fechaInicio) || pick(p.fechaFin) || y;
        if (p.periodoTipo === 'POR_MES') return pick(p.mesUnico) || y;
        if (p.periodoTipo === 'ENTRE_MESES') return pick(p.mesInicio) || pick(p.mesFin) || y;
        return y;
    }

    function periodoKeyDesdeParams(p) {
        var x = p || {};
        return [
            String(x.periodoTipo || ''),
            String(x.fechaUnica || ''),
            String(x.fechaInicio || ''),
            String(x.fechaFin || ''),
            String(x.mesUnico || ''),
            String(x.mesInicio || ''),
            String(x.mesFin || '')
        ].join('|');
    }

    function resetearArbolSiCambioPeriodo(p) {
        var key = periodoKeyDesdeParams(p);
        if (key === arbolPeriodoKey) return;
        arbolPeriodoKey = key;
        arbolFiltrosDisponibles = [];
        campaniasDisponibles = [];
        galponesDisponibles = [];
        arbolGranjasCargadas = new Set();
        arbolGranjasCargando = {};
    }

    function recalcularDisponiblesDesdeArbol() {
        campaniasDisponibles = arbolFiltrosDisponibles.map(function(item) {
            return {
                granja: String(item.granja || ''),
                campanias: (item.campanias || []).map(function(c) { return String((c && c.campania) || '').trim(); }).filter(Boolean)
            };
        });
        galponesDisponibles = [];
        arbolFiltrosDisponibles.forEach(function(item) {
            var g = String(item.granja || '').trim();
            (item.campanias || []).forEach(function(c) {
                var codC = String((c && c.campania) || '').trim();
                var gps = (c && Array.isArray(c.galpones)) ? c.galpones.map(function(gp) { return String(gp || '').trim(); }).filter(Boolean) : [];
                if (!g || !codC) return;
                galponesDisponibles.push({ granja: g, campania: codC, galpones: gps });
            });
        });
    }

    function cargarArbolDesdeEndpoints(granjasObjetivo, p) {
        var anio = obtenerAnioDesdePeriodo(p);
        var mapa = {};
        granjasObjetivo.forEach(function(g) { mapa[String(g || '').trim()] = {}; });

        var tareasGranjas = granjasObjetivo.map(function(granja) {
            var g = String(granja || '').trim();
            if (!g) return Promise.resolve();
            return fetch('get_galpones.php?codigo=' + encodeURIComponent(g))
                .then(function(r) { return r.json(); })
                .then(function(resGalpones) {
                    var arrGalpones = asList(resGalpones);
                    var tareasCamp = arrGalpones.map(function(it) {
                        var gp = (it && it.galpon != null) ? String(it.galpon).trim() : '';
                        if (!gp) return Promise.resolve();
                        var url = 'get_campanias.php?granja=' + encodeURIComponent(g) + '&galpon=' + encodeURIComponent(gp) + '&anio=' + encodeURIComponent(anio);
                        return fetch(url)
                            .then(function(rc) { return rc.json(); })
                            .then(function(resCamps) {
                                var arrCamps = asList(resCamps);
                                arrCamps.forEach(function(c) {
                                    var camp = (c && c.campania != null) ? String(c.campania).trim() : '';
                                    if (!camp) return;
                                    if (!mapa[g][camp]) mapa[g][camp] = {};
                                    mapa[g][camp][gp] = true;
                                });
                            })
                            .catch(function() {});
                    });
                    return Promise.all(tareasCamp);
                })
                .catch(function() {});
        });

        return Promise.all(tareasGranjas).then(function() {
            var out = [];
            Object.keys(mapa).sort().forEach(function(g) {
                var campMap = mapa[g] || {};
                var campanias = Object.keys(campMap).sort().map(function(camp) {
                    var gps = Object.keys(campMap[camp] || {});
                    gps.sort(function(a, b) {
                        var na = isNaN(parseInt(a, 10)) ? 999999 : parseInt(a, 10);
                        var nb = isNaN(parseInt(b, 10)) ? 999999 : parseInt(b, 10);
                        if (na !== nb) return na - nb;
                        return String(a).localeCompare(String(b));
                    });
                    return { campania: camp, galpones: gps };
                });
                out.push({ granja: g, campanias: campanias });
            });
            return out;
        });
    }

    function cargarDetalleArbolPorGranja(granja, p) {
        var g = String(granja || '').trim();
        if (!g) return Promise.resolve();
        if (arbolGranjasCargadas.has(g)) return Promise.resolve();
        if (arbolGranjasCargando[g]) return arbolGranjasCargando[g];

        var prom = cargarArbolDesdeEndpoints([g], p)
            .then(function(data) {
                var fila = (Array.isArray(data) && data.length > 0) ? data[0] : { granja: g, campanias: [] };
                arbolFiltrosDisponibles = arbolFiltrosDisponibles.filter(function(x) { return String(x.granja || '') !== g; });
                arbolFiltrosDisponibles.push(fila);
                arbolGranjasCargadas.add(g);
                recalcularDisponiblesDesdeArbol();
            })
            .catch(function() {})
            .finally(function() {
                delete arbolGranjasCargando[g];
            });

        arbolGranjasCargando[g] = prom;
        return prom;
    }

    function cargarArbolFiltrosComparativo() {
        campaniasSeleccionadas = campaniasSeleccionadas.filter(function(sel) {
            return granjasSeleccionadas.length === 0 || granjasSeleccionadas.indexOf(sel.granja) >= 0;
        });
        galponesSeleccionados = galponesSeleccionados.filter(function(sel) {
            return granjasSeleccionadas.length === 0 || granjasSeleccionadas.indexOf(sel.granja) >= 0;
        });
        refrescarResumenes();
        if (granjasDisponibles.length === 0) {
            construirChecksCampaniasAgrupadas();
            construirChecksGalponesAgrupados();
            return Promise.resolve();
        }
        var p = getParamsPeriodo();
        resetearArbolSiCambioPeriodo(p);
        var granjasObjetivo = granjasSeleccionadas.length > 0 ? granjasSeleccionadas.slice() : [];
        var tareaCarga = Promise.resolve();
        if (granjasObjetivo.length > 0) {
            tareaCarga = Promise.all(granjasObjetivo.map(function(g) { return cargarDetalleArbolPorGranja(g, p); }));
        } else {
            recalcularDisponiblesDesdeArbol();
        }

        return tareaCarga
            .then(function() {
                depurarSeleccionesDependientes();
                construirChecksCampaniasAgrupadas();
                construirChecksGalponesAgrupados();
                var mg = document.getElementById('modalGranja');
                if (mg && mg.classList.contains('visible')) renderTarjetasGranjas();
            })
            .catch(function() {
                construirChecksCampaniasAgrupadas();
                construirChecksGalponesAgrupados();
            });
    }

    function depurarSeleccionesDependientes() {
        var setCamp = new Set();
        campaniasDisponibles.forEach(function(g) {
            (g.campanias || []).forEach(function(c) {
                setCamp.add(String(g.granja || '') + '|' + String(c || ''));
            });
        });
        campaniasSeleccionadas = campaniasSeleccionadas.filter(function(x) {
            return setCamp.has(String(x.granja || '') + '|' + String(x.campania || ''));
        });

        var setGalpon = new Set();
        galponesDisponibles.forEach(function(g) {
            (g.galpones || []).forEach(function(gp) {
                setGalpon.add(String(g.granja || '') + '|' + String(g.campania || '') + '|' + String(gp || ''));
            });
        });
        galponesSeleccionados = galponesSeleccionados.filter(function(x) {
            var key = String(x.granja || '') + '|' + String(x.campania || '') + '|' + String(x.galpon || '');
            if (!setGalpon.has(key)) return false;
            return true;
        });
        refrescarResumenes();
    }

    function esTipoNecropsias() {
        var sel = document.getElementById('tipoPrograma');
        if (!sel || !sel.value) return false;
        var opt = sel.options[sel.selectedIndex];
        if (!opt) return false;
        var nombre = (opt.dataset.nombre || opt.textContent || '').toString().toLowerCase();
        var sigla = (opt.dataset.sigla || '').toString().toUpperCase();
        return nombre.indexOf('necropsia') >= 0 || sigla === 'NEC' || sigla === 'NC';
    }

    document.getElementById('btnFiltrar').addEventListener('click', function() {
        var sel = document.getElementById('tipoPrograma');
        var tipoVacio = !sel || !sel.value || (sel.value || '').trim() === '';
        if (tipoVacio) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Tipo de programa', text: 'Debe seleccionar un tipo de programa.' });
            } else {
                alert('Debe seleccionar un tipo de programa.');
            }
            return;
        }
        if (!esTipoNecropsias()) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'info', title: 'Tipo de programa', text: 'Por ahora solo está disponible el reporte PDF para el tipo Necropsias. Seleccione Necropsias en el filtro de tipo de programa.' });
            } else {
                alert('Por ahora solo está disponible el reporte PDF para el tipo Necropsias. Seleccione Necropsias en el filtro de tipo de programa.');
            }
            return;
        }
        var p = getParamsPeriodo();
        var params = new URLSearchParams();
        params.set('periodoTipo', p.periodoTipo);
        if (p.fechaUnica) params.set('fechaUnica', p.fechaUnica);
        if (p.fechaInicio) params.set('fechaInicio', p.fechaInicio);
        if (p.fechaFin) params.set('fechaFin', p.fechaFin);
        if (p.mesUnico) params.set('mesUnico', p.mesUnico);
        if (p.mesInicio) params.set('mesInicio', p.mesInicio);
        if (p.mesFin) params.set('mesFin', p.mesFin);
        var granjasFiltro = granjasSeleccionadas.length > 0
            ? granjasSeleccionadas.slice()
            : granjasDisponibles.map(function(g) { return g.codigo; }).filter(Boolean);
        granjasFiltro.forEach(function(v) { params.append('granja[]', v); });
        var url = 'generar_reporte_necropsias_vs_cronograma.php?' + params.toString();
        window.open(url, '_blank');
    });

    document.getElementById('btnLimpiar').addEventListener('click', function() {
        var d = new Date();
        var pad = function(n) { return String(n).padStart(2, '0'); };
        var ymd = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        var first = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-01';
        var last = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate());
        var month = d.getFullYear() + '-' + pad(d.getMonth() + 1);
        var y = d.getFullYear();

        var p = document.getElementById('periodoTipo'); if (p) p.value = 'ENTRE_MESES';
        var tp = document.getElementById('tipoPrograma'); if (tp) tp.value = '';
        var fu = document.getElementById('fechaUnica'); if (fu) fu.value = ymd;
        var fi = document.getElementById('fechaInicio'); if (fi) fi.value = first;
        var ff = document.getElementById('fechaFin'); if (ff) ff.value = last;
        var mu = document.getElementById('mesUnico'); if (mu) mu.value = month;
        var mi = document.getElementById('mesInicio'); if (mi) mi.value = y + '-01';
        var mf = document.getElementById('mesFin'); if (mf) mf.value = y + '-12';
        filtroZonasGranjas = [];
        filtroSubzonasGranjas = [];
        granjasDisponibles = [];
        granjasSeleccionadas = [];
        arbolFiltrosDisponibles = [];
        campaniasDisponibles = [];
        campaniasSeleccionadas = [];
        galponesDisponibles = [];
        galponesSeleccionados = [];
        arbolGranjasCargadas = new Set();
        arbolGranjasCargando = {};
        arbolPeriodoKey = '';
        var cards = document.getElementById('cardsZonasGranjas');
        if (cards) cards.innerHTML = '';
        construirChecksCampaniasAgrupadas();
        construirChecksGalponesAgrupados();
        cargarGranjasConMetadatos();
        refrescarResumenes();
        aplicarVisibilidadPeriodoComparativo();
    });

    var periodoTipo = document.getElementById('periodoTipo');
    if (periodoTipo) periodoTipo.addEventListener('change', function() {
        aplicarVisibilidadPeriodoComparativo();
        cargarArbolFiltrosComparativo();
    });
    ['fechaUnica', 'fechaInicio', 'fechaFin', 'mesUnico', 'mesInicio', 'mesFin'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', function() {
            cargarArbolFiltrosComparativo();
        });
    });
    aplicarVisibilidadPeriodoComparativo();
    cargarTiposPrograma();
    cargarZonas().then(function() { cargarGranjasConMetadatos(); });
    construirChecksCampaniasAgrupadas();
    construirChecksGalponesAgrupados();
    refrescarResumenes();

    function abrirSelectorGranja() {
        granjasTemp = granjasSeleccionadas.slice();
        campaniasTemp = campaniasSeleccionadas.slice();
        galponesTemp = galponesSeleccionados.slice();
        treeSelTemp = null;
        cargarArbolFiltrosComparativo().then(function() {
        renderFiltrosGranjasModal();
        construirChecksGranjas();
        abrirModal('modalGranja');
        });
    }
    document.getElementById('granjaResumen').addEventListener('click', abrirSelectorGranja);
    document.getElementById('btnCerrarModalGranja').addEventListener('click', function() { cerrarModal('modalGranja'); });
    document.getElementById('btnCancelarModalGranja').addEventListener('click', function() { cerrarModal('modalGranja'); });
    document.getElementById('btnAplicarModalGranja').addEventListener('click', function() {
        aplicarSeleccionGranjasTarjetas();
        cerrarModal('modalGranja');
        depurarSeleccionesDependientes();
        cargarArbolFiltrosComparativo().then(function() { refrescarResumenes(); });
    });

    ['modalGranja'].forEach(function(id) {
        var m = document.getElementById(id);
        if (!m) return;
        m.addEventListener('click', function(ev) { if (ev.target === m) cerrarModal(id); });
    });
})();
</script>
</body>
</html>
