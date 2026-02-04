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
if (!$input || !isset($input['cabId']) || !isset($input['filas'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$cabId = trim($input['cabId']);
$filas = $input['filas'];
$usuario = $_SESSION['usuario'] ?? 'SYSTEM';

if ($cabId === '' || !is_array($filas)) {
    echo json_encode(['success' => false, 'message' => 'cabId y filas requeridos']);
    exit();
}

$actualizadas = 0;
$insertadas = 0;
$errores = [];
$fecProgramacion = date('Y-m-d H:i:s');

foreach ($filas as $idx => $f) {
    $id = trim($f['id'] ?? '');
    $esNueva = ($id === '' || $id === 'new');

    $granja = str_pad(trim($f['granja'] ?? ''), 3, '0', STR_PAD_LEFT);
    $campania = str_pad(trim($f['campania'] ?? ''), 3, '0', STR_PAD_LEFT);
    $galpon = str_pad(trim($f['galpon'] ?? ''), 2, '0', STR_PAD_LEFT);
    $edad = str_pad(trim($f['edad'] ?? ''), 2, '0', STR_PAD_LEFT);
    $codRef = $granja . $campania . $galpon . $edad;
    $codCronograma = (int)($f['codCronograma'] ?? 0);
    $nomCronograma = trim($f['nomCronograma'] ?? '');
    $fecToma = trim($f['fecToma'] ?? '');
    $codMuestraRaw = $f['codMuestra'] ?? null;
    $codMuestra = (isset($codMuestraRaw) && $codMuestraRaw !== '' && $codMuestraRaw !== null) ? (int)$codMuestraRaw : null;
    $nomMuestra = trim((string)($f['nomMuestra'] ?? ''));
    $lugarToma = trim($f['lugarToma'] ?? '');
    $codDestinoRaw = $f['codDestino'] ?? null;
    $codDestino = (isset($codDestinoRaw) && $codDestinoRaw !== '' && $codDestinoRaw !== null) ? (int)$codDestinoRaw : null;
    $nomDestino = trim((string)($f['nomDestino'] ?? ''));
    $responsable = trim($f['responsable'] ?? '');
    $nMacho = (int)($f['nMacho'] ?? 0);
    $nHembra = (int)($f['nHembra'] ?? 0);
    $obsRaw = trim((string)($f['observacion'] ?? ''));
    $observacion = ($obsRaw === '' || $obsRaw === '0') ? null : $obsRaw;
    $nomGranja = trim($f['nomGranja'] ?? '');

    if (!$granja || !$campania || !$galpon || $edad === '' || $codCronograma <= 0 || !$fecToma) {
        if ($esNueva) continue;
    }

    $codMuestraBind = ($codMuestra === null || $codMuestra === '') ? 0 : (int)$codMuestra;
    $codDestinoBind = ($codDestino === null || $codDestino === '') ? 0 : (int)$codDestino;

    if ($esNueva) {
        $detId = generar_uuid_v4();
        $sql = "INSERT INTO san_plan_det (id, cabId, codCronograma, nomCronograma, fecProgramacion, granja, nomGranja, campania, galpon, edad, codRef, lugarToma, fecToma, responsable, codDestino, nomDestino, codMuestra, nomMuestra, nMacho, nHembra, estado, observacion, usuarioRegistrador, usuarioTransferencia)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PLANIFICADO', ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $obsBind = $observacion ?? '';
        $stmt->bind_param('ssissssssssssssisisiiss', $detId, $cabId, $codCronograma, $nomCronograma, $fecProgramacion, $granja, $nomGranja, $campania, $galpon, $edad, $codRef, $lugarToma, $fecToma, $responsable, $codDestinoBind, $nomDestino, $codMuestraBind, $nomMuestra, $nMacho, $nHembra, $obsBind, $usuario, $usuario);
            if ($stmt->execute()) {
                $insertadas++;
                if ($codMuestra === null || $codMuestra === '') $conn->query("UPDATE san_plan_det SET codMuestra = NULL WHERE id = '$detId'");
                if ($codDestino === null || $codDestino === '') $conn->query("UPDATE san_plan_det SET codDestino = NULL WHERE id = '$detId'");
            } else {
                $errores[] = "Fila " . ($idx + 1) . ": " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $sql = "UPDATE san_plan_det SET granja=?, nomGranja=?, campania=?, galpon=?, edad=?, codRef=?, codCronograma=?, nomCronograma=?, fecToma=?, codMuestra=?, nomMuestra=?, lugarToma=?, responsable=?, codDestino=?, nomDestino=?, nMacho=?, nHembra=?, observacion=?, usuarioTransferencia=?, fechaHoraTransferencia=NOW() WHERE id=? AND cabId=?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $obsBind = $observacion ?? '';
        $stmt->bind_param('sssssssissssissiisss', $granja, $nomGranja, $campania, $galpon, $edad, $codRef, $codCronograma, $nomCronograma, $fecToma, $codMuestraBind, $nomMuestra, $lugarToma, $responsable, $codDestinoBind, $nomDestino, $nMacho, $nHembra, $obsBind, $usuario, $id, $cabId);
            if ($stmt->execute() && $stmt->affected_rows >= 0) $actualizadas++;
            if ($codMuestra === null) $conn->query("UPDATE san_plan_det SET codMuestra = NULL WHERE id = '$id'");
            if ($codDestino === null) $conn->query("UPDATE san_plan_det SET codDestino = NULL WHERE id = '$id'");
            $stmt->close();
        }
    }
}

echo json_encode([
    'success' => ($actualizadas > 0 || $insertadas > 0),
    'actualizadas' => $actualizadas,
    'insertadas' => $insertadas,
    'errores' => $errores
], JSON_UNESCAPED_UNICODE);
