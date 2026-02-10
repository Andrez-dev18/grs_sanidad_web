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
    <title>Dashboard - base</title>

    <!-- Tailwind CSS -->
    <link href="../../../css/output.css" rel="stylesheet">

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

        <!-- SECCION DE SCANEO Y CODIGO -->
        <div id="seccionBusqueda" class="max-w-xl mx-auto bg-white rounded-lg shadow-md p-6">
            <!-- TÍTULO -->
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Seguimiento de muestra</h2>

            <!-- Tabs -->
            <div class="flex border-b mb-4">
                <!-- CÓDIGO ACTIVO -->
                <button id="tab-codigo"
                    class="px-4 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-600">
                    N° de orden
                </button>

                <!-- CÁMARA INACTIVA -->
                <button id="tab-camara"
                    class="px-4 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-blue-600 hover:border-blue-600 transition-colors duration-200 ease-in-out">
                    Cámara
                </button>
            </div>

            <!-- Contenido de tabs -->
            <div id="panel-codigo" class="">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Ingrese el número de orden
                </label>

                <div class="flex flex-col sm:flex-row items-stretch mb-3 gap-2 sm:gap-0">
                    <!-- Prefijo fijo: SAN- -->
                    <div class="bg-gray-100 border border-gray-300 px-4 py-2 flex items-center text-gray-700 font-medium whitespace-nowrap rounded-t-md sm:rounded-l-md sm:rounded-tr-none sm:border-r-0">
                        SAN-
                    </div>

                    <!-- Select para el año (DINÁMICO) -->
                    <select id="anioCodigo"
                        class="w-full sm:w-24 text-center border border-gray-300 px-2 py-2 focus:ring focus:ring-blue-300 focus:outline-none bg-white">
                        <!-- Las opciones se generarán automáticamente con JS -->
                    </select>

                    <!-- Campo para los últimos 4 dígitos -->
                    <input type="text"
                        id="secuenciaCodigo"
                        maxlength="4"
                        placeholder="0001"
                        class="flex-1 border border-gray-300 px-3 py-2 focus:ring focus:ring-blue-300 focus:outline-none"
                        required>

                    <!-- Botón buscar -->
                    <button id="btnValidar"
                        class="bg-blue-600 text-white px-6 py-2 rounded-b-md sm:rounded-r-md sm:rounded-bl-none hover:bg-blue-700 transition flex items-center justify-center">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>

                <div id="mensajeValidacion" class="text-center text-sm mt-4"></div>
            </div>

            <div id="panel-camara" class="hidden">

                <div id="reader" class="mb-3"></div>

                <div id="cardqr"
                    class="mb-3 relative w-full h-60 border-2 border-dashed border-blue-400 rounded-xl flex items-center justify-center bg-white shadow-sm">
                    <div class="absolute inset-0 pointer-events-none rounded-xl border-4 border-blue-500 opacity-20"></div>
                    <i class="fa-solid fa-qrcode text-blue-600 text-8xl z-10"></i>
                </div>

                <select id="selectCamara"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 mb-3 focus:ring focus:ring-blue-300 focus:outline-none">
                    <option value="">Seleccione una cámara</option>
                </select>

                <div>
                    <button id="startScan"
                        class="w-full bg-blue-800 text-white py-2 rounded-md hover:bg-blue-900 transition mb-2">
                        Abrir cámara
                    </button>
                    <button id="stopScan"
                        class="w-full bg-gray-300 text-gray-700 py-2 rounded-md hover:bg-gray-400 transition">
                        Detener cámara
                    </button>
                </div>
            </div>

        </div>

        <!-- TRACKING -->
        <div id="trackingContainer" class="hidden bg-white rounded-lg shadow-md p-6 relative">
            <!-- Botón "Realizar otra consulta" - Responsivo -->
            <div class="mb-4 flex justify-end">
                <button
                    onclick="limpiarTracking()"
                    class="inline-block bg-gray-800 text-white text-sm font-semibold py-2 px-5 rounded-lg hover:bg-gray-700 transition shadow-md hover:shadow-lg">
                    Realizar otra consulta
                </button>
            </div>

            <!-- CABECERA DEL ENVÍO - DISEÑO PROFESIONAL -->
            <div class=" p-6 mb-8 ">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-xl font-bold text-gray-800">
                        Información del envío
                    </h3>
                    <span class="px-4 py-2 rounded-full text-xs font-semibold uppercase tracking-wider
                     ${document.getElementById('t_estado').textContent.toLowerCase().includes('pendiente') ? 'bg-yellow-100 text-yellow-800' : 
                       document.getElementById('t_estado').textContent.toLowerCase().includes('finalizado') ? 'bg-green-100 text-green-800' : 
                       'bg-blue-100 text-blue-800'}">
                        <span id="t_estado"></span>
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Código -->
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-600 text-white rounded-lg p-3 shadow-md">
                            <i class="fa-solid fa-barcode text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 uppercase font-medium">Código de envío</p>
                            <p class="text-lg font-bold text-gray-800" id="t_codEnvio"></p>
                        </div>
                    </div>

                    <!-- Fecha y hora -->
                    <div class="flex items-center space-x-3">
                        <div class="bg-indigo-600 text-white rounded-lg p-3 shadow-md">
                            <i class="fa-solid fa-calendar-alt text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 uppercase font-medium">Fecha de envío</p>
                            <p class="text-lg font-bold text-gray-800" id="t_fecha"></p>
                        </div>
                    </div>

                    <!-- Laboratorio -->
                    <div class="flex items-center space-x-3">
                        <div class="bg-purple-600 text-white rounded-lg p-3 shadow-md">
                            <i class="fa-solid fa-flask text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 uppercase font-medium">Laboratorio</p>
                            <p class="text-base font-semibold text-gray-800" id="t_lab"></p>
                        </div>
                    </div>

                    <!-- Transporte -->
                    <div class="flex items-center space-x-3">
                        <div class="bg-orange-600 text-white rounded-lg p-3 shadow-md">
                            <i class="fa-solid fa-truck text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 uppercase font-medium">Transporte</p>
                            <p class="text-base font-semibold text-gray-800" id="t_trans"></p>
                        </div>
                    </div>

                    <!-- Análisis -->
                    <div class="flex items-center space-x-3 sm:col-span-2 lg:col-span-1">
                        <div class="bg-green-600 text-white rounded-lg p-3 shadow-md">
                            <i class="fa-solid fa-vials text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 uppercase font-medium">Análisis registrados</p>
                            <p class="text-lg font-bold text-gray-800" id="t_analisis"></p>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="mb-4">
            <!-- PROGRESO HORIZONTAL (DESKTOP) / VERTICAL (MOBILE) -->
            <div class="relative">
                <!-- Línea de fondo gris -->
                <div class="hidden md:block absolute top-10 left-0 right-0 h-1 bg-gray-300 -z-10"></div>
                <div class="md:hidden absolute left-1/2 top-0 bottom-0 w-1 bg-gray-300 -z-10 transform -translate-x-1/2"></div>

                <!-- Línea de progreso azul -->
                <div id="progressLine" class="hidden md:block absolute top-10 left-0 h-1 bg-blue-600 transition-all duration-700 -z-10" style="width: 0%"></div>
                <div id="progressLineMobile" class="md:hidden absolute left-1/2 top-0 w-1 bg-blue-600 transition-all duration-700 -z-10 transform -translate-x-1/2" style="height: 0%"></div>

                <!-- Contenedor de los 4 pasos -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 md:gap-0">

                    <!-- PASO 1 -->
                    <div class="flex flex-col items-center relative z-10">
                        <div id="icon-1" class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-gray-200 flex items-center justify-center shadow-lg transition-all duration-500 mb-4">
                            <svg class="w-8 h-8 md:w-10 md:h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-sm mb-1">GRS</h4>
                        <p class="text-xs text-gray-600 mb-4">Solicitud creada</p>

                        <!-- Botón y detalles debajo del paso 1 -->
                        <button onclick="toggleDetails(1)" class="w-full max-w-xs bg-gray-800 text-white text-sm font-semibold py-2 px-4 rounded-md hover:bg-gray-700 transition mb-2">
                            VER DETALLE
                        </button>
                        <div id="details-1" class="hidden w-full mt-2 space-y-4 border-l-4 border-blue-600 pl-4">
                            <div id="timeline-1" class="space-y-4"></div>
                        </div>
                    </div>

                    <!-- PASO 2 -->
                    <div class="flex flex-col items-center relative z-10">
                        <div id="icon-2" class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-gray-200 flex items-center justify-center shadow-lg transition-all duration-500 mb-4">
                            <svg class="w-8 h-8 md:w-10 md:h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-sm mb-1">Transporte</h4>
                        <p class="text-xs text-gray-600 mb-4">Recibido Por transportista</p>

                        <button onclick="toggleDetails(2)" class="w-full max-w-xs bg-gray-800 text-white text-sm font-semibold py-2 px-4 rounded-md hover:bg-gray-700 transition mb-2">
                            VER DETALLE
                        </button>
                        <div id="details-2" class="hidden w-full mt-2 space-y-4 border-l-4 border-blue-600 pl-4">
                            <div id="timeline-2" class="space-y-4"></div>
                        </div>
                    </div>

                    <!-- PASO 3 -->
                    <div class="flex flex-col items-center relative z-10">
                        <div id="icon-3" class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-gray-200 flex items-center justify-center shadow-lg transition-all duration-500 mb-4">
                            <svg class="w-8 h-8 md:w-10 md:h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.2 1.2.1 3.3-1.6 3.3H5c-1.7 0-2.8-2.1-1.6-3.3l5-5A2 2 0 008 8.172V3L7 2z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-sm mb-1">En Laboratorio</h4>
                        <p class="text-xs text-gray-600 mb-4">Muestra recibida</p>

                        <button onclick="toggleDetails(3)" class="w-full max-w-xs bg-gray-800 text-white text-sm font-semibold py-2 px-4 rounded-md hover:bg-gray-700 transition mb-2">
                            VER DETALLE
                        </button>
                        <div id="details-3" class="hidden w-full mt-2 space-y-4 border-l-4 border-blue-600 pl-4">
                            <div id="timeline-3" class="space-y-4"></div>
                        </div>
                    </div>

                    <!-- PASO 4 -->
                    <div class="flex flex-col items-center relative z-10">
                        <div id="icon-4" class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-gray-200 flex items-center justify-center shadow-lg transition-all duration-500 mb-4">
                            <svg class="w-8 h-8 md:w-10 md:h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-sm mb-1">Finalizado</h4>
                        <p class="text-xs text-gray-600 mb-4">Resultados emitidos</p>

                        <button onclick="toggleDetails(4)" class="w-full max-w-xs bg-gray-800 text-white text-sm font-semibold py-2 px-4 rounded-md hover:bg-gray-700 transition mb-2">
                            VER DETALLE
                        </button>
                        <div id="details-4" class="hidden w-full mt-2 space-y-4 border-l-4 border-blue-600 pl-4">
                            <div id="timeline-4" class="space-y-4"></div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- MODAL PARA VER MÚLTIPLES EVIDENCIAS -->
        <div id="modalEvidencia" class="fixed inset-0 bg-black/80 hidden z-50">
            <!-- Fondo oscuro sin padding lateral para maximizar espacio -->
            <div class="flex min-h-full items-start justify-center pt-4 px-4 sm:pt-0 sm:items-center">

                <div class="bg-white rounded-t-3xl sm:rounded-xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col">

                    <!-- Header -->
                    <div class="flex justify-between items-center px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Evidencia fotográfica
                        </h3>
                        <div class="flex items-center gap-3">
                            <!-- Botón abrir en nueva pestaña (solo ícono) -->
                            <button onclick="abrirFotoActualEnPestana()"
                                class="text-blue-600 hover:text-blue-800 transition bg-blue-100 hover:bg-blue-200 rounded-full p-2">
                                <i class="fa-solid fa-external-link-alt text-lg"></i>
                            </button>

                            <!-- Botón cerrar -->
                            <button onclick="cerrarModalEvidencia()" class="text-gray-500 hover:text-gray-700 text-2xl">
                                ×
                            </button>
                        </div>
                    </div>

                    <!-- Carrusel de imágenes -->
                    <div class="flex-1 overflow-hidden relative bg-gray-50">
                        <div id="carruselFotos" class="flex transition-transform duration-300 ease-in-out h-full">
                            <!-- Imágenes dinámicas -->
                        </div>

                        <!-- Flechas -->
                        <button id="prevFoto" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition z-10">
                            <i class="fa-solid fa-chevron-left text-2xl text-gray-800"></i>
                        </button>
                        <button id="nextFoto" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition z-10">
                            <i class="fa-solid fa-chevron-right text-2xl text-gray-800"></i>
                        </button>

                        <!-- Contador -->
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 text-white px-4 py-2 rounded-full text-sm font-medium z-10">
                            <span id="contadorFotos">1 / 1</span>
                        </div>

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

    <script src="../../../assets/js/scanapp.min.js"></script>

    <script>
        function botonLoading(boton, activar = true) {
            if (activar) {
                boton.disabled = true;
                boton.innerHTML = `
            <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        `;
            } else {
                boton.disabled = false;
                boton.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i>';
            }
        }

        // Generar años dinámicamente al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const selectAnio = document.getElementById('anioCodigo');
            const anioActual = new Date().getFullYear(); // 2025 hoy, 2026 mañana
            const anioCortoActual = anioActual.toString().slice(-3); // "025", "026", etc.

            // Generar los últimos 4 años (puedes cambiar el número)
            for (let i = 0; i < 4; i++) {
                const anio = anioActual - i;
                const anioCorto = anio.toString().slice(-3); // "025", "024", "023", "022"

                const option = document.createElement('option');
                option.value = anioCorto;
                option.textContent = anioCorto;

                // Seleccionar automáticamente el año actual
                if (anioCorto === anioCortoActual) {
                    option.selected = true;
                }

                selectAnio.appendChild(option);
            }
        });

        let evidenciasActuales = []; // Array de rutas
        let indiceFotoActual = 0;

        function abrirModalEvidencia(rutasEvidencia) {
            if (!rutasEvidencia || rutasEvidencia.trim() === '') return;

            evidenciasActuales = rutasEvidencia.split(',').map(r => r.trim()).filter(r => r);

            if (evidenciasActuales.length === 0) return;

            indiceFotoActual = 0;
            renderizarCarrusel();
            document.getElementById('modalEvidencia').classList.remove('hidden');
        }

        function cerrarModalEvidencia() {
            document.getElementById('modalEvidencia').classList.add('hidden');
            document.getElementById('carruselFotos').innerHTML = '';
            evidenciasActuales = [];
        }

        function renderizarCarrusel() {
            const carrusel = document.getElementById('carruselFotos');
            carrusel.innerHTML = '';

            evidenciasActuales.forEach((ruta, index) => {
                const div = document.createElement('div');
                div.className = 'min-w-full h-full flex items-center justify-center px-4';
                div.innerHTML = `
            <img src="../../../${ruta}" alt="Evidencia ${index + 1}" 
                 class="max-w-full max-h-full object-contain rounded-lg shadow-xl">
            `;
                carrusel.appendChild(div);
            });

            // Posicionar en la foto actual
            carrusel.style.transform = `translateX(-${indiceFotoActual * 100}%)`;

            // Actualizar contador
            document.getElementById('contadorFotos').textContent = `${indiceFotoActual + 1} / ${evidenciasActuales.length}`;

            // Ocultar flechas si solo hay una foto
            const prev = document.getElementById('prevFoto');
            const next = document.getElementById('nextFoto');
            if (evidenciasActuales.length <= 1) {
                prev.classList.add('hidden');
                next.classList.add('hidden');
            } else {
                prev.classList.remove('hidden');
                next.classList.remove('hidden');
            }
        }

        // Navegación
        document.getElementById('prevFoto').addEventListener('click', () => {
            if (indiceFotoActual > 0) {
                indiceFotoActual--;
                renderizarCarrusel();
            }
        });

        document.getElementById('nextFoto').addEventListener('click', () => {
            if (indiceFotoActual < evidenciasActuales.length - 1) {
                indiceFotoActual++;
                renderizarCarrusel();
            }
        });

        // Abrir foto actual en nueva pestaña
        function abrirFotoActualEnPestana() {
            if (evidenciasActuales.length > 0) {
                window.open("../../../" + evidenciasActuales[indiceFotoActual], '_blank');
            }
        }
    </script>

    <script>
        let html5QrCode = null;
        let isScanning = false;
        let isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        // Método que hace la lectura
        function onScanSuccess(decodedText, decodedResult) {
            if (!isScanning) return;

            isScanning = false;
            html5QrCode
                .stop()
                .then(() => {
                    console.log("Escaneo detenido");
                    document.getElementById("cardqr").classList.remove("hidden");
                    if (!isMobile) document.getElementById("selectCamara").value = "";
                    document.getElementById("startScan").disabled = false;
                })
                .catch((err) => console.error("Error al detener: ", err));

            validarOrden(decodedText);
        }

        function onScanFailure(error) {
            // console.warn(`Code scan error = ${error}`);
        }

        // Seleccionar cámara por defecto (trasera en móviles)
        function getDefaultCamera(devices) {
            if (isMobile) {
                const backCamera = devices.find((device) =>
                    device.label.toLowerCase().includes("back") || device.label.toLowerCase().includes("rear")
                );
                return backCamera ? backCamera.id : devices[0].id;
            }
            return devices[0].id;
        }

        // Cargar cámaras disponibles
        Html5Qrcode.getCameras()
            .then((devices) => {
                if (devices && devices.length) {
                    let select = document.getElementById("selectCamara");

                    // Si es móvil, ocultar el select y no cargarlo
                    if (isMobile) {
                        select.style.display = "none";
                    } else {
                        // Si no es móvil, mostrar el select y cargarlo
                        let html = `<option value="" selected>Seleccione una cámara</option>`;
                        devices.forEach((device) => {
                            html += `<option value="${device.id}">${device.label}</option>`;
                        });
                        select.innerHTML = html;
                        select.value = getDefaultCamera(devices); // Preseleccionar en escritorio
                    }

                    // Guardar las cámaras disponibles para usarlas después
                    window.availableCameras = devices;
                }
            })
            .catch((err) => {
                console.error("Error al obtener cámaras: ", err);
            });

        // Iniciar el escaneo desde el botón
        const iniciarEscaneo = () => {
            let idCamara;

            if (isMobile) {
                // En móvil, usar la cámara trasera por defecto
                if (!window.availableCameras) {
                    Swal.fire("No se han cargado las cámaras aún. Intenta de nuevo.");
                    return;
                }
                idCamara = getDefaultCamera(window.availableCameras);
            } else {
                // En escritorio, usar la cámara seleccionada en el select
                idCamara = document.getElementById("selectCamara").value;
                if (!idCamara) {
                    Swal.fire("Por favor, selecciona una cámara.");
                    return;
                }
            }

            document.getElementById("cardqr").classList.add("hidden");
            document.getElementById("startScan").disabled = true;

            html5QrCode = new Html5Qrcode("reader");
            isScanning = true;
            html5QrCode
                .start(
                    idCamara, {
                        fps: 10,
                        qrbox: {
                            width: 450,
                            height: 450
                        },
                    },
                    onScanSuccess,
                    onScanFailure
                )
                .catch((err) => {
                    console.error("Error al iniciar la cámara: ", err);
                    isScanning = false;
                    document.getElementById("startScan").disabled = false;
                    Swal.fire("Error al iniciar la cámara: " + err.message);
                });
        };

        // Detener la cámara manualmente
        const detenerCamara = () => {
            if (html5QrCode && isScanning) {
                isScanning = false;
                html5QrCode
                    .stop()
                    .then(() => {
                        document.getElementById("cardqr").classList.remove("hidden");
                        if (!isMobile) document.getElementById("selectCamara").value = "";
                        document.getElementById("startScan").disabled = false;
                    })
                    .catch((err) => console.error("Error al detener: ", err));
            }
        };

        function convertirJSON(dataForm) {
            const formJson = {};
            dataForm.forEach((value, key) => {
                formJson[key] = value;
            });
            return formJson;
        }


        document.getElementById('btnValidar').addEventListener('click', function() {
            const btn = this; // referencia al botón

            const anio = document.getElementById('anioCodigo').value;
            const secuencia = document.getElementById('secuenciaCodigo').value.trim().padStart(4, '0');

            if (secuencia.length !== 4 || !/^\d{4}$/.test(secuencia)) {
                mostrarMensaje('Ingrese los 4 últimos dígitos', true);
                return;
            }

            const codigoCompleto = `SAN-${anio}${secuencia}`;

            // === AQUÍ AGREGAMOS EL LOADING ===
            botonLoading(btn, true);

            validarOrden(codigoCompleto)
                .finally(() => {
                    botonLoading(btn, false); // siempre vuelve al icono de lupa
                });
        });


        function validarOrden(codigo) {

            if (!codigo) return;

            return fetch('tracking_envio.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        codigo
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) {
                        mostrarMensaje(data.mensaje, true);
                        return;
                    }

                    renderTracking(data);
                    mostrarMensaje('');
                });
        }

        function mostrarMensaje(msg, error = false) {
            const el = document.getElementById('mensajeValidacion');

            if (!msg) {
                el.textContent = '';
                return;
            }

            el.textContent = msg;
            el.className = `text-center text-sm mt-4 ${
                error ? 'text-red-600' : 'text-green-600'
            }`;
        }

        function renderTracking(data) {

            const e = data.envio;

            document.getElementById('t_codEnvio').textContent = e.codEnvio;
            document.getElementById('t_estado').textContent = e.estado;
            document.getElementById('t_lab').textContent = e.nomLab;
            document.getElementById('t_trans').textContent = e.nomEmpTrans;
            document.getElementById('t_fecha').textContent = `${e.fecEnvio} ${e.horaEnvio}`;
            document.getElementById('t_analisis').textContent =
                `${e.totalAnalisis} análisis`;

            // MOSTRAR tracking y OCULTAR sección de búsqueda
            document.getElementById('trackingContainer').classList.remove('hidden');
            document.getElementById('seccionBusqueda').classList.add('hidden')

            const pasoActual = renderSteps(data.historial);
            renderTimeline(data.historial);
        }

        function renderSteps(historial) {
            // Reset todos los íconos a gris
            for (let i = 1; i <= 4; i++) {
                const icon = document.getElementById(`icon-${i}`);
                icon.className = 'w-16 h-16 md:w-20 md:h-20 rounded-full bg-gray-200 flex items-center justify-center shadow-lg transition-all duration-500 mb-4';
                icon.querySelector('svg').className = 'w-8 h-8 md:w-10 md:h-10 text-gray-600';
            }
            document.getElementById('progressLine').style.width = '0%';
            document.getElementById('progressLineMobile').style.height = '0%';

            if (historial.length === 0) return;

            const pasosActivos = new Set();

            historial.forEach(h => {
                if (!h.ubicacion) return;

                const ubicacion = h.ubicacion.trim();

                // Pasos 1, 2 y 3: solo por ubicación
                if (ubicacion === 'GRS') {
                    pasosActivos.add(1);
                } else if (ubicacion === 'Transporte') {
                    pasosActivos.add(2);
                } else if (ubicacion === 'Laboratorio') {
                    const accionLower = (h.accion || '').toLowerCase();

                    // Paso 3: solo si hay recepción en laboratorio
                    if (accionLower.includes('recepción de muestra por laboratorio') ||
                        accionLower.includes('recepcionado por laboratorio')) {
                        pasosActivos.add(3);
                    }

                    // Paso 4: solo si hay registro de resultados (cualitativo o cuantitativo)
                    if (accionLower.includes('registro_resultados_cualitativos') ||
                        accionLower.includes('registro_resultados_cuantitativos')) {
                        pasosActivos.add(4);
                    }
                }
            });

            if (pasosActivos.size === 0) return;

            const pasoMaximo = Math.max(...pasosActivos);

            // Activar visualmente los pasos
            pasosActivos.forEach(paso => {
                const icon = document.getElementById(`icon-${paso}`);
                icon.classList.remove('bg-gray-200');
                icon.classList.add('bg-blue-600');
                icon.querySelector('svg').classList.remove('text-gray-600');
                icon.querySelector('svg').classList.add('text-white');
            });

            // Línea de progreso
            const progress = (pasoMaximo - 1) / 3 * 100;
            document.getElementById('progressLine').style.width = `${progress}%`;
            document.getElementById('progressLineMobile').style.height = `${progress}%`;
        }

        function renderTimeline(historial) {
            // Limpiar todos los timelines
            for (let i = 1; i <= 4; i++) {
                document.getElementById(`timeline-${i}`).innerHTML = '';
            }

            historial.forEach(h => {
                let paso = null;

                if (h.ubicacion) {
                    const ubicacion = h.ubicacion.trim();

                    if (ubicacion === 'GRS') paso = 1;
                    else if (ubicacion === 'Transporte') paso = 2;
                    else if (ubicacion === 'Laboratorio') {
                        const accionLower = (h.accion || '').toLowerCase();

                        if (accionLower.includes('recepción de muestra por laboratorio') ||
                            accionLower.includes('recepcionado por laboratorio')) {
                            paso = 3;
                        }
                        if (accionLower.includes('registro_resultados_cualitativos') ||
                            accionLower.includes('registro_resultados_cuantitativos')) {
                            paso = 4;
                        }
                    }
                }

                if (paso === null) return;

                // Título legible
                let tituloAccion = h.accion || 'Acción registrada';
                if (tituloAccion === 'ENVIO_REGISTRADO') tituloAccion = 'Envío Registrado';
                else if (tituloAccion === 'Recepción de muestra') tituloAccion = 'Recepción por transportista';
                else if (tituloAccion === 'Recepción de muestra por laboratorio') tituloAccion = 'Recepcionado por laboratorio';
                else if (tituloAccion.toLowerCase().includes('registro_resultados_cualitativos')) tituloAccion = 'Resultados cualitativos registrados';
                else if (tituloAccion.toLowerCase().includes('registro_resultados_cuantitativos')) tituloAccion = 'Resultados cuantitativos registrados';

                const div = document.createElement('div');
                div.className = 'relative pl-8 text-sm bg-white rounded-lg shadow-sm p-4 mb-4 border border-gray-100';

                let botonEvidencia = '';
                if (h.evidencia && h.evidencia.trim() !== '') {
                    botonEvidencia = `
                        <button onclick="abrirModalEvidencia('${h.evidencia.replace(/'/g, "\\'")}')" 
                                class="absolute top-4 right-4 text-blue-600 hover:text-blue-800 transition">
                            <i class="fa-solid fa-eye text-xl"></i> 
                        </button>
                    `;
                }

                div.innerHTML = `
            ${botonEvidencia}
            <span class="absolute left-0 top-6 w-3 h-3 bg-blue-600 rounded-full -translate-x-1/2"></span>
            <p class="font-semibold text-blue-900 pr-10">${tituloAccion}</p>
            <p class="text-xs text-blue-800 mt-1">${h.comentario || ''}</p>
            <p class="text-xs text-gray-600 mt-2">
                ${h.fechaHoraRegistro} · ${h.ubicacion || 'Sin ubicación'} · ${h.usuario}
            </p>
        `;
                document.getElementById(`timeline-${paso}`).appendChild(div);
            });
        }

        function toggleDetails(paso) {
            const details = document.getElementById(`details-${paso}`);
            const button = details.previousElementSibling; // el botón justo arriba

            if (details.classList.contains('hidden')) {
                details.classList.remove('hidden');
                button.textContent = 'OCULTAR DETALLE';
            } else {
                details.classList.add('hidden');
                button.textContent = 'VER DETALLE';
            }
        }

        // Asignar el evento al botón
        document.getElementById("startScan").onclick = iniciarEscaneo;
        document.getElementById("stopScan").onclick = detenerCamara;

        function alertScanSuccess(response, estado) {

            Swal.fire({
                icon: "success",
                html: htmlCardSweet(response, estado),
                confirmButtonText: "Cerrar",
                customClass: {
                    popup: 'text-sm rounded-lg p-4'
                }
            });
        }


        document.getElementById("tab-camara").addEventListener('click', function() {
            activarTab('camara');
        });

        document.getElementById("tab-codigo").addEventListener('click', function() {
            activarTab('codigo');
        });

        function activarTab(tab) {
            const camaraBtn = document.getElementById('tab-camara');
            const codigoBtn = document.getElementById('tab-codigo');
            const panelCamara = document.getElementById('panel-camara');
            const panelCodigo = document.getElementById('panel-codigo');

            // Resetear ambos tabs
            [camaraBtn, codigoBtn].forEach(btn => {
                btn.classList.remove('text-blue-600', 'border-blue-600');
                btn.classList.add(
                    'text-gray-500',
                    'border-transparent',
                    'hover:text-blue-600',
                    'hover:border-blue-600'
                );
            });

            if (tab === 'camara') {
                // Activar cámara
                limpiarTracking();
                camaraBtn.classList.remove('text-gray-500', 'border-transparent');
                camaraBtn.classList.add('text-blue-600', 'border-blue-600');

                panelCamara.classList.remove('hidden');
                panelCodigo.classList.add('hidden');
            } else {
                // Activar código
                limpiarTracking();
                codigoBtn.classList.remove('text-gray-500', 'border-transparent');
                codigoBtn.classList.add('text-blue-600', 'border-blue-600');

                panelCodigo.classList.remove('hidden');
                panelCamara.classList.add('hidden');
            }
        }

        function limpiarTracking() {
            // Ocultar tracking
            document.getElementById('trackingContainer').classList.add('hidden');

            // MOSTRAR nuevamente la sección de búsqueda
            document.getElementById('seccionBusqueda').classList.remove('hidden');

            // Limpiar campos de información
            document.getElementById('t_codEnvio').textContent = '';
            document.getElementById('t_estado').textContent = '';
            document.getElementById('t_lab').textContent = '';
            document.getElementById('t_trans').textContent = '';
            document.getElementById('t_fecha').textContent = '';
            document.getElementById('t_analisis').textContent = '';

            // Resetear íconos y progreso
            for (let i = 1; i <= 4; i++) {
                const icon = document.getElementById(`icon-${i}`);
                if (icon) {
                    icon.className = 'w-16 h-16 md:w-20 md:h-20 rounded-full bg-gray-200 flex items-center justify-center shadow-lg transition-all duration-500 mb-4';
                    icon.querySelector('svg').className = 'w-8 h-8 md:w-10 md:h-10 text-gray-600';
                }

                // Cerrar detalles abiertos y limpiar timeline
                const details = document.getElementById(`details-${i}`);
                if (details) {
                    details.classList.add('hidden');
                    const button = details.previousElementSibling;
                    if (button && button.tagName === 'BUTTON') {
                        button.textContent = 'VER DETALLE';
                    }
                }

                const timeline = document.getElementById(`timeline-${i}`);
                if (timeline) {
                    timeline.innerHTML = '';
                }
            }

            // Resetear línea de progreso
            document.getElementById('progressLine').style.width = '0%';
            document.getElementById('progressLineMobile').style.height = '0%';

            // Limpiar el input del código


            // Limpiar mensaje de validación
            mostrarMensaje('');

        }

        function htmlCardSweet(d, estado) {

            const html = `
          <div class="text-left rounded-md p-4 bg-white shadow-lg border border-gray-100">
          <h2 class="text-center mb-3 text-xl font-semibold">Pase Vehicular valido!</h2>
          <p class="text-center mb-3 text-base font-semibold">Se registro una ${estado}</p>
            <div class="flex items-center space-x-3 mb-4">
              <i class="fa-solid fa-user text-blue-600 text-xl"></i>
              <div>
                <p class="text-sm text-gray-700">Estudiante</p>
                <p class="font-semibold text-gray-800">${d.data.alumno}</p>
              </div>
            </div>

            <div class="flex items-center space-x-3 mb-4">
              <i class="fa-solid fa-graduation-cap text-blue-600 text-xl"></i>
              <div>
                <p class="text-sm text-gray-700">Carrera</p>
                <p class="font-semibold text-gray-800">${d.data.carrera}</p>
              </div>
            </div>

            <div class="flex items-center space-x-3 mb-4">
              <i class="fa-solid fa-id-card text-blue-600 text-xl"></i>
              <div>
                <p class="text-sm text-gray-700">Código</p>
                <p class="font-semibold text-gray-800">${d.data.codigoQR}</p>
              </div>
            </div>

            <div class="flex items-center space-x-3 mb-4">
              <i class="fa-solid fa-car text-blue-600 text-xl"></i>
              <div>
                <p class="text-sm text-gray-700">Vehículo</p>
                <p class="font-semibold text-gray-800">${d.data.marca} ${d.data.modelo}</p>
              </div>
            </div>

            <div class="flex items-center space-x-3">
              <i class="fa-solid fa-rectangle-list text-blue-600 text-xl"></i>
              <div>
                <p class="text-sm text-gray-700">Placa</p>
                <p class="font-semibold text-gray-800">${d.data.placa}</p>
              </div>
            </div>

          </div>
        `;
            return html;
        }

        function alertErrors(icon, titulo, texto) {
            Swal.fire({
                icon: icon,
                title: titulo,
                text: texto,
                confirmButtonText: "Cerrar",
            })
        }
    </script>

</body>

</html>