# Guía del módulo de Planificación

Esta guía explica cómo usar el módulo de Planificación (programas, asignaciones, cronograma) y las secciones de Configuración relacionadas.

## Conceptos del módulo

### Programa

Es un modelo que describe una actividad de sanidad (vacunas en granja, necropsias, etc). Una vez creado, se puede asignar a distintas granjas.

### Asignación

Es la aplicación de un programa a una granja, zona o subzona en un periodo concreto.

### Cronograma

Es la visualización de las asignaciones planificadas en el tiempo.

## Flujo de registro

1. **Crear mi programa** — Defino las características de mi programa. Sección [link:programa-registro].
2. **Crear mi asignación** — Selecciono mi programa creado, el año y las granjas. Sección [link:asignacion-registro].
3. **Ver cronograma** — Veo en un calendario todas las asignaciones planificadas. Sección [link:calendario].

[flow:registro]

## Programa - Registro

Elige la sección [link:programa-registro] de tu portal de Sanidad.

Elija el tipo de programa y complete el encabezado. Para agregar los detalles del programa presione [btn:agregar-fila].

**Proveedor:** En 7.10 Productos puede asignar un proveedor al producto y se cargará por defecto. O bien, puede buscarlo directamente con el icono de lupa.

[render:proveedor]

**Descripción:** En la sección [link:productos] (7.10 Productos) puede editar un producto e indicar si es una vacuna y seleccionar las enfermedades.

[render:descripcion]

**Edad:** En el campo edad puede ingresar varias separadas por comas (ej: 1, 2, 5).

| Ejemplo (fecha carga 15/02/2026):
| edad 1 → 15/02
| edad 2 → 16/02
| edad -1 → 14/02
| No use edad 0; para un día antes use -1.

Al finalizar, presione [btn:guardar].

[render:edad]

[imagen:1]

## Programa - Listado

Para ver tus programas registrados, ve a la sección [link:programa-listado].

Usar [i:filter] para buscar.

| Acciones por fila:
| [i:eye] Ver cabecera y detalles
| [i:pdf] Genera reporte PDF
| [i:edit] Modifica el programa. Las asignaciones ya creadas en fechas anteriores al día actual se mantienen.
| [i:copy] Crea una copia del programa
| [i:trash] Borra el programa y todas sus asignaciones relacionadas

[imagen:2]

## Asignación - Registro

Puedes registrar una asignación en la sección [link:asignacion-registro].

| Pasos:
| 1. Elegir programa
| 2. Seleccionar granjas
| 3. Calcular fechas
| 4. Guardar

[btn:calcular-fechas] [btn:guardar]

[imagen:3]

## Asignación - Listado

Para ver tus asignaciones registradas, ve a la sección [link:asignacion-listado].

Usar [i:filter] para buscar.

| Acciones por fila:
| [i:eye] Ver detalles de la asignación
| [i:pdf] Genera reporte PDF
| [i:edit] Modifica la asignación. Las asignaciones ya creadas en fechas anteriores al día actual se mantienen.
| [i:trash] Borra la asignación con todos sus detalles

[imagen:4]

## Cronograma

Para ver el cronograma ve a la sección [link:calendario]. Usa vistas por día, semana, mes o año para ver las asignaciones.

Puedes obtener un reporte diario [i:pdf] y enviar este reporte por [i:whatsapp] WhatsApp.

[imagen:5]

## Comparativo

[link:comparativo] [btn:filtrar] por granja, zona o fechas. Revisar la tabla planificado vs ejecutado.

[imagen:6]

## Configuración

Se encuentra en la Sección 7. Configuración.

### Tipos de Programa

[link:tipo-programa] Configure los tipos de programa (ej: Vacunación, Despique). Indique nombre, sigla (se usará para el código del programa) y establezca qué campos se ingresan en el registro de programa.

[imagen:7]

### Proveedor

[link:proveedor] Sección 7.9. Registre proveedores de productos (vacunas, medicamentos). Puede establecer una abreviatura que aparecerá como nombre corto en los reportes.

[imagen:8]

### Productos

[link:productos] Sección 7.10. Edite los productos existentes enlazándolos con un proveedor (puede o no estar en el maestro de proveedores), dosis y unidad.

Si el producto es una vacuna puede elegir y marcar las enfermedades relacionadas; esta información aparece en la descripción del producto.

[imagen:9]

### Enfermedades

[link:enfermedades] Sección 7.11. Agregue y edite la lista de enfermedades.

[imagen:10]

### Número telefónico

[link:whatsapp] Sección 7.12. Agregue su número telefónico para poder enviarle notificaciones por WhatsApp.

[imagen:11]

[admin:notificaciones]
