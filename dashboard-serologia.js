// ============================================
// VARIABLES GLOBALES
// ============================================
window.enfermedadesActuales = [];
window.codigoEnvioActual = '';
// Guardar estados temporales por enfermedad (clave: nombre de enfermedad)
window.enfermedadStates = {};
window.currentEnfermedadSelected = null;

const CONFIG = { 
    BB: { 
        enfs: ['CAV', 'IBD', 'IBV', 'NDV', 'REO'], 
        color: 'blue', 
        bg: 'bg-blue-50', 
        text: 'text-blue-800', 
        border: 'border-blue-200' 
    }, 
    ADULTO: { 
        enfs: ['NC', 'BI', 'GUMBORO', 'APV', 'MG'], 
        color: 'orange', 
        bg: 'bg-orange-50', 
        text: 'text-orange-800', 
        border: 'border-orange-200' 
    } 
};

// ============================================
// 1. DECODIFICAR CÓDIGO REF
// ============================================
function decodificarCodRef(codRef) {
    // Formato esperado: 10 dígitos. Se separa como 3-3-2-2
    const refStr = String(codRef).padStart(10, '0');
    return {
        granja: refStr.substring(0, 3),
        campana: refStr.substring(3, 6),
        galpon: refStr.substring(6, 8),
        edad: refStr.substring(8, 10),
        codRefCompleto: parseInt(refStr, 10)
    };
}

// ============================================
// 2. CARGAR SOLICITUD DESDE SIDEBAR
// ============================================
function cargarSolicitud(codigo, fecha, referencia, estado = 'pendiente') {
    window.codigoEnvioActual = codigo;
    
    document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('formPanel').classList.remove('hidden');
    document.getElementById('lblCodigo').textContent = codigo;

    // Mostrar estado en el header (debajo del código)
    const lblEstado = document.getElementById('lblEstado');
    if (lblEstado) {
        const e = String(estado || 'pendiente').toLowerCase();
        if (e === 'pendiente') {
            lblEstado.innerHTML = `<span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>`;
        } else {
            // capitalizar primera letra
            const cap = (e.charAt(0).toUpperCase() + e.slice(1));
            lblEstado.innerHTML = `<span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">${cap}</span>`;
        }
    }

    document.getElementById('formAnalisis').reset();
    document.getElementById('codigoSolicitud').value = codigo;
    document.getElementById('fechaToma').value = fecha;

    const datosRef = decodificarCodRef(referencia);
    document.getElementById('edadAves').value = datosRef.codRefCompleto;
    document.getElementById('codRef_granja').value = datosRef.granja;
    document.getElementById('codRef_campana').value = datosRef.campana;
    // galpon y edad son distintos: galpon = posiciones 7-8, edad = posiciones 9-10
    document.getElementById('codRef_galpon').value = datosRef.galpon;
    // mantener edad en el campo que corresponde si existe
    const edadField = document.getElementById('edadAves_display');
    if (edadField) edadField.value = datosRef.edad;

    // Cargar enfermedades desde BD
    fetch(`crud-serologia.php?action=get_enfermedades&codEnvio=${codigo}`)
        .then(r => {
            if (!r.ok) throw new Error('HTTP error! status: ' + r.status);
            return r.text();
        })
        .then(text => {
            console.log('Respuesta del servidor:', text);
            const data = JSON.parse(text);
            if(data.success) {
                window.enfermedadesActuales = data.enfermedades;
                detectarTipo(parseInt(datosRef.edad));
            } else {
                alert('❌ Error: ' + (data.message || 'No se pudieron cargar enfermedades'));
            }
        })
        .catch(e => {
            console.error('Error completo:', e);
            alert('❌ Error de conexión. Ver consola para detalles.');
        });
}

// ============================================
// 3. DETECTAR TIPO (BB vs ADULTO)
// ============================================
function detectarTipo(edad) {
    // Sólo consideramos POLLO BB cuando la edad es exactamente 1
    let tipo = 'ADULTO';
    const edadInt = parseInt(edad, 10) || 0;
    if (edadInt === 1) {
        tipo = 'BB';
    }

    document.getElementById('tipo_ave_hidden').value = tipo;

    const badge = document.getElementById('badgeTipo');
    // Mostrar tipo sin indicar unidades entre paréntesis
    badge.textContent = (tipo === 'BB') ? 'POLLO BB' : 'POLLO ADULTO';
    badge.className = (tipo === 'BB') 
        ? 'px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200'
        : 'px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700 border border-orange-200';

    const campoInforme = document.getElementById('numeroInforme');
    if (campoInforme) {
        if (tipo === 'ADULTO') {
            if (campoInforme.parentElement) campoInforme.parentElement.style.display = 'none';
            campoInforme.value = '';
        } else {
            if (campoInforme.parentElement) campoInforme.parentElement.style.display = 'block';
        }
    }

    renderizarCampos(tipo);
    renderizarEnfermedades(tipo);

    setTimeout(() => {
        if (tipo === 'ADULTO') {
            const granja = document.getElementById('codRef_granja')?.value || '';
            const campana = document.getElementById('codRef_campana')?.value || '';
            const galpon = document.getElementById('codRef_galpon')?.value || '';
            const edad = document.getElementById('edadAves_display')?.value || '';

            const granjaDisplay = document.getElementById('codRef_granja_display');
            const campanaDisplay = document.getElementById('codRef_campana_display');
            const galponDisplay = document.getElementById('codRef_galpon_display');
            const edadDisplay = document.getElementById('edadAves_display');

            if (granjaDisplay) granjaDisplay.value = granja;
            if (campanaDisplay) campanaDisplay.value = campana;
            if (galponDisplay) galponDisplay.value = galpon;
            if (edadDisplay) edadDisplay.value = edad;
        }
    }, 0);
}

// ============================================
// 4. RENDERIZAR CAMPOS ESPECÍFICOS
// ============================================
function renderizarCampos(tipo) {
    const container = document.getElementById('camposEspecificos');

    if (tipo === 'BB') {
        const granja = document.getElementById('codRef_granja')?.value || '';
        const campana = document.getElementById('codRef_campana')?.value || '';
        const galpon = document.getElementById('codRef_galpon')?.value || '';
        // Para BB usamos explícitamente el campo edadAves_display si está presente
        // (contiene los últimos 2 dígitos decodificados). Si no existe, hacemos fallback
        // a tomar los últimos 2 dígitos de `edadAves`.
        let edadRef = '';
        const edadDisplayVal = document.getElementById('edadAves_display')?.value;
        if (edadDisplayVal && String(edadDisplayVal).length > 0) {
            edadRef = String(edadDisplayVal).slice(-2);
        } else {
            const edadAvesFull = document.getElementById('edadAves')?.value || '';
            const tmp = String(edadAvesFull);
            edadRef = tmp.length > 2 ? tmp.slice(-2) : tmp;
        }

        container.innerHTML = `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 col-span-3 mb-4">
                <h4 class="text-[10px] font-bold text-blue-700 uppercase mb-3 flex items-center gap-1">
                    <i class="fas fa-lock"></i> Datos Decodificados del Código Ref
                </h4>
                <div class="grid grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Granja</label>
                        <input type="text" id="codRef_granja_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${granja}" readonly>
                        <input type="hidden" id="codRef_granja" name="codigo_granja" value="${granja}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Campaña</label>
                        <input type="text" id="codRef_campana_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${campana}" readonly>
                        <input type="hidden" id="codRef_campana" name="codigo_campana" value="${campana}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Galpón</label>
                        <input type="text" id="codRef_galpon_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${galpon}" readonly>
                        <input type="hidden" id="codRef_galpon" name="numero_galpon" value="${galpon}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Edad (Ref)</label>
                        <input type="text" id="edadAves_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${edadRef}" readonly>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Planta Incubación</label>
                <select name="planta_incubacion" class="input-lab">
                    <option value="CHINCHA">CHINCHA</option>
                    <option value="HUARMEY">HUARMEY</option>
                    <option value="ICA">ICA</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Lote</label>
                <input type="text" name="lote" class="input-lab">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Edad Reprod.</label>
                <input type="number" name="edad_reproductora" class="input-lab">
            </div>
        `;
    } else {
        const granja = document.getElementById('codRef_granja')?.value || '';
        const campana = document.getElementById('codRef_campana')?.value || '';
        const galpon = document.getElementById('codRef_galpon')?.value || '';
        const edad = document.getElementById('edadAves_display')?.value || '';
        
        container.innerHTML = `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 col-span-3">
                <h4 class="text-[10px] font-bold text-blue-700 uppercase mb-3 flex items-center gap-1">
                    <i class="fas fa-lock"></i> Datos Decodificados del Código Ref
                </h4>
                <div class="grid grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Granja</label>
                        <input type="text" id="codRef_granja_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${granja}" readonly>
                        <input type="hidden" id="codRef_granja" name="codigo_granja" value="${granja}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Campaña</label>
                        <input type="text" id="codRef_campana_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${campana}" readonly>
                        <input type="hidden" id="codRef_campana" name="codigo_campana" value="${campana}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Galpón</label>
                        <input type="text" id="codRef_galpon_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${galpon}" readonly>
                        <input type="hidden" id="codRef_galpon" name="numero_galpon" value="${galpon}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-700 uppercase mb-1">Edad (Ref)</label>
                        <input type="text" id="edadAves_display" class="input-lab bg-blue-100 border-blue-300 text-blue-900 font-bold text-center cursor-not-allowed" value="${edad}" readonly>
                    </div>
                </div>
            </div>
        `;
    }
}

// ============================================
// 5. RENDERIZAR ENFERMEDADES
// ============================================
function renderizarEnfermedades(tipo) {
    const container = document.getElementById('contenedorEnfermedades');
    
    const enfermedades = window.enfermedadesActuales || [];
    
    if(enfermedades.length === 0) {
        container.innerHTML = '<p class="text-red-500 text-sm">⚠️ No hay enfermedades asignadas</p>';
        return;
    }
    
    const conf = (tipo === 'BB') 
        ? { color: 'blue', bg: 'bg-blue-50', text: 'text-blue-800', border: 'border-blue-200' }
        : { color: 'orange', bg: 'bg-orange-50', text: 'text-orange-800', border: 'border-orange-200' };

    let html = `
        <div class="flex justify-between items-center mb-4 gap-3">
            <div class="flex-1">
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-2">
                    Seleccione Enfermedad (${enfermedades.length} asignada${enfermedades.length !== 1 ? 's' : ''})
                </label>
                <select id="selectEnfermedad" class="input-lab">
                    ${enfermedades.map(e => `<option value="${e.nombre}">${e.nombre}</option>`).join('')}
                </select>
            </div>
            <div class="pt-5">
                <button type="button" onclick="abrirModalAgregarEnfermedad()" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold text-sm shadow-lg transition-all">
                    <i class="fas fa-plus-circle mr-2"></i> Agregar Enfermedad
                </button>
            </div>
        </div>
        <div id="enfermedadPanel"></div>
    `;

    container.innerHTML = html;

    const select = document.getElementById('selectEnfermedad');
    const inicial = select.value;
    // establecer el seleccionado actual
    window.currentEnfermedadSelected = inicial;
    renderEnfermedadPanel(inicial, conf, tipo);

    select.addEventListener('change', (e) => {
        const nuevo = e.target.value;
        // guardar estado de la enfermedad anterior antes de renderizar la nueva
        if (window.currentEnfermedadSelected) saveCurrentEnfermedadState(window.currentEnfermedadSelected);
        window.currentEnfermedadSelected = nuevo;
        renderEnfermedadPanel(nuevo, conf, tipo);
    });
}

// ============================================
// 6. RENDERIZAR PANEL DE ENFERMEDAD
// ============================================
function renderEnfermedadPanel(enf, conf, tipo) {
    const panel = document.getElementById('enfermedadPanel');
    // Construir HTML dinámico para niveles según tipo (BB: n0..n25, ADULTO: s1..s6)
    let nivelesHtml = '';
    if ((tipo || '').toUpperCase() === 'ADULTO') {
        // ADULTO: mostrar 6 niveles s1..s6
        nivelesHtml = `<div class="mt-2 grid grid-cols-6 gap-2 bg-gray-50 p-2 rounded border border-gray-100">` +
            Array.from({length: 6}, (_, i) => {
                const idx = i + 1;
                return `<input type="number" name="${enf}_s${idx}" placeholder="S${idx}" class="text-center text-[10px] border border-gray-300 rounded h-7 w-full focus:border-blue-500 outline-none">`;
            }).join('') +
        `</div>`;
    } else {
        // BB: mejorar diseño de niveles (6 columnas responsive, inputs más grandes)
        nivelesHtml = `<div class="mt-2 bg-gray-50 p-3 rounded border border-gray-100">
            <div class="text-sm font-semibold text-gray-600 mb-2">Niveles (N0 - N25)</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">` +
            Array.from({length: 26}, (_, i) => {
                const label = 't' + String(i+1).padStart(2, '0');
                return `<div class="flex flex-col">
                    <label class="text-[10px] text-gray-500 mb-1 text-center font-medium">${label}</label>
                    <input type="number" name="${enf}_n${i}" placeholder="${label}" class="text-center text-sm border border-gray-300 rounded py-2 px-2 w-full focus:border-blue-500 outline-none bg-white">
                 </div>`
            }).join('') +
        `</div></div>`;
    }

    panel.innerHTML = `
        <div class="border ${conf.border} rounded-lg bg-white overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div class="px-4 py-2 ${conf.bg} border-b ${conf.border} flex justify-between items-center">
                <span class="font-bold ${conf.text}">${enf}</span>
                <input type="hidden" name="enfermedades[]" value="${enf}">
                <span class="text-[10px] text-gray-500 opacity-70">Resultados</span>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1">GMEAN</label>
                        <input type="number" step="0.01" name="${enf}_gmean" class="input-lab font-bold text-gray-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1">CV %</label>
                        <input type="number" step="0.01" name="${enf}_cv" class="input-lab">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1">SD</label>
                        <input type="number" step="0.01" name="${enf}_sd" class="input-lab">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1">COUNT</label>
                        <input type="number" value="20" name="${enf}_count" class="input-lab bg-gray-50 text-center">
                    </div>
                </div>

                <details class="group">
                    <summary class="flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-500 hover:text-blue-600 p-1">
                        <i class="fas fa-chart-bar"></i> Niveles
                    </summary>
                    ${nivelesHtml}
                </details>
            </div>
        </div>`;

    // Después de renderizar, intentar rellenar valores guardados (si existen)
    populatePanelValues(enf);
}

// Guarda los valores actuales del panel de enfermedad identificado por `enfName`
function saveCurrentEnfermedadState(enfName) {
    try {
        if (!enfName) return;
        const panel = document.getElementById('enfermedadPanel');
        if (!panel) return;

        const inputs = panel.querySelectorAll('input[name]');
        const state = {};
        inputs.forEach(inp => {
            // omitimos inputs tipo file
            if (inp.type === 'file') return;
            state[inp.name] = inp.value;
        });

        window.enfermedadStates[enfName] = state;
        // console.log('Guardado estado de', enfName, state);
    } catch (e) {
        console.error('Error guardando estado enfermedad:', e);
    }
}

// Rellena el panel con los valores guardados para `enfName` si existen
function populatePanelValues(enfName) {
    try {
        if (!enfName) return;
        const state = window.enfermedadStates[enfName];
        if (!state) return;
        const panel = document.getElementById('enfermedadPanel');
        if (!panel) return;

        Object.keys(state).forEach(key => {
            const el = panel.querySelector(`[name="${key}"]`);
            if (el) {
                el.value = state[key];
            }
        });
        // console.log('Restaurado estado de', enfName, state);
    } catch (e) {
        console.error('Error restaurando estado enfermedad:', e);
    }
}

// ============================================
// 7. GUARDAR DATOS
// ============================================
function guardar(e) {
    e.preventDefault();
    
    
    
    // Guardar estado del panel actualmente visible antes de empaquetar datos
    saveCurrentEnfermedadState(window.currentEnfermedadSelected);

    // Crear FormData a partir del formulario base y añadir inputs ocultos temporales
    const form = document.getElementById('formAnalisis');
    // Eliminar posibles inputs temporales previos que creamos
    document.querySelectorAll('.tmp-enf-input').forEach(n => n.remove());

    Object.keys(window.enfermedadStates || {}).forEach(enfName => {
        const st = window.enfermedadStates[enfName] || {};
        const inpE = document.createElement('input');
        inpE.type = 'hidden';
        inpE.name = 'enfermedades[]';
        inpE.value = enfName;
        inpE.className = 'tmp-enf-input';
        form.appendChild(inpE);

        Object.keys(st).forEach(key => {
            if (key.toLowerCase().includes('archivo')) return;
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = key;
            inp.value = st[key];
            inp.className = 'tmp-enf-input';
            form.appendChild(inp);
        });
    });

    // Reconstruir FormData para que incluya los inputs temporales recién añadidos
    const fd2 = new FormData(form);

    // Obtener archivos seleccionados del input
    const archivos = document.getElementById('archivoPdf') ? document.getElementById('archivoPdf').files : [];

    // Adjuntar archivos (si hay). Usamos la clave 'archivoPdf[]' para que PHP reciba arrays.
    if (archivos && archivos.length > 0) {
        for (let i = 0; i < archivos.length; i++) {
            fd2.append('archivoPdf[]', archivos[i]);
        }
    }

    // Contar archivos dentro de fd2 (más fiable que solo el input.files)
    let archivosCount = 0;
    for (let pair of fd2.entries()) {
        if (pair[1] instanceof File) archivosCount++;
    }

    // Contar cuántas enfermedades tienen datos (no solo asignadas)
    const enfermedadStates = window.enfermedadStates || {};
    let enfermedadesConDatos = Object.keys(enfermedadStates).filter(enf => {
        const st = enfermedadStates[enf] || {};
        return Object.keys(st).some(k => st[k] !== null && st[k] !== '');
    }).length;

    // Fallback: si no detectamos nada en memoria, intentar contar inputs visibles
    if (enfermedadesConDatos === 0) {
        const visibles = document.querySelectorAll('input[name="enfermedades[]"]');
        enfermedadesConDatos = visibles.length || 0;
    }

    if (enfermedadesConDatos === 0) {
        // limpiar inputs temporales
        document.querySelectorAll('.tmp-enf-input').forEach(n => n.remove());
        alert('⚠️ No hay enfermedades con datos para guardar');
        return;
    }

    // Mostrar confirm con cantidad real de enfermedades y archivos
    if (!confirm(`¿Guardar análisis con ${enfermedadesConDatos} enfermedad(es) y ${archivosCount} archivo(s)?`)) {
        // limpiar inputs temporales si cancela
        document.querySelectorAll('.tmp-enf-input').forEach(n => n.remove());
        return;
    }

    // ahora sí mostrar spinner y bloquear botón antes de enviar
    const btn = document.querySelector('button[type="submit"]');
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;
    
    console.log('📤 Datos a enviar:');
    for (let pair of fd2.entries()) {
        if (pair[1] instanceof File) {
            console.log(`  ${pair[0]}: [Archivo] ${pair[1].name}`);
        } else {
            console.log(`  ${pair[0]}: ${pair[1]}`);
        }
    }

    fetch('crud-serologia.php', { 
        method: 'POST', 
        body: fd2 
    })
    .then(r => {
        if (!r.ok) {
            throw new Error(`HTTP error! status: ${r.status}`);
        }
        return r.text();
    })
    .then(text => {
        console.log('📥 Respuesta del servidor:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Error al parsear JSON:', e);
            throw new Error('Respuesta del servidor no es JSON válido: ' + text.substring(0, 200));
        }
        
        if (data.success) {
            let mensaje = data.message;
            if (data.archivos_guardados > 0) {
                mensaje += `\n📎 ${data.archivos_guardados} archivo(s) cargado(s)`;
            }
            alert(mensaje);
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
            btn.innerHTML = original;
            btn.disabled = false;
        }
    })
    .catch(e => { 
        console.error('Error completo:', e);
        alert('❌ Error de conexión: ' + e.message); 
        btn.innerHTML = original; 
        btn.disabled = false; 
    });
}

// ============================================
// Manejo de archivos (lista, validación) - similar a rptaLaboratorio.js
// ============================================
const inputPDF = document.getElementById('archivoPdf');
const fileList = document.getElementById('fileList');

// Validaciones
const MAX_SIZE = 10 * 1024 * 1024; // 10MB
const allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'image/png',
    'image/jpeg'
];

function renderFiles() {
    if (!fileList) return;
    fileList.innerHTML = '';

    if (!inputPDF || inputPDF.files.length === 0) return;

    for (let file of inputPDF.files) {
        const div = document.createElement('div');
        div.className = 'flex justify-between items-center p-2 border rounded-md bg-gray-50';

        div.innerHTML = `
            <div>
                <p class="text-sm font-medium">${file.name}</p>
                <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
            </div>
            <button class="text-red-600 font-bold text-xl leading-none" onclick="removeFile('${file.name.replace(/'/g, "\\'")}')">×</button>
        `;

        fileList.appendChild(div);
    }
}

function removeFile(name) {
    if (!inputPDF) return;
    const dt = new DataTransfer();

    for (let file of inputPDF.files) {
        if (file.name !== name) dt.items.add(file);
    }

    inputPDF.files = dt.files;
    renderFiles();
}

if (inputPDF) {
    inputPDF.addEventListener('change', () => {
        const dt = new DataTransfer();

        for (let file of inputPDF.files) {
            // validar tipo
            if (!allowedTypes.includes(file.type)) {
                alert(`❌ Archivo no permitido: ${file.name}`);
                continue;
            }

            // validar tamaño
            if (file.size > MAX_SIZE) {
                alert(`❌ ${file.name} pesa ${(file.size / 1024 / 1024).toFixed(2)}MB (máx. 10MB)`);
                continue;
            }

            dt.items.add(file);
        }

        inputPDF.files = dt.files;
        renderFiles();
    });

    // Inicializar vista si ya hay archivos (por ejemplo al recargar)
    renderFiles();
}

// ============================================
// 8. FILTRO SIDEBAR
// ============================================
function filtrarLista() {
    const term = document.getElementById('filtroSidebar').value.toLowerCase();
    
    // Obtener estado activo actual
    let estadoActivo = 'todos';
    document.querySelectorAll('.filtro-estado-btn').forEach(btn => {
        if (btn.classList.contains('bg-blue-600')) {
            estadoActivo = btn.getAttribute('data-estado');
        }
    });
    
    document.querySelectorAll('.sidebar-item').forEach(el => {
        const itemEstado = el.getAttribute('data-estado');
        const textoItem = el.innerText.toLowerCase();
        
        // Mostrar si cumple AMBAS condiciones (estado Y búsqueda)
        const cumpleEstado = (estadoActivo === 'todos') || (itemEstado === estadoActivo);
        const cumpleBusqueda = (term === '') || textoItem.includes(term);
        
        el.style.display = (cumpleEstado && cumpleBusqueda) ? 'block' : 'none';
    });
}

// ============================================
// FILTRO POR ESTADO
// ============================================
function filtrarPorEstado(estado) {
    // Actualizar estilos de botones
    document.querySelectorAll('.filtro-estado-btn').forEach(btn => {
        const btnEstado = btn.getAttribute('data-estado');
        if (btnEstado === estado) {
            // Botón activo - azul
            btn.className = 'filtro-estado-btn flex-1 px-3 py-1.5 text-xs font-semibold rounded-full transition-all bg-blue-600 text-white';
        } else {
            // Botón inactivo - gris
            btn.className = 'filtro-estado-btn flex-1 px-3 py-1.5 text-xs font-semibold rounded-full transition-all bg-gray-200 text-gray-600 hover:bg-gray-300';
        }
    });

    // Filtrar items
    const textoFiltro = document.getElementById('filtroSidebar').value.toLowerCase();
    
    document.querySelectorAll('.sidebar-item').forEach(item => {
        const itemEstado = item.getAttribute('data-estado');
        const textoItem = item.innerText.toLowerCase();
        
        // Mostrar si cumple AMBAS condiciones: estado Y búsqueda
        const cumpleEstado = (estado === 'todos') || (itemEstado === estado);
        const cumpleBusqueda = (textoFiltro === '') || textoItem.includes(textoFiltro);
        
        item.style.display = (cumpleEstado && cumpleBusqueda) ? 'block' : 'none';
    });
}


// ============================================
// 9. MODAL AGREGAR ENFERMEDAD
// ============================================
function abrirModalAgregarEnfermedad() {
    const modal = document.createElement('div');
    modal.id = 'modalAgregarEnfermedad';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-[80vh] overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-green-700 text-white flex justify-between items-center">
                <h3 class="font-bold text-lg"><i class="fas fa-plus-circle mr-2"></i> Agregar Nueva Enfermedad</h3>
                <button onclick="cerrarModalAgregarEnfermedad()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Seleccione una enfermedad del catálogo:</p>
                <div class="mb-4">
                    <input type="text" id="filtroEnfermedades" placeholder="🔍 Buscar..." 
                        class="w-full p-3 border rounded-lg" onkeyup="filtrarEnfermedadesCatalogo()">
                </div>
                <div id="listadoEnfermedades" class="max-h-96 overflow-y-auto border rounded-lg">
                    <div class="text-center py-10">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                        <p class="text-gray-500 mt-2">Cargando...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    fetch('crud-serologia.php?action=get_catalogo_enfermedades')
        .then(r => r.text())
        .then(text => {
            console.log('Catálogo:', text);
            const data = JSON.parse(text);
            if(data.success) mostrarCatalogoEnfermedades(data.enfermedades);
        })
        .catch(e => {
            console.error(e);
            document.getElementById('listadoEnfermedades').innerHTML = '<p class="text-red-500 p-4">Error al cargar catálogo</p>';
        });
}

function cerrarModalAgregarEnfermedad() {
    const modal = document.getElementById('modalAgregarEnfermedad');
    if(modal) modal.remove();
}

function mostrarCatalogoEnfermedades(catalogo) {
    const listado = document.getElementById('listadoEnfermedades');
    const codigosAsignados = window.enfermedadesActuales.map(e => parseInt(e.codigo));
    const disponibles = catalogo.filter(e => !codigosAsignados.includes(parseInt(e.codigo)));
    
    if(disponibles.length === 0) {
        listado.innerHTML = '<p class="text-gray-500 text-center p-8">✅ Todas las enfermedades están asignadas</p>';
        return;
    }
    
    listado.innerHTML = disponibles.map(e => `
        <div class="p-3 border-b hover:bg-gray-50 cursor-pointer transition-colors enfermedad-item"
            onclick="agregarEnfermedadASolicitud('${e.codigo}', '${e.nombre}')">
            <div class="font-bold text-gray-800">${e.nombre}</div>
            <div class="text-xs text-gray-500">${e.enfermedad_completa || 'Sin descripción'}</div>
        </div>
    `).join('');
}

function filtrarEnfermedadesCatalogo() {
    const filtro = document.getElementById('filtroEnfermedades').value.toLowerCase();
    document.querySelectorAll('.enfermedad-item').forEach(item => {
        const texto = item.textContent.toLowerCase();
        item.style.display = texto.includes(filtro) ? 'block' : 'none';
    });
}

function agregarEnfermedadASolicitud(codigo, nombre) {
    if(!confirm(`¿Agregar "${nombre}" a la solicitud ${window.codigoEnvioActual}?`)) return;
    
    const codRef = document.getElementById('edadAves').value;
    const fecToma = document.getElementById('fechaToma').value;
    
    const fd = new FormData();
    fd.append('action', 'agregar_enfermedad_solicitud');
    fd.append('codEnvio', window.codigoEnvioActual);
    fd.append('codAnalisis', codigo);
    fd.append('nomAnalisis', nombre);
    fd.append('codRef', codRef);
    fd.append('fecToma', fecToma);
    
    fetch('crud-serologia.php', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(text => {
                // Intentar parsear JSON; si falla, mostrar respuesta cruda para debugging
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert('✅ Enfermedad agregada');
                        cerrarModalAgregarEnfermedad();
                        // Refrescar lista de enfermedades para la solicitud actual sin recargar la página
                        try {
                            fetch(`crud-serologia.php?action=get_enfermedades&codEnvio=${window.codigoEnvioActual}`)
                                .then(r => r.text())
                                .then(t => {
                                    try {
                                        const dd = JSON.parse(t);
                                        if (dd.success) {
                                            window.enfermedadesActuales = dd.enfermedades;
                                            const tipo = document.getElementById('tipo_ave_hidden') ? document.getElementById('tipo_ave_hidden').value : 'BB';
                                            renderizarEnfermedades(tipo);
                                        } else {
                                            console.warn('No se pudo actualizar enfermedades:', dd.message);
                                        }
                                    } catch (e) {
                                        console.error('Error parseando respuesta de get_enfermedades:', e, t);
                                    }
                                })
                                .catch(e => console.error('Error fetching enfermedades:', e));
                        } catch (e) {
                            console.warn('No se pudo refrescar lista localmente, recarga manualmente si es necesario', e);
                        }
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                } catch (err) {
                    console.error('Respuesta no JSON al agregar enfermedad:', text);
                    // Mostrar al usuario un mensaje más informativo y sugerir revisar la consola
                    alert('❌ Respuesta inesperada del servidor. Revisa la consola (F12) para ver detalles.');
                }
        })
        .catch(e => {
            console.error('Error fetch agregar_enfermedad_solicitud:', e);
            alert('❌ Error de conexión: ' + e.message);
        });
}
