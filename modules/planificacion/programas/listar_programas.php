<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Listar programas (edad va en detalle; edades se obtienen del detalle si no están en cab)
$chkEdad = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'edad'");
$tieneEdadCab = $chkEdad && $chkEdad->fetch_assoc();
if ($tieneEdadCab) {
    $sql = "SELECT codigo, nombre, codTipo, nomTipo, MAX(zona) AS zona, MAX(descripcion) AS descripcion, GROUP_CONCAT(edad ORDER BY edad ASC) AS edades FROM san_fact_programa_cab GROUP BY codigo, nombre, codTipo, nomTipo ORDER BY codigo DESC";
} else {
    $sql = "SELECT c.codigo, c.nombre, c.codTipo, c.nomTipo, c.zona, c.descripcion,
            (SELECT GROUP_CONCAT(DISTINCT d.edad ORDER BY d.edad ASC) FROM san_fact_programa_det d WHERE d.codPrograma = c.codigo AND d.edad IS NOT NULL) AS edades
            FROM san_fact_programa_cab c ORDER BY c.codigo DESC";
}
$result = $conn->query($sql);
$lista = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lista[] = [
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'],
            'codTipo' => (int)$row['codTipo'],
            'nomTipo' => $row['nomTipo'],
            'edades' => $row['edades'] ?? ''
        ];
    }
}

echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
