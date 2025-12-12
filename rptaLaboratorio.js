let currentPosition = null;
async function openDetail(code, fechaToma, posicion) {

    resaltarItemSidebar(code, posicion);
    cargarCabecera(code);

    document.getElementById('emptyStatePanel').classList.add('hidden');
    document.getElementById('responseDetailPanel').classList.remove('hidden');
    document.getElementById('detailCodigo').textContent = code;
    currentPosition = posicion;

    document.getElementById('detailFecha').textContent = fechaToma;

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

function cargarCabecera(codEnvio) {
    fetch(`get_solicitud_cabecera.php?codEnvio=${codEnvio}`)
        .then(r => r.json())
        .then(data => {

            if (data.error) return;

            document.getElementById("detailCodigo").textContent = data.codEnvio;
            

            document.getElementById("cabLaboratorio").textContent = `${data.nomLab}`;
            document.getElementById("cabTransporte").textContent = `${data.nomEmpTrans}`;

            document.getElementById("cabRegistrador").textContent = data.usuarioRegistrador;
            document.getElementById("cabResponsable").textContent = data.usuarioResponsable;
            document.getElementById("cabAutorizado").textContent = data.autorizadoPor;

            // Cambia badge
            const badge = document.getElementById("badgeStatus");
            if (data.estado === "completado") {
                badge.textContent = "Completado";
                badge.className = "inline-block mt-2 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium";
            } else {
                badge.textContent = "Pendiente";
                badge.className = "inline-block mt-2 px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium";
            }

        });
}


async function guardarResultados() {

    let fechaRegistroLab = document.getElementById('fechaRegistroLab').value.trim();

    if(!fechaRegistroLab){
        alert("⚠️Tiene que seleccionar una fecha para guardar primero.");
        return;
    }

    const code = document.getElementById("detailCodigo").textContent;
    const cont = document.getElementById("analisisContainer");

    let datos = [];

    cont.querySelectorAll("select").forEach(sel => {

        let codigoAnalisis = sel.dataset.codigo;
        let nombre = sel.dataset.nombre;
        let resultado = sel.value;

        //  OBTENER EL COMMENT DEL MISMO BLOQUE DEL SELECT
        let block = sel.closest(".bloque-analisis");
        let observaciones = block.querySelector("textarea").value;

        datos.push({
            analisisCodigo: codigoAnalisis,
            analisisNombre: nombre,
            resultado: resultado,
            observaciones: observaciones,
            fechaLabRegistro: fechaRegistroLab
        });
    });

    let res = await fetch("guardarResultAnalisis.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            codigoEnvio: code,
            posicion: currentPosition,
            analisis: datos
        })
    });

    let r = await res.json();

    if (r.success) {
        //  1. Cerrar panel detalle
        closeDetail();

        //  2. Remover la tarjeta del sidebar
        const item = document.getElementById(`item-${code}-${currentPosition}`);
        if (item) item.remove();

        // 3. Limpiar contenedor
        document.getElementById("analisisContainer").innerHTML = "";

        // 4. Si ya no quedan pendientes, mostrar mensaje
        const list = document.getElementById("pendingOrdersList");
        if (list.children.length === 0) {
            list.innerHTML = `<div class="text-gray-500 text-sm">No hay solicitudes pendientes.</div>`;
        }
        alert("✔️Resultados guardados correctamente");

        // SOLO SUBE PDF SI HAY ARCHIVOS
        if (inputPDF.files.length > 0) {
            await guardarPDF();
        }
    } else {
        alert("Error al guardar: " + r.error);
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

    // Default option
    select.innerHTML = `<option value="" selected disabled>Seleccionar resultado</option>`;

    // 👉 Si NO hay resultados, agregar "Sin resultados"
    if (!resultados || resultados.length === 0) {
        let opt = document.createElement("option");
        opt.value = "";
        opt.textContent = "Sin resultados";
        opt.disabled = true;
        select.appendChild(opt);

        // Opcional: deshabilitar el select para que no puedan elegir nada
        // select.disabled = true;
    } else {
        resultados.forEach(r => {
            let opt = document.createElement("option");
            opt.value = r;
            opt.textContent = r;
            select.appendChild(opt);
        });
    }

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
