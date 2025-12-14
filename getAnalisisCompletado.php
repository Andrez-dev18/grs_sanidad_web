<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$codigo = $_GET["codigoEnvio"] ?? "";
$pos    = $_GET["posicion"] ?? "";

if ($codigo === "" || $pos === "") {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT 
        r.id,
        r.analisis_codigo,
        r.analisis_nombre,
        r.resultado,
        r.obs,
        r.fechaLabRegistro
    FROM san_fact_resultado_analisis r
    WHERE r.codEnvio = '$codigo'
      AND r.posSolicitud = '$pos'
";

$res = $conn->query($sql);

if (!$res) {
    http_response_code(500);
    echo json_encode([
        "error" => "SQL error resultados",
        "detalle" => $conn->error
    ]);
    exit;
}

$data = [];

while ($row = $res->fetch_assoc()) {

    // 🔹 Opciones del análisis
    $opciones = [];

    $sqlOpciones = "
        SELECT tipo
        FROM san_dim_tiporesultado
        WHERE analisis = '{$row['analisis_codigo']}'
        ORDER BY codigo
    ";

    $rs = $conn->query($sqlOpciones);

    if ($rs) {
        while ($o = $rs->fetch_assoc()) {
            $opciones[] = $o["tipo"];
        }
    }

    $data[] = [
        "id"               => $row["id"], 
        "analisis_codigo"  => $row["analisis_codigo"],
        "analisis_nombre"  => $row["analisis_nombre"],
        "resultado"        => $row["resultado"],
        "obs"              => $row["obs"],
        "fechaLabRegistro" => $row["fechaLabRegistro"],
        "opciones"         => $opciones
    ];
}

echo json_encode($data);
