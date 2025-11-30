<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

//ruta relativa a la conexion
include_once 'conexion_grs_joya\conexion.php';
$conexion = conectar_sanidad();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Obtener el código de envío si viene de otra página
$codigoEnvioFiltro = $_GET['codigoEnvio'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Detalle de Muestras</title>

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
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- VISTA DETALLE DE MUESTRAS -->
        <div id="viewDetalleMuestras" class="content-view">
            <div class="content-header max-w-7xl mx-auto mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-4xl">🔬</span>
                    <h1 class="text-3xl font-bold text-gray-800">Detalle de Muestras</h1>
                </div>
                <p class="text-gray-600 text-sm">Administre los detalles de las muestras enviadas</p>
            </div>

            <div class="form-container max-w-7xl mx-auto">
                <!-- Filtro y Botones de acción -->
                <div class="mb-6">
                    <!-- Filtro de código de envío -->
                    <div class="mb-4 bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex items-center gap-4">
                            <label class="text-sm font-medium text-gray-700">Filtrar por Código de Envío:</label>
                            <select id="filtroCodigoEnvio" 
                                    onchange="filtrarMuestraDetalle()" 
                                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Todos los envíos</option>
                                <?php
                                $query_filtro = "SELECT DISTINCT mc.codigoEnvio, mc.fechaEnvio 
                                               FROM com_db_muestra_cabecera mc 
                                               ORDER BY mc.fechaEnvio DESC, mc.codigoEnvio DESC";
                                $result_filtro = mysqli_query($conexion, $query_filtro);
                                if ($result_filtro) {
                                    while ($row = mysqli_fetch_assoc($result_filtro)) {
                                        $selected = ($row['codigoEnvio'] === $codigoEnvioFiltro) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($row['codigoEnvio']) . '" ' . $selected . '>' . 
                                             htmlspecialchars($row['codigoEnvio']) . ' (' . date('d/m/Y', strtotime($row['fechaEnvio'])) . ')' .
                                             '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <button onclick="filtrarMuestraDetalle()" 
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                                <i class="fas fa-filter mr-2"></i>Filtrar
                            </button>
                            <button onclick="limpiarFiltro()" 
                                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                                <i class="fas fa-times mr-2"></i>Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex justify-between items-center flex-wrap gap-3">
                        <button type="button" 
                                class="px-6 py-2.5 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2" 
                                onclick="exportarDetalleMuestras()" 
                                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);" 
                                onmouseover="this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'" 
                                onmouseout="this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'">
                            📊 Exportar a Excel
                        </button>
                        <button type="button" 
                                class="btn btn-primary px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2" 
                                onclick="openMuestraDetalleModal('create')">
                            ➕ Nuevo Detalle
                        </button>
                    </div>
                </div>

                <!-- Tabla de detalles -->
                <div class="table-container border border-gray-300 rounded-2xl bg-white overflow-x-auto">
                    <table class="data-table w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Código Envío</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-gray-800">Posición</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Fecha Toma</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Código Ref.</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-gray-800">N° Muestras</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Observaciones</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="muestraDetalleTableBody" class="divide-y divide-gray-200">
                            <?php
                            $query = "SELECT md.*, mc.fechaEnvio, mc.laboratorio, l.nombre as laboratorio_nombre
                                     FROM com_db_muestra_detalle md
                                     LEFT JOIN com_db_muestra_cabecera mc ON md.codigoEnvio = mc.codigoEnvio
                                     LEFT JOIN com_laboratorio l ON mc.laboratorio = l.codigo
                                     ORDER BY md.codigoEnvio DESC, md.posicionSolicitud";
                            $result = mysqli_query($conexion, $query);
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $observaciones_cortas = strlen($row['observaciones']) > 50 ? 
                                                           substr($row['observaciones'], 0, 50) . '...' : 
                                                           $row['observaciones'];
                                    
                                    echo '<tr class="hover:bg-gray-50 transition" data-codigo-envio="' . htmlspecialchars($row['codigoEnvio']) . '">';
                                    echo '<td class="px-6 py-4 text-gray-700 font-medium">' . htmlspecialchars($row['codigoEnvio']) . '</td>';
                                    echo '<td class="px-6 py-4 text-center text-gray-700">' . $row['posicionSolicitud'] . '</td>';
                                    echo '<td class="px-6 py-4 text-gray-700">' . date('d/m/Y', strtotime($row['fechaToma'])) . '</td>';
                                    echo '<td class="px-6 py-4 text-gray-700">' . htmlspecialchars($row['codigoReferencia']) . '</td>';
                                    echo '<td class="px-6 py-4 text-center text-gray-700">' . $row['numeroMuestras'] . '</td>';
                                    echo '<td class="px-6 py-4 text-gray-600 text-sm" title="' . htmlspecialchars($row['observaciones'] ?? '') . '">' . 
                                         htmlspecialchars($observaciones_cortas ?? '') . '</td>';
                                    echo '<td class="px-6 py-4">
                                        <div class="flex gap-2">
                                            <button class="btn-icon p-2 text-lg hover:bg-blue-100 rounded-lg transition" 
                                                    title="Editar" 
                                                    onclick="openMuestraDetalleModal(\'edit\', \'' . 
                                                    addslashes(htmlspecialchars($row['codigoEnvio'])) . '\', ' .
                                                    (int)$row['posicionSolicitud'] . ', \'' .
                                                    $row['fechaToma'] . '\', \'' .
                                                    addslashes(htmlspecialchars($row['codigoReferencia'])) . '\', ' .
                                                    (int)$row['numeroMuestras'] . ', \'' .
                                                    addslashes(htmlspecialchars($row['observaciones'] ?? '')) . '\')">
                                                ✏️
                                            </button>
                                            <button class="btn-icon p-2 text-lg hover:bg-purple-100 rounded-lg transition" 
                                                    title="Ver Análisis" 
                                                    onclick="viewAnalisisDetalle(\'' . 
                                                    addslashes(htmlspecialchars($row['codigoEnvio'])) . '\', ' . 
                                                    (int)$row['posicionSolicitud'] . ')">
                                                🔍
                                            </button>
                                            <button class="btn-icon p-2 text-lg hover:bg-red-100 rounded-lg transition" 
                                                    title="Eliminar" 
                                                    onclick="confirmMuestraDetalleDelete(\'' . 
                                                    addslashes(htmlspecialchars($row['codigoEnvio'])) . '\', ' . 
                                                    (int)$row['posicionSolicitud'] . ')">
                                                🗑️
                                            </button>
                                        </div>
                                    </td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr>';
                                echo '<td colspan="7" class="px-6 py-8 text-center text-gray-500">No hay detalles de muestras registrados</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal para Crear/Editar Muestra Detalle -->
        <div id="muestraDetalleModal" style="display: none;" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-2xl">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="muestraDetalleModalTitle" class="text-xl font-bold text-gray-800">➕ Nuevo Detalle de Muestra</h2>
                    <button onclick="closeMuestraDetalleModal()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition">
                        ×
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <form id="muestraDetalleForm" onsubmit="return saveMuestraDetalle(event)">
                        <input type="hidden" id="muestraDetalleModalAction" value="create">
                        <input type="hidden" id="muestraDetalleEditCodigo" value="">
                        <input type="hidden" id="muestraDetalleEditPosicion" value="">

                        <!-- Código de Envío -->
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Código de Envío <span class="text-red-500">*</span>
                            </label>
                            <select id="muestraDetalleModalCodigoEnvio" 
                                    name="codigoEnvio" 
                                    required
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                <option value="">Seleccione un código de envío...</option>
                                <?php
                                $query_envios = "SELECT mc.codigoEnvio, mc.fechaEnvio, l.nombre as lab_nombre 
                                                FROM com_db_muestra_cabecera mc
                                                LEFT JOIN com_laboratorio l ON mc.laboratorio = l.codigo
                                                ORDER BY mc.fechaEnvio DESC, mc.codigoEnvio DESC";
                                $result_envios = mysqli_query($conexion, $query_envios);
                                if ($result_envios) {
                                    while ($row = mysqli_fetch_assoc($result_envios)) {
                                        echo '<option value="' . htmlspecialchars($row['codigoEnvio']) . '">' . 
                                             htmlspecialchars($row['codigoEnvio']) . ' - ' . 
                                             date('d/m/Y', strtotime($row['fechaEnvio'])) . 
                                             ' (' . htmlspecialchars($row['lab_nombre'] ?? 'N/A') . ')' .
                                             '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Posición de Solicitud -->
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Posición de Solicitud <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="muestraDetalleModalPosicion" 
                                name="posicionSolicitud" 
                                min="1" 
                                placeholder="Número de posición"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            >
                        </div>

                        <!-- Fecha de Toma -->
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Fecha de Toma <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="muestraDetalleModalFechaToma" 
                                name="fechaToma" 
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            >
                        </div>

                        <!-- Código de Referencia -->
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Código de Referencia <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="muestraDetalleModalCodigoRef" 
                                name="codigoReferencia" 
                                maxlength="50" 
                                placeholder="Código de referencia"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            >
                        </div>

                        <!-- Número de Muestras -->
                        <div class="form-field mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Número de Muestras <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="muestraDetalleModalNumMuestras" 
                                name="numeroMuestras" 
                                min="1" 
                                placeholder="Cantidad de muestras"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                            >
                        </div>

                        <!-- Observaciones -->
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Observaciones <span class="text-gray-500 text-xs">(Opcional)</span>
                            </label>
                            <textarea 
                                id="muestraDetalleModalObservaciones" 
                                name="observaciones" 
                                rows="3"
                                placeholder="Observaciones adicionales..."
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-none"
                            ></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end">
                            <button 
                                type="button" 
                                onclick="closeMuestraDetalleModal()"
                                class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition duration-200"
                            >
                                Cancelar
                            </button>
                            <button 
                                type="submit"
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

    <script src="detalle.js"></script>
    <script>
        // Si viene con filtro, aplicarlo al cargar
        <?php if ($codigoEnvioFiltro): ?>
        window.addEventListener('DOMContentLoaded', function() {
            filtrarMuestraDetalle();
        });
        <?php endif; ?>
    </script>
</body>

</html>

