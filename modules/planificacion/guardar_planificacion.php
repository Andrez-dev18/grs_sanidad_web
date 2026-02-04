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

// Separar edad 00 (bebe) de las demás
$tieneEdadBebe = in_array('00', $edades);
$edadesNormales = array_filter($edades, function($e) { return $e != '00'; });

// Escapar granjas
$granjasEscapadas = array();
foreach ($granjas as $g) {
    $granjasEscapadas[] = "'" . mysqli_real_escape_string($conexion, $g) . "'";
}
$granjasIn = implode(',', $granjasEscapadas);

mysqli_autocommit($conexion, false);
$errores = array();

try {
    // === PRECARGAR nombres ===
    $nombresTipos = array();
    $nombresPaquetes = array();
    foreach ($analisisPorTipo as $tipoId => $lista) {
        $resTipo = mysqli_query($conexion, "SELECT nombre FROM san_dim_tipo_muestra WHERE codigo = " . (int)$tipoId);
        $nombresTipos[$tipoId] = '';
        if ($row = mysqli_fetch_assoc($resTipo)) {
            $nombresTipos[$tipoId] = $row['nombre'];
        }
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

    // === 1. PROCESAR EDADES NORMALES (01-45) ===
    if (!empty($edadesNormales)) {
        $edadesNormalesEsc = array();
        foreach ($edadesNormales as $e) {
            $edadesNormalesEsc[] = "'" . mysqli_real_escape_string($conexion, $e) . "'";
        }
        $edadesNormIn = implode(',', $edadesNormalesEsc);

        $sqlNormal = "
            SELECT DISTINCT
                LEFT(tcencos, 3) AS granja,
                RIGHT(tcencos, 3) AS campania,
                tcodint AS galpon,
                edad,
                fecha
            FROM cargapollo_proyeccion
            WHERE 
                LEFT(tcencos, 3) IN ($granjasIn)
                AND edad IN ($edadesNormIn)
                AND YEAR(fecha) = $year
            ORDER BY granja, campania, galpon, edad
        ";

        $resNormal = mysqli_query($conexion, $sqlNormal);
        if ($resNormal) {
            while ($combo = mysqli_fetch_assoc($resNormal)) {
                $granja = $combo['granja'];
                // Formato codRef: Granja(3) + Campaña(3) + Galpón(2) + Edad(2)
                $campania = str_pad($combo['campania'], 3, '0', STR_PAD_LEFT);
                $galpon = str_pad($combo['galpon'], 2, '0', STR_PAD_LEFT);
                $edad = str_pad($combo['edad'], 2, '0', STR_PAD_LEFT);
                $fecha = $combo['fecha'];
                $codRef = $granja . $campania . $galpon . $edad;

                insertarRegistros($conexion, $codRef, $fecha, $analisisPorTipo, $nombresTipos, $nombresPaquetes, $usuario, $errores);
            }
        }
    }

    // === 2. PROCESAR EDAD BEBÉ (00) ===
    if ($tieneEdadBebe) {
        // Buscar registros con edad '01'
        $sqlBebe = "
            SELECT DISTINCT
                LEFT(tcencos, 3) AS granja,
                RIGHT(tcencos, 3) AS campania,
                tcodint AS galpon,
                '00' AS edad,
                DATE_SUB(fecha, INTERVAL 1 DAY) AS fecha
            FROM cargapollo_proyeccion
            WHERE 
                LEFT(tcencos, 3) IN ($granjasIn)
                AND edad = '01'
                AND YEAR(fecha) = $year
            ORDER BY granja, campania, galpon
        ";

        $resBebe = mysqli_query($conexion, $sqlBebe);
        if ($resBebe) {
            while ($combo = mysqli_fetch_assoc($resBebe)) {
                $granja = $combo['granja'];
                // Formato codRef: Granja(3) + Campaña(3) + Galpón(2) + Edad(2)
                $campania = str_pad($combo['campania'], 3, '0', STR_PAD_LEFT);
                $galpon = str_pad($combo['galpon'], 2, '0', STR_PAD_LEFT);
                $edad = '00';
                $fecha = $combo['fecha'];
                $codRef = $granja . $campania . $galpon . $edad;

                // Validar que la fecha resultante sea del mismo año (opcional)
                if ($fecha && date('Y', strtotime($fecha)) == $year) {
                    insertarRegistros($conexion, $codRef, $fecha, $analisisPorTipo, $nombresTipos, $nombresPaquetes, $usuario, $errores);
                }
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

// === FUNCIÓN AUXILIAR PARA INSERTAR ===
function insertarRegistros($conexion, $codRef, $fecha, $analisisPorTipo, $nombresTipos, $nombresPaquetes, $usuario, &$errores) {
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
?>