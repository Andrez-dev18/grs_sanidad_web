-- Hacer cod_enf autoincremental en tenfermedades
-- Ejecutar en la base donde está tenfermedades (conexion_grs_joya)

ALTER TABLE tenfermedades
  MODIFY COLUMN cod_enf INT NOT NULL AUTO_INCREMENT;
