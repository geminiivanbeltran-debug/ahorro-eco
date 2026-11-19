<?php
declare(strict_types=1);

/**
 * M4 - Recomendaciones de ahorro (RF-05, RF-06, HU-03, HU-04).
 * Ejecuta el motor de reglas con el perfil, los equipos y el consumo del usuario y muestra
 * cada recomendación con la regla que la activó, la franja y el ahorro estimado.
 * El usuario califica cada una de 1 a 5 para medir su pertinencia (OE4).
 */
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require __DIR__ . '/inc/consultas.php';

header('Content-Type: text/html; charset=utf-8');

$uid = requiere_sesion();
$u = cargar_usuario($conn, $uid);
$equipos = cargar_equipos($conn, $uid);
$tarifa = new Tarifa($CFG['tarifas']);

$recs = [];
$tiempoMs = null;
if ($equipos) {
    $ev = evaluar_y_guardar($conn, $u, $equipos, $CFG);
    $tiempoMs = $ev['tiempo_ms'];
    $recs = recomendaciones_vigentes($conn, $uid);
}
$per = pertinencia($conn, $uid);

$ahorroKwh = 0.0;
$ahorroCop = 0.0;
foreach ($recs as $r) {
    if ($r['tipo_beneficio'] === 'ahorro') {
        $ahorroKwh += $r['ahorro_kwh_mes'];
        $ahorroCop += $r['ahorro_cop_mes'];
    }
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$etqEsfuerzo = [1 => 'Esfuerzo bajo', 2 => 'Esfuerzo medio', 3 => 'Esfuerzo alto'];

pagina_inicio('Recomendaciones', 'recomendaciones');
?>
<h1 class="section-title">Recomendaciones de ahorro</h1>
<p class="lead">Calculadas con reglas para tu perfil de <strong><?= $u['tipo_usuario'] === 'hogar' ? 'hogar' : 'microempresa' ?></strong>.
    Cada recomendación te dice qué regla la activó y por qué, y están ordenadas de menos a más esfuerzo.</p>

<?php if ($flash): ?><div class="alerta <?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<?php if (!$equipos): ?>
    <div class="alerta info">Aún no tienes equipos registrados, por eso no hay recomendaciones.</div>
    <a class="btn btn-negro" href="equipos.php">Registrar mis equipos</a>
<?php else: ?>

<div class="grid-kpi">
    <div class="kpi"><div class="etq">Recomendaciones</div><div class="val"><?= count($recs) ?></div><div class="sub">activas para ti</div></div>
    <div class="kpi"><div class="etq">Ahorro potencial</div><div class="val"><?= cop($ahorroCop) ?></div><div class="sub">por mes · <?= num($ahorroKwh, 1) ?> kWh</div></div>
    <div class="kpi"><div class="etq">Calificadas pertinentes</div><div class="val"><?= $per['porcentaje'] === null ? '—' : num($per['porcentaje'], 0) . ' %' ?></div>
        <div class="sub"><?= $per['calificadas'] ?> calificadas · meta 80 % con 4 o 5</div></div>
    <div class="kpi"><div class="etq">Tiempo del motor</div><div class="val"><?= num($tiempoMs ?? 0, 1) ?> ms</div><div class="sub">meta: 2 s o menos</div></div>
</div>

<?php if (!$recs): ?>
    <div class="card vacio">Con tus equipos actuales el motor no encontró recomendaciones. ¡Buen uso de la energía!</div>
<?php endif; ?>

<?php foreach ($recs as $r):
    $franjaTxt = $r['franja'] && isset($CFG['franjas'][$r['franja']]) ? explode(' (', $CFG['franjas'][$r['franja']]['nombre'])[0] : null; ?>
    <div class="card rec esf-<?= (int) $r['esfuerzo'] ?>" id="rec-<?= (int) $r['id'] ?>">
        <h3><?= e($r['titulo']) ?></h3>
        <div class="chips">
            <?php if ($r['equipo_nombre']): ?><span class="chip">Equipo: <?= e($r['equipo_nombre']) ?></span><?php endif; ?>
            <?php if ($franjaTxt): ?><span class="chip">Franja: <?= e($franjaTxt) ?></span><?php endif; ?>
            <?php if ($r['tipo_beneficio'] === 'ahorro'): ?>
                <span class="chip ahorro">Ahorro estimado: <?= cop($r['ahorro_cop_mes']) ?>/mes (<?= num($r['ahorro_kwh_mes'], 1) ?> kWh)</span>
            <?php elseif ($r['tipo_beneficio'] === 'demanda'): ?>
                <span class="chip">Beneficio: menor demanda en la hora pico</span>
            <?php else: ?>
                <span class="chip alerta">Alerta</span>
            <?php endif; ?>
            <span class="chip"><?= e($etqEsfuerzo[$r['esfuerzo']] ?? '') ?></span>
        </div>
        <p><?= e($r['mensaje']) ?></p>
        <div class="porque"><strong>¿Por qué?</strong> <?= e($r['explicacion']) ?></div>

        <form method="post" action="calificar.php" style="margin-top:12px">
            <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <div class="estrellas">
                <span class="estado">¿Te sirve? Califica de 1 a 5:</span>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="submit" name="calificacion" value="<?= $i ?>" class="<?= $r['calificacion'] === $i ? 'sel' : '' ?>" title="<?= $i ?> de 5"><?= $i ?></button>
                <?php endfor; ?>
                <?php if ($r['calificacion'] !== null): ?><span class="estado">Tu calificación: <?= (int) $r['calificacion'] ?></span><?php endif; ?>
            </div>
        </form>
    </div>
<?php endforeach; ?>

<p class="estado"><?= e($tarifa->descripcion($u['tipo_usuario'], $u['estrato'])) ?>. Los ahorros son estimaciones calculadas con las horas de uso que registraste y con supuestos ajustables.</p>
<?php endif; ?>
<?php pagina_fin();
