<?php
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

header('Content-Type: application/json');

// Obtener parámetros
$codigoEnvio = $_GET["codigoEnvio"] ?? "";
$pos = $_GET["posSolicitud"] ?? "";
$tipo = $_GET["tipo"] ?? "";

// Validar que los parámetros obligatorios existan
if ($codigoEnvio === "" || $pos === "") {
    echo json_encode([]);
    exit;
}

// Usar prepared statement para evitar inyección SQL
$sql = "
    SELECT 
        id,
        archRuta,
        tipo,
        fechaRegistro
    FROM san_fact_resultado_archivo
    WHERE codEnvio = ?
      AND posSolicitud = ?
      AND tipo = ?
    ORDER BY fechaRegistro DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Error en la preparación (raro, pero por seguridad)
    echo json_encode(['error' => 'Error en consulta']);
    exit;
}

// Bind parameters: todos son strings
$stmt->bind_param("sss", $codigoEnvio, $pos, $tipo);

if (!$stmt->execute()) {
    echo json_encode(['error' => 'Error al ejecutar consulta']);
    exit;
}

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "id"     => $row["id"],
        "ruta"   => $row["archRuta"],
        "nombre" => basename($row["archRuta"]),
        "tipo"   => $row["tipo"],
        "fecha"  => $row["fechaRegistro"] ? date('d/m/Y H:i', strtotime($row["fechaRegistro"])) : 'Sin fecha'
    ];
}

echo json_encode($data);

$stmt->close();
$conn->close();
?>