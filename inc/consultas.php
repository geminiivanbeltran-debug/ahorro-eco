<?php
declare(strict_types=1);

/**
 * Consultas del prototipo que combinan base de datos y motor de reglas.
 * Todas usan consultas preparadas (RNF-05).
 * Requiere haber cargado inc/bootstrap.php antes.
 */

/** Suma de consumo y costo de un usuario entre dos instantes ('Y-m-d H:i:s'). */
function consumo_entre(mysqli $conn, int $usuarioId, string $desde, string $hasta): array
{
    $stmt = $conn->prepare(
        'SELECT COALESCE(SUM(consumo_kwh),0) AS kwh, COALESCE(SUM(costo_cop),0) AS cop
           FROM lecturas WHERE usuario_id = ? AND fecha_lectura >= ? AND fecha_lectura < ?'
    );
    $stmt->bind_param('iss', $usuarioId, $desde, $hasta);
    $stmt->execute();
    $f = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['kwh' => (float) $f['kwh'], 'cop' => (float) $f['cop']];
}

function limites_hoy(): array
{
    return [date('Y-m-d 00:00:00'), date('Y-m-d 00:00:00', strtotime('+1 day'))];
}

function limites_mes(): array
{
    return [date('Y-m-01 00:00:00'), date('Y-m-01 00:00:00', strtotime('first day of next month'))];
}

/**
 * Resumen que muestra el panel de consumo (M3): hoy por equipo, últimos 7 días,
 * acumulado del mes y proyección mensual con la tarifa de EMCALI (RF-04).
 */
function resumen_consumo(mysqli $conn, array $u, array $equipos, array $cfg): array
{
    $tarifa = new Tarifa($cfg['tarifas']);
    $modelo = new Modelo($cfg);
    $uid = (int) $u['id'];
    [$hoyD, $hoyH] = limites_hoy();
    [$mesD, $mesH] = limites_mes();

    // Hoy, por equipo.
    $stmt = $conn->prepare(
        'SELECT e.id, e.nombre, COALESCE(SUM(l.consumo_kwh),0) AS kwh, COALESCE(SUM(l.costo_cop),0) AS cop
           FROM equipos e
           LEFT JOIN lecturas l ON l.equipo_id = e.id AND l.fecha_lectura >= ? AND l.fecha_lectura < ?
          WHERE e.usuario_id = ?
          GROUP BY e.id, e.nombre ORDER BY kwh DESC, e.id'
    );
    $stmt->bind_param('ssi', $hoyD, $hoyH, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $porEquipo = [];
    while ($f = $res->fetch_assoc()) {
        $porEquipo[] = ['id' => (int) $f['id'], 'nombre' => $f['nombre'], 'kwh' => (float) $f['kwh'], 'cop' => (float) $f['cop']];
    }
    $stmt->close();

    // Últimos 7 días.
    $desde7 = date('Y-m-d 00:00:00', strtotime('-6 days'));
    $stmt = $conn->prepare(
        'SELECT DATE(fecha_lectura) AS dia, SUM(consumo_kwh) AS kwh, SUM(costo_cop) AS cop
           FROM lecturas WHERE usuario_id = ? AND fecha_lectura >= ? AND fecha_lectura < ?
          GROUP BY DATE(fecha_lectura) ORDER BY dia'
    );
    $stmt->bind_param('iss', $uid, $desde7, $hoyH);
    $stmt->execute();
    $res = $stmt->get_result();
    $mapa = [];
    while ($f = $res->fetch_assoc()) {
        $mapa[$f['dia']] = ['kwh' => (float) $f['kwh'], 'cop' => (float) $f['cop']];
    }
    $stmt->close();
    $dias = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $dias[] = ['fecha' => $d, 'etiqueta' => date('d/m', strtotime($d)),
                   'kwh' => $mapa[$d]['kwh'] ?? 0.0, 'cop' => $mapa[$d]['cop'] ?? 0.0];
    }

    $hoy = consumo_entre($conn, $uid, $hoyD, $hoyH);
    $mes = consumo_entre($conn, $uid, $mesD, $mesH);

    // Proyección mensual a partir de los equipos declarados.
    $kwhProy = 0.0;
    foreach ($equipos as $eq) {
        $kwhProy += $modelo->kwhMes($eq);
    }
    $copProy = $tarifa->costoMes($u['tipo_usuario'], $u['estrato'], $kwhProy);

    $stmt = $conn->prepare('SELECT MAX(fecha_lectura) AS ultima FROM lecturas WHERE usuario_id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $ultima = $stmt->get_result()->fetch_assoc()['ultima'] ?? null;
    $stmt->close();

    return [
        'hoy'            => $hoy,
        'por_equipo'     => $porEquipo,
        'dias'           => $dias,
        'mes'            => $mes,
        'proyeccion_mes' => ['kwh' => round($kwhProy, 2), 'cop' => round($copProy, 0)],
        'tarifa'         => $tarifa->descripcion($u['tipo_usuario'], $u['estrato']),
        'ultima_lectura' => $ultima,
        'limite_diario'  => $u['limite_diario_kwh'],
    ];
}

/**
 * Evalúa el motor de reglas con el estado actual del usuario y guarda las
 * recomendaciones (RF-05). La calificación de cada una se conserva (RF-06).
 *
 * @return array{recomendaciones:array,tiempo_ms:float}
 */
function evaluar_y_guardar(mysqli $conn, array $u, array $equipos, array $cfg): array
{
    $t0 = microtime(true);
    $uid = (int) $u['id'];
    [$hoyD, $hoyH] = limites_hoy();
    $hoy = consumo_entre($conn, $uid, $hoyD, $hoyH);

    $motor = new MotorReglas($cfg);
    $recs = $motor->evaluar([
        'perfil'            => $u['tipo_usuario'],
        'estrato'           => $u['estrato'],
        'equipos'           => $equipos,
        'consumo_hoy_kwh'   => $hoy['kwh'],
        'limite_diario_kwh' => $u['limite_diario_kwh'],
    ]);
    $tiempoMotor = (microtime(true) - $t0) * 1000.0;

    $ahora = date('Y-m-d H:i:s');
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('UPDATE recomendaciones SET vigente = 0 WHERE usuario_id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            'INSERT INTO recomendaciones
                (usuario_id, regla_id, equipo_clave, titulo, mensaje, explicacion, franja,
                 ahorro_kwh_mes, ahorro_cop_mes, tipo_beneficio, esfuerzo, vigente, fecha_generada)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,1,?)
             ON DUPLICATE KEY UPDATE
                titulo = VALUES(titulo), mensaje = VALUES(mensaje), explicacion = VALUES(explicacion),
                franja = VALUES(franja), ahorro_kwh_mes = VALUES(ahorro_kwh_mes),
                ahorro_cop_mes = VALUES(ahorro_cop_mes), tipo_beneficio = VALUES(tipo_beneficio),
                esfuerzo = VALUES(esfuerzo), vigente = 1, fecha_generada = VALUES(fecha_generada)'
        );
        foreach ($recs as $r) {
            $clave = (int) ($r['equipo_id'] ?? 0);
            $franja = $r['franja'];
            $stmt->bind_param(
                'isissssddsis',
                $uid, $r['regla_id'], $clave, $r['titulo'], $r['mensaje'], $r['explicacion'], $franja,
                $r['ahorro_kwh_mes'], $r['ahorro_cop_mes'], $r['tipo_beneficio'], $r['esfuerzo'], $ahora
            );
            $stmt->execute();
        }
        $stmt->close();
        $conn->commit();
    } catch (Throwable $ex) {
        $conn->rollback();
        error_log('Ahorro Eco: no se pudieron guardar las recomendaciones: ' . $ex->getMessage());
    }

    return ['recomendaciones' => $recs, 'tiempo_ms' => round((microtime(true) - $t0) * 1000.0, 1)];
}

/** Recomendaciones vigentes con su id y calificación, en el orden del motor. */
function recomendaciones_vigentes(mysqli $conn, int $uid): array
{
    $stmt = $conn->prepare(
        'SELECT r.id, r.regla_id, r.equipo_clave, r.titulo, r.mensaje, r.explicacion, r.franja,
                r.ahorro_kwh_mes, r.ahorro_cop_mes, r.tipo_beneficio, r.esfuerzo, r.calificacion,
                e.nombre AS equipo_nombre
           FROM recomendaciones r
           LEFT JOIN equipos e ON e.id = r.equipo_clave AND e.usuario_id = r.usuario_id
          WHERE r.usuario_id = ? AND r.vigente = 1
          ORDER BY r.esfuerzo ASC, r.ahorro_cop_mes DESC, r.id ASC'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($f = $res->fetch_assoc()) {
        $f['id'] = (int) $f['id'];
        $f['ahorro_kwh_mes'] = (float) $f['ahorro_kwh_mes'];
        $f['ahorro_cop_mes'] = (float) $f['ahorro_cop_mes'];
        $f['esfuerzo'] = (int) $f['esfuerzo'];
        $f['calificacion'] = $f['calificacion'] !== null ? (int) $f['calificacion'] : null;
        $out[] = $f;
    }
    $stmt->close();
    return $out;
}

/** Indicador de pertinencia (OE4): % de recomendaciones calificadas con 4 o 5. */
function pertinencia(mysqli $conn, int $uid): array
{
    $stmt = $conn->prepare(
        'SELECT COUNT(calificacion) AS calificadas, COALESCE(SUM(calificacion >= 4),0) AS pertinentes
           FROM recomendaciones WHERE usuario_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $f = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $cal = (int) $f['calificadas'];
    $per = (int) $f['pertinentes'];
    return ['calificadas' => $cal, 'pertinentes' => $per, 'porcentaje' => $cal > 0 ? round($per * 100 / $cal, 1) : null];
}
