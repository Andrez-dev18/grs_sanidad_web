(function() {
    'use strict';

    function aplicarVisibilidadVistaProductos() {
        var wrapper = document.getElementById('tablaProductosWrapper');
        if (!wrapper) return;
        var vista = wrapper.getAttribute('data-vista') || 'tabla';
        var listaWrap = wrapper.querySelector('.view-lista-wrap');
        var tarjetasWrap = wrapper.querySelector('.view-tarjetas-wrap');
        var btnLista = document.getElementById('btnViewTablaProductos');
        var btnIconos = document.getElementById('btnViewIconosProductos');
        if (listaWrap) listaWrap.style.display = vista === 'tabla' ? 'block' : 'none';
        if (tarjetasWrap) tarjetasWrap.style.display = vista === 'iconos' ? 'block' : 'none';
        if (btnLista) btnLista.classList.toggle('active', vista === 'tabla');
        if (btnIconos) btnIconos.classList.toggle('active', vista === 'iconos');
    }

    function renderizarTarjetasProductos() {
        var tbody = document.getElementById('productosTableBody');
        var cont = document.getElementById('cardsContainerProductos');
        if (!tbody || !cont) return;
        cont.innerHTML = '';
        var rows = tbody.querySelectorAll('tr[data-codigo][data-descri]');
        rows.forEach(function(tr, i) {
            var codigo = tr.getAttribute('data-codigo');
            var descri = tr.getAttribute('data-descri') || '';
            var nombreProveedor = tr.getAttribute('data-nombre-proveedor') || '';
            var dosis = tr.getAttribute('data-dosis') || '';
            var tipo = tr.getAttribute('data-tipo') || 'GENERAL';
            var idx = tr.getAttribute('data-index') || (i + 1);
            var descriEsc = (descri + '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            var nomProvEsc = (nombreProveedor + '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            var dosisEsc = (dosis + '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            var codigoEsc = (codigo + '').replace(/</g, '&lt;').replace(/&/g, '&amp;').replace(/"/g, '&quot;');
            var tipoEsc = (tipo + '').replace(/</g, '&lt;').replace(/&/g, '&amp;').replace(/"/g, '&quot;');
            var card = document.createElement('div');
            card.className = 'card-item';
            card.setAttribute('data-codigo', codigo);
            card.setAttribute('data-descri', descri);
            card.setAttribute('data-tcodprove', tr.getAttribute('data-tcodprove') || '');
            card.setAttribute('data-dosis', dosis);
            card.setAttribute('data-nombre-proveedor', nombreProveedor);
            card.setAttribute('data-tipo', tipo);
            card.innerHTML = '<div class="card-numero-row">#' + idx + '</div>' +
                '<div class="card-row"><span class="label">Código:</span> <span>' + codigoEsc + '</span></div>' +
                '<div class="card-row"><span class="label">Descripción:</span> <span>' + descriEsc + '</span></div>' +
                '<div class="card-row"><span class="label">Tipo:</span> <span>' + tipoEsc + '</span></div>' +
                '<div class="card-row"><span class="label">Proveedor:</span> <span>' + nomProvEsc + '</span></div>' +
                '<div class="card-row"><span class="label">Dosis:</span> <span>' + dosisEsc + '</span></div>' +
                '<div class="card-acciones">' +
                '<button type="button" class="btn-editar-card-producto p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Editar"><i class="fa-solid fa-edit"></i></button>' +
                '<button type="button" class="btn-eliminar-card-producto p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition" title="Eliminar" data-codigo="' + codigo + '"><i class="fa-solid fa-trash"></i></button>' +
                '</div>';
            cont.appendChild(card);
        });
    }

    function initVistaProductos() {
        var wrapper = document.getElementById('tablaProductosWrapper');
        if (!wrapper) return;
        var vistaInicial = window.innerWidth < 768 ? 'iconos' : 'tabla';
        wrapper.setAttribute('data-vista', vistaInicial);
        renderizarTarjetasProductos();
        aplicarVisibilidadVistaProductos();
    }

    var cacheProveedoresProducto = null;
    var cacheEnfermedades = null;

    function getBaseUrlProducto() {
        return window.location.pathname.replace(/\/[^/]+\.php$/, '/');
    }

    function toggleWrapEsVacuna() {
        var wrap = document.getElementById('wrapEsVacuna');
        var chk = document.getElementById('modalEsVacuna');
        if (!wrap || !chk) return;
        if (chk.checked) {
            wrap.classList.remove('hidden');
            if (!cacheEnfermedades) cargarEnfermedadesCheckboxes([]);
        } else {
            wrap.classList.add('hidden');
        }
    }

    function cargarEnfermedadesCheckboxes(codEnfermedadesSeleccionados) {
        var wrap = document.getElementById('wrapCheckboxEnfermedades');
        var loading = document.getElementById('loadingEnfermedades');
        if (!wrap) return;
        if (loading) loading.classList.remove('hidden');
        var url = getBaseUrlProducto() + 'get_enfermedades.php';
        fetch(url)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (loading) loading.classList.add('hidden');
                wrap.innerHTML = '';
                if (data.success && data.results) {
                    cacheEnfermedades = data.results;
                    var set = (codEnfermedadesSeleccionados || []).map(Number);
                    data.results.forEach(function(o) {
                        var label = document.createElement('label');
                        label.className = 'flex items-center gap-2 cursor-pointer hover:bg-gray-100 p-1 rounded';
                        var input = document.createElement('input');
                        input.type = 'checkbox';
                        input.name = 'cod_enfermedades[]';
                        input.value = o.cod_enf;
                        input.className = 'rounded border-gray-300 text-indigo-600 focus:ring-indigo-500';
                        if (set.indexOf(Number(o.cod_enf)) !== -1) input.checked = true;
                        var span = document.createElement('span');
                        span.className = 'text-sm text-gray-700';
                        span.textContent = o.nom_enf;
                        label.appendChild(input);
                        label.appendChild(span);
                        wrap.appendChild(label);
                    });
                    actualizarDescripcionVacunaDesdeCheckboxes();
                }
            })
            .catch(function(err) { console.error(err); if (loading) loading.classList.add('hidden'); });
    }

    function actualizarDescripcionVacunaDesdeCheckboxes() {
        var wrap = document.getElementById('wrapCheckboxEnfermedades');
        var ta = document.getElementById('modalDescripcionVacuna');
        if (!wrap || !ta) return;
        var checks = wrap.querySelectorAll('input[name="cod_enfermedades[]"]:checked');
        var nombres = [];
        checks.forEach(function(cb) {
            var label = cb.closest('label');
            var span = label ? label.querySelector('span') : null;
            if (span && span.textContent) nombres.push(span.textContent.trim());
        });
        ta.value = nombres.join(', ');
    }

    function loadDatosVacunaProducto(codigo, callback) {
        var url = getBaseUrlProducto() + 'get_datos_enfermedad_producto.php?codigo=' + encodeURIComponent(codigo);
        fetch(url)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (typeof callback === 'function') callback(data.success ? data : { descripcion: '', codEnfermedades: [] });
            })
            .catch(function(err) { console.error(err); if (typeof callback === 'function') callback({ descripcion: '', codEnfermedades: [] }); });
    }

    function destroySelect2Producto() {
        if (typeof jQuery === 'undefined') return;
        var $selProducto = jQuery('#modalProductoSelect');
        var $selProveedor = jQuery('#modalProveedorProducto');
        if ($selProducto.length && $selProducto.data('select2')) $selProducto.select2('destroy');
        if ($selProveedor.length && $selProveedor.data('select2')) $selProveedor.select2('destroy');
    }

    function initSelect2Producto() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') return;
        var baseUrl = window.location.pathname.replace(/\/[^/]+\.php$/, '/');
        jQuery('#modalProductoSelect').select2({
            placeholder: 'Escriba para buscar producto...',
            allowClear: true,
            width: '100%',
            dropdownParent: jQuery('#productoModal'),
            minimumInputLength: 1,
            ajax: {
                url: baseUrl + 'get_mitm_buscar.php',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
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

    function initSelect2ProveedorProducto() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') return;
        jQuery('#modalProveedorProducto').select2({
            placeholder: 'Seleccionar proveedor...',
            allowClear: true,
            width: '100%',
            dropdownParent: jQuery('#productoModal'),
            language: {
                noResults: function() { return 'Sin resultados'; },
                searching: function() { return 'Buscando...'; }
            }
        });
    }

    function cargarProveedoresProducto(tcodproveSeleccionado, callback) {
        var url = window.location.pathname.replace(/\/[^/]+\.php$/, '/') + 'get_proveedores_productos.php';
        fetch(url)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.results) {
                    if (typeof callback === 'function') callback(data.results, tcodproveSeleccionado);
                } else {
                    if (typeof SwalAlert === 'function') SwalAlert(data.message || 'Error al cargar proveedores', 'error');
                }
            })
            .catch(function(err) {
                console.error(err);
                if (typeof SwalAlert === 'function') SwalAlert('Error al cargar proveedores', 'error');
            });
    }

    function rellenarSelectProveedorYInit(opciones, valorSeleccionado) {
        var select = document.getElementById('modalProveedorProducto');
        var loadingEl = document.getElementById('loadingProveedoresProducto');
        if (!select) return;
        select.innerHTML = '<option value="">Seleccionar...</option>';
        (opciones || []).forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.text;
            if (valorSeleccionado !== undefined && String(o.id) === String(valorSeleccionado)) opt.selected = true;
            select.appendChild(opt);
        });
        if (loadingEl) loadingEl.classList.add('hidden');
        select.disabled = false;
        setTimeout(function() { initSelect2ProveedorProducto(); }, 0);
    }

    window.openProductoModal = function(action, codigo, descri, tcodprove, nombreProveedor, dosis) {
        if (typeof codigo === 'undefined') codigo = '';
        if (typeof descri === 'undefined') descri = '';
        if (typeof tcodprove === 'undefined') tcodprove = '';
        if (typeof nombreProveedor === 'undefined') nombreProveedor = '';
        if (typeof dosis === 'undefined') dosis = '';
        var modal = document.getElementById('productoModal');
        var title = document.getElementById('modalTitleProducto');
        var loadingEl = document.getElementById('loadingProveedoresProducto');
        var inputDosis = document.getElementById('modalDosisProducto');
        var chkEsVacuna = document.getElementById('modalEsVacuna');
        var wrapEsVacuna = document.getElementById('wrapEsVacuna');
        var inputDescripcionVacuna = document.getElementById('modalDescripcionVacuna');

        destroySelect2Producto();

        if (inputDosis) inputDosis.value = dosis;

        if (action === 'create') {
            title.textContent = '➕ Nuevo Producto';
            document.getElementById('modalActionProducto').value = 'create';
            document.getElementById('editCodigoProducto').value = '';
            var selProducto = document.getElementById('modalProductoSelect');
            selProducto.innerHTML = '<option value="">Escriba para buscar...</option>';
            selProducto.disabled = false;
            document.getElementById('modalProveedorProducto').disabled = true;
            document.getElementById('modalProveedorProducto').innerHTML = '<option value="">Seleccionar...</option>';
            if (inputDosis) inputDosis.value = '';
            if (chkEsVacuna) chkEsVacuna.checked = false;
            if (wrapEsVacuna) wrapEsVacuna.classList.add('hidden');
            if (inputDescripcionVacuna) inputDescripcionVacuna.value = '';
            var wrapChk = document.getElementById('wrapCheckboxEnfermedades');
            if (wrapChk) wrapChk.innerHTML = '';
            if (loadingEl) loadingEl.classList.remove('hidden');
            if (cacheProveedoresProducto && cacheProveedoresProducto.length >= 0) {
                rellenarSelectProveedorYInit(cacheProveedoresProducto);
            } else {
                cargarProveedoresProducto(null, function(opciones) {
                    cacheProveedoresProducto = opciones;
                    rellenarSelectProveedorYInit(opciones);
                });
            }
            setTimeout(function() {
                initSelect2Producto();
                if (typeof jQuery !== 'undefined') {
                    jQuery('#modalProductoSelect').off('select2:select.productoDescripcion').on('select2:select.productoDescripcion', function(e) {
                        var data = e.params.data;
                        if (data && data.text) {
                            var desc = document.getElementById('modalDescripcionVacuna');
                            if (desc) desc.value = data.text;
                        }
                    });
                }
            }, 50);
        } else if (action === 'edit') {
            title.textContent = '✏️ Editar Producto';
            document.getElementById('modalActionProducto').value = 'update';
            document.getElementById('editCodigoProducto').value = codigo;
            var selProducto = document.getElementById('modalProductoSelect');
            selProducto.innerHTML = '<option value="' + (codigo + '').replace(/"/g, '&quot;') + '" selected="selected">' + (descri + '').replace(/</g, '&lt;').replace(/&/g, '&amp;') + '</option>';
            selProducto.disabled = true;
            if (loadingEl) loadingEl.classList.remove('hidden');
            cargarProveedoresProducto(tcodprove, function(opciones) {
                cacheProveedoresProducto = opciones;
                rellenarSelectProveedorYInit(opciones, tcodprove);
            });
            setTimeout(function() { initSelect2Producto(); }, 50);
            loadDatosVacunaProducto(codigo, function(datos) {
                var tieneDatos = (datos.codEnfermedades && datos.codEnfermedades.length > 0) || (datos.descripcion && datos.descripcion.length > 0);
                if (chkEsVacuna) chkEsVacuna.checked = tieneDatos;
                if (wrapEsVacuna) wrapEsVacuna.classList.toggle('hidden', !tieneDatos);
                if (inputDescripcionVacuna) inputDescripcionVacuna.value = datos.descripcion || '';
                cargarEnfermedadesCheckboxes(datos.codEnfermedades || []);
            });
        }
        modal.style.display = 'flex';
    };

    window.closeProductoModal = function() {
        destroySelect2Producto();
        document.getElementById('productoModal').style.display = 'none';
    };

    window.saveProducto = function(event) {
        event.preventDefault();
        var action = document.getElementById('modalActionProducto').value;
        var codigo = (action === 'create') ? (document.getElementById('modalProductoSelect').value || '').trim() : (document.getElementById('editCodigoProducto').value || '').trim();
        var tcodprove = (document.getElementById('modalProveedorProducto').value || '').trim();
        var dosis = (document.getElementById('modalDosisProducto') && document.getElementById('modalDosisProducto').value) ? document.getElementById('modalDosisProducto').value.trim() : '';

        if (!codigo) {
            if (typeof SwalAlert === 'function') SwalAlert('Debe seleccionar un producto.', 'warning');
            return false;
        }
        if (!tcodprove) {
            if (typeof SwalAlert === 'function') SwalAlert('Debe seleccionar un proveedor.', 'warning');
            return false;
        }

        var params = new URLSearchParams();
        params.append('action', action);
        params.append('codigo', codigo);
        params.append('tcodprove', tcodprove);
        params.append('dosis', dosis);
        params.append('es_vacuna', document.getElementById('modalEsVacuna') && document.getElementById('modalEsVacuna').checked ? '1' : '0');
        var checksEnf = document.querySelectorAll('#wrapCheckboxEnfermedades input[name="cod_enfermedades[]"]:checked');
        if (checksEnf) checksEnf.forEach(function(c) { params.append('cod_enfermedades[]', c.value); });
        var url = getBaseUrlProducto() + 'crud_productos.php';
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); });
                else { alert(data.message); location.reload(); }
            } else {
                if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error');
            }
        })
        .catch(function(err) {
            console.error(err);
            if (typeof SwalAlert === 'function') SwalAlert('Error al guardar.', 'error');
        });
        return false;
    };

    window.confirmDeleteProducto = function(codigo) {
        var msg = '¿Está seguro de quitar el proveedor de este producto?';
        var doDelete = typeof SwalConfirm === 'function' ? SwalConfirm(msg, 'Confirmar') : Promise.resolve(confirm(msg));
        doDelete.then(function(confirmed) {
            if (!confirmed) return;
            var url = window.location.pathname.replace(/\/[^/]+\.php$/, '/') + 'crud_productos.php';
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'delete', codigo: codigo })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    if (typeof SwalAlert === 'function') SwalAlert(data.message, 'success').then(function() { location.reload(); });
                    else { alert(data.message); location.reload(); }
                } else {
                    if (typeof SwalAlert === 'function') SwalAlert(data.message, 'error');
                }
            })
            .catch(function(err) {
                console.error(err);
                if (typeof SwalAlert === 'function') SwalAlert('Error al eliminar.', 'error');
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        var btnLista = document.getElementById('btnViewTablaProductos');
        var btnIconos = document.getElementById('btnViewIconosProductos');
        var wrapper = document.getElementById('tablaProductosWrapper');
        if (btnLista) btnLista.addEventListener('click', function() {
            if (wrapper) wrapper.setAttribute('data-vista', 'tabla');
            aplicarVisibilidadVistaProductos();
        });
        if (btnIconos) btnIconos.addEventListener('click', function() {
            if (wrapper) wrapper.setAttribute('data-vista', 'iconos');
            renderizarTarjetasProductos();
            aplicarVisibilidadVistaProductos();
        });
        initVistaProductos();

        var chkEsEnfermedad = document.getElementById('modalEsEnfermedad');
        var chkEsVacuna = document.getElementById('modalEsVacuna');
        if (chkEsVacuna) chkEsVacuna.addEventListener('change', toggleWrapEsVacuna);
        var wrapChkEnf = document.getElementById('wrapCheckboxEnfermedades');
        if (wrapChkEnf) wrapChkEnf.addEventListener('change', actualizarDescripcionVacunaDesdeCheckboxes);

        document.addEventListener('click', function(e) {
            var edCard = e.target.closest('.btn-editar-card-producto');
            var elCard = e.target.closest('.btn-eliminar-card-producto');
            var edTable = e.target.closest('.btn-editar-producto');
            var elTable = e.target.closest('.btn-eliminar-producto');
            if (edCard) {
                var card = edCard.closest('.card-item');
                if (card) openProductoModal('edit', card.getAttribute('data-codigo'), card.getAttribute('data-descri'), card.getAttribute('data-tcodprove'), card.getAttribute('data-nombre-proveedor'), card.getAttribute('data-dosis'));
            }
            if (edTable) {
                openProductoModal('edit', edTable.getAttribute('data-codigo'), edTable.getAttribute('data-descri'), edTable.getAttribute('data-tcodprove'), edTable.getAttribute('data-nombre-proveedor'), edTable.getAttribute('data-dosis'));
            }
            if (elCard) {
                var cod = elCard.getAttribute('data-codigo');
                if (cod !== null) confirmDeleteProducto(cod);
            }
            if (elTable) {
                var cod = elTable.getAttribute('data-codigo');
                if (cod !== null) confirmDeleteProducto(cod);
            }
        });
    });
})();
