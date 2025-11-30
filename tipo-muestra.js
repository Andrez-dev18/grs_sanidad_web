
function openTipoMuestraModal(action, codigo = null, nombre = '', descripcion = '', longitud = 8) {
    const modal = document.getElementById('tipoMuestraModal');
    const title = document.getElementById('tipoMuestraModalTitle');
    const form = document.getElementById('tipoMuestraForm');

    if (action === 'create') {
        title.textContent = '➕ Nuevo Tipo de Muestra';
        document.getElementById('tipoMuestraModalAction').value = 'create';
        document.getElementById('tipoMuestraEditCodigo').value = '';
        document.getElementById('tipoMuestraModalNombre').value = '';
        document.getElementById('tipoMuestraModalDescripcion').value = '';
        document.getElementById('tipoMuestraModalLongitud').value = 8;
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Tipo de Muestra';
        document.getElementById('tipoMuestraModalAction').value = 'update';
        document.getElementById('tipoMuestraEditCodigo').value = codigo;
        document.getElementById('tipoMuestraModalNombre').value = nombre;
        document.getElementById('tipoMuestraModalDescripcion').value = descripcion;
        document.getElementById('tipoMuestraModalLongitud').value = longitud;
    }

    modal.style.display = 'flex';
}

function closeTipoMuestraModal() {
    document.getElementById('tipoMuestraModal').style.display = 'none';
}

function saveTipoMuestra(event) {
    event.preventDefault();
    const action = document.getElementById('tipoMuestraModalAction').value;
    const nombre = document.getElementById('tipoMuestraModalNombre').value.trim();
    const descripcion = document.getElementById('tipoMuestraModalDescripcion').value.trim();
    const longitud = document.getElementById('tipoMuestraModalLongitud').value;
    const codigo = document.getElementById('tipoMuestraEditCodigo').value;

    if (!nombre) {
        alert('⚠️ El nombre es obligatorio.');
        return false;
    }

    if (!longitud || longitud < 1 || longitud > 20) {
        alert('⚠️ La longitud de código debe estar entre 1 y 20.');
        return false;
    }

    const params = { action, nombre, descripcion, longitud_codigo: longitud };
    if (action === 'update') params.codigo = codigo;

    fetch('crud_tipo_muestra.php', {
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

function confirmTipoMuestraDelete(codigo) {
    if (confirm('¿Está seguro de eliminar este tipo de muestra? Esta acción no se puede deshacer y puede afectar a otros registros relacionados.')) {
        fetch('crud_tipo_muestra.php', {
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
function exportarTiposMuestra() {
    console.log('Iniciando exportación de tipos de muestra...');
    
    // Obtener los datos de la tabla
    const tbody = document.getElementById('tipoMuestraTableBody');
    
    if (!tbody) {
        alert('⚠️ No se encontró la tabla de tipos de muestra.');
        console.error('No se encontró el elemento tipoMuestraTableBody');
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
            if (nombre && nombre !== 'No hay tipos de muestra registrados') {
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
    csv += 'LISTADO DE TIPOS DE MUESTRA,,,\n';
    csv += 'Fecha de Exportación:,' + new Date().toLocaleDateString('es-PE') + ',,\n';
    csv += ',,,\n';
    
    // Encabezados de columnas
    csv += 'Código,Nombre,Descripción,Long. Código\n';

    let count = 0;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 4) {
            const codigo = cells[0].textContent.trim();
            const nombre = cells[1].textContent.trim();
            const descripcion = cells[2].textContent.trim();
            const longitud = cells[3].textContent.trim();
            
            // Evitar la fila de "No hay tipos de muestra registrados"
            if (nombre && nombre !== 'No hay tipos de muestra registrados') {
                csv += `${codigo},"${nombre}","${descripcion}",${longitud}\n`;
                count++;
            }
        }
    });

    // Pie de página
    csv += ',,,\n';
    csv += `Total de Tipos de Muestra:,${count},,\n`;

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
    link.setAttribute('download', `Tipos_Muestra_${fecha}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('Exportación completada');
    alert(`✅ Se exportaron ${count} tipo(s) de muestra correctamente.`);
}
