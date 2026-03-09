# Estado final de tablas de planificación

Referencia: `modules/planificacion/programas/dashboard-programas-registro.php`, `guardar_programa.php`, `get_programa_cab_detalle.php`

---

## san_fact_programa_cab

**Cabecera del programa** (formulario: Categoría, Tipo, Código, Nombre, Descripción, Despliegue, Fechas, Programa especial). **La edad NO va aquí; va en el detalle.**

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT PRIMARY KEY AUTO_INCREMENT | |
| codigo | VARCHAR(20) NOT NULL | Código único (ej: NCS-0001, CP-0001) |
| nombre | VARCHAR(200) NOT NULL | |
| codTipo | INT NOT NULL | FK san_dim_tipo_programa |
| nomTipo | VARCHAR(100) | |
| zona | VARCHAR(100) | |
| despliegue | VARCHAR(200) | GRS, Piloto, etc. |
| descripcion | VARCHAR(500) | |
| categoria | VARCHAR(100) | PROGRAMA SANITARIO, SEGUIMIENTO SANITARIO |
| tipo o tipoCDP | VARCHAR(50) | Control de Plagas: ROEDORES, GORGOJOS, INSECTOS (solo CP) |
| fechaInicio | DATE | Obligatorio si existe columna |
| fechaFin | DATE | Opcional |
| esEspecial | TINYINT(1) DEFAULT 0 | 1 = programa especial |
| modoEspecial | VARCHAR(30) | PERIODICIDAD o MANUAL |
| intervaloMeses | INT | Solo modo PERIODICIDAD |
| diaDelMes | INT | Solo modo PERIODICIDAD |
| fechas_manuales | TEXT (JSON) | Solo modo MANUAL |
| toleranciaEspecial | INT | Tolerancia global (programa especial sin detalle) |
| fechaHoraRegistro | TIMESTAMP | |
| usuarioRegistro | VARCHAR(50) | |

**Origen columnas opcionales:** `planificacion_alter_programa_cab_despliegue`, `planificacion_alter_programa_cab_tipo_cp` (tipo), `agregar_campo_tipo_cp` (tipo), `planificacion_alter_programa_cab_especial`, `planificacion_programa_cab_fechas_manuales`. El código usa `tipoCDP` si existe, sino `tipo`.

---

## san_fact_programa_det

**Detalle del programa** (tabla: Producto, Proveedor, Ubicación, Edad, Tolerancia, etc.). Una fila por edad o configuración.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT PRIMARY KEY AUTO_INCREMENT | |
| codPrograma | VARCHAR(20) NOT NULL | FK a san_fact_programa_cab.codigo |
| nomPrograma | VARCHAR(200) | |
| codProducto | VARCHAR(50) | |
| nomProducto | VARCHAR(255) | |
| codProveedor | VARCHAR(50) | |
| nomProveedor | VARCHAR(255) | |
| ubicacion | VARCHAR(200) | |
| unidades | VARCHAR(50) | |
| dosis | VARCHAR(100) | |
| unidadDosis | VARCHAR(50) | |
| numeroFrascos | VARCHAR(50) | |
| edad | INT | Edad de aplicación (ej: 8, 17, 26, 34) |
| posDetalle | INT DEFAULT 1 | Orden de la fila |
| descripcionVacuna | VARCHAR(500) | "Contra: enf1, enf2" |
| areaGalpon | INT | MC (Mortalidad Controlada) |
| cantidadPorGalpon | INT | MC |
| tolerancia | INT | Días tolerancia (plan vs ejecutado); se copia a cronograma |
| fechas | TEXT (JSON) | Fechas manuales por fila (programa especial modo MANUAL) |
| intervaloMeses | INT | Periodicidad por fila |
| diaDelMes | INT | Periodicidad por fila |

**Prioridad:** Si la primera fila det tiene `fechas`, `intervaloMeses` o `diaDelMes`, el formulario los usa para la cabecera (override).

---

## san_fact_cronograma

Una fila por fecha asignada (granja + campaña + galpón + programa + fecha). Base: *conexion_grs_joya*.

**Nota:** No hay CREATE TABLE explícito en el repo; la tabla evolucionó de `san_plan_cronograma` o se creó aparte con columnas distintas.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT PRIMARY KEY AUTO_INCREMENT | (asumido) |
| granja | VARCHAR(10) | 3 caracteres recomendado (LPAD) |
| campania | VARCHAR(10) | |
| galpon | VARCHAR(20) | |
| codPrograma | VARCHAR(20) | |
| nomPrograma | VARCHAR(200) | |
| fechaCarga | DATE | Fecha carga del plan |
| fechaEjecucion | DATE | Fecha planificada/ejecutada (reemplaza "fecha") |
| usuarioRegistro | VARCHAR(50) | |
| zona | VARCHAR(100) | *planificacion_alter_cronograma_nomGranja_edad* |
| subzona | VARCHAR(100) | *planificacion_alter_cronograma_nomGranja_edad* |
| nomGranja | VARCHAR(120) | *planificacion_alter_cronograma_nomGranja_edad* |
| edad | INT | *planificacion_alter_cronograma_nomGranja_edad* |
| tolerancia | INT | *planificacion_alter_cronograma_tolerancia* - días tolerancia (copiada de programa_det) |
| observaciones | VARCHAR(500) | *planificacion_alter_cronograma_observaciones* - motivo anomalía asignación eventual |
| numCronograma | INT | Agrupa registros por asignación; 0 = desarrollado (migrado, asignación eventual) |

**Uso de numCronograma:**
- `numCronograma > 0` → planificado (desde programa)
- `numCronograma = 0` o `NULL` → desarrollado (t_regnecropsia migrado, asignación eventual)

**Origen:** migración, `guardar_cronograma.php`, `migrar_necropsias_a_cronograma.sql`, scripts alter

---

## Relaciones

- **san_fact_programa_cab** ↔ **san_dim_tipo_programa**: codTipo → codigo
- **san_fact_programa_det** ↔ **san_fact_programa_cab**: codPrograma → codigo
- **san_fact_cronograma**: codPrograma referencia a san_fact_programa_cab.codigo
- **Tolerancia:** se copia de `san_fact_programa_det.tolerancia` (por edad) al crear registros en `san_fact_cronograma`
