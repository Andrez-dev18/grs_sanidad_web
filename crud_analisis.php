<?php
session_start();
if (empty($_SESSION['active'])) {
    header('HTTP/1.0 401 Unauthorized');
    exit('No autorizado');
}

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}
mysqli_set_charset($conexion, 'utf8');

$action = $_POST['action'] ?? '';
try {
    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $enfermedad = trim($_POST['enfermedad'] ?? '');

        if (empty($nombre))
            throw new Exception('El nombre del análisis es obligatorio');

        // Verificar duplicado
        /* $check = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_analisis WHERE nombre = ?");
         mysqli_stmt_bind_param($check, "s", $nombre);
         mysqli_stmt_execute($check);
         mysqli_stmt_store_result($check);
         if (mysqli_stmt_num_rows($check) > 0) {
             throw new Exception('Ya existe un análisis con este nombre');
         }
         mysqli_stmt_close($check);*/

        $stmt = mysqli_prepare($conexion, "INSERT INTO san_dim_analisis (nombre, enfermedad) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $nombre, $enfermedad);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(['success' => true, 'message' => 'Análisis creado exitosamente']);
        exit();
    }

    if ($action === 'update') {
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $enfermedad = trim($_POST['enfermedad'] ?? '');

        if (empty($codigo))
            throw new Exception('Código inválido');
        if (empty($nombre))
            throw new Exception('El nombre del análisis es obligatorio');

        // Verificar duplicado (excluyendo el actual)
        /*$check = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_analisis WHERE nombre = ? AND codigo != ?");
         mysqli_stmt_bind_param($check, "ss", $nombre, $codigo);
         mysqli_stmt_execute($check);
         mysqli_stmt_store_result($check);
         if (mysqli_stmt_num_rows($check) > 0) {
             throw new Exception('Ya existe otro análisis con este nombre');
         }
         mysqli_stmt_close($check);
 */
        $stmt = mysqli_prepare($conexion, "UPDATE san_dim_analisis SET nombre = ?, enfermedad = ? WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "sss", $nombre, $enfermedad, $codigo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(['success' => true, 'message' => 'Análisis actualizado exitosamente']);
        exit();
    }

    if ($action === 'delete') {
        $codigo = trim($_POST['codigo'] ?? '');
        if (empty($codigo))
            throw new Exception('Código inválido');

        // Verificar si está en uso
        $check = mysqli_prepare($conexion, "SELECT COUNT(*) AS total FROM san_dim_analisis_paquete WHERE analisis = ?");
        mysqli_stmt_bind_param($check, "s", $codigo);
        mysqli_stmt_execute($check);
        mysqli_stmt_bind_result($check, $total);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);

        if ($total > 0) {
            throw new Exception('No se puede eliminar porque este análisis está asociado a uno o más paquetes.');
        }

        $stmt = mysqli_prepare($conexion, "DELETE FROM san_dim_analisis WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt, "s", $codigo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo json_encode(['success' => true, 'message' => 'Análisis eliminado exitosamente']);
        exit();
    }

    throw new Exception('Acción no válida');

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
mysqli_close($conexion);
?>