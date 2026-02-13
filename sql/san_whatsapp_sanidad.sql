-- Tabla para guardar el número de teléfono del usuario (WhatsApp notificaciones).
-- Ejecutar en la base donde está san_correo_sanidad (conexion_grs_joya).
CREATE TABLE IF NOT EXISTS san_telefono_sanidad (
    codigo VARCHAR(50) NOT NULL PRIMARY KEY COMMENT 'codigo usuario',
    telefono VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'número con código país, ej: 51987654321'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
