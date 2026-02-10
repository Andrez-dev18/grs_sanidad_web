<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : (isset($_POST['q']) ? trim($_POST['q']) : '');
$codigo_actual = isset($_GET['codigo_actual']) ? trim((string)$_GET['codigo_actual']) : (isset($_POST['codigo_actual']) ? trim((string)$_POST['codigo_actual']) : '');

$results = [];

// En edición: incluir siempre el registro actual para que aparezca seleccionado
if ($codigo_actual !== '') {
    $st = $conn->prepare("SELECT codigo, nombre FROM ccte WHERE codigo = ? AND TRIM(COALESCE(nombre, '')) <> ''");
    if ($st) {
        $st->bind_param("s", $codigo_actual);
        $st->execute();
        $res = $st->get_result();
        if ($row = $res->fetch_assoc()) {
            $results[] = ['id' => (string)$row['codigo'], 'text' => $row['nombre']];
        }
        $st->close();
    }
}

// Búsqueda por nombre: no proveedores (proveedor_programa = 0) o el actual si estamos editando
if (strlen($q) >= 1) {
    $term = '%' . $q . '%';
    if ($codigo_actual !== '') {
        $stmt = $conn->prepare("SELECT codigo, nombre FROM ccte WHERE TRIM(COALESCE(nombre, '')) <> '' AND nombre LIKE ? AND (COALESCE(proveedor_programa, 0) = 0 OR codigo = ?) AND codigo <> ? ORDER BY nombre LIMIT 50");
        if ($stmt) {
            $stmt->bind_param("sss", $term, $codigo_actual, $codigo_actual);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $results[] = ['id' => (string)$row['codigo'], 'text' => $row['nombre']];
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("SELECT codigo, nombre FROM ccte WHERE TRIM(COALESCE(nombre, '')) <> '' AND nombre LIKE ? AND COALESCE(proveedor_programa, 0) = 0 ORDER BY nombre LIMIT 50");
        if ($stmt) {
            $stmt->bind_param("s", $term);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $results[] = ['id' => (string)$row['codigo'], 'text' => $row['nombre']];
            }
            $stmt->close();
        }
    }
}

echo json_encode(['success' => true, 'results' => $results]);
