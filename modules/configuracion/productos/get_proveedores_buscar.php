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
$results = [];
// Sin término no devolvemos opciones (son demasiadas)
if ($q === '') {
    echo json_encode(['success' => true, 'results' => []]);
    $conn->close();
    exit;
}

// Buscar por codigo, nombre, sigla (codigo_proveedor) o descri (si existe la columna). Mostrar "codigo - descri"
$chkDescri = @$conn->query("SHOW COLUMNS FROM ccte LIKE 'descri'");
$tieneDescri = $chkDescri && $chkDescri->num_rows > 0;
$condiciones = "(nombre LIKE ? OR codigo LIKE ? OR COALESCE(codigo_proveedor,'') LIKE ?";
$params = ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%'];
$types = 'sss';
if ($tieneDescri) {
    $condiciones .= " OR COALESCE(descri,'') LIKE ?";
    $params[] = '%' . $q . '%';
    $types .= 's';
}
$condiciones .= ") AND COALESCE(proveedor_programa,0)=1";
$selectFields = $tieneDescri ? "codigo, nombre, descri" : "codigo, nombre";
$sql = "SELECT " . $selectFields . " FROM ccte WHERE " . $condiciones . " ORDER BY nombre ASC LIMIT 50";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $cod = (string)($row['codigo'] ?? '');
        $descriVal = $tieneDescri ? trim((string)($row['descri'] ?? '')) : '';
        $text = $descriVal !== '' ? ($cod . ' - ' . $descriVal) : ($cod . ' - ' . (string)($row['nombre'] ?? $cod));
        $results[] = ['id' => $cod, 'text' => $text];
    }
    $stmt->close();
}

echo json_encode(['success' => true, 'results' => $results]);
$conn->close();
