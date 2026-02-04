<?php
// Iniciar buffer de salida para capturar cualquier output no deseado
ob_start();

// --- CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Función para enviar error JSON
function enviarError($mensaje, $codigo = 500) {
    ob_end_clean();
    http_response_code($codigo);
    echo json_encode(['success' => false, 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error fatal: ' . $error['message'] . ' en ' . $error['file'] . ' línea ' . $error['line']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

// Limpiar cualquier output previo
ob_clean();

try {
    include_once '../../conexion_grs_joya/conexion.php';
} catch (Exception $e) {
    enviarError('Error al incluir conexion.php: ' . $e->getMessage());
}

try {
    include_once '../includes/historial_acciones.php';
} catch (Exception $e) {
    enviarError('Error al incluir historial_acciones.php: ' . $e->getMessage());
}

try {
    include_once '../includes/funciones.php';
} catch (Exception $e) {
    enviarError('Error al incluir funciones.php: ' . $e->getMessage());
}

// Limpiar cualquier output de los includes
ob_clean();

try {
    $conexion = conectar_joya();
    if (!$conexion) {
        enviarError('Error de conexión a la base de datos');
    }
    mysqli_set_charset($conexion, 'utf8');
} catch (Exception $e) {
    enviarError('Error al conectar: ' . $e->getMessage());
}

// Leer datos del remitente
$usuario = $_POST['usuario'] ?? '';
if (empty($usuario)) {
    enviarError('Usuario no proporcionado', 400);
}

try {
    $stmt = mysqli_prepare($conexion, "SELECT correo, password FROM san_correo_sanidad WHERE codigo = ?");
    if (!$stmt) {
        enviarError('Error al preparar consulta: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 's', $usuario);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        enviarError('Error al ejecutar consulta: ' . mysqli_error($conexion));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $sender = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$sender) {
        enviarError('Configuración de correo no encontrada para el usuario: ' . $usuario);
    }
} catch (Exception $e) {
    enviarError('Error al obtener configuración de correo: ' . $e->getMessage());
}

// Datos del correo
$subject = $_POST['subject'] ?? '';
$body = $_POST['body'] ?? '';
$codigo = $_POST['codigo'] ?? '';
$para = [];

// Procesar destinatarios (pueden venir como para[] o para)
if (isset($_POST['para']) && is_array($_POST['para'])) {
    $para = $_POST['para'];
} elseif (isset($_POST['para'])) {
    $para = [$_POST['para']];
} elseif (isset($_POST['para[]'])) {
    if (is_array($_POST['para[]'])) {
        $para = $_POST['para[]'];
    } else {
        $para = [$_POST['para[]']];
    }
} 

// Validación
if (empty($subject) || empty($body) || empty($codigo) || empty($para) || !is_array($para)) {
    enviarError('Faltan datos obligatorios o destinatarios inválidos.', 400);
}

// Generar PDF
// Asegurar que funciones.php esté incluido antes de requerir pdf_generador
// porque pdf_generador.php lo necesita pero la ruta relativa puede fallar desde Flutter
if (!function_exists('formatearFecha')) {
    try {
        include_once '../includes/funciones.php';
    } catch (Exception $e) {
        error_log("Advertencia: No se pudo incluir funciones.php: " . $e->getMessage());
    }
}

try {
    require_once  '../modules/reportes/pdf_generador.php';
    $pdfContent = generarPDFReporte($codigo, $conexion);
    if (empty($pdfContent)) {
        enviarError('El PDF generado está vacío');
    }
} catch (Exception $e) {
    error_log("Error generando PDF: " . $e->getMessage());
    enviarError('Error al generar el PDF: ' . $e->getMessage());
} catch (Error $e) {
    error_log("Error fatal generando PDF: " . $e->getMessage());
    enviarError('Error fatal al generar el PDF: ' . $e->getMessage());
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
    
    // Registrar en historial de acciones
    $nom_usuario = $_POST['usuarioNombre'] ?? $usuario ?? 'Usuario Móvil';
    $datos_nuevos = [
        'asunto' => $subject,
        'cuerpo' => substr($body, 0, 200) . (strlen($body) > 200 ? '...' : ''),
        'codigo_reporte' => $codigo,
        'destinatarios' => $para,
        'num_adjuntos' => !empty($_FILES['archivos_adjuntos']['name'][0]) 
            ? count($_FILES['archivos_adjuntos']['name']) 
            : 0,
        'fecha_hora' => date('Y-m-d H:i:s')
    ];
    $datos_nuevos_json = json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE);
    
    try {
        registrarAccion(
            $usuario,
            $nom_usuario,
            'ENVIO_DE_CORREO',
            'san_fact_solicitud_cab',
            $codigo,
            null,
            $datos_nuevos_json,
            'Se envió el correo del reporte desde app móvil',
            'Flutter'
        );
    } catch (Exception $e) {
        error_log("Error al registrar historial de acciones (envío correo): " . $e->getMessage());
    }
    
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Correo enviado correctamente a todos los destinatarios.'], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error al enviar correo: " . $e->getMessage());
    $errorMsg = isset($mail) && method_exists($mail, 'ErrorInfo') ? $mail->ErrorInfo : $e->getMessage();
    enviarError('Error al enviar correo: ' . $errorMsg);
} catch (Error $e) {
    error_log("Error fatal al enviar correo: " . $e->getMessage());
    enviarError('Error fatal al enviar correo: ' . $e->getMessage());
}

// Limpieza
if (file_exists($tmpPdf)) {
    unlink($tmpPdf);
}
if ($conexion) {
    @mysqli_close($conexion);
}
