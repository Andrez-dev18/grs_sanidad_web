
function openModal(action, codigo = null, nombre = '') {
    const modal = document.getElementById('empTransModal');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('empTransForm');

    if (action === 'create') {
        title.textContent = '➕ Nueva Empresa de Transporte';
        document.getElementById('modalAction').value = 'create';
        document.getElementById('editCodigo').value = '';
        document.getElementById('modalNombre').value = '';
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Empresa de Transporte';
        document.getElementById('modalAction').value = 'update';
        document.getElementById('editCodigo').value = codigo;
        document.getElementById('modalNombre').value = nombre;
    }

    modal.style.display = 'flex';
}

function closeEmpTransModal() {
    document.getElementById('empTransModal').style.display = 'none';
}

function saveEmpTrans(event) {
    event.preventDefault();
    const action = document.getElementById('modalAction').value;
    const nombre = document.getElementById('modalNombre').value.trim();
    const codigo = document.getElementById('editCodigo').value;

    if (!nombre) {
        alert('⚠️ El nombre es obligatorio.');
        return false;
    }

    const params = { action, nombre };
    if (action === 'update') params.codigo = codigo;

    fetch('crud_emp_trans.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error al guardar la empresa de transporte.');
    });

    return false;
}

function confirmDelete(codigo) {
    if (confirm('¿Está seguro de eliminar esta empresa de transporte? Esta acción no se puede deshacer.')) {
        fetch('crud_emp_trans.php', {
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
                alert(data.message);
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al eliminar la empresa.');
        });
    }
}

// Función para exportar a Excel
function exportarEmpresasTransporte() {
    // Obtener la ruta base del módulo actual
    const currentPath = window.location.pathname;
    const modulePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
    window.location.href = modulePath + '/exportar_empresas_transporte.php';
}
