<?php
require_once __DIR__ . '/../../vendor/autoload.php';
include_once __DIR__ . '/../../../conexion_grs_joya/conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$conn = conectar_joya();
if (!$conn) {
    die('Error de conexión.');
}

$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$granja = $_GET['granja'] ?? '';

$where = [];
$params = [];
$types = '';

if ($fecha_inicio !== '') {
    $where[] = "tfectra >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
}

if ($fecha_fin !== '') {
    $where[] = "tfectra <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}

if ($granja !== '') {
    $where[] = "tgranja = ?";
    $params[] = $granja;
    $types .= 's';
}

$sql = "SELECT DISTINCT tgranja, tnumreg, tfectra, tcencos, tedad, tgalpon, tuser, tdate, ttime
        FROM t_regnecropsia";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY tfectra DESC, tdate DESC, tgranja ASC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Necropsias');

$headers = [
    'N°Reg',
    'Fecha Necropsia',
    'Granja (código)',
    'Granja (nombre)',
    'Campaña',
    'Galpón',
    'Edad',
    'Usuario',
    'Fecha Registro',
];

$sheet->fromArray($headers, null, 'A1');
$sheet->getStyle('A1:I1')->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

$rowIdx = 2;
while ($row = $result->fetch_assoc()) {
    $tgranja = $row['tgranja'] ?? '';
    $tnumreg = $row['tnumreg'] ?? '';
    $tfectra = $row['tfectra'] ?? '';
    $tcencos = $row['tcencos'] ?? '';
    $tedad = $row['tedad'] ?? '';
    $tgalpon = $row['tgalpon'] ?? '';
    $tuser = $row['tuser'] ?? '';
    $tdate = $row['tdate'] ?? '';
    $ttime = $row['ttime'] ?? '';

    $campania = strlen($tgranja) >= 3 ? substr($tgranja, -3) : '';
    $nombre = $tcencos;
    if (strpos($nombre, 'C=') !== false) {
        $nombre = trim(substr($nombre, 0, strpos($nombre, 'C=')));
    }

    $fechaNecropsia = $tfectra ? date('d/m/Y', strtotime($tfectra)) : '';
    $fechaRegistro = ($tdate && $tdate !== '1000-01-01') ? date('d/m/Y H:i', strtotime($tdate . ' ' . ($ttime ?: '00:00:00'))) : '';

    $sheet->fromArray([
        $tnumreg,
        $fechaNecropsia,
        $tgranja,
        $nombre,
        $campania,
        $tgalpon,
        $tedad,
        $tuser,
        $fechaRegistro,
    ], null, "A{$rowIdx}");

    $rowIdx++;
}

foreach (range('A', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = 'Necropsias_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

