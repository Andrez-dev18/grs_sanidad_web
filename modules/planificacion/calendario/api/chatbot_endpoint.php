<?php
/**
 * Chatbot endpoint: el asistente se desenvuelve en lenguaje natural; cuando detecta
 * una petición con fechas (calendario o comparativo), extrae fechas y las pasa al backend.
 * Usa Groq API si está configurado, si no Ollama local.
 */
session_start();

$CHATBOT_USE_GROQ = false;
$raizProyecto = dirname(__DIR__, 4);
$groqConfigPath = $raizProyecto . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'groq.php';
if (!is_file($groqConfigPath) && isset($_SERVER['DOCUMENT_ROOT'])) {
    $groqConfigPath = $_SERVER['DOCUMENT_ROOT'] . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, '/sanidad/config/groq.php');
}
if (is_file($groqConfigPath)) {
    require_once $groqConfigPath;
    $CHATBOT_USE_GROQ = defined('GROQ_API_KEY') && GROQ_API_KEY !== '';
}

if (empty($_SESSION['active'])) {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Saludos: si el mensaje es solo un saludo, no llamar a Ollama ni devolver reporte
$SALUDOS = [
    'hola', 'holi', 'holis', 'hey', 'hi', 'hello', 'buenos días', 'buenas tardes', 'buenas noches',
    'buen día', 'qué tal', 'que tal', 'saludos', 'buenas', 'ola', 'ey'
];

// --- FAQ: preguntas frecuentes (pregunta normalizada => respuesta)
$FAQ = [
    'como veo el calendario' => 'Puedes decir por ejemplo: "¿Qué hay en el calendario esta semana?" o "Muéstrame el calendario del último mes". Te generaré el enlace al calendario con las fechas que indiques.',
    'donde veo necropsias' => 'Puedes pedir: "Comparativo de necropsias de la última semana" o "Necropsias entre el 1 y el 15 de marzo". Te daré el enlace al reporte comparativo Necropsias vs Cronograma.',
    'que puedo preguntar' => 'Puedes pedir: ver el calendario (por fecha, última semana, último mes o entre dos fechas), o el comparativo de necropsias para un periodo. También puedes escribir "hoy", "calendario hoy" o "necropsias hoy".',
    'ayuda' => 'Puedes preguntar por el calendario ("calendario última semana", "calendario hoy") o por el reporte comparativo de necropsias ("necropsias último mes", "necropsias hoy"). Escribe en lenguaje natural las fechas que te interesan.',
    'ultima semana' => 'Para la última semana puedo mostrarte el calendario o el comparativo de necropsias. ¿Quieres "calendario última semana" o "comparativo necropsias última semana"?',
    'comparativo' => 'El comparativo muestra Necropsias vs Cronograma. Di por ejemplo: "Comparativo de necropsias de la última semana" o "Necropsias hoy".',
];

function normalizarParaFAQ($texto) {
    $t = mb_strtolower(trim(preg_replace('/\s+/', ' ', $texto)), 'UTF-8');
    $t = preg_replace('/[¿?¡!.,;:]/u', '', $t);
    return $t;
}

function buscarFAQ($mensaje, $faqList) {
    $key = normalizarParaFAQ($mensaje);
    if (isset($faqList[$key])) {
        return $faqList[$key];
    }
    foreach ($faqList as $pregunta => $respuesta) {
        if (strpos($key, $pregunta) !== false || strpos($pregunta, $key) !== false) {
            return $respuesta;
        }
    }
    return null;
}

/** True si el mensaje es solo un saludo o muy corto sin pedir fechas/calendario/reporte */
function esSoloSaludo($mensaje, $saludosList) {
    $key = normalizarParaFAQ($mensaje);
    if (strlen($key) > 50) {
        return false;
    }
    foreach ($saludosList as $saludo) {
        if ($key === $saludo || $key === $saludo . ' ' || preg_match('/^' . preg_quote($saludo, '/') . '\s*$/u', $key)) {
            return true;
        }
        if (strpos($key, $saludo) === 0 && strlen($key) <= strlen($saludo) + 5) {
            return true;
        }
    }
    return false;
}

function llamarOllama($prompt, $modelo = 'qwen2.5:1.5b') {
    $url = 'http://localhost:11434/api/generate';
    $data = [
        'model' => $modelo,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.2,
            'num_predict' => 220
        ]
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err || $httpCode !== 200) {
        return ['error' => $err ?: 'HTTP ' . $httpCode];
    }
    $dec = json_decode($response, true);
    if (!isset($dec['response'])) {
        return ['error' => 'Respuesta Ollama inválida'];
    }
    return ['texto' => trim($dec['response'])];
}

/** Llama a Groq API (chat completions). Mismo formato de salida que llamarOllama. */
function llamarGroq($systemPrompt, $userMessage, $apiKey, $model = 'llama-3.1-8b-instant') {
    $url = 'https://api.groq.com/openai/v1/chat/completions';
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage]
        ],
        'temperature' => 0.1,
        'max_tokens' => 280,
        'response_format' => ['type' => 'json_object']
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        return ['error' => $err];
    }
    if ($httpCode !== 200) {
        return ['error' => 'HTTP ' . $httpCode . (preg_match('/"error"/', $response) ? ': ' . $response : '')];
    }
    $dec = json_decode($response, true);
    $content = $dec['choices'][0]['message']['content'] ?? null;
    if ($content === null) {
        return ['error' => 'Respuesta Groq inválida'];
    }
    return ['texto' => trim($content)];
}

/** Extrae un objeto JSON de la respuesta del modelo (puede venir dentro de ```json o con texto alrededor) */
function extraerJsonRespuesta($texto) {
    $texto = trim($texto);
    if (preg_match('/```(?:json)?\s*(\{[^`]+\})\s*```/s', $texto, $m)) {
        $dec = json_decode($m[1], true);
        if (is_array($dec)) return $dec;
    }
    $inicio = strpos($texto, '{');
    if ($inicio !== false) {
        $fin = strrpos($texto, '}');
        if ($fin !== false && $fin > $inicio) {
            $dec = json_decode(substr($texto, $inicio, $fin - $inicio + 1), true);
            if (is_array($dec)) return $dec;
        }
    }
    return null;
}

/** Normaliza petición extraída del JSON (accion, rango, fechas). */
function normalizarPeticion($json, $hoy) {
    $accionesPermitidas = ['CALENDARIO', 'COMPARATIVO'];
    $rangosPermitidos = ['LAST_7_DAYS', 'LAST_30_DAYS', 'POR_FECHA', 'ENTRE_FECHAS'];
    $accion = strtoupper(trim((string)($json['accion'] ?? '')));
    if (!in_array($accion, $accionesPermitidas, true)) return null;
    $rango = strtoupper(trim((string)($json['rango'] ?? '')));
    if (!in_array($rango, $rangosPermitidos, true)) {
        if ($rango === 'ULTIMA_SEMANA' || $rango === 'SEMANA') $rango = 'LAST_7_DAYS';
        elseif ($rango === 'ULTIMO_MES' || $rango === 'MES') $rango = 'LAST_30_DAYS';
        elseif ($rango === 'HOY' || $rango === 'TODAY' || $rango === '') $rango = 'POR_FECHA';
        else $rango = 'LAST_7_DAYS';
    }
    $f1 = isset($json['fecha_inicio']) ? trim((string)$json['fecha_inicio']) : null;
    $f2 = isset($json['fecha_fin']) ? trim((string)$json['fecha_fin']) : null;
    // Aceptar también camelCase por si el modelo lo devuelve así
    if ($f1 === null && isset($json['fechaInicio'])) $f1 = trim((string)$json['fechaInicio']);
    if ($f2 === null && isset($json['fechaFin'])) $f2 = trim((string)$json['fechaFin']);
    // Para POR_FECHA: si no hay fecha válida (vacía, "hoy", o no YYYY-MM-DD), usar hoy
    if ($rango === 'POR_FECHA') {
        $valida = $f1 !== null && $f1 !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f1);
        if (!$valida || strtolower($f1) === 'hoy' || strtolower($f1) === 'today') {
            $f1 = $hoy;
            $f2 = $hoy;
        } elseif ($f2 === null || $f2 === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f2)) {
            $f2 = $f1;
        }
    }
    return ['accion' => $accion, 'rango' => $rango, 'fecha_inicio' => $f1 ?: null, 'fecha_fin' => $f2 ?: null];
}

/** Interpreta periodos relativos en el mensaje y devuelve una sola fecha Y-m-d o null. */
function interpretarPeriodoRelativo($mensaje) {
    $m = mb_strtolower(trim(preg_replace('/\s+/', ' ', $mensaje)), 'UTF-8');
    $hoy = new DateTime('today');
    if (preg_match('/\b(mañana|manana|tomorrow)\b/u', $m)) {
        $d = (clone $hoy)->modify('+1 day');
        return $d->format('Y-m-d');
    }
    if (preg_match('/\b(pasado\s*mañana|pasado\s*manana|day\s*after\s*tomorrow)\b/u', $m)) {
        $d = (clone $hoy)->modify('+2 days');
        return $d->format('Y-m-d');
    }
    if (preg_match('/\b(ayer|yesterday)\b/u', $m)) {
        $d = (clone $hoy)->modify('-1 day');
        return $d->format('Y-m-d');
    }
    if (preg_match('/\b(anteayer|ante\s*ayer)\b/u', $m)) {
        $d = (clone $hoy)->modify('-2 days');
        return $d->format('Y-m-d');
    }
    return null;
}

/** Interpreta rangos relativos (próxima semana, próximo mes) y devuelve ['inicio'=>'Y-m-d','fin'=>'Y-m-d'] o null. */
function interpretarRangoRelativo($mensaje) {
    $m = mb_strtolower(trim(preg_replace('/\s+/', ' ', $mensaje)), 'UTF-8');
    $hoy = new DateTime('today');
    // próxima semana / siguiente semana / next week = próximos 7 días (desde mañana)
    if (preg_match('/\b(pr[oó]xima|siguiente|next)\s+(semana|week)\b/u', $m) || preg_match('/\b(pr[oó]ximos|siguientes)\s+7\s*d[ií]as?\b/u', $m)) {
        $inicio = (clone $hoy)->modify('+1 day');
        $fin = (clone $hoy)->modify('+7 days');
        return ['inicio' => $inicio->format('Y-m-d'), 'fin' => $fin->format('Y-m-d')];
    }
    // próximo mes / next month = desde el 1 del próximo mes hasta el último día
    if (preg_match('/\b(pr[oó]ximo|siguiente|next)\s+mes\b/u', $m)) {
        $inicio = (clone $hoy)->modify('first day of next month');
        $fin = (clone $inicio)->modify('last day of this month');
        return ['inicio' => $inicio->format('Y-m-d'), 'fin' => $fin->format('Y-m-d')];
    }
    return null;
}

function calcularRangoFechas($rango, $fechaInicio = null, $fechaFin = null) {
    $hoy = new DateTime('today');
    $maxDias = 365;
    $maxFuturo = 365; // permitir calendario hasta 1 año adelante (ej. "eventos mañana")
    switch ($rango) {
        case 'LAST_7_DAYS':
            $fin = clone $hoy;
            $inicio = (clone $hoy)->modify('-6 days');
            return [$inicio->format('Y-m-d'), $fin->format('Y-m-d')];
        case 'LAST_30_DAYS':
            $fin = clone $hoy;
            $inicio = (clone $hoy)->modify('-29 days');
            return [$inicio->format('Y-m-d'), $fin->format('Y-m-d')];
        case 'POR_FECHA':
            if ($fechaInicio && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
                $d = DateTime::createFromFormat('Y-m-d', $fechaInicio);
                if ($d && $d->format('Y-m-d') === $fechaInicio) {
                    $limiteFuturo = (clone $hoy)->modify('+' . $maxFuturo . ' days');
                    if ($d > $limiteFuturo) return null;
                    return [$fechaInicio, $fechaInicio];
                }
            }
            return null;
        case 'ENTRE_FECHAS':
            if ($fechaInicio && $fechaFin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
                $d1 = DateTime::createFromFormat('Y-m-d', $fechaInicio);
                $d2 = DateTime::createFromFormat('Y-m-d', $fechaFin);
                if (!$d1 || $d1->format('Y-m-d') !== $fechaInicio || !$d2 || $d2->format('Y-m-d') !== $fechaFin) return null;
                if ($d1 > $d2) return null;
                $limiteFuturo = (clone $hoy)->modify('+' . $maxFuturo . ' days');
                if ($d2 > $limiteFuturo) return null;
                if ($d1->diff($d2)->days > $maxDias) return null;
                return [$fechaInicio, $fechaFin];
            }
            return null;
    }
    return null;
}

function construirUrlCalendario($fechaInicio, $fechaFin) {
    // Abrir vista calendario; el usuario puede navegar al rango indicado en el mensaje
    return 'dashboard-calendario.php';
}

function construirUrlComparativo($fechaInicio, $fechaFin) {
    $base = '../../cronograma/generar_reporte_necropsias_vs_cronograma.php';
    $params = [
        'periodoTipo' => 'ENTRE_FECHAS',
        'fechaInicio' => $fechaInicio,
        'fechaFin' => $fechaFin
    ];
    return $base . '?' . http_build_query($params);
}

// --- POST: mensaje del usuario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$mensaje = isset($input['mensaje']) ? trim((string)$input['mensaje']) : '';

if ($mensaje === '') {
    echo json_encode(['ok' => false, 'error' => 'Escribe un mensaje.']);
    exit();
}

// 1) Si es solo saludo: responder amigable y no llamar a Ollama ni devolver reporte
if (esSoloSaludo($mensaje, $SALUDOS)) {
    echo json_encode([
        'ok' => true,
        'mensaje' => '¡Hola! Puedo ayudarte a ver el calendario o el comparativo de necropsias. Di por ejemplo: "calendario hoy", "última semana", "necropsias último mes" o "entre 2025-01-01 y 2025-01-31".',
        'accion' => 'saludo',
        'url' => null,
        'label' => null
    ]);
    exit();
}

// 2) Revisar FAQ (solo si no es una petición de rango/fecha que queremos que procese Ollama)
$respuestaFAQ = buscarFAQ($mensaje, $FAQ);
if ($respuestaFAQ !== null) {
    echo json_encode([
        'ok' => true,
        'mensaje' => $respuestaFAQ,
        'accion' => 'faq',
        'url' => null,
        'label' => null
    ]);
    exit();
}

// 3) Una sola llamada: prompt few-shot para que el modelo sea más predictivo (Groq u Ollama)
$hoy = date('Y-m-d');
$manana = (new DateTime('today'))->modify('+1 day')->format('Y-m-d');
$proxInicio = (new DateTime('today'))->modify('+1 day')->format('Y-m-d');
$proxFin = (new DateTime('today'))->modify('+7 days')->format('Y-m-d');
$promptSistema = <<<PROMPT
Eres un clasificador. Solo respondes con un JSON válido, sin otro texto.

Reglas:
- tipo "peticion" = usuario pide ver CALENDARIO (eventos, agenda, cronograma) o COMPARATIVO (necropsias). Usa "conversacion" solo si saluda, agradece o no pide ninguna de esas dos cosas.
- accion: "CALENDARIO" o "COMPARATIVO".
- rango: "POR_FECHA" (un día), "LAST_7_DAYS" (últimos 7 días), "LAST_30_DAYS" (último mes), "ENTRE_FECHAS" (entre dos fechas).
- fecha_inicio y fecha_fin: YYYY-MM-DD. Para POR_FECHA usa la misma fecha en ambos. Para LAST_7_DAYS y LAST_30_DAYS puedes usar null.
- Fechas de hoy: $hoy, mañana: $manana. Próxima semana (7 días adelante): $proxInicio a $proxFin.

EJEMPLOS (responde exactamente en este formato):

Usuario: calendario hoy
{"tipo":"peticion","accion":"CALENDARIO","rango":"POR_FECHA","fecha_inicio":"$hoy","fecha_fin":"$hoy"}

Usuario: eventos mañana
{"tipo":"peticion","accion":"CALENDARIO","rango":"POR_FECHA","fecha_inicio":"$manana","fecha_fin":"$manana"}

Usuario: para la próxima semana
{"tipo":"peticion","accion":"CALENDARIO","rango":"ENTRE_FECHAS","fecha_inicio":"$proxInicio","fecha_fin":"$proxFin"}

Usuario: última semana
{"tipo":"peticion","accion":"CALENDARIO","rango":"LAST_7_DAYS","fecha_inicio":null,"fecha_fin":null}

Usuario: hola
{"tipo":"conversacion","mensaje":"¡Hola! Puedo mostrarte el calendario o el comparativo de necropsias. Di por ejemplo calendario hoy o próxima semana."}

Ahora responde solo el JSON para este mensaje:
PROMPT;

if ($CHATBOT_USE_GROQ) {
    $resultado = llamarGroq($promptSistema, "Usuario: " . $mensaje, GROQ_API_KEY, defined('GROQ_CHATBOT_MODEL') ? GROQ_CHATBOT_MODEL : 'llama-3.1-8b-instant');
} else {
    $resultado = llamarOllama($promptSistema . "\n\nUsuario: " . $mensaje . "\n\nJSON:", 'qwen2.5:1.5b');
}

if (isset($resultado['error'])) {
    $backend = $CHATBOT_USE_GROQ ? 'Groq' : 'Ollama';
    echo json_encode([
        'ok' => false,
        'error' => 'No se pudo conectar con el asistente (' . $backend . ').',
        'detalle' => $resultado['error']
    ]);
    exit();
}

$json = extraerJsonRespuesta($resultado['texto']);
$tipo = isset($json['tipo']) ? trim((string)$json['tipo']) : '';

// Respuesta conversación: una sola llamada, devolvemos el mensaje del modelo
if ($tipo === 'conversacion') {
    $mensajeNatural = trim((string)($json['mensaje'] ?? ''));
    if ($mensajeNatural === '') {
        $mensajeNatural = 'Puedo mostrarte el calendario o el comparativo de necropsias. Prueba con "calendario hoy", "última semana" o "necropsias último mes".';
    }
    echo json_encode([
        'ok' => true,
        'mensaje' => $mensajeNatural,
        'accion' => 'conversacion',
        'url' => null,
        'label' => null
    ]);
    exit();
}

// Respuesta petición: validar y devolver URL (sin segunda llamada si falla)
$parsed = $tipo === 'peticion' ? normalizarPeticion($json, $hoy) : null;
if ($parsed === null) {
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Puedo mostrarte el calendario o el comparativo de necropsias. Di por ejemplo "calendario hoy", "última semana" o "necropsias último mes".',
        'accion' => null,
        'url' => null,
        'label' => null
    ]);
    exit();
}

$rango = $parsed['rango'];
$fechaInicioParsed = $parsed['fecha_inicio'];
$fechaFinParsed = $parsed['fecha_fin'];

// Interpretar rangos relativos en el mensaje (próxima semana, próximo mes) — siempre en backend para fechas correctas
$rangoRelativo = interpretarRangoRelativo($mensaje);
if ($rangoRelativo !== null) {
    $rango = 'ENTRE_FECHAS';
    $fechaInicioParsed = $rangoRelativo['inicio'];
    $fechaFinParsed = $rangoRelativo['fin'];
} else {
    // Interpretar una sola fecha relativa (mañana, ayer, etc.)
    $fechaRelativa = interpretarPeriodoRelativo($mensaje);
    if ($fechaRelativa !== null) {
        $rango = 'POR_FECHA';
        $fechaInicioParsed = $fechaRelativa;
        $fechaFinParsed = $fechaRelativa;
    }
}

$fechas = calcularRangoFechas($rango, $fechaInicioParsed, $fechaFinParsed);

// Respaldo por periodo relativo: si falló el rango pero el mensaje pide hoy/mañana/ayer o próxima semana
if ($fechas === null && $parsed['accion'] === 'CALENDARIO') {
    $rr = interpretarRangoRelativo($mensaje);
    if ($rr !== null) {
        $fechas = [$rr['inicio'], $rr['fin']];
        $rango = 'ENTRE_FECHAS';
    } else {
        $msgNorm = mb_strtolower($mensaje, 'UTF-8');
        if (strpos($msgNorm, 'hoy') !== false || strpos($msgNorm, 'today') !== false) {
            $fechas = [date('Y-m-d'), date('Y-m-d')];
            $rango = 'POR_FECHA';
        } elseif (isset($fechaRelativa) && $fechaRelativa !== null) {
            $fechas = [$fechaRelativa, $fechaRelativa];
            $rango = 'POR_FECHA';
        }
    }
}

if ($fechas === null) {
    echo json_encode([
        'ok' => true,
        'mensaje' => 'No pude usar ese rango de fechas. Prueba con "calendario hoy", "última semana", "último mes" o "entre 2025-01-01 y 2025-01-31".',
        'accion' => null,
        'url' => null,
        'label' => null
    ]);
    exit();
}

list($fechaInicio, $fechaFin) = $fechas;
$accion = $parsed['accion'];

if ($accion === 'CALENDARIO') {
    $url = construirUrlCalendario($fechaInicio, $fechaFin);
    $label = 'Abrir calendario';
    $mensajeRespuesta = "Calendario del $fechaInicio al $fechaFin. Haz clic en el botón para ver los eventos.";
} else {
    $url = construirUrlComparativo($fechaInicio, $fechaFin);
    $label = 'Ver reporte comparativo (Necropsias vs Cronograma)';
    $mensajeRespuesta = "Reporte comparativo de necropsias del $fechaInicio al $fechaFin. Abre el enlace para ver el PDF o resultado.";
}

echo json_encode([
    'ok' => true,
    'mensaje' => $mensajeRespuesta,
    'accion' => strtolower($accion),
    'url' => $url,
    'label' => $label,
    'fecha_inicio' => $fechaInicio,
    'fecha_fin' => $fechaFin
]);
