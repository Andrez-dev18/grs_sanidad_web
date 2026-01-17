<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

@ini_set('upload_max_filesize', '50M');
@ini_set('post_max_size', '50M');
@ini_set('max_execution_time', '300'); // 5 minutos
@ini_set('max_input_time', '300');
@ini_set('memory_limit', '256M');

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Lima');

ob_start();
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
ob_end_clean(); 

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$basePath = __DIR__ . '/';
$carpetaUploads = $basePath . '../../uploads/';
$carpetaNecropsias = $carpetaUploads . 'necropsias/';
$rutaRelativaBD = 'uploads/necropsias/';

if (!is_dir($carpetaNecropsias)) {
    mkdir($carpetaNecropsias, 0755, true);
}

$postMaxSize = ini_get('post_max_size');
$uploadMaxSize = ini_get('upload_max_filesize');
$postMaxSizeBytes = return_bytes($postMaxSize);
$contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;

function return_bytes($val) {
    $val = trim($val);
    if (empty($val)) return 0;
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
            // Fall through
        case 'm':
            $val *= 1024;
    
        case 'k':
            $val *= 1024;
            break;
        default:          
            break;
    }
    return $val;
}

if ($contentLength > 0 && $postMaxSizeBytes > 0 && $contentLength > $postMaxSizeBytes) {
    echo json_encode([
        'success' => false,
        'message' => 'El tamaño de los datos (' . number_format($contentLength / 1024 / 1024, 2) . ' MB) excede el límite permitido (' . $postMaxSize . ' / ' . number_format($postMaxSizeBytes / 1024 / 1024, 2) . ' MB). ' .
                     'Por favor, reduzca el número de imágenes o comprímalas más. ' .
                     'Si necesita enviar archivos más grandes, contacte al administrador del servidor para aumentar el límite post_max_size en php.ini o .htaccess.',
        'content_length' => $contentLength,
        'content_length_mb' => round($contentLength / 1024 / 1024, 2),
        'max_allowed' => $postMaxSizeBytes,
        'max_allowed_mb' => round($postMaxSizeBytes / 1024 / 1024, 2),
        'current_php_limits' => [
            'post_max_size' => $postMaxSize,
            'upload_max_filesize' => $uploadMaxSize
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$phpInput = file_get_contents('php://input');
$phpInputLength = strlen($phpInput);

if ($contentLength > 0 && $phpInputLength > 0 && $phpInputLength < ($contentLength * 0.9)) {
    echo json_encode([
        'success' => false,
        'message' => 'Los datos fueron truncados porque exceden el límite de tamaño. Tamaño esperado: ' . number_format($contentLength / 1024 / 1024, 2) . ' MB, recibido: ' . number_format($phpInputLength / 1024 / 1024, 2) . ' MB. Límite actual: ' . $postMaxSize . '. Contacte al administrador para aumentar el límite.',
        'content_length' => $contentLength,
        'received_length' => $phpInputLength,
        'max_allowed' => $postMaxSizeBytes
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$dataJson = $_POST['data'] ?? null;

if (empty($dataJson)) {
    if (!empty($phpInput)) {
        $jsonStart = strpos($phpInput, '{"');
        if ($jsonStart !== false) {
            $jsonPart = substr($phpInput, $jsonStart);
            $jsonEnd = strrpos($jsonPart, '}');
            if ($jsonEnd !== false) {
                $dataJson = substr($jsonPart, 0, $jsonEnd + 1);
            }
        }
        
        if (empty($dataJson) && !empty($_POST['data'])) {
            $dataJson = $_POST['data'];
        }
    }
}

if (empty($dataJson)) {
    $filesCount = count($_FILES ?? []);
    if ($filesCount > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Se recibieron archivos (' . $filesCount . ') pero no se encontró el JSON de datos. Posible error en el formato del request.',
            'files_received' => $filesCount
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se recibieron datos. Content-Length: ' . $contentLength
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$input = json_decode($dataJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $dataLength = strlen($dataJson);
    if ($contentLength > 0 && $dataLength < ($contentLength * 0.9)) {
        echo json_encode([
            'success' => false,
            'message' => 'Los datos JSON fueron truncados. Tamaño esperado: ~' . number_format($contentLength / 1024 / 1024, 2) . ' MB, recibido: ' . number_format($dataLength / 1024 / 1024, 2) . ' MB. Por favor, reduzca el número de imágenes.',
            'content_length' => $contentLength,
            'received_length' => $dataLength,
            'json_error' => json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al decodificar JSON: ' . json_last_error_msg() . '. Longitud de datos recibidos: ' . number_format($dataLength / 1024 / 1024, 2) . ' MB',
            'json_error' => json_last_error_msg(),
            'data_length' => $dataLength
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if (!$input || !isset($input['necropcias']) || !is_array($input['necropcias']) || empty($input['necropcias'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos inválidos o incompletos. Se esperaba un array de "necropcias". Keys recibidos: ' . implode(', ', array_keys($input ?? []))
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$items = $input['necropcias'];
$insertados = 0;
$duplicados = 0;

$checkDuplicado = $conn->prepare("SELECT COUNT(*) FROM t_regnecropsia WHERE tuuid = ?");
if (!$checkDuplicado) {
    echo json_encode([
        'success' => false,
        'message' => 'Error prepare check duplicados: ' . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

$sqlInsert = "INSERT INTO t_regnecropsia (
    tuuid, tuser, tdate, ttime, tcencos, tgranja, tcampania, tedad, tgalpon, tnumreg, tfectra, diareg,
    tcodsistema, tsistema, tnivel, tparametro,
    tporcentaje1, tporcentaje2, tporcentaje3, tporcentaje4, tporcentaje5, tporcentajetotal,
    tobservacion, evidencia, tobs, tuser_trans, tdate_trans, ttime_trans, tidandroid, tid,
    tdiagpresuntivo, tfecreghorainicio, tfecreghorafin
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?
)";

$stmtInsert = $conn->prepare($sqlInsert);

if (!$stmtInsert) {
    echo json_encode([
        'success' => false,
        'message' => 'Error preparando consulta de inserción: ' . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    $checkDuplicado->close();
    $conn->close();
    exit;
}


$tidContador = 1; // Contador consecutivo para tid

error_log("Total de items a procesar: " . count($items));

foreach ($items as $itemIndex => $item) {
    error_log("Procesando item #" . ($itemIndex + 1));
    // === CABECERA ===
    $granja = $item['granja'] ?? '';
    $campania = $item['campania'] ?? '';
    $galpon = $item['galpon'] ?? '';
    $edad = $item['edad'] ?? '';
    $fectra = $item['fectra'] ?? '';
    $numreg = isset($item['numreg']) ? (int)$item['numreg'] : 0;
    $tcencos = $item['tcencos'] ?? '';
    $diagnosticoPresuntivo = $item['diagnostico_presuntivo'] ?? '';
    $tfecreghorainicio = $item['tfecreghorainicio'] ?? '';
    $tfecreghorafin = $item['tfecreghorafin'] ?? '';
    
    // === CAMPOS ADICIONALES DE LA APP ===
    $tuser = $item['tuser'] ?? 'app_movil';
    $tidandroid = $item['tidandroid'] ?? 'app_movil';
    $tuser_trans = $item['tuser_trans'] ?? $tuser;
    $tdate_trans = $item['tdate_trans'] ?? date('Y-m-d');
    $ttime_trans = $item['ttime_trans'] ?? date('H:i:s');
    
    // Validar y formatear fecha de transacción
    if (empty($tdate_trans) || $tdate_trans == '1000-01-01') {
        $tdate_trans = date('Y-m-d');
    }
    // Asegurar formato yyyy-mm-dd
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tdate_trans)) {
        $tdate_trans = date('Y-m-d');
    }
    
    // Validar y formatear tiempo de transacción (formato HH:MM:SS o HHMMSS)
    if (empty($ttime_trans) || $ttime_trans == '00:00:00') {
        $ttime_trans = date('H:i:s');
    } elseif (strlen($ttime_trans) == 6 && is_numeric($ttime_trans)) {
        // Convertir HHMMSS a HH:MM:SS
        $ttime_trans = substr($ttime_trans, 0, 2) . ':' . substr($ttime_trans, 2, 2) . ':' . substr($ttime_trans, 4, 2);
    }
    
    // Formatear fectra si viene en formato diferente
    if (!empty($fectra)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fectra)) {
            // Intentar convertir formato dd/mm/yyyy a yyyy-mm-dd
            $parts = explode('/', $fectra);
            if (count($parts) == 3) {
                $fectra = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            } else {
                $fectra = date('Y-m-d');
            }
        }
    } else {
        $fectra = date('Y-m-d');
    }
    
    // === AUTOMÁTICOS ===
    $tdate = date('Y-m-d');
    $ttime = date('H:i:s');
    $diareg = date('Y-m-d');

    // === REGISTROS DEL ITEM ===
    $registros = $item['registros'] ?? [];
    
    if (empty($registros) || !is_array($registros)) {
       
        continue; 
    }

    $registrosPorNivel = [];
    foreach ($registros as $reg) {
        $nivel = $reg['tnivel'] ?? '';
        if (!isset($registrosPorNivel[$nivel])) {
            $registrosPorNivel[$nivel] = [];
        }
        $registrosPorNivel[$nivel][] = $reg;
    }

    error_log("Procesando " . count($registros) . " registros del item #" . ($itemIndex + 1));
    
    // Procesar evidencias ANTES del loop de registros para tenerlas disponibles
    $evidenciasMetadata = $item['evidencias_metadata'] ?? [];
    $rutasEvidenciasPorNivel = [];
    
    foreach ($evidenciasMetadata as $evidenciaKey => $evidenciaInfo) {
        $nivel = $evidenciaInfo['nivel'] ?? '';
        $cantidad = (int)($evidenciaInfo['cantidad'] ?? 0);
        $rutasNivel = [];
        $normalizedEvidenciaKey = str_replace(' ', '_', $evidenciaKey);
        for ($i = 0; $i < $cantidad; $i++) {
            $fileKey = 'evidencia_' . $normalizedEvidenciaKey . '_' . $i;
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$fileKey];
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!$extension) $extension = 'jpg';
                
                $nombreArchivo = $granja . '_' . $galpon . '_' . $numreg . '_' . 
                                str_replace('-', '', $fectra) . '_' . 
                                strtolower(str_replace(' ', '_', $nivel)) . '_' . 
                                $i . '_' . uniqid() . '.' . $extension;

                $rutaFisica = $carpetaNecropsias . $nombreArchivo;
                if (move_uploaded_file($file['tmp_name'], $rutaFisica)) {
                    $rutasNivel[] = $rutaRelativaBD . $nombreArchivo;
                }
            }
        }
        if (!empty($rutasNivel)) {
            $rutasEvidenciasPorNivel[$nivel] = implode(',', $rutasNivel);
        }
    }
    
    foreach ($registros as $regIndex => $reg) {
        if (!isset($reg['tuuid']) || !isset($reg['tsistema']) || !isset($reg['tnivel']) || !isset($reg['tparametro'])) {
            error_log("Registro #" . ($regIndex + 1) . " inválido (faltan campos requeridos). Keys: " . implode(', ', array_keys($reg ?? [])));
            continue;
        }
        
        $tuuidRegistro = $reg['tuuid'] ?? '';
        $existeRegistro = 0;

        // Verificar si el registro ya existe (duplicado)
        if (!empty($tuuidRegistro)) {
            $checkDuplicado->bind_param("s", $tuuidRegistro);
            $checkDuplicado->execute();
            $checkDuplicado->bind_result($existeRegistro);
            $checkDuplicado->fetch();
            $checkDuplicado->free_result();

            if ($existeRegistro > 0) {
                $duplicados++;
                continue; // Saltar registro duplicado
            }
        }

        $obs = $reg['tobservacion'] ?? '';
        $tobs = substr($obs, 0, 255); 

      
        switch ($reg['tsistema']) {
            case 'SISTEMA INMUNOLOGICO':
            case 'SISTEMA INMUNOLÓGICO':
                $tcodsistema = 1;
                break;
            case 'SISTEMA DIGESTIVO':
                $tcodsistema = 2;
                break;
            case 'SISTEMA RESPIRATORIO':
                $tcodsistema = 3;
                break;
            case 'EVALUACION FISICA':
            case 'EVALUACIÓN FÍSICA':
                $tcodsistema = 4;
                break;
            default:
                $tcodsistema = 0;
        }

        // Obtener evidencia del nivel si existe
        $evidencia = '';
        if (isset($rutasEvidenciasPorNivel[$reg['tnivel'] ?? ''])) {
            $evidencia = $rutasEvidenciasPorNivel[$reg['tnivel']];
        }
        
        $tid = $tidContador;
        $tidContador++;

        $porcentaje1 = isset($reg['tporcentaje1']) ? (double)$reg['tporcentaje1'] : 0.0;
        $porcentaje2 = isset($reg['tporcentaje2']) ? (double)$reg['tporcentaje2'] : 0.0;
        $porcentaje3 = isset($reg['tporcentaje3']) ? (double)$reg['tporcentaje3'] : 0.0;
        $porcentaje4 = isset($reg['tporcentaje4']) ? (double)$reg['tporcentaje4'] : 0.0;
        $porcentaje5 = isset($reg['tporcentaje5']) ? (double)$reg['tporcentaje5'] : 0.0;
        $porcentajeTotal = isset($reg['tporcentajetotal']) ? (double)$reg['tporcentajetotal'] : 0.0;
        
        $tdate = date('Y-m-d'); 
        $diareg = date('Y-m-d'); 
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fectra)) {
            $fectra = date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tdate_trans)) {
            $tdate_trans = date('Y-m-d');
        }
        
       
        $tsistema = (string)($reg['tsistema'] ?? '');
        $tnivel = (string)($reg['tnivel'] ?? '');
        $tparametro = (string)($reg['tparametro'] ?? '');
      
        // Insertar nuevo registro
        $stmtInsert->bind_param(
                "sssssssssississsddddddsssssssisss",
                $tuuidRegistro,      // 1. tuuid: s
                $tuser,              // 2. tuser: s
                $tdate,              // 3. tdate: s
                $ttime,              // 4. ttime: s
                $tcencos,            // 5. tcencos: s
                $granja,             // 6. tgranja: s
                $campania,           // 7. tcampania: s
                $edad,               // 8. tedad: s
                $galpon,             // 9. tgalpon: s
                $numreg,             // 10. tnumreg: i
                $fectra,             // 11. tfectra: s
                $diareg,             // 12. diareg: s
                $tcodsistema,        // 13. tcodsistema: i
                $tsistema,           // 14. tsistema: s
                $tnivel,             // 15. tnivel: s
                $tparametro,         // 16. tparametro: s
                $porcentaje1,        // 17. tporcentaje1: d
                $porcentaje2,        // 18. tporcentaje2: d
                $porcentaje3,        // 19. tporcentaje3: d
                $porcentaje4,        // 20. tporcentaje4: d
                $porcentaje5,        // 21. tporcentaje5: d
                $porcentajeTotal,    // 22. tporcentajetotal: d
                $obs,                // 23. tobservacion: s
                $evidencia,          // 24. evidencia: s
                $tobs,               // 25. tobs: s
                $tuser_trans,        // 26. tuser_trans: s
                $tdate_trans,        // 27. tdate_trans: s
                $ttime_trans,        // 28. ttime_trans: s
                $tidandroid,         // 29. tidandroid: s
                $tid,                // 30. tid: i
                $diagnosticoPresuntivo, // 31. tdiagpresuntivo: s
                $tfecreghorainicio,  // 32. tfecreghorainicio: s
                $tfecreghorafin      // 33. tfecreghorafin: s
            );

        if ($stmtInsert->execute()) {
            $insertados++;
        } else {
            $errorMsg = "✗ Error al insertar registro con UUID $tuuidRegistro: " . $stmtInsert->error;
            error_log($errorMsg);
        }
    }
    
}

$stmtInsert->close();
$checkDuplicado->close();
$conn->close();


$respuesta = [
    "insertados" => $insertados,
    "duplicados" => $duplicados
];

$totalProcesados = $insertados;
error_log("RESUMEN: Insertados=$insertados, Duplicados=$duplicados, Total items procesados=" . count($items));

echo json_encode([
    "success" => $totalProcesados > 0,
    "message" => $totalProcesados > 0 
        ? "Datos procesados correctamente. Insertados: $insertados, Duplicados: $duplicados"
        : "No se procesaron registros. Verifique los logs para más detalles.",
    "detalle" => $respuesta
], JSON_UNESCAPED_UNICODE);
?>
