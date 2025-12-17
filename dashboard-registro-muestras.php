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

        #samples-table-container .table-responsive {
            overflow-x: auto;
            min-width: 100%;
        }

        #samplesTable {
            min-width: 1200px;
            /* o más si es necesario */
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container-fluid px-4 py-6">
        <!-- VISTA REGISTRO -->
        <div id="viewRegistro" class="content-view active">
            <div class="form-container max-w-7xl mx-auto">
                <form id="sampleForm" onsubmit="return handleSampleSubmit(event)">
                    <!-- INFORMACIÓN DE REGISTRO Y ENVÍO -->
                    <div class="form-section mb-6">
                        <div class="dual-group-container grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
                            <!-- GRUPO 1: Datos de Envío -->
                            <div class="field-group border border-gray-300 rounded-2xl p-5 bg-white">
                                <div
                                    class="group-header text-xs font-bold text-blue-600 uppercase tracking-wide pb-2 mb-4">
                                    Datos de Envío
                                </div>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Fecha de Envío <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" id="fechaEnvio" name="fechaEnvio" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Hora <span class="text-red-500">*</span>
                                            </label>
                                            <input type="time" id="horaEnvio" name="horaEnvio" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Código de Envío <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="codigoEnvio" name="codigoEnvio" readonly
                                                class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg font-bold text-blue-600 focus:outline-none text-sm">
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Laboratorio <span class="text-red-500">*</span>
                                            </label>
                                            <select id="laboratorio" name="laboratorio"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm">
                                                <option value="">Seleccionar...</option>
                                                <?php
                                                $query = "SELECT codigo, nombre FROM san_dim_laboratorio ORDER BY nombre";
                                                $result = mysqli_query($conexion, $query);
                                                if ($result && mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        echo '<option value="' . htmlspecialchars($row['codigo']) . '">' . htmlspecialchars($row['nombre']) . '</option>';
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
                            <div class="field-group border border-gray-300 rounded-2xl p-5 bg-white">
                                <div
                                    class="group-header text-xs font-bold text-blue-600 uppercase tracking-wide pb-2 mb-4">
                                    TRANSPORTE Y RESPONSABLES
                                </div>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Empresa de Transporte <span class="text-red-500">*</span>
                                            </label>
                                            <select name="empresa_transporte" id="empresa_transporte"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm">
                                                <option value="">Seleccionar...</option>
                                                <?php
                                                $query = "SELECT codigo, nombre FROM san_dim_emptrans ORDER BY nombre";
                                                $result = mysqli_query($conexion, $query);
                                                if ($result && mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        echo '<option value="' . htmlspecialchars($row['codigo']) . '">' . htmlspecialchars($row['nombre']) . '</option>';
                                                    }
                                                } else {
                                                    echo '<option value="">No hay empresas disponibles</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Autorizado por <span class="text-red-500">*</span>
                                            </label>
                                            <input name="autorizado_por" id="autorizado_por" type="text"
                                                placeholder="Nombre"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Usuario
                                                Registrador</label>
                                            <input name="usuario_registrador"
                                                value="<?php echo htmlspecialchars($_SESSION['usuario'] ?? 'user'); ?>"
                                                type="text" readonly
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 focus:outline-none text-sm">
                                        </div>
                                        <div class="form-field">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Usuario Responsable <span class="text-red-500">*</span>
                                            </label>
                                            <input name="usuario_responsable" id="usuario_responsable" type="text"
                                                placeholder="Nombre del responsable"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Número de Solicitudes -->
                        <div class="form-field max-w-xs">
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Número de Solicitudes <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="numeroSolicitudes" name="numeroSolicitudes" min="1" max="20"
                                placeholder="Ingrese cantidad"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>

                    <!-- CONTENEDOR DE MUESTRAS DINÁMICAS (oculto al inicio) -->
                    <div id="samples-table-container" class="mt-6 hidden">
                        <div class="overflow-x-auto w-full">
                            <table id="samplesTable" class="min-w-full table-auto border-collapse">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-4 py-2 text-center border text-xs font-bold text-gray-700">Tipo de
                                            Muestra</th>
                                        <th class="px-4 py-2 text-center border text-xs font-bold text-gray-700">Código
                                            de Referencia</th>
                                        <th class="px-4 py-2 text-center border text-xs font-bold text-gray-700">Fecha
                                            de Toma</th>
                                        <th class="px-4 py-2 text-center border text-xs font-bold text-gray-700">Número
                                            de Muestras</th>
                                        <th class="px-4 py-2 text-center border text-xs font-bold text-gray-700">
                                            Análisis</th>
                                        <th class="px-4 py-2 text-center border text-xs font-bold text-gray-700">
                                            Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody id="samplesTableBody" class="divide-y">
                                    <!-- Filas dinámicas -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- BOTÓN GUARDAR A LA DERECHA -->
                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2 text-sm">
                            Guardar Registro
                        </button>
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
        <!-- Modal para copiar análisis -->
        <div class="modal fade" id="copyAnalisisModal" tabindex="-1" aria-labelledby="copyAnalisisModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="copyAnalisisModalLabel">Copiar análisis</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="copyTargetSelect" class="form-label">Copiar a solicitud:</label>
                            <select class="form-select" id="copyTargetSelect">
                                <option value="">Seleccionar...</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmCopyAnalisis">Copiar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de Confirmación (Bootstrap 5) -->
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable" style="max-width: 85%;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmModalLabel">📋 Confirmar Envío de Muestras</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Aquí va el contenido generado -->
                        <div id="summaryContent"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btnConfirmSubmit">
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

        let optionsMuestraHTML = '<option value="">Seleccionar...</option>'; // Valor por defecto
        let analisisModalInstance = null;
        document.addEventListener('DOMContentLoaded', () => {
            initApp();
            analisisModalInstance = new bootstrap.Modal(document.getElementById('analisisModal'));
            document.getElementById('samplesTableBody').addEventListener('change', function (e) {
                if (e.target && e.target.matches('select[id^="tipoMuestra_"]')) {
                    const select = e.target;
                    const idx = parseInt(select.dataset.sampleIndex);

                    // Guardar en caché
                    sampleDataCache[idx] = sampleDataCache[idx] || {};
                    sampleDataCache[idx].tipoMuestra = select.value;
                    sampleDataCache[idx].analisisSeleccionados = [];

                    // Actualizar UI
                    document.getElementById(`analisisResumen_${idx}`).innerHTML = '';//'Ninguno';
                    updateCodigoReferencia(idx);
                }
            });
            document.getElementById('samplesTableBody').addEventListener('click', function (e) {
                const index = e.target.closest('[data-index]')?.dataset.index;
                if (index === undefined) return;
                const i = parseInt(index, 10);

                if (e.target.classList.contains('btn-seleccionar')) {
                    openAnalisisModal(i);
                } else if (e.target.classList.contains('btn-copiar')) {
                    copyAnalisisTo(i);
                }
            });
            document.getElementById('btnConfirmSubmit').addEventListener('click', function () {
                // Cierra el modal antes de enviar (mejor UX)
                bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                // Ejecuta el envío real
                confirmSubmit();
            });
        });

        async function initApp() {
            loadCodigoEnvio(); // Tu función existente

            // Precargar tipos de muestra INMEDIATAMENTE
            try {
                const res = await fetch("get_tipos_muestra.php");
                const tipos = await res.json();

                if (!tipos.error) {
                    allTiposMuestra = tipos;
                    // Construimos el string HTML una sola vez aquí
                    optionsMuestraHTML = '<option value="">Seleccionar...</option>';
                    allTiposMuestra.forEach(tipo => {
                        optionsMuestraHTML += `<option value="${tipo.codigo}">${tipo.nombre}</option>`;
                    });
                }
            } catch (error) {
                console.error("Error precargando tipos:", error);
            }
        }

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

        function updateCodigoReferencia(sampleIndex) {
            return new Promise((resolve, reject) => {
                const tipoId = document.getElementById(`tipoMuestra_${sampleIndex}`)?.value;
                const container = document.getElementById(`codigoReferenciaContainer_${sampleIndex}`);

                if (!container) {
                    reject(new Error("Contenedor no encontrado"));
                    return;
                }

                // Crear input oculto
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = `codigoReferenciaValue_${sampleIndex}`;
                hiddenInput.name = `codigoReferenciaValue_${sampleIndex}`;
                container.innerHTML = '';
                container.appendChild(hiddenInput);

                if (!tipoId) {
                    resolve(); // sin tipo, nada más que hacer
                    return;
                }

                fetch(`get_config_muestra.php?tipo=${encodeURIComponent(tipoId)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) throw new Error(data.error);

                        const longitud = data.tipo_muestra.longitud_codigo;
                        container.innerHTML = '';

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
                                if (this.value && this.nextElementSibling) this.nextElementSibling.focus();
                                updateHiddenValue();
                            });
                            box.addEventListener('keydown', function (e) {
                                if (e.key === "Backspace" && !this.value && this.previousElementSibling) {
                                    this.previousElementSibling.focus();
                                }
                            });
                            boxesContainer.appendChild(box);
                        }

                        function updateHiddenValue() {
                            const value = Array.from(boxesContainer.querySelectorAll("input"))
                                .map(i => i.value || "")
                                .join("");
                            hiddenInput.value = value;
                        }

                        container.appendChild(boxesContainer);
                        container.appendChild(hiddenInput);
                        resolve(); // ✅ DOM listo
                    })
                    .catch(error => {
                        console.error("Error al cargar config:", error);
                        alert("⚠️ No se pudo cargar la configuración del tipo de muestra.");
                        reject(error);
                    });
            });
        }

        function adjustTableRows(newCount) {
            const currentCount = totalSamples;

            if (newCount === currentCount) return;

            const tableBody = document.getElementById("samplesTableBody");

            if (newCount > currentCount) {
                // ✅ Agregar filas nuevas
                for (let i = currentCount; i < newCount; i++) {
                    const row = createSampleRow(i);
                    tableBody.appendChild(row);
                    // Inicializar caché vacía
                    sampleDataCache[i] = {
                        tipoMuestra: '',
                        codigoReferencia: '',
                        fechaToma: dateStr,
                        numeroMuestras: '1',
                        analisisSeleccionados: [],
                        observaciones: '',
                        analisisResumenHtml: ''
                    };
                }
            } else {
                // ❌ Eliminar filas sobrantes (de abajo hacia arriba)
                for (let i = currentCount - 1; i >= newCount; i--) {
                    const row = document.getElementById(`sampleRow_${i}`);
                    if (row) row.remove();
                    // Eliminar de caché
                    delete sampleDataCache[i];
                }
            }

            totalSamples = newCount;
        }
        function createSampleRow(i) {
            const row = document.createElement('tr');
            row.id = `sampleRow_${i}`;

            // 1. Tipo de Muestra
            const tmCell = document.createElement('td');
            const tmSelect = document.getElementById('templateSelect').cloneNode(true);
            tmSelect.id = `tipoMuestra_${i}`;
            tmSelect.name = `tipoMuestra_${i}`;
            tmSelect.dataset.sampleIndex = i;
            tmSelect.classList.remove('d-none');
            tmSelect.innerHTML = optionsMuestraHTML;
            tmCell.appendChild(tmSelect);
            row.appendChild(tmCell);

            // 2. Código de Referencia
            const crCell = document.createElement('td');
            crCell.innerHTML = `<div id="codigoReferenciaContainer_${i}"></div>`;
            row.appendChild(crCell);

            // 3. Fecha de Toma
            const ftCell = document.createElement('td');
            ftCell.innerHTML = `<input type="date" class="form-control" id="fechaToma_${i}" value="${dateStr}">`;
            row.appendChild(ftCell);

            // 4. Número de Muestras
            const nmCell = document.createElement('td');
            nmCell.innerHTML = `<input type="number" class="form-control" id="numeroMuestras_${i}" min="1" max="20" value="1">`;
            row.appendChild(nmCell);

            // 5. Análisis
            const anCell = document.createElement('td');
            anCell.innerHTML = `
        <div class="d-flex flex-column">
            <div class="d-flex gap-1 mb-1">
                <button type="button" class="btn btn-sm btn-outline-primary btn-seleccionar" data-index="${i}">Seleccionar</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-copiar" data-index="${i}">Copiar</button>
            </div>
            <div id="analisisResumen_${i}" style="font-size: 0.85em;"></div>
        </div>`;
            row.appendChild(anCell);

            // 6. Observaciones
            const obsCell = document.createElement('td');
            obsCell.innerHTML = `<textarea class="form-control" id="observaciones_${i}" rows="2"></textarea>`;
            row.appendChild(obsCell);

            return row;
        }
        function generateTableRows(count) {
            // A. Guardar datos actuales en caché
            for (let i = 0; i < totalSamples; i++) {
                const row = document.getElementById(`sampleRow_${i}`);
                if (row) {
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

            // B. Limpiar tabla
            tableBody.innerHTML = '';
            totalSamples = count;

            // C. Generar filas
            for (let i = 0; i < count; i++) {
                const row = document.createElement('tr');
                row.id = `sampleRow_${i}`;

                // 1. Tipo de Muestra - CLON DEL TEMPLATE
                const tmCell = document.createElement('td');
                const tmSelect = document.getElementById('templateSelect').cloneNode(true);
                tmSelect.id = `tipoMuestra_${i}`;
                tmSelect.name = `tipoMuestra_${i}`;
                tmSelect.dataset.sampleIndex = i;
                tmSelect.classList.remove('d-none');        // ← IMPORTANTE
                tmSelect.innerHTML = optionsMuestraHTML;    // ← Opciones precargadas
                tmCell.appendChild(tmSelect);
                row.appendChild(tmCell);

                // 2. Celda Código Referencia
                const crCell = document.createElement('td');
                crCell.innerHTML = `<div id="codigoReferenciaContainer_${i}"></div>`;
                row.appendChild(crCell);

                // 3. Celda Fecha
                const ftCell = document.createElement('td');
                ftCell.innerHTML = `<input type="date" class="form-control" id="fechaToma_${i}" value="${dateStr}">`;
                row.appendChild(ftCell);

                // 4. Celda Número Muestras
                const nmCell = document.createElement('td');
                nmCell.innerHTML = `<input type="number" class="form-control" id="numeroMuestras_${i}" min="1" max="20" value="1">`;
                row.appendChild(nmCell);

                // 5. Celda Análisis
                const anCell = document.createElement('td');
                anCell.innerHTML = `
    <div class="d-flex flex-column">
        <div class="d-flex gap-1 mb-1">
            <button type="button" class="btn btn-sm btn-outline-primary btn-seleccionar" data-index="${i}">Seleccionar</button>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-copiar" data-index="${i}">Copiar</button>
        </div>
        <div id="analisisResumen_${i}" style="font-size: 0.85em;">Ninguno</div>
    </div>
`;
                row.appendChild(anCell);

                // 6. Celda Observaciones
                const obsCell = document.createElement('td');
                obsCell.innerHTML = `<textarea class="form-control" id="observaciones_${i}" rows="2"></textarea>`;
                // Dentro del bucle for (let i = 0; i < count; i++)
                row.appendChild(obsCell);
                tableBody.appendChild(row);

                // --- RESTAURAR DATOS ---
                const cache = sampleDataCache[i];
                if (cache) {
                    // 1. Tipo de muestra
                    if (cache.tipoMuestra) {
                        document.getElementById(`tipoMuestra_${i}`).value = cache.tipoMuestra;
                    }

                    // 2. Código de referencia (se restaurará async)
                    if (cache.tipoMuestra) {
                        updateCodigoReferencia(i).then(() => {
                            if (cache.codigoReferencia) {
                                const boxes = document.querySelectorAll(`#codigoReferenciaBoxes_${i} input`);
                                const digits = cache.codigoReferencia.split('');
                                boxes.forEach((box, idx) => {
                                    if (digits[idx]) box.value = digits[idx];
                                });
                                const hidden = document.getElementById(`codigoReferenciaValue_${i}`);
                                if (hidden) hidden.value = cache.codigoReferencia;
                            }
                        });
                    }

                    // 3. Otros campos simples
                    if (cache.fechaToma) document.getElementById(`fechaToma_${i}`).value = cache.fechaToma;
                    if (cache.numeroMuestras) document.getElementById(`numeroMuestras_${i}`).value = cache.numeroMuestras;
                    if (cache.observaciones) document.getElementById(`observaciones_${i}`).value = cache.observaciones;

                    if (cache.analisisSeleccionados && cache.analisisSeleccionados.length > 0) {
                        // ✅ Si NO hay HTML bonito, es porque la fila nunca pasó por el modal.
                        //    En ese caso, NO mostramos nada bonito, pero tampoco destruimos el formato.
                        //    Simplemente dejamos que updateAnalisisResumen maneje el fallback.
                        updateAnalisisResumen(i);
                    }
                }
            }
        }
        // ✅ SOLUCIÓN: Cargar tipos de muestra de forma síncrona y usar delegación de eventos
        async function cargarTodosTiposMuestra(count) {
            try {
                // Cargar tipos de muestra una sola vez si no están cargados
                if (allTiposMuestra.length === 0) {
                    const res = await fetch("get_tipos_muestra.php");
                    const tipos = await res.json();
                    if (tipos.error) throw new Error(tipos.error);
                    allTiposMuestra = tipos;
                }

                // Llenar todos los selects
                for (let i = 0; i < count; i++) {
                    const tmSelect = document.getElementById(`tipoMuestra_${i}`);
                    if (!tmSelect) continue;

                    // Llenar opciones
                    tmSelect.innerHTML = '<option value="">Seleccionar...</option>';
                    allTiposMuestra.forEach((tipo) => {
                        const option = document.createElement('option');
                        option.value = tipo.codigo;
                        option.textContent = tipo.nombre;
                        tmSelect.appendChild(option);
                    });

                    // Restaurar datos si existen
                    const cache = sampleDataCache[i];
                    if (cache && cache.tipoMuestra) {
                        tmSelect.value = cache.tipoMuestra;

                        // Restaurar código de referencia y demás campos
                        await updateCodigoReferencia(i);

                        if (cache.codigoReferencia) {
                            const boxes = document.querySelectorAll(`#codigoReferenciaBoxes_${i} input`);
                            const digits = cache.codigoReferencia.split('');
                            boxes.forEach((box, idx) => {
                                if (digits[idx]) box.value = digits[idx];
                            });
                            const hidden = document.getElementById(`codigoReferenciaValue_${i}`);
                            if (hidden) hidden.value = cache.codigoReferencia;
                        }
                        if (cache.fechaToma) document.getElementById(`fechaToma_${i}`).value = cache.fechaToma;
                        if (cache.numeroMuestras) document.getElementById(`numeroMuestras_${i}`).value = cache.numeroMuestras;
                        if (cache.observaciones) document.getElementById(`observaciones_${i}`).value = cache.observaciones;
                        if (cache.analisisSeleccionados && cache.analisisSeleccionados.length > 0) {
                            sampleDataCache[i].analisisSeleccionados = cache.analisisSeleccionados;
                            updateAnalisisResumen(i);
                        }
                    }
                }
            } catch (error) {
                console.error("Error al cargar tipos de muestra:", error);
                alert("⚠️ No se pudieron cargar los tipos de muestra.");
            }
        }

        function restoreRowData(index, data) {
            if (!data || !data.tipoMuestra) return;

            const tmSelect = document.getElementById(`tipoMuestra_${index}`);
            if (!tmSelect) return;

            // 1. Establecer valor
            tmSelect.value = data.tipoMuestra;

            // 2. Forzar actualización del código de referencia SIN depender de eventos
            updateCodigoReferencia(index).then(() => {
                // 3. Restaurar campos una vez que el código de referencia ya está renderizado
                if (data.codigoReferencia) {
                    const boxes = document.querySelectorAll(`#codigoReferenciaBoxes_${index} input`);
                    const digits = data.codigoReferencia.split('');
                    boxes.forEach((box, i) => {
                        if (digits[i]) box.value = digits[i];
                    });
                    const hidden = document.getElementById(`codigoReferenciaValue_${index}`);
                    if (hidden) hidden.value = data.codigoReferencia;
                }

                if (data.fechaToma) document.getElementById(`fechaToma_${index}`).value = data.fechaToma;
                if (data.numeroMuestras) document.getElementById(`numeroMuestras_${index}`).value = data.numeroMuestras;
                if (data.observaciones) document.getElementById(`observaciones_${index}`).value = data.observaciones;
                if (data.analisisSeleccionados) {
                    sampleDataCache[index] = sampleDataCache[index] || {};
                    sampleDataCache[index].analisisSeleccionados = data.analisisSeleccionados;
                    updateAnalisisResumen(index);
                }
            }).catch(err => {
                console.warn("Error al restaurar código de referencia:", err);
                // Aún así restaurar otros campos
                if (data.fechaToma) document.getElementById(`fechaToma_${index}`).value = data.fechaToma;
                // ... resto igual
            });
        }
        // Actualiza el resumen de análisis visualmente
        function updateAnalisisResumen(sampleIndex) {
            const resumenEl = document.getElementById(`analisisResumen_${sampleIndex}`);
            if (!resumenEl) return;

            const cache = sampleDataCache[sampleIndex] || {};
            const analisis = cache.analisisSeleccionados || [];

            if (analisis.length === 0) {
                resumenEl.innerHTML = 'Ninguno';
                return;
            }

            // ✅ Si ya tenemos el HTML bonito, lo usamos
            if (cache.analisisResumenHtml) {
                resumenEl.innerHTML = cache.analisisResumenHtml;
                return;
            }

            // ✅ Si NO tenemos HTML bonito, pero SÍ tenemos objetos con nombre, generamos uno decente
            if (typeof analisis[0] === 'object' && analisis[0].nombre) {
                const nombres = analisis.map(a => a.nombre).join(', ');
                resumenEl.innerHTML = `<small>${nombres}</small>`;
                return;
            }

            // ✅ Si solo tenemos códigos (strings), mostramos placeholder
            const codigos = analisis.map(a => typeof a === 'string' ? a : a.codigo);
            const nombres = codigos.map(c => `Análisis ${c}`).join(', ');
            resumenEl.innerHTML = `<small>${nombres}</small>`;
        }

        /*function copyAnalisisTo(sourceIndex) {
            const analisisResumenCell = document.querySelector(`#analisisResumen_${sourceIndex}`).closest('td');
            const existingContainer = analisisResumenCell.querySelector('.copy-controls-container');

            // ✅ Si ya existe el contenedor de copia, no crear otro
            if (existingContainer) {
                existingContainer.style.display = 'block';
                return;
            }

            const sourceCache = sampleDataCache[sourceIndex];
            if (!sourceCache || !sourceCache.analisisSeleccionados?.length) {
                alert('No hay análisis seleccionados en la fila origen.');
                return;
            }

            // ✅ Crear contenedor único para selección de destino
            const container = document.createElement('div');
            container.className = 'copy-controls-container mt-2 p-2 bg-gray-100 rounded';
            container.innerHTML = `
        <label class="text-sm">Copiar a fila:</label>
        <select class="form-select copy-target-select mt-1">
            <option value="">Seleccionar...</option>
        </select>
        <div class="mt-2">
            <button type="button" class="btn btn-sm btn-primary copy-confirm">Copiar</button>
            <button type="button" class="btn btn-sm btn-secondary copy-cancel ms-2">Cancelar</button>
        </div>
    `;
            analisisResumenCell.appendChild(container);

            // Llenar opciones (excluye la fila origen)
            const selectEl = container.querySelector('.copy-target-select');
            for (let i = 0; i < totalSamples; i++) {
                if (i !== sourceIndex) {
                    const opt = document.createElement('option');
                    opt.value = i;
                    opt.textContent = `Fila ${i + 1}`;
                    selectEl.appendChild(opt);
                }
            }

            // ✅ Confirmar copia
            container.querySelector('.copy-confirm').onclick = () => {
                const targetIndex = parseInt(selectEl.value);
                if (isNaN(targetIndex) || targetIndex < 0 || targetIndex >= totalSamples) {
                    alert('Seleccione una fila válida.');
                    return;
                }

                // 1. Copiar tipo de muestra si es diferente
                const srcSelect = document.getElementById(`tipoMuestra_${sourceIndex}`);
                const tgtSelect = document.getElementById(`tipoMuestra_${targetIndex}`);
                const srcTipo = srcSelect?.value || '';

                if (srcTipo && srcTipo !== tgtSelect.value) {
                    tgtSelect.value = srcTipo;
                    tgtSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                // 2. ✅ Copiar datos COMPLETOS desde la caché (incluyendo HTML del resumen)
                sampleDataCache[targetIndex] = sampleDataCache[targetIndex] || {};
                sampleDataCache[targetIndex].analisisSeleccionados = [...sourceCache.analisisSeleccionados];
                sampleDataCache[targetIndex].tipoMuestra = srcTipo;

                // ✅ Copiar el HTML exacto del resumen (lo que ya se generó en el modal)
                if (sourceCache.analisisResumenHtml) {
                    sampleDataCache[targetIndex].analisisResumenHtml = sourceCache.analisisResumenHtml;
                }

                // 3. ✅ Actualizar el resumen visual usando el HTML guardado
                updateAnalisisResumen(targetIndex);

                // 4. Notificación y limpieza
                alert(`✅ Análisis copiados a la Fila ${targetIndex + 1}.`);
                container.remove();
            };

            // ✅ Cancelar copia
            container.querySelector('.copy-cancel').onclick = () => {
                container.remove();
            };
        }*/
        function copyAnalisisTo(sourceIndex) {
            const sourceCache = sampleDataCache[sourceIndex];
            if (!sourceCache || !sourceCache.analisisSeleccionados?.length) {
                alert('No hay análisis seleccionados en la fila origen.');
                return;
            }

            // Llenar el select del modal
            const selectEl = document.getElementById('copyTargetSelect');
            selectEl.innerHTML = '<option value="">Seleccionar...</option>';
            for (let i = 0; i < totalSamples; i++) {
                if (i !== sourceIndex) {
                    const opt = document.createElement('option');
                    opt.value = i;
                    opt.textContent = `Solicitud ${i + 1}`;
                    selectEl.appendChild(opt);
                }
            }

            // Guardar el índice de origen en un atributo del botón de confirmación
            const confirmBtn = document.getElementById('confirmCopyAnalisis');
            confirmBtn.dataset.sourceIndex = sourceIndex;

            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('copyAnalisisModal'));
            modal.show();
        }
        // Listener para el botón de copiar en el modal
        document.getElementById('confirmCopyAnalisis').addEventListener('click', function () {
            const sourceIndex = parseInt(this.dataset.sourceIndex);
            const targetIndex = parseInt(document.getElementById('copyTargetSelect').value);

            if (isNaN(targetIndex) || targetIndex < 0 || targetIndex >= totalSamples) {
                alert('Seleccione una solicitud válida.');
                return;
            }

            // 1. Copiar tipo de muestra
            const srcSelect = document.getElementById(`tipoMuestra_${sourceIndex}`);
            const tgtSelect = document.getElementById(`tipoMuestra_${targetIndex}`);
            const srcTipo = srcSelect?.value || '';
            if (srcTipo && srcTipo !== tgtSelect.value) {
                tgtSelect.value = srcTipo;
                tgtSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // 2. Copiar análisis y resumen
            const sourceCache = sampleDataCache[sourceIndex];
            sampleDataCache[targetIndex] = sampleDataCache[targetIndex] || {};
            sampleDataCache[targetIndex].analisisSeleccionados = [...sourceCache.analisisSeleccionados];
            sampleDataCache[targetIndex].tipoMuestra = srcTipo;

            // Copiar el HTML del resumen si existe
            if (sourceCache.analisisResumenHtml) {
                sampleDataCache[targetIndex].analisisResumenHtml = sourceCache.analisisResumenHtml;
            }

            // 3. Actualizar UI
            updateAnalisisResumen(targetIndex);

            // 4. Cerrar modal y notificar
            bootstrap.Modal.getInstance(document.getElementById('copyAnalisisModal')).hide();
            alert(`✅ Análisis copiados a la Solicitud ${targetIndex + 1}.`);
        });
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

            // ✅ Regenerar resumen bonito si existe análisis pero no HTML
            const cache = sampleDataCache[sampleIndex] || {};
            if (cache.analisisSeleccionados?.length > 0 && !cache.analisisResumenHtml) {
                const tipoId = tipoMuestraSelect.value;
                try {
                    const res = await fetch(`get_config_muestra.php?tipo=${tipoId}`);
                    const data = await res.json();
                    if (!data.error) {
                        const resumenHtml = generateAnalisisResumen(
                            cache.analisisSeleccionados,
                            data.paquetes,
                            data.analisis.reduce((acc, a) => {
                                if (a.paquete) {
                                    if (!acc[a.paquete]) acc[a.paquete] = [];
                                    acc[a.paquete].push(a);
                                }
                                return acc;
                            }, {})
                        );
                        sampleDataCache[sampleIndex].analisisResumenHtml = resumenHtml;
                        document.getElementById(`analisisResumen_${sampleIndex}`).innerHTML = resumenHtml;
                    }
                } catch (err) {
                    console.warn("No se pudo regenerar el resumen bonito:", err);
                }
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

                const selectedAnalisisCodigos = new Set(
                    (cache.analisisSeleccionados || []).map(a => String(a.codigo))
                );

                let html = '<style>.analisis-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 8px; margin-left: 24px; }</style>';

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

                // ✅ Listener para checkboxes de paquetes
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

                // ✅ Guardar selección
                const saveBtn = document.getElementById("analisisModalSaveBtn");
                // Evita múltiples listeners acumulados
                saveBtn.replaceWith(saveBtn.cloneNode(true));
                document.getElementById("analisisModalSaveBtn").onclick = () => {
                    const selectedAnalisis = Array.from(
                        document.querySelectorAll("#analisisModal .analisis-individual:checked")
                    ).map(cb => ({
                        codigo: cb.value,
                        nombre: cb.dataset.nombre
                    }));

                    const resumenHtml = generateAnalisisResumen(selectedAnalisis, data.paquetes, analisisPorPaquete);
                    sampleDataCache[sampleIndex] = sampleDataCache[sampleIndex] || {};
                    sampleDataCache[sampleIndex].analisisSeleccionados = selectedAnalisis;
                    sampleDataCache[sampleIndex].analisisResumenHtml = resumenHtml;
                    document.getElementById(`analisisResumen_${sampleIndex}`).innerHTML = resumenHtml;

                    // ✅ Cerrar usando la instancia reutilizable
                    if (analisisModalInstance) {
                        analisisModalInstance.hide();
                    }
                };

                // ✅ Mostrar modal usando la instancia global
                if (analisisModalInstance) {
                    analisisModalInstance.show();
                } else {
                    console.error("❌ La instancia del modal de análisis no fue inicializada.");
                }

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
                return '';//Ninguno
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

        function handleNumeroSolicitudesChange() {
            let value = this.value.trim();


            if (value === "") {
                return;
            }
            let count = parseInt(value, 10) || 0;


            const max = 50;
            const min = 1;

            if (count < min) {
                this.value = "";
                document.getElementById('samples-table-container').classList.add('hidden');
                totalSamples = 0;
                // Opcional: limpiar todas las filas
                document.getElementById("samplesTableBody").innerHTML = '';
                return;
            }

            if (count > max) count = max;
            this.value = count;

            // Mostrar tabla
            document.getElementById('samples-table-container').classList.remove('hidden');

            // ✅ Ajustar filas de forma INCREMENTAL
            adjustTableRows(count);
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

        numeroInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                this.blur(); // dispara 'change'
            }
        });
        // Escuchar tanto 'input' como 'change'
        numeroInput.addEventListener("input", handleNumeroSolicitudesChange);
        numeroInput.addEventListener("change", handleNumeroSolicitudesChange);

        window.handleSampleSubmit = function (event) {
            event.preventDefault();
            const errores = [];

            // Campos fijos
            const fixedFields = [
                { id: 'fechaEnvio', name: 'Fecha de envío' },
                { id: 'horaEnvio', name: 'Hora de envío' },
                { id: 'laboratorio', name: 'Laboratorio' },
                { id: 'empresa_transporte', name: 'Empresa de transporte' },
                { id: 'autorizado_por', name: 'Autorizado por' },
                { id: 'usuario_responsable', name: 'Usuario responsable' },
                { id: 'numeroSolicitudes', name: 'Número de solicitudes' }
            ];

            for (const { id, name } of fixedFields) {
                const el = document.getElementById(id);
                if (!el?.value?.trim()) {
                    errores.push(`- ${name} es obligatorio.`);
                }
            }

            // Validar filas
            for (let i = 0; i < totalSamples; i++) {
                const rowPrefix = `Fila ${i + 1}:`;
                const tipo = document.getElementById(`tipoMuestra_${i}`)?.value;
                const fecha = document.getElementById(`fechaToma_${i}`)?.value;
                const codRef = document.getElementById(`codigoReferenciaValue_${i}`)?.value;
                const analisis = (sampleDataCache[i]?.analisisSeleccionados || []);

                if (!tipo) errores.push(`${rowPrefix} Tipo de muestra es obligatorio.`);
                if (!fecha) errores.push(`${rowPrefix} Fecha de toma es obligatoria.`);
                if (!codRef?.trim()) errores.push(`${rowPrefix} Código de referencia incompleto.`);
                if (analisis.length === 0) errores.push(`${rowPrefix} Debe seleccionar al menos un análisis.`);
            }

            if (errores.length > 0) {
                alert("❌ Corrija los siguientes errores:\n" + errores.join('\n'));
                return;
            }

            generateSummary(new FormData(document.getElementById("sampleForm")));
            document.getElementById("confirmModal").classList.remove('hidden');
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
                        <div>N° muestras</div>
                    </th>
    `;

            // Columnas de tipos de muestra (rotadas)
            allTiposMuestra.forEach(tm => {
                summaryHTML += `
                    <th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white; height:80px; vertical-align:middle;">
                        <div>${tm.nombre}</div>
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
            // Al final de generateSummary()
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            confirmModal.show();
        }
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
                analisis.forEach(item => {
                    // item es un objeto { codigo: "...", nombre: "..." }
                    formData.append(`analisis_${i}[]`, item.codigo);
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


                } else {
                    throw new Error(result.message || result.error || "Error desconocido al guardar.");
                }
            } catch (error) {
                console.error("Error:", error);
                alert("❌ Error al guardar el registro: " + error.message);
            }
        };

        // === Función para cerrar el modal de confirmación ===
        /*window.closeConfirmModal = function () {
            document.getElementById("confirmModal").style.display = "none";
        };*/

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
    <select class="form-select d-none" id="templateSelect">
        <option value="">Seleccionar...</option>
    </select>
</body>

</html>