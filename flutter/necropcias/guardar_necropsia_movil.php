<?php
/**
 * IMPORTANTE: Para manejar imágenes grandes en PHP 7.2, configure estos valores:
 * 
 * OPCIÓN 1 - En php.ini (recomendado, funciona siempre):
 *   post_max_size = 50M
 *   upload_max_filesize = 50M
 *   max_execution_time = 300
 *   max_input_time = 300
 *   memory_limit = 256M
 * 
 * OPCIÓN 2 - En .htaccess (solo si usa mod_php, no PHP-FPM/CGI):
 *   php_value post_max_size 50M
 *   php_value upload_max_filesize 50M
 *   php_value max_execution_time 300
 *   php_value max_input_time 300
 *   php_value memory_limit 256M
 * 
 * NOTA IMPORTANTE PHP 7.2:
 * - ini_set() para post_max_size y upload_max_filesize NO funciona en tiempo de ejecución.
 * - Estos valores SOLO pueden establecerse en php.ini o .htaccess ANTES de que PHP procese el request.
 * - Si usas PHP-FPM o FastCGI, las directivas php_value en .htaccess NO funcionarán.
 * - En ese caso, DEBES configurarlos en php.ini o usar user.ini (PHP 7.2 soporta user.ini)
 * 
 * VERIFICACIÓN:
 * Este script loggea los límites actuales. Revisa los logs para confirmar que se aplicaron.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Intentar aumentar límites (puede no funcionar si están deshabilitados en php.ini)
// Estas configuraciones solo funcionan si el servidor lo permite
@ini_set('upload_max_filesize', '50M');
@ini_set('post_max_size', '50M');
@ini_set('max_execution_time', '300'); // 5 minutos
@ini_set('max_input_time', '300');
@ini_set('memory_limit', '256M');

// Log para debugging (verificar qué límites están activos en PHP 7.2)
error_log('=== PHP 7.2 LIMITS DEBUG ===');
error_log('PHP Version: ' . phpversion());
error_log('SAPI: ' . php_sapi_name()); // mod_php, fpm-fcgi, cgi, etc.
error_log('upload_max_filesize: ' . ini_get('upload_max_filesize'));
error_log('post_max_size: ' . ini_get('post_max_size'));
error_log('max_execution_time: ' . ini_get('max_execution_time'));
error_log('max_input_time: ' . ini_get('max_input_time'));
error_log('memory_limit: ' . ini_get('memory_limit'));
error_log('file_uploads: ' . (ini_get('file_uploads') ? 'On' : 'Off'));
error_log('max_file_uploads: ' . ini_get('max_file_uploads'));
error_log('================================');

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

// === RUTAS CORRECTAS DENTRO DEL PROYECTO ===
$basePath = __DIR__ . '/';
$carpetaUploads = $basePath . '../../uploads/';
$carpetaNecropsias = $carpetaUploads . 'necropsias/';
$rutaRelativaBD = 'uploads/necropsias/';

if (!is_dir($carpetaNecropsias)) {
    mkdir($carpetaNecropsias, 0755, true);
}

// Verificar tamaño del POST antes de procesar
$postMaxSize = ini_get('post_max_size');
$uploadMaxSize = ini_get('upload_max_filesize');
$postMaxSizeBytes = return_bytes($postMaxSize);
$contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;

// Log para debugging
error_log('Request Info - Content-Length: ' . $contentLength . ' bytes (' . number_format($contentLength / 1024 / 1024, 2) . ' MB)');
error_log('Request Info - post_max_size: ' . $postMaxSize . ' (' . number_format($postMaxSizeBytes / 1024 / 1024, 2) . ' MB)');
error_log('Request Info - upload_max_filesize: ' . $uploadMaxSize);
error_log('Request Info - $_POST count: ' . count($_POST ?? []));
error_log('Request Info - $_FILES count: ' . count($_FILES ?? []));

// Función auxiliar para convertir tamaño en formato PHP (como "50M") a bytes
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
            // Fall through
        case 'k':
            $val *= 1024;
            break;
        default:
            // Si es solo un número sin sufijo, ya está en bytes
            break;
    }
    return $val;
}

// Verificar si el tamaño excede el límite
// IMPORTANTE: Si el POST excede post_max_size, PHP puede truncar silenciosamente los datos
// Por eso es crítico configurar post_max_size en php.ini o .htaccess ANTES de que llegue el request
if ($contentLength > 0 && $postMaxSizeBytes > 0 && $contentLength > $postMaxSizeBytes) {
    // El tamaño del request excede el límite configurado
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

// Leer php://input una sola vez (solo puede leerse una vez)
$phpInput = file_get_contents('php://input');
$phpInputLength = strlen($phpInput);

// Verificar si el POST fue truncado (si Content-Length es mayor que lo recibido)
if ($contentLength > 0 && $phpInputLength > 0 && $phpInputLength < ($contentLength * 0.9)) {
    // Probablemente fue truncado (recibimos menos del 90% de lo esperado)
    echo json_encode([
        'success' => false,
        'message' => 'Los datos fueron truncados porque exceden el límite de tamaño. Tamaño esperado: ' . number_format($contentLength / 1024 / 1024, 2) . ' MB, recibido: ' . number_format($phpInputLength / 1024 / 1024, 2) . ' MB. Límite actual: ' . $postMaxSize . '. Contacte al administrador para aumentar el límite.',
        'content_length' => $contentLength,
        'received_length' => $phpInputLength,
        'max_allowed' => $postMaxSizeBytes
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener datos JSON
$dataJson = $_POST['data'] ?? null;

if (empty($dataJson)) {
    // Si no está en $_POST, puede venir en el cuerpo (para multipart/form-data)
    // Intentar parsear desde php://input si contiene JSON
    if (!empty($phpInput)) {
        // Si php://input es JSON directo, usarlo
        $jsonStart = strpos($phpInput, '{"');
        if ($jsonStart !== false) {
            // Intentar extraer JSON del input (puede venir después de multipart boundary)
            $jsonPart = substr($phpInput, $jsonStart);
            $jsonEnd = strrpos($jsonPart, '}');
            if ($jsonEnd !== false) {
                $dataJson = substr($jsonPart, 0, $jsonEnd + 1);
            }
        }
        
        // Si aún no hay JSON, puede estar en formato multipart/form-data
        // En ese caso, $_POST['data'] debería estar disponible
        if (empty($dataJson) && !empty($_POST['data'])) {
            $dataJson = $_POST['data'];
        }
    }
}

if (empty($dataJson)) {
    // Verificar si hay archivos pero no hay JSON (error en el envío)
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

// Intentar decodificar JSON
$input = json_decode($dataJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // Si hay un error de JSON, verificar si el contenido fue truncado
    $dataLength = strlen($dataJson);
    if ($contentLength > 0 && $dataLength < ($contentLength * 0.9)) {
        // Probablemente fue truncado
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
$actualizados = 0;

// === PREPARAR CONSULTA DE VERIFICACIÓN DE DUPLICADOS (por UUID de cada registro) ===
$checkDuplicado = $conn->prepare("SELECT COUNT(*) FROM t_regnecropsia WHERE tuuid = ?");
if (!$checkDuplicado) {
    echo json_encode([
        'success' => false,
        'message' => 'Error prepare check duplicados: ' . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

// === PREPARAR CONSULTA DE INSERCIÓN ===
$sqlInsert = "INSERT INTO t_regnecropsia (
    tuuid, tuser, tdate, ttime, tcencos, tgranja, tcampania, tedad, tgalpon, tnumreg, tfectra, diareg,
    tcodsistema, tsistema, tnivel, tparametro,
    tporcentaje1, tporcentaje2, tporcentaje3, tporcentaje4, tporcentaje5, tporcentajetotal,
    tobservacion, evidencia, tobs, tuser_trans, tdate_trans, ttime_trans, tidandroid, tid
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?
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

// === PREPARAR CONSULTA DE ACTUALIZACIÓN ===
$sqlUpdate = "UPDATE t_regnecropsia SET
    tuser = ?, tdate = ?, ttime = ?, tcencos = ?, tgranja = ?, tcampania = ?, tedad = ?, tgalpon = ?, tnumreg = ?, tfectra = ?, diareg = ?,
    tcodsistema = ?, tsistema = ?, tnivel = ?, tparametro = ?,
    tporcentaje1 = ?, tporcentaje2 = ?, tporcentaje3 = ?, tporcentaje4 = ?, tporcentaje5 = ?, tporcentajetotal = ?,
    tobservacion = ?, evidencia = ?, tobs = ?, tuser_trans = ?, tdate_trans = ?, ttime_trans = ?, tidandroid = ?
WHERE tuuid = ?";

$stmtUpdate = $conn->prepare($sqlUpdate);

if (!$stmtUpdate) {
    echo json_encode([
        'success' => false,
        'message' => 'Error preparando consulta de actualización: ' . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    $checkDuplicado->close();
    $stmtInsert->close();
    $conn->close();
    exit;
}

// === PROCESAR CADA REGISTRO COMPLETO ===
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
    
    error_log("Item #" . ($itemIndex + 1) . " tiene " . (is_array($registros) ? count($registros) : 0) . " registros");
    
    if (empty($registros) || !is_array($registros)) {
        error_log("Item #" . ($itemIndex + 1) . " sin registros válidos. Item keys: " . implode(', ', array_keys($item)));
        continue; // Saltar si no hay registros
    }

    // Agrupar registros por nivel para manejar evidencias
    $registrosPorNivel = [];
    foreach ($registros as $reg) {
        $nivel = $reg['tnivel'] ?? '';
        if (!isset($registrosPorNivel[$nivel])) {
            $registrosPorNivel[$nivel] = [];
        }
        $registrosPorNivel[$nivel][] = $reg;
    }

    // === INSERTAR CADA REGISTRO ===
    error_log("Procesando " . count($registros) . " registros del item #" . ($itemIndex + 1));
    
    foreach ($registros as $regIndex => $reg) {
        // Validar que el registro tenga los campos mínimos necesarios
        if (!isset($reg['tuuid']) || !isset($reg['tsistema']) || !isset($reg['tnivel']) || !isset($reg['tparametro'])) {
            error_log("Registro #" . ($regIndex + 1) . " inválido (faltan campos requeridos). Keys: " . implode(', ', array_keys($reg ?? [])));
            continue;
        }
        
        $tuuidRegistro = $reg['tuuid'] ?? '';
        $existeRegistro = 0;
        $needsUpdate = isset($item['needs_update']) && $item['needs_update'] === true;

        // Verificar si el registro ya existe por tuuid (cada registro tiene su propio UUID)
        if (!empty($tuuidRegistro)) {
            $checkDuplicado->bind_param("s", $tuuidRegistro);
            $checkDuplicado->execute();
            $checkDuplicado->bind_result($existeRegistro);
            $checkDuplicado->fetch();
            $checkDuplicado->free_result();

            // Si existe y NO necesita actualización, saltarlo como duplicado
            if ($existeRegistro > 0 && !$needsUpdate) {
                $duplicados++;
                continue; // Saltar este registro si ya existe y no necesita actualización
            }
            // Si existe Y necesita actualización, continuar para actualizarlo más abajo
        }

        $obs = $reg['tobservacion'] ?? '';
        $tobs = substr($obs, 0, 255); // Limitar a 255 caracteres

        // Determinar código de sistema
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

        // evidencia vacía por ahora (se actualizará después si hay imágenes)
        $evidencia = '';
        $tid = $tidContador;
        $tidContador++;

        // Asegurar que los valores numéricos sean válidos
        $porcentaje1 = isset($reg['tporcentaje1']) ? (double)$reg['tporcentaje1'] : 0.0;
        $porcentaje2 = isset($reg['tporcentaje2']) ? (double)$reg['tporcentaje2'] : 0.0;
        $porcentaje3 = isset($reg['tporcentaje3']) ? (double)$reg['tporcentaje3'] : 0.0;
        $porcentaje4 = isset($reg['tporcentaje4']) ? (double)$reg['tporcentaje4'] : 0.0;
        $porcentaje5 = isset($reg['tporcentaje5']) ? (double)$reg['tporcentaje5'] : 0.0;
        $porcentajeTotal = isset($reg['tporcentajetotal']) ? (double)$reg['tporcentajetotal'] : 0.0;
        
        // Asegurar tipos correctos y validar fechas ANTES de cualquier otra operación
        // FECHAS - deben estar en formato YYYY-MM-DD
        $tdate = date('Y-m-d'); // Siempre usar fecha actual del servidor
        $diareg = date('Y-m-d'); // Siempre usar fecha actual del servidor
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fectra)) {
            $fectra = date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tdate_trans)) {
            $tdate_trans = date('Y-m-d');
        }
        
       
        $tsistema = (string)($reg['tsistema'] ?? '');
        $tnivel = (string)($reg['tnivel'] ?? '');
        $tparametro = (string)($reg['tparametro'] ?? '');
      
        if ($existeRegistro > 0 && $needsUpdate) {
            // Formato UPDATE: 29 parámetros (8s+1i+2s+1i+3s+6d+3s+3s+1s+1s = 8+1+2+1+3+6+3+3+1+1 = 29)
            $stmtUpdate->bind_param(
                "ssssssssississsddddddssssssss",
                $tuser,              // 1. tuser: s
                $tdate,              // 2. tdate: s
                $ttime,              // 3. ttime: s
                $tcencos,            // 4. tcencos: s
                $granja,             // 5. tgranja: s
                $campania,           // 6. tcampania: s
                $edad,               // 7. tedad: s
                $galpon,             // 8. tgalpon: s
                $numreg,             // 9. tnumreg: i
                $fectra,             // 10. tfectra: s
                $diareg,             // 11. diareg: s
                $tcodsistema,        // 12. tcodsistema: i
                $tsistema,           // 13. tsistema: s
                $tnivel,             // 14. tnivel: s
                $tparametro,         // 15. tparametro: s
                $porcentaje1,        // 16. tporcentaje1: d
                $porcentaje2,        // 17. tporcentaje2: d
                $porcentaje3,        // 18. tporcentaje3: d
                $porcentaje4,        // 19. tporcentaje4: d
                $porcentaje5,        // 20. tporcentaje5: d
                $porcentajeTotal,    // 21. tporcentajetotal: d
                $obs,                // 22. tobservacion: s
                $evidencia,          // 23. evidencia: s
                $tobs,               // 24. tobs: s
                $tuser_trans,        // 25. tuser_trans: s
                $tdate_trans,        // 26. tdate_trans: s
                $ttime_trans,        // 27. ttime_trans: s
                $tidandroid,         // 28. tidandroid: s
                $tuuidRegistro       // 29. tuuid (WHERE): s
            );

            if ($stmtUpdate->execute()) {
                $actualizados++;
                error_log("✓ Registro actualizado exitosamente: UUID=$tuuidRegistro, Sistema={$reg['tsistema']}, Nivel={$reg['tnivel']}");
            } else {
                $errorMsg = "✗ Error al actualizar registro con UUID $tuuidRegistro: " . $stmtUpdate->error;
                error_log($errorMsg);
            }
        } else {
            // Si no existe, insertar como nuevo registro
            // Formato correcto: "sssssssssissisddddddssssssssi" (9+1+2+1+3+6+3+3+1+1 = 30)
            $stmtInsert->bind_param(
                "sssssssssississsddddddsssssssi",
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
                $tid                 // 30. tid: i
            );

            if ($stmtInsert->execute()) {
                $insertados++;
                
            }
        }
    }

    // === PROCESAR IMÁGENES SI EXISTEN ===
    // Obtener metadata de evidencias del item
    $evidenciasMetadata = $item['evidencias_metadata'] ?? [];
    $rutasEvidenciasPorNivel = []; // Agrupar rutas por nivel
    
    error_log("Procesando evidencias para item #" . ($itemIndex + 1) . ". Total metadata: " . count($evidenciasMetadata));
    
    // Log detallado de todos los archivos recibidos
    if (!empty($_FILES)) {
        error_log("=== ARCHIVOS RECIBIDOS EN \$_FILES ===");
        foreach ($_FILES as $key => $fileInfo) {
            $errorCode = isset($fileInfo['error']) ? $fileInfo['error'] : 'N/A';
            $size = isset($fileInfo['size']) ? $fileInfo['size'] : 0;
            $name = isset($fileInfo['name']) ? $fileInfo['name'] : 'N/A';
            error_log("  - Key: '$key' | Name: '$name' | Size: $size bytes | Error: $errorCode");
        }
        error_log("=== FIN ARCHIVOS ===");
    } else {
        error_log("⚠ ADVERTENCIA: \$_FILES está vacío!");
    }
    
    foreach ($evidenciasMetadata as $evidenciaKey => $evidenciaInfo) {
        $sistema = $evidenciaInfo['sistema'] ?? '';
        $nivel = $evidenciaInfo['nivel'] ?? '';
        $cantidad = isset($evidenciaInfo['cantidad']) ? (int)$evidenciaInfo['cantidad'] : 0;
        $originalKey = $evidenciaInfo['original_key'] ?? $evidenciaKey; // Clave original antes de normalizar
        
      
        $rutasNivel = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $fileKey = 'evidencia_' . $evidenciaKey . '_' . $i;
            
            $fileExists = isset($_FILES[$fileKey]);
            if ($fileExists) {
                $fileError = $_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE;
              
            } else {
              
                foreach ($_FILES as $key => $val) {
                    if (strpos($key, 'evidencia_') === 0 && strpos($key, '_' . $i) !== false) {
                        error_log("    → Posible variación encontrada: '$key' (podría ser esta imagen)");
                    }
                }
            }
            
            if ($fileExists && isset($_FILES[$fileKey]['error']) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$fileKey];
              
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $nombreArchivo = $granja . '_' . $galpon . '_' . $numreg . '_' . 
                                str_replace('-', '', $fectra) . '_' . 
                                strtolower(str_replace(' ', '_', $nivel)) . '_' . 
                                $i . '_' . uniqid() . '.' . $extension;
                
                $rutaFisica = $carpetaNecropsias . $nombreArchivo;
                $rutaBD = $rutaRelativaBD . $nombreArchivo;
                
               
                if (move_uploaded_file($file['tmp_name'], $rutaFisica)) {
                    $rutasNivel[] = $rutaBD;
                    error_log("✓ Imagen guardada: $rutaBD");
                } else {
                    error_log("✗ Error al mover archivo: $nombreArchivo");
                }
            } else {
                $errorCode = isset($_FILES[$fileKey]) ? $_FILES[$fileKey]['error'] : 'NO_EXISTE';
                error_log("✗ Archivo NO encontrado o con error: $fileKey (error: $errorCode)");
            }
        }
        
        
        if (!empty($rutasNivel)) {
            $rutasEvidenciasPorNivel[$nivel] = implode(',', $rutasNivel);
            error_log("✓ Rutas guardadas para nivel $nivel: " . $rutasEvidenciasPorNivel[$nivel]);
        } else {
            error_log("⚠ No se encontraron imágenes para el nivel: $nivel");
        }
    }
    
   
    foreach ($rutasEvidenciasPorNivel as $nivel => $rutasComas) {
    
        $updateEvidenciaSql = "UPDATE t_regnecropsia 
                      SET evidencia = ? 
                      WHERE tgranja = ? AND tgalpon = ? AND tnumreg = ? AND tfectra = ? AND tnivel = ?";
        $stmtUpdateEvidencia = $conn->prepare($updateEvidenciaSql);
        if ($stmtUpdateEvidencia) {
            $stmtUpdateEvidencia->bind_param("ssssss", $rutasComas, $granja, $galpon, $numreg, $fectra, $nivel);
            if ($stmtUpdateEvidencia->execute()) {
                $affectedRows = $stmtUpdateEvidencia->affected_rows;
                error_log("✓ Actualizados $affectedRows registros con evidencia para nivel: $nivel (ruta: $rutasComas)");
            } else {
                error_log("✗ Error al actualizar evidencia para nivel $nivel: " . $stmtUpdateEvidencia->error);
            }
            $stmtUpdateEvidencia->close();
        } else {
            error_log("✗ Error preparando consulta de actualización de evidencia para nivel $nivel: " . $conn->error);
        }
    }
}

$stmtInsert->close();
$stmtUpdate->close();
$checkDuplicado->close();
$conn->close();


$respuesta = [
    "insertados" => $insertados,
    "actualizados" => $actualizados,
    "duplicados" => $duplicados
];

$totalProcesados = $insertados + $actualizados;
error_log("RESUMEN: Insertados=$insertados, Actualizados=$actualizados, Duplicados=$duplicados, Total items procesados=" . count($items));

echo json_encode([
    "success" => $totalProcesados > 0,
    "message" => $totalProcesados > 0 
        ? "Datos procesados correctamente. Insertados: $insertados, Actualizados: $actualizados, Duplicados: $duplicados"
        : "No se procesaron registros. Verifique los logs para más detalles.",
    "detalle" => $respuesta
], JSON_UNESCAPED_UNICODE);
?>
