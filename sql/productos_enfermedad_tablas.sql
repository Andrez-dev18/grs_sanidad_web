-- Tablas para producto-vacuna y relación producto-enfermedad
-- Motor: MyISAM, charset: latin1, collation: latin1_swedish_ci
-- Ejecutar en la base donde está mitm/ccte (conexion_grs_joya)

-- Enfermedades (si no existe)
CREATE TABLE IF NOT EXISTS tenfermedades (
  cod_enf INT NOT NULL AUTO_INCREMENT,
  nom_enf VARCHAR(255) NOT NULL,
  PRIMARY KEY (cod_enf)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Relación producto (vacuna) - enfermedad: por codProducto, id autoincremental
CREATE TABLE IF NOT EXISTS san_rel_vacuna_enfermedad (
  id INT NOT NULL AUTO_INCREMENT,
  codProducto VARCHAR(50) NOT NULL,
  codEnfermedad INT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_producto_enfermedad (codProducto, codEnfermedad)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Campos en mitm para producto como vacuna (ejecutar si no existen)
-- ALTER TABLE mitm ADD COLUMN codVacuna INT NULL DEFAULT NULL;
-- ALTER TABLE mitm ADD COLUMN descripcion_vacuna VARCHAR(500) NULL DEFAULT NULL;
