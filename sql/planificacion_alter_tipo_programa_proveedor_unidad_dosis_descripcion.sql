-- Añadir campos a registrar: Proveedor, Unidad, Dosis, Descripcion (por tipo de programa).
-- Ejecutar este script antes de usar los nuevos checkboxes en Configuración > Tipos de programa.
ALTER TABLE san_dim_tipo_programa
  ADD COLUMN campoProveedor TINYINT(1) NOT NULL DEFAULT 0 AFTER campoProducto,
  ADD COLUMN campoUnidad TINYINT(1) NOT NULL DEFAULT 0 AFTER campoProveedor,
  ADD COLUMN campoDosis TINYINT(1) NOT NULL DEFAULT 0 AFTER campoUnidad,
  ADD COLUMN campoDescripcion TINYINT(1) NOT NULL DEFAULT 0 AFTER campoDosis;
