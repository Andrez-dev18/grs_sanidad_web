function openAnalisisModal(action, codigo = null, nombre = '', paquete = null) {
    const modal = document.getElementById('analisisModal');
    const title = document.getElementById('analisisModalTitle');
    if (action === 'create') {
        title.textContent = '➕ Nuevo Análisis';
        document.getElementById('analisisModalAction').value = 'create';
        document.getElementById('analisisEditCodigo').value = '';
        document.getElementById('analisisModalNombre').value = '';
        document.getElementById('analisisModalPaquete').value = '';
    } else if (action === 'edit') {
        title.textContent = '✏️ Editar Análisis';
        document.getElementById('analisisModalAction').value = 'update';
        document.getElementById('analisisEditCodigo').value = codigo;
        document.getElementById('analisisModalNombre').value = nombre;
        document.getElementById('analisisModalPaquete').value = paquete || '';
    }
    modal.style.display = 'flex';
}

function closeAnalisisModal() {
    document.getElementById('analisisModal').style.display = 'none';
}

function saveAnalisis(event) {
    event.preventDefault();
    const nombre = document.getElementById('analisisModalNombre').value.trim();
    const paquete = document.getElementById('analisisModalPaquete').value || null;
    const action = document.getElementById('analisisModalAction').value;
    const codigo = document.getElementById('analisisEditCodigo').value;

    if (!nombre) {
        alert('⚠️ El nombre es obligatorio.');
        return false;
    }

    const params = new URLSearchParams();
    params.append('action', action);
    params.append('nombre', nombre);
    if (paquete !== null && paquete !== '') params.append('paquete', paquete);
    if (action === 'update') params.append('codigo', codigo);

    fetch('crud_analisis.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(r => r.json())
    .then(d => {
        alert(d.message);
        if (d.success) location.reload();
    })
    .catch(e => {
        console.error(e);
        alert('Error al guardar.');
    });
}

function confirmAnalisisDelete(codigo) {
    if (confirm('¿Está seguro? Esta acción no se puede deshacer.')) {
        fetch('crud_analisis.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'delete', codigo })
        })
        .then(r => r.json())
        .then(d => {
            alert(d.message);
            if (d.success) location.reload();
        })
        .catch(e => {
            console.error(e);
            alert('Error al eliminar.');
        });
    }
}

function exportarAnalisis() {
    const rows = document.querySelectorAll('#analisisTableBody tr');
    if (rows.length === 0 || rows[0].querySelector('td').textContent.includes('No hay')) {
        alert('⚠️ No hay datos para exportar.');
        return;
    }

    let csv = '\uFEFF';
    csv += 'SISTEMA GRS - ANÁLISIS\n';
    csv += 'Fecha:,' + new Date().toLocaleDateString('es-PE') + '\n\n';
    csv += 'Código,Nombre,Paquete Código,Paquete Nombre,Tipo Muestra Código,Tipo Muestra Nombre\n';

    let count = 0;
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length < 4) return;

        const cod = cells[0].textContent.replace(/[^\d]/g, '');
        const nom = cells[1].textContent.trim();

        // Extraer datos del badge
        const badge = cells[2].querySelector('.paquete-line');
        let paqCod = '', paqNom = '', tmCod = '', tmNom = '';
        if (badge) {
            const lines = cells[2].querySelectorAll('.paquete-line');
            if (lines[0]) {
                const pText = lines[0].textContent;
                const pMatch = pText.match(/Paquete:\s*(\d+)\s*-\s*(.+)/);
                if (pMatch) { paqCod = pMatch[1]; paqNom = pMatch[2].trim(); }
            }
            if (lines[1]) {
                const tText = lines[1].textContent;
                const tMatch = tText.match(/Muestra:\s*(\d+)\s*-\s*(.+)/);
                if (tMatch) { tmCod = tMatch[1]; tmNom = tMatch[2].trim(); }
            }
        }

        if (nom) {
            csv += `"${cod}","${nom}","${paqCod}","${paqNom}","${tmCod}","${tmNom}"\n`;
            count++;
        }
    });

    if (count === 0) {
        alert('⚠️ No hay datos válidos.');
        return;
    }

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `Analisis_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
    alert(`✅ ${count} análisis exportados.`);
}