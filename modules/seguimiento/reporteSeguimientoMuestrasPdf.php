<?php
require_once  '../../vendor/autoload.php'; // Composer autoload para mPDF

include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!isset($_GET['codEnvio'])) {
    die('Código de envío no proporcionado');
}

$codEnvio = $_GET['codEnvio'];

// === 1. Datos de cabecera ===
$cab = $conn->prepare("
    SELECT 
        codEnvio, fecEnvio, horaEnvio, nomLab, nomEmpTrans,
        usuarioRegistrador, usuarioResponsable, autorizadoPor,
        fechaHoraRegistro, estado
    FROM san_fact_solicitud_cab
    WHERE codEnvio = ?
");
$cab->bind_param("s", $codEnvio);
$cab->execute();
$cabResult = $cab->get_result();
$cabecera = $cabResult->fetch_assoc();

if (!$cabecera) {
    die('Solicitud no encontrada');
}

// Formatear fechas
$fechaEnvio = date('d/m/Y', strtotime($cabecera['fecEnvio']));
$horaEnvio = date('H:i', strtotime($cabecera['horaEnvio']));
$fechaRegistro = date('d/m/Y H:i', strtotime($cabecera['fechaHoraRegistro']));

// === 2. Detalle de solicitud ===
$det = $conn->query("
    SELECT 
        posSolicitud,
        codRef,
        fecToma,
        numMuestras,
        GROUP_CONCAT(DISTINCT nomMuestra SEPARATOR ', ') AS muestras,
        GROUP_CONCAT(DISTINCT nomAnalisis SEPARATOR ', ') AS analisis,
        GROUP_CONCAT(DISTINCT obs SEPARATOR ' | ') AS observaciones
    FROM san_fact_solicitud_det
    WHERE codEnvio = '$codEnvio'
    GROUP BY posSolicitud, codRef, fecToma, numMuestras
    ORDER BY posSolicitud
");

// === 3. Resultados Cualitativos ===
$cuali = $conn->query("
    SELECT 
        posSolicitud,
        codRef,
        analisis_nombre,
        resultado,
        obs,
        fechaLabRegistro,
        usuarioRegistrador
    FROM san_fact_resultado_analisis
    WHERE codEnvio = '$codEnvio'
    ORDER BY posSolicitud, analisis_nombre
");

// === 4. Resultados Cuantitativos (Pollo BB Adulto) ===
$cuanti = $conn->query("
    SELECT 
        fecha_toma_muestra,
        edad_aves,
        planta_incubacion,
        lote,
        codigo_granja,
        numero_galpon,
        enfermedad,
        gmean,
        desviacion_estandar AS sd,
        cv,
        count_muestras,
        titulo_promedio
    FROM san_analisis_pollo_bb_adulto
    WHERE codigo_envio = '$codEnvio'
    ORDER BY fecha_toma_muestra DESC
");

// === Generar PDF con mPDF ===
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 20,
    'margin_bottom' => 20,
    'margin_header' => 10,
    'margin_footer' => 10,
    'tempDir' =>  '../../pdf_tmp'
]);

// === Estilos CSS (neutros, profesionales, sin azules) ===
$stylesheet = '
<style>
    body { 
        font-family: DejaVuSans, sans-serif; 
        font-size: 10pt; 
        color: #333; 
        line-height: 1.6;
    }
    h1 { 
        font-size: 16pt; 
        text-align: center; 
        color: #1f2937; 
        margin-bottom: 10px; 
        font-weight: bold;
    }
    .subtitle { 
        text-align: center; 
        color: #4b5563; 
        font-size: 12pt; 
        margin-bottom: 40px; 
        font-weight: 600;
    }
    .section-title { 
        font-size: 14pt; 
        color: #1f2937; 
        border-bottom: 2px solid #e5e7eb; 
        padding-bottom: 8px; 
        margin: 40px 0 20px 0; 
        font-weight: bold;
    }
    .summary-list {
        max-width: 600px;
        margin: 0 auto 40px auto;
        font-size: 11pt;
    }
    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px dotted #d1d5db;
    }
    .summary-label {
        font-weight: bold;
        color: #374151;
        min-width: 180px;
    }
    .summary-value {
        color: #1f2937;
        text-align: right;
        flex: 1;
    }
    table.data-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 20px 0; 
        font-size: 9pt; 
    }
    table.data-table th { 
        background: #374151; 
        color: white; 
        padding: 12px; 
        text-align: left; 
        font-weight: 600;
    }
    table.data-table td { 
        padding: 10px 12px; 
        border-bottom: 1px solid #e5e7eb; 
        vertical-align: top; 
    }
    table.data-table tr:nth-child(even) { 
        background: #f9fafb; 
    }

    table.info-table .value-cell {
        padding-left: 140px !important; /* ← Esto crea el espacio a la derecha del label */
        padding-bottom: 10px !important;
    }

    table.info-table .label-cell {
        padding-bottom: 10px !important; /* ← Esto crea el espacio a la derecha del label */
    }

    .text-center { text-align: center; }
    .badge-pendiente { 
        background: #fef3c7; 
        color: #92400e; 
        padding: 6px 16px; 
        border-radius: 20px; 
        font-weight: bold; 
        font-size: 10pt; 
    }
    .badge-completado { 
        background: #d1fae5; 
        color: #065f46; 
        padding: 6px 16px; 
        border-radius: 20px; 
        font-weight: bold; 
        font-size: 10pt; 
    }
    .footer { 
        text-align: center; 
        color: #6b7280; 
        font-size: 8pt; 
        margin-top: 50px; 
        padding-top: 20px; 
        border-top: 1px solid #e5e7eb;
    }
</style>
';
$mpdf->WriteHTML($stylesheet);

// === Contenido del PDF ===
$html = "
<h1>Reporte Seguimiento de Muestras</h1>

<div class='subtitle'>
    <strong>Resumen de Envío</strong><br>  
</div>

<!-- Resumen de envío -->
<table class='info-table'>
    <tr>
        <td class='label-cell'>Codigo de envio</td>
        <td class='value-cell'>: {$cabecera['codEnvio']}</td>
    </tr>
    <tr>
        <td class='label-cell'>Fecha creacion</td>
        <td class='value-cell'>: " . date('d/m/Y ') . " </td>
    </tr>
    <tr>
        <td class='label-cell'>Laboratorio</td>
        <td class='value-cell'>: {$cabecera['nomLab']}</td>
    </tr>
    <tr>
        <td class='label-cell'>Transporte</td>
        <td class='value-cell'>: {$cabecera['nomEmpTrans']}</td>
    </tr>
    <tr>
        <td class='label-cell'>Fecha y Hora de Envío</td>
        <td class='value-cell'>: $fechaEnvio a las $horaEnvio</td>
    </tr>
    <tr>
        <td class='label-cell'>Registrado por</td>
        <td class='value-cell'>: {$cabecera['usuarioRegistrador']}</td>
    </tr>
    <tr>
        <td class='label-cell'>Responsable</td>
        <td class='value-cell'>: {$cabecera['usuarioResponsable']}</td>
    </tr>
    <tr>
        <td class='label-cell'>Autorizado por</td>
        <td class='value-cell'>: {$cabecera['autorizadoPor']}</td>
    </tr>
    <tr>
        <td class='label-cell'>Estado General</td>
        <td class='value-cell'>: {$cabecera['estado']} </td>
    </tr>
</table>

<div class='section-title'>Detalle de Solicitud</div>
<table class='data-table'>
    <thead>
        <tr>
            <th>N° Solicitud.</th>
            <th>Cód. Ref</th>
            <th>Fecha Toma</th>
            <th>N° Muestras</th>
            <th>Muestras</th>
            <th>Análisis Solicitados</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>";

while ($row = $det->fetch_assoc()) {
    $fechaToma = $row['fecToma'] ? date('d/m/Y', strtotime($row['fecToma'])) : '-';
    $observaciones = $row['observaciones'] ?: '-';
    $html .= "
        <tr>
            <td class='text-center'>{$row['posSolicitud']}</td>
            <td>{$row['codRef']}</td>
            <td class='text-center'>$fechaToma</td>
            <td class='text-center'>{$row['numMuestras']}</td>
            <td>{$row['muestras']}</td>
            <td>{$row['analisis']}</td>
            <td>$observaciones</td>
        </tr>";
}

$html .= "</tbody></table>";

// === Resultados Cualitativos ===
if ($cuali->num_rows > 0) {
    $html .= "<div class='section-title'>Resultados Cualitativos</div><table class='data-table'>
        <thead>
            <tr>
                <th>N° Solicitud.</th>
                <th>Cód. Ref</th>
                <th>Análisis</th>
                <th>Resultado</th>
                <th>Observaciones</th>
                <th>Fecha Lab</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>";

    while ($row = $cuali->fetch_assoc()) {
        $fechaLab = $row['fechaLabRegistro'] ? date('d/m/Y', strtotime($row['fechaLabRegistro'])) : '-';
        $obs = $row['obs'] ?: '-';
        $html .= "
            <tr>
                <td class='text-center'>{$row['posSolicitud']}</td>
                <td>{$row['codRef']}</td>
                <td>{$row['analisis_nombre']}</td>
                <td>{$row['resultado']}</td>
                <td>$obs</td>
                <td class='text-center'>$fechaLab</td>
                <td>{$row['usuarioRegistrador']}</td>
            </tr>";
    }
    $html .= "</tbody></table>";
} else {
    $html .= "<p style='color:#6b7280; font-style:italic; margin:20px 0;'>No hay resultados cualitativos registrados.</p>";
}

// === Resultados Cuantitativos ===
if ($cuanti->num_rows > 0) {
    $html .= "<div class='section-title'>Resultados Cuantitativos (Pollo BB/Adulto)</div><table class='data-table'>
        <thead>
            <tr>
                <th>Fecha Toma</th>
                <th>Edad</th>
                <th>Planta</th>
                <th>Lote</th>
                <th>Granja</th>
                <th>Galpón</th>
                <th>Enfermedad</th>
                <th>GMean</th>
                <th>SD</th>
                <th>CV</th>
                <th>Count</th>
                <th>Título Promedio</th>
            </tr>
        </thead>
        <tbody>";

    while ($row = $cuanti->fetch_assoc()) {
        $fechaToma = $row['fecha_toma_muestra'] ? date('d/m/Y', strtotime($row['fecha_toma_muestra'])) : '-';
        $html .= "
            <tr>
                <td class='text-center'>$fechaToma</td>
                <td class='text-center'>{$row['edad_aves']}</td>
                <td>{$row['planta_incubacion']}</td>
                <td>{$row['lote']}</td>
                <td>{$row['codigo_granja']}</td>
                <td class='text-center'>{$row['numero_galpon']}</td>
                <td>{$row['enfermedad']}</td>
                <td class='text-center'>{$row['gmean']}</td>
                <td class='text-center'>{$row['sd']}</td>
                <td class='text-center'>{$row['cv']}</td>
                <td class='text-center'>{$row['count_muestras']}</td>
                <td class='text-center'>{$row['titulo_promedio']}</td>
            </tr>";
    }
    $html .= "</tbody></table>";
} else {
    $html .= "<p style='color:#6b7280; font-style:italic; margin:20px 0;'>No hay resultados cuantitativos registrados.</p>";
}

$html .= "<div class='footer'>
    Reporte generado el " . date('d/m/Y \a \l\a\s H:i') . " • Sistema de Sanidad
</div>";

$mpdf->WriteHTML($html);

$mpdf->Output("Reporte_{$codEnvio}.pdf", 'I');
exit;
