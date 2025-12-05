<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}

include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión.");
}

// ===== EXPORTAR TODO (CSV COMPLETO DE CABECERAS) =====
if (isset($_GET['export_all_cabeceras'])) {
    $query = "
        SELECT 
            codEnvio, fecEnvio, horaEnvio, codLab, nomLab, 
            codEmpTrans, nomEmpTrans, usuarioRegistrador, 
            usuarioResponsable, autorizadoPor, fechaHoraRegistro
        FROM com_db_solicitud_cab 
        ORDER BY fecEnvio DESC, horaEnvio DESC
    ";
    $result = mysqli_query($conexion, $query);

    $csv = "\uFEFF";
    $csv .= "Código Envío,Fecha Envío,Hora Envío,Código Laboratorio,Nombre Laboratorio,Código Empresa Transporte,Nombre Empresa Transporte,Usuario Registrador,Usuario Responsable,Autorizado Por,Fecha Hora Registro\n";

    while ($row = mysqli_fetch_assoc($result)) {
        $csv .= '"' . str_replace('"', '""', $row['codEnvio']) . '",';
        $csv .= '"' . $row['fecEnvio'] . '",';
        $csv .= '"' . $row['horaEnvio'] . '",';
        $csv .= '"' . str_replace('"', '""', $row['codLab']) . '",';
        $csv .= '"' . str_replace('"', '""', $row['nomLab']) . '",';
        $csv .= '"' . str_replace('"', '""', $row['codEmpTrans']) . '",';
        $csv .= '"' . str_replace('"', '""', $row['nomEmpTrans']) . '",';
        $csv .= '"' . str_replace('"', '""', $row['usuarioRegistrador']) . '",';
        $csv .= '"' . str_replace('"', '""', $row['usuarioResponsable']) . '",';
        $csv .= '"' . str_replace('"', '""', $row['autorizadoPor']) . '",';
        $csv .= '"' . $row['fechaHoraRegistro'] . '"' . "\n";
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cabeceras_envios_completas.csv"');
    echo $csv;
    exit();
}

// ===== PAGINACIÓN PARA TABLA PRINCIPAL =====
$registrosPorPagina = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $registrosPorPagina;

$totalQuery = "SELECT COUNT(*) as total FROM com_db_solicitud_cab";
$totalResult = mysqli_query($conexion, $totalQuery);
$totalRegistros = mysqli_fetch_assoc($totalResult)['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Cabeceras paginadas
$cabecerasQuery = "
    SELECT 
        codEnvio, fecEnvio, horaEnvio, codLab, nomLab, 
        codEmpTrans, nomEmpTrans, usuarioRegistrador, 
        usuarioResponsable, autorizadoPor, fechaHoraRegistro
    FROM com_db_solicitud_cab 
    ORDER BY fecEnvio DESC, horaEnvio DESC 
    LIMIT $registrosPorPagina OFFSET $offset
";
$cabeceras = mysqli_query($conexion, $cabecerasQuery);

// ===== CARGAR DETALLE SI HAY SELECCIÓN =====
$codEnvioSeleccionado = $_GET['codEnvio'] ?? null;
$detallesAgrupados = [];

if ($codEnvioSeleccionado) {
    $codEnvioEscapado = mysqli_real_escape_string($conexion, $codEnvioSeleccionado);
    $sql = "
        SELECT 
            d.codEnvio, d.codRef, d.fecToma, d.numMuestras,
            d.codMuestra, d.nomMuestra, d.codAnalisis, d.nomAnalisis,
            d.obs, d.id, d.posSolicitud,
            tm.nombre AS tipo_muestra_real,
            a.nombre AS analisis_real
        FROM com_db_solicitud_det d
        LEFT JOIN com_tipo_muestra tm ON d.codMuestra = tm.codigo
        LEFT JOIN com_analisis a ON d.codAnalisis = a.codigo
        WHERE d.codEnvio = '$codEnvioEscapado'
        ORDER BY d.posSolicitud ASC, d.codMuestra ASC, d.codAnalisis ASC
    ";
    $res = mysqli_query($conexion, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $pos = $row['posSolicitud'];
        if (!isset($detallesAgrupados[$pos])) {
            $detallesAgrupados[$pos] = [];
        }
        $detallesAgrupados[$pos][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muestras - Cabecera y Detalle</title>
    <link rel="stylesheet" href="css/output.css">
    <style>
        body {
            background-color: #f9fafb;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #1f2937;
        }

        .container {
            max-width: 1400px;
        }

        h1,
        h2 {
            font-weight: 700;
            color: #111827;
            margin-bottom: 1rem;
        }

        /* Botón exportar */
        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .export-btn:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }

        /* Tablas */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-top: 0.5rem;
        }

        .data-table th {
            background-color: #f3f4f6;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.875rem;
        }

        .data-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.875rem;
            vertical-align: top;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background-color: #f9fafb;
        }

        /* Selector */
        .selector-section {
            margin: 2.5rem 0;
            padding: 1.25rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .selector-section select {
            padding: 0.625rem 0.875rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            min-width: 200px;
            margin-right: 1rem;
        }

        .selector-section button {
            padding: 0.625rem 1.25rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .selector-section button:hover {
            background: #2563eb;
        }

        /* Agrupación visual */
        .pos-group-header {
            background-color: #f0f9ff;
            padding: 0.75rem 1rem;
            font-weight: 600;
            color: #0369a1;
            border-bottom: 2px solid #bae6fd;
            margin-top: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .data-table {
                font-size: 0.8rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl">Registro Cabecera</h1>

        <!-- === PRIMERA SECCIÓN: TABLA COMPLETA DE CABECERAS === -->
        <div class="mb-6">
            <a href="?export_all_cabeceras=1" class="export-btn">📊 Exportar Todas las Cabeceras</a>
        </div>

        <!--h2 class="text-xl">Registros de Cabeceras (<code>com_db_solicitud_cab</code>)</h2-->
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código Envío</th>
                        <th>Fecha Envío</th>
                        <th>Hora Envío</th>
                        <th>Código Laboratorio</th>
                        <th>Nombre Laboratorio</th>
                        <th>Código Empresa Transporte</th>
                        <th>Nombre Empresa Transporte</th>
                        <th>Usuario Registrador</th>
                        <th>Usuario Responsable</th>
                        <th>Autorizado Por</th>
                        <th>Fecha Hora Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($cabeceras) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($cabeceras)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['codEnvio']) ?></td>
                                <td><?= $row['fecEnvio'] ?></td>
                                <td><?= $row['horaEnvio'] ?></td>
                                <td><?= htmlspecialchars($row['codLab']) ?></td>
                                <td><?= htmlspecialchars($row['nomLab']) ?></td>
                                <td><?= htmlspecialchars($row['codEmpTrans']) ?></td>
                                <td><?= htmlspecialchars($row['nomEmpTrans']) ?></td>
                                <td><?= htmlspecialchars($row['usuarioRegistrador']) ?></td>
                                <td><?= htmlspecialchars($row['usuarioResponsable']) ?></td>
                                <td><?= htmlspecialchars($row['autorizadoPor']) ?></td>
                                <td><?= $row['fechaHoraRegistro'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center py-4 text-gray-500">No hay registros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
            <div class="flex justify-center mt-4 gap-1">
                <?php
                $baseParams = $codEnvioSeleccionado ? "&codEnvio=" . urlencode($codEnvioSeleccionado) : '';
                if ($page > 1): ?>
                    <a href="?page=1<?= $baseParams ?>" class="px-3 py-1 border rounded">« Primera</a>
                    <a href="?page=<?= $page - 1 ?><?= $baseParams ?>" class="px-3 py-1 border rounded">‹ Anterior</a>
                <?php endif;

                $start = max(1, $page - 2);
                $end = min($totalPaginas, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                    $isActive = ($i == $page);
                    $class = $isActive ? 'bg-blue-600 text-white' : 'bg-white text-gray-700';
                    echo "<a href='?page=$i{$baseParams}' class='px-3 py-1 border rounded $class'>$i</a>";
                endfor;

                if ($page < $totalPaginas): ?>
                    <a href="?page=<?= $page + 1 ?><?= $baseParams ?>" class="px-3 py-1 border rounded">Siguiente ›</a>
                    <a href="?page=<?= $totalPaginas ?><?= $baseParams ?>" class="px-3 py-1 border rounded">Última »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- === SEGUNDA SECCIÓN: SELECTOR Y DETALLE === -->
        <div class="selector-section">
            <form method="GET">
                <label class="font-medium mr-2">Seleccione un envío:</label>
                <select name="codEnvio">
                    <option value="">-- Código de Envío --</option>
                    <?php
                    $todas = mysqli_query($conexion, "SELECT DISTINCT codEnvio FROM com_db_solicitud_cab ORDER BY codEnvio");
                    while ($r = mysqli_fetch_assoc($todas)):
                        $sel = ($codEnvioSeleccionado == $r['codEnvio']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($r['codEnvio']) . "\" $sel>" . htmlspecialchars($r['codEnvio']) . '</option>';
                    endwhile;
                    ?>
                </select>
                <button type="submit">Cargar Detalle</button>
            </form>
        </div>

        <!-- === TABLA DE DETALLE AGRUPADO === -->
        <?php if ($codEnvioSeleccionado && !empty($detallesAgrupados)): ?>
            <h2 class="text-xl">Detalle del Envío: <strong><?= htmlspecialchars($codEnvioSeleccionado) ?></strong></h2>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código Referencia</th> <!-- MOVIDO AL PRINCIPIO -->
                            <th>Pos Solicitud</th>
                            <th>Código Envío</th>
                            <th>Fecha Toma</th>
                            <th>Número de Muestras</th>
                            <th>Código Muestra</th>
                            <th>Nombre Muestra</th>
                            <th>Código Análisis</th>
                            <th>Nombre Análisis</th>
                            <th>Observación</th>
                            <th>ID Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detallesAgrupados as $pos => $items): ?>
                            <tr>
                                <td colspan="11" class="pos-group-header">
                                    Posición Solicitud: <?= (int) $pos ?>
                                </td>
                            </tr>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['codRef']) ?></td> <!-- PRIMERO -->
                                    <td><?= (int) $pos ?></td>
                                    <td><?= htmlspecialchars($item['codEnvio']) ?></td>
                                    <td><?= $item['fecToma'] ?></td>
                                    <td><?= (int) $item['numMuestras'] ?></td>
                                    <td><?= htmlspecialchars($item['codMuestra']) ?></td>
                                    <td><?= htmlspecialchars($item['tipo_muestra_real'] ?? $item['nomMuestra']) ?></td>
                                    <td><?= htmlspecialchars($item['codAnalisis']) ?></td>
                                    <td><?= htmlspecialchars($item['analisis_real'] ?? $item['nomAnalisis']) ?></td>
                                    <td><?= htmlspecialchars($item['obs'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($item['id']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Botón Exportar Detalle -->
            <div class="mt-4">
                <a href="#" id="exportDetalle" class="export-btn">📊 Exportar Este Detalle</a>
            </div>

        <?php elseif ($codEnvioSeleccionado): ?>
            <p class="text-gray-500">No se encontraron detalles para este envío.</p>
        <?php endif; ?>

    </div>

    <?php if ($codEnvioSeleccionado && !empty($detallesAgrupados)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const exportBtn = document.getElementById('exportDetalle');
                if (exportBtn) {
                    exportBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        let csv = '\uFEFFDETALLE - <?= addslashes($codEnvioSeleccionado) ?>\n';
                        csv += 'Código Referencia,Posición Solicitud,Código Envío,Fecha Toma,Número de Muestras,Código Muestra,Nombre Muestra,Código Análisis,Nombre Análisis,Observación,ID Detalle\n';

                        <?php foreach ($detallesAgrupados as $pos => $items): ?>
                            <?php foreach ($items as $item): ?>
                                csv += <?= json_encode($item['codRef']) ?> + "," +
                                    <?= (int) $pos ?> + "," +
                                    <?= json_encode($item['codEnvio']) ?> + "," +
                                    <?= json_encode($item['fecToma']) ?> + "," +
                                    <?= json_encode($item['numMuestras']) ?> + "," +
                                    <?= json_encode($item['codMuestra']) ?> + "," +
                                    <?= json_encode($item['tipo_muestra_real'] ?? $item['nomMuestra']) ?> + "," +
                                    <?= json_encode($item['codAnalisis']) ?> + "," +
                                    <?= json_encode($item['analisis_real'] ?? $item['nomAnalisis']) ?> + "," +
                                    <?= json_encode($item['obs'] ?? '') ?> + "," +
                                    <?= json_encode($item['id']) ?> + "\n";
                            <?php endforeach; ?>
                        <?php endforeach; ?>

                        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        a.download = 'detalle_envio_<?= str_replace(['/', '\\'], '_', addslashes($codEnvioSeleccionado)) ?>.csv';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        alert('✅ Detalle exportado.');
                    });
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>