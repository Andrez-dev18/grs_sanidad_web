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
    <title>Dashboard - Análisis</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
      <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

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
    <div class="container mx-auto px-6 py-12">

        <!-- Encabezado con botones y exportar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-4">

                <a class="btn-export" href="exportar_analisis_excel.php"">
                    📊 Exportar Todos
                </a>
            </div>
            <button type=" button" class="btn-secondary" onclick="openAnalisisModal('create')">
                    Nuevo Analisis
                    </button>
            </div>
            <!-- Tabla de análisis -->
            <!--div class="card">-->
          <div class="max-w-full mx-auto mt-6">
            <div class="border border-gray-300 rounded-2xl bg-white overflow-hidden">
                <div class="table-wrapper">
                    <table id="tablaAnalisis" class="data-table display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Enfermedad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT codigo, nombre, enfermedad FROM san_dim_analisis ORDER BY codigo ASC";
                                $result = mysqli_query($conexion, $query);
                                if (!$result) {
                                    die("Error en consulta: " . mysqli_error($conexion));
                                }
                                while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['codigo']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                                        <td><?= htmlspecialchars($row['enfermedad'] ?? '') ?></td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <button class="btn-secondary text-xs px-3 py-1 flex items-center gap-1"
                                                    onclick='openAnalisisModal("update", <?= json_encode($row["codigo"]) ?>, <?= json_encode($row["nombre"]) ?>, <?= json_encode($row["enfermedad"] ?? "") ?>)'>
                                                    <i class="fas fa-pencil-alt"></i> Editar
                                                </button>
                                                <button
                                                    class="btn-outline text-xs px-3 py-1 text-red-600 border-red-300 hover:bg-red-50 flex items-center gap-1"
                                                    onclick='confirmDelete(<?= json_encode($row["codigo"]) ?>, <?= json_encode($row["nombre"]) ?>)'>
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!--/div-->

        <!-- Modal -->
        <div id="analisisModal" style="display: none;"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div
                class="bg-white rounded-2xl shadow-lg w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-200">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="analisisModalTitle" class="text-xl font-bold text-gray-800">➕ Nuevo Análisis</h2>
                    <button onclick="closeAnalisisModal()" class="text-2xl text-gray-500 hover:text-gray-700">×</button>
                </div>
                <div class="flex-1 overflow-y-auto p-6" style="max-height: 60vh;">
                    <form id="analisisForm">
                        <input type="hidden" id="analisisModalAction" name="action" value="create">
                        <input type="hidden" id="analisisEditCodigo" name="codigo" value="">

                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Análisis <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="analisisModalNombre" name="nombre" maxlength="100" required
                                class="form-control">
                        </div>

                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Enfermedad</label>
                            <input type="text" id="analisisModalEnfermedad" name="enfermedad" maxlength="100"
                                class="form-control">
                        </div>
                    </form>
                </div>
                <div class="border-t border-gray-200 p-6 bg-gray-50">
                    <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end">
                        <button type="button" onclick="closeAnalisisModal()" class="btn-outline">Cancelar</button>
                        <button type="submit" form="analisisForm" class="btn-primary">💾 Guardar Análisis</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> -
                © 2025</p>
        </div>


          <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
       
    <script src="../../../assets/js/configuracion/analisis.js"></script>

        <script>
            $(document).ready(function () {
                $('#tablaAnalisis').DataTable({
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                    order: [[0, 'asc']]
                });
            });

            function confirmDelete(codigo, nombre) {
                if (confirm(`¿Eliminar el análisis "${nombre}"?\n\n⚠️ Si está asociado a paquetes, no se podrá eliminar.`)) {
                    fetch('crud_analisis.php', {
                        method: 'POST',
                        body: new URLSearchParams({ action: 'delete', codigo: codigo })
                    })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) {
                                alert('✅ ' + d.message);
                                location.reload();
                            } else {
                                alert('❌ ' + d.message);
                            }
                        });
                }
            }
        </script>
</body>

</html>