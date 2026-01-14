<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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


if (empty($fecha_inicio) || empty($fecha_fin)) {
    die('Debe especificar fecha de inicio y fecha de fin');
}


if (is_string($cencos) && $cencos !== 'todos') {
    $cencos = explode(',', $cencos);
}
if (is_string($galpones) && $galpones !== 'todos') {
    $galpones = explode(',', $galpones);
}


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
    
    // Filtrar parámetros vacíos o que sean "0"
    if (empty(trim($parametro)) || trim($parametro) === '0') {
        continue;
    }
    
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

// Agrupar galpones por CENCO (necesario para ambos formatos)
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

// Generar reporte según formato
if ($formato === 'excel') {
    _generarExcel($datosAgrupados, $fecha_inicio, $fecha_fin, $cencosUnicos, $esComparativoCencos, $todosNiveles, $galponesUnicos, $galponesPorCenco);
} else {
    _generarPDF($datosAgrupados, $fecha_inicio, $fecha_fin, $cencosUnicos, $esComparativoCencos, $todosNiveles, $galponesUnicos, $galponesPorCenco);
}

function _generarPDF($datosAgrupados, $fecha_inicio, $fecha_fin, $cencosUnicos, $esComparativoCencos, $todosNiveles, $galponesUnicos, $galponesPorCenco) {
    require_once '../../vendor/autoload.php';

    $numColumnas = 3 + (2 * count($galponesUnicos));
    $anchoMM = max(297, 60 + ($numColumnas * 28));


    try {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [210,  $anchoMM], 
            'orientation' => 'L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 15,
            'margin_bottom' => 10,
            'tempDir' => __DIR__ . '/../../pdf_tmp',
        ]);


        $html = _generarHTMLReporte(
            $datosAgrupados, 
            $fecha_inicio, 
            $fecha_fin, 
            $cencosUnicos, 
            $esComparativoCencos, 
            $todosNiveles,
            $galponesUnicos,
            $galponesPorCenco 
        );

        $mpdf->WriteHTML($html);
        $mpdf->Output('reporte_comparativo_' . date('Ymd_His') . '.pdf', 'I');
        
    } catch (Exception $e) {
        die('Error generando PDF: ' . $e->getMessage());
    }
}

function _generarExcel($datosAgrupados, $fecha_inicio, $fecha_fin, $cencosUnicos, $esComparativoCencos, $todosNiveles, $galponesUnicos, $galponesPorCenco) {
    try {
        require_once '../../vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // === Logo ===
    $logoPath = __DIR__ . '/logo.png';
    $logoObj = null;
    if (file_exists($logoPath)) {
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setPath($logoPath);
        $drawing->setHeight(20);
        $drawing->setCoordinates('A1');
        $logoObj = $drawing;
    }
    
 
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => '0C4A6E']], // Color del texto igual al PDF
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F0F9FF'] 
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'CBD5E1'] 
            ]
        ]
    ];
    
    $cellStyle = [
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'CBD5E1'] 
            ]
        ]
    ];
    
    $row = 1;
    
    // Calcular número de columnas totales
    $numCols = 2; // Nivel, Parámetro (o Sistema si se usa)
    foreach ($galponesPorCenco as $info) {
        $numCols += count($info['galpones']) * 2; // % y Obs por cada galpón
    }
    // Si hay más de un CENCO, no contamos Sistema como columna separada
    if (count($galponesPorCenco) <= 1) {
        $numCols += 1; // Sistema
    }
    $lastCol = $sheet->getCellByColumnAndRow($numCols, 1)->getColumn();
    
    // === Header con Logo (igual al PDF) ===
    // Calcular proporciones: 20% logo, 60% título, 20% vacía
    $logoCols = max(1, floor($numCols * 0.2));
    $titleCols = max(1, floor($numCols * 0.6));
    $emptyCols = $numCols - $logoCols - $titleCols;
    
    $logoEndCol = $sheet->getCellByColumnAndRow($logoCols, $row)->getColumn();
    $titleStartCol = $sheet->getCellByColumnAndRow($logoCols + 1, $row)->getColumn();
    $titleEndCol = $sheet->getCellByColumnAndRow($logoCols + $titleCols, $row)->getColumn();
    $emptyStartCol = $sheet->getCellByColumnAndRow($logoCols + $titleCols + 1, $row)->getColumn();
    
    // Celda 1: Logo y nombre de empresa (20% del ancho)
    if ($logoObj) {
        $logoObj->setCoordinates('A' . $row);
        $logoObj->setOffsetX(5);
        $logoObj->setOffsetY(5);
        $logoObj->setWorksheet($sheet);
        $sheet->getRowDimension($row)->setRowHeight(30);
    }
    $sheet->setCellValue('A' . $row, 'GRANJA RINCONADA DEL SUR S.A.');
    $sheet->mergeCells('A' . $row . ':' . $logoEndCol . $row);
    $sheet->getStyle('A' . $row)->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '334155']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'CBD5E1']
            ]
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFFFF']
        ]
    ]);
    
    // Celda 2: Título del reporte (60% del ancho)
    $sheet->setCellValue($titleStartCol . $row, 'REPORTE COMPARATIVO DE NECROPSIA');
    $sheet->mergeCells($titleStartCol . $row . ':' . $titleEndCol . $row);
    $sheet->getStyle($titleStartCol . $row)->applyFromArray([
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '000000']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'CBD5E1']
            ]
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E6F2FF']
        ]
    ]);
    
    // Celda 3: Vacía (20% del ancho, igual al PDF)
    $sheet->setCellValue($emptyStartCol . $row, '');
    $sheet->mergeCells($emptyStartCol . $row . ':' . $lastCol . $row);
    $sheet->getStyle($emptyStartCol . $row)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'CBD5E1']
            ]
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFFFF']
        ]
    ]);
    $row++;
    
    // === Período ===
    $sheet->setCellValue('A' . $row, 'Período: ' . $fecha_inicio . ' - ' . $fecha_fin);
    $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
    $sheet->getStyle('A' . $row)->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1E3A8A']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFFFF']
        ]
    ]);
    $row++;
    
    $coloresBaseCenco = [
        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
        '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#84cc16',
        '#6366f1', '#22c55e', '#fbbf24', '#f87171', '#a78bfa',
        '#34d399', '#fb923c', '#60a5fa', '#c084fc', '#f472b6',
        '#38bdf8', '#4ade80', '#fbbf24', '#fb7185', '#a5b4fc'
    ];
    
    $coloresPorCenco = [];
    $idxColor = 0;
   
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
        return sprintf("%02x%02x%02x", $r, $g, $b);
    };
    

    foreach (array_keys($galponesPorCenco) as $cenco) {
        if ($idxColor < count($coloresBaseCenco)) {
            $coloresPorCenco[$cenco] = strtoupper(ltrim($coloresBaseCenco[$idxColor], '#'));
        } else {
            // Generar color dinámicamente usando HSL (convertido a hex)
            $hue = ($idxColor * 137.508) % 360;
            $saturation = 55 + (($idxColor % 4) * 5);
            $lightness = 48 + (($idxColor % 3) * 2);
            $coloresPorCenco[$cenco] = $hslToHex($hue, $saturation, $lightness);
        }
        $idxColor++;
    }
    
    
    if (!empty($galponesPorCenco)) {
        $row++; 
        
       
        $sheet->setCellValue('A' . $row, 'LEYENDA');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1E40AF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8FAFC']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1']
                ]
            ]
        ]);
        $row++;
        
        // Cabeceras de la tabla de leyenda
        $sheet->setCellValue('A' . $row, 'Color');
        $sheet->setCellValue('B' . $row, 'CENCO');
        $sheet->setCellValue('C' . $row, 'Galpones (Edad en días)');
        $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6F2FF']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1']
                ]
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
        ]);
        $row++;
        
        // Filas de la leyenda
        foreach ($galponesPorCenco as $cenco => $info) {
            $colorHex = $coloresPorCenco[$cenco];
            
            // Construir lista de galpones con sus edades
            $galponesList = [];
            foreach ($info['galpones'] as $galpon) {
                $edad = $galponesUnicos[$galpon]['tedad'] ?? '0';
                $galponesList[] = 'Galpón ' . $galpon . ' (' . $edad . ' días)';
            }
            $galponesStr = implode(', ', $galponesList);
            
            // Color (celda con fondo de color)
            $sheet->setCellValue('A' . $row, '');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $colorHex]
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1']
                    ]
                ]
            ]);
            
            // CENCO
            $sheet->setCellValue('B' . $row, 'CENCO ' . $cenco);
            $sheet->getStyle('B' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '1E293B']],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1']
                    ]
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
            ]);
            
            // Galpones
            $sheet->setCellValue('C' . $row, $galponesStr);
            $sheet->getStyle('C' . $row)->applyFromArray([
                'font' => ['color' => ['rgb' => '475569']],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1']
                    ]
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'wrapText' => true
            ]);
            
            // Aplicar fondo alternado
            if ($row % 2 == 0) {
                $sheet->getStyle('B' . $row . ':C' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC']
                    ]
                ]);
            }
            
            $row++;
        }
        
        // Ajustar ancho de columnas de la leyenda
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(50);
        
        $row++; // Espacio después de la leyenda
    }
    
    // === Mapa de datos (igual al PDF) ===
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
    
    // === Ordenar sistemas (igual al PDF) ===
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
    
    // === Función auxiliar: convertir hex a rgb ===
    $hexToRgb = function($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1),2) . str_repeat(substr($hex,1,1),2) . str_repeat(substr($hex,2,1),2);
        }
        return [
            'r' => hexdec(substr($hex,0,2)),
            'g' => hexdec(substr($hex,2,2)),
            'b' => hexdec(substr($hex,4,2))
        ];
    };
    
    // === Por cada sistema (igual al PDF) ===
    foreach ($final as $sistema => $niveles) {
        // Título del sistema
        $sheet->setCellValue('A' . $row, strtoupper($sistema));
        $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1E40AF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DBEAFE']
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '93C5FD']
                ]
            ]
        ]);
        $row++;
        
        // === Fila 1: Agrupación por CENCO (igual al PDF) ===
        $headerRow1 = $row;
        $col = 1;
        
        if (count($galponesPorCenco) > 1) {
            // Nivel (rowspan 2)
            $sheet->setCellValueByColumnAndRow($col++, $row, 'Nivel');
            // Parámetro (rowspan 2)
            $sheet->setCellValueByColumnAndRow($col++, $row, 'Parámetro');
            
            // CENCOS con colspan
            foreach ($galponesPorCenco as $cenco => $info) {
                $colorHex = $coloresPorCenco[$cenco];
                $numColsCenco = count($info['galpones']) * 2;
                $startCol = $col;
                $endCol = $col + $numColsCenco - 1;
                
                $sheet->setCellValueByColumnAndRow($col, $row, 'CENCO ' . $cenco . "\n" . $info['granja']);
                $sheet->mergeCells($sheet->getCellByColumnAndRow($startCol, $row)->getCoordinate() . ':' . $sheet->getCellByColumnAndRow($endCol, $row)->getCoordinate());
                $sheet->getStyle($sheet->getCellByColumnAndRow($startCol, $row)->getCoordinate())->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 8.5],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $colorHex]
                    ],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1']
                        ]
                    ]
                ]);
                $col = $endCol + 1;
            }
        } else {
            // Si solo hay un CENCO, mostrar Nivel y Parámetro normalmente
            $sheet->setCellValueByColumnAndRow($col++, $row, 'Nivel');
            $sheet->setCellValueByColumnAndRow($col++, $row, 'Parámetro');
        }
        $row++;
        
        // === Fila 2: Encabezados individuales (% y Obs) ===
        $col = 1;
        
        if (count($galponesPorCenco) <= 1) {
            // Si solo hay un CENCO, ya pusimos Nivel y Parámetro en la fila anterior
            $col = 3;
        }
        
        foreach ($galponesPorCenco as $cenco => $info) {
            $colorHex = $coloresPorCenco[$cenco];
            $rgb = $hexToRgb('#' . $colorHex);
            // Calcular color con transparencia (similar a rgba en PDF)
            $bgColorR = min(255, $rgb['r'] + round((255 - $rgb['r']) * 0.88));
            $bgColorG = min(255, $rgb['g'] + round((255 - $rgb['g']) * 0.88));
            $bgColorB = min(255, $rgb['b'] + round((255 - $rgb['b']) * 0.88));
            $bgColor = sprintf('%02X%02X%02X', $bgColorR, $bgColorG, $bgColorB);
            
            foreach ($info['galpones'] as $galpon) {
                // % Galpón
                $sheet->setCellValueByColumnAndRow($col++, $row, '% Galpón ' . $galpon);
                $sheet->getStyle($sheet->getCellByColumnAndRow($col - 1, $row)->getCoordinate())->applyFromArray([
                    'font' => ['size' => 8, 'color' => ['rgb' => '0C4A6E']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $bgColor]
                    ],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1']
                        ]
                    ]
                ]);
                
                // Obs Galpón
                $sheet->setCellValueByColumnAndRow($col++, $row, 'Obs Galpón ' . $galpon);
                $sheet->getStyle($sheet->getCellByColumnAndRow($col - 1, $row)->getCoordinate())->applyFromArray([
                    'font' => ['size' => 8, 'color' => ['rgb' => '0C4A6E']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $bgColor]
                    ],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1']
                        ]
                    ]
                ]);
            }
        }
        
        $row++;
        
        // === Escribir datos (igual al PDF) ===
        foreach ($niveles as $nivel => $parametros) {
            $totalFilas = count($parametros);
            $nivelStartRow = $row;
            
            foreach ($parametros as $i => $parametro) {
                $col = 1;
                
                // Nivel (con rowspan) - solo en la primera fila del nivel
                if ($i === 0) {
                    $sheet->setCellValueByColumnAndRow($col++, $row, $nivel);
                } else {
                    $col++; // Saltar columna de Nivel
                }
                
                // Parámetro
                $sheet->setCellValueByColumnAndRow($col++, $row, $parametro);
                
                // Datos por galpón
                foreach ($galponesPorCenco as $cenco => $info) {
                    foreach ($info['galpones'] as $galpon) {
                        $porc = $mapaCompleto[$sistema][$nivel][$parametro][$galpon]['porc'] ?? '0';
                        
                        // % Galpón
                        $sheet->setCellValueByColumnAndRow($col++, $row, $porc);
                        
                        // Obs Galpón (solo en la primera fila del nivel, con rowspan)
                        if ($i === 0) {
                            $obsPrimero = $mapaCompleto[$sistema][$nivel][$parametros[0]][$galpon]['obs'] ?? '';
                            $sheet->setCellValueByColumnAndRow($col++, $row, $obsPrimero);
                        } else {
                            $col++; // Saltar columna de Obs
                        }
                    }
                }
                
                // Aplicar estilos básicos a la fila
                $rowRange = $sheet->getCellByColumnAndRow(1, $row)->getCoordinate() . ':' . $sheet->getCellByColumnAndRow($numCols, $row)->getCoordinate();
                $sheet->getStyle($rowRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1']
                        ]
                    ]
                ]);
                
                $row++;
            }
            
            // Aplicar rowspan a Nivel después de escribir todas las filas del nivel (siempre, como en el PDF)
            $sheet->mergeCells($sheet->getCellByColumnAndRow(1, $nivelStartRow)->getCoordinate() . ':' . $sheet->getCellByColumnAndRow(1, $nivelStartRow + $totalFilas - 1)->getCoordinate());
            $sheet->getStyle($sheet->getCellByColumnAndRow(1, $nivelStartRow)->getCoordinate())->applyFromArray([
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1']
                    ]
                ]
            ]);
            
            // Aplicar rowspan a Obs para cada galpón (solo primera fila del nivel)
            // Obs está después de Nivel (col 1), Parámetro (col 2), y % Galpón (col 3), así que empieza en col 4
            $obsCol = 4;
            foreach ($galponesPorCenco as $cenco => $info) {
                foreach ($info['galpones'] as $galpon) {
                    $sheet->mergeCells($sheet->getCellByColumnAndRow($obsCol, $nivelStartRow)->getCoordinate() . ':' . $sheet->getCellByColumnAndRow($obsCol, $nivelStartRow + $totalFilas - 1)->getCoordinate());
                    $sheet->getStyle($sheet->getCellByColumnAndRow($obsCol, $nivelStartRow)->getCoordinate())->applyFromArray([
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP, 'wrapText' => true],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1']
                            ]
                        ]
                    ]);
                    $obsCol += 2; // Siguiente par % y Obs
                }
            }
        }
        
        $row++; // Espacio después del sistema
    }
    
    // Ajustar ancho de columnas (las columnas de la leyenda ya se ajustaron antes, aquí ajustamos las de la tabla principal)
    // Nota: Las columnas A, B, C ya tienen ancho asignado para la leyenda, pero aquí las reajustamos para la tabla principal
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
    } catch (Exception $e) {
        die('Error generando Excel: ' . $e->getMessage());
    }
}

function _generarHTMLReporte($datosAgrupados, $fecha_inicio, $fecha_fin, $cencosUnicos, $esComparativoCencos, $todosNiveles, $galponesUnicos, $galponesPorCenco = []) {
    // === Logo ===
    $logoPath = __DIR__ . '/logo.png';
    $logo = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
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

    // === Cabecera conjunta ===
    $html .= '<table width="100%" style="border-collapse: collapse; border: 1px solid #cbd5e1; margin-top: 10px; margin-bottom: 0;">';
    $html .= '<tr>';
    if (!empty($logo)) {
        $html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">';
        $html .= $logo . ' GRANJA RINCONADA DEL SUR S.A.';
        $html .= '</td>';
    } else {
        $html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">';
        $html .= 'GRANJA RINCONADA DEL SUR S.A.';
        $html .= '</td>';
    }
    $html .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #e6f2ff; color: #000; font-weight: bold; font-size: 14px; border: 1px solid #cbd5e1;">';
    $html .= 'REPORTE COMPARATIVO DE NECROPSIA';
    $html .= '</td>';
    $html .= '<td style="width: 20%; background-color: #fff; border: 1px solid #cbd5e1;"></td>';
    $html .= '</tr>';
    $html .= '</table>';
    
    // === Período ===
    $html .= '<div class="periodo">Período: ' . htmlspecialchars($fecha_inicio) . ' - ' . htmlspecialchars($fecha_fin) . '</div>';

    // === Colores por CENCO  ===

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

        // === Fila 1: Agrupación por CENCO ===
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

        // === Fila 2: Encabezados individuales (% y Obs)===
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
            
                // Celda de Nivel (con rowspan)
                if ($i === 0) {
                    $html .= '<td rowspan="' . $totalFilas . '">' . htmlspecialchars($nivel) . '</td>';
                }
            
                // Celda de Parámetro
                $html .= '<td>' . htmlspecialchars($parametro) . '</td>';
            
                // Datos por galpón
                foreach ($galponesPorCenco as $cenco => $info) {
                    foreach ($info['galpones'] as $galpon) {
                        $porc = $mapaCompleto[$sistema][$nivel][$parametro][$galpon]['porc'] ?? '0';
            
                        
                        $obsPrimero = $mapaCompleto[$sistema][$nivel][$parametros[0]][$galpon]['obs'] ?? '';
            
                        
                        if ($i === 0) {
                            $html .= '<td>' . htmlspecialchars($porc) . '</td>';
                            $html .= '<td rowspan="' . $totalFilas . '">' . nl2br(htmlspecialchars($obsPrimero)) . '</td>';
                        } else {
                         
                            $html .= '<td>' . htmlspecialchars($porc) . '</td>';
                           
                        }
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
