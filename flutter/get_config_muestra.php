<?php
// --- Encabezados CORS (copiados de tu archivo funcional) ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Conexión ---
include_once '../../conexion_grs_joya/conexion.php';
date_default_timezone_set('America/Lima');

$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'Error de conexión: ' . mysqli_connect_error()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Obtener parámetro 'tipo_muestra_codigo' ---
$tipoCodigo = $_POST['tipo_muestra_codigo'] ?? $_GET['tipo_muestra_codigo'] ?? null;

if (!$tipoCodigo || !is_string($tipoCodigo) || trim($tipoCodigo) === '') {
    echo json_encode([
        'status' => 400,
        'success' => false,
        'message' => 'Parámetro "tipo_muestra_codigo" es obligatorio y debe ser una cadena.'
    ], JSON_UNESCAPED_UNICODE);
    mysqli_close($conexion);
    exit;
}

$tipoCodigo = mysqli_real_escape_string($conexion, trim($tipoCodigo));

// --- 1. Consultar tipo de muestra ---
$tmQuery = mysqli_query($conexion, "
    SELECT codigo, nombre, lonCod
    FROM com_tipo_muestra 
    WHERE codigo = '$tipoCodigo'
    LIMIT 1
");
$tipoMuestra = mysqli_fetch_assoc($tmQuery);

if (!$tipoMuestra) {
    echo json_encode([
        'status' => 404,
        'success' => false,
        'message' => 'Tipo de muestra no encontrado.'
    ], JSON_UNESCAPED_UNICODE);
    mysqli_close($conexion);
    exit;
}

// --- 2. Consultar paquetes del tipo ---
$paquetes = [];
$paqQuery = mysqli_query($conexion, "
    SELECT codigo, nombre 
    FROM com_paquete_muestra
    WHERE tipoMuestra = '$tipoCodigo'
    ORDER BY nombre
");
while ($row = mysqli_fetch_assoc($paqQuery)) {
    $paquetes[] = $row;
}

// --- 3. Consultar análisis del tipo ---
$analisis = [];
$anaQuery = mysqli_query($conexion, "
    SELECT A.codigo, A.nombre, A.paquete 
            FROM com_analisis A
            JOIN com_paquete_muestra P ON A.paquete = P.codigo
            WHERE P.tipoMuestra = '$tipoCodigo' 
            ORDER BY nombre
");
while ($row = mysqli_fetch_assoc($anaQuery)) {
    $analisis[] = [
        'codigo' => $row['codigo'],
        'nombre' => $row['nombre'],
        // ⚠️ Asegúrate de usar el nombre correcto del campo: 'PaqueteAnalisis'
        'paquete' => $row['paquete'] ?: null
    ];
}

// --- Respuesta final ---
echo json_encode([
    'status' => 200,
    'success' => true,
    'message' => 'Configuración cargada correctamente.',
    'data' => [
        'tipo_muestra' => $tipoMuestra,
        'paquetes' => $paquetes,
        'analisis' => $analisis
    ]
], JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>