<?php
session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) { window.top.location.href = "../../../login.php"; } else { window.location.href = "../../../login.php"; }
    </script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programas - Registro</title>
    <!-- Orden unificado con listado y cronograma: config al final para estilos base -->
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="../../../assets/js/fetch-auth-redirect.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none; padding: 0.5rem 1rem; font-size: 0.8125rem; font-weight: 600;
            color: white; border-radius: 0.25rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;
        }
        .btn-primary:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); transform: translateY(-1px); }
        .form-control { width: 100%; padding: 0.375rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; font-size: 0.8125rem; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
        .bloque-detalle { display: block; }
        .select2-container .select2-selection--single { height: 32px; border-radius: 0.25rem; border: 1px solid #d1d5db; padding: 2px 8px; font-size: 0.8125rem; }
        .select2-container { width: 100% !important; }
        /* Opciones del dropdown Select2 más pequeñas y formato código - descri */
        .select2-container--default .select2-results__option { font-size: 0.75rem; padding: 4px 8px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { font-size: 0.8125rem; line-height: 28px; }
        .fila-fechas-programa { display: grid; grid-template-columns: 1fr auto 1fr; align-items: end; gap: 0.75rem 1rem; }
        .fila-fechas-programa .col-fecha { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
        .fila-fechas-programa .col-fecha .form-control { min-height: 32px; }
        .fila-fechas-programa .col-fecha label { font-size: 0.75rem; font-weight: 500; color: #4b5563; }
        .fila-fechas-programa .chk-fecha-fin-wrap { display: flex; align-items: center; gap: 0.5rem; padding-bottom: 2px; flex-shrink: 0; }
        .fila-fechas-programa .chk-fecha-fin-wrap input[type="checkbox"] { width: 1rem; height: 1rem; margin: 0; flex-shrink: 0; cursor: pointer; }
        .fila-fechas-programa .chk-fecha-fin-wrap span { font-size: 0.8125rem; color: #374151; cursor: pointer; white-space: nowrap; }
        .fila-fechas-programa .form-control:disabled { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
        @media (max-width: 640px) { .fila-fechas-programa { grid-template-columns: 1fr; } }
        /* Tabla detalle: scroll horizontal, sin ellipsis en cabecera */
        #solicitudesContainer { overflow-x: auto; overflow-y: visible; -webkit-overflow-scrolling: touch; }
        .tabla-detalle-compact { font-size: 0.75rem; width: 100%; min-width: 100%; table-layout: fixed; }
        .tabla-detalle-compact th, .tabla-detalle-compact td { padding: 4px 6px; vertical-align: middle; }
        .tabla-detalle-compact th { white-space: pre-line; overflow-wrap: normal; word-break: normal; line-height: 1.2; min-height: 2.4em; }
        .tabla-detalle-compact td { white-space: nowrap; }
        .tabla-detalle-compact .form-control.compact { padding: 0.25rem 0.5rem; font-size: 0.75rem; min-height: 26px; border-radius: 0.2rem; }
        .tabla-detalle-compact textarea.compact { min-height: 36px; border-radius: 0.2rem; }
        .btn-add-row { padding: 0.3rem 0.6rem; font-size: 0.8rem; border-radius: 0.25rem; }
        .btn-quitar-fila { padding: 0.15rem 0.35rem; font-size: 0.7rem; line-height: 1; border-radius: 0.2rem; }
        /* Anchos de columnas: # reducida, Producto/Proveedor/Ubicación más anchos, Unidad/Dosis/Frascos/Edad reducidos, Quitar mínima */
        .tabla-detalle-compact th.col-num, .tabla-detalle-compact td.col-num { width: 28px; max-width: 28px; min-width: 28px; }
        .tabla-detalle-compact th.col-quitar, .tabla-detalle-compact td.col-quitar { width: 36px; max-width: 36px; min-width: 36px; }
        .tabla-detalle-compact .col-ubicacion { width: 130px; min-width: 100px; max-width: 130px; }
        .tabla-detalle-compact .col-ubicacion textarea.compact.multiline { resize: none; min-height: 36px; overflow-y: hidden; line-height: 1.3; white-space: pre-wrap; word-wrap: break-word; width: 100%; min-width: 0; }
        .tabla-detalle-compact .col-ubicacion input.form-control { width: 100%; min-width: 0; max-width: 100%; box-sizing: border-box; }
        .tabla-detalle-compact .col-producto { min-width: 260px; }
        .tabla-detalle-compact .col-proveedor { min-width: 200px; }
        .tabla-detalle-compact .col-producto .form-control,
        .tabla-detalle-compact .col-proveedor .form-control,
        .tabla-detalle-compact .col-producto textarea.compact,
        .tabla-detalle-compact .col-proveedor textarea.compact { min-width: 0; width: 100%; }
        /* Lupa mismo tamaño en producto y proveedor */
        .tabla-detalle-compact .btn-lupa-detalle { width: 28px; min-width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0; }
        /* Textarea producto/proveedor: multilínea y altura dinámica */
        .tabla-detalle-compact .col-producto .wrap-producto-proveedor,
        .tabla-detalle-compact .col-proveedor .wrap-producto-proveedor { display: flex; align-items: flex-start; gap: 4px; width: 100%; }
        .tabla-detalle-compact .col-producto textarea.compact.multiline,
        .tabla-detalle-compact .col-proveedor textarea.compact.multiline { resize: none; min-height: 36px; overflow-y: hidden; line-height: 1.3; white-space: pre-wrap; word-wrap: break-word; }
        /* Descripción vacuna: editable + botón enfermedades */
        .tabla-detalle-compact .td-descripcion-vacuna textarea.compact { resize: none; min-height: 36px; overflow-y: hidden; line-height: 1.3; white-space: pre-wrap; word-wrap: break-word; }
        .wrap-descripcion-vacuna { display: flex; align-items: flex-start; gap: 4px; width: 100%; min-width: 0; }
        .wrap-descripcion-vacuna textarea { flex: 1; min-width: 0; }
        .btn-enfermedades-descripcion { flex-shrink: 0; width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #d1d5db; border-radius: 0.25rem; color: #4b5563; background: #fff; cursor: pointer; }
        .btn-enfermedades-descripcion:hover { background: #eff6ff; color: #2563eb; }
        #modalEnfermedadesDescripcion .modal-inner { max-width: 820px; width: 95vw; max-height: 90vh; min-height: 400px; display: flex; flex-direction: column; }
        #modalEnfermedadesDescripcion .modal-body { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
        #wrapCheckboxEnfermedadesDescripcion { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem 0.75rem; flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden; padding: 0.5rem 0; }
        #wrapCheckboxEnfermedadesDescripcion label { display: flex; align-items: center; gap: 0.35rem; cursor: pointer; font-size: 0.8125rem; }
        #wrapCheckboxEnfermedadesDescripcion input[type="checkbox"] { flex-shrink: 0; background: #fff; }
        #wrapCheckboxEnfermedadesDescripcion::-webkit-scrollbar { width: 8px; }
        #wrapCheckboxEnfermedadesDescripcion::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        #wrapCheckboxEnfermedadesDescripcion::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
        .tabla-detalle-compact .col-unidad, .tabla-detalle-compact .col-frascos { min-width: 42px; max-width: 58px; width: 50px; }
        .tabla-detalle-compact .col-uniddosis { min-width: 72px; max-width: 96px; width: 84px; }
        .tabla-detalle-compact .col-dosis { min-width: 55px; max-width: 75px; width: 65px; }
        .tabla-detalle-compact .col-edad { min-width: 58px; max-width: 72px; width: 65px; }
        .tabla-detalle-compact .col-tolerancia { min-width: 72px; max-width: 90px; width: 82px; }
        .tabla-detalle-compact .col-tolerancia .form-control { min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box; }
        .tabla-detalle-compact .col-unidad .form-control, .tabla-detalle-compact .col-dosis .form-control,
        .tabla-detalle-compact .col-uniddosis .form-control, .tabla-detalle-compact .col-frascos .form-control,
        .tabla-detalle-compact .col-edad .form-control { min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box; }
        .tabla-detalle-compact .col-area-galpon,
        .tabla-detalle-compact .col-cantidad-galpon { min-width: 78px; max-width: 102px; width: 90px; }
        .tabla-detalle-compact .col-area-galpon .form-control,
        .tabla-detalle-compact .col-cantidad-galpon .form-control { min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box; font-size: 0.8125rem; }
        #modalProveedorResultados { overflow-y: auto; overflow-x: hidden; max-height: 320px; min-height: 120px; }
        /* Modal buscar producto: más ancho y alto; scroll en resultados */
        #modalBuscarProducto .modal-producto-inner { width: 100%; max-width: 56rem; max-height: 90vh; min-height: 420px; display: flex; flex-direction: column; overflow: hidden; }
        #modalProductoResultados { overflow-y: auto; overflow-x: hidden; flex: 1; min-height: 200px; max-height: 420px; }
        #modalProductoResultados::-webkit-scrollbar { width: 8px; }
        #modalProductoResultados::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        #modalProductoResultados::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
        /* Cabecera compacta */
        .cabecera-compact .form-control { padding: 0.3rem 0.5rem; font-size: 0.8125rem; border-radius: 0.25rem; }
        .cabecera-compact label { font-size: 0.7rem; margin-bottom: 0.2rem; }
        /* Popover flotante fuera de la tabla (se inyecta en body) */
        .th-edad-wrap, .th-proveedor-wrap, .th-descripcion-wrap, .th-tolerancia-wrap { display: inline-flex; align-items: center; }
        #popoverInfoEdadFlotante, #popoverInfoProveedorFlotante, #popoverInfoDescripcionFlotante, #popoverInfoToleranciaFlotante { position: fixed; z-index: 9999; min-width: 200px; max-width: 260px; padding: 8px 10px; font-size: 0.75rem; line-height: 1.35; color: #374151; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); white-space: normal; display: none; }
        #popoverInfoEdadFlotante.visible, #popoverInfoProveedorFlotante.visible, #popoverInfoDescripcionFlotante.visible, #popoverInfoToleranciaFlotante.visible { display: block; }
        /* Dentro del modal (iframe): sin fondo gris, inputs directos sin contenedor con esquinas redondas */
        body.en-modal-editar { background: #fff; }
        body.en-modal-editar #formProgramaContainer { padding: 0.75rem 1rem; max-width: none; }
        body.en-modal-editar #formProgramaContainer > div { border: none; border-radius: 0; box-shadow: none; margin-bottom: 0; overflow: visible; background: transparent; }
        body.en-modal-editar #formPrograma { padding: 0; }
        /* Barra de carga modal recálculo (progreso real vía JS) */
        .recalc-loading-bar-track { height: 6px; }
        .recalc-loading-bar {
            height: 6px;
            min-height: 6px;
            background: linear-gradient(90deg, #0ea5e9, #38bdf8);
            border-radius: 9999px;
            transition: width 0.15s ease-out;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full max-w-full py-4 px-3 sm:px-4 lg:px-6 box-border" id="formProgramaContainer">
        <div class="mb-6 bg-white border rounded-lg shadow-sm overflow-hidden">
            <form id="formPrograma" class="p-4">
                <div class="cabecera-compact space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label for="categoria" class="block text-xs font-medium text-gray-600 mb-0.5">Categoría *</label>
                            <select id="categoria" name="categoria" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <option value="PROGRAMA SANITARIO">Programa Sanitario</option>
                                <option value="SEGUIMIENTO SANITARIO">Seguimiento Sanitario</option>
                            </select>
                            <p id="msgCategoria" class="text-xs text-gray-500 mt-1 hidden"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Tipo de programa *</label>
                            <select id="tipo" name="codTipo" class="form-control" required>
                                <option value="">Seleccione...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Código del programa</label>
                            <input type="text" id="codigo" name="codigo" class="form-control bg-gray-100" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Nombre del programa *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Necropsia campaña 2026" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div id="wrapTipoCP" class="hidden">
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Tipo (Control de Plagas)</label>
                            <select id="tipoCP" name="tipoCP" class="form-control">
                                <option value="">Seleccione...</option>
                                <option value="ROEDORES">ROEDORES</option>
                                <option value="GORGOJOS">GORGOJOS</option>
                                <option value="INSECTOS">INSECTOS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Descripción del programa *</label>
                            <input type="text" id="descripcion" name="descripcion" class="form-control" placeholder="Descripción" maxlength="500" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Despliegue del programa *</label>
                            <input type="text" id="despliegue" name="despliegue" class="form-control" placeholder="Despliegue" maxlength="200" list="desplieguesList" autocomplete="off" required>
                            <datalist id="desplieguesList"><option value="GRS"><option value="Piloto"></datalist>
                        </div>
                    </div>
                    <div class="fila-fechas-programa mt-2">
                        <div class="col-fecha">
                            <label for="fechaInicio">Fecha inicio *</label>
                            <input type="date" id="fechaInicio" name="fechaInicio" class="form-control" required>
                        </div>
                        <label class="chk-fecha-fin-wrap" for="chkIncluirFechaFin">
                            <input type="checkbox" id="chkIncluirFechaFin" name="chkIncluirFechaFin">
                            <span>Incluir fecha de fin</span>
                        </label>
                        <div class="col-fecha">
                            <label for="fechaFin">Fecha fin</label>
                            <input type="date" id="fechaFin" name="fechaFin" class="form-control" disabled>
                        </div>
                    </div>
                    <p id="msgSoloFechaFin" class="hidden mt-2 text-amber-700 text-sm bg-amber-50 border border-amber-200 rounded px-3 py-2">
                        <i class="fas fa-info-circle mr-1"></i> Este programa solo tiene asignaciones pasadas. Solo puede modificar la fecha de fin.
                    </p>
                    <div id="wrapProgramaEspecial" class="mt-3 pt-3 border-t border-gray-200">
                        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3 space-y-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="chkEspecial" name="esEspecial" value="1" class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm font-medium text-gray-700">Programa especial</span>
                            </label>
                            <div id="wrapOpcionesEspecial" class="hidden space-y-3 pl-6 border-l-2 border-gray-200">
                                <div id="wrapToleranciaEspecial" class="hidden">
                                    <label for="toleranciaEspecial" class="block text-xs font-medium text-gray-600 mb-0.5">Tolerancia (días)</label>
                                    <input type="number" id="toleranciaEspecial" name="toleranciaEspecial" min="0" value="1" class="form-control w-20" placeholder="1" title="Días de margen para contrastar planificado vs. ejecutado.">
                                </div>
                                <div id="wrapModoEspecial" class="hidden">
                                    <span class="block text-xs font-medium text-gray-600 mb-1">¿Cómo se definen las fechas?</span>
                                    <div class="flex flex-wrap gap-3">
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" name="modoEspecial" value="PERIODICIDAD" class="text-emerald-600 focus:ring-emerald-500">
                                            <span class="text-sm text-gray-700">Con periodicidad</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" name="modoEspecial" value="MANUAL" class="text-emerald-600 focus:ring-emerald-500">
                                            <span class="text-sm text-gray-700">Fechas manuales</span>
                                        </label>
                                    </div>
                                </div>
                                <div id="wrapPeriodicidad" class="hidden flex flex-wrap items-center gap-3">
                                    <div class="flex items-center gap-1.5">
                                        <label for="intervaloMeses" class="text-xs text-gray-600">Cada</label>
                                        <input type="number" id="intervaloMeses" name="intervaloMeses" min="1" max="12" value="1" class="form-control w-14 text-center">
                                        <span class="text-xs text-gray-600">mes(es)</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <label for="diaDelMes" class="text-xs text-gray-600">día del mes</label>
                                        <input type="number" id="diaDelMes" name="diaDelMes" min="1" max="31" value="15" class="form-control w-14 text-center">
                                    </div>
                                </div>
                                <div id="wrapFechasManuales" class="hidden">
                                    <span class="block text-xs font-medium text-gray-600 mb-1">Fechas de ejecución</span>
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <input type="date" id="nuevaFechaManual" class="form-control w-36">
                                        <button type="button" id="btnAgregarFechaManual" class="px-2 py-1 text-xs font-medium rounded border border-emerald-600 text-emerald-700 bg-white hover:bg-emerald-50"><i class="fas fa-plus mr-0.5"></i> Agregar</button>
                                    </div>
                                    <ul id="listaFechasManuales" class="list-none pl-0 space-y-0.5 max-h-32 overflow-y-auto border border-gray-200 rounded p-2 bg-white text-sm"></ul>
                                    <p id="msgListaFechasManualesVacia" class="hidden text-gray-500 text-xs mt-0.5">Aún no hay fechas. Agregue al menos una.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    <div id="bloqueDetalle" class="bloque-detalle mt-4 pt-4 border-t border-gray-200">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                            <span class="text-sm font-medium text-gray-700">Detalle del programa</span>
                            <button type="button" id="btnAgregarFila" class="btn-primary btn-add-row">
                                <i class="fas fa-plus"></i> Agregar fila
                            </button>
                        </div>
                        <div id="solicitudesContainer" class="hidden mt-2 w-full overflow-x-auto overflow-y-visible">
                            <table class="w-full min-w-full border border-gray-200 rounded-lg tabla-detalle-compact" id="tablaSolicitudes">
                                <thead class="bg-gray-100" id="solicitudesThead"></thead>
                                <tbody id="solicitudesBody"></tbody>
                            </table>
                            <datalist id="ubicacionList">
                                <option value="Planta de Incubacion"><option value="Granja"><option value="Galpón"><option value="Piso"><option value="Techo">
                            </datalist>
                        </div>
                        <p id="solicitudesMsgTipo" class="hidden text-amber-600 text-xs mt-1">Seleccione primero el tipo de programa.</p>
                    </div>
                </div>
                <div id="formProgramaFooter" class="mt-4 flex gap-2 justify-end border-t border-gray-200 pt-3">
                    <button type="button" id="btnLimpiarForm" class="px-3 py-1.5 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 text-xs font-medium">Limpiar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal buscar producto -->
    <div id="modalBuscarProducto" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div id="modalBuscarProductoInner" class="modal-producto-inner bg-white rounded-xl shadow-xl w-full flex flex-col overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <h3 class="text-base font-semibold text-gray-800">Buscar producto</h3>
                <button type="button" id="btnCerrarModalProducto" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>
            <div class="p-4 flex flex-col flex-1 min-h-0">
           
                <div class="flex-shrink-0 mb-2">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Filtros</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3 flex-shrink-0">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Línea</label>
                        <select id="modalProductoLinea" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 form-control">
                            <option value="">Seleccionar</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Almacén</label>
                        <select id="modalProductoAlmacen" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 form-control">
                            <option value="">Seleccionar</option>
                        </select>
                    </div>
                </div>
                <div class="flex-shrink-0 mb-2">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Búsqueda</span>
                </div>
                <input type="text" id="modalProductoBuscar" class="form-control mb-3 flex-shrink-0" placeholder="Nombre o código del producto (opcional)" autocomplete="off">
                <div class="flex flex-wrap gap-2 mb-3 flex-shrink-0">
                    <button type="button" id="btnModalProductoLimpiar" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 bg-gray-100 text-sm font-medium hover:bg-gray-200">Limpiar todo</button>
                </div>
                <div id="modalProductoResultados" class="border border-gray-200 rounded text-sm"></div>
            </div>
        </div>
    </div>
    <!-- Modal buscar proveedor (ccte) -->
    <div id="modalBuscarProveedor" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800">Buscar proveedor</h3>
                <button type="button" id="btnCerrarModalProveedor" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>
            <div class="p-4">
                <input type="text" id="modalProveedorBuscar" class="form-control mb-3" placeholder="Escriba nombre o código del proveedor..." autocomplete="off">
                <div id="modalProveedorResultados" class="border border-gray-200 rounded text-sm"></div>
            </div>
        </div>
    </div>
    <!-- Modal seleccionar enfermedades (para descripción) -->
    <div id="modalEnfermedadesDescripcion" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div id="modalEnfermedadesDescripcionInner" class="modal-inner bg-white rounded-lg shadow-xl w-full flex flex-col max-h-[90vh] min-h-[400px]">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <h3 class="text-sm font-semibold text-gray-800">Seleccionar enfermedades</h3>
                <button type="button" id="btnCerrarModalEnfermedadesDescripcion" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>
            <div class="modal-body p-4 flex-1 min-h-0">
               
                <div id="loadingEnfermedadesDescripcion" class="hidden text-sm text-gray-500 py-2">Cargando...</div>
                <div id="wrapCheckboxEnfermedadesDescripcion"></div>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 flex justify-end gap-2 flex-shrink-0">
                <button type="button" id="btnCancelarEnfermedadesDescripcion" class="px-3 py-1.5 border border-gray-300 rounded text-gray-700 text-sm hover:bg-gray-50">Cancelar</button>
                <button type="button" id="btnAceptarEnfermedadesDescripcion" class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">Aceptar</button>
            </div>
        </div>
    </div>
    <!-- Modal carga durante recálculo de fechas -->
    <div id="modalCargaRecalcular" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]">
        <div class="bg-sky-50 rounded-xl shadow-2xl p-8 text-center max-w-sm w-full">
            <div class="flex flex-col items-center gap-3">
                <img src="../../../assets/img/gallina.gif" alt="Cargando..." class="w-32 h-32" onerror="this.style.display='none'">
                <div class="recalc-loading-bar-track w-full max-w-xs bg-gray-200 rounded-full overflow-hidden">
                    <div id="recalcLoadingBar" class="recalc-loading-bar" style="width:0%"></div>
                </div>
            </div>
            <p class="text-lg font-semibold text-gray-800 mt-4">Recalculando fechas...</p>
            <p class="text-sm text-gray-600 mt-2">Por favor espere, estamos procesando las asignaciones</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        (function() {
            var params = new URLSearchParams(window.location.search);
            if (params.get('editar') === '1' && params.get('codigo')) {
                window._modoEditar = true;
                window._codigoEditar = params.get('codigo').trim();
            } else {
                window._modoEditar = false;
                window._codigoEditar = '';
            }
        })();
        window._contextZona = '';
        window._contextSubzona = '';
        /** Agrupa detalles iguales salvo edad; devuelve filas con edad como "2,3,4" */
        function agruparDetallesPorEdad(detalles) {
            if (!detalles || detalles.length === 0) return [];
            var map = {};
            detalles.forEach(function(d) {
                var key = [
                    (d.ubicacion || ''), (d.codProducto || ''), (d.nomProducto || ''),
                    (d.codProveedor || ''), (d.nomProveedor || ''), (d.unidades || ''),
                    (d.dosis || ''), (d.unidadDosis || ''), (d.numeroFrascos || ''),
                    (d.descripcionVacuna || ''), (d.areaGalpon !== null && d.areaGalpon !== undefined ? String(d.areaGalpon) : ''),
                    (d.cantidadPorGalpon !== null && d.cantidadPorGalpon !== undefined ? String(d.cantidadPorGalpon) : ''),
                    (d.tolerancia !== null && d.tolerancia !== undefined ? String(d.tolerancia) : '1')
                ].join('\t');
                if (!map[key]) map[key] = { row: d, edades: [] };
                var e = d.edad;
                if (e !== null && e !== undefined && e !== '') map[key].edades.push(String(e).trim());
            });
            var out = [];
            Object.keys(map).forEach(function(k) {
                var g = map[k];
                var r = {};
                for (var p in g.row) if (g.row.hasOwnProperty(p)) r[p] = g.row[p];
                r.edad = (g.edades.length > 0 ? g.edades.join(',') : (g.row.edad !== null && g.row.edad !== undefined ? String(g.row.edad) : ''));
                out.push(r);
            });
            return out;
        }
        function cargarProgramaParaEditar() {
            var codigo = window._codigoEditar;
            if (!codigo) return Promise.resolve();
            return fetch('get_programa_cab_detalle.php?codigo=' + encodeURIComponent(codigo))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success || !res.cab) return;
                    var cab = res.cab;
                    var detalles = res.detalles || [];
                    var sigla = (res.sigla || 'PL').toUpperCase();
                    if (sigla === 'NEC') sigla = 'NC';
                    var tipoEl = document.getElementById('tipo');
                    tipoEl.value = String(cab.codTipo || '');
                    tipoEl.disabled = true;
                    var catStr = (cab.categoria || '').trim();
                    var selCat = document.getElementById('categoria');
                    if (selCat) {
                        if (catStr) {
                            var cats = catStr.split(',').map(function(s) { return s.trim().toUpperCase(); });
                            var tieneSeg = cats.indexOf('PROGRAMA SEGUIMIENTO') !== -1 || cats.indexOf('SEGUIMIENTO SANITARIO') !== -1 || cats.indexOf('SEGUIMIENTO') !== -1;
                            var tieneSan = cats.indexOf('PROGRAMA SANITARIO') !== -1 || cats.indexOf('SANIDAD') !== -1;
                            selCat.value = tieneSeg ? 'SEGUIMIENTO SANITARIO' : (tieneSan ? 'PROGRAMA SANITARIO' : '');
                        } else {
                            selCat.value = 'PROGRAMA SANITARIO';
                        }
                    }
                    if (typeof aplicarVisibilidadCategoria === 'function') aplicarVisibilidadCategoria();
                    if (tipoEl.value) {
                        document.getElementById('codigo').value = (cab.codigo || '').trim();
                        document.getElementById('nombre').value = (cab.nombre || '').trim();
                        document.getElementById('descripcion').value = (cab.descripcion || '').trim();
                        document.getElementById('despliegue').value = (cab.despliegue || '').trim();
                        // Cargar fecha inicio y fecha fin si existen en la cabecera (al editar)
                        var elFechaInicio = document.getElementById('fechaInicio');
                        var elFechaFin = document.getElementById('fechaFin');
                        var chkFechaFin = document.getElementById('chkIncluirFechaFin');
                        if (elFechaInicio) {
                            var vInicio = (cab.fechaInicio != null && cab.fechaInicio !== '') ? String(cab.fechaInicio).trim().substring(0, 10) : '';
                            elFechaInicio.value = vInicio;
                            window._originalFechaInicio = vInicio;
                            var hoy = new Date();
                            hoy.setHours(0, 0, 0, 0);
                            var fIni = vInicio ? new Date(vInicio) : null;
                            if (fIni && fIni < hoy) elFechaInicio.readOnly = true;
                        }
                        if (chkFechaFin && elFechaFin) {
                            var vFin = (cab.fechaFin != null && cab.fechaFin !== '') ? String(cab.fechaFin).trim().substring(0, 10) : '';
                            window._originalFechaFin = vFin || '';
                            if (vFin) {
                                chkFechaFin.checked = true;
                                elFechaFin.disabled = false;
                                elFechaFin.value = vFin;
                            } else {
                                chkFechaFin.checked = false;
                                elFechaFin.disabled = true;
                                elFechaFin.value = '';
                            }
                        }
                        if (!window._contextZona) window._contextZona = (cab.zona || '').trim();
                        if (!window._contextSubzona) window._contextSubzona = (cab.subzona || '').trim();
                        var wrapTipoCP = document.getElementById('wrapTipoCP');
                        var tipoCPEl = document.getElementById('tipoCP');
                        if ((cab.codigo || '').startsWith('CP') && wrapTipoCP && tipoCPEl) {
                            wrapTipoCP.classList.remove('hidden');
                            tipoCPEl.value = (cab.tipoCDP || cab.tipo || '').trim();
                        }
                        var chkEspecial = document.getElementById('chkEspecial');
                        if (chkEspecial) chkEspecial.checked = !!(cab.esEspecial === 1 || cab.esEspecial === '1' || cab.esEspecial === true);
                        var inpTolEsp = document.getElementById('toleranciaEspecial');
                        if (inpTolEsp && detalles.length > 0 && detalles[0].tolerancia !== undefined && detalles[0].tolerancia !== null && detalles[0].tolerancia !== '') {
                            inpTolEsp.value = Math.max(1, parseInt(detalles[0].tolerancia, 10) || 1);
                        }
                        var modo = (cab.modoEspecial || '').toString().trim().toUpperCase();
                        document.querySelectorAll('input[name="modoEspecial"]').forEach(function(r) {
                            r.checked = (r.value === modo);
                        });
                        var intM = document.getElementById('intervaloMeses');
                        var diaM = document.getElementById('diaDelMes');
                        if (intM && cab.intervaloMeses != null && cab.intervaloMeses !== '') intM.value = Math.max(1, Math.min(12, parseInt(cab.intervaloMeses, 10) || 1));
                        if (diaM && cab.diaDelMes != null && cab.diaDelMes !== '') diaM.value = Math.max(1, Math.min(31, parseInt(cab.diaDelMes, 10) || 15));
                        if (cab.fechasManuales != null && Array.isArray(cab.fechasManuales)) {
                            fechasManualesData = cab.fechasManuales.slice();
                            renderListaFechasManuales();
                        } else if (cab.fechasManuales && typeof cab.fechasManuales === 'string') {
                            try {
                                var parsed = JSON.parse(cab.fechasManuales);
                                fechasManualesData = Array.isArray(parsed) ? parsed.slice() : [];
                            } catch (err) { fechasManualesData = []; }
                            renderListaFechasManuales();
                        }
                        if (typeof aplicarVisibilidadEspecial === 'function') aplicarVisibilidadEspecial();
                        var soloSegEdit = (typeof esSoloSeguimiento === 'function' && esSoloSeguimiento());
                        var esEspEdit = (typeof esProgramaEspecial === 'function' && esProgramaEspecial());
                        var cargarDetalle = ((typeof tieneSanitario === 'function' && tieneSanitario()) || (typeof tieneSeguimiento === 'function' && tieneSeguimiento())) && !(soloSegEdit && esEspEdit);
                        if (cargarDetalle) {
                        document.getElementById('btnAgregarFila').classList.remove('hidden');
                        document.getElementById('solicitudesContainer').classList.remove('hidden');
                        currentCampos = getCamposActual();
                        buildThead(currentCampos);
                        var agrupados = agruparDetallesPorEdad(detalles);
                        solicitudesData = {};
                        agrupados.forEach(function(d, i) {
                            solicitudesData[i] = {
                                ubicacion: d.ubicacion || '',
                                codProducto: d.codProducto || '',
                                nomProducto: d.nomProducto || '',
                                codProveedor: d.codProveedor || '',
                                nomProveedor: d.nomProveedor || '',
                                unidades: d.unidades || '',
                                dosis: d.dosis || '',
                                unidadDosis: d.unidadDosis || '',
                                numeroFrascos: d.numeroFrascos || '',
                                edad: d.edad,
                                descripcionVacuna: d.descripcionVacuna || '',
                                areaGalpon: d.areaGalpon,
                                cantidadPorGalpon: d.cantidadPorGalpon,
                                tolerancia: d.tolerancia !== undefined && d.tolerancia !== null && d.tolerancia !== '' ? parseInt(d.tolerancia, 10) : 1
                            };
                        });
                        var msgTipo = document.getElementById('solicitudesMsgTipo');
                        if (msgTipo) msgTipo.classList.add('hidden');
                        adjustSolicitudesRows(agrupados.length);
                        agrupados.forEach(function(d, i) {
                            var u = document.getElementById('unidad_ro_' + i);
                            if (u) u.value = (d.unidades || '').trim();
                            var nf = document.getElementById('numeroFrascos_' + i);
                            if (nf) nf.value = (d.numeroFrascos || '').trim();
                            var ud = document.getElementById('unidadDosis_' + i);
                            if (ud) ud.value = (d.unidadDosis || '').trim();
                            var ag = document.getElementById('area_galpon_' + i);
                            if (ag && d.areaGalpon !== null && d.areaGalpon !== undefined) ag.value = d.areaGalpon;
                            var cg = document.getElementById('cantidad_por_galpon_' + i);
                            if (cg && d.cantidadPorGalpon !== null && d.cantidadPorGalpon !== undefined) cg.value = d.cantidadPorGalpon;
                            var tol = document.getElementById('tolerancia_' + i);
                            if (tol && (d.tolerancia !== null && d.tolerancia !== undefined && d.tolerancia !== '')) tol.value = d.tolerancia;
                        });
                        setTimeout(function() {
                            try { window._originalDetalles = JSON.stringify(getDetallesFromForm()); } catch (err) {}
                        }, 0);
                        } else {
                            window._originalDetalles = '[]';
                        }
                        if (typeof aplicarVisibilidadCategoria === 'function') aplicarVisibilidadCategoria();
                    }
                })
                .catch(function() {});
        }
        function aplicarRestriccionSoloFechaFin() {
            if (!window._modoEditar || !window._soloAsignacionesPasadas) return;
            var elNombre = document.getElementById('nombre');
            var elDesc = document.getElementById('descripcion');
            var elDespl = document.getElementById('despliegue');
            var elFechaInicio = document.getElementById('fechaInicio');
            var btnAgregar = document.getElementById('btnAgregarFila');
            var bloqueDet = document.getElementById('bloqueDetalle');
            var msgSolo = document.getElementById('msgSoloFechaFin');
            if (elNombre) { elNombre.disabled = true; elNombre.classList.add('bg-gray-100'); }
            if (elDesc) { elDesc.disabled = true; elDesc.classList.add('bg-gray-100'); }
            if (elDespl) { elDespl.disabled = true; elDespl.classList.add('bg-gray-100'); }
            if (elFechaInicio) { elFechaInicio.disabled = true; elFechaInicio.readOnly = true; elFechaInicio.classList.add('bg-gray-100'); }
            if (btnAgregar) btnAgregar.classList.add('hidden');
            if (bloqueDet) {
                bloqueDet.style.pointerEvents = 'none';
                bloqueDet.style.opacity = '0.7';
                var inputs = bloqueDet.querySelectorAll('input, textarea, select, button');
                inputs.forEach(function(inp) { inp.disabled = true; inp.readOnly = true; });
            }
            if (msgSolo) msgSolo.classList.remove('hidden');
        }
        function cargarTipos() {
            return fetch('get_tipos_programa.php').then(r => r.json()).then(res => {
                if (!res.success) return;
                const sel = document.getElementById('tipo');
                sel.innerHTML = '<option value="">Seleccione tipo...</option>';
                (res.data || []).forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.codigo;
                    opt.textContent = t.nombre;
                    opt.dataset.nombre = t.nombre || '';
                    opt.dataset.sigla = (t.sigla || '').trim().toUpperCase();
                    opt.dataset.campos = t.campos ? JSON.stringify(t.campos) : '{}';
                    sel.appendChild(opt);
                });
            }).catch(() => {});
        }
        function generarCodigoPorTipo(codTipo) {
            if (!codTipo) { document.getElementById('codigo').value = ''; toggleTipoCP(); return; }
            fetch('generar_codigo_nec.php?codTipo=' + encodeURIComponent(codTipo))
                .then(r => r.json())
                .then(res => { document.getElementById('codigo').value = (res.success && res.codigo) ? res.codigo : ''; toggleTipoCP(); })
                .catch(() => { document.getElementById('codigo').value = ''; toggleTipoCP(); });
        }
        var solicitudesData = {};
        function getSiglaActual() {
            var tipo = document.getElementById('tipo');
            if (!tipo || !tipo.value) return '';
            var opt = tipo.options[tipo.selectedIndex];
            var s = (opt && opt.dataset.sigla) ? String(opt.dataset.sigla).toUpperCase() : '';
            if (s === 'NEC') s = 'NC';
            return s;
        }
        function toggleTipoCP() {
            var wrap = document.getElementById('wrapTipoCP');
            var codigo = (document.getElementById('codigo').value || '').trim();
            var sigla = getSiglaActual();
            var esCP = codigo.startsWith('CP') || sigla === 'CP' || sigla === 'CDP';
            if (wrap) {
                if (esCP) wrap.classList.remove('hidden'); else { wrap.classList.add('hidden'); var sel = document.getElementById('tipoCP'); if (sel) sel.value = ''; }
            }
        }
        function getCamposActual() {
            var tipo = document.getElementById('tipo');
            if (!tipo || !tipo.value) return null;
            var val = tipo.value;
            var opt = null;
            for (var i = 0; i < tipo.options.length; i++) {
                if (tipo.options[i].value === val) { opt = tipo.options[i]; break; }
            }
            if (!opt || !opt.dataset.campos) return null;
            try {
                var c = JSON.parse(opt.dataset.campos);
                if (typeof c.descripcion === 'undefined') c.descripcion = 0;
                return c;
            } catch (e) { return null; }
        }
        function getCategoriaFromCheckboxes() {
            var sel = document.getElementById('categoria');
            return (sel && sel.value) ? sel.value : '';
        }
        function esSoloSeguimiento() {
            var cat = (getCategoriaFromCheckboxes() || '').toString().trim().toUpperCase();
            return (cat === 'SEGUIMIENTO SANITARIO');
        }
        function tieneSanitario() {
            var sel = document.getElementById('categoria');
            return sel && sel.value === 'PROGRAMA SANITARIO';
        }
        function tieneSeguimiento() {
            var sel = document.getElementById('categoria');
            return sel && sel.value === 'SEGUIMIENTO SANITARIO';
        }
        function esProgramaEspecial() {
            var chk = document.getElementById('chkEspecial');
            return chk && chk.checked;
        }
        function getColumnasFromCampos(campos) {
            var tieneSan = tieneSanitario();
            var tieneSeg = tieneSeguimiento();
            var esEsp = esProgramaEspecial();
            var cols;
            if (tieneSeg) {
                cols = ['num', 'edad', 'tolerancia'];
            } else if (tieneSan) {
                if (!campos) cols = ['num', 'ubicacion', 'producto', 'proveedor', 'unidad', 'dosis', 'descripcion_vacuna', 'numeroFrascos'];
                else {
                    cols = ['num'];
                    if (campos.ubicacion === 1) cols.push('ubicacion');
                    if (campos.producto === 1) cols.push('producto');
                    if (campos.proveedor === 1) cols.push('proveedor');
                    if ((campos.unidad === 1 || campos.unidades === 1) && cols.indexOf('unidad') === -1) cols.push('unidad');
                    if (campos.dosis === 1) cols.push('dosis');
                    if (Number(campos.descripcion) === 1) cols.push('descripcion_vacuna');
                    if (campos.unidad_dosis === 1) cols.push('unidadDosis');
                    if (campos.numero_frascos === 1) cols.push('numeroFrascos');
                    if (campos.area_galpon === 1) cols.push('area_galpon');
                    if (campos.cantidad_por_galpon === 1) cols.push('cantidad_por_galpon');
                    if (cols.length === 1) cols.push('ubicacion');
                }
                if (cols.indexOf('edad') === -1) cols.push('edad');
                if (cols.indexOf('tolerancia') === -1) cols.push('tolerancia');
            } else {
                return ['num'];
            }
            if (esEsp) cols = cols.filter(function(c) { return c !== 'edad' && c !== 'tolerancia'; });
            return cols;
        }
        var TOLERANCIA_TITLE = 'Días de margen para contrastar planificado vs. desarrollado. Ej.: si ingresa 2, se considerará cumplido si la ejecución está entre 2 días antes y 2 días después de la fecha planificada. Si hay varias ejecuciones en ese rango, se toma la más cercana a la fecha planificada.';
        var LABELS = { num: '#', ubicacion: 'Ubic.', producto: 'Producto', proveedor: 'Proveedor', unidad: 'Unid.', dosis: 'Dosis', descripcion_vacuna: 'Descrip.', numeroFrascos: 'Nº frascos', edad: 'Edad', unidadDosis: 'Unid. dosis', area_galpon: 'Área galpón', cantidad_por_galpon: 'Cant/\ngalpon', tolerancia: 'Tolerancia' };
        function buildThead(campos) {
            var thead = document.getElementById('solicitudesThead');
            if (!thead) return;
            var cols = getColumnasFromCampos(campos);
            var html = '<tr>';
            cols.forEach(function(k) {
                var ext = '';
                if (k === 'num') ext = ' col-num';
                else if (k === 'ubicacion') ext = ' col-ubicacion';
                else if (k === 'producto') ext = ' col-producto';
                else if (k === 'proveedor') ext = ' col-proveedor';
                else if (k === 'unidad') ext = ' col-unidad';
                else if (k === 'dosis') ext = ' col-dosis';
                else if (k === 'unidadDosis') ext = ' col-uniddosis';
                else if (k === 'numeroFrascos') ext = ' col-frascos';
                else if (k === 'edad') ext = ' col-edad';
                else if (k === 'area_galpon') ext = ' col-area-galpon';
                else if (k === 'cantidad_por_galpon') ext = ' col-cantidad-galpon';
                else if (k === 'tolerancia') ext = ' col-tolerancia';
                if (k === 'tolerancia') {
                    html += '<th class="px-1.5 py-1 text-left border-b border-gray-200 font-semibold text-gray-600 text-xs col-tolerancia"><span class="th-tolerancia-wrap">' + (LABELS[k] || k) + ' <button type="button" class="btn-info-tolerancia ml-0.5 text-blue-500 hover:text-blue-700 inline-flex align-middle p-0 border-0 bg-transparent cursor-pointer" title="Ayuda"><i class="fas fa-info-circle text-sm"></i></button></span></th>';
                } else if (k === 'descripcion_vacuna') {
                    html += '<th id="th_descripcion_vacuna" class="th-descripcion-vacuna px-1.5 py-1 text-left border-b border-gray-200 font-semibold text-gray-600 text-xs' + ext + '"><span class="th-descripcion-wrap">' + (LABELS[k] || k) + ' <button type="button" class="btn-info-descripcion ml-0.5 text-blue-500 hover:text-blue-700 inline-flex align-middle p-0 border-0 bg-transparent cursor-pointer" title="Ayuda"><i class="fas fa-info-circle text-sm"></i></button></span></th>';
                } else if (k === 'edad') {
                    html += '<th class="px-1.5 py-1 text-left border-b border-gray-200 font-semibold text-gray-600 text-xs' + ext + '"><span class="th-edad-wrap">' + (LABELS[k] || k) + ' <button type="button" class="btn-info-edad ml-0.5 text-blue-500 hover:text-blue-700 inline-flex align-middle p-0 border-0 bg-transparent cursor-pointer" title="Ayuda"><i class="fas fa-info-circle text-sm"></i></button></span></th>';
                } else if (k === 'proveedor') {
                    html += '<th class="px-1.5 py-1 text-left border-b border-gray-200 font-semibold text-gray-600 text-xs' + ext + '"><span class="th-proveedor-wrap">' + (LABELS[k] || k) + ' <button type="button" class="btn-info-proveedor ml-0.5 text-blue-500 hover:text-blue-700 inline-flex align-middle p-0 border-0 bg-transparent cursor-pointer" title="Ayuda"><i class="fas fa-info-circle text-sm"></i></button></span></th>';
                } else {
                    html += '<th class="px-1.5 py-1 text-left border-b border-gray-200 font-semibold text-gray-600 text-xs' + ext + '">' + (LABELS[k] || k) + '</th>';
                }
            });
            html += '<th class="col-quitar px-1.5 py-1 text-center border-b border-gray-200 font-semibold text-gray-600 text-xs">Quitar</th></tr>';
            thead.innerHTML = html;
        }
    
        function buildRowHtml(campos, i) {
            var cols = getColumnasFromCampos(campos);
            var parts = [];
            var cellClass = 'px-1.5 py-1';
            var inputClass = 'form-control compact';
            cols.forEach(function(k) {
                if (k === 'num') parts.push('<td class="col-num ' + cellClass + ' text-gray-600 text-xs">' + (i + 1) + '</td>');
                else if (k === 'ubicacion') parts.push('<td class="col-ubicacion ' + cellClass + '"><input type="text" id="ubicacion_' + i + '" name="ubicacion_' + i + '" class="' + inputClass + '" maxlength="200" placeholder="Ubicación" list="ubicacionList" autocomplete="off"></td>');
                else if (k === 'producto') parts.push('<td class="col-producto ' + cellClass + '"><input type="hidden" id="producto_' + i + '" name="codProducto_' + i + '" value=""><div class="wrap-producto-proveedor"><textarea id="producto_text_' + i + '" class="' + inputClass + ' compact multiline bg-gray-100" readonly rows="2"></textarea><button type="button" class="btn-lupa-detalle btn-buscar-celda border border-gray-300 text-gray-600 hover:bg-gray-100 rounded" data-row="' + i + '" title="Buscar producto"><i class="fas fa-search"></i></button></div></td>');
                else if (k === 'proveedor') parts.push('<td class="col-proveedor ' + cellClass + '"><input type="hidden" id="codProveedor_' + i + '" name="codProveedor_' + i + '" value=""><div class="wrap-producto-proveedor"><textarea id="proveedor_' + i + '" class="' + inputClass + ' compact multiline bg-gray-100" readonly rows="2"></textarea><button type="button" class="btn-lupa-detalle btn-buscar-proveedor border border-gray-300 text-gray-600 hover:bg-gray-100 rounded" data-row="' + i + '" title="Buscar proveedor"><i class="fas fa-search"></i></button></div></td>');
                else if (k === 'unidad') parts.push('<td class="col-unidad ' + cellClass + '"><input type="text" id="unidad_ro_' + i + '" name="unidad_' + i + '" class="' + inputClass + '" maxlength="50"></td>');
                else if (k === 'dosis') parts.push('<td class="col-dosis ' + cellClass + '"><input type="text" id="dosis_' + i + '" name="dosis_' + i + '" class="' + inputClass + '"></td>');
                else if (k === 'descripcion_vacuna') parts.push('<td id="td_descripcion_vacuna_' + i + '" class="' + cellClass + ' td-descripcion-vacuna" style="min-width:200px;"><div class="wrap-descripcion-vacuna"><textarea id="descripcion_vacuna_ro_' + i + '" name="descripcion_vacuna_' + i + '" class="' + inputClass + ' compact descripcion-vacuna-ta bg-gray-100" style="min-width:140px;" readonly></textarea></div></td>');
                else if (k === 'numeroFrascos') parts.push('<td class="col-frascos ' + cellClass + '"><input type="text" id="numeroFrascos_' + i + '" name="numeroFrascos_' + i + '" class="' + inputClass + '" maxlength="50"></td>');
                else if (k === 'edad') parts.push('<td class="col-edad ' + cellClass + '" title="Una edad (ej: 2) o varias separadas por coma (ej: 2,4)"><input type="text" id="edad_' + i + '" name="edad_' + i + '" class="' + inputClass + '" maxlength="50"></td>');
                else if (k === 'unidadDosis') parts.push('<td class="col-uniddosis ' + cellClass + '"><input type="text" id="unidadDosis_' + i + '" name="unidadDosis_' + i + '" class="' + inputClass + '" maxlength="50"></td>');
                else if (k === 'area_galpon') parts.push('<td class="col-area-galpon ' + cellClass + '"><input type="number" id="area_galpon_' + i + '" name="area_galpon_' + i + '" class="' + inputClass + '" min="0"></td>');
                else if (k === 'cantidad_por_galpon') parts.push('<td class="col-cantidad-galpon ' + cellClass + '"><input type="number" id="cantidad_por_galpon_' + i + '" name="cantidad_por_galpon_' + i + '" class="' + inputClass + '" min="0"></td>');
                else if (k === 'tolerancia') parts.push('<td class="col-tolerancia ' + cellClass + '" title="' + TOLERANCIA_TITLE.replace(/'/g, '&#39;') + '"><input type="number" id="tolerancia_' + i + '" name="tolerancia_' + i + '" class="' + inputClass + '" min="0" value="1" placeholder="1"></td>');
                else parts.push('<td class="' + cellClass + '"></td>');
            });
            parts.push('<td class="col-quitar ' + cellClass + ' text-center"><button type="button" class="btn-quitar-fila border border-red-200 text-red-600 hover:bg-red-50 rounded" data-row="' + i + '" title="Quitar fila"><i class="fas fa-trash-alt"></i></button></td>');
            return parts.join('');
        }
        function aplicarVisibilidadCategoria() {
            var bloqueDet = document.getElementById('bloqueDetalle');
            var btnAgregar = document.getElementById('btnAgregarFila');
            var container = document.getElementById('solicitudesContainer');
            var msgTipo = document.getElementById('solicitudesMsgTipo');
            var msgCat = document.getElementById('msgCategoria');
            var soloSeg = esSoloSeguimiento();
            var esEsp = esProgramaEspecial();
            // Ocultar solo cuando es Seguimiento Sanitario Y Programa especial; si es Programa Sanitario siempre mostrar
            var ocultarDetallePorEspecial = soloSeg && esEsp;
            if (ocultarDetallePorEspecial) {
                // Seguimiento Sanitario + Programa especial: ocultar Detalle del programa y Agregar fila
                if (bloqueDet) { bloqueDet.classList.add('hidden'); bloqueDet.style.display = 'none'; }
                if (btnAgregar) { btnAgregar.classList.add('hidden'); btnAgregar.style.display = 'none'; }
                if (container) { container.classList.add('hidden'); container.style.display = 'none'; }
                if (msgTipo) msgTipo.classList.add('hidden');
            } else {
                // Programa Sanitario, o Seguimiento sin Programa especial: mostrar Detalle del programa y Agregar fila
                if (bloqueDet) { bloqueDet.classList.remove('hidden'); bloqueDet.style.display = 'block'; }
                if (btnAgregar) { btnAgregar.classList.remove('hidden'); btnAgregar.style.display = 'inline-flex'; }
                var codTipo = (document.getElementById('tipo') && document.getElementById('tipo').value) ? document.getElementById('tipo').value.trim() : '';
                if (codTipo) {
                    if (container) { container.classList.remove('hidden'); container.style.display = 'block'; }
                    currentCampos = getCamposActual();
                    buildThead(currentCampos);
                    // Reconstruir filas al cambiar columnas (p. ej. Programa Sanitario: edad/tolerancia se ocultan si Programa especial está marcado)
                    var tbody = document.getElementById('solicitudesBody');
                    var rowCount = tbody ? tbody.querySelectorAll('tr').length : 0;
                    if (rowCount > 0) {
                        tbody.innerHTML = '';
                        adjustSolicitudesRows(rowCount);
                    }
                    if (msgTipo) msgTipo.classList.add('hidden');
                } else {
                    if (container) { container.classList.add('hidden'); container.style.display = 'none'; }
                    if (msgTipo) msgTipo.classList.remove('hidden');
                }
            }
        }
        document.getElementById('tipo').addEventListener('change', function() {
            var codTipo = (this.value || '').toString().trim();
            if (codTipo) {
                generarCodigoPorTipo(codTipo);
                toggleTipoCP();
                aplicarVisibilidadCategoria();
                var tbody = document.getElementById('solicitudesBody');
                if (tbody && (tieneSanitario() || tieneSeguimiento())) tbody.innerHTML = '';
                solicitudesData = {};
            } else {
                document.getElementById('codigo').value = '';
                toggleTipoCP();
                document.getElementById('btnAgregarFila').classList.add('hidden');
                document.getElementById('solicitudesContainer').classList.add('hidden');
                document.getElementById('solicitudesBody').innerHTML = '';
                document.getElementById('solicitudesThead').innerHTML = '';
                solicitudesData = {};
            }
        });
        (function() {
            var selCat = document.getElementById('categoria');
            function onCategoriaChange() {
                var tbody = document.getElementById('solicitudesBody');
                if (tbody) tbody.innerHTML = '';
                solicitudesData = {};
                aplicarVisibilidadCategoria();
            }
            if (selCat) selCat.addEventListener('change', onCategoriaChange);
        })();
        (function() {
            var chk = document.getElementById('chkIncluirFechaFin');
            var inp = document.getElementById('fechaFin');
            if (chk && inp) {
                chk.addEventListener('change', function() {
                    if (this.checked) {
                        inp.disabled = false;
                    } else {
                        inp.disabled = true;
                        inp.value = '';
                    }
                });
            }
        })();
        var fechasManualesData = [];
        function renderListaFechasManuales() {
            var ul = document.getElementById('listaFechasManuales');
            var msg = document.getElementById('msgListaFechasManualesVacia');
            if (!ul) return;
            ul.innerHTML = '';
            fechasManualesData.forEach(function(fecha, idx) {
                var li = document.createElement('li');
                li.className = 'flex items-center justify-between gap-2 py-0.5';
                li.innerHTML = '<span class="text-gray-700">' + fecha + '</span><button type="button" class="btn-quitar-fecha-manual px-1.5 py-0.5 text-red-600 hover:bg-red-50 rounded text-xs border border-red-200" data-index="' + idx + '" title="Eliminar"><i class="fas fa-times"></i></button>';
                ul.appendChild(li);
            });
            if (msg) msg.classList.toggle('hidden', fechasManualesData.length > 0);
        }
        function aplicarVisibilidadEspecial() {
            var chk = document.getElementById('chkEspecial');
            var wrapOpciones = document.getElementById('wrapOpcionesEspecial');
            var wrapModo = document.getElementById('wrapModoEspecial');
            var wrapTol = document.getElementById('wrapToleranciaEspecial');
            var wrapPeriod = document.getElementById('wrapPeriodicidad');
            var wrapManual = document.getElementById('wrapFechasManuales');
            var radios = document.querySelectorAll('input[name="modoEspecial"]');
            if (!chk) return;
            if (wrapOpciones) wrapOpciones.classList.toggle('hidden', !chk.checked);
            if (chk.checked) {
                if (wrapModo) wrapModo.classList.remove('hidden');
                if (wrapTol) wrapTol.classList.remove('hidden');
                var per = document.querySelector('input[name="modoEspecial"][value="PERIODICIDAD"]');
                var man = document.querySelector('input[name="modoEspecial"][value="MANUAL"]');
                if (wrapPeriod) wrapPeriod.classList.toggle('hidden', !(per && per.checked));
                if (wrapManual) wrapManual.classList.toggle('hidden', !(man && man.checked));
            } else {
                if (wrapModo) wrapModo.classList.add('hidden');
                if (wrapTol) wrapTol.classList.add('hidden');
                if (wrapPeriod) wrapPeriod.classList.add('hidden');
                if (wrapManual) wrapManual.classList.add('hidden');
                radios.forEach(function(r) { r.checked = false; });
            }
        }
        (function() {
            var chk = document.getElementById('chkEspecial');
            if (chk) {
                chk.addEventListener('change', function() {
                    aplicarVisibilidadEspecial();
                    if (typeof aplicarVisibilidadCategoria === 'function') {
                        aplicarVisibilidadCategoria();
                        // Asegurar visibilidad tras actualizar estado (Seguimiento + Programa especial debe ocultar detalle y botón)
                        setTimeout(function() { if (typeof aplicarVisibilidadCategoria === 'function') aplicarVisibilidadCategoria(); }, 0);
                    }
                });
            }
            document.querySelectorAll('input[name="modoEspecial"]').forEach(function(r) {
                r.addEventListener('change', function() {
                    var v = this.value;
                    var wrapPeriod = document.getElementById('wrapPeriodicidad');
                    var wrapManual = document.getElementById('wrapFechasManuales');
                    if (wrapPeriod) wrapPeriod.classList.toggle('hidden', v !== 'PERIODICIDAD');
                    if (wrapManual) wrapManual.classList.toggle('hidden', v !== 'MANUAL');
                });
            });
            var btnAgregar = document.getElementById('btnAgregarFechaManual');
            var inpFecha = document.getElementById('nuevaFechaManual');
            if (btnAgregar && inpFecha) {
                btnAgregar.addEventListener('click', function() {
                    var v = (inpFecha.value || '').trim();
                    if (!v) return;
                    if (fechasManualesData.indexOf(v) !== -1) return;
                    fechasManualesData.push(v);
                    fechasManualesData.sort();
                    renderListaFechasManuales();
                    inpFecha.value = '';
                });
            }
            document.getElementById('wrapFechasManuales').addEventListener('click', function(e) {
                var btn = e.target.closest('.btn-quitar-fecha-manual');
                if (!btn) return;
                var idx = parseInt(btn.getAttribute('data-index'), 10);
                if (isNaN(idx) || idx < 0 || idx >= fechasManualesData.length) return;
                fechasManualesData.splice(idx, 1);
                renderListaFechasManuales();
            });
        })();
        var currentCampos = null;
        var modalProductoRowIndex = -1;
        var modalProveedorRowIndex = -1;
        var modalDescripcionRowIndex = -1;
        var _modalSecundarioCount = 0;
        function _notificarModalSecundarioAbierto() {
            if (window.self === window.top) return;
            _modalSecundarioCount++;
            if (_modalSecundarioCount === 1) try { window.top.postMessage('expandEditarIframe', '*'); } catch (e) {}
        }
        function _notificarModalSecundarioCerrado() {
            if (window.self === window.top) return;
            _modalSecundarioCount--;
            if (_modalSecundarioCount <= 0) { _modalSecundarioCount = 0; try { window.top.postMessage('restoreEditarIframe', '*'); } catch (e) {} }
        }
        var modalProductoSearchTimer = null;
        var modalProveedorSearchTimer = null;
        var modalProductoLineasCargadas = false;
        var modalProductoAlmacenesCargados = false;
        var baseUrlProductos = '../../configuracion/productos/';
        function cargarLineasAlmacenesModalProducto() {
            if (!modalProductoLineasCargadas) {
                fetch(baseUrlProductos + 'get_lineas.php').then(function(r) { return r.json(); }).then(function(res) {
                    if (!res.success || !res.data) return;
                    var sel = document.getElementById('modalProductoLinea');
                    if (!sel) return;
                    sel.innerHTML = '<option value="">Seleccionar</option>';
                    res.data.forEach(function(o) {
                        var opt = document.createElement('option');
                        opt.value = o.linea || '';
                        opt.textContent = o.text || (o.linea + ' - ' + (o.descri || ''));
                        sel.appendChild(opt);
                    });
                    modalProductoLineasCargadas = true;
                }).catch(function() {});
            }
            if (!modalProductoAlmacenesCargados) {
                fetch(baseUrlProductos + 'get_almacenes.php').then(function(r) { return r.json(); }).then(function(res) {
                    if (!res.success || !res.data) return;
                    var sel = document.getElementById('modalProductoAlmacen');
                    if (!sel) return;
                    sel.innerHTML = '<option value="">Seleccionar</option>';
                    res.data.forEach(function(o) {
                        var opt = document.createElement('option');
                        opt.value = o.alma || '';
                        opt.textContent = o.text || o.alma || '';
                        sel.appendChild(opt);
                    });
                    modalProductoAlmacenesCargados = true;
                }).catch(function() {});
            }
        }
        function abrirModalEnfermedadesDescripcion(rowIndex) {
            modalDescripcionRowIndex = rowIndex;
            var ta = document.getElementById('descripcion_vacuna_ro_' + rowIndex);
            var textoActual = (ta && ta.value) ? ta.value.trim() : '';
            var nombresPreseleccionados = [];
            if (textoActual) {
                var lineas = textoActual.split(/\r?\n/);
                lineas.forEach(function(ln) {
                    var t = ln.trim();
                    if (t.indexOf('- ') === 0) t = t.substring(2).trim();
                    else if (t.toLowerCase() === 'contra') return;
                    if (t) nombresPreseleccionados.push(t);
                });
                if (nombresPreseleccionados.length === 0 && textoActual.indexOf(',') !== -1) {
                    textoActual.split(',').forEach(function(s) { var x = s.trim(); if (x) nombresPreseleccionados.push(x); });
                }
            }
            var cont = document.getElementById('wrapCheckboxEnfermedadesDescripcion');
            var loading = document.getElementById('loadingEnfermedadesDescripcion');
            cont.innerHTML = '';
            loading.classList.remove('hidden');
            fetch('../../configuracion/productos/get_enfermedades.php').then(function(r) { return r.json(); }).then(function(res) {
                loading.classList.add('hidden');
                if (!res.success || !res.results) { cont.innerHTML = '<p class="text-gray-500 text-sm">No se pudieron cargar las enfermedades.</p>'; return; }
                var setNombres = {};
                nombresPreseleccionados.forEach(function(n) { setNombres[n.toLowerCase().trim()] = true; });
                (res.results || []).forEach(function(e) {
                    var nom = (e.nom_enf || '').trim();
                    var label = document.createElement('label');
                    label.className = 'flex items-center gap-2 cursor-pointer';
                    var cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.className = 'cb-enfermedad-descripcion';
                    cb.setAttribute('data-nom-enf', nom);
                    if (setNombres[nom.toLowerCase()]) cb.checked = true;
                    var span = document.createElement('span');
                    span.textContent = nom;
                    span.className = 'truncate';
                    label.appendChild(cb);
                    label.appendChild(span);
                    cont.appendChild(label);
                });
            }).catch(function() { loading.classList.add('hidden'); cont.innerHTML = '<p class="text-red-500 text-sm">Error al cargar.</p>'; });
            document.getElementById('modalEnfermedadesDescripcion').classList.remove('hidden');
            _notificarModalSecundarioAbierto();
        }
        function cerrarModalEnfermedadesDescripcion() {
            _notificarModalSecundarioCerrado();
            document.getElementById('modalEnfermedadesDescripcion').classList.add('hidden');
            modalDescripcionRowIndex = -1;
        }
        document.getElementById('btnCerrarModalEnfermedadesDescripcion').addEventListener('click', cerrarModalEnfermedadesDescripcion);
        document.getElementById('btnCancelarEnfermedadesDescripcion').addEventListener('click', cerrarModalEnfermedadesDescripcion);
        document.getElementById('modalEnfermedadesDescripcion').addEventListener('click', function(e) { if (e.target.id === 'modalEnfermedadesDescripcion') cerrarModalEnfermedadesDescripcion(); });
        document.getElementById('btnAceptarEnfermedadesDescripcion').addEventListener('click', function() {
            var row = modalDescripcionRowIndex;
            if (row < 0) { cerrarModalEnfermedadesDescripcion(); return; }
            var cont = document.getElementById('wrapCheckboxEnfermedadesDescripcion');
            var seleccionados = [];
            cont.querySelectorAll('.cb-enfermedad-descripcion:checked').forEach(function(cb) {
                var n = cb.getAttribute('data-nom-enf');
                if (n) seleccionados.push(n);
            });
            var texto = seleccionados.length > 0 ? 'Contra\n' + seleccionados.map(function(n) { return '- ' + n; }).join('\n') : '';
            var ta = document.getElementById('descripcion_vacuna_ro_' + row);
            if (ta) { ta.value = texto; autoResizeTextarea(ta); }
            if (solicitudesData[row]) solicitudesData[row].descripcionVacuna = texto;
            cerrarModalEnfermedadesDescripcion();
        });
        function autoResizeTextarea(ta) {
            if (!ta || !(ta.classList.contains('multiline') || ta.classList.contains('descripcion-vacuna-ta'))) return;
            ta.style.height = 'auto';
            var maxH = ta.classList.contains('descripcion-vacuna-ta') ? 280 : 120;
            ta.style.height = Math.max(36, Math.min(ta.scrollHeight, maxH)) + 'px';
        }
        document.getElementById('solicitudesContainer').addEventListener('input', function(e) {
            var ta = e.target;
            if (ta && (ta.tagName === 'TEXTAREA') && (ta.classList.contains('multiline') || ta.classList.contains('descripcion-vacuna-ta'))) autoResizeTextarea(ta);
        });
        document.getElementById('solicitudesContainer').addEventListener('click', function(e) {
            var btnInfoDescripcion = e.target.closest('.btn-info-descripcion');
            if (btnInfoDescripcion) {
                e.preventDefault();
                e.stopPropagation();
                var pop = document.getElementById('popoverInfoDescripcionFlotante');
                if (!pop) {
                    pop = document.createElement('div');
                    pop.id = 'popoverInfoDescripcionFlotante';
                    pop.innerHTML = 'En la secci\u00f3n 7.10 Productos se puede editar un producto e indicar si es una vacuna y seleccionar las enfermedades.';
                    document.body.appendChild(pop);
                }
                var isVisible = pop.classList.contains('visible');
                if (isVisible) {
                    pop.classList.remove('visible');
                } else {
                    var rect = btnInfoDescripcion.getBoundingClientRect();
                    var pad = 8;
                    pop.style.left = rect.left + 'px';
                    pop.style.top = (rect.bottom + 6) + 'px';
                    pop.classList.add('visible');
                    var w = pop.offsetWidth, h = pop.offsetHeight;
                    var left = parseFloat(pop.style.left) || 0, top = parseFloat(pop.style.top) || 0;
                    left = Math.max(pad, Math.min(left, window.innerWidth - w - pad));
                    top = Math.max(pad, Math.min(top, window.innerHeight - h - pad));
                    pop.style.left = left + 'px';
                    pop.style.top = top + 'px';
                }
                return;
            }
            var btnInfoProveedor = e.target.closest('.btn-info-proveedor');
            if (btnInfoProveedor) {
                e.preventDefault();
                e.stopPropagation();
                var pop = document.getElementById('popoverInfoProveedorFlotante');
                if (!pop) {
                    pop = document.createElement('div');
                    pop.id = 'popoverInfoProveedorFlotante';
                    pop.innerHTML = 'En 7.10 Productos puede asignar un proveedor al producto y se cargar\u00e1 por defecto.<br><br>O bien, puede buscarlo directamente con el \u00edcono de lupa.';
                    document.body.appendChild(pop);
                }
                var isVisible = pop.classList.contains('visible');
                if (isVisible) {
                    pop.classList.remove('visible');
                } else {
                    var rect = btnInfoProveedor.getBoundingClientRect();
                    var pad = 8;
                    pop.style.left = rect.left + 'px';
                    pop.style.top = (rect.bottom + 6) + 'px';
                    pop.classList.add('visible');
                    var w = pop.offsetWidth, h = pop.offsetHeight;
                    var left = parseFloat(pop.style.left) || 0, top = parseFloat(pop.style.top) || 0;
                    left = Math.max(pad, Math.min(left, window.innerWidth - w - pad));
                    top = Math.max(pad, Math.min(top, window.innerHeight - h - pad));
                    pop.style.left = left + 'px';
                    pop.style.top = top + 'px';
                }
                return;
            }
            var btnInfoEdad = e.target.closest('.btn-info-edad');
            if (btnInfoEdad) {
                e.preventDefault();
                e.stopPropagation();
                var pop = document.getElementById('popoverInfoEdadFlotante');
                if (!pop) {
                    pop = document.createElement('div');
                    pop.id = 'popoverInfoEdadFlotante';
                    pop.innerHTML = 'Use una edad (ej: 1, 2) o varias separadas por comas (ej: 2, 4, 6).<br><br><strong>No se permite 0.</strong> Para el d\u00eda anterior a la edad 1 ingrese <strong>-1</strong>; para dos d\u00edas antes, <strong>-2</strong>, y as\u00ed sucesivamente.';
                    document.body.appendChild(pop);
                }
                var isVisible = pop.classList.contains('visible');
                if (isVisible) {
                    pop.classList.remove('visible');
                } else {
                    var rect = btnInfoEdad.getBoundingClientRect();
                    var pad = 8;
                    pop.style.left = rect.left + 'px';
                    pop.style.top = (rect.bottom + 6) + 'px';
                    pop.classList.add('visible');
                    var w = pop.offsetWidth, h = pop.offsetHeight;
                    var left = parseFloat(pop.style.left) || 0, top = parseFloat(pop.style.top) || 0;
                    left = Math.max(pad, Math.min(left, window.innerWidth - w - pad));
                    top = Math.max(pad, Math.min(top, window.innerHeight - h - pad));
                    pop.style.left = left + 'px';
                    pop.style.top = top + 'px';
                }
                return;
            }
            var btnInfoTolerancia = e.target.closest('.btn-info-tolerancia');
            if (btnInfoTolerancia) {
                e.preventDefault();
                e.stopPropagation();
                var pop = document.getElementById('popoverInfoToleranciaFlotante');
                if (!pop) {
                    pop = document.createElement('div');
                    pop.id = 'popoverInfoToleranciaFlotante';
                    pop.className = 'popover-info-flotante';
                    pop.innerHTML = TOLERANCIA_TITLE.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                    document.body.appendChild(pop);
                }
                var isVisible = pop.classList.contains('visible');
                if (isVisible) {
                    pop.classList.remove('visible');
                } else {
                    var rect = btnInfoTolerancia.getBoundingClientRect();
                    var pad = 8;
                    pop.style.left = rect.left + 'px';
                    pop.style.top = (rect.bottom + 6) + 'px';
                    pop.classList.add('visible');
                    var w = pop.offsetWidth, h = pop.offsetHeight;
                    var left = parseFloat(pop.style.left) || 0, top = parseFloat(pop.style.top) || 0;
                    left = Math.max(pad, Math.min(left, window.innerWidth - w - pad));
                    top = Math.max(pad, Math.min(top, window.innerHeight - h - pad));
                    pop.style.left = left + 'px';
                    pop.style.top = top + 'px';
                }
                return;
            }
            var btnProd = e.target.closest('.btn-buscar-celda');
            if (btnProd) {
                var row = parseInt(btnProd.getAttribute('data-row'), 10);
                if (isNaN(row)) return;
                modalProductoRowIndex = row;
                var parentTieneOverlay = false;
                try {
                    if (window.parent !== window.self) { parentTieneOverlay = !!window.parent.document.getElementById('overlayProductoProveedor'); }
                } catch (err) {}
                if (parentTieneOverlay) {
                    try { window.parent.postMessage({ tipo: 'abrirModalProducto', rowIndex: row }, '*'); } catch (err) {}
                    return;
                }
                limpiarModalProducto();
                cargarLineasAlmacenesModalProducto();
                document.getElementById('modalBuscarProducto').classList.remove('hidden');
                setTimeout(function() { document.getElementById('modalProductoBuscar').focus(); }, 100);
                return;
            }
            var btnProv = e.target.closest('.btn-buscar-proveedor');
            if (btnProv) {
                var row = parseInt(btnProv.getAttribute('data-row'), 10);
                if (isNaN(row)) return;
                modalProveedorRowIndex = row;
                var parentTieneOverlay = false;
                try {
                    if (window.parent !== window.self) { parentTieneOverlay = !!window.parent.document.getElementById('overlayProductoProveedor'); }
                } catch (err) {}
                if (parentTieneOverlay) {
                    try { window.parent.postMessage({ tipo: 'abrirModalProveedor', rowIndex: row }, '*'); } catch (err) {}
                    return;
                }
                document.getElementById('modalProveedorBuscar').value = '';
                document.getElementById('modalProveedorResultados').innerHTML = '<p class="text-gray-500 text-sm p-2">Escriba para buscar proveedor.</p>';
                document.getElementById('modalBuscarProveedor').classList.remove('hidden');
                setTimeout(function() { document.getElementById('modalProveedorBuscar').focus(); }, 100);
                return;
            }
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.btn-info-edad')) {
                var pop = document.getElementById('popoverInfoEdadFlotante');
                if (pop) pop.classList.remove('visible');
            }
            if (!e.target.closest('.btn-info-tolerancia')) {
                var popTol = document.getElementById('popoverInfoToleranciaFlotante');
                if (popTol) popTol.classList.remove('visible');
            }
            if (!e.target.closest('.btn-info-proveedor')) {
                var popProv = document.getElementById('popoverInfoProveedorFlotante');
                if (popProv) popProv.classList.remove('visible');
            }
            if (!e.target.closest('.btn-info-descripcion')) {
                var popDesc = document.getElementById('popoverInfoDescripcionFlotante');
                if (popDesc) popDesc.classList.remove('visible');
            }
        });
        document.getElementById('btnCerrarModalProducto').addEventListener('click', function() {
            document.getElementById('modalBuscarProducto').classList.add('hidden');
            modalProductoRowIndex = -1;
        });
        document.getElementById('modalBuscarProducto').addEventListener('click', function(e) {
            if (e.target.id === 'modalBuscarProducto') { document.getElementById('modalBuscarProducto').classList.add('hidden'); modalProductoRowIndex = -1; }
        });
        function limpiarModalProducto() {
            if (document.getElementById('modalProductoBuscar')) document.getElementById('modalProductoBuscar').value = '';
            if (document.getElementById('modalProductoLinea')) document.getElementById('modalProductoLinea').value = '';
            if (document.getElementById('modalProductoAlmacen')) document.getElementById('modalProductoAlmacen').value = '';
            var c = document.getElementById('modalProductoResultados');
            if (c) c.innerHTML = '<p class="text-gray-500 text-sm p-2">Seleccione línea y/o almacén o escriba para buscar producto.</p>';
        }
        function limpiarModalProveedor() {
            if (document.getElementById('modalProveedorBuscar')) document.getElementById('modalProveedorBuscar').value = '';
            var c = document.getElementById('modalProveedorResultados');
            if (c) c.innerHTML = '<p class="text-gray-500 text-sm p-2">Escriba para buscar proveedor.</p>';
        }
        function ejecutarBusquedaModalProducto() {
            var q = (document.getElementById('modalProductoBuscar') && document.getElementById('modalProductoBuscar').value) ? document.getElementById('modalProductoBuscar').value.trim() : '';
            var lin = (document.getElementById('modalProductoLinea') && document.getElementById('modalProductoLinea').value) ? document.getElementById('modalProductoLinea').value.trim() : '';
            var alma = (document.getElementById('modalProductoAlmacen') && document.getElementById('modalProductoAlmacen').value) ? document.getElementById('modalProductoAlmacen').value.trim() : '';
            var cont = document.getElementById('modalProductoResultados');
            if (!q && !lin && !alma) {
                cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Seleccione línea y/o almacén o escriba para buscar producto.</p>';
                return;
            }
            var esc = function(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
            cont.innerHTML = q ? '<p class="text-gray-500 text-sm p-2">Buscando <strong>"' + esc(q) + '"</strong>...</p>' : '<p class="text-gray-500 text-sm p-2">Buscando...</p>';
            var url = 'get_productos_programa.php?q=' + encodeURIComponent(q) + (lin ? '&lin=' + encodeURIComponent(lin) : '') + (alma ? '&alma=' + encodeURIComponent(alma) : '');
            fetch(url).then(function(r) { return r.json(); }).then(function(data) {
                if (!data.success || !data.results || !data.results.length) {
                    cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Sin resultados.</p>';
                    return;
                }
                var html = '';
                data.results.forEach(function(item) {
                    var cod = item.codigo || item.id;
                    var desc = (item.descri || item.text || '').replace(/^[^\s-]+\s*-\s*/, '');
                    var esc = function(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
                    var labelHtml = '<strong>' + esc(cod) + '</strong> - ' + esc(desc);
                    html += '<div class="modal-producto-item p-2 border-b border-gray-100 hover:bg-gray-100 cursor-pointer text-sm" data-id="' + (item.id || '').replace(/"/g, '&quot;') + '" data-codigo="' + (cod || '').replace(/"/g, '&quot;') + '" data-descri="' + (desc || '').replace(/"/g, '&quot;') + '">' + labelHtml + '</div>';
                });
                cont.innerHTML = html;
                cont.querySelectorAll('.modal-producto-item').forEach(function(el) {
                    el.onclick = function() {
                        var id = this.getAttribute('data-id');
                        var codigo = this.getAttribute('data-codigo');
                        var descri = this.getAttribute('data-descri');
                        var text = (codigo || '') + (descri ? '\n' + descri : '');
                        var row = modalProductoRowIndex;
                        var inpCod = document.getElementById('producto_' + row);
                        var inpText = document.getElementById('producto_text_' + row);
                        if (inpCod) inpCod.value = id || '';
                        if (inpText) { inpText.value = text; autoResizeTextarea(inpText); }
                        document.getElementById('modalBuscarProducto').classList.add('hidden');
                        modalProductoRowIndex = -1;
                        limpiarModalProducto();
                        if (id) onProductoChange(row);
                    };
                });
            }).catch(function() { cont.innerHTML = '<p class="text-red-500 text-sm p-2">Error al buscar.</p>'; });
        }
        document.getElementById('btnModalProductoLimpiar').addEventListener('click', function() {
            if (document.getElementById('modalProductoLinea')) document.getElementById('modalProductoLinea').value = '';
            if (document.getElementById('modalProductoAlmacen')) document.getElementById('modalProductoAlmacen').value = '';
            if (document.getElementById('modalProductoBuscar')) document.getElementById('modalProductoBuscar').value = '';
            var cont = document.getElementById('modalProductoResultados');
            if (cont) cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Escriba para buscar producto.</p>';
        });
        function programarBusquedaModalProducto() {
            if (modalProductoSearchTimer) clearTimeout(modalProductoSearchTimer);
            modalProductoSearchTimer = setTimeout(ejecutarBusquedaModalProducto, 300);
        }
        document.getElementById('modalProductoLinea').addEventListener('change', programarBusquedaModalProducto);
        document.getElementById('modalProductoAlmacen').addEventListener('change', programarBusquedaModalProducto);
        document.getElementById('modalProductoBuscar').addEventListener('input', function() {
            var q = (this.value || '').trim();
            var cont = document.getElementById('modalProductoResultados');
            if (modalProductoSearchTimer) clearTimeout(modalProductoSearchTimer);
            if (!q) { cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Escriba para buscar producto.</p>'; return; }
            var esc = function(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
            cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Buscando <strong>"' + esc(q) + '"</strong>...</p>';
            modalProductoSearchTimer = setTimeout(ejecutarBusquedaModalProducto, 250);
        });
        document.getElementById('btnCerrarModalProveedor').addEventListener('click', function() {
            document.getElementById('modalBuscarProveedor').classList.add('hidden');
            modalProveedorRowIndex = -1;
        });
        document.getElementById('modalBuscarProveedor').addEventListener('click', function(e) {
            if (e.target.id === 'modalBuscarProveedor') { document.getElementById('modalBuscarProveedor').classList.add('hidden'); modalProveedorRowIndex = -1; }
        });
        document.getElementById('modalProveedorBuscar').addEventListener('input', function() {
            var q = (this.value || '').trim();
            var cont = document.getElementById('modalProveedorResultados');
            if (modalProveedorSearchTimer) clearTimeout(modalProveedorSearchTimer);
            if (!q) { cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Escriba para buscar proveedor.</p>'; return; }
            cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Buscando...</p>';
            modalProveedorSearchTimer = setTimeout(function() {
                fetch('get_ccte_lista.php?q=' + encodeURIComponent(q)).then(function(r) { return r.json(); }).then(function(data) {
                    if (!data.success || !data.data || !data.data.length) {
                        cont.innerHTML = '<p class="text-gray-500 text-sm p-2">Sin resultados.</p>';
                        return;
                    }
                    var html = '';
                    var esc = function(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
                    data.data.forEach(function(item) {
                        var cod = item.codigo || '';
                        var nom = item.nombre || '';
                        var labelHtml = '<strong>' + esc(cod) + '</strong> - ' + esc(nom);
                        html += '<div class="modal-proveedor-item p-2 border-b border-gray-100 hover:bg-gray-100 cursor-pointer text-sm" data-codigo="' + (cod + '').replace(/"/g, '&quot;') + '" data-nombre="' + (nom + '').replace(/"/g, '&quot;') + '">' + labelHtml + '</div>';
                    });
                    cont.innerHTML = html;
                    cont.querySelectorAll('.modal-proveedor-item').forEach(function(el) {
                        el.onclick = function() {
                            var codigo = this.getAttribute('data-codigo') || '';
                            var nombre = this.getAttribute('data-nombre') || '';
                            var row = modalProveedorRowIndex;
                            var inpCod = document.getElementById('codProveedor_' + row);
                            var inpNom = document.getElementById('proveedor_' + row);
                            if (inpCod) inpCod.value = codigo;
                            if (inpNom) {
                                inpNom.value = codigo + (nombre ? '\n' + nombre : '');
                                autoResizeTextarea(inpNom);
                            }
                            document.getElementById('modalBuscarProveedor').classList.add('hidden');
                            modalProveedorRowIndex = -1;
                            limpiarModalProveedor();
                        };
                    });
                }).catch(function() { cont.innerHTML = '<p class="text-red-500 text-sm p-2">Error al buscar.</p>'; });
            }, 250);
        });
        function onProductoChange(rowIndex) {
            var inp = document.getElementById('producto_' + rowIndex);
            if (!inp || !inp.value) return;
            if (!solicitudesData[rowIndex]) solicitudesData[rowIndex] = {};
            fetch('get_datos_producto_programa.php?codigo=' + encodeURIComponent(inp.value)).then(function(r) { return r.json(); }).then(function(data) {
                if (!data || data.success === false) return;
                solicitudesData[rowIndex].codProveedor = data.codProveedor || '';
                solicitudesData[rowIndex].nomProducto = data.nomProducto || '';
                solicitudesData[rowIndex].dosis = data.dosis || '';
                solicitudesData[rowIndex].esVacuna = data.esVacuna || false;
                var desc = (data.descripcionVacuna || '').trim();
                var descTexto = data.esVacuna && desc ? 'Contra\n' + desc.split(',').map(function(s) { return '- ' + s.trim(); }).filter(Boolean).join('\n') : desc || '';
                solicitudesData[rowIndex].descripcionVacuna = descTexto;
                var codProv = document.getElementById('codProveedor_' + rowIndex);
                if (codProv) codProv.value = data.codProveedor || '';
                var prov = document.getElementById('proveedor_' + rowIndex);
                if (prov) {
                    prov.value = (data.codProveedor || '') + (data.nomProveedor ? '\n' + data.nomProveedor : '');
                    autoResizeTextarea(prov);
                }
                var inpProdText = document.getElementById('producto_text_' + rowIndex);
                if (inpProdText && data.nomProducto) {
                    var codProd = document.getElementById('producto_' + rowIndex);
                    var cod = (codProd && codProd.value) ? codProd.value : (data.codProducto || '');
                    inpProdText.value = cod + '\n' + data.nomProducto;
                    autoResizeTextarea(inpProdText);
                }
                var unid = document.getElementById('unidad_ro_' + rowIndex); if (unid) unid.value = data.unidad || '';
                var dosisInp = document.getElementById('dosis_' + rowIndex); if (dosisInp) dosisInp.value = data.dosis || '';
                var descVac = document.getElementById('descripcion_vacuna_ro_' + rowIndex);
                if (descVac) {
                    descVac.value = descTexto;
                    autoResizeTextarea(descVac);
                }
                var ud = document.getElementById('unidadDosis_' + rowIndex); var nf = document.getElementById('numeroFrascos_' + rowIndex);
                if (ud && nf) {
                    var sigla = getSiglaActual();
                    if (sigla === 'PL' || sigla === 'GR') { if (data.esVacuna) { ud.disabled = false; ud.value = ''; nf.disabled = false; nf.value = ''; } else { ud.disabled = true; ud.value = ''; nf.disabled = true; nf.value = ''; } }
                    else { ud.disabled = false; nf.disabled = false; }
                }
            }).catch(function() {});
        }
        function adjustSolicitudesRows(count) {
            var tbody = document.getElementById('solicitudesBody');
            var container = document.getElementById('solicitudesContainer');
            var msgTipo = document.getElementById('solicitudesMsgTipo');
            if (!tbody) return;
            currentCampos = getCamposActual();
            if (count < 1) {
                tbody.innerHTML = '';
                return;
            }
            if (!document.getElementById('tipo').value) {
                if (msgTipo) { msgTipo.classList.remove('hidden'); msgTipo.textContent = 'Seleccione primero el tipo de programa.'; }
                container.classList.add('hidden');
                return;
            }
            if (msgTipo) msgTipo.classList.add('hidden');
            container.classList.remove('hidden');
            if (!document.getElementById('solicitudesThead').innerHTML) buildThead(currentCampos);
            var current = tbody.querySelectorAll('tr').length;
            var sigla = getSiglaActual();
            if (count > current) {
                for (var i = current; i < count; i++) {
                    solicitudesData[i] = solicitudesData[i] || {};
                    var tr = document.createElement('tr');
                    tr.className = 'border-b border-gray-200';
                    tr.innerHTML = buildRowHtml(currentCampos, i);
                    tbody.appendChild(tr);
                    var inpUb = document.getElementById('ubicacion_' + i); if (inpUb && solicitudesData[i].ubicacion) { inpUb.value = solicitudesData[i].ubicacion; autoResizeTextarea(inpUb); }
                    var inpDosis = document.getElementById('dosis_' + i); if (inpDosis && solicitudesData[i].dosis) inpDosis.value = solicitudesData[i].dosis;
                    var inpCodProv = document.getElementById('codProveedor_' + i); var inpProv = document.getElementById('proveedor_' + i);
                    if (inpCodProv && solicitudesData[i].codProveedor) inpCodProv.value = solicitudesData[i].codProveedor;
                    if (inpProv) {
                        var pv = solicitudesData[i].nomProveedor || '';
                        inpProv.value = (pv.indexOf('\n') !== -1) ? pv : ((solicitudesData[i].codProveedor || '') + (pv ? '\n' + pv : ''));
                        autoResizeTextarea(inpProv);
                    }
                    var inpDescVac = document.getElementById('descripcion_vacuna_ro_' + i);
                    if (inpDescVac && solicitudesData[i].descripcionVacuna) { inpDescVac.value = solicitudesData[i].descripcionVacuna; autoResizeTextarea(inpDescVac); }
                    var inpEdad = document.getElementById('edad_' + i); if (inpEdad && solicitudesData[i].edad !== undefined && solicitudesData[i].edad !== '') inpEdad.value = String(solicitudesData[i].edad);
                    var ud = document.getElementById('unidadDosis_' + i); var nf = document.getElementById('numeroFrascos_' + i);
                    if (sigla === 'PL' || sigla === 'GR') { if (ud) ud.disabled = true; if (nf) nf.disabled = true; }
                    var inpProd = document.getElementById('producto_' + i); var inpProdText = document.getElementById('producto_text_' + i);
                    if (inpProd && solicitudesData[i].codProducto) inpProd.value = solicitudesData[i].codProducto;
                    if (inpProdText) {
                        var np = solicitudesData[i].nomProducto || '';
                        inpProdText.value = (np.indexOf('\n') !== -1) ? np : ((solicitudesData[i].codProducto || '') + (np ? '\n' + np : ''));
                        autoResizeTextarea(inpProdText);
                    }
                }
                tbody.querySelectorAll('.btn-quitar-fila').forEach(function(btn) {
                    var rowIdx = parseInt(btn.getAttribute('data-row'), 10);
                    btn.onclick = function() { quitarFila(rowIdx); };
                });
            } else if (count < current) {
                for (var j = current - 1; j >= count; j--) {
                    var trs = tbody.querySelectorAll('tr');
                    if (trs[j]) trs[j].remove();
                    delete solicitudesData[j];
                }
            } else {
                for (var i = 0; i < count; i++) {
                    var tr = tbody.querySelectorAll('tr')[i];
                    if (tr) {
                        tr.innerHTML = buildRowHtml(currentCampos, i);
                        var inpUb = document.getElementById('ubicacion_' + i); if (inpUb && solicitudesData[i] && solicitudesData[i].ubicacion) { inpUb.value = solicitudesData[i].ubicacion; autoResizeTextarea(inpUb); }
                        var inpDosis = document.getElementById('dosis_' + i); if (inpDosis && solicitudesData[i] && solicitudesData[i].dosis) inpDosis.value = solicitudesData[i].dosis;
                        var inpCodProv = document.getElementById('codProveedor_' + i); var inpProv = document.getElementById('proveedor_' + i);
                        if (inpCodProv && solicitudesData[i].codProveedor) inpCodProv.value = solicitudesData[i].codProveedor;
                        if (inpProv) {
                            var pv = (solicitudesData[i] && solicitudesData[i].nomProveedor) ? solicitudesData[i].nomProveedor : '';
                            inpProv.value = (pv.indexOf('\n') !== -1) ? pv : ((solicitudesData[i].codProveedor || '') + (pv ? '\n' + pv : ''));
                            autoResizeTextarea(inpProv);
                        }
                        var inpDescVac = document.getElementById('descripcion_vacuna_ro_' + i);
                        if (inpDescVac && solicitudesData[i] && solicitudesData[i].descripcionVacuna) { inpDescVac.value = solicitudesData[i].descripcionVacuna; autoResizeTextarea(inpDescVac); }
                        var inpEdad = document.getElementById('edad_' + i); if (inpEdad && solicitudesData[i] && solicitudesData[i].edad !== undefined && solicitudesData[i].edad !== '') inpEdad.value = String(solicitudesData[i].edad);
                        var inpProd = document.getElementById('producto_' + i); var inpProdText = document.getElementById('producto_text_' + i);
                        if (inpProd && solicitudesData[i].codProducto) inpProd.value = solicitudesData[i].codProducto;
                        if (inpProdText) {
                            var np = (solicitudesData[i] && solicitudesData[i].nomProducto) ? solicitudesData[i].nomProducto : '';
                            inpProdText.value = (np.indexOf('\n') !== -1) ? np : ((solicitudesData[i].codProducto || '') + (np ? '\n' + np : ''));
                            autoResizeTextarea(inpProdText);
                        }
                    }
                }
                tbody.querySelectorAll('.btn-quitar-fila').forEach(function(btn) {
                    var rowIdx = parseInt(btn.getAttribute('data-row'), 10);
                    btn.onclick = function() { quitarFila(rowIdx); };
                });
            }
        }
        /** Parsea campo edad: "2", "2,4", "-1" -> array de números. No se permite 0; -1 = día anterior a edad 1, -2 = dos días antes, etc. Sin rango fijo. */
        function parseEdades(edadStr) {
            if (typeof edadStr !== 'string') edadStr = '';
            var parts = edadStr.split(',').map(function(s) { return parseInt(s.trim(), 10); }).filter(function(n) { return !isNaN(n) && n !== 0; });
            return parts;
        }
        /** Lee el valor de un campo del detalle para la fila s según la columna mostrada (id del input). Solo se usan columnas que están en cols (campos con valor 1). */
        function leerValorDetalle(colKey, s) {
            var el;
            switch (colKey) {
                case 'ubicacion': el = document.getElementById('ubicacion_' + s); return el ? (el.value || '').trim() : '';
                case 'producto': el = document.getElementById('producto_' + s); return el ? (el.value || '').trim() : '';
                case 'proveedor': el = document.getElementById('codProveedor_' + s); return el ? (el.value || '').trim() : '';
                case 'unidad': el = document.getElementById('unidad_ro_' + s); return el ? (el.value || '').trim() : '';
                case 'dosis': el = document.getElementById('dosis_' + s); return el ? (el.value || '').trim() : '';
                case 'unidadDosis': el = document.getElementById('unidadDosis_' + s); return el ? (el.value || '').trim() : '';
                case 'numeroFrascos': el = document.getElementById('numeroFrascos_' + s); return el ? (el.value || '').trim() : '';
                case 'edad': el = document.getElementById('edad_' + s); return el ? String(el.value || '').trim() : '';
                case 'descripcion_vacuna': el = document.getElementById('descripcion_vacuna_ro_' + s); return el ? (el.value || '').trim() : '';
                case 'area_galpon': el = document.getElementById('area_galpon_' + s); return el ? (parseInt(el.value, 10) || null) : null;
                case 'tolerancia': el = document.getElementById('tolerancia_' + s); return el ? (parseInt(el.value, 10) || 1) : 1;
                case 'cantidad_por_galpon': el = document.getElementById('cantidad_por_galpon_' + s); return el ? (parseInt(el.value, 10) || null) : null;
                default: return '';
            }
        }
        function getRowDataFromRowIndex(s, cols) {
            var inpText = document.getElementById('producto_text_' + s);
            var nomProducto = (inpText && inpText.value) ? inpText.value.trim() : '';
            if (solicitudesData[s] && solicitudesData[s].nomProducto) nomProducto = solicitudesData[s].nomProducto;
            var obj = { ubicacion: '', codProducto: '', nomProducto: nomProducto, codProveedor: '', nomProveedor: '', unidades: '', dosis: '', unidadDosis: '', numeroFrascos: '', edad: '', descripcionVacuna: '', esVacuna: !!(solicitudesData[s] && solicitudesData[s].esVacuna), areaGalpon: null, cantidadPorGalpon: null, tolerancia: 1 };
            if (cols.indexOf('ubicacion') !== -1) obj.ubicacion = leerValorDetalle('ubicacion', s);
            if (cols.indexOf('producto') !== -1) { obj.codProducto = leerValorDetalle('producto', s); obj.nomProducto = nomProducto; }
            if (cols.indexOf('proveedor') !== -1) { obj.codProveedor = leerValorDetalle('proveedor', s); obj.nomProveedor = (document.getElementById('proveedor_' + s) && document.getElementById('proveedor_' + s).value) ? document.getElementById('proveedor_' + s).value.trim() : ''; }
            if (cols.indexOf('unidad') !== -1) obj.unidades = leerValorDetalle('unidad', s);
            if (cols.indexOf('dosis') !== -1) obj.dosis = leerValorDetalle('dosis', s);
            if (cols.indexOf('unidadDosis') !== -1) obj.unidadDosis = leerValorDetalle('unidadDosis', s);
            if (cols.indexOf('numeroFrascos') !== -1) obj.numeroFrascos = leerValorDetalle('numeroFrascos', s);
            if (cols.indexOf('edad') !== -1) obj.edad = leerValorDetalle('edad', s);
            if (cols.indexOf('descripcion_vacuna') !== -1) obj.descripcionVacuna = leerValorDetalle('descripcion_vacuna', s);
            if (cols.indexOf('area_galpon') !== -1) obj.areaGalpon = leerValorDetalle('area_galpon', s);
            if (cols.indexOf('cantidad_por_galpon') !== -1) obj.cantidadPorGalpon = leerValorDetalle('cantidad_por_galpon', s);
            if (cols.indexOf('tolerancia') !== -1) obj.tolerancia = leerValorDetalle('tolerancia', s);
            return obj;
        }
        /** Devuelve un objeto por fila de la tabla (para quitar/restaurar), con edad como string. Solo incluye valores de columnas mostradas (campos con valor 1). */
        function getRowDataForTable() {
            var tbody = document.getElementById('solicitudesBody');
            if (!tbody) return [];
            var rows = tbody.querySelectorAll('tr');
            var campos = getCamposActual();
            var cols = getColumnasFromCampos(campos || {});
            var out = [];
            for (var s = 0; s < rows.length; s++) {
                var row = getRowDataFromRowIndex(s, cols);
                out.push({ ubicacion: row.ubicacion, codProducto: row.codProducto, nomProducto: row.nomProducto, codProveedor: row.codProveedor, nomProveedor: row.nomProveedor, unidades: row.unidades, dosis: row.dosis, unidadDosis: row.unidadDosis, numeroFrascos: row.numeroFrascos, edad: row.edad, descripcionVacuna: row.descripcionVacuna, esVacuna: row.esVacuna, areaGalpon: row.areaGalpon, cantidadPorGalpon: row.cantidadPorGalpon, tolerancia: row.tolerancia !== undefined && row.tolerancia !== null ? row.tolerancia : 1 });
            }
            return out;
        }
        /** Construye el array de detalles para enviar al backend. Solo incluye valores de columnas mostradas (campos con valor 1 en el tipo). */
        function getDetallesFromForm() {
            var tbody = document.getElementById('solicitudesBody');
            if (!tbody) return [];
            var rows = tbody.querySelectorAll('tr');
            var campos = getCamposActual();
            var cols = getColumnasFromCampos(campos || {});
            var esEsp = typeof esProgramaEspecial === 'function' && esProgramaEspecial();
            var tolEspecial = 1;
            if (esEsp) {
                var inp = document.getElementById('toleranciaEspecial');
                tolEspecial = inp ? Math.max(1, parseInt(inp.value, 10) || 1) : 1;
            }
            var out = [];
            var posDetalle = 0;
            for (var s = 0; s < rows.length; s++) {
                var row = getRowDataFromRowIndex(s, cols);
                var toleranciaVal = esEsp ? tolEspecial : (row.tolerancia !== undefined && row.tolerancia !== null ? (parseInt(row.tolerancia, 10) || 1) : 1);
                var base = {
                    ubicacion: row.ubicacion,
                    codProducto: row.codProducto,
                    nomProducto: row.nomProducto,
                    codProveedor: row.codProveedor,
                    nomProveedor: row.nomProveedor,
                    unidades: row.unidades,
                    dosis: row.dosis,
                    unidadDosis: row.unidadDosis,
                    numeroFrascos: row.numeroFrascos,
                    descripcionVacuna: row.descripcionVacuna,
                    esVacuna: row.esVacuna,
                    areaGalpon: row.areaGalpon,
                    cantidadPorGalpon: row.cantidadPorGalpon,
                    tolerancia: toleranciaVal
                };
                var edades = parseEdades(row.edad);
                if (edades.length === 0) {
                    posDetalle++;
                    out.push(Object.assign({}, base, { edad: 0, posDetalle: posDetalle }));
                } else {
                    edades.forEach(function(edadVal) {
                        posDetalle++;
                        out.push(Object.assign({}, base, { edad: edadVal, posDetalle: posDetalle }));
                    });
                }
            }
            return out;
        }
        function quitarFila(index) {
            var data = getRowDataForTable();
            data.splice(index, 1);
            solicitudesData = {};
            data.forEach(function(d, i) { solicitudesData[i] = d; });
            var tbody = document.getElementById('solicitudesBody');
            if (tbody) tbody.innerHTML = '';
            adjustSolicitudesRows(data.length);
        }
        document.getElementById('btnAgregarFila').addEventListener('click', function() {
            var tbody = document.getElementById('solicitudesBody');
            var selCat = document.getElementById('categoria');
            if (!selCat || !selCat.value || (selCat.value || '').trim() === '') {
                if (window._swalEnParent) window._swalEnParent('warning', 'Aviso', 'Escoja una categoría.');
                else Swal.fire({ icon: 'warning', title: 'Aviso', text: 'Escoja una categoría.' });
                return;
            }
            if (typeof esSoloSeguimiento === 'function' && esSoloSeguimiento() && typeof esProgramaEspecial === 'function' && esProgramaEspecial()) return;
            if (!document.getElementById('tipo').value) {
                if (window._swalEnParent) window._swalEnParent('warning', 'Aviso', 'Seleccione primero el tipo de programa.');
                else Swal.fire({ icon: 'warning', title: 'Aviso', text: 'Seleccione primero el tipo de programa.' });
                return;
            }
            var current = tbody ? tbody.querySelectorAll('tr').length : 0;
            if (current >= 50) return;
            adjustSolicitudesRows(current + 1);
        });

        document.getElementById('btnLimpiarForm').addEventListener('click', function() {
            document.getElementById('formPrograma').reset();
            document.getElementById('codigo').value = '';
            var selCat = document.getElementById('categoria'); if (selCat) selCat.value = '';
            var fi = document.getElementById('fechaInicio'); if (fi) fi.value = '';
            var chkFf = document.getElementById('chkIncluirFechaFin'); if (chkFf) chkFf.checked = false;
            var ff = document.getElementById('fechaFin'); if (ff) { ff.value = ''; ff.disabled = true; }
            var chkEsp = document.getElementById('chkEspecial'); if (chkEsp) chkEsp.checked = false;
            var inpTolEsp = document.getElementById('toleranciaEspecial'); if (inpTolEsp) inpTolEsp.value = '1';
            document.querySelectorAll('input[name="modoEspecial"]').forEach(function(r) { r.checked = false; });
            var intM = document.getElementById('intervaloMeses'); if (intM) intM.value = '1';
            var diaM = document.getElementById('diaDelMes'); if (diaM) diaM.value = '15';
            fechasManualesData = [];
            if (typeof renderListaFechasManuales === 'function') renderListaFechasManuales();
            if (typeof aplicarVisibilidadEspecial === 'function') aplicarVisibilidadEspecial();
            if (typeof aplicarVisibilidadCategoria === 'function') aplicarVisibilidadCategoria();
            document.getElementById('solicitudesBody').innerHTML = '';
            document.getElementById('solicitudesThead').innerHTML = '';
            document.getElementById('btnAgregarFila').classList.add('hidden');
            document.getElementById('solicitudesContainer').classList.add('hidden');
            var msgTipo = document.getElementById('solicitudesMsgTipo');
            if (msgTipo) msgTipo.classList.add('hidden');
            var bloqueDet = document.getElementById('bloqueDetalle');
            if (bloqueDet) bloqueDet.classList.add('hidden');
            solicitudesData = {};
        });

        document.getElementById('formPrograma').addEventListener('submit', function(e) {
            e.preventDefault();
            var tipo = document.getElementById('tipo');
            var codTipo = tipo.value;
            var nomTipo = tipo.options[tipo.selectedIndex] ? tipo.options[tipo.selectedIndex].textContent : '';
            var codigo = document.getElementById('codigo').value.trim();
            var nombre = document.getElementById('nombre').value.trim();
            var categoria = (typeof getCategoriaFromCheckboxes === 'function') ? getCategoriaFromCheckboxes() : '';
            var despliegue = document.getElementById('despliegue') ? document.getElementById('despliegue').value.trim() : '';
            var descripcion = document.getElementById('descripcion') ? document.getElementById('descripcion').value.trim() : '';
            var tipoCP = (codigo.startsWith('CP') && document.getElementById('tipoCP')) ? document.getElementById('tipoCP').value.trim() : '';
            var fechaInicio = (document.getElementById('fechaInicio') && document.getElementById('fechaInicio').value) ? document.getElementById('fechaInicio').value.trim() : '';
            var chkIncluirFin = document.getElementById('chkIncluirFechaFin');
            var fechaFin = (chkIncluirFin && chkIncluirFin.checked && document.getElementById('fechaFin') && document.getElementById('fechaFin').value) ? document.getElementById('fechaFin').value.trim() : '';
            var chkEsp = document.getElementById('chkEspecial');
            var esEspecial = (chkEsp && chkEsp.checked) ? 1 : 0;
            var modoEspecial = '';
            var intervaloMesesVal = null;
            var diaDelMesVal = null;
            var fechasManualesVal = [];
            if (esEspecial) {
                var rad = document.querySelector('input[name="modoEspecial"]:checked');
                modoEspecial = rad ? rad.value : '';
                if (modoEspecial === 'PERIODICIDAD') {
                    var i = document.getElementById('intervaloMeses');
                    var d = document.getElementById('diaDelMes');
                    intervaloMesesVal = (i && i.value !== '') ? Math.max(1, Math.min(12, parseInt(i.value, 10) || 1)) : 1;
                    diaDelMesVal = (d && d.value !== '') ? Math.max(1, Math.min(31, parseInt(d.value, 10) || 15)) : 15;
                } else if (modoEspecial === 'MANUAL' && typeof fechasManualesData !== 'undefined') {
                    fechasManualesVal = fechasManualesData.slice();
                }
            }
            if (!categoria || !codTipo || !codigo || !nombre) {
                if (window._swalEnParent) window._swalEnParent('warning', 'Datos incompletos', 'Marque al menos una categoría y complete tipo, código y nombre.');
                else Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Marque al menos una categoría y complete tipo, código y nombre.' });
                return;
            }
            if (!fechaInicio) {
                if (window._swalEnParent) window._swalEnParent('warning', 'Datos incompletos', 'La fecha de inicio es obligatoria.');
                else Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'La fecha de inicio es obligatoria.' });
                return;
            }
            var hoyStr = new Date().toISOString().slice(0, 10);
            if (window._originalFechaInicio !== undefined && fechaInicio !== (window._originalFechaInicio || '') && fechaInicio < hoyStr) {
                if (window._swalEnParent) window._swalEnParent('warning', 'Fecha inválida', 'La fecha de inicio debe ser mayor o igual a hoy.');
                else Swal.fire({ icon: 'warning', title: 'Fecha inválida', text: 'La fecha de inicio debe ser mayor o igual a hoy.' });
                return;
            }
            var origFin = (window._originalFechaFin !== undefined) ? (window._originalFechaFin || '') : null;
            if (origFin !== null && fechaFin && origFin && fechaFin < origFin && fechaFin < hoyStr) {
                if (window._swalEnParent) window._swalEnParent('warning', 'Ajuste la fecha fin', 'Está moviendo la fecha fin a un día ya pasado. Elija hoy o un día futuro.');
                else Swal.fire({ icon: 'warning', title: 'Ajuste la fecha fin', text: 'Está moviendo la fecha fin a un día ya pasado. Elija hoy o un día futuro.' });
                return;
            }
            var campos = getCamposActual();
            var cols = getColumnasFromCampos(campos || {});
            var noExigeEdad = (tieneSanitario() && !tieneSeguimiento()) || (typeof esProgramaEspecial === 'function' && esProgramaEspecial());
            if (cols.indexOf('edad') !== -1 && !noExigeEdad) {
                var tbody = document.getElementById('solicitudesBody');
                var rows = tbody ? tbody.querySelectorAll('tr') : [];
                for (var i = 0; i < rows.length; i++) {
                    var edadStr = leerValorDetalle('edad', i);
                    if (edadStr === '') continue;
                    if (/\-\s+\d/.test(edadStr)) {
                        if (window._swalEnParent) window._swalEnParent('warning', 'Formato de edad incorrecto', 'El signo menos debe estar junto al número. Correcto: -2, -1. Incorrecto: -  4 (hay espacio entre el menos y el número).');
                        else Swal.fire({ icon: 'warning', title: 'Formato de edad incorrecto', text: 'El signo menos debe estar junto al número. Correcto: -2, -1. Incorrecto: -  4 (hay espacio entre el menos y el número).' });
                        return;
                    }
                    var partes = edadStr.split(',').map(function(s) { return parseInt(String(s).trim(), 10); });
                    var tieneCero = partes.some(function(n) { return n === 0; });
                    if (tieneCero) {
                        if (window._swalEnParent) window._swalEnParent('warning', 'Edad no permitida', 'No se permite la edad 0. Corrija las edades en el detalle (por ejemplo use 1, 2, -1, etc.).');
                        else Swal.fire({ icon: 'warning', title: 'Edad no permitida', text: 'No se permite la edad 0. Corrija las edades en el detalle (por ejemplo use 1, 2, -1, etc.).' });
                        return;
                    }
                }
            }
            var detalles = getDetallesFromForm();
            var soloSegMasEspecial = (typeof esSoloSeguimiento === 'function' && esSoloSeguimiento()) && (typeof esProgramaEspecial === 'function' && esProgramaEspecial());
            if (soloSegMasEspecial) {
                var tolEsp = 1;
                var inp = document.getElementById('toleranciaEspecial');
                if (inp) tolEsp = Math.max(1, parseInt(inp.value, 10) || 1);
                detalles = [{
                    edad: null,
                    tolerancia: tolEsp,
                    fechas: fechasManualesVal || [],
                    intervaloMeses: intervaloMesesVal,
                    diaDelMes: diaDelMesVal
                }];
            } else if (esEspecial && detalles.length > 0) {
                detalles = detalles.map(function(d) {
                    d.fechas = fechasManualesVal || [];
                    d.intervaloMeses = intervaloMesesVal;
                    d.diaDelMes = diaDelMesVal;
                    return d;
                });
            }
            var permiteDetallesVacios = soloSegMasEspecial || (typeof esSoloSeguimiento === 'function' && esSoloSeguimiento()) || (typeof esProgramaEspecial === 'function' && esProgramaEspecial());
            if (detalles.length < 1 && !permiteDetallesVacios) {
                if (window._swalEnParent) window._swalEnParent('warning', 'Datos incompletos', 'Debe haber al menos un detalle. Agregue por lo menos una fila con ubicación y producto.');
                else Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Debe haber al menos un detalle. Agregue por lo menos una fila con ubicación y producto.' });
                return;
            }
            var sigla = getSiglaActual();
            var cambioFechas = (window._originalFechaInicio !== undefined) && (fechaInicio !== (window._originalFechaInicio || '') || (fechaFin || '') !== (window._originalFechaFin || ''));
            var cambioDetalles = (window._originalDetalles !== undefined) && (JSON.stringify(detalles) !== window._originalDetalles);
            var tieneAsignacionesPasadas = window._tieneAsignacionesPasadas === true;
            var tieneAsignacionesFuturas = window._tieneAsignacionesFuturas === true;
            var crearNuevo = cambioDetalles && tieneAsignacionesPasadas && tieneAsignacionesFuturas;
            function hacerRecalcular(codPrograma, msgExtra, cerrar, codProgramaOrigen, fechaInicio, fechaFin, conDebug, fechaFinAnterior, fechaInicioAnterior, soloCambioFechas) {
                var body = { codPrograma: codPrograma };
                if (codProgramaOrigen && codProgramaOrigen !== codPrograma) body.codProgramaOrigen = codProgramaOrigen;
                if (fechaInicio && typeof fechaInicio === 'string' && fechaInicio.trim()) body.fechaInicio = fechaInicio.trim();
                if (fechaFin !== undefined && fechaFin !== null && fechaFin !== '') body.fechaFin = typeof fechaFin === 'string' ? fechaFin.trim() : fechaFin;
                if (fechaFinAnterior !== undefined && fechaFinAnterior !== null && typeof fechaFinAnterior === 'string' && fechaFinAnterior.trim()) body.fechaFinAnterior = fechaFinAnterior.trim();
                if (fechaInicioAnterior !== undefined && fechaInicioAnterior !== null && typeof fechaInicioAnterior === 'string' && fechaInicioAnterior.trim()) body.fechaInicioAnterior = fechaInicioAnterior.trim();
                if (soloCambioFechas === true) body.soloCambioFechaFin = true;
                if (conDebug) body.debug = true;
                var modalCarga = document.getElementById('modalCargaRecalcular');
                var bar = modalCarga ? modalCarga.querySelector('#recalcLoadingBar') : null;
                var progInterval = null;
                var enIframe = (window.self !== window.top);
                function setProgreso(pct) {
                    var val = Math.min(100, Math.max(0, pct));
                    if (bar) bar.style.width = val + '%';
                    if (enIframe) { try { window.parent.postMessage({ tipo: 'recalcProgreso', pct: val }, '*'); } catch (err) {} }
                }
                function iniciarProgreso() {
                    setProgreso(0);
                    var inicio = Date.now();
                    var duracionEstimada = 8000;
                    progInterval = setInterval(function() {
                        var elapsed = Date.now() - inicio;
                        var pct = Math.min(90, (elapsed / duracionEstimada) * 90);
                        setProgreso(pct);
                        if (pct >= 90) clearInterval(progInterval);
                    }, 150);
                }
                function completarProgreso() {
                    if (progInterval) { clearInterval(progInterval); progInterval = null; }
                    setProgreso(100);
                }
                if (enIframe) {
                    try { window.parent.postMessage({ tipo: 'mostrarModalCargaRecalcular' }, '*'); } catch (err) {}
                    iniciarProgreso();
                    /* No mostrar modal local: el overlay del padre cubre toda la pantalla */
                } else if (modalCarga) {
                    iniciarProgreso();
                    modalCarga.classList.remove('hidden');
                }
                var urlRecalc = (!codProgramaOrigen || codProgramaOrigen === codPrograma) ? '../cronograma/recalcular_fechas_programa_editado.php' : '../cronograma/recalcular_cronograma_programa.php';
                return fetch(urlRecalc, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
                    .then(function(r) { return r.json(); })
                    .then(function(rec) {
                        completarProgreso();
                        if (enIframe) { try { window.parent.postMessage({ tipo: 'ocultarModalCargaRecalcular' }, '*'); } catch (err) {} }
                        else { setTimeout(function() { if (modalCarga) modalCarga.classList.add('hidden'); }, 300); }
                        if (rec.debug && typeof console !== 'undefined' && console.log) console.log('Recalcular debug:', rec.debug);
                        var texto = (rec.success && rec.total !== undefined && rec.total > 0) ? (msgExtra + ' Se realizó el recálculo de asignaciones (' + rec.total + ' registro(s)).') : (rec.success ? msgExtra : (msgExtra + (rec.message ? ' ' + rec.message : '')));
                        if (window._swalEnParent) window._swalEnParent('success', 'Listo', texto, false);
                        else Swal.fire({ icon: 'success', title: 'Listo', text: texto }).then(function() { if (cerrar && document.getElementById('btnLimpiarForm')) document.getElementById('btnLimpiarForm').click(); });
                        if (window.self !== window.top) {
                            try { window.parent.postMessage({ tipo: 'programaGuardado', success: true, nuevoCodigo: crearNuevo ? codPrograma : null }, '*'); } catch (err) {}
                        }
                    })
                    .catch(function() {
                        completarProgreso();
                        if (enIframe) { try { window.parent.postMessage({ tipo: 'ocultarModalCargaRecalcular' }, '*'); } catch (err) {} }
                        setTimeout(function() { if (modalCarga) modalCarga.classList.add('hidden'); }, 300);
                        var texto = msgExtra + ' No se pudo recalcular el cronograma.';
                        if (window._swalEnParent) window._swalEnParent('warning', 'Aviso', texto, false);
                        else Swal.fire({ icon: 'warning', title: 'Aviso', text: texto });
                        if (window.self !== window.top) {
                            try { window.parent.postMessage({ tipo: 'programaGuardado', success: true, nuevoCodigo: crearNuevo ? codPrograma : null }, '*'); } catch (err) {}
                        }
                    });
            }
            function enviarActualizar(payloadData, codigoRecalcular, msgOk, cerrar, codigoOrigen) {
                var url = (window._modoEditar) ? 'actualizar_programa.php' : 'guardar_programa.php';
                fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payloadData) })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.success) {
                            document.getElementById('modalBuscarProducto').classList.add('hidden');
                            document.getElementById('modalBuscarProveedor').classList.add('hidden');
                            modalProductoRowIndex = -1;
                            modalProveedorRowIndex = -1;
                            limpiarModalProducto();
                            limpiarModalProveedor();
                            if (!window._modoEditar) {
                                if (window._swalEnParent) window._swalEnParent('success', 'Guardado', res.message, true);
                                else Swal.fire({ icon: 'success', title: 'Guardado', text: res.message }).then(function() {
                                    document.getElementById('formPrograma').reset();
                                    document.getElementById('codigo').value = '';
                                    document.getElementById('solicitudesBody').innerHTML = '';
                                    document.getElementById('solicitudesThead').innerHTML = '';
                                    document.getElementById('btnAgregarFila').classList.add('hidden');
                                    document.getElementById('solicitudesContainer').classList.add('hidden');
                                    solicitudesData = {};
                                });
                                return;
                            }
                            var recCod = (res.recalcularCodigo != null && res.recalcularCodigo !== '') ? res.recalcularCodigo : codigoRecalcular;
                            if (recCod && (cambioFechas || cambioDetalles)) {
                                var fechaFinAnt = (cambioFechas && window._originalFechaFin !== undefined) ? (window._originalFechaFin || '') : undefined;
                                var fechaInicioAnt = (cambioFechas && window._originalFechaInicio !== undefined) ? (window._originalFechaInicio || '') : undefined;
                                var soloCambioFechas = cambioFechas && !cambioDetalles;
                                hacerRecalcular(recCod, msgOk, true, codigoOrigen, payloadData.fechaInicio, payloadData.fechaFin, true, fechaFinAnt, fechaInicioAnt, soloCambioFechas);
                            } else {
                                if (window._swalEnParent) window._swalEnParent('success', 'Actualizado', msgOk, false);
                                else Swal.fire({ icon: 'success', title: 'Actualizado', text: msgOk });
                                if (window.self !== window.top) {
                                    try { window.parent.postMessage({ tipo: 'programaGuardado', success: true, nuevoCodigo: null }, '*'); } catch (err) {}
                                }
                            }
                        } else {
                            if (window._swalEnParent) window._swalEnParent('error', 'Error', res.message || 'No se pudo guardar.');
                            else Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo guardar.' });
                        }
                    })
                    .catch(function() {
                        if (window._swalEnParent) window._swalEnParent('error', 'Error', 'Error de conexión.');
                        else Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
                    });
            }
            if (crearNuevo) {
                var msgHtml = 'Este programa tiene <strong>asignaciones pasadas</strong>.<br><br>' +
                    'Al guardar, se creará un <strong>nuevo programa</strong>. Las asignaciones pasadas permanecerán en el programa actual y se recalcularán las asignaciones para el nuevo programa.<br><br>¿Confirmar guardado?';
                var opts = {
                    title: 'Aviso al guardar',
                    html: msgHtml,
                    icon: 'warning',
                    iconColor: '#d97706',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#6b7280'
                };
                var hacerCrearNuevo = function() {
                    fetch('generar_codigo_nec.php?codTipo=' + encodeURIComponent(codTipo))
                        .then(function(r) { return r.json(); })
                        .then(function(resCod) {
                            if (!resCod.success || !resCod.codigo) {
                                if (window._swalEnParent) window._swalEnParent('error', 'Error', resCod.message || 'No se pudo generar el nuevo código.');
                                else Swal.fire({ icon: 'error', title: 'Error', text: resCod.message || 'No se pudo generar el nuevo código.' });
                                return;
                            }
                            var nuevoCodigo = String(resCod.codigo).trim();
                            var payload = { crearNuevoPrograma: true, nuevoCodigo: nuevoCodigo, codigo: nuevoCodigo, codigoProgramaViejo: codigo, nombre: nombre, codTipo: parseInt(codTipo, 10), nomTipo: nomTipo, sigla: sigla, despliegue: despliegue, descripcion: descripcion, fechaInicio: hoyStr, fechaFin: fechaFin || null, detalles: detalles, categoria: categoria, esEspecial: esEspecial, modoEspecial: modoEspecial || null, intervaloMeses: intervaloMesesVal, diaDelMes: diaDelMesVal, fechasManuales: fechasManualesVal };
                            if (nuevoCodigo.startsWith('CP') && tipoCP) payload.tipo = tipoCP;
                            enviarActualizar(payload, nuevoCodigo, 'Se creó un nuevo programa (' + nuevoCodigo + '). Las asignaciones pasadas permanecen en el programa anterior.', true, codigo);
                        })
                        .catch(function() {
                            if (window._swalEnParent) window._swalEnParent('error', 'Error', 'Error al obtener el nuevo código.');
                            else Swal.fire({ icon: 'error', title: 'Error', text: 'Error al obtener el nuevo código.' });
                        });
                };
                if (window.self !== window.top) {
                    try {
                        window.parent.postMessage({ tipo: 'mostrarSwalConfirmar', title: opts.title, html: msgHtml, icon: opts.icon, iconColor: opts.iconColor, confirmButtonText: opts.confirmButtonText, cancelButtonText: opts.cancelButtonText, confirmButtonColor: opts.confirmButtonColor, cancelButtonColor: opts.cancelButtonColor }, '*');
                        var handlerCrear = function(ev) {
                            if (ev.data && ev.data.tipo === 'swalConfirmResult') {
                                window.removeEventListener('message', handlerCrear);
                                if (ev.data.isConfirmed) hacerCrearNuevo();
                            }
                        };
                        window.addEventListener('message', handlerCrear);
                    } catch (err) {
                        if (typeof Swal !== 'undefined') Swal.fire(opts).then(function(result) { if (result.isConfirmed) hacerCrearNuevo(); });
                        else if (confirm('Este programa tiene asignaciones pasadas. Al guardar se creará un nuevo programa. ¿Confirmar?')) hacerCrearNuevo();
                    }
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire(opts).then(function(result) {
                        if (result.isConfirmed) hacerCrearNuevo();
                    });
                } else {
                    if (confirm('Este programa tiene asignaciones pasadas. Al guardar se creará un nuevo programa. ¿Confirmar?')) hacerCrearNuevo();
                }
                return;
            }
            if (cambioFechas && window._modoEditar) {
                var cambioInicio = fechaInicio !== (window._originalFechaInicio || '');
                var cambioFin = (fechaFin || '') !== (window._originalFechaFin || '');
                var queModifico = '';
                if (cambioInicio && cambioFin) queModifico = 'la <strong>fecha de inicio</strong> y la <strong>fecha de fin</strong>';
                else if (cambioInicio) queModifico = 'la <strong>fecha de inicio</strong>';
                else queModifico = 'la <strong>fecha de fin</strong>';
                var msgHtmlRecalc = 'Ha modificado ' + queModifico + ' del programa.<br><br>Se recalcularán las fechas de las asignaciones relacionadas con este programa.<br><br>¿Confirmar guardado?';
                var optsRecalc = {
                    title: 'Aviso al guardar',
                    html: msgHtmlRecalc,
                    icon: 'warning',
                    iconColor: '#d97706',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#6b7280'
                };
                var hacerEnviar = function() {
                    var payload = { codigo: codigo, nombre: nombre, codTipo: parseInt(codTipo, 10), nomTipo: nomTipo, sigla: sigla, despliegue: despliegue, descripcion: descripcion, fechaInicio: fechaInicio, fechaFin: fechaFin || null, detalles: detalles, categoria: categoria, esEspecial: esEspecial, modoEspecial: modoEspecial || null, intervaloMeses: intervaloMesesVal, diaDelMes: diaDelMesVal, fechasManuales: fechasManualesVal };
                    if (codigo.startsWith('CP') && tipoCP) payload.tipo = tipoCP;
                    enviarActualizar(payload, codigo, 'Programa actualizado correctamente.', true);
                };
                if (window.self !== window.top) {
                    try {
                        window.parent.postMessage({ tipo: 'mostrarSwalConfirmar', title: optsRecalc.title, html: msgHtmlRecalc, icon: optsRecalc.icon, iconColor: optsRecalc.iconColor, confirmButtonText: optsRecalc.confirmButtonText, cancelButtonText: optsRecalc.cancelButtonText, confirmButtonColor: optsRecalc.confirmButtonColor, cancelButtonColor: optsRecalc.cancelButtonColor }, '*');
                        var handlerRecalc = function(ev) {
                            if (ev.data && ev.data.tipo === 'swalConfirmResult') {
                                window.removeEventListener('message', handlerRecalc);
                                if (ev.data.isConfirmed) hacerEnviar();
                            }
                        };
                        window.addEventListener('message', handlerRecalc);
                    } catch (err) {
                        if (typeof Swal !== 'undefined') Swal.fire(optsRecalc).then(function(result) { if (result.isConfirmed) hacerEnviar(); });
                        else if (confirm('Se recalcularán las fechas de las asignaciones relacionadas con este programa. ¿Confirmar guardado?')) hacerEnviar();
                    }
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire(optsRecalc).then(function(result) {
                        if (result.isConfirmed) hacerEnviar();
                    });
                } else {
                    if (confirm('Se recalcularán las fechas de las asignaciones relacionadas con este programa. ¿Confirmar guardado?')) hacerEnviar();
                }
                return;
            }
            var payload = { codigo: codigo, nombre: nombre, codTipo: parseInt(codTipo, 10), nomTipo: nomTipo, sigla: sigla, despliegue: despliegue, descripcion: descripcion, fechaInicio: fechaInicio, fechaFin: fechaFin || null, detalles: detalles, categoria: categoria, esEspecial: esEspecial, modoEspecial: modoEspecial || null, intervaloMeses: intervaloMesesVal, diaDelMes: diaDelMesVal, fechasManuales: fechasManualesVal };
            if (codigo.startsWith('CP') && tipoCP) payload.tipo = tipoCP;
            enviarActualizar(payload, codigo, 'Programa actualizado correctamente.', true);
        });

        cargarTipos().then(function() {
            if (typeof aplicarVisibilidadCategoria === 'function') aplicarVisibilidadCategoria();
            if (window._modoEditar && window._codigoEditar) {
                cargarProgramaParaEditar();
            }
        });

        // Cuando se abre en iframe (modal desde listado): SweetAlert en el padre para que se vea por fuera del modal
        if (window._modoEditar && window.self !== window.top) {
            window._swalEnParent = function(icon, title, text, cerrarAlConfirmar) {
                try { window.parent.postMessage({ tipo: 'mostrarSwal', icon: icon || 'info', title: title || '', text: text || '', cerrarAlConfirmar: !!cerrarAlConfirmar }, '*'); } catch (e) {}
            };
        } else {
            window._swalEnParent = null;
        }
        if (window._modoEditar && window.self !== window.top) {
            document.body.classList.add('en-modal-editar');
            var formFooter = document.getElementById('formProgramaFooter');
            if (formFooter) formFooter.classList.add('hidden');
            window.submitFormPrograma = function() {
                var form = document.getElementById('formPrograma');
                if (form) form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            };
            window.addEventListener('message', function(e) {
                if (e.data === 'limpiarFormPrograma') {
                    var btn = document.getElementById('btnLimpiarForm');
                    if (btn) btn.click();
                }
                if (e.data && e.data.tipo === 'productoSeleccionado') {
                    var row = e.data.rowIndex;
                    var id = e.data.id; var codigo = e.data.codigo; var descri = e.data.descri || '';
                    var inpCod = document.getElementById('producto_' + row);
                    var inpText = document.getElementById('producto_text_' + row);
                    if (inpCod) inpCod.value = id || '';
                    if (inpText) inpText.value = (codigo || '') + (descri ? '\n' + descri : '');
                    if (typeof autoResizeTextarea === 'function' && inpText) autoResizeTextarea(inpText);
                    if (id && typeof onProductoChange === 'function') onProductoChange(row);
                }
                if (e.data && e.data.tipo === 'proveedorSeleccionado') {
                    var row = e.data.rowIndex;
                    var codigo = e.data.codigo || ''; var nombre = e.data.nombre || '';
                    var inpCod = document.getElementById('codProveedor_' + row);
                    var inpNom = document.getElementById('proveedor_' + row);
                    if (inpCod) inpCod.value = codigo;
                    if (inpNom) {
                        inpNom.value = codigo + (nombre ? '\n' + nombre : '');
                        if (typeof autoResizeTextarea === 'function') autoResizeTextarea(inpNom);
                    }
                }
                if (e.data && e.data.tipo === 'contextoZonaSubzonaPrograma') {
                    window._contextZona = (e.data.zona || '').toString().trim();
                    window._contextSubzona = (e.data.subzona || '').toString().trim();
                }
                if (e.data && e.data.tipo === 'tieneAsignacionesPasadas') {
                    window._tieneAsignacionesPasadas = !!e.data.tieneAsignacionesPasadas;
                    window._tieneAsignacionesFuturas = !!e.data.tieneAsignacionesFuturas;
                    window._soloAsignacionesPasadas = window._tieneAsignacionesPasadas && !window._tieneAsignacionesFuturas;
                    if (window._soloAsignacionesPasadas && typeof aplicarRestriccionSoloFechaFin === 'function') {
                        aplicarRestriccionSoloFechaFin();
                    }
                }
            });
        }
    </script>
</body>
</html>
