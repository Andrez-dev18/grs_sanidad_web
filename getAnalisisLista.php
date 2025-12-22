<?php
include_once '../conexion_grs_joya/conexion.php';
$conn = conectar_joya();

$q = "
    SELECT DISTINCT
        a.codigo AS analisisCodigo,
        a.nombre AS analisisNombre,
        tm.nombre AS tipoMuestraNombre
    FROM san_dim_tiporesultado tr
    INNER JOIN san_dim_analisis a ON a.codigo = tr.analisis
    INNER JOIN san_dim_analisis_paquete ap ON ap.analisis = a.codigo
    INNER JOIN san_dim_paquete p ON p.codigo = ap.paquete
    INNER JOIN san_dim_tipo_muestra tm ON tm.codigo = p.tipoMuestra
    ORDER BY tm.nombre ASC, a.nombre ASC
";

$res = $conn->query($q);

if (!$res) {
    die("Error SQL: " . $conn->error);
}

$data = [];

while ($row = $res->fetch_assoc()) {
    $grupo = $row["tipoMuestraNombre"] ?: "SIN TIPO DE MUESTRA";
    $codigo = $row["analisisCodigo"];

    if (!isset($data[$grupo])) {
        $data[$grupo] = [];
    }

    // Evitar duplicados (por si un análisis está en múltiples paquetes)
    if (!isset($data[$grupo][$codigo])) {
        $data[$grupo][$codigo] = [
            "codigo" => $codigo,
            "nombre" => $row["analisisNombre"]
        ];
    }
}

// Convertir a array indexado
foreach ($data as $grupo => $items) {
    $data[$grupo] = array_values($items);
}

echo json_encode($data);
?>