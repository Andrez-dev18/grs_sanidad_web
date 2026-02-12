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
    die("Error de conexión: " . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Enfermedades</title>
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
        body { background: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; min-height: 100vh; }
        .card { transition: all 0.3s ease; cursor: pointer; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); }
        @media (max-width: 640px) {
            .container.mx-auto { padding-left: 0.75rem; padding-right: 0.75rem; padding-top: 1rem; padding-bottom: 1.5rem; }
            .dashboard-actions { flex-direction: column; align-items: stretch; }
            .dashboard-actions .btn, .dashboard-actions button { width: 100%; justify-content: center; }
            #tablaEnfermedadesWrapper { padding: 0.75rem; border-radius: 1rem; }
            .data-table th, .data-table td { padding: 0.5rem 0.75rem; font-size: 0.8125rem; }
            .data-table th:first-child, .data-table td:first-child { min-width: 2.5rem; }
            .text-center.mt-12 { margin-top: 2rem; padding: 0 0.5rem; }
        }
        #enfermedadesModal { min-height: 100vh; min-height: 100dvh; align-items: center; justify-content: center; padding: 0.75rem; padding-top: max(0.75rem, env(safe-area-inset-top)); padding-bottom: max(0.75rem, env(safe-area-inset-bottom)); overflow-y: auto; -webkit-overflow-scrolling: touch; }
        #enfermedadesModal .modal-inner { width: 100%; max-width: 28rem; max-height: calc(100vh - 1.5rem); max-height: calc(100dvh - 1.5rem); display: flex; flex-direction: column; flex-shrink: 0; }
        #enfermedadesModal .modal-header { flex-shrink: 0; padding: 1rem 1.25rem; }
        #enfermedadesModal .modal-body { flex: 1; min-height: 0; overflow-y: auto; padding: 1rem 1.25rem; -webkit-overflow-scrolling: touch; }
        #enfermedadesModal .modal-title { font-size: 1.125rem; line-height: 1.4; word-break: break-word; }
        @media (min-width: 480px) {
            #enfermedadesModal .modal-inner { max-height: calc(100vh - 2rem); max-height: calc(100dvh - 2rem); }
            #enfermedadesModal .modal-header, #enfermedadesModal .modal-body { padding: 1.5rem; }
            #enfermedadesModal .modal-title { font-size: 1.25rem; }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 py-6 sm:py-12 max-w-full">

        <div id="viewEnfermedades" class="content-view">
            <div class="form-container max-w-7xl mx-auto">
                <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
                    <div class="dashboard-actions flex flex-col sm:flex-row justify-end sm:justify-between items-stretch sm:items-center gap-3 px-4 sm:px-6 py-4">
                        <a href="exportar_enfermedades.php" class="btn-export inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-2 sm:order-1">📊 Exportar a Excel</a>
                        <button type="button" class="btn-secondary inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-1 sm:order-2" onclick="openModal('create')">➕ Nueva Enfermedad</button>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-5 dashboard-tabla-wrapper" id="tablaEnfermedadesWrapper" data-vista="">
                    <div class="card-body p-0 mt-5">
                    <div class="reportes-toolbar-row flex flex-wrap items-center justify-between gap-3 mb-3">
                        <div class="view-toggle-group flex items-center gap-2">
                            <button type="button" class="view-toggle-btn active" id="btnViewTablaEnfermedades" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                            <button type="button" class="view-toggle-btn" id="btnViewIconosEnfermedades" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                        </div>
                        <div id="enfermedadesDtControls" class="toolbar-dt-controls flex flex-wrap items-center gap-3"></div>
                        <div id="enfermedadesIconosControls" class="toolbar-iconos-controls flex flex-wrap items-center gap-3" style="display: none;"></div>
                    </div>
                    <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetasEnfermedades">
                        <div id="cardsControlsTopEnfermedades" class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-gray-600 border-b border-gray-200 pb-3"></div>
                        <div id="cardsContainerEnfermedades" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                        <div id="cardsPaginationEnfermedades" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3" data-table="#tablaEnfermedades"></div>
                    </div>
                    <div class="view-lista-wrap" id="viewListaEnfermedades">
                    <div class="table-wrapper overflow-x-auto">
                        <table id="tablaEnfermedades" class="data-table display w-full text-sm border-collapse config-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">N°</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Nombre</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="enfermedadesTableBody" class="divide-y divide-gray-200">
                                <?php
                                $query = "SELECT cod_enf, nom_enf FROM tenfermedades ORDER BY nom_enf";
                                $result = mysqli_query($conexion, $query);
                                if ($result && mysqli_num_rows($result) > 0) {
                                    $idx = 0;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $idx++;
                                        $cod = (int) $row['cod_enf'];
                                        $nom = htmlspecialchars($row['nom_enf']);
                                        $nomAttr = htmlspecialchars($row['nom_enf'], ENT_QUOTES, 'UTF-8');
                                        echo '<tr data-codigo="' . $cod . '" data-nombre="' . $nomAttr . '" data-index="' . $idx . '">';
                                        echo '<td class="px-6 py-4 text-gray-700">' . $idx . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700 font-medium">' . $nom . '</td>';
                                        echo '<td class="px-6 py-4 flex gap-2">';
                                        echo '<button class="btn-icon p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar" onclick="openModal(\'edit\', ' . $cod . ', \'' . addslashes($row['nom_enf']) . '\')"><i class="fa-solid fa-edit"></i></button>';
                                        echo '<button class="btn-icon p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" onclick="confirmDelete(' . $cod . ')"><i class="fa-solid fa-trash"></i></button>';
                                        echo '</td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">No hay enfermedades registradas</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar -->
        <div id="enfermedadesModal" style="display: none;" class="fixed inset-0 bg-black/50 flex justify-center z-50 overflow-y-auto">
            <div class="modal-inner bg-white rounded-xl sm:rounded-2xl shadow-xl w-full my-auto">
                <div class="modal-header flex items-center justify-between border-b border-gray-200 gap-3">
                    <h2 id="modalTitleEnfermedades" class="modal-title font-bold text-gray-800 min-w-0">➕ Nueva Enfermedad</h2>
                    <button type="button" onclick="closeEnfermedadesModal()" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition touch-manipulation" aria-label="Cerrar">×</button>
                </div>
                <div class="modal-body">
                    <form id="enfermedadesForm" onsubmit="return saveEnfermedad(event)">
                        <input type="hidden" id="modalActionEnfermedades" value="create">
                        <input type="hidden" id="editCodEnf" value="">
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                            <input type="text" id="modalNombreEnfermedad" name="nom_enf" maxlength="255" placeholder="Ingrese el nombre de la enfermedad" required
                                class="w-full min-w-0 px-3 sm:px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-base">
                        </div>
                        <div class="dashboard-modal-actions flex flex-col-reverse sm:flex-row flex-wrap gap-3 justify-end">
                            <button type="button" onclick="closeEnfermedadesModal()" class="w-full sm:w-auto px-4 sm:px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition duration-200 touch-manipulation">Cancelar</button>
                            <button type="submit" class="w-full sm:w-auto btn btn-primary px-4 sm:px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center justify-center gap-2 touch-manipulation">💾 Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="text-center mt-8 sm:mt-12 px-2">
            <p class="text-gray-500 text-xs sm:text-sm">Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © <span id="currentYear"></span></p>
        </div>
        <script>document.getElementById('currentYear').textContent = new Date().getFullYear();</script>
    </div>
    <script src="../../../assets/js/pagination-iconos.js"></script>
    <script src="../../../assets/js/configuracion/enfermedades.js"></script>
    <script>
    (function() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) return;
        var $t = jQuery('#tablaEnfermedades');
        if ($t.length && !$t.hasClass('dataTable')) {
            $t.DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                order: [[1, 'asc']],
                columnDefs: [{ orderable: false, targets: [0, 2] }]
            });
        }
    })();
    </script>
</body>

</html>
