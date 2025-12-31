<?php

session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) {
            window.top.location.href = "../../login.php";
        } else {
            window.location.href = "../../login.php";
        }
    </script>';
    exit();
}

$usuario = $_SESSION['usuario'] ?? 'usuario';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Planificación de Muestreo</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <style>
    /* Tus estilos existentes */
    body {
      background: #f8f9fa;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .btn-primary {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
      border: none;
      padding: 0.625rem 1.5rem;
      font-size: 0.875rem;
      font-weight: 600;
      color: white;
      border-radius: 0.75rem;
      transition: all 0.2s ease;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 8px rgba(16, 185, 129, 0.4);
    }

    .btn-secondary {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
      border: none;
      padding: 0.625rem 1.5rem;
      font-size: 0.875rem;
      font-weight: 600;
      color: white;
      border-radius: 0.75rem;
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .btn-secondary:hover {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 8px rgba(59, 130, 246, 0.4);
    }

    .btn-export {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
      border: none;
      padding: 0.625rem 1.5rem;
      font-size: 0.875rem;
      font-weight: 600;
      color: white;
      border-radius: 0.75rem;
      transition: all 0.2s ease;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-export:hover {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      transform: translateY(-2px);
      box-shadow: 0 6px 8px rgba(16, 185, 129, 0.4);
    }

    .btn-outline {
      background: white;
      border: 1px solid #d1d5db;
      color: #374151;
      padding: 0.625rem 1.5rem;
      font-size: 0.875rem;
      font-weight: 600;
      border-radius: 0.75rem;
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .btn-outline:hover {
      background: #f3f4f6;
      border-color: #9ca3af;
    }

    .form-control {
      width: 100%;
      padding: 0.625rem 1rem;
      border: 1px solid #d1d5db;
      border-radius: 0.75rem;
      font-size: 0.875rem;
      transition: all 0.2s;
    }

    .form-control:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .card {
      background: white;
      border-radius: 1rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      border: 1px solid #e5e7eb;
      overflow: hidden;
    }

    .table-wrapper {
      overflow-x: auto;
      overflow-y: visible;
      width: 100%;
      border-radius: 1rem;
    }

    .table-wrapper::-webkit-scrollbar {
      height: 10px;
    }

    .table-wrapper::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 10px;
    }

    .table-wrapper::-webkit-scrollbar-thumb {
      background: #94a3b8;
      border-radius: 10px;
    }

    .table-wrapper::-webkit-scrollbar-thumb:hover {
      background: #64748b;
    }

    .data-table {
      width: 100% !important;
      border-collapse: collapse;
      min-width: 1200px;
    }

    .data-table th,
    .data-table td {
      padding: 0.75rem 1rem;
      text-align: left;
      font-size: 0.875rem;
      border-bottom: 1px solid #e5e7eb;
      white-space: nowrap;
    }

    .data-table th {
      background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
      font-weight: 600;
      color: #ffffff !important;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .data-table tbody tr:hover {
      background-color: #eff6ff !important;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
      padding: 1rem;
    }

    .dataTables_wrapper .dataTables_length select {
      padding: 0.5rem;
      border: 1px solid #d1d5db;
      border-radius: 0.5rem;
      margin: 0 0.5rem;
    }

    .dataTables_wrapper .dataTables_filter input {
      padding: 0.5rem 1rem;
      border: 1px solid #d1d5db;
      border-radius: 0.5rem;
      margin-left: 0.5rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
      padding: 0.5rem 1rem !important;
      margin: 0 0.25rem;
      border-radius: 0.5rem;
      border: 1px solid #d1d5db !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%) !important;
      color: white !important;
      border: 1px solid #1e40af !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: #eff6ff !important;
      color: #1d4ed8 !important;
    }

    table.dataTable thead .sorting:before,
    table.dataTable thead .sorting_asc:before,
    table.dataTable thead .sorting_desc:before,
    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:after {
      color: white !important;
    }

    .dataTables_wrapper {
      overflow-x: visible !important;
    }

    /* Modal fijo */
    .modal-xl {
      max-width: 1200px;
    }

    .calendar-container {
      background: white;
      border-radius: 20px;
      padding: 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      display: flex;
      flex-direction: column;
    }

    .month-nav {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }

    .btn-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      background: #f1f3f5;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      color: #495057;
    }

    .btn-icon:hover {
      background: #e9ecef;
      color: #4361ee;
    }

    .current-month-title {
      font-size: 20px;
      font-weight: 600;
      color: #2d3748;
      min-width: 160px;
      text-align: center;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 6px;
      flex: 1;
      overflow: hidden;
    }

    .calendar-day {
      min-height: 80px;
      border-radius: 8px;
      padding: 6px;
      background: #fff;
      border: 1px solid #e9ecef;
      cursor: default;
      font-size: 12px;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .calendar-day.today {
      border-color: #4361ee;
      background: #eef2ff;
    }

    .calendar-day.empty {
      background: #f8fafc;
      border: none;
      cursor: default;
    }

    .day-number {
      font-weight: 700;
      color: #212529;
    }

    .day-event {
      background: #4361ee;
      color: white;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 10px;
      cursor: pointer;
      position: relative;
    }

    .day-event:hover {
      background: #3a56d4;
    }

    .analisis-lista {
      margin-top: 4px;
      padding: 6px;
      background: #f0f4ff;
      border-radius: 4px;
      font-size: 10px;
      display: none;
      /* inicialmente oculto */
    }

    .analisis-item {
      margin: 2px 0;
      color: #2d3748;
    }

    /* === CALENDARIO MODAL === */
    .calendar-container {
      background: white;
      border-radius: 20px;
      padding: 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      display: flex;
      flex-direction: column;
    }

    .month-nav {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }

    .btn-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      background: #f1f3f5;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      color: #495057;
    }

    .btn-icon:hover {
      background: #e9ecef;
      color: #4361ee;
    }

    .current-month-title {
      font-size: 20px;
      font-weight: 600;
      color: #2d3748;
      min-width: 160px;
      text-align: center;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 4px;
      margin-top: 8px;
    }

    .cal-day-header {
      text-align: center;
      font-weight: bold;
      padding: 6px 0;
      font-size: 0.85rem;
      color: #6c757d;
    }

    .cal-day {
      min-height: 80px;
      border-radius: 8px;
      padding: 4px;
      background: #fff;
      border: 1px solid #e9ecef;
      font-size: 0.85rem;
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .cal-day.today {
      border-color: #4361ee;
      background: #f0f4ff;
    }

    .cal-day.empty {
      background: #fafafa;
      border-color: #f1f3f5;
      cursor: default;
    }

    .cal-day-number {
      font-weight: bold;
      text-align: right;
      padding-right: 4px;
      font-size: 0.9rem;
    }

    .cal-event {
      padding: 3px 6px;
      border-radius: 4px;
      font-size: 0.8rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: white;
      text-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
    }

    .cal-event .btn-plus {
      margin-left: 4px;
      font-size: 0.9rem;
      opacity: 0.9;
    }

    .cal-event .btn-plus:hover {
      opacity: 1;
    }

    /* Modal de análisis */
    .modal-analisis {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1050;
    }

    .modal-analisis-content {
      background: white;
      border-radius: 12px;
      padding: 20px;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .modal-analisis-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }

    .modal-analisis-header h6 {
      margin: 0;
      font-size: 1.1rem;
    }

    .modal-analisis-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #6c757d;
    }
  </style>
</head>

<body class="bg-light">

  <div class="container-fluid py-4">

    <!-- FILTROS -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-2">
            <label class="form-label">Fecha desde</label>
            <input type="date" class="form-control" id="fechaDesde">
          </div>
          <div class="col-md-2">
            <label class="form-label">Fecha hasta</label>
            <input type="date" class="form-control" id="fechaHasta">
          </div>
          <div class="col-md-2">
            <label class="form-label">Granja</label>
            <select id="filtroGranja" class="form-control">
              <option value="">Todas</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Galpón</label>
            <select id="filtroGalpon" class="form-control">
              <option value="">Todos</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Campaña</label>
            <select id="filtroCampania" class="form-control">
              <option value="">Todas</option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Edad</label>
            <select id="filtroEdad" class="form-control">
              <option value="">Todas</option>
            </select>
          </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
          <button class="btn-primary" id="btnAplicar">
            <i class="fas fa-filter"></i> Aplicar
          </button>
        </div>
      </div>
    </div>

    <!-- BOTONES -->
    <div class="d-flex gap-3 mb-4">
      <a href="#" id="btnExportar" class="btn-export">
        <i class="fas fa-file-excel"></i> Exportar a Excel
      </a>
      <button class="btn-primary" data-bs-toggle="modal" data-bs-target="#modalPlanificacion">
        <i class="fas fa-plus"></i> Nueva Planificación
      </button>
    </div>

    <!-- TABLA -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-wrapper">
          <table id="tablaPlanificacion" class="data-table">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Granja</th>
                <th>Nombre</th>
                <th>Campaña</th>
                <th>Galpón</th>
                <th>Edad</th>
                <th>Análisis</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>

        </div>


      </div>
    </div>

  </div>
  <!--div class="d-flex gap-3 mb-4"-->
    <!-- Tus otros botones -->
    <button class="btn-secondary" id="btnVerHorario">
      <i class="fas fa-calendar-alt"></i> Ver Horario
    </button>
    <!-- CALENDARIO (sin modal) -->
    <div id="seccionCalendario" class="card mt-5" style="display:none;">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Horario de Planificación</h5>
        <button class="btn btn-sm btn-outline-secondary" id="btnCerrarCalendario">Cerrar</button>
      </div>
      <div class="card-body p-0">
        <div class="calendar-container">
          <div class="month-nav">
            <button class="btn-icon" id="calPrev">&lt;</button>
            <div class="current-month-title" id="calTitle">Junio 2025</div>
            <button class="btn-icon" id="calNext">&gt;</button>
          </div>
          <div class="calendar-grid" id="calGrid"></div>
        </div>

        <!-- CONTENEDOR DE DETALLES -->
        <div id="contenedorDetalles" class="p-4"
          style="display:none; border-top: 1px solid #e9ecef; background: #f8fafc;">
          <h6 class="mb-3">Detalles del Evento</h6>
          <div id="detallesContenido"></div>
        </div>
      </div>
    </div>
  <!-- /div->



  <!-- MODAL PLANIFICACIÓN ACTUALIZADO -->
  <div class="modal fade" id="modalPlanificacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Nueva Planificación</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
          <!-- Fila 1: Granjas y Edades -->
          <div class="row g-3 mb-4">

            <div class="col-md-6">
              <label class="form-label">Granja(s)</label>
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="selectAllGranjas">
                <label class="form-check-label" for="selectAllGranjas">Seleccionar todas</label>
              </div>
              <select id="granjasPlan" class="form-control" multiple size="6"></select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Año</label> <!-- ✅ NUEVO -->
              <select id="anioPlan" class="form-control">
                <option value="">Seleccione año</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Edad(es)</label>
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="selectAllEdades">
                <label class="form-check-label" for="selectAllEdades">Seleccionar todas</label>
              </div>
              <select id="edadesPlan" class="form-control" multiple size="6">
                <option value="00">0 POLLO BEBE</option>
                <?php for ($e = 1; $e <= 45; $e++): ?>
                  <option value="<?= str_pad($e, 2, '0', STR_PAD_LEFT) ?>"><?= $e ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>

          <!-- Fila 2: Tipo de muestra + Análisis -->
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Tipo(s) de muestra</label>
              <select id="tiposMuestraPlan" class="form-control" multiple size="8"></select>
            </div>
            <div class="col-md-8">
              <label class="form-label">Análisis por tipo de muestra</label>
              <div id="analisisContainer" class="border rounded p-3"
                style="background: #f9fafb; max-height: 300px; overflow-y: auto;">
                <p class="text-muted mb-0">Seleccione uno o más tipos de muestra para ver sus análisis.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn-primary" id="btnGuardarPlan">Guardar Planificación</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
  <script>
    let analisisSeleccionados = {};
    let analisisSeleccionadosPorTipo = {};
    let tablaPlanificacion;
    // Cargar granjas en filtros y modal
    async function cargarGranjas() {
      const res = await fetch('get_granjas.php');
      const data = await res.json();
      const opt = d => `<option value="${d.codigo}">${d.codigo} - ${d.nombre}</option>`;
      $('#filtroGranja, #granjasPlan').html('<option value="">Seleccione</option>' + data.map(opt).join(''));
    }

    // Cargar campañas por granja
    $('#filtroGranja, #granjasPlan').on('change', async function () {
      let granjas = $(this).val();
      if (!Array.isArray(granjas)) granjas = [granjas];
      if (!granjas[0]) return;
      const res = await fetch(`get_campanias.php?granja=${granjas[0]}`);
      const data = await res.json();
      const opt = c => `<option value="${c}">${c}</option>`;
      $('#filtroCampania, #campaniasPlan').html('<option value="">Seleccione</option>' + data.map(opt).join(''));
    });

    // Cargar galpones por granja
    $('#filtroGranja, #granjasPlan').on('change', async function () {
      let granjas = $(this).val();
      if (!Array.isArray(granjas)) granjas = [granjas];
      if (!granjas[0]) return;
      const res = await fetch(`get_galpones.php?granja=${granjas[0]}`);
      const data = await res.json();
      const opt = g => `<option value="${g}">${g}</option>`;
      $('#filtroGalpon, #galponesPlan').html('<option value="">Seleccione</option>' + data.map(opt).join(''));
    });

    // Cargar edades
    $(document).ready(function () {
      const edades = [];
      for (let e = 1; e <= 45; e++) {
        edades.push(`<option value="${String(e).padStart(2, '0')}">${e}</option>`);
      }
      $('#filtroEdad').html('<option value="">Todas</option>' + edades.join(''));
    });

    // Cargar tipos de muestra en modal
    /*$('#modalPlanificacion').on('shown.bs.modal', async () => {
      if ($('#tiposMuestraPlan option').length > 1) return;
      const res = await fetch('get_tipos_muestra.php');
      const data = await res.json();
      const opt = t => `<option value="${t.codigo}">${t.nombre}</option>`;
      $('#tiposMuestraPlan').html(data.map(opt).join(''));
    });*/
    $('#modalPlanificacion').on('shown.bs.modal', async () => {
      if ($('#tiposMuestraPlan option').length > 1) return; // ya cargado

      try {
        const res1 = await fetch('../../includes/get_tipos_muestra.php');
        const tipos = await res1.json();
        $('#tiposMuestraPlan').html(tipos.map(t =>
          `<option value="${t.codigo}">${t.nombre}</option>`
        ).join(''));
        const res2 = await fetch('get_years_disponibles.php');
        const anios = await res2.json();
        const opciones = anios.map(a => `<option value="${a}">${a}</option>`).join('');
        $('#anioPlan').html('<option value="">Seleccione año</option>' + opciones);
      } catch (err) {
        console.error("Error al cargar tipos de muestra:", err);
        $('#tiposMuestraPlan').html('<option>Error al cargar</option>');
      }
    });

    // Cargar análisis cuando cambian tipos
    /*$('#tiposMuestraPlan').on('change', async function () {
      const tipos = $(this).val() || [];
      let html = '';
      for (const tipo of tipos) {
        const res = await fetch(`get_config_muestra.php?tipo=${tipo}`);
        const data = await res.json();
        const paquetes = {};
        data.analisis.forEach(a => {
          if (a.paquete) {
            if (!paquetes[a.paquete]) paquetes[a.paquete] = { nombre: '', analisis: [] };
            paquetes[a.paquete].analisis.push(a);
          }
        });
        html += `<h6>${data.tipo_muestra.nombre}</h6>`;
        Object.values(paquetes).forEach(p => {
          html += `<div class="mb-2"><strong>${p.nombre}</strong><br>${p.analisis.map(a =>
            `<label><input type="checkbox" class="analisis-check" data-tipo="${tipo}" value="${a.codigo}"> ${a.nombre}</label>`
          ).join('<br>')}</div>`;
        });
      }
      $('#analisisContainer').html(html);
    });*/
    $('#tiposMuestraPlan').on('change', async function () {
      const tiposSeleccionados = $(this).val() || [];

      // Limpiar selecciones de tipos no elegidos
      for (const tipo in analisisSeleccionadosPorTipo) {
        if (!tiposSeleccionados.includes(tipo)) {
          delete analisisSeleccionadosPorTipo[tipo];
        }
      }

      if (tiposSeleccionados.length === 0) {
        $('#analisisContainer').html('<p class="text-muted mb-0">Seleccione uno o más tipos de muestra.</p>');
        return;
      }

      let htmlCompleto = '';

      for (const tipoId of tiposSeleccionados) {
        try {
          const res = await fetch(`../../includes/get_config_muestra.php?tipo=${tipoId}`);
          const data = await res.json();

          if (data.error) throw new Error(data.error);

          // Agrupar análisis por paquete
          const analisisPorPaquete = {};
          const analisisSinPaquete = [];
          data.analisis.forEach(a => {
            if (a.paquete) {
              if (!analisisPorPaquete[a.paquete]) analisisPorPaquete[a.paquete] = [];
              analisisPorPaquete[a.paquete].push(a);
            } else {
              analisisSinPaquete.push(a);
            }
          });

          // Conjunto de análisis ya seleccionados para este tipo
          const seleccionados = new Set(
            (analisisSeleccionadosPorTipo[tipoId] || []).map(a => String(a.codigo))
          );

          // HTML por tipo
          htmlCompleto += `<div class="mb-4"><h6 class="fw-bold border-bottom pb-2">${data.tipo_muestra.nombre}</h6>`;

          // Paquetes
          data.paquetes.forEach(p => {
            const analisisDelPaquete = analisisPorPaquete[p.codigo] || [];
            const todosSeleccionados = analisisDelPaquete.length > 0 &&
              analisisDelPaquete.every(a => seleccionados.has(String(a.codigo)));

            htmlCompleto += `
          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input paquete-check" type="checkbox" 
                data-tipo="${tipoId}" data-paquete="${p.codigo}" ${todosSeleccionados ? 'checked' : ''}>
              <label class="form-check-label fw-bold">${p.nombre}</label>
            </div>
            <div class="ms-4 mt-2">
              ${analisisDelPaquete.map(a => `
                <div class="form-check form-check-inline me-3">
                  <input class="form-check-input analisis-check" type="checkbox"
                    data-tipo="${tipoId}" data-paquete="${p.codigo}" value="${a.codigo}"
                    ${seleccionados.has(String(a.codigo)) ? 'checked' : ''}>
                  <label class="form-check-label">${a.nombre}</label>
                </div>
              `).join('')}
            </div>
          </div>
        `;
          });

          // Análisis sin paquete
          if (analisisSinPaquete.length > 0) {
            htmlCompleto += `
          <div class="mt-3 pt-2 border-top">
            <h6 class="fw-bold">Otros análisis</h6>
            ${analisisSinPaquete.map(a => `
              <div class="form-check form-check-inline me-3">
                <input class="form-check-input analisis-check" type="checkbox"
                  data-tipo="${tipoId}" value="${a.codigo}"
                  ${seleccionados.has(String(a.codigo)) ? 'checked' : ''}>
                <label class="form-check-label">${a.nombre}</label>
              </div>
            `).join('')}
          </div>
        `;
          }

          htmlCompleto += `</div>`;
        } catch (err) {
          console.error("Error al cargar análisis para tipo", tipoId, err);
          htmlCompleto += `<div class="alert alert-danger">Error al cargar análisis para tipo ${tipoId}</div>`;
        }
      }

      $('#analisisContainer').html(htmlCompleto || '<p class="text-muted">Sin análisis disponibles.</p>');

      // === Eventos: checkboxes de análisis ===
      $('.analisis-check').off('change').on('change', function () {
        const tipo = $(this).data('tipo');
        const codigo = $(this).val();
        const nombre = $(this).siblings('label').text();
        const paquete = $(this).data('paquete') || null;

        if (!analisisSeleccionadosPorTipo[tipo]) analisisSeleccionadosPorTipo[tipo] = [];

        if (this.checked) {
          // Evitar duplicados
          if (!analisisSeleccionadosPorTipo[tipo].some(a => a.codigo == codigo)) {
            analisisSeleccionadosPorTipo[tipo].push({ codigo, nombre, paquete });
          }
        } else {
          analisisSeleccionadosPorTipo[tipo] = analisisSeleccionadosPorTipo[tipo].filter(a => a.codigo != codigo);
        }

        // Actualizar checkbox de paquete
        const paqueteId = $(this).data('paquete');
        if (paqueteId) {
          const paqueteChecks = $(`.analisis-check[data-tipo="${tipo}"][data-paquete="${paqueteId}"]`);
          const todos = paqueteChecks.length > 0 && paqueteChecks.toArray().every(cb => cb.checked);
          $(`.paquete-check[data-tipo="${tipo}"][data-paquete="${paqueteId}"]`).prop('checked', todos);
        }
      });

      // === Eventos: checkboxes de paquete ===
      $('.paquete-check').off('change').on('change', function () {
        const tipo = $(this).data('tipo');
        const paqueteId = $(this).data('paquete');
        const checked = this.checked;

        const analisisChecks = $(`.analisis-check[data-tipo="${tipo}"][data-paquete="${paqueteId}"]`);
        analisisChecks.prop('checked', checked).trigger('change');
      });
    });

    $('#btnGuardarPlan').on('click', async () => {
      const granjas = $('#granjasPlan').val() || [];
      const edades = $('#edadesPlan').val() || [];
      const year = $('#anioPlan').val();
      const tipos = Object.keys(analisisSeleccionadosPorTipo).filter(t =>
        analisisSeleccionadosPorTipo[t].length > 0
      );

      if (granjas.length === 0 || edades.length === 0 || !year || tipos.length === 0) {
        alert("Por favor, complete todos los campos y seleccione al menos un análisis.");
        return;
      }

      // Preparar datos para el backend
      const datos = {
        granjas,
        edades,
        year,
        analisisPorTipo: analisisSeleccionadosPorTipo,
        usuario: "<?= $_SESSION['usuario'] ?? 'usuario' ?>"
      };

      try {
        const res = await fetch('guardar_planificacion.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(datos)
        });
        const result = await res.json();

        if (result.success) {
          alert("Planificación guardada exitosamente.");
          $('#modalPlanificacion').modal('hide');
          // Recargar tabla si usas DataTables
          if (typeof tablaPlanificacion !== 'undefined') {
            tablaPlanificacion.ajax.reload();
          }
        } else {
          alert("Error: " + (result.message || 'Desconocido'));
        }
      } catch (err) {
        console.error(err);
        alert("Error al guardar. Verifique la consola.");
      }
    });

    // DataTable
    $(document).ready(function () {
      cargarGranjas();
      tablaPlanificacion = $('#tablaPlanificacion').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
          "url": "listar_planificacion.php",
          "data": function (d) {
            d.fecha_desde = $('#fechaDesde').val();
            d.fecha_hasta = $('#fechaHasta').val();
            d.granja = $('#filtroGranja').val();
            d.campania = $('#filtroCampania').val();
            d.galpon = $('#filtroGalpon').val();
            d.edad = $('#filtroEdad').val();
          }
        },
        "columns": [
          { "data": "fecha" },
          { "data": "granja" },
          { "data": "nombreGranja" },
          { "data": "campania" },
          { "data": "galpon" },
          { "data": "edad" },
          { "data": "analisisResumen" }
        ],
        "pageLength": 25,
        "language": {
          "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
      });

      $('#btnAplicar').on('click', () => tablaPlanificacion.ajax.reload());

      $('#selectAllEdades').on('change', function () {
        $('#edadesPlan option').prop('selected', this.checked);
        $('#edadesPlan').trigger('change');
      });

      // Granjas (similar)
      $('#selectAllGranjas').on('change', function () {
        $('#granjasPlan option').prop('selected', this.checked);
        $('#granjasPlan').trigger('change');
      });
      $('#btnExportar').on('click', function (e) {
        e.preventDefault();
        const baseUrl = 'exportar_planificacion.php';
        const params = new URLSearchParams();

        const fdesde = $('#fechaDesde').val();
        const fhasta = $('#fechaHasta').val();
        const granja = $('#filtroGranja').val();
        const camp = $('#filtroCampania').val();
        const galp = $('#filtroGalpon').val();
        const edad = $('#filtroEdad').val();

        if (fdesde) params.append('fecha_desde', fdesde);
        if (fhasta) params.append('fecha_hasta', fhasta);
        if (granja) params.append('granja', granja);
        if (camp) params.append('campania', camp);
        if (galp) params.append('galpon', galp);
        if (edad) params.append('edad', edad);

        window.location.href = baseUrl + (params.toString() ? '?' + params.toString() : '');
      });
      // === VARIABLES GLOBALES ===
      let fechaCalendario = new Date();
      let primerMesConRegistros = null;

      // === ABRIR CALENDARIO ===
      $('#btnVerHorario').on('click', async function () {
        const filtros = obtenerFiltrosActuales();

        // Determinar mes inicial
        let mesInicial = new Date();

        if (filtros.fecha_desde) {
          // Usar el mes del filtro de fecha inicio
          mesInicial = new Date(filtros.fecha_desde);
        } else if (primerMesConRegistros) {
          // Usar el primer mes con registros
          mesInicial = new Date(primerMesConRegistros);
        }

        $('#seccionCalendario').show();
        await cargarCalendario(mesInicial, filtros);
      });

      $('#btnCerrarCalendario').on('click', function () {
        $('#seccionCalendario').hide();
        $('#contenedorDetalles').hide();
      });

      // === OBTENER FILTROS ACTUALES ===
      function obtenerFiltrosActuales() {
        return {
          fecha_desde: $('#fechaDesde').val(),
          fecha_hasta: $('#fechaHasta').val(),
          granja: $('#filtroGranja').val(),
          campania: $('#filtroCampania').val(),
          galpon: $('#filtroGalpon').val(),
          edad: $('#filtroEdad').val()
        };
      }

      // === CARGAR CALENDARIO ===
      async function cargarCalendario(fecha, filtros = {}) {
        fechaCalendario = fecha;
        const year = fecha.getFullYear();
        const month = fecha.getMonth() + 1;

        // Rango del mes
        const inicioMes = `${year}-${String(month).padStart(2, '0')}-01`;
        const ultimoDia = new Date(year, month, 0).getDate();
        const finMes = `${year}-${String(month).padStart(2, '0')}-${String(ultimoDia).padStart(2, '0')}`;

        // Determinar rango de fechas
        const fechaDesde = filtros.fecha_desde || inicioMes;
        const fechaHasta = filtros.fecha_hasta || finMes;

        const params = new URLSearchParams({ fecha_desde: fechaDesde, fecha_hasta: fechaHasta });
        if (filtros.granja) params.append('granja', filtros.granja);
        if (filtros.campania) params.append('campania', filtros.campania);
        if (filtros.galpon) params.append('galpon', filtros.galpon);
        if (filtros.edad) params.append('edad', filtros.edad);

        try {
          const res = await fetch(`listar_planificacion.php?${params.toString()}`);
          const data = await res.json();

          // Guardar el primer mes si no lo tenemos
          if (!primerMesConRegistros && data.data.length > 0) {
            const primeraFecha = data.data[data.data.length - 1].fecha; // más antigua
            primerMesConRegistros = primeraFecha;
          }

          // Agrupar por fecha
          const eventosPorFecha = {};
          data.data.forEach(item => {
            const fecha = item.fecha;
            const evento = {
              codRef: item.granja + item.campania + item.galpon + item.edad,
              analisis: item.analisisResumen,
              granja: item.granja,
              campania: item.campania,
              galpon: item.galpon,
              edad: item.edad,
              nombreGranja: item.nombreGranja
            };
            if (!eventosPorFecha[fecha]) eventosPorFecha[fecha] = [];
            eventosPorFecha[fecha].push(evento);
          });

          renderizarCalendario(fecha, eventosPorFecha);
        } catch (err) {
          console.error("Error al cargar calendario:", err);
          $('#calGrid').html('<div class="col-12 text-center text-danger py-3">Error al cargar datos</div>');
        }
      }

      // === RENDERIZAR CALENDARIO ===
      function renderizarCalendario(fecha, eventosPorFecha) {
        const year = fecha.getFullYear();
        const month = fecha.getMonth() + 1;
        const primerDia = new Date(year, month - 1, 1);
        const ultimoDia = new Date(year, month, 0);
        const inicioSemana = new Date(primerDia);
        inicioSemana.setDate(primerDia.getDate() - primerDia.getDay());

        const grid = $('#calGrid');
        grid.html('');

        // Cabeceras
        ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'].forEach(dia => {
          grid.append(`<div class="cal-day-header">${dia}</div>`);
        });

        const hoy = new Date();
        for (let d = new Date(inicioSemana); d <= ultimoDia; d.setDate(d.getDate() + 1)) {
          const esDelMes = d.getMonth() === month - 1;
          const esHoy = d.toDateString() === hoy.toDateString();
          const fechaStr = d.toISOString().split('T')[0];

          let clase = 'cal-day';
          if (!esDelMes) clase += ' empty';
          if (esHoy) clase += ' today';

          let html = `<div class="${clase}">
      <div class="cal-day-number">${d.getDate()}</div>`;

          if (esDelMes && eventosPorFecha[fechaStr]) {
            eventosPorFecha[fechaStr].forEach((evento, index) => {
              const color = generarColor(evento.granja);
              // Guardar el índice del evento para referenciarlo
              html += `
          <div class="cal-event" style="background: ${color}" 
               data-fecha="${fechaStr}" 
               data-index="${index}">
            ${evento.codRef}
            <span class="btn-plus">+</span>
          </div>`;
            });
          }

          html += '</div>';
          grid.append(html);
        }

        // Título
        const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        $('#calTitle').text(`${meses[month - 1]} ${year}`);

        // Eventos de navegación
        $('#calPrev').off('click').on('click', () => {
          fechaCalendario.setMonth(fechaCalendario.getMonth() - 1);
          cargarCalendario(fechaCalendario, obtenerFiltrosActuales());
        });

        $('#calNext').off('click').on('click', () => {
          fechaCalendario.setMonth(fechaCalendario.getMonth() + 1);
          cargarCalendario(fechaCalendario, obtenerFiltrosActuales());
        });

        // Evento para mostrar detalles
        $('.cal-event').off('click').on('click', function () {
          const fecha = $(this).data('fecha');
          const index = $(this).data('index');
          const eventos = eventosPorFecha[fecha];
          const evento = eventos[index];

          // Mostrar detalles
          $('#detallesContenido').html(`
      <div class="row">
        <div class="col-md-6">
          <strong>Granja:</strong> ${evento.granja} - ${evento.nombreGranja}<br>
          <strong>Campaña:</strong> ${evento.campania}<br>
          <strong>Galpón:</strong> ${evento.galpon}<br>
          <strong>Edad:</strong> ${evento.edad}
        </div>
        <div class="col-md-6">
          <strong>Análisis:</strong><br>
          <small>${evento.analisis.replace(/, /g, '<br>')}</small>
        </div>
      </div>
    `);
          $('#contenedorDetalles').show();
        });
      }

      // === FUNCIONES AUXILIARES ===
      function generarColor(texto) {
        if (!texto) return '#6c757d';
        let hash = 0;
        for (let i = 0; i < texto.length; i++) {
          hash = texto.charCodeAt(i) + ((hash << 5) - hash);
        }
        let color = '#';
        for (let i = 0; i < 3; i++) {
          const value = (hash >> (i * 8)) & 0xFF;
          color += ('00' + value.toString(16)).substr(-2);
        }
        // Asegurar contraste
        const r = parseInt(color.substr(1, 2), 16);
        const g = parseInt(color.substr(3, 2), 16);
        const b = parseInt(color.substr(5, 2), 16);
        const luminancia = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        if (luminancia > 0.7) {
          color = `#${Math.max(0, r - 50).toString(16).padStart(2, '0')}${Math.max(0, g - 50).toString(16).padStart(2, '0')}${Math.max(0, b - 50).toString(16).padStart(2, '0')}`;
        }
        return color;
      }
    });
  </script>

</body>

</html>