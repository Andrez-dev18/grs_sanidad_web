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
        c.usuarioRegistrador AS usuario_registrador,
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

// === AGRUPAR POR posSolicitud ===
$grupos = [];
foreach ($detalles_raw as $row) {
    $posSolicitud = $row['posSolicitud'];
    if (!isset($grupos[$posSolicitud])) {
        $grupos[$posSolicitud] = [
            'posSolicitud' => $posSolicitud,
            'codRef' => $row['codRef'],
            'fecToma' => $row['fecToma'],
            'numMuestras' => $row['numMuestras'],
            'obs' => $row['obs'] ?? '',
            'analisis_codigos' => [],
            'tipo_muestra' => '-'
        ];
    }
    // Acumular códigos de análisis
    if (!empty($row['codAnalisis'])) {
        $grupos[$posSolicitud]['analisis_codigos'][] = $row['codAnalisis'];
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
        /*$sql = "
            SELECT 
                a.nombre AS analisis_nombre,
                p.nombre AS paquete_nombre,
                tm.nombre AS tipo_muestra_nombre
            FROM san_dim_analisis a
            JOIN san_dim_paquete p ON a.paquete = p.codigo
            JOIN san_dim_tipo_muestra tm ON p.tipoMuestra = tm.codigo
            WHERE a.codigo IN ($placeholders)
            ORDER BY p.nombre, a.nombre
        ";*/
        $sql = "
        SELECT
        nomAnalisis AS analisis_nombre,
        nomPaquete AS paquete_nombre,
        nomMuestra AS tipo_muestra_nombre
    FROM
        san_fact_solicitud_det sd
    WHERE
        sd.codEnvio = ? AND sd.posSolicitud = ? AND sd.codAnalisis IN ($placeholders)
    ORDER BY
        paquete_nombre, analisis_nombre
";
        $stmt = mysqli_prepare($conexion, $sql);

        // Tipos: 's' para codEnvio, 's' para posSolicitud, y luego N 's' para análisis
        $tipos = 'ss' . str_repeat('s', count($analisisCodigos));

        // Parámetros: codEnvio, posSolicitud, y luego los códigos de análisis
        mysqli_stmt_bind_param($stmt, $tipos, $codigoEnvio, $grupo['posSolicitud'], ...$analisisCodigos);

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


// === Generar PDF con mPDF ===
require_once __DIR__ . '/../vendor/autoload.php';
use Mpdf\Mpdf;

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 25,
    'margin_bottom' => 20,
    'margin_left' => 15,
    'margin_right' => 15,
    'default_font_size' => 10,
    'tempDir' => __DIR__ . '/../pdf_tmp',
]);

$logo = '';
if (file_exists(__DIR__ . '/../logo.png')) {
    $logo = '<img src="' . __DIR__ . '/../logo.png" style="height: 20px; vertical-align: top;">';
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

/**
 * Función para calcular el ancho necesario basado en el campo más largo
 */
function calcularAnchoCampo($campos)
{
    $maxLongitud = 0;
    foreach (array_keys($campos) as $etiqueta) {
        // Aproximación: 1 carácter ≈ 0.5% en A4 con fuente 11px
        $longitud = strlen($etiqueta);
        if ($longitud > $maxLongitud) {
            $maxLongitud = $longitud;
        }
    }
    // Ajuste empírico: 35% para campos normales, 40% para campos más largos
    if ($maxLongitud > 20) {
        return '40%';
    } elseif ($maxLongitud > 15) {
        return '35%';
    } else {
        return '30%';
    }
}

/**
 * Función para generar tabla con campos alineados a la izquierda y dos puntos alineados
 */
function generarTablaAlineada($campos, $titulo = '')
{
    $anchoCampo = calcularAnchoCampo($campos);

    $html = '';
    if ($titulo) {
        $html .= '<h4 style="margin:0 0 10px; color:#2d3748; font-size:13px; font-weight:bold;">' . $titulo . '</h4>';
    }

    $html .= '<table style="border-collapse: collapse; width: 100%; font-size:11px; line-height:1.6;">';

    foreach ($campos as $etiqueta => $valor) {
        $html .= '<tr>';
        // Campo alineado a la IZQUIERDA
        $html .= '<td style="padding: 3px 0 3px 0; vertical-align: top; text-align: left; width: ' . $anchoCampo . '; white-space: nowrap;"><strong>' . $etiqueta . '</strong></td>';
        // Dos puntos - ANCHO MÍNIMO FIJO
        $html .= '<td style="padding: 3px 5px; vertical-align: top; text-align: center; width: 15px; min-width: 15px; max-width: 15px;"><strong>:</strong></td>';
        // Valor
        $html .= '<td style="padding: 3px 0; vertical-align: top; text-align: left;">' . htmlspecialchars($valor) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</table>';

    return $html;
}

$html = '<h3 style="text-align:center; margin:10px 0;">Resumen del Envío</h3>';

// ===== TABLA CABECERA =====
$html .= '<div style="margin-bottom:20px;">';

$camposCabecera = [
    'Fecha de Envío' => $cab['fechaEnvio'],
    'Hora de Envío' => substr($cab['horaEnvio'], 0, 5),
    'Código de Envío' => $cab['codigoEnvio'],
    'Laboratorio' => $cab['laboratorio'],
    'Empresa de Transporte' => $cab['empresa_transporte'],
    'Autorizado por' => $cab['autorizadoPor'],
    'Usuario Registrador' => $cab['usuario_registrador']
];

$html .= generarTablaAlineada($camposCabecera);
$html .= '</div>';

$html .= '<hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">';

$html .= '<h3 style="margin-top:20px;">Solicitudes</h3>';

foreach ($detalles_agrupados as $i => $d) {
    $html .= '<div style="margin:15px 0; padding:10px; background:#fff; font-size:10px;">';

    // ===== TABLA SOLICITUD =====
    $camposSolicitud = [
        'Tipo de Muestra' => $d['tipo_muestra'],
        'Fecha de Toma' => $d['fecToma'],
        'N° de Muestras' => $d['numMuestras'],
        'Código de Referencia' => $d['codRef'],
        'Observaciones' => $d['obs'],
    ];

    $html .= generarTablaAlineada($camposSolicitud, 'Solicitud #' . ($i + 1));

    // ===== ANÁLISIS SOLICITADOS =====
    $html .= '<div style="margin-top:12px;">';
    $html .= '<div style="margin-bottom:4px;"><strong>Análisis Solicitados:</strong></div>';
    $html .= '<div style="padding:6px 10px; background:#f8f9fa; border:1px solid #eee; border-radius:4px; font-family: monospace; white-space: pre-line;">' . $d['analisis_nombres'] . '</div>';
    $html .= '</div>';

    $html .= '</div>';
}

$mpdf->WriteHTML($html);
$qrSize = '20mm';
$qrHtml = '
        <div style="
            position: absolute;
            bottom: 10mm;
            right: 12mm;
            width: ' . $qrSize . ';
            height: ' . $qrSize . ';
            z-index: 10;
        ">
            <barcode code="' . htmlspecialchars($codigoEnvio) . '" type="QR" size="0.7" error="M" />
        </div>
        ';
$mpdf->WriteHTML($qrHtml);
$mpdf->Output("resumen_envio_{$codigoEnvio}.pdf", 'I');
?>