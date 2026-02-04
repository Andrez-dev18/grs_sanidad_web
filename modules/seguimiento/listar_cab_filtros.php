<?php
include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

// Parámetros DataTables
$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

// Filtros personalizados
$fechaInicio = $_POST['fechaInicio'] ?? '';
$fechaFin = $_POST['fechaFin'] ?? '';
$estado = $_POST['estado'] ?? '';
$laboratorio = $_POST['laboratorio'] ?? '';
$empTrans = $_POST['empTrans'] ?? '';
$muestra = $_POST['muestra'] ?? '';
$analisis = $_POST['analisis'] ?? '';
$granjas = $_POST['granjas'] ?? [];
$galpon = $_POST['galpon'] ?? '';
$edadDesde = $_POST['edadDesde'] ?? '';
$edadHasta = $_POST['edadHasta'] ?? '';


$where = " WHERE 1=1 ";

/* FILTROS */
if ($fechaInicio && $fechaFin) {
    $fechaInicio = mysqli_real_escape_string($conexion, $fechaInicio);
    $fechaFin = mysqli_real_escape_string($conexion, $fechaFin);
    $where .= " AND c.fecEnvio BETWEEN '$fechaInicio' AND '$fechaFin' ";
}

if ($estado) {
    $estado = mysqli_real_escape_string($conexion, $estado);
    $where .= " AND c.estado = '$estado' ";
}

if ($laboratorio) {
    $laboratorio = mysqli_real_escape_string($conexion, $laboratorio);
    $where .= " AND c.nomLab = '$laboratorio' ";
}

if ($empTrans) {
    $empTrans = mysqli_real_escape_string($conexion, $empTrans);
    $where .= " AND c.nomEmpTrans = '$empTrans' ";
}

if ($muestra) {
    $muestra = mysqli_real_escape_string($conexion, $muestra);
    $where .= " AND d.nomMuestra = '$muestra' ";
}

if ($analisis) {
    $analisis = mysqli_real_escape_string($conexion, $analisis);
    $where .= " AND d.nomAnalisis = '$analisis' ";
}

if (!empty($granjas) && is_array($granjas)) {
    // Escapar cada valor
    $granjasEscaped = array_map(function($g) use ($conexion) {
        return "'" . mysqli_real_escape_string($conexion, $g) . "'";
    }, $granjas);

    $where .= " AND LEFT(d.codRef, 3) IN (" . implode(',', $granjasEscaped) . ") ";
}

if ($galpon) {
    $galpon = mysqli_real_escape_string($conexion, $galpon);
    $where .= " AND SUBSTRING(d.codRef, 7, 2) = '$galpon' ";
}

if ($edadDesde !== '' && $edadHasta !== '') {
    $edadDesde = (int)$edadDesde;
    $edadHasta = (int)$edadHasta;

    $where .= " AND CAST(RIGHT(d.codRef, 2) AS UNSIGNED) 
                BETWEEN $edadDesde AND $edadHasta ";
}


if ($edadDesde !== '' && $edadHasta === '') {
    $edadDesde = (int)$edadDesde;

    $where .= " AND CAST(RIGHT(d.codRef, 2) AS UNSIGNED) >= $edadDesde ";
}


if ($edadDesde === '' && $edadHasta !== '') {
    $edadHasta = (int)$edadHasta;

    $where .= " AND CAST(RIGHT(d.codRef, 2) AS UNSIGNED) <= $edadHasta ";
}


/* SEARCH GLOBAL */
if (!empty($search)) {
    $search = mysqli_real_escape_string($conexion, $search);
    $where .= " AND (
        c.codEnvio LIKE '%$search%' OR
        c.nomLab LIKE '%$search%' OR
        c.nomEmpTrans LIKE '%$search%' OR
        c.usuarioRegistrador LIKE '%$search%' OR
        c.usuarioResponsable LIKE '%$search%' OR
        d.nomMuestra LIKE '%$search%' OR
        d.nomAnalisis LIKE '%$search%'
    )";
}

/* TOTAL SIN FILTROS - Contar registros únicos de cabecera */
$totalSinFiltrosQuery = mysqli_query($conexion, "
    SELECT COUNT(DISTINCT c.codEnvio) as total
    FROM san_fact_solicitud_cab c
    LEFT JOIN san_fact_solicitud_det d ON c.codEnvio = d.codEnvio
");
$totalSinFiltros = mysqli_fetch_assoc($totalSinFiltrosQuery)['total'];

/* TOTAL CON FILTROS */
$totalFiltradosQuery = mysqli_query($conexion, "
    SELECT COUNT(DISTINCT c.codEnvio) as total
    FROM san_fact_solicitud_cab c
    LEFT JOIN san_fact_solicitud_det d ON c.codEnvio = d.codEnvio
    $where
");
$totalFiltrados = mysqli_fetch_assoc($totalFiltradosQuery)['total'];

/* DATA */
$dataQuery = mysqli_query($conexion, "
    SELECT 
        c.codEnvio,
        c.fecEnvio,
        c.horaEnvio,
        c.nomLab,
        c.nomEmpTrans,
        c.usuarioRegistrador,
        c.usuarioResponsable,
        c.autorizadoPor,
        c.estado,
        
        GROUP_CONCAT(DISTINCT d.nomMuestra ORDER BY d.posSolicitud SEPARATOR ', ') as muestras,
        GROUP_CONCAT(DISTINCT d.nomAnalisis ORDER BY d.posSolicitud SEPARATOR ', ') as analisis,
        (SELECT COUNT(*) FROM san_plan_link_muestra lm WHERE lm.codEnvio = c.codEnvio) as enlace_plan
        
    FROM san_fact_solicitud_cab c
    LEFT JOIN san_fact_solicitud_det d ON c.codEnvio = d.codEnvio
    $where
    GROUP BY c.codEnvio
    ORDER BY c.codEnvio DESC
    LIMIT $start, $length
");

$data = [];
while ($row = mysqli_fetch_assoc($dataQuery)) {
    $row['enlace_plan'] = !empty($row['enlace_plan']) && (int)$row['enlace_plan'] > 0;
    $data[] = $row;
}

mysqli_close($conexion);

echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $totalSinFiltros,
    "recordsFiltered" => $totalFiltrados,
    "data" => $data
]);
?>