<?php
// --- CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once '../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    http_response_code(500);
    exit('{"error":"Error de conexión"}');
}

$codigoEnvio = $_GET['codigo'] ?? '';
if (!$codigoEnvio) {
    die("Código de envío no proporcionado.");
}

// Verificar que existe el registro (opcional, pero recomendado)
$queryCab = "
    SELECT c.codEnvio
    FROM san_fact_solicitud_cab c
    WHERE c.codEnvio = ?
";
$stmtCab = mysqli_prepare($conexion, $queryCab);
mysqli_stmt_bind_param($stmtCab, "s", $codigoEnvio);
mysqli_stmt_execute($stmtCab);
$cab = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCab));
mysqli_stmt_close($stmtCab);

if (!$cab) {
    die("Registro no encontrado.");
}

// === Generar PDF con mPDF ===
require_once __DIR__ . '/../vendor/autoload.php';
use Mpdf\Mpdf;

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => [50, 38],
    'margin_top' => 0,
    'margin_bottom' => 0,
    'margin_left' => 0,
    'margin_right' => 0,
    'default_font_size' => 8,
    'tempDir' => __DIR__ . '/../pdf_tmp',
]);

// Contenido: solo el QR centrado y maximizado
$html = '

<div style="width: 100%; height: 100%; display: table;">
    <div style="display: table-cell; text-align: center; vertical-align: middle;">
        <barcode code="' . htmlspecialchars($codigoEnvio) . '" type="QR" size="1.52" error="M" />
    </div>
</div>
';

$mpdf->WriteHTML($html);
$mpdf->Output("qr_" . $codigoEnvio . ".pdf", 'I'); // 'I' = mostrar en navegador12