<?php
include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

$search = $_POST['q'] ?? '';

$where = '';
if ($search !== '') {
    $where = "WHERE nombre LIKE '%$search%'";
}

$sql = "
    SELECT codigo, nombre
    FROM san_dim_analisis
    $where
    ORDER BY nombre
    LIMIT 10
";

$res = $conexion->query($sql);

$resultados = [];
while ($row = $res->fetch_assoc()) {
    $resultados[] = [
        'id' => $row['nombre'],
        'text' => $row['nombre']
    ];
}

echo json_encode($resultados);
