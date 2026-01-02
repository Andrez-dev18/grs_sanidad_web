<?php
session_start();
include_once '../../../conexion_grs_joya/conexion.php';
include_once '../../includes/historial_acciones.php'; // Tu archivo con las funciones

$conn = conectar_joya();

header('Content-Type: application/json');

if (!isset($_POST['codEnvio'])) {
    echo json_encode(['success' => false, 'message' => 'Código de envío no proporcionado']);
    exit;
}

$codEnvio = $conn->real_escape_string($_POST['codEnvio']);

// Usuario
$codUsuario = $_SESSION['usuario'] ?? 'unknown';
$nomUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['usuario'] ?? 'Usuario desconocido';

// === 1. Recolectar todos los datos antes de borrar (para historial) ===
$datosPrevios = [];

// Cabecera
$res = $conn->query("SELECT * FROM san_fact_solicitud_cab WHERE codEnvio = '$codEnvio'");
if ($res && $res->num_rows > 0) {
    $datosPrevios['cabecera'] = $res->fetch_assoc();
}

// Detalle
$res = $conn->query("SELECT * FROM san_fact_solicitud_det WHERE codEnvio = '$codEnvio'");
$datosPrevios['detalle'] = [];
while ($row = $res->fetch_assoc()) {
    $datosPrevios['detalle'][] = $row;
}

// Resultados análisis
$res = $conn->query("SELECT * FROM san_fact_resultado_analisis WHERE codEnvio = '$codEnvio'");
$datosPrevios['resultados_analisis'] = [];
while ($row = $res->fetch_assoc()) {
    $datosPrevios['resultados_analisis'][] = $row;
}

// Archivos
$res = $conn->query("SELECT id, archRuta FROM san_fact_resultado_archivo WHERE codEnvio = '$codEnvio'");
$archivos = [];
while ($row = $res->fetch_assoc()) {
    $archivos[] = $row['archRuta'];
}
$datosPrevios['archivos'] = $archivos;

// Cuantitativos
$res = $conn->query("SELECT * FROM san_analisis_pollo_bb_adulto WHERE codigo_envio = '$codEnvio'");
$datosPrevios['cuantitativos'] = [];
while ($row = $res->fetch_assoc()) {
    $datosPrevios['cuantitativos'][] = $row;
}

//historial acciones
$res = $conn->query("SELECT * FROM san_dim_historial_resultados WHERE codEnvio = '$codEnvio'");
$datosPrevios['historial_resultados'] = [];
while ($row = $res->fetch_assoc()) {
    $datosPrevios['historial_resultados'][] = $row;
}

// Convertir a JSON bonito para historial
$datosPreviosJson = json_encode($datosPrevios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// === 2. Borrar archivos físicos ===
foreach ($archivos as $ruta) {
    if ($ruta && file_exists($ruta)) {
        @unlink($ruta); // @ para evitar warnings si falla
    }
}

// === 3. Borrar de la BD (orden seguro: dependientes primero) ===
$tablas = [
    'san_fact_resultado_archivo' => "codEnvio = '$codEnvio'",
    'san_fact_resultado_analisis' => "codEnvio = '$codEnvio'",
    'san_analisis_pollo_bb_adulto' => "codigo_envio = '$codEnvio'",
    'san_fact_solicitud_det' => "codEnvio = '$codEnvio'",
    'san_fact_solicitud_cab' => "codEnvio = '$codEnvio'",
    'san_dim_historial_resultados' => "codEnvio = '$codEnvio'"
];

$borrados = 0;
foreach ($tablas as $tabla => $condicion) {
    $sql = "DELETE FROM `$tabla` WHERE $condicion";
    if ($conn->query($sql)) {
        $borrados += $conn->affected_rows;
    }
}

// === 4. REGISTRAR EN HISTORIAL (usando tu función) ===
registrarAccionCRUD(
    'ELIMINACION_ENVIO_COMPLETO',
    $codUsuario,
    $nomUsuario,
    'san_fact_solicitud_cab',
    $codEnvio,
    $datosPreviosJson,  // Todos los datos borrados en JSON
    null,               // No hay datos nuevos
    "Eliminación completa del envío $codEnvio y todos sus registros asociados (detalle, resultados, archivos, cuantitativos)"
);

echo json_encode([
    'success' => true,
    'message' => "Envío $codEnvio eliminado correctamente. $borrados registros borrados.",
    'historial_guardado' => true
]);

$conn->close();
?>