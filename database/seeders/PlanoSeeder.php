<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class PlanoSeeder extends Seeder
{
    public function run(): void
    {
        $planos = [
            [
                'nome' => 'Plano Livre',
                'valor' => 249.90,
                'frequencia_semanal' => null,
            ],
            [
                'nome' => 'Muaythai ou Jiu Jitsu',
                'valor' => 169.90,
                'frequencia_semanal' => 3,
            ],
            [
                'nome' => 'Boxe, Muaythai Feminino ou Jiu Jitsu Feminino',
                'valor' => 129.90,
                'frequencia_semanal' => 2,
            ],
        ];

        foreach ($planos as $plano) {
            Plano::updateOrCreate(['nome' => $plano['nome']], $plano);
        }
    }
}