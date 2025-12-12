<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$input = json_decode(file_get_contents("php://input"), true);

$codigoEnvio = $input["codigoEnvio"] ?? "";
$analisis = $input["analisis"] ?? [];
$pos = $input["posicion"] ?? "";

if ($codigoEnvio == "" || empty($analisis) || $pos == "") {
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

$fechaLabRegistro = null;
if (!empty($analisis) && isset($analisis[0]["fechaLabRegistro"])) {
    $fechaLabRegistro = $conn->real_escape_string($analisis[0]["fechaLabRegistro"]);
}

// Obtener datos base
$q = "
    SELECT codRef, fecToma
    FROM san_fact_solicitud_det
    WHERE codEnvio = '$codigoEnvio'
      AND posSolicitud = '$pos'
    LIMIT 1
";

$res = $conn->query($q);

if (!$res || $res->num_rows == 0) {
    echo json_encode(["error" => "No existe detalle para este códigoEnvio + posición"]);
    exit;
}

$base = $res->fetch_assoc();
$ref = $base["codRef"];
$fecha = $base["fecToma"];

// GUARDAR RESULTADOS CUALITATIVOS + MARCAR estado_cuali
foreach ($analisis as $a) {

    $cod = $a["analisisCodigo"];
    $nom = $conn->real_escape_string($a["analisisNombre"]);
    $resul = $conn->real_escape_string($a["resultado"]);

    $obs = isset($a["observaciones"]) && trim($a["observaciones"]) !== ""
        ? $conn->real_escape_string($a["observaciones"])
        : NULL;

    // Insertar resultado
    $sql = "
         INSERT INTO san_fact_resultado_analisis 
            (codEnvio, posSolicitud, codRef, fecToma, analisis_codigo, analisis_nombre, resultado, obs, fechaLabRegistro)
            VALUES 
            (
                '$codigoEnvio', 
                '$pos', 
                '$ref', 
                '$fecha', 
                '$cod', 
                '$nom', 
                '$resul', 
                " . ($obs === NULL ? "NULL" : "'$obs'") . ",
                " . ($fechaLabRegistro === null ? "NULL" : "'$fechaLabRegistro'") . "
            )

        ";
    $conn->query($sql);

    //  MARCAR SOLO LO CUALITATIVO
    $conn->query("
        UPDATE san_fact_solicitud_det 
        SET estado_cuali = 'completado'
        WHERE codEnvio = '$codigoEnvio'
          AND posSolicitud = '$pos'
          AND codAnalisis = '$cod'
    ");
}

// ------------------------------------------------------------
// VERIFICAR SI TODA ESTA POSICIÓN YA TIENE AMBOS ESTADOS OK
// ------------------------------------------------------------

$checkPos = $conn->query("
    SELECT COUNT(*) AS pendientes
    FROM san_fact_solicitud_det
    WHERE codEnvio = '$codigoEnvio'
      AND posSolicitud = '$pos'
      AND (
            estado_cuali = 'pendiente'
         OR estado_cuanti = 'pendiente'
      )
");

$rowPos = $checkPos->fetch_assoc();
$posCompleta = ($rowPos["pendientes"] == 0);

// ------------------------------------------------------------
//  SI TODAS LAS POSICIONES DE ESTE ENVÍO ESTÁN COMPLETAS 
// (cuali y cuanti), SE COMPLETA LA CABECERA
// ------------------------------------------------------------

if ($posCompleta) {

    // Revisar si TODAS las posiciones están completas
    $checkCab = $conn->query("
        SELECT COUNT(*) AS pendientes
        FROM san_fact_solicitud_det
        WHERE codEnvio = '$codigoEnvio'
          AND (
                estado_cuali = 'pendiente'
             OR estado_cuanti = 'pendiente'
          )
    ");

    $rowCab = $checkCab->fetch_assoc();

    if ($rowCab["pendientes"] == 0) {
        // 🔥 COMPLETAR CABECERA SOLO SI NADA FALTA
        $conn->query("
            UPDATE san_fact_solicitud_cab
            SET estado = 'completado'
            WHERE codEnvio = '$codigoEnvio'
        ");
    }
}

echo json_encode(["success" => true]);
