
function openAnalisisModal(action, codigo = null, nombre = '', tipoMuestra = '', paqueteAnalisis = null) {
    const modal = document.getElementById('analisisModal');
    const title = document.getElementById('analisisModalTitle');
    const form = document.getElementById('analisisForm');

    if (action === 'create') {
        title.textContent = '➕ Nuevo Análisis';
        document.getElementById('analisisModalAction').value = 'create';
        document.getElementById('analisisEditCodigo').value = '';
        document.getElementById('analisisModalNombre').value = '';
        document.getElementById('analisisModalTipoMuestra').value = '';
        document.getElementById('analisisModalPaquete').value = '';
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Análisis';
        document.getElementById('analisisModalAction').value = 'update';
        document.getElementById('analisisEditCodigo').value = codigo;
        document.getElementById('analisisModalNombre').value = nombre;
        document.getElementById('analisisModalTipoMuestra').value = tipoMuestra;
        document.getElementById('analisisModalPaquete').value = paqueteAnalisis || '';
    }

    modal.style.display = 'flex';
}

function closeAnalisisModal() {
    document.getElementById('analisisModal').style.display = 'none';
}

function saveAnalisis(event) {
    event.preventDefault();
    const action = document.getElementById('analisisModalAction').value;
    const nombre = document.getElementById('analisisModalNombre').value.trim();
    const tipoMuestra = document.getElementById('analisisModalTipoMuestra').value;
    const paqueteAnalisis = document.getElementById('analisisModalPaquete').value;
    const codigo = document.getElementById('analisisEditCodigo').value;

    if (!nombre) {
        alert('⚠️ El nombre es obligatorio.');
        return false;
    }

    if (!tipoMuestra) {
        alert('⚠️ Debe seleccionar un tipo de muestra.');
        return false;
    }

    const params = { action, nombre, tipoMuestra };
    if (paqueteAnalisis) params.paqueteAnalisis = paqueteAnalisis;
    if (action === 'update') params.codigo = codigo;

    fetch('crud_analisis.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params)
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
        alert('Error al guardar.');
    });

    return false;
}

function confirmAnalisisDelete(codigo) {
    if (confirm('¿Está seguro de eliminar este análisis? Esta acción no se puede deshacer.')) {
        fetch('crud_analisis.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'delete',
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

// Función para exportar a Excel/CSV
function exportarAnalisis() {
    console.log('Iniciando exportación de análisis...');
    
    // Obtener los datos de la tabla
    const tbody = document.getElementById('analisisTableBody');
    
    if (!tbody) {
        alert('⚠️ No se encontró la tabla de análisis.');
        console.error('No se encontró el elemento analisisTableBody');
        return;
    }
    
    const rows = tbody.querySelectorAll('tr');
    console.log('Filas encontradas:', rows.length);
    
    // Verificar si hay datos válidos
    let hasData = false;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 4) {
            const nombre = cells[1].textContent.trim();
            if (nombre && nombre !== 'No hay análisis registrados') {
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
    csv += 'SISTEMA DE SANIDAD GRS,,,\n';
    csv += 'LISTADO DE ANÁLISIS,,,\n';
    csv += 'Fecha de Exportación:,' + new Date().toLocaleDateString('es-PE') + ',,\n';
    csv += ',,,\n';
    
    // Encabezados de columnas
    csv += 'Código,Nombre del Análisis,Tipo de Muestra,Paquete de Análisis\n';

    let count = 0;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 4) {
            const codigo = cells[0].textContent.trim();
            const nombre = cells[1].textContent.trim();
            const tipoMuestra = cells[2].textContent.trim();
            const paquete = cells[3].textContent.trim();
            
            // Evitar la fila de "No hay análisis registrados"
            if (nombre && nombre !== 'No hay análisis registrados') {
                csv += `${codigo},"${nombre}","${tipoMuestra}","${paquete}"\n`;
                count++;
            }
        }
    });

    // Pie de página
    csv += ',,,\n';
    csv += `Total de Análisis:,${count},,\n`;

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
    link.setAttribute('download', `Analisis_${fecha}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('Exportación completada');
    alert(`✅ Se exportaron ${count} análisis correctamente.`);
}

