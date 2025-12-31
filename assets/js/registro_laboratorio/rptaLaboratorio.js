//###################################################
//BLOQUE PARA CARGAR SIDEBAR CON LAS SOLICITUDES
//####################################################

let currentPage = 1;
let limit = 10;
let debounceTimer = null;

// VARIABLES GLOBALES PARA CARGAR DATOS CUANTITATIVOS
let codEnvioCuantiAux = null;
let fecTomaCuantiAux = null;
let codRefCuantiAux = null;
let estadoCuantiAux = null;
let nomMuestrasAux = null;

/** carga la lista (pagina). page opcional */
function loadSidebar(page = 1) {
    currentPage = page;

    // filtros
    const fechaInicio = encodeURIComponent(document.getElementById("filtroFechaInicio").value || "");
    const fechaFin = encodeURIComponent(document.getElementById("filtroFechaFin").value || "");
    const estado = encodeURIComponent(document.getElementById("filtroEstado").value || "pendiente");
    const filtroLab = encodeURIComponent(document.getElementById("filtroLab").value || "");
    const q = encodeURIComponent(document.getElementById("searchInput").value.trim() || "");

    const url = `get_solicitudes.php?page=${page}&limit=${limit}&fechaInicio=${fechaInicio}&fechaFin=${fechaFin}&estado=${estado}&lab=${filtroLab}&q=${q}`;

    fetch(url)
        .then(r => r.json())
        .then(res => {
            const list = document.getElementById("pendingOrdersList");
            list.innerHTML = "";

            res.data.forEach(row => {
                const btn = document.createElement("button");
                // id único por codEnvio + pos
                btn.id = `item-${row.codEnvio}-${row.posSolicitud}`;
                btn.className = "w-full text-left p-3 rounded-md hover:bg-gray-50 transition border border-gray-200 hover:border-gray-100";

                btn.onclick = () => {
                    // resaltar visualmente


                    cargarSolicitud(row.codEnvio, row.fecToma, row.codRef, row.estado_cuanti, row.nomMuestras, row.posSolicitud, row.analisis, row.analisisCodigos, row.analisisEnfermedades);
                    resaltarItemSidebar(row.codEnvio, row.posSolicitud);
                    cargarCabecera(row.codEnvio, row.fecToma, row.posSolicitud, row.codRef, row.estado_cuanti, row.nomMuestras);
                };

                btn.innerHTML = `
                                <div class="flex justify-between items-start gap-2">
                                <!-- IZQUIERDA -->
                                <div>
                                    <div class="font-semibold text-sm text-gray-800">
                                        ${escapeHtml(row.codEnvio)}
                                    </div>

                                    <!-- SUB ESTADOS -->
                                    ${getSubEstadosBadge(row.estado_cuali, row.estado_cuanti, row.codEnvio, row.posSolicitud)}
                                </div>

                                <!-- DERECHA -->
                                <div>
                                    ${getEstadoBadge(row.estado_general)}
                                </div>
                            </div>

                            <div class="text-xs text-gray-500 mt-1">
                                ${formatDate(row.fecToma)}
                            </div>

                            <div class="text-xs text-gray-600 mt-0.5">
                                Ref: <span class="font-medium">${escapeHtml(row.codRef)}</span>
                                • Solicitud: <span class="font-medium">${escapeHtml(row.posSolicitud)}</span>
                            </div>
                            `;


                list.appendChild(btn);
            });

            // PAGINACIÓN
            renderPagination(res.page, res.total, res.limit);
        })
        .catch(err => {
            console.error("Error cargando solicitudes:", err);
        });
}

function getEstadoBadge(estado) {
    if (estado === "completado") {
        return `
            <span class="px-2 py-0.5 text-[11px] rounded-full bg-green-100 text-green-700 font-medium">
                Completado
            </span>`;
    }

    return `
        <span class="px-2 py-0.5 text-[11px] rounded-full bg-yellow-100 text-yellow-700 font-medium">
            Pendiente
        </span>`;
}

function getSubEstadosBadge(cuali, cuanti, codEnvio, posSolicitud) {
    const badge = (label, estado, tipo) => {
        const isCompletado = estado === "completado";

        if (isCompletado) {
            return `
                <span onclick="event.stopPropagation(); abrirModalPendiente('${codEnvio}', '${posSolicitud}', '${tipo}')" 
                      class="px-1.5 py-0.5 text-[10px] rounded-full
                             bg-green-50 text-green-700 border border-green-200
                             cursor-pointer hover:bg-green-100 hover:border-green-300 
                             transition-colors duration-150 inline-block">
                    ${label} ✔
                </span>`;
        }

        return `
            <span onclick="event.stopPropagation(); abrirModalCompletar('${codEnvio}', '${posSolicitud}', '${tipo}')" 
                  class="px-1.5 py-0.5 text-[10px] rounded-full
                         bg-yellow-50 text-yellow-700 border border-yellow-200
                         cursor-pointer hover:bg-yellow-100 hover:border-yellow-300 
                         transition-colors duration-150 inline-block">
                ${label} ⏳
            </span>`;
    };


    return `
        <div class="flex gap-1 mt-1">
            ${badge("Cuali", cuali, 'cualitativo')}
            ${badge("Cuanti", cuanti, 'cuantitativo')}
        </div>
    `;
}

// Abrir el modal para completar
let codEnvioCurrent = null;
let posSolicitudCurrent = null;
let tipoCurrent = null;

function abrirModalCompletar(codEnvio, posSolicitud, tipo) {

    codEnvioCurrent = codEnvio;
    posSolicitudCurrent = posSolicitud;
    tipoCurrent = tipo;

    document.getElementById('comentarioCompletar').value = ''; // Limpiar comentario anterior
    document.getElementById('modalCompletarResultado').classList.remove('hidden');
    document.getElementById('lblModalCompletar').textContent = `¿Desea completar este resultado ${tipo}?`;
}

// Confirmar acción 
function confirmarCompletado() {
    const comentario = document.getElementById('comentarioCompletar').value.trim();

    // ✅ Validar que tenemos los datos necesarios
    if (!codEnvioCurrent || !posSolicitudCurrent || !tipoCurrent) {
        alert('Error: Datos de solicitud no válidos');
        cerrarModalCompletar();
        return;
    }

    console.log('📤 Enviando cambio de estado:', {
        codEnvio: codEnvioCurrent,
        posSolicitud: posSolicitudCurrent,
        tipo: tipoCurrent,
        nuevoEstado: 'completado'
    });

    // Guardar referencia local porque las variables globales pueden cambiar
    const codEnvioAActualizar = codEnvioCurrent;
    const posSolicitudAActualizar = posSolicitudCurrent;
    const tipoAActualizar = tipoCurrent.toLowerCase();

    /* CODIGO ANTERIOR
    fetch('cambiar_estado_solicitud.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            codEnvio: codEnvioCurrent,
            posSolicitud: posSolicitudCurrent,
            tipo: tipoCurrent.toLowerCase(),
            nuevoEstado: 'completado',
            comentario: comentario
        })
    })
    */

    fetch('cambiar_estado_solicitud.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            codEnvio: codEnvioAActualizar,
            posSolicitud: posSolicitudAActualizar,
            tipo: tipoAActualizar,
            nuevoEstado: 'completado',
            comentario: comentario
        })
    })
        .then(r => r.json())
        .then(data => {
            console.log('📥 Respuesta del servidor:', data);

            if (data.success) {
                /* CODIGO ANTERIOR
                alert('Resultado marcado como completado');
                */
                alert(`Resultado marcado como completado (${data.affected_rows} registros actualizados)`);

                // ✅ Verificar si el panel actual corresponde a la solicitud que se actualizó
                const esMismaSolicitud = (window.codigoEnvioActual === codEnvioAActualizar &&
                    String(window.posSolicitudActual) === String(posSolicitudAActualizar));

                /* CODIGO ANTERIOR (actualizaba siempre sin verificar)
                if (tipoCurrent.toLowerCase() === 'cuantitativo') {
                    const badgeCuanti = document.getElementById('badgeStatusCuanti');
                    if (badgeCuanti) {
                        badgeCuanti.textContent = 'COMPLETADO';
                        badgeCuanti.className = 'inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide bg-green-100 text-green-800 ring-2 ring-green-300';
                    }
                    window.estadoActualSolicitud = 'completado';
                    
                    const btnGuardar = document.querySelector('#formAnalisis button[type="submit"]');
                    if (btnGuardar) {
                        btnGuardar.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Actualizar Resultados';
                    }
                } else if (tipoCurrent.toLowerCase() === 'cualitativo') {
                    const badgeCuali = document.getElementById('badgeStatusCuali');
                    if (badgeCuali) {
                        badgeCuali.textContent = 'Completado';
                        badgeCuali.className = 'inline-block mt-2 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium';
                    }
                }
                
                loadSidebar(currentPage || 1);
                
                setTimeout(() => {
                    resaltarItemSidebar(codEnvioCurrent, posSolicitudCurrent);
                }, 300);
                */

                // ✅ Actualizar el badge del panel SOLO si es la misma solicitud
                if (esMismaSolicitud) {
                    if (tipoAActualizar === 'cuantitativo') {
                        const badgeCuanti = document.getElementById('badgeStatusCuanti');
                        if (badgeCuanti) {
                            badgeCuanti.textContent = 'COMPLETADO';
                            badgeCuanti.className = 'inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide bg-green-100 text-green-800 ring-2 ring-green-300';
                        }
                        window.estadoActualSolicitud = 'completado';

                        const btnGuardar = document.querySelector('#formAnalisis button[type="submit"]');
                        if (btnGuardar) {
                            btnGuardar.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Actualizar Resultados';
                        }
                    } else if (tipoAActualizar === 'cualitativo') {
                        const badgeCuali = document.getElementById('badgeStatusCuali');
                        if (badgeCuali) {
                            badgeCuali.textContent = 'Completado';
                            badgeCuali.className = 'inline-block mt-2 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium';
                        }
                    }
                }

                // ✅ Recargar sidebar para reflejar el cambio
                loadSidebar(currentPage || 1);

                // ✅ Esperar y resaltar el item correcto (el que está seleccionado en el panel)
                setTimeout(() => {
                    resaltarItemSidebar(window.codigoEnvioActual, window.posSolicitudActual);
                }, 300);

            } else {
                alert('No se pudo cambiar el estado: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error de conexión');
        });

    cerrarModalCompletar();
}

// Cerrar el modal
function cerrarModalCompletar() {
    document.getElementById('modalCompletarResultado').classList.add('hidden');
}

// Cerrar al hacer clic fuera del modal
document.getElementById('modalCompletarResultado').addEventListener('click', function (e) {
    if (e.target === this) {
        cerrarModalCompletar();
    }
});


// Abrir el modal para poner pendiente
function abrirModalPendiente(codEnvio, posSolicitud, tipo) {

    codEnvioCurrent = codEnvio;
    posSolicitudCurrent = posSolicitud;
    tipoCurrent = tipo;

    document.getElementById('comentarioPendiente').value = ''; // Limpiar comentario anterior
    document.getElementById('modalResultadoPendiente').classList.remove('hidden');
    document.getElementById('lblModalPendiente').textContent = `¿Desea dejar como pendiente este resultado ${tipo}?`;
}

// Cerrar el modal
function cerrarModalPendiente() {
    document.getElementById('modalResultadoPendiente').classList.add('hidden');
}

// Confirmar acción (aquí pones tu lógica real)
function confirmarPendiente() {
    const comentario = document.getElementById('comentarioPendiente').value.trim();

    // Guardar referencia local porque las variables globales pueden cambiar
    const codEnvioAActualizar = codEnvioCurrent;
    const posSolicitudAActualizar = posSolicitudCurrent;
    const tipoAActualizar = tipoCurrent.toLowerCase();

    /* CODIGO ANTERIOR
    fetch('cambiar_estado_solicitud.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            codEnvio: codEnvioCurrent,
            posSolicitud: posSolicitudCurrent,
            tipo: tipoCurrent.toLowerCase(),
            nuevoEstado: 'pendiente',
            comentario: comentario
        })
    })
    */

    fetch('cambiar_estado_solicitud.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            codEnvio: codEnvioAActualizar,
            posSolicitud: posSolicitudAActualizar,
            tipo: tipoAActualizar,
            nuevoEstado: 'pendiente',
            comentario: comentario
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Resultado marcado como pendiente');

                // ✅ Verificar si el panel actual corresponde a la solicitud que se actualizó
                const esMismaSolicitud = (window.codigoEnvioActual === codEnvioAActualizar &&
                    String(window.posSolicitudActual) === String(posSolicitudAActualizar));

                /* CODIGO ANTERIOR (actualizaba siempre sin verificar)
                if (tipoCurrent.toLowerCase() === 'cuantitativo') {
                    const badgeCuanti = document.getElementById('badgeStatusCuanti');
                    if (badgeCuanti) {
                        badgeCuanti.textContent = 'PENDIENTE';
                        badgeCuanti.className = 'inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide bg-yellow-100 text-yellow-800 ring-2 ring-yellow-300';
                    }
                    window.estadoActualSolicitud = 'pendiente';
                    
                    const btnGuardar = document.querySelector('#formAnalisis button[type="submit"]');
                    if (btnGuardar) {
                        btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar Resultados';
                    }
                } else if (tipoCurrent.toLowerCase() === 'cualitativo') {
                    const badgeCuali = document.getElementById('badgeStatusCuali');
                    if (badgeCuali) {
                        badgeCuali.textContent = 'Pendiente';
                        badgeCuali.className = 'inline-block mt-2 px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium';
                    }
                }
                
                loadSidebar(currentPage || 1);
                
                setTimeout(() => {
                    resaltarItemSidebar(codEnvioCurrent, posSolicitudCurrent);
                }, 300);
                */

                // ✅ Actualizar el badge del panel SOLO si es la misma solicitud
                if (esMismaSolicitud) {
                    if (tipoAActualizar === 'cuantitativo') {
                        const badgeCuanti = document.getElementById('badgeStatusCuanti');
                        if (badgeCuanti) {
                            badgeCuanti.textContent = 'PENDIENTE';
                            badgeCuanti.className = 'inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide bg-yellow-100 text-yellow-800 ring-2 ring-yellow-300';
                        }
                        window.estadoActualSolicitud = 'pendiente';

                        const btnGuardar = document.querySelector('#formAnalisis button[type="submit"]');
                        if (btnGuardar) {
                            btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar Resultados';
                        }
                    } else if (tipoAActualizar === 'cualitativo') {
                        const badgeCuali = document.getElementById('badgeStatusCuali');
                        if (badgeCuali) {
                            badgeCuali.textContent = 'Pendiente';
                            badgeCuali.className = 'inline-block mt-2 px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium';
                        }
                    }
                }

                // ✅ Recargar sidebar para reflejar el cambio
                loadSidebar(currentPage || 1);

                // ✅ Esperar y resaltar el item correcto (el que está seleccionado en el panel)
                setTimeout(() => {
                    resaltarItemSidebar(window.codigoEnvioActual, window.posSolicitudActual);
                }, 300);

            } else {
                alert('No se pudo cambiar el estado: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión');
        });

    cerrarModalPendiente();
}

// Cerrar al hacer clic fuera del modal
document.getElementById('modalResultadoPendiente').addEventListener('click', function (e) {
    if (e.target === this) {
        cerrarModalPendiente();
    }
});
/** render simple paginación */
function renderPagination(page, total, limit) {
    const totalPages = Math.max(1, Math.ceil(total / limit));
    const container = document.getElementById("paginationControls");

    container.innerHTML = `
        <button onclick="if(${page} > 1) loadSidebar(${page - 1});"
            class="px-3 py-1 rounded ${page <= 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-gray-200'}">
            ← Anterior
        </button>

        <span>Página ${page} de ${totalPages}</span>

        <button onclick="if(${page} < ${totalPages}) loadSidebar(${page + 1});"
            class="px-3 py-1 rounded ${page >= totalPages ? 'opacity-30 cursor-not-allowed' : 'hover:bg-gray-200'}">
            Siguiente →
        </button>
    `;
}

/** aplica filtros (llama loadSidebar a página 1) */
function aplicarFiltros() {
    closeDetail();
    loadSidebar(1);
}

/** debounce wrapper para el searchInput */
function debouncedSearch() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadSidebar(1);
    }, 300);
}

/** formato fecha para mostrar */
function formatDate(str) {
    if (!str) return "-";
    const d = new Date(str);
    return d.toLocaleDateString("es-PE");
}

/** escapar texto simple para seguridad en innerHTML */
function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

/** conectar input search al debouncedSearch */
document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("searchInput");
    if (input) {
        input.addEventListener("input", debouncedSearch);
    }

    // cargar primera página
    loadSidebar(1);
});


let currentPosition = null;

function switchTab(tab) {

    const tabs = [
        {
            btn: "tabAnalisis",
            content: "tabContentAnalisis",
            key: "analisis"
        },
        {
            btn: "tabSegundo",
            content: "tabContentSegundo",
            key: "segundo",
            /*onActivate: () => cargarSolicitud(
                codEnvioCuantiAux,
                fecTomaCuantiAux,
                codRefCuantiAux,
                estadoCuantiAux,
                nomMuestrasAux, currentPosition, analisisStr = '', analisisCodigosStr = '', analisisEnfermedadesStr = ''
            )*/
        }
    ];

    tabs.forEach(t => {
        // ocultar contenido
        document.getElementById(t.content).classList.add("hidden");

        // estado inactivo
        const btn = document.getElementById(t.btn);
        btn.classList.remove("border-blue-600", "text-blue-600");
        btn.classList.add("border-transparent", "text-gray-500", "hover:text-gray-700");
    });

    // activar tab seleccionado
    const active = tabs.find(t => t.key === tab);

    document.getElementById(active.content).classList.remove("hidden");

    const activeBtn = document.getElementById(active.btn);
    activeBtn.classList.remove("border-transparent", "text-gray-500", "hover:text-gray-700");
    activeBtn.classList.add("border-blue-600", "text-blue-600");

    // 🔥 EJECUTAR ACCIÓN EXTRA SI EXISTE
    if (typeof active.onActivate === "function") {
        active.onActivate();
    }
}

async function openDetailPrincipal(code, fechaToma, posicion) {
    // Configuración común para ambos modos
    document.getElementById('emptyStatePanel').classList.add('hidden');
    document.getElementById('responseDetailPanel').classList.remove('hidden');
    document.getElementById('detailCodigo').textContent = code;
    document.getElementById('detailFecha').textContent = fechaToma;
    currentPosition = posicion;

    const cont = document.getElementById("analisisContainer");
    cont.innerHTML = "<div class='text-gray-500 text-sm'>Verificando estado de resultados...</div>";

    try {
        // 1. Consultar si ya hay resultados guardados
        const checkRes = await fetch(`checkResultadosCualisGuardados.php?codigoEnvio=${code}&posicion=${posicion}`);
        const checkData = await checkRes.json();

        if (checkData.tieneResultados) {
            // === MODO EDICIÓN: Ya hay resultados guardados ===
            await openDetailCompletado(code, fechaToma, posicion);
        } else {
            // === MODO NUEVO: No hay resultados aún ===
            await openDetail(code, fechaToma, posicion);
        }
    } catch (error) {

        cont.innerHTML = "<div class='text-red-500 text-sm'>Error al cargar el detalle. Intente nuevamente.</div>";
    }
}

async function openDetail(code, fechaToma, posicion) {


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

async function openDetailCompletado(code, fechaToma, posicion) {

    //resaltarItemSidebar(code, posicion);
    currentPosition = posicion;

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

function closeDetail() {
    // Ocultar panel de detalle / mostrar empty
    document.getElementById('responseDetailPanel').classList.add('hidden');
    document.getElementById('emptyStatePanel').classList.remove('hidden');
    document.getElementById('fechaRegistroLab').value = '';

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


function cargarCabecera(codEnvio, fecToma, pos, codRef, estado_cuanti, nomMuestras) {

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
            document.getElementById("cabPosSolicitud").textContent = "Solicitud N°: " + data.posSolicitud;

            //variables globales para cuantitativos
            codEnvioCuantiAux = codEnvio;
            fecTomaCuantiAux = fecToma;
            codRefCuantiAux = codRef;
            estadoCuantiAux = estado_cuanti;
            nomMuestrasAux = nomMuestras;

            const datosRef = decodificarCodRef(codRef);

            document.getElementById('codRef_granja_display').value = datosRef.granja;
            document.getElementById('codRef_granja').value = datosRef.granja;

            document.getElementById('codRef_campana_display').value = datosRef.campana;
            document.getElementById('codRef_campana').value = datosRef.campana;

            document.getElementById('codRef_galpon_display').value = datosRef.galpon;
            document.getElementById('codRef_galpon').value = datosRef.galpon;

            document.getElementById('edadAves_display').value = datosRef.edad;

            const edadField = document.getElementById('edadAves_display');
            if (edadField) edadField.value = datosRef.edad;

            // ✅ Guardar codRef completo en campo hidden
            const codRefCompletoField = document.getElementById('codRef_completo');
            if (codRefCompletoField) codRefCompletoField.value = codRef;

            /* CODIGO ANTERIOR
            // Cambia badge
            const badge = document.getElementById("badgeStatusCuali");
            if (data.estado_cuali_general === "completado") {
                //openDetailCompletado(codEnvio, fecToma, pos);
                badge.textContent = "Completado";
                badge.className = "inline-block mt-2 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium";
            } else {
                //openDetail(codEnvio, fecToma, pos);
                badge.textContent = "Pendiente";
                badge.className = "inline-block mt-2 px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium";
            }
            openDetailPrincipal(codEnvio, fecToma, pos);
            */

            // Cambia badge cuali
            const badge = document.getElementById("badgeStatusCuali");
            if (data.estado_cuali_general === "completado") {
                //openDetailCompletado(codEnvio, fecToma, pos);
                badge.textContent = "Completado";
                badge.className = "inline-block mt-2 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium";
            } else {
                //openDetail(codEnvio, fecToma, pos);
                badge.textContent = "Pendiente";
                badge.className = "inline-block mt-2 px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium";
            }

            // Cambia badge cuanti
            const badgeCuanti = document.getElementById("badgeStatusCuanti");
            if (data.estado_cuanti_general === "completado") {
                badgeCuanti.textContent = "COMPLETADO";
                badgeCuanti.className = "inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide bg-green-100 text-green-800 ring-2 ring-green-300";
            } else {
                badgeCuanti.textContent = "PENDIENTE";
                badgeCuanti.className = "inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide bg-yellow-100 text-yellow-800 ring-2 ring-yellow-300";
            }

            openDetailPrincipal(codEnvio, fecToma, pos);
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

async function guardarResultados(estadoCuali) {

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
            analisis: datos,
            estadoCuali: estadoCuali
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

    if (r.insertados > 0) mensajes.push(`🆕 ${r.insertados} análisis registrados, Cod de envio: ${code}, solicitud: ${currentPosition}`);
    if (r.actualizados > 0) mensajes.push(`✏️ ${r.actualizados} análisis actualizados, Cod de envio: ${code}, solicitud: ${currentPosition}`);
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
    //closeDetail();

    // -------------------------
    // SOLO ACTUALIZAR DEL SIDEBAR SI HUBO INSERT
    // -------------------------
    if (r.insertados > 0 || r.actualizados > 0) {
        loadSidebar(1);
    }

    // -------------------------
    // actualizar estado y abrir resultado cuali completado
    // -------------------------
    openDetailCompletado(code, fechaRegistroLab, currentPosition);

    resaltarItemSidebar(code, currentPosition);
    // === CAMBIAR EL BADGE A "COMPLETADO" DESPUÉS DE GUARDAR ===
    const badge = document.getElementById('badgeStatusCuali');

    if (badge && (r.insertados > 0 || r.actualizados > 0)) {

        const estado = (estadoCuali || '').trim().toUpperCase();

        // Resetear clases
        badge.classList.remove(
            'bg-yellow-100', 'text-yellow-700',
            'bg-green-100', 'text-green-700'
        );

        if (estado === 'PENDIENTE') {
            badge.textContent = 'Pendiente';
            badge.classList.add('bg-yellow-100', 'text-yellow-700');

        } else if (estado === 'COMPLETADO') {
            badge.textContent = 'Completado';
            badge.classList.add('bg-green-100', 'text-green-700');

        } else {
            // fallback por si viene algo raro
            badge.textContent = estadoCuali;
            badge.classList.add('bg-gray-100', 'text-gray-700');
        }
    }


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
    cerrarModalConfirmacion();
}

//modal de confirmacion para completar

function abrirModalConfirmacion() {
    document.getElementById('modalConfirmacion').classList.remove('hidden');
}

// Cerrar el modal
function cerrarModalConfirmacion() {
    document.getElementById('modalConfirmacion').classList.add('hidden');
}


// Cerrar al hacer clic fuera del modal
document.getElementById('modalConfirmacion').addEventListener('click', function (e) {
    if (e.target === this) {
        cerrarModalConfirmacion();
    }
});

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

//DESACTIVACION DE PANEL PARA RESULTADOS CUALITATIVOS
function confirmarCambioCuali(checkbox) {

    if (!checkbox.checked) {

        const confirmar = confirm(
            "⚠️ ¿Desea desactivar los resultados cualitativos?"
        );

        // ❌ Si cancela → volver a ON
        if (!confirmar) {
            checkbox.checked = true;
            return;
        }
        desactivarResultadosCuali();
        return;
    }
    if (checkbox.checked) {
        const confirmar = confirm(
            "⚠️ ¿Desea activar los resultados cualitativos?"
        );

        // ❌ Si cancela → volver a OFF
        if (!confirmar) {
            checkbox.checked = false;
            return;
        }
        activarResultadosCuali();
        return;
    }
}

function desactivarResultadosCuali() {

    const bloque = document.getElementById("bloqueCuali");

    // 🔒 efecto visual
    bloque.classList.add(
        "opacity-50",
        "pointer-events-none",
        "bg-gray-100",
        "rounded-xl",
        "p-4"
    );

    // 🚫 deshabilitar inputs reales
    bloque.querySelectorAll("input, select, textarea, button").forEach(el => {
        el.disabled = true;
    });

    console.log("❌ Cuali desactivado");
}

function activarResultadosCuali() {

    const bloque = document.getElementById("bloqueCuali");

    // 🔓 quitar efecto visual
    bloque.classList.remove(
        "opacity-50",
        "pointer-events-none",
        "bg-gray-100",
        "rounded-xl",
        "p-4"
    );

    // ✅ habilitar inputs
    bloque.querySelectorAll("input, select, textarea, button").forEach(el => {
        el.disabled = false;
    });

    console.log("✅ Cuali activado");
}


//DESACTIVACION DE PANEL PARA RESULTADOS CUANTITATIVOS
function confirmarCambioCuanti(checkbox) {

    if (!checkbox.checked) {

        const confirmar = confirm(
            "⚠️ ¿Desea desactivar los resultados cuantitativos?"
        );

        // ❌ Si cancela → volver a ON
        if (!confirmar) {
            checkbox.checked = true;
            return;
        }
        desactivarResultadosCuanti();
        return;
    }
    if (checkbox.checked) {
        const confirmar = confirm(
            "⚠️ ¿Desea activar los resultados cuantitativos?"
        );

        // ❌ Si cancela → volver a OFF
        if (!confirmar) {
            checkbox.checked = false;
            return;
        }
        activarResultadosCuanti();
        return;
    }
}

function desactivarResultadosCuanti() {

    const bloque = document.getElementById("bloqueCuanti");

    // efecto visual
    bloque.classList.add(
        "opacity-50",
        "pointer-events-none",
        "bg-gray-100",
        "rounded-xl",
        "p-4"
    );

    //  deshabilitar inputs
    bloque.querySelectorAll("input, select, textarea, button").forEach(el => {
        el.disabled = true;
    });
}

function activarResultadosCuanti() {

    const bloque = document.getElementById("bloqueCuanti");

    // 🔓 quitar efecto visual
    bloque.classList.remove(
        "opacity-50",
        "pointer-events-none",
        "bg-gray-100",
        "rounded-xl",
        "p-4"
    );

    // ✅ habilitar inputs
    bloque.querySelectorAll("input, select, textarea, button").forEach(el => {
        el.disabled = false;
    });

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
        "bloque-analisis relative bg-blue-50 border border-blue-200 shadow-sm rounded-xl p-3";
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
        "w-full px-2 py-2 border border-gray-300 text-sm rounded-md bg-white focus:ring-2 focus:ring-blue-300 focus:outline-none";
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
        "w-full mt-2 p-2 border border-gray-300 rounded-md text-sm h-20 bg-white focus:ring-2 focus:ring-blue-300 focus:outline-none";
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

    let tipo = "cualitativo";

    const res = await fetch(
        `getResultadoArchivos.php?codigoEnvio=${codigoEnvio}&posSolicitud=${pos}&tipo=${tipo.trim()}`
    );
    const data = await res.json();

    const fileListPrecargados = document.getElementById("fileListPrecargados");
    fileListPrecargados.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
        fileListPrecargados.innerHTML = `
            <p class="text-sm text-gray-500 italic">No hay archivos adjuntos</p>
        `;
        return;
    }

    data.forEach(f => {
        const extension = (f.nombre.split('.').pop() || '').toLowerCase();
        const esPdf = extension === 'pdf';

        const div = document.createElement("div");
        div.className = "flex justify-between items-center gap-4 p-3 border rounded-lg bg-gray-50 hover:bg-gray-100 transition";

        div.innerHTML = `
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">${escapeHtml(f.nombre)}</p>
                <p class="text-xs text-gray-500 mt-1">
                    ${f.tipo} • ${f.fecha ? new Date(f.fecha).toLocaleDateString("es-PE") : 'Sin fecha'}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <!-- CODIGO ANTERIOR: botones sin type="button" causaban submit del formulario -->
                <!-- Botón Previsualizar (solo PDF) -->
                ${esPdf ? `
                    <button
                        type="button"
                        title="Previsualizar PDF"
                        onclick="abrirModalPdf('${f.ruta}', '${escapeHtml(f.nombre)}')"
                        class="text-blue-600 hover:text-blue-800 text-xl">
                        👁️
                    </button>
                ` : ''}

                <!-- Botón Descargar (todos) -->
                <button
                    type="button"
                    title="Descargar archivo"
                    onclick="descargarArchivo('${f.ruta}', '${escapeHtml(f.nombre)}')"
                    class="text-green-600 hover:text-green-800 text-xl">
                    ⬇️
                </button>

                <!-- Botón Reemplazar -->
                <button
                    type="button"
                    title="Reemplazar archivo"
                    onclick="reemplazarArchivo(${f.id})"
                    class="text-orange-600 hover:text-orange-800 text-xl">
                    ♻️
                </button>

                 <!-- ELIMINAR -->
                <button type="button"
                        title="Eliminar archivo" 
                        onclick="eliminarArchivo(${f.id}, '${escapeHtml(f.nombre)}')"
                        class="text-red-600 hover:text-red-800 text-xl">
                    🗑️
                </button>
            </div>
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

    /* CODIGO ANTERIOR
    // Solo limpiaba cualitativos, no cuantitativos
    */

    // Limpiar también cuantitativos
    limpiarArchivosCuanti();
}

// ============================================
// FUNCIONES PARA ARCHIVOS CUANTITATIVOS
// ============================================

function limpiarArchivosCuanti() {
    const inputPDFCuanti = document.getElementById("archivoPdfCuanti");
    const fileListCuanti = document.getElementById("fileListCuanti");
    const fileListPrecargadosCuanti = document.getElementById("fileListPrecargadosCuanti");

    if (inputPDFCuanti) {
        const dt = new DataTransfer();
        inputPDFCuanti.files = dt.files;
    }

    if (fileListCuanti) {
        fileListCuanti.innerHTML = "";
    }
    if (fileListPrecargadosCuanti) {
        fileListPrecargadosCuanti.innerHTML = "";
    }
}

async function cargarArchivosCompletadosCuanti(codigoEnvio, pos) {
    let tipo = "cuantitativo";

    const res = await fetch(
        `getResultadoArchivos.php?codigoEnvio=${codigoEnvio}&posSolicitud=${pos}&tipo=${tipo.trim()}`
    );
    const data = await res.json();

    const fileListPrecargadosCuanti = document.getElementById("fileListPrecargadosCuanti");
    if (!fileListPrecargadosCuanti) return;

    fileListPrecargadosCuanti.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
        fileListPrecargadosCuanti.innerHTML = `
            <p class="text-sm text-gray-500 italic">No hay archivos adjuntos</p>
        `;
        return;
    }

    data.forEach(f => {
        const extension = (f.nombre.split('.').pop() || '').toLowerCase();
        const esPdf = extension === 'pdf';

        const div = document.createElement("div");
        div.className = "flex justify-between items-center gap-4 p-3 border rounded-lg bg-gray-50 hover:bg-gray-100 transition";

        div.innerHTML = `
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">${escapeHtml(f.nombre)}</p>
                <p class="text-xs text-gray-500 mt-1">
                    ${f.tipo} • ${f.fecha ? f.fecha : 'Sin fecha'}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <!-- CODIGO ANTERIOR: botones sin type="button" causaban submit del formulario -->
                <!-- Botón Previsualizar (solo PDF) -->
                ${esPdf ? `
                    <button
                        type="button"
                        title="Previsualizar PDF"
                        onclick="abrirModalPdf('${f.ruta}', '${escapeHtml(f.nombre)}')"
                        class="text-blue-600 hover:text-blue-800 text-xl">
                        👁️
                    </button>
                ` : ''}

                <!-- Botón Descargar (todos) -->
                <button
                    type="button"
                    title="Descargar archivo"
                    onclick="descargarArchivo('${f.ruta}', '${escapeHtml(f.nombre)}')"
                    class="text-green-600 hover:text-green-800 text-xl">
                    ⬇️
                </button>

                <!-- Botón Reemplazar -->
                <button
                    type="button"
                    title="Reemplazar archivo"
                    onclick="reemplazarArchivoCuanti(${f.id})"
                    class="text-orange-600 hover:text-orange-800 text-xl">
                    ♻️
                </button>

                 <!-- ELIMINAR -->
                <button type="button" 
                        title="Eliminar archivo" 
                        onclick="eliminarArchivoCuanti(${f.id}, '${escapeHtml(f.nombre)}')"
                        class="text-red-600 hover:text-red-800 text-xl">
                    🗑️
                </button>
            </div>
        `;

        fileListPrecargadosCuanti.appendChild(div);
    });
}

function reemplazarArchivoCuanti(idArchivo) {
    const input = document.createElement("input");
    input.type = "file";

    input.onchange = async () => {
        if (!input.files.length) return;

        const file = input.files[0];

        // Validaciones
        if (!allowedTypes.includes(file.type)) {
            alert("❌ Tipo de archivo no permitido");
            return;
        }

        if (file.size > MAX_SIZE) {
            alert("❌ Archivo supera el tamaño permitido");
            return;
        }

        const codigoEnvio = window.codigoEnvioActual;
        const posSolicitud = window.posSolicitudActual;

        let formData = new FormData();
        formData.append("archivo", file);
        formData.append("idArchivo", idArchivo);
        formData.append("codigoEnvio", codigoEnvio);
        formData.append("posSolicitud", posSolicitud);

        const res = await fetch("actualizarResultadoArchivo.php", {
            method: "POST",
            body: formData
        });

        const r = await res.json();

        if (r.success) {
            alert("♻️ Archivo reemplazado correctamente");
            cargarArchivosCompletadosCuanti(codigoEnvio, posSolicitud);
        } else {
            alert("❌ Error: " + r.error);
        }
    };

    input.click();
}

function eliminarArchivoCuanti(idArchivo, nombreArchivo) {
    if (!confirm(`¿Estás seguro de eliminar el archivo "${nombreArchivo}"?\nEsta acción no se puede deshacer.`)) {
        return;
    }

    const codigoEnvio = window.codigoEnvioActual;
    const posSolicitud = window.posSolicitudActual;

    fetch('eliminar_archivo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            idArchivo: idArchivo
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Archivo eliminado correctamente');
                // Recargar la lista de archivos cuantitativos
                cargarArchivosCompletadosCuanti(codigoEnvio, posSolicitud);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión al eliminar archivo');
        });
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

function eliminarArchivo(idArchivo, nombreArchivo) {
    if (!confirm(`¿Estás seguro de eliminar el archivo "${nombreArchivo}"?\nEsta acción no se puede deshacer.`)) {
        return;
    }
    const codigoEnvio = document.getElementById("detailCodigo").textContent;
    fetch('eliminar_archivo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            idArchivo: idArchivo
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Archivo eliminado correctamente');
                // Recargar la lista de archivos
                cargarArchivosCompletados(codigoEnvio, currentPosition);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión al eliminar archivo');
        });
}


function abrirModalPdf(ruta, nombreArchivo) {
    document.getElementById('iframePdfPreview').src = ruta;
    document.getElementById('modalPdfPreview').classList.remove('hidden');
}

function cerrarModalPdf() {
    document.getElementById('modalPdfPreview').classList.add('hidden');
    document.getElementById('iframePdfPreview').src = '';
}


//####################################################################
//## SERELOGIA RESULTADOS CUANTITATIVOS
//####################################################################


// ============================================
// VARIABLES GLOBALES
// ============================================
window.enfermedadesActuales = [];
window.codigoEnvioActual = '';
window.enfermedadStates = {};
window.currentEnfermedadSelected = null;
window.estadoActualSolicitud = 'pendiente';

const CONFIG = {
    BB: {
        enfs: ['CAV', 'IBD', 'IBV', 'NDV', 'REO'],
        color: 'blue',
        bg: 'bg-blue-50',
        text: 'text-blue-800',
        border: 'border-blue-200'
    },
    ADULTO: {
        enfs: ['NC', 'BI', 'GUMBORO', 'APV', 'MG'],
        color: 'orange',
        bg: 'bg-orange-50',
        text: 'text-orange-800',
        border: 'border-orange-200'
    }
};



// ============================================
// 2. CARGAR SOLICITUD DESDE SIDEBAR
// ============================================
function cargarSolicitud(codigo, fecha, referencia, estado = 'pendiente', nomMuestra = '', posSolicitud = 1, analisisStr = '', analisisCodigosStr = '', analisisEnfermedadesStr = '') {
    window.codigoEnvioActual = codigo;
    window.posSolicitudActual = posSolicitud;
    window.enfermedadStates = {};

    window.estadoActualSolicitud = estado.toLowerCase();

    // 🗑️ Limpiar archivos precargados
    const seccionArchivos = document.getElementById('seccionArchivosCompletados');
    if (seccionArchivos) seccionArchivos.classList.add('hidden');
    const fileListPrecargados = document.getElementById('fileListPrecargados');
    if (fileListPrecargados) fileListPrecargados.innerHTML = '';

    /* CODIGO ANTERIOR
    // No se limpiaban ni cargaban archivos cuantitativos aquí
    */

    // 🗑️ Limpiar archivos cuantitativos
    limpiarArchivosCuanti();

    // 📎 Cargar archivos cuantitativos existentes (siempre)
    cargarArchivosCompletadosCuanti(codigo, posSolicitud);

    //document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('formPanel').classList.remove('hidden');
    //document.getElementById('lblCodigo').textContent = codigo;

    const lblEstado = document.getElementById('lblEstado');
    if (lblEstado) {
        const e = String(estado || 'pendiente').toLowerCase();
        if (e === 'pendiente') {
            lblEstado.innerHTML = `<span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>`;
        } else {
            const cap = (e.charAt(0).toUpperCase() + e.slice(1));
            lblEstado.innerHTML = `<span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">${cap}</span>`;
        }
    }

    document.getElementById('formAnalisis').reset();
    document.getElementById('codigoSolicitud').value = codigo;
    document.getElementById('fechaToma').value = fecha;

    // ✅ Limpiar fecha de registro del laboratorio al cambiar de solicitud
    const fechaLabCuanti = document.getElementById('fechaRegistroLabCuanti');
    if (fechaLabCuanti) {
        fechaLabCuanti.value = '';
    }

    const datosRef = decodificarCodRef(referencia);
    //document.getElementById('edadAves').value = datosRef.codRefCompleto;
    document.getElementById('codRef_granja').value = datosRef.granja;
    document.getElementById('codRef_campana').value = datosRef.campana;
    document.getElementById('codRef_galpon').value = datosRef.galpon;

    const edadField = document.getElementById('edadAves_display');
    if (edadField) edadField.value = datosRef.edad;

    // ✅ Cambiar texto del botón según estado
    const btnGuardar = document.querySelector('button[type="submit"]');
    if (btnGuardar) {
        if (estado.toLowerCase() === 'completado') {
            btnGuardar.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Actualizar Resultados';
            btnGuardar.className = 'bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-lg font-bold shadow-lg shadow-blue-500/30 transition-all transform hover:scale-105';
        } else {
            btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar Resultados';
            btnGuardar.className = 'bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-lg font-bold shadow-lg shadow-blue-500/30 transition-all transform hover:scale-105';
        }
    }

    // ✅ MODIFICADO: Cargar enfermedades desde los parámetros pasados
    if (analisisStr && analisisCodigosStr) {
        const nombresArr = analisisStr.split(', ').map(s => s.trim());
        const codigosArr = analisisCodigosStr.split(',').map(s => s.trim());
        const enfermedadesArr = analisisEnfermedadesStr ? analisisEnfermedadesStr.split(',').map(s => s.trim()).filter(s => s !== '') : []; // ← Cambio clave

        window.enfermedadesActuales = nombresArr.map((nombre, i) => ({
            nombre: nombre,
            codigo: codigosArr[i] || '',
            enfermedad: enfermedadesArr[i] || ''
        }));

        // === Llenar select de enfermedades ===
        const selectEnfermedad = document.getElementById('selectEnfermedad');
        if (selectEnfermedad) {
            selectEnfermedad.innerHTML = '<option value="">Seleccionar enfermedad</option>';

            const enfermedadesUnicas = [...new Set(enfermedadesArr)];

            enfermedadesUnicas.forEach(enfermedad => {
                if (enfermedad) {
                    const option = document.createElement('option');
                    option.value = enfermedad;
                    option.textContent = enfermedad;
                    selectEnfermedad.appendChild(option);
                }
            });

            const label = selectEnfermedad.parentElement.querySelector('label');
            if (label) {
                label.textContent = `Seleccione Enfermedad (${enfermedadesUnicas.length} asignadas)`;
            }
        }

        detectarTipo(parseInt(datosRef.edad), nomMuestra);

        /* CODIGO ANTERIOR (solo cargaba datos cuando estado era completado)
        if (estado.toLowerCase() === 'completado') {
            setTimeout(() => {
                cargarDatosCompletados(codigo);
            }, 300);
        }
        */

        // ✅ Siempre intentar cargar datos guardados (incluso si está pendiente)
        setTimeout(() => {
            cargarDatosCompletados(codigo);
        }, 300);
    } else {
        // Fallback: cargar desde el servidor
        fetch(`crud-serologia.php?action=get_enfermedades&codEnvio=${codigo}&posSolicitud=${posSolicitud}&estado=${estado}`)
            .then(r => {
                if (!r.ok) throw new Error('HTTP error! status: ' + r.status);
                return r.text();
            })
            .then(text => {
                console.log('Respuesta del servidor:', text);
                const data = JSON.parse(text);
                if (data.success) {
                    window.enfermedadesActuales = data.enfermedades;

                    detectarTipo(parseInt(datosRef.edad), nomMuestra);

                    /* CODIGO ANTERIOR (solo cargaba datos cuando estado era completado)
                    if (estado.toLowerCase() === 'completado') {
                        setTimeout(() => {
                            cargarDatosCompletados(codigo);
                        }, 300);
                    }
                    */

                    // ✅ Siempre intentar cargar datos guardados (incluso si está pendiente)
                    setTimeout(() => {
                        cargarDatosCompletados(codigo);
                    }, 300);
                } else {
                    alert('❌ Error: ' + (data.message || 'No se pudieron cargar enfermedades'));
                }
            })
            .catch(e => {
                console.error('Error completo:', e);
                alert('❌ Error de conexión. Ver consola para detalles.');
            });
    }
}

// ============================================
// 1. DECODIFICAR CÓDIGO REF
// ============================================
function decodificarCodRef(codRef) {
    const refStr = String(codRef).padStart(10, '0');
    return {
        granja: refStr.substring(0, 3),
        campana: refStr.substring(3, 6),
        galpon: refStr.substring(6, 8),
        edad: refStr.substring(8, 10),
        codRefCompleto: parseInt(refStr, 10)
    };
}

// ============================================
// CARGAR DATOS GUARDADOS (COMPLETADOS)
// ============================================
async function cargarDatosCompletados(codigoEnvio) {
    console.log('🔍 Cargando datos guardados para:', codigoEnvio);

    const enfermedades = window.enfermedadesActuales || [];

    if (enfermedades.length === 0) {
        console.warn('No hay enfermedades para cargar datos');
        return;
    }

    const tipo = document.getElementById('tipo_ave_hidden')?.value || 'BB';
    const posSolicitud = window.posSolicitudActual || 1; // ✅ Obtener posSolicitud actual
    console.log('📊 Tipo detectado:', tipo);
    console.log('📌 posSolicitud:', posSolicitud);

    // Variable para guardar la fecha de registro del laboratorio (solo se necesita cargar una vez)
    let fechaRegistroLabCargada = false;

    // Cargar TODAS las enfermedades en paralelo
    const promesas = enfermedades.map(async (enf) => {
        /* CODIGO ANTERIOR (no incluía posSolicitud)
        const url = `crud-serologia.php?action=get_resultados_guardados&codEnvio=${codigoEnvio}&enfermedad=${encodeURIComponent(enf.nombre)}`;
        */
        // ✅ Incluir posSolicitud en la URL
        const url = `crud-serologia.php?action=get_resultados_guardados&codEnvio=${codigoEnvio}&posSolicitud=${posSolicitud}&enfermedad=${encodeURIComponent(enf.nombre)}`;
        console.log('🌐 Consultando:', url);

        try {
            const response = await fetch(url);
            const data = await response.json();

            if (data.success && data.datos) {
                console.log(`✅ Datos cargados para ${enf.nombre}:`, data.datos);

                const state = {};
                const d = data.datos;

                // ✅ Cargar fecha de registro del laboratorio (solo una vez)
                if (!fechaRegistroLabCargada && d.fecha_registro_lab) {
                    const inputFechaLab = document.getElementById('fechaRegistroLabCuanti');
                    if (inputFechaLab) {
                        inputFechaLab.value = d.fecha_registro_lab;
                        console.log('📅 Fecha de registro del laboratorio cargada:', d.fecha_registro_lab);
                    }
                    fechaRegistroLabCargada = true;
                }

                // Campos principales
                state[`${enf.nombre}_gmean`] = d.gmean || '';
                state[`${enf.nombre}_cv`] = d.cv || '';
                state[`${enf.nombre}_sd`] = d.desviacion_estandar || '';
                state[`${enf.nombre}_count`] = d.count_muestras || 20;

                // Niveles según tipo
                if (tipo.toUpperCase() === 'ADULTO') {
                    for (let i = 1; i <= 6; i++) {
                        const colBD = `s${String(i).padStart(2, '0')}`;
                        const nombreInput = `${enf.nombre}_s${i}`;
                        state[nombreInput] = d[colBD] || '';
                        console.log(`  ${nombreInput} = ${d[colBD]}`);
                    }
                } else {
                    for (let i = 0; i <= 25; i++) {
                        const colBD = `nivel_${i}`;
                        const nombreInput = `${enf.nombre}_n${i}`;
                        state[nombreInput] = d[colBD] || 0;
                    }
                }

                window.enfermedadStates[enf.nombre] = state;
                console.log(`💾 Estado guardado para ${enf.nombre}:`, state);

                return true;
            } else {
                console.log(`ℹ️ Sin datos guardados para ${enf.nombre}`);
                return false;
            }
        } catch (e) {
            console.error(`❌ Error cargando ${enf.nombre}:`, e);
            return false;
        }
    });

    // Esperar a que TODAS las peticiones terminen
    await Promise.all(promesas);

    // AHORA SÍ rellenar el panel
    const selectEnf = document.getElementById('selectEnfermedad');
    if (selectEnf && selectEnf.value) {
        console.log(`🖊️ Rellenando panel de: ${selectEnf.value}`);
        populatePanelValues(selectEnf.value);
    }

    /* CODIGO ANTERIOR
    // 📎 Cargar archivos adjuntos (usaba función de cualitativos)
    await cargarArchivosCompletados(codigoEnvio);
    */

    // 📎 Cargar archivos adjuntos cuantitativos
    await cargarArchivosCompletadosCuanti(codigoEnvio, posSolicitud);
}

// ============================================
// 3. DETECTAR TIPO (BB vs ADULTO)
// ============================================
function detectarTipo(edad, nomMuestra = '') {
    let tipo = 'ADULTO';
    const nombreMuestraUpper = (nomMuestra || '').toUpperCase();

    if (nombreMuestraUpper.includes('POLLO ADULTO')) {
        tipo = 'ADULTO';
    } else if (nombreMuestraUpper.includes('POLLO BB')) {
        tipo = 'BB';
    } else {
        const edadInt = parseInt(edad, 10) || 0;
        if (edadInt === 1) {
            tipo = 'BB';
        }
    }

    document.getElementById('tipo_ave_hidden').value = tipo;

    const badge = document.getElementById('badgeTipo');
    badge.textContent = (tipo === 'BB') ? 'POLLO BB' : 'POLLO ADULTO';
    badge.className = (tipo === 'BB')
        ? 'px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200'
        : 'px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700 border border-orange-200';

    const campoInforme = document.getElementById('numeroInforme');
    if (campoInforme) {
        if (tipo === 'ADULTO') {
            if (campoInforme.parentElement) campoInforme.parentElement.style.display = 'none';
            campoInforme.value = '';
        } else {
            if (campoInforme.parentElement) campoInforme.parentElement.style.display = 'block';
        }
    }

    renderizarCampos(tipo);
    renderizarEnfermedades(tipo);

    setTimeout(() => {
        if (tipo === 'ADULTO') {
            const granja = document.getElementById('codRef_granja')?.value || '';
            const campana = document.getElementById('codRef_campana')?.value || '';
            const galpon = document.getElementById('codRef_galpon')?.value || '';
            const edad = document.getElementById('edadAves_display')?.value || '';

            const granjaDisplay = document.getElementById('codRef_granja_display');
            const campanaDisplay = document.getElementById('codRef_campana_display');
            const galponDisplay = document.getElementById('codRef_galpon_display');
            const edadDisplay = document.getElementById('edadAves_display');

            if (granjaDisplay) granjaDisplay.value = granja;
            if (campanaDisplay) campanaDisplay.value = campana;
            if (galponDisplay) galponDisplay.value = galpon;
            if (edadDisplay) edadDisplay.value = edad;
        }
    }, 0);
}


// ============================================
// 4. RENDERIZAR CAMPOS ESPECÍFICOS
// ============================================
function renderizarCampos(tipo) {
    const container = document.getElementById('camposEspecificos');

    if (tipo === 'BB') {
        const granja = document.getElementById('codRef_granja')?.value || '';
        const campana = document.getElementById('codRef_campana')?.value || '';
        const galpon = document.getElementById('codRef_galpon')?.value || '';

        let edadRef = '';
        const edadDisplayVal = document.getElementById('edadAves_display')?.value;
        if (edadDisplayVal && String(edadDisplayVal).length > 0) {
            edadRef = String(edadDisplayVal).slice(-2);
        } else {
            const edadAvesFull = document.getElementById('edadAves')?.value || '';
            const tmp = String(edadAvesFull);
            edadRef = tmp.length > 2 ? tmp.slice(-2) : tmp;
        }

        container.innerHTML = ``;
    } else {
        const granja = document.getElementById('codRef_granja')?.value || '';
        const campana = document.getElementById('codRef_campana')?.value || '';
        const galpon = document.getElementById('codRef_galpon')?.value || '';
        const edad = document.getElementById('edadAves_display')?.value || '';

        /* CODIGO ANTERIOR (panel de datos decodificados visible)
        container.innerHTML = `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 col-span-3">
                <h4 class="text-[10px] font-bold text-blue-700 uppercase mb-3 flex items-center gap-1">
                    <i class="fas fa-lock"></i> Datos Decodificados del Código Ref
                </h4>
                <div class="grid grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Granja</label>
                        <input type="text" id="codRef_granja_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${granja}" readonly>
                        <input type="hidden" id="codRef_granja" name="codigo_granja" value="${granja}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Campaña</label>
                        <input type="text" id="codRef_campana_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${campana}" readonly>
                        <input type="hidden" id="codRef_campana" name="codigo_campana" value="${campana}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Galpón</label>
                        <input type="text" id="codRef_galpon_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${galpon}" readonly>
                        <input type="hidden" id="codRef_galpon" name="numero_galpon" value="${galpon}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Edad (Ref)</label>
                        <input type="text" id="edadAves_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${edad}" readonly>
                    </div>
                </div>
            </div>
        `;
        */
        // Panel comentado - no se muestra
        container.innerHTML = ``;
    }
}

// ============================================
// 5. RENDERIZAR ENFERMEDADES
// ============================================
function renderizarEnfermedades(tipo) {
    const container = document.getElementById('contenedorEnfermedades');

    const enfermedades = window.enfermedadesActuales || [];

    if (enfermedades.length === 0) {
        container.innerHTML = '<p class="text-red-500 text-sm">⚠️ No hay enfermedades asignadas</p>';
        return;
    }

    const conf = (tipo === 'BB')
        ? { color: 'blue', bg: 'bg-blue-50', text: 'text-blue-800', border: 'border-blue-200' }
        : { color: 'orange', bg: 'bg-orange-50', text: 'text-orange-800', border: 'border-orange-200' };

    /* CODIGO ANTERIOR - Mostraba badges/chips de enfermedades arriba del select
    let html = `
        <div class="flex justify-between items-center mb-4 gap-3">
            <div class="flex-1">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-2">
                    Seleccione Enfermedad (${enfermedades.length} asignada${enfermedades.length !== 1 ? 's' : ''})
                </label>
                <select id="selectEnfermedad" class="input-lab">
                    ${enfermedades.map(e => {
        const displayText = e.enfermedad ? `${e.nombre} (${e.enfermedad})` : e.nombre;
        return `<option value="${e.nombre}">${displayText}</option>`;
    }).join('')}
                </select>
            </div>
            <div class="pt-5">
                <button type="button" onclick="abrirModalAgregarEnfermedad()" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold text-sm shadow-lg transition-all">
                    <i class="fas fa-plus-circle mr-2"></i> Agregar Enfermedad
                </button>
            </div>
        </div>
        <div id="enfermedadPanel"></div>
    `;
    */

    //  Solo select y botón agregar, sin badges arriba
    let html = `
        <div class="flex justify-between items-center mb-4 gap-3">
            <div class="flex-1">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-2">
                    SELECCIONE ENFERMEDAD PARA EDITAR RESULTADOS
                </label>
                <select id="selectEnfermedad" class="input-lab">
                    ${enfermedades.map(e => {
        const displayText = e.enfermedad ? `${e.nombre} (${e.enfermedad})` : e.nombre;
        return `<option value="${e.nombre}" data-codigo="${e.codigo}">${displayText}</option>`;
    }).join('')}
                </select>
            </div>
            <div class="pt-5">
                <button type="button" onclick="abrirModalAgregarEnfermedad()" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold text-sm shadow-lg transition-all">
                    <i class="fas fa-plus-circle mr-2"></i> Agregar Enfermedad
                </button>
            </div>
        </div>
        <div id="enfermedadPanel"></div>
    `;

    container.innerHTML = html;

    const select = document.getElementById('selectEnfermedad');
    const inicial = select.value;
    window.currentEnfermedadSelected = inicial;
    renderEnfermedadPanel(inicial, conf, tipo);

    select.addEventListener('change', (e) => {
        const nuevo = e.target.value;

        if (window.currentEnfermedadSelected) {
            saveCurrentEnfermedadState(window.currentEnfermedadSelected);
            console.log('💾 Guardado:', window.currentEnfermedadSelected);
        }

        window.currentEnfermedadSelected = nuevo;
        renderEnfermedadPanel(nuevo, conf, tipo);
        populatePanelValues(nuevo);

        console.log('📂 Cargando:', nuevo);
    });
}

// ============================================
// MODAL DE CONFIRMACIÓN PARA CUANTITATIVOS
// ============================================
function abrirModalConfirmacionCuanti() {
    // Validar fecha de registro del laboratorio
    let fechaRegistroLabCuanti = document.getElementById('fechaRegistroLabCuanti').value.trim();

    if (!fechaRegistroLabCuanti) {
        alert("⚠️ Tiene que seleccionar una fecha de registro del laboratorio primero.");
        return;
    }

    // Validar que hay datos antes de abrir el modal
    const enfermedadStates = window.enfermedadStates || {};
    let enfermedadesConDatos = Object.keys(enfermedadStates).filter(enf => {
        const st = enfermedadStates[enf] || {};
        return Object.keys(st).some(k => st[k] !== null && st[k] !== '');
    }).length;

    if (enfermedadesConDatos === 0) {
        const visibles = document.querySelectorAll('input[name="enfermedades[]"]');
        enfermedadesConDatos = visibles.length || 0;
    }

    if (enfermedadesConDatos === 0) {
        alert('⚠️ No hay enfermedades con datos para guardar');
        return;
    }

    document.getElementById('modalConfirmacionCuanti').classList.remove('hidden');
}

function cerrarModalConfirmacionCuanti() {
    document.getElementById('modalConfirmacionCuanti').classList.add('hidden');
}

// Cerrar al hacer clic fuera del modal
document.addEventListener('DOMContentLoaded', function () {
    const modalCuanti = document.getElementById('modalConfirmacionCuanti');
    if (modalCuanti) {
        modalCuanti.addEventListener('click', function (e) {
            if (e.target === this) {
                cerrarModalConfirmacionCuanti();
            }
        });
    }
});

function guardarResultadosCuanti(estadoCuanti) {
    cerrarModalConfirmacionCuanti();
    guardar(null, estadoCuanti);
}

/* CODIGO ANTERIOR
function guardar(e) {
    e.preventDefault();

    saveCurrentEnfermedadState(window.currentEnfermedadSelected);

    const form = document.getElementById('formAnalisis');
    document.querySelectorAll('.tmp-enf-input').forEach(n => n.remove());

    // ✅ Determinar si es UPDATE o CREATE según el estado
    const esActualizacion = window.estadoActualSolicitud === 'completado';
*/

function guardar(e, estadoCuanti = 'completado') {
    if (e) e.preventDefault();

    saveCurrentEnfermedadState(window.currentEnfermedadSelected);

    const form = document.getElementById('formAnalisis');
    document.querySelectorAll('.tmp-enf-input').forEach(n => n.remove());

    // ✅ Determinar si es UPDATE o CREATE según el estado
    const esActualizacion = window.estadoActualSolicitud === 'completado';
    const actionValue = esActualizacion ? 'update' : 'create';

    // Cambiar el valor del action hidden
    document.getElementById('action').value = actionValue;

    // ✅ LOG: Ver qué enfermedades se van a enviar
    const enfermedadesAEnviar = Object.keys(window.enfermedadStates || {});
    console.log('📋 Enfermedades a enviar:', enfermedadesAEnviar);
    console.log('📋 Total enfermedades:', enfermedadesAEnviar.length);
    
    // ✅ Usar Set para evitar duplicados
    const enfermedadesUnicas = [...new Set(enfermedadesAEnviar)];
    
    enfermedadesUnicas.forEach(enfName => {
        const st = window.enfermedadStates[enfName] || {};
        const inpE = document.createElement('input');
        inpE.type = 'hidden';
        inpE.name = 'enfermedades[]';
        inpE.value = enfName;
        inpE.className = 'tmp-enf-input';
        form.appendChild(inpE);

        Object.keys(st).forEach(key => {
            if (key.toLowerCase().includes('archivo')) return;
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = key;
            inp.value = st[key];
            inp.className = 'tmp-enf-input';
            form.appendChild(inp);
        });
    });

    const fd2 = new FormData(form);

    /* CODIGO ANTERIOR
    const archivos = document.getElementById('archivoPdf') ? document.getElementById('archivoPdf').files : [];

    console.log('📁 Archivos detectados:', archivos.length);

    if (archivos && archivos.length > 0) {
        for (let i = 0; i < archivos.length; i++) {
            console.log(`   Agregando archivo #${i}: ${archivos[i].name} (${archivos[i].size} bytes)`);
            fd2.append('archivoPdf[]', archivos[i]);
        }
    }
    */

    // Usar archivoPdfCuanti para cuantitativos
    const archivosCuanti = document.getElementById('archivoPdfCuanti') ? document.getElementById('archivoPdfCuanti').files : [];

    console.log('📁 Archivos cuantitativos detectados:', archivosCuanti.length);

    if (archivosCuanti && archivosCuanti.length > 0) {
        for (let i = 0; i < archivosCuanti.length; i++) {
            console.log(`   Agregando archivo #${i}: ${archivosCuanti[i].name} (${archivosCuanti[i].size} bytes)`);
            fd2.append('archivoPdf[]', archivosCuanti[i]);
        }
    }

    let archivosCount = 0;
    for (let pair of fd2.entries()) {
        if (pair[1] instanceof File) archivosCount++;
    }

    const enfermedadStates = window.enfermedadStates || {};
    let enfermedadesConDatos = Object.keys(enfermedadStates).filter(enf => {
        const st = enfermedadStates[enf] || {};
        return Object.keys(st).some(k => st[k] !== null && st[k] !== '');
    }).length;

    if (enfermedadesConDatos === 0) {
        const visibles = document.querySelectorAll('input[name="enfermedades[]"]');
        enfermedadesConDatos = visibles.length || 0;
    }

    if (enfermedadesConDatos === 0) {
        document.querySelectorAll('.tmp-enf-input').forEach(n => n.remove());
        alert('⚠️ No hay enfermedades con datos para guardar');
        return;
    }

    /* CODIGO ANTERIOR
    // ✅ Mensaje diferente según acción
    const accionTexto = esActualizacion ? 'Actualizar' : 'Guardar';
    if (!confirm(`¿${accionTexto} análisis?`)) {
        document.querySelectorAll('.tmp-enf-input').forEach(n => n.remove());
        return;
    }

    const btn = document.querySelector('button[type="submit"]');
    const original = btn.innerHTML;
    const textoBoton = esActualizacion ? 'Actualizando...' : 'Guardando...';
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${textoBoton}`;
    btn.disabled = true;
    */

    /* CODIGO ANTERIOR (selector incorrecto)
    const btn = document.querySelector('#formAnalisis button[type="button"]');
    */

    // ✅ Ahora el confirm ya no es necesario porque usamos el modal
    const accionTexto = esActualizacion ? 'Actualizar' : 'Guardar';

    // ✅ Usar el ID específico del botón de guardar cuantitativos
    const btn = document.getElementById('btnGuardarCuanti');
    const original = btn ? btn.innerHTML : '';
    const textoBoton = esActualizacion ? 'Actualizando...' : 'Guardando...';
    if (btn) {
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${textoBoton}`;
        btn.disabled = true;
    }

    // ✅ Agregar posSolicitud directamente al FormData (NO al form, ya que fd2 ya fue creado)
    fd2.append('posSolicitud', window.posSolicitudActual || 1);

    // ✅ Agregar estado cuantitativo al FormData
    fd2.append('estadoCuanti', estadoCuanti);

    console.log(`📤 ${accionTexto} datos:`, actionValue);
    for (let pair of fd2.entries()) {
        if (pair[1] instanceof File) {
            console.log(`  ${pair[0]}: [Archivo] ${pair[1].name}`);
        } else {
            console.log(`  ${pair[0]}: ${pair[1]}`);
        }
    }

    fetch('crud-serologia.php', {
        method: 'POST',
        body: fd2
    })
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP error! status: ${r.status}`);
            }
            return r.text();
        })
        .then(text => {
            console.log('📥 Respuesta del servidor:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                throw new Error('Respuesta del servidor no es JSON válido: ' + text.substring(0, 200));
            }

            if (data.success) {
                const accionTexto = window.estadoActualSolicitud === 'completado' ? 'actualizado' : 'guardado';

                /* CODIGO ANTERIOR
                alert(`✅ Análisis ${accionTexto} correctamente`);
                
                // Restaurar botón
                btn.innerHTML = original;
                btn.disabled = false;
                
                // Actualizar estado a completado sin recargar la página
                window.estadoActualSolicitud = 'completado';
                
                // Actualizar el badge de estado cuantitativo
                const badgeCuanti = document.getElementById('badgeStatusCuanti');
                if (badgeCuanti) {
                    badgeCuanti.textContent = 'Completado';
                    badgeCuanti.className = 'inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide bg-green-100 text-green-800 ring-2 ring-green-300';
                }
                
                // Actualizar el botón a modo "Actualizar"
                btn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Actualizar Resultados';
                btn.className = 'bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-lg font-bold shadow-lg shadow-blue-500/30 transition-all transform hover:scale-105';
                */

                const estadoGuardado = estadoCuanti; // Estado seleccionado en el modal
                alert(`✅ Análisis ${accionTexto} como ${estadoGuardado}`);

                // Restaurar botón
                if (btn) {
                    btn.innerHTML = original;
                    btn.disabled = false;
                }

                // Actualizar estado según lo seleccionado en el modal
                window.estadoActualSolicitud = estadoGuardado;

                // Actualizar el badge de estado cuantitativo según el estado seleccionado
                const badgeCuanti = document.getElementById('badgeStatusCuanti');
                if (badgeCuanti) {
                    if (estadoGuardado === 'completado') {
                        badgeCuanti.textContent = 'COMPLETADO';
                        badgeCuanti.className = 'inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide bg-green-100 text-green-800 ring-2 ring-green-300';
                    } else {
                        badgeCuanti.textContent = 'PENDIENTE';
                        badgeCuanti.className = 'inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide bg-yellow-100 text-yellow-800 ring-2 ring-yellow-300';
                    }
                }

                // Actualizar el botón según el estado
                if (btn) {
                    if (estadoGuardado === 'completado') {
                        btn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Actualizar Resultados';
                    } else {
                        btn.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar Resultados';
                    }
                    btn.className = 'bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-lg font-bold shadow-lg shadow-blue-500/30 transition-all transform hover:scale-105';
                }

                // Actualizar el sidebar para reflejar el cambio de estado
                loadSidebar(currentPage);

                // Limpiar inputs temporales
                document.querySelectorAll('.tmp-enf-input').forEach(n => n.remove());

                // Limpiar input de archivos nuevos y recargar archivos guardados
                const inputPDFCuantiLocal = document.getElementById('archivoPdfCuanti');
                if (inputPDFCuantiLocal) {
                    const dt = new DataTransfer();
                    inputPDFCuantiLocal.files = dt.files;
                }
                const fileListCuantiLocal = document.getElementById('fileListCuanti');
                if (fileListCuantiLocal) {
                    fileListCuantiLocal.innerHTML = '';
                }

                // Recargar los archivos guardados de cuantitativos
                cargarArchivosCompletadosCuanti(window.codigoEnvioActual, window.posSolicitudActual);

                // ✅ Limpiar registro de enfermedades agregadas recientemente (ya están guardadas)
                window.enfermedadesAgregadasReciente = {};

                // ✅ Re-renderizar el panel para quitar la X de eliminar
                const tipo = document.getElementById('tipo_ave_hidden')?.value || 'BB';
                renderizarEnfermedades(tipo);
            } else {
                alert('❌ Error: ' + data.message);
                if (btn) {
                    btn.innerHTML = original;
                    btn.disabled = false;
                }
            }
        })
        .catch(e => {
            console.error('Error completo:', e);
            alert('❌ Error de conexión: ' + e.message);
            if (btn) {
                btn.innerHTML = original;
                btn.disabled = false;
            }
        });
}

// ============================================
// 6. RENDERIZAR PANEL DE ENFERMEDAD
// ============================================
function renderEnfermedadPanel(enf, conf, tipo) {
    const panel = document.getElementById('enfermedadPanel');

    // ✅ Verificar si la enfermedad fue agregada recientemente (en esta sesión)
    const esEnfermedadReciente = window.enfermedadesAgregadasReciente &&
        window.enfermedadesAgregadasReciente[enf] !== undefined;

    // Obtener código de la enfermedad del select o del registro de recientes
    const selectEnf = document.getElementById('selectEnfermedad');
    const optionSeleccionada = selectEnf ? selectEnf.querySelector(`option[value="${enf}"]`) : null;
    let codigoEnfermedad = optionSeleccionada ? optionSeleccionada.dataset.codigo : '';

    // Si es reciente, usar el código guardado
    if (esEnfermedadReciente && !codigoEnfermedad) {
        codigoEnfermedad = window.enfermedadesAgregadasReciente[enf];
    }

    let nivelesHtml = '';
    if ((tipo || '').toUpperCase() === 'ADULTO') {
        nivelesHtml = `<div class="mt-2 grid grid-cols-6 gap-2 bg-gray-50 p-2 rounded border border-gray-100">` +
            Array.from({ length: 6 }, (_, i) => {
                const idx = i + 1;
                return `<input type="number" name="${enf}_s${idx}" placeholder="S${idx}" class="text-center text-[10px] border border-gray-300 rounded h-7 w-full focus:border-blue-500 outline-none">`;
            }).join('') +
            `</div>`;
    } else {
        nivelesHtml = `<div class="mt-2 bg-gray-50 p-3 rounded border border-gray-100">
            <div class="text-sm font-semibold text-gray-600 mb-2">Niveles (N0 - N25)</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">` +
            Array.from({ length: 26 }, (_, i) => {
                const label = 't' + String(i + 1).padStart(2, '0');
                return `<div class="flex flex-col">
                    <label class="text-[10px] text-gray-500 mb-1 text-center font-medium">${label}</label>
                    <input type="number" name="${enf}_n${i}" placeholder="${label}" class="text-center text-sm border border-gray-300 rounded py-2 px-2 w-full focus:border-blue-500 outline-none bg-white">
                 </div>`
            }).join('') +
            `</div></div>`;
    }

    /* CODIGO ANTERIOR - sin botón de eliminar enfermedad
    panel.innerHTML = `
        <div class="border ${conf.border} rounded-lg bg-white overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div class="px-4 py-2 ${conf.bg} border-b ${conf.border} flex justify-between items-center">
                <span class="font-bold ${conf.text}">${enf}</span>
                <input type="hidden" name="enfermedades[]" value="${enf}">
                <span class="text-[10px] text-gray-500 opacity-70">Resultados</span>
            </div>
    */

    //  Panel con X roja solo para enfermedades agregadas recientemente
    const botonEliminar = esEnfermedadReciente ? `
            <button type="button" 
                onclick="eliminarEnfermedadCuanti('${enf}', '${codigoEnfermedad}')"
                title="Eliminar enfermedad"
                class="absolute top-1 right-1 text-red-500 hover:text-red-700 text-lg font-bold z-10 w-6 h-6 flex items-center justify-center hover:bg-red-50 rounded">
                ×
            </button>` : '';

    panel.innerHTML = `
        <div class="relative border border-gray-200 rounded-lg bg-white overflow-hidden shadow-sm">
            <!-- X roja para eliminar enfermedad (solo visible si fue agregada recientemente) -->
            ${botonEliminar}
            
            <div class="px-4 py-2 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <span class="font-bold text-gray-700">${enf}</span>
                <input type="hidden" name="enfermedades[]" value="${enf}">
                <span class="text-[10px] text-gray-500 opacity-70 mr-6">Resultados</span>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1">GMEAN</label>
                        <input type="number" step="0.01" name="${enf}_gmean" class="input-lab font-bold text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1">CV %</label>
                        <input type="number" step="0.01" name="${enf}_cv" class="input-lab">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1">SD</label>
                        <input type="number" step="0.01" name="${enf}_sd" class="input-lab">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1">COUNT</label>
                        <input type="number" value="20" name="${enf}_count" class="input-lab bg-gray-50 text-center">
                    </div>
                </div>

                <details class="group">
                    <summary class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-500 hover:text-blue-600 p-1">
                        <i class="fas fa-chart-bar"></i> Niveles
                    </summary>
                    ${nivelesHtml}
                </details>
            </div>
        </div>`;

    populatePanelValues(enf);
}

// ============================================
// ELIMINAR ENFERMEDAD DE CUANTITATIVOS
// ============================================
function eliminarEnfermedadCuanti(nombreEnfermedad, codigoEnfermedad) {
    /* CODIGO ANTERIOR - no permitía eliminar si tenía resultados
    const tieneEstado = window.enfermedadStates && 
        window.enfermedadStates[nombreEnfermedad] && 
        Object.keys(window.enfermedadStates[nombreEnfermedad]).some(k => {
            const val = window.enfermedadStates[nombreEnfermedad][k];
            return val !== null && val !== '' && val !== '0' && val !== 0;
        });

    if (tieneEstado) {
        alert('⚠️ Esta enfermedad ya tiene resultados guardados. No se puede eliminar.');
        return;
    }
    */

    //  Permitir eliminar aunque tenga resultados (solo enfermedades agregadas recientemente)
    if (!confirm(`¿Eliminar "${nombreEnfermedad}" de esta solicitud?\n\nSi tiene resultados guardados, también se eliminarán.`)) {
        return;
    }

    // Llamar al backend para eliminar de san_fact_solicitud_det
    const fd = new FormData();
    fd.append('action', 'eliminar_enfermedad_solicitud');
    fd.append('codEnvio', window.codigoEnvioActual);
    fd.append('posSolicitud', window.posSolicitudActual);
    fd.append('codAnalisis', codigoEnfermedad);
    fd.append('nomAnalisis', nombreEnfermedad);

    fetch('crud-serologia.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ Enfermedad eliminada');

                // Eliminar del estado local
                if (window.enfermedadStates && window.enfermedadStates[nombreEnfermedad]) {
                    delete window.enfermedadStates[nombreEnfermedad];
                }

                // Eliminar del registro de enfermedades recientes
                if (window.enfermedadesAgregadasReciente && window.enfermedadesAgregadasReciente[nombreEnfermedad]) {
                    delete window.enfermedadesAgregadasReciente[nombreEnfermedad];
                }

                // Recargar lista de enfermedades
                fetch(`crud-serologia.php?action=get_enfermedades&codEnvio=${window.codigoEnvioActual}&posSolicitud=${window.posSolicitudActual}`)
                    .then(r => r.json())
                    .then(dd => {
                        if (dd.success) {
                            window.enfermedadesActuales = dd.enfermedades;
                            const tipo = document.getElementById('tipo_ave_hidden')?.value || 'BB';
                            renderizarEnfermedades(tipo);
                        }
                    });
            } else {
                alert('❌ Error: ' + (data.message || 'No se pudo eliminar'));
            }
        })
        .catch(e => {
            console.error(e);
            alert('❌ Error de conexión');
        });
}

// ============================================
// GUARDAR Y CARGAR ESTADOS
// ============================================
function saveCurrentEnfermedadState(enfName) {
    try {
        if (!enfName) return;
        const panel = document.getElementById('enfermedadPanel');
        if (!panel) return;

        const inputs = panel.querySelectorAll('input[name]');
        const state = {};
        inputs.forEach(inp => {
            if (inp.type === 'file') return;
            state[inp.name] = inp.value;
        });

        window.enfermedadStates[enfName] = state;
    } catch (e) {
        console.error('Error guardando estado enfermedad:', e);
    }
}

function populatePanelValues(enfName) {
    try {
        if (!enfName) return;

        const state = window.enfermedadStates[enfName];
        if (!state) {
            console.log(`ℹ️ Sin estado guardado para: ${enfName}`);
            return;
        }

        const panel = document.getElementById('enfermedadPanel');
        if (!panel) {
            console.error('❌ Panel no encontrado');
            return;
        }

        console.log('🔧 Rellenando campos con:', state);

        Object.keys(state).forEach(key => {
            const el = panel.querySelector(`[name="${key}"]`);
            if (el) {
                el.value = state[key];
                console.log(`  ✅ ${key} = ${state[key]}`);
            } else {
                console.warn(`  ⚠️ Campo no encontrado: ${key}`);
            }
        });

        console.log('✅ Restaurado estado de', enfName);
    } catch (e) {
        console.error('Error restaurando estado enfermedad:', e);
    }
}


// ============================================
// 9. MODAL AGREGAR ENFERMEDAD
// ============================================
function abrirModalAgregarEnfermedad() {
    const modal = document.createElement('div');
    modal.id = 'modalAgregarEnfermedad';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-[80vh] overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-green-700 text-white flex justify-between items-center">
                <h3 class="font-bold text-lg"><i class="fas fa-plus-circle mr-2"></i> Agregar Nueva Enfermedad</h3>
                <button onclick="cerrarModalAgregarEnfermedad()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Seleccione una enfermedad del catálogo:</p>
                <div class="mb-4">
                    <input type="text" id="filtroEnfermedades" placeholder="🔍 Buscar..." 
                        class="w-full p-3 border rounded-lg" onkeyup="filtrarEnfermedadesCatalogo()">
                </div>
                <div id="listadoEnfermedades" class="max-h-96 overflow-y-auto border rounded-lg">
                    <div class="text-center py-10">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                        <p class="text-gray-500 mt-2">Cargando...</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    fetch('crud-serologia.php?action=get_catalogo_enfermedades')
        .then(r => r.text())
        .then(text => {
            console.log('Catálogo:', text);
            const data = JSON.parse(text);
            if (data.success) mostrarCatalogoEnfermedades(data.enfermedades);
        })
        .catch(e => {
            console.error(e);
            document.getElementById('listadoEnfermedades').innerHTML = '<p class="text-red-500 p-4">Error al cargar catálogo</p>';
        });
}

function cerrarModalAgregarEnfermedad() {
    const modal = document.getElementById('modalAgregarEnfermedad');
    if (modal) modal.remove();
}

function mostrarCatalogoEnfermedades(catalogo) {
    const listado = document.getElementById('listadoEnfermedades');
    const codigosAsignados = window.enfermedadesActuales.map(e => parseInt(e.codigo));
    const disponibles = catalogo.filter(e => !codigosAsignados.includes(parseInt(e.codigo)));

    if (disponibles.length === 0) {
        listado.innerHTML = '<p class="text-gray-500 text-center p-8">✅ Todas las enfermedades están asignadas</p>';
        return;
    }

    listado.innerHTML = disponibles.map(e => `
        <div class="p-3 border-b hover:bg-gray-50 cursor-pointer transition-colors enfermedad-item"
            onclick="agregarEnfermedadASolicitud('${e.codigo}', '${e.nombre}')">
            <div class="font-bold text-gray-800">${e.nombre}</div>
            <div class="text-xs text-gray-500">${e.enfermedad_completa || 'Sin descripción'}</div>
        </div>
    `).join('');
}

function filtrarEnfermedadesCatalogo() {
    const filtro = document.getElementById('filtroEnfermedades').value.toLowerCase();
    document.querySelectorAll('.enfermedad-item').forEach(item => {
        const texto = item.textContent.toLowerCase();
        item.style.display = texto.includes(filtro) ? 'block' : 'none';
    });
}

function agregarEnfermedadASolicitud(codigo, nombre) {
    if (!confirm(`¿Agregar "${nombre}" a la solicitud ${window.codigoEnvioActual} (Pos: ${window.posSolicitudActual})?`)) return;

    const codRef = document.getElementById('edadAves_display').value;
    //const fecToma = document.getElementById('fechaToma').value;

    const fd = new FormData();
    fd.append('action', 'agregar_enfermedad_solicitud');
    fd.append('codEnvio', window.codigoEnvioActual);
    fd.append('posSolicitud', window.posSolicitudActual); // ✅ Enviar posSolicitud actual
    fd.append('codAnalisis', codigo);
    fd.append('nomAnalisis', nombre);
    fd.append('codRef', codRef);
    fd.append('fecToma', fecTomaCuantiAux);

    // ✅ Guardar el nombre de la enfermedad que se está agregando
    const nombreEnfermedadNueva = nombre;
    const codigoEnfermedadNueva = codigo;

    fetch('crud-serologia.php', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('✅ Enfermedad agregada');
                    cerrarModalAgregarEnfermedad();

                    /* CODIGO ANTERIOR (no incluía posSolicitud en la consulta)
                    fetch(`crud-serologia.php?action=get_enfermedades&codEnvio=${window.codigoEnvioActual}`)
                    */
                    // ✅ Incluir posSolicitud en la consulta para obtener solo las enfermedades de esta solicitud
                    fetch(`crud-serologia.php?action=get_enfermedades&codEnvio=${window.codigoEnvioActual}&posSolicitud=${window.posSolicitudActual}`)
                        .then(r => r.text())
                        .then(t => {
                            try {
                                const dd = JSON.parse(t);
                                if (dd.success) {
                                    window.enfermedadesActuales = dd.enfermedades;

                                    // ✅ Inicializar el estado de la nueva enfermedad (vacío para poder guardar datos)
                                    dd.enfermedades.forEach(enf => {
                                        if (!window.enfermedadStates[enf.nombre]) {
                                            window.enfermedadStates[enf.nombre] = {};
                                        }
                                    });

                                    // ✅ Marcar la enfermedad como agregada recientemente (para mostrar X)
                                    if (!window.enfermedadesAgregadasReciente) {
                                        window.enfermedadesAgregadasReciente = {};
                                    }
                                    window.enfermedadesAgregadasReciente[nombreEnfermedadNueva] = codigoEnfermedadNueva;

                                    const tipo = document.getElementById('tipo_ave_hidden') ? document.getElementById('tipo_ave_hidden').value : 'BB';

                                    /* CODIGO ANTERIOR (solo renderizaba sin seleccionar la nueva)
                                    renderizarEnfermedades(tipo);
                                    const selectEnfermedad = document.getElementById('selectEnfermedad');
                                    if (selectEnfermedad) {
                                        const enfermedadesUnicas = [...new Set(dd.enfermedades.map(e => e.enfermedad || e.nombre))];
                                        const label = selectEnfermedad.parentElement?.querySelector('label');
                                        if (label) {
                                            label.textContent = `Seleccione Enfermedad (${enfermedadesUnicas.length} asignadas)`;
                                        }
                                    }
                                    */

                                    //  Seleccionar automáticamente la nueva enfermedad agregada
                                    // Primero renderizamos las enfermedades (esto pondrá la primera por defecto)
                                    renderizarEnfermedades(tipo);

                                    const selectEnfermedad = document.getElementById('selectEnfermedad');
                                    if (selectEnfermedad) {
                                        // Buscar la opción que coincida con el nombre de la enfermedad agregada
                                        const opciones = Array.from(selectEnfermedad.options);
                                        const opcionNueva = opciones.find(opt => opt.value === nombreEnfermedadNueva);

                                        if (opcionNueva) {
                                            // Cambiar el valor del select a la nueva enfermedad
                                            selectEnfermedad.value = nombreEnfermedadNueva;
                                            window.currentEnfermedadSelected = nombreEnfermedadNueva;

                                            // Disparar el evento change para que se renderice el panel correcto
                                            selectEnfermedad.dispatchEvent(new Event('change'));
                                        }

                                        const enfermedadesUnicas = [...new Set(dd.enfermedades.map(e => e.enfermedad || e.nombre))];
                                        const label = selectEnfermedad.parentElement?.querySelector('label');
                                        if (label) {
                                            label.textContent = `Seleccione Enfermedad (${enfermedadesUnicas.length} asignadas)`;
                                        }
                                    }
                                }
                            } catch (e) {
                                console.error('Error parseando respuesta:', e);
                            }
                        });
                } else {
                    alert('❌ Error: ' + data.message);
                }
            } catch (err) {
                console.error('Respuesta no JSON:', text);
                alert('Respuesta inesperada del servidor. Revisa la consola (F12).');
            }
        })
        .catch(e => {
            console.error('Error fetch:', e);
            alert('❌ Error de conexión: ' + e.message);
        });
}


// ============================================
// MANEJO DE ARCHIVOS
// ============================================
const inputPDFCuanti = document.getElementById('archivoPdfCuanti');
const fileListCuanti = document.getElementById('fileListCuanti');

const MAX_SIZECuanti = 10 * 1024 * 1024;
const allowedTypesCuanti = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'image/png',
    'image/jpeg'
];

function renderFilesCuanti() {
    if (!fileListCuanti) return;
    fileListCuanti.innerHTML = '';

    if (!inputPDFCuanti || inputPDFCuanti.files.length === 0) return;

    for (let file of inputPDFCuanti.files) {
        const div = document.createElement('div');
        div.className = 'flex justify-between items-center p-2 border rounded-md bg-gray-50';

        div.innerHTML = `
            <div>
                <p class="text-sm font-medium">${file.name}</p>
                <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
            </div>
            <button class="text-red-600 font-bold text-xl leading-none" onclick="removeFileCuanti('${file.name.replace(/'/g, "\\'")}')">×</button>
        `;

        fileListCuanti.appendChild(div);
    }
}

function removeFileCuanti(name) {
    if (!inputPDFCuanti) return;
    const dt = new DataTransfer();

    for (let file of inputPDFCuanti.files) {
        if (file.name !== name) dt.items.add(file);
    }

    inputPDFCuanti.files = dt.files;
    renderFilesCuanti();
}

if (inputPDFCuanti) {
    inputPDFCuanti.addEventListener('change', () => {
        const dt = new DataTransfer();

        for (let file of inputPDFCuanti.files) {
            if (!allowedTypes.includes(file.type)) {
                alert(`❌ Archivo no permitido: ${file.name}`);
                continue;
            }

            if (file.size > MAX_SIZE) {
                alert(`❌ ${file.name} pesa ${(file.size / 1024 / 1024).toFixed(2)}MB (máx. 10MB)`);
                continue;
            }

            dt.items.add(file);
        }

        inputPDFCuanti.files = dt.files;
        renderFilesCuanti();
    });

    renderFilesCuanti();
}