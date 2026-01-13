<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

date_default_timezone_set('America/Lima');

ob_start();
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
ob_end_clean();

if (!$conn) {
    die('Error de conexión a la base de datos');
}

// Obtener parámetros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '1';
$numreg = isset($_GET['numreg']) ? $_GET['numreg'] : '';
$granja = isset($_GET['granja']) ? $_GET['granja'] : '';
$galpon = isset($_GET['galpon']) ? $_GET['galpon'] : '';
$fectra = isset($_GET['fectra']) ? $_GET['fectra'] : '';

if (empty($numreg) || empty($granja) || empty($galpon) || empty($fectra)) {
    die('Parámetros incompletos');
}

$sql = "SELECT * 
FROM t_regnecropsia 
WHERE tnumreg = ? AND tgranja = ? AND tgalpon = ? AND tfectra = ?
ORDER BY 
    tdate DESC,
    tnumreg DESC,
    tgranja ASC,
    tid ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Error preparando consulta: ' . $conn->error);
}

$stmt->bind_param("isss", $numreg, $granja, $galpon, $fectra);
$stmt->execute();
$result = $stmt->get_result();

$registros = [];
while ($row = $result->fetch_assoc()) {
    $registros[] = $row;
}

$stmt->close();
$conn->close();

if (empty($registros)) {
    die('No se encontraron registros');
}

$cabecera = $registros[0];

require_once '../../vendor/autoload.php'; 

use Mpdf\Mpdf;

try {
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'P',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20,
        'tempDir' => __DIR__ . '/../../pdf_tmp',
    ]);

    $html = '';
    
    if ($tipo == '1') {
        $html = _generarReporte1($cabecera, $registros);
    } else {
      
        $html = _generarReportePDF($cabecera, $registros);
    }

    $mpdf->WriteHTML($html);
    $mpdf->Output('reporte_necropsia_' . $numreg . '_' . $tipo . '.pdf', 'I');
    
} catch (Exception $e) {
    die('Error generando PDF: ' . $e->getMessage());
}

function _generarReporte1($cabecera, $registros) {
    $html = '<html><head><meta charset="UTF-8"><style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .header-title {
            background-color: #e6f2ff; /* ¡Celeste bonito para la cabecera principal! */
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            padding: 8px;
            border: 1px solid #000; /* Borde negro completo */
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 10px;
            border: 1px solid #000;
            border-top: none;
        }
        .info-label {
            font-weight: bold;
            width: 15%;
        }
        .info-value {
            width: 30%;
            padding-left: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
            border-top: 1px solid #000;
        }
        th {
            background-color: #e6f2ff;
            padding: 5px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
        }
        td {
            padding: 5px;
            border: 1px solid #000;
            vertical-align: top;
        }
        .nivel-cell {
            background-color: #e6f2ff;
            font-weight: bold;
            text-align: center;
            width: 15%;
        }
        .param-cell {
            width: 35%;
        }
        .porc-cell {
            text-align: center;
            width: 10%;
        }
        .obs-cell {
            width: 40%;
        }
    </style></head><body>';

    $html .= '<div class="header-title">REPORTE DE NECROPSIA</div>';

    $html .= '<div class="info-row">';
    $html .= '<div class="info-label">Fecha:</div><div class="info-value">' . htmlspecialchars($cabecera['tdate'] ?? '') . '</div>';
    $html .= '<div class="info-label">Granja:</div><div class="info-value">' . htmlspecialchars($cabecera['tgranja'] ?? '') . '</div>';
    $html .= '<div class="info-label">Campaña:</div><div class="info-value">' . htmlspecialchars($cabecera['tcampania'] ?? '') . '</div>';
    $html .= '<div class="info-label">Galpón:</div><div class="info-value">' . htmlspecialchars($cabecera['tgalpon'] ?? '') . '</div>';
    $html .= '<div class="info-label">Edad:</div><div class="info-value">' . htmlspecialchars($cabecera['tedad'] ?? '') . ' días</div>';
    $html .= '</div>';

    // Agrupar por sistema
    $porSistema = [];
    foreach ($registros as $reg) {
        $sistema = $reg['tsistema'] ?? '';
        if (!isset($porSistema[$sistema])) {
            $porSistema[$sistema] = [];
        }
        $porSistema[$sistema][] = $reg;
    }

    foreach ($porSistema as $sistema => $regs) {
        if (empty(trim($sistema))) continue;

        $html .= '<table>';
        
        $html .= '<tr>';
        $html .= '<th colspan="4" style="background-color: #e6f2ff; font-weight: bold; text-align: left; padding: 5px 8px; border: 1px solid #000;">' . htmlspecialchars(strtoupper($sistema)) . '</th>';
        $html .= '</tr>';
        
        $html .= '<tr>';
        $html .= '<th>Nivel</th>';
        $html .= '<th>Parámetro</th>';
        $html .= '<th>%</th>';
        $html .= '<th>Observaciones</th>';
        $html .= '</tr>';

        $porNivel = [];
        foreach ($regs as $r) {
            $nivel = $r['tnivel'] ?? '';
            if (!isset($porNivel[$nivel])) {
                $porNivel[$nivel] = [];
            }
            $porNivel[$nivel][] = $r;
        }

        foreach ($porNivel as $nivel => $items) {
            $rowspan = count($items);
            $primero = true;
            foreach ($items as $item) {
                $html .= '<tr>';
                if ($primero) {
                    $html .= '<td class="nivel-cell" rowspan="' . $rowspan . '">' . htmlspecialchars(strtoupper($nivel)) . '</td>';
                    $primero = false;
                }
                $html .= '<td class="param-cell">' . htmlspecialchars($item['tparametro'] ?? '') . '</td>';
                $html .= '<td class="porc-cell">' . htmlspecialchars($item['tporcentajetotal'] ?? '0') . '%</td>';
                $html .= '<td class="obs-cell">' . nl2br(htmlspecialchars($item['tobservacion'] ?? '')) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';
    }

    $html .= '</body></html>';
    return $html;
}


function _generarReportePDF($cabecera, $registros) {
   
    return '';
}
?>
