<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit();
}

$codEnvio = $_GET['codEnvio'] ?? '';
if (empty($codEnvio)) {
    echo json_encode(['error' => 'Código de envío requerido']);
    exit();
}

$puedeEditar = true;
$razones = [];

// Verificar en historial_resultados si hay resultados registrados
$stmt = mysqli_prepare($conexion, "
    SELECT COUNT(*) as total 
    FROM san_dim_historial_resultados 
    WHERE codEnvio = ? 
    AND accion IN ('registro_resultados_cualitativos', 'registro_resultados_cuantitativos')
");
mysqli_stmt_bind_param($stmt, "s", $codEnvio);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if ($row && $row['total'] > 0) {
    $puedeEditar = false;
    $razones[] = 'Ya se registraron resultados del laboratorio';
}

// Verificar en historial_acciones si se envió correo
$stmt2 = mysqli_prepare($conexion, "
    SELECT COUNT(*) as total 
    FROM san_dim_historial_acciones 
    WHERE registro_id = ? 
    AND accion = 'ENVIO_DE_CORREO'
");
mysqli_stmt_bind_param($stmt2, "s", $codEnvio);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);
$row2 = mysqli_fetch_assoc($result2);

if ($row2 && $row2['total'] > 0) {
    $puedeEditar = false;
    $razones[] = 'Ya se envió el correo del reporte';
}

mysqli_close($conexion);

echo json_encode([
    'puedeEditar' => $puedeEditar,
    'razones' => $razones
]);
?>
