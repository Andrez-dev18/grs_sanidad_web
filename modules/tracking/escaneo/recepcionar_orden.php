<?php
session_start();

include '../../../../conexion_grs_joya/conexion.php';
include '../../../includes/historial_resultados.php';

$conn = conectar_joya();

// === RUTAS CORRECTAS DENTRO DEL PROYECTO ===
$basePath = __DIR__ . '/';                          // Carpeta donde está este archivo PHP
$carpetaUploads = $basePath . '../../../uploads/';           // gc_sanidad_web/uploads/
$carpetaEvidencias = $carpetaUploads . 'evidencias/'; // gc_sanidad_web/uploads/evidencias/
$rutaRelativaBD = 'uploads/evidencias/';            // Ruta que se guardará en la BD

// === VERIFICAR Y CREAR CARPETAS ===
if (!is_dir($carpetaUploads)) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error: La carpeta principal "uploads" no existe.'
    ]);
    exit;
}

if (!is_dir($carpetaEvidencias)) {
    if (!mkdir($carpetaEvidencias, 0755, true)) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error: No se pudo crear la carpeta "uploads/evidencias". Verifique permisos del servidor.'
        ]);
        exit;
    }
}

if (!is_writable($carpetaEvidencias)) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error: No hay permisos de escritura en "uploads/evidencias".'
    ]);
    exit;
}

// === DATOS RECIBIDOS ===
$codEnvio = $_POST['codEnvio'] ?? '';
$obs = $_POST['obs'] ?? '';
$tipoReceptor = $_POST['tipoReceptor'] ?? '';
$rutaEvidencia = null;

// === VALIDACIONES BÁSICAS ===
if (empty($codEnvio)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Código de envío requerido']);
    exit;
}

if (!in_array($tipoReceptor, ['Transporte', 'Laboratorio'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Tipo de receptor no válido']);
    exit;
}

// Configuración según receptor
$ubicacion = ($tipoReceptor === 'Transporte') ? 'Transporte' : 'Laboratorio';
$accion = ($tipoReceptor === 'Transporte')
    ? 'Recepción de muestra'
    : 'Recepción de muestra por laboratorio';

// === VERIFICAR DUPLICADO ===
$sqlCheck = "SELECT id FROM san_dim_historial_resultados WHERE codEnvio = ? AND ubicacion = ? AND accion = 'Recepción de muestra por laboratorio' LIMIT 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ss", $codEnvio, $ubicacion);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => "Esta muestra ya fue recepcionada en esta etapa ($ubicacion)"
    ]);
    exit;
}
$stmtCheck->close();

// === PROCESAR MÚLTIPLES EVIDENCIAS ===
$rutaEvidencia = null;
$rutasEvidencias = [];

if (isset($_FILES['evidencias']) && !empty($_FILES['evidencias']['name'][0])) {
    $archivos = $_FILES['evidencias'];
    $cantidad = count($archivos['name']);

    for ($i = 0; $i < $cantidad; $i++) {
        if ($archivos['error'][$i] !== UPLOAD_ERR_OK) continue;

        $archivoTmp = $archivos['tmp_name'][$i];
        $nombreOriginal = $archivos['name'][$i];
        $tamano = $archivos['size'][$i];

        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) continue;
        if ($tamano > 5 * 1024 * 1024) continue; // 5MB

        $fechaHora = date('Ymd_His') . "_{$i}";
        $nombreArchivo = "evidencia_{$codEnvio}_{$fechaHora}.{$extension}";
        $rutaCompleta = $carpetaEvidencias . $nombreArchivo;

        if (move_uploaded_file($archivoTmp, $rutaCompleta)) {
            $rutasEvidencias[] = $rutaRelativaBD . $nombreArchivo;
        }
    }

    if (!empty($rutasEvidencias)) {
        $rutaEvidencia = implode(',', $rutasEvidencias); // "ruta1.jpg,ruta2.jpg,ruta3.jpg"
    }
}

// === INSERTAR EN LA BASE DE DATOS ===
$usuario = $_SESSION['usuario'] ?? ($tipoReceptor === 'Transporte' ? 'transportista' : 'laboratorio');

$ok = insertarHistorial(
    $conn,
    $codEnvio,
    0,
    $accion,
    null,
    $obs,
    $usuario,
    $ubicacion,
    $rutaEvidencia
);

$mensajeExito = $tipoReceptor === 'Transporte'
    ? 'Muestra recogida por transportista'
    : 'Muestra recibida en laboratorio';

echo json_encode([
    'ok' => $ok,
    'mensaje' => $ok ? $mensajeExito : 'Error al registrar en la base de datos'
]);
