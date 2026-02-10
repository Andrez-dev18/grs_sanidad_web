<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit();
}

include_once '../../../../conexion_grs_joya/conexion.php';
include_once '../../../includes/historial_acciones.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión.']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$sigla = trim($_POST['sigla'] ?? '');
$codigo = isset($_POST['codigo']) ? (int) $_POST['codigo'] : 0;

$campoUbicacion = isset($_POST['campoUbicacion']) ? (int) $_POST['campoUbicacion'] : 0;
$campoProducto = isset($_POST['campoProducto']) ? (int) $_POST['campoProducto'] : 0;
$campoUnidades = isset($_POST['campoUnidades']) ? (int) $_POST['campoUnidades'] : 0;
$campoUnidadDosis = isset($_POST['campoUnidadDosis']) ? (int) $_POST['campoUnidadDosis'] : 0;
$campoNumeroFrascos = isset($_POST['campoNumeroFrascos']) ? (int) $_POST['campoNumeroFrascos'] : 0;
$campoEdadAplicacion = isset($_POST['campoEdadAplicacion']) ? (int) $_POST['campoEdadAplicacion'] : 0;
$campoAreaGalpon = isset($_POST['campoAreaGalpon']) ? (int) $_POST['campoAreaGalpon'] : 0;
$campoCantidadPorGalpon = isset($_POST['campoCantidadPorGalpon']) ? (int) $_POST['campoCantidadPorGalpon'] : 0;

if ($action === 'create') {
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'Nombre es obligatorio.']);
        exit();
    }
    $stmt = $conexion->prepare("INSERT INTO san_dim_tipo_programa (nombre, sigla, campoUbicacion, campoProducto, campoUnidades, campoUnidadDosis, campoNumeroFrascos, campoEdadAplicacion, campoAreaGalpon, campoCantidadPorGalpon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $siglaVal = $sigla === '' ? '' : $sigla;
    if ($stmt && $stmt->bind_param("ssiiiiiiii", $nombre, $siglaVal, $campoUbicacion, $campoProducto, $campoUnidades, $campoUnidadDosis, $campoNumeroFrascos, $campoEdadAplicacion, $campoAreaGalpon, $campoCantidadPorGalpon) && $stmt->execute()) {
        $codigoGenerado = $conexion->insert_id;
        $datos_nuevos = json_encode(['codigo' => $codigoGenerado, 'nombre' => $nombre, 'sigla' => $sigla], JSON_UNESCAPED_UNICODE);
        $usuario = $_SESSION['usuario'] ?? 'sistema';
        $nom_usuario = $_SESSION['nombre'] ?? $usuario;
        try {
            registrarAccion($usuario, $nom_usuario, 'INSERT', 'san_dim_tipo_programa', $codigoGenerado, null, $datos_nuevos, 'Se creó un nuevo tipo de programa', null);
        } catch (Exception $e) {
            error_log("Error al registrar historial de acciones: " . $e->getMessage());
        }
        echo json_encode(['success' => true, 'message' => 'Tipo de programa creado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear el tipo de programa.']);
    }
} elseif ($action === 'update') {
    if (empty($nombre) || !$codigo) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
        exit();
    }
    $stmt_prev = $conexion->prepare("SELECT nombre, sigla, campoUbicacion, campoProducto, campoUnidades, campoUnidadDosis, campoNumeroFrascos, campoEdadAplicacion, campoAreaGalpon, campoCantidadPorGalpon FROM san_dim_tipo_programa WHERE codigo = ?");
    $stmt_prev->bind_param("i", $codigo);
    $stmt_prev->execute();
    $result_prev = $stmt_prev->get_result();
    $datos_previos = null;
    if ($row_prev = $result_prev->fetch_assoc()) {
        $datos_previos = json_encode($row_prev, JSON_UNESCAPED_UNICODE);
    }
    $stmt_prev->close();

    $siglaVal = $sigla === '' ? '' : $sigla;
    $stmt = $conexion->prepare("UPDATE san_dim_tipo_programa SET nombre = ?, sigla = ?, campoUbicacion = ?, campoProducto = ?, campoUnidades = ?, campoUnidadDosis = ?, campoNumeroFrascos = ?, campoEdadAplicacion = ?, campoAreaGalpon = ?, campoCantidadPorGalpon = ? WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("ssiiiiiiiii", $nombre, $siglaVal, $campoUbicacion, $campoProducto, $campoUnidades, $campoUnidadDosis, $campoNumeroFrascos, $campoEdadAplicacion, $campoAreaGalpon, $campoCantidadPorGalpon, $codigo) && $stmt->execute()) {
        $datos_nuevos = json_encode(['codigo' => $codigo, 'nombre' => $nombre, 'sigla' => $sigla, 'campoUbicacion' => $campoUbicacion, 'campoProducto' => $campoProducto, 'campoUnidades' => $campoUnidades, 'campoUnidadDosis' => $campoUnidadDosis, 'campoNumeroFrascos' => $campoNumeroFrascos, 'campoEdadAplicacion' => $campoEdadAplicacion, 'campoAreaGalpon' => $campoAreaGalpon, 'campoCantidadPorGalpon' => $campoCantidadPorGalpon], JSON_UNESCAPED_UNICODE);
        $usuario = $_SESSION['usuario'] ?? 'sistema';
        $nom_usuario = $_SESSION['nombre'] ?? $usuario;
        try {
            registrarAccion($usuario, $nom_usuario, 'UPDATE', 'san_dim_tipo_programa', $codigo, $datos_previos, $datos_nuevos, 'Se actualizó un tipo de programa', null);
        } catch (Exception $e) {
            error_log("Error al registrar historial de acciones: " . $e->getMessage());
        }
        echo json_encode(['success' => true, 'message' => 'Tipo de programa actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
    }
} elseif ($action === 'delete') {
    if (!$codigo) {
        echo json_encode(['success' => false, 'message' => 'Código no válido.']);
        exit();
    }
    $stmt_prev = $conexion->prepare("SELECT nombre, sigla, campoUbicacion, campoProducto, campoUnidades, campoUnidadDosis, campoNumeroFrascos, campoEdadAplicacion, campoAreaGalpon, campoCantidadPorGalpon FROM san_dim_tipo_programa WHERE codigo = ?");
    $stmt_prev->bind_param("i", $codigo);
    $stmt_prev->execute();
    $result_prev = $stmt_prev->get_result();
    $datos_previos = null;
    if ($row_prev = $result_prev->fetch_assoc()) {
        $datos_previos = json_encode(array_merge(['codigo' => $codigo], $row_prev), JSON_UNESCAPED_UNICODE);
    }
    $stmt_prev->close();

    $stmt = $conexion->prepare("DELETE FROM san_dim_tipo_programa WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("i", $codigo) && $stmt->execute()) {
        $usuario = $_SESSION['usuario'] ?? 'sistema';
        $nom_usuario = $_SESSION['nombre'] ?? $usuario;
        try {
            registrarAccion($usuario, $nom_usuario, 'DELETE', 'san_dim_tipo_programa', $codigo, $datos_previos, null, 'Se eliminó un tipo de programa', null);
        } catch (Exception $e) {
            error_log("Error al registrar historial de acciones: " . $e->getMessage());
        }
        echo json_encode(['success' => true, 'message' => 'Tipo de programa eliminado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar. Puede estar en uso.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
}
?>
