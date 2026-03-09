<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit();
}

include_once '../../../../conexion_grs/conexion.php';
$conexion = conectar_joya_mysqli();
if (!$conexion) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de conexión.']);
    exit();
}

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit();
}

$tablaCab = 'san_fact_estandares_cab';
$tablaDet = 'san_fact_estandares_det';

// Eliminar registro (cabecera y sus detalles)
if (array_key_exists('eliminarId', $data)) {
    $id = (int) $data['eliminarId'];
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Id inválido.']);
        exit();
    }
    $stmtDet = $conexion->prepare("DELETE FROM " . $tablaDet . " WHERE idEstandarCab = ?");
    if ($stmtDet && $stmtDet->bind_param("i", $id) && $stmtDet->execute()) {
        $stmtDet->close();
    } else {
        if ($stmtDet) $stmtDet->close();
    }
    $stmtCab = $conexion->prepare("DELETE FROM " . $tablaCab . " WHERE id = ?");
    if ($stmtCab && $stmtCab->bind_param("i", $id) && $stmtCab->execute()) {
        $stmtCab->close();
        echo json_encode(['success' => true, 'message' => 'Registro eliminado correctamente.']);
    } else {
        if ($stmtCab) $stmtCab->close();
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
    exit();
}

// Crear o actualizar registro: { nombre, subprocesos } o { id, nombre, subprocesos }
$nombre = isset($data['nombre']) ? trim((string) $data['nombre']) : '';
$subprocesos = isset($data['subprocesos']) && is_array($data['subprocesos']) ? $data['subprocesos'] : [];
$idRegistro = isset($data['id']) ? (int) $data['id'] : 0;

if ($nombre === '') {
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
    exit();
}

if ($idRegistro > 0) {
    // Actualizar: UPDATE cab, DELETE det, INSERT det
    $stmtCab = $conexion->prepare("UPDATE " . $tablaCab . " SET nombre = ? WHERE id = ?");
    if (!$stmtCab || !$stmtCab->bind_param("si", $nombre, $idRegistro) || !$stmtCab->execute()) {
        if ($stmtCab) $stmtCab->close();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar cabecera.']);
        exit();
    }
    $stmtCab->close();
    $stmtDel = $conexion->prepare("DELETE FROM " . $tablaDet . " WHERE idEstandarCab = ?");
    if ($stmtDel && $stmtDel->bind_param("i", $idRegistro) && $stmtDel->execute()) {
        $stmtDel->close();
    } else {
        if ($stmtDel) $stmtDel->close();
    }
    $idEstandarCab = $idRegistro;
} else {
    // Crear: INSERT cab, obtener id, INSERT det
    $stmtCab = $conexion->prepare("INSERT INTO " . $tablaCab . " (nombre) VALUES (?)");
    if (!$stmtCab || !$stmtCab->bind_param("s", $nombre) || !$stmtCab->execute()) {
        if ($stmtCab) $stmtCab->close();
        echo json_encode(['success' => false, 'message' => 'Error al crear cabecera.']);
        exit();
    }
    $idEstandarCab = (int) $conexion->insert_id;
    $stmtCab->close();
}

$sqlDet = "INSERT INTO " . $tablaDet . " (idEstandarCab, subproceso, actividad, tipo, parametro, unidades, stdMin, stdMax) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmtDet = $conexion->prepare($sqlDet);
if (!$stmtDet) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar inserción de detalle.']);
    exit();
}

$insertados = 0;
foreach ($subprocesos as $nodo) {
    $subproceso = isset($nodo['subproceso']) ? trim((string) $nodo['subproceso']) : '';
    $actividades = isset($nodo['actividades']) && is_array($nodo['actividades']) ? $nodo['actividades'] : [];
    foreach ($actividades as $actNode) {
        $actividad = isset($actNode['actividad']) ? trim((string) $actNode['actividad']) : '';
        $filas = isset($actNode['filas']) && is_array($actNode['filas']) ? $actNode['filas'] : [];
        foreach ($filas as $f) {
            $tipo = isset($f['tipo']) ? trim((string) $f['tipo']) : '';
            $parametro = isset($f['parametro']) ? trim((string) $f['parametro']) : '';
            $unidades = isset($f['unidades']) ? trim((string) $f['unidades']) : '';
            $stdMin = isset($f['stdMin']) ? trim((string) $f['stdMin']) : '';
            $stdMax = isset($f['stdMax']) ? trim((string) $f['stdMax']) : '';
            $stmtDet->bind_param("isssssss", $idEstandarCab, $subproceso, $actividad, $tipo, $parametro, $unidades, $stdMin, $stdMax);
            if ($stmtDet->execute()) $insertados++;
        }
    }
}
$stmtDet->close();
echo json_encode(['success' => true, 'message' => 'Registro guardado correctamente.', 'id' => $idEstandarCab, 'insertados' => $insertados]);
