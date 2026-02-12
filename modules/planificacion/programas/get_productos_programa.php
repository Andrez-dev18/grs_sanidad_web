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

// Búsqueda en mitm por descripción (sugerencias al escribir). Filtros opcionales: línea (lin), almacén (alma).
$q = isset($_GET['q']) ? trim($_GET['q']) : (isset($_POST['q']) ? trim($_POST['q']) : '');
$lin = isset($_GET['lin']) ? trim((string)$_GET['lin']) : '';
$alma = isset($_GET['alma']) ? trim((string)$_GET['alma']) : '';

$tieneLin = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'lin'");
$tieneLin = $tieneLin && $tieneLin->num_rows > 0;
$tieneAlma = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'alma'");
$tieneAlma = $tieneAlma && $tieneAlma->num_rows > 0;

$results = [];
$sql = "SELECT m.codigo, m.descri FROM mitm m WHERE 1=1";
$params = [];
$types = '';
if (strlen($q) >= 1) {
    $sql .= " AND (m.descri LIKE ? OR m.codigo LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $types .= 'ss';
}
if ($tieneLin && $lin !== '') {
    $sql .= " AND m.lin = ?";
    $params[] = $lin;
    $types .= 's';
}
if ($tieneAlma && $alma !== '') {
    $sql .= " AND m.alma = ?";
    $params[] = $alma;
    $types .= 's';
}
$sql .= " ORDER BY m.descri LIMIT 50";

if ($types !== '') {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $results[] = ['id' => (string)$row['codigo'], 'text' => (string)$row['codigo'] . ' - ' . (string)$row['descri'], 'codigo' => (string)$row['codigo'], 'descri' => (string)$row['descri']];
        }
        $stmt->close();
    }
} else {
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $results[] = ['id' => (string)$row['codigo'], 'text' => (string)$row['codigo'] . ' - ' . (string)$row['descri'], 'codigo' => (string)$row['codigo'], 'descri' => (string)$row['descri']];
        }
    }
}

echo json_encode(['success' => true, 'results' => $results]);
$conn->close();
