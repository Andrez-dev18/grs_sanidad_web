<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión a la base de datos.");
}

// Detección de vendor/autoload.php
$vendorPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/sanidadunion/vendor/autoload.php',
];

$vendorLoaded = false;
foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $vendorLoaded = true;
        break;
    }
}

if (!$vendorLoaded) {
    die("Error: No se encuentra vendor/autoload.php. Instala PhpSpreadsheet con: composer require phpoffice/phpspreadsheet");
}

// Consulta SIMPLE: cada registro es una fila
$query = "SELECT * FROM san_analisis_pollo_bb_adulto 
          WHERE tipo_ave = 'BB' 
          ORDER BY 
            codigo_envio ASC,
            fecha_toma_muestra DESC,
            enfermedad ASC";
$result = mysqli_query($conexion, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    die("No hay datos para exportar.");
}

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('SEROLOGIA POLLO BB');

$currentRow = 1;

// Encabezados principales (información general)
$encabezadosPrincipales = [
    'Código SAM', 'Fecha Muestra', 'Edad (días)', 'Tipo Ave', 
    'Planta Incubación', 'Lote', 'Granja', 'Campaña', 
    'Galpón', 'Edad Reprod.', 'Condición', 'Enfermedad'
];

$col = 1;
foreach ($encabezadosPrincipales as $header) {
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($cellLetter . $currentRow, $header);
    
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);
    $col++;
}

// Encabezados de titulación (0-18)
for ($i = 0; $i <= 18; $i++) {
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($cellLetter . $currentRow, $i);
    
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'font' => ['bold' => true, 'size' => 9],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E7E6E6']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);
    $col++;
}

// Encabezados finales
$finalHeaders = ['Count', 'Gmean', 'SD', 'CV'];
foreach ($finalHeaders as $header) {
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($cellLetter . $currentRow, $header);
    
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '70AD47']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);
    $col++;
}

$sheet->getRowDimension($currentRow)->setRowHeight(25);
$currentRow++;

// Escribir CADA REGISTRO como UNA FILA
while ($data = mysqli_fetch_assoc($result)) {
    $col = 1;
    
    // Obtener nombre completo de enfermedad
    $enfermedad = strtoupper($data['enfermedad'] ?? '');
    $nombreCompleto = $enfermedad;
    
    switch($enfermedad) {
        case 'IBV': $nombreCompleto = 'IBV : Inmuno Bronchitis Virus'; break;
        case 'NDV': $nombreCompleto = 'NDV : New Castle Disease Virus'; break;
        case 'REO': $nombreCompleto = 'REO : Reovirus'; break;
        case 'IBD':
        case 'GUMBORO': $nombreCompleto = 'IBD : Gumboro'; break;
        case 'AI': $nombreCompleto = 'AI : Avian Influenza'; break;
        case 'BI': $nombreCompleto = 'BI : Bronquitis Infecciosa'; break;
        case 'ENC': $nombreCompleto = 'ENC : Encefalomielitis'; break;
        case 'CAV': $nombreCompleto = 'CAV : Anemia Viral del Pollo'; break;
        case 'LT': $nombreCompleto = 'LT : Laringotraqueitis'; break;
        case 'MG': $nombreCompleto = 'MG : Mycoplasma Gallisepticum'; break;
        case 'MS': $nombreCompleto = 'MS : Mycoplasma Synoviae'; break;
        case 'ASPERGILOSIS': $nombreCompleto = 'ASPERGILOSIS'; break;
        case 'COCCIDIA': $nombreCompleto = 'COCCIDIA'; break;
        case '(CL2) CLORO LIBRE': $nombreCompleto = '(CL2) CLORO LIBRE'; break;
        case 'OTRAS AFECCIONES': $nombreCompleto = 'OTRAS AFECCIONES'; break;
    }
    
    // Información general
    $datosGenerales = [
        $data['codigo_envio'] ?? '',
        $data['fecha_toma_muestra'] ?? '',
        $data['edad_aves'] ?? '',
        $data['tipo_ave'] ?? '',
        $data['planta_incubacion'] ?? '',
        $data['lote'] ?? '',
        $data['codigo_granja'] ?? '',
        $data['codigo_campana'] ?? '',
        $data['numero_galpon'] ?? '',
        $data['edad_reproductora'] ?? '',
        $data['condicion'] ?? '',
        $nombreCompleto
    ];
    
    foreach ($datosGenerales as $valor) {
        $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($cellLetter . $currentRow, $valor);
        
        $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D0D0']
                ]
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $col++;
    }
    
    // Titulaciones (0-18)
    for ($i = 0; $i <= 18; $i++) {
        $fieldName = 't' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $valor = isset($data[$fieldName]) ? (int)$data[$fieldName] : 0;
        
        $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($cellLetter . $currentRow, $valor);
        
        if ($valor > 0) {
            $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFEB9C']
                ],
                'font' => ['bold' => true]
            ]);
        }
        
        $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D0D0']
                ]
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);
        $col++;
    }
    
    // Count
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($cellLetter . $currentRow, $data['count_muestras'] ?? 20);
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E2EFDA']
        ],
        'font' => ['bold' => true],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    $col++;
    
    // Gmean
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($cellLetter . $currentRow, $data['gmean'] ?? 0);
    $sheet->getStyle($cellLetter . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => '0000FF']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    $col++;
    
    // SD
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($cellLetter . $currentRow, $data['desviacion_estandar'] ?? 0);
    $sheet->getStyle($cellLetter . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    $col++;
    
    // CV
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($cellLetter . $currentRow, $data['cv'] ?? 0);
    $sheet->getStyle($cellLetter . $currentRow)->getNumberFormat()->setFormatCode('0.0');
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    
    $currentRow++;
}

// Ajustar ancho de columnas
for ($i = 1; $i <= 40; $i++) {
    $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
}

// Congelar primera fila
$sheet->freezePane('A2');

// Descargar archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Serologia_Pollo_BB_' . date('Y-m-d_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit();