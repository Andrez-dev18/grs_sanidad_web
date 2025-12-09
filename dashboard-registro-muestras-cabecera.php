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

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/output.css">

    <!-- Font Awesome (opcional, aunque usas emojis) -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .data-table th {
            background-color: #f9fafb;
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

        .pos-group-header {
            background-color: #f0f9ff;
            padding: 0.75rem 1rem;
            font-weight: 600;
            color: #0369a1;
            border-bottom: 2px solid #bae6fd;
        }

        @media (max-width: 768px) {
            .data-table thead {
                display: none;
            }

            .data-table,
            .data-table tbody,
            .data-table tr,
            .data-table td {
                display: block;
                width: 100%;
            }

            .data-table tr {
                margin-bottom: 1.25rem;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 1rem;
            }

            .data-table td {
                text-align: right;
                padding: 0.4rem 0;
                border: none;
                position: relative;
                padding-left: 50%;
            }

            .data-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 1rem;
                font-weight: 600;
                color: #374151;
            }

            .pos-group-header {
                margin-top: 1.5rem;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- TÍTULO PRINCIPAL -->
        <div class="content-header max-w-7xl mx-auto mb-8">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-4xl">🗃️</span>
                <h1 class="text-3xl font-bold text-gray-800">Registro Cabecera</h1>
            </div>
            <p class="text-gray-600 text-sm">Vea las cabeceras de los registros en el sistema</p>
        </div>

        <!-- BOTÓN DE EXPORTAR -->
        <div class="max-w-7xl mx-auto mb-6">
            <a href="?export_all_cabeceras=1"
                class="px-6 py-2.5 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2"
                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);"
                onmouseover="this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'"
                onmouseout="this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'">
                📊 Exportar Todas las Cabeceras
            </a>
        </div>

        <!-- TABLA DE CABECERAS -->
        <div class="max-w-7xl mx-auto mb-10">
            <div class="table-container border border-gray-300 rounded-2xl bg-white overflow-x-auto">
                <table class="data-table w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Código Envío</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Fecha Envío</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Hora Envío</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Cód. Lab</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Nombre Lab</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Cód. Transp.</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Nombre Transp.</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Registrador</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Responsable</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Autorizado Por</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">F/H Registro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($cabeceras) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($cabeceras)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-gray-700" data-label="Código Envío">
                                        <?= htmlspecialchars($row['codEnvio']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700" data-label="Fecha Envío"><?= $row['fecEnvio'] ?></td>
                                    <td class="px-6 py-4 text-gray-700" data-label="Hora Envío"><?= $row['horaEnvio'] ?></td>
                                    <td class="px-6 py-4 text-gray-700" data-label="Cód. Lab">
                                        <?= htmlspecialchars($row['codLab']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700" data-label="Nombre Lab">
                                        <?= htmlspecialchars($row['nomLab']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700" data-label="Cód. Transp.">
                                        <?= htmlspecialchars($row['codEmpTrans']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700" data-label="Nombre Transp.">
                                        <?= htmlspecialchars($row['nomEmpTrans']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700" data-label="Registrador">
                                        <?= htmlspecialchars($row['usuarioRegistrador']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700" data-label="Responsable">
                                        <?= htmlspecialchars($row['usuarioResponsable']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700" data-label="Autorizado Por">
                                        <?= htmlspecialchars($row['autorizadoPor']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700" data-label="F/H Registro">
                                        <?= $row['fechaHoraRegistro'] ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="px-6 py-8 text-center text-gray-500">No hay registros.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($totalPaginas > 1): ?>
            <div class="max-w-7xl mx-auto mb-10">
                <div class="flex flex-wrap justify-center gap-1">
                    <?php
                    $baseParams = $codEnvioSeleccionado ? "&codEnvio=" . urlencode($codEnvioSeleccionado) : '';
                    if ($page > 1): ?>
                        <a href="?page=1<?= $baseParams ?>"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">«
                            Primera</a>
                        <a href="?page=<?= $page - 1 ?><?= $baseParams ?>"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">‹
                            Anterior</a>
                    <?php endif;

                    $start = max(1, $page - 2);
                    $end = min($totalPaginas, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        $isActive = ($i == $page);
                        $class = $isActive ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300';
                        echo "<a href='?page=$i{$baseParams}' class='px-3 py-1.5 rounded-lg transition hover:bg-gray-100 $class'>$i</a>";
                    endfor;

                    if ($page < $totalPaginas): ?>
                        <a href="?page=<?= $page + 1 ?><?= $baseParams ?>"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">Siguiente
                            ›</a>
                        <a href="?page=<?= $totalPaginas ?><?= $baseParams ?>"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">Última
                            »</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>


        <!-- SELECCIONADOR DE ENVÍO -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200">
                <form method="GET" class="flex flex-wrap items-center gap-4">
                    <label class="font-medium text-gray-700 whitespace-nowrap">Seleccione un envío:</label>
                    <div class="flex-grow min-w-[200px] max-w-xs">
                        <select name="codEnvio"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="">-- Código de Envío --</option>
                            <?php
                            $todas = mysqli_query($conexion, "SELECT DISTINCT codEnvio FROM com_db_solicitud_cab ORDER BY codEnvio");
                            while ($r = mysqli_fetch_assoc($todas)):
                                $sel = ($codEnvioSeleccionado == $r['codEnvio']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($r['codEnvio']) . "\" $sel>" . htmlspecialchars($r['codEnvio']) . '</option>';
                            endwhile;
                            ?>
                        </select>
                    </div>
                   <button type="submit"
    class="px-6 py-2.5 min-w-[250px] w-auto bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium rounded-lg transition duration-200">
    Cargar Detalle
</button>
                </form>
            </div>
        </div>

        <!-- DETALLE AGRUPADO -->
        <?php if ($codEnvioSeleccionado && !empty($detallesAgrupados)): ?>
            <div class="max-w-7xl mx-auto mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Detalle del Envío: <span
                            class="text-blue-600"><?= htmlspecialchars($codEnvioSeleccionado) ?></span></h2>
                </div>

                <div class="table-container border border-gray-300 rounded-2xl bg-white overflow-x-auto mb-4">
                    <table class="data-table w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">Cód. Ref</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">Pos</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">Cód. Envío</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">Fec. Toma</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">Muestras</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">Cód. Muestra</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">Muestra</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">Cód. Análisis</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">Análisis</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">Obs.</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-800">ID</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($detallesAgrupados as $pos => $items): ?>
                                <tr>
                                    <td colspan="11" class="px-6 py-3 pos-group-header">
                                        Posición Solicitud: <?= (int) $pos ?>
                                    </td>
                                </tr>
                                <?php foreach ($items as $item): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-3 text-gray-700" data-label="Cód. Ref">
                                            <?= htmlspecialchars($item['codRef']) ?>
                                        </td>
                                        <td class="px-6 py-3 text-gray-700" data-label="Pos"><?= (int) $pos ?></td>
                                        <td class="px-6 py-3 text-gray-700" data-label="Cód. Envío">
                                            <?= htmlspecialchars($item['codEnvio']) ?>
                                        </td>
                                        <td class="px-6 py-3 text-gray-700" data-label="Fec. Toma"><?= $item['fecToma'] ?></td>
                                        <td class="px-6 py-3 text-gray-700" data-label="Muestras"><?= (int) $item['numMuestras'] ?>
                                        </td>
                                        <td class="px-6 py-3 text-gray-700" data-label="Cód. Muestra">
                                            <?= htmlspecialchars($item['codMuestra']) ?>
                                        </td>
                                        <td class="px-6 py-3 text-gray-700" data-label="Muestra">
                                            <?= htmlspecialchars($item['tipo_muestra_real'] ?? $item['nomMuestra']) ?>
                                        </td>
                                        <td class="px-6 py-3 text-gray-700" data-label="Cód. Análisis">
                                            <?= htmlspecialchars($item['codAnalisis']) ?>
                                        </td>
                                        <td class="px-6 py-3 text-gray-700" data-label="Análisis">
                                            <?= htmlspecialchars($item['analisis_real'] ?? $item['nomAnalisis']) ?>
                                        </td>
                                        <td class="px-6 py-3 text-gray-700" data-label="Obs.">
                                            <?= htmlspecialchars($item['obs'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-3 text-gray-700" data-label="ID"><?= htmlspecialchars($item['id']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- BOTÓN EXPORTAR DETALLE -->
                <a href="#" id="exportDetalle"
                    class="px-6 py-2.5 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);"
                    onmouseover="this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'"
                    onmouseout="this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'">
                    📊 Exportar Este Detalle
                </a>
            </div>

        <?php elseif ($codEnvioSeleccionado): ?>
            <div class="max-w-7xl mx-auto">
                <p class="text-gray-500 text-center py-6">No se encontraron detalles para este envío.</p>
            </div>
        <?php endif; ?>


        <!-- FOOTER -->
        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © 2025
            </p>
        </div>

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