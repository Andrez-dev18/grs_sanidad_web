
function openMuestraCabeceraModal(action, codigoEnvio = null, fecha = '', hora = '', laboratorio = '', empTrans = '', responsable = '', autorizado = '') {
    const modal = document.getElementById('muestraCabeceraModal');
    const title = document.getElementById('muestraCabeceraModalTitle');
    const form = document.getElementById('muestraCabeceraForm');

    if (action === 'create') {
        title.textContent = '➕ Nuevo Registro de Muestra';
        document.getElementById('muestraCabeceraModalAction').value = 'create';
        document.getElementById('muestraCabeceraEditCodigo').value = '';
        document.getElementById('muestraCabeceraModalCodigo').value = '';
        document.getElementById('muestraCabeceraModalCodigo').readOnly = false;
        document.getElementById('muestraCabeceraModalFecha').value = '';
        document.getElementById('muestraCabeceraModalHora').value = '';
        document.getElementById('muestraCabeceraModalLaboratorio').value = '';
        document.getElementById('muestraCabeceraModalEmpTrans').value = '';
        document.getElementById('muestraCabeceraModalResponsable').value = '';
        document.getElementById('muestraCabeceraModalAutorizado').value = '';
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Registro de Muestra';
        document.getElementById('muestraCabeceraModalAction').value = 'update';
        document.getElementById('muestraCabeceraEditCodigo').value = codigoEnvio;
        document.getElementById('muestraCabeceraModalCodigo').value = codigoEnvio;
        document.getElementById('muestraCabeceraModalCodigo').readOnly = false; // Permitir cambiar código
        document.getElementById('muestraCabeceraModalFecha').value = fecha;
        document.getElementById('muestraCabeceraModalHora').value = hora;
        document.getElementById('muestraCabeceraModalLaboratorio').value = laboratorio;
        document.getElementById('muestraCabeceraModalEmpTrans').value = empTrans;
        document.getElementById('muestraCabeceraModalResponsable').value = responsable;
        document.getElementById('muestraCabeceraModalAutorizado').value = autorizado;
    }

    modal.style.display = 'flex';
}

function closeMuestraCabeceraModal() {
    document.getElementById('muestraCabeceraModal').style.display = 'none';
}

function saveMuestraCabecera(event) {
    event.preventDefault();
    const action = document.getElementById('muestraCabeceraModalAction').value;
    const codigoEnvio = document.getElementById('muestraCabeceraModalCodigo').value.trim();
    const fechaEnvio = document.getElementById('muestraCabeceraModalFecha').value;
    const horaEnvio = document.getElementById('muestraCabeceraModalHora').value;
    const laboratorio = document.getElementById('muestraCabeceraModalLaboratorio').value;
    const empTrans = document.getElementById('muestraCabeceraModalEmpTrans').value;
    const usuarioResponsable = document.getElementById('muestraCabeceraModalResponsable').value.trim();
    const autorizadoPor = document.getElementById('muestraCabeceraModalAutorizado').value.trim();
    const codigoOriginal = document.getElementById('muestraCabeceraEditCodigo').value;

    if (!codigoEnvio || !fechaEnvio || !horaEnvio || !laboratorio || !empTrans || !usuarioResponsable || !autorizadoPor) {
        alert('⚠️ Todos los campos son obligatorios.');
        return false;
    }

    const params = { 
        action, 
        codigoEnvio, 
        fechaEnvio, 
        horaEnvio, 
        laboratorio, 
        empTrans, 
        usuarioResponsable, 
        autorizadoPor 
    };
    if (action === 'update') params.codigoOriginal = codigoOriginal;

    fetch('crud_muestra_cabecera.php', {
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

function confirmMuestraCabeceraDelete(codigoEnvio) {
    if (confirm('¿Está seguro de eliminar este registro de muestra? Esta acción eliminará también todos los detalles asociados y no se puede deshacer.')) {
        fetch('crud_muestra_cabecera.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'delete',
                codigoEnvio: codigoEnvio
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

function viewMuestraDetalle(codigoEnvio) {
    // Redirigir a la página de detalles con el código de envío como parámetro
    window.location.href = 'dashboard-muestra-detalle.php?codigoEnvio=' + encodeURIComponent(codigoEnvio);
}

// Función para exportar a Excel/CSV
function exportarRegistroMuestras() {
    console.log('Iniciando exportación de registro de muestras...');
    
    // Obtener los datos de la tabla
    const tbody = document.getElementById('registroMuestrasTableBody');
    
    if (!tbody) {
        alert('⚠️ No se encontró la tabla de registros.');
        console.error('No se encontró el elemento registroMuestrasTableBody');
        return;
    }
    
    const rows = tbody.querySelectorAll('tr');
    console.log('Filas encontradas:', rows.length);
    
    // Verificar si hay datos válidos
    let hasData = false;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            const codigo = cells[0].textContent.trim();
            if (codigo && codigo !== 'No hay registros de muestras') {
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
    csv += 'SISTEMA DE SANIDAD GRS,,,,,,\n';
    csv += 'REGISTRO DE MUESTRAS,,,,,,\n';
    csv += 'Fecha de Exportación:,' + new Date().toLocaleDateString('es-PE') + ',,,,,\n';
    csv += ',,,,,,\n';
    
    // Encabezados de columnas
    csv += 'Código Envío,Fecha,Hora,Laboratorio,Transporte,Responsable,Autorizado Por\n';

    let count = 0;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            const codigo = cells[0].textContent.trim();
            const fecha = cells[1].textContent.trim();
            const hora = cells[2].textContent.trim();
            const laboratorio = cells[3].textContent.trim();
            const transporte = cells[4].textContent.trim();
            const responsable = cells[5].textContent.trim();
            const autorizado = cells[6].textContent.trim();
            
            // Evitar la fila de "No hay registros"
            if (codigo && codigo !== 'No hay registros de muestras') {
                csv += `"${codigo}",${fecha},${hora},"${laboratorio}","${transporte}","${responsable}","${autorizado}"\n`;
                count++;
            }
        }
    });

    // Pie de página
    csv += ',,,,,,\n';
    csv += `Total de Registros:,${count},,,,,\n`;

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
    link.setAttribute('download', `Registro_Muestras_${fecha}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('Exportación completada');
    alert(`✅ Se exportaron ${count} registro(s) correctamente.`);
}
