<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

// Obtener granjas desde pi_dim_detalles y nombre desde regcencosgalpones
$sql = "
    SELECT d.codigo,
           COALESCE(NULLIF(r.nombre, ''), d.codigo) AS nombre,
           COALESCE(NULLIF(zs.zona, ''), '') AS zona,
           COALESCE(NULLIF(zs.subzona, ''), '') AS subzona
    FROM (
        SELECT DISTINCT TRIM(id_granja) AS codigo
        FROM pi_dim_detalles
        WHERE TRIM(id_granja) <> ''
          AND TRIM(id_granja) LIKE '6%'
          AND TRIM(id_granja) REGEXP '^[0-9]+$'
          AND TRIM(id_granja) NOT IN ('624', '640', '641')
    ) AS d
    LEFT JOIN (
        SELECT LEFT(TRIM(tcencos), 3) AS codigo, MAX(TRIM(tnomcen)) AS nombre
        FROM regcencosgalpones
        WHERE TRIM(tcencos) <> ''
          AND LEFT(TRIM(tcencos), 3) LIKE '6%'
          AND LEFT(TRIM(tcencos), 3) REGEXP '^[0-9]+$'
        GROUP BY LEFT(TRIM(tcencos), 3)
    ) AS r ON r.codigo = d.codigo
    LEFT JOIN (
        SELECT
            TRIM(det.id_granja) AS codigo,
            MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'ZONA' THEN TRIM(det.dato) END) AS zona,
            MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'SUBZONA' THEN TRIM(det.dato) END) AS subzona
        FROM pi_dim_detalles det
        INNER JOIN pi_dim_caracteristicas car ON car.id = det.id_caracteristica
        WHERE TRIM(det.id_granja) <> ''
          AND TRIM(det.id_granja) LIKE '6%'
          AND TRIM(det.id_granja) REGEXP '^[0-9]+$'
          AND UPPER(TRIM(car.nombre)) IN ('ZONA', 'SUBZONA')
        GROUP BY TRIM(det.id_granja)
    ) AS zs ON zs.codigo = d.codigo
    ORDER BY d.codigo
";

$res = $conn->query($sql);
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $codigo = trim((string)($row['codigo'] ?? ''));
        if ($codigo === '') {
            continue;
        }
        $data[] = [
            'codigo' => $codigo,
            'nombre' => trim((string)($row['nombre'] ?? $codigo)),
            'zona' => trim((string)($row['zona'] ?? '')),
            'subzona' => trim((string)($row['subzona'] ?? ''))
        ];
    }
}

echo json_encode(['success' => true, 'data' => $data]);
$conn->close();
