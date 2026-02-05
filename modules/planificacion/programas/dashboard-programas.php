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

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
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
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
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
        .bloque-necropsias { display: none; }
        .bloque-necropsias.visible { display: block; }
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
            max-width: 520px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-body { padding: 1.5rem; }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }
        /* Edades: etiquetas */
        .edades-input-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        .edades-input-wrap input[type="number"] {
            width: 5rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }
        .edades-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .edad-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.6rem;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 500;
        }
        .edad-tag button {
            background: none;
            border: none;
            color: #6366f1;
            cursor: pointer;
            padding: 0 0.15rem;
            line-height: 1;
            font-size: 1rem;
        }
        .edad-tag button:hover { color: #dc2626; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border">

        <!-- Card principal (estilo reportes) -->
        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Programas</h2>
                <button type="button" id="btnNuevoPrograma" class="btn-primary">
                    <i class="fas fa-plus"></i> Nuevo programa
                </button>
            </div>

            <div class="table-wrapper p-4">
                <table id="tablaProgramas" class="data-table w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">Código</th>
                            <th class="px-4 py-3 text-left">Nombre</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Edades (días)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Ver edades -->
    <div id="modalVerEdades" class="modal-overlay hidden">
        <div class="modal-box" style="max-width: 320px;">
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-gray-800">Edades (días)</h3>
                <button type="button" class="modal-cerrar-ver text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="modal-body">
                <ul id="modalVerEdadesLista" class="list-none p-0 m-0 space-y-1 text-sm text-gray-800"></ul>
            </div>
        </div>
    </div>

    <!-- Modal Registrar programa -->
    <div id="modalPrograma" class="modal-overlay hidden" aria-hidden="true">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-800">Registrar programa</h3>
                <button type="button" id="modalCerrar" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" aria-label="Cerrar">&times;</button>
            </div>
            <form id="formPrograma">
                <div class="modal-body space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de programa *</label>
                        <select id="tipo" name="codTipo" class="form-control" required>
                            <option value="">Seleccione tipo...</option>
                        </select>
                    </div>

                    <div id="bloqueNecropsias" class="bloque-necropsias space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                                <input type="text" id="codigo" name="codigo" class="form-control bg-gray-100" placeholder="Automático" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Necropsia campaña 2026" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Edades (días) *</label>
                            <div class="edades-input-wrap">
                                <input type="number" id="inputEdad" min="1" max="999" placeholder="Ej: 21" class="flex-shrink-0">
                                <button type="button" id="btnAgregarEdad" class="px-3 py-2 rounded-lg border border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100 text-sm font-medium">
                                    <i class="fas fa-plus mr-1"></i> Agregar edad
                                </button>
                            </div>
                            <div id="listaEdades" class="edades-tags" aria-live="polite"></div>
                            <p class="text-xs text-gray-500 mt-1">Agregue una o más edades en días. Puede quitar con ×.</p>
                        </div>
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

    <script>
        const TIPO_NECROPSIAS = 'NECROPSIAS';
        let edadesSeleccionadas = [];

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
                        opt.dataset.nombre = t.nombre;
                        sel.appendChild(opt);
                    });
                })
                .catch(() => {});
        }

        function generarCodigoNec() {
            fetch('generar_codigo_nec.php')
                .then(r => r.json())
                .then(res => {
                    if (res.success) document.getElementById('codigo').value = res.codigo || '';
                })
                .catch(() => {});
        }

        function actualizarListaEdades() {
            const cont = document.getElementById('listaEdades');
            cont.innerHTML = '';
            edadesSeleccionadas.forEach((edad, i) => {
                const tag = document.createElement('span');
                tag.className = 'edad-tag';
                tag.innerHTML = edad + ' <button type="button" data-index="' + i + '" aria-label="Quitar ' + edad + '">&times;</button>';
                tag.querySelector('button').addEventListener('click', () => {
                    edadesSeleccionadas.splice(i, 1);
                    actualizarListaEdades();
                });
                cont.appendChild(tag);
            });
        }

        document.getElementById('btnAgregarEdad').addEventListener('click', function() {
            const input = document.getElementById('inputEdad');
            const n = parseInt(input.value, 10);
            if (isNaN(n) || n < 1 || n > 999) {
                Swal.fire({ icon: 'warning', title: 'Edad inválida', text: 'Ingrese un número entre 1 y 999.' });
                return;
            }
            if (edadesSeleccionadas.indexOf(n) !== -1) {
                Swal.fire({ icon: 'info', title: 'Ya agregada', text: 'La edad ' + n + ' ya está en la lista.' });
                return;
            }
            edadesSeleccionadas.push(n);
            edadesSeleccionadas.sort((a, b) => a - b);
            actualizarListaEdades();
            input.value = '';
            input.focus();
        });

        document.getElementById('inputEdad').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('btnAgregarEdad').click();
            }
        });

        document.getElementById('tipo').addEventListener('change', function() {
            const nombre = this.options[this.selectedIndex]?.dataset?.nombre || this.options[this.selectedIndex]?.textContent || '';
            const bloque = document.getElementById('bloqueNecropsias');
            if (nombre.trim().toUpperCase() === TIPO_NECROPSIAS) {
                bloque.classList.add('visible');
                document.getElementById('nombre').required = true;
                generarCodigoNec();
            } else {
                bloque.classList.remove('visible');
                document.getElementById('codigo').value = '';
                document.getElementById('nombre').required = false;
            }
        });

        function cargarListado() {
            fetch('listar_programas.php')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    if ($.fn.DataTable.isDataTable('#tablaProgramas')) {
                        $('#tablaProgramas').DataTable().destroy();
                    }
                    const tbody = document.querySelector('#tablaProgramas tbody');
                    tbody.innerHTML = '';
                    (res.data || []).forEach(p => {
                        const tr = document.createElement('tr');
                        tr.className = 'border-b border-gray-200';
                        const edadesStr = (p.edades || '').toString();
                        tr.innerHTML = '<td class="px-4 py-3">' + (p.codigo || '') + '</td>' +
                            '<td class="px-4 py-3">' + (p.nombre || '') + '</td>' +
                            '<td class="px-4 py-3">' + (p.nomTipo || '') + '</td>' +
                            '<td class="px-4 py-3"><button type="button" class="btn-ver-edades px-2 py-1 rounded border border-blue-300 text-blue-700 hover:bg-blue-50 text-xs" data-codigo="' + (p.codigo || '') + '" data-nombre="' + (p.nombre || '').replace(/"/g, '&quot;') + '" data-edades="' + (edadesStr || '').replace(/"/g, '&quot;') + '"><i class="fas fa-eye mr-1"></i>Ver</button></td>';
                        tbody.appendChild(tr);
                    });
                    document.getElementById('tablaProgramas').addEventListener('click', function(e) {
                        var btn = e.target.closest('.btn-ver-edades');
                        if (!btn) return;
                        var edades = (btn.dataset.edades || '').split(',').filter(Boolean);
                        var ul = document.getElementById('modalVerEdadesLista');
                        ul.innerHTML = '';
                        edades.forEach(function(edad) {
                            var li = document.createElement('li');
                            li.className = 'py-1 border-b border-gray-100 last:border-0';
                            li.textContent = edad.trim() + ' días';
                            ul.appendChild(li);
                        });
                        if (edades.length === 0) { var li = document.createElement('li'); li.textContent = 'Sin edades'; ul.appendChild(li); }
                        document.getElementById('modalVerEdades').classList.remove('hidden');
                    });
                    $('#tablaProgramas').DataTable({
                        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                        order: [[0, 'desc']]
                    });
                })
                .catch(() => {});
        }

        function abrirModal() {
            document.getElementById('formPrograma').reset();
            document.getElementById('codigo').value = '';
            edadesSeleccionadas = [];
            actualizarListaEdades();
            document.getElementById('bloqueNecropsias').classList.remove('visible');
            document.getElementById('modalPrograma').classList.remove('hidden');
            cargarTipos().then(function() {
                var sel = document.getElementById('tipo');
                for (var i = 0; i < sel.options.length; i++) {
                    var opt = sel.options[i];
                    var nom = (opt.dataset && opt.dataset.nombre) ? opt.dataset.nombre : opt.textContent;
                    if (nom && nom.trim().toUpperCase() === TIPO_NECROPSIAS) {
                        sel.selectedIndex = i;
                        document.getElementById('bloqueNecropsias').classList.add('visible');
                        document.getElementById('nombre').required = true;
                        generarCodigoNec();
                        break;
                    }
                }
            });
        }

        function cerrarModal() {
            document.getElementById('modalPrograma').classList.add('hidden');
        }

        document.getElementById('modalVerEdades').addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('modal-cerrar-ver')) this.classList.add('hidden');
        });
        document.getElementById('btnNuevoPrograma').addEventListener('click', abrirModal);
        document.getElementById('btnCancelarForm').addEventListener('click', cerrarModal);
        document.getElementById('modalCerrar').addEventListener('click', cerrarModal);
        document.getElementById('modalPrograma').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });

        document.getElementById('formPrograma').addEventListener('submit', function(e) {
            e.preventDefault();
            const tipo = document.getElementById('tipo');
            const codTipo = tipo.value;
            const nomTipo = tipo.options[tipo.selectedIndex]?.textContent || '';
            const codigo = document.getElementById('codigo').value.trim();
            const nombre = document.getElementById('nombre').value.trim();
            if (nomTipo.toUpperCase() === TIPO_NECROPSIAS) {
                if (!codigo || !nombre) {
                    Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Complete código y nombre.' });
                    return;
                }
                if (edadesSeleccionadas.length === 0) {
                    Swal.fire({ icon: 'warning', title: 'Edades requeridas', text: 'Agregue al menos una edad (días).' });
                    return;
                }
            }
            const edades = edadesSeleccionadas.join(',');
            const payload = { codigo, nombre, codTipo: parseInt(codTipo, 10), nomTipo, edades };
            fetch('guardar_programa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Guardado', text: res.message });
                    cerrarModal();
                    cargarListado();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo guardar.' });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }));
        });

        cargarTipos();
        cargarListado();
    </script>
</body>
</html>
