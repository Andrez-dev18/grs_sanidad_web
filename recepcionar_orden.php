<?php
session_start();
include '../conexion_grs_joya/conexion.php';
include 'historial_resultados.php';

$conn = conectar_joya();

// === RUTAS CORRECTAS DENTRO DEL PROYECTO ===
$basePath = __DIR__ . '/';                          // Carpeta donde está este archivo PHP
$carpetaUploads = $basePath . 'uploads/';           // gc_sanidad_web/uploads/
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
$sqlCheck = "SELECT id FROM san_dim_historial_resultados WHERE codEnvio = ? AND ubicacion = ? LIMIT 1";
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

// === PROCESAR IMAGEN (si se envió) ===
if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['evidencia'];

    // Validaciones de seguridad
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
    $tamanoMaximo = 5 * 1024 * 1024; // 5MB
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas)) {
        echo json_encode(['ok' => false, 'mensaje' => 'Solo se permiten imágenes JPG, PNG o GIF']);
        exit;
    }

    if ($archivo['size'] > $tamanoMaximo) {
        echo json_encode(['ok' => false, 'mensaje' => 'La imagen no debe superar los 5MB']);
        exit;
    }

    // Nombre único
    $fechaHora = date('Ymd_His');
    $nombreArchivo = "evidencia_{$codEnvio}_{$fechaHora}.{$extension}";
    $rutaCompleta = $carpetaEvidencias . $nombreArchivo;

    // Guardar archivo
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error al guardar la imagen en el servidor.'
        ]);
        exit;
    }

    // Ruta para la BD
    $rutaEvidencia = $rutaRelativaBD . $nombreArchivo;
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
