<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Lima');

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// === RUTAS CORRECTAS DENTRO DEL PROYECTO ===
$basePath = __DIR__ . '/';                                      // carpeta actual del PHP
$carpetaUploads = $basePath . '/../../uploads/';              // gc_sanidad_web/uploads/
$carpetaNecropsias = $carpetaUploads . 'necropsias/';           // gc_sanidad_web/uploads/necropsias/
$rutaRelativaBD = 'uploads/necropsias/';                        // ruta que se guarda en BD

if (!is_dir($carpetaNecropsias)) {
    mkdir($carpetaNecropsias, 0755, true);
}

// === LEER DATOS ===
$dataJson = $_POST['data'] ?? null;
$input = json_decode($dataJson, true);

if (!$input || !isset($input['granja']) || !isset($input['registros']) || empty($input['registros'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o incompletos']);
    exit;
}

// === CABECERA ===
$granja     = $input['granja'];
$campania   = $input['campania'];
$galpon     = $input['galpon'];
$edad       = $input['edad'];
$fectra     = $input['fectra'];
$numreg     = (int)$input['numreg'];
$tcencos    = $input['tcencos'] ?? '';

// === AUTOMÁTICOS ===
session_start();
$tuser  = $_SESSION['usuario'] ?? 'WEB';
$tdate  = date('Y-m-d');
$ttime  = date('H:i:s');
$diareg = date('Y-m-d');

// === INSERTAR REGISTROS ===
$sql = "INSERT INTO t_regnecropsia (
    tuser, tdate, ttime, tcencos, tgranja, tcampania, tedad, tgalpon, tnumreg, tfectra, diareg,
    tcodsistema, tsistema, tnivel, tparametro,
    tporcentaje1, tporcentaje2, tporcentaje3, tporcentaje4, tporcentaje5, tporcentajetotal,
    tobservacion, evidencia, tobs
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?,
    ?, ?, ?
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

    // evidencia vacía por ahora (se actualizará después)
    $evidencia = '';

    $stmt->bind_param(
        "ssssssssississsddddddsss",
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
        $obs
    );

    if ($stmt->execute()) {
        $insertadas++;
    }
}

$stmt->close();

// === PROCESAR Y GUARDAR IMÁGENES POR NIVEL ===
$imagenesGuardadas = 0;

foreach ($_FILES as $key => $file) {
    if (strpos($key, 'evidencia_') === 0 && $file['error'] === 0) {
        $obsId = str_replace('evidencia_', '', $key);  // ej: indice_bursal
        $nivelNombre = '';

        // Mapeo obsId → tnivel real
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

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $nombreArchivo = $granja . '_' . $galpon . '_' . $numreg . '_' . str_replace('-', '', $fectra) . '_' . $obsId . '_' . uniqid() . '.' . $extension;
            $rutaFisica = $carpetaNecropsias . $nombreArchivo;
            $rutaBD = $rutaRelativaBD . $nombreArchivo;

            if (move_uploaded_file($file['tmp_name'], $rutaFisica)) {
                // Actualizar TODOS los registros del mismo nivel con la ruta de la imagen
                $updateSql = "UPDATE t_regnecropsia 
                              SET evidencia = ? 
                              WHERE tgranja = ? AND tgalpon = ? AND tnumreg = ? AND tfectra = ? AND tnivel = ?";
                $stmtUpdate = $conn->prepare($updateSql);
                $stmtUpdate->bind_param("ssssss", $rutaBD, $granja, $galpon, $numreg, $fectra, $nivelNombre);
                $stmtUpdate->execute();
                $stmtUpdate->close();

                $imagenesGuardadas++;
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