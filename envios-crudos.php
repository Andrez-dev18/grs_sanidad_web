<?php
session_start();
if (empty($_SESSION['active'])) {
    header('Location: login.php');
    exit();
}
include_once '../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion)
    die("Error de conexión.");

// Obtener datos crudos para exportación
$cabData = [];
$detData = [];

$res = mysqli_query($conexion, "SELECT * FROM san_fact_solicitud_cab ORDER BY codEnvio");
while ($row = mysqli_fetch_assoc($res))
    $cabData[] = $row;

$res = mysqli_query($conexion, "SELECT * FROM san_fact_solicitud_det ORDER BY codEnvio, posSolicitud");
while ($row = mysqli_fetch_assoc($res))
    $detData[] = $row;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registros Crudos - Envíos</title>
    <link rel="stylesheet" href="css/output.css">
    <style>
        .raw-table {
            font-size: 0.85rem;
            margin-bottom: 2rem;
        }

        .raw-table th {
            background: #f3f4f6;
        }

        .raw-table td,
        .raw-table th {
            border: 1px solid #e5e7eb;
            padding: 0.5rem;
            vertical-align: top;
            max-width: 200px;
            word-wrap: break-word;
        }

        .section-title {
            background: #1e293b;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            margin: 2rem 0 1rem;
            display: inline-block;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">🔍 Registros Crudos – Envíos y Detalles</h1>
            <div class="flex gap-3">
                <button onclick="exportarCrudo()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    📊 Exportar Todo
                </button>
                <a href="envios.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">← Volver</a>
            </div>
        </div>

        <!-- Cabeceras -->
        <div class="section-title">1. san_fact_solicitud_cab (Cabeceras)</div>
        <div class="overflow-x-auto">
            <table class="raw-table w-full" id="tablaCab">
                <?php if (!empty($cabData)):
                    $cols = array_keys($cabData[0]);
                    echo '<thead><tr>' . implode('', array_map(fn($c) => "<th>" . htmlspecialchars($c) . "</th>", $cols)) . '</tr></thead><tbody>';
                    foreach ($cabData as $row) {
                        echo '<tr>' . implode('', array_map(fn($c) => "<td>" . (isset($row[$c]) ? htmlspecialchars((string) $row[$c]) : 'NULL') . "</td>", $cols)) . '</tr>';
                    }
                    echo '</tbody>';
                else: ?>
                        <tr>
                            <td>No hay registros</td>
                        </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Detalles -->
        <div class="section-title">2. san_fact_solicitud_det (Detalles)</div>
        <div class="overflow-x-auto">
            <table class="raw-table w-full" id="tablaDet">
                <?php if (!empty($detData)):
                    $cols = array_keys($detData[0]);
                    echo '<thead><tr>' . implode('', array_map(fn($c) => "<th>" . htmlspecialchars($c) . "</th>", $cols)) . '</tr></thead><tbody>';
                    foreach ($detData as $row) {
                        echo '<tr>' . implode('', array_map(fn($c) => "<td>" . (isset($row[$c]) ? htmlspecialchars((string) $row[$c]) : 'NULL') . "</td>", $cols)) . '</tr>';
                    }
                    echo '</tbody>';
                else: ?>
                        <tr>
                            <td>No hay registros</td>
                        </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <script>
        function exportarCrudo() {
            let csv = '\uFEFF';

            // Cabeceras
            csv += '=== san_fact_solicitud_cab ===\n';
            const cabHeaders = Array.from(document.querySelectorAll('#tablaCab thead th')).map(th => th.textContent);
            csv += cabHeaders.map(h => `"${h}"`).join(',') + '\n';
            document.querySelectorAll('#tablaCab tbody tr').forEach(tr => {
                const cells = Array.from(tr.querySelectorAll('td')).map(td => `"${td.textContent.replace(/"/g, '""')}"`);
                csv += cells.join(',') + '\n';
            });
            csv += '\n\n';

            // Detalles
            csv += '=== san_fact_solicitud_det ===\n';
            const detHeaders = Array.from(document.querySelectorAll('#tablaDet thead th')).map(th => th.textContent);
            csv += detHeaders.map(h => `"${h}"`).join(',') + '\n';
            document.querySelectorAll('#tablaDet tbody tr').forEach(tr => {
                const cells = Array.from(tr.querySelectorAll('td')).map(td => `"${td.textContent.replace(/"/g, '""')}"`);
                csv += cells.join(',') + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `Envios_Crudo_${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            alert('✅ Registros crudos exportados.');
        }
    </script>
</body>

</html>