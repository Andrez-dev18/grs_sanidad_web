<?php
include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

$draw = intval($_REQUEST['draw']);
$start = intval($_REQUEST['start']);
$length = intval($_REQUEST['length']);
$search = $_REQUEST['search']['value'] ?? '';

// Filtros personalizados
$fechaInicio = $_REQUEST['fechaInicio'] ?? '';
$fechaFin = $_REQUEST['fechaFin'] ?? '';
$estado = $_REQUEST['estado'] ?? '';
$laboratorio = $_REQUEST['laboratorio'] ?? '';
$tipoMuestra = $_REQUEST['tipoMuestra'] ?? '';
$tipoAnalisis = $_REQUEST['tipoAnalisis'] ?? '';
$granja = $_REQUEST['granja'] ?? '';
$galpon = $_REQUEST['galpon'] ?? '';
$edadDesde = $_REQUEST['edadDesde'] ?? '';
$edadHasta = $_REQUEST['edadHasta'] ?? '';

$where = " WHERE 1=1 ";

// Búsqueda global
if (!empty($search)) {
    $search = mysqli_real_escape_string($conexion, $search);
    $where .= " AND (
        a.codEnvio LIKE '%$search%' OR
        a.nomLab LIKE '%$search%' OR
        a.nomEmpTrans LIKE '%$search%' OR
        b.nomMuestra LIKE '%$search%' OR
        b.nomAnalisis LIKE '%$search%' OR
        c.resultado LIKE '%$search%' OR
        c.obs LIKE '%$search%'
    )";
}

// FILTROS PERSONALIZADOS
if ($fechaInicio) {
    $where .= " AND a.fecEnvio >= '" . mysqli_real_escape_string($conexion, $fechaInicio) . "'";
}
if ($fechaFin) {
    $where .= " AND a.fecEnvio <= '" . mysqli_real_escape_string($conexion, $fechaFin) . "'";
}

if ($estado) {
    $estado = $estado === 'Completado' ? 'completado' : 'pendiente';
    $where .= " AND b.estado_cuali = '" . mysqli_real_escape_string($conexion, $estado) . "'";
}

if ($laboratorio) {
    $where .= " AND a.nomLab = '" . mysqli_real_escape_string($conexion, $laboratorio) . "'";
}

if ($tipoMuestra) {
    $where .= " AND b.nomMuestra = '" . mysqli_real_escape_string($conexion, $tipoMuestra) . "'";
}

if ($tipoAnalisis) {
    $where .= " AND b.nomAnalisis LIKE '%" . mysqli_real_escape_string($conexion, $tipoAnalisis) . "%'";
}

if ($granja) {
    $where .= " AND LEFT(b.codRef, 3) = '" . mysqli_real_escape_string($conexion, $granja) . "'";
}

if ($galpon) {
    $where .= " AND SUBSTRING(b.codRef, 7, 2) = '" . mysqli_real_escape_string($conexion, $galpon) . "'";
}

if ($edadDesde !== '') {
    $where .= " AND CAST(RIGHT(b.codRef, 2) AS UNSIGNED) >= " . (int)$edadDesde;
}
if ($edadHasta !== '') {
    $where .= " AND CAST(RIGHT(b.codRef, 2) AS UNSIGNED) <= " . (int)$edadHasta;
}

// Total sin filtros
$totalQuery = "SELECT COUNT(*) as total FROM san_fact_solicitud_cab a";
$totalResult = mysqli_query($conexion, $totalQuery);
$recordsTotal = mysqli_fetch_assoc($totalResult)['total'];

// Total filtrado
$filteredQuery = "
    SELECT COUNT(*) as total
    FROM san_fact_solicitud_cab a
    INNER JOIN san_fact_solicitud_det b ON a.codEnvio = b.codEnvio
    LEFT JOIN san_fact_resultado_analisis c ON b.codEnvio = c.codEnvio 
        AND b.codRef = c.codRef 
        AND b.posSolicitud = c.posSolicitud 
        AND b.codAnalisis = c.analisis_codigo
    $where
";
$filteredResult = mysqli_query($conexion, $filteredQuery);
$recordsFiltered = mysqli_fetch_assoc($filteredResult)['total'];

// Datos
$sql = "
    SELECT 
        a.codEnvio,
        a.fecEnvio,
        a.horaEnvio,
        a.nomLab,
        a.nomEmpTrans,
        a.usuarioResponsable,
        a.autorizadoPor,
        b.posSolicitud,
        b.codRef,
        b.estado_cuali,
        b.fecToma,
        b.numMuestras,
        b.nomMuestra,
        b.nomAnalisis,
        c.fechaHoraRegistro,
        c.fechaLabRegistro,
        c.analisis_nombre,
        c.resultado,
        c.obs,
        c.usuarioRegistrador
    FROM san_fact_solicitud_cab a
    INNER JOIN san_fact_solicitud_det b ON a.codEnvio = b.codEnvio
    LEFT JOIN san_fact_resultado_analisis c ON b.codEnvio = c.codEnvio
        AND b.codRef = c.codRef
        AND b.posSolicitud = c.posSolicitud
        AND b.codAnalisis = c.analisis_codigo
    $where
    ORDER BY a.codEnvio DESC, b.posSolicitud
    LIMIT $start, $length
";

$result = mysqli_query($conexion, $sql);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $row['fecEnvio'] = $row['fecEnvio'] ? date('d/m/Y', strtotime($row['fecEnvio'])) : '';
    $row['fecToma'] = $row['fecToma'] ? date('d/m/Y', strtotime($row['fecToma'])) : '';
    $row['fechaLabRegistro'] = $row['fechaLabRegistro'] ? date('d/m/Y', strtotime($row['fechaLabRegistro'])) : '';
    $row['fechaHoraRegistro'] = $row['fechaHoraRegistro'] ? date('d/m/Y H:i', strtotime($row['fechaHoraRegistro'])) : '';

    $data[] = $row;
}

echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $recordsTotal,
    "recordsFiltered" => $recordsFiltered,
    "data" => $data
]);

mysqli_close($conexion);
?>