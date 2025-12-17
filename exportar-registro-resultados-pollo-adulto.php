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

// Consulta de datos ORDENADA
$query = "SELECT * FROM san_analisis_pollo_bb_adulto 
          WHERE tipo_ave = 'ADULTO' 
          ORDER BY 
            fecha_toma_muestra DESC,
            enfermedad ASC,
            codigo_envio ASC";
$result = mysqli_query($conexion, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    die("No hay datos para exportar.");
}

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('SEROLOGIA POLLO ADULTO');

// Detectar TODAS las columnas S que tienen datos
$columnasConDatos = [];
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)) {
    for ($i = 1; $i <= 6; $i++) {
        $fieldName = 's' . str_pad($i, 2, '0', STR_PAD_LEFT);
        if (isset($row[$fieldName]) && $row[$fieldName] > 0) {
            if (!in_array($i, $columnasConDatos)) {
                $columnasConDatos[] = $i;
            }
        }
    }
}
sort($columnasConDatos);

if (empty($columnasConDatos)) {
    $columnasConDatos = range(1, 6);
}

// CREAR ENCABEZADO ÚNICO
$col = 1;
$encabezados = [
    'Código SAM',
    'Fecha Muestra',
    'Edad (días)',
    'Tipo Ave',
    'Planta Incubación',
    'Lote',
    'Granja',
    'Campaña',
    'Galpón',
    'Edad Reprod.',
    'Estado',
    'Enfermedad',
    'LCS',
    'LCC',
    'LCI',
    '%Coef.Var.',
    'STD I',
    'STD S'
];

$currentRow = 1;

// Escribir encabezados de información general
foreach ($encabezados as $header) {
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

// Encabezados de columnas S (S1, S2, S3, etc.)
foreach ($columnasConDatos as $numCol) {
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($cellLetter . $currentRow, 'S' . $numCol);
    
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 9],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E7E6E6']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
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

// Encabezados finales (Gmean, CV, SD, Count)
$finalHeaders = ['Gmean', 'CV', 'SD', 'Count'];
foreach ($finalHeaders as $header) {
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($cellLetter . $currentRow, $header);
    
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '70AD47']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
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

$sheet->getRowDimension($currentRow)->setRowHeight(25);
$currentRow++;

// ESCRIBIR TODOS LOS DATOS
mysqli_data_seek($result, 0);

while ($data = mysqli_fetch_assoc($result)) {
    $col = 1;
    
    // Nombre completo de enfermedad
    $enfermedad = $data['enfermedad'] ?? '';
    $nombreCompleto = strtoupper($enfermedad);
    
    switch(strtoupper($enfermedad)) {
        case 'IBV': $nombreCompleto = 'IBV : Inmuno Bronchitis Virus'; break;
        case 'NDV': $nombreCompleto = 'NDV : New Castle Disease Virus'; break;
        case 'REO': $nombreCompleto = 'REO : Reovirus'; break;
        case 'IBD':
        case 'GUMBORO': $nombreCompleto = 'IBD : Gumboro'; break;
        case 'AI': $nombreCompleto = 'AI : Avian Influenza'; break;
        case 'BI': 
        case 'BRONQUITIS': $nombreCompleto = 'BI : Bronquitis Infecciosa'; break;
        case 'ENC': $nombreCompleto = 'ENC : Encefalomielitis'; break;
        case 'CAV': $nombreCompleto = 'CAV : Anemia Viral del Pollo'; break;
        case 'LT': $nombreCompleto = 'LT : Laringotraqueitis'; break;
        case 'MG': $nombreCompleto = 'MG : Mycoplasma Gallisepticum'; break;
        case 'MS': $nombreCompleto = 'MS : Mycoplasma Synoviae'; break;
    }
    
    // Datos de información general
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
        $data['estado'] ?? '',
        $nombreCompleto,
        $data['lcs'] ?? '',
        $data['lcc'] ?? '',
        $data['lci'] ?? '',
        $data['coef_variacion'] ?? '',
        $data['std_1'] ?? '',
        $data['std_2'] ?? ''
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
    
    // Datos de columnas S
    foreach ($columnasConDatos as $numCol) {
        $fieldName = 's' . str_pad($numCol, 2, '0', STR_PAD_LEFT);
        $valor = isset($data[$fieldName]) && $data[$fieldName] !== null ? $data[$fieldName] : '';
        
        $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($cellLetter . $currentRow, $valor);
        
        if ($valor !== '' && $valor > 0) {
            $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF2CC']
                ],
                'font' => ['bold' => true, 'color' => ['rgb' => '0000FF']]
            ]);
            $sheet->getStyle($cellLetter . $currentRow)->getNumberFormat()->setFormatCode('0.000');
        }
        
        $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D0D0']
                ]
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ]);
        
        $col++;
    }
    
    // Gmean
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $gmean = $data['gmean'] ?? 0;
    $sheet->setCellValue($cellLetter . $currentRow, $gmean);
    $sheet->getStyle($cellLetter . $currentRow)->getNumberFormat()->setFormatCode('#,##0.0');
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => '0000FF']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'DEEBF7']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            ]
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    $col++;
    
    // CV
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $cv = $data['cv'] ?? 0;
    $sheet->setCellValue($cellLetter . $currentRow, $cv);
    $sheet->getStyle($cellLetter . $currentRow)->getNumberFormat()->setFormatCode('0.0');
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            ]
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    $col++;
    
    // SD
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sd = $data['desviacion_estandar'] ?? 0;
    $sheet->setCellValue($cellLetter . $currentRow, $sd);
    $sheet->getStyle($cellLetter . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            ]
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    $col++;
    
    // Count
    $cellLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($cellLetter . $currentRow, $data['count_muestras'] ?? 20);
    $sheet->getStyle($cellLetter . $currentRow)->applyFromArray([
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E2EFDA']
        ],
        'font' => ['bold' => true],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            ]
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);

    $currentRow++;
}

// Ajustar ancho de columnas
for ($i = 1; $i <= 50; $i++) {
    $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
}

// Congelar primera fila
$sheet->freezePane('A2');

// Descargar archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Serologia_Pollo_Adulto_' . date('Y-m-d_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit();

