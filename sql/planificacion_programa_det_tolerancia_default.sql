-- Asignar tolerancia = 1 a registros de detalle que aún no tengan valor.
-- Ejecutar después de agregar la columna tolerancia a san_fact_programa_det.

UPDATE san_fact_programa_det
SET tolerancia = 1
WHERE tolerancia IS NULL;
