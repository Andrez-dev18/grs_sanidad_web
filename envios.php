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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Envíos de Muestras</title>
    <link rel="stylesheet" href="css/output.css">
    <style>
        body {
            background: #f9fafb;
            font-family: system-ui;
        }

        .envio-card {
            border-left: 4px solid #8b5cf6;
            background: #f5f3ff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .envio-card:hover {
            background: #ede9fe;
        }

        .cod-envio {
            font-weight: bold;
            color: #7c3aed;
            font-size: 1.05rem;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                    📤 Envíos de Muestras
                </h1>
                <p class="text-gray-600 mt-1">Haga clic en un envío para ver sus detalles agrupados por posición.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="exportarListaEnvios()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    📊 Exportar Lista
                </button>
                <a href="envios-crudos.php" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">
                    📋 Registros Crudos
                </a>
            </div>
        </div>

        <div class="max-w-5xl mx-auto" id="listaEnvios">
            <?php
            $sql = "SELECT * FROM san_fact_solicitud_cab ORDER BY fecEnvio DESC, horaEnvio DESC";
            $res = mysqli_query($conexion, $sql);
            if (mysqli_num_rows($res) > 0):
                while ($row = mysqli_fetch_assoc($res)):
                    ?>
                    <div class="envio-card" onclick="verDetalles('<?= addslashes($row['codEnvio']) ?>')">
                        <div class="cod-envio"><?= htmlspecialchars($row['codEnvio']) ?></div>
                        <div class="text-sm text-gray-600 mt-1">
                            <?= $row['fecEnvio'] ?>         <?= $row['horaEnvio'] ?> |
                            Lab: <?= htmlspecialchars($row['nomLab']) ?> |
                            Transporte: <?= htmlspecialchars($row['nomEmpTrans']) ?>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Registrado por: <?= htmlspecialchars($row['usuarioRegistrador']) ?>
                        </div>
                    </div>
                    <?php
                endwhile;
            else:
                ?>
                <div class="text-center py-12 text-gray-500">No hay envíos registrados.</div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function verDetalles(cod) {
            window.location = 'detalle-envio.php?codEnvio=' + encodeURIComponent(cod);
        }

        function exportarListaEnvios() {
            const cards = document.querySelectorAll('.envio-card');
            if (cards.length === 0) {
                alert('⚠️ No hay envíos para exportar.');
                return;
            }

            let csv = '\uFEFF';
            csv += 'LISTA DE ENVÍOS - GRS\n';
            csv += 'Fecha de exportación:,' + new Date().toLocaleDateString('es-PE') + '\n\n';
            csv += 'codEnvio,fecEnvio,horaEnvio,nomLab,nomEmpTrans,usuarioRegistrador\n';

            cards.forEach(card => {
                const codEnvio = card.querySelector('.cod-envio').textContent.trim();
                const meta = card.querySelector('.text-sm.text-gray-600').textContent;
                const registrador = card.querySelector('.text-xs.text-gray-500')?.textContent.replace('Registrado por: ', '') || '';

                let fecEnvio = '', horaEnvio = '', nomLab = '', nomEmpTrans = '';
                const parts = meta.split(' | ');
                if (parts[0]) {
                    const dt = parts[0].split(' ');
                    fecEnvio = dt[0] || '';
                    horaEnvio = dt[1] || '';
                }
                if (parts[1]) nomLab = parts[1].replace('Lab: ', '').trim();
                if (parts[2]) nomEmpTrans = parts[2].replace('Transporte: ', '').trim();

                csv += `"${codEnvio}","${fecEnvio}","${horaEnvio}","${nomLab}","${nomEmpTrans}","${registrador}"\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `Envios_Lista_${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            alert('✅ Lista de envíos exportada.');
        }
    </script>
</body>

</html>