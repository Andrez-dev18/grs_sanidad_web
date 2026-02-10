-- Quitar columna dosis de san_dim_vacuna (dosis se guarda y lee en mitm)
-- y añadir codLaboratorio para laboratorio de vacuna (san_dim_laboratorio_vacuna)
-- Ejecutar en la base donde está san_dim_vacuna (si dosis ya no existe, ignorar el primer ALTER)

ALTER TABLE san_dim_vacuna DROP COLUMN dosis;
ALTER TABLE san_dim_vacuna ADD COLUMN codLaboratorio INT NULL DEFAULT NULL AFTER descripcion;
