<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planos', function (Blueprint $table) {
            $table->string('dias_semana')->nullable()->after('ativo');
        });

        $map = [
            'Boxe'               => 'Ter/Qui',
            'Muay Thai Feminino' => 'Ter/Qui',
            'Muay Thai'          => 'Seg/Qua/Sex',
            'Jiu-Jitsu'          => 'Seg/Qua/Sex',
        ];

        foreach ($map as $nome => $dias) {
            DB::table('planos')->where('nome', $nome)->update(['dias_semana' => $dias]);
        }
    }

    public function down(): void
    {
        Schema::table('planos', function (Blueprint $table) {
            $table->dropColumn('dias_semana');
        });
    }
};