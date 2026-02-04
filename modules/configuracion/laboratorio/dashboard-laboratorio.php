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


//ruta relativa a la conexion
include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

$codigoUsuario = $_SESSION['usuario'] ?? '';
$rolLab = 'user';
if ($codigoUsuario) {
    $sqlRol = "SELECT rol_sanidad FROM usuario WHERE codigo = ?";
    $stmtRol = $conexion->prepare($sqlRol);
    if ($stmtRol) {
        $stmtRol->bind_param("s", $codigoUsuario);
        $stmtRol->execute();
        $resRol = $stmtRol->get_result();
        if ($resRol && $resRol->num_rows > 0) {
            $rolLab = strtolower(trim($resRol->fetch_assoc()['rol_sanidad'] ?? 'user'));
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
    <title>Dashboard - Laboratorios</title>

    <!-- Tailwind CSS -->
    <link href="../../../css/output.css" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">

    <style>
        /* Tus estilos existentes */
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

        .data-table {
            width: 100% !important;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem 1rem;
            text-align: left;
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

        .data-table tbody tr:hover {
            background-color: #eff6ff !important;
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
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- VISTA LABORATORIOS -->
        <div id="viewLaboratorio" class="content-view">


            <div class="form-container max-w-7xl mx-auto">
                <!-- Botones de acción -->
                <div class="dashboard-actions mb-6 flex justify-between items-center flex-wrap gap-3">
                    <button type="button"
                        class="px-6 py-2.5 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2"
                        onclick="exportarLaboratorios()"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);"
                        onmouseover="this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'"
                        onmouseout="this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'">
                        📊 Exportar a Excel
                    </button>
                    <button type="button"
                        class="btn btn-primary px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2"
                        onclick="openLaboratorioModal('create')">
                        ➕ Nuevo Laboratorio
                    </button>
                </div>

                <!-- Rol para mostrar/ocultar Eliminar (solo admin) -->
                <p id="idRolUserLab" data-rol="<?= htmlspecialchars($rolLab) ?>" class="hidden"></p>
                <!-- Tabla de laboratorios -->
                <div id="tablaLaboratorioWrapper" class="border border-gray-300 rounded-2xl bg-white overflow-x-auto p-4" data-vista-tabla-iconos data-vista="">
                    <div class="view-toggle-group flex items-center gap-2 mb-4">
                        <button type="button" class="view-toggle-btn active" id="btnViewTablaLab" title="Lista">
                            <i class="fas fa-list mr-1"></i> Lista
                        </button>
                        <button type="button" class="view-toggle-btn" id="btnViewIconosLab" title="Iconos">
                            <i class="fas fa-th mr-1"></i> Iconos
                        </button>
                    </div>
                    <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetasLab">
                        <div id="cardsContainerLab" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                        <div id="cardsPaginationLab" class="flex items-center justify-between mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3"></div>
                    </div>
                    <div class="view-lista-wrap table-container overflow-x-auto">
                    <table id="tabla" class="data-table w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Código</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Nombre</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="laboratorioTableBody" class="divide-y divide-gray-200">
                            <?php
                            $query = "SELECT codigo, nombre FROM san_dim_laboratorio ORDER BY codigo";
                            $result = mysqli_query($conexion, $query);
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<tr class="hover:bg-gray-50 transition">';
                                    echo '<td class="px-6 py-4 text-gray-700">' . htmlspecialchars($row['codigo']) . '</td>';
                                    echo '<td class="px-6 py-4 text-gray-700">' . htmlspecialchars($row['nombre']) . '</td>';
                                    echo '<td class="px-6 py-4 flex gap-2">
                            <button class="btn-icon p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar" onclick="openLaboratorioModal(\'update\', ' . (int) $row['codigo'] . ', \'' . addslashes(htmlspecialchars($row['nombre'])) . '\')">
                                <i class="fa-solid fa-edit"></i>
                            </button>';
                                    if ($rolLab === 'admin') {
                                        echo '<button class="btn-icon p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" onclick="confirmLaboratorioDelete(' . (int) $row['codigo'] . ')">
                                <i class="fa-solid fa-trash"></i>
                            </button>';
                                    }
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr>';
                                echo '<td colspan="3" class="px-6 py-8 text-center text-gray-500">No hay laboratorios registrados</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal para Crear/Editar Laboratorio -->
        <div id="laboratorioModal" style="display: none;"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="laboratorioModalTitle" class="text-xl font-bold text-gray-800">➕ Nuevo Laboratorio</h2>
                    <button onclick="closeLaboratorioModal()"
                        class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition">
                        ×
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <form id="laboratorioForm" onsubmit="return saveLaboratorio(event)">
                        <input type="hidden" id="laboratorioModalAction" value="create">
                        <input type="hidden" id="laboratorioEditCodigo" value="">

                        <!-- Campo Nombre -->
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre del Laboratorio <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="laboratorioModalNombre" name="nombre" maxlength="255"
                                placeholder="Ingrese el nombre del laboratorio" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        </div>

                        <!-- Botones -->
                        <div class="dashboard-modal-actions flex flex-col-reverse sm:flex-row flex-wrap gap-3 justify-end">
                            <button type="button" onclick="closeLaboratorioModal()"
                                class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition duration-200">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="btn btn-primary px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2">
                                💾 Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <!-- Footer dinámico -->
        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> -
                © <span id="currentYear"></span>
            </p>
        </div>

        <script>
            // Actualizar el año dinámicamente
            document.getElementById('currentYear').textContent = new Date().getFullYear();
        </script>

    </div>

    <script src="../../../assets/js/configuracion/laboratorio.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        var tableLaboratorio;
        function actualizarVistaInicialLab() {
            var w = $(window).width();
            var w$ = $('#tablaLaboratorioWrapper');
            if (!w$.attr('data-vista')) {
                w$.attr('data-vista', w < 768 ? 'iconos' : 'tabla');
                $('#btnViewIconosLab').toggleClass('active', w$.attr('data-vista') === 'iconos');
                $('#btnViewTablaLab').toggleClass('active', w$.attr('data-vista') === 'tabla');
            }
        }
        function renderizarTarjetasLab() {
            if (!tableLaboratorio) return;
            var api = tableLaboratorio;
            var cont = $('#cardsContainerLab');
            cont.empty();
            var rolLab = ($('#idRolUserLab').attr('data-rol') || '').trim().toLowerCase();
            var puedeEliminar = (rolLab === 'admin');
            var info = api.page.info();
            var rowIndex = 0;
            api.rows({ page: 'current' }).every(function() {
                rowIndex++;
                var numero = info.start + rowIndex;
                var row = this.node();
                var $row = $(row);
                var cells = $row.find('td');
                if (cells.length < 2) return;
                var codigo = $(cells[0]).text().trim();
                var nombre = $(cells[1]).text().trim();
                var codAttr = (codigo + '').replace(/"/g, '&quot;');
                var nomAttr = (nombre + '').replace(/"/g, '&quot;');
                var acciones = '<button type="button" class="btn-editar-card-lab p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar"><i class="fa-solid fa-edit"></i></button>';
                if (puedeEliminar) {
                    acciones += '<button type="button" class="btn-eliminar-card-lab p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" data-codigo="' + $('<div>').text(codigo).html() + '"><i class="fa-solid fa-trash"></i></button>';
                }
                var card = $('<div class="card-item" data-codigo="' + codAttr + '" data-nombre="' + nomAttr + '">' +
                    '<div class="card-numero-row">#' + numero + '</div>' +
                    '<div class="card-row"><span class="label">codigo:</span> ' + $('<div>').text(codigo).html() + '</div>' +
                    '<div class="card-row"><span class="label">Nombre:</span> ' + $('<div>').text(nombre).html() + '</div>' +
                    '<div class="card-acciones">' + acciones + '</div></div>');
                cont.append(card);
            });
            var info = api.page.info();
            var pagHtml = '<span>Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros</span>' +
                '<div class="flex gap-2">' +
                '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page === 0 ? 'disabled' : '') + ' onclick="tableLaboratorio && tableLaboratorio.page(\'previous\').draw(false); renderizarTarjetasLab();">Anterior</button>' +
                '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page >= info.pages - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page >= info.pages - 1 ? 'disabled' : '') + ' onclick="tableLaboratorio && tableLaboratorio.page(\'next\').draw(false); renderizarTarjetasLab();">Siguiente</button>' +
                '</div>';
            $('#cardsPaginationLab').html(pagHtml);
        }
        $(document).ready(function() {
            tableLaboratorio = $('#tabla').DataTable({
                language: { url: '../../../assets/i18n/es-ES.json' },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                order: [[0, 'asc']],
                drawCallback: function() { renderizarTarjetasLab(); }
            });
            actualizarVistaInicialLab();
            $('#btnViewIconosLab').on('click', function() {
                $('#tablaLaboratorioWrapper').attr('data-vista', 'iconos');
                $('#btnViewIconosLab').addClass('active');
                $('#btnViewTablaLab').removeClass('active');
            });
            $('#btnViewTablaLab').on('click', function() {
                $('#tablaLaboratorioWrapper').attr('data-vista', 'tabla');
                $('#btnViewTablaLab').addClass('active');
                $('#btnViewIconosLab').removeClass('active');
            });
            $(window).on('resize', function() {
                if (!$('#tablaLaboratorioWrapper').attr('data-vista')) return;
                actualizarVistaInicialLab();
            });
            $(document).on('click', '.btn-editar-card-lab', function() {
                var c = $(this).closest('.card-item');
                openLaboratorioModal('update', parseInt(c.attr('data-codigo'), 10) || c.attr('data-codigo'), c.attr('data-nombre') || '');
            });
            $(document).on('click', '.btn-eliminar-card-lab', function() {
                var cod = $(this).data('codigo');
                if (cod !== undefined) confirmLaboratorioDelete(isNaN(cod) ? cod : parseInt(cod, 10));
            });
        });
    </script>

</body>

</html>