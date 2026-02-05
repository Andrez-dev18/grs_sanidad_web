<?php

session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) {
            window.top.location.href = "../../login.php";
        } else {
            window.location.href = "../../login.php";
        }
    </script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Reportes</title>

    <!-- Tailwind CSS -->
    <link href="../../css/output.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/sweetalert-helpers.js"></script>

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">
        <div id="metrics-cards" class="max-w-6xl mx-auto grid grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-100 rounded-xl h-24 animate-pulse"></div>
            <div class="bg-gray-100 rounded-xl h-24 animate-pulse"></div>
            <div class="bg-gray-100 rounded-xl h-24 animate-pulse"></div>
            <div class="bg-gray-100 rounded-xl h-24 animate-pulse"></div>
            <div class="bg-gray-100 rounded-xl h-24 animate-pulse"></div>
            <div class="bg-gray-100 rounded-xl h-24 animate-pulse"></div>
        </div>
        <!-- Últimos 10 envíos (versión compacta en 2 columnas) -->
        <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-sm p-4 border border-gray-200 mb-6">
            <h3 class="text-base font-semibold text-gray-800 mb-2">Últimos 10 envíos</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Columna 1 -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">
                                    Código Envío</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Fecha
                                    Envío</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">
                                    Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaEnviosRecientesCol1" class="divide-y divide-gray-100">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-center text-gray-500">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Columna 2 -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">
                                    Código Envío</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">Fecha
                                    Envío</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 uppercase tracking-wider">
                                    Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaEnviosRecientesCol2" class="divide-y divide-gray-100">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-center text-gray-500">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Filtro por año -->
        <div class="max-w-4xl mx-auto mb-6">
            <label for="yearFilter" class="block text-sm font-medium text-gray-700 mb-2">Filtrar por año:</label>
            <select id="yearFilter"
                class="w-full md:w-auto px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <!-- Se llenará automáticamente -->
            </select>
        </div>
        <!-- Gráficos en columna -->
        <div class="max-w-4xl mx-auto space-y-8 mb-10">
            <!-- Envíos por mes -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Envíos por mes</h3>
                <div class="chart-container" style="position: relative; height:200px;">
                    <canvas id="chartEnviosMes"></canvas>
                </div>
                <div id="envios-mes-summary" class="mt-3 text-sm text-gray-600 text-center"></div>

            </div>

            <!-- Estado general -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Estado general de los envíos</h3>
                <div class="chart-container" style="position: relative; height:200px;">
                    <canvas id="chartEstado"></canvas>
                </div>
                <div id="estado-summary" class="mt-3 text-sm text-gray-600 text-center"></div>
            </div>
            <!-- Estado detallado por tipo (en dos columnas) -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Estado detallado por tipo</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Columna 1: Cuantitativas -->
                    <div class="flex flex-col">
                        <h4 class="text-md font-medium text-gray-700 mb-2">Cuantitativas</h4>
                        <div class="chart-container w-full" style="height:150px; min-height:150px; position:relative;">
                            <canvas id="chartCuantitativas"></canvas>
                        </div>
                        <div id="cuant-summary"
                            class="mt-2 text-sm text-gray-600 text-center min-h-[44px] flex flex-col justify-center">
                        </div>
                    </div>

                    <!-- Columna 2: Cualitativas -->
                    <div class="flex flex-col">
                        <h4 class="text-md font-medium text-gray-700 mb-2">Cualitativas</h4>
                        <div class="chart-container w-full" style="height:150px; min-height:150px; position:relative;">
                            <canvas id="chartCualitativas"></canvas>
                        </div>
                        <div id="cuali-summary"
                            class="mt-2 text-sm text-gray-600 text-center min-h-[44px] flex flex-col justify-center">
                        </div>
                    </div>
                </div>
            </div>
            <!-- Top 10 Muestras -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 10 Muestras</h3>
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="chartTopMuestras"></canvas>
                </div>
                <div id="top-muestras-resumen" class="mt-3 text-sm text-gray-600 text-center"></div>
            </div>

            <!-- Top 10 Análisis -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 10 Análisis</h3>
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="chartTopAnalisis"></canvas>
                </div>
                <div id="top-analisis-resumen" class="mt-3 text-sm text-gray-600 text-center"></div>
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

        <script>
            // --- Variables globales para los gráficos ---
            let chartEnvios = null;
            let chartEstado = null;
            let chartCuantitativas = null;
            let chartCualitativas = null;
            let chartTopMuestras = null;
            let chartTopAnalisis = null;

            let yearSelect = null;
            const currentYear = new Date().getFullYear();
            let selectedYear = currentYear;

            function populateYearSelect() {
                const current = new Date().getFullYear();
                const startYear = current - 5;
                for (let y = current; y >= startYear; y--) {
                    const option = document.createElement('option');
                    option.value = y;
                    option.textContent = y;
                    yearSelect.appendChild(option);
                }
                yearSelect.value = current;
                selectedYear = current;
            }

            // Inicialización principal
            document.addEventListener('DOMContentLoaded', () => {
                yearSelect = document.getElementById('yearFilter');
                if (!yearSelect) {
                    console.error('❌ No se encontró el elemento #yearFilter en el DOM');
                    return;
                }

                populateYearSelect();
                loadData(selectedYear);

                yearSelect.addEventListener('change', () => {
                    selectedYear = parseInt(yearSelect.value);
                    loadData(selectedYear);
                });
            });

            function renderMetricsCards({
                totalEnvios,
                pctCompletasGeneral,
                pctCuantCompletas,
                pctCualiCompletas,
                topMuestras,
                topAnalisis
            }) {
                const cardContainer = document.getElementById('metrics-cards');
                if (!cardContainer) return;

                const [m1, m2, m3] = topMuestras || [{ nomMuestra: '-', total: 0 }];
                const [a1, a2, a3] = topAnalisis || [{ nomAnalisis: '-', total: 0 }];

                // Colores suaves para las tarjetas
                const colors = [
                    'bg-blue-50 border-blue-200 text-blue-800',      // Envíos Totales
                    'bg-green-50 border-green-200 text-green-800',    // % Completas General
                    'bg-purple-50 border-purple-200 text-purple-800',  // Cualitativas Completas
                    'bg-indigo-50 border-indigo-200 text-indigo-800',  // Cuantitativas Completas
                    'bg-amber-50 border-amber-200 text-amber-800',     // Muestra más común
                    'bg-pink-50 border-pink-200 text-pink-800'        // Análisis más común
                ];

                cardContainer.innerHTML = `
        <!-- Fila 1 -->
        <div class="bg-gradient-to-br ${colors[0]} rounded-xl shadow-sm p-4 border text-center flex flex-col justify-center">
            <p class="text-xs font-medium">Envíos Totales</p>
            <p class="text-xl font-bold mt-1">${totalEnvios}</p>
        </div>
        <div class="bg-gradient-to-br ${colors[1]} rounded-xl shadow-sm p-4 border text-center flex flex-col justify-center">
            <p class="text-xs font-medium">% Completas (General)</p>
            <p class="text-xl font-bold mt-1">${pctCompletasGeneral}%</p>
        </div>

        <!-- Fila 2 -->
        <div class="bg-gradient-to-br ${colors[2]} rounded-xl shadow-sm p-4 border text-center flex flex-col justify-center">
            <p class="text-xs font-medium">Cualitativas Completas</p>
            <p class="text-xl font-bold mt-1">${pctCualiCompletas}%</p>
        </div>
        <div class="bg-gradient-to-br ${colors[3]} rounded-xl shadow-sm p-4 border text-center flex flex-col justify-center">
            <p class="text-xs font-medium">Cuantitativas Completas</p>
            <p class="text-xl font-bold mt-1">${pctCuantCompletas}%</p>
        </div>

        <!-- Fila 3 -->
        <div class="bg-gradient-to-br ${colors[4]} rounded-xl shadow-sm p-4 border text-center flex flex-col justify-center">
            <p class="text-xs font-medium">Top 3 Muestras</p>
            <p class="text-sm font-semibold mt-1">${m1?.nomMuestra || '-'}</p>
            <p class="text-xs mt-1">${m2?.nomMuestra ? `2°: ${m2.nomMuestra}` : ''}</p>
            <p class="text-xs">${m3?.nomMuestra ? `3°: ${m3.nomMuestra}` : ''}</p>
        </div>
        <div class="bg-gradient-to-br ${colors[5]} rounded-xl shadow-sm p-4 border text-center flex flex-col justify-center">
            <p class="text-xs font-medium">Top 3 Análisis</p>
            <p class="text-sm font-semibold mt-1">${a1?.nomAnalisis || '-'}</p>
            <p class="text-xs mt-1">${a2?.nomAnalisis ? `2°: ${a2.nomAnalisis}` : ''}</p>
            <p class="text-xs">${a3?.nomAnalisis ? `3°: ${a3.nomAnalisis}` : ''}</p>
        </div>
    `;
            }

            async function loadData(year) {
                const qs = `?year=${year}`;
                console.log(year);
                try {
                    const enviosData = (await (await fetch(`envios-por-mes.php${qs}`)).json()).data;
                    const estadoData = (await (await fetch(`estado-solicitudes.php${qs}`)).json()).data;
                    const cuantData = (await (await fetch(`solicitudes-cuantitativas.php${qs}`)).json()).data;
                    const cualiData = (await (await fetch(`solicitudes-cualitativas.php${qs}`)).json()).data;
                    const muestrasData = (await (await fetch(`top-muestras.php${qs}`)).json()).data;
                    const analisisData = (await (await fetch(`top-analisis.php${qs}`)).json()).data;
                    console.log(enviosData);

                    renderChartEnviosMes(enviosData);
                    renderChartEstado(estadoData);
                    renderChartCuantitativas(cuantData);
                    renderChartCualitativas(cualiData);
                    renderChartTopMuestras(muestrasData);
                    renderChartTopAnalisis(analisisData);
                    cargarEnviosRecientes(year);
                    // Calcular métricas para los recuadros
                    const totalEnvios = estadoData.completadas + estadoData.pendientes;
                    const pctCompGen = totalEnvios ? ((estadoData.completadas / totalEnvios) * 100).toFixed(1) : 0;
                    const pctCuantComp = (cuantData.completadas + cuantData.pendientes)
                        ? ((cuantData.completadas / (cuantData.completadas + cuantData.pendientes)) * 100).toFixed(1)
                        : 0;
                    const pctCualiComp = (cualiData.completadas + cualiData.pendientes)
                        ? ((cualiData.completadas / (cualiData.completadas + cualiData.pendientes)) * 100).toFixed(1)
                        : 0;

                    renderMetricsCards({
                        totalEnvios,
                        pctCompletasGeneral: pctCompGen,
                        pctCuantCompletas: pctCuantComp,
                        pctCualiCompletas: pctCualiComp,
                        topMuestras: muestrasData,
                        topAnalisis: analisisData
                    });

                } catch (err) {
                    console.error('Error al cargar datos:', err);
                    SwalAlert('No se pudieron cargar los datos del dashboard.', 'warning');
                }
            }

            // --- Funciones de renderizado (sin cambios respecto a tu versión estable) ---
            function renderChartEnviosMes(data) {
                const ctx = document.getElementById('chartEnviosMes').getContext('2d');
                if (chartEnvios) chartEnvios.destroy();
                if (data.length === 0) return;

                const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

                // Colores para cada barra (azules suaves)
                const colors = [
                    '#dbeafe', '#bfdbfe', '#93c5fd', '#60a5fa', '#3b82f6', '#2563eb',
                    '#1d4ed8', '#1e40af', '#1e3a8a', '#172554', '#1e1b4b', '#1c1917'
                ];

                chartEnvios = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => `${meses[d.mes - 1] || d.mes} ${d.anio}`),
                        datasets: [{
                            data: data.map(d => d.total),
                            backgroundColor: data.map((_, i) => colors[i % colors.length]),
                            borderColor: data.map((_, i) => colors[i % colors.length].replace('e', 'a')),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        maxBarThickness: 60,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } },
                            x: { ticks: { autoSkip: true, maxRotation: 0, minRotation: 0 } }
                        }
                    }
                });

                // Generar resumen debajo del gráfico
                const summaryEl = document.getElementById('envios-mes-summary');
                if (summaryEl) {
                    const total = data.reduce((sum, d) => sum + d.total, 0);
                    summaryEl.innerHTML = `
            <div class="text-xs font-medium text-gray-500 mb-1">Resumen mensual:</div>
            <div class="flex flex-wrap justify-center gap-2 text-xs">
                ${data.map((d, i) => `
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full" style="background-color: ${colors[i % colors.length]}"></span>
                        <span>${meses[d.mes - 1] || d.mes}: ${d.total}</span>
                    </div>
                `).join('')}
            </div>
            <div class="mt-1 text-sm font-bold">Total: ${total} envíos</div>
        `;
                }
            }

            function renderChartEstado(data) {
                const ctx = document.getElementById('chartEstado').getContext('2d');
                if (chartEstado) chartEstado.destroy();

                const total = data.completadas + data.pendientes;
                const pctComp = total ? ((data.completadas / total) * 100).toFixed(1) : 0;
                const pctPend = total ? ((data.pendientes / total) * 100).toFixed(1) : 0;


                document.getElementById('estado-summary').innerHTML = `
    <div class="flex flex-col items-center gap-1 text-sm">
        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500"></span> <strong>Completadas:</strong> ${data.completadas} — ${pctComp}%</div>
        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500"></span> <strong>Pendientes:</strong> ${data.pendientes} — ${pctPend}%</div>
    </div>
`;

                chartEstado = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completadas', 'Pendientes'],
                        datasets: [{
                            data: [data.completadas, data.pendientes],
                            backgroundColor: ['#3b82f6', '#f59e0b'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: { legend: { display: false } }
                    }
                });
            }

            function renderChartCuantitativas(data) {
                const ctx = document.getElementById('chartCuantitativas').getContext('2d');
                if (chartCuantitativas) chartCuantitativas.destroy();

                const total = data.completadas + data.pendientes;
                const pctComp = total ? ((data.completadas / total) * 100).toFixed(1) : 0;
                const pctPend = total ? ((data.pendientes / total) * 100).toFixed(1) : 0;

                //leyenda debajo del gráfico
                document.getElementById('cuant-summary').innerHTML = `
                        <div class="flex flex-col md:flex-row flex-wrap justify-center gap-3 md:gap-6 text-sm">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-blue-500 flex-shrink-0"></span>
                                <div class="text-left">
                                    <strong class="block">Completadas:</strong>
                                    <span>${data.completadas} — ${pctComp}%</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-amber-500 flex-shrink-0"></span>
                                <div class="text-left">
                                    <strong class="block">Pendientes:</strong>
                                    <span>${data.pendientes} — ${pctPend}%</span>
                                </div>
                            </div>
                        </div>
                    `;

                chartCuantitativas = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completadas', 'Pendientes'],
                        datasets: [{
                            data: [data.completadas, data.pendientes],
                            backgroundColor: ['#3b82f6', '#f59e0b'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: { legend: { display: false } }
                    }
                });
            }

            function renderChartCualitativas(data) {
                const ctx = document.getElementById('chartCualitativas').getContext('2d');
                if (chartCualitativas) chartCualitativas.destroy();

                const total = data.completadas + data.pendientes;
                const pctComp = total ? ((data.completadas / total) * 100).toFixed(1) : 0;
                const pctPend = total ? ((data.pendientes / total) * 100).toFixed(1) : 0;

                //leyenda debajo del gráfico
                document.getElementById('cuali-summary').innerHTML = `
                <div class="flex flex-col md:flex-row flex-wrap justify-center gap-3 md:gap-6 text-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 flex-shrink-0"></span>
                        <div class="text-left">
                            <strong class="block">Completadas:</strong>
                            <span>${data.completadas} — ${pctComp}%</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-red-500 flex-shrink-0"></span>
                        <div class="text-left">
                            <strong class="block">Pendientes:</strong>
                            <span>${data.pendientes} — ${pctPend}%</span>
                        </div>
                    </div>
                </div>
            `;

                chartCualitativas = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completadas', 'Pendientes'],
                        datasets: [{
                            data: [data.completadas, data.pendientes],
                            backgroundColor: ['#10b981', '#ef4444'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: { legend: { display: false } }
                    }
                });
            }

            function renderChartTopMuestras(data) {
                const ctx = document.getElementById('chartTopMuestras').getContext('2d');
                if (chartTopMuestras) chartTopMuestras.destroy();

                chartTopMuestras = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.nomMuestra),
                        datasets: [{ data: data.map(d => d.total) }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true } }
                    }
                });

                //resumen en dos columnas
                const summaryEl = document.getElementById('top-muestras-resumen');
                if (summaryEl && data.length > 0) {
                    const half = Math.ceil(data.length / 2);
                    const col1 = data.slice(0, half);
                    const col2 = data.slice(half);

                    summaryEl.innerHTML = `
            <div class="text-xs font-medium text-gray-500 mb-1">Top 10 Muestras:</div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="flex flex-col gap-1">
                    ${col1.map((d, i) => `<div><strong>${i + 1}.</strong> ${d.nomMuestra} — ${d.total}</div>`).join('')}
                </div>
                <div class="flex flex-col gap-1">
                    ${col2.map((d, i) => `<div><strong>${half + i + 1}.</strong> ${d.nomMuestra} — ${d.total}</div>`).join('')}
                </div>
            </div>
        `;
                }
            }

            function renderChartTopAnalisis(data) {
                const ctx = document.getElementById('chartTopAnalisis').getContext('2d');
                if (chartTopAnalisis) chartTopAnalisis.destroy();

                chartTopAnalisis = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.nomAnalisis),
                        datasets: [{ data: data.map(d => d.total) }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true } }
                    }
                });

                // Generar resumen en dos columnas
                const summaryEl = document.getElementById('top-analisis-resumen');
                if (summaryEl && data.length > 0) {
                    const half = Math.ceil(data.length / 2);
                    const col1 = data.slice(0, half);
                    const col2 = data.slice(half);

                    summaryEl.innerHTML = `
                        <div class="text-xs font-medium text-gray-500 mb-1">Top 10 Análisis:</div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="flex flex-col gap-1">
                                ${col1.map((d, i) => `<div><strong>${i + 1}.</strong> ${d.nomAnalisis} — ${d.total}</div>`).join('')}
                            </div>
                            <div class="flex flex-col gap-1">
                                ${col2.map((d, i) => `<div><strong>${half + i + 1}.</strong> ${d.nomAnalisis} — ${d.total}</div>`).join('')}
                            </div>
                        </div>
                    `;
                }
            }
            async function cargarEnviosRecientes(year) {
                try {
                    const response = await fetch(`envios-recientes.php?year=${year}`);
                    const data = await response.json();

                    // Dividir los datos en dos mitades
                    const half = Math.ceil(data.length / 2);
                    const col1 = data.slice(0, half);
                    const col2 = data.slice(half);

                    // Renderizar columna 1
                    renderTablaEnvios(col1, 'tablaEnviosRecientesCol1');

                    // Renderizar columna 2
                    renderTablaEnvios(col2, 'tablaEnviosRecientesCol2');

                } catch (err) {
                    console.error('Error al cargar envíos recientes:', err);
                    document.getElementById('tablaEnviosRecientesCol1').innerHTML = `<tr><td colspan="3" class="px-3 py-2 text-center text-red-500">Error al cargar</td></tr>`;
                    document.getElementById('tablaEnviosRecientesCol2').innerHTML = `<tr><td colspan="3" class="px-3 py-2 text-center text-red-500">Error al cargar</td></tr>`;
                }
            }

            function renderTablaEnvios(envios, tbodyId) {
                const tbody = document.getElementById(tbodyId);
                if (!tbody) return;

                if (!envios || envios.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" class="px-3 py-2 text-center text-gray-500">No hay envíos</td></tr>`;
                    return;
                }

                tbody.innerHTML = envios.map(envio => {
                    // Formatear fecha a dd-mm-yyyy
                    const fecha = new Date(envio.fecEnvio);
                    const dia = String(fecha.getDate()).padStart(2, '0');
                    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
                    const año = fecha.getFullYear();
                    const fechaFormateada = `${dia}-${mes}-${año}`;

                    const estadoClase = envio.estado === 'completado'
                        ? 'bg-green-100 text-green-800'
                        : 'bg-yellow-100 text-yellow-800';

                    return `
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-gray-800">${envio.codEnvio}</td>
                            <td class="px-3 py-2 text-gray-700">${fechaFormateada}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ${estadoClase}">
                                    ${envio.estado.charAt(0).toUpperCase() + envio.estado.slice(1)}
                                </span>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        </script>
</body>

</html>