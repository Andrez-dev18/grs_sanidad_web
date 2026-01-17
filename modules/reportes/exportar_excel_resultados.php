<?php
// Exportar a Excel (mismo espíritu que modules/seguimiento/exportar_excel_resultados.php),
// pero asegurando rutas robustas a vendor/ y conexión usando __DIR__.

require_once __DIR__ . '/../../vendor/autoload.php';
include_once __DIR__ . '/../../../conexion_grs_joya/conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$conn = conectar_joya();
if (!$conn) {
    die('Error de conexión.');
}

// Filtros (mismos del listado en dashboard-reportes.php)
$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';
$laboratorio = $_GET['laboratorio'] ?? '';
$muestra = $_GET['muestra'] ?? '';
$analisis = $_GET['analisis'] ?? '';

$where = " WHERE 1=1 ";

if ($fechaInicio !== '' && $fechaFin !== '') {
    $fi = mysqli_real_escape_string($conn, $fechaInicio);
    $ff = mysqli_real_escape_string($conn, $fechaFin);
    $where .= " AND a.fecEnvio BETWEEN '$fi' AND '$ff' ";
} elseif ($fechaInicio !== '' && $fechaFin === '') {
    $fi = mysqli_real_escape_string($conn, $fechaInicio);
    $where .= " AND a.fecEnvio >= '$fi' ";
} elseif ($fechaInicio === '' && $fechaFin !== '') {
    $ff = mysqli_real_escape_string($conn, $fechaFin);
    $where .= " AND a.fecEnvio <= '$ff' ";
}

if ($laboratorio !== '') {
    $lab = mysqli_real_escape_string($conn, $laboratorio);
    $where .= " AND a.nomLab = '$lab' ";
}

if ($muestra !== '') {
    $mu = mysqli_real_escape_string($conn, $muestra);
    $where .= " AND b.nomMuestra = '$mu' ";
}

if ($analisis !== '') {
    $an = mysqli_real_escape_string($conn, $analisis);
    $where .= " AND b.nomAnalisis = '$an' ";
}

$query = "
SELECT 
    a.codEnvio, a.fecEnvio, a.horaEnvio, a.nomLab, a.nomEmpTrans,
    a.usuarioResponsable, a.autorizadoPor,

    b.posSolicitud, b.codRef, b.fecToma, b.numMuestras,
    b.nomMuestra, b.nomAnalisis,

    c.fechaHoraRegistro, c.fechaLabRegistro,
    c.analisis_nombre, c.resultado, c.obs
FROM san_fact_solicitud_cab a
INNER JOIN san_fact_solicitud_det b ON a.codEnvio = b.codEnvio
LEFT JOIN san_fact_resultado_analisis c 
    ON b.codEnvio = c.codEnvio 
    AND b.codRef = c.codRef 
    AND b.posSolicitud = c.posSolicitud 
    AND b.codAnalisis = c.analisis_codigo
$where
ORDER BY a.codEnvio, b.posSolicitud
";

$result = $conn->query($query);
if (!$result) {
    die('Error en la consulta: ' . $conn->error);
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

/* ================= CABECERAS GENERALES ================= */
$sheet->mergeCells('A1:G1');
$sheet->mergeCells('H1:M1');
$sheet->mergeCells('N1:R1');

$sheet->setCellValue('A1', 'MUESTRA');
$sheet->setCellValue('H1', 'ANALISIS');
$sheet->setCellValue('N1', 'RESULTADOS CUALITATIVOS');

$colorPalette = [
    'E0F2FE', // azul claro
    'ECFDF5', // verde claro
];
$paletteIndex = 0;
$colorsByEnvio = [];
$lastCodEnvio = null;

function headerStyle($color)
{
    return [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $color],
        ],
    ];
}

$sheet->getStyle('A1:G1')->applyFromArray(headerStyle('2563EB')); // Azul
$sheet->getStyle('H1:M1')->applyFromArray(headerStyle('16A34A')); // Verde
$sheet->getStyle('N1:R1')->applyFromArray(headerStyle('FACC15')); // Amarillo

/* ================= CABECERAS DE COLUMNAS ================= */
$headers = [
    'Cod Envío', 'Fecha Envío', 'Hora Envío', 'Laboratorio', 'Empresa Transp.',
    'Usuario Responsable', 'Autorizado Por',

    'Pos. Solicitud', 'Cod Ref', 'Fecha Toma', 'N° Muestras', 'Muestra', 'Análisis',

    'Fecha Reg.', 'Fecha Lab', 'Análisis', 'Resultado', 'Obs',
];

$sheet->fromArray($headers, null, 'A2');
$sheet->getStyle('A2:R2')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

/* ================= DATOS ================= */
$row = 3;
while ($data = $result->fetch_assoc()) {
    $currentCodEnvio = $data['codEnvio'];

    if (!isset($colorsByEnvio[$currentCodEnvio])) {
        if ($paletteIndex >= count($colorPalette)) {
            $paletteIndex = 0;
        }
        $colorsByEnvio[$currentCodEnvio] = $colorPalette[$paletteIndex];
        $paletteIndex++;
    }

    if ($lastCodEnvio !== null && $currentCodEnvio !== $lastCodEnvio) {
        $sheet->getStyle("A" . ($row - 1) . ":R" . ($row - 1))
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
    }

    $sheet->fromArray(array_values($data), null, "A$row");

    $sheet->getStyle("A$row:R$row")->applyFromArray([
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $colorsByEnvio[$currentCodEnvio]],
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ]);

    $lastCodEnvio = $currentCodEnvio;
    $row++;
}

/* ================= AJUSTES ================= */
foreach (range('A', 'R') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* ================= DESCARGA ================= */
$filename = "Reporte_Resultados_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

