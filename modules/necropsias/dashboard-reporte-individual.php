<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Necropsia</title>
    <link href="../../css/output.css" rel="stylesheet">
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
    <div class="container mx-auto px-6 py-12">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200 mb-8">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h3 class="text-lg font-semibold text-gray-800">Registros de Necropsia</h3>
                    <input type="text" id="searchReportes" placeholder="Buscar por código de lote..." class="w-full sm:w-72 px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
        document.getElementById('currentYear').textContent = new Date().getFullYear();

        let paginaActual = 1;
        let totalPaginas = 1;

        function cargarLotes(page = 1, query = "") {
            const contenedor = document.getElementById("reportes-lista");
            contenedor.innerHTML = '<div class="text-center py-10 text-gray-600">Cargando lotes...</div>';

            const formData = new FormData();
            formData.append('draw', 1);
            formData.append('start', (page - 1) * 10);
            formData.append('length', 10);
            if (query) formData.append('search[value]', query);

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
                            // tfectra viene ya formateado como DD/MM/YYYY desde el backend.
                            // Evitar new Date("DD/MM/YYYY") porque en JS es ambiguo y puede dar "Invalid Date".
                            const fechaNecropsia = lote.tfectra || '-';
                            const fechaRegistro = lote.fecha_registro || '-';
                            const horaRegistro = (lote.ttime ? String(lote.ttime).substring(0, 5) : '-');

                            const fectraParts = lote.tfectra.split('/');
                            const fectraUrl = `${fectraParts[2]}-${fectraParts[1].padStart(2, '0')}-${fectraParts[0].padStart(2, '0')}`; // Convierte "09/01/2026" → "2026-01-09"


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
                                        <p class="font-semibold text-gray-900">${lote.tcencos}</p>
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
                                        <p class="font-semibold text-gray-900">${fechaRegistro}</p>
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

        document.addEventListener("DOMContentLoaded", () => cargarLotes(1, ""));

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