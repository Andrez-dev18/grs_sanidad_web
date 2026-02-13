<?php

session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) {
            window.top.location.href = "login.php";
        } else {
            window.location.href = "login.php";
        }
    </script>';
    exit();
}

//ruta relativa a la conexion
include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// === OBTENER ROL DEL USUARIO ===
$codigoUsuario = $_SESSION['usuario'] ?? null;
$isTransportista = false; // por defecto NO es transportista

if ($codigoUsuario) {
    $sql = "SELECT rol_sanidad FROM usuario WHERE codigo = ? AND estado = 'A'";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $codigoUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $rol = (trim($row['rol_sanidad'] ?? 'user'));
            $isTransportista = ($rol === 'TRANSPORTE');
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión de Pollo - Múltiples Dashboards">
    <title>Sistema de Sanidad- Dashboard</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/output.css">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard-sstyles.css">

    <style>
        /* Sidebar */
        body {
            background: #f8f9fa;
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }

        :root {
            --sidebar-width: 300px;
            --sidebar-mini-width: 112px;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            transition: transform 0.3s ease, width 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            /* Desktop: mini sidebar (NO se oculta por completo) */
            width: var(--sidebar-mini-width);
            overflow: visible;
        }

        .toggle-sidebar-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .toggle-sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .sidebar.open {
            transform: translateX(0);
        }

        /* Scroll del sidebar: fondo transparente */
        .sidebar-nav {
            scrollbar-color: rgba(255, 255, 255, 0.4) transparent;
            scrollbar-width: thin;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.35);
            border-radius: 4px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .menu-item {
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 48px;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: none;
        }

        .menu-item.active {
            background: rgba(255, 255, 255, 0.15);
            border-left: 4px solid #60a5fa;
        }

        .menu-item>span {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 1;
        }

        .menu-item>span>span {
            display: block;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            line-height: 1.3;
            text-align: left;
        }

        .menu-item>span>i {
            flex-shrink: 0;
            width: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-item>i {
            flex-shrink: 0;
            margin-left: auto;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-link.active,
        .menu-item.active,
        .submenu-toggle.active {
            color: white !important;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.6);
            font-weight: 600;
        }

        .menu-item.active,
        .submenu-toggle.active {
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 4 6px 18px rgba(0, 0, 0, 0.25);
        }

        .submenu.hidden {
            display: none !important;
        }

        .submenu:not(.hidden) {
            display: block;
        }

        .menu-link {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            line-height: 1.4;
        }

        .submenu-toggle {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            line-height: 1.3;
        }

        /* Content area - ¡CORRECCIÓN CLAVE! */
        .content-wrapper {
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
        }

        @media (min-width: 1024px) {
            .sidebar {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: none;
            }

            .menu-toggle-mobile {
                display: none;
            }

            /* Desktop: cuando sidebar está colapsado, usar ancho mini */
            .content-wrapper.sidebar-collapsed {
                margin-left: var(--sidebar-mini-width);
            }
        }

        @media (max-width: 1023px) {
            .content-wrapper {
                margin-left: 0 !important;
                width: 100%;
                max-width: 100%;
            }

            .content-wrapper.sidebar-collapsed {
                margin-left: 0 !important;
            }

            /* Móvil: sidebar oculto por defecto, se abre con overlay */
            .sidebar.collapsed {
                transform: translateX(-100%);
                width: var(--sidebar-width);
                visibility: visible;
            }

            .sidebar:not(.collapsed) {
                transform: translateX(0);
                box-shadow: 8px 0 24px rgba(0, 0, 0, 0.15);
            }

            /* Iframe y main ocupan todo el ancho en móvil */
            main,
            #dashboardFrame {
                width: 100% !important;
                max-width: 100%;
            }

            /* En móvil el modal de calendario puede ocupar toda la pantalla (sidebar está oculto) */
            #dashboardFrame.iframe-fullscreen {
                left: 0 !important;
                width: 100vw !important;
            }
        }

        /* Animaciones */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slideIn 0.3s ease;
        }

        /* Dashboard iframe */
        #dashboardFrame {
            width: 100%;
            min-height: calc(100vh - 80px);
            border: none;
            background: #f9fafb;
        }

        /* Iframe a pantalla completa cuando un modal interno (ej. Calendario) está abierto; no cubre el sidebar para no cambiarlo */
        #dashboardFrame.iframe-fullscreen {
            position: fixed !important;
            top: 0 !important;
            left: var(--sidebar-width) !important;
            width: calc(100vw - var(--sidebar-width)) !important;
            height: 100vh !important;
            min-height: 100vh !important;
            z-index: 99999 !important;
            transition: left 0.3s ease, width 0.3s ease;
        }

        .content-wrapper.sidebar-collapsed #dashboardFrame.iframe-fullscreen {
            left: var(--sidebar-mini-width) !important;
            width: calc(100vw - var(--sidebar-mini-width)) !important;
        }

        /* Loading */
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* ===============================
           MINI SIDEBAR (desktop)
           Icono arriba + texto debajo
        ================================ */
        @media (min-width: 1024px) {
            .sidebar.collapsed .sidebar-header {
                padding: 16px 12px !important;
            }

            .sidebar.collapsed .sidebar-logo-text {
                display: none;
            }

            .sidebar.collapsed .sidebar-nav {
                padding: 12px !important;
            }

            .sidebar.collapsed .nav-section-title {
                display: none;
            }

            .sidebar.collapsed .menu-group {
                margin-top: 8px;
            }

            .sidebar.collapsed .menu-item {
                padding: 10px 8px !important;
                justify-content: center;
            }

            .sidebar.collapsed .menu-item>span {
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 6px;
            }

            .sidebar.collapsed .menu-item>span>i {
                font-size: 18px;
                width: auto;
            }

            .sidebar.collapsed .menu-item>span>span {
                text-align: center;
                font-size: 11px;
                line-height: 1.1;
                /* Evitar desbordes en nombres largos */
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: calc(var(--sidebar-mini-width) - 24px);
            }

            /* Ocultar chevron lateral en mini (se expande igual al click) */
            .sidebar.collapsed .menu-item>i {
                display: none;
            }

            /* Submenú en mini: centrado y sin sangría */
            .sidebar.collapsed .submenu {
                padding-left: 0 !important;
                margin-top: 6px !important;
            }

            .sidebar.collapsed .submenu-link {
                text-align: center;
                font-size: 12px;
                padding: 6px 8px;
                border-radius: 8px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Sidebar: por defecto oculto en pantallas pequeñas (clase collapsed desde el HTML) -->
    <aside id="sidebar" class="sidebar collapsed">
        <!-- Logo/Header -->
        <div class="sidebar-header p-6 border-b border-blue-700">
            <div class="flex items-center justify-between">
                <div class="sidebar-logo flex items-center gap-3">
                    <div class="sidebar-logo-icon w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <i class="fas fa-heartbeat text-blue-700"></i>
                    </div>
                    <div class="sidebar-logo-text leading-tight">
                        <span class="title text-white font-bold text-lg block">Sistema Sanidad</span>
                        <span class="subtitle text-blue-300 text-xs block">Dashboard Ejecutivo</span>
                    </div>
                </div>
                <button onclick="toggleSidebarCollapse()" class="lg:hidden text-white hover:text-blue-200">
                    <i class="fas fa-times text-x"></i>
                </button>
            </div>
        </div>

        <!-- Menu -->
        <!-- Navbar actualizado -->
        <nav class="sidebar-nav p-4 space-y-6 overflow-y-auto" style="max-height: calc(100vh - 130px);">
            <?php if (!$isTransportista): ?>
                <div class="nav-section">
                    <div class="nav-section-title text-blue-300 text-xs uppercase font-semibold mb-2 px-3 flex items-center gap-2">
                        <i class="fas fa-chart-bar"></i> <span>Análisis</span>
                    </div>
                    <div class="menu-group">
                        <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                            onclick="toggleSubmenu('submenu-dashboard')">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-chart-line"></i>
                                <span class="font-medium">Dashboards</span>
                            </span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <div id="submenu-dashboard" class="submenu hidden pl-10 mt-2 space-y-2">
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/dashboard/dashboard-dashboard.php', '📊 Dashboard',  'Resumen visual de los datos registrados en el sistema')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">General</a>

                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/dashboard-indicadores/dashboard-indicadores.php', '📊 Dashboard Indicadores', 'Resumen visual de los datos registrados en el sistema')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Indicadores</a>

                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/tracking/dashboard/dashboard-tracking.php','🧪 Dashboard Tracking', 'Resumen visual de los estados de entrega y pedidos.')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Tracking</a>

                        </div>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title text-blue-300 text-xs uppercase font-semibold mb-2 px-3 flex items-center gap-2">
                        <i class="fas fa-flask"></i> <span>Gestión</span>
                    </div>
                    <div class="menu-group">
                        <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                            onclick="toggleSubmenu('submenu-muestras')">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-vial"></i>
                                <span class="font-medium">Muestras</span>
                            </span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <div id="submenu-muestras" class="submenu hidden pl-10 mt-2 space-y-2">
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/registro_muestra/dashboard-registro-muestras.php', '📋 Registro de Muestras', 'Registro del pedido de muestra')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Registro</a>

                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/reportes/dashboard-reportes.php', '📄 Listado de Muestras', 'Listado de registros enviados (muestras)')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Listado</a>

                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/seguimiento/dashboard-seguimiento.php', '📊 Seguimiento de Muestras', 'Seguimiento de los resultados cualitativo y cuantitativo registrados en el sistema')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Seguimiento</a>
                        </div>
                    </div>

                    <div class="menu-group">
                        <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                            onclick="toggleSubmenu('submenu-laboratorio')">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-atom"></i>
                                <span class="font-medium">Laboratorio</span>
                            </span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <div id="submenu-laboratorio" class="submenu hidden pl-10 mt-2 space-y-2">
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/registro_laboratorio/dashboard-rpta-laboratorio.php', '🧪 Resultados de Laboratorio', 'Registro de la respuesta del laboratorio')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Resultados</a>
                        </div>

                    </div>

                    <div class="menu-group">
                        <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                            onclick="toggleSubmenu('submenu-planificacion')">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-calendar"></i>
                                <span class="font-medium">Planificación</span>
                            </span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <div id="submenu-planificacion" class="submenu hidden pl-10 mt-2 space-y-2">
                            <div class="text-gray-500 text-xs font-semibold uppercase tracking-wider mt-2 first:mt-0">Programa</div>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/planificacion/programas/dashboard-programas-registro.php', '📋 Programa - Registro', 'Registro de programas de planificación')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white pl-2">Registro</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/planificacion/programas/dashboard-programas-listado.php', '📋 Programa - Listado', 'Filtros y listado de programas')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white pl-2">Listado</a>
                            <div class="text-gray-500 text-xs font-semibold uppercase tracking-wider mt-2">Asignación</div>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/planificacion/cronograma/dashboard-cronograma-registro.php', '📅 Asignación - Registro', 'Registro de cronograma')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white pl-2">Registro</a>
                            <div class="text-gray-500 text-xs font-semibold uppercase tracking-wider mt-2">Cronograma</div>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/planificacion/cronograma/dashboard-cronograma-listado.php', '📅 Asignación - Listado', 'Listado de cronogramas')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white pl-2">Listado</a>

                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/planificacion/calendario/dashboard-calendario.php', '📅 Calendario', 'Vista de cronogramas por día, semana, mes y año')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white pl-2">Calendario</a>
                            <!--a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/planificacion/comparativo/dashboard-comparativo.php', '⚖️ Comparativo', 'Necropsias vs Cronograma: planificado o eventual por fecha')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white pl-2">Comparativo</a-->
                        </div>
                    </div>


                    <div class="menu-group">
                        <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                            onclick="toggleSubmenu('submenu-necropsia')">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-feather-pointed"></i>
                                <span class="font-medium">Necropsias</span>
                            </span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <div id="submenu-necropsia" class="submenu hidden pl-10 mt-2 space-y-2">
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/necropsias/dashboard-necropsias-registro.php', '📋 Registro de Necropsias', 'Complete los campos y presione Guardar.')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Registro</a>

                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/necropsias/dashboard-necropsias-listado.php', '📄 Listado de Necropsias', 'Listado de Necropsias')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Listado</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/necropsias/dashboard-reporte-comparativo.php', '📊 Reporte Comparativo', 'Compare los resultados de las necropsias de los galpones seleccionados.')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Reporte</a>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
            <!-- TRACKING -->
            <div class="nav-section">
                <div class="nav-section-title text-blue-300 text-xs uppercase font-semibold mb-2 px-3 flex items-center gap-2">
                    <i class="fas fa-truck"></i> <span>Logística</span>
                </div>
                <div class="menu-group">
                    <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                        onclick="toggleSubmenu('submenu-tracking')">
                        <span class="flex items-center gap-3">
                            <i class="fa-solid fa-location-dot"></i>
                            <span class="font-medium">
                                Tracking
                            </span>
                        </span>
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>

                    <div id="submenu-tracking" class="submenu hidden pl-10 mt-2 space-y-2">

                        <a href="#"
                            onclick="selectMenuItem(this); loadDashboardAndData('modules/tracking/escaneo/dashboard-escaneoQR.php','Escaneo QR', 'Escaneo tracking')"
                            class="submenu-link menu-link block text-gray-400 hover:text-white">
                            Escaneo
                        </a>
                        <a href="#"
                            onclick="selectMenuItem(this); loadDashboardAndData('modules/tracking/seguimiento_envios/dashboard-tracking-muestra.php','Seguimiento de envios', 'Visualice el seguimiento de muestra')"
                            class="submenu-link menu-link block text-gray-400 hover:text-white">
                            Seguimiento
                        </a>
                        <a href="#"
                            onclick="selectMenuItem(this); loadDashboardAndData('modules/tracking/reporte/dashboard-reporte-tracking.php','🧪 Pendientes de entregas', 'Administre los pendientes y demas.')"
                            class="submenu-link menu-link block text-gray-400 hover:text-white">
                            Pendientes
                        </a>
                    </div>
                </div>
            </div>
            <?php if (!$isTransportista): ?>


                <!-- ADMINISTRACIÓN -->
                <div class="nav-section">
                    <div class="nav-section-title text-blue-300 text-xs uppercase font-semibold mb-2 px-3 flex items-center gap-2">
                        <i class="fas fa-cog"></i> <span>Administración</span>
                    </div>
                    <div class="menu-group">
                        <button class="menu-item flex items-center justify-between w-full px-4 py-3 text-white rounded-lg"
                            onclick="toggleSubmenu('submenu-maestros-sistema')">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-cogs w-5"></i>
                                <span class="font-medium">Configuración</span>
                            </span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>

                        <div id="submenu-maestros-sistema" class="submenu hidden pl-10 mt-2 space-y-2">

                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/empTransporte/dashboard-empresas-transporte.php','🚚 Empresas de transporte', 'Administre las empresas de transporte registradas en el sistema')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Empresas de transporte</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/laboratorio/dashboard-laboratorio.php','🔬 Laboratorio', 'Administre los laboratorios registrados en el sistema')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Laboratorios</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/tipo_muestra/dashboard-tipo-muestra.php','🧪 Tipo muestra', 'Administre los tipos de muestra registrados en el sistema')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Tipos de Muestra</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/tipo_analisis/dashboard-analisis.php','🔍 Analisis', 'Administre los analisis registrados en el sistema')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Tipos de Analisis</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/paquete_analisis/dashboard-paquete-analisis.php','📦 Paquete analisis', 'Administre los paquetes de analisis registrados en el sistema')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Paquetes de Analisis</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/tipo_respuesta/dashboard-respuesta.php','🛠️ Tipos de Respuesta', 'Administre los tipos de respuestas registrados de los analisis')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Tipos de Respuesta</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/correo_contacto/dashboard-correo-contactos.php','📧 Correo y Contactos', 'Administre  tu cuenta de correo y tus contactos para envío de ')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Correo y Contactos</a>
                            <div class="border-t border-gray-600 my-2 pt-2"></div>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/tipoPrograma/dashboard-tipo-programa.php','📋 Tipos de Programa', 'Administre los tipos de programa')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Tipos de Programa</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/proveedor/dashboard-proveedor.php','📦 Proveedor', 'Administre proveedores')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Proveedor</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/productos/dashboard-productos.php','📦 Productos', 'Asigne proveedores a productos')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Productos</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/enfermedades/dashboard-enfermedades.php','🩺 Enfermedades', 'Gestione las enfermedades')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Enfermedades</a>
                            <a href="#"
                                onclick="selectMenuItem(this); loadDashboardAndData('modules/configuracion/notificaciones_whatsapp/dashboard-notificaciones-whatsapp.php','📱 Número telefónico', 'Configure su número para recordatorios por WhatsApp')"
                                class="submenu-link menu-link block text-gray-400 hover:text-white">Número telefónico</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </nav>



    </aside>

    <!-- Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebarCollapse()"></div>

    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm sticky top-0 z-50 border border-gray-200">
            <div class="px-4 py-4 flex items-center justify-between">
                <div class="flex items-start gap-4">
                    <!-- Botón menú: desktop (colapsar) / mobile (abrir sidebar) -->
                    <button class="mt-1 p-2 rounded-lg hover:bg-gray-100 transition-colors" onclick="toggleSidebarCollapse()" aria-label="Menú">
                        <i class="fas fa-bars text-gray-600 text-lg"></i>
                    </button>
                    <div>
                        <h1 id="dashboardTitle" class="text-2xl font-bold text-gray-800 leading-tight">

                        </h1>
                        <p id="dashboardsubTitle" class="text-xs text-gray-500 mt-1"></p>
                    </div>
                </div>

                <!-- Notificaciones + Usuario (juntos) -->
                <div class="flex items-center gap-1">
                    <div class="relative flex items-center" id="notifWrapper">
                        <button type="button" class="p-2 rounded-lg hover:bg-gray-100 relative transition-colors" aria-label="Notificaciones" id="btnNotif" onclick="toggleNotifDropdown()">
                            <i class="fas fa-bell text-gray-600 text-lg"></i>
                            <span id="notifBadge" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] rounded-full w-5 h-5 hidden flex items-center justify-center">0</span>
                        </button>
                        <div id="notifDropdown" class="hidden absolute top-full mt-2 w-96 max-w-[calc(100vw-1.5rem)] right-0 bg-white border border-gray-200 rounded-2xl shadow-lg z-50 overflow-hidden">
                            <div class="p-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between rounded-t-2xl">
                                <span class="font-semibold text-gray-800">Eventos del cronograma</span>
                                <a href="#" onclick="irACalendario(); return false;" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Ver calendario</a>
                            </div>
                            <div class="p-3 max-h-80 overflow-y-auto">
                                <p class="text-sm font-medium text-gray-700 mb-1">Hoy: <span id="notifHoy">0</span></p>
                                <ul id="listHoy" class="text-sm text-gray-600 mb-3 space-y-2 cursor-pointer"></ul>
                                <p class="text-sm font-medium text-gray-700 mb-1">Próximos 7 días: <span id="notifProximos">0</span></p>
                                <ul id="listProximos" class="text-sm text-gray-600 space-y-2 cursor-pointer"></ul>
                                <p id="notifSinEventos" class="text-sm text-gray-500 hidden">No hay eventos para hoy ni próximos días.</p>
                            </div>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <details class="relative">
                        <summary class="flex items-center gap-3 cursor-pointer select-none list-none">
                            <div class="text-right">
                                <p id="userName" class="text-sm font-semibold text-gray-700">
                                    <?php echo htmlspecialchars($_SESSION['usuario'] ?? 'usuario'); ?>
                                </p>
                                <p id="rolUser" class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'nombre'); ?>
                                </p>
                            </div>
                            <div
                                class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white"></i>
                            </div>
                        </summary>

                        <div class="absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user text-gray-500 mr-2"></i> Perfil
                            </a>
                            <hr class="border-gray-200 my-1" />
                            <a href="#" onclick="logout()" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar sesión
                            </a>
                        </div>
                    </details>
                </div>
            </div>
        </header>


        <!-- Dashboard Content -->
        <main class="">
            <div id="loadingIndicator" class="loading hidden">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <iframe id="dashboardFrame"></iframe>
        </main>
    </div>

    <script>
        function showLoading() {
            document.getElementById('loadingIndicator').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingIndicator').classList.add('hidden');
        }

        // Registrar correctamente el evento 'load' del iframe
        document.getElementById('dashboardFrame').addEventListener('load', hideLoading);

        function toggleSidebarCollapse() {
            const sidebar = document.getElementById('sidebar');
            const contentWrapper = document.querySelector('.content-wrapper');
            const overlay = document.getElementById('sidebarOverlay');

            const isCollapsed = sidebar.classList.contains('collapsed');

            // Móvil: mismo comportamiento actual (abre/cierra con overlay)
            if (window.innerWidth < 1024) {
                if (isCollapsed) {
                    sidebar.classList.remove('collapsed');
                    overlay.classList.add('active');
                } else {
                    sidebar.classList.add('collapsed');
                    overlay.classList.remove('active'); // ← ¡IMPORTANTE!
                }
                contentWrapper.classList.toggle('sidebar-collapsed', !isCollapsed);
                return;
            }

            // Desktop: colapsado tipo "mini sidebar" (no se oculta completo)
            sidebar.classList.toggle('collapsed');
            overlay.classList.remove('active');
            contentWrapper.classList.toggle('sidebar-collapsed');
        }

        function toggleSubmenu(id, btn) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.toggle('hidden');
            if (btn) {
                btn.classList.toggle('active');
            }
        }

        function markActiveElement(element) {
            document.querySelectorAll('.menu-link.active, .menu-item.active, .submenu-toggle.active')
                .forEach(el => el.classList.remove('active'));
            if (!element) return;
            element.classList.add('active');

            const menuGroup = element.closest('.menu-group');
            if (menuGroup) {
                const topMenuItem = menuGroup.querySelector('.menu-item');
                if (topMenuItem) topMenuItem.classList.add('active');
            }
        }

        function selectMenuItem(element) {
            markActiveElement(element);
        }

        function activateAndLoad(buttonOrLink, dashboardUrl, title, subtitle) {
            markActiveElement(buttonOrLink);
            loadDashboardAndData(dashboardUrl, title, subtitle);
        }

        function loadDashboardAndData(dashboardUrl, title, subtitle) {
            const frame = document.getElementById('dashboardFrame');
            document.getElementById('dashboardTitle').textContent = title;
            document.getElementById('dashboardsubTitle').textContent = subtitle;
            showLoading();
            // Siempre reiniciar el src para forzar el evento 'load'
            frame.src = dashboardUrl;
            if (window.innerWidth < 1024) {
                toggleSidebarCollapse();
            }

        }

        // Cuando el iframe abre/cierra un modal a pantalla completa (ej. cronograma calendario o detalle evento)
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'sanidadIframeFullscreen') {
                var frame = document.getElementById('dashboardFrame');
                if (!frame) return;
                if (event.data.open) {
                    frame.classList.add('iframe-fullscreen');
                } else {
                    frame.classList.remove('iframe-fullscreen');
                }
            }
        });

        function toggleNotifDropdown() {
            const dd = document.getElementById('notifDropdown');
            const btn = document.getElementById('btnNotif');
            if (!dd || !btn) return;
            if (dd.classList.contains('hidden')) {
                var rect = btn.getBoundingClientRect();
                dd.style.position = 'fixed';
                dd.style.top = (rect.bottom + 8) + 'px';
                dd.style.right = (window.innerWidth - rect.right) + 'px';
                dd.style.left = 'auto';
                dd.style.width = 'min(24rem, calc(100vw - 1.5rem))';
                dd.classList.remove('hidden');
            } else {
                dd.classList.add('hidden');
            }
        }

        function irACalendario(fechaYMD) {
            document.getElementById('notifDropdown').classList.add('hidden');
            var url = 'modules/planificacion/calendario/dashboard-calendario.php';
            if (fechaYMD && /^\d{4}-\d{2}-\d{2}$/.test(String(fechaYMD))) {
                url += '?fecha=' + encodeURIComponent(fechaYMD);
            }
            var calLink = document.querySelector('[onclick*="dashboard-calendario.php"]');
            if (calLink) {
                markActiveElement(calLink);
                loadDashboardAndData(url, '📅 Calendario', 'Vista de cronogramas por día, semana, mes y año');
            } else {
                loadDashboardAndData(url, '📅 Calendario', 'Vista de cronogramas por día, semana, mes y año');
            }
        }

        function fechaDDMMYYYY(str) {
            if (!str || str.length < 10) return str;
            var d = str.substring(8, 10),
                m = str.substring(5, 7),
                y = str.substring(0, 4);
            return d + '/' + m + '/' + y;
        }

        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('notifWrapper');
            const dd = document.getElementById('notifDropdown');
            if (wrapper && dd && !wrapper.contains(e.target)) {
                dd.classList.add('hidden');
            }
        });

        function loadResumenEventos() {
            fetch('modules/planificacion/cronograma/get_resumen_eventos.php')
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    var hoy = data.hoy || 0;
                    var proximos = data.proximos || 0;
                    var total = hoy + proximos;

                    document.getElementById('notifHoy').textContent = hoy;
                    document.getElementById('notifProximos').textContent = proximos;

                    var badge = document.getElementById('notifBadge');
                    if (total > 0) {
                        badge.textContent = total > 99 ? '99+' : total;
                        badge.classList.remove('hidden');
                        badge.style.display = 'flex';
                        if (total >= 10) {
                            badge.classList.remove('w-5', 'h-5');
                            badge.classList.add('w-6', 'h-6', 'text-[10px]');
                        } else {
                            badge.classList.remove('w-6', 'h-6', 'text-[10px]');
                            badge.classList.add('w-5', 'h-5');
                        }
                    } else {
                        badge.classList.add('hidden');
                        badge.style.display = 'none';
                    }

                    var listHoy = document.getElementById('listHoy');
                    var listProximos = document.getElementById('listProximos');
                    var sinEventos = document.getElementById('notifSinEventos');
                    listHoy.innerHTML = '';
                    listProximos.innerHTML = '';

                    if (data.eventosHoy && data.eventosHoy.length) {
                        data.eventosHoy.forEach(function(ev) {
                            var li = document.createElement('li');
                            li.className = 'hover:bg-gray-100 rounded-lg px-2 py-2 cursor-pointer border border-transparent hover:border-gray-200';
                            var fechaKey = (ev.fechaEjecucion || '').substring(0, 10);
                            li.setAttribute('data-fecha', fechaKey);
                            li.innerHTML = '<div class="font-medium text-gray-800">' + (ev.codPrograma || '') + ' — ' + (ev.nomPrograma || '') + '</div>' +
                                '<div class="text-xs text-gray-500 mt-0.5">Granja: ' + (ev.nomGranja || ev.granja || '—') + ' · Campaña: ' + (ev.campania || '—') + ' · Galpón: ' + (ev.galpon || '—') + (ev.edad !== undefined && ev.edad !== '' ? ' · Edad: ' + ev.edad : '') + '</div>';
                            li.onclick = function() {
                                irACalendario(fechaKey);
                            };
                            listHoy.appendChild(li);
                        });
                    }
                    if (data.eventosProximos && data.eventosProximos.length) {
                        data.eventosProximos.forEach(function(ev) {
                            var li = document.createElement('li');
                            li.className = 'hover:bg-gray-100 rounded-lg px-2 py-2 cursor-pointer border border-transparent hover:border-gray-200';
                            var fechaStr = (ev.fechaEjecucion || '').substring(0, 10);
                            li.setAttribute('data-fecha', fechaStr);
                            li.innerHTML = '<div class="text-xs text-blue-600 font-medium">' + fechaDDMMYYYY(fechaStr) + '</div>' +
                                '<div class="font-medium text-gray-800">' + (ev.codPrograma || '') + ' — ' + (ev.nomPrograma || '') + '</div>' +
                                '<div class="text-xs text-gray-500 mt-0.5">Granja: ' + (ev.nomGranja || ev.granja || '—') + ' · Campaña: ' + (ev.campania || '—') + ' · Galpón: ' + (ev.galpon || '—') + (ev.edad !== undefined && ev.edad !== '' ? ' · Edad: ' + ev.edad : '') + '</div>';
                            li.onclick = function() {
                                irACalendario(fechaStr);
                            };
                            listProximos.appendChild(li);
                        });
                    }
                    if (total === 0) {
                        sinEventos.classList.remove('hidden');
                    } else {
                        sinEventos.classList.add('hidden');
                    }
                })
                .catch(function() {
                    document.getElementById('notifBadge').classList.add('hidden');
                });
        }

        // Inicialización al cargar la página
        window.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const contentWrapper = document.querySelector('.content-wrapper');

            // Pantallas pequeñas: sidebar oculto (ya tiene clase collapsed en el HTML)
            // Escritorio (>= 1024px): mostrar sidebar completo al iniciar
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('collapsed');
                contentWrapper.classList.remove('sidebar-collapsed');
                overlay.classList.remove('active');
            } else {
                sidebar.classList.add('collapsed');
                contentWrapper.classList.add('sidebar-collapsed');
                overlay.classList.remove('active');
            }

            loadResumenEventos();

            // Cargar dashboard por defecto
            const defaultMenuItem = document.querySelector('[onclick*="modules/dashboard/dashboard-dashboard.php"]');
            if (defaultMenuItem) {
                activateAndLoad(defaultMenuItem, 'modules/dashboard/dashboard-dashboard.php', '📊 Dashboard de Reportes', 'Resumen visual de los datos registrados en el sistema');
            } else {
                const frame = document.getElementById('dashboardFrame');
                frame.src = 'modules/dashboard/dashboard-dashboard.php';
                document.getElementById('dashboardTitle').textContent = '📊 Dashboard de Reportes';
                document.getElementById('dashboardsubTitle').textContent = 'Resumen visual de los datos registrados en el sistema';
            }
        });

        // Ajuste al redimensionar: en pantallas pequeñas sidebar oculto
        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const contentWrapper = document.querySelector('.content-wrapper');
            if (window.innerWidth >= 1024) {
                sidebar.classList.add('open');
                sidebar.classList.remove('collapsed');
                overlay.classList.remove('active');
                contentWrapper.classList.remove('sidebar-collapsed');
            } else {
                sidebar.classList.add('collapsed');
                overlay.classList.remove('active');
                contentWrapper.classList.add('sidebar-collapsed');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/sweetalert-helpers.js"></script>
    <script src="logout.js"></script>

</body>

</html>