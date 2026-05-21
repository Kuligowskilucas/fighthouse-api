<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('email'); // 'admin' | 'professor' | 'aluno'
            $table->foreignId('aluno_id')
                ->nullable()
                ->after('role')
                ->constrained('alunos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['aluno_id']);
            $table->dropColumn(['role', 'aluno_id']);
        });
    }
};