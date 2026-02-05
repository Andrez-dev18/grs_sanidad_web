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

/**
 * Genera el siguiente código automático consultando la tabla san_plan_programa.
 * Formato: NEC-0 + (2 últimos dígitos del año) + (4 dígitos secuenciales)
 * Ejemplo: NEC-0260001, NEC-0260002, ...
 */
function generarCodigoNec($conn) {
    $conn->query("LOCK TABLES san_plan_programa WRITE");
    try {
        $anio = date('y'); // 2 últimos dígitos del año: "26" para 2026
        $patron = "NEC-0" . $anio . "%";
        $stmt = $conn->prepare("SELECT codigo FROM san_plan_programa WHERE codigo LIKE ? ORDER BY codigo DESC LIMIT 1");
        $stmt->bind_param("s", $patron);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $ultimo = $row['codigo'];
            $numero = (int) substr($ultimo, -4); // últimos 4 dígitos
            $nuevo_numero = $numero + 1;
        } else {
            $nuevo_numero = 1;
        }

        $codigo = "NEC-0" . $anio . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);
        $conn->query("UNLOCK TABLES");
        return $codigo;
    } catch (Exception $e) {
        $conn->query("UNLOCK TABLES");
        throw $e;
    }
}

try {
    $codigo = generarCodigoNec($conn);
    echo json_encode(['success' => true, 'codigo' => $codigo]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
