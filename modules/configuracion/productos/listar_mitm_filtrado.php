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

$lin = trim((string)($_GET['lin'] ?? ''));
$alma = trim((string)($_GET['alma'] ?? ''));
$tcodprove = trim((string)($_GET['tcodprove'] ?? ''));
$descri = trim((string)($_GET['descri'] ?? ''));

$tieneLin = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'lin'");
$tieneAlma = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'alma'");
$tieneLin = $tieneLin && $tieneLin->fetch_assoc();
$tieneAlma = $tieneAlma && $tieneAlma->fetch_assoc();

$lista = [];
$sql = "SELECT m.codigo, m.descri, m.tcodprove, m.dosis, c.nombre AS nombre_proveedor";
$sqlFrom = " FROM mitm m LEFT JOIN ccte c ON c.codigo = m.tcodprove";
$sqlWhere = " WHERE 1=1";
$params = [];
$types = '';

if ($tieneLin && $lin !== '') {
    $sqlWhere .= " AND m.lin = ?";
    $params[] = $lin;
    $types .= 's';
}
if ($tieneAlma && $alma !== '') {
    $sqlWhere .= " AND m.alma = ?";
    $params[] = $alma;
    $types .= 's';
}
if ($tcodprove !== '') {
    $sqlWhere .= " AND m.tcodprove = ?";
    $params[] = $tcodprove;
    $types .= 's';
}
if ($descri !== '') {
    $sqlWhere .= " AND m.descri LIKE ?";
    $params[] = '%' . $descri . '%';
    $types .= 's';
}

$chkUnidad = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'unidad'");
$tieneUnidad = $chkUnidad && $chkUnidad->fetch_assoc();
if ($tieneUnidad) $sql .= ", m.unidad";
if ($tieneLin) $sql .= ", m.lin";
if ($tieneAlma) $sql .= ", m.alma";
$sql .= $sqlFrom . $sqlWhere . " ORDER BY m.descri ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $item = [
            'codigo' => (string)($row['codigo'] ?? ''),
            'descri' => (string)($row['descri'] ?? ''),
            'tcodprove' => (string)($row['tcodprove'] ?? ''),
            'nombre_proveedor' => (string)($row['nombre_proveedor'] ?? ''),
            'dosis' => (string)($row['dosis'] ?? '')
        ];
        if ($tieneUnidad) $item['unidad'] = (string)($row['unidad'] ?? '');
        if ($tieneLin) $item['lin'] = (string)($row['lin'] ?? '');
        if ($tieneAlma) $item['alma'] = (string)($row['alma'] ?? '');
        $lista[] = $item;
    }
    $stmt->close();
}

echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
