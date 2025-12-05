
function openModal(action, codigo = null, nombre = '') {
    const modal = document.getElementById('empTransModal');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('empTransForm');

    if (action === 'create') {
        title.textContent = '➕ Nueva Empresa de Transporte';
        document.getElementById('modalAction').value = 'create';
        document.getElementById('editCodigo').value = '';
        document.getElementById('modalNombre').value = '';
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Empresa de Transporte';
        document.getElementById('modalAction').value = 'update';
        document.getElementById('editCodigo').value = codigo;
        document.getElementById('modalNombre').value = nombre;
    }

    modal.style.display = 'flex';
}

function closeEmpTransModal() {
    document.getElementById('empTransModal').style.display = 'none';
}

function saveEmpTrans(event) {
    event.preventDefault();
    const action = document.getElementById('modalAction').value;
    const nombre = document.getElementById('modalNombre').value.trim();
    const codigo = document.getElementById('editCodigo').value;

    if (!nombre) {
        alert('⚠️ El nombre es obligatorio.');
        return false;
    }

    const params = { action, nombre };
    if (action === 'update') params.codigo = codigo;

    fetch('crud_emp_trans.php', {
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
        alert('Error al guardar la empresa de transporte.');
    });

    return false;
}

function confirmDelete(codigo) {
    if (confirm('¿Está seguro de eliminar esta empresa de transporte? Esta acción no se puede deshacer.')) {
        fetch('crud_emp_trans.php', {
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
            alert('Error al eliminar la empresa.');
        });
    }
}

// Función para exportar a CSV
function exportarEmpresasTransporte() {
    const tbody = document.getElementById('empTransTableBody');
    if (!tbody) {
        alert('⚠️ No se encontró la tabla de empresas.');
        return;
    }

    const rows = tbody.querySelectorAll('tr');
    let hasData = false;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 2) {
            const nombre = cells[1].textContent.trim();
            if (nombre && nombre !== 'No hay empresas de transporte registradas') {
                hasData = true;
            }
        }
    });

    if (!hasData) {
        alert('⚠️ No hay datos para exportar.');
        return;
    }

    let csv = '\uFEFF';
    csv += 'SISTEMA DE SANIDAD GRS,,\n';
    csv += 'LISTADO DE EMPRESAS DE TRANSPORTE,,\n';
    csv += 'Fecha de Exportación:,' + new Date().toLocaleDateString('es-PE') + ',\n';
    csv += ',,\n';
    csv += 'Código,Nombre de la Empresa,\n';

    let count = 0;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 2) {
            const codigo = cells[0].textContent.trim();
            const nombre = cells[1].textContent.trim();
            if (nombre && nombre !== 'No hay empresas de transporte registradas') {
                csv += `${codigo},"${nombre}",\n`;
                count++;
            }
        }
    });

    csv += ',,\n';
    csv += `Total de Empresas:,${count},\n`;

    if (count === 0) {
        alert('⚠️ No hay datos válidos para exportar.');
        return;
    }

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    const fecha = new Date().toISOString().split('T')[0];
    link.setAttribute('href', url);
    link.setAttribute('download', `Empresas_Transporte_${fecha}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    alert(`✅ Se exportaron ${count} empresa(s) correctamente.`);
}
