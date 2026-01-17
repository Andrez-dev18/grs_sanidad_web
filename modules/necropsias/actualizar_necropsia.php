<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Lima');

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

function generar_uuid_v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$basePath = __DIR__;
$carpetaUploads = $basePath . '/../../uploads/';
$carpetaNecropsias = $carpetaUploads . 'necropsias/';
$rutaRelativaBD = 'uploads/necropsias/';

if (!is_dir($carpetaNecropsias)) {
    mkdir($carpetaNecropsias, 0755, true);
}

$dataJson = $_POST['data'] ?? null;
$input = json_decode($dataJson, true);

if (!$input || !isset($input['granja']) || !isset($input['registros'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Datos Identificadores (Clave Primaria Compuesta)
$granja     = $input['granja'];
$galpon     = $input['galpon'];
$numreg     = (int)$input['numreg']; // Importante: Viene del JS, no se genera nuevo
$fectra     = $input['fectra'];

// Datos complementarios
$campania   = $input['campania'];
$edad       = $input['edad'];
$tcencos    = $input['tcencos'] ?? '';
$diagpresuntivo = $input['diagpresuntivo'];
$imagenes_existentes = $input['imagenes_existentes'] ?? []; // Array con rutas de fotos antiguas

session_start();
$tuser  = $_SESSION['usuario'] ?? 'WEB';
$tdate  = date('Y-m-d');
$ttime  = date('H:i:s');
$diareg = date('Y-m-d');

// Iniciamos transacción para evitar borrar sin insertar
$conn->begin_transaction();

try {
    // 1. ELIMINAR REGISTROS ANTERIORES
    // Borramos todo el lote para volver a insertarlo limpio con los nuevos datos
    $sqlDelete = "DELETE FROM t_regnecropsia 
                  WHERE tgranja = ? AND tgalpon = ? AND tnumreg = ? AND tfectra = ?";
    $stmtDel = $conn->prepare($sqlDelete);
    $stmtDel->bind_param("ssis", $granja, $galpon, $numreg, $fectra);
    
    if (!$stmtDel->execute()) {
        throw new Exception("Error al limpiar registros anteriores: " . $stmtDel->error);
    }
    $stmtDel->close();

    // 2. INSERTAR DATOS NUEVOS
    $sqlInsert = "INSERT INTO t_regnecropsia (
        tid, tuser, tdate, ttime, tcencos, tgranja, tcampania, tedad, tgalpon, tnumreg, tfectra, diareg,
        tcodsistema, tsistema, tnivel, tparametro,
        tporcentaje1, tporcentaje2, tporcentaje3, tporcentaje4, tporcentaje5, tporcentajetotal,
        tobservacion, evidencia, tdiagpresuntivo, tobs, tuuid
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

    $stmt = $conn->prepare($sqlInsert);
    $tid_contador = 1;

    foreach ($input['registros'] as $reg) {
        $obs = $reg['tobservacion'] ?? '';
        
        // Mapeo simple de códigos
        switch ($reg['tsistema']) {
            case 'SISTEMA INMUNOLOGICO': $tcodsistema = 1; break;
            case 'SISTEMA DIGESTIVO': $tcodsistema = 2; break;
            case 'SISTEMA RESPIRATORIO': $tcodsistema = 3; break;
            case 'EVALUACION FISICA': $tcodsistema = 4; break;
            default: $tcodsistema = 0;
        }

        $current_tid = $tid_contador++;
        $tuuid = generar_uuid_v4();
        $evidencia_temp = ''; // Se actualizará en el paso 3

        $stmt->bind_param(
            "issssssssississsddddddsssss",
            $current_tid, $tuser, $tdate, $ttime, $tcencos, $granja, $campania, $edad, $galpon, $numreg, $fectra, $diareg,
            $tcodsistema, $reg['tsistema'], $reg['tnivel'], $reg['tparametro'],
            $reg['tporcentaje1'], $reg['tporcentaje2'], $reg['tporcentaje3'], $reg['tporcentaje4'], $reg['tporcentaje5'], $reg['tporcentajetotal'],
            $obs, $evidencia_temp, $diagpresuntivo ,$obs, $tuuid
        );

        if (!$stmt->execute()) {
            throw new Exception("Error insertando fila: " . $stmt->error);
        }
    }
    $stmt->close();

    // 3. GESTIÓN DE IMÁGENES (Viejas + Nuevas)
    // Mapeo IDs HTML -> Nombres BD
    $mapeoNivel = [
        'indice_bursal' => 'INDICE BURSAL', 'mucosa_bursa' => 'MUCOSA DE LA BURSA', 'timos' => 'TIMOS',
        'higados' => 'HIGADO', 'vesicula' => 'VESICULA BILIAR', 'erosion' => 'EROSION DE LA MOLLEJA',
        'pancreas' => 'RETRACCION DEL PANCREAS', 'saco' => 'ABSORCION DEL SACO VITELINO',
        'enteritis' => 'ENTERITIS', 'cecal' => 'CONTENIDO CECAL', 'alimento' => 'ALIMENTO SIN DIGERIR',
        'heces' => 'HECES ANARANJADAS', 'lesion' => 'LESION ORAL', 'tonicidad' => 'TONICIDAD INTESTINAL',
        'traquea' => 'TRAQUEA', 'pulmon' => 'PULMON', 'sacos' => 'SACOS AEREOS',
        'pododermatitis' => 'PODODERMATITIS', 'tarsos' => 'COLOR TARSOS'
    ];

    foreach ($mapeoNivel as $obsId => $nombreNivelBD) {
        $rutasFinales = [];

        // A. Recuperar imágenes antiguas que el usuario NO borró
        if (isset($imagenes_existentes[$nombreNivelBD]) && !empty($imagenes_existentes[$nombreNivelBD])) {
            $imgs = explode(',', $imagenes_existentes[$nombreNivelBD]);
            foreach ($imgs as $ruta) {
                if (trim($ruta)) $rutasFinales[] = trim($ruta);
            }
        }

        // B. Subir nuevas imágenes
        $inputKey = 'evidencia_' . $obsId;
        if (isset($_FILES[$inputKey]) && is_array($_FILES[$inputKey]['tmp_name'])) {
            foreach ($_FILES[$inputKey]['tmp_name'] as $i => $tmp_name) {
                if ($_FILES[$inputKey]['error'][$i] === 0 && count($rutasFinales) < 3) {
                    $ext = strtolower(pathinfo($_FILES[$inputKey]['name'][$i], PATHINFO_EXTENSION));
                    $nombreArchivo = $granja . '_' . $galpon . '_' . $numreg . '_' . str_replace('-', '', $fectra) . '_' . $obsId . '_' . uniqid() . '.' . $ext;
                    
                    if (move_uploaded_file($tmp_name, $carpetaNecropsias . $nombreArchivo)) {
                        $rutasFinales[] = $rutaRelativaBD . $nombreArchivo;
                    }
                }
            }
        }

        // C. Actualizar BD si hay imágenes
        if (!empty($rutasFinales)) {
            // === CORRECCIÓN AQUÍ: Eliminar duplicados ===
            $rutasFinales = array_unique($rutasFinales);
            
            $stringEvidencia = implode(',', $rutasFinales);
            $sqlUpdate = "UPDATE t_regnecropsia SET evidencia = ? 
                          WHERE tgranja = ? AND tgalpon = ? AND tnumreg = ? AND tfectra = ? AND tnivel = ?";
            $stmtUpd = $conn->prepare($sqlUpdate);
            $stmtUpd->bind_param("ssssss", $stringEvidencia, $granja, $galpon, $numreg, $fectra, $nombreNivelBD);
            $stmtUpd->execute();
            $stmtUpd->close();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Necropsia actualizada correctamente']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>