function aplicarVisibilidadVistaTipoProg() {
    var wrapper = document.getElementById('tablaTipoProgramaWrapper');
    if (!wrapper) return;
    var vista = wrapper.getAttribute('data-vista') || 'tabla';
    var listaWrap = wrapper.querySelector('.view-lista-wrap');
    var tarjetasWrap = wrapper.querySelector('.view-tarjetas-wrap');
    var btnLista = document.getElementById('btnViewTablaTipoProg');
    var btnIconos = document.getElementById('btnViewIconosTipoProg');
    if (listaWrap) listaWrap.style.display = vista === 'tabla' ? 'block' : 'none';
    if (tarjetasWrap) tarjetasWrap.style.display = vista === 'iconos' ? 'block' : 'none';
    if (btnLista) btnLista.classList.toggle('active', vista === 'tabla');
    if (btnIconos) btnIconos.classList.toggle('active', vista === 'iconos');
}

function renderizarTarjetasTipoProg() {
    var tbody = document.getElementById('tipoProgramaTableBody');
    var cont = document.getElementById('cardsContainerTipoProg');
    if (!tbody || !cont) return;
    cont.innerHTML = '';
    var rows = tbody.querySelectorAll('tr[data-codigo][data-nombre]');
    rows.forEach(function(tr, i) {
        var codigo = tr.getAttribute('data-codigo');
        var nombre = tr.getAttribute('data-nombre') || '';
        var sigla = tr.getAttribute('data-sigla') || '';
        var idx = tr.getAttribute('data-index') || (i + 1);
        var nomEsc = (nombre + '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        var siglaEsc = (sigla + '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        var card = document.createElement('div');
        card.className = 'card-item';
        card.setAttribute('data-codigo', codigo);
        card.setAttribute('data-nombre', nombre);
        card.setAttribute('data-sigla', sigla);
        card.innerHTML = '<div class="card-numero-row">#' + idx + '</div>' +
            '<div class="card-row"><span class="label">Nombre:</span> <span>' + nomEsc + '</span></div>' +
            '<div class="card-row"><span class="label">Sigla:</span> <span>' + siglaEsc + '</span></div>' +
            '<div class="card-acciones">' +
            '<button type="button" class="btn-editar-card-tipoprog p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar"><i class="fa-solid fa-edit"></i></button>' +
            '<button type="button" class="btn-eliminar-card-tipoprog p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" data-codigo="' + codigo + '"><i class="fa-solid fa-trash"></i></button>' +
            '</div>';
        cont.appendChild(card);
    });
}

function initVistaTipoProg() {
    var wrapper = document.getElementById('tablaTipoProgramaWrapper');
    if (!wrapper) return;
    var vistaInicial = window.innerWidth < 768 ? 'iconos' : 'tabla';
    wrapper.setAttribute('data-vista', vistaInicial);
    renderizarTarjetasTipoProg();
    aplicarVisibilidadVistaTipoProg();
}

document.addEventListener('DOMContentLoaded', function() {
    var btnLista = document.getElementById('btnViewTablaTipoProg');
    var btnIconos = document.getElementById('btnViewIconosTipoProg');
    var wrapper = document.getElementById('tablaTipoProgramaWrapper');
    if (btnLista) {
        btnLista.addEventListener('click', function() {
            if (wrapper) wrapper.setAttribute('data-vista', 'tabla');
            aplicarVisibilidadVistaTipoProg();
        });
    }
    if (btnIconos) {
        btnIconos.addEventListener('click', function() {
            if (wrapper) wrapper.setAttribute('data-vista', 'iconos');
            renderizarTarjetasTipoProg();
            aplicarVisibilidadVistaTipoProg();
        });
    }
    initVistaTipoProg();
    document.addEventListener('click', function(e) {
        var ed = e.target.closest('.btn-editar-card-tipoprog');
        var el = e.target.closest('.btn-eliminar-card-tipoprog');
        if (ed) {
            var card = ed.closest('.card-item');
            if (card) {
                var cod = parseInt(card.getAttribute('data-codigo'), 10);
                var tr = document.querySelector('#tipoProgramaTableBody tr[data-codigo="' + cod + '"]');
                if (tr) {
                    var campos = null;
                    try { var c = tr.getAttribute('data-campos'); if (c) campos = JSON.parse(c); } catch (x) {}
                    openModal('edit', cod, card.getAttribute('data-nombre') || '', card.getAttribute('data-sigla') || '', campos);
                } else {
                    openModal('edit', cod, card.getAttribute('data-nombre') || '', card.getAttribute('data-sigla') || '', null);
                }
            }
        }
        if (el) {
            var cod = el.getAttribute('data-codigo');
            if (cod) confirmDelete(parseInt(cod, 10));
        }
    });
});

var CAMPOS_IDS = ['modalCampoUbicacion', 'modalCampoProducto', 'modalCampoUnidades', 'modalCampoUnidadDosis', 'modalCampoNumeroFrascos', 'modalCampoEdadAplicacion', 'modalCampoAreaGalpon', 'modalCampoCantidadPorGalpon'];
var CAMPOS_KEYS = ['ubicacion', 'producto', 'unidades', 'unidad_dosis', 'numero_frascos', 'edad_aplicacion', 'area_galpon', 'cantidad_por_galpon'];

function setCamposCheckboxes(campos) {
    CAMPOS_IDS.forEach(function(id, i) {
        var el = document.getElementById(id);
        if (el) el.checked = campos ? (campos[CAMPOS_KEYS[i]] === 1) : false;
    });
}

function openModalEditFromRow(btn) {
    var tr = btn && btn.closest ? btn.closest('tr') : null;
    if (!tr) return;
    var codigo = parseInt(tr.getAttribute('data-codigo'), 10);
    var nombre = tr.getAttribute('data-nombre') || '';
    var sigla = tr.getAttribute('data-sigla') || '';
    var campos = null;
    try {
        var c = tr.getAttribute('data-campos');
        if (c) campos = JSON.parse(c);
    } catch (e) {}
    openModal('edit', codigo, nombre, sigla, campos);
}

function openModal(action, codigo, nombre, sigla, campos) {
    if (typeof codigo === 'undefined') codigo = null;
    if (typeof nombre === 'undefined') nombre = '';
    if (typeof sigla === 'undefined') sigla = '';
    if (typeof campos === 'undefined') campos = null;
    var modal = document.getElementById('tipoProgramaModal');
    var title = document.getElementById('modalTitleTipoProg');
    if (action === 'create') {
        title.textContent = '➕ Nuevo Tipo de Programa';
        document.getElementById('modalActionTipoProg').value = 'create';
        document.getElementById('editCodigoTipoProg').value = '';
        document.getElementById('modalNombreTipoProg').value = '';
        document.getElementById('modalSiglaTipoProg').value = '';
        setCamposCheckboxes(null);
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Tipo de Programa';
        document.getElementById('modalActionTipoProg').value = 'update';
        document.getElementById('editCodigoTipoProg').value = codigo;
        document.getElementById('modalNombreTipoProg').value = nombre;
        document.getElementById('modalSiglaTipoProg').value = sigla;
        setCamposCheckboxes(campos);
    }
    modal.style.display = 'flex';
}

function closeTipoProgramaModal() {
    document.getElementById('tipoProgramaModal').style.display = 'none';
}

function saveTipoPrograma(event) {
    event.preventDefault();
    var action = document.getElementById('modalActionTipoProg').value;
    var nombre = document.getElementById('modalNombreTipoProg').value.trim();
    var sigla = document.getElementById('modalSiglaTipoProg').value.trim();
    var codigo = document.getElementById('editCodigoTipoProg').value;
    if (!nombre) {
        if (typeof SwalAlert === 'function') SwalAlert('El nombre es obligatorio.', 'warning'); else alert('El nombre es obligatorio.');
        return false;
    }
    var params = { action: action, nombre: nombre, sigla: sigla };
    if (action === 'update') params.codigo = codigo;
    var campoNames = ['campoUbicacion', 'campoProducto', 'campoUnidades', 'campoUnidadDosis', 'campoNumeroFrascos', 'campoEdadAplicacion', 'campoAreaGalpon', 'campoCantidadPorGalpon'];
    CAMPOS_IDS.forEach(function(id, i) {
        var el = document.getElementById(id);
        params[campoNames[i]] = (el && el.checked) ? '1' : '0';
    });
    fetch('crud_tipo_programa.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); }); else { alert(data.message); location.reload(); }
        } else {
            if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error'); else alert(data.message);
        }
    })
    .catch(function(err) {
        console.error(err);
        if (typeof SwalAlert === 'function') SwalAlert('Error al guardar el tipo de programa.', 'error'); else alert('Error al guardar.');
    });
    return false;
}

function confirmDelete(codigo) {
    var msg = '¿Está seguro de eliminar este tipo de programa? Esta acción no se puede deshacer.';
    var doDelete = typeof SwalConfirm === 'function' ? SwalConfirm(msg, 'Confirmar eliminación') : Promise.resolve(confirm(msg));
    doDelete.then(function(confirmed) {
        if (!confirmed) return;
        fetch('crud_tipo_programa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'delete', codigo: codigo })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); }); else { alert(data.message); location.reload(); }
            } else {
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error'); else alert(data.message);
            }
        })
        .catch(function(err) {
            console.error(err);
            if (typeof SwalAlert === 'function') SwalAlert('Error al eliminar.', 'error'); else alert('Error al eliminar.');
        });
    });
}

function exportarTiposPrograma() {
    window.location.href = 'exportar_tipo_programa.php';
}
