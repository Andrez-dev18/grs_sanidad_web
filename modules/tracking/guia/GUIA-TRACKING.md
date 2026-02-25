# Guía del módulo de Tracking

Esta guía explica el seguimiento logístico de los envíos de muestras: registro de recepciones (transportista y laboratorio), consulta por código de envío y gestión de pendientes.

## Conceptos del módulo

### Código de envío

Identificador único de cada envío. Se usa para registrar recepciones y consultar el estado. El código [i:qr] QR se obtiene en [link:listado].

### Recepción

Registro de que el envío fue recibido en un punto del trayecto: **recepción por transportista** (recogida en origen) o **recepción por laboratorio** (llegada al destino).



## Flujo de uso

| 1. [link:escaneo] Registrar recepción (QR o código)
| 2. [link:seguimiento] Consultar recepciones por código de envío
| 3. [link:pendientes] Gestionar envíos pendientes y ver historial

## Escaneo

**Registrar recepción:** [link:escaneo] Use el código de envío o escanee el [i:qr] QR (obtenido en [link:listado]) para registrar la recepción.

| Por transportista:
| Indica que recogió el envío en origen
| Puede adjuntar evidencia fotográfica y observaciones

| Por laboratorio:
| Indica que el envío llegó al laboratorio
| Puede adjuntar evidencia fotográfica, observaciones y documentos de respuesta

| Flujo: escanear [i:qr] o ingresar código → completar datos → [btn:guardar]

[imagenes:1,2]

## Seguimiento

**Consultar recepciones:** [link:seguimiento] Ingrese el código de envío para ver todas las recepciones: recogida por transportista y recepción en laboratorio. Permite rastrear en qué punto está el envío.

| Información mostrada: fechas, evidencia fotográfica y observaciones por cada recepción

[imagenes:3,4]

## Pendientes

**Gestionar entregas:** [link:pendientes] Dos pestañas: [i:list] Todos los registros e [i:th] Pendientes.

| Tab "Todos los registros":
| Historial de recepciones por código de envío
| Acciones por fila: [i:eye] Ver evidencia, [i:edit] Editar, [i:trash] Eliminar

[imagen-small:5]

| Tab "Pendientes":
| Envíos a los que **falta recoger** por el transportista
| Envíos a los que **falta recepción** en laboratorio
| Use [btn:escanear] para registrar la recepción pendiente

| Filtros: [i:filter] periodo, ubicación (GRS, Transporte, Laboratorio)
| Acciones: [btn:filtrar] y [btn:limpiar]

[imagen-small:6]
