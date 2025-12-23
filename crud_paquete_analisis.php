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

if (isset($_GET['action']) && $_GET['action'] === 'get_analisis') {
    $query = "SELECT codigo, nombre FROM san_dim_analisis ORDER BY codigo ASC";
    $result = mysqli_query($conexion, $query);
    $analisis = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $analisis[] = $row;
    }
    echo json_encode(['success' => true, 'analisis' => $analisis]);
    exit();
}

$action = $_POST['action'] ?? '';
try {
    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $tipoMuestra = trim($_POST['tipoMuestra'] ?? '');
        $analisis_lista = json_decode($_POST['analisis'] ?? '[]', true);

        if (empty($nombre))
            throw new Exception('El nombre del paquete es obligatorio');
        if (empty($tipoMuestra))
            throw new Exception('Debe seleccionar un tipo de muestra');
        if (empty($analisis_lista) || !is_array($analisis_lista)) {
            throw new Exception('Debe seleccionar al menos un análisis');
        }

        // Validar tipo de muestra
        $check = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_tipo_muestra WHERE codigo = ?");
        mysqli_stmt_bind_param($check, "s", $tipoMuestra);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) == 0)
            throw new Exception('Tipo de muestra no válido');
        mysqli_stmt_close($check);

        // Validar análisis
        $placeholders = str_repeat('?,', count($analisis_lista) - 1) . '?';
        $check2 = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_analisis WHERE codigo IN ($placeholders)");
        mysqli_stmt_bind_param($check2, str_repeat('s', count($analisis_lista)), ...$analisis_lista);
        mysqli_stmt_execute($check2);
        mysqli_stmt_store_result($check2);
        if (mysqli_stmt_num_rows($check2) != count($analisis_lista)) {
            throw new Exception('Uno o más análisis no existen');
        }
        mysqli_stmt_close($check2);

        // Validar duplicado de nombre
        /*$check3 = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_paquete WHERE nombre = ?");
        mysqli_stmt_bind_param($check3, "s", $nombre);
        mysqli_stmt_execute($check3);
        mysqli_stmt_store_result($check3);
        if (mysqli_stmt_num_rows($check3) > 0) {
            throw new Exception('Ya existe un paquete con este nombre');
        }
        mysqli_stmt_close($check3);*/

        // === Insertar paquete + análisis ===
        mysqli_autocommit($conexion, false);
        $stmt1 = mysqli_prepare($conexion, "INSERT INTO san_dim_paquete (nombre, tipoMuestra) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt1, "ss", $nombre, $tipoMuestra);
        mysqli_stmt_execute($stmt1);
        $paquete_id = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt1);

        $stmt2 = mysqli_prepare($conexion, "INSERT INTO san_dim_analisis_paquete (paquete, analisis) VALUES (?, ?)");
        foreach ($analisis_lista as $a) {
            mysqli_stmt_bind_param($stmt2, "ss", $paquete_id, $a);
            mysqli_stmt_execute($stmt2);
        }
        mysqli_stmt_close($stmt2);
        mysqli_commit($conexion);

        echo json_encode(['success' => true, 'message' => 'Paquete creado exitosamente']);
        exit();
    }

    if ($action === 'update') {
        $codigo = (int) ($_POST['codigo'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $tipoMuestra = trim($_POST['tipoMuestra'] ?? '');
        $analisis_lista = json_decode($_POST['analisis'] ?? '[]', true);

        if ($codigo <= 0)
            throw new Exception('Código de paquete inválido');
        if (empty($nombre))
            throw new Exception('Nombre obligatorio');
        if (empty($tipoMuestra))
            throw new Exception('Tipo de muestra obligatorio');
        /*if (empty($analisis_lista) || !is_array($analisis_lista)) {
            throw new Exception('Seleccione al menos un análisis');
        }*/

        // Validar paquete existente
        $check0 = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_paquete WHERE codigo = ?");
        mysqli_stmt_bind_param($check0, "i", $codigo);
        mysqli_stmt_execute($check0);
        mysqli_stmt_store_result($check0);
        if (mysqli_stmt_num_rows($check0) == 0)
            throw new Exception('Paquete no encontrado');
        mysqli_stmt_close($check0);

        // Validar tipo de muestra
        $check1 = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_tipo_muestra WHERE codigo = ?");
        mysqli_stmt_bind_param($check1, "s", $tipoMuestra);
        mysqli_stmt_execute($check1);
        mysqli_stmt_store_result($check1);
        if (mysqli_stmt_num_rows($check1) == 0)
            throw new Exception('Tipo de muestra no válido');
        mysqli_stmt_close($check1);
        if (!empty($analisis_lista)) {
            // Validar análisis
            $placeholders = str_repeat('?,', count($analisis_lista) - 1) . '?';
            $check2 = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_analisis WHERE codigo IN ($placeholders)");
            mysqli_stmt_bind_param($check2, str_repeat('s', count($analisis_lista)), ...$analisis_lista);
            mysqli_stmt_execute($check2);
            mysqli_stmt_store_result($check2);
            if (mysqli_stmt_num_rows($check2) != count($analisis_lista)) {
                throw new Exception('Uno o más análisis no existen');
            }
            mysqli_stmt_close($check2);
        }
        // Validar duplicado de nombre (excluyendo el actual)
        /*  $check3 = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_paquete WHERE nombre = ? AND codigo != ?");
           mysqli_stmt_bind_param($check3, "si", $nombre, $codigo);
           mysqli_stmt_execute($check3);
           mysqli_stmt_store_result($check3);
           if (mysqli_stmt_num_rows($check3) > 0) {
               throw new Exception('Ya existe otro paquete con este nombre');
           }
           mysqli_stmt_close($check3);*/

        // === Actualizar ===
        mysqli_autocommit($conexion, false);
        $stmt1 = mysqli_prepare($conexion, "UPDATE san_dim_paquete SET nombre = ?, tipoMuestra = ? WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt1, "ssi", $nombre, $tipoMuestra, $codigo);
        mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);

        $stmt2 = mysqli_prepare($conexion, "DELETE FROM san_dim_analisis_paquete WHERE paquete = ?");
        mysqli_stmt_bind_param($stmt2, "i", $codigo);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        $stmt3 = mysqli_prepare($conexion, "INSERT INTO san_dim_analisis_paquete (paquete, analisis) VALUES (?, ?)");
        foreach ($analisis_lista as $a) {
            mysqli_stmt_bind_param($stmt3, "ss", $codigo, $a);
            mysqli_stmt_execute($stmt3);
        }
        mysqli_stmt_close($stmt3);
        mysqli_commit($conexion);

        echo json_encode(['success' => true, 'message' => 'Paquete actualizado exitosamente']);
        exit();
    }

    if ($action === 'delete') {
        $codigo = (int) ($_POST['codigo'] ?? 0);
        if ($codigo <= 0)
            throw new Exception('Código inválido');

        mysqli_autocommit($conexion, false);
        $check2 = mysqli_prepare($conexion, "SELECT COUNT(*) FROM san_dim_analisis_paquete WHERE paquete = ?");
        mysqli_stmt_bind_param($check2, "i", $codigo);
        mysqli_stmt_execute($check2);
        $count2 = 0;
        mysqli_stmt_bind_result($check2, $count2);
        mysqli_stmt_fetch($check2);
        mysqli_stmt_close($check2);

        if ($count2 > 0) {
            throw new Exception('No se puede eliminar: el paquete está en uso.');
        }

        $stmt2 = mysqli_prepare($conexion, "DELETE FROM san_dim_paquete WHERE codigo = ?");
        mysqli_stmt_bind_param($stmt2, "i", $codigo);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        mysqli_commit($conexion);

        echo json_encode(['success' => true, 'message' => 'Paquete eliminado exitosamente']);
        exit();
    }

    throw new Exception('Acción no válida');

} catch (Exception $e) {
    // En caso de error, revertir transacción si está activa
    if (mysqli_autocommit($conexion, true) === false) {
        mysqli_rollback($conexion);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
mysqli_close($conexion);
?>