<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$codigoEnvio = $_GET["codigoEnvio"] ?? "";
$pos = $_GET["posSolicitud"] ?? "";

if ($codigoEnvio === "" || $pos === "") {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT 
        id,
        archRuta,
        tipo,
        fechaRegistro
    FROM san_fact_resultado_archivo
    WHERE codEnvio = '$codigoEnvio'
      AND posSolicitud = '$pos'
    ORDER BY fechaRegistro DESC
";

$res = $conn->query($sql);

$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = [
        "id" => $row["id"],
        "ruta" => $row["archRuta"],
        "nombre" => basename($row["archRuta"]),
        "tipo" => $row["tipo"],
        "fecha" => $row["fechaRegistro"]
    ];
}

echo json_encode($data);
