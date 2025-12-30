<?php
header('Content-Type: application/json');
include '../conexion_grs_joya/conexion.php';

$conn = conectar_joya();

$data = json_decode(file_get_contents("php://input"), true);
$codigo = $data['codigo'] ?? '';

if (!$codigo) {
    echo json_encode(['ok' => false, 'mensaje' => 'Código vacío']);
    exit;
}

/* ================= CAB ================= */
$sqlCab = "
    SELECT 
        codEnvio,
        fecEnvio,
        horaEnvio,
        nomLab,
        nomEmpTrans,
        estado
    FROM san_fact_solicitud_cab
    WHERE codEnvio = ?
";

$stmtCab = $conn->prepare($sqlCab);
$stmtCab->bind_param("s", $codigo);
$stmtCab->execute();
$cab = $stmtCab->get_result()->fetch_assoc();

if (!$cab) {
    echo json_encode(['ok' => false, 'mensaje' => 'Envío no encontrado']);
    exit;
}

/* ================= DET RESUMEN ================= */
$sqlDet = "
    SELECT 
        COUNT(*) AS totalAnalisis
    FROM san_fact_solicitud_det
    WHERE codEnvio = ?
";

$stmtDet = $conn->prepare($sqlDet);
$stmtDet->bind_param("s", $codigo);
$stmtDet->execute();
$det = $stmtDet->get_result()->fetch_assoc();

/* ================= HISTORIAL ================= */
$sqlHist = "
    SELECT 
        accion,
        comentario,
        usuario,
        ubicacion,
        fechaHoraRegistro,
        evidencia
    FROM san_dim_historial_resultados
    WHERE codEnvio = ?
    ORDER BY fechaHoraRegistro ASC
";

$stmtHist = $conn->prepare($sqlHist);
$stmtHist->bind_param("s", $codigo);
$stmtHist->execute();

$historial = [];
$resHist = $stmtHist->get_result();
while ($row = $resHist->fetch_assoc()) {
    $historial[] = $row;
}

echo json_encode([
    'ok' => true,
    'envio' => [
        'codEnvio' => $cab['codEnvio'],
        'fecEnvio' => $cab['fecEnvio'],
        'horaEnvio' => $cab['horaEnvio'],
        'nomLab' => $cab['nomLab'],
        'nomEmpTrans' => $cab['nomEmpTrans'],
        'estado' => $cab['estado'],
        'totalAnalisis' => (int)$det['totalAnalisis']
    ],
    'historial' => $historial
]);
