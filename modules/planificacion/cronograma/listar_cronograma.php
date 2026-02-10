<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

// Una fila por registro de cronograma (incl. nomGranja y edad para calendario/leyenda)
$tieneFechaHora = false;
$tieneNomGranja = false;
$tieneEdad = false;
$chk = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fechaHoraRegistro'");
if ($chk && $chk->num_rows > 0) $tieneFechaHora = true;
$chk2 = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
if ($chk2 && $chk2->num_rows > 0) $tieneNomGranja = true;
$chk3 = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
if ($chk3 && $chk3->num_rows > 0) $tieneEdad = true;

$sql = "SELECT c.id, c.codPrograma, c.nomPrograma, c.granja, c.campania, c.galpon, c.fechaCarga, c.fechaEjecucion";
if ($tieneFechaHora) $sql .= ", c.fechaHoraRegistro";
if ($tieneNomGranja) $sql .= ", c.nomGranja";
if ($tieneEdad) $sql .= ", c.edad";
$sql .= " FROM san_fact_cronograma c ORDER BY c.codPrograma, c.granja, c.campania, c.galpon, c.fechaEjecucion ASC";

$res = $conn->query($sql);
if ($res === false) {
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Error en consulta: ' . $conn->error]);
    $conn->close();
    exit;
}
$lista = [];
$num = 0;
while ($row = $res->fetch_assoc()) {
    $num++;
    $lista[] = [
        'num' => $num,
        'id' => (int)($row['id'] ?? 0),
        'codPrograma' => $row['codPrograma'] ?? '',
        'nomPrograma' => $row['nomPrograma'] ?? '',
        'fechaHoraRegistro' => $tieneFechaHora ? ($row['fechaHoraRegistro'] ?? '') : ($row['fechaCarga'] ?? ''),
        'granja' => $row['granja'] ?? '',
        'nomGranja' => $tieneNomGranja ? ($row['nomGranja'] ?? '') : '',
        'campania' => $row['campania'] ?? '',
        'galpon' => $row['galpon'] ?? '',
        'fechaCarga' => $row['fechaCarga'] ?? '',
        'fechaEjecucion' => $row['fechaEjecucion'] ?? '',
        'edad' => $tieneEdad ? ($row['edad'] ?? '') : ''
    ];
}
echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
