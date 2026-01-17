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

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 300px;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            transition: transform 0.3s ease, width 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: 0;
            overflow: hidden;
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
            margin-left: 300px;
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
        }

        @media (max-width: 1023px) {
            .content-wrapper {
                margin-left: 0;
            }

            .content-wrapper.sidebar-collapsed {
                margin-left: 0;
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

        /* Loading */
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* Clave: Asegurar que el content-wrapper tenga margen izquierdo cuando el sidebar está abierto */
        .content-wrapper.sidebar-collapsed {
            margin-left: 0;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
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
                            onclick="activateAndLoad(this, 'modules/planificacion/dashboard-planificacion.php', '📅 Planificación', 'Registro de la planificación')">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-calendar"></i>
                                <span class="font-medium">Planificación</span>
                            </span>
                        </button>
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
        <header class="bg-white shadow-sm sticky top-0 z-50">
            <div class="px-4 py-4 flex items-center justify-between">
                <div class="flex items-start gap-4">
                    <!-- Botón PARA DESKTOP (ocultar sidebar) -->
                    <button class="mt-1" onclick="toggleSidebarCollapse()">
                        <i class="fas fa-bars text-gray-600 text-lg"></i>
                    </button>
                    <div>
                        <h1 id="dashboardTitle" class="text-2xl font-bold text-gray-800 leading-tight">

                        </h1>
                        <p id="dashboardsubTitle" class="text-xs text-gray-500 mt-1"></p>
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

            if (isCollapsed) {
                // Vamos a abrir el sidebar (solo en móvil)
                sidebar.classList.remove('collapsed');
                if (window.innerWidth < 1024) {
                    overlay.classList.add('active');
                }
            } else {
                // Vamos a cerrar el sidebar
                sidebar.classList.add('collapsed');
                overlay.classList.remove('active'); // ← ¡IMPORTANTE!
            }

            contentWrapper.classList.toggle('sidebar-collapsed', !isCollapsed);
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

        // Inicialización al cargar la página
        window.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const contentWrapper = document.querySelector('.content-wrapper');

            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('collapsed');
                contentWrapper.classList.remove('sidebar-collapsed');
                overlay.classList.remove('active');
            } else {
                sidebar.classList.add('collapsed');
                contentWrapper.classList.add('sidebar-collapsed');
                overlay.classList.remove('active');
            }

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

        // Ajuste al redimensionar
        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (window.innerWidth >= 1024) {
                sidebar.classList.add('open');
                overlay.classList.remove('active');
            }
        });
    </script>
    <script src="logout.js"></script>

</body>

</html>