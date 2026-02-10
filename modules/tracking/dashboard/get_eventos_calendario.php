<?php
header('Content-Type: application/json');
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

// Filtro opcional por codEnvio (para el buscador)
$codEnvio = $_GET['codEnvio'] ?? '';

$whereExtra = '';
if (!empty($codEnvio)) {
    $whereExtra = " AND codEnvio = '" . $conn->real_escape_string($codEnvio) . "'";
}

if (!$start || !$end) {
    echo json_encode([]);
    exit;
}

// Consulta optimizada: solo trae registros con ubicación no nula y acciones específicas, en el rango de fechas
$sql = "
    SELECT codEnvio, ubicacion, fechaHoraRegistro, accion
    FROM san_dim_historial_resultados
    WHERE fechaHoraRegistro BETWEEN ? AND ?
    AND ubicacion IS NOT NULL
    AND (
        (ubicacion = 'GRS' AND accion = 'ENVIO_REGISTRADO')
        OR (ubicacion = 'Transporte' AND accion = 'Recepción de muestra')
        OR (ubicacion = 'Laboratorio' AND accion = 'Recepción de muestra por laboratorio')
    )
";

$params = [$start, $end];
$types = "ss";

if (!empty($codEnvio)) {
    $sql .= " AND codEnvio = ?";
    $params[] = $codEnvio;
    $types .= "s";
}

$sql .= " ORDER BY fechaHoraRegistro ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$events = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $codEnvio = $row['codEnvio'];
        $ubicacion = $row['ubicacion'];
        $fechaHora = $row['fechaHoraRegistro']; // Usa fecha completa con hora para eventos timed

        $color = '#3b82f6'; // Default azul (GRS)
        if ($ubicacion === 'Transporte') $color = '#f97316'; // Naranja
        if ($ubicacion === 'Laboratorio') $color = '#22c55e'; // Verde

        $events[] = [
            'title' => $codEnvio . ' - ' . $ubicacion,
            'start' => $fechaHora, // Incluye hora para vistas de tiempo
            'color' => $color,
            'extendedProps' => [
                'codEnvio' => $codEnvio, // Para modal futuro
                'ubicacion' => $ubicacion
            ]
        ];
    }
}

echo json_encode($events);
$conn->close(); // Buen hábito: cierra la conexión
