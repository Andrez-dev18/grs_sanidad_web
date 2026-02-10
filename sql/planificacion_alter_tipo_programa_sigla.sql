-- Añadir columna sigla a san_dim_tipo_programa (ejecutar si la tabla ya existía sin ella)
ALTER TABLE san_dim_tipo_programa ADD COLUMN sigla VARCHAR(20) DEFAULT NULL AFTER nombre;
