// Funciones para Paquetes de Muestra

function openPaqueteMuestraModal(action, codigo = null, nombre = '', tipoMuestra = '') {
    const modal = document.getElementById('paqueteMuestraModal');
    const title = document.getElementById('paqueteMuestraModalTitle');
    const form = document.getElementById('paqueteMuestraForm');

    if (action === 'create') {
        title.textContent = '➕ Nuevo Paquete de Muestra';
        document.getElementById('paqueteMuestraModalAction').value = 'create';
        document.getElementById('paqueteMuestraEditCodigo').value = '';
        document.getElementById('paqueteMuestraModalNombre').value = '';
        document.getElementById('paqueteMuestraModalTipoMuestra').value = '';
        
        
        
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Paquete de Muestra';
        document.getElementById('paqueteMuestraModalAction').value = 'update';
        document.getElementById('paqueteMuestraEditCodigo').value = codigo;
        document.getElementById('paqueteMuestraModalNombre').value = nombre;
        document.getElementById('paqueteMuestraModalTipoMuestra').value = tipoMuestra;
        
        // Mostrar visualización de selección si hay tipo seleccionado
        if (tipoMuestra) {
            const select = document.getElementById('paqueteMuestraModalTipoMuestra');
            const selectedOption = select.options[select.selectedIndex];
            mostrarSeleccionTipoMuestra(select);
        }
    }

    modal.style.display = 'flex';
}

function closePaqueteMuestraModal() {
    document.getElementById('paqueteMuestraModal').style.display = 'none';
}

// Función para mostrar la selección actual del tipo de muestra
function mostrarSeleccionTipoMuestra(select) {
    /*const seleccionDiv = document.getElementById('tipoMuestraSeleccionado');
    //const codigoSpan = document.getElementById('codigoSeleccionado');
    //const nombreSpan = document.getElementById('nombreSeleccionado');
    
    if (select.value) {
        const selectedOption = select.options[select.selectedIndex];
        const texto = selectedOption.text;
        
        // Extraer código y nombre del texto "Código - Nombre"
        const partes = texto.split(' - ');
        if (partes.length === 2) {
           // codigoSpan.textContent = partes[0].trim();
           // nombreSpan.textContent = partes[1].trim();
            seleccionDiv.classList.remove('hidden');
        } else {
            seleccionDiv.classList.add('hidden');
        }
    } else {
        seleccionDiv.classList.add('hidden');
    }*/
}

function savePaqueteMuestra(event) {
    event.preventDefault();
    const action = document.getElementById('paqueteMuestraModalAction').value;
    const nombre = document.getElementById('paqueteMuestraModalNombre').value.trim();
    const tipoMuestra = document.getElementById('paqueteMuestraModalTipoMuestra').value;
    const codigo = document.getElementById('paqueteMuestraEditCodigo').value;

    if (!nombre) {
        alert('⚠️ El nombre es obligatorio.');
        document.getElementById('paqueteMuestraModalNombre').focus();
        return false;
    }

    if (!tipoMuestra) {
        alert('⚠️ Debe seleccionar un tipo de muestra.');
        document.getElementById('paqueteMuestraModalTipoMuestra').focus();
        return false;
    }

    // Obtener nombre del tipo seleccionado para mostrar en mensaje
    const select = document.getElementById('paqueteMuestraModalTipoMuestra');
    const selectedOption = select.options[select.selectedIndex];
    const tipoNombre = selectedOption ? selectedOption.text.split(' - ')[1] : '';

    const params = { 
        action: action, 
        nombre: nombre, 
        tipoMuestra: tipoMuestra 
    };
    
    if (action === 'update') {
        params.codigo = codigo;
    }

    // Mostrar indicador de carga
    const submitBtn = document.querySelector('#paqueteMuestraForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';
    submitBtn.disabled = true;

    fetch('crud_paquete_analisis.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
    })
    .then(res => res.json())
    .then(data => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            let mensaje = '✅ ' + data.message;
            if (tipoNombre) {
                mensaje += `\n\nTipo de muestra: ${tipoMuestra} - ${tipoNombre}`;
            }
            alert(mensaje);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        alert('Error al guardar el paquete.');
    });

    return false;
}

function confirmPaqueteMuestraDelete(codigo) {
    // Obtener información del paquete para mostrar en confirmación
    const row = event.target.closest('tr');
    const nombrePaquete = row.querySelector('td:nth-child(2)').textContent.trim();
    const tipoMuestraDiv = row.querySelector('.tipo-muestra-badge');
    
    let mensaje = `¿Está seguro de eliminar este paquete?\n\n`;
    mensaje += `Paquete: ${nombrePaquete}\n`;
    mensaje += `Código: ${codigo}\n`;
    
    if (tipoMuestraDiv) {
        const codigoTipo = tipoMuestraDiv.querySelector('.tipo-codigo').textContent.replace('Código:', '').trim();
        const nombreTipo = tipoMuestraDiv.querySelector('.tipo-nombre').textContent.replace('Nombre:', '').trim();
        mensaje += `Tipo de muestra: ${codigoTipo} - ${nombreTipo}\n`;
    }
    
    mensaje += `\n⚠️ Esta acción no se puede deshacer.`;

    if (confirm(mensaje)) {
        // Mostrar indicador de carga
        const deleteBtn = event.target;
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Eliminando...';
        deleteBtn.disabled = true;

        fetch('crud_paquete_analisis.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'delete',
                codigo: codigo
            })
        })
        .then(res => res.json())
        .then(data => {
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
            
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
            alert('Error al eliminar el paquete.');
        });
    }
}

// Función para exportar a Excel/CSV
function exportarPaquetesMuestra() {
    console.log('Iniciando exportación de paquetes de muestra...');
    
    // Obtener los datos de la tabla
    const tbody = document.getElementById('paqueteMuestraTableBody');
    
    if (!tbody) {
        alert('⚠️ No se encontró la tabla de paquetes de muestra.');
        console.error('No se encontró el elemento paqueteMuestraTableBody');
        return;
    }
    
    const rows = tbody.querySelectorAll('tr');
    console.log('Filas encontradas:', rows.length);
    
    // Verificar si hay datos válidos
    let hasData = false;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 3) {
            const nombre = cells[1].textContent.trim();
            if (nombre && nombre !== 'No hay paquetes de muestra registrados') {
                hasData = true;
            }
        }
    });
    
    if (!hasData) {
        alert('⚠️ No hay datos para exportar.');
        return;
    }

    // Crear el contenido CSV con formato mejorado
    let csv = '\uFEFF'; // BOM para UTF-8
    
    // Encabezado del documento
    csv += 'SISTEMA DE SANIDAD GRS,,\n';
    csv += 'LISTADO DE PAQUETES DE MUESTRA,,\n';
    csv += 'Fecha de Exportación:,' + new Date().toLocaleDateString('es-PE') + ',\n';
    csv += ',,\n';
    
    // Encabezados de columnas
    csv += 'Código Paquete,Nombre del Paquete,Código Tipo Muestra,Nombre Tipo Muestra\n';

    let count = 0;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 3) {
            const codigoPaquete = cells[0].textContent.trim();
            const nombrePaquete = cells[1].textContent.trim();
            const tipoMuestraDiv = cells[2].querySelector('.tipo-muestra-badge');
            
            let codigoTipo = '';
            let nombreTipo = '';
            
            if (tipoMuestraDiv) {
                // Extraer código y nombre del badge con formato visual
                const codigoLinea = tipoMuestraDiv.querySelector('.tipo-codigo').textContent;
                const nombreLinea = tipoMuestraDiv.querySelector('.tipo-nombre').textContent;
                
                codigoTipo = codigoLinea.replace('Código:', '').trim();
                nombreTipo = nombreLinea.replace('Nombre:', '').trim();
            } else {
                // Si no hay badge, mostrar texto simple
                nombreTipo = cells[2].textContent.trim();
            }
            
            // Evitar la fila de "No hay paquetes registrados"
            if (nombrePaquete && nombrePaquete !== 'No hay paquetes de muestra registrados') {
                csv += `${codigoPaquete},"${nombrePaquete}",${codigoTipo},"${nombreTipo}"\n`;
                count++;
            }
        }
    });

    // Pie de página
    csv += ',,\n';
    csv += `Total de Paquetes:,${count},\n`;

    console.log('Registros exportados:', count);

    if (count === 0) {
        alert('⚠️ No hay datos válidos para exportar.');
        return;
    }

    // Crear el archivo y descargarlo
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    const fecha = new Date().toISOString().split('T')[0];
    link.setAttribute('href', url);
    link.setAttribute('download', `Paquetes_Muestra_${fecha}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('Exportación completada');
    alert(`✅ Se exportaron ${count} paquete(s) de muestra correctamente.`);
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('paqueteMuestraModal');
    if (event.target == modal) {
        closePaqueteMuestraModal();
    }
};

// Cerrar modal con ESC
document.addEventListener('keydown', function(event) {
    const modal = document.getElementById('paqueteMuestraModal');
    if (event.key === 'Escape' && modal.style.display === 'flex') {
        closePaqueteMuestraModal();
    }
});

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Agregar efecto focus a los campos del modal
    const modalInputs = document.querySelectorAll('#paqueteMuestraModal input, #paqueteMuestraModal select');
    modalInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('ring-2', 'ring-blue-200');
        });
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('ring-2', 'ring-blue-200');
        });
    });
    
    // Inicializar visualización de selección si hay valor en el select
    const tipoMuestraSelect = document.getElementById('paqueteMuestraModalTipoMuestra');
    if (tipoMuestraSelect && tipoMuestraSelect.value) {
        mostrarSeleccionTipoMuestra(tipoMuestraSelect);
    }
});