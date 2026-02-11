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

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$codigo = trim($input['codigo'] ?? '');
$nombre = trim($input['nombre'] ?? '');
$codTipo = (int)($input['codTipo'] ?? 0);
$nomTipo = trim($input['nomTipo'] ?? '');
$zona = ''; // Ya no se registra zona
$despliegue = trim($input['despliegue'] ?? '');
$descripcion = trim($input['descripcion'] ?? '');
$detalles = isset($input['detalles']) && is_array($input['detalles']) ? $input['detalles'] : [];

if (empty($codigo) || empty($nombre) || $codTipo <= 0) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Faltan código, nombre o tipo.']);
    exit;
}

if (empty($detalles)) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Debe agregar al menos una fila al detalle.']);
    exit;
}

// Nombre del tipo si no vino
if (empty($nomTipo)) {
    $st = $conn->prepare("SELECT nombre FROM san_dim_tipo_programa WHERE codigo = ?");
    $st->bind_param("i", $codTipo);
    $st->execute();
    $r = $st->get_result();
    if ($r && $row = $r->fetch_assoc()) $nomTipo = $row['nombre'];
    $st->close();
}

$usuarioRegistro = $_SESSION['usuario'] ?? 'WEB';

// Cabecera: tipo, código, nombre, zona, despliegue (si existe), descripción. Edad en detalle si existe columna.
$chkEdadCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'edad'");
$tieneEdadCab = $chkEdadCab && $chkEdadCab->fetch_assoc();
$chkDespliegueCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'despliegue'");
$tieneDespliegueCab = $chkDespliegueCab && $chkDespliegueCab->fetch_assoc();
if ($tieneEdadCab && $tieneDespliegueCab) {
    $edadCab = (count($detalles) > 0 && isset($detalles[0]['edad'])) ? (int)$detalles[0]['edad'] : 0;
    $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, edad, zona, despliegue, descripcion, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
    $stmtCab->bind_param("ssisissss", $codigo, $nombre, $codTipo, $nomTipo, $edadCab, $zona, $despliegue, $descripcion, $usuarioRegistro);
} elseif ($tieneDespliegueCab) {
    $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, despliegue, descripcion, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
    $stmtCab->bind_param("ssisssss", $codigo, $nombre, $codTipo, $nomTipo, $zona, $despliegue, $descripcion, $usuarioRegistro);
} elseif ($tieneEdadCab) {
    $edadCab = (count($detalles) > 0 && isset($detalles[0]['edad'])) ? (int)$detalles[0]['edad'] : 0;
    $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, edad, zona, descripcion, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
    $stmtCab->bind_param("ssisisss", $codigo, $nombre, $codTipo, $nomTipo, $edadCab, $zona, $descripcion, $usuarioRegistro);
} else {
    $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, descripcion, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
    if (!$stmtCab) { $conn->close(); echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]); exit; }
    $stmtCab->bind_param("ssissss", $codigo, $nombre, $codTipo, $nomTipo, $zona, $descripcion, $usuarioRegistro);
}
if (!$stmtCab->execute()) {
    $stmtCab->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al guardar cabecera: ' . $conn->error]);
    exit;
}
$stmtCab->close();

// Detalle: una fila por cada elemento en detalles (incl. descripcionVacuna, areaGalpon, cantidadPorGalpon, posDetalle)
$chkCols = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'descripcionVacuna'");
$tieneExtras = $chkCols && $chkCols->fetch_assoc();
$chkPosDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'posDetalle'");
$tienePosDetalle = $chkPosDet && $chkPosDet->fetch_assoc();
if ($tieneExtras && $tienePosDetalle) {
    $stmtDet = $conn->prepare("INSERT INTO san_fact_programa_det (codPrograma, nomPrograma, codProducto, nomProducto, codProveedor, nomProveedor, ubicacion, unidades, dosis, unidadDosis, numeroFrascos, edad, descripcionVacuna, areaGalpon, cantidadPorGalpon, posDetalle) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
} elseif ($tieneExtras) {
    $stmtDet = $conn->prepare("INSERT INTO san_fact_programa_det (codPrograma, nomPrograma, codProducto, nomProducto, codProveedor, nomProveedor, ubicacion, unidades, dosis, unidadDosis, numeroFrascos, edad, descripcionVacuna, areaGalpon, cantidadPorGalpon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
} elseif ($tienePosDetalle) {
    $stmtDet = $conn->prepare("INSERT INTO san_fact_programa_det (codPrograma, nomPrograma, codProducto, nomProducto, codProveedor, nomProveedor, ubicacion, unidades, dosis, unidadDosis, numeroFrascos, edad, posDetalle) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
} else {
    $stmtDet = $conn->prepare("INSERT INTO san_fact_programa_det (codPrograma, nomPrograma, codProducto, nomProducto, codProveedor, nomProveedor, ubicacion, unidades, dosis, unidadDosis, numeroFrascos, edad) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
}
if (!$stmtDet) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al preparar detalle: ' . $conn->error]);
    exit;
}

foreach ($detalles as $d) {
    $ubicacion = trim($d['ubicacion'] ?? '');
    $codProducto = trim($d['codProducto'] ?? '');
    $nomProducto = trim($d['nomProducto'] ?? '');
    $codProveedor = trim($d['codProveedor'] ?? '');
    $nomProveedor = trim($d['nomProveedor'] ?? '');
    $unidades = trim($d['unidades'] ?? '');
    $dosis = trim($d['dosis'] ?? '');
    $unidadDosis = trim($d['unidadDosis'] ?? '');
    $numeroFrascos = trim($d['numeroFrascos'] ?? '');
    $edad = isset($d['edad']) ? (int)$d['edad'] : 0;
    if ($edad < 0) $edad = 0;
    if ($edad > 45) $edad = 45;
    $descripcionVacuna = trim($d['descripcionVacuna'] ?? '');
    // descripcionVacuna solo si el producto es vacuna (san_rel_vacuna_enfermedad: codVacuna = mitm.codigo o codProducto)
    if ($descripcionVacuna !== '' && $codProducto !== '') {
        $chkColVac = @$conn->query("SHOW COLUMNS FROM san_rel_vacuna_enfermedad LIKE 'codVacuna'");
        $colVacuna = ($chkColVac && $chkColVac->fetch_assoc()) ? 'codVacuna' : 'codProducto';
        $chkVac = $conn->prepare("SELECT 1 FROM san_rel_vacuna_enfermedad WHERE " . $colVacuna . " = ? LIMIT 1");
        if ($chkVac) {
            $chkVac->bind_param("s", $codProducto);
            $chkVac->execute();
            $rV = $chkVac->get_result();
            if (!$rV || !$rV->fetch_assoc()) $descripcionVacuna = '';
            $chkVac->close();
        }
    }
    $areaGalpon = isset($d['areaGalpon']) && $d['areaGalpon'] !== '' && $d['areaGalpon'] !== null ? (int)$d['areaGalpon'] : null;
    $cantidadPorGalpon = isset($d['cantidadPorGalpon']) && $d['cantidadPorGalpon'] !== '' && $d['cantidadPorGalpon'] !== null ? (int)$d['cantidadPorGalpon'] : null;
    $posDetalle = isset($d['posDetalle']) ? (int)$d['posDetalle'] : 1;
    if ($posDetalle < 1) $posDetalle = 1;

    if ($tieneExtras && $tienePosDetalle) {
        $stmtDet->bind_param("sssssssssssisiii", $codigo, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad, $descripcionVacuna, $areaGalpon, $cantidadPorGalpon, $posDetalle);
    } elseif ($tieneExtras) {
        $stmtDet->bind_param("sssssssssssisii", $codigo, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad, $descripcionVacuna, $areaGalpon, $cantidadPorGalpon);
    } elseif ($tienePosDetalle) {
        $stmtDet->bind_param("sssssssssssii", $codigo, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad, $posDetalle);
    } else {
        $stmtDet->bind_param("sssssssssssi", $codigo, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad);
    }
    if (!$stmtDet->execute()) {
        $stmtDet->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Error al guardar detalle: ' . $conn->error]);
        exit;
    }
}

$stmtDet->close();
echo json_encode(['success' => true, 'message' => 'Programa registrado correctamente.']);
$conn->close();
