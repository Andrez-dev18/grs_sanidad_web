// Vista Lista / Iconos - Enfermedades
function aplicarVisibilidadVistaEnfermedades() {
    var wrapper = document.getElementById('tablaEnfermedadesWrapper');
    if (!wrapper) return;
    var vista = wrapper.getAttribute('data-vista') || 'tabla';
    var listaWrap = wrapper.querySelector('.view-lista-wrap');
    var tarjetasWrap = wrapper.querySelector('.view-tarjetas-wrap');
    var btnLista = document.getElementById('btnViewTablaEnfermedades');
    var btnIconos = document.getElementById('btnViewIconosEnfermedades');
    if (listaWrap) listaWrap.style.display = vista === 'tabla' ? 'block' : 'none';
    if (tarjetasWrap) tarjetasWrap.style.display = vista === 'iconos' ? 'block' : 'none';
    if (btnLista) btnLista.classList.toggle('active', vista === 'tabla');
    if (btnIconos) btnIconos.classList.toggle('active', vista === 'iconos');
}

function renderizarTarjetasEnfermedades() {
    var tbody = document.getElementById('enfermedadesTableBody');
    var cont = document.getElementById('cardsContainerEnfermedades');
    if (!tbody || !cont) return;
    cont.innerHTML = '';
    var rows = tbody.querySelectorAll('tr[data-codigo][data-nombre]');
    rows.forEach(function(tr, i) {
        var codigo = tr.getAttribute('data-codigo');
        var nombre = tr.getAttribute('data-nombre') || '';
        var idx = tr.getAttribute('data-index') || (i + 1);
        var nomEsc = nombre.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        var card = document.createElement('div');
        card.className = 'card-item';
        card.setAttribute('data-codigo', codigo);
        card.setAttribute('data-nombre', nombre);
        card.innerHTML = '<div class="card-numero-row">N° ' + idx + '</div>' +
            '<div class="card-row"><span class="label">Nombre:</span> <span>' + nomEsc + '</span></div>' +
            '<div class="card-acciones">' +
            '<button type="button" class="btn-editar-card-enfermedad p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar"><i class="fa-solid fa-edit"></i></button>' +
            '<button type="button" class="btn-eliminar-card-enfermedad p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" data-codigo="' + codigo + '"><i class="fa-solid fa-trash"></i></button>' +
            '</div>';
        cont.appendChild(card);
    });
}

function initVistaEnfermedades() {
    var wrapper = document.getElementById('tablaEnfermedadesWrapper');
    if (!wrapper) return;
    var vistaInicial = window.innerWidth < 768 ? 'iconos' : 'tabla';
    wrapper.setAttribute('data-vista', vistaInicial);
    renderizarTarjetasEnfermedades();
    aplicarVisibilidadVistaEnfermedades();
}

document.addEventListener('DOMContentLoaded', function() {
    var btnLista = document.getElementById('btnViewTablaEnfermedades');
    var btnIconos = document.getElementById('btnViewIconosEnfermedades');
    var wrapper = document.getElementById('tablaEnfermedadesWrapper');
    if (btnLista) {
        btnLista.addEventListener('click', function() {
            if (wrapper) wrapper.setAttribute('data-vista', 'tabla');
            aplicarVisibilidadVistaEnfermedades();
        });
    }
    if (btnIconos) {
        btnIconos.addEventListener('click', function() {
            if (wrapper) wrapper.setAttribute('data-vista', 'iconos');
            renderizarTarjetasEnfermedades();
            aplicarVisibilidadVistaEnfermedades();
        });
    }
    initVistaEnfermedades();
    document.addEventListener('click', function(e) {
        var ed = e.target.closest('.btn-editar-card-enfermedad');
        var el = e.target.closest('.btn-eliminar-card-enfermedad');
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

function openModal(action, codigo, nombre) {
    codigo = codigo !== undefined && codigo !== null ? codigo : null;
    nombre = nombre !== undefined ? nombre : '';
    var modal = document.getElementById('enfermedadesModal');
    var title = document.getElementById('modalTitleEnfermedades');
    if (action === 'create') {
        title.textContent = '➕ Nueva Enfermedad';
        document.getElementById('modalActionEnfermedades').value = 'create';
        document.getElementById('editCodEnf').value = '';
        document.getElementById('modalNombreEnfermedad').value = '';
    } else {
        title.textContent = '✏️ Editar Enfermedad';
        document.getElementById('modalActionEnfermedades').value = 'update';
        document.getElementById('editCodEnf').value = codigo;
        document.getElementById('modalNombreEnfermedad').value = nombre;
    }
    modal.style.display = 'flex';
}

function closeEnfermedadesModal() {
    document.getElementById('enfermedadesModal').style.display = 'none';
}

function saveEnfermedad(event) {
    event.preventDefault();
    var action = document.getElementById('modalActionEnfermedades').value;
    var nombre = document.getElementById('modalNombreEnfermedad').value.trim();
    var codigo = document.getElementById('editCodEnf').value;

    if (!nombre) {
        if (typeof SwalAlert === 'function') SwalAlert('El nombre es obligatorio.', 'warning');
        else alert('⚠️ El nombre es obligatorio.');
        return false;
    }

    var params = { action: action, nom_enf: nombre };
    if (action === 'update') params.cod_enf = codigo;

    fetch('crud_enfermedades.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); });
            else { alert(data.message); location.reload(); }
        } else {
            if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error');
            else alert('❌ ' + data.message);
        }
    })
    .catch(function(err) {
        console.error(err);
        if (typeof SwalAlert === 'function') SwalAlert('Error al guardar la enfermedad.', 'error');
        else alert('Error al guardar la enfermedad.');
    });
    return false;
}

function confirmDelete(codigo) {
    var msg = '¿Está seguro de eliminar esta enfermedad? Esta acción no se puede deshacer.';
    var doDelete = typeof SwalConfirm === 'function' ? SwalConfirm(msg, 'Confirmar eliminación') : Promise.resolve(confirm(msg));
    doDelete.then(function(confirmed) {
        if (!confirmed) return;
        fetch('crud_enfermedades.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'delete', cod_enf: codigo })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); });
                else { alert(data.message); location.reload(); }
            } else {
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error');
                else alert('❌ ' + data.message);
            }
        })
        .catch(function(err) {
            console.error(err);
            if (typeof SwalAlert === 'function') SwalAlert('Error al eliminar la enfermedad.', 'error');
            else alert('Error al eliminar la enfermedad.');
        });
    });
}
