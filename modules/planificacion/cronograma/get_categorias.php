<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
$chk = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'categoria'");
$tieneCategoria = $chk && $chk->num_rows > 0;
$data = [];
if ($tieneCategoria) {
    $r = @$conn->query("SELECT DISTINCT TRIM(categoria) AS categoria FROM san_fact_programa_cab WHERE TRIM(categoria) <> '' ORDER BY categoria");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $cat = trim((string)($row['categoria'] ?? ''));
            if ($cat !== '' && !in_array($cat, $data)) $data[] = $cat;
        }
    }
}
if (empty($data)) {
    $data = ['PROGRAMA SANITARIO', 'SEGUIMIENTO SANITARIO'];
}
$conn->close();
echo json_encode(['success' => true, 'data' => $data]);
