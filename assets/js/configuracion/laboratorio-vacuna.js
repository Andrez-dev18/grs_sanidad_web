(function() {
    'use strict';

    function getBaseUrl() {
        return window.location.pathname.replace(/\/[^/]+\.php$/, '/');
    }

    window.openModal = function(action, codigo, nombre) {
        var modal = document.getElementById('laboratorioModal');
        var title = document.getElementById('modalTitle');
        var inputNombre = document.getElementById('inputNombre');
        var codigoActual = document.getElementById('codigoActual');
        var modalAction = document.getElementById('modalAction');
        if (!modal) return;
        if (action === 'create') {
            title.textContent = '➕ Nuevo laboratorio';
            modalAction.value = 'create';
            codigoActual.value = '';
            inputNombre.value = '';
        } else {
            title.textContent = '✏️ Editar laboratorio';
            modalAction.value = 'update';
            codigoActual.value = codigo || '';
            inputNombre.value = nombre || '';
        }
        modal.style.display = 'flex';
    };

    window.closeModal = function() {
        var modal = document.getElementById('laboratorioModal');
        if (modal) modal.style.display = 'none';
    };

    window.save = function(event) {
        event.preventDefault();
        var action = document.getElementById('modalAction').value;
        var nombre = (document.getElementById('inputNombre').value || '').trim();
        var codigo_actual = document.getElementById('codigoActual').value ? parseInt(document.getElementById('codigoActual').value, 10) : 0;
        var params = new URLSearchParams();
        params.append('action', action);
        params.append('nombre', nombre);
        if (action === 'update' || action === 'delete') params.append('codigo_actual', codigo_actual);
        var url = getBaseUrl() + 'crud_laboratorio_vacuna.php';
        fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); });
                    else { alert(data.message); location.reload(); }
                } else {
                    if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error'); else alert(data.message);
                }
            })
            .catch(function(err) { console.error(err); if (typeof SwalAlert === 'function') SwalAlert('Error al guardar', 'error'); });
        return false;
    };

    window.confirmDelete = function(codigo) {
        var msg = '¿Eliminar este laboratorio?';
        var doIt = typeof SwalConfirm === 'function' ? SwalConfirm(msg, 'Confirmar') : Promise.resolve(confirm(msg));
        doIt.then(function(ok) {
            if (!ok) return;
            var params = new URLSearchParams({ action: 'delete', codigo_actual: codigo });
            fetch(getBaseUrl() + 'crud_laboratorio_vacuna.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params })
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
            if (ed) openModal('edit', ed.getAttribute('data-codigo'), ed.getAttribute('data-nombre'));
            if (el) confirmDelete(el.getAttribute('data-codigo'));
        });
    });
})();
