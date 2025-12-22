<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

header('Content-Type: application/json');

if (!isset($_POST['codEnvio'], $_POST['posSolicitud'], $_POST['tipo'], $_POST['nuevoEstado'])) {
    echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
    exit;
}

$codEnvio = $_POST['codEnvio'];
$posSolicitud = $_POST['posSolicitud'];
$tipo = strtolower($_POST['tipo']); // 'cualitativo' or 'cuantitativo'
$nuevoEstado = $_POST['nuevoEstado'];

if (!in_array($nuevoEstado, ['completado', 'pendiente'])) {
    echo json_encode(['success' => false, 'message' => 'Estado inválido']);
    exit;
}

// Mapear tipo a campo
if ($tipo === 'cualitativo') {
    $campo = 'estado_cuali';
} elseif ($tipo === 'cuantitativo') {
    $campo = 'estado_cuanti';
} else {
    echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
    exit;
}

// === 1. Actualizar estado ===
$stmt = $conn->prepare("
    UPDATE san_fact_solicitud_det 
    SET $campo = ?
    WHERE codEnvio = ?
      AND posSolicitud = ?
");

$stmt->bind_param("ssi", $nuevoEstado, $codEnvio, $posSolicitud);
$stmt->execute();

$affected = $stmt->affected_rows;

$response = [
    'success' => true,
    'updated' => $affected > 0,
    'affected_rows' => $affected
];

if ($affected > 0) {
    $response['message'] = "Estado $tipo actualizado a '$nuevoEstado' en $affected registros";
} else {
    $response['message'] = "No se actualizó ningún registro. Posiblemente ya estaba en '$nuevoEstado'";
}

// === 2. Verificar si la posición quedó completa ===
$checkPos = $conn->prepare("
    SELECT COUNT(*) AS pendientes
    FROM san_fact_solicitud_det
    WHERE codEnvio = ?
      AND posSolicitud = ?
      AND (estado_cuali = 'pendiente' OR estado_cuanti = 'pendiente')
");
$checkPos->bind_param("si", $codEnvio, $posSolicitud);
$checkPos->execute();
$rowPos = $checkPos->get_result()->fetch_assoc();
$posCompleta = ($rowPos['pendientes'] == 0);
$response['posCompleta'] = $posCompleta;

// === 3. Si la posición está completa, verificar si el envío completo ===
$cabeceraCompletada = false;
if ($posCompleta) {
    $checkCab = $conn->prepare("
        SELECT COUNT(*) AS pendientes
        FROM san_fact_solicitud_det
        WHERE codEnvio = ?
          AND (estado_cuali = 'pendiente' OR estado_cuanti = 'pendiente')
    ");
    $checkCab->bind_param("s", $codEnvio);
    $checkCab->execute();
    $rowCab = $checkCab->get_result()->fetch_assoc();

    if ($rowCab['pendientes'] == 0) {
        $updateCab = $conn->prepare("UPDATE san_fact_solicitud_cab SET estado = 'completado' WHERE codEnvio = ?");
        $updateCab->bind_param("s", $codEnvio);
        $updateCab->execute();
        $cabeceraCompletada = ($updateCab->affected_rows > 0);
        $response['cabeceraUpdated'] = $cabeceraCompletada;
        $updateCab->close();
    }
    $checkCab->close();
}
$response['cabeceraCompletada'] = $cabeceraCompletada;

// Cerrar recursos
$stmt->close();
$checkPos->close();
$conn->close();

echo json_encode($response);
?>

