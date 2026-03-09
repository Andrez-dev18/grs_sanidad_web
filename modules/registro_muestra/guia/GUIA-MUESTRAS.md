# Guía paso a paso — Módulo Muestras

Esta guía explica el flujo completo del módulo de muestras: conceptos, flujo de uso, secciones web (Registro, Listado, Seguimiento) y configuración (secciones 7.1 a 7.7).

## Conceptos del módulo

### Envío

Un **envío** es el pedido de muestras que se envía al laboratorio. Contiene una o más **solicitudes**, y cada solicitud agrupa un conjunto de **análisis** a realizar.

### Solicitud

Cada **solicitud** corresponde a un pedido de análisis dentro del envío. En el sistema está identificada por código de referencia (granja, campaña, galpón, edad) y tipo de muestra.

### Análisis

Son los estudios que el laboratorio debe realizar. Se configuran en [link:config-tipo-analisis], se agrupan en paquetes en [link:config-paquete] y las opciones de respuesta en [link:config-tipo-respuesta].


## Flujo de uso

| **1. Crear mis envíos** — Defina las características del envío en la sección [link:registro] (2.1 Registro).
| **2. Ver mis envíos** — Para ver los envíos registrados use la sección [link:listado] (2.2 Listado).
| **3. Ver seguimiento** — Para el seguimiento de envíos (resultados de laboratorio, documentos, progreso) use la sección [link:seguimiento] (2.3 Seguimiento).


## Configuración previa

El módulo de Muestras se encuentra en la **Sección 2**. La configuración previa está en la **Sección 7**. Antes de registrar muestras, configure:

| [link:config-transporte] **(7.1)** — Empresas de transporte que aparecen en Registro
| [link:config-laboratorio] **(7.2)** — Laboratorios destino de las muestras
| [link:config-tipo-muestra] **(7.3)** — Tipos de muestra (pollo vivo, agua, etc.)
| [link:config-tipo-analisis] **(7.4)** — Tipos de análisis que se pueden solicitar
| [link:config-paquete] **(7.5)** — Paquetes de análisis (tipo de muestra + análisis que forman el paquete)
| [link:config-tipo-respuesta] **(7.6)** — Tipos de respuesta de cada análisis (útiles en laboratorio)
| [link:config-correo] **(7.7)** — Correo y contactos; aparece en Listado al enviar correo al laboratorio


## Registro

Para registrar el pedido de muestra elija la sección [link:registro] (2.1 Registro).

1. Complete el **encabezado** del envío (granja, campaña, galpón, edad, laboratorio, transporte, etc.).
2. Defina las **solicitudes** del envío.
3. En cada solicitud seleccione los **análisis** a realizar (por paquete o individual).
4. Use [btn:guardar] para guardar el envío.

| Estructura: Envío → Solicitud 1 (análisis A, B, C) | Solicitud 2 (análisis D, E) — cada solicitud puede tener distintos análisis según paquete o selección manual.

[imagen:1]


## Listado

Para ver los envíos registrados use la sección [link:listado] (2.2 Listado). Use [btn:filtrar] o el buscador para localizar envíos.

Por cada envío puede:

| [i:eye] Ver las solicitudes del envío
| [i:pdf] PDF en formato tabla
| [i:file-lines] PDF en formato lista (resumen)
| [i:paper-plane] Envío de correo al laboratorio
| [i:qr] Generar etiqueta QR con el código de envío
| [i:edit] Editar el envío — Solo disponible si **no** se ha enviado correo ni se han registrado resultados de laboratorio
| [i:trash] Eliminar — Solo Admin. Borra todo: análisis y resultados asociados (confirmación previa)

[imagen:2]


## Seguimiento

Para hacer el seguimiento del envío de muestras use la sección [link:seguimiento] (2.3 Seguimiento).

Por cada envío puede:

| [i:eye] Ver las solicitudes, resultados cualitativos, cuantitativos y documentos guardados
| [i:history] Ver el historial de seguimiento
| [i:pdf] Reporte del envío con resultados cualitativos y cuantitativos

[imagen:3]
