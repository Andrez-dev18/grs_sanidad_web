-- Migrar san_rel_vacuna_enfermedad de codVacuna a codProducto (y añadir id)
-- Requiere que san_dim_vacuna exista para migrar. Ejecutar en orden. Al final opcional: DROP TABLE san_dim_vacuna;
-- 1) Añadir codProducto
ALTER TABLE san_rel_vacuna_enfermedad ADD COLUMN codProducto VARCHAR(50) NULL DEFAULT NULL AFTER codVacuna;
-- 2) Rellenar codProducto desde san_dim_vacuna
UPDATE san_rel_vacuna_enfermedad r INNER JOIN san_dim_vacuna v ON v.codigo = r.codVacuna SET r.codProducto = v.codProducto;
-- 3) Añadir id, quitar PK compuesta, poner PK(id), quitar codVacuna
ALTER TABLE san_rel_vacuna_enfermedad ADD COLUMN id INT NOT NULL AUTO_INCREMENT FIRST;
ALTER TABLE san_rel_vacuna_enfermedad DROP PRIMARY KEY, ADD PRIMARY KEY (id);
ALTER TABLE san_rel_vacuna_enfermedad MODIFY codProducto VARCHAR(50) NOT NULL;
ALTER TABLE san_rel_vacuna_enfermedad DROP COLUMN codVacuna;
ALTER TABLE san_rel_vacuna_enfermedad ADD UNIQUE KEY uk_producto_enfermedad (codProducto, codEnfermedad);
-- 4) Opcional: DROP TABLE IF EXISTS san_dim_vacuna;
