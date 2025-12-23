<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'message' => 'No autorizado'));
    exit();
}

include_once '../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(array('success' => false, 'message' => 'Error de conexión'));
    exit();
}

// Evitar salida no JSON
ob_start();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    ob_end_clean();
    echo json_encode(array('success' => false, 'message' => 'Datos inválidos'));
    exit();
}

$granjas = isset($input['granjas']) ? $input['granjas'] : array();
$edades = isset($input['edades']) ? $input['edades'] : array();
$analisisPorTipo = isset($input['analisisPorTipo']) ? $input['analisisPorTipo'] : array();
$usuario = isset($input['usuario']) ? $input['usuario'] : (isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'SYSTEM');
$year = (int)(isset($input['year']) ? $input['year'] : date('Y'));

if (empty($granjas) || empty($edades) || empty($analisisPorTipo)) {
    ob_end_clean();
    echo json_encode(array('success' => false, 'message' => 'Faltan datos'));
    exit();
}

// Escapar para SQL
$granjasEscapadas = array();
foreach ($granjas as $g) {
    $granjasEscapadas[] = "'" . mysqli_real_escape_string($conexion, $g) . "'";
}
$edadesEscapadas = array();
foreach ($edades as $e) {
    $edadesEscapadas[] = "'" . mysqli_real_escape_string($conexion, $e) . "'";
}

$granjasIn = implode(',', $granjasEscapadas);
$edadesIn = implode(',', $edadesEscapadas);

mysqli_autocommit($conexion, false);
$errores = array();

try {
    // === OBTENER COMBINACIONES ÚNICAS ===
    $sqlCombinaciones = "
        SELECT DISTINCT
            LEFT(tcencos, 3) AS granja,
            RIGHT(tcencos, 3) AS campania,
            tcodint AS galpon,
            edad,
            fecha
        FROM cargapollo_proyeccion
        WHERE 
            LEFT(tcencos, 3) IN ($granjasIn)
            AND edad IN ($edadesIn)
            AND YEAR(fecha) = $year
        ORDER BY granja, campania, galpon, edad
    ";

    $resCombinaciones = mysqli_query($conexion, $sqlCombinaciones);
    if (!$resCombinaciones) {
        throw new Exception("Error SQL: " . mysqli_error($conexion));
    }

    // === PRECARGAR nombres ===
    $nombresTipos = array();
    $nombresPaquetes = array();

    foreach ($analisisPorTipo as $tipoId => $lista) {
        // Tipo muestra
        $resTipo = mysqli_query($conexion, "SELECT nombre FROM san_dim_tipo_muestra WHERE codigo = " . (int)$tipoId);
        $nombresTipos[$tipoId] = '';
        if ($row = mysqli_fetch_assoc($resTipo)) {
            $nombresTipos[$tipoId] = $row['nombre'];
        }

        // Paquetes
        if (is_array($lista)) {
            foreach ($lista as $analisis) {
                $codPkg = isset($analisis['paquete']) ? $analisis['paquete'] : null;
                if ($codPkg && !isset($nombresPaquetes[$codPkg])) {
                    $resPkg = mysqli_query($conexion, "SELECT nombre FROM san_dim_paquete WHERE codigo = " . (int)$codPkg);
                    $nombresPaquetes[$codPkg] = '';
                    if ($rowPkg = mysqli_fetch_assoc($resPkg)) {
                        $nombresPaquetes[$codPkg] = $rowPkg['nombre'];
                    }
                }
            }
        }
    }

    // === PROCESAR ===
    while ($combo = mysqli_fetch_assoc($resCombinaciones)) {
        $granja = $combo['granja'];
        $galpon = $combo['galpon'];
        $campania = str_pad($combo['campania'], 2, '0', STR_PAD_LEFT);
        $edad = str_pad($combo['edad'], 2, '0', STR_PAD_LEFT); // edad a 2 dígitos
        $fecha = $combo['fecha'];
        $codRef = $granja . $galpon . $campania . $edad; // 3+2+3+2 = 10

        foreach ($analisisPorTipo as $tipoId => $listaAnalisis) {
            if (empty($listaAnalisis) || !is_array($listaAnalisis)) continue;

            $tipoNombre = isset($nombresTipos[$tipoId]) ? $nombresTipos[$tipoId] : '';

            foreach ($listaAnalisis as $analisis) {
                if (!is_array($analisis)) continue;
                $codAnalisis = (int)(isset($analisis['codigo']) ? $analisis['codigo'] : 0);
                $nomAnalisis = isset($analisis['nombre']) ? $analisis['nombre'] : '';
                $codPaquete = isset($analisis['paquete']) ? $analisis['paquete'] : null;
                $nomPaquete = isset($nombresPaquetes[$codPaquete]) ? $nombresPaquetes[$codPaquete] : '';

                if (!$codAnalisis || empty($nomAnalisis)) continue;

                $sql = "INSERT INTO san_planificacion (
                    codRef, fecToma, codMuestra, nomMuestra,
                    codAnalisis, nomAnalisis, codPaquete, nomPaquete,
                    usuarioRegistrador, fechaHoraRegistro
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                $stmt = mysqli_prepare($conexion, $sql);
                if (!$stmt) {
                    $errores[] = "Prepare falló: " . mysqli_error($conexion);
                    continue;
                }

                mysqli_stmt_bind_param(
                    $stmt,
                    'sssssssss',
                    $codRef,
                    $fecha,
                    $tipoId,
                    $tipoNombre,
                    $codAnalisis,
                    $nomAnalisis,
                    $codPaquete,
                    $nomPaquete,
                    $usuario
                );

                if (!mysqli_stmt_execute($stmt)) {
                    $errores[] = mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    if (!empty($errores)) {
        mysqli_rollback($conexion);
        ob_end_clean();
        echo json_encode(array('success' => false, 'message' => 'Errores: ' . implode('; ', array_unique($errores))));
    } else {
        mysqli_commit($conexion);
        ob_end_clean();
        echo json_encode(array('success' => true, 'message' => 'Planificación guardada exitosamente'));
    }

} catch (Exception $e) {
    mysqli_rollback($conexion);
    ob_end_clean();
    echo json_encode(array('success' => false, 'message' => 'Excepción: ' . $e->getMessage()));
}

mysqli_close($conexion);
?>