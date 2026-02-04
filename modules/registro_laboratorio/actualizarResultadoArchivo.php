<?php
include_once '../../../conexion_grs_joya/conexion.php';
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
$rutaAnteriorRelativa = $row["archRuta"]; // ej: uploads/resultados/archivo.pdf

// === RAÍZ DEL PROYECTO (portable, sin nombre fijo) ===
$raizProyecto = dirname(__DIR__, 2); // Sube 2 niveles desde modules/registro_laboratorio

// === RUTA ABSOLUTA PARA OPERACIONES EN SERVIDOR ===
$carpetaResultados = $raizProyecto . '/uploads/resultados/';
$rutaAnteriorAbsoluta = $carpetaResultados . basename($rutaAnteriorRelativa);

// Borrar archivo anterior si existe
if (file_exists($rutaAnteriorAbsoluta)) {
    unlink($rutaAnteriorAbsoluta);
}

$file = $_FILES["archivo"];

$nombreFinal = $codigoEnvio . "_" . $pos . "_" . time() . "_" . $file["name"];
$nombreFinal = str_replace(" ", "_", $nombreFinal);

// === GUARDAR NUEVO ARCHIVO ===
$rutaCompleta = $carpetaResultados . $nombreFinal;

// Crear carpeta si no existe
if (!is_dir($carpetaResultados)) {
    mkdir($carpetaResultados, 0755, true);
}

if (!move_uploaded_file($file["tmp_name"], $rutaCompleta)) {
    echo json_encode(["error" => "No se pudo guardar el archivo en el servidor"]);
    exit;
}

// === RUTA RELATIVA PARA GUARDAR EN BD ===
$rutaNuevaBD = 'uploads/resultados/' . $nombreFinal;

$conn->query("
    UPDATE san_fact_resultado_archivo
    SET archRuta = '$rutaNuevaBD',
        fechaRegistro = NOW()
    WHERE id = '$idArchivo'
");

echo json_encode(["success" => true]);

