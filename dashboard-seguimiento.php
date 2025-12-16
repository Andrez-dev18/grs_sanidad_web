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

$estadoCuali  = strtolower(trim($row['estado_cuali'] ?? 'pendiente'));
$estadoCuanti = strtolower(trim($row['estado_cuanti'] ?? 'pendiente'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'cargar_detalle') {

        $codEnvio = $_POST['codEnvio'];

        $sql = "
            SELECT 
                codEnvio,
                posSolicitud,
                codRef,
                fecToma,
                numMuestras,
                nomMuestra,
                nomAnalisis,
                estado_cuali,
                estado_cuanti,
                obs
            FROM san_fact_solicitud_det
            WHERE codEnvio = ?
            ORDER BY posSolicitud ASC
        ";


        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $codEnvio);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $estadoCuali  = strtolower(trim($row['estado_cuali'] ?? 'pendiente'));
                $estadoCuanti = strtolower(trim($row['estado_cuanti'] ?? 'pendiente'));

                $claseCuali = ($estadoCuali === 'completado')
                    ? 'bg-green-100 text-green-700'
                    : 'bg-yellow-100 text-yellow-700';

                $claseCuanti = ($estadoCuanti === 'completado')
                    ? 'bg-green-100 text-green-700'
                    : 'bg-yellow-100 text-yellow-700';


                echo "<tr>
                                <td class='px-4 py-2'>{$row['codEnvio']}</td>
                    <td class='px-4 py-2'>{$row['posSolicitud']}</td>
                    <td class='px-4 py-2'>{$row['codRef']}</td>
                    <td class='px-4 py-2'>{$row['fecToma']}</td>
                    <td class='px-4 py-2 text-center'>{$row['numMuestras']}</td>
                    <td class='px-4 py-2'>{$row['nomMuestra']}</td>
                    <td class='px-4 py-2'>{$row['nomAnalisis']}</td>

                    <td class='px-4 py-2 text-center'>
                        <span class='inline-block px-3 py-1 rounded-full text-xs font-semibold {$claseCuali}'>
                            " . ucfirst($estadoCuali) . "
                        </span>
                    </td>

                    <td class='px-4 py-2 text-center'>
                        <span class='inline-block px-3 py-1 rounded-full text-xs font-semibold {$claseCuanti}'>
                            " . ucfirst($estadoCuanti) . "
                        </span>
                    </td>

                    <td class='px-4 py-2'>{$row['obs']}</td>
                </tr>";
            }
        } else {
            echo "<tr>
                    <td colspan='9' class='text-center py-4 text-gray-500'>
                        No hay detalle para este envío
                    </td>
                  </tr>";
        }

        exit; // ⛔ ESTO ES CLAVE
    }

    // Agregar esto DESPUÉS del bloque de cargar_detalle y ANTES del cierre de if ($_SERVER['REQUEST_METHOD'])

    if ($_POST['accion'] === 'cargar_resultados') {

        $codEnvio = $_POST['codEnvio'];

        $sql = "
        SELECT 
            codEnvio,
            posSolicitud,
            codRef,
            fecToma,
            analisis_nombre,
            resultado,
            usuarioRegistrador,
            fechaLabRegistro,
            obs
        FROM san_fact_resultado_analisis
        WHERE codEnvio = ?
        ORDER BY posSolicitud ASC
    ";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $codEnvio);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                <td class='px-4 py-2'>" . htmlspecialchars($row['codEnvio']) . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['posSolicitud']) . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['codRef']) . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['fecToma']) . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['analisis_nombre']) . "</td>
                <td class='px-4 py-2'>
                    <span class='inline-block px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700'>
                        " . htmlspecialchars($row['resultado']) . "
                    </span>
                </td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['usuarioRegistrador'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['fechaLabRegistro'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['obs'] ?? '') . "</td>
            </tr>";
            }
        } else {
            echo "<tr>
                <td colspan='9' class='text-center py-4 text-gray-500'>
                    No hay resultados de análisis para este envío
                </td>
              </tr>";
        }

        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Resultado de lab</title>

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

        /* Evitar estilos default de DataTables */
        .dataTables_wrapper table {
            border-collapse: separate !important;
            border-spacing: 0;
        }

        /* Mantener separación visual entre filas */
        .dataTables_wrapper tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        /* Inputs y selects integrados con Tailwind */
        .dataTables_wrapper input[type="search"],
        .dataTables_wrapper select {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.4rem 0.75rem;
            font-size: 0.875rem;
        }

        /* Paginación más limpia */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid transparent;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background-color: #2563eb !important;
            color: white !important;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- VISTA EMPRESAS DE TRANSPORTE -->
        <div id="viewEmpresasTransporte" class="content-view">
            <div class="form-container w-full mb-4">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-4xl">🗒️</span>
                    <h1 class="text-3xl font-bold text-gray-800">Seguimiento</h1>
                </div>

            </div>

            <div class="form-container w-full">
                <!-- Botones de acción -->
                <div class="mb-6 flex justify-between items-center flex-wrap gap-3">
                    <button type="button"
                        class="px-6 py-2.5 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2"
                        onclick="exportarReporteExcel()"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);"
                        onmouseover="this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'"
                        onmouseout="this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'">
                        📊 Exportar a Excel
                    </button>

                </div>

                <!-- Tabla  -->
                <div class="table-container border border-gray-300 rounded-2xl bg-white overflow-hidden">

                    <!-- padding interno para DataTables -->
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table id="tablaResultados" class="data-table w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800">Cod. Envío</th>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800">Pos. Solicitud</th>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800">Cod. Ref</th>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800">Fecha Toma</th>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800">N° Muestras</th>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800">Tipo Muestra</th>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800">Muestra</th>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800">Tipo Análisis</th>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800">Análisis</th>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800">Observaciones</th>
                                        <!-- NUEVAS COLUMNAS -->
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800 text-center">Detalle</th>
                                        <th class="px-6 py-4 text-sm font-semibold text-gray-800 text-center">Historial</th>
                                    </tr>
                                </thead>


                                <tbody class="divide-y divide-gray-200">
                                    <?php
                                    $query = "
                                            SELECT 
                                                d.codEnvio, d.codRef, d.fecToma, d.numMuestras,
                                                d.codMuestra, d.nomMuestra, d.codAnalisis, d.nomAnalisis,
                                                d.obs, d.id, d.posSolicitud,
                                                tm.nombre AS tipo_muestra_real,
                                                a.nombre AS analisis_real
                                            FROM san_fact_solicitud_det d
                                            LEFT JOIN san_dim_tipo_muestra tm ON d.codMuestra = tm.codigo
                                            LEFT JOIN san_dim_analisis a ON d.codAnalisis = a.codigo
                                            ORDER BY d.codEnvio DESC
                                            ";

                                    $result = mysqli_query($conexion, $query);

                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {

                                            echo '<tr class="hover:bg-gray-50 transition">';

                                            echo '<td class="px-6 py-3 text-gray-700">' . htmlspecialchars($row['codEnvio']) . '</td>';
                                            echo '<td class="px-6 py-3 text-gray-700">' . htmlspecialchars($row['posSolicitud']) . '</td>';
                                            echo '<td class="px-6 py-3 text-gray-700">' . htmlspecialchars($row['codRef']) . '</td>';
                                            echo '<td class="px-6 py-3 text-gray-700">' . htmlspecialchars($row['fecToma']) . '</td>';
                                            echo '<td class="px-6 py-3 text-gray-700 text-center">' . htmlspecialchars($row['numMuestras']) . '</td>';
                                            echo '<td class="px-6 py-3 text-gray-700">' . htmlspecialchars($row['tipo_muestra_real']) . '</td>';
                                            echo '<td class="px-6 py-3 text-gray-700">' . htmlspecialchars($row['nomMuestra']) . '</td>';
                                            echo '<td class="px-6 py-3 text-gray-700">' . htmlspecialchars($row['analisis_real']) . '</td>';
                                            echo '<td class="px-6 py-3 text-gray-700">' . htmlspecialchars($row['nomAnalisis']) . '</td>';
                                            echo '<td class="px-6 py-3 text-gray-700">' . htmlspecialchars($row['obs']) . '</td>';

                                            // ================= BOTÓN DETALLE =================
                                            echo '<td class="px-6 py-3 text-center">';
                                            echo '<button 
                                                    class="text-blue-600 hover:text-blue-800 transition"
                                                    title="Ver detalle"
                                                    onclick="verDetalle(\'' . $row['codEnvio'] . '\')">
                                                    <i class="fa-solid fa-eye text-lg"></i>
                                                </button>';

                                            // ================= BOTÓN HISTORIAL =================
                                            echo '<td class="px-6 py-3 text-center">';
                                            echo '<button 
                                                    class="text-amber-600 hover:text-amber-800 transition"
                                                    title="Ver historial"
                                                    onclick="verHistorial(\'' . $row['codEnvio'] . '\')">
                                                    <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                                                </button>';
                                            echo '</td>';

                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr>';
                                        echo '<td colspan="10" class="px-6 py-8 text-center text-gray-500">
                                                    No hay resultados cualitativos registrados
                                                </td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Detalle -->
        <div id="modalDetalle" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="w-full mx-auto bg-white rounded-lg shadow-2xl flex flex-col" style="width: 80vw; max-width: 1200px; height: 90vh;">

                <!-- Header del Modal -->
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800">Detalle de Envío</h2>
                    <button onclick="cerrarModalDetalle()" class="text-gray-500 hover:text-gray-800 text-2xl leading-none">
                        ×
                    </button>
                </div>

                <!-- Tabs Navigation -->
                <div class="flex border-b border-gray-200 px-6 bg-gray-50">
                    <button onclick="cambiarTab(1)" class="tab-btn tab-active px-4 py-3 font-semibold text-gray-700 border-b-2 border-blue-600 hover:text-blue-600">
                        Detalle
                    </button>
                    <button onclick="cambiarTab(2)" class="tab-btn px-4 py-3 font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700">
                        Resultado Cualitativo
                    </button>
                    <button onclick="cambiarTab(3)" class="tab-btn px-4 py-3 font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700">
                        Resultado Cuantitativo
                    </button>
                </div>

                <!-- Tab Contents -->
                <div class="flex-1 overflow-hidden">

                    <!-- Tab 1 - Detalle del Envío -->
                    <div id="tab-1" class="tab-content h-full flex flex-col">
                        <div class="overflow-y-auto flex-1">
                            <table class="w-full border-collapse text-sm">
                                <thead class="sticky top-0 bg-gray-100 border-b border-gray-300">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Código</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Pos</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Referencia</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Fecha Toma</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">N° Muestras</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Muestra</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Análisis</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Estado Cuali</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Estado Cuanti</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody id="detalleBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-gray-500">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2 - Resultados de Análisis -->
                    <div id="tab-2" class="tab-content hidden h-full flex flex-col">
                        <div class="overflow-y-auto flex-1">
                            <table class="w-full border-collapse text-sm">
                                <thead class="sticky top-0 bg-gray-100 border-b border-gray-300">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Código Envío</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Pos Solicitud</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Cod Ref</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Fecha Toma</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Análisis</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Resultado</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Usuario Registrador</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Fecha Lab Registro</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody id="resultadosBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-gray-500">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3 - Información General -->
                    <div id="tab-3" class="tab-content hidden h-full flex flex-col">
                        <div class="overflow-y-auto flex-1">
                            <table class="w-full border-collapse text-sm">
                                <thead class="sticky top-0 bg-gray-100 border-b border-gray-300">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Campo</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Valor</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Tipo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-4 py-3 font-semibold">Empresa</td>
                                        <td class="px-4 py-3">ABC Laboratorios</td>
                                        <td class="px-4 py-3"><span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Texto</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 font-semibold">Teléfono</td>
                                        <td class="px-4 py-3">+51 999 999 999</td>
                                        <td class="px-4 py-3"><span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Texto</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 font-semibold">Email</td>
                                        <td class="px-4 py-3">contacto@abclabs.com</td>
                                        <td class="px-4 py-3"><span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Texto</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 font-semibold">Dirección</td>
                                        <td class="px-4 py-3">Calle Principal 123, Lima</td>
                                        <td class="px-4 py-3"><span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Texto</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 font-semibold">Fecha Registro</td>
                                        <td class="px-4 py-3">2024-12-15</td>
                                        <td class="px-4 py-3"><span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">Fecha</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-3 font-semibold">Muestras Totales</td>
                                        <td class="px-4 py-3">45</td>
                                        <td class="px-4 py-3"><span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Número</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Footer del Modal -->
                <div class="border-t border-gray-200 px-6 py-4 flex justify-end gap-3 bg-gray-50">
                    <button onclick="cerrarModalDetalle()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded transition">
                        Cerrar
                    </button>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition">
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Tracking -->
        <div id="modalTracking" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="w-full mx-auto bg-white rounded-lg shadow-2xl flex flex-col" style="width: 80vw; max-width: 1200px; height: 90vh;">

                <!-- Header -->
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-clock-rotate-left text-amber-600 mr-2"></i>
                        Historial de Seguimiento
                    </h2>
                    <button onclick="cerrarModalTracking()" class="text-gray-500 hover:text-gray-800 text-2xl leading-none">
                        ×
                    </button>
                </div>

                <!-- Progreso General -->
                <div id="resumenTracking" class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Progreso General del Envío</p>
                            <p class="text-2xl font-bold text-gray-800" id="codEnvioTracking"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-4xl font-bold text-blue-600" id="porcentajeComplecion">0%</p>
                            <p class="text-sm text-gray-600">Completado</p>
                        </div>
                    </div>
                    <div class="w-full bg-gray-300 rounded-full h-3">
                        <div id="barraProgreso" class="bg-gradient-to-r from-green-400 to-blue-500 h-3 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="flex-1 overflow-y-auto px-6 py-8">
                    <div id="timelineContainer" class="space-y-8">
                        <!-- Los eventos se cargarán aquí -->
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex justify-end">
                    <button onclick="cerrarModalTracking()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded transition">
                        Cerrar
                    </button>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>


    <script src="empresas_transporte.js"></script>

    <script>
        $(document).ready(function() {
            $('#tablaResultados').DataTable({
                pageLength: 10,
                lengthChange: true,
                lengthMenu: [10, 20, 50, 100],
                ordering: false,
                searching: true,
                info: true,
                autoWidth: false,

                language: {
                    lengthMenu: "Mostrar _MENU_ filas",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron resultados",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "No hay registros disponibles",
                    paginate: {
                        previous: "Anterior",
                        next: "Siguiente"
                    }
                },

                dom: `
            <"flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4"
                <"flex items-center gap-4"
                    <"text-sm text-gray-600" l>
                    <"text-sm text-gray-600" i>
                >
                <"flex items-center gap-2" f>
            >
            rt
            <"flex flex-col md:flex-row md:items-center md:justify-between gap-4 mt-6"
                <"text-sm text-gray-600" p>
            >
        `
            });
        });

        let codEnvioActual = null;

        function cargarResultados() {
            if (!codEnvioActual) return;

            document.getElementById('resultadosBody').innerHTML =
                '<tr><td colspan="9" class="text-center py-4 text-gray-500">Cargando...</td></tr>';

            fetch('dashboard-seguimiento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'accion=cargar_resultados&codEnvio=' + encodeURIComponent(codEnvioActual)
                })
                .then(r => r.text())
                .then(html => {
                    document.getElementById('resultadosBody').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('resultadosBody').innerHTML =
                        '<tr><td colspan="9" class="text-center py-4 text-red-500">Error al cargar los datos</td></tr>';
                    console.error('Error:', error);
                });
        }

        function verDetalle(codEnvio) {
            codEnvioActual = codEnvio;
            document.getElementById('detalleBody').innerHTML =
                '<tr><td colspan="10" class="text-center py-4 text-gray-500">Cargando...</td></tr>';

            // Cambiar esta ruta a tu PHP real
            fetch('dashboard-seguimiento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'accion=cargar_detalle&codEnvio=' + encodeURIComponent(codEnvio)
                })
                .then(r => r.text())
                .then(html => {
                    document.getElementById('detalleBody').innerHTML = html;
                    document.getElementById('modalDetalle').classList.remove('hidden');
                    document.getElementById('modalDetalle').classList.add('flex');
                    cambiarTab(1);
                })
                .catch(error => {
                    document.getElementById('detalleBody').innerHTML =
                        '<tr><td colspan="10" class="text-center py-4 text-red-500">Error al cargar los datos</td></tr>';
                    console.error('Error:', error);
                });
        }

        function cerrarModalDetalle() {
            document.getElementById('modalDetalle').classList.add('hidden');
            document.getElementById('modalDetalle').classList.remove('flex');
        }

        function cambiarTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('tab-active', 'border-blue-600', 'text-blue-600');
                el.classList.add('border-transparent', 'text-gray-500');
            });

            document.getElementById('tab-' + tab).classList.remove('hidden');
            document.querySelectorAll('.tab-btn')[tab - 1].classList.remove('border-transparent', 'text-gray-500');
            document.querySelectorAll('.tab-btn')[tab - 1].classList.add('tab-active', 'border-blue-600', 'text-blue-600');

            // Cargar datos del Tab 2 si se hace clic
            if (tab === 2) {
                cargarResultados();
            }
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalDetalle').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalDetalle();
            }
        });
    </script>

    <script>
        function verHistorial(codEnvio) {
            document.getElementById('timelineContainer').innerHTML = '<div class="text-center py-8"><p class="text-gray-500">Cargando historial...</p></div>';
            document.getElementById('modalTracking').classList.remove('hidden');
            document.getElementById('modalTracking').classList.add('flex');

            fetch('seguimiento_tracking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'accion=cargar_tracking&codEnvio=' + encodeURIComponent(codEnvio)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        renderizarTimeline(data.timeline, data.resumen);
                    } else {
                        document.getElementById('timelineContainer').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error al cargar el historial</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('timelineContainer').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error al cargar los datos</p></div>';
                });
        }

        function renderizarTimeline(timeline, resumen) {
            const container = document.getElementById('timelineContainer');
            container.innerHTML = '';

            // Actualizar resumen
            document.getElementById('codEnvioTracking').textContent = resumen.codEnvio;
            document.getElementById('porcentajeComplecion').textContent = resumen.porcentajeComplecion + '%';
            document.getElementById('barraProgreso').style.width = resumen.porcentajeComplecion + '%';

            // Renderizar timeline
            timeline.forEach((evento, index) => {
                const isCompleted = evento.estado === 'completado';
                const iconClass = getIcono(evento.paso);
                const colorClase = isCompleted ? 'bg-green-100 border-green-300' : 'bg-yellow-100 border-yellow-300';
                const colorTexto = isCompleted ? 'text-green-700' : 'text-yellow-700';
                const colorIcono = isCompleted ? 'bg-green-500' : 'bg-yellow-500';

                let html = `
                    <div class="relative">
                        <div class="flex gap-6">
                            <!-- Línea vertical -->
                            <div class="flex flex-col items-center">
                                <div class="${colorIcono} rounded-full w-12 h-12 flex items-center justify-center text-white shadow-lg">
                                    <i class="fas ${iconClass} text-lg"></i>
                                </div>
                                ${index < timeline.length - 1 ? '<div class="w-1 h-20 bg-gray-300 my-2"></div>' : ''}
                            </div>

                            <!-- Contenido -->
                            <div class="flex-1 pt-2 mb-4">
                                <div class="p-4 ${colorClase} border border-opacity-30 rounded-lg">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="font-bold text-gray-800 text-lg">${evento.titulo}</h3>
                                            <p class="text-sm text-gray-600 mt-1">${evento.descripcion}</p>
                                        </div>
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${isCompleted ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800'}">
                                            ${isCompleted ? 'Completado' : 'Pendiente'}
                                        </span>
                                    </div>

                                    <!-- Detalles -->
                                    <div class="grid grid-cols-2 gap-3 mt-3 text-sm border-t border-opacity-20 border-gray-400 pt-3">
                `;

                // Agregar detalles
                for (const [clave, valor] of Object.entries(evento.detalles)) {
                    html += `
                        <div>
                            <p class="text-gray-600 font-semibold">${clave}</p>
                            <p class="text-gray-800">${valor}</p>
                        </div>
                    `;
                }

                html += `
                                    </div>

                                    <!-- Meta información -->
                                    <div class="flex justify-between items-center mt-3 text-xs text-gray-600 border-t border-opacity-20 border-gray-400 pt-3">
                                        <div>
                                            <i class="fas fa-user-circle mr-1"></i>
                                            <strong>${evento.usuario}</strong>
                                        </div>
                                        <div>
                                            <i class="fas fa-calendar mr-1"></i>
                                            ${evento.fecha ? new Date(evento.fecha).toLocaleString('es-PE') : 'Sin fecha'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                container.innerHTML += html;
            });
        }

        function getIcono(paso) {
            const iconos = {
                1: 'fa-file-invoice',
                2: 'fa-flask',
                3: 'fa-microscope',
                4: 'fa-chart-bar',
                5: 'fa-check-circle'
            };
            return iconos[paso] || 'fa-circle';
        }

        function cerrarModalTracking() {
            document.getElementById('modalTracking').classList.add('hidden');
            document.getElementById('modalTracking').classList.remove('flex');
        }

        // Cerrar al hacer clic fuera
        document.getElementById('modalTracking').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalTracking();
            }
        });
    </script>

    <script>
        function exportarReporteExcel() {
            window.location.href = "";
        }
    </script>



</body>

</html>