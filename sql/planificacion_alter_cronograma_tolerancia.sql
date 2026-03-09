-- Añadir columna tolerancia (int) a san_fact_cronograma y setear 1 a registros existentes
-- Ejecutar en la base donde está san_fact_cronograma (conexion_grs_joya)

-- Si la columna aún no existe:
ALTER TABLE san_fact_cronograma ADD COLUMN tolerancia INT NULL DEFAULT NULL COMMENT 'Días de tolerancia (copiada de san_fact_programa_det)' AFTER edad;

-- Setear tolerancia = 1 en todos los registros existentes (los nuevos se copian desde programa_det al asignar):
UPDATE san_fact_cronograma SET tolerancia = 1 WHERE tolerancia IS NULL OR tolerancia = 0;
