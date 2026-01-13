<?php
// Evitar mostrar warnings/errors
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

// Obtener parámetros del POST o GET
$cencos = isset($_POST['cencos']) ? $_POST['cencos'] : (isset($_GET['cencos']) ? $_GET['cencos'] : 'todos');
$galpones = isset($_POST['galpones']) ? $_POST['galpones'] : (isset($_GET['galpones']) ? $_GET['galpones'] : 'todos');
$fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : (isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '');
$fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : (isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '');
$formato = isset($_POST['formato']) ? $_POST['formato'] : (isset($_GET['formato']) ? $_GET['formato'] : 'pdf');

// Si viene como JSON
$dataJson = file_get_contents('php://input');
if (!empty($dataJson)) {
    $input = json_decode($dataJson, true);
    if ($input) {
        $cencos = $input['cencos'] ?? $cencos;
        $galpones = $input['galpones'] ?? $galpones;
        $fecha_inicio = $input['fecha_inicio'] ?? $fecha_inicio;
        $fecha_fin = $input['fecha_fin'] ?? $fecha_fin;
        $formato = $input['formato'] ?? $formato;
    }
}

// Validar fechas
if (empty($fecha_inicio) || empty($fecha_fin)) {
    die('Debe especificar fecha de inicio y fecha de fin');
}

// Convertir cencos y galpones a arrays si vienen como string
if (is_string($cencos) && $cencos !== 'todos') {
    $cencos = explode(',', $cencos);
}
if (is_string($galpones) && $galpones !== 'todos') {
    $galpones = explode(',', $galpones);
}

// Construir consulta SQL
$sql = "SELECT DISTINCT
    tcencos, tgranja, tgalpon, tedad, tsistema, tnivel, tparametro, 
    tporcentajetotal, tobservacion, tdate, tfectra
FROM t_regnecropsia 
WHERE tdate >= ? AND tdate <= ?";

$params = [];
$types = "ss";
$params[] = $fecha_inicio;
$params[] = $fecha_fin;

// Filtrar por CENCOS
if ($cencos !== 'todos' && is_array($cencos) && !empty($cencos)) {
    $placeholders = implode(',', array_fill(0, count($cencos), '?'));
    $sql .= " AND tgranja IN ($placeholders)";
    $types .= str_repeat('s', count($cencos));
    foreach ($cencos as $cenco) {
        $params[] = $cenco;
    }
}

// Filtrar por galpones
if ($galpones !== 'todos' && is_array($galpones) && !empty($galpones)) {
    $placeholders = implode(',', array_fill(0, count($galpones), '?'));
    $sql .= " AND tgalpon IN ($placeholders)";
    $types .= str_repeat('s', count($galpones));
    foreach ($galpones as $galpon) {
        $params[] = $galpon;
    }
}

$sql .= " ORDER BY tcencos, tgalpon, tsistema, tnivel, tparametro";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Error preparando consulta: ' . $conn->error);
}

// Bind parameters
$bindParams = array_merge(array($types), $params);
$refs = array();
foreach ($bindParams as $key => $value) {
    $refs[$key] = &$bindParams[$key];
}
call_user_func_array(array($stmt, 'bind_param'), $refs);

$stmt->execute();
$result = $stmt->get_result();

$registros = [];
while ($row = $result->fetch_assoc()) {
    $registros[] = $row;
}

$stmt->close();

// Obtener TODAS las combinaciones únicas de sistema/nivel/parámetro (sin filtrar por fechas)
$sqlTodosNiveles = "
    SELECT DISTINCT tsistema, tnivel, tparametro 
    FROM t_regnecropsia 
    ORDER BY 
        CASE 
            WHEN LOWER(tsistema) LIKE '%inmunol%' THEN 1
            WHEN LOWER(tsistema) LIKE '%digestiv%' THEN 2
            WHEN LOWER(tsistema) LIKE '%respirat%' THEN 3
            WHEN LOWER(tsistema) LIKE '%evaluaci%' AND LOWER(tsistema) LIKE '%físic%' THEN 4
            ELSE 5
        END,
        tsistema,
        tnivel,
        tparametro
";
$resultTodosNiveles = $conn->query($sqlTodosNiveles);
$todosNiveles = [];
while ($row = $resultTodosNiveles->fetch_assoc()) {
    $sistema = $row['tsistema'] ?? '';
    $nivel = $row['tnivel'] ?? '';
    $parametro = $row['tparametro'] ?? '';
    
    if (!isset($todosNiveles[$sistema])) {
        $todosNiveles[$sistema] = [];
    }
    if (!isset($todosNiveles[$sistema][$nivel])) {
        $todosNiveles[$sistema][$nivel] = [];
    }
    if (!in_array($parametro, $todosNiveles[$sistema][$nivel])) {
        $todosNiveles[$sistema][$nivel][] = $parametro;
    }
}

$conn->close();

if (empty($registros)) {
    die('No se encontraron registros para los filtros especificados');
}

// Agrupar datos por cenco y galpón
$datosAgrupados = [];
$cencosUnicos = [];
foreach ($registros as $reg) {
    $key = $reg['tcencos'] . '_' . $reg['tgalpon'];
    if (!isset($datosAgrupados[$key])) {
        $datosAgrupados[$key] = [
            'tcencos' => $reg['tcencos'],
            'tgranja' => $reg['tgranja'],
            'tgalpon' => $reg['tgalpon'],
            'tedad' => $reg['tedad'],
            'registros' => []
        ];
    }
    $datosAgrupados[$key]['registros'][] = $reg;
    
    // Agrupar cencos únicos
    if (!isset($cencosUnicos[$reg['tcencos']])) {
        $cencosUnicos[$reg['tcencos']] = $reg['tgranja'];
    }
}

// Determinar si es comparativo entre cencos (más de un cenco)
$esComparativoCencos = count($cencosUnicos) > 1;

// Generar reporte según formato
if ($formato === 'excel') {
    _generarExcel($datosAgrupados, $fecha_inicio, $fecha_fin, $cencosUnicos, $esComparativoCencos, $todosNiveles);
} else {
    _generarPDF($datosAgrupados, $fecha_inicio, $fecha_fin, $cencosUnicos, $esComparativoCencos, $todosNiveles);
}

function _generarPDF($datosAgrupados, $fecha_inicio, $fecha_fin, $cencosUnicos, $esComparativoCencos, $todosNiveles) {
    require_once '../../vendor/autoload.php';
    
    // Obtener lista de galpones con información completa (agrupados por CENCO)
    $galponesUnicos = [];
    $galponesPorCenco = [];
    foreach ($datosAgrupados as $key => $grupo) {
        $galponKey = $grupo['tgalpon'];
        if (!isset($galponesUnicos[$galponKey])) {
            $galponesUnicos[$galponKey] = [
                'tcencos' => $grupo['tcencos'],
                'tgranja' => $grupo['tgranja'],
                'tedad' => $grupo['tedad']
            ];
        }
        // Agrupar por CENCO para la leyenda
        $cenco = $grupo['tcencos'];
        if (!isset($galponesPorCenco[$cenco])) {
            $galponesPorCenco[$cenco] = [
                'granja' => $grupo['tgranja'],
                'galpones' => []
            ];
        }
        if (!in_array($galponKey, $galponesPorCenco[$cenco]['galpones'])) {
            $galponesPorCenco[$cenco]['galpones'][] = $galponKey;
        }
    }
    // Ordenar galpones
    ksort($galponesUnicos);

    $numColumnas = 3 + (2 * count($galponesUnicos));
    $anchoMM = max(297, 60 + ($numColumnas * 28));

    // === Logo ===
    $logoPath = __DIR__ . '/../../logo.png';
    $logo = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
    }

    try {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [$anchoMM, 210], // [ancho, alto] → landscape si ancho > 210
            'orientation' => 'L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 30,
            'margin_bottom' => 10,
            'tempDir' => __DIR__ . '/../../pdf_tmp',
        ]);

        // === Cabecera conjunta ===
        $headerHTML = '<table width="100%" style="border-collapse: collapse; border: 1px solid #000; margin-top: 20px; page-break-after: avoid;">';
        $headerHTML .= '<tr>';
        if (!empty($logo)) {
            $headerHTML .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap;">';
            $headerHTML .= $logo . ' GRANJA RINCONADA DEL SUR S.A.';
            $headerHTML .= '</td>';
        } else {
            $headerHTML .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff;"></td>';
        }
        $headerHTML .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #e6f2ff; color: #000; font-weight: bold; font-size: 14px;">';
        $headerHTML .= 'REPORTE COMPARATIVO DE NECROPSIA';
        $headerHTML .= '</td>';
        $headerHTML .= '<td style="width: 20%; background-color: #fff;"></td>';
        $headerHTML .= '</tr>';
        $headerHTML .= '</table>';
        
        $mpdf->SetHTMLHeader($headerHTML, 'O');

        $html = _generarHTMLReporte(
            $datosAgrupados, 
            $fecha_inicio, 
            $fecha_fin, 
            $cencosUnicos, 
            $esComparativoCencos, 
            $todosNiveles,
            $galponesUnicos,
            $galponesPorCenco  // pasamos la agrupación por CENCO
        );

        $mpdf->WriteHTML($html);
        $mpdf->Output('reporte_comparativo_' . date('Ymd_His') . '.pdf', 'I');
        
    } catch (Exception $e) {
        die('Error generando PDF: ' . $e->getMessage());
    }
}

function _generarExcel($datosAgrupados, $fecha_inicio, $fecha_fin, $cencosUnicos, $esComparativoCencos, $todosNiveles) {
    require_once '../../vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Obtener lista de galpones únicos para las columnas
    $galponesUnicos = [];
    foreach ($datosAgrupados as $key => $grupo) {
        $galponKey = $grupo['tgalpon'];
        if (!isset($galponesUnicos[$galponKey])) {
            $galponesUnicos[$galponKey] = [
                'tcencos' => $grupo['tcencos'],
                'tgranja' => $grupo['tgranja'],
                'tedad' => $grupo['tedad']
            ];
        }
    }
    
    // Estilos
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4FC3F7'] // Celeste
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    
    $cellStyle = [
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    
    $row = 1;
    
    // Título
    $numCols = count($galponesUnicos) * 2 + 3; // 3 columnas base + 2 por cada galpón
    $lastCol = $sheet->getCellByColumnAndRow($numCols, 1)->getColumn();
    $sheet->setCellValue('A' . $row, 'REPORTE COMPARATIVO DE NECROPSIA');
    $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
    $sheet->getStyle('A' . $row)->applyFromArray([
        'font' => ['bold' => true, 'size' => 16],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    $row++;
    
    // Fechas
    $sheet->setCellValue('A' . $row, 'Período: ' . $fecha_inicio . ' - ' . $fecha_fin);
    $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
    $row++;
    
    // Cabecera de CENCOS si es comparativo entre cencos
    if ($esComparativoCencos) {
        $sheet->setCellValue('A' . $row, 'CENCOS COMPARADOS:');
        $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4FC3F7']
            ]
        ]);
        $row++;
        
        $col = 1;
        foreach ($cencosUnicos as $cenco => $granja) {
            $sheet->setCellValueByColumnAndRow($col++, $row, 'Cenco: ' . $cenco);
            $sheet->setCellValueByColumnAndRow($col++, $row, 'Granja: ' . $granja);
        }
        $row += 2;
    } else {
        $row++;
    }
    
    // Usar todos los niveles (sin filtrar por datos existentes)
    $sistemas = $todosNiveles;
    
    // Obtener lista de galpones únicos para las columnas
    $galponesUnicos = [];
    foreach ($datosAgrupados as $key => $grupo) {
        $galponKey = $grupo['tgalpon'];
        if (!isset($galponesUnicos[$galponKey])) {
            $galponesUnicos[$galponKey] = [
                'tcencos' => $grupo['tcencos'],
                'tgranja' => $grupo['tgranja'],
                'tedad' => $grupo['tedad']
            ];
        }
    }
    
    // Generar cabecera - REORGANIZADA: Cenco/Granja/Edad en primera fila
    $col = 1;
    $sheet->setCellValueByColumnAndRow($col++, $row, 'Sistema');
    $sheet->setCellValueByColumnAndRow($col++, $row, 'Nivel');
    $sheet->setCellValueByColumnAndRow($col++, $row, 'Parámetro');
    
    // Fila 1: Información de cada galpón (Cenco, Granja, Edad) - COLSPAN 2
    foreach ($galponesUnicos as $galpon => $info) {
        $sheet->setCellValueByColumnAndRow($col, $row, 'Cenco: ' . $info['tcencos'] . "\nGranja: " . $info['tgranja'] . "\nEdad: " . $info['tedad']);
        $sheet->mergeCells($sheet->getCellByColumnAndRow($col, $row)->getCoordinate() . ':' . $sheet->getCellByColumnAndRow($col + 1, $row)->getCoordinate());
        $col += 2;
    }
    
    // Aplicar estilo a la cabecera
    $headerRange = 'A' . $row . ':' . $sheet->getCellByColumnAndRow($col - 1, $row)->getCoordinate();
    $sheet->getStyle($headerRange)->applyFromArray($headerStyle);
    $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true);
    $row++;
    
    // Fila 2: Galpón (colspan 2)
    $col = 1;
    $sheet->setCellValueByColumnAndRow($col++, $row, '');
    $sheet->setCellValueByColumnAndRow($col++, $row, '');
    $sheet->setCellValueByColumnAndRow($col++, $row, '');
    
    foreach ($galponesUnicos as $galpon => $info) {
        $sheet->setCellValueByColumnAndRow($col, $row, 'Galpón ' . $galpon);
        $sheet->mergeCells($sheet->getCellByColumnAndRow($col, $row)->getCoordinate() . ':' . $sheet->getCellByColumnAndRow($col + 1, $row)->getCoordinate());
        $col += 2;
    }
    
    $subHeaderRange = 'A' . $row . ':' . $sheet->getCellByColumnAndRow($col - 1, $row)->getCoordinate();
    $sheet->getStyle($subHeaderRange)->applyFromArray($headerStyle);
    $row++;
    
    // Fila 3: % y Observaciones
    $col = 1;
    $sheet->setCellValueByColumnAndRow($col++, $row, '');
    $sheet->setCellValueByColumnAndRow($col++, $row, '');
    $sheet->setCellValueByColumnAndRow($col++, $row, '');
    
    foreach ($galponesUnicos as $galpon => $info) {
        $sheet->setCellValueByColumnAndRow($col++, $row, '%');
        $sheet->setCellValueByColumnAndRow($col++, $row, 'Observaciones');
    }
    
    $subHeaderRange = 'A' . $row . ':' . $sheet->getCellByColumnAndRow($col - 1, $row)->getCoordinate();
    $sheet->getStyle($subHeaderRange)->applyFromArray($headerStyle);
    $row++;
    
    // Calcular total de filas por sistema para rowspan
    $filasPorSistema = [];
    foreach ($sistemas as $sistema => $niveles) {
        $totalFilas = 0;
        foreach ($niveles as $nivel => $parametros) {
            $totalFilas += count($parametros);
        }
        $filasPorSistema[$sistema] = $totalFilas;
    }
    
    // Datos por sistema
    foreach ($sistemas as $sistema => $niveles) {
        $firstRowSistema = $row;
        $totalFilasSistema = $filasPorSistema[$sistema];
        
        foreach ($niveles as $nivel => $parametros) {
            foreach ($parametros as $parametro) {
                $col = 1;
                
                // Sistema solo en la primera fila
                if ($row == $firstRowSistema) {
                    $sheet->setCellValueByColumnAndRow($col++, $row, $sistema);
                    $sheet->mergeCells(
                        $sheet->getCellByColumnAndRow(1, $row)->getCoordinate() . ':' . 
                        $sheet->getCellByColumnAndRow(1, $row + $totalFilasSistema - 1)->getCoordinate()
                    );
                    $sheet->getStyle($sheet->getCellByColumnAndRow(1, $row)->getCoordinate())->applyFromArray([
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E3F2FD']
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => '000000']
                            ]
                        ]
                    ]);
                } else {
                    $col++;
                }
                
                $sheet->setCellValueByColumnAndRow($col++, $row, $nivel);
                $sheet->setCellValueByColumnAndRow($col++, $row, $parametro);
                
                // Buscar datos para cada galpón
                foreach ($galponesUnicos as $galpon => $info) {
                    $porcentaje = '';
                    $observacion = '';
                    
                    // Buscar en los datos agrupados
                    foreach ($datosAgrupados as $grupo) {
                        if ($grupo['tgalpon'] == $galpon) {
                            foreach ($grupo['registros'] as $reg) {
                                if (($reg['tsistema'] ?? '') == $sistema &&
                                    ($reg['tnivel'] ?? '') == $nivel &&
                                    ($reg['tparametro'] ?? '') == $parametro) {
                                    $porcentaje = $reg['tporcentajetotal'] ?? '';
                                    $observacion = $reg['tobservacion'] ?? '';
                                    break 2;
                                }
                            }
                        }
                    }
                    
                    $sheet->setCellValueByColumnAndRow($col++, $row, $porcentaje);
                    $sheet->setCellValueByColumnAndRow($col++, $row, $observacion);
                }
                
                $row++;
            }
        }
        $row++; // Espacio entre sistemas
    }
    
    // Aplicar estilos a todas las celdas de datos (desde la fila de datos, no desde la fila 4)
    $dataStartRow = 4; // Fila donde empiezan los datos (después de las cabeceras)
    $dataRange = 'A' . $dataStartRow . ':' . $sheet->getCellByColumnAndRow(count($galponesUnicos) * 2 + 3, $row - 1)->getCoordinate();
    $sheet->getStyle($dataRange)->applyFromArray($cellStyle);
    
    // Ajustar ancho de columnas
    $sheet->getColumnDimension('A')->setWidth(20);
    $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getColumnDimension('C')->setWidth(25);
    for ($i = 0; $i < count($galponesUnicos) * 2; $i++) {
        $colLetter = $sheet->getCellByColumnAndRow(4 + $i, 1)->getColumn();
        $sheet->getColumnDimension($colLetter)->setWidth(15);
    }
    
    // Enviar archivo
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="reporte_comparativo_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function _generarHTMLReporte($datosAgrupados, $fecha_inicio, $fecha_fin, $cencosUnicos, $esComparativoCencos, $todosNiveles, $galponesUnicos, $galponesPorCenco = []) {
    // === Logo ===
    $logoPath = __DIR__ . '/../../logo.png';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    }

    // === Estilos ===
    $html = '<html><head><meta charset="UTF-8"><style>
        @page {
            margin-top: 20mm;
            margin-bottom: 10mm;
            margin-left: 8mm;
            margin-right: 8mm;
        }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-title-cell {
            width: 75%;
            background-color: #dbeafe;
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            padding: 8px;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        .header-logo-cell {
            width: 25%;
            text-align: center;
            vertical-align: middle;
            padding: 6px;
            border: 1px solid #93c5fd;
        }
        .header-logo-cell img {
            width: 28px;
            height: auto;
        }
        .header-logo-cell .logo-text {
            font-size: 6.5pt;
            color: #334155;
            line-height: 1.2;
            margin-top: 3px;
        }
        .periodo {
            font-weight: bold;
            margin: 8px 0;
            font-size: 10pt;
            text-align: center;
            color: #1e3a8a;
        }
        .leyenda-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px;
            margin: 10px 0;
            font-size: 8.5pt;
        }
        .leyenda-title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #1e40af;
            font-size: 9.5pt;
        }
        .leyenda-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            font-size: 8.5pt;
        }
        .leyenda-table th {
            background-color: #e6f2ff;
            color: #1e40af;
            font-weight: bold;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #cbd5e1;
        }
        .leyenda-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .leyenda-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .leyenda-color-cell {
            width: 30px;
            text-align: center;
            padding: 4px !important;
            background-color: #fff;
        }
        .leyenda-color-box {
            width: 16px;
            height: 16px;
            border: 1px solid #000;
            display: block;
            margin: 0 auto;
        }
        .leyenda-cenco {
            font-weight: 600;
            color: #1e293b;
        }
        .leyenda-galpones {
            color: #475569;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 14px 0;
            font-size: 8.5pt;
            page-break-inside: auto;
        }
        .data-table th,
        .data-table td {
            padding: 5px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            text-align: center;
        }
        .data-table th {
            background-color: #f0f9ff;
            color: #0c4a6e;
            font-weight: bold;
            font-size: 8.5pt;
        }
        .sistema-header {
            background-color: #dbeafe;
            color: #1e40af;
            font-weight: bold;
            font-size: 11pt;
            text-align: center;
            padding: 6px;
            border: 1px solid #93c5fd;
            margin: 12px 0 6px 0;
            page-break-after: avoid;
        }
        .cenco-group-header {
            font-weight: bold;
            color: white;
            text-align: center;
            padding: 4px;
            font-size: 8.5pt;
        }
        .galpon-col-porc, .galpon-col-obs {
            font-size: 8pt;
        }
    </style></head><body>';

    // === Período (la cabecera ya está en SetHTMLHeader) ===
    $html .= '<div class="periodo">Período: ' . htmlspecialchars($fecha_inicio) . ' - ' . htmlspecialchars($fecha_fin) . '</div>';

    // === Colores por CENCO (generación dinámica) ===
    // Paleta base de colores distintivos (hex para compatibilidad)
    $coloresBaseCenco = [
        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
        '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#84cc16',
        '#6366f1', '#22c55e', '#fbbf24', '#f87171', '#a78bfa',
        '#34d399', '#fb923c', '#60a5fa', '#c084fc', '#f472b6',
        '#38bdf8', '#4ade80', '#fbbf24', '#fb7185', '#a5b4fc'
    ];
    
    $coloresPorCenco = [];
    $idxColor = 0;
    $numCencos = count($galponesPorCenco);
    
    // Función auxiliar para generar color hex desde HSL
    $hslToHex = function($h, $s, $l) {
        $h /= 360;
        $s /= 100;
        $l /= 100;
        $r = $l;
        $g = $l;
        $b = $l;
        $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
        if ($v > 0) {
            $m = $l + $l - $v;
            $sv = ($v - $m) / $v;
            $h *= 6.0;
            $sextant = floor($h);
            $fract = $h - $sextant;
            $vsf = $v * $sv * $fract;
            $mid1 = $m + $vsf;
            $mid2 = $v - $vsf;
            switch ($sextant) {
                case 0: $r = $v; $g = $mid1; $b = $m; break;
                case 1: $r = $mid2; $g = $v; $b = $m; break;
                case 2: $r = $m; $g = $v; $b = $mid1; break;
                case 3: $r = $m; $g = $mid2; $b = $v; break;
                case 4: $r = $mid1; $g = $m; $b = $v; break;
                case 5: $r = $v; $g = $m; $b = $mid2; break;
            }
        }
        $r = round($r * 255);
        $g = round($g * 255);
        $b = round($b * 255);
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    };
    
    // Asignar colores a cada CENCO
    foreach (array_keys($galponesPorCenco) as $cenco) {
        if ($idxColor < count($coloresBaseCenco)) {
            $coloresPorCenco[$cenco] = $coloresBaseCenco[$idxColor];
        } else {
            // Generar color dinámicamente usando HSL (convertido a hex)
            $hue = ($idxColor * 137.508) % 360; // Golden angle para distribución uniforme
            $saturation = 55 + (($idxColor % 4) * 5); // Entre 55-70%
            $lightness = 48 + (($idxColor % 3) * 2); // Entre 48-52%
            $coloresPorCenco[$cenco] = $hslToHex($hue, $saturation, $lightness);
        }
        $idxColor++;
    }

    // === Leyenda en tabla: agrupa CENCOS con sus galpones y edades ===
    if (!empty($galponesPorCenco)) {
        $html .= '<div class="leyenda-box">';
        $html .= '<div class="leyenda-title">LEYENDA</div>';
        $html .= '<table class="leyenda-table">';
        $html .= '<thead><tr>';
        $html .= '<th style="width: 30px;">Color</th>';
        $html .= '<th style="width: 120px;">CENCO</th>';
        $html .= '<th>Galpones (Edad en días)</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($galponesPorCenco as $cenco => $info) {
            $color = $coloresPorCenco[$cenco];
            
            // Construir lista de galpones con sus edades
            $galponesList = [];
            foreach ($info['galpones'] as $galpon) {
                // Buscar edad del galpón
                $edad = '0';
                foreach ($galponesUnicos as $g => $det) {
                    if ($g == $galpon) {
                        $edad = $det['tedad'] ?? '0';
                        break;
                    }
                }
                $galponesList[] = 'Galpón ' . htmlspecialchars($galpon) . ' (' . htmlspecialchars($edad) . ' días)';
            }
            $galponesStr = implode(', ', $galponesList);
            
            $html .= '<tr>';
            $html .= '<td class="leyenda-color-cell" style="background-color: ' . $color . ';">';
            $html .= '<span class="leyenda-color-box" style="background-color: ' . $color . '; border-color: #000;"></span>';
            $html .= '</td>';
            $html .= '<td class="leyenda-cenco">CENCO ' . htmlspecialchars($cenco) . '</td>';
            $html .= '<td class="leyenda-galpones">' . $galponesStr . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table></div>';
    }

    // === Mapa de datos ===
    $mapaCompleto = [];
    foreach ($datosAgrupados as $grupo) {
        foreach ($grupo['registros'] as $reg) {
            $s = $reg['tsistema'] ?? '';
            $n = $reg['tnivel'] ?? '';
            $p = $reg['tparametro'] ?? '';
            $g = $reg['tgalpon'] ?? '';
            if ($s !== '' && $n !== '' && $p !== '' && $g !== '') {
                $mapaCompleto[$s][$n][$p][$g] = [
                    'porc' => $reg['tporcentajetotal'] ?? '0',
                    'obs' => $reg['tobservacion'] ?? ''
                ];
            }
        }
    }

    // === Ordenar sistemas ===
    $sistemasFinales = [];
    $otros = [];
    foreach ($todosNiveles as $sistema => $niveles) {
        $sLower = mb_strtolower(trim($sistema), 'UTF-8');
        $pos = false;
        if (strpos($sLower, 'inmunol') !== false) $pos = 0;
        elseif (strpos($sLower, 'digestiv') !== false) $pos = 1;
        elseif (strpos($sLower, 'respirat') !== false) $pos = 2;
        elseif (strpos($sLower, 'evaluaci') !== false && strpos($sLower, 'físic') !== false) $pos = 3;
        if ($pos !== false) {
            $sistemasFinales[$pos] = [$sistema => $niveles];
        } else {
            $otros[$sistema] = $niveles;
        }
    }
    ksort($sistemasFinales);
    $final = [];
    foreach ($sistemasFinales as $item) $final = array_merge($final, $item);
    $final = array_merge($final, $otros);

    // === Función auxiliar: convertir hex a rgba ===
    function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1),2) . str_repeat(substr($hex,1,1),2) . str_repeat(substr($hex,2,1),2);
        }
        return [
            'r' => hexdec(substr($hex,0,2)),
            'g' => hexdec(substr($hex,2,2)),
            'b' => hexdec(substr($hex,4,2))
        ];
    }

    // === Por cada sistema ===
    foreach ($final as $sistema => $niveles) {
        $html .= '<div style="page-break-after: avoid;">';
        $html .= '<div class="sistema-header">' . htmlspecialchars(strtoupper($sistema)) . '</div>';

        $html .= '<table class="data-table">';
        $html .= '<thead>';

        // === Fila 1: Agrupación por CENCO (solo si hay >1 CENCO) ===
        if (count($galponesPorCenco) > 1) {
            $html .= '<tr>';
            $html .= '<th rowspan="2">Nivel</th>';
            $html .= '<th rowspan="2">Parámetro</th>';
            foreach ($galponesPorCenco as $cenco => $info) {
                $color = $coloresPorCenco[$cenco];
                $numCols = count($info['galpones']) * 2;
                $html .= '<th class="cenco-group-header" colspan="' . $numCols . '" style="background-color:' . $color . ';">';
                $html .= 'CENCO ' . htmlspecialchars($cenco) . '<br><small>' . htmlspecialchars($info['granja']) . '</small>';
                $html .= '</th>';
            }
            $html .= '</tr>';
        }

        // === Fila 2: Encabezados individuales (% y Obs) con fondo suave ===
        $html .= '<tr>';
        if (count($galponesPorCenco) <= 1) {
            $html .= '<th>Nivel</th><th>Parámetro</th>';
        }
        foreach ($galponesPorCenco as $cenco => $info) {
            $color = $coloresPorCenco[$cenco];
            $rgb = hexToRgb($color);
            $bgColor = 'rgba(' . $rgb['r'] . ',' . $rgb['g'] . ',' . $rgb['b'] . ', 0.12)';
            foreach ($info['galpones'] as $galpon) {
                $html .= '<th class="galpon-col-porc" style="background-color:' . $bgColor . ';">% Galpón ' . htmlspecialchars($galpon) . '</th>';
                $html .= '<th class="galpon-col-obs" style="background-color:' . $bgColor . ';">Obs Galpón ' . htmlspecialchars($galpon) . '</th>';
            }
        }
        $html .= '</tr>';
        $html .= '</thead><tbody>';

        foreach ($niveles as $nivel => $parametros) {
            $totalFilas = count($parametros);
            foreach ($parametros as $i => $parametro) {
                $html .= '<tr>';
                if ($i === 0 && count($galponesPorCenco) <= 1) {
                    $html .= '<td rowspan="' . $totalFilas . '">' . htmlspecialchars($nivel) . '</td>';
                }
                if (count($galponesPorCenco) <= 1) {
                    $html .= '<td>' . htmlspecialchars($parametro) . '</td>';
                } else {
                    if ($i === 0) {
                        $html .= '<td rowspan="' . $totalFilas . '">' . htmlspecialchars($nivel) . '</td>';
                    }
                    $html .= '<td>' . htmlspecialchars($parametro) . '</td>';
                }

                // Datos por galpón
                foreach ($galponesPorCenco as $cenco => $info) {
                    foreach ($info['galpones'] as $galpon) {
                        $porc = $mapaCompleto[$sistema][$nivel][$parametro][$galpon]['porc'] ?? '0';
                        $obs = $mapaCompleto[$sistema][$nivel][$parametro][$galpon]['obs'] ?? '';
                        $html .= '<td>' . htmlspecialchars($porc) . '</td>';
                        $html .= '<td>' . nl2br(htmlspecialchars($obs)) . '</td>';
                    }
                }

                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';
        $html .= '</div>';
    }

    $html .= '</body></html>';
    return $html;
}
?>
