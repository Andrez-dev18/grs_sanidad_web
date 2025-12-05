<?php
include_once 'conexion_grs_joya\conexion.php';
$conn = conectar_sanidad();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$codigoEnvio = $_GET["codigoEnvio"] ?? "";

if ($codigoEnvio == "") {
    echo json_encode(["error" => "codigoEnvio requerido"]);
    exit;
}

// 1️⃣ Obtener analisis del detalle
$q = "
    SELECT analisis 
    FROM com_db_muestra_detalle
    WHERE codigoEnvio = '$codigoEnvio'
";
$res = $conn->query($q);

if (!$res || $res->num_rows == 0) {
    echo json_encode([]);
    exit;
}

$row = $res->fetch_assoc();
$codigos = explode(",", $row["analisis"]); // "21,17,12" → [21,17,12]

// 2️⃣ Obtener info de cada análisis
$lista = [];

foreach ($codigos as $cod) {

    $sql = "
        SELECT 
            r.id,
            r.analisis AS codigoAnalisis,
            a.nombre AS nomAnalisis,
            r.tipo AS resultado
        FROM com_tipo_resultado r
        LEFT JOIN com_analisis a ON r.analisis = a.codigo
        WHERE r.analisis = '$cod'
    ";

    $rs = $conn->query($sql);

    $item = [
        "analisisCodigo" => $cod,
        "nombre" => "",
        "resultados" => []
    ];

    while ($r = $rs->fetch_assoc()) {
        $item["nombre"] = $r["nomAnalisis"];
        $item["resultados"][] = $r["resultado"];
    }

    $lista[] = $item;
}

echo json_encode($lista);
