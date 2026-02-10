-- Quitar columnas Zona, Despliegue y Descripción de san_dim_tipo_programa.
-- Esos campos se muestran siempre al registrar/editar un programa (dashboard-programas).
-- Ejecutar en la base donde está san_dim_tipo_programa.
-- Si alguna columna ya no existe, ignorar el error de esa línea.

ALTER TABLE san_dim_tipo_programa DROP COLUMN campoZona;
ALTER TABLE san_dim_tipo_programa DROP COLUMN campoDespliegue;
ALTER TABLE san_dim_tipo_programa DROP COLUMN campoDescripcion;
