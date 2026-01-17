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
while($row = $resSistemas->fetch_assoc()) {
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
while($row = $resHallazgos->fetch_assoc()) {
    $labelHallazgos[] = $row['hallazgo'];
    $dataHallazgos[] = $row['frecuencia'];
}

// 3. CANTIDAD DE NECROPSIAS POR GRANJA

// A) Desactivar modo estricto para evitar errores al agrupar
$conn->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

// B) CONSULTA CORREGIDA (Faltaba la coma después de tcencos)
$sqlGranjas = "SELECT tgranja, tcencos, COUNT(DISTINCT tnumreg) as cantidad 
               FROM t_regnecropsia 
               GROUP BY tgranja 
               ORDER BY cantidad DESC";

$resGranjas = $conn->query($sqlGranjas);

$dataGranjas = [];
$labelGranjas = [];

// Verificamos si la consulta se ejecutó bien
if ($resGranjas) {
    while($row = $resGranjas->fetch_assoc()) {
        
        // C) IMPORTANTE: Convertir a UTF-8. 
        // Si no haces esto y hay una 'Ñ' o tilde, json_encode devuelve todo vacío.
        $nombreCompleto = utf8_encode($row['tcencos']); 
        
        // Limpieza del nombre (Quitar lo que está después de "C=")
        if (strpos($nombreCompleto, 'C=') !== false) {
            $nombreLimpio = explode('C=', $nombreCompleto)[0];
        } else {
            $nombreLimpio = $nombreCompleto;
        }
        
        // Si por alguna razón el nombre está vacío, usamos el código como respaldo
        $etiqueta = trim($nombreLimpio);
        if ($etiqueta === '') {
            $etiqueta = $row['tgranja'];
        }

        $labelGranjas[] = $etiqueta;
        $dataGranjas[] = $row['cantidad'];
    }
} else {
    // Si falla la consulta, muestra el error real de MySQL para depurar
    die("Error SQL: " . $conn->error);
}

// ... (Tus consultas anteriores 1, 2 y 3) ...

// 4. DATOS PARA EL MAPA DE CALOR (HEATMAP)
// Cruzamos Galpón vs Sistema y promediamos el porcentaje de daño
$sqlHeatmap = "SELECT tgalpon, tsistema, AVG(tporcentajetotal) as severidad 
               FROM t_regnecropsia 
               WHERE tporcentajetotal > 0 
               GROUP BY tgalpon, tsistema";
$resHeatmap = $conn->query($sqlHeatmap);

$dataHeatmap = [];
while($row = $resHeatmap->fetch_assoc()) {
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
while($row = $resDin->fetch_assoc()) {
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
?>