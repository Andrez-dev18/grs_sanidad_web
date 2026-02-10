<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>var u="../../login.php";if(window.top!==window.self){window.top.location.href=u;}else{window.location.href=u;}</script>';
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
    <title>Reportes de Necropsias</title>

    <!-- Tailwind CSS -->
    <link href="../../css/output.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            display: flex;
            flex-direction: column;
        }

        .container-fluid {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .tab-button {
            transition: all 0.2s ease;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        .tab-button.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }

        .tab-button:not(.active) {
            color: #6b7280;
        }

        .tab-button:not(.active):hover {
            color: #374151;
        }

        .tab-content {
            display: none;
            flex: 1;
            min-height: 0;
        }

        .tab-content.active {
            display: block;
        }

        .tabs-container {
            background: white;
            border-bottom: 1px solid #e5e7eb;
        }

        .tabs-nav {
            display: flex;
            justify-content: center;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- TABS -->
        <div class="mx-5 mb-6">
            <div class="tabs-container">
                <nav class="tabs-nav" role="tablist">
                    <button id="tabIndividual"
                        class="tab-button active"
                        aria-selected="true" onclick="cambiarTab('tabIndividual', 'tabComparativo', 'contenidoIndividual', 'contenidoComparativo')">
                        Reporte Individual
                    </button>
                    <button id="tabComparativo"
                        class="tab-button"
                        aria-selected="false" onclick="cambiarTab('tabComparativo', 'tabIndividual', 'contenidoComparativo', 'contenidoIndividual')">
                        Reporte Comparativo
                    </button>
                </nav>
            </div>
        </div>

        <!-- CONTENIDO DE TABS -->
        <div id="contenidoIndividual" class="tab-content active">
            <iframe id="iframeIndividual" src="dashboard-reporte-individual.php" 
                    style="width: 100%; height: 100%; border: none; background: transparent;"></iframe>
        </div>

        <div id="contenidoComparativo" class="tab-content">
            <iframe id="iframeComparativo" src="dashboard-reporte-comparativo.php" 
                    style="width: 100%; height: 100%; border: none; background: transparent;"></iframe>
        </div>
    </div>

    <script>
        function cambiarTab(tabActivoId, tabInactivoId, contenidoActivoId, contenidoInactivoId) {
            // Cambiar clases de los botones
            const tabActivo = document.getElementById(tabActivoId);
            const tabInactivo = document.getElementById(tabInactivoId);

            tabActivo.classList.add('active');
            tabActivo.classList.remove('border-transparent', 'text-gray-600');
            tabInactivo.classList.remove('active');
            tabInactivo.classList.add('border-transparent', 'text-gray-600');

            // Cambiar contenido visible
            document.getElementById(contenidoActivoId).classList.add('active');
            document.getElementById(contenidoActivoId).classList.remove('hidden');
            document.getElementById(contenidoInactivoId).classList.remove('active');
            document.getElementById(contenidoInactivoId).classList.add('hidden');

            // Actualizar aria-selected para accesibilidad
            tabActivo.setAttribute('aria-selected', 'true');
            tabInactivo.setAttribute('aria-selected', 'false');

        }
    </script>
</body>

</html>
