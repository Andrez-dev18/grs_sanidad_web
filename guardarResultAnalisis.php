<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$input = json_decode(file_get_contents("php://input"), true);

$codigoEnvio = $input["codigoEnvio"] ?? "";
$analisis = $input["analisis"] ?? [];
$pos = $input["posicion"] ?? ""; // ← La posición enviada por el front

if ($codigoEnvio == "" || empty($analisis) || $pos == "") {
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

// Obtener info base correctamente usando la posición enviada
$q = "
    SELECT 
        codRef,
        fecToma
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

foreach ($analisis as $a) {

    $cod = $a["analisisCodigo"];
    $nom = $conn->real_escape_string($a["analisisNombre"]);
    $resul = $conn->real_escape_string($a["resultado"]);

    // Observación opcional
    $obs = isset($a["observaciones"]) && trim($a["observaciones"]) !== ""
        ? $conn->real_escape_string($a["observaciones"])
        : NULL;

    // Insertar resultado
    $sql = "
        INSERT INTO san_fact_resultado_analisis 
        (codEnvio, posSolicitud, codRef, fecToma, analisis_codigo, analisis_nombre, resultado, obs)
        VALUES 
        ('$codigoEnvio', '$pos', '$ref', '$fecha', '$cod', '$nom', '$resul', " .
        ($obs === NULL ? "NULL" : "'$obs'") . "
        )
    ";
    $conn->query($sql);

    // Actualizar estado del análisis
    $conn->query("
        UPDATE san_fact_solicitud_det 
        SET estado = 'completado'
        WHERE codEnvio = '$codigoEnvio'
          AND posSolicitud = '$pos'
          AND codAnalisis = '$cod'
    ");
}

// Verificar si quedan pendientes
$check = $conn->query("
    SELECT COUNT(*) AS pendientes
    FROM san_fact_solicitud_det
    WHERE codEnvio = '$codigoEnvio'
      AND estado = 'pendiente'
");

$row = $check->fetch_assoc();

// Si no quedan pendientes → completar cabecera
if ($row["pendientes"] == 0) {
    $conn->query("
        UPDATE san_fact_solicitud_cab
        SET estado = 'completado'
        WHERE codEnvio = '$codigoEnvio'
    ");
}

echo json_encode(["success" => true]);
