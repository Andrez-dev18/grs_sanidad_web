<?php
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$conexionPath = __DIR__ . '/../../../../conexion_grs/conexion.php';
if (!is_file($conexionPath)) {
    echo json_encode(['success' => false, 'message' => 'No se encuentra conexión: ' . $conexionPath]);
    exit;
}
include_once $conexionPath;
$conn = conectar_joya_mysqli();
if (!$conn) {
    $errMsg = 'Error de conexión a la base de datos.';
    if (function_exists('mysqli_connect_error')) {
        $e = mysqli_connect_error();
        if ($e) $errMsg .= ' ' . $e;
    }
    echo json_encode(['success' => false, 'message' => $errMsg]);
    exit;
}

// Número de WhatsApp: el que el usuario guarda/actualiza en la configuración (whatsapp_config.php → usuario.telefo o san_telefono_sanidad).
$codigo = $_SESSION['usuario'];
$telefono = '';
$stmt = @mysqli_prepare($conn, "SELECT COALESCE(telefo, '') AS telefono FROM usuario WHERE codigo = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $codigo);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $telefono = trim($row['telefono'] ?? '');
    }
    mysqli_stmt_close($stmt);
}
if ($telefono === '') {
    $chk = @$conn->query("SHOW TABLES LIKE 'san_telefono_sanidad'");
    if ($chk && $chk->num_rows > 0) {
        $st2 = mysqli_prepare($conn, "SELECT COALESCE(telefono, '') AS telefono FROM san_telefono_sanidad WHERE codigo = ? LIMIT 1");
        if ($st2) {
            mysqli_stmt_bind_param($st2, 's', $codigo);
            mysqli_stmt_execute($st2);
            $res2 = mysqli_stmt_get_result($st2);
            if ($res2 && $r2 = mysqli_fetch_assoc($res2)) {
                $telefono = trim($r2['telefono'] ?? '');
            }
            mysqli_stmt_close($st2);
        }
    }
}
if ($telefono === '') {
    echo json_encode(['success' => false, 'message' => 'No tiene un número guardado. Guarde su teléfono primero.']);
    exit;
}

// Obtener un registro de cronograma con tipo de programa (para armar el mensaje de prueba)
$chkTable = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
$chkCab = @$conn->query("SHOW TABLES LIKE 'san_fact_programa_cab'");
$tieneCronograma = $chkTable && $chkTable->num_rows > 0;
$tieneCab = $chkCab && $chkCab->num_rows > 0;

$nomTipo = '';
$codPrograma = '';
$nomPrograma = '';
$granja = '';
$nomGranja = '';
$campania = '';
$galpon = '';
$edad = '';
$fechaCarga = '';
$fechaEjecucion = '';

if ($tieneCronograma && $tieneCab) {
    $chkNomGranja = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
    $chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
    $tieneNomGranja = $chkNomGranja && $chkNomGranja->num_rows > 0;
    $tieneEdad = $chkEdad && $chkEdad->num_rows > 0;
    $sql = "SELECT c.codPrograma, c.nomPrograma, c.granja, c.campania, c.galpon, c.fechaCarga, c.fechaEjecucion";
    if ($tieneNomGranja) $sql .= ", c.nomGranja";
    if ($tieneEdad) $sql .= ", c.edad";
    $sql .= ", cab.nomTipo FROM san_fact_cronograma c
        INNER JOIN san_fact_programa_cab cab ON cab.codigo = c.codPrograma
        ORDER BY c.fechaEjecucion DESC, c.id DESC LIMIT 1";
    $q = $conn->query($sql);
    if ($q && $r = $q->fetch_assoc()) {
        $nomTipo = $r['nomTipo'] ?? '';
        $codPrograma = $r['codPrograma'] ?? '';
        $nomPrograma = $r['nomPrograma'] ?? '';
        $granja = $r['granja'] ?? '';
        $nomGranja = ($tieneNomGranja && !empty($r['nomGranja'])) ? $r['nomGranja'] : $nomPrograma;
        $campania = $r['campania'] ?? '';
        $galpon = $r['galpon'] ?? '';
        $edad = $tieneEdad ? ($r['edad'] ?? '') : '';
        $fechaCarga = !empty($r['fechaCarga']) ? date('d/m/Y', strtotime($r['fechaCarga'])) : '—';
        $fechaEjecucion = !empty($r['fechaEjecucion']) ? date('d/m/Y H:i', strtotime($r['fechaEjecucion'])) : '—';
    }
}

$conn->close();

if ($nomTipo === '' && $codPrograma === '') {
    $nomTipo = 'Ejemplo';
    $codPrograma = 'N/A';
    $nomPrograma = 'Sin datos en cronograma';
    $granja = '—';
    $nomGranja = '—';
    $campania = '—';
    $galpon = '—';
    $edad = '—';
    $fechaCarga = date('d/m/Y');
    $fechaEjecucion = date('d/m/Y H:i');
}

$mensaje = "📋 *Prueba - Recordatorio cronograma*\n\n";
$mensaje .= "*Tipo de programa:* " . $nomTipo . "\n";
$mensaje .= "*Código del programa:* " . $codPrograma . "\n";
$mensaje .= "*Granja:* " . $granja . " | *Nombre:* " . $nomGranja . " | *Campaña:* " . $campania . " | *Galpón:* " . $galpon . " | *Edad:* " . $edad . "\n";
$mensaje .= "*Fecha de carga:* " . $fechaCarga . "\n";
$mensaje .= "*Fecha ejecución:* " . $fechaEjecucion . "\n";

require_once __DIR__ . '/../../../includes/whatsapp_enviar.php';
$enviado = enviar_whatsapp($telefono, $mensaje);

if ($enviado) {
    echo json_encode(['success' => true, 'message' => 'Mensaje de prueba enviado a su número.']);
} else {
    echo json_encode(['success' => false, 'message' => 'No se pudo enviar. Revise config/whatsapp.php (appkey, authkey) o conexión.']);
}
