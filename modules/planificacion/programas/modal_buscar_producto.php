<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>try { window.parent.postMessage({ tipo: "cerrarModalProducto" }, "*"); } catch(e) {};</script>';
    exit;
}
$rowIndex = isset($_GET['rowIndex']) ? (int)$_GET['rowIndex'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar producto</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; background: #fff; display: flex; flex-direction: column; min-height: 0; }
        .form-control { width: 100%; padding: 0.375rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; font-size: 0.875rem; box-sizing: border-box; }
        .modal-producto-cuerpo { display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem; min-height: 0; flex: 1; }
        .modal-producto-fila { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem; }
        .modal-producto-fila .campo { flex: 1; min-width: 0; }
        .modal-producto-fila .campo-linea { flex: 0 0 28%; min-width: 100px; }
        .modal-producto-fila .campo-almacen { flex: 0 0 28%; min-width: 100px; }
        .modal-producto-fila .campo-busqueda { flex: 1; min-width: 140px; }
        .modal-producto-fila .campo-btn { flex: 0 0 auto; }
        .modal-producto-fila label { display: block; font-size: 0.75rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem; }
        #modalProductoResultados { overflow-y: auto; flex: 1; min-height: 160px; border: 1px solid #e5e7eb; border-radius: 0.375rem; }
    </style>
</head>
<body class="p-0">
    <div class="modal-producto-cuerpo">
        <div class="modal-producto-fila">
            <div class="campo campo-linea">
                <label for="modalProductoLinea">Línea</label>
                <select id="modalProductoLinea" class="form-control">
                    <option value="">Seleccionar</option>
                </select>
            </div>
            <div class="campo campo-almacen">
                <label for="modalProductoAlmacen">Almacén</label>
                <select id="modalProductoAlmacen" class="form-control">
                    <option value="">Seleccionar</option>
                </select>
            </div>
            <div class="campo campo-busqueda">
                <label for="modalProductoBuscar">Búsqueda</label>
                <input type="text" id="modalProductoBuscar" class="form-control" placeholder="Nombre o código (opcional)" autocomplete="off">
            </div>
            <div class="campo campo-btn">
                <label>&nbsp;</label>
                <button type="button" id="btnLimpiar" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-100 whitespace-nowrap">Limpiar</button>
            </div>
        </div>
        <div id="modalProductoResultados" class="border border-gray-200 rounded text-sm"></div>
    </div>
    <script>
        var rowIndex = <?php echo json_encode($rowIndex); ?>;
        var baseUrl = '../../configuracion/productos/';
        function cargarLineas() {
            fetch(baseUrl + 'get_lineas.php').then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success || !res.data) return;
                var sel = document.getElementById('modalProductoLinea');
                sel.innerHTML = '<option value="">Seleccionar</option>';
                (res.data || []).forEach(function(o) {
                    var opt = document.createElement('option');
                    opt.value = o.linea || '';
                    opt.textContent = o.text || (o.linea + ' - ' + (o.descri || ''));
                    sel.appendChild(opt);
                });
            }).catch(function() {});
        }
        function cargarAlmacenes() {
            fetch(baseUrl + 'get_almacenes.php').then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success || !res.data) return;
                var sel = document.getElementById('modalProductoAlmacen');
                sel.innerHTML = '<option value="">Seleccionar</option>';
                (res.data || []).forEach(function(o) {
                    var opt = document.createElement('option');
                    opt.value = o.alma || '';
                    opt.textContent = o.text || o.alma || '';
                    sel.appendChild(opt);
                });
            }).catch(function() {});
        }
        function buscar() {
            var q = (document.getElementById('modalProductoBuscar').value || '').trim();
            var lin = (document.getElementById('modalProductoLinea').value || '').trim();
            var alma = (document.getElementById('modalProductoAlmacen').value || '').trim();
            var cont = document.getElementById('modalProductoResultados');
            if (!q && !lin && !alma) {
                cont.innerHTML = '<p class="text-gray-500 p-2">Seleccione línea y/o almacén o escriba para buscar.</p>';
                return;
            }
            cont.innerHTML = '<p class="text-gray-500 p-2">Buscando...</p>';
            var url = 'get_productos_programa.php?q=' + encodeURIComponent(q) + (lin ? '&lin=' + encodeURIComponent(lin) : '') + (alma ? '&alma=' + encodeURIComponent(alma) : '');
            fetch(url).then(function(r) { return r.json(); }).then(function(data) {
                if (!data.success || !data.results || !data.results.length) {
                    cont.innerHTML = '<p class="text-gray-500 p-2">Sin resultados.</p>';
                    return;
                }
                var html = '';
                data.results.forEach(function(item) {
                    var cod = item.codigo || item.id;
                    var desc = (item.descri || item.text || '').replace(/^[^\s-]+\s*-\s*/, '');
                    var esc = function(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); };
                    html += '<div class="modal-producto-item p-2 border-b border-gray-100 hover:bg-gray-100 cursor-pointer" data-id="' + (item.id || '').replace(/"/g, '&quot;') + '" data-codigo="' + (cod || '').replace(/"/g, '&quot;') + '" data-descri="' + (desc || '').replace(/"/g, '&quot;') + '"><strong>' + esc(cod) + '</strong> - ' + esc(desc) + '</div>';
                });
                cont.innerHTML = html;
                cont.querySelectorAll('.modal-producto-item').forEach(function(el) {
                    el.onclick = function() {
                        var id = this.getAttribute('data-id');
                        var codigo = this.getAttribute('data-codigo');
                        var descri = this.getAttribute('data-descri');
                        try {
                            window.parent.postMessage({ tipo: 'productoSeleccionado', rowIndex: rowIndex, id: id || '', codigo: codigo || '', descri: descri || '' }, '*');
                        } catch (e) {}
                    };
                });
            }).catch(function() { cont.innerHTML = '<p class="text-red-500 p-2">Error al buscar.</p>'; });
        }
        var buscarTimer = null;
        function programarBusqueda() {
            if (buscarTimer) clearTimeout(buscarTimer);
            buscarTimer = setTimeout(buscar, 300);
        }
        function limpiar() {
            document.getElementById('modalProductoLinea').value = '';
            document.getElementById('modalProductoAlmacen').value = '';
            document.getElementById('modalProductoBuscar').value = '';
            document.getElementById('modalProductoResultados').innerHTML = '<p class="text-gray-500 p-2">Seleccione línea y/o almacén o escriba para buscar.</p>';
        }
        document.getElementById('btnLimpiar').addEventListener('click', limpiar);
        document.getElementById('modalProductoLinea').addEventListener('change', programarBusqueda);
        document.getElementById('modalProductoAlmacen').addEventListener('change', programarBusqueda);
        document.getElementById('modalProductoBuscar').addEventListener('input', programarBusqueda);
        document.getElementById('modalProductoBuscar').addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); if (buscarTimer) clearTimeout(buscarTimer); buscar(); } });
        cargarLineas();
        cargarAlmacenes();
        document.getElementById('modalProductoResultados').innerHTML = '<p class="text-gray-500 p-2">Seleccione línea y/o almacén o escriba para buscar.</p>';
    </script>
</body>
</html>
