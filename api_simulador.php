<?php
declare(strict_types=1);

/**
 * M2 - Simulador de sensores (capa de percepción simulada) y API del panel.
 *
 * GET  ?accion=resumen   -> resumen de consumo (para pintar el panel M3)
 * POST accion=simular    -> genera una lectura por equipo, la guarda, recalcula el
 *                           resumen y vuelve a evaluar las reglas (RF-03, RF-05)
 * POST accion=reiniciar  -> borra las lecturas del usuario (para repetir una demo)
 *
 * Las lecturas se generan bajo demanda: mientras el panel está abierto, JavaScript
 * llama a este endpoint cada cierto número de segundos (configurable), así el servidor
 * no necesita procesos en segundo plano. Respuesta en JSON por HTTPS.
 */
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/consultas.php';

$uid = requiere_sesion(true);
$u = cargar_usuario($conn, $uid);
$equipos = cargar_equipos($conn, $uid);
$metodo = $_SERVER['REQUEST_METHOD'];
$accion = (string) ($_POST['accion'] ?? $_GET['accion'] ?? 'resumen');

if ($metodo === 'GET' && $accion === 'resumen') {
    json_salida([
        'ok'      => true,
        'equipos' => count($equipos),
        'resumen' => resumen_consumo($conn, $u, $equipos, $CFG),
    ]);
}

if ($metodo !== 'POST') {
    json_salida(['ok' => false, 'error' => 'metodo_no_permitido'], 405);
}

csrf_exigir(true);

if ($accion === 'reiniciar') {
    $stmt = $conn->prepare('DELETE FROM lecturas WHERE usuario_id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->close();
    $equipos = cargar_equipos($conn, $uid);
    json_salida(['ok' => true, 'resumen' => resumen_consumo($conn, $u, $equipos, $CFG)]);
}

if ($accion !== 'simular') {
    json_salida(['ok' => false, 'error' => 'accion_no_valida'], 400);
}

if (!$equipos) {
    json_salida(['ok' => false, 'error' => 'sin_equipos', 'mensaje' => 'Registra al menos un equipo antes de simular.'], 422);
}

// Freno sencillo contra llamadas excesivas (no más de una lectura cada 0,8 s por sesión).
$ahora = microtime(true);
if (isset($_SESSION['ultima_simulacion']) && ($ahora - $_SESSION['ultima_simulacion']) < 0.8) {
    json_salida(['ok' => false, 'error' => 'demasiado_rapido'], 429);
}
$_SESSION['ultima_simulacion'] = $ahora;

// Parámetros configurables (RF-03): minutos que cubre cada lectura y hora simulada.
$intervalo = filter_var($_POST['intervalo_min'] ?? 15, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 60]]);
if ($intervalo === false) {
    $intervalo = 15;
}
$horaTxt = $_POST['hora'] ?? '';
$hora = ($horaTxt !== '' && filter_var($horaTxt, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 23]]) !== false)
    ? (int) $horaTxt
    : (int) date('G');

$modelo = new Modelo($CFG);
$tarifa = new Tarifa($CFG['tarifas']);
[$mesD, $mesH] = limites_mes();
$acumMes = consumo_entre($conn, $uid, $mesD, $mesH)['kwh'];

$fecha = date('Y-m-d H:i:s');
$lecturas = [];
$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        'INSERT INTO lecturas (usuario_id, equipo_id, fecha_lectura, intervalo_min, potencia_w, consumo_kwh, costo_cop)
         VALUES (?,?,?,?,?,?,?)'
    );
    foreach ($equipos as $eq) {
        $l = $modelo->lectura($eq, $hora, $intervalo, Modelo::ruidoAleatorio());
        $precio = $tarifa->precioMarginal($u['tipo_usuario'], $u['estrato'], $acumMes);
        $costo = round($l['consumo_kwh'] * $precio, 2);
        $acumMes += $l['consumo_kwh'];

        $eid = $eq['id'];
        $pot = $l['potencia_w'];
        $kwh = $l['consumo_kwh'];
        $stmt->bind_param('iisiddd', $uid, $eid, $fecha, $intervalo, $pot, $kwh, $costo);
        $stmt->execute();

        $lecturas[] = ['equipo_id' => $eid, 'nombre' => $eq['nombre'], 'potencia_w' => $pot, 'consumo_kwh' => $kwh, 'costo_cop' => $costo];
    }
    $stmt->close();
    $conn->commit();
} catch (Throwable $ex) {
    $conn->rollback();
    error_log('Ahorro Eco: error al guardar lecturas: ' . $ex->getMessage());
    json_salida(['ok' => false, 'error' => 'error_servidor'], 500);
}

// Nueva lectura -> se vuelven a evaluar las reglas (RNF-02 mide este tiempo).
$ev = evaluar_y_guardar($conn, $u, $equipos, $CFG);
$top = array_slice(array_map(static fn(array $r): string => $r['titulo'], $ev['recomendaciones']), 0, 3);

json_salida([
    'ok'                     => true,
    'fecha'                  => $fecha,
    'hora_simulada'          => $hora,
    'franja'                 => $modelo->franjaDeHora($hora),
    'intervalo_min'          => $intervalo,
    'lecturas'               => $lecturas,
    'resumen'                => resumen_consumo($conn, $u, $equipos, $CFG),
    'recomendaciones_total'  => count($ev['recomendaciones']),
    'recomendaciones_top'    => $top,
    'tiempo_reglas_ms'       => $ev['tiempo_ms'],
]);
