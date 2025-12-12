<?php 

include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$codigo = $_GET["codEnvio"] ?? "";

if ($codigo == "") {
    echo json_encode(["error" => "Falta codEnvio"]);
    exit;
}

$q = "
    SELECT 
        codEnvio,
        fecEnvio,
        horaEnvio,
        codLab,
        nomLab,
        codEmpTrans,
        nomEmpTrans,
        usuarioRegistrador,
        usuarioResponsable,
        autorizadoPor,
        fechaHoraRegistro,
        estado
    FROM san_fact_solicitud_cab
    WHERE codEnvio = '$codigo'
";

$res = $conn->query($q);

if (!$res || $res->num_rows == 0) {
    echo json_encode(["error" => "No existe cabecera"]);
    exit;
}

echo json_encode($res->fetch_assoc());


?>