<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode(['labels' => [], 'cualitativos' => [], 'cuantitativos' => []]);
    exit;
}

$periodo = $_GET['periodo'] ?? 'mes';

// Fecha actual
$hoy = new DateTime();
$anioActual = $hoy->format('Y');
$mesActual = $hoy->format('m');

$groupBy = '';
$format = '';
$whereFecha = '';

switch ($periodo) {
    case 'dia':
        $groupBy = 'DATE(fechaHoraRegistro)';
        $format = '%Y-%m-%d';
        $whereFecha = "AND fechaHoraRegistro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"; // últimos 30 días
        break;

    case 'semana':
        // Solo semanas del MES ACTUAL
        $groupBy = 'YEAR(fechaHoraRegistro), WEEK(fechaHoraRegistro, 1)';
        $format = 'Sem %v'; // "Sem 1", "Sem 2", etc.
        $whereFecha = "AND YEAR(fechaHoraRegistro) = $anioActual AND MONTH(fechaHoraRegistro) = $mesActual";
        break;

    case 'mes':
    default:
        $groupBy = 'YEAR(fechaHoraRegistro), MONTH(fechaHoraRegistro)';
        $format = '%Y-%m';
        $whereFecha = "AND fechaHoraRegistro >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)"; // últimos 12 meses
        break;
}

// === CUALITATIVOS ===
$sqlCuali = "
    SELECT DATE_FORMAT(fechaHoraRegistro, '$format') AS periodo,
           COUNT(*) AS count
    FROM san_fact_resultado_analisis
    WHERE resultado IS NOT NULL AND TRIM(resultado) != ''
    $whereFecha
    GROUP BY periodo
    ORDER BY periodo
";

$resultCuali = $conn->query($sqlCuali);

// === CUANTITATIVOS ===
$sqlCuanti = "
    SELECT DATE_FORMAT(fechaHoraRegistro, '$format') AS periodo,
           COUNT(*) AS count
    FROM san_analisis_pollo_bb_adulto
    WHERE gmean IS NOT NULL OR estado = 'completado'
    $whereFecha
    GROUP BY periodo
    ORDER BY periodo
";

$resultCuanti = $conn->query($sqlCuanti);

// Mapas para unir datos
$mapCuali = [];
$mapCuanti = [];

while ($row = $resultCuali->fetch_assoc()) {
    $mapCuali[$row['periodo']] = (int)$row['count'];
}
while ($row = $resultCuanti->fetch_assoc()) {
    $mapCuanti[$row['periodo']] = (int)$row['count'];
}

// Generar labels en orden natural
$allPeriods = array_unique(array_merge(array_keys($mapCuali), array_keys($mapCuanti)));
sort($allPeriods);

$labels = [];
$cualitativos = [];
$cuantitativos = [];

foreach ($allPeriods as $periodo) {
    $labels[] = $periodo;
    $cualitativos[] = $mapCuali[$periodo] ?? 0;
    $cuantitativos[] = $mapCuanti[$periodo] ?? 0;
}

echo json_encode([
    'labels' => $labels,
    'cualitativos' => $cualitativos,
    'cuantitativos' => $cuantitativos
]);