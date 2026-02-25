<?php
$modoLibreria = defined('WHATSAPP_CONFIG_LIB_ONLY') ? (bool) constant('WHATSAPP_CONFIG_LIB_ONLY') : false;

if (!$modoLibreria) {
    session_start();
    if (empty($_SESSION['active'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    include_once '../../../../conexion_grs/conexion.php';
    $conexion = conectar_joya_mysqli();
    if (!$conexion) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error de conexión']);
        exit;
    }
}

function enviar_whatsapp_configurado($numero_destino, $mensaje)
{
    $configPath = __DIR__ . '/../../../config/whatsapp.php';
    if (is_file($configPath)) {
        require_once $configPath;
    }

    $appkey = defined('WHATSAPP_APPKEY') ? trim((string) WHATSAPP_APPKEY) : '';
    $authkey = defined('WHATSAPP_AUTHKEY') ? trim((string) WHATSAPP_AUTHKEY) : '';
    $baseUrl = defined('WHATSAPP_BASE_URL') ? trim((string) WHATSAPP_BASE_URL) : '';
    if ($appkey === '' || $authkey === '' || $baseUrl === '') {
        return false;
    }

    $numero_destino = preg_replace('/\D/', '', (string)$numero_destino);
    $mensaje = trim((string)$mensaje);
    if ($numero_destino === '' || $mensaje === '') {
        return false;
    }

    $payload = [
        'appkey' => $appkey,
        'authkey' => $authkey,
        'to' => $numero_destino,
        'message' => $mensaje
    ];

    $ch = curl_init($baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

if ($modoLibreria) {
    return;
}

function limpiar_telefono($telefono)
{
    $telefono = trim((string)$telefono);
    return preg_replace('/\D/', '', $telefono);
}

function obtener_telefono_usuario($conexion, $codigo)
{
    $telefono = '';
    $stmt = @mysqli_prepare($conexion, "SELECT COALESCE(telefo, '') AS telefono FROM usuario WHERE codigo = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $codigo);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            if ($row && !empty(trim($row['telefono'] ?? ''))) {
                $telefono = trim($row['telefono']);
            }
        }
        mysqli_stmt_close($stmt);
    }
    return $telefono;
}

function guardar_telefono_usuario($conexion, $codigo, $telefono)
{
    $stmt = mysqli_prepare($conexion, "UPDATE usuario SET telefo = ? WHERE codigo = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $telefono, $codigo);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($ok) {
            return ['ok' => true, 'error' => ''];
        }
    }
    return ['ok' => false, 'error' => mysqli_error($conexion)];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (!isset($_GET['action']) || $_GET['action'] === 'get')) {
    $codigo = $_SESSION['usuario'];
    $telefono = obtener_telefono_usuario($conexion, $codigo);

    $diasAnticipo = 2;
    $tiposPrograma = [];
    $chkNoti = @$conexion->query("SHOW TABLES LIKE 'san_notificaciones'");
    if ($chkNoti && $chkNoti->num_rows > 0) {
        $stN = mysqli_prepare($conexion, "SELECT tipoPrograma, diasAnticipo FROM san_notificaciones WHERE codigo = ?");
        if ($stN) {
            mysqli_stmt_bind_param($stN, 's', $codigo);
            mysqli_stmt_execute($stN);
            $resN = mysqli_stmt_get_result($stN);
            while ($resN && ($r = mysqli_fetch_assoc($resN))) {
                $tp = (int)($r['tipoPrograma'] ?? 0);
                if ($tp > 0) {
                    $tiposPrograma[] = $tp;
                }
                $d = (int)($r['diasAnticipo'] ?? 0);
                if ($d >= 1 && $d <= 7) {
                    $diasAnticipo = $d;
                }
            }
            mysqli_stmt_close($stN);
        }
    }

    $tiposPrograma = array_values(array_unique($tiposPrograma));
    header('Content-Type: application/json');
    echo json_encode([
        'telefono' => $telefono,
        'diasAnticipo' => $diasAnticipo,
        'tiposPrograma' => $tiposPrograma
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigoPais = limpiar_telefono($_POST['codigoPais'] ?? '');
    $numeroLocal = limpiar_telefono($_POST['numero'] ?? '');
    $telefono = limpiar_telefono($_POST['telefono'] ?? '');
    if ($codigoPais !== '' && $numeroLocal !== '') {
        $telefono = $codigoPais . $numeroLocal;
    }

    if (!preg_match('/^\d{9,15}$/', $telefono)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ingrese un número válido (solo dígitos, 9 a 15).']);
        exit;
    }

    $codigo = $_SESSION['usuario'];
    $telSave = guardar_telefono_usuario($conexion, $codigo, $telefono);
    if (!$telSave['ok']) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar número de WhatsApp.' . ($telSave['error'] ? ' ' . $telSave['error'] : '')
        ]);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Número guardado correctamente.']);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Método no permitido']);
