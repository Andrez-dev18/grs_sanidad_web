<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json; charset=UTF-8');
ob_start();

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

if (!$conexion) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error de conexión a BD']);
    exit();
}

// Generador de UUID v4 (usado para columnas `id` en tablas que lo requieren)
if (!function_exists('generar_uuid_v4')) {
    function generar_uuid_v4() {
        try {
            $data = random_bytes(16);
        } catch (Exception $e) {
            $data = openssl_random_pseudo_bytes(16);
        }
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

$action = $_REQUEST['action'] ?? '';

try {

    // ==================== GET ENFERMEDADES DE UNA SOLICITUD ====================
if ($action == 'get_enfermedades') {
    $codEnvio = $_GET['codEnvio'] ?? '';
    $estado = strtolower(trim($_GET['estado'] ?? 'pendiente'));

    if (empty($codEnvio)) {
        throw new Exception('Código de envío vacío');
    }

    // CORREGIDO: Siempre cargar desde san_fact_solicitud_det
    // Esta tabla mantiene la lista correcta de enfermedades asignadas
    $q = "SELECT DISTINCT nomAnalisis as nombre, codAnalisis as codigo
          FROM san_fact_solicitud_det 
          WHERE codEnvio = ? 
          AND estado_cuanti IN ('pendiente', 'completado')
          ORDER BY codAnalisis";

    $stmt = $conexion->prepare($q);
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conexion->error);
    }

    $stmt->bind_param("s", $codEnvio);
    $stmt->execute();
    $res = $stmt->get_result();

    $enfermedades = [];
    while ($row = $res->fetch_assoc()) {
        $enfermedades[] = $row;
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'enfermedades' => $enfermedades,
        'total' => count($enfermedades),
        'estado' => $estado
    ]);
    exit;
}


    //leer datos
    // ==================== GET CATÁLOGO DE ENFERMEDADES ====================
    elseif ($action == 'get_catalogo_enfermedades') {
        $q = "SELECT codigo, nombre, enfermedad as enfermedad_completa
              FROM san_dim_analisis 
              WHERE paquete IS NOT NULL
              ORDER BY nombre";

        $res = $conexion->query($q);

        if (!$res) {
            throw new Exception('Error en consulta catálogo: ' . $conexion->error);
        }

        $enfermedades = [];
        while ($row = $res->fetch_assoc()) {
            $enfermedades[] = $row;
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'enfermedades' => $enfermedades]);
        exit;
    } 
    // ==================== GET RESULTADOS GUARDADOS ====================
elseif ($action == 'get_resultados_guardados') {
    $codEnvio = $_GET['codEnvio'] ?? '';
    $enfermedad = $_GET['enfermedad'] ?? '';
    
    if (empty($codEnvio) || empty($enfermedad)) {
        throw new Exception('Parámetros incompletos');
    }
    
    $q = "SELECT * FROM san_analisis_pollo_bb_adulto 
          WHERE codigo_envio = ? AND enfermedad = ? 
          LIMIT 1";
    
    $stmt = $conexion->prepare($q);
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conexion->error);
    }
    
    $stmt->bind_param("ss", $codEnvio, $enfermedad);
    $stmt->execute();
    $res = $stmt->get_result();
    $datos = $res->fetch_assoc();
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'datos' => $datos
    ]);
    exit;
}

    
    // ==================== AGREGAR ENFERMEDAD A SOLICITUD ====================
    elseif ($action == 'agregar_enfermedad_solicitud') {
        $codEnvio = $_POST['codEnvio'] ?? '';
        $codAnalisis = $_POST['codAnalisis'] ?? '';
        $nomAnalisis = $_POST['nomAnalisis'] ?? '';
        $codRef = $_POST['codRef'] ?? '';
        $fecToma = $_POST['fecToma'] ?? '';
        $codMuestra = $_POST['codMuestra'] ?? 3;

        if (empty($codEnvio) || empty($codAnalisis)) {
            throw new Exception('Datos incompletos');
        }

        // Obtener último posSolicitud
        $qPos = "SELECT MAX(posSolicitud) as maxPos FROM san_fact_solicitud_det WHERE codEnvio = ?";
        $stmtPos = $conexion->prepare($qPos);
        $stmtPos->bind_param("s", $codEnvio);
        $stmtPos->execute();
        $resPos = $stmtPos->get_result()->fetch_assoc();
        $posSolicitud = ($resPos['maxPos'] ?? 0) + 1;

        // Obtener nombre muestra
        $qMuestra = "SELECT nombre FROM san_dim_tipo_muestra WHERE codigo = ?";
        $stmtMuestra = $conexion->prepare($qMuestra);
        $stmtMuestra->bind_param("i", $codMuestra);
        $stmtMuestra->execute();
        $resMuestra = $stmtMuestra->get_result()->fetch_assoc();
        $nomMuestra = $resMuestra['nombre'] ?? 'Sueros';

        // Insertar (agregar campo `id` generado para cumplir esquema que requiere id NOT NULL)
        $uuid_det = generar_uuid_v4();

        $qInsert = "INSERT INTO san_fact_solicitud_det 
                    (codEnvio, posSolicitud, codRef, fecToma, codMuestra, nomMuestra, codAnalisis, nomAnalisis, numMuestras, obs, id, estado_cuali, estado_cuanti)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 20, '', ?, 'pendiente', 'pendiente')";

        $stmtInsert = $conexion->prepare($qInsert);
        if (!$stmtInsert) {
            throw new Exception('Error preparando INSERT detalle: ' . $conexion->error);
        }

        $stmtInsert->bind_param("sissisiss", $codEnvio, $posSolicitud, $codRef, $fecToma, $codMuestra, $nomMuestra, $codAnalisis, $nomAnalisis, $uuid_det);

        if (!$stmtInsert->execute()) {
            throw new Exception('Error al insertar: ' . $stmtInsert->error);
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Enfermedad agregada']);
        exit;
    } 
    
    // ==================== GET PENDIENTES ====================
    elseif ($action == 'get_pendientes') {
        $q = "SELECT d.codEnvio, d.fecToma, d.codRef, d.numMuestras, d.nomMuestra 
              FROM san_fact_solicitud_det d 
              WHERE d.estado_cuanti = 'pendiente' 
              ORDER BY d.fecToma DESC";
        $res = $conexion->query($q);

        $pendientes = [];
        while ($row = $res->fetch_assoc()) {
            $pendientes[] = $row;
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'data' => $pendientes]);
        exit;
    } 
    
    // ==================== CREATE (GUARDAR ANÁLISIS) ====================
    elseif ($action == 'create') {
        mysqli_begin_transaction($conexion);
        
        try {
            $tipo = $_POST['tipo_ave'] ?? '';
            $cod = $_POST['codigo_solicitud'] ?? '';
            $fec = $_POST['fecha_toma'] ?? '';

            // Saneamiento de edad_aves: el frontend a veces envía el codRef completo (número muy grande)
            // Si el valor es demasiado largo, tomar los últimos 2 dígitos que representan la edad real.
            $edad_raw = $_POST['edad_aves'] ?? '';
            $edad = 0;
            if ($edad_raw !== '') {
                // eliminar todo excepto dígitos
                $digits = preg_replace('/\D/', '', (string)$edad_raw);
                if ($digits === '') {
                    $edad = 0;
                } else {
                    // Si el número tiene más de 3 dígitos (p. ej. codRef completo), tomar últimos 2
                    if (strlen($digits) > 3) {
                        $edad = (int)substr($digits, -2);
                    } else {
                        $edad = (int)$digits;
                    }
                }
            }
            
            if(empty($cod)) {
                throw new Exception('Código de solicitud requerido');
            }
            
            // Campos opcionales
            $planta = isset($_POST['planta_incubacion']) ? $_POST['planta_incubacion'] : NULL;
            $lote = isset($_POST['lote']) ? $_POST['lote'] : NULL;
            $granja = isset($_POST['codigo_granja']) ? $_POST['codigo_granja'] : NULL;
            $camp = isset($_POST['codigo_campana']) ? $_POST['codigo_campana'] : NULL;
            // Asegurar que los valores numéricos no excedan rangos válidos
            $galp_raw = $_POST['numero_galpon'] ?? null;
            $galp = null;
            if ($galp_raw !== null && $galp_raw !== '') {
                $galp_digits = preg_replace('/\D/','', (string)$galp_raw);
                $galp = $galp_digits === '' ? null : (int)$galp_digits;
            }

            $edRep_raw = $_POST['edad_reproductora'] ?? null;
            $edRep = null;
            if ($edRep_raw !== null && $edRep_raw !== '') {
                $edRep_digits = preg_replace('/\D/','', (string)$edRep_raw);
                $edRep = $edRep_digits === '' ? null : (int)$edRep_digits;
            }
            $cond = $_POST['condicion'] ?? '';
            $est = $_POST['estado'] ?? 'ANALIZADO';
            $inf = isset($_POST['numero_informe']) ? $_POST['numero_informe'] : '';
            $fecInf = isset($_POST['fecha_informe']) ? $_POST['fecha_informe'] : NULL;
            $user = $_SESSION['usuario'] ?? 'SISTEMA';
            
            // Validar que existan enfermedades
            if(!isset($_POST['enfermedades']) || !is_array($_POST['enfermedades'])) {
                throw new Exception('No se encontraron enfermedades para guardar');
            }

            // Construir mapeo nombre -> codigo de analisis si el frontend no lo envía.
            // Si el frontend envía 'codAnalisis[]' intentamos mapear por posición.
            $nameToCode = [];
            $receivedCodes = isset($_POST['codAnalisis']) && is_array($_POST['codAnalisis']) ? array_values($_POST['codAnalisis']) : [];
            $receivedNames = isset($_POST['enfermedades']) && is_array($_POST['enfermedades']) ? array_values($_POST['enfermedades']) : [];
            if (!empty($receivedCodes) && count($receivedCodes) === count($receivedNames)) {
                // Mapear por índice (posicional)
                foreach ($receivedNames as $i => $nm) {
                    $codeVal = $receivedCodes[$i];
                    // normalizar a entero cuando sea posible
                    $nameToCode[$nm] = is_numeric($codeVal) ? (int)$codeVal : $codeVal;
                }
            } else {
                // Preparar consulta para buscar codigo por nombre en la tabla de catálogo
                $stmtGetCode = $conexion->prepare("SELECT codigo FROM san_dim_analisis WHERE nombre = ? LIMIT 1");
                foreach ($receivedNames as $nm) {
                    $codigoTmp = null;
                    if ($stmtGetCode) {
                        $stmtGetCode->bind_param('s', $nm);
                        $stmtGetCode->execute();
                        $resCode = $stmtGetCode->get_result();
                        if ($resCode && $rowc = $resCode->fetch_assoc()) {
                            $codigoTmp = is_numeric($rowc['codigo']) ? (int)$rowc['codigo'] : $rowc['codigo'];
                        }
                    }
                    $nameToCode[$nm] = $codigoTmp;
                }
                if ($stmtGetCode) $stmtGetCode->close();
            }
            
            $enfermedadesGuardadas = 0;
            
            // INSERTAR CADA ENFERMEDAD
            foreach ($_POST['enfermedades'] as $enf) {
                $enf = trim($enf);
                if(empty($enf)) continue;
                
                $g = isset($_POST[$enf.'_gmean']) && $_POST[$enf.'_gmean'] !== '' ? (float)$_POST[$enf.'_gmean'] : NULL;
                $c = isset($_POST[$enf.'_cv']) && $_POST[$enf.'_cv'] !== '' ? (float)$_POST[$enf.'_cv'] : NULL;
                $s = isset($_POST[$enf.'_sd']) && $_POST[$enf.'_sd'] !== '' ? (float)$_POST[$enf.'_sd'] : NULL;
                $cnt = isset($_POST[$enf.'_count']) ? (int)$_POST[$enf.'_count'] : 20;
                
                // Construir columnas base
                $baseCols = [
                    'codigo_envio','fecha_toma_muestra','edad_aves','tipo_ave',
                    'planta_incubacion','lote','codigo_granja','codigo_campana',
                    'numero_galpon','edad_reproductora','condicion','estado',
                    'numero_informe','fecha_informe','usuario_registro','fecha_solicitud',
                    'enfermedad','codigo_enfermedad',
                    'gmean','cv','desviacion_estandar','count_muestras'
                ];

                $levelCols = [];
                $levelValues = [];

                // Según tipo, recoger niveles: BB -> n0..n25 -> nivel_0..nivel_25
                // ADULTO -> s1..s6 -> s01..s06
                $tipo_upper = strtoupper(trim($tipo));
                if ($tipo_upper === 'ADULTO') {
                    for ($i = 1; $i <= 6; $i++) {
                        $col = sprintf('s%02d', $i); // s01..s06
                        $levelCols[] = $col;
                        $postKey1 = $enf.'_s'.$i; // e.g., BI_s1
                        $postKey2 = $enf.'_s0'.$i; // fallback e.g., BI_s01
                        $val = null;
                        if (isset($_POST[$postKey1]) && $_POST[$postKey1] !== '') {
                            $val = is_numeric($_POST[$postKey1]) ? (float)$_POST[$postKey1] : null;
                        } elseif (isset($_POST[$postKey2]) && $_POST[$postKey2] !== '') {
                            $val = is_numeric($_POST[$postKey2]) ? (float)$_POST[$postKey2] : null;
                        } else {
                            // también intentar leer numeric values from n0.. in case frontend didn't change
                            $fallback = $_POST[$enf.'_n'.($i-1)] ?? null;
                            $val = is_numeric($fallback) ? (int)$fallback : null;
                        }
                        $levelValues[] = $val;
                    }
                } else {
                    // BB: n0..n25 -> nivel_0..nivel_25
                    for ($i = 0; $i <= 25; $i++) {
                        $col = 'nivel_'.$i;
                        $levelCols[] = $col;
                        $postKey = $enf.'_n'.$i;
                        $val = isset($_POST[$postKey]) && $_POST[$postKey] !== '' ? (int)$_POST[$postKey] : 0;
                        $levelValues[] = $val;
                    }
                }

                // Combinar columnas y valores
                $allCols = array_merge($baseCols, $levelCols);

                // Preparar valores en mismo orden
                // determinar codigo_enfermedad (usar mapping si existe)
                $codigo_enf = $nameToCode[$enf] ?? null;

                $values = [
                    $cod, $fec, $edad, $tipo,
                    $planta, $lote, $granja, $camp,
                    $galp, $edRep, $cond, $est,
                    $inf, $fecInf, $user,
                    // fecha_solicitud usamos valor directo (will be bound as string but DB may accept NULL)
                    null,
                    $enf, $codigo_enf,
                    $g, $c, $s, $cnt
                ];

                // Append level values
                foreach ($levelValues as $lv) $values[] = $lv;

                // --- Seguridad: verificar columnas existentes en la tabla para evitar "Unknown column" ---
                $existingCols = [];
                $resCols = $conexion->query("SHOW COLUMNS FROM san_analisis_pollo_bb_adulto");
                if ($resCols) {
                    while ($rc = $resCols->fetch_assoc()) {
                        $existingCols[] = $rc['Field'];
                    }
                }

                // Filtrar columnas/valores manteniendo orden
                $filteredCols = [];
                $filteredValues = [];
                foreach ($allCols as $idx => $colName) {
                    if (in_array($colName, $existingCols, true)) {
                        $filteredCols[] = $colName;
                        // En caso de índices fuera de rango, garantizar valor nulo
                        $filteredValues[] = array_key_exists($idx, $values) ? $values[$idx] : null;
                    }
                }

                if (count($filteredCols) === 0) {
                    throw new Exception('No hay columnas válidas para insertar en san_analisis_pollo_bb_adulto');
                }

                $placeholders = array_fill(0, count($filteredCols), '?');

                $sqlEnf = "INSERT INTO san_analisis_pollo_bb_adulto (" . implode(', ', $filteredCols) . ") VALUES (" . implode(', ', $placeholders) . ")";

                $stmtEnf = $conexion->prepare($sqlEnf);
                if (!$stmtEnf) {
                    throw new Exception('Error preparando INSERT: ' . $conexion->error);
                }

                // Build types for filteredValues
                $types = '';
                foreach ($filteredValues as $v) {
                    if (is_int($v)) $types .= 'i';
                    elseif (is_float($v) || is_double($v)) $types .= 'd';
                    elseif (is_null($v)) $types .= 's';
                    else $types .= 's';
                }

                if (!@$stmtEnf->bind_param($types, ...$filteredValues)) {
                    throw new Exception('Error en bind_param dinámico: ' . $conexion->error);
                }

                if (!$stmtEnf->execute()) {
                    throw new Exception('Error guardando ' . $enf . ': ' . $stmtEnf->error);
                }

                $enfermedadesGuardadas++;
            }
            
            // PROCESAR ARCHIVOS
            $archivos_guardados = 0;
            
            if (isset($_FILES['archivoPdf']) && !empty($_FILES['archivoPdf']['name'][0])) {
                $uploadDir = '../uploads/resultados/';
                
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $qPos = "SELECT posSolicitud FROM san_fact_solicitud_det WHERE codEnvio = ? LIMIT 1";
                $stmtPos = $conexion->prepare($qPos);
                $stmtPos->bind_param("s", $cod);
                $stmtPos->execute();
                $resPos = $stmtPos->get_result()->fetch_assoc();
                $posSolicitud = $resPos['posSolicitud'] ?? 1;
                
                $qArch = "INSERT INTO san_fact_resultado_archivo (codEnvio, posSolicitud, archRuta, tipo, fechaRegistro) VALUES (?, ?, ?, 'cuantitativo', NOW())";
                $stmtArch = $conexion->prepare($qArch);
                
                if(!$stmtArch) {
                    throw new Exception('Error preparando INSERT archivos: ' . $conexion->error);
                }
                
                $archivos = $_FILES['archivoPdf'];
                $totalArchivos = count($archivos['name']);
                
                for ($i = 0; $i < $totalArchivos; $i++) {
                    if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                        $nombreOriginal = basename($archivos['name'][$i]);
                        $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
                        $nombreSinExt = pathinfo($nombreOriginal, PATHINFO_FILENAME);
                        
                        $nombreFinal = $cod . '_' . $posSolicitud . '_' . $nombreSinExt . '.' . $extension;
                        $rutaCompleta = $uploadDir . $nombreFinal;
                        $rutaRelativa = 'uploads/resultados/' . $nombreFinal;
                        
                        if ($archivos['size'][$i] > 10 * 1024 * 1024) {
                            throw new Exception("El archivo {$nombreOriginal} excede 10 MB");
                        }
                        
                        $extensionesPermitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'png', 'jpg', 'jpeg'];
                        if (!in_array(strtolower($extension), $extensionesPermitidas)) {
                            throw new Exception("Extensión no permitida: {$extension}");
                        }
                        
                        if (move_uploaded_file($archivos['tmp_name'][$i], $rutaCompleta)) {
                            $stmtArch->bind_param("sis", $cod, $posSolicitud, $rutaRelativa);
                            
                            if ($stmtArch->execute()) {
                                $archivos_guardados++;
                            } else {
                                throw new Exception('Error guardando ruta en BD: ' . $stmtArch->error);
                            }
                        } else {
                            throw new Exception("Error al mover archivo: {$nombreOriginal}");
                        }
                    }
                }
            }
            
            // ACTUALIZAR ESTADO
            $updateQuery = "UPDATE san_fact_solicitud_det SET estado_cuanti = 'completado' WHERE codEnvio = ?";
            $stmtUpdate = $conexion->prepare($updateQuery);
            $stmtUpdate->bind_param("s", $cod);
            $stmtUpdate->execute();
            
            mysqli_commit($conexion);
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => "✅ Guardado exitoso\n$enfermedadesGuardadas enfermedades\n$archivos_guardados archivos",
                'codigo_envio' => $cod,
                'enfermedades_guardadas' => $enfermedadesGuardadas,
                'archivos_guardados' => $archivos_guardados
            ]);
            exit;
            
        } catch (Exception $e) {
            mysqli_rollback($conexion);
            throw $e;
        }
    }

    // ==================== UPDATE (ACTUALIZAR) ====================
elseif ($action == 'update') {
    mysqli_begin_transaction($conexion);
    
    try {
        $tipo = $_POST['tipo_ave'] ?? '';
        $cod = $_POST['codigo_solicitud'] ?? '';
        $fec = $_POST['fecha_toma'] ?? '';

        $edad_raw = $_POST['edad_aves'] ?? '';
        $edad = 0;
        if ($edad_raw !== '') {
            $digits = preg_replace('/\D/', '', (string)$edad_raw);
            if ($digits === '') {
                $edad = 0;
            } else {
                if (strlen($digits) > 3) {
                    $edad = (int)substr($digits, -2);
                } else {
                    $edad = (int)$digits;
                }
            }
        }
        
        if(empty($cod)) {
            throw new Exception('Código de solicitud requerido');
        }
        
        $planta = isset($_POST['planta_incubacion']) ? $_POST['planta_incubacion'] : NULL;
        $lote = isset($_POST['lote']) ? $_POST['lote'] : NULL;
        $granja = isset($_POST['codigo_granja']) ? $_POST['codigo_granja'] : NULL;
        $camp = isset($_POST['codigo_campana']) ? $_POST['codigo_campana'] : NULL;
        
        $galp_raw = $_POST['numero_galpon'] ?? null;
        $galp = null;
        if ($galp_raw !== null && $galp_raw !== '') {
            $galp_digits = preg_replace('/\D/','', (string)$galp_raw);
            $galp = $galp_digits === '' ? null : (int)$galp_digits;
        }

        $edRep_raw = $_POST['edad_reproductora'] ?? null;
        $edRep = null;
        if ($edRep_raw !== null && $edRep_raw !== '') {
            $edRep_digits = preg_replace('/\D/','', (string)$edRep_raw);
            $edRep = $edRep_digits === '' ? null : (int)$edRep_digits;
        }
        
        $cond = $_POST['condicion'] ?? '';
        $est = $_POST['estado'] ?? 'ANALIZADO';
        $inf = isset($_POST['numero_informe']) ? $_POST['numero_informe'] : '';
        $fecInf = isset($_POST['fecha_informe']) ? $_POST['fecha_informe'] : NULL;
        $user = $_SESSION['usuario'] ?? 'SISTEMA';
        
        if(!isset($_POST['enfermedades']) || !is_array($_POST['enfermedades'])) {
            throw new Exception('No se encontraron enfermedades para actualizar');
        }

        $nameToCode = [];
        $receivedCodes = isset($_POST['codAnalisis']) && is_array($_POST['codAnalisis']) ? array_values($_POST['codAnalisis']) : [];
        $receivedNames = isset($_POST['enfermedades']) && is_array($_POST['enfermedades']) ? array_values($_POST['enfermedades']) : [];
        
        if (!empty($receivedCodes) && count($receivedCodes) === count($receivedNames)) {
            foreach ($receivedNames as $i => $nm) {
                $codeVal = $receivedCodes[$i];
                $nameToCode[$nm] = is_numeric($codeVal) ? (int)$codeVal : $codeVal;
            }
        } else {
            $stmtGetCode = $conexion->prepare("SELECT codigo FROM san_dim_analisis WHERE nombre = ? LIMIT 1");
            foreach ($receivedNames as $nm) {
                $codigoTmp = null;
                if ($stmtGetCode) {
                    $stmtGetCode->bind_param('s', $nm);
                    $stmtGetCode->execute();
                    $resCode = $stmtGetCode->get_result();
                    if ($resCode && $rowc = $resCode->fetch_assoc()) {
                        $codigoTmp = is_numeric($rowc['codigo']) ? (int)$rowc['codigo'] : $rowc['codigo'];
                    }
                }
                $nameToCode[$nm] = $codigoTmp;
            }
            if ($stmtGetCode) $stmtGetCode->close();
        }
        
        $enfermedadesActualizadas = 0;
        
        foreach ($_POST['enfermedades'] as $enf) {
            $enf = trim($enf);
            if(empty($enf)) continue;
            
            $g = isset($_POST[$enf.'_gmean']) && $_POST[$enf.'_gmean'] !== '' ? (float)$_POST[$enf.'_gmean'] : NULL;
            $c = isset($_POST[$enf.'_cv']) && $_POST[$enf.'_cv'] !== '' ? (float)$_POST[$enf.'_cv'] : NULL;
            $s = isset($_POST[$enf.'_sd']) && $_POST[$enf.'_sd'] !== '' ? (float)$_POST[$enf.'_sd'] : NULL;
            $cnt = isset($_POST[$enf.'_count']) ? (int)$_POST[$enf.'_count'] : 20;
            
            $baseCols = [
                'codigo_envio','fecha_toma_muestra','edad_aves','tipo_ave',
                'planta_incubacion','lote','codigo_granja','codigo_campana',
                'numero_galpon','edad_reproductora','condicion','estado',
                'numero_informe','fecha_informe','usuario_registro','fecha_solicitud',
                'enfermedad','codigo_enfermedad',
                'gmean','cv','desviacion_estandar','count_muestras'
            ];

            $levelCols = [];
            $levelValues = [];

            $tipo_upper = strtoupper(trim($tipo));
            if ($tipo_upper === 'ADULTO') {
                for ($i = 1; $i <= 6; $i++) {
                    $col = sprintf('s%02d', $i);
                    $levelCols[] = $col;
                    $postKey1 = $enf.'_s'.$i;
                    $postKey2 = $enf.'_s0'.$i;
                    $val = null;
                    if (isset($_POST[$postKey1]) && $_POST[$postKey1] !== '') {
                        $val = is_numeric($_POST[$postKey1]) ? (float)$_POST[$postKey1] : null;
                    } elseif (isset($_POST[$postKey2]) && $_POST[$postKey2] !== '') {
                        $val = is_numeric($_POST[$postKey2]) ? (float)$_POST[$postKey2] : null;
                    }
                    $levelValues[] = $val;
                }
            } else {
                for ($i = 0; $i <= 25; $i++) {
                    $col = 'nivel_'.$i;
                    $levelCols[] = $col;
                    $postKey = $enf.'_n'.$i;
                    $val = isset($_POST[$postKey]) && $_POST[$postKey] !== '' ? (int)$_POST[$postKey] : 0;
                    $levelValues[] = $val;
                }
            }

            $allCols = array_merge($baseCols, $levelCols);
            $codigo_enf = $nameToCode[$enf] ?? null;

            $values = [
                $cod, $fec, $edad, $tipo,
                $planta, $lote, $granja, $camp,
                $galp, $edRep, $cond, $est,
                $inf, $fecInf, $user,
                null,
                $enf, $codigo_enf,
                $g, $c, $s, $cnt
            ];

            foreach ($levelValues as $lv) $values[] = $lv;

            $existingCols = [];
            $resCols = $conexion->query("SHOW COLUMNS FROM san_analisis_pollo_bb_adulto");
            if ($resCols) {
                while ($rc = $resCols->fetch_assoc()) {
                    $existingCols[] = $rc['Field'];
                }
            }

            $filteredCols = [];
            $filteredValues = [];
            foreach ($allCols as $idx => $colName) {
                if (in_array($colName, $existingCols, true)) {
                    $filteredCols[] = $colName;
                    $filteredValues[] = array_key_exists($idx, $values) ? $values[$idx] : null;
                }
            }

            if (count($filteredCols) === 0) {
                throw new Exception('No hay columnas válidas');
            }

            // ✅ VERIFICAR (SIN columna id)
            $checkQuery = "SELECT codigo_envio FROM san_analisis_pollo_bb_adulto WHERE codigo_envio = ? AND enfermedad = ? LIMIT 1";
            $stmtCheck = $conexion->prepare($checkQuery);
            
            if (!$stmtCheck) {
                throw new Exception('Error preparando SELECT: ' . $conexion->error);
            }
            
            $stmtCheck->bind_param("ss", $cod, $enf);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            
            if ($resCheck->num_rows > 0) {
                // ✅ ACTUALIZAR
                $updateParts = [];
                foreach ($filteredCols as $colName) {
                    if ($colName !== 'codigo_envio' && $colName !== 'enfermedad') {
                        $updateParts[] = "$colName = ?";
                    }
                }
                
                $sqlUpdate = "UPDATE san_analisis_pollo_bb_adulto SET " . implode(', ', $updateParts) . " WHERE codigo_envio = ? AND enfermedad = ?";
                
                $stmtUpdate = $conexion->prepare($sqlUpdate);
                if (!$stmtUpdate) {
                    throw new Exception('Error preparando UPDATE: ' . $conexion->error);
                }
                
                $updateValues = [];
                foreach ($filteredCols as $idx => $colName) {
                    if ($colName !== 'codigo_envio' && $colName !== 'enfermedad') {
                        $updateValues[] = $filteredValues[$idx];
                    }
                }
                $updateValues[] = $cod;
                $updateValues[] = $enf;
                
                $types = '';
                foreach ($updateValues as $v) {
                    if (is_int($v)) $types .= 'i';
                    elseif (is_float($v) || is_double($v)) $types .= 'd';
                    elseif (is_null($v)) $types .= 's';
                    else $types .= 's';
                }
                
                if (!@$stmtUpdate->bind_param($types, ...$updateValues)) {
                    throw new Exception('Error bind UPDATE: ' . $conexion->error);
                }
                
                if (!$stmtUpdate->execute()) {
                    throw new Exception('Error actualizando: ' . $stmtUpdate->error);
                }
            } else {
                // ✅ INSERTAR
                $placeholders = array_fill(0, count($filteredCols), '?');
                $sqlInsert = "INSERT INTO san_analisis_pollo_bb_adulto (" . implode(', ', $filteredCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                
                $stmtInsert = $conexion->prepare($sqlInsert);
                if (!$stmtInsert) {
                    throw new Exception('Error preparando INSERT: ' . $conexion->error);
                }
                
                $types = '';
                foreach ($filteredValues as $v) {
                    if (is_int($v)) $types .= 'i';
                    elseif (is_float($v) || is_double($v)) $types .= 'd';
                    elseif (is_null($v)) $types .= 's';
                    else $types .= 's';
                }
                
                if (!@$stmtInsert->bind_param($types, ...$filteredValues)) {
                    throw new Exception('Error bind INSERT: ' . $conexion->error);
                }
                
                if (!$stmtInsert->execute()) {
                    throw new Exception('Error insertando: ' . $stmtInsert->error);
                }
            }
            
            $enfermedadesActualizadas++;
        }
        
        // ARCHIVOS (igual que create)
        $archivos_guardados = 0;
        
        if (isset($_FILES['archivoPdf']) && !empty($_FILES['archivoPdf']['name'][0])) {
            $uploadDir = '../uploads/resultados/';
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $qPos = "SELECT posSolicitud FROM san_fact_solicitud_det WHERE codEnvio = ? LIMIT 1";
            $stmtPos = $conexion->prepare($qPos);
            $stmtPos->bind_param("s", $cod);
            $stmtPos->execute();
            $resPos = $stmtPos->get_result()->fetch_assoc();
            $posSolicitud = $resPos['posSolicitud'] ?? 1;
            
            $qArch = "INSERT INTO san_fact_resultado_archivo (codEnvio, posSolicitud, archRuta, tipo, fechaRegistro) VALUES (?, ?, ?, 'cuantitativo', NOW())";
            $stmtArch = $conexion->prepare($qArch);
            
            if(!$stmtArch) {
                throw new Exception('Error preparando archivos: ' . $conexion->error);
            }
            
            $archivos = $_FILES['archivoPdf'];
            $totalArchivos = count($archivos['name']);
            
            for ($i = 0; $i < $totalArchivos; $i++) {
                if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                    $nombreOriginal = basename($archivos['name'][$i]);
                    $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
                    $nombreSinExt = pathinfo($nombreOriginal, PATHINFO_FILENAME);
                    
                    $nombreFinal = $cod . '_' . $posSolicitud . '_' . $nombreSinExt . '_' . time() . '.' . $extension;
                    $rutaCompleta = $uploadDir . $nombreFinal;
                    $rutaRelativa = 'uploads/resultados/' . $nombreFinal;
                    
                    if ($archivos['size'][$i] > 10 * 1024 * 1024) {
                        throw new Exception("Archivo muy grande: {$nombreOriginal}");
                    }
                    
                    $extensionesPermitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'png', 'jpg', 'jpeg'];
                    if (!in_array(strtolower($extension), $extensionesPermitidas)) {
                        throw new Exception("Extensión no permitida: {$extension}");
                    }
                    
                    if (move_uploaded_file($archivos['tmp_name'][$i], $rutaCompleta)) {
                        $stmtArch->bind_param("sis", $cod, $posSolicitud, $rutaRelativa);
                        
                        if ($stmtArch->execute()) {
                            $archivos_guardados++;
                        }
                    }
                }
            }
        }
        
        mysqli_commit($conexion);
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => "✅ Actualización exitosa\n$enfermedadesActualizadas enfermedades\n$archivos_guardados archivos",
            'codigo_envio' => $cod
        ]);
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        throw $e;
    }
}


} catch (Exception $e) {
    if (isset($conexion)) mysqli_rollback($conexion);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}



$conexion->close();

?>

