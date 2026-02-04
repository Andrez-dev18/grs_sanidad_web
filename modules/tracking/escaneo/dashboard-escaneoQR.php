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
        <div class="max-w-xl mx-auto bg-white rounded-lg shadow-md p-6">
            <!-- TÍTULO -->
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Escanear QR</h2>

            <!-- Tabs -->
            <div class="flex border-b mb-4">
                <button id="tab-camara"
                    class="px-4 py-2 text-sm font-medium border-b-2 transition-colors duration-200 ease-in-out border-blue-600 text-blue-600">
                    Cámara
                </button>
                <button id="tab-codigo"
                    class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-blue-600 hover:border-blue-600 border-b-2 border-transparent">
                    N° de orden
                </button>
            </div>

            <!-- Contenido de tabs -->
            <div id="panel-camara">

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

            <div id="panel-codigo" class="hidden">
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
        </div>


        <!-- MODAL DETALLE ORDEN -->
        <div id="modalOrden" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50 px-4">
            <!-- Contenedor principal con altura máxima y scroll interno -->
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col animate-fade-in mb-12">

                <!-- Header fijo -->
                <div class="flex justify-between items-center px-6 py-4 border-b flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Detalle de la orden de envío
                    </h3>
                    <button onclick="cerrarModalOrden()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
                        ×
                    </button>
                </div>

                <!-- Body con scroll -->
                <div class="px-6 py-4 overflow-y-auto flex-1">
                    <!-- Todo el contenido que ya tenías -->
                    <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm text-gray-700 mb-4">
                        <div><span class="font-medium text-gray-600">Código de envío:</span> <span id="d_codEnvio" class="ml-1 font-semibold"></span></div>
                        <div><span class="font-medium text-gray-600">Fecha de envío:</span> <span id="d_fechaEnvio" class="ml-1"></span></div>
                        <div><span class="font-medium text-gray-600">Hora de envío:</span> <span id="d_horaEnvio" class="ml-1"></span></div>
                        <div><span class="font-medium text-gray-600">Laboratorio:</span> <span id="d_laboratorio" class="ml-1"></span></div>
                        <div><span class="font-medium text-gray-600">Análisis registrados:</span> <span id="d_totalAnalisis" class="ml-1 font-semibold"></span></div>
                    </div>

                    <!-- SELECTOR RECEPTOR -->
                    <div class="mb-6">
                        <label for="tipoReceptor" class="block text-sm font-medium text-gray-700 mb-2">
                            ¿Quién está realizando la recepción?
                        </label>
                        <select id="tipoReceptor" class="w-full border border-gray-300 rounded-md px-2 py-3 text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-600 focus:outline-none transition">
                            <option value="">Seleccione una opción</option>
                            <option value="Transporte" selected>Transportista</option>
                            <option value="Laboratorio">Laboratorio</option>
                        </select>
                        <p id="errorReceptor" class="text-red-600 text-sm mt-2 hidden">
                            Por favor, seleccione quién está realizando la recepción.
                        </p>
                    </div>

                    <!-- EVIDENCIA FOTOGRÁFICA (OPCIONAL - MÚLTIPLES FOTOS) -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Evidencia fotográfica <span class="text-gray-500 text-xs">(opcional - hasta 3 fotos recomendadas)</span>
                        </label>

                        <!-- Contenedor de fotos seleccionadas -->
                        <div id="fotosContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 mb-6">
                            <!-- Las miniaturas se agregarán aquí dinámicamente -->
                        </div>

                        <!-- Área para agregar más fotos (siempre visible si hay menos de 10) -->
                        <div id="addFotoContainer" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="cursor-pointer block">
                                <input type="file" id="inputFoto" accept="image/*" capture="environment" class="hidden" multiple>
                                <div class="border-2 border-dashed border-gray-400 rounded-lg px-4 py-8 text-center hover:border-blue-500 transition h-full flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-camera text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-base font-medium text-gray-700">Tomar fotos</p>
                                    <p class="text-xs text-gray-500 mt-1">máx. 3 recomendadas</p>
                                </div>
                            </label>

                            <label class="cursor-pointer block">
                                <input type="file" id="inputGaleria" accept="image/*" class="hidden" multiple>
                                <div class="border-2 border-dashed border-gray-400 rounded-lg px-4 py-8 text-center hover:border-blue-500 transition h-full flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-images text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-base font-medium text-gray-700">Desde galería</p>
                                    <p class="text-xs text-gray-500 mt-1">seleccionar varias</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- OBSERVACIONES -->
                    <div class="mb-4">
                        <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">
                            Observaciones
                        </label>
                        <textarea id="observaciones" rows="3" placeholder="Ingrese alguna observación (opcional)"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring focus:ring-blue-300 focus:outline-none resize-none"></textarea>
                    </div>
                </div>

                <!-- Footer fijo (siempre visible) -->
                <div class="px-6 pt-4 pb-8 sm:pb-6 border-t bg-gray-50 rounded-b-xl flex-shrink-0">
                    <div class="flex justify-end gap-3">
                        <button onclick="cerrarModalOrden()"
                            class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition shadow">
                            Cancelar
                        </button>
                        <button id="btnRecepcionar"
                            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition shadow-lg">
                            Recepcionar
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Footer dinámico -->
        <div class="text-center mt-12 mb-5">
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
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

        let fotosSeleccionadas = []; // Array de objetos {file, previewUrl}

        const fotosContainer = document.getElementById('fotosContainer');
        const addFotoContainer = document.getElementById('addFotoContainer');

        // Compresión simple pero efectiva (calidad 0.7 = ~70% reducción promedio)
        async function comprimirImagen(file) {
            return new Promise((resolve) => {
                const img = new Image();
                const reader = new FileReader();

                reader.onload = function(e) {
                    img.src = e.target.result;
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        // Máximo 1200px de ancho/alto para mantener calidad
                        let width = img.width;
                        let height = img.height;
                        if (width > 1200 || height > 1200) {
                            if (width > height) {
                                height = Math.round(height * (1200 / width));
                                width = 1200;
                            } else {
                                width = Math.round(width * (1200 / height));
                                height = 1200;
                            }
                        }

                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob((blob) => {
                            const archivoComprimido = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            resolve(archivoComprimido);
                        }, 'image/jpeg', 0.7); // calidad 70%
                    };
                };
                reader.readAsDataURL(file);
            });
        }

        // Agregar fotos con vista previa
        async function agregarFotos(files) {
            for (let file of files) {
                if (!file.type.startsWith('image/')) continue;

                // Comprimir
                const archivoComprimido = await comprimirImagen(file);

                // Crear vista previa
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative group';
                    div.innerHTML = `
                <img src="${e.target.result}" class="w-full h-32 object-cover rounded-lg shadow-md border border-gray-200">
                <button type="button" onclick="removerFoto(this)" 
                        class="absolute top-2 right-2 bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                    ×
                </button>
            `;
                    fotosContainer.appendChild(div);
                };
                reader.readAsDataURL(archivoComprimido);

                fotosSeleccionadas.push(archivoComprimido);
            }

            // Ocultar botones si hay 5 o más fotos
            if (fotosSeleccionadas.length >= 3) {
                addFotoContainer.classList.add('hidden');
            }
        }

        // Remover foto
        function removerFoto(boton) {
            const index = Array.from(fotosContainer.children).indexOf(boton.parentElement);
            if (index > -1) {
                fotosSeleccionadas.splice(index, 1);
                boton.parentElement.remove();

                // Mostrar botones de agregar si hay menos de 5
                if (fotosSeleccionadas.length < 3) {
                    addFotoContainer.classList.remove('hidden');
                }
            }
        }

        // Event listeners
        document.getElementById('inputFoto').addEventListener('change', (e) => {
            if (e.target.files.length) agregarFotos(e.target.files);
        });

        document.getElementById('inputGaleria').addEventListener('change', (e) => {
            if (e.target.files.length) agregarFotos(e.target.files);
        });

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

            const anio = document.getElementById('anioCodigo').value.trim();
            const secuenciaInput = document.getElementById('secuenciaCodigo').value.trim();

            // Validar que el campo no esté vacío
            if (!secuenciaInput) {
                mostrarAlerta('Por favor ingrese los 4 dígitos del código');
                document.getElementById('secuenciaCodigo').focus();
                return;
            }

            // Validar que tenga exactamente 4 caracteres
            if (secuenciaInput.length !== 4) {
                mostrarAlerta('El código debe tener exactamente 4 dígitos');
                document.getElementById('secuenciaCodigo').focus();
                return;
            }

            // Validar que sean solo números
            if (!/^\d{4}$/.test(secuenciaInput)) {
                mostrarAlerta('El código debe contener solo números (0-9)');
                document.getElementById('secuenciaCodigo').focus();
                return;
            }

            const codigoCompleto = `SAN-${anio}${secuenciaInput}`;

            // === AQUÍ AGREGAMOS EL LOADING ===
            botonLoading(btn, true);

            // Llamar a la búsqueda
            validarOrden(codigoCompleto)
                .finally(() => {
                    botonLoading(btn, false); // siempre vuelve al icono de lupa
                });
        });

        document.getElementById('btnRecepcionar').addEventListener('click', recepcionarOrden);

        let ordenActual = null;

        function validarOrden(codigo) {

            if (!codigo) return;

            return fetch('buscar_orden.php', {
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
                        mostrarAlerta(data.mensaje, true);
                        return;
                    }

                    ordenActual = data;

                    document.getElementById('d_codEnvio').textContent = data.codEnvio;
                    document.getElementById('d_fechaEnvio').textContent = data.fecEnvio;
                    document.getElementById('d_horaEnvio').textContent = data.horaEnvio;
                    document.getElementById('d_laboratorio').textContent = data.nomLab;
                    document.getElementById('d_totalAnalisis').textContent = data.totalAnalisis;

                    abrirModalOrden();
                    //mostrarAlerta('');
                });
        }

        function abrirModalOrden() {
            document.getElementById('modalOrden')
                .classList.remove('hidden');
            document.getElementById('modalOrden')
                .classList.add('flex');
        }

        function cerrarModalOrden() {
            document.getElementById('modalOrden')
                .classList.add('hidden');
            document.getElementById('modalOrden')
                .classList.remove('flex');
        }


        function recepcionarOrden() {
            if (!ordenActual) return;

            const observaciones = document.getElementById('observaciones').value.trim();
            const tipoReceptor = document.getElementById('tipoReceptor').value;

            if (!tipoReceptor) {
                mostrarAlertaError('Seleccione quién realiza la recepción');
                return;
            }

            const formData = new FormData();
            formData.append('codEnvio', ordenActual.codEnvio);
            formData.append('obs', observaciones);
            formData.append('tipoReceptor', tipoReceptor);

            // Agregar todas las fotos comprimidas
            fotosSeleccionadas.forEach((foto, index) => {
                formData.append('evidencias[]', foto, foto.name);
            });

            fetch('recepcionar_orden.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        cerrarModalOrden();
                        mostrarAlertaRecepcion(ordenActual.codEnvio, tipoReceptor);
                        resetOrden();
                    } else {
                        mostrarAlertaError(data.mensaje);
                    }
                })
                .catch(err => {
                    console.error(err);
                    mostrarAlertaError('Error de conexión');
                });
        }

        function resetOrden() {
            ordenActual = null;

            // Limpiar observaciones
            document.getElementById('observaciones').value = '';

            // Limpiar todas las fotos
            fotosSeleccionadas = []; // vaciar array
            fotosContainer.innerHTML = ''; // remover todas las miniaturas

            // Mostrar de nuevo los botones para agregar fotos
            addFotoContainer.classList.remove('hidden');

            // Resetear inputs file (importante para permitir subir la misma foto de nuevo)
            document.getElementById('inputFoto').value = '';
            document.getElementById('inputGaleria').value = '';
        }

        function mostrarAlertaRecepcion(codigoEnvio, tipoReceptor) {
            const receptor = tipoReceptor === 'Transporte' ? 'Transportista' : 'Laboratorio';

            // Fecha y hora actual bonitas
            const ahora = new Date();
            const fecha = ahora.toLocaleDateString('es-PE', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            }); // ej: 27 de diciembre de 2025
            const hora = ahora.toLocaleTimeString('es-PE', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }); // ej: 18:45

            const mensaje = `Muestra ${codigoEnvio} recepcionada por ${receptor} hoy ${fecha} a las ${hora}`;

            Swal.fire({
                icon: 'success',
                title: '¡Recepción registrada!',
                text: mensaje,
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'rounded-xl',
                    confirmButton: 'bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-lg'
                }
            });
        }

        function mostrarAlertaError(mensaje) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje,
                confirmButtonText: 'Cerrar',
                customClass: {
                    confirmButton: 'bg-red-600 hover:bg-red-700 text-white font-medium px-6 py-2'
                }
            });
        }

        function mostrarAlerta(mensaje, esError = false) {
            Swal.fire({
                icon: esError ? 'error' : 'warning',
                title: esError ? 'Error' : 'Atención',
                text: mensaje,
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'rounded-xl',
                    confirmButton: 'bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-lg'
                }
            });
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
                camaraBtn.classList.remove('text-gray-500', 'border-transparent');
                camaraBtn.classList.add('text-blue-600', 'border-blue-600');

                panelCamara.classList.remove('hidden');
                panelCodigo.classList.add('hidden');
            } else {
                // Activar código
                codigoBtn.classList.remove('text-gray-500', 'border-transparent');
                codigoBtn.classList.add('text-blue-600', 'border-blue-600');

                panelCodigo.classList.remove('hidden');
                panelCamara.classList.add('hidden');
            }
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