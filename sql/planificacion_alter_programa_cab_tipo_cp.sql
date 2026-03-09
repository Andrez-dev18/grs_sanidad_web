-- Agregar campo tipo a san_fact_programa_cab (para programas CP - Control de Plagas)
-- Valores: ROEDORES, GARRAPATAS, INSECTOS
-- Ejecutar en la base de datos sanidad

ALTER TABLE san_fact_programa_cab
ADD COLUMN tipo VARCHAR(50) NULL DEFAULT NULL
COMMENT 'Tipo de control de plagas: ROEDORES, GARRAPATAS, INSECTOS (solo para programas CP)'
AFTER descripcion;
