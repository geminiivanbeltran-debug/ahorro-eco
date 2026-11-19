<?php
declare(strict_types=1);

require_once __DIR__ . '/Modelo.php';
require_once __DIR__ . '/Tarifa.php';

/**
 * Motor de reglas de "Ahorro Eco" (sistema experto sencillo).
 *
 * Compara los equipos del usuario y su consumo con umbrales y franjas horarias
 * (config.php) y devuelve recomendaciones EXPLICABLES: cada una dice qué regla
 * la activó y por qué. No usa aprendizaje automático (ver Capítulo 6).
 *
 * Sin dependencias de base de datos: recibe un contexto con arreglos y devuelve
 * arreglos. Así se prueba desde la consola (tests/prueba_motor.php).
 *
 * Contexto esperado ($ctx):
 *   perfil              'hogar' | 'microempresa'
 *   estrato             ?int (hogares)
 *   equipos             lista de ['id','nombre','categoria','potencia_w','cantidad',
 *                                 'horas_dia','franja','deja_reposo']
 *   consumo_hoy_kwh     float  consumo registrado hoy por el simulador
 *   limite_diario_kwh   ?float límite que fijó el usuario
 */
final class MotorReglas
{
    private Modelo $modelo;
    private Tarifa $tarifa;
    private array $u;       // umbrales
    private array $cfg;

    /** id => [nombre, perfiles, método] */
    private const REGLAS = [
        'R01' => ['Equipos en reposo (standby)',            ['hogar', 'microempresa'], 'r01'],
        'R02' => ['Alto consumo en la franja pico',          ['hogar'],                 'r02'],
        'R03' => ['Aire acondicionado con demasiadas horas', ['hogar', 'microempresa'], 'r03'],
        'R04' => ['Ajuste del aire acondicionado',           ['hogar', 'microempresa'], 'r04'],
        'R05' => ['Bombillos de alto consumo',               ['hogar'],                 'r05'],
        'R06' => ['Iluminación encendida fuera de atención', ['microempresa'],          'r06'],
        'R07' => ['Duchas eléctricas largas',                ['hogar'],                 'r07'],
        'R08' => ['Plancha en una sola tanda',               ['hogar'],                 'r08'],
        'R09' => ['Vitrinas comerciales',                    ['microempresa'],          'r09'],
        'R10' => ['Computadores fuera de atención',          ['microempresa'],          'r10'],
        'R11' => ['Nevera de alto consumo',                  ['hogar', 'microempresa'], 'r11'],
        'R12' => ['Consumo diario sobre tu límite',          ['hogar', 'microempresa'], 'r12'],
        'R13' => ['Equipo que domina el consumo',            ['hogar', 'microempresa'], 'r13'],
        'R14' => ['Consumo sobre el tope de subsistencia',   ['hogar'],                 'r14'],
        'R15' => ['Alto consumo fuera del horario de atención', ['microempresa'],       'r15'],
    ];

    public function __construct(array $cfg)
    {
        $this->cfg    = $cfg;
        $this->modelo = new Modelo($cfg);
        $this->tarifa = new Tarifa($cfg['tarifas']);
        $this->u      = $cfg['umbrales'];
    }

    /** Catálogo de reglas: id => [nombre, perfiles]. */
    public static function catalogo(): array
    {
        $out = [];
        foreach (self::REGLAS as $id => [$nombre, $perfiles]) {
            $out[$id] = ['nombre' => $nombre, 'perfiles' => $perfiles];
        }
        return $out;
    }

    /**
     * Evalúa las reglas del perfil y devuelve las recomendaciones ordenadas:
     * primero las de menor esfuerzo (Tabla 5: si la disposición a ahorrar es baja)
     * y, a igual esfuerzo, las de mayor ahorro.
     */
    public function evaluar(array $ctx): array
    {
        $perfil = $ctx['perfil'] === 'microempresa' ? 'microempresa' : 'hogar';
        $equipos = $ctx['equipos'] ?? [];

        $total = 0.0;
        foreach ($equipos as $eq) {
            $total += $this->modelo->kwhMes($eq);
        }
        $ctx['_total_kwh_mes'] = $total;
        $ctx['perfil'] = $perfil;

        $recs = [];
        foreach (self::REGLAS as $id => [$nombre, $perfiles, $metodo]) {
            if (!in_array($perfil, $perfiles, true)) {
                continue;
            }
            foreach ($this->$metodo($ctx) as $r) {
                $recs[] = $this->completar($id, $nombre, $r, $ctx);
            }
        }

        usort($recs, static function (array $a, array $b): int {
            return [$a['esfuerzo'], -$a['ahorro_cop_mes']] <=> [$b['esfuerzo'], -$b['ahorro_cop_mes']];
        });
        return $recs;
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /** Ahorro en pesos de dejar de consumir $kwh al mes, respetando los tramos de la tarifa. */
    private function ahorroCop(array $ctx, float $kwh): float
    {
        $total = $ctx['_total_kwh_mes'];
        return max(0.0, $this->tarifa->costoMes($ctx['perfil'], $ctx['estrato'] ?? null, $total)
                      - $this->tarifa->costoMes($ctx['perfil'], $ctx['estrato'] ?? null, max(0.0, $total - $kwh)));
    }

    private function completar(string $id, string $nombre, array $r, array $ctx): array
    {
        $kwh = max(0.0, (float) ($r['kwh'] ?? 0.0));
        return [
            'regla_id'       => $id,
            'regla_nombre'   => $nombre,
            'equipo_id'      => $r['equipo']['id'] ?? null,
            'equipo_nombre'  => $r['equipo']['nombre'] ?? null,
            'titulo'         => $r['titulo'],
            'mensaje'        => $r['mensaje'],
            'explicacion'    => sprintf('Regla %s (%s): %s', $id, $nombre, $r['porque']),
            'franja'         => $r['franja'] ?? null,
            'ahorro_kwh_mes' => round($kwh, 2),
            'ahorro_cop_mes' => round($this->ahorroCop($ctx, $kwh), 0),
            'tipo_beneficio' => $r['beneficio'] ?? 'ahorro',
            'esfuerzo'       => (int) ($r['esfuerzo'] ?? 1),
        ];
    }

    private function nombreFranja(string $clave): string
    {
        return $this->cfg['franjas'][$clave]['nombre'] ?? $clave;
    }

    private static function h(float $horas): string
    {
        return rtrim(rtrim(number_format($horas, 1, ',', ''), '0'), ',') . ' h';
    }

    private static function kwh(float $v): string
    {
        return number_format($v, 1, ',', '.') . ' kWh';
    }

    // ------------------------------------------------------------------
    // Reglas. Cada una devuelve una lista de recomendaciones "en bruto".
    // ------------------------------------------------------------------

    /** R01: equipos que quedan conectados sin usarse. */
    private function r01(array $ctx): array
    {
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            $reposoW = $this->modelo->potenciaReposo($eq);
            $horasCorte = min((float) $this->u['standby_horas_corte'], 24.0 - (float) $eq['horas_dia']);
            if ($reposoW <= 0 || $horasCorte <= 0) {
                continue;
            }
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Desconecta «' . $eq['nombre'] . '» cuando no lo uses',
                'mensaje' => 'Este equipo sigue gastando energía en reposo. Desconéctalo o usa una regleta con interruptor, sobre todo en la noche.',
                'porque'  => sprintf('lo marcaste como equipo que queda conectado sin usarse y gasta %s W en reposo; con %s de corte al día se evita este consumo.',
                                     number_format($reposoW, 0, ',', '.'), self::h($horasCorte)),
                'kwh'     => $reposoW / 1000.0 * $horasCorte * 30.0,
                'franja'  => 'noche',
                'esfuerzo' => 1,
            ];
        }
        return $out;
    }

    /** R02: equipo de alto consumo que se usa en la franja de mayor demanda. */
    private function r02(array $ctx): array
    {
        $pico = $this->cfg['franja_pico'];
        $movibles = ['lavadora', 'plancha', 'horno', 'ducha', 'otro'];
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            if (!in_array($eq['categoria'], $movibles, true)
                || $this->modelo->potenciaTotal($eq) < (float) $this->u['alto_consumo_w']
                || $this->modelo->cobertura($eq)[$pico] <= 0) {
                continue;
            }
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Mueve «' . $eq['nombre'] . '» fuera de la ' . strtolower($this->nombreFranja($pico)),
                'mensaje' => 'Usa este equipo en la mañana o en la tarde. Con tarifa plana no baja la factura, pero reduce la carga en la hora de mayor demanda.',
                'porque'  => sprintf('es un equipo de alto consumo (%s W) y lo usas en la franja pico (%s).',
                                     number_format($this->modelo->potenciaTotal($eq), 0, ',', '.'), $this->nombreFranja($pico)),
                'kwh'     => 0.0,
                'franja'  => $pico,
                'beneficio' => 'demanda',
                'esfuerzo' => 2,
            ];
        }
        return $out;
    }

    /** R03: aire acondicionado por encima de las horas razonables. */
    private function r03(array $ctx): array
    {
        $max = (float) ($ctx['perfil'] === 'microempresa' ? $this->u['aire_horas_max_micro'] : $this->u['aire_horas_max_hogar']);
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            if ($eq['categoria'] !== 'aire' || (float) $eq['horas_dia'] <= $max) {
                continue;
            }
            $exceso = (float) $eq['horas_dia'] - $max;
            $factor = (float) $this->modelo->categoria('aire')['factor'];
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Reduce las horas de «' . $eq['nombre'] . '»',
                'mensaje' => sprintf('Intenta usarlo máximo %s al día y apágalo cuando salgas de la habitación o del local.', self::h($max)),
                'porque'  => sprintf('lo usas %s al día y el umbral para tu perfil es %s.', self::h((float) $eq['horas_dia']), self::h($max)),
                'kwh'     => $exceso * $this->modelo->potenciaTotal($eq) * $factor / 1000.0 * 30.0,
                'franja'  => $eq['franja'],
                'esfuerzo' => 2,
            ];
        }
        return $out;
    }

    /** R04: termostato más alto o ventilador. */
    private function r04(array $ctx): array
    {
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            if ($eq['categoria'] !== 'aire' || (float) $eq['horas_dia'] <= 0) {
                continue;
            }
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Ajusta el termostato de «' . $eq['nombre'] . '»',
                'mensaje' => 'Programa el termostato en 24 °C o combina el aire con un ventilador para reducir su trabajo.',
                'porque'  => sprintf('el aire acondicionado es de los equipos que más consumen; se asume un ahorro de %d %% al ajustar su uso (supuesto configurable).',
                                     (int) round($this->u['ahorro_termostato_pct'] * 100)),
                'kwh'     => $this->modelo->kwhMes($eq) * (float) $this->u['ahorro_termostato_pct'],
                'franja'  => $eq['franja'],
                'esfuerzo' => 1,
            ];
        }
        return $out;
    }

    /** R05: bombillos incandescentes u otros de alta potencia. */
    private function r05(array $ctx): array
    {
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            if ($eq['categoria'] !== 'iluminacion' || (float) $eq['potencia_w'] < (float) $this->u['incandescente_w_min']) {
                continue;
            }
            $led = (float) $this->u['led_w'];
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Cambia «' . $eq['nombre'] . '» por bombillos LED',
                'mensaje' => sprintf('Un bombillo LED equivalente gasta cerca de %d W en lugar de %d W.', (int) $led, (int) $eq['potencia_w']),
                'porque'  => sprintf('cada bombillo gasta %d W y el umbral para sugerir LED es %d W.', (int) $eq['potencia_w'], (int) $this->u['incandescente_w_min']),
                'kwh'     => ((float) $eq['potencia_w'] - $led) * (int) $eq['cantidad'] * (float) $eq['horas_dia'] / 1000.0 * 30.0,
                'franja'  => $eq['franja'],
                'esfuerzo' => 2,
            ];
        }
        return $out;
    }

    /** R06 (microempresa): iluminación encendida más horas de las que atiende. */
    private function r06(array $ctx): array
    {
        $aten = (float) $this->u['horas_atencion'];
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            if ($eq['categoria'] !== 'iluminacion' || (float) $eq['horas_dia'] <= $aten) {
                continue;
            }
            $exceso = (float) $eq['horas_dia'] - $aten;
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Apaga «' . $eq['nombre'] . '» cuando el local esté cerrado',
                'mensaje' => 'Revisa el horario de encendido y apaga la iluminación que no se necesita al cerrar.',
                'porque'  => sprintf('la iluminación está encendida %s al día y tu atención dura cerca de %s.', self::h((float) $eq['horas_dia']), self::h($aten)),
                'kwh'     => $exceso * $this->modelo->potenciaTotal($eq) / 1000.0 * 30.0,
                'franja'  => $eq['franja'],
                'esfuerzo' => 1,
            ];
        }
        return $out;
    }

    /** R07: duchas eléctricas largas. */
    private function r07(array $ctx): array
    {
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            $min = (float) $eq['horas_dia'] * 60.0;
            if ($eq['categoria'] !== 'ducha' || $min < (float) $this->u['ducha_min_umbral']) {
                continue;
            }
            $recorte = (float) $this->u['ducha_min_reducir'];
            $factor = (float) $this->modelo->categoria('ducha')['factor'];
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Acorta la ducha eléctrica en ' . (int) $recorte . ' minutos',
                'mensaje' => 'La ducha eléctrica es de los equipos de mayor potencia. Recortar el tiempo de uso es un ahorro directo.',
                'porque'  => sprintf('la usas cerca de %d min al día y el umbral para sugerir recorte es %d min.', (int) round($min), (int) $this->u['ducha_min_umbral']),
                'kwh'     => $this->modelo->potenciaTotal($eq) * $factor * ($recorte / 60.0) / 1000.0 * 30.0,
                'franja'  => $eq['franja'],
                'esfuerzo' => 2,
            ];
        }
        return $out;
    }

    /** R08: plancha en una sola tanda. */
    private function r08(array $ctx): array
    {
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            if ($eq['categoria'] !== 'plancha' || (float) $eq['horas_dia'] < (float) $this->u['plancha_horas_umbral']) {
                continue;
            }
            $factor = (float) $this->modelo->categoria('plancha')['factor'];
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Plancha en una sola tanda',
                'mensaje' => 'Junta la ropa, plancha de una vez y desconecta la plancha apenas termines.',
                'porque'  => sprintf('la usas %s al día; planchar en una tanda evita cerca de %s diarias de calentamiento (supuesto configurable).',
                                     self::h((float) $eq['horas_dia']), self::h((float) $this->u['plancha_ahorro_horas'])),
                'kwh'     => $this->modelo->potenciaTotal($eq) * $factor * (float) $this->u['plancha_ahorro_horas'] / 1000.0 * 30.0,
                'franja'  => $eq['franja'],
                'esfuerzo' => 1,
            ];
        }
        return $out;
    }

    /** R09 (microempresa): vitrinas y neveras comerciales. */
    private function r09(array $ctx): array
    {
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            if ($eq['categoria'] !== 'vitrina') {
                continue;
            }
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Cuida «' . $eq['nombre'] . '»',
                'mensaje' => 'Revisa los sellos de las puertas, no la llenes en exceso y apaga la luz interna o cubre la vitrina al cerrar.',
                'porque'  => sprintf('las vitrinas trabajan las 24 horas; se asume un ahorro de %d %% con buenas prácticas (supuesto configurable).',
                                     (int) round($this->u['ahorro_vitrina_pct'] * 100)),
                'kwh'     => $this->modelo->kwhMes($eq) * (float) $this->u['ahorro_vitrina_pct'],
                'franja'  => 'noche',
                'esfuerzo' => 1,
            ];
        }
        return $out;
    }

    /** R10 (microempresa): computadores encendidos más allá de la atención. */
    private function r10(array $ctx): array
    {
        $aten = (float) $this->u['horas_atencion'];
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            if ($eq['categoria'] !== 'computador' || (float) $eq['horas_dia'] <= $aten) {
                continue;
            }
            $exceso = (float) $eq['horas_dia'] - $aten;
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Apaga «' . $eq['nombre'] . '» al terminar la jornada',
                'mensaje' => 'Apaga los computadores y sus pantallas al cerrar en lugar de dejarlos en suspensión.',
                'porque'  => sprintf('permanecen encendidos %s al día y la atención dura cerca de %s.', self::h((float) $eq['horas_dia']), self::h($aten)),
                'kwh'     => $exceso * $this->modelo->potenciaTotal($eq) / 1000.0 * 30.0,
                'franja'  => $eq['franja'],
                'esfuerzo' => 1,
            ];
        }
        return $out;
    }

    /** R11: nevera de potencia alta. */
    private function r11(array $ctx): array
    {
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            if ($eq['categoria'] !== 'nevera' || (float) $eq['potencia_w'] <= (float) $this->u['nevera_w_alta']) {
                continue;
            }
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Revisa la eficiencia de «' . $eq['nombre'] . '»',
                'mensaje' => 'Su potencia es alta. Revisa los sellos y, si es antigua, considera reemplazarla por una de mejor etiqueta RETIQ.',
                'porque'  => sprintf('su potencia es %d W y el umbral es %d W; se asume un ahorro de %d %% al reemplazarla (supuesto configurable).',
                                     (int) $eq['potencia_w'], (int) $this->u['nevera_w_alta'], (int) round($this->u['ahorro_reemplazo_pct'] * 100)),
                'kwh'     => $this->modelo->kwhMes($eq) * (float) $this->u['ahorro_reemplazo_pct'],
                'franja'  => null,
                'esfuerzo' => 3,
            ];
        }
        return $out;
    }

    /** R12: el consumo del día supera el límite fijado por el usuario. */
    private function r12(array $ctx): array
    {
        $limite = (float) ($ctx['limite_diario_kwh'] ?? 0);
        $hoy = (float) ($ctx['consumo_hoy_kwh'] ?? 0);
        if ($limite <= 0 || $hoy <= $limite) {
            return [];
        }
        return [[
            'equipo'  => null,
            'titulo'  => 'Superaste tu límite diario de consumo',
            'mensaje' => 'Hoy llevas más energía de la que fijaste como límite. Revisa qué equipos tienes encendidos.',
            'porque'  => sprintf('llevas %s hoy y tu límite diario es %s.', self::kwh($hoy), self::kwh($limite)),
            'kwh'     => 0.0,
            'beneficio' => 'alerta',
            'esfuerzo' => 1,
        ]];
    }

    /** R13: un equipo concentra buena parte del consumo. */
    private function r13(array $ctx): array
    {
        $total = $ctx['_total_kwh_mes'];
        if ($total <= 0 || count($ctx['equipos']) < 2) {
            return [];
        }
        $mayor = null;
        $mayorKwh = 0.0;
        foreach ($ctx['equipos'] as $eq) {
            $k = $this->modelo->kwhMes($eq);
            if ($k > $mayorKwh) {
                $mayorKwh = $k;
                $mayor = $eq;
            }
        }
        $peso = $mayorKwh / $total;
        if ($mayor === null || $peso < (float) $this->u['peso_equipo_dominante']) {
            return [];
        }
        return [[
            'equipo'  => $mayor,
            'titulo'  => 'Prioriza «' . $mayor['nombre'] . '»',
            'mensaje' => 'Es el equipo que más pesa en tu factura. Cualquier mejora en su uso se nota más que en los demás.',
            'porque'  => sprintf('concentra el %d %% de tu consumo mensual estimado y el umbral es %d %%.',
                                 (int) round($peso * 100), (int) round($this->u['peso_equipo_dominante'] * 100)),
            'kwh'     => $mayorKwh * (float) $this->u['ahorro_dominante_pct'],
            'franja'  => $mayor['franja'],
            'esfuerzo' => 2,
        ]];
    }

    /** R14 (hogares estratos 1 a 3): consumo sobre el tope de subsistencia. */
    private function r14(array $ctx): array
    {
        $estrato = $ctx['estrato'] ?? null;
        if (!$this->tarifa->aplicaSubsistencia('hogar', $estrato)) {
            return [];
        }
        $total = $ctx['_total_kwh_mes'];
        $tope = (float) $this->tarifa->tope();
        if ($total <= $tope) {
            return [];
        }
        $exceso = $total - $tope;
        $reducir = min($exceso, $total * (float) $this->u['ahorro_reduccion_pct']);
        return [[
            'equipo'  => null,
            'titulo'  => 'Tu consumo supera el tope de subsistencia',
            'mensaje' => 'Los kWh que pasan del tope se cobran a un precio mayor. Bajar el consumo hacia el tope reduce la factura más que en otros meses.',
            'porque'  => sprintf('tu consumo mensual estimado es %s y el tope de subsistencia es %d kWh, así que %s se cobran a tarifa plena.',
                                 self::kwh($total), (int) $tope, self::kwh($exceso)),
            'kwh'     => $reducir,
            'esfuerzo' => 2,
        ]];
    }

    /** R15 (microempresa): equipos de alto consumo que corren fuera del horario de atención. */
    private function r15(array $ctx): array
    {
        $atencion = $this->cfg['franjas_atencion'];
        $fuera = array_values(array_diff(array_keys($this->cfg['franjas']), $atencion));
        $out = [];
        foreach ($ctx['equipos'] as $eq) {
            if (in_array($eq['categoria'], ['nevera', 'vitrina'], true)
                || $this->modelo->potenciaTotal($eq) < (float) $this->u['alto_consumo_w']) {
                continue;
            }
            $kwh = $this->modelo->kwhMesEnFranjas($eq, $fuera);
            // Se descarta el consumo de reposo: eso lo cubre R01.
            $cob = $this->modelo->cobertura($eq);
            $franjaFuera = null;
            foreach ($fuera as $f) {
                if ($cob[$f] > 0) { $franjaFuera = $f; break; }
            }
            if ($franjaFuera === null || $kwh <= 0) {
                continue;
            }
            $out[] = [
                'equipo'  => $eq,
                'titulo'  => 'Revisa si «' . $eq['nombre'] . '» debe funcionar con el local cerrado',
                'mensaje' => 'Este equipo de alto consumo trabaja fuera del horario en que atiendes. Si no es necesario, apágalo o prográmalo.',
                'porque'  => sprintf('consume %s al mes en franjas en las que normalmente no atiendes (%s).',
                                     self::kwh($kwh), implode(', ', array_map(fn($f) => strtolower($this->nombreFranja($f)), $fuera))),
                'kwh'     => $kwh,
                'franja'  => $franjaFuera,
                'esfuerzo' => 1,
            ];
        }
        return $out;
    }
}
