function logout() {
  var msg = "¿Desea cerrar la sesión?";
  var prom = (typeof SwalConfirm === 'function') ? SwalConfirm(msg, 'Cerrar sesión') : Promise.resolve(confirm(msg));
  prom.then(function(ok) {
    if (ok) window.location.href = "logout.php";
  });
}