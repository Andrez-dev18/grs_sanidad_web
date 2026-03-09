# Archivos necesarios para producción - Módulo Registro Laboratorio

Para que `dashboard-rpta-laboratorio.php` funcione en producción, debes subir **además de** `modules/registro_laboratorio/` los siguientes archivos y carpetas:

## 1. JavaScript (CRÍTICO – sin esto no cargan S/P, CV%(S/P), % Pos, % Sos, % Neg)

| Ruta en el proyecto | Descripción |
|---------------------|-------------|
| `assets/js/registro_laboratorio/rptaLaboratorio.js` | Construye el formulario de Resultados Cuantitativos (S/P, CV%, % Pos, % Sos, % Neg, etc.) |

## 2. CSS

| Ruta en el proyecto | Descripción |
|---------------------|-------------|
| `css/style-rpt-lab.css` | Estilos del panel de laboratorio |
| `css/dashboard-config.css` | Estilos generales del dashboard |

## 3. Otros assets

| Ruta en el proyecto | Descripción |
|---------------------|-------------|
| `assets/fontawesome/css/all.min.css` | Iconos (o carpeta completa `assets/fontawesome/`) |
| `assets/js/sweetalert-helpers.js` | Mensajes de confirmación |

## 4. Conexión y dependencias PHP

| Ruta en el proyecto | Descripción |
|---------------------|-------------|
| `conexion_grs/conexion.php` | Conexión a BD (o carpeta `conexion_grs/`) |

---

## Resumen: qué subir

```
modules/registro_laboratorio/     (ya lo subiste)
assets/js/registro_laboratorio/  (rptaLaboratorio.js – obligatorio)
assets/fontawesome/               (iconos)
assets/js/sweetalert-helpers.js   (mensajes)
css/style-rpt-lab.css
css/dashboard-config.css
conexion_grs/                     (si no existe en producción)
```

## Verificación rápida

1. En producción, abre la consola del navegador (F12 → pestaña Network/Red).
2. Recarga la página del dashboard de laboratorio.
3. Revisa si hay solicitudes con error 404. Los más habituales:
   - `rptaLaboratorio.js` (404)
   - `style-rpt-lab.css` (404)
   - `dashboard-config.css` (404)
