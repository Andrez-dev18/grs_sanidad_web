<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

//ruta relativa a la conexion
include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit();
}


mysqli_autocommit($conexion, FALSE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $analisis = intval($_POST['analisis']);
    $tipo = trim(strtoupper($_POST['tipo']));

    if ($analisis <= 0 || empty($tipo)) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    // Insertar
    $query = "INSERT INTO san_dim_tiporesultado (analisis, tipo) VALUES ($analisis, '$tipo')";

    if (mysqli_query($conexion, $query)) {
        $nuevo_codigo = mysqli_insert_id($conexion); // Obtiene el ID del nuevo registro

        // Obtener los datos completos del nuevo registro para mostrarlo
        $queryDatos = "
            SELECT
                tr.codigo AS tr_codigo,
                tr.tipo AS tr_tipo,
                a.nombre AS analisis_nombre,
                p.codigo AS paquete_codigo,
                p.nombre AS paquete_nombre,
                tm.codigo AS muestra_codigo,
                tm.nombre AS muestra_nombre
            FROM san_dim_tiporesultado tr
            LEFT JOIN san_dim_analisis a ON tr.analisis = a.codigo
            LEFT JOIN san_dim_paquete p ON a.paquete = p.codigo
            LEFT JOIN san_dim_tipo_muestra tm ON p.tipoMuestra = tm.codigo
            WHERE tr.codigo = $nuevo_codigo
        ";

        $resultDatos = mysqli_query($conexion, $queryDatos);
        $nuevoRow = mysqli_fetch_assoc($resultDatos);

        echo json_encode([
            'success' => true,
            'nuevo' => $nuevoRow
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conexion)]);
    }
}
?>