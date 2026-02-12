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
$zona = '';
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

// Verificar que no esté en uso en cronograma
$enUso = false;
$chk = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
if ($chk && $chk->num_rows > 0) {
    $st = $conn->prepare("SELECT 1 FROM san_fact_cronograma WHERE codPrograma = ? LIMIT 1");
    if ($st) {
        $st->bind_param("s", $codigo);
        $st->execute();
        $r = $st->get_result();
        $enUso = $r && $r->num_rows > 0;
        $st->close();
    }
}
if ($enUso) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'No puede editar: el programa ya ha sido asignado en cronogramas.']);
    exit;
}

if (empty($nomTipo)) {
    $st = $conn->prepare("SELECT nombre FROM san_dim_tipo_programa WHERE codigo = ?");
    $st->bind_param("i", $codTipo);
    $st->execute();
    $r = $st->get_result();
    if ($r && $row = $r->fetch_assoc()) $nomTipo = $row['nombre'];
    $st->close();
}

$chkEdadCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'edad'");
$tieneEdadCab = $chkEdadCab && $chkEdadCab->fetch_assoc();
$chkDespliegueCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'despliegue'");
$tieneDespliegueCab = $chkDespliegueCab && $chkDespliegueCab->fetch_assoc();

$usuarioModificacion = $_SESSION['usuario'] ?? 'WEB';

// UPDATE cabecera (usuarioModificacion y fechaHoraModificacion en tabla)
$edadCab = (count($detalles) > 0 && isset($detalles[0]['edad'])) ? (int)$detalles[0]['edad'] : 0;
if ($tieneEdadCab && $tieneDespliegueCab) {
    $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, edad = ?, zona = ?, despliegue = ?, descripcion = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
    if ($stmtCab) $stmtCab->bind_param("sisssssss", $nombre, $codTipo, $nomTipo, $edadCab, $zona, $despliegue, $descripcion, $usuarioModificacion, $codigo);
} elseif ($tieneDespliegueCab) {
    $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, zona = ?, despliegue = ?, descripcion = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
    if ($stmtCab) $stmtCab->bind_param("sissssss", $nombre, $codTipo, $nomTipo, $zona, $despliegue, $descripcion, $usuarioModificacion, $codigo);
} elseif ($tieneEdadCab) {
    $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, edad = ?, zona = ?, descripcion = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
    if ($stmtCab) $stmtCab->bind_param("sisissss", $nombre, $codTipo, $nomTipo, $edadCab, $zona, $descripcion, $usuarioModificacion, $codigo);
} else {
    $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, zona = ?, descripcion = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
    if ($stmtCab) $stmtCab->bind_param("sisssss", $nombre, $codTipo, $nomTipo, $zona, $descripcion, $usuarioModificacion, $codigo);
}
if (!$stmtCab || !$stmtCab->execute()) {
    if ($stmtCab) $stmtCab->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al actualizar cabecera: ' . $conn->error]);
    exit;
}
$stmtCab->close();

// Borrar detalle anterior
$stmtDel = $conn->prepare("DELETE FROM san_fact_programa_det WHERE codPrograma = ?");
if ($stmtDel) {
    $stmtDel->bind_param("s", $codigo);
    $stmtDel->execute();
    $stmtDel->close();
}

// Insertar nuevo detalle (misma lógica que guardar_programa)
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
$conn->close();
echo json_encode(['success' => true, 'message' => 'Programa actualizado correctamente.']);
