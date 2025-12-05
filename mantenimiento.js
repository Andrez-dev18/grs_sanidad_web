// mantenimiento.js - Funciones comunes para mantenimiento

/**
 * Abre el modal para crear/editar
 */
function openModal(action, codigo = null, datos = {}) {
    const modal = document.getElementById('modalOverlay');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('crudForm');
    
    // Guardar acción y código en el formulario
    document.getElementById('modalAction').value = action;
    
    if (codigo) {
        document.getElementById('codigo').value = codigo;
    } else {
        document.getElementById('codigo').value = '';
    }
    
    if (action === 'create') {
        title.textContent = '➕ Nuevo Registro';
        // Limpiar campos
        form.querySelectorAll('input, select, textarea').forEach(field => {
            if (!field.closest('div[style*="display: none"]')) {
                if (field.type !== 'hidden' && field.id !== 'modalAction' && field.id !== 'codigo') {
                    field.value = '';
                }
            }
        });
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Registro';
        // Llenar campos con datos
        Object.keys(datos).forEach(key => {
            const field = document.getElementById(`field_${key}`);
            if (field) {
                field.value = datos[key];
            }
        });
    }
    
    modal.style.display = 'flex';
}

/**
 * Cierra el modal
 */
function closeModal() {
    document.getElementById('modalOverlay').style.display = 'none';
}

/**
 * Guarda un registro (genérico)
 */
function saveRecord(event, tabla, fields) {
    event.preventDefault();
    
    const action = document.getElementById('modalAction').value;
    const codigo = document.getElementById('codigo').value;
    const form = document.getElementById('crudForm');
    
    // Validar campos requeridos
    let valid = true;
    const requiredFields = [];
    
    fields.forEach(field => {
        if (field.required && !document.getElementById(`field_${field.name}`).value.trim()) {
            valid = false;
            document.getElementById(`field_${field.name}`).style.borderColor = '#ef4444';
            requiredFields.push(field.label);
        } else {
            document.getElementById(`field_${field.name}`).style.borderColor = '';
        }
    });
    
    if (!valid) {
        alert(`⚠️ Campos obligatorios faltantes:\n${requiredFields.join('\n')}`);
        return false;
    }
    
    // Preparar datos
    const params = new URLSearchParams();
    params.append('action', action);
    params.append('tabla', tabla);
    
    if (action === 'update') {
        params.append('codigo', codigo);
    }
    
    // Agregar campos del formulario
    fields.forEach(field => {
        const value = document.getElementById(`field_${field.name}`).value;
        params.append(field.name, value);
    });
    
    // Mostrar loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    submitBtn.disabled = true;
    
    // Enviar datos
    fetch('crud_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(res => res.json())
    .then(data => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        alert('Error al guardar.');
    });
    
    return false;
}

/**
 * Confirma eliminación
 */
function confirmDelete(codigo, tabla) {
    if (confirm('¿Está seguro de eliminar este registro? Esta acción no se puede deshacer.')) {
        fetch('crud_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'delete',
                tabla: tabla,
                codigo: codigo
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al eliminar.');
        });
    }
}

/**
 * Exporta datos a CSV
 */
function exportarDatos(tabla, nombreTabla) {
    // Obtener datos de la tabla actual
    const tbody = document.querySelector('tbody');
    if (!tbody || tbody.querySelectorAll('tr').length === 0) {
        alert('⚠️ No hay datos para exportar.');
        return;
    }
    
    let csv = '\uFEFF'; // BOM para UTF-8
    
    // Encabezado del documento
    csv += 'SISTEMA DE SANIDAD GRS,,\n';
    csv += `LISTADO DE ${nombreTabla.toUpperCase()},,\n`;
    csv += 'Fecha de Exportación:,' + new Date().toLocaleDateString('es-PE') + ',\n';
    csv += ',,\n';
    
    // Obtener encabezados de la tabla
    const headers = [];
    document.querySelectorAll('thead th').forEach(th => {
        const text = th.textContent.trim();
        if (text && text !== 'Acciones') {
            headers.push(text);
        }
    });
    csv += headers.join(',') + '\n';
    
    // Obtener datos
    let count = 0;
    document.querySelectorAll('tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = [];
        
        cells.forEach((cell, index) => {
            if (index < headers.length) { // Excluir columna de acciones
                let text = cell.textContent.trim();
                // Si el texto contiene comas, ponerlo entre comillas
                if (text.includes(',')) {
                    text = `"${text}"`;
                }
                rowData.push(text);
            }
        });
        
        if (rowData.length > 0 && !rowData.every(cell => cell === '')) {
            csv += rowData.join(',') + '\n';
            count++;
        }
    });
    
    // Pie de página
    csv += ',,\n';
    csv += `Total de Registros:,${count},\n`;
    
    if (count === 0) {
        alert('⚠️ No hay datos válidos para exportar.');
        return;
    }
    
    // Crear y descargar archivo
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    const fecha = new Date().toISOString().split('T')[0];
    link.setAttribute('href', url);
    link.setAttribute('download', `${nombreTabla.replace(/\s+/g, '_')}_${fecha}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    alert(`✅ Se exportaron ${count} registro(s) correctamente.`);
}

/**
 * Carga datos de un registro para edición
 */
function loadRecordData(codigo, tabla, callback) {
    fetch(`get_record.php?tabla=${tabla}&codigo=${codigo}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                callback(data.data);
            } else {
                alert('Error al cargar datos: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al cargar datos');
        });
}

/**
 * Alternar entre vista de tabla y tarjetas
 */
function toggleView(mode) {
    const tableView = document.getElementById('tableView');
    const cardsView = document.getElementById('cardsView');
    const btnTable = document.getElementById('btnTable');
    const btnCards = document.getElementById('btnCards');
    
    if (mode === 'table') {
        tableView.style.display = 'block';
        cardsView.style.display = 'none';
        btnTable.classList.add('active');
        btnCards.classList.remove('active');
    } else {
        tableView.style.display = 'none';
        cardsView.style.display = 'block';
        btnTable.classList.remove('active');
        btnCards.classList.add('active');
    }
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('modalOverlay');
    if (event.target === modal) {
        closeModal();
    }
};