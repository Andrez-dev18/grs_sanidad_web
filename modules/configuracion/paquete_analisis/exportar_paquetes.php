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

// Consulta: paquetes + tipo de muestra + análisis asociados
$query = "
    SELECT 
        p.codigo AS paquete_codigo,
        p.nombre AS paquete_nombre,
        tm.codigo AS muestra_codigo,
        tm.nombre AS muestra_nombre,
        GROUP_CONCAT(a.nombre ORDER BY a.nombre SEPARATOR ', ') AS analisis_lista
    FROM san_dim_paquete p
    LEFT JOIN san_dim_tipo_muestra tm ON p.tipoMuestra = tm.codigo
    LEFT JOIN san_dim_analisis_paquete ap ON p.codigo = ap.paquete
    LEFT JOIN san_dim_analisis a ON ap.analisis = a.codigo
    GROUP BY p.codigo, p.nombre, tm.codigo, tm.nombre
    ORDER BY p.codigo
";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Error en la consulta: " . mysqli_error($conn));
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

/* ================= CABECERA PRINCIPAL ================= */
$sheet->mergeCells('A1:D1');
$sheet->setCellValue('A1', 'LISTADO DE PAQUETES DE MUESTRA');
$sheet->getStyle('A1:D1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']]
]);

/* ================= CABECERAS DE COLUMNAS ================= */
$headers = [
    'Código Paquete',
    'Nombre del Paquete',
    'Tipo de Muestra',
    'Análisis Asociados'
];

$sheet->fromArray($headers, null, 'A2');
$sheet->getStyle('A2:D2')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

/* ================= DATOS ================= */
$row = 3;
$lastPaquete = null;
$colorPalette = ['E0F2FE', 'ECFDF5', 'FEF3C7', 'F3E8FF'];
$paletteIndex = 0;
$colorsByPaquete = [];

while ($data = mysqli_fetch_assoc($result)) {
    $currentPaquete = $data['paquete_codigo'];

    // Asignar color por paquete
    if (!isset($colorsByPaquete[$currentPaquete])) {
        $colorsByPaquete[$currentPaquete] = $colorPalette[$paletteIndex % count($colorPalette)];
        $paletteIndex++;
    }

    // Línea separadora entre paquetes
    if ($lastPaquete !== null && $currentPaquete !== $lastPaquete) {
        $sheet->getStyle("A" . ($row - 1) . ":D" . ($row - 1))
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM);
    }

    // Formatear tipo de muestra
    $tipoMuestra = '';
    if (!empty($data['muestra_codigo']) && !empty($data['muestra_nombre'])) {
        $tipoMuestra = $data['muestra_codigo'] . ' - ' . $data['muestra_nombre'];
    } else {
        $tipoMuestra = 'Sin tipo asignado';
    }

    // Escribir fila
    $sheet->setCellValue('A' . $row, $data['paquete_codigo']);
    $sheet->setCellValue('B' . $row, $data['paquete_nombre']);
    $sheet->setCellValue('C' . $row, $tipoMuestra);
    $sheet->setCellValue('D' . $row, $data['analisis_lista'] ?? '');

    // Aplicar color de fondo
    // Aplicar solo bordes y alineación (sin color de fondo)
    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);

    $lastPaquete = $currentPaquete;
    $row++;while ($data = mysqli_fetch_assoc($result)) {
    $currentPaquete = $data['paquete_codigo'];

    // Línea separadora entre paquetes
    if ($lastPaquete !== null && $currentPaquete !== $lastPaquete) {
        $sheet->getStyle("A" . ($row - 1) . ":D" . ($row - 1))
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM);
    }

    // Formatear tipo de muestra
    $tipoMuestra = '';
    if (!empty($data['muestra_codigo']) && !empty($data['muestra_nombre'])) {
        $tipoMuestra = $data['muestra_codigo'] . ' - ' . $data['muestra_nombre'];
    } else {
        $tipoMuestra = 'Sin tipo asignado';
    }

    // Escribir fila
    $sheet->setCellValue('A' . $row, $data['paquete_codigo']);
    $sheet->setCellValue('B' . $row, $data['paquete_nombre']);
    $sheet->setCellValue('C' . $row, $tipoMuestra);
    $sheet->setCellValue('D' . $row, $data['analisis_lista'] ?? '');


    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);

    $lastPaquete = $currentPaquete;
    $row++;
}
}   

/* ================= AJUSTES DE COLUMNAS ================= */
foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* ================= DESCARGA ================= */
$filename = "Paquetes_Muestra_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>