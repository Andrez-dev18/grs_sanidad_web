<?php
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit();
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión.']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$codigo = trim((string)($_POST['codigo'] ?? ''));
$tcodprove = trim((string)($_POST['tcodprove'] ?? ''));
$dosis = trim((string)($_POST['dosis'] ?? ''));
$es_vacuna = (int)($_POST['es_vacuna'] ?? 0);
$cod_enfermedades = isset($_POST['cod_enfermedades']) && is_array($_POST['cod_enfermedades']) ? array_map('intval', array_filter($_POST['cod_enfermedades'])) : [];

if ($action === 'create' || $action === 'update') {
    if ($codigo === '') {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar un producto.']);
        exit();
    }

    // Si es vacuna: san_rel_vacuna_enfermedad usa codVacuna (= código mitm) o codProducto si migrado
    if ($es_vacuna) {
        $chkVac = @$conexion->query("SHOW COLUMNS FROM san_rel_vacuna_enfermedad LIKE 'codVacuna'");
        $colVacuna = ($chkVac && $chkVac->fetch_assoc()) ? 'codVacuna' : 'codProducto';
        $stDel = $conexion->prepare("DELETE FROM san_rel_vacuna_enfermedad WHERE " . $colVacuna . " = ?");
        if ($stDel) {
            $stDel->bind_param("s", $codigo);
            $stDel->execute();
            $stDel->close();
        }
        $stIns = $conexion->prepare("INSERT INTO san_rel_vacuna_enfermedad (" . $colVacuna . ", codEnfermedad) VALUES (?, ?)");
        if ($stIns) {
            foreach ($cod_enfermedades as $codEnf) {
                $stIns->bind_param("si", $codigo, $codEnf);
                $stIns->execute();
            }
            $stIns->close();
        }
    }

    // Actualizar mitm: si existe columna producto_programa la ponemos a 1
    $chk = $conexion->query("SHOW COLUMNS FROM mitm LIKE 'producto_programa'");
    $tiene_producto_programa = $chk && $chk->fetch_assoc();
    if ($tiene_producto_programa) {
        $stmt = $conexion->prepare("UPDATE mitm SET tcodprove = ?, dosis = ?, producto_programa = 1 WHERE codigo = ?");
    } else {
        $stmt = $conexion->prepare("UPDATE mitm SET tcodprove = ?, dosis = ? WHERE codigo = ?");
    }
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conexion->error]);
        exit();
    }
    $stmt->bind_param("sss", $tcodprove, $dosis, $codigo);
    if (!$stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $conexion->error]);
        exit();
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => $action === 'create' ? 'Producto asignado correctamente.' : 'Producto actualizado correctamente.']);
} elseif ($action === 'delete') {
    if ($codigo === '') {
        echo json_encode(['success' => false, 'message' => 'Código de producto no válido.']);
        exit();
    }
    $chk = $conexion->query("SHOW COLUMNS FROM mitm LIKE 'producto_programa'");
    $tiene_producto_programa = $chk && $chk->fetch_assoc();
    $stmt = $conexion->prepare($tiene_producto_programa
        ? "UPDATE mitm SET tcodprove = '', producto_programa = 0 WHERE codigo = ?"
        : "UPDATE mitm SET tcodprove = '' WHERE codigo = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conexion->error]);
        exit();
    }
    $stmt->bind_param("s", $codigo);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Proveedor del producto quitado (tcodprove vacío).']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
}
