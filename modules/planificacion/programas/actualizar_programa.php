<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$crearNuevoPrograma = !empty($input['crearNuevoPrograma']);
$nuevoCodigo = $crearNuevoPrograma ? trim($input['nuevoCodigo'] ?? '') : '';
$codigo = trim($input['codigo'] ?? '');
$nombre = trim($input['nombre'] ?? '');
$codTipo = (int)($input['codTipo'] ?? 0);
$nomTipo = trim($input['nomTipo'] ?? '');
$zona = '';
$subzona = '';
$despliegue = trim($input['despliegue'] ?? '');
$descripcion = trim($input['descripcion'] ?? '');
$fechaInicio = trim($input['fechaInicio'] ?? '');
$fechaFin = isset($input['fechaFin']) && $input['fechaFin'] !== '' && $input['fechaFin'] !== null ? trim($input['fechaFin']) : null;
$detalles = isset($input['detalles']) && is_array($input['detalles']) ? $input['detalles'] : [];


if ($crearNuevoPrograma) {
    if (empty($nuevoCodigo) || empty($nombre) || $codTipo <= 0) {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Faltan nuevo código, nombre o tipo.']);
        exit;
    }
    if (empty($detalles)) {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Debe agregar al menos una fila al detalle.']);
        exit;
    }
    $codigoParaInsertar = $nuevoCodigo;
} else {
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
    $codigoParaInsertar = $codigo;
}

// Si el programa está en cronograma, permitimos editar; al establecer fecha de fin se recortan registros posteriores (más abajo)
$chkCrono = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
$tieneTablaCrono = $chkCrono && $chkCrono->num_rows > 0;

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
$chkFechaInicioCab = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'fechaInicio'");
$tieneFechasCab = $chkFechaInicioCab && $chkFechaInicioCab->fetch_assoc();
if ($tieneFechasCab && $fechaInicio === '') {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'La fecha de inicio es obligatoria.']);
    exit;
}

if ($crearNuevoPrograma) {
    $codigoProgramaViejo = trim($input['codigoProgramaViejo'] ?? '');
    $hoy = date('Y-m-d');
    $ayer = date('Y-m-d', strtotime($hoy . ' -1 day'));

    // 1. Programa viejo: fechaFin = hoy - 1 (siempre que tengamos el código)
    if (!empty($codigoProgramaViejo) && $tieneFechasCab) {
        $stUpd = $conn->prepare("UPDATE san_fact_programa_cab SET fechaFin = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stUpd) {
            $usuarioMod = $_SESSION['usuario'] ?? 'WEB';
            $stUpd->bind_param("sss", $ayer, $usuarioMod, $codigoProgramaViejo);
            $stUpd->execute();
            $stUpd->close();
        }
    }

    // 2. Programa viejo: borrar asignaciones con fechaEjecucion >= hoy
    if (!empty($codigoProgramaViejo) && $tieneTablaCrono) {
        $chkFecCrono = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fechaEjecucion'");
        if ($chkFecCrono && $chkFecCrono->num_rows > 0) {
            $stDel = $conn->prepare("DELETE FROM san_fact_cronograma WHERE codPrograma = ? AND DATE(fechaEjecucion) >= ?");
            if ($stDel) {
                $stDel->bind_param("ss", $codigoProgramaViejo, $hoy);
                $stDel->execute();
                $stDel->close();
            }
        }
    }

    // 3. Nuevo programa: fechaInicio = hoy, fechaFin = la del formulario
    $fechaInicio = $hoy;
    $usuarioRegistro = $_SESSION['usuario'] ?? 'WEB';
    $edadCab = (count($detalles) > 0 && isset($detalles[0]['edad'])) ? (int)$detalles[0]['edad'] : 0;
    if ($tieneEdadCab && $tieneDespliegueCab && $tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, edad, zona, despliegue, descripcion, fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
        if ($stmtCab) $stmtCab->bind_param("ssisissssss", $codigoParaInsertar, $nombre, $codTipo, $nomTipo, $edadCab, $zona, $despliegue, $descripcion, $fechaInicio, $fechaFin, $usuarioRegistro);
    } elseif ($tieneDespliegueCab && $tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, despliegue, descripcion, fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
        if ($stmtCab) $stmtCab->bind_param("ssisssssss", $codigoParaInsertar, $nombre, $codTipo, $nomTipo, $zona, $despliegue, $descripcion, $fechaInicio, $fechaFin, $usuarioRegistro);
    } elseif ($tieneEdadCab && $tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, edad, zona, descripcion, fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
        if ($stmtCab) $stmtCab->bind_param("ssisisssss", $codigoParaInsertar, $nombre, $codTipo, $nomTipo, $edadCab, $zona, $descripcion, $fechaInicio, $fechaFin, $usuarioRegistro);
    } elseif ($tieneFechasCab) {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, descripcion, fechaInicio, fechaFin, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
        if ($stmtCab) $stmtCab->bind_param("ssissssss", $codigoParaInsertar, $nombre, $codTipo, $nomTipo, $zona, $descripcion, $fechaInicio, $fechaFin, $usuarioRegistro);
    } else {
        $stmtCab = $conn->prepare("INSERT INTO san_fact_programa_cab (codigo, nombre, codTipo, nomTipo, zona, descripcion, fechaHoraRegistro, usuarioRegistro) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        if ($stmtCab) $stmtCab->bind_param("ssissss", $codigoParaInsertar, $nombre, $codTipo, $nomTipo, $zona, $descripcion, $usuarioRegistro);
    }
    if (!$stmtCab || !$stmtCab->execute()) {
        if ($stmtCab) $stmtCab->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Error al crear nuevo programa: ' . $conn->error]);
        exit;
    }
    $stmtCab->close();
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
        echo json_encode(['success' => false, 'message' => 'Error al preparar detalle nuevo programa.']);
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
            $stmtDet->bind_param("sssssssssssisiii", $codigoParaInsertar, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad, $descripcionVacuna, $areaGalpon, $cantidadPorGalpon, $posDetalle);
        } elseif ($tieneExtras) {
            $stmtDet->bind_param("sssssssssssisii", $codigoParaInsertar, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad, $descripcionVacuna, $areaGalpon, $cantidadPorGalpon);
        } elseif ($tienePosDetalle) {
            $stmtDet->bind_param("sssssssssssii", $codigoParaInsertar, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad, $posDetalle);
        } else {
            $stmtDet->bind_param("sssssssssssi", $codigoParaInsertar, $nombre, $codProducto, $nomProducto, $codProveedor, $nomProveedor, $ubicacion, $unidades, $dosis, $unidadDosis, $numeroFrascos, $edad);
        }
        if (!$stmtDet->execute()) {
            $stmtDet->close();
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Error al guardar detalle nuevo programa.']);
            exit;
        }
    }
    $stmtDet->close();
    $conn->close();
    echo json_encode(['success' => true, 'message' => 'Nuevo programa creado. Se debe recalcular el cronograma para el código ' . $nuevoCodigo . '.', 'nuevoCodigo' => $nuevoCodigo, 'recalcularCodigo' => $nuevoCodigo]);
    exit;
}

$usuarioModificacion = $_SESSION['usuario'] ?? 'WEB';

// UPDATE cabecera (usuarioModificacion y fechaHoraModificacion en tabla)
$edadCab = (count($detalles) > 0 && isset($detalles[0]['edad'])) ? (int)$detalles[0]['edad'] : 0;
if ($tieneEdadCab && $tieneDespliegueCab) {
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, edad = ?, zona = ?, despliegue = ?, descripcion = ?, fechaInicio = ?, fechaFin = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) $stmtCab->bind_param("sisssssssss", $nombre, $codTipo, $nomTipo, $edadCab, $zona, $despliegue, $descripcion, $fechaInicio, $fechaFin, $usuarioModificacion, $codigo);
    } else {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, edad = ?, zona = ?, despliegue = ?, descripcion = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) $stmtCab->bind_param("sisssssss", $nombre, $codTipo, $nomTipo, $edadCab, $zona, $despliegue, $descripcion, $usuarioModificacion, $codigo);
    }
} elseif ($tieneDespliegueCab) {
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, zona = ?, despliegue = ?, descripcion = ?, fechaInicio = ?, fechaFin = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) $stmtCab->bind_param("sissssssss", $nombre, $codTipo, $nomTipo, $zona, $despliegue, $descripcion, $fechaInicio, $fechaFin, $usuarioModificacion, $codigo);
    } else {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, zona = ?, despliegue = ?, descripcion = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) $stmtCab->bind_param("sissssss", $nombre, $codTipo, $nomTipo, $zona, $despliegue, $descripcion, $usuarioModificacion, $codigo);
    }
} elseif ($tieneEdadCab) {
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, edad = ?, zona = ?, descripcion = ?, fechaInicio = ?, fechaFin = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) $stmtCab->bind_param("sisissssss", $nombre, $codTipo, $nomTipo, $edadCab, $zona, $descripcion, $fechaInicio, $fechaFin, $usuarioModificacion, $codigo);
    } else {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, edad = ?, zona = ?, descripcion = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) $stmtCab->bind_param("sisissss", $nombre, $codTipo, $nomTipo, $edadCab, $zona, $descripcion, $usuarioModificacion, $codigo);
    }
} else {
    if ($tieneFechasCab) {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, zona = ?, descripcion = ?, fechaInicio = ?, fechaFin = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) $stmtCab->bind_param("sisssssss", $nombre, $codTipo, $nomTipo, $zona, $descripcion, $fechaInicio, $fechaFin, $usuarioModificacion, $codigo);
    } else {
        $stmtCab = $conn->prepare("UPDATE san_fact_programa_cab SET nombre = ?, codTipo = ?, nomTipo = ?, zona = ?, descripcion = ?, usuarioModificacion = ?, fechaHoraModificacion = NOW() WHERE codigo = ?");
        if ($stmtCab) $stmtCab->bind_param("sisssss", $nombre, $codTipo, $nomTipo, $zona, $descripcion, $usuarioModificacion, $codigo);
    }
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

// No eliminar aquí registros del cronograma: cuando hay cambio de fechas, el frontend llama a
// recalcular_fechas_programa_editado.php que elimina >= hoy e inserta respetando fechaFin.
// Evita borrar todo antes del recálculo y perder el scope (numCronograma, despliegue).

$conn->close();
echo json_encode(['success' => true, 'message' => 'Programa actualizado correctamente.', 'recalcularCodigo' => $codigo]);
