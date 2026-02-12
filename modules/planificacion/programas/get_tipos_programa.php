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

$defaultCampos = [
    'ubicacion' => 0, 'producto' => 0, 'proveedor' => 0, 'unidad' => 0, 'dosis' => 0, 'descripcion' => 0,
    'unidades' => 0, 'unidad_dosis' => 0, 'numero_frascos' => 0,
    'edad_aplicacion' => 0, 'area_galpon' => 0, 'cantidad_por_galpon' => 0
];
$lista = [];

// Detectar columnas opcionales (por si no se ha ejecutado el ALTER)
$tieneCampoDescripcion = false;
$chkDesc = @$conn->query("SHOW COLUMNS FROM san_dim_tipo_programa LIKE 'campoDescripcion'");
if ($chkDesc && $chkDesc->fetch_assoc()) $tieneCampoDescripcion = true;

$selectCols = "codigo, nombre, sigla,
        COALESCE(campoUbicacion, 0) AS campoUbicacion,
        COALESCE(campoProducto, 0) AS campoProducto,
        COALESCE(campoProveedor, 0) AS campoProveedor,
        COALESCE(campoUnidad, 0) AS campoUnidad,
        COALESCE(campoDosis, 0) AS campoDosis";
if ($tieneCampoDescripcion) $selectCols .= ",\n        COALESCE(campoDescripcion, 0) AS campoDescripcion";
$selectCols .= ",
        COALESCE(campoUnidades, 0) AS campoUnidades,
        COALESCE(campoUnidadDosis, 0) AS campoUnidadDosis,
        COALESCE(campoNumeroFrascos, 0) AS campoNumeroFrascos,
        COALESCE(campoEdadAplicacion, 0) AS campoEdadAplicacion,
        COALESCE(campoAreaGalpon, 0) AS campoAreaGalpon,
        COALESCE(campoCantidadPorGalpon, 0) AS campoCantidadPorGalpon";
$sqlFull = "SELECT " . $selectCols . " FROM san_dim_tipo_programa ORDER BY nombre";
$result = @$conn->query($sqlFull);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lista[] = [
            'codigo' => (int)$row['codigo'],
            'nombre' => $row['nombre'],
            'sigla' => $row['sigla'] ?? '',
            'campos' => [
                'ubicacion' => (int)($row['campoUbicacion'] ?? 0),
                'producto' => (int)($row['campoProducto'] ?? 0),
                'proveedor' => (int)($row['campoProveedor'] ?? 0),
                'unidad' => (int)($row['campoUnidad'] ?? 0),
                'dosis' => (int)($row['campoDosis'] ?? 0),
                'descripcion' => $tieneCampoDescripcion ? (int)($row['campoDescripcion'] ?? 0) : 0,
                'unidades' => (int)($row['campoUnidades'] ?? 0),
                'unidad_dosis' => (int)($row['campoUnidadDosis'] ?? 0),
                'numero_frascos' => (int)($row['campoNumeroFrascos'] ?? 0),
                'edad_aplicacion' => (int)($row['campoEdadAplicacion'] ?? 0),
                'area_galpon' => (int)($row['campoAreaGalpon'] ?? 0),
                'cantidad_por_galpon' => (int)($row['campoCantidadPorGalpon'] ?? 0)
            ]
        ];
    }
}
if (empty($lista)) {
    $result2 = $conn->query("SELECT codigo, nombre FROM san_dim_tipo_programa ORDER BY nombre");
    if ($result2) {
        while ($row = $result2->fetch_assoc()) {
            $lista[] = [
                'codigo' => (int)$row['codigo'],
                'nombre' => $row['nombre'] ?? '',
                'sigla' => '',
                'campos' => $defaultCampos
            ];
        }
    }
}

echo json_encode(['success' => true, 'data' => $lista]);
$conn->close();
