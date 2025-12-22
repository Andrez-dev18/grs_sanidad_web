<?php 

include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$codigo = $_GET["codEnvio"] ?? "";
$pos = $_GET["posSolicitud"] ?? "";

if ($codigo == "") {
    echo json_encode(["error" => "Falta codEnvio"]);
    exit;
}

$q = "
    SELECT 
        c.codEnvio,
        MAX(d.codRef) AS codRef,
        c.fecEnvio,
        c.horaEnvio,
        c.codLab,
        c.nomLab,
        c.codEmpTrans,
        c.nomEmpTrans,
        c.usuarioRegistrador,
        c.usuarioResponsable,
        c.autorizadoPor,
        c.fechaHoraRegistro,
        c.estado,
        d.posSolicitud,

        CASE 
            WHEN SUM(d.estado_cuali = 'pendiente') > 0 THEN 'pendiente'
            ELSE 'completado'
        END AS estado_cuali_general

    FROM san_fact_solicitud_cab c
    INNER JOIN san_fact_solicitud_det d
        ON c.codEnvio = d.codEnvio

    WHERE c.codEnvio = '$codigo'
    AND d.posSolicitud = '$pos'
";

$res = $conn->query($q);

if (!$res || $res->num_rows == 0) {
    echo json_encode(["error" => "No existe cabecera"]);
    exit;
}

echo json_encode($res->fetch_assoc());


?>