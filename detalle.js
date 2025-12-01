function openMuestraDetalleModal(action, codigoEnvio = null, posicion = null, fechaToma = '', codigoRef = '', numMuestras = '', observaciones = '') {
    const modal = document.getElementById('muestraDetalleModal');
    const title = document.getElementById('muestraDetalleModalTitle');
    const form = document.getElementById('muestraDetalleForm');

    if (action === 'create') {
        title.textContent = '➕ Nuevo Detalle de Muestra';
        document.getElementById('muestraDetalleModalAction').value = 'create';
        document.getElementById('muestraDetalleEditCodigo').value = '';
        document.getElementById('muestraDetalleEditPosicion').value = '';
        document.getElementById('muestraDetalleModalCodigoEnvio').disabled = false;
        document.getElementById('muestraDetalleModalPosicion').readOnly = false;
        document.getElementById('muestraDetalleModalCodigoEnvio').value = '';
        document.getElementById('muestraDetalleModalPosicion').value = '';
        document.getElementById('muestraDetalleModalFechaToma').value = '';
        document.getElementById('muestraDetalleModalCodigoRef').value = '';
        document.getElementById('muestraDetalleModalNumMuestras').value = '';
        document.getElementById('muestraDetalleModalObservaciones').value = '';
        
        // Comentado: Si hay un filtro activo, preseleccionar ese código de envío
        // const filtroActivo = document.getElementById('filtroCodigoEnvio').value;
        // if (filtroActivo) {
        //     document.getElementById('muestraDetalleModalCodigoEnvio').value = filtroActivo;
        // }
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Detalle de Muestra';
        document.getElementById('muestraDetalleModalAction').value = 'update';
        document.getElementById('muestraDetalleEditCodigo').value = codigoEnvio;
        document.getElementById('muestraDetalleEditPosicion').value = posicion;
        document.getElementById('muestraDetalleModalCodigoEnvio').disabled = false;
        document.getElementById('muestraDetalleModalPosicion').readOnly = false;
        document.getElementById('muestraDetalleModalCodigoEnvio').value = codigoEnvio;
        document.getElementById('muestraDetalleModalPosicion').value = posicion;
        document.getElementById('muestraDetalleModalFechaToma').value = fechaToma;
        document.getElementById('muestraDetalleModalCodigoRef').value = codigoRef;
        document.getElementById('muestraDetalleModalNumMuestras').value = numMuestras;
        document.getElementById('muestraDetalleModalObservaciones').value = observaciones;
    }

    modal.style.display = 'flex';
}

function closeMuestraDetalleModal() {
    document.getElementById('muestraDetalleModal').style.display = 'none';
}

function saveMuestraDetalle(event) {
    event.preventDefault();
    const action = document.getElementById('muestraDetalleModalAction').value;
    const codigoEnvio = document.getElementById('muestraDetalleModalCodigoEnvio').value;
    const posicionSolicitud = document.getElementById('muestraDetalleModalPosicion').value;
    const fechaToma = document.getElementById('muestraDetalleModalFechaToma').value;
    const codigoReferencia = document.getElementById('muestraDetalleModalCodigoRef').value.trim();
    const numeroMuestras = document.getElementById('muestraDetalleModalNumMuestras').value;
    const observaciones = document.getElementById('muestraDetalleModalObservaciones').value.trim();
    const codigoOriginal = document.getElementById('muestraDetalleEditCodigo').value;
    const posicionOriginal = document.getElementById('muestraDetalleEditPosicion').value;

    if (!codigoEnvio || !posicionSolicitud || !fechaToma || !codigoReferencia || !numeroMuestras) {
        alert('⚠️ Todos los campos obligatorios deben ser completados.');
        return false;
    }

    const params = { 
        action, 
        codigoEnvio, 
        posicionSolicitud, 
        fechaToma, 
        codigoReferencia, 
        numeroMuestras, 
        observaciones 
    };
    if (action === 'update') {
        params.codigoOriginal = codigoOriginal;
        params.posicionOriginal = posicionOriginal;
    }

    fetch('crud_muestra_detalle.php', {
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

function confirmMuestraDetalleDelete(codigoEnvio, posicion) {
    if (confirm('¿Está seguro de eliminar este detalle de muestra? Esta acción no se puede deshacer.')) {
        fetch('crud_muestra_detalle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'delete',
                codigoEnvio: codigoEnvio,
                posicionSolicitud: posicion
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

// Comentado: Funciones de filtrado
// function filtrarMuestraDetalle() {
//     const filtro = document.getElementById('filtroCodigoEnvio').value;
//     const filas = document.querySelectorAll('#muestraDetalleTableBody tr');
//     
//     filas.forEach(fila => {
//         const codigoEnvio = fila.getAttribute('data-codigo-envio');
//         if (!filtro || codigoEnvio === filtro) {
//             fila.style.display = '';
//         } else {
//             fila.style.display = 'none';
//         }
//     });
// }

// function limpiarFiltro() {
//     document.getElementById('filtroCodigoEnvio').value = '';
//     filtrarMuestraDetalle();
// }

function viewAnalisisDetalle(codigoEnvio, posicion) {
    // Redirigir a la vista de análisis con parámetros
    window.location.href = 'dashboard-analisis-muestra.php?codigoEnvio=' + encodeURIComponent(codigoEnvio) + '&posicion=' + posicion;
}

// Función para exportar a Excel/CSV - Simplificada sin filtros
function exportarDetalleMuestras() {
    console.log('Iniciando exportación de detalle de muestras...');
    
    const tbody = document.getElementById('muestraDetalleTableBody');
    
    if (!tbody) {
        alert('⚠️ No se encontró la tabla de detalles.');
        console.error('No se encontró el elemento muestraDetalleTableBody');
        return;
    }
    
    const rows = tbody.querySelectorAll('tr');
    
    // Crear el contenido CSV con formato mejorado
    let csv = '\uFEFF'; // BOM para UTF-8
    
    // Encabezado del documento
    csv += 'SISTEMA DE SANIDAD GRS,,,,,\n';
    csv += 'DETALLE DE MUESTRAS,,,,,\n';
    csv += 'Fecha de Exportación:,' + new Date().toLocaleDateString('es-PE') + ',,,,\n';
    csv += ',,,,,\n';
    
    // Encabezados de columnas
    csv += 'Código Envío,Posición,Fecha Toma,Código Ref.,N° Muestras,Observaciones\n';

    let count = 0;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 6) {
            const codigo = cells[0].textContent.trim();
            
            // Evitar la fila de "No hay detalles"
            if (codigo && !codigo.includes('No hay detalles')) {
                const posicion = cells[1].textContent.trim();
                const fechaToma = cells[2].textContent.trim();
                const codigoRef = cells[3].textContent.trim();
                const numMuestras = cells[4].textContent.trim();
                const observaciones = cells[5].textContent.trim();
                
                csv += `"${codigo}",${posicion},${fechaToma},"${codigoRef}",${numMuestras},"${observaciones}"\n`;
                count++;
            }
        }
    });

    if (count === 0) {
        alert('⚠️ No hay datos para exportar.');
        return;
    }

    // Pie de página
    csv += ',,,,,\n';
    csv += `Total de Detalles:,${count},,,,\n`;

    console.log('Detalles exportados:', count);

    // Crear el archivo y descargarlo
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    const fecha = new Date().toISOString().split('T')[0];
    const nombreArchivo = `Detalle_Muestras_${fecha}.csv`;
    
    link.setAttribute('href', url);
    link.setAttribute('download', nombreArchivo);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('Exportación completada');
    alert(`✅ Se exportaron ${count} detalle(s) correctamente.`);
}
