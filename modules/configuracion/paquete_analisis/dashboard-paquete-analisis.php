<?php

session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) {
            window.top.location.href = "../../../login.php";
        } else {
            window.location.href = "../../../login.php";
        }
    </script>';
    exit();
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión.");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Paquetes de Muestra</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        /* --- Botones --- */
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

        /* --- Inputs --- */
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

        /* --- Badges de tipo de muestra --- */
        .tipo-muestra-badge {
            display: inline-flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 0.75rem;
            min-width: 180px;
            height: 56px;
            justify-content: center;
            align-items: center;
            padding: 0.5rem 0.75rem;
            box-sizing: border-box;
        }

        .tipo-codigo {
            font-size: 0.7rem;
            color: #0369a1;
            font-weight: 600;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .tipo-nombre {
            font-size: 0.8rem;
            color: #0c4a6e;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- Scrollbar personalizada --- */
        .table-wrapper {
            overflow-x: auto;
            width: 100%;
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

        /* --- ESTILO DE DATATABLES --- */

        /* ✅ Cabecera de controles: fondo blanco */
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_length {
            background: white;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            color: #374151;
        }

        .dataTables_wrapper .dataTables_filter label,
        .dataTables_wrapper .dataTables_length label {
            font-weight: 500;
            margin: 0;
            color: #374151;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            background: white;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            background: white;
        }

        /* ✅ Cabecera de la tabla: azul con texto blanco */
        #tablaPaquetes thead th {
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
            color: white !important;
            font-weight: 600;
            padding: 0.75rem 1rem;
        }

        #tablaPaquetes tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        #tablaPaquetes tbody tr:hover {
            background-color: #eff6ff !important;
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




        /* Botón de exportar con estilo uniforme */
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

        #limpiarBuscador {
            font-size: 1.25rem;
            line-height: 1;
            padding: 0 0.25rem;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container-fluid py-4 mx-8">

        <!-- Encabezado con botones y exportar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mx-8">
            <div class="flex items-center gap-4">

                <a class="btn-export" href="exportar_paquetes.php"">
                    📊 Exportar Todos
                </a>
            </div>
            <button type=" button" class="btn-secondary" onclick="openPaqueteMuestraModal('create')">
                    Nuevo Paquete
                    </button>
            </div>

            <!-- Tabla de paquetes -->

            <div class="max-w-full mx-auto mt-6">
                <div class="border border-gray-300 rounded-2xl bg-white overflow-hidden">
                    <div class="table-wrapper">
                        <table id="tablaPaquetes" class="data-table display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre del Paquete</th>
                                    <th>Tipo de Muestra</th>
                                    <!--<th>N° Análisis</th>-->
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "
                        SELECT 
                            p.codigo, 
                            p.nombre, 
                            p.tipoMuestra,
                            tm.nombre as tipo_muestra_nombre,
                            tm.codigo as tipo_muestra_codigo
                           
                        FROM san_dim_paquete p
                        LEFT JOIN san_dim_tipo_muestra tm ON p.tipoMuestra = tm.codigo
                        ORDER BY p.codigo ASC       
                    ";
                                $result = mysqli_query($conexion, $query);
                                if (!$result) {
                                    die("Error en consulta: " . mysqli_error($conexion));
                                }
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $tipoMuestraHtml = '';
                                    if ($row['tipo_muestra_nombre'] && $row['tipo_muestra_codigo']) {
                                        $tipoMuestraHtml = '<div class="tipo-muestra-badge">
                                <div class="tipo-codigo">Código: ' . htmlspecialchars($row['tipo_muestra_codigo']) . '</div>
                                <div class="tipo-nombre">Nombre: ' . htmlspecialchars($row['tipo_muestra_nombre']) . '</div>
                            </div>';
                                    } else {
                                        $tipoMuestraHtml = '<span class="text-gray-400 italic">Sin tipo</span>';
                                    }

                                    // Obtener análisis asociados para el modal de edición
                                    $analisisRes = mysqli_query($conexion, "SELECT analisis FROM san_dim_analisis_paquete WHERE paquete = " . (int) $row['codigo']);
                                    $analisisList = [];
                                    while ($a = mysqli_fetch_assoc($analisisRes)) {
                                        $analisisList[] = $a['analisis'];
                                    }
                                    $analisisJson = json_encode($analisisList);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['codigo']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                                        <td><?= $tipoMuestraHtml ?></td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <button class="btn-secondary text-xs px-3 py-1 flex items-center gap-1"
                                                    onclick='openPaqueteMuestraModal("update", <?= (int) $row["codigo"] ?>, <?= json_encode($row["nombre"]) ?>, <?= json_encode($row["tipoMuestra"]) ?>, <?= $analisisJson ?>)'>
                                                    <i class="fas fa-pencil-alt"></i> Editar
                                                </button>
                                                <button
                                                    class="btn-outline text-xs px-3 py-1 text-red-600 border-red-300 hover:bg-red-50 flex items-center gap-1"
                                                    onclick='confirmDelete(<?= (int) $row["codigo"] ?>, <?= json_encode($row["nombre"]) ?>)'>
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <!-- Modal para Crear/Editar Paquete de Muestra -->
            <div id="paqueteMuestraModal" style="display: none;"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div
                    class="bg-white rounded-2xl shadow-lg w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-200">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <h2 id="paqueteMuestraModalTitle" class="text-xl font-bold text-gray-800">➕ Nuevo Paquete de
                            Muestra
                        </h2>
                        <button onclick="closePaqueteMuestraModal()"
                            class="text-2xl text-gray-500 hover:text-gray-700">×</button>
                    </div>

                    <!-- Cuerpo con scroll -->
                    <div class="flex-1 overflow-y-auto p-6" style="max-height: 60vh;">
                        <!-- FORMULARIO COMPLETO -->
                        <form id="paqueteMuestraForm">
                            <!-- Campos ocultos -->
                            <input type="hidden" id="paqueteMuestraModalAction" name="action" value="create">
                            <input type="hidden" id="paqueteMuestraEditCodigo" name="codigo" value="">

                            <!-- Nombre y Tipo de Muestra en la MISMA FILA (siempre) -->
                            <div style="display: flex; gap: 1.25rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
                                <!-- Nombre del paquete -->
                                <div style="flex: 1; min-width: 250px;">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Paquete <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" id="paqueteMuestraModalNombre" maxlength="100" required
                                        class="form-control text-sm px-3 py-1.5 w-full">
                                </div>

                                <!-- Tipo de muestra -->
                                <div style="flex: 1; min-width: 250px;">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Muestra <span
                                            class="text-red-500">*</span></label>
                                    <select id="paqueteMuestraModalTipoMuestra" required
                                        class="form-control text-sm px-3 py-1.5 w-full">
                                        <option value="">Seleccione...</option>
                                        <?php
                                        $query_tipos = "SELECT codigo, nombre FROM san_dim_tipo_muestra ORDER BY nombre";
                                        $result_tipos = mysqli_query($conexion, $query_tipos);
                                        while ($tipo = mysqli_fetch_assoc($result_tipos)) {
                                            echo '<option value="' . htmlspecialchars($tipo['codigo']) . '">' .
                                                htmlspecialchars($tipo['codigo'] . ' - ' . $tipo['nombre']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Campo de búsqueda compacto -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar análisis</label>
                                <div class="relative" style="width: 280px;">
                                    <input type="text" id="buscadorAnalisis" placeholder="Nombre o código..."
                                        class="w-full px-3 py-1.5 pr-8 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <span id="iconoLimpiar"
                                        style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: none; cursor: pointer; color: #9ca3af; font-weight: bold;"
                                        onclick="limpiarBuscador()">
                                        ×
                                    </span>
                                </div>
                            </div>

                            <!-- Análisis compactos en columnas -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Análisis a incluir <span class="text-red-500">*</span>
                                    <span class="text-xs text-gray-500 ml-1">Seleccione uno o más</span>
                                </label>
                                <div id="analisisCheckboxes"
                                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 max-h-60 overflow-y-auto p-3 border border-gray-200 rounded-xl bg-gray-50">
                                    <p class="text-gray-500 italic col-span-full">Seleccione un tipo de muestra primero
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Pie fijo -->
                    <div class="border-t border-gray-200 p-6 bg-gray-50">
                        <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end">
                            <button type="button" onclick="closePaqueteMuestraModal()"
                                class="btn-outline">Cancelar</button>
                            <button type="submit" form="paqueteMuestraForm" class="btn-primary">💾 Guardar
                                Paquete</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer dinámico -->
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

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="../../../assets/js/configuracion/paquete_analisis.js"></script>

        <script>
            /*$(document).ready(function () {
                var table = $('#tablaPaquetes').DataTable({
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                    order: [[0, 'asc']],
                    processing: true,
                    deferRender: true,
                    scrollY: 400,
                    scrollCollapse: true,
                    initComplete: function () {
                        // Ocultar el spinner o mostrar la tabla completa
                        $('.dataTables_processing').hide();
                    }
                });
            });*/
            $('#tablaPaquetes').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                order: [[0, 'asc']]
            });

            function confirmDelete(codigo, nombre) {
                if (confirm(`¿Eliminar el paquete "${nombre}" y todos sus análisis asociados?`)) {
                    fetch('crud_paquete_analisis.php', {
                        method: 'POST',
                        body: new URLSearchParams({ action: 'delete', codigo: codigo })
                    })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) location.reload();
                            else alert('❌ ' + d.message);
                        });
                }
            }
        </script>
</body>

</html>