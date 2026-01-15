<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);

    date_default_timezone_set('America/Lima');

    ob_start();
    include_once '../../../conexion_grs_joya/conexion.php';
    $conn = conectar_joya();
    ob_end_clean();

    if (!$conn) {
        die('Error de conexión a la base de datos');
    }

    // Obtener parámetros
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '1';
    $numreg = isset($_GET['numreg']) ? $_GET['numreg'] : '';
    $granja = isset($_GET['granja']) ? $_GET['granja'] : '';
    $galpon = isset($_GET['galpon']) ? $_GET['galpon'] : '';
    $fectra = isset($_GET['fectra']) ? $_GET['fectra'] : '';

    if (empty($numreg) || empty($granja) || empty($galpon) || empty($fectra)) {
        die('Parámetros incompletos');
    }

    $sql = "SELECT * 
    FROM t_regnecropsia 
    WHERE tnumreg = ? AND tgranja = ? AND tgalpon = ? AND tfectra = ?
    ORDER BY 
        tdate DESC,
        tnumreg DESC,
        tgranja ASC,
        tid ASC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die('Error preparando consulta: ' . $conn->error);
    }

    $stmt->bind_param("isss", $numreg, $granja, $galpon, $fectra);
    $stmt->execute();
    $result = $stmt->get_result();

    $registros = [];
    while ($row = $result->fetch_assoc()) {
        $registros[] = $row;
    }

    $stmt->close();
    $conn->close();

    if (empty($registros)) {
        die('No se encontraron registros');
    }

    $cabecera = $registros[0];

    require_once '../../vendor/autoload.php'; 

    use Mpdf\Mpdf;

    // === Logo ===
    $logoPath = __DIR__ . '/logo.png';
   /* $logo = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
    }*/

    try {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            // Dar más espacio al pie para que la paginación se vea siempre
            'margin_bottom' => 25,
            'margin_footer' => 10,
            'tempDir' => __DIR__ . '/../../pdf_tmp',
        ]);

        // Paginación: se define dentro del HTML con <htmlpagefooter> y @page { footer: html_... }


        $html = '';
        
        if ($tipo == '1') {
            $html = _generarReporte1($cabecera, $registros);
        } else {
        
            $html = _generarReportePDF($cabecera, $registros);
        }

        $mpdf->WriteHTML($html);
        $mpdf->Output('reporte_necropsia_' . $numreg . '_' . $tipo . '.pdf', 'I');
        
    } catch (Exception $e) {
        die('Error generando PDF: ' . $e->getMessage());
    }

    function _generarReporte1($cabecera, $registros) {
        // N°Reg: tomar desde la cabecera (BD) y si no existe, fallback a GET
        $numregLocal = $cabecera['tnumreg'] ?? ($_GET['numreg'] ?? '');

        // Logo
        $logoPath = __DIR__ . '/logo.png';
        $logo = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
            $logo = '<img src="' . htmlspecialchars($logoBase64) . '" style="height: 20px; vertical-align: top;">';
        }
        
        // HTML
        $html = '<html><head><meta charset="UTF-8"><style>
         @page {
    margin-top: 15mm;
    margin-bottom: 28mm; /* deja espacio para el footer/paginado */
    margin-left: 15mm;
    margin-right: 15mm;
    footer: html_myfooter;
}
body {
    font-family: Arial, sans-serif;
    font-size: 9pt;
    color: #000;
    margin: 0;
    padding: 0;
    position: relative;
}
            .header-title {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid #cbd5e1;
                margin-top: 20px;
            }
            .header-title td {
                padding: 5px;
            }
            .info-table {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
                font-size: 9pt;
                border: 1px solid #cbd5e1;
                border-top: none;
            }
            .info-table th,
            .info-table td {
                padding: 5px;
                border: 1px solid #cbd5e1;
                text-align: center;
                font-weight: normal;
            }
            .info-table th {
                background-color: #e6f2ff;
                font-weight: bold;
                width: 15%;
            }
            table.data-table {
                width: 100%;
                border-collapse: collapse;
                margin: 8px 0;
                font-size: 9pt;
                border-top: 1px solid #cbd5e1;
                page-break-inside: auto;
            }
            tr {
                page-break-inside: auto;
            }
            th {
                background-color: #e6f2ff;
                padding: 5px;
                text-align: center;
                border: 1px solid #cbd5e1;
                font-weight: bold;
            }
            td {
                padding: 5px;
                border: 1px solid #cbd5e1;
                vertical-align: top;
            }
            .nivel-cell {
                background-color: #e6f2ff;
                font-weight: bold;
                text-align: center;
                padding: 5px;
                border: 1px solid #cbd5e1;
                vertical-align: middle;
                width: 15%; /* ← ¡Ancho reducido! */
            }
            .param-cell {
                width: 40%;
            }
            .porc-cell {
                width: 10%;
                text-align: center;
            }
            .obs-cell {
                width: 35%;
            }
        </style></head><body>
        <htmlpagefooter name="myfooter">
            <div style="text-align:center; font-size:8pt;">
                Página {PAGENO} de {nbpg}
            </div>
        </htmlpagefooter>';
    
        // Fecha de elaboración del reporte (fuera de la tabla, arriba a la derecha)
        $fechaElaboracion = date('d/m/Y H:i');
        $html .= '<div style="text-align: right; font-size: 8pt; font-style: italic; margin-top: 6px; margin-bottom: 4px;">';
        $html .= htmlspecialchars($fechaElaboracion);
        $html .= '</div>';

        // === Cabecera conjunta ===
        $html .= '<table width="100%" style="border-collapse: collapse; border: 1px solid #cbd5e1; margin-top: 10px; margin-bottom: 8px;">';
        $html .= '<tr>';
        if (!empty($logo)) {
            $html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">';
            $html .= $logo . ' GRANJA RINCONADA DEL SUR S.A.';
            $html .= '</td>';
        } else {
            $html .= '<td style="width: 20%; text-align: left; padding: 5px; background-color: #fff; font-size: 8pt; white-space: nowrap; border: 1px solid #cbd5e1;">';
            $html .= 'GRANJA RINCONADA DEL SUR S.A.';
            $html .= '</td>';
        }
        $html .= '<td style="width: 60%; text-align: center; padding: 5px; background-color: #e6f2ff; color: #000; font-weight: bold; font-size: 14px; border: 1px solid #cbd5e1;">';
        $html .= 'REPORTE DE NECROPSIA';
        $html .= '</td>';
        // Esquina superior derecha: N°Reg
        $html .= '<td style="width: 20%; text-align: right; padding: 5px; background-color: #fff; border: 1px solid #cbd5e1; font-size: 8pt; white-space: nowrap;">';
        $html .= '<div><strong>N°Reg:</strong> ' . htmlspecialchars($numregLocal) . '</div>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
    
        // === Tabla de información ===
        $html .= '<table class="info-table">';
        $html .= '<tr>
            <th>Fecha</th>
            <th>Granja</th>
            <th>Campaña</th>
            <th>Galpón</th>
            <th>Edad</th>
        </tr>';
        $html .= '<tr>
            <td>' . htmlspecialchars($cabecera['tdate'] ?? '') . '</td>
            <td>' . htmlspecialchars($cabecera['tgranja'] ?? '') . '</td>
            <td>' . htmlspecialchars($cabecera['tcampania'] ?? '') . '</td>
            <td>' . htmlspecialchars($cabecera['tgalpon'] ?? '') . '</td>
            <td>' . htmlspecialchars($cabecera['tedad'] ?? '') . ' días</td>
        </tr>';
        $html .= '</table>';
    
        // Agrupar por sistema
        $porSistema = [];
        foreach ($registros as $reg) {
            $sistema = $reg['tsistema'] ?? '';
            if (!isset($porSistema[$sistema])) {
                $porSistema[$sistema] = [];
            }
            $porSistema[$sistema][] = $reg;
        }
    
        foreach ($porSistema as $sistema => $regs) {
            if (empty(trim($sistema))) continue;
    
            $html .= '<table class="data-table">';
            // Encabezado del sistema
            $html .= '<tr><th colspan="4" style="background-color: #e6f2ff; font-weight: bold; text-align: left; padding: 5px 8px;">' . htmlspecialchars(strtoupper($sistema)) . '</th></tr>';
            
            // Cabeceras
            $html .= '<tr>
                <th>Nivel</th>
                <th>Parámetro</th>
                <th>%</th>
                <th>Observaciones</th>
            </tr>';
    
            // Agrupar por nivel
            $porNivel = [];
            foreach ($regs as $r) {
                $nivel = $r['tnivel'] ?? '';
                if (!isset($porNivel[$nivel])) {
                    $porNivel[$nivel] = [];
                }
                $porNivel[$nivel][] = $r;
            }
    
            foreach ($porNivel as $nivel => $items) {
                $rowspan = count($items);
                
                // Obtener la observación del nivel (usamos la primera)
                $observacionNivel = '';
                if (!empty($items)) {
                    $observacionNivel = $items[0]['tobservacion'] ?? '';
                }
            
                for ($i = 0; $i < $rowspan; $i++) {
                    $item = $items[$i];
                    $html .= '<tr>';
            
                    if ($i === 0) {
                        // Primera fila: Nivel + Parámetro + % + Observaciones (con rowspan)
                        $html .= '<td class="nivel-cell" rowspan="' . $rowspan . '">' . htmlspecialchars(strtoupper($nivel)) . '</td>';
                        $html .= '<td class="param-cell">' . htmlspecialchars($item['tparametro'] ?? '') . '</td>';
                        $html .= '<td class="porc-cell">' . htmlspecialchars($item['tporcentajetotal'] ?? '0') . '%</td>';
                        $html .= '<td class="obs-cell" rowspan="' . $rowspan . '">' . nl2br(htmlspecialchars($observacionNivel)) . '</td>';
                    } else {
                        // Filas siguientes: solo Parámetro + %
                        $html .= '<td class="param-cell">' . htmlspecialchars($item['tparametro'] ?? '') . '</td>';
                        $html .= '<td class="porc-cell">' . htmlspecialchars($item['tporcentajetotal'] ?? '0') . '%</td>';
                        // ¡NO se agrega la celda de observaciones aquí!
                    }
            
                    $html .= '</tr>';
                }
            }
    
            $html .= '</table>';
        }
    
        $html .= '</body></html>';
        return $html;
    }

    function _generarReportePDF($cabecera, $registros) {
        // Logo y nombre de empresa en esquina superior derecha
        $logoPath = __DIR__ . '/../../logo.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
        
        $logoHtml = '';
        if ($logoBase64) {
            $logoHtml = '<div style="position: absolute; top: 15px; right: 20px; text-align: right; z-index: 1000;">
                <img src="' . htmlspecialchars($logoBase64) . '" style="width: 40px; height: 40px; vertical-align: top;" />
                <div style="display: inline-block; vertical-align: top; margin-left: 8px; text-align: center; font-size: 7pt; line-height: 1.3;">
                  GRANJA<br>RINCONADA<br>DEL SUR S.A.
                </div>
              </div>';
        }
        
        $granjaRaw = $cabecera['tcencos'] ?? '';
        $granjaTxt = strpos($granjaRaw, 'C=') !== false 
            ? trim(explode('C=', $granjaRaw)[0]) 
            : $granjaRaw;
        
        // Calcular porcentajes por nivel
        $porcentajes = [];
        foreach ($registros as $r) {
            $nivel = $r['tnivel'] ?? '';
            $parametro = $r['tparametro'] ?? '';
            $porcentaje = $r['tporcentajetotal'] ?? 0;
            if ($nivel && $parametro) {
                if (!isset($porcentajes[$nivel])) {
                    $porcentajes[$nivel] = [];
                }
                $porcentajes[$nivel][$parametro] = $porcentaje;
            }
        }

        // Agrupar por sistema
        $porSistema = [];
        foreach ($registros as $reg) {
            $sistema = $reg['tsistema'] ?? '';
            if ($sistema) {
                if (!isset($porSistema[$sistema])) {
                    $porSistema[$sistema] = [];
                }
                $porSistema[$sistema][] = $reg;
            }
        }

        $html = '<html><head><meta charset="UTF-8"><style>
            @page {
                size: A4 landscape;
                margin-top: 10mm;
                margin-left: 10mm;
                margin-right: 10mm;
                margin-bottom: 28mm; /* deja espacio para el footer/paginado */
                footer: html_myfooter;
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 11pt;
                color: #000;
                margin: 0;
                padding: 0;
                position: relative;
            }
            .page {
                page-break-after: always;
                position: relative;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .granja {
                font-size: 22pt;
                font-weight: bold;
                color: #002060;
            }
            .detalles {
                font-size: 15pt;
            }
            h1 {
                text-align: center;
                color: #002060;
                font-size: 26pt;
            }
            h2 {
                text-align: center;
                color: #002060;
                font-size: 22pt;
                border-bottom: 4px solid #002060;
                margin: 40px 0 20px 0;
                padding-bottom: 6px;
                page-break-before: always;
            }
            .nivel-title {
                font-size: 14pt;
                font-weight: bold;
                color: #002060;
                margin-bottom: 6px;
                text-align: center;
            }
            .first-page-content {
                text-align: center;
                margin: 80px auto;
                padding: 40px;
                border: 3px solid #666;
                border-style: double;
                background: #f5f5f5;
                max-width: 600px;
            }
            .first-page-title {
                font-size: 32pt;
                font-weight: bold;
                color: #002060;
                margin-bottom: 15px;
                text-transform: uppercase;
            }
            .first-page-line {
                border-top: 2px solid #fff;
                margin: 15px 0;
            }
            .first-page-subtitle {
                font-size: 20pt;
                font-weight: bold;
                color: #002060;
                margin: 10px 0;
                text-transform: uppercase;
            }
            .imagen-container {
                width: 320px;
                height: 250px;
                border: 1px solid #ccc;
                margin: 0 auto 6px auto;
            }
            .imagen-container img {
                max-width: 300px;
                max-height: 250px;
            }
            .imagen-label {
                font-size: 10pt;
                text-align: center;
                margin-top: 4px;
                font-weight: bold;
            }
            .porcentajes-container {
                width: 260px;
                padding: 5px;
                background-color: #D5D5D5;
                margin: 0 auto;
                font-size: 10pt;
            }
            .tabla-bursal {
                width: 70%;
                margin: 50px auto 0 auto;
                border-collapse: collapse;
                font-size: 11pt;
                background: #ffffff;
                clear: both;
            }
            .tabla-bursal th,
            .tabla-bursal td {
                border: 1px solid #ffffff;
                padding: 5px 7px;
                text-align: center;
            }
            .tabla-bursal th {
                background: #d9d9d9;
                color: #000;
                font-weight: bold;
            }
            .tabla-bursal tbody tr:nth-child(odd) {
                background: #f5f5f5;
            }
            .tabla-bursal tbody tr:nth-child(even) {
                background: #ffffff;
            }
            .tabla-bursal .estado {
                text-align: left;
                padding-left: 10px;
            }
        </style></head><body>
        <htmlpagefooter name="myfooter">
            <div style="text-align:center; font-size:8pt; color:#64748b;">
                Página {PAGENO} de {nbpg}
            </div>
        </htmlpagefooter>';

        // Primera página exactamente como la segunda imagen (estilo minimalista con doble borde)
        $html .= '<div class="page">';
        // Logo solo en la primera página (esquina superior derecha)
        if ($logoBase64) {
            $html .= '<div style="position: absolute; top: 15px; right: 15px; text-align: right; z-index: 1000; padding: 10px;">';
            $html .= '<img src="' . htmlspecialchars($logoBase64) . '" style="width: 50px; height: 50px; vertical-align: top;" />';
            $html .= '</div>';
        }
        
        // Contenedor con doble borde estilo segunda imagen
        $html .= '<div class="first-page-content">';
        $html .= '<div class="first-page-title">GRANJA ' . htmlspecialchars($granjaTxt) . '</div>';
        $html .= '<div class="first-page-line"></div>';
        $html .= '<div class="first-page-subtitle">Campaña ' . htmlspecialchars($cabecera['tcampania'] ?? '') . '</div>';
        $html .= '<div class="first-page-subtitle">GALPÓN ' . htmlspecialchars($cabecera['tgalpon'] ?? '') . '</div>';
        $html .= '<div class="first-page-subtitle">EDAD ' . htmlspecialchars($cabecera['tedad'] ?? '') . ' DÍAS</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Páginas por sistema
        foreach ($porSistema as $sistema => $regs) {
            if (empty(trim($sistema))) continue;

            // Agrupar por nivel
            $porNivel = [];
            foreach ($regs as $r) {
                $nivel = $r['tnivel'] ?? '';
                if ($nivel) {
                    if (!isset($porNivel[$nivel])) {
                        $porNivel[$nivel] = [];
                    }
                    $porNivel[$nivel][] = $r;
                }
            }

            $html .= '<h2>' . htmlspecialchars(strtoupper($sistema)) . '</h2>';
            
            // Calcular cuántas columnas por fila (2 o 3)
            $totalNiveles = count($porNivel);
            $columnasPorFila = $totalNiveles <= 2 ? 2 : 3;
            $anchoColumna = $columnasPorFila == 2 ? '50%' : '33%';
            
            $html .= '<table width="100%" cellpadding="5">';
            
            $nivelesList = array_keys($porNivel);
            foreach ($nivelesList as $i => $nivel) {
                $items = $porNivel[$nivel];
                
                // Abrir nueva fila cada 2 o 3 columnas
                if ($i % $columnasPorFila == 0) {
                    $html .= '<tr>';
                }
                
                // Imagen (por ahora sin imagen, solo placeholder)
                $imgHtml = 'Sin evidencia';
                
                // Porcentajes
                $porcHtml = '';
                $porcentajesNivel = $porcentajes[$nivel] ?? [];
                if (!empty($porcentajesNivel)) {
                    foreach ($porcentajesNivel as $param => $porc) {
                        $porcHtml .= '<tr><td>' . htmlspecialchars($param) . '</td><td><strong>' . number_format($porc, 2) . '%</strong></td></tr>';
                    }
                }

                $html .= '<td width="' . $anchoColumna . '" align="center" valign="top">';
                $html .= '<div class="nivel-title">' . htmlspecialchars($nivel) . '</div>';
                $html .= '<table class="imagen-container"><tr><td align="center" valign="middle">' . $imgHtml . '</td></tr></table>';
                $html .= '<div class="imagen-label">' . htmlspecialchars($nivel) . '</div>';
                $html .= '<table class="porcentajes-container">';
                if ($porcHtml) {
                    $html .= $porcHtml;
                } else {
                    $html .= '<tr><td style="text-align: center;">Sin datos</td></tr>';
                }
                $html .= '</table>';
                $html .= '</td>';
                
                // Cerrar fila cada 2 o 3 columnas o al final
                if (($i + 1) % $columnasPorFila == 0 || $i == count($nivelesList) - 1) {
                    $html .= '</tr>';
                }
            }

            $html .= '</table>';
        }

        $html .= '</body></html>';
        return $html;
    }
    ?>
    ?>
