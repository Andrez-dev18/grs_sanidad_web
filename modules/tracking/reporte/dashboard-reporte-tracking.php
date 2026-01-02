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
            $rol = strtolower(trim($row['rol_sanidad'] ?? 'user'));
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
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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

        .data-table {
            width: 100% !important;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.875rem;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .data-table th {
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
            font-weight: 600;
            color: #ffffff !important;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table tbody tr:hover {
            background-color: #eff6ff !important;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding: 1rem;
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            margin: 0 0.5rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            margin-left: 0.5rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 1rem !important;
            margin: 0 0.25rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%) !important;
            color: white !important;
            border: 1px solid #1e40af !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #eff6ff !important;
            color: #1d4ed8 !important;
        }

        table.dataTable thead .sorting:before,
        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_desc:before,
        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:after {
            color: white !important;
        }

        .dataTables_wrapper {
            overflow-x: visible !important;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid py-4">

        <!-- CARD FILTROS PLEGABLE -->
        <div class="mx-5 mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

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

                <!-- GRID DE FILTROS -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                    <!-- Fecha inicio -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                        <input type="date" id="filtroFechaInicio"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>

                    <!-- Fecha fin -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                        <input type="date" id="filtroFechaFin"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>

                    <!-- ubicacion -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ubicacion</label>
                        <select id="filtroUbicacion"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <option value="">Seleccionar</option>
                            <option value="GRS">GRS</option>
                            <option value="Transporte">Transporte</option>
                            <option value="Laboratorio">Laboratorio</option>
                        </select>
                    </div>

                </div>

                <!-- ACCIONES -->
                <div class="mt-6 flex flex-wrap justify-end gap-4">

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
                <div class="card-body p-0">
                    <div class="table-wrapper overflow-x-auto">
                        <table id="tabla" class="data-table w-full text-sm border-collapse">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 text-left border-b">#</th>
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
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="contenidoPendientes" class="tab-content hidden">
                <!-- TABLA SIMPLE DE PENDIENTES -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Envíos pendientes de acción
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium text-gray-700">Código de envío</th>
                                    <th class="px-6 py-3 text-center font-medium text-gray-700">Falta</th>
                                    <th class="px-6 py-3 text-center font-medium text-gray-700">Acción</th>
                                </tr>
                            </thead>
                            <?php
                            // Obtener envíos que tienen GRS
                            $enviosGRS = $conexion->query("SELECT DISTINCT codEnvio FROM san_dim_historial_resultados WHERE ubicacion = 'GRS'");

                            $pendientes = [];

                            while ($row = $enviosGRS->fetch_assoc()) {
                                $cod = $row['codEnvio'];

                                // Verificar si tiene Transporte y Laboratorio
                                $tieneTransporte = $conexion->query("SELECT 1 FROM san_dim_historial_resultados WHERE codEnvio = '$cod' AND ubicacion = 'Transporte' LIMIT 1")->num_rows > 0;
                                $tieneLaboratorio = $conexion->query("SELECT 1 FROM san_dim_historial_resultados WHERE codEnvio = '$cod' AND ubicacion = 'Laboratorio' LIMIT 1")->num_rows > 0;

                                if (!$tieneTransporte || !$tieneLaboratorio) {
                                    $falta = [];
                                    if (!$tieneTransporte) $falta[] = 'Recoger por transportista';
                                    if (!$tieneLaboratorio) $falta[] = 'Recepción en laboratorio';

                                    $pendientes[] = [
                                        'codEnvio' => $cod,
                                        'falta' => implode(' y ', $falta)
                                    ];
                                }
                            }
                            ?>
                            <tbody id="tablaPendientes">
                                <?php if (empty($pendientes)): ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                            ¡Excelente! No hay envíos pendientes.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendientes as $p): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-blue-600">
                                                <?php echo htmlspecialchars($p['codEnvio']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-center text-orange-600 font-medium">
                                                <?php echo $p['falta']; ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <button onclick="cargarEscaneoConCodigo('<?php echo htmlspecialchars($p['codEnvio']); ?>')"
                                                    class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition shadow hover:shadow-md">
                                                    <i class="fa-solid fa-qrcode mr-2"></i>
                                                    Escanear / Recepcionar
                                                </button>
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
                <div class="px-6 py-5 border-t bg-gray-50 rounded-b-xl flex justify-end gap-3">
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

    <script>
        $(document).ready(function() {
            let tabla = $('#tabla').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'tracking_historial.php', // archivo PHP que devuelve los datos
                    type: 'POST',
                    data: function(d) {
                        // Enviar filtros adicionales
                        d.fechaInicio = $('#filtroFechaInicio').val();
                        d.fechaFin = $('#filtroFechaFin').val();
                        d.ubicacion = $('#filtroUbicacion').val();
                    }
                },
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [7, 'desc']
                ], // ordenar por fecha registro descendente
                columns: [{
                        data: 'id',
                        className: 'text-center'
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
                        render: function(data) {
                            return data ? data.substring(0, 100) + (data.length > 100 ? '...' : '') : '-';
                        }
                    },
                    {
                        data: 'evidencia',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return data ? `<button onclick="abrirModalEvidencia('${data}')" class="text-blue-600 hover:underline">Ver evidencias</button>` : '-';
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
                            return `
                    <div class="flex justify-center gap-3">
                        <button onclick="editarRegistro(${row.id})" class="text-green-600 hover:text-green-800">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button onclick="eliminarRegistro(${row.id})" class="text-red-600 hover:text-red-800">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    `;
                        }
                    }
                ]
            });

            // Aplicar filtros
            $('#btnAplicarFiltros').on('click', function() {
                tabla.ajax.reload();
            });

            // Limpiar filtros
            $('#btnLimpiarFiltros').on('click', function() {
                $('#filtroFechaInicio').val('');
                $('#filtroFechaFin').val('');
                $('#filtroUbicacion').val('');
                tabla.ajax.reload();
            });
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

        document.getElementById('tabPendientes').addEventListener('click', function() {
            cambiarTab('tabPendientes', 'tabTodos', 'contenidoPendientes', 'contenidoTodos');
        });

        function cargarEscaneoConCodigo(codigoEnvio) {
            // Cargar la ventana de escaneo
            parent.loadDashboardAndData(
                'modules/tracking/escaneo/dashboard-escaneoQR.php',
                'Escaneo QR',
                'Recepción de envío: ' + codigoEnvio
            );
        }


        function eliminarRegistro(idRegistro) {
            // Confirmación simple del navegador
            if (!confirm('¿Estás seguro de que deseas eliminar este registro?\nEsta acción no se puede deshacer.')) {
                return;
            }

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
            const data = tabla.row($(event.target).closest('tr')).data();

            if (!data) {
                mostrarAlerta('No se pudo obtener los datos del registro', true);
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