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

        .tab-btn {
            padding: 12px 4px;
            font-weight: 500;
            color: #6b7280;
            border-bottom: 2px solid transparent;
        }

        .tab-btn:hover {
            color: #2563eb;
        }

        .tab-active {
            color: #2563eb;
            border-bottom: 2px solid #2563eb;
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
                    <h1 class="text-3xl font-bold text-gray-800">Seguimiento</h1>
                </div>

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
                                                    onclick="verHistorial(
                                                        ' . $row['codEnvio'] . '
                                                    )">
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

        <!-- MODAL DETALLE -->
<div id="modalDetalle"
    class="fixed inset-0 bg-black bg-opacity-50 hidden
           flex items-center justify-center z-50">

    <!-- CAJA FIJA -->
    <div class="bg-white w-[90vw] max-w-6xl h-[85vh]
                rounded-xl shadow-xl
                flex flex-col overflow-hidden">

        <!-- HEADER (FIJO) -->
        <div class="px-6 py-4 border-b
                    flex justify-between items-center
                    flex-shrink-0">
            <h2 class="text-lg font-semibold">Detalle de Solicitud</h2>
            <button onclick="cerrarModalDetalle()"
                class="text-xl text-gray-600 hover:text-red-600">
                ✕
            </button>
        </div>

        <!-- TABS (FIJOS) -->
        <div class="px-6 border-b flex-shrink-0">
            <div class="flex gap-6">
                <button class="tab-btn tab-active" onclick="cambiarTab(1)">Detalle</button>
                <button class="tab-btn" onclick="cambiarTab(2)">Resultado Cualitativo</button>
                <button class="tab-btn" onclick="cambiarTab(3)">Resultado Cuantitativo</button>
            </div>
        </div>

        <!-- CONTENEDOR DE TABS (ALTURA CONTROLADA) -->
        <div class="flex-1 min-h-0 overflow-hidden">

            <!-- TAB 1 -->
            <div id="tab-1"
                class="tab-content h-full w-full overflow-auto">

                <table class="border border-gray-200 min-w-max">
                    <thead class="bg-blue-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2">Cod Envio</th>
                            <th class="px-4 py-2">Pos</th>
                            <th class="px-4 py-2">Cod Ref</th>
                            <th class="px-4 py-2">Fecha</th>
                            <th class="px-4 py-2">N° Muestra</th>
                            <th class="px-4 py-2">Muestra</th>
                            <th class="px-4 py-2">Análisis</th>
                            <th class="px-4 py-2">Estado Cuali</th>
                            <th class="px-4 py-2">Estado Cuanti</th>
                        </tr>
                    </thead>
                    <tbody id="detalleBody"></tbody>
                </table>
            </div>

            <!-- TAB 2 -->
            <div id="tab-2"
                class="tab-content hidden h-full w-full overflow-auto">

                <table class="border border-gray-200 min-w-max">
                    <thead class="bg-green-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2">Pos</th>
                            <th class="px-4 py-2">Análisis</th>
                            <th class="px-4 py-2">Resultado</th>
                            <th class="px-4 py-2">Obs</th>
                        </tr>
                    </thead>
                    <tbody id="resultadoCualiBody"></tbody>
                </table>
            </div>

            <!-- TAB 3 -->
            <div id="tab-3"
                class="tab-content hidden h-full w-full overflow-auto">

                <table class="border border-gray-200 min-w-max">
                    <thead class="bg-purple-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2">Pos</th>
                            <th class="px-4 py-2">Análisis</th>
                            <th class="px-4 py-2">Resultado</th>
                        </tr>
                    </thead>
                    <tbody id="resultadoCuantiBody"></tbody>
                </table>
            </div>

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

        function verDetalle(codEnvio) {

            document.getElementById('detalleBody').innerHTML =
                '<tr><td colspan="9" class="text-center py-4">Cargando...</td></tr>';

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
                });
        }



        function cerrarModalDetalle() {
            document.getElementById('modalDetalle').classList.add('hidden');
        }

        function cambiarTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('tab-active'));

            document.getElementById('tab-' + tab).classList.remove('hidden');
            document.querySelectorAll('.tab-btn')[tab - 1].classList.add('tab-active');
        }
    </script>

    <script>
        function exportarReporteExcel() {
            window.location.href = "";
        }
    </script>



</body>

</html>