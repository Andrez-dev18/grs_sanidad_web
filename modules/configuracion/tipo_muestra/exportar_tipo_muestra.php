<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
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

// Consulta: tipos de muestra
$query = "
    SELECT 
        codigo,
        nombre,
        descripcion,
        lonCod
    FROM san_dim_tipo_muestra
    ORDER BY codigo
";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Error en la consulta: " . mysqli_error($conn));
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

/* ================= CABECERA PRINCIPAL ================= */
$sheet->mergeCells('A1:D1');
$sheet->setCellValue('A1', 'LISTADO DE TIPOS DE MUESTRA');
$sheet->getStyle('A1:D1')->applyFromArray([
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

/* ================= CABECERAS DE COLUMNAS ================= */
$headers = ['Código', 'Nombre', 'Descripción', 'Long. Código'];
$sheet->fromArray($headers, null, 'A2');
$sheet->getStyle('A2:D2')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

/* ================= DATOS ================= */
$row = 3;
while ($data = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue('A' . $row, $data['codigo'] ?? '');
    $sheet->setCellValue('B' . $row, $data['nombre'] ?? '');
    $sheet->setCellValue('C' . $row, $data['descripcion'] ?? '');
    $sheet->setCellValue('D' . $row, $data['lonCod'] ?? '');

    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);

    $row++;
}

/* ================= AJUSTES DE COLUMNAS ================= */
foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* ================= DESCARGA ================= */
$filename = "Tipos_Muestra_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>