document.addEventListener("DOMContentLoaded", function () {
  // Filtro de búsqueda en reportes
  window.filterReportes = function filterReportes() {
    const input = document.getElementById("searchReportes");
    const filter = input.value.toUpperCase();
    const cards = document.querySelectorAll(".report-card");

    cards.forEach((card) => {
      const codigo = card.getAttribute("data-codigo");
      if (codigo && codigo.toUpperCase().includes(filter)) {
        card.style.display = "";
      } else {
        card.style.display = "none";
      }
    });
  };

  // Permitir buscar con Enter
  document
    .getElementById("searchReportes")
    ?.addEventListener("keyup", function (e) {
      if (e.key === "Enter") filterReportes();
    });
});

let paginaActual = 1;
let totalPaginas = 1;
let busquedaActual = "";
function cargarReportes(page = 1, query = "") {
  const contenedor = document.getElementById("reportes-lista");
  const paginacion = document.getElementById("paginacion-controles");

  contenedor.innerHTML =
    '<div class="text-center py-10 text-gray-600">Cargando reportes...</div>';

  const urlParams = new URLSearchParams();
  urlParams.append("page", page);
  if (query) urlParams.append("q", query);
  if (page === 1) urlParams.append("get_total", "1");

  fetch(`cargar_reportes.php?${urlParams.toString()}`)
    .then((response) => {
      if (!response.ok) throw new Error("Error al cargar");
      return response.text();
    })
    .then((html) => {
      // Extraer total de páginas si está presente
      const totalMatch = html.match(/<!--TOTAL_PAGES:(\d+)-->/);
      if (totalMatch) {
        totalPaginas = parseInt(totalMatch[1]);
        html = html.replace(/<!--TOTAL_PAGES:\d+-->/, "");
      }

      // ✅ Solo inserta el HTML sin modificar clases ni inyectar botones
      contenedor.innerHTML = html;

      paginaActual = page;
      busquedaActual = query;
      renderizarPaginacion();
    })
    .catch((err) => {
      contenedor.innerHTML =
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
    html += `<button class="bg-white text-gray-700 font-medium py-2 px-3 sm:px-4 rounded-md border border-gray-300 hover:bg-gray-50 transition-colors text-sm sm:text-base" onclick="cargarReportes(${
      paginaActual - 1
    }, \`${busquedaActual}\`)">← Anterior</button>`;
  }
  html += `<span class="text-gray-600 text-xs sm:text-sm whitespace-nowrap">Página ${paginaActual} de ${totalPaginas}</span>`;
  if (paginaActual < totalPaginas) {
    html += `<button class="bg-white text-gray-700 font-medium py-2 px-3 sm:px-4 rounded-md border border-gray-300 hover:bg-gray-50 transition-colors text-sm sm:text-base" onclick="cargarReportes(${
      paginaActual + 1
    }, \`${busquedaActual}\`)">Siguiente →</button>`;
  }
  paginacion.innerHTML = html;
}

// Filtro en tiempo real
document
  .getElementById("searchReportes")
  .addEventListener("input", function () {
    const query = this.value.trim();
    cargarReportes(1, query);
  });

// Cargar al iniciar
document.addEventListener("DOMContentLoaded", () => {
  cargarReportes(1, "");
});
let codigoReporteSeleccionado = null;

function abrirModalCorreo(codigo) {
  document.getElementById("codigoEnvio").value = codigo;
  document.getElementById("asuntoCorreo").value = `Reporte ${codigo}`;
  document.getElementById(
    "mensajeCorreo"
  ).value = `Estimado,\n\nAdjunto el reporte solicitado: ${codigo}.\n\nSaludos cordiales.`;
  document.getElementById("mensajeResultado").textContent = "";
  document.getElementById("otroCorreoCheck").checked = false;
  document.getElementById("otroCorreo").classList.add("hidden");
  document.getElementById("otroCorreo").disabled = true;
  cargarContactosParaSelect();
  document.getElementById("modalCorreo").classList.remove("hidden");
}
function cargarContactosParaSelect() {
  fetch("api_dashboard/obtener_contactos.php")
    .then((r) => r.json())
    .then((contactos) => {
      const select = document.getElementById("destinatarioSelect");
      select.innerHTML = '<option value="">Seleccione un contacto</option>';
      contactos.forEach((c) => {
        const opt = document.createElement("option");
        opt.value = c.correo;
        opt.textContent = `${c.contacto} (${c.correo})`;
        select.appendChild(opt);
      });
    });
}
document
  .getElementById("otroCorreoCheck")
  .addEventListener("change", function () {
    const otro = document.getElementById("otroCorreo");
    if (this.checked) {
      otro.classList.remove("hidden");
      otro.disabled = false;
      otro.value = "";
      document.getElementById("destinatarioSelect").value = "";
    } else {
      otro.classList.add("hidden");
      otro.disabled = true;
      document.getElementById("destinatarioSelect").value = "";
    }
  });

document
  .getElementById("destinatarioSelect")
  .addEventListener("change", function () {
    if (this.value) document.getElementById("otroCorreoCheck").checked = false;
  });

function enviarCorreoDesdeSistema() {
  const codigoEnvio = document.getElementById("codigoEnvio").value;
  let to = document.getElementById("destinatarioSelect").value;
  const otro = document.getElementById("otroCorreo").value.trim();
  const asunto = document.getElementById("asuntoCorreo").value.trim();
  const mensaje = document.getElementById("mensajeCorreo").value.trim();
  const resultado = document.getElementById("mensajeResultado");

  if (!codigoEnvio || (!to && !otro) || !asunto || !mensaje) {
    resultado.textContent = "Todos los campos son obligatorios.";
    resultado.className = "mt-2 text-sm text-center text-red-600";
    return;
  }

  if (otro && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(otro)) {
    resultado.textContent = "Correo inválido.";
    resultado.className = "mt-2 text-sm text-center text-red-600";
    return;
  }

  to = otro || to;

  resultado.textContent = "Enviando...";
  resultado.className = "mt-2 text-sm text-center text-blue-600";

  fetch("api_dashboard/enviar_correo_reporte.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `to=${encodeURIComponent(to)}&subject=${encodeURIComponent(
      asunto
    )}&body=${encodeURIComponent(mensaje)}&codigo=${encodeURIComponent(
      codigoEnvio
    )}`,
  })
    .then((r) => r.json())
    .then((data) => {
      resultado.className = data.success
        ? "mt-2 text-sm text-center text-green-600"
        : "mt-2 text-sm text-center text-red-600";
      resultado.textContent =
        data.message || (data.success ? "¡Correo enviado!" : "Error al enviar");
      if (data.success) setTimeout(cerrarModalCorreo, 1500);
    })
    .catch((err) => {
      resultado.className = "mt-2 text-sm text-center text-red-600";
      resultado.textContent = "Error de red";
      console.error(err);
    });
}
function cerrarModalCorreo() {
  document.getElementById("modalCorreo").classList.add("hidden");
}

function abrirClienteCorreo() {
  const to = document.getElementById("correoDestino").value.trim();
  const subject = encodeURIComponent(
    document.getElementById("asuntoCorreo").value.trim()
  );
  const body = encodeURIComponent(
    document.getElementById("mensajeCorreo").value.trim()
  );

  if (!to) {
    alert("Por favor ingresa un correo de destino.");
    return;
  }

  // Construir URL mailto:
  let url = `mailto:${to}?subject=${subject}&body=${body}`;

  // Abrir en nueva pestaña (lo que activa el cliente predeterminado)
  window.open(url, "_blank");

  cerrarModalCorreo();
}

function cerrarModalCorreo() {
  document.getElementById("modalCorreo").classList.add("hidden");
}

function confirmarEnviarCorreo() {
  const to = document.getElementById("correoTo").value.trim();
  const cc = document.getElementById("correoCc").value.trim();
  const cco = document.getElementById("correoCco").value.trim();
  const asunto = document.getElementById("correoAsunto").value.trim();
  const mensaje = document.getElementById("correoMensaje").value.trim();
  const adjuntos = document.getElementById("adjuntos").files;
  const mensajeEl = document.getElementById("mensajeCorreo");

  if (!to || !asunto || !mensaje) {
    mensajeEl.textContent =
      'Los campos "Para", "Asunto" y "Mensaje" son obligatorios.';
    mensajeEl.className = "mt-3 text-sm text-center text-red-600 font-medium";
    return;
  }

  if (
    !validateEmail(to) ||
    (cc && !validateEmail(cc)) ||
    (cco && !validateEmail(cco))
  ) {
    mensajeEl.textContent = "Uno o más correos electrónicos no son válidos.";
    mensajeEl.className = "mt-3 text-sm text-center text-red-600 font-medium";
    return;
  }

  // Preparar FormData
  const formData = new FormData();
  formData.append("to", to);
  if (cc) formData.append("cc", cc);
  if (cco) formData.append("cco", cco);
  formData.append("subject", asunto);
  formData.append("body", mensaje);

  // Adjuntar archivos
  for (let i = 0; i < adjuntos.length; i++) {
    formData.append("adjuntos[]", adjuntos[i]);
  }

  // Estado de carga
  mensajeEl.textContent = "Enviando correo...";
  mensajeEl.className = "mt-3 text-sm text-center text-blue-600 font-medium";

  fetch("api_dashboard/enviar_correo_reporte.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        mensajeEl.textContent = "¡Correo enviado correctamente!";
        mensajeEl.className =
          "mt-3 text-sm text-center text-green-600 font-medium";
        setTimeout(cerrarModalCorreo, 1500);
      } else {
        mensajeEl.textContent = data.message || "Error al enviar el correo.";
        mensajeEl.className =
          "mt-3 text-sm text-center text-red-600 font-medium";
      }
    })
    .catch((err) => {
      console.error(err);
      mensajeEl.textContent = "Error de conexión. Verifica tu red.";
      mensajeEl.className = "mt-3 text-sm text-center text-red-600 font-medium";
    });
}

// Validador simple de correo
function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
