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
        Schema::create('mensalidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained()->cascadeOnDelete();
            $table->date('mes_referencia')
                ->comment('Sempre dia 1 do mês, ex: 2026-04-01');
            $table->decimal('valor', 8, 2);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->string('forma_pagamento', 20)->nullable()
                ->comment('pix, dinheiro, cartao, transferencia');
            $table->text('observacoes')->nullable();
            $table->timestamps();
    
            $table->unique(['aluno_id', 'mes_referencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensalidades');
    }
};
