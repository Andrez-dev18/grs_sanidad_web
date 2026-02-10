function aplicarVisibilidadVistaProveedor() {
    var wrapper = document.getElementById('tablaProveedorWrapper');
    if (!wrapper) return;
    var vista = wrapper.getAttribute('data-vista') || 'tabla';
    var listaWrap = wrapper.querySelector('.view-lista-wrap');
    var tarjetasWrap = wrapper.querySelector('.view-tarjetas-wrap');
    var btnLista = document.getElementById('btnViewTablaProveedor');
    var btnIconos = document.getElementById('btnViewIconosProveedor');
    if (listaWrap) listaWrap.style.display = vista === 'tabla' ? 'block' : 'none';
    if (tarjetasWrap) tarjetasWrap.style.display = vista === 'iconos' ? 'block' : 'none';
    if (btnLista) btnLista.classList.toggle('active', vista === 'tabla');
    if (btnIconos) btnIconos.classList.toggle('active', vista === 'iconos');
}

function renderizarTarjetasProveedor() {
    var tbody = document.getElementById('proveedorTableBody');
    var cont = document.getElementById('cardsContainerProveedor');
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
        var codEsc = (codigo + '').replace(/</g, '&lt;').replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        card.innerHTML = '<div class="card-numero-row">#' + idx + '</div>' +
            '<div class="card-row"><span class="label">Código:</span> <span>' + codEsc + '</span></div>' +
            '<div class="card-row"><span class="label">Nombre:</span> <span>' + nomEsc + '</span></div>' +
            '<div class="card-row"><span class="label">Abreviatura:</span> <span>' + siglaEsc + '</span></div>' +
            '<div class="card-acciones">' +
            '<button type="button" class="btn-editar-card-proveedor p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar"><i class="fa-solid fa-edit"></i></button>' +
            '<button type="button" class="btn-eliminar-card-proveedor p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" data-codigo="' + codigo + '"><i class="fa-solid fa-trash"></i></button>' +
            '</div>';
        cont.appendChild(card);
    });
}

function initVistaProveedor() {
    var wrapper = document.getElementById('tablaProveedorWrapper');
    if (!wrapper) return;
    var vistaInicial = window.innerWidth < 768 ? 'iconos' : 'tabla';
    wrapper.setAttribute('data-vista', vistaInicial);
    renderizarTarjetasProveedor();
    aplicarVisibilidadVistaProveedor();
}

function destroySelect2Proveedor() {
    if (typeof jQuery === 'undefined') return;
    var $sel = jQuery('#modalCcteProveedor');
    if ($sel.length && $sel.data('select2')) {
        $sel.select2('destroy');
    }
}

function getBaseUrlProveedor() {
    return window.location.pathname.replace(/\/[^/]+\.php$/, '/');
}

function initSelect2Proveedor(codigoActual) {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') return;
    var baseUrl = getBaseUrlProveedor();
    jQuery('#modalCcteProveedor').select2({
        placeholder: 'Escriba para buscar...',
        allowClear: true,
        width: '100%',
        dropdownParent: jQuery('#proveedorModal'),
        minimumInputLength: 1,
        ajax: {
            url: baseUrl + 'get_ccte_buscar.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                var data = { q: params.term };
                var editCodigo = document.getElementById('editCodigoProveedor');
                if (editCodigo && editCodigo.value) data.codigo_actual = editCodigo.value;
                return data;
            },
            processResults: function(data) {
                if (data.success && data.results) return { results: data.results };
                return { results: [] };
            },
            cache: true
        },
        language: {
            noResults: function() { return 'Sin resultados'; },
            searching: function() { return 'Buscando...'; },
            inputTooShort: function() { return 'Escriba al menos 1 carácter'; }
        }
    });
}

function openModal(action, codigo, nombre, sigla) {
    if (typeof codigo === 'undefined') codigo = null;
    if (typeof nombre === 'undefined') nombre = '';
    if (typeof sigla === 'undefined') sigla = '';
    var modal = document.getElementById('proveedorModal');
    var title = document.getElementById('modalTitleProveedor');
    var select = document.getElementById('modalCcteProveedor');
    var inputSigla = document.getElementById('modalSiglaProveedor');

    destroySelect2Proveedor();

    if (action === 'create') {
        title.textContent = '➕ Nuevo Proveedor';
        document.getElementById('modalActionProveedor').value = 'create';
        document.getElementById('editCodigoProveedor').value = '';
        inputSigla.value = '';
        select.innerHTML = '<option value="">Escriba para buscar...</option>';
        select.disabled = false;
        setTimeout(function() { initSelect2Proveedor(); }, 50);
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Proveedor';
        document.getElementById('modalActionProveedor').value = 'update';
        document.getElementById('editCodigoProveedor').value = codigo;
        inputSigla.value = sigla;
        var codigoEsc = (codigo + '').replace(/"/g, '&quot;');
        var nombreEsc = (nombre + '').replace(/</g, '&lt;').replace(/&/g, '&amp;');
        select.innerHTML = '<option value="' + codigoEsc + '" selected="selected">' + nombreEsc + '</option>';
        select.disabled = false;
        setTimeout(function() { initSelect2Proveedor(codigo); }, 50);
    }
    modal.style.display = 'flex';
}

function closeProveedorModal() {
    destroySelect2Proveedor();
    document.getElementById('proveedorModal').style.display = 'none';
}

function saveProveedor(event) {
    event.preventDefault();
    var action = document.getElementById('modalActionProveedor').value;
    var codigoCcte = document.getElementById('modalCcteProveedor').value || '';
    var sigla = document.getElementById('modalSiglaProveedor').value.trim();
    var codigoActual = document.getElementById('editCodigoProveedor').value;

    if (!codigoCcte) {
        if (typeof SwalAlert === 'function') SwalAlert('Debe seleccionar un registro de la lista.', 'warning'); else alert('Debe seleccionar un registro.');
        return false;
    }

    var params = { action: action, codigo_ccte: codigoCcte, sigla: sigla };
    if (action === 'update') params.codigo_actual = codigoActual;

    fetch('crud_proveedor.php', {
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
            if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error'); else alert(data.message);
        }
    })
    .catch(function(err) {
        console.error(err);
        if (typeof SwalAlert === 'function') SwalAlert('Error al guardar.', 'error'); else alert('Error al guardar.');
    });
    return false;
}

function confirmDelete(codigo) {
    var msg = '¿Está seguro de eliminar este proveedor? Dejará de mostrarse en la lista (proveedor_programa se pondrá en 0).';
    var doDelete = typeof SwalConfirm === 'function' ? SwalConfirm(msg, 'Confirmar eliminación') : Promise.resolve(confirm(msg));
    doDelete.then(function(confirmed) {
        if (!confirmed) return;
        fetch('crud_proveedor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'delete', codigo_actual: codigo })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); });
                else { alert(data.message); location.reload(); }
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

document.addEventListener('DOMContentLoaded', function() {
    var btnLista = document.getElementById('btnViewTablaProveedor');
    var btnIconos = document.getElementById('btnViewIconosProveedor');
    var wrapper = document.getElementById('tablaProveedorWrapper');
    if (btnLista) btnLista.addEventListener('click', function() {
        if (wrapper) wrapper.setAttribute('data-vista', 'tabla');
        aplicarVisibilidadVistaProveedor();
    });
    if (btnIconos) btnIconos.addEventListener('click', function() {
        if (wrapper) wrapper.setAttribute('data-vista', 'iconos');
        renderizarTarjetasProveedor();
        aplicarVisibilidadVistaProveedor();
    });
    initVistaProveedor();
    document.addEventListener('click', function(e) {
        var edCard = e.target.closest('.btn-editar-card-proveedor');
        var elCard = e.target.closest('.btn-eliminar-card-proveedor');
        var edTable = e.target.closest('.btn-editar-proveedor');
        var elTable = e.target.closest('.btn-eliminar-proveedor');
        if (edCard) {
            var card = edCard.closest('.card-item');
            if (card) openModal('edit', card.getAttribute('data-codigo') || '', card.getAttribute('data-nombre') || '', card.getAttribute('data-sigla') || '');
        }
        if (edTable) {
            openModal('edit', edTable.getAttribute('data-codigo') || '', edTable.getAttribute('data-nombre') || '', edTable.getAttribute('data-sigla') || '');
        }
        if (elCard) {
            var cod = elCard.getAttribute('data-codigo');
            if (cod !== null) confirmDelete(cod);
        }
        if (elTable) {
            var cod = elTable.getAttribute('data-codigo');
            if (cod !== null) confirmDelete(cod);
        }
    });
});
