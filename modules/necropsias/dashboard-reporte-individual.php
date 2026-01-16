<?php

session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

//ruta relativa a la conexion
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Necropsia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .report-item {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem;
            background: white;
            transition: background 0.2s;
        }

        .report-item:hover {
            background-color: #f9fafb;
        }

        @media (min-width: 640px) {
            .report-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem 1.5rem;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-3 py-6">
        <div class="max-w-7xl mx-auto">

            <!-- CARD FILTROS PLEGABLE -->
            <div class="mb-6 bg-white border rounded-2xl shadow-sm overflow-hidden">

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
                <div id="contenidoFiltros" class="px-6 pb-6 pt-4 hidden">

                    <?php
                    if ($conn) {
                        // 1. Ejecutar el SET para evitar errores de GROUP BY
                        $conn->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

                        // 2. Consulta SQL optimizada
                        // Seleccionamos SOLO codigo y nombre.
                        // Usamos la lógica de 'edad' (b.edad) SOLO en el WHERE para filtrar, pero no la traemos en el SELECT.
                        $sqlGranjas = "SELECT codigo, nombre
                   FROM ccos AS a 
                   LEFT JOIN (
                        SELECT a.tcencos, a.tcodint, a.tcodigo, DATEDIFF(NOW(), MIN(a.fec_ing))+1 as edad 
                        FROM maes_zonas AS a 
                        USE INDEX(tcencos,tcodint,tcodigo) 
                        WHERE a.tcodigo IN ('P0001001','P0001002')  
                        GROUP BY tcencos
                   ) AS b ON a.codigo = b.tcencos  
                   WHERE (LEFT(codigo,1) IN ('6','5') 
                   AND RIGHT(codigo,3)<>'000' 
                   AND swac='A' 
                   AND LENGTH(codigo)=6 
                   AND LEFT(codigo,3)<>'650'
                   AND LEFT(codigo,3) <= '667')
                   AND IF(b.edad IS NULL, '0', b.edad) <> '0'
                   ORDER BY nombre ASC";

                        $resultadoGranjas = $conn->query($sqlGranjas);
                    }
                    ?>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                            <input type="date" id="filtroFechaInicio"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                            <input type="date" id="filtroFechaFin"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Granja</label>
                            <select id="filtroGranja" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="">Seleccionar</option>

                                <?php if (isset($resultadoGranjas) && $resultadoGranjas): ?>
                                    <?php while ($fila = $resultadoGranjas->fetch_assoc()): ?>
                                        <?php
                                        // 1. Convertimos caracteres especiales primero
                                        $nombreCompleto = utf8_encode($fila['nombre']);

                                        // 2. LOGICA DE LIMPIEZA:
                                        // Explotamos el string usando 'C=' como separador y tomamos la parte [0] (la izquierda)
                                        $nombreCorto = explode('C=', $nombreCompleto)[0];

                                        // 3. Quitamos espacios en blanco sobrantes al final (el espacio antes del C=)
                                        $nombreCorto = trim($nombreCorto);

                                        // 4. Sanear para HTML
                                        $textoMostrar = htmlspecialchars($nombreCorto);
                                        $codigo = htmlspecialchars($fila['codigo']);
                                        ?>
                                        <option value="<?php echo $codigo; ?>">
                                            <?php echo $textoMostrar; ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="" disabled>Sin datos disponibles</option>
                                <?php endif; ?>

                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Num. Reg</label>
                            <input type="text" id="searchReportes" placeholder="Buscar por código de lote..." class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        </div>

                    </div>

                    <!-- ACCIONES -->
                    <div class="mt-6 flex flex-wrap justify-end gap-4">

                        <button type="button" id="btnAplicarFiltros"
                            class="hidden px-6 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Filtrar
                        </button>

                        <button type="button" id="btnLimpiarFiltros"
                            class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200">
                            Limpiar
                        </button>
                    </div>

                </div>
            </div>
            <div id="reportes-lista" class="space-y-3 mb-10">
                <div class="text-center py-10 text-gray-600">Cargando lotes...</div>
            </div>

            <div id="paginacion-controles" class="flex flex-wrap justify-center gap-1"></div>
        </div>

        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © <span id="currentYear"></span>
            </p>
        </div>
    </div>

    <script>
        function toggleFiltros() {
            const contenido = document.getElementById('contenidoFiltros');
            const icono = document.getElementById('iconoFiltros');

            contenido.classList.toggle('hidden');
            icono.classList.toggle('rotate-180');
        }

        document.getElementById('currentYear').textContent = new Date().getFullYear();

        let paginaActual = 1;
        let totalPaginas = 1;

        function cargarLotes(page = 1) {
            const contenedor = document.getElementById("reportes-lista");
            contenedor.innerHTML = '<div class="text-center py-10 text-gray-600">Cargando lotes...</div>';

            // 1. Obtener valores de los filtros
            const query = document.getElementById("searchReportes").value.trim();
            const fechaInicio = document.getElementById("filtroFechaInicio").value;
            const fechaFin = document.getElementById("filtroFechaFin").value;
            const granja = document.getElementById("filtroGranja").value;

            const formData = new FormData();
            formData.append('draw', 1);
            formData.append('start', (page - 1) * 10);
            formData.append('length', 10);

            // 2. Enviar filtros al PHP
            if (query) formData.append('search[value]', query);
            if (fechaInicio) formData.append('fecha_inicio', fechaInicio);
            if (fechaFin) formData.append('fecha_fin', fechaFin);
            if (granja) formData.append('granja', granja);

            fetch('cargar_lotes_necropsias.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    let html = '';
                    if (result.data.length === 0) {
                        html = '<div class="text-center py-10 text-gray-600">No se encontraron lotes</div>';
                    } else {
                        result.data.forEach(lote => {
                            const codigoLote = String(lote.tnumreg).padStart(6, '0');
                            const fechaNecropsia = lote.tfectra;
                            const horaRegistro = lote.ttime.substring(0, 5);

                            const fectraParts = lote.tfectra.split('/');
                            const fectraUrl = `${fectraParts[2]}-${fectraParts[1].padStart(2, '0')}-${fectraParts[0].padStart(2, '0')}`; // Convierte "09/01/2026" → "2026-01-09"

                            let nombreGranja = lote.tcencos;
                            if (nombreGranja.includes('C=')) {
                                nombreGranja = nombreGranja.split('C=')[0].trim();
                            }

                            html += `
                        <div class="report-item">
                            <div class="flex-1">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Num reg:</p>
                                        <p class="font-semibold text-gray-900">${codigoLote}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Granja:</p>
                                        <p class="font-semibold text-gray-900">${nombreGranja}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Campañia:</p>
                                        <p class="font-semibold text-gray-900">${lote.tcampania}</p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Galpon:</p>
                                        <p class="font-semibold text-gray-900">${lote.tgalpon}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Fecha de registro:</p>
                                        <p class="font-semibold text-gray-900">${fechaNecropsia}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Hora de Registro:</p>
                                        <p class="font-semibold text-gray-900">${horaRegistro}</p>
                                    </div>
                                    
                                    
                                    <div>
                                        <p class="text-sm text-gray-600">Registrado por:</p>
                                        <p class="font-semibold text-gray-900">${lote.tuser}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 mt-4 sm:mt-0">
                                <a href="generar_reporte_tabla.php?tipo=1&numreg=${lote.tnumreg}&granja=${encodeURIComponent(lote.tgranja)}&galpon=${encodeURIComponent(lote.tgalpon)}&fectra=${fectraUrl}" 
                                   target="_blank" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 text-center">
                                    <i class="fas fa-file-pdf mr-2"></i>PDF Tabla
                                </a>
                                <button onclick="verificarYGenerarPDF('${lote.tgranja}', ${lote.tnumreg}, '${lote.tfectra}')" 
                                        class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 text-center">
                                    <i class="fas fa-images mr-2"></i>PDF Imágenes
                                </button>
                            </div>
                        </div>`;
                        });
                    }

                    contenedor.innerHTML = html;
                    totalPaginas = Math.ceil(result.recordsFiltered / 10);
                    paginaActual = page;
                    renderizarPaginacion();
                })
                .catch(err => {
                    contenedor.innerHTML = '<div class="text-center py-10 text-red-600">Error al cargar lotes.</div>';
                    console.error(err);
                });
        }

        function renderizarPaginacion() {
            const paginacion = document.getElementById("paginacion-controles");
            if (totalPaginas <= 1) {
                paginacion.innerHTML = "";
                return;
            }

            let html = "";
            if (paginaActual > 1) {
                html += `<button class="bg-white text-gray-700 font-medium py-2 px-3 sm:px-4 rounded-md border border-gray-300 hover:bg-gray-50 transition-colors text-sm sm:text-base"
                          onclick="cargarLotes(${paginaActual - 1}, document.getElementById('searchReportes').value)">← Anterior</button>`;
            }
            html += `<span class="text-gray-600 text-xs sm:text-sm whitespace-nowrap mx-4">Página ${paginaActual} de ${totalPaginas}</span>`;
            if (paginaActual < totalPaginas) {
                html += `<button class="bg-white text-gray-700 font-medium py-2 px-3 sm:px-4 rounded-md border border-gray-300 hover:bg-gray-50 transition-colors text-sm sm:text-base"
                          onclick="cargarLotes(${paginaActual + 1}, document.getElementById('searchReportes').value)">Siguiente →</button>`;
            }
            paginacion.innerHTML = html;
        }

        document.getElementById("searchReportes").addEventListener("input", function() {
            cargarLotes(1, this.value.trim());
        });

        //EVENT LISTENERS (Activar búsqueda al cambiar filtros)
        document.addEventListener('DOMContentLoaded', function() {

            // Carga inicial
            cargarLotes(1);

            // Evento para el buscador de texto (Num Reg)
            const inputSearch = document.getElementById("searchReportes");
            if (inputSearch) {
                inputSearch.addEventListener("input", function() {
                    cargarLotes(1);
                });
            }

            // Eventos para los filtros de fecha y granja
            const filtros = ['filtroFechaInicio', 'filtroFechaFin', 'filtroGranja'];
            filtros.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.addEventListener('change', function() {
                        cargarLotes(1);
                    });
                }
            });

            const btnLimpiar = document.getElementById('btnLimpiarFiltros');

            if (btnLimpiar) {
                btnLimpiar.addEventListener('click', function() {
                    // A. Limpiar los valores de los inputs
                    document.getElementById('filtroFechaInicio').value = '';
                    document.getElementById('filtroFechaFin').value = '';
                    document.getElementById('filtroGranja').value = '';

                    // B. Limpiar también el buscador de texto (importante)
                    if (inputSearch) {
                        inputSearch.value = '';
                    }

                    // C. Recargar la lista "limpia" (Página 1, sin filtros)
                    cargarLotes(1);
                });
            }

        });

        function verificarYGenerarPDF(granja, numreg, fectra) {
            // Primero verificar si hay imágenes
            fetch(`generar_reporte_necropsia.php?granja=${encodeURIComponent(granja)}&numreg=${numreg}&fectra=${encodeURIComponent(fectra)}&check=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.tiene_imagenes) {
                        // Si hay imágenes, abrir el PDF normalmente
                        window.open(`generar_reporte_necropsia.php?granja=${encodeURIComponent(granja)}&numreg=${numreg}&fectra=${encodeURIComponent(fectra)}`, '_blank');
                    } else {
                        // Si no hay imágenes, mostrar mensaje con SweetAlert
                        Swal.fire({
                            icon: 'warning',
                            title: 'No se registró imágenes',
                            text: 'Este registro de necropsia no tiene imágenes asociadas.',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#3b82f6'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error al verificar imágenes:', error);
                    // En caso de error, intentar abrir el PDF de todas formas
                    window.open(`generar_reporte_necropsia.php?granja=${encodeURIComponent(granja)}&numreg=${numreg}&fectra=${encodeURIComponent(fectra)}`, '_blank');
                });
        }
    </script>
</body>

</html>