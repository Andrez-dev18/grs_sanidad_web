# Análisis: dashboard-programas-registro.php

## 1. Correspondencia front–backend

### Endpoints usados y contrato

| Endpoint | Uso en front | Respuesta backend | Estado |
|----------|--------------|-------------------|--------|
| `get_tipos_programa.php` | `res.success`, `res.data` | `{ success: true, data: [...] }` | OK |
| `get_programa_cab_detalle.php?codigo=` | `res.success`, `res.cab`, `res.detalles`, `res.sigla` | `{ success, cab, detalles, sigla }` | OK |
| `generar_codigo_nec.php?sigla=` | `res.success`, `res.codigo` | Revisar que devuelva `{ success, codigo }` | OK (asumido) |
| `guardar_programa.php` (POST JSON) | Envía `codigo, nombre, codTipo, nomTipo, zona, subzona, despliegue, descripcion, detalles` | `{ success, message }` | OK |
| `actualizar_programa.php` (POST JSON) | Mismo payload | `{ success, message }` | OK |
| `get_productos_programa.php` | Búsqueda productos | JSON array/data | OK |
| `get_ccte_lista.php` | Búsqueda proveedores | JSON | OK |
| `get_datos_producto_programa.php` | Datos de un producto | JSON | OK |
| `../../configuracion/productos/get_enfermedades.php` | Listado enfermedades (modal) | JSON | Ruta correcta (desde programas: ../../ → planificacion, luego configuracion/productos) |

### Payload guardar/actualizar

- **Front** (`getDetallesFromForm()`): cada detalle tiene `ubicacion`, `codProducto`, `nomProducto`, `codProveedor`, `nomProveedor`, `unidades`, `dosis`, `unidadDosis`, `numeroFrascos`, `descripcionVacuna`, `areaGalpon`, `cantidadPorGalpon`, `edad`, `posDetalle`.
- **Backend** (guardar_programa.php, actualizar_programa.php): lee `$d['ubicacion']`, `codProducto`, `nomProducto`, etc. Coinciden con los nombres del front. La columna `posDetalle` existe opcionalmente en BD; si no existe, el backend no la usa. **Conclusión:** correspondencia correcta.

### Mensajes de éxito/error

- Backend siempre devuelve `success` (boolean) y `message` (string).
- Front usa `if (res.success)` y `res.message` en Swal. **Conclusión:** alineado.

---

## 2. Consistencia de estilos

### Orden de CSS actual (programas registro)

1. output.css  
2. fontawesome  
3. select2.min.css  
4. dashboard-responsive.css  
5. (inline `<style>`)

**Comparación con cronograma-registro:**

- Cronograma: output → fontawesome → select2 → **dashboard-vista-tabla-iconos** → **dashboard-config** → dashboard-responsive → inline.
- Programas registro **no** incluye `dashboard-config.css` ni `dashboard-vista-tabla-iconos.css`.

**Razón de cambio:** En el proyecto se fijó que los estilos unificados (formularios, botones, cards) viven en `dashboard-config.css` y que este debe cargarse en todos los dashboards para mantener la misma base. Programas registro define su propio `.btn-primary`, `.form-control`, etc. en `<style>`, por lo que no depende del config para funcionar, pero para **consistencia** con listado y con cronograma registro conviene:

1. Cargar **dashboard-config.css** en el mismo orden que en otros módulos (p. ej. después de select2, antes o después de responsive según convención).
2. Convención usada en NUEVA-TABLA-LISTADO: **dashboard-config al final** (para que gane sobre el resto). Aquí no hay DataTables ni tabla listado, así que el orden puede ser: output → fontawesome → select2 → dashboard-responsive → **dashboard-config**.

**Acción propuesta:** Añadir una sola línea:  
`<link rel="stylesheet" href="../../../css/dashboard-config.css">`  
después de `dashboard-responsive.css` (para que config quede al final y no rompa estilos propios del registro que dependen del inline).

---

## 3. Cambios realizados

- Se añadió `dashboard-config.css` al final del bloque de CSS (después de dashboard-responsive) para unificar con el resto de módulos y que cualquier estilo global (filtros, botones, cards) se aplique igual que en listado y cronograma.

---

## 4. No modificado (y por qué)

- **dashboard-vista-tabla-iconos.css:** No se añade en programas registro porque la página no tiene tabla listado ni vista lista/iconos; es solo formulario. Cronograma registro sí lo usa porque incluye una tabla/toolbar.
- **Estructura del formulario y nombres de campos:** Coinciden con backend; no se cambia.
- **Lógica de submit y de getDetallesFromForm:** Correcta y alineada con guardar/actualizar; no se toca.
- **Modales (producto, proveedor, enfermedades):** Específicos del registro; se dejan como están.
