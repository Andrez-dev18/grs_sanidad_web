<?php

function enviar_whatsapp($numero_destino, $mensaje) {
    $configPath = __DIR__ . '/../config/whatsapp.php';
    if (is_file($configPath)) {
        require_once $configPath;
    }
    $appkey = defined('WHATSAPP_APPKEY') ? trim((string) WHATSAPP_APPKEY) : '';
    $authkey = defined('WHATSAPP_AUTHKEY') ? trim((string) WHATSAPP_AUTHKEY) : '';
    $baseUrl = defined('WHATSAPP_BASE_URL') ? trim((string) WHATSAPP_BASE_URL) : '';

    if ($appkey === '' || $authkey === '' || $baseUrl === '') {
        return false;
    }

    $numero_destino = preg_replace('/\D/', '', $numero_destino);
    if ($numero_destino === '' || $mensaje === '') {
        return false;
    }

    $dataEnviar = [
        'appkey'  => $appkey,
        'authkey' => $authkey,
        'to'      => $numero_destino,
        'message' => $mensaje
    ];

    $ch = curl_init($baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS    => json_encode($dataEnviar),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER    => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT      => 15
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}
