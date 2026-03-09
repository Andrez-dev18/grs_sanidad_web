# Guía de los Dashboards

Los dashboards son pantallas que muestran **resúmenes visuales** de los datos del sistema mediante gráficos, tarjetas y tablas. En lugar de revisar listados uno por uno, puede ver de un vistazo tendencias, totales y estados clave.

**¿Cuándo usar cada uno?** Depende de lo que necesite analizar: datos generales de muestras y envíos, indicadores de necropsias, o el estado logístico de las entregas.

## Dashboard General

**Vista:** [link:dashboard-general]

### ¿Para qué sirve?

Le da una **visión rápida del estado general** del sistema: cuántos envíos se han hecho, cómo evolucionan por mes, qué muestras y análisis son los más solicitados, y el estado de los resultados (completados, pendientes, cuantitativos vs cualitativos).

### ¿Qué encontrará?

| [i:cards] Tarjetas superiores: totales de envíos, completados, pendientes, etc.
| [i:table] Tabla "Últimos 10 envíos": códigos, fechas y estado de los envíos más recientes
| [i:chart-bar] Gráfico "Envíos por mes": evolución mensual del año seleccionado
| [i:chart-pie] Gráfico "Estado general": distribución entre pendientes y completados
| [i:chart-line] Gráfico "Estado detallado por tipo": cuantitativas vs cualitativas
| [i:top] Gráficos "Top 10": muestras y análisis más solicitados

### Cómo usarlo

1. Seleccione el **año** en el filtro superior para ver los datos de ese período.
2. Revise las tarjetas para los totales principales.
3. Use los gráficos para identificar tendencias y los análisis más frecuentes.

[imagen:1]

## Dashboard Indicadores

**Vista:** [link:dashboard-indicadores]

### ¿Para qué sirve?

Ofrece un **análisis más detallado** con indicadores específicos: muestras enviadas, enfermedades más repetidas, resultados completados y, **estadísticas de necropsias** por sistema, hallazgos y granjas. 

### ¿Qué encontrará?

| Parte superior — Indicadores generales:
| [i:vial] Muestras enviadas (por año, mes o día)
| [i:disease] Enfermedades más repetidas
| [i:flask] Resultados completados (por día, semana o mes)
| [i:top] Análisis con más/menos resultados registrados

| Parte inferior — Estadísticas de Necropsias [i:filter] (con filtros):
| [i:necropsia] Impacto por Sistema: severidad promedio por sistema (digestivo, respiratorio, etc.)
| [i:necropsia] Detalle por Nivel (Órgano): seleccione un nivel para ver parámetros específicos
| [i:top] Top 10 Hallazgos Frecuentes: lesiones más reportadas

### Cómo usarlo

1. Use los botones **Por año / Por mes / Por día** en cada gráfico para cambiar el período.
2. En la sección de Necropsias, despliegue los **Filtros** (granja, galpón) y aplique para acotar los datos.
3. En "Detalle por Nivel", elija un nivel (órgano) para ver el gráfico dinámico de sus parámetros.
4. Use "Mostrar menos frecuentes" en Análisis para ver los menos solicitados.

[imagen:2]

## Dashboard Tracking

**Vista:** [link:dashboard-tracking]

### ¿Para qué sirve?

Muestra el **estado logístico de los envíos**: cuántos están pendientes (sin recoger o sin llegar al laboratorio), cuántos completados, tiempos de demora por etapa y los análisis más solicitados. Pensado para transportistas, coordinación con laboratorios y seguimiento de entregas.

### ¿Qué encontrará?

| [i:truck] Envíos realizados: gráfico por día, semana o mes
| [i:chart-pie] Estado general de envíos: gráfico circular (pendientes vs completados)
| [i:top] Top 10 Análisis más solicitados: qué análisis se piden con más frecuencia
| [i:clock] Tiempo promedio de demora por etapa: en horas o días (GRS → Transporte → Laboratorio)

### Cómo usarlo

1. Use los botones **Por día / Por semana / Por mes** para cambiar la agrupación de envíos.
2. En "Tiempo promedio de demora", cambie entre **En horas** y **En días** según prefiera.
3. Un envío se considera **completado** cuando pasa por GRS → Transporte → Laboratorio.

[imagen:3]
