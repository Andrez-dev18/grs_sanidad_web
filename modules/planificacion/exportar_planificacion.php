<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

// Asegúrate de que PhpSpreadsheet esté instalado vía Composer
require_once '../../vendor/autoload.php';

include_once '../../../conexion_grs_joya/conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión a la base de datos.");
}
mysqli_set_charset($conexion, 'utf8');

// === Filtros desde URL ===
$fecha_desde = $_GET['fecha_desde'] ?? null;
$fecha_hasta = $_GET['fecha_hasta'] ?? null;
$granja = $_GET['granja'] ?? null;
$campania = $_GET['campania'] ?? null;
$galpon = $_GET['galpon'] ?? null;
$edad = $_GET['edad'] ?? null;

// === Construir WHERE ===
$where = [];
if ($fecha_desde) $where[] = "p.fecToma >= '" . mysqli_real_escape_string($conexion, $fecha_desde) . "'";
if ($fecha_hasta) $where[] = "p.fecToma <= '" . mysqli_real_escape_string($conexion, $fecha_hasta) . "'";
if ($granja) $where[] = "LEFT(p.codRef, 3) = '" . mysqli_real_escape_string($conexion, $granja) . "'";
if ($campania) $where[] = "SUBSTRING(p.codRef, 4, 3) = '" . mysqli_real_escape_string($conexion, $campania) . "'";
if ($galpon) $where[] = "SUBSTRING(p.codRef, 7, 2) = '" . mysqli_real_escape_string($conexion, $galpon) . "'";
if ($edad) $where[] = "SUBSTRING(p.codRef, 9, 2) = '" . mysqli_real_escape_string($conexion, $edad) . "'";

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// === Consulta SQL ===
$sql = "
    SELECT 
        p.fecToma AS fecha,
        LEFT(p.codRef, 3) AS granja,
        c.nombre AS nombreGranja,
        SUBSTRING(p.codRef, 4, 3) AS campania,
        SUBSTRING(p.codRef, 7, 2) AS galpon,
        SUBSTRING(p.codRef, 9, 2) AS edad,
        p.nomMuestra AS tipo_muestra,
        p.nomAnalisis AS analisis
    FROM san_planificacion p
    LEFT JOIN ccos c ON LEFT(p.codRef, 3) = c.codigo
    $whereClause
    ORDER BY p.fecToma DESC, p.codRef
";

$result = mysqli_query($conexion, $sql);
if (!$result) {
    die("Error en la consulta: " . mysqli_error($conexion));
}

// === Crear spreadsheet ===
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Planificación');

// === Cabecera principal ===
$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', 'REPORTE DE PLANIFICACIÓN');
$sheet->getStyle('A1:H1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1E40AF'] // Azul oscuro
    ]
]);

// === Cabeceras de columna ===
$headers = ['Fecha', 'Granja', 'Nombre Granja', 'Campaña', 'Galpón', 'Edad', 'Tipo Muestra', 'Análisis'];
$sheet->fromArray($headers, null, 'A2');
$sheet->getStyle('A2:H2')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

// === Llenar datos ===
$fila = 3;
while ($row = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue('A' . $fila, $row['fecha']);
    $sheet->setCellValue('B' . $fila, $row['granja']);
    $sheet->setCellValue('C' . $fila, $row['nombreGranja']);
    $sheet->setCellValue('D' . $fila, $row['campania']);
    $sheet->setCellValue('E' . $fila, $row['galpon']);
    $sheet->setCellValue('F' . $fila, $row['edad']);
    $sheet->setCellValue('G' . $fila, $row['tipo_muestra']);
    $sheet->setCellValue('H' . $fila, $row['analisis']);

    $sheet->getStyle("A{$fila}:H{$fila}")->applyFromArray([
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $fila++;
}

// === Ajustar columnas ===
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// === Descargar ===
$filename = "Planificacion_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>