-- Tabla de tipos de programa (maestro). campo* = 1 si se registra ese campo (camelCase). Nuevo tipo: todos DEFAULT 0.
CREATE TABLE IF NOT EXISTS san_dim_tipo_programa (
    codigo INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    sigla VARCHAR(20) DEFAULT NULL,
    campoZona TINYINT(1) NOT NULL DEFAULT 0,
    campoDespliegue TINYINT(1) NOT NULL DEFAULT 0,
    campoDescripcion TINYINT(1) NOT NULL DEFAULT 0,
    campoUbicacion TINYINT(1) NOT NULL DEFAULT 0,
    campoProducto TINYINT(1) NOT NULL DEFAULT 0,
    campoUnidades TINYINT(1) NOT NULL DEFAULT 0,
    campoUnidadDosis TINYINT(1) NOT NULL DEFAULT 0,
    campoNumeroFrascos TINYINT(1) NOT NULL DEFAULT 0,
    campoEdadAplicacion TINYINT(1) NOT NULL DEFAULT 0,
    campoAreaGalpon TINYINT(1) NOT NULL DEFAULT 0,
    campoCantidadPorGalpon TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ejemplo: insertar tipo NECROPSIAS si no existe
INSERT IGNORE INTO san_dim_tipo_programa (codigo, nombre, sigla) VALUES (1, 'NECROPSIAS', 'NEC');

-- Si la tabla ya existía sin sigla, ejecutar:
-- ALTER TABLE san_dim_tipo_programa ADD COLUMN sigla VARCHAR(20) DEFAULT NULL AFTER nombre;

-- Tabla de programas de planificación (cabecera): solo campos del formulario antes de "Número de filas" (edad va en detalle)
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
    FOREIGN KEY (codTipo) REFERENCES san_dim_tipo_programa(codigo) ON DELETE RESTRICT,
    INDEX idx_codigo (codigo),
    INDEX idx_codTipo (codTipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Si ya existía san_plan_programa, migrar y añadir columnas:
-- RENAME TABLE san_plan_programa TO san_fact_programa_cab;
-- ALTER TABLE san_fact_programa_cab ADD COLUMN zona VARCHAR(100) DEFAULT NULL;
-- ALTER TABLE san_fact_programa_cab ADD COLUMN descripcion VARCHAR(500) DEFAULT NULL AFTER zona;
-- Para quitar edad de cab (ya va en detalle): ALTER TABLE san_fact_programa_cab DROP COLUMN edad;

-- Tabla cronograma: una fila por fecha asignada (granja + campaña + galpón + programa)
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
