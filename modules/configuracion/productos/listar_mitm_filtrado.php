<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Misma lógica de filtros que get_productos_programa.php (modal programas): lin, alma; más tcodprove y descri para el dashboard
$lin = trim((string)($_GET['lin'] ?? ''));
$alma = trim((string)($_GET['alma'] ?? ''));
$tcodprove = trim((string)($_GET['tcodprove'] ?? ''));
$descri = trim((string)($_GET['descri'] ?? ''));

// Comprobar columnas de mitm (en producción pueden no existir todas)
$chkLin = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'lin'");
$tieneLin = $chkLin && $chkLin->num_rows > 0;
$chkAlma = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'alma'");
$tieneAlma = $chkAlma && $chkAlma->num_rows > 0;
$chkTcodprove = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'tcodprove'");
$tieneTcodprove = $chkTcodprove && $chkTcodprove->num_rows > 0;
$chkDosis = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'dosis'");
$tieneDosis = $chkDosis && $chkDosis->num_rows > 0;
$chkUnidad = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'unidad'");
$tieneUnidad = $chkUnidad && $chkUnidad->num_rows > 0;

// SELECT solo columnas que existan en mitm; ccte existe y se usa para nombre_proveedor si mitm tiene tcodprove
$sql = "SELECT m.codigo, m.descri";
if ($tieneTcodprove) $sql .= ", m.tcodprove";
if ($tieneDosis) $sql .= ", m.dosis";
if ($tieneUnidad) $sql .= ", m.unidad";
if ($tieneLin) $sql .= ", m.lin";
if ($tieneAlma) $sql .= ", m.alma";
if ($tieneTcodprove) $sql .= ", c.nombre AS nombre_proveedor";
$sql .= " FROM mitm m";
if ($tieneTcodprove) $sql .= " LEFT JOIN ccte c ON c.codigo = m.tcodprove";
$sql .= " WHERE 1=1";

$params = [];
$types = '';

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
if ($tieneTcodprove && $tcodprove !== '') {
    $sql .= " AND m.tcodprove = ?";
    $params[] = $tcodprove;
    $types .= 's';
}
if ($descri !== '') {
    $sql .= " AND (m.descri LIKE ? OR m.codigo LIKE ?)";
    $params[] = '%' . $descri . '%';
    $params[] = '%' . $descri . '%';
    $types .= 'ss';
}

$sql .= " ORDER BY m.descri ASC";

$lista = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $item = [
                'codigo' => (string)($row['codigo'] ?? ''),
                'descri' => (string)($row['descri'] ?? ''),
                'tcodprove' => '',
                'nombre_proveedor' => '',
                'dosis' => '',
                'unidad' => '',
            ];
            if ($tieneTcodprove) $item['tcodprove'] = ($row['tcodprove'] === null || $row['tcodprove'] === '') ? '' : trim((string)$row['tcodprove']);
            if ($tieneTcodprove) $item['nombre_proveedor'] = trim((string)($row['nombre_proveedor'] ?? ''));
            if ($tieneDosis) $item['dosis'] = trim((string)($row['dosis'] ?? ''));
            if ($tieneUnidad) $item['unidad'] = trim((string)($row['unidad'] ?? ''));
            if ($tieneLin) $item['lin'] = (string)($row['lin'] ?? '');
            if ($tieneAlma) $item['alma'] = (string)($row['alma'] ?? '');
            $lista[] = $item;
        }
    }
    $stmt->close();
}

echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
