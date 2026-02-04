<?php

session_start();
if (empty($_SESSION['active'])) {
    echo '<script>
        if (window.top !== window.self) {
            window.top.location.href = "../../../login.php";
        } else {
            window.location.href = "../../../login.php";
        }
    </script>';
    exit();
}

include_once '../../../../conexion_grs_joya/conexion.php';
$conexion = conectar_joya();
if (!$conexion) {
    die("Error de conexión.");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tipo de Respuestas</title>
    <link rel="stylesheet" href="../../../css/output.css">
    <link rel="stylesheet" href="../../../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../../../css/dashboard-vista-tabla-iconos.css">
    <link rel="stylesheet" href="../../../css/dashboard-responsive.css">
    <style>
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

        .table-wrapper {
            overflow-x: auto;
            border-radius: 1rem;
        }

        #tablaRespuesta th,
        #tablaRespuesta td {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.875rem;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        #tablaRespuesta th {
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
            font-weight: 600;
            color: #ffffff !important;
        }

        #tablaRespuesta tbody tr:hover {
            background-color: #eff6ff !important;
        }

        /* Modal interno */
        #respuestasModal table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
        }

        #respuestasModal thead {
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
            color: white !important;
        }

        #respuestasModal th,
        #respuestasModal td {
            padding: 0.5rem 0.75rem;
            text-align: left;
            font-size: 0.875rem;
            border-bottom: 1px solid #e5e7eb;
        }

        #nuevaRespuestaForm {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem;
            margin: 1rem 0;
        }

        #noRespuestas {
            text-align: center;
            padding: 1rem;
            color: #64748b;
            font-style: italic;
            display: none;
        }

        /* ✅ Encabezado de controles de DataTables: fondo blanco */
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_length {
    background: white;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    color: #374151; /* gris oscuro */
}

.dataTables_wrapper .dataTables_filter label,
.dataTables_wrapper .dataTables_length label {
    font-weight: 500;
    margin: 0;
    color: #374151;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #d1d5db;
    color: #374151;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background: white;
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid #d1d5db;
    color: #374151;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background: white;
}

/* ✅ Cabecera de la tabla: azul con texto blanco */
#tablaRespuesta thead th {
    background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%) !important;
    color: white !important;
    font-weight: 600;
    padding: 0.75rem 1rem;
}
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-12">
        <div class="max-w-full mx-auto mt-6">
            <div id="tablaRespuestaWrapper" class="border border-gray-300 rounded-2xl bg-white overflow-hidden p-4" data-vista-tabla-iconos data-vista="">
                <div class="view-toggle-group flex items-center gap-2 mb-4">
                    <button type="button" class="view-toggle-btn active" id="btnViewTablaResp" title="Lista"><i class="fas fa-list mr-1"></i> Lista</button>
                    <button type="button" class="view-toggle-btn" id="btnViewIconosResp" title="Iconos"><i class="fas fa-th mr-1"></i> Iconos</button>
                </div>
                <div class="view-tarjetas-wrap px-4 pb-4 overflow-x-hidden" id="viewTarjetasResp">
                    <div id="cardsContainerResp" class="cards-grid cards-grid-iconos" data-vista-cards="iconos"></div>
                    <div id="cardsPaginationResp" class="flex items-center justify-between mt-4 text-sm text-gray-600 border-t border-gray-200 pt-3"></div>
                </div>
                <div class="view-lista-wrap table-wrapper">
                    <table id="tablaRespuesta" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT codigo, nombre FROM san_dim_analisis ORDER BY codigo ASC";
                            $result = mysqli_query($conexion, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['codigo']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                                    <td>
                                        <button
                                            type="button"
                                            class="p-2 rounded-lg text-blue-600 hover:text-blue-800 hover:bg-blue-100 transition"
                                            title="Ver respuestas"
                                            onclick="openRespuestasModal(<?= (int) $row['codigo'] ?>)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal CRUD de tiporesultado -->
        <div id="respuestasModal" style="display: none;"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-200">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="respuestasModalTitle" class="text-xl font-bold text-gray-800">Respuestas del análisis: <span id="codigoAnalisis"></span></h2>
                    <button onclick="closeRespuestasModal()" class="text-2xl text-gray-500 hover:text-gray-700">×</button>
                </div>

                <div class="flex-1 overflow-y-auto p-6" style="max-height: 60vh;">
                    <!-- Botón para añadir -->
                    <button type="button" class="btn-primary text-sm px-3 py-1.5 mb-4" onclick="openFormTipoResultado(null)">
                        ➕ Añadir respuesta
                    </button>

                    <!-- Formulario (crear/editar) -->
                    <div id="formTipoResultado" style="display: none;" class="mb-5 p-4 bg-gray-50 rounded-lg border">
                        <input type="hidden" id="editTipoResultadoCodigo" value="">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor de la respuesta</label>
                        <input type="text" id="tipoResultadoInput" class="form-control mb-2" placeholder="Ej: Positivo, 5.2 mg/dL..." required>
                        <div class="dashboard-modal-actions flex flex-wrap gap-2">
                            <button type="button" class="btn-outline text-sm" onclick="closeFormTipoResultado()">Cancelar</button>
                            <button type="button" class="btn-primary text-sm" onclick="guardarTipoResultado()">💾 Guardar</button>
                        </div>
                    </div>

                    <!-- Lista de respuestas -->
                    <div class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Valor</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="listaTipoResultados">
                                <!-- Se llenará con JS -->
                            </tbody>
                        </table>
                        <div id="noRespuestas">No hay respuestas registradas.</div>
                    </div>
                </div>

                <div class="border-t border-gray-200 p-6 bg-gray-50">
                    <button type="button" onclick="closeRespuestasModal()" class="btn-outline">Cerrar</button>
                </div>
            </div>
        </div>

        <!-- Footer dinámico -->
        <div class="text-center mt-12 mb-5">
            <p class="text-gray-500 text-sm">
                Sistema desarrollado para <strong>Granja Rinconada Del Sur S.A.</strong> -
                © <span id="currentYear"></span>
            </p>
        </div>

        <script>
            // Actualizar el año dinámicamente
            document.getElementById('currentYear').textContent = new Date().getFullYear();
        </script>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script>
           
            let currentAnalisisCodigo = null;

            function openRespuestasModal(codigo) {
                currentAnalisisCodigo = codigo;
                document.getElementById('codigoAnalisis').textContent = codigo;
                document.getElementById('respuestasModal').style.display = 'flex';
                cargarTipoResultados(codigo);
            }

            function closeRespuestasModal() {
                document.getElementById('respuestasModal').style.display = 'none';
                currentAnalisisCodigo = null;
            }

            function openFormTipoResultado(item = null) {
                const input = document.getElementById('tipoResultadoInput');
                const codigoInput = document.getElementById('editTipoResultadoCodigo');

                if (item) {
                    // Modo edición
                    codigoInput.value = item.codigo;
                    input.value = item.tipo;
                } else {
                    // Modo creación
                    codigoInput.value = '';
                    input.value = '';
                }
                document.getElementById('formTipoResultado').style.display = 'block';
                input.focus();
            }

            function closeFormTipoResultado() {
                document.getElementById('formTipoResultado').style.display = 'none';
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function cargarTipoResultados(codigo) {
                const tbody = document.getElementById('listaTipoResultados');
                const noRespuestas = document.getElementById('noRespuestas');

                tbody.innerHTML = '<tr><td colspan="2" class="text-center py-4">Cargando...</td></tr>';

                fetch('obtener_tiporesultados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'analisis=' + encodeURIComponent(codigo)
                })
                .then(r => r.json())
                .then(data => {
                    tbody.innerHTML = '';
                    if (data && data.length > 0) {
                        noRespuestas.style.display = 'none';
                        data.forEach(item => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="border-b py-2">${escapeHtml(item.tipo)}</td>
                                <td class="border-b py-2 text-right">
                                    <button
                                        title="Editar"
                                        class="text-blue-600 hover:text-blue-800 text-xs mr-2 inline-flex items-center gap-1 transition" 
                                        onclick="openFormTipoResultado(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                                        <i class="fa-solid fa-edit"></i> Editar
                                    </button>
                                    <button
                                        title="Eliminar"
                                        class="text-red-600 hover:text-red-800 text-xs inline-flex items-center gap-1 transition" 
                                        onclick="eliminarTipoResultado(${item.codigo})">
                                        <i class="fa-solid fa-trash"></i> Eliminar
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        noRespuestas.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error(err);
                    tbody.innerHTML = '<tr><td colspan="2" class="text-center py-4 text-red-500">Error al cargar respuestas</td></tr>';
                    noRespuestas.style.display = 'none';
                });
            }

            function guardarTipoResultado() {
                const codigo = document.getElementById('editTipoResultadoCodigo').value;
                const tipo = document.getElementById('tipoResultadoInput').value.trim();
                const analisis = currentAnalisisCodigo;

                if (!tipo) {
                    alert('El valor de la respuesta es obligatorio.');
                    return;
                }

                const data = new URLSearchParams();
                data.append('analisis', analisis);
                data.append('tipo', tipo);
                if (codigo) data.append('codigo', codigo);

                fetch('guardar_tiporesultado.php', {
                    method: 'POST',
                    body: data
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert(res.message);
                        closeFormTipoResultado();
                        cargarTipoResultados(analisis);
                    } else {
                        alert('Error: ' + res.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error al guardar la respuesta.');
                });
            }

            function eliminarTipoResultado(codigo) {
                if (!confirm('¿Eliminar esta respuesta?')) return;

                fetch('eliminar_tiporesultado.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'codigo=' + encodeURIComponent(codigo)
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert(res.message);
                        cargarTipoResultados(currentAnalisisCodigo);
                    } else {
                        alert('Error: ' + res.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error al eliminar la respuesta.');
                });
            }

          var tableRespuesta;
          function actualizarVistaInicialResp() {
              var w = $(window).width();
              var w$ = $('#tablaRespuestaWrapper');
              if (!w$.attr('data-vista')) {
                  w$.attr('data-vista', w < 768 ? 'iconos' : 'tabla');
                  $('#btnViewIconosResp').toggleClass('active', w$.attr('data-vista') === 'iconos');
                  $('#btnViewTablaResp').toggleClass('active', w$.attr('data-vista') === 'tabla');
              }
          }
          function renderizarTarjetasResp() {
              if (!tableRespuesta) return;
              var api = tableRespuesta;
              var cont = $('#cardsContainerResp');
              cont.empty();
              api.rows({ page: 'current' }).every(function() {
                  var $row = $(this.node());
                  var cells = $row.find('td');
                  if (cells.length < 2) return;
                  var codigo = $(cells[0]).text().trim();
                  var nombre = $(cells[1]).text().trim();
                  var codAttr = (codigo + '').replace(/"/g, '&quot;');
                  var card = $('<div class="card-item" data-codigo="' + codAttr + '">' +
                      '<div class="card-codigo">' + $('<div>').text(codigo).html() + '</div>' +
                      '<div class="card-row"><span class="label">Nombre:</span> ' + $('<div>').text(nombre).html() + '</div>' +
                      '<div class="card-acciones">' +
                      '<button type="button" class="btn-ver-resp-card p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition" title="Ver respuestas" data-codigo="' + codAttr + '"><i class="fa-solid fa-eye"></i> Ver respuestas</button>' +
                      '</div></div>');
                  cont.append(card);
              });
              var info = api.page.info();
              var pagHtml = '<span>Mostrando ' + (info.start + 1) + ' a ' + info.end + ' de ' + info.recordsDisplay + ' registros</span>' +
                  '<div class="flex gap-2"><button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page === 0 ? 'disabled' : '') + ' onclick="tableRespuesta && tableRespuesta.page(\'previous\').draw(false); renderizarTarjetasResp();">Anterior</button>' +
                  '<button type="button" class="px-3 py-1 rounded border border-gray-300 text-sm ' + (info.page >= info.pages - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100') + '" ' + (info.page >= info.pages - 1 ? 'disabled' : '') + ' onclick="tableRespuesta && tableRespuesta.page(\'next\').draw(false); renderizarTarjetasResp();">Siguiente</button></div>';
              $('#cardsPaginationResp').html(pagHtml);
          }
          tableRespuesta = $('#tablaRespuesta').DataTable({
              language: { url: '../../../assets/i18n/es-ES.json' },
              pageLength: 10,
              lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
              order: [[0, 'asc']],
              drawCallback: function() { renderizarTarjetasResp(); }
          });
          actualizarVistaInicialResp();
          $('#btnViewIconosResp').on('click', function() {
              $('#tablaRespuestaWrapper').attr('data-vista', 'iconos');
              $('#btnViewIconosResp').addClass('active');
              $('#btnViewTablaResp').removeClass('active');
          });
          $('#btnViewTablaResp').on('click', function() {
              $('#tablaRespuestaWrapper').attr('data-vista', 'tabla');
              $('#btnViewTablaResp').addClass('active');
              $('#btnViewIconosResp').removeClass('active');
          });
          $(window).on('resize', function() {
              if (!$('#tablaRespuestaWrapper').attr('data-vista')) return;
              actualizarVistaInicialResp();
          });
          $(document).on('click', '.btn-ver-resp-card', function() {
              var cod = $(this).attr('data-codigo');
              if (cod !== undefined) openRespuestasModal(isNaN(cod) ? cod : parseInt(cod, 10));
          });
        </script>
</body>

</html>