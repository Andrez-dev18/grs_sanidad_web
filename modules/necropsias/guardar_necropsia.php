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
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
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

if (!$input || !isset($input['granja']) || !isset($input['registros']) || empty($input['registros'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o incompletos']);
    exit;
}

$granja     = $input['granja'];
$campania   = $input['campania'];
$galpon     = $input['galpon'];
$edad       = $input['edad'];
$fectra     = $input['fectra'];
$numreg     = (int)$input['numreg'];
$tcencos    = $input['tcencos'] ?? '';

session_start();
$tuser  = $_SESSION['usuario'] ?? 'WEB';
$tdate  = date('Y-m-d');
$ttime  = date('H:i:s');
$diareg = date('Y-m-d');

$tid_contador = 1; 

$sql = "INSERT INTO t_regnecropsia (
    tid, tuser, tdate, ttime, tcencos, tgranja, tcampania, tedad, tgalpon, tnumreg, tfectra, diareg,
    tcodsistema, tsistema, tnivel, tparametro,
    tporcentaje1, tporcentaje2, tporcentaje3, tporcentaje4, tporcentaje5, tporcentajetotal,
    tobservacion, evidencia, tobs, tuuid
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?
)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparando consulta: ' . $conn->error]);
    exit;
}

$insertadas = 0;

foreach ($input['registros'] as $reg) {
    $obs = $reg['tobservacion'] ?? '';

    switch ($reg['tsistema']) {
        case 'SISTEMA INMUNOLOGICO': $tcodsistema = 1; break;
        case 'SISTEMA DIGESTIVO': $tcodsistema = 2; break;
        case 'SISTEMA RESPIRATORIO': $tcodsistema = 3; break;
        case 'EVALUACION FISICA': $tcodsistema = 4; break;
        default: $tcodsistema = 0;
    }

    $current_tid = $tid_contador++;
    $tuuid = generar_uuid_v4();
    $evidencia = '';

    $stmt->bind_param(
        "issssssssississsddddddssss",
        $current_tid,
        $tuser,
        $tdate,
        $ttime,
        $tcencos,
        $granja,
        $campania,
        $edad,
        $galpon,
        $numreg,
        $fectra,
        $diareg,
        $tcodsistema,
        $reg['tsistema'],
        $reg['tnivel'],
        $reg['tparametro'],
        $reg['tporcentaje1'],
        $reg['tporcentaje2'],
        $reg['tporcentaje3'],
        $reg['tporcentaje4'],
        $reg['tporcentaje5'],
        $reg['tporcentajetotal'],
        $obs,
        $evidencia,
        $obs,
        $tuuid
    );

    if ($stmt->execute()) {
        $insertadas++;
    }
}

$stmt->close();

// === PROCESAR IMÁGENES (hasta 3 por nivel) ===
$imagenesGuardadas = 0;

foreach ($_FILES as $key => $files) {
    // Solo procesar inputs que terminan en [] (múltiples)
    if (strpos($key, 'evidencia_') === 0 && is_array($files['tmp_name'])) {
        $obsId = str_replace('evidencia_', '', $key);
        $rutas = []; // Array para guardar las URLs de este nivel

        $mapeoNivel = [
            'indice_bursal'     => 'INDICE BURSAL',
            'mucosa_bursa'      => 'MUCOSA DE LA BURSA',
            'timos'             => 'TIMOS',
            'higados'           => 'HIGADO',
            'vesicula'          => 'VESICULA BILIAR',
            'erosion'           => 'EROSION DE LA MOLLEJA',
            'pancreas'          => 'RETRACCION DEL PANCREAS',
            'saco'              => 'ABSORCION DEL SACO VITELINO',
            'enteritis'         => 'ENTERITIS',
            'cecal'             => 'CONTENIDO CECAL',
            'alimento'          => 'ALIMENTO SIN DIGERIR',
            'heces'             => 'HECES ANARANJADAS',
            'lesion'            => 'LESION ORAL',
            'tonicidad'         => 'TONICIDAD INTESTINAL',
            'traquea'           => 'TRAQUEA',
            'pulmon'            => 'PULMON',
            'sacos'             => 'SACOS AEREOS',
            'pododermatitis'    => 'PODODERMATITIS',
            'tarsos'            => 'COLOR TARSOS'
        ];

        if (isset($mapeoNivel[$obsId])) {
            $nivelNombre = $mapeoNivel[$obsId];

            // Procesar cada archivo del array
            foreach ($files['tmp_name'] as $i => $tmp_name) {
                if ($files['error'][$i] === 0 && count($rutas) < 3) { // límite 3
                    $extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    $nombreArchivo = $granja . '_' . $galpon . '_' . $numreg . '_' . str_replace('-', '', $fectra) . '_' . $obsId . '_' . uniqid() . '.' . $extension;
                    $rutaFisica = $carpetaNecropsias . $nombreArchivo;
                    $rutaBD = $rutaRelativaBD . $nombreArchivo;

                    if (move_uploaded_file($tmp_name, $rutaFisica)) {
                        $rutas[] = $rutaBD;
                        $imagenesGuardadas++;
                    }
                }
            }

            // Si hay rutas, guardarlas separadas por coma
            if (!empty($rutas)) {
                $rutaFinal = implode(',', $rutas); // ej: ruta1.jpg,ruta2.jpg,ruta3.jpg

                $updateSql = "UPDATE t_regnecropsia 
                              SET evidencia = ? 
                              WHERE tgranja = ? AND tgalpon = ? AND tnumreg = ? AND tfectra = ? AND tnivel = ?";
                $stmtUpdate = $conn->prepare($updateSql);
                $stmtUpdate->bind_param("ssssss", $rutaFinal, $granja, $galpon, $numreg, $fectra, $nivelNombre);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }
        }
    }
}

$conn->close();

echo json_encode([
    'success' => $insertadas > 0,
    'message' => $insertadas > 0 
        ? "¡Necropsia guardada exitosamente! ($insertadas registros + $imagenesGuardadas imágenes)"
        : "Error al guardar los registros"
]);
?>