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

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Leer datos del remitente
$usuario = $_POST['usuario'];
$stmt = mysqli_prepare($conexion, "SELECT correo, password FROM san_correo_sanidad WHERE codigo = ?");
mysqli_stmt_bind_param($stmt, 's', $usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sender = mysqli_fetch_assoc($result);

if (!$sender) {
    echo json_encode(['success' => false, 'message' => 'Configuración de correo no encontrada']);
    exit;
}

// Datos del correo
$subject = $_POST['subject'] ?? '';
$body = $_POST['body'] ?? '';
$codigo = $_POST['codigo'] ?? '';
$para = $_POST['para'] ?? []; 

// Validación
if (!$subject || !$body || !$codigo || empty($para) || !is_array($para)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios o destinatarios inválidos.']);
    exit;
}

// Generar PDF
require_once  '../api_dashboard/pdf_generador.php';
try {
    $pdfContent = generarPDFReporte($codigo, $conexion);
} catch (Exception $e) {
    error_log("Error generando PDF: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al generar el PDF.']);
    exit;
}

// Guardar PDF temporalmente
$tmpPdf = tempnam(sys_get_temp_dir(), 'reporte_') . '.pdf';
file_put_contents($tmpPdf, $pdfContent);

// Enviar correo
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once '../vendor/autoload.php';

$mail = new PHPMailer(true);
try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = 'mail.rinconadadelsur.com.pe'; // 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $sender['correo'];
    $mail->Password   = base64_decode($sender['password']);
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom($sender['correo'], 'Sistema Reportes - Granja Rinconada');
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->isHTML(false);

    // Añadir destinatarios en "Para"
    $alMenosUnoValido = false;
    foreach ($para as $email) {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail->addAddress($email);
            $alMenosUnoValido = true;
        }
    }

    if (!$alMenosUnoValido) {
        throw new Exception('Ningún destinatario válido en "Para".');
    }

    // Adjuntar PDF
    $mail->addAttachment($tmpPdf, "Reporte_{$codigo}.pdf");

    // Adjuntar archivos adicionales (si los hay)
    if (!empty($_FILES['archivos_adjuntos']['tmp_name'][0])) {
        foreach ($_FILES['archivos_adjuntos']['tmp_name'] as $index => $tmpName) {
            if ($tmpName && $_FILES['archivos_adjuntos']['error'][$index] === UPLOAD_ERR_OK) {
                $originalName = $_FILES['archivos_adjuntos']['name'][$index];
                $mail->addAttachment($tmpName, $originalName);
            }
        }
    }

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Correo enviado correctamente a todos los destinatarios.']);

} catch (Exception $e) {
    error_log("Error al enviar correo: " . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => 'Error al enviar: ' . $mail->ErrorInfo]);
}

// Limpieza
if (file_exists($tmpPdf)) {
    unlink($tmpPdf);
}
if ($conexion) {
    @mysqli_close($conexion);
}
