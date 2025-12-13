<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

//ruta relativa a la conexion
include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$action = $_POST['action'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$codigo = isset($_POST['codigo']) ? (int) $_POST['codigo'] : null;

if (empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
    exit();
}

mysqli_begin_transaction($conexion);

try {
    if ($action === 'create') {
        $stmt = mysqli_prepare($conexion, "INSERT INTO san_dim_emptrans (nombre) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $nombre);
    } elseif ($action === 'update') {
        if (!$codigo)
            throw new Exception('Código no válido.');
        $stmt = mysqli_prepare($conexion, "UPDATE san_dim_emptrans SET nombre = ? WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "si", $nombre, $codigo);
    } elseif ($action === 'delete') {
        if (!$codigo)
            throw new Exception('Código no válido.');
        // Verifica uso en envíos
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM san_fact_solicitud_cab WHERE empTrans = ?");
        mysqli_stmt_bind_param($check, "i", $codigo);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('No se puede eliminar: la empresa ya está en uso en envíos.');
        }
        $stmt = mysqli_prepare($conexion, "DELETE FROM san_dim_emptrans WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "i", $codigo);
    } else {
        throw new Exception('Acción no válida.');
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error en la base de datos: ' . mysqli_error($conexion));
    }

    mysqli_commit($conexion);
    echo json_encode(['success' => true, 'message' => 'Operación realizada con éxito.']);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conexion);
?>