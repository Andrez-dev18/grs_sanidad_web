<?php
include_once '../conexion_grs_joya\conexion.php';
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$codigoEnvio = $_GET["codigoEnvio"] ?? "";
$posicion = $_GET["posicion"] ?? "";

if ($codigoEnvio == "") {
    echo json_encode(["error" => "codigoEnvio requerido"]);
    exit;
}if ($posicion == "") {
    echo json_encode(["error" => "posicion requerida"]);
    exit;
}

// 1Obtener todos los análisis del detalle para este envío
$q = "
    SELECT codAnalisis, nomAnalisis
    FROM san_fact_solicitud_det
    WHERE codEnvio = '$codigoEnvio'
    AND posSolicitud = '$posicion'
";
$res = $conn->query($q);

if (!$res || $res->num_rows == 0) {
    echo json_encode([]);
    exit;
}

$lista = [];

while ($row = $res->fetch_assoc()) {

    $codAnalisis = $row["codAnalisis"];
    $nomAnalisis = $row["nomAnalisis"];

    //  Obtener resultados posibles del análisis
    $sql = "
        SELECT 
            r.codigo,
            r.analisis AS codigoAnalisis,
            r.tipo AS resultado
        FROM san_dim_tiporesultado r
        WHERE r.analisis = '$codAnalisis'
        ORDER BY r.codigo ASC
    ";

    $rs = $conn->query($sql);

    $item = [
        "analisisCodigo" => $codAnalisis,
        "nombre" => $nomAnalisis,
        "resultados" => []
    ];

    while ($r = $rs->fetch_assoc()) {
        $item["resultados"][] = $r["resultado"];
    }

    $lista[] = $item;
}

echo json_encode($lista);
?>
