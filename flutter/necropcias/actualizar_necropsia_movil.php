<?php
/**
 * Endpoint específico para actualizar registros de necropsia existentes
 * Este endpoint se usa cuando se edita un registro que ya fue sincronizado
 * 
 * IMPORTANTE: Para manejar imágenes grandes en PHP 7.2, configure estos valores:
 * 
 * OPCIÓN 1 - En php.ini (recomendado, funciona siempre):
 *   post_max_size = 50M
 *   upload_max_filesize = 50M
 *   max_execution_time = 300
 *   max_input_time = 300
 *   memory_limit = 256M
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Intentar aumentar límites (puede no funcionar si están deshabilitados en php.ini)
@ini_set('upload_max_filesize', '50M');
@ini_set('post_max_size', '50M');
@ini_set('max_execution_time', '300');
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

// === RUTAS PARA EVIDENCIAS ===
$basePath = __DIR__ . '/';
$carpetaUploads = $basePath . '../../uploads/';
$carpetaNecropsias = $carpetaUploads . 'necropsias/';
$rutaRelativaBD = 'uploads/necropsias/';

if (!is_dir($carpetaNecropsias)) {
    mkdir($carpetaNecropsias, 0755, true);
}

// Verificar tamaño del POST
$postMaxSize = ini_get('post_max_size');
$postMaxSizeBytes = return_bytes($postMaxSize);
$contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;

if ($contentLength > 0 && $postMaxSizeBytes > 0 && $contentLength > $postMaxSizeBytes) {
    echo json_encode([
        'success' => false,
        'message' => 'El tamaño de los datos (' . number_format($contentLength / 1024 / 1024, 2) . ' MB) excede el límite permitido (' . $postMaxSize . ')',
        'content_length' => $contentLength,
        'max_allowed' => $postMaxSizeBytes
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Función auxiliar para convertir tamaño en formato PHP a bytes
function return_bytes($val) {
    $val = trim($val);
    if (empty($val)) return 0;
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

// Obtener datos JSON
$dataJson = $_POST['data'] ?? null;

if (empty($dataJson)) {
    $phpInput = file_get_contents('php://input');
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
    echo json_encode([
        'success' => false,
        'message' => 'No se recibieron datos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Decodificar JSON
$input = json_decode($dataJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al decodificar JSON: ' . json_last_error_msg()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$input || !isset($input['necropcias']) || !is_array($input['necropcias']) || empty($input['necropcias'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos inválidos o incompletos. Se esperaba un array de "necropcias"'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$items = $input['necropcias'];
$actualizados = 0;
$noEncontrados = 0;
$errores = 0;

// === PREPARAR CONSULTA DE VERIFICACIÓN DE EXISTENCIA ===
$checkExiste = $conn->prepare("SELECT COUNT(*) FROM t_regnecropsia WHERE tuuid = ?");
if (!$checkExiste) {
    echo json_encode([
        'success' => false,
        'message' => 'Error preparando consulta de verificación: ' . $conn->error
    ], JSON_UNESCAPED_UNICODE);
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
    $checkExiste->close();
    $conn->close();
    exit;
}

// === PROCESAR CADA REGISTRO ===
error_log("=== ACTUALIZACIÓN DE REGISTROS ===");
error_log("Total de items a actualizar: " . count($items));

foreach ($items as $itemIndex => $item) {
    error_log("Procesando item #" . ($itemIndex + 1) . " para actualización");
    
    // === CABECERA ===
    $granja = $item['granja'] ?? '';
    $campania = $item['campania'] ?? '';
    $galpon = $item['galpon'] ?? '';
    $edad = $item['edad'] ?? '';
    $fectra = $item['fectra'] ?? '';
    $numreg = isset($item['numreg']) ? (int)$item['numreg'] : 0;
    $tcencos = $item['tcencos'] ?? '';
    
    // === CAMPOS ADICIONALES ===
    $tuser = $item['tuser'] ?? 'app_movil';
    $tidandroid = $item['tidandroid'] ?? 'app_movil';
    $tuser_trans = $item['tuser_trans'] ?? $tuser;
    $tdate_trans = $item['tdate_trans'] ?? date('Y-m-d');
    $ttime_trans = $item['ttime_trans'] ?? date('H:i:s');
    
    // Validar y formatear fecha de transacción
    if (empty($tdate_trans) || $tdate_trans == '1000-01-01') {
        $tdate_trans = date('Y-m-d');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tdate_trans)) {
        $tdate_trans = date('Y-m-d');
    }
    
    // Validar y formatear tiempo de transacción
    if (empty($ttime_trans) || $ttime_trans == '00:00:00') {
        $ttime_trans = date('H:i:s');
    } elseif (strlen($ttime_trans) == 6 && is_numeric($ttime_trans)) {
        $ttime_trans = substr($ttime_trans, 0, 2) . ':' . substr($ttime_trans, 2, 2) . ':' . substr($ttime_trans, 4, 2);
    }
    
    // Formatear fectra
    if (!empty($fectra)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fectra)) {
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
        error_log("Item #" . ($itemIndex + 1) . " sin registros válidos");
        continue;
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

    // === ACTUALIZAR CADA REGISTRO ===
    error_log("Item #" . ($itemIndex + 1) . " tiene " . count($registros) . " registros");
    
    foreach ($registros as $regIndex => $reg) {
        // Validar campos requeridos
        if (!isset($reg['tuuid']) || !isset($reg['tsistema']) || !isset($reg['tnivel']) || !isset($reg['tparametro'])) {
            error_log("✗ Registro #" . ($regIndex + 1) . " inválido (faltan campos requeridos)");
            error_log("  Campos presentes: tuuid=" . (isset($reg['tuuid']) ? 'SÍ' : 'NO') . 
                     ", tsistema=" . (isset($reg['tsistema']) ? 'SÍ' : 'NO') . 
                     ", tnivel=" . (isset($reg['tnivel']) ? 'SÍ' : 'NO') . 
                     ", tparametro=" . (isset($reg['tparametro']) ? 'SÍ' : 'NO'));
            $errores++;
            continue;
        }
        
        $tuuidRegistro = $reg['tuuid'] ?? '';
        
        if (empty($tuuidRegistro)) {
            error_log("✗ Registro #" . ($regIndex + 1) . " sin tuuid - no se puede actualizar");
            $errores++;
            continue;
        }
        
        error_log("Procesando registro #" . ($regIndex + 1) . " con UUID: $tuuidRegistro");

        // Verificar que el registro existe
        $existeRegistro = 0;
        $checkExiste->bind_param("s", $tuuidRegistro);
        if (!$checkExiste->execute()) {
            error_log("✗ Error ejecutando verificación de existencia para UUID $tuuidRegistro: " . $checkExiste->error);
            $errores++;
            continue;
        }
        $checkExiste->bind_result($existeRegistro);
        $checkExiste->fetch();
        $checkExiste->free_result();

        if ($existeRegistro == 0) {
            error_log("⚠ Registro con UUID $tuuidRegistro no existe en la base de datos - saltando");
            $noEncontrados++;
            continue;
        }
        
        error_log("✓ Registro encontrado: UUID=$tuuidRegistro, Sistema={$reg['tsistema']}, Nivel={$reg['tnivel']}");

        $obs = $reg['tobservacion'] ?? '';
        $tobs = substr($obs, 0, 255);

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

        // evidencia: mantener la existente si no hay nuevas imágenes
        // Se actualizará después si hay nuevas imágenes para este registro específico
        $evidencia = '';
        
        // Si hay evidencias metadata para este nivel, marcar para actualización posterior
        // (se manejará en la sección de procesamiento de imágenes)

        // Asegurar valores numéricos válidos
        $porcentaje1 = isset($reg['tporcentaje1']) ? (double)$reg['tporcentaje1'] : 0.0;
        $porcentaje2 = isset($reg['tporcentaje2']) ? (double)$reg['tporcentaje2'] : 0.0;
        $porcentaje3 = isset($reg['tporcentaje3']) ? (double)$reg['tporcentaje3'] : 0.0;
        $porcentaje4 = isset($reg['tporcentaje4']) ? (double)$reg['tporcentaje4'] : 0.0;
        $porcentaje5 = isset($reg['tporcentaje5']) ? (double)$reg['tporcentaje5'] : 0.0;
        $porcentajeTotal = isset($reg['tporcentajetotal']) ? (double)$reg['tporcentajetotal'] : 0.0;
        
        // Validar fechas
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fectra)) {
            $fectra = date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tdate_trans)) {
            $tdate_trans = date('Y-m-d');
        }
        
        $tsistema = (string)($reg['tsistema'] ?? '');
        $tnivel = (string)($reg['tnivel'] ?? '');
        $tparametro = (string)($reg['tparametro'] ?? '');

        // Ejecutar actualización
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
            $affectedRows = $stmtUpdate->affected_rows;
            if ($affectedRows > 0) {
                $actualizados++;
                error_log("✓ Registro actualizado exitosamente: UUID=$tuuidRegistro, Sistema={$reg['tsistema']}, Nivel={$reg['tnivel']}, Filas afectadas: $affectedRows");
            } else {
                error_log("⚠ UPDATE ejecutado pero no afectó filas: UUID=$tuuidRegistro (puede que los datos sean idénticos)");
                // No incrementar contadores, solo loguear
            }
        } else {
            $errores++;
            $errorMsg = "✗ Error al actualizar registro con UUID $tuuidRegistro: " . $stmtUpdate->error;
            error_log($errorMsg);
            error_log("  SQL: " . $sqlUpdate);
            error_log("  Parámetros: tuuid=$tuuidRegistro, sistema={$reg['tsistema']}, nivel={$reg['tnivel']}");
        }
    }

    // === PROCESAR IMÁGENES SI EXISTEN ===
    // Crear un mapa de tuuid -> nivel para asociar evidencias con registros específicos
    $tuuidPorNivel = [];
    foreach ($registros as $reg) {
        $tuuidReg = $reg['tuuid'] ?? '';
        $nivelReg = $reg['tnivel'] ?? '';
        if (!empty($tuuidReg) && !empty($nivelReg)) {
            if (!isset($tuuidPorNivel[$nivelReg])) {
                $tuuidPorNivel[$nivelReg] = [];
            }
            $tuuidPorNivel[$nivelReg][] = $tuuidReg;
        }
    }
    
    $evidenciasMetadata = $item['evidencias_metadata'] ?? [];
    $rutasEvidenciasPorNivel = [];
    
    error_log("Procesando evidencias para item #" . ($itemIndex + 1) . ". Total metadata: " . count($evidenciasMetadata));
    
    // Log de archivos recibidos para debugging
    if (!empty($_FILES)) {
        error_log("=== ARCHIVOS RECIBIDOS EN \$_FILES (ACTUALIZACIÓN) ===");
        foreach ($_FILES as $key => $fileInfo) {
            $errorCode = isset($fileInfo['error']) ? $fileInfo['error'] : 'N/A';
            $size = isset($fileInfo['size']) ? $fileInfo['size'] : 0;
            $name = isset($fileInfo['name']) ? $fileInfo['name'] : 'N/A';
            error_log("  - Key: '$key' | Name: '$name' | Size: $size bytes | Error: $errorCode");
        }
        error_log("=== FIN ARCHIVOS ===");
    }
    
    // Niveles que deben procesarse (incluso si no tienen imágenes, para limpiar evidencias)
    $nivelesProcesados = [];
    
    foreach ($evidenciasMetadata as $evidenciaKey => $evidenciaInfo) {
        $sistema = $evidenciaInfo['sistema'] ?? '';
        $nivel = $evidenciaInfo['nivel'] ?? '';
        $cantidad = isset($evidenciaInfo['cantidad']) ? (int)$evidenciaInfo['cantidad'] : 0;
        
        // Marcar este nivel como procesado
        $nivelesProcesados[] = $nivel;
        
        error_log("Procesando evidencias para nivel: $nivel, cantidad esperada: $cantidad");
        
        $rutasNivel = [];
        
        // Si cantidad es 0, significa que el usuario eliminó todas las imágenes de este nivel
        if ($cantidad == 0) {
            error_log("⚠ Nivel $nivel tiene cantidad 0 - se limpiarán las evidencias existentes");
            $rutasEvidenciasPorNivel[$nivel] = ''; // Cadena vacía para limpiar
            continue;
        }
        
        // Procesar cada imagen esperada
        for ($i = 0; $i < $cantidad; $i++) {
            $fileKey = 'evidencia_' . $evidenciaKey . '_' . $i;
            
            // Intentar también con variaciones del nombre de la clave
            $fileFound = false;
            $actualFileKey = $fileKey;
            
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $fileFound = true;
            } else {
                // Buscar variaciones (puede haber diferencias en normalización)
                foreach ($_FILES as $key => $val) {
                    if (strpos($key, 'evidencia_') === 0 && 
                        strpos($key, $evidenciaKey) !== false && 
                        strpos($key, '_' . $i) !== false) {
                        $actualFileKey = $key;
                        $fileFound = true;
                        error_log("    → Usando variación encontrada: '$key' en lugar de '$fileKey'");
                        break;
                    }
                }
            }
            
            if ($fileFound && isset($_FILES[$actualFileKey]) && $_FILES[$actualFileKey]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$actualFileKey];
                
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
                $errorCode = isset($_FILES[$actualFileKey]) ? $_FILES[$actualFileKey]['error'] : 'NO_EXISTE';
                error_log("⚠ Archivo NO encontrado o con error: $fileKey (error: $errorCode)");
            }
        }
        
        if (!empty($rutasNivel)) {
            $rutasComas = implode(',', $rutasNivel);
            $rutasEvidenciasPorNivel[$nivel] = $rutasComas;
            error_log("✓ Rutas guardadas para nivel $nivel: $rutasComas");
        } else if ($cantidad > 0) {
            // Si se esperaban imágenes pero no se encontraron, mantener las existentes (no actualizar)
            error_log("⚠ No se encontraron imágenes nuevas para el nivel: $nivel (se mantendrán las existentes)");
        }
    }
    
    // Actualizar evidencias en la base de datos usando tuuid (más preciso)
    // Solo actualizar niveles que tienen nuevas imágenes o que deben limpiarse
    foreach ($rutasEvidenciasPorNivel as $nivel => $rutasComas) {
        // $rutasComas puede ser una cadena con rutas o cadena vacía (para limpiar)
        if (isset($tuuidPorNivel[$nivel]) && !empty($tuuidPorNivel[$nivel])) {
            // Actualizar cada registro del nivel usando su tuuid
            foreach ($tuuidPorNivel[$nivel] as $tuuidReg) {
                $updateEvidenciaSql = "UPDATE t_regnecropsia 
                              SET evidencia = ? 
                              WHERE tuuid = ?";
                $stmtUpdateEvidencia = $conn->prepare($updateEvidenciaSql);
                if ($stmtUpdateEvidencia) {
                    $stmtUpdateEvidencia->bind_param("ss", $rutasComas, $tuuidReg);
                    if ($stmtUpdateEvidencia->execute()) {
                        $affectedRows = $stmtUpdateEvidencia->affected_rows;
                        if ($affectedRows > 0) {
                            if (empty($rutasComas)) {
                                error_log("✓ Limpiadas evidencias del registro con UUID $tuuidReg (nivel: $nivel)");
                            } else {
                                error_log("✓ Actualizado registro con UUID $tuuidReg (nivel: $nivel) con evidencia: $rutasComas");
                            }
                        } else {
                            error_log("⚠ No se actualizó ningún registro con UUID $tuuidReg (puede que no exista)");
                        }
                    } else {
                        error_log("✗ Error al actualizar evidencia para UUID $tuuidReg: " . $stmtUpdateEvidencia->error);
                    }
                    $stmtUpdateEvidencia->close();
                } else {
                    error_log("✗ Error preparando consulta de actualización de evidencia para UUID $tuuidReg: " . $conn->error);
                }
            }
        } else {
            // Fallback: usar método anterior si no hay tuuid disponible
            $updateEvidenciaSql = "UPDATE t_regnecropsia 
                          SET evidencia = ? 
                          WHERE tgranja = ? AND tgalpon = ? AND tnumreg = ? AND tfectra = ? AND tnivel = ?";
            $stmtUpdateEvidencia = $conn->prepare($updateEvidenciaSql);
            if ($stmtUpdateEvidencia) {
                $stmtUpdateEvidencia->bind_param("ssssss", $rutasComas, $granja, $galpon, $numreg, $fectra, $nivel);
                if ($stmtUpdateEvidencia->execute()) {
                    $affectedRows = $stmtUpdateEvidencia->affected_rows;
                    if (empty($rutasComas)) {
                        error_log("✓ Limpiadas evidencias de $affectedRows registros para nivel: $nivel (método fallback)");
                    } else {
                        error_log("✓ Actualizados $affectedRows registros con evidencia para nivel: $nivel (método fallback)");
                    }
                } else {
                    error_log("✗ Error al actualizar evidencia para nivel $nivel: " . $stmtUpdateEvidencia->error);
                }
                $stmtUpdateEvidencia->close();
            }
        }
    }
}

$stmtUpdate->close();
$checkExiste->close();
$conn->close();

error_log("RESUMEN ACTUALIZACIÓN: Actualizados=$actualizados, No encontrados=$noEncontrados, Errores=$errores, Total items procesados=" . count($items));

// Si no se actualizó ningún registro pero tampoco hubo errores ni "no encontrados",
// puede ser que todos los registros ya tenían los mismos valores
if ($actualizados == 0 && $noEncontrados == 0 && $errores == 0) {
    error_log("⚠ ADVERTENCIA: No se actualizó ningún registro, pero tampoco hubo errores. Puede que todos los registros ya tengan los valores actualizados.");
}

echo json_encode([
    "success" => $actualizados > 0,
    "message" => $actualizados > 0 
        ? "Registros actualizados correctamente. Actualizados: $actualizados" . 
          ($noEncontrados > 0 ? ", No encontrados: $noEncontrados" : "") .
          ($errores > 0 ? ", Errores: $errores" : "")
        : ($noEncontrados > 0 
            ? "No se encontraron registros para actualizar ($noEncontrados registros no encontrados). " 
            : "") .
          ($errores > 0 
            ? "Errores al actualizar: $errores. " 
            : "") .
          ($actualizados == 0 && $noEncontrados == 0 && $errores == 0
            ? "No se procesaron registros. Verifique los logs para más detalles."
            : "Verifique que los registros existan y tengan el tuuid correcto."),
    "detalle" => [
        "actualizados" => $actualizados,
        "no_encontrados" => $noEncontrados,
        "errores" => $errores,
        "total_items" => count($items)
    ]
], JSON_UNESCAPED_UNICODE);
?>
