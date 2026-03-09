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
    <title>Estándares - Configuración</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <link rel="stylesheet" href="../../../css/dashboard-config.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="../../../assets/js/fetch-auth-redirect.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="../../../assets/js/i18n/datatables-es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../../assets/js/sweetalert-helpers.js"></script>
    <style>
        body { background: #f0f4f8; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none; padding: 0.5rem 1rem; font-size: 0.8125rem; font-weight: 600;
            color: white; border-radius: 0.375rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;
            transition: all 0.2s;
        }
        .btn-primary:hover { background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%); transform: translateY(-1px); }
        .form-control { width: 100%; padding: 0.375rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.375rem; font-size: 0.8125rem; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
        .tabla-estandares { font-size: 0.75rem; width: 100%; min-width: 500px; border-collapse: collapse; }
        .tabla-estandares th, .tabla-estandares td { padding: 4px 6px; vertical-align: middle; border: 1px solid #e2e8f0; }
        .tabla-estandares th { background: #f1f5f9; font-weight: 600; }
        .tabla-estandares .form-control.compact { padding: 0.25rem 0.5rem; font-size: 0.75rem; min-height: 28px; }
        .btn-add-row, .btn-agregar-actividad { padding: 0.3rem 0.6rem; font-size: 0.8rem; border-radius: 0.375rem; }
        .btn-quitar { padding: 0.2rem 0.4rem; font-size: 0.7rem; border-radius: 0.2rem; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; cursor: pointer; }
        .btn-quitar:hover { background: #fee2e2; }
        .col-num { width: 28px; }
        .col-quitar { width: 36px; }
        .btn-icon { padding: 0.35rem; border-radius: 0.375rem; transition: all 0.2s; }
        .btn-icon.p-2 { padding: 0.5rem; }

        /* Vista tipo Mind Manager */
        .map-container { display: flex; gap: 0; min-height: 580px; background: #f8fafc; border-radius: 1rem; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,.08); }
        .map-tree-panel { width: 380px; min-width: 340px; border-right: 1px solid #cbd5e1; background: linear-gradient(180deg, #f1f5f9 0%, #fff 100%); overflow: auto; padding: 1rem; }
        .map-detail-panel { flex: 1; min-width: 0; overflow-y: auto; padding: 1.25rem; background: #fff; }
        .map-breadcrumb { display: flex; flex-wrap: wrap; align-items: center; gap: 0.25rem; padding: 0.5rem 0; margin-bottom: 1rem; font-size: 0.8rem; color: #64748b; }
        .map-breadcrumb span { color: #94a3b8; }
        .map-breadcrumb a, .map-breadcrumb .current { color: #334155; text-decoration: none; }
        .map-breadcrumb a:hover { color: #2563eb; }
        .map-breadcrumb .current { font-weight: 600; color: #1e293b; }

        /* MindManager: nodo raíz */
        .mm-root { background: #fff; border: 2px solid #1e40af; border-radius: 8px; padding: 0.6rem 1rem; font-weight: 700; font-size: 1rem; color: #1e40af; text-align: center; margin: 0 auto 1rem; max-width: 280px; box-shadow: 0 2px 4px rgba(30,64,175,.15); cursor: pointer; transition: all 0.2s; }
        .mm-root:hover { background: #eff6ff; box-shadow: 0 4px 8px rgba(30,64,175,.2); }
        .mm-root.selected { background: #dbeafe; border-color: #2563eb; }

        /* MindManager: ramas principales (subprocesos) - óvalos azules */
        .mm-branch { margin-bottom: 1rem; }
        .mm-node-sub { display: inline-flex; align-items: center; gap: 0.35rem; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 2px solid #1e40af; border-radius: 999px; padding: 0.4rem 0.9rem; font-weight: 600; font-size: 0.85rem; color: #1e40af; cursor: pointer; transition: all 0.2s; margin-bottom: 0.5rem; }
        .mm-node-sub:hover { background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%); box-shadow: 0 2px 6px rgba(30,64,175,.25); }
        .mm-node-sub.selected { background: linear-gradient(135deg, #93c5fd 0%, #60a5fa 100%); border-color: #1d4ed8; color: #fff; box-shadow: 0 2px 8px rgba(30,64,175,.35); }

        /* MindManager: indicador expandir/colapsar (círculo con - o +) */
        .mm-toggle { width: 20px; height: 20px; border-radius: 50%; border: 1px solid #64748b; background: #fff; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; cursor: pointer; transition: all 0.15s; }
        .mm-toggle:hover { background: #e2e8f0; border-color: #475569; }
        .mm-toggle.collapsed { transform: none; }
        .mm-toggle .minus { line-height: 1; }
        .mm-toggle .plus { line-height: 1; font-size: 0.65rem; }

        /* MindManager: sub-nodos (actividades) - texto con línea conectora */
        .mm-children { margin-left: 1.25rem; padding-left: 0.75rem; border-left: 2px solid #94a3b8; }
        .mm-children.collapsed { display: none; }
        .mm-node-act { display: flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.4rem; margin: 0.2rem 0; font-size: 0.8rem; color: #334155; cursor: pointer; border-radius: 4px; transition: all 0.15s; }
        .mm-node-act:hover { background: #e2e8f0; color: #1e293b; }
        .mm-node-act.selected { background: #dbeafe; color: #1e40af; font-weight: 600; }
        .mm-node-act .mm-num { color: #64748b; font-size: 0.7rem; min-width: 1.2em; }

        /* MindManager: nodos hoja (parámetros) - con círculo bullet */
        .mm-node-param { display: flex; align-items: center; gap: 0.4rem; padding: 0.15rem 0.4rem 0.15rem 0; margin: 0.1rem 0; font-size: 0.75rem; color: #64748b; cursor: pointer; border-radius: 3px; transition: all 0.15s; margin-left: 0.5rem; }
        .mm-node-param:hover { background: #f1f5f9; color: #475569; }
        .mm-node-param.selected { background: #e0e7ff; color: #3730a3; }
        .mm-node-param .mm-bullet { width: 10px; height: 10px; border-radius: 50%; border: 1.5px solid #94a3b8; background: #fff; flex-shrink: 0; }
        .mm-node-param.selected .mm-bullet { border-color: #4f46e5; background: #e0e7ff; }

        /* Botones agregar/eliminar estilo MindManager */
        .mm-add { width: 20px; height: 20px; border-radius: 50%; border: 1px dashed #94a3b8; background: transparent; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; flex-shrink: 0; cursor: pointer; transition: all 0.15s; }
        .mm-add:hover { border-color: #16a34a; color: #16a34a; background: #dcfce7; }
        .mm-delete { width: 18px; height: 18px; border-radius: 50%; border: 1px solid #fecaca; background: transparent; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 0.55rem; flex-shrink: 0; cursor: pointer; transition: all 0.15s; }
        .mm-delete:hover { background: #fef2f2; border-color: #dc2626; }
        .mm-move { width: 18px; height: 18px; border-radius: 4px; border: 1px solid #cbd5e1; background: transparent; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.5rem; flex-shrink: 0; cursor: pointer; transition: all 0.15s; }
        .mm-move:hover { background: #e2e8f0; color: #475569; }
        .mm-move:disabled, .mm-move.disabled { opacity: 0.4; cursor: not-allowed; }

        .map-tree-empty { padding: 1.5rem; text-align: center; color: #64748b; font-size: 0.85rem; }
        .map-add-sub-root.w-full { width: 100%; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-3 { margin-top: 0.75rem; }

        .detail-placeholder { text-align: center; padding: 3rem 2rem; color: #94a3b8; }
        .detail-placeholder i { font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.5; }
        .detail-placeholder p { margin: 0.25rem 0; }
        .detail-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; width: 100%; }
        .detail-header .ml-auto { margin-left: auto; }
        .detail-header h3 { margin: 0; font-size: 1.1rem; color: #1e293b; }
        .detail-content { margin-top: 1rem; }
        .bloque-subproceso-modal { border: 1px solid #e2e8f0; border-radius: 0.5rem; margin-bottom: 1rem; overflow: hidden; background: #f8fafc; }
        .bloque-subproceso-modal .subproceso-header { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-bottom: 1px solid #e2e8f0; }
        .bloque-subproceso-modal .subproceso-header .input-subproceso { flex: 1; max-width: 360px; }
        .bloque-subproceso-modal .subproceso-body { padding: 0.5rem 0.75rem; }
        .bloque-actividad { margin-bottom: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; overflow: hidden; background: #fff; }
        .actividad-header { display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.6rem; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
        .actividad-header .input-actividad { flex: 1; max-width: 300px; }
        .actividad-tabla { padding: 0.5rem 0.6rem; overflow-x: auto; }
        .actividad-tabla .tabla-estandares { min-width: 480px; }
        .btn-add-discreto {
            background: transparent; border: 1px dashed #94a3b8; color: #64748b;
            padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem;
            cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem; transition: all 0.15s ease;
        }
        .btn-add-discreto:hover { background: #f1f5f9; border-color: #64748b; color: #475569; }
        .btn-add-discreto i { font-size: 0.65rem; opacity: 0.9; }
        .map-toolbar { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; }
        .view-toggle { display: flex; gap: 0; border-radius: 0.375rem; overflow: hidden; border: 1px solid #e2e8f0; }
        .view-toggle button { padding: 0.4rem 0.75rem; font-size: 0.8rem; border: none; background: #fff; color: #64748b; cursor: pointer; transition: all 0.15s; }
        .view-toggle button.active { background: #2563eb; color: #fff; }
        .view-toggle button:not(:first-child) { border-left: 1px solid #e2e8f0; }

        /* Vista Directa: árbol full-width con edición inline */
        .direct-view { width: 100%; min-height: 400px; padding: 1rem; overflow-y: auto; background: #f8fafc; }
        .direct-view .mm-root-input { background: #fff; border: 2px solid #1e40af; border-radius: 8px; padding: 0.6rem 1rem; font-weight: 700; font-size: 1rem; color: #1e40af; text-align: center; margin: 0 auto 1rem; max-width: 320px; display: block; width: 100%; }
        .direct-view .mm-inline-sub { display: inline-flex; align-items: center; gap: 0.35rem; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 2px solid #1e40af; border-radius: 999px; padding: 0.35rem 0.6rem; font-weight: 600; font-size: 0.85rem; color: #1e40af; margin-bottom: 0.5rem; min-width: 120px; }
        .direct-view .mm-inline-sub input { background: transparent; border: none; font: inherit; color: inherit; flex: 1; min-width: 80px; }
        .direct-view .mm-inline-sub input:focus { outline: none; }
        .direct-view .mm-inline-act { display: flex; align-items: center; gap: 0.4rem; padding: 0.2rem 0.4rem; margin: 0.15rem 0; font-size: 0.8rem; color: #334155; border-radius: 4px; }
        .direct-view .mm-inline-act input { background: transparent; border: 1px solid transparent; border-radius: 3px; font-size: 0.8rem; flex: 1; min-width: 100px; padding: 0.2rem 0.35rem; }
        .direct-view .mm-inline-act input:hover { border-color: #e2e8f0; }
        .direct-view .mm-inline-act input:focus { outline: none; border-color: #3b82f6; background: #fff; }
        .direct-view .mm-param-row { display: flex; align-items: center; gap: 0.35rem; margin: 0.1rem 0 0.1rem 1.5rem; font-size: 0.75rem; }
        .direct-view .mm-param-row input { padding: 0.2rem 0.35rem; font-size: 0.75rem; border: 1px solid #e2e8f0; border-radius: 3px; }
        .direct-view .mm-param-row input:focus { outline: none; border-color: #3b82f6; }
        .direct-view .mm-param-row .col-tipo { width: 100px; }
        .direct-view .mm-param-row .col-param { width: 120px; }
        .direct-view .mm-param-row .col-unid { width: 50px; }
        .direct-view .mm-param-row .col-std { width: 55px; }

        /* Árbol PROCESO PRODUCTIVO: raíz + conectores + ramas */
        .arbol-pp { text-align: center; padding: 1.5rem; }
        .arbol-pp-root { display: inline-block; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 2px solid #1e40af; border-radius: 10px; padding: 0.75rem 1.5rem; font-weight: 700; font-size: 1.1rem; color: #1e40af; margin-bottom: 0; }
        .arbol-pp-trunk { display: flex; flex-direction: column; align-items: center; margin: 0 0 0.5rem 0; }
        .arbol-pp-connector-v { width: 2px; height: 24px; background: #3b82f6; flex-shrink: 0; }
        .arbol-pp-connector-h { height: 2px; background: #3b82f6; width: 85%; min-width: 200px; max-width: 800px; flex-shrink: 0; }
        .arbol-pp-branches { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.25rem; margin-top: 0; }
        .arbol-pp-branch { display: flex; flex-direction: column; align-items: center; flex: 0 0 auto; min-width: 160px; max-width: 240px; }
        .arbol-pp-branch-connector { width: 2px; height: 18px; background: #3b82f6; margin: 0 0 6px 0; flex-shrink: 0; }
        .arbol-pp-estandar-wrap { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); border: 2px solid #4f46e5; border-radius: 8px; padding: 0.45rem 0.9rem; font-weight: 600; font-size: 0.9rem; color: #4338ca; margin-bottom: 0; }
        .arbol-pp-estandar-trunk { display: flex; flex-direction: column; align-items: center; margin: 0.25rem 0; }
        .arbol-pp-estandar-connector-v { width: 2px; height: 12px; background: #6366f1; flex-shrink: 0; }
        .arbol-pp-estandar-connector-h { height: 2px; background: #6366f1; width: 95%; min-width: 80px; flex-shrink: 0; }
        .arbol-pp-estandar-subprocesos { display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; margin-top: 0.25rem; }
        .arbol-pp-sub { display: block; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 2px solid #1e40af; border-radius: 10px; padding: 0.5rem 0.75rem; font-weight: 600; font-size: 0.85rem; color: #1e40af; text-align: center; transition: all 0.2s; }
        .arbol-pp-sub:hover { background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%); box-shadow: 0 2px 8px rgba(30,64,175,.25); }
        .arbol-pp .arbol-pp-sub-wrap { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 2px solid #1e40af; border-radius: 10px; padding: 0.4rem 0.6rem; flex-wrap: wrap; }
        .arbol-pp-branch .mm-children { margin-left: 0; padding-left: 0; border-left: none; margin-top: 0.5rem; width: 100%; }
        #modalEstándares { overflow-y: auto; overflow-x: hidden; align-items: center; }
        #modalEstándares .modal-registro-inner { max-width: 96vw; width: 1100px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; margin: 1rem auto; }
        #modalEstándares .modal-registro-body { flex: 1; min-height: 0; overflow-y: auto; padding: 0; display: flex; flex-direction: column; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 py-6">
        <div id="arbolPrincipalContainer" class="bg-white rounded-xl shadow-md p-5 min-h-[400px]">
            <div id="arbolPrincipalToolbar" class="flex gap-2 mb-4" style="display: none;">
                <button type="button" id="btnGuardarPrincipal" class="btn-primary"><i class="fas fa-save"></i> Guardar</button>
                <button type="button" id="btnEliminarPrincipal" class="px-4 py-2 border border-red-300 rounded-lg text-red-700 hover:bg-red-50" style="display: none;"><i class="fas fa-trash-alt"></i> Eliminar</button>
            </div>
            <div id="arbolPrincipalRoot" class="direct-view"></div>
            <div id="arbolPrincipalEmpty" class="text-center py-12 text-gray-500">
                <i class="fas fa-spinner fa-spin text-4xl mb-3 opacity-50"></i>
                <p>Cargando árbol de subprocesos...</p>
            </div>
        </div>

        <!-- Modal Nuevo/Editar: vista tipo Mind Map -->
        <div id="modalEstándares" style="display: none;" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="modal-registro-inner bg-white rounded-2xl shadow-xl w-full flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 flex-shrink-0">
                    <h2 id="modalEstándaresTitle" class="text-xl font-bold text-gray-800">Nuevo Registro</h2>
                    <button type="button" id="btnCerrarModalEstandares" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">×</button>
                </div>
                <div class="modal-registro-body">
                    <input type="hidden" id="modalRegistroId" value="">
                    <div class="p-4 border-b border-gray-200 flex-shrink-0">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del estándar *</label>
                        <input type="text" id="modalInputNombre" class="form-control" placeholder="Nombre del estándar" style="max-width: 400px;">
                    </div>
                    <div class="map-toolbar flex-shrink-0">
                        <span class="text-sm text-gray-600 font-medium">Vista:</span>
                        <div class="view-toggle">
                            <button type="button" id="btnViewDirect" class="active" title="Editar directo en el árbol"><i class="fas fa-edit"></i> Directa</button>
                            <button type="button" id="btnViewMap" title="Vista Mapa (árbol + detalle)"><i class="fas fa-sitemap"></i> Mapa</button>
                            <button type="button" id="btnViewList" title="Vista Lista"><i class="fas fa-list"></i> Lista</button>
                        </div>
                    </div>
                    <!-- Vista Directa: árbol full-width con edición inline -->
                    <div id="directViewContainer" class="flex-1 min-h-0 overflow-y-auto">
                        <div id="directViewRoot" class="direct-view"></div>
                    </div>
                    <!-- Vista Mapa (árbol con drill-down) -->
                    <div id="mapViewContainer" style="display: none;" class="flex-1 flex flex-col min-h-0">
                        <div class="map-container flex-1 min-h-0">
                            <div class="map-tree-panel">
                                <div class="map-tree-root" id="mapTreeRoot"></div>
                            </div>
                            <div class="map-detail-panel">
                                <div id="mapBreadcrumb" class="map-breadcrumb"></div>
                                <div id="mapDetailContent"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Vista Lista (bloques tradicionales) -->
                    <div id="listViewContainer" style="display: none;" class="flex-1 overflow-y-auto p-4">
                        <div class="text-sm font-medium text-gray-700 mb-2">Subprocesos</div>
                        <div id="modalArbolSubprocesos"></div>
                        <button type="button" id="modalBtnAgregarSubproceso" class="btn-add-discreto mt-2"><i class="fas fa-plus"></i> Agregar subproceso</button>
                    </div>
                </div>
                <div class="flex gap-2 justify-end p-4 border-t border-gray-200 flex-shrink-0">
                    <button type="button" id="modalBtnCancelar" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="button" id="modalBtnGuardar" class="btn-primary"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>
    <datalist id="datalistSubproceso"></datalist>
    <datalist id="datalistActividad"></datalist>
    <datalist id="datalistTipo"></datalist>
    <datalist id="datalistParametro"></datalist>
    <datalist id="datalistUnidades"></datalist>
    <script>
    window.ESTANDARES_BASE_URL = '<?php echo rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") . "/"; ?>';
    </script>
    <script src="../../../assets/js/configuracion/estandares.js"></script>
</body>
</html>
