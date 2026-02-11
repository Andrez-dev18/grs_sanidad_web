<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; } else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programas - Registro</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none; padding: 0.5rem 1rem; font-size: 0.8125rem; font-weight: 600;
            color: white; border-radius: 0.25rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;
        }
        .btn-primary:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); transform: translateY(-1px); }
        .form-control { width: 100%; padding: 0.375rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; font-size: 0.8125rem; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
        .bloque-detalle { display: block; }
        .select2-container .select2-selection--single { height: 32px; border-radius: 0.25rem; border: 1px solid #d1d5db; padding: 2px 8px; font-size: 0.8125rem; }
        .select2-container { width: 100% !important; }
        /* Opciones del dropdown Select2 más pequeñas y formato código - descri */
        .select2-container--default .select2-results__option { font-size: 0.75rem; padding: 4px 8px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { font-size: 0.8125rem; line-height: 28px; }
        /* Tabla detalle compacta: ocupa todo el ancho del contenedor */
        .tabla-detalle-compact { font-size: 0.75rem; width: 100%; table-layout: fixed; }
        .tabla-detalle-compact th, .tabla-detalle-compact td { padding: 4px 6px; vertical-align: middle; }
        .tabla-detalle-compact .form-control.compact { padding: 0.25rem 0.5rem; font-size: 0.75rem; min-height: 26px; border-radius: 0.2rem; }
        .tabla-detalle-compact textarea.compact { min-height: 36px; border-radius: 0.2rem; }
        .btn-add-row { padding: 0.3rem 0.6rem; font-size: 0.8rem; border-radius: 0.25rem; }
        .btn-quitar-fila { padding: 0.15rem 0.35rem; font-size: 0.7rem; line-height: 1; border-radius: 0.2rem; }
        /* Anchos de columnas: # reducida, Producto/Proveedor/Ubicación más anchos, Unidad/Dosis/Frascos/Edad reducidos, Quitar mínima */
        .tabla-detalle-compact th.col-num, .tabla-detalle-compact td.col-num { width: 28px; max-width: 28px; min-width: 28px; }
        .tabla-detalle-compact th.col-quitar, .tabla-detalle-compact td.col-quitar { width: 36px; max-width: 36px; min-width: 36px; }
        .tabla-detalle-compact .col-ubicacion { min-width: 72px; max-width: 90px; }
        .tabla-detalle-compact .col-producto { min-width: 260px; }
        .tabla-detalle-compact .col-proveedor { min-width: 200px; }
        .tabla-detalle-compact .col-producto .form-control,
        .tabla-detalle-compact .col-proveedor .form-control,
        .tabla-detalle-compact .col-producto textarea.compact,
        .tabla-detalle-compact .col-proveedor textarea.compact { min-width: 0; width: 100%; }
        /* Lupa mismo tamaño en producto y proveedor */
        .tabla-detalle-compact .btn-lupa-detalle { width: 28px; min-width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0; }
        /* Textarea producto/proveedor: multilínea y altura dinámica */
        .tabla-detalle-compact .col-producto .wrap-producto-proveedor,
        .tabla-detalle-compact .col-proveedor .wrap-producto-proveedor { display: flex; align-items: flex-start; gap: 4px; width: 100%; }
        .tabla-detalle-compact .col-producto textarea.compact.multiline,
        .tabla-detalle-compact .col-proveedor textarea.compact.multiline { resize: none; min-height: 36px; overflow-y: hidden; line-height: 1.3; white-space: pre-wrap; word-wrap: break-word; }
        /* Descripción vacuna: altura dinámica */
        .tabla-detalle-compact .td-descripcion-vacuna textarea.compact { resize: none; min-height: 36px; overflow-y: hidden; line-height: 1.3; white-space: pre-wrap; word-wrap: break-word; }
        .tabla-detalle-compact .col-unidad, .tabla-detalle-compact .col-uniddosis, .tabla-detalle-compact .col-frascos { min-width: 42px; max-width: 58px; width: 50px; }
        .tabla-detalle-compact .col-dosis { min-width: 55px; max-width: 75px; width: 65px; }
        .tabla-detalle-compact .col-edad { min-width: 58px; max-width: 72px; width: 65px; }
        .tabla-detalle-compact .col-unidad .form-control, .tabla-detalle-compact .col-dosis .form-control,
        .tabla-detalle-compact .col-uniddosis .form-control, .tabla-detalle-compact .col-frascos .form-control,
        .tabla-detalle-compact .col-edad .form-control { min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box; }
        #modalProveedorResultados { overflow-y: auto; overflow-x: hidden; max-height: 320px; min-height: 120px; }
        /* Modal buscar producto: scroll en resultados */
        #modalProductoResultados { overflow-y: auto; overflow-x: hidden; max-height: 320px; min-height: 120px; }
        #modalProductoResultados::-webkit-scrollbar { width: 8px; }
        #modalProductoResultados::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        #modalProductoResultados::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
        /* Cabecera compacta */
        .cabecera-compact .form-control { padding: 0.3rem 0.5rem; font-size: 0.8125rem; border-radius: 0.25rem; }
        .cabecera-compact label { font-size: 0.7rem; margin-bottom: 0.2rem; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border" id="formProgramaContainer">
        <div class="mb-6 bg-white border rounded-lg shadow-sm overflow-hidden">
            <form id="formPrograma" class="p-4">
                <div class="cabecera-compact space-y-3">
                    <!-- Fila 1: Tipo, Código, Descripción (siempre visibles) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Tipo de programa *</label>
                            <select id="tipo" name="codTipo" class="form-control" required>
                                <option value="">Seleccione...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Código</label>
                            <input type="text" id="codigo" name="codigo" class="form-control bg-gray-100" readonly>
                        </div>
                        <div >
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Nombre del programa *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Necropsia campaña 2026" required>
                        </div>
                        
                    </div>
                    <!-- Fila 2: Descripción y Despliegue (zona ya no se registra) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Descripción</label>
                            <input type="text" id="descripcion" name="descripcion" class="form-control" placeholder="Descripción" maxlength="500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Despliegue</label>
                            <input type="text" id="despliegue" name="despliegue" class="form-control" placeholder="Despliegue" maxlength="200" list="desplieguesList" autocomplete="off">
                            <datalist id="desplieguesList"><option value="GRS"><option value="Piloto"></datalist>
                        </div>
                    </div>
                </div>
                    <div id="bloqueDetalle" class="bloque-detalle mt-4 pt-4 border-t border-gray-200">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                            <span class="text-sm font-medium text-gray-700">Detalle del programa</span>
                            <button type="button" id="btnAgregarFila" class="btn-primary btn-add-row hidden">
                                <i class="fas fa-plus"></i> Agregar fila
                            </button>
                        </div>
                        <div id="solicitudesContainer" class="hidden mt-2 w-full overflow-x-auto overflow-y-visible">
                            <table class="w-full min-w-full border border-gray-200 rounded-lg tabla-detalle-compact" id="tablaSolicitudes">
                                <thead class="bg-gray-100" id="solicitudesThead"></thead>
                                <tbody id="solicitudesBody"></tbody>
                            </table>
                            <datalist id="ubicacionList">
                                <option value="Planta de Incubacion"><option value="Granja"><option value="Galpón"><option value="Piso"><option value="Techo">
                            </datalist>
                        </div>
                        <p id="solicitudesMsgTipo" class="hidden text-amber-600 text-xs mt-1">Seleccione primero el tipo de programa.</p>
                    </div>
                </div>
                <div class="mt-4 flex gap-2 justify-end border-t border-gray-200 pt-3">
                    <button type="button" id="btnLimpiarForm" class="px-3 py-1.5 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 text-xs font-medium">Limpiar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal buscar producto -->
    <div id="modalBuscarProducto" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Buscar producto</h3>
                <button type="button" id="btnCerrarModalProducto" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>
            <div class="p-4">
                <input type="text" id="modalProductoBuscar" class="form-control mb-3" placeholder="Escriba nombre o código del producto..." autocomplete="off">
                <div id="modalProductoResultados" class="border border-gray-200 rounded text-sm"></div>
            </div>
        </div>
    </div>
    <!-- Modal buscar proveedor (ccte) -->
    <div id="modalBuscarProveedor" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Buscar proveedor</h3>
                <button type="button" id="btnCerrarModalProveedor" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>
            <div class="p-4">
                <input type="text" id="modalProveedorBuscar" class="form-control mb-3" placeholder="Escriba nombre o código del proveedor..." autocomplete="off">
                <div id="modalProveedorResultados" class="border border-gray-200 rounded text-sm"></div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function cargarTipos() {
            return fetch('get_tipos_programa.php').then(r => r.json()).then(res => {
                if (!res.success) return;
                const sel = document.getElementById('tipo');
                sel.innerHTML = '<option value="">Seleccione tipo...</option>';
                (res.data || []).forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.codigo;
                    opt.textContent = t.nombre;
                    opt.dataset.nombre = t.nombre || '';
                    opt.dataset.sigla = (t.sigla || '').trim().toUpperCase();
                    opt.dataset.campos = t.campos ? JSON.stringify(t.campos) : '{}';
                    sel.appendChild(opt);
                });
            }).catch(() => {});
        }
        function generarCodigoPorSigla(sigla) {
            if (!sigla) { document.getElementById('codigo').value = ''; return; }
            fetch('generar_codigo_nec.php?sigla=' + encodeURIComponent(sigla))
                .then(r => r.json())
                .then(res => { document.getElementById('codigo').value = (res.success && res.codigo) ? res.codigo : ''; })
                .catch(() => { document.getElementById('codigo').value = ''; });
        }
        var solicitudesData = {};
        function getSiglaActual() {
            var tipo = document.getElementById('tipo');
            if (!tipo || !tipo.value) return '';
            var opt = tipo.options[tipo.selectedIndex];
            var s = (opt && opt.dataset.sigla) ? String(opt.dataset.sigla).toUpperCase() : '';
            if (s === 'NEC') s = 'NC';
            return s;
        }
        function getCamposActual() {
            var tipo = document.getElementById('tipo');
            if (!tipo || !tipo.value) return null;
            var opt = tipo.options[tipo.selectedIndex];
            if (!opt || !opt.dataset.campos) return null;
            try { return JSON.parse(opt.dataset.campos); } catch (e) { return null; }
        }
        function getColumnasFromCampos(campos) {
            if (!campos) return ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos', 'edad'];
            var cols = ['num'];
            if (campos.ubicacion === 1) cols.push('ubicacion');
            if (campos.producto === 1) cols.push('producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna');
            if (campos.unidades === 1 && cols.indexOf('unidad') === -1) cols.push('unidad');
            if (campos.unidad_dosis === 1) cols.push('unidadDosis');
            if (campos.numero_frascos === 1) cols.push('numeroFrascos');
            if (campos.edad_aplicacion === 1) cols.push('edad');
            if (campos.area_galpon === 1) cols.push('area_galpon');
            if (campos.cantidad_por_galpon === 1) cols.push('cantidad_por_galpon');
            if (cols.length === 1) cols.push('ubicacion', 'edad');
            return cols;
        }
        var LABELS = { num: '#', ubicacion: 'Ubicación', producto: 'Producto', proveedor: 'Proveedor', unidad: 'Unidad', dosis: 'Dosis', descripcion_vacuna: 'Descripcion', numeroFrascos: 'Nº frascos', edad: 'Edad', unidadDosis: 'Unid. dosis', area_galpon: 'Área galpón', cantidad_por_galpon: 'Cant. por galpón' };
        function buildThead(campos) {
            var thead = document.getElementById('solicitudesThead');
            if (!thead) return;
            var cols = getColumnasFromCampos(campos);
            var html = '<tr>';
            cols.forEach(function(k) {
                var ext = '';
                if (k === 'num') ext = ' col-num';
                else if (k === 'ubicacion') ext = ' col-ubicacion';
                else if (k === 'producto') ext = ' col-producto';
                else if (k === 'proveedor') ext = ' col-proveedor';
                else if (k === 'unidad') ext = ' col-unidad';
                else if (k === 'dosis') ext = ' col-dosis';
                else if (k === 'unidadDosis') ext = ' col-uniddosis';
                else if (k === 'numeroFrascos') ext = ' col-frascos';
                else if (k === 'edad') ext = ' col-edad';
                if (k === 'descripcion_vacuna') {
                    html += '<th id="th_descripcion_vacuna" class="th-descripcion-vacuna px-1.5 py-1 text-left border-b border-gray-200 font-semibold text-gray-600 text-xs' + ext + '" style="display:none">' + (LABELS[k] || k) + '</th>';
                } else {
                    html += '<th class="px-1.5 py-1 text-left border-b border-gray-200 font-semibold text-gray-600 text-xs' + ext + '">' + (LABELS[k] || k) + '</th>';
                }
            });
            html += '<th class="col-quitar px-1.5 py-1 text-center border-b border-gray-200 font-semibold text-gray-600 text-xs">Quitar</th></tr>';
            thead.innerHTML = html;
        }
        function updateVisibilidadColumnaDescripcion() {
            var th = document.getElementById('th_descripcion_vacuna');
            if (!th) return;
            var algunaVacuna = false;
            for (var key in solicitudesData) { if (solicitudesData[key].esVacuna) { algunaVacuna = true; break; } }
            th.style.display = algunaVacuna ? '' : 'none';
        }
        function buildRowHtml(campos, i) {
            var cols = getColumnasFromCampos(campos);
            var parts = [];
            var cellClass = 'px-1.5 py-1';
            var inputClass = 'form-control compact';
            cols.forEach(function(k) {
                if (k === 'num') parts.push('<td class="col-num ' + cellClass + ' text-gray-600 text-xs">' + (i + 1) + '</td>');
                else if (k === 'ubicacion') parts.push('<td class="col-ubicacion ' + cellClass + '"><input type="text" id="ubicacion_' + i + '" name="ubicacion_' + i + '" class="' + inputClass + '" list="ubicacionList" placeholder="Ubicación" maxlength="200"></td>');
                else if (k === 'producto') parts.push('<td class="col-producto ' + cellClass + '"><input type="hidden" id="producto_' + i + '" name="codProducto_' + i + '" value=""><div class="wrap-producto-proveedor"><textarea id="producto_text_' + i + '" class="' + inputClass + ' compact multiline bg-gray-100" readonly placeholder="Producto..." rows="2"></textarea><button type="button" class="btn-lupa-detalle btn-buscar-celda border border-gray-300 text-gray-600 hover:bg-gray-100 rounded" data-row="' + i + '" title="Buscar producto"><i class="fas fa-search"></i></button></div></td>');
                else if (k === 'proveedor') parts.push('<td class="col-proveedor ' + cellClass + '"><input type="hidden" id="codProveedor_' + i + '" name="codProveedor_' + i + '" value=""><div class="wrap-producto-proveedor"><textarea id="proveedor_' + i + '" class="' + inputClass + ' compact multiline bg-gray-100" readonly placeholder="Proveedor" rows="2"></textarea><button type="button" class="btn-lupa-detalle btn-buscar-proveedor border border-gray-300 text-gray-600 hover:bg-gray-100 rounded" data-row="' + i + '" title="Buscar proveedor"><i class="fas fa-search"></i></button></div></td>');
                else if (k === 'unidad') parts.push('<td class="col-unidad ' + cellClass + '"><input type="text" id="unidad_ro_' + i + '" name="unidad_' + i + '" class="' + inputClass + '" placeholder="Unidad" maxlength="50"></td>');
                else if (k === 'dosis') parts.push('<td class="col-dosis ' + cellClass + '"><input type="text" id="dosis_' + i + '" name="dosis_' + i + '" class="' + inputClass + '" placeholder="Dosis"></td>');
                else if (k === 'descripcion_vacuna') parts.push('<td id="td_descripcion_vacuna_' + i + '" class="' + cellClass + ' td-descripcion-vacuna" style="display:none;min-width:160px;"><textarea id="descripcion_vacuna_ro_' + i + '" class="' + inputClass + ' compact bg-gray-100 descripcion-vacuna-ta" readonly style="min-width:140px;"></textarea></td>');
                else if (k === 'numeroFrascos') parts.push('<td class="col-frascos ' + cellClass + '"><input type="text" id="numeroFrascos_' + i + '" name="numeroFrascos_' + i + '" class="' + inputClass + '" placeholder="Nº" maxlength="50"></td>');
                else if (k === 'edad') parts.push('<td class="col-edad ' + cellClass + '" title="Una edad (ej: 2) o varias separadas por coma (ej: 2,4)"><input type="text" id="edad_' + i + '" name="edad_' + i + '" class="' + inputClass + '" placeholder="Ej: 2 o 2,4" maxlength="50"></td>');
                else if (k === 'unidadDosis') parts.push('<td class="col-uniddosis ' + cellClass + '"><input type="text" id="unidadDosis_' + i + '" name="unidadDosis_' + i + '" class="' + inputClass + '" placeholder="Unid." maxlength="50"></td>');
                else if (k === 'area_galpon') parts.push('<td class="' + cellClass + '"><input type="number" id="area_galpon_' + i + '" name="area_galpon_' + i + '" class="' + inputClass + '" min="0" placeholder="Área" style="min-width:50px"></td>');
                else if (k === 'cantidad_por_galpon') parts.push('<td class="' + cellClass + '"><input type="number" id="cantidad_por_galpon_' + i + '" name="cantidad_por_galpon_' + i + '" class="' + inputClass + '" min="0" placeholder="Cant." style="min-width:50px"></td>');
            });
            parts.push('<td class="col-quitar ' + cellClass + ' text-center"><button type="button" class="btn-quitar-fila border border-red-200 text-red-600 hover:bg-red-50 rounded" data-row="' + i + '" title="Quitar fila"><i class="fas fa-trash-alt"></i></button></td>');
            return parts.join('');
        }
        document.getElementById('tipo').addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (this.value) {
                var sigla = (opt && opt.dataset.sigla) ? opt.dataset.sigla : '';
                generarCodigoPorSigla(sigla);
                document.getElementById('btnAgregarFila').classList.remove('hidden');
                document.getElementById('solicitudesContainer').classList.remove('hidden');
                currentCampos = getCamposActual();
                buildThead(currentCampos);
                var tbody = document.getElementById('solicitudesBody');
                if (tbody) tbody.innerHTML = '';
                solicitudesData = {};
                var msgTipo = document.getElementById('solicitudesMsgTipo');
                if (msgTipo) msgTipo.classList.add('hidden');
            } else {
                document.getElementById('codigo').value = '';
                document.getElementById('btnAgregarFila').classList.add('hidden');
                document.getElementById('solicitudesContainer').classList.add('hidden');
                document.getElementById('solicitudesBody').innerHTML = '';
                document.getElementById('solicitudesThead').innerHTML = '';
                solicitudesData = {};
            }
        });
        var currentCampos = null;
        var modalProductoRowIndex = -1;
        var modalProveedorRowIndex = -1;
        var modalProductoSearchTimer = null;
        var modalProveedorSearchTimer = null;
        function autoResizeTextarea(ta) {
            if (!ta || !(ta.classList.contains('multiline') || ta.classList.contains('descripcion-vacuna-ta'))) return;
            ta.style.height = 'auto';
            var maxH = ta.classList.contains('descripcion-vacuna-ta') ? 280 : 120;
            ta.style.height = Math.max(36, Math.min(ta.scrollHeight, maxH)) + 'px';
        }
        document.getElementById('solicitudesContainer').addEventListener('click', function(e) {
            var btnProd = e.target.closest('.btn-buscar-celda');
            if (btnProd) {
                var row = parseInt(btnProd.getAttribute('data-row'), 10);
                if (isNaN(row)) return;
                modalProductoRowIndex = row;
                document.getElementById('modalProductoBuscar').value = '';
                document.getElementById('modalProductoResultados').innerHTML = '<p class="text-gray-500 text-sm p-2">Escriba para buscar producto.</p>';
                document.getElementById('modalBuscarProducto').classList.remove('hidden');
                setTimeout(function() { document.getElementById('modalProductoBuscar').focus(); }, 100);
                return;
            }
            var btnProv = e.target.closest('.btn-buscar-proveedor');
            if (btnProv) {
                var row = parseInt(btnProv.getAttribute('data-row'), 10);
                if (isNaN(row)) return;
                modalProveedorRowIndex = row;
                document.getElementById('modalProveedorBuscar').value = '';
                document.getElementById('modalProveedorResultados').innerHTML = '<p class="text-gray-500 text-sm p-2">Escriba para buscar proveedor.</p>';
                document.getElementById('modalBuscarProveedor').classList.remove('hidden');
                setTimeout(function() { document.getElementById('modalProveedorBuscar').focus(); }, 100);
            }
        });
        document.getElementById('btnCerrarModalProducto').addEventListener('click', function() {
            document.getElementById('modalBuscarProducto').classList.add('hidden');
            modalProductoRowIndex = -1;
        });
        document.getElementById('modalBuscarProducto').addEventListener('click', function(e) {
            if (e.target.id === 'modalBuscarProducto') { document.getElementById('modalBuscarProducto').classList.add('hidden'); modalProductoRowIndex = -1; }
        });
        document.getElementById('modalProductoBuscar').addEventListener('input', function() {
            var q = (this.value || '').trim();
            var cont = document.getElementById('modalProductoResultados');
            if (modalProductoSearchTimer) clearTimeout(modalProductoSearchTimer);
            if (!q) { cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Escriba para buscar producto.</p>'; return; }
            cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Buscando...</p>';
            modalProductoSearchTimer = setTimeout(function() {
                fetch('get_productos_programa.php?q=' + encodeURIComponent(q)).then(function(r) { return r.json(); }).then(function(data) {
                    if (!data.success || !data.results || !data.results.length) {
                        cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Sin resultados.</p>';
                        return;
                    }
                    var html = '';
                    data.results.forEach(function(item) {
                        var cod = item.codigo || item.id;
                        var desc = (item.descri || item.text || '').replace(/^[^\s-]+\s*-\s*/, '');
                        var esc = function(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
                        var labelHtml = '<strong>' + esc(cod) + '</strong> - ' + esc(desc);
                        html += '<div class="modal-producto-item p-2 border-b border-gray-100 hover:bg-gray-100 cursor-pointer text-sm" data-id="' + (item.id || '').replace(/"/g, '&quot;') + '" data-codigo="' + (cod || '').replace(/"/g, '&quot;') + '" data-descri="' + (desc || '').replace(/"/g, '&quot;') + '">' + labelHtml + '</div>';
                    });
                    cont.innerHTML = html;
                    cont.querySelectorAll('.modal-producto-item').forEach(function(el) {
                        el.onclick = function() {
                            var id = this.getAttribute('data-id');
                            var codigo = this.getAttribute('data-codigo');
                            var descri = this.getAttribute('data-descri');
                            var text = (codigo || '') + (descri ? '\n' + descri : '');
                            var row = modalProductoRowIndex;
                            var inpCod = document.getElementById('producto_' + row);
                            var inpText = document.getElementById('producto_text_' + row);
                            if (inpCod) inpCod.value = id || '';
                            if (inpText) { inpText.value = text; autoResizeTextarea(inpText); }
                            document.getElementById('modalBuscarProducto').classList.add('hidden');
                            modalProductoRowIndex = -1;
                            if (id) onProductoChange(row);
                        };
                    });
                }).catch(function() { cont.innerHTML = '<p class="text-red-500 text-sm p-2">Error al buscar.</p>'; });
            }, 250);
        });
        document.getElementById('btnCerrarModalProveedor').addEventListener('click', function() {
            document.getElementById('modalBuscarProveedor').classList.add('hidden');
            modalProveedorRowIndex = -1;
        });
        document.getElementById('modalBuscarProveedor').addEventListener('click', function(e) {
            if (e.target.id === 'modalBuscarProveedor') { document.getElementById('modalBuscarProveedor').classList.add('hidden'); modalProveedorRowIndex = -1; }
        });
        document.getElementById('modalProveedorBuscar').addEventListener('input', function() {
            var q = (this.value || '').trim();
            var cont = document.getElementById('modalProveedorResultados');
            if (modalProveedorSearchTimer) clearTimeout(modalProveedorSearchTimer);
            if (!q) { cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Escriba para buscar proveedor.</p>'; return; }
            cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Buscando...</p>';
            modalProveedorSearchTimer = setTimeout(function() {
                fetch('get_ccte_lista.php?q=' + encodeURIComponent(q)).then(function(r) { return r.json(); }).then(function(data) {
                    if (!data.success || !data.data || !data.data.length) {
                        cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Sin resultados.</p>';
                        return;
                    }
                    var html = '';
                    var esc = function(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
                    data.data.forEach(function(item) {
                        var cod = item.codigo || '';
                        var nom = item.nombre || '';
                        var labelHtml = '<strong>' + esc(cod) + '</strong> - ' + esc(nom);
                        html += '<div class="modal-proveedor-item p-2 border-b border-gray-100 hover:bg-gray-100 cursor-pointer text-sm" data-codigo="' + (cod + '').replace(/"/g, '&quot;') + '" data-nombre="' + (nom + '').replace(/"/g, '&quot;') + '">' + labelHtml + '</div>';
                    });
                    cont.innerHTML = html;
                    cont.querySelectorAll('.modal-proveedor-item').forEach(function(el) {
                        el.onclick = function() {
                            var codigo = this.getAttribute('data-codigo') || '';
                            var nombre = this.getAttribute('data-nombre') || '';
                            var row = modalProveedorRowIndex;
                            var inpCod = document.getElementById('codProveedor_' + row);
                            var inpNom = document.getElementById('proveedor_' + row);
                            if (inpCod) inpCod.value = codigo;
                            if (inpNom) {
                                inpNom.value = codigo + (nombre ? '\n' + nombre : '');
                                autoResizeTextarea(inpNom);
                            }
                            document.getElementById('modalBuscarProveedor').classList.add('hidden');
                            modalProveedorRowIndex = -1;
                        };
                    });
                }).catch(function() { cont.innerHTML = '<p class="text-red-500 text-sm p-2">Error al buscar.</p>'; });
            }, 250);
        });
        function onProductoChange(rowIndex) {
            var inp = document.getElementById('producto_' + rowIndex);
            if (!inp || !inp.value) return;
            if (!solicitudesData[rowIndex]) solicitudesData[rowIndex] = {};
            fetch('get_datos_producto_programa.php?codigo=' + encodeURIComponent(inp.value)).then(function(r) { return r.json(); }).then(function(data) {
                if (!data.success) return;
                solicitudesData[rowIndex].codProveedor = data.codProveedor || '';
                solicitudesData[rowIndex].nomProducto = data.nomProducto || '';
                solicitudesData[rowIndex].dosis = data.dosis || '';
                solicitudesData[rowIndex].esVacuna = data.esVacuna || false;
                var desc = (data.descripcionVacuna || '').trim();
                var descTexto = data.esVacuna && desc ? 'Contra\n' + desc.split(',').map(function(s) { return '- ' + s.trim(); }).filter(Boolean).join('\n') : desc || '';
                solicitudesData[rowIndex].descripcionVacuna = descTexto;
                var codProv = document.getElementById('codProveedor_' + rowIndex);
                if (codProv) codProv.value = data.codProveedor || '';
                var prov = document.getElementById('proveedor_' + rowIndex);
                if (prov) {
                    prov.value = (data.codProveedor || '') + (data.nomProveedor ? '\n' + data.nomProveedor : '');
                    autoResizeTextarea(prov);
                }
                var inpProdText = document.getElementById('producto_text_' + rowIndex);
                if (inpProdText && data.nomProducto) {
                    var codProd = document.getElementById('producto_' + rowIndex);
                    var cod = (codProd && codProd.value) ? codProd.value : (data.codProducto || '');
                    inpProdText.value = cod + '\n' + data.nomProducto;
                    autoResizeTextarea(inpProdText);
                }
                var unid = document.getElementById('unidad_ro_' + rowIndex); if (unid) unid.value = data.unidad || '';
                var dosisInp = document.getElementById('dosis_' + rowIndex); if (dosisInp) dosisInp.value = data.dosis || '';
                var tdDesc = document.getElementById('td_descripcion_vacuna_' + rowIndex);
                var descVac = document.getElementById('descripcion_vacuna_ro_' + rowIndex);
                if (data.esVacuna) {
                    if (tdDesc) tdDesc.style.display = '';
                    if (descVac) { descVac.value = descTexto; autoResizeTextarea(descVac); }
                    var thDesc = document.getElementById('th_descripcion_vacuna'); if (thDesc) thDesc.style.display = '';
                } else {
                    if (tdDesc) tdDesc.style.display = 'none';
                    if (descVac) descVac.value = '';
                    updateVisibilidadColumnaDescripcion();
                }
                var ud = document.getElementById('unidadDosis_' + rowIndex); var nf = document.getElementById('numeroFrascos_' + rowIndex);
                if (ud && nf) {
                    var sigla = getSiglaActual();
                    if (sigla === 'PL' || sigla === 'GR') { if (data.esVacuna) { ud.disabled = false; ud.value = ''; nf.disabled = false; nf.value = ''; } else { ud.disabled = true; ud.value = ''; nf.disabled = true; nf.value = ''; } }
                    else { ud.disabled = false; nf.disabled = false; }
                }
            }).catch(function() {});
        }
        function adjustSolicitudesRows(count) {
            var tbody = document.getElementById('solicitudesBody');
            var container = document.getElementById('solicitudesContainer');
            var msgTipo = document.getElementById('solicitudesMsgTipo');
            if (!tbody) return;
            currentCampos = getCamposActual();
            if (count < 1) {
                tbody.innerHTML = '';
                return;
            }
            if (!document.getElementById('tipo').value) {
                if (msgTipo) { msgTipo.classList.remove('hidden'); msgTipo.textContent = 'Seleccione primero el tipo de programa.'; }
                container.classList.add('hidden');
                return;
            }
            if (msgTipo) msgTipo.classList.add('hidden');
            container.classList.remove('hidden');
            if (!document.getElementById('solicitudesThead').innerHTML) buildThead(currentCampos);
            var current = tbody.querySelectorAll('tr').length;
            var sigla = getSiglaActual();
            if (count > current) {
                for (var i = current; i < count; i++) {
                    solicitudesData[i] = solicitudesData[i] || {};
                    var tr = document.createElement('tr');
                    tr.className = 'border-b border-gray-200';
                    tr.innerHTML = buildRowHtml(currentCampos, i);
                    tbody.appendChild(tr);
                    var inpUb = document.getElementById('ubicacion_' + i); if (inpUb && solicitudesData[i].ubicacion) inpUb.value = solicitudesData[i].ubicacion;
                    var inpDosis = document.getElementById('dosis_' + i); if (inpDosis && solicitudesData[i].dosis) inpDosis.value = solicitudesData[i].dosis;
                    var inpCodProv = document.getElementById('codProveedor_' + i); var inpProv = document.getElementById('proveedor_' + i);
                    if (inpCodProv && solicitudesData[i].codProveedor) inpCodProv.value = solicitudesData[i].codProveedor;
                    if (inpProv) {
                        var pv = solicitudesData[i].nomProveedor || '';
                        inpProv.value = (pv.indexOf('\n') !== -1) ? pv : ((solicitudesData[i].codProveedor || '') + (pv ? '\n' + pv : ''));
                        autoResizeTextarea(inpProv);
                    }
                    var inpDescVac = document.getElementById('descripcion_vacuna_ro_' + i);
                    if (inpDescVac && solicitudesData[i].descripcionVacuna) { inpDescVac.value = solicitudesData[i].descripcionVacuna; autoResizeTextarea(inpDescVac); }
                    var tdDescI = document.getElementById('td_descripcion_vacuna_' + i); if (tdDescI) tdDescI.style.display = solicitudesData[i].esVacuna ? '' : 'none';
                    var inpEdad = document.getElementById('edad_' + i); if (inpEdad && solicitudesData[i].edad !== undefined && solicitudesData[i].edad !== '') inpEdad.value = String(solicitudesData[i].edad);
                    var ud = document.getElementById('unidadDosis_' + i); var nf = document.getElementById('numeroFrascos_' + i);
                    if (sigla === 'PL' || sigla === 'GR') { if (ud) ud.disabled = true; if (nf) nf.disabled = true; }
                    var inpProd = document.getElementById('producto_' + i); var inpProdText = document.getElementById('producto_text_' + i);
                    if (inpProd && solicitudesData[i].codProducto) inpProd.value = solicitudesData[i].codProducto;
                    if (inpProdText) {
                        var np = solicitudesData[i].nomProducto || '';
                        inpProdText.value = (np.indexOf('\n') !== -1) ? np : ((solicitudesData[i].codProducto || '') + (np ? '\n' + np : ''));
                        autoResizeTextarea(inpProdText);
                    }
                }
                tbody.querySelectorAll('.btn-quitar-fila').forEach(function(btn) {
                    var rowIdx = parseInt(btn.getAttribute('data-row'), 10);
                    btn.onclick = function() { quitarFila(rowIdx); };
                });
            } else if (count < current) {
                for (var j = current - 1; j >= count; j--) {
                    var trs = tbody.querySelectorAll('tr');
                    if (trs[j]) trs[j].remove();
                    delete solicitudesData[j];
                }
            } else {
                for (var i = 0; i < count; i++) {
                    var tr = tbody.querySelectorAll('tr')[i];
                    if (tr) {
                        tr.innerHTML = buildRowHtml(currentCampos, i);
                        var inpUb = document.getElementById('ubicacion_' + i); if (inpUb && solicitudesData[i] && solicitudesData[i].ubicacion) inpUb.value = solicitudesData[i].ubicacion;
                        var inpDosis = document.getElementById('dosis_' + i); if (inpDosis && solicitudesData[i] && solicitudesData[i].dosis) inpDosis.value = solicitudesData[i].dosis;
                        var inpCodProv = document.getElementById('codProveedor_' + i); var inpProv = document.getElementById('proveedor_' + i);
                        if (inpCodProv && solicitudesData[i].codProveedor) inpCodProv.value = solicitudesData[i].codProveedor;
                        if (inpProv) {
                            var pv = (solicitudesData[i] && solicitudesData[i].nomProveedor) ? solicitudesData[i].nomProveedor : '';
                            inpProv.value = (pv.indexOf('\n') !== -1) ? pv : ((solicitudesData[i].codProveedor || '') + (pv ? '\n' + pv : ''));
                            autoResizeTextarea(inpProv);
                        }
                        var inpDescVac = document.getElementById('descripcion_vacuna_ro_' + i);
                        if (inpDescVac && solicitudesData[i] && solicitudesData[i].descripcionVacuna) { inpDescVac.value = solicitudesData[i].descripcionVacuna; autoResizeTextarea(inpDescVac); }
                        var tdDescI = document.getElementById('td_descripcion_vacuna_' + i); if (tdDescI && solicitudesData[i]) tdDescI.style.display = solicitudesData[i].esVacuna ? '' : 'none';
                        var inpEdad = document.getElementById('edad_' + i); if (inpEdad && solicitudesData[i] && solicitudesData[i].edad !== undefined && solicitudesData[i].edad !== '') inpEdad.value = String(solicitudesData[i].edad);
                        var inpProd = document.getElementById('producto_' + i); var inpProdText = document.getElementById('producto_text_' + i);
                        if (inpProd && solicitudesData[i].codProducto) inpProd.value = solicitudesData[i].codProducto;
                        if (inpProdText) {
                            var np = (solicitudesData[i] && solicitudesData[i].nomProducto) ? solicitudesData[i].nomProducto : '';
                            inpProdText.value = (np.indexOf('\n') !== -1) ? np : ((solicitudesData[i].codProducto || '') + (np ? '\n' + np : ''));
                            autoResizeTextarea(inpProdText);
                        }
                    }
                }
                tbody.querySelectorAll('.btn-quitar-fila').forEach(function(btn) {
                    var rowIdx = parseInt(btn.getAttribute('data-row'), 10);
                    btn.onclick = function() { quitarFila(rowIdx); };
                });
            }
            updateVisibilidadColumnaDescripcion();
        }
        /** Parsea campo edad: "2", "2,4", "2, 4" -> array de números [2] o [2,4]. Cada edad genera un detalle; posDetalle es el número de detalle global (1, 2, 3, 4...). */
        function parseEdades(edadStr) {
            if (typeof edadStr !== 'string') edadStr = '';
            var parts = edadStr.split(',').map(function(s) { return parseInt(s.trim(), 10); }).filter(function(n) { return !isNaN(n) && n >= 0 && n <= 45; });
            return parts.length ? parts : [0];
        }
        /** Lee el valor de un campo del detalle para la fila s según la columna mostrada (id del input). Solo se usan columnas que están en cols (campos con valor 1). */
        function leerValorDetalle(colKey, s) {
            var el;
            switch (colKey) {
                case 'ubicacion': el = document.getElementById('ubicacion_' + s); return el ? (el.value || '').trim() : '';
                case 'producto': el = document.getElementById('producto_' + s); return el ? (el.value || '').trim() : '';
                case 'proveedor': el = document.getElementById('codProveedor_' + s); return el ? (el.value || '').trim() : '';
                case 'unidad': el = document.getElementById('unidad_ro_' + s); return el ? (el.value || '').trim() : '';
                case 'dosis': el = document.getElementById('dosis_' + s); return el ? (el.value || '').trim() : '';
                case 'unidadDosis': el = document.getElementById('unidadDosis_' + s); return el ? (el.value || '').trim() : '';
                case 'numeroFrascos': el = document.getElementById('numeroFrascos_' + s); return el ? (el.value || '').trim() : '';
                case 'edad': el = document.getElementById('edad_' + s); return el ? String(el.value || '').trim() : '';
                case 'descripcion_vacuna': el = document.getElementById('descripcion_vacuna_ro_' + s); return el ? (el.value || '').trim() : '';
                case 'area_galpon': el = document.getElementById('area_galpon_' + s); return el ? (parseInt(el.value, 10) || null) : null;
                case 'cantidad_por_galpon': el = document.getElementById('cantidad_por_galpon_' + s); return el ? (parseInt(el.value, 10) || null) : null;
                default: return '';
            }
        }
        function getRowDataFromRowIndex(s, cols) {
            var inpText = document.getElementById('producto_text_' + s);
            var nomProducto = (inpText && inpText.value) ? inpText.value.trim() : '';
            if (solicitudesData[s] && solicitudesData[s].nomProducto) nomProducto = solicitudesData[s].nomProducto;
            var obj = { ubicacion: '', codProducto: '', nomProducto: nomProducto, codProveedor: '', nomProveedor: '', unidades: '', dosis: '', unidadDosis: '', numeroFrascos: '', edad: '', descripcionVacuna: '', esVacuna: !!(solicitudesData[s] && solicitudesData[s].esVacuna), areaGalpon: null, cantidadPorGalpon: null };
            if (cols.indexOf('ubicacion') !== -1) obj.ubicacion = leerValorDetalle('ubicacion', s);
            if (cols.indexOf('producto') !== -1) { obj.codProducto = leerValorDetalle('producto', s); obj.nomProducto = nomProducto; }
            if (cols.indexOf('proveedor') !== -1) { obj.codProveedor = leerValorDetalle('proveedor', s); obj.nomProveedor = (document.getElementById('proveedor_' + s) && document.getElementById('proveedor_' + s).value) ? document.getElementById('proveedor_' + s).value.trim() : ''; }
            if (cols.indexOf('unidad') !== -1) obj.unidades = leerValorDetalle('unidad', s);
            if (cols.indexOf('dosis') !== -1) obj.dosis = leerValorDetalle('dosis', s);
            if (cols.indexOf('unidadDosis') !== -1) obj.unidadDosis = leerValorDetalle('unidadDosis', s);
            if (cols.indexOf('numeroFrascos') !== -1) obj.numeroFrascos = leerValorDetalle('numeroFrascos', s);
            if (cols.indexOf('edad') !== -1) obj.edad = leerValorDetalle('edad', s);
            if (cols.indexOf('descripcion_vacuna') !== -1) obj.descripcionVacuna = leerValorDetalle('descripcion_vacuna', s);
            if (cols.indexOf('area_galpon') !== -1) obj.areaGalpon = leerValorDetalle('area_galpon', s);
            if (cols.indexOf('cantidad_por_galpon') !== -1) obj.cantidadPorGalpon = leerValorDetalle('cantidad_por_galpon', s);
            return obj;
        }
        /** Devuelve un objeto por fila de la tabla (para quitar/restaurar), con edad como string. Solo incluye valores de columnas mostradas (campos con valor 1). */
        function getRowDataForTable() {
            var tbody = document.getElementById('solicitudesBody');
            if (!tbody) return [];
            var rows = tbody.querySelectorAll('tr');
            var campos = getCamposActual();
            var cols = getColumnasFromCampos(campos || {});
            var out = [];
            for (var s = 0; s < rows.length; s++) {
                var row = getRowDataFromRowIndex(s, cols);
                out.push({ ubicacion: row.ubicacion, codProducto: row.codProducto, nomProducto: row.nomProducto, codProveedor: row.codProveedor, nomProveedor: row.nomProveedor, unidades: row.unidades, dosis: row.dosis, unidadDosis: row.unidadDosis, numeroFrascos: row.numeroFrascos, edad: row.edad, descripcionVacuna: row.descripcionVacuna, esVacuna: row.esVacuna, areaGalpon: row.areaGalpon, cantidadPorGalpon: row.cantidadPorGalpon });
            }
            return out;
        }
        /** Construye el array de detalles para enviar al backend. Solo incluye valores de columnas mostradas (campos con valor 1 en el tipo). */
        function getDetallesFromForm() {
            var tbody = document.getElementById('solicitudesBody');
            if (!tbody) return [];
            var rows = tbody.querySelectorAll('tr');
            var campos = getCamposActual();
            var cols = getColumnasFromCampos(campos || {});
            var out = [];
            var posDetalle = 0;
            for (var s = 0; s < rows.length; s++) {
                var row = getRowDataFromRowIndex(s, cols);
                var edades = parseEdades(row.edad);
                var base = {
                    ubicacion: row.ubicacion,
                    codProducto: row.codProducto,
                    nomProducto: row.nomProducto,
                    codProveedor: row.codProveedor,
                    nomProveedor: row.nomProveedor,
                    unidades: row.unidades,
                    dosis: row.dosis,
                    unidadDosis: row.unidadDosis,
                    numeroFrascos: row.numeroFrascos,
                    descripcionVacuna: row.descripcionVacuna,
                    esVacuna: row.esVacuna,
                    areaGalpon: row.areaGalpon,
                    cantidadPorGalpon: row.cantidadPorGalpon
                };
                edades.forEach(function(edadVal) {
                    posDetalle++;
                    out.push(Object.assign({}, base, { edad: edadVal, posDetalle: posDetalle }));
                });
            }
            return out;
        }
        function quitarFila(index) {
            var data = getRowDataForTable();
            data.splice(index, 1);
            solicitudesData = {};
            data.forEach(function(d, i) { solicitudesData[i] = d; });
            var tbody = document.getElementById('solicitudesBody');
            if (tbody) tbody.innerHTML = '';
            adjustSolicitudesRows(data.length);
        }
        document.getElementById('btnAgregarFila').addEventListener('click', function() {
            var tbody = document.getElementById('solicitudesBody');
            if (!document.getElementById('tipo').value) {
                Swal.fire({ icon: 'warning', title: 'Aviso', text: 'Seleccione primero el tipo de programa.' });
                return;
            }
            var current = tbody ? tbody.querySelectorAll('tr').length : 0;
            if (current >= 50) return;
            adjustSolicitudesRows(current + 1);
        });

        document.getElementById('btnLimpiarForm').addEventListener('click', function() {
            document.getElementById('formPrograma').reset();
            document.getElementById('codigo').value = '';
            document.getElementById('solicitudesBody').innerHTML = '';
            document.getElementById('solicitudesThead').innerHTML = '';
            document.getElementById('btnAgregarFila').classList.add('hidden');
            document.getElementById('solicitudesContainer').classList.add('hidden');
            var msgTipo = document.getElementById('solicitudesMsgTipo');
            if (msgTipo) msgTipo.classList.add('hidden');
            solicitudesData = {};
        });

        document.getElementById('formPrograma').addEventListener('submit', function(e) {
            e.preventDefault();
            var tipo = document.getElementById('tipo');
            var codTipo = tipo.value;
            var nomTipo = tipo.options[tipo.selectedIndex] ? tipo.options[tipo.selectedIndex].textContent : '';
            var codigo = document.getElementById('codigo').value.trim();
            var nombre = document.getElementById('nombre').value.trim();
            var despliegue = document.getElementById('despliegue') ? document.getElementById('despliegue').value.trim() : '';
            var descripcion = document.getElementById('descripcion') ? document.getElementById('descripcion').value.trim() : '';
            if (!codTipo || !codigo || !nombre) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Complete tipo, código y nombre.' });
                return;
            }
            var detalles = getDetallesFromForm();
            if (detalles.length < 1) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Debe haber al menos un detalle. Agregue una fila y complete edad (ej: 2 o 2,4).' });
                return;
            }
            var sigla = getSiglaActual();
            var payload = { codigo: codigo, nombre: nombre, codTipo: parseInt(codTipo, 10), nomTipo: nomTipo, sigla: sigla, despliegue: despliegue, descripcion: descripcion, detalles: detalles };
            fetch('guardar_programa.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Guardado', text: res.message }).then(function() {
                            document.getElementById('formPrograma').reset();
                            document.getElementById('codigo').value = '';
                            document.getElementById('solicitudesBody').innerHTML = '';
                            document.getElementById('solicitudesThead').innerHTML = '';
                            document.getElementById('btnAgregarFila').classList.add('hidden');
                            document.getElementById('solicitudesContainer').classList.add('hidden');
                            solicitudesData = {};
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo guardar.' });
                    }
                })
                .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }); });
        });

        cargarTipos();
    </script>
</body>
</html>
