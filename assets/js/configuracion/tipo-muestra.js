
function openTipoMuestraModal(action, codigo = null, nombre = '', descripcion = '', longitud = 8) {
    const modal = document.getElementById('tipoMuestraModal');
    const title = document.getElementById('tipoMuestraModalTitle');
    const form = document.getElementById('tipoMuestraForm');

    if (action === 'create') {
        title.textContent = '➕ Nuevo Tipo de Muestra';
        document.getElementById('tipoMuestraModalAction').value = 'create';
        document.getElementById('tipoMuestraEditCodigo').value = '';
        document.getElementById('tipoMuestraModalNombre').value = '';
        document.getElementById('tipoMuestraModalDescripcion').value = '';
        document.getElementById('tipoMuestraModalLongitud').value = 8;
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Tipo de Muestra';
        document.getElementById('tipoMuestraModalAction').value = 'update';
        document.getElementById('tipoMuestraEditCodigo').value = codigo;
        document.getElementById('tipoMuestraModalNombre').value = nombre;
        document.getElementById('tipoMuestraModalDescripcion').value = descripcion;
        document.getElementById('tipoMuestraModalLongitud').value = longitud;
    }

    modal.style.display = 'flex';
}

function closeTipoMuestraModal() {
    document.getElementById('tipoMuestraModal').style.display = 'none';
}

function saveTipoMuestra(event) {
    event.preventDefault();
    const action = document.getElementById('tipoMuestraModalAction').value;
    const nombre = document.getElementById('tipoMuestraModalNombre').value.trim();
    const descripcion = document.getElementById('tipoMuestraModalDescripcion').value.trim();
    const longitud = document.getElementById('tipoMuestraModalLongitud').value;
    const codigo = document.getElementById('tipoMuestraEditCodigo').value;

    if (!nombre) {
        if (typeof SwalAlert === 'function') SwalAlert('El nombre es obligatorio.', 'warning'); else alert('⚠️ El nombre es obligatorio.');
        return false;
    }

    if (!longitud || longitud < 1) {
        if (typeof SwalAlert === 'function') SwalAlert('La longitud de código debe ser mayor que 1.', 'warning'); else alert('⚠️ La longitud de código debe ser mayor que 1.');
        return false;
    }

    const params = { action, nombre, descripcion, lonCod: longitud };
    if (action === 'update') params.codigo = codigo;

    fetch('crud_tipo_muestra.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); }); else { alert(data.message); location.reload(); }
        } else {
            if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error'); else alert('❌ ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        if (typeof SwalAlert === 'function') SwalAlert('Error al guardar.', 'error'); else alert('Error al guardar.');
    });

    return false;
}

function confirmTipoMuestraDelete(codigo) {
    var msg = '¿Está seguro de eliminar este tipo de muestra? Esta acción no se puede deshacer y puede afectar a otros registros relacionados.';
    var doDelete = typeof SwalConfirm === 'function'
        ? SwalConfirm(msg, 'Confirmar eliminación')
        : Promise.resolve(confirm(msg));
    doDelete.then(function(confirmed) {
        if (!confirmed) return;
        fetch('crud_tipo_muestra.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'delete',
                codigo: codigo
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); }); else { alert(data.message); location.reload(); }
            } else {
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error'); else alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            if (typeof SwalAlert === 'function') SwalAlert('Error al eliminar.', 'error'); else alert('Error al eliminar.');
        });
    });
}

// Función para exportar a Excel
function exportarTiposMuestra() {
    // Obtener la ruta base del módulo actual
    const currentPath = window.location.pathname;
    const modulePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
    window.location.href = modulePath + '/exportar_tipo_muestra.php';
}
