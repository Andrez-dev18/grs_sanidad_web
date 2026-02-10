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

$lista = [];
$chk = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'alma'");
if ($chk && $chk->fetch_assoc()) {
    $res = $conn->query("SELECT DISTINCT alma FROM mitm WHERE alma IS NOT NULL AND TRIM(alma) <> '' ORDER BY alma ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $alma = trim((string)($row['alma'] ?? ''));
            if ($alma !== '') $lista[] = ['alma' => $alma, 'text' => $alma];
        }
    }
}

echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
