<?php

session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

//ruta relativa a la conexion
include_once '../../../../conexion_grs_joya/conexion.php';
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
    <title>Dashboard - tracking</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
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
    <div class="container mx-auto px-6 py-12">

        <!-- GRÁFICOS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- 3. Top 10 Análisis más solicitados -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-6 text-center">
                    Top 10 Análisis más solicitados
                </h3>

                <canvas id="graficoTopAnalisis" class="w-full h-60"></canvas>
            </div>

            <!-- 4. Tiempo promedio de demora por etapa -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 text-center">
                    Tiempo promedio de demora por etapa
                </h3>

                <div class="flex gap-3 mb-6 justify-center">
                    <button id="btnDemoraHoras" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition">
                        En horas
                    </button>
                    <button id="btnDemoraDias" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition">
                        En días
                    </button>
                </div>

                <canvas id="graficoDemoras" class="w-full h-60"></canvas>

                <p class="text-sm text-gray-500 mt-4 text-center">
                    Basado en envíos completados. Demora calculada entre registros consecutivos.
                </p>
            </div>
            <!-- 1. Envíos realizados por período -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    Envíos realizados
                </h3>

                <div class="flex flex-wrap gap-3 mb-6">
                    <button id="btnEnvioDia" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition">
                        Por día
                    </button>
                    <button id="btnEnvioSemana" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition">
                        Por semana
                    </button>
                    <button id="btnEnvioMes" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition">
                        Por mes
                    </button>
                </div>

                <canvas id="graficoEnvios" class="w-full h-60"></canvas> <!-- altura mediana -->
            </div>

            <!-- 2. Estado general de envíos -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    Estado general de envíos
                </h3>

                <div class="flex justify-center">
                    <canvas id="graficoEstado" class="w-full max-w-xs h-60"></canvas> <!-- más compacto -->
                </div>

                <div class="mt-6 text-center space-y-2">
                    <p class="text-lg"><span class="font-bold text-orange-600" id="totalPendientes">0</span> envíos pendientes</p>
                    <p class="text-lg"><span class="font-bold text-green-600" id="totalCompletados">0</span> envíos completados</p>
                    <p class="text-sm text-gray-500 mt-4">Un envío es completado cuando pasa por GRS → Transporte → Laboratorio</p>
                </div>
            </div>


        </div>


        <!-- CALENDARIO VISUAL -->
        <div class="bg-white rounded-xl shadow-md p-6 mt-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">
                Calendario de Ubicaciones de Envíos
            </h3>

            <p class="text-sm text-gray-500 mb-6">
                Representa las fechas de registro por ubicación (GRS, Transporte, Laboratorio). Colores: Azul (GRS), Naranja (Transporte), Verde (Laboratorio).
            </p>
            <!-- BUSCADOR POR CÓDIGO DE ENVÍO -->
            <div class="mb-6 flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label for="buscadorCodEnvio" class="block text-sm font-medium text-gray-700 mb-1">
                        Buscar por Código de Envío
                    </label>
                    <input
                        type="text"
                        id="buscadorCodEnvio"
                        placeholder="Ej: san-025-0065"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                </div>
                <div>
                    <button
                        id="btnBuscar"
                        class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-md">
                        Buscar
                    </button>
            <div id="calendarioTracking" class="w-full"></div>          <button
                        id="btnLimpiar"
                        class="ml-3 px-6 py-2 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 transition shadow-md">
                        Limpiar
                    </button>
                </div>
            </div>
            <!-- Contenedor del calendario -->
          
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

    <!-- Librería Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Librería FullCalendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>



    <script>
        // === GRÁFICO ENVÍOS REALIZADOS ===
        let graficoEnvios = null;

        async function cargarGraficoEnvios(periodo = 'mes') {
            const response = await fetch(`get_envios_tracking.php?periodo=${periodo}`);
            const datos = await response.json();

            if (graficoEnvios) graficoEnvios.destroy();

            graficoEnvios = new Chart(document.getElementById('graficoEnvios'), {
                type: 'bar',
                data: {
                    labels: datos.labels,
                    datasets: [{
                        label: 'Envíos realizados',
                        data: datos.data,
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgba(59, 130, 246, 1)',
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

            // === ACTUALIZAR BOTONES VISUALMENTE ===
            // Primero: poner todos en gris
            document.getElementById('btnEnvioDia').className = 'px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition';
            document.getElementById('btnEnvioSemana').className = 'px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition';
            document.getElementById('btnEnvioMes').className = 'px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition';

            // Luego: poner el activo en azul
            document.getElementById(`btnEnvio${periodo.charAt(0).toUpperCase() + periodo.slice(1)}`).className =
                'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition';
        }

        // Botones con cambio visual
        document.getElementById('btnEnvioDia').onclick = () => cargarGraficoEnvios('dia');
        document.getElementById('btnEnvioSemana').onclick = () => cargarGraficoEnvios('semana');
        document.getElementById('btnEnvioMes').onclick = () => cargarGraficoEnvios('mes');

        // Carga inicial (mes activo)
        cargarGraficoEnvios('mes');

        // === GRÁFICO ESTADO (TORTA) ===
        async function cargarGraficoEstado() {
            const response = await fetch('get_estado_envios.php');
            const datos = await response.json();

            new Chart(document.getElementById('graficoEstado'), {
                type: 'doughnut',
                data: {
                    labels: ['Pendientes', 'Completados'],
                    datasets: [{
                        data: [datos.pendientes, datos.completados],
                        backgroundColor: ['#fb923c', '#4ade80'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            document.getElementById('totalPendientes').textContent = datos.pendientes;
            document.getElementById('totalCompletados').textContent = datos.completados;
        }

        cargarGraficoEstado();

        // === GRÁFICO TOP 10 ANÁLISIS ===
        async function cargarGraficoTopAnalisis() {
            const response = await fetch('get_top_analisis.php');
            const datos = await response.json();

            new Chart(document.getElementById('graficoTopAnalisis'), {
                type: 'bar',
                data: {
                    labels: datos.labels,
                    datasets: [{
                        label: 'Cantidad de solicitudes',
                        data: datos.data,
                        backgroundColor: 'rgba(168, 85, 247, 0.8)', // morado bonito
                        borderColor: 'rgba(168, 85, 247, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y', // barras horizontales (mejor para top 10)
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Solicitudes: ' + context.parsed.x;
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
        }

        // Cargar al iniciar
        cargarGraficoTopAnalisis();

        // === GRÁFICO DEMORAS POR ETAPA ===
        let graficoDemoras = null;

        async function cargarGraficoDemoras(unidad = 'horas') {
            const response = await fetch(`get_demoras.php?unidad=${unidad}`);
            const datos = await response.json();

            if (graficoDemoras) graficoDemoras.destroy();

            graficoDemoras = new Chart(document.getElementById('graficoDemoras'), {
                type: 'bar',
                data: {
                    labels: datos.labels, // ej: ['GRS → Transporte', 'Transporte → Laboratorio']
                    datasets: [{
                        label: `Tiempo promedio (${unidad})`,
                        data: datos.data,
                        backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(251, 146, 60, 0.8)', 'rgba(74, 222, 128, 0.8)'],
                        borderColor: ['rgba(59, 130, 246, 1)', 'rgba(251, 146, 60, 1)', 'rgba(74, 222, 128, 1)'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed.y.toFixed(1) + ' ' + unidad;
                                }
                            }
                        }
                    }
                }
            });

            // Actualizar botones
            document.getElementById('btnDemoraHoras').className = unidad === 'horas' ? 'px-4 py-2 bg-blue-600 text-white rounded-lg' : 'px-4 py-2 bg-gray-300 text-gray-700 rounded-lg';
            document.getElementById('btnDemoraDias').className = unidad === 'dias' ? 'px-4 py-2 bg-blue-600 text-white rounded-lg' : 'px-4 py-2 bg-gray-300 text-gray-700 rounded-lg';
        }

        // Botones
        document.getElementById('btnDemoraHoras').onclick = () => cargarGraficoDemoras('horas');
        document.getElementById('btnDemoraDias').onclick = () => cargarGraficoDemoras('dias');

        // Carga inicial (por horas)
        cargarGraficoDemoras('horas');
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendarioTracking');
            let calendar = null;
            let codEnvioFiltro = ''; // Variable global para el filtro actual

            // Función para cargar eventos (con o sin filtro por codEnvio)
            function cargarEventos(fetchInfo, successCallback, failureCallback) {
                let url = 'get_eventos_calendario.php?start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr;

                // Si hay filtro activo, lo agregamos al URL
                if (codEnvioFiltro.trim() !== '') {
                    url += '&codEnvio=' + encodeURIComponent(codEnvioFiltro.trim());
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        successCallback(data);
                    })
                    .catch(err => {
                        console.error('Error cargando eventos:', err);
                        failureCallback(err);
                    });
            }

            // Inicializar el calendario
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                height: 'auto',
                editable: false,
                navLinks: true,
                dayMaxEvents: 4, // Recomendado: evita sobrecarga visual en month
                events: cargarEventos,
                eventClick: function(info) {
                    alert('Envío: ' + info.event.title +
                        '\nUbicación: ' + info.event.extendedProps.ubicacion +
                        '\nFecha/Hora: ' + info.event.start);
                    // Aquí puedes abrir un modal más completo después
                },
                loading: function(isLoading) {
                    // Opcional: mostrar un spinner mientras carga
                    if (isLoading) {
                        calendarEl.style.opacity = '0.6';
                    } else {
                        calendarEl.style.opacity = '1';
                    }
                }
            });

            calendar.render();

            // === LÓGICA DEL BUSCADOR ===
            const inputBuscador = document.getElementById('buscadorCodEnvio');
            const btnBuscar = document.getElementById('btnBuscar');
            const btnLimpiar = document.getElementById('btnLimpiar');

            // Función para aplicar el filtro
            function aplicarFiltro() {
                codEnvioFiltro = inputBuscador.value.trim();
                calendar.refetchEvents(); // Recarga los eventos con el nuevo filtro
            }

            // Buscar al hacer clic en el botón
            btnBuscar.addEventListener('click', aplicarFiltro);

            // Buscar al presionar Enter en el input
            inputBuscador.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    aplicarFiltro();
                }
            });

            // Limpiar filtro y mostrar todo
            btnLimpiar.addEventListener('click', function() {
                inputBuscador.value = '';
                codEnvioFiltro = '';
                calendar.refetchEvents();
            });
        });
    </script>

</body>

</html>