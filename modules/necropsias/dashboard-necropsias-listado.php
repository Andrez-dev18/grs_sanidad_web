<?php

session_start();
if (empty($_SESSION['active'])) {
    echo '<script>var u="../../login.php";if(window.top!==window.self){window.top.location.href=u;}else{window.location.href=u;}</script>';
    exit();
}

//ruta relativa a la conexion
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - necropsia</title>

    <!-- Tailwind CSS -->
    <link href="../../css/output.css" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../css/dashboard-responsive.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        .data-table tbody tr:hover {
            background-color: #eff6ff !important;
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
    <div class="container-fluid py-4 mx-8">

        <!-- CARD FILTROS PLEGABLE -->
        <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">

            <!-- HEADER -->
            <button type="button" onclick="toggleFiltros()"
                class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition">

                <div class="flex items-center gap-2">
                    <span class="text-lg">🔎</span>
                    <h3 class="text-base font-semibold text-gray-800">
                        Filtros de búsqueda
                    </h3>
                </div>

                <!-- ICONO -->
                <svg id="iconoFiltros" class="w-5 h-5 text-gray-600 transition-transform duration-300"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- CONTENIDO PLEGABLE -->
            <div id="contenidoFiltros" class="px-6 pb-6 pt-4 hidden">

                <?php
                if ($conn) {
                    // 1. Ejecutar el SET para evitar errores de GROUP BY
                    $conn->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

                    // 2. Consulta SQL optimizada
                    // Seleccionamos SOLO codigo y nombre.
                    // Usamos la lógica de 'edad' (b.edad) SOLO en el WHERE para filtrar, pero no la traemos en el SELECT.
                    $sqlGranjas = "SELECT codigo, nombre
                   FROM ccos AS a 
                   LEFT JOIN (
                        SELECT a.tcencos, a.tcodint, a.tcodigo, DATEDIFF(NOW(), MIN(a.fec_ing))+1 as edad 
                        FROM maes_zonas AS a 
                        USE INDEX(tcencos,tcodint,tcodigo) 
                        WHERE a.tcodigo IN ('P0001001','P0001002')  
                        GROUP BY tcencos
                   ) AS b ON a.codigo = b.tcencos  
                   WHERE (LEFT(codigo,1) IN ('6','5') 
                   AND RIGHT(codigo,3)<>'000' 
                   AND swac='A' 
                   AND LENGTH(codigo)=6 
                   AND LEFT(codigo,3)<>'650'
                   AND LEFT(codigo,3) <= '667')
                   AND IF(b.edad IS NULL, '0', b.edad) <> '0'
                   ORDER BY nombre ASC";

                    $resultadoGranjas = $conn->query($sqlGranjas);
                }
                ?>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                        <input type="date" id="filtroFechaInicio"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                        <input type="date" id="filtroFechaFin"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Granja</label>
                        <select id="filtroGranja" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Seleccionar</option>

                            <?php if (isset($resultadoGranjas) && $resultadoGranjas): ?>
                                <?php while ($fila = $resultadoGranjas->fetch_assoc()): ?>
                                    <?php
                                    // 1. Convertimos caracteres especiales primero
                                    $nombreCompleto = utf8_encode($fila['nombre']);

                                    // 2. LOGICA DE LIMPIEZA:
                                    // Explotamos el string usando 'C=' como separador y tomamos la parte [0] (la izquierda)
                                    $nombreCorto = explode('C=', $nombreCompleto)[0];

                                    // 3. Quitamos espacios en blanco sobrantes al final (el espacio antes del C=)
                                    $nombreCorto = trim($nombreCorto);

                                    // 4. Sanear para HTML
                                    $textoMostrar = htmlspecialchars($nombreCorto);
                                    $codigo = htmlspecialchars($fila['codigo']);
                                    ?>
                                    <option value="<?php echo $codigo; ?>">
                                        <?php echo $textoMostrar; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>Sin datos disponibles</option>
                            <?php endif; ?>

                        </select>
                    </div>

                </div>

                <!-- ACCIONES -->
                <div class="dashboard-actions mt-6 flex flex-wrap justify-end gap-4">

                    <button type="button" id="btnAplicarFiltros"
                        class="px-6 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Filtrar
                    </button>

                    <button type="button" id="btnLimpiarFiltros"
                        class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200">
                        Limpiar
                    </button>

                    <button type="button"
                        class="px-6 py-2.5 text-white font-medium rounded-lg transition inline-flex items-center gap-2"
                        onclick="exportarNecropsiasExcel()"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);">
                        📊 Exportar a Excel
                    </button>
                </div>

            </div>
        </div>


        <!-- Modal Editar (estilo similar a registro) -->
        <div id="modalNecropsia" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 flex items-center justify-center p-2 sm:p-4">
            <div class="bg-sky-50 rounded-2xl shadow-2xl w-full max-w-7xl max-h-[95vh] sm:max-h-[90vh] flex flex-col overflow-hidden">
                <!-- Header modal -->
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800 truncate pr-2">Editar Necropsia</h2>
                    <button id="closeModal" class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition flex-shrink-0" aria-label="Cerrar">&times;</button>
                </div>

                <div id="contenidoNecropsia" class="flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6 min-h-0">
                    <!-- Card cabecera (estilo registro) -->
                    <div class="mb-6 rounded-xl border border-gray-200 bg-sky-50 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 px-4 py-3 border-b border-gray-200">
                            <label class="text-sm font-semibold text-gray-700">Datos del lote</label>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4 necropsia-cabecera-grid">
                        <!-- FECHA NECROPSIA (primero) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">FECHA</label>
                            <input type="date" id="fectra" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>

                        <!-- GRANJA (select más grande) -->
                        <div class="md:col-span-2 necropsia-granja-cell"> <!-- Ocupa 2 columnas en desktop para más espacio -->
                            <label class="block text-sm font-medium text-gray-700">GRANJA</label>
                            <select id="granja" required class="mt-1 block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                                <option value="">Cargando granjas...</option>
                            </select>
                        </div>

                        <!-- CAMPAÑA (solo lectura) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CAMP.</label>
                            <input type="text" id="campania" readonly class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed">
                        </div>

                        <!-- GALPÓN (se autoselecciona el mayor) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">GALPÓN</label>
                            <select id="galpon" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" disabled>
                                <option value="">Seleccione granja primero</option>
                            </select>
                        </div>

                        <!-- EDAD (solo lectura) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">EDAD</label>
                            <input type="text" id="edad" readonly class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed">
                        </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs (estilo registro) -->
                    <div class="mb-6 rounded-xl border border-gray-200 bg-white overflow-hidden">
                    <div class="border-b border-gray-200 p-2 sm:p-4 necropsia-tabs-nav overflow-x-auto">
                        <nav class="-mb-px flex flex-nowrap space-x-4 sm:space-x-8" role="tablist">
                            <button type="button" class="tab-button py-3 px-1 border-b-4 border-green-600 text-green-600 font-semibold" data-tab="inmunologico">
                                Sistema Inmunológico
                            </button>
                            <button type="button" class="tab-button py-3 px-1 border-b-4 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium" data-tab="digestivo">
                                Sistema Digestivo
                            </button>
                            <button type="button" class="tab-button py-3 px-1 border-b-4 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium" data-tab="respiratorio">
                                Sistema Respiratorio
                            </button>
                            <button type="button" class="tab-button py-3 px-1 border-b-4 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium" data-tab="evaluacion">
                                Evaluación Física
                            </button>
                            <button type="button" class="tab-button py-3 px-1 border-b-4 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium" data-tab="diag-presuntivo">
                                Diag. Presuntivo
                            </button>
                        </nav>
                    </div>

                    <!-- Contenido de los Tabs (estilo registro: tabla con títulos de columnas) -->
                    <div class="p-4">
                    <!-- sistema inmunologico -->
                    <div class="tab-content" id="inmunologico">
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full table-auto border-collapse text-sm">
                            <thead class="bg-sky-100 text-xs font-semibold text-gray-700 uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium w-48"></th>
                                    <th class="px-4 py-3 text-left font-medium w-32">OPCIÓN</th>
                                    <th class="px-4 py-3 text-center font-medium">1</th>
                                    <th class="px-4 py-3 text-center font-medium">2</th>
                                    <th class="px-4 py-3 text-center font-medium">3</th>
                                    <th class="px-4 py-3 text-center font-medium">4</th>
                                    <th class="px-4 py-3 text-center font-medium">5</th>
                                    <th class="px-4 py-3 text-center font-medium">%</th>
                                    <th class="px-4 py-3 text-left font-medium">OBSERVACIONES</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">

                                <!-- ÍNDICE BURSAL -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="3">ÍNDICE BURSAL*</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_indice_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="3">
                                        <textarea id="obs_indice_bursal" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_indice_bursal" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_indice_bursal"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Atrofia</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_atrofia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_atrofia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_atrofia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_atrofia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_atrofia')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_indice_atrofia">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Severa Atrofia</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_severa_atrofia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_severa_atrofia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_severa_atrofia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_severa_atrofia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_severa_atrofia')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_indice_severa_atrofia">0%</td>
                                </tr>

                                <!-- MUCOSA DE LA BURSA -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="3">MUCOSA DE LA BURSA</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_mucosa_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="3">
                                        <textarea id="obs_mucosa_bursa" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="4"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_mucosa_bursa" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_mucosa_bursa"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Petequias</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_petequias')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_petequias')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_petequias')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_petequias')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_petequias')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_mucosa_petequias">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Hemorragia</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_hemorragia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_hemorragia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_hemorragia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_hemorragia')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('mucosa_hemorragia')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_mucosa_hemorragia">0%</td>
                                </tr>

                                <!-- TIMOS -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="4">TIMOS</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_timos_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="4">
                                        <textarea id="obs_timos" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_timos" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_timos"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Atrofiados</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_atrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_atrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_atrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_atrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_atrofiado')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_timos_atrofiado">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Aspecto Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_aspecto_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_aspecto_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_aspecto_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_aspecto_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_aspecto_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_timos_aspecto_normal">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Congestionado</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_congestionado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_congestionado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_congestionado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_congestionado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_congestionado')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_timos_congestionado">0%</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <!-- sistema digestivo -->
                    <div class="tab-content hidden" id="digestivo">
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full table-auto border-collapse text-sm">
                            <thead class="bg-sky-100 text-xs font-semibold text-gray-700 uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium w-48"></th>
                                    <th class="px-4 py-3 text-left font-medium w-32">OPCIÓN</th>
                                    <th class="px-4 py-3 text-center font-medium">1</th>
                                    <th class="px-4 py-3 text-center font-medium">2</th>
                                    <th class="px-4 py-3 text-center font-medium">3</th>
                                    <th class="px-4 py-3 text-center font-medium">4</th>
                                    <th class="px-4 py-3 text-center font-medium">5</th>
                                    <th class="px-4 py-3 text-center font-medium">%</th>
                                    <th class="px-4 py-3 text-left font-medium">OBSERVACIONES</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">

                                <!-- HÍGADOS -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="4">HÍGADOS</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_higados_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="4">
                                        <textarea id="obs_higados" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="4"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_higados" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_higados"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Esteatósico</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_esteatosico')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_esteatosico')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_esteatosico')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_esteatosico')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_esteatosico')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_higados_esteatosico">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Tmn. Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_tmnnormal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_tmnnormal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_tmnnormal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_tmnnormal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_tmnnormal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_higados_tmnnormal">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Hipertrofiado</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_hipertrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_hipertrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_hipertrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_hipertrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_hipertrofiado')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_higados_hipertrofiado">0%</td>
                                </tr>

                                <!-- VESÍCULA BILIAR -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="5">VESÍCULA BILIAR</td>
                                    <td class="px-4 py-4">Color Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_vesicula_color_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="5">
                                        <textarea id="obs_vesicula" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="5"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_vesicula" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_vesicula"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Color Claro</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_claro')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_claro')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_claro')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_claro')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_claro')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_vesicula_color_claro">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Tmn. Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_vesicula_tam_normal">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Atrofiado</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_atrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_atrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_atrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_atrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_atrofiado')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_vesicula_atrofiado">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Hipertrofiado</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_hipertrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_hipertrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_hipertrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_hipertrofiado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_hipertrofiado')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_vesicula_hipertrofiado">0%</td>
                                </tr>

                                <!-- EROSIÓN DE LA MOLLEJA -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="5">EROSIÓN DE LA MOLLEJA</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_erosion_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="5">
                                        <textarea id="obs_erosion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="5"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_erosion" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_erosion"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Grado 1</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado1')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_erosion_grado1">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Grado 2</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado2')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado2')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado2')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado2')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado2')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_erosion_grado2">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Grado 3</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado3')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado3')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado3')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado3')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado3')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_erosion_grado3">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Grado 4</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado4')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado4')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado4')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado4')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado4')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_erosion_grado4">0%</td>
                                </tr>

                                <!-- RETRACCIÓN DEL PÁNCREAS -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="2">RETRACCIÓN DEL PÁNCREAS</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pancreas_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="2">
                                        <textarea id="obs_pancreas" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100" id="evidencia_pancreas">
                                            <div class="mt-2" id="preview_pancreas"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Retraído</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_retraido')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_retraido')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_retraido')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_retraido')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_retraido')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pancreas_retraido">0%</td>
                                </tr>

                                <!-- ABSORCIÓN DEL SACO VITELINO -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="2">ABSORCIÓN DEL SACO VITELINO</td>
                                    <td class="px-4 py-4">Sí</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('saco_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('saco_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('saco_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('saco_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('saco_si')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_saco_si">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="2">
                                        <textarea id="obs_saco" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100" id="evidencia_saco">
                                            <div class="mt-2" id="preview_saco"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">No</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('saco_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('saco_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('saco_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('saco_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('saco_no')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_saco_no">0%</td>
                                </tr>

                                <!-- ENTERITIS -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="4">ENTERITIS</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_enteritis_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="4">
                                        <textarea id="obs_enteritis" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="5"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100" id="evidencia_enteritis">
                                            <div class="mt-2" id="preview_enteritis"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Leve</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_leve')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_leve')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_leve')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_leve')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_leve')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_enteritis_leve">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Moderado</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_moderado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_moderado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_moderado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_moderado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_moderado')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_enteritis_moderado">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Severo</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_severo')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_severo')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_severo')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_severo')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('enteritis_severo')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_enteritis_severo">0%</td>
                                </tr>

                                <!-- CONTENIDO CECAL -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="3">CONTENIDO CECAL</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_cecal_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="3">
                                        <textarea id="obs_cecal" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="4"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_cecal" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_cecal"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Gas</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_gas')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_gas')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_gas')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_gas')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_gas')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_cecal_gas">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Espuma</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_espuma')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_espuma')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_espuma')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_espuma')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('cecal_espuma')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_cecal_espuma">0%</td>
                                </tr>

                                <!-- ALIMENTO SIN DIGERIR -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="2">ALIMENTO SIN DIGERIR</td>
                                    <td class="px-4 py-4">Sí</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('alimento_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('alimento_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('alimento_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('alimento_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('alimento_si')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_alimento_si">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="2">
                                        <textarea id="obs_alimento" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_alimento" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_alimento"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">No</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('alimento_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('alimento_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('alimento_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('alimento_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('alimento_no')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_alimento_no">0%</td>
                                </tr>

                                <!-- HECES ANARANJADAS -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="2">HECES ANARANJADAS</td>
                                    <td class="px-4 py-4">Sí</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('heces_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('heces_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('heces_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('heces_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('heces_si')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_heces_si">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="2">
                                        <textarea id="obs_heces" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_heces" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_heces"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">No</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('heces_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('heces_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('heces_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('heces_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('heces_no')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_heces_no">0%</td>
                                </tr>

                                <!-- LESIÓN ORAL -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="2">LESIÓN ORAL</td>
                                    <td class="px-4 py-4">Sí</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('lesion_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('lesion_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('lesion_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('lesion_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('lesion_si')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_lesion_si">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="2">
                                        <textarea id="obs_lesion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_lesion" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_lesion"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">No</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('lesion_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('lesion_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('lesion_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('lesion_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('lesion_no')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_lesion_no">0%</td>
                                </tr>

                                <!-- TONICIDAD INTESTINAL -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="3">TONICIDAD INTESTINAL</td>
                                    <td class="px-4 py-4">Buena</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_buena')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_buena')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_buena')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_buena')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_buena')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_tonicidad_buena">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="3">
                                        <textarea id="obs_tonicidad" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="4"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_tonicidad" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_tonicidad"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Regular</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_regular')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_regular')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_regular')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_regular')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_regular')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_tonicidad_regular">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Mala</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_mala')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_mala')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_mala')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_mala')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tonicidad_mala')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_tonicidad_mala">0%</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <!-- sistema respiratorio-->
                    <div class="tab-content hidden" id="respiratorio">
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full table-auto border-collapse text-sm">
                            <thead class="bg-green-100">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium w-48"></th>
                                    <th class="px-4 py-3 text-left font-medium w-32">OPCIÓN</th>
                                    <th class="px-4 py-3 text-center font-medium">1</th>
                                    <th class="px-4 py-3 text-center font-medium">2</th>
                                    <th class="px-4 py-3 text-center font-medium">3</th>
                                    <th class="px-4 py-3 text-center font-medium">4</th>
                                    <th class="px-4 py-3 text-center font-medium">5</th>
                                    <th class="px-4 py-3 text-center font-medium">%</th>
                                    <th class="px-4 py-3 text-left font-medium">OBSERVACIONES</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">

                                <!-- TRAQUEA -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="4">TRAQUEA</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_traquea_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="4">
                                        <textarea id="obs_traquea" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="5"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_traquea" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_traquea"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Leve</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_leve')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_leve')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_leve')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_leve')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_leve')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_traquea_leve">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Moderada</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_moderada')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_moderada')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_moderada')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_moderada')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_moderada')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_traquea_moderada">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Severa</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_severa')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_severa')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_severa')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_severa')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('traquea_severa')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_traquea_severa">0%</td>
                                </tr>

                                <!-- PULMÓN -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="2">PULMÓN</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pulmon_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pulmon_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pulmon_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pulmon_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pulmon_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pulmon_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="2">
                                        <textarea id="obs_pulmon" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_pulmon" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_pulmon"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Neumónico</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pulmon_neumonico')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pulmon_neumonico')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pulmon_neumonico')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pulmon_neumonico')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pulmon_neumonico')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pulmon_neumonico">0%</td>
                                </tr>

                                <!-- SACOS AÉREOS -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="3">SACOS AÉREOS</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_sacos_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="3">
                                        <textarea id="obs_sacos" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="4"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_sacos" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_sacos"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Turbio</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_turbio')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_turbio')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_turbio')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_turbio')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_turbio')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_sacos_turbio">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Material Caseoso</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_caseoso')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_caseoso')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_caseoso')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_caseoso')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('sacos_caseoso')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_sacos_caseoso">0%</td>
                                </tr>

                            </tbody>
                        </table>
                        </div>
                    </div>

                    <!-- evaluacion fisica-->
                    <div class="tab-content hidden" id="evaluacion">
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full table-auto border-collapse text-sm">
                            <thead class="bg-sky-100 text-xs font-semibold text-gray-700 uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium w-48"></th>
                                    <th class="px-4 py-3 text-left font-medium w-32">OPCIÓN</th>
                                    <th class="px-4 py-3 text-center font-medium">1</th>
                                    <th class="px-4 py-3 text-center font-medium">2</th>
                                    <th class="px-4 py-3 text-center font-medium">3</th>
                                    <th class="px-4 py-3 text-center font-medium">4</th>
                                    <th class="px-4 py-3 text-center font-medium">5</th>
                                    <th class="px-4 py-3 text-center font-medium">%</th>
                                    <th class="px-4 py-3 text-left font-medium">OBSERVACIONES</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">

                                <!-- PODODERMATITIS -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="5">PODODERMATITIS</td>
                                    <td class="px-4 py-4">Grado 0</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado0')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado0')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado0')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado0')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado0')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pododermatitis_grado0">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="5">
                                        <textarea id="obs_pododermatitis" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="6"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_pododermatitis" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_pododermatitis"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Grado 1</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado1')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pododermatitis_grado1">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Grado 2</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado2')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado2')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado2')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado2')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado2')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pododermatitis_grado2">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Grado 3</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado3')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado3')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado3')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado3')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado3')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pododermatitis_grado3">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Grado 4</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado4')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado4')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado4')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado4')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pododermatitis_grado4')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pododermatitis_grado4">0%</td>
                                </tr>

                                <!-- COLOR TARSOS -->
                                <tr class="bg-sky-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="6">COLOR TARSOS</td>
                                    <td class="px-4 py-4">3.5</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_35')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_35')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_35')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_35')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_35')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_tarsos_35">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="6">
                                        <textarea id="obs_tarsos" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="7"></textarea>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">EVIDENCIA (opcional)</label>
                                            <input type="file" accept="image/*" multiple id="evidencia_tarsos" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                            <div class="mt-2" id="preview_tarsos"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">4.0</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_40')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_40')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_40')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_40')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_40')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_tarsos_40">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">4.5</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_45')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_45')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_45')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_45')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_45')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_tarsos_45">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">5.0</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_50')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_50')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_50')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_50')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_50')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_tarsos_50">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">5.5</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_55')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_55')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_55')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_55')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_55')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_tarsos_55">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">6.0</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_60')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_60')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_60')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_60')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('tarsos_60')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_tarsos_60">0%</td>
                                </tr>

                            </tbody>
                        </table>
                        </div>
                    </div>

                    <!-- DIAGNOSTICO PRESUNTIVO -->
                    <div class="tab-content hidden" id="diag-presuntivo">
                        <div class="">
                            <div class="mb-4">
                                <label for="txtDiagnosticoPresuntivo" class="block text-sm font-semibold text-gray-800 mb-1 flex items-center gap-2">
                                    <i class="fas fa-stethoscope text-green-600"></i> Diagnóstico Presuntivo
                                </label>
                                <p class="text-xs text-gray-500">
                                    Detalle aquí las conclusiones preliminares basadas en las lesiones observadas durante la necropsia.
                                </p>
                            </div>

                            <div class="relative flex-1">
                                <textarea
                                    id="txtDiagnosticoPresuntivo"
                                    name="diagnostico_presuntivo"
                                    class="w-full h-full min-h-[150px] p-4 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none transition-all outline-none"
                                    placeholder="Escriba su diagnóstico aquí..."></textarea>

                                <div class="absolute bottom-3 right-3 text-xs text-gray-400 pointer-events-none">
                                    <i class="fas fa-pen"></i>
                                </div>
                            </div>

                        </div>
                    </div>
                    </div>
                    </div>
                    </div>
                    <!-- /Card Tabs -->

                    <!-- Botones al final -->
                    <div class="dashboard-modal-actions necropsia-modal-actions flex flex-wrap justify-end mt-6 sm:mt-8 gap-3 sm:gap-4 pt-4 pb-4 sm:pb-6 px-4 sm:px-6 border-t border-gray-200 bg-white rounded-b-2xl flex-shrink-0">
                        <button type="button" id="closeModalBtn" class="px-4 sm:px-6 py-2.5 sm:py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition w-full sm:w-auto flex-shrink-0 min-w-0">
                            Cancelar
                        </button>
                        <button type="button" id="btnGuardarNecropsia" class="px-4 sm:px-6 py-2.5 sm:py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition w-full sm:w-auto flex-shrink-0 min-w-0 whitespace-nowrap overflow-visible">
                            Guardar Necropsia
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Carga con Pollo -->
        <div id="modalCarga" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-sky-50 rounded-xl shadow-2xl p-8 text-center max-w-sm w-full">
                <img src="../../assets/img/gallina.gif" alt="Cargando..." class="w-32 h-32 mx-auto mb-4">
                <p class="text-lg font-semibold text-gray-800">Guardando necropsia...</p>
                <p class="text-sm text-gray-600 mt-2">Por favor espera, estamos procesando los registros y las imágenes</p>
                <div class="mt-6">
                    <div class="inline-block w-12 h-12 border-4 border-green-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>
        </div>

        <!-- contenedor boton y tabla  -->
        <div class="bg-white rounded-xl shadow-md p-5">
            <?php
            $codigoUsuario = $_SESSION['usuario'] ?? 'USER';  // Cambia 'usuario' si tu sesión usa otro nombre
            // Consulta directa, simple
            $sql = "SELECT rol_sanidad FROM usuario WHERE codigo = '$codigoUsuario'";
            $res = $conn->query($sql);

            $rol = 'user'; // valor por defecto si no encuentra nada

            if ($res && $res->num_rows > 0) {
                $fila = $res->fetch_assoc();
                $rol = strtolower(trim($fila['rol_sanidad']));
            }
            ?>

            <!-- Este <p> oculto guarda el rol para que JavaScript lo lea -->
            <p id="idRolUser" data-rol="<?= htmlspecialchars($rol) ?>"></p>

            <!-- tabla (estilo reportes) -->
            <div class="card-body p-0 mt-5" id="tablaNecropsiasWrapper" data-vista-tabla-iconos data-vista="">
                <div class="view-toggle-group flex items-center gap-2 mb-4 px-4">
                    <button type="button" class="view-toggle-btn active" id="btnViewTablaNec" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                    <button type="button" class="view-toggle-btn" id="btnViewIconosNec" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                </div>
                <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetasNec">
                    <div id="cardsControlsTopNec" class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-gray-600 border-b border-gray-200 pb-3"></div>
                    <div id="cardsContainerNec" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                    <div id="cardsPaginationNec" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3"></div>
                </div>
                <div class="view-lista-wrap table-wrapper overflow-x-auto">
                    <table id="tabla" class="data-table display w-full text-sm border-collapse" style="width:100%">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>N°Reg</th>
                                <th>Fecha Necropsia</th>
                                <th>Granja</th>
                                <th>Nombre</th>
                                <th>Campaña</th>
                                <th>Galpón</th>
                                <th>Edad</th>
                                <th>Usuario</th>
                                <th>Fecha Registro</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL PARA VER MÚLTIPLES EVIDENCIAS -->
        <div id="modalEvidencia" class="fixed inset-0 bg-black/80 hidden z-50">
            <!-- Fondo oscuro sin padding lateral para maximizar espacio -->
            <div class="flex min-h-full items-start justify-center pt-4 px-4 sm:pt-0 sm:items-center">

                <div class="bg-white rounded-t-3xl sm:rounded-xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col">

                    <!-- Header -->
                    <div class="flex justify-between items-center px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Evidencia fotográfica
                        </h3>
                        <div class="flex items-center gap-3">
                            <!-- Botón abrir en nueva pestaña (solo ícono) -->
                            <button onclick="abrirFotoActualEnPestana()"
                                class="text-blue-600 hover:text-blue-800 transition bg-blue-100 hover:bg-blue-200 rounded-full p-2">
                                <i class="fa-solid fa-external-link-alt text-lg"></i>
                            </button>

                            <!-- Botón cerrar -->
                            <button onclick="cerrarModalEvidencia()" class="text-gray-500 hover:text-gray-700 text-2xl">
                                ×
                            </button>
                        </div>
                    </div>

                    <!-- Carrusel de imágenes -->
                    <div class="flex-1 overflow-hidden relative bg-gray-50">
                        <div id="carruselFotos" class="flex transition-transform duration-300 ease-in-out h-full">
                            <!-- Imágenes dinámicas -->
                        </div>

                        <!-- Flechas -->
                        <button id="prevFoto" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition z-10">
                            <i class="fa-solid fa-chevron-left text-2xl text-gray-800"></i>
                        </button>
                        <button id="nextFoto" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition z-10">
                            <i class="fa-solid fa-chevron-right text-2xl text-gray-800"></i>
                        </button>

                        <!-- Contador -->
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 text-white px-4 py-2 rounded-full text-sm font-medium z-10">
                            <span id="contadorFotos">1 / 1</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </div>

    <script>
        // Helpers (SweetAlert: evita desbordes con contenido scrollable)
        function SwalAlert(mensaje, tipo) {
            var icono = (tipo === 'error') ? 'error' : (tipo === 'success') ? 'success' : (tipo === 'warning') ? 'warning' : 'info';
            return Swal.fire({
                icon: icono,
                title: tipo === 'error' ? 'Error' : tipo === 'success' ? 'Éxito' : tipo === 'warning' ? 'Aviso' : 'Información',
                html: '<div class="text-left max-h-[60vh] overflow-y-auto px-1">' + (typeof mensaje === 'string' ? mensaje.replace(/</g, '&lt;').replace(/>/g, '&gt;') : mensaje) + '</div>',
                confirmButtonText: 'Aceptar',
                customClass: { htmlContainer: 'text-left' }
            });
        }
        function SwalConfirm(mensaje, titulo) {
            return Swal.fire({
                title: titulo || 'Confirmar',
                html: '<div class="text-left max-h-[50vh] overflow-y-auto">' + (typeof mensaje === 'string' ? mensaje.replace(/</g, '&lt;').replace(/>/g, '&gt;') : mensaje) + '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí',
                cancelButtonText: 'Cancelar',
                customClass: { htmlContainer: 'text-left' }
            }).then(function(r) { return r.isConfirmed; });
        }
        function getFechaSeleccionada() {
            const f = document.getElementById('fectra')?.value;
            if (f) return f;
            const hoy = new Date();
            const yyyy = hoy.getFullYear();
            const mm = String(hoy.getMonth() + 1).padStart(2, '0');
            const dd = String(hoy.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        }

        function toggleFiltros() {
            const contenido = document.getElementById('contenidoFiltros');
            const icono = document.getElementById('iconoFiltros');

            contenido.classList.toggle('hidden');
            icono.classList.toggle('rotate-180');
        }

        async function cargarGranjasConFecha({
            preserveSelection = false
        } = {}) {
            const select = document.getElementById('granja');
            if (!select) return;

            const valorPrevio = select.value;
            const fecha = getFechaSeleccionada();

            try {
                select.innerHTML = '<option value="">Cargando granjas...</option>';
                const response = await fetch(`get_granjas.php?fecha=${encodeURIComponent(fecha)}`);
                const granjas = await response.json();

                select.innerHTML = '<option value="">Seleccione granja...</option>';
                granjas.forEach(g => {
                    const opt = document.createElement('option');
                    opt.value = g.codigo;
                    opt.textContent = `${g.codigo} - ${g.nombre}`;
                    opt.dataset.edad = g.edad;
                    select.appendChild(opt);
                });

                if (preserveSelection && valorPrevio) {
                    select.value = valorPrevio;
                    if (select.value) {
                        // Recalcular dependencias si se mantiene selección
                        select.dispatchEvent(new Event('change'));
                    } else {
                        // Si la granja ya no existe para esa fecha, limpiar dependencias
                        document.getElementById('campania').value = '';
                        document.getElementById('edad').value = '';
                        const selectGalpon = document.getElementById('galpon');
                        if (selectGalpon) {
                            selectGalpon.innerHTML = '<option value="">Seleccione granja primero</option>';
                            selectGalpon.disabled = true;
                        }
                    }
                }
            } catch (err) {
                console.error('Error cargando granjas:', err);
                select.innerHTML = '<option value="">Error al cargar</option>';
            }
        }

        $(document).ready(function() {

            // Referencia a los inputs
            const inputInicio = $('#filtroFechaInicio');
            const inputFin = $('#filtroFechaFin');
            const selectGranja = $('#filtroGranja');

            let tabla = $('#tabla').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'listar_necropsias.php',
                    type: 'POST',
                    data: function(d) {
                        d.fecha_inicio = inputInicio.val();
                        d.fecha_fin = inputFin.val();
                        d.granja = selectGranja.val();
                    }
                },
                language: {
                    url: '../../assets/i18n/es-ES.json'
                },
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [9, 'desc']
                ], // Ordenar por fecha de registro (incluida la hora)
                columns: [{
                        data: 'counter',
                        className: 'text-center',
                        orderable: false
                    },
                    {
                        data: 'tnumreg',
                        className: 'text-center font-medium'
                    },
                    {
                        data: 'tfectra',
                        className: 'text-center'
                    },
                    {
                        data: 'granja',
                        className: 'text-center font-medium'
                    },
                    {
                        data: 'nombre'
                    },
                    {
                        data: 'campania',
                        className: 'text-center font-medium'
                    },
                    {
                        data: 'tgalpon',
                        className: 'text-center font-bold'
                    },
                    {
                        data: 'tedad',
                        className: 'text-center font-medium'
                    },
                    {
                        data: 'tuser'
                    },
                    {
                        data: 'fecha_registro',
                        className: 'text-center'
                    },
                    {
                        data: null,
                        className: 'text-center whitespace-nowrap',
                        orderable: false,
                        render: function(data, type, row) {

                            const rolUser = document.getElementById('idRolUser')?.dataset.rol?.trim().toLowerCase() || 'user';

                            const fectraRaw = row.tfectra_raw || '';
                            const fectraVista = row.tfectra || '';
                            const granja = row.tgranja || '';
                            const numreg = row.tnumreg || '';
                            const galpon = row.tgalpon || '';

                            let buttonsHtml = `
                                    <div class="flex justify-center items-center gap-2 flex-nowrap whitespace-nowrap">
                                        <a
                                            class="text-red-600 hover:text-red-800 transition"
                                            title="PDF Tabla"
                                            target="_blank"
                                            href="generar_reporte_tabla.php?tipo=1&numreg=${encodeURIComponent(numreg)}&granja=${encodeURIComponent(granja)}&galpon=${encodeURIComponent(galpon)}&fectra=${encodeURIComponent(fectraRaw)}">
                                            <i class="fa-solid fa-file-pdf text-lg"></i>
                                        </a>

                                        <button
                                            type="button"
                                            class="text-red-600 hover:text-red-800 transition"
                                            title="PDF Imágenes"
                                            onclick="verificarYGenerarPDF('${granja}', ${numreg}, '${fectraVista}')">
                                            <i class="fa-solid fa-images text-lg"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="text-indigo-600 hover:text-indigo-800 transition"
                                            title="Editar"
                                            onclick="editarNecropsia('${granja}', ${numreg}, '${fectraRaw}')">
                                            <i class="fa-solid fa-edit text-lg"></i>
                                        </button>
                                `;

                            if (rolUser === 'admin' || rolUser === 'administrador') {
                                buttonsHtml += `
                                    <button
                                        type="button"
                                        class="text-rose-600 hover:text-rose-800 transition"
                                        title="Eliminar"
                                        onclick="eliminarNecropsia('${granja}', ${numreg}, '${fectraRaw}')">
                                        <i class="fa-solid fa-trash text-lg"></i>
                                    </button>
                                `;
                            }

                            // 4. CERRAR EL CONTENEDOR
                            buttonsHtml += `</div>`;

                            return buttonsHtml;
                        }
                    }
                ],
                drawCallback: function() {
                    if (typeof renderizarTarjetasNecropsias === 'function') renderizarTarjetasNecropsias();
                }
            });

            function actualizarVistaInicialNec() {
                var w = $(window).width();
                var w$ = $('#tablaNecropsiasWrapper');
                if (!w$.attr('data-vista')) {
                    var vistaInicial = w < 768 ? 'iconos' : 'tabla';
                    w$.attr('data-vista', vistaInicial);
                    $('#btnViewTablaNec').toggleClass('active', vistaInicial === 'tabla');
                    $('#btnViewIconosNec').toggleClass('active', vistaInicial === 'iconos');
                    $('#cardsContainerNec').attr('data-vista-cards', 'iconos');
                    aplicarVisibilidadVistaNec(vistaInicial);
                }
            }
            function renderizarTarjetasNecropsias() {
                if (!tabla) return;
                var api = tabla;
                var cont = $('#cardsContainerNec');
                cont.empty();
                var rolUser = ($('#idRolUser').attr('data-rol') || '').trim().toLowerCase();
                var puedeEliminar = (rolUser === 'admin');
                var info = api.page.info();
                var rowIndex = 0;
                api.rows({ page: 'current' }).every(function() {
                    rowIndex++;
                    var numero = info.start + rowIndex;
                    var row = this.data();
                    var fectraRaw = (row.tfectra_raw || '').replace(/"/g, '&quot;');
                    var fectraVista = (row.tfectra || '').replace(/"/g, '&quot;');
                    var granja = (row.tgranja || row.granja || '').replace(/"/g, '&quot;');
                    var numreg = row.tnumreg || '';
                    var galpon = (row.tgalpon || '').replace(/"/g, '&quot;');
                    var acciones = '<a class="text-red-600 hover:text-red-800 transition" title="PDF Tabla" target="_blank" href="generar_reporte_tabla.php?tipo=1&numreg=' + encodeURIComponent(numreg) + '&granja=' + encodeURIComponent(row.tgranja || row.granja || '') + '&galpon=' + encodeURIComponent(row.tgalpon || '') + '&fectra=' + encodeURIComponent(row.tfectra_raw || '') + '"><i class="fa-solid fa-file-pdf"></i></a>' +
                        '<button type="button" class="text-red-600 hover:text-red-800 transition" title="PDF Imágenes" onclick="verificarYGenerarPDF(\'' + (row.tgranja || row.granja || '').replace(/'/g, "\\'") + '\', ' + numreg + ', \'' + (row.tfectra || '').replace(/'/g, "\\'") + '\')"><i class="fa-solid fa-images"></i></button>' +
                        '<button type="button" class="text-indigo-600 hover:text-indigo-800 transition" title="Editar" onclick="editarNecropsia(\'' + (row.tgranja || row.granja || '').replace(/'/g, "\\'") + '\', ' + numreg + ', \'' + (row.tfectra_raw || '').replace(/'/g, "\\'") + '\')"><i class="fa-solid fa-edit"></i></button>';
                    if (puedeEliminar) {
                        acciones += '<button type="button" class="text-rose-600 hover:text-rose-800 transition" title="Eliminar" onclick="eliminarNecropsia(\'' + (row.tgranja || row.granja || '').replace(/'/g, "\\'") + '\', ' + numreg + ', \'' + (row.tfectra_raw || '').replace(/'/g, "\\'") + '\')"><i class="fa-solid fa-trash"></i></button>';
                    }
                    var card = '<div class="card-item">' +
                        '<div class="card-numero-row">#' + numero + '</div>' +
                        '<div class="card-contenido">' +
                        '<div class="card-codigo">N°Reg ' + $('<div>').text(row.tnumreg || '').html() + '</div>' +
                        '<div class="card-campos">' +
                        '<div class="card-row"><span class="label">Fecha necropsia:</span> ' + $('<div>').text(row.tfectra || '').html() + '</div>' +
                        '<div class="card-row"><span class="label">Granja:</span> ' + $('<div>').text(row.tgranja || row.granja || '').html() + '</div>' +
                        '<div class="card-row"><span class="label">Nombre:</span> ' + $('<div>').text(row.nombre || row.tcencos || '').html() + '</div>' +
                        '<div class="card-row"><span class="label">Campaña:</span> ' + $('<div>').text(row.campania || '').html() + '</div>' +
                        '<div class="card-row"><span class="label">Galpón:</span> ' + $('<div>').text(row.tgalpon || '').html() + '</div>' +
                        '<div class="card-row"><span class="label">Edad:</span> ' + $('<div>').text((row.tedad != null && row.tedad !== '') ? row.tedad + ' días' : '-').html() + '</div>' +
                        '<div class="card-row"><span class="label">Usuario:</span> ' + $('<div>').text(row.tuser || '-').html() + '</div>' +
                        '<div class="card-row"><span class="label">Fecha registro:</span> ' + $('<div>').text(row.fecha_registro || row.tdate || '-').html() + '</div>' +
                        '</div>' +
                        '<div class="card-acciones">' + acciones + '</div></div></div>';
                    cont.append(card);
                });
                var len = api.page.len();
                var lengthOptions = [10, 25, 50, 100];
                var lengthSelect = '<label class="inline-flex items-center gap-2"><span>Mostrar</span><select class="cards-length-select px-2 py-1 border border-gray-300 rounded-md text-sm">' +
                    lengthOptions.map(function(n) { return '<option value="' + n + '"' + (n === len ? ' selected' : '') + '>' + n + '</option>'; }).join('') +
                    '</select><span>registros</span></label>';
                var navBtns = '<div class="flex items-center gap-3 flex-wrap">' +
                    '<span>Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros</span>' +
                    '<div class="flex gap-2">' +
                    '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page === 0 ? 'disabled' : '') + ' onclick="var dt=$(\'#tabla\').DataTable(); if(dt) dt.page(\'previous\').draw(false);">Anterior</button>' +
                    '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page >= info.pages - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page >= info.pages - 1 ? 'disabled' : '') + ' onclick="var dt=$(\'#tabla\').DataTable(); if(dt) dt.page(\'next\').draw(false);">Siguiente</button>' +
                    '</div></div>';
                var controlsHtml = '<div class="flex flex-wrap items-center justify-between gap-3 w-full">' + lengthSelect + navBtns + '</div>';
                $('#cardsControlsTopNec').html(controlsHtml);
                $('#cardsPaginationNec').html(controlsHtml);
                $('#cardsControlsTopNec .cards-length-select, #cardsPaginationNec .cards-length-select').on('change', function() {
                    var val = parseInt($(this).val(), 10);
                    if (tabla) tabla.page.len(val).draw(false);
                });
            }
            function aplicarVisibilidadVistaNec(vista) {
                var esTabla = (vista === 'tabla');
                $('#tablaNecropsiasWrapper').attr('data-vista', vista);
                if (esTabla) {
                    $('#viewTarjetasNec').addClass('hidden').css('display', 'none');
                    $('#tablaNecropsiasWrapper .view-lista-wrap').removeClass('hidden').css('display', 'block');
                } else {
                    $('#tablaNecropsiasWrapper .view-lista-wrap').addClass('hidden').css('display', 'none');
                    $('#viewTarjetasNec').removeClass('hidden').css('display', 'block');
                    $('#cardsContainerNec').attr('data-vista-cards', 'iconos');
                }
            }
            actualizarVistaInicialNec();
            $('#btnViewTablaNec').on('click', function() {
                aplicarVisibilidadVistaNec('tabla');
                $('#btnViewTablaNec').addClass('active');
                $('#btnViewIconosNec').removeClass('active');
            });
            $('#btnViewIconosNec').on('click', function() {
                aplicarVisibilidadVistaNec('iconos');
                $('#btnViewIconosNec').addClass('active');
                $('#btnViewTablaNec').removeClass('active');
            });
            $(window).on('resize', function() {
                if (!$('#tablaNecropsiasWrapper').attr('data-vista')) return;
                actualizarVistaInicialNec();
            });

            // Botón Filtrar
            $('#btnAplicarFiltros').on('click', function() {
                tabla.draw();
            });

            // Limpiar filtros
            $('#btnLimpiarFiltros').on('click', function() {
                $('#filtroFechaInicio').val('');
                $('#filtroFechaFin').val('');
                $('#filtroGranja').val('');
                tabla.ajax.reload();
            });

        });
    </script>

    <script>
        function exportarNecropsiasExcel() {
            const params = new URLSearchParams();
            const fi = document.getElementById('filtroFechaInicio')?.value || '';
            const ff = document.getElementById('filtroFechaFin')?.value || '';
            const granja = document.getElementById('filtroGranja')?.value || '';
            if (fi) params.set('fecha_inicio', fi);
            if (ff) params.set('fecha_fin', ff);
            if (granja) params.set('granja', granja);
            window.location.href = 'exportar_excel_necropsias.php?' + params.toString();
        }

        function verificarYGenerarPDF(granja, numreg, fectra) {
            fetch(`generar_reporte_necropsia.php?granja=${encodeURIComponent(granja)}&numreg=${numreg}&fectra=${encodeURIComponent(fectra)}&check=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.tiene_imagenes) {
                        window.open(`generar_reporte_necropsia.php?granja=${encodeURIComponent(granja)}&numreg=${numreg}&fectra=${encodeURIComponent(fectra)}`, '_blank');
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No se registró imágenes',
                            text: 'Este registro de necropsia no tiene imágenes asociadas.',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#3b82f6'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error al verificar imágenes:', error);
                    window.open(`generar_reporte_necropsia.php?granja=${encodeURIComponent(granja)}&numreg=${numreg}&fectra=${encodeURIComponent(fectra)}`, '_blank');
                });
        }
    </script>

    <script>
        let isEditMode = false;
        let loteEditando = {};
        let fechaHoraInicioNec = null;

        function obtenerFechaHoraActualNec() {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const hh = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd} ${hh}:${min}:${ss}`;
        }

        async function editarNecropsia(granja, numreg, fectra) {
            // Limpiar formulario primero
            limpiarFormularioNecropsia();
            isEditMode = true;
            loteEditando = {
                granja,
                numreg,
                fectra
            };

            // Cambiar botón a "Actualizar"
            const btnGuardar = document.getElementById('btnGuardarNecropsia');
            if (btnGuardar) btnGuardar.textContent = 'Actualizar Necropsia';

            try {
                const response = await fetch(`cargar_necropsia_editar.php?granja=${encodeURIComponent(granja)}&numreg=${numreg}&fectra=${fectra}`);
                const result = await response.json();

                if (!result.success) {
                    SwalAlert('Error al cargar datos: ' + result.message, 'error');
                    return;
                }


                // === CABECERA ===
                // Fecha necropsia (setear primero para que get_granjas.php calcule edad con esta fecha)
                const fectraInput = document.getElementById('fectra');
                if (fectraInput) fectraInput.value = result.fectra || fectra;

                const diagpresuntivo = document.getElementById('txtDiagnosticoPresuntivo');
                if(diagpresuntivo) diagpresuntivo.value = result.diagpresuntivo || '';

                // Granja (select) - cargar opciones primero si es necesario
                const granjaSelect = document.getElementById('granja');
                if (granjaSelect) {
                    await cargarGranjasParaEdicion();
                    granjaSelect.value = result.granja || granja;
                    // Al editar no recalcular edad: usamos datos ya guardados; solo cargar galpones
                    window._loadingEditNecropsia = true;
                    granjaSelect.dispatchEvent(new Event('change'));
                }

                // Esperar a que se carguen los galpones
                await new Promise(resolve => setTimeout(resolve, 500));

                // Galpón, campaña y edad desde datos guardados (no desde recálculo dinámico)
                const galponSelect = document.getElementById('galpon');
                if (galponSelect) galponSelect.value = result.galpon || '';

                const campaniaInput = document.getElementById('campania');
                if (campaniaInput) campaniaInput.value = result.campania || '';

                const edadInput = document.getElementById('edad');
                if (edadInput) edadInput.value = result.edad || '';

                window._loadingEditNecropsia = false;

                // === PROCESAR REGISTROS ===
                const registros = result.registros;

                // Agrupar por nivel para evitar duplicados
                const registrosPorNivel = {};
                registros.forEach(reg => {
                    const nivel = reg.tnivel.trim().toUpperCase();
                    if (!registrosPorNivel[nivel]) {
                        registrosPorNivel[nivel] = {
                            parametros: {},
                            observacion: reg.tobservacion || '',
                            evidencia: reg.evidencia || ''
                        };
                    }

                    // Agregar parámetro con sus checkboxes (leer porcentajes tolerando string o número y posibles variantes de nombre)
                    const parametro = (reg.tparametro || '').trim();
                    const getP = (r, key) => Number(r[key] ?? r[key.replace(/^t/, 'T')] ?? 0);
                    registrosPorNivel[nivel].parametros[parametro] = {
                        p1: getP(reg, 'tporcentaje1'),
                        p2: getP(reg, 'tporcentaje2'),
                        p3: getP(reg, 'tporcentaje3'),
                        p4: getP(reg, 'tporcentaje4'),
                        p5: getP(reg, 'tporcentaje5'),
                        total: Number(reg.tporcentajetotal ?? reg.Tporcentajetotal ?? 0) || 0
                    };
                });

                // Normalizar nivel para mapeo (BD puede devolver HÍGADO, VESÍCULA, EROSIÓN, etc.)
                function normalizarNivelParaMapa(nivel) {
                    if (!nivel || typeof nivel !== 'string') return nivel || '';
                    return nivel.normalize('NFD').replace(/\p{Diacritic}/gu, '').trim();
                }

                // Mapeo de nivel a obsId base (debe coincidir con los IDs en el HTML; se usa clave sin acentos)
                const mapeoObsId = {
                    'INDICE BURSAL': 'indice_bursal',
                    'MUCOSA DE LA BURSA': 'mucosa_bursa',
                    'TIMOS': 'timos',
                    'HIGADO': 'higados',
                    'VESICULA BILIAR': 'vesicula',
                    'EROSION DE LA MOLLEJA': 'erosion',
                    'RETRACCION DEL PANCREAS': 'pancreas',
                    'ABSORCION DEL SACO VITELINO': 'saco',
                    'ENTERITIS': 'enteritis',
                    'CONTENIDO CECAL': 'cecal',
                    'ALIMENTO SIN DIGERIR': 'alimento',
                    'HECES ANARANJADAS': 'heces',
                    'LESION ORAL': 'lesion',
                    'TONICIDAD INTESTINAL': 'tonicidad',
                    'TRAQUEA': 'traquea',
                    'PULMON': 'pulmon',
                    'SACOS AEREOS': 'sacos',
                    'PODODERMATITIS': 'pododermatitis',
                    'COLOR TARSOS': 'tarsos'
                };

                // Mapeo de parámetro a sufijo de idGrupo (para niveles que NO son ÍNDICE BURSAL ni MUCOSA)
                const mapeoParametroASufijo = {
                    // TIMOS
                    'Normal': '_normal',
                    'Atrofiado': '_atrofiado',
                    'Aspecto Normal': '_aspecto_normal',
                    'Congestionado': '_congestionado',
                    // TIMOS
                    'Atrofiado': '_atrofiado',
                    'Aspecto Normal': '_aspecto_normal',
                    'Congestionado': '_congestionado',
                    // HÍGADO
                    'Esteatosico': '_esteatosico',
                    'Tmn. Normal': '_tmnnormal',
                    'Hipertrofiado': '_hipertrofiado',
                    // VESÍCULA
                    'Color Normal': '_color_normal',
                    'Color Claro': '_color_claro',
                    'Tam. Normal': '_tam_normal',
                    'Atrofiado': '_atrofiado',
                    'Hipertrofiado': '_hipertrofiado',
                    // EROSIÓN
                    'Grado 1': '_grado1',
                    'Grado 2': '_grado2',
                    'Grado 3': '_grado3',
                    'Grado 4': '_grado4',
                    // PÁNCREAS
                    'Normal': '_normal',
                    'Retraido': '_retraido',
                    // SACO/ALIMENTO/HECES/LESIÓN
                    'Sí': '_si',
                    'No': '_no',
                    // ENTERITIS
                    'Leve': '_leve',
                    'Moderado': '_moderado',
                    'Severo': '_severo',
                    // CECAL
                    'Gas': '_gas',
                    'Espuma': '_espuma',
                    // TONICIDAD
                    'Buena': '_buena',
                    'Regular': '_regular',
                    'Mala': '_mala',
                    // TRÁQUEA
                    'Moderada': '_moderada',
                    'Severa': '_severa',
                    // PULMÓN
                    'Neumonico': '_neumonico',
                    // SACOS
                    'Turbio': '_turbio',
                    'Material Caseoso': '_caseoso',
                    // PODODERMATITIS
                    'Grado 0': '_grado0',
                    // TARSOS
                    '3.5': '_35',
                    '4.0': '_40',
                    '4.5': '_45',
                    '5.0': '_50',
                    '5.5': '_55',
                    '6.0': '_60'
                };

                // Llenar datos por nivel
                Object.keys(registrosPorNivel).forEach(nivelKey => {
                    const datos = registrosPorNivel[nivelKey];
                    const nivelNorm = normalizarNivelParaMapa(nivelKey);
                    const obsIdBase = mapeoObsId[nivelKey] || mapeoObsId[nivelNorm];

                    if (!obsIdBase) {
                        console.warn('Nivel no mapeado:', nivelKey, '(normalizado:', nivelNorm, ')');
                        return;
                    }

                    // Observación
                    const textarea = document.getElementById('obs_' + obsIdBase);
                    if (textarea) textarea.value = datos.observacion;

                    // Procesar cada parámetro
                    Object.keys(datos.parametros).forEach(parametro => {
                        const valores = datos.parametros[parametro];

                        // Construir idGrupo - casos especiales para ÍNDICE BURSAL y MUCOSA DE LA BURSA (usar nivel normalizado)
                        let idGrupo;
                        const nivelNorm = normalizarNivelParaMapa(nivelKey);

                        if (nivelNorm === 'INDICE BURSAL') {
                            // Los IDs son: indice_normal, indice_atrofia, indice_severa_atrofia
                            if (parametro === 'Normal') idGrupo = 'indice_normal';
                            else if (parametro === 'Atrofia') idGrupo = 'indice_atrofia';
                            else if (parametro === 'Severa Atrofia') idGrupo = 'indice_severa_atrofia';
                        } else if (nivelNorm === 'MUCOSA DE LA BURSA') {
                            // Los IDs son: mucosa_normal, mucosa_petequias, mucosa_hemorragia
                            if (parametro === 'Normal') idGrupo = 'mucosa_normal';
                            else if (parametro === 'Petequias') idGrupo = 'mucosa_petequias';
                            else if (parametro === 'Hemorragia') idGrupo = 'mucosa_hemorragia';
                        } else {
                            // Para el resto, usar el mapeo normal
                            let sufijo = mapeoParametroASufijo[parametro];
                            if (!sufijo && parametro === 'Normal') sufijo = '_normal'; // Fallback

                            if (!sufijo) {
                                console.warn('Parámetro sin mapeo:', parametro, 'en nivel', nivelKey);
                                return;
                            }

                            idGrupo = obsIdBase + sufijo;
                        }

                        if (!idGrupo) {
                            console.warn('No se pudo construir idGrupo para:', nivelKey, parametro);
                            return;
                        }

                        // Marcar checkboxes (5 aves) — asegurar porcentajes como número por si el backend envía string
                        const porcentajes = [
                            Number(valores.p1) || 0,
                            Number(valores.p2) || 0,
                            Number(valores.p3) || 0,
                            Number(valores.p4) || 0,
                            Number(valores.p5) || 0
                        ];
                        const checkboxes = document.querySelectorAll(`input[onchange*="('${idGrupo}')"]`);
                        checkboxes.forEach((cb, index) => {
                            cb.checked = (porcentajes[index] || 0) > 0;
                        });

                        // Actualizar porcentaje visual
                        const porcElement = document.getElementById('porc_' + idGrupo);
                        if (porcElement) porcElement.textContent = valores.total + '%';
                    });

                  
                    if (datos.evidencia) {
                        const preview = document.getElementById('preview_' + obsIdBase);
                        if (preview) {
                            preview.innerHTML = ''; // Limpiar primero

                            const rutas = datos.evidencia.split(',').map(r => r.trim()).filter(r => r);

                            rutas.forEach(ruta => {
                                const container = document.createElement('div');
                                container.classList.add('relative', 'inline-block', 'mr-2', 'mb-2', 'group'); // Agregué 'group'

                                // Imagen
                                const img = document.createElement('img');
                                img.src = '../../' + ruta; // Ruta visual
                                img.dataset.serverPath = ruta; // IMPORTANTE: Guardamos la ruta original del servidor
                                img.classList.add('h-32', 'w-32', 'object-cover', 'rounded-lg', 'shadow-md');

                                // Botón Eliminar (Unificado)
                                const removeBtn = document.createElement('button');
                                removeBtn.innerHTML = '×';
                                removeBtn.classList.add(
                                    'absolute', 'top-1', 'right-1',
                                    'bg-red-600', 'hover:bg-red-700', 'text-white',
                                    'text-sm', 'font-bold', 'rounded-full', 'w-6', 'h-6',
                                    'flex', 'items-center', 'justify-center', 'cursor-pointer', 'shadow-sm',
                                    'opacity-0', 'group-hover:opacity-100', 'transition-opacity' // Efecto hover
                                );

                                removeBtn.onclick = (e) => {
                                    e.preventDefault(); // Evitar submits accidentales
                                    container.remove();
                                };

                                container.appendChild(img);
                                container.appendChild(removeBtn);
                                preview.appendChild(container);
                            });
                        }
                    }
                    // ...
                });

                // Abrir modal solo después de tener todos los datos cargados (evita desincronización en producción)
                document.getElementById('modalNecropsia').classList.remove('hidden');

            } catch (err) {
                console.error('Error en editarNecropsia:', err);
                SwalAlert('Error al cargar datos para edición: ' + err.message, 'error');
            }
        }

        // Función auxiliar para cargar granjas si no están cargadas
        async function cargarGranjasParaEdicion() {
            const select = document.getElementById('granja');
            if (select.options.length <= 1) { // Solo tiene placeholder
                try {
                    const fecha = getFechaSeleccionada();
                    const response = await fetch(`get_granjas.php?fecha=${encodeURIComponent(fecha)}`);
                    const granjas = await response.json();

                    select.innerHTML = '<option value="">Seleccione granja...</option>';
                    granjas.forEach(g => {
                        const opt = document.createElement('option');
                        opt.value = g.codigo;
                        opt.textContent = `${g.codigo} - ${g.nombre}`;
                        opt.dataset.edad = g.edad;
                        select.appendChild(opt);
                    });
                } catch (err) {
                    console.error('Error cargando granjas:', err);
                }
            }
        }
    </script>

    <script>
        
        document.getElementById('closeModal').addEventListener('click', () => {
            document.getElementById('modalNecropsia').classList.add('hidden');
            limpiarFormularioNecropsia();
        });

        document.getElementById('closeModalBtn').addEventListener('click', () => {
            document.getElementById('modalNecropsia').classList.add('hidden');
            limpiarFormularioNecropsia();
        });

        document.getElementById('btnAnadirFilaNecropsia')?.addEventListener('click', () => {
            const tabActivo = document.querySelector('.tab-button.border-green-600');
            if (tabActivo) {
                const tabId = tabActivo.dataset.tab;
                const tabContent = document.getElementById(tabId);
                if (tabContent) {
                    tabContent.scrollIntoView({ behavior: 'smooth' });
                    Swal.fire({ icon: 'info', title: 'Añadir fila', text: 'Use los campos de observaciones para agregar información adicional en cada nivel.', timer: 2500, showConfirmButton: false });
                }
            }
        });

        // Tabs (misma lógica que dashboard-necropsias-registro.php)
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('border-green-600', 'text-green-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });
                document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
                button.classList.remove('border-transparent', 'text-gray-500');
                button.classList.add('border-green-600', 'text-green-600');
                var panel = document.getElementById(button.getAttribute('data-tab'));
                if (panel) panel.classList.remove('hidden');
            });
        });

        // Calcular porcentaje (mejorado para mayor precisión)
        function calcularPorcentaje(idGrupo) {
            const checkboxes = document.querySelectorAll(`input[onchange*="('${idGrupo}')"]`);
            let count = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) count++;
            });
            const porcentaje = (count * 20); // Cada ave = 20%
            const elemento = document.getElementById('porc_' + idGrupo);
            if (elemento) elemento.textContent = porcentaje + '%';
        }

        // GUARDAR CON AJAX
        document.getElementById('btnGuardarNecropsia').addEventListener('click', async () => {
            if (isEditMode) {
                // Lógica separada para actualizar
                actualizarNecropsia();
            } else {
                // Lógica original para guardar (Debes asegurarte de que tu función anterior de guardar
                // esté envuelta en una función llamada guardarNecropsia() o pegar aquí el código de guardar)
                guardarNecropsia();
            }
        });

        async function guardarNecropsia() {
            // === CABECERA ===
            const granjaSelect = document.getElementById('granja');
            const galponSelect = document.getElementById('galpon');

            const codigoGranja = granjaSelect.value;
            const nombreGranja = granjaSelect.options[granjaSelect.selectedIndex]?.textContent.trim() || '';
            const tcencos = nombreGranja.replace(/^\d+ - /, '');
            const campania = codigoGranja.slice(-3);
            const edad = document.getElementById('edad').value;
            const galpon = galponSelect.value;
            const fectra = document.getElementById('fectra').value;
            const diagpresuntivo = document.getElementById('txtDiagnosticoPresuntivo').value;

            // Validación básica
            if (!codigoGranja || !galpon || !fectra) {
                SwalAlert('Por favor complete todos los campos de la cabecera', 'warning');
                return;
            }

            if (!diagpresuntivo) {
                SwalAlert('Por favor es importante que llene el diagnostico presuntivo', 'warning');
                return;
            }

            // === GENERAR NÚMERO DE REGISTRO AUTOMÁTICO: HHMMSS ===
            const now = new Date();
            const horas = String(now.getHours()).padStart(2, '0');
            const minutos = String(now.getMinutes()).padStart(2, '0');
            const segundos = String(now.getSeconds()).padStart(2, '0');
            const numreg = horas + minutos + segundos;

            const fechaHoraFin = obtenerFechaHoraActualNec();
            const fechaHoraInicio = fechaHoraInicioNec || fechaHoraFin;

            const data = {
                granja: codigoGranja, // → tgranja
                campania: campania, // → tcampania
                edad: edad, // → tedad
                galpon: galpon, // → tgalpon
                fectra: fectra, // → tfectra
                numreg: numreg, // → tnumreg
                tcencos: tcencos, // → tcencos (nombre completo)
                diagpresuntivo: diagpresuntivo,
                fechaHoraInicio: fechaHoraInicio,
                fechaHoraFin: fechaHoraFin,
                registros: [] // Aquí van los parámetros como antes
            };

            //Definir todos los parámetros y sus opciones (exactamente como en la BD)
            const parametros = [
                // SISTEMA INMUNOLÓGICO
                {
                    sistema: 'SISTEMA INMUNOLOGICO',
                    nivel: 'INDICE BURSAL',
                    opciones: ['Normal', 'Atrofia', 'Severa Atrofia'],
                    obsId: 'obs_indice_bursal'
                },
                {
                    sistema: 'SISTEMA INMUNOLOGICO',
                    nivel: 'MUCOSA DE LA BURSA',
                    opciones: ['Normal', 'Petequias', 'Hemorragia'],
                    obsId: 'obs_mucosa_bursa'
                },
                {
                    sistema: 'SISTEMA INMUNOLOGICO',
                    nivel: 'TIMOS',
                    opciones: ['Normal', 'Atrofiado', 'Aspecto Normal', 'Congestionado'],
                    obsId: 'obs_timos'
                },

                // SISTEMA DIGESTIVO
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'HIGADO',
                    opciones: ['Normal', 'Esteatosico', 'Tmn. Normal', 'Hipertrofiado'],
                    obsId: 'obs_higados'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'VESICULA BILIAR',
                    opciones: ['Color Normal', 'Color Claro', 'Tam. Normal', 'Atrofiado', 'Hipertrofiado'],
                    obsId: 'obs_vesicula'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'EROSION DE LA MOLLEJA',
                    opciones: ['Normal', 'Grado 1', 'Grado 2', 'Grado 3', 'Grado 4'],
                    obsId: 'obs_erosion'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'RETRACCION DEL PANCREAS',
                    opciones: ['Normal', 'Retraido'],
                    obsId: 'obs_pancreas'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'ABSORCION DEL SACO VITELINO',
                    opciones: ['Sí', 'No'],
                    obsId: 'obs_saco'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'ENTERITIS',
                    opciones: ['Normal', 'Leve', 'Moderado', 'Severo'],
                    obsId: 'obs_enteritis'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'CONTENIDO CECAL',
                    opciones: ['Normal', 'Gas', 'Espuma'],
                    obsId: 'obs_cecal'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'ALIMENTO SIN DIGERIR',
                    opciones: ['Sí', 'No'],
                    obsId: 'obs_alimento'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'HECES ANARANJADAS',
                    opciones: ['Sí', 'No'],
                    obsId: 'obs_heces'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'LESION ORAL',
                    opciones: ['Sí', 'No'],
                    obsId: 'obs_lesion'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'TONICIDAD INTESTINAL',
                    opciones: ['Buena', 'Regular', 'Mala'],
                    obsId: 'obs_tonicidad'
                },

                // SISTEMA RESPIRATORIO
                {
                    sistema: 'SISTEMA RESPIRATORIO',
                    nivel: 'TRAQUEA',
                    opciones: ['Normal', 'Leve', 'Moderada', 'Severa'],
                    obsId: 'obs_traquea'
                },
                {
                    sistema: 'SISTEMA RESPIRATORIO',
                    nivel: 'PULMON',
                    opciones: ['Normal', 'Neumonico'],
                    obsId: 'obs_pulmon'
                },
                {
                    sistema: 'SISTEMA RESPIRATORIO',
                    nivel: 'SACOS AEREOS',
                    opciones: ['Normal', 'Turbio', 'Material Caseoso'],
                    obsId: 'obs_sacos'
                },

                // EVALUACIÓN FÍSICA
                {
                    sistema: 'EVALUACION FISICA',
                    nivel: 'PODODERMATITIS',
                    opciones: ['Grado 0', 'Grado 1', 'Grado 2', 'Grado 3', 'Grado 4'],
                    obsId: 'obs_pododermatitis'
                },
                {
                    sistema: 'EVALUACION FISICA',
                    nivel: 'COLOR TARSOS',
                    opciones: ['3.5', '4.0', '4.5', '5.0', '5.5', '6.0'],
                    obsId: 'obs_tarsos'
                }
            ];

            parametros.forEach(param => {
                param.opciones.forEach(opcion => {
                    let idGrupo = '';

                    // === SISTEMA INMUNOLÓGICO (ya perfecto) ===
                    if (param.nivel === 'INDICE BURSAL') {
                        if (opcion === 'Normal') idGrupo = 'indice_normal';
                        else if (opcion === 'Atrofia') idGrupo = 'indice_atrofia';
                        else if (opcion === 'Severa Atrofia') idGrupo = 'indice_severa_atrofia';
                    } else if (param.nivel === 'MUCOSA DE LA BURSA') {
                        if (opcion === 'Normal') idGrupo = 'mucosa_normal';
                        else if (opcion === 'Petequias') idGrupo = 'mucosa_petequias';
                        else if (opcion === 'Hemorragia') idGrupo = 'mucosa_hemorragia';
                    } else if (param.nivel === 'TIMOS') {
                        if (opcion === 'Normal') idGrupo = 'timos_normal';
                        else if (opcion === 'Atrofiado') idGrupo = 'timos_atrofiado';
                        else if (opcion === 'Aspecto Normal') idGrupo = 'timos_aspecto_normal';
                        else if (opcion === 'Congestionado') idGrupo = 'timos_congestionado';
                    }

                    // === SISTEMA DIGESTIVO (100% corregido según tu HTML y capturas) ===
                    else if (param.nivel === 'HIGADO') {
                        if (opcion === 'Normal') idGrupo = 'higados_normal';
                        else if (opcion === 'Esteatosico') idGrupo = 'higados_esteatosico';
                        else if (opcion === 'Tmn. Normal') idGrupo = 'higados_tmnnormal';
                        else if (opcion === 'Hipertrofiado') idGrupo = 'higados_hipertrofiado';
                    } else if (param.nivel === 'VESICULA BILIAR') {
                        if (opcion === 'Color Normal') idGrupo = 'vesicula_color_normal';
                        else if (opcion === 'Color Claro') idGrupo = 'vesicula_color_claro';
                        else if (opcion === 'Tam. Normal') idGrupo = 'vesicula_tam_normal';
                        else if (opcion === 'Atrofiado') idGrupo = 'vesicula_atrofiado';
                        else if (opcion === 'Hipertrofiado') idGrupo = 'vesicula_hipertrofiado';
                    } else if (param.nivel === 'EROSION DE LA MOLLEJA') {
                        if (opcion === 'Normal') idGrupo = 'erosion_normal';
                        else if (opcion === 'Grado 1') idGrupo = 'erosion_grado1';
                        else if (opcion === 'Grado 2') idGrupo = 'erosion_grado2';
                        else if (opcion === 'Grado 3') idGrupo = 'erosion_grado3';
                        else if (opcion === 'Grado 4') idGrupo = 'erosion_grado4';
                    } else if (param.nivel === 'RETRACCION DEL PANCREAS') {
                        if (opcion === 'Normal') idGrupo = 'pancreas_normal';
                        else if (opcion === 'Retraido') idGrupo = 'pancreas_retraido';
                    } else if (param.nivel === 'ABSORCION DEL SACO VITELINO') {
                        if (opcion === 'Sí') idGrupo = 'saco_si';
                        else if (opcion === 'No') idGrupo = 'saco_no';
                    } else if (param.nivel === 'ENTERITIS') {
                        if (opcion === 'Normal') idGrupo = 'enteritis_normal';
                        else if (opcion === 'Leve') idGrupo = 'enteritis_leve';
                        else if (opcion === 'Moderado') idGrupo = 'enteritis_moderado';
                        else if (opcion === 'Severo') idGrupo = 'enteritis_severo';
                    } else if (param.nivel === 'CONTENIDO CECAL') {
                        if (opcion === 'Normal') idGrupo = 'cecal_normal';
                        else if (opcion === 'Gas') idGrupo = 'cecal_gas';
                        else if (opcion === 'Espuma') idGrupo = 'cecal_espuma';
                    } else if (param.nivel === 'ALIMENTO SIN DIGERIR') {
                        if (opcion === 'Sí') idGrupo = 'alimento_si';
                        else if (opcion === 'No') idGrupo = 'alimento_no';
                    } else if (param.nivel === 'HECES ANARANJADAS') {
                        if (opcion === 'Sí') idGrupo = 'heces_si';
                        else if (opcion === 'No') idGrupo = 'heces_no';
                    } else if (param.nivel === 'LESION ORAL') {
                        if (opcion === 'Sí') idGrupo = 'lesion_si';
                        else if (opcion === 'No') idGrupo = 'lesion_no';
                    } else if (param.nivel === 'TONICIDAD INTESTINAL') {
                        if (opcion === 'Buena') idGrupo = 'tonicidad_buena';
                        else if (opcion === 'Regular') idGrupo = 'tonicidad_regular';
                        else if (opcion === 'Mala') idGrupo = 'tonicidad_mala';
                    }
                    // === SISTEMA RESPIRATORIO (nuevo y perfecto según tu HTML y capturas) ===
                    else if (param.nivel === 'TRAQUEA') {
                        if (opcion === 'Normal') idGrupo = 'traquea_normal';
                        else if (opcion === 'Leve') idGrupo = 'traquea_leve';
                        else if (opcion === 'Moderada') idGrupo = 'traquea_moderada';
                        else if (opcion === 'Severa') idGrupo = 'traquea_severa';
                    } else if (param.nivel === 'PULMON') {
                        if (opcion === 'Normal') idGrupo = 'pulmon_normal';
                        else if (opcion === 'Neumonico') idGrupo = 'pulmon_neumonico';
                    } else if (param.nivel === 'SACOS AEREOS') {
                        if (opcion === 'Normal') idGrupo = 'sacos_normal';
                        else if (opcion === 'Turbio') idGrupo = 'sacos_turbio';
                        else if (opcion === 'Material Caseoso') idGrupo = 'sacos_caseoso';
                    }

                    // === EVALUACIÓN FÍSICA (última sección, perfecta según tu HTML y capturas) ===
                    else if (param.nivel === 'PODODERMATITIS') {
                        if (opcion === 'Grado 0') idGrupo = 'pododermatitis_grado0';
                        else if (opcion === 'Grado 1') idGrupo = 'pododermatitis_grado1';
                        else if (opcion === 'Grado 2') idGrupo = 'pododermatitis_grado2';
                        else if (opcion === 'Grado 3') idGrupo = 'pododermatitis_grado3';
                        else if (opcion === 'Grado 4') idGrupo = 'pododermatitis_grado4';
                    } else if (param.nivel === 'COLOR TARSOS') {
                        if (opcion === '3.5') idGrupo = 'tarsos_35';
                        else if (opcion === '4.0') idGrupo = 'tarsos_40';
                        else if (opcion === '4.5') idGrupo = 'tarsos_45';
                        else if (opcion === '5.0') idGrupo = 'tarsos_50';
                        else if (opcion === '5.5') idGrupo = 'tarsos_55';
                        else if (opcion === '6.0') idGrupo = 'tarsos_60';
                    }

                    // === Seguridad final (nunca debería llegar aquí) ===
                    else {
                        console.warn('ID no mapeado:', param.nivel, opcion);
                        idGrupo = 'unknown';
                    }

                    // Buscar checkboxes y porcentaje
                    const checkboxes = document.querySelectorAll(`input[onchange*="('${idGrupo}')"]`);
                    const porcElement = document.getElementById('porc_' + idGrupo);
                    const porcentajeTotal = porcElement ? parseFloat(porcElement.textContent.replace('%', '')) : 0;

                    const aves = [0, 0, 0, 0, 0];
                    checkboxes.forEach((cb, i) => {
                        if (cb.checked) aves[i] = 20;
                    });

                    data.registros.push({
                        tsistema: param.sistema,
                        tnivel: param.nivel,
                        tparametro: opcion,
                        tporcentaje1: aves[0],
                        tporcentaje2: aves[1],
                        tporcentaje3: aves[2],
                        tporcentaje4: aves[3],
                        tporcentaje5: aves[4],
                        tporcentajetotal: porcentajeTotal,
                        tobservacion: document.getElementById(param.obsId)?.value.trim() || ''
                    });
                });
            });

            try {
                // Mostrar modal de carga
                document.getElementById('modalCarga').classList.remove('hidden');

                const formData = new FormData();
                formData.append('data', JSON.stringify(data)); // Cabecera + registros

                // Enviar las imágenes (múltiples por nivel)
                Object.keys(evidencias).forEach(obsId => {
                    evidencias[obsId].forEach(file => {
                        formData.append(`evidencia_${obsId}[]`, file);
                    });
                });

                const response = await fetch('guardar_necropsia.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                // Ocultar modal
                document.getElementById('modalCarga').classList.add('hidden');

                if (result.success) {
                    SwalAlert('¡Necropsia registrada con éxito!', 'success');
                    document.getElementById('modalNecropsia').classList.add('hidden');
                    limpiarFormularioNecropsia();
                    $('#tabla').DataTable().ajax.reload();
                } else {
                    SwalAlert('Error: ' + result.message, 'error');
                }
            } catch (err) {
                document.getElementById('modalCarga').classList.add('hidden');
                console.error(err);
                SwalAlert('Error de conexión. Intenta nuevamente.', 'error');
            }
        }
    </script>

    <script>
        

        // Si cambia la fecha, recargar granjas usando esa fecha (y mantener selección si ya eligió una)
        document.getElementById('fectra').addEventListener('change', async function() {
            // Si ya hay granja seleccionada, mantenerla y recalcular edad/campaña
            const tieneSeleccion = !!document.getElementById('granja')?.value;
            await cargarGranjasConFecha({
                preserveSelection: tieneSeleccion
            });
        });

        // Al cambiar granja
        document.getElementById('granja').addEventListener('change', async function() {
            const codigo = this.value;
            const option = this.options[this.selectedIndex];
            const loadingEdit = !!window._loadingEditNecropsia;

            // Limpiar campos dependientes solo si no estamos cargando para edición (ahí usamos valores guardados)
            if (!loadingEdit) {
                document.getElementById('campania').value = '';
                document.getElementById('edad').value = '';
            }
            const selectGalpon = document.getElementById('galpon');
            selectGalpon.innerHTML = '<option value="">Cargando galpones...</option>';
            selectGalpon.disabled = true;

            if (!codigo) return;

            // Al editar usamos edad/campaña ya guardados; no recalcular ni mostrar Swal
            if (!loadingEdit) {
                const edadStr = option.dataset.edad || '';
                const edadNum = parseInt(edadStr, 10);
                if (!isNaN(edadNum) && edadNum <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Fecha inválida',
                        text: 'La edad calculada es negativa. Seleccione una fecha válida para esta granja.',
                        confirmButtonText: 'Aceptar'
                    });
                    this.value = '';
                    document.getElementById('campania').value = '';
                    document.getElementById('edad').value = '';
                    selectGalpon.innerHTML = '<option value="">Seleccione granja primero</option>';
                    selectGalpon.disabled = true;
                    return;
                }
                document.getElementById('edad').value = edadStr;
                document.getElementById('campania').value = codigo.slice(-3);
            }

            // Cargar galpones
            try {
                const response = await fetch(`get_galpones.php?codigo=${codigo}`);
                const galpones = await response.json();

                selectGalpon.innerHTML = '<option value="">Seleccione galpón</option>';

                let maxGalpon = 0;
                let maxOption = null;

                galpones.forEach(g => {
                    const opt = document.createElement('option');
                    opt.value = g.galpon;
                    opt.textContent = `${g.galpon} - ${g.nombre}`;
                    selectGalpon.appendChild(opt);

                    // Guardar el mayor
                    if (parseInt(g.galpon) > maxGalpon) {
                        maxGalpon = parseInt(g.galpon);
                        maxOption = opt;
                    }
                });

                // Autoseleccionar el galpón mayor solo en registro nuevo (al editar se usa el valor guardado)
                if (!loadingEdit && maxOption) {
                    maxOption.selected = true;
                }

                selectGalpon.disabled = false;

            } catch (err) {
                console.error('Error cargando galpones:', err);
                selectGalpon.innerHTML = '<option value="">Error al cargar</option>';
            }
        });

        // Objeto para guardar las imágenes por nivel (máx 3)
        const evidencias = {};

        document.querySelectorAll('input[type="file"][id^="evidencia_"]').forEach(input => {
            const obsId = input.id.replace('evidencia_', '');
            const preview = document.getElementById('preview_' + obsId);

            input.addEventListener('change', function(e) {
                if (!evidencias[obsId]) evidencias[obsId] = [];
                const newFiles = Array.from(this.files);
                const total = evidencias[obsId].length + newFiles.length;

                if (total > 3) {
                    SwalAlert('Máximo 3 imágenes por nivel. Se agregarán solo hasta completar 3.', 'warning');
                    newFiles.splice(3 - evidencias[obsId].length);
                }

                newFiles.forEach(file => {
                    evidencias[obsId].push(file);

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const container = document.createElement('div');
                        container.classList.add('relative', 'inline-block');

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('h-32', 'w-full', 'object-cover', 'rounded-lg');

                        const removeBtn = document.createElement('button');
                        removeBtn.innerHTML = '×';
                        removeBtn.classList.add('absolute', 'top-0', 'right-0', 'bg-red-600', 'text-white', 'text-xs', 'font-bold', 'rounded-full', 'w-6', 'h-6', 'flex', 'items-center', 'justify-center', 'cursor-pointer', 'hover:bg-red-700');
                        removeBtn.style.transform = 'translate(50%, -50%)';
                        removeBtn.onclick = function() {
                            const index = evidencias[obsId].indexOf(file);
                            if (index > -1) {
                                evidencias[obsId].splice(index, 1);
                            }
                            container.remove();
                        };

                        container.appendChild(img);
                        container.appendChild(removeBtn);
                        preview.appendChild(container);
                    };
                    reader.readAsDataURL(file);
                });

                // Limpiar el input para permitir nueva selección
                this.value = '';
            });
        });

        // === FUNCIÓN PARA LIMPIAR TODO EL FORMULARIO Y RESETEAR ESTADO ===
        function limpiarFormularioNecropsia() {
            // 1. Resetear variables globales de control
            isEditMode = false;
            loteEditando = {}; // Limpiamos el objeto de edición
            fechaHoraInicioNec = null;

            // 2. Restaurar el botón a su estado original
            const btnGuardar = document.getElementById('btnGuardarNecropsia');
            if (btnGuardar) {
                btnGuardar.textContent = 'Registrar Necropsia';
                btnGuardar.classList.remove('bg-yellow-500', 'hover:bg-yellow-600'); // Quitar color de edición (opcional)
                btnGuardar.classList.add('bg-green-600', 'hover:bg-green-700'); // Volver al verde original
            }

            // 3. Habilitar campos que se bloquean al editar (si los hubiera)
            const selectGalpon = document.getElementById('galpon');
            if (selectGalpon) {
                selectGalpon.disabled = true; // Se deshabilita al inicio hasta que seleccionen granja
                selectGalpon.innerHTML = '<option value="">Seleccione granja primero</option>';
            }

            // 4. Limpiar campos de cabecera
            document.getElementById('granja').value = '';
            document.getElementById('campania').value = '';
            document.getElementById('edad').value = '';
            document.getElementById('fectra').value = '';
            document.getElementById('txtDiagnosticoPresuntivo').value = '';

            // 5. Desmarcar todos los checkboxes
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.checked = false;
            });

            // 6. Limpiar textareas de observaciones
            document.querySelectorAll('textarea[id^="obs_"]').forEach(textarea => {
                textarea.value = '';
            });

            // 7. Limpiar inputs de archivo y las vistas previas
            document.querySelectorAll('input[type="file"][id^="evidencia_"]').forEach(input => {
                input.value = '';
            });

            document.querySelectorAll('div[id^="preview_"]').forEach(preview => {
                preview.innerHTML = ''; // Esto borra tanto las fotos nuevas como las viejas cargadas
            });

            // 8. Resetear los porcentajes visuales a 0%
            document.querySelectorAll('td[id^="porc_"]').forEach(td => {
                td.textContent = '0%';
            });

            // 9. Limpiar el objeto global de evidencias nuevas
            // (Importante para que no se suban fotos de la sesión anterior)
            for (const key in evidencias) {
                delete evidencias[key];
            }
        }

        let evidenciasActuales = []; // Array de rutas
        let indiceFotoActual = 0;

        function abrirModalEvidencia(rutasEvidencia) {
            if (!rutasEvidencia || rutasEvidencia.trim() === '') return;

            evidenciasActuales = rutasEvidencia.split(',').map(r => r.trim()).filter(r => r);

            if (evidenciasActuales.length === 0) return;

            indiceFotoActual = 0;
            renderizarCarrusel();
            document.getElementById('modalEvidencia').classList.remove('hidden');
        }

        function cerrarModalEvidencia() {
            document.getElementById('modalEvidencia').classList.add('hidden');
            document.getElementById('carruselFotos').innerHTML = '';
            evidenciasActuales = [];
        }

        function renderizarCarrusel() {
            const carrusel = document.getElementById('carruselFotos');
            carrusel.innerHTML = '';

            evidenciasActuales.forEach((ruta, index) => {
                const div = document.createElement('div');
                div.className = 'min-w-full h-full flex items-center justify-center px-4';
                div.innerHTML = `
            <img src="../../${ruta}" alt="Evidencia ${index + 1}" 
                 class="max-w-full max-h-full object-contain rounded-lg shadow-xl">
            `;
                carrusel.appendChild(div);
            });

            // Posicionar en la foto actual
            carrusel.style.transform = `translateX(-${indiceFotoActual * 100}%)`;

            // Actualizar contador
            document.getElementById('contadorFotos').textContent = `${indiceFotoActual + 1} / ${evidenciasActuales.length}`;

            // Ocultar flechas si solo hay una foto
            const prev = document.getElementById('prevFoto');
            const next = document.getElementById('nextFoto');
            if (evidenciasActuales.length <= 1) {
                prev.classList.add('hidden');
                next.classList.add('hidden');
            } else {
                prev.classList.remove('hidden');
                next.classList.remove('hidden');
            }
        }

        // Navegación
        document.getElementById('prevFoto').addEventListener('click', () => {
            if (indiceFotoActual > 0) {
                indiceFotoActual--;
                renderizarCarrusel();
            }
        });

        document.getElementById('nextFoto').addEventListener('click', () => {
            if (indiceFotoActual < evidenciasActuales.length - 1) {
                indiceFotoActual++;
                renderizarCarrusel();
            }
        });

        // Abrir foto actual en nueva pestaña
        function abrirFotoActualEnPestana() {
            if (evidenciasActuales.length > 0) {
                window.open("../../" + evidenciasActuales[indiceFotoActual], '_blank');
            }
        }
    </script>

    <script>
        // === FUNCIÓN EXCLUSIVA PARA ACTUALIZAR ===
        async function actualizarNecropsia() {
            // 1. Recolección de Datos de Cabecera (Igual que guardar, pero usamos datos fijos de edición)
            const granjaSelect = document.getElementById('granja');
            const galponSelect = document.getElementById('galpon');

            const codigoGranja = granjaSelect.value;
            // Recuperamos el nombre para cencos
            const nombreGranja = granjaSelect.options[granjaSelect.selectedIndex]?.textContent.trim() || '';
            const tcencos = nombreGranja.replace(/^\d+ - /, '');
            const campania = document.getElementById('campania').value;
            const edad = document.getElementById('edad').value;
            const galpon = galponSelect.value;
            const fectra = document.getElementById('fectra').value;
            const diagpresuntivo = document.getElementById('txtDiagnosticoPresuntivo').value;

            // VALIDACIÓN IMPORTANTE: Usamos los datos globales de edición para asegurar integridad
            if (!loteEditando.granja || !loteEditando.numreg) {
                SwalAlert("Error de estado: No se identificó la necropsia a editar.", "error");
                return;
            }

            // 2. RECOPILAR IMÁGENES ANTIGUAS (SCRAPING DEL DOM)
            // Buscamos qué imágenes siguen vivas en los divs 'preview_'
            const imagenesExistentes = {};

            // Mapeo ID HTML -> Nombre BD (Mismo que usa tu PHP)
            const mapIdToBdName = {
                'indice_bursal': 'INDICE BURSAL',
                'mucosa_bursa': 'MUCOSA DE LA BURSA',
                'timos': 'TIMOS',
                'higados': 'HIGADO',
                'vesicula': 'VESICULA BILIAR',
                'erosion': 'EROSION DE LA MOLLEJA',
                'pancreas': 'RETRACCION DEL PANCREAS',
                'saco': 'ABSORCION DEL SACO VITELINO',
                'enteritis': 'ENTERITIS',
                'cecal': 'CONTENIDO CECAL',
                'alimento': 'ALIMENTO SIN DIGERIR',
                'heces': 'HECES ANARANJADAS',
                'lesion': 'LESION ORAL',
                'tonicidad': 'TONICIDAD INTESTINAL',
                'traquea': 'TRAQUEA',
                'pulmon': 'PULMON',
                'sacos': 'SACOS AEREOS',
                'pododermatitis': 'PODODERMATITIS',
                'tarsos': 'COLOR TARSOS'
            };

            Object.keys(mapIdToBdName).forEach(obsId => {
                const previewDiv = document.getElementById('preview_' + obsId);
                if (previewDiv) {
                    const imgs = previewDiv.querySelectorAll('img');
                    const rutasLimpias = [];

                    imgs.forEach(img => {
                        if (img.dataset.serverPath) {
                            rutasLimpias.push(img.dataset.serverPath);
                        }
                        // Opción B (Fallback): Analizar el src buscando 'uploads/' y evitando base64
                        else {
                            let src = img.getAttribute('src');
                            if (src.includes('uploads/') && !src.startsWith('data:')) {
                                // Limpiar ../ si existe
                                const cleanPath = src.substring(src.indexOf('uploads/'));
                                rutasLimpias.push(cleanPath);
                            }
                        }
                    });

                    if (rutasLimpias.length > 0) {
                        // Usamos Set para eliminar duplicados visuales por si acaso
                        const unicas = [...new Set(rutasLimpias)];
                        imagenesExistentes[mapIdToBdName[obsId]] = unicas.join(',');
                    }
                }
            });

            // 3. Preparar Objeto Data
            const data = {
                granja: loteEditando.granja, // Usamos la original por seguridad
                galpon: galpon, // El galpón podría haber cambiado (raro pero posible)
                numreg: loteEditando.numreg, // EL ID CRÍTICO
                fectra: fectra, // Fecha crítica
                campania: campania,
                edad: edad,
                tcencos: tcencos,
                diagpresuntivo: diagpresuntivo,
                registros: [],
                imagenes_existentes: imagenesExistentes // Enviamos las rutas viejas
            };

            const parametros = [
                // SISTEMA INMUNOLÓGICO
                {
                    sistema: 'SISTEMA INMUNOLOGICO',
                    nivel: 'INDICE BURSAL',
                    opciones: ['Normal', 'Atrofia', 'Severa Atrofia'],
                    obsId: 'obs_indice_bursal'
                },
                {
                    sistema: 'SISTEMA INMUNOLOGICO',
                    nivel: 'MUCOSA DE LA BURSA',
                    opciones: ['Normal', 'Petequias', 'Hemorragia'],
                    obsId: 'obs_mucosa_bursa'
                },
                {
                    sistema: 'SISTEMA INMUNOLOGICO',
                    nivel: 'TIMOS',
                    opciones: ['Normal', 'Atrofiado', 'Aspecto Normal', 'Congestionado'],
                    obsId: 'obs_timos'
                },

                // SISTEMA DIGESTIVO
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'HIGADO',
                    opciones: ['Normal', 'Esteatosico', 'Tmn. Normal', 'Hipertrofiado'],
                    obsId: 'obs_higados'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'VESICULA BILIAR',
                    opciones: ['Color Normal', 'Color Claro', 'Tam. Normal', 'Atrofiado', 'Hipertrofiado'],
                    obsId: 'obs_vesicula'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'EROSION DE LA MOLLEJA',
                    opciones: ['Normal', 'Grado 1', 'Grado 2', 'Grado 3', 'Grado 4'],
                    obsId: 'obs_erosion'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'RETRACCION DEL PANCREAS',
                    opciones: ['Normal', 'Retraido'],
                    obsId: 'obs_pancreas'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'ABSORCION DEL SACO VITELINO',
                    opciones: ['Sí', 'No'],
                    obsId: 'obs_saco'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'ENTERITIS',
                    opciones: ['Normal', 'Leve', 'Moderado', 'Severo'],
                    obsId: 'obs_enteritis'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'CONTENIDO CECAL',
                    opciones: ['Normal', 'Gas', 'Espuma'],
                    obsId: 'obs_cecal'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'ALIMENTO SIN DIGERIR',
                    opciones: ['Sí', 'No'],
                    obsId: 'obs_alimento'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'HECES ANARANJADAS',
                    opciones: ['Sí', 'No'],
                    obsId: 'obs_heces'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'LESION ORAL',
                    opciones: ['Sí', 'No'],
                    obsId: 'obs_lesion'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'TONICIDAD INTESTINAL',
                    opciones: ['Buena', 'Regular', 'Mala'],
                    obsId: 'obs_tonicidad'
                },

                // SISTEMA RESPIRATORIO
                {
                    sistema: 'SISTEMA RESPIRATORIO',
                    nivel: 'TRAQUEA',
                    opciones: ['Normal', 'Leve', 'Moderada', 'Severa'],
                    obsId: 'obs_traquea'
                },
                {
                    sistema: 'SISTEMA RESPIRATORIO',
                    nivel: 'PULMON',
                    opciones: ['Normal', 'Neumonico'],
                    obsId: 'obs_pulmon'
                },
                {
                    sistema: 'SISTEMA RESPIRATORIO',
                    nivel: 'SACOS AEREOS',
                    opciones: ['Normal', 'Turbio', 'Material Caseoso'],
                    obsId: 'obs_sacos'
                },

                // EVALUACIÓN FÍSICA
                {
                    sistema: 'EVALUACION FISICA',
                    nivel: 'PODODERMATITIS',
                    opciones: ['Grado 0', 'Grado 1', 'Grado 2', 'Grado 3', 'Grado 4'],
                    obsId: 'obs_pododermatitis'
                },
                {
                    sistema: 'EVALUACION FISICA',
                    nivel: 'COLOR TARSOS',
                    opciones: ['3.5', '4.0', '4.5', '5.0', '5.5', '6.0'],
                    obsId: 'obs_tarsos'
                }
            ];

            // 4. Recolección de Filas (Tu misma lógica de barrido de parámetros)
            // NOTA: Asegúrate que la variable 'parametros' (el array grande) es accesible aquí.
            parametros.forEach(param => {
                param.opciones.forEach(opcion => {
                    let idGrupo = '';

                    // --- COPIA AQUÍ TU BLOQUE GIGANTE DE IF/ELSE IF PARA idGrupo ---
                    // (El mismo que usas en el guardar, para no hacer la respuesta infinita no lo pego todo)
                    // Ejemplo:
                    if (param.nivel === 'INDICE BURSAL') {
                        if (opcion === 'Normal') idGrupo = 'indice_normal';
                        else if (opcion === 'Atrofia') idGrupo = 'indice_atrofia';
                        else if (opcion === 'Severa Atrofia') idGrupo = 'indice_severa_atrofia';
                    }
                    // ... resto de ifs ...
                    else if (param.nivel === 'COLOR TARSOS') {
                        if (opcion === '3.5') idGrupo = 'tarsos_35';
                        else if (opcion === '4.0') idGrupo = 'tarsos_40';
                        else if (opcion === '4.5') idGrupo = 'tarsos_45';
                        else if (opcion === '5.0') idGrupo = 'tarsos_50';
                        else if (opcion === '5.5') idGrupo = 'tarsos_55';
                        else if (opcion === '6.0') idGrupo = 'tarsos_60';
                    } else if (param.nivel === 'MUCOSA DE LA BURSA') {
                        if (opcion === 'Normal') idGrupo = 'mucosa_normal';
                        else if (opcion === 'Petequias') idGrupo = 'mucosa_petequias';
                        else if (opcion === 'Hemorragia') idGrupo = 'mucosa_hemorragia';
                    } else if (param.nivel === 'TIMOS') {
                        if (opcion === 'Normal') idGrupo = 'timos_normal';
                        else if (opcion === 'Atrofiado') idGrupo = 'timos_atrofiado';
                        else if (opcion === 'Aspecto Normal') idGrupo = 'timos_aspecto_normal';
                        else if (opcion === 'Congestionado') idGrupo = 'timos_congestionado';
                    } else if (param.nivel === 'HIGADO') {
                        if (opcion === 'Normal') idGrupo = 'higados_normal';
                        else if (opcion === 'Esteatosico') idGrupo = 'higados_esteatosico';
                        else if (opcion === 'Tmn. Normal') idGrupo = 'higados_tmnnormal';
                        else if (opcion === 'Hipertrofiado') idGrupo = 'higados_hipertrofiado';
                    } else if (param.nivel === 'VESICULA BILIAR') {
                        if (opcion === 'Color Normal') idGrupo = 'vesicula_color_normal';
                        else if (opcion === 'Color Claro') idGrupo = 'vesicula_color_claro';
                        else if (opcion === 'Tam. Normal') idGrupo = 'vesicula_tam_normal';
                        else if (opcion === 'Atrofiado') idGrupo = 'vesicula_atrofiado';
                        else if (opcion === 'Hipertrofiado') idGrupo = 'vesicula_hipertrofiado';
                    } else if (param.nivel === 'EROSION DE LA MOLLEJA') {
                        if (opcion === 'Normal') idGrupo = 'erosion_normal';
                        else if (opcion === 'Grado 1') idGrupo = 'erosion_grado1';
                        else if (opcion === 'Grado 2') idGrupo = 'erosion_grado2';
                        else if (opcion === 'Grado 3') idGrupo = 'erosion_grado3';
                        else if (opcion === 'Grado 4') idGrupo = 'erosion_grado4';
                    } else if (param.nivel === 'RETRACCION DEL PANCREAS') {
                        if (opcion === 'Normal') idGrupo = 'pancreas_normal';
                        else if (opcion === 'Retraido') idGrupo = 'pancreas_retraido';
                    } else if (param.nivel === 'ABSORCION DEL SACO VITELINO') {
                        if (opcion === 'Sí') idGrupo = 'saco_si';
                        else if (opcion === 'No') idGrupo = 'saco_no';
                    } else if (param.nivel === 'ENTERITIS') {
                        if (opcion === 'Normal') idGrupo = 'enteritis_normal';
                        else if (opcion === 'Leve') idGrupo = 'enteritis_leve';
                        else if (opcion === 'Moderado') idGrupo = 'enteritis_moderado';
                        else if (opcion === 'Severo') idGrupo = 'enteritis_severo';
                    } else if (param.nivel === 'CONTENIDO CECAL') {
                        if (opcion === 'Normal') idGrupo = 'cecal_normal';
                        else if (opcion === 'Gas') idGrupo = 'cecal_gas';
                        else if (opcion === 'Espuma') idGrupo = 'cecal_espuma';
                    } else if (param.nivel === 'ALIMENTO SIN DIGERIR') {
                        if (opcion === 'Sí') idGrupo = 'alimento_si';
                        else if (opcion === 'No') idGrupo = 'alimento_no';
                    } else if (param.nivel === 'HECES ANARANJADAS') {
                        if (opcion === 'Sí') idGrupo = 'heces_si';
                        else if (opcion === 'No') idGrupo = 'heces_no';
                    } else if (param.nivel === 'LESION ORAL') {
                        if (opcion === 'Sí') idGrupo = 'lesion_si';
                        else if (opcion === 'No') idGrupo = 'lesion_no';
                    } else if (param.nivel === 'TONICIDAD INTESTINAL') {
                        if (opcion === 'Buena') idGrupo = 'tonicidad_buena';
                        else if (opcion === 'Regular') idGrupo = 'tonicidad_regular';
                        else if (opcion === 'Mala') idGrupo = 'tonicidad_mala';
                    } else if (param.nivel === 'TRAQUEA') {
                        if (opcion === 'Normal') idGrupo = 'traquea_normal';
                        else if (opcion === 'Leve') idGrupo = 'traquea_leve';
                        else if (opcion === 'Moderada') idGrupo = 'traquea_moderada';
                        else if (opcion === 'Severa') idGrupo = 'traquea_severa';
                    } else if (param.nivel === 'PULMON') {
                        if (opcion === 'Normal') idGrupo = 'pulmon_normal';
                        else if (opcion === 'Neumonico') idGrupo = 'pulmon_neumonico';
                    } else if (param.nivel === 'SACOS AEREOS') {
                        if (opcion === 'Normal') idGrupo = 'sacos_normal';
                        else if (opcion === 'Turbio') idGrupo = 'sacos_turbio';
                        else if (opcion === 'Material Caseoso') idGrupo = 'sacos_caseoso';
                    } else if (param.nivel === 'PODODERMATITIS') {
                        if (opcion === 'Grado 0') idGrupo = 'pododermatitis_grado0';
                        else if (opcion === 'Grado 1') idGrupo = 'pododermatitis_grado1';
                        else if (opcion === 'Grado 2') idGrupo = 'pododermatitis_grado2';
                        else if (opcion === 'Grado 3') idGrupo = 'pododermatitis_grado3';
                        else if (opcion === 'Grado 4') idGrupo = 'pododermatitis_grado4';
                    }

                    // --- FIN BLOQUE MAPEO ---

                    if (idGrupo) {
                        const checkboxes = document.querySelectorAll(`input[onchange*="('${idGrupo}')"]`);
                        const porcElement = document.getElementById('porc_' + idGrupo);
                        const porcentajeTotal = porcElement ? parseFloat(porcElement.textContent.replace('%', '')) : 0;

                        const aves = [0, 0, 0, 0, 0];
                        checkboxes.forEach((cb, i) => {
                            if (cb.checked) aves[i] = 20;
                        });

                        data.registros.push({
                            tsistema: param.sistema,
                            tnivel: param.nivel,
                            tparametro: opcion,
                            tporcentaje1: aves[0],
                            tporcentaje2: aves[1],
                            tporcentaje3: aves[2],
                            tporcentaje4: aves[3],
                            tporcentaje5: aves[4],
                            tporcentajetotal: porcentajeTotal,
                            tobservacion: document.getElementById(param.obsId)?.value.trim() || ''
                        });
                    }
                });
            });

            // 5. ENVIAR DATOS
            try {
                document.getElementById('modalCarga').classList.remove('hidden');
                document.querySelector('#modalCarga p.font-semibold').textContent = 'Actualizando registros...';

                const formData = new FormData();
                formData.append('data', JSON.stringify(data));

                // Adjuntar SOLO las imágenes NUEVAS que están en los inputs file
                Object.keys(evidencias).forEach(obsId => {
                    evidencias[obsId].forEach(file => {
                        formData.append(`evidencia_${obsId}[]`, file);
                    });
                });

                const response = await fetch('actualizar_necropsia.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                document.getElementById('modalCarga').classList.add('hidden');

                if (result.success) {
                    SwalAlert(result.message, result.success ? 'success' : 'error');
                    document.getElementById('modalNecropsia').classList.add('hidden');
                    limpiarFormularioNecropsia();

                    // Resetear variables globales de edición
                    isEditMode = false;
                    loteEditando = {};
                    document.getElementById('btnGuardarNecropsia').textContent = 'Registrar Necropsia';
                    document.getElementById('galpon').disabled = false;

                    $('#tabla').DataTable().ajax.reload();
                } else {
                    SwalAlert('Error: ' + result.message, 'error');
                }

            } catch (err) {
                document.getElementById('modalCarga').classList.add('hidden');
                console.error(err);
                SwalAlert('Error de conexión al actualizar.', 'error');
            }
        }

        async function eliminarNecropsia(granja, numreg, fectra) {
            // 1. Confirmación con SweetAlert2
            const result = await Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción eliminará permanentemente los registros y las fotos asociadas. ¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Rojo para indicar peligro
                cancelButtonColor: '#3085d6', // Azul para cancelar
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true, // Pone el botón de cancelar primero (más seguro UX)
                focusCancel: true
            });

            if (!result.isConfirmed) {
                return; // El usuario canceló
            }

            // 2. Preparar datos
            const formData = new FormData();
            formData.append('granja', granja);
            formData.append('numreg', numreg);
            formData.append('fectra', fectra);

            try {
                // 3. Mostrar Loading (Bloqueamos la pantalla con SweetAlert)
                Swal.fire({
                    title: 'Eliminando...',
                    text: 'Por favor espera, borrando archivos y datos.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading(); // Muestra el spinner de carga
                    }
                });

                // 4. Petición al servidor
                const response = await fetch('eliminar_necropsia.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                // 5. Manejar respuesta
                if (data.success) {
                    // Éxito: Mensaje bonito y recarga
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: 'La necropsia ha sido eliminada correctamente.',
                        timer: 1500, // Se cierra solo en 1.5 seg
                        showConfirmButton: false
                    });

                    // Recargar la tabla manteniendo la paginación actual
                    $('#tabla').DataTable().ajax.reload(null, false);
                } else {
                    // Error lógico (ej: no se encontró registro)
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo eliminar el registro.'
                    });
                }

            } catch (error) {
                console.error('Error:', error);
                // Error técnico (ej: servidor caído, error 500)
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'Ocurrió un problema al intentar conectar con el servidor. Por favor intenta de nuevo.'
                });
            }
        }
    </script>


</body>

</html>