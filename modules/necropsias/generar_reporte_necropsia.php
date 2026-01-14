<?php
require_once __DIR__ . '../../../vendor/autoload.php';

use Mpdf\Mpdf;

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) die('Error de conexión');

/* ===============================
   PARÁMETROS
================================ */
$granja   = $_GET['granja'] ?? '';
$numreg  = (int)($_GET['numreg'] ?? 0);
$fectra_input = $_GET['fectra'] ?? '';

$fechaObj = DateTime::createFromFormat('d/m/Y', $fectra_input);
$fectra = $fechaObj ? $fechaObj->format('Y-m-d') : '';

if (!$granja || !$numreg || !$fectra) die('Parámetros inválidos');

/* ===============================
   CONSULTA
================================ */
$sql = "SELECT * FROM t_regnecropsia
        WHERE tgranja = ? AND tnumreg = ? AND tfectra = ?
        ORDER BY tid ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sis", $granja, $numreg, $fectra);
$stmt->execute();
$registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$registros) die('Sin registros');

// Verificar si hay imágenes (evidencia)
$tieneImagenes = false;
foreach ($registros as $reg) {
    if (!empty($reg['evidencia']) && trim($reg['evidencia']) !== '') {
        $tieneImagenes = true;
        break;
    }
}

// Si se solicita solo verificación (parámetro check)
if (isset($_GET['check']) && $_GET['check'] == '1') {
    header('Content-Type: application/json');
    echo json_encode(['tiene_imagenes' => $tieneImagenes]);
    $conn->close();
    exit;
}

// Si no hay imágenes, no generar PDF (debe ser manejado por el frontend)
if (!$tieneImagenes) {
    header('Content-Type: application/json');
    echo json_encode(['tiene_imagenes' => false, 'mensaje' => 'No se registró imágenes']);
    $conn->close();
    exit;
}

$conn->close();

/* ===============================
   MPDF CONFIG
================================ */
$tempDir = __DIR__ . '/../../pdf_tmp';
$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'simpleTables' => true,
    'useSubstitutions' => false,
    'autoPageBreak' => true,
    'tempDir' => $tempDir
]);

/* ===============================
   CABECERA
================================ */
$granjaRaw = $registros[0]['tcencos'];
$granjaTxt = trim(explode('C=', $granjaRaw)[0]);

/* ===============================
   PORCENTAJES
================================ */
$porcentajes = [];
foreach ($registros as $r) {
    $porcentajes[$r['tnivel']][$r['tparametro']] = $r['tporcentajetotal'];
}

/* ===============================
   HTML
================================ */
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial; font-size: 11pt; }
    h1 { text-align:center; color:#002060; font-size:26pt; }
    h2 {
        text-align:center;
        color:#002060;
        font-size:22pt;
        border-bottom:4px solid #002060;
        margin:40px 0 20px 0;
        padding-bottom:6px;
        page-break-before:always;
    }
    .header { text-align:center; margin-bottom:30px; }
    .granja { font-size:22pt; font-weight:bold; color:#002060; }
    .detalles { font-size:15pt; }

    .nivel { font-size:14pt; font-weight:bold; color:#002060; margin-bottom:6px; }

    .img-box {
        width:260px;
        height:200px;
    }

    .porc-box {
        width:260px;
        margin-top:6px;
        font-size:10pt;
        background: #D5D5D5;
    }

    .tabla-bursal {
        width: 70%;
        margin: 50px auto 0 auto;
        border-collapse: collapse;
        font-size: 11pt;
        background: #ffffff;
        clear: both;
    }

    .tabla-bursal th,
    .tabla-bursal td {
        border: 1px solid #ffffff;
        padding: 5px 7px;
        text-align: center;
    }

    .tabla-bursal th {
        background: #d9d9d9;
        color: #000;
        font-weight: bold;
    }

    .tabla-bursal tbody tr:nth-child(odd) {
        background: #f5f5f5;
    }

    .tabla-bursal tbody tr:nth-child(even) {
        background: #ffffff;
    }

    .tabla-bursal .estado {
        text-align: left;
        padding-left: 10px;
    }
</style>
</head>
<body>

<div class="header">
    <h1>REPORTE DE NECROPSIA</h1>
    <div class="granja">GRANJA ' . htmlspecialchars($granjaTxt) . '</div>
    <div class="detalles">
        Campaña ' . $registros[0]['tcampania'] . ' &nbsp;&nbsp;
        GALPÓN ' . $registros[0]['tgalpon'] . ' &nbsp;&nbsp;
        EDAD ' . $registros[0]['tedad'] . ' DÍAS
    </div>
    <p><strong>N° Reg:</strong> ' . $numreg . ' &nbsp;&nbsp;
       <strong>Fecha:</strong> ' . date('d/m/Y', strtotime($fectra)) . '</p>
</div>
';

/* ===============================
   TABLA BURSAL (ESTÁTICA)
================================ */
$tablaBursalHTML = '
<table class="tabla-bursal">
<thead>
<tr>
<th>N° Ave</th><th>Peso Ave</th><th>Peso Bursa</th><th>Índice Bursal</th><th>Estado</th>
</tr>
</thead>
<tbody>
<tr><td>01</td><td>1.555</td><td>0.75</td><td>0.48</td><td>Atrofia severa</td></tr>
<tr><td>02</td><td>1.705</td><td>1.62</td><td>0.95</td><td>Atrofia</td></tr>
<tr><td>03</td><td>1.705</td><td>1.75</td><td>1.02</td><td>Normal</td></tr>
<tr><td>04</td><td>1.995</td><td>0.96</td><td>0.48</td><td>Atrofia severa</td></tr>
<tr><td>05</td><td>1.625</td><td>1.17</td><td>0.72</td><td>Atrofia</td></tr>
</tbody>
</table>
';

/* ===============================
   CONTENIDO POR SISTEMA
================================ */
$sistema_actual = '';
$niveles = [];
$col = 0;
$tabla_bursal_mostrada = false;

foreach ($registros as $reg) {

    if ($reg['tsistema'] !== $sistema_actual) {

        if ($sistema_actual !== '') {
            $html .= '</tr></table>';

            if (!$tabla_bursal_mostrada && stripos($sistema_actual, 'INMUNO') !== false) {
                $html .= $tablaBursalHTML;
                $tabla_bursal_mostrada = true;
            }
        }

        $html .= '<h2>' . htmlspecialchars($reg['tsistema']) . '</h2>';
        $html .= '<table width="100%" cellpadding="5"><tr>';

        $sistema_actual = $reg['tsistema'];
        $niveles = [];
        $col = 0;
    }

    if (in_array($reg['tnivel'], $niveles)) continue;
    $niveles[] = $reg['tnivel'];

    if ($col === 3) {
        $html .= '</tr></table><pagebreak /><table width="100%" cellpadding="5"><tr>';
        $col = 0;
    }

    $imgHtml = 'Sin evidencia';
    if (!empty($reg['evidencia'])) {
        $img = trim(explode(',', $reg['evidencia'])[0]);
        $path = realpath(__DIR__ . '/../../' . $img);
        if ($path) $imgHtml = '<img src="file:///' . $path . '" width="240">';
    }

    $porcHtml = '';
    if (!empty($porcentajes[$reg['tnivel']])) {
        foreach ($porcentajes[$reg['tnivel']] as $p => $v) {
            $porcHtml .= "
        <tr>
            <td>$p</td>
            <td><strong>" . number_format($v, 2) . "%</strong></td>
        </tr>";
        }
    }

    $html .= '
    <td width="33%" align="center" valign="top">
        <div class="nivel">' . htmlspecialchars($reg['tnivel']) . '</div>
        <table class="img-box"><tr><td align="center" valign="middle">' . $imgHtml . '</td></tr></table>
        <table class="porc-box">' . $porcHtml . '</table>
    </td>';

    $col++;
}

/* CIERRES FINALES */
$html .= '</tr></table>';

if (!$tabla_bursal_mostrada && stripos($sistema_actual, 'INMUNO') !== false) {
    $html .= $tablaBursalHTML;
}

$html .= '</body></html>';

$mpdf->WriteHTML($html);
$mpdf->Output('Reporte_Necropsia_' . $numreg . '.pdf', 'I');
