<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->string('horario_treino', 100)->nullable()->after('data_matricula');
        });
    }
 
    public function down(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->dropColumn('horario_treino');
        });
    }
};
 