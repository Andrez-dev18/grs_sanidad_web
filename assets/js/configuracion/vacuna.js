(function() {
    'use strict';

    function getBaseUrl() {
        return window.location.pathname.replace(/\/[^/]+\.php$/, '/');
    }

    window.openModal = function(action, codigo, codProducto, descripcion) {
        var modal = document.getElementById('vacunaModal');
        var title = document.getElementById('modalTitle');
        var inputCodigo = document.getElementById('inputCodigo');
        var inputCodProducto = document.getElementById('inputCodProducto');
        var inputDescripcion = document.getElementById('inputDescripcion');
        var codigoActual = document.getElementById('codigoActual');
        var modalAction = document.getElementById('modalAction');
        if (!modal) return;
        if (action === 'create') {
            title.textContent = '➕ Nueva vacuna';
            modalAction.value = 'create';
            codigoActual.value = '';
            inputCodigo.value = '';
            inputCodigo.disabled = false;
            inputCodProducto.value = '';
            inputDescripcion.value = '';
        } else {
            title.textContent = '✏️ Editar vacuna';
            modalAction.value = 'update';
            codigoActual.value = codigo || '';
            inputCodigo.value = codigo || '';
            inputCodigo.disabled = true;
            inputCodProducto.value = codProducto || '';
            inputDescripcion.value = descripcion || '';
        }
        modal.style.display = 'flex';
    };

    window.closeModal = function() {
        var modal = document.getElementById('vacunaModal');
        if (modal) modal.style.display = 'none';
    };

    window.save = function(event) {
        event.preventDefault();
        var action = document.getElementById('modalAction').value;
        var codigo = document.getElementById('inputCodigo').value ? parseInt(document.getElementById('inputCodigo').value, 10) : 0;
        var codProducto = (document.getElementById('inputCodProducto').value || '').trim();
        var descripcion = (document.getElementById('inputDescripcion').value || '').trim();
        var codigo_actual = document.getElementById('codigoActual').value ? parseInt(document.getElementById('codigoActual').value, 10) : 0;
        var params = new URLSearchParams();
        params.append('action', action);
        params.append('codProducto', codProducto);
        params.append('descripcion', descripcion);
        if (action === 'create') params.append('codigo', codigo);
        if (action === 'update' || action === 'delete') params.append('codigo_actual', codigo_actual);
        fetch(getBaseUrl() + 'crud_vacuna.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); });
                    else { alert(data.message); location.reload(); }
                } else {
                    if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error'); else alert(data.message);
                }
            })
            .catch(function(err) { console.error(err); });
        return false;
    };

    window.confirmDelete = function(codigo) {
        var msg = '¿Eliminar esta vacuna?';
        var doIt = typeof SwalConfirm === 'function' ? SwalConfirm(msg, 'Confirmar') : Promise.resolve(confirm(msg));
        doIt.then(function(ok) {
            if (!ok) return;
            var params = new URLSearchParams({ action: 'delete', codigo_actual: codigo });
            fetch(getBaseUrl() + 'crud_vacuna.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); });
                        else location.reload();
                    } else {
                        if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error');
                    }
                });
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            var ed = e.target.closest('.btn-editar');
            var el = e.target.closest('.btn-eliminar');
            if (ed) openModal('edit', ed.getAttribute('data-codigo'), ed.getAttribute('data-codproducto'), ed.getAttribute('data-descripcion'));
            if (el) confirmDelete(el.getAttribute('data-codigo'));
        });
    });
})();
