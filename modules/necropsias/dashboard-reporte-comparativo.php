<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: ../../login.php');
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reporte Comparativo de Necropsias</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
            border: none;
            padding: 0.625rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
            border: none;
            padding: 0.625rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(59, 130, 246, 0.4);
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .checkbox-group {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 0.25rem;
            transition: background-color 0.2s;
        }

        .checkbox-group label:hover {
            background-color: #f3f4f6;
        }
    </style>
</head>

<body class="bg-light">

<div class="container-fluid py-4">
    <!-- CARD FILTROS PLEGABLE -->
    <div class="mx-5 mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <!-- HEADER -->
        <button type="button" onclick="toggleFiltros()"
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
        <div id="contenidoFiltros" class="px-6 pb-6 pt-4">
            <!-- GRID DE FILTROS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Fecha inicio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde <span class="text-red-500">*</span></label>
                    <input type="date" id="filtroFechaInicio" required
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 form-control">
                </div>

                <!-- Fecha fin -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta <span class="text-red-500">*</span></label>
                    <input type="date" id="filtroFechaFin" required
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 form-control">
                </div>
            </div>

            <!-- CENCOS -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">CENCOS</label>
                
                <!-- Radio buttons: Todos, Activos, Escoger -->
                <div class="mb-3">
                    <label class="inline-flex items-center mr-4">
                        <input type="radio" name="filtro_cencos" value="todos" onchange="actualizarFiltroCencos()">
                        <span class="ml-2 text-sm">Todos</span>
                    </label>
                    <label class="inline-flex items-center mr-4">
                        <input type="radio" name="filtro_cencos" value="activos" onchange="actualizarFiltroCencos()">
                        <span class="ml-2 text-sm">Activos</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="filtro_cencos" value="escoger" onchange="actualizarFiltroCencos()">
                        <span class="ml-2 text-sm">Escoger</span>
                    </label>
                </div>

                <!-- Contenedor para checkboxes de CENCOS (solo cuando se selecciona "Escoger") -->
                <div id="containerCencos" class="checkbox-group hidden">
                    <p class="text-gray-500 text-sm text-center py-4">Cargando CENCOS...</p>
                </div>
            </div>

            <!-- GALPONES (Dinámico según filtro de CENCOS) -->
            <div id="seccionGalpones" class="mt-6 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Galpones</label>

                <!-- Selector: Todos / Escoger -->
                <div class="mb-3">
                    <label class="inline-flex items-center mr-4">
                        <input type="radio" name="tipo_galpon" value="todos" onchange="actualizarFiltroGalpones()">
                        <span class="ml-2 text-sm">Todos</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="tipo_galpon" value="escoger" onchange="actualizarFiltroGalpones()">
                        <span class="ml-2 text-sm">Escoger</span>
                    </label>
                </div>

                <!-- Contenedor de checkboxes de galpones (solo cuando se selecciona "Escoger") -->
                <div id="containerGalpones" class="checkbox-group hidden">
                    <!-- Los checkboxes se generarán aquí dinámicamente -->
                </div>
            </div>

            <!-- FORMATO -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Formato de Reporte</label>
                <select id="filtroFormato" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 form-control">
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel</option>
                </select>
            </div>

            <!-- ACCIONES -->
            <div class="mt-6 flex flex-wrap justify-end gap-4">
                <button type="button" id="btnGenerarReporte"
                    class="px-6 py-2.5 rounded-lg bg-green-600 text-white hover:bg-green-700 font-medium">
                    <i class="fas fa-file-pdf mr-2"></i> Generar Reporte
                </button>
                <button type="button" id="btnLimpiarFiltros"
                    class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium">
                    <i class="fas fa-redo mr-2"></i> Limpiar
                </button>
            </div>
        </div>
    </div>
</div>

    <script>
        let cencosData = [];
        let galponesData = {};
        let cencosSeleccionados = new Set();
        let galponesSeleccionados = new Set();
        let filtroCencosActual = ''; // 'todos', 'activos', 'escoger', o '' si no hay selección

        // Toggle filtros
        function toggleFiltros() {
            const contenido = document.getElementById('contenidoFiltros');
            const icono = document.getElementById('iconoFiltros');
            contenido.classList.toggle('hidden');
            icono.classList.toggle('rotate-180');
        }

        // Actualizar filtro de CENCOS
        async function actualizarFiltroCencos() {
            const checkedRadio = document.querySelector('input[name="filtro_cencos"]:checked');
            if (!checkedRadio) return; // Si no hay selección, no hacer nada
            
            const filtro = checkedRadio.value;
            filtroCencosActual = filtro;
            
            const containerCencos = document.getElementById('containerCencos');
            const seccionGalpones = document.getElementById('seccionGalpones');
            
            if (filtro === 'escoger') {
                // Mostrar checkboxes de CENCOS
                containerCencos.classList.remove('hidden');
                await cargarCencos('todos'); // Cargar todos para seleccionar
                seccionGalpones.classList.add('hidden');
            } else {
                // Ocultar checkboxes de CENCOS
                containerCencos.classList.add('hidden');
                cencosSeleccionados.clear();
                
                // Mostrar sección de galpones y cargar según el filtro
                seccionGalpones.classList.remove('hidden');
                await cargarGalponesPorFiltro(filtro);
            }
        }

        // Cargar CENCOS
        async function cargarCencos(filtro = 'activos') {
            try {
                const container = document.getElementById('containerCencos');
                if (container && !container.classList.contains('hidden')) {
                    container.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">Cargando CENCOS...</p>';
                }
                
                const response = await fetch(`get_granjas.php?filtro=${filtro}`);
                if (!response.ok) throw new Error('Error al cargar CENCOS');
                cencosData = await response.json();
                if (container && !container.classList.contains('hidden')) {
                    renderizarCencos();
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudieron cargar los CENCOS', 'error');
            }
        }

        // Renderizar CENCOS
        function renderizarCencos() {
            const container = document.getElementById('containerCencos');
            container.innerHTML = '';

            cencosData.forEach(cenco => {
                const label = document.createElement('label');
                label.className = 'flex items-center gap-2';
                label.innerHTML = `
                    <input type="checkbox" class="chk-cenco w-4 h-4" value="${cenco.codigo}" 
                           onchange="actualizarCencosSeleccionados()">
                    <span class="text-sm">${cenco.codigo} - ${cenco.nombre}</span>
                `;
                container.appendChild(label);
            });

            actualizarCencosSeleccionados();
        }

        // Actualizar CENCOS seleccionados (cuando se selecciona "Escoger")
        function actualizarCencosSeleccionados() {
            cencosSeleccionados.clear();
            document.querySelectorAll('.chk-cenco:checked').forEach(chk => {
                cencosSeleccionados.add(chk.value);
            });
            
            // Si hay CENCOS seleccionados, mostrar sección de galpones
            const seccionGalpones = document.getElementById('seccionGalpones');
            if (cencosSeleccionados.size > 0) {
                seccionGalpones.classList.remove('hidden');
                // Cargar galpones para los CENCOS seleccionados
                cargarGalponesParaCencosSeleccionados();
            } else {
                seccionGalpones.classList.add('hidden');
            }
        }
        
        // Cargar galpones para los CENCOS seleccionados (cuando se usa "Escoger")
        async function cargarGalponesParaCencosSeleccionados() {
            galponesData = {};
            galponesSeleccionados.clear();
            
            const container = document.getElementById('containerGalpones');
            if (!container) return;
            
            const tipoGalpon = document.querySelector('input[name="tipo_galpon"]:checked')?.value || 'todos';
            
            if (tipoGalpon === 'escoger') {
                container.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">Cargando galpones...</p>';
            }
            
            for (const cencoCodigo of Array.from(cencosSeleccionados)) {
                try {
                    const response = await fetch(`get_galpones.php?codigo=${encodeURIComponent(cencoCodigo)}`);
                    if (response.ok) {
                        const galpones = await response.json();
                        if (Array.isArray(galpones)) {
                            galponesData[cencoCodigo] = galpones;
                        }
                    }
                } catch (error) {
                    console.error(`Error cargando galpones para ${cencoCodigo}:`, error);
                }
            }
            
            // Verificar si hay que mostrar checkboxes
            if (tipoGalpon === 'escoger') {
                renderizarCheckboxesGalpones();
            }
        }

        // Cargar galpones por filtro (para "Todos" o "Activos")
        async function cargarGalponesPorFiltro(filtro) {
            try {
                // Cargar CENCOS según el filtro
                const response = await fetch(`get_granjas.php?filtro=${filtro}`);
                if (!response.ok) throw new Error('Error al cargar CENCOS');
                const cencos = await response.json();
                
                // Guardar todos los códigos de CENCOS
                cencosSeleccionados = new Set(cencos.map(c => c.codigo));
                cencosData = cencos;
                
                // Cargar galpones para todos estos CENCOS
                await cargarTodosGalpones(cencos.map(c => c.codigo));
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudieron cargar los galpones', 'error');
            }
        }

        // Cargar todos los galpones para una lista de CENCOS
        async function cargarTodosGalpones(cencosLista) {
            galponesData = {};
            galponesSeleccionados.clear();
            
            const container = document.getElementById('containerGalpones');
            if (!container) return;
            
            const tipoGalpon = document.querySelector('input[name="tipo_galpon"]:checked')?.value || 'todos';
            
            if (tipoGalpon === 'escoger') {
                container.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">Cargando galpones...</p>';
            }
            
            for (const cencoCodigo of cencosLista) {
                try {
                    const response = await fetch(`get_galpones.php?codigo=${encodeURIComponent(cencoCodigo)}`);
                    if (response.ok) {
                        const galpones = await response.json();
                        if (Array.isArray(galpones)) {
                            galponesData[cencoCodigo] = galpones;
                        }
                    }
                } catch (error) {
                    console.error(`Error cargando galpones para ${cencoCodigo}:`, error);
                }
            }
            
            // Verificar si hay que mostrar checkboxes
            if (tipoGalpon === 'escoger') {
                renderizarCheckboxesGalpones();
            }
        }

        // Renderizar checkboxes de galpones
        function renderizarCheckboxesGalpones() {
            const container = document.getElementById('containerGalpones');
            if (!container) return;
            
            container.innerHTML = '';
            container.classList.remove('hidden');
            
            if (Object.keys(galponesData).length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">No se encontraron galpones</p>';
                return;
            }
            
            // Obtener información de CENCOS para mostrar nombres
            const cencosMap = {};
            cencosData.forEach(c => {
                cencosMap[c.codigo] = c.nombre;
            });
            
            // Si estamos en modo "Escoger" para CENCOS, filtrar solo los CENCOS seleccionados
            const cencosParaMostrar = filtroCencosActual === 'escoger' 
                ? Array.from(cencosSeleccionados) 
                : Object.keys(galponesData);
            
            // Renderizar galpones agrupados por CENCO
            cencosParaMostrar.sort().forEach(cencoCodigo => {
                // Verificar que este CENCO tenga galpones cargados
                if (!galponesData[cencoCodigo]) return;
                
                const galpones = galponesData[cencoCodigo] || [];
                if (galpones.length === 0) return;
                
                const cencoNombre = cencosMap[cencoCodigo] || cencoCodigo;
                
                // Contenedor del CENCO
                const cencoDiv = document.createElement('div');
                cencoDiv.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50 mb-3';
                cencoDiv.setAttribute('data-cenco-container', cencoCodigo);
                
                // Header del CENCO
                const headerDiv = document.createElement('div');
                headerDiv.className = 'flex items-center justify-between mb-3 pb-2 border-b border-gray-300';
                headerDiv.innerHTML = `
                    <span class="font-semibold text-sm text-gray-800">${cencoCodigo} - ${cencoNombre}</span>
                    <span class="text-xs text-gray-500">${galpones.length} galpones</span>
                `;
                cencoDiv.appendChild(headerDiv);
                
                // Contenedor de checkboxes de galpones
                const galponesDiv = document.createElement('div');
                galponesDiv.className = 'grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2';
                galponesDiv.setAttribute('data-cenco', cencoCodigo);
                
                galpones.forEach(galponItem => {
                    const galponCodigo = galponItem.galpon || galponItem;
                    // Solo mostrar el número del galpón (sin nombre)
                    const displayText = galponCodigo;
                    
                    const label = document.createElement('label');
                    label.className = 'flex items-center gap-2 p-2 hover:bg-gray-100 rounded cursor-pointer';
                    label.innerHTML = `
                        <input type="checkbox" 
                               class="chk-galpon w-4 h-4" 
                               data-cenco="${cencoCodigo}"
                               value="${galponCodigo}" 
                               onchange="actualizarGalponesSeleccionados()">
                        <span class="text-xs">${displayText}</span>
                    `;
                    galponesDiv.appendChild(label);
                });
                
                cencoDiv.appendChild(galponesDiv);
                container.appendChild(cencoDiv);
            });
        }


        // Actualizar filtro de galpones
        function actualizarFiltroGalpones() {
            const checkedRadio = document.querySelector('input[name="tipo_galpon"]:checked');
            if (!checkedRadio) return; // Si no hay selección, no hacer nada
            
            const tipo = checkedRadio.value;
            const container = document.getElementById('containerGalpones');
            
            if (tipo === 'todos') {
                container.classList.add('hidden');
                galponesSeleccionados.clear();
            } else {
                renderizarCheckboxesGalpones();
            }
        }

        // Actualizar galpones seleccionados
        function actualizarGalponesSeleccionados() {
            galponesSeleccionados.clear();
            document.querySelectorAll('.chk-galpon:checked').forEach(chk => {
                galponesSeleccionados.add(chk.value);
            });
        }

        // Limpiar filtros
        function limpiarFiltros() {
            document.getElementById('filtroFechaInicio').value = '';
            document.getElementById('filtroFechaFin').value = '';
            document.getElementById('filtroFormato').value = 'pdf';
            
            // Deseleccionar radio buttons de CENCOS
            document.querySelectorAll('input[name="filtro_cencos"]').forEach(radio => {
                radio.checked = false;
            });
            filtroCencosActual = '';
            cencosSeleccionados.clear();
            document.getElementById('containerCencos').classList.add('hidden');
            document.getElementById('seccionGalpones').classList.add('hidden');
            
            // Deseleccionar radio buttons de galpones
            document.querySelectorAll('input[name="tipo_galpon"]').forEach(radio => {
                radio.checked = false;
            });
            galponesSeleccionados.clear();
            galponesData = {};
            document.getElementById('containerGalpones').classList.add('hidden');
        }

        // Generar reporte
        async function generarReporte() {
            const fechaInicio = document.getElementById('filtroFechaInicio').value;
            const fechaFin = document.getElementById('filtroFechaFin').value;
            const formato = document.getElementById('filtroFormato').value;

            if (!fechaInicio || !fechaFin) {
                Swal.fire('Validación', 'Debe seleccionar las fechas de inicio y fin', 'warning');
                return;
            }

            if (fechaInicio > fechaFin) {
                Swal.fire('Validación', 'La fecha inicio debe ser menor o igual a la fecha fin', 'warning');
                return;
            }

            // Construir parámetros
            const params = new URLSearchParams();
            params.append('fecha_inicio', fechaInicio);
            params.append('fecha_fin', fechaFin);
            params.append('formato', formato);

            // CENCOS
            if (filtroCencosActual === 'todos' || filtroCencosActual === 'activos') {
                params.append('cencos', 'todos');
            } else {
                // Escoger
                if (cencosSeleccionados.size === 0 || cencosSeleccionados.size === cencosData.length) {
                    params.append('cencos', 'todos');
                } else {
                    params.append('cencos', Array.from(cencosSeleccionados).join(','));
                }
            }

            // Galpones
            const tipoGalpon = document.querySelector('input[name="tipo_galpon"]:checked').value;
            if (tipoGalpon === 'todos') {
                params.append('galpones', 'todos');
            } else {
                const totalGalpones = document.querySelectorAll('.chk-galpon').length;
                if (galponesSeleccionados.size === 0 || galponesSeleccionados.size === totalGalpones) {
                    params.append('galpones', 'todos');
                } else {
                    params.append('galpones', Array.from(galponesSeleccionados).join(','));
                }
            }
            // Abrir reporte en nueva ventana
            const url = `generar_reporte_comparativo.php?${params.toString()}`;
            window.open(url, '_blank');
        }

        // Event listeners
        document.getElementById('btnGenerarReporte').addEventListener('click', generarReporte);
        document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);

        // No cargar nada al iniciar - el usuario debe seleccionar una opción
    </script>

    <!-- FOOTER -->
    <div class="text-center mt-12 mb-4">
        <p class="text-gray-500 text-sm">
            Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> -
            © <span id="currentYear"></span>
        </p>
    </div>

    <script>
        // Establecer año actual en el footer
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>

</body>

</html>
