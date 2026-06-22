<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Ciclo de cobrança da academia: a janela [inicio, fim) entre dois fechamentos.
 * Fechamento é todo dia 5. Intervalo semiaberto: pagamento em 05/07 cai no
 * ciclo seguinte, não neste.
 */
final class CicloCobranca
{
    public const DIA_FECHAMENTO = 5;

    public readonly CarbonImmutable $inicio; // dia 5, 00:00
    public readonly CarbonImmutable $fim;    // dia 5 do mês seguinte, 00:00 (exclusivo)

    private function __construct(CarbonImmutable $inicio)
    {
        $this->inicio = $inicio->startOfDay();
        $this->fim = $this->inicio->addMonth();
    }

    public static function doMes(int $ano, int $mes): self
    {
        return new self(CarbonImmutable::create($ano, $mes, self::DIA_FECHAMENTO));
    }

    /** "2026-06" => ciclo [2026-06-05, 2026-07-05) */
    public static function deReferencia(string $referencia): self
    {
        [$ano, $mes] = array_map('intval', explode('-', $referencia));
        return self::doMes($ano, $mes);
    }

    public static function queContem(CarbonImmutable $data): self
    {
        $dia5DoMes = $data->startOfMonth()->day(self::DIA_FECHAMENTO);

        return $data->lt($dia5DoMes)
            ? new self($dia5DoMes->subMonth())
            : new self($dia5DoMes);
    }

    public static function atual(): self
    {
        return self::queContem(CarbonImmutable::now());
    }

    public function anterior(): self
    {
        return new self($this->inicio->subMonth());
    }

    public function proximo(): self
    {
        return new self($this->inicio->addMonth());
    }

    /** Identificador pra querystring/label, ex: "2026-06". */
    public function referencia(): string
    {
        return $this->inicio->format('Y-m');
    }
}