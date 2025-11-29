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

try {
    mysqli_autocommit($conexion, FALSE);
    $anio_actual = date('y');


    mysqli_query($conexion, "
        DELETE FROM com_codigos_pendientes 
        WHERE TIMESTAMPDIFF(HOUR, fecha_reserva, NOW()) >= 1
    ");




    $res = mysqli_query($conexion, "
        SELECT codigo FROM com_codigos_pendientes 
        ORDER BY fecha_reserva ASC 
        LIMIT 1
    ");

    if (mysqli_num_rows($res) > 0) {

        $row = mysqli_fetch_assoc($res);
        $codigo_final = $row['codigo'];

        $stmt = mysqli_prepare($conexion, "
            UPDATE com_codigos_pendientes 
            SET usuario_id = ?, fecha_reserva = NOW() 
            WHERE codigo = ?
        ");
        mysqli_stmt_bind_param($stmt, "is", $_SESSION['usuario'], $codigo_final);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {

        $res_cont = mysqli_query($conexion, "SELECT ultimo_numero, anio FROM com_contador_codigo WHERE id = 1 FOR UPDATE");
        if (!$res_cont || mysqli_num_rows($res_cont) === 0) {
            mysqli_query($conexion, "INSERT INTO com_contador_codigo (id, ultimo_numero, anio) VALUES (1, 0, '$anio_actual')");
            $ultimo_numero = 0;
            $anio_db = $anio_actual;
        } else {
            $row = mysqli_fetch_assoc($res_cont);
            $ultimo_numero = (int)$row['ultimo_numero'];
            $anio_db = $row['anio'];
        }

        if ($anio_db !== $anio_actual) {
            $nuevo_numero = 1;
            mysqli_query($conexion, "UPDATE com_contador_codigo SET ultimo_numero = 1, anio = '$anio_actual' WHERE id = 1");
        } else {
            $nuevo_numero = $ultimo_numero + 1;
            mysqli_query($conexion, "UPDATE com_contador_codigo SET ultimo_numero = $nuevo_numero WHERE id = 1");
        }

        $codigo_final = "SAN-0{$anio_actual}" . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);


        $stmt = mysqli_prepare($conexion, "
            INSERT INTO com_codigos_pendientes (codigo, usuario_id, fecha_reserva) 
            VALUES (?, ?, NOW())
        ");
        mysqli_stmt_bind_param($stmt, "si", $codigo_final, $_SESSION['usuario']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    mysqli_commit($conexion);
    echo json_encode(['codigo_envio' => $codigo_final]);

} catch (Exception $e) {
    mysqli_rollback($conexion);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>