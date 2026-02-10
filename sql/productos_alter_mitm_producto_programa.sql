-- Añadir campo producto_programa en mitm (0 = no disponible para programas, 1 = disponible)
-- Ejecutar en la base donde está mitm (conexion_grs_joya)

ALTER TABLE mitm ADD COLUMN producto_programa SMALLINT NOT NULL DEFAULT 0;
