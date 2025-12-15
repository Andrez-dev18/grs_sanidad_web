<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$page  = intval($_GET["page"] ?? 1);
$limit = intval($_GET["limit"] ?? 10);
$offset = ($page - 1) * $limit;

$fechaInicio = $_GET['fechaInicio'] ?? null;
$fechaFin    = $_GET['fechaFin'] ?? null;
$estado      = $_GET['estado'] ?? "pendiente";
$nomLab = trim($_GET['lab'] ?? '');
$qSearch     = trim($_GET['q'] ?? '');

// Construir condiciones
$conditions = [];

if ($estado !== "todos") {
    $conditions[] = "d.estado_cuali = '" . $conn->real_escape_string($estado) . "'";
}

if (!empty($fechaInicio)) {
    $conditions[] = "d.fecToma >= '" . $conn->real_escape_string($fechaInicio) . "'";
}

if (!empty($fechaFin)) {
    $conditions[] = "d.fecToma <= '" . $conn->real_escape_string($fechaFin) . "'";
}

if (!empty($qSearch)) {
    $qEsc = $conn->real_escape_string($qSearch);
    $conditions[] = "(d.codEnvio LIKE '%$qEsc%' OR d.codRef LIKE '%$qEsc%')";
}

if (!empty($nomLab)) {
    $labEsc = $conn->real_escape_string($nomLab);
    $conditions[] = "c.nomLab = '$labEsc'";
}

if (count($conditions) === 0) {
    $conditions[] = "d.estado_cuali = 'pendiente'";
}

$where = "WHERE " . implode(" AND ", $conditions);


// -------- TOTAL --------
$countQuery = "
    SELECT COUNT(*) AS total
    FROM (
        SELECT d.codEnvio, d.posSolicitud
        FROM san_fact_solicitud_det d
        INNER JOIN san_fact_solicitud_cab c
            ON c.codEnvio = d.codEnvio
        $where
        GROUP BY d.codEnvio, d.posSolicitud
    ) AS temp
";

$resCount = $conn->query($countQuery);
if (!$resCount) {
    die("SQL ERROR COUNT: " . $conn->error . " --- QUERY: $countQuery");
}
$total = (int)$resCount->fetch_assoc()["total"];

// -------- REGISTROS PAGINADOS --------
$query = "
    SELECT 
        d.codEnvio,
        d.posSolicitud,

        CASE 
            WHEN SUM(d.estado_cuali = 'pendiente') > 0 THEN 'pendiente'
            ELSE 'completado'
        END AS estado_cuali,

        MIN(d.fecToma) AS fecToma,
        MIN(d.codRef) AS codRef,
        MIN(d.numMuestras) AS numeroMuestras,
        MIN(c.nomLab) AS nomLab

    FROM san_fact_solicitud_det d
    INNER JOIN san_fact_solicitud_cab c
        ON c.codEnvio = d.codEnvio

    $where
    GROUP BY d.codEnvio, d.posSolicitud
    ORDER BY d.codEnvio DESC, fecToma DESC
    LIMIT $limit OFFSET $offset
";


$result = $conn->query($query);
if (!$result) {
    die("SQL ERROR DATA: " . $conn->error . " --- QUERY: $query");
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    "data"  => $data,
    "total" => $total,
    "page"  => $page,
    "limit" => $limit
]);
