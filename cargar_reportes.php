<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit('No autorizado');
}

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    echo '<div style="text-align:center; padding:20px; color:red;">Error de conexión.</div>';
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

// Contar total (solo en primera página)
$total = false;
if (isset($_GET['get_total'])) {
    $sqlTotal = "SELECT COUNT(*) AS total FROM com_db_solicitud_cab c " . $condicion;
    $stmt = mysqli_prepare($conexion, $sqlTotal);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    }
    mysqli_stmt_execute($stmt);
    $totalRes = mysqli_stmt_get_result($stmt);
    $total = mysqli_fetch_assoc($totalRes)['total'];
    mysqli_stmt_close($stmt);
}

// Consulta: solo campos de la cabecera
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
    FROM com_db_solicitud_cab c
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
        <div class="report-card" data-codigo="<?php echo htmlspecialchars($row['codEnvio']); ?>">
            <div class="report-cabecera-resumen"
                style="font-family: ui-monospace, monospace; font-size: 0.875rem; line-height: 1.4; margin-bottom: 12px;">
                <?php foreach ($campos as $label => $valor): ?>
                    <div style="display: flex; margin-bottom: 3px;">
                        <span style="font-weight: bold; min-width: 190px;"><?php echo htmlspecialchars($label); ?>:</span>
                        <span><?php echo htmlspecialchars($valor ?? '–'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="report-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button class="btn-download-pdf"
                    onclick="window.open('generar_pdf.php?codigo=<?php echo urlencode($row['codEnvio']); ?>', '_blank')"
                    style="background-color: #dc2626; color: white; font-weight: bold; padding: 0.375rem 0.5rem; border-radius: 0.375rem; border: none; cursor: pointer; font-size: 0.875rem;">
                    📄 PDF Tabla
                </button>
                <button class="btn-download-pdf"
                    onclick="window.open('generar_pdf_resumen.php?codigo=<?php echo urlencode($row['codEnvio']); ?>', '_blank')"
                    style="background-color: #dc2626; color: white; font-weight: bold; padding: 0.375rem 0.5rem; border-radius: 0.375rem; border: none; cursor: pointer; font-size: 0.875rem;">
                    📋 PDF Resumen
                </button>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div style="text-align: center; padding: 40px; color: #718096;">
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