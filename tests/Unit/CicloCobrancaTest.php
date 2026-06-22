<?php

namespace Tests\Unit;

use App\Support\CicloCobranca;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class CicloCobrancaTest extends TestCase
{
    public function test_mapeia_o_ciclo_de_junho_como_05_06_a_05_07(): void
    {
        $ciclo = CicloCobranca::doMes(2026, 6);

        $this->assertSame('2026-06-05', $ciclo->inicio->toDateString());
        $this->assertSame('2026-07-05', $ciclo->fim->toDateString());
    }

    public function test_janela_e_semiaberta_nos_limites_do_dia_5(): void
    {
        $ciclo = CicloCobranca::doMes(2026, 6);

        $this->assertTrue(CarbonImmutable::parse('2026-06-04')->lt($ciclo->inicio));
        $this->assertTrue(CarbonImmutable::parse('2026-06-05')->gte($ciclo->inicio));
        $this->assertTrue(CarbonImmutable::parse('2026-07-04')->lt($ciclo->fim));
        $this->assertFalse(CarbonImmutable::parse('2026-07-05')->lt($ciclo->fim));
    }

    public function test_descobre_o_ciclo_que_contem_uma_data(): void
    {
        $this->assertSame('2026-05', CicloCobranca::queContem(CarbonImmutable::parse('2026-06-04'))->referencia());
        $this->assertSame('2026-06', CicloCobranca::queContem(CarbonImmutable::parse('2026-06-05'))->referencia());
        $this->assertSame('2026-06', CicloCobranca::queContem(CarbonImmutable::parse('2026-06-20'))->referencia());
    }

    public function test_navega_anterior_proximo_e_vira_o_ano(): void
    {
        $junho = CicloCobranca::doMes(2026, 6);
        $this->assertSame('2026-05', $junho->anterior()->referencia());
        $this->assertSame('2026-07', $junho->proximo()->referencia());

        $dezembro = CicloCobranca::doMes(2025, 12);
        $this->assertSame('2026-01-05', $dezembro->fim->toDateString());
        $this->assertSame('2026-01', $dezembro->proximo()->referencia());
    }
}