<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
include_once '../../conexion_grs_joya/conexion.php';

$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión'], JSON_UNESCAPED_UNICODE);
    exit;
}

$year = $_GET['year'] ?? null;
$whereConditions = [];
$params = [];
$types = "";

// Condición de año (si aplica)
if ($year && ctype_digit($year)) {
    $whereConditions[] = "YEAR(c.fechaHoraRegistro) = ?";
    $params[] = (int)$year;
    $types .= "i";
}

// Condición de nomMuestra no nulo ni vacío
$whereConditions[] = "d.nomMuestra IS NOT NULL AND d.nomMuestra != ''";

// Construir WHERE completo
$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

$sql = "
    SELECT 
        d.nomMuestra,
        COUNT(*) AS total
    FROM san_fact_solicitud_det d
    JOIN san_fact_solicitud_cab c ON d.codEnvio = c.codEnvio
    $whereClause
    GROUP BY d.nomMuestra
    ORDER BY total DESC
    LIMIT 10
";

if ($params) {
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conexion, $sql);
}

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Error en la consulta SQL: ' . mysqli_error($conexion)], JSON_UNESCAPED_UNICODE);
    mysqli_close($conexion);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'nomMuestra' => $row['nomMuestra'],
        'total' => (int)$row['total']
    ];
}

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
mysqli_close($conexion);