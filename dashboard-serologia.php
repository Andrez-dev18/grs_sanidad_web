<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

$queryPendientes = "
    SELECT 
        d.codEnvio,
        d.posSolicitud,
        d.fecToma,
        d.codRef,
        d.numMuestras AS numeroMuestras,
        d.nomMuestra,
        c.nomLab,
        d.estado_cuanti AS estado_cuanti,
        
        GROUP_CONCAT(d.nomAnalisis ORDER BY d.nomAnalisis SEPARATOR ', ') AS analisis,
        
        GROUP_CONCAT(d.codAnalisis ORDER BY d.codAnalisis SEPARATOR ',') AS analisisCodigos,
        
        GROUP_CONCAT(d.obs ORDER BY d.posSolicitud SEPARATOR ' | ') AS observaciones

    FROM san_fact_solicitud_det d
    INNER JOIN san_fact_solicitud_cab c
           ON d.codEnvio = c.codEnvio
            
    WHERE d.estado_cuanti = 'pendiente'

    GROUP BY 
        d.codEnvio,
        d.posSolicitud,
        d.fecToma,
        d.codRef,
        d.numMuestras,
        d.nomMuestra,
        c.nomLab

    ORDER BY d.fecToma DESC,
             d.codEnvio DESC,
             d.posSolicitud ASC
";

$resPendientes = $conexion->query($queryPendientes);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Serología</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f3f4f6;
        }

        .sidebar-item {
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }

        .sidebar-item:hover,
        .sidebar-item.active {
            background: #e0f2fe;
            border-left-color: #3b82f6;
        }

        .input-lab {
            width: 100%;
            border: 1px solid #d1d5db;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
        }

        .input-lab:focus {
            border-color: #2563eb;
            outline: none;
            ring: 2px;
            ring-color: #bfdbfe;
        }

        .hidden {
            display: none;
        }

        .grid-niveles {
            display: grid;
            grid-template-columns: repeat(13, 1fr);
        }

        #modalAgregarEnfermedad {
            animation: fadeIn 0.2s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body class="h-screen flex overflow-hidden bg-gray-50">

    <aside class="w-80 bg-white border-r border-gray-200 flex flex-col z-20 shadow-lg shrink-0">
        <div class="p-5 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide flex items-center gap-2">
                <i class="fas fa-inbox text-blue-600"></i> Solicitudes Pendientes
            </h2>
            <input type="text" id="filtroSidebar" onkeyup="filtrarLista()" placeholder="Buscar código..."
                class="mt-3 w-full p-2 text-xs border rounded bg-white">

               <!-- Filtros por estado
            <div class="mt-3 flex gap-2">
                <button onclick="filtrarPorEstado('todos')" 
                    class="filtro-estado-btn flex-1 px-3 py-1.5 text-xs font-semibold rounded-full transition-all bg-blue-600 text-white"
                    data-estado="todos">
                    Todos
                </button>
                <button onclick="filtrarPorEstado('pendiente')" 
                    class="filtro-estado-btn flex-1 px-3 py-1.5 text-xs font-semibold rounded-full transition-all bg-gray-200 text-gray-600 hover:bg-gray-300"
                    data-estado="pendiente">
                    Pendientes
                </button>
                <button onclick="filtrarPorEstado('completado')" 
                    class="filtro-estado-btn flex-1 px-3 py-1.5 text-xs font-semibold rounded-full transition-all bg-gray-200 text-gray-600 hover:bg-gray-300"
                    data-estado="completado">
                    Completados
                </button>
            </div>
        </div> -->

        

        <div class="flex-1 overflow-y-auto p-2 space-y-1" id="listaPendientes">
            <aside class="w-80 bg-white border-r border-gray-200 flex flex-col z-20 shadow-sm shrink-0">
                <!-- HEADER -
                <div class="p-4 border-b border-gray-200 bg-white">
                    <h2 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">
                        Solicitudes Pendientes
                    </h2>
                    <input type="text" id="filtroSidebar" onkeyup="filtrarLista()" placeholder="Buscar código..."
                        class="w-full px-3 py-2 text-xs border border-gray-300 rounded focus:outline-none focus:border-gray-400 bg-gray-50">
                </div>-->

                <!-- LISTA -->
                <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50" id="listaPendientes">
                    <?php if ($resPendientes && $resPendientes->num_rows > 0): ?>
                        <?php while ($row = $resPendientes->fetch_assoc()): ?>
                            <?php $estado_item = $row['estado_cuanti'] ?? 'pendiente'; ?>
                            <div class="sidebar-item cursor-pointer p-3 rounded bg-white border border-gray-200 hover:border-gray-400 transition-all"
                                data-estado="<?= $estado_item ?>"
                                onclick="cargarSolicitud('<?= $row['codEnvio'] ?>', '<?= $row['fecToma'] ?>', '<?= $row['codRef'] ?>', '<?= $estado_item ?>')">

                                <!-- Código -->
                                <div class="font-bold text-sm text-gray-800 mb-1">
                                    <?= $row['codEnvio'] ?>
                                </div>

                                <!-- Estado debajo del código -->
                                <div class="mb-2">
                                    <?php if (strtolower($estado_item) === 'pendiente'): ?>
                                        <span
                                            class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                                    <?php else: ?>
                                        <span
                                            class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"><?= ucfirst($estado_item) ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Info -->
                                <div class="text-xs text-gray-500 space-y-1">
                                    <p>
                                        Fecha: <?= date('d/m/Y', strtotime($row['fecToma'])) ?> •
                                        Ref: <?= $row['codRef'] ?>
                                    </p>
                                    <p>
                                        N° Solicitud: <?= $row['posSolicitud'] ?>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-10 text-gray-400 text-xs">
                            No hay pendientes
                        </div>
                    <?php endif; ?>
                </div>
            </aside>

        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 bg-gray-100 relative">
        <header class="h-16 bg-white border-b border-gray-200 flex justify-between items-center px-6 shadow-sm z-10">
            <h1 class="text-lg font-bold text-gray-800"><i class="fas fa-microscope text-blue-600"></i> Respuesta de
                Laboratorio cuantitativo</h1>
            <button onclick="document.location.reload()" class="text-gray-500 hover:text-blue-600"><i
                    class="fas fa-sync"></i></button>
        </header>

        <div class="flex-1 overflow-auto p-6 flex justify-center">

            <div id="emptyState" class="flex flex-col items-center justify-center text-gray-400 h-full">
                <i class="fas fa-arrow-left text-4xl mb-2"></i>
                <p>Seleccione una solicitud de la izquierda</p>
            </div>

            <div id="formPanel"
                class="bg-white w-full max-w-5xl rounded-xl shadow-lg border border-gray-200 hidden flex flex-col">

                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <div>
                        <h2 class="font-bold text-gray-700"><span id="lblCodigo" class="text-blue-600"></span></h2>
                        <div id="lblEstado" class="mt-1"></div>
                    </div>
                    <span id="badgeTipo"
                        class="px-3 py-1 rounded-full text-xs font-bold bg-gray-200 text-gray-600">...</span>
                </div>

                <form id="formAnalisis" onsubmit="guardar(event)" class="flex-1 overflow-y-auto p-6">
                    <input type="hidden" id="action" name="action" value="create">
                    <input type="hidden" id="tipo_ave_hidden" name="tipo_ave">
                    <input type="hidden" id="codRef_granja" name="codigo_granja">
                    <input type="hidden" id="codRef_campana" name="codigo_campana">
                    <input type="hidden" id="codRef_galpon" name="numero_galpon">

                    <div class="grid grid-cols-4 gap-4 mb-6 bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <div>
                            <label class="text-[10px] uppercase font-bold text-gray-500">Código</label>
                            <input type="text" name="codigo_solicitud" id="codigoSolicitud" class="input-lab bg-white"
                                readonly>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-gray-500">Fecha Toma</label>
                            <input type="date" name="fecha_toma" id="fechaToma" class="input-lab bg-white" readonly>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-blue-700">REF</label>
                            <input type="number" name="edad_aves" id="edadAves"
                                class="input-lab font-bold text-blue-800 text-center" readonly>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-gray-500">Nº Informe</label>
                            <input type="text" name="numero_informe" id="numeroInforme" class="input-lab">
                        </div>
                    </div>

                    <div id="camposEspecificos" class="grid grid-cols-3 gap-4 mb-6 border-b pb-6"></div>

                    <h3 class="text-sm font-bold text-gray-700 mb-3 uppercase">Resultados Analíticos</h3>
                    <div id="contenedorEnfermedades" class="space-y-4"></div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Subir archivos (PDF, Word, Excel, Imágenes, etc.) — Opcional
                        </label>

                        <input type="file" id="archivoPdf" name="archivoPdf[]" multiple
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.png,.jpg,.jpeg" class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-600 file:text-white
                                            hover:file:bg-blue-700
                                            border border-gray-300 rounded-md p-1" />

                        <div id="fileList" class="mt-3 space-y-2"></div>

                        <p class="text-xs text-gray-500 mt-1">(Máx. 10 MB por archivo)</p>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-lg font-bold shadow-lg shadow-blue-500/30 transition-all transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i> Guardar Resultados
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="dashboard-serologia.js"></script>
</body>

</html>
