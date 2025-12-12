<?php
// --- CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once '../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    http_response_code(500);
    exit('{"error":"Error de conexión"}');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$codigoEnvio = $_GET['codigo'] ?? '';
if (!$codigoEnvio) {
    http_response_code(400);
    exit('{"error":"Código de envío no proporcionado"}');
}

// --- Cabecera ---
$queryCab = "
    SELECT
        c.fecEnvio AS fechaEnvio,
        c.horaEnvio,
        c.codEnvio AS codigoEnvio,
        c.nomLab AS laboratorio,
        c.nomEmpTrans AS empresa_transporte,
        c.usuarioRegistrador AS responsable_envio,
        c.usuarioResponsable AS usuario_responsable,
        c.autorizadoPor
    FROM san_fact_solicitud_cab c
    WHERE c.codEnvio = ?
";
$stmtCab = mysqli_prepare($conexion, $queryCab);
mysqli_stmt_bind_param($stmtCab, "s", $codigoEnvio);
mysqli_stmt_execute($stmtCab);
$cab = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCab));
mysqli_stmt_close($stmtCab);

if (!$cab) {
    die("Registro no encontrado.");
}

// --- Tipos de muestra (para columnas) ---
$tipos_muestra = [];
$res_tm = mysqli_query($conexion, "SELECT nombre FROM san_dim_tipo_muestra ORDER BY codigo");
while ($r = mysqli_fetch_assoc($res_tm)) {
    $tipos_muestra[] = htmlspecialchars($r['nombre']);
}

// --- Detalles: traer todos los registros ---
$detalles_raw = [];
$res_det = mysqli_query($conexion, "
    SELECT posSolicitud, fecToma, codRef, numMuestras, obs, codAnalisis
    FROM san_fact_solicitud_det
    WHERE codEnvio = '" . mysqli_real_escape_string($conexion, $codigoEnvio) . "'
    ORDER BY posSolicitud
");
while ($row = mysqli_fetch_assoc($res_det)) {
    $detalles_raw[] = $row;
}

// === AGRUPAR POR codRef ===
$grupos = [];
foreach ($detalles_raw as $row) {
    $codRef = $row['codRef'];
    if (!isset($grupos[$codRef])) {
        $grupos[$codRef] = [
            'codRef' => $codRef,
            'fecToma' => $row['fecToma'],
            'numMuestras' => $row['numMuestras'],
            'obs' => $row['obs'] ?? '',
            'analisis_codigos' => [],
            'tipo_muestra' => '-'
        ];
    }
    // Acumular códigos de análisis
    if (!empty($row['codAnalisis'])) {
        $grupos[$codRef]['analisis_codigos'][] = $row['codAnalisis'];
    }
    // Si aún no tiene observación y esta no está vacía, tomarla
    if (empty($grupos[$codRef]['obs']) && !empty($row['obs'])) {
        $grupos[$codRef]['obs'] = $row['obs'];
    }
}

// === Procesar cada grupo: obtener análisis, paquetes y tipo de muestra ===
$detalles_agrupados = [];
foreach ($grupos as $grupo) {
    $analisisCodigos = array_unique($grupo['analisis_codigos']);
    $analisisConPaquete = [];
    $tipo_muestra = '-';

    if (!empty($analisisCodigos)) {
        $placeholders = str_repeat('?,', count($analisisCodigos) - 1) . '?';
        $sql = "
            SELECT 
                a.nombre AS analisis_nombre,
                p.nombre AS paquete_nombre,
                tm.nombre AS tipo_muestra_nombre
            FROM san_dim_analisis a
            JOIN san_dim_paquete p ON a.paquete = p.codigo
            JOIN san_dim_tipo_muestra tm ON p.tipoMuestra = tm.codigo
            WHERE a.codigo IN ($placeholders)
            ORDER BY p.nombre, a.nombre
        ";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($analisisCodigos)), ...$analisisCodigos);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $paquetes = [];
        while ($a = mysqli_fetch_assoc($result)) {
            $paquete = htmlspecialchars($a['paquete_nombre']);
            $analisisNombre = htmlspecialchars($a['analisis_nombre']);
            $paquetes[$paquete][] = $analisisNombre;
            // Tomar el tipo de muestra (debería ser el mismo en todos)
            if ($tipo_muestra === '-') {
                $tipo_muestra = htmlspecialchars($a['tipo_muestra_nombre']);
            }
        }
        mysqli_stmt_close($stmt);

        // Formato: "<b>Paquete X:</b> anal1, anal2"
        foreach ($paquetes as $paq => $analisisLista) {
            $analisisConPaquete[] = '<b>' . $paq . ':</b> ' . implode(', ', $analisisLista);
        }
    }

    // Formatear en líneas de hasta 2 paquetes por línea
    $analisisFormateados = [];
    for ($i = 0; $i < count($analisisConPaquete); $i += 2) {
        $linea = implode(', ', array_slice($analisisConPaquete, $i, 2));
        $analisisFormateados[] = $linea;
    }

    $grupo['analisis_nombres'] = implode("\n", $analisisFormateados);
    $grupo['tipo_muestra'] = $tipo_muestra;
    $detalles_agrupados[] = $grupo;
}

mysqli_close($conexion);

// === Generar PDF ===
require_once __DIR__ . '/../vendor/autoload.php';
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

// Encabezado
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

// Contenido
$html = '';

// ===== CABECERA EN DOS COLUMNAS CON ALINEACIÓN DE DOS PUNTOS =====
// ===== CABECERA EN 2 COLUMNAS CON ALINEACIÓN INTERNA (etiqueta : valor) =====
$html .= '<table style="border-collapse: collapse; width: 100%; font-size: 10px; margin-bottom: 15px; line-height: 1.6;">';
$html .= '<tr>';

// Columna 1: Fecha de envío + Hora de envío
$html .= '<td style="width: 50%; vertical-align: top; padding-right: 10px;">';
$html .= '<table style="border-collapse: collapse; width: 100%;">';
$html .= '<tr>';
$html .= '<td style="padding: 2px 0; vertical-align: top; text-align: left; width: 45%; white-space: nowrap;"><strong>Fecha de envío</strong></td>';
$html .= '<td style="padding: 2px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>';
$html .= '<td style="padding: 2px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">' . htmlspecialchars($cab['fechaEnvio']) . '</td>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td style="padding: 2px 0; vertical-align: top; text-align: left; width: 45%; white-space: nowrap;"><strong>Hora de envío</strong></td>';
$html .= '<td style="padding: 2px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>';
$html .= '<td style="padding: 2px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">' . substr(htmlspecialchars($cab['horaEnvio']), 0, 8) . '</td>';
$html .= '</tr>';
$html .= '</table>';
$html .= '</td>';

// Columna 2: Código de envío + Laboratorio
$html .= '<td style="width: 50%; vertical-align: top; padding-left: 10px;">';
$html .= '<table style="border-collapse: collapse; width: 100%;">';
$html .= '<tr>';
$html .= '<td style="padding: 2px 0; vertical-align: top; text-align: left; width: 45%; white-space: nowrap;"><strong>Código de envío</strong></td>';
$html .= '<td style="padding: 2px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>';
$html .= '<td style="padding: 2px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">' . htmlspecialchars($cab['codigoEnvio']) . '</td>';
$html .= '</tr>';
$html .= '<tr>';
$html .= '<td style="padding: 2px 0; vertical-align: top; text-align: left; width: 45%; white-space: nowrap;"><strong>Laboratorio</strong></td>';
$html .= '<td style="padding: 2px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>';
$html .= '<td style="padding: 2px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">' . htmlspecialchars($cab['laboratorio']) . '</td>';
$html .= '</tr>';
$html .= '</table>';
$html .= '</td>';

$html .= '</tr>';
$html .= '</table>';

// Tabla de detalles
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

// Cuerpo — AHORA USAMOS $detalles_agrupados
$html .= '<tbody>';
foreach ($detalles_agrupados as $d) {
    $html .= '<tr>';
    $html .= '<td style="border:1px solid #000; padding:3px; text-align:center;">' . htmlspecialchars($d['codRef']) . '</td>';
    $html .= '<td style="border:1px solid #000; padding:3px; text-align:center;">' . htmlspecialchars($d['fecToma']) . '</td>';
    $html .= '<td style="border:1px solid #000; padding:3px; text-align:center;">' . htmlspecialchars($d['numMuestras']) . '</td>';

    foreach ($tipos_muestra as $tm) {
        $mark = ($tm === $d['tipo_muestra']) ? 'x' : '';
        $html .= '<td style="border:1px solid #000; padding:3; text-align:center;">' . $mark . '</td>';
    }

    $html .= '<td style="border:1px solid #000; padding:3px; vertical-align:top; white-space:pre-wrap; word-break:break-word;">' . nl2br($d['analisis_nombres']) . '</td>';
    $html .= '<td style="border:1px solid #000; padding:3px; vertical-align:top; white-space:pre-wrap; word-break:break-word;">' . htmlspecialchars($d['obs'] ?? '') . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';

// === Pie con datos reales (campos a la izquierda, dos puntos centrados, valor subrayado, todo centrado en página) ===
$usuarioResponsable = htmlspecialchars($cab['usuario_responsable'] ?? 'No especificado');
$empresa = htmlspecialchars($cab['empresa_transporte'] ?? 'No especificado');
$autorizado = htmlspecialchars($cab['autorizadoPor'] ?? 'No especificado');

$html .= '<div style="margin-top:20px; font-size:10px; text-align:center;">';
$html .= '<table style="border-collapse: collapse; width: 60%; margin: 0 auto; font-size:10px; line-height:1.5;">';

// Responsable de envío
$html .= '<tr>';
$html .= '<td style="padding: 3px 0; vertical-align: top; text-align: left; width: 40%; white-space: nowrap;"><strong>Responsable de envío</strong></td>';
$html .= '<td style="padding: 3px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>';
$html .= '<td style="padding: 3px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">' . $usuarioResponsable . '</td>';
$html .= '</tr>';

// Empresa
$html .= '<tr>';
$html .= '<td style="padding: 3px 0; vertical-align: top; text-align: left; width: 40%; white-space: nowrap;"><strong>Empresa</strong></td>';
$html .= '<td style="padding: 3px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>';
$html .= '<td style="padding: 3px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">' . $empresa . '</td>';
$html .= '</tr>';

// Autorizado por
$html .= '<tr>';
$html .= '<td style="padding: 3px 0; vertical-align: top; text-align: left; width: 40%; white-space: nowrap;"><strong>Autorizado por</strong></td>';
$html .= '<td style="padding: 3px 5px; vertical-align: top; text-align: center; width: 10px; min-width: 10px;">:</td>';
$html .= '<td style="padding: 3px 0; vertical-align: top; text-align: left; border-bottom: 1px solid #000;">' . $autorizado . '</td>';
$html .= '</tr>';

$html .= '</table>';
$html .= '</div>';

$mpdf->WriteHTML($html);
$mpdf->Output("registro_{$codigoEnvio}.pdf", 'I');
?>