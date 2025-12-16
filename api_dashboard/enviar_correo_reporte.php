<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'No autorizado']));
}

include_once '../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();

// Leer datos del remitente
$usuario = $_SESSION['usuario'];
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
$to = $_POST['to'] ?? '';
$subject = $_POST['subject'] ?? '';
$body = $_POST['body'] ?? '';
$codigo = $_POST['codigo'] ?? '';

if (!$to || !$subject || !$body || !$codigo) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

require_once __DIR__ . '/pdf_generador.php';

try {
    $pdfContent = generarPDFReporte($codigo, $conexion);
} catch (Exception $e) {
    error_log("Error generando PDF: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al generar el PDF: ' . $e->getMessage()]);
    exit;
}

// Guardar temporalmente
$tmpPdf = tempnam(sys_get_temp_dir(), 'reporte_') . '.pdf';
file_put_contents($tmpPdf, $pdfContent);


// --- ENVIAR CORREO ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once '../vendor/autoload.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'mail.rinconadadelsur.com.pe';
    $mail->SMTPAuth = true;
    $mail->Username = $sender['correo'];
    $mail->Password = base64_decode($sender['password']);
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($sender['correo'], 'Sistema Reportes');
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->isHTML(false);
    $mail->addAttachment($tmpPdf, "Reporte_{$codigo}.pdf");

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Correo enviado correctamente']);

} catch (Exception $e) {
    error_log("Error al enviar: " . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $mail->ErrorInfo]);
}

// Limpiar
unlink($tmpPdf);
mysqli_close($conexion);
?>