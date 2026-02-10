-- Quitar columna edad de san_fact_programa_cab (la edad va en el detalle, por fila)
-- Ejecutar en la base donde está san_fact_programa_cab
ALTER TABLE san_fact_programa_cab DROP COLUMN edad;
