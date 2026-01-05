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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Reportes</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap 5 JS + Popper (necesario para modales) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Font Awesome para íconos -->
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
        }

        /* Asegura que el contenedor de botones no desborde en móvil */
        @media (max-width: 639px) {
            .report-card {
                padding-right: 50px !important;
            }
        }
    </style>

</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- CONTENEDOR PRINCIPAL -->
        <div class="max-w-7xl mx-auto">

            <!-- BÚSQUEDA -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200 mb-8">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h3 class="text-lg font-semibold text-gray-800">Registros Enviados</h3>
                    <div class="w-full sm:w-auto">
                        <input type="text" id="searchReportes" placeholder="Buscar por código de envío..."
                            maxlength="20"
                            class="w-full sm:w-72 px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    </div>
                </div>
            </div>

            <!-- LISTA DE REPORTES (dinámico) -->
            <div id="reportes-lista" class="space-y-3 mb-10">
              
            </div>

            <!-- PAGINACIÓN -->
            <div id="paginacion-controles" class="flex flex-wrap justify-center gap-1"></div>

        </div>

        <!-- FOOTER -->
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
    <!-- Modal Bootstrap -->
    <div class="modal fade" id="modalCorreo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="max-height: 85vh; display: flex; flex-direction: column;">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar reporte por correo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body flex-grow-1 overflow-auto">
                    <input type="hidden" id="codigoEnvio" value="">

                    <!-- DESTINATARIOS -->
                    <div class="mb-3">
                        <label class="form-label">Para (destinatarios) *</label>
                        <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center"
                            id="btnMostrarSelect">
                            <i class="fas fa-plus me-1"></i> Seleccionar contactos
                        </button>

                        <!-- Select oculto con contactos -->
                        <select id="destinatarioSelect" multiple class="form-select mt-2" size="6"
                            style="display:none;"></select>

                        <!-- Lista de destinatarios elegidos -->
                        <div id="listaPara" class="mt-2 small"></div>
                    </div>

                    <!-- ASUNTO Y MENSAJE -->
                    <div class="mb-3">
                        <label class="form-label">Asunto *</label>
                        <input type="text" id="asuntoCorreo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mensaje *</label>
                        <textarea id="mensajeCorreo" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- ARCHIVOS -->
                    <div class="mt-4">
                        <p class="fw-bold mb-2">Archivos adjuntos:</p>
                        <div id="listaArchivos" class="mb-3"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Agregar más archivos</label>
                        <input type="file" id="archivosAdjuntos" multiple class="form-control">
                    </div>

                    <div id="mensajeResultado" class="text-center small min-vh-25"></div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="enviarCorreoDesdeSistema()">Enviar</button>
                </div>
            </div>
        </div>
    </div>
   
    <script src="../../assets/js/reportes/reportes.js"></script>
</body>

</html>