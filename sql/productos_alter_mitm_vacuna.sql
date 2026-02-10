-- Añadir columnas vacuna en mitm (ejecutar en la base donde está mitm)
-- Permite marcar producto como vacuna y guardar descripción

ALTER TABLE mitm ADD COLUMN codVacuna INT NULL DEFAULT NULL;
ALTER TABLE mitm ADD COLUMN descripcion_vacuna VARCHAR(500) NULL DEFAULT NULL;
