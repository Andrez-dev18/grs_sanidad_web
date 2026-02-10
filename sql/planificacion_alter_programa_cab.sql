-- Renombrar tabla de programas y añadir columnas zona y descripcion (ejecutar si ya existe san_plan_programa)
RENAME TABLE san_plan_programa TO san_fact_programa_cab;
ALTER TABLE san_fact_programa_cab ADD COLUMN zona VARCHAR(100) DEFAULT NULL AFTER edad;
ALTER TABLE san_fact_programa_cab ADD COLUMN descripcion VARCHAR(500) DEFAULT NULL AFTER zona;
