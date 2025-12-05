<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

//ruta relativa a la conexion
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
    <title>Dashboard - Paquetes de Muestra</title>

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/output.css">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">

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

        .tipo-muestra-badge {
            display: inline-flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 8px 12px;
            min-width: 120px;
        }

        .tipo-codigo {
            font-size: 0.75rem;
            color: #0369a1;
            font-weight: 600;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }

        .tipo-nombre {
            font-size: 0.875rem;
            color: #0c4a6e;
            font-weight: 500;
        }

        .select-option-group {
            display: flex;
            align-items: center;
            padding: 8px 12px;
        }

        .select-codigo {
            background: #3b82f6;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 8px;
            min-width: 40px;
            text-align: center;
        }

        .select-nombre {
            color: #374151;
            font-size: 0.875rem;
        }

        .formato-label {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 4px;
            font-style: italic;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- VISTA PAQUETES DE MUESTRA -->
        <div id="viewPaqueteMuestra" class="content-view">
            <div class="content-header max-w-7xl mx-auto mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-4xl">📦</span>
                    <h1 class="text-3xl font-bold text-gray-800">Paquetes de Muestra</h1>
                </div>
                <p class="text-gray-600 text-sm">Administre los paquetes de muestra registrados en el sistema</p>
            </div>

            <div class="form-container max-w-7xl mx-auto">
                <!-- Botones de acción -->
                <div class="mb-6 flex justify-between items-center flex-wrap gap-3">
                    <button type="button"
                        class="px-6 py-2.5 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2"
                        onclick="exportarPaquetesMuestra()"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);"
                        onmouseover="this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'"
                        onmouseout="this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'">
                        📊 Exportar a Excel
                    </button>
                    <button type="button"
                        class="btn btn-primary px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2"
                        onclick="openPaqueteMuestraModal('create')">
                        ➕ Nuevo Paquete
                    </button>
                </div>

                <!-- Tabla de paquetes de muestra -->
                <div class="table-container border border-gray-300 rounded-2xl bg-white overflow-x-auto">
                    <table class="data-table w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Código</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Nombre del Paquete
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Tipo de Muestra</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="paqueteMuestraTableBody" class="divide-y divide-gray-200">
                            <?php
                            // Consulta con JOIN para obtener código y nombre del tipo de muestra
                            $query = "SELECT 
                                        pm.codigo, 
                                        pm.nombre, 
                                        pm.tipoMuestra,
                                        tm.nombre as tipo_muestra_nombre,
                                        tm.codigo as tipo_muestra_codigo
                                      FROM com_paquete_muestra pm
                                      LEFT JOIN com_tipo_muestra tm ON pm.tipoMuestra = tm.codigo
                                      ORDER BY pm.codigo";

                            $result = mysqli_query($conexion, $query);
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    // Mostrar código y nombre del tipo de muestra en formato visual
                                    $tipoMuestraHtml = '';
                                    if ($row['tipo_muestra_nombre'] && $row['tipo_muestra_codigo']) {
                                        $tipoMuestraHtml = '<div class="tipo-muestra-badge">
                                            <div class="tipo-codigo">Código: ' . htmlspecialchars($row['tipo_muestra_codigo']) . '</div>
                                            <div class="tipo-nombre">Nombre: ' . htmlspecialchars($row['tipo_muestra_nombre']) . '</div>
                                        </div>';
                                    } else {
                                        $tipoMuestraHtml = '<span class="text-gray-400 italic">Sin tipo asignado</span>';
                                    }

                                    echo '<tr class="hover:bg-gray-50 transition">';
                                    echo '<td class="px-6 py-4 text-gray-700 font-medium">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                ' . htmlspecialchars($row['codigo']) . '
                                            </span>
                                          </td>';
                                    echo '<td class="px-6 py-4 text-gray-700 font-medium">' . htmlspecialchars($row['nombre']) . '</td>';
                                    echo '<td class="px-6 py-4">' . $tipoMuestraHtml . '</td>';
                                    echo '<td class="px-6 py-4 flex gap-2">
                                        <button class="btn-icon p-2 text-lg hover:bg-blue-100 rounded-lg transition" 
                                                title="Editar" 
                                                onclick="openPaqueteMuestraModal(\'edit\', ' . (int) $row['codigo'] . ', \'' .
                                        addslashes(htmlspecialchars($row['nombre'])) . '\', ' .
                                        (int) $row['tipoMuestra'] . ')">
                                            ✏️
                                        </button>
                                        <button class="btn-icon p-2 text-lg hover:bg-red-100 rounded-lg transition" 
                                                title="Eliminar" 
                                                onclick="confirmPaqueteMuestraDelete(' . (int) $row['codigo'] . ')">
                                            🗑️
                                        </button>
                                    </td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr>';
                                echo '<td colspan="4" class="px-6 py-8 text-center text-gray-500">No hay paquetes de muestra registrados</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal para Crear/Editar Paquete de Muestra -->
        <div id="paqueteMuestraModal" style="display: none;"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="paqueteMuestraModalTitle" class="text-xl font-bold text-gray-800">➕ Nuevo Paquete de Muestra
                    </h2>
                    <button onclick="closePaqueteMuestraModal()"
                        class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition">
                        ×
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <form id="paqueteMuestraForm" onsubmit="return savePaqueteMuestra(event)">
                        <input type="hidden" id="paqueteMuestraModalAction" value="create">
                        <input type="hidden" id="paqueteMuestraEditCodigo" value="">

                        <!-- Campo Nombre -->
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre del Paquete <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="paqueteMuestraModalNombre" name="nombre" maxlength="100"
                                placeholder="Ingrese el nombre del paquete" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        </div>

                        <!-- Campo Tipo de Muestra (con código y nombre en formato visual) -->
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de Muestra <span class="text-red-500">*</span>
                                <!--<span class="formato-label">Formato: Código - Nombre</span>-->
                            </label>
                            <select id="paqueteMuestraModalTipoMuestra" name="tipoMuestra" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                onchange="mostrarSeleccionTipoMuestra(this)">
                                <option value="">Seleccione un tipo de muestra...</option>
                                <?php
                                // Obtener tipos de muestra con código y nombre
                                $query_tipos = "SELECT codigo, nombre FROM com_tipo_muestra ORDER BY codigo";
                                $result_tipos = mysqli_query($conexion, $query_tipos);
                                if ($result_tipos) {
                                    while ($tipo = mysqli_fetch_assoc($result_tipos)) {
                                        // Mostrar "Código - Nombre" en formato visual en el select
                                        echo '<option value="' . $tipo['codigo'] . '" data-nombre="' . htmlspecialchars($tipo['nombre']) . '">' .
                                            htmlspecialchars($tipo['codigo'] . ' - ' . $tipo['nombre']) .
                                            '</option>';
                                    }
                                }
                                ?>
                            </select>

                            <!-- Visualización de la selección actual -->
                            <!--div id="tipoMuestraSeleccionado" class="mt-3 hidden">
                                <div class="tipo-muestra-badge inline-block">
                                    <div class="tipo-codigo">Código: <span id="codigoSeleccionado">-</span></div>
                                    <div class="tipo-nombre">Nombre: <span id="nombreSeleccionado">-</span></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 italic">Tipo de muestra seleccionado</p>
                            </div-->
                        </div>

                        <!-- Botones -->
                        <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end">
                            <button type="button" onclick="closePaqueteMuestraModal()"
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
        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © 2025
            </p>
        </div>

    </div>

    <script src="paquete-analisis.js"></script>
</body>

</html>