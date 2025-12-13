<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$action = $_POST['action'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$codigo = isset($_POST['codigo']) ? (int) $_POST['codigo'] : null;

// Validar nombre solo para crear/editar
if (($action === 'create' || $action === 'update') && empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
    exit();
}

mysqli_begin_transaction($conexion);

try {
    if ($action === 'create') {
        // Opcional: evitar duplicados
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM san_dim_laboratorio WHERE nombre = ?");
        mysqli_stmt_bind_param($check, "s", $nombre);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('Ya existe un laboratorio con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "INSERT INTO san_dim_laboratorio (nombre) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $nombre);

    } elseif ($action === 'update') {
        if (!$codigo)
            throw new Exception('Código no válido.');

        // Opcional: evitar duplicados (excluyendo el actual)
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM san_dim_laboratorio WHERE nombre = ? AND codigo != ?");
        mysqli_stmt_bind_param($check, "si", $nombre, $codigo);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('Ya existe otro laboratorio con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "UPDATE san_dim_laboratorio SET nombre = ? WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "si", $nombre, $codigo);

    } elseif ($action === 'delete') {
        if (!$codigo)
            throw new Exception('Código no válido.');

        $stmt = mysqli_prepare($conexion, "DELETE FROM san_dim_laboratorio WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "i", $codigo);

    } else {
        throw new Exception('Acción no válida.');
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al ejecutar la operación en la base de datos.');
    }

    mysqli_commit($conexion);

    $mensaje = match ($action) {
        'create' => '✅ Laboratorio creado correctamente.',
        'update' => '✅ Laboratorio actualizado correctamente.',
        'delete' => '✅ Laboratorio eliminado correctamente.',
    };

    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    mysqli_close($conexion);
}
?>