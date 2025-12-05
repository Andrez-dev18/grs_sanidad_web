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
    <title>Dashboard - Respuesta lab</title>

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/output.css">

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


        <!-- VISTA RESPUESTA LABORATORIO -->
        <div class="container-main">
            <!-- HEADER -->
            <div class="header-section">
                <h1>📨 Respuesta de Laboratorio</h1>
                <p>Adjunte los resultados enviados por el laboratorio</p>
            </div>

            <!-- CONTENIDO PRINCIPAL -->
            <div class="content-wrapper">
                <!-- SIDEBAR - ÓRDENES PENDIENTES -->
                <div class="sidebar-panel">
                    <div class="sidebar-header">
                        <h3>Órdenes Pendientes</h3>
                        <input type="text" class="search-input" placeholder="Buscar..."
                            onkeyup="filterOrders(this.value)">
                    </div>
                    <div id="pendingOrdersList" class="orders-list-container">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>

                <!-- MAIN CONTENT AREA -->
                <div class="main-content">
                    <!-- EMPTY STATE -->
                    <div id="emptyStatePanel" class="empty-state">
                        <h3>Seleccione una orden</h3>
                        <p>Elija una orden de la lista para adjuntar su respuesta</p>
                    </div>

                    <!-- DETAIL PANEL (Hidden by default) -->
                    <div id="responseDetailPanel" class="detail-panel hidden">
                        <div class="detail-card">
                            <!-- HEADER DETALLE -->
                            <div class="detail-header">
                                <div class="detail-header-top">
                                    <div>
                                        <h2 id="detailCodigo" class="detail-codigo">SAN-000000</h2>
                                        <span class="badge badge-pending">Pendiente de Respuesta</span>
                                    </div>
                                    <div class="detail-meta">
                                        <span id="detailFecha">📅 01/01/2024</span>
                                        <span id="detailLab">🔬 Laboratorio</span>
                                    </div>
                                </div>
                            </div>

                            <!-- UPLOAD SECTION -->
                            <div class="upload-section">
                                <h3>Adjuntar Respuesta</h3>
                                <p>Suba el informe de resultados (PDF, Imagen, Correo)</p>

                                <!-- DROP ZONE -->
                                <div class="drop-zone" id="dropZone"
                                    onclick="document.getElementById('fileInput').click()">
                                    <div class="drop-zone-icon">☁️</div>
                                    <p>Arrastre archivos aquí o <span class="text-primary">explore</span></p>
                                    <input type="file" id="fileInput" multiple onchange="handleFiles(this.files)">
                                </div>

                                <!-- FILE PREVIEW -->
                                <div id="filePreviewList" class="file-preview-list">
                                    <!-- Archivos adjuntos -->
                                </div>

                                <!-- COMMENTS -->
                                <div class="form-group">
                                    <label for="responseComments">Comentarios Adicionales</label>
                                    <textarea id="responseComments" rows="3"
                                        placeholder="Observaciones sobre los resultados..."></textarea>
                                </div>

                                <!-- ACTION BUTTONS -->
                                <div class="action-buttons">
                                    <button class="btn btn-secondary" onclick="clearSelection()">Cancelar</button>
                                    <button class="btn btn-primary" onclick="saveResponse()">💾 Guardar
                                        Respuesta</button>
                                </div>
                            </div>
                        </div>
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

    <script src="funciones.js"></script>
    <script src="planificacion.js"></script>
    <script src="manteminiento.js"></script>
</body>

</html>