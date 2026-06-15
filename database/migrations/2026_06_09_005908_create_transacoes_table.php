<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mensalidade_id')
                ->constrained('mensalidades')
                ->cascadeOnDelete();

            $table->string('status')->default('pending')->index();
            $table->string('preference_id')->nullable()->index();
            $table->string('payment_id')->nullable()->index();
            $table->decimal('valor', 10, 2);
            $table->jsonb('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacoes');
    }
};