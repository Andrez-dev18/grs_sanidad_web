<?php
/**
 * Verifica si la combinación granja, campaña, galpón, edad existe en san_fact_cronograma
 * (programa NC%). Match exacto o con tolerancia en edad.
 * Devuelve la lista de matches para que el usuario seleccione con cuál relacionar la necropsia.
 *
 * Schema:
 * - san_fact_cronograma: granja (3 chars), campania (3 chars), galpon (1 char), edad (int), fechaEjecucion (fecha planificada)
 * - t_regnecropsia: tgranja (granja+campania concatenados = 6 chars), tgalpon (2 chars), tedad, tfectra (fecha)
 */
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'existe' => false, 'matches' => [], 'message' => 'No autorizado']);
    exit;
}

include_once __DIR__ . '/../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'existe' => false, 'matches' => [], 'message' => 'Error de conexión']);
    exit;
}

$granja   = trim((string)($_GET['granja'] ?? $_POST['granja'] ?? ''));
$campania = trim((string)($_GET['campania'] ?? $_POST['campania'] ?? ''));
$galpon   = trim((string)($_GET['galpon'] ?? $_POST['galpon'] ?? ''));
$edad     = trim((string)($_GET['edad'] ?? $_POST['edad'] ?? ''));

// Se requieren granja (o tgranja 6 chars) y galpón. Campaña se puede derivar de granja si viene como 6 chars
if ($granja === '' || $galpon === '') {
    echo json_encode(['success' => false, 'existe' => false, 'matches' => [], 'message' => 'Faltan granja o galpón']);
    exit;
}
if ($campania === '' && strlen($granja) < 6) {
    echo json_encode(['success' => false, 'existe' => false, 'matches' => [], 'message' => 'Faltan campaña']);
    exit;
}

$edadInt = ($edad !== '') ? (int)$edad : -1;
if ($edadInt < 0) $edadInt = -1; // -1 = no filtrar por edad (solo granja/campaña/galpón)

// Normalizar: san_fact_cronograma usa granja 3 chars, campania 3 chars
// Param "granja" puede venir como tgranja (6 chars = granja+campania) o 3 chars
$granjaNorm = strlen($granja) >= 3 ? substr($granja, 0, 3) : str_pad($granja, 3, '0', STR_PAD_LEFT);
$campaniaNorm = strlen($campania) >= 3 ? substr($campania, -3) : (strlen($granja) >= 6 ? substr($granja, -3) : str_pad($campania, 3, '0', STR_PAD_LEFT));
// Galpón: cronograma 1 char, t_regnecropsia 2 chars; normalizar ceros "01" = "1"
$galponNorm = ltrim($galpon, '0') ?: '0';

// Verificar que la tabla existe
$chkTable = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
if (!$chkTable || $chkTable->num_rows === 0) {
    echo json_encode(['success' => false, 'existe' => false, 'matches' => [], 'message' => 'Tabla san_fact_cronograma no encontrada']);
    $conn->close();
    exit;
}

// Columna fecha: puede ser fechaEjecucion (actual) o fecha (legacy)
$chkFecEjec = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fechaEjecucion'");
$chkFechaLegacy = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'fecha'");
$tieneFechaEjec = $chkFecEjec && $chkFecEjec->num_rows > 0;
$tieneFechaLegacy = $chkFechaLegacy && $chkFechaLegacy->num_rows > 0;
$colFecha = $tieneFechaEjec ? 'fechaEjecucion' : ($tieneFechaLegacy ? 'fecha' : '');

$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'edad'");
$chkTolerancia = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'tolerancia'");
$chkNumCronograma = @$conn->query("SHOW COLUMNS FROM san_fact_cronograma LIKE 'numCronograma'");
$tieneEdad = $chkEdad && $chkEdad->num_rows > 0;
$tieneTolerancia = $chkTolerancia && $chkTolerancia->num_rows > 0;
$tieneNumCronograma = $chkNumCronograma && $chkNumCronograma->num_rows > 0;

// Buscar matches: codPrograma NC%, granja, campania, galpon. SQL simplificado para MySQL 5.5+
$selectCols = "c.granja, c.campania, c.galpon, " . ($tieneEdad ? "COALESCE(c.edad, 0) AS edad" : "0 AS edad");
if ($colFecha !== '') {
    $selectCols .= ", DATE(c.`" . $colFecha . "`) AS fechaEjecucion";
} else {
    // Fallback seguro si la BD aún no tiene columna de fecha.
    $selectCols .= ", NULL AS fechaEjecucion";
}
$selectCols .= ", c.codPrograma, c.nomPrograma";
$selectCols .= ($tieneNumCronograma ? ", COALESCE(c.numCronograma, 0) AS numCronograma" : ", 0 AS numCronograma");

$sql = "SELECT " . $selectCols . " FROM san_fact_cronograma c WHERE c.codPrograma LIKE 'NC%' AND c.granja = ? AND c.campania = ? AND (c.galpon = ? OR c.galpon = ?)";

// Si edad viene informada
if ($tieneEdad && $edadInt >= 0) {
    if ($tieneTolerancia) {
        $sql .= " AND (c.edad = ? OR (? BETWEEN (COALESCE(c.edad,0) - IF(COALESCE(c.tolerancia,0) > 0, COALESCE(c.tolerancia,0), 1)) AND (COALESCE(c.edad,0) + IF(COALESCE(c.tolerancia,0) > 0, COALESCE(c.tolerancia,0), 1))))";
    } else {
        $sql .= " AND (c.edad = ? OR (? BETWEEN COALESCE(c.edad,0) - 1 AND COALESCE(c.edad,0) + 1))";
    }
}
if ($colFecha !== '') {
    $sql .= " ORDER BY c.`" . $colFecha . "` DESC, c.codPrograma ASC";
} else {
    $sql .= " ORDER BY c.codPrograma ASC";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $err = $conn->error ?: 'prepare failed';
    echo json_encode(['success' => false, 'existe' => false, 'matches' => [], 'message' => 'Error en consulta: ' . $err]);
    $conn->close();
    exit;
}

// Granja/campania: valor normalizado 3 chars. Galpón: "1" y "01" para match cronograma (1 char) vs t_regnecropsia (2 chars)
$galponAlt = (strlen($galponNorm) === 1 && $galponNorm !== '0') ? '0' . $galponNorm : $galponNorm;
$types = 'ssss';
$params = [$granjaNorm, $campaniaNorm, $galponNorm, $galponAlt];
if ($tieneEdad && $edadInt >= 0) {
    $types .= 'ii';
    $params[] = $edadInt;
    $params[] = $edadInt;
}
$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
    $err = $stmt->error ?: $conn->error ?: 'execute failed';
    echo json_encode(['success' => false, 'existe' => false, 'matches' => [], 'message' => 'Error en consulta: ' . $err]);
    $stmt->close();
    $conn->close();
    exit;
}
$res = $stmt->get_result();
$matches = [];
while ($row = $res->fetch_assoc()) {
    $g = trim($row['granja'] ?? '');
    $c = trim($row['campania'] ?? '');
    $gp = trim($row['galpon'] ?? '');
    $e = (int)($row['edad'] ?? 0);
    $fec = $row['fechaEjecucion'] ?? '';
    $cod = trim($row['codPrograma'] ?? '');
    $nom = trim($row['nomPrograma'] ?? '');
    $numC = (int)($row['numCronograma'] ?? 0);
    $tipo = $numC === 0 ? 'Eventual' : 'Planificado';
    $key = $g . '|' . $c . '|' . $gp . '|' . $e . '|' . $fec . '|' . $cod . '|' . $numC;
    $label = $g . ' | ' . $c . ' | Galpón ' . $gp . ' | Edad ' . $e;
    if ($fec) $label .= ' | ' . date('d/m/Y', strtotime($fec));
    $label .= ' | ' . $cod . ' (' . $tipo . ')';
    $matches[] = ['value' => $key, 'label' => $label];
}
$stmt->close();
$conn->close();

$existe = count($matches) > 0;

echo json_encode([
    'success' => true,
    'existe'  => $existe,
    'matches' => $matches,
    'message' => $existe ? 'Seleccione con cuál planificación relacionar' : 'No existe en cronograma. Regístrelo primero en 4.2.2 Registro Eventual.'
]);
