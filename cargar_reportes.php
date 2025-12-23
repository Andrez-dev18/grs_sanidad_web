<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit('No autorizado');
}

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo '<div class="text-center py-5 text-red-600">Error de conexión.</div>';
    exit;
}

$registrosPorPagina = 10;
$pagina = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($pagina < 1)
    $pagina = 1;
$offset = ($pagina - 1) * $registrosPorPagina;

$busqueda = trim($_GET['q'] ?? '');
$condicion = '';
$params = [];

if ($busqueda !== '') {
    $condicion = "WHERE c.codEnvio LIKE ?";
    $params[] = "%$busqueda%";
}


$total = false;
if (isset($_GET['get_total'])) {
    $sqlTotal = "SELECT COUNT(*) AS total FROM san_fact_solicitud_cab c " . $condicion;
    $stmt = mysqli_prepare($conexion, $sqlTotal);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    }
    mysqli_stmt_execute($stmt);
    $totalRes = mysqli_stmt_get_result($stmt);
    $total = mysqli_fetch_assoc($totalRes)['total'];
    mysqli_stmt_close($stmt);
}

// Consulta de registros
$sql = "
    SELECT 
        c.codEnvio,
        c.fecEnvio,
        c.horaEnvio,
        c.nomLab,
        c.nomEmpTrans,
        c.usuarioRegistrador,
        c.usuarioResponsable,
        c.autorizadoPor
    FROM san_fact_solicitud_cab c
    $condicion
    ORDER BY c.fechaHoraRegistro DESC
    LIMIT $registrosPorPagina OFFSET $offset
";

$stmt = mysqli_prepare($conexion, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<?php if (mysqli_num_rows($result) > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <?php
        $campos = [
            'Código de Envío' => $row['codEnvio'],
            'Fecha de Envío' => $row['fecEnvio'],
            'Hora de Envío' => substr($row['horaEnvio'], 0, 5),
            'Laboratorio' => $row['nomLab'],
            'Empresa de Transporte' => $row['nomEmpTrans'] ?? '–',
            'Usuario Registrador' => $row['usuarioRegistrador'] ?? '–',
            'Usuario Responsable' => $row['usuarioResponsable'] ?? '–',
            'Autorizado por' => $row['autorizadoPor'] ?? '–',
        ];
        ?>
        <div class="report-card bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow mb-6 relative"
            data-codigo="<?= htmlspecialchars($row['codEnvio']) ?>">

            <!-- Campos en pares -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 mb-6 text-sm">
                <?php
                $campoArray = array_chunk(array_map(
                    function ($label, $valor) {
                        return [
                            'label' => htmlspecialchars($label),
                            'value' => htmlspecialchars($valor ?? '–')
                        ];
                    },
                    array_keys($campos),
                    array_values($campos)
                ), 2);

                foreach ($campoArray as $par): ?>
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-700"><?= $par[0]['label'] ?>:</span>
                        <span class="text-gray-900"><?= $par[0]['value'] ?></span>
                    </div>
                    <?php if (isset($par[1])): ?>
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-700"><?= $par[1]['label'] ?>:</span>
                            <span class="text-gray-900"><?= $par[1]['value'] ?></span>
                        </div>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="absolute top-4 right-4 flex flex-col gap-2 w-36">
                <!-- PDF y Correo (como ya tenías) -->
                <button onclick="window.open('generar_pdf_tabla.php?codigo=<?= urlencode($row['codEnvio']) ?>', '_blank')"
                    class="flex items-center gap-2 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs rounded-md shadow transition"
                    title="PDF Tabla">
                    <span class="text-base min-w-[1.2em] text-center">📄</span>
                    <span>PDF Tabla</span>
                </button>
                <button onclick="window.open('generar_pdf_resumen.php?codigo=<?= urlencode($row['codEnvio']) ?>', '_blank')"
                    class="flex items-center gap-2 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs rounded-md shadow transition"
                    title="PDF Resumen">
                    <span class="text-base min-w-[1.2em] text-center">📋</span>
                    <span>PDF Resumen</span>
                </button>
                <button onclick="abrirModalCorreo('<?= htmlspecialchars($row['codEnvio']) ?>')"
                    class="flex items-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-md shadow transition"
                    title="Enviar por correo">
                    <span class="text-base min-w-[1.2em] text-center">✉️</span>
                    <span>Correo</span>
                </button>
              
            </div>


        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="text-center py-10 text-gray-500 text-sm">
        <p>No se encontraron registros.</p>
    </div>
<?php endif; ?>

<?php
if ($total !== false) {
    echo "<!--TOTAL_PAGES:" . ceil($total / $registrosPorPagina) . "-->";
}
mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>