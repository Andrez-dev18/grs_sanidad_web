<?php
session_start();
//ruta relativa a la conexion
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$data = json_decode(file_get_contents("php://input"), true);
$idRegistro = $data['id'] ?? 0;

if ($idRegistro <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'ID de registro inválido']);
    exit;
}

// Obtener el codEnvio del registro a eliminar
$sql = "SELECT codEnvio, ubicacion FROM san_dim_historial_resultados WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idRegistro);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Registro no encontrado']);
    exit;
}

$registro = $result->fetch_assoc();
$codEnvio = $registro['codEnvio'];
$ubicacionActual = $registro['ubicacion'];

// === REGLA PRINCIPAL: Si el envío ya tiene Laboratorio, NO eliminar NADA ===
$tieneLaboratorio = $conn->query("
    SELECT 1 FROM san_dim_historial_resultados 
    WHERE codEnvio = '$codEnvio' AND ubicacion = 'Laboratorio' 
    LIMIT 1
")->num_rows > 0;

if ($tieneLaboratorio) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se puede eliminar: este envío ya fue recibido en Laboratorio (etapa final).'
    ]);
    exit;
}

// Si no tiene Laboratorio → permitir eliminar
$sqlDelete = "DELETE FROM san_dim_historial_resultados WHERE id = ?";
$stmtDelete = $conn->prepare($sqlDelete);
$stmtDelete->bind_param("i", $idRegistro);

if ($stmtDelete->execute()) {
    echo json_encode(['ok' => true, 'mensaje' => 'Registro eliminado correctamente']);
} else {
    echo json_encode(['ok' => false, 'mensaje' => 'Error al eliminar el registro']);
}

$stmtDelete->close();