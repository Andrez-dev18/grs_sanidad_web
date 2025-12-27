<?php
session_start();
include '../conexion_grs_joya/conexion.php';

$conn = conectar_joya();

$data = json_decode(file_get_contents("php://input"), true);
$codigo = $data['codigo'] ?? '';

if (!$codigo) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Código de envío vacío'
    ]);
    exit;
}

$sql = "
    SELECT 
        c.codEnvio,
        c.fecEnvio,
        c.horaEnvio,
        c.nomLab,
        c.nomEmpTrans,
        c.estado,
        COUNT(d.id) AS totalAnalisis
    FROM san_fact_solicitud_cab c
    INNER JOIN san_fact_solicitud_det d 
        ON c.codEnvio = d.codEnvio
    WHERE c.codEnvio = ?
    GROUP BY 
        c.codEnvio,
        c.fecEnvio,
        c.horaEnvio,
        c.nomLab,
        c.nomEmpTrans,
        c.estado
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("❌ Error prepare buscar_orden: " . $conn->error);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error interno al preparar la consulta'
    ]);
    exit;
}

$stmt->bind_param("s", $codigo);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    echo json_encode([
        'ok'            => true,
        'codEnvio'      => $row['codEnvio'],
        'fecEnvio'      => $row['fecEnvio'],
        'horaEnvio'     => $row['horaEnvio'],
        'nomLab'        => $row['nomLab'],
        'nomEmpTrans'   => $row['nomEmpTrans'],
        'estado'        => $row['estado'],
        'totalAnalisis' => (int)$row['totalAnalisis']
    ]);

} else {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se encontró ninguna orden con ese código'
    ]);
}

$stmt->close();
$conn->close();
