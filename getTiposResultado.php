<?php
include_once 'conexion_grs_joya/conexion.php';
$conn = conectar_sanidad();

$codigo = $_GET["codigoAnalisis"];

$q = "
SELECT id, tipo 
FROM com_tipo_resultado 
WHERE analisis = '$codigo'
ORDER BY tipo
";

$res = $conn->query($q);

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row["tipo"];
}

echo json_encode($data);
