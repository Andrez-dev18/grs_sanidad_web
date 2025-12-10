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

if (isset($_GET['export_crudo_all'])) {
    $query = "SELECT * FROM com_db_solicitud_det ORDER BY codEnvio, posSolicitud, codMuestra, codAnalisis, id";
    $result = mysqli_query($conexion, $query);

    if (!$result || mysqli_num_rows($result) === 0) {
        die("No hay datos para exportar.");
    }

    $cols = [];
    $fieldInfo = mysqli_fetch_fields($result);
    foreach ($fieldInfo as $field) {
        $cols[] = $field->name;
    }

    $csv = "\uFEFF";
    $csv .= '"' . implode('","', array_map(function ($c) {
        return str_replace('"', '""', $c);
    }, $cols)) . '"' . "\n";

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

// PAGINACIÓN
$registrosPorPagina = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $registrosPorPagina;

$totalQuery = "SELECT COUNT(*) as total FROM com_db_solicitud_det";
$totalResult = mysqli_query($conexion, $totalQuery);
$totalRegistros = mysqli_fetch_assoc($totalResult)['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

$detallesQuery = "
    SELECT * 
    FROM com_db_solicitud_det 
    ORDER BY codEnvio, posSolicitud, codMuestra, codAnalisis, id
    LIMIT $registrosPorPagina OFFSET $offset
";
$detalles = mysqli_query($conexion, $detallesQuery);

// Obtener siempre los nombres de las columnas, incluso si no hay registros
$columnNames = [];
$metaQuery = mysqli_query($conexion, "SELECT * FROM com_db_solicitud_det LIMIT 0");
if ($metaQuery) {
    $fieldInfo = mysqli_fetch_fields($metaQuery);
    foreach ($fieldInfo as $field) {
        $columnNames[] = $field->name;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muestras - Detalle (Crudo)</title>

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="css/output.css">

    <!-- Font Awesome (opcional) -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.875rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background-color: #f9fafb;
        }

        @media (max-width: 768px) {

            .data-table,
            .data-table thead,
            .data-table tbody,
            .data-table th,
            .data-table td,
            .data-table tr {
                display: block;
            }

            .data-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            .data-table tr {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .data-table td {
                text-align: right;
                padding-left: 50% !important;
                position: relative;
                border: none;
            }

            .data-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 1rem;
                font-weight: 600;
                color: #374151;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">

        <!-- TÍTULO -->
        <div class="content-header max-w-7xl mx-auto mb-8">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-4xl">🗃️</span>
                <h1 class="text-3xl font-bold text-gray-800">Registro Detalle</h1>
            </div>
            <p class="text-gray-600 text-sm">Vea los detalles de los registros en el sistema</p>
        </div>

        <!-- BOTÓN EXPORTAR -->
        <div class="max-w-7xl mx-auto mb-6">
            <a href="?export_crudo_all=1"
                class="px-6 py-2.5 text-white font-medium rounded-lg transition duration-200 inline-flex items-center gap-2"
                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);"
                onmouseover="this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'"
                onmouseout="this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'">
                📊 Exportar Todos los Registros a CSV
            </a>
        </div>

        <!-- TABLA -->
        <div class="max-w-7xl mx-auto">
            <div class="table-container border border-gray-300 rounded-2xl bg-white overflow-x-auto">
                <table class="data-table w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <?php if (!empty($columnNames)): ?>
                                <?php foreach ($columnNames as $col): ?>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">
                                        <?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-800">Sin columnas disponibles
                                </th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (mysqli_num_rows($detalles) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($detalles)): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <?php foreach ($columnNames as $col): ?>
                                        <td class="px-6 py-4 text-gray-700" data-label="<?= htmlspecialchars($col) ?>">
                                            <?= htmlspecialchars($row[$col] ?? 'NULL') ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= count($columnNames) ?: 1 ?>" class="px-6 py-8 text-center text-gray-500">
                                    No hay registros.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($totalPaginas > 1): ?>
            <div class="max-w-7xl mx-auto mt-8">
                <div class="flex flex-wrap justify-center gap-1">
                    <?php
                    if ($page > 1): ?>
                        <a href="?page=1"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">«
                            Primera</a>
                        <a href="?page=<?= $page - 1 ?>"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">‹
                            Anterior</a>
                    <?php endif;

                    $start = max(1, $page - 2);
                    $end = min($totalPaginas, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        $isActive = ($i == $page);
                        $class = $isActive ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300';
                        echo "<a href='?page=$i' class='px-3 py-1.5 rounded-lg transition hover:bg-gray-100 $class'>$i</a>";
                    endfor;

                    if ($page < $totalPaginas): ?>
                        <a href="?page=<?= $page + 1 ?>"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">Siguiente
                            ›</a>
                        <a href="?page=<?= $totalPaginas ?>"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">Última
                            »</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- FOOTER -->
        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> - © 2025
            </p>
        </div>

    </div>
</body>

</html>