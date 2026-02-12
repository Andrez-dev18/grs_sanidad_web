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
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión.");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Análisis</title>
    <link href="../../../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../../assets/js/sweetalert-helpers.js"></script>

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

        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
            <div class="dashboard-actions flex flex-col sm:flex-row justify-end sm:justify-between items-stretch sm:items-center gap-3 px-4 sm:px-6 py-4">
                <a class="btn-export inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-2 sm:order-1" href="exportar_analisis_excel.php">📊 Exportar a Excel</a>
                <button type="button" class="btn-secondary inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-1 sm:order-2" onclick="openAnalisisModal('create')">➕ Nuevo Análisis</button>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 dashboard-tabla-wrapper" id="tablaAnalisisWrapper" data-vista="">
            <div class="card-body p-0 mt-5">
                <div class="reportes-toolbar-row flex flex-wrap items-center justify-between gap-3 mb-3" id="analisisToolbarRow">
                    <div class="view-toggle-group flex items-center gap-2" id="viewToggleGroupAnalisis">
                        <button type="button" class="view-toggle-btn active" id="btnViewTablaAna" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                        <button type="button" class="view-toggle-btn" id="btnViewIconosAna" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                    </div>
                    <div id="analisisDtControls" class="toolbar-dt-controls flex flex-wrap items-center gap-3"></div>
                    <div id="analisisIconosControls" class="toolbar-iconos-controls flex flex-wrap items-center gap-3" style="display: none;"></div>
                </div>
                <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetasAna">
                    <div id="cardsControlsTopAna" class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-gray-600 border-b border-gray-200 pb-3"></div>
                    <div id="cardsContainerAna" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                    <div id="cardsPaginationAna" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3" data-table="#tablaAnalisis"></div>
                </div>
                <div class="view-lista-wrap" id="viewListaAnalisis">
                <div class="table-wrapper overflow-x-auto">
                    <table id="tablaAnalisis" class="data-table display w-full text-sm border-collapse config-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3">N°</th>
                                    <th>Nombre</th>
                                    <th>Enfermedad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT codigo, nombre, enfermedad FROM san_dim_analisis ORDER BY codigo ASC";
                                $result = mysqli_query($conexion, $query);
                                if (!$result) {
                                    die("Error en consulta: " . mysqli_error($conexion));
                                }
                                $idx = 0;
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $idx++;
                                    ?>
                                    <tr data-codigo="<?= htmlspecialchars($row['codigo']) ?>">
                                        <td><?= $idx ?></td>
                                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                                        <td><?= htmlspecialchars($row['enfermedad'] ?? '') ?></td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="p-2 rounded-lg text-blue-600 hover:text-blue-800 hover:bg-blue-100 transition"
                                                    title="Editar"
                                                    onclick='openAnalisisModal("update", <?= json_encode($row["codigo"]) ?>, <?= json_encode($row["nombre"]) ?>, <?= json_encode($row["enfermedad"] ?? "") ?>)'>
                                                    <i class="fa-solid fa-edit"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="p-2 rounded-lg text-red-600 hover:text-red-800 hover:bg-red-100 transition"
                                                    title="Eliminar"
                                                    onclick='confirmDelete(<?= json_encode($row["codigo"]) ?>, <?= json_encode($row["nombre"]) ?>)'>
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Modal -->
        <div id="analisisModal" style="display: none;"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div
                class="bg-white rounded-2xl shadow-lg w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-200">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="analisisModalTitle" class="text-xl font-bold text-gray-800">➕ Nuevo Análisis</h2>
                    <button onclick="closeAnalisisModal()" class="text-2xl text-gray-500 hover:text-gray-700">×</button>
                </div>
                <div class="flex-1 overflow-y-auto p-6" style="max-height: 60vh;">
                    <form id="analisisForm">
                        <input type="hidden" id="analisisModalAction" name="action" value="create">
                        <input type="hidden" id="analisisEditCodigo" name="codigo" value="">

                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Análisis <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="analisisModalNombre" name="nombre" maxlength="100" required
                                class="form-control">
                        </div>

                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Enfermedad</label>
                            <input type="text" id="analisisModalEnfermedad" name="enfermedad" maxlength="100"
                                class="form-control">
                        </div>
                    </form>
                </div>
                <div class="border-t border-gray-200 p-6 bg-gray-50">
                    <div class="dashboard-modal-actions flex flex-col-reverse sm:flex-row flex-wrap gap-3 justify-end">
                        <button type="button" onclick="closeAnalisisModal()" class="btn-outline">Cancelar</button>
                        <button type="submit" form="analisisForm" class="btn-primary">💾 Guardar Análisis</button>
                    </div>
                </div>
            </div>
        </div>

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


    <script src="../../../assets/js/pagination-iconos.js"></script>
    <script src="../../../assets/js/configuracion/analisis.js"></script>

        <script>
            var tableAnalisis;
            function actualizarVistaInicialAna() {
                var w = $(window).width();
                var w$ = $('#tablaAnalisisWrapper');
                if (!w$.attr('data-vista')) {
                    w$.attr('data-vista', w < 768 ? 'iconos' : 'tabla');
                    $('#btnViewIconosAna').toggleClass('active', w$.attr('data-vista') === 'iconos');
                    $('#btnViewTablaAna').toggleClass('active', w$.attr('data-vista') === 'tabla');
                }
            }
            function renderizarTarjetasAna() {
                if (!tableAnalisis) return;
                var api = tableAnalisis;
                var cont = $('#cardsContainerAna');
                cont.empty();
                var info = api.page.info();
                var rowIndex = 0;
                api.rows({ page: 'current' }).every(function() {
                    rowIndex++;
                    var numero = info.start + rowIndex;
                    var $row = $(this.node());
                    var cells = $row.find('td');
                    if (cells.length < 3) return;
                    var codigo = $row.attr('data-codigo') || $(cells[0]).text().trim();
                    var nombre = $(cells[1]).text().trim();
                    var enfermedad = $(cells[2]).text().trim();
                    var codAttr = (codigo + '').replace(/"/g, '&quot;');
                    var nomAttr = (nombre + '').replace(/"/g, '&quot;');
                    var enfAttr = (enfermedad + '').replace(/"/g, '&quot;');
                    var card = $('<div class="card-item" data-codigo="' + codAttr + '" data-nombre="' + nomAttr + '" data-enfermedad="' + enfAttr + '">' +
                        '<div class="card-numero-row">#' + numero + '</div>' +
                        '<div class="card-row"><span class="label">Nombre:</span> ' + $('<div>').text(nombre).html() + '</div>' +
                        '<div class="card-row"><span class="label">Enfermedad:</span> ' + $('<div>').text(enfermedad).html() + '</div>' +
                        '<div class="card-acciones">' +
                        '<button type="button" class="btn-editar-card-ana p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar"><i class="fa-solid fa-edit"></i></button>' +
                        '<button type="button" class="btn-eliminar-card-ana p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" data-codigo="' + codAttr + '" data-nombre="' + nomAttr + '"><i class="fa-solid fa-trash"></i></button>' +
                        '</div></div>');
                    cont.append(card);
                });
                var info = api.page.info();
                $('#cardsPaginationAna').html(typeof buildPaginationIconos === 'function' ? buildPaginationIconos(info) : ('<span class="dataTables_info">Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros</span>'));
            }
            $(document).ready(function () {
                tableAnalisis = $('#tablaAnalisis').DataTable({
                    language: { url: '../../../assets/i18n/es-ES.json' },
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                    order: [[0, 'asc']],
                    columnDefs: [{ orderable: false, targets: [3] }],
                    dom: '<"dt-top-row"<"flex items-center gap-6" l><"flex items-center gap-2" f>>rt<"dt-bottom-row"<"text-sm text-gray-600" i><"text-sm text-gray-600" p>>',
                    initComplete: function() {
                        var wrapper = $('#tablaAnalisis').closest('.dataTables_wrapper');
                        var $length = wrapper.find('.dataTables_length').first();
                        var $filter = wrapper.find('.dataTables_filter').first();
                        var $controls = $('#analisisDtControls');
                        if ($controls.length && $length.length && $filter.length) {
                            $controls.append($length, $filter);
                        }
                    },
                    drawCallback: function() { renderizarTarjetasAna(); }
                });
                actualizarVistaInicialAna();
                function aplicarVistaAnalisis(vista) {
                    var w = $('#tablaAnalisisWrapper');
                    w.attr('data-vista', vista);
                    var esLista = (vista === 'lista' || vista === 'tabla');
                    $('#viewListaAnalisis').css('display', esLista ? 'block' : 'none');
                    $('#viewTarjetasAna').css('display', esLista ? 'none' : 'block');
                    $('#btnViewTablaAna').toggleClass('active', esLista);
                    $('#btnViewIconosAna').toggleClass('active', !esLista);
                    if (esLista) {
                        var filterEl = $('#analisisIconosControls .dataTables_filter').detach();
                        if (filterEl.length) $('#analisisDtControls').append(filterEl);
                        $('#analisisIconosControls').hide();
                        $('#analisisDtControls').show();
                    } else {
                        var filterEl = $('#analisisDtControls .dataTables_filter').detach();
                        if (filterEl.length) $('#analisisIconosControls').append(filterEl);
                        $('#analisisDtControls').hide();
                        $('#analisisIconosControls').show();
                        if (typeof renderizarTarjetasAna === 'function') renderizarTarjetasAna();
                    }
                }
                $('#btnViewIconosAna').on('click', function() { aplicarVistaAnalisis('iconos'); });
                $('#btnViewTablaAna').on('click', function() { aplicarVistaAnalisis('lista'); });
                aplicarVistaAnalisis($('#tablaAnalisisWrapper').attr('data-vista') || 'lista');
                $(window).on('resize', function() {
                    if (!$('#tablaAnalisisWrapper').attr('data-vista')) return;
                    actualizarVistaInicialAna();
                });
                $(document).on('click', '.btn-editar-card-ana', function() {
                    var c = $(this).closest('.card-item');
                    openAnalisisModal('update', c.attr('data-codigo'), c.attr('data-nombre') || '', c.attr('data-enfermedad') || '');
                });
                $(document).on('click', '.btn-eliminar-card-ana', function() {
                    var cod = $(this).attr('data-codigo');
                    var nom = $(this).attr('data-nombre') || '';
                    if (cod !== undefined) confirmDelete(cod, nom);
                });
            });

            async function confirmDelete(codigo, nombre) {
                var ok = await SwalConfirm('¿Eliminar el análisis "' + nombre + '"?\n\nSi está asociado a paquetes, no se podrá eliminar.', 'Confirmar eliminación');
                if (!ok) return;
                fetch('crud_analisis.php', {
                        method: 'POST',
                        body: new URLSearchParams({ action: 'delete', codigo: codigo })
                    })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) {
                                SwalAlert(d.message, 'success').then(() => location.reload());
                            } else {
                                SwalAlert(d.message, 'error');
                            }
                        });
            }
        </script>
</body>

</html>