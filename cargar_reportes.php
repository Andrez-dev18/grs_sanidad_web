<?php
session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    exit('No autorizado');
}

//ruta relativa a la conexion
include_once 'conexion_grs_joya\conexion.php';
$conexion = conectar_sanidad();
if (!$conexion) {
    echo '<div style="text-align:center; padding:20px; color:red;">Error de conexión.</div>';
    exit;
}

$registrosPorPagina = 10;
$pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $registrosPorPagina;

$busqueda = trim($_GET['q'] ?? '');
$condicion = '';
$params = [];

if ($busqueda !== '') {
    // Buscar solo por código de envío (coincidencia parcial)
    $condicion = "WHERE c.codigoEnvio LIKE ?";
    $params[] = "%$busqueda%";
}

// Contar total (solo si es la primera carga)
$total = false;
if (isset($_GET['get_total'])) {
    $sqlTotal = "SELECT COUNT(*) AS total FROM com_db_muestra_cabecera c " . $condicion;
    $stmt = mysqli_prepare($conexion, $sqlTotal);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    }
    mysqli_stmt_execute($stmt);
    $totalRes = mysqli_stmt_get_result($stmt);
    $total = mysqli_fetch_assoc($totalRes)['total'];
    mysqli_stmt_close($stmt);
}

// Consulta principal
$sql = "
    SELECT 
        c.codigoEnvio,
        c.fechaEnvio,
        c.horaEnvio,
        l.nombre AS laboratorio,
        COUNT(d.posicionSolicitud) AS total_muestras,
        MIN(d.codigoReferencia) AS primer_codigo_ref,
        MIN(tm.nombre) AS primer_tipo_muestra
    FROM com_db_muestra_cabecera c
    JOIN com_laboratorio l ON c.laboratorio = l.codigo
    LEFT JOIN com_db_muestra_detalle d ON c.codigoEnvio = d.codigoEnvio
    LEFT JOIN com_analisis a ON FIND_IN_SET(a.codigo, d.analisis)
    LEFT JOIN com_tipo_muestra tm ON a.tipoMuestra = tm.codigo
    $condicion
    GROUP BY c.codigoEnvio
    ORDER BY c.fechaRegistro DESC
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
        $codigoEnvio = htmlspecialchars($row['codigoEnvio']);
        $fecha = htmlspecialchars($row['fechaEnvio']);
        $hora = htmlspecialchars($row['horaEnvio']);
        $laboratorio = htmlspecialchars($row['laboratorio']);
        $codigoRef = htmlspecialchars($row['primer_codigo_ref'] ?? '–');
        $tipoMuestra = htmlspecialchars($row['primer_tipo_muestra'] ?? '–');
        $totalMuestras = (int)($row['total_muestras'] ?? 0);
        ?>
        <div class="report-card" data-codigo="<?php echo $codigoEnvio; ?>">
  <div class="report-header">
    <div class="report-code"><?php echo $codigoEnvio; ?></div>
  </div>
  <div class="report-grid">
    <div class="report-info-item">
      <span class="report-label">Fecha y Hora</span>
      <span class="report-value"><?php echo $fecha . ' - ' . substr($hora, 0, 5); ?></span>
    </div>
    <div class="report-info-item">
      <span class="report-label">Laboratorio</span>
      <span class="report-value"><?php echo $laboratorio; ?></span>
    </div>
    <div class="report-info-item">
      <span class="report-label">Código de Referencia</span>
      <span class="report-value"><?php echo $codigoRef; ?></span>
    </div>
    <div class="report-info-item">
      <span class="report-label">N° de Muestras</span>
      <span class="report-value"><?php echo $totalMuestras; ?> unidad(es)</span>
    </div>
  </div>
  <div class="report-actions">
    <button class="btn-download-pdf" 
      onclick="window.open('generar_pdf.php?codigo=<?php echo urlencode($codigoEnvio); ?>', '_blank')">
      📄 Descargar PDF Modo Tabla
    </button>
    <button class="btn-download-pdf"
      onclick="window.open('generar_pdf_resumen.php?codigo=<?php echo urlencode($codigoEnvio); ?>', '_blank')">
      📋 Descargar PDF Modo Resumen
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