<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$telefono = preg_replace('/\D+/', '', (string)($payload['telefono'] ?? ''));
$mensaje = trim((string)($payload['mensaje'] ?? ''));

if (!preg_match('/^\d{9,15}$/', $telefono)) {
    echo json_encode(['success' => false, 'message' => 'Número inválido. Use de 9 a 15 dígitos.']);
    exit;
}

if ($mensaje === '') {
    echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío.']);
    exit;
}

if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($mensaje, 'UTF-8') > 6000) {
        $mensaje = mb_substr($mensaje, 0, 6000, 'UTF-8');
    }
} elseif (strlen($mensaje) > 6000) {
    $mensaje = substr($mensaje, 0, 6000);
}

$whatsPath = __DIR__ . '/../../../includes/whatsapp_enviar.php';
if (!is_file($whatsPath)) {
    echo json_encode(['success' => false, 'message' => 'No se encontró el módulo de envío WhatsApp.']);
    exit;
}

require_once $whatsPath;

$ok = enviar_whatsapp($telefono, $mensaje);
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'No se pudo enviar. Verifique la configuración de WhatsApp.']);
exit;
