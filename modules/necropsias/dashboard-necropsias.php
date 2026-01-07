<?php

session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

//ruta relativa a la conexion
include_once '../../../conexion_grs_joya/conexion.php';
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
    <title>Dashboard - necropsia</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

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


        <!-- Botón para abrir el modal (igual) -->
        <button id="btnRegistrarNecropsia" class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-300">
            Registrar Necropsia
        </button>

        <!-- Modal con Tabs -->
        <div id="modalNecropsia" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-800 bg-opacity-75 flex items-center justify-center">
            <div class="bg-white rounded-xl shadow-2xl p-8 max-w-7xl w-full mx-4 max-h-[95vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Registro de Necropsia</h2>
                    <button id="closeModal" class="text-gray-500 hover:text-gray-700 text-3xl">&times;</button>
                </div>

                <div id="contenidoNecropsia">
                    <!-- Cabecera Inteligente Mejorada -->
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8 bg-gray-50 p-4 rounded-lg">
                        <!-- GRANJA (select más grande) -->
                        <div class="md:col-span-2"> <!-- Ocupa 2 columnas en desktop para más espacio -->
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

                        <!-- FECHA NECROPSIA -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">FECHA</label>
                            <input type="date" id="fectra" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>

                        <!-- NÚM. REG. -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">NÚM. REG.</label>
                            <input type="text" id="numreg" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="border-b border-gray-200 mb-6">
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
                                <tr class="bg-blue-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="2">ÍNDICE BURSAL*</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('indice_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_indice_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="2">
                                        <textarea id="obs_indice_bursal" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
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

                                <!-- MUCOSA DE LA BURSA -->
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="2">TIMOS</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_timos_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="2">
                                        <textarea id="obs_timos" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Atrofiados</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_atrofiados')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_atrofiados')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_atrofiados')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_atrofiados')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('timos_atrofiados')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_timos_atrofiados">0%</td>
                                </tr>

                            </tbody>
                        </table>

                    </div>

                    <!-- sistema digestivo-->
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
                                <tr class="bg-blue-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="3">HÍGADOS</td>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_higados_normal">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="3">
                                        <textarea id="obs_higados" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="4"></textarea>
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
                                    <td class="px-4 py-4">Color Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_color_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_color_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_color_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_color_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('higados_color_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_higados_color_normal">0%</td>
                                </tr>

                                <!-- VESÍCULA BILIAR -->
                                <tr class="bg-blue-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="4">VESÍCULA BILIAR</td>
                                    <td class="px-4 py-4">Color Claro</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_claro')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_claro')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_claro')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_claro')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_color_claro')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_vesicula_color_claro">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="4">
                                        <textarea id="obs_vesicula" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="5"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Tam. Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_vesicula_tam_normal">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Tam. Agrandado</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_agrandado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_agrandado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_agrandado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_agrandado')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_tam_agrandado')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_vesicula_tam_agrandado">0%</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('vesicula_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_vesicula_normal">0%</td>
                                </tr>

                                <!-- EROSIÓN DE LA MOLLEJA -->
                                <tr class="bg-blue-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="4">EROSIÓN DE LA MOLLEJA</td>
                                    <td class="px-4 py-4">Grado 1</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado1')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_grado1')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_erosion_grado1">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="4">
                                        <textarea id="obs_erosion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="5"></textarea>
                                    </td>
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
                                    <td class="px-4 py-4">Normal</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_normal')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('erosion_normal')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_erosion_normal">0%</td>
                                </tr>

                                <!-- RETRACCIÓN DEL PÁNCREAS -->
                                <tr class="bg-blue-50 font-medium">
                                    <td class="px-4 py-4 align-top" rowspan="2">RETRACCIÓN DEL PÁNCREAS</td>
                                    <td class="px-4 py-4">Sí</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_si')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_si')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pancreas_si">0%</td>
                                    <td class="px-4 py-4 align-top" rowspan="2">
                                        <textarea id="obs_pancreas" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" rows="3"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-4">No</td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_no')"></td>
                                    <td class="px-4 py-4 text-center"><input type="checkbox" class="w-5 h-5 text-green-600 focus:ring-green-500" onchange="calcularPorcentaje('pancreas_no')"></td>
                                    <td class="px-4 py-4 text-center font-bold text-lg" id="porc_pancreas_no">0%</td>
                                </tr>

                                <!-- ABSORCIÓN DEL SACO VITELINO -->
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
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
                                <tr class="bg-blue-50 font-medium">
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

                    <!-- Botones al final -->
                    <div class="flex justify-end mt-8 gap-4">
                        <button type="button" id="closeModalBtn" class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                            Cancelar
                        </button>
                        <button type="button" id="btnGuardarNecropsia" class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                            Guardar Necropsia
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0 mt-5">
            <div class="table-wrapper overflow-x-auto">
                <table id="tabla" class="data-table w-full text-sm border-collapse">
                    <thead class="bg-gray-100 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Granja</th>
                            <th class="px-4 py-3 text-left">Camp.</th>
                            <th class="px-4 py-3 text-left">Galpón</th>
                            <th class="px-4 py-3 text-left">Edad</th>
                            <th class="px-4 py-3 text-left">Núm. Reg.</th>
                            <th class="px-4 py-3 text-left">Usuario</th>
                            <th class="px-4 py-3 text-left">Registrado</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
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

    </div>

    <script>
        $(document).ready(function() {
            let tabla = $('#tabla').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'listar_necropsias.php',
                    type: 'POST'
                },
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'desc']
                ], // Ordenar por fecha necropsia descendente
                columns: [{
                        data: 'tfectra',
                        render: function(data) {
                            if (!data || data === '1000-01-01') return '-';
                            const date = new Date(data);
                            return date.toLocaleDateString('es-PE');
                        }
                    },
                    {
                        data: 'tgranja'
                    },
                    {
                        data: 'tcampania'
                    },
                    {
                        data: 'tgalpon',
                        className: 'text-center font-medium'
                    },
                    {
                        data: 'tedad',
                        className: 'text-center'
                    },
                    {
                        data: 'tnumreg',
                        className: 'text-center font-medium'
                    },
                    {
                        data: 'tuser'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (!row.tdate || row.tdate === '1000-01-01') return '-';
                            const date = new Date(row.tdate);
                            return date.toLocaleDateString('es-PE') + ' ' +
                                date.toLocaleTimeString('es-PE', {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                        }
                    }
                ]
            });
        });
    </script>

    <script>
        // Abrir y cerrar modal (igual que antes)
        document.getElementById('btnRegistrarNecropsia').addEventListener('click', () => {
            document.getElementById('modalNecropsia').classList.remove('hidden');
        });

        document.getElementById('closeModal').addEventListener('click', () => {
            document.getElementById('modalNecropsia').classList.add('hidden');
        });

        document.getElementById('closeModalBtn').addEventListener('click', () => {
            document.getElementById('modalNecropsia').classList.add('hidden');
        });

        // Tabs (igual que antes)
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
            // Recolectar cabecera
            const data = {
                granja: document.getElementById('granja').value.trim(),
                campania: document.getElementById('campania').value.trim(),
                galpon: document.getElementById('galpon').value.trim(),
                edad: document.getElementById('edad').value.trim(),
                fectra: document.getElementById('fectra').value,
                numreg: document.getElementById('numreg').value.trim(),
                registros: [] // Aquí van todas las filas
            };

            if (!data.granja || !data.campania || !data.galpon || !data.edad || !data.fectra || !data.numreg) {
                alert('Por favor completa todos los campos de la cabecera');
                return;
            }

            // Definir todos los parámetros y sus opciones (exactamente como en la BD)
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
                    opciones: ['Color Claro', 'Tam. Normal', 'Tam. Agrandado', 'Normal'],
                    obsId: 'obs_vesicula'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'EROSION DE LA MOLLEJA',
                    opciones: ['Grado 1', 'Grado 2', 'Grado 3', 'Normal'],
                    obsId: 'obs_erosion'
                },
                {
                    sistema: 'SISTEMA DIGESTIVO',
                    nivel: 'RETRACCION DEL PANCREAS',
                    opciones: ['Sí', 'No'],
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

            // Recorrer cada parámetro y generar filas
            parametros.forEach(param => {
                param.opciones.forEach(opcion => {
                    const idGrupo = param.nivel.toLowerCase().replace(/ /g, '_') + '_' + opcion.toLowerCase().replace(/[^a-z0-9]/g, '');
                    const checkboxes = document.querySelectorAll(`input[onchange*="('${idGrupo}')"]`);
                    const porcElement = document.getElementById('porc_' + idGrupo);
                    const porcentajeTotal = porcElement ? parseInt(porcElement.textContent) : 0;

                    // Solo insertamos si hay al menos una marca o es necesario (en los ejemplos insertan todo)
                    // Pero para optimizar, insertamos siempre (como en los registros antiguos)
                    const aves = [0, 0, 0, 0, 0];
                    checkboxes.forEach((cb, index) => {
                        if (cb.checked) aves[index] = 20;
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
                const response = await fetch('guardar_necropsia.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    alert('¡Necropsia registrada con éxito!');
                    document.getElementById('modalNecropsia').classList.add('hidden');
                    // Opcional: limpiar formulario o recargar tabla
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (err) {
                console.error(err);
                alert('Error de conexión');
            }
        });
    </script>

    <script>
        // Cargar granjas al abrir el modal
        document.getElementById('btnRegistrarNecropsia').addEventListener('click', async () => {
            document.getElementById('modalNecropsia').classList.remove('hidden');

            try {
                const response = await fetch('get_granjas.php');
                const granjas = await response.json();

                const select = document.getElementById('granja');
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
                document.getElementById('granja').innerHTML = '<option value="">Error al cargar</option>';
            }
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
            document.getElementById('edad').value = option.dataset.edad || '';

            // Extraer campaña del nombre (C=126)
            const texto = option.textContent;
            const match = texto.match(/C=(\d+)/);
            if (match) {
                document.getElementById('campania').value = match[1];
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
    </script>

</body>

</html>