<?php
include_once 'conexion_grs_joya/conexion.php';
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

// === 1️⃣ Obtener datos base del detalle ===
$q = "
    SELECT posicionSolicitud, codigoReferencia, fechaToma
    FROM com_db_muestra_detalle
    WHERE codigoEnvio='$codigoEnvio'
    LIMIT 1
";

$res = $conn->query($q);
$base = $res->fetch_assoc();

$pos = $base["posicionSolicitud"];
$ref = $base["codigoReferencia"];
$fecha = $base["fechaToma"];

// === 2️⃣ Insertar cada análisis ===
foreach ($analisis as $a) {

    $cod = $a["analisisCodigo"];
    $nom = $conn->real_escape_string($a["analisisNombre"]);
    $resul = $conn->real_escape_string($a["resultado"]);

    // 🔹 Comentario opcional
    $obs = isset($a["observaciones"])
        ? $conn->real_escape_string($a["observaciones"])
        : "NULL";

    $sql = "
        INSERT INTO com_resultado_analisis 
        (codigoEnvio, posicionSolicitud, codigoReferencia, fechaToma, analisis_codigo, analisis_nombre, resultado, observaciones)
        VALUES 
        ('$codigoEnvio', '$pos', '$ref', '$fecha', '$cod', '$nom', '$resul', '$obs')
    ";

    $conn->query($sql);
}

//actualizar estado de cabecera
$conn->query("UPDATE com_db_muestra_cabecera SET estado = 'completado' WHERE codigoEnvio = '$codigoEnvio'");


echo json_encode(["success" => true]);
