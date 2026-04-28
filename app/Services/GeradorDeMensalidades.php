<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\Mensalidade;
use Carbon\Carbon;

class GeradorDeMensalidades
{
    /**
     * Gera mensalidades do mês de referência para todos os alunos ativos
     * que ainda não tenham mensalidade nesse mês.
     *
     * @param Carbon|null $mesReferencia Mês a gerar (default: mês atual)
     * @return array{criadas: int, ignoradas: int, mes_referencia: string}
     */
    public function gerar(?Carbon $mesReferencia = null): array
    {
        $mesReferencia = ($mesReferencia ?? Carbon::now())->copy()->startOfMonth();

        $criadas = 0;
        $ignoradas = 0;

        $alunos = Aluno::with('plano')
            ->where('ativo', true)
            ->where('data_matricula', '<=', $mesReferencia->copy()->endOfMonth())
            ->get();

        foreach ($alunos as $aluno) {
            $resultado = Mensalidade::firstOrCreate(
                [
                    'aluno_id' => $aluno->id,
                    'mes_referencia' => $mesReferencia,
                ],
                [
                    'valor' => $aluno->valorMensalidade(),
                    'data_vencimento' => $this->calcularDataVencimento($aluno, $mesReferencia),
                ]
            );

            if ($resultado->wasRecentlyCreated) {
                $criadas++;
            } else {
                $ignoradas++;
            }
        }

        return [
            'criadas' => $criadas,
            'ignoradas' => $ignoradas,
            'mes_referencia' => $mesReferencia->format('Y-m-d'),
        ];
    }

    /**
     * Calcula a data de vencimento, ajustando se o dia não existir no mês.
     * Ex: dia 31 em fevereiro vira último dia do mês.
     */
    private function calcularDataVencimento(Aluno $aluno, Carbon $mesReferencia): Carbon
    {
        $diaVencimento = min($aluno->dia_vencimento, $mesReferencia->daysInMonth);

        return $mesReferencia->copy()->day($diaVencimento);
    }
}