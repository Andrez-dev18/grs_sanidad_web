<?php

date_default_timezone_set('America/Lima');

$log_file = __DIR__ . '/cron_recordatorio_whatsapp.log';

function escribir_log($mensaje, $log_file) {
    $fecha = date('Y-m-d H:i:s');
    $log_mensaje = "[$fecha] [CRON-RECORDATORIO] $mensaje\n";
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }
    @file_put_contents($log_file, $log_mensaje, FILE_APPEND);
    echo $log_mensaje;
}

escribir_log("=== INICIO Recordatorio WhatsApp (configurable) ===", $log_file);


require_once __DIR__ . '/../../../../conexion_grs/conexion.php';
define('WHATSAPP_CONFIG_LIB_ONLY', true);
require_once __DIR__ . '/whatsapp_config.php';

$conn = conectar_joya_mysqli();
if (!$conn) {
    escribir_log("ERROR: No se pudo conectar a la base de datos", $log_file);
    exit(1);
}

escribir_log("Conexión exitosa", $log_file);

// Configuración por usuario y tipo de programa
$sqlConf = "
    SELECT n.codigo,
           COALESCE(n.tipoPrograma, 0) AS tipoPrograma,
           COALESCE(n.diasAnticipo, 0) AS diasAnticipo,
           NULLIF(TRIM(u.telefo), '') AS telefono,
           COALESCE(NULLIF(TRIM(u.nombre), ''), n.codigo) AS nombre_usuario
    FROM san_notificaciones n
    LEFT JOIN usuario u ON u.codigo = n.codigo
    WHERE n.codigo IS NOT NULL
";
$resConf = $conn->query($sqlConf);
if (!$resConf || $resConf->num_rows === 0) {
    escribir_log("Sin configuración en san_notificaciones", $log_file);
    $conn->close();
    exit(0);
}

$confUsuarios = [];
while ($r = $resConf->fetch_assoc()) {
    $codigo = trim((string)($r['codigo'] ?? ''));
    $tipo = (int)($r['tipoPrograma'] ?? 0);
    $dias = (int)($r['diasAnticipo'] ?? 0);
    $telefono = trim((string)($r['telefono'] ?? ''));
    $nombre = trim((string)($r['nombre_usuario'] ?? $codigo));

    if ($codigo === '' || $tipo <= 0) continue;
    if ($dias < 1 || $dias > 7) continue;
    if ($telefono === '') continue;

    if (!isset($confUsuarios[$codigo])) {
        $confUsuarios[$codigo] = [
            'telefono' => $telefono,
            'nombre' => $nombre !== '' ? $nombre : $codigo,
            'porDias' => []
        ];
    }
    if (!isset($confUsuarios[$codigo]['porDias'][$dias])) {
        $confUsuarios[$codigo]['porDias'][$dias] = [];
    }
    $confUsuarios[$codigo]['porDias'][$dias][] = $tipo;
}

if (empty($confUsuarios)) {
    escribir_log("No hay usuarios con configuración válida (tipo/días/teléfono).", $log_file);
    $conn->close();
    exit(0);
}

$baseUrl = 'https://granjarinconadadelsur.com/sanidad';
$totalIntentos = 0;
$totalEnviados = 0;

foreach ($confUsuarios as $codigoUsuario => $cfg) {
    $telefono = $cfg['telefono'];
    $nombreUsuario = $cfg['nombre'];
    foreach ($cfg['porDias'] as $diasAnticipo => $tipos) {
        $tipos = array_values(array_unique(array_filter($tipos, function ($v) {
            return (int)$v > 0;
        })));
        if (empty($tipos)) continue;

        $fechaEjecucionObjetivo = date('Y-m-d', strtotime('+' . (int)$diasAnticipo . ' days'));
        $tiposInt = array_map('intval', $tipos);
        $placeholders = implode(',', array_fill(0, count($tiposInt), '?'));

        $sqlEventos = "SELECT c.codPrograma, c.nomPrograma, c.granja, c.campania, c.galpon, c.fechaCarga, c.fechaEjecucion,
                              COALESCE(c.nomGranja, c.nomPrograma, '') AS nomGranja,
                              COALESCE(c.edad, '') AS edad,
                              cab.nomTipo, cab.codTipo
            FROM san_fact_cronograma c
            INNER JOIN san_fact_programa_cab cab ON cab.codigo = c.codPrograma
            WHERE DATE(c.fechaEjecucion) = ?
              AND cab.codTipo IN ($placeholders)
            ORDER BY c.codPrograma, c.granja, c.campania, c.galpon, c.fechaEjecucion";

        $stmtEv = $conn->prepare($sqlEventos);
        if (!$stmtEv) {
            escribir_log("ERROR preparando consulta de eventos para usuario $codigoUsuario: " . $conn->error, $log_file);
            continue;
        }

        $types = 's' . str_repeat('i', count($tiposInt));
        $params = array_merge([$fechaEjecucionObjetivo], $tiposInt);
        $bind = [$types];
        foreach ($params as $k => $v) $bind[] = &$params[$k];
        call_user_func_array([$stmtEv, 'bind_param'], $bind);
        $stmtEv->execute();
        $resEv = $stmtEv->get_result();
        $eventos = [];
        while ($resEv && ($ev = $resEv->fetch_assoc())) {
            $eventos[] = $ev;
        }
        $stmtEv->close();

        if (empty($eventos)) {
            escribir_log("Usuario $codigoUsuario: sin eventos para +$diasAnticipo días (fecha $fechaEjecucionObjetivo).", $log_file);
            continue;
        }

        $textoEventos = '';
        foreach ($eventos as $r) {
            $nomTipo = $r['nomTipo'] ?? '';
            $codPrograma = $r['codPrograma'] ?? '';
            $nomPrograma = $r['nomPrograma'] ?? '';
            $granja = $r['granja'] ?? '';
            $nomGranja = $r['nomGranja'] ?? $nomPrograma;
            $campania = $r['campania'] ?? '';
            $galpon = $r['galpon'] ?? '';
            $edad = $r['edad'] ?? '';
            $fechaCarga = !empty($r['fechaCarga']) ? date('d/m/Y', strtotime($r['fechaCarga'])) : '—';
            $fechaEjec = !empty($r['fechaEjecucion']) ? date('d/m/Y H:i', strtotime($r['fechaEjecucion'])) : '—';
            $textoEventos .= "• *Tipo:* " . $nomTipo . " | *Código:* " . $codPrograma . "\n";
            $textoEventos .= "  Granja: " . $granja . " | Nombre: " . $nomGranja . " | Campaña: " . $campania . " | Galpón: " . $galpon . " | Edad: " . $edad . "\n";
            $textoEventos .= "  F. carga: " . $fechaCarga . " | F. ejecución: " . $fechaEjec . "\n\n";
        }

        $urlCalendario = $baseUrl . '/modules/planificacion/calendario/dashboard-calendario.php?fecha=' . $fechaEjecucionObjetivo;
        $mensaje = "Estimado *" . $nombreUsuario . "*, GRS te recuerda que dentro de *" . $diasAnticipo . "* día(s) tendremos estos eventos en nuestro cronograma:\n\n";
        $mensaje .= $textoEventos;
        $mensaje .= "\n📅 Ver calendario: " . $urlCalendario;

        $totalIntentos++;
        if (enviar_whatsapp_configurado($telefono, $mensaje)) {
            $totalEnviados++;
            escribir_log("Enviado a $telefono ($nombreUsuario) - usuario $codigoUsuario, +$diasAnticipo días, eventos: " . count($eventos), $log_file);
        } else {
            escribir_log("Error al enviar a $telefono - usuario $codigoUsuario, +$diasAnticipo días", $log_file);
        }
    }
}

$conn->close();
escribir_log("Envíos exitosos: $totalEnviados de $totalIntentos", $log_file);
escribir_log("=== FIN Recordatorio WhatsApp ===", $log_file);
exit($totalIntentos === 0 ? 0 : ($totalEnviados === $totalIntentos ? 0 : 1));
