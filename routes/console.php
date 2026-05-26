<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;

Artisan::command('inspire', function () {
$this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('mensalidades:gerar')->monthlyOn(1, '00:00');

Schedule::command('lembretes:enviar')
    ->dailyAt('11:00')
    ->timezone('America/Sao_Paulo');