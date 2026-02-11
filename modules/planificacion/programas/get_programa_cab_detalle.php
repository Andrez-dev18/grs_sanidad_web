<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$codigo = trim((string)($_GET['codigo'] ?? $_POST['codigo'] ?? ''));
if ($codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Falta código de programa']);
    $conn->close();
    exit;
}

$chkDespliegue = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'despliegue'");
$tieneDespliegue = $chkDespliegue && $chkDespliegue->fetch_assoc();

$sqlCab = "SELECT c.codigo, c.nombre, c.codTipo, c.nomTipo, c.zona, c.descripcion, c.fechaHoraRegistro";
if ($tieneDespliegue) $sqlCab .= ", c.despliegue";
$sqlCab .= " FROM san_fact_programa_cab c WHERE c.codigo = ? LIMIT 1";
$stmtCab = $conn->prepare($sqlCab);
if (!$stmtCab) {
    echo json_encode(['success' => false, 'message' => 'Error preparar consulta']);
    $conn->close();
    exit;
}
$stmtCab->bind_param("s", $codigo);
$stmtCab->execute();
$resCab = $stmtCab->get_result();
$cab = $resCab->fetch_assoc();
$stmtCab->close();

if (!$cab) {
    echo json_encode(['success' => false, 'message' => 'Programa no encontrado']);
    $conn->close();
    exit;
}

$cab['despliegue'] = $tieneDespliegue ? ($cab['despliegue'] ?? '') : '';

$chkExtras = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'descripcionVacuna'");
$tieneExtras = $chkExtras && $chkExtras->fetch_assoc();
$chkPosDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'posDetalle'");
$tienePosDetalle = $chkPosDet && $chkPosDet->fetch_assoc();
$camposDet = $tieneExtras
    ? "ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad, descripcionVacuna, areaGalpon, cantidadPorGalpon"
    : "ubicacion, codProducto, nomProducto, codProveedor, nomProveedor, unidades, dosis, unidadDosis, numeroFrascos, edad";
if ($tienePosDetalle) $camposDet .= ", posDetalle";
$sqlDet = "SELECT " . $camposDet . " FROM san_fact_programa_det WHERE codPrograma = ? ORDER BY " . ($tienePosDetalle ? "posDetalle, id" : "id");
$stmtDet = $conn->prepare($sqlDet);
$detalles = [];
if ($stmtDet) {
    $stmtDet->bind_param("s", $codigo);
    $stmtDet->execute();
    $resDet = $stmtDet->get_result();
    while ($d = $resDet->fetch_assoc()) {
        $detalles[] = $d;
    }
    $stmtDet->close();
}

// Sigla del tipo (para columnas como en listado/reporte)
$sigla = 'PL';
$codTipo = (int)($cab['codTipo'] ?? 0);
if ($codTipo > 0) {
    $stSigla = $conn->prepare("SELECT sigla FROM san_dim_tipo_programa WHERE codigo = ? LIMIT 1");
    $stSigla->bind_param("i", $codTipo);
    $stSigla->execute();
    $rSigla = $stSigla->get_result();
    if ($rSigla && $row = $rSigla->fetch_assoc() && !empty(trim($row['sigla'] ?? ''))) {
        $sigla = strtoupper(trim($row['sigla']));
        if ($sigla === 'NEC') $sigla = 'NC';
    }
    $stSigla->close();
}

$conn->close();
echo json_encode(['success' => true, 'cab' => $cab, 'detalles' => $detalles, 'sigla' => $sigla]);
