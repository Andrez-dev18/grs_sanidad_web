<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_sanidad();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$input = json_decode(file_get_contents("php://input"), true);

$codigoEnvio = $input["codigoEnvio"] ?? "";
$analisis = $input["analisis"] ?? [];

if ($codigoEnvio == "" || empty($analisis)) {
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

$q = "
    SELECT 
        posSolicitud,
        codRef,
        fecToma
    FROM com_db_solicitud_det
    WHERE codEnvio = '$codigoEnvio'
    LIMIT 1
";

$res = $conn->query($q);

if (!$res || $res->num_rows == 0) {
    echo json_encode(["error" => "No existe detalle para este códigoEnvio"]);
    exit;
}

$base = $res->fetch_assoc();

$pos = $base["posSolicitud"];
$ref = $base["codRef"];
$fecha = $base["fecToma"];


foreach ($analisis as $a) {

    $cod = $a["analisisCodigo"];
    $nom = $conn->real_escape_string($a["analisisNombre"]);
    $resul = $conn->real_escape_string($a["resultado"]);

    // observaciones opcional
    $obs = isset($a["observaciones"]) && trim($a["observaciones"]) !== ""
        ? $conn->real_escape_string($a["observaciones"])
        : NULL;

    $sql = "
        INSERT INTO com_resultado_analisis 
        (codEnvio, posSolicitud, codRef, fecToma, analisis_codigo, analisis_nombre, resultado, obs)
        VALUES 
        ('$codigoEnvio', '$pos', '$ref', '$fecha', '$cod', '$nom', '$resul', " . 
        ($obs === NULL ? "NULL" : "'$obs'") . "
        )
    ";

    $conn->query($sql);
}
//actualizar estado de cabecera
//$conn->query("UPDATE com_db_muestra_cabecera SET estado = 'completado' WHERE codigoEnvio = '$codigoEnvio'");

echo json_encode(["success" => true]);
