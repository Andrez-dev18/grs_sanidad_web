<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit();
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión.']);
    exit();
}

if (file_exists('../../../includes/historial_acciones.php')) {
    include_once '../../../includes/historial_acciones.php';
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$nombre = trim($_POST['nom_enf'] ?? '');
$codigo = isset($_POST['cod_enf']) ? (int) $_POST['cod_enf'] : 0;

if ($action === 'create') {
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
        exit();
    }
    $stmt = $conexion->prepare("INSERT INTO tenfermedades (nom_enf) VALUES (?)");
    if ($stmt && $stmt->bind_param("s", $nombre) && $stmt->execute()) {
        $codigoGenerado = (int) $conexion->insert_id;
        if (function_exists('registrarAccion')) {
            $usuario = $_SESSION['usuario'] ?? 'sistema';
            $nom_usuario = $_SESSION['nombre'] ?? $usuario;
            try {
                registrarAccion($usuario, $nom_usuario, 'INSERT', 'tenfermedades', $codigoGenerado, null, json_encode(['cod_enf' => $codigoGenerado, 'nom_enf' => $nombre], JSON_UNESCAPED_UNICODE), 'Se creó una nueva enfermedad', null);
            } catch (Exception $e) {
                error_log("Error al registrar historial: " . $e->getMessage());
            }
        }
        echo json_encode(['success' => true, 'message' => 'Enfermedad creada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear la enfermedad.']);
    }
} elseif ($action === 'update') {
    if (empty($nombre) || !$codigo) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
        exit();
    }
    $datos_previos = null;
    $stmt_prev = $conexion->prepare("SELECT nom_enf FROM tenfermedades WHERE cod_enf = ?");
    if ($stmt_prev && $stmt_prev->bind_param("i", $codigo) && $stmt_prev->execute()) {
        $r = $stmt_prev->get_result();
        if ($row = $r->fetch_assoc()) {
            $datos_previos = json_encode(['cod_enf' => $codigo, 'nom_enf' => $row['nom_enf']], JSON_UNESCAPED_UNICODE);
        }
        $stmt_prev->close();
    }
    $stmt = $conexion->prepare("UPDATE tenfermedades SET nom_enf = ? WHERE cod_enf = ?");
    if ($stmt && $stmt->bind_param("si", $nombre, $codigo) && $stmt->execute()) {
        if (function_exists('registrarAccion')) {
            $usuario = $_SESSION['usuario'] ?? 'sistema';
            $nom_usuario = $_SESSION['nombre'] ?? $usuario;
            try {
                registrarAccion($usuario, $nom_usuario, 'UPDATE', 'tenfermedades', $codigo, $datos_previos, json_encode(['cod_enf' => $codigo, 'nom_enf' => $nombre], JSON_UNESCAPED_UNICODE), 'Se actualizó una enfermedad', null);
            } catch (Exception $e) {
                error_log("Error al registrar historial: " . $e->getMessage());
            }
        }
        echo json_encode(['success' => true, 'message' => 'Enfermedad actualizada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
    }
} elseif ($action === 'delete') {
    if (!$codigo) {
        echo json_encode(['success' => false, 'message' => 'Código no válido.']);
        exit();
    }
    $datos_previos = null;
    $stmt_prev = $conexion->prepare("SELECT nom_enf FROM tenfermedades WHERE cod_enf = ?");
    if ($stmt_prev && $stmt_prev->bind_param("i", $codigo) && $stmt_prev->execute()) {
        $r = $stmt_prev->get_result();
        if ($row = $r->fetch_assoc()) {
            $datos_previos = json_encode(['cod_enf' => $codigo, 'nom_enf' => $row['nom_enf']], JSON_UNESCAPED_UNICODE);
        }
        $stmt_prev->close();
    }
    $stmt = $conexion->prepare("DELETE FROM tenfermedades WHERE cod_enf = ?");
    if ($stmt && $stmt->bind_param("i", $codigo) && $stmt->execute()) {
        if (function_exists('registrarAccion')) {
            $usuario = $_SESSION['usuario'] ?? 'sistema';
            $nom_usuario = $_SESSION['nombre'] ?? $usuario;
            try {
                registrarAccion($usuario, $nom_usuario, 'DELETE', 'tenfermedades', $codigo, $datos_previos, null, 'Se eliminó una enfermedad', null);
            } catch (Exception $e) {
                error_log("Error al registrar historial: " . $e->getMessage());
            }
        }
        echo json_encode(['success' => true, 'message' => 'Enfermedad eliminada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
}
