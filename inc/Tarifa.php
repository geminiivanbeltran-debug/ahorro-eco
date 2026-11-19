<?php
declare(strict_types=1);

/**
 * Tarifas de EMCALI por estrato (hogares) o sector comercial (microempresas).
 * Sin dependencias de base de datos: recibe la sección 'tarifas' de config.php.
 */
final class Tarifa
{
    public function __construct(private array $cfg) {}

    /** Hogares de estratos 1 a 3 tienen un tope de consumo de subsistencia con precio menor. */
    public function aplicaSubsistencia(string $perfil, ?int $estrato): bool
    {
        return $perfil === 'hogar' && $this->estrato($estrato) <= 3;
    }

    public function tope(): int
    {
        return (int) $this->cfg['subsistencia_kwh'];
    }

    /** Estrato válido de 1 a 6; si falta, se asume 3 (el más frecuente en hogares). */
    public function estrato(?int $estrato): int
    {
        return ($estrato !== null && $estrato >= 1 && $estrato <= 6) ? $estrato : 3;
    }

    /** Precios ['sub' => ..., 'sobre' => ...] que le corresponden al usuario. */
    public function precios(string $perfil, ?int $estrato): array
    {
        if ($perfil === 'microempresa') {
            return $this->cfg['comercial'];
        }
        return $this->cfg['residencial'][$this->estrato($estrato)];
    }

    /** Precio del siguiente kWh dado lo que el usuario ya consumió en el mes. */
    public function precioMarginal(string $perfil, ?int $estrato, float $kwhAcumMes): float
    {
        $p = $this->precios($perfil, $estrato);
        if ($this->aplicaSubsistencia($perfil, $estrato) && $kwhAcumMes < $this->tope()) {
            return (float) $p['sub'];
        }
        return (float) $p['sobre'];
    }

    /** Costo total en pesos de un consumo mensual (energía solamente). */
    public function costoMes(string $perfil, ?int $estrato, float $kwhMes): float
    {
        $p = $this->precios($perfil, $estrato);
        if ($this->aplicaSubsistencia($perfil, $estrato)) {
            $dentro = min($kwhMes, $this->tope());
            $sobre  = max(0.0, $kwhMes - $this->tope());
            return $dentro * (float) $p['sub'] + $sobre * (float) $p['sobre'];
        }
        return $kwhMes * (float) $p['sobre'];
    }

    /** Texto breve para mostrar la tarifa aplicada. */
    public function descripcion(string $perfil, ?int $estrato): string
    {
        $p = $this->precios($perfil, $estrato);
        $vig = $this->cfg['vigencia'];
        if ($perfil === 'microempresa') {
            return sprintf('Tarifa comercial EMCALI (%s): $%s por kWh', $vig, number_format($p['sobre'], 2, ',', '.'));
        }
        $e = $this->estrato($estrato);
        if ($this->aplicaSubsistencia($perfil, $estrato)) {
            return sprintf(
                'Estrato %d, EMCALI (%s): $%s por kWh hasta %d kWh y $%s por encima',
                $e, $vig, number_format($p['sub'], 2, ',', '.'), $this->tope(), number_format($p['sobre'], 2, ',', '.')
            );
        }
        return sprintf('Estrato %d, EMCALI (%s): $%s por kWh', $e, $vig, number_format($p['sobre'], 2, ',', '.'));
    }
}
