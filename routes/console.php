<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Verifikasi integritas akuntansi setiap malam.
// Hasil ketidaksesuaian ditulis ke log (storage/logs) dan command exit code 1.
Schedule::command('accounting:verify-integrity')
    ->dailyAt('02:00')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/accounting-integrity.log'));
