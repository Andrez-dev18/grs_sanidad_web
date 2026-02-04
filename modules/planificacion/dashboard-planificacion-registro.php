<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) {
            window.top.location.href = "../../login.php";
        } else {
            window.location.href = "../../login.php";
        }
    </script>';
    exit();
}

$usuario = $_SESSION['usuario'] ?? 'usuario';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registro de Planificación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <style>
        body { background: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .card { background: white; border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; }
        .btn-primary { background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; font-weight: 600; }
        .plan-row td { vertical-align: middle; padding: 0.25rem 0.35rem !important; }
        .plan-row input, .plan-row select { font-size: 0.8rem; padding: 0.2rem 0.35rem; }
        .plan-row select { min-width: 100%; }
        .plan-row .form-control, .plan-row .form-select { height: 1.75rem; }
        .plan-row .col-granja { min-width: 200px; }
        .plan-row .col-camp { min-width: 100px; }
        .plan-row .col-galpon { width: 65px; }
        .plan-row .col-edad { min-width: 70px; }
        .plan-row .col-fec { width: 115px; }
        .plan-row .col-cron { min-width: 200px; }
        .plan-row .col-muestra { min-width: 180px; }
        .plan-row .col-lugar { min-width: 150px; }
        .plan-row .col-dest { min-width: 150px; }
        .plan-row .col-resp { min-width: 100px; }
        .plan-row .col-numm { min-width: 120px; }
        .plan-row .col-numh { min-width: 120px; }
        .plan-row .col-obs { min-width: 150px; vertical-align: top !important; }
        .plan-row .obs-textarea { resize: vertical; min-height: 2.5rem; overflow-x: hidden; overflow-y: auto; width: 100%; word-wrap: break-word; }
        .plan-row .col-acc { width: 50px; text-align: center; }
        .btn-add-row { padding: 0.35rem 0.6rem; }
        .card-header-cabecera { background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%); border: 1px solid #bae6fd; }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-body">
                <form id="formPlan">
                    <div class="card-header-cabecera mb-3 px-3 py-2 rounded">
                        <label class="form-label small mb-1 text-muted">Planificación del mes</label>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <select class="form-select form-select-sm" id="mesFiltro" style="width:130px">
                                <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option>
                                <option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option>
                                <option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option>
                                <option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                            </select>
                            <select class="form-select form-select-sm" id="anioFiltro" style="width:90px"></select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-2">
                            <thead class="table-light small">
                                <tr>
                                    <th class="col-granja">Granja</th>
                                    <th class="col-camp">Camp</th>
                                    <th class="col-galpon">Galpón</th>
                                    <th class="col-edad">Edad</th>
                                    <th class="col-fec">Fec.Toma</th>
                                    <th class="col-cron">Cronograma</th>
                                    <th class="col-muestra">Tipo muestra</th>
                                    <th class="col-lugar">Lugar</th>
                                    <th class="col-dest">Destino</th>
                                    <th class="col-resp">Resp.</th>
                                    <th class="col-numm">N° Muestra M.</th>
                                    <th class="col-numh">N° Muestra H.</th>
                                    <th class="col-obs">Obs.</th>
                                    <th class="col-acc"></th>
                                </tr>
                            </thead>
                            <tbody id="planTableBody"></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm btn-add-row" id="btnAddRow">
                            <i class="fas fa-plus me-1"></i> Añadir fila
                        </button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiar">Limpiar</button>
                            <button type="submit" class="btn btn-primary btn-sm" id="btnGuardar">
                                <i class="fas fa-save me-1"></i> Guardar todo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <select id="templateCronograma" class="d-none"></select>
    <select id="templateMuestra" class="d-none"></select>
    <select id="templateLugar" class="d-none">
        <option value="">-</option>
        <option value="Planta de incubación">Planta inc.</option>
        <option value="Galpón">Galpón</option>
        <option value="Otros">Otros</option>
    </select>
    <select id="templateDestino" class="d-none"></select>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const today = new Date();

        let rowIndex = 0;
        let cronogramasHTML = '';
        let muestrasHTML = '';
        let destinosHTML = '';
        let granjasHTML = '';
        const cacheCampanias = {};
        const cacheGalpones = {};

        async function cargarGranjas() {
            const res = await fetch('get_granjas.php');
            const data = await res.json();
            const esc = s => String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            const opt = d => `<option value="${d.codigo}" data-nombre="${esc(d.nombre)}">${d.codigo} - ${esc(d.nombre)}</option>`;
            granjasHTML = '<option value="">-</option>' + data.map(opt).join('');
        }

        async function cargarCampanias(granja) {
            if (!granja) return [];
            if (cacheCampanias[granja]) return cacheCampanias[granja];
            const res = await fetch(`get_campanias.php?granja=${encodeURIComponent(granja)}`);
            const data = await res.json();
            cacheCampanias[granja] = Array.isArray(data) ? data : [];
            return cacheCampanias[granja];
        }

        async function cargarGalpones(granja) {
            if (!granja) return [];
            if (cacheGalpones[granja]) return cacheGalpones[granja];
            const res = await fetch(`get_galpones.php?granja=${encodeURIComponent(granja)}`);
            const data = await res.json();
            cacheGalpones[granja] = Array.isArray(data) ? data : [];
            return cacheGalpones[granja];
        }

        async function cargarCronogramas() {
            const res = await fetch('get_cronogramas.php');
            const data = await res.json();
            if (!Array.isArray(data)) return;
            const opt = d => `<option value="${d.codigo}" data-nombre="${(d.nombre || '').replace(/"/g, '&quot;')}">${d.nombre}</option>`;
            cronogramasHTML = '<option value="">-</option>' + data.map(opt).join('');
            $('#templateCronograma').html(cronogramasHTML);
        }

        async function cargarDestinos() {
            const res = await fetch('get_destinos.php');
            const data = await res.json();
            if (!Array.isArray(data)) return;
            const opt = d => `<option value="${d.codigo}" data-nombre="${(d.nombre || '').replace(/"/g, '&quot;')}">${d.nombre}</option>`;
            destinosHTML = '<option value="">-</option>' + data.map(opt).join('');
            $('#templateDestino').html(destinosHTML);
        }

        async function cargarTiposMuestra() {
            const res = await fetch('../../includes/get_tipos_muestra.php');
            const data = await res.json();
            if (!Array.isArray(data)) return;
            const opt = t => `<option value="${t.codigo}" data-nombre="${(t.nombre || '').replace(/"/g, '&quot;')}">${t.nombre}</option>`;
            muestrasHTML = '<option value="">-</option>' + data.map(opt).join('');
            $('#templateMuestra').html(muestrasHTML);
        }

        function getFechaDefault() {
            const mes = parseInt($('#mesFiltro').val() || today.getMonth() + 1, 10);
            const anio = parseInt($('#anioFiltro').val() || today.getFullYear(), 10);
            const d = new Date(anio, mes - 1, 1);
            return d.toISOString().split('T')[0];
        }

        function createPlanRow() {
            const i = rowIndex++;
            const fecDefault = getFechaDefault();
            const tr = document.createElement('tr');
            tr.id = `planRow_${i}`;
            tr.className = 'plan-row';

            const campOpts = '<option value="">-</option>';
            const galpOpts = '<option value="">-</option>';

            tr.innerHTML = `
                <td class="col-granja"><select class="form-select form-select-sm granja-select" data-row="${i}">${granjasHTML}</select></td>
                <td class="col-camp"><select class="form-select form-select-sm camp-select" data-row="${i}">${campOpts}</select></td>
                <td class="col-galpon"><select class="form-select form-select-sm galpon-select" data-row="${i}">${galpOpts}</select></td>
                <td class="col-edad"><input type="number" class="form-control form-control-sm edad-input" data-row="${i}" min="0" max="99" value=""></td>
                <td class="col-fec"><input type="date" class="form-control form-control-sm fec-input" data-row="${i}" value="${fecDefault}"></td>
                <td class="col-cron"><select class="form-select form-select-sm cron-select" data-row="${i}">${cronogramasHTML}</select></td>
                <td class="col-muestra"><select class="form-select form-select-sm muestra-select" data-row="${i}">${muestrasHTML}</select></td>
                <td class="col-lugar"><select class="form-select form-select-sm lugar-select" data-row="${i}">${$('#templateLugar').html()}</select></td>
                <td class="col-dest"><select class="form-select form-select-sm dest-select" data-row="${i}">${destinosHTML}</select></td>
                <td class="col-resp"><input type="text" class="form-control form-control-sm resp-input" data-row="${i}" placeholder=""></td>
                <td class="col-numm"><input type="number" class="form-control form-control-sm nmacho-input" data-row="${i}" min="0" value="0"></td>
                <td class="col-numh"><input type="number" class="form-control form-control-sm nhembra-input" data-row="${i}" min="0" value="0"></td>
                <td class="col-obs"><textarea class="form-control form-control-sm obs-textarea obs-input" data-row="${i}" placeholder="" rows="2"></textarea></td>
                <td class="col-acc"><button type="button" class="btn btn-outline-danger btn-sm py-0 px-1" data-row="${i}" title="Quitar fila"><i class="fas fa-minus"></i></button></td>
            `;

            return tr;
        }

        async function onGranjaChange(rowIdx) {
            const row = document.getElementById(`planRow_${rowIdx}`);
            if (!row) return;
            const granjaSel = row.querySelector('.granja-select');
            const campSel = row.querySelector('.camp-select');
            const galpSel = row.querySelector('.galpon-select');
            const granja = granjaSel?.value || '';
            campSel.innerHTML = '<option value="">-</option>';
            galpSel.innerHTML = '<option value="">-</option>';
            if (!granja) return;
            const campanias = await cargarCampanias(granja);
            const galpones = await cargarGalpones(granja);
            campanias.forEach(c => { campSel.innerHTML += `<option value="${c}">${c}</option>`; });
            galpones.forEach(g => { galpSel.innerHTML += `<option value="${g}">${g}</option>`; });
        }

        $(document).ready(async function () {
            const now = new Date();
            $('#mesFiltro').val(now.getMonth() + 1);
            const anioActual = now.getFullYear();
            let aniosHTML = '';
            for (let y = anioActual - 2; y <= anioActual + 2; y++) aniosHTML += `<option value="${y}" ${y === anioActual ? 'selected' : ''}>${y}</option>`;
            $('#anioFiltro').html(aniosHTML);

            await cargarGranjas();
            await cargarCronogramas();
            await cargarDestinos();
            await cargarTiposMuestra();

            const tbody = document.getElementById('planTableBody');
            tbody.appendChild(createPlanRow());

            $(document).on('change', '.granja-select', function () {
                onGranjaChange(parseInt(this.dataset.row, 10));
            });

            $(document).on('click', '.btn-outline-danger[data-row]', function () {
                const r = parseInt(this.dataset.row, 10);
                const row = document.getElementById(`planRow_${r}`);
                if (row && document.querySelectorAll('#planTableBody tr').length > 1) row.remove();
            });

            $('#btnAddRow').on('click', function () {
                tbody.appendChild(createPlanRow());
            });

            $('#btnLimpiar').on('click', function () {
                document.getElementById('formPlan').reset();
                $('#mesFiltro').val(now.getMonth() + 1);
                $('#anioFiltro').val(anioActual);
                tbody.innerHTML = '';
                rowIndex = 0;
                tbody.appendChild(createPlanRow());
            });

            $('#formPlan').on('submit', async function (e) {
                e.preventDefault();
                const filas = [];
                document.querySelectorAll('#planTableBody tr').forEach((tr, idx) => {
                    const granja = tr.querySelector('.granja-select')?.value || '';
                    const campania = tr.querySelector('.camp-select')?.value || '';
                    const galpon = tr.querySelector('.galpon-select')?.value || '';
                    const edad = tr.querySelector('.edad-input')?.value || '';
                    const fecToma = tr.querySelector('.fec-input')?.value || '';
                    const codCronograma = parseInt(tr.querySelector('.cron-select')?.value || '0', 10);
                    if (!granja || !campania || !galpon || edad === '' || codCronograma <= 0 || !fecToma) return;

                    const granjaSel = tr.querySelector('.granja-select');
                    const nomGranja = granjaSel?.selectedOptions?.[0]?.dataset?.nombre || '';
                    const cronSel = tr.querySelector('.cron-select');
                    const nomCronograma = cronSel?.selectedOptions?.[0]?.dataset?.nombre || cronSel?.selectedOptions?.[0]?.textContent || '';
                    const codMuestra = tr.querySelector('.muestra-select')?.value || null;
                    const muestraSel = tr.querySelector('.muestra-select');
                    const nomMuestra = codMuestra ? (muestraSel?.selectedOptions?.[0]?.dataset?.nombre || muestraSel?.selectedOptions?.[0]?.textContent?.trim() || '') : '';
                    const lugarToma = tr.querySelector('.lugar-select')?.value || '';
                    const codDestino = tr.querySelector('.dest-select')?.value ? parseInt(tr.querySelector('.dest-select').value, 10) : null;
                    const destSel = tr.querySelector('.dest-select');
                    const nomDestino = codDestino ? (destSel?.selectedOptions?.[0]?.dataset?.nombre || destSel?.selectedOptions?.[0]?.textContent?.trim() || '') : '';
                    const nMacho = parseInt(tr.querySelector('.nmacho-input')?.value || '0', 10);
                    const nHembra = parseInt(tr.querySelector('.nhembra-input')?.value || '0', 10);

                    const obsVal = (tr.querySelector('.obs-input')?.value ?? '').trim();
                    const observacion = (obsVal !== '' && obsVal !== '0') ? obsVal : '';
                    filas.push({
                        granja, campania, galpon, edad, nomGranja,
                        fecToma, codCronograma, nomCronograma, codMuestra, nomMuestra,
                        lugarToma, codDestino, nomDestino,
                        responsable: tr.querySelector('.resp-input')?.value || '',
                        nMacho, nHembra,
                        observacion
                    });
                });

                if (filas.length === 0) {
                    alert('Complete al menos una fila con Granja, Campaña, Galpón, Edad, Cronograma y Fecha de toma.');
                    return;
                }

                const mes = parseInt($('#mesFiltro').val() || '1', 10);
                const anio = parseInt($('#anioFiltro').val() || new Date().getFullYear(), 10);
                const payload = { mes, anio, filas };
                const btn = document.getElementById('btnGuardar');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

                try {
                    const res = await fetch('guardar_plan_bulk.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const out = await res.json();

                    if (out.success) {
                        alert(`Guardadas ${out.guardadas} de ${out.total} planificaciones.`);
                        $('#btnLimpiar').click();
                    } else {
                        alert('Error: ' + (out.message || 'Desconocido') + (out.errores?.length ? '\n' + out.errores.join('\n') : ''));
                    }
                } catch (err) {
                    alert('Error de conexión.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-1"></i> Guardar todo';
                }
            });
        });
    </script>
</body>

</html>
