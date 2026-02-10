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

$sigla = isset($_GET['sigla']) ? trim($_GET['sigla']) : (isset($_POST['sigla']) ? trim($_POST['sigla']) : '');
$codTipo = isset($_GET['codTipo']) ? (int)$_GET['codTipo'] : (isset($_POST['codTipo']) ? (int)$_POST['codTipo'] : 0);

if (empty($sigla) && $codTipo > 0) {
    $st = $conn->prepare("SELECT sigla FROM san_dim_tipo_programa WHERE codigo = ?");
    $st->bind_param("i", $codTipo);
    $st->execute();
    $res = $st->get_result();
    if ($res && $row = $res->fetch_assoc() && !empty(trim($row['sigla'] ?? ''))) {
        $sigla = trim($row['sigla']);
    }
    $st->close();
}

if (empty($sigla)) {
    echo json_encode(['success' => false, 'message' => 'Indique sigla o codTipo.']);
    exit;
}

$sigla = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sigla));
if (strlen($sigla) > 10) {
    $sigla = substr($sigla, 0, 10);
}

/**
 * Genera el siguiente código para la sigla consultando san_fact_programa_cab.
 * Formato: SIGLA-0001, SIGLA-0002, ...
 */
function generarSiguienteCodigo($conn, $sigla) {
    $conn->query("LOCK TABLES san_fact_programa_cab WRITE");
    try {
        $prefijo = $sigla . '-';
        $stmt = $conn->prepare("SELECT codigo FROM san_fact_programa_cab WHERE codigo LIKE ? ORDER BY codigo DESC LIMIT 1");
        $patron = $prefijo . '%';
        $stmt->bind_param("s", $patron);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();

        $nuevo_numero = 1;
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $ultimo = $row['codigo'];
            $sufijo = substr($ultimo, strlen($prefijo));
            if (preg_match('/^(\d+)$/', $sufijo, $m)) {
                $nuevo_numero = (int)$m[1] + 1;
            }
        }

        $codigo = $prefijo . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);
        $conn->query("UNLOCK TABLES");
        return $codigo;
    } catch (Exception $e) {
        $conn->query("UNLOCK TABLES");
        throw $e;
    }
}

try {
    $codigo = generarSiguienteCodigo($conn, $sigla);
    echo json_encode(['success' => true, 'codigo' => $codigo]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
