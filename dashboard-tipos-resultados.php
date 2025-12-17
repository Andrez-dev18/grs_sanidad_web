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

        /* Evitar que DataTables rompa tus estilos Tailwind */
        .dataTables_wrapper table {
            border-collapse: separate !important;
            border-spacing: 0;
        }

        /* Hover en filas */
        .dataTables_wrapper tbody tr:hover {
            background-color: #f9fafb !important;
            transition: background-color 0.2s;
        }

        /* Separación entre filas */
        .dataTables_wrapper tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        /* Buscador y selector de longitud bonitos */
        .dataTables_wrapper input[type="search"],
        .dataTables_wrapper select {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            background-color: white;
        }

        /* Paginación limpia y moderna */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.4rem 0.8rem !important;
            margin: 0 2px;
            border-radius: 0.5rem !important;
            border: 1px solid #d1d5db !important;
            background: white !important;
            color: #374151 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: linear-gradient(to right, #3b82f6, #9333ea) !important;
            color: white !important;
            border-color: transparent !important;
        }

        /* Info de paginación */
        .dataTables_wrapper .dataTables_info {
            font-size: 0.875rem;
            color: #6b7280;
        }

        /* Quitar bordes extras que pone DataTables */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
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
                    <h1 class="text-3xl font-bold text-gray-800">Tipos de resultados</h1>
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
                    <button type="button" id="btnNuevoResultado"
                        class="btn btn-primary px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2">
                        ➕ Nuevo resultado
                    </button>
                </div>

                <!-- Tabla de análisis -->
                <div class="table-container border border-gray-300 rounded-2xl bg-white overflow-x-auto">
                    <table class="data-table w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800">Código</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800">Tipo Resultado</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800">Análisis</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800">Paquete / Muestra</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="analisisTableBody" class="divide-y divide-gray-200">
                            <?php


                            $query = "
                                SELECT
                                    tr.codigo               AS tr_codigo,
                                    tr.tipo                 AS tr_tipo,

                                    a.codigo                AS analisis_codigo,
                                    a.nombre                AS analisis_nombre,

                                    p.codigo                AS paquete_codigo,
                                    p.nombre                AS paquete_nombre,

                                    tm.codigo               AS muestra_codigo,
                                    tm.nombre               AS muestra_nombre

                                FROM san_dim_tiporesultado tr
                                LEFT JOIN san_dim_analisis a 
                                    ON tr.analisis = a.codigo
                                LEFT JOIN san_dim_paquete p 
                                    ON a.paquete = p.codigo
                                LEFT JOIN san_dim_tipo_muestra tm 
                                    ON p.tipoMuestra = tm.codigo

                                ORDER BY tr.codigo ASC
                            ";

                            $result = mysqli_query($conexion, $query);

                            $result = mysqli_query($conexion, $query);

                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {

                                    // Paquete + muestra
                                    if ($row['paquete_codigo']) {
                                        $paqueteHtml = "
                                            <div class='space-y-1 text-sm'>
                                                <div>
                                                    <span class='font-medium'>Paquete:</span>
                                                    {$row['paquete_codigo']} - {$row['paquete_nombre']}
                                                </div>
                                                <div>
                                                    <span class='font-medium'>Muestra:</span>
                                                    {$row['muestra_codigo']} - {$row['muestra_nombre']}
                                                </div>
                                            </div>
                                        ";
                                    } else {
                                        $paqueteHtml = "<span class='text-gray-400 italic'>Sin paquete</span>";
                                    }

                                    echo "<tr class='hover:bg-gray-50 transition'>";

                                    echo "<td class='px-6 py-4'>
                                            <span class='inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800'>
                                                {$row['tr_codigo']}
                                            </span>
                                        </td>";

                                    echo "<td class='px-6 py-4 font-medium text-gray-700'>
                                            {$row['tr_tipo']}
                                        </td>";

                                    echo "<td class='px-6 py-4 text-gray-700'>
                                            {$row['analisis_nombre']}
                                        </td>";

                                    echo "<td class='px-6 py-4'>
                                            $paqueteHtml
                                        </td>";

                                    echo "<td class='px-6 py-4 text-center flex justify-center gap-2'>
                                            <button
                                                class='p-2 rounded-lg hover:bg-blue-100 transition'
                                                title='Editar'
                                                onclick=\"editarTipoResultado({$row['tr_codigo']})\">
                                                ✏️
                                            </button>
                                            <button
                                                class='p-2 rounded-lg hover:bg-red-100 transition'
                                                title='Eliminar'
                                                onclick=\"eliminarTipoResultado({$row['tr_codigo']})\">
                                                🗑️
                                            </button>
                                        </td>";

                                    echo "</tr>";
                                }
                            } else {
                                echo "
                                    <tr>
                                        <td colspan='5' class='px-6 py-8 text-center text-gray-500'>
                                            No hay tipos de resultado registrados
                                        </td>
                                    </tr>
                                ";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal - Versión LIMPIA, PROFESIONAL y BONITA (inspirada en diseños modernos Tailwind) -->
        <div id="modalNuevoResultado" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center px-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-8 relative">

                <!-- Botón cerrar - FORZADO a la derecha, imposible que se vaya a la izquierda -->
                <button type="button" id="cerrarModal"
                    class="absolute top-6 right-6 left-auto text-gray-400 hover:text-gray-700 text-2xl transition duration-150"
                    style="left: auto; right: 1.5rem;">
                    &times;
                </button>

                <!-- Título grande y centrado -->
                <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">
                    Nuevo Tipo de Resultado
                </h3>

                <form id="formNuevoResultado" class="space-y-6">
                    <!-- Análisis -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Análisis
                        </label>
                        <select name="analisis" id="selectAnalisis" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Seleccione un análisis...</option>
                            <?php
                            // Tu PHP para cargar análisis (igual que antes)
                            $queryAnalisis = "
                        SELECT 
                            a.codigo AS analisis_codigo,
                            a.nombre AS analisis_nombre,
                            p.codigo AS paquete_codigo,
                            p.nombre AS paquete_nombre,
                            tm.nombre AS muestra_nombre
                        FROM san_dim_analisis a
                        LEFT JOIN san_dim_paquete p ON a.paquete = p.codigo
                        LEFT JOIN san_dim_tipo_muestra tm ON p.tipoMuestra = tm.codigo
                        ORDER BY a.nombre ASC
                    ";

                            $resultAnalisis = mysqli_query($conexion, $queryAnalisis);

                            while ($row = mysqli_fetch_assoc($resultAnalisis)) {
                                $texto = $row['analisis_nombre'] . " (Paquete: " .
                                    ($row['paquete_codigo'] ? $row['paquete_codigo'] . " - " . $row['paquete_nombre'] : "Sin paquete") .
                                    " | Muestra: " . ($row['muestra_nombre'] ?? "N/A") . ")";

                                echo "<option value='{$row['analisis_codigo']}'>" . htmlspecialchars($texto) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Tipo de Resultado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Resultado
                        </label>
                        <input type="text" name="tipo" required placeholder="Ej: NEGATIVO, POSITIVO, NUMÉRICO..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition uppercase"
                            autocomplete="off">
                    </div>

                    <!-- Botones grandes y bonitos, alineados a la derecha -->
                    <div class="flex justify-end gap-4 pt-4">
                        <button type="button" id="cancelarModal"
                            class="px-6 py-3 text-base font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-6 py-3 text-base font-medium text-white bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 rounded-lg transition shadow-md">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> -
                © 2025</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        const modal = document.getElementById('modalNuevoResultado');
        const tbody = document.getElementById('analisisTableBody');
        const form = document.getElementById('formNuevoResultado');

        // Abrir y cerrar modal (ya lo tienes, solo asegúrate de tener esto)
        document.getElementById('btnNuevoResultado').addEventListener('click', () => {
            modal.classList.remove('hidden');
        });

        document.getElementById('cerrarModal').addEventListener('click', () => cerrarModal());
        document.getElementById('cancelarModal').addEventListener('click', () => cerrarModal());

        function cerrarModal() {
            modal.classList.add('hidden');
            form.reset();
        }

        // === AQUÍ ESTÁ LA MAGIA: Guardar y actualizar tabla sin recargar ===
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);

            fetch('guardar_tipo_resultado.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const row = data.nuevo;

                        // Construir el HTML del paquete/muestra
                        let paqueteHtml = row.paquete_codigo ?
                            `<div class='space-y-1 text-sm'>
                           <div><span class='font-medium'>Paquete:</span> ${row.paquete_codigo} - ${row.paquete_nombre}</div>
                           <div><span class='font-medium'>Muestra:</span> ${row.muestra_codigo} - ${row.muestra_nombre}</div>
                       </div>` :
                            `<span class='text-gray-400 italic'>Sin paquete</span>`;

                        // Crear la nueva fila
                        const nuevaFila = `
                    <tr class='hover:bg-gray-50 transition'>
                        <td class='px-6 py-4'>
                            <span class='inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800'>
                                ${row.tr_codigo}
                            </span>
                        </td>
                        <td class='px-6 py-4 font-medium text-gray-700'>${row.tr_tipo}</td>
                        <td class='px-6 py-4 text-gray-700'>${row.analisis_nombre}</td>
                        <td class='px-6 py-4'>${paqueteHtml}</td>
                        <td class='px-6 py-4 text-center flex justify-center gap-2'>
                            <button class='p-2 rounded-lg hover:bg-blue-100 transition' title='Editar' onclick="editarTipoResultado(${row.tr_codigo})">✏️</button>
                            <button class='p-2 rounded-lg hover:bg-red-100 transition' title='Eliminar' onclick="eliminarTipoResultado(${row.tr_codigo})">🗑️</button>
                        </td>
                    </tr>
                `;

                        // Si no hay filas, eliminar el mensaje "No hay tipos..."
                        if (tbody.innerHTML.includes('No hay tipos de resultado registrados')) {
                            tbody.innerHTML = '';
                        }

                        // Insertar la nueva fila al principio (o al final si prefieres: tbody.insertAdjacentHTML('beforeend', nuevaFila);)
                        tbody.insertAdjacentHTML('afterbegin', nuevaFila);

                        alert('¡Tipo de resultado agregado correctamente!');
                        cerrarModal();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error(error);
                    alert('Error de conexión');
                });
        });
    </script>


</body>

</html>