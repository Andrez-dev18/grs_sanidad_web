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
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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

        /* Select2 estilo Tailwind */
        .select2-container .select2-selection--single {
            height: 38px;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            /* gray-300 */
            padding: 4px 8px;
            display: flex;
            align-items: center;
        }

        .select2-selection__rendered {
            font-size: 0.875rem;
            color: #374151;
            /* gray-700 */
        }

        .select2-selection__arrow {
            height: 100%;
        }

        .select2-container--default .select2-selection--single:focus {
            outline: none;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- CARD FILTROS PLEGABLE -->
        <div class="mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

            <!-- HEADER -->
            <button type="button"
                onclick="toggleFiltros()"
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

                <!-- GRID DE FILTROS -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                    <!-- Fecha inicio -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                        <input type="date" id="filtroFechaInicio"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>

                    <!-- Fecha fin -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                        <input type="date" id="filtroFechaFin"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select id="filtroEstado"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Seleccionar</option>
                            <option value="Completado">Completado</option>
                            <option value="Pendiente">Pendiente</option>
                        </select>
                    </div>

                    <!-- Laboratorio -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Laboratorio</label>
                        <select id="filtroLaboratorio"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Seleccionar</option>
                            <?php
                            $sql = "SELECT codigo, nombre FROM san_dim_laboratorio ORDER BY nombre ASC";
                            $res = $conexion->query($sql);

                            if ($res && $res->num_rows > 0) {
                                while ($row = $res->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($row['nombre']) . '">'
                                        . htmlspecialchars($row['nombre']) .
                                        '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Tipo análisis (autocomplete) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de análisis
                        </label>

                        <select id="filtroTipoAnalisis"
                            class="w-full text-sm rounded-lg border border-gray-300">
                        </select>
                    </div>


                    <!-- Tipo muestra -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de muestra</label>
                        <select id="filtroTipoMuestra"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Seleccionar</option>
                            <?php
                            $sql = "SELECT codigo, nombre FROM san_dim_tipo_muestra ORDER BY nombre ASC";
                            $res = $conexion->query($sql);

                            if ($res && $res->num_rows > 0) {
                                while ($row = $res->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($row['nombre']) . '">'
                                        . htmlspecialchars($row['nombre']) .
                                        '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Granja -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Granja</label>
                        <select id="filtroGranja"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Seleccionar</option>
                            <?php
                            $sql = "
                                        SELECT codigo, nombre
                                        FROM ccos
                                        WHERE LENGTH(codigo)=3
                                        AND swac='A'
                                        AND LEFT(codigo,1)='6'
                                        AND codigo NOT IN ('650','668','669','600')
                                        ORDER BY nombre
                                    ";

                            $res = mysqli_query($conexion, $sql);

                            if ($res && mysqli_num_rows($res) > 0) {
                                while ($row = mysqli_fetch_assoc($res)) {
                                    echo '<option value="' . htmlspecialchars($row['codigo']) . '">'
                                        . htmlspecialchars($row['nombre']) .
                                        '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Galpón -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Galpón</label>
                        <select id="filtroGalpon"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Seleccionar</option>
                            <?php
                            for ($i = 1; $i <= 13; $i++) {
                                $valor = str_pad($i, 2, '0', STR_PAD_LEFT); // 01, 02, ...
                                echo "<option value=\"$valor\">$valor</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Edad -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Edad</label>

                        <div class="flex gap-2">
                            <input type="number"
                                id="filtroEdadDesde"
                                placeholder="Desde"
                                min="0"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">

                            <input type="number"
                                id="filtroEdadHasta"
                                placeholder="Hasta"
                                min="0"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>
                    </div>

                </div>

                <!-- ACCIONES -->
                <div class="mt-8 mb-4 flex flex-wrap justify-end gap-4">

                    <button type="button" id="btnFiltrar"
                        class="px-6 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Filtrar
                    </button>

                    <button type="button" id="btnLimpiar"
                        class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200">
                        Limpiar
                    </button>

                    <button type="button"
                        class="px-6 py-2.5 text-white font-medium rounded-lg transition inline-flex items-center gap-2"
                        onclick="exportarReporteExcel()"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);">
                        📊 Exportar a Excel
                    </button>
                </div>

            </div>
        </div>

        <!-- tabla -->
        <div class="max-w-full mx-auto mt-6">
            <div class="border border-gray-300 rounded-2xl bg-white overflow-hidden">
                <div class="table-wrapper">
                    <table id="tablaResultados" class="data-table display" style="width:100%">
                        <thead>
                            <tr>
                                <!-- CABECERA -->
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Cod. Envío</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Fecha Envío</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Hora</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Laboratorio</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Empresa</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Responsable</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Autorizado Por</th>

                                <!-- DETALLE -->
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Pos.</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Cod. Ref</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Fecha Toma</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Muestras</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Muestra</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Análisis (Detalle)</th>

                                <!-- RESULTADO -->
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Fecha Reg.</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Fecha Lab</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Análisis (Resultado)</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Resultado</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Estado</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Observaciones</th>
                                <th class="px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">Usuario</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script src="empresas_transporte.js"></script>

    <script>
        let table;

        function cargarTabla() {
            if (table) {
                table.destroy();
            }

            // Obtener valores de los filtros
            const fechaInicio = $('#filtroFechaInicio').val();
            const fechaFin = $('#filtroFechaFin').val();
            const estado = $('#filtroEstado').val();
            const laboratorio = $('#filtroLaboratorio').val();
            const tipoMuestra = $('#filtroTipoMuestra').val();
            const tipoAnalisis = $('#filtroTipoAnalisis').val();
            const granja = $('#filtroGranja').val();
            const galpon = $('#filtroGalpon').val();
            const edadDesde = $('#filtroEdadDesde').val();
            const edadHasta = $('#filtroEdadHasta').val();

            table = $('#tablaResultados').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                autoWidth: false,
                dom: `
                    <"flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6"
                        <"flex items-center gap-6"
                            <"text-sm text-gray-600" l>
                            <"text-sm text-gray-600" i>
                        >
                        <"flex items-center gap-2" f>
                    >
                    rt
                    <"flex flex-col md:flex-row md:items-center md:justify-between gap-4 mt-6"
                        <"text-sm text-gray-600" p>
                    >
                    `,
                ajax: {
                    url: 'listar_resultradosCualis_filtro.php',
                    type: 'POST',
                    data: function(d) {
                        // Parámetros estándar de DataTables
                        d.fechaInicio = fechaInicio;
                        d.fechaFin = fechaFin;
                        d.estado = estado;
                        d.laboratorio = laboratorio;
                        d.tipoMuestra = tipoMuestra;
                        d.tipoAnalisis = tipoAnalisis;
                        d.granja = granja;
                        d.galpon = galpon;
                        d.edadDesde = edadDesde;
                        d.edadHasta = edadHasta;
                    }
                },
                columns: [{
                        data: 'codEnvio'
                    },
                    {
                        data: 'fecEnvio'
                    },
                    {
                        data: 'horaEnvio'
                    },
                    {
                        data: 'nomLab'
                    },
                    {
                        data: 'nomEmpTrans'
                    },
                    {
                        data: 'usuarioResponsable'
                    },
                    {
                        data: 'autorizadoPor'
                    },
                    {
                        data: 'posSolicitud',
                        className: 'text-center font-medium'
                    },
                    {
                        data: 'codRef'
                    },
                    {
                        data: 'fecToma'
                    },
                    {
                        data: 'numMuestras',
                        className: 'text-center'
                    },
                    {
                        data: 'nomMuestra'
                    },
                    {
                        data: 'nomAnalisis'
                    },
                    {
                        data: 'fechaHoraRegistro'
                    },
                    {
                        data: 'fechaLabRegistro'
                    },
                    {
                        data: 'analisis_nombre'
                    },
                    {
                        data: 'resultado',
                        className: 'font-semibold text-blue-700'
                    },
                    {
                        data: 'estado_cuali',
                        className: 'text-center',
                        render: function(data) {

                            if (data === 'pendiente') {
                                return `
                                    <span class="inline-flex items-center px-3 py-1 rounded-full 
                                                font-semibold
                                                bg-yellow-100 text-yellow-800">
                                        Pendiente
                                    </span>
                                `;
                            }

                            if (data === 'completado') {
                                return `
                                    <span class="inline-flex items-center px-3 py-1 rounded-full 
                                                font-semibold
                                                bg-green-100 text-green-800">
                                        Completado
                                    </span>
                                `;
                            }

                            return data; // fallback por si aparece otro estado
                        }
                    },
                    {
                        data: 'obs'
                    },
                    {
                        data: 'usuarioRegistrador'
                    }
                ],
                columnDefs: [{
                    targets: '_all',
                    className: 'px-6 py-4 text-sm text-gray-700'
                }],
                rowCallback: function(row, data) {
                    $(row).addClass('hover:bg-gray-50 transition');
                },
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                    processing: "Procesando..."
                },
                pageLength: 10,
                lengthMenu: [
                    [10, 15, 20, 25],
                    [10, 15, 20, 25]
                ],
                order: [
                    [0, 'desc']
                ]
            });
        }

        $(document).ready(function() {
            // Inicializar Select2 para Tipo de Análisis
            $('#filtroTipoAnalisis').select2({
                placeholder: 'Seleccionar análisis',
                allowClear: true,
                width: '100%',
                minimumInputLength: 0,
                ajax: {
                    url: 'buscar_analisis.php',
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });

            // Cargar tabla al iniciar
            cargarTabla();

            // BOTÓN FILTRAR
            $('#btnFiltrar').on('click', function() {
                cargarTabla();
            });

            // BOTÓN LIMPIAR
            $('#btnLimpiar').on('click', function() {
                // Limpiar todos los inputs
                $('#filtroFechaInicio').val('');
                $('#filtroFechaFin').val('');
                $('#filtroEstado').val('');
                $('#filtroLaboratorio').val('');
                $('#filtroTipoMuestra').val('');
                $('#filtroGranja').val('');
                $('#filtroGalpon').val('');
                $('#filtroEdadDesde').val('');
                $('#filtroEdadHasta').val('');

                // Limpiar Select2
                $('#filtroTipoAnalisis').val(null).trigger('change');

                // Recargar tabla sin filtros
                cargarTabla();
            });
        });

        // Toggle filtros
        function toggleFiltros() {
            const content = document.getElementById('contenidoFiltros');
            const icon = document.getElementById('iconoFiltros');

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                content.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }

        $('#filtroTipoAnalisis').select2({
            placeholder: 'Seleccionar análisis',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0, // 🔑 CLAVE
            ajax: {
                url: 'buscar_analisis.php',
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || ''
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                }
            }
        });
    </script>

    <script>
        function exportarReporteExcel() {
            window.location.href = "exportar_excel_resultados.php";
        }
    </script>



</body>

</html>