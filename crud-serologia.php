<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json; charset=UTF-8');
ob_start();

include_once '../conexion_grs_joya/conexion.php';
include_once 'historial_resultados.php';
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
    $posSolicitud = $_GET['posSolicitud'] ?? ''; // 
    $estado = strtolower(trim($_GET['estado'] ?? 'pendiente'));

    if (empty($codEnvio)) {
        throw new Exception('Código de envío vacío');
    }

    /* CODIGO ANTERIOR (no filtraba por posSolicitud)
    $q = "SELECT DISTINCT nomAnalisis as nombre, codAnalisis as codigo
          FROM san_fact_solicitud_det 
          WHERE codEnvio = ? 
          AND estado_cuanti IN ('pendiente', 'completado')
          ORDER BY codAnalisis";
    $stmt = $conexion->prepare($q);
    $stmt->bind_param("s", $codEnvio);
    */

    /* CODIGO ANTERIOR (no traía el campo enfermedad)
    // ✅ CORREGIDO: Filtrar por posSolicitud si se proporciona
    if (!empty($posSolicitud)) {
        $q = "SELECT DISTINCT nomAnalisis as nombre, codAnalisis as codigo
              FROM san_fact_solicitud_det 
              WHERE codEnvio = ? 
              AND posSolicitud = ?
              AND estado_cuanti IN ('pendiente', 'completado')
              ORDER BY codAnalisis";

        $stmt = $conexion->prepare($q);
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . $conexion->error);
        }
        $stmt->bind_param("si", $codEnvio, $posSolicitud);
    } else {
        // Mantener compatibilidad si no se envía posSolicitud
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
    }
    */

    // ✅ CORREGIDO: Filtrar por posSolicitud y hacer JOIN para obtener el nombre completo de enfermedad
    if (!empty($posSolicitud)) {
        $q = "SELECT DISTINCT d.nomAnalisis as nombre, d.codAnalisis as codigo, a.enfermedad
              FROM san_fact_solicitud_det d
              LEFT JOIN san_dim_analisis a ON d.codAnalisis = a.codigo
              WHERE d.codEnvio = ? 
              AND d.posSolicitud = ?
              AND d.estado_cuanti IN ('pendiente', 'completado')
              ORDER BY d.codAnalisis";

        $stmt = $conexion->prepare($q);
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . $conexion->error);
        }
        $stmt->bind_param("si", $codEnvio, $posSolicitud);
    } else {
        // Mantener compatibilidad si no se envía posSolicitud
        $q = "SELECT DISTINCT d.nomAnalisis as nombre, d.codAnalisis as codigo, a.enfermedad
              FROM san_fact_solicitud_det d
              LEFT JOIN san_dim_analisis a ON d.codAnalisis = a.codigo
              WHERE d.codEnvio = ? 
              AND d.estado_cuanti IN ('pendiente', 'completado')
              ORDER BY d.codAnalisis";

        $stmt = $conexion->prepare($q);
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . $conexion->error);
        }
        $stmt->bind_param("s", $codEnvio);
    }

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
        error_log('🔍 Obteniendo catálogo de enfermedades...');
        
        /* CODIGO ANTERIOR - mostraba todas las enfermedades incluyendo las que tienen enfermedad NULL
        $q = "SELECT codigo, nombre, enfermedad as enfermedad_completa
              FROM san_dim_analisis
              ORDER BY nombre";
        */
        
        //  Solo mostrar enfermedades que tienen el campo 'enfermedad' con valor (no NULL)
        $q = "SELECT codigo, nombre, enfermedad as enfermedad_completa
              FROM san_dim_analisis
              WHERE enfermedad IS NOT NULL AND enfermedad != ''
              ORDER BY nombre";

        $res = $conexion->query($q);

        if (!$res) {
            error_log('❌ Error en consulta catálogo: ' . $conexion->error);
            throw new Exception('Error en consulta catálogo: ' . $conexion->error);
        }

        $enfermedades = [];
        while ($row = $res->fetch_assoc()) {
            $enfermedades[] = $row;
        }

        error_log('✅ Se obtuvieron ' . count($enfermedades) . ' enfermedades');
        
        ob_end_clean();
        echo json_encode(['success' => true, 'enfermedades' => $enfermedades]);
        exit;
    } 
    // ==================== GET RESULTADOS GUARDADOS ====================
elseif ($action == 'get_resultados_guardados') {
    $codEnvio = $_GET['codEnvio'] ?? '';
    $posSolicitud = $_GET['posSolicitud'] ?? ''; // 
    $enfermedad = $_GET['enfermedad'] ?? '';
    
    if (empty($codEnvio) || empty($enfermedad)) {
        throw new Exception('Parámetros incompletos');
    }
    
    /* CODIGO ANTERIOR (no filtraba por posSolicitud)
    $q = "SELECT * FROM san_analisis_pollo_bb_adulto 
          WHERE codigo_envio = ? AND enfermedad = ? 
          LIMIT 1";
    $stmt = $conexion->prepare($q);
    $stmt->bind_param("ss", $codEnvio, $enfermedad);
    */

    // ✅ Filtrar por posSolicitud si se proporciona
    if (!empty($posSolicitud)) {
        $q = "SELECT * FROM san_analisis_pollo_bb_adulto 
              WHERE codigo_envio = ? AND posSolicitud = ? AND enfermedad = ? 
              LIMIT 1";
        
        $stmt = $conexion->prepare($q);
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . $conexion->error);
        }
        
        $stmt->bind_param("sis", $codEnvio, $posSolicitud, $enfermedad);
    } else {
        // Mantener compatibilidad si no se envía posSolicitud
        $q = "SELECT * FROM san_analisis_pollo_bb_adulto 
              WHERE codigo_envio = ? AND enfermedad = ? 
              LIMIT 1";
        
        $stmt = $conexion->prepare($q);
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . $conexion->error);
        }
        
        $stmt->bind_param("ss", $codEnvio, $enfermedad);
    }
    
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
        $posSolicitud = $_POST['posSolicitud'] ?? ''; // ✅ Recibir posSolicitud desde frontend
        $codAnalisis = $_POST['codAnalisis'] ?? '';
        $nomAnalisis = $_POST['nomAnalisis'] ?? '';
        $codRef = $_POST['codRef'] ?? '';
        $fecToma = $_POST['fecToma'] ?? '';
        $codMuestra = $_POST['codMuestra'] ?? 3;

        if (empty($codEnvio) || empty($codAnalisis) || empty($posSolicitud)) {
            throw new Exception('Datos incompletos');
        }

        /* CODIGO ANTERIOR (verificaba solo por codEnvio sin posSolicitud)
        $qCheck = "SELECT id FROM san_fact_solicitud_det WHERE codEnvio = ? AND codAnalisis = ?";
        $stmtCheck = $conexion->prepare($qCheck);
        $stmtCheck->bind_param("si", $codEnvio, $codAnalisis);
        */

        // ✅ Verificar si ya existe esta enfermedad en esta solicitud
        $qCheck = "SELECT id FROM san_fact_solicitud_det WHERE codEnvio = ? AND posSolicitud = ? AND codAnalisis = ?";
        $stmtCheck = $conexion->prepare($qCheck);
        $stmtCheck->bind_param("sii", $codEnvio, $posSolicitud, $codAnalisis);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        
        if ($resCheck->num_rows > 0) {
            throw new Exception('Esta enfermedad ya existe en la solicitud');
        }

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
        
        // ✅ REGISTRAR EN HISTORIAL
        $userHistorial = $_SESSION['usuario'] ?? 'SISTEMA';
        insertarHistorial(
            $conexion,
            $codEnvio,
            $posSolicitud,
            'agregar_enfermedad',
            null,
            "Enfermedad agregada: $nomAnalisis ($codAnalisis)",
            $userHistorial
        );

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Enfermedad agregada']);
        exit;
    }
    
    // ==================== ELIMINAR ENFERMEDAD DE SOLICITUD ====================
    elseif ($action == 'eliminar_enfermedad_solicitud') {
        $codEnvio = $_POST['codEnvio'] ?? '';
        $posSolicitud = $_POST['posSolicitud'] ?? '';
        $codAnalisis = $_POST['codAnalisis'] ?? '';
        $nomAnalisis = $_POST['nomAnalisis'] ?? '';

        if (empty($codEnvio) || empty($posSolicitud) || empty($codAnalisis)) {
            throw new Exception('Datos incompletos para eliminar');
        }

        /* CODIGO ANTERIOR - no permitía eliminar si tenía resultados
        // Verificar si tiene resultados guardados en san_analisis_pollo_bb_adulto
        $qCheck = "SELECT id FROM san_analisis_pollo_bb_adulto 
                   WHERE codigo_envio = ? AND pos_solicitud = ? AND nombre_analisis = ?";
        $stmtCheck = $conexion->prepare($qCheck);
        $stmtCheck->bind_param("sis", $codEnvio, $posSolicitud, $nomAnalisis);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        
        if ($resCheck->num_rows > 0) {
            throw new Exception('No se puede eliminar: ya tiene resultados guardados');
        }
        */

        //  Primero eliminar resultados guardados si existen
        $resultadosEliminados = 0;
        $qDeleteResultados = "DELETE FROM san_analisis_pollo_bb_adulto 
                              WHERE codigo_envio = ? AND pos_solicitud = ? AND nombre_analisis = ?";
        $stmtDeleteResultados = $conexion->prepare($qDeleteResultados);
        if ($stmtDeleteResultados) {
            $stmtDeleteResultados->bind_param("sis", $codEnvio, $posSolicitud, $nomAnalisis);
            $stmtDeleteResultados->execute();
            $resultadosEliminados = $stmtDeleteResultados->affected_rows;
        }

        // Eliminar de san_fact_solicitud_det
        $qDelete = "DELETE FROM san_fact_solicitud_det 
                    WHERE codEnvio = ? AND posSolicitud = ? AND codAnalisis = ?";
        $stmtDelete = $conexion->prepare($qDelete);
        if (!$stmtDelete) {
            throw new Exception('Error preparando DELETE: ' . $conexion->error);
        }
        $stmtDelete->bind_param("sis", $codEnvio, $posSolicitud, $codAnalisis);
        
        if (!$stmtDelete->execute()) {
            throw new Exception('Error al eliminar: ' . $stmtDelete->error);
        }

        if ($stmtDelete->affected_rows === 0) {
            throw new Exception('No se encontró la enfermedad para eliminar');
        }

        // ✅ REGISTRAR EN HISTORIAL
        $userHistorial = $_SESSION['usuario'] ?? 'SISTEMA';
        $detalleHistorial = "Enfermedad eliminada: $nomAnalisis ($codAnalisis)";
        if ($resultadosEliminados > 0) {
            $detalleHistorial .= " - Se eliminaron $resultadosEliminados resultado(s) guardado(s)";
        }
        insertarHistorial(
            $conexion,
            $codEnvio,
            $posSolicitud,
            'eliminar_enfermedad',
            null,
            $detalleHistorial,
            $userHistorial
        );

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Enfermedad eliminada']);
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
        // 🔍 LOG: Verificar archivos recibidos
        error_log("📥 FILES recibidos: " . print_r($_FILES, true));
        
        mysqli_begin_transaction($conexion);
        
        try {
            $tipo = $_POST['tipo_ave'] ?? '';
            $cod = $_POST['codigo_solicitud'] ?? '';
            $fec = $_POST['fecha_toma'] ?? '';
            $posSolicitud = $_POST['posSolicitud'] ?? 1;  //  Recibir desde frontend

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
            $fechaRegistroLab = isset($_POST['fecha_registro_lab']) && $_POST['fecha_registro_lab'] !== '' ? $_POST['fecha_registro_lab'] : NULL;
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
                
                // ✅ Construir codRef completo
                $codRefCompleto = str_pad($granja ?? '', 3, '0', STR_PAD_LEFT) 
                                . str_pad($camp ?? '', 3, '0', STR_PAD_LEFT) 
                                . str_pad($galp ?? '', 2, '0', STR_PAD_LEFT) 
                                . str_pad($edad ?? '', 2, '0', STR_PAD_LEFT);
                
                // Construir columnas base - ✅ AGREGADO posSolicitud, codRef y fecha_registro_lab
                $baseCols = [
                    'codigo_envio','posSolicitud','codRef','fecha_toma_muestra','edad_aves','tipo_ave',
                    'planta_incubacion','lote','codigo_granja','codigo_campana',
                    'numero_galpon','edad_reproductora','condicion','estado',
                    'numero_informe','fecha_informe','fecha_registro_lab','usuario_registro','fecha_solicitud',
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
                    $cod, $posSolicitud, $codRefCompleto, $fec, $edad, $tipo,
                    $planta, $lote, $granja, $camp,
                    $galp, $edRep, $cond, $est,
                    $inf, $fecInf, $fechaRegistroLab, $user,
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
            
            error_log("🔍 Verificando archivos...");
            error_log("🔍 isset(\$_FILES['archivoPdf']): " . (isset($_FILES['archivoPdf']) ? 'SI' : 'NO'));
            
            if (isset($_FILES['archivoPdf'])) {
                error_log("🔍 \$_FILES['archivoPdf']: " . print_r($_FILES['archivoPdf'], true));
                error_log("🔍 empty(\$_FILES['archivoPdf']['name'][0]): " . (empty($_FILES['archivoPdf']['name'][0]) ? 'SI (vacío)' : 'NO (tiene archivos)'));
            }
            
            if (isset($_FILES['archivoPdf']) && !empty($_FILES['archivoPdf']['name'][0])) {
                $uploadDir = 'uploads/resultados/';
                
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // ✅ USAR posSolicitud QUE YA TENEMOS
                // ✅ PREPARAR CONSULTA PARA VERIFICAR SI YA EXISTE
                $qCheckArch = "SELECT id FROM san_fact_resultado_archivo WHERE codEnvio = ? AND posSolicitud = ? AND archRuta LIKE ? LIMIT 1";
                $stmtCheckArch = $conexion->prepare($qCheckArch);
                
                $qArch = "INSERT INTO san_fact_resultado_archivo (codEnvio, posSolicitud, archRuta, tipo, fechaRegistro, usuarioRegistrador) VALUES (?, ?, ?, 'cuantitativo', NOW(), ?)";
                $stmtArch = $conexion->prepare($qArch);
                
                if(!$stmtArch || !$stmtCheckArch) {
                    throw new Exception('Error preparando INSERT archivos: ' . $conexion->error);
                }
                
                $archivos = $_FILES['archivoPdf'];
                $totalArchivos = count($archivos['name']);
                
                error_log("📦 [CREATE] Total de archivos a procesar: $totalArchivos");
                
                for ($i = 0; $i < $totalArchivos; $i++) {
                    error_log("📄 [CREATE] Procesando archivo #$i: " . $archivos['name'][$i]);
                    error_log("   - Error code: " . $archivos['error'][$i]);
                    error_log("   - Size: " . $archivos['size'][$i] . " bytes");
                    
                    if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                        $nombreOriginal = basename($archivos['name'][$i]);
                        $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
                        $nombreSinExt = pathinfo($nombreOriginal, PATHINFO_FILENAME);
                        
                        // ✅ VERIFICAR SI YA EXISTE UN ARCHIVO CON NOMBRE SIMILAR
                        $patronBusqueda = 'uploads/resultados/' . $cod . '_' . $posSolicitud . '_' . $nombreSinExt . '%';
                        $stmtCheckArch->bind_param("sis", $cod, $posSolicitud, $patronBusqueda);
                        $stmtCheckArch->execute();
                        $resCheckArch = $stmtCheckArch->get_result();
                        
                        if ($resCheckArch->num_rows > 0) {
                            error_log("⚠️ [CREATE] Archivo ya existe en BD, omitiendo: $nombreOriginal");
                            continue; // ⛔ SALTAR si ya existe
                        }
                        
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
                            error_log("✅ Archivo movido exitosamente a: $rutaCompleta");
                            
                            $stmtArch->bind_param("siss", $cod, $posSolicitud, $rutaRelativa, $user);
                            
                            if ($stmtArch->execute()) {
                                $archivos_guardados++;
                                error_log("✅ Ruta guardada en BD: $rutaRelativa");
                            } else {
                                throw new Exception('Error guardando ruta en BD: ' . $stmtArch->error);
                            }
                        } else {
                            error_log("❌ Error al mover archivo: {$nombreOriginal} a {$rutaCompleta}");
                            throw new Exception("Error al mover archivo: {$nombreOriginal}");
                        }
                    }
                }
                
                error_log("📊 [CREATE] Total de archivos guardados: $archivos_guardados");
            }
            
            /* CODIGO ANTERIOR
            // ACTUALIZAR ESTADO
            $updateQuery = "UPDATE san_fact_solicitud_det SET estado_cuanti = 'completado' WHERE codEnvio = ? AND posSolicitud = ?";
            $stmtUpdate = $conexion->prepare($updateQuery);
            $stmtUpdate->bind_param("si", $cod, $posSolicitud);
            $stmtUpdate->execute();
            */
            
            // ACTUALIZAR ESTADO (según lo seleccionado en el modal)
            $estadoCuanti = $_POST['estadoCuanti'] ?? 'completado';
            // Validar que sea un valor válido
            if (!in_array($estadoCuanti, ['pendiente', 'completado'])) {
                $estadoCuanti = 'completado';
            }
            $updateQuery = "UPDATE san_fact_solicitud_det SET estado_cuanti = ? WHERE codEnvio = ? AND posSolicitud = ?";
            $stmtUpdate = $conexion->prepare($updateQuery);
            $stmtUpdate->bind_param("ssi", $estadoCuanti, $cod, $posSolicitud);
            $stmtUpdate->execute();
            
            // ✅ REGISTRAR EN HISTORIAL
            $enfermedadesLista = implode(', ', $_POST['enfermedades']);
            $comentarioHistorial = "Enfermedades: $enfermedadesLista. Archivos: $archivos_guardados";
            insertarHistorial(
                $conexion,
                $cod,
                $posSolicitud,
                'registro_resultados_cuantitativos',
                'cuantitativo',
                $comentarioHistorial,
                $user,
                'Laboratorio'
            );
            
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
        $posSolicitud = $_POST['posSolicitud'] ?? 1;  //  Recibir desde frontend

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
        $fechaRegistroLab = isset($_POST['fecha_registro_lab']) && $_POST['fecha_registro_lab'] !== '' ? $_POST['fecha_registro_lab'] : NULL;
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
            
            // ✅ Construir codRef completo
            $codRefCompleto = str_pad($granja ?? '', 3, '0', STR_PAD_LEFT) 
                            . str_pad($camp ?? '', 3, '0', STR_PAD_LEFT) 
                            . str_pad($galp ?? '', 2, '0', STR_PAD_LEFT) 
                            . str_pad($edad ?? '', 2, '0', STR_PAD_LEFT);
            
            // ✅ AGREGADO posSolicitud, codRef y fecha_registro_lab
            $baseCols = [
                'codigo_envio','posSolicitud','codRef','fecha_toma_muestra','edad_aves','tipo_ave',
                'planta_incubacion','lote','codigo_granja','codigo_campana',
                'numero_galpon','edad_reproductora','condicion','estado',
                'numero_informe','fecha_informe','fecha_registro_lab','usuario_registro','fecha_solicitud',
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
                $cod, $posSolicitud, $codRefCompleto, $fec, $edad, $tipo,
                $planta, $lote, $granja, $camp,
                $galp, $edRep, $cond, $est,
                $inf, $fecInf, $fechaRegistroLab, $user,
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

            // ✅ VERIFICAR incluyendo posSolicitud para no confundir solicitudes
            $checkQuery = "SELECT codigo_envio FROM san_analisis_pollo_bb_adulto WHERE codigo_envio = ? AND posSolicitud = ? AND enfermedad = ? LIMIT 1";
            $stmtCheck = $conexion->prepare($checkQuery);
            
            if (!$stmtCheck) {
                throw new Exception('Error preparando SELECT: ' . $conexion->error);
            }
            
            $stmtCheck->bind_param("sis", $cod, $posSolicitud, $enf);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            
            if ($resCheck->num_rows > 0) {
                // ✅ ACTUALIZAR - incluyendo posSolicitud en WHERE
                $updateParts = [];
                foreach ($filteredCols as $colName) {
                    if ($colName !== 'codigo_envio' && $colName !== 'enfermedad' && $colName !== 'posSolicitud') {
                        $updateParts[] = "$colName = ?";
                    }
                }
                
                $sqlUpdate = "UPDATE san_analisis_pollo_bb_adulto SET " . implode(', ', $updateParts) . " WHERE codigo_envio = ? AND posSolicitud = ? AND enfermedad = ?";
                
                $stmtUpdate = $conexion->prepare($sqlUpdate);
                if (!$stmtUpdate) {
                    throw new Exception('Error preparando UPDATE: ' . $conexion->error);
                }
                
                $updateValues = [];
                foreach ($filteredCols as $idx => $colName) {
                    if ($colName !== 'codigo_envio' && $colName !== 'enfermedad' && $colName !== 'posSolicitud') {
                        $updateValues[] = $filteredValues[$idx];
                    }
                }
                $updateValues[] = $cod;
                $updateValues[] = $posSolicitud;
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
        
        // ARCHIVOS - SOLO AGREGAR NUEVOS (NO DUPLICAR)
        $archivos_guardados = 0;
        
        error_log("🔄 [UPDATE] Verificando archivos...");
        error_log("🔄 [UPDATE] isset(\$_FILES['archivoPdf']): " . (isset($_FILES['archivoPdf']) ? 'SI' : 'NO'));
        
        if (isset($_FILES['archivoPdf'])) {
            error_log("🔄 [UPDATE] \$_FILES['archivoPdf']: " . print_r($_FILES['archivoPdf'], true));
            error_log("🔄 [UPDATE] empty(\$_FILES['archivoPdf']['name'][0]): " . (empty($_FILES['archivoPdf']['name'][0]) ? 'SI (vacío)' : 'NO (tiene archivos)'));
        }
        
        if (isset($_FILES['archivoPdf']) && !empty($_FILES['archivoPdf']['name'][0])) {
            $uploadDir = 'uploads/resultados/';
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // ✅ USAR posSolicitud QUE YA TENEMOS
            // ✅ PREPARAR CONSULTA PARA VERIFICAR SI YA EXISTE
            $qCheckArch = "SELECT id FROM san_fact_resultado_archivo WHERE codEnvio = ? AND posSolicitud = ? AND archRuta LIKE ? LIMIT 1";
            $stmtCheckArch = $conexion->prepare($qCheckArch);
            
            $qArch = "INSERT INTO san_fact_resultado_archivo (codEnvio, posSolicitud, archRuta, tipo, fechaRegistro, usuarioRegistrador) VALUES (?, ?, ?, 'cuantitativo', NOW(), ?)";
            $stmtArch = $conexion->prepare($qArch);
            
            if(!$stmtArch || !$stmtCheckArch) {
                throw new Exception('Error preparando archivos: ' . $conexion->error);
            }
            
            $archivos = $_FILES['archivoPdf'];
            $totalArchivos = count($archivos['name']);
            
            error_log("📦 [UPDATE] Total de archivos a procesar: $totalArchivos");
            
            for ($i = 0; $i < $totalArchivos; $i++) {
                error_log("📄 [UPDATE] Procesando archivo #$i: " . $archivos['name'][$i]);
                error_log("   - Error code: " . $archivos['error'][$i]);
                error_log("   - Size: " . $archivos['size'][$i] . " bytes");
                
                if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                    $nombreOriginal = basename($archivos['name'][$i]);
                    $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
                    $nombreSinExt = pathinfo($nombreOriginal, PATHINFO_FILENAME);
                    
                    // ✅ VERIFICAR SI YA EXISTE UN ARCHIVO CON NOMBRE SIMILAR
                    $patronBusqueda = 'uploads/resultados/' . $cod . '_' . $posSolicitud . '_' . $nombreSinExt . '%';
                    $stmtCheckArch->bind_param("sis", $cod, $posSolicitud, $patronBusqueda);
                    $stmtCheckArch->execute();
                    $resCheckArch = $stmtCheckArch->get_result();
                    
                    if ($resCheckArch->num_rows > 0) {
                        error_log("⚠️ [UPDATE] Archivo ya existe en BD, omitiendo: $nombreOriginal");
                        continue; // ⛔ SALTAR si ya existe
                    }
                    
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
                        error_log("✅ [UPDATE] Archivo movido exitosamente a: $rutaCompleta");
                        
                        $stmtArch->bind_param("siss", $cod, $posSolicitud, $rutaRelativa, $user);
                        
                        if ($stmtArch->execute()) {
                            $archivos_guardados++;
                            error_log("✅ [UPDATE] Ruta guardada en BD: $rutaRelativa");
                        }
                    } else {
                        error_log("❌ [UPDATE] Error al mover archivo: {$nombreOriginal} a {$rutaCompleta}");
                    }
                }
            }
            
            error_log("📊 [UPDATE] Total de archivos guardados: $archivos_guardados");
        }
        
        // ACTUALIZAR ESTADO CUANTITATIVO (según lo seleccionado en el modal)
        $estadoCuanti = $_POST['estadoCuanti'] ?? 'completado';
        // Validar que sea un valor válido
        if (!in_array($estadoCuanti, ['pendiente', 'completado'])) {
            $estadoCuanti = 'completado';
        }
        $updateEstadoQuery = "UPDATE san_fact_solicitud_det SET estado_cuanti = ? WHERE codEnvio = ? AND posSolicitud = ?";
        $stmtUpdateEstado = $conexion->prepare($updateEstadoQuery);
        $stmtUpdateEstado->bind_param("ssi", $estadoCuanti, $cod, $posSolicitud);
        $stmtUpdateEstado->execute();
        
        // ✅ REGISTRAR EN HISTORIAL
        $enfermedadesLista = implode(', ', $_POST['enfermedades']);
        $comentarioHistorial = "Actualizado: $enfermedadesLista. Archivos nuevos: $archivos_guardados. Estado: $estadoCuanti";
        insertarHistorial(
            $conexion,
            $cod,
            $posSolicitud,
            'actualizacion_resultados_cuantitativos',
            'cuantitativo',
            $comentarioHistorial,
            $user
        );
        
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

// ==================== REEMPLAZAR ARCHIVO ====================
elseif ($action == 'reemplazar_archivo') {
    try {
        $idArchivo = $_POST['idArchivo'] ?? '';
        $codigoEnvio = $_POST['codigoEnvio'] ?? '';
        
        if (empty($idArchivo) || !isset($_FILES['archivo'])) {
            throw new Exception('Datos incompletos');
        }
        
        // Obtener archivo anterior
        $qArch = "SELECT archRuta FROM san_fact_resultado_archivo WHERE id = ?";
        $stmtArch = $conexion->prepare($qArch);
        $stmtArch->bind_param("i", $idArchivo);
        $stmtArch->execute();
        $resArch = $stmtArch->get_result()->fetch_assoc();
        
        if (!$resArch) {
            throw new Exception('Archivo no encontrado');
        }
        
        $rutaAnterior = $resArch['archRuta'];
        
        // Borrar archivo anterior
        if (file_exists($rutaAnterior)) {
            @unlink($rutaAnterior);
            error_log("🗑️ Archivo anterior eliminado: $rutaAnterior");
        }
        
        // Procesar nuevo archivo
        $file = $_FILES['archivo'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir archivo: ' . $file['error']);
        }
        
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('Archivo supera 10 MB');
        }
        
        $nombreOriginal = basename($file['name']);
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $permitidos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'png', 'jpg', 'jpeg'];
        
        if (!in_array($extension, $permitidos)) {
            throw new Exception("Extensión no permitida: $extension");
        }
        
        $uploadDir = 'uploads/resultados/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $nombreFinal = $codigoEnvio . '_1_' . time() . '_' . pathinfo($nombreOriginal, PATHINFO_FILENAME) . '.' . $extension;
        $rutaCompleta = $uploadDir . $nombreFinal;
        $rutaRelativa = 'uploads/resultados/' . $nombreFinal;
        
        if (!move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            throw new Exception('Error al mover archivo');
        }
        
        error_log("✅ Archivo movido exitosamente a: $rutaCompleta");
        
        // Actualizar BD con usuario
        $user = $_SESSION['usuario'] ?? 'SISTEMA';
        $qUpd = "UPDATE san_fact_resultado_archivo SET archRuta = ?, fechaRegistro = NOW(), usuarioRegistrador = ? WHERE id = ?";
        $stmtUpd = $conexion->prepare($qUpd);
        $stmtUpd->bind_param("ssi", $rutaRelativa, $user, $idArchivo);
        
        if (!$stmtUpd->execute()) {
            throw new Exception('Error actualizando BD: ' . $stmtUpd->error);
        }
        
        // ✅ REGISTRAR EN HISTORIAL
        $posSolicitud = $_POST['posSolicitud'] ?? 1;
        insertarHistorial(
            $conexion,
            $codigoEnvio,
            $posSolicitud,
            'reemplazo_archivo',
            'cuantitativo',
            "Archivo reemplazado: $nombreOriginal",
            $user
        );
        
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Archivo reemplazado correctamente']);
        exit;
        
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
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


