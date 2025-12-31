
// Abrir modal
window.openAnalisisModal = function openAnalisisModal(action, codigo = '', nombre = '', enfermedad = '') {
    document.getElementById('analisisModalAction').value = action;
    document.getElementById('analisisEditCodigo').value = codigo;
    document.getElementById('analisisModalNombre').value = nombre;
    document.getElementById('analisisModalEnfermedad').value = enfermedad || '';
    document.getElementById('analisisModalTitle').textContent = action === 'create' ? '➕ Nuevo Análisis' : '✏️ Editar Análisis';
    document.getElementById('analisisModal').style.display = 'flex';
}

// Cerrar modal
window.closeAnalisisModal = function closeAnalisisModal() {
    document.getElementById('analisisModal').style.display = 'none';
}

// Guardar análisis
document.getElementById('analisisForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const action = document.getElementById('analisisModalAction').value;
    const nombre = document.getElementById('analisisModalNombre').value.trim();
    const enfermedad = document.getElementById('analisisModalEnfermedad').value.trim();
    const codigo = document.getElementById('analisisEditCodigo').value;

    if (!nombre) {
        alert('⚠️ El nombre del análisis es obligatorio.');
        return;
    }

    const params = { action, nombre, enfermedad };
    if (action === 'update') params.codigo = codigo;

    const btn = document.querySelector('.btn-primary');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    fetch('crud_analisis.php', {
        method: 'POST',
        body: new URLSearchParams(params)
    })
    .then(r => r.json())
    .then(d => {
        btn.innerHTML = orig;
        btn.disabled = false;
        if (d.success) {
            alert('✅ ' + d.message);
            location.reload();
        } else {
            alert('❌ ' + d.message);
        }
    })
    .catch(err => {
        btn.innerHTML = orig;
        btn.disabled = false;
        alert('Error: ' + err.message);
    });
});

// Cerrar modal al hacer clic fuera
window.onclick = function(e) {
    const modal = document.getElementById('analisisModal');
    if (modal && e.target === modal) closeAnalisisModal();
};