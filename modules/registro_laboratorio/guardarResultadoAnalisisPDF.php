<?php
include_once '../../../conexion_grs_joya/conexion.php';
session_start();
date_default_timezone_set('America/Lima');  // Zona horaria de Perú

$conn = conectar_joya();

if (!$conn) {
    echo json_encode(["error" => "DB error"]);
    exit;
}

$codigoEnvio = $_POST["codigoEnvio"] ?? "";
$pos = $_POST["posSolicitud"] ?? "";
$user = $_SESSION['usuario'] ?? "";

if ($codigoEnvio == "" || $pos == "") {
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

if (!isset($_FILES["pdf"])) {
    echo json_encode(["error" => "No se recibió archivo"]);
    exit;
}

$file = $_FILES["pdf"];

//  VALIDACIÓN DE TAMAÑO (20 MB)
$maxSize = 20 * 1024 * 1024; // 20 MB
if ($file["size"] > $maxSize) {
    echo json_encode(["error" => "El archivo supera el límite de 20 MB"]);
    exit;
}

//  VALIDACIÓN DE EXTENSIONES PERMITIDAS
$permitidos = [
    "pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx",
    "jpg", "jpeg", "png", "txt", "csv"
];

$nombreOriginal = $file["name"];
$ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

if (!in_array($ext, $permitidos)) {
    echo json_encode(["error" => "Tipo de archivo no permitido"]);
    exit;
}

//  Crear carpeta destino si no existe
$carpeta = "../../uploads/resultados/";
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

//  NOMBRE FINAL DEL ARCHIVO (sin cambiar nombre original)
$nombreArchivo = $codigoEnvio . "_" . $pos . "_" . $nombreOriginal;
$nombreArchivo = str_replace(" ", "_", $nombreArchivo); // evitar espacios

$rutaFinal = $carpeta . $nombreArchivo;
$rutaRelativa = 'uploads/resultados/' . $nombreArchivo;
//  Guardar archivo
if (!move_uploaded_file($file["tmp_name"], $rutaFinal)) {
    echo json_encode(["error" => "No se pudo guardar el archivo"]);
    exit;
}

$tipo = "cualitativo";

// === OBTENER FECHA Y HORA ACTUAL ===
$fechaHoraActual = date('Y-m-d H:i:s');

//  Insertar registro
$sql = "
    INSERT INTO san_fact_resultado_archivo (codEnvio, posSolicitud, archRuta, tipo, usuarioRegistrador, fechaRegistro)
    VALUES ('$codigoEnvio', '$pos', '$rutaRelativa', '$tipo', '$user', '$fechaHoraActual')
";

if ($conn->query($sql)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => $conn->error]);
}
?>
