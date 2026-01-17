<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php'; // Ajusta tu ruta
$conn = conectar_joya();

// 1. OBTENER PREVALENCIA POR SISTEMA
// Promedio del porcentaje de afectación por sistema (para ver cuál está peor)
$sqlSistemas = "SELECT tsistema, AVG(tporcentajetotal) as promedio 
                FROM t_regnecropsia 
                WHERE tporcentajetotal > 0 
                GROUP BY tsistema 
                ORDER BY promedio DESC";
$resSistemas = $conn->query($sqlSistemas);

$dataSistemas = [];
$labelSistemas = [];
while ($row = $resSistemas->fetch_assoc()) {
    $labelSistemas[] = $row['tsistema'];
    $dataSistemas[] = round($row['promedio'], 2);
}

// 2. TOP 10 HALLAZGOS (PARAMETROS) MÁS FRECUENTES
// Cuenta cuántas veces aparece un parámetro con porcentaje > 0
$sqlHallazgos = "SELECT CONCAT(tnivel, ' - ', tparametro) as hallazgo, COUNT(DISTINCT tnumreg) as frecuencia 
                 FROM t_regnecropsia 
                 WHERE tporcentajetotal > 0 
                 GROUP BY tnivel, tparametro 
                 ORDER BY frecuencia DESC 
                 LIMIT 10";
$resHallazgos = $conn->query($sqlHallazgos);

$dataHallazgos = [];
$labelHallazgos = [];
while ($row = $resHallazgos->fetch_assoc()) {
    $labelHallazgos[] = $row['hallazgo'];
    $dataHallazgos[] = $row['frecuencia'];
}

// 3. CANTIDAD DE NECROPSIAS POR GRANJA
// A) Desactivar modo estricto
$conn->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

// B) Consulta: Traemos el código y el nombre
$sqlGranjas = "SELECT tgranja, tcencos, COUNT(DISTINCT tnumreg) as cantidad 
               FROM t_regnecropsia 
               GROUP BY tgranja 
               ORDER BY cantidad DESC";

$resGranjas = $conn->query($sqlGranjas);

// Arrays auxiliares para agrupar
$agrupadoPorCodigo = []; // Aquí sumaremos las cantidades: '632' => 50
$nombresPorCodigo = [];  // Aquí guardaremos el nombre bonito: '632' => 'GJA. GUADALUPE III'

if ($resGranjas) {
    while ($row = $resGranjas->fetch_assoc()) {

        // 1. OBTENER EL CÓDIGO BASE (IDENTIFICADOR REAL DE LA GRANJA)
        // Tomamos los primeros 3 dígitos (ej: '632' de '632148')
        // Si tu código es de longitud variable, ajusta esto, pero en tus capturas veo que son los 3 primeros.
        $codigoBase = substr($row['tgranja'], 0, 3);

        // 2. OBTENER Y LIMPIAR EL NOMBRE
        $nombreCompleto = utf8_encode($row['tcencos']);
        if (strpos($nombreCompleto, 'C=') !== false) {
            $nombreLimpio = explode('C=', $nombreCompleto)[0];
        } else {
            $nombreLimpio = $nombreCompleto;
        }

        // Normalización visual (Opcional pero recomendado para que se vea limpio)
        $nombreLimpio = strtoupper(trim($nombreLimpio));
        $nombreLimpio = str_replace('GRANJA', 'GJA.', $nombreLimpio); // Estandarizar prefijo
        $nombreLimpio = str_replace('GJA. ', 'GJA.', $nombreLimpio);  // Quitar espacio después del punto

        // 3. ACUMULAR CANTIDADES
        if (!isset($agrupadoPorCodigo[$codigoBase])) {
            $agrupadoPorCodigo[$codigoBase] = 0;
            // Guardamos el primer nombre que encontremos para este código
            $nombresPorCodigo[$codigoBase] = $nombreLimpio;
        }

        $agrupadoPorCodigo[$codigoBase] += $row['cantidad'];
    }
}

// 4. PREPARAR DATOS FINALES PARA EL GRÁFICO
$dataGranjas = [];
$labelGranjas = [];

// Ordenamos de mayor a menor cantidad para que la rosquilla se pinte ordenada
arsort($agrupadoPorCodigo);

foreach ($agrupadoPorCodigo as $codigo => $cantidad) {
    // Usamos el nombre que guardamos. Si por error no hay nombre, usamos el código.
    $nombreMostrar = $nombresPorCodigo[$codigo] ?: "Granja $codigo";

    $labelGranjas[] = $nombreMostrar;
    $dataGranjas[] = $cantidad;
}


// 4. DATOS PARA EL MAPA DE CALOR (HEATMAP)
// Cruzamos Galpón vs Sistema y promediamos el porcentaje de daño
$sqlHeatmap = "SELECT tgalpon, tsistema, AVG(tporcentajetotal) as severidad 
               FROM t_regnecropsia 
               WHERE tporcentajetotal > 0 
               GROUP BY tgalpon, tsistema";
$resHeatmap = $conn->query($sqlHeatmap);

$dataHeatmap = [];
while ($row = $resHeatmap->fetch_assoc()) {
    $dataHeatmap[] = [
        'y' => "Galpón " . $row['tgalpon'], // Eje Y
        'x' => $row['tsistema'],            // Eje X
        'v' => round($row['severidad'], 2)  // Valor (para el color/tamaño)
    ];
}

// 5. DATOS PARA EL GRÁFICO DINÁMICO (NIVELES Y PARÁMETROS)
// Obtenemos todo agrupado para procesarlo en JS
$sqlDin = "SELECT tnivel, tparametro, AVG(tporcentajetotal) as promedio 
           FROM t_regnecropsia 
           WHERE tporcentajetotal > 0 
           GROUP BY tnivel, tparametro 
           ORDER BY tnivel, promedio DESC";
$resDin = $conn->query($sqlDin);

$dataNiveles = [];
while ($row = $resDin->fetch_assoc()) {
    $nivel = utf8_encode($row['tnivel']); // Importante UTF8
    $param = utf8_encode($row['tparametro']);

    if (!isset($dataNiveles[$nivel])) {
        $dataNiveles[$nivel] = ['labels' => [], 'data' => []];
    }
    $dataNiveles[$nivel]['labels'][] = $param;
    $dataNiveles[$nivel]['data'][] = round($row['promedio'], 2);
}

// ACTUALIZAR EL JSON FINAL
echo json_encode([
    'sistemas'  => ['labels' => $labelSistemas, 'data' => $dataSistemas],
    'hallazgos' => ['labels' => $labelHallazgos, 'data' => $dataHallazgos],
    'granjas'   => ['labels' => $labelGranjas, 'data' => $dataGranjas],
    'heatmap'   => $dataHeatmap,   // Nuevo
    'dinamico'  => $dataNiveles    // Nuevo
]);


$conn->close();
