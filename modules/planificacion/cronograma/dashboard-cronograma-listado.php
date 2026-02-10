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
    <style>
        body { background: #f8f9fa; font-family: system-ui, sans-serif; }
        .table-wrapper { overflow-x: auto; width: 100%; border-radius: 1rem; }
        .data-table { width: 100% !important; border-collapse: collapse; min-width: 600px; }
        .data-table th, .data-table td { padding: 0.75rem 1rem; text-align: left; font-size: 0.875rem; border-bottom: 1px solid #e5e7eb; }
        .data-table th {
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
            font-weight: 600; color: #ffffff !important; position: sticky; top: 0; z-index: 10;
        }
        .data-table tbody tr:hover { background-color: #eff6ff !important; }
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
        .cal-dia.otro-mes { background: #f1f5f9; color: #94a3b8; }
        .cal-dia-num { font-weight: 600; color: #475569; margin-bottom: 4px; }
        .cal-evento { padding: 2px 6px; border-radius: 4px; margin-bottom: 2px; font-size: 0.7rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; display: flex; align-items: center; gap: 4px; }
        .cal-evento:hover { opacity: 0.9; }
        .cal-evento-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .cal-evento-texto { overflow: hidden; text-overflow: ellipsis; }
        .leyenda { display: flex; flex-wrap: wrap; gap: 0.75rem 1rem; margin-top: 0.75rem; font-size: 0.8rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; }
        .leyenda-item { display: flex; align-items: center; gap: 0.35rem; }
        .leyenda-color { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border">
        <div class="mb-4 flex justify-end">
            <button type="button" id="btnAbrirCalendario" class="btn-cal">
                <i class="fas fa-calendar-alt mr-1"></i> Calendario
            </button>
        </div>
        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="table-wrapper p-4">
                <table id="tablaCronograma" class="data-table w-full text-sm config-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">N°</th>
                            <th class="px-4 py-3 text-left">Cód. Programa</th>
                            <th class="px-4 py-3 text-left">Nom. Programa</th>
                            <th class="px-4 py-3 text-left">Fecha Prog.</th>
                            <th class="px-4 py-3 text-left">Granja</th>
                            <th class="px-4 py-3 text-left">Campaña</th>
                            <th class="px-4 py-3 text-left">Galpón</th>
                            <th class="px-4 py-3 text-left">Fec. Carga</th>
                            <th class="px-4 py-3 text-left">Fec. Ejecución</th>
                            <th class="px-4 py-3 text-left">Detalles</th>
                            <th class="px-4 py-3 text-left">Opciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detalles: tabla con registros (fechas dd/mm/yyyy) -->
    <div id="modalDetalles" class="modal-overlay hidden">
        <div class="modal-box" style="max-width: 900px;">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-800">Detalles del cronograma</h3>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none" data-modal="modalDetalles">&times;</button>
            </div>
            <div class="modal-body overflow-x-auto">
                <p class="text-xs font-medium text-gray-500 mb-2">Programa: <strong id="detallesCodPrograma"></strong> — <span id="detallesTotal">0</span> registro(s)</p>
                <table class="data-table w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left bg-blue-600 text-white">N°</th>
                            <th class="px-3 py-2 text-left bg-blue-600 text-white">Cód. Programa</th>
                            <th class="px-3 py-2 text-left bg-blue-600 text-white">Nom. Programa</th>
                            <th class="px-3 py-2 text-left bg-blue-600 text-white">Fecha Prog.</th>
                            <th class="px-3 py-2 text-left bg-blue-600 text-white">Granja</th>
                            <th class="px-3 py-2 text-left bg-blue-600 text-white">Campaña</th>
                            <th class="px-3 py-2 text-left bg-blue-600 text-white">Galpón</th>
                            <th class="px-3 py-2 text-left bg-blue-600 text-white">Fec. Carga</th>
                            <th class="px-3 py-2 text-left bg-blue-600 text-white">Fec. Ejecución</th>
                        </tr>
                    </thead>
                    <tbody id="detallesLista"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Calendario -->
    <div id="modalCalendario" class="modal-overlay hidden">
        <div class="modal-box" style="max-width: 820px;">
            <div class="modal-header flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <button type="button" id="calPrevMes" class="px-2 py-1 rounded border border-gray-300 hover:bg-gray-100 text-sm">&laquo;</button>
                    <span id="calMesAnio" class="font-semibold text-gray-800"></span>
                    <button type="button" id="calNextMes" class="px-2 py-1 rounded border border-gray-300 hover:bg-gray-100 text-sm">&raquo;</button>
                </div>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none" data-modal="modalCalendario">&times;</button>
            </div>
            <div class="modal-body">
                <div id="calGrid" class="cal-grid"></div>
                <div id="calLeyenda" class="leyenda"></div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle evento (al hacer clic en un evento del calendario) -->
    <div id="modalDetalleEvento" class="modal-overlay hidden">
        <div class="modal-box" style="max-width: 420px;">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-800">Detalle del evento</h3>
                <button type="button" class="modal-cerrar text-gray-400 hover:text-gray-600 text-2xl leading-none" data-modal="modalDetalleEvento">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-gray-500 font-medium">Granja</dt><dd id="detEvGranja" class="text-gray-800"></dd></div>
                    <div><dt class="text-gray-500 font-medium">Nombre granja</dt><dd id="detEvNomGranja" class="text-gray-800"></dd></div>
                    <div><dt class="text-gray-500 font-medium">Campaña</dt><dd id="detEvCampania" class="text-gray-800"></dd></div>
                    <div><dt class="text-gray-500 font-medium">Galpón</dt><dd id="detEvGalpon" class="text-gray-800"></dd></div>
                    <div><dt class="text-gray-500 font-medium">Edad</dt><dd id="detEvEdad" class="text-gray-800"></dd></div>
                    <div><dt class="text-gray-500 font-medium">Programa</dt><dd id="detEvPrograma" class="text-gray-800"></dd></div>
                    <div><dt class="text-gray-500 font-medium">Fec. Carga</dt><dd id="detEvFecCarga" class="text-gray-800"></dd></div>
                    <div><dt class="text-gray-500 font-medium">Fec. Ejecución</dt><dd id="detEvFecEjec" class="text-gray-800"></dd></div>
                </dl>
            </div>
        </div>
    </div>

    <script>
        var listadoData = [];
        var mesActualCal = 0, anioActualCal = 0;
        var coloresGranja = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1'];

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

        function cargarListado() {
            fetch('listar_cronograma.php').then(r => r.json()).then(res => {
                if (!res.success) return;
                listadoData = res.data || [];
                if ($.fn.DataTable.isDataTable('#tablaCronograma')) $('#tablaCronograma').DataTable().destroy();
                var tbody = document.querySelector('#tablaCronograma tbody');
                tbody.innerHTML = '';
                listadoData.forEach(function(r, idx) {
                    var tr = document.createElement('tr');
                    tr.className = 'border-b border-gray-200';
                    var urlPdf = 'generar_reporte_cronograma_pdf.php?granja=' + encodeURIComponent(r.granja || '') + '&campania=' + encodeURIComponent(r.campania || '') + '&galpon=' + encodeURIComponent(r.galpon || '') + '&codPrograma=' + encodeURIComponent(r.codPrograma || '');
                    tr.innerHTML = '<td class="px-4 py-3">' + (idx + 1) + '</td>' +
                        '<td class="px-4 py-3">' + esc(r.codPrograma) + '</td>' +
                        '<td class="px-4 py-3">' + esc(r.nomPrograma) + '</td>' +
                        '<td class="px-4 py-3">' + esc(fechaDDMMYYYY(r.fechaHoraRegistro)) + '</td>' +
                        '<td class="px-4 py-3">' + esc(r.granja) + '</td>' +
                        '<td class="px-4 py-3">' + esc(r.campania) + '</td>' +
                        '<td class="px-4 py-3">' + esc(r.galpon) + '</td>' +
                        '<td class="px-4 py-3">' + esc(fechaDDMMYYYY(r.fechaCarga)) + '</td>' +
                        '<td class="px-4 py-3">' + esc(fechaDDMMYYYY(r.fechaEjecucion)) + '</td>' +
                        '<td class="px-4 py-3"><button type="button" class="btn-row btn-detalles" data-idx="' + idx + '"><i class="fas fa-list mr-1"></i>Ver</button></td>' +
                        '<td class="px-4 py-3"><a href="' + urlPdf + '" target="_blank" class="btn-row inline-flex items-center" title="Reporte PDF"><i class="fas fa-file-pdf text-red-600"></i></a></td>';
                    tbody.appendChild(tr);
                });
                $('#tablaCronograma').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }, order: [[1, 'asc']] });
            }).catch(function() {});
        }

        document.querySelectorAll('.modal-cerrar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-modal');
                if (id) document.getElementById(id).classList.add('hidden');
            });
        });
        document.getElementById('modalDetalles').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });

        document.getElementById('tablaCronograma').addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-detalles');
            if (!btn) return;
            e.preventDefault();
            var idx = parseInt(btn.getAttribute('data-idx'), 10);
            var r = listadoData[idx];
            if (!r) return;
            var mismos = listadoData.filter(function(x) {
                return x.codPrograma === r.codPrograma && x.granja === r.granja && x.campania === r.campania && x.galpon === r.galpon;
            });
            document.getElementById('detallesCodPrograma').textContent = (r.codPrograma || '') + ' — ' + (r.nomPrograma || '');
            document.getElementById('detallesTotal').textContent = mismos.length;
            var tbody = document.getElementById('detallesLista');
            tbody.innerHTML = '';
            mismos.forEach(function(x, i) {
                var tr = document.createElement('tr');
                tr.className = 'border-b border-gray-200';
                tr.innerHTML = '<td class="px-3 py-2">' + (i + 1) + '</td>' +
                    '<td class="px-3 py-2">' + esc(x.codPrograma) + '</td>' +
                    '<td class="px-3 py-2">' + esc(x.nomPrograma) + '</td>' +
                    '<td class="px-3 py-2">' + esc(fechaDDMMYYYY(x.fechaHoraRegistro)) + '</td>' +
                    '<td class="px-3 py-2">' + esc(x.granja) + '</td>' +
                    '<td class="px-3 py-2">' + esc(x.campania) + '</td>' +
                    '<td class="px-3 py-2">' + esc(x.galpon) + '</td>' +
                    '<td class="px-3 py-2">' + esc(fechaDDMMYYYY(x.fechaCarga)) + '</td>' +
                    '<td class="px-3 py-2">' + esc(fechaDDMMYYYY(x.fechaEjecucion)) + '</td>';
                tbody.appendChild(tr);
            });
            document.getElementById('modalDetalles').classList.remove('hidden');
        });

        function eventosPorDiaDesdeListado() {
            var map = {};
            listadoData.forEach(function(r) {
                var fec = (r.fechaEjecucion || '').toString().trim();
                var key = fec.substring(0, 10);
                if (!key || key.length < 10) return;
                if (!map[key]) map[key] = [];
                map[key].push({
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
            listadoData.forEach(function(r) {
                var g = (r.granja || '').toString().trim();
                if (g && !seen[g]) {
                    seen[g] = true;
                    out.push({ granja: g, nomGranja: (r.nomGranja || g).toString().trim() });
                }
            });
            return out;
        }

        var calEventosGlobal = [];
        function renderCalendario() {
            var eventosPorDia = eventosPorDiaDesdeListado();
            var granjasLeyenda = granjasUnicasParaLeyenda();
            calEventosGlobal = [];
            var mesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            document.getElementById('calMesAnio').textContent = mesNombres[mesActualCal] + ' ' + anioActualCal;

            function pad2(n) { return (n < 10 ? '0' : '') + n; }
            var primerdia = new Date(anioActualCal, mesActualCal, 1);
            var inicioSem = primerdia.getDay();
            var diasGrid = [];
            var d = 1 - inicioSem;
            for (var i = 0; i < 42; i++) {
                var fecha = new Date(anioActualCal, mesActualCal, d + i);
                var esEsteMes = fecha.getMonth() === mesActualCal;
                var dia = fecha.getDate();
                var key = fecha.getFullYear() + '-' + pad2(fecha.getMonth() + 1) + '-' + pad2(dia);
                var eventos = eventosPorDia[key] || [];
                diasGrid.push({ dia: dia, esEsteMes: esEsteMes, key: key, eventos: eventos });
            }

            var html = '<div class="cal-dia-header">Dom</div><div class="cal-dia-header">Lun</div><div class="cal-dia-header">Mar</div><div class="cal-dia-header">Mié</div><div class="cal-dia-header">Jue</div><div class="cal-dia-header">Vie</div><div class="cal-dia-header">Sáb</div>';
            diasGrid.forEach(function(cel) {
                var cls = 'cal-dia';
                if (!cel.esEsteMes) cls += ' otro-mes';
                html += '<div class="' + cls + '">';
                html += '<div class="cal-dia-num">' + cel.dia + '</div>';
                (cel.eventos || []).forEach(function(ev) {
                    var idx = calEventosGlobal.length;
                    calEventosGlobal.push(ev);
                    var color = colorGranja(ev.granja);
                    var texto = (ev.nomGranja || ev.granja) + ' · ' + (ev.campania || '') + ' · ' + (ev.galpon || '') + (ev.edad !== '—' && ev.edad !== '' ? ' · ' + ev.edad + 'd' : '');
                    html += '<div class="cal-evento cal-evento-click" data-evidx="' + idx + '" style="border-left: 3px solid ' + color + '">';
                    html += '<span class="cal-evento-dot" style="background:' + color + '"></span>';
                    html += '<span class="cal-evento-texto" title="' + esc(texto) + '">' + esc(texto) + '</span>';
                    html += '</div>';
                });
                html += '</div>';
            });
            document.getElementById('calGrid').innerHTML = html;

            var leyendaHtml = '';
            granjasLeyenda.forEach(function(g) {
                var color = colorGranja(g.granja);
                leyendaHtml += '<div class="leyenda-item"><span class="leyenda-color" style="background:' + color + '"></span><span>' + esc(g.nomGranja || g.granja) + '</span></div>';
            });
            document.getElementById('calLeyenda').innerHTML = leyendaHtml || '<span class="text-gray-500">Sin eventos en el cronograma</span>';

            document.querySelectorAll('.cal-evento-click').forEach(function(el) {
                el.addEventListener('click', function() {
                    var idx = parseInt(this.getAttribute('data-evidx'), 10);
                    var ev = calEventosGlobal[idx];
                    if (!ev) return;
                    document.getElementById('detEvGranja').textContent = ev.granja || '—';
                    document.getElementById('detEvNomGranja').textContent = ev.nomGranja || '—';
                    document.getElementById('detEvCampania').textContent = ev.campania || '—';
                    document.getElementById('detEvGalpon').textContent = ev.galpon || '—';
                    document.getElementById('detEvEdad').textContent = ev.edad !== undefined && ev.edad !== null && ev.edad !== '' ? ev.edad : '—';
                    document.getElementById('detEvPrograma').textContent = (ev.codPrograma || '') + (ev.nomPrograma ? ' — ' + ev.nomPrograma : '');
                    document.getElementById('detEvFecCarga').textContent = fechaDDMMYYYY(ev.fechaCarga) || '—';
                    document.getElementById('detEvFecEjec').textContent = fechaDDMMYYYY(ev.fechaEjecucion) || '—';
                    document.getElementById('modalDetalleEvento').classList.remove('hidden');
                });
            });
        }

        document.getElementById('btnAbrirCalendario').addEventListener('click', function() {
            var hoy = new Date();
            mesActualCal = hoy.getMonth();
            anioActualCal = hoy.getFullYear();
            renderCalendario();
            document.getElementById('modalCalendario').classList.remove('hidden');
        });
        document.getElementById('modalCalendario').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
        document.getElementById('modalDetalleEvento').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
        document.getElementById('calPrevMes').addEventListener('click', function() {
            mesActualCal--;
            if (mesActualCal < 0) { mesActualCal = 11; anioActualCal--; }
            renderCalendario();
        });
        document.getElementById('calNextMes').addEventListener('click', function() {
            mesActualCal++;
            if (mesActualCal > 11) { mesActualCal = 0; anioActualCal++; }
            renderCalendario();
        });

        cargarListado();
    </script>
</body>
</html>
