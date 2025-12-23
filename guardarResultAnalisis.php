<?php
include_once '../conexion_grs_joya/conexion.php';
include_once 'historial_resultados.php';
session_start();
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$input = json_decode(file_get_contents("php://input"), true);

$codigoEnvio = $input["codigoEnvio"] ?? "";
$analisis = $input["analisis"] ?? [];
$pos = $input["posicion"] ?? "";
$estado = $input["estadoCuali"] ?? "";
$user = $_SESSION['usuario'] ?? null;

$accion = "Se registro resultados para este analisis";
$accionUpdate = "Se actualizo el resultado de los analisis";

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

//variables para la respuesta
$insertados = 0;
$actualizados = 0;
$estadosActualizados = 0;
$cabeceraCompletada = false;

$analisisInsertados = [];
$analisisActualizados = [];

foreach ($analisis as $a) {
    $cod = $a["analisisCodigo"];
    $nom = $conn->real_escape_string($a["analisisNombre"]);
    $resul = $conn->real_escape_string($a["resultado"]);

    $obs = isset($a["observaciones"]) && trim($a["observaciones"]) !== ""
        ? $conn->real_escape_string($a["observaciones"])
        : NULL;

    $idResultado = $a["id"] ?? null;
    if ($idResultado === "null" || $idResultado === "" || $idResultado === null) {
        $idResultado = null;
    }

    if ($idResultado !== null) {
        // 🔁 UPDATE
        $sql = "
            UPDATE san_fact_resultado_analisis
            SET 
                resultado = '$resul',
                obs = " . ($obs === NULL ? "NULL" : "'$obs'") . ",
                fechaLabRegistro = " . ($fechaLabRegistro === null ? "NULL" : "'$fechaLabRegistro'") . "
            WHERE id = '$idResultado'
        ";

        if ($conn->query($sql)) {
            $actualizados++;
            $analisisActualizados[] = $nom; // Guardamos el nombre
        }

    } else {
        // ➕ INSERT
        $sql = "
            INSERT INTO san_fact_resultado_analisis 
            (codEnvio, posSolicitud, codRef, fecToma, analisis_codigo, analisis_nombre, resultado, obs, usuarioRegistrador, fechaLabRegistro)
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
                " . ($user === null ? "NULL" : "'$user'") . ",
                " . ($fechaLabRegistro === null ? "NULL" : "'$fechaLabRegistro'") . "
            )
        ";

        if ($conn->query($sql)) {
            $insertados++;
            $analisisInsertados[] = $nom; // Guardamos el nombre
        }
    }
}

// === LOG ÚNICO CON TUS ACCIONES RESPETADAS ===
$partesComentario = [];

if ($insertados > 0) {
    $partesComentario[] = $accion . ": " . implode(", ", $analisisInsertados);
}

if ($actualizados > 0) {
    $partesComentario[] = $accionUpdate . ": " . implode(", ", $analisisActualizados);
}

$comentarioFinal = !empty($partesComentario)
    ? implode(". ", $partesComentario)
    : "No se realizaron cambios en los resultados";

insertarHistorial(
    $conn,
    $codigoEnvio,
    $pos,
    'registro_resultados_cualitativos',
    'cualitativo',
    $comentarioFinal,
    $user
);

// === AQUÍ LA CORRECCIÓN: Actualizar estado siempre al final ===
$conn->query("
    UPDATE san_fact_solicitud_det 
    SET estado_cuali = '$estado'
    WHERE codEnvio = '$codigoEnvio'
      AND posSolicitud = '$pos'
");

if ($conn->affected_rows > 0) {
    $estadosActualizados += $conn->affected_rows; // O simplemente $estadosActualizados = $conn->affected_rows;
} else {
    // Opcional: si quieres contar incluso si no cambió
    $estadosActualizados += 1; // o mantener como está
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
        $conn->query("
            UPDATE san_fact_solicitud_cab
            SET estado = 'completado'
            WHERE codEnvio = '$codigoEnvio'
        ");

        if ($conn->affected_rows > 0) {
            $cabeceraCompletada = true;
        }
    }
}


echo json_encode([
    "success" => true,
    "insertados" => $insertados,
    "actualizados" => $actualizados,
    "estadosActualizados" => $estadosActualizados,
    "posicionCompletada" => $posCompleta,
    "cabeceraCompletada" => $cabeceraCompletada
]);

