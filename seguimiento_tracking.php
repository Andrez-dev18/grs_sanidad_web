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

        // 1. DATOS DE LA SOLICITUD CABECERA
        $sqlCab = "
            SELECT 
                codEnvio,
                fecEnvio,
                horaEnvio,
                nomLab,
                nomEmpTrans,
                usuarioRegistrador,
                usuarioResponsable,
                autorizadoPor,
                fechaHoraRegistro as fechaRegistroCab,
                estado as estadoCab
            FROM san_fact_solicitud_cab
            WHERE codEnvio = ?
            LIMIT 1
        ";
        
        $stmtCab = $conexion->prepare($sqlCab);
        if (!$stmtCab) {
            echo json_encode(array('success' => false, 'error' => 'Error en consulta cabecera: ' . $conexion->error));
            exit;
        }
        
        $stmtCab->bind_param("s", $codEnvio);
        $stmtCab->execute();
        $resultCab = $stmtCab->get_result();
        $dataCab = $resultCab->fetch_assoc();

        if (!$dataCab) {
            echo json_encode(array('success' => false, 'error' => 'No se encontró la solicitud'));
            exit;
        }

        // 2. DATOS DEL DETALLE (SOLICITUD DE ANÁLISIS)
        $sqlDet = "
            SELECT 
                COUNT(*) as totalAnalisis,
                SUM(CASE WHEN estado_cuali = 'completado' THEN 1 ELSE 0 END) as cualiCompletados,
                SUM(CASE WHEN estado_cuanti = 'completado' THEN 1 ELSE 0 END) as cuantiCompletados,
                MAX(fecToma) as fechaUltimaActualizacion
            FROM san_fact_solicitud_det
            WHERE codEnvio = ?
        ";
        
        $stmtDet = $conexion->prepare($sqlDet);
        if (!$stmtDet) {
            echo json_encode(array('success' => false, 'error' => 'Error en consulta detalle: ' . $conexion->error));
            exit;
        }
        
        $stmtDet->bind_param("s", $codEnvio);
        $stmtDet->execute();
        $resultDet = $stmtDet->get_result();
        $dataDet = $resultDet->fetch_assoc();

        // 3. DATOS DE RESULTADOS CUALITATIVOS
        $sqlCuali = "
            SELECT 
                COUNT(*) as totalCuali,
                MAX(fechaHoraRegistro) as ultimaFechaCuali,
                GROUP_CONCAT(DISTINCT usuarioRegistrador SEPARATOR ', ') as usuariosCuali
            FROM san_fact_resultado_analisis
            WHERE codEnvio = ?
        ";
        
        $stmtCuali = $conexion->prepare($sqlCuali);
        if (!$stmtCuali) {
            echo json_encode(array('success' => false, 'error' => 'Error en consulta cualitativos: ' . $conexion->error));
            exit;
        }
        
        $stmtCuali->bind_param("s", $codEnvio);
        $stmtCuali->execute();
        $resultCuali = $stmtCuali->get_result();
        $dataCuali = $resultCuali->fetch_assoc();

        // 4. DATOS DE RESULTADOS CUANTITATIVOS
        $sqlCuanti = "
            SELECT 
                COUNT(*) as totalCuanti,
                MAX(fecha_solicitud) as ultimaFechaCuanti,
                GROUP_CONCAT(DISTINCT usuario_registro SEPARATOR ', ') as usuariosCuanti,
                COUNT(CASE WHEN estado = 'COMPLETADO' THEN 1 END) as cuantiFinalizados
            FROM san_analisis_pollo_bb_adulto
            WHERE codigo_envio = ?
        ";
        
        $stmtCuanti = $conexion->prepare($sqlCuanti);
        if (!$stmtCuanti) {
            echo json_encode(array('success' => false, 'error' => 'Error en consulta cuantitativos: ' . $conexion->error));
            exit;
        }
        
        $stmtCuanti->bind_param("s", $codEnvio);
        $stmtCuanti->execute();
        $resultCuanti = $stmtCuanti->get_result();
        $dataCuanti = $resultCuanti->fetch_assoc();

        // Construir respuesta JSON con el timeline
        $timeline = array();

        // Evento 1: Solicitud Registrada
        $timeline[] = array(
            'paso' => 1,
            'titulo' => 'Solicitud Registrada',
            'descripcion' => 'Solicitud creada en el sistema',
            'fecha' => $dataCab['fechaRegistroCab'],
            'usuario' => $dataCab['usuarioRegistrador'],
            'estado' => 'completado',
            'detalles' => array(
                'Laboratorio' => $dataCab['nomLab'] ?? 'N/A',
                'Empresa Transporte' => $dataCab['nomEmpTrans'] ?? 'N/A',
                'Responsable' => $dataCab['usuarioResponsable'] ?? 'N/A',
                'Autorizado Por' => $dataCab['autorizadoPor'] ?? 'N/A'
            )
        );

        // Evento 2: Solicitudes de Análisis Ingresadas
        $timeline[] = array(
            'paso' => 2,
            'titulo' => 'Análisis Solicitados',
            'descripcion' => 'Se ingresaron ' . ($dataDet['totalAnalisis'] ?? 0) . ' solicitudes de análisis',
            'fecha' => $dataCab['fechaRegistroCab'],
            'usuario' => $dataCab['usuarioRegistrador'],
            'estado' => 'completado',
            'detalles' => array(
                'Total de Análisis' => $dataDet['totalAnalisis'] ?? 0,
                'Pendientes' => ($dataDet['totalAnalisis'] ?? 0) - ($dataDet['cualiCompletados'] ?? 0)
            )
        );

        // Evento 3: Resultados Cualitativos
        $cualiCompletados = $dataDet['cualiCompletados'] ?? 0;
        $totalAnalisis = $dataDet['totalAnalisis'] ?? 0;
        $estadoCuali = ($cualiCompletados > 0 && $cualiCompletados == $totalAnalisis) ? 'completado' : 'pendiente';
        
        $timeline[] = array(
            'paso' => 3,
            'titulo' => 'Resultados Cualitativos',
            'descripcion' => 'Ingreso de resultados de análisis cualitativos',
            'fecha' => $dataDet['fechaUltimaActualizacion'] ?? $dataCab['fechaRegistroCab'],
            'usuario' => $dataCuali['usuariosCuali'] ?? 'Pendiente',
            'estado' => $estadoCuali,
            'detalles' => array(
                'Resultados Ingresados' => $dataCuali['totalCuali'] ?? 0,
                'Completados' => $cualiCompletados,
                'Pendientes' => $totalAnalisis - $cualiCompletados
            )
        );

        // Evento 4: Resultados Cuantitativos
        $cuantiCompletados = $dataDet['cuantiCompletados'] ?? 0;
        $estadoCuanti = ($cuantiCompletados > 0 && $cuantiCompletados == $totalAnalisis) ? 'completado' : 'pendiente';
        
        $timeline[] = array(
            'paso' => 4,
            'titulo' => 'Resultados Cuantitativos',
            'descripcion' => 'Ingreso de resultados de análisis cuantitativos',
            'fecha' => $dataDet['fechaUltimaActualizacion'] ?? $dataCab['fechaRegistroCab'],
            'usuario' => $dataCuanti['usuariosCuanti'] ?? 'Pendiente',
            'estado' => $estadoCuanti,
            'detalles' => array(
                'Análisis Registrados' => $dataCuanti['totalCuanti'] ?? 0,
                'Completados' => $dataCuanti['cuantiFinalizados'] ?? 0,
                'Pendientes' => ($dataCuanti['totalCuanti'] ?? 0) - ($dataCuanti['cuantiFinalizados'] ?? 0)
            )
        );

        // Evento 5: Proceso Completo
        $todoCompleto = ($cualiCompletados > 0 && 
                        $cuantiCompletados > 0 &&
                        $cualiCompletados == $totalAnalisis && 
                        $cuantiCompletados == $totalAnalisis);
        
        $timeline[] = array(
            'paso' => 5,
            'titulo' => 'Envío Completado',
            'descripcion' => 'Todos los análisis han sido completados',
            'fecha' => $dataDet['fechaUltimaActualizacion'] ?? null,
            'usuario' => 'Sistema',
            'estado' => $todoCompleto ? 'completado' : 'pendiente',
            'detalles' => array(
                'Código Envío' => $codEnvio,
                'Estado General' => $todoCompleto ? 'COMPLETADO' : 'EN PROCESO'
            )
        );

        // Calcular porcentaje de completación
        if ($totalAnalisis > 0) {
            $porcentaje = round((($cualiCompletados + $cuantiCompletados) / ($totalAnalisis * 2)) * 100);
        } else {
            $porcentaje = 0;
        }

        echo json_encode(array(
            'success' => true,
            'timeline' => $timeline,
            'resumen' => array(
                'codEnvio' => $codEnvio,
                'totalAnalisis' => $totalAnalisis,
                'cualiCompletados' => $cualiCompletados,
                'cuantiCompletados' => $cuantiCompletados,
                'porcentajeComplecion' => $porcentaje
            )
        ));

        exit;
    }
}
?>