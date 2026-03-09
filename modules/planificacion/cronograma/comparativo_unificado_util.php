<?php
/**
 * Utilidades para reporte comparativo unificado.
 * - Key: granja|campania|galpon (normal) o granja (si programa esEspecial en san_fact_programa_cab).
 * - Comparación por granja, campaña, galpón y fecha (edad en segundo plano).
 * - Tolerancia por registro en san_fact_cronograma para determinar SI CUMPLIO / NO CUMPLIO / ATRASADO.
 */

if (!function_exists('comparativo_cargar_es_especial')) {
    /**
     * Carga mapa codPrograma -> esEspecial (1 = especial) desde san_fact_programa_cab.
     * @param mysqli $conn
     * @param string[] $codigos Lista de códigos de programa
     * @return array [ codPrograma => 1|0 ]
     */
    function comparativo_cargar_es_especial($conn, array $codigos) {
        $out = [];
        if (empty($codigos)) return $out;
        $chk = @$conn->query("SHOW COLUMNS FROM san_fact_programa_cab LIKE 'esEspecial'");
        if (!$chk || $chk->num_rows === 0) return $out;
        $ph = implode(',', array_fill(0, count($codigos), '?'));
        $st = $conn->prepare("SELECT codigo, COALESCE(esEspecial, 0) AS esEspecial FROM san_fact_programa_cab WHERE codigo IN ($ph)");
        if (!$st) return $out;
        $types = str_repeat('s', count($codigos));
        $st->bind_param($types, ...$codigos);
        $st->execute();
        $res = $st->get_result();
        while ($row = $res->fetch_assoc()) {
            $cod = trim((string)($row['codigo'] ?? ''));
            if ($cod !== '') $out[$cod] = (int)($row['esEspecial'] ?? 0);
        }
        $st->close();
        return $out;
    }
}

if (!function_exists('comparativo_build_key')) {
    /**
     * Construye la key de comparación: granja|campania|galpon (normal) o granja|campania|galpon|edad
     * cuando se incluye edad, o granja (si programa especial).
     * @param string $granja
     * @param string $campania
     * @param string $galpon
     * @param string $codPrograma
     * @param array $esEspecialPorCod [ codPrograma => 1|0 ]
     * @param string|null $edad Opcional. Si se pasa y no es especial, la key incluye edad.
     * @param bool $edadMenos1Como0 Para vacunas: si edad=-1 interpretar como 0 para match.
     * @return string
     */
    function comparativo_build_key($granja, $campania, $galpon, $codPrograma, array $esEspecialPorCod, $edad = null, $edadMenos1Como0 = false) {
        $esEspecial = isset($esEspecialPorCod[$codPrograma]) ? (int)$esEspecialPorCod[$codPrograma] : 0;
        if ($esEspecial === 1) {
            return $granja;
        }
        $base = $granja . '|' . $campania . '|' . $galpon;
        if ($edad !== null && $edad !== '') {
            $e = trim((string)$edad);
            if ($edadMenos1Como0 && ($e === '-1' || $e === '- 1')) $e = '0';
            $base .= '|' . $e;
        }
        return $base;
    }
}

if (!function_exists('match_plan_eje_con_tolerancia_por_fecha')) {
    /**
     * Match plan vs ejecución con tolerancia por fecha (cada fecha plan tiene su tolerancia en san_fact_cronograma).
     * Devuelve pares (una fila por fecha) y ejecutadas sin match para construir una fila por fecha.
     *
     * @param array $fechasPlan list of fechas planificadas
     * @param array $toleranciaPorFecha [ fecha => dias ] (por defecto 1 si falta)
     * @param array $fechasEje list of fechas ejecutadas
     * @param bool $soloExacto Si true, solo empareja cuando plan=fecha ejec (dias=0). Si false, usa tolerancia.
     * @return array [
     *   'interseccion' => [],           // fechas ejecutadas que hicieron match (compat)
     *   'estado' => 'SI CUMPLIO'|'ATRASADO'|'',
     *   'fechaMostrar' => '',
     *   'pares' => [ ['plan'=>fp, 'ejec'=>fe|null, 'atrasado'=>bool], ... ],  // una entrada por fecha plan
     *   'ejecutadasSinMatch' => []     // fechas ejecutadas que no emparejaron con ninguna plan
     * ]
     */
    function match_plan_eje_con_tolerancia_por_fecha(array $fechasPlan, array $toleranciaPorFecha, array $fechasEje, $soloExacto = false) {
        $fechasPlan = array_values(array_unique($fechasPlan));
        $fechasEje = array_values(array_unique($fechasEje));
        sort($fechasPlan);
        sort($fechasEje);
        $interseccion = [];
        $pares = [];
        $paresDetalle = [];
        $usadas = [];
        foreach ($fechasPlan as $fp) {
            $tol = $soloExacto ? 0 : (isset($toleranciaPorFecha[$fp]) ? max(1, (int)$toleranciaPorFecha[$fp]) : 1);
            $tsPlan = strtotime($fp);
            $candidatos = [];
            foreach ($fechasEje as $i => $fe) {
                if (isset($usadas[$i])) continue;
                $tsEje = strtotime($fe);
                $dias = (int)round(($tsEje - $tsPlan) / 86400);
                if ($dias >= -$tol && $dias <= $tol) $candidatos[] = ['i' => $i, 'fecha' => $fe, 'dias' => $dias, 'abs' => abs($dias)];
            }
            if (!empty($candidatos)) {
                usort($candidatos, function ($a, $b) { return $a['abs'] - $b['abs']; });
                $mejor = $candidatos[0];
                $usadas[$mejor['i']] = true;
                $interseccion[] = $mejor['fecha'];
                $pares[] = ['atrasado' => $mejor['dias'] > 0];
                $paresDetalle[] = ['plan' => $fp, 'ejec' => $mejor['fecha'], 'atrasado' => $mejor['dias'] > 0];
            } else {
                $paresDetalle[] = ['plan' => $fp, 'ejec' => null, 'atrasado' => false];
            }
        }
        $ejecutadasSinMatch = [];
        foreach ($fechasEje as $i => $fe) {
            if (!isset($usadas[$i])) $ejecutadasSinMatch[] = $fe;
        }
        $estado = '';
        $fechaMostrar = '';
        if (count($interseccion) > 0) {
            $fechaMostrar = $interseccion[0];
            $algunaAtrasada = false;
            foreach ($pares as $p) { if ($p['atrasado']) { $algunaAtrasada = true; break; } }
            $estado = $algunaAtrasada ? 'ATRASADO' : 'SI CUMPLIO';
        }
        return [
            'interseccion' => $interseccion,
            'estado' => $estado,
            'fechaMostrar' => $fechaMostrar,
            'pares' => $paresDetalle,
            'ejecutadasSinMatch' => $ejecutadasSinMatch
        ];
    }
}

/**
 * Empareja ejecutadas sin match (desarrollados) con un planificado anterior (NO CUMPLIO).
 *
 * REGLA ATRASADOS: Un registro desarrollado (ejecutada sin match) puede buscar match solo con un
 * plan NO CUMPLIO (desarrollado sin match) ANTERIOR. Nunca con uno posterior.
 *
 * - Plan debe ser anterior: tsPlan < tsEje (solo ese caso; no se empareja con plan posterior).
 * - Ejecución dentro de ventana: tsEje <= plan + max(tol, 365 días). Se usa al menos 365 días para
 *   emparejar desarrollos tardíos como ATRASADO (un NO CUMPLIO planificado + ejecutada posterior = atrasado).
 *
 * @param array $pares [ ['plan'=>fp, 'ejec'=>fe|null, 'atrasado'=>bool], ... ]
 * @param array $ejecutadasSinMatch fechas ejecutadas que no hicieron match
 * @param array $toleranciaPorFecha [ fecha => dias ] Opcional. Si se pasa, solo emparejar cuando ejec <= plan + tol.
 * @param array $noCumplioExtras [ ['plan'=>fecha, 'tol'=>dias, 'key'=>keyPlan], ... ] Planes NO CUMPLIO de otras keys (mismo keyBase). Si plan se empareja, el par incluirá planKey.
 * @return array [ 'pares' => pares actualizados, 'ejecutadasSinMatch' => resto, 'planUsadoDesdeOtraKey' => [ keyPlan => [ fechaPlan => true ] ] para suprimir salida en key origen ]
 */
if (!function_exists('emparejar_atrasados_con_plan_anterior')) {
    function emparejar_atrasados_con_plan_anterior(array $pares, array $ejecutadasSinMatch, array $toleranciaPorFecha = [], array $noCumplioExtras = []) {
        $noCumplioPlans = [];
        foreach ($pares as $par) {
            if (array_key_exists('ejec', $par) && $par['ejec'] === null) {
                $noCumplioPlans[] = ['plan' => $par['plan'], 'tol' => null, 'key' => null];
            }
        }
        foreach ($noCumplioExtras as $ext) {
            $noCumplioPlans[] = ['plan' => $ext['plan'], 'tol' => isset($ext['tol']) ? (int)$ext['tol'] : null, 'key' => isset($ext['key']) ? $ext['key'] : null];
        }
        usort($noCumplioPlans, function ($a, $b) { return strcmp($a['plan'], $b['plan']); });
        $ejecutadas = array_values($ejecutadasSinMatch);
        sort($ejecutadas);
        $planUsado = [];
        $planUsadoDesdeOtraKey = [];
        $atrasadoPares = [];
        $ejecutadasRestantes = [];
        foreach ($ejecutadas as $ejec) {
            $tsEje = strtotime($ejec);
            $mejor = null;
            $mejorTs = -1;
            foreach ($noCumplioPlans as $item) {
                $plan = $item['plan'];
                $planKey = $item['key'];
                if (isset($planUsado[$plan])) continue;
                $tsPlan = strtotime($plan);
                if ($tsPlan >= $tsEje) continue;
                if ($tsPlan <= $mejorTs) continue;
                $tol = $item['tol'] !== null ? $item['tol'] : (isset($toleranciaPorFecha[$plan]) ? max(1, (int)$toleranciaPorFecha[$plan]) : 1);
                // Para atrasados: permitir emparejar desarrollos tardíos (plan no cumplido + ejecutada posterior = ATRASADO)
                $tol = max($tol, 365);
                $limiteTs = strtotime($plan . ' +' . $tol . ' days');
                if ($tsEje > $limiteTs) continue;
                $mejorTs = $tsPlan;
                $mejor = $item;
            }
            if ($mejor !== null) {
                $planUsado[$mejor['plan']] = true;
                $ap = ['plan' => $mejor['plan'], 'ejec' => $ejec, 'atrasado' => true];
                if ($mejor['key'] !== null) {
                    $ap['planKey'] = $mejor['key'];
                    if (!isset($planUsadoDesdeOtraKey[$mejor['key']])) $planUsadoDesdeOtraKey[$mejor['key']] = [];
                    $planUsadoDesdeOtraKey[$mejor['key']][$mejor['plan']] = true;
                }
                $atrasadoPares[] = $ap;
            } else {
                $ejecutadasRestantes[] = $ejec;
            }
        }
        $nuevosPares = [];
        foreach ($pares as $par) {
            if (isset($par['ejec']) && $par['ejec'] !== null) {
                $nuevosPares[] = $par;
            } else {
                if (!isset($planUsado[$par['plan']])) {
                    $nuevosPares[] = $par;
                }
            }
        }
        foreach ($atrasadoPares as $ap) {
            $nuevosPares[] = $ap;
        }
        return ['pares' => $nuevosPares, 'ejecutadasSinMatch' => $ejecutadasRestantes, 'planUsadoDesdeOtraKey' => $planUsadoDesdeOtraKey];
    }
}
