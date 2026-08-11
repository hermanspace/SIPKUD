<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup database penuh setiap malam (simpan 14 terbaru).
// File tersimpan di storage/app/backups dan dapat dikelola dari panel
// Super Admin (menu Backup Database).
Schedule::command('db:backup --keep=14')
    ->dailyAt('01:30')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/db-backup.log'));

// Penyusutan aset tetap: tanggal 1 tiap bulan.
Schedule::command('aset:penyusutan')
    ->monthlyOn(1, '01:00')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/aset-penyusutan.log'));

// Verifikasi integritas akuntansi setiap malam.
// Hasil ketidaksesuaian ditulis ke log (storage/logs) dan command exit code 1.
Schedule::command('accounting:verify-integrity')
    ->dailyAt('02:00')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/accounting-integrity.log'));
