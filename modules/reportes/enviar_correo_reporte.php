<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'No autorizado']));
}

include_once '../../conexion_grs_joya/conexion.php';
include_once '../../includes/historial_acciones.php';
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
require_once __DIR__ . '/pdf_generador.php';
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
    $mail->Host = 'mail.rinconadadelsur.com.pe';
    $mail->SMTPAuth = true;
    $mail->Username = $sender['correo'];
    $mail->Password = base64_decode($sender['password']);
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($sender['correo'], 'Sistema Reportes - Granja Rinconada');
    $mail->Subject = $subject;
    $mail->Body = $body;
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
    $nom_usuario = $_SESSION['nombre'];
   $datos_nuevos = [
    'asunto' => $subject,
    'cuerpo' => substr($body, 0, 200) . (strlen($body) > 200 ? '...' : ''), // Limitar tamaño
    'codigo_reporte' => $codigo,
    'destinatarios' => $para,
    'num_adjuntos' => !empty($_FILES['archivos_adjuntos']['name'][0]) 
        ? count($_FILES['archivos_adjuntos']['name']) 
        : 0,
    
    'fecha_hora' => date('Y-m-d H:i:s')
];

// Convertir a JSON
$datos_nuevos_json = json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE);
    registrarAccion(
        $usuario,
        $nom_usuario,
        'ENVIO_DE_CORREO',
        null,
        $codigo,
        null,
        $datos_nuevos,
        'Se realizo el envio de correo al laboratorio',
        null
    );
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
?>