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

$results = [];
if (strlen($q) >= 1) {
    $term = '%' . $q . '%';
    $chk = $conn->query("SHOW COLUMNS FROM mitm LIKE 'producto_programa'");
    $tiene_producto_programa = $chk && $chk->fetch_assoc();
    $sql = $tiene_producto_programa
        ? "SELECT codigo, descri FROM mitm WHERE descri LIKE ? AND (COALESCE(producto_programa, 0) = 0) ORDER BY descri LIMIT 50"
        : "SELECT codigo, descri FROM mitm WHERE descri LIKE ? ORDER BY descri LIMIT 50";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $term);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $results[] = ['id' => (string)$row['codigo'], 'text' => $row['descri']];
        }
        $stmt->close();
    }
}

echo json_encode(['success' => true, 'results' => $results]);
