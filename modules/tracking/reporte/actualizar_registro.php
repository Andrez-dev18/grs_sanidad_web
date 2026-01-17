<?php
session_start();
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$baseDir = realpath(__DIR__ . '/../../../'); 
$carpetaEvidencias = $baseDir . '/uploads/evidencias/';

$rutaRelativaBD = 'uploads/evidencias/'; 

// Verificar que la carpeta exista
if (!is_dir($carpetaEvidencias)) {
    // Intenta crearla si no existe (opcional, pero útil)
    if (!mkdir($carpetaEvidencias, 0755, true)) {
        echo json_encode(['ok' => false, 'mensaje' => 'Carpeta de evidencias no encontrada y no se pudo crear en: ' . $carpetaEvidencias]);
        exit;
    }
}

// === DATOS RECIBIDOS ===
$id = $_POST['id'] ?? 0;
$comentario = $_POST['comentario'] ?? '';
$ubicacionNueva = $_POST['ubicacion'] ?? '';

// Validar ID
if ($id <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'ID de registro inválido']);
    exit;
}

// Obtener registro actual
$sql = "SELECT codEnvio, ubicacion, evidencia FROM san_dim_historial_resultados WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Registro no encontrado']);
    exit;
}

$registro = $result->fetch_assoc();
$codEnvio = $registro['codEnvio'];
$ubicacionActual = $registro['ubicacion'];
$rutaEvidenciaActual = $registro['evidencia'] ?? '';

$ubicacionFinal = $ubicacionNueva;

// === PROCESAR EVIDENCIAS ===

// 1. Fotos que el usuario decidió mantener
$fotosRestantesStr = $_POST['fotos_restantes'] ?? '';
$fotosRestantes = [];
if ($fotosRestantesStr !== '') {
    $fotosRestantes = array_map('trim', explode(',', $fotosRestantesStr));
    $fotosRestantes = array_filter($fotosRestantes);
}

// 2. Fotos antiguas (para comparar y borrar las eliminadas)
$fotosAntiguas = $rutaEvidenciaActual ? array_map('trim', explode(',', $rutaEvidenciaActual)) : [];

// 3. Borrar del servidor las fotos que fueron eliminadas
foreach ($fotosAntiguas as $rutaAntigua) {
    if (!in_array($rutaAntigua, $fotosRestantes)) {
        // basename() es importante por seguridad para evitar 'directory traversal'
        $nombreArchivo = basename($rutaAntigua); 
        $rutaCompletaEliminar = $carpetaEvidencias . $nombreArchivo;
        
        if (file_exists($rutaCompletaEliminar)) {
            @unlink($rutaCompletaEliminar); 
        }
    }
}

// 4. Procesar nuevas fotos subidas
$rutasNuevas = [];
if (isset($_FILES['nuevas_evidencias']) && !empty($_FILES['nuevas_evidencias']['name'][0])) {
    $archivos = $_FILES['nuevas_evidencias'];
    $cantidad = count($archivos['name']);

    for ($i = 0; $i < $cantidad; $i++) {
        if ($archivos['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $archivoTmp = $archivos['tmp_name'][$i];
        $nombreOriginal = $archivos['name'][$i];
        $tamano = $archivos['size'][$i];

        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            continue;
        }
        if ($tamano > 5 * 1024 * 1024) { // 5MB
            continue;
        }

        $fechaHora = date('Ymd_His') . "_edit_$i";
        // Sanitizamos el nombre de archivo eliminando caracteres especiales
        $nombreArchivo = "evidencia_{$codEnvio}_{$fechaHora}.{$extension}";
        $rutaCompleta = $carpetaEvidencias . $nombreArchivo;

        if (move_uploaded_file($archivoTmp, $rutaCompleta)) {
            $rutasNuevas[] = $rutaRelativaBD . $nombreArchivo;
        }
    }
}

// 5. Lista final de rutas para guardar en BD
$rutasFinales = array_merge($fotosRestantes, $rutasNuevas);
$rutasFinales = array_unique(array_filter($rutasFinales)); 
$rutaEvidenciaFinal = implode(',', $rutasFinales);

// === ACTUALIZAR EN BD ===
$sqlUpdate = "
    UPDATE san_dim_historial_resultados 
    SET comentario = ?, 
        ubicacion = ?, 
        evidencia = ?,
        fechaHoraRegistro = NOW()
    WHERE id = ?
";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("sssi", $comentario, $ubicacionFinal, $rutaEvidenciaFinal, $id);

if ($stmtUpdate->execute()) {
    echo json_encode(['ok' => true, 'mensaje' => 'Registro actualizado correctamente']);
} else {
    echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar el registro']);
}

$stmtUpdate->close();
$conn->close();
?>
