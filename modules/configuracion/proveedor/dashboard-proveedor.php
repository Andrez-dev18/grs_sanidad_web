<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; }
        else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Proveedores = registros de ccte con proveedor_programa = 1
$query = "SELECT codigo, nombre, codigo_proveedor FROM ccte WHERE COALESCE(proveedor_programa, 0) = 1 ORDER BY nombre";
$result_proveedores = mysqli_query($conexion, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Proveedores</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../../assets/js/sweetalert-helpers.js"></script>
    <style>
        body { background: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; min-height: 100vh; }
        .card { transition: all 0.3s ease; cursor: pointer; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .select2-container .select2-selection--single { height: 42px; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 6px 12px; display: flex; align-items: center; }
        .select2-selection__rendered { font-size: 0.875rem; color: #374151; }
        .select2-selection__arrow { height: 100%; }
        .select2-dropdown { border-radius: 0.5rem; border: 1px solid #d1d5db; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #3b82f6; color: #fff; }
        .select2-container--default .select2-search--dropdown .select2-search__field { border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.5rem 0.75rem; }
        /* Responsivo: pantalla completa */
        @media (max-width: 640px) {
            .container.mx-auto { padding-left: 0.75rem; padding-right: 0.75rem; padding-top: 1rem; padding-bottom: 1.5rem; }
            .dashboard-actions { flex-direction: column; align-items: stretch; }
            .dashboard-actions .btn, .dashboard-actions button { width: 100%; justify-content: center; }
            #tablaProveedorWrapper { padding: 0.75rem; border-radius: 1rem; }
            .data-table th, .data-table td { padding: 0.5rem 0.75rem; font-size: 0.8125rem; }
            .data-table th:first-child, .data-table td:first-child { min-width: 2.5rem; }
            .text-center.mt-12 { margin-top: 2rem; padding: 0 0.5rem; }
        }
        /* Modal responsivo */
        #proveedorModal { min-height: 100vh; min-height: 100dvh; align-items: center; justify-content: center; padding: 0.75rem; padding-top: max(0.75rem, env(safe-area-inset-top)); padding-bottom: max(0.75rem, env(safe-area-inset-bottom)); overflow-y: auto; -webkit-overflow-scrolling: touch; }
        #proveedorModal .modal-inner { width: 100%; max-width: 28rem; max-height: calc(100vh - 1.5rem); max-height: calc(100dvh - 1.5rem); display: flex; flex-direction: column; flex-shrink: 0; }
        #proveedorModal .modal-header { flex-shrink: 0; padding: 1rem 1.25rem; }
        #proveedorModal .modal-body { flex: 1; min-height: 0; overflow-y: auto; padding: 1rem 1.25rem; -webkit-overflow-scrolling: touch; }
        #proveedorModal .modal-title { font-size: 1.125rem; line-height: 1.4; word-break: break-word; }
        @media (min-width: 480px) {
            #proveedorModal .modal-inner { max-height: calc(100vh - 2rem); max-height: calc(100dvh - 2rem); }
            #proveedorModal .modal-header, #proveedorModal .modal-body { padding: 1.5rem; }
            #proveedorModal .modal-title { font-size: 1.25rem; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 py-6 sm:py-12 max-w-full">

        <div id="viewProveedor" class="content-view">
            <div class="form-container max-w-7xl mx-auto">
                <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
                    <div class="dashboard-actions flex flex-col sm:flex-row justify-end sm:justify-between items-stretch sm:items-center gap-3 px-4 sm:px-6 py-4">
                        <a href="exportar_proveedor.php" class="btn-export inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-2 sm:order-1">📊 Exportar a Excel</a>
                        <button type="button" class="btn-secondary inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-1 sm:order-2" onclick="openModal('create')">➕ Nuevo Proveedor</button>
                    </div>
                </div>
                <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
                <div id="tablaProveedorWrapper" class="p-3 sm:p-4 min-w-0" data-vista-tabla-iconos data-vista="tabla">
                    <div class="view-toggle-group flex items-center gap-2 mb-3 sm:mb-4">
                        <button type="button" class="view-toggle-btn active" id="btnViewTablaProveedor" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                        <button type="button" class="view-toggle-btn" id="btnViewIconosProveedor" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                    </div>
                    <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetasProveedor">
                        <div id="cardsContainerProveedor" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                    </div>
                    <div class="view-lista-wrap table-container overflow-x-auto">
                        <table id="tablaProveedor" class="data-table w-full config-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">N°</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Código</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Nombre</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Abreviatura</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="proveedorTableBody" class="divide-y divide-gray-200">
                                <?php
                                if ($result_proveedores && mysqli_num_rows($result_proveedores) > 0) {
                                    $idx = 0;
                                    while ($row = mysqli_fetch_assoc($result_proveedores)) {
                                        $idx++;
                                        $cod = (string)$row['codigo'];
                                        $nom = htmlspecialchars($row['nombre']);
                                        $sigla = htmlspecialchars($row['codigo_proveedor'] ?? '');
                                        $codAttr = htmlspecialchars($cod, ENT_QUOTES, 'UTF-8');
                                        $nomAttr = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
                                        $siglaAttr = htmlspecialchars($row['codigo_proveedor'] ?? '', ENT_QUOTES, 'UTF-8');
                                        echo '<tr class="hover:bg-gray-50 transition" data-codigo="' . $codAttr . '" data-nombre="' . $nomAttr . '" data-sigla="' . $siglaAttr . '" data-index="' . $idx . '">';
                                        echo '<td class="px-6 py-4 text-gray-700">' . $idx . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700">' . $codAttr . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700 font-medium">' . $nom . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700">' . $sigla . '</td>';
                                        echo '<td class="px-6 py-4 flex gap-2">';
                                        echo '<button type="button" class="btn-editar-proveedor btn-icon p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar" data-codigo="' . $codAttr . '" data-nombre="' . $nomAttr . '" data-sigla="' . $siglaAttr . '"><i class="fa-solid fa-edit"></i></button>';
                                        echo '<button type="button" class="btn-icon p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition btn-eliminar-proveedor" title="Eliminar" data-codigo="' . $codAttr . '"><i class="fa-solid fa-trash"></i></button>';
                                        echo '</td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay proveedores registrados. Use "Nuevo Proveedor" y seleccione un registro de la lista.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar Proveedor -->
        <div id="proveedorModal" style="display: none;" class="fixed inset-0 bg-black/50 flex justify-center z-50 overflow-y-auto">
            <div class="modal-inner bg-white rounded-xl sm:rounded-2xl shadow-xl w-full my-auto">
                <div class="modal-header flex items-center justify-between border-b border-gray-200 gap-3">
                    <h2 id="modalTitleProveedor" class="modal-title font-bold text-gray-800 min-w-0">➕ Nuevo Proveedor</h2>
                    <button type="button" onclick="closeProveedorModal()" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition touch-manipulation" aria-label="Cerrar">×</button>
                </div>
                <div class="modal-body">
                    <form id="proveedorForm" onsubmit="return saveProveedor(event)">
                        <input type="hidden" id="modalActionProveedor" value="create">
                        <input type="hidden" id="editCodigoProveedor" value="">
                        <div class="form-field mb-4" id="wrapSelectCcte">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                            <select id="modalCcteProveedor" name="codigo_ccte" required
                                class="w-full min-w-0 px-3 sm:px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-base"
                                style="width: 100%;">
                                <option value="">Escriba para buscar...</option>
                            </select>
                        </div>
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Abreviatura</label>
                            <input type="text" id="modalSiglaProveedor" name="sigla" maxlength="50" placeholder="Ej: Abreviatura del proveedor"
                                class="w-full min-w-0 px-3 sm:px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-base">
                        </div>
                        <div class="dashboard-modal-actions flex flex-col-reverse sm:flex-row flex-wrap gap-3 justify-end">
                            <button type="button" onclick="closeProveedorModal()"
                                class="w-full sm:w-auto px-4 sm:px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition duration-200 touch-manipulation">Cancelar</button>
                            <button type="submit"
                                class="w-full sm:w-auto btn btn-primary px-4 sm:px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center justify-center gap-2 touch-manipulation">💾 Guardar</button>
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../../assets/js/configuracion/proveedor.js"></script>
    <script>
    (function() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) return;
        var $table = jQuery('#tablaProveedor');
        if ($table.length && !$table.hasClass('dataTable')) {
            jQuery('#tablaProveedor').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                order: [[1, 'asc']],
                columnDefs: [{ orderable: false, targets: [0, 4] }]
            });
        }
    })();
    </script>
</body>
</html>
