document.addEventListener("DOMContentLoaded", function () {
  window.toggleSubmenu = function toggleSubmenu(menuId) {
    const menu = document.getElementById(menuId);
    const submenu = document.getElementById(menuId.replace("Menu", "Submenu"));
    menu.classList.toggle("expanded");
    submenu.classList.toggle("open");
  };
  window.showView = function showView(viewId) {
    document
      .querySelectorAll(".content-view")
      .forEach((el) => el.classList.remove("active"));

    document
      .getElementById("view" + viewId.charAt(0).toUpperCase() + viewId.slice(1))
      .classList.add("active");

    document
      .querySelectorAll(".menu-item, .submenu-item")
      .forEach((el) => el.classList.remove("active"));

    if (viewId === "registro") {
      const capturaMenu = document.getElementById("capturaMenu");
      const capturaSubmenu = document.getElementById("capturaSubmenu");
      capturaMenu.classList.add("expanded");
      capturaSubmenu.classList.add("open");

      const registroItem = capturaSubmenu.querySelector(".submenu-item");
      if (registroItem) registroItem.classList.add("active");
    } else {
      const menuItem = document.querySelector(
        `.menu-item[onclick="showView('${viewId}')"]`
      );
      if (menuItem) menuItem.classList.add("active");
    }
  };
});
