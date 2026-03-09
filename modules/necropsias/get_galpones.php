<?php
/**
 * Devuelve galpones con fec_ing_min (alineado con app Flutter get_cencos_galpones).
 * fec_ing_min = MIN(fec_ing) de maes_zonas por (tcencos, tcodint) para cálculo correcto de edad.
 * Edad = DATEDIFF(fecha_seleccionada, fec_ing_min) + 1
 * 
 * Flutter usa: c.codigo = m.tcencos (6 dígitos) y LEFT(c.codigo,3) = g.tcencos (regcencosgalpones).
 */
header('Content-Type: application/json');
include_once '../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();

date_default_timezone_set('America/Lima');
$conn->query("SET time_zone = 'America/Lima'");

$codigo = trim((string)($_GET['codigo'] ?? ''));
if (strlen($codigo) < 3) {
    echo json_encode([]);
    exit;
}

$prefijo = substr($codigo, 0, 3);

// Igual que Flutter: maes_zonas con tcencos = codigo (6 dígitos), regcencosgalpones con tcencos = prefijo (3 dígitos)
// INNER JOIN para solo devolver galpones con fec_ing_min válido (como en Flutter)
$sql = "SELECT g.tcodint, g.tnomcen, m.fec_ing_min
        FROM regcencosgalpones g
        INNER JOIN (
            SELECT tcencos, tcodint, DATE_FORMAT(MIN(fec_ing), '%Y-%m-%d') AS fec_ing_min
            FROM maes_zonas
            WHERE tcodigo IN ('P0001001','P0001002')
            GROUP BY tcencos, tcodint
        ) m ON m.tcencos = ? AND CAST(m.tcodint AS CHAR) = CAST(g.tcodint AS CHAR)
        WHERE g.tcencos = ?
        ORDER BY g.tcodint ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $codigo, $prefijo);
$stmt->execute();
$result = $stmt->get_result();

$galpones = [];
while ($row = $result->fetch_assoc()) {
    $galpones[] = [
        'galpon' => $row['tcodint'],
        'nombre' => trim($row['tnomcen'] ?? ''),
        'fec_ing_min' => $row['fec_ing_min'] ?? null
    ];
}

echo json_encode($galpones);
$stmt->close();
$conn->close();
?>