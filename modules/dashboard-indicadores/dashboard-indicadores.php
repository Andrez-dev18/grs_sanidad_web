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
    <div class="container mx-auto px-6 py-12">

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

        <!-- Footer -->
        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © 2025
            </p>
        </div>

    </div>

    <!-- Librería Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


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
    </script>

    </div>
</body>

</html>