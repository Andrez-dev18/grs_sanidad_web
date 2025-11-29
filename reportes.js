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
  }

  // Permitir buscar con Enter
  document
    .getElementById("searchReportes")
    ?.addEventListener("keyup", function (e) {
      if (e.key === "Enter") filterReportes();
    });
});


let paginaActual = 1;
let totalPaginas = 1;
let busquedaActual = '';

function cargarReportes(page = 1, query = '') {
  const contenedor = document.getElementById('reportes-lista');
  const paginacion = document.getElementById('paginacion-controles');

  contenedor.innerHTML = '<div class="text-center py-10 text-gray-600">Cargando reportes...</div>';

  const urlParams = new URLSearchParams();
  urlParams.append('page', page);
  if (query) urlParams.append('q', query);
  if (page === 1) urlParams.append('get_total', '1');

  fetch(`cargar_reportes.php?${urlParams.toString()}`)
    .then(response => {
      if (!response.ok) throw new Error('Error al cargar');
      return response.text();
    })
    .then(html => {
      // Extraer total de páginas si está presente
      const totalMatch = html.match(/<!--TOTAL_PAGES:(\d+)-->/);
      if (totalMatch) {
        totalPaginas = parseInt(totalMatch[1]);
        html = html.replace(/<!--TOTAL_PAGES:\d+-->/, '');
      }

      // Procesar HTML con Tailwind
      const processedHtml = html.replace(/class="report-card"/g, 'class="bg-white border-2 border-blue-600 rounded-xl p-4 sm:p-6 hover:shadow-lg transition-shadow"')
        .replace(/class="report-header"/g, 'class="mb-4 sm:mb-5 pb-3 sm:pb-4 border-b border-gray-200"')
        .replace(/class="report-code"/g, 'class="text-base sm:text-lg font-bold text-gray-900"')
        .replace(/class="report-grid"/g, 'class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-4 sm:mb-5"')
        .replace(/class="report-info-item"/g, 'class="flex flex-col"')
        .replace(/class="report-label"/g, 'class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1 sm:mb-1.5"')
        .replace(/class="report-value"/g, 'class="text-sm font-medium text-gray-900"')
        .replace(/class="report-actions"/g, 'class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4"')
        .replace(/class="btn-download-pdf"/g, 'class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 sm:py-3 px-3 sm:px-4 rounded-md transition-colors flex items-center justify-center gap-2 text-sm sm:text-base"');

      contenedor.innerHTML = processedHtml;
      paginaActual = page;
      busquedaActual = query;
      renderizarPaginacion();
    })
    .catch(err => {
      contenedor.innerHTML = '<div class="text-center py-10 text-red-600">Error al cargar reportes.</div>';
      console.error(err);
    });
}

function renderizarPaginacion() {
  const paginacion = document.getElementById('paginacion-controles');
  if (totalPaginas <= 1) {
    paginacion.innerHTML = '';
    return;
  }

  let html = '';
  if (paginaActual > 1) {
    html += `<button class="bg-white text-gray-700 font-medium py-2 px-3 sm:px-4 rounded-md border border-gray-300 hover:bg-gray-50 transition-colors text-sm sm:text-base" onclick="cargarReportes(${paginaActual - 1}, \`${busquedaActual}\`)">← Anterior</button>`;
  }
  html += `<span class="text-gray-600 text-xs sm:text-sm whitespace-nowrap">Página ${paginaActual} de ${totalPaginas}</span>`;
  if (paginaActual < totalPaginas) {
    html += `<button class="bg-white text-gray-700 font-medium py-2 px-3 sm:px-4 rounded-md border border-gray-300 hover:bg-gray-50 transition-colors text-sm sm:text-base" onclick="cargarReportes(${paginaActual + 1}, \`${busquedaActual}\`)">Siguiente →</button>`;
  }
  paginacion.innerHTML = html;
}

// Filtro en tiempo real
document.getElementById('searchReportes').addEventListener('input', function () {
  const query = this.value.trim();
  cargarReportes(1, query);
});

// Cargar al iniciar
document.addEventListener('DOMContentLoaded', () => {
  cargarReportes(1, '');
});

