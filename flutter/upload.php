<?php
// --- CONFIGURACIÓN Y VALIDACIONES INICIALES ---
ob_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

date_default_timezone_set('America/Lima');
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../../conexion_grs_joya/conexion.php';
$authHeader = '';

if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} else {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
    }
}

if ($authHeader !== "Bearer " . API_TOKEN) {
    echo json_encode(["success" => false, "message" => "Token inválido"], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- CONEXIÓN ---
$conexion = conectar_joya();
if (!$conexion) {
    echo json_encode(["success" => false, "message" => "Error conexión"], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- LEER JSON ---
$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data || !is_array($data)) {
    echo json_encode(["success" => false, "message" => "JSON inválido"], JSON_UNESCAPED_UNICODE);
    exit;
}

$respuesta = [];

// --- PROCESAR TABLAS ---
foreach ($data as $tabla => $items) {
    switch ($tabla) {
        case 'vivo_aqp':
            $respuesta[$tabla] = procesarVivoAqp($conexion, $items);
            break;
        case 'vivo_provincia':
            $respuesta[$tabla] = procesarVivoProvincia($conexion, $items);
            break;
        case 'beneficio_provincia':
            $respuesta[$tabla] = procesarBeneficioProvincia($conexion, $items);
            break;
        case 'precio_vivo':
            $respuesta[$tabla] = procesarPrecioVivo($conexion, $items);
            break;
        case 'precio_trozado':
            $respuesta[$tabla] = procesarPrecioTrozado($conexion, $items);
            break;
        case 'tienda':
            $respuesta[$tabla] = procesarTienda($conexion, $items);
            break;
        case 'huevo':
            $respuesta[$tabla] = procesarHuevo($conexion, $items);
            break;
        case 'gallina':
            $respuesta[$tabla] = procesarGallina($conexion, $items);
            break;
        case 'alterno':
            $respuesta[$tabla] = procesarAlterno($conexion, $items);
            break;
        case 'entero_autoser':
            $respuesta[$tabla] = procesarEnteroAutoser($conexion, $items);
            break;
        case 'trozado_autoser':
            $respuesta[$tabla] = procesarTrozadoAutoser($conexion, $items);
            break;
        case 'cria_emprende':
            $respuesta[$tabla] = procesarCriadorEmprendedor($conexion, $items);
            break;
        case 'ing_emp_lima':
            $respuesta[$tabla] = procesarIngresoEmpresaLima($conexion, $items);
            break;
        case 'gallina_cd':
            $respuesta[$tabla] = procesarGallinaCD($conexion, $items);
            break;
        case 'productos_sustitutos':
            $respuesta[$tabla] = procesarProductosSustitutos($conexion, $items);
            break;
        case 'puesto_mercado_tienda':
            $respuesta[$tabla] = procesarPuestoMercadoTienda($conexion, $items);
            break;
        default:
            $respuesta[$tabla] = ["insertados" => 0, "duplicados" => 0, "mensaje" => "Tabla no reconocida"];
            break;
    }
}


if (ob_get_level()) {
    ob_clean();
}
echo json_encode([
    "success" => true,
    "message" => "Datos procesados correctamente",
    "detalle" => $respuesta
], JSON_UNESCAPED_UNICODE);

$conexion->close();

function procesarVivoAqp($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_vivo_aqp WHERE id = ?");
    $insert = $conexion->prepare("INSERT INTO com_db_vivo_aqp (
        id, fecha, mercado, empresa, condicion, proveedor,
        precioMayMin, precioMayMax, precioPubMin, precioPubMax,
        pesoMachoMin, pesoMachoMax, pesoHembMin, pesoHembMax,
        colorMin, colorMax, pesoMachoPromMin, pesoMachoPromMax,
        pesoHembraPromMin, pesoHembraPromMax, cantidad,
        usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $tipos = "sssssddddddddddddddssssss";

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['mercado'] ?? null,
            $item['empresa'] ?? null,
            $item['condicion'] ?? null,
            $item['proveedor'] ?? null,
            $item['precioMayMin'] ?? null,
            $item['precioMayMax'] ?? null,
            $item['precioPubMin'] ?? null,
            $item['precioPubMax'] ?? null,
            $item['pesoMachoMin'] ?? null,
            $item['pesoMachoMax'] ?? null,
            $item['pesoHembMin'] ?? null,
            $item['pesoHembMax'] ?? null,
            $item['colorMin'] ?? null,
            $item['colorMax'] ?? null,
            $item['pesoMachoPromMin'] ?? null,
            $item['pesoMachoPromMax'] ?? null,
            $item['pesoHembraPromMin'] ?? null,
            $item['pesoHembraPromMax'] ?? null,
            $item['cantidad'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];
        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
    }

    $check->close();
    $insert->close();
    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function procesarVivoProvincia($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    // Preparar statements
    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_vivo_provincia WHERE id = ?");
    if (!$check)
        return ["insertados" => 0, "duplicados" => 0, "error" => "Error prepare check: " . $conexion->error];

    $insert = $conexion->prepare("INSERT INTO com_db_vivo_provincia (
        id, fecha, provincia, proveedor, tipo, cantidad,
        precioMayCarMin, precioMayCarMax, precioMayBraMin, precioMayBraMax,
        precioPubMin, precioPubMax, pesoMachoPromMin, pesoMachoPromMax,
        pesoHembraPromMin, pesoHembraPromMax, pesoBrasaPromMin, pesoBrasaPromMax,
        colorMin, colorMax, fechaHoraRegistro, usuarioRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$insert) {
        $check->close();
        return ["insertados" => 0, "duplicados" => 0, "error" => "Error prepare insert: " . $conexion->error];
    }

    // Tipos: usamos 's' por seguridad; ajustar después si quieres tipos concretos
    $numParams = 24;
    $tipos = str_repeat('s', $numParams);

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $fechaHoraRegistro = $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s');
        $usuarioRegistro = $item['usuarioRegistro'] ?? 'app_movil';
        $usuarioTransferencia = $item['usuarioTransferencia'] ?? 'app_movil';
        $fechaHoraTransferencia = date('Y-m-d H:i:s');

        // Verificar duplicado
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        // Preparar parámetros
        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['provincia'] ?? null,
            $item['proveedor'] ?? null,
            $item['tipo'] ?? null,
            $item['cantidad'] ?? null,
            $item['precioMayCarMin'] ?? null,
            $item['precioMayCarMax'] ?? null,
            $item['precioMayBraMin'] ?? null,
            $item['precioMayBraMax'] ?? null,
            $item['precioPubMin'] ?? null,
            $item['precioPubMax'] ?? null,
            $item['pesoMachoPromMin'] ?? null,
            $item['pesoMachoPromMax'] ?? null,
            $item['pesoHembraPromMin'] ?? null,
            $item['pesoHembraPromMax'] ?? null,
            $item['pesoBrasaPromMin'] ?? null,
            $item['pesoBrasaPromMax'] ?? null,
            $item['colorMin'] ?? null,
            $item['colorMax'] ?? null,
            $fechaHoraRegistro,
            $usuarioRegistro,
            $usuarioTransferencia,
            $fechaHoraTransferencia
        ];

        // Bind y ejecutar
        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
        // si falla, puedes loggear con $insert->error
    }

    $check->close();
    $insert->close();

    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function procesarBeneficioProvincia($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_beneficio_provincia WHERE id = ?");
    if (!$check)
        return ["insertados" => 0, "duplicados" => 0, "error" => "Error prepare check: " . $conexion->error];

    $insert = $conexion->prepare("INSERT INTO com_db_beneficio_provincia (
        id, fecha, provincia, proveedor, cantidad,
        precioMayEntero, precioMayMejorado, precioMayCarcasa,
        precioPubMejorado, precioPubCarcasa, pesoPromMenor, pesoPromMayor,
        colorMin, colorMax, fechaHoraRegistro, usuarioRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$insert) {
        $check->close();
        return ["insertados" => 0, "duplicados" => 0, "error" => "Error prepare insert: " . $conexion->error];
    }

    // Número de parámetros = 18
    $numParams = 18;
    $tipos = str_repeat('s', $numParams);

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $fechaHoraRegistro = $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s');
        $usuarioRegistro = $item['usuarioRegistro'] ?? 'app_movil';
        $usuarioTransferencia = $item['usuarioTransferencia'] ?? 'app_movil';
        $fechaHoraTransferencia = date('Y-m-d H:i:s');

        // Verificar duplicado
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['provincia'] ?? null,
            $item['proveedor'] ?? null,
            $item['cantidad'] ?? null,
            $item['precioMayEntero'] ?? null,
            $item['precioMayMejorado'] ?? null,
            $item['precioMayCarcasa'] ?? null,
            $item['precioPubMejorado'] ?? null,
            $item['precioPubCarcasa'] ?? null,
            $item['pesoPromMenor'] ?? null,
            $item['pesoPromMayor'] ?? null,
            $item['colorMin'] ?? null,
            $item['colorMax'] ?? null,
            $fechaHoraRegistro,
            $usuarioRegistro,
            $usuarioTransferencia,
            $fechaHoraTransferencia
        ];

        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
    }

    $check->close();
    $insert->close();

    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function procesarPrecioVivo($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_precio_vivo WHERE id = ?");
    $insert = $conexion->prepare("INSERT INTO com_db_precio_vivo (
        id, fecha, empresa,
        precioMinCentroAcopio, precioMaxCentroAcopio,
        precioMinMayoristaReparto, precioMaxMayoristaReparto,
        precioPubMin, precioPubMax,
        usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $tipos = "ssdddddddssss";

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['empresa'] ?? null,
            $item['precioMinCentroAcopio'] ?? null,
            $item['precioMaxCentroAcopio'] ?? null,
            $item['precioMinMayoristaReparto'] ?? null,
            $item['precioMaxMayoristaReparto'] ?? null,
            $item['precioPubMin'] ?? null,
            $item['precioPubMax'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];
        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
    }

    $check->close();
    $insert->close();
    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function procesarPrecioTrozado($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_precio_trozado WHERE id = ?");
    $insert = $conexion->prepare("INSERT INTO com_db_precio_trozado (
        id, fecha, empresa,
        corte, precio,
        usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $numParams = 9;
    $tipos = str_repeat('s', $numParams);

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['empresa'] ?? null,
            $item['corte'] ?? null,
            $item['precio'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];
        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
    }

    $check->close();
    $insert->close();
    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function procesarTienda($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_tienda WHERE id = ?");
    $insert = $conexion->prepare("INSERT INTO com_db_tienda (
        id, fecha, empresa,
        tipo, precio,
        usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $numParams = 9;
    $tipos = str_repeat('s', $numParams);

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['empresa'] ?? null,
            $item['tipo'] ?? null,
            $item['precio'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];

        $insert->bind_param($tipos, ...$params);
        if ($insert->execute()) {
            $insertados++;

            // 🔄 Update codpro desde com_tipo_tienda
            $queryUpdate = "
                UPDATE com_db_tienda AS a
                INNER JOIN com_tipo_tienda AS b ON a.tipo = b.codigo
                SET a.codpro = b.codpro
                WHERE a.id = '$id'
            ";
            $conexion->query($queryUpdate);
        }
    }

    $check->close();
    $insert->close();

    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function procesarHuevo($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_huevo WHERE id = ?");
    $insert = $conexion->prepare("INSERT INTO com_db_huevo (
        id, fecha, provincia,tipo,mercado,proveedor,
        precioMayMin, precioMayMax,precioPubMin,precioPubMax,
        usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $numParams = 14;
    $tipos = str_repeat('s', $numParams);

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['provincia'] ?? null,
            $item['tipo'] ?? null,
            $item['mercado'] ?? null,
            $item['proveedor'] ?? null,
            $item['precioMayMin'] ?? null,
            $item['precioMayMax'] ?? null,
            $item['precioPubMin'] ?? null,
            $item['precioPubMax'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];

        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
    }

    $check->close();
    $insert->close();

    return ["insertados" => $insertados, "duplicados" => $duplicados];
}
function procesarGallina($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_gallina WHERE id = ?");
    $insert = $conexion->prepare("INSERT INTO com_db_gallina (
        id, fecha, tipo,
        precioMayMin, precioMayMax, precioPubMin, precioPubMax,
        cantidad,
        usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $numParams = 12;
    $tipos = str_repeat('s', $numParams);

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['tipo'] ?? null,
            $item['precioMayMin'] ?? null,
            $item['precioMayMax'] ?? null,
            $item['precioPubMin'] ?? null,
            $item['precioPubMax'] ?? null,
            $item['cantidad'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];
        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
    }

    $check->close();
    $insert->close();
    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function procesarAlterno($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_alterno WHERE id = ?");
    $insert = $conexion->prepare("INSERT INTO com_db_alterno (
        id, fecha, provincia,mercado,tipo,
        precioMin, precioMax, 
        usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $numParams = 11;
    $tipos = str_repeat('s', $numParams);

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['provincia'] ?? null,
            $item['mercado'] ?? null,
            $item['tipo'] ?? null,
            $item['precioMin'] ?? null,
            $item['precioMax'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];
        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
    }

    $check->close();
    $insert->close();
    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function procesarEnteroAutoser($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_entero_autoser WHERE id = ?");
    $insert = $conexion->prepare("INSERT INTO com_db_entero_autoser (
        id, fecha, proveedor,
        precioMayMin, precioMayMax, precioPubMin, precioPubMax,
        color, cantidad,
        usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $numParams = 13;
    $tipos = str_repeat('s', $numParams);

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['proveedor'] ?? null,
            $item['precioMayMin'] ?? null,
            $item['precioMayMax'] ?? null,
            $item['precioPubMin'] ?? null,
            $item['precioPubMax'] ?? null,
            $item['color'] ?? null,
            $item['cantidad'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];
        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
    }

    $check->close();
    $insert->close();
    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function procesarTrozadoAutoser($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_trozado_autoser WHERE id = ?");
    $insert = $conexion->prepare("INSERT INTO com_db_trozado_autoser (
        id, fecha, corte,
        precioSuper, precioPlazaVea, 
        precioTottus, precioMetro, 
        precioTiendaPalomar, precioTiendaRicoPollo, 
        precioAvelino,
        usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $numParams = 14;
    $tipos = str_repeat('s', $numParams);

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['corte'] ?? null,
            $item['precioSuper'] ?? null,
            $item['precioPlazaVea'] ?? null,
            $item['precioTottus'] ?? null,
            $item['precioMetro'] ?? null,
            $item['precioTiendaPalomar'] ?? null,
            $item['precioTiendaRicoPollo'] ?? null,
            $item['precioAvelino'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];
        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
    }

    $check->close();
    $insert->close();
    return ["insertados" => $insertados, "duplicados" => $duplicados];
}
function procesarCriadorEmprendedor($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_criador_emprendedor WHERE id = ?");
    $insert = $conexion->prepare("INSERT INTO com_db_criador_emprendedor (
        id, fecha, provincia, proveedor, tipo, 
        cantidad, precio, observaciones, 
        usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $numParams = 12;
    $tipos = str_repeat('s', $numParams);

    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['provincia'] ?? null,
            $item['proveedor'] ?? null,
            $item['tipo'] ?? null,
            $item['cantidad'] ?? null,
            $item['precio'] ?? null,
            $item['observaciones'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];
        $insert->bind_param($tipos, ...$params);
        if ($insert->execute())
            $insertados++;
    }

    $check->close();
    $insert->close();
    return ["insertados" => $insertados, "duplicados" => $duplicados];
}
function procesarIngresoEmpresaLima($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_ingre_emp_lima WHERE id = ?");
    if (!$check)
        return ["error" => "Error prepare check: " . $conexion->error];

    $insert = $conexion->prepare("
        INSERT INTO com_db_ingre_emp_lima (
            id, fecha, empresa,
            unidad_fija, unidad_movil, kilos, peso_promedio,
            precio_campo, precio_granja, soles, participacion,
            usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?)
    ");
    if (!$insert) {
        $check->close();
        return ["error" => "Error prepare insert: " . $conexion->error];
    }

    $tipos = "ssidddddddssss";
    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['empresa'] ?? null,
            $item['unidad_fija'] ?? null,
            $item['unidad_movil'] ?? null,
            $item['kilos'] ?? null,
            $item['peso_promedio'] ?? null,
            $item['precio_campo'] ?? null,
            $item['precio_granja'] ?? null,
            $item['soles'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];

        $insert->bind_param($tipos, ...$params);
        if ($insert->execute()) {
            $insertados++;

            $fecha = $item['fecha'] ?? null;
            if ($fecha) {
                $totalQuery = $conexion->prepare("SELECT SUM(soles) as total FROM com_db_ingre_emp_lima WHERE fecha = ?");
                $totalQuery->bind_param("s", $fecha);
                $totalQuery->execute();
                $result = $totalQuery->get_result();
                $row = $result->fetch_assoc();
                $totalSoles = $row['total'] ?? 0;
                $totalQuery->close();

                if ($totalSoles > 0) {
                    $updateStmt = $conexion->prepare("
                        UPDATE com_db_ingre_emp_lima 
                        SET participacion = ROUND((soles / ?) * 100, 2)
                        WHERE fecha = ?
                    ");
                    $updateStmt->bind_param("ds", $totalSoles, $fecha);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            }
        }
    }

    $check->close();
    $insert->close();

    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function procesarGallinaCD($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_gallina_cd WHERE id = ?");
    if (!$check)
        return ["error" => "Error prepare check: " . $conexion->error];

    $insert = $conexion->prepare("
        INSERT INTO com_db_gallina_cd (
            id, fecha, tipo,
            unidades, kilos, peso,
            precio_granja_1, precio_granja_2, precio_granja_3, precio_granja_4, precio_granja_5,
            precio_cd_1, precio_cd_2, precio_cd_3, precio_cd_4, precio_cd_5,
            usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$insert) {
        $check->close();
        return ["error" => "Error prepare insert: " . $conexion->error];
    }

    $tipos = "ssiiddddddddddddssss";
    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['tipo'] ?? null,
            $item['unidades'] ?? null,
            $item['kilos'] ?? null,
            $item['peso'] ?? null,
            $item['precio_granja_1'] ?? null,
            $item['precio_granja_2'] ?? null,
            $item['precio_granja_3'] ?? null,
            $item['precio_granja_4'] ?? null,
            $item['precio_granja_5'] ?? null,
            $item['precio_cd_1'] ?? null,
            $item['precio_cd_2'] ?? null,
            $item['precio_cd_3'] ?? null,
            $item['precio_cd_4'] ?? null,
            $item['precio_cd_5'] ?? null,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];

        $insert->bind_param($tipos, ...$params);
        if ($insert->execute()) {
            $insertados++;
        }
    }

    $check->close();
    $insert->close();

    return ["insertados" => $insertados, "duplicados" => $duplicados];
}
function procesarProductosSustitutos($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_prod_sustituto WHERE id = ?");
    if (!$check)
        return ["error" => "Error prepare check: " . $conexion->error];

    $insert = $conexion->prepare("
        INSERT INTO com_db_prod_sustituto (
            id, fecha, producto,
            peso, precio,     
            usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$insert) {
        $check->close();
        return ["error" => "Error prepare insert: " . $conexion->error];
    }

    $tipos = "ssiddssss";
    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['producto'] ?? null,
            $item['peso'] ?? null,
            $item['precio'] ?? null,

            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];

        $insert->bind_param($tipos, ...$params);
        if ($insert->execute()) {
            $insertados++;
        }
    }

    $check->close();
    $insert->close();

    return ["insertados" => $insertados, "duplicados" => $duplicados];
}
function procesarPuestoMercadoTienda($conexion, $items)
{
    $insertados = 0;
    $duplicados = 0;
    $count = 0;

    $check = $conexion->prepare("SELECT COUNT(*) FROM com_db_mercado_det WHERE id = ?");
    if (!$check)
        return ["error" => "Error prepare check: " . $conexion->error];

    $insert = $conexion->prepare("
            INSERT INTO com_db_mercado_det (
                id, fecha, mercado, tipoEstablecimiento,
                tamanio, cantidad,
                usuarioRegistro, fechaHoraRegistro, usuarioTransferencia, fechaHoraTransferencia
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
    if (!$insert) {
        $check->close();
        return ["error" => "Error prepare insert: " . $conexion->error];
    }

    $tipos = "ssississss";
    foreach ($items as $item) {
        $id = $item['id'] ?? uniqid();
        $check->bind_param("s", $id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->free_result();

        if ($count > 0) {
            $duplicados++;
            continue;
        }

        $params = [
            $id,
            $item['fecha'] ?? null,
            $item['mercado_codigo'] ?? 0,
            $item['tipo_establecimiento'] ?? null,
            $item['tamanio'] ?? null,
            $item['cantidad'] ?? 0,
            $item['usuarioRegistro'] ?? 'app_movil',
            $item['fechaHoraRegistro'] ?? date('Y-m-d H:i:s'),
            $item['usuarioTransferencia'] ?? 'app_movil',
            date('Y-m-d H:i:s')
        ];

        $insert->bind_param($tipos, ...$params);
        if ($insert->execute()) {
            $insertados++;

            actualizarResumenIncremental(
                $conexion,
                $item['mercado_codigo'],
                $item['tipo_establecimiento'],
                $item['tamanio']
            );
        }
    }

    $check->close();
    $insert->close();

    return ["insertados" => $insertados, "duplicados" => $duplicados];
}

function actualizarResumenIncremental($conexion, $mercado_codigo, $tipo_establecimiento, $tamanio)
{
    // Obtener la provincia del mercado
    $prov_stmt = $conexion->prepare("SELECT provincia FROM com_mercado WHERE codigo = ?");
    if (!$prov_stmt)
        return;
    $prov_stmt->bind_param("s", $mercado_codigo);
    $prov_stmt->execute();
    $provincia = null;
    $prov_stmt->bind_result($provincia);
    if (!$prov_stmt->fetch() || $provincia === null) {
        $prov_stmt->close();
        return;
    }
    $prov_stmt->close();

    $sql = "
        INSERT INTO com_db_mercado_res (
            provincia,
            tipoEstablecimiento,
            tamanio,
            total,
            numAves,
            fecha
        )
        SELECT 
            p.codigo AS provincia,
            m1.tipoEstablecimiento,
            m1.tamanio,
            SUM(m1.cantidad) AS total,
            ROUND(SUM(m1.cantidad) * COALESCE(fp.factor, 1)) AS numAves,
            CURDATE() AS fecha
        FROM com_db_mercado_det m1  
        LEFT JOIN com_db_mercado_det m2
            ON m1.mercado = m2.mercado
            AND m1.tipoEstablecimiento = m2.tipoEstablecimiento
            AND m1.tamanio = m2.tamanio
            AND m2.fechaHoraRegistro > m1.fechaHoraRegistro
        JOIN com_mercado m ON m1.mercado = m.codigo
        JOIN com_provincia p ON m.provincia = p.codigo
        LEFT JOIN com_factor_proyeccion fp ON m1.tamanio = fp.tamanio
        WHERE 
            m2.id IS NULL
            AND m1.mercado IS NOT NULL
            AND m1.tipoEstablecimiento = ?
            AND m1.tamanio = ?
            AND p.codigo = ?
        GROUP BY p.codigo, m1.tipoEstablecimiento, m1.tamanio
        ON DUPLICATE KEY UPDATE 
            total = VALUES(total),
            numAves = VALUES(numAves),
            fecha = VALUES(fecha);
            ";

    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sss", $tipo_establecimiento, $tamanio, $provincia);
        $stmt->execute();
        $stmt->close();
    }
}