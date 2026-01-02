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

//ruta relativa a la conexion
include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

$estadoCuali = strtolower(trim($row['estado_cuali'] ?? 'pendiente'));
$estadoCuanti = strtolower(trim($row['estado_cuanti'] ?? 'pendiente'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'cargar_detalle') {

        $codEnvio = $_POST['codEnvio'];

        $sql = "
            SELECT 
                codEnvio,
                posSolicitud,
                codRef,
                fecToma,
                numMuestras,
                nomMuestra,
                nomAnalisis,
                estado_cuali,
                estado_cuanti,
                obs
            FROM san_fact_solicitud_det
            WHERE codEnvio = ?
            ORDER BY posSolicitud ASC
        ";


        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $codEnvio);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $estadoCuali = strtolower(trim($row['estado_cuali'] ?? 'pendiente'));
                $estadoCuanti = strtolower(trim($row['estado_cuanti'] ?? 'pendiente'));

                $claseCuali = ($estadoCuali === 'completado')
                    ? 'bg-green-100 text-green-700'
                    : 'bg-yellow-100 text-yellow-700';

                $claseCuanti = ($estadoCuanti === 'completado')
                    ? 'bg-green-100 text-green-700'
                    : 'bg-yellow-100 text-yellow-700';

                $boton = '<button 
                            class="text-indigo-600 hover:text-indigo-800 transition"
                            title="Ver historial de acciones"
                            onclick="verHistorialResults(\'' . $row['codEnvio'] . '\', ' . $row['posSolicitud'] . ')">
                            <i class="fas fa-history text-lg"></i>
                        </button>';


                echo "<tr>
                                <td class='px-4 py-2'>{$row['codEnvio']}</td>
                    <td class='px-4 py-2'>{$row['posSolicitud']}</td>
                    <td class='px-4 py-2'>{$row['codRef']}</td>
                    <td class='px-4 py-2'>{$row['fecToma']}</td>
                    <td class='px-4 py-2 text-center'>{$row['numMuestras']}</td>
                    <td class='px-4 py-2'>{$row['nomMuestra']}</td>
                    <td class='px-4 py-2'>{$row['nomAnalisis']}</td>

                    <td class='px-4 py-2 text-center'>
                        <span class='inline-block px-3 py-1 rounded-full text-xs font-semibold {$claseCuali}'>
                            " . ucfirst($estadoCuali) . "
                        </span>
                    </td>

                    <td class='px-4 py-2 text-center'>
                        <span class='inline-block px-3 py-1 rounded-full text-xs font-semibold {$claseCuanti}'>
                            " . ucfirst($estadoCuanti) . "
                        </span>
                    </td>

                    <td class='px-4 py-2'>{$row['obs']}</td>
                    <td class='text-center'>
                        $boton
                    </td>
                </tr>";
            }
        } else {
            echo "<tr>
                    <td colspan='9' class='text-center py-4 text-gray-500'>
                        No hay detalle para este envío
                    </td>
                  </tr>";
        }

        exit; // aqui termina
    }

    // 

    if ($_POST['accion'] === 'cargar_resultados') {

        $codEnvio = $_POST['codEnvio'];

        $sql = "
        SELECT 
            codEnvio,
            posSolicitud,
            codRef,
            fecToma,
            analisis_nombre,
            resultado,
            usuarioRegistrador,
            fechaLabRegistro,
            obs
        FROM san_fact_resultado_analisis
        WHERE codEnvio = ?
        ORDER BY posSolicitud ASC
    ";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $codEnvio);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                <td class='px-4 py-2'>" . htmlspecialchars($row['codEnvio']) . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['posSolicitud']) . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['codRef']) . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['fecToma']) . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['analisis_nombre']) . "</td>
                <td class='px-4 py-2'>
                    <span class='inline-block px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700'>
                        " . htmlspecialchars($row['resultado']) . "
                    </span>
                </td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['usuarioRegistrador'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['fechaLabRegistro'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['obs'] ?? '') . "</td>
            </tr>";
            }
        } else {
            echo "<tr>
                <td colspan='9' class='text-center py-4 text-gray-500'>
                    No hay resultados de análisis para este envío
                </td>
              </tr>";
        }

        exit;
    }

    if ($_POST['accion'] === 'cargar_cuantitativos') {

        $codEnvio = $_POST['codEnvio'];

        $sql = "
        SELECT 
            id_analisis,
            codigo_envio,
            enfermedad,
            codigo_enfermedad,
            tipo_ave,
            fecha_toma_muestra,
            edad_aves,
            planta_incubacion,
            lote,
            codigo_granja,
            codigo_campana,
            numero_galpon,
            edad_reproductora,
            condicion,
            gmean,
            desviacion_estandar,
            cv,
            count_muestras,
            t01, t02, t03, t04, t05, t06, t07, t08, t09, t10,
            t11, t12, t13, t14, t15, t16, t17, t18, t19, t20,
            t21, t22, t23, t24, t25,
            titulo_promedio,
            lcs,
            lcc,
            lci,
            coef_variacion,
            std_1,
            std_2,
            s01, s02, s03, s04, s05, s06,
            numero_informe,
            fecha_informe,
            estado,
            usuario_registro,
            fecha_solicitud
            FROM san_analisis_pollo_bb_adulto
            WHERE codigo_envio = ?
            ORDER BY id_analisis ASC
        ";

        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            echo "<tr><td colspan='65' class='text-center py-4 text-red-500'>Error en la consulta: " . $conexion->error . "</td></tr>";
            exit;
        }

        $stmt->bind_param("s", $codEnvio);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                // Determinar color del estado
                $estadoClass = '';
                $estadoTexto = strtoupper($row['estado'] ?? 'PENDIENTE');

                if ($estadoTexto === 'COMPLETADO') {
                    $estadoClass = 'bg-green-100 text-green-700';
                } elseif ($estadoTexto === 'PENDIENTE') {
                    $estadoClass = 'bg-yellow-100 text-yellow-700';
                } else {
                    $estadoClass = 'bg-gray-100 text-gray-700';
                }

                echo "<tr class='hover:bg-gray-50'>
                <td class='px-4 py-2'>" . htmlspecialchars($row['id_analisis']) . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['codigo_envio']) . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['enfermedad'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['codigo_enfermedad'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'><span class='inline-block px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-700'>" . htmlspecialchars($row['tipo_ave'] ?? 'N/A') . "</span></td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['fecha_toma_muestra'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['edad_aves'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['planta_incubacion'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['lote'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['codigo_granja'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['codigo_campana'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['numero_galpon'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['edad_reproductora'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['condicion'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center font-semibold'>" . htmlspecialchars($row['gmean'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['desviacion_estandar'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['cv'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['count_muestras'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t01'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t02'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t03'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t04'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t05'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t06'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t07'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t08'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t09'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t10'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t11'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t12'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t13'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t14'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t15'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t16'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t17'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t18'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t19'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t20'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t21'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t22'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t23'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t24'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['t25'] ?? '0') . "</td>
                <td class='px-4 py-2 text-center font-semibold'>" . htmlspecialchars($row['titulo_promedio'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['lcs'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['lcc'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['lci'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['coef_variacion'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['std_1'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['std_2'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['s01'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['s02'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['s03'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['s04'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['s05'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>" . htmlspecialchars($row['s06'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['numero_informe'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['fecha_informe'] ?? 'N/A') . "</td>
                <td class='px-4 py-2 text-center'>
                    <span class='inline-block px-3 py-1 rounded-full text-xs font-semibold {$estadoClass}'>
                        {$estadoTexto}
                    </span>
                </td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['usuario_registro'] ?? 'N/A') . "</td>
                <td class='px-4 py-2'>" . htmlspecialchars($row['fecha_solicitud'] ?? 'N/A') . "</td>
            </tr>";
            }
        } else {
            echo "<tr>
                <td colspan='65' class='text-center py-4 text-gray-500'>
                    No hay resultados cuantitativos registrados para este envío
                </td>
              </tr>";
        }

        exit;
    }

    if ($_POST['accion'] === 'cargar_docsDetalle') {

        $codEnvio = $_POST['codEnvio'];

        $sql = "
        SELECT DISTINCT
            a.id,
            a.codEnvio,
            a.posSolicitud,
            d.codRef,
            d.numMuestras,
            d.nomMuestra,
            a.archRuta,
            a.tipo,
            a.usuarioRegistrador,
            a.fechaRegistro
        FROM san_fact_resultado_archivo a 
        LEFT JOIN san_fact_solicitud_det d 
            ON a.codEnvio = d.codEnvio 
            AND a.posSolicitud = d.posSolicitud
        WHERE a.codEnvio = ?
        ORDER BY a.posSolicitud, a.id
    ";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $codEnvio);
        $stmt->execute();
        $result = $stmt->get_result();

        $BASE_URL = 'https://granjarinconadadelsur.com/sanidad';
        //$BASE_URL = 'https://toshia-glucidic-herlinda.ngrok-free.dev/gc_sanidad_web';

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Extraer extensión del archivo
                $ruta = $row['archRuta'];
                $nombreArchivo = basename($ruta);
                $rutaPublica = $BASE_URL . '/' . ltrim($ruta, '/');
                $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));

                if ($extension === 'pdf') {

                    $boton = '<button onclick="previsualizarPDF(\'../../' . htmlspecialchars($ruta) . '\')" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg">
                                📄 Ver PDF
                            </button>';
                } elseif (in_array($extension, ['doc', 'docx', 'xls', 'xlsx'])) {

                    $boton = '<button onclick="previsualizarOffice(\'' . htmlspecialchars($rutaPublica) . '\')" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg">
                                📊 Ver Documento
                            </button>';
                } else {

                    $boton = '<a href="' . htmlspecialchars($rutaPublica) . '" download
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                                ⬇️ Descargar
                            </a>';
                }


                echo "<tr class='hover:bg-gray-50 transition'>
                <td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['codEnvio']) . "</td>
                <td class='px-4 py-3 text-sm text-center font-medium'>" . htmlspecialchars($row['posSolicitud']) . "</td>
                <td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['codRef'] ?? '—') . "</td>
                <td class='px-4 py-3 text-sm text-center'>" . ($row['fechaRegistro'] ? date('d/m/Y H:i', strtotime($row['fechaRegistro'])) : '—') . "</td>
                <td class='px-4 py-3 text-sm text-center'>
                    <span class='inline-block px-3 py-1.5 rounded-full font-semibold bg-blue-100 text-blue-700'>
                        " . htmlspecialchars($row['numMuestras'] ?? '—') . "
                    </span>
                </td>
                <td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['nomMuestra'] ?? '—') . "</td>
                <td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['tipo'] ?? '—') . "</td>
                <td class='px-4 py-3 text-sm text-center'>" . htmlspecialchars($row['usuarioRegistrador'] ?? '—') . "</td>
                <td class='px-4 py-3 text-sm text-gray-700'>" . htmlspecialchars($nombreArchivo) . "</td>
                <td class='px-4 py-3 text-center'>
                    $boton
                </td>
            </tr>";
            }
        } else {
            echo "<tr>
            <td colspan='9' class='text-center py-8 text-gray-500 italic'>
                No hay documentos registrados para esta solicitud
            </td>
        </tr>";
        }

        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Resultado de lab</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />



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

        .table-wrapper {
            overflow-x: auto;
            overflow-y: visible;
            width: 100%;
            border-radius: 1rem;
        }

        .table-wrapper::-webkit-scrollbar {
            height: 10px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        .data-table {
            width: 100% !important;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem 1rem;
            text-align: center;
            font-size: 0.875rem;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .data-table th {
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
            font-weight: 600;
            color: #ffffff !important;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table tbody tr:hover {
            background-color: #eff6ff !important;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding: 1rem;
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            margin: 0 0.5rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            margin-left: 0.5rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 1rem !important;
            margin: 0 0.25rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%) !important;
            color: white !important;
            border: 1px solid #1e40af !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #eff6ff !important;
            color: #1d4ed8 !important;
        }

        table.dataTable thead .sorting:before,
        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_desc:before,
        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:after {
            color: white !important;
        }

        .dataTables_wrapper {
            overflow-x: visible !important;
        }


        /* Select2 estilo Tailwind */
        .select2-container .select2-selection--single {
            height: 38px;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            /* gray-300 */
            padding: 4px 8px;
            display: flex;
            align-items: center;
        }

        .select2-selection__rendered {
            font-size: 0.875rem;
            color: #374151;
            /* gray-700 */
        }

        .select2-selection__arrow {
            height: 100%;
        }

        .select2-container--default .select2-selection--single:focus {
            outline: none;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!--  -->
        <div id="" class="content-view">

            <div class="form-container w-full">
                <!-- CARD FILTROS PLEGABLE -->
                <div class="mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

                    <!-- HEADER -->
                    <button type="button" onclick="toggleFiltros()"
                        class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition">

                        <div class="flex items-center gap-2">
                            <span class="text-lg">🔎</span>
                            <h3 class="text-base font-semibold text-gray-800">
                                Filtros de búsqueda
                            </h3>
                        </div>

                        <!-- ICONO -->
                        <svg id="iconoFiltros" class="w-5 h-5 text-gray-600 transition-transform duration-300"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- CONTENIDO PLEGABLE -->
                    <div id="contenidoFiltros" class="px-6 pb-6 pt-4 hidden">

                        <!-- GRID DE FILTROS -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                            <!-- Fecha inicio -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                                <input type="date" id="filtroFechaInicio"
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            </div>

                            <!-- Fecha fin -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                                <input type="date" id="filtroFechaFin"
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            </div>

                            <!-- Estado -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <select id="filtroEstado"
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                    <option value="">Seleccionar</option>
                                    <option value="Completado">Completado</option>
                                    <option value="Pendiente">Pendiente</option>
                                </select>
                            </div>

                            <!-- Laboratorio -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Laboratorio</label>
                                <select id="filtroLaboratorio"
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $sql = "SELECT codigo, nombre FROM san_dim_laboratorio ORDER BY nombre ASC";
                                    $res = $conexion->query($sql);

                                    if ($res && $res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($row['nombre']) . '">'
                                                . htmlspecialchars($row['nombre']) .
                                                '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Tipo análisis (autocomplete) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tipo de análisis
                                </label>

                                <select id="filtroTipoAnalisis"
                                    class="w-full text-sm rounded-lg border border-gray-300">
                                </select>
                            </div>


                            <!-- Tipo muestra -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de muestra</label>
                                <select id="filtroTipoMuestra"
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $sql = "SELECT codigo, nombre FROM san_dim_tipo_muestra ORDER BY nombre ASC";
                                    $res = $conexion->query($sql);

                                    if ($res && $res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($row['nombre']) . '">'
                                                . htmlspecialchars($row['nombre']) .
                                                '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Granja -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Granja(s)</label>

                                <div class="relative">
                                    <button type="button" id="dropdownGranjaBtn"
                                        class="w-full px-3 py-2 text-sm text-left bg-white border border-gray-300 rounded-lg shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 flex justify-between items-center">
                                        <span id="dropdownGranjaText" class="text-gray-500">Seleccionar
                                            granjas...</span>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <!-- Dropdown con checkboxes -->
                                    <div id="dropdownGranjaMenu"
                                        class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                                        <div class="p-2">
                                            <?php
                                            $sql = "
                                                    SELECT codigo, nombre
                                                    FROM ccos
                                                    WHERE LENGTH(codigo)=3
                                                    AND swac='A'
                                                    AND LEFT(codigo,1)='6'
                                                    AND codigo NOT IN ('650','668','669','600')
                                                    ORDER BY nombre
                                                ";

                                            $res = mysqli_query($conexion, $sql);

                                            if ($res && mysqli_num_rows($res) > 0) {
                                                while ($row = mysqli_fetch_assoc($res)) {
                                                    echo '
                                                        <label class="flex items-center px-3 py-2 hover:bg-gray-50 rounded cursor-pointer">
                                                            <input type="checkbox" 
                                                                name="filtroGranja[]" 
                                                                value="' . htmlspecialchars($row['codigo']) . '" 
                                                                class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                                            <span class="ml-3 text-sm text-gray-700">' . htmlspecialchars($row['nombre']) . '</span>
                                                        </label>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Galpón -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Galpón</label>
                                <select id="filtroGalpon"
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                    <option value="">Seleccionar</option>
                                    <?php
                                    for ($i = 1; $i <= 13; $i++) {
                                        $valor = str_pad($i, 2, '0', STR_PAD_LEFT); // 01, 02, ...
                                        echo "<option value=\"$valor\">$valor</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Edad -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Edad</label>

                                <div class="flex gap-2">
                                    <input type="number" id="filtroEdadDesde" placeholder="Desde" min="0"
                                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">

                                    <input type="number" id="filtroEdadHasta" placeholder="Hasta" min="0"
                                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                </div>
                            </div>

                        </div>

                        <!-- ACCIONES -->
                        <div class="mt-8 mb-4 flex flex-wrap justify-end gap-4">

                            <button type="button" id="btnFiltrar"
                                class="px-6 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                                Filtrar
                            </button>

                            <button type="button" id="btnLimpiar"
                                class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 hover:bg-gray-200">
                                Limpiar
                            </button>

                            <button type="button"
                                class="px-6 py-2.5 text-white font-medium rounded-lg transition inline-flex items-center gap-2"
                                onclick="exportarReporteExcel()"
                                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);">
                                📊 Exportar a Excel
                            </button>
                            <button type="button"
                                class=" hidden px-6 py-2.5 text-white font-medium rounded-lg transition inline-flex items-center gap-2"
                                onclick="generarReportePDF()"
                                style="background: linear-gradient(135deg, #b91b10e1 0%, #960f05ff 100%); box-shadow: 0 4px 6px rgba(185, 16, 38, 0.3);">
                                Exportar a PDF
                            </button>
                        </div>

                    </div>
                </div>

                <?php
                $codigoUsuario = $_SESSION['usuario'] ?? 'USER';  // Cambia 'usuario' si tu sesión usa otro nombre
                // Consulta directa, simple
                $sql = "SELECT rol_sanidad FROM usuario WHERE codigo = '$codigoUsuario'";
                $res = $conexion->query($sql);

                $rol = 'user'; // valor por defecto si no encuentra nada
                
                if ($res && $res->num_rows > 0) {
                    $fila = $res->fetch_assoc();
                    $rol = strtolower(trim($fila['rol_sanidad']));
                }
                ?>

                <!-- Este <p> oculto guarda el rol para que JavaScript lo lea -->
                <p id="idRolUser" data-rol="<?= htmlspecialchars($rol) ?>"></p>

                <!-- Tabla  -->
                <div class="max-w-full mx-auto mt-6">
                    <div class="border border-gray-300 rounded-2xl bg-white overflow-hidden">
                        <div class="table-wrapper">
                            <table id="tablaResultados" class="data-table display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th class="">Cod. Envío</th>

                                        <th class="">Fecha Envio</th>
                                        <th class="">Nom. Lab</th>
                                        <th class="">Nom. EmpTrans</th>
                                        <th class="">Usuario Registrador</th>
                                        <th class="">Usuario Responsable</th>
                                        <th class="">Autorizado Por</th>
                                        <th class="">Muestra</th>
                                        <th class="">Analisis</th>
                                        <th class="">Estado</th>
                                        <th class="">Obs</th>
                                        <!-- NUEVAS COLUMNAS -->
                                        <th class="">Detalle</th>
                                        <th class="">Seguimiento</th>
                                        <th class="">PDF</th>
                                        <th class="">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Detalle -->
        <div id="modalDetalle"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="w-full mx-auto bg-white rounded-lg shadow-2xl flex flex-col"
                style="width: 80vw; max-width: 1200px; height: 90vh;">

                <!-- Header del Modal -->
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800">Detalle de Envío</h2>
                    <button onclick="cerrarModalDetalle()"
                        class="text-gray-500 hover:text-gray-800 text-2xl leading-none">
                        ×
                    </button>
                </div>

                <!-- Tabs Navigation -->
                <div class="flex border-b border-gray-200 px-6 bg-gray-50">
                    <button onclick="cambiarTab(1)"
                        class="tab-btn tab-active px-4 py-3 font-semibold text-gray-700 border-b-2 border-blue-600 hover:text-blue-600">
                        Detalle
                    </button>
                    <button onclick="cambiarTab(2)"
                        class="tab-btn px-4 py-3 font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700">
                        Resultado Cualitativo
                    </button>
                    <button onclick="cambiarTab(3)"
                        class="tab-btn px-4 py-3 font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700">
                        Resultado Cuantitativo
                    </button>
                    <button onclick="cambiarTab(4)"
                        class="tab-btn px-4 py-3 font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700">
                        Documentos Guardados
                    </button>
                </div>

                <!-- Tab Contents -->
                <div class="flex-1 overflow-hidden">

                    <!-- Tab 1 - Detalle del Envío -->
                    <div id="tab-1" class="tab-content h-full">
                        <div class="h-full overflow-auto">
                            <table class="w-full border-collapse text-sm">
                                <thead class="sticky top-0 bg-gray-100 border-b border-gray-300 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Código</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Pos</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Referencia</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Fecha Toma</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            N° Muestras</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Muestra</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Análisis</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Estado Cuali</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Estado Cuanti</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Observaciones</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Historial</th>
                                    </tr>
                                </thead>
                                <tbody id="detalleBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-gray-500">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2 - Resultados de Análisis -->
                    <div id="tab-2" class="tab-content hidden h-full">
                        <div class="h-full overflow-auto">
                            <table class="w-full border-collapse text-sm">
                                <thead class="sticky top-0 bg-gray-100 border-b border-gray-300 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Código Envío</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Pos Solicitud</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Cod Ref</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Fecha Toma</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Análisis</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Resultado</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Usuario Registrador</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Fecha Lab Registro</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody id="resultadosBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-gray-500">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3 - cuantitativos -->
                    <div id="tab-3" class="tab-content hidden h-full">
                        <div class="h-full overflow-auto">
                            <table class="w-full border-collapse text-sm">
                                <thead class="sticky top-0 bg-gray-100 border-b border-gray-300 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">ID
                                        </th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Código Envío</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Enfermedad</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Cód Enfermedad</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Tipo Ave</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Fecha Toma</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Edad Aves</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Planta Incubación</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Lote</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Código Granja</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Código Campaña</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Número Galpón</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Edad Reproductora</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Condición</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Gmean</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            SD</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            CV</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Count Muestras</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T01</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T02</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T03</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T04</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T05</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T06</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T07</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T08</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T09</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T10</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T11</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T12</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T13</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T14</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T15</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T16</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T17</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T18</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T19</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T20</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T21</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T22</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T23</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T24</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            T25</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Título Promedio</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            LCS</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            LCC</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            LCI</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            %Coef Var</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            STD I</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            STD S</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            S01</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            S02</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            S03</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            S04</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            S05</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            S06</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Número Informe</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Fecha Informe</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Estado</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Usuario Registro</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">
                                            Fecha Solicitud</th>
                                    </tr>
                                </thead>
                                <tbody id="cuantitativosBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="65" class="text-center py-4 text-gray-500">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 4 - documentos -->
                    <div id="tab-4" class="tab-content h-full">
                        <div class="h-full overflow-auto">
                            <table class="w-full border-collapse text-sm">
                                <thead class="sticky top-0 bg-gray-100 border-b border-gray-300 z-10">
                                    <tr>
                                        <th class="px-4 py-3 text-centerfont-semibold text-gray-700 whitespace-nowrap">
                                            Código</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Pos</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Cod Ref</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Fecha</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            N° Muestras</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Muestra</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Tipo</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            User Registro</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Archivo</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">
                                            Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="docsBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-gray-500">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Footer del Modal -->
                <div class="border-t border-gray-200 px-6 py-4 flex justify-end gap-3 bg-gray-50">
                    <button onclick="cerrarModalDetalle()"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Tracking -->
        <div id="modalTracking"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="w-full mx-auto bg-white rounded-lg shadow-2xl flex flex-col"
                style="width: 80vw; max-width: 1200px; height: 90vh;">

                <!-- Header -->
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-clock-rotate-left text-amber-600 mr-2"></i>
                        Historial de Seguimiento
                    </h2>
                    <button onclick="cerrarModalTracking()"
                        class="text-gray-500 hover:text-gray-800 text-2xl leading-none">
                        ×
                    </button>
                </div>

                <!-- Progreso General -->
                <div id="resumenTracking"
                    class="px-6 py-2 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">

                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <p class="text-xs text-gray-600">Progreso General del Envío</p>
                            <p class="text-lg font-bold text-gray-800" id="codEnvioTracking"></p>
                        </div>

                        <div class="text-right leading-tight">
                            <p class="text-2xl font-bold text-blue-600" id="porcentajeComplecion">0%</p>
                            <p class="text-xs text-gray-600">Completado</p>
                        </div>
                    </div>

                    <div class="w-full bg-gray-300 rounded-full h-2">
                        <div id="barraProgreso"
                            class="bg-gradient-to-r from-green-400 to-blue-500 h-2 rounded-full transition-all duration-500"
                            style="width: 0%">
                        </div>
                    </div>
                </div>


                <!-- Timeline -->
                <div class="flex-1 overflow-y-auto px-6 py-8">
                    <div id="timelineContainer" class="space-y-8">
                        <!-- Los eventos se cargarán aquí -->
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex justify-end">
                    <button onclick="cerrarModalTracking()"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para PDF -->
        <div id="modalPDF" class="fixed inset-0 bg-black bg-opacity-75 hidden flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-5/6 flex flex-col">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b">

                    <!-- Título + Descargar -->
                    <div class="flex items-center gap-4">
                        <button onclick="descargarArchivo()"
                            class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm flex items-center gap-1">
                            ⬇️ Descargar
                        </button>
                    </div>

                    <!-- Cerrar -->
                    <button onclick="cerrarModalPDF()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">
                        ×
                    </button>
                </div>


                <!-- Body -->
                <div class="flex-1 overflow-hidden">
                    <iframe id="iframePDF" class="w-full h-full" frameborder="0"></iframe>
                </div>
            </div>
        </div>

        <!-- Modal Historial de Resultados -->
        <div id="modalHistorial"
            class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50 rounded-t-xl">
                    <h3 class="text-lg font-bold text-gray-800">Historial de Acciones</h3>
                    <button onclick="cerrarModalHistorial()" class="text-gray-500 hover:text-gray-700 text-2xl">
                        ×
                    </button>
                </div>

                <!-- Contenido scrollable -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div class="text-center mb-4">
                        <p class="text-sm text-gray-600">Código: <strong id="historialCodEnvio"></strong> • Posición:
                            <strong id="historialPos"></strong>
                        </p>
                    </div>

                    <div id="historialContainer" class="space-y-4">
                        <!-- Se llena con JS -->
                        <p class="text-center text-gray-500 py-8">Cargando historial...</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t bg-gray-50 rounded-b-xl text-right">
                    <button onclick="cerrarModalHistorial()"
                        class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal de Edición -->
        <div class="modal fade" id="modalEditarEnvio" tabindex="-1" aria-labelledby="modalEditarEnvioLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-blue-50">
                        <h5 class="modal-title" id="modalEditarEnvioLabel">Editar Envío</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Aquí irá el formulario -->
                        <form id="formEditarEnvio">
                            <!-- INFORMACIÓN DE REGISTRO Y ENVÍO -->
                            <div class="form-section mb-6">
                                <div class="dual-group-container grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
                                    <!-- GRUPO 1: Datos de Envío -->
                                    <div class="field-group border border-gray-300 rounded-2xl p-5 bg-white">
                                        <div
                                            class="group-header text-xs font-bold text-blue-600 uppercase tracking-wide pb-2 mb-4">
                                            Datos de Envío
                                        </div>
                                        <div class="space-y-4">
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="form-field">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                                        Fecha de Envío <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="date" id="fechaEnvio" name="fechaEnvio" required
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                                </div>
                                                <div class="form-field">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                                        Hora <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="time" id="horaEnvio" name="horaEnvio" required
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="form-field">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                                        Código de Envío <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" id="codigoEnvio" name="codigoEnvio" readonly
                                                        class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg font-bold text-blue-600 focus:outline-none text-sm">
                                                </div>
                                                <div class="form-field">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                                        Laboratorio <span class="text-red-500">*</span>
                                                    </label>
                                                    <select id="laboratorio" name="laboratorio"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm">
                                                        <option value="">Seleccionar...</option>
                                                        <?php
                                                        $query = "SELECT codigo, nombre FROM san_dim_laboratorio ORDER BY nombre";
                                                        $result = mysqli_query($conexion, $query);
                                                        while ($row = mysqli_fetch_assoc($result)) {
                                                            echo '<option value="' . htmlspecialchars($row['codigo']) . '">' . htmlspecialchars($row['nombre']) . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- GRUPO 2: Datos de Transporte y Responsables -->
                                    <div class="field-group border border-gray-300 rounded-2xl p-5 bg-white">
                                        <div
                                            class="group-header text-xs font-bold text-blue-600 uppercase tracking-wide pb-2 mb-4">
                                            TRANSPORTE Y RESPONSABLES
                                        </div>
                                        <div class="space-y-4">
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="form-field">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                                        Empresa de Transporte <span class="text-red-500">*</span>
                                                    </label>
                                                    <select name="empresa_transporte" id="empresa_transporte"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm">
                                                        <option value="">Seleccionar...</option>
                                                        <?php
                                                        $query = "SELECT codigo, nombre FROM san_dim_emptrans ORDER BY nombre";
                                                        $result = mysqli_query($conexion, $query);
                                                        while ($row = mysqli_fetch_assoc($result)) {
                                                            echo '<option value="' . htmlspecialchars($row['codigo']) . '">' . htmlspecialchars($row['nombre']) . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-field">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                                        Autorizado por <span class="text-red-500">*</span>
                                                    </label>
                                                    <input name="autorizado_por" id="autorizado_por" type="text"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="form-field">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Usuario
                                                        Registrador</label>
                                                    <input name="usuario_registrador" id="usuario_registrador"
                                                        type="text" readonly
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 focus:outline-none text-sm">
                                                </div>
                                                <div class="form-field">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                                        Usuario Responsable <span class="text-red-500">*</span>
                                                    </label>
                                                    <input name="usuario_responsable" id="usuario_responsable"
                                                        type="text"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Número de Solicitudes -->
                                <div class="form-field max-w-xs">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Número de Solicitudes <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="numeroSolicitudes" name="numeroSolicitudes" min="1"
                                        max="30" placeholder="Ingrese cantidad"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </div>
                            </div>

                            <!-- DETALLES DE LAS SOLICITUDES -->
                            <div class="form-section mt-6">
                                <h6 class="font-bold text-gray-700 mb-3">Detalles de las Solicitudes</h6>
                                <div id="tablaSolicitudes" class="space-y-4">
                                    <!-- Las filas se generarán dinámicamente aquí -->
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btnGuardarEdicion">Guardar Cambios</button>
                    </div>
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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap 5 JS (requiere Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        var table; // Variable global

        // Función única para cargar/filtrar la tabla
        function cargarTabla() {

            // Si la tabla ya existe, destruirla
            if (table) {
                table.destroy();
            }

            // Obtener valores de los filtros
            var fechaInicio = $('#filtroFechaInicio').val();
            var fechaFin = $('#filtroFechaFin').val();
            var estado = $('#filtroEstado').val();
            var laboratorio = $('#filtroLaboratorio').val();
            var muestra = $('#filtroTipoMuestra').val();
            var analisis = $('#filtroTipoAnalisis').val();

            //array de granjas
            var granjas = Array.from(document.querySelectorAll('input[name="filtroGranja[]"]:checked'))
                .map(cb => cb.value);

            var galpon = $('#filtroGalpon').val();
            var edadDesde = $('#filtroEdadDesde').val();
            var edadHasta = $('#filtroEdadHasta').val();


            // Inicializar/Reinicializar DataTable
            table = $('#tablaResultados').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                autoWidth: false,
                dom: `
                    <"flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6"
                        <"flex items-center gap-6"
                            <"text-sm text-gray-600" l>
                            <"text-sm text-gray-600" i>
                        >
                        <"flex items-center gap-2" f>
                    >
                    rt
                    <"flex flex-col md:flex-row md:items-center md:justify-between gap-4 mt-6"
                        <"text-sm text-gray-600" p>
                    >
                    `,

                ajax: {
                    url: 'listar_cab_filtros.php',
                    type: 'POST',
                    data: {
                        fechaInicio: fechaInicio,
                        fechaFin: fechaFin,
                        estado: estado,
                        laboratorio: laboratorio,
                        muestra: muestra,
                        analisis: analisis,
                        granjas: granjas,
                        galpon: galpon,
                        edadDesde: edadDesde,
                        edadHasta: edadHasta,
                    }
                },
                columns: [{
                    data: 'codEnvio'
                },
                {
                    data: 'fecEnvio'
                },
                {
                    data: 'nomLab'
                },
                {
                    data: 'nomEmpTrans'
                },
                {
                    data: 'usuarioRegistrador'
                },
                {
                    data: 'usuarioResponsable'
                },
                {
                    data: 'autorizadoPor'
                },
                {
                    data: 'muestras',
                    className: 'max-w-xs',
                    render: function (data, type, row) {
                        if (!data || data.trim() === '' || data === '—') {
                            return '<span class="text-gray-400 italic">—</span>';
                        }

                        const items = data.split(', ').map(item => item.trim()).filter(item => item);
                        if (items.length === 0) return '<span class="text-gray-400 italic">—</span>';

                        const visibles = items.slice(0, 3);
                        const ocultos = items.slice(3);
                        const tieneMas = ocultos.length > 0;

                        let texto = '(' + visibles.join(', ') + ')';
                        if (tieneMas) {
                            texto += ` <span class="ml-2 text-lime-600 font-semibold cursor-help" title="${ocultos.join(', ')}">+${ocultos.length} más</span>`;
                        }

                        return `
                                <div class="inline-block px-4 py-3 rounded-xl bg-lime-100 border border-lime-200 text-lime-800 text-sm font-medium leading-snug">
                                    <span class="">
                                        ${texto}
                                    </span>
                                </div>
                            `;
                    }
                },
                {
                    data: 'analisis',
                    className: 'max-w-sm', // Controla el ancho para que envuelva bien
                    render: function (data, type, row) {
                        if (!data || data.trim() === '' || data === '—') {
                            return '<span class="text-gray-400 italic">—</span>';
                        }

                        // Dividir en análisis individuales
                        const items = data.split(', ').map(item => item.trim()).filter(item => item);

                        if (items.length === 0) {
                            return '<span class="text-gray-400 italic">—</span>';
                        }

                        // Mostrar máximo 5 análisis visibles
                        const visibles = items.slice(0, 3);
                        const ocultos = items.slice(3);
                        const tieneMas = ocultos.length > 0;

                        // Construir el texto visible: (A, B, C, D, E)
                        let textoVisible = '(' + visibles.join(', ') + ')';

                        // Si hay más, agregar el indicador
                        if (tieneMas) {
                            textoVisible += ` <span class="ml-2 text-blue-600 font-semibold cursor-help" title="${ocultos.join(', ')}">+${ocultos.length} más</span>`;
                        }

                        return `
                            <div class="inline-block px-4 py-3 rounded-xl bg-blue-100 border border-blue-200 text-blue-800 text-sm font-medium leading-snug">
                                <span class="">
                                    ${textoVisible}
                                </span>
                            </div>
                        `;
                    }
                },
                {
                    data: 'estado',
                    className: 'text-center',
                    render: function (data) {

                        if (data === 'pendiente') {
                            return `
                                    <span class="inline-flex items-center px-3 py-1 rounded-full 
                                                font-semibold
                                                bg-yellow-100 text-yellow-800">
                                        Pendiente
                                    </span>
                                `;
                        }

                        if (data === 'completado') {
                            return `
                                    <span class="inline-flex items-center px-3 py-1 rounded-full 
                                                font-semibold
                                                bg-green-100 text-green-800">
                                        Completado
                                    </span>
                                `;
                        }

                        return data; // fallback por si aparece otro estado
                    }
                },
                {
                    data: 'obs',
                    className: 'text-sm text-gray-700',
                    render: function (data, type, row) {
                        return (data === null || data === undefined || data.trim() === '') ?
                            '<span class="text-gray-400 italic">N/A</span>' :
                            data;
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return `<button 
                            class="text-blue-600 hover:text-blue-800 transition"
                            title="Ver detalle"
                            onclick="verDetalle('${row.codEnvio}')">
                            <i class="fa-solid fa-eye text-lg"></i>
                        </button>`;
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return `<button 
                            class="text-amber-600 hover:text-amber-800 transition"
                            title="Ver historial"
                            onclick="verHistorial('${row.codEnvio}')">
                            <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                        </button>`;
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return `<button 
                            class="text-red-600 hover:text-red-800 transition"
                            title="Generar PDF"
                            onclick="generarReportePDF('${row.codEnvio}')">
                            <i class="fa-solid fa-file-pdf"></i>
                        </button>`;
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        const rolUser = (document.getElementById('idRolUser')?.textContent.trim().toLowerCase() ||
                            document.getElementById('idRolUser')?.dataset.rol?.toLowerCase() ||
                            '');

                        let buttons = '';

                        buttons += `<button 
                            class="btn-editar text-blue-600 hover:text-blue-800 transition mr-2"
                            title="Editar solicitud"
                            data-codenvio="${row.codEnvio}">
                            <i class="fa-solid fa-edit"></i>
                        </button>`;

                        if (rolUser === 'admin') {
                            buttons += `<button 
                                class="btn-borrar text-red-600 hover:text-red-800 transition"
                                title="Eliminar solicitud"
                                data-codenvio="${row.codEnvio}">
                                <i class="fa-solid fa-trash"></i>
                            </button>`;
                        }

                        return buttons;
                    }
                }
                ],
                columnDefs: [{
                    targets: '_all',
                    className: 'px-6 py-4 text-sm text-gray-700'
                }],
                rowCallback: function (row, data) {
                    $(row).addClass('hover:bg-gray-50 transition');
                },
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                pageLength: 5,
                lengthMenu: [
                    [5, 10, 15, 20, 25],
                    [5, 10, 15, 20, 25]
                ],
            });

        }

        // Cargar tabla al iniciar la página
        $(document).ready(function () {
            cargarTabla();

            // ASIGNAR EVENTOS A LOS BOTONES
            $('#btnFiltrar').click(function () {
                cargarTabla();
            });

            $('#btnLimpiar').click(function () {
                $('#filtroFechaInicio').val('');
                $('#filtroFechaFin').val('');
                $('#filtroEstado').val('');
                $('#filtroLaboratorio').val('');
                $('#filtroTipoMuestra').val('');

                // Limpiar select2 (si usas Select2 para análisis)
                $('#filtroTipoAnalisis').val(null).trigger('change');

                $('#filtroGalpon').val('');
                $('#filtroEdadDesde').val('');
                $('#filtroEdadHasta').val('');

                // === NUEVO: Limpiar dropdown de granjas múltiples ===
                const checkboxesGranja = document.querySelectorAll('input[name="filtroGranja[]"]');
                checkboxesGranja.forEach(cb => {
                    cb.checked = false;
                });

                // Restaurar texto del botón dropdown
                const dropdownText = document.getElementById('dropdownGranjaText');
                if (dropdownText) {
                    dropdownText.textContent = "Seleccionar granjas...";
                    dropdownText.classList.add('text-gray-500');
                }

                // Cerrar el dropdown si está abierto
                const dropdownMenu = document.getElementById('dropdownGranjaMenu');
                if (dropdownMenu) {
                    dropdownMenu.classList.add('hidden');
                }

                // Recargar la tabla con filtros limpios
                cargarTabla();
            });
        });


        function verHistorialResults(codEnvio, posSolicitud) {
            document.getElementById('historialCodEnvio').textContent = codEnvio;
            document.getElementById('historialPos').textContent = posSolicitud;

            const container = document.getElementById('historialContainer');
            container.innerHTML = '<p class="text-center text-gray-500 py-8">Cargando historial...</p>';

            document.getElementById('modalHistorial').classList.remove('hidden');

            fetch('get_historial_resultados.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `codEnvio=${encodeURIComponent(codEnvio)}&posSolicitud=${posSolicitud}`
            })
                .then(r => r.json())
                .then(data => {
                    container.innerHTML = '';

                    if (!data.success || data.historial.length === 0) {
                        container.innerHTML = '<p class="text-center text-gray-500 py-8">No hay acciones registradas para esta solicitud.</p>';
                        return;
                    }

                    // Ordenar por fecha descendente (más reciente arriba)
                    data.historial.sort((a, b) => new Date(b.fechaHoraRegistro) - new Date(a.fechaHoraRegistro));

                    data.historial.forEach(item => {
                        const fecha = new Date(item.fechaHoraRegistro).toLocaleString('es-PE');

                        const div = document.createElement('div');
                        div.className = 'flex gap-4 py-3 border-b border-gray-200 last:border-0';

                        div.innerHTML = `
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-user text-gray-600"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-medium text-gray-800">${item.accion}</p>
                            <p class="text-sm text-gray-600 mt-1">${item.comentario || 'Sin comentario'}</p>
                            ${item.tipo_analisis ? `<span class="inline-block mt-2 px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">${item.tipo_analisis}</span>` : ''}
                        </div>
                        <span class="text-xs text-gray-500">${fecha}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-user mr-1"></i> ${item.usuario}
                    </p>
                </div>
            `;

                        container.appendChild(div);
                    });
                })
                .catch(err => {
                    console.error(err);
                    container.innerHTML = '<p class="text-center text-red-500 py-8">Error al cargar el historial.</p>';
                });
        }

        function cerrarModalHistorial() {
            document.getElementById('modalHistorial').classList.add('hidden');
        }
    </script>

    <script>
        function generarReportePDF(codEnvio) {
            if (!codEnvio) {
                alert('Seleccione una solicitud primero');
                return;
            }
            window.open(`reporteSeguimientoMuestrasPdf.php?codEnvio=${codEnvio}`, '_blank');
        }

        function borrarRegistros(codEnvio) {
            // Confirmación simple y clara
            if (!confirm(`¿Estás COMPLETAMENTE seguro de eliminar el envío "${codEnvio}"?\n\nEsta acción:\n• Eliminará todos los resultados, archivos y detalles asociados\n• NO se puede deshacer\n\n¿Continuar?`)) {
                return; // Cancela si dice "No"
            }

            // Enviar al PHP
            fetch('borrarSolicitudCompleto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'codEnvio=' + encodeURIComponent(codEnvio)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Envío eliminado correctamente');
                        // Recargar la tabla
                        if (table) table.ajax.reload();
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('❌ Error de conexión');
                });
        }
    </script>

    <script>
        let codEnvioActual = null;

        function cargarResultados() {
            if (!codEnvioActual) return;

            document.getElementById('resultadosBody').innerHTML =
                '<tr><td colspan="9" class="text-center py-4 text-gray-500">Cargando...</td></tr>';

            fetch('dashboard-seguimiento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'accion=cargar_resultados&codEnvio=' + encodeURIComponent(codEnvioActual)
            })
                .then(r => r.text())
                .then(html => {
                    document.getElementById('resultadosBody').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('resultadosBody').innerHTML =
                        '<tr><td colspan="9" class="text-center py-4 text-red-500">Error al cargar los datos</td></tr>';
                    console.error('Error:', error);
                });
        }

        function verDetalle(codEnvio) {
            codEnvioActual = codEnvio;
            document.getElementById('detalleBody').innerHTML =
                '<tr><td colspan="10" class="text-center py-4 text-gray-500">Cargando...</td></tr>';

            // Cambiar esta ruta a tu PHP real
            fetch('dashboard-seguimiento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'accion=cargar_detalle&codEnvio=' + encodeURIComponent(codEnvio)
            })
                .then(r => r.text())
                .then(html => {
                    document.getElementById('detalleBody').innerHTML = html;
                    document.getElementById('modalDetalle').classList.remove('hidden');
                    document.getElementById('modalDetalle').classList.add('flex');
                    cambiarTab(1);
                })
                .catch(error => {
                    document.getElementById('detalleBody').innerHTML =
                        '<tr><td colspan="10" class="text-center py-4 text-red-500">Error al cargar los datos</td></tr>';
                    console.error('Error:', error);
                });
        }

        function cerrarModalDetalle() {
            document.getElementById('modalDetalle').classList.add('hidden');
            document.getElementById('modalDetalle').classList.remove('flex');
        }

        function cambiarTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('tab-active', 'border-blue-600', 'text-blue-600');
                el.classList.add('border-transparent', 'text-gray-500');
            });

            document.getElementById('tab-' + tab).classList.remove('hidden');
            document.querySelectorAll('.tab-btn')[tab - 1].classList.remove('border-transparent', 'text-gray-500');
            document.querySelectorAll('.tab-btn')[tab - 1].classList.add('tab-active', 'border-blue-600', 'text-blue-600');

            // Cargar datos del Tab 2 si se hace clic
            if (tab === 2) {
                cargarResultados();
            }
            if (tab === 3) {
                cargarCuantitativos();
            }
            if (tab = 4) {
                cargarDocumentosDetalle();
            }
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalDetalle').addEventListener('click', function (e) {
            if (e.target === this) {
                cerrarModalDetalle();
            }
        });

        function toggleFiltros() {
            const contenido = document.getElementById('contenidoFiltros');
            const icono = document.getElementById('iconoFiltros');

            contenido.classList.toggle('hidden');
            icono.classList.toggle('rotate-180');
        }

        $('#filtroTipoAnalisis').select2({
            placeholder: 'Seleccionar análisis',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0, // 🔑 CLAVE
            ajax: {
                url: 'buscar_analisis.php',
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || ''
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                }
            }
        });
    </script>

    <script>
        function cargarCuantitativos() {
            if (!codEnvioActual) return;

            document.getElementById('cuantitativosBody').innerHTML =
                '<tr><td colspan="65" class="text-center py-4 text-gray-500">Cargando...</td></tr>';

            fetch('dashboard-seguimiento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'accion=cargar_cuantitativos&codEnvio=' + encodeURIComponent(codEnvioActual)
            })
                .then(r => r.text())
                .then(html => {
                    document.getElementById('cuantitativosBody').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('cuantitativosBody').innerHTML =
                        '<tr><td colspan="65" class="text-center py-4 text-red-500">Error al cargar los datos</td></tr>';
                    console.error('Error:', error);
                });
        }

        function cargarDocumentosDetalle() {
            if (!codEnvioActual) return;

            document.getElementById('docsBody').innerHTML =
                '<tr><td colspan="65" class="text-center py-4 text-gray-500">Cargando...</td></tr>';

            fetch('dashboard-seguimiento.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'accion=cargar_docsDetalle&codEnvio=' + encodeURIComponent(codEnvioActual)
            })
                .then(r => r.text())
                .then(html => {
                    document.getElementById('docsBody').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('docsBody').innerHTML =
                        '<tr><td colspan="65" class="text-center py-4 text-red-500">Error al cargar los datos</td></tr>';
                    console.error('Error:', error);
                });
        }

        function verHistorial(codEnvio) {
            document.getElementById('timelineContainer').innerHTML = '<div class="text-center py-8"><p class="text-gray-500">Cargando historial...</p></div>';
            document.getElementById('modalTracking').classList.remove('hidden');
            document.getElementById('modalTracking').classList.add('flex');

            fetch('seguimiento_tracking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'accion=cargar_tracking&codEnvio=' + encodeURIComponent(codEnvio)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        renderizarTimeline(data.timeline, data.resumen);
                    } else {
                        document.getElementById('timelineContainer').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error al cargar el historial</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('timelineContainer').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error al cargar los datos</p></div>';
                });
        }

        function renderizarTimeline(timeline, resumen) {
            const container = document.getElementById('timelineContainer');
            container.innerHTML = '';

            // Actualizar resumen
            document.getElementById('codEnvioTracking').textContent = resumen.codEnvio;
            document.getElementById('porcentajeComplecion').textContent = resumen.porcentajeComplecion + '%';
            document.getElementById('barraProgreso').style.width = resumen.porcentajeComplecion + '%';

            // Renderizar timeline
            timeline.forEach((evento, index) => {
                const isCompleted = evento.estado === 'completado';
                const iconClass = getIcono(evento.paso);
                const colorClase = isCompleted ? 'bg-green-100 border-green-300' : 'bg-yellow-100 border-yellow-300';
                const colorIcono = isCompleted ? 'bg-green-500' : 'bg-yellow-500';

                let html = `
                    <div class="relative">
                        <div class="flex gap-4">
                            <!-- Línea vertical -->
                            <div class="flex flex-col items-center">
                                <div class="${colorIcono} rounded-full w-9 h-9 flex items-center justify-center text-white shadow">
                                    <i class="fas ${iconClass} text-sm"></i>
                                </div>
                                ${index < timeline.length - 1
                        ? '<div class="w-0.5 h-12 bg-gray-300 my-1"></div>'
                        : ''
                    }
                            </div>

                            <!-- Contenido -->
                            <div class="flex-1 pt-1">
                                <div class="p-3 ${colorClase} border border-opacity-30 rounded-lg">
                                    <div class="flex justify-between items-start gap-3">
                                        <div>
                                            <h3 class="font-bold text-gray-800 text-sm leading-tight">
                                                ${evento.titulo}
                                            </h3>
                                            <p class="text-xs text-gray-600 mt-0.5 leading-snug">
                                                ${evento.descripcion}
                                            </p>
                                        </div>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold
                                            ${isCompleted ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800'}">
                                            ${isCompleted ? 'Completado' : 'Pendiente'}
                                        </span>
                                    </div>

                                    <!-- Detalles -->
                                    <div class="grid grid-cols-3 gap-2 mt-2 text-xs border-t border-opacity-20 border-gray-400 pt-2">
                    `;

                // Agregar detalles
                for (const [clave, valor] of Object.entries(evento.detalles)) {
                    html += `
                <div>
                    <p class="text-gray-600 font-semibold leading-tight">${clave}</p>
                    <p class="text-gray-800 leading-tight">${valor}</p>
                </div>
            `;
                }

                html += `
                            </div>

                            <!-- Meta información -->
                            <div class="flex justify-between items-center mt-2 text-[11px] text-gray-600 border-t border-opacity-20 border-gray-400 pt-2">
                                <div>
                                    <i class="fas fa-user-circle mr-1"></i>
                                    <strong>${evento.usuario}</strong>
                                </div>
                                <div>
                                    <i class="fas fa-calendar mr-1"></i>
                                    ${evento.fecha ? new Date(evento.fecha).toLocaleString('es-PE') : 'Sin fecha'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

                container.innerHTML += html;
            });
        }


        function getIcono(paso) {
            const iconos = {
                1: 'fa-file-invoice',
                2: 'fa-flask',
                3: 'fa-microscope',
                4: 'fa-chart-bar',
                5: 'fa-check-circle'
            };
            return iconos[paso] || 'fa-circle';
        }

        function cerrarModalTracking() {
            document.getElementById('modalTracking').classList.add('hidden');
            document.getElementById('modalTracking').classList.remove('flex');
        }

        // Cerrar al hacer clic fuera
        document.getElementById('modalTracking').addEventListener('click', function (e) {
            if (e.target === this) {
                cerrarModalTracking();
            }
        });


        function aplicarFiltros() {
            const fechaInicio = document.getElementById('filtroFechaInicio').value;
            const fechaFin = document.getElementById('filtroFechaFin').value;
            const estado = document.getElementById('filtroEstado').value.toLowerCase();

            const filas = document.querySelectorAll('#tablaResultados tbody tr');

            filas.forEach(fila => {
                const fechaFila = fila.children[3].innerText.trim(); // fecToma
                const estadoFila = fila.children[10].innerText.trim().toLowerCase();

                let mostrar = true;

                if (fechaInicio && fechaFila < fechaInicio) {
                    mostrar = false;
                }

                if (fechaFin && fechaFila > fechaFin) {
                    mostrar = false;
                }

                if (estado && estadoFila !== estado) {
                    mostrar = false;
                }

                fila.style.display = mostrar ? '' : 'none';
            });
        }

        function limpiarFiltros() {
            document.getElementById('filtroFechaInicio').value = '';
            document.getElementById('filtroFechaFin').value = '';
            document.getElementById('filtroEstado').value = '';

            document.querySelectorAll('#tablaResultados tbody tr')
                .forEach(fila => fila.style.display = '');
        }
    </script>

    <script>
        let archivoActual = '';

        function exportarReporteExcel() {
            window.location.href = "exportar_excel_resultados.php";
        }

        function previsualizarPDF(ruta) {
            archivoActual = ruta;

            document.getElementById('iframePDF').src = ruta;
            document.getElementById('modalPDF').classList.remove('hidden');
        }

        function previsualizarOffice(ruta) {
            archivoActual = ruta;

            const viewer =
                'https://docs.google.com/gview?url=' +
                encodeURIComponent(ruta) +
                '&embedded=true';

            document.getElementById('iframePDF').src = viewer;
            document.getElementById('modalPDF').classList.remove('hidden');
        }

        function cerrarModalPDF() {
            document.getElementById('modalPDF').classList.add('hidden');
            document.getElementById('iframePDF').src = '';
            archivoActual = '';
        }

        function descargarArchivo() {
            if (!archivoActual) return;

            // Fuerza descarga en nueva pestaña
            window.open(archivoActual, '_blank');
        }


        // Dropdown Granja
        const dropdownBtn = document.getElementById('dropdownGranjaBtn');
        const dropdownMenu = document.getElementById('dropdownGranjaMenu');
        const dropdownText = document.getElementById('dropdownGranjaText');
        const checkboxes = dropdownMenu.querySelectorAll('input[type="checkbox"]');

        dropdownBtn.addEventListener('click', () => {
            dropdownMenu.classList.toggle('hidden');
        });

        // Actualizar texto del botón según selección
        function updateGranjaText() {
            const selected = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.parentElement.textContent.trim());

            if (selected.length === 0) {
                dropdownText.textContent = "Seleccionar granjas...";
                dropdownText.classList.add('text-gray-500');
            } else if (selected.length === 1) {
                dropdownText.textContent = selected[0];
                dropdownText.classList.remove('text-gray-500');
            } else {
                dropdownText.textContent = `${selected.length} granjas seleccionadas`;
                dropdownText.classList.remove('text-gray-500');
            }
        }

        // Escuchar cambios en checkboxes
        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateGranjaText);
        });

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.add('hidden');
            }
        });
    </script>

    <script>
        let currentSolicitudCount = 0;
        document.addEventListener('click', function (e) {
            if (e.target.closest('.btn-editar')) {
                const codEnvio = e.target.closest('.btn-editar').dataset.codenvio;
                editarRegistro(codEnvio); // ahora sí, aunque esté en un bloque
            }

            if (e.target.closest('.btn-borrar')) {
                const codEnvio = e.target.closest('.btn-borrar').dataset.codenvio;
                borrarRegistros(codEnvio);
            }
        });
        function editarRegistro(codEnvio) {

            $('#modalEditarEnvio').modal('show');
            $('#tablaSolicitudes').empty();

            // 1. Cargar cabecera
            fetch(`get_cabecera_envio.php?codEnvio=${encodeURIComponent(codEnvio)}`)
                .then(res => res.json())
                .then(cab => {
                    if (cab.error) throw new Error(cab.error);

                    document.getElementById('codigoEnvio').value = cab.codEnvio;
                    document.getElementById('fechaEnvio').value = cab.fecEnvio;
                    document.getElementById('horaEnvio').value = cab.horaEnvio;
                    document.getElementById('laboratorio').value = cab.codLab;
                    document.getElementById('empresa_transporte').value = cab.codEmpTrans;
                    document.getElementById('usuario_registrador').value = cab.usuarioRegistrador;
                    document.getElementById('usuario_responsable').value = cab.usuarioResponsable;
                    document.getElementById('autorizado_por').value = cab.autorizadoPor;
                })
                .catch(err => {
                    console.error(err);
                    alert('Error al cargar la cabecera: ' + err.message);
                });

            // 2. Cargar detalles y contar posSolicitud únicos
            fetch(`get_detalles_envio.php?codEnvio=${encodeURIComponent(codEnvio)}`)
                .then(res => res.json())
                .then(det => {
                    if (det.error) throw new Error(det.error);

                    const total = det.length > 0 ? Math.max(...det.map(d => d.posSolicitud)) : 0;
                    document.getElementById('numeroSolicitudes').value = total;

                    // Agrupar por posSolicitud
                    const grupos = {};
                    det.forEach(item => {
                        if (!grupos[item.posSolicitud]) grupos[item.posSolicitud] = [];
                        grupos[item.posSolicitud].push(item);
                    });
                    console.log('grupos', grupos);
                    // Renderizar filas
                    renderizarFilasDeSolicitudes(grupos);
                    //const total = Object.keys(grupos).length;
                    currentSolicitudCount = total;
                    //document.getElementById('numeroSolicitudes').value = total;
                })
                .catch(err => {
                    console.error(err);
                    alert('Error al cargar los detalles: ' + err.message);
                });

        }
        function renderizarFilasDeSolicitudes(grupos) {
            const contenedor = document.getElementById('tablaSolicitudes');
            contenedor.innerHTML = '';

            // Cargar tipos de muestra una vez
            let tiposMuestraCache = null;

            async function getTiposMuestra() {
                if (tiposMuestraCache) return tiposMuestraCache;
                const res = await fetch('../../includes/get_tipos_muestra.php');
                tiposMuestraCache = await res.json();
                return tiposMuestraCache;
            }

            for (const pos in grupos) {
                const items = grupos[pos];
                const primerItem = items[0];

                const div = document.createElement('div');
                div.id = `fila-solicitud-${pos}`;
                div.className = 'border rounded-lg p-4 bg-gray-50';
                div.innerHTML = `
      <h6 class="font-bold mb-3">Solicitud #${pos}</h6>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
        <div>
          <label class="text-xs text-gray-600">Tipo de muestra</label>
          <select class="w-full text-sm px-2 py-1 border rounded tipo-muestra" data-pos="${pos}">
            <option value="">Seleccionar...</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-600">Cód. Referencia</label>
          <input type="text" class="w-full text-sm px-2 py-1 border rounded cod-ref" 
                 value="${primerItem.codRef}" data-pos="${pos}">
        </div>
        <div>
          <label class="text-xs text-gray-600">Núm. Muestras</label>
          <select class="w-full text-sm px-2 py-1 border rounded num-muestras" data-pos="${pos}">
            ${Array.from({ length: 30 }, (_, i) => `<option value="${i + 1}" ${primerItem.numMuestras == i + 1 ? 'selected' : ''}>${i + 1}</option>`).join('')}
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-600">Fecha Toma</label>
          <input type="date" class="w-full text-sm px-2 py-1 border rounded fecha-toma" 
                 value="${primerItem.fecToma}" data-pos="${pos}">
        </div>
      </div>
      <div class="mb-2">
        <label class="text-xs text-gray-600">Observaciones</label>
        <textarea class="w-full text-sm px-2 py-1 border rounded obs" data-pos="${pos}" rows="2">${primerItem.obs || ''}</textarea>
      </div>
      <button type="button" class="btn btn-sm btn-outline-primary ver-analisis" data-pos="${pos}">Ver Análisis</button>
      <div class="mt-3 analisis-container hidden" id="analisis-container-${pos}"></div>
    `;
                contenedor.appendChild(div);

                // Llenar tipo de muestra
                getTiposMuestra().then(tipos => {
                    const select = div.querySelector('.tipo-muestra');
                    tipos.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.codigo;
                        opt.textContent = t.nombre;
                        if (t.codigo == primerItem.codMuestra) opt.selected = true;
                        select.appendChild(opt);
                    });
                });

                // Evento "Ver Análisis"
                div.querySelector('.ver-analisis').addEventListener('click', async function () {
                    const posActual = this.dataset.pos;
                    const tipoId = div.querySelector('.tipo-muestra').value;
                    if (!tipoId) {
                        alert('Seleccione primero el tipo de muestra');
                        return;
                    }

                    const container = document.getElementById(`analisis-container-${posActual}`);
                    container.classList.remove('hidden');
                    container.innerHTML = '<p>Cargando análisis...</p>';

                    try {
                        const res = await fetch(`../../includes/get_config_muestra.php?tipo=${encodeURIComponent(tipoId)}`);
                        const data = await res.json();
                        if (data.error) throw new Error(data.error);

                        // Agrupar análisis por paquete
                        const analisisPorPaquete = {};
                        const sinPaquete = [];
                        data.analisis.forEach(a => {
                            if (a.paquete) {
                                if (!analisisPorPaquete[a.paquete]) analisisPorPaquete[a.paquete] = [];
                                analisisPorPaquete[a.paquete].push(a);
                            } else {
                                sinPaquete.push(a);
                            }
                        });

                        // Determinar qué análisis están ya seleccionados (del grupo actual)
                        const codigosSeleccionados = new Set(items.map(i => String(i.codAnalisis)));

                        let html = '';

                        // Paquetes
                        data.paquetes.forEach(p => {
                            const analisisDelPaquete = analisisPorPaquete[p.codigo] || [];
                            const todosSel = analisisDelPaquete.length > 0 && analisisDelPaquete.every(a => codigosSeleccionados.has(String(a.codigo)));
                            html += `
            <div class="mb-2">
              <div class="form-check">
                <input class="form-check-input paquete-check" type="checkbox"
                  data-pos="${posActual}" data-paquete="${p.codigo}" ${todosSel ? 'checked' : ''}>
                <label class="form-check-label fw-bold">${p.nombre}</label>
              </div>
              <div class="ms-3 mt-1">
                ${analisisDelPaquete.map(a => `
                  <div class="form-check form-check-inline me-2">
                    <input class="form-check-input analisis-check" type="checkbox"
                      data-pos="${posActual}" data-paquete="${p.codigo}" value="${a.codigo}"
                      ${codigosSeleccionados.has(String(a.codigo)) ? 'checked' : ''}>
                    <label class="form-check-label">${a.nombre}</label>
                  </div>
                `).join('')}
              </div>
            </div>
          `;
                        });

                        // Sin paquete
                        if (sinPaquete.length > 0) {
                            html += `<div class="mt-2 pt-2 border-t"><strong>Otros análisis:</strong> `;
                            html += sinPaquete.map(a => `
            <div class="form-check form-check-inline me-2">
              <input class="form-check-input analisis-check" type="checkbox"
                data-pos="${posActual}" value="${a.codigo}"
                ${codigosSeleccionados.has(String(a.codigo)) ? 'checked' : ''}>
              <label class="form-check-label">${a.nombre}</label>
            </div>
          `).join('');
                            html += '</div>';
                        }

                        container.innerHTML = html;

                        // Eventos checkboxes
                        container.querySelectorAll('.analisis-check').forEach(cb => {
                            cb.addEventListener('change', function () {
                                // Puedes opcionalmente guardar el estado en un objeto global si necesitas validación
                            });
                        });

                        container.querySelectorAll('.paquete-check').forEach(cb => {
                            cb.addEventListener('change', function () {
                                const paqueteId = this.dataset.paquete;
                                const pos = this.dataset.pos;
                                const checks = container.querySelectorAll(`.analisis-check[data-pos="${pos}"][data-paquete="${paqueteId}"]`);
                                checks.forEach(c => c.checked = this.checked);
                            });
                        });

                    } catch (err) {
                        container.innerHTML = `<div class="text-danger">Error: ${err.message}</div>`;
                    }
                });
            }
        }
        document.getElementById('btnGuardarEdicion').addEventListener('click', async function () {
            /*
                        const errores = [];
            
                        // === 1. Validar campos fijos de la cabecera ===
                        const fixedFields = [
                            { id: 'fechaEnvio', name: 'Fecha de envío' },
                            { id: 'horaEnvio', name: 'Hora de envío' },
                            { id: 'laboratorio', name: 'Laboratorio' },
                            { id: 'empresa_transporte', name: 'Empresa de transporte' },
                            { id: 'autorizado_por', name: 'Autorizado por' },
                            { id: 'usuario_responsable', name: 'Usuario responsable' }
                        ];
            
                        for (const { id, name } of fixedFields) {
                            const el = document.getElementById(id);
                            if (!el?.value?.trim()) {
                                errores.push(`- ${name} es obligatorio.`);
                            }
                        }
            
                        // Validar número de solicitudes (debe coincidir con filas visibles, pero al menos 1)
                        const filas = document.querySelectorAll('#tablaSolicitudes > div[id^="fila-solicitud-"]');
                        if (filas.length === 0) {
                             errores.push('- Debe haber al menos una solicitud.');
                         }
            
                        // === 2. Validar cada fila visible ===
                        const filasOrdenadas = Array.from(filas).sort((a, b) => {
                            const posA = parseInt(a.id.split('-').pop());
                            const posB = parseInt(b.id.split('-').pop());
                            return posA - posB;
                        });
            
                        filasOrdenadas.forEach((fila, idx) => {
                            const pos = parseInt(fila.id.split('-').pop());
                            const prefix = `Solicitud #${pos}:`;
            
                            const tipoMuestra = fila.querySelector('.tipo-muestra')?.value?.trim();
                            const fechaToma = fila.querySelector('.fecha-toma')?.value?.trim();
                            const codRef = fila.querySelector('.cod-ref')?.value?.trim();
            
                            if (!tipoMuestra) errores.push(`${prefix} Tipo de muestra es obligatorio.`);
                            if (!fechaToma) errores.push(`${prefix} Fecha de toma es obligatoria.`);
                            if (!codRef) errores.push(`${prefix} Código de referencia es obligatorio.`);
            
                            // Validar al menos un análisis seleccionado
                            const analisisCheckeds = fila.querySelectorAll('.analisis-check:checked');
                            if (analisisCheckeds.length === 0) {
                                errores.push(`${prefix} Debe seleccionar al menos un análisis.`);
                            }
                        });
            
                        // === 3. Mostrar errores si existen ===
                        if (errores.length > 0) {
                            alert("❌ Por favor, corrija los siguientes errores:\n\n" + errores.join('\n'));
                            return;
                        }
            */
            //////////////////////////////////////////////////////
            const codEnvio = document.getElementById('codigoEnvio').value;
            const numeroSolicitudes = parseInt(document.getElementById('numeroSolicitudes').value);

            // Validar
            if (numeroSolicitudes < 1) return alert('Número de solicitudes inválido');

            const formData = new FormData();

            // Campos fijos
            const fields = [
                'fechaEnvio', 'horaEnvio', 'laboratorio', 'empresa_transporte',
                'usuario_registrador', 'usuario_responsable', 'autorizado_por', 'codigoEnvio'
            ];
            fields.forEach(f => formData.append(f, document.getElementById(f)?.value || ''));

            // Para cada solicitud (posSolicitud = i+1)
            for (let i = 1; i <= numeroSolicitudes; i++) {
                const pos = i;
                const contenedor = document.querySelector(`.analisis-container[data-pos="${pos}"]`); // no usado aquí

                // Obtener valores de la fila
                const tipoMuestraEl = document.querySelector(`.tipo-muestra[data-pos="${pos}"]`);
                const nombreTipoMuestra = tipoMuestraEl?.selectedOptions[0]?.text || '';
                const codRefEl = document.querySelector(`.cod-ref[data-pos="${pos}"]`);
                const numMuestrasEl = document.querySelector(`.num-muestras[data-pos="${pos}"]`);
                const fechaTomaEl = document.querySelector(`.fecha-toma[data-pos="${pos}"]`);
                const obsEl = document.querySelector(`.obs[data-pos="${pos}"]`);

                formData.append(`fechaToma_${i}`, fechaTomaEl?.value || '');
                formData.append(`tipoMuestra_${i}`, tipoMuestraEl?.value || '');
                formData.append(`tipoMuestraNombre_${i}`, nombreTipoMuestra || '');
                formData.append(`codigoReferenciaValue_${i}`, codRefEl?.value || '');
                formData.append(`numeroMuestras_${i}`, numMuestrasEl?.value || '');
                formData.append(`observaciones_${i}`, obsEl?.value || '');

                // Recoger análisis seleccionados para esta posición
                const analisisSeleccionados = [];
                document.querySelectorAll(`.analisis-check[data-pos="${pos}"]:checked`).forEach(cb => {
                    // Buscar nombre y paquete desde el DOM o cache (aquí simplificado)
                    const label = cb.nextElementSibling?.textContent || '';
                    const paquete = cb.dataset.paquete || null;
                    analisisSeleccionados.push({
                        codigo: cb.value,
                        nombre: label,
                        paquete_codigo: paquete,
                        paquete_nombre: paquete ? document.querySelector(`.paquete-check[data-paquete="${paquete}"]`)?.nextElementSibling?.textContent : null
                    });
                });
                formData.append(`analisis_completos_${i}`, JSON.stringify(analisisSeleccionados));
            }

            // Enviar
            try {
                const res = await fetch('actualizar_muestra.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert('¡Cambios guardados exitosamente!');
                    $('#modalEditarEnvio').modal('hide');
                    if (table) table.ajax.reload();
                } else {
                    alert('Error: ' + (data.error || 'No se pudo guardar'));
                }
            } catch (err) {
                console.error(err);
                alert('Error de red al guardar');
            }
        });

        //manejo de numero de solicitudes
        document.getElementById('numeroSolicitudes').addEventListener('change', function () {
            const nuevoValor = parseInt(this.value) || 0;
            if (nuevoValor < 1) {
                this.value = 1;
                return;
            }
            if (nuevoValor > 20) {
                this.value = 20;
                return;
            }

            if (nuevoValor > currentSolicitudCount) {
                // Añadir filas nuevas
                for (let i = currentSolicitudCount + 1; i <= nuevoValor; i++) {
                    agregarFilaSolicitud(i);
                }
            } else if (nuevoValor < currentSolicitudCount) {
                // Eliminar filas desde el final
                for (let i = currentSolicitudCount; i > nuevoValor; i--) {
                    const fila = document.querySelector(`#fila-solicitud-${i}`);
                    if (fila) fila.remove();

                }
            }

            currentSolicitudCount = nuevoValor;
        });
        async function agregarFilaSolicitud(pos) {
            const contenedor = document.getElementById('tablaSolicitudes');

            // Obtener tipos de muestra (cacheado)
            let tiposMuestra = [];
            try {
                const res = await fetch('../../includes/get_tipos_muestra.php');
                tiposMuestra = await res.json();
            } catch (err) {
                console.error('Error al cargar tipos de muestra:', err);
                tiposMuestra = [];
            }

            const div = document.createElement('div');
            div.id = `fila-solicitud-${pos}`;
            div.className = 'border rounded-lg p-4 bg-gray-50';
            div.innerHTML = `
    <h6 class="font-bold mb-3">Solicitud #${pos}</h6>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
      <div>
        <label class="text-xs text-gray-600">Tipo de muestra</label>
        <select class="w-full text-sm px-2 py-1 border rounded tipo-muestra" data-pos="${pos}">
          <option value="">Seleccionar...</option>
          ${tiposMuestra.map(t => `<option value="${t.codigo}">${t.nombre}</option>`).join('')}
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-600">Cód. Referencia</label>
        <input type="text" class="w-full text-sm px-2 py-1 border rounded cod-ref" data-pos="${pos}">
      </div>
      <div>
        <label class="text-xs text-gray-600">Núm. Muestras</label>
        <select class="w-full text-sm px-2 py-1 border rounded num-muestras" data-pos="${pos}">
          ${Array.from({ length: 30 }, (_, i) => `<option value="${i + 1}">${i + 1}</option>`).join('')}
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-600">Fecha Toma</label>
        <input type="date" class="w-full text-sm px-2 py-1 border rounded fecha-toma" data-pos="${pos}">
      </div>
    </div>
    <div class="mb-2">
      <label class="text-xs text-gray-600">Observaciones</label>
      <textarea class="w-full text-sm px-2 py-1 border rounded obs" data-pos="${pos}" rows="2"></textarea>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary ver-analisis" data-pos="${pos}">Ver Análisis</button>
    <div class="mt-3 analisis-container hidden" id="analisis-container-${pos}"></div>
  `;
            contenedor.appendChild(div);

            // Evento para "Ver Análisis"
            div.querySelector('.ver-analisis').addEventListener('click', async function () {
                const posActual = this.dataset.pos;
                const tipoId = div.querySelector('.tipo-muestra').value;
                if (!tipoId) {
                    alert('Seleccione primero el tipo de muestra');
                    return;
                }

                const container = document.getElementById(`analisis-container-${posActual}`);
                container.classList.remove('hidden');
                container.innerHTML = '<p>Cargando análisis...</p>';

                try {
                    const res = await fetch(`../../includes/get_config_muestra.php?tipo=${encodeURIComponent(tipoId)}`);
                    const data = await res.json();
                    if (data.error) throw new Error(data.error);

                    const analisisPorPaquete = {};
                    const sinPaquete = [];
                    data.analisis.forEach(a => {
                        if (a.paquete) {
                            if (!analisisPorPaquete[a.paquete]) analisisPorPaquete[a.paquete] = [];
                            analisisPorPaquete[a.paquete].push(a);
                        } else {
                            sinPaquete.push(a);
                        }
                    });

                    // No hay análisis previos (es nueva), así que nada está seleccionado
                    let html = '';

                    data.paquetes.forEach(p => {
                        const analisisDelPaquete = analisisPorPaquete[p.codigo] || [];
                        html += `
          <div class="mb-2">
            <div class="form-check">
              <input class="form-check-input paquete-check" type="checkbox"
                data-pos="${posActual}" data-paquete="${p.codigo}">
              <label class="form-check-label fw-bold">${p.nombre}</label>
            </div>
            <div class="ms-3 mt-1">
              ${analisisDelPaquete.map(a => `
                <div class="form-check form-check-inline me-2">
                  <input class="form-check-input analisis-check" type="checkbox"
                    data-pos="${posActual}" data-paquete="${p.codigo}" value="${a.codigo}">
                  <label class="form-check-label">${a.nombre}</label>
                </div>
              `).join('')}
            </div>
          </div>
        `;
                    });

                    if (sinPaquete.length > 0) {
                        html += `<div class="mt-2 pt-2 border-t"><strong>Otros análisis:</strong> `;
                        html += sinPaquete.map(a => `
          <div class="form-check form-check-inline me-2">
            <input class="form-check-input analisis-check" type="checkbox"
              data-pos="${posActual}" value="${a.codigo}">
            <label class="form-check-label">${a.nombre}</label>
          </div>
        `).join('');
                        html += '</div>';
                    }

                    container.innerHTML = html;

                    container.querySelectorAll('.paquete-check').forEach(cb => {
                        cb.addEventListener('change', function () {
                            const paqueteId = this.dataset.paquete;
                            const pos = this.dataset.pos;
                            const checks = container.querySelectorAll(`.analisis-check[data-pos="${pos}"][data-paquete="${paqueteId}"]`);
                            checks.forEach(c => c.checked = this.checked);
                        });
                    });

                } catch (err) {
                    container.innerHTML = `<div class="text-danger">Error: ${err.message}</div>`;
                }
            });
        }


        document.getElementById('tablaSolicitudes').addEventListener('change', async function (e) {
            if (!e.target.classList.contains('tipo-muestra')) return;

            const selectTipo = e.target;
            const pos = selectTipo.dataset.pos;
            const tipoId = selectTipo.value;

            if (!tipoId) return;

            // Buscar el contenedor de análisis para esta posición
            const container = document.getElementById(`analisis-container-${pos}`);
            if (!container || !container.classList.contains('hidden')) {

                if (container) {
                    await cargarAnalisisParaPos(pos, tipoId, container);
                }
            }
        });
        async function cargarAnalisisParaPos(pos, tipoId, container) {
            container.innerHTML = '<p class="text-sm text-gray-500">Actualizando análisis...</p>';

            try {
                const res = await fetch(`../../includes/get_config_muestra.php?tipo=${encodeURIComponent(tipoId)}`);
                const data = await res.json();
                if (data.error) throw new Error(data.error);

                // Agrupar análisis por paquete
                const analisisPorPaquete = {};
                const sinPaquete = [];
                data.analisis.forEach(a => {
                    if (a.paquete) {
                        if (!analisisPorPaquete[a.paquete]) analisisPorPaquete[a.paquete] = [];
                        analisisPorPaquete[a.paquete].push(a);
                    } else {
                        sinPaquete.push(a);
                    }
                });

                // No hay análisis previos seleccionados (se resetea al cambiar tipo)
                let html = '';

                // Paquetes
                data.paquetes.forEach(p => {
                    const analisisDelPaquete = analisisPorPaquete[p.codigo] || [];
                    html += `
        <div class="mb-2">
          <div class="form-check">
            <input class="form-check-input paquete-check" type="checkbox"
              data-pos="${pos}" data-paquete="${p.codigo}">
            <label class="form-check-label fw-bold">${p.nombre}</label>
          </div>
          <div class="ms-3 mt-1">
            ${analisisDelPaquete.map(a => `
              <div class="form-check form-check-inline me-2">
                <input class="form-check-input analisis-check" type="checkbox"
                  data-pos="${pos}" data-paquete="${p.codigo}" value="${a.codigo}">
                <label class="form-check-label">${a.nombre}</label>
              </div>
            `).join('')}
          </div>
        </div>
      `;
                });

                // Análisis sin paquete
                if (sinPaquete.length > 0) {
                    html += `<div class="mt-2 pt-2 border-t"><strong>Otros análisis:</strong> `;
                    html += sinPaquete.map(a => `
        <div class="form-check form-check-inline me-2">
          <input class="form-check-input analisis-check" type="checkbox"
            data-pos="${pos}" value="${a.codigo}">
          <label class="form-check-label">${a.nombre}</label>
        </div>
      `).join('');
                    html += '</div>';
                }

                container.innerHTML = html;

                // Volver a enlazar eventos de checkboxes (paquetes y análisis)
                container.querySelectorAll('.analisis-check').forEach(cb => {
                    cb.addEventListener('change', function () {
                        // Si en el futuro necesitas guardar estado, puedes hacerlo aquí
                    });
                });

                container.querySelectorAll('.paquete-check').forEach(cb => {
                    cb.addEventListener('change', function () {
                        const paqueteId = this.dataset.paquete;
                        const pos = this.dataset.pos;
                        const checks = container.querySelectorAll(`.analisis-check[data-pos="${pos}"][data-paquete="${paqueteId}"]`);
                        checks.forEach(c => c.checked = this.checked);
                    });
                });

            } catch (err) {
                console.error('Error al recargar análisis:', err);
                container.innerHTML = `<div class="text-danger text-sm">Error al cargar análisis: ${err.message}</div>`;
            }
        }
    </script>

</body>

</html>