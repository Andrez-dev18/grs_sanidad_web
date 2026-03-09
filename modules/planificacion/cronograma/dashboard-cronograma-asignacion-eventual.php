<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; } else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación Eventual - Cronograma</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <script src="../../../assets/js/fetch-auth-redirect.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f8f9fa; font-family: system-ui, sans-serif; }
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none; padding: 0.625rem 1.5rem; font-size: 0.875rem; font-weight: 600;
            color: white; border-radius: 0.75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-primary:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); transform: translateY(-2px); }
        .form-control { width: 100%; padding: 0.625rem 1rem; border: 1px solid #d1d5db; border-radius: 0.75rem; font-size: 0.875rem; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full max-w-full py-4 px-4 sm:px-6 lg:px-8 box-border">
        <div class="mb-6">
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de programa *</label>
                        <select id="tipoPrograma" class="form-control">
                            <option value="">Seleccione tipo...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código del programa *</label>
                        <select id="codigoPrograma" class="form-control" disabled>
                            <option value="">Primero seleccione el tipo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del programa</label>
                        <input type="text" id="nombrePrograma" class="form-control bg-gray-100" readonly placeholder="—">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input type="date" id="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Granja *</label>
                        <select id="granja" class="form-control">
                            <option value="">Seleccione granja...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Campaña *</label>
                        <select id="campania" class="form-control" disabled>
                            <option value="">Primero seleccione granja</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Galpón *</label>
                        <select id="galpon" class="form-control" disabled>
                            <option value="">Primero seleccione granja</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Edad *</label>
                        <input type="number" id="edad" class="form-control" min="0" max="999" value="1" placeholder="1">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea id="observaciones" class="form-control" rows="3" placeholder="Indique por qué se registra esta asignación eventual"></textarea>
                    </div>
                </div>

                <div class="pt-4 flex flex-wrap gap-4">
                    <button type="button" id="btnGuardar" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="btnLimpiar" class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200 text-sm font-medium">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var programasPorTipo = {};
        var granjasData = [];
        var galponesData = [];
        var campaniasData = [];

        function cargarTipos() {
            fetch('../programas/get_tipos_programa.php').then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) return;
                var sel = document.getElementById('tipoPrograma');
                sel.innerHTML = '<option value="">Seleccione tipo...</option>';
                (res.data || []).forEach(function(t) {
                    var opt = document.createElement('option');
                    opt.value = t.codigo;
                    opt.textContent = (t.nombre || t.codigo);
                    opt.dataset.sigla = t.sigla || '';
                    sel.appendChild(opt);
                });
            }).catch(function() {});
        }

        function cargarProgramas(codTipo) {
            var sel = document.getElementById('codigoPrograma');
            sel.innerHTML = '<option value="">Seleccione programa...</option>';
            sel.disabled = true;
            if (!codTipo) return;
            fetch('get_programas.php?codTipo=' + encodeURIComponent(codTipo)).then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) return;
                programasPorTipo[codTipo] = res.data || [];
                programasPorTipo[codTipo].forEach(function(p) {
                    var opt = document.createElement('option');
                    opt.value = p.codigo;
                    opt.textContent = p.label || (p.codigo + ' - ' + p.nombre);
                    opt.dataset.nombre = p.nombre || '';
                    sel.appendChild(opt);
                });
                sel.disabled = false;
            }).catch(function() { sel.disabled = false; });
        }

        function cargarGranjas() {
            fetch('get_granjas.php').then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) return;
                granjasData = res.data || [];
                var sel = document.getElementById('granja');
                sel.innerHTML = '<option value="">Seleccione granja...</option>';
                granjasData.forEach(function(g) {
                    var opt = document.createElement('option');
                    opt.value = g.codigo;
                    opt.textContent = (g.codigo + ' - ' + (g.nombre || g.codigo));
                    opt.dataset.nombre = g.nombre || g.codigo;
                    sel.appendChild(opt);
                });
            }).catch(function() {});
        }

        function cargarCampaniasPorGranja(codigoGranja) {
            var sel = document.getElementById('campania');
            sel.innerHTML = '<option value="">Seleccione campaña...</option>';
            sel.disabled = true;
            if (!codigoGranja || codigoGranja.length < 3) return;
            var anio = document.getElementById('fecha').value ? document.getElementById('fecha').value.substring(0, 4) : new Date().getFullYear();
            fetch('get_campanias_por_granjas.php?granjas=' + encodeURIComponent(codigoGranja) + '&anio=' + anio).then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) return;
                var campanias = [];
                (res.data || []).forEach(function(item) {
                    if (item.campanias) campanias = campanias.concat(item.campanias);
                });
                campanias = campanias.filter(function(c, i, a) { return a.indexOf(c) === i; }).sort();
                campaniasData = campanias.map(function(c) { return { campania: c }; });
                campaniasData.forEach(function(c) {
                    var opt = document.createElement('option');
                    opt.value = c.campania;
                    opt.textContent = c.campania;
                    sel.appendChild(opt);
                });
                sel.disabled = false;
            }).catch(function() { sel.disabled = false; });
        }

        function cargarGalpones(codigoGranja) {
            var sel = document.getElementById('galpon');
            sel.innerHTML = '<option value="">Seleccione galpón...</option>';
            sel.disabled = true;
            if (!codigoGranja || codigoGranja.length < 3) return;
            fetch('get_galpones.php?codigo=' + encodeURIComponent(codigoGranja)).then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) return;
                galponesData = res.data || [];
                galponesData.forEach(function(g) {
                    var opt = document.createElement('option');
                    opt.value = g.galpon;
                    opt.textContent = g.galpon;
                    sel.appendChild(opt);
                });
                sel.disabled = false;
            }).catch(function() { sel.disabled = false; });
        }

        document.getElementById('tipoPrograma').addEventListener('change', function() {
            var v = this.value;
            document.getElementById('codigoPrograma').value = '';
            document.getElementById('nombrePrograma').value = '';
            cargarProgramas(v);
        });

        document.getElementById('codigoPrograma').addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            document.getElementById('nombrePrograma').value = opt ? (opt.dataset.nombre || '') : '';
        });

        document.getElementById('granja').addEventListener('change', function() {
            var v = this.value;
            document.getElementById('campania').innerHTML = '<option value="">Seleccione campaña...</option>';
            document.getElementById('campania').disabled = true;
            document.getElementById('galpon').innerHTML = '<option value="">Seleccione galpón...</option>';
            document.getElementById('galpon').disabled = true;
            if (v) {
                cargarCampaniasPorGranja(v);
                cargarGalpones(v);
            }
        });

        document.getElementById('fecha').addEventListener('change', function() {
            var granja = document.getElementById('granja').value;
            if (granja) cargarCampaniasPorGranja(granja);
        });

        document.getElementById('btnGuardar').addEventListener('click', function() {
            var tipoPrograma = document.getElementById('tipoPrograma').value.trim();
            var codPrograma = document.getElementById('codigoPrograma').value.trim();
            var nomPrograma = document.getElementById('nombrePrograma').value.trim();
            var fecha = document.getElementById('fecha').value.trim();
            var granja = document.getElementById('granja').value.trim();
            var galpon = document.getElementById('galpon').value.trim();
            var campania = document.getElementById('campania').value.trim();
            var edad = document.getElementById('edad').value.trim();
            if (!tipoPrograma) {
                Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'Seleccione el tipo de programa.' });
                return;
            }
            if (!codPrograma) {
                Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'Seleccione el código del programa.' });
                return;
            }
            if (!fecha) {
                Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'Indique la fecha.' });
                return;
            }
            if (!granja) {
                Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'Seleccione la granja.' });
                return;
            }
            if (!galpon) {
                Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'Seleccione el galpón.' });
                return;
            }
            if (!campania) {
                Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'Seleccione la campaña.' });
                return;
            }
            var edadNum = parseInt(edad, 10) || 0;
            var nomGranja = '';
            var gOpt = document.getElementById('granja').options[document.getElementById('granja').selectedIndex];
            if (gOpt && gOpt.dataset.nombre) nomGranja = gOpt.dataset.nombre;
            var observaciones = (document.getElementById('observaciones') && document.getElementById('observaciones').value) ? document.getElementById('observaciones').value.trim() : '';

            var payload = {
                codPrograma: codPrograma,
                nomPrograma: nomPrograma || codPrograma,
                granja: granja,
                galpon: galpon,
                campania: campania,
                edad: edadNum,
                nomGranja: nomGranja,
                fecha: fecha,
                observaciones: observaciones
            };

            fetch('guardar_asignacion_eventual.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function(r) { return r.json(); }).then(function(res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Guardado', text: res.message || 'Asignación eventual registrada correctamente.' });
                    document.getElementById('btnLimpiar').click();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo guardar.' });
                }
            }).catch(function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
            });
        });

        document.getElementById('btnLimpiar').addEventListener('click', function() {
            document.getElementById('tipoPrograma').value = '';
            document.getElementById('codigoPrograma').innerHTML = '<option value="">Primero seleccione el tipo</option>';
            document.getElementById('codigoPrograma').disabled = true;
            document.getElementById('nombrePrograma').value = '';
            document.getElementById('fecha').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('granja').value = '';
            document.getElementById('galpon').innerHTML = '<option value="">Primero seleccione granja</option>';
            document.getElementById('galpon').disabled = true;
            document.getElementById('campania').innerHTML = '<option value="">Primero seleccione granja</option>';
            document.getElementById('campania').disabled = true;
            document.getElementById('edad').value = '1';
            if (document.getElementById('observaciones')) document.getElementById('observaciones').value = '';
        });

        cargarTipos();
        cargarGranjas();
    })();
    </script>
</body>
</html>
