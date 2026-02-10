<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
$codTipo = isset($_GET['codTipo']) ? trim((string)$_GET['codTipo']) : '';
$chk = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'nomTipo'");
$tieneTipo = $chk && $chk->fetch_assoc();
$sql = $tieneTipo
    ? "SELECT codigo, nombre, codTipo, nomTipo FROM san_fact_programa_cab WHERE 1=1"
    : "SELECT codigo, nombre FROM san_fact_programa_cab WHERE 1=1";
$params = [];
$types = '';
if ($codTipo !== '' && is_numeric($codTipo)) {
    $sql .= " AND codTipo = ?";
    $params[] = (int)$codTipo;
    $types .= 'i';
}
$sql .= " ORDER BY codigo DESC";
if ($types !== '') {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query($sql);
    }
} else {
    $res = $conn->query($sql);
}
$lista = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $codigo = trim((string)($row['codigo'] ?? ''));
        $nombre = trim((string)($row['nombre'] ?? ''));
        $nomTipo = $tieneTipo ? trim((string)($row['nomTipo'] ?? '')) : '';
        $lista[] = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'nomTipo' => $nomTipo,
            'label' => $codigo . ' - ' . $nombre
        ];
    }
}
echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
