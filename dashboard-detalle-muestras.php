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

// ===== EXPORTAR TODO EL DETALLE CRUDO (IGNORA PAGINACIÓN) =====
if (isset($_GET['export_crudo_all'])) {
    $query = "SELECT * FROM com_db_solicitud_det ORDER BY codEnvio, posSolicitud, codMuestra, codAnalisis, id";
    $result = mysqli_query($conexion, $query);

    if (!$result || mysqli_num_rows($result) === 0) {
        die("No hay datos para exportar.");
    }

    // Obtener nombres de columnas
    $cols = [];
    $fieldInfo = mysqli_fetch_fields($result);
    foreach ($fieldInfo as $field) {
        $cols[] = $field->name;
    }

    $csv = "\uFEFF";
    $csv .= '"' . implode('","', array_map(fn($c) => str_replace('"', '""', $c), $cols)) . '"' . "\n";

    while ($row = mysqli_fetch_row($result)) {
        $escapedRow = array_map(function ($value) {
            if ($value === null)
                return 'NULL';
            return '"' . str_replace('"', '""', $value) . '"';
        }, $row);
        $csv .= implode(',', $escapedRow) . "\n";
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="DetalleMuestras_Crudo_Completo.csv"');
    echo $csv;
    exit();
}

// ===== PAGINACIÓN PARA VISUALIZACIÓN =====
$registrosPorPagina = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $registrosPorPagina;

// Contar total
$totalQuery = "SELECT COUNT(*) as total FROM com_db_solicitud_det";
$totalResult = mysqli_query($conexion, $totalQuery);
$totalRegistros = mysqli_fetch_assoc($totalResult)['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Obtener datos paginados (con orden correcto)
$detallesQuery = "
    SELECT * 
    FROM com_db_solicitud_det 
    ORDER BY codEnvio, posSolicitud, codMuestra, codAnalisis, id
    LIMIT $registrosPorPagina OFFSET $offset
";
$detalles = mysqli_query($conexion, $detallesQuery);

// Obtener nombres de columnas (para la tabla)
$columnNames = [];
if (mysqli_num_rows($detalles) > 0) {
    mysqli_data_seek($detalles, 0);
    $sampleRow = mysqli_fetch_assoc($detalles);
    $columnNames = array_keys($sampleRow);
    mysqli_data_seek($detalles, 0);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muestras - Detalle (Crudo)</title>
    <link rel="stylesheet" href="css/output.css">
    <style>
        body {
            background-color: #f9fafb;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #1f2937;
        }

        .container {
            max-width: 1600px;
        }

        h1 {
            font-weight: 700;
            color: #111827;
            margin-bottom: 1.5rem;
        }

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
            text-decoration: none;
        }

        .export-btn:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }

        .raw-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-top: 1rem;
            font-size: 0.85rem;
        }

        .raw-table th {
            background-color: #f3f4f6;
            padding: 0.6rem 0.8rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        .raw-table td {
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 0.85rem;
        }

        .raw-table tr:last-child td {
            border-bottom: none;
        }

        .raw-table tr:hover {
            background-color: #f9fafb;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
        }

        .pagination a {
            color: #2563eb;
            background: white;
            border: 1px solid #d1d5db;
        }

        .pagination a:hover {
            background: #eff6ff;
        }

        .pagination .current {
            background: #2563eb;
            color: white;
            border: 1px solid #2563eb;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl">Registro Cabecera</h1>

        <!-- Botón de exportación (exporta TODO, no solo la página) -->
        <div class="mb-6">
            <a href="?export_crudo_all=1" class="export-btn">📊 Exportar Todos los Registros a CSV</a>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto">
            <?php if (!empty($columnNames) && mysqli_num_rows($detalles) > 0): ?>
                    <table class="raw-table">
                        <thead>
                            <tr>
                                <?php foreach ($columnNames as $col): ?>
                                        <th><?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($detalles)): ?>
                                    <tr>
                                        <?php foreach ($columnNames as $col): ?>
                                                <td><?= htmlspecialchars($row[$col] ?? 'NULL') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
            <?php else: ?>
                    <p class="text-gray-500">No hay registros en <code>com_db_solicitud_det</code>.</p>
            <?php endif; ?>
        </div>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                            <a href="?page=1">&laquo;</a>
                            <a href="?page=<?= $page - 1 ?>">‹</a>
                    <?php endif;

                    $start = max(1, $page - 2);
                    $end = min($totalPaginas, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        $isActive = ($i == $page);
                        if ($isActive):
                            echo "<span class='current'>$i</span>";
                        else:
                            echo "<a href='?page=$i'>$i</a>";
                        endif;
                    endfor;

                    if ($page < $totalPaginas): ?>
                            <a href="?page=<?= $page + 1 ?>">›</a>
                            <a href="?page=<?= $totalPaginas ?>">&raquo;</a>
                    <?php endif; ?>
                </div>
        <?php endif; ?>
    </div>
</body>

</html>