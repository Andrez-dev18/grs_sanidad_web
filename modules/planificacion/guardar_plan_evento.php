<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Lima');

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}
mysqli_set_charset($conn, 'utf8');

function generar_uuid_v4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'JSON inválido']);
    exit();
}

$codCronograma = (int)($input['codCronograma'] ?? 0);
$nomCronograma = trim($input['nomCronograma'] ?? $input['cronograma'] ?? '');
$fecProgramacion = trim($input['fecProgramacion'] ?? $input['fecha_programacion'] ?? '');
$granja = trim($input['granja'] ?? '');
$nomGranja = trim($input['nomGranja'] ?? $input['nombreGranja'] ?? '');
$campania = trim($input['campania'] ?? '');
$galpon = trim($input['galpon'] ?? '');
$edad = trim($input['edad'] ?? '');
$codMuestra = $input['codMuestra'] ?? null;
$nomMuestra = trim($input['nomMuestra'] ?? '');
$lugarToma = trim($input['lugarToma'] ?? $input['lugar_toma'] ?? '');
$fecToma = trim($input['fecToma'] ?? '');
$responsable = trim($input['responsable'] ?? '');
$nMacho = (int)($input['nMacho'] ?? $input['n_macho'] ?? 0);
$nHembra = (int)($input['nHembra'] ?? $input['n_hembra'] ?? 0);
$codDestino = isset($input['codDestino']) ? (int)$input['codDestino'] : null;
$nomDestino = trim($input['nomDestino'] ?? $input['destino'] ?? '');
$observacion = trim($input['observacion'] ?? '');

if ($codCronograma <= 0 || $fecProgramacion === '' || $granja === '' || $campania === '' || $galpon === '' || $edad === '' || $fecToma === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Normalización/padding
$granja = str_pad($granja, 3, '0', STR_PAD_LEFT);
$campania = str_pad($campania, 3, '0', STR_PAD_LEFT);
$galpon = str_pad($galpon, 2, '0', STR_PAD_LEFT);
$edad = str_pad($edad, 2, '0', STR_PAD_LEFT);
$codRef = $granja . $campania . $galpon . $edad;

// codMuestra opcional (necropsias puede no tener)
if ($codMuestra === '' || $codMuestra === null) {
    $codMuestra = null;
} else if (!preg_match('/^\d+$/', (string)$codMuestra)) {
    echo json_encode(['success' => false, 'message' => 'codMuestra inválido'], JSON_UNESCAPED_UNICODE);
    exit();
} else {
    $codMuestra = (int)$codMuestra;
}

$id = generar_uuid_v4();
$usuario = $_SESSION['usuario'] ?? 'SYSTEM';
$usuarioTransferencia = $usuario;

// Estandarización cabecera/detalle:
// 1) Reusar cabecera si ya existe para el mismo grupo lógico
$lugarToma = $lugarToma ?: '';
$responsable = $responsable ?: '';

$sqlFindCab = "SELECT id FROM san_plan_cab
               WHERE codCronograma = ? AND codRef = ? AND fecToma = ?
                 AND lugarToma = ? AND responsable = ?
                 AND " . ($codDestino === null ? "codDestino IS NULL" : "codDestino = ?") . "
               LIMIT 1";
$stmtFind = $conn->prepare($sqlFindCab);
if (!$stmtFind) {
    echo json_encode(['success' => false, 'message' => 'Error preparando búsqueda: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit();
}
if ($codDestino === null) {
    $stmtFind->bind_param('issss', $codCronograma, $codRef, $fecToma, $lugarToma, $responsable);
} else {
    $stmtFind->bind_param('issssi', $codCronograma, $codRef, $fecToma, $lugarToma, $responsable, $codDestino);
}
$stmtFind->execute();
$resFind = $stmtFind->get_result();
$cabId = null;
if ($rowCab = $resFind->fetch_assoc()) {
    $cabId = $rowCab['id'];
}
$stmtFind->close();

if ($cabId === null) {
    $cabId = generar_uuid_v4();
    $sqlCab = "INSERT INTO san_plan_cab (
                    id, codCronograma, nomCronograma, fecProgramacion,
                    granja, nomGranja, campania, galpon, edad, codRef,
                    lugarToma, fecToma, responsable, codDestino, nomDestino,
                    estado,
                    usuarioRegistrador, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia,
                    observacion
               ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    'PLANIFICADO',
                    ?, NOW(), ?, NOW(),
                    ?
               )";
    $stmtCab = $conn->prepare($sqlCab);
    if (!$stmtCab) {
        echo json_encode(['success' => false, 'message' => 'Error preparando cabecera: ' . $conn->error], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $stmtCab->bind_param(
        'sisssssssssssissss',
        $cabId,
        $codCronograma,
        $nomCronograma,
        $fecProgramacion,
        $granja,
        $nomGranja,
        $campania,
        $galpon,
        $edad,
        $codRef,
        $lugarToma,
        $fecToma,
        $responsable,
        $codDestino,
        $nomDestino,
        $usuario,
        $usuarioTransferencia,
        $observacion
    );
    if (!$stmtCab->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar cabecera: ' . $stmtCab->error], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $stmtCab->close();
}

// 2) Insertar detalle
$detId = generar_uuid_v4();
$codMuestraIsNull = ($codMuestra === null);
$codMuestraBind = $codMuestraIsNull ? 0 : $codMuestra;
$nomMuestra = $nomMuestra ?: '';

$sqlDet = "INSERT INTO san_plan_det (
                id, cabId,
                codMuestra, nomMuestra,
                nMacho, nHembra,
                estado,
                observacion
          ) VALUES (
                ?, ?,
                ?, ?,
                ?, ?,
                'PLANIFICADO',
                ?
          )";
$stmtDet = $conn->prepare($sqlDet);
if (!$stmtDet) {
    echo json_encode(['success' => false, 'message' => 'Error preparando detalle: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit();
}
$stmtDet->bind_param(
    'ssisiis',
    $detId,
    $cabId,
    $codMuestraBind,
    $nomMuestra,
    $nMacho,
    $nHembra,
    $observacion
);
if (!$stmtDet->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar detalle: ' . $stmtDet->error], JSON_UNESCAPED_UNICODE);
    exit();
}
$stmtDet->close();

// Si codMuestra era NULL, dejarlo como NULL (best-effort)
if ($codMuestraIsNull) {
    $upd = $conn->prepare("UPDATE san_plan_det SET codMuestra = NULL WHERE id = ?");
    if ($upd) {
        $upd->bind_param('s', $detId);
        $upd->execute();
        $upd->close();
    }
}

echo json_encode(['success' => true, 'cabId' => $cabId, 'detId' => $detId, 'codRef' => $codRef], JSON_UNESCAPED_UNICODE);

