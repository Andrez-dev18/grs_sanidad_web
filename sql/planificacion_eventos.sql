-- Ejecutar en la BD (MySQL/MariaDB) donde vive el sistema.
-- Tablas de dimensión (san_dim_*) y planificación (san_plan_*).
--
-- Planificación:
-- - san_plan_cab: cabecera del mes (año, mes)
-- - san_plan_det: detalle (una fila por item: granja, campaña, galpón, fecToma, cronograma, tipo muestra, nMacho, nHembra, etc.)
--
-- Relación con ejecuciones:
-- - Muestras: san_plan_link_muestra (codEnvio, posSolicitud, detId)
-- - Necropsias: san_plan_link_necropsia (tgranja, tgalpon, tfectra, tnumreg, detId)

SET NAMES latin1;

-- ========== TABLAS DE DIMENSIÓN ==========

CREATE TABLE IF NOT EXISTS san_dim_cronograma (
  codigo INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  PRIMARY KEY (codigo)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT IGNORE INTO san_dim_cronograma (codigo, nombre) VALUES
(1, 'Control: Calidad Pollo BB'),
(2, 'Control: Calidad Pollo Crianza'),
(3, 'Control: Insumo - Agua'),
(4, 'Control: Insumo - Alimento'),
(5, 'Control: Insumo - Vacunas'),
(6, 'Control: Limpieza & Desinfección'),
(7, 'Control: Necropsias'),
(8, 'Control: Entorno');

CREATE TABLE IF NOT EXISTS san_dim_destino (
  codigo INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(40) NOT NULL,
  PRIMARY KEY (codigo)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT IGNORE INTO san_dim_destino (codigo, nombre) VALUES
(1, 'Chincha'),
(2, 'Lima'),
(3, 'Arequipa');

-- ========== TABLAS DE PLANIFICACIÓN ==========

-- Cabecera por mes (puede haber varias cab por mismo anio/mes)
-- Si la tabla ya existe con uk_plan_cab_anio_mes, ejecutar: ALTER TABLE san_plan_cab DROP INDEX uk_plan_cab_anio_mes;
CREATE TABLE IF NOT EXISTS san_plan_cab (
  id CHAR(36) NOT NULL,
  anio SMALLINT NOT NULL,
  mes TINYINT NOT NULL,
  fecProgramacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuarioRegistrador VARCHAR(50) NOT NULL,
  fechaHoraRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_plan_cab_anio (anio),
  KEY idx_plan_cab_mes (mes)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Detalle: una fila por item
CREATE TABLE IF NOT EXISTS san_plan_det (
  id CHAR(36) NOT NULL,
  cabId CHAR(36) NULL,
  codCronograma INT NOT NULL,
  nomCronograma VARCHAR(80) NOT NULL DEFAULT '',
  fecProgramacion DATETIME NOT NULL,
  granja CHAR(3) NOT NULL,
  nomGranja VARCHAR(120) NOT NULL DEFAULT '',
  campania CHAR(3) NOT NULL,
  galpon CHAR(2) NOT NULL,
  edad CHAR(2) NOT NULL,
  codRef VARCHAR(20) NOT NULL,
  lugarToma VARCHAR(40) NOT NULL DEFAULT '',
  fecToma DATE NOT NULL,
  responsable VARCHAR(120) NOT NULL DEFAULT '',
  codDestino INT NULL,
  nomDestino VARCHAR(40) NOT NULL DEFAULT '',
  codMuestra INT NULL,
  nomMuestra VARCHAR(120) NOT NULL DEFAULT '',
  nMacho INT NOT NULL DEFAULT 0,
  nHembra INT NOT NULL DEFAULT 0,
  estado VARCHAR(20) NOT NULL DEFAULT 'PLANIFICADO',
  observacion TEXT NULL,
  usuarioRegistrador VARCHAR(50) NOT NULL,
  fechaHoraRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuarioTransferencia VARCHAR(50) NOT NULL,
  fechaHoraTransferencia TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_plan_det_cab (cabId),
  KEY idx_plan_det_lookup (codRef, fecToma),
  KEY idx_plan_det_estado (estado),
  KEY idx_plan_det_fecha (fecToma),
  KEY idx_plan_det_cronograma (codCronograma),
  KEY idx_plan_det_destino (codDestino),
  KEY idx_plan_det_muestra (codMuestra)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS san_plan_link_necropsia (
  id CHAR(36) NOT NULL,
  detId CHAR(36) NOT NULL,
  tgranja VARCHAR(10) NOT NULL,
  tgalpon CHAR(2) NOT NULL,
  tfectra DATE NOT NULL,
  tnumreg INT NOT NULL,
  usuarioRegistrador VARCHAR(50) NOT NULL,
  fechaHoraRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuarioTransferencia VARCHAR(50) NOT NULL,
  fechaHoraTransferencia TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_necropsia_detId (detId),
  KEY idx_necropsia_registro (tgranja, tgalpon, tfectra, tnumreg)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS san_plan_link_muestra (
  id CHAR(36) NOT NULL,
  detId CHAR(36) NOT NULL,
  codEnvio VARCHAR(20) NOT NULL,
  posSolicitud INT NOT NULL,
  codRef VARCHAR(20) NOT NULL,
  fecToma DATE NOT NULL,
  codMuestra INT NOT NULL,
  usuarioRegistrador VARCHAR(50) NOT NULL,
  fechaHoraRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuarioTransferencia VARCHAR(50) NOT NULL,
  fechaHoraTransferencia TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_muestra_plan_lookup (codRef, fecToma, codMuestra),
  KEY idx_muestra_detId (detId),
  KEY idx_muestra_envio (codEnvio, posSolicitud)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
