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
$ok = @$conn->query("SHOW TABLES LIKE 'linea'");
if ($ok && $ok->num_rows > 0) {
    $res = $conn->query("SELECT linea, descri FROM linea ORDER BY linea ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $lista[] = [
                'linea' => (string)($row['linea'] ?? ''),
                'descri' => (string)($row['descri'] ?? ''),
                'text' => trim($row['linea'] ?? '') . ' - ' . trim($row['descri'] ?? '')
            ];
        }
    }
}

echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
