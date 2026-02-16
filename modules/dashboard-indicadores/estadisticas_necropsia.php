<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

// ====================================================================
// 1. CONSTRUCCIÓN DINÁMICA DE FILTROS (Granja y Galpón)
// ====================================================================

// Array para guardar las condiciones SQL
$filtros = [];

// A) Filtro de GRANJAS (Array de checkboxes)
// El JS enviará algo como: ?granjas=632,633,640
if (isset($_GET['granjas']) && !empty($_GET['granjas'])) {
    // Convertimos el string separado por comas en un array seguro
    $granjasInput = explode(',', $_GET['granjas']);
    
    // Sanatización básica para evitar inyección SQL
    $granjasLimpias = array_map(function($g) use ($conn) {
        return "'" . $conn->real_escape_string(trim($g)) . "'";
    }, $granjasInput);
    
    $listaGranjas = implode(',', $granjasLimpias);
    
    // Comparamos los primeros 3 dígitos del código de granja (tgranja)
    $filtros[] = "LEFT(tgranja, 3) IN ($listaGranjas)";
}

// B) Filtro de GALPÓN (Select simple)
if (isset($_GET['galpon']) && !empty($_GET['galpon'])) {
    $galpon = $conn->real_escape_string($_GET['galpon']);
    // Aseguramos que sea string de 2 dígitos o número según tu BD
    $filtros[] = "tgalpon = '$galpon'";
}

// C) Crear el String WHERE base
// Si hay filtros, inicia con " AND ...", si no, queda vacío.
$sqlWhereExtra = "";
if (count($filtros) > 0) {
    $sqlWhereExtra = " AND " . implode(' AND ', $filtros);
}

// NOTA: La mayoría de tus consultas originales tenían "WHERE tporcentajetotal > 0".
// Mantendremos esa condición base y le pegaremos nuestros filtros extra.
$whereSeveridad = " WHERE tporcentajetotal > 0 " . $sqlWhereExtra;

// Para el conteo de granjas (cantidad de necropsias), a veces se quieren contar todas
// incluso las que salieron sanas (porcentaje 0). 
// Si quieres filtrar TAMBIÉN por porcentaje > 0 en el conteo, usa $whereSeveridad.
// Si quieres contar todo lo que coincida con la granja/galpón, usa $whereConteo.
$whereConteo = " WHERE 1=1 " . $sqlWhereExtra; 


// ====================================================================
// 2. EJECUCIÓN DE CONSULTAS CON FILTRO APLICADO
// ====================================================================

// --- A. PREVALENCIA POR SISTEMA ---
$sqlSistemas = "SELECT tsistema, AVG(tporcentajetotal) as promedio 
                FROM t_regnecropsia 
                $whereSeveridad  
                GROUP BY tsistema 
                ORDER BY promedio DESC";
$resSistemas = $conn->query($sqlSistemas);

$dataSistemas = []; $labelSistemas = [];
while ($row = $resSistemas->fetch_assoc()) {
    $labelSistemas[] = $row['tsistema'];
    $dataSistemas[] = round($row['promedio'], 2);
}

// --- B. TOP 10 HALLAZGOS ---
$sqlHallazgos = "SELECT CONCAT(tnivel, ' - ', tparametro) as hallazgo, COUNT(DISTINCT tnumreg) as frecuencia 
                 FROM t_regnecropsia 
                 $whereSeveridad 
                 GROUP BY tnivel, tparametro 
                 ORDER BY frecuencia DESC 
                 LIMIT 10";
$resHallazgos = $conn->query($sqlHallazgos);

$dataHallazgos = []; $labelHallazgos = [];
while ($row = $resHallazgos->fetch_assoc()) {
    $labelHallazgos[] = $row['hallazgo'];
    $dataHallazgos[] = $row['frecuencia'];
}

// --- C. CANTIDAD POR GRANJA ---
$conn->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

// Usamos $whereConteo aquí para contar registros filtrados por granja/galpón
$sqlGranjas = "SELECT tgranja, tcencos, COUNT(DISTINCT tnumreg) as cantidad 
               FROM t_regnecropsia 
               $whereConteo
               GROUP BY tgranja 
               ORDER BY cantidad DESC";

$resGranjas = $conn->query($sqlGranjas);

$agrupadoPorCodigo = []; 
$nombresPorCodigo = [];

if ($resGranjas) {
    while ($row = $resGranjas->fetch_assoc()) {
        $codigoBase = substr($row['tgranja'], 0, 3);
        $nombreCompleto = utf8_encode($row['tcencos']);
        
        if (strpos($nombreCompleto, 'C=') !== false) {
            $nombreLimpio = explode('C=', $nombreCompleto)[0];
        } else {
            $nombreLimpio = $nombreCompleto;
        }

        $nombreLimpio = strtoupper(trim($nombreLimpio));
        $nombreLimpio = str_replace(['GRANJA', 'GJA. '], ['GJA.', 'GJA.'], $nombreLimpio);

        if (!isset($agrupadoPorCodigo[$codigoBase])) {
            $agrupadoPorCodigo[$codigoBase] = 0;
            $nombresPorCodigo[$codigoBase] = $nombreLimpio;
        }
        $agrupadoPorCodigo[$codigoBase] += $row['cantidad'];
    }
}

$dataGranjas = []; $labelGranjas = [];
arsort($agrupadoPorCodigo);
foreach ($agrupadoPorCodigo as $codigo => $cantidad) {
    $labelGranjas[] = $nombresPorCodigo[$codigo] ?: "Granja $codigo";
    $dataGranjas[] = $cantidad;
}

// --- D. HEATMAP (Galpón vs Sistema) ---
$sqlHeatmap = "SELECT tgalpon, tsistema, AVG(tporcentajetotal) as severidad 
               FROM t_regnecropsia 
               $whereSeveridad
               GROUP BY tgalpon, tsistema";
$resHeatmap = $conn->query($sqlHeatmap);
$dataHeatmap = [];
while ($row = $resHeatmap->fetch_assoc()) {
    $dataHeatmap[] = [
        'y' => "Galpón " . $row['tgalpon'], 
        'x' => $row['tsistema'],            
        'v' => round($row['severidad'], 2)  
    ];
}

// --- E. GRÁFICO DINÁMICO ---
$sqlDin = "SELECT tnivel, tparametro, AVG(tporcentajetotal) as promedio 
           FROM t_regnecropsia 
           $whereSeveridad
           GROUP BY tnivel, tparametro 
           ORDER BY tnivel, promedio DESC";
$resDin = $conn->query($sqlDin);
$dataNiveles = [];
while ($row = $resDin->fetch_assoc()) {
    $nivel = utf8_encode($row['tnivel']); 
    $param = utf8_encode($row['tparametro']);
    if (!isset($dataNiveles[$nivel])) {
        $dataNiveles[$nivel] = ['labels' => [], 'data' => []];
    }
    $dataNiveles[$nivel]['labels'][] = $param;
    $dataNiveles[$nivel]['data'][] = round($row['promedio'], 2);
}

// RESPUESTA JSON
echo json_encode([
    'sistemas'  => ['labels' => $labelSistemas, 'data' => $dataSistemas],
    'hallazgos' => ['labels' => $labelHallazgos, 'data' => $dataHallazgos],
    'granjas'   => ['labels' => $labelGranjas, 'data' => $dataGranjas],
    'heatmap'   => $dataHeatmap,   
    'dinamico'  => $dataNiveles    
]);

$conn->close();
?>