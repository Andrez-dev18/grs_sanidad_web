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

$codigoUsuario = $_SESSION['usuario'] ?? '';
$rolReportes = 'user';
if ($codigoUsuario) {
    $sqlRol = "SELECT rol_sanidad FROM usuario WHERE codigo = ?";
    $stmtRol = $conexion->prepare($sqlRol);
    if ($stmtRol) {
        $stmtRol->bind_param("s", $codigoUsuario);
        $stmtRol->execute();
        $resRol = $stmtRol->get_result();
        if ($resRol && $resRol->num_rows > 0) {
            $rolReportes = strtolower(trim($resRol->fetch_assoc()['rol_sanidad'] ?? 'user'));
        }
        $stmtRol->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Reportes</title>

     <!-- Tailwind CSS -->
     <link href="../../css/output.css" rel="stylesheet">

<!-- Font Awesome para iconos -->
<link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="../../css/dashboard-vista-tabla-iconos.css">
<link rel="stylesheet" href="../../css/dashboard-responsive.css">
<link rel="stylesheet" href="../../css/dashboard-config.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../assets/js/sweetalert-helpers.js"></script>

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

        .btn-secondary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
            border: none;
            padding: 0.625rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(59, 130, 246, 0.4);
        }

        .btn-export {
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

        .btn-export:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(16, 185, 129, 0.4);
        }

        .btn-outline {
            background: white;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 0.625rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-outline:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
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

        .table-wrapper::-webkit-scrollbar {
            height: 10px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding: 1rem;
        }

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

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #eff6ff !important;
            color: #1d4ed8 !important;
        }

        table.dataTable thead .sorting:before,
        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_desc:before,
        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:after {
            color: white !important;
        }

        .dataTables_wrapper {
            overflow-x: visible !important;
        }

        /* Vista tarjetas (iconos) para móvil */
        .view-toggle-group {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 1rem;
        }
        .view-toggle-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .view-toggle-btn:hover {
            background: #f3f4f6;
        }
        .view-toggle-btn.active {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border-color: #1d4ed8;
        }
        .view-lista-wrap { display: block; }
        .view-tarjetas-wrap { display: none; }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            padding: 0.5rem 0;
        }
        .card-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: box-shadow 0.2s;
        }
        .card-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .card-item .card-codigo {
            font-weight: 700;
            font-size: 1rem;
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        .card-item .card-row {
            font-size: 0.8rem;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }
        .card-item .card-row span.label { color: #6b7280; }
        .card-item .card-acciones {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #f3f4f6;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .card-item .card-acciones a,
        .card-item .card-acciones button {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }

        @media (max-width: 767px) {
            #tablaReportesWrapper .view-tarjetas-wrap { display: block; }
            #tablaReportesWrapper .view-lista-wrap { display: none; }
            #tablaReportesWrapper[data-vista="lista"] .view-tarjetas-wrap { display: none !important; }
            #tablaReportesWrapper[data-vista="lista"] .view-lista-wrap { display: block !important; }
        }
        @media (min-width: 768px) {
            #tablaReportesWrapper .view-lista-wrap { display: block; }
            #tablaReportesWrapper .view-tarjetas-wrap { display: none; }
            #tablaReportesWrapper[data-vista="iconos"] .view-lista-wrap { display: none !important; }
            #tablaReportesWrapper[data-vista="iconos"] .view-tarjetas-wrap { display: block !important; }
        }
    </style>

</head>

<body class="bg-gray-50">
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border">

        <!-- CARD FILTROS PLEGABLE -->
        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <!-- HEADER -->
            <button type="button" onclick="toggleFiltros()"
                class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition">

                <div class="flex items-center gap-2">
                    <span class="text-lg">🔎</span>
                    <h3 class="text-base font-semibold text-gray-800">Filtros de búsqueda</h3>
                </div>

                <!-- ICONO -->
                <svg id="iconoFiltros" class="w-5 h-5 text-gray-600 transition-transform duration-300 rotate-180"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- CONTENIDO PLEGABLE (desplegado por defecto) -->
            <div id="contenidoFiltros" class="px-6 pb-6 pt-4">
                <!-- Fila 1: Periodo -->
                <div class="filter-row-periodo flex flex-wrap items-end gap-4 mb-6">
                    <div class="flex-shrink-0" style="min-width: 200px;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-calendar-alt mr-1 text-blue-600"></i>
                            Periodo
                        </label>
                        <select id="periodoTipo"
                            class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer text-sm">
                            <option value="TODOS">Todos</option>
                            <option value="POR_FECHA" selected>Por fecha</option>
                            <option value="ENTRE_FECHAS">Entre fechas</option>
                            <option value="POR_MES">Por mes</option>
                            <option value="ENTRE_MESES">Entre meses</option>
                            <option value="ULTIMA_SEMANA">Última Semana</option>
                        </select>
                    </div>
                    <div id="periodoPorFecha" class="flex-shrink-0 min-w-[200px] hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-calendar-day mr-1 text-blue-600"></i>
                            Fecha
                        </label>
                        <input id="fechaUnica" type="date" value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div id="periodoEntreFechas" class="hidden periodo-dos-inputs flex-shrink-0 flex items-end gap-2">
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-hourglass-start mr-1 text-blue-600"></i>
                                Desde
                            </label>
                            <input id="fechaInicio" type="date"
                                class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-hourglass-end mr-1 text-blue-600"></i>
                                Hasta
                            </label>
                            <input id="fechaFin" type="date"
                                class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                    <div id="periodoPorMes" class="hidden flex-shrink-0 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-calendar mr-1 text-blue-600"></i>
                            Mes
                        </label>
                        <input id="mesUnico" type="month"
                            class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div id="periodoEntreMeses" class="hidden periodo-dos-inputs flex-shrink-0 flex items-end gap-2">
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-hourglass-start mr-1 text-blue-600"></i>
                                Mes Inicio
                            </label>
                            <input id="mesInicio" type="month"
                                class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div class="min-w-[180px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-hourglass-end mr-1 text-blue-600"></i>
                                Mes Fin
                            </label>
                            <input id="mesFin" type="month"
                                class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>
                </div>
                <!-- Fila 2: Resto de filtros -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-flask mr-1 text-blue-600"></i>Laboratorio
                        </label>
                        <select id="filtroLaboratorio" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-truck mr-1 text-blue-600"></i>Emp. transporte
                        </label>
                        <select id="filtroEmpTrans" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccionar</option>
                            <?php
                            $sql = "SELECT DISTINCT nomEmpTrans AS nombre
                                    FROM san_fact_solicitud_cab
                                    WHERE nomEmpTrans IS NOT NULL AND nomEmpTrans <> ''
                                    ORDER BY nomEmpTrans ASC";
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-vial mr-1 text-blue-600"></i>Tipo muestra
                        </label>
                        <select id="filtroTipoMuestra" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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

                <!-- ACCIONES -->
                <div class="dashboard-actions mt-6 flex flex-wrap justify-end gap-4">
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

        <!-- Rol para mostrar Eliminar solo a admin -->
        <p id="idRolUserReportes" data-rol="<?= htmlspecialchars($rolReportes) ?>" class="hidden"></p>
        <!-- Tabla -->
        <div class="bg-white rounded-xl shadow-md p-5" id="tablaReportesWrapper" data-vista="">
            <div class="card-body p-0 mt-5">
                <!-- Toggle vista: Lista / Iconos (como necropsias) -->
                <div class="view-toggle-group flex items-center gap-2 mb-4">
                    <button type="button" class="view-toggle-btn active" id="btnViewLista" title="Lista">
                        <i class="fas fa-list mr-1"></i> Lista
                    </button>
                    <button type="button" class="view-toggle-btn" id="btnViewIconos" title="Iconos">
                        <i class="fas fa-th mr-1"></i> Iconos
                    </button>
                </div>

                <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetas">
                    <div id="cardsControlsTopReportes" class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-gray-600 border-b border-gray-200 pb-3"></div>
                    <div id="cardsContainer" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                    <div id="cardsPagination" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3"></div>
                </div>

                <div class="view-lista-wrap" id="viewLista">
                <div class="table-wrapper overflow-x-auto">
                    <table id="tablaReportes" class="data-table display w-full text-sm border-collapse config-table" style="width:100%">
                        <thead>
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold">N°</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Cod. Envío</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Fecha Envio</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Laboratorio</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Emp. Trans.</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">U. Reg.</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">U. Resp.</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Aut. Por</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Detalles</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Opciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                </div><!-- /viewLista -->
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
    <!-- Modal Tailwind - Enviar reporte por correo -->
    <div id="modalCorreo" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" aria-hidden="true">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h5 class="text-lg font-semibold text-gray-800">Enviar reporte por correo</h5>
                <button type="button" onclick="cerrarModalCorreo()"
                    class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition"
                    aria-label="Cerrar">
                    &times;
                </button>
            </div>

            <div class="px-6 py-5 overflow-auto flex-1">
                <input type="hidden" id="codigoEnvio" value="">

                <!-- DESTINATARIOS -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Para (destinatarios) *</label>
                    <button type="button" id="btnMostrarSelect"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-blue-300 text-blue-700 bg-white hover:bg-blue-50 transition text-sm font-medium">
                        <i class="fas fa-plus"></i>
                        Seleccionar contactos
                    </button>

                    <!-- Select oculto con contactos -->
                    <select id="destinatarioSelect" multiple
                        class="mt-2 w-full rounded-lg border border-gray-300 text-sm p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        size="6"
                        style="display:none;"></select>

                    <!-- Lista de destinatarios elegidos -->
                    <div id="listaPara" class="mt-2 text-sm"></div>
                </div>

                <!-- ASUNTO Y MENSAJE -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asunto *</label>
                    <input type="text" id="asuntoCorreo"
                        class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje *</label>
                    <textarea id="mensajeCorreo" rows="3"
                        class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required></textarea>
                </div>

                <!-- ARCHIVOS -->
                <div class="mt-4">
                    <p class="font-semibold text-gray-800 mb-2">Archivos adjuntos:</p>
                    <div id="listaArchivos" class="mb-3"></div>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Agregar más archivos</label>
                    <input type="file" id="archivosAdjuntos" multiple
                        class="w-full rounded-lg border border-gray-300 text-sm p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div id="mensajeResultado" class="text-center text-sm min-h-[2rem]"></div>
            </div>

            <div class="dashboard-modal-actions px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-wrap justify-end gap-3">
                <button type="button" onclick="cerrarModalCorreo()"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition">
                    Cancelar
                </button>
                <button type="button" onclick="enviarCorreoDesdeSistema()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    Enviar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Tailwind - Detalles (Muestra + Análisis) -->
    <div id="modalDetallesEnvio"
        class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4"
        aria-hidden="true">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[85vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h5 class="text-lg font-semibold text-gray-800" id="modalDetallesEnvioTitle">Detalles</h5>
                <button type="button" onclick="cerrarModalDetallesEnvio()"
                    class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition"
                    aria-label="Cerrar">
                    &times;
                </button>
            </div>
            <div class="px-6 py-5 overflow-auto flex-1">
                <div id="modalDetallesEnvioBody"></div>
            </div>
            <div class="dashboard-modal-actions px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-wrap justify-end">
                <button type="button" onclick="cerrarModalDetallesEnvio()"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition">
                    Cerrar
                </button>
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
            <div class="dashboard-modal-actions bg-gray-50 px-6 py-4 rounded-b-lg flex flex-wrap justify-end">
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

                        <!-- Número de Solicitudes (al cambiar se agregan/quitan tarjetas dinámicamente) -->
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
            <div class="dashboard-modal-actions bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-wrap justify-end gap-3">
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

        function toggleFiltros() {
            const contenido = document.getElementById('contenidoFiltros');
            const icono = document.getElementById('iconoFiltros');
            if (!contenido || !icono) return;

            contenido.classList.toggle('hidden');
            icono.classList.toggle('rotate-180');

            // Si la tabla ya existe, recalcular anchos (evita header angosto vs body con scrollX)
            if (window.tableReportes) {
                setTimeout(() => {
                    try { window.tableReportes.columns.adjust(); } catch (e) {}
                }, 150);
            }
        }

        function exportarReporteExcel() {
            const params = new URLSearchParams();
            const periodoTipo = $('#periodoTipo').val() || 'TODOS';
            const fechaUnica = $('#fechaUnica').val() || '';
            const fechaInicio = $('#fechaInicio').val() || '';
            const fechaFin = $('#fechaFin').val() || '';
            const mesUnico = $('#mesUnico').val() || '';
            const mesInicio = $('#mesInicio').val() || '';
            const mesFin = $('#mesFin').val() || '';
            const laboratorio = $('#filtroLaboratorio').val() || '';
            const muestra = $('#filtroTipoMuestra').val() || '';
            const empTrans = $('#filtroEmpTrans').val() || '';

            params.set('periodoTipo', periodoTipo);
            if (fechaUnica) params.set('fechaUnica', fechaUnica);
            if (fechaInicio) params.set('fechaInicio', fechaInicio);
            if (fechaFin) params.set('fechaFin', fechaFin);
            if (mesUnico) params.set('mesUnico', mesUnico);
            if (mesInicio) params.set('mesInicio', mesInicio);
            if (mesFin) params.set('mesFin', mesFin);
            if (laboratorio) params.set('laboratorio', laboratorio);
            if (muestra) params.set('muestra', muestra);
            if (empTrans) params.set('empTrans', empTrans);

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
                cerrarModalDetallesEnvio();
                if (typeof cerrarModalCorreo === 'function') cerrarModalCorreo();
            }
        });

        // Cerrar modales al hacer clic fuera
        document.addEventListener('click', function (e) {
            const modalAdvertencia = document.getElementById('modalAdvertenciaEdicion');
            const modalEditar = document.getElementById('modalEditarEnvio');
            const modalDetalles = document.getElementById('modalDetallesEnvio');

            if (e.target === modalAdvertencia) cerrarModalAdvertencia();
            if (e.target === modalEditar) cerrarModalEditar();
            if (e.target === modalDetalles) cerrarModalDetallesEnvio();
        });

        let currentSolicitudCount = 0;
        let tiposMuestraCacheModal = null;
        let datosOriginales = { cabecera: null, detalles: {} };

        async function verificarYEditar(codEnvio) {
            try {
                const res = await fetch(`../seguimiento/verificar_editable.php?codEnvio=${encodeURIComponent(codEnvio)}`);
                const data = await res.json();

                if (data.error) {
                    SwalAlert('Error al verificar: ' + data.error, 'error');
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
                SwalAlert('Error al verificar si se puede editar el envío', 'error');
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
                    SwalAlert('Error al cargar los detalles: ' + err.message, 'error');
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

        async function getTiposMuestraParaModal() {
            if (tiposMuestraCacheModal) return tiposMuestraCacheModal;
            const res = await fetch('../../includes/get_tipos_muestra.php');
            tiposMuestraCacheModal = await res.json();
            return tiposMuestraCacheModal || [];
        }

        function actualizarCantidadSolicitudesModal() {
            const input = document.getElementById('numeroSolicitudes');
            const contenedor = document.getElementById('tablaSolicitudes');
            if (!input || !contenedor) return;
            let n = parseInt(input.value, 10) || 1;
            n = Math.max(1, Math.min(30, n));
            input.value = n;

            const filas = contenedor.querySelectorAll('div[id^="fila-solicitud-"]');
            const current = filas.length;

            if (n > current) {
                for (let pos = current + 1; pos <= n; pos++) {
                    agregarFilaSolicitudEnModal(pos);
                }
                currentSolicitudCount = n;
            } else if (n < current) {
                const sortedPos = Array.from(filas).map(f => parseInt(f.id.split('-').pop(), 10)).sort((a, b) => a - b);
                for (let i = sortedPos.length - 1; i >= n; i--) {
                    const el = document.getElementById('fila-solicitud-' + sortedPos[i]);
                    if (el) el.remove();
                }
                currentSolicitudCount = n;
            }
        }

        function agregarFilaSolicitudEnModal(pos) {
            const contenedor = document.getElementById('tablaSolicitudes');
            if (!contenedor) return;
            const analisisIniciales = [];
            const div = document.createElement('div');
            div.id = 'fila-solicitud-' + pos;
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
                        <input type="text" class="w-full text-sm px-2 py-1 border rounded cod-ref" value="" data-pos="${pos}">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Núm. Muestras</label>
                        <select class="w-full text-sm px-2 py-1 border rounded num-muestras" data-pos="${pos}">
                            ${Array.from({ length: 30 }, (_, i) => `<option value="${i + 1}"${i === 0 ? ' selected' : ''}>${i + 1}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Fecha Toma</label>
                        <input type="date" class="w-full text-sm px-2 py-1 border rounded fecha-toma" value="" data-pos="${pos}">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="text-xs text-gray-600">Observaciones</label>
                    <textarea class="w-full text-sm px-2 py-1 border rounded obs" data-pos="${pos}" rows="2"></textarea>
                </div>
                <button type="button" class="px-4 py-2 text-sm font-medium rounded-lg border-2 border-sky-400 bg-white text-sky-500 hover:bg-sky-500 hover:text-white transition duration-200 ver-analisis-toggle" data-pos="${pos}">
                    <span class="toggle-text">Ver Análisis</span>
                </button>
                <div class="mt-3 analisis-container hidden" id="analisis-container-${pos}"></div>
            `;
            contenedor.appendChild(div);

            getTiposMuestraParaModal().then(tipos => {
                const select = div.querySelector('.tipo-muestra');
                (tipos || []).forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.codigo;
                    opt.textContent = t.nombre;
                    select.appendChild(opt);
                });
            });

            div.querySelector('.ver-analisis-toggle').addEventListener('click', async function () {
                const posActual = this.dataset.pos;
                const tipoId = div.querySelector('.tipo-muestra').value;
                if (!tipoId) {
                    alert('Seleccione primero el tipo de muestra');
                    return;
                }
                const container = document.getElementById('analisis-container-' + posActual);
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
                SwalAlert("Por favor, corrija los siguientes errores:\n\n" + errores.join('\n'), 'error');
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
                    SwalAlert('Error: ' + (data.error || 'No se pudo guardar'), 'error');
                }
            } catch (err) {
                console.error(err);
                SwalAlert('Error de red al guardar', 'error');
            }
        });

        document.getElementById('numeroSolicitudes') && document.getElementById('numeroSolicitudes').addEventListener('change', actualizarCantidadSolicitudesModal);

        async function borrarRegistroDesdeListado(codEnvio) {
            var ok = await SwalConfirm('¿Eliminar el envío "' + codEnvio + '"?\n\nEsta acción no se puede deshacer.', 'Confirmar eliminación');
            if (!ok) return;

            fetch('../seguimiento/borrarSolicitudCompleto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'codEnvio=' + encodeURIComponent(codEnvio)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        SwalAlert('Envío eliminado correctamente', 'success');
                        if (tableReportes) tableReportes.ajax.reload();
                    } else {
                        SwalAlert('Error: ' + (data.message || 'No se pudo eliminar'), 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    SwalAlert('Error de conexión', 'error');
                });
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatearFechaDMY(value) {
            if (!value) return '<span class="text-gray-400 italic">—</span>';
            const raw = String(value).trim();

            // Soporta "YYYY-MM-DD" y "YYYY-MM-DD HH:mm:ss"
            if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
                const ymd = raw.substring(0, 10);
                const [yyyy, mm, dd] = ymd.split('-');
                return `${dd}/${mm}/${yyyy}`;
            }

         
            if (/^\d{2}\/\d{2}\/\d{4}$/.test(raw)) return raw;

            return escapeHtml(raw);
        }

        function construirHtmlDetalles(detalles) {
            if (!Array.isArray(detalles) || detalles.length === 0) {
                return `<div class="rounded-lg border border-yellow-200 bg-yellow-50 text-yellow-900 px-4 py-3 text-sm">No se encontraron detalles para este envío.</div>`;
            }

            // Agrupar por posSolicitud (similar a PDF)
            const grupos = {};
            for (const item of detalles) {
                const pos = item.posSolicitud ?? '—';
                if (!grupos[pos]) grupos[pos] = [];
                grupos[pos].push(item);
            }

            const posiciones = Object.keys(grupos).sort((a, b) => (Number(a) || 0) - (Number(b) || 0));
            let html = '';

            for (const pos of posiciones) {
                const items = grupos[pos] || [];
                const muestra = items.find(i => i.nomMuestra)?.nomMuestra || '—';
                const fecToma = items.find(i => i.fecToma)?.fecToma || '';

                // Agrupar análisis por paquete (similar a PDF)
                const paquetes = new Map(); // paquete => Set(analisis)
                for (const it of items) {
                    const paquete = (it.nomPaquete && String(it.nomPaquete).trim()) ? String(it.nomPaquete).trim() : 'Sin paquete';
                    const analisis = (it.nomAnalisis && String(it.nomAnalisis).trim()) ? String(it.nomAnalisis).trim() : '';
                    if (!paquetes.has(paquete)) paquetes.set(paquete, new Set());
                    if (analisis) paquetes.get(paquete).add(analisis);
                }

                let bloquesAnalisis = '';
                for (const [paq, setAnalisis] of paquetes.entries()) {
                    const lista = Array.from(setAnalisis);
                    if (lista.length === 0) continue;
                    bloquesAnalisis += `
                        <div class="mb-2">
                            <span class="font-semibold text-gray-800">${escapeHtml(paq)}:</span>
                            <span class="text-gray-700">${escapeHtml(lista.join(', '))}</span>
                        </div>
                    `;
                }
                if (!bloquesAnalisis) {
                    bloquesAnalisis = `<div class="text-gray-400 italic">—</div>`;
                }

                html += `
                    <div class="border border-gray-200 rounded-xl p-4 mb-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <div class="font-semibold text-gray-800">Solicitud #${escapeHtml(pos)}</div>
                            <div class="text-gray-500 text-sm">Fecha toma: ${formatearFechaDMY(fecToma)}</div>
                        </div>
                        <div class="mb-2 text-sm">
                            <span class="font-semibold text-gray-800">Muestra:</span>
                            <span class="text-gray-700">${escapeHtml(muestra)}</span>
                        </div>
                        <div class="text-sm">
                            <div class="font-semibold text-gray-800 mb-1">Análisis:</div>
                            <div class="pl-3 ml-1 border-l border-gray-200">
                                ${bloquesAnalisis}
                            </div>
                        </div>
                    </div>
                `;
            }

            return html;
        }

        function abrirModalDetallesEnvioUI() {
            const modal = document.getElementById('modalDetallesEnvio');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalDetallesEnvio() {
            const modal = document.getElementById('modalDetallesEnvio');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        async function abrirModalDetallesEnvio(codEnvio) {
            const titleEl = document.getElementById('modalDetallesEnvioTitle');
            const bodyEl = document.getElementById('modalDetallesEnvioBody');
            if (!titleEl || !bodyEl) return;

            titleEl.textContent = `Detalles - Envío ${codEnvio}`;
            bodyEl.innerHTML = `
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <div class="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
                    <div>Cargando detalles...</div>
                </div>
            `;

            abrirModalDetallesEnvioUI();

            try {
                const res = await fetch(`../seguimiento/get_detalles_envio.php?codEnvio=${encodeURIComponent(codEnvio)}`);
                const data = await res.json();
                if (!res.ok || data?.error) {
                    bodyEl.innerHTML = `<div class="rounded-lg border border-red-200 bg-red-50 text-red-900 px-4 py-3 text-sm">Error al cargar detalles: ${escapeHtml(data?.error || res.statusText)}</div>`;
                    return;
                }
                bodyEl.innerHTML = construirHtmlDetalles(data);
            } catch (err) {
                console.error(err);
                bodyEl.innerHTML = `<div class="rounded-lg border border-red-200 bg-red-50 text-red-900 px-4 py-3 text-sm">Error de conexión al cargar detalles.</div>`;
            }
        }

        function aplicarVisibilidadVistaReportes(vista) {
            const esLista = (vista === 'lista');
            $('#tablaReportesWrapper').attr('data-vista', vista);
            if (esLista) {
                $('#viewTarjetas').addClass('hidden').css('display', 'none');
                $('#tablaReportesWrapper .view-lista-wrap').removeClass('hidden').css('display', 'block');
            } else {
                $('#tablaReportesWrapper .view-lista-wrap').addClass('hidden').css('display', 'none');
                $('#viewTarjetas').removeClass('hidden').css('display', 'block');
                $('#cardsContainer').attr('data-vista-cards', 'iconos');
            }
        }
        function actualizarVistaInicial() {
            const w = $(window).width();
            const w$ = $('#tablaReportesWrapper');
            if (!w$.attr('data-vista')) {
                const vistaInicial = w < 768 ? 'iconos' : 'lista';
                w$.attr('data-vista', vistaInicial);
                $('#btnViewLista').toggleClass('active', vistaInicial === 'lista');
                $('#btnViewIconos').toggleClass('active', vistaInicial === 'iconos');
                $('#cardsContainer').attr('data-vista-cards', 'iconos');
                aplicarVisibilidadVistaReportes(vistaInicial);
            }
        }

        function renderizarTarjetas() {
            if (!tableReportes) return;
            const api = tableReportes;
            const cont = $('#cardsContainer');
            cont.empty();
            const rolReportes = ($('#idRolUserReportes').attr('data-rol') || '').trim().toLowerCase();
            const puedeEliminar = (rolReportes === 'admin');
            const info = api.page.info();
            let rowIndex = 0;
            api.rows({ page: 'current' }).every(function () {
                rowIndex++;
                const numero = info.start + rowIndex;
                const row = this.data();
                const cod = escapeHtml(row.codEnvio || '');
                const codEnc = encodeURIComponent(row.codEnvio || '');
                const fec = formatearFechaDMY(row.fecEnvio);
                const codEdit = escapeHtml(row.codEnvio || '');
                const btnEliminar = puedeEliminar
                    ? `<button type="button" class="btn-eliminar-card text-rose-600 hover:text-rose-800" title="Eliminar" data-codigo="${codEdit}"><i class="fa-solid fa-trash"></i></button>`
                    : '';
                const card = `
                    <div class="card-item">
                        <div class="card-numero-row">#${numero}</div>
                        <div class="card-contenido">
                            <div class="card-codigo">${cod}</div>
                            <div class="card-campos">
                                <div class="card-row"><span class="label">Fecha:</span> ${fec}</div>
                                <div class="card-row"><span class="label">Lab:</span> ${escapeHtml(row.nomLab || '')}</div>
                                <div class="card-row"><span class="label">Emp.Trans:</span> ${escapeHtml(row.nomEmpTrans || '')}</div>
                                <div class="card-row"><span class="label">U.Reg:</span> ${escapeHtml(row.usuarioRegistrador || '')}</div>
                                <div class="card-row"><span class="label">U.Resp:</span> ${escapeHtml(row.usuarioResponsable || '')}</div>
                                <div class="card-row"><span class="label">Aut Por:</span> ${escapeHtml(row.autorizadoPor || '')}</div>
                            </div>
                            <div class="card-acciones">
                                <button type="button" class="btn-detalles text-blue-600 hover:text-blue-800" data-codigo="${cod}" title="Ver"><i class="fas fa-eye"></i></button>
                                <a class="text-red-600 hover:text-red-800" title="PDF Tabla" target="_blank" href="generar_pdf_tabla.php?codigo=${codEnc}"><i class="fa-solid fa-file-pdf"></i></a>
                                <a class="text-red-600 hover:text-red-800" title="PDF Resumen" target="_blank" href="generar_pdf_resumen.php?codigo=${codEnc}"><i class="fa-solid fa-file-lines"></i></a>
                                <button type="button" class="btn-enviar-correo text-blue-600 hover:text-blue-800" title="Correo" data-codigo="${cod}"><i class="fa-solid fa-paper-plane"></i></button>
                                <a class="text-slate-700 hover:text-slate-900" title="QR" target="_blank" href="generar_qr_etiqueta.php?codigo=${codEnc}"><i class="fa-solid fa-qrcode"></i></a>
                                <button type="button" class="btn-editar-card text-indigo-600 hover:text-indigo-800" title="Editar" data-codigo="${codEdit}"><i class="fa-solid fa-edit"></i></button>
                                ${btnEliminar}
                            </div>
                        </div>
                    </div>
                `;
                cont.append(card);
            });
            const len = api.page.len();
            const lengthOptions = [10, 25, 50];
            const lengthSelect = '<label class="inline-flex items-center gap-2"><span>Mostrar</span><select class="cards-length-select px-2 py-1 border border-gray-300 rounded-md text-sm">' +
                lengthOptions.map(n => '<option value="' + n + '"' + (n === len ? ' selected' : '') + '>' + n + '</option>').join('') +
                '</select><span>registros</span></label>';
            const navBtns = '<div class="flex items-center gap-3 flex-wrap">' +
                '<span>Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros</span>' +
                '<div class="flex gap-2">' +
                '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page === 0 ? 'disabled' : '') + ' onclick="var dt=$(\'#tablaReportes\').DataTable(); if(dt) dt.page(\'previous\').draw(false);">Anterior</button>' +
                '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page >= info.pages - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page >= info.pages - 1 ? 'disabled' : '') + ' onclick="var dt=$(\'#tablaReportes\').DataTable(); if(dt) dt.page(\'next\').draw(false);">Siguiente</button>' +
                '</div></div>';
            const controlsHtml = '<div class="flex flex-wrap items-center justify-between gap-3 w-full">' + lengthSelect + navBtns + '</div>';
            $('#cardsControlsTopReportes').html(controlsHtml);
            $('#cardsPagination').html(controlsHtml);
            $('#cardsControlsTopReportes .cards-length-select, #cardsPagination .cards-length-select').on('change', function() {
                const val = parseInt($(this).val(), 10);
                if (tableReportes) tableReportes.page.len(val).draw(false);
            });
        }

        function cargarTablaReportes() {
            if (tableReportes) tableReportes.destroy();

            var periodoTipo = ($('#periodoTipo').val() || 'TODOS').trim();
            var fechaUnica = ($('#fechaUnica').val() || '').trim();
            var fechaInicio = ($('#fechaInicio').val() || '').trim();
            var fechaFin = ($('#fechaFin').val() || '').trim();
            var mesUnico = ($('#mesUnico').val() || '').trim();
            var mesInicio = ($('#mesInicio').val() || '').trim();
            var mesFin = ($('#mesFin').val() || '').trim();
            var laboratorio = ($('#filtroLaboratorio').val() || '').trim();
            var muestra = ($('#filtroTipoMuestra').val() || '').trim();
            var empTrans = ($('#filtroEmpTrans').val() || '').trim();

            tableReportes = $('#tablaReportes').DataTable({
                processing: true,
                serverSide: true,
                scrollX: false,
                autoWidth: false,
                stripeClasses: [],
                drawCallback: function () {
                    renderizarTarjetas();
                },
                initComplete: function () {
                    this.api().columns.adjust();
                },
                ajax: {
                    url: '../seguimiento/listar_cab_filtros.php',
                    type: 'POST',
                    data: {
                        periodoTipo,
                        fechaUnica,
                        fechaInicio,
                        fechaFin,
                        mesUnico,
                        mesInicio,
                        mesFin,
                        laboratorio,
                        muestra,
                        empTrans,
                        granjas: [],
                        galpon: '',
                        edadDesde: '',
                        edadHasta: ''
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data, type, row, meta) {
                            return type === 'display' ? (meta.settings._iDisplayStart + meta.row + 1) : '';
                        }
                    },
                    { data: 'codEnvio' },
                    {
                        data: 'fecEnvio',
                        render: function (data, type) {
                            if (type !== 'display' && type !== 'filter') return data;
                            return formatearFechaDMY(data);
                        }
                    },
                    { data: 'nomLab' },
                    { data: 'nomEmpTrans' },
                    { data: 'usuarioRegistrador' },
                    { data: 'usuarioResponsable' },
                    { data: 'autorizadoPor' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            const cod = escapeHtml(row.codEnvio || '');
                            return `
                                <button type="button"
                                    class="btn-detalles cursor-pointer text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-2 transition"
                                    title="Ver detalles"
                                    data-codigo="${cod}">
                                   <i class="fas fa-eye"></i>
                                    Ver
                                </button>
                            `;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function (data, type, row) {
                            const cod = encodeURIComponent(row.codEnvio);
                            const rolReportes = ($('#idRolUserReportes').attr('data-rol') || '').trim().toLowerCase();
                            const puedeEliminar = (rolReportes === 'admin');
                            let botonEliminar = '';
                            if (puedeEliminar) {
                                botonEliminar = `<button class="text-rose-600 hover:text-rose-800" title="Eliminar"
                                        onclick="borrarRegistroDesdeListado('${row.codEnvio}')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>`;
                            }
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
                                    ${botonEliminar}
                                </div>
                            `;
                        }
                    }
                ],
                columnDefs: [{ orderable: false, targets: [0, 8, 9] }],
                language: { url: '../../assets/i18n/es-ES.json' },
                pageLength: 10,
                lengthMenu: [[10, 25, 50], [10, 25, 50]]
            });
        }

        $(document).ready(function () {
            actualizarVistaInicial();

            $('#btnViewLista').on('click', function () {
                aplicarVisibilidadVistaReportes('lista');
                $('#btnViewLista').addClass('active');
                $('#btnViewIconos').removeClass('active');
            });
            $('#btnViewIconos').on('click', function () {
                aplicarVisibilidadVistaReportes('iconos');
                $('#btnViewIconos').addClass('active');
                $('#btnViewLista').removeClass('active');
            });

            $(window).on('resize', function () {
                if (!$('#tablaReportesWrapper').attr('data-vista')) return;
                actualizarVistaInicial();
            });

            $(document).on('click', '.btn-detalles', function () {
                const codEnvio = $(this).data('codigo');
                if (!codEnvio) return;
                abrirModalDetallesEnvio(codEnvio);
            });
            $(document).on('click', '.btn-editar-card', function () {
                const cod = $(this).data('codigo');
                if (cod) verificarYEditar(cod);
            });
            $(document).on('click', '.btn-eliminar-card', function () {
                const cod = $(this).data('codigo');
                if (cod) {
                    SwalConfirm('¿Eliminar el envío "' + cod + '"?\n\nEsta acción no se puede deshacer.', 'Confirmar eliminación').then(function(ok) {
                        if (ok) borrarRegistroDesdeListado(cod);
                    });
                }
            });

            cargarTablaReportes();

            $('#btnFiltrar').click(function () {
                cargarTablaReportes();
            });

            $('#btnLimpiar').click(function () {
                $('#periodoTipo').val('POR_FECHA');
                $('#fechaUnica').val(new Date().toISOString().slice(0, 10));
                $('#fechaInicio').val('');
                $('#fechaFin').val('');
                $('#mesUnico').val('');
                $('#mesInicio').val('');
                $('#mesFin').val('');
                $('#filtroLaboratorio').val('');
                $('#filtroTipoMuestra').val('');
                $('#filtroEmpTrans').val('');
                aplicarVisibilidadPeriodoReportes();
                cargarTablaReportes();
            });

            function aplicarVisibilidadPeriodoReportes() {
                var t = $('#periodoTipo').val() || '';
                $('#periodoPorFecha, #periodoEntreFechas, #periodoPorMes, #periodoEntreMeses').addClass('hidden');
                if (t === 'POR_FECHA') $('#periodoPorFecha').removeClass('hidden');
                else if (t === 'ENTRE_FECHAS') $('#periodoEntreFechas').removeClass('hidden');
                else if (t === 'POR_MES') $('#periodoPorMes').removeClass('hidden');
                else if (t === 'ENTRE_MESES') $('#periodoEntreMeses').removeClass('hidden');
            }
            $('#periodoTipo').on('change', aplicarVisibilidadPeriodoReportes);
            aplicarVisibilidadPeriodoReportes();
        });
    </script>

    <!-- JS existente para modal de correo-->
    <script src="../../assets/js/reportes/reportes.js"></script>
    <!-- Tabla: estilos unificados al final para ganar a DataTables -->
    <link rel="stylesheet" href="../../css/dashboard-config.css">
</body>

</html>