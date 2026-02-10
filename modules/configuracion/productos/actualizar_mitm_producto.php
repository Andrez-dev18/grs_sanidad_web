<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$codigo = trim((string)($_POST['codigo'] ?? ''));
$tcodprove = trim((string)($_POST['tcodprove'] ?? ''));
$unidad = trim((string)($_POST['unidad'] ?? ''));
$dosis = trim((string)($_POST['dosis'] ?? ''));
$es_vacuna = (int)($_POST['es_vacuna'] ?? 0);
$cod_enfermedades = isset($_POST['cod_enfermedades']) && is_array($_POST['cod_enfermedades']) ? array_map('intval', array_filter($_POST['cod_enfermedades'])) : [];

if ($codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Código de producto requerido.']);
    exit;
}

// Actualizar relación vacuna-enfermedad (codVacuna = código mitm, o codProducto si migrado)
$chkRel = @$conn->query("SHOW TABLES LIKE 'san_rel_vacuna_enfermedad'");
if ($chkRel && $chkRel->fetch_assoc()) {
    $chkVac = @$conn->query("SHOW COLUMNS FROM san_rel_vacuna_enfermedad LIKE 'codVacuna'");
    $usarCodVacuna = $chkVac && $chkVac->fetch_assoc();
    $colVacuna = $usarCodVacuna ? 'codVacuna' : 'codProducto';
    $stDel = $conn->prepare("DELETE FROM san_rel_vacuna_enfermedad WHERE " . $colVacuna . " = ?");
    if ($stDel) {
        $stDel->bind_param("s", $codigo);
        $stDel->execute();
        $stDel->close();
    }
    if ($es_vacuna && count($cod_enfermedades) > 0) {
        $stIns = $conn->prepare("INSERT INTO san_rel_vacuna_enfermedad (" . $colVacuna . ", codEnfermedad) VALUES (?, ?)");
        if ($stIns) {
            foreach ($cod_enfermedades as $codEnf) {
                $stIns->bind_param("si", $codigo, $codEnf);
                $stIns->execute();
            }
            $stIns->close();
        }
    }
}

$chkUnidad = @$conn->query("SHOW COLUMNS FROM mitm LIKE 'unidad'");
$tieneUnidad = $chkUnidad && $chkUnidad->fetch_assoc();

if ($tieneUnidad) {
    $stmt = $conn->prepare("UPDATE mitm SET tcodprove = ?, unidad = ?, dosis = ? WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("ssss", $tcodprove, $unidad, $dosis, $codigo) && $stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
    }
} else {
    $stmt = $conn->prepare("UPDATE mitm SET tcodprove = ?, dosis = ? WHERE codigo = ?");
    if ($stmt && $stmt->bind_param("sss", $tcodprove, $dosis, $codigo) && $stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
    }
}
$conn->close();
