<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_sanidad();

$q = "
    SELECT 
        a.codigo AS analisisCodigo,
        a.nombre AS analisisNombre,

        tm.nombre AS tipoMuestraNombre,

        tr.tipo AS resultadoTipo
    FROM com_tipo_resultado tr
    INNER JOIN com_analisis a
        ON a.codigo = tr.analisis
    LEFT JOIN com_paquete_muestra pm
        ON pm.codigo = a.paquete
    LEFT JOIN com_tipo_muestra tm
        ON tm.codigo = pm.tipoMuestra
    ORDER BY tm.nombre ASC, a.nombre ASC, tr.tipo ASC
";

$res = $conn->query($q);

$data = [];

while ($row = $res->fetch_assoc()) {

    $grupo = $row["tipoMuestraNombre"] ?: "SIN TIPO DE MUESTRA";
    $codigo = $row["analisisCodigo"];

    if (!isset($data[$grupo])) {
        $data[$grupo] = [];
    }

    if (!isset($data[$grupo][$codigo])) {
        $data[$grupo][$codigo] = [
            "codigo" => $codigo,
            "nombre" => $row["analisisNombre"],
            "resultados" => []
        ];
    }

    $data[$grupo][$codigo]["resultados"][] = $row["resultadoTipo"];
}

foreach ($data as $grupo => $items) {
    $data[$grupo] = array_values($items);
}

echo json_encode($data);
