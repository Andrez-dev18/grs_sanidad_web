-- Lista de fechas manuales para programas especiales (modo MANUAL).
-- Se guarda como JSON array de strings 'YYYY-MM-DD'.

ALTER TABLE san_fact_programa_cab
ADD COLUMN fechas_manuales TEXT NULL DEFAULT NULL
COMMENT 'JSON array de fechas YYYY-MM-DD para modo MANUAL'
AFTER diaDelMes;
