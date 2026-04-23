<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alunos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('telefone', 20)->unique()
                ->comment('Normalizado: só números com DDI+DDD');
            $table->string('email')->nullable();
            $table->foreignId('plano_id')->constrained()->restrictOnDelete();
            $table->decimal('valor_personalizado', 8, 2)->nullable()
                ->comment('Sobrescreve o valor do plano quando preenchido');
            $table->unsignedTinyInteger('dia_vencimento');
            $table->date('data_matricula');
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alunos');
    }
};
