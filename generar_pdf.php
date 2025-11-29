<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit('No autorizado');
}

//ruta relativa a la conexion
include_once 'conexion_grs_joya\conexion.php';
$conexion = conectar_sanidad();
if (!$conexion) {
    die("Error de conexión.");
}

// Asegurar modo de excepciones para depuración clara
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
        c.usuarioRegistrador AS responsable_envio,
        c.autorizadoPor
    FROM com_db_muestra_cabecera c
    JOIN com_laboratorio l ON c.laboratorio = l.codigo
    JOIN com_emp_trans e ON c.empTrans = e.codigo
    WHERE c.codigoEnvio = '" . mysqli_real_escape_string($conexion, $codigoEnvio) . "'
"));

if (!$cab) {
    die("Registro no encontrado.");
}

// --- Tipos de muestra ---
$tipos_muestra = [];
$res_tm = mysqli_query($conexion, "SELECT nombre FROM com_tipo_muestra ORDER BY nombre");
while ($r = mysqli_fetch_assoc($res_tm)) {
    $tipos_muestra[] = htmlspecialchars($r['nombre']);
}

// --- Detalles ---
$detalles_raw = [];
$res_det = mysqli_query($conexion, "
    SELECT posicionSolicitud, fechaToma, codigoReferencia, numeroMuestras, observaciones, analisis
    FROM com_db_muestra_detalle
    WHERE codigoEnvio = '" . mysqli_real_escape_string($conexion, $codigoEnvio) . "'
    ORDER BY posicionSolicitud
");
while ($row = mysqli_fetch_assoc($res_det)) {
    $detalles_raw[] = $row;
}

// Procesar detalles
$detalles = [];
foreach ($detalles_raw as $row) {
    // Limpiar y filtrar códigos de análisis
    $analisis = $row['analisis'] ?? '';
    $analisisCodigos = array_filter(array_map('trim', explode(',', $analisis)), function($v) {
        return !empty($v);
    });
    $analisisNombres = [];
    $tipo_muestra = '-';

    if (!empty($analisisCodigos)) {
        // Preparar consulta con IN dinámico
        $placeholders = str_repeat('?,', count($analisisCodigos) - 1) . '?';
        $sql = "SELECT nombre FROM com_analisis WHERE codigo IN ($placeholders)";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, str_repeat('s', count($analisisCodigos)), ...$analisisCodigos);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($a = mysqli_fetch_assoc($result)) {
                $analisisNombres[] = htmlspecialchars($a['nombre']);
            }
            mysqli_stmt_close($stmt);
        }

        // Obtener tipo de muestra (del primer análisis)
        $primerCodigo = reset($analisisCodigos);
        $stmt_tm = mysqli_prepare($conexion, "SELECT tm.nombre FROM com_analisis a JOIN com_tipo_muestra tm ON a.tipoMuestra = tm.codigo WHERE a.codigo = ?");
        if ($stmt_tm) {
            mysqli_stmt_bind_param($stmt_tm, "s", $primerCodigo);
            mysqli_stmt_execute($stmt_tm);
            $tipo_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_tm));
            $tipo_muestra = htmlspecialchars($tipo_row['nombre'] ?? '-');
            mysqli_stmt_close($stmt_tm);
        }
    }

    // Formatear análisis: 2 por línea
    $analisisFormateados = [];
    for ($i = 0; $i < count($analisisNombres); $i += 2) {
        $linea = implode(', ', array_slice($analisisNombres, $i, 2));
        $analisisFormateados[] = $linea;
    }
    $row['analisis_nombres'] = implode("\n", $analisisFormateados);
    $row['tipo_muestra'] = $tipo_muestra;
    $detalles[] = $row;
}

mysqli_close($conexion);

// === Generar PDF con mPDF ===
require_once __DIR__ . '/vendor/autoload.php';
use Mpdf\Mpdf;

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'margin_top' => 25,
    'margin_bottom' => 20,
    'margin_left' => 12,
    'margin_right' => 12,
    'default_font_size' => 8
]);

// === Encabezado ===
$logo = '';
if (file_exists(__DIR__ . '/logo.png')) {
    $logo = '<img src="' . __DIR__ . '/logo.png" style="height: 20px; vertical-align: top;">';
}

$mpdf->SetHTMLHeader('
    <table width="100%" style="border-collapse: collapse; border: 1px solid #000;">
        <tr>
            <td style="width: 20%; text-align: left; padding: 5px; background-color: #fff;">
                ' . $logo . ' GRANJA RINCONADA DEL SUR S.A.
            </td>
            <td style="width: 60%; text-align: center; padding: 5px; background-color: #6c5b7b; color: white; font-weight: bold; font-size: 14px;">
                REGISTRO DE ENVÍO DE MUESTRAS
            </td>
            <td style="width: 20%; background-color: #fff;"></td>
        </tr>
    </table>
');

// === Contenido ===
$html = '';

$html .= '<div style="font-size:10px; font-weight:bold; margin-bottom:15px;">';
$html .= "Fecha de envío: {$cab['fechaEnvio']} - Hora: " . substr($cab['horaEnvio'], 0, 5) . '<br>';
$html .= "Código de envío: {$codigoEnvio}<br>";
$html .= "Laboratorio: {$cab['laboratorio']}";
$html .= '</div>';

$html .= '<table style="border-collapse: collapse; width: 100%; font-size: 8px;">';

// Cabecera
$html .= '<thead><tr>';
$html .= '<th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white;">Cód. Ref.</th>';
$html .= '<th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white;">Toma de muestra</th>';
$html .= '<th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white; height:80px; vertical-align:middle;"><div style="rotate: -90;">N° muestras</div></th>';

foreach ($tipos_muestra as $tm) {
    $html .= '<th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white; height:80px; vertical-align:middle;"><div style="rotate: -90;">' . $tm . '</div></th>';
}

$html .= '<th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white;">TIPO DE ANÁLISIS</th>';
$html .= '<th style="border:1px solid #000; padding:4px; text-align:center; background-color:#6c5b7b; color:white;">Observaciones</th>';
$html .= '</tr></thead>';

// Cuerpo
$html .= '<tbody>';
foreach ($detalles as $d) {
    $html .= '<tr>';
    $html .= '<td style="border:1px solid #000; padding:3px; text-align:center; background-color:#fff;">' . htmlspecialchars($d['codigoReferencia']) . '</td>';
    $html .= '<td style="border:1px solid #000; padding:3px; text-align:center; background-color:#fff;">' . htmlspecialchars($d['fechaToma']) . '</td>';
    $html .= '<td style="border:1px solid #000; padding:3px; text-align:center; background-color:#fff;">' . htmlspecialchars($d['numeroMuestras']) . '</td>';

    foreach ($tipos_muestra as $tm) {
        $mark = ($tm === $d['tipo_muestra']) ? 'x' : '';
        $html .= '<td style="border:1px solid #000; padding:3px; text-align:center; background-color:#fff;">' . $mark . '</td>';
    }

    $html .= '<td style="border:1px solid #000; padding:3px; vertical-align:top; white-space:pre-wrap; word-break:break-word; background-color:#fff;">' . htmlspecialchars($d['analisis_nombres']) . '</td>';
    $html .= '<td style="border:1px solid #000; padding:3px; vertical-align:top; white-space:pre-wrap; word-break:break-word; background-color:#fff;">' . htmlspecialchars($d['observaciones'] ?? '') . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';

// === Pie centrado con subrayado moderado ===
$html .= '<div style="margin-top:20px; font-size:10px; text-align:center;">';
$html .= '<table width="60%" style="border-collapse:collapse; margin:0 auto;">';
$html .= '<tr><td style="width:30%; padding:5px; text-align:right;">Empresa:</td><td style="width:70%; border-bottom:1px solid #000; padding:5px; text-align:left;">COMITÉ 4</td></tr>';
$html .= '<tr><td style="width:30%; padding:5px; text-align:right;">Autorizado por:</td><td style="width:70%; border-bottom:1px solid #000; padding:5px; text-align:left;">Dr. Julio Alvan</td></tr>';
$html .= '</table>';
$html .= '</div>';

$mpdf->WriteHTML($html);
$mpdf->Output("registro_{$codigoEnvio}.pdf", 'I');
?>