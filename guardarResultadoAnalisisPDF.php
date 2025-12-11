<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode(["error" => "DB error"]);
    exit;
}

$codigoEnvio = $_POST["codigoEnvio"] ?? "";
$pos = $_POST["posSolicitud"] ?? "";

if ($codigoEnvio == "" || $pos == "") {
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

if (!isset($_FILES["pdf"])) {
    echo json_encode(["error" => "No se recibió PDF"]);
    exit;
}

// Crear carpeta destino si no existe
$carpeta = "uploads/resultados/";
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

// Generar nombre único
$nombreOriginal = $_FILES["pdf"]["name"];
$ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);

$nombreArchivo = $codigoEnvio . "_" . $pos . "_" . time() . "." . $ext;

$rutaFinal = $carpeta . $nombreArchivo;

// Mover archivo
if (!move_uploaded_file($_FILES["pdf"]["tmp_name"], $rutaFinal)) {
    echo json_encode(["error" => "No se pudo guardar el archivo"]);
    exit;
}

// Obtener número siguiente de posArchivo
$q = "
    SELECT IFNULL(MAX(posArchivo), 0) + 1 AS nextPos
    FROM san_fact_resultadopdf
";
$res = $conn->query($q);
$row = $res->fetch_assoc();
$nextPos = $row["nextPos"];

// Insertar registro
$sql = "
    INSERT INTO san_fact_resultadopdf (codEnvio, posSolicitud, archRuta, posArchivo)
    VALUES ('$codigoEnvio', '$pos', '$rutaFinal', '$nextPos')
";

if ($conn->query($sql)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => $conn->error]);
}
