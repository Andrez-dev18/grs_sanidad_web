<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'totalProgramas' => 0, 'totalAsignaciones' => 0]);
    exit;
}

include_once '../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'totalProgramas' => 0, 'totalAsignaciones' => 0]);
    exit;
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($year < 2000 || $year > 2100) $year = (int)date('Y');

$totalProgramas = 0;
$chkCab = @$conn->query("SHOW TABLES LIKE 'san_fact_programa_cab'");
if ($chkCab && $chkCab->num_rows > 0) {
    $r = @$conn->query("SELECT COUNT(*) AS n FROM san_fact_programa_cab");
    if ($r && $row = $r->fetch_assoc()) $totalProgramas = (int)$row['n'];
}

$totalAsignaciones = 0;
$chkCrono = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
if ($chkCrono && $chkCrono->num_rows > 0) {
    $chkNum = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
    $chkFec = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fechaEjecucion'");
    if ($chkNum && $chkNum->num_rows > 0 && $chkFec && $chkFec->num_rows > 0) {
        $st = $conn->prepare("SELECT COUNT(DISTINCT numCronograma) AS n FROM san_fact_cronograma WHERE numCronograma > 0 AND YEAR(fechaEjecucion) = ?");
        if ($st) {
            $st->bind_param('i', $year);
            $st->execute();
            $res = $st->get_result();
            if ($res && $row = $res->fetch_assoc()) $totalAsignaciones = (int)$row['n'];
            $st->close();
        }
    }
}

$conn->close();

echo json_encode([
    'success' => true,
    'year' => $year,
    'totalProgramas' => $totalProgramas,
    'totalAsignaciones' => $totalAsignaciones
]);
