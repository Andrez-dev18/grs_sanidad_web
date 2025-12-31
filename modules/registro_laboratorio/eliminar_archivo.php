<?php
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

header('Content-Type: application/json');

// Validar que se reciba el ID
if (!isset($_POST['idArchivo'])) {
    echo json_encode(['success' => false, 'message' => 'ID del archivo no proporcionado']);
    exit;
}

$idArchivo = $_POST['idArchivo'];

// Preparar consulta para obtener la ruta del archivo
$stmt = $conn->prepare("
    SELECT archRuta 
    FROM san_fact_resultado_archivo 
    WHERE id = ?
");
$stmt->bind_param("i", $idArchivo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Archivo no encontrado en la base de datos']);
    $stmt->close();
    exit;
}

$row = $result->fetch_assoc();
$rutaArchivo = $row['archRuta'];
$stmt->close();

// === Eliminar archivo físico del servidor ===
$eliminadoFisico = true;
if (file_exists($rutaArchivo)) {
    if (!unlink($rutaArchivo)) {
        $eliminadoFisico = false;
        // No fallamos aquí, seguimos para eliminar de BD aunque el físico falle
    }
}

// === Eliminar registro de la base de datos ===
$stmtDelete = $conn->prepare("
    DELETE FROM san_fact_resultado_archivo 
    WHERE id = ?
");
$stmtDelete->bind_param("i", $idArchivo);
$eliminadoBD = $stmtDelete->execute();
$stmtDelete->close();

if (!$eliminadoBD) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar el registro de la base de datos'
    ]);
    exit;
}

// Respuesta final
echo json_encode([
    'success' => true,
    'message' => 'Archivo eliminado correctamente',
    'fisicoEliminado' => $eliminadoFisico,
    'rutaEliminada' => $rutaArchivo
]);

$conn->close();
?>