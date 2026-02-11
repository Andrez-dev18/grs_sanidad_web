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

$q = trim((string)($_GET['q'] ?? ''));
$lista = [];
$condProveedor = " AND LENGTH(TRIM(codigo)) = 11 AND codigo REGEXP '^[0-9]{11}\$'";
$sql = "SELECT codigo, nombre FROM ccte WHERE TRIM(COALESCE(nombre, '')) <> ''" . $condProveedor;
if ($q !== '') {
    $qLike = '%' . $conn->real_escape_string($q) . '%';
    $stmt = $conn->prepare("SELECT codigo, nombre FROM ccte WHERE TRIM(COALESCE(nombre, '')) <> '' AND (codigo LIKE ? OR nombre LIKE ?)" . $condProveedor . " ORDER BY nombre ASC");
    $stmt->bind_param('ss', $qLike, $qLike);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql . " ORDER BY nombre ASC");
}
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $lista[] = ['codigo' => (string)$row['codigo'], 'nombre' => (string)$row['nombre']];
    }
    if (isset($stmt)) $stmt->close();
}

echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
