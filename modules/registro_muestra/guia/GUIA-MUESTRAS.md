# Guía del módulo de Muestras

Esta guía explica el flujo completo de muestras: registro del envío, listado con solicitudes y análisis, seguimiento de resultados y configuración previa (secciones 7.1 a 7.7).

## Conceptos del módulo

### Envío

Un **envío** es el pedido de muestras que se envía al laboratorio. Contiene una o más **solicitudes**, y cada solicitud agrupa un conjunto de **análisis** a realizar.

### Solicitud

Cada solicitud esta identificado por un código de referencia (granja + campaña + galpón + edad), es un pedido de análisis que corresponden a un tipo de muestra.

### Análisis

Son los estudios que el laboratorio debe realizar. Se configuran en [link:config-tipo-analisis] y se agrupan en paquetes en [link:config-paquete]. Las opciones de respuesta se pueden configurar en [link:config-tipo-respuesta].


## Configuración previa

Antes de registrar muestras, configure en la sección **7. Configuración**:

| [link:config-transporte] — Quién realiza el envío
| [link:config-laboratorio] — Destino de las muestras
| [link:config-tipo-muestra] — Clasificación (pollo vivo, hisopados, etc.)
| [link:config-tipo-analisis] — Qué estudios se pueden solicitar
| [link:config-paquete] — Conjuntos predefinidos de análisis
| [link:config-tipo-respuesta] — Opciones para resultados cualitativos
| [link:config-correo] — Cuenta para enviar reportes al laboratorio


## Registro

**Crear envío:** [link:registro] Registre el pedido de muestras. Defina las **solicitudes** y en cada una seleccione los **análisis** a realizar (por paquete o individual). [btn:guardar] para guardar el envío.

| Estructura:
| Envío → Solicitud 1 (análisis A, B, C)
|       → Solicitud 2 (análisis D, E)
| Cada solicitud puede tener distintos análisis según el paquete o selección manual

| Acciones: [btn:guardar] Guardar envío

[imagen:1]

## Listado

**Consultar:** [link:listado] Use [btn:filtrar] para buscar envíos. Expanda cada fila para ver las **solicitudes** y su detalle.

| Acciones disponibles:
| [i:eye] Ver las solicitudes del envío
| [i:pdf] Reporte tabla — PDF en formato tabla
| [i:file-lines] Reporte resumen — PDF en formato lista
| [i:paper-plane] Enviar correo — Envía el reporte PDF al laboratorio
| [i:qr] Etiqueta QR — Genera etiqueta para escaneo
| [i:edit] Editar — Solo disponible si **no** se ha enviado correo ni se han registrado resultados de laboratorio
| [i:trash] Eliminar — Solo para Admin. [i:warning] Borra todo: análisis y resultados asociados (confirmación previa)

| Restricciones:
| Editar: bloqueado si ya se envió correo o hay resultados de laboratorio
| Eliminar: solo usuarios con rol Admin

[imagen:2]

## Seguimiento

Para hacer el seguimiento al envío de muestras puedes hacerlo en la sección [link:seguimiento].

Por cada envío puedes:

| [i:eye] Puedes ver las solicitudes, los resultados cualitativos, cuantitativos y los documentos guardados.
| [i:history] Para ver el historial de seguimiento.
| [i:pdf] Reporte del envío con resultados cualitativos y cuantitativos.

[imagen:3]
