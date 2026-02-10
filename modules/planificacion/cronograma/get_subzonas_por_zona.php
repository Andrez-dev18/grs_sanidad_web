<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}
$zona = trim($_GET['zona'] ?? '');
if ($zona === '') {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

// Obtener id_caracteristica para Zona (1) y Subzona (2) por nombre por si varían
$idZona = 1;
$idSubzona = 2;
$r = @$conn->query("SELECT id, nombre FROM pi_dim_caracteristicas WHERE nombre IN ('Zona','Subzona')");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        if (strtoupper(trim($row['nombre'] ?? '')) === 'ZONA') $idZona = (int)$row['id'];
        if (strtoupper(trim($row['nombre'] ?? '')) === 'SUBZONA') $idSubzona = (int)$row['id'];
    }
}

$conn->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
$stmt = $conn->prepare("
    SELECT a.id_granja, a.id_galpon, a.dato
    FROM pi_dim_detalles AS a
    INNER JOIN pi_dim_caracteristicas AS b ON a.id_caracteristica = b.id
    INNER JOIN (
        SELECT id_granja, id_galpon
        FROM pi_dim_detalles AS a
        INNER JOIN pi_dim_caracteristicas AS b ON a.id_caracteristica = b.id
        WHERE id_caracteristica = ? AND dato = ?
    ) AS z ON a.id_granja = z.id_granja AND a.id_galpon = z.id_galpon
    AND a.id_caracteristica = ?
    GROUP BY a.id_granja, a.id_galpon, a.dato
");
if (!$stmt) {
    echo json_encode(['success' => false, 'data' => [], 'message' => $conn->error]);
    exit;
}
$stmt->bind_param("isi", $idZona, $zona, $idSubzona);
$stmt->execute();
$res = $stmt->get_result();
$filas = [];
$granjasCodigos = [];
while ($row = $res->fetch_assoc()) {
    $id_granja = trim($row['id_granja'] ?? '');
    $id_galpon = trim($row['id_galpon'] ?? '');
    $dato = trim($row['dato'] ?? '');
    if ($dato !== '') {
        $filas[] = ['id_granja' => $id_granja, 'id_galpon' => $id_galpon, 'dato' => $dato];
        if ($id_granja !== '') $granjasCodigos[$id_granja] = true;
    }
}
$stmt->close();

// Nombres de granja desde ccos (mismo origen que get_granjas)
$nombresGranja = [];
if (!empty($granjasCodigos)) {
    $placeholders = implode(',', array_fill(0, count($granjasCodigos), '?'));
    $st2 = $conn->prepare("SELECT codigo, nombre FROM ccos WHERE codigo IN ($placeholders)");
    if ($st2) {
        $st2->bind_param(str_repeat('s', count($granjasCodigos)), ...array_keys($granjasCodigos));
        $st2->execute();
        $r2 = $st2->get_result();
        while ($row = $r2->fetch_assoc()) {
            $nombresGranja[trim($row['codigo'])] = trim($row['nombre'] ?? '');
        }
        $st2->close();
    }
}

foreach ($filas as &$f) {
    $f['nombre_granja'] = $nombresGranja[$f['id_granja']] ?? $f['id_granja'];
}
unset($f);

$conn->close();
echo json_encode(['success' => true, 'data' => $filas]);
