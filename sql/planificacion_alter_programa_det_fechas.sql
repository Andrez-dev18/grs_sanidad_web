-- Fechas y periodicidad en detalle (desnormalización para programa especial)
-- Ejecutar una vez. Si las columnas ya existen, omitir los ALTER correspondientes.

-- Columna fechas: JSON array ['YYYY-MM-DD','YYYY-MM-DD'] para modo MANUAL
ALTER TABLE san_fact_programa_det
ADD COLUMN fechas TEXT NULL DEFAULT NULL
COMMENT 'JSON array fechas YYYY-MM-DD (modo MANUAL programa especial)'
AFTER cantidadPorGalpon;

-- Columna intervaloMeses: para modo PERIODICIDAD
ALTER TABLE san_fact_programa_det
ADD COLUMN intervaloMeses INT NULL DEFAULT NULL
COMMENT 'Intervalo meses (modo PERIODICIDAD programa especial)'
AFTER fechas;

-- Columna diaDelMes: para modo PERIODICIDAD
ALTER TABLE san_fact_programa_det
ADD COLUMN diaDelMes INT NULL DEFAULT NULL
COMMENT 'Día del mes (modo PERIODICIDAD programa especial)'
AFTER intervaloMeses;
