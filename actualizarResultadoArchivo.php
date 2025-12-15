<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$idArchivo = $_POST["idArchivo"] ?? "";
$codigoEnvio = $_POST["codigoEnvio"] ?? "";
$pos = $_POST["posSolicitud"] ?? "";

if ($idArchivo === "" || !isset($_FILES["archivo"])) {
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

$res = $conn->query("
    SELECT archRuta 
    FROM san_fact_resultado_archivo
    WHERE id = '$idArchivo'
");

if (!$res || $res->num_rows === 0) {
    echo json_encode(["error" => "Archivo no encontrado"]);
    exit;
}

$row = $res->fetch_assoc();
$rutaAnterior = $row["archRuta"];

// borrar archivo anterior
if (file_exists($rutaAnterior)) {
    unlink($rutaAnterior);
}

$file = $_FILES["archivo"];

$nombreFinal = $codigoEnvio . "_" . $pos . "_" . time() . "_" . $file["name"];
$nombreFinal = str_replace(" ", "_", $nombreFinal);
$rutaNueva = "uploads/resultados/" . $nombreFinal;

if (!move_uploaded_file($file["tmp_name"], $rutaNueva)) {
    echo json_encode(["error" => "No se pudo guardar archivo"]);
    exit;
}

$conn->query("
    UPDATE san_fact_resultado_archivo
    SET archRuta = '$rutaNueva',
        fechaRegistro = NOW()
    WHERE id = '$idArchivo'
");

echo json_encode(["success" => true]);
