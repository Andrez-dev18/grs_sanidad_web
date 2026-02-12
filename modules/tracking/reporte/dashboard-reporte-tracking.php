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

$usuario = $_SESSION['usuario'] ?? 'usuario';

//ruta relativa a la conexion
include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// === OBTENER ROL DEL USUARIO (ocultar Eliminar si es transportista) ===
$codigoUsuario = $_SESSION['usuario'] ?? null;
$isTransportista = false;
if ($codigoUsuario) {
    $sql = "SELECT rol_sanidad FROM usuario WHERE codigo = ? AND estado = 'A'";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $codigoUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $rol = trim($row['rol_sanidad'] ?? 'user');
            $isTransportista = ($rol === 'TRANSPORTE');
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tracking</title>

    <!-- Tailwind CSS -->
    <link href="../../../css/output.css" rel="stylesheet">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <style>
        /* Tus estilos existentes */
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

        .btn-export {
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

        .btn-export:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(16, 185, 129, 0.4);
        }

        .btn-outline {
            background: white;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 0.625rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-outline:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
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

        .table-wrapper {
            overflow-x: auto;
            overflow-y: visible;
            width: 100%;
            border-radius: 1rem;
        }

        .table-wrapper::-webkit-scrollbar {
            height: 10px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Comentario: varias líneas */
        #tabla td.comentario-cell {
            white-space: normal !important;
            word-wrap: break-word;
            max-width: 280px;
            vertical-align: top;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid py-4">

        <!-- CARD FILTROS PLEGABLE -->
        <div class="card-filtros-compacta mx-5 mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

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
                <svg id="iconoFiltros" class="w-5 h-5 text-gray-600 transition-transform duration-300 rotate-180"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- CONTENIDO PLEGABLE (desplegado por defecto) -->
            <div id="contenidoFiltros" class="px-6 pb-6 pt-4">

                <!-- Fila 1: Periodo -->
                <div class="filter-row-periodo flex flex-wrap items-end gap-4 mb-6">
                    <div class="flex-shrink-0" style="min-width: 200px;">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1 text-blue-600"></i> Periodo</label>
                        <select id="periodoTipo" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer text-sm">
                            <option value="TODOS" selected>Todos</option>
                            <option value="POR_FECHA">Por fecha</option>
                            <option value="ENTRE_FECHAS">Entre fechas</option>
                            <option value="POR_MES">Por mes</option>
                            <option value="ENTRE_MESES">Entre meses</option>
                            <option value="ULTIMA_SEMANA">Última Semana</option>
                        </select>
                    </div>
                    <div id="periodoPorFecha" class="flex-shrink-0 min-w-[200px] hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar-day mr-1 text-blue-600"></i>Fecha</label>
                        <input id="fechaUnica" type="date" value="<?php echo date('Y-m-d'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div id="periodoEntreFechas" class="hidden flex-shrink-0 flex items-end gap-2">
                        <div class="min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i>Desde</label><input id="fechaInicio" type="date" value="<?php echo date('Y-m-01'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></div>
                        <div class="min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i>Hasta</label><input id="fechaFin" type="date" value="<?php echo date('Y-m-t'); ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></div>
                    </div>
                    <div id="periodoPorMes" class="hidden flex-shrink-0 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-calendar mr-1 text-blue-600"></i>Mes</label>
                        <input id="mesUnico" type="month" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div id="periodoEntreMeses" class="hidden flex-shrink-0 flex items-end gap-2">
                        <div class="min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-start mr-1 text-blue-600"></i>Mes Inicio</label><input id="mesInicio" type="month" value="<?php echo date('Y') . '-01'; ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></div>
                        <div class="min-w-[180px]"><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-hourglass-end mr-1 text-blue-600"></i>Mes Fin</label><input id="mesFin" type="month" value="<?php echo date('Y') . '-12'; ?>" class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></div>
                    </div>
                </div>
                <!-- Fila 2: Ubicación -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-map-marker-alt mr-1 text-blue-600"></i>Ubicación</label>
                        <select id="filtroUbicacion" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccionar</option>
                            <option value="GRS">GRS</option>
                            <option value="Transporte">Transporte</option>
                            <option value="Laboratorio">Laboratorio</option>
                        </select>
                    </div>
                </div>

                <!-- ACCIONES -->
                <div class="dashboard-actions mt-6 flex flex-wrap justify-end gap-4">

                    <button type="button" id="btnAplicarFiltros"
                        class="px-6 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Filtrar
                    </button>

                    <button type="button" id="btnLimpiarFiltros"
                        class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200">
                        Limpiar
                    </button>
                </div>

            </div>
        </div>

        <!-- TABLA -->
        <div class="card mx-5">
            <!-- TABS -->
            <div class="bg-white rounded-xl shadow-md mb-6 overflow-hidden">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px" role="tablist">
                        <button id="tabTodos"
                            class="tab-button px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200
                           border-blue-600 text-blue-600 bg-blue-50"
                            aria-selected="true">
                            Todos los registros
                        </button>
                        <button id="tabPendientes"
                            class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent transition-colors duration-200
                           text-gray-600 hover:text-gray-800 hover:border-gray-300"
                            aria-selected="false">
                            Pendientes
                        </button>
                    </nav>
                </div>
            </div>
            <!-- CONTENIDO DE TABS -->
            <div id="contenidoTodos" class="tab-content">
                <div id="tablaTrackingWrapper" class="card-body p-4" data-vista-tabla-iconos data-vista="">
                    <div class="toolbar-vista-row flex flex-wrap items-center justify-between gap-3 mb-3" id="trackingToolbarRow">
                        <div class="view-toggle-group flex items-center gap-2" id="viewToggleGroupTrack">
                            <button type="button" class="view-toggle-btn active" id="btnViewTablaTrack" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                            <button type="button" class="view-toggle-btn" id="btnViewIconosTrack" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                        </div>
                        <div id="trackDtControls" class="flex flex-wrap items-center gap-3"></div>
                        <div id="trackIconosControls" class="flex flex-wrap items-center gap-3" style="display: none;"></div>
                    </div>
                    <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetasTrack">
                        <div id="cardsControlsTopTrack" class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-gray-600 border-b border-gray-200 pb-3"></div>
                        <div id="cardsContainerTrack" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                        <div id="cardsPaginationTrack" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3" data-table="#tabla"></div>
                    </div>
                    <div class="view-lista-wrap table-wrapper overflow-x-auto">
                        <table id="tabla" class="data-table w-full text-sm border-collapse config-table">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left">N°</th>
                                    <th class="px-4 py-3 text-left border-b">Código Envío</th>
                                    <th class="px-4 py-3 text-left border-b">Acción</th>
                                    <th class="px-4 py-3 text-left border-b">Comentario</th>
                                    <th class="px-4 py-3 text-left border-b">Evidencia</th>
                                    <th class="px-4 py-3 text-left border-b">Usuario</th>
                                    <th class="px-4 py-3 text-left border-b">Ubicación</th>
                                    <th class="px-4 py-3 text-left border-b">Fecha Registro</th>
                                    <th class="px-4 py-3 text-center border-b">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="contenidoPendientes" class="tab-content hidden">
                <?php
                $enviosGRS = $conexion->query("SELECT DISTINCT codEnvio FROM san_dim_historial_resultados WHERE ubicacion = 'GRS'");
                $pendientes = [];
                while ($row = $enviosGRS->fetch_assoc()) {
                    $cod = $row['codEnvio'];
                    $tieneTransporte = $conexion->query("SELECT 1 FROM san_dim_historial_resultados WHERE codEnvio = '$cod' AND ubicacion = 'Transporte' LIMIT 1")->num_rows > 0;
                    $tieneLaboratorio = $conexion->query("SELECT 1 FROM san_dim_historial_resultados WHERE codEnvio = '$cod' AND ubicacion = 'Laboratorio' LIMIT 1")->num_rows > 0;
                    if (!$tieneTransporte || !$tieneLaboratorio) {
                        $falta = [];
                        if (!$tieneTransporte) $falta[] = 'Recoger por transportista';
                        if (!$tieneLaboratorio) $falta[] = 'Recepción en laboratorio';
                        $pendientes[] = [ 'codEnvio' => $cod, 'falta' => implode(' y ', $falta) ];
                    }
                }
                ?>
                <div id="pendientesWrapper" class="bg-white rounded-xl shadow-md p-5 mt-4" data-vista-tabla-iconos data-vista="">
                    <div class="card-body p-0">
                        <div class="reportes-toolbar-row flex flex-wrap items-center justify-between gap-3 mb-3" id="pendientesToolbarRow">
                            <div class="view-toggle-group flex items-center gap-2">
                                <button type="button" class="view-toggle-btn active" id="btnViewListaPend" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                                <button type="button" class="view-toggle-btn" id="btnViewIconosPend" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                            </div>
                            <div id="pendDtControls" class="flex flex-wrap items-center gap-3"></div>
                            <div id="pendIconosControls" class="flex flex-wrap items-center gap-3" style="display: none;"></div>
                        </div>
                        <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden hidden" id="viewTarjetasPend">
                            <div id="cardsControlsTopPend" class="flex flex-wrap items-center justify-between gap-3 mb-4 text-sm text-gray-600 border-b border-gray-200 pb-3"></div>
                            <div id="cardsContainerPend" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                            <div id="cardsPaginationPend" class="flex flex-wrap items-center justify-between gap-3 mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3" data-table="#tablaPendientes"></div>
                        </div>
                        <div class="view-lista-wrap" id="viewListaPend">
                            <div class="table-wrapper overflow-x-auto">
                                <table id="tablaPendientes" class="data-table display w-full text-sm border-collapse config-table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-4 text-left text-sm font-semibold">N°</th>
                                            <th class="px-6 py-4 text-left text-sm font-semibold">Código de envío</th>
                                            <th class="px-6 py-4 text-center text-sm font-semibold">Falta</th>
                                            <th class="px-6 py-4 text-center text-sm font-semibold">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($pendientes)): ?>
                                            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">¡Excelente! No hay envíos pendientes.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($pendientes as $idx => $p): $n = $idx + 1; $codAttr = htmlspecialchars($p['codEnvio'], ENT_QUOTES, 'UTF-8'); ?>
                                                <tr data-codigo="<?php echo $codAttr; ?>" data-falta="<?php echo htmlspecialchars($p['falta'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <td class="px-6 py-4 text-gray-700"><?php echo $n; ?></td>
                                                    <td class="px-6 py-4 font-medium text-blue-600"><?php echo htmlspecialchars($p['codEnvio']); ?></td>
                                                    <td class="px-6 py-4 text-center text-orange-600 font-medium"><?php echo htmlspecialchars($p['falta']); ?></td>
                                                    <td class="px-6 py-4 text-center">
                                                        <button type="button" onclick="cargarEscaneoConCodigo('<?php echo $codAttr; ?>')" class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition shadow hover:shadow-md"><i class="fa-solid fa-qrcode mr-2"></i> Escanear / Recepcionar</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- MODAL PARA VER MÚLTIPLES EVIDENCIAS -->
        <div id="modalEvidencia" class="fixed inset-0 bg-black/80 hidden z-50">
            <!-- Fondo oscuro sin padding lateral para maximizar espacio -->
            <div class="flex min-h-full items-start justify-center pt-4 px-4 sm:pt-0 sm:items-center">

                <div class="bg-white rounded-t-3xl sm:rounded-xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col">

                    <!-- Header -->
                    <div class="flex justify-between items-center px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Evidencia fotográfica
                        </h3>
                        <div class="flex items-center gap-3">
                            <!-- Botón abrir en nueva pestaña (solo ícono) -->
                            <button onclick="abrirFotoActualEnPestana()"
                                class="text-blue-600 hover:text-blue-800 transition bg-blue-100 hover:bg-blue-200 rounded-full p-2">
                                <i class="fa-solid fa-external-link-alt text-lg"></i>
                            </button>

                            <!-- Botón cerrar -->
                            <button onclick="cerrarModalEvidencia()" class="text-gray-500 hover:text-gray-700 text-2xl">
                                ×
                            </button>
                        </div>
                    </div>

                    <!-- Carrusel de imágenes -->
                    <div class="flex-1 overflow-hidden relative bg-gray-50">
                        <div id="carruselFotos" class="flex transition-transform duration-300 ease-in-out h-full">
                            <!-- Imágenes dinámicas -->
                        </div>

                        <!-- Flechas -->
                        <button id="prevFoto" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition z-10">
                            <i class="fa-solid fa-chevron-left text-2xl text-gray-800"></i>
                        </button>
                        <button id="nextFoto" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition z-10">
                            <i class="fa-solid fa-chevron-right text-2xl text-gray-800"></i>
                        </button>

                        <!-- Contador -->
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 text-white px-4 py-2 rounded-full text-sm font-medium z-10">
                            <span id="contadorFotos">1 / 1</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>


        <!-- MODAL EDITAR REGISTRO -->
        <div id="modalEditar" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50 px-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col animate-fade-in">

                <!-- Header -->
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Editar registro de tracking
                    </h3>
                    <button onclick="cerrarModalEditar()" class="text-gray-400 hover:text-gray-700 text-2xl">
                        ×
                    </button>
                </div>

                <!-- Body -->
                <div class="px-6 py-4 overflow-y-auto flex-1">
                    <input type="hidden" id="editIdRegistro">

                    <!-- Código envío (solo lectura) -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Código de envío
                        </label>
                        <input type="text" id="editCodEnvio" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg" readonly>
                    </div>

                    <!-- Ubicación (editable solo si no es Laboratorio) -->
                    <div class="mb-6">
                        <label for="editUbicacion" class="block text-sm font-medium text-gray-700 mb-1">
                            Ubicación actual
                        </label>
                        <select id="editUbicacion" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="GRS">GRS</option>
                            <option value="Transporte">Transporte</option>
                            <option value="Laboratorio">Laboratorio</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Solo se puede cambiar si no ha llegado a Laboratorio.</p>
                    </div>

                    <!-- Comentario -->
                    <div class="mb-6">
                        <label for="editComentario" class="block text-sm font-medium text-gray-700 mb-1">
                            Comentario
                        </label>
                        <textarea id="editComentario" rows="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    </div>

                    <!-- Evidencia actual (si existe) -->
                    <div id="editEvidenciaActual" class="mb-6 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Evidencia actual
                        </label>

                        <div id="editEvidenciaPreview" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <!-- Miniaturas actuales se agregan con JS -->
                        </div>
                    </div>

                    <!-- Nueva evidencia (opcional) -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Agregar/reemplazar evidencia <span class="text-gray-500 text-xs">(opcional)</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="cursor-pointer">
                                <input type="file" id="editInputFoto" accept="image/*" capture="environment" class="hidden" multiple>
                                <div class="border-2 border-dashed border-gray-400 rounded-lg px-4 py-8 text-center hover:border-blue-500 transition">
                                    <i class="fa-solid fa-camera text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-base font-medium text-gray-700">Tomar fotos</p>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="file" id="editInputGaleria" accept="image/*" class="hidden" multiple>
                                <div class="border-2 border-dashed border-gray-400 rounded-lg px-4 py-8 text-center hover:border-blue-500 transition">
                                    <i class="fa-solid fa-images text-4xl text-gray-400 mb-3"></i>
                                    <p class="text-base font-medium text-gray-700">Desde galería</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <!-- VISTA PREVIA DE NUEVAS FOTOS -->
                    <div id="editNuevasFotosPreview" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
                        <!-- Aquí aparecerán las nuevas fotos con botón eliminar -->
                    </div>
                </div>

                <!-- Footer -->
                <div class="dashboard-modal-actions px-6 py-5 border-t bg-gray-50 rounded-b-xl flex flex-wrap justify-end gap-3">
                    <button onclick="cerrarModalEditar()"
                        class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium transition">
                        Cancelar
                    </button>
                    <button id="btnGuardarEdicion"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition shadow-lg">
                        Guardar cambios
                    </button>
                </div>
            </div>
        </div>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../../assets/js/sweetalert-helpers.js"></script>
    <script src="../../../assets/js/pagination-iconos.js"></script>

    <script>
        $(document).ready(function() {
            let tabla = $('#tabla').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'tracking_historial.php', // archivo PHP que devuelve los datos
                    type: 'POST',
                    data: function(d) {
                        d.periodoTipo = ($('#periodoTipo').val() || 'TODOS').trim();
                        d.fechaUnica = ($('#fechaUnica').val() || '').trim();
                        d.fechaInicio = ($('#fechaInicio').val() || '').trim();
                        d.fechaFin = ($('#fechaFin').val() || '').trim();
                        d.mesUnico = ($('#mesUnico').val() || '').trim();
                        d.mesInicio = ($('#mesInicio').val() || '').trim();
                        d.mesFin = ($('#mesFin').val() || '').trim();
                        d.ubicacion = ($('#filtroUbicacion').val() || '').trim();
                    }
                },
                language: {
                    url: '../../../assets/i18n/es-ES.json'
                },
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [7, 'desc']
                ], // ordenar por fecha registro descendente (columna 7 = fechaHoraRegistro)
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row, meta) {
                            return type === 'display' ? (meta.settings._iDisplayStart + meta.row + 1) : '';
                        }
                    },
                    {
                        data: 'codEnvio',
                        className: 'font-medium'
                    },
                    {
                        data: 'accion'
                    },
                    {
                        data: 'comentario',
                        className: 'comentario-cell',
                        render: function(data) {
                            if (!data) return '<span class="text-gray-400">—</span>';
                            var esc = (data + '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/</g, '&lt;').replace(/>/g, '&gt;');
                            return '<div class="text-wrap whitespace-normal max-w-md">' + esc + '</div>';
                        }
                    },
                    {
                        data: 'evidencia',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data) {
                            if (!data) return '<span class="text-gray-400">—</span>';
                            var esc = (data + '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                            return '<button type="button" onclick="abrirModalEvidencia(\'' + esc + '\')" class="text-blue-600 hover:text-blue-800 transition inline-flex items-center justify-center" title="Ver evidencias"><i class="fa-solid fa-eye text-lg"></i></button>';
                        }
                    },
                    {
                        data: 'usuario'
                    },
                    {
                        data: 'ubicacion'
                    },
                    {
                        data: 'fechaHoraRegistro',
                        render: function(data) {
                            if (!data) return '-';
                            const date = new Date(data);
                            return date.toLocaleDateString('es-PE') + ' ' + date.toLocaleTimeString('es-PE', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            const esTransportista = <?php echo $isTransportista ? 'true' : 'false'; ?>;
                            let botonEliminar = '';
                            if (!esTransportista) {
                                botonEliminar = `<button onclick="eliminarRegistro(${row.id})" class="text-red-600 hover:text-red-800" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </button>`;
                            }
                            return `
                            <div class="flex justify-center gap-3">
                                <button onclick="editarRegistro(${row.id})" class="text-green-600 hover:text-green-800" title="Editar">
                                    <i class="fa-solid fa-edit"></i>
                                </button>
                                ${botonEliminar}
                            </div>
                            `;
                        }
                    }
                ],
                drawCallback: function() {
                    if (typeof renderizarTarjetasTracking === 'function') renderizarTarjetasTracking();
                },
                initComplete: function() {
                    var wrapper = $('#tabla').closest('.dataTables_wrapper');
                    var $length = wrapper.find('.dataTables_length').first();
                    var $filter = wrapper.find('.dataTables_filter').first();
                    var $controls = $('#trackDtControls');
                    if ($controls.length && $length.length && $filter.length) {
                        $controls.append($length, $filter);
                        var vista = $('#tablaTrackingWrapper').attr('data-vista') || 'tabla';
                        $controls.toggle(vista === 'tabla');
                    }
                }
            });

            function aplicarVisibilidadVistaTrack(vista) {
                var esTabla = (vista === 'tabla');
                $('#tablaTrackingWrapper').attr('data-vista', vista);
                if (esTabla) {
                    var $filter = $('#trackIconosControls .dataTables_filter').detach();
                    if ($filter.length) $('#trackDtControls').append($filter);
                    $('#trackDtControls').show();
                    $('#trackIconosControls').hide();
                    $('#viewTarjetasTrack').addClass('hidden').css('display', 'none');
                    $('#tablaTrackingWrapper .view-lista-wrap').removeClass('hidden').css('display', 'block');
                } else {
                    $('#trackDtControls').hide();
                    $('#trackIconosControls').show();
                    $('#tablaTrackingWrapper .view-lista-wrap').addClass('hidden').css('display', 'none');
                    $('#viewTarjetasTrack').removeClass('hidden').css('display', 'block');
                    $('#cardsContainerTrack').attr('data-vista-cards', 'iconos');
                    if (typeof renderizarTarjetasTracking === 'function') renderizarTarjetasTracking();
                }
            }
            function actualizarVistaInicialTrack() {
                var w = $(window).width();
                var w$ = $('#tablaTrackingWrapper');
                if (!w$.attr('data-vista')) {
                    var vistaInicial = w < 768 ? 'iconos' : 'tabla';
                    w$.attr('data-vista', vistaInicial);
                    $('#btnViewTablaTrack').toggleClass('active', vistaInicial === 'tabla');
                    $('#btnViewIconosTrack').toggleClass('active', vistaInicial === 'iconos');
                    $('#cardsContainerTrack').attr('data-vista-cards', 'iconos');
                    aplicarVisibilidadVistaTrack(vistaInicial);
                }
            }
            function renderizarTarjetasTracking() {
                if (!tabla) return;
                var api = tabla;
                var cont = $('#cardsContainerTrack');
                cont.empty();
                var info = api.page.info();
                var rowIndex = 0;
                var esTransportista = <?php echo $isTransportista ? 'true' : 'false'; ?>;
                api.rows({ page: 'current' }).every(function() {
                    rowIndex++;
                    var numero = info.start + rowIndex;
                    var row = this.data();
                    var fecha = row.fechaHoraRegistro ? (function(d) { var x = new Date(d); return x.toLocaleDateString('es-PE') + ' ' + x.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' }); })(row.fechaHoraRegistro) : '-';
                    var comentario = (row.comentario || '').substring(0, 80) + ((row.comentario || '').length > 80 ? '...' : '');
                    var btnEliminar = '';
                    if (!esTransportista) {
                        btnEliminar = '<button type="button" class="text-red-600 hover:text-red-800 transition" title="Eliminar" onclick="eliminarRegistro(' + row.id + ')"><i class="fa-solid fa-trash"></i></button>';
                    }
                    var card = '<div class="card-item">' +
                        '<div class="card-numero-row">#' + numero + '</div>' +
                        '<div class="card-contenido">' +
                        '<div class="card-codigo">' + $('<div>').text(row.codEnvio || '').html() + '</div>' +
                        '<div class="card-campos">' +
                        '<div class="card-row"><span class="label">Acción:</span> ' + $('<div>').text(row.accion || '').html() + '</div>' +
                        '<div class="card-row"><span class="label">Usuario:</span> ' + $('<div>').text(row.usuario || '').html() + '</div>' +
                        '<div class="card-row"><span class="label">Ubicación:</span> ' + $('<div>').text(row.ubicacion || '').html() + '</div>' +
                        '<div class="card-row"><span class="label">Fecha:</span> ' + $('<div>').text(fecha).html() + '</div>' +
                        '<div class="card-row"><span class="label">Comentario:</span> ' + $('<div>').text(comentario).html() + '</div>' +
                        '</div>' +
                        '<div class="card-acciones">' +
                        '<button type="button" class="text-green-600 hover:text-green-800 transition" title="Editar" onclick="editarRegistro(' + row.id + ')"><i class="fa-solid fa-edit"></i></button>' +
                        btnEliminar +
                        '</div></div></div>';
                    cont.append(card);
                });
                var len = api.page.len();
                var lengthOptions = [10, 25, 50, 100];
                var lengthSelect = '<label class="inline-flex items-center gap-2"><span>Mostrar</span><select class="cards-length-select">' +
                    lengthOptions.map(function(n) { return '<option value="' + n + '"' + (n === len ? ' selected' : '') + '>' + n + '</option>'; }).join('') +
                    '</select><span>registros</span></label>';
                var vista = $('#tablaTrackingWrapper').attr('data-vista') || '';
                if (vista === 'iconos') {
                    var $toolbarRow = $('#trackIconosControls .iconos-toolbar-row');
                    if (!$toolbarRow.length) {
                        var $filter = $('#trackDtControls .dataTables_filter').detach();
                        var iconosRow = '<div class="iconos-toolbar-row flex flex-wrap items-center gap-3">' + lengthSelect + '</div>';
                        $('#trackIconosControls').html(iconosRow);
                        if ($filter.length) $('#trackIconosControls .iconos-toolbar-row').append($filter);
                        $('#trackIconosControls .cards-length-select').on('change', function() {
                            var val = parseInt($(this).val(), 10);
                            if (tabla) tabla.page.len(val).draw(false);
                        });
                    } else {
                        var $sel = $toolbarRow.find('.cards-length-select');
                        if ($sel.length) $sel.find('option').remove().end().append(lengthOptions.map(function(n) { return '<option value="' + n + '"' + (n === len ? ' selected' : '') + '>' + n + '</option>'; }).join(''));
                    }
                    $('#cardsControlsTopTrack').empty();
                    $('#cardsPaginationTrack').html(typeof buildPaginationIconos === 'function' ? buildPaginationIconos(info) : '');
                } else {
                    var navBtns = '<div class="flex gap-2">' +
                        '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page === 0 ? 'disabled' : '') + ' onclick="var dt=$(\'#tabla\').DataTable(); if(dt) dt.page(\'previous\').draw(false);">Anterior</button>' +
                        '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page >= info.pages - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page >= info.pages - 1 ? 'disabled' : '') + ' onclick="var dt=$(\'#tabla\').DataTable(); if(dt) dt.page(\'next\').draw(false);">Siguiente</button>' +
                        '</div>';
                    var controlsHtml = '<div class="flex flex-wrap items-center justify-between gap-3 w-full">' + lengthSelect + '<span>Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros</span>' + navBtns + '</div>';
                    $('#cardsControlsTopTrack').html(controlsHtml);
                    $('#cardsPaginationTrack').html(controlsHtml);
                    $('#cardsControlsTopTrack .cards-length-select, #cardsPaginationTrack .cards-length-select').on('change', function() {
                        var val = parseInt($(this).val(), 10);
                        if (tabla) tabla.page.len(val).draw(false);
                    });
                }
            }
            actualizarVistaInicialTrack();
            $('#btnViewTablaTrack').on('click', function() {
                aplicarVisibilidadVistaTrack('tabla');
                $('#btnViewTablaTrack').addClass('active');
                $('#btnViewIconosTrack').removeClass('active');
            });
            $('#btnViewIconosTrack').on('click', function() {
                aplicarVisibilidadVistaTrack('iconos');
                $('#btnViewIconosTrack').addClass('active');
                $('#btnViewTablaTrack').removeClass('active');
            });
            $(window).on('resize', function() {
                if (!$('#tablaTrackingWrapper').attr('data-vista')) return;
                actualizarVistaInicialTrack();
            });

            // Aplicar filtros
            $('#btnAplicarFiltros').on('click', function() {
                tabla.ajax.reload();
            });

            // Limpiar filtros
            $('#btnLimpiarFiltros').on('click', function() {
                $('#periodoTipo').val('TODOS');
                var d = new Date();
                $('#fechaUnica').val(d.toISOString().slice(0, 10));
                $('#fechaInicio').val(d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-01');
                $('#fechaFin').val(d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate()).padStart(2, '0'));
                $('#mesUnico').val(d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'));
                $('#mesInicio').val(d.getFullYear() + '-01');
                $('#mesFin').val(d.getFullYear() + '-12');
                aplicarVisibilidadPeriodoTracking();
                $('#filtroUbicacion').val('');
                tabla.ajax.reload();
            });
            function aplicarVisibilidadPeriodoTracking() {
                var t = $('#periodoTipo').val() || '';
                $('#periodoPorFecha, #periodoEntreFechas, #periodoPorMes, #periodoEntreMeses').addClass('hidden');
                if (t === 'POR_FECHA') $('#periodoPorFecha').removeClass('hidden');
                else if (t === 'ENTRE_FECHAS') $('#periodoEntreFechas').removeClass('hidden');
                else if (t === 'POR_MES') $('#periodoPorMes').removeClass('hidden');
                else if (t === 'ENTRE_MESES') $('#periodoEntreMeses').removeClass('hidden');
            }
            $('#periodoTipo').on('change', aplicarVisibilidadPeriodoTracking);
            aplicarVisibilidadPeriodoTracking();
        });
    </script>

    <script>
        function toggleFiltros() {
            const contenido = document.getElementById('contenidoFiltros');
            const icono = document.getElementById('iconoFiltros');

            contenido.classList.toggle('hidden');
            icono.classList.toggle('rotate-180');
        }

        function cambiarTab(tabActivoId, tabInactivoId, contenidoActivoId, contenidoInactivoId) {
            // Cambiar clases de los botones
            const tabActivo = document.getElementById(tabActivoId);
            const tabInactivo = document.getElementById(tabInactivoId);

            tabActivo.className = 'tab-button px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200 border-blue-600 text-blue-600 bg-blue-50';
            tabInactivo.className = 'tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent transition-colors duration-200 text-gray-600 hover:text-gray-800 hover:border-gray-300';

            // Cambiar contenido visible
            document.getElementById(contenidoActivoId).classList.remove('hidden');
            document.getElementById(contenidoInactivoId).classList.add('hidden');

            // Actualizar aria-selected para accesibilidad
            tabActivo.setAttribute('aria-selected', 'true');
            tabInactivo.setAttribute('aria-selected', 'false');
        }

        // Eventos
        document.getElementById('tabTodos').addEventListener('click', function() {
            cambiarTab('tabTodos', 'tabPendientes', 'contenidoTodos', 'contenidoPendientes');
        });

        var tablePendientes = null;
        function initTablePendientesOnce() {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#tablaPendientes')) return;
            if (!document.getElementById('tablaPendientes')) return;
            tablePendientes = $('#tablaPendientes').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[0, 'asc']],
                columnDefs: [{ orderable: false, targets: [3] }],
                drawCallback: function() {
                    if (typeof renderizarTarjetasPendientes === 'function') renderizarTarjetasPendientes();
                },
                initComplete: function() {
                    this.api().columns.adjust();
                    var wrapper = $('#tablaPendientes').closest('.dataTables_wrapper');
                    var $controls = $('#pendDtControls');
                    var $length = wrapper.find('.dataTables_length').first();
                    var $filter = wrapper.find('.dataTables_filter').first();
                    if ($controls.length && $length.length && $filter.length) {
                        $controls.append($length, $filter);
                        var vista = $('#pendientesWrapper').attr('data-vista') || '';
                        $controls.toggle(vista !== 'iconos');
                    }
                }
            });
        }
        function renderizarTarjetasPendientes() {
            if (!tablePendientes) return;
            var api = tablePendientes;
            var cont = $('#cardsContainerPend');
            cont.empty();
            var info = api.page.info();
            if (info.recordsDisplay === 0) {
                cont.html('<p class="col-span-full text-center text-gray-500 py-8">¡Excelente! No hay envíos pendientes.</p>');
                $('#cardsControlsTopPend').empty();
                $('#cardsPaginationPend').html('<span class="text-sm text-gray-600">Mostrando 0 registros</span>');
                return;
            }
            var rowIndex = 0;
            api.rows({ page: 'current' }).every(function() {
                var $row = $(this.node());
                if ($row.find('td[colspan]').length) return;
                rowIndex++;
                var numero = info.start + rowIndex;
                var cod = $row.attr('data-codigo') || $row.find('td').eq(1).text().trim();
                var falta = $row.attr('data-falta') || $row.find('td').eq(2).text().trim();
                var codEsc = (cod || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                var card = '<div class="card-item">' +
                    '<div class="card-numero-row">#' + numero + '</div>' +
                    '<div class="card-contenido">' +
                    '<div class="card-codigo">' + codEsc + '</div>' +
                    '<div class="card-campos">' +
                    '<div class="card-row"><span class="label">Falta:</span> <span class="text-orange-600 font-medium">' + (falta || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span></div>' +
                    '</div>' +
                    '<div class="card-acciones">' +
                    '<button type="button" onclick="cargarEscaneoConCodigo(\'' + (cod || '').replace(/'/g, "\\'") + '\')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition"><i class="fa-solid fa-qrcode mr-2"></i> Escanear / Recepcionar</button>' +
                    '</div></div></div>';
                cont.append(card);
            });
            var len = api.page.len();
            var lengthOptions = [10, 25, 50, 100];
            var lengthSelect = '<label class="inline-flex items-center gap-2"><span>Mostrar</span><select class="cards-length-select">' +
                lengthOptions.map(function(n) { return '<option value="' + n + '"' + (n === len ? ' selected' : '') + '>' + n + '</option>'; }).join('') +
                '</select><span>registros</span></label>';
            var vista = $('#pendientesWrapper').attr('data-vista') || '';
            if (vista === 'iconos') {
                var $toolbarRow = $('#pendIconosControls .reportes-iconos-toolbar-row');
                if (!$toolbarRow.length) {
                    var $filter = $('#pendDtControls .dataTables_filter').detach();
                    var iconosRow = '<div class="reportes-iconos-toolbar-row flex flex-wrap items-center gap-3">' + lengthSelect + '</div>';
                    $('#pendIconosControls').html(iconosRow);
                    if ($filter.length) $('#pendIconosControls .reportes-iconos-toolbar-row').append($filter);
                    $('#pendIconosControls .cards-length-select').on('change', function() {
                        var val = parseInt($(this).val(), 10);
                        if (tablePendientes) tablePendientes.page.len(val).draw(false);
                    });
                } else {
                    var $sel = $toolbarRow.find('.cards-length-select');
                    if ($sel.length) $sel.find('option').remove().end().append(lengthOptions.map(function(n) { return '<option value="' + n + '"' + (n === len ? ' selected' : '') + '>' + n + '</option>'; }).join(''));
                }
                $('#cardsControlsTopPend').empty();
                $('#cardsPaginationPend').html(typeof buildPaginationIconos === 'function' ? buildPaginationIconos(info) : '');
            } else {
                var navBtns = '<div class="flex gap-2 flex-shrink-0">' +
                    '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page === 0 ? 'disabled' : '') + ' onclick="var dt=$(\'#tablaPendientes\').DataTable(); if(dt) dt.page(\'previous\').draw(false);">Anterior</button>' +
                    '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page >= info.pages - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page >= info.pages - 1 ? 'disabled' : '') + ' onclick="var dt=$(\'#tablaPendientes\').DataTable(); if(dt) dt.page(\'next\').draw(false);">Siguiente</button></div>';
                var controlsHtml = '<div class="flex flex-wrap items-center justify-between gap-3 w-full">' + lengthSelect + '<span class="text-sm text-gray-600">Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros</span>' + navBtns + '</div>';
                $('#cardsControlsTopPend').html(controlsHtml);
                $('#cardsPaginationPend').html(controlsHtml);
                $('#cardsControlsTopPend .cards-length-select, #cardsPaginationPend .cards-length-select').on('change', function() {
                    var val = parseInt($(this).val(), 10);
                    if (tablePendientes) tablePendientes.page.len(val).draw(false);
                });
            }
        }
        function aplicarVisibilidadPendientes(vista) {
            var esLista = (vista === 'tabla' || vista === 'lista');
            $('#pendientesWrapper').attr('data-vista', vista);
            if (esLista) {
                var $filter = $('#pendIconosControls .dataTables_filter').detach();
                if ($filter.length) $('#pendDtControls').append($filter);
                $('#pendDtControls').show();
                $('#pendIconosControls').hide();
                $('#viewTarjetasPend').addClass('hidden').css('display', 'none');
                $('#pendientesWrapper .view-lista-wrap').removeClass('hidden').css('display', 'block');
            } else {
                $('#pendDtControls').hide();
                $('#pendIconosControls').show();
                $('#pendientesWrapper .view-lista-wrap').addClass('hidden').css('display', 'none');
                $('#viewTarjetasPend').removeClass('hidden').css('display', 'block');
                $('#cardsContainerPend').attr('data-vista-cards', 'iconos');
                if (typeof renderizarTarjetasPendientes === 'function') renderizarTarjetasPendientes();
            }
            $('#btnViewListaPend').toggleClass('active', esLista);
            $('#btnViewIconosPend').toggleClass('active', !esLista);
        }
        function actualizarVistaInicialPendientes() {
            var wrapper = document.getElementById('pendientesWrapper');
            if (!wrapper || wrapper.getAttribute('data-vista')) return;
            initTablePendientesOnce();
            var w = window.innerWidth;
            var vistaInicial = w < 768 ? 'iconos' : 'tabla';
            wrapper.setAttribute('data-vista', vistaInicial);
            aplicarVisibilidadPendientes(vistaInicial);
        }
        document.getElementById('tabPendientes').addEventListener('click', function() {
            cambiarTab('tabPendientes', 'tabTodos', 'contenidoPendientes', 'contenidoTodos');
            actualizarVistaInicialPendientes();
        });
        document.getElementById('btnViewListaPend').addEventListener('click', function() {
            aplicarVisibilidadPendientes('tabla');
        });
        document.getElementById('btnViewIconosPend').addEventListener('click', function() {
            aplicarVisibilidadPendientes('iconos');
        });

        function cargarEscaneoConCodigo(codigoEnvio) {
            // Cargar la ventana de escaneo
            parent.loadDashboardAndData(
                'modules/tracking/escaneo/dashboard-escaneoQR.php',
                'Escaneo QR',
                'Recepción de envío: ' + codigoEnvio
            );
        }


        async function eliminarRegistro(idRegistro) {
            var ok = await SwalConfirm('¿Estás seguro de que deseas eliminar este registro?\nEsta acción no se puede deshacer.', 'Confirmar eliminación');
            if (!ok) return;

            // Mostrar loading en el botón (opcional, pero recomendado)
            const boton = event.target.closest('button');
            const iconoOriginal = boton.innerHTML;
            boton.disabled = true;
            boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            fetch('eliminar_registro.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: idRegistro
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        // Éxito: recargar tabla
                        mostrarAlerta('Registro eliminado correctamente', false);
                        $('#tabla').DataTable().ajax.reload();
                    } else {
                        mostrarAlerta(data.mensaje || 'Error al eliminar el registro', true);
                    }
                })
                .catch(err => {
                    console.error(err);
                    mostrarAlerta('Error de conexión al eliminar', true);
                })
                .finally(() => {
                    // Restaurar botón
                    boton.disabled = false;
                    boton.innerHTML = iconoOriginal;
                });
        }

        function mostrarAlerta(mensaje, esError = false) {
            Swal.fire({
                icon: esError ? 'error' : 'warning',
                title: esError ? 'Error' : 'Atención',
                text: mensaje,
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'rounded-xl',
                    confirmButton: 'bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-lg'
                }
            });
        }
    </script>

    <script>
        let evidenciasActuales = []; // Array de rutas
        let indiceFotoActual = 0;

        function abrirModalEvidencia(rutasEvidencia) {
            if (!rutasEvidencia || rutasEvidencia.trim() === '') return;

            evidenciasActuales = rutasEvidencia.split(',').map(r => r.trim()).filter(r => r);

            if (evidenciasActuales.length === 0) return;

            indiceFotoActual = 0;
            renderizarCarrusel();
            document.getElementById('modalEvidencia').classList.remove('hidden');
        }

        function cerrarModalEvidencia() {
            document.getElementById('modalEvidencia').classList.add('hidden');
            document.getElementById('carruselFotos').innerHTML = '';
            evidenciasActuales = [];
        }

        function renderizarCarrusel() {
            const carrusel = document.getElementById('carruselFotos');
            carrusel.innerHTML = '';

            evidenciasActuales.forEach((ruta, index) => {
                const div = document.createElement('div');
                div.className = 'min-w-full h-full flex items-center justify-center px-4';
                div.innerHTML = `
            <img src="../../../${ruta}" alt="Evidencia ${index + 1}" 
                 class="max-w-full max-h-full object-contain rounded-lg shadow-xl">
            `;
                carrusel.appendChild(div);
            });

            // Posicionar en la foto actual
            carrusel.style.transform = `translateX(-${indiceFotoActual * 100}%)`;

            // Actualizar contador
            document.getElementById('contadorFotos').textContent = `${indiceFotoActual + 1} / ${evidenciasActuales.length}`;

            // Ocultar flechas si solo hay una foto
            const prev = document.getElementById('prevFoto');
            const next = document.getElementById('nextFoto');
            if (evidenciasActuales.length <= 1) {
                prev.classList.add('hidden');
                next.classList.add('hidden');
            } else {
                prev.classList.remove('hidden');
                next.classList.remove('hidden');
            }
        }

        // Navegación
        document.getElementById('prevFoto').addEventListener('click', () => {
            if (indiceFotoActual > 0) {
                indiceFotoActual--;
                renderizarCarrusel();
            }
        });

        document.getElementById('nextFoto').addEventListener('click', () => {
            if (indiceFotoActual < evidenciasActuales.length - 1) {
                indiceFotoActual++;
                renderizarCarrusel();
            }
        });

        // Abrir foto actual en nueva pestaña
        function abrirFotoActualEnPestana() {
            if (evidenciasActuales.length > 0) {
                window.open("../../../" + evidenciasActuales[indiceFotoActual], '_blank');
            }
        }
    </script>

    <script>
        let registroEditando = null;
        let fotosActualesEdit = []; // Rutas de fotos actuales que el usuario no eliminó
        let nuevasFotosEdit = []; // Nuevas fotos seleccionadas (comprimidas)

        function editarRegistro(id) {
            const tabla = $('#tabla').DataTable();
            let data = null;
            // Obtener datos por id desde la página actual (funciona tanto desde tabla como desde tarjetas)
            tabla.rows({ page: 'current', search: 'applied' }).every(function() {
                const d = this.data();
                if (parseInt(d.id, 10) === parseInt(id, 10)) {
                    data = d;
                    return false; // break
                }
            });

            if (!data) {
                mostrarAlerta('No se pudo obtener los datos del registro. Intente de nuevo desde la lista.', true);
                return;
            }

            registroEditando = data;

            // Llenar campos
            document.getElementById('editIdRegistro').value = data.id;
            document.getElementById('editCodEnvio').value = data.codEnvio;
            document.getElementById('editUbicacion').value = data.ubicacion || '';
            document.getElementById('editComentario').value = data.comentario || '';

            // === EVIDENCIAS ACTUALES ===
            const previewActual = document.getElementById('editEvidenciaPreview');
            previewActual.innerHTML = '';
            const containerActual = document.getElementById('editEvidenciaActual');

            fotosActualesEdit = [];

            if (data.evidencia && data.evidencia.trim() !== '') {
                fotosActualesEdit = data.evidencia.split(',').map(r => r.trim());

                fotosActualesEdit.forEach(ruta => {
                    const div = document.createElement('div');
                    div.className = 'relative group';
                    div.innerHTML = `
                        <img src="../../../${ruta}" class="w-full h-32 object-cover rounded-lg shadow border">
                        
                        <!-- Botón eliminar (siempre encima con z-20) -->
                        <button type="button" onclick="eliminarFotoActualEdit(this, '${ruta}')" 
                                class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white rounded-full w-8 h-8 flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow-lg z-20">
                            ×
                        </button>
                        
                        <!-- Capa para abrir en nueva pestaña (debajo del botón, z-10) -->
                        <a href="../../../${ruta}" target="_blank" 
                        class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition rounded-lg z-10">
                            <i class="fa-solid fa-expand text-white text-2xl"></i>
                        </a>
                    `;
                    previewActual.appendChild(div);
                });
                containerActual.classList.remove('hidden');
            } else {
                containerActual.classList.add('hidden');
            }

            // Limpiar nuevas fotos
            nuevasFotosEdit = [];
            document.getElementById('editNuevasFotosPreview').innerHTML = '';

            // Reset inputs
            document.getElementById('editInputFoto').value = '';
            document.getElementById('editInputGaleria').value = '';

            // Abrir modal
            document.getElementById('modalEditar').classList.remove('hidden');
        }

        // Eliminar foto actual (solo del array, no del servidor)
        function eliminarFotoActualEdit(boton, ruta) {
            // Quitar del array
            fotosActualesEdit = fotosActualesEdit.filter(r => r !== ruta);

            // Quitar del DOM
            boton.closest('div').remove();

            // Si no quedan fotos actuales, ocultar contenedor
            if (fotosActualesEdit.length === 0) {
                document.getElementById('editEvidenciaActual').classList.add('hidden');
            }
        }

        // Compresión de imagen (reutiliza tu función anterior)
        async function comprimirImagen(file) {
            return new Promise((resolve) => {
                const img = new Image();
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        let width = img.width;
                        let height = img.height;
                        if (width > 1200 || height > 1200) {
                            if (width > height) {
                                height = Math.round(height * (1200 / width));
                                width = 1200;
                            } else {
                                width = Math.round(width * (1200 / height));
                                height = 1200;
                            }
                        }

                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob((blob) => {
                            resolve(new File([blob], file.name, {
                                type: 'image/jpeg'
                            }));
                        }, 'image/jpeg', 0.7);
                    };
                };
                reader.readAsDataURL(file);
            });
        }

        // Agregar nuevas fotos
        async function agregarFotosEdit(files) {
            const previewNuevas = document.getElementById('editNuevasFotosPreview');

            for (let file of files) {
                if (!file.type.startsWith('image/')) continue;

                const archivoComprimido = await comprimirImagen(file);

                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative group';
                    div.innerHTML = `
                <img src="${e.target.result}" class="w-full h-32 object-cover rounded-lg shadow border">
                <button type="button" onclick="eliminarNuevaFotoEdit(this)" 
                        class="absolute top-2 right-2 bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow-lg z-10">
                    ×
                </button>
            `;
                    previewNuevas.appendChild(div);
                };
                reader.readAsDataURL(archivoComprimido);

                nuevasFotosEdit.push(archivoComprimido);
            }
        }

        function eliminarNuevaFotoEdit(boton) {
            const div = boton.closest('div');
            const index = Array.from(div.parentNode.children).indexOf(div);
            nuevasFotosEdit.splice(index, 1);
            div.remove();
        }

        // Eventos para nuevas fotos
        document.getElementById('editInputFoto').addEventListener('change', (e) => {
            if (e.target.files.length) agregarFotosEdit(e.target.files);
        });
        document.getElementById('editInputGaleria').addEventListener('change', (e) => {
            if (e.target.files.length) agregarFotosEdit(e.target.files);
        });

        // Guardar edición
        document.getElementById('btnGuardarEdicion').addEventListener('click', function() {
            const id = document.getElementById('editIdRegistro').value;
            const ubicacion = document.getElementById('editUbicacion').value;
            const comentario = document.getElementById('editComentario').value.trim();

            const formData = new FormData();
            formData.append('id', id);
            formData.append('ubicacion', ubicacion);
            formData.append('comentario', comentario);

            // === ENVIAR LAS FOTOS QUE QUEDAN (las que no se eliminaron) ===
            formData.append('fotos_restantes', fotosActualesEdit.join(','));

            // === ENVIAR LAS NUEVAS FOTOS ===
            nuevasFotosEdit.forEach((foto, i) => {
                formData.append('nuevas_evidencias[]', foto);
            });

            fetch('actualizar_registro.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        mostrarAlerta('Registro actualizado correctamente', false);
                        cerrarModalEditar();
                        $('#tabla').DataTable().ajax.reload();
                    } else {
                        mostrarAlerta(data.mensaje || 'Error al actualizar', true);
                    }
                })
        });

        function cerrarModalEditar() {
            document.getElementById('modalEditar').classList.add('hidden');
            registroEditando = null;
            fotosActualesEdit = [];
            nuevasFotosEdit = [];
        }
    </script>

</body>

</html>