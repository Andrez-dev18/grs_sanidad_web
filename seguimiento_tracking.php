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
        // Contar cada análisis como registro individual
        $sqlDet = "
            SELECT 
                COUNT(*) as totalAnalisis,
                SUM(CASE WHEN estado_cuali = 'completado' THEN 1 ELSE 0 END) as cualiCompletados,
                SUM(CASE WHEN estado_cuanti = 'completado' THEN 1 ELSE 0 END) as cuantiCompletados,
                MAX(fecToma) as fechaUltimaActualizacion
            FROM san_fact_solicitud_det
            WHERE codEnvio = ?
            GROUP BY codEnvio
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
        
        // Si no hay resultados, crear array vacío
        if (!$dataDet) {
            $dataDet = array(
                'totalAnalisis' => 0,
                'cualiCompletados' => 0,
                'cuantiCompletados' => 0,
                'fechaUltimaActualizacion' => $dataCab['fechaRegistroCab']
            );
        }

        // No consultamos a otras tablas por ahora
        $dataCuali = array();
        $dataCuanti = array();

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
        $estadoCuali = ($cualiCompletados == $totalAnalisis && $totalAnalisis > 0) ? 'completado' : 'pendiente';
        
        $timeline[] = array(
            'paso' => 3,
            'titulo' => 'Resultados Cualitativos',
            'descripcion' => 'Ingreso de resultados de análisis cualitativos',
            'fecha' => $dataDet['fechaUltimaActualizacion'] ?? $dataCab['fechaRegistroCab'],
            'usuario' => $dataCab['usuarioRegistrador'],
            'estado' => $estadoCuali,
            'detalles' => array(
                'Total Análisis' => $totalAnalisis,
                'Completados' => $cualiCompletados,
                'Pendientes' => $totalAnalisis - $cualiCompletados
            )
        );

        // Evento 4: Resultados Cuantitativos
        $cuantiCompletados = $dataDet['cuantiCompletados'] ?? 0;
        $estadoCuanti = ($cuantiCompletados == $totalAnalisis && $totalAnalisis > 0) ? 'completado' : 'pendiente';
        
        $timeline[] = array(
            'paso' => 4,
            'titulo' => 'Resultados Cuantitativos',
            'descripcion' => 'Ingreso de resultados de análisis cuantitativos',
            'fecha' => $dataDet['fechaUltimaActualizacion'] ?? $dataCab['fechaRegistroCab'],
            'usuario' => $dataCab['usuarioRegistrador'],
            'estado' => $estadoCuanti,
            'detalles' => array(
                'Total Análisis' => $totalAnalisis,
                'Completados' => $cuantiCompletados,
                'Pendientes' => $totalAnalisis - $cuantiCompletados
            )
        );

        // Evento 5: Proceso Completo
        $todoCompleto = ($cualiCompletados > 0 && 
                        $cuantiCompletados > 0 &&
                        $cualiCompletados == $totalAnalisis && 
                        $cuantiCompletados == $totalAnalisis &&
                        $totalAnalisis > 0);
        
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
        // Contamos: completados cuali + completados cuanti / (total análisis * 2)
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