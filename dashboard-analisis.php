<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}
include_once '../conexion_grs_joya/conexion.php';
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
    <title>Dashboard - Análisis</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .paquete-completo-badge {
            display: inline-flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 8px 12px;
            min-width: 200px;
        }

        .paquete-line {
            font-size: 0.8rem;
            color: #166534;
            font-weight: 600;
            margin: 2px 0;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">
        <!-- VISTA ANÁLISIS -->
        <div id="viewAnalisis" class="content-view">
            <div class="content-header max-w-7xl mx-auto mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-4xl">🔍</span>
                    <h1 class="text-3xl font-bold text-gray-800">Análisis</h1>
                </div>
                <p class="text-gray-600 text-sm">Administre los análisis registrados en el sistema</p>
            </div>
            <div class="form-container max-w-7xl mx-auto">
                <div class="mb-6 flex justify-between items-center flex-wrap gap-3">
                    <button type="button"
                        class="px-6 py-2.5 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2"
                        onclick="exportarAnalisis()"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);">
                        📊 Exportar a Excel
                    </button>
                    <button type="button"
                        class="btn btn-primary px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2"
                        onclick="openAnalisisModal('create')">
                        ➕ Nuevo Análisis
                    </button>
                </div>

                <!-- Tabla de análisis -->
                <div class="table-container border border-gray-300 rounded-2xl bg-white overflow-x-auto">
                    <table class="data-table w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Código</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Nombre del Análisis
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Paquete y Tipo de
                                    Muestra</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="analisisTableBody" class="divide-y divide-gray-200">
                            <?php
                            // 🔁 Traemos paquete + tipo de muestra vía JOIN
                            $query = "SELECT 
                                        a.codigo,
                                        a.nombre,
                                        a.paquete,
                                        pm.codigo AS paq_codigo,
                                        pm.nombre AS paq_nombre,
                                        tm.codigo AS tm_codigo,
                                        tm.nombre AS tm_nombre
                                      FROM san_dim_analisis a
                                      LEFT JOIN san_dim_paquete pm ON a.paquete = pm.codigo
                                      LEFT JOIN san_dim_tipo_muestra tm ON pm.tipoMuestra = tm.codigo
                                      ORDER BY a.codigo";
                            $result = mysqli_query($conexion, $query);
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $paqueteHtml = '';
                                    if (!empty($row['paq_codigo']) && !empty($row['tm_codigo'])) {
                                        $paqueteHtml = '<div class="paquete-completo-badge">
                                            <div class="paquete-line">Paquete: ' . htmlspecialchars($row['paq_codigo']) . ' - ' . htmlspecialchars($row['paq_nombre']) . '</div>
                                            <div class="paquete-line">Muestra: ' . htmlspecialchars($row['tm_codigo']) . ' - ' . htmlspecialchars($row['tm_nombre']) . '</div>
                                        </div>';
                                    } else {
                                        $paqueteHtml = '<span class="text-gray-400 italic">Sin paquete asignado</span>';
                                    }

                                    echo '<tr class="hover:bg-gray-50 transition">';
                                    echo '<td class="px-6 py-4 text-gray-700 font-medium">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                ' . htmlspecialchars($row['codigo']) . '
                                            </span>
                                          </td>';
                                    echo '<td class="px-6 py-4 text-gray-700 font-medium">' . htmlspecialchars($row['nombre']) . '</td>';
                                    echo '<td class="px-6 py-4">' . $paqueteHtml . '</td>';
                                    echo '<td class="px-6 py-4 flex gap-2">
                                        <button class="btn-icon p-2 text-lg hover:bg-blue-100 rounded-lg transition" 
                                                title="Editar" 
                                                onclick="openAnalisisModal(\'edit\', ' . (int) $row['codigo'] . ', \'' . addslashes(htmlspecialchars($row['nombre'])) . '\', ' . (isset($row['paquete']) && $row['paquete'] ? (int) $row['paquete'] : 'null') . ')">
                                            ✏️
                                        </button>
                                        <button class="btn-icon p-2 text-lg hover:bg-red-100 rounded-lg transition" 
                                                title="Eliminar" 
                                                onclick="confirmAnalisisDelete(' . (int) $row['codigo'] . ')">
                                            🗑️
                                        </button>
                                    </td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No hay análisis registrados</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div id="analisisModal" style="display: none;"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-lg">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="analisisModalTitle" class="text-xl font-bold text-gray-800">➕ Nuevo Análisis</h2>
                    <button onclick="closeAnalisisModal()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
                </div>
                <div class="p-6">
                    <form id="analisisForm" onsubmit="return saveAnalisis(event)">
                        <input type="hidden" id="analisisModalAction" value="create">
                        <input type="hidden" id="analisisEditCodigo" value="">

                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Análisis <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="analisisModalNombre" name="nombre" maxlength="255"
                                placeholder="Ingrese el nombre del análisis" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                        </div>

                        <!-- Select de Paquete + Muestra combinado -->
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Paquete y Tipo de Muestra <span class="text-gray-500 text-xs">(Opcional)</span>
                            </label>
                            <select id="analisisModalPaquete" name="paquete"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Ninguno</option>
                                <?php
                                // Traemos paquetes con su tipo de muestra
                                $query_paquetes = "SELECT 
                                                    pm.codigo,
                                                    pm.nombre AS paq_nombre,
                                                    tm.codigo AS tm_codigo,
                                                    tm.nombre AS tm_nombre
                                                  FROM san_dim_paquete pm
                                                  LEFT JOIN san_dim_tipo_muestra tm ON pm.tipoMuestra = tm.codigo
                                                  ORDER BY pm.codigo";
                                $result_paquetes = mysqli_query($conexion, $query_paquetes);
                                if ($result_paquetes) {
                                    while ($p = mysqli_fetch_assoc($result_paquetes)) {
                                        $label = '[Paq ' . $p['codigo'] . '] ' . $p['paq_nombre'] . ' → [Muestra ' . ($p['tm_codigo'] ?? '??') . '] ' . ($p['tm_nombre'] ?? 'Sin tipo');
                                        echo '<option value="' . $p['codigo'] . '">' . htmlspecialchars($label) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end">
                            <button type="button" onclick="closeAnalisisModal()"
                                class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg">
                                💾 Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> -
                © 2025</p>
        </div>
    </div>
    <script src="analisis.js"></script>
</body>

</html>