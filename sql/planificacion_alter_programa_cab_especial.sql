-- Añadir columnas para programas especiales en san_fact_programa_cab.
-- Ejecutar ANTES de planificacion_programa_cab_fechas_manuales.sql (que usa diaDelMes).
-- El código usa SHOW COLUMNS para detectarlas; si no existen, omite la funcionalidad.

-- esEspecial: 1 = programa especial (key comparativo = solo granja; fechas por periodicidad o manual)
-- Si existe columna tipo, ejecutar: AFTER tipo. Si no: AFTER descripcion.
ALTER TABLE san_fact_programa_cab
ADD COLUMN esEspecial TINYINT(1) NOT NULL DEFAULT 0
COMMENT '1 = programa especial (fechas por periodicidad o manual)'
AFTER descripcion;

-- modoEspecial: PERIODICIDAD | MANUAL
ALTER TABLE san_fact_programa_cab
ADD COLUMN modoEspecial VARCHAR(30) NULL DEFAULT NULL
COMMENT 'PERIODICIDAD o MANUAL para programas especiales'
AFTER esEspecial;

-- intervaloMeses: intervalo en meses (modo PERIODICIDAD)
ALTER TABLE san_fact_programa_cab
ADD COLUMN intervaloMeses INT NULL DEFAULT NULL
COMMENT 'Intervalo meses para modo PERIODICIDAD'
AFTER modoEspecial;

-- diaDelMes: día del mes (modo PERIODICIDAD)
ALTER TABLE san_fact_programa_cab
ADD COLUMN diaDelMes INT NULL DEFAULT NULL
COMMENT 'Día del mes para modo PERIODICIDAD'
AFTER intervaloMeses;
