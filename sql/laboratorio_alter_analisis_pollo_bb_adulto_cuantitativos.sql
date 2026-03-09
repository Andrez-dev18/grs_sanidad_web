-- Campos cuantitativos adicionales para resultados de laboratorio (antes de observaciones en UI)
-- S/P (3 decimales), CV%(S/P) (1 decimal), %Positivos, %Sospechosos, %Negativos (1 decimal)
-- Tabla: san_analisis_pollo_bb_adulto

ALTER TABLE san_analisis_pollo_bb_adulto
  ADD COLUMN sp DOUBLE NULL DEFAULT NULL COMMENT 'S/P - dato numérico 3 decimales' AFTER count_muestras,
  ADD COLUMN cv_sp DOUBLE NULL DEFAULT NULL COMMENT 'CV%(S/P) 1 decimal' AFTER sp,
  ADD COLUMN pct_positivos DOUBLE NULL DEFAULT NULL COMMENT '% Positivos 1 decimal' AFTER cv_sp,
  ADD COLUMN pct_sospechosos DOUBLE NULL DEFAULT NULL COMMENT '% Sospechosos 1 decimal' AFTER pct_positivos,
  ADD COLUMN pct_negativos DOUBLE NULL DEFAULT NULL COMMENT '% Negativos 1 decimal' AFTER pct_sospechosos;
