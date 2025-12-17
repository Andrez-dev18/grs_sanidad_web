<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

include_once '../conexion_grs_joya/conexion.php';
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

        // 2. DETALLE: conteos y fecha máxima del último resultado
        $sqlDet = "
            SELECT 
                COUNT(*) as totalAnalisis,
                SUM(CASE WHEN sd.estado_cuali = 'completado' THEN 1 ELSE 0 END) as cualiCompletados,
                SUM(CASE WHEN sd.estado_cuanti = 'completado' THEN 1 ELSE 0 END) as cuantiCompletados,
                MAX(ra.fechaHoraRegistro) as fechaUltimaResultado
            FROM san_fact_solicitud_det sd
            LEFT JOIN san_fact_resultado_analisis ra 
                ON sd.codEnvio = ra.codEnvio 
                AND sd.codAnalisis = ra.analisis_codigo
            WHERE sd.codEnvio = ?
        ";
        
        $stmtDet = $conexion->prepare($sqlDet);
        if (!$stmtDet) {
            echo json_encode(['success' => false, 'error' => 'Error prepare detalle: ' . $conexion->error]);
            exit;
        }
        $stmtDet->bind_param("s", $codEnvio);
        $stmtDet->execute();
        $resultDet = $stmtDet->get_result();
        $dataDet = $resultDet->fetch_assoc();

        // Valores por defecto
        $totalAnalisis = $dataDet['totalAnalisis'] ?? 0;
        $cualiCompletados = $dataDet['cualiCompletados'] ?? 0;
        $cuantiCompletados = $dataDet['cuantiCompletados'] ?? 0;
        $fechaUltimaResultado = $dataDet['fechaUltimaResultado'] ?? $dataCab['fechaRegistroCab'];

        // 3. USUARIO DEL ÚLTIMO RESULTADO (consulta separada)
        $sqlUsuario = "
            SELECT usuarioRegistrador
            FROM san_fact_resultado_analisis
            WHERE codEnvio = ?
            ORDER BY fechaHoraRegistro DESC
            LIMIT 1
        ";
        
        $stmtUsuario = $conexion->prepare($sqlUsuario);
        if ($stmtUsuario) {
            $stmtUsuario->bind_param("s", $codEnvio);
            $stmtUsuario->execute();
            $resultUsuario = $stmtUsuario->get_result();
            $rowUsuario = $resultUsuario->fetch_assoc();
            $ultimoUsuarioResultado = $rowUsuario['usuarioRegistrador'] ?? $dataCab['usuarioRegistrador'];
        } else {
            $ultimoUsuarioResultado = $dataCab['usuarioRegistrador'];
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

        // Paso 3
        $estadoCuali = ($cualiCompletados == $totalAnalisis && $totalAnalisis > 0) ? 'completado' : 'pendiente';
        $timeline[] = [
            'paso' => 3,
            'titulo' => 'Resultados Cualitativos',
            'descripcion' => 'Ingreso de resultados de análisis cualitativos',
            'fecha' => $fechaUltimaResultado,
            'usuario' => $ultimoUsuarioResultado,
            'estado' => $estadoCuali,
            'detalles' => [
                'Total Análisis' => $totalAnalisis,
                'Completados' => $cualiCompletados,
                'Pendientes' => $totalAnalisis - $cualiCompletados
            ]
        ];

        // Paso 4
        $estadoCuanti = ($cuantiCompletados == $totalAnalisis && $totalAnalisis > 0) ? 'completado' : 'pendiente';
        $timeline[] = [
            'paso' => 4,
            'titulo' => 'Resultados Cuantitativos',
            'descripcion' => 'Ingreso de resultados de análisis cuantitativos',
            'fecha' => $fechaUltimaResultado,
            'usuario' => $ultimoUsuarioResultado,
            'estado' => $estadoCuanti,
            'detalles' => [
                'Total Análisis' => $totalAnalisis,
                'Completados' => $cuantiCompletados,
                'Pendientes' => $totalAnalisis - $cuantiCompletados
            ]
        ];

        // Paso 5
        $todoCompleto = ($cualiCompletados == $totalAnalisis && $cuantiCompletados == $totalAnalisis && $totalAnalisis > 0);
        $timeline[] = [
            'paso' => 5,
            'titulo' => 'Envío Completado',
            'descripcion' => 'Todos los análisis han sido completados',
            'fecha' => $fechaUltimaResultado,
            'usuario' => $ultimoUsuarioResultado,
            'estado' => $todoCompleto ? 'completado' : 'pendiente',
            'detalles' => [
                'Código Envío' => $codEnvio,
                'Estado General' => $todoCompleto ? 'COMPLETADO' : 'EN PROCESO'
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
?>