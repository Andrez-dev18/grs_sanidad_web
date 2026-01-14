<?php
header('Content-Type: application/json');
include_once '../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

if (!$conn) {
    echo json_encode(['error' => 'No se pudo conectar a la base de datos']);
    exit;
}

// Obtener parámetro de filtro: 'todos', 'activos', o vacío (activos por defecto)
$filtro = $_GET['filtro'] ?? 'activos';

// Construir condición WHERE según el filtro
$condicionSwac = '';
if ($filtro === 'activos') {
    $condicionSwac = "AND a.swac='A'";
}

$sql = "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));

SELECT a.codigo, a.nombre, IF(b.edad IS NULL, '0', b.edad) AS edad 
FROM ccos AS a 
LEFT JOIN (
    SELECT a.tcencos, DATEDIFF(NOW(), MIN(a.fec_ing))+1 AS edad 
    FROM maes_zonas AS a 
    WHERE a.tcodigo IN ('P0001001','P0001002')  
    GROUP BY tcencos
) AS b ON a.codigo = b.tcencos  
WHERE (LEFT(a.codigo,1) IN ('6','5') 
    AND RIGHT(a.codigo,3)<>'000' 
    $condicionSwac
    AND LENGTH(a.codigo)=6 
    AND LEFT(a.codigo,3)<>'650'
    AND LEFT(a.codigo,3) <= '667')
AND IF(b.edad IS NULL, '0', b.edad) <> '0'
ORDER BY a.nombre";

$result = $conn->multi_query($sql);

if ($result) {
    do {
        if ($res = $conn->store_result()) {
            $granjas = [];
            while ($row = $res->fetch_assoc()) {
                $granjas[] = [
                    'codigo' => $row['codigo'],
                    'nombre' => $row['nombre'],
                    'edad'   => $row['edad']
                ];
            }
            $res->free();
            echo json_encode($granjas);
            exit;
        }
    } while ($conn->next_result());
}

echo json_encode(['error' => 'Error en la consulta']);
$conn->close();
?>
