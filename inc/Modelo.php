<?php
declare(strict_types=1);

/**
 * Modelo de consumo y simulador de sensores (capa de percepción simulada).
 *
 * Es un modelo de VALOR ESPERADO: a partir de la potencia, la cantidad y las
 * horas de uso que declara el usuario, calcula cuánto debería consumir cada
 * equipo en cada franja del día y genera lecturas con una pequeña variación
 * aleatoria. Sin dependencias de base de datos.
 *
 * Un equipo se asume "encendido" las horas_dia declaradas, empezando por su
 * franja principal y desbordando a las franjas siguientes (6 h por franja).
 * Cuando está apagado y deja_reposo = 1, consume la potencia de reposo (standby).
 */
final class Modelo
{
    private const ORDEN = ['madrugada', 'manana', 'tarde', 'noche'];

    public function __construct(private array $cfg) {}

    public function categoria(string $clave): array
    {
        return $this->cfg['categorias'][$clave] ?? $this->cfg['categorias']['otro'];
    }

    /** Franja del día (clave) para una hora 0..23. */
    public function franjaDeHora(int $hora): string
    {
        return self::ORDEN[intdiv(max(0, min(23, $hora)), 6)];
    }

    /**
     * Horas de uso que el equipo pasa dentro de cada franja.
     * @return array<string,float> franja => horas (0..6)
     */
    public function cobertura(array $eq): array
    {
        $horas = max(0.0, min(24.0, (float) $eq['horas_dia']));
        $cobertura = array_fill_keys(self::ORDEN, 0.0);
        $inicio = array_search($eq['franja'], self::ORDEN, true);
        $inicio = $inicio === false ? 3 : $inicio;
        for ($k = 0; $k < 4 && $horas > 0; $k++) {
            $f = self::ORDEN[($inicio + $k) % 4];
            $toma = min(6.0, $horas);
            $cobertura[$f] = $toma;
            $horas -= $toma;
        }
        return $cobertura;
    }

    /** Fracción de la franja en que el equipo está encendido (0..1). */
    public function fraccionEncendido(array $eq, string $franja): float
    {
        return $this->cobertura($eq)[$franja] / 6.0;
    }

    /** Potencia total de reposo (W) del equipo, todas las unidades. */
    public function potenciaReposo(array $eq): float
    {
        if (empty($eq['deja_reposo'])) {
            return 0.0;
        }
        return (float) $this->categoria($eq['categoria'])['standby'] * (int) $eq['cantidad'];
    }

    /** Potencia plena (W) del equipo, todas las unidades. */
    public function potenciaTotal(array $eq): float
    {
        return (float) $eq['potencia_w'] * (int) $eq['cantidad'];
    }

    /** Potencia media (W) del equipo en una franja del día. */
    public function potenciaMedia(array $eq, string $franja): float
    {
        $f = $this->fraccionEncendido($eq, $franja);
        $factor = (float) $this->categoria($eq['categoria'])['factor'];
        return $this->potenciaTotal($eq) * $factor * $f + $this->potenciaReposo($eq) * (1.0 - $f);
    }

    /** Consumo esperado del equipo en un día (kWh). */
    public function kwhDia(array $eq): float
    {
        $kwh = 0.0;
        foreach (self::ORDEN as $franja) {
            $kwh += $this->potenciaMedia($eq, $franja) / 1000.0 * 6.0;
        }
        return $kwh;
    }

    public function kwhMes(array $eq): float
    {
        return $this->kwhDia($eq) * 30.0;
    }

    /** Consumo mensual (kWh) que cae dentro de un conjunto de franjas. */
    public function kwhMesEnFranjas(array $eq, array $franjas): float
    {
        $kwh = 0.0;
        foreach ($franjas as $franja) {
            $kwh += $this->potenciaMedia($eq, $franja) / 1000.0 * 6.0;
        }
        return $kwh * 30.0;
    }

    /**
     * Genera una lectura simulada del equipo.
     *
     * @param int   $hora          hora simulada 0..23
     * @param int   $intervaloMin  minutos simulados que cubre la lectura
     * @param float $ruido         variación multiplicativa (1.0 = sin variación)
     * @return array{franja:string,potencia_w:float,consumo_kwh:float}
     */
    public function lectura(array $eq, int $hora, int $intervaloMin, float $ruido = 1.0): array
    {
        $franja = $this->franjaDeHora($hora);
        $potencia = $this->potenciaMedia($eq, $franja) * $ruido;
        return [
            'franja'      => $franja,
            'potencia_w'  => round($potencia, 2),
            'consumo_kwh' => round($potencia / 1000.0 * ($intervaloMin / 60.0), 5),
        ];
    }

    /** Variación aleatoria de ±8 %. */
    public static function ruidoAleatorio(): float
    {
        return 0.92 + (mt_rand() / mt_getrandmax()) * 0.16;
    }
}
