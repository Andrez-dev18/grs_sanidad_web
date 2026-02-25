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
include_once '../../../../conexion_grs/conexion.php';
$conexion = conectar_joya_mysqli();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
include_once __DIR__ . '/../../../includes/datatables_lang_es.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tipos de Muestra</title>

    <link href="../../../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>window.DATATABLES_LANG_ES = <?php echo $datatablesLangEs; ?>;</script>
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
        /* Tabla y DataTables: estilos generales en dashboard-config.css */
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- VISTA TIPOS DE MUESTRA -->
        <div id="viewTipoMuestra" class="content-view">


            <div class="form-container max-w-7xl mx-auto">
                <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
                    <div class="dashboard-actions flex flex-col sm:flex-row justify-end sm:justify-between items-stretch sm:items-center gap-3 px-4 sm:px-6 py-4">
                        <a href="exportar_tipo_muestra.php" class="btn-export inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-2 sm:order-1">📊 Exportar a Excel</a>
                        <button type="button" class="btn-secondary inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-1 sm:order-2" onclick="openTipoMuestraModal('create')">➕ Nuevo Tipo de Muestra</button>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-5 dashboard-tabla-wrapper" id="tablaTipoMuestraWrapper" data-vista="">
                    <div class="card-body p-0 mt-5">
                        <div class="reportes-toolbar-row flex flex-wrap items-center justify-between gap-3 mb-3">
                            <div class="view-toggle-group flex items-center gap-2">
                                <button type="button" class="view-toggle-btn active" id="btnViewTablaTM" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                                <button type="button" class="view-toggle-btn" id="btnViewIconosTM" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                            </div>
                            <div id="tipoMuestraDtControls" class="toolbar-dt-controls flex flex-wrap items-center gap-3"></div>
                            <div id="tipoMuestraIconosControls" class="toolbar-iconos-controls flex flex-wrap items-center gap-3" style="display: none;"></div>
                        </div>
                        <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetasTM">
                            <div id="cardsControlsTopTM" class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-gray-600 border-b border-gray-200 pb-3"></div>
                            <div id="cardsContainerTM" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                            <div id="cardsPaginationTM" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3" data-table="#tabla"></div>
                        </div>
                        <div class="view-lista-wrap" id="viewListaTM">
                        <div class="table-wrapper overflow-x-auto">
                            <table id="tabla" class="data-table display w-full text-sm border-collapse config-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-4 text-left text-sm font-semibold">N°</th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold">Nombre</th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold">Descripción</th>
                                        <th class="px-6 py-4 text-center text-sm font-semibold">Long. Código</th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tipoMuestraTableBody" class="divide-y divide-gray-200">
                                    <?php
                                    $query = "SELECT codigo, nombre, descripcion, lonCod FROM san_dim_tipo_muestra ORDER BY codigo";
                                    $result = mysqli_query($conexion, $query);
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        $idx = 0;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $idx++;
                                            $descripcion = htmlspecialchars($row['descripcion'] ?? '');
                                            $descripcion_corta = strlen($descripcion) > 50 ? substr($descripcion, 0, 50) . '...' : $descripcion;

                                            echo '<tr data-codigo="' . (int)$row['codigo'] . '">';
                                            echo '<td class="px-6 py-4 text-gray-700">' . $idx . '</td>';
                                            echo '<td class="px-6 py-4 text-gray-700 font-medium">' . htmlspecialchars($row['nombre']) . '</td>';
                                            echo '<td class="px-6 py-4 text-gray-600 text-sm" title="' . $descripcion . '">' . $descripcion_corta . '</td>';
                                            echo '<td class="px-6 py-4 text-center text-gray-700">' . htmlspecialchars($row['lonCod']) . '</td>';
                                            echo '<td class="px-6 py-4 flex gap-2">
                                        <button class="btn-icon p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" 
                                                title="Editar" 
                                                onclick="openTipoMuestraModal(\'edit\', ' . (int) $row['codigo'] . ', \'' .
                                                addslashes(htmlspecialchars($row['nombre'])) . '\', \'' .
                                                addslashes(htmlspecialchars($row['descripcion'] ?? '')) . '\', ' .
                                                (int) $row['lonCod'] . ')">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button class="btn-icon p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" 
                                                title="Eliminar" 
                                                onclick="confirmTipoMuestraDelete(' . (int) $row['codigo'] . ')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr>';
                                        echo '<td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay tipos de muestra registrados</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Crear/Editar Tipo de Muestra -->
        <div id="tipoMuestraModal" style="display: none;"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-lg">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="tipoMuestraModalTitle" class="text-xl font-bold text-gray-800">➕ Nuevo Tipo de Muestra</h2>
                    <button onclick="closeTipoMuestraModal()"
                        class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition">
                        ×
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <form id="tipoMuestraForm" onsubmit="return saveTipoMuestra(event)">
                        <input type="hidden" id="tipoMuestraModalAction" value="create">
                        <input type="hidden" id="tipoMuestraEditCodigo" value="">

                        <!-- Campo Nombre -->
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre del Tipo de Muestra <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="tipoMuestraModalNombre" name="nombre" maxlength="100"
                                placeholder="Ingrese el nombre del tipo de muestra" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        </div>

                        <!-- Campo Descripción -->
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Descripción
                            </label>
                            <textarea id="tipoMuestraModalDescripcion" name="descripcion" rows="3" maxlength="500"
                                placeholder="Descripción opcional del tipo de muestra"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-none"></textarea>
                        </div>

                        <!-- Campo Longitud de Código -->
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Longitud de Código <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="tipoMuestraModalLongitud" name="longitud_codigo" min="1" max="20"
                                value="8" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            <p class="text-xs text-gray-500 mt-1">Define la cantidad de caracteres del código (1-20)</p>
                        </div>

                        <!-- Botones -->
                        <div class="dashboard-modal-actions flex flex-col-reverse sm:flex-row flex-wrap gap-3 justify-end">
                            <button type="button" onclick="closeTipoMuestraModal()"
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

    <script src="../../../assets/js/configuracion/tipo-muestra.js"></script>

    <script src="../../../assets/js/pagination-iconos.js"></script>

    <script>
        var tableTipoMuestra;
        var searchDebounceTM = null;
        var TM_LENGTH_OPTIONS = [20, 25, 50, 100];
        var tmPageLengthSel = 20;

        function normalizarPageLengthTM(v) {
            var n = parseInt(v, 10);
            return TM_LENGTH_OPTIONS.indexOf(n) >= 0 ? n : 20;
        }

        function renderizarControlesDtTM($container, rowClass) {
            if (!$container || !$container.length || !tableTipoMuestra) return;
            var rowSelector = '.' + rowClass;
            if (!$container.find(rowSelector).length) {
                var html = '<div class="' + rowClass + ' flex flex-wrap items-center gap-3">' +
                    '<div class="dataTables_length"><label>Mostrar ' +
                    '<select class="js-tm-len dt-toolbar-length-select cards-length-select">' +
                    '<option value="20">20</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>' +
                    '</select> registros</label></div>' +
                    '<div class="dataTables_filter"><label>Buscar <input type="search" class="js-tm-search" placeholder=""></label></div>' +
                    '</div>';
                $container.html(html);
            }
            if (!$container.data('tmControlsBound')) {
                $container.on('change.tm', '.js-tm-len', function () {
                    var val = parseInt($(this).val(), 10);
                    if (tableTipoMuestra && !isNaN(val)) {
                        tmPageLengthSel = normalizarPageLengthTM(val);
                        tableTipoMuestra.page.len(tmPageLengthSel).draw(false);
                    }
                });
                $container.on('input.tm', '.js-tm-search', function () {
                    var val = ($(this).val() || '').toString();
                    if (searchDebounceTM) clearTimeout(searchDebounceTM);
                    searchDebounceTM = setTimeout(function () {
                        if (tableTipoMuestra && tableTipoMuestra.search() !== val) tableTipoMuestra.search(val).draw();
                    }, 220);
                });
                $container.data('tmControlsBound', true);
            }
            var len = String(tableTipoMuestra.page.len());
            var search = tableTipoMuestra.search() || '';
            var $len = $container.find('.js-tm-len');
            var $search = $container.find('.js-tm-search');
            if ($len.val() !== len) $len.val(len);
            if (!$search.is(':focus') && $search.val() !== search) $search.val(search);
        }

        function sincronizarControlesTM() {
            if (!tableTipoMuestra) return;
            renderizarControlesDtTM($('#tipoMuestraDtControls'), 'tipo-muestra-dt-toolbar-row');
            renderizarControlesDtTM($('#tipoMuestraIconosControls'), 'tipo-muestra-iconos-toolbar-row');
        }

        function actualizarVistaInicialTM() {
            var w = $(window).width();
            var w$ = $('#tablaTipoMuestraWrapper');
            if (!w$.attr('data-vista')) {
                w$.attr('data-vista', w < 768 ? 'iconos' : 'tabla');
                $('#btnViewIconosTM').toggleClass('active', w$.attr('data-vista') === 'iconos');
                $('#btnViewTablaTM').toggleClass('active', w$.attr('data-vista') === 'tabla');
            }
            var vista = w$.attr('data-vista') || 'tabla';
            $('#tipoMuestraDtControls').toggle(vista === 'tabla');
            $('#tipoMuestraIconosControls').toggle(vista === 'iconos');
            sincronizarControlesTM();
        }

        function renderizarTarjetasTM() {
            if (!tableTipoMuestra) return;
            var api = tableTipoMuestra;
            var cont = $('#cardsContainerTM');
            cont.empty();
            var info = api.page.info();
            var rowIndex = 0;
            api.rows({
                page: 'current'
            }).every(function() {
                rowIndex++;
                var numero = info.start + rowIndex;
                var $row = $(this.node());
                var cells = $row.find('td');
                if (cells.length < 5) return;
                var codigo = $row.attr('data-codigo') || $(cells[0]).text().trim();
                var nombre = $(cells[1]).text().trim();
                var desc = $(cells[2]).text().trim();
                var lonCod = $(cells[3]).text().trim();
                var codAttr = (codigo + '').replace(/"/g, '&quot;');
                var nomAttr = (nombre + '').replace(/"/g, '&quot;');
                var descAttr = (desc + '').replace(/"/g, '&quot;');
                var card = $('<div class="card-item" data-codigo="' + codAttr + '" data-nombre="' + nomAttr + '" data-descripcion="' + descAttr + '" data-loncod="' + (lonCod + '').replace(/"/g, '&quot;') + '">' +
                    '<div class="card-numero-row">#' + numero + '</div>' +
                    '<div class="card-row"><span class="label">Nombre:</span> ' + $('<div>').text(nombre).html() + '</div>' +
                    '<div class="card-row"><span class="label">Descripción:</span> ' + $('<div>').text(desc).html() + '</div>' +
                    '<div class="card-row"><span class="label">Long. Código:</span> ' + $('<div>').text(lonCod).html() + '</div>' +
                    '<div class="card-acciones">' +
                    '<button type="button" class="btn-editar-card-tm p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar"><i class="fa-solid fa-edit"></i></button>' +
                    '<button type="button" class="btn-eliminar-card-tm p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" data-codigo="' + codAttr + '"><i class="fa-solid fa-trash"></i></button>' +
                    '</div></div>');
                cont.append(card);
            });
            var info = api.page.info();
            $('#cardsPaginationTM').html(typeof buildPaginationIconos === 'function' ? buildPaginationIconos(info) : ('<span class="dataTables_info">Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros</span>'));
            sincronizarControlesTM();
        }
        $(document).ready(function() {
            tableTipoMuestra = $('#tabla').DataTable({
                dom: 'rtip',
                language: (typeof window.DATATABLES_LANG_ES !== 'undefined' ? window.DATATABLES_LANG_ES : {}),
                pageLength: normalizarPageLengthTM(tmPageLengthSel),
                lengthMenu: [[20, 25, 50, 100], [20, 25, 50, 100]],
                ordering: false,
                order: [[0, 'asc']],
                orderClasses: false,
                columnDefs: [{ orderable: false, targets: [4] }],
                drawCallback: function() {
                    renderizarTarjetasTM();
                },
                initComplete: function() {
                    sincronizarControlesTM();
                }
            });
            actualizarVistaInicialTM();
            $('#btnViewIconosTM').on('click', function() {
                $('#tablaTipoMuestraWrapper').attr('data-vista', 'iconos');
                $('#btnViewIconosTM').addClass('active');
                $('#btnViewTablaTM').removeClass('active');
                $('#tipoMuestraDtControls').hide();
                $('#tipoMuestraIconosControls').show();
                sincronizarControlesTM();
            });
            $('#btnViewTablaTM').on('click', function() {
                $('#tablaTipoMuestraWrapper').attr('data-vista', 'tabla');
                $('#btnViewTablaTM').addClass('active');
                $('#btnViewIconosTM').removeClass('active');
                $('#tipoMuestraDtControls').show();
                $('#tipoMuestraIconosControls').hide();
                sincronizarControlesTM();
            });
            $(window).on('resize', function() {
                if (!$('#tablaTipoMuestraWrapper').attr('data-vista')) return;
                actualizarVistaInicialTM();
            });
            $(document).on('click', '.btn-editar-card-tm', function() {
                var c = $(this).closest('.card-item');
                openTipoMuestraModal('edit', parseInt(c.attr('data-codigo'), 10) || c.attr('data-codigo'), c.attr('data-nombre') || '', c.attr('data-descripcion') || '', parseInt(c.attr('data-loncod'), 10) || c.attr('data-loncod'));
            });
            $(document).on('click', '.btn-eliminar-card-tm', function() {
                var cod = $(this).attr('data-codigo');
                if (cod !== undefined) confirmTipoMuestraDelete(isNaN(cod) ? cod : parseInt(cod, 10));
            });
        });
    </script>
</body>

</html>