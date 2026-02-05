-- Tabla de tipos de programa (maestro)
CREATE TABLE IF NOT EXISTS san_tipo_programa (
    codigo INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ejemplo: insertar tipo NECROPSIAS si no existe
INSERT IGNORE INTO san_tipo_programa (codigo, nombre) VALUES (1, 'NECROPSIAS');

-- Tabla de programas de planificación
CREATE TABLE IF NOT EXISTS san_plan_programa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    codTipo INT NOT NULL,
    nomTipo VARCHAR(100) DEFAULT NULL,
    edad INT NOT NULL,
    fechaHoraRegistro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuarioRegistro VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (codTipo) REFERENCES san_tipo_programa(codigo) ON DELETE RESTRICT,
    INDEX idx_codigo (codigo),
    INDEX idx_codTipo (codTipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Si la tabla ya existía con creado_en, ejecutar (opcional):
-- ALTER TABLE san_plan_programa CHANGE creado_en fechaHoraRegistro TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
-- ALTER TABLE san_plan_programa ADD COLUMN usuarioRegistro VARCHAR(50) DEFAULT NULL AFTER fechaHoraRegistro;
-- UPDATE san_tipo_programa SET nombre = 'NECROPSIAS' WHERE codigo = 1;

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
