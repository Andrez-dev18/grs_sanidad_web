-- Añadir columna observaciones a san_fact_cronograma (motivo de la anomalía en asignación eventual)
-- Ejecutar en la base donde está san_fact_cronograma (conexion_grs_joya)

ALTER TABLE san_fact_cronograma ADD COLUMN observaciones VARCHAR(500) NULL DEFAULT NULL COMMENT 'Motivo de la anomalía (asignación eventual)' AFTER edad;
