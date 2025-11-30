<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit();
}

//ruta relativa a la conexion
include_once 'conexion_grs_joya\conexion.php';
$conexion = conectar_sanidad();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit();
}

$tipoMuestraId = $_GET['tipo'] ?? null;
if (!$tipoMuestraId || !is_numeric($tipoMuestraId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de muestra inválido']);
    exit();
}

try {

    $tm = mysqli_fetch_assoc(mysqli_query($conexion, "
        SELECT codigo, nombre, longitud_codigo 
        FROM com_tipo_muestra 
        WHERE codigo = $tipoMuestraId
        ORDER BY codigo
    "));
    if (!$tm) {
        echo json_encode(['error' => 'Tipo de muestra no encontrado']);
        exit();
    }


    $paquetes = [];
    $paquetes_res = mysqli_query($conexion, "
        SELECT codigo, nombre 
        FROM com_paquetes_analisis 
        WHERE tipoMuestra = $tipoMuestraId 
        ORDER BY nombre
    ");
    while ($row = mysqli_fetch_assoc($paquetes_res)) {
        $paquetes[] = $row;
    }


    $analisis = [];
    $analisis_res = mysqli_query($conexion, "
        SELECT codigo, nombre, PaqueteAnalisis 
        FROM com_analisis 
        WHERE tipoMuestra = $tipoMuestraId 
        ORDER BY nombre
    ");
    while ($row = mysqli_fetch_assoc($analisis_res)) {
        $analisis[] = [
            'codigo' => (int)$row['codigo'],
            'nombre' => $row['nombre'],
            'paquete' => $row['PaqueteAnalisis'] ? (int)$row['PaqueteAnalisis'] : null
        ];
    }

    echo json_encode([
        'tipo_muestra' => [
            'codigo' => (int)$tm['codigo'],
            'nombre' => $tm['nombre'],
            'longitud_codigo' => (int)$tm['longitud_codigo']
        ],
        'paquetes' => $paquetes,
        'analisis' => $analisis
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>