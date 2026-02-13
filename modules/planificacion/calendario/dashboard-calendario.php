<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; } else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}
include_once __DIR__ . '/../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) die("Error de conexión: " . mysqli_connect_error());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario - Planificación</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: system-ui, sans-serif; }
        .cal-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem; background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; }
        .cal-view-select { padding: 0.4rem 0.75rem; border-radius: 0.5rem; border: 1px solid #d1d5db; background: #fff; font-size: 0.875rem; min-width: 120px; }
        .cal-nav-btn { padding: 0.4rem 0.6rem; border-radius: 0.5rem; border: 1px solid #d1d5db; background: #fff; font-size: 0.875rem; cursor: pointer; }
        .cal-nav-btn:hover { background: #f3f4f6; }
        .cal-nav-label { font-size: 0.9rem; font-weight: 600; color: #374151; min-width: 140px; text-align: center; }
        .cal-layout { display: grid; grid-template-columns: 240px 1fr; gap: 1rem; align-items: start; }
        @media (max-width: 900px) { .cal-layout { grid-template-columns: 1fr; } }
        .cal-main { background: #fff; border-radius: 1rem; padding: 1rem; border: 1px solid #e5e7eb; min-width: 0; }
        .cal-sidebar { background: #fff; border-radius: 1rem; padding: 1rem; border: 1px solid #e5e7eb; position: sticky; top: 1rem; }
        .cal-mini { width: 100%; font-size: 0.75rem; }
        .cal-mini .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
        .cal-mini .cal-dia-header { padding: 0.25rem; text-align: center; font-weight: 600; color: #64748b; background: #f1f5f9; border-radius: 4px; }
        .cal-mini .cal-dia { min-height: 24px; padding: 2px; border: 1px solid #e2e8f0; border-radius: 4px; text-align: center; cursor: pointer; background: #fafafa; }
        .cal-mini .cal-dia:hover { background: #e0e7ff; }
        .cal-mini .cal-dia.otro-mes { background: #f1f5f9; color: #94a3b8; }
        .cal-mini .cal-dia.hoy { background: #dbeafe; border-color: #2563eb; }
        .cal-mini .cal-dia.selected { background: #fef3c7; border-color: #d97706; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; font-size: 0.8rem; }
        .cal-dia-header { padding: 0.4rem; text-align: center; font-weight: 600; color: #64748b; background: #f1f5f9; border-radius: 4px; }
        .cal-dia { min-height: 70px; padding: 4px; border: 1px solid #e2e8f0; border-radius: 4px; background: #fafafa; }
        .cal-dia.cal-dia-celda { cursor: pointer; }
        .cal-dia.otro-mes { background: #f1f5f9; color: #94a3b8; }
        .cal-dia.cal-dia-hoy { background: #dbeafe; border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.3); }
        .cal-dia.cal-dia-elegida { background: #fef3c7; border-color: #d97706; box-shadow: 0 0 0 2px rgba(217, 119, 6, 0.3); }
        .cal-dia-num { font-weight: 600; color: #475569; margin-bottom: 4px; }
        .cal-evento { padding: 3px 6px; border-radius: 4px; margin-bottom: 2px; font-size: 0.7rem; cursor: pointer; display: flex; align-items: flex-start; gap: 4px; line-height: 1.25; }
        .cal-evento-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; margin-top: 0.2rem; }
        .cal-evento-texto { word-break: break-word; overflow: hidden; }
        .cal-evento-programa { font-weight: 600; color: #1e40af; margin-bottom: 1px; }
        .cal-evento-granja { font-size: 0.65rem; color: #4b5563; }
        .leyenda { display: flex; flex-wrap: wrap; gap: 0.75rem 1rem; margin-top: 0.75rem; font-size: 0.8rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; align-items: center; }
        .leyenda-titulo { width: 100%; font-weight: 600; color: #374151; margin-bottom: 0.25rem; }
        .leyenda-item { display: flex; align-items: center; gap: 0.35rem; cursor: pointer; user-select: none; }
        .leyenda-item input[type="checkbox"] { cursor: pointer; margin-right: 0.15rem; }
        .leyenda-color { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
        .filter-section { margin-bottom: 1rem; }
        .filter-section h4 { font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
        .filter-section .checkboxes { max-height: 160px; overflow-y: auto; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .modal-overlay.hidden { display: none; }
        .modal-box { background: white; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .modal-body { padding: 1.25rem; }
        .detalle-evento-grid { display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem; font-size: 0.875rem; }
        .detalle-evento-grid dt { color: #6b7280; }
        .detalle-evento-grid dd { margin: 0; color: #111827; }
        #modalDetalleProgramaCal .modal-box table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        #modalDetalleProgramaCal .modal-box th { background: #f1f5f9; font-weight: 600; color: #475569; padding: 0.5rem 0.75rem; text-align: left; }
        #modalDetalleProgramaCal .modal-box td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb; }
        #modalDetalleProgramaCal .modal-box tbody tr:nth-child(even) { background: #f9fafb; }
        .cal-vista-dia-card { padding: 1rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; background: #fff; margin-bottom: 0.5rem; }
        .cal-vista-semana { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; }
        @media (max-width: 900px) { .cal-vista-semana { grid-template-columns: 1fr; } }
        .cal-vista-semana-dia { padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #fafafa; min-height: 120px; }
        .cal-vista-semana-dia h4 { font-size: 0.8rem; color: #64748b; margin-bottom: 0.5rem; }
        .cal-vista-anio { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        @media (max-width: 900px) { .cal-vista-anio { grid-template-columns: repeat(2, 1fr); } }
        .cal-vista-anio-mes { font-size: 0.75rem; }
        .cal-vista-anio-mes .cal-grid { grid-template-columns: repeat(7, 1fr); gap: 1px; }
        .cal-vista-anio-mes .cal-dia { min-height: 28px; padding: 2px; font-size: 0.7rem; cursor: pointer; }
        .cal-vista-anio-mes .cal-dia.has-eventos { background: #dbeafe; }
        .cal-vista-anio-mes .cal-dia.hoy { background: #fef3c7; }
        #calCargaOverlay { position: absolute; inset: 0; background: rgba(255,255,255,0.85); display: none; align-items: center; justify-content: center; z-index: 10; border-radius: 1rem; }
        #calCargaOverlay.visible { display: flex; }
        #calCargaOverlay .cal-spinner { width: 40px; height: 40px; border: 3px solid #e5e7eb; border-top-color: #2563eb; border-radius: 50%; animation: cal-spin 0.8s linear infinite; }
        #calCargaOverlay .cal-carga-texto { margin-top: 0.75rem; font-size: 0.875rem; color: #4b5563; }
        @keyframes cal-spin { to { transform: rotate(360deg); } }
        .cal-pdf-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.25rem 0.5rem; border-radius: 0.375rem; color: #dc2626; border: 1px solid #fca5a5; font-size: 0.875rem; text-decoration: none; white-space: nowrap; }
        .cal-pdf-btn:hover { background: #fef2f2; color: #b91c1c; }
        .cal-pdf-btn i { margin-right: 0.25rem; }
        .cal-pdf-celda { padding: 0.2rem 0.35rem; font-size: 0.75rem; margin-top: 0.25rem; }
        .cal-pdf-celda i { margin-right: 0; }
        .cal-dia-celda { position: relative; }
    </style>
</head>
<body class="p-4">
    <!-- Barra: select vista + navegación según periodo -->
    <div class="cal-toolbar">
        <select id="calSelectVista" class="cal-view-select" title="Vista">
            <option value="dia">Día</option>
            <option value="semana">Semana</option>
            <option value="mes" selected>Mes</option>
            <option value="anio">Año</option>
        </select>
        <div class="flex items-center gap-2">
            <button type="button" id="calNavPrev" class="cal-nav-btn" title="Anterior">&laquo;</button>
            <span id="calNavLabel" class="cal-nav-label"></span>
            <button type="button" id="calNavNext" class="cal-nav-btn" title="Siguiente">&raquo;</button>
        </div>
        <button type="button" id="calHoy" class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm hover:bg-gray-50">Hoy</button>
    </div>

    <div class="cal-layout">
        <!-- Panel único a la izquierda: mini calendario + filtros -->
        <div class="cal-sidebar">
            <div class="cal-mini mb-4" id="calMiniWrap">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <button type="button" id="calMiniPrev" class="p-1 rounded border border-gray-300 hover:bg-gray-100 text-sm" title="Mes anterior">&laquo;</button>
                    <span class="text-sm font-semibold text-gray-700" id="calMiniMesAnio"></span>
                    <button type="button" id="calMiniNext" class="p-1 rounded border border-gray-300 hover:bg-gray-100 text-sm" title="Mes siguiente">&raquo;</button>
                </div>
                <div id="calMiniGrid" class="cal-mini cal-grid"></div>
            </div>
            <div class="filter-section">
                <h4><i class="fas fa-filter mr-1"></i> Tipo de programa</h4>
                <div id="filtroTiposPrograma" class="checkboxes space-y-1">
                    <span class="text-gray-500 text-sm">Cargando...</span>
                </div>
            </div>
            <div class="filter-section">
                <h4><i class="fas fa-list mr-1"></i> Cronogramas <span id="calAnioLeyenda"></span></h4>
                <div id="filtroCronogramasAnio" class="checkboxes space-y-1">
                    <span class="text-gray-500 text-sm">Seleccione un mes para cargar</span>
                </div>
            </div>
        </div>

        <!-- Área principal: contenido según vista (día / semana / mes / año) -->
        <div class="cal-main" style="position: relative;">
            <div id="calCargaOverlay" aria-hidden="true">
                <div class="flex flex-col items-center">
                    <div class="cal-spinner"></div>
                    <span class="cal-carga-texto">Cargando eventos...</span>
                </div>
            </div>
            <div id="calGrid"></div>
        </div>
    </div>

    <!-- Modal detalle evento -->
    <div id="modalDetalleEvento" class="modal-overlay hidden">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-800">Detalle del evento</h3>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none" data-modal="modalDetalleEvento">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detalle-evento-seccion mb-3">
                    <div class="text-sm font-semibold text-blue-800 mb-1">Programa</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="detEvPrograma" class="text-gray-800"></span>
                        <a href="#" id="detEvVerMasPrograma" class="text-sm text-blue-600 hover:text-blue-800 hover:underline hidden">Ver más</a>
                    </div>
                </div>
                <div class="detalle-evento-seccion">
                    <div class="detalle-evento-grid">
                        <dt>Granja</dt>
                        <dd id="detEvGranja"></dd>
                        <dt>Campaña</dt>
                        <dd id="detEvCampania"></dd>
                        <dt>Galpón</dt>
                        <dd id="detEvGalpon"></dd>
                        <dt>Edad</dt>
                        <dd id="detEvEdad"></dd>
                        <dt>Fec. Carga</dt>
                        <dd id="detEvFecCarga"></dd>
                        <dt>Fec. Ejecución</dt>
                        <dd id="detEvFecEjec"></dd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal detalle del programa (desde detalle evento) -->
    <div id="modalDetalleProgramaCal" class="modal-overlay hidden">
        <div class="modal-box" style="max-width: 720px;">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-800" id="modalDetalleProgramaCalTitulo">Detalle del programa</h3>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none" data-modal="modalDetalleProgramaCal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="modalDetalleProgramaCalCab" class="mb-3 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm"></div>
                <div class="overflow-x-auto">
                    <table class="tabla-fechas-crono w-full text-sm" id="tablaDetalleProgramaCal">
                        <thead class="bg-gray-50 border-b border-gray-200" id="modalDetalleProgramaCalThead"></thead>
                        <tbody id="modalDetalleProgramaCalBody"></tbody>
                    </table>
                </div>
                <p id="modalDetalleProgramaCalSinReg" class="hidden text-gray-500 text-sm mt-2">Sin registros en el detalle.</p>
            </div>
        </div>
    </div>

    <!-- Modal eventos de un día (vista año u otro) -->
    <div id="modalEventosDia" class="modal-overlay hidden">
        <div class="modal-box" style="max-width: 560px;">
            <div class="modal-header flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <h3 class="text-lg font-semibold text-gray-800 truncate" id="modalEventosDiaTitulo">Eventos del día</h3>
                    <span id="modalEventosDiaPdf"></span>
                </div>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none flex-shrink-0" data-modal="modalEventosDia">&times;</button>
            </div>
            <div class="modal-body" id="modalEventosDiaBody"></div>
        </div>
    </div>

    <script>
(function() {
    var calendarData = [];
    var mesActualCal = 0, anioActualCal = 0;
    var miniCalMes = 0, miniCalAnio = 0;
    var calFechaElegida = null;
    var codProgramaFiltroCal = null;
    var coloresGranja = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1','#14b8a6','#a855f7','#e11d48','#0ea5e9','#22c55e','#f43f5e','#d946ef','#facc15','#2dd4bf','#64748b'];
    var calCronogramasVisibles = null;
    var calGranjasVisibles = null;
    var calEventosGlobal = [];
    var vistaActual = 'mes';
    var fechaNavegacion = (function() {
        var d = new Date();
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    })();
    (function() {
        var m = (window.location.search || '').match(/[?&]fecha=(\d{4}-\d{2}-\d{2})/);
        if (m && m[1]) {
            fechaNavegacion = m[1];
            calFechaElegida = m[1];
        }
    })();

    function parseFecha(ymd) {
        var p = (ymd || '').toString().trim().split('-');
        if (p.length < 3) return new Date();
        return new Date(parseInt(p[0],10), parseInt(p[1],10) - 1, parseInt(p[2],10));
    }
    function addDays(ymd, delta) {
        var d = parseFecha(ymd);
        d.setDate(d.getDate() + delta);
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    }
    function addMonths(ymd, delta) {
        var d = parseFecha(ymd);
        d.setMonth(d.getMonth() + delta);
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    }
    function addYears(ymd, delta) {
        var d = parseFecha(ymd);
        d.setFullYear(d.getFullYear() + delta);
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    }
    function getLunesSemana(ymd) {
        var d = parseFecha(ymd);
        var day = d.getDay();
        var diff = (day === 0 ? -6 : 1) - day;
        d.setDate(d.getDate() + diff);
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    }
    function getDomingoSemana(ymd) {
        return addDays(getLunesSemana(ymd), 6);
    }
    function formatNavLabel() {
        var v = vistaActual;
        var f = parseFecha(fechaNavegacion);
        var mesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        if (v === 'dia') return f.getDate() + ' ' + mesNombres[f.getMonth()] + ' ' + f.getFullYear();
        if (v === 'semana') {
            var lunes = getLunesSemana(fechaNavegacion);
            var domingo = getDomingoSemana(fechaNavegacion);
            var dL = parseFecha(lunes), dD = parseFecha(domingo);
            return dL.getDate() + '-' + dD.getDate() + ' ' + mesNombres[dL.getMonth()] + ' ' + dL.getFullYear();
        }
        if (v === 'mes') return mesNombres[f.getMonth()] + ' ' + f.getFullYear();
        if (v === 'anio') return '' + f.getFullYear();
        return fechaNavegacion;
    }

    function esc(s) {
        return (s || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }
    function fechaDDMMYYYY(s) {
        if (!s) return '';
        s = (s || '').toString().trim();
        var m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (m) return m[3] + '/' + m[2] + '/' + m[1];
        return s;
    }
    function pad2(n) { return (n < 10 ? '0' : '') + n; }

    function urlPdfCronogramaDia(fechaKey) {
        if (!fechaKey) return '#';
        var base = '../cronograma/generar_reporte_cronograma_filtrado_pdf.php';
        var q = 'periodoTipo=ENTRE_FECHAS&fechaInicio=' + encodeURIComponent(fechaKey) + '&fechaFin=' + encodeURIComponent(fechaKey) + '&porFechaEjecucion=1';
        return base + '?' + q;
    }

    var calEventoActualCodPrograma = '';
    var columnasPorSiglaCal = { 'NC': ['num', 'ubicacion', 'edad'], 'PL': ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'edad'], 'GR': ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'edad'], 'MC': ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'area_galpon', 'cantidad_por_galpon', 'unidadDosis', 'edad'], 'LD': ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'unidadDosis', 'edad'], 'CP': ['num', 'ubicacion', 'producto', 'proveedor', 'dosis', 'unidadDosis', 'edad'] };
    var labelsDetalleProgramaCal = { num: '#', ubicacion: 'Ubicación', producto: 'Producto', proveedor: 'Proveedor', unidad: 'Unidad', dosis: 'Dosis', descripcion_vacuna: 'Descripcion', numeroFrascos: 'Nº frascos', edad: 'Edad', unidadDosis: 'Unid. dosis', area_galpon: 'Área galpón', cantidad_por_galpon: 'Cant. por galpón' };
    function formatearDescripcionVacunaCal(s) {
        if (s == null || s === undefined) s = '';
        s = String(s).trim();
        if (!s) return '';
        if (/^Contra[\r\n]/.test(s) || (s.indexOf('\n') !== -1 && s.indexOf('- ') !== -1)) return s;
        var partes = s.split(',').map(function(x) { return x.trim(); }).filter(Boolean);
        return partes.length === 0 ? '' : 'Contra\n' + partes.map(function(p) { return '- ' + p; }).join('\n');
    }
    function valorCeldaDetalleProgramaCal(k, d) {
        if (k === 'num') return '';
        if (k === 'ubicacion') return esc(d.ubicacion || '');
        if (k === 'producto') return esc(d.nomProducto || d.codProducto || '');
        if (k === 'proveedor') return esc(d.nomProveedor || '');
        if (k === 'unidad') return esc(d.unidades || '');
        if (k === 'dosis') return esc(d.dosis || '');
        if (k === 'descripcion_vacuna') return esc(formatearDescripcionVacunaCal(d.descripcionVacuna));
        if (k === 'numeroFrascos') return esc(d.numeroFrascos || '');
        if (k === 'edad') return (d.edad !== null && d.edad !== undefined && d.edad !== '' ? d.edad : '');
        if (k === 'unidadDosis') return esc(d.unidadDosis || '');
        if (k === 'area_galpon') return (d.areaGalpon !== null && d.areaGalpon !== undefined && d.areaGalpon !== '' ? d.areaGalpon : '');
        if (k === 'cantidad_por_galpon') return (d.cantidadPorGalpon !== null && d.cantidadPorGalpon !== undefined && d.cantidadPorGalpon !== '' ? d.cantidadPorGalpon : '');
        return '';
    }
    function abrirModalDetalleProgramaCal(codigo) {
        if (!codigo || String(codigo).trim() === '') return;
        var cabEl = document.getElementById('modalDetalleProgramaCalCab');
        var theadEl = document.getElementById('modalDetalleProgramaCalThead');
        var tbodyEl = document.getElementById('modalDetalleProgramaCalBody');
        var sinRegEl = document.getElementById('modalDetalleProgramaCalSinReg');
        document.getElementById('modalDetalleProgramaCalTitulo').textContent = 'Detalle del programa ' + esc(codigo);
        cabEl.innerHTML = '<span class="text-gray-500">Cargando...</span>';
        theadEl.innerHTML = '';
        tbodyEl.innerHTML = '';
        sinRegEl.classList.add('hidden');
        document.getElementById('modalDetalleProgramaCal').classList.remove('hidden');
        fetch('../programas/get_programa_cab_detalle.php?codigo=' + encodeURIComponent(codigo)).then(function(r) { return r.json(); }).then(function(res) {
            if (!res.success) {
                cabEl.innerHTML = '<span class="text-red-600">' + esc(res.message || 'Error al cargar.') + '</span>';
                return;
            }
            var cab = res.cab || {};
            var detalles = res.detalles || [];
            cabEl.innerHTML = '<div class="font-semibold text-gray-800 mb-1">' + esc(cab.codigo) + ' — ' + esc(cab.nombre) + '</div><dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-600"><dt class="font-medium">Tipo</dt><dd>' + esc(cab.nomTipo || '') + '</dd>' + (cab.despliegue ? '<dt class="font-medium">Despliegue</dt><dd>' + esc(cab.despliegue) + '</dd>' : '') + (cab.descripcion ? '<dt class="font-medium col-span-2">Descripción</dt><dd class="col-span-2">' + esc(cab.descripcion) + '</dd>' : '') + '</dl>';
            var sigla = (res.sigla || 'PL').toUpperCase();
            if (sigla === 'NEC') sigla = 'NC';
            var cols = columnasPorSiglaCal[sigla] || columnasPorSiglaCal['PL'];
            var colsSinNum = cols.filter(function(k) { return k !== 'num'; });
            if (colsSinNum.indexOf('edad') !== -1) { colsSinNum = colsSinNum.filter(function(k) { return k !== 'edad'; }); colsSinNum.push('edad'); }
            var thCells = '<th>Código</th><th>Nombre programa</th><th>Despliegue</th><th>Descripción</th>';
            colsSinNum.forEach(function(k) { thCells += '<th>' + (labelsDetalleProgramaCal[k] || k) + '</th>'; });
            theadEl.innerHTML = '<tr>' + thCells + '</tr>';
            if (detalles.length === 0) {
                sinRegEl.classList.remove('hidden');
            } else {
                detalles.forEach(function(d) {
                    var td = '<td>' + esc(cab.codigo) + '</td><td>' + esc(cab.nombre) + '</td><td>' + esc(cab.despliegue || '') + '</td><td>' + esc(cab.descripcion || '') + '</td>';
                    colsSinNum.forEach(function(k) { td += '<td' + (k === 'descripcion_vacuna' ? ' style="white-space:pre-wrap;"' : '') + '>' + valorCeldaDetalleProgramaCal(k, d) + '</td>'; });
                    tbodyEl.appendChild(document.createElement('tr')).innerHTML = td;
                });
            }
        }).catch(function() {
            cabEl.innerHTML = '<span class="text-red-600">Error de conexión.</span>';
        });
    }

    function getCodTiposSeleccionados() {
        var cods = [];
        document.querySelectorAll('#filtroTiposPrograma input[type="checkbox"]:checked').forEach(function(cb) {
            var c = (cb.getAttribute('data-codigo') || '').toString().trim();
            if (c) cods.push(c);
        });
        return cods;
    }
    function eventosPorDiaDesdeListado() {
        var map = {};
        var data = calendarData;
        var codTipos = getCodTiposSeleccionados();
        data.forEach(function(r) {
            if (codTipos.length > 0) {
                var rt = r.codTipo != null ? String(r.codTipo) : '';
                if (codTipos.indexOf(rt) === -1) return;
            } else {
                return;
            }
            if (calCronogramasVisibles) {
                if (calCronogramasVisibles.size === 0) return;
                if (!calCronogramasVisibles.has(Number(r.numCronograma))) return;
            }
            var fec = (r.fechaEjecucion || '').toString().trim();
            var key = fec.substring(0, 10);
            if (!key || key.length < 10) return;
            if (!map[key]) map[key] = [];
            map[key].push({
                numCronograma: Number(r.numCronograma) || 0,
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

    function cronogramasUnicosParaLeyenda(datos) {
        var data = datos && datos.length ? datos : calendarData;
        var seen = {};
        var out = [];
        data.forEach(function(r) {
            var numC = Number(r.numCronograma) || 0;
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

    function colorCronograma(numC) {
        return coloresGranja[Math.abs(Number(numC) || 0) % coloresGranja.length];
    }

    function renderMiniCalendario() {
        var mesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        document.getElementById('calMiniMesAnio').textContent = mesNombres[miniCalMes] + ' ' + miniCalAnio;
        var hoy = new Date();
        var hoyStr = hoy.getFullYear() + '-' + pad2(hoy.getMonth() + 1) + '-' + pad2(hoy.getDate());
        var primerdia = new Date(miniCalAnio, miniCalMes, 1);
        var inicioSem = primerdia.getDay();
        var ultimoDiaMes = new Date(miniCalAnio, miniCalMes + 1, 0).getDate();
        var ultimoDate = new Date(miniCalAnio, miniCalMes, ultimoDiaMes);
        var primerFechaCal = new Date(miniCalAnio, miniCalMes, 1 - inicioSem);
        var ultimaFechaCal = new Date(miniCalAnio, miniCalMes, ultimoDiaMes + (6 - ultimoDate.getDay()));
        var html = '<div class="cal-dia-header">Do</div><div class="cal-dia-header">Lu</div><div class="cal-dia-header">Ma</div><div class="cal-dia-header">Mi</div><div class="cal-dia-header">Ju</div><div class="cal-dia-header">Vi</div><div class="cal-dia-header">Sá</div>';
        var fecha = new Date(primerFechaCal.getTime());
        while (fecha.getTime() <= ultimaFechaCal.getTime()) {
            var esEsteMes = fecha.getMonth() === miniCalMes;
            var dia = fecha.getDate();
            var key = fecha.getFullYear() + '-' + pad2(fecha.getMonth() + 1) + '-' + pad2(dia);
            var cls = 'cal-dia';
            if (!esEsteMes) cls += ' otro-mes';
            if (key === hoyStr) cls += ' hoy';
            if (calFechaElegida && key === calFechaElegida) cls += ' selected';
            html += '<div class="' + cls + '" data-key="' + key + '" role="button" tabindex="0">' + dia + '</div>';
            fecha.setDate(fecha.getDate() + 1);
        }
        document.getElementById('calMiniGrid').innerHTML = html;
        document.querySelectorAll('#calMiniGrid .cal-dia').forEach(function(el) {
            el.addEventListener('click', function() {
                var key = this.getAttribute('data-key');
                if (!key) return;
                calFechaElegida = key;
                fechaNavegacion = key;
                cargarDatosVista();
            });
        });
    }

    function pintarEventosEnContenedor(eventos, colorByNumCronograma, contenedorId) {
        calEventosGlobal = [];
        var html = '';
        (eventos || []).forEach(function(ev) {
            var idx = calEventosGlobal.length;
            calEventosGlobal.push(ev);
            var color = colorByNumCronograma[ev.numCronograma] || colorCronograma(ev.numCronograma);
            var programa = [(ev.codPrograma||'').trim(), (ev.nomPrograma||'').trim()].filter(Boolean).join(' — ') || 'Programa';
            var granja = (ev.nomGranja||ev.granja||'').trim() || '—';
            var titulo = 'Programa: ' + programa + '\nGranja: ' + granja;
            html += '<div class="cal-evento cal-evento-click" data-evidx="' + idx + '" style="border-left: 3px solid ' + color + '" title="' + esc(titulo) + '">';
            html += '<span class="cal-evento-dot" style="background:' + color + '"></span>';
            html += '<span class="cal-evento-texto"><span class="cal-evento-programa">' + esc(programa) + '</span><br><span class="cal-evento-granja">' + esc(granja) + '</span></span></div>';
        });
        var cont = document.getElementById(contenedorId);
        if (cont) cont.innerHTML = html;
        document.querySelectorAll('#' + contenedorId + ' .cal-evento-click').forEach(function(el) {
            el.addEventListener('click', function() {
                var idx = parseInt(this.getAttribute('data-evidx'), 10);
                var ev = calEventosGlobal[idx];
                if (!ev) return;
                var programaTexto = [(ev.codPrograma||'').trim(), (ev.nomPrograma||'').trim()].filter(Boolean).join(' — ') || '—';
                calEventoActualCodPrograma = (ev.codPrograma || '').toString().trim();
                document.getElementById('detEvPrograma').textContent = programaTexto;
                var verMas = document.getElementById('detEvVerMasPrograma');
                if (verMas) { if (calEventoActualCodPrograma) verMas.classList.remove('hidden'); else verMas.classList.add('hidden'); }
                document.getElementById('detEvGranja').textContent = [ev.granja||'', ev.nomGranja||''].filter(Boolean).join(' — ') || '—';
                document.getElementById('detEvCampania').textContent = ev.campania || '—';
                document.getElementById('detEvGalpon').textContent = ev.galpon || '—';
                document.getElementById('detEvEdad').textContent = ev.edad || '—';
                document.getElementById('detEvFecCarga').textContent = fechaDDMMYYYY(ev.fechaCarga) || '—';
                document.getElementById('detEvFecEjec').textContent = fechaDDMMYYYY(ev.fechaEjecucion) || '—';
                document.getElementById('modalDetalleEvento').classList.remove('hidden');
            });
        });
    }

    function renderVistaDia(eventosPorDia, colorByNumCronograma) {
        var eventos = eventosPorDia[fechaNavegacion] || [];
        var mesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        var f = parseFecha(fechaNavegacion);
        var titulo = f.getDate() + ' ' + mesNombres[f.getMonth()] + ' ' + f.getFullYear();
        var pdfLink = eventos.length > 0 ? '<a href="' + esc(urlPdfCronogramaDia(fechaNavegacion)) + '" class="cal-pdf-btn" target="_blank" rel="noopener" title="Reporte PDF cronogramas del día"><i class="fas fa-file-pdf"></i> PDF</a>' : '';
        var html = '<div class="cal-vista-dia-card"><div class="flex items-center justify-between gap-2 mb-3 flex-wrap"><h3 class="text-lg font-semibold text-gray-800">' + esc(titulo) + '</h3>' + pdfLink + '</div><div id="calContEventosDia"></div></div>';
        document.getElementById('calGrid').innerHTML = html;
        pintarEventosEnContenedor(eventos, colorByNumCronograma, 'calContEventosDia');
    }

    function renderVistaSemana(eventosPorDia, colorByNumCronograma) {
        var lunes = getLunesSemana(fechaNavegacion);
        var mesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        var diasSem = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
        var html = '<div class="cal-vista-semana">';
        for (var i = 0; i < 7; i++) {
            var key = addDays(lunes, i);
            var eventos = eventosPorDia[key] || [];
            var d = parseFecha(key);
            var titulo = diasSem[i] + ' ' + d.getDate() + ' ' + mesNombres[d.getMonth()];
            var pdfLink = eventos.length > 0 ? '<a href="' + esc(urlPdfCronogramaDia(key)) + '" class="cal-pdf-btn" target="_blank" rel="noopener" title="Reporte PDF cronogramas del día"><i class="fas fa-file-pdf"></i> PDF</a>' : '';
            html += '<div class="cal-vista-semana-dia"><div class="flex items-center justify-between gap-2 mb-1"><h4>' + esc(titulo) + '</h4>' + pdfLink + '</div><div id="calContSemana' + i + '"></div></div>';
        }
        html += '</div>';
        document.getElementById('calGrid').innerHTML = html;
        for (var j = 0; j < 7; j++) {
            var keyJ = addDays(lunes, j);
            pintarEventosEnContenedor(eventosPorDia[keyJ] || [], colorByNumCronograma, 'calContSemana' + j);
        }
    }

    function renderVistaMes(eventosPorDia, colorByNumCronograma, cronogramasLeyenda) {
        var mesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
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
        var html = '<div class="cal-grid"><div class="cal-dia-header">Dom</div><div class="cal-dia-header">Lun</div><div class="cal-dia-header">Mar</div><div class="cal-dia-header">Mié</div><div class="cal-dia-header">Jue</div><div class="cal-dia-header">Vie</div><div class="cal-dia-header">Sáb</div>';
        calEventosGlobal = [];
        diasGrid.forEach(function(cel) {
            var cls = 'cal-dia cal-dia-celda';
            if (!cel.esEsteMes) cls += ' otro-mes';
            if (cel.key === hoyStr) cls += ' cal-dia-hoy';
            if (calFechaElegida && cel.key === calFechaElegida && cel.key !== hoyStr) cls += ' cal-dia-elegida';
            html += '<div class="' + cls + '" data-key="' + esc(cel.key) + '" role="button" tabindex="0">';
            html += '<div class="cal-dia-num">' + cel.dia + '</div>';
            (cel.eventos || []).forEach(function(ev) {
                var idx = calEventosGlobal.length;
                calEventosGlobal.push(ev);
                var color = colorByNumCronograma[ev.numCronograma] || colorCronograma(ev.numCronograma);
                var programa = [(ev.codPrograma||'').trim(), (ev.nomPrograma||'').trim()].filter(Boolean).join(' — ') || 'Programa';
                var granja = (ev.nomGranja||ev.granja||'').trim() || '—';
                var titulo = 'Programa: ' + programa + '\nGranja: ' + granja;
                html += '<div class="cal-evento cal-evento-click" data-evidx="' + idx + '" style="border-left: 3px solid ' + color + '" title="' + esc(titulo) + '">';
                html += '<span class="cal-evento-dot" style="background:' + color + '"></span>';
                html += '<span class="cal-evento-texto"><span class="cal-evento-programa">' + esc(programa) + '</span><br><span class="cal-evento-granja">' + esc(granja) + '</span></span></div>';
            });
            if ((cel.eventos || []).length > 0) {
                var urlPdf = urlPdfCronogramaDia(cel.key);
                html += '<a href="' + esc(urlPdf) + '" class="cal-pdf-btn cal-pdf-celda" target="_blank" rel="noopener" title="Reporte PDF cronogramas del día" onclick="event.stopPropagation();"><i class="fas fa-file-pdf"></i></a>';
            }
            html += '</div>';
        });
        html += '</div>';
        document.getElementById('calGrid').innerHTML = html;
        document.querySelectorAll('#calGrid .cal-dia-celda').forEach(function(el) {
            el.addEventListener('click', function(ev) {
                if (ev.target.closest('.cal-evento-click')) return;
                var key = this.getAttribute('data-key');
                if (key) { calFechaElegida = key; renderCalendario(); }
            });
        });
        document.querySelectorAll('#calGrid .cal-evento-click').forEach(function(el) {
            el.addEventListener('click', function() {
                var idx = parseInt(this.getAttribute('data-evidx'), 10);
                var ev = calEventosGlobal[idx];
                if (!ev) return;
                var programaTexto = [(ev.codPrograma||'').trim(), (ev.nomPrograma||'').trim()].filter(Boolean).join(' — ') || '—';
                calEventoActualCodPrograma = (ev.codPrograma || '').toString().trim();
                document.getElementById('detEvPrograma').textContent = programaTexto;
                var verMas = document.getElementById('detEvVerMasPrograma');
                if (verMas) { if (calEventoActualCodPrograma) verMas.classList.remove('hidden'); else verMas.classList.add('hidden'); }
                document.getElementById('detEvGranja').textContent = [ev.granja||'', ev.nomGranja||''].filter(Boolean).join(' — ') || '—';
                document.getElementById('detEvCampania').textContent = ev.campania || '—';
                document.getElementById('detEvGalpon').textContent = ev.galpon || '—';
                document.getElementById('detEvEdad').textContent = ev.edad || '—';
                document.getElementById('detEvFecCarga').textContent = fechaDDMMYYYY(ev.fechaCarga) || '—';
                document.getElementById('detEvFecEjec').textContent = fechaDDMMYYYY(ev.fechaEjecucion) || '—';
                document.getElementById('modalDetalleEvento').classList.remove('hidden');
            });
        });
    }

    function renderVistaAnio(eventosPorDia, colorByNumCronograma) {
        var mesNombres = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        var hoy = new Date();
        var hoyStr = hoy.getFullYear() + '-' + pad2(hoy.getMonth() + 1) + '-' + pad2(hoy.getDate());
        var anio = parseFecha(fechaNavegacion).getFullYear();
        var html = '<div class="cal-vista-anio">';
        for (var mes = 0; mes < 12; mes++) {
            var primerdia = new Date(anio, mes, 1);
            var inicioSem = primerdia.getDay();
            var ultimoDiaMes = new Date(anio, mes + 1, 0).getDate();
            var ultimoDate = new Date(anio, mes, ultimoDiaMes);
            var primerFechaCal = new Date(anio, mes, 1 - inicioSem);
            var ultimaFechaCal = new Date(anio, mes, ultimoDiaMes + (6 - ultimoDate.getDay()));
            html += '<div class="cal-vista-anio-mes"><div class="font-semibold text-gray-700 mb-1">' + mesNombres[mes] + ' ' + anio + '</div><div class="cal-mini cal-grid">';
            html += '<div class="cal-dia-header">D</div><div class="cal-dia-header">L</div><div class="cal-dia-header">M</div><div class="cal-dia-header">X</div><div class="cal-dia-header">J</div><div class="cal-dia-header">V</div><div class="cal-dia-header">S</div>';
            var fecha = new Date(primerFechaCal.getTime());
            while (fecha.getTime() <= ultimaFechaCal.getTime()) {
                var esEsteMes = fecha.getMonth() === mes;
                var dia = fecha.getDate();
                var key = fecha.getFullYear() + '-' + pad2(fecha.getMonth() + 1) + '-' + pad2(dia);
                var tieneEventos = (eventosPorDia[key] || []).length > 0;
                var cls = 'cal-dia';
                if (!esEsteMes) cls += ' otro-mes';
                if (key === hoyStr) cls += ' hoy';
                if (tieneEventos) cls += ' has-eventos';
                html += '<div class="' + cls + '" data-key="' + esc(key) + '" role="button" tabindex="0">' + dia + '</div>';
                fecha.setDate(fecha.getDate() + 1);
            }
            html += '</div></div>';
        }
        html += '</div>';
        document.getElementById('calGrid').innerHTML = html;
        document.querySelectorAll('.cal-vista-anio-mes .cal-dia').forEach(function(el) {
            el.addEventListener('click', function() {
                var key = this.getAttribute('data-key');
                if (!key) return;
                var eventos = eventosPorDia[key] || [];
                var f = parseFecha(key);
                var mesNombresLargo = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
                document.getElementById('modalEventosDiaTitulo').textContent = 'Eventos del ' + f.getDate() + ' de ' + mesNombresLargo[f.getMonth()] + ' de ' + f.getFullYear();
                var pdfWrap = document.getElementById('modalEventosDiaPdf');
                if (pdfWrap) pdfWrap.innerHTML = eventos.length > 0 ? '<a href="' + urlPdfCronogramaDia(key) + '" class="cal-pdf-btn" target="_blank" rel="noopener" title="Reporte PDF cronogramas del día"><i class="fas fa-file-pdf"></i> PDF</a>' : '';
                var body = document.getElementById('modalEventosDiaBody');
                body.innerHTML = '<div id="calModalEventosDiaLista"></div>';
                pintarEventosEnContenedor(eventos, colorByNumCronograma, 'calModalEventosDiaLista');
                document.getElementById('modalEventosDia').classList.remove('hidden');
            });
        });
    }

    function renderCalendario() {
        codProgramaFiltroCal = null;
        var f = parseFecha(fechaNavegacion);
        anioActualCal = f.getFullYear();
        mesActualCal = f.getMonth();
        document.getElementById('calNavLabel').textContent = formatNavLabel();

        var cronogramasLeyenda = cronogramasUnicosParaLeyenda(window.calendarDataLeyendaAnio || calendarData);
        if ((!calCronogramasVisibles || calCronogramasVisibles.size === 0) && cronogramasLeyenda.length > 0) {
            calCronogramasVisibles = new Set(cronogramasLeyenda.map(function(c) { return c.numCronograma; }));
        }
        var eventosPorDia = eventosPorDiaDesdeListado();
        var colorByNumCronograma = {};
        cronogramasLeyenda.forEach(function(c, i) {
            colorByNumCronograma[c.numCronograma] = coloresGranja[i % coloresGranja.length];
        });
        document.getElementById('calAnioLeyenda').textContent = '(' + anioActualCal + ')';

        if (vistaActual === 'dia') { renderVistaDia(eventosPorDia, colorByNumCronograma); renderMiniCalendario(); actualizarPanelCronogramasAnio(cronogramasLeyenda); return; }
        if (vistaActual === 'semana') { renderVistaSemana(eventosPorDia, colorByNumCronograma); renderMiniCalendario(); actualizarPanelCronogramasAnio(cronogramasLeyenda); return; }
        if (vistaActual === 'anio') { renderVistaAnio(eventosPorDia, colorByNumCronograma); renderMiniCalendario(); actualizarPanelCronogramasAnio(cronogramasLeyenda); return; }
        renderVistaMes(eventosPorDia, colorByNumCronograma, cronogramasLeyenda);
        renderMiniCalendario();
        actualizarPanelCronogramasAnio(cronogramasLeyenda);
    }

    function actualizarPanelCronogramasAnio(cronogramasLeyenda) {
        var cont = document.getElementById('filtroCronogramasAnio');
        if (!cont) return;
        if (!cronogramasLeyenda || cronogramasLeyenda.length === 0) {
            cont.innerHTML = '<span class="text-gray-500 text-sm">No hay cronogramas en este período</span>';
            return;
        }
        var html = '';
        cronogramasLeyenda.forEach(function(c, i) {
            var color = colorCronograma(c.numCronograma);
            var checked = !calCronogramasVisibles || calCronogramasVisibles.size === 0 || calCronogramasVisibles.has(c.numCronograma);
            html += '<label class="leyenda-item block mb-1"><input type="checkbox" class="chk-crono-anio" data-numcronograma="' + c.numCronograma + '" ' + (checked ? 'checked' : '') + '><span class="leyenda-color inline-block" style="background:' + color + '"></span><span class="text-sm">' + esc(c.etiqueta) + '</span></label>';
        });
        cont.innerHTML = html;
        cont.querySelectorAll('.chk-crono-anio').forEach(function(chk) {
            chk.addEventListener('change', function() {
                var num = parseInt(this.getAttribute('data-numcronograma'), 10);
                if (!calCronogramasVisibles) calCronogramasVisibles = new Set();
                if (this.checked) calCronogramasVisibles.add(num); else calCronogramasVisibles.delete(num);
                renderCalendario();
            });
        });
    }

    function mostrarCargaCal(mostrar) {
        var el = document.getElementById('calCargaOverlay');
        if (!el) return;
        if (mostrar) el.classList.add('visible'); else el.classList.remove('visible');
    }

    function cargarDatosVista() {
        var sel = document.getElementById('calSelectVista');
        vistaActual = (sel && sel.value) ? sel.value : vistaActual;
        var v = vistaActual;
        if (v !== 'dia' && v !== 'semana' && v !== 'mes' && v !== 'anio') v = 'mes';
        vistaActual = v;
        if (sel && sel.value !== v) sel.value = v;

        var f = parseFecha(fechaNavegacion);
        if (isNaN(f.getTime())) {
            var hoy = new Date();
            fechaNavegacion = hoy.getFullYear() + '-' + pad2(hoy.getMonth() + 1) + '-' + pad2(hoy.getDate());
            f = parseFecha(fechaNavegacion);
        }
        var anio = f.getFullYear();
        var mes = f.getMonth();

        mostrarCargaCal(true);

        function finCarga() {
            mostrarCargaCal(false);
            renderCalendario();
        }

        if (v === 'dia') {
            var urlDia = '../cronograma/listar_cronograma.php?periodoTipo=ENTRE_FECHAS&fechaInicio=' + encodeURIComponent(fechaNavegacion) + '&fechaFin=' + encodeURIComponent(fechaNavegacion) + '&porFechaEjecucion=1';
            var urlAnio = '../cronograma/listar_cronograma.php?periodoTipo=ENTRE_FECHAS&fechaInicio=' + anio + '-01-01&fechaFin=' + anio + '-12-31' + '&porFechaEjecucion=1';
            return fetch(urlDia).then(function(r) { return r.json(); }).then(function(res) {
                calendarData = res.success && res.data ? res.data : [];
                return fetch(urlAnio).then(function(r2) { return r2.json(); }).then(function(res2) {
                    window.calendarDataLeyendaAnio = (res2.success && res2.data) ? res2.data : calendarData;
                    finCarga();
                }).catch(function() {
                    window.calendarDataLeyendaAnio = calendarData;
                    finCarga();
                });
            }).catch(function() {
                calendarData = [];
                fetch(urlAnio).then(function(r2) { return r2.json(); }).then(function(res2) {
                    window.calendarDataLeyendaAnio = (res2.success && res2.data) ? res2.data : [];
                    finCarga();
                }).catch(function() { window.calendarDataLeyendaAnio = []; finCarga(); });
            });
        }
        if (v === 'semana') {
            var lunes = getLunesSemana(fechaNavegacion);
            var domingo = getDomingoSemana(fechaNavegacion);
            var urlSemana = '../cronograma/listar_cronograma.php?periodoTipo=ENTRE_FECHAS&fechaInicio=' + encodeURIComponent(lunes) + '&fechaFin=' + encodeURIComponent(domingo) + '&porFechaEjecucion=1';
            var urlAnioS = '../cronograma/listar_cronograma.php?periodoTipo=ENTRE_FECHAS&fechaInicio=' + anio + '-01-01&fechaFin=' + anio + '-12-31' + '&porFechaEjecucion=1';
            return fetch(urlSemana).then(function(r) { return r.json(); }).then(function(res) {
                calendarData = res.success && res.data ? res.data : [];
                return fetch(urlAnioS).then(function(r2) { return r2.json(); }).then(function(res2) {
                    window.calendarDataLeyendaAnio = (res2.success && res2.data) ? res2.data : calendarData;
                    finCarga();
                }).catch(function() {
                    window.calendarDataLeyendaAnio = calendarData;
                    finCarga();
                });
            }).catch(function() {
                calendarData = [];
                fetch(urlAnioS).then(function(r2) { return r2.json(); }).then(function(res2) {
                    window.calendarDataLeyendaAnio = (res2.success && res2.data) ? res2.data : [];
                    finCarga();
                }).catch(function() { window.calendarDataLeyendaAnio = []; finCarga(); });
            });
        }
        if (v === 'mes') {
            var mesStr = (mes + 1) < 10 ? '0' + (mes + 1) : '' + (mes + 1);
            var mesEjecucion = anio + '-' + mesStr;
            var url = '../cronograma/listar_cronograma.php?mesEjecucion=' + encodeURIComponent(mesEjecucion);
            return fetch(url).then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) { calendarData = []; window.calendarDataLeyendaAnio = []; finCarga(); return; }
                calendarData = res.data || [];
                var urlAnio = '../cronograma/listar_cronograma.php?periodoTipo=ENTRE_FECHAS&fechaInicio=' + anio + '-01-01&fechaFin=' + anio + '-12-31' + '&porFechaEjecucion=1';
                return fetch(urlAnio).then(function(r2) { return r2.json(); }).then(function(res2) {
                    window.calendarDataLeyendaAnio = (res2.success && res2.data) ? res2.data : calendarData;
                    finCarga();
                }).catch(function() {
                    window.calendarDataLeyendaAnio = calendarData;
                    finCarga();
                });
            }).catch(function() {
                calendarData = []; window.calendarDataLeyendaAnio = []; finCarga();
            });
        }
        if (v === 'anio') {
            var url = '../cronograma/listar_cronograma.php?periodoTipo=ENTRE_FECHAS&fechaInicio=' + anio + '-01-01&fechaFin=' + anio + '-12-31' + '&porFechaEjecucion=1';
            return fetch(url).then(function(r) { return r.json(); }).then(function(res) {
                calendarData = res.success && res.data ? res.data : [];
                window.calendarDataLeyendaAnio = calendarData;
                finCarga();
            }).catch(function() {
                calendarData = []; window.calendarDataLeyendaAnio = []; finCarga();
            });
        }
        finCarga();
    }

    function cargarTiposPrograma() {
        fetch('../programas/get_tipos_programa.php').then(function(r) { return r.json(); }).then(function(res) {
            var cont = document.getElementById('filtroTiposPrograma');
            if (!res.success || !res.data || res.data.length === 0) {
                cont.innerHTML = '<span class="text-gray-500 text-sm">Sin tipos</span>';
                return;
            }
            var html = '';
            res.data.forEach(function(t) {
                html += '<label class="leyenda-item block mb-1"><input type="checkbox" class="chk-tipo" data-codigo="' + esc(t.codigo) + '" checked><span class="text-sm">' + esc(t.nombre || t.sigla || t.codigo) + '</span></label>';
            });
            cont.innerHTML = html;
            cont.querySelectorAll('.chk-tipo').forEach(function(chk) {
                chk.addEventListener('change', function() {
                    renderCalendario();
                });
            });
        }).catch(function() {
            document.getElementById('filtroTiposPrograma').innerHTML = '<span class="text-gray-500 text-sm">Error al cargar</span>';
        });
    }

    document.getElementById('calSelectVista').addEventListener('change', function() {
        vistaActual = this.value;
        cargarDatosVista();
    });

    document.getElementById('calNavPrev').addEventListener('click', function() {
        vistaActual = (document.getElementById('calSelectVista') && document.getElementById('calSelectVista').value) || vistaActual;
        if (vistaActual === 'dia') fechaNavegacion = addDays(fechaNavegacion, -1);
        else if (vistaActual === 'semana') fechaNavegacion = addDays(fechaNavegacion, -7);
        else if (vistaActual === 'mes') fechaNavegacion = addMonths(fechaNavegacion, -1);
        else if (vistaActual === 'anio') fechaNavegacion = addYears(fechaNavegacion, -1);
        cargarDatosVista();
    });
    document.getElementById('calNavNext').addEventListener('click', function() {
        vistaActual = (document.getElementById('calSelectVista') && document.getElementById('calSelectVista').value) || vistaActual;
        if (vistaActual === 'dia') fechaNavegacion = addDays(fechaNavegacion, 1);
        else if (vistaActual === 'semana') fechaNavegacion = addDays(fechaNavegacion, 7);
        else if (vistaActual === 'mes') fechaNavegacion = addMonths(fechaNavegacion, 1);
        else if (vistaActual === 'anio') fechaNavegacion = addYears(fechaNavegacion, 1);
        cargarDatosVista();
    });

    document.getElementById('calHoy').addEventListener('click', function() {
        var d = new Date();
        fechaNavegacion = d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
        calFechaElegida = null;
        cargarDatosVista();
    });

    document.getElementById('calMiniPrev').addEventListener('click', function() {
        miniCalMes--;
        if (miniCalMes < 0) { miniCalMes = 11; miniCalAnio--; }
        renderMiniCalendario();
    });
    document.getElementById('calMiniNext').addEventListener('click', function() {
        miniCalMes++;
        if (miniCalMes > 11) { miniCalMes = 0; miniCalAnio++; }
        renderMiniCalendario();
    });

    document.querySelectorAll('[data-modal]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-modal');
            if (id) document.getElementById(id).classList.add('hidden');
        });
    });
    document.getElementById('detEvVerMasPrograma').addEventListener('click', function(e) {
        e.preventDefault();
        if (calEventoActualCodPrograma) abrirModalDetalleProgramaCal(calEventoActualCodPrograma);
    });
    document.getElementById('modalDetalleEvento').addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
    document.getElementById('modalDetalleProgramaCal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
    document.getElementById('modalEventosDia').addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });

    var d = new Date();
    anioActualCal = d.getFullYear();
    mesActualCal = d.getMonth();
    miniCalMes = d.getMonth();
    miniCalAnio = d.getFullYear();
    cargarTiposPrograma();
    cargarDatosVista();
})();
    </script>
</body>
</html>
