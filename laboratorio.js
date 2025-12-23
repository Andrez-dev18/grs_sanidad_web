
function openLaboratorioModal(action, codigo = null, nombre = '') {
    const modal = document.getElementById('laboratorioModal');
    const title = document.getElementById('laboratorioModalTitle');
    const form = document.getElementById('laboratorioForm');

    if (action === 'create') {
        title.textContent = '➕ Nuevo Laboratorio';
        document.getElementById('laboratorioModalAction').value = 'create';
        document.getElementById('laboratorioEditCodigo').value = '';
        document.getElementById('laboratorioModalNombre').value = '';
    } else if (action === 'update') {
        title.textContent = '✏️ Editar Laboratorio';
        document.getElementById('laboratorioModalAction').value = 'update';
        document.getElementById('laboratorioEditCodigo').value = codigo;
        document.getElementById('laboratorioModalNombre').value = nombre;
    }

    modal.style.display = 'flex';
}

function closeLaboratorioModal() {
    document.getElementById('laboratorioModal').style.display = 'none';
}

function saveLaboratorio(event) {
    event.preventDefault();
    const action = document.getElementById('laboratorioModalAction').value;
    const nombre = document.getElementById('laboratorioModalNombre').value.trim();
    const codigo = document.getElementById('laboratorioEditCodigo').value;

    if (!nombre) {
        alert('⚠️ El nombre es obligatorio.');
        return false;
    }

    const params = { action, nombre };
    if (action === 'update') params.codigo = codigo;

    fetch('crud_laboratorio.php', {
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

function confirmLaboratorioDelete(codigo) {
    if (confirm('¿Está seguro de eliminar este laboratorio? Esta acción no se puede deshacer.')) {
        fetch('crud_laboratorio.php', {
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
function exportarLaboratorios() {
    console.log('Iniciando exportación...');
    
    // Obtener los datos de la tabla
    const tbody = document.getElementById('laboratorioTableBody');
    
    if (!tbody) {
        alert('⚠️ No se encontró la tabla de laboratorios.');
        console.error('No se encontró el elemento laboratorioTableBody');
        return;
    }
    
    const rows = tbody.querySelectorAll('tr');
    console.log('Filas encontradas:', rows.length);
    
    // Verificar si hay datos válidos
    let hasData = false;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 2) {
            const nombre = cells[1].textContent.trim();
            if (nombre && nombre !== 'No hay laboratorios registrados') {
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
    csv += 'LISTADO DE LABORATORIOS,,\n';
    csv += 'Fecha de Exportación:,' + new Date().toLocaleDateString('es-PE') + ',\n';
    csv += ',,\n';
    
    // Encabezados de columnas
    csv += 'Código,Nombre del Laboratorio,\n';

    let count = 0;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 2) {
            const codigo = cells[0].textContent.trim();
            const nombre = cells[1].textContent.trim();
            
            // Evitar la fila de "No hay laboratorios registrados"
            if (nombre && nombre !== 'No hay laboratorios registrados') {
                csv += `${codigo},"${nombre}",\n`;
                count++;
            }
        }
    });

    // Pie de página
    csv += ',,\n';
    csv += `Total de Laboratorios:,${count},\n`;

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
    link.setAttribute('download', `Laboratorios_${fecha}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('Exportación completada');
    alert(`✅ Se exportaron ${count} laboratorio(s) correctamente.`);
}
