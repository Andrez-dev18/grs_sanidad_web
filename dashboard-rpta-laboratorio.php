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

// Recibir filtros si vienen del AJAX
$fechaInicio = $_GET['fechaInicio'] ?? null;
$fechaFin    = $_GET['fechaFin'] ?? null;
$estado      = $_GET['estado'] ?? 'pendiente';  // default

// Construir condiciones dinámicas
$conditions = [];

// ESTADO
if ($estado !== "todos") {
    $conditions[] = "d.estado_cuali = '$estado'";
}

// FECHA INICIO
if (!empty($fechaInicio)) {
    $conditions[] = "d.fecToma >= '$fechaInicio'";
}

// FECHA FIN
if (!empty($fechaFin)) {
    $conditions[] = "d.fecToma <= '$fechaFin'";
}

// Si no hay filtros, igual ponemos default pendiente
if (count($conditions) === 0) {
    $conditions[] = "d.estado_cuali = 'pendiente'";
}

// Convertir array a WHERE
$where = "WHERE " . implode(" AND ", $conditions);

$query = "
    SELECT 
        d.codEnvio,
        d.posSolicitud,
        d.fecToma,
        d.codRef,
        d.numMuestras AS numeroMuestras,
        
        GROUP_CONCAT(d.nomAnalisis ORDER BY d.nomAnalisis SEPARATOR ', ') AS analisis,
        GROUP_CONCAT(d.codAnalisis ORDER BY d.codAnalisis SEPARATOR ',') AS analisisCodigos,
        GROUP_CONCAT(d.obs ORDER BY d.posSolicitud SEPARATOR ' | ') AS observaciones

    FROM san_fact_solicitud_det d
    INNER JOIN san_fact_solicitud_cab c
           ON d.codEnvio = c.codEnvio
            
    $where

    GROUP BY 
        d.codEnvio,
        d.posSolicitud,
        d.fecToma,
        d.codRef,
        d.numMuestras

    ORDER BY d.codEnvio DESC,
        d.fecToma DESC,
        d.posSolicitud ASC;
";

$result = $conexion->query($query);


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Respuesta lab</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link rel="stylesheet" href="css/style-rpt-lab.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .selected-order {
            background-color: #e6f0ff;
            /* azul muy suave */
            border-color: #3b82f6 !important;
            /* azul */
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
    <div class="container mx-auto px-3 py-6">

        <div class="flex flex-col h-screen">

            <!-- HEADER -->
            <header class="mx- mt-6 bg-white p-8 rounded-xl shadow-sm border border-[#e5e7eb]">
                <h1 class="text-2xl font-semibold text-[#2c3e50]">🧪 Respuesta de Laboratorio cualitativo</h1>
                <!-- DIVISOR -->
                <div class="w-full h-px bg-gray-200 my-6"></div>

                <!-- FILTROS -->
                <div class="mt-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">

                    <!-- HEADER FILTROS (con botón plegable) -->
                    <div class="flex items-center justify-between cursor-pointer"
                        onclick="toggleFiltros()">
                        <h3 class="text-sm font-semibold text-[#2c3e50]">
                            🔍 Filtros de búsqueda
                        </h3>

                        <!-- Botón plegable -->
                        <button id="btnToggleFiltros"
                            class="text-gray-600 text-lg font-bold w-7 h-7 flex items-center justify-center rounded hover:bg-gray-200">
                            ➕
                        </button>
                    </div>

                    <!-- CONTENIDO PLEGABLE -->
                    <div id="filtrosContent"
                        class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end mt-4 transition-all duration-300 origin-top hidden">

                        <!-- FECHA INICIO -->
                        <div>
                            <label class="text-xs font-medium text-gray-600 mb-1 block">Fecha Inicio</label>
                            <input type="date" id="filtroFechaInicio"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-600 focus:border-blue-600">
                        </div>

                        <!-- FECHA FIN -->
                        <div>
                            <label class="text-xs font-medium text-gray-600 mb-1 block">Fecha Fin</label>
                            <input type="date" id="filtroFechaFin"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-600 focus:border-blue-600">
                        </div>

                        <!-- ESTADO -->
                        <div>
                            <label class="text-xs font-medium text-gray-600 mb-1 block">Estado</label>
                            <select id="filtroEstado"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-600 focus:border-blue-600">
                                <option value="todos">Seleccionar</option>
                                <option value="pendiente">Pendientes</option>
                                <option value="completado">Completados</option>
                            </select>
                        </div>

                        <!-- Laboratorio -->
                        <div>
                            <label class="text-xs font-medium text-gray-600 mb-1 block">Laboratorio</label>
                            <select id="filtroLab"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-600 focus:border-blue-600">
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

                        <!-- BOTÓN -->
                        <div class="flex">
                            <button onclick="aplicarFiltros()"
                                class="w-full md:w-auto px-5 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 shadow-sm">
                                Filtrar
                            </button>
                        </div>

                    </div>
                </div>


            </header>

            <div class="flex flex-1 overflow-hidden flex-col md:flex-row">

                <!-- SIDEBAR -->
                <aside class="bg-white w-full md:w-[300px] rounded-xl shadow-sm border border-[#e5e7eb] mt-4 mr-3 flex flex-col">
                    <div class="px-6 py-5 border-b border-[#e5e7eb]">
                        <h3 class="text-base font-semibold text-[#2c3e50]">Solicitudes</h3>

                        <input
                            id="searchInput"
                            type="text"
                            placeholder="Buscar..."

                            class="mt-3 w-full px-3 py-2 border border-[#d0d7de] rounded-md text-sm placeholder-[#a0aec0]
                                focus:outline-none focus:ring-2 focus:ring-[#0066cc]/30">
                    </div>

                    <!-- Lista de solicitudes -->
                    <div id="pendingOrdersList" class="flex-1 overflow-y-auto p-4 space-y-3">

                    </div>
                    <div id="paginationControls" class="p-4 flex justify-between text-sm text-gray-600"></div>

                </aside>

                <!-- MAIN CONTENT -->
                <main class="flex-1 overflow-y-auto mt-4 bg-white rounded-xl shadow-sm border border-[#e5e7eb] p-6 md:p-10">

                    <!-- EMPTY STATE (lo que se ve al inicio) -->
                    <div id="emptyStatePanel" class="mx-auto max-w-6xl">
                        <div class="border-2 border-dashed border-[#cfd8e3] rounded-md p-12 md:p-32 bg-white flex items-center justify-center">
                            <div class="text-center">
                                <h3 class="text-lg font-medium text-[#374151] mb-1">Seleccione una solicitud</h3>
                                <p class="text-sm text-[#9ca3af]">Elija una orden de la lista para adjuntar su respuesta</p>
                            </div>
                        </div>
                    </div>

                    <!-- DETAIL PANEL (oculto por defecto) -->
                    <div id="responseDetailPanel" class="hidden mx-auto max-w-6xl">
                        <div class="bg-white rounded-lg shadow-sm p-8">

                            <!-- Cabecera detalle -->
                            <div class="pb-4 border-b border-[#e5e7eb] mb-6 flex flex-col md:flex-row justify-between items-start gap-4">
                                <div>
                                    <h2 id="detailCodigo" class="text-3xl font-bold text-[#1f2937]">SAN-000000</h2>
                                    <span id="badgeStatus" class="inline-block mt-2 px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium">Pendiente de Respuesta</span>
                                    <!-- INFO ADICIONAL CABECERA -->
                                    <div id="extraInfoCabecera" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-2 text-sm text-gray-600">

                                        <div><span class="font-semibold text-gray-800">🔬 Laboratorio:</span> <span id="cabLaboratorio">--</span></div>
                                        <div><span class="font-semibold text-gray-800">🚚 Transporte:</span> <span id="cabTransporte">--</span></div>

                                        <div><span class="font-semibold text-gray-800">👤 Registrado por:</span> <span id="cabRegistrador">--</span></div>
                                        <div><span class="font-semibold text-gray-800">🧪 Responsable:</span> <span id="cabResponsable">--</span></div>

                                        <div><span class="font-semibold text-gray-800">✔️ Autorizado por:</span> <span id="cabAutorizado">--</span></div>
                                        <div><span class="font-semibold text-gray-800">🔑 Cod Ref:</span> <span id="cabCodRefe">--</span></div>

                                    </div>
                                </div>

                                <div class="text-sm text-gray-600 mt-3 md:mt-0 flex flex-col gap-1">
                                    <span id="detailFecha">📅 01/01/2024</span>
                                </div>
                            </div>

                            <!-- analisis section -->
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-gray-800">Seleccionar resultados de análisis</h3>

                                    <button id="addAnalisis"
                                        class="px-5 py-2 rounded-md text-white bg-green-600 hover:bg-green-700">
                                        ➕ Agregar nuevo análisis
                                    </button>
                                </div>
                                <!-- NUEVA FECHA DE REGISTRO -->
                                <div class="mt-6 mb-3">
                                    <label for="fechaRegistroLab" class="block text-sm font-medium text-gray-700 mb-1">
                                        📅 Fecha de registro del laboratorio
                                    </label>

                                    <input type="date"
                                        id="fechaRegistroLab"
                                        class="block w-full max-w-xs text-sm border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                                <div id="analisisContainer" class="grid grid-cols-[repeat(auto-fit,minmax(260px,1fr))] gap-4"></div>

                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Subir archivos (PDF, Word, Excel, Imágenes, etc.) — Opcional
                                    </label>

                                    <input type="file"
                                        id="archivoPdf"
                                        name="archivoPdf[]"
                                        multiple
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.png,.jpg,.jpeg"
                                        class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-600 file:text-white
                                            hover:file:bg-blue-700
                                            border border-gray-300 rounded-md p-1" />

                                    <div id="fileList" class="mt-3 space-y-2"></div>

                                    <!-- ARCHIVOS PRECARGADOS -->
                                    <div id="fileListPrecargados" class="mt-3 space-y-2"></div>

                                    <p class="text-xs text-gray-500 mt-1">(Máx. 10 MB por archivo)</p>
                                </div>



                                <!-- Botones -->
                                <div class="mt-6 flex justify-end gap-3">
                                    <button onclick="closeDetail()" class="px-5 py-2 rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-100">Cancelar</button>
                                    <button id="btnGuardarResultados" onclick="guardarResultados()" class="px-5 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700">💾 Guardar Respuesta</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </main>
            </div>
        </div>

        <!-- Modal Selección Múltiple de Análisis -->
        <div id="modalAnalisis" class="fixed inset-0 bg-black bg-opacity-40 hidden flex justify-center items-center">
            <div class="bg-white rounded-lg p-6 w-[950px] shadow-lg max-h-[85vh] overflow-y-auto relative">

                <!-- Botón X para cerrar -->
                <button onclick="cerrarModalAnalisis()"
                    class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl font-bold">
                    ✕
                </button>

                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Agregar análisis</h2>

                <div id="listaAnalisis" class="space-y-3">
                    <!-- Aquí se cargarán los grupos con checkboxes -->
                </div>

                <div class="flex justify-end mt-6 gap-3">
                    <button onclick="cerrarModalAnalisis()" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
                    <button onclick="confirmarAnalisisMultiples()"
                        class="px-4 py-2 bg-blue-600 text-white rounded">
                        Agregar Seleccionados
                    </button>
                </div>

            </div>
        </div>


        <!--  -->
        <script src="rptaLaboratorio.js"></script>


        <!-- Footer -->
        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © 2025
            </p>
        </div>

    </div>

    <script>
        let currentPage = 1;
        let limit = 10;
        let debounceTimer = null;

        /** carga la lista (pagina). page opcional */
        function loadSidebar(page = 1) {
            currentPage = page;

            // filtros
            const fechaInicio = encodeURIComponent(document.getElementById("filtroFechaInicio").value || "");
            const fechaFin = encodeURIComponent(document.getElementById("filtroFechaFin").value || "");
            const estado = encodeURIComponent(document.getElementById("filtroEstado").value || "pendiente");
            const filtroLab= encodeURIComponent(document.getElementById("filtroLab").value || "");
            const q = encodeURIComponent(document.getElementById("searchInput").value.trim() || "");

            const url = `get_solicitudes.php?page=${page}&limit=${limit}&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}&estado=${estado}&lab=${filtroLab}&q=${q}`;

            fetch(url)
                .then(r => r.json())
                .then(res => {
                    const list = document.getElementById("pendingOrdersList");
                    list.innerHTML = "";

                    res.data.forEach(row => {
                        const btn = document.createElement("button");
                        // id único por codEnvio + pos
                        btn.id = `item-${row.codEnvio}-${row.posSolicitud}`;
                        btn.className = "w-full text-left p-3 rounded-md hover:bg-gray-50 transition border border-gray-200 hover:border-gray-100";

                        btn.onclick = () => {
                            // resaltar visualmente
                            resaltarItemSidebar(row.codEnvio, row.posSolicitud);
                            cargarCabecera(row.codEnvio, row.fecToma, row.posSolicitud);
                        };

                        btn.innerHTML = `
                                <div class="flex justify-between items-center">
                                    <div class="font-semibold text-sm text-gray-800">
                                        ${escapeHtml(row.codEnvio)}
                                    </div>
                                    ${getEstadoBadge(row.estado_cuali)}
                                </div>

                                <div class="text-xs text-gray-500 mt-1">
                                    ${formatDate(row.fecToma)}
                                </div>

                                <div class="text-xs text-gray-600 mt-0.5">
                                    Ref: <span class="font-medium">${escapeHtml(row.codRef)}</span>
                                    • Solicitud: <span class="font-medium">${escapeHtml(row.posSolicitud)}</span>
                                </div>
                            `;


                        list.appendChild(btn);
                    });

                    // PAGINACIÓN
                    renderPagination(res.page, res.total, res.limit);
                })
                .catch(err => {
                    console.error("Error cargando solicitudes:", err);
                });
        }

        function getEstadoBadge(estado) {
            if (estado === "completado") {
                return `
            <span class="px-2 py-0.5 text-[11px] rounded-full bg-green-100 text-green-700 font-medium">
                Completado
            </span>`;
            }

            return `
        <span class="px-2 py-0.5 text-[11px] rounded-full bg-yellow-100 text-yellow-700 font-medium">
            Pendiente
        </span>`;
        }


        /** render simple paginación */
        function renderPagination(page, total, limit) {
            const totalPages = Math.max(1, Math.ceil(total / limit));
            const container = document.getElementById("paginationControls");

            container.innerHTML = `
        <button onclick="if(${page} > 1) loadSidebar(${page - 1});"
            class="px-3 py-1 rounded ${page <= 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-gray-200'}">
            ← Anterior
        </button>

        <span>Página ${page} de ${totalPages}</span>

        <button onclick="if(${page} < ${totalPages}) loadSidebar(${page + 1});"
            class="px-3 py-1 rounded ${page >= totalPages ? 'opacity-30 cursor-not-allowed' : 'hover:bg-gray-200'}">
            Siguiente →
        </button>
    `;
        }

        /** aplica filtros (llama loadSidebar a página 1) */
        function aplicarFiltros() {
            closeDetail();
            loadSidebar(1);
        }

        /** debounce wrapper para el searchInput */
        function debouncedSearch() {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                loadSidebar(1);
            }, 300);
        }

        /** formato fecha para mostrar */
        function formatDate(str) {
            if (!str) return "-";
            const d = new Date(str);
            return d.toLocaleDateString("es-PE");
        }

        /** escapar texto simple para seguridad en innerHTML */
        function escapeHtml(str) {
            if (str === null || str === undefined) return "";
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#39;");
        }

        /** conectar input search al debouncedSearch */
        document.addEventListener("DOMContentLoaded", () => {
            const input = document.getElementById("searchInput");
            if (input) {
                input.addEventListener("input", debouncedSearch);
            }

            // cargar primera página
            loadSidebar(1);
        });
    </script>




    <script src="funciones.js"></script>
    <script src="planificacion.js"></script>
    <script src="manteminiento.js"></script>
</body>

</html>