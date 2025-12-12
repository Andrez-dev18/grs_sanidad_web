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
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'create':
            $nombre = trim($_POST['nombre'] ?? '');
            $tipoMuestra = $_POST['tipoMuestra'] ?? 0;

            if (empty($nombre)) {
                throw new Exception('El nombre del paquete es obligatorio');
            }

            if ($tipoMuestra <= 0) {
                throw new Exception('Debe seleccionar un tipo de muestra válido');
            }

            // Verificar si ya existe un paquete con el mismo nombre
            $check = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_paquete WHERE nombre = ?");
            mysqli_stmt_bind_param($check, "s", $nombre);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                throw new Exception('Ya existe un paquete con este nombre');
            }

            // Verificar que el tipo de muestra exista
            $checkTipo = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_tipo_muestra WHERE codigo = ?");
            mysqli_stmt_bind_param($checkTipo, "i", $tipoMuestra);
            mysqli_stmt_execute($checkTipo);
            mysqli_stmt_store_result($checkTipo);

            if (mysqli_stmt_num_rows($checkTipo) == 0) {
                throw new Exception('El tipo de muestra seleccionado no existe');
            }

            // Insertar nuevo paquete
            $stmt = mysqli_prepare($conexion, "INSERT INTO san_dim_paquete (nombre, tipoMuestra) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "si", $nombre, $tipoMuestra);

            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Paquete creado exitosamente';
            } else {
                throw new Exception('Error al crear el paquete: ' . mysqli_error($conexion));
            }
            break;

        case 'update':
            $codigo = $_POST['codigo'] ?? 0;
            $nombre = trim($_POST['nombre'] ?? '');
            $tipoMuestra = $_POST['tipoMuestra'] ?? 0;

            if ($codigo <= 0) {
                throw new Exception('Código de paquete inválido');
            }

            if (empty($nombre)) {
                throw new Exception('El nombre del paquete es obligatorio');
            }

            if ($tipoMuestra <= 0) {
                throw new Exception('Debe seleccionar un tipo de muestra válido');
            }

            // Verificar si ya existe otro paquete con el mismo nombre
            $check = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_paquete WHERE nombre = ? AND codigo != ?");
            mysqli_stmt_bind_param($check, "si", $nombre, $codigo);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                throw new Exception('Ya existe otro paquete con este nombre');
            }

            // Verificar que el tipo de muestra exista
            $checkTipo = mysqli_prepare($conexion, "SELECT codigo FROM san_dim_tipo_muestra WHERE codigo = ?");
            mysqli_stmt_bind_param($checkTipo, "i", $tipoMuestra);
            mysqli_stmt_execute($checkTipo);
            mysqli_stmt_store_result($checkTipo);

            if (mysqli_stmt_num_rows($checkTipo) == 0) {
                throw new Exception('El tipo de muestra seleccionado no existe');
            }

            // Actualizar paquete
            $stmt = mysqli_prepare($conexion, "UPDATE san_dim_paquete SET nombre = ?, tipoMuestra = ? WHERE codigo = ?");
            mysqli_stmt_bind_param($stmt, "sii", $nombre, $tipoMuestra, $codigo);

            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Paquete actualizado exitosamente';
            } else {
                throw new Exception('Error al actualizar el paquete: ' . mysqli_error($conexion));
            }
            break;

        case 'delete':
            $codigo = $_POST['codigo'] ?? 0;

            if ($codigo <= 0) {
                throw new Exception('Código de paquete inválido');
            }

            // Verificar si hay análisis usando este paquete
            $check = mysqli_prepare($conexion, "SELECT COUNT(*) as total FROM san_dim_analisis WHERE paquete = ?");
            mysqli_stmt_bind_param($check, "i", $codigo);
            mysqli_stmt_execute($check);
            mysqli_stmt_bind_result($check, $count);
            mysqli_stmt_fetch($check);
            mysqli_stmt_close($check);

            if ($count > 0) {
                throw new Exception('No se puede eliminar este paquete porque tiene análisis asociados');
            }

            // Eliminar paquete
            $stmt = mysqli_prepare($conexion, "DELETE FROM san_dim_paquete WHERE codigo = ?");
            mysqli_stmt_bind_param($stmt, "i", $codigo);

            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Paquete eliminado exitosamente';
            } else {
                throw new Exception('Error al eliminar el paquete: ' . mysqli_error($conexion));
            }
            break;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
mysqli_close($conexion);
?>