<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
include_once 'pdf_generador.php'; // Incluye la función

$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$codigoEnvio = $_GET['codigo'] ?? '';
if (!$codigoEnvio) {
    die("Código de envío no proporcionado.");
}

// Generar el PDF usando la función
$pdfContenido = generarPDFReporte($codigoEnvio, $conexion);

// Enviar el PDF al navegador
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="registro_' . urlencode($codigoEnvio) . '.pdf"');
echo $pdfContenido;
exit();