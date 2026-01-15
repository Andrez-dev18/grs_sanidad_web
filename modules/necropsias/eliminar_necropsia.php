<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$granja = $_POST['granja'] ?? '';
$numreg = $_POST['numreg'] ?? '';
$fectra = $_POST['fectra'] ?? '';

if (empty($granja) || empty($numreg) || empty($fectra)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos para identificar el registro']);
    exit;
}

if (strpos($fectra, '/') !== false) {
    $fechaObj = DateTime::createFromFormat('d/m/Y', $fectra);
    if ($fechaObj) {
        $fectra = $fechaObj->format('Y-m-d');
    }
}

// Configuración de carpetas para borrar archivos físicos
$basePath = __DIR__;
$carpetaNecropsias = $basePath . '/../../uploads/necropsias/';

try {
    $conn->begin_transaction();

    // 1. OBTENER IMÁGENES PARA BORRARLAS FÍSICAMENTE (Limpieza)
    // Seleccionamos las rutas guardadas antes de borrar los registros
    $sqlImages = "SELECT evidencia FROM t_regnecropsia 
                  WHERE tgranja = ? AND tnumreg = ? AND tfectra = ? AND evidencia != ''";
    
    $stmtImg = $conn->prepare($sqlImages);
    $stmtImg->bind_param("sis", $granja, $numreg, $fectra);
    $stmtImg->execute();
    $resultImg = $stmtImg->get_result();

    while ($row = $resultImg->fetch_assoc()) {
        if (!empty($row['evidencia'])) {
            // Las rutas vienen separadas por comas: "uploads/..., uploads/..."
            $rutas = explode(',', $row['evidencia']);
            foreach ($rutas as $rutaBD) {
                // La ruta en BD es relativa (uploads/necropsias/foto.jpg)
                // Necesitamos el nombre del archivo para buscarlo en la carpeta física
                $nombreArchivo = basename(trim($rutaBD));
                $rutaFisica = $carpetaNecropsias . $nombreArchivo;

                if (file_exists($rutaFisica)) {
                    unlink($rutaFisica); // Borrar archivo
                }
            }
        }
    }
    $stmtImg->close();

    // 2. ELIMINAR LOS REGISTROS DE LA BD
    $sqlDelete = "DELETE FROM t_regnecropsia WHERE tgranja = ? AND tnumreg = ? AND tfectra = ?";
    $stmtDel = $conn->prepare($sqlDelete);
    $stmtDel->bind_param("sis", $granja, $numreg, $fectra);
    
    if (!$stmtDel->execute()) {
        throw new Exception("Error al ejecutar la eliminación: " . $conn->error);
    }

    if ($stmtDel->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Necropsia eliminada correctamente']);
    } else {
        throw new Exception("No se encontró el registro o ya fue eliminado");
    }

    $stmtDel->close();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>