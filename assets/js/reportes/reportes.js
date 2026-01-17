// =============== VARIABLES GLOBALES ===============
let archivosAcumulados = [];
let destinatariosPara = []; // Solo usamos este array

// =============== CARGA INICIAL ===============
document.addEventListener("DOMContentLoaded", (e) => {
  // Este JS se usa en distintas vistas. Solo cargar el listado "card-based" si existe el contenedor.
  if (document.getElementById("reportes-lista")) {
    cargarReportes(1, "");
  }
});
// === MANEJO DE EVENTOS (DELEGACIÓN) ===
document.addEventListener("click", function (e) {
  // Botón "Enviar por correo"
  if (e.target.closest('.btn-enviar-correo')) {
    const boton = e.target.closest('.btn-enviar-correo');
    const codigo = boton.dataset.codigo;
    if (codigo) {
      abrirModalCorreo(codigo);
    }
  }
});
// =============== FILTRO DE BÚSQUEDA ===============
function filterReportes() {
  const input = document.getElementById("searchReportes");
  const filter = input.value.toUpperCase();
  const cards = document.querySelectorAll(".report-card");
  cards.forEach(card => {
    const codigo = card.getAttribute("data-codigo");
    card.style.display = codigo && codigo.toUpperCase().includes(filter) ? "" : "none";
  });
}

document.getElementById("searchReportes")?.addEventListener("keyup", e => {
  if (e.key === "Enter") filterReportes();
});

document.getElementById("searchReportes")?.addEventListener("input", () => {
  const query = this.value.trim();
  cargarReportes(1, query);
});

// =============== PAGINACIÓN ===============
let paginaActual = 1;
let totalPaginas = 1;
let busquedaActual = "";

function cargarReportes(page = 1, query = "") {
  const contenedor = document.getElementById("reportes-lista");
  contenedor.innerHTML = '<div class="text-center py-10 text-gray-600">Cargando reportes...</div>';

  const urlParams = new URLSearchParams({ page });
  if (query) urlParams.append("q", query);
  if (page === 1) urlParams.append("get_total", "1");

  fetch(`cargar_reportes.php?${urlParams.toString()}`)
    .then(response => {
      if (!response.ok) throw new Error("Error al cargar");
      return response.text();
    })
    .then(html => {
      const totalMatch = html.match(/<!--TOTAL_PAGES:(\d+)-->/);
      if (totalMatch) {
        totalPaginas = parseInt(totalMatch[1]);
        html = html.replace(/<!--TOTAL_PAGES:\d+-->/, "");
      }
      document.getElementById("reportes-lista").innerHTML = html;
      paginaActual = page;
      busquedaActual = query;
      renderizarPaginacion();
    })
    .catch(err => {
      document.getElementById("reportes-lista").innerHTML =
        '<div class="text-center py-10 text-red-600">Error al cargar reportes.</div>';
      console.error(err);
    });
}

function renderizarPaginacion() {
  const paginacion = document.getElementById("paginacion-controles");
  if (totalPaginas <= 1) {
    paginacion.innerHTML = "";
    return;
  }

  let html = "";
  if (paginaActual > 1) {
    html += `<button class="bg-white text-gray-700 font-medium py-2 px-3 sm:px-4 rounded-md border border-gray-300 hover:bg-gray-50 transition-colors text-sm sm:text-base"
              onclick="cargarReportes(${paginaActual - 1}, \`${busquedaActual}\`)">← Anterior</button>`;
  }
  html += `<span class="text-gray-600 text-xs sm:text-sm whitespace-nowrap">Página ${paginaActual} de ${totalPaginas}</span>`;
  if (paginaActual < totalPaginas) {
    html += `<button class="bg-white text-gray-700 font-medium py-2 px-3 sm:px-4 rounded-md border border-gray-300 hover:bg-gray-50 transition-colors text-sm sm:text-base"
              onclick="cargarReportes(${paginaActual + 1}, \`${busquedaActual}\`)">Siguiente →</button>`;
  }
  paginacion.innerHTML = html;
}

// =============== MODAL DE ENVÍO ===============
function abrirModalCorreo(codigo) {
  document.getElementById("codigoEnvio").value = codigo;
  document.getElementById("asuntoCorreo").value = `Reporte ${codigo}`;
  document.getElementById("mensajeCorreo").value = `Estimado,\n\nAdjunto el reporte solicitado: ${codigo}.\n\nSaludos cordiales.`;
  document.getElementById("mensajeResultado").textContent = "";

  // Reiniciar
  archivosAcumulados = [];
  destinatariosPara = [];

  // Actualizar UI
  actualizarListaArchivos(codigo);
  renderDestinatariosPara();

  // Cargar contactos
  cargarContactosEnSelect();

  // Evento de selección de archivos
  document.getElementById("archivosAdjuntos").addEventListener("change", function () {
    const nuevos = Array.from(this.files);
    archivosAcumulados.push(...nuevos);
    actualizarListaArchivos(codigo);
    this.value = "";
  });

  // Mostrar modal
  new bootstrap.Modal(document.getElementById("modalCorreo")).show();
}

// =============== DESTINATARIOS ("Para") ===============
function cargarContactosEnSelect() {
  const select = document.getElementById("destinatarioSelect");
  select.innerHTML = '<option>Cargando...</option>';

  fetch("obtener_contactos.php")
    .then(r => r.json())
    .then(contactos => {
      select.innerHTML = "";
      contactos.forEach(c => {
        const option = document.createElement("option");
        option.value = c.correo;
        option.textContent = `${c.contacto} (${c.correo})`;
        if (destinatariosPara.includes(c.correo)) {
          option.disabled = true;
          option.selected = true;
        }
        select.appendChild(option);
      });
    })
    .catch(err => {
      select.innerHTML = '<option>Error al cargar contactos</option>';
      console.error(err);
    });
}

document.getElementById("btnMostrarSelect").addEventListener("click", () => {
  const select = document.getElementById("destinatarioSelect");
  select.style.display = select.style.display === "block" ? "none" : "block";
  if (select.style.display === "block") cargarContactosEnSelect();
});

// Detectar cambios en el <select> y ocultarlo tras seleccionar
document.getElementById("destinatarioSelect").addEventListener("change", function () {
  const select = this;
  const opciones = Array.from(select.selectedOptions);

  // Actualizar array de destinatarios
  destinatariosPara = opciones.map(opt => opt.value);

  // Actualizar vista
  renderDestinatariosPara();

  // Recargar el select para deshabilitar los ya elegidos
  cargarContactosEnSelect();

  
  select.style.display = "none";
});
function renderDestinatariosPara() {
  const cont = document.getElementById("listaPara");
  cont.innerHTML = "";
  destinatariosPara.forEach((correo, i) => {
    const badge = document.createElement("span");
    badge.className = "badge bg-primary me-2 mb-1 d-inline-flex align-items-center";
    badge.innerHTML = `
      ${correo}
      <i class="fas fa-times ms-1" style="cursor:pointer;font-size:0.85em;" 
         onclick="eliminarDestinatario(${i})"></i>
    `;
    cont.appendChild(badge);
  });
}

function eliminarDestinatario(index) {
  destinatariosPara.splice(index, 1);
  renderDestinatariosPara();
  cargarContactosEnSelect(); // Reactivar en el select
}

// =============== ARCHIVOS ADJUNTOS ===============
function getIconoArchivo(nombre) {
  const ext = nombre.split('.').pop().toLowerCase();
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) return 'fa-file-image text-success';
  if (['pdf'].includes(ext)) return 'fa-file-pdf text-danger';
  if (['doc', 'docx', 'txt'].includes(ext)) return 'fa-file-word text-blue';
  if (['xls', 'xlsx', 'csv'].includes(ext)) return 'fa-file-excel text-green';
  if (['zip', 'rar', '7z'].includes(ext)) return 'fa-file-archive text-warning';
  return 'fa-file text-secondary';
}

function actualizarListaArchivos(codigo) {
  const cont = document.getElementById("listaArchivos");
  cont.innerHTML = '';

  // PDF por defecto
  const pdf = document.createElement("div");
  pdf.className = "d-flex justify-content-between align-items-center bg-primary bg-opacity-10 p-2 rounded mb-2";
  pdf.innerHTML = `
    <div class="d-flex align-items-center gap-2">
      <i class="fas fa-file-pdf text-danger"></i>
      <span>Reporte_${codigo}.pdf</span>
    </div>
    <a href="generar_pdf_tabla.php?codigo=${encodeURIComponent(codigo)}" target="_blank" class="text-primary">
      <i class="fas fa-eye"></i>
    </a>
  `;
  cont.appendChild(pdf);

  // Archivos acumulados
  archivosAcumulados.forEach((file, i) => {
    const item = document.createElement("div");
    item.className = "d-flex justify-content-between align-items-center bg-light p-2 rounded mb-2";
    item.innerHTML = `
      <div class="d-flex align-items-center gap-2">
        <i class="fas ${getIconoArchivo(file.name)}"></i>
        <span>${file.name}</span>
      </div>
      <i class="fas fa-times text-danger" style="cursor:pointer;" onclick="eliminarArchivo(${i})"></i>
    `;
    cont.appendChild(item);
  });
}

function eliminarArchivo(index) {
  archivosAcumulados.splice(index, 1);
  const codigo = document.getElementById("codigoEnvio").value;
  actualizarListaArchivos(codigo);
}

// =============== ENVÍO ===============
function enviarCorreoDesdeSistema() {
  const codigo = document.getElementById("codigoEnvio").value;
  const asunto = document.getElementById("asuntoCorreo").value.trim();
  const mensaje = document.getElementById("mensajeCorreo").value.trim();
  const resultado = document.getElementById("mensajeResultado");

  if (!codigo || destinatariosPara.length === 0 || !asunto || !mensaje) {
    resultado.textContent = "Todos los campos son obligatorios. Debe seleccionar al menos un destinatario.";
    resultado.className = "text-danger small";
    return;
  }

  resultado.textContent = "Enviando...";
  resultado.className = "text-primary small";

  const formData = new FormData();
  destinatariosPara.forEach(email => formData.append("para[]", email));
  formData.append("subject", asunto);
  formData.append("body", mensaje);
  formData.append("codigo", codigo);
  archivosAcumulados.forEach(file => formData.append("archivos_adjuntos[]", file));

  fetch("enviar_correo_reporte.php", { method: "POST", body: formData })
    .then(r => r.json())
    .then(data => {
      resultado.className = data.success ? "text-success small" : "text-danger small";
      resultado.textContent = data.message || (data.success ? "¡Correo enviado!" : "Error al enviar.");
      if (data.success) setTimeout(cerrarModalCorreo, 1500);
    })
    .catch(err => {
      resultado.className = "text-danger small";
      resultado.textContent = "Error de red.";
      console.error(err);
    });
}

function cerrarModalCorreo() {
  bootstrap.Modal.getInstance(document.getElementById("modalCorreo"))?.hide();
}

// =============== UTILIDADES ===============
function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
