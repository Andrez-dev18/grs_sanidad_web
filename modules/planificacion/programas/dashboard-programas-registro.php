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
        .btn-quitar-fila { padding: 0.15rem 0.4rem; font-size: 0.7rem; line-height: 1; border-radius: 0.2rem; }
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
                    <!-- Fila 2: Nombre del programa, Zona y Despliegue en una misma fila (siempre visibles) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Descripción</label>
                            <input type="text" id="descripcion" name="descripcion" class="form-control" placeholder="Descripción" maxlength="500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Zona</label>
                            <input type="text" id="zona" name="zona" class="form-control" placeholder="La Joya" maxlength="100" list="zonasList" autocomplete="off">
                            <datalist id="zonasList"><option value="Mollendo"><option value="La Joya"></datalist>
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
                if (k === 'descripcion_vacuna') {
                    html += '<th id="th_descripcion_vacuna" class="th-descripcion-vacuna px-1.5 py-1 text-left border-b border-gray-200 font-semibold text-gray-600 text-xs" style="display:none">' + (LABELS[k] || k) + '</th>';
                } else {
                    html += '<th class="px-1.5 py-1 text-left border-b border-gray-200 font-semibold text-gray-600 text-xs">' + (LABELS[k] || k) + '</th>';
                }
            });
            html += '<th class="px-1.5 py-1 text-center border-b border-gray-200 font-semibold text-gray-600 text-xs w-14">Quitar</th></tr>';
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
            var estiloEdad = 'min-width:70px', anchoUbicacion = 'min-width:140px', anchoProveedor = 'min-width:140px', anchoUnidad = 'min-width:80px', anchoDosis = 'min-width:90px', anchoUnidDosis = 'min-width:80px', anchoFrascos = 'min-width:72px';
            cols.forEach(function(k) {
                if (k === 'num') parts.push('<td class="' + cellClass + ' text-gray-600 text-xs">' + (i + 1) + '</td>');
                else if (k === 'ubicacion') parts.push('<td class="' + cellClass + '" style="' + anchoUbicacion + '"><input type="text" id="ubicacion_' + i + '" name="ubicacion_' + i + '" class="' + inputClass + '" placeholder="Ubicación" maxlength="200"></td>');
                else if (k === 'producto') parts.push('<td class="' + cellClass + '" style="min-width:200px;"><select id="producto_' + i + '" class="' + inputClass + ' select-producto-programa" name="codProducto_' + i + '" style="width:100%;min-width:180px;"><option value="">Producto...</option></select></td>');
                else if (k === 'proveedor') parts.push('<td class="' + cellClass + '" style="' + anchoProveedor + '"><input type="text" id="proveedor_ro_' + i + '" class="' + inputClass + ' bg-gray-100" readonly placeholder="-"></td>');
                else if (k === 'unidad') parts.push('<td class="' + cellClass + '" style="' + anchoUnidad + '"><input type="text" id="unidad_ro_' + i + '" class="' + inputClass + ' bg-gray-100" readonly placeholder="-"></td>');
                else if (k === 'dosis') parts.push('<td class="' + cellClass + '" style="' + anchoDosis + '"><input type="text" id="dosis_ro_' + i + '" class="' + inputClass + ' bg-gray-100" readonly placeholder="-"></td>');
                else if (k === 'descripcion_vacuna') parts.push('<td id="td_descripcion_vacuna_' + i + '" class="' + cellClass + ' td-descripcion-vacuna" style="display:none;min-width:160px;"><textarea id="descripcion_vacuna_ro_' + i + '" class="' + inputClass + ' compact bg-gray-100" readonly rows="2" style="min-width:140px;white-space:pre-wrap;"></textarea></td>');
                else if (k === 'numeroFrascos') parts.push('<td class="' + cellClass + '" style="' + anchoFrascos + '"><input type="text" id="numeroFrascos_' + i + '" name="numeroFrascos_' + i + '" class="' + inputClass + '" placeholder="Nº" maxlength="50"></td>');
                else if (k === 'edad') parts.push('<td class="' + cellClass + '"><input type="number" id="edad_' + i + '" name="edad_' + i + '" class="' + inputClass + '" min="0" max="45" placeholder="0-45" style="' + estiloEdad + '"></td>');
                else if (k === 'unidadDosis') parts.push('<td class="' + cellClass + '" style="' + anchoUnidDosis + '"><input type="text" id="unidadDosis_' + i + '" name="unidadDosis_' + i + '" class="' + inputClass + '" placeholder="Unid." maxlength="50"></td>');
                else if (k === 'area_galpon') parts.push('<td class="' + cellClass + '"><input type="number" id="area_galpon_' + i + '" name="area_galpon_' + i + '" class="' + inputClass + '" min="0" placeholder="Área" style="min-width:50px"></td>');
                else if (k === 'cantidad_por_galpon') parts.push('<td class="' + cellClass + '"><input type="number" id="cantidad_por_galpon_' + i + '" name="cantidad_por_galpon_' + i + '" class="' + inputClass + '" min="0" placeholder="Cant." style="min-width:50px"></td>');
            });
            parts.push('<td class="' + cellClass + ' text-center"><button type="button" class="btn-quitar-fila border border-red-200 text-red-600 hover:bg-red-50 rounded" data-row="' + i + '" title="Quitar fila"><i class="fas fa-trash-alt"></i></button></td>');
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
        function initSelect2ProductoRow(selectEl) {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2 || !selectEl) return;
            var $sel = jQuery(selectEl);
            if ($sel.data('select2')) return;
            $sel.select2({
                placeholder: 'Escriba nombre del producto para buscar...',
                allowClear: true,
                width: '100%',
                dropdownParent: jQuery('#formProgramaContainer'),
                minimumInputLength: 0,
                ajax: { url: 'get_productos_programa.php', dataType: 'json', delay: 250, data: function(params) { return { q: params.term }; }, processResults: function(data) { if (data.success && data.results) return { results: data.results }; return { results: [] }; }, cache: true },
                templateResult: function(item) {
                    if (!item.id) return item.text;
                    var cod = item.codigo || item.id;
                    var desc = item.descri || (item.text ? item.text.replace(/^[^\s-]+\s*-\s*/, '') : '');
                    return jQuery('<span><b>' + cod + '</b> - ' + desc + '</span>');
                },
                templateSelection: function(item) {
                    if (!item.id) return item.text || '';
                    var cod = item.codigo || item.id;
                    var desc = item.descri || (item.text ? item.text.replace(/^[^\s-]+\s*-\s*/, '') : '');
                    return jQuery('<span><b>' + cod + '</b> - ' + desc + '</span>');
                },
                language: { noResults: function() { return 'Sin resultados'; }, searching: function() { return 'Buscando...'; } }
            });
        }
        function onProductoChange(rowIndex) {
            var sel = document.getElementById('producto_' + rowIndex);
            if (!sel || !sel.value) return;
            if (!solicitudesData[rowIndex]) solicitudesData[rowIndex] = {};
            fetch('get_datos_producto_programa.php?codigo=' + encodeURIComponent(sel.value)).then(function(r) { return r.json(); }).then(function(data) {
                if (!data.success) return;
                solicitudesData[rowIndex].codProveedor = data.codProveedor || '';
                solicitudesData[rowIndex].nomProducto = data.nomProducto || '';
                solicitudesData[rowIndex].dosis = data.dosis || '';
                solicitudesData[rowIndex].esVacuna = data.esVacuna || false;
                var desc = (data.descripcionVacuna || '').trim();
                var descTexto = data.esVacuna && desc ? 'Contra\n' + desc.split(',').map(function(s) { return '- ' + s.trim(); }).filter(Boolean).join('\n') : desc || '';
                solicitudesData[rowIndex].descripcionVacuna = descTexto;
                var prov = document.getElementById('proveedor_ro_' + rowIndex); if (prov) prov.value = data.nomProveedor || '';
                var unid = document.getElementById('unidad_ro_' + rowIndex); if (unid) unid.value = data.unidad || '';
                var dosisRo = document.getElementById('dosis_ro_' + rowIndex); if (dosisRo) dosisRo.value = data.dosis || '';
                var tdDesc = document.getElementById('td_descripcion_vacuna_' + rowIndex);
                var descVac = document.getElementById('descripcion_vacuna_ro_' + rowIndex);
                if (data.esVacuna) {
                    if (tdDesc) tdDesc.style.display = '';
                    if (descVac) descVac.value = descTexto;
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
                    var inpDosisRo = document.getElementById('dosis_ro_' + i); if (inpDosisRo && solicitudesData[i].dosis) inpDosisRo.value = solicitudesData[i].dosis;
                    var inpDescVac = document.getElementById('descripcion_vacuna_ro_' + i); if (inpDescVac && solicitudesData[i].descripcionVacuna) inpDescVac.value = solicitudesData[i].descripcionVacuna;
                    var tdDescI = document.getElementById('td_descripcion_vacuna_' + i); if (tdDescI) tdDescI.style.display = solicitudesData[i].esVacuna ? '' : 'none';
                    var inpEdad = document.getElementById('edad_' + i); if (inpEdad && solicitudesData[i].edad !== undefined && solicitudesData[i].edad !== '') inpEdad.value = solicitudesData[i].edad;
                    var ud = document.getElementById('unidadDosis_' + i); var nf = document.getElementById('numeroFrascos_' + i);
                    if (sigla === 'PL' || sigla === 'GR') { if (ud) ud.disabled = true; if (nf) nf.disabled = true; }
                    initSelect2ProductoRow(document.getElementById('producto_' + i));
                    (function(idx) {
                        jQuery('#producto_' + idx).off('select2:select').on('select2:select', function() { onProductoChange(idx); });
                        jQuery('#producto_' + idx).off('select2:clear').on('select2:clear', function() {
                            if (solicitudesData[idx]) solicitudesData[idx].esVacuna = false;
                            var tdDesc = document.getElementById('td_descripcion_vacuna_' + idx);
                            var descVac = document.getElementById('descripcion_vacuna_ro_' + idx);
                            if (tdDesc) tdDesc.style.display = 'none';
                            if (descVac) descVac.value = '';
                            updateVisibilidadColumnaDescripcion();
                        });
                    })(i);
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
                        var inpDosisRo = document.getElementById('dosis_ro_' + i); if (inpDosisRo && solicitudesData[i] && solicitudesData[i].dosis) inpDosisRo.value = solicitudesData[i].dosis;
                        var inpDescVac = document.getElementById('descripcion_vacuna_ro_' + i); if (inpDescVac && solicitudesData[i] && solicitudesData[i].descripcionVacuna) inpDescVac.value = solicitudesData[i].descripcionVacuna;
                        var tdDescI = document.getElementById('td_descripcion_vacuna_' + i); if (tdDescI && solicitudesData[i]) tdDescI.style.display = solicitudesData[i].esVacuna ? '' : 'none';
                        var inpEdad = document.getElementById('edad_' + i); if (inpEdad && solicitudesData[i] && solicitudesData[i].edad !== undefined && solicitudesData[i].edad !== '') inpEdad.value = solicitudesData[i].edad;
                        initSelect2ProductoRow(document.getElementById('producto_' + i));
                        (function(idx) {
                            jQuery('#producto_' + idx).off('select2:select').on('select2:select', function() { onProductoChange(idx); });
                            jQuery('#producto_' + idx).off('select2:clear').on('select2:clear', function() {
                                if (solicitudesData[idx]) solicitudesData[idx].esVacuna = false;
                                var tdDesc = document.getElementById('td_descripcion_vacuna_' + idx);
                                var descVac = document.getElementById('descripcion_vacuna_ro_' + idx);
                                if (tdDesc) tdDesc.style.display = 'none';
                                if (descVac) descVac.value = '';
                                updateVisibilidadColumnaDescripcion();
                            });
                        })(i);
                    }
                }
                tbody.querySelectorAll('.btn-quitar-fila').forEach(function(btn) {
                    var rowIdx = parseInt(btn.getAttribute('data-row'), 10);
                    btn.onclick = function() { quitarFila(rowIdx); };
                });
            }
            updateVisibilidadColumnaDescripcion();
        }
        function getDetallesFromForm() {
            var tbody = document.getElementById('solicitudesBody');
            if (!tbody) return [];
            var rows = tbody.querySelectorAll('tr');
            var out = [];
            for (var s = 0; s < rows.length; s++) {
                var selProd = document.getElementById('producto_' + s);
                var codProducto = selProd ? (selProd.value || '').trim() : '';
                var nomProducto = selProd && selProd.options[selProd.selectedIndex] ? selProd.options[selProd.selectedIndex].textContent : '';
                if (solicitudesData[s] && solicitudesData[s].nomProducto) nomProducto = solicitudesData[s].nomProducto;
                out.push({
                    ubicacion: (document.getElementById('ubicacion_' + s) && document.getElementById('ubicacion_' + s).value) ? document.getElementById('ubicacion_' + s).value.trim() : '',
                    codProducto: codProducto,
                    nomProducto: nomProducto,
                    codProveedor: (solicitudesData[s] && solicitudesData[s].codProveedor) ? solicitudesData[s].codProveedor : '',
                    nomProveedor: (document.getElementById('proveedor_ro_' + s) && document.getElementById('proveedor_ro_' + s).value) ? document.getElementById('proveedor_ro_' + s).value.trim() : '',
                    unidades: (document.getElementById('unidad_ro_' + s) && document.getElementById('unidad_ro_' + s).value) ? document.getElementById('unidad_ro_' + s).value.trim() : '',
                    dosis: (solicitudesData[s] && solicitudesData[s].dosis) ? solicitudesData[s].dosis : '',
                    unidadDosis: (document.getElementById('unidadDosis_' + s) && document.getElementById('unidadDosis_' + s).value) ? document.getElementById('unidadDosis_' + s).value.trim() : '',
                    numeroFrascos: (document.getElementById('numeroFrascos_' + s) && document.getElementById('numeroFrascos_' + s).value) ? document.getElementById('numeroFrascos_' + s).value.trim() : '',
                    edad: (function(){ var el = document.getElementById('edad_' + s); return el ? (parseInt(el.value,10)||0) : 0; })(),
                    descripcionVacuna: (document.getElementById('descripcion_vacuna_ro_' + s) && document.getElementById('descripcion_vacuna_ro_' + s).value) ? document.getElementById('descripcion_vacuna_ro_' + s).value.trim() : '',
                    esVacuna: !!(solicitudesData[s] && solicitudesData[s].esVacuna),
                    areaGalpon: (function(){ var el = document.getElementById('area_galpon_' + s); return el ? (parseInt(el.value,10)||null) : null; })(),
                    cantidadPorGalpon: (function(){ var el = document.getElementById('cantidad_por_galpon_' + s); return el ? (parseInt(el.value,10)||null) : null; })()
                });
            }
            return out;
        }
        function quitarFila(index) {
            var data = getDetallesFromForm();
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
            var zona = document.getElementById('zona') ? document.getElementById('zona').value.trim() : '';
            var despliegue = document.getElementById('despliegue') ? document.getElementById('despliegue').value.trim() : '';
            var descripcion = document.getElementById('descripcion') ? document.getElementById('descripcion').value.trim() : '';
            if (!codTipo || !codigo || !nombre) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Complete tipo, código y nombre.' });
                return;
            }
            var numSol = document.getElementById('solicitudesBody') ? document.getElementById('solicitudesBody').querySelectorAll('tr').length : 0;
            if (numSol < 1) {
                Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Debe haber al menos un detalle. Agregue una fila con el botón "Agregar fila".' });
                return;
            }
            var sigla = getSiglaActual();
            var detalles = [];
            for (var s = 0; s < numSol; s++) {
                var ub = document.getElementById('ubicacion_' + s) ? document.getElementById('ubicacion_' + s).value.trim() : '';
                var selProd = document.getElementById('producto_' + s);
                var codProducto = selProd ? (selProd.value || '').trim() : '';
                var nomProducto = selProd && selProd.options[selProd.selectedIndex] ? selProd.options[selProd.selectedIndex].textContent : '';
                var codProveedor = (solicitudesData[s] && solicitudesData[s].codProveedor) ? solicitudesData[s].codProveedor : '';
                var nomProveedor = document.getElementById('proveedor_ro_' + s) ? document.getElementById('proveedor_ro_' + s).value.trim() : '';
                if (solicitudesData[s] && solicitudesData[s].nomProducto) nomProducto = solicitudesData[s].nomProducto;
                var unidadDosis = document.getElementById('unidadDosis_' + s) ? document.getElementById('unidadDosis_' + s).value.trim() : '';
                var numeroFrascos = document.getElementById('numeroFrascos_' + s) ? document.getElementById('numeroFrascos_' + s).value.trim() : '';
                var edadEl = document.getElementById('edad_' + s);
                var edad = edadEl ? (parseInt(edadEl.value, 10) || 0) : 0;
                if (edad < 0) edad = 0; if (edad > 45) edad = 45;
                var descVacEl = document.getElementById('descripcion_vacuna_ro_' + s);
                var descripcionVacuna = descVacEl ? descVacEl.value.trim() : (solicitudesData[s] && solicitudesData[s].descripcionVacuna ? solicitudesData[s].descripcionVacuna : '');
                var areaGalponEl = document.getElementById('area_galpon_' + s);
                var areaGalpon = areaGalponEl ? (parseInt(areaGalponEl.value, 10) || null) : null;
                var cantGalponEl = document.getElementById('cantidad_por_galpon_' + s);
                var cantidadPorGalpon = cantGalponEl ? (parseInt(cantGalponEl.value, 10) || null) : null;
                var unidades = (document.getElementById('unidad_ro_' + s) ? document.getElementById('unidad_ro_' + s).value.trim() : '') || '';
                var dosis = (solicitudesData[s] && solicitudesData[s].dosis) ? solicitudesData[s].dosis : '';
                detalles.push({ ubicacion: ub, codProducto: codProducto, nomProducto: nomProducto, codProveedor: codProveedor, nomProveedor: nomProveedor, unidades: unidades, dosis: dosis, unidadDosis: unidadDosis, numeroFrascos: numeroFrascos, edad: edad, descripcionVacuna: descripcionVacuna, areaGalpon: areaGalpon, cantidadPorGalpon: cantidadPorGalpon });
            }
            var payload = { codigo: codigo, nombre: nombre, codTipo: parseInt(codTipo, 10), nomTipo: nomTipo, sigla: sigla, zona: zona, despliegue: despliegue, descripcion: descripcion, detalles: detalles };
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
                            if (window.top && window.top !== window.self && typeof window.top.loadDashboardAndData === 'function') {
                                window.top.loadDashboardAndData('modules/planificacion/programas/dashboard-programas-listado.php', '📋 Programas - Listado', 'Filtros y listado de programas');
                            }
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
