-- Añadir nomGranja y edad a san_fact_cronograma (granja ya existe; asegurar granja 3 caracteres si se desea)
-- Ejecutar en la base donde está san_fact_cronograma (conexion_grs_joya)

ALTER TABLE san_fact_cronograma ADD COLUMN nomGranja VARCHAR(120) NULL DEFAULT NULL AFTER nomPrograma;
ALTER TABLE san_fact_cronograma ADD COLUMN edad INT NULL DEFAULT NULL AFTER nomGranja;

-- Opcional: acortar granja a 3 caracteres si la columna permite más
-- ALTER TABLE san_fact_cronograma MODIFY COLUMN granja VARCHAR(3) NOT NULL;
