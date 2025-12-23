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

    if ($estado === "pendiente") {
        $conditions[] = "
            (
                d.estado_cuali = 'pendiente'
                OR
                d.estado_cuanti = 'pendiente'
            )
        ";
    }

    if ($estado === "completado") {
        $conditions[] = "
            (
                d.estado_cuali = 'completado'
                AND
                d.estado_cuanti = 'completado'
            )
        ";
    }
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

//protegido si no tiene algun where para filtrar
$where = "";
if (count($conditions) > 0) {
    $where = "WHERE " . implode(" AND ", $conditions);
}


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

        -- ESTADO CUALI
        CASE 
            WHEN SUM(d.estado_cuali = 'pendiente') > 0 THEN 'pendiente'
            ELSE 'completado'
        END AS estado_cuali,

        -- ESTADO CUANTI
        CASE 
            WHEN SUM(d.estado_cuanti = 'pendiente') > 0 THEN 'pendiente'
            ELSE 'completado'
        END AS estado_cuanti,

        -- ESTADO GENERAL
        CASE 
            WHEN SUM(d.estado_cuali = 'pendiente') = 0 AND SUM(d.estado_cuanti = 'pendiente') = 0
            THEN 'completado'
            ELSE 'pendiente'
        END AS estado_general,

        MIN(d.fecToma) AS fecToma,
        MIN(d.codRef) AS codRef,
        MIN(d.numMuestras) AS numeroMuestras,
        GROUP_CONCAT(DISTINCT d.nomMuestra SEPARATOR ', ') AS nomMuestra,
        MIN(c.nomLab) AS nomLab,

        -- === NUEVOS CAMPOS QUE TE FALTABAN ===
        GROUP_CONCAT(DISTINCT d.nomAnalisis ORDER BY d.nomAnalisis SEPARATOR ', ') AS analisis,
        GROUP_CONCAT(DISTINCT d.codAnalisis ORDER BY d.codAnalisis SEPARATOR ', ') AS analisisCodigos,
        GROUP_CONCAT(DISTINCT a.enfermedad ORDER BY a.enfermedad SEPARATOR ', ') AS analisisEnfermedades

    FROM san_fact_solicitud_det d
    INNER JOIN san_fact_solicitud_cab c ON c.codEnvio = d.codEnvio
    LEFT JOIN san_dim_analisis a ON a.codigo = d.codAnalisis  -- Para traer la enfermedad
    $where
    GROUP BY d.codEnvio, d.posSolicitud
    ORDER BY d.codEnvio DESC, d.posSolicitud ASC
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
