<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'data' => []]);
    exit();
}

include_once '../../../../conexion_grs/conexion.php';
$conexion = conectar_joya_mysqli();
if (!$conexion) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'data' => []]);
    exit();
}

header('Content-Type: application/json');

$tablaCab = 'san_fact_estandares_cab';
$tablaDet = 'san_fact_estandares_det';
$idRegistro = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Devolver un registro completo por id (para editar): { id, nombre, subprocesos: [ { subproceso, actividades: [ { actividad, filas } ] } ] }
if ($idRegistro > 0) {
    $stmtCab = $conexion->prepare("SELECT id, nombre FROM " . $tablaCab . " WHERE id = ?");
    if ($stmtCab && $stmtCab->bind_param("i", $idRegistro) && $stmtCab->execute()) {
        $resCab = $stmtCab->get_result();
        $rowCab = $resCab->fetch_assoc();
        $stmtCab->close();
        if (!$rowCab) {
            echo json_encode(['success' => false, 'data' => null]);
            exit();
        }
        $stmtDet = $conexion->prepare("SELECT id, subproceso, actividad, tipo, parametro, unidades, stdMin, stdMax FROM " . $tablaDet . " WHERE idEstandarCab = ? ORDER BY id ASC");
        if (!$stmtDet || !$stmtDet->bind_param("i", $idRegistro) || !$stmtDet->execute()) {
            if ($stmtDet) $stmtDet->close();
            echo json_encode(['success' => true, 'data' => ['id' => (int) $rowCab['id'], 'nombre' => $rowCab['nombre'] ?? '', 'subprocesos' => []]]);
            exit();
        }
        $resDet = $stmtDet->get_result();
        $subprocesos = [];
        $minIdSub = [];
        $minIdAct = [];
        while ($row = $resDet->fetch_assoc()) {
            $idRow = (int) ($row['id'] ?? 0);
            $sub = $row['subproceso'] ?? '';
            $act = $row['actividad'] ?? '';
            if (!isset($minIdSub[$sub]) || $idRow < $minIdSub[$sub]) $minIdSub[$sub] = $idRow;
            $keyAct = $sub . "\0" . $act;
            if (!isset($minIdAct[$keyAct]) || $idRow < $minIdAct[$keyAct]) $minIdAct[$keyAct] = $idRow;
            if (!isset($subprocesos[$sub])) $subprocesos[$sub] = [];
            if (!isset($subprocesos[$sub][$act])) $subprocesos[$sub][$act] = [];
            $subprocesos[$sub][$act][] = [
                'tipo' => $row['tipo'] ?? '',
                'parametro' => $row['parametro'] ?? '',
                'unidades' => $row['unidades'] ?? '',
                'stdMin' => $row['stdMin'] ?? '',
                'stdMax' => $row['stdMax'] ?? ''
            ];
        }
        $stmtDet->close();
        $subsOrdenados = array_keys($subprocesos);
        usort($subsOrdenados, function ($a, $b) use ($minIdSub) {
            $idA = $minIdSub[$a] ?? 999999;
            $idB = $minIdSub[$b] ?? 999999;
            return $idA - $idB;
        });
        $listaSub = [];
        foreach ($subsOrdenados as $sub) {
            $acts = $subprocesos[$sub];
            $actsOrdenadas = array_keys($acts);
            usort($actsOrdenadas, function ($a, $b) use ($sub, $minIdAct) {
                $keyA = $sub . "\0" . $a;
                $keyB = $sub . "\0" . $b;
                $idA = $minIdAct[$keyA] ?? 999999;
                $idB = $minIdAct[$keyB] ?? 999999;
                return $idA - $idB;
            });
            $listaAct = [];
            foreach ($actsOrdenadas as $act) {
                $listaAct[] = ['actividad' => $act, 'filas' => $acts[$act]];
            }
            $listaSub[] = ['subproceso' => $sub, 'actividades' => $listaAct];
        }
        echo json_encode(['success' => true, 'data' => [
            'id' => (int) $rowCab['id'],
            'nombre' => $rowCab['nombre'] ?? '',
            'subprocesos' => $listaSub
        ]]);
        exit();
    }
    if ($stmtCab) $stmtCab->close();
    echo json_encode(['success' => false, 'data' => null]);
    exit();
}

$todosCompletos = isset($_GET['todos']) && $_GET['todos'] == '1';
if ($todosCompletos) {
    $stmtCab = $conexion->prepare("SELECT id, nombre FROM " . $tablaCab . " ORDER BY id");
    if (!$stmtCab || !$stmtCab->execute()) {
        if ($stmtCab) $stmtCab->close();
        echo json_encode(['success' => false, 'data' => []]);
        exit();
    }
    $resCab = $stmtCab->get_result();
    $estandares = [];
    while ($rowCab = $resCab->fetch_assoc()) {
        $idCab = (int) $rowCab['id'];
        $stmtDet = $conexion->prepare("SELECT id, subproceso, actividad, tipo, parametro, unidades, stdMin, stdMax FROM " . $tablaDet . " WHERE idEstandarCab = ? ORDER BY id ASC");
        $subprocesos = [];
        $minIdSub = [];
        $minIdAct = [];
        if ($stmtDet && $stmtDet->bind_param("i", $idCab) && $stmtDet->execute()) {
            $resDet = $stmtDet->get_result();
            while ($row = $resDet->fetch_assoc()) {
                $idRow = (int) ($row['id'] ?? 0);
                $sub = $row['subproceso'] ?? '';
                $act = $row['actividad'] ?? '';
                if (!isset($minIdSub[$sub]) || $idRow < $minIdSub[$sub]) $minIdSub[$sub] = $idRow;
                $keyAct = $sub . "\0" . $act;
                if (!isset($minIdAct[$keyAct]) || $idRow < $minIdAct[$keyAct]) $minIdAct[$keyAct] = $idRow;
                if (!isset($subprocesos[$sub])) $subprocesos[$sub] = [];
                if (!isset($subprocesos[$sub][$act])) $subprocesos[$sub][$act] = [];
                $subprocesos[$sub][$act][] = [
                    'tipo' => $row['tipo'] ?? '',
                    'parametro' => $row['parametro'] ?? '',
                    'unidades' => $row['unidades'] ?? '',
                    'stdMin' => $row['stdMin'] ?? '',
                    'stdMax' => $row['stdMax'] ?? ''
                ];
            }
            $stmtDet->close();
        }
        $subsOrdenados = array_keys($subprocesos);
        usort($subsOrdenados, function ($a, $b) use ($minIdSub) {
            $idA = $minIdSub[$a] ?? 999999;
            $idB = $minIdSub[$b] ?? 999999;
            return $idA - $idB;
        });
        $listaSub = [];
        foreach ($subsOrdenados as $sub) {
            $acts = $subprocesos[$sub];
            $actsOrdenadas = array_keys($acts);
            usort($actsOrdenadas, function ($a, $b) use ($sub, $minIdAct) {
                $keyA = $sub . "\0" . $a;
                $keyB = $sub . "\0" . $b;
                $idA = $minIdAct[$keyA] ?? 999999;
                $idB = $minIdAct[$keyB] ?? 999999;
                return $idA - $idB;
            });
            $listaAct = [];
            foreach ($actsOrdenadas as $act) {
                $listaAct[] = ['actividad' => $act, 'filas' => $acts[$act]];
            }
            $listaSub[] = ['subproceso' => $sub, 'actividades' => $listaAct];
        }
        $estandares[] = ['id' => $idCab, 'nombre' => $rowCab['nombre'] ?? '', 'subprocesos' => $listaSub];
    }
    $stmtCab->close();
    echo json_encode(['success' => true, 'data' => $estandares]);
    exit();
}

$stmt = $conexion->prepare("SELECT id, nombre FROM " . $tablaCab . " ORDER BY id");
if ($stmt && $stmt->execute()) {
    $res = $stmt->get_result();
    $lista = [];
    while ($row = $res->fetch_assoc()) {
        $lista[] = ['id' => (int) $row['id'], 'nombre' => $row['nombre'] ?? ''];
    }
    $stmt->close();
    echo json_encode(['success' => true, 'data' => $lista]);
} else {
    if ($stmt) $stmt->close();
    echo json_encode(['success' => false, 'data' => []]);
}
