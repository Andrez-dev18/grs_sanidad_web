
function openPaqueteAnalisisModal(action, codigo = null, nombre = '', tipoMuestra = '') {
    const modal = document.getElementById('paqueteAnalisisModal');
    const title = document.getElementById('paqueteAnalisisModalTitle');
    const form = document.getElementById('paqueteAnalisisForm');

    if (action === 'create') {
        title.textContent = '➕ Nuevo Paquete de Análisis';
        document.getElementById('paqueteAnalisisModalAction').value = 'create';
        document.getElementById('paqueteAnalisisEditCodigo').value = '';
        document.getElementById('paqueteAnalisisModalNombre').value = '';
        document.getElementById('paqueteAnalisisModalTipoMuestra').value = '';
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Paquete de Análisis';
        document.getElementById('paqueteAnalisisModalAction').value = 'update';
        document.getElementById('paqueteAnalisisEditCodigo').value = codigo;
        document.getElementById('paqueteAnalisisModalNombre').value = nombre;
        document.getElementById('paqueteAnalisisModalTipoMuestra').value = tipoMuestra;
    }

    modal.style.display = 'flex';
}

function closePaqueteAnalisisModal() {
    document.getElementById('paqueteAnalisisModal').style.display = 'none';
}

function savePaqueteAnalisis(event) {
    event.preventDefault();
    const action = document.getElementById('paqueteAnalisisModalAction').value;
    const nombre = document.getElementById('paqueteAnalisisModalNombre').value.trim();
    const tipoMuestra = document.getElementById('paqueteAnalisisModalTipoMuestra').value;
    const codigo = document.getElementById('paqueteAnalisisEditCodigo').value;

    if (!nombre) {
        alert('⚠️ El nombre es obligatorio.');
        return false;
    }

    if (!tipoMuestra) {
        alert('⚠️ Debe seleccionar un tipo de muestra.');
        return false;
    }

    const params = { action, nombre, tipoMuestra };
    if (action === 'update') params.codigo = codigo;

    fetch('crud_paquete_analisis.php', {
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

function confirmPaqueteAnalisisDelete(codigo) {
    if (confirm('¿Está seguro de eliminar este paquete de análisis? Esta acción no se puede deshacer.')) {
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
function exportarPaquetesAnalisis() {
    console.log('Iniciando exportación de paquetes de análisis...');
    
    // Obtener los datos de la tabla
    const tbody = document.getElementById('paqueteAnalisisTableBody');
    
    if (!tbody) {
        alert('⚠️ No se encontró la tabla de paquetes de análisis.');
        console.error('No se encontró el elemento paqueteAnalisisTableBody');
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
            if (nombre && nombre !== 'No hay paquetes de análisis registrados') {
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
    csv += 'LISTADO DE PAQUETES DE ANÁLISIS,,\n';
    csv += 'Fecha de Exportación:,' + new Date().toLocaleDateString('es-PE') + ',\n';
    csv += ',,\n';
    
    // Encabezados de columnas
    csv += 'Código,Nombre del Paquete,Tipo de Muestra\n';

    let count = 0;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 3) {
            const codigo = cells[0].textContent.trim();
            const nombre = cells[1].textContent.trim();
            const tipoMuestra = cells[2].textContent.trim();
            
            // Evitar la fila de "No hay paquetes registrados"
            if (nombre && nombre !== 'No hay paquetes de análisis registrados') {
                csv += `${codigo},"${nombre}","${tipoMuestra}"\n`;
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
    link.setAttribute('download', `Paquetes_Analisis_${fecha}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('Exportación completada');
    alert(`✅ Se exportaron ${count} paquete(s) de análisis correctamente.`);
}
