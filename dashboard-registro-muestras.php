<?php

session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

//ruta relativa a la conexion
include_once 'conexion_grs_joya\conexion.php';
$conexion = conectar_sanidad();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inicio</title>

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/output.css">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">

    <!-- Estilos para el control de navegacion dinamico -->
    <link rel="stylesheet" href="css/style-NavigationControls.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .icon-box {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
        }

        .logo-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .logo-container img {
            width: 90%;
            height: 90%;
            object-fit: contain;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- VISTA REGISTRO -->
        <div id="viewRegistro" class="content-view active">
            <div class="form-container max-w-7xl mx-auto">
                <form id="sampleForm" onsubmit="return handleSampleSubmit(event)">
                    <!-- INFORMACIÓN DE REGISTRO Y ENVÍO -->
                    <div class="form-section mb-8">
                        <div class="flex items-center gap-3 mb-8">
                            <span class="text-3xl">📋</span>
                            <h2 class="text-2xl font-bold text-gray-800">Información de Registro</h2>
                        </div>

                        <div class="dual-group-container grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <!-- GRUPO 1: Datos de Envío -->
                            
                            <div class="field-group border border-gray-300 rounded-2xl p-8 bg-white">
                                <div class="group-header text-sm font-bold text-blue-600 uppercase tracking-wide pb-4 mb-6">
                                    Datos de Envío
                                </div>

                                <div class="space-y-6">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Fecha <span class="text-red-500">*</span>
                                            </label>
                                            <input type="date" id="fechaEnvio" name="fechaEnvio" required
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </div>

                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Código de Envío <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="codigoEnvio" name="codigoEnvio" readonly
                                                class="w-full px-4 py-2.5 bg-gray-100 border border-gray-300 rounded-lg font-bold text-blue-600 focus:outline-none">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Hora <span class="text-red-500">*</span>
                                            </label>
                                            <input type="time" id="horaEnvio" name="horaEnvio" required
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </div>

                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Laboratorio <span class="text-red-500">*</span>
                                            </label>
                                            <select id="laboratorio" name="laboratorio" required
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer">
                                                <option value="">Seleccionar...</option>
                                                <?php

                                                $query = "SELECT codigo, nombre FROM com_laboratorio ORDER BY nombre";
                                                $result = mysqli_query($conexion, $query);
                                                if ($result && mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        echo '<option value="' . htmlspecialchars($row['codigo']) . '">' .
                                                            htmlspecialchars($row['nombre']) . '</option>';
                                                    }
                                                } else {
                                                    echo '<option value="">No hay laboratorios disponibles</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- GRUPO 2: Datos de Transporte y Responsables -->
                            <div class="field-group border border-gray-300 rounded-2xl p-8 bg-white">
                                <div class="group-header text-sm font-bold text-blue-600 uppercase tracking-wide pb-4 mb-6">
                                    Transporte y Responsables
                                </div>

                                <div class="space-y-6">
                                    <div class="form-field">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Empresa de Transporte <span class="text-red-500">*</span>
                                        </label>
                                        <select name="empresa_transporte" id="empresa_transporte" required
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer">
                                            <option value="">Seleccionar...</option>
                                            <?php

                                            $query = "SELECT codigo, nombre FROM com_emp_trans ORDER BY nombre";
                                            $result = mysqli_query($conexion, $query);
                                            if ($result && mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo '<option value="' . htmlspecialchars($row['codigo']) . '">' .
                                                        htmlspecialchars($row['nombre']) . '</option>';
                                                }
                                            } else {
                                                echo '<option value="">No hay empresas de transporte disponibles</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Usuario Registrador</label>
                                            <input name="usuario_registrador" value="<?php echo htmlspecialchars($_SESSION['usuario'] ?? 'user'); ?>" type="text" readonly
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 focus:outline-none">
                                        </div>

                                        <div class="form-field">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Usuario Responsable <span class="text-red-500">*</span>
                                            </label>
                                            <input name="usuario_responsable" type="text" placeholder="Nombre del responsable" required
                                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    </div>

                                    <div class="form-field">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Autorizado por <span class="text-red-500">*</span>
                                        </label>
                                        <input name="autorizado_por" type="text" placeholder="Nombre" required
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Número de Muestras integrado -->
                        <div class="form-field">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Número de Solicitudes <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="numeroSolicitudes" name="numeroSolicitudes" min="1" max="20"
                                placeholder="Ingrese cantidad de solicitudes" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- CONTENEDOR DE MUESTRAS DINÁMICAS -->
                    <div id="samples-container"></div>

                    <!-- TEMPLATE DE DATOS DE LA MUESTRA (oculto) -->
                    <div class="sample-template hidden">
                        <div class="form-section sample-block mb-8">


                            <div class="border border-gray-300 rounded-2xl p-8 bg-white space-y-6">
                                <div class="flex items-center gap-3 mb-8">
                                    <span class="text-3xl">🧪</span>
                                    <h2 class="text-blue-600 text-2xl font-bold">Solicitud #<span class="sample-number"></span></h2>
                                </div>
                                <div class="form-row grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-field">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Fecha de Toma <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" id="fechaToma" required
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div class="form-field">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            N° de Muestras <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="numeroMuestras" min="1" max="20"
                                            placeholder="Ingrese cantidad de muestras"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>

                                <div class="form-field">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        Tipo de Muestra (seleccione solo una) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="radio-group grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3" id="tipoMuestraRadios">
                                    </div>
                                </div>

                                <div class="form-field hidden" id="codigoReferenciaContainer">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        Código de Referencia <span class="text-red-500">*</span>
                                    </label>
                                    <div id="codigoReferenciaBoxes" class="flex flex-wrap gap-2 mb-3">
                                    </div>
                                    <input type="hidden" id="codigoReferenciaValue" required>
                                </div>

                                <div id="paquetesContainer" class="hidden"></div>

                                <div class="form-field">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                    <textarea id="observaciones"
                                        placeholder="Ingrese observaciones adicionales..."
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                        rows="4"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BUTTONS -->
                    <div class="btn-group flex flex-col-reverse sm:flex-row gap-4 justify-end">
                        <button type="button" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition duration-200">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2">
                            Guardar Registro
                        </button>
                    </div>
                </form>
            </div>
        </div>

         <!-- Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    
    <!-- Contenedor del modal -->
    <div class="bg-white rounded-lg shadow-lg w-[90%] max-w-3xl max-h-[600px] flex flex-col">
        
        <!-- Header -->
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h2 class="text-xl font-semibold">📋 Confirmar Envío de Muestras</h2>
            <button class="text-gray-500 text-2xl hover:text-gray-700" onclick="closeConfirmModal()">&times;</button>
        </div>

        <!-- Body -->
        <div class="overflow-y-auto p-6 flex-1">
            <div id="summaryContent"></div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t flex justify-end gap-3 bg-white">
            <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md" onclick="closeConfirmModal()">
                Cancelar
            </button>
            <button class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-md"
                onclick="confirmSubmit()">
                ✅ Confirmar y Guardar
            </button>
        </div>

    </div>
</div>
                                   
        
        <!-- Footer -->
        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © 2025
            </p>
        </div>

    </div>



    <script src="funciones.js"></script>
    <script src="planificacion.js"></script>
    <script src="registro.js"></script>
    <script src="manteminiento.js"></script>
</body>

</html>