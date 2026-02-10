-- Añadir columna despliegue a la cabecera de programa (si no existe)
ALTER TABLE san_fact_programa_cab ADD COLUMN despliegue VARCHAR(200) DEFAULT NULL AFTER zona;
