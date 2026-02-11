-- Añadir columna posDetalle a san_fact_programa_det (posición del detalle cuando una fila tiene varias edades: 1, 2, 3...)
ALTER TABLE san_fact_programa_det ADD COLUMN posDetalle INT NOT NULL DEFAULT 1 AFTER edad;
