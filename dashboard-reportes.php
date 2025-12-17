<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Reportes</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
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

        /* Estilo para ítems de reporte (simulando filas de tabla en móvil) */
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

        <!-- TÍTULO PRINCIPAL -->
        <div class="content-header max-w-7xl mx-auto mb-8">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-4xl">📊</span>
                <h1 class="text-3xl font-bold text-gray-800">Reportes de muestras</h1>
            </div>
            <p class="text-gray-600 text-sm">Historial de muestras registradas por su cuenta</p>
        </div>

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
                <!-- Ejemplo visual (solo para diseño; será reemplazado por JS) -->
                <!--
                <div class="report-item">
                    <div>
                        <p class="font-medium text-gray-800">ENV-2025-001</p>
                        <p class="text-sm text-gray-500">12 registros • 2025-04-10</p>
                    </div>
                    <button class="text-blue-600 hover:text-blue-800 font-medium mt-2 sm:mt-0">Ver detalle</button>
                </div>
                -->
            </div>

            <!-- PAGINACIÓN -->
            <div id="paginacion-controles" class="flex flex-wrap justify-center gap-1"></div>

        </div>

        <!-- FOOTER -->
        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © 2025
            </p>
        </div>

    </div>
    <!-- Modal para enviar correo desde el sistema -->
    <div id="modalCorreo" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4 shadow-xl">
            <h3 class="text-lg font-bold mb-4">Enviar reporte por correo</h3>

            <input type="hidden" id="codigoEnvio" value="">

            <label class="block text-sm text-gray-700 mb-1">Destinatario *</label>
            <select id="destinatarioSelect"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 mb-4"
                required>
                <option value="">Seleccione un contacto</option>
                <!-- Se llenará con JS -->
            </select>

            <!-- Opción para otro correo -->
            <div class="flex items-center mb-3">
                <input type="checkbox" id="otroCorreoCheck" class="mr-2">
                <label for="otroCorreoCheck" class="text-sm text-gray-700">Otro correo</label>
            </div>
            <input type="email" id="otroCorreo" placeholder="Ingrese otro correo"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-4 hidden" disabled>

            <label class="block text-sm text-gray-700 mb-1">Asunto *</label>
            <input type="text" id="asuntoCorreo" class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-4"
                required>

            <label class="block text-sm text-gray-700 mb-1">Mensaje *</label>
            <textarea id="mensajeCorreo" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-4"
                required></textarea>

            <div class="flex gap-2">
                <button type="button" onclick="cerrarModalCorreo()"
                    class="flex-1 py-2 bg-gray-200 rounded">Cancelar</button>
                <button type="button" onclick="enviarCorreoDesdeSistema()"
                    class="flex-1 py-2 bg-blue-600 text-white rounded">Enviar</button>
            </div>
            <p id="mensajeResultado" class="mt-2 text-sm text-center min-h-[20px]"></p>
        </div>
    </div>
    </div>
    <script src="reportes.js"></script>
</body>

</html>