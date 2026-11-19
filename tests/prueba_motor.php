<?php
/**
 * Pruebas por consola de la lógica del prototipo (sin base de datos).
 * Uso:  php tests/prueba_motor.php
 * Sale con código 0 si todo pasa y 1 si algo falla.
 */
declare(strict_types=1);

require __DIR__ . '/../inc/MotorReglas.php';

$cfg = require __DIR__ . '/../inc/config.php';
$fallos = 0;
$pruebas = 0;

function prueba(string $nombre, bool $ok, string $detalle = ''): void
{
    global $fallos, $pruebas;
    $pruebas++;
    if (!$ok) { $fallos++; }
    printf("[%s] %s%s\n", $ok ? ' OK ' : 'FALLA', $nombre, $detalle !== '' ? "  ($detalle)" : '');
}

function equipos(array $plantilla): array
{
    $out = [];
    foreach ($plantilla as $i => $p) {
        $out[] = ['id' => $i + 1, 'nombre' => $p['nombre'], 'categoria' => $p['categoria'],
                  'potencia_w' => $p['potencia'], 'cantidad' => $p['cantidad'], 'horas_dia' => $p['horas'],
                  'franja' => $p['franja'], 'deja_reposo' => $p['reposo']];
    }
    return $out;
}

// ---------------------------------------------------------------- Tarifas
$t = new Tarifa($cfg['tarifas']);
prueba('Estrato 4, 200 kWh ≈ $183.100 (Capítulo 2 del documento)',
    abs($t->costoMes('hogar', 4, 200) - 183104.0) < 0.01, number_format($t->costoMes('hogar', 4, 200), 2));
$esp3 = 173 * 778.19 + 27 * 915.52;
prueba('Estrato 3, 200 kWh usa tarifa por tramos (173 kWh de subsistencia)',
    abs($t->costoMes('hogar', 3, 200) - $esp3) < 0.01, number_format($t->costoMes('hogar', 3, 200), 2));
prueba('Microempresa paga tarifa comercial $1.098,63',
    abs($t->costoMes('microempresa', null, 100) - 109863.0) < 0.01);
prueba('Precio marginal estrato 3 cambia al pasar el tope',
    $t->precioMarginal('hogar', 3, 100) === 778.19 && $t->precioMarginal('hogar', 3, 180) === 915.52);
prueba('Estrato 4 no tiene subsistencia', !$t->aplicaSubsistencia('hogar', 4));

// ---------------------------------------------------------------- Modelo / simulador
$m = new Modelo($cfg);
$hogar = equipos($cfg['plantillas']['hogar']);
$micro = equipos($cfg['plantillas']['microempresa']);

$okConsistencia = true;
$peor = 0.0;
foreach (array_merge($hogar, $micro) as $eq) {
    $suma = 0.0;
    for ($h = 0; $h < 24; $h++) {
        $suma += $m->lectura($eq, $h, 60, 1.0)['consumo_kwh'];
    }
    $dif = abs($suma - $m->kwhDia($eq));
    $peor = max($peor, $dif);
    if ($dif > 0.001) { $okConsistencia = false; }
}
prueba('Suma de lecturas simuladas de 24 h = consumo diario esperado', $okConsistencia, 'diferencia máxima ' . round($peor, 6) . ' kWh');

$nevera = $hogar[0];
prueba('Nevera (24 h, factor 0,35): 150 W × 0,35 × 24 h = 1,26 kWh/día',
    abs($m->kwhDia($nevera) - 1.26) < 0.001, number_format($m->kwhDia($nevera), 4));

$tv = $hogar[1]; // 5 h en la noche, deja_reposo
$lNoche = $m->lectura($tv, 20, 60, 1.0);
$lMadru = $m->lectura($tv, 2, 60, 1.0);
prueba('Televisor en la noche consume más que en la madrugada (reposo)',
    $lNoche['potencia_w'] > $lMadru['potencia_w'] && $lMadru['potencia_w'] > 0,
    $lNoche['potencia_w'] . ' W vs ' . $lMadru['potencia_w'] . ' W');

$r1 = $m->lectura($hogar[2], 20, 15, 1.0);
$r2 = $m->lectura($hogar[2], 20, 30, 1.0);
prueba('El intervalo simulado escala el consumo (30 min = 2 × 15 min)',
    abs($r2['consumo_kwh'] - 2 * $r1['consumo_kwh']) < 0.0001);

// ---------------------------------------------------------------- Motor de reglas
$motor = new MotorReglas($cfg);
$catalogo = MotorReglas::catalogo();
prueba('El catálogo tiene al menos 10 reglas', count($catalogo) >= 10, count($catalogo) . ' reglas');

$escenarios = [
    'hogar estrato 3 (plantilla)' => ['perfil' => 'hogar', 'estrato' => 3, 'equipos' => $hogar, 'consumo_hoy_kwh' => 2.0, 'limite_diario_kwh' => null],
    'hogar estrato 3, consumo alto' => ['perfil' => 'hogar', 'estrato' => 3, 'equipos' => array_merge($hogar, equipos([
        ['nombre' => 'Nevera vieja', 'categoria' => 'nevera', 'potencia' => 260, 'cantidad' => 1, 'horas' => 24, 'franja' => 'noche', 'reposo' => 0],
        ['nombre' => 'Aire alcoba', 'categoria' => 'aire', 'potencia' => 1200, 'cantidad' => 1, 'horas' => 10, 'franja' => 'noche', 'reposo' => 0],
    ])), 'consumo_hoy_kwh' => 9.5, 'limite_diario_kwh' => 8.0],
    'microempresa (plantilla)' => ['perfil' => 'microempresa', 'estrato' => null, 'equipos' => $micro, 'consumo_hoy_kwh' => 20.0, 'limite_diario_kwh' => 15.0],
];

$disparadas = [];
foreach ($escenarios as $nombre => $ctx) {
    $t0 = microtime(true);
    $recs = $motor->evaluar($ctx);
    $ms = (microtime(true) - $t0) * 1000;
    $ids = array_values(array_unique(array_column($recs, 'regla_id')));
    sort($ids);
    foreach ($ids as $id) { $disparadas[$id] = true; }

    echo "\n  Escenario: $nombre -> " . count($recs) . " recomendaciones, reglas: " . implode(', ', $ids) . sprintf(" (%.2f ms)\n", $ms);
    prueba("  RNF-02: el motor responde en 2 s o menos", $ms <= 2000);

    $perfil = $ctx['perfil'];
    $ajenas = array_filter($ids, fn($id) => !in_array($perfil, $catalogo[$id]['perfiles'], true));
    prueba("  Solo aparecen reglas del perfil $perfil", count($ajenas) === 0, implode(',', $ajenas));

    $completas = true;
    foreach ($recs as $r) {
        foreach (['regla_id', 'titulo', 'mensaje', 'explicacion', 'ahorro_kwh_mes', 'ahorro_cop_mes', 'esfuerzo'] as $k) {
            if (!array_key_exists($k, $r) || $r[$k] === '' || $r[$k] === null) { $completas = false; }
        }
        if ($r['tipo_beneficio'] === 'ahorro' && $r['ahorro_kwh_mes'] <= 0) { $completas = false; }
    }
    prueba('  Cada recomendación trae regla, explicación y ahorro estimado', $completas);

    $ordenado = true;
    for ($i = 1; $i < count($recs); $i++) {
        if ($recs[$i - 1]['esfuerzo'] > $recs[$i]['esfuerzo']) { $ordenado = false; }
    }
    prueba('  Orden: menor esfuerzo primero', $ordenado);
}

echo "\n";
$faltan = array_diff(array_keys($catalogo), array_keys($disparadas));
prueba('Las ' . count($catalogo) . ' reglas se activan en al menos un escenario', count($faltan) === 0, 'sin activar: ' . implode(',', $faltan));

// Muestra de una recomendación para revisar el texto.
$muestra = $motor->evaluar($escenarios['hogar estrato 3 (plantilla)'])[0];
echo "\nEjemplo de recomendación del hogar:\n";
echo '  ' . $muestra['titulo'] . "\n  " . $muestra['explicacion'] . "\n";
printf("  Ahorro estimado: %.2f kWh/mes = \$%s/mes\n", $muestra['ahorro_kwh_mes'], number_format($muestra['ahorro_cop_mes'], 0, ',', '.'));

echo "\n$pruebas pruebas, $fallos fallos\n";
exit($fallos > 0 ? 1 : 0);
