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

$codEnvio = $_GET['codEnvio'] ?? '';
if (!$codEnvio)
    die("Código de envío no especificado.");

$cab = mysqli_fetch_assoc(mysqli_query(
    $conexion,
    "SELECT * FROM com_db_solicitud_cab WHERE codEnvio = '" . mysqli_real_escape_string($conexion, $codEnvio) . "'"
));
if (!$cab)
    die("Envío no encontrado.");

$det = mysqli_query($conexion, "
    SELECT 
        d.*,
        tm.nombre AS tipo_muestra_real,
        a.nombre AS analisis_real
    FROM com_db_solicitud_det d
    LEFT JOIN com_tipo_muestra tm ON d.codMuestra = tm.codigo
    LEFT JOIN com_analisis a ON d.codAnalisis = a.codigo
    WHERE d.codEnvio = '" . mysqli_real_escape_string($conexion, $codEnvio) . "'
    ORDER BY d.posSolicitud, d.codAnalisis
");

$agrupado = [];
while ($row = mysqli_fetch_assoc($det)) {
    $pos = $row['posSolicitud'];
    if (!isset($agrupado[$pos]))
        $agrupado[$pos] = [];
    $agrupado[$pos][] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalle Envío - <?= htmlspecialchars($codEnvio) ?></title>
    <link rel="stylesheet" href="css/output.css">
    <style>
        .pos-group {
            background: #f0fdf4;
            border-left: 4px solid #047857;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 6px;
        }

        .detalle-item {
            background: white;
            padding: 0.75rem;
            margin-top: 0.5rem;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .volver {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #047857;
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">
        <div class="flex items-center gap-4 mb-6">
            <a href="envios.php" class="volver">← Volver a Envíos</a>
            <button onclick="exportarDetalle()"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                📊 Exportar Detalle
            </button>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Envío: <?= htmlspecialchars($codEnvio) ?></h1>
        <p class="text-gray-600 mb-6">
            Laboratorio: <strong><?= htmlspecialchars($cab['nomLab']) ?></strong> |
            Fecha: <?= $cab['fecEnvio'] ?> <?= $cab['horaEnvio'] ?>
        </p>

        <?php if (!empty($agrupado)): ?>
                <?php foreach ($agrupado as $pos => $items): ?>
                        <div class="pos-group">
                            <h2 class="font-bold text-lg text-green-800">Posición Solicitud: <?= (int) $pos ?></h2>
                            <?php foreach ($items as $item): ?>
                                    <div class="detalle-item">
                                        <div class="font-medium">
                                            <?= htmlspecialchars($item['analisis_real'] ?? $item['nomAnalisis']) ?>
                                        </div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            Ref: <code><?= htmlspecialchars($item['codRef']) ?></code> |
                                            Tipo Muestra: <?= htmlspecialchars($item['tipo_muestra_real'] ?? $item['nomMuestra']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            Fecha Toma: <?= $item['fecToma'] ?> | Muestras: <?= $item['numMuestras'] ?>
                                        </div>
                                        <?php if (!empty($item['obs'])): ?>
                                                <div class="text-xs italic text-gray-500 mt-1">Obs: <?= htmlspecialchars($item['obs']) ?></div>
                                        <?php endif; ?>
                                    </div>
                            <?php endforeach; ?>
                        </div>
                <?php endforeach; ?>
        <?php else: ?>
                <p class="text-gray-500">Este envío no tiene muestras asociadas.</p>
        <?php endif; ?>
    </div>

    <script>
        function exportarDetalle() {
            const codEnvio = <?= json_encode($codEnvio) ?>;
            let csv = '\uFEFF';
            csv += 'DETALLE DE ENVÍO - ' + codEnvio + '\n';
            csv += 'Fecha de exportación:,' + new Date().toLocaleDateString('es-PE') + '\n\n';
            csv += 'posSolicitud,codRef,fecToma,numMuestras,Tipo Muestra,Análisis,Observación\n';

            let count = 0;
            document.querySelectorAll('.pos-group').forEach(group => {
                const posText = group.querySelector('h2').textContent;
                const pos = posText.match(/Posición Solicitud:\s*(\d+)/)?.[1] || '';

                group.querySelectorAll('.detalle-item').forEach(item => {
                    const lines = item.querySelectorAll('.text-sm, .text-xs');
                    let codRef = '', fecToma = '', numMuestras = '', tipoMuestra = '', analisis = '', obs = '';

                    // Analisis real
                    const analisisEl = item.querySelector('.font-medium');
                    analisis = analisisEl ? analisisEl.textContent.trim().replace(/\bPDFA\b/g, 'BK') : '';

                    // Ref y tipo muestra
                    if (lines[0]) {
                        const firstLine = lines[0].textContent;
                        const refMatch = firstLine.match(/Ref:\s*([^\|]+)/);
                        codRef = refMatch ? refMatch[1].trim().replace(/\bPDFA\b/g, 'BK') : '';
                        const tmMatch = firstLine.match(/Tipo Muestra:\s*(.+)$/);
                        tipoMuestra = tmMatch ? tmMatch[1].trim().replace(/\bPDFA\b/g, 'BK') : '';
                    }

                    // Fecha y número
                    if (lines[1]) {
                        const secondLine = lines[1].textContent;
                        const dateMatch = secondLine.match(/Fecha Toma:\s*([^|]+)/);
                        fecToma = dateMatch ? dateMatch[1].trim() : '';
                        const numMatch = secondLine.match(/Muestras:\s*(\d+)/);
                        numMuestras = numMatch ? numMatch[1] : '';
                    }

                    // Obs
                    if (lines[2] && lines[2].textContent.includes('Obs:')) {
                        obs = lines[2].textContent.replace('Obs: ', '').trim().replace(/\bPDFA\b/g, 'BK');
                    }

                    csv += `"${pos}","${codRef}","${fecToma}","${numMuestras}","${tipoMuestra}","${analisis}","${obs}"\n`;
                    count++;
                });
            });

            if (count === 0) {
                alert('⚠️ No hay datos para exportar.');
                return;
            }

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `Detalle_Envio_${codEnvio.replace(/\//g, '_')}_${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            alert(`✅ ${count} análisis exportados. PDFA reemplazado por BK.`);
        }
    </script>
</body>

</html>