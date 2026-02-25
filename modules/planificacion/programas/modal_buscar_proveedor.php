<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>try { window.parent.postMessage({ tipo: "cerrarModalProveedor" }, "*"); } catch(e) {};</script>';
    exit;
}
$rowIndex = isset($_GET['rowIndex']) ? (int)$_GET['rowIndex'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar proveedor</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; background: #fff; display: flex; flex-direction: column; min-height: 0; }
        .form-control { width: 100%; padding: 0.375rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; font-size: 0.875rem; box-sizing: border-box; }
        .modal-proveedor-cuerpo { display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem; min-height: 0; flex: 1; }
        .modal-proveedor-fila { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem; }
        .modal-proveedor-fila .campo-busqueda { flex: 1; min-width: 140px; }
        .modal-proveedor-fila .campo-btn { flex: 0 0 auto; }
        .modal-proveedor-fila label { display: block; font-size: 0.75rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem; }
        #modalProveedorResultados { overflow-y: auto; flex: 1; min-height: 160px; border: 1px solid #e5e7eb; border-radius: 0.375rem; }
    </style>
</head>
<body class="p-0">
    <div class="modal-proveedor-cuerpo">
        <div class="modal-proveedor-fila">
            <div class="campo-busqueda">
                <label for="modalProveedorBuscar">Buscar proveedor</label>
                <input type="text" id="modalProveedorBuscar" class="form-control" placeholder="Nombre o código del proveedor..." autocomplete="off">
            </div>
            <div class="campo-btn">
                <label>&nbsp;</label>
                <button type="button" id="btnLimpiar" class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-100 whitespace-nowrap">Limpiar</button>
            </div>
        </div>
        <div id="modalProveedorResultados" class="border border-gray-200 rounded text-sm"></div>
    </div>
    <script src="../../../assets/js/fetch-auth-redirect.js"></script>
    <script>
        var rowIndex = <?php echo json_encode($rowIndex); ?>;
        var timer = null;
        document.getElementById('modalProveedorBuscar').addEventListener('input', function() {
            var q = (this.value || '').trim();
            var cont = document.getElementById('modalProveedorResultados');
            if (timer) clearTimeout(timer);
            if (!q) { cont.innerHTML = '<p class="text-gray-500 p-2">Escriba para buscar proveedor.</p>'; return; }
            cont.innerHTML = '<p class="text-gray-500 p-2">Buscando...</p>';
            timer = setTimeout(function() {
                fetch('get_ccte_lista.php?q=' + encodeURIComponent(q)).then(function(r) { return r.json(); }).then(function(data) {
                    if (!data.success || !data.data || !data.data.length) {
                        cont.innerHTML = '<p class="text-gray-500 p-2">Sin resultados.</p>';
                        return;
                    }
                    var html = '';
                    var esc = function(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); };
                    data.data.forEach(function(item) {
                        var cod = item.codigo || '';
                        var nom = item.nombre || '';
                        html += '<div class="modal-proveedor-item p-2 border-b border-gray-100 hover:bg-gray-100 cursor-pointer" data-codigo="' + (cod + '').replace(/"/g, '&quot;') + '" data-nombre="' + (nom + '').replace(/"/g, '&quot;') + '"><strong>' + esc(cod) + '</strong> - ' + esc(nom) + '</div>';
                    });
                    cont.innerHTML = html;
                    cont.querySelectorAll('.modal-proveedor-item').forEach(function(el) {
                        el.onclick = function() {
                            var codigo = this.getAttribute('data-codigo') || '';
                            var nombre = this.getAttribute('data-nombre') || '';
                            try {
                                window.parent.postMessage({ tipo: 'proveedorSeleccionado', rowIndex: rowIndex, codigo: codigo, nombre: nombre }, '*');
                            } catch (e) {}
                        };
                    });
                }).catch(function() { cont.innerHTML = '<p class="text-red-500 p-2">Error al buscar.</p>'; });
            }, 250);
        });
        document.getElementById('btnLimpiar').addEventListener('click', function() {
            document.getElementById('modalProveedorBuscar').value = '';
            document.getElementById('modalProveedorResultados').innerHTML = '<p class="text-gray-500 p-2">Escriba para buscar proveedor.</p>';
        });
        document.getElementById('modalProveedorResultados').innerHTML = '<p class="text-gray-500 p-2">Escriba para buscar proveedor.</p>';
    </script>
</body>
</html>
