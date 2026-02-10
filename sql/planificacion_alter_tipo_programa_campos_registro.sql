-- Campos a registrar por tipo de programa (camelCase). Nuevos tipos: todos los check en 0 (DEFAULT 0).

-- Opción A: Si la tabla ya tiene columnas en snake_case, renombrar a camelCase y DEFAULT 0:
/*
ALTER TABLE san_dim_tipo_programa
  CHANGE COLUMN campo_zona campoZona TINYINT(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN campo_despliegue campoDespliegue TINYINT(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN campo_descripcion campoDescripcion TINYINT(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN campo_ubicacion campoUbicacion TINYINT(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN campo_producto campoProducto TINYINT(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN campo_unidades campoUnidades TINYINT(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN campo_unidad_dosis campoUnidadDosis TINYINT(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN campo_numero_frascos campoNumeroFrascos TINYINT(1) NOT NULL DEFAULT 0,
  CHANGE COLUMN campo_edad_aplicacion campoEdadAplicacion TINYINT(1) NOT NULL DEFAULT 0;
*/

-- Opción B: Si la tabla NO tiene estas columnas, añadirlas en camelCase (DEFAULT 0):
/*
ALTER TABLE san_dim_tipo_programa
  ADD COLUMN campoZona TINYINT(1) NOT NULL DEFAULT 0 AFTER sigla,
  ADD COLUMN campoDespliegue TINYINT(1) NOT NULL DEFAULT 0 AFTER campoZona,
  ADD COLUMN campoDescripcion TINYINT(1) NOT NULL DEFAULT 0 AFTER campoDespliegue,
  ADD COLUMN campoUbicacion TINYINT(1) NOT NULL DEFAULT 0 AFTER campoDescripcion,
  ADD COLUMN campoProducto TINYINT(1) NOT NULL DEFAULT 0 AFTER campoUbicacion,
  ADD COLUMN campoUnidades TINYINT(1) NOT NULL DEFAULT 0 AFTER campoProducto,
  ADD COLUMN campoUnidadDosis TINYINT(1) NOT NULL DEFAULT 0 AFTER campoUnidades,
  ADD COLUMN campoNumeroFrascos TINYINT(1) NOT NULL DEFAULT 0 AFTER campoNumeroFrascos,
  ADD COLUMN campoEdadAplicacion TINYINT(1) NOT NULL DEFAULT 0 AFTER campoNumeroFrascos;
*/

-- Opción C: Añadir Area Galpón y Cantidad x Galpón (ejecutar si la tabla ya tiene los 9 campos anteriores):
ALTER TABLE san_dim_tipo_programa
  ADD COLUMN campoAreaGalpon TINYINT(1) NOT NULL DEFAULT 0 AFTER campoEdadAplicacion,
  ADD COLUMN campoCantidadPorGalpon TINYINT(1) NOT NULL DEFAULT 0 AFTER campoAreaGalpon;

-- Opción D (opcional): Cambiar default de 1 a 0 en columnas existentes (nuevos tipos quedarán con todo desactivado):
/*
ALTER TABLE san_dim_tipo_programa
  MODIFY COLUMN campoZona TINYINT(1) NOT NULL DEFAULT 0,
  MODIFY COLUMN campoDespliegue TINYINT(1) NOT NULL DEFAULT 0,
  MODIFY COLUMN campoDescripcion TINYINT(1) NOT NULL DEFAULT 0,
  MODIFY COLUMN campoUbicacion TINYINT(1) NOT NULL DEFAULT 0,
  MODIFY COLUMN campoProducto TINYINT(1) NOT NULL DEFAULT 0,
  MODIFY COLUMN campoUnidades TINYINT(1) NOT NULL DEFAULT 0,
  MODIFY COLUMN campoUnidadDosis TINYINT(1) NOT NULL DEFAULT 0,
  MODIFY COLUMN campoNumeroFrascos TINYINT(1) NOT NULL DEFAULT 0,
  MODIFY COLUMN campoEdadAplicacion TINYINT(1) NOT NULL DEFAULT 0;
*/
