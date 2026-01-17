<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) {
            window.top.location.href = "../../login.php";
        } else {
            window.location.href = "../../login.php";
        }
    </script>';
    exit();
}

// Conexión (para poblar filtros)
include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Reportes</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap 5 (modal correo) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">

    <!-- DataTables + jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .table-wrapper {
            overflow-x: auto;
            overflow-y: visible;
            width: 100%;
            border-radius: 1rem;
        }

        .data-table {
            width: 100% !important;
            border-collapse: collapse;
            min-width: 1400px;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem 1rem;
            text-align: center;
            font-size: 0.875rem;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .data-table th {
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
            font-weight: 600;
            color: #ffffff !important;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .dataTables_wrapper {
            overflow-x: visible !important;
        }

        /* Separación del header de DataTables ("Show ...", buscador, etc.) */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding-top: 1rem;
            padding-left: 1.25rem;
            padding-right: 1.25rem;
        }
    </style>

</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-10">
        <div class="max-w-7xl mx-auto">
            <!-- Filtros -->
            <div class="mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🔎</span>
                        <h3 class="text-base font-semibold text-gray-800">Filtros de búsqueda</h3>
                    </div>
                </div>
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                            <input type="date" id="filtroFechaInicio" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                            <input type="date" id="filtroFechaFin" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Laboratorio</label>
                            <select id="filtroLaboratorio" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="">Seleccionar</option>
                                <?php
                                $sql = "SELECT codigo, nombre FROM san_dim_laboratorio ORDER BY nombre ASC";
                                $res = $conexion->query($sql);
                                if ($res && $res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($row['nombre']) . '">' . htmlspecialchars($row['nombre']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo muestra</label>
                            <select id="filtroTipoMuestra" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="">Seleccionar</option>
                                <?php
                                $sql = "SELECT codigo, nombre FROM san_dim_tipo_muestra ORDER BY nombre ASC";
                                $res = $conexion->query($sql);
                                if ($res && $res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($row['nombre']) . '">' . htmlspecialchars($row['nombre']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap justify-end gap-4">
                        <button type="button" id="btnFiltrar" class="px-6 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Filtrar
                        </button>
                        <button type="button" id="btnLimpiar" class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200">
                            Limpiar
                        </button>
                        <button type="button" class="px-6 py-2.5 text-white font-medium rounded-lg transition inline-flex items-center gap-2"
                            onclick="exportarReporteExcel()"
                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);">
                            📊 Exportar a Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="max-w-full mx-auto mt-6">
                <div class="border border-gray-300 rounded-2xl bg-white overflow-hidden">
                    <div class="table-wrapper">
                        <table id="tablaReportes" class="data-table display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Cod. Envío</th>
                                    <th>Fecha Envio</th>
                                    <th>Nom. Lab</th>
                                    <th>Nom. EmpTrans</th>
                                    <th>Usuario Registrador</th>
                                    <th>Usuario Responsable</th>
                                    <th>Autorizado Por</th>
                                    <th>Muestra</th>
                                    <th>Analisis</th>
                                    <th>Obs</th>
                                    <th>Opciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-12">
                <p class="text-gray-500 text-sm">
                    Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> -
                    © <span id="currentYear"></span>
                </p>
            </div>
        </div>
    </div>
    <!-- Modal Bootstrap -->
    <div class="modal fade" id="modalCorreo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="max-height: 85vh; display: flex; flex-direction: column;">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar reporte por correo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body flex-grow-1 overflow-auto">
                    <input type="hidden" id="codigoEnvio" value="">

                    <!-- DESTINATARIOS -->
                    <div class="mb-3">
                        <label class="form-label">Para (destinatarios) *</label>
                        <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center"
                            id="btnMostrarSelect">
                            <i class="fas fa-plus me-1"></i> Seleccionar contactos
                        </button>

                        <!-- Select oculto con contactos -->
                        <select id="destinatarioSelect" multiple class="form-select mt-2" size="6"
                            style="display:none;"></select>

                        <!-- Lista de destinatarios elegidos -->
                        <div id="listaPara" class="mt-2 small"></div>
                    </div>

                    <!-- ASUNTO Y MENSAJE -->
                    <div class="mb-3">
                        <label class="form-label">Asunto *</label>
                        <input type="text" id="asuntoCorreo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mensaje *</label>
                        <textarea id="mensajeCorreo" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- ARCHIVOS -->
                    <div class="mt-4">
                        <p class="fw-bold mb-2">Archivos adjuntos:</p>
                        <div id="listaArchivos" class="mb-3"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Agregar más archivos</label>
                        <input type="file" id="archivosAdjuntos" multiple class="form-control">
                    </div>

                    <div id="mensajeResultado" class="text-center small min-vh-25"></div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="enviarCorreoDesdeSistema()">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Advertencia - No se puede editar -->
    <div id="modalAdvertenciaEdicion" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="bg-red-50 border-b border-red-200 px-6 py-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h5 class="text-lg font-semibold text-red-700" id="modalAdvertenciaEdicionLabel">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        No se puede editar este envío
                    </h5>
                    <button type="button" onclick="cerrarModalAdvertencia()" class="text-red-500 hover:text-red-700 text-2xl leading-none transition">
                        ×
                    </button>
                </div>
            </div>
            <div class="px-6 py-4">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fas fa-ban text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-700 mb-3">
                            Este envío no puede ser editado por las siguientes razones:
                        </p>
                        <ul id="listaRazones" class="list-disc list-inside space-y-2 text-gray-600">
                            <!-- Las razones se cargarán aquí -->
                        </ul>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end">
                <button type="button" onclick="cerrarModalAdvertencia()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition duration-200">
                    Entendido
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Edición (copiado/adaptado desde Seguimiento; NO navega fuera de Reportes) -->
    <div id="modalEditarEnvio" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-7xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="bg-blue-50 px-6 py-4 border-b border-blue-200">
                <div class="flex items-center justify-between">
                    <h5 class="text-lg font-semibold text-gray-800" id="modalEditarEnvioLabel">Editar Envío</h5>
                    <button type="button" onclick="cerrarModalEditar()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition">
                        ×
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <!-- Formulario -->
                <form id="formEditarEnvio">
                    <!-- INFORMACIÓN DE REGISTRO Y ENVÍO -->
                    <div class="form-section mb-6">
                        <div class="dual-group-container grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
                            <!-- GRUPO 1: Datos de Envío -->
                            <div class="field-group border border-gray-300 rounded-2xl p-5 bg-white">
                                <div class="group-header text-xs font-bold text-blue-600 uppercase tracking-wide pb-2 mb-4">
                                    Datos de Envío
                                </div>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Fecha de Envío <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" id="fechaEnvio" name="fechaEnvio" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Hora <span class="text-red-500">*</span>
                                            </label>
                                            <input type="time" id="horaEnvio" name="horaEnvio" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Código de Envío <span class="text-red-500">*</span>
                                            </label>
                                            <!-- IMPORTANTE: en Reportes ya existe un #codigoEnvio para el modal de correo -->
                                            <input type="text" id="codigoEnvioEdit" readonly
                                                class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg font-bold text-blue-600 focus:outline-none text-sm">
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Laboratorio <span class="text-red-500">*</span>
                                            </label>
                                            <select id="laboratorio" name="laboratorio"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm">
                                                <option value="">Seleccionar...</option>
                                                <?php
                                                $query = "SELECT codigo, nombre FROM san_dim_laboratorio ORDER BY nombre";
                                                $result = mysqli_query($conexion, $query);
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo '<option value="' . htmlspecialchars($row['codigo']) . '">' . htmlspecialchars($row['nombre']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- GRUPO 2: Transporte y Responsables -->
                            <div class="field-group border border-gray-300 rounded-2xl p-5 bg-white">
                                <div class="group-header text-xs font-bold text-blue-600 uppercase tracking-wide pb-2 mb-4">
                                    Transporte y responsables
                                </div>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Empresa de Transporte <span class="text-red-500">*</span>
                                            </label>
                                            <select name="empresa_transporte" id="empresa_transporte"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm">
                                                <option value="">Seleccionar...</option>
                                                <?php
                                                $query = "SELECT codigo, nombre FROM san_dim_emptrans ORDER BY nombre";
                                                $result = mysqli_query($conexion, $query);
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo '<option value="' . htmlspecialchars($row['codigo']) . '">' . htmlspecialchars($row['nombre']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Autorizado por <span class="text-red-500">*</span>
                                            </label>
                                            <input name="autorizado_por" id="autorizado_por" type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Usuario registrador</label>
                                            <input name="usuario_registrador" id="usuario_registrador" type="text" readonly
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 focus:outline-none text-sm">
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Usuario responsable <span class="text-red-500">*</span>
                                            </label>
                                            <input name="usuario_responsable" id="usuario_responsable" type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Número de Solicitudes -->
                        <div class="form-field max-w-xs">
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Número de Solicitudes <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="numeroSolicitudes" name="numeroSolicitudes" min="1"
                                max="30" placeholder="Ingrese cantidad"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>

                    <!-- DETALLES DE SOLICITUDES -->
                    <div class="form-section mt-6">
                        <h6 class="font-bold text-gray-700 mb-3">Detalles de las Solicitudes</h6>
                        <div id="tablaSolicitudes" class="space-y-4">
                            <!-- dinámico -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" onclick="cerrarModalEditar()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition duration-200">
                    Cancelar
                </button>
                <button type="button" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-200" id="btnGuardarEdicion">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>
   
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>

    <script>
        let tableReportes;

        function exportarReporteExcel() {
            const params = new URLSearchParams();
            const fechaInicio = $('#filtroFechaInicio').val();
            const fechaFin = $('#filtroFechaFin').val();
            const laboratorio = $('#filtroLaboratorio').val();
            const muestra = $('#filtroTipoMuestra').val();

            if (fechaInicio) params.set('fechaInicio', fechaInicio);
            if (fechaFin) params.set('fechaFin', fechaFin);
            if (laboratorio) params.set('laboratorio', laboratorio);
            if (muestra) params.set('muestra', muestra);

            window.location.href = 'exportar_excel_resultados.php?' + params.toString();
        }

        // ====== EDICIÓN (copiado/adaptado desde Seguimiento) ======
        function abrirModalAdvertencia() {
            const modal = document.getElementById('modalAdvertenciaEdicion');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalAdvertencia() {
            const modal = document.getElementById('modalAdvertenciaEdicion');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        function abrirModalEditar() {
            const modal = document.getElementById('modalEditarEnvio');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalEditar() {
            const modal = document.getElementById('modalEditarEnvio');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        // Cerrar modales con ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                cerrarModalAdvertencia();
                cerrarModalEditar();
            }
        });

        // Cerrar modales al hacer clic fuera
        document.addEventListener('click', function (e) {
            const modalAdvertencia = document.getElementById('modalAdvertenciaEdicion');
            const modalEditar = document.getElementById('modalEditarEnvio');

            if (e.target === modalAdvertencia) cerrarModalAdvertencia();
            if (e.target === modalEditar) cerrarModalEditar();
        });

        let currentSolicitudCount = 0;
        let datosOriginales = { cabecera: null, detalles: {} };

        async function verificarYEditar(codEnvio) {
            try {
                const res = await fetch(`../seguimiento/verificar_editable.php?codEnvio=${encodeURIComponent(codEnvio)}`);
                const data = await res.json();

                if (data.error) {
                    alert('Error al verificar: ' + data.error);
                    return;
                }

                if (!data.puedeEditar) {
                    const listaRazones = document.getElementById('listaRazones');
                    listaRazones.innerHTML = '';
                    (data.razones || []).forEach(razon => {
                        const li = document.createElement('li');
                        li.textContent = razon;
                        li.className = 'text-red-600 font-medium';
                        listaRazones.appendChild(li);
                    });
                    abrirModalAdvertencia();
                    return;
                }

                editarRegistro(codEnvio);
            } catch (err) {
                console.error('Error al verificar si se puede editar:', err);
                alert('Error al verificar si se puede editar el envío');
            }
        }

        function editarRegistro(codEnvio) {
            abrirModalEditar();
            $('#tablaSolicitudes').empty();
            datosOriginales = { cabecera: null, detalles: {} };

            // 1) Cabecera
            fetch(`../seguimiento/get_cabecera_envio.php?codEnvio=${encodeURIComponent(codEnvio)}`)
                .then(res => res.json())
                .then(cab => {
                    if (cab.error) throw new Error(cab.error);

                    datosOriginales.cabecera = {
                        codEnvio: cab.codEnvio,
                        fecEnvio: cab.fecEnvio,
                        horaEnvio: cab.horaEnvio,
                        codLab: cab.codLab,
                        codEmpTrans: cab.codEmpTrans,
                        usuarioRegistrador: cab.usuarioRegistrador,
                        usuarioResponsable: cab.usuarioResponsable,
                        autorizadoPor: cab.autorizadoPor
                    };

                    document.getElementById('codigoEnvioEdit').value = cab.codEnvio;
                    document.getElementById('fechaEnvio').value = cab.fecEnvio;
                    document.getElementById('horaEnvio').value = cab.horaEnvio;
                    document.getElementById('laboratorio').value = cab.codLab;
                    document.getElementById('empresa_transporte').value = cab.codEmpTrans;
                    document.getElementById('usuario_registrador').value = cab.usuarioRegistrador;
                    document.getElementById('usuario_responsable').value = cab.usuarioResponsable;
                    document.getElementById('autorizado_por').value = cab.autorizadoPor;
                })
                .catch(err => {
                    console.error(err);
                    alert('Error al cargar la cabecera: ' + err.message);
                });

            // 2) Detalles
            fetch(`../seguimiento/get_detalles_envio.php?codEnvio=${encodeURIComponent(codEnvio)}`)
                .then(res => res.json())
                .then(det => {
                    if (det.error) throw new Error(det.error);

                    const total = det.length > 0 ? Math.max(...det.map(d => d.posSolicitud)) : 0;
                    document.getElementById('numeroSolicitudes').value = total;

                    const grupos = {};
                    det.forEach(item => {
                        if (!grupos[item.posSolicitud]) grupos[item.posSolicitud] = [];
                        grupos[item.posSolicitud].push(item);
                    });

                    Object.keys(grupos).forEach(pos => {
                        datosOriginales.detalles[pos] = grupos[pos].map(item => ({
                            codMuestra: item.codMuestra,
                            nomMuestra: item.nomMuestra,
                            codRef: item.codRef,
                            fecToma: item.fecToma,
                            numMuestras: item.numMuestras,
                            obs: item.obs || '',
                            codAnalisis: item.codAnalisis,
                            nomAnalisis: item.nomAnalisis,
                            codPaquete: item.codPaquete || null,
                            nomPaquete: item.nomPaquete || null
                        }));
                    });

                    renderizarFilasDeSolicitudes(grupos);
                    currentSolicitudCount = total;
                })
                .catch(err => {
                    console.error(err);
                    alert('Error al cargar los detalles: ' + err.message);
                });
        }

        function renderizarFilasDeSolicitudes(grupos) {
            const contenedor = document.getElementById('tablaSolicitudes');
            contenedor.innerHTML = '';

            let tiposMuestraCache = null;
            async function getTiposMuestra() {
                if (tiposMuestraCache) return tiposMuestraCache;
                const res = await fetch('../../includes/get_tipos_muestra.php');
                tiposMuestraCache = await res.json();
                return tiposMuestraCache;
            }

            for (const pos in grupos) {
                const items = grupos[pos];
                const primerItem = items[0];

                const analisisIniciales = items.map(item => ({
                    codigo: item.codAnalisis,
                    nombre: item.nomAnalisis,
                    paquete_codigo: item.codPaquete || null,
                    paquete_nombre: item.nomPaquete || null
                }));

                const div = document.createElement('div');
                div.id = `fila-solicitud-${pos}`;
                div.className = 'border rounded-lg p-4 bg-gray-50';
                div.setAttribute('data-analisis', JSON.stringify(analisisIniciales));
                div.innerHTML = `
                    <h6 class="font-bold mb-3">Solicitud #${pos}</h6>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                        <div>
                            <label class="text-xs text-gray-600">Tipo de muestra</label>
                            <select class="w-full text-sm px-2 py-1 border rounded tipo-muestra" data-pos="${pos}">
                                <option value="">Seleccionar...</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Cód. Referencia</label>
                            <input type="text" class="w-full text-sm px-2 py-1 border rounded cod-ref"
                                value="${primerItem.codRef || ''}" data-pos="${pos}">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Núm. Muestras</label>
                            <select class="w-full text-sm px-2 py-1 border rounded num-muestras" data-pos="${pos}">
                                ${Array.from({ length: 30 }, (_, i) => `<option value="${i + 1}" ${primerItem.numMuestras == i + 1 ? 'selected' : ''}>${i + 1}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Fecha Toma</label>
                            <input type="date" class="w-full text-sm px-2 py-1 border rounded fecha-toma"
                                value="${primerItem.fecToma || ''}" data-pos="${pos}">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="text-xs text-gray-600">Observaciones</label>
                        <textarea class="w-full text-sm px-2 py-1 border rounded obs" data-pos="${pos}" rows="2">${primerItem.obs || ''}</textarea>
                    </div>
                    <button type="button" class="px-4 py-2 text-sm font-medium rounded-lg border-2 border-sky-400 bg-white text-sky-500 hover:bg-sky-500 hover:text-white transition duration-200 ver-analisis-toggle" data-pos="${pos}">
                        <span class="toggle-text">Ver Análisis</span>
                    </button>
                    <div class="mt-3 analisis-container hidden" id="analisis-container-${pos}"></div>
                `;
                contenedor.appendChild(div);

                getTiposMuestra().then(tipos => {
                    const select = div.querySelector('.tipo-muestra');
                    (tipos || []).forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.codigo;
                        opt.textContent = t.nombre;
                        if (t.codigo == primerItem.codMuestra) opt.selected = true;
                        select.appendChild(opt);
                    });
                });

                if (analisisIniciales.length > 0) {
                    cargarAnalisisEnContenedor(pos, primerItem.codMuestra, analisisIniciales, div);
                }

                div.querySelector('.ver-analisis-toggle').addEventListener('click', async function () {
                    const posActual = this.dataset.pos;
                    const tipoId = div.querySelector('.tipo-muestra').value;
                    if (!tipoId) {
                        alert('Seleccione primero el tipo de muestra');
                        return;
                    }

                    const container = document.getElementById(`analisis-container-${posActual}`);
                    const toggleText = this.querySelector('.toggle-text');

                    if (container.classList.contains('hidden')) {
                        if (container.innerHTML.trim() === '' || container.innerHTML.includes('Cargando')) {
                            container.innerHTML = '<p>Cargando análisis...</p>';
                            await cargarAnalisisEnContenedor(posActual, tipoId, null, div);
                        }
                        container.classList.remove('hidden');
                        toggleText.textContent = 'Ocultar Análisis';
                    } else {
                        container.classList.add('hidden');
                        toggleText.textContent = 'Ver Análisis';
                    }
                });
            }
        }

        async function cargarAnalisisEnContenedor(pos, tipoId, analisisIniciales, filaDiv) {
            const container = document.getElementById(`analisis-container-${pos}`);
            try {
                const res = await fetch(`../../includes/get_config_muestra.php?tipo=${encodeURIComponent(tipoId)}`);
                const data = await res.json();
                if (data.error) throw new Error(data.error);

                if (filaDiv && data.tipo_muestra && data.tipo_muestra.longitud_codigo) {
                    filaDiv.setAttribute('data-longitud-codigo', data.tipo_muestra.longitud_codigo);
                }

                const analisisPorPaquete = {};
                const sinPaquete = [];
                (data.analisis || []).forEach(a => {
                    if (a.paquete) {
                        if (!analisisPorPaquete[a.paquete]) analisisPorPaquete[a.paquete] = [];
                        analisisPorPaquete[a.paquete].push(a);
                    } else {
                        sinPaquete.push(a);
                    }
                });

                let codigosSeleccionados = new Set();
                if (analisisIniciales) {
                    codigosSeleccionados = new Set(analisisIniciales.map(a => String(a.codigo)));
                } else {
                    const analisisData = filaDiv.getAttribute('data-analisis');
                    if (analisisData) {
                        const analisis = JSON.parse(analisisData);
                        codigosSeleccionados = new Set(analisis.map(a => String(a.codigo)));
                    }
                }

                let html = '';

                (data.paquetes || []).forEach(p => {
                    const analisisDelPaquete = analisisPorPaquete[p.codigo] || [];
                    const todosSel = analisisDelPaquete.length > 0 && analisisDelPaquete.every(a => codigosSeleccionados.has(String(a.codigo)));
                    html += `
                        <div class="mb-4">
                            <div class="flex items-center mb-2">
                                <input class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 paquete-check" type="checkbox"
                                    data-pos="${pos}" data-paquete="${p.codigo}" ${todosSel ? 'checked' : ''}>
                                <label class="ml-2 text-sm font-bold text-gray-700">${p.nombre}</label>
                            </div>
                            <div class="ml-6 mt-2 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                ${analisisDelPaquete.map(a => `
                                    <div class="flex items-center">
                                        <input class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 analisis-check" type="checkbox"
                                            data-pos="${pos}" data-paquete="${p.codigo}" value="${a.codigo}"
                                            data-nombre="${a.nombre}"
                                            data-paquete-nombre="${p.nombre}"
                                            ${codigosSeleccionados.has(String(a.codigo)) ? 'checked' : ''}>
                                        <label class="ml-2 text-sm text-gray-700">${a.nombre}</label>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                });

                if (sinPaquete.length > 0) {
                    html += `<div class="mt-4 pt-4 border-t border-gray-300">
                        <strong class="text-sm font-bold text-gray-700 mb-2 block">Otros análisis:</strong>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    `;
                    html += sinPaquete.map(a => `
                        <div class="flex items-center">
                            <input class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 analisis-check" type="checkbox"
                                data-pos="${pos}" value="${a.codigo}"
                                data-nombre="${a.nombre}"
                                ${codigosSeleccionados.has(String(a.codigo)) ? 'checked' : ''}>
                            <label class="ml-2 text-sm text-gray-700">${a.nombre}</label>
                        </div>
                    `).join('');
                    html += '</div></div>';
                }

                container.innerHTML = html;

                container.querySelectorAll('.analisis-check').forEach(cb => {
                    cb.addEventListener('change', function () {
                        actualizarAnalisisEnFila(pos);
                    });
                });

                container.querySelectorAll('.paquete-check').forEach(cb => {
                    cb.addEventListener('change', function () {
                        const paqueteId = this.dataset.paquete;
                        const pos = this.dataset.pos;
                        const checks = container.querySelectorAll(`.analisis-check[data-pos="${pos}"][data-paquete="${paqueteId}"]`);
                        checks.forEach(c => c.checked = this.checked);
                        actualizarAnalisisEnFila(pos);
                    });
                });

                actualizarAnalisisEnFila(pos);
            } catch (err) {
                console.error(err);
                container.innerHTML = `<div class="text-red-600">Error: ${err.message}</div>`;
            }
        }

        function actualizarAnalisisEnFila(pos) {
            const fila = document.getElementById(`fila-solicitud-${pos}`);
            if (!fila) return;

            const analisisSeleccionados = [];
            const container = document.getElementById(`analisis-container-${pos}`);

            if (container) {
                container.querySelectorAll('.analisis-check:checked').forEach(cb => {
                    analisisSeleccionados.push({
                        codigo: cb.value,
                        nombre: cb.dataset.nombre || cb.nextElementSibling.textContent.trim(),
                        paquete_codigo: cb.dataset.paquete || null,
                        paquete_nombre: cb.dataset.paqueteNombre || null
                    });
                });
            }

            fila.setAttribute('data-analisis', JSON.stringify(analisisSeleccionados));
        }

        // Guardar cambios
        document.getElementById('btnGuardarEdicion').addEventListener('click', async function () {
            const errores = [];

            const fixedFields = [
                { id: 'fechaEnvio', name: 'Fecha de envío' },
                { id: 'horaEnvio', name: 'Hora de envío' },
                { id: 'laboratorio', name: 'Laboratorio' },
                { id: 'empresa_transporte', name: 'Empresa de transporte' },
                { id: 'autorizado_por', name: 'Autorizado por' },
                { id: 'usuario_responsable', name: 'Usuario responsable' }
            ];

            for (const { id, name } of fixedFields) {
                const el = document.getElementById(id);
                if (!el?.value?.trim()) errores.push(`- ${name} es obligatorio.`);
            }

            const numeroSolicitudes = parseInt(document.getElementById('numeroSolicitudes').value) || 0;
            if (numeroSolicitudes < 1) errores.push('- Debe haber al menos una solicitud.');

            const filas = document.querySelectorAll('#tablaSolicitudes > div[id^="fila-solicitud-"]');
            if (filas.length === 0) errores.push('- Debe haber al menos una solicitud.');

            const filasOrdenadas = Array.from(filas).sort((a, b) => {
                const posA = parseInt(a.id.split('-').pop());
                const posB = parseInt(b.id.split('-').pop());
                return posA - posB;
            });

            filasOrdenadas.forEach((fila) => {
                const pos = parseInt(fila.id.split('-').pop());
                const prefix = `Solicitud #${pos}:`;

                const tipoMuestra = fila.querySelector('.tipo-muestra')?.value?.trim();
                const fechaToma = fila.querySelector('.fecha-toma')?.value?.trim();
                const codRef = fila.querySelector('.cod-ref')?.value?.trim();

                if (!tipoMuestra) errores.push(`${prefix} Tipo de muestra es obligatorio.`);
                if (!fechaToma) errores.push(`${prefix} Fecha de toma es obligatoria.`);
                if (!codRef) errores.push(`${prefix} Código de referencia es obligatorio.`);

                const longitudCodigo = fila.getAttribute('data-longitud-codigo');
                if (longitudCodigo && codRef) {
                    const longitudRequerida = parseInt(longitudCodigo);
                    if (!isNaN(longitudRequerida) && codRef.length !== longitudRequerida) {
                        errores.push(`${prefix} El código de referencia debe tener exactamente ${longitudRequerida} caracteres (actual: ${codRef.length}).`);
                    }
                }

                const analisisData = fila.getAttribute('data-analisis');
                let analisisSeleccionados = [];
                try {
                    analisisSeleccionados = analisisData ? JSON.parse(analisisData) : [];
                } catch (e) {
                    console.error('Error al parsear análisis:', e);
                }

                if (analisisSeleccionados.length === 0) {
                    errores.push(`${prefix} Debe seleccionar al menos un análisis.`);
                }
            });

            if (errores.length > 0) {
                alert("❌ Por favor, corrija los siguientes errores:\n\n" + errores.join('\n'));
                return;
            }

            const codEnvio = document.getElementById('codigoEnvioEdit').value;
            const formData = new FormData();

            const fields = [
                'fechaEnvio', 'horaEnvio', 'laboratorio', 'empresa_transporte',
                'usuario_registrador', 'usuario_responsable', 'autorizado_por'
            ];
            fields.forEach(f => formData.append(f, document.getElementById(f)?.value || ''));
            // El backend espera este key:
            formData.append('codigoEnvio', codEnvio);

            const solicitudesAEnviar = filasOrdenadas.map(f => parseInt(f.id.split('-').pop()));

            for (let i = 0; i < solicitudesAEnviar.length; i++) {
                const pos = solicitudesAEnviar[i];
                const fila = document.getElementById(`fila-solicitud-${pos}`);
                if (!fila) continue;

                const tipoMuestraEl = fila.querySelector('.tipo-muestra');
                const nombreTipoMuestra = tipoMuestraEl?.selectedOptions[0]?.text || '';
                const codRefEl = fila.querySelector('.cod-ref');
                const numMuestrasEl = fila.querySelector('.num-muestras');
                const fechaTomaEl = fila.querySelector('.fecha-toma');
                const obsEl = fila.querySelector('.obs');

                const analisisData = fila.getAttribute('data-analisis');
                let analisisSeleccionados = [];
                try {
                    analisisSeleccionados = analisisData ? JSON.parse(analisisData) : [];
                } catch (e) {
                    console.error('Error al parsear análisis:', e);
                }

                const indice = i + 1;
                formData.append(`fechaToma_${indice}`, fechaTomaEl?.value || '');
                formData.append(`tipoMuestra_${indice}`, tipoMuestraEl?.value || '');
                formData.append(`tipoMuestraNombre_${indice}`, nombreTipoMuestra || '');
                formData.append(`codigoReferenciaValue_${indice}`, codRefEl?.value || '');
                formData.append(`numeroMuestras_${indice}`, numMuestrasEl?.value || '1');
                formData.append(`observaciones_${indice}`, obsEl?.value || '');
                formData.append(`analisis_completos_${indice}`, JSON.stringify(analisisSeleccionados));
                formData.append(`posSolicitud_original_${indice}`, pos);
            }

            formData.append('numeroSolicitudes', solicitudesAEnviar.length);

            try {
                const res = await fetch('../seguimiento/actualizar_muestra.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert('¡Cambios guardados exitosamente!');
                    cerrarModalEditar();
                    if (tableReportes) tableReportes.ajax.reload();
                } else {
                    alert('Error: ' + (data.error || 'No se pudo guardar'));
                }
            } catch (err) {
                console.error(err);
                alert('Error de red al guardar');
            }
        });

        function borrarRegistroDesdeListado(codEnvio) {
            if (!confirm(`¿Eliminar el envío "${codEnvio}"?\n\nEsta acción no se puede deshacer.`)) return;

            fetch('../seguimiento/borrarSolicitudCompleto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'codEnvio=' + encodeURIComponent(codEnvio)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Envío eliminado correctamente');
                        if (tableReportes) tableReportes.ajax.reload();
                    } else {
                        alert('❌ Error: ' + (data.message || 'No se pudo eliminar'));
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('❌ Error de conexión');
                });
        }

        function cargarTablaReportes() {
            if (tableReportes) tableReportes.destroy();

            const fechaInicio = $('#filtroFechaInicio').val();
            const fechaFin = $('#filtroFechaFin').val();
            const laboratorio = $('#filtroLaboratorio').val();
            const muestra = $('#filtroTipoMuestra').val();

            tableReportes = $('#tablaReportes').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                autoWidth: false,
                ajax: {
                    url: '../seguimiento/listar_cab_filtros.php',
                    type: 'POST',
                    data: {
                        fechaInicio,
                        fechaFin,
                        laboratorio,
                        muestra,
                        granjas: [],
                        galpon: '',
                        edadDesde: '',
                        edadHasta: ''
                    }
                },
                columns: [
                    { data: 'codEnvio' },
                    { data: 'fecEnvio' },
                    { data: 'nomLab' },
                    { data: 'nomEmpTrans' },
                    { data: 'usuarioRegistrador' },
                    { data: 'usuarioResponsable' },
                    { data: 'autorizadoPor' },
                    { data: 'muestras', defaultContent: '—' },
                    { data: 'analisis', defaultContent: '—' },
                    {
                        data: 'obs',
                        defaultContent: '',
                        render: function (data) {
                            if (!data) return '<span class="text-gray-400 italic">—</span>';
                            return data;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function (data, type, row) {
                            const cod = encodeURIComponent(row.codEnvio);
                            return `
                                <div class="flex items-center justify-center gap-3">
                                    <a class="text-red-600 hover:text-red-800" title="PDF Tabla" target="_blank"
                                        href="generar_pdf_tabla.php?codigo=${cod}">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                    <a class="text-red-600 hover:text-red-800" title="PDF Resumen" target="_blank"
                                        href="generar_pdf_resumen.php?codigo=${cod}">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </a>
                                    <button class="btn-enviar-correo text-blue-600 hover:text-blue-800" title="Enviar correo" data-codigo="${row.codEnvio}">
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                    <a class="text-slate-700 hover:text-slate-900" title="QR" target="_blank"
                                        href="generar_qr_etiqueta.php?codigo=${cod}">
                                        <i class="fa-solid fa-qrcode"></i>
                                    </a>
                                    <button class="text-indigo-600 hover:text-indigo-800" title="Editar"
                                        onclick="verificarYEditar('${row.codEnvio}')">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <button class="text-rose-600 hover:text-rose-800" title="Eliminar"
                                        onclick="borrarRegistroDesdeListado('${row.codEnvio}')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                pageLength: 10,
                lengthMenu: [[10, 25, 50], [10, 25, 50]]
            });
        }

        $(document).ready(function () {
            cargarTablaReportes();

            $('#btnFiltrar').click(function () {
                cargarTablaReportes();
            });

            $('#btnLimpiar').click(function () {
                $('#filtroFechaInicio').val('');
                $('#filtroFechaFin').val('');
                $('#filtroLaboratorio').val('');
                $('#filtroTipoMuestra').val('');
                cargarTablaReportes();
            });

        });
    </script>

    <!-- JS existente para modal de correo (reutilizado) -->
    <script src="../../assets/js/reportes/reportes.js"></script>
</body>

</html>