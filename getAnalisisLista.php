<?php
include_once 'conexion_grs_joya/conexion.php';
$conn = conectar_sanidad();

$q = "
    SELECT 
        a.codigo AS analisisCodigo,
        a.nombre AS analisisNombre,
        tm.codigo AS tipoCodigo,
        tm.nombre AS tipoNombre,
        tr.tipo AS resultadoTipo
    FROM com_analisis a
    INNER JOIN com_tipo_muestra tm 
        ON tm.codigo = a.tipoMuestra
    INNER JOIN com_tipo_resultado tr 
        ON tr.analisis = a.codigo
    ORDER BY tm.nombre ASC, a.nombre ASC, tr.tipo ASC
";

$res = $conn->query($q);

$data = [];
while ($row = $res->fetch_assoc()) {

    $grupo = $row["tipoNombre"];
    $codigo = $row["analisisCodigo"];

    if (!isset($data[$grupo])) {
        $data[$grupo] = [];
    }

    // Si es la primera vez que vemos este análisis, lo creamos
    if (!isset($data[$grupo][$codigo])) {
        $data[$grupo][$codigo] = [
            "codigo" => $codigo,
            "nombre" => $row["analisisNombre"],
            "resultados" => []
        ];
    }

    // Agregar tipo de resultado
    $data[$grupo][$codigo]["resultados"][] = $row["resultadoTipo"];
}

// Convertir subgrupos de objeto → array
foreach ($data as $grupo => $items) {
    $data[$grupo] = array_values($items);
}

echo json_encode($data);
