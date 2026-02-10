-- Detalle de programa: por cada solicitud/fila (necropsias) o un registro con campos NULL (otros tipos)
-- codPrograma/nomPrograma replicados de san_fact_programa_cab; resto desde mitm, ccte, san_dim_vacuna

CREATE TABLE IF NOT EXISTS san_fact_programa_det (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codPrograma VARCHAR(20) NOT NULL,
    nomPrograma VARCHAR(200) NULL DEFAULT NULL,
    codProducto VARCHAR(50) NULL DEFAULT NULL,
    nomProducto VARCHAR(255) NULL DEFAULT NULL,
    codProveedor VARCHAR(50) NULL DEFAULT NULL,
    nomProveedor VARCHAR(255) NULL DEFAULT NULL,
    ubicacion VARCHAR(200) NULL DEFAULT NULL,
    unidades VARCHAR(50) NULL DEFAULT NULL,
    dosis VARCHAR(100) NULL DEFAULT NULL,
    unidadDosis VARCHAR(50) NULL DEFAULT NULL,
    numeroFrascos VARCHAR(50) NULL DEFAULT NULL,
    edad INT NULL DEFAULT NULL,
    descripcionVacuna VARCHAR(500) NULL DEFAULT NULL,
    areaGalpon INT NULL DEFAULT NULL,
    cantidadPorGalpon INT NULL DEFAULT NULL,
    INDEX idx_codPrograma (codPrograma)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
