<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit('No autorizado');
}

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_sanidad();
if (!$conexion) {
    die("Error de conexión.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$codigoEnvio = $_GET['codigo'] ?? '';
if (!$codigoEnvio) {
    die("Código de envío no proporcionado.");
}

// --- Cabecera ---
$cab = mysqli_fetch_assoc(mysqli_query($conexion, "
    SELECT
        c.fechaEnvio,
        c.horaEnvio,
        c.codigoEnvio,
        l.nombre AS laboratorio,
        e.nombre AS empresa_transporte,
        c.usuarioRegistrador,
        c.autorizadoPor
    FROM com_db_muestra_cabecera c
    JOIN com_laboratorio l ON c.laboratorio = l.codigo
    JOIN com_emp_trans e ON c.empTrans = e.codigo
    WHERE c.codigoEnvio = '" . mysqli_real_escape_string($conexion, $codigoEnvio) . "'
"));

if (!$cab) {
    die("Registro no encontrado.");
}

// --- Detalles ---
$detalles = [];
$res_det = mysqli_query($conexion, "
    SELECT posicionSolicitud, fechaToma, codigoReferencia, numeroMuestras, observaciones, analisis
    FROM com_db_muestra_detalle
    WHERE codigoEnvio = '" . mysqli_real_escape_string($conexion, $codigoEnvio) . "'
    ORDER BY posicionSolicitud
");

while ($row = mysqli_fetch_assoc($res_det)) {
    // Obtener nombres de análisis
    $analisisCodigos = array_filter(array_map('trim', explode(',', $row['analisis'] ?? '')));
    $analisisNombres = [];
    if (!empty($analisisCodigos)) {
        $placeholders = str_repeat('?,', count($analisisCodigos) - 1) . '?';
        $stmt = mysqli_prepare($conexion, "SELECT nombre FROM com_analisis WHERE codigo IN ($placeholders)");
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($analisisCodigos)), ...$analisisCodigos);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($a = mysqli_fetch_assoc($result)) {
            $analisisNombres[] = htmlspecialchars($a['nombre']);
        }
        mysqli_stmt_close($stmt);
    }

    // Tipo de muestra (del primer análisis)
    $tipoMuestra = '-';
    if (!empty($analisisCodigos)) {
        $stmt = mysqli_prepare($conexion, "SELECT tm.nombre FROM com_analisis a JOIN com_tipo_muestra tm ON a.tipoMuestra = tm.codigo WHERE a.codigo = ?");
        mysqli_stmt_bind_param($stmt, "s", $analisisCodigos[0]);
        mysqli_stmt_execute($stmt);
        $tmRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $tipoMuestra = htmlspecialchars($tmRow['nombre'] ?? '-');
        mysqli_stmt_close($stmt);
    }

    $detalles[] = [
        'posicion' => $row['posicionSolicitud'],
        'fechaToma' => $row['fechaToma'] ?? '-',
        'codigoReferencia' => $row['codigoReferencia'] ?? '',
        'numeroMuestras' => $row['numeroMuestras'] ?? '1',
        'observaciones' => $row['observaciones'] ?? 'Ninguna',
        'analisis' => implode(', ', $analisisNombres),
        'tipoMuestra' => $tipoMuestra
    ];
}

mysqli_close($conexion);

// === Generar PDF con mPDF ===
require_once __DIR__ . '/vendor/autoload.php';
use Mpdf\Mpdf;

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 25,
    'margin_bottom' => 20,
    'margin_left' => 15,
    'margin_right' => 15,
    'default_font_size' => 10
]);

$logo = '';
if (file_exists(__DIR__ . '/logo.png')) {
    $logo = '<img src="' . __DIR__ . '/logo.png" style="height: 20px; vertical-align: top;">';
}

$mpdf->SetHTMLHeader('
    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td style="width: 20%; text-align: left;">' . $logo . ' GRANJA RINCONADA DEL SUR S.A.</td>
            <td style="width: 60%; text-align: center; font-weight: bold; font-size: 14px;">RESUMEN DE ENVÍO DE MUESTRAS</td>
            <td style="width: 20%;"></td>
        </tr>
    </table>
');

$html = '<h3 style="text-align:center; margin:10px 0;"> Resumen del Envío</h3>';
$html .= '<div style="font-size:11px; line-height:1.5;">';
$html .= '<strong>Código de Envío:</strong> ' . htmlspecialchars($cab['codigoEnvio']) . '<br>';
$html .= '<strong>Laboratorio:</strong> ' . htmlspecialchars($cab['laboratorio']) . '<br>';
$html .= '<strong>Fecha de Envío:</strong> ' . htmlspecialchars($cab['fechaEnvio']) . '<br>';
$html .= '<strong>Hora de Envío:</strong> ' . substr(htmlspecialchars($cab['horaEnvio']), 0, 5) . '<br>';
$html .= '<strong>Empresa de Transporte:</strong> ' . htmlspecialchars($cab['empresa_transporte']) . '<br>';
$html .= '<strong>Autorizado por:</strong> ' . htmlspecialchars($cab['autorizadoPor']) . '<br>';
$html .= '<strong>Usuario Registrador:</strong> ' . htmlspecialchars($cab['usuarioRegistrador']) . '<br>';
$html .= '<strong>Número de Muestras:</strong> ' . count($detalles) . '<br>';
$html .= '</div><hr>';

$html .= '<h3 style="margin-top:20px;"> Solicitudes</h3>';

foreach ($detalles as $i => $d) {
    $html .= '<div style="border:1px solid #ccc; padding:12px; margin:12px 0; border-radius:6px; font-size:10px;">';
    $html .= '<h4 style="margin:0 0 10px; color:#2d3748;">Solicitud #' . ($i + 1) . '</h4>';
    $html .= '<strong>Tipo de Muestra:</strong> ' . $d['tipoMuestra'] . '<br>';
    $html .= '<strong>Fecha de Toma:</strong> ' . $d['fechaToma'] . '<br>';
    $html .= '<strong>N° de Muestras:</strong> ' . $d['numeroMuestras'] . '<br>';
    $html .= '<strong>Código de Referencia:</strong> ' . $d['codigoReferencia'] . '<br>';
    $html .= '<strong>Observaciones:</strong> ' . $d['observaciones'] . '<br>';
    $html .= '<strong>Análisis Solicitados:</strong><br>';
    $html .= '<span style="display:inline-block; margin-top:4px; padding:4px 8px; background:#f0f0f0; border-radius:4px;">' . ($d['analisis'] ?: 'Ninguno') . '</span>';
    $html .= '</div>';
}

$mpdf->WriteHTML($html);
$mpdf->Output("resumen_envio_{$codigoEnvio}.pdf", 'I');
?>