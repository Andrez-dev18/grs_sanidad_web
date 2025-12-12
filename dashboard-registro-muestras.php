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
    <title>Dashboard - Inicio</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/output.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <!-- Estilos para el control de navegacion dinamico -->
    <link rel="stylesheet" href="css/style-NavigationControls.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js "></script>
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

        /* Estilos para los cuadraditos del código de referencia */
        .codigo-referencia-box {
            width: 25px;
            height: 25px;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            border: 2px solid #cbd5e0;
            border-radius: 4px;
            background: #f7fafc;
            padding: 0;
            line-height: 25px;
            box-sizing: border-box;
            margin: 0 2px;
        }

        .codigo-referencia-container {
            display: flex;
            flex-wrap: nowrap;
            gap: 2px;
            overflow-x: auto;
            max-width: 100%;
        }

        /* Estilos para la tabla vertical */
        .table-vertical th,
        .table-vertical td {
            vertical-align: middle;
            text-align: center;
        }

        .table-vertical th {
            background-color: #6c5b7b;
            color: white;
        }

        .table-vertical td {
            background-color: #fff;
        }

        /* Estilos para la previsualización del PDF */
        .pdf-preview {
            font-family: Arial, sans-serif;
            font-size: 10px;
            background: white;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .pdf-header {
            background-color: #6c5b7b;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }

        .pdf-table {
            border-collapse: collapse;
            width: 100%;
            font-size: 8px;
            border: 1px solid #000;
        }

        .pdf-table th,
        .pdf-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        .pdf-table th {
            background-color: #6c5b7b;
            color: white;
        }

        .pdf-table td {
            background-color: #fff;
        }

        .pdf-footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: center;
        }

        .pdf-footer table {
            border-collapse: collapse;
            margin: 0 auto;
            width: 60%;
        }

        .pdf-footer td {
            padding: 5px;
            text-align: left;
            border-bottom: 1px solid #000;
        }

        .pdf-footer td:first-child {
            width: 30%;
            text-align: right;
            padding-right: 10px;
        }

        .pdf-footer td:last-child {
            width: 70%;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">
        <!-- VISTA REGISTRO -->
        <div id="viewRegistro" class="content-view active">
            <div class="form-container max-w-7xl mx-auto">
                <form id="sampleForm" onsubmit="return handleSampleSubmit(event)">
                    <!-- INFORMACIÓN DE REGISTRO Y ENVÍO -->
                    <div class="form-section mb-8">
                        <div class="dual-group-container grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <!-- GRUPO 1: Datos de Envío -->
                            <div class="field-group border border-gray-300 rounded-2xl p-8 bg-white">
                                <div
                                    class="group-header text-sm font-bold text-blue-600 uppercase tracking-wide pb-4 mb-6">
                                    Datos de Envío
                                </div>
                                <div class="space-y-6">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Fecha de Envío <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" id="fechaEnvio" name="fechaEnvio" required
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Hora <span class="text-red-500">*</span>
                                            </label>
                                            <input type="time" id="horaEnvio" name="horaEnvio" required
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Código de Envío <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="codigoEnvio" name="codigoEnvio" readonly
                                                class="w-full px-4 py-2.5 bg-gray-100 border border-gray-300 rounded-lg font-bold text-blue-600 focus:outline-none">
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Laboratorio <span class="text-red-500">*</span>
                                            </label>
                                            <select id="laboratorio" name="laboratorio" required
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer">
                                                <option value="">Seleccionar...</option>
                                                <?php
                                                $query = "SELECT codigo, nombre FROM san_dim_laboratorio ORDER BY nombre";
                                                $result = mysqli_query($conexion, $query);
                                                if ($result && mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        echo '<option value="' . htmlspecialchars($row['codigo']) . '">' .
                                                            htmlspecialchars($row['nombre']) . '</option>';
                                                    }
                                                } else {
                                                    echo '<option value="">No hay laboratorios disponibles</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- GRUPO 2: Datos de Transporte y Responsables -->
                            <div class="field-group border border-gray-300 rounded-2xl p-8 bg-white">
                                <div
                                    class="group-header text-sm font-bold text-blue-600 uppercase tracking-wide pb-4 mb-6">
                                    TRANSPORTE Y RESPONSABLES
                                </div>
                                <div class="space-y-6">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Empresa de Transporte <span class="text-red-500">*</span>
                                            </label>
                                            <select name="empresa_transporte" id="empresa_transporte" required
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer">
                                                <option value="">Seleccionar...</option>
                                                <?php
                                                $query = "SELECT codigo, nombre FROM san_dim_emptrans ORDER BY nombre";
                                                $result = mysqli_query($conexion, $query);
                                                if ($result && mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        echo '<option value="' . htmlspecialchars($row['codigo']) . '">' .
                                                            htmlspecialchars($row['nombre']) . '</option>';
                                                    }
                                                } else {
                                                    echo '<option value="">No hay empresas de transporte disponibles</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Autorizado por <span class="text-red-500">*</span>
                                            </label>
                                            <input name="autorizado_por" id="autorizado_por" type="text"
                                                placeholder="Nombre" required
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Usuario
                                                Registrador</label>
                                            <input name="usuario_registrador"
                                                value="<?php echo htmlspecialchars($_SESSION['usuario'] ?? 'user'); ?>"
                                                type="text" readonly
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 focus:outline-none">
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Usuario Responsable <span class="text-red-500">*</span>
                                            </label>
                                            <input name="usuario_responsable" id="usuario_responsable" type="text"
                                                placeholder="Nombre del responsable" required
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- Número de Muestras integrado -->
                        <div class="form-field">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Número de Solicitudes <span class="text-red-500">*</span>
                            </label>
                            <!-- Asegurar ancho con clase personalizada o estilo -->
                            <input type="number" id="numeroSolicitudes" name="numeroSolicitudes" min="1" max="20"
                                placeholder="Ingrese cantidad de solicitudes" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <!-- CONTENEDOR DE MUESTRAS DINÁMICAS -->
                    <div id="samples-table-container" class="mt-8">
                        <!--div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="text-lg font-bold">Solicitudes</h3>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="configButton">
                                <i class="fas fa-cog"></i> Configurar Tabla
                            </button>
                        </div>-->
                        <div class="table-responsive" style="overflow-x: auto; min-width: 100%;">
                            <table id="samplesTable" class="table table-bordered table-striped"
                                style="min-width: 800px;">
                                <thead>
                                    <tr>
                                        <th scope="col" style="text-align: center; min-width: 200px;">Tipo de Muestra
                                        </th>
                                        <th scope="col" style="text-align: center;">Código de Referencia</th>
                                        <th scope="col" style="text-align: center;">Fecha de Toma</th>
                                        <th scope="col" style="text-align: center;">Número de Muestras</th>
                                        <th scope="col" style="text-align: center;">Análisis</th>
                                        <th scope="col" style="text-align: center;">Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody id="samplesTableBody">
                                    <!-- Filas dinámicas -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- BUTTONS -->
                    <div class="btn-group flex flex-col-reverse sm:flex-row gap-4 justify-end mt-8">
                        <div class="btn-group flex flex-col-reverse sm:flex-row gap-4 justify-end mt-8">
                            <!--button type="button"
      class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition duration-200">
      Cancelar
  </button-->
                            <button type="submit"
                                class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2">
                                Guardar Registro
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Modal de configuración -->
        <div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="configModalLabel">Configuración de la Tabla</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="viewMode" class="form-label">Modo de Vista:</label>
                            <select class="form-select" id="viewMode">
                                <option value="horizontal">Horizontal</option>
                                <option value="vertical">Vertical</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="typeMuestraDisplay" class="form-label">Mostrar Tipo de Muestra:</label>
                            <select class="form-select" id="typeMuestraDisplay">
                                <option value="singleSelect">Un solo select</option>
                                <option value="allTypes">Todos los tipos de muestra</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" id="applyConfig">Aplicar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de previsualización -->
        <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="previewModalLabel">Previsualización</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="previewContent" style="max-height: 70vh; overflow-y: auto;">
                            <!-- El contenido de la previsualización se generará aquí -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" id="generatePdf">Generar PDF</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="confirmModal"
            class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-6xl max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800">📋 Confirmar Envío de Muestras</h2>
                    <button class="text-gray-500 text-2xl hover:text-gray-700 transition" onclick="closeConfirmModal()">
                        &times;
                    </button>
                </div>

                <!-- Body con scroll interno (vertical y horizontal) -->
                <div class="flex-1 overflow-y-auto overflow-x-auto p-6">
                    <div id="summaryContent" class="min-w-max">
                        <!-- Aquí se inyectará la tabla -->
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-200 bg-white rounded-b-2xl">
                    <div class="flex flex-col sm:flex-row justify-end gap-3">
                        <button type="button" onclick="closeConfirmModal()"
                            class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition duration-200">
                            Cancelar
                        </button>
                        <button type="button" onclick="confirmSubmit()"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2">
                            ✅ Confirmar y Guardar
                        </button>
                    </div>
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
    <!--<!-- Modal de análisis actualizado -->
    <div class="modal fade" id="analisisModal" tabindex="-1" aria-labelledby="analisisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="analisisModalLabel">
                        Seleccionar Análisis — <span id="analisisModalSampleTitle"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3" id="analisisModalBody" style="max-height: 60vh; overflow-y: auto;">
                    <p>Cargando análisis...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="analisisModalSaveBtn">Guardar Selección</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="funciones.js"></script>
    <script src="planificacion.js"></script>
    <!--<script src="registro.js"></script>-->
    <script src="manteminiento.js"></script>

    <script>
        let currentFormData = null;
        // === Variables globales ===
        let currentSample = 0;
        let totalSamples = 0;
        let sampleDataCache = {};
        let allTiposMuestra = [];
        let persistentSampleCache = {};
        const today = new Date();
        const dateStr = today.toISOString().split("T")[0];
        const timeStr = today.toTimeString().split(" ")[0].substring(0, 5);
        const fechaEnvio = document.getElementById("fechaEnvio");
        const horaEnvio = document.getElementById("horaEnvio");
        const fechaToma = document.getElementById("fechaToma");
        if (fechaEnvio) fechaEnvio.value = dateStr;
        if (horaEnvio) horaEnvio.value = timeStr;
        if (fechaToma) fechaToma.value = dateStr;
        const numeroInput = document.getElementById("numeroSolicitudes");
        const container = document.getElementById("samples-table-container");
        const tableBody = document.getElementById("samplesTableBody");

        // === Función para cargar el código de envío ===
        async function loadCodigoEnvio() {
            try {
                const res = await fetch("reserve_codigo_envio.php");
                const data = await res.json();
                if (data.error) throw new Error(data.error);
                document.getElementById("codigoEnvio").value = data.codigo_envio;
            } catch (error) {
                console.error("Error al reservar código:", error);
                alert(
                    "⚠️ No se pudo generar el código de envío. Intente recargar la página."
                );
            }
        }

        // === Función para cargar tipos de muestra ===
        /*async function cargarTiposMuestra(selectId, sampleIndex) {
            try {
                const res = await fetch("get_tipos_muestra.php");
                const tipos = await res.json();
                if (tipos.error) throw new Error(tipos.error);
                allTiposMuestra = tipos;
                const select = document.getElementById(selectId);
                select.innerHTML = '<option value="">Seleccionar...</option>';
                tipos.forEach((tipo) => {
                    const option = document.createElement('option');
                    option.value = tipo.codigo;
                    option.textContent = tipo.nombre;
                    select.appendChild(option);
                });

                // Agregar evento change para actualizar el código de referencia
                select.addEventListener('change', function () {
                    updateCodigoReferencia(sampleIndex);
                });

            } catch (error) {
                allTiposMuestra = [];
                console.error("Error al cargar tipos de muestra:", error);
                alert("⚠️ No se pudieron cargar los tipos de muestra.");
            }
        }*/
        async function cargarTiposMuestra(selectId, sampleIndex) {
            try {
                const res = await fetch("get_tipos_muestra.php");
                const tipos = await res.json();
                if (tipos.error) throw new Error(tipos.error);
                allTiposMuestra = tipos;
                const select = document.getElementById(selectId);
                select.innerHTML = '<option value="">Seleccionar...</option>';
                tipos.forEach((tipo) => {
                    const option = document.createElement('option');
                    option.value = tipo.codigo;
                    option.textContent = tipo.nombre;
                    select.appendChild(option);
                });

                // ✅ FIX: Remover listeners previos antes de agregar uno nuevo
                select.removeEventListener('change', handleTipoMuestraChange);

                // ✅ Usar una función nombrada para poder removerla después
                select.addEventListener('change', handleTipoMuestraChange);

                // Guardar el índice en el elemento para usarlo en el handler
                select.dataset.sampleIndex = sampleIndex;

            } catch (error) {
                allTiposMuestra = [];
                console.error("Error al cargar tipos de muestra:", error);
                alert("⚠️ No se pudieron cargar los tipos de muestra.");
            }
        }
        function handleTipoMuestraChange(event) {
            const select = event.target;
            const sampleIndex = parseInt(select.dataset.sampleIndex);
            updateCodigoReferencia(sampleIndex);
        }

        // === Función para actualizar el código de referencia ===
        function updateCodigoReferencia(sampleIndex) {
            const tipoId = document.getElementById(`tipoMuestra_${sampleIndex}`).value;
            const container = document.getElementById(`codigoReferenciaContainer_${sampleIndex}`);
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.id = `codigoReferenciaValue_${sampleIndex}`;
            hiddenInput.name = `codigoReferenciaValue_${sampleIndex}`;

            if (!tipoId) {
                container.innerHTML = '';
                container.appendChild(hiddenInput);
                return;
            }

            // Obtener la longitud del código de referencia
            fetch(`get_config_muestra.php?tipo=${tipoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) throw new Error(data.error);

                    const longitud = data.tipo_muestra.longitud_codigo;
                    container.innerHTML = '';

                    // Crear los cuadraditos
                    const boxesContainer = document.createElement('div');
                    boxesContainer.id = `codigoReferenciaBoxes_${sampleIndex}`;
                    boxesContainer.className = 'codigo-referencia-container';

                    for (let i = 0; i < longitud; i++) {
                        const box = document.createElement('input');
                        box.type = 'text';
                        box.maxLength = 1;
                        box.className = 'codigo-referencia-box';
                        box.addEventListener('input', function () {
                            this.value = this.value.replace(/[^0-9]/g, "");
                            if (this.value && this.nextElementSibling)
                                this.nextElementSibling.focus();
                            updateHiddenValue();
                        });
                        box.addEventListener('keydown', function (e) {
                            if (
                                e.key === "Backspace" &&
                                !this.value &&
                                this.previousElementSibling
                            ) {
                                this.previousElementSibling.focus();
                            }
                        });
                        boxesContainer.appendChild(box);
                    }

                    // Función para actualizar el valor oculto
                    function updateHiddenValue() {
                        const value = Array.from(boxesContainer.querySelectorAll("input"))
                            .map((i) => i.value || "")
                            .join("");
                        hiddenInput.value = value;
                    }

                    container.appendChild(boxesContainer);
                    container.appendChild(hiddenInput);

                })
                .catch(error => {
                    console.error("Error al obtener la configuración de la muestra:", error);
                    alert("⚠️ No se pudo cargar la configuración de la muestra.");
                });
        }

        function generateTableRows(count) {
            // 1. Guardar estado actual de todas las filas existentes en sampleDataCache
            for (let i = 0; i < totalSamples; i++) {
                const row = document.getElementById(`sampleRow_${i}`);
                if (row) {
                    // Extraer datos actuales y guardar en caché
                    sampleDataCache[i] = {
                        tipoMuestra: document.getElementById(`tipoMuestra_${i}`)?.value || '',
                        codigoReferencia: document.getElementById(`codigoReferenciaValue_${i}`)?.value || '',
                        fechaToma: document.getElementById(`fechaToma_${i}`)?.value || dateStr,
                        numeroMuestras: document.getElementById(`numeroMuestras_${i}`)?.value || '1',
                        analisisSeleccionados: sampleDataCache[i]?.analisisSeleccionados || [],
                        observaciones: document.getElementById(`observaciones_${i}`)?.value || ''
                    };
                }
            }

            // 2. Limpiar y generar nuevas filas
            tableBody.innerHTML = '';
            for (let i = 0; i < count; i++) {
                const row = document.createElement('tr');
                row.id = `sampleRow_${i}`;

                // --- Tipo de Muestra (select) ---
                const tmCell = document.createElement('td');
                const tmSelect = document.createElement('select');
                tmSelect.id = `tipoMuestra_${i}`;
                tmSelect.className = 'form-select';
                tmSelect.innerHTML = '<option value="">Seleccionar...</option>';
                tmSelect.addEventListener('change', function () {
                    updateCodigoReferencia(i);
                    // Guardar en caché al cambiar
                    sampleDataCache[i] = sampleDataCache[i] || {};
                    sampleDataCache[i].tipoMuestra = this.value;
                });
                tmCell.appendChild(tmSelect);
                row.appendChild(tmCell);

                // --- Código de Referencia ---
                const crCell = document.createElement('td');
                const crContainer = document.createElement('div');
                crContainer.id = `codigoReferenciaContainer_${i}`;
                crCell.appendChild(crContainer);
                row.appendChild(crCell);

                // --- Fecha de Toma ---
                const ftCell = document.createElement('td');
                const ftInput = document.createElement('input');
                ftInput.type = 'date';
                ftInput.className = 'form-control';
                ftInput.id = `fechaToma_${i}`;
                ftInput.value = dateStr;
                ftCell.appendChild(ftInput);
                row.appendChild(ftCell);

                // --- Número de Muestras ---
                const nmCell = document.createElement('td');
                const nmInput = document.createElement('input');
                nmInput.type = 'number';
                nmInput.className = 'form-control';
                nmInput.id = `numeroMuestras_${i}`;
                nmInput.min = '1';
                nmInput.max = '20';
                nmInput.value = '1';
                nmCell.appendChild(nmInput);
                row.appendChild(nmCell);

                // --- Análisis: botones + resumen ---
                const anCell = document.createElement('td');
                const anDiv = document.createElement('div');
                anDiv.className = 'd-flex flex-column';

                const buttonsDiv = document.createElement('div');
                buttonsDiv.className = 'd-flex gap-1 mb-1';

                const selectBtn = document.createElement('button');
                selectBtn.type = 'button';
                selectBtn.className = 'btn btn-sm btn-outline-primary';
                selectBtn.textContent = 'Seleccionar';
                selectBtn.onclick = () => openAnalisisModal(i);

                const copyBtn = document.createElement('button');
                copyBtn.type = 'button';
                copyBtn.className = 'btn btn-sm btn-outline-secondary';
                copyBtn.title = 'Copiar análisis y tipo de muestra a otra fila';
                copyBtn.textContent = 'Copiar';
                copyBtn.onclick = () => copyAnalisisTo(i);

                buttonsDiv.appendChild(selectBtn);
                buttonsDiv.appendChild(copyBtn);

                const resumen = document.createElement('div');
                resumen.id = `analisisResumen_${i}`;
                resumen.style.fontSize = '0.85em';
                resumen.style.minHeight = '1.2em';
                resumen.innerHTML = 'Ninguno';

                anDiv.appendChild(buttonsDiv);
                anDiv.appendChild(resumen);
                anCell.appendChild(anDiv);
                row.appendChild(anCell);

                // --- Observaciones (textarea) ---
                const obsCell = document.createElement('td');
                const obsTA = document.createElement('textarea');
                obsTA.className = 'form-control';
                obsTA.id = `observaciones_${i}`;
                obsTA.rows = 2;
                obsTA.placeholder = 'Observaciones...';
                obsCell.appendChild(obsTA);
                row.appendChild(obsCell);

                tableBody.appendChild(row);

                // --- Cargar tipos de muestra y restaurar datos ---
                cargarTiposMuestra(`tipoMuestra_${i}`, i).then(() => {
                    const cache = sampleDataCache[i];
                    if (cache) {
                        // Restaurar tipo de muestra
                        if (cache.tipoMuestra) {
                            tmSelect.value = cache.tipoMuestra;
                            // Disparar cambio para cargar código de referencia
                            setTimeout(() => {
                                tmSelect.dispatchEvent(new Event('change'));
                                // Restaurar otros campos después de que se cargue el código
                                setTimeout(() => {
                                    if (cache.codigoReferencia) {
                                        const boxes = document.querySelectorAll(`#codigoReferenciaBoxes_${i} input`);
                                        const digits = cache.codigoReferencia.split('');
                                        boxes.forEach((box, idx) => box.value = digits[idx] || '');
                                        const hidden = document.getElementById(`codigoReferenciaValue_${i}`);
                                        if (hidden) hidden.value = cache.codigoReferencia;
                                    }
                                    if (cache.fechaToma) document.getElementById(`fechaToma_${i}`).value = cache.fechaToma;
                                    if (cache.numeroMuestras) document.getElementById(`numeroMuestras_${i}`).value = cache.numeroMuestras;
                                    if (cache.observaciones) document.getElementById(`observaciones_${i}`).value = cache.observaciones;
                                    if (cache.analisisSeleccionados) {
                                        sampleDataCache[i].analisisSeleccionados = cache.analisisSeleccionados;
                                        updateAnalisisResumen(i); // actualiza el resumen visual
                                    }
                                }, 300);
                            }, 50);
                        }
                    }
                });
            }

            totalSamples = count;
        }
        function restoreRowData(index, data) {
            if (!data) return;
            const tmSelect = document.getElementById(`tipoMuestra_${index}`);
            if (tmSelect && data.tipoMuestra) {
                tmSelect.value = data.tipoMuestra;
                // Disparar cambio para cargar código de referencia y análisis
                const event = new Event('change');
                tmSelect.dispatchEvent(event);
                // Restaurar otros campos con delay
                setTimeout(() => {
                    if (data.codigoReferencia) {
                        const boxes = document.querySelectorAll(`#codigoReferenciaBoxes_${index} input`);
                        const digits = data.codigoReferencia.split('');
                        boxes.forEach((box, i) => box.value = digits[i] || '');
                        document.getElementById(`codigoReferenciaValue_${index}`).value = data.codigoReferencia;
                    }
                    if (data.fechaToma) document.getElementById(`fechaToma_${index}`).value = data.fechaToma;
                    if (data.numeroMuestras) document.getElementById(`numeroMuestras_${index}`).value = data.numeroMuestras;
                    if (data.observaciones) document.getElementById(`observaciones_${index}`).value = data.observaciones;
                    if (data.analisisSeleccionados) {
                        sampleDataCache[index] = sampleDataCache[index] || {};
                        sampleDataCache[index].analisisSeleccionados = data.analisisSeleccionados;
                        // Forzar actualización del resumen
                        updateAnalisisResumen(index);
                    }
                }, 300);
            }
        }
        // Actualiza el resumen de análisis visualmente
        function updateAnalisisResumen(sampleIndex) {
            const cache = sampleDataCache[sampleIndex] || {};
            const codigos = cache.analisisSeleccionados || [];
            if (codigos.length === 0) {
                document.getElementById(`analisisResumen_${sampleIndex}`).innerHTML = 'Ninguno';
                return;
            }
            // En entorno real, cargar nombres desde API. Aquí usamos placeholder.
            const nombres = codigos.map(c => `Análisis ${c}`).join(', ');
            document.getElementById(`analisisResumen_${sampleIndex}`).innerHTML = `<small>${nombres}</small>`;
        }
        // Función de copia mejorada (no permite copiar a sí misma)
        // Función de copia mejorada (no permite copiar a sí misma)
        function copyAnalisisTo(sourceIndex) {
            const sourceCache = sampleDataCache[sourceIndex];
            if (!sourceCache || !sourceCache.analisisSeleccionados?.length) {
                alert('No hay análisis seleccionados en la fila origen.');
                return;
            }

            const targetStr = prompt('Copiar a fila (1, 2, 3...):');
            if (!targetStr) return;
            const targetIndex = parseInt(targetStr) - 1;

            if (targetIndex < 0 || targetIndex >= totalSamples) {
                alert('Fila no válida.');
                return;
            }
            if (targetIndex === sourceIndex) {
                alert('No se puede copiar a la misma fila.');
                return;
            }

            const srcSelect = document.getElementById(`tipoMuestra_${sourceIndex}`);
            const tgtSelect = document.getElementById(`tipoMuestra_${targetIndex}`);
            const srcTipo = srcSelect?.value || '';
            const tgtTipo = tgtSelect?.value || '';

            // === Solo cambiar el tipo de muestra si es diferente ===
            if (srcTipo && srcTipo !== tgtTipo) {
                tgtSelect.value = srcTipo;
                // Disparar 'change' solo si el tipo cambió
                tgtSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // === Actualizar el caché de análisis en la fila destino ===
            sampleDataCache[targetIndex] = sampleDataCache[targetIndex] || {};
            sampleDataCache[targetIndex].analisisSeleccionados = [...sourceCache.analisisSeleccionados];
            // Opcional: guardar tipo de muestra en caché para consistencia
            sampleDataCache[targetIndex].tipoMuestra = srcTipo;

            // === Copiar el resumen visual de análisis (siempre) ===
            const srcResumen = document.getElementById(`analisisResumen_${sourceIndex}`);
            const tgtResumen = document.getElementById(`analisisResumen_${targetIndex}`);
            if (srcResumen && tgtResumen) {
                tgtResumen.innerHTML = srcResumen.innerHTML;
            }

            alert(`✅ Análisis copiados a la fila ${targetIndex + 1}.`);
        }
        // === Función para abrir el modal de observaciones ===
        function openObservacionesModal(sampleIndex) {
            const observacionesInput = document.getElementById('observacionesInput');
            const currentVal = document.getElementById(`observaciones_${sampleIndex}`).value;
            observacionesInput.value = currentVal;

            const saveButton = document.getElementById('saveObservaciones');
            saveButton.onclick = function () {
                const val = observacionesInput.value;
                document.getElementById(`observaciones_${sampleIndex}`).value = val;
                bootstrap.Modal.getInstance(document.getElementById('observacionesModal')).hide();
            };

            const modal = new bootstrap.Modal(document.getElementById('observacionesModal'));
            modal.show();
        }

        // === Función para abrir el modal de análisis ===
        window.openAnalisisModal = async function (sampleIndex) {
            const tipoMuestraSelect = document.getElementById(`tipoMuestra_${sampleIndex}`);
            if (!tipoMuestraSelect || tipoMuestraSelect.value === "") {
                alert("Primero seleccione un tipo de muestra.");
                return;
            }
            const tipoId = tipoMuestraSelect.value;

            document.getElementById("analisisModalSampleTitle").textContent = `Solicitud #${sampleIndex + 1}`;

            try {
                const res = await fetch(`get_config_muestra.php?tipo=${tipoId}`);
                const data = await res.json();
                if (data.error) throw new Error(data.error);

                const analisisPorPaquete = {};
                const analisisSinPaquete = [];
                data.analisis.forEach(a => {
                    if (a.paquete) {
                        if (!analisisPorPaquete[a.paquete]) analisisPorPaquete[a.paquete] = [];
                        analisisPorPaquete[a.paquete].push(a);
                    } else {
                        analisisSinPaquete.push(a);
                    }
                });

                const cache = sampleDataCache[sampleIndex] || {};
                const selectedAnalisisCodigos = new Set(
                    (cache.analisisSeleccionados || []).map(String)
                );
                console.log(selectedAnalisisCodigos);

                let html = '';

                // Estilo común para todos los paquetes
                html += '<style>.analisis-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 8px; margin-left: 24px; }</style>';

                data.paquetes.forEach(p => {
                    const analisisDelPaquete = analisisPorPaquete[p.codigo] || [];
                    const todosAnalisisSeleccionados = analisisDelPaquete.length > 0 &&
                        analisisDelPaquete.every(a => selectedAnalisisCodigos.has(String(a.codigo)));

                    html += `
    <div class="mb-4">
      <div class="form-check">
        <input class="form-check-input paquete-checkbox" 
               type="checkbox" 
               data-paquete-id="${p.codigo}"
               ${todosAnalisisSeleccionados ? 'checked' : ''}>
        <label class="form-check-label fw-bold">${p.nombre}</label>
      </div>
      ${analisisDelPaquete.length ? `
        <div class="analisis-grid">
          ${analisisDelPaquete.map(a => `
            <div class="form-check">
              <input class="form-check-input analisis-individual" 
                     type="checkbox" 
                     id="analisis_${a.codigo}_${sampleIndex}"
                     value="${a.codigo}" 
                     data-nombre="${a.nombre}"
                     ${selectedAnalisisCodigos.has(String(a.codigo)) ? 'checked' : ''}>
              <label class="form-check-label" for="analisis_${a.codigo}_${sampleIndex}">${a.nombre}</label>
            </div>
          `).join('')}
        </div>
      ` : ''}
    </div>
  `;
                });

                // Análisis sin paquete (también en 3 columnas)
                if (analisisSinPaquete.length > 0) {
                    html += `
    <div class="mt-4 pt-3 border-top">
      <h6 class="fw-bold">Otros análisis</h6>
      <div class="analisis-grid">
        ${analisisSinPaquete.map(a => `
          <div class="form-check">
            <input class="form-check-input analisis-individual" 
                   type="checkbox" 
                   id="analisis_sueltos_${a.codigo}_${sampleIndex}"
                   value="${a.codigo}" 
                   data-nombre="${a.nombre}"
                   ${selectedAnalisisCodigos.has(String(a.codigo)) ? 'checked' : ''}>
            <label class="form-check-label" for="analisis_sueltos_${a.codigo}_${sampleIndex}">${a.nombre}</label>
          </div>
        `).join('')}
      </div>
    </div>
  `;
                }

                document.getElementById("analisisModalBody").innerHTML = html;

                // ✅ Listener para paquetes (marcar/desmarcar análisis)
                document.querySelectorAll("#analisisModal .paquete-checkbox").forEach(cb => {
                    cb.addEventListener("change", function () {
                        const paqueteId = this.dataset.paqueteId;
                        const isChecked = this.checked;
                        const analisisDelPaquete = analisisPorPaquete[paqueteId] || [];
                        analisisDelPaquete.forEach(a => {
                            const analisisCb = document.querySelector(`#analisisModal .analisis-individual[value="${a.codigo}"]`);
                            if (analisisCb) analisisCb.checked = isChecked;
                        });
                    });
                });

                // ✅ Guardar solo análisis
                document.getElementById("analisisModalSaveBtn").onclick = () => {
                    const selectedAnalisis = Array.from(
                        document.querySelectorAll("#analisisModal .analisis-individual:checked")
                    ).map(cb => ({
                        codigo: cb.value,
                        nombre: cb.dataset.nombre
                    }));


                    const resumenHtml = generateAnalisisResumen(selectedAnalisis, data.paquetes, analisisPorPaquete);
                    document.getElementById(`analisisResumen_${sampleIndex}`).innerHTML = resumenHtml;

                    // ✅ Guardar solo análisis en caché
                    sampleDataCache[sampleIndex] = sampleDataCache[sampleIndex] || {};
                    sampleDataCache[sampleIndex].analisisSeleccionados = selectedAnalisis.map(a => a.codigo);
                    console.log(sampleDataCache[sampleIndex].analisisSeleccionados);
                    bootstrap.Modal.getInstance(document.getElementById('analisisModal')).hide();
                };

                const modal = new bootstrap.Modal(document.getElementById('analisisModal'));
                modal.show();

            } catch (err) {
                console.error(err);
                document.getElementById("analisisModalBody").innerHTML = "<p class='text-danger'>Error al cargar análisis.</p>";
            }
        };
        function saveAnalisisToSample(sampleIndex, selectedAnalisis) {
            // Guardar en caché
            sampleDataCache[sampleIndex] = sampleDataCache[sampleIndex] || {};
            sampleDataCache[sampleIndex].analisisSeleccionados = selectedAnalisis;

            // Mostrar resumen en el bloque de muestra
            const resumenEl = document.querySelector(`.sample-item[data-sample-index="${sampleIndex}"] .analisis-resumen`);
            if (!resumenEl) {
                // Crear contenedor de resumen si no existe
                const bloque = document.querySelector(`.sample-item[data-sample-index="${sampleIndex}"]`);
                const resumen = document.createElement("div");
                resumen.className = "analisis-resumen mt-2 text-sm text-gray-700";
                resumen.style.minHeight = "1.5em";
                bloque.querySelector("#paquetesContainer_" + sampleIndex).after(resumen);
            }

            if (selectedAnalisis.length === 0) {
                document.querySelector(`.analisis-resumen`).textContent = "Ningún análisis seleccionado.";
            } else {
                // Agrupar por paquetes (asumiendo que ya tienes los datos de paquetes)
                // Para simplificar, mostramos solo nombres
                const nombres = selectedAnalisis.map(a => a.nombre).join(", ");
                document.querySelector(`.analisis-resumen`).innerHTML = `<strong>Seleccionados:</strong> ${nombres}`;
            }
        }
        // === Función para generar el resumen de análisis ===
        function generateAnalisisResumen(selectedAnalisis, paquetes, analisisPorPaquete) {
            if (selectedAnalisis.length === 0) {
                return 'Ninguno';
            }

            const analisisPorNombrePaquete = {};
            selectedAnalisis.forEach(analisis => {
                // Buscar a qué paquete pertenece
                let paqueteNombre = 'Sin paquete';
                for (const [paqueteId, analisisList] of Object.entries(analisisPorPaquete)) {
                    if (analisisList.some(a => String(a.codigo) === String(analisis.codigo))) {
                        const paquete = paquetes.find(p => p.codigo === paqueteId);
                        if (paquete) {
                            paqueteNombre = paquete.nombre;
                        }
                        break;
                    }
                }

                if (!analisisPorNombrePaquete[paqueteNombre]) {
                    analisisPorNombrePaquete[paqueteNombre] = [];
                }
                analisisPorNombrePaquete[paqueteNombre].push(analisis.nombre);
            });

            let resumenHtml = '';
            for (const [nombrePaquete, listaAnalisis] of Object.entries(analisisPorNombrePaquete)) {
                resumenHtml += `<strong>${nombrePaquete}:</strong> ${listaAnalisis.join(', ')}<br>`;
            }

            return resumenHtml;
        }

        // === Función para copiar análisis de una fila a otra ===
        function copyAnalisisFromRow(sourceIndex) {
            const sourceAnalisis = document.getElementById(`analisisSelected_${sourceIndex}`);
            if (!sourceAnalisis) {
                alert('No hay análisis seleccionados en la fila fuente.');
                return;
            }

            const targetIndex = prompt('Ingrese el número de la fila destino (1, 2, 3...):');
            if (!targetIndex || isNaN(targetIndex) || targetIndex < 1 || targetIndex > totalSamples) {
                alert('Por favor ingrese un número de fila válido.');
                return;
            }

            const targetIndexInt = parseInt(targetIndex) - 1; // Convertir a índice base 0
            const targetResumen = document.getElementById(`analisisResumen_${targetIndexInt}`);

            // Copiar el resumen y el campo oculto
            targetResumen.innerHTML = sourceAnalisis.parentNode.innerHTML;

            // Actualizar los IDs de los elementos copiados
            const targetHiddenField = targetResumen.querySelector('input[type="hidden"]');
            if (targetHiddenField) {
                targetHiddenField.id = `analisisSelected_${targetIndexInt}`;
                targetHiddenField.name = `analisisSelected_${targetIndexInt}`;
            }

            alert('Análisis copiados correctamente.');
        }

        // === Función para eliminar una fila ===
        function deleteRow(index) {
            if (confirm('¿Está seguro de que desea eliminar esta fila?')) {
                const row = document.getElementById(`sampleRow_${index}`);
                if (row) {
                    row.remove();
                    // Reordenar los índices
                    const rows = document.querySelectorAll('#samplesTableBody tr');
                    rows.forEach((r, i) => {
                        r.id = `sampleRow_${i}`;
                        // Actualizar los IDs de los elementos dentro de la fila
                        r.querySelectorAll('input, select, button').forEach(el => {
                            const oldId = el.id;
                            if (oldId) {
                                const newId = oldId.replace(/_\d+$/, `_${i}`);
                                el.id = newId;
                            }
                        });
                    });
                    totalSamples--;
                    document.getElementById('numeroSolicitudes').value = totalSamples;
                }
            }
        }

        // === Función para generar la previsualización HTML ===
        function generatePreviewHTML(formData) {
            const numeroSolicitudes = parseInt(formData.get("numeroSolicitudes"));
            const fechaEnvio = formData.get("fechaEnvio");
            const horaEnvio = formData.get("horaEnvio");
            const laboratorioCodigo = formData.get("laboratorio");
            const empresaTransporte = formData.get("empresa_transporte");
            const autorizadoPor = formData.get("autorizadoPor");
            const usuarioRegistrador = formData.get("usuario_registrador") || "user";
            const usuarioResponsable = formData.get("usuario_responsable");

            let laboratorioNombre = "No disponible";
            const laboratorioSelect = document.getElementById("laboratorio");
            if (laboratorioSelect) {
                const selectedOption =
                    laboratorioSelect.options[laboratorioSelect.selectedIndex];
                laboratorioNombre = selectedOption
                    ? selectedOption.text
                    : "No seleccionado";
            }

            let previewHTML = `
                <div class="pdf-preview">
                    <div class="pdf-header">
                        REGISTRO DE ENVÍO DE MUESTRAS
                    </div>
                    <div style="margin: 15px 0;">
                        <div><strong>Fecha de envío:</strong> ${fechaEnvio} - Hora: ${horaEnvio.substring(0, 5)}</div>
                        <div><strong>Código de envío:</strong> <span id="resumenCodigoEnvio"></span></div>
                        <div><strong>Laboratorio:</strong> ${laboratorioNombre}</div>
                        <div><strong>Empresa de transporte:</strong> ${empresaTransporte}</div>
                        <div><strong>Autorizado por:</strong> ${autorizadoPor}</div>
                        <div><strong>Usuario Registrador:</strong> ${usuarioRegistrador}</div>
                        <div><strong>Usuario Responsable:</strong> ${usuarioResponsable}</div>
                        <div><strong>Número de Solicitudes:</strong> ${numeroSolicitudes}</div>
                    </div>
                    <table class="pdf-table">
                        <thead>
                            <tr>
                                <th>Cód. Ref.</th>
                                <th>Toma de muestra</th>
                                <th>N° muestras</th>
                                <th>TIPO DE ANÁLISIS</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            for (let i = 0; i < numeroSolicitudes; i++) {
                const tipoMuestraRadio = document.getElementById(`tipoMuestra_${i}`);
                const tipoMuestraNombre = tipoMuestraRadio ? tipoMuestraRadio.options[tipoMuestraRadio.selectedIndex]?.text : "No seleccionado";

                const fechaTomaInput = document.getElementById(`fechaToma_${i}`);
                const fechaToma = fechaTomaInput ? fechaTomaInput.value : "-";

                const numeroMuestras = formData.get(`numeroMuestras_${i}`) || "1";

                const codigoRefBoxes = document.querySelectorAll(`#codigoReferenciaBoxes_${i} input`);
                const codigoRef = Array.from(codigoRefBoxes)
                    .map((box) => box.value || "")
                    .join("");

                const observacionesTextarea = document.getElementById(`observaciones_${i}`);
                const observaciones = observacionesTextarea ? observacionesTextarea.value : "Ninguna";

                const analisisSelected = document.getElementById(`analisisSelected_${i}`);
                let analisisHtml = "Ninguno";
                if (analisisSelected) {
                    const selectedAnalisis = JSON.parse(analisisSelected.value);
                    if (selectedAnalisis.length > 0) {
                        // Agrupar por paquetes (similar a la lógica del PHP)
                        const analisisPorPaquete = {};
                        const sinPaquete = [];

                        // Necesitamos cargar los datos de los paquetes y análisis
                        // Esto es una simplificación, en un caso real se haría una llamada al servidor
                        // Por ahora, asumimos que tenemos los datos disponibles
                        // En un escenario real, esto se haría con una llamada AJAX a get_config_muestra.php

                        // Simulación de agrupación
                        const paquetesMap = {}; // Aquí debería estar el mapa de paquetes
                        const analisisMap = {}; // Aquí debería estar el mapa de análisis

                        // Para este ejemplo, simplemente mostramos los nombres
                        analisisHtml = selectedAnalisis.map(a => a.nombre).join(", ");
                    }
                }

                previewHTML += `
                            <tr>
                                <td>${codigoRef}</td>
                                <td>${fechaToma}</td>
                                <td>${numeroMuestras}</td>
                                <td>${analisisHtml}</td>
                                <td>${observaciones}</td>
                            </tr>
                `;
            }

            previewHTML += `
                        </tbody>
                    </table>
                    <div class="pdf-footer">
                        <table>
                            <tr><td>Empresa:</td><td>COMITÉ 4</td></tr>
                            <tr><td>Autorizado por:</td><td>Dr. Julio Alvan</td></tr>
                        </table>
                    </div>
                </div>
            `;

            return previewHTML;
        }

        // === Función para mostrar la previsualización ===
        function showPreview() {
            const formData = new FormData(document.getElementById("sampleForm"));
            const previewContent = generatePreviewHTML(formData);

            document.getElementById("previewContent").innerHTML = previewContent;
            document.getElementById("resumenCodigoEnvio").textContent = document.getElementById("codigoEnvio").value;

            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        }

        // === Función para generar el PDF ===
        document.getElementById('generatePdf').addEventListener('click', function () {
            const formData = new FormData(document.getElementById("sampleForm"));
            const codigoEnvio = document.getElementById("codigoEnvio").value;
            window.open(`generar_pdf.php?codigo=${encodeURIComponent(codigoEnvio)}`, '_blank');
        });

        // === Event listener para el botón de configuración ===
        /*document.getElementById('configButton').addEventListener('click', function () {
            const modal = new bootstrap.Modal(document.getElementById('configModal'));
            modal.show();
        });*/

        // === Event listener para aplicar la configuración ===
        /*document.getElementById('applyConfig').addEventListener('click', function () {
            const viewMode = document.getElementById('viewMode').value;
            const typeMuestraDisplay = document.getElementById('typeMuestraDisplay').value;

            // Aplicar la configuración
            if (viewMode === 'vertical') {
                // Cambiar la tabla a vista vertical (esto requeriría una reestructuración más compleja)
                alert('La vista vertical no está implementada en este ejemplo.');
            } else {
                // Mantener la vista horizontal
            }

            if (typeMuestraDisplay === 'allTypes') {
                // Mostrar todos los tipos de muestra en la primera celda
                // Esto requeriría una reestructuración de la tabla
                alert('Mostrar todos los tipos de muestra no está implementado en este ejemplo.');
            }

            bootstrap.Modal.getInstance(document.getElementById('configModal')).hide();
        });*/

        // === Event listener para el número de solicitudes ===
        /*function handleNumeroSolicitudesChange() {
            let count = parseInt(this.value, 10) || 0;
            const max = 20;
            const min = 1;

            // Normalizar el valor
            if (count < min) {
                count = min;
                this.value = ""; // o this.value = ""; si permites vacío
                document.getElementById('samples-table-container').classList.add('hidden');
                tableBody.innerHTML = '';
                totalSamples = 0;
                return;
            }

            if (count > max) {
                count = max;
                this.value = count; // corregir visualmente
            } else {
                this.value = count; // asegurar que sea número, no "2a"
            }

            // Mostrar tabla
            document.getElementById('samples-table-container').classList.remove('hidden');

            // Guardar estado actual antes de regenerar
            for (let i = 0; i < totalSamples; i++) {
                persistentSampleCache[i] = extractRowData(i);
            }

            // Actualizar total y regenerar
            totalSamples = count;
            generateTableRows(count);
        }*/
        function handleNumeroSolicitudesChange() {
            let count = parseInt(this.value, 10) || 0;
            const max = 20;
            const min = 1;

            if (count < min) {
                this.value = "";
                document.getElementById('samples-table-container').classList.add('hidden');
                tableBody.innerHTML = '';
                totalSamples = 0;
                return;
            }

            if (count > max) {
                count = max;
                this.value = count;
            } else {
                this.value = count;
            }

            totalSamples = count;
            document.getElementById('samples-table-container').classList.remove('hidden');
            generateTableRows(count); // ya restaura desde sampleDataCache
        }

        function extractRowData(index) {
            return {
                tipoMuestra: document.getElementById(`tipoMuestra_${index}`).value,
                codigoReferencia: document.getElementById(`codigoReferenciaValue_${index}`).value,
                fechaToma: document.getElementById(`fechaToma_${index}`).value,
                numeroMuestras: document.getElementById(`numeroMuestras_${index}`).value,
                analisisSeleccionados: sampleDataCache[index]?.analisisSeleccionados || [],
                observaciones: document.getElementById(`observaciones_${index}`).value
            };
        }
        // === Función para limpiar las muestras ===
        function clearSamples() {
            tableBody.innerHTML = '';
            totalSamples = 0;
        }

        // === Inicialización ===
        if (numeroInput.value) {
            numeroInput.dispatchEvent(new Event("input"));
        }
        /* numeroInput.addEventListener("change", function () {
             // Limpiar valor: eliminar no-dígitos y normalizar
             let val = this.value.trim();
             if (val === "") {
                 this.value = "";
                 handleNumeroSolicitudesChange.call(this);
                 return;
             }
             // Extraer solo dígitos
             val = val.replace(/\D/g, "");
             if (val === "") {
                 this.value = "";
                 handleNumeroSolicitudesChange.call(this);
                 return;
             }
             let num = parseInt(val, 10);
             const max = 20;
             if (num > max) num = max;
             this.value = num;
             handleNumeroSolicitudesChange.call(this);
         });*/
        numeroInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                this.blur(); // dispara 'change'
            }
        });
        // Escuchar tanto 'input' como 'change'
        numeroInput.addEventListener("input", handleNumeroSolicitudesChange);
        numeroInput.addEventListener("change", handleNumeroSolicitudesChange);
        // === Función para manejar el envío del formulario ===
        window.handleSampleSubmit = function (event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById("sampleForm"));
            generateSummary(formData);
            document.getElementById("confirmModal").style.display = "flex";
        };

        // === Función para generar el resumen ===
        function generateSummary(formData) {
            const numeroSolicitudes = parseInt(formData.get("numeroSolicitudes")) || 0;
            const fechaEnvio = formData.get("fechaEnvio") || "-";
            const horaEnvio = formData.get("horaEnvio") || "-";
            const codigoEnvio = document.getElementById("codigoEnvio")?.value || "Pendiente";

            const laboratorioSelect = document.getElementById("laboratorio");
            const laboratorioNombre = laboratorioSelect?.selectedOptions[0]?.text || "No seleccionado";

            const empresaSelect = document.getElementById("empresa_transporte");
            const empresaNombre = empresaSelect?.selectedOptions[0]?.text || "No seleccionado";

            const responsableEnvio = formData.get("usuario_responsable") || "No especificado";
            const autorizadoPor = formData.get("autorizado_por") || "No especificado";

            let summaryHTML = `
    <div style="padding: 10px; font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; background: white;">
        <!-- === CABECERA IDÉNTICA AL PDF === -->
        <table width="100%" style="border-collapse: collapse; border: 1px solid #000; margin-bottom: 15px;">
            <tr>
                <td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 12px;">
                    <img src="logo.png" style="height: 20px; vertical-align: top;"> GRANJA RINCONADA DEL SUR S.A.
                </td>
                <td style="width: 60%; text-align: center; padding: 5px; background-color: #6c5b7b; color: white; font-weight: bold; font-size: 14px;">
                    REGISTRO DE ENVÍO DE MUESTRAS
                </td>
                <td style="width: 20%; background-color: #fff;"></td>
            </tr>
        </table>

        <!-- === DATOS GENERALES EN DOS COLUMNAS, FORMATO PDF === -->
        <table style="border-collapse: collapse; width: 100%; font-size: 10px; margin-bottom: 15px; line-height: 1.6;">
            <tr>
                <!-- Columna 1 -->
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <table style="border-collapse: collapse; width: 100%;">
                        <tr>
                            <td style="padding: 2px 0; vertical-align: top; text-align: left; width: 45%; white-space: nowrap;"><strong>Fecha de envío</strong></td>
                            <td style="padding: 2px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>
                            <td style="padding: 2px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">${fechaEnvio}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0; vertical-align: top; text-align: left; width: 45%; white-space: nowrap;"><strong>Hora de envío</strong></td>
                            <td style="padding: 2px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>
                            <td style="padding: 2px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">${horaEnvio.substring(0, 8)}</td>
                        </tr>
                    </table>
                </td>

                <!-- Columna 2 -->
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <table style="border-collapse: collapse; width: 100%;">
                        <tr>
                            <td style="padding: 2px 0; vertical-align: top; text-align: left; width: 45%; white-space: nowrap;"><strong>Código de envío</strong></td>
                            <td style="padding: 2px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>
                            <td style="padding: 2px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">${codigoEnvio}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0; vertical-align: top; text-align: left; width: 45%; white-space: nowrap;"><strong>Laboratorio</strong></td>
                            <td style="padding: 2px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>
                            <td style="padding: 2px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">${laboratorioNombre}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- === TABLA DE DETALLE === -->
        <table style="border-collapse: collapse; width: 100%; font-size: 8px; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white;">Cód. Ref.</th>
                    <th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white;">Toma de muestra</th>
                    <th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white; height:80px; vertical-align:middle;">
                        <div style="transform: rotate(-90deg); transform-origin: center;">N° muestras</div>
                    </th>
    `;

            // Columnas de tipos de muestra (rotadas)
            allTiposMuestra.forEach(tm => {
                summaryHTML += `
                    <th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white; height:80px; vertical-align:middle;">
                        <div style="transform: rotate(-90deg); transform-origin: center;">${tm.nombre}</div>
                    </th>
        `;
            });

            summaryHTML += `
                    <th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white;">TIPO DE ANÁLISIS</th>
                    <th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white;">Observaciones</th>
                </tr>
            </thead>
            <tbody>
    `;

            // Filas de solicitudes
            for (let i = 0; i < numeroSolicitudes; i++) {
                const tipoMuestraSelect = document.getElementById(`tipoMuestra_${i}`);
                const tipoMuestraCodigo = tipoMuestraSelect?.value || "";
                const fechaToma = document.getElementById(`fechaToma_${i}`)?.value || "-";
                const numeroMuestras = document.getElementById(`numeroMuestras_${i}`)?.value || "1";
                const codigoRef = document.getElementById(`codigoReferenciaValue_${i}`)?.value || "";
                const observaciones = document.getElementById(`observaciones_${i}`)?.value || "Ninguna";
                const analisisResumenEl = document.getElementById(`analisisResumen_${i}`);
                const analisisResumen = analisisResumenEl?.innerHTML || "Ninguno";

                summaryHTML += `<tr>`;
                summaryHTML += `<td style="border:1px solid #000; padding:3px; text-align:center;">${codigoRef}</td>`;
                summaryHTML += `<td style="border:1px solid #000; padding:3px; text-align:center;">${fechaToma}</td>`;
                summaryHTML += `<td style="border:1px solid #000; padding:3px; text-align:center;">${numeroMuestras}</td>`;

                allTiposMuestra.forEach(tm => {
                    const mark = (tm.codigo === tipoMuestraCodigo) ? 'x' : '';
                    summaryHTML += `<td style="border:1px solid #000; padding:3px; text-align:center;">${mark}</td>`;
                });

                summaryHTML += `<td style="border:1px solid #000; padding:3px; vertical-align:top; white-space:pre-wrap; word-break:break-word;">${analisisResumen}</td>`;
                summaryHTML += `<td style="border:1px solid #000; padding:3px; vertical-align:top; white-space:pre-wrap; word-break:break-word;">${observaciones}</td>`;
                summaryHTML += `</tr>`;
            }

            summaryHTML += `
            </tbody>
        </table>

        <!-- === PIE IDÉNTICO AL PDF === -->
        <div style="margin-top:20px; font-size:10px; text-align:center;">
            <table style="border-collapse: collapse; width: 60%; margin: 0 auto; font-size:10px; line-height:1.5;">
                <tr>
                    <td style="padding: 3px 0; vertical-align: top; text-align: left; width: 40%; white-space: nowrap;"><strong>Responsable de envío</strong></td>
                    <td style="padding: 3px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>
                    <td style="padding: 3px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">${responsableEnvio}</td>
                </tr>
                <tr>
                    <td style="padding: 3px 0; vertical-align: top; text-align: left; width: 40%; white-space: nowrap;"><strong>Empresa</strong></td>
                    <td style="padding: 3px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>
                    <td style="padding: 3px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">${empresaNombre}</td>
                </tr>
                <tr>
                    <td style="padding: 3px 0; vertical-align: top; text-align: left; width: 40%; white-space: nowrap;"><strong>Autorizado por</strong></td>
                    <td style="padding: 3px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>
                    <td style="padding: 3px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">${autorizadoPor}</td>
                </tr>
            </table>
        </div>
    </div>
    `;

            document.getElementById("summaryContent").innerHTML = summaryHTML;
        }
        /*function generateSummary(formData) {
            const numeroSolicitudes = parseInt(formData.get("numeroSolicitudes")) || 0;
            const fechaEnvio = formData.get("fechaEnvio") || "-";
            const horaEnvio = formData.get("horaEnvio") || "-";
            const codigoEnvio = document.getElementById("codigoEnvio")?.value || "Pendiente";
            const laboratorioSelect = document.getElementById("laboratorio");
            const laboratorioNombre = laboratorioSelect?.selectedOptions[0]?.text || "No seleccionado";
            const autorizadoPor = formData.get("autorizado_por") || "No especificado";


            const empresaSelect = document.getElementById("empresa_transporte");
            const empresaNombre = empresaSelect?.selectedOptions[0]?.text || "No seleccionado";
            let summaryHTML = `
  <div style="padding: 10px;">
    <div style="font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; background: white; padding: 15px; border: 1px solid #ccc; border-radius: 6px;">
      <!-- Cabecera -->
      <table width="100%" style="border-collapse: collapse; border: 1px solid #000; margin-bottom: 15px;">
        <tr>
          <td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 12px;">
           <img src="logo.png" style="height: 20px; vertical-align: top;"> GRANJA RINCONADA DEL SUR S.A.
          </td>
          <td style="width: 60%; text-align: center; padding: 5; background-color: #6c5b7b; color: white; font-weight: bold; font-size: 14px;">
            REGISTRO DE ENVÍO DE MUESTRAS
          </td>
          <td style="width: 20%; background-color: #fff;"></td>
        </tr>
      </table>

      <!-- Datos generales -->
      <div style="font-size: 12px; font-weight: bold; margin-bottom: 15px; line-height: 1.5;">
        Fecha de envío: ${fechaEnvio} - Hora: ${horaEnvio.substring(0, 5)}<br>
        Código de envío: <strong>${codigoEnvio}</strong><br>
        Laboratorio: ${laboratorioNombre}
      </div>

      <!-- Tabla de solicitudes -->
      <table style="border-collapse: collapse; width: 100%; font-size: 12px; margin-bottom: 20px;">
        <thead>
          <tr>
            <th style="border:1px solid #000; padding:6px; text-align:center; background-color:#6c5b7b; color:white;">Cód. Ref.</th>
            <th style="border:1px solid #000; padding:6px; text-align:center; background-color:#6c5b7b; color:white;">Toma de muestra</th>
            <th style="border:1px solid #000; padding:6px; text-align:center; background-color:#6c5b7b; color:white; writing-mode: vertical-rl; text-orientation: mixed; width: 50px;">N° muestras</th>
  `;

            // === Columnas: un th por cada tipo de muestra ===
            allTiposMuestra.forEach(tm => {
                summaryHTML += `
      <th style="border:1px solid #000; padding:6px; text-align:center; background-color:#6c5b7b; color:white; writing-mode: vertical-rl; text-orientation: mixed; width: 40px;">${tm.nombre}</th>
    `;
            });

            summaryHTML += `
            <th style="border:1px solid #000; padding:6px; text-align:center; background-color:#6c5b7b; color:white;">TIPO DE ANÁLISIS</th>
            <th style="border:1px solid #000; padding:6px; text-align:center; background-color:#6c5b7b; color:white;">Observaciones</th>
          </tr>
        </thead>
        <tbody>
  </div>`;

            // === Filas: una por solicitud ===
            for (let i = 0; i < numeroSolicitudes; i++) {
                const tipoMuestraSelect = document.getElementById(`tipoMuestra_${i}`);
                const tipoMuestraCodigo = tipoMuestraSelect?.value || "";
                const fechaToma = document.getElementById(`fechaToma_${i}`)?.value || "-";
                const numeroMuestras = document.getElementById(`numeroMuestras_${i}`)?.value || "1";
                const codigoRef = document.getElementById(`codigoReferenciaValue_${i}`)?.value || "";
                const observaciones = document.getElementById(`observaciones_${i}`)?.value || "Ninguna";
                const analisisResumenEl = document.getElementById(`analisisResumen_${i}`);
                const analisisResumen = analisisResumenEl?.innerHTML || "Ninguno";

                summaryHTML += `<tr>`;
                summaryHTML += `<td style="border:1px solid #000; padding:6px; text-align:center; background-color:#fff;">${codigoRef}</td>`;
                summaryHTML += `<td style="border:1px solid #000; padding:6px; text-align:center; background-color:#fff;">${fechaToma}</td>`;
                summaryHTML += `<td style="border:1px solid #000; padding:6px; text-align:center; background-color:#fff;">${numeroMuestras}</td>`;

                // === Marcar "x" en el tipo de muestra correspondiente ===
                allTiposMuestra.forEach(tm => {
                    const mark = (tm.codigo === tipoMuestraCodigo) ? 'x' : '';
                    summaryHTML += `<td style="border:1px solid #000; padding:6px; text-align:center; background-color:#fff;">${mark}</td>`;
                });

                summaryHTML += `<td style="border:1px solid #000; padding:6px; vertical-align:top; background-color:#fff;">${analisisResumen}</td>`;
                summaryHTML += `<td style="border:1px solid #000; padding:6px; vertical-align:top; white-space: pre-wrap; word-break: break-word; background-color:#fff;">${observaciones}</td>`;
                summaryHTML += `</tr>`;
            }

            summaryHTML += `
        </tbody>
      </table>

      <!-- Pie -->
      <div style="margin-top:20px; font-size:12px; text-align:center;">
        <table width="60%" style="border-collapse:collapse; margin:0 auto;">
          <tr>
            <td style="width:30%; padding:5px; text-align:right;">Empresa:</td>
            <td style="width:70%; border-bottom:1px solid #000; padding:5px; text-align:left;">${empresaNombre}</td>
          </tr>
          <tr>
            <td style="width:30%; padding:5px; text-align:right;">Autorizado por:</td>
            <td style="width:70%; border-bottom:1px solid #000; padding:5px; text-align:left;">${autorizadoPor}</td>
          </tr>
        </table>
      </div>
    </div>
  `;

            document.getElementById("summaryContent").innerHTML = summaryHTML;
        }*/

        // === Función para confirmar el envío ===
        window.confirmSubmit = async function (descargarPdf = false) {
            //0const formData = new FormData(document.getElementById("sampleForm"));
            // 1. Crear FormData vacío
            const formData = new FormData();

            // 2. Agregar campos fijos (del formulario principal)
            const fields = [
                'fechaEnvio', 'horaEnvio', 'laboratorio', 'empresa_transporte',
                'usuario_registrador', 'usuario_responsable', 'autorizado_por', 'numeroSolicitudes', 'codigoEnvio'
            ];

            fields.forEach(field => {
                const el = document.getElementById(field);
                if (el) formData.append(field, el.value);
            });

            // 3. Agregar campos dinámicos de cada solicitud
            for (let i = 0; i < totalSamples; i++) {
                // Fecha de toma
                const fechaToma = document.getElementById(`fechaToma_${i}`);
                if (fechaToma) formData.append(`fechaToma_${i}`, fechaToma.value);

                // Tipo de muestra
                const tipoMuestra = document.getElementById(`tipoMuestra_${i}`);
                if (tipoMuestra) formData.append(`tipoMuestra_${i}`, tipoMuestra.value);

                // Código de referencia
                const codRef = document.getElementById(`codigoReferenciaValue_${i}`);
                if (codRef) formData.append(`codigoReferenciaValue_${i}`, codRef.value);

                // Observaciones
                const obs = document.getElementById(`observaciones_${i}`);
                if (obs) formData.append(`observaciones_${i}`, obs.value);

                // Número de muestras
                const numMuestras = document.getElementById(`numeroMuestras_${i}`);
                if (numMuestras) formData.append(`numeroMuestras_${i}`, numMuestras.value);

                // Análisis (como array)
                const analisis = sampleDataCache[i]?.analisisSeleccionados || [];
                analisis.forEach(codigo => {
                    formData.append(`analisis_${i}[]`, codigo);
                });
            }
            for (const [key, value] of formData.entries()) {
                console.log(`${key}:`, value, value === '' ? '⚠️ VACÍO' : '');
            }
            try {
                const response = await fetch("guardar_muestra.php", {
                    method: "POST",
                    body: formData,
                });

                const responseText = await response.text();

                // 👁️ Ver qué devolvió el servidor (¡muy útil!)
                console.log("✅ Respuesta cruda del servidor:");
                console.log(responseText);

                // ✅ Parsear JSON manualmente
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    throw new Error("Respuesta del servidor no es JSON válido:\n\n" + responseText);
                }

                if (result.status === "success") {
                    // Cerrar modal
                    document.getElementById("confirmModal").style.display = "none";

                    // Limpiar formulario
                    document.getElementById("sampleForm").reset();
                    const input = document.getElementById("numeroSolicitudes");
                    input.value = "";
                    input.dispatchEvent(new Event("input"));
                    resetAllState();

                    // Mensaje de éxito
                    alert("✅ Registro guardado exitosamente. Código: " + result.codigoEnvio);

                    // Si el usuario eligió descargar PDF (descomenta cuando lo pruebes)
                    if (descargarPdf && result.codigoEnvio) {
                        const pdfUrl = `generar_pdf.php?codigo=${encodeURIComponent(result.codigoEnvio)}`;
                        window.open(pdfUrl, '_blank');
                    }
                } else {
                    throw new Error(result.message || result.error || "Error desconocido al guardar.");
                }
            } catch (error) {
                console.error("Error:", error);
                alert("❌ Error al guardar el registro: " + error.message);
            }
        };

        // === Función para cerrar el modal de confirmación ===
        window.closeConfirmModal = function () {
            document.getElementById("confirmModal").style.display = "none";
        };

        function resetAllState() {
            // 1. Resetear el formulario (incluye inputs, selects, textareas)
            document.getElementById("sampleForm")?.reset();

            // 2. Limpiar inputs manuales que no se resetean solos
            const numeroSolicitudes = document.getElementById("numeroSolicitudes");
            if (numeroSolicitudes) {
                numeroSolicitudes.value = "";
            }

            // 3. Reiniciar variables de estado
            currentSample = 0;
            totalSamples = 0;

            // 4. Limpiar cachés
            sampleDataCache = {};
            persistentSampleCache = {}; // si aún lo usas

            // 5. Limpiar la tabla de muestras
            const tableBody = document.getElementById("samplesTableBody");
            if (tableBody) {
                tableBody.innerHTML = '';
            }

            const tableContainer = document.getElementById("samples-table-container");
            if (tableContainer) {
                tableContainer.classList.add("hidden"); // ocultar si usas "d-none" o "hidden"
            }

            // 6. Reiniciar fechas predeterminadas (opcional, ya se hace al cargar)
            const fechaEnvio = document.getElementById("fechaEnvio");
            const horaEnvio = document.getElementById("horaEnvio");
            const today = new Date();
            const dateStr = today.toISOString().split("T")[0];
            const timeStr = today.toTimeString().split(" ")[0].substring(0, 5);
            if (fechaEnvio) fechaEnvio.value = dateStr;
            if (horaEnvio) horaEnvio.value = timeStr;

            // 7. Cargar nuevo código de envío
            loadCodigoEnvio();
        }
        document.getElementById('numeroSolicitudes').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Opcional: mover foco al siguiente campo o procesar lógica personalizada
                return false;
            }
        });
        // === Cargar el código de envío al cargar la página ===
        loadCodigoEnvio();
    </script>
</body>

</html>