<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) {
            window.top.location.href = "../../login.php";
        } else {
            window.location.href = "../../login.php";
        }
    </script>';
    exit();
}

include_once '../../conexion_grs_joya/conexion.php';
$conn = conectar_joya();
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificación</title>
    <link rel="stylesheet" href="../../css/output.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: system-ui, sans-serif; }
        .card-link {
            display: block;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
            padding: 2rem;
            text-decoration: none;
            color: #1f2937;
            transition: all 0.2s ease;
        }
        .card-link:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            border-color: #3b82f6;
            color: #2563eb;
        }
        .card-link i { font-size: 2.5rem; margin-bottom: 1rem; color: #3b82f6; }
        .card-link h3 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; }
        .card-link p { font-size: 0.875rem; color: #6b7280; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Planificación</h1>
        <p class="text-gray-500 mb-8">Seleccione una sección.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <a href="#" onclick="parent.loadDashboardAndData('modules/planificacion/programas/dashboard-programas.php', '📋 Programas', 'Gestión de programas de planificación'); return false;"
               class="card-link">
                <i class="fas fa-list-check"></i>
                <h3>Programas</h3>
                <p>Gestionar programas (tipos, códigos, edades).</p>
            </a>
            <a href="#" onclick="parent.loadDashboardAndData('modules/planificacion/cronograma/dashboard-cronograma.php', '📅 Cronograma', 'Cronograma de planificación'); return false;"
               class="card-link">
                <i class="fas fa-calendar-days"></i>
                <h3>Cronograma</h3>
                <p>Ver y gestionar el cronograma.</p>
            </a>
        </div>
    </div>
</body>
</html>
