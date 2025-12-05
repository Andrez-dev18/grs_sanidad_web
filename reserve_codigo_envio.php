<?php
while (ob_get_level())
    ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

include_once '../conexion_grs_joya/conexion.php';

$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['error' => 'Error de conexión'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    mysqli_autocommit($conexion, false);
    $anio_actual = date('y'); // Ej. "25" para 2025

    // Leer y bloquear el contador
    $res = mysqli_query($conexion, "SELECT ultimo_numero, anio FROM com_contador_codigo WHERE id = 1 FOR UPDATE");

    if (!$res || mysqli_num_rows($res) === 0) {
        // Inicializar el contador si no existe
        mysqli_query($conexion, "INSERT INTO com_contador_codigo (id, ultimo_numero, anio) VALUES (1, 0, '$anio_actual')");
        $ultimo_numero = 0;
        $anio_db = $anio_actual;
    } else {
        $row = mysqli_fetch_assoc($res);
        $ultimo_numero = (int) $row['ultimo_numero'];
        $anio_db = $row['anio'];
    }

    // Reiniciar si es un año nuevo
    if ($anio_db !== $anio_actual) {
        $nuevo_numero = 1;
        mysqli_query($conexion, "UPDATE com_contador_codigo SET ultimo_numero = 1, anio = '$anio_actual' WHERE id = 1");
    } else {
        $nuevo_numero = $ultimo_numero + 1;

    }

    $codigo = "SAN-0{$anio_actual}" . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);

    mysqli_commit($conexion);
    mysqli_autocommit($conexion, true);

    echo json_encode(['codigo_envio' => $codigo], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(['error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
}
?>