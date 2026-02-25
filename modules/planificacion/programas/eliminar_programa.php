<?php
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$codigo = trim((string)($_GET['codigo'] ?? $_POST['codigo'] ?? ''));
if ($codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Falta código de programa']);
    exit;
}

include_once '../../../../conexion_grs/conexion.php';
$conn = conectar_joya_mysqli();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$enUso = false;
$chk = @$conn->query("SHOW TABLES LIKE 'san_fact_cronograma'");
if ($chk && $chk->num_rows > 0) {
    $stmt = $conn->prepare("SELECT 1 FROM san_fact_cronograma WHERE codPrograma = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $res = $stmt->get_result();
        $enUso = $res && $res->num_rows > 0;
        $stmt->close();
    }
}

$conn->begin_transaction();
try {
    // Si el programa está asignado, eliminar primero sus asignaciones y el despliegue asociado.
    if ($enUso) {
        $numCronogramas = [];
        $chkDespliegue = @$conn->query("SHOW TABLES LIKE 'san_cronograma_despliegue'");
        if ($chkDespliegue && $chkDespliegue->num_rows > 0) {
            $stNum = $conn->prepare("SELECT DISTINCT numCronograma FROM san_fact_cronograma WHERE codPrograma = ?");
            if ($stNum) {
                $stNum->bind_param("s", $codigo);
                $stNum->execute();
                $resNum = $stNum->get_result();
                while ($row = $resNum->fetch_assoc()) {
                    $numCronogramas[] = (int)$row['numCronograma'];
                }
                $stNum->close();
            }
        }
        $stmtCrono = $conn->prepare("DELETE FROM san_fact_cronograma WHERE codPrograma = ?");
        if (!$stmtCrono) {
            throw new Exception('No se pudo preparar la eliminación de asignaciones.');
        }
        $stmtCrono->bind_param("s", $codigo);
        if (!$stmtCrono->execute()) {
            $stmtCrono->close();
            throw new Exception('No se pudieron eliminar las asignaciones del cronograma.');
        }
        $stmtCrono->close();
        foreach ($numCronogramas as $numC) {
            $stmtDesp = $conn->prepare("DELETE FROM san_cronograma_despliegue WHERE numCronograma = ?");
            if ($stmtDesp) {
                $stmtDesp->bind_param("i", $numC);
                @$stmtDesp->execute();
                $stmtDesp->close();
            }
        }
    }

    $stmtDet = $conn->prepare("DELETE FROM san_fact_programa_det WHERE codPrograma = ?");
    if (!$stmtDet) {
        throw new Exception('No se pudo preparar la eliminación del detalle.');
    }
    $stmtDet->bind_param("s", $codigo);
    if (!$stmtDet->execute()) {
        $stmtDet->close();
        throw new Exception('No se pudo eliminar el detalle del programa.');
    }
    $stmtDet->close();

    $stmtCab = $conn->prepare("DELETE FROM san_fact_programa_cab WHERE codigo = ?");
    if (!$stmtCab) {
        throw new Exception('No se pudo preparar la eliminación del programa.');
    }
    $stmtCab->bind_param("s", $codigo);
    if (!$stmtCab->execute()) {
        $stmtCab->close();
        throw new Exception('No se pudo eliminar la cabecera del programa.');
    }
    $eliminadasCab = $stmtCab->affected_rows;
    $stmtCab->close();

    if ($eliminadasCab < 1) {
        throw new Exception('No existe un programa con ese código.');
    }

    $conn->commit();
    $conn->close();
    $msg = $enUso
        ? 'Programa y asignaciones eliminados correctamente.'
        : 'Programa eliminado correctamente.';
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
