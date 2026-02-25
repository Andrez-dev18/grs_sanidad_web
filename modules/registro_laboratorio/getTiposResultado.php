<?php
include_once '../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();

$codigo = $_GET["codigoAnalisis"];

$q = "
SELECT codigo, tipo 
FROM san_dim_tiporesultado 
WHERE analisis = '$codigo'
ORDER BY tipo
";

$res = $conn->query($q);

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row["tipo"];
}

echo json_encode($data);

