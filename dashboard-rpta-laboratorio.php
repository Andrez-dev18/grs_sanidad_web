<?php

session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

//ruta relativa a la conexion
include_once 'conexion_grs_joya\conexion.php';
$conexion = conectar_sanidad();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

$query = "
    SELECT d.codigoEnvio, 
           d.posicionSolicitud, 
           d.fechaToma, 
           d.codigoReferencia, 
           d.observaciones, 
           d.analisis, 
           d.numeroMuestras
    FROM com_db_muestra_detalle d
    INNER JOIN com_db_muestra_cabecera c
           ON d.codigoEnvio = c.codigoEnvio
    WHERE c.estado = 'pendiente'
    ORDER BY d.fechaToma DESC, d.posicionSolicitud ASC;
";

$result = $conexion->query($query);


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Respuesta lab</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

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

        <div class="flex flex-col h-screen bg-[#f5f6f8]">

            <!-- HEADER -->
            <header class="bg-white px-8 py-6 border-b border-[#e5e7eb]">
                <h1 class="text-2xl font-semibold text-[#2c3e50]">🧪 Respuesta de Laboratorio</h1>
                <p class="text-sm text-[#6c757d] mt-1">Adjunte los resultados enviados por el laboratorio</p>
            </header>

            <div class="flex flex-1 overflow-hidden flex-col md:flex-row">

                <!-- SIDEBAR -->
                <aside class="bg-white w-full md:w-[280px] border-r border-[#e5e7eb] flex flex-col">
                    <div class="px-6 py-5 border-b border-[#e5e7eb]">
                        <h3 class="text-base font-semibold text-[#2c3e50]">Solicitudes Pendientes</h3>

                        <input
                            id="searchInput"
                            type="text"
                            placeholder="Buscar..."
                            onkeyup="filterOrders(this.value)"
                            class="mt-3 w-full px-3 py-2 border border-[#d0d7de] rounded-md text-sm placeholder-[#a0aec0]
                                focus:outline-none focus:ring-2 focus:ring-[#0066cc]/30">
                    </div>

                    <!-- Lista de solicitudes -->
                    <div id="pendingOrdersList" class="flex-1 overflow-y-auto p-4 space-y-3">
                        <?php

                        if ($result && $result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):

                        ?>
                                <button id="item-<?php echo $row['codigoEnvio']; ?>"
                                    onclick="openDetail('<?php echo $row['codigoEnvio']; ?>', '<?php echo date('d/m/Y', strtotime($row['fechaToma'])) ?>')"
                                    class="w-full text-left p-3 rounded-md hover:bg-gray-50 transition border border-transparent hover:border-gray-100">

                                    <div class="font-medium text-sm text-[#1f2937]">
                                        <?php echo htmlspecialchars($row['codigoEnvio']); ?>
                                    </div>

                                    <div class="text-xs text-gray-500 mt-1">
                                        Fecha: <?php echo date('d/m/Y', strtotime($row['fechaToma'])); ?>
                                        • Ref: <?php echo htmlspecialchars($row['codigoReferencia']); ?>
                                    </div>

                                </button>

                            <?php
                            endwhile;
                        else:
                            ?>

                            <div class="text-gray-500 text-sm">No hay solicitudes pendientes.</div>

                        <?php endif; ?>
                    </div>
                </aside>

                <!-- MAIN CONTENT -->
                <main class="flex-1 overflow-y-auto p-6 md:p-10">

                    <!-- EMPTY STATE (lo que se ve al inicio) -->
                    <div id="emptyStatePanel" class="mx-auto max-w-6xl">
                        <div class="border-2 border-dashed border-[#cfd8e3] rounded-md p-12 md:p-32 bg-white flex items-center justify-center">
                            <div class="text-center">
                                <h3 class="text-lg font-medium text-[#374151] mb-1">Seleccione una solicitud</h3>
                                <p class="text-sm text-[#9ca3af]">Elija una orden de la lista para adjuntar su respuesta</p>
                            </div>
                        </div>
                    </div>

                    <!-- DETAIL PANEL (oculto por defecto) -->
                    <div id="responseDetailPanel" class="hidden mx-auto max-w-6xl">
                        <div class="bg-white rounded-lg shadow-sm p-8">

                            <!-- Cabecera detalle -->
                            <div class="pb-4 border-b border-[#e5e7eb] mb-6 flex flex-col md:flex-row justify-between items-start gap-4">
                                <div>
                                    <h2 id="detailCodigo" class="text-3xl font-bold text-[#1f2937]">SAN-000000</h2>
                                    <span id="badgeStatus" class="inline-block mt-2 px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium">Pendiente de Respuesta</span>
                                </div>

                                <div class="text-sm text-gray-600 mt-3 md:mt-0 flex flex-col gap-1">
                                    <span id="detailFecha">📅 01/01/2024</span>
                                    <span id="detailLab">🔬 Laboratorio</span>
                                </div>
                            </div>

                            <!-- analisis section -->
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-gray-800">Seleccionar resultados de análisis</h3>

                                    <button id="addAnalisis"
                                        class="px-5 py-2 rounded-md text-white bg-green-600 hover:bg-green-700">
                                        ➕ Agregar nuevo análisis
                                    </button>
                                </div>

                                <div id="analisisContainer" class="grid grid-cols-[repeat(auto-fit,minmax(260px,1fr))] gap-4"></div>


                                <!-- Botones -->
                                <div class="mt-6 flex justify-end gap-3">
                                    <button onclick="closeDetail()" class="px-5 py-2 rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-100">Cancelar</button>
                                    <button onclick="guardarResultados()" class="px-5 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700">💾 Guardar Respuesta</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </main>
            </div>
        </div>


        <!-- Modal Selección Múltiple de Análisis -->
        <div id="modalAnalisis" class="fixed inset-0 bg-black bg-opacity-40 hidden flex justify-center items-center">
            <div class="bg-white rounded-lg p-6 w-[950px] shadow-lg max-h-[85vh] overflow-y-auto relative">

                <!-- Botón X para cerrar -->
                <button onclick="cerrarModalAnalisis()"
                    class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl font-bold">
                    ✕
                </button>

                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Agregar análisis</h2>

                <div id="listaAnalisis" class="space-y-3">
                    <!-- Aquí se cargarán los grupos con checkboxes -->
                </div>

                <div class="flex justify-end mt-6 gap-3">
                    <button onclick="cerrarModalAnalisis()" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
                    <button onclick="confirmarAnalisisMultiples()"
                        class="px-4 py-2 bg-blue-600 text-white rounded">
                        Agregar Seleccionados
                    </button>
                </div>

            </div>
        </div>





        <!-- JS funcional para el dropzone y toggles -->
        <script>
            async function openDetail(code, fechaToma) {

                document.getElementById('emptyStatePanel').classList.add('hidden');
                document.getElementById('responseDetailPanel').classList.remove('hidden');
                document.getElementById('detailCodigo').textContent = code;

                document.getElementById('detailFecha').textContent = fechaToma;

                const cont = document.getElementById("analisisContainer");
                cont.innerHTML = "<div class='text-gray-500 text-sm'>Cargando análisis...</div>";

                let res = await fetch("getAnalisisDetalle.php?codigoEnvio=" + code);
                let data = await res.json();

                cont.innerHTML = "";

                if (!Array.isArray(data) || data.length === 0) {
                    cont.innerHTML = "<div class='text-gray-500 text-sm'>No hay análisis disponibles.</div>";
                    return;
                }

                data.forEach(item => {
                    crearBloqueAnalisis(item.analisisCodigo, item.nombre, item.resultados);
                });
            }


            async function guardarResultados() {
                const code = document.getElementById("detailCodigo").textContent;
                const cont = document.getElementById("analisisContainer");

                let datos = [];

                cont.querySelectorAll("select").forEach(sel => {

                    let codigoAnalisis = sel.dataset.codigo;
                    let nombre = sel.dataset.nombre;
                    let resultado = sel.value;

                    // 🔥 OBTENER EL COMMENT DEL MISMO BLOQUE DEL SELECT
                    let block = sel.closest(".bloque-analisis");
                    let observaciones = block.querySelector("textarea").value;

                    datos.push({
                        analisisCodigo: codigoAnalisis,
                        analisisNombre: nombre,
                        resultado: resultado,
                        observaciones: observaciones
                    });
                });

                let res = await fetch("guardarResultAnalisis.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        codigoEnvio: code,
                        analisis: datos
                    })
                });

                let r = await res.json();

                if (r.success) {
                    //  Eliminar la tarjeta del sidebar
                    const item = document.getElementById("item-" + code);
                    if (item) item.remove();
                    document.getElementById("analisisContainer").innerHTML = "";

                    // 🧹 Si ya no quedan pendientes, mostrar mensaje
                    const list = document.getElementById("pendingOrdersList");
                    if (list.children.length === 0) {
                        list.innerHTML = `<div class="text-gray-500 text-sm">No hay solicitudes pendientes.</div>`;
                    }

                    alert("Resultados guardados correctamente");
                } else {
                    alert("Error al guardar: " + r.error);
                }
            }
        </script>

        <script>
            document.getElementById("addAnalisis").addEventListener("click", abrirModalAnalisis);

            async function abrirModalAnalisis() {
                const modal = document.getElementById("modalAnalisis");
                modal.classList.remove("hidden");

                const cont = document.getElementById("listaAnalisis");
                cont.innerHTML = "<div class='text-gray-500 text-sm'>Cargando...</div>";

                try {
                    const resp = await fetch("getAnalisisLista.php", {
                        cache: "no-store"
                    });
                    if (!resp.ok) throw new Error("Error en la petición");

                    const lista = await resp.json();
                    cont.innerHTML = "";

                    // lista viene así:
                    // { "Aves vivas": [ {codigo, nombre},... ], "Sueros": [...], ... }

                    Object.keys(lista).forEach(tipo => {

                        // Encabezado del grupo
                        const titulo = document.createElement("h3");
                        titulo.className = "text-md font-semibold text-gray-800 mt-4 mb-2";
                        titulo.textContent = "📌 " + tipo;
                        cont.appendChild(titulo);

                        // Contenedor grid horizontal
                        const grid = document.createElement("div");
                        grid.className = "grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mb-3";

                        lista[tipo].forEach(a => {
                            const item = document.createElement("label");
                            item.className =
                                "flex items-center gap-2 p-2 rounded-lg border border-gray-200 shadow-sm bg-gray-50 hover:bg-gray-100 cursor-pointer transition";

                            item.innerHTML = `
                                        <input type="checkbox" class="chkAnalisis h-4 w-4" 
                                            value="${a.codigo}" 
                                            data-nombre="${escapeHtml(a.nombre)}">

                                        <span class="truncate text-gray-800 text-sm font-medium">
                                            ${escapeHtml(a.nombre)} ${a.codigo ? `(${a.codigo})` : ""}
                                        </span>
                                    `;


                            grid.appendChild(item);
                        });

                        cont.appendChild(grid);
                    });

                } catch (err) {
                    console.error(err);
                    cont.innerHTML = "<div class='text-red-500 text-sm'>Error cargando análisis.</div>";
                }

                function escapeHtml(str) {
                    return String(str).replace(/[&<>"']/g, s => ({
                        "&": "&amp;",
                        "<": "&lt;",
                        ">": "&gt;",
                        '"': "&quot;",
                        "'": "&#39;"
                    } [s]));
                }
            }




            function cerrarModalAnalisis() {
                document.getElementById("modalAnalisis").classList.add("hidden");
            }

            async function confirmarAnalisisMultiples() {

                const seleccionados = [...document.querySelectorAll(".chkAnalisis:checked")];

                if (seleccionados.length === 0) {
                    cerrarModalAnalisis();
                    return;
                }

                for (let chk of seleccionados) {

                    let codigo = chk.value;
                    let nombre = chk.dataset.nombre;

                    let res = await fetch("getTiposResultado.php?codigoAnalisis=" + codigo);
                    let resultados = await res.json();

                    crearBloqueAnalisis(codigo, nombre, resultados, true);
                }

                cerrarModalAnalisis();
            }


            function crearBloqueAnalisis(codigo, nombre, resultados, esManual = false) {

                const cont = document.getElementById("analisisContainer");

                let block = document.createElement("div");
                block.className = "bloque-analisis relative bg-white shadow-sm border rounded-xl p-3";

                // --- Botón para eliminar (solo manuales) ---
                if (esManual) {
                    let removeBtn = document.createElement("button");
                    removeBtn.innerHTML = "x";
                    removeBtn.className =
                        "absolute top-2 right-2 text-gray-500 hover:text-red-600 px-2 py-1";
                    removeBtn.title = "Eliminar análisis";

                    // eliminar bloque
                    removeBtn.onclick = () => {
                        block.remove();
                    };

                    block.appendChild(removeBtn);
                }

                // --- Título ---
                let title = document.createElement("div");
                title.className = "text-[13px] font-semibold text-gray-700 mb-2";
                title.textContent = `${nombre} (${codigo})`;

                // --- Select ---
                let select = document.createElement("select");
                select.className =
                    "w-full px-2 py-2 border border-gray-300 text-sm rounded-md focus:ring-2 focus:ring-blue-300";
                select.name = "resultado_" + codigo;
                select.dataset.nombre = nombre;
                select.dataset.codigo = codigo;

                select.innerHTML = `<option value="" selected disabled>Seleccionar resultado</option>`;

                resultados.forEach(r => {
                    let opt = document.createElement("option");
                    opt.value = r;
                    opt.textContent = r;
                    select.appendChild(opt);
                });

                // --- Textarea ---
                let textarea = document.createElement("textarea");
                textarea.placeholder = "Observaciones...";
                textarea.className =
                    "w-full mt-2 p-2 border rounded-md text-sm h-20 resize-none focus:ring-2 focus:ring-blue-300";
                textarea.name = "comentario_" + codigo;

                block.appendChild(title);
                block.appendChild(select);
                block.appendChild(textarea);

                cont.appendChild(block);
            }
        </script>



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