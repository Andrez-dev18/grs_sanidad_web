-- Ejecutar en la BD (MySQL/MariaDB) donde vive el sistema.
-- Crea tablas para:
-- 1) Planificación de necropsias (cabecera por evento planificado)
-- 2) Enlaces: (planificación ↔ necropsia registrada) y (planificación ↔ muestra registrada)
--
-- Nota: No modifica tablas existentes (san_planificacion, san_fact_*, t_regnecropsia).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS san_plan_necropsia (
  id CHAR(36) NOT NULL,
  fecha_programacion DATETIME NOT NULL,
  tgranja VARCHAR(10) NOT NULL,
  tcampania CHAR(3) NOT NULL,
  tgalpon CHAR(2) NOT NULL,
  tedad CHAR(2) NOT NULL,
  tfectra DATE NOT NULL,
  responsable VARCHAR(100) NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'PLANIFICADO',
  usuario_registra VARCHAR(50) NOT NULL,
  fechaHoraRegistro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observacion TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_plan_necropsia_busqueda (tgranja, tgalpon, tedad, tfectra),
  KEY idx_plan_necropsia_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS san_plan_link_necropsia (
  id CHAR(36) NOT NULL,
  plan_id CHAR(36) NOT NULL,
  tgranja VARCHAR(10) NOT NULL,
  tgalpon CHAR(2) NOT NULL,
  tfectra DATE NOT NULL,
  tnumreg INT NOT NULL,
  usuario_enlace VARCHAR(50) NOT NULL,
  fechaHoraEnlace DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_necropsia_registro (tgranja, tgalpon, tfectra, tnumreg),
  KEY idx_necropsia_plan_id (plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS san_plan_link_muestra (
  id CHAR(36) NOT NULL,
  codEnvio VARCHAR(20) NOT NULL,
  posSolicitud INT NOT NULL,
  codRef VARCHAR(20) NOT NULL,
  fecToma DATE NOT NULL,
  codMuestra INT NOT NULL,
  usuario_enlace VARCHAR(50) NOT NULL,
  fechaHoraEnlace DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_envio_solicitud (codEnvio, posSolicitud),
  KEY idx_muestra_plan_lookup (codRef, fecToma, codMuestra)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

