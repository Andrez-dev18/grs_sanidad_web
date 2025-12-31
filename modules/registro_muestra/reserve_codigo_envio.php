<?php
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

if (!$conexion) {
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

try {

    // BLOQUEAR TABLA
    mysqli_query($conexion, "LOCK TABLES san_fact_solicitud_cab WRITE");

    $anio_actual = date('y');

    // Obtener último código
    $sql = "
        SELECT codEnvio
        FROM san_fact_solicitud_cab
        WHERE codEnvio LIKE 'SAN-0{$anio_actual}%'
        ORDER BY codEnvio DESC
        LIMIT 1
    ";

    $res = mysqli_query($conexion, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $ultimo = $row['codEnvio'];
        $numero = intval(substr($ultimo, -4));
        $nuevo_numero = $numero + 1;
    } else {
        $nuevo_numero = 1;
    }

    // Generar código final
    $codigo = "SAN-0{$anio_actual}" . str_pad($nuevo_numero, 4, "0", STR_PAD_LEFT);

    // LIBERAR TABLAS
    mysqli_query($conexion, "UNLOCK TABLES");

    echo json_encode(['codigo_envio' => $codigo]);

} catch (Exception $e) {

    mysqli_query($conexion, "UNLOCK TABLES");
    echo json_encode(['error' => 'Error interno']);
}
