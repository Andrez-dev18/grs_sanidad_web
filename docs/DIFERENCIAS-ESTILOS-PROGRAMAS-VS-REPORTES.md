# Análisis: diferencias de estilos entre Programas listado y Reportes

## 1. Carga de CSS y scripts

| Aspecto | Reportes | Programas |
|---------|----------|-----------|
| **Ruta base** | `../../` | `../../../` |
| **Orden CSS** | output → fontawesome → **jspdf** → DataTables → vista-tabla-iconos → responsive → config | output → fontawesome → DataTables → **select2** → vista-tabla-iconos → responsive → config |
| **Scripts extra** | jspdf, sweetalert-helpers | select2 (CSS+JS), sin jspdf ni sweetalert-helpers |

**Conclusión:** El orden de hojas es equivalente salvo que Reportes mete jspdf entre fontawesome y DataTables, y Programas mete select2 después de DataTables. Eso no debería cambiar el aspecto de la tabla si `dashboard-config.css` sigue siendo el último.

---

## 2. Bloque `<style>` local

### 2.1 Común en ambos
- `body` (background #f8f9fa, font-family)
- `.btn-primary` y `.btn-primary:hover`
- `.form-control` y `.form-control:focus`
- `.card`
- `.table-wrapper` (overflow, scrollbar)
- `.view-lista-wrap` / `.view-tarjetas-wrap` (display block/none)
- Media queries por **ID del wrapper** (`#tablaReportesWrapper` / `#tablaProgramasWrapper`) para vista lista/iconos en móvil y desktop
- Regla “en modo iconos quitar borde” del bloque de controles superiores: `#tablaReportesWrapper[data-vista="iconos"] #cardsControlsTopReportes` vs `#tablaProgramasWrapper[data-vista="iconos"] #cardsControlsTopProg`
- `.dataTables_wrapper { overflow-x: visible !important; }`

### 2.2 Solo en Reportes
- **`.btn-secondary`** y **`.btn-export`**, **`.btn-outline`** (Programas no los define).
- **`#reportesDtControls`** con estilo completo:
  - `.dataTables_length`, `.dataTables_filter`: margin/padding 0
  - Labels: font-size 0.875rem, inline-flex, gap 0.5rem
  - **Select:** padding 0.5rem 1rem, min-height 2.25rem, border-radius, hover (border #9ca3af), focus (border #2563eb, box-shadow)
  - **Input búsqueda:** min-width 180px, min-height 2.25rem, hover/focus
- **`#reportesIconosControls`**: labels, `.cards-length-select` (padding, border, focus), `.dataTables_filter` input (min-height, min-width, focus).
- **`.dataTables_wrapper .dataTables_paginate .paginate_button`**:
  - **`.current`:** `background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%)`
  - **`:hover`:** `background: #eff6ff; color: #1d4ed8`
- **`table.dataTable thead .sorting:before/after`** (y asc/desc): `color: white !important`
- **`#reportesToolbarRow`**: margin-bottom 1rem, overflow visible
- **`.view-toggle-btn`**: además de lo común, border-radius repetido por lado, `box-sizing: border-box`, `overflow: visible`
- **`.cards-grid`**: `grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; padding: 0.5rem 0` (varias columnas en desktop)
- **`.card-item`** (y .card-codigo, .card-row, .card-acciones): definición local que puede pisar al global

### 2.3 Solo en Programas
- **`.dataTables_wrapper .dataTables_length select`** (y filter input, paginate): estilos más cortos; **`.paginate_button.current`**: `background: #1e40af` (sólido, sin degradado).
- **Sin** regla para `.paginate_button:hover` en el bloque local (queda la del `dashboard-config.css`).
- **`#progDtControls`**: solo margin/padding 0 y label (font-size 0.875rem, color #374151). No define select/input con min-height ni focus/hover.
- **Sin** estilos locales para `#progIconosControls` (depende de `dashboard-config.css` por ID).
- **Sin** regla local para `table.dataTable thead .sorting:before/after`.
- **Sin** regla local para `.cards-grid` ni `.card-item` → en vista iconos se usa el global de `dashboard-vista-tabla-iconos.css` (p. ej. `grid-template-columns: 1fr` = una sola columna).
- Estilos de **modales y overlay** (modal-overlay, modal-box, overlay producto/proveedor, modal-editar, modal-header/body/footer, #modalEditarBtnGuardar, select2-container, etc.) que Reportes no tiene.

---

## 3. HTML del contenedor de la tabla

| Elemento | Reportes | Programas |
|----------|----------|-----------|
| Wrapper | `class="bg-white rounded-xl shadow-md p-5"` `id="tablaReportesWrapper"` `data-vista=""` | `class="bg-white rounded-xl shadow-md p-5"` `id="tablaProgramasWrapper"` `data-vista=""` |
| Clase wrapper | Sin `tabla-listado-wrapper` | Sin `tabla-listado-wrapper` (en tu versión actual) |
| Estructura interior | card-body p-0 mt-5 → toolbar → view-tarjetas-wrap → view-lista-wrap → table-wrapper → table | Igual |
| Controles tarjetas | `id="cardsControlsTopReportes"` + clases flex/gap/border | `id="cardsControlsTopProg"` + mismas clases; sin clase `cards-controls-top` |
| Tabla | `class="data-table display w-full text-sm border-collapse config-table"` | Igual |
| thead th | `class="px-6 py-4 text-left text-sm font-semibold"` en cada `<th>` | Igual |

**Conclusión:** Estructura y clases del wrapper/tabla/thead son equivalentes. La diferencia de aspecto no viene del HTML sino de los bloques `<style>` y de qué define cada uno para controles, paginación y vista iconos.

---

## 4. Diferencias que más afectan al aspecto

1. **Vista iconos (tarjetas)**  
   - **Reportes:** `.cards-grid` en local con `repeat(auto-fill, minmax(280px, 1fr))` → varias columnas cuando hay espacio.  
   - **Programas:** No redefine `.cards-grid` → usa el de `dashboard-vista-tabla-iconos.css` (`.cards-grid.cards-grid-iconos` con `grid-template-columns: 1fr`) → **una sola columna** siempre.  
   → En Reportes las tarjetas se ven en grid de varias columnas; en Programas en una columna.

2. **Paginación**  
   - **Reportes:** Botón actual con degradado `linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%)` y hover `#eff6ff` / `#1d4ed8`.  
   - **Programas:** Botón actual con color sólido `#1e40af`; hover lo define solo el CSS global.  
   → Mismo tono de azul pero en Reportes el botón actual tiene degradado y hover explícito en local.

3. **Controles de la tabla (Mostrar / Buscar)**  
   - **Reportes:** En local, select e input con `min-height: 2.25rem`, `min-width: 180px` en input, y estados hover/focus (borde y sombra).  
   - **Programas:** En local solo ajustes de margin/padding y label; el detalle de select/input viene de `dashboard-config.css` (#progDtControls).  
   → Puede haber pequeñas diferencias de altura/ancho y focus si el global no es idéntico al bloque local de Reportes.

4. **Iconos de ordenación en la cabecera**  
   - **Reportes:** `table.dataTable thead .sorting:before/after` (y asc/desc) con `color: white` en local.  
   - **Programas:** No está en local; depende de `dashboard-config.css` (donde sí está la regla global).  
   → Mismo resultado si el global se aplica; si no, en Programas las flechas podrían no ser blancas.

5. **Clase global en el wrapper**  
   - Ninguno usa `tabla-listado-wrapper` en el HTML que revisé. Si Programas la usara y quitara el bloque local de vista lista/iconos, dependería solo del CSS global y se unificaría el comportamiento con lo definido para esa clase.

---

## 5. Resumen de acciones para igualar Programas a Reportes (solo estilos)

Si quieres que Programas se vea y se comporte como Reportes en la zona de la tabla/listado:

1. **Vista iconos:** En Programas, o bien añadir en su `<style>` la misma regla de Reportes para `.cards-grid` (`repeat(auto-fill, minmax(280px, 1fr))`), o asegurar que el contenedor de tarjetas tenga una clase que en `dashboard-vista-tabla-iconos.css` o `dashboard-config.css` use ese grid (y no solo `1fr`).
2. **Paginación:** En el bloque local de Programas, usar para `.paginate_button.current` el mismo `linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%)` y para `.paginate_button:hover` lo mismo que en Reportes (`#eff6ff` / `#1d4ed8`).
3. **Controles DT:** Opcional: copiar el bloque de `#reportesDtControls` (select + input con min-height, min-width, hover, focus) adaptando el ID a `#progDtControls`, para que altura y estados sean idénticos.
4. **Ordenación:** Si en algún navegador las flechas de la cabecera no se ven blancas en Programas, añadir en su `<style>` la misma regla de Reportes para `table.dataTable thead .sorting:before/after` (color white).
5. **Solución global:** Usar en ambos la clase `tabla-listado-wrapper` en el wrapper, eliminar de ambos los bloques locales que dupliquen vista lista/iconos, table-wrapper y controles, y llevar todas esas reglas a `dashboard-config.css` y `dashboard-vista-tabla-iconos.css` (por clase), para que no haya diferencias por módulo.

Con esto quedan identificadas todas las diferencias de estilos entre Programas listado y Reportes y qué tocar en cada archivo para igualarlos.
