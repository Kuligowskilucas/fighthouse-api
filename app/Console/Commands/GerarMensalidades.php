<?php

namespace App\Console\Commands;

use App\Services\GeradorDeMensalidades;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GerarMensalidades extends Command
{
    protected $signature = 'mensalidades:gerar
                            {--mes= : Mês de referência no formato YYYY-MM (default: mês atual)}';

    protected $description = 'Gera mensalidades do mês para todos os alunos ativos';

    public function handle(GeradorDeMensalidades $gerador): int
    {
        $mesReferencia = $this->parseMesReferencia();

        if ($mesReferencia === false) {
            $this->error('Formato de mês inválido. Use YYYY-MM (ex: 2026-11).');
            return self::FAILURE;
        }

        $this->info("Gerando mensalidades para: {$mesReferencia->format('m/Y')}");

        $resultado = $gerador->gerar($mesReferencia);

        $this->info('Concluído!');
        $this->line("  Mensalidades criadas: {$resultado['criadas']}");
        $this->line("  Mensalidades ignoradas (já existiam): {$resultado['ignoradas']}");

        return self::SUCCESS;
    }

    private function parseMesReferencia(): Carbon|false
    {
        $mes = $this->option('mes');

        if (! $mes) {
            return Carbon::now();
        }

        try {
            return Carbon::createFromFormat('Y-m', $mes);
        } catch (\Exception) {
            return false;
        }
    }
}