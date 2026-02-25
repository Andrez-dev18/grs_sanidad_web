<?php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => [], 'message' => 'No autorizado']);
    exit();
}

include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Error de conexion']);
    exit();
}

$hasNotificar = false;
$chk = @$conn->query("SHOW COLUMNS FROM usuario LIKE 'notificar'");
if ($chk && $chk->num_rows > 0) $hasNotificar = true;

if (!$hasNotificar) {
    echo json_encode(['success' => true, 'data' => []]);
    $conn->close();
    exit();
}

$sql = "SELECT codigo, nombre, COALESCE(telefo, '') AS telefono
        FROM usuario
        WHERE estado = 'A'
          AND IFNULL(notificar, 0) = 1
          AND TRIM(COALESCE(telefo, '')) <> ''
        ORDER BY nombre ASC, codigo ASC";
$res = @$conn->query($sql);
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $tel = preg_replace('/\D/', '', (string)($row['telefono'] ?? ''));
        if ($tel === '') continue;
        $data[] = [
            'codigo' => (string)($row['codigo'] ?? ''),
            'nombre' => (string)($row['nombre'] ?? ''),
            'telefono' => $tel
        ];
    }
}

$conn->close();
echo json_encode(['success' => true, 'data' => $data]);

