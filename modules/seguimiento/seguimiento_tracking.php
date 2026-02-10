<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>var u="../../login.php";if(window.top!==window.self){window.top.location.href=u;}else{window.location.href=u;}</script>';
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'cargar_tracking') {
        $codEnvio = $_POST['codEnvio'];

        // 1. CABECERA
        $sqlCab = "
            SELECT 
                codEnvio,
                nomLab,
                nomEmpTrans,
                usuarioRegistrador,
                usuarioResponsable,
                autorizadoPor,
                fechaHoraRegistro as fechaRegistroCab
            FROM san_fact_solicitud_cab
            WHERE codEnvio = ?
            LIMIT 1
        ";

        $stmtCab = $conexion->prepare($sqlCab);
        if (!$stmtCab) {
            echo json_encode(['success' => false, 'error' => 'Error prepare cabecera']);
            exit;
        }
        $stmtCab->bind_param("s", $codEnvio);
        $stmtCab->execute();
        $resultCab = $stmtCab->get_result();
        $dataCab = $resultCab->fetch_assoc();

        if (!$dataCab) {
            echo json_encode(['success' => false, 'error' => 'No se encontró la solicitud']);
            exit;
        }

        // 2. DETALLE: conteos
        $sqlDet = "
            SELECT 
                COUNT(*) as totalAnalisis,
                SUM(CASE WHEN estado_cuali = 'completado' THEN 1 ELSE 0 END) as cualiCompletados,
                SUM(CASE WHEN estado_cuanti = 'completado' THEN 1 ELSE 0 END) as cuantiCompletados
            FROM san_fact_solicitud_det
            WHERE codEnvio = ?
        ";

        $stmtDet = $conexion->prepare($sqlDet);
        if (!$stmtDet) {
            echo json_encode(['success' => false, 'error' => 'Error prepare conteo']);
            exit;
        }
        $stmtDet->bind_param("s", $codEnvio);
        $stmtDet->execute();
        $resultDet = $stmtDet->get_result();
        $dataDet = $resultDet->fetch_assoc();

        $totalAnalisis = $dataDet['totalAnalisis'] ?? 0;
        $cualiCompletados = $dataDet['cualiCompletados'] ?? 0;
        $cuantiCompletados = $dataDet['cuantiCompletados'] ?? 0;

        // 3. Fecha y usuario último resultado CUALITATIVO
        $sqlCuali = "
            SELECT fechaHoraRegistro, usuarioRegistrador
            FROM san_fact_resultado_analisis
            WHERE codEnvio = ?
            ORDER BY fechaHoraRegistro DESC
            LIMIT 1
        ";
        $stmtCuali = $conexion->prepare($sqlCuali);
        $fechaCuali = $dataCab['fechaRegistroCab'];
        $usuarioCuali = $dataCab['usuarioRegistrador'];
        if ($stmtCuali) {
            $stmtCuali->bind_param("s", $codEnvio);
            $stmtCuali->execute();
            $resCuali = $stmtCuali->get_result();
            if ($row = $resCuali->fetch_assoc()) {
                $fechaCuali = $row['fechaHoraRegistro'];
                $usuarioCuali = $row['usuarioRegistrador'] ?? $usuarioCuali;
            }
        }

        // 4. Fecha y usuario último resultado CUANTITATIVO (de san_analisis_pollo_bb_adulto)
        $sqlCuanti = "
            SELECT fechaHoraRegistro, usuario_registro as usuarioRegistrador
            FROM san_analisis_pollo_bb_adulto
            WHERE codigo_envio = ?
            ORDER BY fechaHoraRegistro DESC
            LIMIT 1
        ";
        $stmtCuanti = $conexion->prepare($sqlCuanti);
        $fechaCuanti = $dataCab['fechaRegistroCab'];
        $usuarioCuanti = $dataCab['usuarioRegistrador'];
        if ($stmtCuanti) {
            $stmtCuanti->bind_param("s", $codEnvio);
            $stmtCuanti->execute();
            $resCuanti = $stmtCuanti->get_result();
            if ($row = $resCuanti->fetch_assoc()) {
                $fechaCuanti = $row['fechaHoraRegistro'];
                $usuarioCuanti = $row['usuarioRegistrador'] ?? $usuarioCuanti;
            }
        }

        // Timeline
        $timeline = [];

        // Paso 1
        $timeline[] = [
            'paso' => 1,
            'titulo' => 'Solicitud Registrada',
            'descripcion' => 'Solicitud creada en el sistema',
            'fecha' => $dataCab['fechaRegistroCab'],
            'usuario' => $dataCab['usuarioRegistrador'],
            'estado' => 'completado',
            'detalles' => [
                'Laboratorio' => $dataCab['nomLab'] ?? 'N/A',
                'Empresa Transporte' => $dataCab['nomEmpTrans'] ?? 'N/A',
                'Responsable' => $dataCab['usuarioResponsable'] ?? 'N/A',
                'Autorizado Por' => $dataCab['autorizadoPor'] ?? 'N/A'
            ]
        ];

        // Paso 2
        $timeline[] = [
            'paso' => 2,
            'titulo' => 'Análisis Solicitados',
            'descripcion' => "Se ingresaron $totalAnalisis solicitudes de análisis",
            'fecha' => $dataCab['fechaRegistroCab'],
            'usuario' => $dataCab['usuarioRegistrador'],
            'estado' => 'completado',
            'detalles' => ['Total de Análisis' => $totalAnalisis]
        ];

        // Paso 3: Resultados Cualitativos
        $estadoCuali = ($cualiCompletados == $totalAnalisis && $totalAnalisis > 0) ? 'completado' : 'pendiente';
        $timeline[] = [
            'paso' => 3,
            'titulo' => 'Resultados Cualitativos',
            'descripcion' => 'Ingreso de resultados de análisis cualitativos',
            'fecha' => $fechaCuali,
            'usuario' => $usuarioCuali,
            'estado' => $estadoCuali,
            'detalles' => [
                'Total Análisis' => $totalAnalisis,
                'Completados' => $cualiCompletados,
                'Pendientes' => $totalAnalisis - $cualiCompletados
            ]
        ];

        // Paso 4: Resultados Cuantitativos
        $estadoCuanti = ($cuantiCompletados == $totalAnalisis && $totalAnalisis > 0) ? 'completado' : 'pendiente';
        $timeline[] = [
            'paso' => 4,
            'titulo' => 'Resultados Cuantitativos',
            'descripcion' => 'Ingreso de resultados de análisis cuantitativos',
            'fecha' => $fechaCuanti,
            'usuario' => $usuarioCuanti,
            'estado' => $estadoCuanti,
            'detalles' => [
                'Total Análisis' => $totalAnalisis,
                'Completados' => $cuantiCompletados,
                'Pendientes' => $totalAnalisis - $cuantiCompletados
            ]
        ];

        // Paso 5: Dinámico según si está realmente completado o no
        $todoCompleto = ($cualiCompletados == $totalAnalisis && $cuantiCompletados == $totalAnalisis && $totalAnalisis > 0);

        // Fecha y usuario del último avance real (el más reciente entre cuali y cuanti)
        $fechaFinal = max(strtotime($fechaCuali), strtotime($fechaCuanti));
        $fechaFinal = date('Y-m-d H:i:s', $fechaFinal);
        $usuarioFinal = (strtotime($fechaCuanti) > strtotime($fechaCuali)) ? $usuarioCuanti : $usuarioCuali;

        // Título y descripción que cambian según el estado real
        $tituloPaso5 = $todoCompleto ? 'Envío Completado' : 'Pendiente de Completar';
        $descripcionPaso5 = $todoCompleto
            ? '¡Todos los análisis han sido completados exitosamente!'
            : 'Pendiente ingresar los resultados faltantes para completar el envío';

        $timeline[] = [
            'paso' => 5,
            'titulo' => $tituloPaso5,
            'descripcion' => $descripcionPaso5,
            'fecha' => $fechaFinal,
            'usuario' => $usuarioFinal,
            'estado' => $todoCompleto ? 'completado' : 'pendiente',
            'detalles' => [
                'Código Envío' => $codEnvio,
                'Estado General' => $todoCompleto ? 'COMPLETADO' : 'EN PROCESO',
                'Última acción por' => $usuarioFinal
            ]
        ];

        // Porcentaje
        $porcentaje = ($totalAnalisis > 0)
            ? round((($cualiCompletados + $cuantiCompletados) / ($totalAnalisis * 2)) * 100)
            : 0;

        echo json_encode([
            'success' => true,
            'timeline' => $timeline,
            'resumen' => [
                'codEnvio' => $codEnvio,
                'totalAnalisis' => $totalAnalisis,
                'cualiCompletados' => $cualiCompletados,
                'cuantiCompletados' => $cuantiCompletados,
                'porcentajeComplecion' => $porcentaje
            ]
        ]);

        exit;
    }
}
