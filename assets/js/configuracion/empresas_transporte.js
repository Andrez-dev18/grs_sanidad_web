// Vista Lista / Iconos
function aplicarVisibilidadVistaEmpTrans() {
    var wrapper = document.getElementById('tablaEmpTransWrapper');
    if (!wrapper) return;
    var vista = wrapper.getAttribute('data-vista') || 'tabla';
    var listaWrap = wrapper.querySelector('.view-lista-wrap');
    var tarjetasWrap = wrapper.querySelector('.view-tarjetas-wrap');
    var btnLista = document.getElementById('btnViewTablaEmpTrans');
    var btnIconos = document.getElementById('btnViewIconosEmpTrans');
    if (listaWrap) listaWrap.style.display = vista === 'tabla' ? 'block' : 'none';
    if (tarjetasWrap) tarjetasWrap.style.display = vista === 'iconos' ? 'block' : 'none';
    if (btnLista) btnLista.classList.toggle('active', vista === 'tabla');
    if (btnIconos) btnIconos.classList.toggle('active', vista === 'iconos');
}

function renderizarTarjetasEmpTrans() {
    var tbody = document.getElementById('empTransTableBody');
    var cont = document.getElementById('cardsContainerEmpTrans');
    if (!tbody || !cont) return;
    cont.innerHTML = '';
    var rows = tbody.querySelectorAll('tr[data-codigo][data-nombre]');
    rows.forEach(function(tr, i) {
        var codigo = tr.getAttribute('data-codigo');
        var nombre = tr.getAttribute('data-nombre') || '';
        var idx = (tr.getAttribute('data-index') || (i + 1));
        var nomEsc = nombre.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        var card = document.createElement('div');
        card.className = 'card-item';
        card.setAttribute('data-codigo', codigo);
        card.setAttribute('data-nombre', nombre);
        var codEsc = (codigo + '').replace(/</g, '&lt;').replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        card.innerHTML = '<div class="card-numero-row">#' + idx + '</div>' +
            '<div class="card-row"><span class="label">codigo:</span> <span>' + codEsc + '</span></div>' +
            '<div class="card-row"><span class="label">Nombre:</span> <span>' + nomEsc + '</span></div>' +
            '<div class="card-acciones">' +
            '<button type="button" class="btn-editar-card-emptrans p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar"><i class="fa-solid fa-edit"></i></button>' +
            '<button type="button" class="btn-eliminar-card-emptrans p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" data-codigo="' + codigo + '"><i class="fa-solid fa-trash"></i></button>' +
            '</div>';
        cont.appendChild(card);
    });
}

function initVistaEmpTrans() {
    var wrapper = document.getElementById('tablaEmpTransWrapper');
    if (!wrapper) return;
    // En pantallas pequeñas por defecto mostrar iconos; en escritorio, lista
    var vistaInicial = window.innerWidth < 768 ? 'iconos' : 'tabla';
    wrapper.setAttribute('data-vista', vistaInicial);
    renderizarTarjetasEmpTrans();
    aplicarVisibilidadVistaEmpTrans();
}

document.addEventListener('DOMContentLoaded', function() {
    var btnLista = document.getElementById('btnViewTablaEmpTrans');
    var btnIconos = document.getElementById('btnViewIconosEmpTrans');
    var wrapper = document.getElementById('tablaEmpTransWrapper');
    if (btnLista) {
        btnLista.addEventListener('click', function() {
            if (wrapper) wrapper.setAttribute('data-vista', 'tabla');
            aplicarVisibilidadVistaEmpTrans();
        });
    }
    if (btnIconos) {
        btnIconos.addEventListener('click', function() {
            if (wrapper) wrapper.setAttribute('data-vista', 'iconos');
            renderizarTarjetasEmpTrans();
            aplicarVisibilidadVistaEmpTrans();
        });
    }
    initVistaEmpTrans();
    document.addEventListener('click', function(e) {
        var ed = e.target.closest('.btn-editar-card-emptrans');
        var el = e.target.closest('.btn-eliminar-card-emptrans');
        if (ed) {
            var card = ed.closest('.card-item');
            if (card) openModal('edit', parseInt(card.getAttribute('data-codigo'), 10), card.getAttribute('data-nombre') || '');
        }
        if (el) {
            var cod = el.getAttribute('data-codigo');
            if (cod) confirmDelete(parseInt(cod, 10));
        }
    });
});

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
        if (typeof SwalAlert === 'function') SwalAlert('El nombre es obligatorio.', 'warning'); else alert('⚠️ El nombre es obligatorio.');
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
            if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); }); else { alert(data.message); location.reload(); }
        } else {
            if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error'); else alert('❌ ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        if (typeof SwalAlert === 'function') SwalAlert('Error al guardar la empresa de transporte.', 'error'); else alert('Error al guardar la empresa de transporte.');
    });

    return false;
}

function confirmDelete(codigo) {
    var msg = '¿Está seguro de eliminar esta empresa de transporte? Esta acción no se puede deshacer.';
    var doDelete = typeof SwalConfirm === 'function'
        ? SwalConfirm(msg, 'Confirmar eliminación')
        : Promise.resolve(confirm(msg));
    doDelete.then(function(confirmed) {
        if (!confirmed) return;
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
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); }); else { alert(data.message); location.reload(); }
            } else {
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error'); else alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            if (typeof SwalAlert === 'function') SwalAlert('Error al eliminar la empresa.', 'error'); else alert('Error al eliminar la empresa.');
        });
    });
}

// Función para exportar a Excel
function exportarEmpresasTransporte() {
    // Obtener la ruta base del módulo actual
    const currentPath = window.location.pathname;
    const modulePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
    window.location.href = 'exportar_empresas_transporte.php';
}
