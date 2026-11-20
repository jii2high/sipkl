<?php

use App\Jobs\ExpireMagicLinksJob;
use App\Jobs\ExpireVisitasiJob;
use App\Jobs\ProvisionalReminderJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat provisional harian (BR-F3-4). Jam operasional memakai Asia/Jakarta.
Schedule::job(new ProvisionalReminderJob)
    ->dailyAt('07:00')
    ->timezone('Asia/Jakarta')
    ->name('pengingat-provisional');

Schedule::job(new ExpireVisitasiJob)
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta')
    ->name('kedaluwarsa-visitasi');

Schedule::job(new ExpireMagicLinksJob)
    ->hourly()
    ->name('kedaluwarsa-magic-link');
