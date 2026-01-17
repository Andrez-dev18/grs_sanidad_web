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
    <title>Dashboard - Configuración de Correo</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- TABS -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="flex gap-2 border-b border-gray-200">
                <button id="tabCorreo" onclick="showTab('correo')"
                    class="px-6 py-3 font-medium text-sm rounded-t-lg transition-colors">
                    🛠️ Configuración de Correo
                </button>
                <button id="tabContactos" onclick="showTab('contactos')"
                    class="px-6 py-3 font-medium text-sm rounded-t-lg transition-colors">
                    👥 Mis Contactos
                </button>
            </div>
        </div>

        <!-- CONTENEDOR DE VISTAS -->
        <div class="max-w-7xl mx-auto">

            <!-- VISTA: CONFIGURACIÓN DE CORREO -->
            <div id="viewCorreo" class="space-y-6">
                <div class="bg-white border border-gray-300 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Cuenta de correo para envíos</h2>
                    <p class="text-sm text-gray-600 mb-6">
                        Ingresa tu correo y una <strong>contraseña de aplicación</strong>
                    </p>

                    <form id="formCorreo" onsubmit="guardarConfiguracionCorreo(event)">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Correo electrónico *</label>
                            <input type="email" id="correoEmail" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña de aplicación
                                *</label>
                            <input type="password" id="correoPass" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit"
                                class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2 text-sm">
                                💾 Guardar Mis Datos
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- VISTA: CONTACTOS -->
            <div id="viewContactos" class="space-y-6 hidden">
                <!-- Acciones -->
                <div class="flex justify-between items-center flex-wrap gap-3">
                    <h2 class="text-xl font-semibold text-gray-800">Mis contactos</h2>
                    <button type="button" onclick="openContactModal('create')"
                        class="px-6 py-2.5 bg-gradient-to-r from-green-500 to-green-700 text-white font-medium rounded-lg">
                        ➕ Nuevo contacto
                    </button>
                </div>

                <!-- Tabla de contactos -->
                <div class="bg-white border border-gray-300 rounded-2xl overflow-x-auto shadow-sm">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Nombre</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Correo</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="contactosTableBody" class="divide-y divide-gray-200">
                            <!-- Se llenará con JS -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-3 z-10">
            <p class="text-gray-500 text-sm text-center">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> -
                © <span id="currentYear"></span>
            </p>
        </footer>

        <script>
            // Actualizar el año dinámicamente
            document.getElementById('currentYear').textContent = new Date().getFullYear();
        </script>

    </div>

    <!-- Modal: Contactos -->
    <div id="contactoModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden p-4">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-md">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 id="contactoModalTitle" class="text-xl font-bold text-gray-800">➕ Nuevo contacto</h2>
                <button onclick="closeContactModal()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
            </div>
            <div class="p-6">
                <form id="contactoForm" onsubmit="guardarContacto(event)">
                    <input type="hidden" id="contactoAction" value="create">
                    <input type="hidden" id="contactoId" value="">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre completo *</label>
                        <input type="text" id="contactoNombre" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Correo electrónico *</label>
                        <input type="email" id="contactoEmail" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button type="button" onclick="closeContactModal()"
                            class="px-4 py-2 bg-gray-200 rounded-lg">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">💾 Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // --- Navegación por tabs ---
        function showTab(tab) {
            document.getElementById('viewCorreo').classList.toggle('hidden', tab !== 'correo');
            document.getElementById('viewContactos').classList.toggle('hidden', tab !== 'contactos');
            document.getElementById('tabCorreo').classList.toggle('bg-white', tab === 'correo');
            document.getElementById('tabCorreo').classList.toggle('text-gray-800', tab === 'correo');
            document.getElementById('tabCorreo').classList.toggle('text-gray-500', tab !== 'correo');

            document.getElementById('tabContactos').classList.toggle('bg-white', tab === 'contactos');
            document.getElementById('tabContactos').classList.toggle('text-gray-800', tab === 'contactos');
            document.getElementById('tabContactos').classList.toggle('text-gray-500', tab !== 'contactos');

            if (tab === 'contactos') cargarContactos();
        }

        // --- Configuración de Correo ---
        function guardarConfiguracionCorreo(e) {
            e.preventDefault();
            const email = document.getElementById('correoEmail').value;
            const pass = document.getElementById('correoPass').value;

            fetch('correo_config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `correo=${encodeURIComponent(email)}&password=${encodeURIComponent(pass)}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Configuración guardada correctamente');
                    } else {
                        alert('❌ Error: ' + (data.message || 'No se pudo guardar'));
                    }
                });
        }

        // --- Contactos ---
        function cargarContactos() {
            fetch('contactos_crud.php?action=list')
                .then(r => r.json())
                .then(data => {
                    const tbody = document.getElementById('contactosTableBody');
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">No hay contactos registrados</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.map(c => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium">${c.contacto}</td>
                            <td class="px-6 py-4">${c.correo}</td>
                            <td class="px-6 py-4 flex gap-2">
                                <button
                                    title="Editar"
                                    onclick="openContactModal('edit', ${c.id}, '${c.contacto.replace(/'/g, "\\'")}', '${c.correo}')"
                                    class="text-blue-600 hover:text-blue-800 transition">
                                    <i class="fa-solid fa-edit"></i>
                                </button>
                                <button
                                    title="Eliminar"
                                    onclick="eliminarContacto(${c.id})"
                                    class="text-red-600 hover:text-red-800 transition">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                });
        }

        function openContactModal(mode, id, nombre, email) {
            document.getElementById('contactoAction').value = mode;
            if (mode === 'edit') {
                document.getElementById('contactoId').value = id;
                document.getElementById('contactoNombre').value = nombre;
                document.getElementById('contactoEmail').value = email;
                document.getElementById('contactoModalTitle').textContent = 'Editar contacto';
            } else {
                document.getElementById('contactoId').value = '';
                document.getElementById('contactoNombre').value = '';
                document.getElementById('contactoEmail').value = '';
                document.getElementById('contactoModalTitle').textContent = '➕ Nuevo contacto';
            }
            document.getElementById('contactoModal').classList.remove('hidden');
        }

        function closeContactModal() {
            document.getElementById('contactoModal').classList.add('hidden');
        }

        function guardarContacto(e) {
            e.preventDefault();
            const action = document.getElementById('contactoAction').value;
            const id = document.getElementById('contactoId').value;
            const nombre = document.getElementById('contactoNombre').value;
            const email = document.getElementById('contactoEmail').value;

            fetch('contactos_crud.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=${action}&id=${id}&contacto=${encodeURIComponent(nombre)}&correo=${encodeURIComponent(email)}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closeContactModal();
                        cargarContactos();
                    } else {
                        alert('Error al guardar el contacto');
                    }
                });
        }

        function eliminarContacto(id) {
            if (!confirm('¿Estás seguro de eliminar este contacto?')) return;
            fetch('contactos_crud.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=delete&id=${id}`
            }).then(() => cargarContactos());
        }

        // Cargar configuración al inicio
        document.addEventListener('DOMContentLoaded', () => {
            fetch('correo_config.php?action=get')
                .then(r => r.json())
                .then(data => {
                    if (data.correo) {
                        document.getElementById('correoEmail').value = data.correo;
                        document.getElementById('correoPass').value = data.password;
                    }
                });
        });
    </script>
</body>

</html>