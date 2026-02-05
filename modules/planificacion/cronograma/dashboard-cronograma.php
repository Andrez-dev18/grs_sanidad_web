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
    <title>Cronograma - Planificación</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
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
        .table-wrapper { overflow-x: auto; width: 100%; border-radius: 1rem; }
        .data-table { width: 100% !important; border-collapse: collapse; min-width: 800px; }
        .data-table th, .data-table td { padding: 0.75rem 1rem; text-align: left; font-size: 0.875rem; border-bottom: 1px solid #e5e7eb; }
        .data-table th {
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
            font-weight: 600; color: #ffffff !important; position: sticky; top: 0; z-index: 10;
        }
        .data-table tbody tr:hover { background-color: #eff6ff !important; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding: 1rem; }
        .bloque-necropsias { display: none; }
        .bloque-necropsias.visible { display: block; }
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50;
            display: flex; align-items: center; justify-content: center; padding: 1rem;
        }
        .modal-overlay.hidden { display: none; }
        .modal-box {
            background: white; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            max-width: 560px; width: 100%; max-height: 90vh; overflow-y: auto;
        }
        .modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 0.75rem; justify-content: flex-end; }
        #fechasResultado { margin-top: 1rem; padding: 0.75rem; background: #f0fdf4; border-radius: 0.5rem; font-size: 0.875rem; }
        #fechasResultado ul { margin: 0; padding-left: 1.25rem; }
        .btn-ver-fechas { padding: 0.25rem 0.5rem; border-radius: 0.375rem; border: 1px solid #93c5fd; color: #2563eb; background: #eff6ff; font-size: 0.75rem; cursor: pointer; }
        .btn-ver-fechas:hover { background: #dbeafe; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border">
        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Cronograma</h2>
                <button type="button" id="btnNuevoCronograma" class="btn-primary">
                    <i class="fas fa-plus"></i> Nuevo cronograma
                </button>
            </div>
            <div class="table-wrapper p-4">
                <table id="tablaCronograma" class="data-table w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">Granja</th>
                            <th class="px-4 py-3 text-left">Campaña</th>
                            <th class="px-4 py-3 text-left">Galpón</th>
                            <th class="px-4 py-3 text-left">Programa</th>
                            <th class="px-4 py-3 text-left">Fechas</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Ver fechas -->
    <div id="modalVerFechas" class="modal-overlay hidden">
        <div class="modal-box" style="max-width: 320px;">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-800">Fechas</h3>
                <button type="button" class="modal-cerrar-ver text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="modal-body">
                <ul id="modalVerFechasLista" class="list-none p-0 m-0 space-y-1 text-sm text-gray-800"></ul>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo cronograma -->
    <div id="modalCronograma" class="modal-overlay hidden">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-800">Nuevo cronograma</h3>
                <button type="button" id="modalCronogramaCerrar" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="modal-body space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <select id="cronoTipo" class="form-control">
                        <option value="">Seleccione tipo...</option>
                        <option value="NECROPSIAS">NECROPSIAS</option>
                    </select>
                </div>
                <div id="bloqueCronoNecropsias" class="bloque-necropsias space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Granja *</label>
                            <select id="cronoGranja" class="form-control">
                                <option value="">Seleccione...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Campaña *</label>
                            <select id="cronoCampania" class="form-control" disabled>
                                <option value="">Primero granja</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Galpón *</label>
                            <select id="cronoGalpon" class="form-control" disabled>
                                <option value="">Primero granja</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Programa *</label>
                        <select id="cronoPrograma" class="form-control">
                            <option value="">Seleccione programa...</option>
                        </select>
                    </div>
                    <button type="button" id="btnAsignar" class="btn-primary w-full sm:w-auto">
                        <i class="fas fa-calendar-check mr-1"></i> Asignar
                    </button>
                    <div id="fechasResultado" class="hidden"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCancelarCrono" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">Cancelar</button>
                <button type="button" id="btnGuardarCrono" class="btn-primary" disabled>
                    <i class="fas fa-save"></i> Guardar cronograma
                </button>
            </div>
        </div>
    </div>

    <script>
        const TIPO_NECROPSIAS = 'NECROPSIAS';
        let fechasAsignadas = [];

        function cargarGranjas() {
            fetch('get_granjas.php').then(r => r.json()).then(data => {
                var sel = document.getElementById('cronoGranja');
                sel.innerHTML = '<option value="">Seleccione...</option>';
                (data || []).forEach(g => {
                    var opt = document.createElement('option');
                    opt.value = g.codigo;
                    opt.textContent = g.codigo + ' - ' + (g.nombre || '');
                    sel.appendChild(opt);
                });
            }).catch(() => {});
        }

        document.getElementById('cronoGranja').addEventListener('change', function() {
            var granja = this.value;
            var camp = document.getElementById('cronoCampania');
            var galp = document.getElementById('cronoGalpon');
            camp.innerHTML = '<option value="">Cargando...</option>';
            camp.disabled = true;
            galp.innerHTML = '<option value="">Primero granja</option>';
            galp.disabled = true;
            if (!granja) return;
            fetch('get_campanias.php?granja=' + encodeURIComponent(granja)).then(r => r.json()).then(data => {
                camp.innerHTML = '<option value="">Seleccione campaña...</option>';
                (data || []).forEach(c => {
                    var opt = document.createElement('option');
                    opt.value = c.campania;
                    opt.textContent = c.campania;
                    camp.appendChild(opt);
                });
                camp.disabled = false;
            }).catch(() => { camp.innerHTML = '<option value="">Error</option>'; camp.disabled = false; });
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

        function cargarProgramas() {
            fetch('get_programas.php').then(r => r.json()).then(res => {
                if (!res.success) return;
                var sel = document.getElementById('cronoPrograma');
                sel.innerHTML = '<option value="">Seleccione programa...</option>';
                (res.data || []).forEach(p => {
                    var opt = document.createElement('option');
                    opt.value = p.codigo;
                    opt.textContent = p.label || (p.codigo + ' - ' + p.nombre);
                    opt.dataset.nombre = p.nombre || '';
                    sel.appendChild(opt);
                });
            }).catch(() => {});
        }

        document.getElementById('cronoTipo').addEventListener('change', function() {
            var bloque = document.getElementById('bloqueCronoNecropsias');
            if (this.value === TIPO_NECROPSIAS) bloque.classList.add('visible');
            else bloque.classList.remove('visible');
        });

        document.getElementById('btnAsignar').addEventListener('click', function() {
            var granja = document.getElementById('cronoGranja').value.trim();
            var campania = document.getElementById('cronoCampania').value.trim();
            var galpon = document.getElementById('cronoGalpon').value.trim();
            var codPrograma = document.getElementById('cronoPrograma').value.trim();
            if (!granja || !campania || !galpon || !codPrograma) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Seleccione granja, campaña, galpón y programa.' });
                return;
            }
            var fd = new FormData();
            fd.append('granja', granja);
            fd.append('campania', campania);
            fd.append('galpon', galpon);
            fd.append('codPrograma', codPrograma);
            fetch('calcular_fechas.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    var div = document.getElementById('fechasResultado');
                    if (!res.success) {
                        div.classList.add('hidden');
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudieron calcular fechas.' });
                        return;
                    }
                    fechasAsignadas = res.fechas || [];
                    if (fechasAsignadas.length === 0) {
                        div.innerHTML = '<p class="text-amber-700">No se encontraron fechas para los criterios seleccionados.</p>';
                    } else {
                        div.innerHTML = '<p class="font-medium text-gray-700 mb-1">Fechas resultantes (' + fechasAsignadas.length + '):</p><ul><li>' + fechasAsignadas.join('</li><li>') + '</li></ul>';
                    }
                    div.classList.remove('hidden');
                    document.getElementById('btnGuardarCrono').disabled = fechasAsignadas.length === 0;
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }));
        });

        document.getElementById('btnGuardarCrono').addEventListener('click', function() {
            if (fechasAsignadas.length === 0) return;
            var granja = document.getElementById('cronoGranja').value.trim();
            var campania = document.getElementById('cronoCampania').value.trim();
            var galpon = document.getElementById('cronoGalpon').value.trim();
            var prog = document.getElementById('cronoPrograma');
            var codPrograma = prog.value.trim();
            var nomPrograma = prog.options[prog.selectedIndex] ? (prog.options[prog.selectedIndex].dataset.nombre || '') : '';
            fetch('guardar_cronograma.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ granja: granja, campania: campania, galpon: galpon, codPrograma: codPrograma, nomPrograma: nomPrograma, fechas: fechasAsignadas })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Guardado', text: res.message });
                        cerrarModalCrono();
                        cargarListado();
                    } else Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo guardar.' });
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }));
        });

        function cargarListado() {
            fetch('listar_cronograma.php').then(r => r.json()).then(res => {
                if (!res.success) return;
                if ($.fn.DataTable.isDataTable('#tablaCronograma')) $('#tablaCronograma').DataTable().destroy();
                var tbody = document.querySelector('#tablaCronograma tbody');
                tbody.innerHTML = '';
                (res.data || []).forEach(c => {
                    var tr = document.createElement('tr');
                    tr.className = 'border-b border-gray-200';
                    tr.innerHTML = '<td class="px-4 py-3">' + (c.granja || '') + '</td>' +
                        '<td class="px-4 py-3">' + (c.campania || '') + '</td>' +
                        '<td class="px-4 py-3">' + (c.galpon || '') + '</td>' +
                        '<td class="px-4 py-3">' + (c.codPrograma || '') + ' - ' + (c.nomPrograma || '') + '</td>' +
                        '<td class="px-4 py-3"><button type="button" class="btn-ver-fechas px-2 py-1 rounded border border-blue-300 text-blue-700 hover:bg-blue-50 text-xs" data-fechas="' + (c.fechas || '').replace(/"/g, '&quot;') + '" data-titulo="' + (c.granja + ' / ' + c.campania + ' / ' + c.galpon + ' - ' + c.codPrograma).replace(/"/g, '&quot;') + '"><i class="fas fa-eye mr-1"></i>Ver</button></td>';
                    tbody.appendChild(tr);
                });
                $('#tablaCronograma').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }, order: [[0, 'asc']] });
            }).catch(() => {});
        }

        function abrirModalCrono() {
            document.getElementById('cronoTipo').value = '';
            document.getElementById('bloqueCronoNecropsias').classList.remove('visible');
            document.getElementById('cronoGranja').value = '';
            document.getElementById('cronoCampania').innerHTML = '<option value="">Primero granja</option>';
            document.getElementById('cronoCampania').disabled = true;
            document.getElementById('cronoGalpon').innerHTML = '<option value="">Primero granja</option>';
            document.getElementById('cronoGalpon').disabled = true;
            document.getElementById('cronoPrograma').value = '';
            document.getElementById('fechasResultado').classList.add('hidden');
            document.getElementById('btnGuardarCrono').disabled = true;
            fechasAsignadas = [];
            document.getElementById('modalCronograma').classList.remove('hidden');
            cargarGranjas();
            cargarProgramas();
        }

        function cerrarModalCrono() {
            document.getElementById('modalCronograma').classList.add('hidden');
        }

        document.getElementById('btnNuevoCronograma').addEventListener('click', abrirModalCrono);
        document.getElementById('btnCancelarCrono').addEventListener('click', cerrarModalCrono);
        document.getElementById('modalCronogramaCerrar').addEventListener('click', cerrarModalCrono);
        document.getElementById('modalCronograma').addEventListener('click', function(e) { if (e.target === this) cerrarModalCrono(); });
        document.getElementById('modalVerFechas').addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('modal-cerrar-ver')) this.classList.add('hidden');
        });
        document.getElementById('tablaCronograma').addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-ver-fechas');
            if (!btn) return;
            var fechas = (btn.dataset.fechas || '').split(',').filter(Boolean);
            var ul = document.getElementById('modalVerFechasLista');
            ul.innerHTML = '';
            fechas.forEach(function(f) {
                var li = document.createElement('li');
                li.className = 'py-1 border-b border-gray-100 last:border-0';
                li.textContent = f.trim();
                ul.appendChild(li);
            });
            if (fechas.length === 0) { var li = document.createElement('li'); li.textContent = 'Sin fechas'; ul.appendChild(li); }
            document.getElementById('modalVerFechas').classList.remove('hidden');
        });

        cargarListado();
    </script>
</body>
</html>
