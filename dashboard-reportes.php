<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - reportes</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome para iconos -->
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


        <div class="min-h-screen bg-gray-50">
            <!-- HEADER -->
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">📊 Reportes de Registros</h1>
                <p class="text-gray-600 text-sm">Historial de muestras registradas por su cuenta</p>
            </div>

            <!-- CONTENIDO -->
            <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                <!-- HEADER REPORTES -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">Registros Enviados</h3>
                    <div class="flex w-full sm:w-auto">
                        <input type="text" id="searchReportes" placeholder="Buscar por código de envío..." maxlength="20"
                            class="px-3 sm:px-4 py-2 sm:py-2.5 border-2 border-gray-900 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full sm:w-72">
                    </div>
                </div>

                <!-- LISTA REPORTES -->
                <div id="reportes-lista" class="space-y-3 sm:space-y-5">
                    <!-- Se llena dinámicamente -->
                </div>

                <!-- PAGINACIÓN -->
                <div id="paginacion-controles" class="flex flex-wrap justify-center items-center gap-2 sm:gap-4 mt-6 sm:mt-8"></div>
            </div>
        </div>



        <!-- Footer -->
        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © 2025
            </p>
        </div>

    </div>


    <script src="reportes.js"></script>

</body>

</html>