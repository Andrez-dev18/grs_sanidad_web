<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['active'])) {
    echo json_encode(['ok' => false, 'respuesta' => 'Debe iniciar sesión.']);
    exit;
}

$pregunta = trim((string)($_POST['pregunta'] ?? $_GET['pregunta'] ?? ''));
if ($pregunta === '') {
    echo json_encode(['ok' => false, 'respuesta' => 'Escriba una pregunta.']);
    exit;
}

$p = mb_strtolower($pregunta);
$p = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $p);
$p = preg_replace('/\s+/', ' ', trim($p));
// Normalizar acentos para que "asignación" coincida con "asignacion"
$p = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $p);
$palabras = array_filter(preg_split('/\s+/', $p), function($w) { return strlen($w) >= 2; });
$textoNormalizado = $p;

// Orden: acciones (editar, crear, agregar) antes que conceptos genéricos para priorizar respuestas específicas
$faq = [
    ['keys' => ['editar', 'edito', 'modificar', 'modifico', 'cambio', 'cambiar', 'editar programa', 'editar asignacion'], 'resp' => 'Para **editar** un programa o asignación: vaya al Listado, use el ícono de editar en la fila. Las asignaciones en fechas anteriores al día actual se mantienen.'],
    ['keys' => ['agregar fila', 'agregar detalle', 'detalle programa', 'productos dosis', 'agregar detalles', 'como agrego'], 'resp' => 'Para agregar los detalles del programa (producto, proveedor, ubicación, unidades, dosis, frascos, edad), presione el botón **Agregar fila** en el registro de programa.'],
    ['keys' => ['crear asignacion', 'como creo asignacion', 'crear cronograma', 'calcular fechas'], 'resp' => 'Para **crear una asignación**: elija el programa, seleccione granjas (por zona/subzona o individual), indique el año. Presione **Calcular fechas** y luego **Guardar**.'],
    ['keys' => ['crear programa', 'como creo programa', 'nuevo programa'], 'resp' => 'Para **crear un programa**: complete encabezado (tipo, nombre, despliegue, descripción) y detalle (producto, proveedor, ubicación, unidades, dosis, frascos, edad). Use **Agregar fila** para cada detalle y **Guardar** al finalizar.'],
    ['keys' => ['programa', 'modelo', 'plantilla', 'que es programa', 'que es un programa'], 'resp' => 'Un **programa** es un modelo que describe una actividad de sanidad (productos, dosis, edades). Incluye fecha de inicio obligatoria y, si aplica, fecha de fin. Una vez creado, se puede asignar a distintas granjas o zonas sin volver a definir productos ni dosis.'],
    ['keys' => ['que es asignacion', 'que es la asignacion', 'que es una asignacion', 'definicion asignacion'], 'resp' => 'Una **asignación** es la aplicación de un programa a una granja, zona o subzona en fechas concretas. Cada asignación vincula un programa con un lugar y un periodo de tiempo. En otras palabras: lleva el programa que ya creó a las granjas que elija y a las fechas que calcule.'],
    ['keys' => ['cronograma', 'calendario', 'visualizacion', 'eventos', 'que es cronograma'], 'resp' => 'El **cronograma** es la visualización de las asignaciones planificadas en el tiempo. Se consulta mediante el Calendario (vista por día, semana, mes o año) o el Comparativo (planificado vs ejecutado por fecha).'],
    ['keys' => ['edad', 'edades', '-1', 'fecha carga', 'que es edad', 'edad menos uno'], 'resp' => 'En el campo **Edad** puede ingresar varias separadas por comas (ej: 1, 2, 5). Edad 1 = fecha de carga de pollo. Un día antes: -1 (no 0). Dos días antes: -2.'],
    ['keys' => ['edad 0', 'puedo ingresar edad 0', 'edad cero', 'ingresar 0'], 'resp' => 'No use **edad 0**. Para el día de la fecha de carga use **edad 1**. Para un día antes use **-1** (no 0). Ejemplo: si la fecha de carga es 15/02, edad 1 = 15/02, edad -1 = 14/02.'],
    ['keys' => ['guardar', 'guardar programa', 'guardar asignacion'], 'resp' => 'Al finalizar el formulario, presione el botón **Guardar** para registrar el programa o la asignación.'],
    ['keys' => ['filtrar', 'buscar', 'listado'], 'resp' => 'En los listados use el botón **Filtrar** para buscar por granja, zona, programa, fechas u otros criterios.'],
    ['keys' => ['eliminar', 'borrar', 'quitar'], 'resp' => '**Eliminar programa:** borra el programa y todas sus asignaciones (confirmación previa). **Eliminar asignación:** borra todos los registros de esa asignación (confirmación previa).'],
    ['keys' => ['calendario', 'vista dia', 'semana', 'mes', 'como veo calendario', 'ver calendario'], 'resp' => 'El **Calendario** ofrece vistas por día, semana, mes o año. Navegue con los controles superiores. Puede obtener un reporte diario en PDF y enviarlo por WhatsApp.'],
    ['keys' => ['comparativo', 'planificado', 'ejecutado'], 'resp' => 'El **Comparativo** muestra planificado vs ejecutado por fecha. Use el botón Filtrar para granja, zona y fechas.'],
    ['keys' => ['configuracion', 'tipos programa', 'proveedor', 'productos', 'enfermedades'], 'resp' => 'En **Configuración** (menú 7) encontrará: Tipos de Programa, Proveedor, Productos, Enfermedades, Número telefónico y Notificaciones de usuarios (solo admin).'],
    ['keys' => ['flujo de registro', 'flujo registro', 'cual es el flujo', 'flujo', 'pasos', 'como empezar', 'donde empiezo'], 'resp' => '**Flujo:** 1) Crear programa (encabezado y detalle, agregar fila, guardar). 2) Ver en listado si desea. 3) Crear asignación (programa, granjas, año, calcular fechas, guardar). 4) Ver asignaciones en listado. 5) Ver eventos en el Calendario.'],
];

$mejor = null;
$mejorPuntos = 0;

foreach ($faq as $item) {
    $puntos = 0;
    // Prioridad alta: frase completa coincide con alguna key
    foreach ($item['keys'] as $k) {
        if (strpos($textoNormalizado, $k) !== false || strpos($k, $textoNormalizado) !== false) {
            $puntos += 5;
            break;
        }
    }
    // Prioridad media: palabras sueltas
    if ($puntos === 0) {
        foreach ($palabras as $pal) {
            foreach ($item['keys'] as $k) {
                if (strpos($k, $pal) !== false || strpos($pal, $k) !== false || $pal === $k) {
                    $puntos += 2;
                    break;
                }
            }
        }
    }
    if ($puntos > $mejorPuntos) {
        $mejorPuntos = $puntos;
        $mejor = $item['resp'];
    }
}

if ($mejor !== null && $mejorPuntos >= 2) {
    $mejor = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $mejor);
    echo json_encode(['ok' => true, 'respuesta' => $mejor]);
} else {
    echo json_encode(['ok' => true, 'respuesta' => 'No encontré una respuesta exacta. Intente con: "¿Qué es un programa?", "¿Cómo agrego detalles?", "¿Qué es la edad -1?" o revise la guía completa.']);
}
