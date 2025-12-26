-- Script para agregar el campo fecha_registro_lab a la tabla san_analisis_pollo_bb_adulto
-- Ejecutar este script en la base de datos 'sanidad'

ALTER TABLE san_analisis_pollo_bb_adulto 
ADD COLUMN fecha_registro_lab DATE DEFAULT NULL 
COMMENT 'Fecha de registro del laboratorio' 
AFTER fecha_informe;

-- Verificar que el campo se agregó correctamente
-- SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME = 'san_analisis_pollo_bb_adulto' 
-- AND COLUMN_NAME = 'fecha_registro_lab';
