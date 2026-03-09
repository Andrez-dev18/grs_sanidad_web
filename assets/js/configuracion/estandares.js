/**
 * Estándares - Cabecera (nombre) + Detalle (subprocesos → actividades → filas). Tabla listado por cabecera.
 */
(function() {
    var ACTIVIDADES_POR_SUBPROCESO = {
        'Preparacion de granja': ['Manejo de cama', 'Limpieza y desinfeccion de galpones', 'Control de plagas'],
        'Aprovisionamiento de pollo BB': ['Proceso en planta de incubacion', 'Transporte de pollo BB', 'Recepcion y descarga de pollo BB'],
        'Procesamiento': ['Manejo de carne', 'Descarga y faena de pollos'],
        'Gestion playa': ['Gestion playa'],
        'Incubacion': ['Proceso Pastos y Incubacion', 'Transporte de pollito', 'Recepcion y entrega de pollito'],
        'Crianza': ['Control serologico', 'Titulos y variacion', 'Transporte de pollo BB', 'Cria de pollos', 'Seleccion y descarte de pollos', 'Crecimiento']
    };
    var SUBPROCESOS_LIST = ['Preparacion de granja', 'Aprovisionamiento de pollo BB', 'Procesamiento', 'Gestion playa', 'Incubacion', 'Crianza'];

    function getTodasActividades() {
        var uniq = {};
        Object.keys(ACTIVIDADES_POR_SUBPROCESO).forEach(function(sub) {
            (ACTIVIDADES_POR_SUBPROCESO[sub] || []).forEach(function(a) { uniq[a] = true; });
        });
        return Object.keys(uniq).sort();
    }

    var SUGERENCIAS_ESTANDARES = {};
    try { if (window.ESTANDARES_SUGERENCIAS) SUGERENCIAS_ESTANDARES = window.ESTANDARES_SUGERENCIAS; } catch (e) {}
    if (Object.keys(SUGERENCIAS_ESTANDARES).length === 0) {
        SUGERENCIAS_ESTANDARES = {
            'Preparacion de granja': {
                'Manejo de cama': [
                    { tipo: 'Cama nueva', parametro: 'Cantidad de cascarilla', unidades: 'Kg.' },
                    { tipo: 'Cama nueva', parametro: 'Calidad de cascarilla', unidades: 'Dato' },
                    { tipo: 'Cama nueva', parametro: 'Tiempo de ingreso de cascarilla', unidades: 'Hrs' },
                    { tipo: 'Cama nueva', parametro: 'Altura de cama', unidades: 'cm' },
                    { tipo: 'Cama reusada', parametro: 'T° Cono dia 1', unidades: '°C' },
                    { tipo: 'Cama reusada', parametro: 'T° Cono dia 2', unidades: '°C' },
                    { tipo: 'Cama reusada', parametro: 'T° Cono dia 3', unidades: '°C' },
                    { tipo: 'Cama reusada', parametro: 'T° Cono dia 4', unidades: '°C' },
                    { tipo: 'Cama reusada', parametro: 'T° Cono dia 5', unidades: '°C' }
                ],
                'Limpieza y desinfeccion de galpones': []
            },
            'Aprovisionamiento de pollo BB': {
                'Transporte de pollo BB': [
                    { tipo: 'Transporte de pollo BB', parametro: 'Temperatura', unidades: '°C' },
                    { tipo: 'Transporte de pollo BB', parametro: 'Humedad', unidades: '%' },
                    { tipo: 'Transporte de pollo BB', parametro: 'Tiempo de transporte', unidades: 'hrs' },
                    { tipo: 'Transporte de pollo BB', parametro: '% Merma', unidades: '%' }
                ]
            },
            'Procesamiento': {
                'Manejo de carne': [
                    { tipo: 'Carne nueva', parametro: 'Cantidad', unidades: 'Kg' },
                    { tipo: 'Carne lavada', parametro: 'Calidad', unidades: 'un' }
                ],
                'Descarga y faena de pollos': [
                    { tipo: 'Manejo Sanitario', parametro: 'Tiempo', unidades: 'Min' },
                    { tipo: 'Manejo Reposo', parametro: 'Duracion', unidades: 'Min' },
                    { tipo: 'Manejo Linea Evicerado', parametro: 'Velocidad', unidades: 'un' },
                    { tipo: 'Manejo Rapido', parametro: 'Tiempo', unidades: 'Min' }
                ]
            },
            'Gestion playa': {
                'Gestion playa': [
                    { tipo: 'Generacion agua salobre', parametro: 'Caudal', unidades: 'ml' },
                    { tipo: 'Calidad de agua salobre', parametro: 'pH', unidades: 'pH' },
                    { tipo: 'General de playas', parametro: 'Pollos', unidades: 'un' }
                ]
            },
            'Incubacion': {
                'Proceso Pastos y Incubacion': [
                    { tipo: 'Alimento en incubacion', parametro: 'Cantidad', unidades: 'Kg' },
                    { tipo: 'Pollos', parametro: 'Cantidad', unidades: 'un' }
                ],
                'Transporte de pollito': [
                    { tipo: 'Temperatura de pollito', parametro: 'Temperatura', unidades: '°C' },
                    { tipo: 'Calidad de pollito B0', parametro: 'Calidad', unidades: 'un' }
                ],
                'Recepcion y entrega de pollito': [
                    { tipo: 'Ambiente', parametro: 'Temperatura', unidades: '°C' },
                    { tipo: 'Sanidad', parametro: 'Control', unidades: 'un' }
                ]
            },
            'Crianza': {
                'Cria de pollos': [
                    { tipo: 'Control de Peso', parametro: 'Peso', unidades: 'gr' },
                    { tipo: 'Alimento', parametro: 'Cantidad', unidades: 'Kg' },
                    { tipo: 'Agua', parametro: 'Consumo', unidades: 'ml' },
                    { tipo: 'Bioseguridad', parametro: 'Control', unidades: 'un' },
                    { tipo: 'Iluminacion', parametro: 'Intensidad', unidades: 'Tonos' }
                ],
                'Seleccion y descarte de pollos': [
                    { tipo: 'Pollos de descarte', parametro: 'Cantidad', unidades: 'un' },
                    { tipo: 'Pollos de calidad', parametro: 'Cantidad', unidades: 'un' }
                ],
                'Crecimiento': [
                    { tipo: 'Alimentacion', parametro: 'Peso', unidades: 'gr' },
                    { tipo: 'Crecimiento', parametro: 'Incremento', unidades: 'gr' },
                    { tipo: 'Manejo', parametro: 'Densidad', unidades: 'densidad' }
                ],
                'Control serologico': [
                    { tipo: 'Titulos', parametro: 'Anemia (CAV)', unidades: 'Titulos' },
                    { tipo: 'Titulos', parametro: 'Gumboro (IBD)', unidades: 'Titulos' },
                    { tipo: 'Titulos', parametro: 'Bronquitis (BI)', unidades: 'Titulos' },
                    { tipo: 'Titulos', parametro: 'Newcastle (ENC)', unidades: 'Titulos' },
                    { tipo: 'Titulos', parametro: 'Reovirus (REO)', unidades: 'Titulos' },
                    { tipo: 'Coeficiente de variacion', parametro: 'Anemia (CAV)', unidades: '%' },
                    { tipo: 'Coeficiente de variacion', parametro: 'Gumboro (IBD)', unidades: '%' },
                    { tipo: 'Coeficiente de variacion', parametro: 'Bronquitis (BI)', unidades: '%' },
                    { tipo: 'Coeficiente de variacion', parametro: 'Newcastle (ENC)', unidades: '%' },
                    { tipo: 'Coeficiente de variacion', parametro: 'Reovirus (REO)', unidades: '%' },
                    { tipo: 'Desviacion estandar', parametro: 'Anemia (CAV)', unidades: 'Titulos' },
                    { tipo: 'Desviacion estandar', parametro: 'Gumboro (IBD)', unidades: 'Titulos' },
                    { tipo: 'Desviacion estandar', parametro: 'Bronquitis (BI)', unidades: 'Titulos' },
                    { tipo: 'Desviacion estandar', parametro: 'Newcastle (ENC)', unidades: 'Titulos' },
                    { tipo: 'Desviacion estandar', parametro: 'Reovirus (REO)', unidades: 'Titulos' }
                ],
                'Transporte de pollo BB': [
                    { tipo: 'Transporte de pollo BB', parametro: 'Temperatura', unidades: '°C' },
                    { tipo: 'Transporte de pollo BB', parametro: 'Humedad', unidades: '%' },
                    { tipo: 'Transporte de pollo BB', parametro: 'Tiempo de transporte', unidades: 'hrs' },
                    { tipo: 'Transporte de pollo BB', parametro: '% Merma', unidades: '%' }
                ]
            }
        };
    }

    var AYUDA_ACTIVIDAD = 'Ejemplos: Manejo de cama, Limpieza y desinfeccion de galpones, Transporte de pollo BB, Control serologico, Cria de pollos, Seleccion y descarte de pollos, Crecimiento, Manejo de carne, Descarga y faena de pollos, Gestion playa';
    var AYUDA_TIPO = 'Ejemplos: Cama nueva, Cama reusada, Titulos, Coeficiente de variacion, Desviacion estandar, Transporte de pollo BB, Carne nueva, Carne lavada, Manejo Sanitario, Manejo Reposo, Control de Peso, Alimento, Crecimiento';
    var AYUDA_UNIDAD = 'Ejemplos: Kg., Dato, Hrs, cm, °C, Titulos, %, gr, Min, un, ml, pH';

    function escapeHtml(s) {
        if (s == null) return '';
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function getOpcionesTipoParametroUnidades() {
        var tipos = {}, parametros = {}, unidades = {};
        Object.keys(SUGERENCIAS_ESTANDARES).forEach(function(sub) {
            Object.keys(SUGERENCIAS_ESTANDARES[sub] || {}).forEach(function(act) {
                (SUGERENCIAS_ESTANDARES[sub][act] || []).forEach(function(s) {
                    if (s.tipo) tipos[s.tipo] = true;
                    if (s.parametro) parametros[s.parametro] = true;
                    if (s.unidades) unidades[s.unidades] = true;
                });
            });
        });
        return { tipo: Object.keys(tipos).sort(), parametro: Object.keys(parametros).sort(), unidades: Object.keys(unidades).sort() };
    }

    function fillAllDatalists() {
        var dlSub = document.getElementById('datalistSubproceso');
        var dlAct = document.getElementById('datalistActividad');
        var dlTipo = document.getElementById('datalistTipo');
        var dlParam = document.getElementById('datalistParametro');
        var dlUnid = document.getElementById('datalistUnidades');
        if (dlSub) dlSub.innerHTML = SUBPROCESOS_LIST.map(function(v) { return '<option value="' + escapeHtml(v) + '">'; }).join('');
        if (dlAct) dlAct.innerHTML = getTodasActividades().map(function(v) { return '<option value="' + escapeHtml(v) + '">'; }).join('');
        var opc = getOpcionesTipoParametroUnidades();
        if (dlTipo) dlTipo.innerHTML = opc.tipo.map(function(v) { return '<option value="' + escapeHtml(v) + '">'; }).join('');
        if (dlParam) dlParam.innerHTML = opc.parametro.map(function(v) { return '<option value="' + escapeHtml(v) + '">'; }).join('');
        if (dlUnid) dlUnid.innerHTML = opc.unidades.map(function(v) { return '<option value="' + escapeHtml(v) + '">'; }).join('');
    }

    // ---- Listado: cabeceras (id, nombre) ----
    function buildFilaHtml(idx, tipo, parametro, unidades, stdMin, stdMax) {
        tipo = tipo || ''; parametro = parametro || ''; unidades = unidades || ''; stdMin = stdMin || ''; stdMax = stdMax || '';
        return '<tr class="border-b border-gray-200" data-fila-index="' + idx + '">' +
            '<td class="col-num text-gray-500">' + (idx + 1) + '</td>' +
            '<td><input type="text" class="form-control compact inp-tipo" list="datalistTipo" autocomplete="off" title="' + escapeHtml(AYUDA_TIPO) + '" value="' + escapeHtml(tipo) + '"></td>' +
            '<td><input type="text" class="form-control compact inp-parametro" list="datalistParametro" autocomplete="off" value="' + escapeHtml(parametro) + '"></td>' +
            '<td><input type="text" class="form-control compact inp-unidades" list="datalistUnidades" autocomplete="off" title="' + escapeHtml(AYUDA_UNIDAD) + '" value="' + escapeHtml(unidades) + '"></td>' +
            '<td><input type="text" class="form-control compact inp-stdMin" value="' + escapeHtml(stdMin) + '"></td>' +
            '<td><input type="text" class="form-control compact inp-stdMax" value="' + escapeHtml(stdMax) + '"></td>' +
            '<td class="col-quitar"><button type="button" class="btn-quitar btn-quitar-fila" title="Quitar fila"><i class="fas fa-trash-alt"></i></button></td></tr>';
    }

    function buildActividadModalHtml(actIdx, actividad, filas) {
        var filasHtml = (filas || []).map(function(f, i) { return buildFilaHtml(i, f.tipo, f.parametro, f.unidades, f.stdMin, f.stdMax); }).join('');
        return '<div class="bloque-actividad" data-act-index="' + actIdx + '">' +
            '<div class="actividad-header">' +
            '<input type="text" class="form-control input-actividad" placeholder="Actividad" list="datalistActividad" autocomplete="off" title="' + escapeHtml(AYUDA_ACTIVIDAD) + '" value="' + escapeHtml(actividad || '') + '">' +
            '<button type="button" class="btn-quitar btn-quitar-actividad" title="Quitar actividad"><i class="fas fa-times"></i></button>' +
            '</div>' +
            '<div class="actividad-tabla">' +
            '<table class="tabla-estandares"><thead><tr><th class="col-num">#</th><th>Tipo</th><th>Parámetro</th><th>Unidades</th><th>STD Min</th><th>STD Max</th><th class="col-quitar"></th></tr></thead>' +
            '<tbody class="tbody-filas">' + filasHtml + '</tbody></table>' +
            '<button type="button" class="btn-add-discreto btn-add-row btn-agregar-fila" style="margin-top:6px"><i class="fas fa-plus"></i> Agregar fila</button>' +
            '</div></div>';
    }

    function buildSubprocesoModalHtml(subIdx, subproceso, actividades) {
        var actHtml = (actividades || []).map(function(a, i) { return buildActividadModalHtml(i, a.actividad, a.filas); }).join('');
        return '<div class="bloque-subproceso-modal" data-sub-index="' + subIdx + '">' +
            '<div class="subproceso-header">' +
            '<input type="text" class="form-control input-subproceso" placeholder="Subproceso" list="datalistSubproceso" autocomplete="off" value="' + escapeHtml(subproceso || '') + '">' +
            '<button type="button" class="btn-quitar btn-quitar-subproceso" title="Quitar subproceso"><i class="fas fa-times"></i></button>' +
            '</div>' +
            '<div class="subproceso-body">' +
            actHtml +
            '<button type="button" class="btn-add-discreto btn-agregar-actividad btn-agregar-actividad-sub" style="margin-top:6px" data-sub-index="' + subIdx + '"><i class="fas fa-plus"></i> Agregar actividad</button>' +
            '</div></div>';
    }

    var modalSubprocesos = [];
    var mapSelectedNode = null;
    var mapCollapsed = {};
    var mainEstandarData = null;  // { estandares: [{ id, nombre, subprocesos }] } vista consolidada
    var mainCollapsed = {};       // colapsado por defecto: actividades y params

    // ------ Vista Mapa (estilo Mind Manager) ------
    function getMapData() {
        return modalSubprocesos;
    }

    function renderMapTree() {
        var root = document.getElementById('mapTreeRoot');
        if (!root) return;
        var data = getMapData();
        var nombreEstandar = (document.getElementById('modalInputNombre') && document.getElementById('modalInputNombre').value) ? document.getElementById('modalInputNombre').value.trim() : 'Proceso / Estándar';
        if (!nombreEstandar) nombreEstandar = 'Proceso / Estándar';
        if (!data || data.length === 0) {
            root.innerHTML = '<div class="mm-root" id="mmRootNode" title="Nodo raíz">' + escapeHtml(nombreEstandar || 'Estándar') + '</div>' +
                '<div class="map-tree-empty"><p>No hay subprocesos.</p><button type="button" class="btn-add-discreto mt-2" id="mapAddFirstSub"><i class="fas fa-plus"></i> Agregar primer subproceso</button></div>';
            var btn = root.querySelector('#mapAddFirstSub');
            if (btn) btn.onclick = function() {
                modalSubprocesos.push({ subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                mapSelectedNode = { type: 'sub', subIdx: 0 };
                renderMapTree();
                renderMapDetail();
            };
            var r = root.querySelector('#mmRootNode');
            if (r) r.onclick = function() { mapSelectedNode = null; renderMapTree(); renderMapDetail(); };
            return;
        }
        var html = '<div class="mm-root' + (!mapSelectedNode ? ' selected' : '') + '" id="mmRootNode" title="Proceso / Estándar">' + escapeHtml(nombreEstandar) + '</div>';
        data.forEach(function(sub, subIdx) {
            var subKey = 'sub-' + subIdx;
            var isCollapsed = mapCollapsed[subKey];
            var subLabel = (sub.subproceso && sub.subproceso.trim()) ? sub.subproceso.trim() : '(Subproceso sin nombre)';
            var isSelected = mapSelectedNode && mapSelectedNode.type === 'sub' && mapSelectedNode.subIdx === subIdx;
            var subNum = (subIdx + 1) + '.';
            html += '<div class="mm-branch">';
            html += '<div class="mm-node-sub' + (isSelected ? ' selected' : '') + '" data-type="sub" data-sub="' + subIdx + '" role="button">';
            html += '<button type="button" class="mm-toggle' + (isCollapsed ? ' collapsed' : '') + '" data-toggle="' + subKey + '" aria-label="Expandir/colapsar">' + (isCollapsed ? '<span class="plus">+</span>' : '<span class="minus">−</span>') + '</button>';
            html += '<span>' + escapeHtml(subNum) + ' ' + escapeHtml(subLabel) + '</span>';
            html += '<button type="button" class="mm-add map-add-sub" data-after-sub="' + subIdx + '" title="Agregar subproceso"><i class="fas fa-plus"></i></button>';
            html += '<button type="button" class="mm-delete map-delete-sub" data-sub="' + subIdx + '" title="Eliminar subproceso"><i class="fas fa-trash-alt"></i></button>';
            html += '</div>';
            if (!isCollapsed && sub.actividades && sub.actividades.length > 0) {
                html += '<div class="mm-children">';
                sub.actividades.forEach(function(act, actIdx) {
                    var actKey = 'act-' + subIdx + '-' + actIdx;
                    var actIsCollapsed = mapCollapsed[actKey];
                    var actLabel = (act.actividad && act.actividad.trim()) ? act.actividad.trim() : '(Actividad sin nombre)';
                    var actIsSelected = mapSelectedNode && mapSelectedNode.type === 'act' && mapSelectedNode.subIdx === subIdx && mapSelectedNode.actIdx === actIdx;
                    var actNum = (subIdx + 1) + '.' + (actIdx + 1) + '.';
                    html += '<div class="mm-node-act' + (actIsSelected ? ' selected' : '') + '" data-type="act" data-sub="' + subIdx + '" data-act="' + actIdx + '" role="button">';
                    html += '<button type="button" class="mm-toggle' + (actIsCollapsed ? ' collapsed' : '') + '" data-toggle="' + actKey + '">' + (actIsCollapsed ? '<span class="plus">+</span>' : '<span class="minus">−</span>') + '</button>';
                    html += '<span class="mm-num">' + actNum + '</span><span>' + escapeHtml(actLabel) + '</span>';
                    html += '<button type="button" class="mm-add map-add-act" data-sub="' + subIdx + '" data-act="' + actIdx + '" title="Agregar actividad"><i class="fas fa-plus"></i></button>';
                    html += '<button type="button" class="mm-delete map-delete-act" data-sub="' + subIdx + '" data-act="' + actIdx + '" title="Eliminar actividad"><i class="fas fa-trash-alt"></i></button>';
                    html += '</div>';
                    if (!actIsCollapsed && act.filas && act.filas.length > 0) {
                        html += '<div class="mm-children">';
                        act.filas.forEach(function(fila, fIdx) {
                            var filaLabel = [fila.tipo, fila.parametro].filter(Boolean).join(' / ') || '(Parámetro)';
                            var paramIsSelected = mapSelectedNode && mapSelectedNode.type === 'param' && mapSelectedNode.subIdx === subIdx && mapSelectedNode.actIdx === actIdx && mapSelectedNode.filaIdx === fIdx;
                            var paramNum = (subIdx + 1) + '.' + (actIdx + 1) + '.' + (fIdx + 1);
                            html += '<div class="mm-node-param' + (paramIsSelected ? ' selected' : '') + '" data-type="param" data-sub="' + subIdx + '" data-act="' + actIdx + '" data-fila="' + fIdx + '" role="button">';
                            html += '<span class="mm-bullet"></span><span class="mm-num">' + paramNum + '.</span><span>' + escapeHtml(filaLabel) + '</span></div>';
                        });
                        html += '</div>';
                    }
                });
                html += '</div>';
            }
            html += '</div>';
        });
        html += '<button type="button" class="btn-add-discreto mt-3 w-full map-add-sub-root" title="Agregar subproceso al final"><i class="fas fa-plus"></i> Agregar subproceso</button>';
        root.innerHTML = html;
        var r = root.querySelector('#mmRootNode');
        if (r) r.onclick = function() { mapSelectedNode = null; renderMapTree(); renderMapDetail(); };
        bindMapTreeEvents();
    }

    function bindMapTreeEvents() {
        var root = document.getElementById('mapTreeRoot');
        if (!root) return;
        root.querySelectorAll('.mm-node-sub, .mm-node-act, .mm-node-param').forEach(function(el) {
            el.onclick = function(e) {
                if (e.target.closest('.mm-toggle') || e.target.closest('.mm-add') || e.target.closest('.mm-delete')) return;
                var type = el.getAttribute('data-type');
                var subIdx = parseInt(el.getAttribute('data-sub'), 10);
                var actIdx = el.hasAttribute('data-act') ? parseInt(el.getAttribute('data-act'), 10) : null;
                mapSelectedNode = type === 'sub' ? { type: 'sub', subIdx: subIdx } : (type === 'act' ? { type: 'act', subIdx: subIdx, actIdx: actIdx } : { type: 'param', subIdx: subIdx, actIdx: actIdx, filaIdx: parseInt(el.getAttribute('data-fila'), 10) });
                renderMapTree();
                renderMapDetail();
            };
        });
        root.querySelectorAll('.mm-toggle').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var key = btn.getAttribute('data-toggle');
                mapCollapsed[key] = !mapCollapsed[key];
                renderMapTree();
            };
        });
        root.querySelectorAll('.map-add-sub').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var after = parseInt(btn.getAttribute('data-after-sub'), 10);
                modalSubprocesos.splice(after + 1, 0, { subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                mapSelectedNode = { type: 'sub', subIdx: after + 1 };
                mapCollapsed['sub-' + (after + 1)] = false;
                renderMapTree();
                renderMapDetail();
            };
        });
        root.querySelectorAll('.map-add-act').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                if (!modalSubprocesos[subIdx]) return;
                modalSubprocesos[subIdx].actividades = modalSubprocesos[subIdx].actividades || [];
                modalSubprocesos[subIdx].actividades.splice(actIdx + 1, 0, { actividad: '', filas: [] });
                mapSelectedNode = { type: 'act', subIdx: subIdx, actIdx: actIdx + 1 };
                renderMapTree();
                renderMapDetail();
            };
        });
        root.querySelectorAll('.map-add-sub-root').forEach(function(btn) {
            btn.onclick = function() {
                modalSubprocesos.push({ subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                mapSelectedNode = { type: 'sub', subIdx: modalSubprocesos.length - 1 };
                mapCollapsed['sub-' + (modalSubprocesos.length - 1)] = false;
                renderMapTree();
                renderMapDetail();
            };
        });
        root.querySelectorAll('.map-delete-sub').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                modalSubprocesos.splice(subIdx, 1);
                mapSelectedNode = null;
                renderMapTree();
                renderMapDetail();
            };
        });
        root.querySelectorAll('.map-delete-act').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                if (modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades) {
                    modalSubprocesos[subIdx].actividades.splice(actIdx, 1);
                    mapSelectedNode = { type: 'sub', subIdx: subIdx };
                    renderMapTree();
                    renderMapDetail();
                }
            };
        });
    }

    function renderMapBreadcrumb() {
        var bc = document.getElementById('mapBreadcrumb');
        if (!bc) return;
        if (!mapSelectedNode) {
            bc.innerHTML = '<span class="current">Seleccione un nodo del árbol</span>';
            return;
        }
        var parts = ['<a href="#" class="map-bc-root">Raíz</a>'];
        if (mapSelectedNode.type === 'sub' || mapSelectedNode.type === 'act' || mapSelectedNode.type === 'param') {
            var sub = modalSubprocesos[mapSelectedNode.subIdx];
            if (sub) {
                var subName = (sub.subproceso && sub.subproceso.trim()) ? sub.subproceso.trim() : 'Subproceso';
                parts.push('<span>›</span>', mapSelectedNode.type === 'sub' ? ('<span class="current">' + escapeHtml(subName) + '</span>') : ('<a href="#" class="map-bc-sub" data-sub="' + mapSelectedNode.subIdx + '">' + escapeHtml(subName) + '</a>'));
            }
            if (mapSelectedNode.type === 'act' || mapSelectedNode.type === 'param') {
                var act = sub && sub.actividades && sub.actividades[mapSelectedNode.actIdx];
                if (act) {
                    var actName = (act.actividad && act.actividad.trim()) ? act.actividad.trim() : 'Actividad';
                    parts.push('<span>›</span>', mapSelectedNode.type === 'act' ? ('<span class="current">' + escapeHtml(actName) + '</span>') : ('<a href="#" class="map-bc-act" data-sub="' + mapSelectedNode.subIdx + '" data-act="' + mapSelectedNode.actIdx + '">' + escapeHtml(actName) + '</a>'));
                }
            }
            if (mapSelectedNode.type === 'param') {
                var fila = sub && sub.actividades && sub.actividades[mapSelectedNode.actIdx] && sub.actividades[mapSelectedNode.actIdx].filas && sub.actividades[mapSelectedNode.actIdx].filas[mapSelectedNode.filaIdx];
                var paramName = fila ? ([fila.tipo, fila.parametro].filter(Boolean).join(' / ') || 'Parámetro') : 'Parámetro';
                parts.push('<span>›</span>', '<span class="current">' + escapeHtml(paramName) + '</span>');
            }
        }
        bc.innerHTML = parts.join(' ');
        bc.querySelectorAll('.map-bc-root').forEach(function(a) { a.onclick = function(e) { e.preventDefault(); mapSelectedNode = null; renderMapTree(); renderMapDetail(); renderMapBreadcrumb(); }; });
        bc.querySelectorAll('.map-bc-sub').forEach(function(a) { a.onclick = function(e) { e.preventDefault(); mapSelectedNode = { type: 'sub', subIdx: parseInt(a.getAttribute('data-sub'), 10) }; renderMapTree(); renderMapDetail(); renderMapBreadcrumb(); }; });
        bc.querySelectorAll('.map-bc-act').forEach(function(a) { a.onclick = function(e) { e.preventDefault(); mapSelectedNode = { type: 'act', subIdx: parseInt(a.getAttribute('data-sub'), 10), actIdx: parseInt(a.getAttribute('data-act'), 10) }; renderMapTree(); renderMapDetail(); renderMapBreadcrumb(); }; });
    }

    function renderMapDetail() {
        var content = document.getElementById('mapDetailContent');
        if (!content) return;
        renderMapBreadcrumb();
        if (!mapSelectedNode) {
            content.innerHTML = '<div class="detail-placeholder"><i class="fas fa-mouse-pointer"></i><p><strong>Seleccione un nodo</strong> en el árbol de la izquierda</p><p>para cargar y editar solo esa rama.</p><p class="text-sm mt-2">Puede expandir/colapsar ramas y agregar nodos con <i class="fas fa-plus"></i></p>' +
                (modalSubprocesos.length === 0 ? '<button type="button" class="btn-add-discreto mt-3" id="mapAddFirstSubFromDetail"><i class="fas fa-plus"></i> Agregar primer subproceso</button>' : '') + '</div>';
            content.querySelectorAll('#mapAddFirstSubFromDetail').forEach(function(btn) {
                btn.onclick = function() {
                    modalSubprocesos.push({ subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                    mapSelectedNode = { type: 'sub', subIdx: 0 };
                    renderMapTree();
                    renderMapDetail();
                };
            });
            bindMapDetailEvents();
            return;
        }
        var sub = modalSubprocesos[mapSelectedNode.subIdx];
        if (!sub) {
            content.innerHTML = '<div class="detail-placeholder"><p>Nodo no encontrado.</p></div>';
            return;
        }
        if (mapSelectedNode.type === 'sub') {
            var actHtml = (sub.actividades || []).map(function(a, i) {
                return '<div class="bloque-actividad" data-act-index="' + i + '"><div class="actividad-header">' +
                    '<input type="text" class="form-control input-actividad" placeholder="Actividad" list="datalistActividad" value="' + escapeHtml(a.actividad || '') + '">' +
                    '<button type="button" class="btn-icon p-2 text-blue-600 hover:bg-blue-100 map-go-act" data-sub="' + mapSelectedNode.subIdx + '" data-act="' + i + '" title="Ver parámetros"><i class="fas fa-arrow-right"></i></button>' +
                    '<button type="button" class="btn-quitar btn-quitar-actividad-map" data-sub="' + mapSelectedNode.subIdx + '" data-act="' + i + '"><i class="fas fa-times"></i></button></div></div>';
            }).join('');
            content.innerHTML = '<div class="detail-header"><h3><i class="fas fa-layer-group text-blue-600"></i> Subproceso</h3>' +
                '<button type="button" class="btn-quitar ml-auto" id="mapEliminarSubproceso" data-sub="' + mapSelectedNode.subIdx + '" title="Eliminar subproceso"><i class="fas fa-trash-alt"></i> Eliminar</button></div>' +
                '<div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre del subproceso</label>' +
                '<input type="text" class="form-control input-subproceso-map" data-sub="' + mapSelectedNode.subIdx + '" value="' + escapeHtml(sub.subproceso || '') + '" list="datalistSubproceso" placeholder="Ej: Preparación de granja" style="max-width:400px"></div>' +
                '<div class="mb-2"><label class="block text-sm font-medium text-gray-700 mb-1">Actividades</label></div>' +
                '<div id="mapDetailActividades">' + actHtml + '</div>' +
                '<button type="button" class="btn-add-discreto mt-2 btn-agregar-actividad-map" data-sub="' + mapSelectedNode.subIdx + '"><i class="fas fa-plus"></i> Agregar actividad</button>';
        } else if (mapSelectedNode.type === 'act') {
            var act = sub.actividades && sub.actividades[mapSelectedNode.actIdx];
            if (!act) { content.innerHTML = '<div class="detail-placeholder"><p>Actividad no encontrada.</p></div>'; return; }
            var filasHtml = (act.filas || []).map(function(f, i) {
                return buildFilaHtml(i, f.tipo, f.parametro, f.unidades, f.stdMin, f.stdMax);
            }).join('');
            content.innerHTML = '<div class="detail-header"><h3><i class="fas fa-tasks text-blue-600"></i> Actividad</h3>' +
                '<button type="button" class="btn-quitar ml-auto" id="mapEliminarActividad" data-sub="' + mapSelectedNode.subIdx + '" data-act="' + mapSelectedNode.actIdx + '" title="Eliminar actividad"><i class="fas fa-trash-alt"></i> Eliminar</button></div>' +
                '<div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la actividad</label>' +
                '<input type="text" class="form-control input-actividad-map" data-sub="' + mapSelectedNode.subIdx + '" data-act="' + mapSelectedNode.actIdx + '" value="' + escapeHtml(act.actividad || '') + '" list="datalistActividad" placeholder="Ej: Manejo de cama" style="max-width:400px"></div>' +
                '<div class="mb-2"><label class="block text-sm font-medium text-gray-700 mb-1">Parámetros / filas</label></div>' +
                '<div class="actividad-tabla"><table class="tabla-estandares"><thead><tr><th class="col-num">#</th><th>Tipo</th><th>Parámetro</th><th>Unidades</th><th>STD Min</th><th>STD Max</th><th class="col-quitar"></th></tr></thead>' +
                '<tbody class="tbody-filas">' + filasHtml + '</tbody></table>' +
                '<button type="button" class="btn-add-discreto btn-add-row btn-agregar-fila-map mt-2" data-sub="' + mapSelectedNode.subIdx + '" data-act="' + mapSelectedNode.actIdx + '"><i class="fas fa-plus"></i> Agregar fila</button></div>';
        } else {
            var f = sub.actividades && sub.actividades[mapSelectedNode.actIdx] && sub.actividades[mapSelectedNode.actIdx].filas && sub.actividades[mapSelectedNode.actIdx].filas[mapSelectedNode.filaIdx];
            if (!f) { content.innerHTML = '<div class="detail-placeholder"><p>Parámetro no encontrado.</p></div>'; return; }
            content.innerHTML = '<div class="detail-header"><h3><i class="fas fa-sliders-h text-blue-600"></i> Parámetro</h3></div>' +
                '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0.75rem">' +
                '<div><label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label><input type="text" class="form-control inp-map-tipo" list="datalistTipo" value="' + escapeHtml(f.tipo || '') + '"></div>' +
                '<div><label class="block text-sm font-medium text-gray-700 mb-1">Parámetro</label><input type="text" class="form-control inp-map-parametro" list="datalistParametro" value="' + escapeHtml(f.parametro || '') + '"></div>' +
                '<div><label class="block text-sm font-medium text-gray-700 mb-1">Unidades</label><input type="text" class="form-control inp-map-unidades" list="datalistUnidades" value="' + escapeHtml(f.unidades || '') + '"></div>' +
                '<div><label class="block text-sm font-medium text-gray-700 mb-1">STD Min</label><input type="text" class="form-control inp-map-stdMin" value="' + escapeHtml(f.stdMin || '') + '"></div>' +
                '<div><label class="block text-sm font-medium text-gray-700 mb-1">STD Max</label><input type="text" class="form-control inp-map-stdMax" value="' + escapeHtml(f.stdMax || '') + '"></div></div>';
        }
        bindMapDetailEvents();
    }

    function bindMapDetailEvents() {
        var content = document.getElementById('mapDetailContent');
        if (!content) return;
        content.querySelectorAll('#mapEliminarSubproceso').forEach(function(btn) {
            btn.onclick = function() {
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                modalSubprocesos.splice(subIdx, 1);
                mapSelectedNode = null;
                renderMapTree();
                renderMapDetail();
            };
        });
        content.querySelectorAll('#mapEliminarActividad').forEach(function(btn) {
            btn.onclick = function() {
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                if (modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades) {
                    modalSubprocesos[subIdx].actividades.splice(actIdx, 1);
                    mapSelectedNode = { type: 'sub', subIdx: subIdx };
                    renderMapTree();
                    renderMapDetail();
                }
            };
        });
        content.querySelectorAll('.input-subproceso-map').forEach(function(inp) {
            inp.onchange = inp.onblur = function() {
                var idx = parseInt(inp.getAttribute('data-sub'), 10);
                if (modalSubprocesos[idx]) modalSubprocesos[idx].subproceso = inp.value.trim();
                renderMapTree();
                renderMapBreadcrumb();
            };
        });
        content.querySelectorAll('.input-actividad').forEach(function(inp) {
            var parent = inp.closest('.bloque-actividad');
            if (!parent) return;
            var actIdx = parseInt(parent.getAttribute('data-act-index'), 10);
            var subIdx = mapSelectedNode && mapSelectedNode.subIdx;
            if (subIdx === undefined) return;
            inp.onchange = inp.onblur = function() {
                if (modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx]) {
                    modalSubprocesos[subIdx].actividades[actIdx].actividad = inp.value.trim();
                }
                renderMapTree();
                renderMapBreadcrumb();
            };
        });
        content.querySelectorAll('.input-actividad-map').forEach(function(inp) {
            inp.onchange = inp.onblur = function() {
                var subIdx = parseInt(inp.getAttribute('data-sub'), 10);
                var actIdx = parseInt(inp.getAttribute('data-act'), 10);
                if (modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx]) {
                    modalSubprocesos[subIdx].actividades[actIdx].actividad = inp.value.trim();
                }
                renderMapTree();
                renderMapBreadcrumb();
            };
        });
        content.querySelectorAll('.inp-map-tipo, .inp-map-parametro, .inp-map-unidades, .inp-map-stdMin, .inp-map-stdMax').forEach(function(inp) {
            var cls = inp.className.match(/inp-map-(\w+)/);
            if (!cls || !mapSelectedNode) return;
            inp.onchange = inp.onblur = function() {
                var sub = modalSubprocesos[mapSelectedNode.subIdx];
                var act = sub && sub.actividades && sub.actividades[mapSelectedNode.actIdx];
                var fila = act && act.filas && act.filas[mapSelectedNode.filaIdx];
                if (fila) fila[cls[1] === 'parametro' ? 'parametro' : cls[1]] = inp.value.trim();
                renderMapTree();
            };
        });
        content.querySelectorAll('.map-go-act').forEach(function(btn) {
            btn.onclick = function() {
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                mapSelectedNode = { type: 'act', subIdx: subIdx, actIdx: actIdx };
                renderMapTree();
                renderMapDetail();
            };
        });
        content.querySelectorAll('.btn-quitar-actividad-map').forEach(function(btn) {
            btn.onclick = function() {
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                if (modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades) {
                    modalSubprocesos[subIdx].actividades.splice(actIdx, 1);
                    mapSelectedNode = null;
                    renderMapTree();
                    renderMapDetail();
                }
            };
        });
        content.querySelectorAll('.btn-agregar-actividad-map').forEach(function(btn) {
            btn.onclick = function() {
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                if (!modalSubprocesos[subIdx]) return;
                modalSubprocesos[subIdx].actividades = modalSubprocesos[subIdx].actividades || [];
                modalSubprocesos[subIdx].actividades.push({ actividad: '', filas: [] });
                mapSelectedNode = { type: 'act', subIdx: subIdx, actIdx: modalSubprocesos[subIdx].actividades.length - 1 };
                renderMapTree();
                renderMapDetail();
            };
        });
        content.querySelectorAll('.btn-agregar-fila-map').forEach(function(btn) {
            btn.onclick = function() {
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                var act = modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx];
                if (!act) return;
                act.filas = act.filas || [];
                act.filas.push({ tipo: '', parametro: '', unidades: '', stdMin: '', stdMax: '' });
                renderMapDetail();
            };
        });
        content.querySelectorAll('.btn-quitar-fila').forEach(function(btn) {
            btn.onclick = function() {
                var tr = btn.closest('tr');
                var subBlq = document.getElementById('mapDetailActividades');
                var blq = tr ? tr.closest('.actividad-tabla') : null;
                if (!blq || !mapSelectedNode) return;
                var subIdx = mapSelectedNode.subIdx;
                var actIdx = mapSelectedNode.actIdx;
                var filaIdx = parseInt(tr.getAttribute('data-fila-index'), 10);
                var act = modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx];
                if (act && act.filas) {
                    act.filas.splice(filaIdx, 1);
                    renderMapDetail();
                }
            };
        });
        content.querySelectorAll('.tbody-filas input').forEach(function(inp) {
            var tr = inp.closest('tr');
            if (!tr || !mapSelectedNode) return;
            var subIdx = mapSelectedNode.subIdx;
            var actIdx = mapSelectedNode.actIdx;
            var filaIdx = parseInt(tr.getAttribute('data-fila-index'), 10);
            var act = modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx];
            var fila = act && act.filas && act.filas[filaIdx];
            if (!fila) return;
            var fn = function() {
                fila.tipo = (tr.querySelector('.inp-tipo') && tr.querySelector('.inp-tipo').value) ? tr.querySelector('.inp-tipo').value.trim() : '';
                fila.parametro = (tr.querySelector('.inp-parametro') && tr.querySelector('.inp-parametro').value) ? tr.querySelector('.inp-parametro').value.trim() : '';
                fila.unidades = (tr.querySelector('.inp-unidades') && tr.querySelector('.inp-unidades').value) ? tr.querySelector('.inp-unidades').value.trim() : '';
                fila.stdMin = (tr.querySelector('.inp-stdMin') && tr.querySelector('.inp-stdMin').value) ? tr.querySelector('.inp-stdMin').value.trim() : '';
                fila.stdMax = (tr.querySelector('.inp-stdMax') && tr.querySelector('.inp-stdMax').value) ? tr.querySelector('.inp-stdMax').value.trim() : '';
                renderMapTree();
            };
            inp.addEventListener('change', fn);
            inp.addEventListener('blur', fn);
        });
    }

    function switchToDirectView() {
        syncModalFromDom();
        document.getElementById('directViewContainer').style.display = 'block';
        document.getElementById('mapViewContainer').style.display = 'none';
        document.getElementById('listViewContainer').style.display = 'none';
        document.getElementById('btnViewDirect').classList.add('active');
        document.getElementById('btnViewMap').classList.remove('active');
        document.getElementById('btnViewList').classList.remove('active');
        renderDirectView();
    }

    function switchToMapView() {
        syncModalFromDom();
        document.getElementById('directViewContainer').style.display = 'none';
        document.getElementById('mapViewContainer').style.display = 'flex';
        document.getElementById('listViewContainer').style.display = 'none';
        document.getElementById('btnViewDirect').classList.remove('active');
        document.getElementById('btnViewMap').classList.add('active');
        document.getElementById('btnViewList').classList.remove('active');
        renderMapTree();
        renderMapDetail();
    }

    function switchToListView() {
        syncDirectFromDom();
        syncModalFromDom();
        document.getElementById('directViewContainer').style.display = 'none';
        document.getElementById('mapViewContainer').style.display = 'none';
        document.getElementById('listViewContainer').style.display = 'block';
        document.getElementById('btnViewDirect').classList.remove('active');
        document.getElementById('btnViewMap').classList.remove('active');
        document.getElementById('btnViewList').classList.add('active');
        renderModalSubprocesos();
    }

    function isMapViewActive() {
        var c = document.getElementById('mapViewContainer');
        return c && c.style.display !== 'none';
    }

    function isDirectViewActive() {
        var c = document.getElementById('directViewContainer');
        return c && c.style.display !== 'none';
    }

    // ----- Vista Directa: árbol con edición inline -----
    function renderDirectView() {
        var root = document.getElementById('directViewRoot');
        if (!root) return;
        var data = getMapData();
        var nombreEstandar = (document.getElementById('modalInputNombre') && document.getElementById('modalInputNombre').value) ? document.getElementById('modalInputNombre').value.trim() : '';
        var html = '<div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Nombre del estándar</label>' +
            '<input type="text" id="directRootNombre" class="mm-root-input" placeholder="Nombre del estándar" value="' + escapeHtml(nombreEstandar) + '"></div>';
        if (!data || data.length === 0) {
            html += '<div class="map-tree-empty"><p>No hay subprocesos.</p><button type="button" class="btn-add-discreto mt-2" id="directAddFirstSub"><i class="fas fa-plus"></i> Agregar primer subproceso</button></div>';
            root.innerHTML = html;
            var btn = root.querySelector('#directAddFirstSub');
            if (btn) btn.onclick = function() {
                modalSubprocesos.push({ subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                renderDirectView();
            };
            var rootNombreInp = root.querySelector('#directRootNombre');
            if (rootNombreInp) rootNombreInp.onchange = rootNombreInp.onblur = function() {
                var inp = document.getElementById('modalInputNombre');
                if (inp) inp.value = this.value.trim();
            };
            return;
        }
        data.forEach(function(sub, subIdx) {
            var subKey = 'sub-' + subIdx;
            var isCollapsed = mapCollapsed[subKey];
            var subVal = (sub.subproceso && sub.subproceso.trim()) ? sub.subproceso : '';
            var subNum = (subIdx + 1) + '.';
            html += '<div class="mm-branch direct-branch">';
            html += '<div class="mm-inline-sub">';
            html += '<button type="button" class="mm-toggle' + (isCollapsed ? ' collapsed' : '') + '" data-toggle="' + subKey + '" aria-label="Expandir/colapsar">' + (isCollapsed ? '<span class="plus">+</span>' : '<span class="minus">−</span>') + '</button>';
            html += '<span class="mm-num">' + subNum + '</span>';
            html += '<input type="text" class="direct-inp-sub" data-sub="' + subIdx + '" value="' + escapeHtml(subVal) + '" placeholder="Subproceso" list="datalistSubproceso">';
            html += '<button type="button" class="mm-add map-add-sub" data-after-sub="' + subIdx + '" title="Agregar subproceso"><i class="fas fa-plus"></i></button>';
            html += '<button type="button" class="mm-delete map-delete-sub" data-sub="' + subIdx + '" title="Eliminar subproceso"><i class="fas fa-trash-alt"></i></button>';
            html += '</div>';
            if (!isCollapsed && sub.actividades && sub.actividades.length > 0) {
                html += '<div class="mm-children">';
                sub.actividades.forEach(function(act, actIdx) {
                    var actKey = 'act-' + subIdx + '-' + actIdx;
                    var actIsCollapsed = mapCollapsed[actKey];
                    var actVal = (act.actividad && act.actividad.trim()) ? act.actividad : '';
                    var actNum = (subIdx + 1) + '.' + (actIdx + 1) + '.';
                    html += '<div class="mm-inline-act">';
                    html += '<button type="button" class="mm-toggle' + (actIsCollapsed ? ' collapsed' : '') + '" data-toggle="' + actKey + '">' + (actIsCollapsed ? '<span class="plus">+</span>' : '<span class="minus">−</span>') + '</button>';
                    html += '<span class="mm-num">' + actNum + '</span>';
                    html += '<input type="text" class="direct-inp-act" data-sub="' + subIdx + '" data-act="' + actIdx + '" value="' + escapeHtml(actVal) + '" placeholder="Actividad" list="datalistActividad">';
                    html += '<button type="button" class="mm-add map-add-act" data-sub="' + subIdx + '" data-act="' + actIdx + '"><i class="fas fa-plus"></i></button>';
                    html += '<button type="button" class="mm-delete map-delete-act" data-sub="' + subIdx + '" data-act="' + actIdx + '"><i class="fas fa-trash-alt"></i></button>';
                    html += '</div>';
                    if (!actIsCollapsed && act.filas && act.filas.length > 0) {
                        html += '<div class="mm-children">';
                        act.filas.forEach(function(fila, fIdx) {
                            html += '<div class="mm-param-row direct-param-row" data-sub="' + subIdx + '" data-act="' + actIdx + '" data-fila="' + fIdx + '">';
                            html += '<input type="text" class="form-control compact col-tipo direct-inp-tipo" list="datalistTipo" value="' + escapeHtml(fila.tipo || '') + '" placeholder="Tipo">';
                            html += '<input type="text" class="form-control compact col-param direct-inp-param" list="datalistParametro" value="' + escapeHtml(fila.parametro || '') + '" placeholder="Parámetro">';
                            html += '<input type="text" class="form-control compact col-unid direct-inp-unid" list="datalistUnidades" value="' + escapeHtml(fila.unidades || '') + '" placeholder="Un.">';
                            html += '<input type="text" class="form-control compact col-std direct-inp-stdmin" value="' + escapeHtml(fila.stdMin || '') + '" placeholder="Min">';
                            html += '<input type="text" class="form-control compact col-std direct-inp-stdmax" value="' + escapeHtml(fila.stdMax || '') + '" placeholder="Max">';
                            html += '<button type="button" class="mm-delete direct-delete-fila" data-sub="' + subIdx + '" data-act="' + actIdx + '" data-fila="' + fIdx + '"><i class="fas fa-trash-alt"></i></button>';
                            html += '</div>';
                        });
                        html += '<button type="button" class="btn-add-discreto mt-1 direct-add-fila" data-sub="' + subIdx + '" data-act="' + actIdx + '"><i class="fas fa-plus"></i> Agregar parámetro</button>';
                        html += '</div>';
                    }
                });
                html += '</div>';
            }
            html += '</div>';
        });
        html += '<button type="button" class="btn-add-discreto mt-3 map-add-sub-root" title="Agregar subproceso"><i class="fas fa-plus"></i> Agregar subproceso</button>';
        root.innerHTML = html;
        var rootNombreInp = root.querySelector('#directRootNombre');
        if (rootNombreInp) {
            rootNombreInp.onchange = rootNombreInp.onblur = function() {
                var inp = document.getElementById('modalInputNombre');
                if (inp) inp.value = this.value.trim();
            };
        }
        bindDirectViewEvents();
    }

    function syncDirectFromDom() {
        var root = document.getElementById('directViewRoot');
        if (!root || !isDirectViewActive()) return;
        var nombreInp = root.querySelector('#directRootNombre');
        if (nombreInp) {
            var m = document.getElementById('modalInputNombre');
            if (m) m.value = nombreInp.value.trim();
        }
        root.querySelectorAll('.direct-inp-sub').forEach(function(inp) {
            var idx = parseInt(inp.getAttribute('data-sub'), 10);
            if (modalSubprocesos[idx]) modalSubprocesos[idx].subproceso = inp.value.trim();
        });
        root.querySelectorAll('.direct-inp-act').forEach(function(inp) {
            var subIdx = parseInt(inp.getAttribute('data-sub'), 10);
            var actIdx = parseInt(inp.getAttribute('data-act'), 10);
            if (modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx]) {
                modalSubprocesos[subIdx].actividades[actIdx].actividad = inp.value.trim();
            }
        });
        root.querySelectorAll('.direct-param-row').forEach(function(row) {
            var subIdx = parseInt(row.getAttribute('data-sub'), 10);
            var actIdx = parseInt(row.getAttribute('data-act'), 10);
            var filaIdx = parseInt(row.getAttribute('data-fila'), 10);
            var act = modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx];
            var fila = act && act.filas && act.filas[filaIdx];
            if (fila) {
                fila.tipo = (row.querySelector('.direct-inp-tipo') && row.querySelector('.direct-inp-tipo').value) ? row.querySelector('.direct-inp-tipo').value.trim() : '';
                fila.parametro = (row.querySelector('.direct-inp-param') && row.querySelector('.direct-inp-param').value) ? row.querySelector('.direct-inp-param').value.trim() : '';
                fila.unidades = (row.querySelector('.direct-inp-unid') && row.querySelector('.direct-inp-unid').value) ? row.querySelector('.direct-inp-unid').value.trim() : '';
                fila.stdMin = (row.querySelector('.direct-inp-stdmin') && row.querySelector('.direct-inp-stdmin').value) ? row.querySelector('.direct-inp-stdmin').value.trim() : '';
                fila.stdMax = (row.querySelector('.direct-inp-stdmax') && row.querySelector('.direct-inp-stdmax').value) ? row.querySelector('.direct-inp-stdmax').value.trim() : '';
            }
        });
    }

    function bindDirectViewEvents() {
        var root = document.getElementById('directViewRoot');
        if (!root) return;
        root.querySelectorAll('.direct-inp-sub').forEach(function(inp) {
            inp.onchange = inp.onblur = function() {
                var idx = parseInt(inp.getAttribute('data-sub'), 10);
                if (modalSubprocesos[idx]) modalSubprocesos[idx].subproceso = inp.value.trim();
            };
        });
        root.querySelectorAll('.direct-inp-act').forEach(function(inp) {
            inp.onchange = inp.onblur = function() {
                var subIdx = parseInt(inp.getAttribute('data-sub'), 10);
                var actIdx = parseInt(inp.getAttribute('data-act'), 10);
                if (modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx]) {
                    modalSubprocesos[subIdx].actividades[actIdx].actividad = inp.value.trim();
                }
            };
        });
        root.querySelectorAll('.direct-inp-tipo, .direct-inp-param, .direct-inp-unid, .direct-inp-stdmin, .direct-inp-stdmax').forEach(function(inp) {
            var row = inp.closest('.direct-param-row');
            if (!row) return;
            var subIdx = parseInt(row.getAttribute('data-sub'), 10);
            var actIdx = parseInt(row.getAttribute('data-act'), 10);
            var filaIdx = parseInt(row.getAttribute('data-fila'), 10);
            var fn = function() {
                var act = modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx];
                var fila = act && act.filas && act.filas[filaIdx];
                if (fila) {
                    fila.tipo = (row.querySelector('.direct-inp-tipo') && row.querySelector('.direct-inp-tipo').value) ? row.querySelector('.direct-inp-tipo').value.trim() : '';
                    fila.parametro = (row.querySelector('.direct-inp-param') && row.querySelector('.direct-inp-param').value) ? row.querySelector('.direct-inp-param').value.trim() : '';
                    fila.unidades = (row.querySelector('.direct-inp-unid') && row.querySelector('.direct-inp-unid').value) ? row.querySelector('.direct-inp-unid').value.trim() : '';
                    fila.stdMin = (row.querySelector('.direct-inp-stdmin') && row.querySelector('.direct-inp-stdmin').value) ? row.querySelector('.direct-inp-stdmin').value.trim() : '';
                    fila.stdMax = (row.querySelector('.direct-inp-stdmax') && row.querySelector('.direct-inp-stdmax').value) ? row.querySelector('.direct-inp-stdmax').value.trim() : '';
                }
            };
            inp.addEventListener('change', fn);
            inp.addEventListener('blur', fn);
        });
        root.querySelectorAll('.mm-toggle').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var key = btn.getAttribute('data-toggle');
                mapCollapsed[key] = !mapCollapsed[key];
                renderDirectView();
            };
        });
        root.querySelectorAll('.map-add-sub').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var after = parseInt(btn.getAttribute('data-after-sub'), 10);
                modalSubprocesos.splice(after + 1, 0, { subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                mapCollapsed['sub-' + (after + 1)] = false;
                renderDirectView();
            };
        });
        root.querySelectorAll('.map-add-act').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                if (!modalSubprocesos[subIdx]) return;
                modalSubprocesos[subIdx].actividades = modalSubprocesos[subIdx].actividades || [];
                modalSubprocesos[subIdx].actividades.splice(actIdx + 1, 0, { actividad: '', filas: [] });
                mapCollapsed['act-' + subIdx + '-' + (actIdx + 1)] = false;
                renderDirectView();
            };
        });
        root.querySelectorAll('.map-add-sub-root').forEach(function(btn) {
            btn.onclick = function() {
                modalSubprocesos.push({ subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                mapCollapsed['sub-' + (modalSubprocesos.length - 1)] = false;
                renderDirectView();
            };
        });
        root.querySelectorAll('.map-delete-sub').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                modalSubprocesos.splice(subIdx, 1);
                renderDirectView();
            };
        });
        root.querySelectorAll('.map-delete-act').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                if (modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades) {
                    modalSubprocesos[subIdx].actividades.splice(actIdx, 1);
                    renderDirectView();
                }
            };
        });
        root.querySelectorAll('.direct-add-fila').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                var act = modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx];
                if (!act) return;
                act.filas = act.filas || [];
                act.filas.push({ tipo: '', parametro: '', unidades: '', stdMin: '', stdMax: '' });
                renderDirectView();
            };
        });
        root.querySelectorAll('.direct-delete-fila').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                var filaIdx = parseInt(btn.getAttribute('data-fila'), 10);
                var act = modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades && modalSubprocesos[subIdx].actividades[actIdx];
                if (act && act.filas) {
                    act.filas.splice(filaIdx, 1);
                    renderDirectView();
                }
            };
        });
    }

    // ----- Árbol principal: Vista Directa colapsable (raíz + subprocesos → actividades → parámetros) -----
    // mainCollapsed: 'est-X', 'sub-X-Y', 'act-X-Y-Z' — por defecto true (colapsado)
    function renderArbolPrincipal() {
        var root = document.getElementById('arbolPrincipalRoot');
        var empty = document.getElementById('arbolPrincipalEmpty');
        var toolbar = document.getElementById('arbolPrincipalToolbar');
        if (!root) return;
        if (!mainEstandarData || !mainEstandarData.estandares) {
            root.innerHTML = '';
            if (empty) empty.style.display = 'block';
            if (toolbar) toolbar.style.display = 'none';
            return;
        }
        if (empty) empty.style.display = 'none';
        if (toolbar) toolbar.style.display = 'none';
        var estandares = mainEstandarData.estandares || [];
        var html = '<div class="direct-view">';
        html += '<div class="arbol-pp-root" style="margin-bottom:1rem">PROCESO PRODUCTIVO</div>';
        if (estandares.length === 0) {
            html += '<div class="map-tree-empty"><p>No hay estándares registrados.</p><button type="button" class="btn-add-discreto mt-2" id="mainAddFirstEstandar"><i class="fas fa-plus"></i> Crear primer estándar</button></div>';
        } else {
            estandares.forEach(function(est, estIdx) {
                var subs = est.subprocesos || [];
                var estKey = 'est-' + estIdx;
                var isEstCollapsed = mainCollapsed[estKey] !== false;
                if (!(estKey in mainCollapsed)) mainCollapsed[estKey] = true;
                else isEstCollapsed = mainCollapsed[estKey];
                var nombreEst = (est.nombre && est.nombre.trim()) ? est.nombre.trim() : '';
                html += '<div class="mm-branch main-branch-est">';
                html += '<div class="arbol-pp-estandar-wrap" style="display:flex;align-items:center;gap:0.4rem;flex-wrap:wrap;margin-bottom:0.5rem">';
                html += '<button type="button" class="mm-toggle main-toggle' + (isEstCollapsed ? ' collapsed' : '') + '" data-toggle="' + estKey + '">' + (isEstCollapsed ? '<span class="plus">+</span>' : '<span class="minus">−</span>') + '</button>';
                html += '<span class="mm-num" style="min-width:1.2em">' + (estIdx + 1) + '.</span>';
                html += '<button type="button" class="mm-move main-move-est-up' + (estIdx === 0 ? ' disabled' : '') + '" data-est="' + estIdx + '" title="Subir"' + (estIdx === 0 ? ' disabled' : '') + '><i class="fas fa-chevron-up"></i></button>';
                html += '<button type="button" class="mm-move main-move-est-down' + (estIdx === estandares.length - 1 ? ' disabled' : '') + '" data-est="' + estIdx + '" title="Bajar"' + (estIdx === estandares.length - 1 ? ' disabled' : '') + '><i class="fas fa-chevron-down"></i></button>';
                html += '<input type="text" class="main-inp-est" data-est="' + estIdx + '" value="' + escapeHtml(nombreEst) + '" placeholder="Nombre del estándar" style="flex:1;min-width:180px;padding:0.35rem 0.5rem;border:1px solid #cbd5e1;border-radius:0.375rem">';
                html += '<button type="button" class="btn-primary main-btn-guardar" data-est="' + estIdx + '" title="Guardar estándar"><i class="fas fa-save"></i> Guardar</button>';
                if (est.id) html += '<button type="button" class="mm-delete main-btn-eliminar-est" data-est="' + estIdx + '" data-est-id="' + est.id + '" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';
                html += '</div>';
                if (!isEstCollapsed) {
                    if (subs.length === 0) {
                        html += '<div class="mm-children" style="margin-left:1.5rem"><button type="button" class="btn-add-discreto mt-1 main-add-first-sub" data-est="' + estIdx + '"><i class="fas fa-plus"></i> Agregar primer subproceso</button></div>';
                    } else {
                        html += '<div class="mm-children" style="margin-left:1.25rem;padding-left:0.75rem;border-left:2px solid #94a3b8">';
                        subs.forEach(function(sub, subIdx) {
                            var subKey = 'sub-' + estIdx + '-' + subIdx;
                            var isSubCollapsed = mainCollapsed[subKey] !== false;
                            if (!(subKey in mainCollapsed)) mainCollapsed[subKey] = true;
                            else isSubCollapsed = mainCollapsed[subKey];
                            var subVal = (sub.subproceso && sub.subproceso.trim()) ? sub.subproceso : '';
                            var roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'][subIdx] || (subIdx + 1) + '.';
                            html += '<div class="mm-branch main-branch-sub">';
                            html += '<div class="mm-inline-sub" style="display:flex;align-items:center;gap:0.35rem;margin:0.25rem 0">';
                            html += '<button type="button" class="mm-toggle main-toggle' + (isSubCollapsed ? ' collapsed' : '') + '" data-toggle="' + subKey + '">' + (isSubCollapsed ? '<span class="plus">+</span>' : '<span class="minus">−</span>') + '</button>';
                            html += '<span class="mm-num">' + roman + '</span>';
                            html += '<button type="button" class="mm-move main-move-sub-up' + (subIdx === 0 ? ' disabled' : '') + '" data-est="' + estIdx + '" data-sub="' + subIdx + '" title="Subir"' + (subIdx === 0 ? ' disabled' : '') + '><i class="fas fa-chevron-up"></i></button>';
                            html += '<button type="button" class="mm-move main-move-sub-down' + (subIdx === subs.length - 1 ? ' disabled' : '') + '" data-est="' + estIdx + '" data-sub="' + subIdx + '" title="Bajar"' + (subIdx === subs.length - 1 ? ' disabled' : '') + '><i class="fas fa-chevron-down"></i></button>';
                            html += '<input type="text" class="main-inp-sub" data-est="' + estIdx + '" data-sub="' + subIdx + '" value="' + escapeHtml(subVal) + '" placeholder="Subproceso" list="datalistSubproceso" style="flex:1;min-width:100px">';
                            html += '<button type="button" class="mm-add main-add-sub-before" data-est="' + estIdx + '" data-sub="' + subIdx + '" title="Agregar antes"><i class="fas fa-plus"></i></button>';
                            html += '<button type="button" class="mm-add main-add-sub" data-est="' + estIdx + '" data-after-sub="' + subIdx + '" title="Agregar después"><i class="fas fa-plus"></i></button>';
                            html += '<button type="button" class="mm-delete main-delete-sub" data-est="' + estIdx + '" data-sub="' + subIdx + '"><i class="fas fa-trash-alt"></i></button>';
                            html += '</div>';
                            if (!isSubCollapsed && sub.actividades && sub.actividades.length > 0) {
                                html += '<div class="mm-children" style="margin-left:1rem">';
                                sub.actividades.forEach(function(act, actIdx) {
                                    var actKey = 'act-' + estIdx + '-' + subIdx + '-' + actIdx;
                                    var actIsCollapsed = mainCollapsed[actKey] !== false;
                                    if (!(actKey in mainCollapsed)) mainCollapsed[actKey] = true;
                                    else actIsCollapsed = mainCollapsed[actKey];
                                    var actVal = (act.actividad && act.actividad.trim()) ? act.actividad : '';
                                    html += '<div class="mm-inline-act" style="display:flex;align-items:center;gap:0.4rem;margin:0.15rem 0">';
                                    html += '<button type="button" class="mm-toggle main-toggle' + (actIsCollapsed ? ' collapsed' : '') + '" data-toggle="' + actKey + '">' + (actIsCollapsed ? '<span class="plus">+</span>' : '<span class="minus">−</span>') + '</button>';
                                    html += '<span class="mm-num">' + (actIdx + 1) + '.</span>';
                                    html += '<button type="button" class="mm-move main-move-act-up' + (actIdx === 0 ? ' disabled' : '') + '" data-est="' + estIdx + '" data-sub="' + subIdx + '" data-act="' + actIdx + '" title="Subir"' + (actIdx === 0 ? ' disabled' : '') + '><i class="fas fa-chevron-up"></i></button>';
                                    html += '<button type="button" class="mm-move main-move-act-down' + (actIdx === sub.actividades.length - 1 ? ' disabled' : '') + '" data-est="' + estIdx + '" data-sub="' + subIdx + '" data-act="' + actIdx + '" title="Bajar"' + (actIdx === sub.actividades.length - 1 ? ' disabled' : '') + '><i class="fas fa-chevron-down"></i></button>';
                                    html += '<input type="text" class="main-inp-act" data-est="' + estIdx + '" data-sub="' + subIdx + '" data-act="' + actIdx + '" value="' + escapeHtml(actVal) + '" placeholder="Actividad" list="datalistActividad" style="flex:1;min-width:100px">';
                                    html += '<button type="button" class="mm-add main-add-act" data-est="' + estIdx + '" data-sub="' + subIdx + '" data-act="' + actIdx + '"><i class="fas fa-plus"></i></button>';
                                    html += '<button type="button" class="mm-delete main-delete-act" data-est="' + estIdx + '" data-sub="' + subIdx + '" data-act="' + actIdx + '"><i class="fas fa-trash-alt"></i></button>';
                                    html += '</div>';
                                    if (!actIsCollapsed && act.filas && act.filas.length > 0) {
                                        html += '<div class="mm-children" style="margin-left:2rem">';
                                        act.filas.forEach(function(fila, fIdx) {
                                            html += '<div class="mm-param-row main-param-row" data-est="' + estIdx + '" data-sub="' + subIdx + '" data-act="' + actIdx + '" data-fila="' + fIdx + '">';
                                            html += '<span class="mm-num" style="min-width:1.5em;color:#64748b;font-size:0.75rem">' + (fIdx + 1) + '.</span>';
                                            html += '<button type="button" class="mm-move main-move-fila-up' + (fIdx === 0 ? ' disabled' : '') + '" data-est="' + estIdx + '" data-sub="' + subIdx + '" data-act="' + actIdx + '" data-fila="' + fIdx + '" title="Subir"' + (fIdx === 0 ? ' disabled' : '') + '><i class="fas fa-chevron-up"></i></button>';
                                            html += '<button type="button" class="mm-move main-move-fila-down' + (fIdx === act.filas.length - 1 ? ' disabled' : '') + '" data-est="' + estIdx + '" data-sub="' + subIdx + '" data-act="' + actIdx + '" data-fila="' + fIdx + '" title="Bajar"' + (fIdx === act.filas.length - 1 ? ' disabled' : '') + '><i class="fas fa-chevron-down"></i></button>';
                                            html += '<input type="text" class="form-control compact col-tipo main-inp-tipo" list="datalistTipo" value="' + escapeHtml(fila.tipo || '') + '" placeholder="Tipo">';
                                            html += '<input type="text" class="form-control compact col-param main-inp-param" list="datalistParametro" value="' + escapeHtml(fila.parametro || '') + '" placeholder="Parámetro">';
                                            html += '<input type="text" class="form-control compact col-unid main-inp-unid" list="datalistUnidades" value="' + escapeHtml(fila.unidades || '') + '" placeholder="Un.">';
                                            html += '<input type="text" class="form-control compact col-std main-inp-stdmin" value="' + escapeHtml(fila.stdMin || '') + '" placeholder="Min">';
                                            html += '<input type="text" class="form-control compact col-std main-inp-stdmax" value="' + escapeHtml(fila.stdMax || '') + '" placeholder="Max">';
                                            html += '<button type="button" class="mm-delete main-delete-fila" data-est="' + estIdx + '" data-sub="' + subIdx + '" data-act="' + actIdx + '" data-fila="' + fIdx + '"><i class="fas fa-trash-alt"></i></button>';
                                            html += '</div>';
                                        });
                                        html += '<button type="button" class="btn-add-discreto mt-1 main-add-fila" data-est="' + estIdx + '" data-sub="' + subIdx + '" data-act="' + actIdx + '"><i class="fas fa-plus"></i> Agregar parámetro</button>';
                                        html += '</div>';
                                    }
                                });
                                html += '<button type="button" class="btn-add-discreto mt-1 main-add-act" data-est="' + estIdx + '" data-sub="' + subIdx + '"><i class="fas fa-plus"></i> Agregar actividad</button>';
                                html += '</div>';
                            } else if (!isSubCollapsed) {
                                html += '<div class="mm-children" style="margin-left:1rem"><button type="button" class="btn-add-discreto mt-1 main-add-first-act" data-est="' + estIdx + '" data-sub="' + subIdx + '"><i class="fas fa-plus"></i> Agregar actividad</button></div>';
                            }
                            html += '</div>';
                        });
                        html += '<button type="button" class="btn-add-discreto mt-2 main-add-sub-root" data-est="' + estIdx + '"><i class="fas fa-plus"></i> Agregar subproceso</button>';
                        html += '</div>';
                    }
                }
                html += '</div>';
            });
            html += '<div class="mt-4"><button type="button" class="btn-add-discreto main-add-est-root"><i class="fas fa-plus"></i> Nuevo estándar</button></div>';
        }
        html += '</div>';
        root.innerHTML = html;
        var addFirst = root.querySelector('#mainAddFirstEstandar');
        if (addFirst) addFirst.onclick = function() { openModal('create'); };
        bindArbolPrincipalEvents();
    }

    function bindArbolPrincipalEvents() {
        var root = document.getElementById('arbolPrincipalRoot');
        if (!root || !mainEstandarData || !mainEstandarData.estandares) return;
        var ests = mainEstandarData.estandares;
        root.querySelectorAll('.main-inp-est').forEach(function(inp) {
            inp.onchange = inp.onblur = function() {
                var estIdx = parseInt(inp.getAttribute('data-est'), 10);
                if (ests[estIdx]) ests[estIdx].nombre = inp.value.trim();
            };
        });
        root.querySelectorAll('.main-inp-sub').forEach(function(inp) {
            inp.onchange = inp.onblur = function() {
                var estIdx = parseInt(inp.getAttribute('data-est'), 10);
                var subIdx = parseInt(inp.getAttribute('data-sub'), 10);
                if (ests[estIdx] && ests[estIdx].subprocesos && ests[estIdx].subprocesos[subIdx]) ests[estIdx].subprocesos[subIdx].subproceso = inp.value.trim();
            };
        });
        root.querySelectorAll('.main-inp-act').forEach(function(inp) {
            inp.onchange = inp.onblur = function() {
                var estIdx = parseInt(inp.getAttribute('data-est'), 10);
                var subIdx = parseInt(inp.getAttribute('data-sub'), 10);
                var actIdx = parseInt(inp.getAttribute('data-act'), 10);
                var sub = ests[estIdx] && ests[estIdx].subprocesos && ests[estIdx].subprocesos[subIdx];
                if (sub && sub.actividades && sub.actividades[actIdx]) sub.actividades[actIdx].actividad = inp.value.trim();
            };
        });
        root.querySelectorAll('.main-inp-tipo, .main-inp-param, .main-inp-unid, .main-inp-stdmin, .main-inp-stdmax').forEach(function(inp) {
            var row = inp.closest('.main-param-row');
            if (!row) return;
            var fn = function() {
                var estIdx = parseInt(row.getAttribute('data-est'), 10);
                var subIdx = parseInt(row.getAttribute('data-sub'), 10);
                var actIdx = parseInt(row.getAttribute('data-act'), 10);
                var filaIdx = parseInt(row.getAttribute('data-fila'), 10);
                var act = ests[estIdx] && ests[estIdx].subprocesos && ests[estIdx].subprocesos[subIdx] && ests[estIdx].subprocesos[subIdx].actividades && ests[estIdx].subprocesos[subIdx].actividades[actIdx];
                var fila = act && act.filas && act.filas[filaIdx];
                if (fila) {
                    fila.tipo = (row.querySelector('.main-inp-tipo') && row.querySelector('.main-inp-tipo').value) ? row.querySelector('.main-inp-tipo').value.trim() : '';
                    fila.parametro = (row.querySelector('.main-inp-param') && row.querySelector('.main-inp-param').value) ? row.querySelector('.main-inp-param').value.trim() : '';
                    fila.unidades = (row.querySelector('.main-inp-unid') && row.querySelector('.main-inp-unid').value) ? row.querySelector('.main-inp-unid').value.trim() : '';
                    fila.stdMin = (row.querySelector('.main-inp-stdmin') && row.querySelector('.main-inp-stdmin').value) ? row.querySelector('.main-inp-stdmin').value.trim() : '';
                    fila.stdMax = (row.querySelector('.main-inp-stdmax') && row.querySelector('.main-inp-stdmax').value) ? row.querySelector('.main-inp-stdmax').value.trim() : '';
                }
            };
            inp.addEventListener('change', fn);
            inp.addEventListener('blur', fn);
        });
        root.querySelectorAll('.main-toggle').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var key = btn.getAttribute('data-toggle');
                mainCollapsed[key] = !mainCollapsed[key];
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-move-sub-up').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                if (btn.disabled) return;
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                if (subIdx <= 0 || !ests[estIdx].subprocesos) return;
                var arr = ests[estIdx].subprocesos;
                var tmp = arr[subIdx]; arr[subIdx] = arr[subIdx - 1]; arr[subIdx - 1] = tmp;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-move-sub-down').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                if (btn.disabled) return;
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var arr = ests[estIdx].subprocesos;
                if (subIdx >= arr.length - 1) return;
                var tmp = arr[subIdx]; arr[subIdx] = arr[subIdx + 1]; arr[subIdx + 1] = tmp;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-move-act-up').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                if (btn.disabled) return;
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                if (actIdx <= 0) return;
                var arr = ests[estIdx].subprocesos[subIdx].actividades;
                var tmp = arr[actIdx]; arr[actIdx] = arr[actIdx - 1]; arr[actIdx - 1] = tmp;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-move-act-down').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                if (btn.disabled) return;
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                var arr = ests[estIdx].subprocesos[subIdx].actividades;
                if (actIdx >= arr.length - 1) return;
                var tmp = arr[actIdx]; arr[actIdx] = arr[actIdx + 1]; arr[actIdx + 1] = tmp;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-move-fila-up').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                if (btn.disabled) return;
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                var filaIdx = parseInt(btn.getAttribute('data-fila'), 10);
                if (filaIdx <= 0) return;
                var arr = ests[estIdx].subprocesos[subIdx].actividades[actIdx].filas;
                var tmp = arr[filaIdx]; arr[filaIdx] = arr[filaIdx - 1]; arr[filaIdx - 1] = tmp;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-move-fila-down').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                if (btn.disabled) return;
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                var filaIdx = parseInt(btn.getAttribute('data-fila'), 10);
                var arr = ests[estIdx].subprocesos[subIdx].actividades[actIdx].filas;
                if (filaIdx >= arr.length - 1) return;
                var tmp = arr[filaIdx]; arr[filaIdx] = arr[filaIdx + 1]; arr[filaIdx + 1] = tmp;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-move-est-up').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                if (btn.disabled) return;
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                if (estIdx <= 0) return;
                var arr = mainEstandarData.estandares;
                var tmp = arr[estIdx]; arr[estIdx] = arr[estIdx - 1]; arr[estIdx - 1] = tmp;
                var k1 = 'est-' + estIdx, k0 = 'est-' + (estIdx - 1);
                var v0 = mainCollapsed[k0], v1 = mainCollapsed[k1];
                if (k0 in mainCollapsed) mainCollapsed[k1] = v0;
                if (k1 in mainCollapsed) mainCollapsed[k0] = v1;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-move-est-down').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                if (btn.disabled) return;
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var arr = mainEstandarData.estandares;
                if (estIdx >= arr.length - 1) return;
                var tmp = arr[estIdx]; arr[estIdx] = arr[estIdx + 1]; arr[estIdx + 1] = tmp;
                var k1 = 'est-' + estIdx, k2 = 'est-' + (estIdx + 1);
                var v1 = mainCollapsed[k1], v2 = mainCollapsed[k2];
                if (k1 in mainCollapsed) mainCollapsed[k2] = v1;
                if (k2 in mainCollapsed) mainCollapsed[k1] = v2;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-btn-guardar').forEach(function(btn) {
            btn.onclick = function() { saveEstandarPrincipal(parseInt(btn.getAttribute('data-est'), 10)); };
        });
        root.querySelectorAll('.main-btn-eliminar-est').forEach(function(btn) {
            btn.onclick = function() {
                var id = btn.getAttribute('data-est-id');
                if (id) confirmDelete(parseInt(id, 10));
            };
        });
        root.querySelectorAll('.main-add-first-sub').forEach(function(btn) {
            btn.onclick = function() {
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                if (!ests[estIdx].subprocesos) ests[estIdx].subprocesos = [];
                ests[estIdx].subprocesos.push({ subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                mainCollapsed['sub-' + estIdx + '-0'] = false;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-add-sub-before').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                if (!ests[estIdx].subprocesos) ests[estIdx].subprocesos = [];
                ests[estIdx].subprocesos.splice(subIdx, 0, { subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                mainCollapsed['sub-' + estIdx + '-' + subIdx] = false;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-add-sub').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var after = parseInt(btn.getAttribute('data-after-sub'), 10);
                if (!ests[estIdx].subprocesos) ests[estIdx].subprocesos = [];
                ests[estIdx].subprocesos.splice(after + 1, 0, { subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                mainCollapsed['sub-' + estIdx + '-' + (after + 1)] = false;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-add-sub-root').forEach(function(btn) {
            btn.onclick = function() {
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                if (!ests[estIdx].subprocesos) ests[estIdx].subprocesos = [];
                ests[estIdx].subprocesos.push({ subproceso: '', actividades: [{ actividad: '', filas: [] }] });
                mainCollapsed['sub-' + estIdx + '-' + (ests[estIdx].subprocesos.length - 1)] = false;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-add-first-act').forEach(function(btn) {
            btn.onclick = function() {
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                if (!ests[estIdx].subprocesos[subIdx].actividades) ests[estIdx].subprocesos[subIdx].actividades = [];
                ests[estIdx].subprocesos[subIdx].actividades.push({ actividad: '', filas: [] });
                mainCollapsed['act-' + estIdx + '-' + subIdx + '-0'] = false;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-add-act').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                if (!ests[estIdx].subprocesos[subIdx].actividades) ests[estIdx].subprocesos[subIdx].actividades = [];
                ests[estIdx].subprocesos[subIdx].actividades.splice(actIdx + 1, 0, { actividad: '', filas: [] });
                mainCollapsed['act-' + estIdx + '-' + subIdx + '-' + (actIdx + 1)] = false;
                renderArbolPrincipal();
            };
        });
        root.querySelectorAll('.main-delete-sub').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                if (ests[estIdx].subprocesos) { ests[estIdx].subprocesos.splice(subIdx, 1); renderArbolPrincipal(); }
            };
        });
        root.querySelectorAll('.main-delete-act').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                if (ests[estIdx].subprocesos[subIdx] && ests[estIdx].subprocesos[subIdx].actividades) {
                    ests[estIdx].subprocesos[subIdx].actividades.splice(actIdx, 1);
                    renderArbolPrincipal();
                }
            };
        });
        root.querySelectorAll('.main-add-fila').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                var act = ests[estIdx] && ests[estIdx].subprocesos && ests[estIdx].subprocesos[subIdx] && ests[estIdx].subprocesos[subIdx].actividades && ests[estIdx].subprocesos[subIdx].actividades[actIdx];
                if (act) { act.filas = act.filas || []; act.filas.push({ tipo: '', parametro: '', unidades: '', stdMin: '', stdMax: '' }); renderArbolPrincipal(); }
            };
        });
        root.querySelectorAll('.main-delete-fila').forEach(function(btn) {
            btn.onclick = function(e) {
                e.stopPropagation();
                var estIdx = parseInt(btn.getAttribute('data-est'), 10);
                var subIdx = parseInt(btn.getAttribute('data-sub'), 10);
                var actIdx = parseInt(btn.getAttribute('data-act'), 10);
                var filaIdx = parseInt(btn.getAttribute('data-fila'), 10);
                var act = ests[estIdx] && ests[estIdx].subprocesos && ests[estIdx].subprocesos[subIdx] && ests[estIdx].subprocesos[subIdx].actividades && ests[estIdx].subprocesos[subIdx].actividades[actIdx];
                if (act && act.filas) { act.filas.splice(filaIdx, 1); renderArbolPrincipal(); }
            };
        });
        root.querySelectorAll('.main-add-est-root').forEach(function(btn) {
            btn.onclick = function() {
                if (!mainEstandarData.estandares) mainEstandarData.estandares = [];
                mainEstandarData.estandares.push({ id: null, nombre: '', subprocesos: [] });
                mainCollapsed['est-' + (mainEstandarData.estandares.length - 1)] = false;
                renderArbolPrincipal();
            };
        });
    }

    function saveEstandarPrincipal(estIdx) {
        if (!mainEstandarData || !mainEstandarData.estandares || !mainEstandarData.estandares[estIdx]) return;
        var est = mainEstandarData.estandares[estIdx];
        var nombre = (est.nombre && est.nombre.trim()) ? est.nombre.trim() : '';
        if (!nombre) {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Falta nombre', text: 'Indique el nombre del estándar.' });
            else alert('Indique el nombre.');
            return;
        }
        var payload = { nombre: nombre, subprocesos: est.subprocesos || [] };
        if (est.id) payload.id = est.id;
        fetch(getBaseUrl() + 'guardar_estandares.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    if (res.id) mainEstandarData.estandares[estIdx].id = res.id;
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Guardado', text: res.message || 'Guardado correctamente.' });
                    else alert(res.message || 'Guardado.');
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Error al guardar.' });
                    else alert('Error: ' + (res.message || 'Error al guardar.'));
                }
            })
            .catch(function() {
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
            });
    }

    function saveArbolPrincipal() {
        if (!mainEstandarData) return;
        syncMainFromDom();
        var nombre = (mainEstandarData.nombre && mainEstandarData.nombre.trim()) ? mainEstandarData.nombre.trim() : '';
        if (!nombre) {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Falta nombre', text: 'Indique el nombre del estándar.' });
            return;
        }
        var payload = { nombre: nombre, subprocesos: mainEstandarData.subprocesos || [] };
        if (mainEstandarData.id) payload.id = mainEstandarData.id;
        fetch(getBaseUrl() + 'guardar_estandares.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(payload) })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    if (res.id) mainEstandarData.id = res.id;
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Guardado', text: res.message || 'Guardado.' });
                    loadListado(res.id);
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Error al guardar.' });
                }
            })
            .catch(function() { if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' }); });
    }

    function renderTablaListado(lista) {
        var tbody = document.getElementById('tablaEstandaresBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!lista || lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">No hay registros</td></tr>';
            return;
        }
        lista.forEach(function(item, i) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', String(item.id));
            tr.innerHTML = '<td class="px-6 py-4 text-gray-700">' + (i + 1) + '</td>' +
                '<td class="px-6 py-4 text-gray-700 font-medium">' + escapeHtml(item.nombre || '') + '</td>' +
                '<td class="px-6 py-4 flex gap-2">' +
                '<button type="button" class="btn-icon p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition btn-editar-estandar" title="Editar"><i class="fa-solid fa-edit"></i></button>' +
                '<button type="button" class="btn-icon p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition btn-eliminar-estandar" title="Eliminar"><i class="fa-solid fa-trash"></i></button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function loadArbolTodos() {
        return fetch(getBaseUrl() + 'get_estandares.php?todos=1')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && Array.isArray(res.data)) {
                    mainEstandarData = { estandares: res.data };
                } else {
                    mainEstandarData = { estandares: [] };
                }
                renderArbolPrincipal();
            })
            .catch(function() {
                mainEstandarData = { estandares: [] };
                renderArbolPrincipal();
            });
    }

    function renderModalSubprocesos() {
        var cont = document.getElementById('modalArbolSubprocesos');
        if (!cont) return;
        cont.innerHTML = modalSubprocesos.map(function(sub, i) {
            return buildSubprocesoModalHtml(i, sub.subproceso, sub.actividades);
        }).join('');
        bindModalEvents();
    }

    function syncModalFromDom() {
        var collected = collectModalSubprocesos();
        if (collected && collected.length > 0) {
            modalSubprocesos = collected;
        }
    }

    function bindModalEvents() {
        var cont = document.getElementById('modalArbolSubprocesos');
        if (!cont) return;
        cont.querySelectorAll('.btn-agregar-fila').forEach(function(btn) {
            btn.onclick = function() {
                var blq = this.closest('.bloque-actividad');
                var subBlq = this.closest('.bloque-subproceso-modal');
                var subIdx = parseInt(subBlq.getAttribute('data-sub-index'), 10);
                var actIdx = parseInt(blq.getAttribute('data-act-index'), 10);
                syncModalFromDom();
                if (!modalSubprocesos[subIdx] || !modalSubprocesos[subIdx].actividades[actIdx]) return;
                modalSubprocesos[subIdx].actividades[actIdx].filas = modalSubprocesos[subIdx].actividades[actIdx].filas || [];
                modalSubprocesos[subIdx].actividades[actIdx].filas.push({ tipo: '', parametro: '', unidades: '', stdMin: '', stdMax: '' });
                renderModalSubprocesos();
            };
        });
        cont.querySelectorAll('.btn-quitar-fila').forEach(function(btn) {
            btn.onclick = function() {
                var tr = this.closest('tr');
                var blq = this.closest('.bloque-actividad');
                var subBlq = this.closest('.bloque-subproceso-modal');
                var subIdx = parseInt(subBlq.getAttribute('data-sub-index'), 10);
                var actIdx = parseInt(blq.getAttribute('data-act-index'), 10);
                var filaIdx = parseInt(tr.getAttribute('data-fila-index'), 10);
                syncModalFromDom();
                if (modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades[actIdx] && modalSubprocesos[subIdx].actividades[actIdx].filas) {
                    modalSubprocesos[subIdx].actividades[actIdx].filas.splice(filaIdx, 1);
                    renderModalSubprocesos();
                }
            };
        });
        cont.querySelectorAll('.btn-quitar-actividad').forEach(function(btn) {
            btn.onclick = function() {
                var blq = this.closest('.bloque-actividad');
                var subBlq = this.closest('.bloque-subproceso-modal');
                var subIdx = parseInt(subBlq.getAttribute('data-sub-index'), 10);
                var actIdx = parseInt(blq.getAttribute('data-act-index'), 10);
                syncModalFromDom();
                if (modalSubprocesos[subIdx] && modalSubprocesos[subIdx].actividades) {
                    modalSubprocesos[subIdx].actividades.splice(actIdx, 1);
                    renderModalSubprocesos();
                }
            };
        });
        cont.querySelectorAll('.btn-agregar-actividad-sub').forEach(function(btn) {
            btn.onclick = function() {
                var subIdx = parseInt(btn.getAttribute('data-sub-index'), 10);
                syncModalFromDom();
                if (!modalSubprocesos[subIdx]) return;
                modalSubprocesos[subIdx].actividades = modalSubprocesos[subIdx].actividades || [];
                modalSubprocesos[subIdx].actividades.push({ actividad: '', filas: [] });
                renderModalSubprocesos();
            };
        });
        cont.querySelectorAll('.btn-quitar-subproceso').forEach(function(btn) {
            btn.onclick = function() {
                var subIdx = parseInt(btn.closest('.bloque-subproceso-modal').getAttribute('data-sub-index'), 10);
                syncModalFromDom();
                modalSubprocesos.splice(subIdx, 1);
                renderModalSubprocesos();
            };
        });
    }

    function collectModalSubprocesos() {
        var result = [];
        document.querySelectorAll('#modalArbolSubprocesos .bloque-subproceso-modal').forEach(function(subBlq) {
            var subInput = subBlq.querySelector('.input-subproceso');
            var subproceso = (subInput && subInput.value) ? subInput.value.trim() : '';
            var actividades = [];
            subBlq.querySelectorAll('.bloque-actividad').forEach(function(actBlq) {
                var actInput = actBlq.querySelector('.input-actividad');
                var actividad = (actInput && actInput.value) ? actInput.value.trim() : '';
                var filas = [];
                actBlq.querySelectorAll('.tbody-filas tr').forEach(function(tr) {
                    filas.push({
                        tipo: (tr.querySelector('.inp-tipo') && tr.querySelector('.inp-tipo').value) ? tr.querySelector('.inp-tipo').value.trim() : '',
                        parametro: (tr.querySelector('.inp-parametro') && tr.querySelector('.inp-parametro').value) ? tr.querySelector('.inp-parametro').value.trim() : '',
                        unidades: (tr.querySelector('.inp-unidades') && tr.querySelector('.inp-unidades').value) ? tr.querySelector('.inp-unidades').value.trim() : '',
                        stdMin: (tr.querySelector('.inp-stdMin') && tr.querySelector('.inp-stdMin').value) ? tr.querySelector('.inp-stdMin').value.trim() : '',
                        stdMax: (tr.querySelector('.inp-stdMax') && tr.querySelector('.inp-stdMax').value) ? tr.querySelector('.inp-stdMax').value.trim() : ''
                    });
                });
                actividades.push({ actividad: actividad, filas: filas });
            });
            result.push({ subproceso: subproceso, actividades: actividades });
        });
        return result;
    }

    function getBaseUrl() {
        if (typeof window.ESTANDARES_BASE_URL === 'string' && window.ESTANDARES_BASE_URL) {
            return window.ESTANDARES_BASE_URL;
        }
        return (window.location.pathname || '').replace(/\/[^/]+\.php$/, '/');
    }

    function openModal(action, idRegistro) {
        var modal = document.getElementById('modalEstándares');
        var title = document.getElementById('modalEstándaresTitle');
        var inputNombre = document.getElementById('modalInputNombre');
        var inputId = document.getElementById('modalRegistroId');
        var cont = document.getElementById('modalArbolSubprocesos');
        modal.style.display = 'flex';
        mapSelectedNode = null;
        mapCollapsed = {};
        if (action === 'create') {
            title.textContent = 'Nuevo Registro';
            inputId.value = '';
            inputNombre.value = '';
            inputNombre.disabled = false;
            modalSubprocesos = [{ subproceso: '', actividades: [{ actividad: '', filas: [] }] }];
            switchToDirectView();
        } else {
            title.textContent = 'Editar Registro';
            inputId.value = idRegistro || '';
            inputNombre.disabled = false;
            switchToDirectView();
            var baseUrl = getBaseUrl();
            fetch(baseUrl + 'get_estandares.php?id=' + encodeURIComponent(idRegistro))
                .then(function(r) {
                    if (!r.ok) throw new Error('Error ' + r.status);
                    return r.json();
                })
                .then(function(res) {
                    if (res.success && res.data) {
                        inputNombre.value = res.data.nombre || '';
                        if (res.data.subprocesos && res.data.subprocesos.length > 0) {
                            modalSubprocesos = res.data.subprocesos.map(function(s) {
                                return {
                                    subproceso: s.subproceso || '',
                                    actividades: (s.actividades || []).map(function(a) {
                                        return { actividad: a.actividad || '', filas: a.filas || [] };
                                    })
                                };
                            });
                        } else {
                            modalSubprocesos = [{ subproceso: '', actividades: [{ actividad: '', filas: [] }] }];
                        }
                    } else {
                        modalSubprocesos = [{ subproceso: '', actividades: [{ actividad: '', filas: [] }] }];
                    }
                    renderDirectView();
                    renderMapTree();
                    renderMapDetail();
                    renderModalSubprocesos();
                })
                .catch(function(err) {
                    modalSubprocesos = [{ subproceso: '', actividades: [{ actividad: '', filas: [] }] }];
                    renderDirectView();
                    renderMapTree();
                    renderMapDetail();
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el registro.' });
                });
        }
    }

    function closeModal() {
        document.getElementById('modalEstándares').style.display = 'none';
    }

    function saveModal() {
        var inputNombre = document.getElementById('modalInputNombre');
        var inputId = document.getElementById('modalRegistroId');
        var nombre = (inputNombre && inputNombre.value) ? inputNombre.value.trim() : '';
        var id = (inputId && inputId.value) ? inputId.value.trim() : '';
        if (!nombre) {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: 'Falta nombre', text: 'Indique el nombre del estándar.' });
            else alert('Indique el nombre.');
            return;
        }
        syncDirectFromDom();
        var subprocesos = (isMapViewActive() || isDirectViewActive()) ? modalSubprocesos : collectModalSubprocesos();
        var payload = { nombre: nombre, subprocesos: subprocesos };
        if (id) payload.id = parseInt(id, 10);
        fetch(getBaseUrl() + 'guardar_estandares.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Guardado', text: res.message || 'Guardado correctamente.' });
                    else alert(res.message || 'Guardado.');
                    closeModal();
                    loadArbolTodos();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Error al guardar.' });
                    else alert('Error: ' + (res.message || 'Error al guardar.'));
                }
            })
            .catch(function() {
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
            });
    }

    function confirmDelete(id) {
        var msg = '¿Eliminar este registro y todo su contenido (subprocesos, actividades y parámetros)? Esta acción no se puede deshacer.';
        var prom = (typeof Swal !== 'undefined' && Swal.fire) ? Swal.fire({ icon: 'warning', title: 'Confirmar eliminación', text: msg, showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' }).then(function(r) { return r.isConfirmed; }) : Promise.resolve(confirm(msg));
        prom.then(function(confirmed) {
            if (!confirmed) return;
            fetch(getBaseUrl() + 'guardar_estandares.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ eliminarId: parseInt(id, 10) })
            })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Eliminado', text: res.message || 'Registro eliminado.' });
                        loadArbolTodos();
                    } else {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Error al eliminar.' });
                    }
                })
                .catch(function() {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
                });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        fillAllDatalists();

        document.getElementById('btnCerrarModalEstandares').addEventListener('click', closeModal);
        document.getElementById('modalBtnCancelar').addEventListener('click', closeModal);
        document.getElementById('modalBtnGuardar').addEventListener('click', saveModal);
        document.getElementById('modalBtnAgregarSubproceso').addEventListener('click', function() {
            syncModalFromDom();
            modalSubprocesos.push({ subproceso: '', actividades: [{ actividad: '', filas: [] }] });
            renderModalSubprocesos();
        });

        var btnViewDirect = document.getElementById('btnViewDirect');
        var btnViewMap = document.getElementById('btnViewMap');
        var btnViewList = document.getElementById('btnViewList');
        if (btnViewDirect) btnViewDirect.addEventListener('click', switchToDirectView);
        if (btnViewMap) btnViewMap.addEventListener('click', switchToMapView);
        if (btnViewList) btnViewList.addEventListener('click', switchToListView);
        var inpNombre = document.getElementById('modalInputNombre');
        if (inpNombre) inpNombre.addEventListener('input', function() { if (isMapViewActive()) renderMapTree(); });

        loadArbolTodos();
    });
})();
