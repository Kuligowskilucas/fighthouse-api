<?php

namespace App\Console\Commands;

use App\Mail\LembreteVencimento;
use App\Models\Mensalidade;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarLembretes extends Command
{
    protected $signature = 'lembretes:enviar
                            {--dias= : Dias antes do vencimento para enviar (default: config LEMBRETE_DIAS_ANTES)}';

    protected $description = 'Envia lembretes de vencimento por email para alunos com mensalidade próxima';

    public function handle(): int
    {
        $diasAntes = (int) ($this->option('dias') ?? config('mail.lembrete_dias_antes', 3));
        $dataAlvo  = Carbon::today()->addDays($diasAntes)->toDateString();
        $pixChave  = config('mail.pix_chave') ?? '';

        $this->info("Enviando lembretes para mensalidades com vencimento em: {$dataAlvo}");

        $mensalidades = Mensalidade::query()
            ->with('aluno')
            ->whereNull('data_pagamento')
            ->whereDate('data_vencimento', $dataAlvo)
            ->whereHas('aluno', fn ($q) => $q->whereNotNull('email')->where('email', '!=', '')->where('ativo', true))
            ->get();

        if ($mensalidades->isEmpty()) {
            $this->line('Nenhum lembrete para enviar hoje.');
            return self::SUCCESS;
        }

        $enviados = 0;
        $falhas   = 0;
        $atrasada = $diasAntes < 0;

        foreach ($mensalidades as $mensalidade) {
            try {
                Mail::to($mensalidade->aluno->email)
                    ->send(new LembreteVencimento($mensalidade, $pixChave, $atrasada));

                $this->line("  ✓ {$mensalidade->aluno->nome} <{$mensalidade->aluno->email}>");
                $enviados++;
            } catch (\Exception $e) {
                $this->warn("  ✗ {$mensalidade->aluno->nome}: {$e->getMessage()}");
                $falhas++;
            }
        }

        $this->info("Concluído! Enviados: {$enviados} | Falhas: {$falhas}");

        return $falhas === 0 ? self::SUCCESS : self::FAILURE;
    }
}