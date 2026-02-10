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

    <!-- Nota: se removió DataTables de esta pantalla porque ahora es SOLO Registro (sin listado/edición) -->

    <link rel="stylesheet" href="../../css/dashboard-responsive.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/sweetalert-helpers.js"></script>

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .plan-enlace-recadro:not(:empty) {
            padding: 0.35rem 0.6rem;
            border: 1px solid #d1e7dd;
            border-radius: 0.5rem;
            background: #f0f9f4;
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

<body class="bg-sky-50">
    <div class="container-fluid py-4 px-4 sm:px-6 lg:px-8 max-w-full overflow-x-hidden">
        <div class="bg-sky-50 rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6">                

            <div id="contenidoNecropsia" class="necropsia-form-wrap">                        
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4 mb-8 bg-gray-50 p-4 rounded-lg necropsia-cabecera-grid">
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

                    <!-- Tabs -->
                    <div class="border-b border-gray-200 mb-6 necropsia-tabs-nav">
                        <nav class="-mb-px flex space-x-8">
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

                    <!-- Contenido de los Tabs -->
                    <!-- sistema inmunologico -->
                    <div class="tab-content" id="inmunologico">

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

                                <!-- ÍNDICE BURSAL -->
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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

                    <!-- sistema digestivo -->
                    <div class="tab-content hidden" id="digestivo">
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

                                <!-- HÍGADOS -->
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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

                    <!-- sistema respiratorio-->
                    <div class="tab-content hidden" id="respiratorio">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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

                    <!-- evaluacion fisica-->
                    <div class="tab-content hidden" id="evaluacion">
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

                                <!-- PODODERMATITIS -->
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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
                                <tr class="bg-sky-50 font-medium border-b border-sky-100">
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

                <!-- Botones al final -->
                <div class="flex flex-wrap justify-end items-center mt-8 gap-3 sm:gap-4">
                    <button type="button" id="btnLimpiarFormulario" class="px-4 sm:px-6 py-2.5 sm:py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition w-full sm:w-auto flex-shrink-0 min-w-0">
                        Limpiar
                    </button>
                    <button type="button" id="btnGuardarNecropsia" class="px-4 sm:px-6 py-2.5 sm:py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition w-full sm:w-auto flex-shrink-0 min-w-0 whitespace-nowrap overflow-visible">
                        Guardar Necropsia
                    </button>
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

        <!-- Lightbox para ver imagen en grande (igual que en listado/editar) -->
        <div id="modalImagenLightbox" class="hidden fixed inset-0 flex items-center justify-center p-4" style="z-index: 99999;">
            <div class="absolute inset-0 bg-black/60" id="lightboxBackdrop" style="cursor: pointer;"></div>
            <div class="relative z-10 border-4 border-blue-500 rounded-xl bg-sky-50 shadow-2xl p-3 flex flex-col" id="lightboxContenedor" style="cursor: default; width: min(90vw, 600px); max-height: 85vh;">
                <button type="button" id="lightboxCerrar" class="absolute top-2 right-2 z-20 w-10 h-10 flex items-center justify-center rounded-full bg-red-600 hover:bg-red-700 text-white text-xl font-bold shadow-lg" aria-label="Cerrar">&times;</button>
                <div class="mt-10 overflow-auto flex items-center justify-center rounded-lg bg-white/80" style="min-height: 200px; max-height: calc(85vh - 4rem);">
                    <img id="lightboxImg" src="" alt="" class="rounded-lg" style="max-width: 100%; max-height: 70vh; width: auto; height: auto; object-fit: contain; display: block;">
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
        // Helpers
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

        // Inicialización (pantalla de registro)
        document.addEventListener('DOMContentLoaded', async () => {
            // Setear fecha por defecto (hoy) si está vacía
            const fectraInput = document.getElementById('fectra');
            if (fectraInput && !fectraInput.value) {
                fectraInput.value = getFechaSeleccionada();
            }

            // Cargar granjas usando la fecha actual
            await cargarGranjasConFecha({ preserveSelection: false });
        });
    </script>

    <script>
        

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
        // Botón limpiar (reset del formulario)
        document.getElementById('btnLimpiarFormulario').addEventListener('click', () => {
            limpiarFormularioNecropsia();
        });

        function activarPrimeraTabRegistro() {
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('border-green-600', 'text-green-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            const firstTab = document.querySelector('.tab-button');
            if (firstTab) {
                firstTab.classList.remove('border-transparent', 'text-gray-500');
                firstTab.classList.add('border-green-600', 'text-green-600');
                const panel = document.getElementById(firstTab.getAttribute('data-tab'));
                if (panel) panel.classList.remove('hidden');
            }
        }

        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('border-green-600', 'text-green-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });
                document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
                button.classList.remove('border-transparent', 'text-gray-500');
                button.classList.add('border-green-600', 'text-green-600');
                document.getElementById(button.dataset.tab).classList.remove('hidden');
            });
        });

        document.addEventListener('DOMContentLoaded', activarPrimeraTabRegistro);

        // Calcular porcentaje: solo los 5 checkboxes de la fila que contiene porc_X (evita mezclar filas)
        function calcularPorcentaje(idGrupo) {
            const elemento = document.getElementById('porc_' + idGrupo);
            const row = elemento ? elemento.closest('tr') : null;
            const checkboxes = row ? row.querySelectorAll('input[type="checkbox"]') : [];
            let count = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) count++;
            });
            const porcentaje = count * 20; // Cada ave = 20%, máx 5 = 100%
            if (elemento) elemento.textContent = porcentaje + '%';
        }

        // GUARDAR CON AJAX
        document.getElementById('btnGuardarNecropsia').addEventListener('click', async () => {
            // Solo registro (sin edición)
            guardarNecropsia();
        });

        // Variable para guardar la hora de inicio
        let fechaHoraInicio = null;
        let planIdSeleccionado = ''; // Sin enlace planificación; se mantiene vacío para compatibilidad con guardar

        // Función para formatear fecha a MySQL (YYYY-MM-DD HH:MM:SS)
        function obtenerFechaHoraActual() {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const hh = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd} ${hh}:${min}:${ss}`;
        }

        // Detector de interacción
        function detectarInicioActividad() {
            if (fechaHoraInicio === null) {
                fechaHoraInicio = obtenerFechaHoraActual();
                console.log("Inicio de registro detectado:", fechaHoraInicio);
            }
        }

        // Asignar el detector al contenedor principal del formulario
        document.addEventListener('DOMContentLoaded', () => {
            const contenedor = document.getElementById('contenidoNecropsia');
            
            // Detectar cualquier cambio (selects, checkbox) o escritura (inputs)
            contenedor.addEventListener('change', detectarInicioActividad);
            contenedor.addEventListener('input', detectarInicioActividad);
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

            // Diagnóstico presuntivo es opcional

            // CAPTURAR FECHA FIN (Hora actual al momento de dar click)
            const fechaHoraFin = obtenerFechaHoraActual();
            
            // Si por alguna razón no detectó inicio (ej. no tocó nada y dio guardar), usar la fecha actual
            if (fechaHoraInicio === null) {
                fechaHoraInicio = fechaHoraFin;
            }

            // === GENERAR NÚMERO DE REGISTRO AUTOMÁTICO: HHMMSS ===
            const now = new Date();
            const horas = String(now.getHours()).padStart(2, '0');
            const minutos = String(now.getMinutes()).padStart(2, '0');
            const segundos = String(now.getSeconds()).padStart(2, '0');
            const numreg = horas + minutos + segundos;

            const data = {
                granja: codigoGranja, // → tgranja
                campania: campania, // → tcampania
                edad: edad, // → tedad
                galpon: galpon, // → tgalpon
                fectra: fectra, // → tfectra
                numreg: numreg, // → tnumreg
                tcencos: tcencos, // → tcencos (nombre completo)
                planId: planIdSeleccionado, // Enlace con planificación (si aplica)
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

                    // Obtener los 5 checkboxes de ESTA fila (porc_X está en la misma tr que los checkboxes)
                    const porcEl = document.getElementById('porc_' + idGrupo);
                    const row = porcEl ? porcEl.closest('tr') : null;
                    const checkboxesInRow = row ? row.querySelectorAll('input[type="checkbox"]') : [];
                    const aves = [0, 0, 0, 0, 0];
                    for (let i = 0; i < 5 && i < checkboxesInRow.length; i++) {
                        if (checkboxesInRow[i].checked) aves[i] = 20;
                    }
                    // Total = suma de tporcentaje1 a tporcentaje5 (siempre consistente)
                    const porcentajeTotal = aves[0] + aves[1] + aves[2] + aves[3] + aves[4];

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
                    limpiarFormularioNecropsia();
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
        // (Antes) se cargaban granjas al abrir el modal. Ahora se cargan al iniciar (ver DOMContentLoaded).

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

            // Limpiar campos dependientes
            document.getElementById('campania').value = '';
            document.getElementById('edad').value = '';
            const selectGalpon = document.getElementById('galpon');
            selectGalpon.innerHTML = '<option value="">Cargando galpones...</option>';
            selectGalpon.disabled = true;

            if (!codigo) return;

            // Rellenar edad
            const edadStr = option.dataset.edad || '';
            const edadNum = parseInt(edadStr, 10);
            if (!isNaN(edadNum) && edadNum <= 0) {
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha inválida',
                    text: 'La edad calculada es negativa. Seleccione una fecha válida para esta granja.',
                    confirmButtonText: 'Aceptar'
                });

                // Resetear selección y dependencias
                this.value = '';
                document.getElementById('campania').value = '';
                document.getElementById('edad').value = '';
                selectGalpon.innerHTML = '<option value="">Seleccione granja primero</option>';
                selectGalpon.disabled = true;
                return;
            }
            document.getElementById('edad').value = edadStr;

            // Campaña: últimos 3 dígitos del código de granja (tgranja)
            document.getElementById('campania').value = codigo.slice(-3);

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

                // Autoseleccionar el galpón mayor
                if (maxOption) {
                    maxOption.selected = true;
                }

                selectGalpon.disabled = false;

            } catch (err) {
                console.error('Error cargando galpones:', err);
                selectGalpon.innerHTML = '<option value="">Error al cargar</option>';
            }
        });

        document.getElementById('galpon').addEventListener('change', function() {});

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
                        img.classList.add('h-32', 'w-full', 'object-cover', 'rounded-lg', 'cursor-pointer', 'hover:opacity-90');
                        img.alt = 'Evidencia';
                        img.title = 'Clic para ver en grande';

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

        // Lightbox: al hacer clic en una imagen de preview, mostrarla en grande (igual que en listado/editar)
        document.getElementById('contenidoNecropsia')?.addEventListener('click', function(e) {
            if (e.target.tagName === 'IMG' && e.target.closest('div[id^="preview_"]')) {
                const modal = document.getElementById('modalImagenLightbox');
                const lightboxImg = document.getElementById('lightboxImg');
                if (modal && lightboxImg) {
                    lightboxImg.src = e.target.currentSrc || e.target.src;
                    lightboxImg.alt = e.target.alt || 'Evidencia';
                    modal.classList.remove('hidden');
                }
            }
        });
        function cerrarLightbox() {
            document.getElementById('modalImagenLightbox')?.classList.add('hidden');
        }
        document.getElementById('lightboxCerrar')?.addEventListener('click', cerrarLightbox);
        document.getElementById('lightboxBackdrop')?.addEventListener('click', cerrarLightbox);
        document.getElementById('lightboxContenedor')?.addEventListener('click', function(e) { e.stopPropagation(); });
        document.getElementById('lightboxImg')?.addEventListener('click', function(e) { e.stopPropagation(); });

        // === FUNCIÓN PARA LIMPIAR TODO EL FORMULARIO Y RESETEAR ESTADO ===
        function limpiarFormularioNecropsia() {
            // Restaurar el botón a su estado original (solo registro)
            const btnGuardar = document.getElementById('btnGuardarNecropsia');
            if (btnGuardar) {
                btnGuardar.textContent = 'Guardar Necropsia';
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
            fechaHoraInicio = null;
            planIdSeleccionado = '';

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

            for (const key in evidencias) {
                delete evidencias[key];
            }

            if (typeof activarPrimeraTabRegistro === 'function') activarPrimeraTabRegistro();
        }

        let evidenciasActuales = []; // Array de rutas
        let indiceFotoActual = 0;

    
    </script>



</body>

</html>