let analisisDisponibles = [];

// === Cargar lista de análisis ===
function cargarListaAnalisis() {
    if (analisisDisponibles.length > 0) return;
    fetch('crud_paquete_analisis.php?action=get_analisis')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                analisisDisponibles = data.analisis;
                aplicarFiltroBusqueda(); // Renderiza inmediatamente
            }
        });
}

// === Renderizar checkboxes con filtro ===
function renderizarCheckboxes(filtro = '') {
    const container = document.getElementById('analisisCheckboxes');
    
    if (analisisDisponibles.length === 0) {
        container.innerHTML = '<p class="text-gray-500 italic col-span-full">Seleccione un tipo de muestra primero</p>';
        return;
    }

    const filtrados = analisisDisponibles.filter(a => 
        a.nombre.toLowerCase().includes(filtro.toLowerCase()) ||
        a.codigo.toLowerCase().includes(filtro.toLowerCase())
    );

    if (filtrados.length === 0) {
        container.innerHTML = '<p class="text-gray-500 italic col-span-full">No se encontraron análisis</p>';
        return;
    }

    container.innerHTML = filtrados.map(a => `
        <div class="flex items-center">
            <input type="checkbox" id="analisis_${a.codigo}" value="${a.codigo}" 
                ${analisisSeleccionadosGlobal.includes(a.codigo) ? 'checked' : ''} 
                class="mr-2 h-4 w-4 text-blue-600 rounded">
            <label for="analisis_${a.codigo}" class="text-xs leading-tight">
                <span class="font-mono bg-blue-50 px-1 py-0.5 rounded text-blue-700 text-[10px]">${a.codigo}</span>
                <span class="ml-1">${a.nombre}</span>
            </label>
        </div>
    `).join('');

    
    filtrados.forEach(a => {
        const checkbox = document.getElementById(`analisis_${a.codigo}`);
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    if (!analisisSeleccionadosGlobal.includes(a.codigo)) {
                        analisisSeleccionadosGlobal.push(a.codigo);
                    }
                } else {
                    analisisSeleccionadosGlobal = analisisSeleccionadosGlobal.filter(c => c !== a.codigo);
                }
            });
        }
    });
}

function aplicarFiltroBusqueda() {
    const filtro = document.getElementById('buscadorAnalisis').value;
    toggleBotonLimpiar();
    renderizarCheckboxes(filtro);
}

// === Obtener análisis seleccionados (para mantenerlos durante búsqueda) ===
function obtenerAnalisisSeleccionados() {
    const checks = document.querySelectorAll('#analisisCheckboxes input[type="checkbox"]:checked');
    return Array.from(checks).map(cb => cb.value);
}

// === Abrir modal ===
window.openPaqueteMuestraModal = function(action, codigo = null, nombre = '', tipoMuestra = '', analisis = []) {
    const modal = document.getElementById('paqueteMuestraModal');
    const title = document.getElementById('paqueteMuestraModalTitle');
    
    document.getElementById('paqueteMuestraModalAction').value = action;
    document.getElementById('paqueteMuestraEditCodigo').value = codigo || '';
    document.getElementById('paqueteMuestraModalNombre').value = nombre || '';
    document.getElementById('paqueteMuestraModalTipoMuestra').value = tipoMuestra || '';
    document.getElementById('buscadorAnalisis').value = '';
    document.getElementById('iconoLimpiar').style.display = 'none';

    analisisSeleccionadosGlobal = Array.isArray(analisis) ? analisis : [];

    if (action === 'create') {
        title.textContent = '➕ Nuevo Paquete de Muestra';
        document.getElementById('analisisCheckboxes').innerHTML = '<p class="text-gray-500 italic col-span-full">Seleccione un tipo de muestra primero</p>';
        modal.style.display = 'flex';
        cargarListaAnalisis();
    } 
    // 🔑 Cambiado de 'update' a 'edit' (porque en tu HTML usas "edit")
    else if (action === 'update') {
        title.textContent = '✏️ Editar Paquete de Muestra';
        
        if (analisisDisponibles.length === 0) {
            // Cargar análisis si no están disponibles
            document.getElementById('analisisCheckboxes').innerHTML = '<p class="text-gray-500 italic col-span-full">Cargando análisis...</p>';
            
            fetch('crud_paquete_analisis.php?action=get_analisis')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        analisisDisponibles = data.analisis;
                        // ✅ Ya no pasamos `analisis`, porque usamos `analisisSeleccionadosGlobal`
                        renderizarCheckboxes('');
                    } else {
                        document.getElementById('analisisCheckboxes').innerHTML = '<p class="text-red-500 col-span-full">Error al cargar análisis</p>';
                    }
                    modal.style.display = 'flex';
                })
                .catch(() => {
                    document.getElementById('analisisCheckboxes').innerHTML = '<p class="text-red-500 col-span-full">Error de red</p>';
                    modal.style.display = 'flex';
                });
        } else {
            // ✅ Renderizar con filtro vacío (usa analisisSeleccionadosGlobal internamente)
            renderizarCheckboxes('');
            modal.style.display = 'flex';
        }
    }
}
// === Cerrar modal ===
function closePaqueteMuestraModal() {
    document.getElementById('paqueteMuestraModal').style.display = 'none';
}

// === Guardar paquete (CORREGIDO) ===
function savePaqueteMuestra(event) {
    event.preventDefault();
    const action = document.getElementById('paqueteMuestraModalAction').value;
    const nombre = document.getElementById('paqueteMuestraModalNombre').value.trim();
    const tipoMuestra = document.getElementById('paqueteMuestraModalTipoMuestra').value;
    const checkboxes = document.querySelectorAll('#analisisCheckboxes input[type="checkbox"]:checked');
    const analisis = [...analisisSeleccionadosGlobal];
    const codigo = document.getElementById('paqueteMuestraEditCodigo').value;

    // Validaciones reforzadas
    if (!nombre) {
        alert('⚠️ El nombre del paquete es obligatorio.');
        document.getElementById('paqueteMuestraModalNombre').focus();
        return;
    }
    if (!tipoMuestra) {
        alert('⚠️ Debe seleccionar un tipo de muestra.');
        document.getElementById('paqueteMuestraModalTipoMuestra').focus();
        return;
    }
    if (analisis.length === 0) {
        alert('⚠️ Debe seleccionar al menos un análisis.');
        return;
    }

    const params = { action, nombre, tipoMuestra, analisis: JSON.stringify(analisis) };
    if (action === 'update') params.codigo = codigo;

    const btn = document.querySelector('.btn-primary');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    fetch('crud_paquete_analisis.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = orig;
        btn.disabled = false;
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(err => {
        btn.innerHTML = orig;
        btn.disabled = false;
        alert('Error de red: ' + err.message);
    });
}

// === EVENT LISTENER para el formulario (CORRECCIÓN CLAVE) ===
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('paqueteMuestraForm');
    if (form) {
        form.addEventListener('submit', savePaqueteMuestra);
    }

    const buscador = document.getElementById('buscadorAnalisis');
    if (buscador) {
        buscador.addEventListener('input', function() {
            toggleBotonLimpiar();
            aplicarFiltroBusqueda();
        });
    }   
    // Cambio de tipo de muestra
    const selectTipo = document.getElementById('paqueteMuestraModalTipoMuestra');
    if (selectTipo) {
        selectTipo.addEventListener('change', function() {
            const action = document.getElementById('paqueteMuestraModalAction').value;
            if (action === 'create') {
                document.getElementById('buscadorAnalisis').value = '';
                if (this.value) {
                    renderizarCheckboxes();
                } else {
                    document.getElementById('analisisCheckboxes').innerHTML = '<p class="text-gray-500 italic col-span-full">Seleccione un tipo de muestra primero</p>';
                }
            }
        });
    }
});

// === Cerrar modal al hacer clic fuera ===
window.onclick = function(e) {
    const modal = document.getElementById('paqueteMuestraModal');
    if (modal && e.target === modal) closePaqueteMuestraModal();
};

function limpiarBuscador() {
    document.getElementById('buscadorAnalisis').value = '';
    document.getElementById('iconoLimpiar').style.display = 'none';
    renderizarCheckboxes(''); 
}

function toggleBotonLimpiar() {
    const input = document.getElementById('buscadorAnalisis');
    const icono = document.getElementById('iconoLimpiar');
    icono.style.display = input.value.trim() ? 'flex' : 'none';
}
window.exportarPaquetesMuestra = function() {
    console.log('Iniciando exportación de paquetes de muestra...');

    // 1. Obtener la tabla
    const table = document.getElementById('tablaPaquetes');
    if (!table) {
        alert('⚠️ No se encontró la tabla de paquetes.');
        return;
    }

    // 2. Obtener todas las filas del cuerpo
    const rows = table.querySelectorAll('tbody tr');
    if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('td')?.textContent?.includes('No hay paquetes'))) {
        alert('⚠️ No hay datos para exportar.');
        return;
    }

    // 3. Crear CSV con BOM para UTF-8
    let csv = '\uFEFF';
    
    // Encabezado del documento
    csv += 'SISTEMA DE SANIDAD GRS,,\n';
    csv += 'LISTADO DE PAQUETES DE MUESTRA,,\n';
    csv += 'Fecha de Exportación:,' + new Date().toLocaleDateString('es-PE') + ',\n';
    csv += ',,\n';
    
    // Encabezados de columnas
    csv += 'Código Paquete,Nombre del Paquete,Código Tipo Muestra,Nombre Tipo Muestra,N° Análisis\n';

    let count = 0;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length < 4) return;

        // Celda 0: Código (ej: <span>COD01</span>)
        const codigoSpan = cells[0].querySelector('span');
        const codigo = codigoSpan ? codigoSpan.textContent.trim() : cells[0].textContent.trim();

        // Celda 1: Nombre del paquete
        const nombrePaquete = cells[1].textContent.trim();

        // Celda 2: Tipo de muestra (badge con código y nombre)
        let codigoTipo = '';
        let nombreTipo = '';
        const badge = cells[2].querySelector('.tipo-muestra-badge');
        if (badge) {
            const codigoLinea = badge.querySelector('.tipo-codigo')?.textContent || '';
            const nombreLinea = badge.querySelector('.tipo-nombre')?.textContent || '';
            codigoTipo = codigoLinea.replace('Código:', '').trim();
            nombreTipo = nombreLinea.replace('Nombre:', '').trim();
        }

        // Celda 3: Número de análisis (si la columna existe)
        const numAnalisis = cells.length > 3 ? cells[3].textContent.trim() : '0';

        // Validar que sea un registro válido
        if (nombrePaquete && nombrePaquete !== 'No hay paquetes de muestra registrados') {
            csv += `"${codigo}","${nombrePaquete}","${codigoTipo}","${nombreTipo}",${numAnalisis}\n`;
            count++;
        }
    });

    // Pie de página
    csv += ',,\n';
    csv += `Total de Paquetes:,${count},\n`;

    if (count === 0) {
        alert('⚠️ No hay datos válidos para exportar.');
        return;
    }

    // Descargar archivo
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    const fecha = new Date().toISOString().split('T')[0];
    
    link.href = url;
    link.download = `Paquetes_Muestra_${fecha}.csv`;
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    alert(`✅ Se exportaron ${count} paquete(s) correctamente.`);
}