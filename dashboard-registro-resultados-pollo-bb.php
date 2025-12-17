<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}
include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión.");
}

// Obtener lista de enfermedades ÚNICAS desde la base de datos para BB
$enfermedadesQuery = "SELECT DISTINCT enfermedad FROM san_analisis_pollo_bb_adulto 
                      WHERE tipo_ave = 'BB' 
                      AND enfermedad IS NOT NULL AND enfermedad != '' 
                      ORDER BY enfermedad ASC";
$enfermedadesResult = mysqli_query($conexion, $enfermedadesQuery);

// Obtener TODOS los registros (DataTables hará la paginación)
$detallesQuery = "
    SELECT * 
    FROM san_analisis_pollo_bb_adulto 
    WHERE tipo_ave = 'BB'
    ORDER BY fecha_toma_muestra DESC, codigo_envio DESC
";
$detalles = mysqli_query($conexion, $detallesQuery);

// Mapeo de nombres técnicos a nombres amigables
$columnLabels = [
    'codigo_envio' => 'Código SAM',
    'fecha_toma_muestra' => 'Fecha Muestra',
    'edad_aves' => 'Edad (días)',
    'tipo_ave' => 'Tipo Ave',
    'planta_incubacion' => 'Planta Incubación',
    'lote' => 'Lote',
    'codigo_granja' => 'Granja',
    'codigo_campana' => 'Campaña',
    'numero_galpon' => 'Galpón',
    'edad_reproductora' => 'Edad Reprod.',
    'condicion' => 'Condición',
    'enfermedad' => 'Enfermedad',
    't00' => '0', 't01' => '1', 't02' => '2', 't03' => '3', 't04' => '4',
    't05' => '5', 't06' => '6', 't07' => '7', 't08' => '8', 't09' => '9',
    't10' => '10', 't11' => '11', 't12' => '12', 't13' => '13', 't14' => '14',
    't15' => '15', 't16' => '16', 't17' => '17', 't18' => '18',
    'count_muestras' => 'Count',
    'gmean' => 'Gmean',
    'desviacion_estandar' => 'SD',
    'cv' => 'CV'
];

// Obtener columnas relevantes (excluir s01-s06 que son de ADULTO)
$columnNames = [];
$metaQuery = mysqli_query($conexion, "SELECT * FROM san_analisis_pollo_bb_adulto LIMIT 0");
if ($metaQuery) {
    $fieldInfo = mysqli_fetch_fields($metaQuery);
    foreach ($fieldInfo as $field) {
        $fieldName = $field->name;
        if (!preg_match('/^s\d{2}$/', $fieldName) && 
            !in_array($fieldName, ['lcs', 'lcc', 'lci', 'coef_variacion', 'std_1', 'std_2', 'id_analisis', 'estado', 'numero_informe', 'fecha_informe', 'usuario_registro', 'fecha_solicitud', 'codigo_enfermedad', 'titulo_promedio'])) {
            $columnNames[] = $fieldName;
        }
    }
}

// Encontrar el índice de las columnas para filtrado
$fechaColumnIndex = array_search('fecha_toma_muestra', $columnNames);
$enfermedadColumnIndex = array_search('enfermedad', $columnNames);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados Cuantitativos - Pollo BB</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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

        <!-- FILTROS -->
        <div class="mt-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">

            <!-- HEADER FILTROS -->
            <div class="flex items-center justify-between">
                
                <!-- LADO IZQUIERDO: Botón toggle + Título -->
                <div class="flex items-center gap-2 cursor-pointer select-none" onclick="toggleFiltros()">
                    <button id="btnToggleFiltros" type="button"
                        class="text-gray-600 text-lg font-bold w-8 h-8 flex items-center justify-center rounded-md hover:bg-gray-100 transition-colors duration-200">
                        ➕
                    </button>
                    <h3 class="text-sm font-semibold text-[#2c3e50]">
                        Filtros de búsqueda
                    </h3>
                </div>

                <!-- LADO DERECHO: Botón Exportar -->
                <a href="exportar-registro-resultados-pollo-bb.php"
                    class="px-4 py-2 text-white font-medium rounded-md text-sm inline-flex items-center gap-2 shadow-sm transition-all hover:shadow-md"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"
                    onmouseover="this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'"
                    onmouseout="this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'">
                    <span>📊</span>
                    <span>Exportar a Excel</span>
                </a>
            </div>

            <!-- CONTENIDO PLEGABLE -->
            <div id="filtrosContent" class="hidden overflow-hidden transition-all duration-300 ease-in-out">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end mt-4">

                    <!-- FECHA INICIO -->
                    <div>
                        <label for="filtroFechaInicio" class="text-xs font-medium text-gray-700 mb-1 block">
                            Fecha Inicio
                        </label>
                        <input type="date" id="filtroFechaInicio"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>

                    <!-- FECHA FIN -->
                    <div>
                        <label for="filtroFechaFin" class="text-xs font-medium text-gray-700 mb-1 block">
                            Fecha Fin
                        </label>
                        <input type="date" id="filtroFechaFin"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>

                    <!-- ENFERMEDADES -->
                    <div>
                        <label for="filtroEnfermedad" class="text-xs font-medium text-gray-700 mb-1 block">
                            Enfermedad
                        </label>
                        <select id="filtroEnfermedad"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white cursor-pointer">
                            <option value="">Todas</option>
                            <?php 
                            if ($enfermedadesResult && mysqli_num_rows($enfermedadesResult) > 0): 
                                while ($row = mysqli_fetch_assoc($enfermedadesResult)):
                                    $enfermedad = htmlspecialchars($row['enfermedad']);
                            ?>
                                <option value="<?= $enfermedad ?>"><?= $enfermedad ?></option>
                            <?php 
                                endwhile;
                            endif; 
                            ?>
                        </select>
                    </div>

                    <!-- BOTÓN FILTRAR -->
                    <div>
                        <button onclick="aplicarFiltros()"
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 active:bg-blue-800 shadow-sm transition-all">
                            Filtrar
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="max-w-full mx-auto mt-6">
            <div class="border border-gray-300 rounded-2xl bg-white overflow-hidden">
                <div class="table-wrapper">
                    <table id="tablaDatos" class="data-table display" style="width:100%">
                        <thead>
                            <tr>
                                <?php if (!empty($columnNames)): ?>
                                    <?php foreach ($columnNames as $col): ?>
                                        <th><?= htmlspecialchars($columnLabels[$col] ?? $col) ?></th>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($detalles) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($detalles)): ?>
                                    <tr>
                                        <?php foreach ($columnNames as $col): ?>
                                            <td>
                                                <?php 
                                                $value = $row[$col] ?? '';
                                                if ($col === 'enfermedad' && $value !== '') {
                                                    $nombreCompleto = strtoupper($value);
                                                    switch(strtoupper($value)) {
                                                        case 'IBV': $nombreCompleto = 'IBV : Inmuno Bronchitis Virus'; break;
                                                        case 'NDV': $nombreCompleto = 'NDV : New Castle Disease Virus'; break;
                                                        case 'REO': $nombreCompleto = 'REO : Reovirus'; break;
                                                        case 'IBD':
                                                        case 'GUMBORO': $nombreCompleto = 'IBD : Gumboro'; break;
                                                        case 'AI': $nombreCompleto = 'AI : Avian Influenza'; break;
                                                        case 'BI': $nombreCompleto = 'BI : Bronquitis Infecciosa'; break;
                                                        case 'ENC': $nombreCompleto = 'ENC : Encefalomielitis'; break;
                                                        case 'CAV': $nombreCompleto = 'CAV : Anemia Viral del Pollo'; break;
                                                        case 'ASPERGILOSIS': $nombreCompleto = 'ASPERGILOSIS'; break;
                                                        case 'COCCIDIA': $nombreCompleto = 'COCCIDIA'; break;
                                                        case '(CL2) CLORO LIBRE': $nombreCompleto = '(CL2) CLORO LIBRE'; break;
                                                    }
                                                    echo htmlspecialchars($nombreCompleto);
                                                } else {
                                                    echo htmlspecialchars($value);
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © 2025
            </p>
        </div>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script>
        // Variable global para la tabla
        let table;
        
        // Índices de columnas desde PHP
        const FECHA_COLUMN = <?= $fechaColumnIndex ?>;
        const ENFERMEDAD_COLUMN = <?= $enfermedadColumnIndex ?>;

        // Función de filtro personalizado para rango de fechas
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                const fechaInicio = $('#filtroFechaInicio').val();
                const fechaFin = $('#filtroFechaFin').val();
                const enfermedad = $('#filtroEnfermedad').val();
                
                // Obtener fecha de la columna (formato: YYYY-MM-DD)
                const fechaColumna = data[FECHA_COLUMN] || '';
                
                // Obtener enfermedad de la columna
                const enfermedadColumna = data[ENFERMEDAD_COLUMN] || '';
                
                // Filtro de fecha
                let pasaFiltroFecha = true;
                if (fechaInicio && fechaFin) {
                    pasaFiltroFecha = fechaColumna >= fechaInicio && fechaColumna <= fechaFin;
                } else if (fechaInicio) {
                    pasaFiltroFecha = fechaColumna >= fechaInicio;
                } else if (fechaFin) {
                    pasaFiltroFecha = fechaColumna <= fechaFin;
                }
                
                // Filtro de enfermedad (buscar en el texto completo)
                let pasaFiltroEnfermedad = true;
                if (enfermedad) {
                    pasaFiltroEnfermedad = enfermedadColumna.toUpperCase().includes(enfermedad.toUpperCase());
                }
                
                return pasaFiltroFecha && pasaFiltroEnfermedad;
            }
        );

        $(document).ready(function() {
            table = $('#tablaDatos').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                responsive: false,
                order: [[FECHA_COLUMN, 'desc']],
                scrollX: true,
                scrollCollapse: true,
                dom: 'lfrtip',
                initComplete: function() {
                    console.log('DataTable Pollo BB cargado correctamente con scroll horizontal');
                }
            });
        });

        // Función para aplicar filtros
        function aplicarFiltros() {
            table.draw();
            console.log('Filtros aplicados - Pollo BB');
        }

        // Función para toggle de filtros
        function toggleFiltros() {
            const content = document.getElementById('filtrosContent');
            const button = document.getElementById('btnToggleFiltros');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                button.textContent = '➖';
                button.setAttribute('aria-expanded', 'true');
            } else {
                content.classList.add('hidden');
                button.textContent = '➕';
                button.setAttribute('aria-expanded', 'false');
            }
        }
    </script>
</body>

</html>
