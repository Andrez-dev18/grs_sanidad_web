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
$sql = "SELECT cod_enf, nom_enf FROM tenfermedades ORDER BY nom_enf";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $lista[] = ['cod_enf' => (int)$row['cod_enf'], 'nom_enf' => $row['nom_enf']];
    }
}
echo json_encode(['success' => true, 'results' => $lista]);
