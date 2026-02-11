-- =============================================================================
-- RESUMEN DE TODAS LAS TABLAS TRABAJADAS (Planificación + Productos/Vacuna)
-- Ejecutar en el orden que corresponda según tu base (planificación vs joya)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. PLANIFICACIÓN (base planificación / misma que programa)
-- -----------------------------------------------------------------------------

-- Tipos de programa (campo* = 1 si el tipo registra ese campo; camelCase). Nuevo tipo: todos DEFAULT 0.
CREATE TABLE IF NOT EXISTS san_dim_tipo_programa (
    codigo INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    sigla VARCHAR(20) DEFAULT NULL,
   
    campoUbicacion TINYINT(1) NOT NULL DEFAULT 0,
    campoProducto TINYINT(1) NOT NULL DEFAULT 0,
    campoUnidades TINYINT(1) NOT NULL DEFAULT 0,
    campoUnidadDosis TINYINT(1) NOT NULL DEFAULT 0,
    campoNumeroFrascos TINYINT(1) NOT NULL DEFAULT 0,
    campoEdadAplicacion TINYINT(1) NOT NULL DEFAULT 0,
    campoAreaGalpon TINYINT(1) NOT NULL DEFAULT 0,
    campoCantidadPorGalpon TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO san_dim_tipo_programa (codigo, nombre, sigla) VALUES (1, 'NECROPSIAS', 'NEC');

-- Cabecera programas (solo campos del formulario antes de "Número de filas"; edad va en detalle)
CREATE TABLE IF NOT EXISTS san_fact_programa_cab (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    codTipo INT NOT NULL,
    nomTipo VARCHAR(100) DEFAULT NULL,
    zona VARCHAR(100) DEFAULT NULL,
    despliegue VARCHAR(200) DEFAULT NULL,
    descripcion VARCHAR(500) DEFAULT NULL,
    fechaHoraRegistro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuarioRegistro VARCHAR(50) DEFAULT NULL,
    INDEX idx_codigo (codigo),
    INDEX idx_codTipo (codTipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle programa (por solicitud: producto, proveedor, ubicación, unidades, dosis, edad, etc.)
-- descripcionVacuna: "Contra: enf1, enf2" (solo para vacunas en Pl/GR). areaGalpon/cantidadPorGalpon: MC.
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
    descripcionVacuna VARCHAR(500) NULL DEFAULT NULL COMMENT 'Contra: enfermedad1, enfermedad2',
    areaGalpon INT NULL DEFAULT NULL,
    cantidadPorGalpon INT NULL DEFAULT NULL,
    INDEX idx_codPrograma (codPrograma)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cronograma
CREATE TABLE IF NOT EXISTS san_plan_cronograma (
    id INT PRIMARY KEY AUTO_INCREMENT,
    granja VARCHAR(10) NOT NULL,
    campania VARCHAR(10) NOT NULL,
    galpon VARCHAR(20) NOT NULL,
    codPrograma VARCHAR(20) NOT NULL,
    nomPrograma VARCHAR(200) DEFAULT NULL,
    fecha DATE NOT NULL,
    usuarioRegistro VARCHAR(50) DEFAULT NULL,
    fechaHoraRegistro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cronograma_busqueda (granja, campania, galpon, codPrograma),
    INDEX idx_cronograma_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- 2. PRODUCTOS / VACUNA / ENFERMEDAD (base conexion_grs_joya, donde está mitm/ccte)
-- Motor: MyISAM, latin1, latin1_swedish_ci
-- -----------------------------------------------------------------------------

-- Laboratorio vacuna
CREATE TABLE IF NOT EXISTS san_dim_laboratorio_vacuna (
    codigo INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    PRIMARY KEY (codigo)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Vacuna (vinculada a producto: codProducto, descripcion; dosis se guarda en mitm. Laboratorio opcional.)
CREATE TABLE IF NOT EXISTS san_dim_vacuna (
    codigo INT NOT NULL,
    codProducto VARCHAR(50) NULL DEFAULT NULL,
    descripcion VARCHAR(500) NULL DEFAULT NULL,
    codLaboratorio INT NULL DEFAULT NULL,
    PRIMARY KEY (codigo)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Enfermedades
CREATE TABLE IF NOT EXISTS tenfermedades (
    cod_enf INT NOT NULL AUTO_INCREMENT,
    nom_enf VARCHAR(255) NOT NULL,
    PRIMARY KEY (cod_enf)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Relación vacuna-enfermedad
CREATE TABLE IF NOT EXISTS san_rel_vacuna_enfermedad (
    codVacuna INT NOT NULL,
    codEnfermedad INT NOT NULL,
    PRIMARY KEY (codVacuna, codEnfermedad)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- -----------------------------------------------------------------------------
-- 3. ALTER EN MITM (ejecutar en la base donde está mitm)
-- -----------------------------------------------------------------------------

-- Productos disponibles para programas (1 = sí, 0 = no). Tipo vacuna: existe en san_rel_vacuna_enfermedad (codProducto).
-- ALTER TABLE mitm ADD COLUMN producto_programa SMALLINT NOT NULL DEFAULT 0;
ALTER TABLE tenfermedades
  MODIFY COLUMN cod_enf INT NOT NULL AUTO_INCREMENT