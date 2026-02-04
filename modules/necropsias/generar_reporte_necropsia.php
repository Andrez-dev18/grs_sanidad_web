<?php
require_once __DIR__ . '../../../vendor/autoload.php';
date_default_timezone_set('America/Lima');
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
// Se asume que la columna tobservacion ya existe en la tabla t_regnecropsia
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
        $break;
    }
}

if (isset($_GET['check']) && $_GET['check'] == '1') {
    header('Content-Type: application/json');
    echo json_encode(['tiene_imagenes' => $tieneImagenes]);
    $conn->close();
    exit;
}

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
    'margin_top' => 10,
    'margin_bottom' => 15,
    'simpleTables' => true,
    'useSubstitutions' => false,
    'autoPageBreak' => true,
    'tempDir' => $tempDir
]);

/* ===============================
   CABECERA Y DATOS GENERALES
================================ */
$granjaRaw = $registros[0]['tcencos'];
$granjaTxt = trim(explode('C=', $granjaRaw)[0]);
$fechaGeneracion = date('d/m/Y h:i A');

// EXTRAER EL DIAGNÓSTICO PRESUNTIVO (Es igual para todo el lote)
$diagnosticoPresuntivo = $registros[0]['tdiagpresuntivo'] ?? '';

// Fecha/hora inicio y fin de registro (para mostrar en reporte)
$tfecreghorainicio = $registros[0]['tfecreghorainicio'] ?? '';
$tfecreghorafin = $registros[0]['tfecreghorafin'] ?? '';
$tfecreghorainicioFormatted = '--/--/-- --:--:--';
$tfecreghorafinFormatted = '--/--/-- --:--:--';
if (!empty(trim($tfecreghorainicio))) {
    try {
        $dateTime = new DateTime(trim($tfecreghorainicio));
        $tfecreghorainicioFormatted = $dateTime->format('d/m/Y H:i:s');
    } catch (Exception $e) {
        $tfecreghorainicioFormatted = $tfecreghorainicio;
    }
}
if (!empty(trim($tfecreghorafin))) {
    try {
        $dateTime = new DateTime(trim($tfecreghorafin));
        $tfecreghorafinFormatted = $dateTime->format('d/m/Y H:i:s');
    } catch (Exception $e) {
        $tfecreghorafinFormatted = $tfecreghorafin;
    }
}

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
    h1 { text-align:center; color:#002060; font-size:26pt; margin-top: 0px; }
    
    /* ESTILO DE SUBTÍTULOS (Sistemas y ahora Diagnóstico) */
    h2 {
        text-align:center;
        color:#002060;
        font-size:22pt;
        border-bottom:4px solid #002060;
        margin:40px 0 20px 0;
        padding-bottom:6px;
        page-break-before:always; /* ESTO FUERZA LA NUEVA HOJA */
    }

    .header { text-align:center; margin-bottom:30px; }
    .granja { font-size:22pt; font-weight:bold; color:#002060; }
    .detalles { font-size:15pt; }

    .nivel { font-size:14pt; font-weight:bold; color:#002060; margin-bottom:6px; }

    .img-box {
        width: 260px;
        min-height: 200px;
        overflow: visible;
        text-align: center;
    }
    
    td { vertical-align: top; }

    .porc-box {
        width:260px;
        margin-top:6px;
        margin-bottom:12px;
        font-size:10pt;
        background: #D5D5D5;
    }

    /* --- ESTILO PARA OBSERVACIONES --- */
    .observacion-box {
        width: 250px; /* Un poco menos que la caja para padding */
        margin-top: 14px;
        padding: 5px;
        font-size: 8pt;
        background: #f4f4f4;
        border: 1px solid #ccc;
        color: #333;
    }

    .tabla-bursal {
        width: 70%;
        margin: 50px auto 0 auto;
        border-collapse: collapse;
        font-size: 11pt;
        background: #ffffff;
        clear: both;
    }

    .tabla-bursal th, .tabla-bursal td {
        border: 1px solid #ffffff;
        padding: 5px 7px;
        text-align: center;
    }

    .tabla-bursal th {
        background: #d9d9d9;
        color: #000;
        font-weight: bold;
    }

    .tabla-bursal tbody tr:nth-child(odd) { background: #f5f5f5; }
    .tabla-bursal tbody tr:nth-child(even) { background: #ffffff; }
    .tabla-bursal .estado { text-align: left; padding-left: 10px; }
    
    /* Estilo para el texto del diagnóstico */
    .texto-diagnostico {
        font-size: 14pt; 
        text-align: justify; 
        line-height: 1.6;
        color: #000;
        margin-top: 20px;
    }
</style>
</head>
<body>

<table width="100%" style="border: none; margin-bottom: 10px;">
    <tr>
        <td align="right" style="border: none; font-size: 9pt; color: #777777;">
            ' . $fechaGeneracion . '
        </td>
    </tr>
</table>

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
    <p style="font-size: 10pt; color: #555;"><strong>Inicio de registro:</strong> ' . htmlspecialchars($tfecreghorainicioFormatted) . ' &nbsp;&nbsp;
       <strong>Fin de registro:</strong> ' . htmlspecialchars($tfecreghorafinFormatted) . '</p>
</div>
';

/* ===============================
   CONTENIDO POR SISTEMA (sin tabla N° Ave)
================================ */
$sistema_actual = '';
$niveles = [];
$col = 0;

foreach ($registros as $reg) {

    if ($reg['tsistema'] !== $sistema_actual) {

        if ($sistema_actual !== '') {
            $html .= '</tr></table>';
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

    // === FOTOS ===
    $imgHtml = 'Sin evidencia';
    
    if (!empty($reg['evidencia'])) {
        $rutas = array_filter(explode(',', $reg['evidencia']), 'trim');
        
        if (count($rutas) > 0) {
            $imgHtml = ''; 
            $estiloImg = 'width:240px; margin-bottom: 5px; border: 1px solid #ccc;';

            foreach ($rutas as $ruta) {
                $path = realpath(__DIR__ . '/../../' . $ruta);
                if ($path && file_exists($path)) {
                    $imgHtml .= '<img src="file:///' . $path . '" style="' . $estiloImg . '"> <br>'; 
                }
            }
        }
    }

    // === TABLA PORCENTAJES ===
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

    // === OBSERVACIÓN: si no tiene, mostrar "sin observaciones" en cursiva ===
    $obsHtml = '';
    if (!empty($reg['tobservacion']) && trim($reg['tobservacion']) !== '') {
        $obsHtml = '<div class="observacion-box"><strong>Obs:</strong> ' . nl2br(htmlspecialchars($reg['tobservacion'])) . '</div>';
    } else {
        $obsHtml = '<div class="observacion-box"><strong>Obs:</strong> <em>sin observaciones</em></div>';
    }

    $html .= '
    <td width="33%" align="center" valign="top">
        <div class="nivel">' . htmlspecialchars($reg['tnivel']) . '</div>
        <table class="img-box"><tr><td align="center" valign="middle">' . $imgHtml . '</td></tr></table>
        <table class="porc-box">' . $porcHtml . '</table>
        ' . $obsHtml . ' </td>';

    $col++;
}

/* CIERRES FINALES TABLAS */
$html .= '</tr></table>';

// DIAGNÓSTICO PRESUNTIVO: nueva hoja, mismo estilo que EVALUACIÓN FÍSICA; si no está presente, "sin diagnóstico presuntivo"
$html .= '<h2>DIAGNÓSTICO PRESUNTIVO</h2>';
$html .= '<div class="texto-diagnostico">';
if (!empty($diagnosticoPresuntivo) && trim($diagnosticoPresuntivo) !== '') {
    $html .= nl2br(htmlspecialchars($diagnosticoPresuntivo));
} else {
    $html .= '<em>Sin diagnóstico presuntivo</em>';
}
$html .= '</div>';

$html .= '</body></html>';

$mpdf->WriteHTML($html);
$mpdf->Output('Reporte_Necropsia_' . $numreg . '.pdf', 'I');
?>