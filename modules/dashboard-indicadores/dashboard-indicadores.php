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
    <title>Dashboard - indicadores</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

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
    </style>
</head>

<body class="bg-gray-50">
    <div class="container-fluid py-4 mx-8">

        <!-- CARD FILTROS PLEGABLE -->
        <div class="mb-4 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

            <!-- HEADER -->
            <button type="button" onclick="toggleFiltros()"
                class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition">

                <div class="flex items-center gap-2">
                    <span class="text-lg">🔎</span>
                    <h3 class="text-base font-semibold text-gray-800">
                        Filtros
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

                    <!-- Granja -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Granja(s)</label>

                        <div class="relative">
                            <button type="button" id="dropdownGranjaBtn"
                                class="w-full px-3 py-2 text-sm text-left bg-white border border-gray-300 rounded-lg shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 flex justify-between items-center">
                                <span id="dropdownGranjaText" class="text-gray-500">Seleccionar
                                    granjas...</span>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown con checkboxes -->
                            <div id="dropdownGranjaMenu"
                                class="fixed z-50 mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto hidden">
                                <div class="p-2">
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
                                            echo '
                                                <label class="flex items-center px-3 py-2 hover:bg-gray-50 rounded cursor-pointer">
                                                    <input type="checkbox" 
                                                        name="filtroGranja[]" 
                                                        value="' . htmlspecialchars($row['codigo']) . '" 
                                                        class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                                        <span class="ml-3 text-sm text-gray-700">' . htmlspecialchars($row['nombre']) . '</span>
                                                </label>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
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
                            <input type="number" id="filtroEdadDesde" placeholder="Desde" min="0"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">

                            <input type="number" id="filtroEdadHasta" placeholder="Hasta" min="0"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>
                    </div>

                </div>

                <!-- ACCIONES -->
                <div class="mt-6 flex flex-wrap justify-end gap-4">

                    <button type="button" id="btnAplicarFiltros"
                        class="px-6 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Filtrar
                    </button>

                    <button type="button" id="btnLimpiarFiltros"
                        class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200">
                        Limpiar
                    </button>
                </div>

            </div>
        </div>

        <!-- GRÁFICOS ESTADÍSTICOS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- Gráfico 1: Muestras enviadas por período -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    Muestras enviadas
                </h3>

                <!-- Botones para cambiar período -->
                <div class="flex gap-3 mb-6">
                    <button id="btnAnio" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition">
                        Por año
                    </button>
                    <button id="btnMes" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition">
                        Por mes
                    </button>
                    <button id="btnDia" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition">
                        Por día
                    </button>
                </div>

                <!-- Contenedor del gráfico -->
                <canvas id="graficoMuestras" class="w-full h-80"></canvas>
            </div>

            <!-- Gráfico 2: Enfermedades más repetidas -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    Enfermedades más repetidas
                </h3>

                <!-- Contenedor del gráfico -->
                <canvas id="graficoEnfermedades" class="w-full h-80"></canvas>
            </div>

            <!-- Gráfico 3: Resultados completados (cualitativo vs cuantitativo) -->
            <div class="bg-white rounded-xl shadow-md p-6 mt-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    Resultados completados
                </h3>

                <!-- Botones para cambiar período -->
                <div class="flex gap-3 mb-6">
                    <button id="btnResultDia" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition">
                        Por día
                    </button>
                    <button id="btnResultSemana" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition">
                        Por semana
                    </button>
                    <button id="btnResultMes" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition">
                        Por mes
                    </button>
                </div>

                <!-- Contenedor del gráfico -->
                <canvas id="graficoResultados" class="w-full h-80"></canvas>
            </div>

            <!-- 4. Análisis con más/menos resultados -->
            <div class="bg-white rounded-xl shadow-md p-6 mt-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">
                        Análisis con resultados registrados
                    </h3>

                    <button id="btnToggleAnalisis" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm transition">
                        Mostrar menos frecuentes
                    </button>
                </div>

                <canvas id="graficoAnalisisResultados" class="w-full h-64"></canvas>

                <p class="text-sm text-gray-500 mt-4 text-center" id="descripcionAnalisis">
                    Top 10 análisis con más resultados registrados
                </p>
            </div>

            <div class="col-span-1 lg:col-span-2 mt-10">
                <h2 class="text-2xl font-bold text-gray-800 border-b-2 border-red-500 pb-2 mb-6">
                    <i class="fas fa-notes-medical mr-2"></i>Estadísticas de Necropsias
                </h2>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Impacto por Sistema</h3>
                <p class="text-sm text-gray-500 mb-4">Severidad promedio (%) por sistema.</p>
                <div class="relative h-64">
                    <canvas id="graficoNecropsiaSistemas"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">Detalle por Nivel (Órgano)</h3>
                        <p class="text-sm text-gray-500">Selecciona un nivel para ver sus parámetros específicos.</p>
                    </div>

                    <select id="selectNivelNecropsia" class="border border-gray-300 rounded-lg px-4 py-2 bg-gray-50 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
                        <option value="">Cargando niveles...</option>
                    </select>
                </div>

                <div class="relative h-80">
                    <canvas id="graficoNecropsiaDinamico"></canvas>
                </div>
            </div>
       
            <div class="bg-white rounded-xl shadow-md p-6 col-span-1 lg:col-span-2">
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Top 10 Hallazgos Frecuentes</h3>
                <p class="text-sm text-gray-500 mb-4">Lesiones específicas más reportadas.</p>
                <div class="relative h-80">
                    <canvas id="graficoNecropsiaHallazgos"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 col-span-1 lg:col-span-2">
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Necropsias por Granja</h3>
                <p class="text-sm text-gray-500 mb-4">Volumen de registros realizados.</p>
                <div class="relative h-64 flex justify-center">
                    <canvas id="graficoNecropsiaGranjas"></canvas>
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

    <!-- Librería Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        function toggleFiltros() {
            const contenido = document.getElementById('contenidoFiltros');
            const icono = document.getElementById('iconoFiltros');

            contenido.classList.toggle('hidden');
            icono.classList.toggle('rotate-180');
        }
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownBtn = document.getElementById('dropdownGranjaBtn');
            const dropdownMenu = document.getElementById('dropdownGranjaMenu');
            const dropdownText = document.getElementById('dropdownGranjaText');
            const checkboxes = dropdownMenu.querySelectorAll('input[type="checkbox"]');

            // Abrir/cerrar dropdown al hacer clic en el botón
            dropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('hidden');
            });

            // Cerrar dropdown si se hace clic fuera
            document.addEventListener('click', function() {
                dropdownMenu.classList.add('hidden');
            });

            // Evitar que al hacer clic dentro del dropdown se cierre
            dropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Actualizar texto cuando cambia algún checkbox
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedText);
            });

            // Función para actualizar el texto del botón
            function updateSelectedText() {
                const selectedCheckboxes = dropdownMenu.querySelectorAll('input[type="checkbox"]:checked');
                const selectedLabels = Array.from(selectedCheckboxes).map(cb => {
                    return cb.closest('label').querySelector('span').textContent.trim();
                });

                if (selectedLabels.length === 0) {
                    dropdownText.textContent = 'Seleccionar granjas...';
                    dropdownText.classList.add('text-gray-500');
                    dropdownText.classList.remove('text-gray-900');
                } else {
                    dropdownText.classList.remove('text-gray-500');
                    dropdownText.classList.add('text-gray-900');

                    if (selectedLabels.length <= 3) {
                        dropdownText.textContent = selectedLabels.join(', ');
                    } else {
                        dropdownText.textContent = `${selectedLabels.length} granjas seleccionadas`;
                    }
                }
            }

            // Inicializar texto al cargar la página (por si hay valores preseleccionados)
            updateSelectedText();
        });

        document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);

        function limpiarFiltros() {
            // === NUEVO: Limpiar dropdown de granjas múltiples ===
            const checkboxesGranja = document.querySelectorAll('input[name="filtroGranja[]"]');
            checkboxesGranja.forEach(cb => {
                cb.checked = false;
            });

            // Restaurar texto del botón dropdown
            const dropdownText = document.getElementById('dropdownGranjaText');
            if (dropdownText) {
                dropdownText.textContent = "Seleccionar granjas...";
                dropdownText.classList.add('text-gray-500');
            }

            // Cerrar el dropdown si está abierto
            const dropdownMenu = document.getElementById('dropdownGranjaMenu');
            if (dropdownMenu) {
                dropdownMenu.classList.add('hidden');
            }

            document.getElementById('filtroGalpon').value = '';
            document.getElementById('filtroEdadDesde').value = '';
            document.getElementById('filtroEdadHasta').value = '';

        }
    </script>

    <script>
        // Función para obtener datos de muestras por período
        async function obtenerDatosMuestras(periodo) {
            const response = await fetch('get_muestras.php?periodo=' + periodo);
            return await response.json();
        }

        // Función para obtener datos de enfermedades
        async function obtenerDatosEnfermedades() {
            const response = await fetch('get_enfermedades.php');
            return await response.json();
        }

        // === GRÁFICO MUETRAS ===
        let graficoMuestras = new Chart(document.getElementById('graficoMuestras'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Muestras enviadas',
                    data: [],
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Cargar datos iniciales (por año)
        obtenerDatosMuestras('anio').then(datos => {
            graficoMuestras.data.labels = datos.labels;
            graficoMuestras.data.datasets[0].data = datos.data;
            graficoMuestras.update();
        });

        // Cambiar período
        document.getElementById('btnAnio').addEventListener('click', () => cambiarPeriodo('anio'));
        document.getElementById('btnMes').addEventListener('click', () => cambiarPeriodo('mes'));
        document.getElementById('btnDia').addEventListener('click', () => cambiarPeriodo('dia'));

        function cambiarPeriodo(periodo) {
            obtenerDatosMuestras(periodo).then(datos => {
                graficoMuestras.data.labels = datos.labels;
                graficoMuestras.data.datasets[0].data = datos.data;
                graficoMuestras.update();
            });

            // Actualizar botones activos
            document.querySelectorAll('.flex.gap-3 button').forEach(btn => {
                btn.classList.replace('bg-blue-600', 'bg-gray-300');
                btn.classList.replace('text-white', 'text-gray-700');
            });
            document.getElementById(`btn${periodo.charAt(0).toUpperCase() + periodo.slice(1)}`).classList.replace('bg-gray-300', 'bg-blue-600');
            document.getElementById(`btn${periodo.charAt(0).toUpperCase() + periodo.slice(1)}`).classList.replace('text-gray-700', 'text-white');
        }

        // === GRÁFICO ENFERMEDADES ===
        obtenerDatosEnfermedades().then(datos => {
            new Chart(document.getElementById('graficoEnfermedades'), {
                type: 'bar',
                data: {
                    labels: datos.labels,
                    datasets: [{
                        label: 'Ocurrencias',
                        data: datos.data,
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });


        // === GRÁFICO RESULTADOS COMPLETADOS ===
        async function obtenerDatosResultados(periodo) {
            const response = await fetch(`get_resultados.php?periodo=${periodo}`);
            return await response.json();
        }

        let graficoResultados = new Chart(document.getElementById('graficoResultados'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                        label: 'Cualitativos completados',
                        data: [],
                        backgroundColor: 'rgba(75, 192, 192, 0.8)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Cuantitativos completados',
                        data: [],
                        backgroundColor: 'rgba(153, 102, 255, 0.8)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Cargar datos iniciales (por mes)
        obtenerDatosResultados('mes').then(datos => {
            graficoResultados.data.labels = datos.labels;
            graficoResultados.data.datasets[0].data = datos.cualitativos;
            graficoResultados.data.datasets[1].data = datos.cuantitativos;
            graficoResultados.update();
        });

        // Botones
        document.getElementById('btnResultDia').addEventListener('click', () => cambiarPeriodoResultados('dia'));
        document.getElementById('btnResultSemana').addEventListener('click', () => cambiarPeriodoResultados('semana'));
        document.getElementById('btnResultMes').addEventListener('click', () => cambiarPeriodoResultados('mes'));

        function cambiarPeriodoResultados(periodo) {
            obtenerDatosResultados(periodo).then(datos => {
                graficoResultados.data.labels = datos.labels;
                graficoResultados.data.datasets[0].data = datos.cualitativos;
                graficoResultados.data.datasets[1].data = datos.cuantitativos;
                graficoResultados.update();
            });

            // === CORREGIDO: Botones activos ===
            const cardResultados = document.getElementById('graficoResultados').closest('.p-6');
            cardResultados.querySelectorAll('button').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-300', 'text-gray-700');
            });

            const btnActivo = document.getElementById(`btnResult${periodo.charAt(0).toUpperCase() + periodo.slice(1)}`);
            btnActivo.classList.remove('bg-gray-300', 'text-gray-700');
            btnActivo.classList.add('bg-blue-600', 'text-white');
        }


        // === GRÁFICO 4: ANÁLISIS CON MÁS/MENOS RESULTADOS ===
        let graficoAnalisisResultados = null;
        let modoAnalisis = 'mas'; // 'mas' o 'menos'

        async function cargarGraficoAnalisisResultados(modo = 'mas') {
            const response = await fetch(`get_analisis_resultados.php?modo=${modo}`);
            const datos = await response.json();

            if (graficoAnalisisResultados) graficoAnalisisResultados.destroy();

            graficoAnalisisResultados = new Chart(document.getElementById('graficoAnalisisResultados'), {
                type: 'bar',
                data: {
                    labels: datos.labels,
                    datasets: [{
                        label: 'Resultados registrados',
                        data: datos.data,
                        backgroundColor: modo === 'mas' ? 'rgba(34, 197, 94, 0.8)' : 'rgba(239, 68, 68, 0.8)',
                        borderColor: modo === 'mas' ? 'rgba(34, 197, 94, 1)' : 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y', // barras horizontales
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Resultados: ' + context.parsed.x;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Actualizar descripción y botón
            document.getElementById('descripcionAnalisis').textContent =
                modo === 'mas' ?
                'Top 10 análisis con más resultados registrados' :
                'Top 10 análisis con menos resultados registrados';

            document.getElementById('btnToggleAnalisis').textContent =
                modo === 'mas' ? 'Mostrar menos frecuentes' : 'Mostrar más frecuentes';

            modoAnalisis = modo;
        }

        // Botón toggle
        document.getElementById('btnToggleAnalisis').onclick = () => {
            const nuevoModo = modoAnalisis === 'mas' ? 'menos' : 'mas';
            cargarGraficoAnalisisResultados(nuevoModo);
        };

        // Carga inicial
        cargarGraficoAnalisisResultados('mas');
    </script>

    <script>
        // Función para cargar estadísticas de necropsia
        async function cargarEstadisticasNecropsia() {
            try {
                const response = await fetch('estadisticas_necropsia.php');
                const datos = await response.json();

                // 1. Gráfico de SISTEMAS (Barra Vertical)
                const ctxSistemas = document.getElementById('graficoNecropsiaSistemas').getContext('2d');
                new Chart(ctxSistemas, {
                    type: 'bar',
                    data: {
                        labels: datos.sistemas.labels,
                        datasets: [{
                            label: 'Severidad Promedio (%)',
                            data: datos.sistemas.data,
                            backgroundColor: 'rgba(59, 130, 246, 0.6)', // Azul
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: '% Afectación'
                                }
                            }
                        }
                    }
                });

                // 2. Gráfico de GRANJAS (Doughnut)
                const ctxGranjas = document.getElementById('graficoNecropsiaGranjas').getContext('2d');
                new Chart(ctxGranjas, {
                    type: 'doughnut',
                    data: {
                        labels: datos.granjas.labels,
                        datasets: [{
                            data: datos.granjas.data,
                            backgroundColor: [
                                '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
                                '#EC4899', '#6366F1', '#14B8A6'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });

                // 3. Gráfico de HALLAZGOS (Barra Horizontal)
                const ctxHallazgos = document.getElementById('graficoNecropsiaHallazgos').getContext('2d');
                new Chart(ctxHallazgos, {
                    type: 'bar',
                    data: {
                        labels: datos.hallazgos.labels,
                        datasets: [{
                            label: 'Frecuencia (N° Lotes)',
                            data: datos.hallazgos.data,
                            backgroundColor: 'rgba(239, 68, 68, 0.6)', // Rojo
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        indexAxis: 'y', // Hace que sea horizontal
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // =========================================================
                // 5. GRÁFICO DINÁMICO (SELECT + BARRA)
                // =========================================================
                const selectNivel = document.getElementById('selectNivelNecropsia');
                const ctxDinamico = document.getElementById('graficoNecropsiaDinamico').getContext('2d');
                let chartDinamico = null; // Variable para guardar la instancia del gráfico

                // A. Llenar el Select
                const niveles = Object.keys(datos.dinamico);
                selectNivel.innerHTML = ''; // Limpiar

                if (niveles.length > 0) {
                    niveles.forEach(nivel => {
                        const option = document.createElement('option');
                        option.value = nivel;
                        option.text = nivel;
                        selectNivel.appendChild(option);
                    });

                    // Seleccionar el primero por defecto (ej: INDICE BURSAL)
                    renderizarGraficoDinamico(niveles[0]);
                } else {
                    selectNivel.innerHTML = '<option>Sin datos disponibles</option>';
                }

                // B. Función para dibujar/actualizar el gráfico
                function renderizarGraficoDinamico(nivelSeleccionado) {
                    const info = datos.dinamico[nivelSeleccionado];

                    // Si ya existe, lo destruimos para volver a crear
                    if (chartDinamico) {
                        chartDinamico.destroy();
                    }

                    chartDinamico = new Chart(ctxDinamico, {
                        type: 'bar',
                        data: {
                            labels: info.labels, // Parametros (ej: Normal, Atrofia...)
                            datasets: [{
                                label: `% Promedio en ${nivelSeleccionado}`,
                                data: info.data,
                                backgroundColor: 'rgba(99, 102, 241, 0.6)', // Indigo
                                borderColor: 'rgba(99, 102, 241, 1)',
                                borderWidth: 1,
                                borderRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100
                                }
                            }
                        }
                    });
                }

                // C. Evento Change del Select
                selectNivel.addEventListener('change', (e) => {
                    renderizarGraficoDinamico(e.target.value);
                });

            } catch (error) {
                console.error("Error cargando estadísticas de necropsia:", error);
            }
        }

        // Llamar a la función al cargar la página
        document.addEventListener("DOMContentLoaded", () => {
            // ... tus otros gráficos ...
            cargarEstadisticasNecropsia();
        });
    </script>

</body>

</html>