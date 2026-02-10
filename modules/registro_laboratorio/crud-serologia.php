<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json; charset=UTF-8');
ob_start();

include_once '../../../conexion_grs_joya/conexion.php';
include_once '../../includes/historial_resultados.php';
$conexion = conectar_joya();

if (!$conexion) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error de conexión a BD']);
    exit();
}

// Generador de UUID v4 (usado para columnas `id` en tablas que lo requieren)
if (!function_exists('generar_uuid_v4')) {
    function generar_uuid_v4()
    {
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

    // ==================== GET ENFERMEDADES (VERSIÓN ROBUSTA SIN UNION SQL) ====================
    if ($action == 'get_enfermedades') {
        $codEnvio = $_GET['codEnvio'] ?? '';
        $posSolicitud = $_GET['posSolicitud'] ?? '';
        $estado = strtolower(trim($_GET['estado'] ?? 'pendiente'));

        if (empty($codEnvio)) {
            throw new Exception('Código de envío vacío');
        }

        $listaEnfermedades = []; // Usaremos esto para evitar duplicados usando el código como clave

        // -----------------------------------------------------------------------
        // CONSULTA 1: Lo planificado (Tabla Solicitud)
        // -----------------------------------------------------------------------
        $sqlA = "SELECT d.nomAnalisis as nombre, d.codAnalisis as codigo, IFNULL(a.enfermedad, d.nomAnalisis) as enfermedad_real
                 FROM san_fact_solicitud_det d
                 LEFT JOIN san_dim_analisis a ON d.codAnalisis = a.codigo
                 WHERE d.codEnvio = ? ";

        $typesA = "s";
        $paramsA = [$codEnvio];

        if (!empty($posSolicitud)) {
            $sqlA .= " AND d.posSolicitud = ? ";
            $typesA .= "i";
            $paramsA[] = $posSolicitud;
        }
        $sqlA .= " AND d.estado_cuanti IN ('pendiente', 'completado') ";

        $stmtA = $conexion->prepare($sqlA);
        if ($stmtA) {
            $stmtA->bind_param($typesA, ...$paramsA);
            $stmtA->execute();
            $resA = $stmtA->get_result();
            while ($row = $resA->fetch_assoc()) {
                // Usamos el código como clave para que sea único
                $clave = $row['codigo'];
                $listaEnfermedades[$clave] = [
                    'nombre' => $row['nombre'],
                    'codigo' => $row['codigo'],
                    'enfermedad' => $row['enfermedad_real']
                ];
            }
            $stmtA->close();
        }

        // -----------------------------------------------------------------------
        // CONSULTA 2: Lo extra guardado (Tabla Resultados)
        // -----------------------------------------------------------------------
        // Esta consulta traerá la "IA" (registro 772) que está en resultados pero no en solicitud
        $sqlB = "SELECT r.enfermedad as nombre, r.codigo_enfermedad as codigo, r.enfermedad as enfermedad_real
                 FROM san_analisis_pollo_bb_adulto r
                 WHERE r.codigo_envio = ? ";

        $typesB = "s";
        $paramsB = [$codEnvio];

        if (!empty($posSolicitud)) {
            $sqlB .= " AND r.posSolicitud = ? ";
            $typesB .= "i";
            $paramsB[] = $posSolicitud;
        }

        $stmtB = $conexion->prepare($sqlB);
        if ($stmtB) {
            $stmtB->bind_param($typesB, ...$paramsB);
            $stmtB->execute();
            $resB = $stmtB->get_result();
            while ($row = $resB->fetch_assoc()) {
                // Si ya existe (ej. BI o LT), lo sobrescribimos o ignoramos.
                // Si es nuevo (ej. AI), se agrega al array.
                $clave = $row['codigo'];
                $listaEnfermedades[$clave] = [
                    'nombre' => $row['nombre'],
                    'codigo' => $row['codigo'],
                    'enfermedad' => $row['enfermedad_real']
                ];
            }
            $stmtB->close();
        }

        // Convertir el array asociativo a array indexado simple para el JSON
        $resultadoFinal = array_values($listaEnfermedades);

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'enfermedades' => $resultadoFinal,
            'total' => count($resultadoFinal),
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


    // ==================== AGREGAR ENFERMEDAD A SOLICITUD (SOLO VISUAL) ====================
    elseif ($action == 'agregar_enfermedad_solicitud') {
        // Solo recibimos datos para validar sesión o integridad básica
        $codEnvio = $_POST['codEnvio'] ?? '';
        $codAnalisis = $_POST['codAnalisis'] ?? '';

        if (empty($codEnvio) || empty($codAnalisis)) {
            throw new Exception('Datos incompletos');
        }

        // GENERAMOS UN ID TEMPORAL ALEATORIO (No se guardará en BD)
        $uuid_det = generar_uuid_v4();

        // ----------------------------------------------------------------------
        // IMPORTANTE: COMENTAMOS EL INSERT PARA QUE NO TOQUE LA BD
        // ----------------------------------------------------------------------
        /*
        $qInsert = "INSERT INTO san_fact_solicitud_det ...";
        $stmtInsert = $conexion->prepare($qInsert);
        $stmtInsert->execute();
        
        insertarHistorial(...); // Tampoco guardamos historial todavía
        */
        // ----------------------------------------------------------------------

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Enfermedad agregada (Visualmente)',
            'id_temporal' => $uuid_det // Devolvemos esto por si el JS lo necesita
        ]);
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

    // BLOQUE 1: CREATE (GUARDAR NUEVO)
    elseif ($action == 'create') {
        error_log("📥 [CREATE] Iniciando guardado...");
        mysqli_begin_transaction($conexion);

        try {
            // --- 1. Recibir Datos Generales ---
            $tipo = $_POST['tipo_ave'] ?? '';
            $cod = $_POST['codigo_solicitud'] ?? '';
            $fec = $_POST['fecha_toma'] ?? '';
            $posSolicitud = $_POST['posSolicitud'] ?? 1;

            // Saneamiento edad
            $edad_raw = $_POST['edad_aves'] ?? '';
            $edad = 0;
            if ($edad_raw !== '') {
                $digits = preg_replace('/\D/', '', (string)$edad_raw);
                if ($digits !== '') {
                    $edad = (strlen($digits) > 3) ? (int)substr($digits, -2) : (int)$digits;
                }
            }

            if (empty($cod)) throw new Exception('Código de solicitud requerido');

            // Otros campos
            $planta = $_POST['planta_incubacion'] ?? NULL;
            $lote = $_POST['lote'] ?? NULL;
            $granja = $_POST['codigo_granja'] ?? NULL;
            $camp = $_POST['codigo_campana'] ?? NULL;
            $galp = preg_replace('/\D/', '', $_POST['numero_galpon'] ?? '') ?: null;
            $edRep = preg_replace('/\D/', '', $_POST['edad_reproductora'] ?? '') ?: null;
            $cond = $_POST['condicion'] ?? '';
            $est = $_POST['estado'] ?? 'ANALIZADO';
            $inf = $_POST['numero_informe'] ?? '';
            $fecInf = $_POST['fecha_informe'] ?? NULL;
            $fechaRegistroLab = !empty($_POST['fecha_registro_lab']) ? $_POST['fecha_registro_lab'] : NULL;
            $user = $_SESSION['usuario'] ?? 'SISTEMA';

            if (!isset($_POST['enfermedades']) || !is_array($_POST['enfermedades'])) {
                throw new Exception('No se encontraron enfermedades para guardar');
            }

            // --- 2. Mapeo de Códigos ---
            $nameToCode = [];
            $receivedNames = $_POST['enfermedades'];
            $stmtGetCode = $conexion->prepare("SELECT codigo FROM san_dim_analisis WHERE nombre = ? LIMIT 1");
            foreach ($receivedNames as $nm) {
                if ($stmtGetCode) {
                    $stmtGetCode->bind_param('s', $nm);
                    $stmtGetCode->execute();
                    $resCode = $stmtGetCode->get_result();
                    if ($rowc = $resCode->fetch_assoc()) {
                        $nameToCode[$nm] = $rowc['codigo'];
                    }
                }
            }
            if ($stmtGetCode) $stmtGetCode->close();

            $enfermedadesGuardadas = 0;
            $enfermedadesRecibidas = array_unique($_POST['enfermedades']);
            $enfermedadesProcesadas = [];

            // --- 3. Procesar Enfermedades ---
            foreach ($enfermedadesRecibidas as $enf) {
                $enf = trim($enf);
                if (empty($enf) || in_array($enf, $enfermedadesProcesadas)) continue;

                // Verificar duplicado
                $stmtCheck = $conexion->prepare("SELECT id_analisis FROM san_analisis_pollo_bb_adulto WHERE codigo_envio = ? AND posSolicitud = ? AND enfermedad = ?");
                $stmtCheck->bind_param("sis", $cod, $posSolicitud, $enf);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows > 0) {
                    $stmtCheck->close();
                    continue;
                }
                $stmtCheck->close();

                // Datos Estadísticos
                $g = (isset($_POST[$enf . '_gmean']) && $_POST[$enf . '_gmean'] !== '') ? (float)$_POST[$enf . '_gmean'] : NULL;
                $c = (isset($_POST[$enf . '_cv']) && $_POST[$enf . '_cv'] !== '') ? (float)$_POST[$enf . '_cv'] : NULL;
                $s = (isset($_POST[$enf . '_sd']) && $_POST[$enf . '_sd'] !== '') ? (float)$_POST[$enf . '_sd'] : NULL;
                $cnt = isset($_POST[$enf . '_count']) ? (int)$_POST[$enf . '_count'] : 20;

                // CodRef
                $codRefCompleto = !empty($_POST['codRef_completo']) ? $_POST['codRef_completo']
                    : str_pad($granja ?? '', 3, '0', STR_PAD_LEFT) . str_pad($camp ?? '', 3, '0', STR_PAD_LEFT) . str_pad($galp ?? '', 2, '0', STR_PAD_LEFT) . str_pad($edad ?? '', 2, '0', STR_PAD_LEFT);

                // Columnas Base
                $baseCols = [
                    'codigo_envio',
                    'posSolicitud',
                    'codRef',
                    'fecha_toma_muestra',
                    'edad_aves',
                    'tipo_ave',
                    'planta_incubacion',
                    'lote',
                    'codigo_granja',
                    'codigo_campana',
                    'numero_galpon',
                    'edad_reproductora',
                    'condicion',
                    'estado',
                    'numero_informe',
                    'fecha_informe',
                    'fecha_registro_lab',
                    'usuario_registro',
                    'fecha_solicitud',
                    'enfermedad',
                    'codigo_enfermedad',
                    'gmean',
                    'cv',
                    'desviacion_estandar',
                    'count_muestras'
                ];

                // --- 4. LÓGICA DE NIVELES (0-24) ---
                $levelCols = [];
                $levelValues = [];
                for ($i = 0; $i <= 24; $i++) {
                    $levelCols[] = 's0' . $i; // DB: s00..s024
                    $postKey = $enf . '_s' . $i; // Frontend: ENF_s0..ENF_s24
                    $val = (isset($_POST[$postKey]) && $_POST[$postKey] !== '') ? (float)$_POST[$postKey] : NULL;
                    $levelValues[] = $val;
                }

                $allCols = array_merge($baseCols, $levelCols);
                $codigo_enf = $nameToCode[$enf] ?? null;

                $values = [
                    $cod,
                    $posSolicitud,
                    $codRefCompleto,
                    $fec,
                    $edad,
                    $tipo,
                    $planta,
                    $lote,
                    $granja,
                    $camp,
                    $galp,
                    $edRep,
                    $cond,
                    $est,
                    $inf,
                    $fecInf,
                    $fechaRegistroLab,
                    $user,
                    null,
                    $enf,
                    $codigo_enf,
                    $g,
                    $c,
                    $s,
                    $cnt
                ];
                foreach ($levelValues as $lv) $values[] = $lv;

                // Insertar (Filtrando columnas válidas)
                $existingCols = [];
                $resCols = $conexion->query("SHOW COLUMNS FROM san_analisis_pollo_bb_adulto");
                while ($rc = $resCols->fetch_assoc()) $existingCols[] = $rc['Field'];

                $filteredCols = [];
                $filteredValues = [];
                foreach ($allCols as $idx => $colName) {
                    if (in_array($colName, $existingCols, true)) {
                        $filteredCols[] = $colName;
                        $filteredValues[] = array_key_exists($idx, $values) ? $values[$idx] : null;
                    }
                }

                $placeholders = array_fill(0, count($filteredCols), '?');
                $sqlInsert = "INSERT INTO san_analisis_pollo_bb_adulto (" . implode(', ', $filteredCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmtInsert = $conexion->prepare($sqlInsert);
                if (!$stmtInsert) throw new Exception('Error prepare INSERT: ' . $conexion->error);

                $types = str_repeat('s', count($filteredValues));
                $stmtInsert->bind_param($types, ...$filteredValues);

                if (!$stmtInsert->execute()) throw new Exception('Error execute INSERT: ' . $stmtInsert->error);

                $enfermedadesProcesadas[] = $enf;
                $enfermedadesGuardadas++;
            }

            // --- 5. Archivos ---
            $archivos_guardados = 0;
            if (isset($_FILES['archivoPdf']) && !empty($_FILES['archivoPdf']['name'][0])) {
                $uploadDir = '../../uploads/resultados/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

                $stmtArch = $conexion->prepare("INSERT INTO san_fact_resultado_archivo (codEnvio, posSolicitud, archRuta, tipo, fechaRegistro, usuarioRegistrador) VALUES (?, ?, ?, 'cuantitativo', NOW(), ?)");
                $archivos = $_FILES['archivoPdf'];

                for ($i = 0; $i < count($archivos['name']); $i++) {
                    if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($archivos['name'][$i], PATHINFO_EXTENSION);
                        $nombreFinal = $cod . '_' . $posSolicitud . '_' . pathinfo($archivos['name'][$i], PATHINFO_FILENAME) . '_' . time() . '.' . $ext;

                        if (move_uploaded_file($archivos['tmp_name'][$i], $uploadDir . $nombreFinal)) {
                            $rutaRel = 'uploads/resultados/' . $nombreFinal;
                            $stmtArch->bind_param("siss", $cod, $posSolicitud, $rutaRel, $user);
                            if ($stmtArch->execute()) $archivos_guardados++;
                        }
                    }
                }
            }

            // Actualizar estado y cerrar
            $stmtUpd = $conexion->prepare("UPDATE san_fact_solicitud_det SET estado_cuanti = ? WHERE codEnvio = ? AND posSolicitud = ?");
            $estadoC = $_POST['estadoCuanti'] ?? 'completado';
            $stmtUpd->bind_param("ssi", $estadoC, $cod, $posSolicitud);
            $stmtUpd->execute();

            insertarHistorial($conexion, $cod, $posSolicitud, 'registro_resultados_cuantitativos', 'cuantitativo', "Enf: " . implode(',', $enfermedadesRecibidas), $user);

            mysqli_commit($conexion);
            echo json_encode(['success' => true, 'message' => "Guardado OK ($enfermedadesGuardadas enf, $archivos_guardados arch)"]);
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conexion);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // BLOQUE 2: UPDATE (ACTUALIZAR)
    elseif ($action == 'update') {
        error_log("🔄 [UPDATE] Iniciando actualización...");
        mysqli_begin_transaction($conexion);

        try {
            // --- 1. Recibir Datos Generales ---
            $tipo = $_POST['tipo_ave'] ?? '';
            $cod = $_POST['codigo_solicitud'] ?? '';
            $fec = $_POST['fecha_toma'] ?? '';
            $posSolicitud = $_POST['posSolicitud'] ?? 1;

            // Saneamiento edad
            $edad_raw = $_POST['edad_aves'] ?? '';
            $edad = 0;
            if ($edad_raw !== '') {
                $digits = preg_replace('/\D/', '', (string)$edad_raw);
                if ($digits !== '') {
                    $edad = (strlen($digits) > 3) ? (int)substr($digits, -2) : (int)$digits;
                }
            }

            if (empty($cod)) throw new Exception('Código de solicitud requerido');

            // Otros campos
            $planta = $_POST['planta_incubacion'] ?? NULL;
            $lote = $_POST['lote'] ?? NULL;
            $granja = $_POST['codigo_granja'] ?? NULL;
            $camp = $_POST['codigo_campana'] ?? NULL;
            $galp = preg_replace('/\D/', '', $_POST['numero_galpon'] ?? '') ?: null;
            $edRep = preg_replace('/\D/', '', $_POST['edad_reproductora'] ?? '') ?: null;
            $cond = $_POST['condicion'] ?? '';
            $est = $_POST['estado'] ?? 'ANALIZADO';
            $inf = $_POST['numero_informe'] ?? '';
            $fecInf = $_POST['fecha_informe'] ?? NULL;
            $fechaRegistroLab = !empty($_POST['fecha_registro_lab']) ? $_POST['fecha_registro_lab'] : NULL;
            $user = $_SESSION['usuario'] ?? 'SISTEMA';

            if (!isset($_POST['enfermedades']) || !is_array($_POST['enfermedades'])) {
                throw new Exception('No se encontraron enfermedades para actualizar');
            }

            // --- 2. Mapeo de Códigos ---
            $nameToCode = [];
            $receivedNames = $_POST['enfermedades'];
            $stmtGetCode = $conexion->prepare("SELECT codigo FROM san_dim_analisis WHERE nombre = ? LIMIT 1");
            foreach ($receivedNames as $nm) {
                if ($stmtGetCode) {
                    $stmtGetCode->bind_param('s', $nm);
                    $stmtGetCode->execute();
                    $resCode = $stmtGetCode->get_result();
                    if ($rowc = $resCode->fetch_assoc()) {
                        $nameToCode[$nm] = $rowc['codigo'];
                    }
                }
            }
            if ($stmtGetCode) $stmtGetCode->close();

            $enfermedadesActualizadas = 0;
            $enfermedadesRecibidas = array_unique($_POST['enfermedades']);
            $enfermedadesProcesadas = [];

            // --- 3. Procesar Enfermedades ---
            foreach ($enfermedadesRecibidas as $enf) {
                $enf = trim($enf);
                if (empty($enf) || in_array($enf, $enfermedadesProcesadas)) continue;

                // Datos Estadísticos
                $g = (isset($_POST[$enf . '_gmean']) && $_POST[$enf . '_gmean'] !== '') ? (float)$_POST[$enf . '_gmean'] : NULL;
                $c = (isset($_POST[$enf . '_cv']) && $_POST[$enf . '_cv'] !== '') ? (float)$_POST[$enf . '_cv'] : NULL;
                $s = (isset($_POST[$enf . '_sd']) && $_POST[$enf . '_sd'] !== '') ? (float)$_POST[$enf . '_sd'] : NULL;
                $cnt = isset($_POST[$enf . '_count']) ? (int)$_POST[$enf . '_count'] : 20;

                // CodRef
                $codRefCompleto = !empty($_POST['codRef_completo']) ? $_POST['codRef_completo']
                    : str_pad($granja ?? '', 3, '0', STR_PAD_LEFT) . str_pad($camp ?? '', 3, '0', STR_PAD_LEFT) . str_pad($galp ?? '', 2, '0', STR_PAD_LEFT) . str_pad($edad ?? '', 2, '0', STR_PAD_LEFT);

                // Columnas Base
                $baseCols = [
                    'codigo_envio',
                    'posSolicitud',
                    'codRef',
                    'fecha_toma_muestra',
                    'edad_aves',
                    'tipo_ave',
                    'planta_incubacion',
                    'lote',
                    'codigo_granja',
                    'codigo_campana',
                    'numero_galpon',
                    'edad_reproductora',
                    'condicion',
                    'estado',
                    'numero_informe',
                    'fecha_informe',
                    'fecha_registro_lab',
                    'usuario_registro',
                    'fecha_solicitud',
                    'enfermedad',
                    'codigo_enfermedad',
                    'gmean',
                    'cv',
                    'desviacion_estandar',
                    'count_muestras'
                ];

                // --- 4. LÓGICA DE NIVELES (0-24) ---
                $levelCols = [];
                $levelValues = [];
                for ($i = 0; $i <= 24; $i++) {
                    $levelCols[] = 's0' . $i; // DB: s00..s024
                    $postKey = $enf . '_s' . $i; // Frontend: ENF_s0..ENF_s24
                    $val = (isset($_POST[$postKey]) && $_POST[$postKey] !== '') ? (float)$_POST[$postKey] : NULL;
                    $levelValues[] = $val;
                }

                $allCols = array_merge($baseCols, $levelCols);
                $codigo_enf = $nameToCode[$enf] ?? null;

                $values = [
                    $cod,
                    $posSolicitud,
                    $codRefCompleto,
                    $fec,
                    $edad,
                    $tipo,
                    $planta,
                    $lote,
                    $granja,
                    $camp,
                    $galp,
                    $edRep,
                    $cond,
                    $est,
                    $inf,
                    $fecInf,
                    $fechaRegistroLab,
                    $user,
                    null,
                    $enf,
                    $codigo_enf,
                    $g,
                    $c,
                    $s,
                    $cnt
                ];
                foreach ($levelValues as $lv) $values[] = $lv;

                // Filtrar columnas válidas
                $existingCols = [];
                $resCols = $conexion->query("SHOW COLUMNS FROM san_analisis_pollo_bb_adulto");
                while ($rc = $resCols->fetch_assoc()) $existingCols[] = $rc['Field'];

                $filteredCols = [];
                $filteredValues = [];
                foreach ($allCols as $idx => $colName) {
                    if (in_array($colName, $existingCols, true)) {
                        $filteredCols[] = $colName;
                        $filteredValues[] = array_key_exists($idx, $values) ? $values[$idx] : null;
                    }
                }

                // Verificar si existe para UPDATE o INSERT
                $stmtCheck = $conexion->prepare("SELECT codigo_envio FROM san_analisis_pollo_bb_adulto WHERE codigo_envio = ? AND posSolicitud = ? AND enfermedad = ? LIMIT 1");
                $stmtCheck->bind_param("sis", $cod, $posSolicitud, $enf);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();

                if ($resCheck->num_rows > 0) {
                    // --- UPDATE ---
                    $updateParts = [];
                    foreach ($filteredCols as $colName) {
                        if (!in_array($colName, ['codigo_envio', 'enfermedad', 'posSolicitud'])) {
                            $updateParts[] = "$colName = ?";
                        }
                    }
                    $sqlUpdate = "UPDATE san_analisis_pollo_bb_adulto SET " . implode(', ', $updateParts) . " WHERE codigo_envio = ? AND posSolicitud = ? AND enfermedad = ?";
                    $stmtUpdate = $conexion->prepare($sqlUpdate);

                    $updateValues = [];
                    foreach ($filteredCols as $idx => $colName) {
                        if (!in_array($colName, ['codigo_envio', 'enfermedad', 'posSolicitud'])) {
                            $updateValues[] = $filteredValues[$idx];
                        }
                    }
                    $updateValues[] = $cod;
                    $updateValues[] = $posSolicitud;
                    $updateValues[] = $enf;

                    $types = str_repeat('s', count($updateValues));
                    $stmtUpdate->bind_param($types, ...$updateValues);
                    $stmtUpdate->execute();
                } else {
                    // --- INSERT (Para nuevas enfermedades en update) ---
                    $placeholders = array_fill(0, count($filteredCols), '?');
                    $sqlInsert = "INSERT INTO san_analisis_pollo_bb_adulto (" . implode(', ', $filteredCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    $stmtInsert = $conexion->prepare($sqlInsert);

                    $types = str_repeat('s', count($filteredValues));
                    $stmtInsert->bind_param($types, ...$filteredValues);
                    $stmtInsert->execute();
                }

                $enfermedadesProcesadas[] = $enf;
                $enfermedadesActualizadas++;
            }

            // --- 5. Archivos ---
            $archivos_guardados = 0;
            if (isset($_FILES['archivoPdf']) && !empty($_FILES['archivoPdf']['name'][0])) {
                $uploadDir = '../../uploads/resultados/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

                $stmtArch = $conexion->prepare("INSERT INTO san_fact_resultado_archivo (codEnvio, posSolicitud, archRuta, tipo, fechaRegistro, usuarioRegistrador) VALUES (?, ?, ?, 'cuantitativo', NOW(), ?)");
                $archivos = $_FILES['archivoPdf'];

                for ($i = 0; $i < count($archivos['name']); $i++) {
                    if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                        $nombreOriginal = basename($archivos['name'][$i]);
                        // Check duplicate
                        $patron = 'uploads/resultados/' . $cod . '_' . $posSolicitud . '_' . pathinfo($nombreOriginal, PATHINFO_FILENAME) . '%';
                        $chk = $conexion->prepare("SELECT id FROM san_fact_resultado_archivo WHERE codEnvio = ? AND posSolicitud = ? AND archRuta LIKE ?");
                        $chk->bind_param("sis", $cod, $posSolicitud, $patron);
                        $chk->execute();
                        if ($chk->get_result()->num_rows > 0) continue;

                        $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
                        $nombreFinal = $cod . '_' . $posSolicitud . '_' . pathinfo($nombreOriginal, PATHINFO_FILENAME) . '_' . time() . '.' . $ext;

                        if (move_uploaded_file($archivos['tmp_name'][$i], $uploadDir . $nombreFinal)) {
                            $rutaRel = 'uploads/resultados/' . $nombreFinal;
                            $stmtArch->bind_param("siss", $cod, $posSolicitud, $rutaRel, $user);
                            if ($stmtArch->execute()) $archivos_guardados++;
                        }
                    }
                }
            }

            // Actualizar estado y cerrar
            $stmtUpd = $conexion->prepare("UPDATE san_fact_solicitud_det SET estado_cuanti = ? WHERE codEnvio = ? AND posSolicitud = ?");
            $estadoC = $_POST['estadoCuanti'] ?? 'completado';
            $stmtUpd->bind_param("ssi", $estadoC, $cod, $posSolicitud);
            $stmtUpd->execute();

            insertarHistorial($conexion, $cod, $posSolicitud, 'actualizacion_resultados_cuantitativos', 'cuantitativo', "Act: " . implode(',', $enfermedadesRecibidas), $user);

            mysqli_commit($conexion);
            echo json_encode(['success' => true, 'message' => "Actualización OK ($enfermedadesActualizadas enf, $archivos_guardados arch)"]);
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conexion);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
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

            $uploadDir = '../../uploads/resultados/';
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
