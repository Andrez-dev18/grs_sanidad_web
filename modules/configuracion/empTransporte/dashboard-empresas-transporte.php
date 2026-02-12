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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Empresas de Transporte</title>

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
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .icon-box {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
        }

        .logo-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .logo-container img {
            width: 90%;
            height: 90%;
            object-fit: contain;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- VISTA EMPRESAS DE TRANSPORTE -->
        <div id="viewEmpresasTransporte" class="content-view">
            
            <div class="form-container max-w-7xl mx-auto">
                <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
                    <div class="dashboard-actions flex flex-col sm:flex-row justify-end sm:justify-between items-stretch sm:items-center gap-3 px-4 sm:px-6 py-4">
                        <a href="exportar_empresas_transporte.php" class="btn-export inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-2 sm:order-1">📊 Exportar a Excel</a>
                        <button type="button" class="btn-secondary inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-1 sm:order-2" onclick="openModal('create')">➕ Nueva Empresa</button>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-5 dashboard-tabla-wrapper" id="tablaEmpTransWrapper" data-vista="">
                    <div class="card-body p-0 mt-5">
                    <div class="reportes-toolbar-row flex flex-wrap items-center justify-between gap-3 mb-3">
                        <div class="view-toggle-group flex items-center gap-2">
                            <button type="button" class="view-toggle-btn active" id="btnViewTablaEmpTrans" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                            <button type="button" class="view-toggle-btn" id="btnViewIconosEmpTrans" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                        </div>
                        <div id="empTransDtControls" class="toolbar-dt-controls flex flex-wrap items-center gap-3"></div>
                        <div id="empTransIconosControls" class="toolbar-iconos-controls flex flex-wrap items-center gap-3" style="display: none;"></div>
                    </div>
                    <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetasEmpTrans">
                        <div id="cardsControlsTopEmpTrans" class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-gray-600 border-b border-gray-200 pb-3"></div>
                        <div id="cardsContainerEmpTrans" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                        <div id="cardsPaginationEmpTrans" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3" data-table="#tablaEmpTrans"></div>
                    </div>
                    <div class="view-lista-wrap" id="viewListaEmpTrans">
                    <div class="table-wrapper overflow-x-auto">
                        <table id="tablaEmpTrans" class="data-table display w-full text-sm border-collapse config-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">N°</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Nombre de la Empresa</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="empTransTableBody" class="divide-y divide-gray-200">
                                <?php
                                $query = "SELECT codigo, nombre FROM san_dim_emptrans ORDER BY codigo";
                                $result = mysqli_query($conexion, $query);
                                if ($result && mysqli_num_rows($result) > 0) {
                                    $idx = 0;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $idx++;
                                        $cod = (int) $row['codigo'];
                                        $nom = htmlspecialchars($row['nombre']);
                                        $nomAttr = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
                                        echo '<tr data-codigo="' . $cod . '" data-nombre="' . $nomAttr . '" data-index="' . $idx . '">';
                                        echo '<td class="px-6 py-4 text-gray-700">' . $idx . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700 font-medium">' . $nom . '</td>';
                                        echo '<td class="px-6 py-4 flex gap-2">
                                        <button class="btn-icon p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar" onclick="openModal(\'edit\', ' . $cod . ', \'' . addslashes($row['nombre']) . '\')">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button class="btn-icon p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" onclick="confirmDelete(' . $cod . ')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr>';
                                    echo '<td colspan="3" class="px-6 py-8 text-center text-gray-500">No hay empresas de transporte registradas</td>';
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

        <!-- Modal para Crear/Editar Empresa de Transporte -->
        <div id="empTransModal" style="display: none;"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="modalTitle" class="text-xl font-bold text-gray-800">➕ Nueva Empresa de Transporte</h2>
                    <button onclick="closeEmpTransModal()"
                        class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition">
                        ×
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <form id="empTransForm" onsubmit="return saveEmpTrans(event)">
                        <input type="hidden" id="modalAction" value="create">
                        <input type="hidden" id="editCodigo" value="">

                        <!-- Campo Nombre -->
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre de la Empresa <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="modalNombre" name="nombre" maxlength="255"
                                placeholder="Ingrese el nombre de la empresa de transporte" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">

                        </div>

                        <!-- Botones -->
                        <div class="dashboard-modal-actions flex flex-col-reverse sm:flex-row flex-wrap gap-3 justify-end">
                            <button type="button" onclick="closeEmpTransModal()"
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

    <script src="../../../assets/js/pagination-iconos.js"></script>
    <script src="../../../assets/js/configuracion/empresas_transporte.js"></script>
    <script>
    (function() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) return;
        var $t = jQuery('#tablaEmpTrans');
        if ($t.length && !$t.hasClass('dataTable')) {
            $t.DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                order: [[1, 'asc']],
                columnDefs: [{ orderable: false, targets: [0, 2] }],
                initComplete: function() {
                    var wrapper = $t.closest('.dataTables_wrapper');
                    var $controls = jQuery('#empTransDtControls');
                    var $length = wrapper.find('.dataTables_length').first();
                    var $filter = wrapper.find('.dataTables_filter').first();
                    if ($controls.length && $length.length && $filter.length) {
                        $controls.append($length, $filter);
                    }
                }
            });
        }
    })();
    </script>

</body>

</html>