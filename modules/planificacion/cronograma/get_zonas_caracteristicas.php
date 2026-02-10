<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'zonas' => [], 'subzonas' => []]);
    exit;
}
include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
$zonas = [];
if ($conn) {
    $chk = @$conn->query("SHOW TABLES LIKE 'pi_dim_caracteristicas'");
    if ($chk && $chk->num_rows > 0) {
        // Buscar por nombre sin importar mayúsculas/espacios (p. ej. 'Zona', 'zona', ' Zona ')
        $sql = "SELECT listado_opciones FROM pi_dim_caracteristicas WHERE LOWER(TRIM(nombre)) = 'zona' LIMIT 1";
        $r = @$conn->query($sql);
        if ($r && $row = $r->fetch_assoc()) {
            $raw = isset($row['listado_opciones']) ? $row['listado_opciones'] : null;
            if ($raw !== null && trim((string)$raw) !== '') {
                $raw = trim((string)$raw);
                
                $opts = array_map('trim', explode(',', $raw));
                $zonas = array_values(array_unique(array_filter($opts, function ($v) { return $v !== ''; })));
            }
        }
    }
    $conn->close();
}
echo json_encode(['success' => true, 'zonas' => $zonas]);
