let currentPosition = null;

async function openDetail(code, fechaToma, posicion) {

    resaltarItemSidebar(code, posicion);
    //cargarCabecera(code, posicion);

    document.getElementById('emptyStatePanel').classList.add('hidden');
    document.getElementById('responseDetailPanel').classList.remove('hidden');
    document.getElementById('detailCodigo').textContent = code;
    currentPosition = posicion;

    document.getElementById('detailFecha').textContent = fechaToma;

    // 👉 cambiar botón
    const btn = document.getElementById("btnGuardarResultados");
    btn.textContent = "💾 Guardar Respuesta";
    btn.dataset.modo = "registrar";

    const cont = document.getElementById("analisisContainer");
    cont.innerHTML = "<div class='text-gray-500 text-sm'>Cargando análisis...</div>";

    let res = await fetch("getAnalisisDetalle.php?codigoEnvio=" + code + "&posicion=" + posicion);
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

function closeDetail() {
    // Ocultar panel de detalle / mostrar empty
    document.getElementById('responseDetailPanel').classList.add('hidden');
    document.getElementById('emptyStatePanel').classList.remove('hidden');

    // Quitar resaltado del sidebar (si existe)
    document.querySelectorAll("#pendingOrdersList button").forEach(btn => {
        btn.classList.remove("selected-order");
    });

    limpiarArchivosAdjuntos();

    // Quitar foco activo (si venía del botón)
    try {
        if (document.activeElement && typeof document.activeElement.blur === "function") {
            document.activeElement.blur();
        }
    } catch (e) {
        // no romper si algo falla
        console.warn("closeDetail blur failed:", e);
    }
}

function resaltarItemSidebar(code, pos) {
    // 1. remover selección previa
    document.querySelectorAll("#pendingOrdersList button").forEach(btn => {
        btn.classList.remove("selected-order");
    });

    // 2. agregar selección al actual
    const item = document.getElementById(`item-${code}-${pos}`);
    if (item) {
        item.classList.add("selected-order");
    }
}

async function openDetailCompletado(code, fechaToma, posicion) {

    resaltarItemSidebar(code, posicion);

    document.getElementById('emptyStatePanel').classList.add('hidden');
    document.getElementById('responseDetailPanel').classList.remove('hidden');

    document.getElementById('detailCodigo').textContent = code;
    document.getElementById('detailFecha').textContent = fechaToma;
    await cargarArchivosCompletados(code, posicion);

    // 👉 cambiar botón
    const btn = document.getElementById("btnGuardarResultados");
    btn.textContent = "Actualizar resultados";
    btn.dataset.modo = "update";

    const cont = document.getElementById("analisisContainer");
    cont.innerHTML = "<div class='text-gray-500 text-sm'>Cargando resultados...</div>";

    const res = await fetch(
        `getAnalisisCompletado.php?codigoEnvio=${code}&posicion=${posicion}`
    );
    const data = await res.json();

    cont.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
        cont.innerHTML = "<div class='text-gray-500 text-sm'>No hay resultados registrados.</div>";
        return;
    }

    data.forEach(item => {
        document.getElementById('fechaRegistroLab').value = item.fechaLabRegistro;
        crearBloqueAnalisis(
            item.analisis_codigo,
            item.analisis_nombre,
            item.opciones,
            false,
            item.resultado,
            item.obs,
            item.id
        );
    });
}


function cargarCabecera(codEnvio, fecToma, pos) {
    
    fetch(`get_solicitud_cabecera.php?codEnvio=${codEnvio}&posSolicitud=${pos}`)
        .then(r => r.json())
        .then(data => {

            if (data.error) return;

            document.getElementById("detailCodigo").textContent = data.codEnvio;
            

            document.getElementById("cabLaboratorio").textContent = `${data.nomLab}`;
            document.getElementById("cabTransporte").textContent = `${data.nomEmpTrans}`;

            document.getElementById("cabRegistrador").textContent = data.usuarioRegistrador;
            document.getElementById("cabResponsable").textContent = data.usuarioResponsable;
            document.getElementById("cabAutorizado").textContent = data.autorizadoPor;
            document.getElementById("cabCodRefe").textContent = data.codRef;

            // Cambia badge
            const badge = document.getElementById("badgeStatus");
            if (data.estado_cuali_general=== "completado") {
                openDetailCompletado(codEnvio, fecToma, pos);
                badge.textContent = "Completado";
                badge.className = "inline-block mt-2 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium";
            } else {
                openDetail(codEnvio, fecToma, pos);
                badge.textContent = "Pendiente";
                badge.className = "inline-block mt-2 px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium";
            }

        });
}


function toggleFiltros() {
    let box = document.getElementById("filtrosContent");
    let btn = document.getElementById("btnToggleFiltros");

    if (box.classList.contains("hidden")) {
        box.classList.remove("hidden");
        btn.textContent = "➖";
    } else {
        box.classList.add("hidden");
        btn.textContent = "➕";
    }
}



async function guardarResultados() {

    let fechaRegistroLab = document.getElementById('fechaRegistroLab').value.trim();

    if (!fechaRegistroLab) {
        alert("⚠️ Tiene que seleccionar una fecha para guardar primero.");
        return;
    }

    const code = document.getElementById("detailCodigo").textContent;
    const cont = document.getElementById("analisisContainer");

    let datos = [];

    cont.querySelectorAll("select").forEach(sel => {

        let block = sel.closest(".bloque-analisis");

        datos.push({
            id: block.dataset.idResultado || null,
            analisisCodigo: sel.dataset.codigo,
            analisisNombre: sel.dataset.nombre,
            resultado: sel.value,
            observaciones: block.querySelector("textarea").value,
            fechaLabRegistro: fechaRegistroLab
        });
    });

    const res = await fetch("guardarResultAnalisis.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            codigoEnvio: code,
            posicion: currentPosition,
            analisis: datos
        })
    });

    const r = await res.json();

    if (!r.success) {
        alert("❌ Error al guardar: " + r.error);
        return;
    }

    // -------------------------
    // MENSAJE DINÁMICO
    // -------------------------
    let mensajes = [];

    if (r.insertados > 0) mensajes.push(`🆕 ${r.insertados} análisis registrados`);
    if (r.actualizados > 0) mensajes.push(`✏️ ${r.actualizados} análisis actualizados`);
    if (r.estadosActualizados > 0) mensajes.push(`📌 Estados cualitativos actualizados`);
    if (r.cabeceraCompletada) mensajes.push(`✅ Solicitud completada`);

    if (mensajes.length === 0) {
        mensajes.push("ℹ️ No se realizaron cambios en los análisis");
    }

    alert(mensajes.join("\n"));

    // -------------------------
    // 🔑 SUBIR ARCHIVOS SIEMPRE QUE EXISTAN
    // -------------------------
    if (inputPDF.files.length > 0) {
        await guardarPDF(); // 🔥 aquí estaba el problema conceptual
    }

    // -------------------------
    // CERRAR PANEL
    // -------------------------
    closeDetail();

    // -------------------------
    // SOLO REMOVER DEL SIDEBAR SI HUBO INSERT
    // -------------------------
    if (r.insertados > 0) {
        const item = document.getElementById(`item-${code}-${currentPosition}`);
        if (item) item.remove();
    }

    // -------------------------
    // LIMPIAR CONTENEDOR
    // -------------------------
    document.getElementById("analisisContainer").innerHTML = "";

    // -------------------------
    // MENSAJE SI NO QUEDAN PENDIENTES
    // -------------------------
    const list = document.getElementById("pendingOrdersList");
    if (list && list.children.length === 0) {
        list.innerHTML = `
            <div class="text-gray-500 text-sm">
                No hay solicitudes pendientes.
            </div>`;
    }
}



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
        }[s]));
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


function crearBloqueAnalisis(
    codigo,
    nombre,
    resultados,
    esManual = false,
    resultadoSeleccionado = null,
    observacion = null,
    idResultado = null
) {

    const cont = document.getElementById("analisisContainer");

    let block = document.createElement("div");
    block.className =
        "bloque-analisis relative bg-white shadow-sm border rounded-xl p-3";
    block.dataset.idResultado = idResultado;    

    // --- Botón eliminar (manuales) ---
    if (esManual) {
        let removeBtn = document.createElement("button");
        removeBtn.textContent = "x";
        removeBtn.className =
            "absolute top-2 right-2 text-gray-500 hover:text-red-600 px-2 py-1";
        removeBtn.onclick = () => block.remove();
        block.appendChild(removeBtn);
    }

    // --- Título ---
    let title = document.createElement("div");
    title.className = "text-[13px] font-semibold text-gray-700 mb-2";
    title.textContent = `${nombre} (${codigo})`;

    // --- Select ---
    let select = document.createElement("select");
    select.className =
        "w-full px-2 py-2 border border-gray-300 text-sm rounded-md";
    select.name = "resultado_" + codigo;

    // 🔥 CLAVE: datos que el backend necesita
    select.dataset.codigo = codigo;
    select.dataset.nombre = nombre;

    // 👉 SIN RESULTADOS
    if (!resultados || resultados.length === 0) {

        let opt = document.createElement("option");
        opt.value = "NO_TIENE_RESULTADO";
        opt.textContent = "No tiene resultados";
        opt.selected = true;

        select.appendChild(opt);

    } else {

        let optDefault = document.createElement("option");
        optDefault.value = "";
        optDefault.textContent = "Seleccionar resultado";
        select.appendChild(optDefault);

        resultados.forEach(r => {
            let opt = document.createElement("option");
            opt.value = r;
            opt.textContent = r;

            if (resultadoSeleccionado && r === resultadoSeleccionado) {
                opt.selected = true;
            }

            select.appendChild(opt);
        });
    }

    // --- Textarea ---
    let textarea = document.createElement("textarea");
    textarea.className =
        "w-full mt-2 p-2 border rounded-md text-sm h-20 resize-none";
    textarea.placeholder = "Observaciones...";
    textarea.value = observacion ?? "";

    block.appendChild(title);
    block.appendChild(select);
    block.appendChild(textarea);

    cont.appendChild(block);
}





async function guardarPDF() {

    if (inputPDF.files.length === 0) {
        return;
    }

    const codigoEnvio = document.getElementById("detailCodigo").textContent;
    const pos = currentPosition;

    for (let file of inputPDF.files) {

        let formData = new FormData();
        formData.append("pdf", file);
        formData.append("codigoEnvio", codigoEnvio);
        formData.append("posSolicitud", pos);

        let res = await fetch("guardarResultadoAnalisisPDF.php", {
            method: "POST",
            body: formData
        });

        let r = await res.json();

        if (!r.success) {
            alert("❌ Error con " + file.name + ": " + r.error);
            return;
        }
    }

    alert("📁 Todos los archivos fueron subidos correctamente");

    // limpiar input
    inputPDF.value = "";
    renderFiles();
}


const inputPDF = document.getElementById("archivoPdf");
const fileList = document.getElementById("fileList");

// Validaciones
const MAX_SIZE = 10 * 1024 * 1024; // 10MB
const allowedTypes = [
    "application/pdf",
    "application/msword",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "application/vnd.ms-excel",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "text/plain",
    "image/png",
    "image/jpeg"
];

function renderFiles() {
    fileList.innerHTML = "";

    if (inputPDF.files.length === 0) return;

    for (let file of inputPDF.files) {

        const div = document.createElement("div");
        div.className = "flex justify-between items-center p-2 border rounded-md bg-gray-50";

        div.innerHTML = `
            <div>
                <p class="text-sm font-medium">${file.name}</p>
                <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
            </div>
            <button class="text-red-600 font-bold text-xl leading-none"
                onclick="removeFile('${file.name}')">×</button>
        `;

        fileList.appendChild(div);
    }
}

// Quitar archivo individual
function removeFile(name) {
    const dt = new DataTransfer();

    for (let file of inputPDF.files) {
        if (file.name !== name) dt.items.add(file);
    }

    inputPDF.files = dt.files;
    renderFiles();
}

// Al seleccionar archivos
inputPDF.addEventListener("change", () => {
    const dt = new DataTransfer();

    for (let file of inputPDF.files) {

        // validar tipo
        if (!allowedTypes.includes(file.type)) {
            alert(`❌ Archivo no permitido: ${file.name}`);
            continue;
        }

        // validar tamaño
        if (file.size > MAX_SIZE) {
            alert(`❌ ${file.name} pesa ${(file.size / 1024 / 1024).toFixed(2)}MB (máx. 10MB)`);
            continue;
        }

        dt.items.add(file);
    }

    inputPDF.files = dt.files;
    renderFiles();
});


async function cargarArchivosCompletados(codigoEnvio, pos) {

    const res = await fetch(
        `getResultadoArchivos.php?codigoEnvio=${codigoEnvio}&posSolicitud=${pos}`
    );
    const data = await res.json();

    const fileListPrecargados = document.getElementById("fileListPrecargados");
    fileListPrecargados.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
        fileListPrecargados.innerHTML = `
            <p class="text-sm text-gray-500">No hay archivos adjuntos</p>
        `;
        return;
    }

    data.forEach(f => {

        const div = document.createElement("div");
        div.className =
            "flex justify-between items-center gap-3 p-2 border rounded-md bg-gray-50";

        div.innerHTML = `
            <div class="flex-1">
                <p class="text-sm font-medium">${f.nombre}</p>
                <p class="text-xs text-gray-500">
                    ${f.tipo} • ${new Date(f.fecha).toLocaleDateString("es-PE")}
                </p>
            </div>

            <!-- Descargar con validación -->
            <button
                title="Descargar archivo"
                class="text-blue-600 hover:text-blue-800 text-lg"
                onclick="descargarArchivo('${f.ruta}', '${f.nombre}')">
                ⬇️
            </button>
            <button
                title="Reemplazar archivo"
                class="text-orange-600 hover:text-orange-800 text-lg"
                onclick="reemplazarArchivo(${f.id})">
                ♻️
            </button>
        `;

        fileListPrecargados.appendChild(div);
    });
}

async function descargarArchivo(ruta, nombre) {
    try {
        // Validar existencia (HEAD es liviano)
        const res = await fetch(ruta, { method: "HEAD" });

        if (!res.ok) {
            alert("❌ El archivo no existe o fue movido del servidor.");
            return;
        }

        // Forzar descarga solo si existe
        const a = document.createElement("a");
        a.href = ruta;
        a.download = nombre;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

    } catch (err) {
        alert("❌ No se pudo verificar el archivo.");
        console.error(err);
    }
}

function limpiarArchivosAdjuntos() {

    const inputPDF = document.getElementById("archivoPdf");
    const fileList = document.getElementById("fileList");
    const fileListPrecargados = document.getElementById("fileListPrecargados");

    if (inputPDF) {
        const dt = new DataTransfer();
        inputPDF.files = dt.files; // elimina todos los archivos
    }

    if (fileList) {
        fileList.innerHTML = "";
    }
    if (fileListPrecargados) {
        fileListPrecargados.innerHTML = "";
    }
}

function reemplazarArchivo(idArchivo) {

    const input = document.createElement("input");
    input.type = "file";

    input.onchange = async () => {

        if (!input.files.length) return;

        const file = input.files[0];

        // Validaciones (reuse de las tuyas)
        if (!allowedTypes.includes(file.type)) {
            alert("❌ Tipo de archivo no permitido");
            return;
        }

        if (file.size > MAX_SIZE) {
            alert("❌ Archivo supera el tamaño permitido");
            return;
        }

        const codigoEnvio = document.getElementById("detailCodigo").textContent;

        let formData = new FormData();
        formData.append("archivo", file);
        formData.append("idArchivo", idArchivo);
        formData.append("codigoEnvio", codigoEnvio);
        formData.append("posSolicitud", currentPosition);

        const res = await fetch("actualizarResultadoArchivo.php", {
            method: "POST",
            body: formData
        });

        const r = await res.json();

        if (r.success) {
            alert("♻️ Archivo reemplazado correctamente");
            cargarArchivosCompletados(codigoEnvio, currentPosition);
        } else {
            alert("❌ Error: " + r.error);
        }
    };

    input.click();
}

