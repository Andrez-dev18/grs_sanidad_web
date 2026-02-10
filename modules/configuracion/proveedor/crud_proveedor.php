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
$codigo_ccte = trim((string)($_POST['codigo_ccte'] ?? ''));
$sigla = trim($_POST['sigla'] ?? '');
$codigo_actual = trim((string)($_POST['codigo_actual'] ?? ''));

if ($action === 'create') {
    if ($codigo_ccte === '') {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar un registro de la lista.']);
        exit();
    }
    // Guardar sql_mode actual y usar modo no estricto para evitar "Truncated incorrect DOUBLE value" con otros campos de ccte
    $resMode = $conexion->query("SELECT @@SESSION.sql_mode");
    $sqlModeOriginal = ($resMode && $row = $resMode->fetch_row()) ? $row[0] : '';
    $conexion->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");

    // Registrar proveedor: solo cambiar proveedor_programa a 1 y guardar codigo_proveedor (sigla)
    $proveedor_programa = 1; // SMALLINT: 1 = es proveedor
    $siglaVal = $sigla === '' ? '' : $sigla;
    $stmt = $conexion->prepare("UPDATE ccte SET proveedor_programa = ?, codigo_proveedor = ? WHERE codigo = ?");
    if (!$stmt) {
        if ($sqlModeOriginal !== '') $conexion->query("SET SESSION sql_mode = '" . $conexion->real_escape_string($sqlModeOriginal) . "'");
        echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conexion->error]);
        exit();
    }
    $stmt->bind_param("iss", $proveedor_programa, $siglaVal, $codigo_ccte);
    if ($stmt->execute()) {
        if ($sqlModeOriginal !== '') $conexion->query("SET SESSION sql_mode = '" . $conexion->real_escape_string($sqlModeOriginal) . "'");
        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'No se encontró el registro o ya es proveedor. Código: ' . $codigo_ccte]);
            $stmt->close();
            exit();
        }
        $usuario = $_SESSION['usuario'] ?? 'sistema';
        $nom_usuario = $_SESSION['nombre'] ?? $usuario;
        $datos_nuevos = json_encode(['codigo' => $codigo_ccte, 'codigo_proveedor' => $sigla], JSON_UNESCAPED_UNICODE);
        try {
            registrarAccion($usuario, $nom_usuario, 'UPDATE', 'ccte', $codigo_ccte, null, $datos_nuevos, 'Proveedor dado de alta (proveedor_programa=1)', null);
        } catch (Exception $e) {
            error_log("Error historial: " . $e->getMessage());
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Proveedor registrado correctamente.']);
    } else {
        if ($sqlModeOriginal !== '') $conexion->query("SET SESSION sql_mode = '" . $conexion->real_escape_string($sqlModeOriginal) . "'");
        $err = $conexion->error;
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Error al registrar: ' . $err]);
    }
} elseif ($action === 'update') {
    if ($codigo_ccte === '' || $codigo_actual === '') {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
        exit();
    }
    $siglaVal = $sigla === '' ? '' : $sigla;

    if ($codigo_ccte === $codigo_actual) {
        // Mismo registro: solo actualizar codigo_proveedor
        $stmt = $conexion->prepare("UPDATE ccte SET codigo_proveedor = ? WHERE codigo = ?");
        if ($stmt && $stmt->bind_param("ss", $siglaVal, $codigo_actual) && $stmt->execute()) {
            $usuario = $_SESSION['usuario'] ?? 'sistema';
            $nom_usuario = $_SESSION['nombre'] ?? $usuario;
            $datos_nuevos = json_encode(['codigo' => $codigo_actual, 'codigo_proveedor' => $sigla], JSON_UNESCAPED_UNICODE);
            try {
                registrarAccion($usuario, $nom_usuario, 'UPDATE', 'ccte', $codigo_actual, null, $datos_nuevos, 'Proveedor actualizado (sigla)', null);
            } catch (Exception $e) {
                error_log("Error historial: " . $e->getMessage());
            }
            echo json_encode(['success' => true, 'message' => 'Proveedor actualizado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
        }
    } else {
        // Cambió de registro: quitar proveedor al anterior y dar de alta al nuevo
        $conexion->begin_transaction();
        try {
            $st0 = $conexion->prepare("UPDATE ccte SET proveedor_programa = 0 WHERE codigo = ?");
            $st0->bind_param("s", $codigo_actual);
            $st0->execute();
            $st0->close();

            $st1 = $conexion->prepare("UPDATE ccte SET proveedor_programa = 1, codigo_proveedor = ? WHERE codigo = ?");
            $st1->bind_param("ss", $siglaVal, $codigo_ccte);
            $st1->execute();
            $st1->close();

            $conexion->commit();
            $usuario = $_SESSION['usuario'] ?? 'sistema';
            $nom_usuario = $_SESSION['nombre'] ?? $usuario;
            try {
                registrarAccion($usuario, $nom_usuario, 'UPDATE', 'ccte', $codigo_actual, null, json_encode(['proveedor_programa' => 0], JSON_UNESCAPED_UNICODE), 'Proveedor cambiado (anterior)', null);
                registrarAccion($usuario, $nom_usuario, 'UPDATE', 'ccte', $codigo_ccte, null, json_encode(['proveedor_programa' => 1, 'codigo_proveedor' => $sigla], JSON_UNESCAPED_UNICODE), 'Proveedor cambiado (nuevo)', null);
            } catch (Exception $e) {
                error_log("Error historial: " . $e->getMessage());
            }
            echo json_encode(['success' => true, 'message' => 'Proveedor actualizado correctamente.']);
        } catch (Exception $e) {
            $conexion->rollback();
            echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
        }
    }
} elseif ($action === 'delete') {
    if ($codigo_actual === '') {
        echo json_encode(['success' => false, 'message' => 'Código no válido.']);
        exit();
    }
    $stmt = $conexion->prepare("UPDATE ccte SET proveedor_programa = 0 WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("s", $codigo_actual) && $stmt->execute()) {
        $usuario = $_SESSION['usuario'] ?? 'sistema';
        $nom_usuario = $_SESSION['nombre'] ?? $usuario;
        try {
            registrarAccion($usuario, $nom_usuario, 'UPDATE', 'ccte', $codigo_actual, null, json_encode(['proveedor_programa' => 0], JSON_UNESCAPED_UNICODE), 'Proveedor dado de baja', null);
        } catch (Exception $e) {
            error_log("Error historial: " . $e->getMessage());
        }
        echo json_encode(['success' => true, 'message' => 'Proveedor eliminado (ya no se muestra en la lista).']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
}
?>
