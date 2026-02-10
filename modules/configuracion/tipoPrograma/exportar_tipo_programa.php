<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>var u="../../../login.php";if(window.top!==window.self){window.top.location.href=u;}else{window.location.href=u;}</script>';
    exit();
}

require '../../../vendor/autoload.php';
include_once '../../../../conexion_grs_joya/conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión.");
}
mysqli_set_charset($conn, 'utf8');

$query = "
    SELECT codigo, nombre, sigla
    FROM san_dim_tipo_programa
    ORDER BY nombre
";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Error en la consulta: " . mysqli_error($conn));
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->mergeCells('A1:C1');
$sheet->setCellValue('A1', 'LISTADO DE TIPOS DE PROGRAMA');
$sheet->getStyle('A1:C1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1E40AF']
    ]
]);

$headers = ['N°', 'Nombre', 'Sigla'];
$sheet->fromArray($headers, null, 'A2');
$sheet->getStyle('A2:C2')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

$row = 3;
$contador = 0;
while ($data = mysqli_fetch_assoc($result)) {
    $contador++;
    $sheet->setCellValue('A' . $row, $contador);
    $sheet->setCellValue('B' . $row, $data['nombre']);
    $sheet->setCellValue('C' . $row, $data['sigla'] ?? '');

    $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $row++;
}

foreach (range('A', 'C') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = "Tipos_Programa_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
