<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    include_once __DIR__ . '/../../../../conexion_grs/conexion.php';
    $conn = conectar_joya_mysqli();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $codPrograma = trim($input['codPrograma'] ?? '');
    $nomPrograma = trim($input['nomPrograma'] ?? '');
    $granja = substr(trim($input['granja'] ?? ''), 0, 3);
    $galpon = trim($input['galpon'] ?? '');
    $campaniaRaw = trim($input['campania'] ?? '');
    $campania = strlen($campaniaRaw) >= 3 ? substr($campaniaRaw, -3) : str_pad($campaniaRaw, 3, '0', STR_PAD_LEFT);
    $edad = isset($input['edad']) ? (int)$input['edad'] : 0;
    $nomGranja = trim($input['nomGranja'] ?? '');
    $fecha = trim($input['fecha'] ?? '');
    $observaciones = trim($input['observaciones'] ?? '');
    if (strlen($observaciones) > 500) $observaciones = substr($observaciones, 0, 500);

    if ($codPrograma === '') {
        echo json_encode(['success' => false, 'message' => 'Falta código de programa.']);
        exit;
    }
    if ($granja === '') {
        echo json_encode(['success' => false, 'message' => 'Falta granja.']);
        exit;
    }
    if ($galpon === '') {
        echo json_encode(['success' => false, 'message' => 'Falta galpón.']);
        exit;
    }
    if ($campania === '' || $campania === '000') {
        echo json_encode(['success' => false, 'message' => 'Falta campaña.']);
        exit;
    }
    if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        echo json_encode(['success' => false, 'message' => 'Indique una fecha válida.']);
        exit;
    }

    if ($edad > 999) $edad = 999;

    $usuario = $_SESSION['usuario'] ?? 'WEB';
    $numCronograma = 0;

    $tieneNomGranja = false;
    $tieneEdad = false;
    $tieneZona = false;
    $tieneSubzona = false;
    $tieneNumCronograma = false;

    $chkNom = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
    if ($chkNom && $chkNom->num_rows > 0) $tieneNomGranja = true;
    $chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
    if ($chkEdad && $chkEdad->num_rows > 0) $tieneEdad = true;
    $chkZona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'zona'");
    if ($chkZona && $chkZona->num_rows > 0) $tieneZona = true;
    $chkSubzona = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'subzona'");
    if ($chkSubzona && $chkSubzona->num_rows > 0) $tieneSubzona = true;
    $chkNum = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
    if ($chkNum && $chkNum->num_rows > 0) $tieneNumCronograma = true;
    $chkObs = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'observaciones'");
    $tieneObservaciones = $chkObs && $chkObs->num_rows > 0;
    $chkTolerancia = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'tolerancia'");
    $tieneTolerancia = $chkTolerancia && $chkTolerancia->num_rows > 0;
    $chkTolDet = @$conn->query("SHOW COLUMNS FROM san_fact_programa_det LIKE 'tolerancia'");
    $tieneTolDet = $chkTolDet && $chkTolDet->num_rows > 0;

    $zona = '';
    $subzona = '';
    if ($tieneZona || $tieneSubzona) {
        $chkPi = @$conn->query("SHOW TABLES LIKE 'pi_dim_detalles'");
        if ($chkPi && $chkPi->num_rows > 0) {
            $stZs = $conn->prepare("SELECT
                MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'ZONA' THEN TRIM(det.dato) END) AS zona,
                MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'SUBZONA' THEN TRIM(det.dato) END) AS subzona
                FROM pi_dim_detalles det
                INNER JOIN pi_dim_caracteristicas car ON car.id = det.id_caracteristica
                WHERE TRIM(det.id_granja) = ? AND UPPER(TRIM(car.nombre)) IN ('ZONA', 'SUBZONA')
                GROUP BY TRIM(det.id_granja)");
            if ($stZs) {
                $stZs->bind_param("s", $granja);
                $stZs->execute();
                $resZs = $stZs->get_result();
                if ($rowZs = $resZs->fetch_assoc()) {
                    $zona = trim($rowZs['zona'] ?? '');
                    $subzona = trim($rowZs['subzona'] ?? '');
                }
                $stZs->close();
            }
        }
    }

    $cols = "granja, campania, galpon, codPrograma, nomPrograma, fechaCarga, fechaEjecucion, usuarioRegistro";
    $placeholders = "?, ?, ?, ?, ?, ?, ?, ?";
    $types = "ssssssss";
    $bindVals = [$granja, $campania, $galpon, $codPrograma, $nomPrograma ?: $codPrograma, $fecha, $fecha, $usuario];

    if ($tieneZona) { $cols .= ", zona"; $placeholders .= ", ?"; $types .= "s"; $bindVals[] = $zona; }
    if ($tieneSubzona) { $cols .= ", subzona"; $placeholders .= ", ?"; $types .= "s"; $bindVals[] = $subzona; }
    if ($tieneNumCronograma) { $cols .= ", numCronograma"; $placeholders .= ", ?"; $types .= "i"; $bindVals[] = $numCronograma; }
    if ($tieneNomGranja) { $cols .= ", nomGranja"; $placeholders .= ", ?"; $types .= "s"; $bindVals[] = $nomGranja; }
    if ($tieneEdad) { $cols .= ", edad"; $placeholders .= ", ?"; $types .= "i"; $bindVals[] = $edad; }
    if ($tieneObservaciones) { $cols .= ", observaciones"; $placeholders .= ", ?"; $types .= "s"; $bindVals[] = $observaciones; }
    $toleranciaVal = 1;
    if ($tieneTolerancia && $tieneTolDet) {
        $stTol = $conn->prepare("SELECT COALESCE(NULLIF(tolerancia, 0), 1) AS tol FROM san_fact_programa_det WHERE codPrograma = ? AND edad = ? LIMIT 1");
        if ($stTol) {
            $stTol->bind_param("si", $codPrograma, $edad);
            $stTol->execute();
            $r = $stTol->get_result();
            if ($r && $row = $r->fetch_assoc()) {
                $toleranciaVal = max(1, (int)($row['tol'] ?? 1));
            } else {
                $stTol2 = $conn->prepare("SELECT COALESCE(MAX(NULLIF(tolerancia, 0)), 1) AS tol FROM san_fact_programa_det WHERE codPrograma = ?");
                if ($stTol2) {
                    $stTol2->bind_param("s", $codPrograma);
                    $stTol2->execute();
                    $r2 = $stTol2->get_result();
                    if ($r2 && $row2 = $r2->fetch_assoc()) $toleranciaVal = max(1, (int)($row2['tol'] ?? 1));
                    $stTol2->close();
                }
            }
            $stTol->close();
        }
    }
    if ($tieneTolerancia) { $cols .= ", tolerancia"; $placeholders .= ", ?"; $types .= "i"; $bindVals[] = $toleranciaVal; }

    $stmt = $conn->prepare("INSERT INTO san_fact_cronograma ($cols) VALUES ($placeholders)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error prepare: ' . ($conn->error ?? '')]);
        exit;
    }

    $params = array_merge([$types], $bindVals);
    $refs = [];
    foreach ($params as $k => $v) {
        $refs[$k] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . ($stmt->error ?? '')]);
        exit;
    }

    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'message' => 'Asignación eventual registrada correctamente.']);
} catch (Throwable $e) {
    if (isset($conn) && $conn) @$conn->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
