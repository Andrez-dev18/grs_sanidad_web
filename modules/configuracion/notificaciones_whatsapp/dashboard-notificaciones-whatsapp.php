<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) {
            window.top.location.href = "../../../login.php";
        } else {
            window.location.href = "../../../login.php";
        }
    </script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones WhatsApp</title>
    <link href="../../../css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <style> body { background: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; } </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white border border-gray-300 rounded-2xl p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">
                    <i class="fab fa-whatsapp text-green-600 mr-2"></i> Número para notificaciones WhatsApp
                </h2>
                <p class="text-sm text-gray-600 mb-6">
                    Se usará para enviar recordatorios de eventos del cronograma por WhatsApp.
                </p>

                <form id="formWhatsApp" onsubmit="guardarTelefono(event)">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código de país *</label>
                        <select id="codigoPais" name="codigoPais" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                            <option value="51" selected>Perú (+51)</option>
                            <option value="52">México (+52)</option>
                            <option value="54">Argentina (+54)</option>
                            <option value="56">Chile (+56)</option>
                            <option value="57">Colombia (+57)</option>
                            <option value="58">Venezuela (+58)</option>
                            <option value="591">Bolivia (+591)</option>
                            <option value="593">Ecuador (+593)</option>
                            <option value="595">Paraguay (+595)</option>
                            <option value="598">Uruguay (+598)</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Número *</label>
                        <input type="text" id="telefono" name="telefono" placeholder="Ej: 987654321"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            maxlength="15" inputmode="numeric" pattern="[0-9]*">
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="submit"
                            class="px-6 py-2.5 bg-gradient-to-r from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 text-white font-medium rounded-lg transition">
                            <i class="fas fa-save mr-2"></i> Guardar
                        </button>
                        <button type="button" id="btnEnviarPrueba"
                            class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition inline-flex items-center gap-2 border-0 cursor-pointer">
                            <i class="fab fa-whatsapp"></i> Enviar prueba
                        </button>
                    </div>
                </form>             
            </div>
        </div>
        <footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-3 z-10">
            <p class="text-gray-500 text-sm text-center">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> — © <span id="currentYear"></span>
            </p>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../../assets/js/sweetalert-helpers.js"></script>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();

        function guardarTelefono(e) {
            e.preventDefault();
            var codigoPais = document.getElementById('codigoPais').value;
            var numero = document.getElementById('telefono').value.replace(/\D/g, '');
            if (!numero || numero.length < 8) {
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Ingrese un número válido (al menos 8 dígitos).' });
                else alert('Ingrese un número válido (al menos 8 dígitos).');
                return;
            }
            var telefono = codigoPais + numero;
            var formData = new FormData();
            formData.append('telefono', telefono);

            fetch('whatsapp_config.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Guardado', text: 'Número guardado correctamente.' });
                    else alert('Número guardado correctamente.');
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo guardar' });
                    else alert('Error: ' + (data.message || 'No se pudo guardar'));
                }
            })
            .catch(function() {
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
                else alert('Error de conexión');
            });
        }

        document.getElementById('btnEnviarPrueba').addEventListener('click', function() {
            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Enviando...';
            fetch('enviar_prueba_whatsapp.php')
                .then(function(r) {
                    return r.text().then(function(text) {
                        if (!r.ok) return { success: false, message: text || 'Error ' + r.status };
                        try { return JSON.parse(text); } catch (e) { return { success: false, message: text || 'Respuesta no válida' }; }
                    });
                })
                .then(function(data) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fab fa-whatsapp"></i> Enviar prueba';
                    if (data && data.success) {
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Enviado', text: data.message });
                        else alert(data.message);
                    } else {
                        var msg = (data && data.message) ? data.message : 'No se pudo enviar';
                        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: msg });
                        else alert(msg);
                    }
                })
                .catch(function(err) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fab fa-whatsapp"></i> Enviar prueba';
                    var msg = (err && err.message) ? err.message : 'Error de conexión o red';
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    else alert(msg);
                });
        });

        var codigosPais = ['51', '52', '54', '56', '57', '58', '591', '593', '595', '598'];
        (function cargarConfig() {
            fetch('whatsapp_config.php?action=get')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data || !data.telefono) return;
                    var t = data.telefono.replace(/\D/g, '');
                    var sel = document.getElementById('codigoPais');
                    var inp = document.getElementById('telefono');
                    for (var i = 0; i < codigosPais.length; i++) {
                        var cod = codigosPais[i];
                        if (t.indexOf(cod) === 0 && t.length > cod.length) {
                            sel.value = cod;
                            inp.value = t.substring(cod.length);
                            return;
                        }
                    }
                    inp.value = t;
                })
                .catch(function() {});
        })();
    </script>
</body>
</html>
