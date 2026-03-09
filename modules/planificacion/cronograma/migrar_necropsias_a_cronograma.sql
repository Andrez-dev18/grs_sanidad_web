-- Migración: insertar registros de t_regnecropsia (año actual) en san_fact_cronograma
-- como desarrollados (numCronograma=0) ligados al programa NC-0021.
--
-- Requisitos: El programa NC-0021 debe existir en san_fact_programa_cab.
-- Agrupación: tdate, granja (LEFT(tgranja,3)), campania, tgalpon, tedad
-- fechaCarga = tfectra - (tedad - 1) días (fecha cuando la parvada tenía edad 1)
-- fechaEjecucion = tdate (fecha de registro en el sistema)
--
-- NOTA: Si su BD no tiene las columnas zona, subzona, nomGranja, edad, numCronograma,
-- ajuste el INSERT según el esquema real. Este SQL asume que existen.

-- Ejecutar solo si NC-0021 existe:
-- SELECT codigo, nombre FROM san_fact_programa_cab WHERE codigo = 'NC-0021';

INSERT INTO san_fact_cronograma (
    granja,
    campania,
    galpon,
    codPrograma,
    nomPrograma,
    fechaCarga,
    fechaEjecucion,
    usuarioRegistro,
    zona,
    subzona,
    nomGranja,
    edad,
    numCronograma
)
SELECT
    r.granja,
    r.campania,
    r.galpon,
    'NC-0021' AS codPrograma,
    COALESCE(cab.nombre, 'NC-0021') AS nomPrograma,
    COALESCE(
        DATE_SUB(DATE(r.tfectra), INTERVAL (GREATEST(COALESCE(CAST(r.edad AS UNSIGNED), 1), 1) - 1) DAY),
        DATE(r.tdate)
    ) AS fechaCarga,
    DATE(r.tdate) AS fechaEjecucion,
    'MIGRACION' AS usuarioRegistro,
    zs.zona,
    zs.subzona,
    COALESCE(rg.nombre, ccos.nombre, '') AS nomGranja,
    r.edad,
    0 AS numCronograma
FROM (
    SELECT
        DATE(r2.tdate) AS tdate,
        MIN(r2.tfectra) AS tfectra,
        LPAD(LEFT(TRIM(r2.tgranja), 3), 3, '0') AS granja,
        LPAD(RIGHT(COALESCE(NULLIF(TRIM(r2.tcampania), ''), RIGHT(TRIM(r2.tgranja), 3)), 3), 3, '0') AS campania,
        TRIM(r2.tgalpon) AS galpon,
        COALESCE(NULLIF(TRIM(CAST(r2.tedad AS CHAR)), ''), '0') AS edad
    FROM t_regnecropsia r2
    WHERE YEAR(r2.tdate) = YEAR(CURDATE())
      AND TRIM(r2.tgranja) <> ''
      AND TRIM(r2.tgalpon) <> ''
      AND TRIM(r2.tgalpon) != '0'
      AND r2.tdate IS NOT NULL
      AND r2.tdate != '1000-01-01'
    GROUP BY DATE(r2.tdate),
             LPAD(LEFT(TRIM(r2.tgranja), 3), 3, '0'),
             LPAD(RIGHT(COALESCE(NULLIF(TRIM(r2.tcampania), ''), RIGHT(TRIM(r2.tgranja), 3)), 3), 3, '0'),
             TRIM(r2.tgalpon),
             COALESCE(NULLIF(TRIM(CAST(r2.tedad AS CHAR)), ''), '0')
) r
LEFT JOIN san_fact_programa_cab cab ON cab.codigo = 'NC-0021'
LEFT JOIN (
    SELECT
        LEFT(TRIM(det.id_granja), 3) AS codigo,
        MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'ZONA' THEN TRIM(det.dato) END) AS zona,
        MAX(CASE WHEN UPPER(TRIM(car.nombre)) = 'SUBZONA' THEN TRIM(det.dato) END) AS subzona
    FROM pi_dim_detalles det
    INNER JOIN pi_dim_caracteristicas car ON car.id = det.id_caracteristica
    WHERE TRIM(det.id_granja) <> '' AND UPPER(TRIM(car.nombre)) IN ('ZONA', 'SUBZONA')
    GROUP BY LEFT(TRIM(det.id_granja), 3)
) zs ON zs.codigo = r.granja
LEFT JOIN (
    SELECT LEFT(TRIM(tcencos), 3) AS codigo, MAX(TRIM(tnomcen)) AS nombre
    FROM regcencosgalpones
    WHERE TRIM(tcencos) <> ''
    GROUP BY LEFT(TRIM(tcencos), 3)
) rg ON rg.codigo = r.granja
LEFT JOIN (
    SELECT LEFT(TRIM(codigo), 3) AS codigo, MAX(TRIM(nombre)) AS nombre
    FROM ccos
    WHERE TRIM(codigo) <> ''
    GROUP BY LEFT(TRIM(codigo), 3)
) ccos ON ccos.codigo = r.granja
WHERE NOT EXISTS (
    SELECT 1 FROM san_fact_cronograma c
    WHERE c.granja = r.granja
      AND c.campania = r.campania
      AND c.galpon = r.galpon
      AND c.codPrograma = 'NC-0021'
      AND DATE(c.fechaEjecucion) = r.tdate
      AND (c.numCronograma = 0 OR c.numCronograma IS NULL)
);
