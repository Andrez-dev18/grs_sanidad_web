<?php
header('Content-Type: application/json');
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode([
        'pendientes' => 0,
        'completados' => 0,
        'debug' => ['error' => 'No conexión BD']
    ]);
    exit;
}

// === 1. ENVÍOS VÁLIDOS: tienen GRS ===
$sqlGRS = "SELECT DISTINCT codEnvio FROM san_dim_historial_resultados WHERE ubicacion = 'GRS'";
$resultGRS = $conn->query($sqlGRS);

if (!$resultGRS || $resultGRS->num_rows === 0) {
    echo json_encode([
        'pendientes' => 0,
        'completados' => 0,
        'debug' => ['pendientes' => [], 'completados' => []]
    ]);
    exit;
}

$enviosValidos = [];
while ($row = $resultGRS->fetch_assoc()) {
    $enviosValidos[] = $conn->real_escape_string($row['codEnvio']);
}

$totalValidos = count($enviosValidos);

$listaEnvios = "'" . implode("','", $enviosValidos) . "'";

// === 2. CONTAR COMPLETADOS: tienen GRS + Transporte + Laboratorio (sin importar orden) ===
$completados = 0;
$debugPendientes = [];
$debugCompletados = [];

foreach ($enviosValidos as $codEnvio) {
    // Contar ubicaciones distintas para este envío
    $sqlCount = "
        SELECT ubicacion, COUNT(*) as count
        FROM san_dim_historial_resultados
        WHERE codEnvio = '$codEnvio' AND ubicacion IN ('GRS', 'Transporte', 'Laboratorio')
        GROUP BY ubicacion
    ";
    $resultCount = $conn->query($sqlCount);

    $tieneGRS = false;
    $tieneTransporte = false;
    $tieneLab = false;

    while ($row = $resultCount->fetch_assoc()) {
        switch ($row['ubicacion']) {
            case 'GRS': $tieneGRS = true; break;
            case 'Transporte': $tieneTransporte = true; break;
            case 'Laboratorio': $tieneLab = true; break;
        }
    }

    // Ubicación actual (la más reciente)
    $sqlActual = "
        SELECT ubicacion
        FROM san_dim_historial_resultados
        WHERE codEnvio = '$codEnvio'
        ORDER BY fechaHoraRegistro DESC
        LIMIT 1
    ";
    $resultActual = $conn->query($sqlActual);
    $ubicacionActual = $resultActual && $resultActual->num_rows > 0 
        ? $resultActual->fetch_assoc()['ubicacion'] 
        : 'Sin registro';

    $itemDebug = [
        'codEnvio' => $codEnvio,
        'ubicacion_actual' => $ubicacionActual,
        'tiene_GRS' => $tieneGRS,
        'tiene_Transporte' => $tieneTransporte,
        'tiene_Laboratorio' => $tieneLab
    ];

    if ($tieneGRS && $tieneTransporte && $tieneLab) {
        $debugCompletados[] = $itemDebug;
        $completados++;
    } else {
        $debugPendientes[] = $itemDebug;
    }
}

$pendientes = $totalValidos - $completados;

echo json_encode([
    'pendientes' => $pendientes,
    'completados' => $completados,
    'debug' => [
        'pendientes' => $debugPendientes,
        'completados' => $debugCompletados
    ]
]);