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

$query = "SELECT v.codigo, v.codProducto, v.descripcion, m.dosis FROM san_dim_vacuna v LEFT JOIN mitm m ON m.codigo = v.codProducto ORDER BY v.descripcion";
$result = mysqli_query($conexion, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Vacuna</title>
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
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">
        <div class="form-container max-w-7xl mx-auto">
            <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">
                <div class="dashboard-actions flex flex-col sm:flex-row justify-end sm:justify-between items-stretch sm:items-center gap-3 px-4 sm:px-6 py-4">
                    <button type="button" class="btn-secondary inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg font-medium order-1 sm:order-2" onclick="openModal('create')">➕ Nueva vacuna</button>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-5">
                <div class="card-body p-0 mt-5">
                <div class="table-wrapper overflow-x-auto">
                <table id="tablaVacuna" class="data-table display w-full text-sm border-collapse config-table" style="width:100%">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">N°</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Cód. Producto</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Descripción</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Dosis</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && mysqli_num_rows($result) > 0) {
                            $idx = 0;
                            while ($row = mysqli_fetch_assoc($result)) {
                                $idx++;
                                $cod = (int)$row['codigo'];
                                $codProd = htmlspecialchars($row['codProducto'] ?? '');
                                $desc = htmlspecialchars($row['descripcion'] ?? '');
                                $dosis = htmlspecialchars($row['dosis'] ?? '');
                                $codAttr = htmlspecialchars($cod, ENT_QUOTES, 'UTF-8');
                                $codProdAttr = htmlspecialchars($row['codProducto'] ?? '', ENT_QUOTES, 'UTF-8');
                                $descAttr = htmlspecialchars($row['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
                                echo '<tr data-codigo="' . $codAttr . '" data-codproducto="' . $codProdAttr . '" data-descripcion="' . $descAttr . '">';
                                echo '<td class="px-6 py-4 text-gray-700">' . $idx . '</td>';
                                echo '<td class="px-6 py-4 text-gray-700">' . $codProd . '</td>';
                                echo '<td class="px-6 py-4 text-gray-700 font-medium">' . $desc . '</td>';
                                echo '<td class="px-6 py-4 text-gray-700">' . $dosis . '</td>';
                                echo '<td class="px-6 py-4 flex gap-2">';
                                echo '<button type="button" class="btn-editar p-2 text-blue-600 hover:bg-blue-100 rounded-lg" title="Editar" data-codigo="' . $codAttr . '" data-codproducto="' . $codProdAttr . '" data-descripcion="' . $descAttr . '"><i class="fa-solid fa-edit"></i></button>';
                                echo '<button type="button" class="btn-eliminar p-2 text-red-600 hover:bg-red-100 rounded-lg" title="Eliminar" data-codigo="' . $codAttr . '"><i class="fa-solid fa-trash"></i></button>';
                                echo '</td></tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay vacunas. Use "Nueva vacuna".</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                </div>
                </div><!-- /card-body -->
            </div>

        <div id="vacunaModal" style="display: none;" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="modalTitle" class="text-xl font-bold text-gray-800">➕ Nueva vacuna</h2>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
                </div>
                <div class="p-6">
                    <form id="formVacuna" onsubmit="return save(event)">
                        <input type="hidden" id="modalAction" value="create">
                        <input type="hidden" id="codigoActual" value="">
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Código <span class="text-red-500">*</span></label>
                            <input type="number" id="inputCodigo" name="codigo" min="1" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                        </div>
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cód. Producto <span class="text-gray-400">(opcional)</span></label>
                            <input type="text" id="inputCodProducto" name="codProducto" maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg" placeholder="Ej: código en mitm">
                        </div>
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descripción <span class="text-red-500">*</span></label>
                            <input type="text" id="inputDescripcion" name="descripcion" maxlength="500" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                        </div>
                        <p class="text-xs text-gray-500 mb-4">La dosis se gestiona en Configuración → Productos (tabla mitm).</p>
                        <div class="flex gap-3 justify-end">
                            <button type="button" onclick="closeModal()" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 rounded-lg">Cancelar</button>
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg">💾 Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="text-center mt-12 text-gray-500 text-sm">
            <p>Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © <span id="currentYear"></span></p>
        </div>
        <script>document.getElementById('currentYear').textContent = new Date().getFullYear();</script>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="../../../assets/js/configuracion/vacuna.js"></script>
    <script>
    (function() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) return;
        var $t = jQuery('#tablaVacuna');
        if ($t.length && !$t.hasClass('dataTable')) {
            $t.DataTable({
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
