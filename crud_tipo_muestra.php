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
$descripcion = trim($_POST['descripcion'] ?? '');
$longitud_codigo = isset($_POST['longitud_codigo']) ? (int) $_POST['longitud_codigo'] : 8;
$codigo = isset($_POST['codigo']) ? (int) $_POST['codigo'] : null;

if (empty($nombre) && $action !== 'delete') {
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
    exit();
}

if ($action !== 'delete' && ($longitud_codigo < 1)) {
    echo json_encode(['success' => false, 'message' => 'La longitud de código debe ser mayor que 1.']);
    exit();
}

mysqli_begin_transaction($conexion);

try {
    if ($action === 'create') {
        // Verificar que no exista un tipo de muestra con el mismo nombre
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_tipo_muestra WHERE nombre = ?");
        mysqli_stmt_bind_param($check, "s", $nombre);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('Ya existe un tipo de muestra con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "INSERT INTO com_tipo_muestra (nombre, descripcion, lonCod) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssi", $nombre, $descripcion, $longitud_codigo);

    } elseif ($action === 'update') {
        if (!$codigo)
            throw new Exception('Código no válido.');

        // Verificar que no exista otro tipo de muestra con el mismo nombre
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_tipo_muestra WHERE nombre = ? AND codigo != ?");
        mysqli_stmt_bind_param($check, "si", $nombre, $codigo);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('Ya existe otro tipo de muestra con ese nombre.');
        }

        $stmt = mysqli_prepare($conexion, "UPDATE com_tipo_muestra SET nombre = ?, descripcion = ?, lonCod = ? WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "ssii", $nombre, $descripcion, $longitud_codigo, $codigo);

    } elseif ($action === 'delete') {
        if (!$codigo)
            throw new Exception('Código no válido.');

        // Verificar si el tipo de muestra está en uso en paquetes de análisis
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_paquete_muestra WHERE tipoMuestra = ?");
        mysqli_stmt_bind_param($check, "i", $codigo);
        mysqli_stmt_execute($check);
        $row = mysqli_stmt_get_result($check)->fetch_assoc();
        if ($row['cnt'] > 0) {
            throw new Exception('No se puede eliminar: el tipo de muestra está en uso en ' . $row['cnt'] . ' paquete(s) de análisis.');
        }

        // Verificar si el tipo de muestra está en uso en análisis
        $check2 = mysqli_prepare($conexion, "SELECT COUNT(*) AS cnt FROM com_analisis a
        JOIN com_paquete_muestra pm ON a.paquete = pm.codigo
        WHERE pm.tipoMuestra = ?");
        mysqli_stmt_bind_param($check2, "i", $codigo);
        mysqli_stmt_execute($check2);
        $row2 = mysqli_stmt_get_result($check2)->fetch_assoc();
        if ($row2['cnt'] > 0) {
            throw new Exception('No se puede eliminar: el tipo de muestra está en uso en ' . $row2['cnt'] . ' análisis.');
        }

        $stmt = mysqli_prepare($conexion, "DELETE FROM com_tipo_muestra WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "i", $codigo);

    } else {
        throw new Exception('Acción no válida.');
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error en la base de datos: ' . mysqli_error($conexion));
    }

    mysqli_commit($conexion);

    $mensaje = '';
    switch ($action) {
        case 'create':
            $mensaje = '✅ Tipo de muestra creado correctamente.';
            break;
        case 'update':
            $mensaje = '✅ Tipo de muestra actualizado correctamente.';
            break;
        case 'delete':
            $mensaje = '✅ Tipo de muestra eliminado correctamente.';
            break;
    }

    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conexion);
?>