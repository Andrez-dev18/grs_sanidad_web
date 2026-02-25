<?php
session_start();
if (empty($_SESSION['active']) || empty($_SESSION['usuario'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; }
        else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}

include_once '../../../../conexion_grs/conexion.php';
$conexion = conectar_joya_mysqli();
if (!$conexion) {
    die('Error de conexion.');
}

$esAdmin = false;
$stmt = $conexion->prepare("SELECT rol_sanidad FROM usuario WHERE codigo = ? AND estado = 'A' LIMIT 1");
if ($stmt) {
    $stmt->bind_param("s", $_SESSION['usuario']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $rol = strtoupper(trim((string)($row['rol_sanidad'] ?? '')));
        $esAdmin = ($rol === 'ADMIN');
    }
    $stmt->close();
}

if (!$esAdmin) {
    echo '<div style="padding:24px;font-family:Segoe UI,Arial,sans-serif">
        <h3 style="margin:0 0 8px 0;color:#b91c1c">No autorizado</h3>
        <p style="margin:0;color:#374151">Solo usuarios con rol ADMIN pueden ingresar a esta seccion.</p>
    </div>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones WhatsApp - Usuarios</title>
    <link href="../../../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../../assets/js/sweetalert-helpers.js"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 py-8">
        <div class="max-w-6xl mx-auto">
            <div class="mb-4 sm:mb-6 bg-white border rounded-xl sm:rounded-2xl shadow-sm overflow-hidden min-w-0">
                <button type="button" id="btnToggleFiltrosNotiUsers"
                    class="w-full flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 hover:bg-gray-100 transition">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🔎</span>
                        <h3 class="text-base font-semibold text-gray-800">Filtros de búsqueda</h3>
                    </div>
                    <svg id="iconoFiltrosNotiUsers" class="w-5 h-5 text-gray-600 transition-transform duration-300"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="contenidoFiltrosNotiUsers" class="px-4 sm:px-6 pb-4 sm:pb-6 pt-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="filtroNotificaciones" class="block text-sm font-medium text-gray-700 mb-1">Notificaciones</label>
                            <select id="filtroNotificaciones" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                                <option value="TODOS">Todos</option>
                                <option value="AUTORIZADO">Autorizado</option>
                                <option value="NO_AUTORIZADO">No autorizado</option>
                            </select>
                        </div>
                    </div>
                    <div class="dashboard-actions filtros-actions mt-6 flex flex-wrap justify-end gap-3 sm:gap-4">
                        <button type="button" id="btnBuscarNotiUsers" class="px-4 sm:px-6 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-medium inline-flex items-center justify-center gap-2">
                            Buscar
                        </button>
                        <button type="button" id="btnLimpiarFiltrosNotiUsers" class="px-4 sm:px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium inline-flex items-center justify-center gap-2">
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <div id="tablaNotiUsersWrapper" class="mb-6 bg-white border rounded-xl sm:rounded-2xl shadow-sm overflow-hidden min-w-0" data-vista-tabla-iconos data-vista="tabla">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    <div class="view-toggle-group flex items-center gap-2 flex-shrink-0">
                        <button type="button" class="view-toggle-btn active text-sm sm:text-base px-3 sm:px-4 py-2 rounded-lg" id="btnViewTablaNotiUsers" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                        <button type="button" class="view-toggle-btn text-sm sm:text-base px-3 sm:px-4 py-2 rounded-lg" id="btnViewIconosNotiUsers" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                    </div>
                    <div id="notiDtControls" class="toolbar-dt-controls flex flex-wrap items-center gap-3"></div>
                    <div id="notiIconosControls" class="toolbar-iconos-controls flex flex-wrap items-center gap-3" style="display:none;"></div>
                </div>
                <div class="view-tarjetas-wrap px-4 sm:px-6 pb-4 overflow-x-hidden" id="viewTarjetasNotiUsers" style="display: none;">
                    <div id="cardsContainerNotiUsers" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                    <div id="cardsPaginationNotiUsers" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3"></div>
                </div>
                <div class="view-lista-wrap table-container overflow-x-auto px-4 sm:px-6 pb-6 pt-4">
                    <div class="table-wrapper overflow-x-auto -webkit-overflow-scrolling-touch">
                        <table id="tablaNotiUsers" class="data-table w-full text-sm config-table">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">N°</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">Codigo</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">Nombre</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">Teléfono</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold">Notificaciones</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold">Opciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyNotiUsers">
                                <tr><td colspan="6" class="py-6 text-center text-gray-500">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="modalEditarNotiUser" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-800">Editar telefono</h3>
                        <button type="button" id="btnCerrarModalEditarNoti" class="w-8 h-8 rounded-lg hover:bg-gray-100 text-gray-600">×</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <input type="hidden" id="editNotiCodigo">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                            <input type="text" id="editNotiUsuario" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-gray-100" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefono (opcional)</label>
                            <input type="text" id="editNotiTelefono" maxlength="15" inputmode="numeric" pattern="[0-9]*"
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: 51987654321">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Permitir que se le envie notificacion</label>
                            <select id="editNotiPermitir" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="1">Autorizado</option>
                                <option value="0">No autorizado</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-5 py-4 border-t border-gray-200 flex flex-wrap justify-end gap-2">
                        <button type="button" id="btnCancelarModalEditarNoti" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</button>
                        <button type="button" id="btnGuardarModalEditarNoti" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Guardar cambios</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="../../../assets/js/i18n/datatables-es.js"></script>
    <script src="../../../assets/js/pagination-iconos.js"></script>
    <script>
        var usuariosTabla = [];
        var tableNotiUsers = null;
        var NOTI_LENGTH_OPTIONS = [20, 25, 50, 100];
        var notiPageLengthSeleccionado = 20;
        var filtroNotificacionesActual = 'TODOS';
        var notiFiltroRegistrado = false;

        function alerta(icon, title, text) {
            if (typeof Swal !== 'undefined') return Swal.fire({ icon: icon, title: title, text: text });
            alert(text);
            return Promise.resolve();
        }

        function fetchPost(params) {
            return fetch('crud_notificaciones_usuarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(params)
            }).then(function(r) { return r.json(); });
        }

        function normalizarPageLengthNoti(valor) {
            var n = parseInt(valor, 10);
            return NOTI_LENGTH_OPTIONS.indexOf(n) >= 0 ? n : 20;
        }

        function sincronizarControlesDtNoti() {
            if (!tableNotiUsers) return;
            var $wrapper = jQuery('#tablaNotiUsers').closest('.dataTables_wrapper');
            var $controls = jQuery('#notiDtControls');
            var $length = $wrapper.find('.dataTables_length').first();
            var $filter = $wrapper.find('.dataTables_filter').first();
            if ($controls.length && $length.length && $filter.length) {
                $controls.empty().append($length, $filter);
            }
        }

        function escHtml(v) {
            return String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        }

        function toggleFiltrosNotiUsers() {
            var contenido = document.getElementById('contenidoFiltrosNotiUsers');
            var icono = document.getElementById('iconoFiltrosNotiUsers');
            if (!contenido || !icono) return;
            contenido.classList.toggle('hidden');
            icono.classList.toggle('rotate-180');
        }

        function renderTablaNoti() {
            var tb = document.getElementById('tbodyNotiUsers');
            if (!tb) return;
            if (!usuariosTabla.length) {
                tb.innerHTML = '<tr><td colspan="6" class="py-6 text-center text-gray-500">No hay usuarios disponibles.</td></tr>';
                renderTarjetasNoti();
                return;
            }
            var html = '';
            usuariosTabla.forEach(function(u, idx) {
                var nro = idx + 1;
                var c = String(u.codigo || '');
                var n = String(u.nombre || '');
                var t = String(u.telefono || '');
                var notificar = parseInt(u.notificar, 10) === 1 ? 1 : 0;
                var estadoTxt = notificar === 1 ? 'Autorizado' : 'No autorizado';
                var estadoBadge = notificar === 1
                    ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Autorizado</span>'
                    : '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">No autorizado</span>';
                html += '<tr class="border-b border-gray-100" data-codigo="' + escHtml(c) + '" data-nombre="' + escHtml(n) + '" data-telefono="' + escHtml(t) + '" data-notificar="' + notificar + '">' +
                        '<td class="px-4 py-3">' + nro + '</td>' +
                        '<td class="px-4 py-3">' + escHtml(c) + '</td>' +
                        '<td class="px-4 py-3">' + escHtml(n) + '</td>' +
                        '<td class="px-4 py-3">' + escHtml(t) + '</td>' +
                        '<td class="px-4 py-3" data-noti-text="' + estadoTxt + '">' + estadoBadge + '</td>' +
                        '<td class="px-4 py-3 text-center"><button type="button" class="btn-editar-noti p-2 text-blue-600 hover:bg-blue-100 rounded-lg" data-codigo="' + escHtml(c) + '" data-nombre="' + escHtml(n) + '" data-telefono="' + escHtml(t) + '" data-notificar="' + notificar + '" title="Editar"><i class="fas fa-edit"></i></button></td>' +
                        '</tr>';
            });
            tb.innerHTML = html;
            renderTarjetasNoti();
        }

        function renderTarjetasNoti() {
            var cont = document.getElementById('cardsContainerNotiUsers');
            if (!cont) return;
            cont.innerHTML = '';
            var rows = document.querySelectorAll('#tbodyNotiUsers tr[data-codigo]');
            Array.prototype.forEach.call(rows, function(tr, idx) {
                var nro = parseInt(String(tr.cells && tr.cells[0] ? tr.cells[0].textContent : ''), 10);
                if (isNaN(nro)) nro = idx + 1;
                var c = String(tr.getAttribute('data-codigo') || '');
                var n = String(tr.getAttribute('data-nombre') || '');
                var t = String(tr.getAttribute('data-telefono') || '');
                var notificar = parseInt(String(tr.getAttribute('data-notificar') || '0'), 10) === 1 ? 1 : 0;
                var estadoTxt = notificar === 1 ? 'Autorizado' : 'No autorizado';
                var card = document.createElement('div');
                card.className = 'card-item';
                card.innerHTML = '<div class="card-numero-row">#' + nro + '</div>' +
                    '<div class="card-row"><span class="label">Codigo:</span> <span>' + escHtml(c) + '</span></div>' +
                    '<div class="card-row"><span class="label">Nombre:</span> <span>' + escHtml(n) + '</span></div>' +
                    '<div class="card-row"><span class="label">Telefo:</span> <span>' + escHtml(t) + '</span></div>' +
                    '<div class="card-row"><span class="label">Notificaciones:</span> <span>' + escHtml(estadoTxt) + '</span></div>' +
                    '<div class="card-acciones">' +
                    '<button type="button" class="btn-editar-noti p-2 text-blue-600 hover:bg-blue-100 rounded-lg" data-codigo="' + escHtml(c) + '" data-nombre="' + escHtml(n) + '" data-telefono="' + escHtml(t) + '" data-notificar="' + notificar + '" title="Editar"><i class="fas fa-edit"></i></button>' +
                    '</div>';
                cont.appendChild(card);
            });
            var pag = document.getElementById('cardsPaginationNotiUsers');
            if (pag) {
                if (tableNotiUsers) {
                    var info = tableNotiUsers.page.info();
                    pag.innerHTML = (typeof buildPaginationIconos === 'function')
                        ? buildPaginationIconos(info)
                        : ('<span>Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros</span>');
                } else {
                    var total = rows.length;
                    pag.innerHTML = total > 0 ? ('<span>Mostrando 1 a ' + total + ' de ' + total + ' registros</span>') : '';
                }
            }
        }

        function aplicarVisibilidadVistaNoti() {
            var wrapper = document.getElementById('tablaNotiUsersWrapper');
            if (!wrapper) return;
            var vista = wrapper.getAttribute('data-vista') || 'tabla';
            var listaWrap = wrapper.querySelector('.view-lista-wrap');
            var tarjetasWrap = document.getElementById('viewTarjetasNotiUsers');
            var btnLista = document.getElementById('btnViewTablaNotiUsers');
            var btnIconos = document.getElementById('btnViewIconosNotiUsers');
            if (listaWrap) listaWrap.style.display = vista === 'tabla' ? 'block' : 'none';
            if (tarjetasWrap) tarjetasWrap.style.display = vista === 'iconos' ? 'block' : 'none';
            if (btnLista) btnLista.classList.toggle('active', vista === 'tabla');
            if (btnIconos) btnIconos.classList.toggle('active', vista === 'iconos');
            $('#notiDtControls').show();
            $('#notiIconosControls').hide();
            sincronizarControlesDtNoti();
        }

        function aplicarFiltroNotificaciones() {
            if (!tableNotiUsers) return;
            tableNotiUsers.draw();
        }

        function inicializarDataTableNoti() {
            if (!(window.jQuery && jQuery.fn && jQuery.fn.DataTable)) {
                tableNotiUsers = null;
                renderTarjetasNoti();
                return;
            }
            if (!notiFiltroRegistrado) {
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    if (!settings || !settings.nTable || settings.nTable.id !== 'tablaNotiUsers') return true;
                    if (filtroNotificacionesActual === 'TODOS') return true;
                    var tr = settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
                    if (!tr) return true;
                    var valor = String(tr.getAttribute('data-notificar') || '0');
                    if (filtroNotificacionesActual === 'AUTORIZADO') return valor === '1';
                    if (filtroNotificacionesActual === 'NO_AUTORIZADO') return valor === '0';
                    return true;
                });
                notiFiltroRegistrado = true;
            }
            if (jQuery.fn.DataTable.isDataTable('#tablaNotiUsers')) {
                tableNotiUsers = jQuery('#tablaNotiUsers').DataTable();
                sincronizarControlesDtNoti();
                renderTarjetasNoti();
                aplicarFiltroNotificaciones();
                return;
            }
            tableNotiUsers = jQuery('#tablaNotiUsers').DataTable({
                pageLength: normalizarPageLengthNoti(notiPageLengthSeleccionado),
                lengthMenu: [[20, 25, 50, 100, -1], [20, 25, 50, 100, 'Todos']],
                language: window.DATATABLES_LANG_ES || {},
                ordering: false,
                columnDefs: [{ orderable: false, targets: [5] }],
                drawCallback: function () {
                    renderTarjetasNoti();
                    sincronizarControlesDtNoti();
                },
                initComplete: function () {
                    sincronizarControlesDtNoti();
                    $('#notiDtControls').show();
                    $('#notiIconosControls').hide();
                    aplicarFiltroNotificaciones();
                }
            });
            sincronizarControlesDtNoti();
            renderTarjetasNoti();
        }

        function cargarNotificados() {
            return fetch('crud_notificaciones_usuarios.php?action=usuarios')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) throw new Error(data.message || 'No se pudo listar usuarios.');
                    if (window.jQuery && jQuery.fn && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#tablaNotiUsers')) {
                        notiPageLengthSeleccionado = normalizarPageLengthNoti(jQuery('#tablaNotiUsers').DataTable().page.len());
                        jQuery('#tablaNotiUsers').DataTable().destroy();
                    }
                    tableNotiUsers = null;
                    usuariosTabla = Array.isArray(data.data) ? data.data : [];
                    renderTablaNoti();
                    inicializarDataTableNoti();
                });
        }

        function abrirModalEditar(codigo, nombre, telefono, notificar) {
            var m = document.getElementById('modalEditarNotiUser');
            if (!m) return;
            document.getElementById('editNotiCodigo').value = String(codigo || '').trim();
            document.getElementById('editNotiUsuario').value = (String(codigo || '').trim() + ' - ' + String(nombre || '').trim()).trim();
            document.getElementById('editNotiTelefono').value = String(telefono || '').trim();
            document.getElementById('editNotiPermitir').value = (parseInt(notificar, 10) === 1 ? '1' : '0');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function cerrarModalEditar() {
            var m = document.getElementById('modalEditarNotiUser');
            if (!m) return;
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        function guardarDesdeModal() {
            var codigo = String((document.getElementById('editNotiCodigo') || {}).value || '').trim();
            var telefono = String((document.getElementById('editNotiTelefono') || {}).value || '').replace(/\D/g, '');
            var notificar = parseInt(String((document.getElementById('editNotiPermitir') || {}).value || '0'), 10) === 1 ? 1 : 0;
            if (!codigo) { alerta('warning', 'Atencion', 'Codigo de usuario invalido.'); return; }
            if (telefono !== '' && !/^\d{9,15}$/.test(telefono)) { alerta('warning', 'Atencion', 'Si ingresa telefono debe tener entre 9 y 15 digitos.'); return; }
            fetchPost({ action: 'update', codigo: codigo, telefono: telefono, notificar: String(notificar) }).then(function(res) {
                if (!res.success) return alerta('error', 'Error', res.message || 'No se pudo actualizar.');
                return alerta('success', 'Actualizado', res.message || 'Telefono actualizado.').then(function() {
                    cerrarModalEditar();
                    return cargarNotificados();
                });
            }).catch(function(err) { alerta('error', 'Error', err.message || 'Error de red.'); });
        }

        document.getElementById('tbodyNotiUsers').addEventListener('click', function(ev) {
            var btn = ev.target.closest('.btn-editar-noti');
            if (!btn) return;
            abrirModalEditar(
                btn.getAttribute('data-codigo') || '',
                btn.getAttribute('data-nombre') || '',
                btn.getAttribute('data-telefono') || '',
                btn.getAttribute('data-notificar') || '0'
            );
        });

        document.getElementById('cardsContainerNotiUsers').addEventListener('click', function(ev) {
            var btn = ev.target.closest('.btn-editar-noti');
            if (!btn) return;
            abrirModalEditar(
                btn.getAttribute('data-codigo') || '',
                btn.getAttribute('data-nombre') || '',
                btn.getAttribute('data-telefono') || '',
                btn.getAttribute('data-notificar') || '0'
            );
        });

        document.getElementById('btnCerrarModalEditarNoti').addEventListener('click', cerrarModalEditar);
        document.getElementById('btnCancelarModalEditarNoti').addEventListener('click', cerrarModalEditar);
        document.getElementById('btnGuardarModalEditarNoti').addEventListener('click', guardarDesdeModal);
        document.getElementById('btnToggleFiltrosNotiUsers').addEventListener('click', toggleFiltrosNotiUsers);
        document.getElementById('btnBuscarNotiUsers').addEventListener('click', function() {
            var sel = document.getElementById('filtroNotificaciones');
            filtroNotificacionesActual = String((sel && sel.value) || 'TODOS');
            aplicarFiltroNotificaciones();
        });
        document.getElementById('btnLimpiarFiltrosNotiUsers').addEventListener('click', function() {
            var sel = document.getElementById('filtroNotificaciones');
            if (sel) sel.value = 'TODOS';
            filtroNotificacionesActual = 'TODOS';
            aplicarFiltroNotificaciones();
        });
        document.getElementById('modalEditarNotiUser').addEventListener('click', function(ev) {
            if (ev.target === this) cerrarModalEditar();
        });

        var btnViewTablaNotiUsers = document.getElementById('btnViewTablaNotiUsers');
        var btnViewIconosNotiUsers = document.getElementById('btnViewIconosNotiUsers');
        var tablaNotiUsersWrapper = document.getElementById('tablaNotiUsersWrapper');
        sincronizarControlesDtNoti();
        if (btnViewTablaNotiUsers) btnViewTablaNotiUsers.addEventListener('click', function() {
            if (tablaNotiUsersWrapper) tablaNotiUsersWrapper.setAttribute('data-vista', 'tabla');
            aplicarVisibilidadVistaNoti();
        });
        if (btnViewIconosNotiUsers) btnViewIconosNotiUsers.addEventListener('click', function() {
            if (tablaNotiUsersWrapper) tablaNotiUsersWrapper.setAttribute('data-vista', 'iconos');
            aplicarVisibilidadVistaNoti();
        });
        if (tablaNotiUsersWrapper) tablaNotiUsersWrapper.setAttribute('data-vista', (window.innerWidth < 768 ? 'iconos' : 'tabla'));
        aplicarVisibilidadVistaNoti();

        cargarNotificados().catch(function(err) {
            alerta('error', 'Error', err.message || 'No se pudo cargar la informacion inicial.');
        });
    </script>
</body>
</html>

