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
            <div class="content-header max-w-7xl mx-auto mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-4xl">🗒️</span>
                    <h1 class="text-3xl font-bold text-gray-800">Resultados de laboratorio</h1>
                </div>
                <p class="text-gray-600 text-sm">Administre las empresas de transporte registradas en el sistema</p>
            </div>

            <div class="form-container max-w-7xl mx-auto">
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
                        <table id="tablaResultados" class="data-table w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Cod. Envio</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Pos. Solicitud</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Cod. Ref</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Analisis</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Resultado</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Observaciones</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">User. Registrador</th>
                                    
                                </tr>
                            </thead>

                            <tbody id="" class="divide-y divide-gray-200">
                                <?php
                                $query = "SELECT * FROM san_fact_resultado_analisis ORDER BY codEnvio desc";
                                $result = mysqli_query($conexion, $query);
                                if ($result && mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo '<tr class="hover:bg-gray-50 transition">';
                                        echo '<td class="px-6 py-4 text-gray-700">' . htmlspecialchars($row['codEnvio']) . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700 font-medium">' . htmlspecialchars($row['posSolicitud']);



                                        echo '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700">' . htmlspecialchars($row['codRef']) . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700">' . htmlspecialchars($row['analisis_nombre']) . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700">' . htmlspecialchars($row['resultado']) . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700">' . htmlspecialchars($row['obs']) . '</td>';
                                        echo '<td class="px-6 py-4 text-gray-700">' . htmlspecialchars($row['usuarioRegistrador']) . '</td>';
                                        
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr>';
                                    echo '<td colspan="3" class="px-6 py-8 text-center text-gray-500">No hay empresas de transporte registradas</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal para Crear/Editar Empresa de Transporte -->
        <div id="empTransModal" style="display: none;"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="modalTitle" class="text-xl font-bold text-gray-800">➕ Editar Analisis</h2>
                    <button onclick="closeEmpTransModal()"
                        class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition">
                        ×
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <form id="empTransForm" onsubmit="return saveEmpTrans(event)">
                        <input type="hidden" id="modalAction" value="create">
                        <input type="hidden" id="editCodigo" value="">

                        <!-- Campo Nombre -->
                        <div class="form-field mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="modalNombre" name="nombre" maxlength="255"
                                placeholder="Ingrese el nombre de la empresa de transporte" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">

                        </div>

                        <!-- Botones -->
                        <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end">
                            <button type="button" onclick="closeEmpTransModal()"
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
    </script>

    <script>
        function exportarReporteExcel() {
            window.location.href = "exportar_excel_resultados.php";
        }
    </script>



</body>

</html>