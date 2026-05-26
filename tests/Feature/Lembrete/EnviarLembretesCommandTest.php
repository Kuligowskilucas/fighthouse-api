<?php

namespace Tests\Feature\Lembrete;

use App\Mail\LembreteVencimento;
use App\Models\Aluno;
use App\Models\Mensalidade;
use Carbon\Carbon;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnviarLembretesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanoSeeder::class);

        config(['mail.pix_chave' => '11999999999']);
    }

    public function test_envia_email_para_aluno_com_vencimento_em_x_dias(): void
    {
        Mail::fake();

        $aluno = Aluno::factory()->create([
            'email' => 'aluno@teste.com',
            'ativo' => true,
        ]);

        Mensalidade::factory()->create([
            'aluno_id'       => $aluno->id,
            'data_vencimento' => Carbon::today()->addDays(3),
            'data_pagamento'  => null,
        ]);

        $this->artisan('lembretes:enviar')
            ->expectsOutputToContain('Enviados: 1')
            ->assertSuccessful();

        Mail::assertSent(LembreteVencimento::class, function ($mail) use ($aluno) {
            return $mail->hasTo($aluno->email);
        });
    }

    public function test_nao_envia_para_mensalidade_ja_paga(): void
    {
        Mail::fake();

        $aluno = Aluno::factory()->create(['email' => 'aluno@teste.com', 'ativo' => true]);

        Mensalidade::factory()->create([
            'aluno_id'        => $aluno->id,
            'data_vencimento' => Carbon::today()->addDays(3),
            'data_pagamento'  => Carbon::today()->subDay(),
        ]);

        $this->artisan('lembretes:enviar')
            ->expectsOutputToContain('Nenhum lembrete para enviar hoje.')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_nao_envia_para_aluno_sem_email(): void
    {
        Mail::fake();

        $aluno = Aluno::factory()->create(['email' => null, 'ativo' => true]);

        Mensalidade::factory()->create([
            'aluno_id'        => $aluno->id,
            'data_vencimento' => Carbon::today()->addDays(3),
            'data_pagamento'  => null,
        ]);

        $this->artisan('lembretes:enviar')
            ->expectsOutputToContain('Nenhum lembrete para enviar hoje.')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_nao_envia_para_vencimento_em_data_diferente(): void
    {
        Mail::fake();

        $aluno = Aluno::factory()->create(['email' => 'aluno@teste.com', 'ativo' => true]);

        Mensalidade::factory()->create([
            'aluno_id'        => $aluno->id,
            'data_vencimento' => Carbon::today()->addDays(10),
            'data_pagamento'  => null,
        ]);

        $this->artisan('lembretes:enviar')
            ->expectsOutputToContain('Nenhum lembrete para enviar hoje.')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_sem_pix_chave_envia_sem_bloco_pix(): void
    {
        Mail::fake();
    
        config(['mail.pix_chave' => null]);
    
        $aluno = Aluno::factory()->create(['email' => 'aluno@teste.com', 'ativo' => true]);
    
        Mensalidade::factory()->create([
            'aluno_id'        => $aluno->id,
            'data_vencimento' => Carbon::today()->addDays(3),
            'data_pagamento'  => null,
        ]);
    
        $this->artisan('lembretes:enviar')
            ->expectsOutputToContain('Enviados: 1')
            ->assertSuccessful();
    
        Mail::assertSent(LembreteVencimento::class);
    }
    
    public function test_aceita_option_dias_customizado(): void
    {
        Mail::fake();

        $aluno = Aluno::factory()->create(['email' => 'aluno@teste.com', 'ativo' => true]);

        Mensalidade::factory()->create([
            'aluno_id'        => $aluno->id,
            'data_vencimento' => Carbon::today()->addDays(7),
            'data_pagamento'  => null,
        ]);

        $this->artisan('lembretes:enviar --dias=7')
            ->expectsOutputToContain('Enviados: 1')
            ->assertSuccessful();

        Mail::assertSent(LembreteVencimento::class);
    }
}