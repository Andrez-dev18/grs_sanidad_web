<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['active']) || empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesion no valida.']);
    exit();
}

include_once '../../../../conexion_grs/conexion.php';
$conexion = conectar_joya_mysqli();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexion.']);
    exit();
}

function es_admin_actual($conexion, $codigoSesion)
{
    $stmt = $conexion->prepare("SELECT rol_sanidad FROM usuario WHERE codigo = ? AND estado = 'A' LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param("s", $codigoSesion);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = false;
    if ($row = $res->fetch_assoc()) {
        $rol = strtoupper(trim((string)($row['rol_sanidad'] ?? '')));
        $ok = ($rol === 'ADMIN');
    }
    $stmt->close();
    return $ok;
}

function existe_campo_notificar($conexion)
{
    $q = @$conexion->query("SHOW COLUMNS FROM usuario LIKE 'notificar'");
    return ($q && $q->num_rows > 0);
}

function limpiar_telefono($telefono)
{
    return preg_replace('/\D/', '', (string)$telefono);
}

if (!es_admin_actual($conexion, $_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado. Solo ADMIN.']);
    exit();
}

if (!existe_campo_notificar($conexion)) {
    echo json_encode(['success' => false, 'message' => "No existe el campo usuario.notificar. Ejecute el SQL de migracion primero."]);
    exit();
}

$action = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));

if ($action === 'list') {
    $data = [];
    $sql = "SELECT codigo, nombre, COALESCE(telefo, '') AS telefono
            FROM usuario
            WHERE estado = 'A' AND IFNULL(notificar,0) = 1
            ORDER BY nombre ASC, codigo ASC";
    $res = @$conexion->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $data[] = [
                'codigo' => (string)($r['codigo'] ?? ''),
                'nombre' => (string)($r['nombre'] ?? ''),
                'telefono' => (string)($r['telefono'] ?? '')
            ];
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

if ($action === 'usuarios') {
    $data = [];
    $sql = "SELECT codigo, nombre, COALESCE(telefo,'') AS telefono, IFNULL(notificar,0) AS notificar
            FROM usuario
            WHERE estado = 'A'
            ORDER BY nombre ASC, codigo ASC";
    $res = @$conexion->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $data[] = [
                'codigo' => (string)($r['codigo'] ?? ''),
                'nombre' => (string)($r['nombre'] ?? ''),
                'telefono' => (string)($r['telefono'] ?? ''),
                'notificar' => (int)($r['notificar'] ?? 0)
            ];
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

$codigo = trim((string)($_POST['codigo'] ?? ''));
$telefono = limpiar_telefono($_POST['telefono'] ?? '');
$notificarRaw = $_POST['notificar'] ?? null;
$notificar = null;
if ($notificarRaw !== null && $notificarRaw !== '') {
    $notificar = (int)$notificarRaw;
    if ($notificar !== 0 && $notificar !== 1) {
        echo json_encode(['success' => false, 'message' => 'Valor de notificar invalido.']);
        exit();
    }
}

if ($action === 'create') {
    if ($codigo === '' || $telefono === '') {
        echo json_encode(['success' => false, 'message' => 'Codigo y telefono son obligatorios.']);
        exit();
    }
    if (!preg_match('/^\d{9,15}$/', $telefono)) {
        echo json_encode(['success' => false, 'message' => 'Telefono invalido (9 a 15 digitos).']);
        exit();
    }
    $stmt = $conexion->prepare("UPDATE usuario SET telefo = ?, notificar = 1 WHERE codigo = ? AND estado = 'A'");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'No se pudo preparar la operacion.']);
        exit();
    }
    $stmt->bind_param("ss", $telefono, $codigo);
    $ok = $stmt->execute();
    $rows = $stmt->affected_rows;
    $stmt->close();
    if ($ok && $rows >= 0) {
        echo json_encode(['success' => true, 'message' => 'Usuario autorizado para notificaciones.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo autorizar el usuario.']);
    }
    exit();
}

if ($action === 'update') {
    if ($codigo === '') {
        echo json_encode(['success' => false, 'message' => 'Codigo obligatorio.']);
        exit();
    }
    if ($telefono !== '' && !preg_match('/^\d{9,15}$/', $telefono)) {
        echo json_encode(['success' => false, 'message' => 'Telefono invalido (9 a 15 digitos).']);
        exit();
    }
    if ($notificar === null) {
        if ($telefono === '') {
            echo json_encode(['success' => false, 'message' => 'Telefono obligatorio.']);
            exit();
        }
        $stmt = $conexion->prepare("UPDATE usuario SET telefo = ? WHERE codigo = ? AND estado = 'A'");
    } else {
        $stmt = $conexion->prepare("UPDATE usuario SET telefo = ?, notificar = ? WHERE codigo = ? AND estado = 'A'");
    }
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'No se pudo preparar la operacion.']);
        exit();
    }
    if ($notificar === null) {
        $stmt->bind_param("ss", $telefono, $codigo);
    } else {
        $stmt->bind_param("sis", $telefono, $notificar, $codigo);
    }
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el usuario.']);
    }
    exit();
}

if ($action === 'delete') {
    if ($codigo === '') {
        echo json_encode(['success' => false, 'message' => 'Codigo obligatorio.']);
        exit();
    }
    $stmt = $conexion->prepare("UPDATE usuario SET notificar = 0 WHERE codigo = ? AND estado = 'A'");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'No se pudo preparar la operacion.']);
        exit();
    }
    $stmt->bind_param("s", $codigo);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Usuario removido de notificaciones.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo remover el usuario.']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Accion no valida.']);

