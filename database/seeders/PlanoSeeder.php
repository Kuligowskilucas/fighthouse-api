<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class PlanoSeeder extends Seeder
{
    public function run(): void
    {
        $turmas = [
            [
                'nome'        => 'Boxe',
                'valor'       => 129.90,
                'dias_semana' => 'Ter/Qui',
                'horarios'    => ['12:15', '18:00', '20:00'],
            ],
            [
                'nome'        => 'Muay Thai',
                'valor'       => 169.90,
                'dias_semana' => 'Seg/Qua/Sex',
                'horarios'    => ['06:00', '09:30', '12:15', '15:00', '16:30', '18:00', '20:00'],
            ],
            [
                'nome'        => 'Muay Thai Feminino',
                'valor'       => 129.90,
                'dias_semana' => 'Ter/Qui',
                'horarios'    => ['19:00'],
            ],
            [
                'nome'        => 'Jiu-Jitsu',
                'valor'       => 169.90,
                'dias_semana' => 'Seg/Qua/Sex',
                'horarios'    => ['19:00'],
            ],
            [
                'nome'        => 'Livre',
                'valor'       => 249.90,
                'dias_semana' => null,
                'horarios'    => [],
            ],
        ];

        foreach ($turmas as $turmaData) {
            $plano = Plano::updateOrCreate(
                ['nome' => $turmaData['nome']],
                ['valor' => $turmaData['valor'], 'dias_semana' => $turmaData['dias_semana']]
            );

            $plano->horarios()->delete();
            foreach ($turmaData['horarios'] as $horario) {
                $plano->horarios()->create(['horario' => $horario]);
            }
        }
    }
}