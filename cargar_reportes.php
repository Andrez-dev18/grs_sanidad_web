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

// Contar total (solo cuando se pide)
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
        <div
            class="report-card bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow mb-6">

            <!-- Campos en pares -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 mb-6 text-sm">
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

                $campoArray = array_chunk(array_map(
                    function ($label, $valor) {
                        return  [
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
                        <div></div> <!-- Espacio vacío para mantener el grid balanceado -->
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Botones alineados en fila, ocupando todo el ancho -->
            <div class="flex flex-col sm:flex-row gap-3 w-full">
                <button onclick="window.open('generar_pdf.php?codigo=<?php echo urlencode($row['codEnvio']); ?>', '_blank')"
                    class="flex-1 px-6 py-2.5 min-w-[140px] bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white font-medium rounded-lg transition duration-200 inline-flex items-center justify-center gap-2">
                    📄 PDF Tabla
                </button>
                <button
                    onclick="window.open('generar_pdf_resumen.php?codigo=<?php echo urlencode($row['codEnvio']); ?>', '_blank')"
                    class="flex-1 px-6 py-2.5 min-w-[140px] bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white font-medium rounded-lg transition duration-200 inline-flex items-center justify-center gap-2">
                    📋 PDF Resumen
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