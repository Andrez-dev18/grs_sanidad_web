<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$granja = $_GET['granja'] ?? '';
$numreg = (int)($_GET['numreg'] ?? 0);
$fectra_input = $_GET['fectra'] ?? '';

// Convertir fecha si viene en formato d/m/Y
$fechaObj = DateTime::createFromFormat('d/m/Y', $fectra_input);
if (!$fechaObj) {
    // Intentar formato Y-m-d directo
    $fechaObj = DateTime::createFromFormat('Y-m-d', $fectra_input);
}
$fectra = $fechaObj ? $fechaObj->format('Y-m-d') : '';

if (empty($granja) || $numreg === 0 || empty($fectra)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

$sql = "SELECT * FROM t_regnecropsia 
        WHERE tgranja = ? AND tnumreg = ? AND tfectra = ?
        ORDER BY tsistema, tnivel, tparametro, tid ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sis", $granja, $numreg, $fectra);
$stmt->execute();
$result = $stmt->get_result();

$registros = [];
$infoLote = [];
$evidenciasPorNivel = []; // Agrupar evidencias por nivel para evitar duplicados

if ($result->num_rows > 0) {
    $first = $result->fetch_assoc();
    
    // Información del lote (cabecera) — tfecreghorainicio/tfecreghorafin solo lectura al editar
    $infoLote = [
        'granja' => $first['tgranja'],
        'campania' => $first['tcampania'],
        'galpon' => $first['tgalpon'],
        'edad' => $first['tedad'],
        'fectra' => $first['tfectra'],
        'diagpresuntivo' => $first['tdiagpresuntivo'] ?? '',
        'tfecreghorainicio' => $first['tfecreghorainicio'] ?? '',
        'tfecreghorafin' => $first['tfecreghorafin'] ?? ''
    ];
    
    // Procesar primer registro
    $registros[] = $first;
    
    // Agrupar evidencias por nivel (solo una vez)
    if (!empty($first['evidencia']) && !isset($evidenciasPorNivel[$first['tnivel']])) {
        $evidenciasPorNivel[$first['tnivel']] = $first['evidencia'];
    }
    
    // Resto de registros
    while ($row = $result->fetch_assoc()) {
        $registros[] = $row;
        
        // Agrupar evidencias (solo la primera vez que aparece el nivel)
        if (!empty($row['evidencia']) && !isset($evidenciasPorNivel[$row['tnivel']])) {
            $evidenciasPorNivel[$row['tnivel']] = $row['evidencia'];
        }
    }
}

// Agregar evidencias agrupadas y normalizar claves/valores para el front (porcentajes como número)
foreach ($registros as &$reg) {
    $nivel = $reg['tnivel'];
    if (isset($evidenciasPorNivel[$nivel])) {
        $reg['evidencia'] = $evidenciasPorNivel[$nivel];
    }
    // Asegurar claves esperadas por JS y porcentajes como número (tolerar distinto casing de la BD)
    $get = function ($key) use ($reg) {
        $u = $key[0] === 't' ? 'T' . substr($key, 1) : $key;
        return $reg[$key] ?? $reg[$u] ?? 0;
    };
    $reg['tporcentaje1']     = (float)$get('tporcentaje1');
    $reg['tporcentaje2']     = (float)$get('tporcentaje2');
    $reg['tporcentaje3']     = (float)$get('tporcentaje3');
    $reg['tporcentaje4']     = (float)$get('tporcentaje4');
    $reg['tporcentaje5']     = (float)$get('tporcentaje5');
    $reg['tporcentajetotal'] = (float)$get('tporcentajetotal');
    $reg['tobservacion']     = trim((string)($reg['tobservacion'] ?? $reg['tObservacion'] ?? ''));
}
unset($reg);

echo json_encode([
    'success' => true,
    'granja' => $infoLote['granja'] ?? '',
    'campania' => $infoLote['campania'] ?? '',
    'galpon' => $infoLote['galpon'] ?? '',
    'edad' => $infoLote['edad'] ?? '',
    'fectra' => $infoLote['fectra'] ?? '',
    'diagpresuntivo' => $infoLote['diagpresuntivo'] ?? '',
    'tfecreghorainicio' => $infoLote['tfecreghorainicio'] ?? '',
    'tfecreghorafin' => $infoLote['tfecreghorafin'] ?? '',
    'registros' => $registros
]);

$stmt->close();
$conn->close();