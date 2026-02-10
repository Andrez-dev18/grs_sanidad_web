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
    <title>Dashboard - Tipos de Programa</title>

    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../../assets/js/sweetalert-helpers.js"></script>

    <style>
        body { background: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .card { transition: all 0.3s ease; cursor: pointer; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); }
        .icon-box { width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border-radius: 16px; margin: 0 auto 1rem; font-size: 2.5rem; }
        .btn-export { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3); color: #fff; }
        .btn-export:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); transform: translateY(-2px); }
        .btn-secondary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3); color: #fff; }
        .btn-secondary:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); transform: translateY(-2px); }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <div id="viewTiposPrograma" class="content-view">
            <div class="form-container max-w-7xl mx-auto">
                <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
                    <div class="dashboard-actions flex flex-col sm:flex-row justify-end sm:justify-between items-stretch sm:items-center gap-3 px-4 sm:px-6 py-4">
                        <a href="exportar_tipo_programa.php" class="btn-export inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-2 sm:order-1">📊 Exportar a Excel</a>
                        <button type="button" class="btn-secondary inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-1 sm:order-2" onclick="openModal('create')">➕ Nuevo Tipo de Programa</button>
                    </div>
                </div>
                <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
                <div id="tablaTipoProgramaWrapper" class="p-4" data-vista-tabla-iconos data-vista="tabla">
                    <div class="view-toggle-group flex items-center gap-2 mb-4">
                        <button type="button" class="view-toggle-btn active" id="btnViewTablaTipoProg" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                        <button type="button" class="view-toggle-btn" id="btnViewIconosTipoProg" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                    </div>
                    <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetasTipoProg">
                        <div id="cardsContainerTipoProg" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                    </div>
                    <div class="view-lista-wrap table-container overflow-x-auto">
                        <table id="tablaTipoPrograma" class="data-table w-full config-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">N°</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Nombre</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Sigla</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tipoProgramaTableBody" class="divide-y divide-gray-200">
                                <?php
                                $query = "SELECT codigo, nombre, sigla, campoUbicacion, campoProducto, campoUnidades, campoUnidadDosis, campoNumeroFrascos, campoEdadAplicacion, campoAreaGalpon, campoCantidadPorGalpon FROM san_dim_tipo_programa ORDER BY nombre";
                                $result = mysqli_query($conexion, $query);
                                if ($result && mysqli_num_rows($result) > 0) {
                                    $idx = 0;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $idx++;
                                        $cod = (int) $row['codigo'];
                                        $nom = htmlspecialchars($row['nombre']);
                                        $sigla = htmlspecialchars($row['sigla'] ?? '');
                                        $nomAttr = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
                                        $siglaAttr = htmlspecialchars($row['sigla'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $campos = [
                                            'ubicacion' => isset($row['campoUbicacion']) ? (int)$row['campoUbicacion'] : 0,
                                            'producto' => isset($row['campoProducto']) ? (int)$row['campoProducto'] : 0,
                                            'unidades' => isset($row['campoUnidades']) ? (int)$row['campoUnidades'] : 0,
                                            'unidad_dosis' => isset($row['campoUnidadDosis']) ? (int)$row['campoUnidadDosis'] : 0,
                                            'numero_frascos' => isset($row['campoNumeroFrascos']) ? (int)$row['campoNumeroFrascos'] : 0,
                                            'edad_aplicacion' => isset($row['campoEdadAplicacion']) ? (int)$row['campoEdadAplicacion'] : 0,
                                            'area_galpon' => isset($row['campoAreaGalpon']) ? (int)$row['campoAreaGalpon'] : 0,
                                            'cantidad_por_galpon' => isset($row['campoCantidadPorGalpon']) ? (int)$row['campoCantidadPorGalpon'] : 0
                                        ];
                                        $camposAttr = htmlspecialchars(json_encode($campos), ENT_QUOTES, 'UTF-8');
                                        echo '<tr class="hover:bg-gray-50 transition" data-codigo="' . $cod . '" data-nombre="' . $nomAttr . '" data-sigla="' . $siglaAttr . '" data-campos="' . $camposAttr . '" data-index="' . $idx . '">';
                                        echo '<td class="px-6 py-4 text-gray-700">' . $idx . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700 font-medium">' . $nom . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700">' . $sigla . '</td>';
                                        echo '<td class="px-6 py-4 flex gap-2">
                                        <button class="btn-icon p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar" onclick="openModalEditFromRow(this)">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <button class="btn-icon p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" onclick="confirmDelete(' . $cod . ')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No hay tipos de programa registrados</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <!-- Modal: scroll solo en el contenido; cabecera y footer fijos para que no quede espacio bajo los botones -->
        <div id="tipoProgramaModal" style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto overflow-x-hidden bg-black bg-opacity-50 flex items-center justify-center py-4 px-3 sm:py-6 sm:px-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-4xl mx-auto my-auto flex flex-col max-h-[calc(100vh-2rem)] max-h-[calc(100dvh-2rem)] min-h-0 overflow-hidden">
                <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200 flex-shrink-0">
                    <h2 id="modalTitleTipoProg" class="text-lg sm:text-xl font-bold text-gray-800 truncate pr-2">➕ Nuevo Tipo de Programa</h2>
                    <button type="button" onclick="closeTipoProgramaModal()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition flex-shrink-0" aria-label="Cerrar">×</button>
                </div>
                <div class="p-4 sm:p-6 flex-1 min-h-0 overflow-y-auto">
                    <form id="tipoProgramaForm" onsubmit="return saveTipoPrograma(event)">
                        <input type="hidden" id="modalActionTipoProg" value="create">
                        <input type="hidden" id="editCodigoTipoProg" value="">
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del tipo de programa <span class="text-red-500">*</span></label>
                            <input type="text" id="modalNombreTipoProg" name="nombre" maxlength="100" placeholder="Ej: NECROPSIAS" required
                                class="w-full px-3 sm:px-4 py-2 sm:py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-base">
                        </div>
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sigla</label>
                            <input type="text" id="modalSiglaTipoProg" name="sigla" maxlength="20" placeholder="Ej: NC"
                                class="w-full px-3 sm:px-4 py-2 sm:py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-base">
                            <p class="mt-1 text-xs text-gray-500">Se usará en el código del programa (ej.: NC en <span class="font-mono bg-gray-100 px-1 rounded">NC-0001</span>).</p>
                        </div>
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Campos a Registrar</label>
                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-8 gap-x-3 gap-y-2">
                                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="campoUbicacion" id="modalCampoUbicacion" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Ubicación</label>
                                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="campoProducto" id="modalCampoProducto" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Producto</label>
                                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="campoUnidades" id="modalCampoUnidades" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Unidades</label>
                                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="campoUnidadDosis" id="modalCampoUnidadDosis" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Unidad de dosis</label>
                                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="campoNumeroFrascos" id="modalCampoNumeroFrascos" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Número de frascos</label>
                                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="campoEdadAplicacion" id="modalCampoEdadAplicacion" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Edad de aplicación</label>
                                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="campoAreaGalpon" id="modalCampoAreaGalpon" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Area Galpón</label>
                                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="campoCantidadPorGalpon" id="modalCampoCantidadPorGalpon" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Cantidad x Galpón</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-2 rounded-b-2xl flex flex-col-reverse sm:flex-row flex-wrap gap-2 sm:gap-3 justify-end items-center flex-shrink-0">
                    <button type="button" onclick="closeTipoProgramaModal()"
                        class="w-full sm:w-auto px-4 sm:px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition duration-200 text-sm sm:text-base">Cancelar</button>
                    <button type="submit" form="tipoProgramaForm"
                        class="w-full sm:w-auto px-4 sm:px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center justify-center gap-2 text-sm sm:text-base">💾 Guardar</button>
                </div>
            </div>
        </div>

        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © <span id="currentYear"></span></p>
        </div>
        <script>document.getElementById('currentYear').textContent = new Date().getFullYear();</script>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="../../../assets/js/configuracion/tipo_programa.js"></script>
    <script>
    (function() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) return;
        var $t = jQuery('#tablaTipoPrograma');
        if ($t.length && !$t.hasClass('dataTable')) {
            $t.DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                order: [[1, 'asc']],
                columnDefs: [{ orderable: false, targets: [0, 3] }]
            });
        }
    })();
    </script>
</body>

</html>
