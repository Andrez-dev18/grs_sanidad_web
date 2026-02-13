<?php

date_default_timezone_set('America/Lima');

$baseDir = __DIR__;
require_once $baseDir . '/conexion_grs_joya/conexion.php';
require_once $baseDir . '/includes/whatsapp_enviar.php';

$conn = conectar_joya();
if (!$conn) {
    exit("Error de conexión\n");
}

$fechaEjecucionObjetivo = date('Y-m-d', strtotime('+2 days'));

$chkTable = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
$chkCab = @$conn->query("SHOW TABLES LIKE 'san_fact_programa_cab'");
$chkTel = @$conn->query("SHOW TABLES LIKE 'san_telefono_sanidad'");
if (!$chkTable || $chkTable->num_rows === 0 || !$chkCab || $chkCab->num_rows === 0 || !$chkTel || $chkTel->num_rows === 0) {
    $conn->close();
    exit("Tablas requeridas no encontradas\n");
}

$chkNomGranja = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'nomGranja'");
$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$tieneNomGranja = $chkNomGranja && $chkNomGranja->num_rows > 0;
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;

$sql = "SELECT c.codPrograma, c.nomPrograma, c.granja, c.campania, c.galpon, c.fechaCarga, c.fechaEjecucion";
if ($tieneNomGranja) $sql .= ", c.nomGranja";
if ($tieneEdad) $sql .= ", c.edad";
$sql .= ", cab.nomTipo FROM san_fact_cronograma c
    INNER JOIN san_fact_programa_cab cab ON cab.codigo = c.codPrograma
    WHERE DATE(c.fechaEjecucion) = ?
    ORDER BY c.codPrograma, c.granja, c.campania, c.galpon, c.fechaEjecucion";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $fechaEjecucionObjetivo);
$stmt->execute();
$res = $stmt->get_result();
$eventos = [];
while ($row = $res->fetch_assoc()) {
    $eventos[] = $row;
}
$stmt->close();

if (empty($eventos)) {
    $conn->close();
    exit("Sin eventos para el " . $fechaEjecucionObjetivo . "\n");
}

$resTel = $conn->query("SELECT codigo, telefono FROM san_telefono_sanidad WHERE telefono IS NOT NULL AND telefono != ''");
if (!$resTel || $resTel->num_rows === 0) {
    $conn->close();
    exit("Sin usuarios con teléfono configurado\n");
}

$destinatarios = [];
while ($row = $resTel->fetch_assoc()) {
    $t = trim($row['telefono']);
    if ($t !== '') $destinatarios[] = $t;
}
$conn->close();

$fechaFormato = date('d/m/Y', strtotime($fechaEjecucionObjetivo));
$mensaje = "📋 *Recordatorio cronograma* — Eventos del *" . $fechaFormato . "* (en 2 días)\n\n";

foreach ($eventos as $r) {
    $nomTipo = $r['nomTipo'] ?? '';
    $codPrograma = $r['codPrograma'] ?? '';
    $nomPrograma = $r['nomPrograma'] ?? '';
    $granja = $r['granja'] ?? '';
    $nomGranja = ($tieneNomGranja && !empty($r['nomGranja'])) ? $r['nomGranja'] : $nomPrograma;
    $campania = $r['campania'] ?? '';
    $galpon = $r['galpon'] ?? '';
    $edad = $tieneEdad ? ($r['edad'] ?? '') : '';
    $fechaCarga = !empty($r['fechaCarga']) ? date('d/m/Y', strtotime($r['fechaCarga'])) : '—';
    $fechaEjec = !empty($r['fechaEjecucion']) ? date('d/m/Y H:i', strtotime($r['fechaEjecucion'])) : '—';
    $mensaje .= "• *Tipo:* " . $nomTipo . " | *Código:* " . $codPrograma . "\n";
    $mensaje .= "  Granja: " . $granja . " | Nombre: " . $nomGranja . " | Campaña: " . $campania . " | Galpón: " . $galpon . " | Edad: " . $edad . "\n";
    $mensaje .= "  F. carga: " . $fechaCarga . " | F. ejecución: " . $fechaEjec . "\n\n";
}

$enviados = 0;
foreach ($destinatarios as $telefono) {
    if (enviar_whatsapp($telefono, $mensaje)) $enviados++;
}

$total = count($destinatarios);
exit("Enviados: " . $enviados . " de " . $total . "\n");
