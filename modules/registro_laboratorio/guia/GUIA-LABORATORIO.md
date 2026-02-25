# Guía del módulo de Laboratorio

Esta guía explica cómo registrar los resultados que envía el laboratorio para cada solicitud de muestras. Las opciones de respuesta cualitativa se configuran en [link:config-tipo-respuesta].

## Conceptos del módulo

### Resultados de laboratorio

Por cada **solicitud** del envío de muestras, el laboratorio devuelve resultados. Usted los ingresa en [link:resultados] para que queden disponibles en el [link:seguimiento] de muestras.

### Resultado cualitativo

Es la respuesta por cada **análisis** solicitado. Las opciones disponibles (positivo, negativo, reactivo, etc.) se definen en [link:config-tipo-respuesta].

### Resultado cuantitativo

Son valores numéricos que el laboratorio reporta: DATO, GMEAN, CV%, SD, COUNT, observaciones y hasta 25 niveles.

[imagen:1]

## Ingreso de resultados

**Registrar:** [link:resultados] Use [btn:filtrar] para buscar el envío. Seleccione el envío y la solicitud. Por cada análisis que se envió al laboratorio:

| Resultados cualitativos:
| Elija la opción de respuesta (configurada en [link:config-tipo-respuesta]) por cada análisis
| [i:plus] Puede adjuntar **enfermedades/análisis extras** no contemplados en la solicitud original
| [i:paperclip] Adjunte los **documentos** que envió el laboratorio ([i:pdf] PDF, imágenes, etc.)

| Resultados cuantitativos:
| DATO — Valor principal reportado
| GMEAN — Media geométrica
| CV % — Coeficiente de variación
| SD — Desviación estándar
| COUNT — Conteo
| Observaciones — Notas adicionales
| Niveles — Hasta 25 valores numéricos según el análisis

| Al finalizar: [btn:guardar-resultados]

[imagen:1]

## Flujo

| 1. El envío se registra en [link:registro-muestras] con sus solicitudes y análisis
| 2. El laboratorio procesa y devuelve resultados
| 3. En [link:resultados] se ingresan cualitativos y cuantitativos por solicitud
| 4. En [link:seguimiento] se visualiza el estado (completado/pendiente) y el reporte [i:pdf]
