<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}
include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit();
}

$action = $_POST['action'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$paquete = isset($_POST['paquete']) && $_POST['paquete'] !== '' ? (int) $_POST['paquete'] : null;
$codigo = isset($_POST['codigo']) ? (int) $_POST['codigo'] : null;

if ($action !== 'delete') {
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
        exit();
    }
}

mysqli_begin_transaction($conexion);
try {
    if ($action === 'create') {
        // Validar paquete si se envió
        if ($paquete !== null) {
            $stmt = $conexion->prepare("SELECT 1 FROM san_dim_paquete WHERE codigo = ?");
            $stmt->bind_param("i", $paquete);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_row()) {
                throw new Exception('El paquete seleccionado no existe.');
            }
        }

        // Evitar duplicados por nombre
        $stmt = $conexion->prepare("SELECT 1 FROM san_dim_analisis WHERE nombre = ?");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        if ($stmt->get_result()->fetch_row()) {
            throw new Exception('Ya existe un análisis con ese nombre.');
        }

        $stmt = $conexion->prepare("INSERT INTO san_dim_analisis (nombre, paquete) VALUES (?, ?)");
        $stmt->bind_param("si", $nombre, $paquete);

    } elseif ($action === 'update') {
        if (!$codigo)
            throw new Exception('Código no válido.');

        if ($paquete !== null) {
            $stmt = $conexion->prepare("SELECT 1 FROM san_dim_paquete WHERE codigo = ?");
            $stmt->bind_param("i", $paquete);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_row()) {
                throw new Exception('El paquete seleccionado no existe.');
            }
        }

        $stmt = $conexion->prepare("SELECT 1 FROM san_dim_analisis WHERE nombre = ? AND codigo != ?");
        $stmt->bind_param("si", $nombre, $codigo);
        $stmt->execute();
        if ($stmt->get_result()->fetch_row()) {
            throw new Exception('Ya existe otro análisis con ese nombre.');
        }

        $stmt = $conexion->prepare("UPDATE san_dim_analisis SET nombre = ?, paquete = ? WHERE codigo = ?");
        $stmt->bind_param("sii", $nombre, $paquete, $codigo);

    } elseif ($action === 'delete') {
        if (!$codigo)
            throw new Exception('Código no válido.');

        // Verificar uso en san_fact_solicitud_det
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM san_fact_solicitud_det WHERE codAnalisis = ?");
        $stmt->bind_param("i", $codigo);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];
        if ($count > 0) {
            throw new Exception("No se puede eliminar: el análisis está en uso en $count registro(s) de muestra.");
        }

        $stmt = $conexion->prepare("DELETE FROM san_dim_analisis WHERE codigo = ?");
        $stmt->bind_param("i", $codigo);

    } else {
        throw new Exception('Acción no válida.');
    }

    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar la operación.');
    }

    mysqli_commit($conexion);
    $mensaje = match ($action) {
        'create' => '✅ Análisis creado correctamente.',
        'update' => '✅ Análisis actualizado correctamente.',
        'delete' => '✅ Análisis eliminado correctamente.',
    };
    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    error_log("Error CRUD análisis: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

mysqli_close($conexion);
exit();