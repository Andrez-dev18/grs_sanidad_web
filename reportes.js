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
