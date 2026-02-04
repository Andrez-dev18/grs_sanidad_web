function logout() {
    /*document.getElementById('dashboard').classList.remove('active');
            document.getElementById('loginScreen').style.display = 'flex';*/
    if (confirm("¿Desea cerrar la sesión?")) {
      window.location.href = "logout.php";
    }
  }