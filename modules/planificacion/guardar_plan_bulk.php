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
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input || !isset($input['filas']) || !is_array($input['filas'])) {
    echo json_encode(['success' => false, 'message' => 'JSON inválido o sin filas']);
    exit();
}

$mes = (int)($input['mes'] ?? date('n'));
$anio = (int)($input['anio'] ?? date('Y'));
$fecProgramacion = date('Y-m-d H:i:s');
$usuario = $_SESSION['usuario'] ?? 'SYSTEM';
$filas = $input['filas'];
$guardadas = 0;
$errores = [];

// Siempre crear nueva cabecera por cada guardado (puede haber varias cab por anio/mes)
$cabId = generar_uuid_v4();
$fecProg = date('Y-m-d 00:00:00', mktime(0, 0, 0, $mes, 1, $anio));
$ins = $conn->prepare("INSERT INTO san_plan_cab (id, anio, mes, fecProgramacion, usuarioRegistrador) VALUES (?, ?, ?, ?, ?)");
if ($ins) {
    $ins->bind_param('siiss', $cabId, $anio, $mes, $fecProg, $usuario);
    $ins->execute();
    $ins->close();
}

foreach ($filas as $idx => $f) {
    $granja = str_pad(trim($f['granja'] ?? ''), 3, '0', STR_PAD_LEFT);
    $campania = str_pad(trim($f['campania'] ?? ''), 3, '0', STR_PAD_LEFT);
    $galpon = str_pad(trim($f['galpon'] ?? ''), 2, '0', STR_PAD_LEFT);
    $edad = str_pad(trim($f['edad'] ?? ''), 2, '0', STR_PAD_LEFT);
    $nomGranja = trim($f['nomGranja'] ?? '');

    if (!$granja || !$campania || !$galpon || $edad === '') {
        $errores[] = "Fila " . ($idx + 1) . ": Granja, campaña, galpón y edad son obligatorios";
        continue;
    }

    $codRef = $granja . $campania . $galpon . $edad;
    $codCronograma = (int)($f['codCronograma'] ?? 0);
    $nomCronograma = trim($f['nomCronograma'] ?? '');
    $fecToma = trim($f['fecToma'] ?? '');
    $codMuestraRaw = $f['codMuestra'] ?? null;
    $codMuestra = (isset($codMuestraRaw) && $codMuestraRaw !== '' && $codMuestraRaw !== null) ? (int)$codMuestraRaw : null;
    $nomMuestra = trim($f['nomMuestra'] ?? '');
    $lugarToma = trim($f['lugarToma'] ?? '');
    $codDestinoRaw = $f['codDestino'] ?? null;
    $codDestino = (isset($codDestinoRaw) && $codDestinoRaw !== '' && $codDestinoRaw !== null) ? (int)$codDestinoRaw : null;
    $nomDestino = trim($f['nomDestino'] ?? '');
    $responsable = trim($f['responsable'] ?? '');
    $nMacho = (int)($f['nMacho'] ?? 0);
    $nHembra = (int)($f['nHembra'] ?? 0);
    $obsRaw = trim((string)($f['observacion'] ?? ''));
    $observacion = ($obsRaw === '' || $obsRaw === '0') ? null : $obsRaw;

    if ($codCronograma <= 0 || !$fecToma) continue;

    $detId = generar_uuid_v4();
    $codMuestraBind = ($codMuestra === null || $codMuestra === '') ? 0 : (int)$codMuestra;
    $codDestinoBind = ($codDestino === null || $codDestino === '') ? 0 : (int)$codDestino;
    $codCronogramaInt = (int)$codCronograma;
    $nMachoInt = (int)$nMacho;
    $nHembraInt = (int)$nHembra;
    $sql = "INSERT INTO san_plan_det (id, cabId, codCronograma, nomCronograma, fecProgramacion, granja, nomGranja, campania, galpon, edad, codRef, lugarToma, fecToma, responsable, codDestino, nomDestino, codMuestra, nomMuestra, nMacho, nHembra, estado, observacion, usuarioRegistrador, usuarioTransferencia)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PLANIFICADO', ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $obsBind = $observacion ?? '';
    $stmt->bind_param('ssissssssssssssisisiiss', $detId, $cabId, $codCronogramaInt, $nomCronograma, $fecProgramacion, $granja, $nomGranja, $campania, $galpon, $edad, $codRef, $lugarToma, $fecToma, $responsable, $codDestinoBind, $nomDestino, $codMuestraBind, $nomMuestra, $nMachoInt, $nHembraInt, $obsBind, $usuario, $usuario);
        if ($stmt->execute()) {
            $guardadas++;
            if ($codMuestra === null || $codMuestra === '') $conn->query("UPDATE san_plan_det SET codMuestra = NULL WHERE id = '$detId'");
            if ($codDestino === null || $codDestino === '') $conn->query("UPDATE san_plan_det SET codDestino = NULL WHERE id = '$detId'");
        } else {
            $errores[] = "Fila " . ($idx + 1) . ": " . $stmt->error;
        }
        $stmt->close();
    }
}

$primerCodRef = null;
foreach ($filas as $f) {
    $g = str_pad(trim($f['granja'] ?? ''), 3, '0', STR_PAD_LEFT);
    $c = str_pad(trim($f['campania'] ?? ''), 3, '0', STR_PAD_LEFT);
    $ga = str_pad(trim($f['galpon'] ?? ''), 2, '0', STR_PAD_LEFT);
    $e = str_pad(trim($f['edad'] ?? ''), 2, '0', STR_PAD_LEFT);
    if ($g && $c && $ga && $e) { $primerCodRef = $g . $c . $ga . $e; break; }
}

echo json_encode([
    'success' => $guardadas > 0,
    'guardadas' => $guardadas,
    'total' => count($filas),
    'errores' => $errores,
    'codRef' => $primerCodRef
], JSON_UNESCAPED_UNICODE);
