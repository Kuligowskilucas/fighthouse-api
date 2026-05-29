<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_plano', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plano_id')->constrained('planos')->cascadeOnDelete();
            $table->string('horario', 5);
            $table->timestamps();
        });

        $horariosPorNome = [
            'Boxe'               => ['12:15', '18:00', '20:00'],
            'Muay Thai Feminino' => ['19:00'],
            'Muay Thai'          => ['06:00', '09:30', '12:15', '15:00', '16:30', '18:00', '20:00'],
            'Jiu-Jitsu'          => ['19:00'],
        ];

        foreach ($horariosPorNome as $nome => $horarios) {
            $plano = DB::table('planos')->where('nome', $nome)->first();
            if (!$plano) continue;

            $now = now();
            DB::table('horarios_plano')->insert(
                array_map(fn($h) => [
                    'plano_id'   => $plano->id,
                    'horario'    => $h,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $horarios)
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_plano');
    }
};